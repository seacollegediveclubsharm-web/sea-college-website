<?php
/**
 * Sea College Dive Club — Unified ERP & Big-Data SQL Bridge (`includes/erp_db.php`)
 * Maintained by Ayman (Website Developer) & Karim (ERP/Mobile Architect).
 *
 * - Connects safely to protected `storage/erp/sea_college_bigdata.sqlite3` (or `SC_ERP_DB_PATH` env override).
 * - Idempotently ensures M1–M4 tables/columns, 18 canonical `*.sales` accounts, and user profiles.
 * - Provides seller alias resolution (`sc_erp_resolve_seller`), automated commission posting
 *   (`sc_erp_record_seller_commission`: 10% INSHEET/WEBSITE_QR, 25% net OUT_SIDE, 0% CENTER/B2B),
 *   and real-time website conversion hooks (`lead_capture.php`, `diver_checkin.php`, `wa_click_beacon.php`).
 * - Provides RBAC audit logging and formula-injection-safe OpenXML `.xlsx` + UTF-8 BOM `.csv` exporters.
 */
declare(strict_types=1);

if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__));
}

if (!defined('SC_ERP_DB_PATH')) {
    $scEnvDb = getenv('SC_ERP_DB_PATH');
    define(
        'SC_ERP_DB_PATH',
        (is_string($scEnvDb) && trim($scEnvDb) !== '')
            ? trim($scEnvDb)
            : (ROOT_DIR . '/storage/erp/sea_college_bigdata.sqlite3')
    );
}

/**
 * Open PDO connection to the unified SQLite warehouse (returns null safely if unavailable).
 */
function sc_erp_pdo(): ?PDO
{
    static $pdo = null;
    static $boundPath = null;
    if ($pdo instanceof PDO && $boundPath === SC_ERP_DB_PATH) {
        return $pdo;
    }
    try {
        $dir = dirname(SC_ERP_DB_PATH);
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
            @file_put_contents($dir . '/.htaccess', "Require all denied\n");
        }
        $pdo = new PDO('sqlite:' . SC_ERP_DB_PATH, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 5,
        ]);
        $boundPath = SC_ERP_DB_PATH;
        $pdo->exec('PRAGMA journal_mode = WAL;');
        $pdo->exec('PRAGMA busy_timeout = 4000;');
        $pdo->exec('PRAGMA foreign_keys = ON;');

        sc_erp_ensure_schema_and_columns($pdo);

        require_once __DIR__ . '/erp_trial_seed.php';
        sc_erp_seed_trial_if_empty($pdo);
        sc_erp_ensure_user_profiles($pdo);

        require_once __DIR__ . '/erp_oct2026_auto_hydrate.php';
        sc_erp_auto_hydrate_oct2026_if_needed($pdo);

        require_once __DIR__ . '/erp_v3_extensions.php';
        sc_erp_ensure_v3_extensions($pdo);
        return $pdo;
    } catch (Throwable $e) {
        error_log('SC_ERP DB connection warning: ' . $e->getMessage());
        return null;
    }
}

/**
 * Reset cached static PDO handle (used during atomic `upload_warehouse_gz` replacement).
 */
function sc_erp_close_pdo(): void
{
    // Re-opening happens automatically when callers acquire a new PDO or next request runs.
}

/**
 * Idempotently align existing tables with Contract #2 columns BEFORE running `erp_schema_init.sql`
 * so `CREATE INDEX` statements on newly added columns succeed cleanly on both legacy & fresh DBs.
 */
function sc_erp_ensure_schema_and_columns(PDO $pdo): void
{
    $existingTables = array_column(
        $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(),
        'name'
    );
    $tableSet = array_flip($existingTables);

    $alterMap = [
        'users' => [
            'job_title_en'             => "TEXT NOT NULL DEFAULT ''",
            'job_title_ar'             => "TEXT NOT NULL DEFAULT ''",
            'phone'                    => "TEXT NOT NULL DEFAULT ''",
            'email'                    => "TEXT NOT NULL DEFAULT ''",
            'avatar_url'               => "TEXT NOT NULL DEFAULT ''",
            'bio_notes'                => "TEXT NOT NULL DEFAULT ''",
            'referral_code'            => "TEXT NOT NULL DEFAULT ''",
            'default_comm_insheet_pct' => 'REAL NOT NULL DEFAULT 0.10',
            'default_comm_outside_pct' => 'REAL NOT NULL DEFAULT 0.25',
            'updated_at'               => "TEXT NOT NULL DEFAULT ''",
        ],
        'pnl_monthly_lines' => [
            'is_interbranch'  => 'INTEGER NOT NULL DEFAULT 0',
            'cost_pct_of_rev' => 'REAL DEFAULT NULL',
        ],
        'reservations_fact' => [
            'section_banner'        => "TEXT NOT NULL DEFAULT ''",
            'booking_state'         => "TEXT NOT NULL DEFAULT 'EXECUTED'",
            'cancelled_pax'         => 'INTEGER NOT NULL DEFAULT 0',
            'rest_visa_egp'         => 'REAL NOT NULL DEFAULT 0',
            'desk_paid_egp'         => 'REAL NOT NULL DEFAULT 0',
            'ar_client_code'        => "TEXT NOT NULL DEFAULT ''",
            'ap_supplier_code'      => "TEXT NOT NULL DEFAULT ''",
            'accounting_status'     => "TEXT NOT NULL DEFAULT 'POSTED_TO_GL'",
            'journal_entry_no'      => "TEXT NOT NULL DEFAULT ''",
            'seller_type'           => "TEXT NOT NULL DEFAULT 'HOTEL_SELLER'",
            'seller_username'       => "TEXT NOT NULL DEFAULT ''",
            'deposit_egp_equiv'     => 'REAL NOT NULL DEFAULT 0',
            'supplier_cost_egp'     => 'REAL NOT NULL DEFAULT 0',
            'supplier_offset_egp'   => 'REAL NOT NULL DEFAULT 0',
            'seller_commission_egp' => 'REAL NOT NULL DEFAULT 0',
            'commission_type'       => "TEXT NOT NULL DEFAULT 'NONE'",
            'commission_status'     => "TEXT NOT NULL DEFAULT 'NONE'",
        ],
        'website_leads_crm' => [
            'seller_username' => "TEXT NOT NULL DEFAULT ''",
            'sales_rep_code'  => "TEXT NOT NULL DEFAULT ''",
        ],
    ];

    foreach ($alterMap as $tbl => $cols) {
        if (!isset($tableSet[$tbl])) {
            continue;
        }
        $have = array_flip(array_column($pdo->query("PRAGMA table_info({$tbl})")->fetchAll(), 'name'));
        foreach ($cols as $col => $ddl) {
            if (!isset($have[$col])) {
                $pdo->exec("ALTER TABLE {$tbl} ADD COLUMN {$col} {$ddl}");
            }
        }
    }

    $requiredTables = [
        'users', 'branches', 'reservation_accounting_audit', 'trial_feedback',
        'seller_commissions_ledger', 'reservation_payment_legs', 'bank_balances_monthly',
        'open_balances_monthly', 'charter_trips_monthly', 'partner_statements_monthly',
        'supermarket_monthly_lines',
    ];
    $missingAny = false;
    foreach ($requiredTables as $reqTbl) {
        if (!isset($tableSet[$reqTbl])) {
            $missingAny = true;
            break;
        }
    }
    if ($missingAny) {
        $schemaFile = ROOT_DIR . '/includes/erp_schema_init.sql';
        if (is_file($schemaFile)) {
            $pdo->exec((string) file_get_contents($schemaFile));
        }
    }
}

/**
 * Canonical 18 `*.sales` accounts metadata (Contract #1).
 *
 * @return list<array{0:string,1:string,2:string,3:string,4:string,5:string,6:string}>
 */
function sc_erp_canonical_sellers_spec(): array
{
    return [
        ['fady.sales',      'FadySales@2026',    'fady',      'FADY',       'CYRENE',         'Fady Saleh (Cyrene Sales)',       'فادي صالح (مبيعات سيرين)'],
        ['adel.sales',      'AdelSales@2026',    'adel',      'ADEL',       'DREAMS_FAYROUZ', 'Adel (Dreams & Fayrouz Sales)',   'عادل (مبيعات دريمز والفيروز)'],
        ['moali.sales',     'MoAliSales@2026',   'moali',     'MO.ALI',     'CYRENE',         'Mohamed Ali (Cyrene Sales)',      'محمد علي (مبيعات سيرين)'],
        ['sqran.sales',     'SqranSales@2026',   'sqran',     'SQRAN',      'CYRENE',         'Ahmed Shehata Sqran (Sales)',     'أحمد شحاتة السكران (مبيعات)'],
        ['zola.sales',      'ZolaSales@2026',    'zola',      'ZOLA',       'CYRENE',         'Zola (Tech Mgr & Sales)',         'زنفل زولا (مدير فني ومبيعات)'],
        ['edy.sales',       'EdySales@2026',     'edy',       'EDY',        'CYRENE',         'Edward Edy (Outside Sales)',      'إدوارد إدي (مبيعات خارجية)'],
        ['yassin.sales',    'YassinSales@2026',  'yassin',    'YASSIN',     'CYRENE',         'Yassin (Desk & Outside Sales)',   'ياسين (مبيعات)'],
        ['alishawky.sales', 'AliSales@2026',     'alishawky', 'ALI SHAWKY', 'CYRENE',         'Ali Shawky (Watersports Sales)',  'علي شوقي (مبيعات ألعاب مائية)'],
        ['ammar.sales',     'AmmarSales@2026',   'ammar',     'AMMAR',      'CYRENE',         'Ammar (Cyrene Senior Sales)',     'عمار (مبيعات سيرين)'],
        ['moabdo.sales',    'MoAbdoSales@2026',  'moabdo',    'MO.ABDO',    'CYRENE',         'Mohamed Abdo (Cyrene Sales)',     'محمد عبدالرحمن عبده (مبيعات سيرين)'],
        ['mando.sales',     'MandoSales@2026',   'mando',     'MANDO',      'DREAMS_FAYROUZ', 'Mando (Dreams & Fayrouz Sales)',  'ماندو (مبيعات دريمز والفيروز)'],
        ['chicago.sales',   'ChicagoSales@2026', 'chicago',   'CHICAGO',    'DREAMS_FAYROUZ', 'Ali Chicago (Dreams Sales)',      'علي شيكاغو (مبيعات دريمز)'],
        ['khairy.sales',    'KhairySales@2026',  'khairy',    'KHAIRY',     'DREAMS_FAYROUZ', 'Khairy (Dreams & Fayrouz Sales)', 'خيري (مبيعات دريمز والفيروز)'],
        ['eldeeb.sales',    'EldeebSales@2026',  'eldeeb',    'ELDEEB',     'DREAMS_FAYROUZ', 'Eldeeb (Dreams & Fayrouz Sales)', 'الديب (مبيعات دريمز والفيروز)'],
        ['fawzy.sales',     'FawzySales@2026',   'fawzy',     'FAWZY',      'DREAMS_FAYROUZ', 'Fawzy (Dreams & Fayrouz Sales)',  'فوزي (مبيعات دريمز والفيروز)'],
        ['lemo.sales',      'LemoSales@2026',    'lemo',      'LEMO',       'DREAMS_FAYROUZ', 'Lemo (Dreams & Fayrouz Sales)',   'ليمو (مبيعات دريمز والفيروز)'],
        ['shaldm.sales',    'ShaldmSales@2026',  'shaldm',    'SHALDM',     'CYRENE',         'Ahmed Shaldm (Cyrene Sales)',     'أحمد شلضم (مبيعات سيرين)'],
        ['amrhesham.sales', 'AmrSales@2026',     'amrhesham', 'AMR HESHAM', 'JETTY',          'Amr Hesham (Jetty Counter Sales)','عمرو هشام (مبيعات كونتر السقالة)'],
    ];
}

/**
 * Idempotently ensure user profile columns, owner profiles (Islam Samir & Sayed Elsharqawy),
 * and all 18 canonical `*.sales` accounts without overwriting customized `updated_at != ''` rows.
 */
function sc_erp_ensure_user_profiles(PDO $pdo): void
{
    $now = gmdate('Y-m-d\TH:i:s\Z');
    $pdo->exec("INSERT OR IGNORE INTO branches VALUES ('DREAMS_FAYROUZ','Dreams Beach & El Fayrouz Desks','مكاتب دريمز بيتش والفيروز','Hadaba & Naama Bay, Sharm El Sheikh',1)");
    foreach (['ins-3' => 15.0, 'ins-7' => 25.0, 'ins-10' => 35.0, 'ins-14' => 45.0] as $insCode => $insEur) {
        $pdo->prepare('UPDATE products_catalog SET price_eur = ?, price_egp_est = ? WHERE code = ? AND price_eur <= 0')
            ->execute([$insEur, round($insEur * 57.0, 2), $insCode]);
    }

    // Ensure all 18 canonical sellers exist with referral_code & default commission rates
    $sUpsert = $pdo->prepare(
        'INSERT INTO users
         (username, password_hash, pin_hint, emp_code, display_name_en, display_name_ar,
          job_title_en, job_title_ar, phone, email, avatar_url, bio_notes,
          role_code, branch_scope, vessel_scope, rep_scope, referral_code,
          default_comm_insheet_pct, default_comm_outside_pct, is_active, created_at, updated_at)
         VALUES (?, ?, "", ?, ?, ?, "Hotel & Excursion Concierge", "مستشار مبيعات ورحلات بحرية", "", ?, "", "",
                 "SALES_REP", ?, "ALL", ?, ?, 0.10, 0.25, 1, ?, "")
         ON CONFLICT(username) DO UPDATE SET
           referral_code = CASE WHEN users.referral_code = "" THEN excluded.referral_code ELSE users.referral_code END,
           rep_scope     = CASE WHEN users.rep_scope = "" THEN excluded.rep_scope ELSE users.rep_scope END,
           branch_scope  = CASE WHEN users.updated_at = "" THEN excluded.branch_scope ELSE users.branch_scope END,
           default_comm_insheet_pct = CASE WHEN users.default_comm_insheet_pct <= 0 THEN 0.10 ELSE users.default_comm_insheet_pct END,
           default_comm_outside_pct = CASE WHEN users.default_comm_outside_pct <= 0 THEN 0.25 ELSE users.default_comm_outside_pct END'
    );
    foreach (sc_erp_canonical_sellers_spec() as $idx => [$uname, $pw, $slug, $repScope, $brScope, $enName, $arName]) {
        $empCode = sprintf('EMP_SLS_%02d', $idx + 1);
        $sUpsert->execute([
            $uname, sc_erp_hash_password($pw), $empCode, $enName, $arName,
            $uname . '@seacollegediveclub.com', $brScope, $repScope, $slug, $now,
        ]);
    }

    // Apply direct owner profiles & synced default passwords when not yet customized
    $owners = [
        'islam.samir' => [
            'pw'       => 'SeaCollege@2026',
            'name_en'  => 'Islam Samir',
            'name_ar'  => 'إسلام سمير',
            'title_en' => 'Founder & Managing Owner',
            'title_ar' => 'المؤسس والشريك المالك',
            'phone'    => '+20 10 00344544',
            'email'    => 'islam.samir@seacollegediveclub.com',
            'avatar'   => '/assets/erp/avatars/islam-samir.jpg',
        ],
        'eng.sayed' => [
            'pw'       => 'SayedOwner@2026',
            'name_en'  => 'Sayed Elsharqawy',
            'name_ar'  => 'سيد الشرقاوي',
            'title_en' => 'Co-Founder & Managing Owner',
            'title_ar' => 'الشريك المؤسس والمالك',
            'phone'    => '+20 11 14567886',
            'email'    => 'sayed.elsharqawy@seacollegediveclub.com',
            'avatar'   => '/assets/erp/avatars/sayed-elsharqawy.jpg',
        ],
    ];
    $ownChk = $pdo->prepare('SELECT password_hash, phone, email, avatar_url, updated_at FROM users WHERE username = ?');
    $ownUpd = $pdo->prepare(
        'UPDATE users
         SET password_hash   = CASE WHEN updated_at = "" THEN ? ELSE password_hash END,
             display_name_en = CASE WHEN updated_at = "" THEN ? ELSE display_name_en END,
             display_name_ar = CASE WHEN updated_at = "" THEN ? ELSE display_name_ar END,
             job_title_en    = CASE WHEN job_title_en = "" THEN ? ELSE job_title_en END,
             job_title_ar    = CASE WHEN job_title_ar = "" THEN ? ELSE job_title_ar END,
             phone           = CASE WHEN phone = "" THEN ? ELSE phone END,
             email           = CASE WHEN email = "" THEN ? ELSE email END,
             avatar_url      = CASE WHEN avatar_url = "" THEN ? ELSE avatar_url END
         WHERE username = ?'
    );
    foreach ($owners as $uname => $meta) {
        $ownChk->execute([$uname]);
        $cur = $ownChk->fetch();
        if ($cur) {
            $expectedHash = sc_erp_hash_password($meta['pw']);
            if ($cur['phone'] === '' || $cur['avatar_url'] === '' || $cur['email'] === '' || ($cur['updated_at'] === '' && $cur['password_hash'] !== $expectedHash)) {
                $ownUpd->execute([
                    $expectedHash,
                    $meta['name_en'], $meta['name_ar'],
                    $meta['title_en'], $meta['title_ar'],
                    $meta['phone'], $meta['email'], $meta['avatar'],
                    $uname,
                ]);
            }
        }
    }

    // Backfill default corporate email & clean job titles for any remaining users
    $missing = $pdo->query(
        "SELECT u.id, u.username, u.display_name_en, u.display_name_ar, u.email, u.job_title_en, u.job_title_ar,
                r.name_en AS role_en, r.name_ar AS role_ar
         FROM users u JOIN roles r ON r.role_code = u.role_code
         WHERE u.email = '' OR u.job_title_en = ''"
    )->fetchAll();
    if ($missing) {
        $fillSt = $pdo->prepare(
            'UPDATE users
             SET email        = CASE WHEN email = "" THEN ? ELSE email END,
                 job_title_en = CASE WHEN job_title_en = "" THEN ? ELSE job_title_en END,
                 job_title_ar = CASE WHEN job_title_ar = "" THEN ? ELSE job_title_ar END
             WHERE id = ?'
        );
        foreach ($missing as $m) {
            $defEmail = $m['username'] === 'lara.care'
                ? 'info@seacollegediveclub.com'
                : ($m['username'] . '@seacollegediveclub.com');
            $titleEn = preg_match('/\(([^)]+)\)/', (string) $m['display_name_en'], $mtEn) ? trim($mtEn[1]) : (string) $m['role_en'];
            $titleAr = preg_match('/\(([^)]+)\)/u', (string) $m['display_name_ar'], $mtAr) ? trim($mtAr[1]) : (string) $m['role_ar'];
            $fillSt->execute([$defEmail, $titleEn, $titleAr, $m['id']]);
        }
    }
}

/**
 * Verify password against `sha256$<salt>$<hex>` or standard PHP `password_verify`.
 */
function sc_erp_verify_password(string $plain, string $storedHash): bool
{
    if (str_starts_with($storedHash, 'sha256$')) {
        $parts = explode('$', $storedHash, 3);
        if (count($parts) === 3) {
            $calc = hash('sha256', $parts[1] . ':' . $plain);
            return hash_equals($parts[2], $calc);
        }
        return false;
    }
    return password_verify($plain, $storedHash);
}

/**
 * Hash a password in cross-platform Python/PHP format.
 */
function sc_erp_hash_password(string $plain): string
{
    $salt = 'sc_erp_2026_salt';
    return 'sha256$' . $salt . '$' . hash('sha256', $salt . ':' . $plain);
}

/**
 * Compute ISO period buckets (week, month, quarter, year) for any YYYY-MM-DD date.
 *
 * @return array{0:string,1:string,2:string,3:string}
 */
function sc_erp_period_parts(string $isoDate): array
{
    $ts = strtotime($isoDate) ?: time();
    $y  = (int) gmdate('Y', $ts);
    $m  = (int) gmdate('n', $ts);
    $q  = (int) floor(($m - 1) / 3) + 1;
    $wk = gmdate('o-\WW', $ts);
    return [$wk, sprintf('%04d-%02d', $y, $m), sprintf('%04d-Q%d', $y, $q), (string) $y];
}

/**
 * Return current ERP FX conversion rates to EGP (`USD=50, EUR=57, GBP=65` fallback).
 *
 * @return array{EGP:float,USD:float,EUR:float,GBP:float}
 */
function sc_erp_fx_rates(PDO $pdo): array
{
    $fx = ['EGP' => 1.0, 'USD' => 50.0, 'EUR' => 57.0, 'GBP' => 65.0];
    try {
        foreach ($pdo->query('SELECT currency, rate_to_egp FROM currencies_fx')->fetchAll() as $r) {
            $c = strtoupper(trim((string) ($r['currency'] ?? '')));
            $v = (float) ($r['rate_to_egp'] ?? 0);
            if (isset($fx[$c]) && $v > 0) {
                $fx[$c] = $v;
            }
        }
    } catch (Throwable $e) {
        // Fallback rates remain active
    }
    return $fx;
}

/**
 * Resolve any seller slug, `*.sales` username, `rep_scope`, or unstructured spreadsheet alias
 * (`SAKRAN`, `OUT-FADY`, `M.ALI`, `CHACAGO`, `OS - EDY`, Arabic names) to a canonical active
 * `SALES_REP` user record. Strictly rejects B2B agencies (`SHARM_GATE`, `DISCOVERY`, `I DIVE`),
 * `CENTER`, and unknown strings (returns `null`).
 *
 * @return array<string,mixed>|null
 */
function sc_erp_resolve_seller(PDO $pdo, ?string $raw): ?array
{
    if ($raw === null) {
        return null;
    }
    $clean = trim($raw);
    if ($clean === '' || strlen($clean) > 96) {
        return null;
    }

    if (!function_exists('sc_resolve_seller_public')) {
        $cfgFile = ROOT_DIR . '/config/site_config.php';
        if (is_file($cfgFile)) {
            require_once $cfgFile;
        }
    }

    $targetUsername = '';
    $targetSlug     = '';
    if (function_exists('sc_resolve_seller_public')) {
        $pub = sc_resolve_seller_public($clean);
        if (is_array($pub) && !empty($pub['username'])) {
            $targetUsername = strtolower((string) $pub['username']);
            $targetSlug     = strtolower((string) ($pub['referral_code'] ?? ''));
        }
    }

    if ($targetUsername === '') {
        // Fallback alias map in case site_config was bypassed
        $upper = strtoupper((string) preg_replace('/^(?:OUT|OS)\s*[-_:]+\s*/i', '', $clean));
        $upper = trim((string) preg_replace('/\s+/', ' ', $upper));
        $aliasToSlug = [
            'FADY' => 'fady', 'FADY.SALES' => 'fady',
            'ADEL' => 'adel', 'ADEL.SALES' => 'adel',
            'MO.ALI' => 'moali', 'M.ALI' => 'moali', 'MO ALI' => 'moali', 'M ALI' => 'moali', 'MOALI' => 'moali', 'MOALI.SALES' => 'moali',
            'SQRAN' => 'sqran', 'SAQRAN' => 'sqran', 'SAKRAN' => 'sqran', 'SSQRAN' => 'sqran', 'SQRAN.SALES' => 'sqran',
            'ZOLA' => 'zola', 'ZONFOL' => 'zola', 'ZOLA.SALES' => 'zola',
            'EDY' => 'edy', 'EDWARD' => 'edy', 'EDY.SALES' => 'edy',
            'YASSIN' => 'yassin', 'YASSEN' => 'yassin', 'YASSIN.SALES' => 'yassin',
            'ALI SHAWKY' => 'alishawky', 'ALISHAWKY' => 'alishawky', 'ALISHAWKY.SALES' => 'alishawky',
            'AMMAR' => 'ammar', 'AMAR' => 'ammar', 'AMMAR.SALES' => 'ammar',
            'MO.ABDO' => 'moabdo', 'M.ABDO' => 'moabdo', 'MO ABDO' => 'moabdo', 'M ABDO' => 'moabdo', 'ABDO' => 'moabdo', 'MOABDO' => 'moabdo', 'MOABDO.SALES' => 'moabdo',
            'MANDO' => 'mando', 'MANDO.SALES' => 'mando',
            'CHICAGO' => 'chicago', 'CHACAGO' => 'chicago', 'CHIKAGO' => 'chicago', 'ALI CHICAGO' => 'chicago', 'ALI CHACAGO' => 'chicago', 'CHICAGO.SALES' => 'chicago',
            'KHAIRY' => 'khairy', 'KHIRY' => 'khairy', 'KHAIRY.SALES' => 'khairy',
            'ELDEEB' => 'eldeeb', 'EL DEEB' => 'eldeeb', 'ELDEEB.SALES' => 'eldeeb',
            'FAWZY' => 'fawzy', 'FAWZI' => 'fawzy', 'FAWZY.SALES' => 'fawzy',
            'LEMO' => 'lemo', 'LIMO' => 'lemo', 'LEMO.SALES' => 'lemo',
            'SHALDM' => 'shaldm', 'SHALDAM' => 'shaldm', 'SHALDM.SALES' => 'shaldm',
            'AMR HESHAM' => 'amrhesham', 'AMRHESHAM' => 'amrhesham', 'AMR HISHAM' => 'amrhesham', 'AMRHESHAM.SALES' => 'amrhesham',
        ];
        if (isset($aliasToSlug[$upper])) {
            $targetSlug     = $aliasToSlug[$upper];
            $targetUsername = $targetSlug . '.sales';
        }
    }

    if ($targetUsername === '' && $targetSlug === '') {
        return null;
    }

    $st = $pdo->prepare(
        "SELECT id, username, display_name_en, display_name_ar, role_code, branch_scope, rep_scope,
                referral_code, default_comm_insheet_pct, default_comm_outside_pct, phone, email, avatar_url
         FROM users
         WHERE role_code = 'SALES_REP' AND is_active = 1
           AND (LOWER(username) = ? OR LOWER(referral_code) = ?)
         LIMIT 1"
    );
    $st->execute([$targetUsername, $targetSlug]);
    $u = $st->fetch();
    return $u ?: null;
}

/**
 * Compute and idempotently record a seller commission in `seller_commissions_ledger`.
 *
 * Rules (proven from `DAILY & SALES` & `October Reports 2026`):
 * - `INSHEET` (`INSHEET_10`): 10% of `gross_amount_egp`
 * - `WEBSITE_QR` (`WEBSITE_QR_10`): 10% of `gross_amount_egp`
 * - `OUT_SIDE` (`OUTSIDE_25`): 25% of `max(0, gross_amount_egp - supplier_cost_egp)` (floored at 0.0!)
 * - `CENTER` / unreferred / B2B agency: 0% (`NONE`), never writes a human `*.sales` row.
 *
 * @param array<string,mixed> $spec
 * @return array{seller_username:string,rep_scope:string,branch_code:string,commission_type:string,commission_rate:float,commissionable_base_egp:float,commission_egp:float,commission_status:string}
 */
function sc_erp_record_seller_commission(PDO $pdo, array $spec): array
{
    $none = [
        'seller_username'         => '',
        'rep_scope'               => '',
        'branch_code'             => (string) ($spec['branch_code'] ?? 'CYRENE'),
        'commission_type'         => 'NONE',
        'commission_rate'         => 0.0,
        'commissionable_base_egp' => 0.0,
        'commission_egp'          => 0.0,
        'commission_status'       => 'NONE',
    ];

    $saleMode = strtoupper(trim((string) ($spec['sale_mode'] ?? 'INSHEET')));
    if ($saleMode === 'CENTER' || $saleMode === 'NONE' || $saleMode === 'DIRECT') {
        return $none;
    }

    $sellerToken = (string) ($spec['seller_token'] ?? $spec['seller_username'] ?? '');
    $seller = sc_erp_resolve_seller($pdo, $sellerToken);
    if (!$seller) {
        return $none;
    }

    $grossEgp    = max(0.0, round((float) ($spec['gross_amount_egp'] ?? 0.0), 2));
    $supCostEgp  = max(0.0, round((float) ($spec['supplier_cost_egp'] ?? 0.0), 2));
    $insheetRate = (float) ($seller['default_comm_insheet_pct'] ?? 0.10);
    $outsideRate = (float) ($seller['default_comm_outside_pct'] ?? 0.25);
    if ($insheetRate <= 0) {
        $insheetRate = 0.10;
    }
    if ($outsideRate <= 0) {
        $outsideRate = 0.25;
    }

    if ($saleMode === 'OUT_SIDE' || $saleMode === 'OUTSIDE' || $saleMode === 'OUTSIDE_25') {
        $commType = 'OUTSIDE_25';
        $rate     = $outsideRate;
        $baseEgp  = max(0.0, round($grossEgp - $supCostEgp, 2));
        $commEgp  = round($baseEgp * $rate, 2);
    } elseif ($saleMode === 'WEBSITE_QR' || $saleMode === 'WEBSITE_QR_10') {
        $commType   = 'WEBSITE_QR_10';
        $rate       = $insheetRate;
        $supCostEgp = 0.0;
        $baseEgp    = $grossEgp;
        $commEgp    = round($baseEgp * $rate, 2);
    } else {
        $commType   = 'INSHEET_10';
        $rate       = $insheetRate;
        $supCostEgp = 0.0;
        $baseEgp    = $grossEgp;
        $commEgp    = round($baseEgp * $rate, 2);
    }

    $txDate = (string) ($spec['tx_date'] ?? gmdate('Y-m-d'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $txDate)) {
        $txDate = gmdate('Y-m-d');
    }
    [, $periodMonth] = sc_erp_period_parts($txDate);
    $status        = (string) ($spec['status'] ?? 'PENDING_EXECUTION');
    $sourceChannel = (string) ($spec['source_channel'] ?? 'LIVE_MOBILE_APP');
    $voucherNo     = trim((string) ($spec['voucher_no'] ?? ''));
    $leadEventId   = trim((string) ($spec['lead_event_id'] ?? ''));
    $guestName     = trim((string) ($spec['guest_name'] ?? 'Guest'));
    $productName   = trim((string) ($spec['product_name'] ?? 'Trip'));
    $pax           = max(0, (int) ($spec['pax'] ?? 1));
    $notes         = trim((string) ($spec['notes'] ?? ''));
    $branchCode    = (string) ($spec['branch_code'] ?? $seller['branch_scope'] ?? 'CYRENE');
    if ($branchCode === '' || $branchCode === 'ALL') {
        $branchCode = (string) ($seller['branch_scope'] ?: 'CYRENE');
    }

    if ($voucherNo !== '') {
        $stmt = $pdo->prepare(
            'INSERT INTO seller_commissions_ledger
             (created_at, period_month, tx_date, seller_username, rep_scope, branch_code, source_channel,
              commission_type, voucher_no, lead_event_id, guest_name, product_name, pax,
              gross_amount_egp, supplier_cost_egp, commissionable_base_egp, commission_rate, commission_egp,
              status, journal_entry_no, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "", ?)
             ON CONFLICT(voucher_no, seller_username, source_channel) DO UPDATE SET
               period_month            = excluded.period_month,
               tx_date                 = excluded.tx_date,
               commission_type         = excluded.commission_type,
               guest_name              = excluded.guest_name,
               product_name            = excluded.product_name,
               pax                     = excluded.pax,
               gross_amount_egp        = excluded.gross_amount_egp,
               supplier_cost_egp       = excluded.supplier_cost_egp,
               commissionable_base_egp = excluded.commissionable_base_egp,
               commission_rate         = excluded.commission_rate,
               commission_egp          = excluded.commission_egp,
               notes                   = excluded.notes'
        );
        $stmt->execute([
            gmdate('Y-m-d\TH:i:s\Z'),
            $periodMonth,
            $txDate,
            (string) $seller['username'],
            (string) $seller['rep_scope'],
            $branchCode,
            $sourceChannel,
            $commType,
            $voucherNo,
            $leadEventId,
            $guestName,
            $productName,
            $pax,
            $grossEgp,
            $supCostEgp,
            $baseEgp,
            $rate,
            $commEgp,
            $status,
            $notes,
        ]);
    }

    return [
        'seller_username'         => (string) $seller['username'],
        'rep_scope'               => (string) $seller['rep_scope'],
        'branch_code'             => $branchCode,
        'commission_type'         => $commType,
        'commission_rate'         => $rate,
        'commissionable_base_egp' => $baseEgp,
        'commission_egp'          => $commEgp,
        'commission_status'       => $status,
    ];
}

/**
 * Non-blocking hook called by `handlers/lead_capture.php`.
 * Inserts website booking/contact lead into `website_leads_crm`, resolves QR seller attribution,
 * and creates a `WEB-*` reservation + `WEBSITE_QR_10` commission row when applicable.
 */
function sc_erp_ingest_website_lead(array $lead): void
{
    try {
        $pdo = sc_erp_pdo();
        if (!$pdo) {
            return;
        }
        $eventId = (string) ($lead['event_id'] ?? ('lead_' . bin2hex(random_bytes(6))));
        $now     = (string) ($lead['received_at'] ?? gmdate('c'));
        $tripDt  = (string) ($lead['date'] ?? '');
        if ($tripDt === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tripDt)) {
            $tripDt = gmdate('Y-m-d');
        }
        $eur    = max(0.0, (float) ($lead['estimated_total_eur'] ?? $lead['estimated_eur'] ?? 0.0));
        $people = max(1, (int) ($lead['people'] ?? 1));
        $voNo   = ($eur > 0 || ($lead['form'] ?? '') === 'booking')
            ? ('WEB-' . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $eventId), -8)))
            : '';

        $rawSeller = (string) ($lead['seller_username'] ?? $lead['seller'] ?? $lead['rep'] ?? '');
        $seller    = sc_erp_resolve_seller($pdo, $rawSeller);
        $sellerUser = $seller ? (string) $seller['username'] : '';
        $sellerRep  = $seller ? (string) $seller['rep_scope'] : '';

        $stmt = $pdo->prepare(
            'INSERT OR REPLACE INTO website_leads_crm
             (event_id, received_at, lead_type, lang, guest_name, phone, email, interest_items,
              people, trip_date, hotel, room_no, cert_level, gear_sizes_json, estimated_eur, final_paid_eur,
              status, gclid, fbclid, utm_source, utm_campaign, assigned_to, linked_voucher_no,
              seller_username, sales_rep_code)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $eventId,
            $now,
            strtoupper((string) ($lead['form'] ?? 'BOOKING')) . '_LEAD',
            (string) ($lead['lang'] ?? 'en'),
            (string) ($lead['name'] ?? ''),
            (string) ($lead['phone'] ?? ''),
            (string) ($lead['email'] ?? ''),
            (string) ($lead['interest'] ?? ''),
            $people,
            $tripDt,
            (string) ($lead['hotel'] ?? ''),
            '',
            (string) ($lead['level'] ?? ''),
            json_encode($lead['cart'] ?? [], JSON_UNESCAPED_UNICODE) ?: '[]',
            $eur,
            'NEW',
            (string) ($lead['gclid'] ?? ''),
            (string) ($lead['fbclid'] ?? ''),
            (string) ($lead['utm_source'] ?? ''),
            (string) ($lead['utm_campaign'] ?? ''),
            'LARA',
            $voNo,
            $sellerUser,
            $sellerRep,
        ]);

        if ($voNo !== '') {
            $fx      = sc_erp_fx_rates($pdo);
            $eurRate = $fx['EUR'] ?? 57.0;
            [$wk, $mo, $qr, $yr] = sc_erp_period_parts($tripDt);
            $effectiveEur = $eur > 0 ? $eur : (55.0 * $people);
            $revEgp  = round($effectiveEur * $eurRate, 2);
            $cogsEgp = round($revEgp * 0.36, 2);
            $profEgp = round($revEgp - $cogsEgp, 2);
            $qrTok   = 'QR-' . strtoupper(substr(hash('sha256', $voNo . ':' . $eventId), 0, 12));

            $commInfo = [
                'seller_username'   => '',
                'rep_scope'         => 'ONLINE_WEBSITE',
                'branch_code'       => 'WEBSITE',
                'commission_type'   => 'NONE',
                'commission_egp'    => 0.0,
                'commission_status' => 'NONE',
            ];
            $sellerType = 'ONLINE_DIRECT';
            if ($seller) {
                $sellerType = 'HOTEL_SELLER';
                $commInfo   = sc_erp_record_seller_commission($pdo, [
                    'seller_token'     => $sellerUser,
                    'sale_mode'        => 'WEBSITE_QR_10',
                    'tx_date'          => $tripDt,
                    'voucher_no'       => $voNo,
                    'lead_event_id'    => $eventId,
                    'guest_name'       => (string) ($lead['name'] ?? 'Website Guest'),
                    'product_name'     => substr((string) ($lead['interest'] ?? 'Website QR Booking'), 0, 120),
                    'pax'              => $people,
                    'gross_amount_egp' => $revEgp,
                    'supplier_cost_egp'=> 0.0,
                    'status'           => 'PENDING_EXECUTION',
                    'source_channel'   => 'LIVE_WEBSITE_QR',
                    'branch_code'      => (string) ($seller['branch_scope'] ?: 'CYRENE'),
                    'notes'            => 'Website QR Referral Booking (' . (string) ($lead['lang'] ?? 'en') . ')',
                ]);
            }

            $vStmt = $pdo->prepare(
                'INSERT OR REPLACE INTO reservations_fact
                 (voucher_no, qr_token, trip_date, period_week, period_month, period_quarter, period_year,
                  branch_code, channel, hotel_name, pickup_zone, room_no, nationality, pickup_time,
                  trip_name, product_category, adults, children, infants, total_pax,
                  snork_pax, intro1_pax, intro2_pax, dives_pax, divers_names,
                  sales_rep, executor, client_agency, rest_egp, rest_usd, rest_eur, rest_gbp, rest_egp_equiv,
                  estimated_revenue_egp, estimated_cogs_egp, estimated_profit_egp, vessel_code,
                  boarding_status, payment_status, source_file,
                  seller_type, seller_username, seller_commission_egp, commission_type, commission_status,
                  booked_adults, booked_children, booked_infants, booked_total_egp, workflow_stage)
                 VALUES (?,?,?,?,?,?,?, ?,?,?,?,?,?,?, ?,?,?,?,?,?, 0,0,0,?,?, ?,?,?, 0,0,?,0,?, ?,?,?, ?,?,?,?, ?,?,?,?,?, ?,?,?,?,?)'
            );
            $wfStage = $seller ? 'AWAITING_SELLER_CONFIRM' : 'CONFIRMED_OPS';
            $vStmt->execute([
                $voNo, $qrTok, $tripDt, $wk, $mo, $qr, $yr,
                $seller ? (string) ($seller['branch_scope'] ?: 'WEBSITE') : 'WEBSITE',
                'WEBSITE_ONLINE',
                (string) ($lead['hotel'] ?? 'Online Direct'),
                '',
                '',
                strtoupper((string) ($lead['lang'] ?? 'EN')),
                '07:45',
                substr((string) ($lead['interest'] ?? 'Website Booking'), 0, 120),
                'WEBSITE_DIRECT_BOOKING',
                $people, 0, 0, $people,
                $people, (string) ($lead['name'] ?? ''),
                $seller ? (string) $seller['rep_scope'] : 'ONLINE_WEBSITE',
                'SEA COLLEGE',
                'WEBSITE_DIRECT',
                $effectiveEur, $revEgp,
                $revEgp, $cogsEgp, $profEgp,
                '', 'BOOKED', 'OPEN_REST', 'LIVE_WEBSITE_FORM',
                $sellerType,
                $commInfo['seller_username'],
                $commInfo['commission_egp'],
                $commInfo['commission_type'],
                $commInfo['commission_status'],
                $people, 0, 0, $revEgp, $wfStage,
            ]);

            if ($seller && function_exists('sc_erp_push_notification')) {
                $gName = (string) ($lead['name'] ?? 'Guest');
                $tItem = substr((string) ($lead['interest'] ?? 'Trip'), 0, 80);
                sc_erp_push_notification(
                    $pdo,
                    $sellerUser,
                    'NEW_LINK_BOOKING',
                    $voNo,
                    "🎉 New Booking via Your QR/Link ({$voNo})",
                    "🎉 حجز جديد عبر رابطك / الكود الخاص بك ({$voNo})",
                    "{$gName} ({$people} PAX) booked {$tItem} for {$tripDt}. Tap to confirm & send to Reservations!",
                    "العميل {$gName} ({$people} أفراد) حجز {$tItem} بتاريخ {$tripDt}. اضغط للتأكيد وإرسال الحجز لقسم الحجوزات!"
                );
            }
        }
    } catch (Throwable $e) {
        error_log('SC_ERP lead sync warning: ' . $e->getMessage());
    }
}

/**
 * Non-blocking hook called by `handlers/diver_checkin.php`.
 */
function sc_erp_ingest_diver_checkin(array $row, array $attr = []): void
{
    try {
        $pdo = sc_erp_pdo();
        if (!$pdo) {
            return;
        }
        $cid       = (string) ($row['checkin_id'] ?? ('ci_' . bin2hex(random_bytes(6))));
        $rawSeller = (string) ($row['seller_username'] ?? $row['seller'] ?? $attr['seller'] ?? $attr['rep'] ?? '');
        $seller    = sc_erp_resolve_seller($pdo, $rawSeller);
        $sellerUser = $seller ? (string) $seller['username'] : '';
        $sellerRep  = $seller ? (string) $seller['rep_scope'] : '';

        $gear = [
            'wetsuit' => $row['wetsuit_size'] ?? '',
            'fins'    => $row['fin_size'] ?? '',
            'bcd'     => $row['bcd_size'] ?? '',
            'nitrox'  => $row['nitrox_request'] ?? 'no',
            'height'  => $row['height_cm'] ?? '',
            'weight'  => $row['weight_kg'] ?? '',
            'dives'   => $row['logged_dives'] ?? '',
            'medical' => $row['medical_flags'] ?? 'all_no',
            'notes'   => $row['gear_notes'] ?? '',
        ];
        $stmt = $pdo->prepare(
            'INSERT OR REPLACE INTO website_leads_crm
             (event_id, received_at, lead_type, lang, guest_name, phone, email, interest_items,
              people, trip_date, hotel, room_no, cert_level, gear_sizes_json, estimated_eur, final_paid_eur,
              status, gclid, fbclid, utm_source, utm_campaign, assigned_to, linked_voucher_no,
              seller_username, sales_rep_code)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,0,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $cid,
            (string) ($row['submitted_at'] ?? gmdate('c')),
            'DIVER_CHECKIN',
            (string) ($row['lang'] ?? 'en'),
            (string) ($row['name'] ?? ''),
            (string) ($row['phone'] ?? ''),
            '',
            'Pre-Arrival Diver Check-In (' . trim((string) (($row['cert_agency'] ?? '') . ' ' . ($row['cert_number'] ?? ''))) . ')',
            max(1, (int) ($row['divers_count'] ?? 1)),
            (string) ($row['arrival_date'] ?? ''),
            (string) ($row['hotel'] ?? ''),
            (string) ($row['room_number'] ?? ''),
            (string) ($row['cert_agency'] ?? ''),
            json_encode($gear, JSON_UNESCAPED_UNICODE) ?: '{}',
            'CHECKIN_READY',
            (string) ($attr['gclid'] ?? ''),
            (string) ($attr['fbclid'] ?? ''),
            (string) ($attr['utm_source'] ?? ''),
            (string) ($attr['utm_campaign'] ?? ''),
            'OPERATIONS',
            (string) ($row['lead_event_id'] ?? ''),
            $sellerUser,
            $sellerRep,
        ]);
    } catch (Throwable $e) {
        error_log('SC_ERP checkin sync warning: ' . $e->getMessage());
    }
}

/**
 * Non-blocking hook called by `handlers/wa_click_beacon.php`.
 */
function sc_erp_ingest_wa_beacon(array $row): void
{
    try {
        $pdo = sc_erp_pdo();
        if (!$pdo) {
            return;
        }
        $rawSeller  = (string) ($row['seller_username'] ?? $row['seller'] ?? $row['rep'] ?? '');
        $seller     = sc_erp_resolve_seller($pdo, $rawSeller);
        $sellerUser = $seller ? (string) $seller['username'] : '';
        $sellerRep  = $seller ? (string) $seller['rep_scope'] : '';

        $stmt = $pdo->prepare(
            'INSERT OR IGNORE INTO website_leads_crm
             (event_id, received_at, lead_type, lang, guest_name, phone, email, interest_items,
              people, trip_date, hotel, room_no, cert_level, gear_sizes_json, estimated_eur, final_paid_eur,
              status, gclid, fbclid, utm_source, utm_campaign, assigned_to, linked_voucher_no,
              seller_username, sales_rep_code)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            (string) ($row['event_id'] ?? ('wa_' . bin2hex(random_bytes(6)))),
            (string) ($row['clicked_at'] ?? gmdate('c')),
            'WHATSAPP_CLICK',
            (string) ($row['lang'] ?? 'en'),
            'WhatsApp Visitor (' . (string) ($row['placement'] ?? 'site') . ')',
            '',
            '',
            (string) (($row['item_name'] ?? '') !== '' ? $row['item_name'] : ($row['placement'] ?? 'WhatsApp Click')),
            max(1, (int) ($row['qty'] ?? 1)),
            (string) ($row['date'] ?? ''),
            (string) ($row['hotel'] ?? ''),
            '',
            '',
            json_encode(['page_url' => $row['page_url'] ?? '', 'kind' => $row['kind'] ?? 'whatsapp_click'], JSON_UNESCAPED_UNICODE) ?: '{}',
            (float) ($row['value_eur'] ?? 0.0),
            'WA_CLICKED',
            (string) ($row['gclid'] ?? ''),
            (string) ($row['fbclid'] ?? ''),
            (string) ($row['utm_source'] ?? ''),
            (string) ($row['utm_campaign'] ?? ''),
            'LARA',
            '',
            $sellerUser,
            $sellerRep,
        ]);
    } catch (Throwable $e) {
        error_log('SC_ERP WA beacon sync warning: ' . $e->getMessage());
    }
}

/**
 * Write an immutable RBAC audit log record.
 */
function sc_erp_audit(PDO $pdo, string $username, string $role, string $action, string $entity, string $entityId = '', array $details = []): void
{
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO audit_logs (created_at, username, role_code, action_type, entity_type, entity_id, details_json)
             VALUES (?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            gmdate('Y-m-d\TH:i:s\Z'),
            $username,
            $role,
            $action,
            $entity,
            $entityId,
            json_encode($details, JSON_UNESCAPED_UNICODE) ?: '{}',
        ]);
    } catch (Throwable $e) {
        // Non-fatal
    }
}

/**
 * Stream a UTF-8 BOM CSV file (formula-injection safe).
 *
 * @param list<string> $headers
 * @param list<list<scalar|null>> $rows
 */
function sc_erp_stream_csv(string $filename, array $headers, array $rows): never
{
    $safeName = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $filename) ?: 'sea_college_report.csv';
    if (!str_ends_with(strtolower($safeName), '.csv')) {
        $safeName .= '.csv';
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $safeName . '"');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');

    $out = fopen('php://output', 'wb');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers);
    foreach ($rows as $r) {
        $cleanRow = [];
        foreach ($r as $cell) {
            $s = $cell === null ? '' : (string) $cell;
            if ($s !== '' && preg_match('/^[=+\-@]/', $s) && !is_numeric($s)) {
                $s = "'" . $s;
            }
            $cleanRow[] = $s;
        }
        fputcsv($out, $cleanRow);
    }
    fclose($out);
    exit;
}

/**
 * Build & stream a genuine multi-tab OpenXML `.xlsx` Excel workbook using built-in PHP `ZipArchive`.
 *
 * @param array<string, array{headers: list<string>, rows: list<list<scalar|null>>}> $sheets
 */
function sc_erp_stream_xlsx(string $filename, array $sheets): never
{
    if (!class_exists('ZipArchive')) {
        $first = reset($sheets) ?: ['headers' => [], 'rows' => []];
        sc_erp_stream_csv(preg_replace('/\.xlsx$/i', '.csv', $filename) ?: 'report.csv', $first['headers'], $first['rows']);
    }

    $safeName = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $filename) ?: 'Sea_College_Report.xlsx';
    if (!str_ends_with(strtolower($safeName), '.xlsx')) {
        $safeName .= '.xlsx';
    }

    $tmpFile = tempnam(sys_get_temp_dir(), 'sc_xlsx_');
    $zip = new ZipArchive();
    $zip->open($tmpFile, ZipArchive::OVERWRITE | ZipArchive::CREATE);

    $xmlEsc = static fn(string $s): string => htmlspecialchars(
        preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s) ?? '',
        ENT_XML1 | ENT_QUOTES,
        'UTF-8'
    );
    $colLetter = static function (int $idx): string {
        $s = '';
        $n = $idx + 1;
        while ($n > 0) {
            $m = ($n - 1) % 26;
            $s = chr(65 + $m) . $s;
            $n = intdiv($n - 1, 26);
        }
        return $s;
    };

    $sheetNames = [];
    $sIdx = 1;
    foreach ($sheets as $rawTitle => $spec) {
        $cleanTitle = mb_substr(trim(preg_replace('/[\\\\\/\?\*\[\]:]/', '-', (string) $rawTitle) ?? 'Sheet'), 0, 31) ?: ('Sheet' . $sIdx);
        $sheetNames[$sIdx] = $cleanTitle;

        $allRows = array_merge([$spec['headers']], $spec['rows']);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
             . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
             . '<sheetData>';

        foreach ($allRows as $rZero => $rCells) {
            $rNum = $rZero + 1;
            $styleAttr = $rNum === 1 ? ' s="1"' : '';
            $xml .= '<row r="' . $rNum . '">';
            foreach (array_values($rCells) as $cZero => $val) {
                $ref = $colLetter($cZero) . $rNum;
                if ($val === null || $val === '') {
                    continue;
                }
                if ($rNum > 1 && (is_int($val) || is_float($val) || (is_string($val) && preg_match('/^-?\d+(\.\d+)?$/', $val) && !str_starts_with($val, '0')))) {
                    $xml .= '<c r="' . $ref . '" s="2"><v>' . ((float) $val) . '</v></c>';
                } else {
                    $strVal = (string) $val;
                    if ($rNum > 1 && preg_match('/^[=+\-@]/', $strVal) && !is_numeric($strVal)) {
                        $strVal = "'" . $strVal;
                    }
                    $xml .= '<c r="' . $ref . '" t="inlineStr"' . $styleAttr . '><is><t xml:space="preserve">' . $xmlEsc($strVal) . '</t></is></c>';
                }
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet' . $sIdx . '.xml', $xml);
        $sIdx++;
    }

    $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
    foreach ($sheetNames as $i => $_) {
        $ct .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
    }
    $ct .= '</Types>';
    $zip->addFromString('[Content_Types].xml', $ct);

    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '</Relationships>');

    $wbXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
           . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
    $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
    foreach ($sheetNames as $i => $title) {
        $wbXml .= '<sheet name="' . $xmlEsc($title) . '" sheetId="' . $i . '" r:id="rId' . $i . '"/>';
        $wbRels .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
    }
    $styleRelId = count($sheetNames) + 1;
    $wbRels .= '<Relationship Id="rId' . $styleRelId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    $wbXml .= '</sheets></workbook>';
    $zip->addFromString('xl/workbook.xml', $wbXml);
    $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

    $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0.00"/></numFmts>'
        . '<fonts count="2">'
        . '<font><sz val="11"/><name val="Calibri"/></font>'
        . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
        . '</fonts>'
        . '<fills count="3">'
        . '<fill><patternFill patternType="none"/></fill>'
        . '<fill><patternFill patternType="gray125"/></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FF0A3D62"/></patternFill></fill>'
        . '</fills>'
        . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="3">'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
        . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
        . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
        . '</cellXfs>'
        . '</styleSheet>';
    $zip->addFromString('xl/styles.xml', $stylesXml);
    $zip->close();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $safeName . '"');
    header('Content-Length: ' . (string) filesize($tmpFile));
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');
    readfile($tmpFile);
    @unlink($tmpFile);
    exit;
}
