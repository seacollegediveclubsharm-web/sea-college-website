<?php
/**
 * Sea College Dive Club — ERP v3 Extensions (`includes/erp_v3_extensions.php`)
 * Built collaboratively by:
 *   - Karim (ERP & Mobile Gamification Architect)
 *   - Nadia (Chief Operational Accountant & Reservations-to-Cash Auditor)
 *   - Tarek (CFO & Multi-Horizon Seller Performance / Awards BI)
 *   - Ayman (Full-Stack Integrations & Shareable Seller Brochure Engineer)
 *   - Hana (Visual Creative & Excursion Catalog Custodian)
 *
 * Provides:
 *   1. Idempotent v3 tables (`master_trips_catalog`, `voucher_change_audit_log`, `user_notifications`,
 *      `seller_commission_advances`, `seller_targets_config`) & `reservations_fact` adjustment columns.
 *   2. Unified trip & Sharm excursion catalog seeding + historical sheet alias resolution + Adult/Child cost engine.
 *   3. Bulletproof image path resolver (`sc_erp_resolve_asset_webp`) eliminating broken trip photos.
 *   4. Strict "Actual Executed + 100% Paid" commission wallet engine + mid-month allowance/installment tracker.
 *   5. Multi-horizon Seller Performance BI (Daily, Weekly, Monthly, Yearly, Custom Range) & Best Seller Trophy Awards.
 */
declare(strict_types=1);

/**
 * Resolve any catalog/excursion/operational image path to a verified web path on disk.
 */
function sc_erp_resolve_asset_webp(string $relPath): string
{
    $fallback = '/assets/images/fleet/sea-college-oceano-yacht-01-exterior-nov2026.webp';
    $trim = trim($relPath);
    if ($trim === '') {
        return $fallback;
    }
    $clean = ltrim($trim, '/');
    $cleanNoAssets = (string) preg_replace('#^assets/#i', '', $clean);
    $cleanNoImages = (string) preg_replace('#^(?:assets/)?images/#i', '', $clean);

    $candidates = [
        '/assets/' . $cleanNoAssets,
        '/assets/images/' . $cleanNoImages,
        '/' . $clean,
    ];
    foreach ($candidates as $cand) {
        $webp = (string) preg_replace('/\.(jpe?g|png)$/i', '.webp', $cand);
        if ($webp !== $cand && is_file(ROOT_DIR . $webp)) {
            return $webp;
        }
        if (is_file(ROOT_DIR . $cand)) {
            return $cand;
        }
    }
    return $fallback;
}

/**
 * Ensure all v3 tables, columns, unified master trips, and default target settings exist idempotently.
 */
function sc_erp_ensure_v3_extensions(PDO $pdo): void
{
    $haveResCols = array_flip(array_column($pdo->query('PRAGMA table_info(reservations_fact)')->fetchAll(), 'name'));
    $resAddCols = [
        'unified_trip_code'    => "TEXT NOT NULL DEFAULT ''",
        'adult_unit_eur'       => 'REAL NOT NULL DEFAULT 0',
        'child_unit_eur'       => 'REAL NOT NULL DEFAULT 0',
        'infant_unit_eur'      => 'REAL NOT NULL DEFAULT 0',
        'booked_adults'        => 'INTEGER NOT NULL DEFAULT 0',
        'booked_children'      => 'INTEGER NOT NULL DEFAULT 0',
        'booked_infants'       => 'INTEGER NOT NULL DEFAULT 0',
        'booked_total_egp'     => 'REAL NOT NULL DEFAULT 0',
        'collected_rest_egp'   => 'REAL NOT NULL DEFAULT 0',
        'actual_paid_total_egp'=> 'REAL NOT NULL DEFAULT 0',
        'workflow_stage'       => "TEXT NOT NULL DEFAULT 'CONFIRMED_OPS'",
        'has_modification'     => 'INTEGER NOT NULL DEFAULT 0',
        'modification_summary' => "TEXT NOT NULL DEFAULT ''",
    ];
    foreach ($resAddCols as $col => $ddl) {
        if (!isset($haveResCols[$col])) {
            $pdo->exec("ALTER TABLE reservations_fact ADD COLUMN {$col} {$ddl}");
        }
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS master_trips_catalog (
            trip_code TEXT PRIMARY KEY,
            unified_name_en TEXT NOT NULL,
            unified_name_ar TEXT NOT NULL,
            category_code TEXT NOT NULL,
            supplier_code TEXT NOT NULL DEFAULT 'SEA_COLLEGE',
            supplier_name TEXT NOT NULL DEFAULT 'Sea College Dive Club',
            sale_mode TEXT NOT NULL DEFAULT 'INSHEET',
            comm_rate REAL NOT NULL DEFAULT 0.10,
            adult_sell_eur REAL NOT NULL DEFAULT 0,
            child_sell_eur REAL NOT NULL DEFAULT 0,
            infant_sell_eur REAL NOT NULL DEFAULT 0,
            adult_cost_egp REAL NOT NULL DEFAULT 0,
            child_cost_egp REAL NOT NULL DEFAULT 0,
            infant_cost_egp REAL NOT NULL DEFAULT 0,
            is_website_public INTEGER NOT NULL DEFAULT 0,
            cover_webp TEXT NOT NULL DEFAULT '',
            aliases_json TEXT NOT NULL DEFAULT '[]'
        );
        CREATE TABLE IF NOT EXISTS voucher_change_audit_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            created_at TEXT NOT NULL,
            voucher_no TEXT NOT NULL,
            trip_date TEXT NOT NULL,
            period_month TEXT NOT NULL,
            seller_username TEXT NOT NULL DEFAULT '',
            changed_by_username TEXT NOT NULL,
            changed_by_role TEXT NOT NULL,
            change_type TEXT NOT NULL,
            prev_adults INTEGER NOT NULL DEFAULT 0,
            prev_children INTEGER NOT NULL DEFAULT 0,
            prev_infants INTEGER NOT NULL DEFAULT 0,
            new_adults INTEGER NOT NULL DEFAULT 0,
            new_children INTEGER NOT NULL DEFAULT 0,
            new_infants INTEGER NOT NULL DEFAULT 0,
            prev_total_egp REAL NOT NULL DEFAULT 0,
            new_total_egp REAL NOT NULL DEFAULT 0,
            delta_total_egp REAL NOT NULL DEFAULT 0,
            extra_collected_egp REAL NOT NULL DEFAULT 0,
            prev_comm_egp REAL NOT NULL DEFAULT 0,
            new_comm_egp REAL NOT NULL DEFAULT 0,
            reason_notes TEXT NOT NULL DEFAULT ''
        );
        CREATE INDEX IF NOT EXISTS idx_vcal_voucher ON voucher_change_audit_log(voucher_no, period_month);
        CREATE TABLE IF NOT EXISTS user_notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            created_at TEXT NOT NULL,
            recipient_username TEXT NOT NULL,
            notif_type TEXT NOT NULL,
            voucher_no TEXT NOT NULL DEFAULT '',
            title_en TEXT NOT NULL,
            title_ar TEXT NOT NULL,
            body_en TEXT NOT NULL,
            body_ar TEXT NOT NULL,
            is_read INTEGER NOT NULL DEFAULT 0
        );
        CREATE INDEX IF NOT EXISTS idx_unotif_recip ON user_notifications(recipient_username, is_read, id);
        CREATE TABLE IF NOT EXISTS seller_commission_advances (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            created_at TEXT NOT NULL,
            period_month TEXT NOT NULL,
            tx_date TEXT NOT NULL,
            seller_username TEXT NOT NULL,
            rep_scope TEXT NOT NULL DEFAULT '',
            tx_type TEXT NOT NULL DEFAULT 'ALLOWANCE_ADVANCE',
            amount_egp REAL NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT 'REQUESTED',
            approved_by TEXT NOT NULL DEFAULT '',
            notes TEXT NOT NULL DEFAULT ''
        );
        CREATE INDEX IF NOT EXISTS idx_sca_seller ON seller_commission_advances(seller_username, period_month, status);
        CREATE TABLE IF NOT EXISTS seller_targets_config (
            seller_username TEXT PRIMARY KEY,
            is_target_active INTEGER NOT NULL DEFAULT 1,
            daily_target_egp REAL NOT NULL DEFAULT 15000,
            weekly_target_egp REAL NOT NULL DEFAULT 95000,
            monthly_target_egp REAL NOT NULL DEFAULT 380000,
            monthly_trips_target INTEGER NOT NULL DEFAULT 65,
            updated_by TEXT NOT NULL DEFAULT 'SYSTEM',
            updated_at TEXT NOT NULL DEFAULT ''
        );"
    );

    $pdo->exec(
        "INSERT OR IGNORE INTO seller_targets_config
         (seller_username, is_target_active, daily_target_egp, weekly_target_egp, monthly_target_egp, monthly_trips_target, updated_by, updated_at)
         VALUES ('__GLOBAL__', 1, 15000, 95000, 380000, 65, 'SYSTEM', '2026-10-01T00:00:00Z')"
    );

    $tripCnt = (int) $pdo->query('SELECT COUNT(*) FROM master_trips_catalog')->fetchColumn();
    if ($tripCnt < 30) {
        sc_erp_sync_master_trips_catalog($pdo);
    }
}

/**
 * Populate `master_trips_catalog` from `data/app_excursions_catalog.php`, `data/operational_skus.php`, and `data/catalog.php`.
 */
function sc_erp_sync_master_trips_catalog(PDO $pdo): void
{
    $fx = sc_erp_fx_rates($pdo);
    $eurRate = $fx['EUR'] ?? 57.0;

    $stmt = $pdo->prepare(
        'INSERT OR REPLACE INTO master_trips_catalog
         (trip_code, unified_name_en, unified_name_ar, category_code, supplier_code, supplier_name,
          sale_mode, comm_rate, adult_sell_eur, child_sell_eur, infant_sell_eur,
          adult_cost_egp, child_cost_egp, infant_cost_egp, is_website_public, cover_webp, aliases_json)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    );

    $pdo->beginTransaction();
    try {
        if (is_file(ROOT_DIR . '/data/app_excursions_catalog.php')) {
            $excs = require ROOT_DIR . '/data/app_excursions_catalog.php';
            foreach ($excs as $ex) {
                $stmt->execute([
                    (string) $ex['id'],
                    (string) ($ex['names']['en'] ?? $ex['id']),
                    (string) ($ex['names']['ar'] ?? $ex['names']['en'] ?? $ex['id']),
                    (string) ($ex['category_group'] ?? 'excursions'),
                    (string) ($ex['supplier_code'] ?? 'OUTSIDE_VENDOR'),
                    (string) ($ex['supplier_name'] ?? 'Sharm Excursion Partner'),
                    (string) ($ex['default_sale_mode'] ?? 'OUT_SIDE'),
                    (float) ($ex['default_comm_pct'] ?? 0.25),
                    (float) ($ex['price_eur'] ?? 30),
                    (float) ($ex['child_price_eur'] ?? round(((float) ($ex['price_eur'] ?? 30)) * 0.5, 2)),
                    (float) ($ex['infant_price_eur'] ?? 0),
                    (float) ($ex['adult_cost_egp'] ?? 500),
                    (float) ($ex['child_cost_egp'] ?? 250),
                    (float) ($ex['infant_cost_egp'] ?? 0),
                    0,
                    sc_erp_resolve_asset_webp((string) ($ex['cover_webp'] ?? '')),
                    json_encode($ex['aliases'] ?? [], JSON_UNESCAPED_UNICODE) ?: '[]',
                ]);
            }
        }

        if (is_file(ROOT_DIR . '/data/operational_skus.php')) {
            $ops = require ROOT_DIR . '/data/operational_skus.php';
            foreach ($ops as $sk) {
                $pEur = (float) ($sk['price_eur'] ?? 30);
                $cEur = isset($sk['child_price_eur']) && $sk['child_price_eur'] !== null ? (float) $sk['child_price_eur'] : $pEur;
                $aCost = (float) ($sk['default_supplier_cost_egp'] ?? 0);
                $stmt->execute([
                    (string) $sk['id'],
                    (string) ($sk['names']['en'] ?? $sk['id']),
                    (string) ($sk['names']['ar'] ?? $sk['names']['en'] ?? $sk['id']),
                    (string) ($sk['category_group'] ?? 'operational'),
                    ((string) ($sk['default_sale_mode'] ?? 'INSHEET')) === 'OUT_SIDE' ? 'AP_WATERSPORTS_SAFARI' : 'SEA_COLLEGE',
                    ((string) ($sk['default_sale_mode'] ?? 'INSHEET')) === 'OUT_SIDE' ? 'Contracted Watersports / Safari Partner' : 'Sea College Dive Club',
                    (string) ($sk['default_sale_mode'] ?? 'INSHEET'),
                    (float) ($sk['default_comm_pct'] ?? 0.10),
                    $pEur,
                    $cEur,
                    0.0,
                    $aCost,
                    round($aCost * 0.5, 2),
                    0.0,
                    0,
                    sc_erp_resolve_asset_webp((string) ($sk['cover_webp'] ?? '')),
                    '[]',
                ]);
            }
        }

        if (is_file(ROOT_DIR . '/data/catalog.php')) {
            $prevLg = $GLOBALS['SC_LANG'] ?? 'en';
            $GLOBALS['SC_LANG'] = 'en';
            $cat = require ROOT_DIR . '/data/catalog.php';
            $GLOBALS['SC_LANG'] = $prevLg;
            foreach ($cat as $catKey => $catMeta) {
                foreach (($catMeta['groups'] ?? []) as $grp) {
                    foreach (($grp['items'] ?? []) as $it) {
                        $id = (string) ($it['id'] ?? '');
                        if ($id === '' || ($it['price'] ?? null) === null) {
                            continue;
                        }
                        $pEur = (float) $it['price'];
                        $isThistlegorm = str_contains($id, 'thistlegorm');
                        $supCode = $isThistlegorm ? 'AP_THISTLEGORM' : 'SEA_COLLEGE';
                        $supName = $isThistlegorm ? 'SS Thistlegorm Marine Charter' : 'Sea College PADI 5* Dive Center';
                        $aCost = $isThistlegorm ? round(100.0 * $eurRate, 0) : round($pEur * $eurRate * 0.35, 0);
                        $cEur  = str_contains($catKey, 'snork') ? round($pEur * 0.5, 0) : $pEur;
                        $stmt->execute([
                            $id,
                            (string) ($it['name'] ?? $id),
                            (string) ($it['name_ar'] ?? $it['name'] ?? $id),
                            (string) $catKey,
                            $supCode,
                            $supName,
                            'INSHEET',
                            0.10,
                            $pEur,
                            $cEur,
                            0.0,
                            $aCost,
                            round($aCost * 0.5, 0),
                            0.0,
                            1,
                            sc_erp_resolve_asset_webp((string) ($it['img'] ?? '')),
                            '[]',
                        ]);
                    }
                }
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('SC_ERP master_trips_catalog sync warning: ' . $e->getMessage());
    }
}

/**
 * Resolve any product code OR raw reservation trip name to its unified `master_trips_catalog` row.
 */
function sc_erp_lookup_unified_trip(PDO $pdo, string $codeOrName): ?array
{
    $needle = trim($codeOrName);
    if ($needle === '') {
        return null;
    }
    $st = $pdo->prepare('SELECT * FROM master_trips_catalog WHERE LOWER(trip_code) = LOWER(?) OR LOWER(unified_name_en) = LOWER(?) LIMIT 1');
    $st->execute([$needle, $needle]);
    $direct = $st->fetch();
    if ($direct) {
        return $direct;
    }

    $up = strtoupper((string) preg_replace('/\s+/', ' ', $needle));
    $all = $pdo->query('SELECT * FROM master_trips_catalog')->fetchAll();
    foreach ($all as $row) {
        $aliases = json_decode((string) ($row['aliases_json'] ?? '[]'), true);
        if (is_array($aliases)) {
            foreach ($aliases as $al) {
                $alUp = strtoupper(trim((string) $al));
                if ($alUp !== '' && ($up === $alUp || str_starts_with($up, $alUp))) {
                    return $row;
                }
            }
        }
    }
    // Keyword heuristic fallback for historical spreadsheet variants
    $kwMap = [
        'WHITE ISLAND' => 'exc-white-island-yacht',
        'TIRAN'        => 'exc-tiran-island-yacht',
        'DINNER'       => 'exc-romantic-dinner-cruise',
        'GRAND MUSEUM' => 'exc-cairo-grand-museum-bus',
        'CAIRO BY PLA' => 'exc-cairo-by-plane',
        'CAIRO FLY'    => 'exc-cairo-by-plane',
        'CAIRO'        => 'exc-cairo-by-bus',
        'LUXOR'        => 'exc-luxor-by-plane',
        'MOUSA'        => 'exc-mount-moses-st-catherine',
        'MOUSSA'       => 'exc-mount-moses-st-catherine',
        'CATH'         => 'exc-mount-moses-st-catherine',
        'CATR'         => 'exc-mount-moses-st-catherine',
        'CANYON'       => 'exc-dahab-canyon-bluehole',
        'DAHAB'        => 'exc-dahab-canyon-bluehole',
        'R M BY BUS'   => 'exc-ras-mohammed-bus',
        'R.M'          => 'exc-ras-mohammed-bus',
        'SWIM'         => 'exc-dolphin-swim',
        'DOLPH'        => 'exc-dolphin-show',
        'SUB'          => 'exc-royal-submarine',
        'GLASS'        => 'exc-glass-bottom-boat',
        'HORSE'        => 'exc-horse-riding-beach',
        'HOURS'        => 'exc-horse-riding-beach',
        'BUGGY F'      => 'exc-safari-family-buggy',
        'BAGGY F'      => 'exc-safari-family-buggy',
        'BUGGY'        => 'ops-safari-dune-buggy',
        'BAGGY'        => 'ops-safari-dune-buggy',
        'MOTO 1D'      => 'exc-safari-quad-double',
        '1D MOTO'      => 'exc-safari-quad-double',
        'MOTO'         => 'exc-safari-quad-single',
        'SAFAR'        => 'exc-safari-vip-bedouin-dinner',
        'PARAS'        => 'ops-parasailing-double',
        'PARAC'        => 'ops-parasailing-double',
        'BANAN'        => 'ops-banana-boat',
        'TUBE'         => 'ops-tube-sofa',
        'TUBA'         => 'ops-tube-sofa',
        'ZODIC'        => 'ops-zodiac-youana-1h',
        'SPEED'        => 'ops-zodiac-youana-1h',
    ];
    foreach ($kwMap as $kw => $targetCode) {
        if (str_contains($up, $kw)) {
            $st2 = $pdo->prepare('SELECT * FROM master_trips_catalog WHERE trip_code = ? LIMIT 1');
            $st2->execute([$targetCode]);
            $m = $st2->fetch();
            if ($m) {
                return $m;
            }
        }
    }
    return null;
}

/**
 * Compute exact catalog selling price (EUR & EGP), Adult/Child vendor cost (EGP), and Seller Commission
 * using Sea College's real accounting spreadsheet formula:
 *   - INSHEET / WEBSITE_QR: 10% * Actual_Paid_Gross_EGP
 *   - OUT_SIDE:             25% * MAX(0, Actual_Paid_Gross_EGP - (Adults*AdultCost + Children*ChildCost + Infants*InfantCost))
 */
function sc_erp_compute_trip_financials(?array $trip, int $adults, int $children, int $infants, array $fx, ?float $overrideGrossEgp = null, ?string $overrideMode = null): array
{
    $eurRate = $fx['EUR'] ?? 57.0;
    $adlEur  = $trip ? (float) $trip['adult_sell_eur'] : 30.0;
    $chdEur  = $trip ? (float) $trip['child_sell_eur'] : 15.0;
    $infEur  = $trip ? (float) $trip['infant_sell_eur'] : 0.0;

    $adlCost = $trip ? (float) $trip['adult_cost_egp'] : 0.0;
    $chdCost = $trip ? (float) $trip['child_cost_egp'] : 0.0;
    $infCost = $trip ? (float) $trip['infant_cost_egp'] : 0.0;

    $sellEur = round($adults * $adlEur + $children * $chdEur + $infants * $infEur, 2);
    $sellEgp = $overrideGrossEgp !== null && $overrideGrossEgp >= 0
        ? round($overrideGrossEgp, 2)
        : round($sellEur * $eurRate, 2);

    $supplierCostEgp = round($adults * $adlCost + $children * $chdCost + $infants * $infCost, 2);
    $mode = strtoupper(trim($overrideMode ?: ($trip ? (string) $trip['sale_mode'] : 'INSHEET')));
    if ($mode === 'OUTSIDE') {
        $mode = 'OUT_SIDE';
    }

    if ($mode === 'CENTER') {
        $commRate = 0.0;
        $commBase = 0.0;
        $commEgp  = 0.0;
    } elseif ($mode === 'OUT_SIDE') {
        $commRate = 0.25;
        $commBase = round(max(0.0, $sellEgp - $supplierCostEgp), 2);
        $commEgp  = round($commBase * $commRate, 2);
    } else {
        $commRate = 0.10;
        $commBase = $sellEgp;
        $commEgp  = round($commBase * $commRate, 2);
    }

    return [
        'adult_unit_eur'          => $adlEur,
        'child_unit_eur'          => $chdEur,
        'infant_unit_eur'         => $infEur,
        'catalog_total_eur'       => $sellEur,
        'gross_total_egp'         => $sellEgp,
        'supplier_cost_egp'       => $supplierCostEgp,
        'sale_mode'               => $mode,
        'commission_rate'         => $commRate,
        'commissionable_base_egp' => $commBase,
        'commission_egp'          => $commEgp,
        'supplier_code'           => $trip ? (string) $trip['supplier_code'] : 'SEA_COLLEGE',
        'supplier_name'           => $trip ? (string) $trip['supplier_name'] : 'Sea College Dive Club',
    ];
}

/**
 * Push a real-time in-app notification to a user (`fady.sales`) or role (`ROLE:OPERATIONS_MANAGER`, `ROLE:CHIEF_ACCOUNTANT`, `ROLE:OWNER_ADMIN`).
 */
function sc_erp_push_notification(
    PDO $pdo,
    string $recipient,
    string $notifType,
    string $voucherNo,
    string $titleEn,
    string $titleAr,
    string $bodyEn,
    string $bodyAr
): void {
    try {
        $st = $pdo->prepare(
            'INSERT INTO user_notifications
             (created_at, recipient_username, notif_type, voucher_no, title_en, title_ar, body_en, body_ar, is_read)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)'
        );
        $st->execute([
            gmdate('Y-m-d\TH:i:s\Z'),
            trim($recipient),
            $notifType,
            $voucherNo,
            $titleEn,
            $titleAr,
            $bodyEn,
            $bodyAr,
        ]);
    } catch (Throwable $e) {
        // Non-fatal
    }
}

/**
 * Fetch target settings for a seller (falling back to `__GLOBAL__`).
 */
function sc_erp_get_seller_target(PDO $pdo, string $sellerUsername): array
{
    $st = $pdo->prepare('SELECT * FROM seller_targets_config WHERE LOWER(seller_username) = LOWER(?) LIMIT 1');
    $st->execute([$sellerUsername]);
    $row = $st->fetch();

    $gSt = $pdo->query("SELECT * FROM seller_targets_config WHERE seller_username = '__GLOBAL__' LIMIT 1");
    $glob = $gSt ? ($gSt->fetch() ?: []) : [];

    $isActive = isset($glob['is_target_active']) ? (int) $glob['is_target_active'] : 1;
    if ($row && isset($row['is_target_active'])) {
        // Global switch controls company-wide visibility; when global is active, per-seller row overrides numbers
        $isActive = $isActive === 1 ? (int) $row['is_target_active'] : 0;
    }

    return [
        'is_active'            => $isActive === 1,
        'daily_target_egp'     => (float) ($row['daily_target_egp'] ?? $glob['daily_target_egp'] ?? 15000.0),
        'weekly_target_egp'    => (float) ($row['weekly_target_egp'] ?? $glob['weekly_target_egp'] ?? 95000.0),
        'monthly_target_egp'   => (float) ($row['monthly_target_egp'] ?? $glob['monthly_target_egp'] ?? 380000.0),
        'monthly_trips_target' => (int) ($row['monthly_trips_target'] ?? $glob['monthly_trips_target'] ?? 65),
    ];
}

/**
 * Compute Best Seller Champions across Daily, Weekly, Monthly & Yearly horizons from executed reservations & commissions.
 * Uses latest operational anchor date if today has zero rows so awards & leaderboards always reflect active operations.
 */
function sc_erp_compute_best_seller_awards(PDO $pdo, ?string $anchorDate = null): array
{
    $today = $anchorDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $anchorDate) ? $anchorDate : gmdate('Y-m-d');
    $cntToday = (int) $pdo->query("SELECT COUNT(*) FROM reservations_fact WHERE trip_date = " . $pdo->quote($today) . " AND booking_state = 'EXECUTED' AND seller_type = 'HOTEL_SELLER'")->fetchColumn();
    if ($cntToday === 0) {
        $latest = (string) $pdo->query("SELECT COALESCE(MAX(trip_date), '2026-10-31') FROM reservations_fact WHERE booking_state = 'EXECUTED' AND seller_type = 'HOTEL_SELLER'")->fetchColumn();
        if ($latest !== '') {
            $today = $latest;
        }
    }

    $dtObj     = new DateTimeImmutable($today);
    $weekStart = $dtObj->modify('-6 days')->format('Y-m-d');
    $monthKey  = substr($today, 0, 7);
    $yearKey   = substr($today, 0, 4);

    $queryTop = static function (PDO $db, string $whereSql, array $params): ?array {
        $sql = "SELECT u.username, u.display_name_en, u.display_name_ar, u.rep_scope, u.branch_scope, u.avatar_url,
                       COUNT(r.id) AS trips_count,
                       COALESCE(SUM(r.total_pax), 0) AS total_pax,
                       ROUND(COALESCE(SUM(CASE WHEN r.actual_paid_total_egp > 0 THEN r.actual_paid_total_egp ELSE r.estimated_revenue_egp END), 0), 2) AS actual_sales_egp,
                       ROUND(COALESCE(SUM(r.seller_commission_egp), 0), 2) AS actual_comm_egp
                FROM reservations_fact r
                JOIN users u ON (LOWER(u.username) = LOWER(r.seller_username) OR UPPER(u.rep_scope) = UPPER(r.sales_rep))
                WHERE u.role_code = 'SALES_REP'
                  AND r.booking_state = 'EXECUTED'
                  AND {$whereSql}
                GROUP BY u.username
                ORDER BY actual_sales_egp DESC, trips_count DESC
                LIMIT 1";
        $st = $db->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();
        return $row ?: null;
    };

    return [
        'anchor_date'   => $today,
        'week_from'     => $weekStart,
        'month_key'     => $monthKey,
        'year_key'      => $yearKey,
        'daily_best'    => $queryTop($pdo, 'r.trip_date = ?', [$today]),
        'weekly_best'   => $queryTop($pdo, 'r.trip_date BETWEEN ? AND ?', [$weekStart, $today]),
        'monthly_best'  => $queryTop($pdo, 'r.period_month = ?', [$monthKey]),
        'yearly_best'   => $queryTop($pdo, 'r.period_year = ?', [$yearKey]),
    ];
}
