<?php
/**
 * Sea College Dive Club — 1-Click Executive & Row-Level Sheet Exporter (`/handlers/erp_export.php`)
 * Streams genuine multi-tab `.xlsx` Excel workbooks and UTF-8 BOM `.csv` sheets
 * across Daily, Weekly, Monthly, Quarterly, and Yearly horizons, strictly enforcing RBAC.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/erp_db.php';

sc_ensure_session();

$pdo = sc_erp_pdo();
if (!$pdo) {
    http_response_code(503);
    exit('Database unavailable');
}

$uname = (string) ($_SESSION['sc_erp_user'] ?? '');
if ($uname === '') {
    http_response_code(401);
    exit('Please log in to the Sea College ERP app before exporting sheets.');
}
$uSt = $pdo->prepare('SELECT * FROM users WHERE username = ? AND is_active = 1');
$uSt->execute([$uname]);
$user = $uSt->fetch();
if (!$user) {
    http_response_code(401);
    exit('Invalid session.');
}
$pSt = $pdo->prepare('SELECT perm_code FROM role_permissions WHERE role_code = ?');
$pSt->execute([$user['role_code']]);
$perms = array_column($pSt->fetchAll(), 'perm_code');

$hasPerm = static fn(string ...$any): bool => count(array_intersect($any, $perms)) > 0;

$report  = strtolower(trim((string) ($_GET['report'] ?? 'pnl')));
$format  = strtolower(trim((string) ($_GET['format'] ?? 'xlsx'))) === 'csv' ? 'csv' : 'xlsx';
$horizon = strtolower(trim((string) ($_GET['horizon'] ?? 'monthly')));
$year    = preg_replace('/[^0-9]/', '', (string) ($_GET['year'] ?? ''));
$month   = preg_replace('/[^0-9\-]/', '', (string) ($_GET['month'] ?? ''));

$whereArr = [];
$args     = [];
if ($month !== '') {
    $whereArr[] = 'period_month = ?';
    $args[]     = $month;
} elseif ($year !== '') {
    $whereArr[] = 'period_year = ?';
    $args[]     = $year;
}
$whereSql = $whereArr ? (' WHERE ' . implode(' AND ', $whereArr)) : '';
$stamp    = $month ?: ($year ?: '2024_2026_All');

sc_erp_audit($pdo, (string) $user['username'], (string) $user['role_code'], 'EXPORT_SHEET', strtoupper($report), $format, ['horizon' => $horizon, 'scope' => $stamp]);

/* ==========================================================================
   1. PROFIT & LOSS STATEMENT (Summary by Horizon + Full Row-Level P&L Lines)
   ========================================================================== */
if ($report === 'pnl') {
    if (!$hasPerm('bi.export_pnl_cashflow', 'gl.view_all_ledgers')) {
        http_response_code(403);
        exit('Role not authorized to export Company Profit & Loss.');
    }
    $grpCol = match ($horizon) {
        'quarterly' => 'period_quarter',
        'yearly'    => 'period_year',
        default     => 'period_month',
    };
    $sumSt = $pdo->prepare(
        "SELECT {$grpCol} AS period,
                ROUND(SUM(CASE WHEN branch_code='CYRENE' AND section_type='TOTAL_REV' THEN amount_egp ELSE 0 END), 2) AS cyrene_rev_egp,
                ROUND(SUM(CASE WHEN branch_code='CYRENE' AND section_type='TOTAL_EXP' THEN amount_egp ELSE 0 END), 2) AS cyrene_exp_egp,
                ROUND(SUM(CASE WHEN branch_code='CYRENE' AND section_type='NET_PROFIT' THEN amount_egp ELSE 0 END), 2) AS cyrene_net_egp,
                ROUND(SUM(CASE WHEN branch_code='JAZ' AND section_type='NET_PROFIT' THEN amount_egp ELSE 0 END), 2) AS jaz_net_egp,
                ROUND(SUM(CASE WHEN branch_code='JETTY' AND section_type='TOTAL_REV' THEN amount_egp ELSE 0 END), 2) AS jetty_rev_egp,
                ROUND(SUM(CASE WHEN branch_code='JETTY' AND section_type='TOTAL_EXP' THEN amount_egp ELSE 0 END), 2) AS jetty_exp_egp,
                ROUND(SUM(CASE WHEN branch_code='JETTY' AND section_type='NET_PROFIT' THEN amount_egp ELSE 0 END), 2) AS jetty_net_egp,
                ROUND(SUM(CASE WHEN section_type='TOTAL_REV' THEN amount_egp ELSE 0 END), 2) AS total_rev_egp,
                ROUND(SUM(CASE WHEN section_type='TOTAL_EXP' THEN amount_egp ELSE 0 END), 2) AS total_exp_egp,
                ROUND(SUM(CASE WHEN section_type='NET_PROFIT' THEN amount_egp ELSE 0 END), 2) AS combined_net_profit_egp
         FROM pnl_monthly_lines {$whereSql}
         GROUP BY {$grpCol} ORDER BY {$grpCol} ASC"
    );
    $sumSt->execute($args);
    $sumRows = array_map('array_values', $sumSt->fetchAll(PDO::FETCH_NUM));

    $rawSt = $pdo->prepare(
        "SELECT period_year, period_quarter, period_month, branch_code, section_type, line_name_ar, line_name_en, trip_or_cost_group, amount_egp, COALESCE(cost_ratio,''), source_file
         FROM pnl_monthly_lines {$whereSql}
         ORDER BY period_month ASC, branch_code ASC, id ASC"
    );
    $rawSt->execute($args);
    $rawRows = $rawSt->fetchAll(PDO::FETCH_NUM);

    $sumHeaders = ['Period', 'Cyrene Revenue (EGP)', 'Cyrene Expenses (EGP)', 'Cyrene Net Profit (EGP)', 'Jaz Net (EGP)', 'Jetty Revenue (EGP)', 'Jetty Expenses (EGP)', 'Jetty Net Profit (EGP)', 'Total Revenue (EGP)', 'Total Expenses (EGP)', 'Net Operating Profit (EGP)'];
    $rawHeaders = ['Year', 'Quarter', 'Month', 'Branch', 'Section', 'Arabic Line Item', 'English Category', 'Group Code', 'Amount (EGP)', 'Direct Cost Ratio', 'Source Workbook'];

    if ($format === 'csv') {
        sc_erp_stream_csv("Sea_College_PnL_RowLevel_{$stamp}.csv", $rawHeaders, $rawRows);
    }
    sc_erp_stream_xlsx("Sea_College_Profit_and_Loss_{$stamp}.xlsx", [
        'PnL_Period_Summary' => ['headers' => $sumHeaders, 'rows' => $sumRows],
        'PnL_Full_Row_Lines' => ['headers' => $rawHeaders, 'rows' => $rawRows],
    ]);
}

/* ==========================================================================
   2. MULTI-CURRENCY CASH FLOW & EXPENSE VOUCHERS
   ========================================================================== */
if ($report === 'cashflow') {
    if (!$hasPerm('bi.export_pnl_cashflow', 'gl.view_all_ledgers')) {
        http_response_code(403);
        exit('Role not authorized to export Cash Flow.');
    }
    $cfCol = match ($horizon) {
        'daily'     => 'tx_date',
        'weekly'    => 'period_week',
        'quarterly' => 'period_quarter',
        'yearly'    => 'period_year',
        default     => 'period_month',
    };
    $cfSt = $pdo->prepare(
        "SELECT {$cfCol} AS period,
                ROUND(SUM(inc_le),2), ROUND(SUM(inc_usd),2), ROUND(SUM(inc_eur),2), ROUND(SUM(inc_gbp),2), ROUND(SUM(inc_visa),2),
                ROUND(SUM(exp_le),2), ROUND(SUM(exp_usd),2), ROUND(SUM(exp_eur),2), ROUND(SUM(exp_gbp),2),
                ROUND(SUM(net_flow_egp_equiv),2)
         FROM cashbox_daily_fact {$whereSql}
         GROUP BY {$cfCol} ORDER BY {$cfCol} ASC"
    );
    $cfSt->execute($args);
    $cfSummary = $cfSt->fetchAll(PDO::FETCH_NUM);

    $rawCf = $pdo->prepare(
        "SELECT tx_date, period_week, period_month, period_quarter, inc_le, inc_usd, inc_eur, inc_gbp, inc_visa, exp_le, exp_usd, exp_eur, exp_gbp, bal_le, bal_usd, bal_eur, bal_gbp, net_flow_egp_equiv, source_file
         FROM cashbox_daily_fact {$whereSql} ORDER BY tx_date ASC"
    );
    $rawCf->execute($args);
    $rawCfRows = $rawCf->fetchAll(PDO::FETCH_NUM);

    $expSt = $pdo->prepare(
        "SELECT exp_date, period_month, voucher_seq, description, cost_center, amount_le, amount_usd, amount_eur, amount_gbp, total_egp, source_file
         FROM expense_vouchers_fact {$whereSql} ORDER BY exp_date DESC LIMIT 6000"
    );
    $expSt->execute($args);
    $expRows = $expSt->fetchAll(PDO::FETCH_NUM);

    $cfHeaders  = ['Period', 'Inflow EGP', 'Inflow USD', 'Inflow EUR', 'Inflow GBP', 'Inflow VISA', 'Outflow EGP', 'Outflow USD', 'Outflow EUR', 'Outflow GBP', 'Net Cash Flow (EGP Equiv)'];
    $rawHeaders = ['Date', 'ISO Week', 'Month', 'Quarter', 'Income LE', 'Income $', 'Income €', 'Income £', 'VISA Batch', 'Expense LE', 'Expense $', 'Expense €', 'Expense £', 'Balance LE', 'Balance $', 'Balance €', 'Balance £', 'Net Flow EGP Equiv', 'Source File'];
    $expHeaders = ['Date', 'Month', 'Voucher #', 'Arabic Description', 'Cost Center', 'Paid LE', 'Paid USD', 'Paid EUR', 'Paid GBP', 'Total EGP', 'Source File'];

    if ($format === 'csv') {
        sc_erp_stream_csv("Sea_College_CashFlow_Daily_{$stamp}.csv", $rawHeaders, $rawCfRows);
    }
    sc_erp_stream_xlsx("Sea_College_Cash_Flow_and_Expenses_{$stamp}.xlsx", [
        'CashFlow_Summary'       => ['headers' => $cfHeaders, 'rows' => $cfSummary],
        'Daily_Cashbox_RowLevel' => ['headers' => $rawHeaders, 'rows' => $rawCfRows],
        'Expense_Vouchers_5692'  => ['headers' => $expHeaders, 'rows' => $expRows],
    ]);
}

/* ==========================================================================
   3. BEST SELLERS OF THE MONTH / PERIOD + NATIONALITIES & HOTELS
   ========================================================================== */
if ($report === 'best_sellers') {
    if (!$hasPerm('bi.export_bestsellers_profitability')) {
        http_response_code(403);
        exit('Role not authorized.');
    }
    $repSt = $pdo->prepare(
        "SELECT CASE WHEN sales_rep != '' THEN sales_rep ELSE 'DIRECT / DESK' END AS seller,
                COUNT(*) AS vouchers, SUM(adults) AS adults, SUM(children) AS children, SUM(total_pax) AS total_pax,
                ROUND(SUM(rest_egp_equiv),2) AS rest_collected_egp,
                ROUND(SUM(estimated_revenue_egp),2) AS est_revenue_egp,
                ROUND(SUM(estimated_profit_egp),2) AS est_net_margin_egp
         FROM reservations_fact {$whereSql}
         GROUP BY seller ORDER BY total_pax DESC"
    );
    $repSt->execute($args);
    $repRows = $repSt->fetchAll(PDO::FETCH_NUM);

    $natSt = $pdo->prepare(
        "SELECT nationality, COUNT(*) AS vouchers, SUM(total_pax) AS total_pax,
                ROUND(SUM(estimated_revenue_egp),2) AS est_rev_egp
         FROM reservations_fact {$whereSql}
         GROUP BY nationality ORDER BY total_pax DESC"
    );
    $natSt->execute($args);
    $natRows = $natSt->fetchAll(PDO::FETCH_NUM);

    $htlSt = $pdo->prepare(
        "SELECT hotel_name, COUNT(*) AS vouchers, SUM(total_pax) AS total_pax,
                ROUND(SUM(estimated_revenue_egp),2) AS est_rev_egp
         FROM reservations_fact " . ($whereSql !== '' ? $whereSql . " AND hotel_name != ''" : " WHERE hotel_name != ''") . "
         GROUP BY hotel_name ORDER BY total_pax DESC LIMIT 100"
    );
    $htlSt->execute($args);
    $htlRows = $htlSt->fetchAll(PDO::FETCH_NUM);

    $repHeaders = ['Sales Rep / Agency', 'Vouchers', 'Adults', 'Children', 'Total PAX', 'REST Collected (EGP)', 'Est. Revenue (EGP)', 'Est. Net Margin (EGP)'];
    if ($format === 'csv') {
        sc_erp_stream_csv("Sea_College_Best_Sellers_{$stamp}.csv", $repHeaders, $repRows);
    }
    sc_erp_stream_xlsx("Sea_College_Best_Sellers_Report_{$stamp}.xlsx", [
        'Best_Sellers_Reps_Agencies' => ['headers' => $repHeaders, 'rows' => $repRows],
        'Top_Guest_Nationalities'    => ['headers' => ['Nationality', 'Vouchers', 'Total PAX', 'Est. Revenue (EGP)'], 'rows' => $natRows],
        'Top_100_Hotels_PAX'         => ['headers' => ['Hotel Name', 'Vouchers', 'Total PAX', 'Est. Revenue (EGP)'], 'rows' => $htlRows],
    ]);
}

/* ==========================================================================
   4. MOST PROFITABLE TRIPS, TOURS & PADI COURSES
   ========================================================================== */
if ($report === 'profitable_trips') {
    if (!$hasPerm('bi.export_bestsellers_profitability')) {
        http_response_code(403);
        exit('Role not authorized.');
    }
    $trSt = $pdo->prepare(
        "SELECT trip_or_cost_group, MAX(line_name_en), MAX(line_name_ar),
                ROUND(SUM(amount_egp), 2) AS rev_egp,
                ROUND(AVG(COALESCE(cost_ratio, 0.40)), 3) AS cost_rat
         FROM pnl_monthly_lines
         WHERE section_type IN ('CASH_REV', 'ACCRUED_REV') " . ($whereArr ? ' AND ' . implode(' AND ', $whereArr) : '') . "
         GROUP BY trip_or_cost_group ORDER BY rev_egp DESC"
    );
    $trSt->execute($args);
    $pnlTrips = [];
    foreach ($trSt->fetchAll(PDO::FETCH_NUM) as $r) {
        $rev  = (float) $r[3];
        $cRat = $r[0] === 'EXTRA_INTRO_UPSELL' ? 0.14 : ($r[0] === 'PADI_COURSES' ? 0.30 : (float) $r[4]);
        $cogs = round($rev * $cRat, 2);
        $net  = round($rev - $cogs, 2);
        $pct  = $rev > 0 ? round(($net / $rev) * 100, 1) : 0.0;
        $pnlTrips[] = [$r[0], $r[1], $r[2], $rev, $cogs, $net, $pct];
    }
    usort($pnlTrips, static fn($a, $b) => $b[5] <=> $a[5]);

    $resCatSt = $pdo->prepare(
        "SELECT product_category, COUNT(*) AS vouchers, SUM(total_pax) AS pax,
                SUM(snork_pax), SUM(intro1_pax), SUM(intro2_pax), SUM(dives_pax),
                ROUND(SUM(estimated_revenue_egp),2), ROUND(SUM(estimated_cogs_egp),2), ROUND(SUM(estimated_profit_egp),2)
         FROM reservations_fact {$whereSql}
         GROUP BY product_category ORDER BY SUM(estimated_profit_egp) DESC"
    );
    $resCatSt->execute($args);
    $resCatRows = $resCatSt->fetchAll(PDO::FETCH_NUM);

    $headers = ['Activity Group Code', 'Activity (EN)', 'Activity (AR)', 'Actual Revenue (EGP)', 'Direct COGS (EGP)', 'Net Profit Contribution (EGP)', 'Gross Margin %'];
    if ($format === 'csv') {
        sc_erp_stream_csv("Sea_College_Most_Profitable_Trips_{$stamp}.csv", $headers, $pnlTrips);
    }
    sc_erp_stream_xlsx("Sea_College_Most_Profitable_Trips_and_Tours_{$stamp}.xlsx", [
        'PnL_Trip_Profitability'       => ['headers' => $headers, 'rows' => $pnlTrips],
        'Reservations_Activity_Volume' => ['headers' => ['Product Category', 'Vouchers', 'Total PAX', 'Snorkelers', '1-Intro', '2-Intro', 'Certified Dives', 'Est. Revenue (EGP)', 'Est. Direct COGS (EGP)', 'Est. Gross Margin (EGP)'], 'rows' => $resCatRows],
    ]);
}

/* ==========================================================================
   5. MOST PROFITABLE BOATS, SPEEDBOATS, DRYDOCK & ASSETS (Scoped for Partners!)
   ========================================================================== */
if ($report === 'profitable_boats') {
    if (!$hasPerm('bi.export_bestsellers_profitability', 'bi.view_partner_fleet')) {
        http_response_code(403);
        exit('Role not authorized.');
    }
    $vScope = (string) $user['vessel_scope'];
    $vFilterSql = '';
    $vFilterArgs = [];
    if ($vScope !== '' && $vScope !== 'ALL' && !$hasPerm('bi.export_bestsellers_profitability')) {
        $codes = array_values(array_filter(array_map('trim', explode(',', $vScope))));
        $vFilterSql = ' WHERE vessel_code IN (' . implode(',', array_fill(0, count($codes), '?')) . ')';
        $vFilterArgs = $codes;
    }

    $bSum = $pdo->query(
        "SELECT v.code, v.name_en, v.name_ar, v.vessel_type, v.ownership_structure, v.monthly_fixed_rent_egp,
                COUNT(t.id) AS sea_days, COALESCE(SUM(t.total_pax),0) AS pax,
                ROUND(COALESCE(SUM(t.launch_fee_egp),0),2), ROUND(COALESCE(SUM(t.food_catering_egp),0),2),
                ROUND(COALESCE(SUM(t.supermarket_egp),0),2), ROUND(COALESCE(SUM(t.total_direct_cost_egp),0),2),
                ROUND(COALESCE(SUM(t.estimated_trip_rev_egp),0),2), ROUND(COALESCE(SUM(t.estimated_trip_net_egp),0),2),
                v.drydock_capex_total_egp, v.drydock_sea_college_share_egp, v.drydock_partner_share_egp
         FROM vessels v LEFT JOIN vessel_trips_fact t ON t.vessel_code = v.code
         GROUP BY v.code ORDER BY COALESCE(SUM(t.estimated_trip_net_egp),0) DESC"
    )->fetchAll(PDO::FETCH_NUM);

    $tRawSt = $pdo->prepare("SELECT trip_date, period_month, vessel_code, vessel_name_ar, adults, children, infants, total_pax, launch_fee_egp, food_catering_egp, supermarket_egp, total_direct_cost_egp, estimated_trip_rev_egp, estimated_trip_net_egp, staff_crew, notes, source_file FROM vessel_trips_fact {$vFilterSql} ORDER BY trip_date DESC");
    $tRawSt->execute($vFilterArgs);
    $tRawRows = $tRawSt->fetchAll(PDO::FETCH_NUM);

    $azSt = $pdo->prepare("SELECT tx_date, period_year, vessel_code, item_description, total_egp, sea_college_share_egp, partner_share_egp, source_file FROM drydock_capex_lines {$vFilterSql} ORDER BY vessel_code, id ASC");
    $azSt->execute($vFilterArgs);
    $azRows = $azSt->fetchAll(PDO::FETCH_NUM);

    $astRows = $pdo->query('SELECT sc_tag, location_branch, item_category, qty, brand_description, serial_or_size, status FROM equipment_assets_master ORDER BY id ASC')->fetchAll(PDO::FETCH_NUM);

    $sumHeaders = ['Asset Code', 'Asset Name (EN)', 'Asset Name (AR)', 'Type', 'Partner / Lease Structure', 'Monthly Lease (EGP)', 'Operating Sea Days', 'Total PAX', 'Launch Fees (EGP)', 'Catering (EGP)', 'Supermarket (EGP)', 'Total Direct Cost (EGP)', 'Attributed Revenue (EGP)', 'Net Operating Contribution (EGP)', '2026 Drydock Total (EGP)', 'Sea College Drydock Share', 'Partner Drydock Receivable'];
    if ($format === 'csv') {
        sc_erp_stream_csv("Sea_College_Fleet_and_Assets_ROI_{$stamp}.csv", $sumHeaders, $bSum);
    }
    sc_erp_stream_xlsx("Sea_College_Boats_Drydock_and_Assets_{$stamp}.xlsx", [
        'Fleet_Profitability_Summary' => ['headers' => $sumHeaders, 'rows' => $bSum],
        'Daily_Yacht_Trips_RowLevel'  => ['headers' => ['Date', 'Month', 'Vessel', 'Arabic Name', 'Adults', 'Children', 'Infants', 'Total PAX', 'Launch Fee', 'Food', 'Supermarket', 'Direct Cost (EGP)', 'Est. Revenue (EGP)', 'Est. Net Margin (EGP)', 'Crew', 'Notes', 'Source'], 'rows' => $tRawRows],
        'Drydock_Azaq_CapEx_RowLevel' => ['headers' => ['Date', 'Year', 'Vessel', 'Overhaul Item Description', 'Total Expense (EGP)', 'Sea College Share (EGP)', 'Partner Share AR (EGP)', 'Source'], 'rows' => $azRows],
        'Dive_Gear_Fixed_Assets_119'  => ['headers' => ['S.C Tag #', 'Location', 'Item Category', 'Qty', 'Brand / Model', 'Size / Serial', 'Status'], 'rows' => $astRows],
    ]);
}

/* ==========================================================================
   6. FULL ROW-LEVEL RESERVATIONS, AR/AP SUBLEDGERS & HR PAYROLL
   ========================================================================== */
if ($report === 'full_data_pack') {
    if (!$hasPerm('gl.view_all_ledgers', 'bi.export_pnl_cashflow')) {
        http_response_code(403);
        exit('Role not authorized.');
    }
    $resSt = $pdo->prepare("SELECT voucher_no, trip_date, period_month, section_banner, booking_state, branch_code, hotel_name, room_no, nationality, pickup_time, trip_name, product_category, adults, children, infants, total_pax, cancelled_pax, sales_rep, executor, vessel_code, ar_client_code, ap_supplier_code, desk_paid_egp, rest_egp_equiv, rest_visa_egp, estimated_revenue_egp, estimated_cogs_egp, estimated_profit_egp, accounting_status, journal_entry_no, source_file FROM reservations_fact {$whereSql} ORDER BY trip_date DESC LIMIT 16000");
    $resSt->execute($args);
    $resRows = $resSt->fetchAll(PDO::FETCH_NUM);

    $cpRows  = $pdo->query('SELECT code, name_ar, party_type, total_debit_egp, total_credit_egp, current_balance_egp, source_workbook FROM counterparties ORDER BY party_type, ABS(current_balance_egp) DESC')->fetchAll(PDO::FETCH_NUM);
    $payRows = $pdo->query('SELECT period_month, branch_code, emp_name_ar, job_title_ar, base_salary_egp, worked_days, actual_wage_egp, commission_egp, allowances_egp, bonuses_egp, net_pay_egp, source_file FROM payroll_monthly_fact ORDER BY period_month DESC, net_pay_egp DESC')->fetchAll(PDO::FETCH_NUM);

    $resHeaders = ['Voucher No', 'Trip Date', 'Month', 'Section', 'Booking State', 'Branch', 'Hotel', 'Room', 'Nationality', 'Pickup', 'Trip', 'Category', 'Adults', 'Children', 'Infants', 'Executed PAX', 'Cancelled PAX', 'Seller', 'Executor', 'Vessel Code', 'AR Client Code', 'AP Supplier Code', 'Desk Paid EGP', 'Jetty REST EGP', 'VISA EGP', 'Total Revenue EGP', 'Direct COGS EGP', 'Net Margin EGP', 'Accounting Link Status', 'GL Journal Entry #', 'Source Workbook'];
    if ($format === 'csv') {
        sc_erp_stream_csv("Sea_College_Reservations_Accounting_RowLevel_{$stamp}.csv", $resHeaders, $resRows);
    }
    sc_erp_stream_xlsx("Sea_College_Master_Reservations_AR_AP_Payroll_{$stamp}.xlsx", [
        'Reservations_GL_RowLevel' => ['headers' => $resHeaders, 'rows' => $resRows],
        'AR_AP_Counterparties'     => ['headers' => ['Account Code', 'Party Name', 'Type', 'Total Debit (EGP)', 'Total Credit (EGP)', 'Closing Balance (EGP)', 'Source Workbook'], 'rows' => $cpRows],
        'HR_Monthly_Payroll'       => ['headers' => ['Month', 'Branch', 'Employee Name', 'Job Title', 'Base Salary', 'Worked Days', 'Actual Wage', 'Commission', 'Allowances', 'Bonuses', 'Net Cash Pay (EGP)', 'Source Workbook'], 'rows' => $payRows],
    ]);
}

/* ==========================================================================
   7. MAY–NOV 2026 RESERVATIONS-TO-ACCOUNTING & DOUBLE-ENTRY GL AUDIT PACK
   ========================================================================== */
if ($report === 'res_accounting_2026') {
    if (!$hasPerm('gl.view_all_ledgers', 'bi.export_pnl_cashflow')) {
        http_response_code(403);
        exit('Role not authorized.');
    }
    $audRows = $pdo->query(
        'SELECT period_month, source_workbook, days_covered, executed_vouchers, cancelled_vouchers,
                executed_pax, cancelled_pax, cyrene_pax, dreams_fayrouz_pax, outside_b2b_pax, discovery_pax,
                cash_paid_at_desk_egp, jetty_rest_cash_egp, pos_visa_egp, ar_b2b_credit_egp,
                total_accrued_rev_egp, direct_trip_cogs_egp, ap_supplier_payable_egp, gross_operating_margin_egp,
                matched_daily_sales_vouchers, gl_journal_entries_count, accounting_bridge_mode
         FROM reservation_accounting_audit ORDER BY period_month ASC'
    )->fetchAll(PDO::FETCH_NUM);

    $r26Rows = $pdo->query(
        "SELECT voucher_no, trip_date, period_month, section_banner, booking_state, branch_code, hotel_name, room_no,
                nationality, trip_name, product_category, adults, children, total_pax, cancelled_pax,
                snork_pax, intro1_pax, intro2_pax, dives_pax, sales_rep, executor, vessel_code,
                ar_client_code, ap_supplier_code, desk_paid_egp, rest_egp_equiv, rest_visa_egp,
                estimated_revenue_egp, estimated_cogs_egp, estimated_profit_egp, accounting_status, journal_entry_no
         FROM reservations_fact
         WHERE period_month BETWEEN '2026-05' AND '2026-11'
         ORDER BY trip_date ASC, id ASC"
    )->fetchAll(PDO::FETCH_NUM);

    $glRows = $pdo->query(
        'SELECT je.entry_no, je.entry_date, jl.account_code, c.name_en, c.name_ar, jl.debit_egp, jl.credit_egp, je.description_en
         FROM journal_entries je
         JOIN journal_lines jl ON jl.journal_id = je.id
         LEFT JOIN chart_of_accounts c ON c.code = jl.account_code
         ORDER BY je.entry_date ASC, jl.id ASC'
    )->fetchAll(PDO::FETCH_NUM);

    $crewRows = $pdo->query('SELECT crew_role, name_ar, cdws_or_id_masked, vessel_assigned, source_month FROM marine_crew_roster ORDER BY crew_role, name_ar')->fetchAll(PDO::FETCH_NUM);

    $audHeaders = ['Month', 'Workbook', 'Days', 'Executed Vouchers', 'Cancelled Vouchers', 'Executed PAX', 'Cancelled PAX', 'Cyrene PAX', 'Dreams & Fayrouz PAX', 'Out Side B2B PAX', 'Discovery PAX', 'Desk Cash (EGP)', 'Jetty REST Cash (EGP)', 'POS VISA (EGP)', 'AR B2B Credit (EGP)', 'Total Accrued Revenue (EGP)', 'Direct Trip COGS (EGP)', 'AP Supplier Payable (EGP)', 'Gross Operating Margin (EGP)', 'Matched Daily Sales Vouchers', 'GL Journals Posted', 'Accounting Mode'];
    if ($format === 'csv') {
        sc_erp_stream_csv('Sea_College_May_Nov_2026_Accounting_Audit.csv', $audHeaders, $audRows);
    }
    sc_erp_stream_xlsx('Sea_College_May_to_Nov_2026_Reservations_Accounting_and_GL.xlsx', [
        'May_Nov_2026_Audit_Summary' => ['headers' => $audHeaders, 'rows' => $audRows],
        'May_Nov_2026_All_7290_Rows' => ['headers' => ['Voucher', 'Date', 'Month', 'Section', 'State', 'Branch', 'Hotel', 'Room', 'Nat', 'Trip', 'Category', 'Adults', 'Children', 'Executed PAX', 'Cancelled PAX', 'Snk', '1-Intro', '2-Intro', 'Diver', 'Seller', 'Executor', 'Vessel', 'AR Client', 'AP Supplier', 'Desk Cash EGP', 'Jetty REST EGP', 'VISA EGP', 'Revenue EGP', 'Direct COGS EGP', 'Net Margin EGP', 'Accounting Status', 'GL Entry #'], 'rows' => $r26Rows],
        'Double_Entry_GL_Lines'      => ['headers' => ['Journal Entry #', 'Date', 'Account Code', 'Account Name (EN)', 'Account Name (AR)', 'Debit (EGP)', 'Credit (EGP)', 'Description'], 'rows' => $glRows],
        'Marine_Crew_Roster_117'     => ['headers' => ['Crew Role', 'Arabic Name', 'CDWS / Masked ID', 'Assigned Vessel', 'Latest Month'], 'rows' => $crewRows],
    ]);
}

/* ==========================================================================
   8. STAFF TRIAL FEEDBACK & PILOT ACTIVITY LOG EXPORT
   ========================================================================== */
if ($report === 'trial_feedback') {
    if (!$hasPerm('bi.view_executive', 'gl.view_all_ledgers')) {
        http_response_code(403);
        exit('Role not authorized.');
    }
    $fbRows = $pdo->query('SELECT id, created_at, username, display_name, role_code, module_tab, feedback_type, priority, status, message, admin_reply, device_info FROM trial_feedback ORDER BY id DESC')->fetchAll(PDO::FETCH_NUM);
    $auRows = $pdo->query('SELECT id, created_at, username, role_code, action_type, entity_type, entity_id, details_json FROM audit_logs ORDER BY id DESC LIMIT 500')->fetchAll(PDO::FETCH_NUM);
    $fbHeaders = ['ID', 'Timestamp', 'Username', 'Employee Name', 'Role', 'Module Tab', 'Feedback Type', 'Priority', 'Status', 'Feedback / Issue Description', 'Admin / AI Team Reply', 'Device'];
    if ($format === 'csv') {
        sc_erp_stream_csv('Sea_College_Trial_Pilot_Feedback.csv', $fbHeaders, $fbRows);
    }
    sc_erp_stream_xlsx('Sea_College_Trial_Pilot_Feedback_and_Audit_Log.xlsx', [
        'Staff_Trial_Feedback' => ['headers' => $fbHeaders, 'rows' => $fbRows],
        'Pilot_Audit_Trail'    => ['headers' => ['ID', 'UTC Time', 'User', 'Role', 'Action', 'Entity', 'Ref ID', 'Details JSON'], 'rows' => $auRows],
    ]);
}

http_response_code(400);
exit('Unknown report type.');
