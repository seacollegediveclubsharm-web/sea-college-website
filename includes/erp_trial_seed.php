<?php
/**
 * Sea College Dive Club — Zero-Friction Trial Pilot Auto-Seeder (`includes/erp_trial_seed.php`)
 * Runs automatically once if `storage/erp/sea_college_bigdata.sqlite3` has 0 users on Hostinger,
 * seeding all 9 RBAC roles, 17 permissions, 24 employee/owner accounts, 5 branches, 9 vessels,
 * 80 official EUR catalog products (`data/catalog.php`), 78 Sharm hotels (`data/hotel_zones.php`),
 * and aggregated 18-month P&L + May–Nov 2026 audit KPIs (zero private bank/debt credentials in git).
 * Full 160,163-row warehouse can then be hydrated in 1 click via the Owner Trial Control tab!
 */
declare(strict_types=1);

function sc_erp_seed_trial_if_empty(PDO $pdo): void
{
    $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($userCount > 0) {
        return;
    }

    $now = gmdate('Y-m-d\TH:i:s\Z');
    $pdo->beginTransaction();
    try {
        $roles = [
            ['OWNER_ADMIN', 'Owner / Executive & CFO', 'المالك / المدير التنفيذي والمدير المالي', 'EXECUTIVE', 100, 'executive_bi', 'Full company visibility, trial management & all Excel/CSV exports.', 'صلاحية كاملة للملاك والمدير المالي وإدارة النسخة التجريبية وتصدير التقارير.'],
            ['FLEET_PARTNER', 'Yacht Equity Partner', 'شريك حصص المراكب (قيراط)', 'FLEET', 75, 'partner_fleet', 'Scoped strictly to partnered vessels, qirat shares & drydock.', 'صلاحية مخصصة لشركاء المراكب لمتابعة رحلات مراكبهم وحصص القيراط وعمرة الازق فقط.'],
            ['CHIEF_ACCOUNTANT', 'Chief Accountant & Auditor', 'رئيس الحسابات والمراجعة', 'FINANCE', 85, 'accounting_gl', 'Full GL, AR/AP subledgers, Payroll & financial exports.', 'إدارة القيود اليومية، العملاء والموردين، المرتبات، وتصدير القوائم المالية.'],
            ['CASHIER_TREASURY', 'Cashier & Multi-Currency Safe', 'أمين الخزينة والعهد', 'TREASURY', 50, 'treasury_safe', 'Manage branch safe (EGP/USD/EUR/GBP) & expense vouchers.', 'إدارة حركة الخزينة متعددة العملات وإذون الصرف والتوريد.'],
            ['OPERATIONS_MANAGER', 'Operations & Boat Dispatch', 'مدير التشغيل وتوزيع المراكب', 'OPERATIONS', 60, 'ops_dispatch', 'Daily boat capacity planner, pickup dispatch & check-in.', 'توزيع الحجوزات على المراكب ومواعيد التحرك والترانسفير.'],
            ['JETTY_SUPERVISOR', 'Jetty Supervisor, Gear & Photo', 'مشرف السقالة والعدة والتصوير', 'JETTY', 45, 'jetty_boarding', 'QR boarding scan, REST collection & S.C # gear check-out.', 'مسح باركود الصعود وتحصيل المتبقي REST وتسليم معدات الغوص.'],
            ['DIVE_INSTRUCTOR', 'PADI Instructor / Boat Captain', 'مدرب غوص / قبطان مركب', 'MARINE_CREW', 35, 'crew_manifest', 'Assigned boat diver manifest, gear sizes & commissions.', 'كشف غواصين المركب ومقاسات المعدات والعمولات الشخصية.'],
            ['SALES_REP', 'Hotel Desk Sales Representative', 'مسؤول مبيعات الفنادق', 'SALES', 30, 'sales_voucher', 'Issue digital QR vouchers & track personal sales only.', 'إصدار فوتشرات الحجز بالباركود ومتابعة المبيعات الشخصية فقط.'],
            ['CUSTOMER_CARE', 'Multilingual Concierge & CRM', 'خدمة العملاء والواتساب والتسويق', 'CRM_MARKETING', 55, 'crm_concierge', 'Manage live website bookings, /check-in, WhatsApp & Ads ROAS.', 'متابعة حجوزات الموقع والواتساب وربط العائد الإعلاني.'],
        ];
        $rSt = $pdo->prepare('INSERT OR REPLACE INTO roles VALUES (?,?,?,?,?,?,?,?)');
        foreach ($roles as $r) {
            $rSt->execute($r);
        }

        $perms = [
            ['bi.view_executive', 'BI', 'View Executive Multi-Horizon KPIs & Trends', 'عرض مؤشرات الأداء والاتجاهات للملاك'],
            ['bi.export_pnl_cashflow', 'BI', 'Export Profit & Loss and Cash Flow Sheets', 'تصدير الأرباح والخسائر والتدفقات النقدية'],
            ['bi.export_bestsellers_profitability', 'BI', 'Export Best Sellers & Profitability Sheets', 'تصدير الأعلى مبيعاً وربحية الرحلات والمراكب'],
            ['bi.view_partner_fleet', 'FLEET', 'View Scoped Partner Vessel Trips & Drydock', 'عرض حسابات ورحلات المراكب المشتركة'],
            ['gl.view_all_ledgers', 'FINANCE', 'View General Ledger, AR Clients & AP Suppliers', 'عرض الأستاذ العام والعملاء والموردين'],
            ['gl.post_journal', 'FINANCE', 'Post & Audit Journal Entries', 'تسجيل القيود المحاسبية'],
            ['hr.manage_payroll', 'HR', 'View & Process Staff Salaries', 'إدارة الحضور والمرتبات'],
            ['safe.view_balances', 'TREASURY', 'View Multi-Currency Safe Balances', 'عرض أرصدة الخزينة والعهد'],
            ['safe.record_tx', 'TREASURY', 'Record Cashbox Income & Expense Vouchers', 'تسجيل إيراد ومصروف الخزينة'],
            ['ops.view_manifests', 'OPERATIONS', 'View Daily Reservations & Boats', 'عرض كشوف التشغيل اليومية'],
            ['ops.assign_boat_driver', 'OPERATIONS', 'Assign Boats & Transfer Buses', 'تسكين المراكب والترانسفير'],
            ['jetty.scan_boarding_collect_rest', 'JETTY', 'Scan Voucher QR & Collect REST', 'مسح الباركود وتحصيل المتبقي'],
            ['jetty.manage_gear_photo', 'JETTY', 'Checkout S.C # Dive Gear', 'تسليم العدة المرقمة'],
            ['crew.view_own_manifest', 'CREW', 'View Assigned Boat Divers & Sizes', 'عرض غواصين المركب ومقاساتهم'],
            ['sales.create_voucher', 'SALES', 'Create Digital Guest Voucher with QR', 'إصدار فوتشر حجز جديد بالباركود'],
            ['sales.view_own_sales', 'SALES', 'View Personal Voucher History Only', 'متابعة المبيعات الشخصية فقط'],
            ['crm.manage_leads_ads', 'CRM', 'Manage Website Leads, Check-Ins & Ads', 'إدارة عملاء الموقع والواتساب والإعلانات'],
        ];
        $pSt = $pdo->prepare('INSERT OR REPLACE INTO permissions VALUES (?,?,?,?)');
        foreach ($perms as $p) {
            $pSt->execute($p);
        }

        $allPermCodes = array_column($perms, 0);
        $rpMap = [
            'OWNER_ADMIN'        => $allPermCodes,
            'CHIEF_ACCOUNTANT'   => ['bi.view_executive', 'bi.export_pnl_cashflow', 'bi.export_bestsellers_profitability', 'bi.view_partner_fleet', 'gl.view_all_ledgers', 'gl.post_journal', 'hr.manage_payroll', 'safe.view_balances', 'safe.record_tx', 'ops.view_manifests'],
            'FLEET_PARTNER'      => ['bi.view_partner_fleet'],
            'CASHIER_TREASURY'   => ['safe.view_balances', 'safe.record_tx', 'jetty.scan_boarding_collect_rest'],
            'OPERATIONS_MANAGER' => ['ops.view_manifests', 'ops.assign_boat_driver', 'sales.create_voucher', 'crm.manage_leads_ads'],
            'JETTY_SUPERVISOR'   => ['jetty.scan_boarding_collect_rest', 'jetty.manage_gear_photo', 'ops.view_manifests'],
            'DIVE_INSTRUCTOR'    => ['crew.view_own_manifest', 'jetty.manage_gear_photo'],
            'SALES_REP'          => ['sales.create_voucher', 'sales.view_own_sales'],
            'CUSTOMER_CARE'      => ['crm.manage_leads_ads', 'sales.create_voucher', 'ops.view_manifests', 'bi.export_bestsellers_profitability'],
        ];
        $rpSt = $pdo->prepare('INSERT OR REPLACE INTO role_permissions VALUES (?,?)');
        foreach ($rpMap as $rc => $list) {
            foreach ($list as $pc) {
                $rpSt->execute([$rc, $pc]);
            }
        }

        $users = [
            ['islam.samir', 'SeaCollege@2026', 'EMP_OWN_01', 'Eng. Islam Samir (Owner)', 'م. إسلام سمير (المالك)', 'OWNER_ADMIN', 'ALL', 'ALL', ''],
            ['eng.sayed', 'Sayed@2026', 'EMP_OWN_02', 'Capt. Sayed (Co-Owner)', 'ك. سيد (الشريك والمالك)', 'OWNER_ADMIN', 'ALL', 'ALL', ''],
            ['tarek.cfo', 'TarekCFO@2026', 'EMP_CFO_01', 'Tarek (CFO & Treasury)', 'طارق (المدير المالي CFO)', 'OWNER_ADMIN', 'ALL', 'ALL', ''],
            ['islam.saber', 'SaberGM@2026', 'EMP_GM_01', 'Islam Saber (General Manager)', 'إسلام صابر (المدير العام)', 'OWNER_ADMIN', 'ALL', 'ALL', ''],
            ['nadia.acc', 'NadiaAcc@2026', 'EMP_ACC_01', 'Nadia (Chief Accountant)', 'نادية (رئيس الحسابات)', 'CHIEF_ACCOUNTANT', 'ALL', 'ALL', ''],
            ['eng.darwish', 'Darwish@2026', 'EMP_PRT_01', 'Eng. Darwish (Oceano 75% / Omar4 50%)', 'م. درويش (شريك أوشينو وعمر 4)', 'FLEET_PARTNER', 'JETTY', 'OCEANO,OMAR_4', ''],
            ['eng.farid', 'Farid@2026', 'EMP_PRT_02', 'Eng. Farid (Nizar 25% Partner)', 'م. فريد (شريك مركب نزار)', 'FLEET_PARTNER', 'JETTY', 'NIZAR', ''],
            ['cashier.cyrene', 'SafeCyrene@2026', 'EMP_CSH_01', 'Cyrene Main Safe Cashier', 'أمين خزينة سيرين', 'CASHIER_TREASURY', 'CYRENE', 'ALL', ''],
            ['cashier.jetty', 'SafeJetty@2026', 'EMP_CSH_02', 'Travco Jetty Cashier', 'أمين خزينة السقالة', 'CASHIER_TREASURY', 'JETTY', 'ALL', ''],
            ['ahmed.nasr', 'NasrOps@2026', 'EMP_OPS_01', 'Ahmed Nasr (Reservations & Dispatch)', 'أحمد نصر (الحجز والتشغيل)', 'OPERATIONS_MANAGER', 'ALL', 'ALL', ''],
            ['talal.jetty', 'TalalJetty@2026', 'EMP_JTY_01', 'Talal (Jetty & Gear Supervisor)', 'طلال (مشرف السقالة والعدة)', 'JETTY_SUPERVISOR', 'JETTY', 'ALL', ''],
            ['ahmed.gamal', 'GamalPADI@2026', 'EMP_INS_01', 'Capt. Ahmed Gamal (Senior Instructor)', 'ك. أحمد جمال (مدرب بادي)', 'DIVE_INSTRUCTOR', 'CYRENE', 'OCEANO,OMAR_4,NIZAR', 'AHMED GAMAL'],
            ['capt.mario', 'MarioPADI@2026', 'EMP_INS_02', 'Capt. Mario (Dive Instructor)', 'ك. ماريو (مدرب غوص)', 'DIVE_INSTRUCTOR', 'JETTY', 'OCEANO,OMAR_4', 'MARIO'],
            ['capt.farid', 'CaptFarid@2026', 'EMP_CPT_01', 'Capt. Farid Labib (Yacht Captain)', 'ريس فريد لبيب (قبطان مركب)', 'DIVE_INSTRUCTOR', 'JETTY', 'OCEANO', 'FARID'],
            ['fady.sales', 'FadySales@2026', 'EMP_SLS_01', 'Fady (Cyrene Senior Sales)', 'فادي (مبيعات سيرين)', 'SALES_REP', 'CYRENE', 'ALL', 'FADY'],
            ['adel.sales', 'AdelSales@2026', 'EMP_SLS_06', 'Adel (Dreams & Fayrouz Lead Sales)', 'عادل (مبيعات دريمز والفيروز)', 'SALES_REP', 'DREAMS_FAYROUZ', 'ALL', 'ADEL'],
            ['moali.sales', 'MoAliSales@2026', 'EMP_SLS_02', 'Mohamed Ali (Fayrouz / Jaz Sales)', 'محمد علي (مبيعات الفيروز وجاز)', 'SALES_REP', 'DREAMS_FAYROUZ', 'ALL', 'MO.ALI'],
            ['sqran.sales', 'SqranSales@2026', 'EMP_SLS_07', 'Sqran (Cyrene Sales Rep)', 'سكران (مبيعات سيرين)', 'SALES_REP', 'CYRENE', 'ALL', 'SQRAN'],
            ['zola.sales', 'ZolaSales@2026', 'EMP_SLS_08', 'Zola (Dreams & Fayrouz Sales)', 'زولا (مبيعات دريمز والفيروز)', 'SALES_REP', 'DREAMS_FAYROUZ', 'ALL', 'ZOLA'],
            ['edy.sales', 'EdySales@2026', 'EMP_SLS_03', 'Edy (Sales Representative)', 'إيدي (مسؤول مبيعات)', 'SALES_REP', 'CYRENE', 'ALL', 'EDY'],
            ['yassin.sales', 'YassinSales@2026', 'EMP_SLS_04', 'Yassin (Sales Representative)', 'ياسين (مسؤول مبيعات)', 'SALES_REP', 'CYRENE', 'ALL', 'YASSIN'],
            ['alishawky.sales', 'AliSales@2026', 'EMP_SLS_05', 'Ali Shawky (Sales Representative)', 'علي شوقي (مسؤول مبيعات)', 'SALES_REP', 'CYRENE', 'ALL', 'ALI SHAWKY'],
            ['lara.care', 'LaraCare@2026', 'EMP_CRM_01', 'Lara (WhatsApp & Website Concierge)', 'لارا (خدمة عملاء الموقع والواتساب)', 'CUSTOMER_CARE', 'WEBSITE', 'ALL', 'ONLINE'],
            ['hossam.ads', 'HossamAds@2026', 'EMP_MKT_01', 'Hossam (Digital Marketing & Ads Director)', 'حسام (مدير التسويق والإعلانات)', 'CUSTOMER_CARE', 'WEBSITE', 'ALL', ''],
        ];
        $uSt = $pdo->prepare('INSERT OR REPLACE INTO users (username, password_hash, pin_hint, emp_code, display_name_en, display_name_ar, role_code, branch_scope, vessel_scope, rep_scope, is_active, created_at) VALUES (?,?, "", ?,?,?,?,?,?,?, 1, ?)');
        foreach ($users as $u) {
            $uSt->execute([$u[0], sc_erp_hash_password($u[1]), $u[2], $u[3], $u[4], $u[5], $u[6], $u[7], $u[8], $now]);
        }

        $branches = [
            ['CYRENE', 'Cyrene Sharm Resort Center & Beach', 'مركز وشاطئ سيرين', 'Al Montazah, Sharm El Sheikh'],
            ['DREAMS_FAYROUZ', 'Dreams Beach & El Fayrouz Desks', 'مكاتب دريمز بيتش والفيروز', 'Hadaba & Naama Bay'],
            ['JETTY', 'Travco Marina Jetty Hub', 'سنتر السقالة (ترافكو)', 'Sharks Bay / Marina'],
            ['JAZ', 'Jaz Resort Counter & POS', 'مكتب مبيعات وفيزا جاز', 'Sharm El Sheikh'],
            ['WEBSITE', 'Website & WhatsApp Direct (8-Lang)', 'الموقع الإلكتروني والواتساب', 'seacollegediveclub.com'],
        ];
        $bSt = $pdo->prepare('INSERT OR REPLACE INTO branches VALUES (?,?,?,?,1)');
        foreach ($branches as $b) {
            $bSt->execute($b);
        }

        $vessels = [
            ['OCEANO', 'M/Y Oceano', 'مركب أوشينو', 'YACHT', 40, '18q Partner (75%) / 6q Sea College (25%)', 'Eng. Darwish', 18, 6, 280000, 3166256, 791564, 2374692, 'ACTIVE'],
            ['OMAR_4', 'M/Y Omar 4', 'مركب عمر 4', 'YACHT', 42, '12q Partner (50%) / 12q Sea College (50%)', 'Eng. Darwish', 12, 12, 350000, 2869531, 1434766, 1434765, 'ACTIVE'],
            ['NIZAR', 'M/Y Nizar', 'مركب نزار', 'YACHT', 36, '6q Partner (25%) / 18q Sea College (75%)', 'Eng. Farid', 6, 18, 250000, 498752, 374064, 124688, 'ACTIVE'],
            ['SOHS', 'M/Y Sohs', 'مركب سحس', 'YACHT', 34, 'Fixed Charter Lease (200k EGP/mo)', 'Charter Lease', 0, 0, 200000, 0, 0, 0, 'ACTIVE'],
            ['OMAR_1', 'M/Y Omar 1', 'مركب عمر 1', 'YACHT', 34, 'Seasonal Yacht Operation', 'Sea College', 0, 24, 180000, 0, 0, 0, 'ACTIVE'],
            ['EXTERNAL_BOATS', 'External Charter Yachts', 'مراكب خارجية (شيرنج)', 'EXTERNAL', 45, 'Per-PAX / Daily Charter', 'External Operators', 0, 0, 0, 0, 0, 0, 'ACTIVE'],
            ['YOUANA', 'Speedboat Youana', 'سبيد يوانا', 'SPEEDBOAT', 10, 'Sea College Speedboat', 'Sea College', 0, 24, 75000, 0, 0, 0, 'ACTIVE'],
            ['SHARM_REEB', 'Zodiac Sharm Reeb', 'زودياك شرم ريب', 'ZODIAC', 12, 'Sea College RIB', 'Sea College', 0, 24, 75000, 0, 0, 0, 'ACTIVE'],
            ['SC_HIACE', 'Sea College Toyota HiAce', 'باص هاي اس السنتر', 'VEHICLE', 14, 'Guest Transfer Minibus', 'Sea College', 0, 24, 25000, 0, 0, 0, 'ACTIVE'],
        ];
        $vSt = $pdo->prepare('INSERT OR REPLACE INTO vessels VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        foreach ($vessels as $v) {
            $vSt->execute($v);
        }

        $safes = [
            ['SAFE_CYRENE', 'Cyrene Main Cashbox (Trial)', 'خزينة سيرين', 'CASHBOX', 'CYRENE', 184520, 4120, 3285, 410, $now],
            ['SAFE_JETTY', 'Travco Jetty Cashbox (Trial)', 'خزينة السقالة', 'CASHBOX', 'JETTY', 96340, 1850, 2190, 95, $now],
            ['CUSTODY_CYRENE', 'Cyrene Petty Custody', 'عهدة سيرين', 'CUSTODY', 'CYRENE', 15000, 200, 100, 50, $now],
            ['POS_VISA_JAZ', 'VISA JAZ Terminal', 'ماكينة فيزا جاز', 'POS', 'JAZ', 248900, 0, 0, 0, $now],
        ];
        $sSt = $pdo->prepare('INSERT OR REPLACE INTO safes_wallets VALUES (?,?,?,?,?,?,?,?,?,?)');
        foreach ($safes as $s) {
            $sSt->execute($s);
        }

        // Seed 78 Sharm Hotels & 80 Catalog Products directly from Website PHP files!
        $hzFile = ROOT_DIR . '/data/hotel_zones.php';
        if (is_file($hzFile)) {
            $hz = require $hzFile;
            $zones = $hz['zones'] ?? [];
            $hSt = $pdo->prepare('INSERT OR REPLACE INTO hotels_directory VALUES (?,?,?,?,?,?,?,?)');
            foreach (($hz['hotels'] ?? []) as $i => $h) {
                $zc = (string) ($h['zone'] ?? 'montazah');
                $zi = $zones[$zc] ?? [];
                $code = substr(trim((string) preg_replace('/[^A-Z0-9]+/', '_', strtoupper($h['name'])), '_'), 0, 32) ?: ('HTL_' . $i);
                $hSt->execute([
                    $code,
                    (string) $h['name'],
                    $zc,
                    (string) ($zi['label']['en'] ?? $zc),
                    (string) ($zi['label']['ar'] ?? $zc),
                    (string) ($zi['pickup'] ?? '07:45'),
                    (string) ($zi['drive']['en'] ?? ''),
                    !empty($h['onsite']) ? 1 : 0,
                ]);
            }
        }

        $catFile = ROOT_DIR . '/data/catalog.php';
        if (is_file($catFile)) {
            $cat = require $catFile;
            $cSt = $pdo->prepare('INSERT OR REPLACE INTO products_catalog VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
            foreach ($cat as $hubKey => $hub) {
                if (!is_array($hub)) {
                    continue;
                }
                foreach (($hub['groups'] ?? []) as $grp) {
                    $gTitle = is_array($grp['title'] ?? null) ? ($grp['title']['en'] ?? $hubKey) : (string) ($grp['title'] ?? $hubKey);
                    foreach (($grp['items'] ?? []) as $it) {
                        $pid = (string) ($it['id'] ?? '');
                        if ($pid === '') {
                            continue;
                        }
                        $prEur = isset($it['price']) && is_numeric($it['price']) ? (float) $it['price'] : null;
                        $prEgp = round(($prEur ?? 0.0) * 54.0, 2);
                        $rat   = ($it['section'] ?? '') === 'courses' ? 0.30 : 0.38;
                        $cSt->execute([
                            $pid,
                            (string) ($it['section'] ?? 'tours'),
                            (string) ($it['slug'] ?? ''),
                            (string) $gTitle,
                            (string) ($it['name'] ?? $pid),
                            (string) ($it['name_ar'] ?? $it['name'] ?? $pid),
                            $prEur,
                            $prEgp,
                            $rat,
                            round($prEgp * $rat, 2),
                            (string) ($it['duration'] ?? ''),
                            !empty($it['popular']) ? 1 : 0,
                            (($it['section'] ?? '') === 'courses') ? 1 : 0,
                        ]);
                    }
                }
            }
        }

        // Seed 18-Month Aggregated P&L Trend & May–Nov 2026 Audit Summary so Owner BI works immediately on Trial!
        $pnlSummary = [
            ['2025-06', '2025-Q2', '2025', 4833192, 4035034, 798158],
            ['2025-07', '2025-Q3', '2025', 5983412, 4918095, 1065317],
            ['2025-08', '2025-Q3', '2025', 7052336, 5330900, 1721436],
            ['2025-09', '2025-Q3', '2025', 6156331, 5738726, 417605],
            ['2025-10', '2025-Q4', '2025', 5944794, 4724205, 1220589],
            ['2025-11', '2025-Q4', '2025', 7006715, 5774615, 1232100],
            ['2025-12', '2025-Q4', '2025', 4914474, 4982844, -68370],
            ['2026-01', '2026-Q1', '2026', 4400649, 4191965, 208684],
            ['2026-02', '2026-Q1', '2026', 4075031, 3834627, 240404],
            ['2026-03', '2026-Q1', '2026', 4166404, 4166681, -277],
            ['2026-04', '2026-Q2', '2026', 5281798, 5098302, 183496],
            ['2026-05', '2026-Q2', '2026', 4948425, 4891458, 56967],
            ['2026-06', '2026-Q2', '2026', 5327168, 5080821, 246347],
            ['2026-07', '2026-Q3', '2026', 6416613, 5992196, 424417],
            ['2026-08', '2026-Q3', '2026', 7873816, 5595586, 2278230],
            ['2026-09', '2026-Q3', '2026', 6274410, 4816001, 1458409],
            ['2026-10', '2026-Q4', '2026', 7032880, 5406324, 1626556],
            ['2026-11', '2026-Q4', '2026', 6868834, 5362464, 1506370],
        ];
        $pnlSt = $pdo->prepare("INSERT INTO pnl_monthly_lines VALUES (NULL,?,?,?,'CYRENE',?,?,?,?,?,NULL,'TRIAL_BOOTSTRAP_SUMMARY')");
        foreach ($pnlSummary as $m) {
            $pnlSt->execute([$m[0], $m[1], $m[2], 'TOTAL_REV', 'اجمالى الايرادات', 'Total Revenue', 'TOTAL_REV', $m[3]]);
            $pnlSt->execute([$m[0], $m[1], $m[2], 'TOTAL_EXP', 'اجمالى المصروفات', 'Total Expenses', 'TOTAL_EXP', $m[4]]);
            $pnlSt->execute([$m[0], $m[1], $m[2], 'NET_PROFIT', 'صافى الربح / الخساره', 'Net Operating Profit', 'NET_PROFIT', $m[5]]);
        }

        $audSt = $pdo->prepare('INSERT OR REPLACE INTO reservation_accounting_audit VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $audSummary = [
            ['2026-05', 'RESERVATION MAY 2026', 31, 822, 44, 2527, 118, 912, 745, 760, 110, 3841852, 348154, 0, 883749, 5073755, 1688727, 620000, 3385028, 498, 31, 'AUDITED_ACC_PLUS_GL'],
            ['2026-06', 'RESERVATION JUN 2026', 30, 880, 31, 3118, 80, 1120, 910, 920, 168, 4149179, 421871, 0, 1126813, 5697863, 2131399, 780000, 3566464, 562, 30, 'AUDITED_ACC_PLUS_GL'],
            ['2026-07', 'RESERVATION JULY 2026', 31, 1026, 48, 3905, 150, 1340, 1125, 1160, 280, 4956336, 516231, 0, 1578589, 7051156, 2650689, 940000, 4400467, 644, 31, 'AUDITED_ACC_PLUS_GL'],
            ['2026-08', 'RESERVATION AUG 2026', 31, 1139, 86, 4410, 285, 1480, 1290, 1340, 300, 5180293, 702899, 0, 1990624, 7873816, 3016386, 1120000, 4857430, 0, 31, 'LIVE_OPERATIONAL_ACCRUAL_GL'],
            ['2026-09', 'RESERVATION SEP 2026', 30, 985, 44, 3457, 108, 1190, 940, 1057, 270, 3852933, 510307, 0, 1911170, 6274410, 2236801, 890000, 4037609, 0, 30, 'LIVE_OPERATIONAL_ACCRUAL_GL'],
            ['2026-10', 'RESERVATION OCT 2026', 30, 1099, 56, 4103, 155, 1210, 1190, 1353, 350, 3886105, 705912, 18500, 2440863, 7032880, 2827124, 1080000, 4205756, 0, 30, 'LIVE_OPERATIONAL_ACCRUAL_GL'],
            ['2026-11', 'RESERVATION NOV 2026', 30, 992, 36, 3913, 88, 1180, 1340, 983, 410, 4698663, 569531, 14200, 1600640, 6868834, 2783264, 990000, 4085570, 0, 26, 'LIVE_OPERATIONAL_ACCRUAL_GL'],
        ];
        foreach ($audSummary as $a) {
            $audSt->execute($a);
        }

        // Welcome note in Trial Feedback Inbox
        $pdo->prepare('INSERT INTO trial_feedback (created_at, username, display_name, role_code, module_tab, feedback_type, priority, device_info, message, status, admin_reply) VALUES (?,?,?,?,?,?,?,?,?,?,?)')->execute([
            $now, 'karim.architect', 'Karim & AI Team (Pilot Ready)', 'OWNER_ADMIN', 'trial_pilot',
            'IMPROVEMENT', 'NORMAL', 'Cloud Bootstrap',
            'Trial v1.0 is live! Every employee can tap the [🧪 Trial Feedback] button on their phone to report bugs, accounting edge cases, or UI requests. Owners can also hydrate the full 5.4 MB row-level SQL dump (.sql.gz) in 1 click from this tab.',
            'OPEN', 'Monitoring live pilot feedback with Ayman, Nadia, Tarek, Lara & Hossam.',
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('SC_ERP trial seed warning: ' . $e->getMessage());
    }
}
