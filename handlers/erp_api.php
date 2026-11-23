<?php
/**
 * Sea College Dive Club — Role-Based Unified ERP & Mobile App API (`/handlers/erp_api.php`)
 * Enforces strict per-employee RBAC authentication, granular permissions,
 * branch/vessel/sales-rep data scoping, Automated Seller Commission Engine (F8),
 * Monthly Management Reports Pack (F9), Safe Live-Data-Preserving Warehouse Hydration (F11),
 * Multilingual Smart Seller Catalog (F12), and Personal Seller QR & Commission Wallet (F13).
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/lead_lib.php';
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
$rawIn  = $_GET;
if ($method === 'POST') {
    $ctype = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? ''));
    $rawBody = (string) file_get_contents('php://input');
    if (str_contains($ctype, 'application/json')) {
        if (trim($rawBody) === '') {
            $rawIn = $_POST;
        } else {
            $decoded = json_decode($rawBody, true);
            if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Malformed JSON request body']);
                exit;
            }
            $rawIn = $decoded;
        }
    } else {
        $decoded = trim($rawBody) !== '' ? json_decode($rawBody, true) : null;
        $rawIn = is_array($decoded) ? $decoded : $_POST;
    }
}
$action = trim((string) ($rawIn['action'] ?? $_GET['action'] ?? ($method === 'POST' ? '' : 'me')));
if ($action === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing required action parameter']);
    exit;
}

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
    $u['default_comm_insheet_pct'] = (float) ($u['default_comm_insheet_pct'] ?? 0.10);
    $u['default_comm_outside_pct'] = (float) ($u['default_comm_outside_pct'] ?? 0.25);
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

/**
 * Snapshot live Hostinger/pilot rows before replacing SQLite warehouse, then restore into `$impPdo`.
 *
 * @return array<string, list<array<string,mixed>>>
 */
function erp_snapshot_live_state(PDO $pdo): array
{
    $snap = [
        'custom_users'     => [],
        'live_vouchers'    => [],
        'live_crm'         => [],
        'live_commissions' => [],
        'live_feedback'    => [],
        'live_expenses'    => [],
    ];
    try {
        $snap['custom_users'] = $pdo->query("SELECT * FROM users WHERE updated_at != ''")->fetchAll();
    } catch (Throwable $e) {}
    try {
        $snap['live_vouchers'] = $pdo->query("SELECT * FROM reservations_fact WHERE source_file IN ('LIVE_MOBILE_APP', 'LIVE_WEBSITE_FORM')")->fetchAll();
    } catch (Throwable $e) {}
    try {
        $snap['live_crm'] = $pdo->query('SELECT * FROM website_leads_crm')->fetchAll();
    } catch (Throwable $e) {}
    try {
        $snap['live_commissions'] = $pdo->query("SELECT * FROM seller_commissions_ledger WHERE source_channel LIKE 'LIVE_%' OR voucher_no LIKE 'TRIAL-%' OR voucher_no LIKE 'WEB-%'")->fetchAll();
    } catch (Throwable $e) {}
    try {
        $snap['live_feedback'] = $pdo->query('SELECT * FROM trial_feedback')->fetchAll();
    } catch (Throwable $e) {}
    try {
        $snap['live_expenses'] = $pdo->query("SELECT * FROM expense_vouchers_fact WHERE source_file = 'LIVE_MOBILE_SAFE'")->fetchAll();
    } catch (Throwable $e) {}
    return $snap;
}

/**
 * Merge snapshotted live state into newly imported SQLite warehouse.
 *
 * @param array<string, list<array<string,mixed>>> $snap
 */
function erp_restore_live_state(PDO $impPdo, array $snap): void
{
    sc_erp_ensure_schema_and_columns($impPdo);
    sc_erp_ensure_user_profiles($impPdo);

    $insertDynamic = static function (PDO $db, string $table, array $rows, string $conflictClause = 'OR IGNORE', array $skipCols = ['id']): void {
        if (!$rows) {
            return;
        }
        $validCols = array_flip(array_column($db->query("PRAGMA table_info({$table})")->fetchAll(), 'name'));
        foreach ($rows as $row) {
            $cols = [];
            $vals = [];
            foreach ($row as $k => $v) {
                if (in_array($k, $skipCols, true) || !isset($validCols[$k])) {
                    continue;
                }
                $cols[] = $k;
                $vals[] = $v;
            }
            if (!$cols) {
                continue;
            }
            $ph  = implode(',', array_fill(0, count($cols), '?'));
            $sql = "INSERT {$conflictClause} INTO {$table} (" . implode(',', $cols) . ") VALUES ({$ph})";
            $db->prepare($sql)->execute($vals);
        }
    };

    // 1) Restore customized user profiles & newly created users
    foreach ($snap['custom_users'] as $cu) {
        $uname = (string) ($cu['username'] ?? '');
        if ($uname === '') {
            continue;
        }
        $chk = $impPdo->prepare('SELECT id FROM users WHERE LOWER(username) = LOWER(?)');
        $chk->execute([$uname]);
        if ($chk->fetchColumn()) {
            $impPdo->prepare(
                'UPDATE users
                 SET password_hash = ?, display_name_en = ?, display_name_ar = ?,
                     job_title_en = ?, job_title_ar = ?, phone = ?, email = ?,
                     avatar_url = ?, bio_notes = ?, is_active = ?, updated_at = ?
                 WHERE LOWER(username) = LOWER(?)'
            )->execute([
                $cu['password_hash'],
                $cu['display_name_en'],
                $cu['display_name_ar'],
                $cu['job_title_en'] ?? '',
                $cu['job_title_ar'] ?? '',
                $cu['phone'] ?? '',
                $cu['email'] ?? '',
                $cu['avatar_url'] ?? '',
                $cu['bio_notes'] ?? '',
                (int) ($cu['is_active'] ?? 1),
                $cu['updated_at'] ?? gmdate('Y-m-d\TH:i:s\Z'),
                $uname,
            ]);
        } else {
            $insertDynamic($impPdo, 'users', [$cu], 'OR IGNORE');
        }
    }

    // 2) Restore live reservations (deduplicating by voucher_no + source_file)
    foreach ($snap['live_vouchers'] as $lv) {
        $vo = (string) ($lv['voucher_no'] ?? '');
        if ($vo === '') {
            continue;
        }
        $ex = $impPdo->prepare("SELECT COUNT(*) FROM reservations_fact WHERE voucher_no = ? AND source_file IN ('LIVE_MOBILE_APP', 'LIVE_WEBSITE_FORM')");
        $ex->execute([$vo]);
        if ((int) $ex->fetchColumn() === 0) {
            $insertDynamic($impPdo, 'reservations_fact', [$lv], '');
        }
    }

    // 3) Restore live website CRM leads (keyed by UNIQUE event_id)
    $insertDynamic($impPdo, 'website_leads_crm', $snap['live_crm'], 'OR REPLACE');

    // 4) Restore live seller commissions (keyed by UNIQUE(voucher_no, seller_username, source_channel))
    $insertDynamic($impPdo, 'seller_commissions_ledger', $snap['live_commissions'], 'OR REPLACE');

    // 5) Restore live trial_feedback (deduplicated by created_at + username + message)
    foreach ($snap['live_feedback'] as $fb) {
        $exFb = $impPdo->prepare('SELECT COUNT(*) FROM trial_feedback WHERE username = ? AND message = ?');
        $exFb->execute([(string) ($fb['username'] ?? ''), (string) ($fb['message'] ?? '')]);
        if ((int) $exFb->fetchColumn() === 0) {
            $insertDynamic($impPdo, 'trial_feedback', [$fb], '');
        }
    }

    // 6) Restore live safe expenses
    foreach ($snap['live_expenses'] as $le) {
        $exLe = $impPdo->prepare("SELECT COUNT(*) FROM expense_vouchers_fact WHERE voucher_seq = ? AND source_file = 'LIVE_MOBILE_SAFE'");
        $exLe->execute([(string) ($le['voucher_seq'] ?? '')]);
        if ((int) $exLe->fetchColumn() === 0) {
            $insertDynamic($impPdo, 'expense_vouchers_fact', [$le], '');
        }
    }
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
    echo json_encode(['ok' => true, 'user' => $ctx['user'], 'permissions' => $ctx['perms'], 'csrf_token' => csrf_token()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($action === 'logout') {
    unset($_SESSION['sc_erp_user']);
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'avatar') {
    $file = basename((string) ($_GET['file'] ?? ''));
    if (!preg_match('/^[a-z0-9._-]+\.(jpg|jpeg|png|webp)$/i', $file)) {
        http_response_code(404);
        exit;
    }
    $path = dirname(SC_ERP_DB_PATH) . '/avatars/' . $file;
    if (!is_file($path)) {
        http_response_code(404);
        exit;
    }
    $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'png'  => 'image/png',
        'webp' => 'image/webp',
        default => 'image/jpeg',
    };
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=86400');
    header('X-Robots-Tag: noindex, noimageindex');
    readfile($path);
    exit;
}

if ($action === 'directory_roles') {
    // Public metadata of roles, job titles & avatars (strictly zero passwords, phones, emails, or commission balances!)
    $rows = $pdo->query('SELECT u.username, u.display_name_en, u.display_name_ar, u.job_title_en, u.job_title_ar, u.avatar_url, u.role_code, u.branch_scope, u.vessel_scope, r.name_en AS role_en, r.name_ar AS role_ar, r.department
                         FROM users u JOIN roles r ON r.role_code = u.role_code
                         WHERE u.is_active = 1 ORDER BY r.hierarchy_level DESC, u.id ASC')->fetchAll();
    echo json_encode(['ok' => true, 'accounts' => $rows, 'csrf_token' => csrf_token()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$ctx = erp_auth_context($pdo);
if ($action === 'me') {
    if (!$ctx) {
        echo json_encode(['ok' => true, 'authenticated' => false, 'csrf_token' => csrf_token()]);
        exit;
    }
    echo json_encode(['ok' => true, 'authenticated' => true, 'user' => $ctx['user'], 'permissions' => $ctx['perms'], 'csrf_token' => csrf_token()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---------------- MULTILINGUAL SMART SELLER CATALOG (`seller_catalog` — F12) ---------------- */
if ($action === 'seller_catalog') {
    erp_require_perm(
        $ctx,
        'sales.create_voucher',
        'sales.view_own_sales',
        'bi.view_executive',
        'gl.view_all_ledgers',
        'ops.assign_boat_driver',
        'crm.manage_leads_ads'
    );
    $supportedLangs = ['en', 'ru', 'it', 'pl', 'de', 'fr', 'ar', 'zh'];
    $rawLang = strtolower(trim((string) ($_GET['lang'] ?? 'en')));
    $lang = in_array($rawLang, $supportedLangs, true) ? $rawLang : 'en';

    $prevLang = $GLOBALS['SC_LANG'] ?? 'en';
    $GLOBALS['SC_LANG'] = $lang;

    $rawCatalog   = require ROOT_DIR . '/data/catalog.php';
    $galleries    = is_file(ROOT_DIR . '/data/item_galleries.php') ? (require ROOT_DIR . '/data/item_galleries.php') : [];
    $seoExtras    = is_file(ROOT_DIR . '/data/items_seo.php') ? (require ROOT_DIR . '/data/items_seo.php') : [];
    $opSkusRaw    = is_file(ROOT_DIR . '/data/operational_skus.php') ? (require ROOT_DIR . '/data/operational_skus.php') : [];
    $hotelData    = is_file(ROOT_DIR . '/data/hotel_zones.php') ? (require ROOT_DIR . '/data/hotel_zones.php') : ['zones' => [], 'hotels' => []];
    $GLOBALS['SC_LANG'] = $prevLang;

    $fx = sc_erp_fx_rates($pdo);
    $eurToEgp = $fx['EUR'] ?? 57.0;
    $usdToEgp = $fx['USD'] ?? 50.0;
    $gbpToEgp = $fx['GBP'] ?? 65.0;

    $resolveAssetWebp = static function (string $relPath): string {
        if ($relPath === '') {
            return '';
        }
        $p = '/' . ltrim($relPath, '/');
        if (!str_starts_with($p, '/assets/')) {
            $p = '/assets/' . ltrim($p, '/');
        }
        $webp = (string) preg_replace('/\.(jpe?g|png)$/i', '.webp', $p);
        if ($webp !== $p && is_file(ROOT_DIR . $webp)) {
            return $webp;
        }
        return $p;
    };

    $items = [];
    foreach ($rawCatalog as $catKey => $catMeta) {
        $catLabel = is_array($catMeta['label'] ?? null)
            ? ($catMeta['label'][$lang] ?? $catMeta['label']['en'] ?? $catKey)
            : (string) ($catMeta['label'] ?? $catKey);
        foreach (($catMeta['groups'] ?? []) as $grp) {
            $grpTitle = is_array($grp['title'] ?? null)
                ? ($grp['title'][$lang] ?? $grp['title']['en'] ?? '')
                : (string) ($grp['title'] ?? '');
            foreach (($grp['items'] ?? []) as $it) {
                $id = (string) ($it['id'] ?? '');
                if ($id === '' || ($it['price'] ?? null) === null) {
                    continue;
                }
                $priceEur = (float) $it['price'];
                $gal      = $galleries[$id] ?? [];
                $seo      = $seoExtras[$id] ?? [];
                $coverImg = $resolveAssetWebp((string) ($it['img'] ?? ''));
                $photos   = [];
                if ($coverImg !== '') {
                    $photos[] = $coverImg;
                }
                foreach (($gal['photos'] ?? []) as $gp) {
                    $wp = $resolveAssetWebp((string) $gp);
                    if ($wp !== '' && !in_array($wp, $photos, true)) {
                        $photos[] = $wp;
                    }
                }
                $vidSrc    = !empty($gal['video']['src']) ? ('/assets/' . ltrim((string) $gal['video']['src'], '/')) : '/assets/videos/sea-college-sharm-red-sea-overview.mp4';
                $vidPoster = !empty($gal['video']['poster']) ? $resolveAssetWebp((string) $gal['video']['poster']) : '/assets/images/items/gallery/sea-college-sharm-video-overview-poster.webp';

                $nameLoc = $lang === 'ar' && !empty($it['name_ar']) ? (string) $it['name_ar'] : (string) ($it['name'] ?? $id);
                $descLoc = $lang === 'ar' && !empty($it['desc_ar']) ? (string) $it['desc_ar'] : (string) ($it['desc'] ?? '');

                $section  = (string) ($it['section'] ?? 'tours');
                $slug     = (string) ($it['slug'] ?? $id);
                $langPref = $lang === 'en' ? '' : ('/' . $lang);
                $publicUrl = 'https://seacollegediveclub.com' . $langPref . '/' . $section . '/' . $slug;

                $specs = $seo['specs'] ?? [];
                $items[] = [
                    'id'                        => $id,
                    'code'                      => $id,
                    'section'                   => $section,
                    'slug'                      => $slug,
                    'category_key'              => $catKey,
                    'category_label'            => $catLabel,
                    'group_title'               => $grpTitle,
                    'name'                      => $nameLoc,
                    'name_en'                   => (string) ($it['name'] ?? $id),
                    'name_ar'                   => (string) ($it['name_ar'] ?? $nameLoc),
                    'summary'                   => $descLoc,
                    'duration'                  => (string) ($it['duration'] ?? ($specs['duration'] ?? 'Full day (08:00–16:30)')),
                    'popular'                   => !empty($it['popular']),
                    'price_eur'                 => $priceEur,
                    'price_usd'                 => round(($priceEur * $eurToEgp) / $usdToEgp, 2),
                    'price_gbp'                 => round(($priceEur * $eurToEgp) / $gbpToEgp, 2),
                    'price_egp'                 => round($priceEur * $eurToEgp, 0),
                    'default_sale_mode'         => 'INSHEET',
                    'default_comm_pct'          => 0.10,
                    'default_supplier_cost_egp' => 0.0,
                    'cover_webp'                => $coverImg,
                    'photos'                    => array_slice($photos, 0, 5),
                    'video_src'                 => $vidSrc,
                    'video_poster'              => $vidPoster,
                    'public_url'                => $publicUrl,
                    'infographics'              => [
                        'min_age'       => (string) ($specs['min_age'] ?? (str_contains($catKey, 'snork') ? 'All Ages (4+)' : '10+ Years')),
                        'max_depth'     => (string) ($specs['max_depth'] ?? (str_contains($id, 'owd') ? '18 m' : (str_contains($id, 'aowd') ? '30 m' : (str_contains($id, 'dsd') ? '6–12 m (1:1 Pro)' : 'Surface / 18–30 m')))),
                        'certification' => (string) ($specs['prerequisites'] ?? (str_contains($id, 'dive-') ? 'Certified Diver' : 'No Experience Needed')),
                        'pickup'        => 'Free Hotel Transfer (All 6 Sharm Zones)',
                        'included'      => is_array($catMeta['notes'][$lang] ?? null) ? array_slice($catMeta['notes'][$lang], 0, 2) : ['PADI Instructor / Guide, Hotel Transfers & Briefing'],
                    ],
                ];
            }
        }
    }

    $opSkusOut = [];
    foreach ($opSkusRaw as $sku) {
        $pEur = (float) ($sku['price_eur'] ?? 0);
        $opSkusOut[] = [
            'id'                        => (string) $sku['id'],
            'code'                      => (string) ($sku['code'] ?? $sku['id']),
            'category_group'            => (string) ($sku['category_group'] ?? 'companion-upsells'),
            'category_label'            => (string) ($sku['category_labels'][$lang] ?? $sku['category_labels']['en'] ?? 'Operational Add-On'),
            'name'                      => (string) ($sku['names'][$lang] ?? $sku['names']['en'] ?? $sku['id']),
            'name_en'                   => (string) ($sku['names']['en'] ?? $sku['id']),
            'name_ar'                   => (string) ($sku['names']['ar'] ?? $sku['names']['en'] ?? $sku['id']),
            'summary'                   => (string) ($sku['summaries'][$lang] ?? $sku['summaries']['en'] ?? ''),
            'duration'                  => (string) ($sku['duration'] ?? 'Flexible'),
            'popular'                   => !empty($sku['popular']),
            'price_eur'                 => $pEur,
            'price_usd'                 => round(($pEur * $eurToEgp) / $usdToEgp, 2),
            'price_gbp'                 => round(($pEur * $eurToEgp) / $gbpToEgp, 2),
            'price_egp'                 => round($pEur * $eurToEgp, 0),
            'default_sale_mode'         => (string) ($sku['default_sale_mode'] ?? 'INSHEET'),
            'default_comm_pct'          => (float) ($sku['default_comm_pct'] ?? 0.10),
            'default_supplier_cost_egp' => (float) ($sku['default_supplier_cost_egp'] ?? 0.0),
            'cover_webp'                => (string) ($sku['cover_webp'] ?? ''),
            'photos'                    => $sku['gallery_photos'] ?? [$sku['cover_webp'] ?? ''],
            'video_src'                 => (string) ($sku['video_src'] ?? '/assets/videos/sea-college-tiran-dive-briefing-sharm.mp4'),
            'video_poster'              => (string) ($sku['video_poster'] ?? '/assets/images/gallery/sea-college-tiran-dive-briefing-poster.webp'),
            'public_url'                => 'https://seacollegediveclub.com' . ($lang === 'en' ? '' : '/' . $lang) . '/prices',
            'infographics'              => [
                'min_age'       => (string) ($sku['specs']['min_age'] ?? '8+'),
                'max_depth'     => (string) ($sku['specs']['max_depth'] ?? 'Surface / Reef'),
                'certification' => (string) ($sku['specs']['prerequisites'] ?? 'None'),
                'pickup'        => (string) ($sku['specs']['pickup'] ?? 'Included'),
                'included'      => $sku['included'][$lang] ?? $sku['included']['en'] ?? [],
            ],
        ];
    }

    $zonesMap = $hotelData['zones'] ?? [];
    $zonesOut = [];
    foreach ($zonesMap as $zKey => $zMeta) {
        $zonesOut[$zKey] = [
            'code'          => $zKey,
            'pickup_window' => (string) ($zMeta['pickup'] ?? '08:00'),
            'label'         => (string) ($zMeta['label'][$lang] ?? $zMeta['label']['en'] ?? $zKey),
            'drive_time'    => (string) ($zMeta['drive'][$lang] ?? $zMeta['drive']['en'] ?? ''),
        ];
    }

    $hotelsOut = [];
    foreach (($hotelData['hotels'] ?? []) as $h) {
        $zCode = (string) ($h['zone'] ?? 'montazah');
        $zInfo = $zonesOut[$zCode] ?? ['pickup_window' => '08:00', 'label' => 'Sharm El Sheikh', 'drive_time' => '10 min'];
        $hotelsOut[] = [
            'name'          => (string) $h['name'],
            'zone_code'     => $zCode,
            'zone_label'    => $zInfo['label'],
            'pickup_window' => $zInfo['pickup_window'],
            'drive_time'    => $zInfo['drive_time'],
            'onsite'        => !empty($h['onsite']),
        ];
    }

    echo json_encode([
        'ok'               => true,
        'lang'             => $lang,
        'languages'        => $supportedLangs,
        'site_url'         => 'https://seacollegediveclub.com',
        'fx_rates'         => $fx,
        'items'            => $items,
        'operational_skus' => $opSkusOut,
        'zones'            => array_values($zonesOut),
        'hotels'           => $hotelsOut,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---------------- PERSONAL SELLER QR & COMMISSION WALLET (`seller_wallet` — F13) ---------------- */
if ($action === 'seller_wallet') {
    erp_require_perm($ctx, 'sales.view_own_sales', 'bi.view_executive', 'gl.view_all_ledgers', 'hr.manage_payroll');
    $meUser  = $ctx['user'];
    $canInspectAll = in_array('bi.view_executive', $ctx['perms'], true)
        || in_array('gl.view_all_ledgers', $ctx['perms'], true)
        || in_array('hr.manage_payroll', $ctx['perms'], true);

    $reqSellerRaw = trim((string) ($_GET['seller'] ?? ''));
    if (!$canInspectAll) {
        if ($reqSellerRaw !== '') {
            $resolvedReq = sc_erp_resolve_seller($pdo, $reqSellerRaw);
            if (!$resolvedReq || strtolower((string) $resolvedReq['username']) !== strtolower((string) $meUser['username'])) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Sales representatives may only access their own commission wallet']);
                exit;
            }
        }
        $targetSeller = sc_erp_resolve_seller($pdo, (string) $meUser['username']) ?: $meUser;
    } else {
        if ($reqSellerRaw !== '') {
            $targetSeller = sc_erp_resolve_seller($pdo, $reqSellerRaw);
            if (!$targetSeller) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => 'Seller account not found']);
                exit;
            }
        } else {
            $targetSeller = $meUser['role_code'] === 'SALES_REP'
                ? (sc_erp_resolve_seller($pdo, (string) $meUser['username']) ?: $meUser)
                : (sc_erp_resolve_seller($pdo, 'fady.sales') ?: $meUser);
        }
    }

    $rawMonth = strtoupper(trim((string) ($_GET['month'] ?? 'ALL')));
    $monthFilter = preg_match('/^\d{4}-\d{2}$/', $rawMonth) ? $rawMonth : 'ALL';

    $sUser = (string) $targetSeller['username'];
    $sSlug = (string) ($targetSeller['referral_code'] ?: preg_replace('/\.sales$/i', '', $sUser));
    $sRep  = (string) ($targetSeller['rep_scope'] ?? strtoupper($sSlug));
    $qrUrl = 'https://seacollegediveclub.com/r/' . rawurlencode($sSlug);

    $langLinks = [];
    foreach (['en', 'ru', 'it', 'pl', 'de', 'fr', 'ar', 'zh'] as $lg) {
        $langLinks[$lg] = $lg === 'en'
            ? $qrUrl
            : ('https://seacollegediveclub.com/' . $lg . '/r/' . rawurlencode($sSlug));
    }

    $wParams = [$sUser];
    $wMonthSql = '';
    if ($monthFilter !== 'ALL') {
        $wMonthSql = ' AND period_month = ?';
        $wParams[] = $monthFilter;
    }

    $ledSt = $pdo->prepare(
        "SELECT * FROM seller_commissions_ledger
         WHERE LOWER(seller_username) = LOWER(?) {$wMonthSql}
         ORDER BY tx_date DESC, id DESC LIMIT 400"
    );
    $ledSt->execute($wParams);
    $ledgerRows = $ledSt->fetchAll();

    $kpiSt = $pdo->prepare(
        "SELECT COUNT(*) AS entries_count,
                ROUND(COALESCE(SUM(CASE WHEN status != 'REVERSED_CANCELLED' THEN gross_amount_egp ELSE 0 END), 0), 2) AS gross_sales_egp,
                ROUND(COALESCE(SUM(CASE WHEN status != 'REVERSED_CANCELLED' THEN commission_egp ELSE 0 END), 0), 2) AS total_commission_egp,
                ROUND(COALESCE(SUM(CASE WHEN status = 'PENDING_EXECUTION' THEN commission_egp ELSE 0 END), 0), 2) AS pending_commission_egp,
                ROUND(COALESCE(SUM(CASE WHEN status = 'EARNED_UNPAID' THEN commission_egp ELSE 0 END), 0), 2) AS earned_commission_egp,
                ROUND(COALESCE(SUM(CASE WHEN status = 'PAID_SETTLED' THEN commission_egp ELSE 0 END), 0), 2) AS paid_commission_egp,
                ROUND(COALESCE(SUM(CASE WHEN status = 'REVERSED_CANCELLED' THEN commission_egp ELSE 0 END), 0), 2) AS reversed_commission_egp
         FROM seller_commissions_ledger
         WHERE LOWER(seller_username) = LOWER(?) {$wMonthSql}"
    );
    $kpiSt->execute($wParams);
    $kpis = $kpiSt->fetch() ?: [];

    // Look up payroll advances/deductions & base salary for this seller when viewing a specific month (e.g. 2026-10)
    $payrollBase = 0.0;
    $payrollDed  = 0.0;
    $payrollNameMap = [
        'fady.sales'      => '%فادى%',
        'sqran.sales'     => '%السكران%',
        'moabdo.sales'    => '%محمد عبدالرحمن%',
        'ammar.sales'     => '%عمار%',
        'moali.sales'     => '%محمد على%',
        'zola.sales'      => '%زنفل%',
        'amrhesham.sales' => '%عمرو هشام%',
    ];
    if (isset($payrollNameMap[$sUser])) {
        $pSql = 'SELECT COALESCE(SUM(actual_wage_egp + allowances_egp + bonuses_egp),0) AS base_egp, COALESCE(SUM(advances_deductions_egp),0) AS ded_egp FROM payroll_monthly_fact WHERE emp_name_ar LIKE ?';
        $pArgs = [$payrollNameMap[$sUser]];
        if ($monthFilter !== 'ALL') {
            $pSql .= ' AND period_month = ?';
            $pArgs[] = $monthFilter;
        }
        $prSt = $pdo->prepare($pSql);
        $prSt->execute($pArgs);
        $prRow = $prSt->fetch();
        if ($prRow) {
            $payrollBase = round((float) $prRow['base_egp'], 2);
            $payrollDed  = round((float) $prRow['ded_egp'], 2);
        }
    }

    $totalComm = round((float) ($kpis['total_commission_egp'] ?? 0.0), 2);
    $kpis['total_commission_rounded_egp'] = round($totalComm, 0);
    $kpis['payroll_base_egp']             = $payrollBase;
    $kpis['payroll_deductions_egp']       = $payrollDed;
    $kpis['net_balance_egp']              = round($payrollBase + $totalComm - $payrollDed, 2);

    $crmParams = [$sUser, $sRep];
    $crmMonthSql = '';
    if ($monthFilter !== 'ALL') {
        $crmMonthSql = ' AND (SUBSTR(trip_date, 1, 7) = ? OR SUBSTR(received_at, 1, 7) = ?)';
        $crmParams[] = $monthFilter;
        $crmParams[] = $monthFilter;
    }
    $crmSt = $pdo->prepare(
        "SELECT event_id, received_at, lead_type, lang, guest_name, phone, interest_items, people, trip_date, hotel, estimated_eur, status, linked_voucher_no
         FROM website_leads_crm
         WHERE (LOWER(seller_username) = LOWER(?) OR (sales_rep_code != '' AND UPPER(sales_rep_code) = UPPER(?))) {$crmMonthSql}
         ORDER BY received_at DESC LIMIT 150"
    );
    $crmSt->execute($crmParams);
    $attributedLeads = $crmSt->fetchAll();
    $kpis['attributed_leads_count'] = count($attributedLeads);

    $allSellers = [];
    if ($canInspectAll) {
        $allSellers = $pdo->query(
            "SELECT username, display_name_en, display_name_ar, rep_scope, branch_scope, referral_code
             FROM users WHERE role_code = 'SALES_REP' AND is_active = 1 ORDER BY id ASC"
        )->fetchAll();
    }

    echo json_encode([
        'ok'               => true,
        'month'            => $monthFilter,
        'seller_profile'   => [
            'username'                 => $sUser,
            'display_name_en'          => (string) ($targetSeller['display_name_en'] ?? $sUser),
            'display_name_ar'          => (string) ($targetSeller['display_name_ar'] ?? $sUser),
            'rep_scope'                => $sRep,
            'branch_scope'             => (string) ($targetSeller['branch_scope'] ?? 'CYRENE'),
            'referral_code'            => $sSlug,
            'qr_url'                   => $qrUrl,
            'referral_url'             => $qrUrl,
            'localized_urls'           => $langLinks,
            'default_comm_insheet_pct' => (float) ($targetSeller['default_comm_insheet_pct'] ?? 0.10),
            'default_comm_outside_pct' => (float) ($targetSeller['default_comm_outside_pct'] ?? 0.25),
            'phone'                    => (string) ($targetSeller['phone'] ?? ''),
            'email'                    => (string) ($targetSeller['email'] ?? ''),
            'avatar_url'               => (string) ($targetSeller['avatar_url'] ?? ''),
        ],
        'kpis'             => $kpis,
        'ledger_rows'      => $ledgerRows,
        'attributed_leads' => $attributedLeads,
        'all_sellers'      => $allSellers,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---------------- MONTHLY MANAGEMENT REPORTS PACK (`monthly_report_pack` — F9) ---------------- */
if ($action === 'monthly_report_pack') {
    erp_require_perm($ctx, 'bi.view_executive', 'gl.view_all_ledgers');
    $rawMonth = trim((string) ($_GET['month'] ?? '2026-10'));
    $month = preg_match('/^\d{4}-\d{2}$/', $rawMonth) ? $rawMonth : '2026-10';
    $year  = substr($month, 0, 4);

    $availMonths = array_values(array_unique(array_merge(
        ['2026-10'],
        array_column($pdo->query('SELECT DISTINCT period_month FROM pnl_monthly_lines ORDER BY period_month DESC')->fetchAll(), 'period_month')
    )));
    rsort($availMonths);

    // 1) FX Rates for requested month
    $fxSt = $pdo->prepare('SELECT ROUND(AVG(fx_usd),2) AS usd, ROUND(AVG(fx_eur),2) AS eur, ROUND(AVG(fx_gbp),2) AS gbp FROM daily_revenue_fact WHERE period_month = ?');
    $fxSt->execute([$month]);
    $fxRow = $fxSt->fetch();
    $defFx = sc_erp_fx_rates($pdo);
    $fxRates = [
        'EGP' => 1.0,
        'USD' => ($fxRow && (float) $fxRow['usd'] > 0) ? (float) $fxRow['usd'] : $defFx['USD'],
        'EUR' => ($fxRow && (float) $fxRow['eur'] > 0) ? (float) $fxRow['eur'] : $defFx['EUR'],
        'GBP' => ($fxRow && (float) $fxRow['gbp'] > 0) ? (float) $fxRow['gbp'] : $defFx['GBP'],
    ];

    // Schedule 1: Daily Cash Revenue
    $dcrSt = $pdo->prepare('SELECT * FROM daily_revenue_fact WHERE period_month = ? ORDER BY rev_date ASC');
    $dcrSt->execute([$month]);
    $dcrRows = $dcrSt->fetchAll();
    $cyrCash = 0.0;
    $jetCash = 0.0;
    $combCash = 0.0;
    $combVisa = 0.0;
    foreach ($dcrRows as $r) {
        $cyrCash  += (float) $r['cyrene_total_egp'];
        $jetCash  += (float) $r['jetty_total_egp'];
        $combCash += (float) $r['combined_total_egp'];
        $combVisa += (float) $r['cyrene_visa'] + (float) $r['jetty_visa'];
    }
    $daysN = max(1, count($dcrRows));

    // Schedule 2: Seller Commissions
    $scByRepSt = $pdo->prepare(
        "SELECT seller_username, rep_scope, branch_code,
                ROUND(SUM(CASE WHEN commission_type='INSHEET_10' THEN gross_amount_egp ELSE 0 END), 2) AS insheet_sales_egp,
                ROUND(SUM(CASE WHEN commission_type='INSHEET_10' THEN commission_egp ELSE 0 END), 2) AS insheet_comm_egp,
                ROUND(SUM(CASE WHEN commission_type='OUTSIDE_25' THEN gross_amount_egp ELSE 0 END), 2) AS outside_sales_egp,
                ROUND(SUM(CASE WHEN commission_type='OUTSIDE_25' THEN supplier_cost_egp ELSE 0 END), 2) AS outside_cost_egp,
                ROUND(SUM(CASE WHEN commission_type='OUTSIDE_25' THEN commission_egp ELSE 0 END), 2) AS outside_comm_egp,
                ROUND(SUM(CASE WHEN commission_type='WEBSITE_QR_10' THEN gross_amount_egp ELSE 0 END), 2) AS qr_sales_egp,
                ROUND(SUM(CASE WHEN commission_type='WEBSITE_QR_10' THEN commission_egp ELSE 0 END), 2) AS qr_comm_egp,
                ROUND(SUM(gross_amount_egp), 2) AS total_sales_egp,
                ROUND(SUM(commission_egp), 2) AS total_comm_egp,
                ROUND(SUM(commission_egp), 0) AS total_comm_rounded_egp
         FROM seller_commissions_ledger
         WHERE period_month = ? AND status != 'REVERSED_CANCELLED'
         GROUP BY seller_username, rep_scope, branch_code
         ORDER BY branch_code ASC, total_comm_egp DESC"
    );
    $scByRepSt->execute([$month]);
    $scSummaryRows = $scByRepSt->fetchAll();

    $cyrScTotSt = $pdo->prepare(
        "SELECT ROUND(COALESCE(SUM(gross_amount_egp),0), 2) AS cyrene_sales_egp,
                ROUND(COALESCE(SUM(commission_egp),0), 2) AS cyrene_comm_exact_egp,
                ROUND(COALESCE(SUM(commission_egp),0), 0) AS cyrene_comm_rounded_egp
         FROM seller_commissions_ledger
         WHERE period_month = ? AND branch_code = 'CYRENE' AND status != 'REVERSED_CANCELLED' AND source_channel NOT LIKE 'LIVE_%'"
    );
    $cyrScTotSt->execute([$month]);
    $cyrScTotals = $cyrScTotSt->fetch() ?: ['cyrene_sales_egp' => 0.0, 'cyrene_comm_exact_egp' => 0.0, 'cyrene_comm_rounded_egp' => 0.0];

    // Schedule 3, 4, 5: Branch & Consolidated P&L (with explicit inter-branch elimination!)
    $pnlSt = $pdo->prepare('SELECT id, branch_code, section_type, line_name_ar, line_name_en, trip_or_cost_group, amount_egp, cost_ratio, cost_pct_of_rev, is_interbranch FROM pnl_monthly_lines WHERE period_month = ? ORDER BY branch_code ASC, id ASC');
    $pnlSt->execute([$month]);
    $allPnlRows = $pnlSt->fetchAll();

    $buildBranchPnl = static function (array $rows, string $branch): array {
        $bRows = [];
        $sums  = ['CASH_REV' => 0.0, 'ACCRUED_REV' => 0.0, 'TOTAL_REV' => 0.0, 'CASH_EXP' => 0.0, 'ACCRUED_EXP' => 0.0, 'TOTAL_EXP' => 0.0, 'NET_PROFIT' => 0.0];
        $ibRev = 0.0;
        $ibExp = 0.0;
        foreach ($rows as $r) {
            if ((string) $r['branch_code'] !== $branch) {
                continue;
            }
            $sec = (string) $r['section_type'];
            $amt = is_numeric($r['amount_egp']) ? (float) $r['amount_egp'] : 0.0;
            if (!is_finite($amt)) {
                $amt = 0.0;
            }
            if (isset($sums[$sec])) {
                $sums[$sec] = round($sums[$sec] + $amt, 2);
            }
            if ((int) ($r['is_interbranch'] ?? 0) === 1) {
                if (in_array($sec, ['CASH_REV', 'ACCRUED_REV'], true)) {
                    $ibRev += $amt;
                } elseif (in_array($sec, ['CASH_EXP', 'ACCRUED_EXP'], true)) {
                    $ibExp += $amt;
                }
            }
            $cpct = ($r['cost_pct_of_rev'] !== null && is_numeric($r['cost_pct_of_rev']) && is_finite((float) $r['cost_pct_of_rev']))
                ? round((float) $r['cost_pct_of_rev'], 4)
                : null;
            $bRows[] = [
                'section_type'      => $sec,
                'line_name_ar'      => (string) $r['line_name_ar'],
                'line_name_en'      => (string) $r['line_name_en'],
                'trip_or_cost_group'=> (string) $r['trip_or_cost_group'],
                'amount_egp'        => round($amt, 2),
                'cost_pct_of_rev'   => $cpct,
                'is_interbranch'    => (int) ($r['is_interbranch'] ?? 0),
            ];
        }
        return [
            'branch_code' => $branch,
            'totals'      => [
                'cash_rev_egp'            => round($sums['CASH_REV'], 2),
                'accrued_rev_egp'         => round($sums['ACCRUED_REV'], 2),
                'total_rev_egp'           => round($sums['TOTAL_REV'], 2),
                'cash_exp_egp'            => round($sums['CASH_EXP'], 2),
                'accrued_exp_egp'         => round($sums['ACCRUED_EXP'], 2),
                'total_exp_egp'           => round($sums['TOTAL_EXP'], 2),
                'net_profit_egp'          => round($sums['NET_PROFIT'], 2),
                'interbranch_rev_egp'     => round($ibRev, 2),
                'interbranch_exp_egp'     => round($ibExp, 2),
            ],
            'rows'        => $bRows,
        ];
    };

    $pnlCyrene = $buildBranchPnl($allPnlRows, 'CYRENE');
    $pnlJetty  = $buildBranchPnl($allPnlRows, 'JETTY');
    $pnlJaz    = $buildBranchPnl($allPnlRows, 'JAZ');

    $grossBranchRev = round($pnlCyrene['totals']['total_rev_egp'] + $pnlJetty['totals']['total_rev_egp'] + $pnlJaz['totals']['total_rev_egp'], 2);
    $grossBranchExp = round($pnlCyrene['totals']['total_exp_egp'] + $pnlJetty['totals']['total_exp_egp'] + $pnlJaz['totals']['total_exp_egp'], 2);
    $ibElimRev      = round($pnlCyrene['totals']['interbranch_rev_egp'] + $pnlJetty['totals']['interbranch_rev_egp'], 2);
    $ibElimExp      = round($pnlCyrene['totals']['interbranch_exp_egp'] + $pnlJetty['totals']['interbranch_exp_egp'], 2);
    $extRev         = round($grossBranchRev - $ibElimRev, 2);
    $extExp         = round($grossBranchExp - $ibElimExp, 2);
    $consolNet      = round($pnlCyrene['totals']['net_profit_egp'] + $pnlJetty['totals']['net_profit_egp'] + $pnlJaz['totals']['net_profit_egp'], 2);

    // Schedule 6: Treasury Cashbox
    $cbSt = $pdo->prepare('SELECT * FROM cashbox_daily_fact WHERE period_month = ? ORDER BY tx_date ASC, id ASC');
    $cbSt->execute([$month]);
    $cbRows = $cbSt->fetchAll();
    $cbLast = $cbRows ? $cbRows[count($cbRows) - 1] : null;
    $cbSums = ['inc_le' => 0.0, 'inc_usd' => 0.0, 'inc_eur' => 0.0, 'inc_gbp' => 0.0, 'inc_visa' => 0.0, 'exp_le' => 0.0, 'exp_usd' => 0.0, 'exp_eur' => 0.0, 'exp_gbp' => 0.0];
    foreach ($cbRows as $cr) {
        foreach ($cbSums as $k => $v) {
            $cbSums[$k] = round($v + (float) ($cr[$k] ?? 0), 2);
        }
    }
    $closingLe  = $cbLast ? round((float) $cbLast['bal_le'], 2) : 0.0;
    $closingUsd = $cbLast ? round((float) $cbLast['bal_usd'], 2) : 0.0;
    $closingEur = $cbLast ? round((float) $cbLast['bal_eur'], 2) : 0.0;
    $closingGbp = $cbLast ? round((float) $cbLast['bal_gbp'], 2) : 0.0;
    $closingCashEgpEquiv = round($closingLe + $closingUsd * $fxRates['USD'] + $closingEur * $fxRates['EUR'] + $closingGbp * $fxRates['GBP'], 2);

    // Schedule 7 & 8: Payroll Cyrene & Payroll Jetty/Boats
    $prSt = $pdo->prepare('SELECT * FROM payroll_monthly_fact WHERE period_month = ? ORDER BY branch_code ASC, id ASC');
    $prSt->execute([$month]);
    $prAll = $prSt->fetchAll();
    $splitPayroll = static function (array $rows, bool $isCyrene): array {
        $list = [];
        $tot  = ['base_salary_egp' => 0.0, 'actual_wage_egp' => 0.0, 'commission_egp' => 0.0, 'allowances_egp' => 0.0, 'bonuses_egp' => 0.0, 'gross_egp' => 0.0, 'advances_deductions_egp' => 0.0, 'net_pay_egp' => 0.0];
        foreach ($rows as $r) {
            $match = $isCyrene ? ((string) $r['branch_code'] === 'CYRENE') : ((string) $r['branch_code'] !== 'CYRENE');
            if (!$match) {
                continue;
            }
            $gross = round((float) $r['actual_wage_egp'] + (float) $r['commission_egp'] + (float) $r['allowances_egp'] + (float) $r['bonuses_egp'], 2);
            $r['gross_egp'] = $gross;
            $list[] = $r;
            $tot['base_salary_egp']         = round($tot['base_salary_egp'] + (float) $r['base_salary_egp'], 2);
            $tot['actual_wage_egp']         = round($tot['actual_wage_egp'] + (float) $r['actual_wage_egp'], 2);
            $tot['commission_egp']          = round($tot['commission_egp'] + (float) $r['commission_egp'], 2);
            $tot['allowances_egp']          = round($tot['allowances_egp'] + (float) $r['allowances_egp'], 2);
            $tot['bonuses_egp']             = round($tot['bonuses_egp'] + (float) $r['bonuses_egp'], 2);
            $tot['gross_egp']               = round($tot['gross_egp'] + $gross, 2);
            $tot['advances_deductions_egp'] = round($tot['advances_deductions_egp'] + (float) $r['advances_deductions_egp'], 2);
            $tot['net_pay_egp']             = round($tot['net_pay_egp'] + (float) $r['net_pay_egp'], 2);
        }
        return ['totals' => $tot, 'rows' => $list];
    };
    $prCyrene = $splitPayroll($prAll, true);
    $prJetty  = $splitPayroll($prAll, false);
    $combinedNetPayroll = round($prCyrene['totals']['net_pay_egp'] + $prJetty['totals']['net_pay_egp'], 2);

    // Schedule 9: Supermarket Catering
    $smSt = $pdo->prepare('SELECT * FROM supermarket_monthly_lines WHERE period_month = ? ORDER BY tx_date ASC, id ASC');
    $smSt->execute([$month]);
    $smRows = $smSt->fetchAll();
    $smTot  = 0.0;
    $smPaid = 0.0;
    foreach ($smRows as $sr) {
        $smTot  += (float) $sr['daily_total_egp'];
        $smPaid += (float) $sr['payment_egp'];
    }
    $smPayable = round($smTot - $smPaid, 2);

    // Schedule 10 & 11: Charters IN & Charters OUT
    $chSt = $pdo->prepare('SELECT * FROM charter_trips_monthly WHERE period_month = ? ORDER BY direction ASC, trip_date ASC, id ASC');
    $chSt->execute([$month]);
    $chAll = $chSt->fetchAll();
    $buildCharters = static function (array $rows, string $dir): array {
        $list = [];
        $tot  = 0.0;
        $paid = 0.0;
        $rem  = 0.0;
        $pax  = 0;
        foreach ($rows as $r) {
            if (strtoupper((string) $r['direction']) !== $dir) {
                continue;
            }
            $list[] = $r;
            $tot  += (float) $r['total_egp'];
            $paid += (float) $r['paid_or_collected_egp'];
            $rem  += (float) $r['remaining_egp'];
            $pax  += (int) $r['adults'] + (int) $r['children'];
        }
        return [
            'direction' => $dir,
            'totals'    => [
                'trips_count'           => count($list),
                'total_pax'             => $pax,
                'total_egp'             => round($tot, 2),
                'paid_or_collected_egp' => round($paid, 2),
                'remaining_egp'         => round($rem, 2),
            ],
            'rows'      => $list,
        ];
    };
    $chartersIn  = $buildCharters($chAll, 'IN');
    $chartersOut = $buildCharters($chAll, 'OUT');

    // Schedule 12 & 13: Partner Statements & YTD 10% Profit Share
    $psSt = $pdo->prepare('SELECT * FROM partner_statements_monthly WHERE period_month = ? ORDER BY schedule_code ASC, sort_order ASC, id ASC');
    $psSt->execute([$month]);
    $psAll = $psSt->fetchAll();
    $partnerRows = [];
    $ytdRows     = [];
    foreach ($psAll as $pr) {
        if ((string) $pr['schedule_code'] === 'PROFIT_SHARE_YTD') {
            $ytdRows[] = $pr;
        } else {
            $partnerRows[] = $pr;
        }
    }
    if (!$ytdRows) {
        // Fallback YTD computation from pnl_monthly_lines for any other month in the year
        $ytdFallbackSt = $pdo->prepare(
            "SELECT period_month, ROUND(SUM(amount_egp), 2) AS net_profit_egp
             FROM pnl_monthly_lines
             WHERE period_year = ? AND period_month <= ? AND section_type = 'NET_PROFIT'
             GROUP BY period_month ORDER BY period_month ASC"
        );
        $ytdFallbackSt->execute([$year, $month]);
        $runNet = 0.0;
        foreach ($ytdFallbackSt->fetchAll() as $idx => $yrRow) {
            $np = (float) $yrRow['net_profit_egp'];
            $runNet += $np;
            $ytdRows[] = [
                'schedule_code'       => 'PROFIT_SHARE_YTD',
                'tx_date'             => $yrRow['period_month'] . '-28',
                'line_description_en' => $yrRow['period_month'] . ' Net Profit',
                'line_description_ar' => 'صافى الربح ' . $yrRow['period_month'],
                'credit_egp'          => $np,
                'debit_egp'           => round($np * 0.10, 2),
                'balance_egp'         => round($np * 0.90, 2),
                'sort_order'          => $idx + 1,
            ];
        }
    }

    // Schedule 14: Corporate Bank Balances (Strictly Masked!)
    $bkSt = $pdo->prepare('SELECT * FROM bank_balances_monthly WHERE period_month = ? ORDER BY id ASC');
    $bkSt->execute([$month]);
    $bkRows = [];
    $opBankSum  = 0.0;
    $allBankSum = 0.0;
    foreach ($bkSt->fetchAll() as $b) {
        $masked = (string) ($b['account_masked'] ?? '****');
        $masked = (string) preg_replace('/\b(\d{4})\d{4,}\b/', '$1****', $masked);
        if (!str_contains($masked, '*')) {
            $masked = substr($masked, 0, 4) . '****';
        }
        $b['account_masked'] = $masked;
        $totB = round((float) $b['total_egp_equiv'], 2);
        if ((int) ($b['is_operating_bank'] ?? 0) === 1) {
            $opBankSum = round($opBankSum + $totB, 2);
        }
        $allBankSum = round($allBankSum + $totB, 2);
        $bkRows[] = $b;
    }

    // Schedule 15: Month-End Open Balances & Net Liquidity Bridge
    $obSt = $pdo->prepare('SELECT * FROM open_balances_monthly WHERE period_month = ? ORDER BY side ASC, sort_order ASC, id ASC');
    $obSt->execute([$month]);
    $obLeft = [];
    $obRight = [];
    $obBridge = [];
    $sumLeft = 0.0;
    $sumRight = 0.0;
    $netFreeLiq = 0.0;
    foreach ($obSt->fetchAll() as $ob) {
        $sd = strtoupper((string) $ob['side']);
        $amt = round((float) $ob['amount_egp'], 2);
        if ($sd === 'RECEIVABLE_ASSET') {
            $obLeft[] = $ob;
            $sumLeft = round($sumLeft + $amt, 2);
        } elseif ($sd === 'PAYABLE_LIABILITY_EQUITY') {
            $obRight[] = $ob;
            $sumRight = round($sumRight + $amt, 2);
        } else {
            $obBridge[] = $ob;
            if ((string) $ob['category'] === 'NET_FREE_LIQUIDITY') {
                $netFreeLiq = $amt;
            }
        }
    }

    // Schedule 16: Reservations-to-Accounting Bridge
    $raSt = $pdo->prepare('SELECT * FROM reservation_accounting_audit WHERE period_month = ?');
    $raSt->execute([$month]);
    $raRow = $raSt->fetch() ?: [
        'period_month'               => $month,
        'days_covered'               => count($dcrRows),
        'executed_vouchers'          => 0,
        'cancelled_vouchers'         => 0,
        'total_accrued_rev_egp'      => $extRev,
        'direct_trip_cogs_egp'       => $extExp,
        'gross_operating_margin_egp' => $consolNet,
        'accounting_bridge_mode'     => 'PNL_SUMMARY',
    ];

    echo json_encode([
        'ok'               => true,
        'month'            => $month,
        'available_months' => $availMonths,
        'fx_rates'         => $fxRates,
        'kpis'             => [
            'cyrene_cash_rev_egp'        => round($cyrCash, 2),
            'jetty_cash_rev_egp'         => round($jetCash, 2),
            'combined_cash_rev_egp'      => round($combCash, 2),
            'combined_visa_egp'          => round($combVisa, 2),
            'cyrene_commissions_egp'     => (float) $cyrScTotals['cyrene_comm_rounded_egp'],
            'cyrene_net_profit_egp'      => $pnlCyrene['totals']['net_profit_egp'],
            'jetty_net_profit_egp'       => $pnlJetty['totals']['net_profit_egp'],
            'interbranch_elimination_egp'=> $ibElimRev,
            'consolidated_ext_rev_egp'   => $extRev,
            'consolidated_ext_exp_egp'   => $extExp,
            'consolidated_net_profit_egp'=> $consolNet,
            'combined_net_payroll_egp'   => $combinedNetPayroll,
            'operating_banks_egp'        => $opBankSum,
            'all_3_banks_egp'            => $allBankSum,
            'open_receivables_egp'       => $sumLeft,
            'open_payables_egp'          => $sumRight,
            'net_free_liquidity_egp'     => $netFreeLiq,
        ],
        'schedules'        => [
            'daily_cash_revenue' => [
                'title_en' => 'Daily Multi-Currency Cash & VISA Revenue',
                'title_ar' => 'اجمالى الايرادات اليومية النقدية والفيزا',
                'totals'   => [
                    'days_count'         => count($dcrRows),
                    'cyrene_cash_egp'    => round($cyrCash, 2),
                    'jetty_cash_egp'     => round($jetCash, 2),
                    'combined_cash_egp'  => round($combCash, 2),
                    'combined_visa_egp'  => round($combVisa, 2),
                    'cyrene_daily_avg_usd' => round(($cyrCash / $fxRates['USD']) / $daysN, 0),
                    'jetty_daily_avg_usd'  => round(($jetCash / $fxRates['USD']) / $daysN, 0),
                ],
                'rows'     => $dcrRows,
            ],
            'seller_commissions' => [
                'title_en' => 'Marketing & Hotel Desk Seller Commissions (10% In-Sheet / 25% Net Outside)',
                'title_ar' => 'عمولات التسويق ومندوبى المبيعات',
                'totals'   => $cyrScTotals,
                'rows'     => $scSummaryRows ?: [['rep_scope' => 'NO_DATA', 'total_sales_egp' => 0.0, 'total_comm_egp' => 0.0]],
            ],
            'pnl_cyrene'         => $pnlCyrene,
            'pnl_jetty'          => $pnlJetty,
            'pnl_consolidated'   => [
                'title_en' => 'Consolidated Company Profit & Loss (Inter-Branch Eliminated)',
                'title_ar' => 'قائمة الارباح والخسائر الموحدة للشركة',
                'totals'   => [
                    'gross_branch_rev_egp'        => $grossBranchRev,
                    'interbranch_elimination_egp' => $ibElimRev,
                    'consolidated_ext_rev_egp'    => $extRev,
                    'gross_branch_exp_egp'        => $grossBranchExp,
                    'consolidated_ext_exp_egp'    => $extExp,
                    'cyrene_net_profit_egp'       => $pnlCyrene['totals']['net_profit_egp'],
                    'jetty_net_profit_egp'        => $pnlJetty['totals']['net_profit_egp'],
                    'consolidated_net_profit_egp' => $consolNet,
                ],
                'rows'     => [
                    ['line_en' => 'Cyrene Branch Gross Revenue', 'line_ar' => 'اجمالى ايرادات فرع سيرين', 'amount_egp' => $pnlCyrene['totals']['total_rev_egp']],
                    ['line_en' => 'Jetty Branch Gross Revenue', 'line_ar' => 'اجمالى ايرادات فرع السقالة', 'amount_egp' => $pnlJetty['totals']['total_rev_egp']],
                    ['line_en' => 'Less: Inter-Branch Elimination (Cyrene <-> Jetty)', 'line_ar' => 'يخصم: استبعاد خوارج السقالة المتبادلة', 'amount_egp' => -$ibElimRev],
                    ['line_en' => 'Consolidated External Revenue', 'line_ar' => 'صافى الايرادات الخارجية الموحدة', 'amount_egp' => $extRev],
                    ['line_en' => 'Consolidated External Expenses', 'line_ar' => 'صافى المصروفات الخارجية الموحدة', 'amount_egp' => $extExp],
                    ['line_en' => 'Consolidated Net Operating Profit', 'line_ar' => 'صافى ربح التشغيل الموحد', 'amount_egp' => $consolNet],
                ],
            ],
            'treasury_cashbox'   => [
                'title_en' => 'Daily Multi-Currency Treasury Cashbox Movement',
                'title_ar' => 'اجمالى حركة الخزينة اليومية بالعملات',
                'totals'   => array_merge($cbSums, [
                    'closing_le'              => $closingLe,
                    'closing_usd'             => $closingUsd,
                    'closing_eur'             => $closingEur,
                    'closing_gbp'             => $closingGbp,
                    'closing_cash_egp_equiv'  => $closingCashEgpEquiv,
                ]),
                'rows'     => $cbRows ?: [['tx_date' => $month . '-01', 'bal_le' => 0.0]],
            ],
            'payroll_cyrene'      => [
                'title_en' => 'Cyrene Branch Staff Salaries & Seller Commissions',
                'title_ar' => 'مرتبات سى كولدج سيرين',
                'totals'   => $prCyrene['totals'],
                'rows'     => $prCyrene['rows'] ?: [['emp_name_ar' => '—', 'net_pay_egp' => 0.0]],
            ],
            'payroll_jetty_boats' => [
                'title_en' => 'Travco Jetty & Fleet Crews Payroll (Omar 4, Oceano, Nizar)',
                'title_ar' => 'مرتبات السقالةوطاقم المراكب',
                'totals'   => array_merge($prJetty['totals'], ['combined_company_net_pay_egp' => $combinedNetPayroll]),
                'rows'     => $prJetty['rows'] ?: [['emp_name_ar' => '—', 'net_pay_egp' => 0.0]],
            ],
            'supermarket'         => [
                'title_en' => 'Yacht Supermarket & Catering Provisions Ledger',
                'title_ar' => 'حساب السوبر ماركت وتموين المراكب',
                'totals'   => ['total_egp' => round($smTot, 2), 'paid_egp' => round($smPaid, 2), 'payable_egp' => $smPayable],
                'rows'     => $smRows ?: [['tx_date' => $month . '-01', 'daily_total_egp' => 0.0]],
            ],
            'charters_in'         => $chartersIn + ['title_en' => 'External Boats Chartered IN', 'title_ar' => 'حساب ايجار مراكب خارجية'],
            'charters_out'        => $chartersOut + ['title_en' => 'Owned Boats Chartered OUT', 'title_ar' => 'حساب تشغيل المراكب الخارجية'],
            'partner_statements'  => [
                'title_en' => 'Partner Yacht Leases, Drydock, Samaha Zodiac, Sharm Diver & Crazy Shark',
                'title_ar' => 'كشوف حسابات الشركاء والمراكب والوكلاء',
                'rows'     => $partnerRows ?: [['schedule_code' => 'NONE', 'balance_egp' => 0.0]],
            ],
            'profit_share_ytd'    => [
                'title_en' => 'YTD Consolidated Net Profit, 10% Management Pool & Fleet CapEx',
                'title_ar' => 'كشف حساب نسبة الارباح السنوية',
                'rows'     => $ytdRows,
            ],
            'bank_balances'       => [
                'title_en' => 'Corporate Multi-Currency Bank Balances (Masked)',
                'title_ar' => 'اجمالى الحسابات بالبنوك (مؤمنة)',
                'totals'   => ['operating_banks_egp' => $opBankSum, 'all_3_banks_egp' => $allBankSum],
                'rows'     => $bkRows ?: [['bank_code' => 'MISR', 'account_masked' => '4440****', 'total_egp_equiv' => 0.0]],
            ],
            'open_balances'       => [
                'title_en'         => 'Month-End Open Balances (AR / Advances vs AP / Accruals) & Liquidity Bridge',
                'title_ar'         => 'كشف الارصدة المفتوحة وصافى السيولة',
                'totals'           => [
                    'receivables_advances_egp' => $sumLeft,
                    'payables_accruals_egp'    => $sumRight,
                    'working_capital_gap_egp'  => round($sumLeft - $sumRight, 2),
                    'net_free_liquidity_egp'   => $netFreeLiq,
                ],
                'receivables'      => $obLeft,
                'payables'         => $obRight,
                'liquidity_bridge' => $obBridge,
            ],
            'res_accounting_bridge' => [
                'title_en' => 'Reservations Sheet to Audited P&L & Double-Entry GL Reconciliation',
                'title_ar' => 'مطابقة شيت الحجوزات مع الحسابات الختامية',
                'summary'  => $raRow,
            ],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
                SUM(CASE WHEN booking_state LIKE 'CANCELLED%' THEN 1 ELSE 0 END) AS cancelled_vouchers,
                COALESCE(SUM(cancelled_pax), 0) AS cancelled_pax,
                ROUND(COALESCE(SUM(CASE WHEN booking_state='EXECUTED' THEN rest_egp_equiv ELSE 0 END), 0), 2) AS total_rest_egp
         FROM reservations_fact {$wStr}"
    );
    $resStmt->execute($pnlArgs);
    $resKpis = $resStmt->fetch() ?: ['vouchers_count' => 0, 'total_pax' => 0, 'cancelled_vouchers' => 0, 'cancelled_pax' => 0, 'total_rest_egp' => 0];

    $tripPnlStmt = $pdo->prepare(
        "SELECT trip_or_cost_group AS product_group,
                MAX(line_name_en) AS name_en,
                MAX(line_name_ar) AS name_ar,
                ROUND(SUM(amount_egp), 2) AS actual_revenue_egp,
                ROUND(AVG(COALESCE(cost_ratio, 0.42)), 3) AS avg_cost_ratio
         FROM pnl_monthly_lines
         WHERE section_type IN ('CASH_REV', 'ACCRUED_REV') AND COALESCE(is_interbranch, 0) = 0
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
            'EXTRA_INTRO_UPSELL'    => 0.14,
            'PADI_COURSES'          => 0.30,
            'CERTIFIED_DIVING'      => 0.36,
            'UNDERWATER_PHOTO'      => 0.52,
            'WEBSITE_ONLINE'        => 0.34,
            'PRIVATE_YACHT'         => 0.44,
            'INTRO_DIVING'          => 0.40,
            'DESERT_SAFARI'         => 0.48,
            'SPEEDBOAT_WATERSPORTS' => 0.49,
            default                 => min(0.85, max(0.25, (float) $tr['avg_cost_ratio'])),
        };
        $cogs = round($rev * $cRat, 2);
        $net  = round($rev - $cogs, 2);
        $profitableTrips[] = [
            'product_group'   => $tr['product_group'],
            'name_en'         => $tr['name_en'],
            'name_ar'         => $tr['name_ar'],
            'revenue_egp'     => $rev,
            'direct_cogs_egp' => $cogs,
            'net_margin_egp'  => $net,
            'margin_pct'      => $rev > 0 ? round(($net / $rev) * 100, 1) : 0.0,
        ];
    }
    usort($profitableTrips, static fn($a, $b) => $b['net_margin_egp'] <=> $a['net_margin_egp']);

    $repStmt = $pdo->prepare(
        "SELECT CASE WHEN sales_rep != '' THEN sales_rep ELSE 'DIRECT / COUNTER' END AS seller_name,
                seller_type,
                COUNT(*) AS vouchers,
                SUM(total_pax) AS pax,
                ROUND(SUM(estimated_revenue_egp), 2) AS est_revenue_egp,
                ROUND(SUM(estimated_profit_egp), 2) AS est_profit_egp
         FROM reservations_fact {$resWhereExec}
         GROUP BY seller_name, seller_type
         ORDER BY pax DESC
         LIMIT 18"
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
            $fa['sea_days']             = (int) $sr['trips'];
            $fa['attributed_rev_egp']   = (float) $sr['rev'];
            $fa['direct_trip_cost_egp'] = (float) $sr['fuel'];
            $fa['operating_margin_egp'] = round((float) $sr['rev'] - (float) $sr['fuel'], 2);
        }
    }
    unset($fa);

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
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

    if (in_array('ops.view_manifests', $perms, true) || in_array('jetty.scan_boarding_collect_rest', $perms, true)) {
        $out['vouchers'] = $pdo->query('SELECT * FROM reservations_fact ORDER BY trip_date DESC, id DESC LIMIT 80')->fetchAll();
    } elseif (in_array('sales.view_own_sales', $perms, true)) {
        // Strict horizontal isolation between sellers!
        $uLower = strtolower((string) $user['username']);
        $repUp  = strtoupper(trim((string) ($user['rep_scope'] ?: $user['username'])));
        $whereSeller = '(LOWER(seller_username) = ? OR (seller_username = "" AND UPPER(sales_rep) = ?))';
        $vSt = $pdo->prepare("SELECT * FROM reservations_fact WHERE {$whereSeller} ORDER BY trip_date DESC, id DESC LIMIT 80");
        $vSt->execute([$uLower, $repUp]);
        $myRows = $vSt->fetchAll();
        $sSt = $pdo->prepare("SELECT COUNT(*) AS total_vouchers, COALESCE(SUM(total_pax),0) AS total_pax, ROUND(COALESCE(SUM(estimated_revenue_egp),0),2) AS est_rev_egp, ROUND(COALESCE(SUM(seller_commission_egp),0),2) AS total_commission_egp FROM reservations_fact WHERE {$whereSeller} AND booking_state = 'EXECUTED'");
        $sSt->execute([$uLower, $repUp]);
        $out['vouchers']       = $myRows;
        $out['my_sales_stats'] = $sSt->fetch();
    } elseif (in_array('crew.view_own_manifest', $perms, true)) {
        $out['vouchers'] = $pdo->query("SELECT * FROM reservations_fact WHERE dives_pax > 0 OR intro1_pax > 0 OR intro2_pax > 0 ORDER BY trip_date DESC LIMIT 50")->fetchAll();
        $out['checkins'] = $pdo->query("SELECT * FROM website_leads_crm WHERE lead_type = 'DIVER_CHECKIN' ORDER BY id DESC LIMIT 30")->fetchAll();
    }

    if (in_array('jetty.manage_gear_photo', $perms, true)) {
        $out['gear_assets'] = $pdo->query('SELECT * FROM equipment_assets_master ORDER BY id ASC LIMIT 120')->fetchAll();
    }

    if (in_array('crm.manage_leads_ads', $perms, true)) {
        $out['crm_leads'] = $pdo->query('SELECT * FROM website_leads_crm ORDER BY id DESC LIMIT 80')->fetchAll();
    }

    if (in_array('sales.create_voucher', $perms, true) || in_array('crm.manage_leads_ads', $perms, true)) {
        $out['catalog'] = $pdo->query('SELECT code, name_en, name_ar, category_group, price_eur, price_egp_est FROM products_catalog WHERE price_eur IS NOT NULL ORDER BY is_popular DESC, code ASC')->fetchAll();
        $out['hotels']  = $pdo->query('SELECT code, name_en, zone_label_en, zone_label_ar, pickup_window FROM hotels_directory ORDER BY zone_code, name_en')->fetchAll();
    }

    if (in_array('bi.view_executive', $perms, true) || in_array('gl.view_all_ledgers', $perms, true)) {
        $out['trial_feedback'] = $pdo->query('SELECT * FROM trial_feedback ORDER BY id DESC LIMIT 100')->fetchAll();
        $out['recent_audits']  = $pdo->query('SELECT * FROM audit_logs ORDER BY id DESC LIMIT 40')->fetchAll();
        $rawCnt = (int) $pdo->query('SELECT COUNT(*) FROM raw_sheet_rows')->fetchColumn();
        $out['trial_stats']    = [
            'full_warehouse_loaded' => $rawCnt > 1000,
            'raw_rows_count'        => $rawCnt,
            'logins_count'          => (int) $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE action_type='USER_LOGIN'")->fetchColumn(),
            'trial_vouchers_count'  => (int) $pdo->query("SELECT COUNT(*) FROM reservations_fact WHERE source_file='LIVE_MOBILE_APP'")->fetchColumn(),
            'open_feedback_count'   => (int) $pdo->query("SELECT COUNT(*) FROM trial_feedback WHERE status != 'RESOLVED'")->fetchColumn(),
        ];
    } else {
        $fSt = $pdo->prepare('SELECT * FROM trial_feedback WHERE username = ? ORDER BY id DESC LIMIT 20');
        $fSt->execute([$user['username']]);
        $out['trial_feedback'] = $fSt->fetchAll();
    }

    if ($user['role_code'] === 'FLEET_PARTNER') {
        $tpSt = $pdo->prepare(
            "SELECT u.id, u.username, u.emp_code, u.display_name_en, u.display_name_ar, u.job_title_en, u.job_title_ar,
                    u.phone, u.email, u.avatar_url, u.bio_notes, u.role_code, u.branch_scope, u.vessel_scope, u.rep_scope, u.referral_code, u.is_active, u.updated_at,
                    r.name_en AS role_en, r.name_ar AS role_ar, r.department
             FROM users u JOIN roles r ON r.role_code = u.role_code
             WHERE u.is_active = 1 AND (u.role_code IN ('OWNER_ADMIN','CHIEF_ACCOUNTANT','OPERATIONS_MANAGER','JETTY_SUPERVISOR') OR u.username = ?)
             ORDER BY r.hierarchy_level DESC, u.id ASC"
        );
        $tpSt->execute([$user['username']]);
        $out['team_profiles'] = $tpSt->fetchAll();
    } else {
        $activeClause = in_array('bi.view_executive', $perms, true) ? '' : 'WHERE u.is_active = 1';
        $out['team_profiles'] = $pdo->query(
            "SELECT u.id, u.username, u.emp_code, u.display_name_en, u.display_name_ar, u.job_title_en, u.job_title_ar,
                    u.phone, u.email, u.avatar_url, u.bio_notes, u.role_code, u.branch_scope, u.vessel_scope, u.rep_scope, u.referral_code, u.is_active, u.updated_at,
                    r.name_en AS role_en, r.name_ar AS role_ar, r.department
             FROM users u JOIN roles r ON r.role_code = u.role_code
             {$activeClause}
             ORDER BY r.hierarchy_level DESC, u.id ASC"
        )->fetchAll();
    }
    if (in_array('bi.view_executive', $perms, true)) {
        $out['all_roles'] = $pdo->query('SELECT role_code, name_en, name_ar, department FROM roles ORDER BY hierarchy_level DESC')->fetchAll();
    }

    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---------------- MUTATING OPERATIONAL & TRIAL ACTIONS (POST + CSRF + RBAC) ---------------- */
if ($method === 'POST') {
    $csrfTok = (string) ($rawIn['csrf_token'] ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!csrf_verify($csrfTok)) {
        http_response_code(419);
        echo json_encode(['ok' => false, 'error' => 'CSRF token expired — please refresh']);
        exit;
    }

    if ($action === 'save_user_profile') {
        if (!$ctx) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Authentication required']);
            exit;
        }
        $meUser  = $ctx['user'];
        $isAdmin = in_array('bi.view_executive', $ctx['perms'], true);
        $mode    = strtolower((string) ($rawIn['mode'] ?? 'update')) === 'create' ? 'create' : 'update';

        if ($mode === 'create' && !$isAdmin) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Only Owners / Admins can create new staff accounts']);
            exit;
        }

        $targetUsername = $mode === 'create'
            ? strtolower(trim((string) ($rawIn['username'] ?? '')))
            : strtolower(trim((string) ($rawIn['target_username'] ?? $meUser['username'])));

        if ($mode === 'update' && $targetUsername !== strtolower((string) $meUser['username']) && !$isAdmin) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'You may only edit your own profile']);
            exit;
        }

        if (!preg_match('/^[a-z0-9._-]{3,32}$/', $targetUsername)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Username must be 3–32 lowercase letters, numbers, dots, or hyphens']);
            exit;
        }

        $nameEn  = clean_line($rawIn['display_name_en'] ?? '', 100);
        $nameAr  = clean_line($rawIn['display_name_ar'] ?? '', 100);
        $titleEn = clean_line($rawIn['job_title_en'] ?? '', 100);
        $titleAr = clean_line($rawIn['job_title_ar'] ?? '', 100);
        $phone   = clean_line($rawIn['phone'] ?? '', 36);
        $email   = strtolower(clean_line($rawIn['email'] ?? '', 160));
        $bio     = clean($rawIn['bio_notes'] ?? '', 500);
        $newPass = (string) ($rawIn['new_password'] ?? '');

        if (mb_strlen($nameEn) < 2) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'English full name is required']);
            exit;
        }
        if ($nameAr === '') {
            $nameAr = $nameEn;
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Please enter a valid email address']);
            exit;
        }
        if ($phone !== '' && !preg_match('/^\+?[0-9 ()\-]{6,28}$/', $phone)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Please enter a valid phone number (e.g. +20 10 00344544)']);
            exit;
        }

        $savedAvatarUrl = null;
        $imgBytes = null;
        $dataUrl  = (string) ($rawIn['avatar_data_url'] ?? '');
        if ($dataUrl !== '' && preg_match('#^data:image/(jpeg|jpg|png|webp);base64,([A-Za-z0-9+/=\r\n]+)$#i', $dataUrl, $dm)) {
            $decoded = base64_decode($dm[2], true);
            if ($decoded !== false && strlen($decoded) > 64 && strlen($decoded) <= 2_500_000) {
                $imgBytes = $decoded;
            }
        } elseif (!empty($_FILES['avatar_file']['tmp_name']) && is_uploaded_file($_FILES['avatar_file']['tmp_name'])) {
            $fBytes = file_get_contents($_FILES['avatar_file']['tmp_name']);
            if ($fBytes !== false && strlen($fBytes) > 64 && strlen($fBytes) <= 4_000_000) {
                $imgBytes = $fBytes;
            }
        }

        if ($imgBytes !== null) {
            $info = @getimagesizefromstring($imgBytes);
            if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => 'Invalid image file (JPEG, PNG, or WebP required)']);
                exit;
            }
            $ext = match ($info[2]) {
                IMAGETYPE_PNG  => 'png',
                IMAGETYPE_WEBP => 'webp',
                default        => 'jpg',
            };
            $avDir = dirname(SC_ERP_DB_PATH) . '/avatars';
            if (!is_dir($avDir)) {
                @mkdir($avDir, 0750, true);
                @file_put_contents($avDir . '/.htaccess', "Require all denied\n");
            }
            $safeBase = preg_replace('/[^a-z0-9_-]/', '_', $targetUsername);
            $fname    = $safeBase . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;
            file_put_contents($avDir . '/' . $fname, $imgBytes);
            $savedAvatarUrl = '/handlers/erp_api.php?action=avatar&file=' . rawurlencode($fname);
        } elseif (!empty($rawIn['clear_avatar'])) {
            $savedAvatarUrl = '';
        }

        $now = gmdate('Y-m-d\TH:i:s\Z');

        if ($mode === 'create') {
            if (strlen($newPass) < 6) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => 'Initial password must be at least 6 characters']);
                exit;
            }
            $exists = $pdo->prepare('SELECT COUNT(*) FROM users WHERE LOWER(username) = ?');
            $exists->execute([$targetUsername]);
            if ((int) $exists->fetchColumn() > 0) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => 'Username already exists']);
                exit;
            }
            $roleCode    = clean_line($rawIn['role_code'] ?? 'SALES_REP', 32);
            $branchScope = clean_line($rawIn['branch_scope'] ?? 'ALL', 32);
            $vesselScope = clean_line($rawIn['vessel_scope'] ?? 'ALL', 64);
            $repScope    = strtoupper(clean_line($rawIn['rep_scope'] ?? '', 32));
            $refCode     = strtolower((string) preg_replace('/[^a-z0-9]/i', '', (string) ($rawIn['referral_code'] ?? preg_replace('/\.sales$/i', '', $targetUsername))));
            $empCode     = 'EMP_' . strtoupper(substr(preg_replace('/[^a-z0-9]/', '', $targetUsername), 0, 8));

            $ins = $pdo->prepare(
                'INSERT INTO users (username, password_hash, pin_hint, emp_code, display_name_en, display_name_ar,
                                    job_title_en, job_title_ar, phone, email, avatar_url, bio_notes,
                                    role_code, branch_scope, vessel_scope, rep_scope, referral_code,
                                    default_comm_insheet_pct, default_comm_outside_pct, is_active, created_at, updated_at)
                 VALUES (?, ?, "", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0.10, 0.25, 1, ?, ?)'
            );
            $ins->execute([
                $targetUsername, sc_erp_hash_password($newPass), $empCode,
                $nameEn, $nameAr, $titleEn, $titleAr, $phone, $email, $savedAvatarUrl ?? '', $bio,
                $roleCode, $branchScope, $vesselScope, $repScope, $refCode, $now, $now,
            ]);
            sc_erp_audit($pdo, (string) $meUser['username'], (string) $meUser['role_code'], 'CREATE_USER_PROFILE', 'USER', $targetUsername, ['role' => $roleCode, 'phone' => $phone]);
        } else {
            $curSt = $pdo->prepare('SELECT * FROM users WHERE LOWER(username) = ?');
            $curSt->execute([$targetUsername]);
            $cur = $curSt->fetch();
            if (!$cur) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => 'User account not found']);
                exit;
            }
            $avatarFinal = $savedAvatarUrl !== null ? $savedAvatarUrl : (string) $cur['avatar_url'];
            $passHash    = strlen($newPass) >= 6 ? sc_erp_hash_password($newPass) : (string) $cur['password_hash'];
            // Non-admins can NEVER escalate role_code, branch_scope, vessel_scope, rep_scope, or referral_code!
            $roleCode    = $isAdmin && !empty($rawIn['role_code']) ? clean_line($rawIn['role_code'], 32) : (string) $cur['role_code'];
            $branchScope = $isAdmin && isset($rawIn['branch_scope']) ? clean_line($rawIn['branch_scope'], 32) : (string) $cur['branch_scope'];
            $vesselScope = $isAdmin && isset($rawIn['vessel_scope']) ? clean_line($rawIn['vessel_scope'], 64) : (string) $cur['vessel_scope'];
            $repScope    = $isAdmin && isset($rawIn['rep_scope']) ? strtoupper(clean_line($rawIn['rep_scope'], 32)) : (string) $cur['rep_scope'];
            $refCode     = $isAdmin && isset($rawIn['referral_code']) ? strtolower((string) preg_replace('/[^a-z0-9]/i', '', (string) $rawIn['referral_code'])) : (string) ($cur['referral_code'] ?? '');
            $isActive    = $isAdmin && isset($rawIn['is_active']) ? ((int) $rawIn['is_active'] ? 1 : 0) : (int) $cur['is_active'];

            $upd = $pdo->prepare(
                'UPDATE users
                 SET password_hash = ?, display_name_en = ?, display_name_ar = ?,
                     job_title_en = ?, job_title_ar = ?, phone = ?, email = ?, avatar_url = ?, bio_notes = ?,
                     role_code = ?, branch_scope = ?, vessel_scope = ?, rep_scope = ?, referral_code = ?, is_active = ?, updated_at = ?
                 WHERE id = ?'
            );
            $upd->execute([
                $passHash, $nameEn, $nameAr,
                $titleEn, $titleAr, $phone, $email, $avatarFinal, $bio,
                $roleCode, $branchScope, $vesselScope, $repScope, $refCode, $isActive, $now,
                $cur['id'],
            ]);
            sc_erp_audit($pdo, (string) $meUser['username'], (string) $meUser['role_code'], 'UPDATE_USER_PROFILE', 'USER', $targetUsername, ['photo_updated' => $savedAvatarUrl !== null, 'phone' => $phone]);
        }

        $freshCtx = erp_auth_context($pdo);
        echo json_encode(['ok' => true, 'mode' => $mode, 'username' => $targetUsername, 'me' => $freshCtx ? $freshCtx['user'] : null], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'submit_trial_feedback') {
        if (!$ctx) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Authentication required']);
            exit;
        }
        $u       = $ctx['user'];
        $fType   = in_array($rawIn['feedback_type'] ?? '', ['BUG', 'IMPROVEMENT', 'ACCOUNTING_RULE', 'UX_SPEED'], true) ? (string) $rawIn['feedback_type'] : 'IMPROVEMENT';
        $prio    = ($rawIn['priority'] ?? 'NORMAL') === 'HIGH' ? 'HIGH' : 'NORMAL';
        $tab     = clean_line($rawIn['module_tab'] ?? 'general', 40);
        $device  = clean_line($rawIn['device_info'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? ''), 120);
        $msg     = clean($rawIn['message'] ?? '', 1500);
        if (mb_strlen($msg) < 3) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Please enter a short description']);
            exit;
        }
        $ins = $pdo->prepare('INSERT INTO trial_feedback (created_at, username, display_name, role_code, module_tab, feedback_type, priority, device_info, message, status, admin_reply) VALUES (?,?,?,?,?,?,?,?,?, "OPEN", "")');
        $ins->execute([gmdate('Y-m-d H:i'), $u['username'], $u['display_name_en'], $u['role_code'], $tab, $fType, $prio, $device, $msg]);
        sc_erp_audit($pdo, (string) $u['username'], (string) $u['role_code'], 'SUBMIT_TRIAL_FEEDBACK', 'FEEDBACK', (string) $pdo->lastInsertId(), ['type' => $fType, 'priority' => $prio]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'update_feedback_status') {
        erp_require_perm($ctx, 'bi.view_executive', 'gl.view_all_ledgers');
        $fid    = (int) ($rawIn['id'] ?? 0);
        $status = in_array($rawIn['status'] ?? '', ['OPEN', 'IN_PROGRESS', 'RESOLVED'], true) ? (string) $rawIn['status'] : 'RESOLVED';
        $reply  = clean_line($rawIn['admin_reply'] ?? '', 400);
        $pdo->prepare('UPDATE trial_feedback SET status = ?, admin_reply = CASE WHEN ? != "" THEN ? ELSE admin_reply END WHERE id = ?')->execute([$status, $reply, $reply, $fid]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'reset_trial_sandbox') {
        erp_require_perm($ctx, 'bi.view_executive');
        $delV = $pdo->exec("DELETE FROM reservations_fact WHERE source_file = 'LIVE_MOBILE_APP'");
        $delC = $pdo->exec("DELETE FROM seller_commissions_ledger WHERE source_channel = 'LIVE_MOBILE_APP'");
        $delE = $pdo->exec("DELETE FROM expense_vouchers_fact WHERE source_file = 'LIVE_MOBILE_SAFE'");
        sc_erp_audit($pdo, (string) $ctx['user']['username'], (string) $ctx['user']['role_code'], 'RESET_TRIAL_SANDBOX', 'SANDBOX', 'ALL', ['deleted_vouchers' => $delV, 'deleted_commissions' => $delC, 'deleted_expenses' => $delE]);
        echo json_encode(['ok' => true, 'deleted_vouchers' => (int) $delV, 'deleted_expenses' => (int) $delE]);
        exit;
    }

    if ($action === 'upload_warehouse_gz') {
        erp_require_perm($ctx, 'bi.view_executive');
        if (empty($_FILES['warehouse_file']['tmp_name']) || !is_uploaded_file($_FILES['warehouse_file']['tmp_name'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'No file received (check file size vs PHP upload_max_filesize)']);
            exit;
        }
        $origName = strtolower((string) ($_FILES['warehouse_file']['name'] ?? ''));
        $tmpUp    = $_FILES['warehouse_file']['tmp_name'];

        @set_time_limit(240);

        if (str_ends_with($origName, '.sqlite3') || str_ends_with($origName, '.sqlite') || str_ends_with($origName, '.db')) {
            $hdr = (string) file_get_contents($tmpUp, false, null, 0, 16);
            if (!str_starts_with($hdr, 'SQLite format 3')) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => 'Invalid SQLite3 binary header']);
                exit;
            }
            $newDbPath = SC_ERP_DB_PATH . '.importing_' . bin2hex(random_bytes(4));
            try {
                $liveSnap = erp_snapshot_live_state($pdo);
                if (!copy($tmpUp, $newDbPath)) {
                    throw new RuntimeException('Unable to stage uploaded SQLite file');
                }
                $impPdo = new PDO('sqlite:' . $newDbPath, null, null, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                $tblCnt = (int) $impPdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table'")->fetchColumn();
                if ($tblCnt < 5) {
                    throw new RuntimeException('Uploaded SQLite file does not contain valid ERP tables');
                }
                $impPdo->beginTransaction();
                erp_restore_live_state($impPdo, $liveSnap);
                $impPdo->commit();
                $impPdo->exec('PRAGMA wal_checkpoint(TRUNCATE);');
                $impPdo = null;
                $pdo    = null;
                @unlink(SC_ERP_DB_PATH . '-wal');
                @unlink(SC_ERP_DB_PATH . '-shm');
                @unlink($newDbPath . '-wal');
                @unlink($newDbPath . '-shm');
                rename($newDbPath, SC_ERP_DB_PATH);
                echo json_encode(['ok' => true, 'mode' => 'SQLITE_REPLACED_LIVE_MERGED']);
                exit;
            } catch (Throwable $e) {
                $impPdo = null;
                @unlink($newDbPath);
                @unlink($newDbPath . '-wal');
                @unlink($newDbPath . '-shm');
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => 'SQLite import validation failed: ' . $e->getMessage()]);
                exit;
            }
        }

        if (str_ends_with($origName, '.sql.gz') || str_ends_with($origName, '.gz')) {
            $newDbPath = SC_ERP_DB_PATH . '.importing_' . bin2hex(random_bytes(4));
            $gz        = null;
            $impPdo    = null;
            try {
                $hdrBytes = (string) file_get_contents($tmpUp, false, null, 0, 2);
                if ($hdrBytes !== "\x1f\x8b") {
                    throw new RuntimeException('Invalid gzip magic header');
                }
                $liveSnap = erp_snapshot_live_state($pdo);
                $impPdo = new PDO('sqlite:' . $newDbPath, null, null, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                $impPdo->exec('PRAGMA synchronous = OFF; PRAGMA journal_mode = MEMORY;');
                $gz = @gzopen($tmpUp, 'rb');
                if (!$gz) {
                    throw new RuntimeException('Unable to gunzip .sql.gz file');
                }
                $buf      = '';
                $stmtExec = 0;
                $impPdo->beginTransaction();
                while (!gzeof($gz)) {
                    $line = gzgets($gz);
                    if ($line === false) {
                        break;
                    }
                    $trim = trim($line);
                    if ($trim === '' || str_starts_with($trim, '--') || in_array(strtoupper($trim), ['BEGIN TRANSACTION;', 'COMMIT;'], true) || str_contains($trim, 'sqlite_sequence')) {
                        continue;
                    }
                    $buf .= $line;
                    if (str_ends_with($trim, ';')) {
                        $impPdo->exec($buf);
                        $buf = '';
                        $stmtExec++;
                    }
                }
                if (trim($buf) !== '') {
                    $impPdo->exec($buf);
                    $stmtExec++;
                }
                if ($stmtExec === 0) {
                    throw new RuntimeException('Empty SQL dump inside .sql.gz');
                }
                erp_restore_live_state($impPdo, $liveSnap);
                $impPdo->commit();
                gzclose($gz);
                $gz = null;

                $impPdo = null;
                $pdo    = null;
                @unlink(SC_ERP_DB_PATH . '-wal');
                @unlink(SC_ERP_DB_PATH . '-shm');
                rename($newDbPath, SC_ERP_DB_PATH);
                echo json_encode(['ok' => true, 'mode' => 'SQL_GZ_IMPORTED_LIVE_MERGED', 'statements' => $stmtExec]);
                exit;
            } catch (Throwable $e) {
                if (is_resource($gz)) {
                    @gzclose($gz);
                }
                $impPdo = null;
                @unlink($newDbPath);
                @unlink($newDbPath . '-wal');
                @unlink($newDbPath . '-shm');
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => 'Failed to import .sql.gz atomically: ' . $e->getMessage()]);
                exit;
            }
        }

        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Upload sea_college_bigdata_dump.sql.gz or sea_college_bigdata.sqlite3']);
        exit;
    }

    if ($action === 'create_voucher') {
        erp_require_perm($ctx, 'sales.create_voucher');
        $user     = $ctx['user'];
        $tripDt   = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($rawIn['trip_date'] ?? '')) ? (string) $rawIn['trip_date'] : gmdate('Y-m-d');
        [$wk, $mo, $qr, $yr] = sc_erp_period_parts($tripDt);
        $voNo     = 'TRIAL-' . random_int(10000, 99999);
        $qrTok    = 'QR-' . strtoupper(bin2hex(random_bytes(6)));
        $guest    = clean_line($rawIn['guest_name'] ?? 'Resort Guest', 120);
        $hotel    = clean_line($rawIn['hotel_name'] ?? 'Cyrene Sharm Hotel', 120);
        $room     = clean_line($rawIn['room_no'] ?? '', 32);
        $nat      = strtoupper(clean_line($rawIn['nationality'] ?? 'ITALY', 24));
        $pickup   = clean_line($rawIn['pickup_time'] ?? '08:00', 24);
        $prodCode = clean_line($rawIn['product_code'] ?? 'dsd-2-boat', 64);
        $notes    = clean_line($rawIn['notes'] ?? '', 250);

        $adults   = max(1, min(60, (int) ($rawIn['adults'] ?? 2)));
        $children = max(0, min(40, (int) ($rawIn['children'] ?? 0)));
        $pax      = $adults + $children;

        $fx = sc_erp_fx_rates($pdo);

        // Multi-currency deposit & rest calculation
        $depAmt   = max(0.0, (float) ($rawIn['deposit_amount'] ?? 0.0));
        $depCurr  = strtoupper(trim((string) ($rawIn['deposit_currency'] ?? 'EGP')));
        if (!isset($fx[$depCurr])) {
            $depCurr = 'EGP';
        }
        $depositEgp = round($depAmt * $fx[$depCurr], 2);

        $restEur  = max(0.0, (float) ($rawIn['rest_eur'] ?? 0.0));
        $restAmt  = max(0.0, (float) ($rawIn['rest_amount'] ?? 0.0));
        $restCurr = strtoupper(trim((string) ($rawIn['rest_currency'] ?? 'EGP')));
        if (!isset($fx[$restCurr])) {
            $restCurr = 'EGP';
        }

        $rEgp = 0.0;
        $rUsd = 0.0;
        $rEur = $restEur;
        $rGbp = 0.0;
        if ($restAmt > 0.0) {
            if ($restCurr === 'EGP') {
                $rEgp += $restAmt;
            } elseif ($restCurr === 'USD') {
                $rUsd += $restAmt;
            } elseif ($restCurr === 'EUR') {
                $rEur += $restAmt;
            } elseif ($restCurr === 'GBP') {
                $rGbp += $restAmt;
            }
        }
        $restEq = round($rEgp + $rUsd * $fx['USD'] + $rEur * $fx['EUR'] + $rGbp * $fx['GBP'], 2);

        $pSt = $pdo->prepare('SELECT * FROM products_catalog WHERE code = ?');
        $pSt->execute([$prodCode]);
        $prod = $pSt->fetch();
        $tripName = $prod ? (string) $prod['name_en'] : '';
        $unitEur  = ($prod && $prod['price_eur'] !== null) ? (float) $prod['price_eur'] : 0.0;
        $costRatio = (float) ($prod['direct_cost_ratio'] ?? 0.38);

        if ($tripName === '' && is_file(ROOT_DIR . '/data/operational_skus.php')) {
            $opSkus = require ROOT_DIR . '/data/operational_skus.php';
            foreach ($opSkus as $sk) {
                if (($sk['id'] ?? '') === $prodCode || ($sk['code'] ?? '') === $prodCode) {
                    $tripName = (string) ($sk['names']['en'] ?? $prodCode);
                    $unitEur  = (float) ($sk['price_eur'] ?? 45.0);
                    break;
                }
            }
        }
        if ($tripName === '') {
            $tripName = $prodCode;
        }
        if ($unitEur <= 0.0) {
            $unitEur = 55.0;
        }

        $catalogRevEgp = round(($adults + 0.5 * $children) * $unitEur * $fx['EUR'], 2);
        $explicitGross = round($depositEgp + $restEq, 2);
        $grossEgp      = $explicitGross > 0.0 ? $explicitGross : $catalogRevEgp;

        $saleModeRaw = strtoupper(trim((string) ($rawIn['sale_mode'] ?? 'INSHEET')));
        if (!in_array($saleModeRaw, ['INSHEET', 'OUT_SIDE', 'OUTSIDE', 'CENTER'], true)) {
            $saleModeRaw = 'INSHEET';
        }
        $supplierCostEgp = max(0.0, round((float) ($rawIn['supplier_cost_egp'] ?? 0.0), 2));
        $cogsEgp  = ($saleModeRaw === 'OUT_SIDE' || $saleModeRaw === 'OUTSIDE')
            ? ($supplierCostEgp > 0 ? $supplierCostEgp : round($grossEgp * $costRatio, 2))
            : round($grossEgp * $costRatio, 2);
        $profEgp  = round($grossEgp - $cogsEgp, 2);

        // Resolve seller attribution (Sales Rep self-scoped; Ops/Admin can pass optional seller_username)
        $sellerCandidate = '';
        if ($user['role_code'] === 'SALES_REP') {
            $sellerCandidate = (string) $user['username'];
        } elseif (!empty($rawIn['seller_username'])) {
            $sellerCandidate = (string) $rawIn['seller_username'];
        }

        $resolvedSeller = $saleModeRaw === 'CENTER' ? null : sc_erp_resolve_seller($pdo, $sellerCandidate);
        $repName    = $saleModeRaw === 'CENTER'
            ? 'CENTER'
            : ($resolvedSeller ? (string) $resolvedSeller['rep_scope'] : ($user['rep_scope'] !== '' ? (string) $user['rep_scope'] : strtoupper((string) $user['username'])));
        $branch     = $resolvedSeller
            ? (string) ($resolvedSeller['branch_scope'] ?: 'CYRENE')
            : ($user['branch_scope'] !== 'ALL' ? (string) $user['branch_scope'] : 'CYRENE');
        $sellerType = $resolvedSeller ? 'HOTEL_SELLER' : 'HOUSE_OPS';

        $commRes = sc_erp_record_seller_commission($pdo, [
            'seller_token'     => $resolvedSeller ? (string) $resolvedSeller['username'] : '',
            'sale_mode'        => $saleModeRaw,
            'tx_date'          => $tripDt,
            'voucher_no'       => $voNo,
            'guest_name'       => $guest,
            'product_name'     => $tripName,
            'pax'              => $pax,
            'gross_amount_egp' => $grossEgp,
            'supplier_cost_egp'=> $supplierCostEgp,
            'status'           => 'PENDING_EXECUTION',
            'source_channel'   => 'LIVE_MOBILE_APP',
            'branch_code'      => $branch,
            'notes'            => $notes,
        ]);

        $ins = $pdo->prepare(
            'INSERT INTO reservations_fact
             (voucher_no, qr_token, trip_date, period_week, period_month, period_quarter, period_year,
              branch_code, channel, hotel_name, pickup_zone, room_no, nationality, pickup_time,
              trip_name, product_category, adults, children, infants, total_pax,
              snork_pax, intro1_pax, intro2_pax, dives_pax, divers_names,
              sales_rep, executor, client_agency, rest_egp, rest_usd, rest_eur, rest_gbp, rest_egp_equiv,
              desk_paid_egp, deposit_egp_equiv, supplier_cost_egp,
              estimated_revenue_egp, estimated_cogs_egp, estimated_profit_egp, vessel_code,
              boarding_status, payment_status, booking_state, source_file,
              seller_type, seller_username, seller_commission_egp, commission_type, commission_status)
             VALUES (?,?,?,?,?,?,?, ?,?,?,?,?,?,?, ?,?,?,?,?,?, 0,0,0,0,?, ?,?,?, ?,?,?,?,?, ?,?,?, ?,?,?, ?,?,?,?, ?, ?,?,?,?,?)'
        );
        $ins->execute([
            $voNo, $qrTok, $tripDt, $wk, $mo, $qr, $yr,
            $branch, 'MOBILE_APP', $hotel, '', $room, $nat, $pickup,
            $tripName, 'MOBILE_VOUCHER', $adults, $children, 0, $pax,
            $guest, $repName, 'SEA COLLEGE', 'DIRECT',
            $rEgp, $rUsd, $rEur, $rGbp, $restEq,
            $depositEgp, $depositEgp, $supplierCostEgp,
            $grossEgp, $cogsEgp, $profEgp,
            '', 'BOOKED', $restEq > 0 ? 'OPEN_REST' : 'PAID', 'EXECUTED', 'LIVE_MOBILE_APP',
            $sellerType,
            $commRes['seller_username'],
            $commRes['commission_egp'],
            $commRes['commission_type'],
            $commRes['commission_status'],
        ]);

        sc_erp_audit($pdo, (string) $user['username'], (string) $user['role_code'], 'CREATE_VOUCHER', 'VOUCHER', $voNo, [
            'pax'            => $pax,
            'gross_egp'      => $grossEgp,
            'sale_mode'      => $saleModeRaw,
            'commission_egp' => $commRes['commission_egp'],
        ]);

        echo json_encode([
            'ok'                    => true,
            'voucher_no'            => $voNo,
            'qr_token'              => $qrTok,
            'estimated_revenue_egp' => $grossEgp,
            'gross_amount_egp'      => $grossEgp,
            'commission_egp'        => $commRes['commission_egp'],
            'commission_type'       => $commRes['commission_type'],
            'commission_status'     => $commRes['commission_status'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
        $voNo = (string) $v['voucher_no'];
        $alreadyBoarded = strtoupper((string) ($v['boarding_status'] ?? '')) === 'BOARDED';
        $collectedEgp   = $alreadyBoarded ? 0.0 : max(0.0, (float) $v['rest_egp_equiv']);

        $pdo->prepare(
            "UPDATE reservations_fact
             SET rest_egp = 0, rest_usd = 0, rest_eur = 0, rest_gbp = 0, rest_egp_equiv = 0,
                 payment_status = 'PAID',
                 boarding_status = 'BOARDED',
                 commission_status = CASE WHEN seller_username != '' AND commission_type != 'NONE' THEN 'EARNED_UNPAID' ELSE commission_status END
             WHERE id = ?"
        )->execute([$id]);

        if ($collectedEgp > 0.0) {
            $pdo->prepare("UPDATE safes_wallets SET bal_egp = bal_egp + ?, updated_at = ? WHERE code = 'SAFE_JETTY'")->execute([$collectedEgp, gmdate('c')]);
        }

        if ($voNo !== '') {
            $pdo->prepare(
                "UPDATE seller_commissions_ledger
                 SET status = 'EARNED_UNPAID'
                 WHERE voucher_no = ? AND status = 'PENDING_EXECUTION'"
            )->execute([$voNo]);
        }

        sc_erp_audit($pdo, (string) $ctx['user']['username'], (string) $ctx['user']['role_code'], 'JETTY_BOARD_COLLECT_REST', 'VOUCHER', $voNo, ['collected_egp' => $collectedEgp]);
        echo json_encode(['ok' => true, 'collected_egp' => $collectedEgp, 'commission_status' => 'EARNED_UNPAID']);
        exit;
    }

    if ($action === 'cancel_voucher') {
        erp_require_perm($ctx, 'ops.assign_boat_driver', 'jetty.scan_boarding_collect_rest', 'bi.view_executive', 'gl.view_all_ledgers');
        $id   = (int) ($rawIn['id'] ?? 0);
        $voIn = trim((string) ($rawIn['voucher_no'] ?? ''));
        $reason = clean_line($rawIn['reason'] ?? 'Cancelled', 200);

        if ($id > 0) {
            $vSt = $pdo->prepare('SELECT * FROM reservations_fact WHERE id = ?');
            $vSt->execute([$id]);
        } else {
            $vSt = $pdo->prepare('SELECT * FROM reservations_fact WHERE voucher_no = ? ORDER BY id DESC LIMIT 1');
            $vSt->execute([$voIn]);
        }
        $v = $vSt->fetch();
        if (!$v) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Voucher not found']);
            exit;
        }
        $vid  = (int) $v['id'];
        $voNo = (string) $v['voucher_no'];
        $pax  = max(0, (int) ($v['total_pax'] ?? 0));

        $pdo->prepare(
            "UPDATE reservations_fact
             SET rest_egp = 0, rest_usd = 0, rest_eur = 0, rest_gbp = 0, rest_egp_equiv = 0,
                 cancelled_pax = CASE WHEN cancelled_pax > 0 THEN cancelled_pax ELSE ? END,
                 total_pax = 0,
                 booking_state = 'CANCELLED',
                 boarding_status = 'CANCELLED',
                 payment_status = 'CANCELLED',
                 seller_commission_egp = 0,
                 commission_status = 'REVERSED_CANCELLED'
             WHERE id = ?"
        )->execute([$pax, $vid]);

        if ($voNo !== '') {
            $pdo->prepare(
                "UPDATE seller_commissions_ledger
                 SET status = 'REVERSED_CANCELLED', notes = CASE WHEN notes = '' THEN ? ELSE notes || ' | ' || ? END
                 WHERE voucher_no = ?"
            )->execute([$reason, $reason, $voNo]);
        }

        sc_erp_audit($pdo, (string) $ctx['user']['username'], (string) $ctx['user']['role_code'], 'CANCEL_VOUCHER', 'VOUCHER', $voNo, ['reason' => $reason]);
        echo json_encode(['ok' => true, 'voucher_no' => $voNo, 'status' => 'CANCELLED', 'commission_status' => 'REVERSED_CANCELLED']);
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
            $fxMap = sc_erp_fx_rates($pdo);
            $totEgp = round($amt * ($fxMap[$curr] ?? 1.0), 2);
            $pdo->prepare('INSERT INTO expense_vouchers_fact (exp_date, period_week, period_month, period_quarter, period_year, voucher_seq, description, cost_center, amount_le, amount_usd, amount_eur, amount_gbp, total_egp, source_file)
                           VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([
                $today, $wk, $mo, $qr, $yr, 'TRIAL-' . random_int(1000, 9999), $desc, 'TREASURY_APP',
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
