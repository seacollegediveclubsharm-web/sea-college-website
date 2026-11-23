<?php
/**
 * Sea College Dive Club — 1-Click Executive, Monthly Closing Pack & Seller Sheet Exporter (`/handlers/erp_export.php`)
 * Streams genuine multi-tab `.xlsx` Excel workbooks and UTF-8 BOM `.csv` sheets
 * across Daily, Weekly, Monthly, Quarterly, and Yearly horizons, strictly enforcing RBAC,
 * horizontal seller isolation, partner vessel isolation, bank/NID masking, and formula-injection safety.
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
$rawMo   = trim((string) ($_GET['month'] ?? ''));
$month   = preg_match('/^\d{4}-\d{2}$/', $rawMo) ? $rawMo : '';

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
   0A. MONTHLY MANAGEMENT REPORTS PACK (`monthly_pack` — 13-Sheet Closing Pack)
   ========================================================================== */
if ($report === 'monthly_pack') {
    if (!$hasPerm('bi.export_pnl_cashflow', 'gl.view_all_ledgers')) {
        http_response_code(403);
        exit('Role not authorized to export Monthly Management Reports Pack.');
    }
    $targetMonth = $month !== '' ? $month : '2026-10';
    $targetYear  = substr($targetMonth, 0, 4);

    // 1. Daily Cash Revenue
    $dcrSt = $pdo->prepare(
        'SELECT rev_date, fx_usd, fx_eur, fx_gbp,
                cyrene_le, cyrene_usd, cyrene_eur, cyrene_gbp, cyrene_visa, cyrene_total_egp,
                jetty_le, jetty_usd, jetty_eur, jetty_gbp, jetty_visa, jetty_total_egp, combined_total_egp
         FROM daily_revenue_fact WHERE period_month = ? ORDER BY rev_date ASC'
    );
    $dcrSt->execute([$targetMonth]);
    $dcrRows = $dcrSt->fetchAll(PDO::FETCH_NUM);
    $cyrCash = 0.0;
    $jetCash = 0.0;
    $combCash = 0.0;
    $combVisa = 0.0;
    foreach ($dcrRows as $r) {
        $cyrCash  += (float) $r[9];
        $jetCash  += (float) $r[15];
        $combCash += (float) $r[16];
        $combVisa += (float) $r[8] + (float) $r[14];
    }
    $dcrRows[] = ['TOTAL', 50, 57, 65, '', '', '', '', '', round($cyrCash, 2), '', '', '', '', round($combVisa, 2), round($jetCash, 2), round($combCash, 2)];

    // 2. Seller Commissions
    $scSt = $pdo->prepare(
        "SELECT branch_code, rep_scope, seller_username, commission_type,
                COUNT(*) AS entries,
                ROUND(SUM(gross_amount_egp), 2) AS gross_sales_egp,
                ROUND(SUM(supplier_cost_egp), 2) AS supplier_cost_egp,
                ROUND(SUM(commissionable_base_egp), 2) AS base_egp,
                ROUND(SUM(commission_egp), 2) AS commission_egp,
                ROUND(SUM(commission_egp), 0) AS commission_rounded_egp
         FROM seller_commissions_ledger
         WHERE period_month = ? AND status != 'REVERSED_CANCELLED'
         GROUP BY branch_code, rep_scope, seller_username, commission_type
         ORDER BY branch_code ASC, commission_egp DESC"
    );
    $scSt->execute([$targetMonth]);
    $scRows = $scSt->fetchAll(PDO::FETCH_NUM);
    $cyrScTot = $pdo->prepare("SELECT ROUND(COALESCE(SUM(gross_amount_egp),0),2), ROUND(COALESCE(SUM(commission_egp),0),0) FROM seller_commissions_ledger WHERE period_month = ? AND branch_code = 'CYRENE' AND status != 'REVERSED_CANCELLED' AND source_channel NOT LIKE 'LIVE_%'");
    $cyrScTot->execute([$targetMonth]);
    [$cyrSalesTot, $cyrComTot] = $cyrScTot->fetch(PDO::FETCH_NUM) ?: [0.0, 0.0];
    $scRows[] = ['CYRENE_TOTAL', 'TOTAL CYRENE DESK', '', 'ALL', '', (float) $cyrSalesTot, '', '', (float) $cyrComTot, (float) $cyrComTot];

    // 3, 4, 5. Branch & Consolidated P&L
    $pnlCSt = $pdo->prepare("SELECT section_type, line_name_ar, line_name_en, trip_or_cost_group, amount_egp, COALESCE(cost_pct_of_rev,''), is_interbranch FROM pnl_monthly_lines WHERE period_month = ? AND branch_code = 'CYRENE' ORDER BY id ASC");
    $pnlCSt->execute([$targetMonth]);
    $pnlCyreneRows = $pnlCSt->fetchAll(PDO::FETCH_NUM);

    $pnlJSt = $pdo->prepare("SELECT section_type, line_name_ar, line_name_en, trip_or_cost_group, amount_egp, COALESCE(cost_pct_of_rev,''), is_interbranch FROM pnl_monthly_lines WHERE period_month = ? AND branch_code = 'JETTY' ORDER BY id ASC");
    $pnlJSt->execute([$targetMonth]);
    $pnlJettyRows = $pnlJSt->fetchAll(PDO::FETCH_NUM);

    $sumSec = static function (PDO $db, string $mo, string $br, string $sec): float {
        $st = $db->prepare('SELECT ROUND(COALESCE(SUM(amount_egp),0),2) FROM pnl_monthly_lines WHERE period_month = ? AND branch_code = ? AND section_type = ?');
        $st->execute([$mo, $br, $sec]);
        return (float) $st->fetchColumn();
    };
    $cyrRev = $sumSec($pdo, $targetMonth, 'CYRENE', 'TOTAL_REV');
    $cyrExp = $sumSec($pdo, $targetMonth, 'CYRENE', 'TOTAL_EXP');
    $cyrNet = $sumSec($pdo, $targetMonth, 'CYRENE', 'NET_PROFIT');
    $jetRev = $sumSec($pdo, $targetMonth, 'JETTY', 'TOTAL_REV');
    $jetExp = $sumSec($pdo, $targetMonth, 'JETTY', 'TOTAL_EXP');
    $jetNet = $sumSec($pdo, $targetMonth, 'JETTY', 'NET_PROFIT');
    $ibSt   = $pdo->prepare("SELECT ROUND(COALESCE(SUM(amount_egp),0),2) FROM pnl_monthly_lines WHERE period_month = ? AND is_interbranch = 1 AND section_type IN ('CASH_REV','ACCRUED_REV')");
    $ibSt->execute([$targetMonth]);
    $ibElim = (float) $ibSt->fetchColumn();
    $extRev = round($cyrRev + $jetRev - $ibElim, 2);
    $extExp = round($cyrExp + $jetExp - $ibElim, 2);
    $conNet = round($cyrNet + $jetNet, 2);

    $pnlConsolRows = [
        ['CYRENE', 'Total Revenue (Cyrene)', 'اجمالى ايرادات سيرين', $cyrRev],
        ['CYRENE', 'Total Expenses (Cyrene)', 'اجمالى مصروفات سيرين', $cyrExp],
        ['CYRENE', 'Net Operating Profit / (Loss) — Cyrene', 'صافى ربح / خسارة سيرين', $cyrNet],
        ['JETTY', 'Total Revenue (Jetty)', 'اجمالى ايرادات السقالة', $jetRev],
        ['JETTY', 'Total Expenses (Jetty)', 'اجمالى مصروفات السقالة', $jetExp],
        ['JETTY', 'Net Operating Profit — Jetty', 'صافى ربح السقالة', $jetNet],
        ['ELIMINATION', 'Inter-Branch Transfer Elimination', 'استبعاد التحويلات المتبادلة', -$ibElim],
        ['CONSOLIDATED', 'Consolidated External Revenue', 'صافى الايرادات الخارجية الموحدة', $extRev],
        ['CONSOLIDATED', 'Consolidated External Expenses', 'صافى المصروفات الخارجية الموحدة', $extExp],
        ['CONSOLIDATED', 'Consolidated Company Net Profit', 'صافى الربح الموحد للشركة', $conNet],
    ];

    // 6. Cashbox
    $cbSt = $pdo->prepare('SELECT tx_date, inc_le, inc_usd, inc_eur, inc_gbp, inc_visa, exp_le, exp_usd, exp_eur, exp_gbp, bal_le, bal_usd, bal_eur, bal_gbp, net_flow_egp_equiv FROM cashbox_daily_fact WHERE period_month = ? ORDER BY tx_date ASC');
    $cbSt->execute([$targetMonth]);
    $cbRows = $cbSt->fetchAll(PDO::FETCH_NUM);

    // 7. Payroll
    $prSt = $pdo->prepare('SELECT branch_code, emp_name_ar, job_title_ar, base_salary_egp, worked_days, actual_wage_egp, commission_egp, allowances_egp, bonuses_egp, advances_deductions_egp, net_pay_egp FROM payroll_monthly_fact WHERE period_month = ? ORDER BY branch_code ASC, id ASC');
    $prSt->execute([$targetMonth]);
    $prRows = $prSt->fetchAll(PDO::FETCH_NUM);
    $prNetTot = 0.0;
    foreach ($prRows as $pr) {
        $prNetTot += (float) $pr[10];
    }
    $prRows[] = ['COMBINED_TOTAL', 'اجمالى المرتبات المستحقة', 'Total Net Payroll Payable', '', '', '', '', '', '', '', round($prNetTot, 2)];

    // 8. Supermarket
    $smSt = $pdo->prepare('SELECT tx_date, oceano_guest, oceano_staff, nizar_guest, nizar_staff, omar4_guest, omar4_staff, external_boats, daily_total_egp, payment_egp, notes FROM supermarket_monthly_lines WHERE period_month = ? ORDER BY tx_date ASC');
    $smSt->execute([$targetMonth]);
    $smRows = $smSt->fetchAll(PDO::FETCH_NUM);
    $smTot = 0.0;
    $smPaid = 0.0;
    foreach ($smRows as $sr) {
        $smTot  += (float) $sr[8];
        $smPaid += (float) $sr[9];
    }
    $smRows[] = ['TOTAL', '', '', '', '', '', '', '', round($smTot, 2), round($smPaid, 2), round($smTot - $smPaid, 2)];

    // 9. Charters IN & OUT
    $chSt = $pdo->prepare('SELECT direction, trip_date, boat_name, counterparty_name, adults, children, infants, trip_description, total_egp, paid_or_collected_egp, remaining_egp FROM charter_trips_monthly WHERE period_month = ? ORDER BY direction ASC, trip_date ASC, id ASC');
    $chSt->execute([$targetMonth]);
    $chRows = $chSt->fetchAll(PDO::FETCH_NUM);
    $inTot = 0.0;
    $inRem = 0.0;
    $outTot = 0.0;
    $outRem = 0.0;
    foreach ($chRows as $cr) {
        if (strtoupper((string) $cr[0]) === 'IN') {
            $inTot += (float) $cr[8];
            $inRem += (float) $cr[10];
        } else {
            $outTot += (float) $cr[8];
            $outRem += (float) $cr[10];
        }
    }
    $chRows[] = ['IN_TOTAL', '', 'Total External Charters IN', '', '', '', '', '', round($inTot, 2), round($inTot - $inRem, 2), round($inRem, 2)];
    $chRows[] = ['OUT_TOTAL', '', 'Total Owned Charters OUT', '', '', '', '', '', round($outTot, 2), round($outTot - $outRem, 2), round($outRem, 2)];

    // 10 & 11. Partner Statements & Profit Share YTD
    $psSt = $pdo->prepare("SELECT schedule_code, schedule_title_ar, tx_date, line_description_ar, line_description_en, debit_egp, credit_egp, balance_egp, currency_note FROM partner_statements_monthly WHERE period_month = ? AND schedule_code != 'PROFIT_SHARE_YTD' ORDER BY schedule_code ASC, sort_order ASC");
    $psSt->execute([$targetMonth]);
    $psRows = $psSt->fetchAll(PDO::FETCH_NUM);

    $ytdSt = $pdo->prepare("SELECT tx_date, line_description_ar, line_description_en, credit_egp, debit_egp, balance_egp, currency_note FROM partner_statements_monthly WHERE period_month = ? AND schedule_code = 'PROFIT_SHARE_YTD' ORDER BY sort_order ASC");
    $ytdSt->execute([$targetMonth]);
    $ytdRows = $ytdSt->fetchAll(PDO::FETCH_NUM);

    // 12. Corporate Banks (Masked) & 13. Open Balances
    $bkSt = $pdo->prepare('SELECT bank_code, bank_name_ar, bank_name_en, account_masked, is_operating_bank, bal_egp, bal_usd, bal_eur, bal_gbp, total_egp_equiv FROM bank_balances_monthly WHERE period_month = ? ORDER BY id ASC');
    $bkSt->execute([$targetMonth]);
    $bkRows = [];
    $opBk = 0.0;
    $allBk = 0.0;
    foreach ($bkSt->fetchAll(PDO::FETCH_NUM) as $br) {
        $br[3] = preg_replace('/\b(\d{4})\d{4,}\b/', '$1****', (string) $br[3]);
        if ((int) $br[4] === 1) {
            $opBk += (float) $br[9];
        }
        $allBk += (float) $br[9];
        $bkRows[] = $br;
    }
    $bkRows[] = ['OPERATING_TOTAL', 'اجمالى البنوك التشغيلية', 'Operating Banks (Misr + NBE)', 'MASKED', 1, '', '', '', '', round($opBk, 2)];
    $bkRows[] = ['ALL_3_BANKS', 'اجمالى البنوك الثلاثة', 'Total Corporate Treasury (3 Banks)', 'MASKED', '', '', '', '', '', round($allBk, 2)];

    $obSt = $pdo->prepare('SELECT side, sort_order, category, party_name_ar, party_name_en, party_code, amount_egp, orig_currency_note FROM open_balances_monthly WHERE period_month = ? ORDER BY side ASC, sort_order ASC');
    $obSt->execute([$targetMonth]);
    $obRows = $obSt->fetchAll(PDO::FETCH_NUM);
    $obAr = 0.0;
    $obAp = 0.0;
    $obLiq = 0.0;
    foreach ($obRows as $obr) {
        if ($obr[0] === 'RECEIVABLE_ASSET') {
            $obAr += (float) $obr[6];
        } elseif ($obr[0] === 'PAYABLE_LIABILITY_EQUITY') {
            $obAp += (float) $obr[6];
        } elseif ($obr[2] === 'NET_FREE_LIQUIDITY') {
            $obLiq = (float) $obr[6];
        }
    }
    $obRows[] = ['SUMMARY_AR', 900, 'TOTAL_RECEIVABLES', 'اجمالى الارصدة المدينة', 'Total Receivables & Advances', 'TOTAL_AR', round($obAr, 2), ''];
    $obRows[] = ['SUMMARY_AP', 901, 'TOTAL_PAYABLES', 'اجمالى الارصدة الدائنة', 'Total Payables, Accruals & Profit', 'TOTAL_AP', round($obAp, 2), ''];

    $execHeaders = ['Schedule', 'Metric (EN)', 'Metric (AR)', 'Amount (EGP)', 'Secondary / Notes'];
    $execSummary = [
        ['01_Daily_Cash', 'Combined Cash Revenue', 'اجمالى الايراد النقدى', round($combCash, 2), 'VISA: ' . round($combVisa, 2)],
        ['02_Commissions', 'Cyrene Desk Seller Commissions', 'عمولات تسويق سيرين', (float) $cyrComTot, 'Sales: ' . (float) $cyrSalesTot],
        ['03_Cyrene_PnL', 'Cyrene Net Operating Profit/(Loss)', 'صافى ربح / خسارة سيرين', $cyrNet, 'Rev: ' . $cyrRev],
        ['04_Jetty_PnL', 'Jetty Net Operating Profit', 'صافى ربح السقالة', $jetNet, 'Rev: ' . $jetRev],
        ['05_Consolidated_PnL', 'Consolidated Net Profit (Elim -226k)', 'صافى الربح الموحد للشركة', $conNet, 'Ext Rev: ' . $extRev],
        ['07_Payroll', 'Combined Net Salaries Payable', 'صافى المرتبات المستحقة', round($prNetTot, 2), 'Cyrene + Jetty + Boats'],
        ['08_Supermarket', 'Supermarket Total Catering', 'اجمالى السوبر ماركت', round($smTot, 2), 'Payable: ' . round($smTot - $smPaid, 2)],
        ['09_Charters_In', 'External Boats Chartered IN', 'ايجار مراكب خارجية', round($inTot, 2), 'Payable: ' . round($inRem, 2)],
        ['09_Charters_Out', 'Owned Boats Chartered OUT', 'تشغيل مراكب خارجية', round($outTot, 2), 'Receivable: ' . round($outRem, 2)],
        ['12_Banks', '3-Bank Corporate Total (Masked)', 'اجمالى البنوك الثلاثة', round($allBk, 2), 'Operating: ' . round($opBk, 2)],
        ['13_Open_Balances_AR', 'Total Receivables & Advances', 'اجمالى الارصدة المدينة', round($obAr, 2), ''],
        ['13_Open_Balances_AP', 'Total Payables & Retained Pool', 'اجمالى الارصدة الدائنة', round($obAp, 2), ''],
        ['13_Net_Liquidity', 'Net Free Corporate Liquidity', 'الباقى الصافى للسيولة', round($obLiq, 2), ''],
    ];

    if ($format === 'csv') {
        sc_erp_stream_csv("Sea_College_Monthly_Report_Pack_{$targetMonth}.csv", $execHeaders, $execSummary);
    }

    sc_erp_stream_xlsx("Sea_College_Monthly_Report_Pack_{$targetMonth}.xlsx", [
        '01_Executive_Summary'     => ['headers' => $execHeaders, 'rows' => $execSummary],
        '02_Daily_Cash_Revenue'    => ['headers' => ['Date', 'FX USD', 'FX EUR', 'FX GBP', 'Cyrene LE', 'Cyrene $', 'Cyrene €', 'Cyrene £', 'Cyrene VISA', 'Cyrene Total EGP', 'Jetty LE', 'Jetty $', 'Jetty €', 'Jetty £', 'Jetty VISA', 'Jetty Total EGP', 'Combined Total EGP'], 'rows' => $dcrRows],
        '03_Seller_Commissions'    => ['headers' => ['Branch', 'Seller Code', 'Account', 'Commission Type', 'Entries', 'Gross Sales EGP', 'Supplier Cost EGP', 'Commissionable Base EGP', 'Exact Commission EGP', 'Rounded Commission EGP'], 'rows' => $scRows],
        '04_PnL_Cyrene_Branch'     => ['headers' => ['Section', 'Arabic Line', 'English Line', 'Group', 'Amount EGP', 'Cost % of Rev', 'Inter-Branch'], 'rows' => $pnlCyreneRows],
        '05_PnL_Jetty_Branch'      => ['headers' => ['Section', 'Arabic Line', 'English Line', 'Group', 'Amount EGP', 'Cost % of Rev', 'Inter-Branch'], 'rows' => $pnlJettyRows],
        '06_PnL_Consolidated'      => ['headers' => ['Scope', 'Line (EN)', 'Line (AR)', 'Amount EGP'], 'rows' => $pnlConsolRows],
        '07_Treasury_Cashbox'      => ['headers' => ['Date', 'Inc LE', 'Inc $', 'Inc €', 'Inc £', 'VISA', 'Exp LE', 'Exp $', 'Exp €', 'Exp £', 'Bal LE', 'Bal $', 'Bal €', 'Bal £', 'Net Flow EGP'], 'rows' => $cbRows],
        '08_Payroll_Cyrene_Jetty'  => ['headers' => ['Branch', 'Employee (AR)', 'Job Title', 'Base Salary', 'Days', 'Actual Wage', 'Commission', 'Allowances', 'Bonuses', 'Advances & Deductions', 'Net Payable EGP'], 'rows' => $prRows],
        '09_Supermarket_Catering'  => ['headers' => ['Date', 'Oceano Guest', 'Oceano Staff', 'Nizar Guest', 'Nizar Staff', 'Omar4 Guest', 'Omar4 Staff', 'Ext Boats', 'Daily Total EGP', 'Paid EGP', 'Notes'], 'rows' => $smRows],
        '10_Charters_In_and_Out'   => ['headers' => ['Direction', 'Date', 'Boat', 'Counterparty', 'Adults', 'Children', 'Infants', 'Description', 'Total EGP', 'Paid/Collected EGP', 'Remaining EGP'], 'rows' => $chRows],
        '11_Partner_Leases_Drydock'=> ['headers' => ['Schedule', 'Title (AR)', 'Date', 'Line (AR)', 'Line (EN)', 'Debit EGP', 'Credit EGP', 'Balance EGP', 'Notes'], 'rows' => $psRows],
        '12_Profit_Share_YTD'      => ['headers' => ['Date', 'Description (AR)', 'Description (EN)', 'Net Profit EGP', '10% Pool / CapEx EGP', 'Retained EGP', 'USD Note'], 'rows' => $ytdRows],
        '13_Corporate_Banks_Masked'=> ['headers' => ['Bank Code', 'Bank (AR)', 'Bank (EN)', 'Masked Account', 'Operating Bank', 'EGP', 'USD', 'EUR', 'GBP', 'Total EGP Equiv'], 'rows' => $bkRows],
        '14_Open_Balances_Bridge'  => ['headers' => ['Side', 'Order', 'Category', 'Party (AR)', 'Party (EN)', 'Code', 'Amount EGP', 'Notes'], 'rows' => $obRows],
    ]);
}

/* ==========================================================================
   0B. SELLER COMMISSIONS & QR ATTRIBUTION EXPORT (`seller_commissions` — F10)
   Permitted for Finance/Owners (all or filtered) AND self-scoped `SALES_REP`!
   ========================================================================== */
if ($report === 'seller_commissions') {
    if (!$hasPerm('sales.view_own_sales', 'bi.view_executive', 'gl.view_all_ledgers', 'hr.manage_payroll')) {
        http_response_code(403);
        exit('Role not authorized to export Seller Commissions.');
    }
    $canAll = $hasPerm('bi.view_executive', 'gl.view_all_ledgers', 'hr.manage_payroll');
    $reqSeller = trim((string) ($_GET['seller'] ?? ''));

    $filterSellerUser = '';
    if (!$canAll) {
        // Strictly self-scoped for SALES_REP — block horizontal escalation!
        if ($reqSeller !== '') {
            $resS = sc_erp_resolve_seller($pdo, $reqSeller);
            if (!$resS || strtolower((string) $resS['username']) !== strtolower((string) $user['username'])) {
                http_response_code(403);
                exit('Sales representatives may only export their own commission ledger.');
            }
        }
        $filterSellerUser = strtolower((string) $user['username']);
    } elseif ($reqSeller !== '') {
        $resS = sc_erp_resolve_seller($pdo, $reqSeller);
        if ($resS) {
            $filterSellerUser = strtolower((string) $resS['username']);
        }
    }

    $scWhere = [];
    $scArgs  = [];
    if ($month !== '') {
        $scWhere[] = 'period_month = ?';
        $scArgs[]  = $month;
    } elseif ($year !== '') {
        $scWhere[] = 'SUBSTR(period_month, 1, 4) = ?';
        $scArgs[]  = $year;
    }
    if ($filterSellerUser !== '') {
        $scWhere[] = 'LOWER(seller_username) = ?';
        $scArgs[]  = $filterSellerUser;
    }
    $scWhereSql = $scWhere ? (' WHERE ' . implode(' AND ', $scWhere)) : '';

    $sumSt = $pdo->prepare(
        "SELECT period_month, branch_code, rep_scope, seller_username, commission_type, status,
                COUNT(*) AS vouchers_count,
                ROUND(SUM(gross_amount_egp), 2) AS gross_sales_egp,
                ROUND(SUM(supplier_cost_egp), 2) AS supplier_cost_egp,
                ROUND(SUM(commissionable_base_egp), 2) AS commissionable_base_egp,
                ROUND(SUM(commission_egp), 2) AS commission_egp
         FROM seller_commissions_ledger {$scWhereSql}
         GROUP BY period_month, branch_code, rep_scope, seller_username, commission_type, status
         ORDER BY period_month DESC, branch_code ASC, commission_egp DESC"
    );
    $sumSt->execute($scArgs);
    $sumRows = $sumSt->fetchAll(PDO::FETCH_NUM);

    $rowSt = $pdo->prepare(
        "SELECT tx_date, period_month, voucher_no, seller_username, rep_scope, branch_code, source_channel,
                commission_type, guest_name, product_name, pax,
                gross_amount_egp, supplier_cost_egp, commissionable_base_egp, commission_rate, commission_egp,
                status, notes
         FROM seller_commissions_ledger {$scWhereSql}
         ORDER BY tx_date DESC, id DESC LIMIT 5000"
    );
    $rowSt->execute($scArgs);
    $detailRows = $rowSt->fetchAll(PDO::FETCH_NUM);

    $detailHeaders = ['Date', 'Month', 'Voucher No', 'Seller Username', 'Rep Scope', 'Branch', 'Channel', 'Commission Rule', 'Guest Name', 'Trip / Product', 'PAX', 'Gross Sales (EGP)', 'Supplier Cost (EGP)', 'Commissionable Base (EGP)', 'Rate', 'Commission (EGP)', 'Status', 'Notes'];
    $sumHeaders    = ['Month', 'Branch', 'Rep Scope', 'Seller Username', 'Commission Rule', 'Status', 'Entries', 'Gross Sales (EGP)', 'Supplier Cost (EGP)', 'Commissionable Base (EGP)', 'Commission (EGP)'];

    $fileTag = ($filterSellerUser !== '' ? str_replace('.', '_', $filterSellerUser) . '_' : '') . $stamp;
    if ($format === 'csv') {
        sc_erp_stream_csv("Sea_College_Seller_Commissions_{$fileTag}.csv", $detailHeaders, $detailRows);
    }
    sc_erp_stream_xlsx("Sea_College_Seller_Commissions_{$fileTag}.xlsx", [
        'Seller_Commission_Summary' => ['headers' => $sumHeaders, 'rows' => $sumRows],
        'Seller_Commission_Ledger'  => ['headers' => $detailHeaders, 'rows' => $detailRows],
    ]);
}

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
        "SELECT period_year, period_quarter, period_month, branch_code, section_type, line_name_ar, line_name_en, trip_or_cost_group, amount_egp, COALESCE(cost_ratio,''), is_interbranch, source_file
         FROM pnl_monthly_lines {$whereSql}
         ORDER BY period_month ASC, branch_code ASC, id ASC"
    );
    $rawSt->execute($args);
    $rawRows = $rawSt->fetchAll(PDO::FETCH_NUM);

    $sumHeaders = ['Period', 'Cyrene Revenue (EGP)', 'Cyrene Expenses (EGP)', 'Cyrene Net Profit (EGP)', 'Jaz Net (EGP)', 'Jetty Revenue (EGP)', 'Jetty Expenses (EGP)', 'Jetty Net Profit (EGP)', 'Consolidated Revenue (EGP)', 'Consolidated Expenses (EGP)', 'Net Operating Profit (EGP)'];
    $rawHeaders = ['Year', 'Quarter', 'Month', 'Branch', 'Section', 'Arabic Line Item', 'English Category', 'Group Code', 'Amount (EGP)', 'Direct Cost Ratio', 'Inter-Branch', 'Source Workbook'];

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
   3. BEST SELLERS (EXECUTED ONLY — SEPARATED HOTEL SELLERS VS B2B AGENCIES)
   ========================================================================== */
if ($report === 'best_sellers') {
    if (!$hasPerm('bi.export_bestsellers_profitability')) {
        http_response_code(403);
        exit('Role not authorized.');
    }
    $execClause = $whereSql !== '' ? ($whereSql . " AND booking_state = 'EXECUTED'") : " WHERE booking_state = 'EXECUTED'";

    $hotelSellerSt = $pdo->prepare(
        "SELECT CASE WHEN sales_rep != '' THEN sales_rep ELSE 'HOTEL_DESK' END AS seller_code,
                seller_username,
                seller_type,
                branch_code,
                COUNT(*) AS vouchers, SUM(adults) AS adults, SUM(children) AS children, SUM(total_pax) AS total_pax,
                ROUND(SUM(desk_paid_egp + rest_egp_equiv + rest_visa_egp),2) AS explicit_sheet_rev_egp,
                ROUND(SUM(seller_commission_egp),2) AS seller_commission_egp
         FROM reservations_fact {$execClause} AND seller_type = 'HOTEL_SELLER'
         GROUP BY seller_code, seller_username, branch_code ORDER BY total_pax DESC"
    );
    $hotelSellerSt->execute($args);
    $hotelSellerRows = $hotelSellerSt->fetchAll(PDO::FETCH_NUM);

    $b2bSt = $pdo->prepare(
        "SELECT CASE WHEN sales_rep != '' THEN sales_rep ELSE 'B2B_PARTNER' END AS agency_code,
                seller_type,
                COUNT(*) AS vouchers, SUM(adults) AS adults, SUM(children) AS children, SUM(total_pax) AS total_pax,
                ROUND(SUM(rest_egp_equiv),2) AS rest_collected_egp,
                ROUND(SUM(estimated_revenue_egp),2) AS est_revenue_egp
         FROM reservations_fact {$execClause} AND seller_type = 'B2B_AGENCY'
         GROUP BY agency_code ORDER BY total_pax DESC"
    );
    $b2bSt->execute($args);
    $b2bRows = $b2bSt->fetchAll(PDO::FETCH_NUM);

    $natSt = $pdo->prepare(
        "SELECT nationality, COUNT(*) AS vouchers, SUM(total_pax) AS total_pax,
                ROUND(SUM(estimated_revenue_egp),2) AS est_rev_egp
         FROM reservations_fact {$execClause}
         GROUP BY nationality ORDER BY total_pax DESC"
    );
    $natSt->execute($args);
    $natRows = $natSt->fetchAll(PDO::FETCH_NUM);

    $htlSt = $pdo->prepare(
        "SELECT hotel_name, COUNT(*) AS vouchers, SUM(total_pax) AS total_pax,
                ROUND(SUM(estimated_revenue_egp),2) AS est_rev_egp
         FROM reservations_fact {$execClause} AND hotel_name != ''
         GROUP BY hotel_name ORDER BY total_pax DESC LIMIT 100"
    );
    $htlSt->execute($args);
    $htlRows = $htlSt->fetchAll(PDO::FETCH_NUM);

    $hsHeaders = ['Seller Code', 'Seller Account', 'Seller Type', 'Branch', 'Executed Vouchers', 'Adults', 'Children', 'Executed PAX', 'Sheet Collections (EGP)', 'Seller Commission (EGP)'];
    if ($format === 'csv') {
        sc_erp_stream_csv("Sea_College_Best_Sellers_{$stamp}.csv", $hsHeaders, $hotelSellerRows);
    }
    sc_erp_stream_xlsx("Sea_College_Best_Sellers_Report_{$stamp}.xlsx", [
        'Hotel_Sellers_Executed'  => ['headers' => $hsHeaders, 'rows' => $hotelSellerRows],
        'B2B_Agencies_Executed'   => ['headers' => ['B2B Agency Code', 'Seller Type', 'Executed Vouchers', 'Adults', 'Children', 'Executed PAX', 'REST Collected (EGP)', 'Est. Revenue (EGP)'], 'rows' => $b2bRows],
        'Top_Guest_Nationalities' => ['headers' => ['Nationality', 'Executed Vouchers', 'Total PAX', 'Est. Revenue (EGP)'], 'rows' => $natRows],
        'Top_100_Hotels_PAX'      => ['headers' => ['Hotel Name', 'Executed Vouchers', 'Total PAX', 'Est. Revenue (EGP)'], 'rows' => $htlRows],
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
         WHERE section_type IN ('CASH_REV', 'ACCRUED_REV') AND COALESCE(is_interbranch, 0) = 0 " . ($whereArr ? ' AND ' . implode(' AND ', $whereArr) : '') . "
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

    $execWhere = $whereSql !== '' ? ($whereSql . " AND booking_state = 'EXECUTED'") : " WHERE booking_state = 'EXECUTED'";
    $resCatSt = $pdo->prepare(
        "SELECT product_category, COUNT(*) AS vouchers, SUM(total_pax) AS pax,
                SUM(snork_pax), SUM(intro1_pax), SUM(intro2_pax), SUM(dives_pax),
                ROUND(SUM(estimated_revenue_egp),2), ROUND(SUM(estimated_cogs_egp),2), ROUND(SUM(estimated_profit_egp),2)
         FROM reservations_fact {$execWhere}
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
    $vScope      = (string) $user['vessel_scope'];
    $vFilterSql  = '';
    $vSummarySql = '';
    $vFilterArgs = [];
    if ($vScope !== '' && $vScope !== 'ALL' && !$hasPerm('bi.export_bestsellers_profitability')) {
        $codes       = array_values(array_filter(array_map('trim', explode(',', $vScope))));
        $ph          = implode(',', array_fill(0, count($codes), '?'));
        $vFilterSql  = " WHERE vessel_code IN ({$ph})";
        $vSummarySql = " WHERE v.code IN ({$ph})";
        $vFilterArgs = $codes;
    }

    $bSumSt = $pdo->prepare(
        "SELECT v.code, v.name_en, v.name_ar, v.vessel_type, v.ownership_structure, v.monthly_fixed_rent_egp,
                COUNT(t.id) AS sea_days, COALESCE(SUM(t.total_pax),0) AS pax,
                ROUND(COALESCE(SUM(t.launch_fee_egp),0),2), ROUND(COALESCE(SUM(t.food_catering_egp),0),2),
                ROUND(COALESCE(SUM(t.supermarket_egp),0),2), ROUND(COALESCE(SUM(t.total_direct_cost_egp),0),2),
                ROUND(COALESCE(SUM(t.estimated_trip_rev_egp),0),2), ROUND(COALESCE(SUM(t.estimated_trip_net_egp),0),2),
                v.drydock_capex_total_egp, v.drydock_sea_college_share_egp, v.drydock_partner_share_egp
         FROM vessels v LEFT JOIN vessel_trips_fact t ON t.vessel_code = v.code
         {$vSummarySql}
         GROUP BY v.code ORDER BY COALESCE(SUM(t.estimated_trip_net_egp),0) DESC"
    );
    $bSumSt->execute($vFilterArgs);
    $bSum = $bSumSt->fetchAll(PDO::FETCH_NUM);

    $tRawSt = $pdo->prepare("SELECT trip_date, period_month, vessel_code, vessel_name_ar, adults, children, infants, total_pax, launch_fee_egp, food_catering_egp, supermarket_egp, total_direct_cost_egp, estimated_trip_rev_egp, estimated_trip_net_egp, staff_crew, notes, source_file FROM vessel_trips_fact {$vFilterSql} ORDER BY trip_date DESC");
    $tRawSt->execute($vFilterArgs);
    $tRawRows = $tRawSt->fetchAll(PDO::FETCH_NUM);

    $azSt = $pdo->prepare("SELECT tx_date, period_year, vessel_code, item_description, total_egp, sea_college_share_egp, partner_share_egp, source_file FROM drydock_capex_lines {$vFilterSql} ORDER BY vessel_code, id ASC");
    $azSt->execute($vFilterArgs);
    $azRows = $azSt->fetchAll(PDO::FETCH_NUM);

    $astRows = $vFilterSql === ''
        ? $pdo->query('SELECT sc_tag, location_branch, item_category, qty, brand_description, serial_or_size, status FROM equipment_assets_master ORDER BY id ASC')->fetchAll(PDO::FETCH_NUM)
        : [];

    $sumHeaders = ['Asset Code', 'Asset Name (EN)', 'Asset Name (AR)', 'Type', 'Partner / Lease Structure', 'Monthly Lease (EGP)', 'Operating Sea Days', 'Total PAX', 'Launch Fees (EGP)', 'Catering (EGP)', 'Supermarket (EGP)', 'Total Direct Cost (EGP)', 'Attributed Revenue (EGP)', 'Net Operating Contribution (EGP)', '2026 Drydock Total (EGP)', 'Sea College Drydock Share', 'Partner Drydock Receivable'];
    if ($format === 'csv') {
        sc_erp_stream_csv("Sea_College_Fleet_and_Assets_ROI_{$stamp}.csv", $sumHeaders, $bSum);
    }
    $sheets = [
        'Fleet_Profitability_Summary' => ['headers' => $sumHeaders, 'rows' => $bSum],
        'Daily_Yacht_Trips_RowLevel'  => ['headers' => ['Date', 'Month', 'Vessel', 'Arabic Name', 'Adults', 'Children', 'Infants', 'Total PAX', 'Launch Fee', 'Food', 'Supermarket', 'Direct Cost (EGP)', 'Est. Revenue (EGP)', 'Est. Net Margin (EGP)', 'Crew', 'Notes', 'Source'], 'rows' => $tRawRows],
        'Drydock_Azaq_CapEx_RowLevel' => ['headers' => ['Date', 'Year', 'Vessel', 'Overhaul Item Description', 'Total Expense (EGP)', 'Sea College Share (EGP)', 'Partner Share AR (EGP)', 'Source'], 'rows' => $azRows],
    ];
    if ($vFilterSql === '') {
        $sheets['Dive_Gear_Fixed_Assets_119'] = ['headers' => ['S.C Tag #', 'Location', 'Item Category', 'Qty', 'Brand / Model', 'Size / Serial', 'Status'], 'rows' => $astRows];
    }
    sc_erp_stream_xlsx("Sea_College_Boats_Drydock_and_Assets_{$stamp}.xlsx", $sheets);
}

/* ==========================================================================
   6. FULL ROW-LEVEL RESERVATIONS, AR/AP SUBLEDGERS & HR PAYROLL
   ========================================================================== */
if ($report === 'full_data_pack') {
    if (!$hasPerm('gl.view_all_ledgers', 'bi.export_pnl_cashflow')) {
        http_response_code(403);
        exit('Role not authorized.');
    }
    $resSt = $pdo->prepare("SELECT voucher_no, trip_date, period_month, section_banner, booking_state, branch_code, hotel_name, room_no, nationality, pickup_time, trip_name, product_category, adults, children, infants, total_pax, cancelled_pax, seller_type, sales_rep, seller_username, executor, vessel_code, ar_client_code, ap_supplier_code, desk_paid_egp, rest_egp_equiv, rest_visa_egp, seller_commission_egp, estimated_revenue_egp, estimated_cogs_egp, estimated_profit_egp, accounting_status, journal_entry_no, source_file FROM reservations_fact {$whereSql} ORDER BY trip_date DESC LIMIT 16000");
    $resSt->execute($args);
    $resRows = $resSt->fetchAll(PDO::FETCH_NUM);

    $cpRows  = $pdo->query('SELECT code, name_ar, party_type, total_debit_egp, total_credit_egp, current_balance_egp, source_workbook FROM counterparties ORDER BY party_type, ABS(current_balance_egp) DESC')->fetchAll(PDO::FETCH_NUM);
    $payRows = $pdo->query('SELECT period_month, branch_code, emp_name_ar, job_title_ar, base_salary_egp, worked_days, actual_wage_egp, commission_egp, allowances_egp, bonuses_egp, advances_deductions_egp, net_pay_egp, source_file FROM payroll_monthly_fact ORDER BY period_month DESC, net_pay_egp DESC')->fetchAll(PDO::FETCH_NUM);

    $resHeaders = ['Voucher No', 'Trip Date', 'Month', 'Section', 'Booking State', 'Branch', 'Hotel', 'Room', 'Nationality', 'Pickup', 'Trip', 'Category', 'Adults', 'Children', 'Infants', 'Executed PAX', 'Cancelled PAX', 'Seller Type', 'Seller', 'Seller Account', 'Executor', 'Vessel Code', 'AR Client Code', 'AP Supplier Code', 'Desk Paid EGP', 'Jetty REST EGP', 'VISA EGP', 'Seller Com EGP', 'Total Revenue EGP', 'Direct COGS EGP', 'Net Margin EGP', 'Accounting Link Status', 'GL Journal Entry #', 'Source Workbook'];
    if ($format === 'csv') {
        sc_erp_stream_csv("Sea_College_Reservations_Accounting_RowLevel_{$stamp}.csv", $resHeaders, $resRows);
    }
    sc_erp_stream_xlsx("Sea_College_Master_Reservations_AR_AP_Payroll_{$stamp}.xlsx", [
        'Reservations_GL_RowLevel' => ['headers' => $resHeaders, 'rows' => $resRows],
        'AR_AP_Counterparties'     => ['headers' => ['Account Code', 'Party Name', 'Type', 'Total Debit (EGP)', 'Total Credit (EGP)', 'Closing Balance (EGP)', 'Source Workbook'], 'rows' => $cpRows],
        'HR_Monthly_Payroll'       => ['headers' => ['Month', 'Branch', 'Employee Name', 'Job Title', 'Base Salary', 'Worked Days', 'Actual Wage', 'Commission', 'Allowances', 'Bonuses', 'Advances & Deductions', 'Net Cash Pay (EGP)', 'Source Workbook'], 'rows' => $payRows],
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
                snork_pax, intro1_pax, intro2_pax, dives_pax, seller_type, sales_rep, seller_username, executor, vessel_code,
                ar_client_code, ap_supplier_code, desk_paid_egp, rest_egp_equiv, rest_visa_egp,
                seller_commission_egp, estimated_revenue_egp, estimated_cogs_egp, estimated_profit_egp, accounting_status, journal_entry_no
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

    $crewRowsRaw = $pdo->query('SELECT crew_role, name_ar, cdws_or_id_masked, vessel_assigned, source_month FROM marine_crew_roster ORDER BY crew_role, name_ar')->fetchAll(PDO::FETCH_NUM);
    $crewRows = [];
    foreach ($crewRowsRaw as $cr) {
        $cr[2] = preg_replace('/\b[23]\d{9}(\d{4})\b/', '**********$1', (string) $cr[2]);
        $crewRows[] = $cr;
    }

    $audHeaders = ['Month', 'Workbook', 'Days', 'Executed Vouchers', 'Cancelled Vouchers', 'Executed PAX', 'Cancelled PAX', 'Cyrene PAX', 'Dreams & Fayrouz PAX', 'Out Side B2B PAX', 'Discovery PAX', 'Desk Cash (EGP)', 'Jetty REST Cash (EGP)', 'POS VISA (EGP)', 'AR B2B Credit (EGP)', 'Total Accrued Revenue (EGP)', 'Direct Trip COGS (EGP)', 'AP Supplier Payable (EGP)', 'Gross Operating Margin (EGP)', 'Matched Daily Sales Vouchers', 'GL Journals Posted', 'Accounting Mode'];
    if ($format === 'csv') {
        sc_erp_stream_csv('Sea_College_May_Nov_2026_Accounting_Audit.csv', $audHeaders, $audRows);
    }
    sc_erp_stream_xlsx('Sea_College_May_to_Nov_2026_Reservations_Accounting_and_GL.xlsx', [
        'May_Nov_2026_Audit_Summary' => ['headers' => $audHeaders, 'rows' => $audRows],
        'May_Nov_2026_All_Rows'      => ['headers' => ['Voucher', 'Date', 'Month', 'Section', 'State', 'Branch', 'Hotel', 'Room', 'Nat', 'Trip', 'Category', 'Adults', 'Children', 'Executed PAX', 'Cancelled PAX', 'Snk', '1-Intro', '2-Intro', 'Diver', 'Seller Type', 'Seller', 'Seller Account', 'Executor', 'Vessel', 'AR Client', 'AP Supplier', 'Desk Cash EGP', 'Jetty REST EGP', 'VISA EGP', 'Seller Com EGP', 'Revenue EGP', 'Direct COGS EGP', 'Net Margin EGP', 'Accounting Status', 'GL Entry #'], 'rows' => $r26Rows],
        'Double_Entry_GL_Lines'      => ['headers' => ['Journal Entry #', 'Date', 'Account Code', 'Account Name (EN)', 'Account Name (AR)', 'Debit (EGP)', 'Credit (EGP)', 'Description'], 'rows' => $glRows],
        'Marine_Crew_Roster_Masked'  => ['headers' => ['Crew Role', 'Arabic Name', 'CDWS / Masked ID', 'Assigned Vessel', 'Latest Month'], 'rows' => $crewRows],
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

/* ==========================================================================
   9. TEAM USER PROFILES & CONTACTS DIRECTORY EXPORT
   ========================================================================== */
if ($report === 'team_profiles') {
    if (!$hasPerm('bi.view_executive', 'gl.view_all_ledgers', 'hr.manage_payroll')) {
        http_response_code(403);
        exit('Role not authorized.');
    }
    $tpRows = $pdo->query(
        'SELECT u.username, u.emp_code, u.display_name_en, u.display_name_ar, u.job_title_en, u.job_title_ar,
                u.phone, u.email, r.name_en, u.role_code, u.branch_scope, u.vessel_scope, u.rep_scope,
                CASE WHEN u.referral_code != "" THEN "https://seacollegediveclub.com/r/" || u.referral_code ELSE "" END AS qr_referral_url,
                CASE WHEN u.avatar_url != "" THEN "YES (" || u.avatar_url || ")" ELSE "PENDING PHOTO" END AS photo_status,
                CASE WHEN u.is_active = 1 THEN "ACTIVE" ELSE "INACTIVE" END AS status_lbl,
                u.updated_at
         FROM users u JOIN roles r ON r.role_code = u.role_code
         ORDER BY r.hierarchy_level DESC, u.id ASC'
    )->fetchAll(PDO::FETCH_NUM);
    $tpHeaders = ['Username', 'Employee Code', 'Full Name (EN)', 'Full Name (AR)', 'Job Title (EN)', 'Job Title (AR)', 'Phone / WhatsApp', 'Email', 'Role Title', 'Role Code', 'Branch Scope', 'Vessel Scope', 'Sales Rep Code', 'Seller QR Link', 'Profile Photo', 'Status', 'Last Updated'];
    if ($format === 'csv') {
        sc_erp_stream_csv('Sea_College_Team_Profiles_Directory.csv', $tpHeaders, $tpRows);
    }
    sc_erp_stream_xlsx('Sea_College_Team_Profiles_Directory.xlsx', [
        'Team_Profiles_34_Users' => ['headers' => $tpHeaders, 'rows' => $tpRows],
    ]);
}

http_response_code(400);
exit('Unknown report type.');
