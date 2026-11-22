<?php
/**
 * Sea College Dive Club — Role-Based Unified ERP & Mobile App API (`/handlers/erp_api.php`)
 * Enforces strict per-employee RBAC authentication, granular permissions,
 * branch/vessel/sales-rep data scoping, and Daily/Weekly/Monthly/Quarterly/Yearly BI.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/erp_db.php';

sc_ensure_session();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Robots-Tag: noindex, nofollow');

$pdo = sc_erp_pdo();
if (!$pdo) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'ERP database temporarily unavailable']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$rawIn  = $method === 'POST' ? (json_decode((string) file_get_contents('php://input'), true) ?: $_POST) : $_GET;
$action = trim((string) ($rawIn['action'] ?? $_GET['action'] ?? 'me'));

/**
 * Resolve authenticated user + permission list from session.
 *
 * @return array{user: array<string,mixed>, perms: list<string>}|null
 */
function erp_auth_context(PDO $pdo): ?array
{
    $uname = (string) ($_SESSION['sc_erp_user'] ?? '');
    if ($uname === '') {
        return null;
    }
    $stmt = $pdo->prepare('SELECT u.*, r.name_en AS role_name_en, r.name_ar AS role_name_ar, r.hierarchy_level, r.default_dashboard
                           FROM users u JOIN roles r ON r.role_code = u.role_code
                           WHERE u.username = ? AND u.is_active = 1');
    $stmt->execute([$uname]);
    $u = $stmt->fetch();
    if (!$u) {
        unset($_SESSION['sc_erp_user']);
        return null;
    }
    unset($u['password_hash'], $u['pin_hint']);
    $pStmt = $pdo->prepare('SELECT perm_code FROM role_permissions WHERE role_code = ? ORDER BY perm_code');
    $pStmt->execute([$u['role_code']]);
    $perms = array_column($pStmt->fetchAll(), 'perm_code');
    return ['user' => $u, 'perms' => $perms];
}

function erp_require_perm(?array $ctx, string ...$anyOf): array
{
    if (!$ctx) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Authentication required']);
        exit;
    }
    foreach ($anyOf as $p) {
        if (in_array($p, $ctx['perms'], true)) {
            return $ctx;
        }
    }
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Insufficient role authority (' . implode(' | ', $anyOf) . ')']);
    exit;
}

/* ---------------- PUBLIC / AUTH ACTIONS ---------------- */
if ($action === 'login') {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'POST required']);
        exit;
    }
    $username = strtolower(trim((string) ($rawIn['username'] ?? '')));
    $password = (string) ($rawIn['password'] ?? '');
    $stmt = $pdo->prepare('SELECT * FROM users WHERE LOWER(username) = ? AND is_active = 1');
    $stmt->execute([$username]);
    $u = $stmt->fetch();
    if (!$u || !sc_erp_verify_password($password, (string) $u['password_hash'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Invalid username or password']);
        exit;
    }
    session_regenerate_id(true);
    $_SESSION['sc_erp_user'] = $u['username'];
    sc_erp_audit($pdo, (string) $u['username'], (string) $u['role_code'], 'USER_LOGIN', 'AUTH', (string) $u['username']);
    $ctx = erp_auth_context($pdo);
    echo json_encode(['ok' => true, 'user' => $ctx['user'], 'permissions' => $ctx['perms'], 'csrf_token' => csrf_token()], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'logout') {
    unset($_SESSION['sc_erp_user']);
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'directory_roles') {
    // Public metadata of roles & usernames (no passwords/hashes exposed!) for the corporate login selector
    $rows = $pdo->query('SELECT u.username, u.display_name_en, u.display_name_ar, u.role_code, u.branch_scope, u.vessel_scope, r.name_en AS role_en, r.name_ar AS role_ar, r.department
                         FROM users u JOIN roles r ON r.role_code = u.role_code
                         WHERE u.is_active = 1 ORDER BY r.hierarchy_level DESC, u.id ASC')->fetchAll();
    echo json_encode(['ok' => true, 'accounts' => $rows, 'csrf_token' => csrf_token()], JSON_UNESCAPED_UNICODE);
    exit;
}

$ctx = erp_auth_context($pdo);
if ($action === 'me') {
    if (!$ctx) {
        echo json_encode(['ok' => true, 'authenticated' => false, 'csrf_token' => csrf_token()]);
        exit;
    }
    echo json_encode(['ok' => true, 'authenticated' => true, 'user' => $ctx['user'], 'permissions' => $ctx['perms'], 'csrf_token' => csrf_token()], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------------- EXECUTIVE & OWNER MULTI-HORIZON BI ---------------- */
if ($action === 'executive_bi') {
    erp_require_perm($ctx, 'bi.view_executive');
    $horizon = strtolower((string) ($_GET['horizon'] ?? 'monthly'));
    if (!in_array($horizon, ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'], true)) {
        $horizon = 'monthly';
    }
    $yearFilter  = preg_replace('/[^0-9]/', '', (string) ($_GET['year'] ?? ''));
    $monthFilter = preg_replace('/[^0-9\-]/', '', (string) ($_GET['month'] ?? ''));

    // 1) Time-Series Trend across requested horizon
    $revBucketCol = match ($horizon) {
        'daily'     => 'rev_date',
        'weekly'    => 'period_week',
        'quarterly' => 'period_quarter',
        'yearly'    => 'period_year',
        default     => 'period_month',
    };

    if (in_array($horizon, ['monthly', 'quarterly', 'yearly'], true)) {
        $pnlCol = match ($horizon) {
            'quarterly' => 'period_quarter',
            'yearly'    => 'period_year',
            default     => 'period_month',
        };
        $whereSql = '';
        $params   = [];
        if ($yearFilter !== '') {
            $whereSql = ' WHERE period_year = ?';
            $params[] = $yearFilter;
        }
        $tStmt = $pdo->prepare(
            "SELECT {$pnlCol} AS period_key,
                    ROUND(SUM(CASE WHEN section_type='TOTAL_REV' THEN amount_egp ELSE 0 END), 2) AS revenue_egp,
                    ROUND(SUM(CASE WHEN section_type='TOTAL_EXP' THEN amount_egp ELSE 0 END), 2) AS expense_egp,
                    ROUND(SUM(CASE WHEN section_type='NET_PROFIT' THEN amount_egp ELSE 0 END), 2) AS net_profit_egp,
                    ROUND(SUM(CASE WHEN branch_code='CYRENE' AND section_type='NET_PROFIT' THEN amount_egp ELSE 0 END), 2) AS cyrene_profit_egp,
                    ROUND(SUM(CASE WHEN branch_code='JETTY' AND section_type='NET_PROFIT' THEN amount_egp ELSE 0 END), 2) AS jetty_profit_egp
             FROM pnl_monthly_lines {$whereSql}
             GROUP BY {$pnlCol}
             ORDER BY {$pnlCol} ASC"
        );
        $tStmt->execute($params);
        $trendSeries = $tStmt->fetchAll();
    } else {
        // Daily or Weekly granularity from `daily_revenue_fact` + `expense_vouchers_fact`
        $expCol = $horizon === 'daily' ? 'exp_date' : 'period_week';
        $wRev = $monthFilter !== '' ? ' WHERE period_month = ?' : ($yearFilter !== '' ? ' WHERE period_year = ?' : '');
        $pArg = $monthFilter !== '' ? [$monthFilter] : ($yearFilter !== '' ? [$yearFilter] : []);

        $rStmt = $pdo->prepare("SELECT {$revBucketCol} AS period_key,
                                       ROUND(SUM(cyrene_total_egp), 2) AS cyrene_rev,
                                       ROUND(SUM(jetty_total_egp), 2) AS jetty_rev,
                                       ROUND(SUM(combined_total_egp), 2) AS revenue_egp
                                FROM daily_revenue_fact {$wRev}
                                GROUP BY {$revBucketCol}
                                ORDER BY {$revBucketCol} DESC LIMIT 60");
        $rStmt->execute($pArg);
        $revRows = array_reverse($rStmt->fetchAll());

        $eStmt = $pdo->prepare("SELECT {$expCol} AS period_key, ROUND(SUM(total_egp), 2) AS cash_exp
                                FROM expense_vouchers_fact {$wRev}
                                GROUP BY {$expCol}");
        $eStmt->execute($pArg);
        $expMap = [];
        foreach ($eStmt->fetchAll() as $er) {
            $expMap[$er['period_key']] = (float) $er['cash_exp'];
        }
        $trendSeries = [];
        foreach ($revRows as $rr) {
            $pk  = $rr['period_key'];
            $rev = (float) $rr['revenue_egp'];
            $exp = $expMap[$pk] ?? 0.0;
            $trendSeries[] = [
                'period_key'        => $pk,
                'revenue_egp'       => $rev,
                'expense_egp'       => round($exp, 2),
                'net_profit_egp'    => round($rev - $exp, 2),
                'cyrene_profit_egp' => (float) $rr['cyrene_rev'],
                'jetty_profit_egp'  => (float) $rr['jetty_rev'],
            ];
        }
    }

    // 2) Headline KPIs (scoped to optional month or year filter)
    $pnlWhere = [];
    $pnlArgs  = [];
    if ($monthFilter !== '') {
        $pnlWhere[] = 'period_month = ?';
        $pnlArgs[]  = $monthFilter;
    } elseif ($yearFilter !== '') {
        $pnlWhere[] = 'period_year = ?';
        $pnlArgs[]  = $yearFilter;
    }
    $wStr = $pnlWhere ? (' WHERE ' . implode(' AND ', $pnlWhere)) : '';

    $kpiStmt = $pdo->prepare(
        "SELECT ROUND(SUM(CASE WHEN section_type='TOTAL_REV' THEN amount_egp ELSE 0 END), 2) AS total_revenue_egp,
                ROUND(SUM(CASE WHEN section_type='TOTAL_EXP' THEN amount_egp ELSE 0 END), 2) AS total_expense_egp,
                ROUND(SUM(CASE WHEN section_type='NET_PROFIT' THEN amount_egp ELSE 0 END), 2) AS net_profit_egp
         FROM pnl_monthly_lines {$wStr}"
    );
    $kpiStmt->execute($pnlArgs);
    $kpis = $kpiStmt->fetch() ?: ['total_revenue_egp' => 0, 'total_expense_egp' => 0, 'net_profit_egp' => 0];

    $resWhereExec = $wStr !== '' ? ($wStr . " AND booking_state = 'EXECUTED'") : " WHERE booking_state = 'EXECUTED'";
    $resStmt = $pdo->prepare(
        "SELECT SUM(CASE WHEN booking_state='EXECUTED' THEN 1 ELSE 0 END) AS vouchers_count,
                COALESCE(SUM(CASE WHEN booking_state='EXECUTED' THEN total_pax ELSE 0 END), 0) AS total_pax,
                SUM(CASE WHEN booking_state='CANCELLED_NOSHOW' THEN 1 ELSE 0 END) AS cancelled_vouchers,
                COALESCE(SUM(cancelled_pax), 0) AS cancelled_pax,
                ROUND(COALESCE(SUM(CASE WHEN booking_state='EXECUTED' THEN rest_egp_equiv ELSE 0 END), 0), 2) AS total_rest_egp
         FROM reservations_fact {$wStr}"
    );
    $resStmt->execute($pnlArgs);
    $resKpis = $resStmt->fetch() ?: ['vouchers_count' => 0, 'total_pax' => 0, 'cancelled_vouchers' => 0, 'cancelled_pax' => 0, 'total_rest_egp' => 0];

    // 3) Most Profitable Trips / Tours / Courses (combining P&L actual cash/accrued lines + Reservation PAX)
    $tripPnlStmt = $pdo->prepare(
        "SELECT trip_or_cost_group AS product_group,
                MAX(line_name_en) AS name_en,
                MAX(line_name_ar) AS name_ar,
                ROUND(SUM(amount_egp), 2) AS actual_revenue_egp,
                ROUND(AVG(COALESCE(cost_ratio, 0.42)), 3) AS avg_cost_ratio
         FROM pnl_monthly_lines
         WHERE section_type IN ('CASH_REV', 'ACCRUED_REV')
         " . ($pnlWhere ? ' AND ' . implode(' AND ', $pnlWhere) : '') . "
         GROUP BY trip_or_cost_group
         ORDER BY actual_revenue_egp DESC
         LIMIT 14"
    );
    $tripPnlStmt->execute($pnlArgs);
    $profitableTrips = [];
    foreach ($tripPnlStmt->fetchAll() as $tr) {
        $rev  = (float) $tr['actual_revenue_egp'];
        $cRat = match ($tr['product_group']) {
            'EXTRA_INTRO_UPSELL' => 0.14,
            'PADI_COURSES'       => 0.30,
            'CERTIFIED_DIVING'   => 0.36,
            'UNDERWATER_PHOTO'   => 0.52,
            'WEBSITE_ONLINE'     => 0.34,
            'PRIVATE_YACHT'      => 0.44,
            'INTRO_DIVING'       => 0.40,
            'DESERT_SAFARI'      => 0.48,
            'SPEEDBOAT_WATERSPORTS' => 0.49,
            default              => min(0.85, max(0.25, (float) $tr['avg_cost_ratio'])),
        };
        $cogs = round($rev * $cRat, 2);
        $net  = round($rev - $cogs, 2);
        $profitableTrips[] = [
            'product_group'  => $tr['product_group'],
            'name_en'        => $tr['name_en'],
            'name_ar'        => $tr['name_ar'],
            'revenue_egp'    => $rev,
            'direct_cogs_egp'=> $cogs,
            'net_margin_egp' => $net,
            'margin_pct'     => $rev > 0 ? round(($net / $rev) * 100, 1) : 0.0,
        ];
    }
    usort($profitableTrips, static fn($a, $b) => $b['net_margin_egp'] <=> $a['net_margin_egp']);

    // 4) Best Sellers (Sales Reps / Agencies & Top Source Nationalities — Executed Bookings Only)
    $repStmt = $pdo->prepare(
        "SELECT CASE WHEN sales_rep != '' THEN sales_rep ELSE 'DIRECT / COUNTER' END AS seller_name,
                COUNT(*) AS vouchers,
                SUM(total_pax) AS pax,
                ROUND(SUM(estimated_revenue_egp), 2) AS est_revenue_egp,
                ROUND(SUM(estimated_profit_egp), 2) AS est_profit_egp
         FROM reservations_fact {$resWhereExec}
         GROUP BY seller_name
         ORDER BY pax DESC
         LIMIT 14"
    );
    $repStmt->execute($pnlArgs);
    $bestSellers = $repStmt->fetchAll();

    $natStmt = $pdo->prepare(
        "SELECT nationality, COUNT(*) AS vouchers, SUM(total_pax) AS pax,
                ROUND(SUM(estimated_revenue_egp), 2) AS est_revenue_egp
         FROM reservations_fact {$resWhereExec}
         GROUP BY nationality
         ORDER BY pax DESC
         LIMIT 10"
    );
    $natStmt->execute($pnlArgs);
    $topNationalities = $natStmt->fetchAll();

    // 5) Most Profitable Boats & Marine Assets
    $boatStmt = $pdo->prepare(
        "SELECT v.code, v.name_en, v.name_ar, v.vessel_type, v.ownership_structure,
                v.monthly_fixed_rent_egp, v.drydock_capex_total_egp, v.drydock_sea_college_share_egp, v.drydock_partner_share_egp,
                COUNT(t.id) AS sea_days,
                COALESCE(SUM(t.total_pax), 0) AS total_pax,
                ROUND(COALESCE(SUM(t.total_direct_cost_egp), 0), 2) AS direct_trip_cost_egp,
                ROUND(COALESCE(SUM(t.estimated_trip_rev_egp), 0), 2) AS attributed_rev_egp,
                ROUND(COALESCE(SUM(t.estimated_trip_net_egp), 0), 2) AS operating_margin_egp
         FROM vessels v
         LEFT JOIN vessel_trips_fact t ON t.vessel_code = v.code " . ($pnlWhere ? ' AND t.' . implode(' AND t.', $pnlWhere) : '') . "
         GROUP BY v.code
         ORDER BY operating_margin_egp DESC, v.monthly_fixed_rent_egp DESC"
    );
    $boatStmt->execute($pnlArgs);
    $fleetAssets = $boatStmt->fetchAll();

    // Enrich Speedboats (Youana & Sharm Reeb) from speedboat_trips_fact
    $spMap = [];
    $spStmt = $pdo->prepare(
        "SELECT boat_code, COUNT(*) AS trips, ROUND(SUM(revenue_egp), 2) AS rev, ROUND(SUM(fuel_cost_egp), 2) AS fuel
         FROM speedboat_trips_fact {$wStr} GROUP BY boat_code"
    );
    $spStmt->execute($pnlArgs);
    foreach ($spStmt->fetchAll() as $sr) {
        $spMap[$sr['boat_code']] = $sr;
    }
    foreach ($fleetAssets as &$fa) {
        if (isset($spMap[$fa['code']])) {
            $sr = $spMap[$fa['code']];
            $fa['sea_days']            = (int) $sr['trips'];
            $fa['attributed_rev_egp']  = (float) $sr['rev'];
            $fa['direct_trip_cost_egp']= (float) $sr['fuel'];
            $fa['operating_margin_egp']= round((float) $sr['rev'] - (float) $sr['fuel'], 2);
        }
    }
    unset($fa);

    // 6) Multi-Currency Treasury Safes, Warehouse Stats & May–Nov 2026 Audit Summary
    $safes = $pdo->query('SELECT * FROM safes_wallets ORDER BY safe_type, code')->fetchAll();
    $whCounts = [
        'workbooks_ingested' => (int) $pdo->query("SELECT COUNT(*) FROM etl_workbooks_catalog WHERE status LIKE 'INGESTED%'")->fetchColumn(),
        'raw_rows_archived'  => (int) $pdo->query('SELECT COUNT(*) FROM raw_sheet_rows')->fetchColumn(),
        'expense_vouchers'   => (int) $pdo->query('SELECT COUNT(*) FROM expense_vouchers_fact')->fetchColumn(),
        'subledger_rows'     => (int) $pdo->query('SELECT COUNT(*) FROM subledger_lines')->fetchColumn(),
        'payroll_records'    => (int) $pdo->query('SELECT COUNT(*) FROM payroll_monthly_fact')->fetchColumn(),
        'gear_assets'        => (int) $pdo->query('SELECT COUNT(*) FROM equipment_assets_master')->fetchColumn(),
        'website_crm_leads'  => (int) $pdo->query('SELECT COUNT(*) FROM website_leads_crm')->fetchColumn(),
        'journal_entries'    => (int) $pdo->query('SELECT COUNT(*) FROM journal_entries')->fetchColumn(),
        'gl_balanced_egp'    => (float) $pdo->query('SELECT COALESCE(ROUND(SUM(debit_egp),2),0) FROM journal_lines')->fetchColumn(),
    ];

    $availMonths = array_column($pdo->query('SELECT DISTINCT period_month FROM pnl_monthly_lines ORDER BY period_month DESC')->fetchAll(), 'period_month');

    echo json_encode([
        'ok'                => true,
        'horizon'           => $horizon,
        'available_months'  => $availMonths,
        'kpis'              => $kpis + $resKpis,
        'trend_series'      => $trendSeries,
        'profitable_trips'  => $profitableTrips,
        'best_sellers'      => $bestSellers,
        'top_nationalities' => $topNationalities,
        'fleet_assets'      => $fleetAssets,
        'safes'             => $safes,
        'warehouse_counts'  => $whCounts,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------------- ROLE-SCOPED WORKSPACE DATA ---------------- */
if ($action === 'role_workspace') {
    if (!$ctx) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Authentication required']);
        exit;
    }
    $user  = $ctx['user'];
    $perms = $ctx['perms'];
    $out   = ['ok' => true, 'role' => $user['role_code']];

    // 1) Fleet Partner Scoped View (Eng. Darwish / Eng. Farid ONLY see their own vessels!)
    if (in_array('bi.view_partner_fleet', $perms, true)) {
        $vScope = trim((string) $user['vessel_scope']);
        if ($vScope !== '' && $vScope !== 'ALL') {
            $codes = array_values(array_filter(array_map('trim', explode(',', $vScope))));
            $ph    = implode(',', array_fill(0, count($codes), '?'));
            $vSt   = $pdo->prepare("SELECT * FROM vessels WHERE code IN ({$ph})");
            $vSt->execute($codes);
            $out['partner_vessels'] = $vSt->fetchAll();

            $tSt = $pdo->prepare("SELECT * FROM vessel_trips_fact WHERE vessel_code IN ({$ph}) ORDER BY trip_date DESC LIMIT 80");
            $tSt->execute($codes);
            $out['partner_trips'] = $tSt->fetchAll();

            $dSt = $pdo->prepare("SELECT * FROM drydock_capex_lines WHERE vessel_code IN ({$ph}) ORDER BY total_egp DESC LIMIT 80");
            $dSt->execute($codes);
            $out['partner_drydock'] = $dSt->fetchAll();
        } else {
            $out['partner_vessels'] = $pdo->query('SELECT * FROM vessels ORDER BY code')->fetchAll();
            $out['partner_trips']   = $pdo->query('SELECT * FROM vessel_trips_fact ORDER BY trip_date DESC LIMIT 60')->fetchAll();
            $out['partner_drydock'] = $pdo->query('SELECT * FROM drydock_capex_lines ORDER BY total_egp DESC LIMIT 60')->fetchAll();
        }
    }

    // 2) Chief Accountant Ledgers, May-Nov 2026 Reservation-to-GL Bridge, AR/AP, Payroll & ETL Status
    if (in_array('gl.view_all_ledgers', $perms, true)) {
        $out['counterparties'] = $pdo->query('SELECT * FROM counterparties ORDER BY ABS(current_balance_egp) DESC LIMIT 50')->fetchAll();
        $out['recent_expenses']= $pdo->query('SELECT * FROM expense_vouchers_fact ORDER BY exp_date DESC, id DESC LIMIT 60')->fetchAll();
        $out['etl_catalog']    = $pdo->query('SELECT rel_path, category, sheet_count, rows_ingested, status FROM etl_workbooks_catalog ORDER BY status DESC, rows_ingested DESC LIMIT 80')->fetchAll();
        $out['res_acc_audit']  = $pdo->query('SELECT * FROM reservation_accounting_audit ORDER BY period_month ASC')->fetchAll();
        $out['gl_journals']    = $pdo->query(
            'SELECT je.entry_no, je.entry_date, je.description_en,
                    ROUND(SUM(jl.debit_egp), 2) AS total_debit_egp,
                    ROUND(SUM(jl.credit_egp), 2) AS total_credit_egp,
                    COUNT(jl.id) AS lines_count
             FROM journal_entries je JOIN journal_lines jl ON jl.journal_id = je.id
             GROUP BY je.id ORDER BY je.entry_date DESC LIMIT 60'
        )->fetchAll();
    }
    if (in_array('hr.manage_payroll', $perms, true)) {
        $out['payroll_recent'] = $pdo->query('SELECT * FROM payroll_monthly_fact ORDER BY period_month DESC, net_pay_egp DESC LIMIT 60')->fetchAll();
    }

    // 3) Treasury / Cashier Safes & Daily Cashbox
    if (in_array('safe.view_balances', $perms, true)) {
        $bScope = (string) $user['branch_scope'];
        if ($bScope !== 'ALL' && in_array($bScope, ['CYRENE', 'JETTY', 'JAZ'], true)) {
            $sSt = $pdo->prepare('SELECT * FROM safes_wallets WHERE branch_code = ?');
            $sSt->execute([$bScope]);
            $out['safes'] = $sSt->fetchAll();
        } else {
            $out['safes'] = $pdo->query('SELECT * FROM safes_wallets ORDER BY safe_type')->fetchAll();
        }
        $out['cashbox_recent'] = $pdo->query('SELECT * FROM cashbox_daily_fact ORDER BY tx_date DESC LIMIT 45')->fetchAll();
    }

    // 4) Operations / Jetty / Crew / Sales Vouchers (strictly scoped for Sales Reps!)
    if (in_array('ops.view_manifests', $perms, true) || in_array('jetty.scan_boarding_collect_rest', $perms, true)) {
        $out['vouchers'] = $pdo->query('SELECT * FROM reservations_fact ORDER BY trip_date DESC, id DESC LIMIT 80')->fetchAll();
    } elseif (in_array('sales.view_own_sales', $perms, true)) {
        $rep = trim((string) $user['rep_scope']);
        $vSt = $pdo->prepare('SELECT * FROM reservations_fact WHERE sales_rep LIKE ? ORDER BY trip_date DESC, id DESC LIMIT 80');
        $vSt->execute(['%' . ($rep !== '' ? $rep : $user['username']) . '%']);
        $myRows = $vSt->fetchAll();
        $sSt = $pdo->prepare('SELECT COUNT(*) AS total_vouchers, COALESCE(SUM(total_pax),0) AS total_pax, ROUND(COALESCE(SUM(estimated_revenue_egp),0),2) AS est_rev_egp
                              FROM reservations_fact WHERE sales_rep LIKE ?');
        $sSt->execute(['%' . ($rep !== '' ? $rep : $user['username']) . '%']);
        $out['vouchers']       = $myRows;
        $out['my_sales_stats'] = $sSt->fetch();
    } elseif (in_array('crew.view_own_manifest', $perms, true)) {
        $out['vouchers'] = $pdo->query("SELECT * FROM reservations_fact WHERE dives_pax > 0 OR intro1_pax > 0 OR intro2_pax > 0 ORDER BY trip_date DESC LIMIT 50")->fetchAll();
        $out['checkins'] = $pdo->query("SELECT * FROM website_leads_crm WHERE lead_type = 'DIVER_CHECKIN' ORDER BY id DESC LIMIT 30")->fetchAll();
    }

    // 5) Gear Assets for Jetty / Instructors
    if (in_array('jetty.manage_gear_photo', $perms, true)) {
        $out['gear_assets'] = $pdo->query('SELECT * FROM equipment_assets_master ORDER BY id ASC LIMIT 120')->fetchAll();
    }

    // 6) Website CRM & Paid Ads Leads for Lara / Hossam / Ops / Owners
    if (in_array('crm.manage_leads_ads', $perms, true)) {
        $out['crm_leads'] = $pdo->query('SELECT * FROM website_leads_crm ORDER BY id DESC LIMIT 80')->fetchAll();
    }

    // 7) Catalog & Sharm Hotel Zones reference for voucher desk & concierge
    if (in_array('sales.create_voucher', $perms, true) || in_array('crm.manage_leads_ads', $perms, true)) {
        $out['catalog'] = $pdo->query('SELECT code, name_en, name_ar, category_group, price_eur, price_egp_est FROM products_catalog WHERE price_eur IS NOT NULL ORDER BY is_popular DESC, code ASC')->fetchAll();
        $out['hotels']  = $pdo->query('SELECT code, name_en, zone_label_en, zone_label_ar, pickup_window FROM hotels_directory ORDER BY zone_code, name_en')->fetchAll();
    }

    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------------- MUTATING OPERATIONAL ACTIONS (POST + CSRF + RBAC) ---------------- */
if ($method === 'POST') {
    $csrfTok = (string) ($rawIn['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!csrf_verify($csrfTok)) {
        http_response_code(419);
        echo json_encode(['ok' => false, 'error' => 'CSRF token expired — please refresh']);
        exit;
    }

    if ($action === 'create_voucher') {
        erp_require_perm($ctx, 'sales.create_voucher');
        $user     = $ctx['user'];
        $tripDt   = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($rawIn['trip_date'] ?? '')) ? (string) $rawIn['trip_date'] : gmdate('Y-m-d');
        [$wk, $mo, $qr, $yr] = sc_erp_period_parts($tripDt);
        $voNo     = 'VO-' . random_int(70000, 99999);
        $qrTok    = 'QR-' . strtoupper(bin2hex(random_bytes(6)));
        $guest    = clean_line($rawIn['guest_name'] ?? 'Resort Guest', 100);
        $hotel    = clean_line($rawIn['hotel_name'] ?? 'Cyrene Sharm Hotel', 100);
        $room     = clean_line($rawIn['room_no'] ?? '', 20);
        $nat      = strtoupper(clean_line($rawIn['nationality'] ?? 'ITALY', 24));
        $pickup   = clean_line($rawIn['pickup_time'] ?? '08:00', 20);
        $prodCode = clean_line($rawIn['product_code'] ?? 'dsd-2-boat', 40);
        $adults   = max(1, min(60, (int) ($rawIn['adults'] ?? 2)));
        $children = max(0, min(40, (int) ($rawIn['children'] ?? 0)));
        $pax      = $adults + $children;
        $restEur  = max(0.0, (float) ($rawIn['rest_eur'] ?? 0));

        $pSt = $pdo->prepare('SELECT * FROM products_catalog WHERE code = ?');
        $pSt->execute([$prodCode]);
        $prod = $pSt->fetch();
        $tripName = $prod ? (string) $prod['name_en'] : $prodCode;
        $unitEur  = $prod && $prod['price_eur'] ? (float) $prod['price_eur'] : 55.0;
        $revEgp   = round(($adults + 0.5 * $children) * $unitEur * 54.0, 2);
        $cogsEgp  = round($revEgp * ((float) ($prod['direct_cost_ratio'] ?? 0.38)), 2);
        $profEgp  = round($revEgp - $cogsEgp, 2);
        $restEq   = round($restEur * 54.0, 2);
        $repName  = $user['rep_scope'] !== '' ? (string) $user['rep_scope'] : strtoupper((string) $user['username']);
        $branch   = $user['branch_scope'] !== 'ALL' ? (string) $user['branch_scope'] : 'CYRENE';

        $ins = $pdo->prepare(
            'INSERT INTO reservations_fact
             (voucher_no, qr_token, trip_date, period_week, period_month, period_quarter, period_year,
              branch_code, channel, hotel_name, pickup_zone, room_no, nationality, pickup_time,
              trip_name, product_category, adults, children, infants, total_pax,
              snork_pax, intro1_pax, intro2_pax, dives_pax, divers_names,
              sales_rep, executor, client_agency, rest_egp, rest_usd, rest_eur, rest_gbp, rest_egp_equiv,
              estimated_revenue_egp, estimated_cogs_egp, estimated_profit_egp, vessel_code, boarding_status, payment_status, source_file)
             VALUES (?,?,?,?,?,?,?, ?,?,?,?,?,?,?, ?,?,?,?,?,?, 0,0,0,0,?, ?,?,?, 0,0,?,?,?, ?,?,?, ?,?,?,?)'
        );
        $ins->execute([
            $voNo, $qrTok, $tripDt, $wk, $mo, $qr, $yr,
            $branch, 'MOBILE_APP', $hotel, '', $room, $nat, $pickup,
            $tripName, 'MOBILE_VOUCHER', $adults, $children, 0, $pax,
            $guest, $repName, 'SEA COLLEGE', 'DIRECT',
            $restEur, $restEq, $revEgp, $cogsEgp, $profEgp,
            '', 'BOOKED', $restEq > 0 ? 'OPEN_REST' : 'PAID', 'LIVE_MOBILE_APP',
        ]);
        sc_erp_audit($pdo, (string) $user['username'], (string) $user['role_code'], 'CREATE_VOUCHER', 'VOUCHER', $voNo, ['pax' => $pax, 'rev_egp' => $revEgp]);
        echo json_encode(['ok' => true, 'voucher_no' => $voNo, 'qr_token' => $qrTok, 'estimated_revenue_egp' => $revEgp]);
        exit;
    }

    if ($action === 'assign_dispatch') {
        erp_require_perm($ctx, 'ops.assign_boat_driver');
        $id     = (int) ($rawIn['id'] ?? 0);
        $vessel = clean_line($rawIn['vessel_code'] ?? 'OCEANO', 32);
        $pickup = clean_line($rawIn['pickup_time'] ?? '07:40', 20);
        $upd = $pdo->prepare("UPDATE reservations_fact SET vessel_code = ?, pickup_time = ?, boarding_status = 'DISPATCHED' WHERE id = ?");
        $upd->execute([$vessel, $pickup, $id]);
        sc_erp_audit($pdo, (string) $ctx['user']['username'], (string) $ctx['user']['role_code'], 'ASSIGN_BOAT', 'VOUCHER_ID', (string) $id, ['vessel' => $vessel]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'collect_rest_board') {
        erp_require_perm($ctx, 'jetty.scan_boarding_collect_rest');
        $id  = (int) ($rawIn['id'] ?? 0);
        $vSt = $pdo->prepare('SELECT * FROM reservations_fact WHERE id = ?');
        $vSt->execute([$id]);
        $v = $vSt->fetch();
        if (!$v) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Voucher not found']);
            exit;
        }
        $collectedEgp = (float) $v['rest_egp_equiv'];
        $pdo->prepare("UPDATE reservations_fact SET rest_egp = 0, rest_usd = 0, rest_eur = 0, rest_gbp = 0, rest_egp_equiv = 0, payment_status = 'PAID', boarding_status = 'BOARDED' WHERE id = ?")->execute([$id]);
        if ($collectedEgp > 0) {
            $pdo->prepare("UPDATE safes_wallets SET bal_egp = bal_egp + ?, updated_at = ? WHERE code = 'SAFE_JETTY'")->execute([$collectedEgp, gmdate('c')]);
        }
        sc_erp_audit($pdo, (string) $ctx['user']['username'], (string) $ctx['user']['role_code'], 'JETTY_BOARD_COLLECT_REST', 'VOUCHER', (string) $v['voucher_no'], ['collected_egp' => $collectedEgp]);
        echo json_encode(['ok' => true, 'collected_egp' => $collectedEgp]);
        exit;
    }

    if ($action === 'record_safe_tx') {
        erp_require_perm($ctx, 'safe.record_tx');
        $safeCode = clean_line($rawIn['safe_code'] ?? 'SAFE_CYRENE', 32);
        $txType   = strtoupper((string) ($rawIn['tx_type'] ?? 'EXPENSE'));
        $curr     = strtoupper((string) ($rawIn['currency'] ?? 'EGP'));
        $amt      = max(0.01, (float) ($rawIn['amount'] ?? 0));
        $desc     = clean_line($rawIn['description'] ?? 'Safe transaction', 200);
        $colMap   = ['EGP' => 'bal_egp', 'USD' => 'bal_usd', 'EUR' => 'bal_eur', 'GBP' => 'bal_gbp'];
        $col      = $colMap[$curr] ?? 'bal_egp';
        $sign     = $txType === 'INCOME' ? 1.0 : -1.0;

        $pdo->prepare("UPDATE safes_wallets SET {$col} = {$col} + ?, updated_at = ? WHERE code = ?")->execute([$sign * $amt, gmdate('c'), $safeCode]);
        $today = gmdate('Y-m-d');
        [$wk, $mo, $qr, $yr] = sc_erp_period_parts($today);
        if ($txType === 'EXPENSE') {
            $fxMap = ['EGP' => 1.0, 'USD' => 50.0, 'EUR' => 54.0, 'GBP' => 63.0];
            $totEgp = round($amt * ($fxMap[$curr] ?? 1.0), 2);
            $pdo->prepare('INSERT INTO expense_vouchers_fact (exp_date, period_week, period_month, period_quarter, period_year, voucher_seq, description, cost_center, amount_le, amount_usd, amount_eur, amount_gbp, total_egp, source_file)
                           VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([
                $today, $wk, $mo, $qr, $yr, 'APP-' . random_int(1000, 9999), $desc, 'TREASURY_APP',
                $curr === 'EGP' ? $amt : 0, $curr === 'USD' ? $amt : 0, $curr === 'EUR' ? $amt : 0, $curr === 'GBP' ? $amt : 0,
                $totEgp, 'LIVE_MOBILE_SAFE',
            ]);
        }
        sc_erp_audit($pdo, (string) $ctx['user']['username'], (string) $ctx['user']['role_code'], 'SAFE_' . $txType, 'SAFE', $safeCode, ['currency' => $curr, 'amount' => $amt, 'desc' => $desc]);
        echo json_encode(['ok' => true]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Unknown API action']);
