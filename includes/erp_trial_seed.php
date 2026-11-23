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

        // [username, password, pin_hint, emp_code, display_en, display_ar, job_en, job_ar, phone, email, avatar, role_code, branch_scope, vessel_scope, rep_scope, referral_code, dept]
        $users = [
            ['islam.samir', 'SeaCollege@2026', 'Owner2026', 'EMP_OWN_01', 'Islam Samir', 'إسلام سمير', 'Founder & Managing Owner', 'المؤسس والشريك المالك', '+20 10 00344544', 'islam.samir@seacollegediveclub.com', '/assets/erp/avatars/islam-samir.jpg', 'OWNER_ADMIN', 'ALL', 'ALL', '', '', 'EXECUTIVE'],
            ['eng.sayed', 'SayedOwner@2026', 'Sayed2026', 'EMP_OWN_02', 'Sayed Elsharqawy', 'سيد الشرقاوي', 'Co-Founder & Managing Owner', 'الشريك المؤسس والمالك', '+20 11 14567886', 'sayed.elsharqawy@seacollegediveclub.com', '/assets/erp/avatars/sayed-elsharqawy.jpg', 'OWNER_ADMIN', 'ALL', 'ALL', '', '', 'EXECUTIVE'],
            ['tarek.cfo', 'TarekCFO@2026', 'CFO2026', 'EMP_CFO_01', 'Tarek (CFO & Treasury)', 'طارق (المدير المالي CFO)', 'Chief Financial Officer', 'المدير المالي', '', 'tarek.cfo@seacollegediveclub.com', '', 'OWNER_ADMIN', 'ALL', 'ALL', '', '', 'EXECUTIVE'],
            ['islam.saber', 'SaberGM@2026', 'GM2026', 'EMP_GM_01', 'Islam Saber (General Manager)', 'إسلام صابر (المدير العام)', 'General Manager', 'المدير العام', '', 'islam.saber@seacollegediveclub.com', '', 'OWNER_ADMIN', 'ALL', 'ALL', '', '', 'EXECUTIVE'],
            ['nadia.acc', 'NadiaAcc@2026', 'Acc2026', 'EMP_ACC_01', 'Nadia (Chief Accountant)', 'نادية (رئيس الحسابات)', 'Chief Accountant & Auditor', 'رئيس الحسابات والمراجعة', '', 'nadia.acc@seacollegediveclub.com', '', 'CHIEF_ACCOUNTANT', 'ALL', 'ALL', '', '', 'FINANCE'],
            ['eng.darwish', 'Darwish@2026', 'Boat18q', 'EMP_PRT_01', 'Eng. Darwish (Oceano 75% / Omar4 50%)', 'م. درويش (شريك أوشينو وعمر 4)', 'Yacht Equity Partner', 'شريك حصص المراكب', '', 'eng.darwish@seacollegediveclub.com', '', 'FLEET_PARTNER', 'JETTY', 'OCEANO,OMAR_4', '', '', 'FLEET'],
            ['eng.farid', 'Farid@2026', 'Nizar6q', 'EMP_PRT_02', 'Eng. Farid (Nizar 25% Partner)', 'م. فريد (شريك مركب نزار)', 'Yacht Equity Partner', 'شريك مركب نزار', '', 'eng.farid@seacollegediveclub.com', '', 'FLEET_PARTNER', 'JETTY', 'NIZAR', '', '', 'FLEET'],
            ['cashier.cyrene', 'SafeCyrene@2026', 'Safe1101', 'EMP_CSH_01', 'Cyrene Main Safe Cashier', 'أمين خزينة سيرين', 'Main Safe Cashier', 'أمين خزينة سيرين', '', 'cashier.cyrene@seacollegediveclub.com', '', 'CASHIER_TREASURY', 'CYRENE', 'ALL', '', '', 'TREASURY'],
            ['cashier.jetty', 'SafeJetty@2026', 'Safe1102', 'EMP_CSH_02', 'Travco Jetty Cashier', 'أمين خزينة السقالة', 'Jetty Cashier', 'أمين خزينة السقالة', '', 'cashier.jetty@seacollegediveclub.com', '', 'CASHIER_TREASURY', 'JETTY', 'ALL', '', '', 'TREASURY'],
            ['ahmed.nasr', 'NasrOps@2026', 'Ops2026', 'EMP_OPS_01', 'Ahmed Nasr (Reservations & Dispatch)', 'أحمد نصر (الحجز والتشغيل)', 'Reservations & Dispatch Manager', 'مدير الحجز والتشغيل', '', 'ahmed.nasr@seacollegediveclub.com', '', 'OPERATIONS_MANAGER', 'ALL', 'ALL', '', '', 'OPERATIONS'],
            ['talal.jetty', 'TalalJetty@2026', 'Jetty2026', 'EMP_JTY_01', 'Talal (Jetty & Gear Supervisor)', 'طلال (مشرف السقالة والعدة)', 'Jetty & Gear Supervisor', 'مشرف السقالة والعدة', '', 'talal.jetty@seacollegediveclub.com', '', 'JETTY_SUPERVISOR', 'JETTY', 'ALL', '', '', 'JETTY'],
            ['ahmed.gamal', 'GamalPADI@2026', 'PADI2026', 'EMP_INS_01', 'Capt. Ahmed Gamal (Senior Instructor)', 'ك. أحمد جمال (مدرب بادي)', 'Senior PADI Instructor', 'مدرب غوص بادي', '', 'ahmed.gamal@seacollegediveclub.com', '', 'DIVE_INSTRUCTOR', 'CYRENE', 'OCEANO,OMAR_4,NIZAR', 'AHMED GAMAL', '', 'MARINE_CREW'],
            ['capt.mario', 'MarioPADI@2026', 'Mario2026', 'EMP_INS_02', 'Capt. Mario (Dive Instructor)', 'ك. ماريو (مدرب غوص)', 'PADI Dive Instructor', 'مدرب غوص', '', 'capt.mario@seacollegediveclub.com', '', 'DIVE_INSTRUCTOR', 'JETTY', 'OCEANO,OMAR_4', 'MARIO', '', 'MARINE_CREW'],
            ['capt.farid', 'CaptFarid@2026', 'Capt2026', 'EMP_CPT_01', 'Capt. Farid Labib (Yacht Captain)', 'ريس فريد لبيب (قبطان مركب)', 'Yacht Captain', 'قبطان مركب', '', 'capt.farid@seacollegediveclub.com', '', 'DIVE_INSTRUCTOR', 'JETTY', 'OCEANO', 'FARID', '', 'MARINE_CREW'],
            // Canonical 18 SALES_REP Accounts
            ['fady.sales', 'FadySales@2026', 'Fady2026', 'EMP_SLS_01', 'Fady (Cyrene Senior Sales)', 'فادي صالح (مبيعات سيرين)', 'Senior Hotel Sales Consultant', 'مسؤول مبيعات سيرين', '', 'fady.sales@seacollegediveclub.com', '', 'SALES_REP', 'CYRENE', 'ALL', 'FADY', 'fady', 'SALES'],
            ['adel.sales', 'AdelSales@2026', 'Adel2026', 'EMP_SLS_02', 'Adel (Dreams & Fayrouz Lead Sales)', 'عادل (مبيعات دريمز والفيروز)', 'Lead Hotel Sales Consultant', 'مسؤول مبيعات دريمز والفيروز', '', 'adel.sales@seacollegediveclub.com', '', 'SALES_REP', 'DREAMS_FAYROUZ', 'ALL', 'ADEL', 'adel', 'SALES'],
            ['moali.sales', 'MoAliSales@2026', 'MoAli2026', 'EMP_SLS_03', 'Mohamed Ali (Cyrene Sales)', 'محمد علي (مبيعات سيرين)', 'Hotel Sales Consultant', 'مسؤول مبيعات سيرين', '', 'moali.sales@seacollegediveclub.com', '', 'SALES_REP', 'CYRENE', 'ALL', 'MO.ALI', 'moali', 'SALES'],
            ['sqran.sales', 'SqranSales@2026', 'Sqran2026', 'EMP_SLS_04', 'Sqran (Cyrene Tech Mgr & Sales)', 'أحمد السكران (مدير فني ومبيعات سيرين)', 'Technical Manager & Sales', 'مدير فني ومبيعات سيرين', '', 'sqran.sales@seacollegediveclub.com', '', 'SALES_REP', 'CYRENE', 'ALL', 'SQRAN', 'sqran', 'SALES'],
            ['zola.sales', 'ZolaSales@2026', 'Zola2026', 'EMP_SLS_05', 'Zola (Cyrene Tech Mgr & Sales)', 'زنفل - زولا (مدير فني ومبيعات سيرين)', 'Technical Manager & Sales', 'مدير فني ومبيعات سيرين', '', 'zola.sales@seacollegediveclub.com', '', 'SALES_REP', 'CYRENE', 'ALL', 'ZOLA', 'zola', 'SALES'],
            ['edy.sales', 'EdySales@2026', 'Edy2026', 'EMP_SLS_06', 'Edy (External & Desk Sales)', 'إيدي - إدوارد (مسؤول مبيعات)', 'Sales Representative', 'مسؤول مبيعات', '', 'edy.sales@seacollegediveclub.com', '', 'SALES_REP', 'CYRENE', 'ALL', 'EDY', 'edy', 'SALES'],
            ['yassin.sales', 'YassinSales@2026', 'Yassin2026', 'EMP_SLS_07', 'Yassin (Cyrene Sales Rep)', 'ياسين (مبيعات سيرين)', 'Hotel Sales Consultant', 'مسؤول مبيعات سيرين', '', 'yassin.sales@seacollegediveclub.com', '', 'SALES_REP', 'CYRENE', 'ALL', 'YASSIN', 'yassin', 'SALES'],
            ['alishawky.sales', 'AliSales@2026', 'Ali2026', 'EMP_SLS_08', 'Ali Shawky (Watersports & Speed Sales)', 'علي شوقي (مبيعات الاسبيد والألعاب المائية)', 'Watersports & Speed Sales', 'مبيعات الاسبيد والألعاب المائية', '', 'alishawky.sales@seacollegediveclub.com', '', 'SALES_REP', 'CYRENE', 'ALL', 'ALI SHAWKY', 'alishawky', 'SALES'],
            ['ammar.sales', 'AmmarSales@2026', 'Ammar2026', 'EMP_SLS_09', 'Ammar (Cyrene Senior Sales)', 'عمار (مبيعات سيرين)', 'Senior Hotel Sales Consultant', 'مسؤول مبيعات سيرين', '', 'ammar.sales@seacollegediveclub.com', '', 'SALES_REP', 'CYRENE', 'ALL', 'AMMAR', 'ammar', 'SALES'],
            ['moabdo.sales', 'MoAbdoSales@2026', 'MoAbdo2026', 'EMP_SLS_10', 'Mohamed Abdo (Cyrene Sales)', 'محمد عبدالرحمن عبده (مبيعات سيرين)', 'Hotel Sales Consultant', 'مسؤول مبيعات سيرين', '', 'moabdo.sales@seacollegediveclub.com', '', 'SALES_REP', 'CYRENE', 'ALL', 'MO.ABDO', 'moabdo', 'SALES'],
            ['mando.sales', 'MandoSales@2026', 'Mando2026', 'EMP_SLS_11', 'Mando (Dreams & Fayrouz Sales)', 'ماندو (مبيعات دريمز والفيروز)', 'Hotel Sales Consultant', 'مسؤول مبيعات دريمز والفيروز', '', 'mando.sales@seacollegediveclub.com', '', 'SALES_REP', 'DREAMS_FAYROUZ', 'ALL', 'MANDO', 'mando', 'SALES'],
            ['chicago.sales', 'ChicagoSales@2026', 'Chicago2026', 'EMP_SLS_12', 'Ali Chicago (Dreams & Fayrouz Sales)', 'علي شيكاغو (مبيعات دريمز والفيروز)', 'Hotel Sales Consultant', 'مسؤول مبيعات دريمز والفيروز', '', 'chicago.sales@seacollegediveclub.com', '', 'SALES_REP', 'DREAMS_FAYROUZ', 'ALL', 'CHICAGO', 'chicago', 'SALES'],
            ['khairy.sales', 'KhairySales@2026', 'Khairy2026', 'EMP_SLS_13', 'Khairy (Dreams & Fayrouz Sales)', 'خيري (مبيعات دريمز والفيروز)', 'Hotel Sales Consultant', 'مسؤول مبيعات دريمز والفيروز', '', 'khairy.sales@seacollegediveclub.com', '', 'SALES_REP', 'DREAMS_FAYROUZ', 'ALL', 'KHAIRY', 'khairy', 'SALES'],
            ['eldeeb.sales', 'EldeebSales@2026', 'Eldeeb2026', 'EMP_SLS_14', 'El-Deeb (Dreams & Fayrouz Sales)', 'الديب (مبيعات دريمز والفيروز)', 'Hotel Sales Consultant', 'مسؤول مبيعات دريمز والفيروز', '', 'eldeeb.sales@seacollegediveclub.com', '', 'SALES_REP', 'DREAMS_FAYROUZ', 'ALL', 'ELDEEB', 'eldeeb', 'SALES'],
            ['fawzy.sales', 'FawzySales@2026', 'Fawzy2026', 'EMP_SLS_15', 'Fawzy (Dreams & Fayrouz Sales)', 'فوزي (مبيعات دريمز والفيروز)', 'Hotel Sales Consultant', 'مسؤول مبيعات دريمز والفيروز', '', 'fawzy.sales@seacollegediveclub.com', '', 'SALES_REP', 'DREAMS_FAYROUZ', 'ALL', 'FAWZY', 'fawzy', 'SALES'],
            ['lemo.sales', 'LemoSales@2026', 'Lemo2026', 'EMP_SLS_16', 'Lemo (Dreams & Fayrouz Sales)', 'ليمو (مبيعات دريمز والفيروز)', 'Hotel Sales Consultant', 'مسؤول مبيعات دريمز والفيروز', '', 'lemo.sales@seacollegediveclub.com', '', 'SALES_REP', 'DREAMS_FAYROUZ', 'ALL', 'LEMO', 'lemo', 'SALES'],
            ['shaldm.sales', 'ShaldmSales@2026', 'Shaldm2026', 'EMP_SLS_17', 'Ahmed Shaldm (Cyrene Sales)', 'أحمد شلضم (مبيعات سيرين)', 'Hotel Sales Consultant', 'مسؤول مبيعات سيرين', '', 'shaldm.sales@seacollegediveclub.com', '', 'SALES_REP', 'CYRENE', 'ALL', 'SHALDM', 'shaldm', 'SALES'],
            ['amrhesham.sales', 'AmrSales@2026', 'Amr2026', 'EMP_SLS_18', 'Amr Hesham (Jetty Counter Sales)', 'عمرو هشام (مبيعات كونتر السقالة)', 'Jetty Counter Sales', 'مبيعات كونتر السقالة', '', 'amrhesham.sales@seacollegediveclub.com', '', 'SALES_REP', 'JETTY', 'ALL', 'AMR HESHAM', 'amrhesham', 'SALES'],
            ['lara.care', 'LaraCare@2026', 'Lara2026', 'EMP_CRM_01', 'Lara (WhatsApp & Website Concierge)', 'لارا (خدمة عملاء الموقع والواتساب)', 'Head of Customer Care & Concierge', 'مديرة خدمة العملاء والواتساب', '', 'info@seacollegediveclub.com', '', 'CUSTOMER_CARE', 'WEBSITE', 'ALL', 'ONLINE', '', 'CRM_MARKETING'],
            ['hossam.ads', 'HossamAds@2026', 'ROAS2026', 'EMP_MKT_01', 'Hossam (Digital Marketing & Ads Director)', 'حسام (مدير التسويق والإعلانات)', 'Performance Marketing Director', 'مدير التسويق الرقمي والإعلانات', '', 'hossam.ads@seacollegediveclub.com', '', 'CUSTOMER_CARE', 'WEBSITE', 'ALL', '', '', 'CRM_MARKETING'],
        ];
        $empSt = $pdo->prepare('INSERT OR REPLACE INTO employees_master (emp_code, full_name_ar, full_name_en, job_title_ar, job_title_en, branch_code, department, base_salary_egp, is_active, latest_month) VALUES (?,?,?,?,?,?,?,0,1,"2026-10")');
        $uSt = $pdo->prepare('INSERT INTO users (username, password_hash, pin_hint, emp_code, display_name_en, display_name_ar, job_title_en, job_title_ar, phone, email, avatar_url, bio_notes, role_code, branch_scope, vessel_scope, rep_scope, referral_code, default_comm_insheet_pct, default_comm_outside_pct, is_active, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?, "", ?,?,?,?,?, 0.10, 0.25, 1, ?, "") ON CONFLICT(username) DO UPDATE SET password_hash=CASE WHEN users.updated_at="" THEN excluded.password_hash ELSE users.password_hash END, pin_hint=excluded.pin_hint, emp_code=excluded.emp_code, role_code=excluded.role_code, branch_scope=excluded.branch_scope, vessel_scope=excluded.vessel_scope, rep_scope=excluded.rep_scope, referral_code=excluded.referral_code, default_comm_insheet_pct=excluded.default_comm_insheet_pct, default_comm_outside_pct=excluded.default_comm_outside_pct, is_active=1');
        foreach ($users as $u) {
            $empBranch = ($u[12] === 'ALL' || $u[12] === 'WEBSITE') ? 'CYRENE' : $u[12];
            $empSt->execute([$u[3], $u[5], $u[4], $u[7], $u[6], $empBranch, $u[16]]);
            $uSt->execute([$u[0], sc_erp_hash_password($u[1]), $u[2], $u[3], $u[4], $u[5], $u[6], $u[7], $u[8], $u[9], $u[10], $u[11], $u[12], $u[13], $u[14], $u[15], $now]);
        }

        $fxRows = [
            ['EGP', 'E£', 1.0, 0.017544, $now],
            ['USD', '$', 50.0, 0.877193, $now],
            ['EUR', '€', 57.0, 1.0, $now],
            ['GBP', '£', 65.0, 1.140351, $now],
        ];
        $fxSt = $pdo->prepare('INSERT OR REPLACE INTO currencies_fx (currency, symbol, rate_to_egp, rate_to_eur, updated_at) VALUES (?,?,?,?,?)');
        foreach ($fxRows as $fx) {
            $fxSt->execute($fx);
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
            ['OCEANO', 'M/Y Oceano', 'مركب أوشينو', 'YACHT', 40, '18q Partner (75%) / 6q Sea College (25%)', 'Eng. Darwish', 18, 6, 350000, 3166256, 791564, 2374692, 'ACTIVE'],
            ['OMAR_4', 'M/Y Omar 4', 'مركب عمر 4', 'YACHT', 42, '12q Partner (50%) / 12q Sea College (50%)', 'Eng. Darwish', 12, 12, 350000, 2869531, 1434766, 1434765, 'ACTIVE'],
            ['NIZAR', 'M/Y Nizar', 'مركب نزار', 'YACHT', 36, '6q Partner (25%) / 18q Sea College (75%)', 'Eng. Farid', 6, 18, 250000, 498752, 374064, 124688, 'ACTIVE'],
            ['SOHS', 'M/Y Sohs', 'مركب سحس', 'YACHT', 34, 'Fixed Charter Lease (200k EGP/mo)', 'Charter Lease', 0, 0, 200000, 0, 0, 0, 'ACTIVE'],
            ['OMAR_1', 'M/Y Omar 1', 'مركب عمر 1', 'YACHT', 34, 'Seasonal Yacht Operation', 'Sea College', 0, 24, 180000, 0, 0, 0, 'ACTIVE'],
            ['EXTERNAL_BOATS', 'External Charter Yachts', 'مراكب خارجية (شيرنج)', 'EXTERNAL', 45, 'Per-PAX / Daily Charter', 'External Operators', 0, 0, 0, 0, 0, 0, 'ACTIVE'],
            ['YOUANA', 'Speedboat Youana', 'سبيد يوانا', 'SPEEDBOAT', 10, 'Sea College Speedboat', 'Sea College', 0, 24, 75000, 0, 0, 0, 'ACTIVE'],
            ['SHARM_REEB', 'Zodiac Sharm Reeb', 'زودياك شرم ريب', 'ZODIAC', 12, 'Sea College RIB', 'Sea College', 0, 24, 75000, 0, 0, 0, 'ACTIVE'],
            ['SC_HIACE', 'Sea College Toyota HiAce', 'باص هاي اس السنتر', 'VEHICLE', 14, 'Guest Transfer Minibus', 'Sea College', 0, 24, 30000, 0, 0, 0, 'ACTIVE'],
        ];
        $vSt = $pdo->prepare('INSERT OR REPLACE INTO vessels VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        foreach ($vessels as $v) {
            $vSt->execute($v);
        }

        $safes = [
            ['SAFE_CYRENE', 'Cyrene Main Cashbox (Trial)', 'خزينة سيرين', 'CASHBOX', 'CYRENE', -238345, 8383, 8222, 1676, $now],
            ['SAFE_JETTY', 'Travco Jetty Cashbox (Trial)', 'خزينة السقالة', 'CASHBOX', 'JETTY', 0, 0, 0, 0, $now],
            ['CUSTODY_CYRENE', 'Cyrene Petty Custody', 'عهدة سيرين', 'CUSTODY', 'CYRENE', 0, 0, 0, 50, $now],
            ['CUSTODY_BOATS_HIACE', 'Boats, HiAce & Talal Custodies', 'عهد المراكب والهاي اس وطلال', 'CUSTODY', 'JETTY', 6000, 0, 0, 0, $now],
            ['POS_VISA_JAZ', 'VISA JAZ Terminal', 'ماكينة فيزا جاز', 'POS', 'JAZ', 2145414, 0, 0, 0, $now],
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

        // Seed 18-Month Aggregated P&L Trend & May–Nov 2026 Audit Summary (aligned to Audited Oct 2026 Reports)
        $pnlSummary = [
            ['2025-06', '2025-Q2', '2025', 'CYRENE', 4833192, 4035034, 798158],
            ['2025-07', '2025-Q3', '2025', 'CYRENE', 5983412, 4918095, 1065317],
            ['2025-08', '2025-Q3', '2025', 'CYRENE', 7052336, 5330900, 1721436],
            ['2025-09', '2025-Q3', '2025', 'CYRENE', 6156331, 5738726, 417605],
            ['2025-10', '2025-Q4', '2025', 'CYRENE', 5944794, 4724205, 1220589],
            ['2025-11', '2025-Q4', '2025', 'CYRENE', 7006715, 5774615, 1232100],
            ['2025-12', '2025-Q4', '2025', 'CYRENE', 4914474, 4982844, -68370],
            ['2026-01', '2026-Q1', '2026', 'CYRENE', 4400649, 4191964, 208685],
            ['2026-02', '2026-Q1', '2026', 'CYRENE', 4156130, 3834627, 321503],
            ['2026-03', '2026-Q1', '2026', 'CYRENE', 4166404, 4166681, -277],
            ['2026-04', '2026-Q2', '2026', 'CYRENE', 5281798, 5098303, 183495],
            ['2026-05', '2026-Q2', '2026', 'CYRENE', 4948425, 4891459, 56966],
            ['2026-06', '2026-Q2', '2026', 'CYRENE', 5327168, 5080821, 246347],
            ['2026-07', '2026-Q3', '2026', 'CYRENE', 6416613, 5995547, 421066],
            ['2026-08', '2026-Q3', '2026', 'JETTY', 7873816, 7270116, 603700],
            ['2026-09', '2026-Q3', '2026', 'JETTY', 6274410, 5856900, 417510],
            ['2026-10', '2026-Q4', '2026', 'CYRENE', 1699576, 1725464, -25888],
            ['2026-10', '2026-Q4', '2026', 'JETTY', 5539800, 5109726, 430074],
            ['2026-10', '2026-Q4', '2026', 'ELIMINATION', -226000, -226000, 0],
            ['2026-11', '2026-Q4', '2026', 'JETTY', 6868834, 5362464, 1506370],
        ];
        $pnlSt = $pdo->prepare('INSERT INTO pnl_monthly_lines (period_month, period_quarter, period_year, branch_code, section_type, line_name_ar, line_name_en, trip_or_cost_group, amount_egp, cost_ratio, source_file, is_interbranch, cost_pct_of_rev) VALUES (?,?,?,?,?,?,?,?,?,NULL,"TRIAL_BOOTSTRAP_SUMMARY",0,NULL)');
        foreach ($pnlSummary as $m) {
            if ((float) $m[4] !== 0.0) {
                $pnlSt->execute([$m[0], $m[1], $m[2], $m[3], 'TOTAL_REV', 'اجمالى الايرادات', 'Total Revenue', 'TOTAL_REV', $m[4]]);
            }
            if ((float) $m[5] !== 0.0) {
                $pnlSt->execute([$m[0], $m[1], $m[2], $m[3], 'TOTAL_EXP', 'اجمالى المصروفات', 'Total Expenses', 'TOTAL_EXP', $m[5]]);
            }
            if ($m[3] !== 'ELIMINATION') {
                $pnlSt->execute([$m[0], $m[1], $m[2], $m[3], 'NET_PROFIT', 'صافى الربح / الخساره', 'Net Operating Profit', 'NET_PROFIT', $m[6]]);
            }
        }

        $audSt = $pdo->prepare('INSERT OR REPLACE INTO reservation_accounting_audit VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $audSummary = [
            ['2026-05', 'RESERVATION MAY 2026', 31, 822, 44, 2527, 118, 912, 745, 760, 110, 3841852, 348154, 0, 883749, 5073755, 1688727, 620000, 3385028, 498, 31, 'AUDITED_ACC_PLUS_GL'],
            ['2026-06', 'RESERVATION JUN 2026', 30, 880, 31, 3118, 80, 1120, 910, 920, 168, 4149179, 421871, 0, 1126813, 5697863, 2131399, 780000, 3566464, 562, 30, 'AUDITED_ACC_PLUS_GL'],
            ['2026-07', 'RESERVATION JULY 2026', 31, 1026, 48, 3905, 150, 1340, 1125, 1160, 280, 4956336, 516231, 0, 1578589, 7051156, 2650689, 940000, 4400467, 644, 31, 'AUDITED_ACC_PLUS_GL'],
            ['2026-08', 'RESERVATION AUG 2026', 31, 1139, 86, 4410, 285, 1480, 1290, 1340, 300, 5180293, 702899, 0, 1990624, 7873816, 7270116, 1120000, 603700, 0, 31, 'AUDITED_PROFIT_STATEMENT_OCT2026'],
            ['2026-09', 'RESERVATION SEP 2026', 30, 985, 44, 3457, 108, 1190, 940, 1057, 270, 3852933, 510307, 0, 1911170, 6274410, 5856900, 890000, 417510, 0, 30, 'AUDITED_PROFIT_STATEMENT_OCT2026'],
            ['2026-10', 'RESERVATION OCT 2026', 31, 1151, 56, 3983, 165, 1210, 1190, 1233, 350, 1620510, 2984472, 1824914, 2408394, 7013376, 6609190, 3124318, 404186, 710, 35, 'AUDITED_OCT_2026_REPORTS'],
            ['2026-11', 'RESERVATION NOV 2026', 30, 992, 36, 3913, 88, 1180, 1340, 983, 410, 4698663, 569531, 14200, 1600640, 6868834, 5362464, 990000, 1506370, 0, 30, 'LIVE_OPERATIONAL_ACCRUAL_GL'],
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
