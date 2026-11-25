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
        'custom_users'        => [],
        'live_vouchers'       => [],
        'live_crm'            => [],
        'live_commissions'    => [],
        'live_feedback'       => [],
        'live_expenses'       => [],
        'live_inbox_msgs'     => [],
        'live_inbox_reads'    => [],
        'live_guest_accounts' => [],
        'live_guest_emails'   => [],
        'live_suppliers'      => [],
        'live_cost_items'     => [],
        'live_cost_audit'     => [],
        'live_staff_bonuses'  => [],
    ];
    try {
        $snap['custom_users'] = $pdo->query("SELECT * FROM users WHERE updated_at != ''")->fetchAll();
    } catch (Throwable $e) {}
    try {
        $snap['live_vouchers'] = $pdo->query("SELECT * FROM reservations_fact WHERE source_file IN ('LIVE_MOBILE_APP', 'LIVE_WEBSITE_FORM', 'SELLER_SHARE_LINK')")->fetchAll();
    } catch (Throwable $e) {}
    try {
        $snap['live_crm'] = $pdo->query('SELECT * FROM website_leads_crm')->fetchAll();
    } catch (Throwable $e) {}
    try {
        $snap['live_commissions'] = $pdo->query("SELECT * FROM seller_commissions_ledger WHERE source_channel LIKE 'LIVE_%' OR source_channel = 'SELLER_SHARE_LINK' OR voucher_no LIKE 'TRIAL-%' OR voucher_no LIKE 'WEB-%' OR voucher_no LIKE 'SLB-%' OR voucher_no LIKE 'VIP-%'")->fetchAll();
    } catch (Throwable $e) {}
    try {
        $snap['live_feedback'] = $pdo->query('SELECT * FROM trial_feedback')->fetchAll();
    } catch (Throwable $e) {}
    try {
        $snap['live_expenses'] = $pdo->query("SELECT * FROM expense_vouchers_fact WHERE source_file = 'LIVE_MOBILE_SAFE'")->fetchAll();
    } catch (Throwable $e) {}
    try {
        $snap['live_inbox_msgs'] = $pdo->query('SELECT * FROM company_inbox_messages WHERE seed_key IS NULL OR is_deleted = 1')->fetchAll();
    } catch (Throwable $e) {}
    try {
        $snap['live_inbox_reads'] = $pdo->query('SELECT * FROM company_inbox_reads')->fetchAll();
    } catch (Throwable $e) {}
    try {
        $snap['live_guest_accounts'] = $pdo->query('SELECT * FROM guest_customer_accounts')->fetchAll();
    } catch (Throwable $e) {}
    try {
        $snap['live_guest_emails'] = $pdo->query('SELECT * FROM guest_email_dispatches')->fetchAll();
    } catch (Throwable $e) {}
    try {
        $snap['live_suppliers'] = $pdo->query("SELECT * FROM suppliers_directory WHERE updated_by != 'SYSTEM'")->fetchAll();
    } catch (Throwable $e) {}
    try {
        $snap['live_cost_items'] = $pdo->query("SELECT * FROM trip_supplier_cost_items WHERE updated_by != 'SYSTEM'")->fetchAll();
    } catch (Throwable $e) {}
    try {
        $snap['live_cost_audit'] = $pdo->query('SELECT * FROM trip_cost_audit_log')->fetchAll();
    } catch (Throwable $e) {}
    try {
        $snap['live_staff_bonuses'] = $pdo->query('SELECT * FROM staff_bonuses_allowances WHERE seed_key IS NULL OR decided_at != ""')->fetchAll();
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
    sc_erp_ensure_v3_extensions($impPdo);

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

    // 1) Restore customized user profiles & newly created users (including GUEST_CUSTOMER accounts)
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
        $ex = $impPdo->prepare("SELECT COUNT(*) FROM reservations_fact WHERE voucher_no = ? AND source_file IN ('LIVE_MOBILE_APP', 'LIVE_WEBSITE_FORM', 'SELLER_SHARE_LINK')");
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

    // 7) Restore live Company Inbox messages & read/reaction states
    foreach (($snap['live_inbox_msgs'] ?? []) as $im) {
        if (!empty($im['seed_key'])) {
            $impPdo->prepare('UPDATE company_inbox_messages SET is_deleted = ?, is_pinned = ?, deleted_by = ?, deleted_at = ? WHERE seed_key = ?')
                ->execute([(int) ($im['is_deleted'] ?? 0), (int) ($im['is_pinned'] ?? 0), (string) ($im['deleted_by'] ?? ''), (string) ($im['deleted_at'] ?? ''), (string) $im['seed_key']]);
        } else {
            $insertDynamic($impPdo, 'company_inbox_messages', [$im], 'OR IGNORE');
        }
    }
    $validMsgIds = array_flip(array_column($impPdo->query('SELECT id FROM company_inbox_messages')->fetchAll(), 'id'));
    foreach (($snap['live_inbox_reads'] ?? []) as $ir) {
        $mid = (int) ($ir['message_id'] ?? 0);
        if ($mid > 0 && isset($validMsgIds[$mid])) {
            $insertDynamic($impPdo, 'company_inbox_reads', [$ir], 'OR REPLACE', []);
        }
    }

    // 8) Restore verified Guest Customer Accounts & Email Dispatches
    $insertDynamic($impPdo, 'guest_customer_accounts', $snap['live_guest_accounts'] ?? [], 'OR REPLACE');
    $insertDynamic($impPdo, 'guest_email_dispatches', $snap['live_guest_emails'] ?? [], 'OR IGNORE');

    // 9) Restore Accounting-customized Suppliers, Trip/Safari Cost Matrix & Staff Bonuses/Allowances
    $insertDynamic($impPdo, 'suppliers_directory', $snap['live_suppliers'] ?? [], 'OR REPLACE', []);
    $insertDynamic($impPdo, 'trip_supplier_cost_items', $snap['live_cost_items'] ?? [], 'OR REPLACE', []);
    foreach (($snap['live_cost_items'] ?? []) as $ci) {
        if (!empty($ci['linked_trip_code'])) {
            $impPdo->prepare('UPDATE master_trips_catalog SET supplier_code = ?, adult_cost_egp = ?, child_cost_egp = ?, infant_cost_egp = ? WHERE trip_code = ?')
                ->execute([(string) $ci['supplier_code'], (float) $ci['adult_cost_egp'], (float) $ci['child_cost_egp'], (float) $ci['infant_cost_egp'], (string) $ci['linked_trip_code']]);
        }
    }
    $insertDynamic($impPdo, 'trip_cost_audit_log', $snap['live_cost_audit'] ?? [], 'OR IGNORE');
    foreach (($snap['live_staff_bonuses'] ?? []) as $sb) {
        if (!empty($sb['seed_key'])) {
            $impPdo->prepare('UPDATE staff_bonuses_allowances SET status = ?, approved_by = ?, decided_at = ? WHERE seed_key = ?')
                ->execute([(string) $sb['status'], (string) $sb['approved_by'], (string) $sb['decided_at'], (string) $sb['seed_key']]);
        } else {
            $insertDynamic($impPdo, 'staff_bonuses_allowances', [$sb], 'OR IGNORE');
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
    // Staff log in by username; Verified Club Guests (GUEST_CUSTOMER) may log in by username OR email!
    $stmt = $pdo->prepare(
        "SELECT * FROM users
         WHERE (LOWER(username) = ? OR (email != '' AND LOWER(email) = ? AND role_code = 'GUEST_CUSTOMER'))
           AND is_active = 1
         ORDER BY CASE WHEN LOWER(username) = ? THEN 0 ELSE 1 END
         LIMIT 1"
    );
    $stmt->execute([$username, $username, $username]);
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
    // Public metadata of staff roles, job titles & avatars (strictly zero passwords, phones, emails, commission balances, or GUEST_CUSTOMER PII!)
    $rows = $pdo->query("SELECT u.username, u.display_name_en, u.display_name_ar, u.job_title_en, u.job_title_ar, u.avatar_url, u.role_code, u.branch_scope, u.vessel_scope, r.name_en AS role_en, r.name_ar AS role_ar, r.department
                         FROM users u JOIN roles r ON r.role_code = u.role_code
                         WHERE u.is_active = 1 AND u.role_code != 'GUEST_CUSTOMER' ORDER BY r.hierarchy_level DESC, u.id ASC")->fetchAll();
    echo json_encode(['ok' => true, 'accounts' => $rows, 'csrf_token' => csrf_token()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($method !== 'POST' && in_array($action, [
    'send_inbox_message', 'mark_inbox_read', 'react_inbox_message', 'toggle_pin_inbox_message', 'delete_inbox_message',
    'guest_create_booking', 'send_guest_login_email',
    'save_supplier', 'save_trip_cost', 'save_staff_bonus_allowance', 'decide_staff_bonus_allowance',
], true)) {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POST required']);
    exit;
}

$ctx = erp_auth_context($pdo);
if ($action === 'me') {
    if (!$ctx) {
        echo json_encode(['ok' => true, 'authenticated' => false, 'csrf_token' => csrf_token()]);
        exit;
    }
    $inbSnap = sc_erp_fetch_inbox_for_user($pdo, $ctx['user'], $ctx['perms'], 'all', 5);
    echo json_encode([
        'ok'                 => true,
        'authenticated'      => true,
        'user'               => $ctx['user'],
        'permissions'        => $ctx['perms'],
        'unread_inbox_count' => $inbSnap['unread_count'],
        'can_send_inbox'     => in_array('bi.view_executive', $ctx['perms'], true),
        'csrf_token'         => csrf_token(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
    $appExcRaw    = is_file(ROOT_DIR . '/data/app_excursions_catalog.php') ? (require ROOT_DIR . '/data/app_excursions_catalog.php') : [];
    $hotelData    = is_file(ROOT_DIR . '/data/hotel_zones.php') ? (require ROOT_DIR . '/data/hotel_zones.php') : ['zones' => [], 'hotels' => []];
    $GLOBALS['SC_LANG'] = $prevLang;

    $fx = sc_erp_fx_rates($pdo);
    $eurToEgp = $fx['EUR'] ?? 57.0;
    $usdToEgp = $fx['USD'] ?? 50.0;
    $gbpToEgp = $fx['GBP'] ?? 65.0;

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
                $childEur = str_contains($catKey, 'snork') ? round($priceEur * 0.5, 0) : $priceEur;
                $gal      = $galleries[$id] ?? [];
                $seo      = $seoExtras[$id] ?? [];
                $coverImg = sc_erp_resolve_asset_webp((string) ($it['img'] ?? ($gal['photos'][0] ?? '')));
                $photos   = [$coverImg];
                foreach (($gal['photos'] ?? []) as $gp) {
                    $wp = sc_erp_resolve_asset_webp((string) $gp);
                    if ($wp !== '' && !in_array($wp, $photos, true)) {
                        $photos[] = $wp;
                    }
                }
                $vidSrc    = !empty($gal['video']['src']) ? ('/assets/' . ltrim((string) $gal['video']['src'], '/')) : '/assets/videos/sea-college-sharm-red-sea-overview.mp4';
                $vidPoster = !empty($gal['video']['poster']) ? sc_erp_resolve_asset_webp((string) $gal['video']['poster']) : $coverImg;

                $nameLoc = $lang === 'ar' && !empty($it['name_ar']) ? (string) $it['name_ar'] : (string) ($it['name'] ?? $id);
                $descLoc = $lang === 'ar' && !empty($it['desc_ar']) ? (string) $it['desc_ar'] : (string) ($it['desc'] ?? '');

                $section  = (string) ($it['section'] ?? 'tours');
                $slug     = (string) ($it['slug'] ?? $id);
                $langPref = $lang === 'en' ? '' : ('/' . $lang);
                $publicUrl = 'https://seacollegediveclub.com' . $langPref . '/' . $section . '/' . $slug;

                $isThistlegorm = str_contains($id, 'thistlegorm');
                $adultCostEgp  = $isThistlegorm ? round(100.0 * $eurToEgp, 0) : round($priceEur * $eurToEgp * 0.35, 0);

                $specs = $seo['specs'] ?? [];
                $isEquip = str_starts_with($id, 'eq-');
                $isIns   = str_starts_with($id, 'ins-');
                $isIntro = str_starts_with($id, 'dsd-');
                if ($isEquip || $isIns) {
                    $resolvedGroup = 'equipment';
                    $resolvedLabel = $isIns
                        ? ($lang === 'ar' ? '🛡️ تأمين الغوص' : '🛡️ Dive Insurance')
                        : ($lang === 'ar' ? '🤿 إيجار معدات الغوص' : '🤿 Equipment Rental');
                } elseif ($isIntro) {
                    $resolvedGroup = 'intro-diving';
                    $resolvedLabel = $lang === 'ar' ? '🤿 غطس تجريبي للمبتدئين' : '🤿 Intro Diving';
                } elseif ($catKey === 'padi-courses' || $catKey === 'offers') {
                    $resolvedGroup = 'padi-courses';
                    $resolvedLabel = $catLabel;
                } elseif ($catKey === 'snorkeling') {
                    $resolvedGroup = 'snorkeling';
                    $resolvedLabel = $catLabel;
                } else {
                    $resolvedGroup = 'daily-diving';
                    $resolvedLabel = $catLabel;
                }

                $items[] = [
                    'id'                        => $id,
                    'code'                      => $id,
                    'section'                   => $section,
                    'slug'                      => $slug,
                    'category_group'            => $resolvedGroup,
                    'category_key'              => ($isEquip || $isIns) ? 'equipment' : $catKey,
                    'category_label'            => $resolvedLabel,
                    'group_title'               => $grpTitle,
                    'name'                      => $nameLoc,
                    'name_en'                   => (string) ($it['name'] ?? $id),
                    'name_ar'                   => (string) ($it['name_ar'] ?? $nameLoc),
                    'summary'                   => $descLoc,
                    'duration'                  => (string) ($it['duration'] ?? ($specs['duration'] ?? (($isEquip || $isIns) ? 'Per Dive Day' : 'Full day (08:00–16:30)'))),
                    'popular'                   => !empty($it['popular']),
                    'price_eur'                 => $priceEur,
                    'child_price_eur'           => $childEur,
                    'infant_price_eur'          => 0.0,
                    'price_usd'                 => round(($priceEur * $eurToEgp) / $usdToEgp, 2),
                    'price_gbp'                 => round(($priceEur * $eurToEgp) / $gbpToEgp, 2),
                    'price_egp'                 => round($priceEur * $eurToEgp, 0),
                    'supplier_code'             => $isThistlegorm ? 'AP_THISTLEGORM' : 'SEA_COLLEGE',
                    'supplier_name'             => $isThistlegorm ? 'SS Thistlegorm Marine Charter' : 'Sea College PADI 5* Dive Center',
                    'default_sale_mode'         => 'INSHEET',
                    'default_comm_pct'          => 0.10,
                    'default_supplier_cost_egp' => $adultCostEgp,
                    'adult_cost_egp'            => $adultCostEgp,
                    'child_cost_egp'            => round($adultCostEgp * 0.5, 0),
                    'is_app_excursion'          => false,
                    'cover_webp'                => $coverImg,
                    'photos'                    => array_slice($photos, 0, 5),
                    'video_src'                 => $vidSrc,
                    'video_poster'              => $vidPoster,
                    'public_url'                => $publicUrl,
                    'infographics'              => [
                        'min_age'       => (string) ($specs['min_age'] ?? (($isEquip || $isIns) ? 'Certified / Student Diver' : (str_contains($catKey, 'snork') ? 'All Ages (Infant <3 Free)' : '10+ Years'))),
                        'max_depth'     => (string) ($specs['max_depth'] ?? ($isIns ? 'Hyperbaric Chamber Coverage' : ($isEquip ? 'Sanitised Pro Scuba Gear' : (str_contains($id, 'owd') ? '18 m' : (str_contains($id, 'aowd') ? '30 m' : (str_contains($id, 'dsd') ? '6–12 m (1:1 Pro)' : 'Surface / 18–30 m')))))),
                        'certification' => (string) ($specs['prerequisites'] ?? (str_contains($id, 'dive-') ? 'Certified Diver' : 'No Experience Needed')),
                        'pickup'        => ($isEquip || $isIns) ? 'Prepared Ready on Boat / Dive Center' : 'Free Hotel Transfer (All 6 Sharm Zones)',
                        'included'      => is_array($catMeta['notes'][$lang] ?? null) ? array_slice($catMeta['notes'][$lang], 0, 2) : ['PADI Instructor / Guide, Hotel Transfers & Briefing'],
                    ],
                ];
            }
        }
    }

    $formatSkuRow = static function (array $sku, bool $isExcursion) use ($lang, $eurToEgp, $usdToEgp, $gbpToEgp): array {
        $pEur = (float) ($sku['price_eur'] ?? 0);
        $cEur = isset($sku['child_price_eur']) && $sku['child_price_eur'] !== null ? (float) $sku['child_price_eur'] : round($pEur * 0.5, 0);
        $iEur = (float) ($sku['infant_price_eur'] ?? 0.0);
        $aCost = (float) ($sku['adult_cost_egp'] ?? $sku['default_supplier_cost_egp'] ?? 0.0);
        $cCost = (float) ($sku['child_cost_egp'] ?? round($aCost * 0.5, 0));
        $cover = sc_erp_resolve_asset_webp((string) ($sku['cover_webp'] ?? ''));
        $photos = [$cover];
        foreach (($sku['gallery_photos'] ?? []) as $gp) {
            $wp = sc_erp_resolve_asset_webp((string) $gp);
            if ($wp !== '' && !in_array($wp, $photos, true)) {
                $photos[] = $wp;
            }
        }
        return [
            'id'                        => (string) $sku['id'],
            'code'                      => (string) ($sku['code'] ?? $sku['id']),
            'category_group'            => (string) ($sku['category_group'] ?? 'excursions'),
            'category_key'              => (string) ($sku['category_group'] ?? 'excursions'),
            'category_label'            => (string) ($sku['category_labels'][$lang] ?? $sku['category_labels']['en'] ?? 'Sharm Excursion'),
            'name'                      => (string) ($sku['names'][$lang] ?? $sku['names']['en'] ?? $sku['id']),
            'name_en'                   => (string) ($sku['names']['en'] ?? $sku['id']),
            'name_ar'                   => (string) ($sku['names']['ar'] ?? $sku['names']['en'] ?? $sku['id']),
            'summary'                   => (string) ($sku['summaries'][$lang] ?? $sku['summaries']['en'] ?? ''),
            'duration'                  => (string) ($sku['duration'] ?? 'Flexible'),
            'popular'                   => !empty($sku['popular']),
            'price_eur'                 => $pEur,
            'child_price_eur'           => $cEur,
            'infant_price_eur'          => $iEur,
            'price_usd'                 => round(($pEur * $eurToEgp) / $usdToEgp, 2),
            'price_gbp'                 => round(($pEur * $eurToEgp) / $gbpToEgp, 2),
            'price_egp'                 => round($pEur * $eurToEgp, 0),
            'supplier_code'             => (string) ($sku['supplier_code'] ?? (($sku['default_sale_mode'] ?? 'INSHEET') === 'OUT_SIDE' ? 'AP_PARTNER' : 'SEA_COLLEGE')),
            'supplier_name'             => (string) ($sku['supplier_name'] ?? 'Sea College Partner'),
            'default_sale_mode'         => (string) ($sku['default_sale_mode'] ?? 'INSHEET'),
            'default_comm_pct'          => (float) ($sku['default_comm_pct'] ?? 0.10),
            'default_supplier_cost_egp' => $aCost,
            'adult_cost_egp'            => $aCost,
            'child_cost_egp'            => $cCost,
            'is_app_excursion'          => $isExcursion,
            'cover_webp'                => $cover,
            'photos'                    => $photos,
            'video_src'                 => (string) ($sku['video_src'] ?? '/assets/videos/sea-college-tiran-dive-briefing-sharm.mp4'),
            'video_poster'              => sc_erp_resolve_asset_webp((string) ($sku['video_poster'] ?? $cover)),
            'public_url'                => 'https://seacollegediveclub.com' . ($lang === 'en' ? '' : '/' . $lang) . '/prices',
            'infographics'              => [
                'min_age'       => (string) ($sku['specs']['min_age'] ?? 'All Ages'),
                'max_depth'     => (string) ($sku['specs']['max_depth'] ?? 'Red Sea / Sinai'),
                'certification' => (string) ($sku['specs']['prerequisites'] ?? 'None'),
                'pickup'        => (string) ($sku['specs']['pickup'] ?? 'Included'),
                'included'      => $sku['included'][$lang] ?? $sku['included']['en'] ?? [],
            ],
        ];
    };

    $liveMtcByCode = [];
    foreach ($pdo->query('SELECT * FROM master_trips_catalog')->fetchAll() as $mr) {
        $liveMtcByCode[(string) $mr['trip_code']] = $mr;
    }
    $applyLiveMtcCost = static function (array &$arr, bool $allowSellUpdate) use ($liveMtcByCode, $eurToEgp, $usdToEgp, $gbpToEgp): void {
        foreach ($arr as &$row) {
            $tc = (string) ($row['code'] ?? $row['id'] ?? '');
            if ($tc !== '' && isset($liveMtcByCode[$tc])) {
                $m = $liveMtcByCode[$tc];
                $row['supplier_code']             = (string) ($m['supplier_code'] ?: $row['supplier_code']);
                $row['supplier_name']             = (string) ($m['supplier_name'] ?: $row['supplier_name']);
                $row['default_sale_mode']         = (string) ($m['sale_mode'] ?: $row['default_sale_mode']);
                $row['adult_cost_egp']            = (float) $m['adult_cost_egp'];
                $row['child_cost_egp']            = (float) $m['child_cost_egp'];
                $row['infant_cost_egp']           = (float) $m['infant_cost_egp'];
                $row['default_supplier_cost_egp'] = (float) $m['adult_cost_egp'];
                if ($allowSellUpdate && (float) $m['adult_sell_eur'] > 0 && (int) ($m['is_website_public'] ?? 0) === 0) {
                    $pE = (float) $m['adult_sell_eur'];
                    $row['price_eur']       = $pE;
                    $row['child_price_eur'] = (float) $m['child_sell_eur'];
                    $row['infant_price_eur']= (float) $m['infant_sell_eur'];
                    $row['price_egp']       = round($pE * $eurToEgp, 0);
                    $row['price_usd']       = round(($pE * $eurToEgp) / $usdToEgp, 2);
                    $row['price_gbp']       = round(($pE * $eurToEgp) / $gbpToEgp, 2);
                }
            }
        }
        unset($row);
    };

    $opSkusOut = [];
    foreach ($opSkusRaw as $sku) {
        $opSkusOut[] = $formatSkuRow($sku, false);
    }
    $excursionsOut = [];
    foreach ($appExcRaw as $exc) {
        $excursionsOut[] = $formatSkuRow($exc, true);
    }
    $applyLiveMtcCost($items, false);
    $applyLiveMtcCost($opSkusOut, true);
    $applyLiveMtcCost($excursionsOut, true);

    $safariVariants = [];
    $safFieldMap = [
        'SAF-QUAD-SINGLE'        => 'single_quad',
        'SAF-QUAD-DOUBLE'        => 'double_quad',
        'SAF-BUGGY-2PAX'         => 'buggy_2pax',
        'SAF-BUGGY-4PAX'         => 'buggy_4pax',
        'SAF-CAMEL-WITH-SAFARI'  => 'camel_safari',
        'SAF-CAMEL-1HR-TOUR'     => 'camel_1hr',
        'SAF-STAR-GAZING'        => 'star_gazing',
        'SAF-BEDOUIN-DINNER'     => 'bedouin_dinner',
        'SAF-VIP-BEDOUIN-DINNER' => 'vip_bedouin_dinner',
        'SAF-PRIVATE-GUIDE'      => 'private_guide',
    ];
    $safRows = $pdo->query("SELECT * FROM trip_supplier_cost_items WHERE category_group = 'DESERT_SAFARI' AND is_safari_component = 1 AND is_active = 1 AND item_code != 'SAF-ECO-TICKET-1USD' ORDER BY rowid ASC")->fetchAll();
    foreach ($safRows as $sr) {
        $ic = (string) $sr['item_code'];
        $uEur = (float) ($sr['unit_sell_eur'] > 0 ? $sr['unit_sell_eur'] : $sr['adult_sell_eur']);
        $safariVariants[] = [
            'item_code'     => $ic,
            'spec_field'    => $safFieldMap[$ic] ?? strtolower($ic),
            'name_en'       => (string) $sr['name_en'],
            'name_ar'       => (string) $sr['name_ar'],
            'pricing_basis' => (string) $sr['pricing_basis'],
            'capacity_pax'  => (int) $sr['capacity_pax'],
            'unit_sell_eur' => $uEur,
            'unit_sell_egp' => round($uEur * $eurToEgp, 0),
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

    $discPolicy = sc_erp_get_seller_discount_policy($pdo, (string) $ctx['user']['username']);

    echo json_encode([
        'ok'               => true,
        'lang'             => $lang,
        'languages'        => $supportedLangs,
        'site_url'         => 'https://seacollegediveclub.com',
        'fx_rates'         => $fx,
        'discount_policy'  => $discPolicy,
        'items'            => $items,
        'operational_skus' => array_merge($excursionsOut, $opSkusOut),
        'excursions'       => $excursionsOut,
        'safari_variants'  => $safariVariants,
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

    // Strict "Actual Done + 100% Paid" commission calculation & seller's own trips
    $brochureUrl = 'https://seacollegediveclub.com/s/' . rawurlencode($sSlug);
    $myTripsArgs = [$sUser, $sRep];
    $myTripsMonthSql = '';
    if ($monthFilter !== 'ALL') {
        $myTripsMonthSql = ' AND period_month = ?';
        $myTripsArgs[] = $monthFilter;
    }
    $myTripsSt = $pdo->prepare(
        "SELECT id, voucher_no, trip_date, period_month, hotel_name, room_no, nationality, pickup_time,
                trip_name, unified_trip_code, adults, children, infants, total_pax,
                booked_adults, booked_children, booked_infants, booked_total_egp,
                list_total_egp, discount_type, discount_value, discount_currency, discount_egp, discount_reason,
                divers_names, guest_phone, guest_email, guest_account_username,
                deposit_egp_equiv, collected_rest_egp, rest_egp_equiv,
                estimated_revenue_egp, actual_paid_total_egp, supplier_cost_egp,
                seller_commission_egp, commission_type, commission_status,
                boarding_status, payment_status, booking_state, workflow_stage,
                has_modification, modification_summary
         FROM reservations_fact
         WHERE (LOWER(seller_username) = LOWER(?) OR (seller_username = '' AND UPPER(sales_rep) = UPPER(?))) {$myTripsMonthSql}
         ORDER BY trip_date DESC, id DESC LIMIT 200"
    );
    $myTripsSt->execute($myTripsArgs);
    $discTripsCnt = 0;
    $discTotalEgp = 0.0;
    $myTrips = array_map(static function (array $r) use (&$discTripsCnt, &$discTotalEgp): array {
        $paid = round((float) ($r['deposit_egp_equiv'] ?? 0.0) + (float) ($r['collected_rest_egp'] ?? 0.0), 2);
        $rest = round((float) ($r['rest_egp_equiv'] ?? 0.0), 2);
        $tot  = round((float) (($r['estimated_revenue_egp'] ?? 0.0) > 0 ? $r['estimated_revenue_egp'] : ($paid + $rest)), 2);
        $dEgp = round((float) ($r['discount_egp'] ?? 0.0), 2);
        if ($dEgp > 0.0 && strtoupper((string) ($r['booking_state'] ?? 'EXECUTED')) === 'EXECUTED') {
            $discTripsCnt++;
            $discTotalEgp = round($discTotalEgp + $dEgp, 2);
        }
        $r['guest_name']        = (string) ($r['divers_names'] ?? '');
        $r['paid_egp_equiv']    = $paid;
        $r['actual_total_egp']  = $tot;
        $r['list_total_egp']    = round((float) (($r['list_total_egp'] ?? 0.0) > 0 ? $r['list_total_egp'] : ($tot + $dEgp)), 2);
        $r['discount_egp']      = $dEgp;
        $r['workflow_status']   = (string) (($r['workflow_stage'] ?? '') !== '' ? $r['workflow_stage'] : ($r['boarding_status'] ?? 'CONFIRMED'));
        $r['modification_note'] = (string) ($r['modification_summary'] ?? '');
        return $r;
    }, $myTripsSt->fetchAll());

    // Actual Done + Paid commission = EARNED_UNPAID + PAID_SETTLED
    $earnedComm = round((float) ($kpis['earned_commission_egp'] ?? 0.0) + (float) ($kpis['paid_commission_egp'] ?? 0.0), 2);
    $pendingComm = round((float) ($kpis['pending_commission_egp'] ?? 0.0), 2);

    // Mid-month allowances / installments requested or paid
    $advArgs = [$sUser];
    $advMonthSql = '';
    if ($monthFilter !== 'ALL') {
        $advMonthSql = ' AND period_month = ?';
        $advArgs[] = $monthFilter;
    }
    $advSt = $pdo->prepare("SELECT * FROM seller_commission_advances WHERE LOWER(seller_username) = LOWER(?) {$advMonthSql} ORDER BY id DESC LIMIT 60");
    $advSt->execute($advArgs);
    $advRows = array_map(static function (array $a): array {
        $a['request_date'] = (string) ($a['tx_date'] ?? substr((string) ($a['created_at'] ?? ''), 0, 10));
        return $a;
    }, $advSt->fetchAll());

    $advPaidEgp = 0.0;
    $advReqEgp  = 0.0;
    foreach ($advRows as $ar) {
        if (in_array((string) $ar['status'], ['APPROVED_PAID', 'PAID'], true)) {
            $advPaidEgp += (float) $ar['amount_egp'];
        } elseif ((string) $ar['status'] === 'REQUESTED') {
            $advReqEgp += (float) $ar['amount_egp'];
        }
    }
    $settledAlreadyEgp = round((float) ($kpis['paid_commission_egp'] ?? 0.0) + $advPaidEgp, 2);
    $netDueNowEgp      = round(max(0.0, $earnedComm - $settledAlreadyEgp), 2);

    // Target progress, discount policy & gamification awards
    $targetCfg  = sc_erp_get_seller_target($pdo, $sUser);
    $discPolicy = sc_erp_get_seller_discount_policy($pdo, $sUser);
    $awardsAll  = sc_erp_compute_best_seller_awards($pdo);
    $activeMonthForTarget = $monthFilter !== 'ALL' ? $monthFilter : $awardsAll['month_key'];

    $tStatSt = $pdo->prepare(
        "SELECT COUNT(*) AS trips_cnt,
                COALESCE(SUM(total_pax), 0) AS pax_cnt,
                ROUND(COALESCE(SUM(CASE WHEN actual_paid_total_egp > 0 THEN actual_paid_total_egp ELSE estimated_revenue_egp END), 0), 2) AS sales_egp
         FROM reservations_fact
         WHERE (LOWER(seller_username) = LOWER(?) OR (seller_username = '' AND UPPER(sales_rep) = UPPER(?)))
           AND booking_state = 'EXECUTED' AND period_month = ?"
    );
    $tStatSt->execute([$sUser, $sRep, $activeMonthForTarget]);
    $tStat = $tStatSt->fetch() ?: ['trips_cnt' => 0, 'pax_cnt' => 0, 'sales_egp' => 0];
    $actualMonthSales = (float) ($tStat['sales_egp'] ?? 0.0);
    $actualMonthTrips = (int) ($tStat['trips_cnt'] ?? 0);
    $pctMonthly = $targetCfg['monthly_target_egp'] > 0
        ? round(min(999.0, ($actualMonthSales / $targetCfg['monthly_target_egp']) * 100.0), 1)
        : 0.0;

    $myAwards = [];
    foreach ([
        'daily_best'   => ['code' => 'BEST_DAILY',   'icon' => '🥇', 'title_en' => 'Star Seller of the Day',   'title_ar' => 'نجم مبيعات اليوم'],
        'weekly_best'  => ['code' => 'BEST_WEEKLY',  'icon' => '🏆', 'title_en' => 'Weekly Sales Champion',    'title_ar' => 'بطل مبيعات الأسبوع'],
        'monthly_best' => ['code' => 'BEST_MONTHLY', 'icon' => '👑', 'title_en' => 'Seller of the Month MVP',  'title_ar' => 'أفضل مسوق في الشهر'],
        'yearly_best'  => ['code' => 'BEST_YEARLY',  'icon' => '💎', 'title_en' => 'Annual Legend Award',      'title_ar' => 'أسطورة مبيعات العام'],
    ] as $awKey => $meta) {
        $win = $awardsAll[$awKey] ?? null;
        if ($win && strtolower((string) $win['username']) === strtolower($sUser)) {
            $myAwards[] = array_merge($meta, [
                'sales_egp'   => (float) $win['actual_sales_egp'],
                'trips_count' => (int) $win['trips_count'],
            ]);
        }
    }
    $xpPoints = (int) round(($actualMonthTrips * 25) + ($actualMonthSales / 500));
    $levelTitleEn = $xpPoints >= 2500 ? '💎 Diamond Concierge' : ($xpPoints >= 1200 ? '👑 Gold Reef Pro' : ($xpPoints >= 500 ? '🥈 Silver Diver Rep' : '🌊 Rising Star'));
    $levelTitleAr = $xpPoints >= 2500 ? '💎 كونسيرج ماسي' : ($xpPoints >= 1200 ? '👑 محترف ذهبي' : ($xpPoints >= 500 ? '🥈 مسوق فضي' : '🌊 نجم صاعد'));

    // Seller notifications
    $nSt = $pdo->prepare('SELECT * FROM user_notifications WHERE LOWER(recipient_username) = LOWER(?) ORDER BY id DESC LIMIT 30');
    $nSt->execute([$sUser]);
    $notifRows = array_map(static function (array $nr): array {
        $nr['title']              = (string) ($nr['title_en'] ?? '');
        $nr['body']               = (string) ($nr['body_en'] ?? '');
        $nr['related_voucher_no'] = (string) ($nr['voucher_no'] ?? '');
        return $nr;
    }, $nSt->fetchAll());
    $unreadCnt = 0;
    foreach ($notifRows as $nr) {
        if ((int) ($nr['is_read'] ?? 0) === 0) {
            $unreadCnt++;
        }
    }

    $kpis['actual_done_paid_commission_egp'] = $earnedComm;
    $kpis['waiting_rest_or_trip_comm_egp']   = $pendingComm;
    $kpis['allowances_paid_egp']             = round($advPaidEgp, 2);
    $kpis['allowances_requested_egp']        = round($advReqEgp, 2);
    $kpis['net_due_now_egp']                 = $netDueNowEgp;
    $kpis['earned_unpaid_egp']               = round((float) ($kpis['earned_commission_egp'] ?? 0.0), 2);
    $kpis['paid_settled_egp']                = round((float) ($kpis['paid_commission_egp'] ?? 0.0), 2);
    $kpis['discounts_granted_egp']           = $discTotalEgp;
    $kpis['discounted_trips_count']          = $discTripsCnt;

    $brochureLocLinks = [];
    foreach (['en', 'ar', 'ru', 'de', 'it', 'fr', 'pl', 'zh'] as $lgCode) {
        $brochureLocLinks[$lgCode] = $lgCode === 'en'
            ? ('https://seacollegediveclub.com/s/' . rawurlencode($sSlug))
            : ('https://seacollegediveclub.com/' . $lgCode . '/s/' . rawurlencode($sSlug));
    }

    echo json_encode([
        'ok'                         => true,
        'month'                      => $monthFilter,
        'discount_policy'            => $discPolicy,
        'discounts_summary'          => [
            'discounted_trips_count' => $discTripsCnt,
            'total_discount_egp'     => $discTotalEgp,
        ],
        'seller'                     => [
            'username'      => $sUser,
            'referral_code' => $sSlug,
            'qr_url'        => $qrUrl,
            'brochure_url'  => $brochureUrl,
        ],
        'seller_profile'             => [
            'username'                 => $sUser,
            'display_name_en'          => (string) ($targetSeller['display_name_en'] ?? $sUser),
            'display_name_ar'          => (string) ($targetSeller['display_name_ar'] ?? $sUser),
            'rep_scope'                => $sRep,
            'branch_scope'             => (string) ($targetSeller['branch_scope'] ?? 'CYRENE'),
            'referral_code'            => $sSlug,
            'qr_url'                   => $qrUrl,
            'referral_url'             => $qrUrl,
            'share_brochure_url'       => $brochureUrl,
            'localized_urls'           => $langLinks,
            'localized_brochure_urls'  => $brochureLocLinks,
            'default_comm_insheet_pct' => (float) ($targetSeller['default_comm_insheet_pct'] ?? 0.10),
            'default_comm_outside_pct' => (float) ($targetSeller['default_comm_outside_pct'] ?? 0.25),
            'phone'                    => (string) ($targetSeller['phone'] ?? ''),
            'email'                    => (string) ($targetSeller['email'] ?? ''),
            'avatar_url'               => (string) ($targetSeller['avatar_url'] ?? ''),
            'xp_points'                => $xpPoints,
            'level_title'              => $levelTitleEn,
            'level_title_en'           => $levelTitleEn,
            'level_title_ar'           => $levelTitleAr,
            'awards'                   => $myAwards,
        ],
        'kpis'                       => $kpis,
        'actual_due_breakdown'       => [
            'actual_done_paid_commission_egp'  => $earnedComm,
            'waiting_rest_or_trip_comm_egp'    => $pendingComm,
            'settled_and_allowances_egp'       => $settledAlreadyEgp,
            'allowances_paid_egp'              => round($advPaidEgp, 2),
            'allowances_requested_egp'         => round($advReqEgp, 2),
            'requested_pending_allowances_egp' => round($advReqEgp, 2),
            'net_due_now_egp'                  => $netDueNowEgp,
        ],
        'target_progress'            => [
            'is_active'            => $targetCfg['is_active'],
            'period_month'         => $activeMonthForTarget,
            'daily_target_egp'     => $targetCfg['daily_target_egp'],
            'weekly_target_egp'    => $targetCfg['weekly_target_egp'],
            'monthly_target_egp'   => $targetCfg['monthly_target_egp'],
            'target_daily_egp'     => $targetCfg['daily_target_egp'],
            'target_weekly_egp'    => $targetCfg['weekly_target_egp'],
            'target_monthly_egp'   => $targetCfg['monthly_target_egp'],
            'monthly_trips_target' => $targetCfg['monthly_trips_target'],
            'actual_sales_egp'     => $actualMonthSales,
            'achieved_monthly_egp' => $actualMonthSales,
            'actual_trips'         => $actualMonthTrips,
            'pct_monthly'          => $pctMonthly,
        ],
        'awards'                     => $myAwards,
        'my_trips'                   => $myTrips,
        'advances_rows'              => $advRows,
        'notifications'              => $notifRows,
        'unread_notifications_count' => $unreadCnt,
        'ledger_rows'                => $ledgerRows,
        'attributed_leads'           => $attributedLeads,
        'all_sellers'                => $allSellers,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---------------- LIVE IN-APP NOTIFICATIONS (`notifications`) ---------------- */
if ($action === 'notifications') {
    if (!$ctx) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Authentication required']);
        exit;
    }
    $uName = (string) $ctx['user']['username'];
    $roleTag = 'ROLE:' . (string) $ctx['user']['role_code'];
    $nSt = $pdo->prepare(
        'SELECT * FROM user_notifications
         WHERE LOWER(recipient_username) = LOWER(?) OR recipient_username = ?
         ORDER BY id DESC LIMIT 40'
    );
    $nSt->execute([$uName, $roleTag]);
    $rows = array_map(static function (array $r): array {
        $r['title']              = (string) ($r['title_en'] ?? '');
        $r['body']               = (string) ($r['body_en'] ?? '');
        $r['related_voucher_no'] = (string) ($r['voucher_no'] ?? '');
        return $r;
    }, $nSt->fetchAll());
    $unread = 0;
    foreach ($rows as $r) {
        if ((int) ($r['is_read'] ?? 0) === 0) {
            $unread++;
        }
    }
    $inbQuick = sc_erp_fetch_inbox_for_user($pdo, $ctx['user'], $ctx['perms'], 'all', 5);
    echo json_encode([
        'ok'                 => true,
        'unread_count'       => $unread,
        'unread_inbox_count' => $inbQuick['unread_count'],
        'notifications'      => $rows,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---------------- COMPANY-WIDE ANNOUNCEMENTS, CONGRATULATIONS & DIRECT INBOX (`inbox_messages`) ---------------- */
if ($action === 'inbox_messages') {
    if (!$ctx) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Authentication required']);
        exit;
    }
    $filter  = strtolower(trim((string) ($_GET['filter'] ?? 'all')));
    $limit   = max(1, min(100, (int) ($_GET['limit'] ?? 50)));
    $canSend = in_array('bi.view_executive', $ctx['perms'], true);
    $feed    = sc_erp_fetch_inbox_for_user($pdo, $ctx['user'], $ctx['perms'], $filter, $limit);

    $recipientsDir = ['users' => [], 'roles' => [], 'branches' => []];
    if ($canSend) {
        $recipientsDir['users'] = $pdo->query(
            "SELECT username, display_name_en, display_name_ar, role_code, branch_scope, avatar_url
             FROM users WHERE is_active = 1 AND role_code != 'GUEST_CUSTOMER' ORDER BY role_code ASC, display_name_en ASC"
        )->fetchAll();
        $recipientsDir['roles'] = $pdo->query(
            "SELECT role_code, name_en, name_ar, department FROM roles WHERE role_code != 'GUEST_CUSTOMER' ORDER BY hierarchy_level DESC"
        )->fetchAll();
        $recipientsDir['branches'] = $pdo->query(
            'SELECT code, name_en, name_ar FROM branches WHERE is_active = 1 ORDER BY code ASC'
        )->fetchAll();
    }

    echo json_encode([
        'ok'                   => true,
        'can_send'             => $canSend,
        'can_pin_delete'       => $canSend,
        'unread_count'         => $feed['unread_count'],
        'pinned_banner'        => $feed['pinned_banner'],
        'allowed_reactions'    => sc_erp_allowed_inbox_reactions(),
        'recipients_directory' => $recipientsDir,
        'messages'             => $feed['messages'],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---------------- OWNER & ACCOUNTING MULTI-HORIZON SELLER PERFORMANCE & AWARDS BI ---------------- */
if ($action === 'seller_performance_bi') {
    erp_require_perm($ctx, 'bi.view_executive', 'gl.view_all_ledgers');
    $horizon = strtolower(trim((string) ($_GET['horizon'] ?? 'monthly')));
    if (!in_array($horizon, ['daily', 'weekly', 'monthly', 'yearly', 'custom'], true)) {
        $horizon = 'monthly';
    }

    $awards = sc_erp_compute_best_seller_awards($pdo);
    $anchor = $awards['anchor_date'];

    $reqFrom = trim((string) ($_GET['date_from'] ?? ''));
    $reqTo   = trim((string) ($_GET['date_to'] ?? ''));

    if ($horizon === 'custom' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $reqFrom) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $reqTo)) {
        $dateFrom = min($reqFrom, $reqTo);
        $dateTo   = max($reqFrom, $reqTo);
    } elseif ($horizon === 'daily') {
        $dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $reqFrom) ? $reqFrom : $anchor;
        $dateTo   = $dateFrom;
    } elseif ($horizon === 'weekly') {
        $dateTo   = $anchor;
        $dateFrom = $awards['week_from'];
    } elseif ($horizon === 'yearly') {
        $yr = preg_match('/^\d{4}$/', $reqFrom) ? $reqFrom : $awards['year_key'];
        $dateFrom = $yr . '-01-01';
        $dateTo   = $yr . '-12-31';
    } else {
        $mo = preg_match('/^\d{4}-\d{2}$/', $reqFrom) ? $reqFrom : $awards['month_key'];
        $dateFrom = $mo . '-01';
        $dateTo   = $mo . '-31';
    }

    $fmtWin = static function (?array $w, string $icon, string $wStart, string $wEnd): ?array {
        if (!$w) {
            return null;
        }
        return [
            'username'        => (string) $w['username'],
            'display_name_en' => (string) $w['display_name_en'],
            'display_name_ar' => (string) $w['display_name_ar'],
            'rep_code'        => (string) ($w['rep_scope'] ?: $w['username']),
            'icon'            => $icon,
            'sales_egp'       => (float) $w['actual_sales_egp'],
            'trips_count'     => (int) $w['trips_count'],
            'pax'             => (int) $w['total_pax'],
            'window_start'    => $wStart,
            'window_end'      => $wEnd,
        ];
    };
    $awards['winners'] = [
        'daily'   => $fmtWin($awards['daily_best'] ?? null,   '🥇', $anchor, $anchor),
        'weekly'  => $fmtWin($awards['weekly_best'] ?? null,  '🏆', $awards['week_from'], $anchor),
        'monthly' => $fmtWin($awards['monthly_best'] ?? null, '👑', $awards['month_key'] . '-01', $awards['month_key'] . '-31'),
        'yearly'  => $fmtWin($awards['yearly_best'] ?? null,  '💎', $awards['year_key'] . '-01-01', $awards['year_key'] . '-12-31'),
    ];

    $globalTarget = sc_erp_get_seller_target($pdo, '__GLOBAL__');
    $globalDiscPolicy = sc_erp_get_seller_discount_policy($pdo, '__GLOBAL__');
    $globalTarget['target_daily_egp']   = $globalTarget['daily_target_egp'];
    $globalTarget['target_weekly_egp']  = $globalTarget['weekly_target_egp'];
    $globalTarget['target_monthly_egp'] = $globalTarget['monthly_target_egp'];
    $globalTarget['is_discount_enabled']= $globalDiscPolicy['is_enabled'];
    $globalTarget['max_discount_pct']   = $globalDiscPolicy['max_discount_pct'];
    $globalTarget['max_discount_eur']   = $globalDiscPolicy['max_discount_eur'];

    $sellersRows = $pdo->query(
        "SELECT username, display_name_en, display_name_ar, rep_scope, branch_scope, referral_code, avatar_url
         FROM users WHERE role_code = 'SALES_REP' AND is_active = 1 ORDER BY id ASC"
    )->fetchAll();

    $leaderboard = [];
    $totTrips = 0;
    $totPax   = 0;
    $totSales = 0.0;
    $totColl  = 0.0;
    $totDisc  = 0.0;

    foreach ($sellersRows as $sr) {
        $u = (string) $sr['username'];
        $rep = (string) $sr['rep_scope'];

        $rSt = $pdo->prepare(
            "SELECT SUM(CASE WHEN booking_state = 'EXECUTED' THEN 1 ELSE 0 END) AS trips_sold,
                    SUM(CASE WHEN booking_state LIKE 'CANCEL%' THEN 1 ELSE 0 END) AS trips_cancelled,
                    SUM(CASE WHEN booking_state = 'EXECUTED' AND discount_egp > 0 THEN 1 ELSE 0 END) AS discounted_trips,
                    COALESCE(SUM(CASE WHEN booking_state = 'EXECUTED' THEN total_pax ELSE 0 END), 0) AS actual_pax,
                    COALESCE(SUM(CASE WHEN booking_state = 'EXECUTED' THEN adults ELSE 0 END), 0) AS actual_adults,
                    COALESCE(SUM(CASE WHEN booking_state = 'EXECUTED' THEN children ELSE 0 END), 0) AS actual_children,
                    COALESCE(SUM(CASE WHEN booking_state = 'EXECUTED' THEN infants ELSE 0 END), 0) AS actual_infants,
                    ROUND(COALESCE(SUM(CASE WHEN booking_state = 'EXECUTED' THEN (CASE WHEN actual_paid_total_egp > 0 THEN actual_paid_total_egp ELSE estimated_revenue_egp END) ELSE 0 END), 0), 2) AS actual_total_egp,
                    ROUND(COALESCE(SUM(CASE WHEN booking_state = 'EXECUTED' THEN discount_egp ELSE 0 END), 0), 2) AS discounts_granted_egp,
                    ROUND(COALESCE(SUM(CASE WHEN booking_state = 'EXECUTED' THEN rest_egp_equiv ELSE 0 END), 0), 2) AS open_rest_egp,
                    ROUND(COALESCE(SUM(CASE WHEN booking_state = 'EXECUTED' THEN supplier_cost_egp ELSE 0 END), 0), 2) AS supplier_cost_egp
             FROM reservations_fact
             WHERE (LOWER(seller_username) = LOWER(?) OR (seller_username = '' AND UPPER(sales_rep) = UPPER(?)))
               AND trip_date BETWEEN ? AND ?"
        );
        $rSt->execute([$u, $rep, $dateFrom, $dateTo]);
        $rAgg = $rSt->fetch() ?: [];

        $cSt = $pdo->prepare(
            "SELECT ROUND(COALESCE(SUM(CASE WHEN status IN ('EARNED_UNPAID', 'PAID_SETTLED') THEN commission_egp ELSE 0 END), 0), 2) AS actual_due_comm,
                    ROUND(COALESCE(SUM(CASE WHEN status = 'PENDING_EXECUTION' THEN commission_egp ELSE 0 END), 0), 2) AS pending_comm,
                    ROUND(COALESCE(SUM(CASE WHEN status = 'PAID_SETTLED' THEN commission_egp ELSE 0 END), 0), 2) AS settled_comm
             FROM seller_commissions_ledger
             WHERE LOWER(seller_username) = LOWER(?) AND tx_date BETWEEN ? AND ?"
        );
        $cSt->execute([$u, $dateFrom, $dateTo]);
        $cAgg = $cSt->fetch() ?: [];

        $aSt = $pdo->prepare(
            "SELECT ROUND(COALESCE(SUM(CASE WHEN status IN ('APPROVED_PAID', 'PAID') THEN amount_egp ELSE 0 END), 0), 2) AS adv_paid
             FROM seller_commission_advances
             WHERE LOWER(seller_username) = LOWER(?) AND tx_date BETWEEN ? AND ?"
        );
        $aSt->execute([$u, $dateFrom, $dateTo]);
        $advPaid = (float) ($aSt->fetchColumn() ?: 0.0);

        $tCfg = sc_erp_get_seller_target($pdo, $u);
        $targetRef = match ($horizon) {
            'daily'  => $tCfg['daily_target_egp'],
            'weekly' => $tCfg['weekly_target_egp'],
            'yearly' => round($tCfg['monthly_target_egp'] * 12.0, 0),
            default  => $tCfg['monthly_target_egp'],
        };
        $actualTotal = (float) ($rAgg['actual_total_egp'] ?? 0.0);
        $discGranted = (float) ($rAgg['discounts_granted_egp'] ?? 0.0);
        $openRest    = (float) ($rAgg['open_rest_egp'] ?? 0.0);
        $collected   = round(max(0.0, $actualTotal - $openRest), 2);
        $dueComm     = (float) ($cAgg['actual_due_comm'] ?? 0.0);
        $settledTot  = round((float) ($cAgg['settled_comm'] ?? 0.0) + $advPaid, 2);

        $totTrips += (int) ($rAgg['trips_sold'] ?? 0);
        $totPax   += (int) ($rAgg['actual_pax'] ?? 0);
        $totSales += $actualTotal;
        $totColl  += $collected;
        $totDisc  += $discGranted;

        $badges = [];
        $awardObjs = [];
        if (($awards['daily_best']['username'] ?? '') === $u) {
            $badges[] = '🥇 Daily #1';
            $awardObjs[] = ['icon' => '🥇', 'title_en' => 'Best Seller Today'];
        }
        if (($awards['weekly_best']['username'] ?? '') === $u) {
            $badges[] = '🏆 Weekly #1';
            $awardObjs[] = ['icon' => '🏆', 'title_en' => 'Best Seller This Week'];
        }
        if (($awards['monthly_best']['username'] ?? '') === $u) {
            $badges[] = '👑 Monthly #1';
            $awardObjs[] = ['icon' => '👑', 'title_en' => 'Best Seller This Month'];
        }
        if (($awards['yearly_best']['username'] ?? '') === $u) {
            $badges[] = '💎 Yearly #1';
            $awardObjs[] = ['icon' => '💎', 'title_en' => 'Best Seller of the Year'];
        }

        $leaderboard[] = [
            'username'               => $u,
            'display_name_en'        => $sr['display_name_en'],
            'display_name_ar'        => $sr['display_name_ar'],
            'avatar_url'             => $sr['avatar_url'],
            'rep_scope'              => $rep,
            'branch_scope'           => $sr['branch_scope'],
            'referral_code'          => $sr['referral_code'],
            'trips_sold'             => (int) ($rAgg['trips_sold'] ?? 0),
            'trips_cancelled'        => (int) ($rAgg['trips_cancelled'] ?? 0),
            'discounted_trips'       => (int) ($rAgg['discounted_trips'] ?? 0),
            'discounts_granted_egp'  => $discGranted,
            'actual_pax'             => (int) ($rAgg['actual_pax'] ?? 0),
            'actual_adults'          => (int) ($rAgg['actual_adults'] ?? 0),
            'actual_children'        => (int) ($rAgg['actual_children'] ?? 0),
            'actual_infants'         => (int) ($rAgg['actual_infants'] ?? 0),
            'actual_total_egp'       => $actualTotal,
            'actual_collected_egp'   => $collected,
            'actual_paid_egp'        => $collected,
            'open_rest_egp'          => $openRest,
            'supplier_cost_egp'      => (float) ($rAgg['supplier_cost_egp'] ?? 0.0),
            'actual_due_comm_egp'    => $dueComm,
            'pending_comm_egp'       => (float) ($cAgg['pending_comm'] ?? 0.0),
            'allowances_paid_egp'    => round($advPaid, 2),
            'net_payable_comm_egp'   => round(max(0.0, $dueComm - $settledTot), 2),
            'target_egp'             => $targetRef,
            'target_pct'             => $targetRef > 0 ? round(($actualTotal / $targetRef) * 100.0, 1) : 0.0,
            'badges'                 => $badges,
            'awards'                 => $awardObjs,
        ];
    }

    usort($leaderboard, static fn($a, $b) => ($b['actual_total_egp'] <=> $a['actual_total_egp']) ?: ($b['trips_sold'] <=> $a['trips_sold']));
    foreach ($leaderboard as $idx => &$lbRow) {
        $lbRow['rank'] = $idx + 1;
    }
    unset($lbRow);

    $modAudit = array_map(static function (array $m): array {
        $m['old_adults']       = (int) ($m['prev_adults'] ?? 0);
        $m['old_children']     = (int) ($m['prev_children'] ?? 0);
        $m['old_infants']      = (int) ($m['prev_infants'] ?? 0);
        $m['old_total_egp']    = (float) ($m['prev_total_egp'] ?? 0.0);
        $m['extra_charge_egp'] = (float) ($m['delta_total_egp'] ?? 0.0);
        $m['reason']           = (string) ($m['reason_notes'] ?? '');
        $m['actor_username']   = (string) ($m['changed_by_username'] ?? '');
        return $m;
    }, $pdo->query('SELECT * FROM voucher_change_audit_log ORDER BY id DESC LIMIT 50')->fetchAll());

    $pendAllow = array_map(static function (array $a): array {
        $a['request_date'] = (string) ($a['tx_date'] ?? substr((string) ($a['created_at'] ?? ''), 0, 10));
        $a['rep_code']     = (string) ($a['rep_scope'] ?? $a['seller_username'] ?? '');
        return $a;
    }, $pdo->query(
        "SELECT a.*, u.display_name_en, u.display_name_ar
         FROM seller_commission_advances a
         LEFT JOIN users u ON LOWER(u.username) = LOWER(a.seller_username)
         ORDER BY CASE WHEN a.status='REQUESTED' THEN 0 ELSE 1 END, a.id DESC LIMIT 40"
    )->fetchAll());

    $pipelineRows = array_map(static function (array $r): array {
        $paid = round((float) ($r['deposit_egp_equiv'] ?? 0.0) + (float) ($r['collected_rest_egp'] ?? 0.0), 2);
        $rest = round((float) ($r['rest_egp_equiv'] ?? 0.0), 2);
        $tot  = round((float) (($r['estimated_revenue_egp'] ?? 0.0) > 0 ? $r['estimated_revenue_egp'] : ($paid + $rest)), 2);
        $r['guest_name']        = (string) ($r['divers_names'] ?? '');
        $r['paid_egp_equiv']    = $paid;
        $r['actual_total_egp']  = $tot;
        $r['workflow_status']   = (string) (($r['workflow_stage'] ?? '') !== '' ? $r['workflow_stage'] : ($r['boarding_status'] ?? 'CONFIRMED'));
        $r['modification_note'] = (string) ($r['modification_summary'] ?? '');
        return $r;
    }, $pdo->query(
        "SELECT id, voucher_no, trip_date, hotel_name, room_no, divers_names, trip_name,
                adults, children, infants, total_pax, deposit_egp_equiv, collected_rest_egp,
                rest_egp_equiv, estimated_revenue_egp, list_total_egp, discount_type, discount_value, discount_currency, discount_egp, discount_reason,
                sales_rep, seller_username,
                boarding_status, payment_status, booking_state, workflow_stage,
                has_modification, modification_summary
         FROM reservations_fact
         WHERE source_file IN ('LIVE_MOBILE_APP', 'LIVE_WEBSITE_FORM', 'SELLER_SHARE_LINK')
            OR has_modification = 1
         ORDER BY id DESC LIMIT 60"
    )->fetchAll());

    echo json_encode([
        'ok'                          => true,
        'horizon'                     => $horizon,
        'date_from'                   => $dateFrom,
        'date_to'                     => $dateTo,
        'totals'                      => [
            'trips_sold'            => $totTrips,
            'actual_pax'            => $totPax,
            'actual_sales_egp'      => round($totSales, 2),
            'collected_egp'         => round($totColl, 2),
            'discounts_granted_egp' => round($totDisc, 2),
        ],
        'awards'                      => $awards,
        'target_config'               => $globalTarget,
        'discount_policy'             => $globalDiscPolicy,
        'sellers_leaderboard'         => $leaderboard,
        'voucher_modifications_audit' => $modAudit,
        'commission_allowances'       => $pendAllow,
        'live_confirmation_pipeline'  => $pipelineRows,
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

    $roleTag = 'ROLE:' . (string) $user['role_code'];
    $unSt = $pdo->prepare('SELECT * FROM user_notifications WHERE LOWER(recipient_username) = LOWER(?) OR recipient_username = ? ORDER BY id DESC LIMIT 30');
    $unSt->execute([(string) $user['username'], $roleTag]);
    $wsNotifs = array_map(static function (array $r): array {
        $r['title']              = (string) ($r['title_en'] ?? '');
        $r['body']               = (string) ($r['body_en'] ?? '');
        $r['related_voucher_no'] = (string) ($r['voucher_no'] ?? '');
        return $r;
    }, $unSt->fetchAll());
    $wsUnread = 0;
    foreach ($wsNotifs as $wn) {
        if ((int) ($wn['is_read'] ?? 0) === 0) {
            $wsUnread++;
        }
    }
    $out['notifications']              = $wsNotifs;
    $out['unread_notifications_count'] = $wsUnread;

    $wsInbox = sc_erp_fetch_inbox_for_user($pdo, $user, $perms, 'all', 12);
    $out['unread_inbox_count']  = $wsInbox['unread_count'];
    $out['can_manage_inbox']    = in_array('bi.view_executive', $perms, true);
    $out['inbox_pinned_banner'] = $wsInbox['pinned_banner'];
    $out['inbox_preview']       = $wsInbox['messages'];

    $enrichVoucherRow = static function (array $r): array {
        $paid = round((float) ($r['deposit_egp_equiv'] ?? 0.0) + (float) ($r['collected_rest_egp'] ?? 0.0), 2);
        $rest = round((float) ($r['rest_egp_equiv'] ?? 0.0), 2);
        $tot  = round((float) (($r['estimated_revenue_egp'] ?? 0.0) > 0 ? $r['estimated_revenue_egp'] : ($paid + $rest)), 2);
        $r['guest_name']        = (string) ($r['divers_names'] ?? '');
        $r['paid_egp_equiv']    = $paid;
        $r['actual_total_egp']  = $tot;
        $r['workflow_status']   = (string) (($r['workflow_stage'] ?? '') !== '' ? $r['workflow_stage'] : ($r['boarding_status'] ?? 'CONFIRMED'));
        $r['modification_note'] = (string) ($r['modification_summary'] ?? '');
        return $r;
    };

    if (in_array('ops.view_manifests', $perms, true) || in_array('jetty.scan_boarding_collect_rest', $perms, true) || in_array('gl.view_all_ledgers', $perms, true) || in_array('bi.view_executive', $perms, true)) {
        $out['vouchers'] = array_map($enrichVoucherRow, $pdo->query('SELECT * FROM reservations_fact ORDER BY CASE WHEN source_file IN ("LIVE_MOBILE_APP","LIVE_WEBSITE_FORM","SELLER_SHARE_LINK") THEN 0 ELSE 1 END, trip_date DESC, id DESC LIMIT 90')->fetchAll());
        $modRows = array_map(static function (array $m): array {
            $m['old_adults']       = (int) ($m['prev_adults'] ?? 0);
            $m['old_children']     = (int) ($m['prev_children'] ?? 0);
            $m['old_infants']      = (int) ($m['prev_infants'] ?? 0);
            $m['old_total_egp']    = (float) ($m['prev_total_egp'] ?? 0.0);
            $m['extra_charge_egp'] = (float) ($m['delta_total_egp'] ?? 0.0);
            $m['reason']           = (string) ($m['reason_notes'] ?? '');
            $m['actor_username']   = (string) ($m['changed_by_username'] ?? '');
            return $m;
        }, $pdo->query('SELECT * FROM voucher_change_audit_log ORDER BY id DESC LIMIT 40')->fetchAll());
        $out['voucher_modifications'] = $modRows;
        $out['voucher_audit_recent']  = $modRows;
    } elseif (in_array('sales.view_own_sales', $perms, true)) {
        // Strict horizontal isolation between sellers!
        $uLower = strtolower((string) $user['username']);
        $repUp  = strtoupper(trim((string) ($user['rep_scope'] ?: $user['username'])));
        $whereSeller = '(LOWER(seller_username) = ? OR (seller_username = "" AND UPPER(sales_rep) = ?))';
        $vSt = $pdo->prepare("SELECT * FROM reservations_fact WHERE {$whereSeller} ORDER BY CASE WHEN source_file IN ('LIVE_MOBILE_APP','LIVE_WEBSITE_FORM','SELLER_SHARE_LINK') THEN 0 ELSE 1 END, trip_date DESC, id DESC LIMIT 80");
        $vSt->execute([$uLower, $repUp]);
        $myRows = array_map($enrichVoucherRow, $vSt->fetchAll());
        $sSt = $pdo->prepare("SELECT COUNT(*) AS total_vouchers, COALESCE(SUM(total_pax),0) AS total_pax, ROUND(COALESCE(SUM(estimated_revenue_egp),0),2) AS est_rev_egp, ROUND(COALESCE(SUM(seller_commission_egp),0),2) AS total_commission_egp FROM reservations_fact WHERE {$whereSeller} AND booking_state = 'EXECUTED'");
        $sSt->execute([$uLower, $repUp]);
        $out['vouchers']       = $myRows;
        $out['my_sales_stats'] = $sSt->fetch();
    } elseif (in_array('crew.view_own_manifest', $perms, true)) {
        $out['vouchers'] = array_map($enrichVoucherRow, $pdo->query("SELECT * FROM reservations_fact WHERE dives_pax > 0 OR intro1_pax > 0 OR intro2_pax > 0 ORDER BY trip_date DESC LIMIT 50")->fetchAll());
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

    if ($user['role_code'] === 'GUEST_CUSTOMER') {
        $tpSt = $pdo->prepare(
            "SELECT u.id, u.username, u.emp_code, u.display_name_en, u.display_name_ar, u.job_title_en, u.job_title_ar,
                    u.phone, u.email, u.avatar_url, u.bio_notes, u.role_code, u.branch_scope, u.vessel_scope, u.rep_scope, u.referral_code, u.is_active, u.updated_at,
                    r.name_en AS role_en, r.name_ar AS role_ar, r.department
             FROM users u JOIN roles r ON r.role_code = u.role_code
             WHERE u.username = ?"
        );
        $tpSt->execute([$user['username']]);
        $out['team_profiles'] = $tpSt->fetchAll();
    } elseif ($user['role_code'] === 'FLEET_PARTNER') {
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
        $activeClause = in_array('bi.view_executive', $perms, true)
            ? "WHERE u.role_code != 'GUEST_CUSTOMER'"
            : "WHERE u.is_active = 1 AND u.role_code != 'GUEST_CUSTOMER'";
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
        $out['all_roles'] = $pdo->query("SELECT role_code, name_en, name_ar, department FROM roles WHERE role_code != 'GUEST_CUSTOMER' ORDER BY hierarchy_level DESC")->fetchAll();
    }

    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---------------- VIP REAL-CUSTOMER GUEST PORTAL (`guest_catalog` & `guest_portal_data`) ---------------- */
if ($action === 'guest_catalog') {
    erp_require_perm($ctx, 'guest.book_future_trips', 'bi.view_executive', 'crm.manage_leads_ads');
    $supportedLangs = ['en', 'ar', 'ru', 'de', 'it', 'fr', 'pl', 'zh'];
    $rawLang = strtolower(trim((string) ($_GET['lang'] ?? 'en')));
    $lang = in_array($rawLang, $supportedLangs, true) ? $rawLang : 'en';

    $prevLang = $GLOBALS['SC_LANG'] ?? 'en';
    $GLOBALS['SC_LANG'] = $lang;
    $rawCatalog = require ROOT_DIR . '/data/catalog.php';
    $galleries  = is_file(ROOT_DIR . '/data/item_galleries.php') ? (require ROOT_DIR . '/data/item_galleries.php') : [];
    $appExcRaw  = is_file(ROOT_DIR . '/data/app_excursions_catalog.php') ? (require ROOT_DIR . '/data/app_excursions_catalog.php') : [];
    $opSkusRaw  = is_file(ROOT_DIR . '/data/operational_skus.php') ? (require ROOT_DIR . '/data/operational_skus.php') : [];
    $hotelData  = is_file(ROOT_DIR . '/data/hotel_zones.php') ? (require ROOT_DIR . '/data/hotel_zones.php') : ['zones' => [], 'hotels' => []];
    $GLOBALS['SC_LANG'] = $prevLang;

    $fx = sc_erp_fx_rates($pdo);
    $eurRate = $fx['EUR'] ?? 57.0;
    $usdRate = $fx['USD'] ?? 50.0;
    $gbpRate = $fx['GBP'] ?? 65.0;

    // Guest-safe trip cards ONLY (strictly zero supplier_cost_egp, default_comm_pct, or internal vendor codes!)
    $trips = [];
    foreach ($appExcRaw as $ex) {
        $pEur = (float) ($ex['price_eur'] ?? 30);
        $cEur = isset($ex['child_price_eur']) && $ex['child_price_eur'] !== null ? (float) $ex['child_price_eur'] : round($pEur * 0.5, 0);
        $iEur = (float) ($ex['infant_price_eur'] ?? 0.0);
        $trips[] = [
            'id'              => (string) $ex['id'],
            'category_group'  => (string) ($ex['category_group'] ?? 'excursions'),
            'category_label'  => (string) ($ex['category_labels'][$lang] ?? $ex['category_labels']['en'] ?? 'Excursions'),
            'name'            => (string) ($ex['names'][$lang] ?? $ex['names']['en'] ?? $ex['id']),
            'summary'         => (string) ($ex['summaries'][$lang] ?? $ex['summaries']['en'] ?? ''),
            'duration'        => (string) ($ex['duration'] ?? 'Full Day'),
            'adult_eur'       => $pEur,
            'child_eur'       => $cEur,
            'infant_eur'      => $iEur,
            'price_usd'       => round(($pEur * $eurRate) / $usdRate, 0),
            'price_gbp'       => round(($pEur * $eurRate) / $gbpRate, 0),
            'price_egp'       => round($pEur * $eurRate, 0),
            'cover_webp'      => sc_erp_resolve_asset_webp((string) ($ex['cover_webp'] ?? '')),
            'pickup'          => (string) ($ex['specs']['pickup'] ?? 'Free Hotel Pickup'),
            'popular'         => !empty($ex['popular']),
        ];
    }
    foreach ($opSkusRaw as $sk) {
        if (!empty($sk['is_pass_through_fee'])) {
            continue;
        }
        $pEur = (float) ($sk['price_eur'] ?? 30);
        $cEur = isset($sk['child_price_eur']) && $sk['child_price_eur'] !== null ? (float) $sk['child_price_eur'] : $pEur;
        $trips[] = [
            'id'              => (string) $sk['id'],
            'category_group'  => (string) ($sk['category_group'] ?? 'watersports'),
            'category_label'  => (string) ($sk['category_labels'][$lang] ?? $sk['category_labels']['en'] ?? 'Watersports'),
            'name'            => (string) ($sk['names'][$lang] ?? $sk['names']['en'] ?? $sk['id']),
            'summary'         => (string) ($sk['summaries'][$lang] ?? $sk['summaries']['en'] ?? ''),
            'duration'        => (string) ($sk['duration'] ?? 'Flexible'),
            'adult_eur'       => $pEur,
            'child_eur'       => $cEur,
            'infant_eur'      => 0.0,
            'price_usd'       => round(($pEur * $eurRate) / $usdRate, 0),
            'price_gbp'       => round(($pEur * $eurRate) / $gbpRate, 0),
            'price_egp'       => round($pEur * $eurRate, 0),
            'cover_webp'      => sc_erp_resolve_asset_webp((string) ($sk['cover_webp'] ?? '')),
            'pickup'          => (string) ($sk['specs']['pickup'] ?? 'Included'),
            'popular'         => !empty($sk['popular']),
        ];
    }
    foreach ($rawCatalog as $catKey => $catMeta) {
        if (in_array($catKey, ['equipment', 'insurance'], true)) {
            continue;
        }
        $catLabel = is_array($catMeta['label'] ?? null)
            ? ($catMeta['label'][$lang] ?? $catMeta['label']['en'] ?? $catKey)
            : (string) ($catMeta['label'] ?? $catKey);
        foreach (($catMeta['groups'] ?? []) as $grp) {
            foreach (($grp['items'] ?? []) as $it) {
                $id = (string) ($it['id'] ?? '');
                if ($id === '' || ($it['price'] ?? null) === null || preg_match('/^(eq|fee|ins)-/', $id)) {
                    continue;
                }
                $pEur = (float) $it['price'];
                $cEur = str_contains($catKey, 'snork') ? round($pEur * 0.5, 0) : $pEur;
                $gal  = $galleries[$id] ?? [];
                $trips[] = [
                    'id'              => $id,
                    'category_group'  => 'diving-' . $catKey,
                    'category_label'  => $catLabel,
                    'name'            => $lang === 'ar' && !empty($it['name_ar']) ? (string) $it['name_ar'] : (string) ($it['name'] ?? $id),
                    'summary'         => $lang === 'ar' && !empty($it['desc_ar']) ? (string) $it['desc_ar'] : (string) ($it['desc'] ?? ''),
                    'duration'        => (string) ($it['duration'] ?? 'Full Day'),
                    'adult_eur'       => $pEur,
                    'child_eur'       => $cEur,
                    'infant_eur'      => 0.0,
                    'price_usd'       => round(($pEur * $eurRate) / $usdRate, 0),
                    'price_gbp'       => round(($pEur * $eurRate) / $gbpRate, 0),
                    'price_egp'       => round($pEur * $eurRate, 0),
                    'cover_webp'      => sc_erp_resolve_asset_webp((string) ($it['img'] ?? ($gal['photos'][0] ?? ''))),
                    'pickup'          => 'Free Hotel Transfer',
                    'popular'         => !empty($it['popular']),
                ];
            }
        }
    }

    $hotelsSimple = [];
    $zonesMap = $hotelData['zones'] ?? [];
    foreach (($hotelData['hotels'] ?? []) as $h) {
        $zk = (string) ($h['zone'] ?? 'montazah');
        $hotelsSimple[] = [
            'name'   => (string) $h['name'],
            'pickup' => (string) ($zonesMap[$zk]['pickup'] ?? '08:00'),
        ];
    }

    echo json_encode([
        'ok'        => true,
        'lang'      => $lang,
        'fx_rates'  => $fx,
        'trips'     => $trips,
        'hotels'    => $hotelsSimple,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($action === 'guest_portal_data') {
    erp_require_perm($ctx, 'guest.book_future_trips');
    $meUser = $ctx['user'];
    $uLower = strtolower((string) $meUser['username']);

    $gSt = $pdo->prepare('SELECT * FROM guest_customer_accounts WHERE LOWER(username) = ? LIMIT 1');
    $gSt->execute([$uLower]);
    $gRow = $gSt->fetch() ?: [
        'username'             => (string) $meUser['username'],
        'guest_name'           => (string) $meUser['display_name_en'],
        'guest_phone'          => (string) ($meUser['phone'] ?? ''),
        'guest_email'          => (string) ($meUser['email'] ?? ''),
        'hotel_name'           => '',
        'room_no'              => '',
        'nationality'          => '',
        'preferred_lang'       => 'en',
        'referred_by_seller'   => '',
        'total_bookings_count' => 0,
    ];

    $conciergeNameEn = 'Sea College VIP Concierge';
    $conciergeNameAr = 'مستشار رحلات سي كوليدج';
    if (!empty($gRow['referred_by_seller'])) {
        $refSeller = sc_erp_resolve_seller($pdo, (string) $gRow['referred_by_seller']);
        if ($refSeller) {
            $conciergeNameEn = (string) ($refSeller['display_name_en'] ?: $conciergeNameEn);
            $conciergeNameAr = (string) ($refSeller['display_name_ar'] ?: $conciergeNameAr);
        }
    }

    $fx = sc_erp_fx_rates($pdo);
    $eurRate = max(1.0, (float) ($fx['EUR'] ?? 57.0));

    $bSt = $pdo->prepare(
        'SELECT id, voucher_no, qr_token, trip_date, trip_name, hotel_name, room_no, pickup_time,
                adults, children, infants, total_pax,
                booked_total_egp, estimated_revenue_egp, deposit_egp_equiv, collected_rest_egp, rest_egp_equiv, rest_eur, discount_egp,
                boarding_status, payment_status, booking_state, workflow_stage
         FROM reservations_fact
         WHERE LOWER(guest_account_username) = ?
         ORDER BY trip_date DESC, id DESC
         LIMIT 60'
    );
    $bSt->execute([$uLower]);
    $myBookings = array_map(static function (array $r) use ($eurRate): array {
        $totEgp  = (float) (($r['booked_total_egp'] ?? 0) > 0 ? $r['booked_total_egp'] : ($r['estimated_revenue_egp'] ?? 0));
        $paidEgp = round((float) ($r['deposit_egp_equiv'] ?? 0) + (float) ($r['collected_rest_egp'] ?? 0), 2);
        $restEgp = round((float) ($r['rest_egp_equiv'] ?? 0), 2);
        return [
            'id'              => (int) $r['id'],
            'voucher_no'      => (string) $r['voucher_no'],
            'qr_token'        => (string) ($r['qr_token'] ?? ''),
            'trip_date'       => (string) $r['trip_date'],
            'trip_name'       => (string) $r['trip_name'],
            'hotel_name'      => (string) ($r['hotel_name'] ?? ''),
            'room_no'         => (string) ($r['room_no'] ?? ''),
            'pickup_time'     => (string) ($r['pickup_time'] ?: '08:00'),
            'adults'          => (int) ($r['adults'] ?? 0),
            'children'        => (int) ($r['children'] ?? 0),
            'infants'         => (int) ($r['infants'] ?? 0),
            'total_pax'       => (int) ($r['total_pax'] ?? 0),
            'total_egp'       => round($totEgp, 2),
            'total_eur'       => round($totEgp / $eurRate, 2),
            'paid_egp'        => $paidEgp,
            'rest_egp'        => $restEgp,
            'rest_eur'        => round($restEgp / $eurRate, 2),
            'discount_egp'    => round((float) ($r['discount_egp'] ?? 0), 2),
            'boarding_status' => (string) ($r['boarding_status'] ?? 'BOOKED'),
            'payment_status'  => (string) ($r['payment_status'] ?? 'OPEN_REST'),
            'booking_state'   => (string) ($r['booking_state'] ?? 'EXECUTED'),
            'workflow_stage'  => (string) ($r['workflow_stage'] ?? 'CONFIRMED'),
        ];
    }, $bSt->fetchAll());

    echo json_encode([
        'ok'               => true,
        'guest_account'    => [
            'username'             => (string) $gRow['username'],
            'guest_name'           => (string) $gRow['guest_name'],
            'guest_phone'          => (string) ($gRow['guest_phone'] ?? ''),
            'guest_email'          => (string) ($gRow['guest_email'] ?? ''),
            'hotel_name'           => (string) ($gRow['hotel_name'] ?? ''),
            'room_no'              => (string) ($gRow['room_no'] ?? ''),
            'nationality'          => (string) ($gRow['nationality'] ?? ''),
            'preferred_lang'       => (string) ($gRow['preferred_lang'] ?? 'en'),
            'concierge_name_en'    => $conciergeNameEn,
            'concierge_name_ar'    => $conciergeNameAr,
            'total_bookings_count' => count($myBookings) ?: (int) ($gRow['total_bookings_count'] ?? 0),
        ],
        'whatsapp_phone'   => WHATSAPP_NUMBER,
        'fx_rates'         => $fx,
        'bookings'         => $myBookings,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---------------- MARKETING TEAM REAL-CUSTOMER VAULT & CSV EXPORT ---------------- */
if ($action === 'marketing_guest_accounts') {
    erp_require_perm($ctx, 'crm.manage_leads_ads', 'bi.view_executive');
    $filter = strtolower(trim((string) ($_GET['filter'] ?? 'all')));
    $search = strtolower(trim((string) ($_GET['q'] ?? '')));

    $allRows = $pdo->query(
        'SELECT g.*, COALESCE(u.is_active, 1) AS account_is_active, COALESCE(s.display_name_en, g.referred_by_seller) AS seller_display_name
         FROM guest_customer_accounts g
         LEFT JOIN users u ON LOWER(u.username) = LOWER(g.username)
         LEFT JOIN users s ON LOWER(s.username) = LOWER(g.referred_by_seller)
         ORDER BY g.id DESC
         LIMIT 500'
    )->fetchAll();

    $kpis = [
        'total_verified_guests'    => count($allRows),
        'guests_with_email'        => 0,
        'credentials_emails_sent'  => 0,
        'unpaid_open_guests'       => 0,
        'cancelled_winback_guests' => 0,
        'paid_vip_guests'          => 0,
        'repeat_app_bookers'       => 0,
    ];

    $filtered = [];
    foreach ($allRows as $gr) {
        $hasEmail  = trim((string) ($gr['guest_email'] ?? '')) !== '';
        $stUp      = strtoupper((string) ($gr['latest_booking_status'] ?? ''));
        $isCancel  = str_contains($stUp, 'CANCEL') || ((int) ($gr['cancelled_bookings_count'] ?? 0) > 0 && (float) ($gr['lifetime_paid_egp'] ?? 0) <= 0.01);
        $isPaid    = !$isCancel && ((float) ($gr['lifetime_paid_egp'] ?? 0) > 0.01 || $stUp === 'CONFIRMED_PAID');
        $isUnpaid  = !$isCancel && !$isPaid;
        $isRepeat  = (int) ($gr['total_bookings_count'] ?? 0) > 1;

        if ($hasEmail) {
            $kpis['guests_with_email']++;
        }
        if ((int) ($gr['credentials_email_sent'] ?? 0) === 1) {
            $kpis['credentials_emails_sent']++;
        }
        if ($isCancel) {
            $kpis['cancelled_winback_guests']++;
        } elseif ($isPaid) {
            $kpis['paid_vip_guests']++;
        } else {
            $kpis['unpaid_open_guests']++;
        }
        if ($isRepeat) {
            $kpis['repeat_app_bookers']++;
        }

        if ($filter === 'with_email' && !$hasEmail) {
            continue;
        }
        if ($filter === 'without_email' && $hasEmail) {
            continue;
        }
        if ($filter === 'unpaid' && !$isUnpaid) {
            continue;
        }
        if ($filter === 'cancelled' && !$isCancel) {
            continue;
        }
        if ($filter === 'paid' && !$isPaid) {
            continue;
        }
        if ($filter === 'repeat' && !$isRepeat) {
            continue;
        }

        if ($search !== '') {
            $hay = strtolower(
                ($gr['guest_name'] ?? '') . ' ' .
                ($gr['guest_email'] ?? '') . ' ' .
                ($gr['guest_phone'] ?? '') . ' ' .
                ($gr['username'] ?? '') . ' ' .
                ($gr['hotel_name'] ?? '') . ' ' .
                ($gr['latest_voucher_no'] ?? '') . ' ' .
                ($gr['latest_trip_name'] ?? '')
            );
            if (!str_contains($hay, $search)) {
                continue;
            }
        }

        $gr['segment'] = $isCancel ? 'CANCELLED_WINBACK' : ($isPaid ? 'PAID_VIP' : 'CONFIRMED_UNPAID');
        $filtered[] = $gr;
    }

    $dispatches = $pdo->query('SELECT id, sent_at, guest_username, recipient_email, voucher_no, subject, transport_ok FROM guest_email_dispatches ORDER BY id DESC LIMIT 40')->fetchAll();

    echo json_encode([
        'ok'               => true,
        'filter'           => $filter,
        'kpis'             => $kpis,
        'guests'           => $filtered,
        'email_dispatches' => $dispatches,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($action === 'export_marketing_guests_csv') {
    erp_require_perm($ctx, 'crm.manage_leads_ads', 'bi.view_executive');
    $segmentFilter = strtolower(trim((string) ($_GET['filter'] ?? 'all')));
    $rows = $pdo->query(
        'SELECT g.*, COALESCE(u.is_active, 1) AS account_is_active
         FROM guest_customer_accounts g
         LEFT JOIN users u ON LOWER(u.username) = LOWER(g.username)
         ORDER BY g.id DESC'
    )->fetchAll();

    $csvSafe = static function ($val): string {
        $s = trim((string) ($val ?? ''));
        if ($s !== '' && in_array($s[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $s;
        }
        return $s;
    };

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sea_college_verified_guests_crm.csv"');
    $outFp = fopen('php://output', 'wb');
    fwrite($outFp, "\xEF\xBB\xBF");
    fputcsv($outFp, [
        'Guest Username', 'Guest Name', 'Email', 'Phone', 'Nationality', 'Language',
        'Hotel', 'Room', 'Referred Seller', 'Latest Voucher', 'Latest Trip', 'Latest Trip Date',
        'Lifecycle Status', 'Total Bookings', 'Cancelled Bookings',
        'Lifetime Booked EGP', 'Lifetime Paid EGP', 'Login Email Sent', 'Account Active', 'Created At',
    ]);
    foreach ($rows as $r) {
        $hasEm = trim((string) ($r['guest_email'] ?? '')) !== '';
        $stUp  = strtoupper((string) ($r['latest_booking_status'] ?? ''));
        $isCan = str_contains($stUp, 'CANCEL') || ((int) ($r['cancelled_bookings_count'] ?? 0) > 0 && (float) ($r['lifetime_paid_egp'] ?? 0) <= 0.01);
        $isPd  = !$isCan && ((float) ($r['lifetime_paid_egp'] ?? 0) > 0.01 || $stUp === 'CONFIRMED_PAID');
        if ($segmentFilter === 'with_email' && !$hasEm) {
            continue;
        }
        if ($segmentFilter === 'cancelled' && !$isCan) {
            continue;
        }
        if ($segmentFilter === 'unpaid' && ($isCan || $isPd)) {
            continue;
        }
        if ($segmentFilter === 'paid' && !$isPd) {
            continue;
        }
        fputcsv($outFp, [
            $csvSafe($r['username']),
            $csvSafe($r['guest_name']),
            $csvSafe($r['guest_email']),
            $csvSafe($r['guest_phone']),
            $csvSafe($r['nationality']),
            $csvSafe($r['preferred_lang']),
            $csvSafe($r['hotel_name']),
            $csvSafe($r['room_no']),
            $csvSafe($r['referred_by_seller']),
            $csvSafe($r['latest_voucher_no']),
            $csvSafe($r['latest_trip_name']),
            $csvSafe($r['latest_trip_date']),
            $csvSafe($r['latest_booking_status']),
            (int) ($r['total_bookings_count'] ?? 1),
            (int) ($r['cancelled_bookings_count'] ?? 0),
            round((float) ($r['lifetime_booked_egp'] ?? 0), 2),
            round((float) ($r['lifetime_paid_egp'] ?? 0), 2),
            ((int) ($r['credentials_email_sent'] ?? 0)) === 1 ? 'YES' : 'NO',
            ((int) ($r['account_is_active'] ?? 1)) === 1 ? 'ACTIVE' : 'INACTIVE',
            $csvSafe($r['created_at']),
        ]);
    }
    fclose($outFp);
    exit;
}

/* ---------------- SUPPLIERS / VENDORS HUB & GRANULAR TRIP/SAFARI COST MATRIX (`suppliers_hub` — v3.5) ---------------- */
if ($action === 'suppliers_hub') {
    erp_require_perm($ctx, 'gl.view_all_ledgers', 'bi.view_executive', 'ops.view_manifests', 'safe.view_balances');
    $canEditCosts = in_array('gl.view_all_ledgers', $ctx['perms'], true) || in_array('bi.view_executive', $ctx['perms'], true);
    $fx = sc_erp_fx_rates($pdo);

    $catFilter = strtoupper(trim((string) ($_GET['category'] ?? 'ALL')));
    $supFilter = strtoupper(trim((string) ($_GET['supplier'] ?? 'ALL')));
    $qSearch   = strtolower(trim((string) ($_GET['q'] ?? '')));

    $suppliers = $pdo->query('SELECT * FROM suppliers_directory ORDER BY CASE WHEN supplier_code = "AP_BAKAR" THEN 0 WHEN supplier_code = "AP_A_SAAD" THEN 1 WHEN supplier_code = "SEA_COLLEGE" THEN 2 ELSE 3 END, name_en ASC')->fetchAll();

    // Aggregate reservation volume & supplier COGS by supplier_code / executor
    $resStats = [];
    try {
        $rAgg = $pdo->query(
            "SELECT COALESCE(NULLIF(ap_supplier_code, ''), UPPER(TRIM(executor))) AS skey,
                    COUNT(*) AS bookings_cnt,
                    SUM(adults + children) AS pax_cnt,
                    ROUND(SUM(CASE WHEN supplier_cost_egp > 0 THEN supplier_cost_egp ELSE estimated_cogs_egp END), 2) AS total_cogs_egp
             FROM reservations_fact
             WHERE booking_state != 'CANCELLED'
             GROUP BY skey"
        )->fetchAll();
        foreach ($rAgg as $ra) {
            $resStats[(string) $ra['skey']] = $ra;
        }
    } catch (Throwable $e) {}

    // Subledger recent statement lines per supplier
    $subMap = [];
    try {
        $subRows = $pdo->query("SELECT party_code, tx_date, voucher_no, description, debit_egp, credit_egp, running_balance_egp FROM subledger_lines WHERE party_code LIKE 'AP_%' ORDER BY id DESC LIMIT 250")->fetchAll();
        foreach ($subRows as $sr) {
            $pc = (string) $sr['party_code'];
            if (!isset($subMap[$pc]) || count($subMap[$pc]) < 8) {
                $subMap[$pc][] = $sr;
            }
        }
    } catch (Throwable $e) {}

    $supNameMap = [];
    $netPayableEgp = 0.0;
    foreach ($suppliers as &$sp) {
        $sc = (string) $sp['supplier_code'];
        $supNameMap[$sc] = ['en' => (string) $sp['name_en'], 'ar' => (string) $sp['name_ar']];
        $tabKey = strtoupper(trim((string) ($sp['sheet_tab_name'] ?? '')));
        $st1 = $resStats[$sc] ?? null;
        $st2 = ($tabKey !== '' && isset($resStats[$tabKey])) ? $resStats[$tabKey] : null;
        $sp['bookings_count']   = (int) (($st1['bookings_cnt'] ?? 0) + ($st2['bookings_cnt'] ?? 0));
        $sp['pax_count']        = (int) (($st1['pax_cnt'] ?? 0) + ($st2['pax_cnt'] ?? 0));
        $sp['booked_cogs_egp']  = round((float) (($st1['total_cogs_egp'] ?? 0.0) + ($st2['total_cogs_egp'] ?? 0.0)), 2);
        $sp['recent_subledger'] = $subMap[$sc] ?? [];
        if ((float) $sp['current_balance_egp'] < 0) {
            $netPayableEgp += abs((float) $sp['current_balance_egp']);
        }
    }
    unset($sp);

    $allCosts = $pdo->query(
        'SELECT c.*, COALESCE(s.name_en, c.supplier_code) AS supplier_name_en, COALESCE(s.name_ar, c.supplier_code) AS supplier_name_ar
         FROM trip_supplier_cost_items c
         LEFT JOIN suppliers_directory s ON s.supplier_code = c.supplier_code
         ORDER BY CASE c.category_group
             WHEN "DESERT_SAFARI" THEN 1
             WHEN "YACHT_SNORKELING" THEN 2
             WHEN "CAIRO_LUXOR" THEN 3
             WHEN "SINAI_DAHAB" THEN 4
             WHEN "DOLPHIN_SUB_DINNER" THEN 5
             WHEN "WATERSPORTS" THEN 6
             WHEN "DIVING_CHARTER" THEN 7
             WHEN "BOAT_SHARING" THEN 8
             WHEN "TRANSFERS" THEN 9
             ELSE 10
         END, c.is_safari_component DESC, c.item_code ASC'
    )->fetchAll();

    $filteredCosts = [];
    $safariCount = 0;
    foreach ($allCosts as $ci) {
        if ((string) $ci['category_group'] === 'DESERT_SAFARI') {
            $safariCount++;
        }
        if ($catFilter !== '' && $catFilter !== 'ALL' && strtoupper((string) $ci['category_group']) !== $catFilter) {
            continue;
        }
        if ($supFilter !== '' && $supFilter !== 'ALL' && strtoupper((string) $ci['supplier_code']) !== $supFilter) {
            continue;
        }
        if ($qSearch !== '') {
            $hay = strtolower((string) $ci['item_code'] . ' ' . (string) $ci['name_en'] . ' ' . (string) $ci['name_ar'] . ' ' . (string) $ci['supplier_name_en'] . ' ' . (string) $ci['sheet_formula_note']);
            if (!str_contains($hay, $qSearch)) {
                continue;
            }
        }
        $filteredCosts[] = $ci;
    }

    $auditLogs = $pdo->query('SELECT * FROM trip_cost_audit_log ORDER BY id DESC LIMIT 60')->fetchAll();
    $masterTripsList = $pdo->query('SELECT trip_code, unified_name_en, unified_name_ar, category_code FROM master_trips_catalog ORDER BY unified_name_en ASC')->fetchAll();

    echo json_encode([
        'ok'             => true,
        'can_edit_costs' => $canEditCosts,
        'fx_rates'       => $fx,
        'kpis'           => [
            'total_suppliers'       => count($suppliers),
            'total_cost_items'      => count($allCosts),
            'safari_variants_count' => $safariCount,
            'net_ap_payable_egp'    => round($netPayableEgp, 2),
            'audit_changes_count'   => count($auditLogs),
        ],
        'suppliers'      => $suppliers,
        'cost_items'     => $filteredCosts,
        'master_trips'   => $masterTripsList,
        'audit_logs'     => $auditLogs,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($action === 'export_suppliers_costs_csv') {
    erp_require_perm($ctx, 'gl.view_all_ledgers', 'bi.view_executive');
    $rows = $pdo->query(
        'SELECT c.*, COALESCE(s.name_en, c.supplier_code) AS supplier_name_en
         FROM trip_supplier_cost_items c
         LEFT JOIN suppliers_directory s ON s.supplier_code = c.supplier_code
         ORDER BY c.category_group ASC, c.item_code ASC'
    )->fetchAll();

    $csvSafe = static function ($val): string {
        $s = trim((string) ($val ?? ''));
        if ($s !== '' && in_array($s[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $s;
        }
        return $s;
    };

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sea_college_suppliers_and_trip_costs_' . gmdate('Ymd') . '.csv"');
    $fp = fopen('php://output', 'wb');
    fwrite($fp, "\xEF\xBB\xBF");
    fputcsv($fp, [
        'Item Code', 'Linked Trip Code', 'Category', 'Supplier Code', 'Supplier Name',
        'Trip / Variant Name (EN)', 'Trip / Variant Name (AR)', 'Pricing Basis', 'Capacity PAX',
        'Cost Currency', 'Adult Cost (Orig)', 'Child Cost (Orig)', 'Infant Cost (Orig)', 'Unit Cost (Orig)',
        'Adult Cost (EGP)', 'Child Cost (EGP)', 'Infant Cost (EGP)', 'Unit Cost (EGP)',
        'Adult Sell (EUR)', 'Child Sell (EUR)', 'Infant Sell (EUR)', 'Sale Mode', 'Sheet Formula Note', 'Updated By', 'Updated At',
    ]);
    foreach ($rows as $r) {
        fputcsv($fp, [
            $csvSafe($r['item_code']),
            $csvSafe($r['linked_trip_code']),
            $csvSafe($r['category_group']),
            $csvSafe($r['supplier_code']),
            $csvSafe($r['supplier_name_en']),
            $csvSafe($r['name_en']),
            $csvSafe($r['name_ar']),
            $csvSafe($r['pricing_basis']),
            (int) ($r['capacity_pax'] ?? 1),
            $csvSafe($r['cost_currency']),
            round((float) ($r['adult_cost_orig'] ?? 0), 2),
            round((float) ($r['child_cost_orig'] ?? 0), 2),
            round((float) ($r['infant_cost_orig'] ?? 0), 2),
            round((float) ($r['unit_cost_orig'] ?? 0), 2),
            round((float) ($r['adult_cost_egp'] ?? 0), 2),
            round((float) ($r['child_cost_egp'] ?? 0), 2),
            round((float) ($r['infant_cost_egp'] ?? 0), 2),
            round((float) ($r['unit_cost_egp'] ?? 0), 2),
            round((float) ($r['adult_sell_eur'] ?? 0), 2),
            round((float) ($r['child_sell_eur'] ?? 0), 2),
            round((float) ($r['infant_sell_eur'] ?? 0), 2),
            $csvSafe($r['sale_mode']),
            $csvSafe($r['sheet_formula_note']),
            $csvSafe($r['updated_by']),
            $csvSafe($r['updated_at']),
        ]);
    }
    fclose($fp);
    exit;
}

/* ---------------- STAFF BONUS & ALLOWANCE / ADVANCE HUB (`staff_bonuses_allowances` — v3.5) ---------------- */
if ($action === 'staff_bonuses_allowances') {
    if (!$ctx) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Authentication required']);
        exit;
    }
    $me = $ctx['user'];
    if (($me['role_code'] ?? '') === 'GUEST_CUSTOMER') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Staff access required']);
        exit;
    }
    $canManageAll = in_array('bi.view_executive', $ctx['perms'], true)
        || in_array('gl.view_all_ledgers', $ctx['perms'], true)
        || in_array('hr.manage_payroll', $ctx['perms'], true)
        || in_array('safe.record_tx', $ctx['perms'], true);
    $canAwardBonus = in_array('bi.view_executive', $ctx['perms'], true)
        || in_array('gl.view_all_ledgers', $ctx['perms'], true)
        || in_array('hr.manage_payroll', $ctx['perms'], true);

    $monthFilter = trim((string) ($_GET['month'] ?? 'ALL'));
    $typeFilter  = strtoupper(trim((string) ($_GET['type'] ?? 'ALL')));

    $sql = 'SELECT b.*, u.avatar_url, u.job_title_en, u.job_title_ar
            FROM staff_bonuses_allowances b
            LEFT JOIN users u ON LOWER(u.username) = LOWER(b.emp_username)
            WHERE 1=1';
    $params = [];
    if (!$canManageAll) {
        $sql .= ' AND LOWER(b.emp_username) = LOWER(?)';
        $params[] = (string) $me['username'];
    }
    if ($monthFilter !== '' && strtoupper($monthFilter) !== 'ALL' && preg_match('/^\d{4}-\d{2}$/', $monthFilter)) {
        $sql .= ' AND b.period_month = ?';
        $params[] = $monthFilter;
    }
    if (in_array($typeFilter, ['BONUS', 'ALLOWANCE', 'ADVANCE'], true)) {
        $sql .= ' AND b.entry_type = ?';
        $params[] = $typeFilter;
    }
    $sql .= ' ORDER BY CASE WHEN b.status = "REQUESTED" THEN 0 ELSE 1 END, b.id DESC LIMIT 200';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();

    $totBonus = 0.0;
    $totAllowance = 0.0;
    $totAdvance = 0.0;
    $pendingCnt = 0;
    $approvedCnt = 0;
    foreach ($rows as $r) {
        $stRow = (string) $r['status'];
        if ($stRow === 'REQUESTED') {
            $pendingCnt++;
        } elseif (in_array($stRow, ['APPROVED_PAID', 'POSTED_TO_PAYROLL'], true)) {
            $approvedCnt++;
            $amt = (float) $r['amount_egp'];
            if ($r['entry_type'] === 'BONUS') {
                $totBonus += $amt;
            } elseif ($r['entry_type'] === 'ALLOWANCE') {
                $totAllowance += $amt;
            } else {
                $totAdvance += $amt;
            }
        }
    }

    $staffDir = [];
    if ($canManageAll) {
        $staffDir = $pdo->query(
            "SELECT username, display_name_en, display_name_ar, role_code, branch_scope, job_title_en, job_title_ar
             FROM users
             WHERE is_active = 1 AND role_code != 'GUEST_CUSTOMER'
             ORDER BY display_name_en ASC"
        )->fetchAll();
    } else {
        $staffDir = [[
            'username'        => (string) $me['username'],
            'display_name_en' => (string) $me['display_name_en'],
            'display_name_ar' => (string) $me['display_name_ar'],
            'role_code'       => (string) $me['role_code'],
            'branch_scope'    => (string) $me['branch_scope'],
            'job_title_en'    => (string) ($me['job_title_en'] ?? ''),
            'job_title_ar'    => (string) ($me['job_title_ar'] ?? ''),
        ]];
    }

    echo json_encode([
        'ok'              => true,
        'can_manage_all'  => $canManageAll,
        'can_award_bonus' => $canAwardBonus,
        'kpis'            => [
            'total_bonuses_egp'    => round($totBonus, 2),
            'total_allowances_egp' => round($totAllowance, 2),
            'total_advances_egp'   => round($totAdvance, 2),
            'pending_count'        => $pendingCnt,
            'approved_count'       => $approvedCnt,
        ],
        'items'           => $rows,
        'staff_directory' => $staffDir,
        'fx_rates'        => sc_erp_fx_rates($pdo),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
            if ($roleCode === 'GUEST_CUSTOMER') {
                $pdo->prepare(
                    'UPDATE guest_customer_accounts
                     SET guest_name = ?,
                         guest_phone = CASE WHEN ? != "" THEN ? ELSE guest_phone END,
                         guest_phone_norm = CASE WHEN ? != "" THEN ? ELSE guest_phone_norm END,
                         guest_email = CASE WHEN ? != "" THEN ? ELSE guest_email END,
                         updated_at = ?
                     WHERE LOWER(username) = ?'
                )->execute([
                    $nameEn,
                    $phone, $phone,
                    sc_erp_normalize_guest_phone($phone), sc_erp_normalize_guest_phone($phone),
                    $email, $email,
                    $now,
                    $targetUsername,
                ]);
            }
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
        $guestPh  = clean_line($rawIn['guest_phone'] ?? ($rawIn['phone'] ?? ''), 48);
        $rawGEm   = strtolower(clean_line($rawIn['guest_email'] ?? ($rawIn['email'] ?? ''), 160));
        $guestEm  = ($rawGEm !== '' && filter_var($rawGEm, FILTER_VALIDATE_EMAIL)) ? $rawGEm : '';
        $guestLg  = strtolower(clean_line($rawIn['lang'] ?? 'en', 8));
        $hotel    = clean_line($rawIn['hotel_name'] ?? 'Cyrene Sharm Hotel', 120);
        $room     = clean_line($rawIn['room_no'] ?? '', 32);
        $nat      = strtoupper(clean_line($rawIn['nationality'] ?? 'ITALY', 24));
        $pickup   = clean_line($rawIn['pickup_time'] ?? '08:00', 24);
        $prodCode = clean_line($rawIn['product_code'] ?? 'dsd-2-boat', 64);
        $notes    = clean_line($rawIn['notes'] ?? '', 250);

        $adults   = max(1, min(60, (int) ($rawIn['adults'] ?? 2)));
        $children = max(0, min(40, (int) ($rawIn['children'] ?? 0)));
        $infants  = max(0, min(20, (int) ($rawIn['infants'] ?? 0)));
        $pax      = $adults + $children + $infants;

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

        $masterTrip = sc_erp_lookup_unified_trip($pdo, $prodCode);
        $pSt = $pdo->prepare('SELECT * FROM products_catalog WHERE code = ?');
        $pSt->execute([$prodCode]);
        $prod = $pSt->fetch();

        // Also check direct match in `trip_supplier_cost_items` (e.g. standalone Camel 1h Tour `SAF-CAMEL-1HR-TOUR` or Petra `PETRA-JORDAN-VIP`)
        $costItemDirect = null;
        if (!$masterTrip) {
            $ciSt = $pdo->prepare('SELECT c.*, COALESCE(s.name_en, c.supplier_code) AS sup_name FROM trip_supplier_cost_items c LEFT JOIN suppliers_directory s ON s.supplier_code = c.supplier_code WHERE UPPER(c.item_code) = UPPER(?) LIMIT 1');
            $ciSt->execute([$prodCode]);
            $costItemDirect = $ciSt->fetch() ?: null;
        }

        $rawSafariSpec = $rawIn['safari_spec'] ?? null;
        if (is_string($rawSafariSpec) && trim($rawSafariSpec) !== '') {
            $decodedSaf = json_decode($rawSafariSpec, true);
            $rawSafariSpec = is_array($decodedSaf) ? $decodedSaf : null;
        }
        $safariCalc = is_array($rawSafariSpec)
            ? sc_erp_compute_safari_breakdown_financials($pdo, $rawSafariSpec, $adults, $children, $infants, $fx)
            : ['has_items' => false];

        $tripName   = $masterTrip
            ? (string) $masterTrip['unified_name_en']
            : ($costItemDirect ? (string) $costItemDirect['name_en'] : ($prod ? (string) $prod['name_en'] : $prodCode));
        $adlEur     = $masterTrip
            ? (float) $masterTrip['adult_sell_eur']
            : ($costItemDirect && (float) $costItemDirect['adult_sell_eur'] > 0 ? (float) $costItemDirect['adult_sell_eur'] : (($prod && $prod['price_eur'] !== null) ? (float) $prod['price_eur'] : 55.0));
        $chdEur     = $masterTrip
            ? (float) $masterTrip['child_sell_eur']
            : ($costItemDirect ? (float) $costItemDirect['child_sell_eur'] : round($adlEur * 0.5, 2));
        $infEur     = $masterTrip
            ? (float) $masterTrip['infant_sell_eur']
            : ($costItemDirect ? (float) $costItemDirect['infant_sell_eur'] : 0.0);
        $costRatio  = (float) ($prod['direct_cost_ratio'] ?? 0.38);

        if (!empty($safariCalc['has_items'])) {
            $tripName = 'Desert Safari (' . $safariCalc['sheet_activity_code'] . ')';
            $catalogRevEgp = (float) $safariCalc['suggested_sell_egp'];
        } else {
            $catalogRevEgp = round(($adults * $adlEur + $children * $chdEur + $infants * $infEur) * $fx['EUR'], 2);
        }

        $explicitGross = round($depositEgp + $restEq, 2);
        $grossEgp      = $explicitGross > 0.0 ? $explicitGross : $catalogRevEgp;

        // If deposit was 0 and rest wasn't explicitly set, automatically put remaining amount in REST EUR so pickup/Jetty knows what to collect!
        if ($explicitGross <= 0.0 && $catalogRevEgp > 0.0 && !array_key_exists('deposit_amount', $rawIn) && !array_key_exists('rest_amount', $rawIn) && !array_key_exists('rest_eur', $rawIn)) {
            $rEur   = !empty($safariCalc['has_items'])
                ? (float) $safariCalc['suggested_sell_eur']
                : round($adults * $adlEur + $children * $chdEur + $infants * $infEur, 2);
            $restEq = $catalogRevEgp;
        }

        // Hidden automatic accounting mode & Adult/Child/Safari supplier cost from live DB unless explicitly overridden
        $defaultMode = !empty($safariCalc['has_items'])
            ? 'OUT_SIDE'
            : ($masterTrip ? (string) $masterTrip['sale_mode'] : ($costItemDirect ? (string) $costItemDirect['sale_mode'] : 'INSHEET'));
        $saleModeRaw = isset($rawIn['sale_mode']) && trim((string) $rawIn['sale_mode']) !== ''
            ? strtoupper(trim((string) $rawIn['sale_mode']))
            : $defaultMode;
        if (!in_array($saleModeRaw, ['INSHEET', 'OUT_SIDE', 'OUTSIDE', 'CENTER'], true)) {
            $saleModeRaw = $defaultMode;
        }

        if (!empty($safariCalc['has_items'])) {
            $autoSupplierCostEgp = (float) $safariCalc['total_cost_egp'];
        } elseif ($masterTrip) {
            $autoSupplierCostEgp = round($adults * (float) $masterTrip['adult_cost_egp'] + $children * (float) $masterTrip['child_cost_egp'] + $infants * (float) $masterTrip['infant_cost_egp'], 2);
        } elseif ($costItemDirect) {
            $autoSupplierCostEgp = (string) $costItemDirect['pricing_basis'] === 'PER_PAX'
                ? round($adults * (float) $costItemDirect['adult_cost_egp'] + $children * (float) $costItemDirect['child_cost_egp'] + $infants * (float) $costItemDirect['infant_cost_egp'], 2)
                : round((float) $costItemDirect['unit_cost_egp'], 2);
        } else {
            $autoSupplierCostEgp = 0.0;
        }
        $supplierCostEgp = array_key_exists('supplier_cost_egp', $rawIn) && (float) $rawIn['supplier_cost_egp'] > 0
            ? max(0.0, round((float) $rawIn['supplier_cost_egp'], 2))
            : $autoSupplierCostEgp;

        // Resolve seller attribution first so discount policy caps follow the seller
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

        // Special Seller Discount by Percentage ('PERCENT') or Fixed Amount ('AMOUNT')
        $reqDiscType   = strtoupper(trim((string) ($rawIn['discount_type'] ?? 'NONE')));
        $reqDiscVal    = max(0.0, (float) ($rawIn['discount_value'] ?? 0.0));
        $reqDiscCurr   = strtoupper(trim((string) ($rawIn['discount_currency'] ?? ($rawIn['deposit_currency'] ?? 'EUR'))));
        $reqDiscReason = clean_line($rawIn['discount_reason'] ?? '', 160);

        $policyUser     = $resolvedSeller ? (string) $resolvedSeller['username'] : (string) $user['username'];
        $discPolicy     = sc_erp_get_seller_discount_policy($pdo, $policyUser);
        $isOwnerOverride= in_array('bi.view_executive', $ctx['perms'], true);

        if (in_array($reqDiscType, ['PERCENT', 'PCT', 'PERCENTAGE', '%', 'AMOUNT', 'FIXED', 'CASH', 'VALUE'], true) && $reqDiscVal > 0.0) {
            $explicitListEgp = max(0.0, (float) ($rawIn['list_total_egp'] ?? 0.0));
            $baseListEgp     = $explicitListEgp > 0.0 ? $explicitListEgp : ($catalogRevEgp > 0.0 ? $catalogRevEgp : $explicitGross);
            $floorCostEgp    = ($saleModeRaw === 'OUT_SIDE' || $saleModeRaw === 'OUTSIDE') ? $supplierCostEgp : 0.0;

            $discRes = sc_erp_calculate_discount(
                $baseListEgp,
                $floorCostEgp,
                $reqDiscType,
                $reqDiscVal,
                $reqDiscCurr,
                $fx,
                $discPolicy,
                $isOwnerOverride
            );
            $listTotalEgp = $discRes['list_total_egp'];
            $grossEgp     = $discRes['net_total_egp'];
            $discTypeOut  = $discRes['discount_type'];
            $discValOut   = $discRes['discount_value'];
            $discCurrOut  = $discRes['discount_currency'];
            $discEgpOut   = $discRes['discount_egp'];
            $discClamped  = (bool) $discRes['was_clamped'];

            // Reconcile deposit + rest cleanly so deposit_egp + rest_egp_equiv == net discounted grossEgp
            if ($depositEgp > $grossEgp) {
                $depositEgp = $grossEgp;
            }
            if (abs(($depositEgp + $restEq) - $grossEgp) > 1.50) {
                $restEq = round(max(0.0, $grossEgp - $depositEgp), 2);
                $rEgp = 0.0;
                $rUsd = 0.0;
                $rEur = 0.0;
                $rGbp = 0.0;
                if ($restEq > 0.0) {
                    $targetRestCurr = array_key_exists('rest_currency', $rawIn) ? $restCurr : 'EUR';
                    if ($targetRestCurr === 'EGP') {
                        $rEgp = $restEq;
                    } elseif ($targetRestCurr === 'USD') {
                        $rUsd = round($restEq / $fx['USD'], 2);
                    } elseif ($targetRestCurr === 'GBP') {
                        $rGbp = round($restEq / $fx['GBP'], 2);
                    } else {
                        $rEur = round($restEq / $fx['EUR'], 2);
                    }
                }
            }
        } else {
            $listTotalEgp = $grossEgp;
            $discTypeOut  = 'NONE';
            $discValOut   = 0.0;
            $discCurrOut  = isset($fx[$reqDiscCurr]) ? $reqDiscCurr : 'EUR';
            $discEgpOut   = 0.0;
            $discClamped  = false;
        }

        $cogsEgp  = ($saleModeRaw === 'OUT_SIDE' || $saleModeRaw === 'OUTSIDE')
            ? ($supplierCostEgp > 0 ? $supplierCostEgp : round($grossEgp * $costRatio, 2))
            : ($supplierCostEgp > 0 ? $supplierCostEgp : round($grossEgp * $costRatio, 2));
        $profEgp  = round($grossEgp - $cogsEgp, 2);

        $supCode  = !empty($safariCalc['has_items'])
            ? (string) $safariCalc['supplier_code']
            : ($masterTrip ? (string) $masterTrip['supplier_code'] : ($costItemDirect ? (string) $costItemDirect['supplier_code'] : 'SEA_COLLEGE'));
        $supName  = !empty($safariCalc['has_items'])
            ? (string) $safariCalc['supplier_name']
            : ($masterTrip ? (string) $masterTrip['supplier_name'] : ($costItemDirect ? (string) $costItemDirect['sup_name'] : 'SEA COLLEGE'));

        $safariBreakdownJson = !empty($safariCalc['has_items'])
            ? (json_encode($safariCalc, JSON_UNESCAPED_UNICODE) ?: '')
            : '';

        $commNote = $notes;
        if (!empty($safariCalc['has_items'])) {
            $commNote = trim($commNote . ($commNote !== '' ? ' | ' : '') . 'Safari: ' . $safariCalc['sheet_activity_code']);
        }
        if ($discEgpOut > 0.0) {
            $dBadge = $discTypeOut === 'PERCENT' ? ("-{$discValOut}%") : ("-{$discValOut} {$discCurrOut}");
            $commNote = trim($commNote . ($commNote !== '' ? ' | ' : '') . "Discount {$dBadge} (-" . round($discEgpOut) . " EGP)");
        }

        // Commission starts as PENDING_EXECUTION on ACTUAL NET DISCOUNTED AMOUNT ($grossEgp) until BOTH trip is done AND 100% paid!
        $commRes = sc_erp_record_seller_commission($pdo, [
            'seller_token'     => $resolvedSeller ? (string) $resolvedSeller['username'] : '',
            'sale_mode'        => $saleModeRaw,
            'tx_date'          => $tripDt,
            'voucher_no'       => $voNo,
            'guest_name'       => $guest,
            'product_name'     => $tripName,
            'pax'              => $pax,
            'gross_amount_egp' => $grossEgp,
            'supplier_cost_egp'=> ($saleModeRaw === 'OUT_SIDE' || $saleModeRaw === 'OUTSIDE') ? $supplierCostEgp : 0.0,
            'status'           => 'PENDING_EXECUTION',
            'source_channel'   => 'LIVE_MOBILE_APP',
            'branch_code'      => $branch,
            'notes'            => $commNote,
        ]);

        $ins = $pdo->prepare(
            'INSERT INTO reservations_fact
             (voucher_no, qr_token, trip_date, period_week, period_month, period_quarter, period_year,
              branch_code, channel, hotel_name, pickup_zone, room_no, nationality, pickup_time,
              trip_name, unified_trip_code, product_category, adults, children, infants, total_pax,
              adult_unit_eur, child_unit_eur, infant_unit_eur,
              booked_adults, booked_children, booked_infants, booked_total_egp,
              list_total_egp, discount_type, discount_value, discount_currency, discount_egp, discount_reason,
              snork_pax, intro1_pax, intro2_pax, dives_pax, divers_names, guest_phone, guest_email, safari_breakdown_json,
              sales_rep, executor, ap_supplier_code, client_agency,
              rest_egp, rest_usd, rest_eur, rest_gbp, rest_egp_equiv,
              desk_paid_egp, deposit_egp_equiv, actual_paid_total_egp, supplier_cost_egp,
              estimated_revenue_egp, estimated_cogs_egp, estimated_profit_egp, vessel_code,
              boarding_status, payment_status, booking_state, workflow_stage, source_file,
              seller_type, seller_username, seller_commission_egp, commission_type, commission_status)
             VALUES (?,?,?,?,?,?,?, ?,?,?,?,?,?,?, ?,?,?,?,?,?,?, ?,?,?, ?,?,?,?, ?,?,?,?,?,?, 0,0,0,0,?,?,?,?, ?,?,?,?, ?,?,?,?,?, ?,?,?,?, ?,?,?, ?,?,?,?,?, ?, ?,?,?,?,?)'
        );
        $ins->execute([
            $voNo, $qrTok, $tripDt, $wk, $mo, $qr, $yr,
            $branch, 'MOBILE_APP', $hotel, '', $room, $nat, $pickup,
            $tripName, $masterTrip ? (string) $masterTrip['trip_code'] : $prodCode, 'MOBILE_VOUCHER', $adults, $children, $infants, $pax,
            $adlEur, $chdEur, $infEur,
            $adults, $children, $infants, $grossEgp,
            $listTotalEgp, $discTypeOut, $discValOut, $discCurrOut, $discEgpOut, $reqDiscReason,
            $guest, $guestPh, $guestEm, $safariBreakdownJson,
            $repName, $supName, $supCode !== 'SEA_COLLEGE' ? $supCode : '', 'DIRECT',
            $rEgp, $rUsd, $rEur, $rGbp, $restEq,
            $depositEgp, $depositEgp, $depositEgp, $supplierCostEgp,
            $grossEgp, $cogsEgp, $profEgp,
            '', 'BOOKED', $restEq > 0 ? 'OPEN_REST' : 'PAID', 'EXECUTED', 'SELLER_CONFIRMED_PENDING_OPS', 'LIVE_MOBILE_APP',
            $sellerType,
            $commRes['seller_username'],
            $commRes['commission_egp'],
            $commRes['commission_type'],
            $commRes['commission_status'],
        ]);
        $createdVid = (int) $pdo->lastInsertId();

        // Automatically provision Verified Real-Customer Account (even if deposit_amount = 0 / OPEN_REST) & send login email if optional email provided!
        $guestAcctRes = sc_erp_provision_verified_guest_account($pdo, [
            'voucher_no'      => $voNo,
            'guest_name'      => $guest,
            'guest_phone'     => $guestPh,
            'guest_email'     => $guestEm,
            'nationality'     => $nat,
            'lang'            => $guestLg,
            'hotel_name'      => $hotel,
            'room_no'         => $room,
            'seller_username' => $resolvedSeller ? (string) $resolvedSeller['username'] : '',
            'trip_name'       => $tripName,
            'trip_date'       => $tripDt,
            'booked_egp'      => $grossEgp,
            'paid_egp'        => $depositEgp,
            'booking_status'  => $restEq > 0.01 ? 'CONFIRMED_UNPAID' : 'CONFIRMED_PAID',
        ]);

        sc_erp_push_notification(
            $pdo,
            'ROLE:OPERATIONS_MANAGER',
            'SELLER_CONFIRMED',
            $voNo,
            "📋 New Seller Voucher Ready for Dispatch ({$voNo})",
            "📋 حجز جديد من المبيعات بانتظار تأكيد الحجوزات والتشغيل ({$voNo})",
            "{$repName}: {$guest} ({$adults} Adl, {$children} Ch, {$infants} Inf) — {$tripName} on {$tripDt}" . ($discEgpOut > 0 ? " [Special Discount -" . number_format($discEgpOut) . " EGP]" : "") . ".",
            "{$repName}: العميل {$guest} ({$adults} بالغ، {$children} طفل، {$infants} رضيع) — {$tripName} بتاريخ {$tripDt}" . ($discEgpOut > 0 ? " [خصم خاص -" . number_format($discEgpOut) . " ج.م]" : "") . "."
        );

        sc_erp_audit($pdo, (string) $user['username'], (string) $user['role_code'], 'CREATE_VOUCHER', 'VOUCHER', $voNo, [
            'pax'            => $pax,
            'list_total_egp' => $listTotalEgp,
            'discount_type'  => $discTypeOut,
            'discount_value' => $discValOut,
            'discount_egp'   => $discEgpOut,
            'gross_egp'      => $grossEgp,
            'sale_mode'      => $saleModeRaw,
            'commission_egp' => $commRes['commission_egp'],
            'guest_username' => $guestAcctRes['guest_username'] ?? '',
            'email_sent'     => !empty($guestAcctRes['email_sent']),
        ]);

        echo json_encode([
            'ok'                    => true,
            'id'                    => $createdVid,
            'voucher_id'            => $createdVid,
            'voucher_no'            => $voNo,
            'qr_token'              => $qrTok,
            'adults'                => $adults,
            'children'              => $children,
            'infants'               => $infants,
            'list_total_egp'        => $listTotalEgp,
            'discount_type'         => $discTypeOut,
            'discount_value'        => $discValOut,
            'discount_currency'     => $discCurrOut,
            'discount_egp'          => $discEgpOut,
            'was_discount_clamped'  => $discClamped,
            'net_total_egp'         => $grossEgp,
            'estimated_revenue_egp' => $grossEgp,
            'gross_amount_egp'      => $grossEgp,
            'deposit_egp'           => $depositEgp,
            'rest_egp'              => $restEq,
            'commission_egp'        => $commRes['commission_egp'],
            'commission_type'       => $commRes['commission_type'],
            'commission_status'     => $commRes['commission_status'],
            'supplier_code'         => $supCode,
            'supplier_cost_egp'     => $supplierCostEgp,
            'safari_breakdown'      => !empty($safariCalc['has_items']) ? $safariCalc : null,
            'guest_account'         => $guestAcctRes,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'assign_dispatch') {
        erp_require_perm($ctx, 'ops.assign_boat_driver');
        $id     = (int) ($rawIn['id'] ?? 0);
        $vessel = clean_line($rawIn['vessel_code'] ?? 'OCEANO', 32);
        $pickup = clean_line($rawIn['pickup_time'] ?? '07:40', 20);
        $upd = $pdo->prepare("UPDATE reservations_fact SET vessel_code = ?, pickup_time = ?, boarding_status = 'DISPATCHED', workflow_stage = 'CONFIRMED_OPS' WHERE id = ?");
        $upd->execute([$vessel, $pickup, $id]);
        sc_erp_audit($pdo, (string) $ctx['user']['username'], (string) $ctx['user']['role_code'], 'ASSIGN_BOAT', 'VOUCHER_ID', (string) $id, ['vessel' => $vessel]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'seller_confirm_booking') {
        erp_require_perm($ctx, 'sales.view_own_sales', 'ops.assign_boat_driver', 'bi.view_executive');
        $id   = (int) ($rawIn['id'] ?? 0);
        $voIn = trim((string) ($rawIn['voucher_no'] ?? ''));
        $optEmail = strtolower(clean_line($rawIn['guest_email'] ?? ($rawIn['email'] ?? ''), 160));
        $optPhone = clean_line($rawIn['guest_phone'] ?? ($rawIn['phone'] ?? ''), 48);
        $vSt  = $id > 0
            ? $pdo->prepare('SELECT * FROM reservations_fact WHERE id = ?')
            : $pdo->prepare('SELECT * FROM reservations_fact WHERE voucher_no = ? ORDER BY id DESC LIMIT 1');
        $vSt->execute([$id > 0 ? $id : $voIn]);
        $v = $vSt->fetch();
        if (!$v) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Booking not found']);
            exit;
        }
        if ($ctx['user']['role_code'] === 'SALES_REP' && strtolower((string) $v['seller_username']) !== strtolower((string) $ctx['user']['username'])) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Cannot confirm another seller booking']);
            exit;
        }
        $voNo = (string) $v['voucher_no'];
        $pdo->prepare("UPDATE reservations_fact SET workflow_stage = 'SELLER_CONFIRMED_PENDING_OPS' WHERE id = ?")->execute([(int) $v['id']]);
        $pdo->prepare("UPDATE user_notifications SET is_read = 1 WHERE voucher_no = ? AND LOWER(recipient_username) = LOWER(?)")->execute([$voNo, (string) $ctx['user']['username']]);

        // Provision Verified Real-Customer Account now that Seller confirmed the booking (even if unpaid / deposit = 0)!
        $bookedVal = (float) (($v['booked_total_egp'] ?? 0) > 0 ? $v['booked_total_egp'] : ($v['estimated_revenue_egp'] ?? 0));
        $paidVal   = (float) (($v['actual_paid_total_egp'] ?? 0) > 0 ? $v['actual_paid_total_egp'] : ($v['deposit_egp_equiv'] ?? 0));
        $guestAcctRes = sc_erp_provision_verified_guest_account($pdo, [
            'voucher_no'      => $voNo,
            'guest_name'      => (string) ($v['divers_names'] ?? ''),
            'guest_phone'     => $optPhone !== '' ? $optPhone : (string) ($v['guest_phone'] ?? ''),
            'guest_email'     => $optEmail !== '' ? $optEmail : (string) ($v['guest_email'] ?? ''),
            'nationality'     => (string) ($v['nationality'] ?? ''),
            'hotel_name'      => (string) ($v['hotel_name'] ?? ''),
            'room_no'         => (string) ($v['room_no'] ?? ''),
            'seller_username' => (string) ($v['seller_username'] ?? ''),
            'trip_name'       => (string) ($v['trip_name'] ?? ''),
            'trip_date'       => (string) ($v['trip_date'] ?? ''),
            'booked_egp'      => $bookedVal,
            'paid_egp'        => $paidVal,
            'booking_status'  => str_contains(strtoupper((string) ($v['booking_state'] ?? '')), 'CANCEL')
                ? 'CANCELLED_RETAINED'
                : (((float) ($v['rest_egp_equiv'] ?? 0)) > 0.01 ? 'CONFIRMED_UNPAID' : 'CONFIRMED_PAID'),
        ]);

        sc_erp_push_notification(
            $pdo,
            'ROLE:OPERATIONS_MANAGER',
            'SELLER_CONFIRMED',
            $voNo,
            "✅ Seller Confirmed Link Booking ({$voNo}) — Ready for Reservations",
            "✅ أكد المسوق الحجز الإلكتروني ({$voNo}) — جاهز لتأكيد مدير الحجوزات",
            "Guest {$v['divers_names']} · {$v['trip_name']} ({$v['total_pax']} PAX) on {$v['trip_date']}.",
            "العميل {$v['divers_names']} · {$v['trip_name']} ({$v['total_pax']} أفراد) بتاريخ {$v['trip_date']}."
        );
        echo json_encode([
            'ok'              => true,
            'voucher_no'      => $voNo,
            'workflow_stage'  => 'SELLER_CONFIRMED_PENDING_OPS',
            'workflow_status' => 'SELLER_CONFIRMED_PENDING_OPS',
            'guest_account'   => $guestAcctRes,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'ops_confirm_reservation') {
        erp_require_perm($ctx, 'ops.view_manifests', 'ops.assign_boat_driver', 'bi.view_executive', 'gl.view_all_ledgers');
        $id       = (int) ($rawIn['id'] ?? 0);
        $voIn     = trim((string) ($rawIn['voucher_no'] ?? ''));
        $pickup   = clean_line($rawIn['pickup_time'] ?? '', 24);
        $vessel   = clean_line($rawIn['vessel_code'] ?? '', 32);
        $optEmail = strtolower(clean_line($rawIn['guest_email'] ?? ($rawIn['email'] ?? ''), 160));

        $vSt = $id > 0
            ? $pdo->prepare('SELECT * FROM reservations_fact WHERE id = ?')
            : $pdo->prepare('SELECT * FROM reservations_fact WHERE voucher_no = ? ORDER BY id DESC LIMIT 1');
        $vSt->execute([$id > 0 ? $id : $voIn]);
        $v = $vSt->fetch();
        if (!$v) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Reservation not found']);
            exit;
        }
        $voNo = (string) $v['voucher_no'];
        $newPickup = $pickup !== '' ? $pickup : (string) ($v['pickup_time'] ?: '08:00');
        $newVessel = $vessel !== '' ? $vessel : (string) ($v['vessel_code'] ?? '');
        $pdo->prepare("UPDATE reservations_fact SET workflow_stage = 'OPS_CONFIRMED', boarding_status = CASE WHEN boarding_status='BOOKED' THEN 'DISPATCHED' ELSE boarding_status END, pickup_time = ?, vessel_code = ? WHERE id = ?")
            ->execute([$newPickup, $newVessel, (int) $v['id']]);

        $guestAcctRes = null;
        $srcFileUp = strtoupper((string) ($v['source_file'] ?? ''));
        if (in_array($srcFileUp, ['LIVE_MOBILE_APP', 'SELLER_SHARE_LINK', 'LIVE_WEBSITE_FORM'], true) || !empty($v['guest_account_username']) || $optEmail !== '') {
            $bookedVal = (float) (($v['booked_total_egp'] ?? 0) > 0 ? $v['booked_total_egp'] : ($v['estimated_revenue_egp'] ?? 0));
            $paidVal   = (float) (($v['actual_paid_total_egp'] ?? 0) > 0 ? $v['actual_paid_total_egp'] : ($v['deposit_egp_equiv'] ?? 0));
            $guestAcctRes = sc_erp_provision_verified_guest_account($pdo, [
                'voucher_no'      => $voNo,
                'guest_name'      => (string) ($v['divers_names'] ?? ''),
                'guest_phone'     => (string) ($v['guest_phone'] ?? ''),
                'guest_email'     => $optEmail !== '' ? $optEmail : (string) ($v['guest_email'] ?? ''),
                'nationality'     => (string) ($v['nationality'] ?? ''),
                'hotel_name'      => (string) ($v['hotel_name'] ?? ''),
                'room_no'         => (string) ($v['room_no'] ?? ''),
                'seller_username' => (string) ($v['seller_username'] ?? ''),
                'trip_name'       => (string) ($v['trip_name'] ?? ''),
                'trip_date'       => (string) ($v['trip_date'] ?? ''),
                'booked_egp'      => $bookedVal,
                'paid_egp'        => $paidVal,
                'booking_status'  => ((float) ($v['rest_egp_equiv'] ?? 0)) > 0.01 ? 'CONFIRMED_UNPAID' : 'CONFIRMED_PAID',
            ]);
        }

        sc_erp_push_notification(
            $pdo,
            'ROLE:CHIEF_ACCOUNTANT',
            'OPS_CONFIRMED',
            $voNo,
            "📑 Confirmed Reservation Posted to Accounting ({$voNo})",
            "📑 حجز مؤكد من مدير الحجوزات جاهز للمتابعة المالية ({$voNo})",
            "{$v['divers_names']} · {$v['trip_name']} · Total: " . number_format((float) $v['estimated_revenue_egp']) . " EGP · Paid: " . number_format((float) $v['deposit_egp_equiv']) . " EGP · Rest: " . number_format((float) $v['rest_egp_equiv']) . " EGP.",
            "العميل {$v['divers_names']} · {$v['trip_name']} · الإجمالي: " . number_format((float) $v['estimated_revenue_egp']) . " ج.م · المدفوع: " . number_format((float) $v['deposit_egp_equiv']) . " ج.م · المتبقي للتحصيل: " . number_format((float) $v['rest_egp_equiv']) . " ج.م."
        );
        echo json_encode([
            'ok'              => true,
            'voucher_no'      => $voNo,
            'workflow_stage'  => 'OPS_CONFIRMED',
            'workflow_status' => 'OPS_CONFIRMED',
            'guest_account'   => $guestAcctRes,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'collect_rest_board') {
        erp_require_perm($ctx, 'jetty.scan_boarding_collect_rest', 'safe.record_tx', 'ops.assign_boat_driver', 'gl.view_all_ledgers');
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
        $totalPaidNow   = round((float) ($v['deposit_egp_equiv'] ?? 0.0) + (float) ($v['collected_rest_egp'] ?? 0.0) + $collectedEgp, 2);
        if ($totalPaidNow <= 0.0) {
            $totalPaidNow = (float) ($v['estimated_revenue_egp'] ?? 0.0);
        }

        $pdo->prepare(
            "UPDATE reservations_fact
             SET rest_egp = 0, rest_usd = 0, rest_eur = 0, rest_gbp = 0, rest_egp_equiv = 0,
                 collected_rest_egp = ROUND(collected_rest_egp + ?, 2),
                 actual_paid_total_egp = ?,
                 payment_status = 'PAID',
                 boarding_status = 'BOARDED',
                 workflow_stage = 'COMPLETED_SETTLED',
                 commission_status = CASE WHEN seller_username != '' AND commission_type != 'NONE' THEN 'EARNED_UNPAID' ELSE commission_status END
             WHERE id = ?"
        )->execute([$collectedEgp, $totalPaidNow, $id]);

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

        sc_erp_sync_guest_account_on_voucher_change($pdo, $v, 'CONFIRMED_PAID');

        if (!empty($v['seller_username'])) {
            sc_erp_push_notification(
                $pdo,
                (string) $v['seller_username'],
                'COMMISSION_UNLOCKED',
                $voNo,
                "💰 Trip Done & 100% Paid ({$voNo}) — Commission Unlocked!",
                "💰 تمت الرحلة وتحصيل كامل المبلغ ({$voNo}) — العمولة مستحقة الآن!",
                "Guest {$v['divers_names']} completed {$v['trip_name']} & full payment was collected.",
                "أتم العميل {$v['divers_names']} رحلة {$v['trip_name']} وتم تحصيل كامل المبلغ في الخزينة."
            );
        }

        sc_erp_audit($pdo, (string) $ctx['user']['username'], (string) $ctx['user']['role_code'], 'JETTY_BOARD_COLLECT_REST', 'VOUCHER', $voNo, ['collected_egp' => $collectedEgp]);
        echo json_encode(['ok' => true, 'collected_egp' => $collectedEgp, 'actual_paid_total_egp' => $totalPaidNow, 'commission_status' => 'EARNED_UNPAID']);
        exit;
    }

    if ($action === 'modify_voucher_settlement') {
        erp_require_perm($ctx, 'ops.view_manifests', 'ops.assign_boat_driver', 'jetty.scan_boarding_collect_rest', 'safe.record_tx', 'gl.view_all_ledgers', 'bi.view_executive');
        $id   = (int) ($rawIn['id'] ?? 0);
        $voIn = trim((string) ($rawIn['voucher_no'] ?? ''));
        $vSt  = $id > 0
            ? $pdo->prepare('SELECT * FROM reservations_fact WHERE id = ?')
            : $pdo->prepare('SELECT * FROM reservations_fact WHERE voucher_no = ? ORDER BY id DESC LIMIT 1');
        $vSt->execute([$id > 0 ? $id : $voIn]);
        $v = $vSt->fetch();
        if (!$v) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Voucher not found']);
            exit;
        }

        $vid  = (int) $v['id'];
        $voNo = (string) $v['voucher_no'];
        $fx   = sc_erp_fx_rates($pdo);

        $prevAdl = (int) ($v['adults'] ?? 0);
        $prevChd = (int) ($v['children'] ?? 0);
        $prevInf = (int) ($v['infants'] ?? 0);
        $prevTot = round((float) ($v['estimated_revenue_egp'] ?? 0.0), 2);
        $prevCom = round((float) ($v['seller_commission_egp'] ?? 0.0), 2);

        $adlKey = array_key_exists('new_adults', $rawIn) ? 'new_adults' : (array_key_exists('adults', $rawIn) ? 'adults' : null);
        $chdKey = array_key_exists('new_children', $rawIn) ? 'new_children' : (array_key_exists('children', $rawIn) ? 'children' : null);
        $infKey = array_key_exists('new_infants', $rawIn) ? 'new_infants' : (array_key_exists('infants', $rawIn) ? 'infants' : null);

        $newAdl = $adlKey !== null ? max(0, min(60, (int) $rawIn[$adlKey])) : $prevAdl;
        $newChd = $chdKey !== null ? max(0, min(40, (int) $rawIn[$chdKey])) : $prevChd;
        $newInf = $infKey !== null ? max(0, min(20, (int) $rawIn[$infKey])) : $prevInf;
        if (!empty($rawIn['cancel_trip'])) {
            $newAdl = 0;
            $newChd = 0;
            $newInf = 0;
        }
        $newPax = $newAdl + $newChd + $newInf;

        $reason = clean_line($rawIn['reason'] ?? 'PAX / Settlement updated at pickup/Jetty', 240);
        if ($newPax === 0) {
            $pdo->prepare(
                "UPDATE reservations_fact
                 SET adults = 0, children = 0, infants = 0, total_pax = 0,
                     cancelled_pax = ?, rest_egp = 0, rest_usd = 0, rest_eur = 0, rest_gbp = 0, rest_egp_equiv = 0,
                     booking_state = 'CANCELLED', boarding_status = 'CANCELLED', payment_status = 'CANCELLED',
                     workflow_stage = 'CANCELLED', seller_commission_egp = 0, commission_status = 'REVERSED_CANCELLED',
                     has_modification = 1, modification_summary = ?
                 WHERE id = ?"
            )->execute([$prevAdl + $prevChd + $prevInf, 'FULL_CANCEL: ' . $reason, $vid]);
            $pdo->prepare("UPDATE seller_commissions_ledger SET pax = 0, commission_egp = 0, status = 'REVERSED_CANCELLED', notes = ? WHERE voucher_no = ?")
                ->execute([$reason, $voNo]);
            $pdo->prepare(
                'INSERT INTO voucher_change_audit_log
                 (created_at, voucher_no, trip_date, period_month, seller_username, changed_by_username, changed_by_role,
                  change_type, prev_adults, prev_children, prev_infants, new_adults, new_children, new_infants,
                  prev_total_egp, new_total_egp, delta_total_egp, extra_collected_egp, prev_comm_egp, new_comm_egp, reason_notes)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                gmdate('Y-m-d\TH:i:s\Z'), $voNo, (string) $v['trip_date'], (string) $v['period_month'], (string) ($v['seller_username'] ?? ''),
                (string) $ctx['user']['username'], (string) $ctx['user']['role_code'],
                'FULL_CANCEL', $prevAdl, $prevChd, $prevInf, 0, 0, 0,
                $prevTot, 0.0, -$prevTot, 0.0, $prevCom, 0.0, $reason,
            ]);
            // Retain (or create!) the guest's verified account as ACTIVE even on full cancellation!
            $guestSync = sc_erp_sync_guest_account_on_voucher_change($pdo, $v, 'CANCELLED_RETAINED');
            echo json_encode(['ok' => true, 'voucher_no' => $voNo, 'change_type' => 'FULL_CANCEL', 'status' => 'CANCELLED', 'new_commission_egp' => 0.0, 'guest_account' => $guestSync]);
            exit;
        }

        // Lookup unified trip & unit prices (respecting explicit adult_price_eur / child_price_eur or stored voucher prices!)
        $tripMeta = sc_erp_lookup_unified_trip($pdo, (string) (($v['unified_trip_code'] ?? '') !== '' ? $v['unified_trip_code'] : $v['trip_name']));
        $adlEur = ((float) ($rawIn['adult_price_eur'] ?? 0)) > 0
            ? (float) $rawIn['adult_price_eur']
            : (((float) ($v['adult_unit_eur'] ?? 0)) > 0 ? (float) $v['adult_unit_eur'] : ($tripMeta ? (float) $tripMeta['adult_sell_eur'] : 30.0));
        $chdEur = array_key_exists('child_price_eur', $rawIn) && (float) $rawIn['child_price_eur'] >= 0
            ? (float) $rawIn['child_price_eur']
            : (((float) ($v['child_unit_eur'] ?? 0)) > 0 ? (float) $v['child_unit_eur'] : ($tripMeta ? (float) $tripMeta['child_sell_eur'] : round($adlEur * 0.5, 2)));
        $infEur = ((float) ($v['infant_unit_eur'] ?? 0)) > 0 ? (float) $v['infant_unit_eur'] : 0.0;

        $deltaFromPaxEgp = round((($newAdl - $prevAdl) * $adlEur + ($newChd - $prevChd) * $chdEur + ($newInf - $prevInf) * $infEur) * $fx['EUR'], 2);

        $extraAmt  = (float) ($rawIn['extra_charge_amount'] ?? 0.0);
        $extraCurr = strtoupper(trim((string) ($rawIn['extra_charge_currency'] ?? 'EUR')));
        $extraExplicitEgp = $extraAmt != 0.0 ? round($extraAmt * ($fx[$extraCurr] ?? $fx['EUR']), 2) : 0.0;

        // Recalculate actual supplier cost for actual Adults + Children
        $adlCost = $tripMeta ? (float) $tripMeta['adult_cost_egp'] : 0.0;
        $chdCost = $tripMeta ? (float) $tripMeta['child_cost_egp'] : round($adlCost * 0.5, 2);
        $infCost = $tripMeta ? (float) $tripMeta['infant_cost_egp'] : 0.0;
        $newSupCostEgp = $tripMeta
            ? round($newAdl * $adlCost + $newChd * $chdCost + $newInf * $infCost, 2)
            : round((float) ($v['supplier_cost_egp'] ?? 0.0), 2);

        $commType   = (string) ($v['commission_type'] ?? 'INSHEET_10');
        $isOutSide  = (str_contains($commType, 'OUT_SIDE') || str_contains($commType, 'OUTSIDE'));

        $vDiscType = array_key_exists('discount_type', $rawIn)
            ? strtoupper(trim((string) $rawIn['discount_type']))
            : strtoupper(trim((string) ($v['discount_type'] ?? 'NONE')));
        $vDiscVal  = array_key_exists('discount_value', $rawIn)
            ? max(0.0, (float) $rawIn['discount_value'])
            : max(0.0, (float) ($v['discount_value'] ?? 0.0));
        $vDiscCurr = array_key_exists('discount_currency', $rawIn)
            ? strtoupper(trim((string) $rawIn['discount_currency']))
            : strtoupper(trim((string) ($v['discount_currency'] ?? 'EUR')));

        $newListTotalEgp = round(max(0.0, ($newAdl * $adlEur + $newChd * $chdEur + $newInf * $infEur) * $fx['EUR']), 2);
        $newDiscEgp      = 0.0;

        if (array_key_exists('override_total_egp', $rawIn) && (float) $rawIn['override_total_egp'] > 0) {
            $newTotalEgp = round((float) $rawIn['override_total_egp'], 2);
            $newDiscEgp  = round(max(0.0, $newListTotalEgp - $newTotalEgp), 2);
        } elseif ($vDiscType !== 'NONE' && $vDiscVal > 0.0 && $newListTotalEgp > 0.0 && $extraExplicitEgp == 0.0) {
            $discAdj = sc_erp_calculate_discount(
                $newListTotalEgp,
                $isOutSide ? $newSupCostEgp : 0.0,
                $vDiscType,
                $vDiscVal,
                $vDiscCurr,
                $fx,
                ['is_enabled' => true, 'max_discount_pct' => 100.0, 'max_discount_eur' => 100000.0],
                true
            );
            $newTotalEgp = $discAdj['net_total_egp'];
            $newDiscEgp  = $discAdj['discount_egp'];
            $vDiscType   = $discAdj['discount_type'];
            $vDiscVal    = $discAdj['discount_value'];
        } elseif (array_key_exists('adult_price_eur', $rawIn) && (float) $rawIn['adult_price_eur'] > 0) {
            $newTotalEgp = $newListTotalEgp;
        } else {
            $newTotalEgp = round(max(0.0, $prevTot + ($extraExplicitEgp != 0.0 ? $extraExplicitEgp : $deltaFromPaxEgp)), 2);
            $newListTotalEgp = max($newListTotalEgp, $newTotalEgp);
        }

        $deltaTotalEgp = round($newTotalEgp - $prevTot, 2);

        $depositPaid   = round((float) ($v['deposit_egp_equiv'] ?? 0.0), 2);
        $prevCollRest  = round((float) ($v['collected_rest_egp'] ?? 0.0), 2);
        $alreadyPaid   = round($depositPaid + $prevCollRest, 2);
        $remainingBeforeCollect = round(max(0.0, $newTotalEgp - $alreadyPaid), 2);

        $collectRestNow = !empty($rawIn['collect_rest_now']) || !empty($rawIn['collect_all_remaining_rest']);
        $markTripDone   = array_key_exists('mark_trip_done', $rawIn)
            ? (bool) $rawIn['mark_trip_done']
            : (array_key_exists('mark_boarded', $rawIn) ? (bool) $rawIn['mark_boarded'] : ($collectRestNow || strtoupper((string) $v['boarding_status']) === 'BOARDED'));

        $explicitColl = (float) ($rawIn['collected_amount_egp'] ?? ($rawIn['extra_collected_egp'] ?? 0.0));
        $newlyCollectedEgp = $collectRestNow ? $remainingBeforeCollect : max(0.0, round($explicitColl, 2));
        $newlyCollectedEgp = min($remainingBeforeCollect, $newlyCollectedEgp);

        $newRestEquiv      = round(max(0.0, $remainingBeforeCollect - $newlyCollectedEgp), 2);
        $newCollectedRest  = round($prevCollRest + $newlyCollectedEgp, 2);
        $actualPaidTotal   = round($depositPaid + $newCollectedRest, 2);

        // Determine change classification for Accounting & Owners Audit
        if ($newInf < $prevInf && $newChd > $prevChd) {
            $changeType = 'INFANT_TO_CHILD_UPGRADE';
        } elseif ($deltaTotalEgp > 0.01) {
            $changeType = 'PAX_INCREASE_EXTRA_CHARGE';
        } elseif ($newPax < ($prevAdl + $prevChd + $prevInf)) {
            $changeType = 'PARTIAL_CANCEL_REDUCTION';
        } elseif ($newlyCollectedEgp > 0) {
            $changeType = 'REST_COLLECTED_SETTLED';
        } else {
            $changeType = 'VOUCHER_ADJUSTMENT';
        }

        $cancelledDelta = max(0, ($prevAdl + $prevChd + $prevInf) - $newPax);
        $newCancelledPax = (int) ($v['cancelled_pax'] ?? 0) + $cancelledDelta;

        // Commission becomes EARNED_UNPAID ("Actual Due") ONLY when markTripDone == true AND newRestEquiv <= 0.01!
        $sellerUser = (string) ($v['seller_username'] ?? '');
        $isFullyDoneAndPaid = $markTripDone && ($newRestEquiv <= 0.01);
        $newCommStatus = ($sellerUser !== '' && $commType !== 'NONE')
            ? ($isFullyDoneAndPaid ? 'EARNED_UNPAID' : 'PENDING_EXECUTION')
            : 'NONE';

        $newCommEgp = 0.0;
        if ($sellerUser !== '' && $commType !== 'NONE') {
            $commMode = $isOutSide
                ? 'OUT_SIDE'
                : ($commType === 'WEBSITE_QR_10' ? 'WEBSITE_QR_10' : 'INSHEET');
            $scChSt = $pdo->prepare('SELECT source_channel FROM seller_commissions_ledger WHERE voucher_no = ? AND LOWER(seller_username) = LOWER(?) LIMIT 1');
            $scChSt->execute([$voNo, $sellerUser]);
            $existingChannel = (string) ($scChSt->fetchColumn() ?: 'LIVE_MOBILE_APP');

            $commRes = sc_erp_record_seller_commission($pdo, [
                'seller_token'     => $sellerUser,
                'sale_mode'        => $commMode,
                'tx_date'          => (string) $v['trip_date'],
                'voucher_no'       => $voNo,
                'guest_name'       => (string) $v['divers_names'],
                'product_name'     => (string) $v['trip_name'],
                'pax'              => $newPax,
                'gross_amount_egp' => $newTotalEgp,
                'supplier_cost_egp'=> $commMode === 'OUT_SIDE' ? $newSupCostEgp : 0.0,
                'status'           => $newCommStatus,
                'source_channel'   => $existingChannel,
                'branch_code'      => (string) $v['branch_code'],
                'notes'            => $reason,
            ]);
            $newCommEgp = (float) $commRes['commission_egp'];
            $pdo->prepare('UPDATE seller_commissions_ledger SET status = ? WHERE voucher_no = ? AND LOWER(seller_username) = LOWER(?)')
                ->execute([$newCommStatus, $voNo, $sellerUser]);
        }

        $modSummary = sprintf(
            '%s: %dA/%dC/%dI -> %dA/%dC/%dI | %+.0f EGP | %s',
            $changeType, $prevAdl, $prevChd, $prevInf, $newAdl, $newChd, $newInf, $deltaTotalEgp, $reason
        );

        $newBoarding = $markTripDone ? 'BOARDED' : (string) $v['boarding_status'];
        $newPayState = $newRestEquiv <= 0.01 ? 'PAID' : 'OPEN_REST';
        $newWfStage  = $isFullyDoneAndPaid ? 'COMPLETED_SETTLED' : ($markTripDone ? 'EXECUTED_UNPAID_REST' : 'OPS_CONFIRMED');
        $newRestEur  = $newRestEquiv > 0 ? round($newRestEquiv / $fx['EUR'], 2) : 0.0;

        $pdo->prepare(
            'UPDATE reservations_fact
             SET adults = ?, children = ?, infants = ?, total_pax = ?, cancelled_pax = ?,
                 adult_unit_eur = ?, child_unit_eur = ?,
                 list_total_egp = ?, discount_type = ?, discount_value = ?, discount_currency = ?, discount_egp = ?,
                 rest_egp = 0, rest_usd = 0, rest_eur = ?, rest_gbp = 0, rest_egp_equiv = ?,
                 collected_rest_egp = ?, actual_paid_total_egp = ?,
                 supplier_cost_egp = ?, estimated_revenue_egp = ?, estimated_cogs_egp = ?, estimated_profit_egp = ?,
                 boarding_status = ?, payment_status = ?, workflow_stage = ?,
                 seller_commission_egp = ?, commission_status = ?,
                 has_modification = 1, modification_summary = ?
             WHERE id = ?'
        )->execute([
            $newAdl, $newChd, $newInf, $newPax, $newCancelledPax,
            $adlEur, $chdEur,
            $newListTotalEgp, $vDiscType, $vDiscVal, $vDiscCurr, $newDiscEgp,
            $newRestEur, $newRestEquiv,
            $newCollectedRest, $actualPaidTotal,
            $newSupCostEgp, $newTotalEgp, $newSupCostEgp, round($newTotalEgp - $newSupCostEgp, 2),
            $newBoarding, $newPayState, $newWfStage,
            $newCommEgp, $newCommStatus,
            $modSummary,
            $vid,
        ]);

        if ($newlyCollectedEgp > 0.0) {
            $safeCode = clean_line($rawIn['safe_code'] ?? 'SAFE_JETTY', 32);
            $pdo->prepare('UPDATE safes_wallets SET bal_egp = bal_egp + ?, updated_at = ? WHERE code = ?')
                ->execute([$newlyCollectedEgp, gmdate('c'), $safeCode]);
        }

        $pdo->prepare(
            'INSERT INTO voucher_change_audit_log
             (created_at, voucher_no, trip_date, period_month, seller_username, changed_by_username, changed_by_role,
              change_type, prev_adults, prev_children, prev_infants, new_adults, new_children, new_infants,
              prev_total_egp, new_total_egp, delta_total_egp, extra_collected_egp, prev_comm_egp, new_comm_egp, reason_notes)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            gmdate('Y-m-d\TH:i:s\Z'), $voNo, (string) $v['trip_date'], (string) $v['period_month'], $sellerUser,
            (string) $ctx['user']['username'], (string) $ctx['user']['role_code'],
            $changeType, $prevAdl, $prevChd, $prevInf, $newAdl, $newChd, $newInf,
            $prevTot, $newTotalEgp, $deltaTotalEgp, $newlyCollectedEgp, $prevCom, $newCommEgp, $reason,
        ]);

        foreach (['ROLE:CHIEF_ACCOUNTANT', 'ROLE:OWNER_ADMIN'] as $admRecip) {
            sc_erp_push_notification(
                $pdo,
                $admRecip,
                'VOUCHER_MODIFIED',
                $voNo,
                "🔄 Voucher Modified ({$voNo}) — {$changeType}",
                "🔄 تعديل فعلي على الحجز ({$voNo}) — {$changeType}",
                "{$modSummary} | Collected Now: " . number_format($newlyCollectedEgp) . " EGP | Actual Total: " . number_format($newTotalEgp) . " EGP.",
                "{$modSummary} | المحصل الآن: " . number_format($newlyCollectedEgp) . " ج.م | الإجمالي الفعلي: " . number_format($newTotalEgp) . " ج.م."
            );
        }
        if ($sellerUser !== '') {
            sc_erp_push_notification(
                $pdo,
                $sellerUser,
                'VOUCHER_MODIFIED',
                $voNo,
                "📊 Actual Guests/Settlement Updated ({$voNo})",
                "📊 تحديث العدد والمبلغ الفعلي للحجز ({$voNo})",
                "{$modSummary} -> Commission: " . number_format($newCommEgp) . " EGP ({$newCommStatus}).",
                "{$modSummary} -> العمولة الفعلية: " . number_format($newCommEgp) . " ج.م ({$newCommStatus})."
            );
        }

        if (!empty($v['guest_account_username'])) {
            sc_erp_sync_guest_account_on_voucher_change($pdo, $v, $isFullyDoneAndPaid ? 'CONFIRMED_PAID' : 'CONFIRMED_UNPAID');
        }

        echo json_encode([
            'ok'                    => true,
            'voucher_no'            => $voNo,
            'change_type'           => $changeType,
            'prev_total_egp'        => $prevTot,
            'new_total_egp'         => $newTotalEgp,
            'delta_total_egp'       => $deltaTotalEgp,
            'extra_charge_egp'      => $deltaTotalEgp,
            'newly_collected_egp'   => $newlyCollectedEgp,
            'actual_paid_total_egp' => $actualPaidTotal,
            'remaining_rest_egp'    => $newRestEquiv,
            'rest_egp_equiv'        => $newRestEquiv,
            'new_supplier_cost_egp' => $newSupCostEgp,
            'new_commission_egp'    => $newCommEgp,
            'commission_status'     => $newCommStatus,
            'workflow_stage'        => $newWfStage,
            'workflow_status'       => $newWfStage,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'request_commission_advance') {
        erp_require_perm($ctx, 'sales.view_own_sales', 'hr.manage_payroll', 'bi.view_executive');
        $me = $ctx['user'];
        $targetUser = $me['role_code'] === 'SALES_REP'
            ? (string) $me['username']
            : clean_line($rawIn['seller_username'] ?? $me['username'], 64);
        $sellerRow = sc_erp_resolve_seller($pdo, $targetUser) ?: $me;
        $sUser = (string) $sellerRow['username'];
        $sRep  = (string) ($sellerRow['rep_scope'] ?? $sUser);
        $amt   = round(max(0.0, (float) ($rawIn['amount_egp'] ?? 0.0)), 2);
        $txType = in_array(($rawIn['tx_type'] ?? ''), ['ALLOWANCE_ADVANCE', 'INSTALLMENT_PAYOUT', 'MONTH_END_SETTLEMENT'], true)
            ? (string) $rawIn['tx_type']
            : 'ALLOWANCE_ADVANCE';
        $notes = clean_line($rawIn['notes'] ?? 'Mid-month commission allowance request', 200);
        if ($amt <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Amount must be greater than zero']);
            exit;
        }
        $today = gmdate('Y-m-d');
        $reqMo = (string) ($rawIn['period_month'] ?? ($rawIn['month'] ?? ''));
        $mo    = preg_match('/^\d{4}-\d{2}$/', $reqMo) ? $reqMo : substr($today, 0, 7);
        $autoApprove = in_array($me['role_code'], ['OWNER_ADMIN', 'CHIEF_ACCOUNTANT', 'CASHIER_TREASURY'], true) && !empty($rawIn['approve_immediately']);
        $status = $autoApprove ? 'APPROVED_PAID' : 'REQUESTED';
        $appBy  = $autoApprove ? (string) $me['username'] : '';

        $pdo->prepare(
            'INSERT INTO seller_commission_advances
             (created_at, period_month, tx_date, seller_username, rep_scope, tx_type, amount_egp, status, approved_by, notes)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        )->execute([gmdate('Y-m-d\TH:i:s\Z'), $mo, $today, $sUser, $sRep, $txType, $amt, $status, $appBy, $notes]);
        $advId = (int) $pdo->lastInsertId();

        if (!$autoApprove) {
            sc_erp_push_notification(
                $pdo,
                'ROLE:CHIEF_ACCOUNTANT',
                'ALLOWANCE_STATUS',
                'ADV-' . $advId,
                "💵 Commission Allowance Request ({$sRep}: " . number_format($amt) . " EGP)",
                "💵 طلب دفعة / سلفة من العمولة المستحقة ({$sRep}: " . number_format($amt) . " ج.م)",
                "{$sUser} requested " . number_format($amt) . " EGP ({$notes}).",
                "طلب المسوق {$sRep} صرف مبلغ " . number_format($amt) . " ج.م تحت حساب العمولة المستحقة ({$notes})."
            );
        }
        echo json_encode(['ok' => true, 'advance_id' => $advId, 'status' => $status, 'amount_egp' => $amt]);
        exit;
    }

    if ($action === 'decide_commission_advance') {
        erp_require_perm($ctx, 'gl.view_all_ledgers', 'bi.view_executive', 'safe.record_tx', 'hr.manage_payroll');
        $advId = (int) ($rawIn['advance_id'] ?? 0);
        $rawDec = strtoupper(trim((string) ($rawIn['decision'] ?? 'APPROVED_PAID')));
        $decision = ($rawDec === 'PAID' || $rawDec === 'APPROVED') ? 'APPROVED_PAID' : (in_array($rawDec, ['APPROVED_PAID', 'REJECTED'], true) ? $rawDec : 'APPROVED_PAID');
        $aSt = $pdo->prepare('SELECT * FROM seller_commission_advances WHERE id = ?');
        $aSt->execute([$advId]);
        $adv = $aSt->fetch();
        if (!$adv) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Allowance request not found']);
            exit;
        }
        $pdo->prepare('UPDATE seller_commission_advances SET status = ?, approved_by = ? WHERE id = ?')
            ->execute([$decision, (string) $ctx['user']['username'], $advId]);

        sc_erp_push_notification(
            $pdo,
            (string) $adv['seller_username'],
            'ALLOWANCE_STATUS',
            'ADV-' . $advId,
            $decision === 'APPROVED_PAID' ? '✅ Commission Allowance Approved & Paid' : '❌ Commission Allowance Declined',
            $decision === 'APPROVED_PAID' ? '✅ تمت الموافقة وصرف الدفعة من عمولتك' : '❌ تم رفض طلب السلفة',
            number_format((float) $adv['amount_egp']) . ' EGP — ' . (string) $adv['notes'],
            number_format((float) $adv['amount_egp']) . ' ج.م — ' . (string) $adv['notes']
        );
        echo json_encode(['ok' => true, 'advance_id' => $advId, 'status' => $decision]);
        exit;
    }

    if ($action === 'save_seller_targets' || $action === 'save_seller_discount_policy') {
        erp_require_perm($ctx, 'bi.view_executive');
        $rawTargetSeller = clean_line($rawIn['seller_username'] ?? '__GLOBAL__', 64) ?: '__GLOBAL__';
        $targetSeller = ($rawTargetSeller === '*' || strtoupper($rawTargetSeller) === 'ALL') ? '__GLOBAL__' : $rawTargetSeller;

        $exSt = $pdo->prepare('SELECT * FROM seller_targets_config WHERE LOWER(seller_username) = LOWER(?) LIMIT 1');
        $exSt->execute([$targetSeller]);
        $exRow = $exSt->fetch() ?: [];

        if (array_key_exists('is_active', $rawIn) || array_key_exists('is_target_active', $rawIn)) {
            $activeKey = array_key_exists('is_active', $rawIn) ? 'is_active' : 'is_target_active';
            $isActive  = !empty($rawIn[$activeKey]) ? 1 : 0;
        } else {
            $isActive  = isset($exRow['is_target_active']) ? (int) $exRow['is_target_active'] : 1;
        }

        $dailyEgp     = max(0.0, (float) ($rawIn['target_daily_egp'] ?? ($rawIn['daily_target_egp'] ?? ($exRow['daily_target_egp'] ?? 15000.0))));
        $weeklyEgp    = max(0.0, (float) ($rawIn['target_weekly_egp'] ?? ($rawIn['weekly_target_egp'] ?? ($exRow['weekly_target_egp'] ?? 95000.0))));
        $monthlyEgp   = max(0.0, (float) ($rawIn['target_monthly_egp'] ?? ($rawIn['monthly_target_egp'] ?? ($exRow['monthly_target_egp'] ?? 380000.0))));
        $monthlyTrips = max(1, (int) ($rawIn['monthly_trips_target'] ?? ($exRow['monthly_trips_target'] ?? 65)));

        $isDiscEnabled = array_key_exists('is_discount_enabled', $rawIn)
            ? (!empty($rawIn['is_discount_enabled']) ? 1 : 0)
            : (isset($exRow['is_discount_enabled']) ? (int) $exRow['is_discount_enabled'] : 1);
        $maxDiscPct = array_key_exists('max_discount_pct', $rawIn)
            ? max(0.0, min(80.0, (float) $rawIn['max_discount_pct']))
            : (float) ($exRow['max_discount_pct'] ?? 20.0);
        $maxDiscEur = array_key_exists('max_discount_eur', $rawIn)
            ? max(0.0, min(1000.0, (float) $rawIn['max_discount_eur']))
            : (float) ($exRow['max_discount_eur'] ?? 50.0);

        $pdo->prepare(
            'INSERT OR REPLACE INTO seller_targets_config
             (seller_username, is_target_active, daily_target_egp, weekly_target_egp, monthly_target_egp, monthly_trips_target,
              is_discount_enabled, max_discount_pct, max_discount_eur, updated_by, updated_at)
             VALUES (?,?,?,?,?,?, ?,?,?,?,?)'
        )->execute([
            $targetSeller, $isActive, $dailyEgp, $weeklyEgp, $monthlyEgp, $monthlyTrips,
            $isDiscEnabled, $maxDiscPct, $maxDiscEur,
            (string) $ctx['user']['username'], gmdate('Y-m-d\TH:i:s\Z'),
        ]);

        sc_erp_audit($pdo, (string) $ctx['user']['username'], (string) $ctx['user']['role_code'], 'UPDATE_SELLER_POLICY', 'SELLER_POLICY', $targetSeller, [
            'is_target_active'    => $isActive,
            'is_discount_enabled' => $isDiscEnabled,
            'max_discount_pct'    => $maxDiscPct,
            'max_discount_eur'    => $maxDiscEur,
        ]);

        echo json_encode([
            'ok'                  => true,
            'seller_username'     => $targetSeller,
            'is_active'           => $isActive === 1,
            'is_target_active'    => $isActive === 1,
            'daily_target_egp'    => $dailyEgp,
            'weekly_target_egp'   => $weeklyEgp,
            'monthly_target_egp'  => $monthlyEgp,
            'is_discount_enabled' => $isDiscEnabled === 1,
            'max_discount_pct'    => $maxDiscPct,
            'max_discount_eur'    => $maxDiscEur,
        ]);
        exit;
    }

    if ($action === 'create_promo_share_link') {
        erp_require_perm($ctx, 'sales.create_voucher', 'sales.view_own_sales', 'bi.view_executive');
        $me = $ctx['user'];
        $isOwner = in_array('bi.view_executive', $ctx['perms'], true);
        $targetUser = ($me['role_code'] === 'SALES_REP' || empty($rawIn['seller_username']))
            ? (string) $me['username']
            : clean_line($rawIn['seller_username'], 64);
        $sellerRow = sc_erp_resolve_seller($pdo, $targetUser) ?: $me;
        $sUser = (string) $sellerRow['username'];
        $sSlug = (string) (($sellerRow['referral_code'] ?? '') !== '' ? $sellerRow['referral_code'] : preg_replace('/\.sales$/i', '', $sUser));
        if ($sSlug === '') {
            $sSlug = 'fady';
        }

        $policy = sc_erp_get_seller_discount_policy($pdo, $sUser);
        if (!$policy['is_enabled'] && !$isOwner) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Special seller discounts are currently disabled by management']);
            exit;
        }

        $rawType  = strtoupper(trim((string) ($rawIn['discount_type'] ?? 'PERCENT')));
        $discType = in_array($rawType, ['AMOUNT', 'FIXED', 'CASH', 'VALUE'], true) ? 'AMOUNT' : 'PERCENT';
        $discVal  = max(0.0, (float) ($rawIn['discount_value'] ?? 0.0));
        $fx       = sc_erp_fx_rates($pdo);
        $discCurr = strtoupper(trim((string) ($rawIn['discount_currency'] ?? 'EUR')));
        if (!isset($fx[$discCurr])) {
            $discCurr = 'EUR';
        }
        $tripCode = clean_line($rawIn['trip_code'] ?? '', 64);
        $lang     = strtolower(clean_line($rawIn['lang'] ?? 'en', 8));
        if (!in_array($lang, ['en', 'ar', 'ru', 'de', 'it', 'fr', 'pl', 'zh'], true)) {
            $lang = 'en';
        }

        $baseUrl = 'https://seacollegediveclub.com'
            . ($lang !== 'en' ? '/' . rawurlencode($lang) : '')
            . '/s/' . rawurlencode($sSlug)
            . ($tripCode !== '' ? '/' . rawurlencode($tripCode) : '');

        if ($discVal <= 0.0) {
            echo json_encode([
                'ok'                => true,
                'promo_token'       => '',
                'promo_url'         => $baseUrl,
                'discount_type'     => 'NONE',
                'discount_value'    => 0.0,
                'discount_currency' => $discCurr,
                'was_clamped'       => false,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $wasClamped = false;
        if ($discType === 'PERCENT') {
            $maxPct = $isOwner ? 80.0 : (float) $policy['max_discount_pct'];
            if ($discVal > $maxPct + 0.001) {
                $discVal = $maxPct;
                $wasClamped = true;
            }
        } else {
            $maxEur = $isOwner ? 500.0 : (float) $policy['max_discount_eur'];
            $maxInCurr = round(($maxEur * $fx['EUR']) / $fx[$discCurr], 2);
            if ($discVal > $maxInCurr + 0.01) {
                $discVal = $maxInCurr;
                $wasClamped = true;
            }
        }
        $discVal = round($discVal, 2);

        $token = sc_erp_sign_promo_token($sSlug, $tripCode, $discType, $discVal, $discCurr, 14);
        $promoUrl = $baseUrl . '?promo=' . rawurlencode($token);

        sc_erp_audit($pdo, (string) $me['username'], (string) $me['role_code'], 'CREATE_PROMO_LINK', 'PROMO_LINK', $sSlug, [
            'trip_code'      => $tripCode,
            'discount_type'  => $discType,
            'discount_value' => $discVal,
            'currency'       => $discCurr,
            'was_clamped'    => $wasClamped,
        ]);

        echo json_encode([
            'ok'                => true,
            'seller_slug'       => $sSlug,
            'trip_code'         => $tripCode,
            'promo_token'       => $token,
            'promo_url'         => $promoUrl,
            'discount_type'     => $discType,
            'discount_value'    => $discVal,
            'discount_currency' => $discCurr,
            'was_clamped'       => $wasClamped,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'mark_notifications_read') {
        if (!$ctx) {
            http_response_code(401);
            echo json_encode(['ok' => false]);
            exit;
        }
        $uName = (string) $ctx['user']['username'];
        $roleTag = 'ROLE:' . (string) $ctx['user']['role_code'];
        $pdo->prepare('UPDATE user_notifications SET is_read = 1 WHERE LOWER(recipient_username) = LOWER(?) OR recipient_username = ?')
            ->execute([$uName, $roleTag]);
        echo json_encode(['ok' => true]);
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
                 workflow_stage = 'CANCELLED',
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

        // Retain (or provision if live booking!) the guest's verified account as ACTIVE even when booking is cancelled!
        $guestSync = sc_erp_sync_guest_account_on_voucher_change($pdo, $v, 'CANCELLED_RETAINED');

        sc_erp_audit($pdo, (string) $ctx['user']['username'], (string) $ctx['user']['role_code'], 'CANCEL_VOUCHER', 'VOUCHER', $voNo, ['reason' => $reason]);
        echo json_encode(['ok' => true, 'voucher_no' => $voNo, 'status' => 'CANCELLED', 'commission_status' => 'REVERSED_CANCELLED', 'guest_account' => $guestSync]);
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

    if ($action === 'send_inbox_message') {
        erp_require_perm($ctx, 'bi.view_executive');
        $me = $ctx['user'];

        $msgType = strtoupper(trim((string) ($rawIn['msg_type'] ?? 'ANNOUNCEMENT')));
        if (!in_array($msgType, ['ANNOUNCEMENT', 'CONGRATULATIONS', 'TARGET_ALERT', 'DIRECT', 'POLICY_UPDATE'], true)) {
            $msgType = 'ANNOUNCEMENT';
        }
        $priority = strtoupper(trim((string) ($rawIn['priority'] ?? 'NORMAL')));
        if (!in_array($priority, ['NORMAL', 'IMPORTANT', 'URGENT', 'CELEBRATION'], true)) {
            $priority = ($msgType === 'CONGRATULATIONS') ? 'CELEBRATION' : 'NORMAL';
        }
        $audType = strtoupper(trim((string) ($rawIn['audience_type'] ?? 'ALL')));
        if (!in_array($audType, ['ALL', 'ROLE', 'BRANCH', 'DIRECT'], true)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Invalid audience_type']);
            exit;
        }

        $audTarget = clean_line($rawIn['audience_target'] ?? '', 64);
        if ($audType === 'ALL') {
            $audTarget = '';
        } elseif ($audType === 'ROLE') {
            $audTarget = strtoupper($audTarget);
            $chkR = $pdo->prepare('SELECT role_code FROM roles WHERE UPPER(role_code) = ?');
            $chkR->execute([$audTarget]);
            $foundRole = $chkR->fetchColumn();
            if (!$foundRole) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => 'Unknown target role']);
                exit;
            }
            $audTarget = (string) $foundRole;
        } elseif ($audType === 'BRANCH') {
            $audTarget = strtoupper($audTarget);
            $chkB = $pdo->prepare('SELECT code FROM branches WHERE UPPER(code) = ?');
            $chkB->execute([$audTarget]);
            $foundBranch = $chkB->fetchColumn();
            if (!$foundBranch) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => 'Unknown target branch']);
                exit;
            }
            $audTarget = (string) $foundBranch;
        } elseif ($audType === 'DIRECT') {
            $chkU = $pdo->prepare('SELECT username FROM users WHERE LOWER(username) = LOWER(?) AND is_active = 1');
            $chkU->execute([$audTarget]);
            $foundUser = $chkU->fetchColumn();
            if (!$foundUser) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => 'Recipient user not found']);
                exit;
            }
            $audTarget = (string) $foundUser;
        }

        $spotlight = clean_line($rawIn['spotlight_username'] ?? '', 64);
        if ($spotlight !== '') {
            $spChk = $pdo->prepare('SELECT username FROM users WHERE LOWER(username) = LOWER(?)');
            $spChk->execute([$spotlight]);
            $foundSp = $spChk->fetchColumn();
            $spotlight = $foundSp ? (string) $foundSp : '';
        }

        $titleEn = clean_line($rawIn['title_en'] ?? ($rawIn['title'] ?? ''), 160);
        $titleAr = clean_line($rawIn['title_ar'] ?? '', 160);
        if ($titleEn === '' && $titleAr !== '') {
            $titleEn = $titleAr;
        } elseif ($titleAr === '' && $titleEn !== '') {
            $titleAr = $titleEn;
        }

        $bodyEn = clean($rawIn['body_en'] ?? ($rawIn['body'] ?? ''), 2000);
        $bodyAr = clean($rawIn['body_ar'] ?? '', 2000);
        if ($bodyEn === '' && $bodyAr !== '') {
            $bodyEn = $bodyAr;
        } elseif ($bodyAr === '' && $bodyEn !== '') {
            $bodyAr = $bodyEn;
        }

        if (mb_strlen($titleEn) < 2 || mb_strlen($bodyEn) < 2) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Please enter a message title and body']);
            exit;
        }

        $defaultIcons = [
            'CONGRATULATIONS' => '🎉',
            'TARGET_ALERT'    => '🎯',
            'DIRECT'          => '💌',
            'POLICY_UPDATE'   => '📋',
            'ANNOUNCEMENT'    => '📢',
        ];
        $badgeIcon = clean_line($rawIn['badge_icon'] ?? '', 12);
        if ($badgeIcon === '') {
            $badgeIcon = $defaultIcons[$msgType] ?? '📢';
        }
        $actionTab = clean_line($rawIn['action_tab'] ?? '', 40);
        $isPinned  = !empty($rawIn['is_pinned']) ? 1 : 0;
        $nowIso    = gmdate('Y-m-d\TH:i:s\Z');

        $insMsg = $pdo->prepare(
            'INSERT INTO company_inbox_messages
             (seed_key, created_at, sender_username, sender_name_en, sender_name_ar, sender_role_code,
              sender_title_en, sender_title_ar, sender_avatar_url, msg_type, priority,
              audience_type, audience_target, spotlight_username, badge_icon,
              title_en, title_ar, body_en, body_ar, action_tab, is_pinned)
             VALUES (NULL, ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $insMsg->execute([
            $nowIso,
            (string) $me['username'],
            (string) ($me['display_name_en'] ?: $me['username']),
            (string) ($me['display_name_ar'] ?: $me['display_name_en'] ?: $me['username']),
            (string) $me['role_code'],
            (string) ($me['job_title_en'] ?? ''),
            (string) ($me['job_title_ar'] ?? ''),
            (string) ($me['avatar_url'] ?? ''),
            $msgType,
            $priority,
            $audType,
            $audTarget,
            $spotlight,
            $badgeIcon,
            $titleEn,
            $titleAr,
            $bodyEn,
            $bodyAr,
            $actionTab,
            $isPinned,
        ]);
        $newMsgId = (int) $pdo->lastInsertId();

        // Mark read automatically for the sender
        $pdo->prepare(
            'INSERT INTO company_inbox_reads (message_id, username, read_at, reaction_emoji, reacted_at)
             VALUES (?, ?, ?, "", "")
             ON CONFLICT(message_id, username) DO UPDATE SET read_at = excluded.read_at'
        )->execute([$newMsgId, strtolower((string) $me['username']), $nowIso]);

        // Also push a quick bell notification when messaging/congratulating a specific user directly
        $bellTarget = $audType === 'DIRECT' ? $audTarget : ($spotlight !== '' ? $spotlight : '');
        if ($bellTarget !== '' && function_exists('sc_erp_push_notification')) {
            sc_erp_push_notification(
                $pdo,
                $bellTarget,
                'INBOX_' . $msgType,
                '',
                $badgeIcon . ' ' . $titleEn,
                $badgeIcon . ' ' . $titleAr,
                $bodyEn,
                $bodyAr
            );
        }

        sc_erp_audit($pdo, (string) $me['username'], (string) $me['role_code'], 'SEND_INBOX_MESSAGE', 'INBOX_MSG', (string) $newMsgId, [
            'msg_type'        => $msgType,
            'audience_type'   => $audType,
            'audience_target' => $audTarget,
            'spotlight'       => $spotlight,
            'is_pinned'       => $isPinned,
        ]);

        echo json_encode([
            'ok'         => true,
            'message_id' => $newMsgId,
            'created_at' => $nowIso,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'mark_inbox_read') {
        if (!$ctx) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Authentication required']);
            exit;
        }
        $me     = $ctx['user'];
        $uLower = strtolower((string) $me['username']);
        $nowIso = gmdate('Y-m-d\TH:i:s\Z');

        if (!empty($rawIn['mark_all'])) {
            $feed = sc_erp_fetch_inbox_for_user($pdo, $me, $ctx['perms'], 'unread', 200);
            $upSt = $pdo->prepare(
                'INSERT INTO company_inbox_reads (message_id, username, read_at, reaction_emoji, reacted_at)
                 VALUES (?, ?, ?, "", "")
                 ON CONFLICT(message_id, username) DO UPDATE SET read_at = CASE WHEN company_inbox_reads.read_at = "" THEN excluded.read_at ELSE company_inbox_reads.read_at END'
            );
            foreach ($feed['messages'] as $um) {
                $upSt->execute([(int) $um['id'], $uLower, $nowIso]);
            }
            echo json_encode(['ok' => true, 'unread_count' => 0]);
            exit;
        }

        $mid = (int) ($rawIn['message_id'] ?? 0);
        if ($mid <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Invalid message_id']);
            exit;
        }
        $mSt = $pdo->prepare('SELECT * FROM company_inbox_messages WHERE id = ? AND is_deleted = 0');
        $mSt->execute([$mid]);
        $msg = $mSt->fetch();
        if (!$msg) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Message not found']);
            exit;
        }
        if (!sc_erp_can_user_see_inbox_message($msg, $me, $ctx['perms'])) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Access denied to this message']);
            exit;
        }
        $pdo->prepare(
            'INSERT INTO company_inbox_reads (message_id, username, read_at, reaction_emoji, reacted_at)
             VALUES (?, ?, ?, "", "")
             ON CONFLICT(message_id, username) DO UPDATE SET read_at = CASE WHEN company_inbox_reads.read_at = "" THEN excluded.read_at ELSE company_inbox_reads.read_at END'
        )->execute([$mid, $uLower, $nowIso]);

        $after = sc_erp_fetch_inbox_for_user($pdo, $me, $ctx['perms'], 'all', 1);
        echo json_encode(['ok' => true, 'message_id' => $mid, 'unread_count' => $after['unread_count']]);
        exit;
    }

    if ($action === 'react_inbox_message') {
        if (!$ctx) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Authentication required']);
            exit;
        }
        $me     = $ctx['user'];
        $uLower = strtolower((string) $me['username']);
        $mid    = (int) ($rawIn['message_id'] ?? 0);
        $emoji  = trim((string) ($rawIn['emoji'] ?? ''));
        $allowed = sc_erp_allowed_inbox_reactions();
        if ($mid <= 0 || ($emoji !== '' && !in_array($emoji, $allowed, true))) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Invalid message_id or reaction emoji']);
            exit;
        }
        $mSt = $pdo->prepare('SELECT * FROM company_inbox_messages WHERE id = ? AND is_deleted = 0');
        $mSt->execute([$mid]);
        $msg = $mSt->fetch();
        if (!$msg) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Message not found']);
            exit;
        }
        if (!sc_erp_can_user_see_inbox_message($msg, $me, $ctx['perms'])) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Access denied to this message']);
            exit;
        }

        $curSt = $pdo->prepare('SELECT reaction_emoji, read_at FROM company_inbox_reads WHERE message_id = ? AND username = ?');
        $curSt->execute([$mid, $uLower]);
        $curRow = $curSt->fetch();
        $nextEmoji = ($curRow && (string) $curRow['reaction_emoji'] === $emoji) ? '' : $emoji;
        $nowIso    = gmdate('Y-m-d\TH:i:s\Z');

        $pdo->prepare(
            'INSERT INTO company_inbox_reads (message_id, username, read_at, reaction_emoji, reacted_at)
             VALUES (?, ?, ?, ?, ?)
             ON CONFLICT(message_id, username) DO UPDATE SET
               read_at = CASE WHEN company_inbox_reads.read_at = "" THEN excluded.read_at ELSE company_inbox_reads.read_at END,
               reaction_emoji = excluded.reaction_emoji,
               reacted_at = excluded.reacted_at'
        )->execute([$mid, $uLower, $nowIso, $nextEmoji, $nowIso]);

        $aggSt = $pdo->prepare("SELECT reaction_emoji, COUNT(*) AS cnt FROM company_inbox_reads WHERE message_id = ? AND reaction_emoji != '' GROUP BY reaction_emoji");
        $aggSt->execute([$mid]);
        $summary = [];
        $totReact = 0;
        foreach ($aggSt->fetchAll() as $ar) {
            $c = (int) $ar['cnt'];
            $summary[(string) $ar['reaction_emoji']] = $c;
            $totReact += $c;
        }
        echo json_encode([
            'ok'                => true,
            'message_id'        => $mid,
            'my_reaction'       => $nextEmoji,
            'reactions_summary' => $summary,
            'reactions_total'   => $totReact,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'toggle_pin_inbox_message') {
        erp_require_perm($ctx, 'bi.view_executive');
        $mid = (int) ($rawIn['message_id'] ?? 0);
        if ($mid <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Invalid message_id']);
            exit;
        }
        $mSt = $pdo->prepare('SELECT id, is_pinned FROM company_inbox_messages WHERE id = ? AND is_deleted = 0');
        $mSt->execute([$mid]);
        $msg = $mSt->fetch();
        if (!$msg) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Message not found']);
            exit;
        }
        $nextPin = array_key_exists('is_pinned', $rawIn) ? (!empty($rawIn['is_pinned']) ? 1 : 0) : (1 - (int) $msg['is_pinned']);
        $pdo->prepare('UPDATE company_inbox_messages SET is_pinned = ? WHERE id = ?')->execute([$nextPin, $mid]);
        sc_erp_audit($pdo, (string) $ctx['user']['username'], (string) $ctx['user']['role_code'], 'PIN_INBOX_MESSAGE', 'INBOX_MSG', (string) $mid, ['is_pinned' => $nextPin]);
        echo json_encode(['ok' => true, 'message_id' => $mid, 'is_pinned' => $nextPin === 1]);
        exit;
    }

    if ($action === 'delete_inbox_message') {
        erp_require_perm($ctx, 'bi.view_executive');
        $mid = (int) ($rawIn['message_id'] ?? 0);
        if ($mid <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Invalid message_id']);
            exit;
        }
        $mSt = $pdo->prepare('SELECT id FROM company_inbox_messages WHERE id = ?');
        $mSt->execute([$mid]);
        if (!$mSt->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Message not found']);
            exit;
        }
        $nowIso = gmdate('Y-m-d\TH:i:s\Z');
        $pdo->prepare('UPDATE company_inbox_messages SET is_deleted = 1, is_pinned = 0, deleted_by = ?, deleted_at = ? WHERE id = ?')
            ->execute([(string) $ctx['user']['username'], $nowIso, $mid]);
        sc_erp_audit($pdo, (string) $ctx['user']['username'], (string) $ctx['user']['role_code'], 'DELETE_INBOX_MESSAGE', 'INBOX_MSG', (string) $mid);
        echo json_encode(['ok' => true, 'message_id' => $mid]);
        exit;
    }

    if ($action === 'guest_create_booking') {
        erp_require_perm($ctx, 'guest.book_future_trips');
        $meUser  = $ctx['user'];
        $gUser   = (string) $meUser['username'];
        $gSt     = $pdo->prepare('SELECT * FROM guest_customer_accounts WHERE LOWER(username) = LOWER(?) LIMIT 1');
        $gSt->execute([$gUser]);
        $gAcct   = $gSt->fetch() ?: [];

        $tripCode = clean_line($rawIn['trip_code'] ?? ($rawIn['product_code'] ?? ''), 64);
        if ($tripCode === '') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Please select a trip to book']);
            exit;
        }
        $tripDt   = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($rawIn['trip_date'] ?? ''))
            ? (string) $rawIn['trip_date']
            : gmdate('Y-m-d', time() + 86400);
        [$wk, $mo, $qr, $yr] = sc_erp_period_parts($tripDt);

        $adults   = max(1, min(50, (int) ($rawIn['adults'] ?? 2)));
        $children = max(0, min(30, (int) ($rawIn['children'] ?? 0)));
        $infants  = max(0, min(15, (int) ($rawIn['infants'] ?? 0)));
        $pax      = $adults + $children + $infants;

        $guestName = clean_line($rawIn['guest_name'] ?? ($gAcct['guest_name'] ?? $meUser['display_name_en']), 120);
        $guestPh   = clean_line($rawIn['guest_phone'] ?? ($gAcct['guest_phone'] ?? $meUser['phone'] ?? ''), 48);
        $rawEm     = strtolower(clean_line($rawIn['guest_email'] ?? ($gAcct['guest_email'] ?? $meUser['email'] ?? ''), 160));
        $guestEm   = ($rawEm !== '' && filter_var($rawEm, FILTER_VALIDATE_EMAIL)) ? $rawEm : '';
        $hotel     = clean_line($rawIn['hotel_name'] ?? ($gAcct['hotel_name'] ?? 'Sharm Hotel'), 120);
        $room      = clean_line($rawIn['room_no'] ?? ($gAcct['room_no'] ?? ''), 32);
        $nat       = strtoupper(clean_line($rawIn['nationality'] ?? ($gAcct['nationality'] ?? 'VIP'), 24));
        $pickup    = clean_line($rawIn['pickup_time'] ?? '08:00', 24);
        $notes     = clean_line($rawIn['notes'] ?? 'Booked via Verified Club Guest App', 240);

        $fx        = sc_erp_fx_rates($pdo);
        $tripMeta  = sc_erp_lookup_unified_trip($pdo, $tripCode);
        $fin       = sc_erp_compute_trip_financials($tripMeta, $adults, $children, $infants, $fx);

        $voNo      = 'VIP-' . random_int(10000, 99999);
        $qrTok     = 'QR-' . strtoupper(bin2hex(random_bytes(6)));
        $tripName  = $tripMeta ? (string) $tripMeta['unified_name_en'] : $tripCode;
        $resCode   = $tripMeta ? (string) $tripMeta['trip_code'] : $tripCode;
        $grossEgp  = (float) $fin['gross_total_egp'];
        $totalEur  = (float) $fin['catalog_total_eur'];
        $supCost   = (float) $fin['supplier_cost_egp'];
        $saleMode  = (string) $fin['sale_mode'];

        // Preserve lifetime referral attribution to the original Seller who onboarded this real customer!
        $refSellerTok = trim((string) ($gAcct['referred_by_seller'] ?? ''));
        $refSeller    = $refSellerTok !== '' ? sc_erp_resolve_seller($pdo, $refSellerTok) : null;
        $sUser        = $refSeller ? (string) $refSeller['username'] : '';
        $sRep         = $refSeller ? (string) $refSeller['rep_scope'] : 'VIP_APP';
        $branch       = $refSeller ? (string) ($refSeller['branch_scope'] ?: 'CYRENE') : 'CYRENE';
        $sellerType   = $refSeller ? 'HOTEL_SELLER' : 'WEBSITE_DIRECT';

        $commRes = sc_erp_record_seller_commission($pdo, [
            'seller_token'     => $sUser,
            'sale_mode'        => $saleMode,
            'tx_date'          => $tripDt,
            'voucher_no'       => $voNo,
            'guest_name'       => $guestName,
            'product_name'     => $tripName,
            'pax'              => $pax,
            'gross_amount_egp' => $grossEgp,
            'supplier_cost_egp'=> $saleMode === 'OUT_SIDE' ? $supCost : 0.0,
            'status'           => 'PENDING_EXECUTION',
            'source_channel'   => 'LIVE_MOBILE_APP',
            'branch_code'      => $branch,
            'notes'            => 'Repeat booking from Verified Guest App (' . $gUser . ')',
        ]);

        $ins = $pdo->prepare(
            'INSERT INTO reservations_fact
             (voucher_no, qr_token, trip_date, period_week, period_month, period_quarter, period_year,
              branch_code, channel, hotel_name, pickup_zone, room_no, nationality, pickup_time,
              trip_name, unified_trip_code, product_category, adults, children, infants, total_pax,
              adult_unit_eur, child_unit_eur, infant_unit_eur,
              booked_adults, booked_children, booked_infants, booked_total_egp,
              list_total_egp, discount_type, discount_value, discount_currency, discount_egp, discount_reason,
              snork_pax, intro1_pax, intro2_pax, dives_pax, divers_names, guest_phone, guest_email, guest_account_username,
              sales_rep, executor, ap_supplier_code, client_agency,
              rest_egp, rest_usd, rest_eur, rest_gbp, rest_egp_equiv,
              desk_paid_egp, deposit_egp_equiv, actual_paid_total_egp, supplier_cost_egp,
              estimated_revenue_egp, estimated_cogs_egp, estimated_profit_egp, vessel_code,
              boarding_status, payment_status, booking_state, workflow_stage, source_file,
              seller_type, seller_username, seller_commission_egp, commission_type, commission_status)
             VALUES (?,?,?,?,?,?,?, ?,?,?,?,?,?,?, ?,?,?,?,?,?,?, ?,?,?, ?,?,?,?, ?,?,?,?,?,?, 0,0,0,0,?,?,?,?, ?,?,?,?, 0,0,?,0,?, 0,0,0,?, ?,?,?, ?,?,?,?,?, ?, ?,?,?,?,?)'
        );
        $ins->execute([
            $voNo, $qrTok, $tripDt, $wk, $mo, $qr, $yr,
            $branch, 'VIP_GUEST_APP', $hotel !== '' ? $hotel : 'Sharm Hotel', '', $room, $nat, $pickup,
            $tripName, $resCode, 'VIP_REPEAT_BOOKING', $adults, $children, $infants, $pax,
            $fin['adult_unit_eur'], $fin['child_unit_eur'], $fin['infant_unit_eur'],
            $adults, $children, $infants, $grossEgp,
            $grossEgp, 'NONE', 0.0, 'EUR', 0.0, '',
            $guestName . ($guestPh !== '' ? " ({$guestPh})" : ''), $guestPh, $guestEm, $gUser,
            $sRep, $fin['supplier_name'], $fin['supplier_code'] !== 'SEA_COLLEGE' ? $fin['supplier_code'] : '', 'DIRECT',
            $totalEur, $grossEgp,
            $supCost,
            $grossEgp, $supCost, round($grossEgp - $supCost, 2),
            '', 'BOOKED', 'OPEN_REST', 'EXECUTED', 'SELLER_CONFIRMED_PENDING_OPS', 'LIVE_MOBILE_APP',
            $sellerType, $sUser,
            $commRes['commission_egp'], $commRes['commission_type'], $commRes['commission_status'],
        ]);
        $newId = (int) $pdo->lastInsertId();

        sc_erp_provision_verified_guest_account($pdo, [
            'voucher_no'      => $voNo,
            'guest_name'      => $guestName,
            'guest_phone'     => $guestPh,
            'guest_email'     => $guestEm,
            'nationality'     => $nat,
            'hotel_name'      => $hotel,
            'room_no'         => $room,
            'seller_username' => $sUser,
            'trip_name'       => $tripName,
            'trip_date'       => $tripDt,
            'booked_egp'      => $grossEgp,
            'paid_egp'        => 0.0,
            'booking_status'  => 'CONFIRMED_UNPAID',
        ]);

        sc_erp_push_notification(
            $pdo,
            'ROLE:OPERATIONS_MANAGER',
            'VIP_GUEST_BOOKING',
            $voNo,
            "🌟 Repeat VIP Guest App Booking ({$voNo})",
            "🌟 حجز جديد من عميل فعلي عبر تطبيق سي كوليدج ({$voNo})",
            "{$guestName} ({$guestPh}) booked {$tripName} on {$tripDt} ({$adults} Adl, {$children} Ch, {$infants} Inf — €{$totalEur}).",
            "حجز العميل المعتمد {$guestName} ({$guestPh}) رحلة {$tripName} بتاريخ {$tripDt} ({$adults} بالغ، {$children} طفل، {$infants} رضيع — €{$totalEur})."
        );
        if ($sUser !== '') {
            sc_erp_push_notification(
                $pdo,
                $sUser,
                'VIP_GUEST_BOOKING',
                $voNo,
                "🎉 Your Returning Guest Booked Another Trip ({$voNo})!",
                "🎉 عميلك السابق حجز رحلة جديدة عبر التطبيق باسمك ({$voNo})!",
                "{$guestName} booked {$tripName} on {$tripDt} (€{$totalEur}) — automatically credited to your account!",
                "قام عميلك {$guestName} بحجز رحلة {$tripName} ليوم {$tripDt} (€{$totalEur}) — مسجلة تلقائيًا باسمك!"
            );
        }

        sc_erp_audit($pdo, $gUser, 'GUEST_CUSTOMER', 'GUEST_CREATE_BOOKING', 'VOUCHER', $voNo, [
            'trip' => $resCode,
            'pax'  => $pax,
            'eur'  => $totalEur,
        ]);

        echo json_encode([
            'ok'             => true,
            'voucher_id'     => $newId,
            'voucher_no'     => $voNo,
            'qr_token'       => $qrTok,
            'trip_name'      => $tripName,
            'trip_date'      => $tripDt,
            'pickup_time'    => $pickup,
            'adults'         => $adults,
            'children'       => $children,
            'infants'        => $infants,
            'total_eur'      => $totalEur,
            'total_egp'      => $grossEgp,
            'whatsapp_phone' => WHATSAPP_NUMBER,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'send_guest_login_email') {
        erp_require_perm($ctx, 'sales.create_voucher', 'sales.view_own_sales', 'crm.manage_leads_ads', 'ops.assign_boat_driver', 'bi.view_executive');
        $me       = $ctx['user'];
        $gUserIn  = strtolower(clean_line($rawIn['guest_username'] ?? '', 64));
        $voNoIn   = clean_line($rawIn['voucher_no'] ?? '', 40);
        $rawEmail = strtolower(clean_line($rawIn['guest_email'] ?? ($rawIn['email'] ?? ''), 160));
        if ($rawEmail === '' || !filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Please enter a valid email address']);
            exit;
        }

        $gRow = null;
        $vRow = null;
        if ($gUserIn !== '') {
            $st = $pdo->prepare('SELECT * FROM guest_customer_accounts WHERE LOWER(username) = ? LIMIT 1');
            $st->execute([$gUserIn]);
            $gRow = $st->fetch() ?: null;
        }
        if ($voNoIn !== '') {
            $vst = $pdo->prepare('SELECT * FROM reservations_fact WHERE voucher_no = ? ORDER BY id DESC LIMIT 1');
            $vst->execute([$voNoIn]);
            $vRow = $vst->fetch() ?: null;
            if (!$gRow && $vRow && !empty($vRow['guest_account_username'])) {
                $st2 = $pdo->prepare('SELECT * FROM guest_customer_accounts WHERE LOWER(username) = LOWER(?) LIMIT 1');
                $st2->execute([(string) $vRow['guest_account_username']]);
                $gRow = $st2->fetch() ?: null;
            }
        }
        if (!$gRow && !$vRow) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Guest account or booking voucher not found']);
            exit;
        }

        // Horizontal RBAC check: Sellers may only email/update guests attributed to them!
        if ($me['role_code'] === 'SALES_REP') {
            $myUser = strtolower((string) $me['username']);
            $ownerSeller = strtolower((string) ($gRow['referred_by_seller'] ?? ($vRow['seller_username'] ?? '')));
            if ($ownerSeller !== $myUser) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'You may only send login emails to your own guests']);
                exit;
            }
        }

        $res = sc_erp_provision_verified_guest_account($pdo, [
            'voucher_no'         => $voNoIn !== '' ? $voNoIn : (string) ($gRow['latest_voucher_no'] ?? ''),
            'guest_name'         => (string) ($gRow['guest_name'] ?? ($vRow['divers_names'] ?? 'Club Guest')),
            'guest_phone'        => (string) ($gRow['guest_phone'] ?? ($vRow['guest_phone'] ?? '')),
            'guest_email'        => $rawEmail,
            'nationality'        => (string) ($gRow['nationality'] ?? ($vRow['nationality'] ?? '')),
            'lang'               => (string) ($gRow['preferred_lang'] ?? 'en'),
            'hotel_name'         => (string) ($gRow['hotel_name'] ?? ($vRow['hotel_name'] ?? '')),
            'room_no'            => (string) ($gRow['room_no'] ?? ($vRow['room_no'] ?? '')),
            'seller_username'    => (string) ($gRow['referred_by_seller'] ?? ($vRow['seller_username'] ?? '')),
            'trip_name'          => (string) ($gRow['latest_trip_name'] ?? ($vRow['trip_name'] ?? '')),
            'trip_date'          => (string) ($gRow['latest_trip_date'] ?? ($vRow['trip_date'] ?? '')),
            'booked_egp'         => (float) ($vRow['booked_total_egp'] ?? ($vRow['estimated_revenue_egp'] ?? 0.0)),
            'paid_egp'           => (float) ($vRow['actual_paid_total_egp'] ?? ($vRow['deposit_egp_equiv'] ?? 0.0)),
            'booking_status'     => (string) ($gRow['latest_booking_status'] ?? 'CONFIRMED_UNPAID'),
            'force_resend_email' => true,
        ]);

        // If `$gRow` existed under `$gUserIn` and `voucher_no` was empty, ensure that exact `guest_customer_accounts` row was updated/emailed
        if ($gRow && strtolower((string) ($res['guest_username'] ?? '')) !== strtolower((string) $gRow['username'])) {
            $exactUser = (string) $gRow['username'];
            $nowIso = gmdate('Y-m-d\TH:i:s\Z');
            $newTempPass = sc_erp_generate_guest_password();
            $pdo->prepare('UPDATE users SET email = ?, password_hash = ?, is_active = 1, updated_at = ? WHERE username = ?')
                ->execute([$rawEmail, password_hash($newTempPass, PASSWORD_BCRYPT), $nowIso, $exactUser]);
            $pdo->prepare('UPDATE guest_customer_accounts SET guest_email = ?, updated_at = ? WHERE username = ?')
                ->execute([$rawEmail, $nowIso, $exactUser]);
            $sentOk = sc_erp_send_guest_credentials_email(
                $pdo,
                ['username' => $exactUser, 'guest_name' => (string) $gRow['guest_name'], 'guest_email' => $rawEmail],
                $newTempPass,
                (string) ($gRow['latest_voucher_no'] ?? ''),
                (string) ($gRow['latest_trip_name'] ?? ''),
                (string) ($gRow['latest_trip_date'] ?? '')
            );
            $res['guest_username']   = $exactUser;
            $res['guest_email']      = $rawEmail;
            $res['email_sent']       = $sentOk;
            $res['initial_password'] = $newTempPass;
        }

        sc_erp_audit($pdo, (string) $me['username'], (string) $me['role_code'], 'SEND_GUEST_LOGIN_EMAIL', 'GUEST_ACCOUNT', (string) ($res['guest_username'] ?? $gUserIn), [
            'email'      => $rawEmail,
            'email_sent' => !empty($res['email_sent']),
        ]);

        echo json_encode([
            'ok'             => true,
            'guest_username' => $res['guest_username'] ?? $gUserIn,
            'guest_email'    => $rawEmail,
            'email_sent'     => !empty($res['email_sent']),
            'guest_account'  => $res,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /* ---------------- SAVE SUPPLIER / VENDOR (`save_supplier` — Accounting & Owners) ---------------- */
    if ($action === 'save_supplier') {
        erp_require_perm($ctx, 'gl.view_all_ledgers', 'bi.view_executive');
        $me = $ctx['user'];
        $rawCode = strtoupper(trim((string) ($rawIn['supplier_code'] ?? '')));
        $nameEn  = clean_line($rawIn['name_en'] ?? '', 140);
        $nameAr  = clean_line($rawIn['name_ar'] ?? $nameEn, 140);
        if ($nameEn === '') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Supplier English name is required']);
            exit;
        }
        if ($rawCode === '') {
            $slug = strtoupper((string) preg_replace('/[^a-zA-Z0-9]+/', '_', $nameEn));
            $slug = trim($slug, '_');
            $rawCode = 'AP_' . ($slug !== '' ? substr($slug, 0, 18) : ('VENDOR_' . random_int(100, 999)));
        }
        $rawCode = (string) preg_replace('/[^A-Z0-9_-]/', '_', $rawCode);

        $exSt = $pdo->prepare('SELECT * FROM suppliers_directory WHERE supplier_code = ? LIMIT 1');
        $exSt->execute([$rawCode]);
        $prev = $exSt->fetch() ?: null;

        $catCode  = strtoupper(clean_line($rawIn['category_code'] ?? ($prev['category_code'] ?? 'EXCURSIONS'), 40));
        $contact  = clean_line($rawIn['contact_person'] ?? ($prev['contact_person'] ?? ''), 100);
        $phone    = clean_line($rawIn['phone'] ?? ($prev['phone'] ?? ''), 48);
        $whatsapp = clean_line($rawIn['whatsapp'] ?? ($phone ?: ($prev['whatsapp'] ?? '')), 48);
        $curr     = strtoupper(clean_line($rawIn['settlement_currency'] ?? ($prev['settlement_currency'] ?? 'EGP'), 8));
        if (!in_array($curr, ['EGP', 'USD', 'EUR', 'GBP'], true)) {
            $curr = 'EGP';
        }
        $terms    = clean_line($rawIn['payment_terms'] ?? ($prev['payment_terms'] ?? 'Monthly Statement'), 120);
        $sheetTab = clean_line($rawIn['sheet_tab_name'] ?? ($prev['sheet_tab_name'] ?? ''), 40);
        $openBal  = array_key_exists('opening_balance_egp', $rawIn) ? round((float) $rawIn['opening_balance_egp'], 2) : (float) ($prev['opening_balance_egp'] ?? 0.0);
        $currBal  = array_key_exists('current_balance_egp', $rawIn) ? round((float) $rawIn['current_balance_egp'], 2) : (float) ($prev['current_balance_egp'] ?? 0.0);
        $notes    = clean_line($rawIn['notes'] ?? ($prev['notes'] ?? ''), 400);
        $isActive = array_key_exists('is_active', $rawIn) ? (!empty($rawIn['is_active']) ? 1 : 0) : (int) ($prev['is_active'] ?? 1);
        $now      = gmdate('Y-m-d\TH:i:s\Z');

        $pdo->prepare(
            'INSERT OR REPLACE INTO suppliers_directory
             (supplier_code, name_en, name_ar, category_code, contact_person, phone, whatsapp,
              settlement_currency, payment_terms, sheet_tab_name, opening_balance_egp, current_balance_egp,
              notes, is_active, updated_by, updated_at)
             VALUES (?,?,?,?,?,?,?, ?,?,?,?,?, ?,?,?,?)'
        )->execute([
            $rawCode, $nameEn, $nameAr, $catCode, $contact, $phone, $whatsapp,
            $curr, $terms, $sheetTab, $openBal, $currBal,
            $notes, $isActive, (string) $me['username'], $now,
        ]);

        // Also mirror into counterparties if AP_ code so Accounting GL stays unified
        if (str_starts_with($rawCode, 'AP_')) {
            try {
                $cpChk = $pdo->prepare('SELECT id FROM counterparties WHERE code = ? LIMIT 1');
                $cpChk->execute([$rawCode]);
                if ($cpChk->fetchColumn()) {
                    $pdo->prepare('UPDATE counterparties SET name_en = ?, name_ar = ?, opening_balance_egp = ?, current_balance_egp = ? WHERE code = ?')
                        ->execute([$nameEn, $nameAr, $openBal, $currBal, $rawCode]);
                } else {
                    $pdo->prepare('INSERT INTO counterparties (code, name_en, name_ar, party_type, opening_balance_egp, total_debit_egp, total_credit_egp, current_balance_egp, source_workbook) VALUES (?,?,?,"SUPPLIER_AP",?,0,0,?,"LIVE_SUPPLIERS_HUB")')
                        ->execute([$rawCode, $nameEn, $nameAr, $openBal, $currBal]);
                }
            } catch (Throwable $e) {}
        }

        sc_erp_audit($pdo, (string) $me['username'], (string) $me['role_code'], $prev ? 'UPDATE_SUPPLIER' : 'CREATE_SUPPLIER', 'SUPPLIER', $rawCode, [
            'name_en'             => $nameEn,
            'category_code'       => $catCode,
            'current_balance_egp' => $currBal,
        ]);

        $freshSt = $pdo->prepare('SELECT * FROM suppliers_directory WHERE supplier_code = ?');
        $freshSt->execute([$rawCode]);
        echo json_encode(['ok' => true, 'supplier' => $freshSt->fetch()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /* ---------------- SAVE / UPDATE TRIP OR SAFARI VARIANT COST (`save_trip_cost` — Accounting & Owners) ---------------- */
    if ($action === 'save_trip_cost') {
        erp_require_perm($ctx, 'gl.view_all_ledgers', 'bi.view_executive');
        $me = $ctx['user'];
        $nameEnChk = trim((string) ($rawIn['name_en'] ?? ''));
        $codeChk   = trim((string) ($rawIn['item_code'] ?? ''));
        if ($nameEnChk === '' && $codeChk === '') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Provide item_code or name_en to save trip/variant cost']);
            exit;
        }
        $savedItem = sc_erp_save_trip_or_variant_cost($pdo, $rawIn, $me);
        sc_erp_audit($pdo, (string) $me['username'], (string) $me['role_code'], 'SAVE_TRIP_COST', 'TRIP_COST', (string) ($savedItem['item_code'] ?? ''), [
            'supplier_code'   => $savedItem['supplier_code'] ?? '',
            'adult_cost_egp'  => $savedItem['adult_cost_egp'] ?? 0,
            'child_cost_egp'  => $savedItem['child_cost_egp'] ?? 0,
            'infant_cost_egp' => $savedItem['infant_cost_egp'] ?? 0,
            'unit_cost_egp'   => $savedItem['unit_cost_egp'] ?? 0,
        ]);
        echo json_encode(['ok' => true, 'item' => $savedItem], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /* ---------------- AWARD STAFF BONUS OR RECORD / REQUEST ALLOWANCE (`save_staff_bonus_allowance` — v3.5) ---------------- */
    if ($action === 'save_staff_bonus_allowance') {
        if (!$ctx) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Authentication required']);
            exit;
        }
        $me = $ctx['user'];
        if (($me['role_code'] ?? '') === 'GUEST_CUSTOMER') {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Staff access required']);
            exit;
        }

        $canManageAll = in_array('bi.view_executive', $ctx['perms'], true)
            || in_array('gl.view_all_ledgers', $ctx['perms'], true)
            || in_array('hr.manage_payroll', $ctx['perms'], true)
            || in_array('safe.record_tx', $ctx['perms'], true);
        $canAwardBonus = in_array('bi.view_executive', $ctx['perms'], true)
            || in_array('gl.view_all_ledgers', $ctx['perms'], true)
            || in_array('hr.manage_payroll', $ctx['perms'], true);

        $entryType = strtoupper(trim((string) ($rawIn['entry_type'] ?? 'BONUS')));
        if (!in_array($entryType, ['BONUS', 'ALLOWANCE', 'ADVANCE'], true)) {
            $entryType = 'ALLOWANCE';
        }
        if ($entryType === 'BONUS' && !$canAwardBonus) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Only Owners, Admins, or Accounting/HR may award Staff Bonuses']);
            exit;
        }

        $targetEmpUser = $canManageAll && !empty($rawIn['emp_username'])
            ? strtolower(trim((string) $rawIn['emp_username']))
            : strtolower((string) $me['username']);

        $empSt = $pdo->prepare("SELECT * FROM users WHERE LOWER(username) = ? AND role_code != 'GUEST_CUSTOMER' LIMIT 1");
        $empSt->execute([$targetEmpUser]);
        $empRow = $empSt->fetch();
        if (!$empRow) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Target staff member not found']);
            exit;
        }

        $fx = sc_erp_fx_rates($pdo);
        $curr = strtoupper(trim((string) ($rawIn['currency'] ?? 'EGP')));
        if (!isset($fx[$curr])) {
            $curr = 'EGP';
        }
        $amtOrig = max(0.0, (float) ($rawIn['amount_orig'] ?? ($rawIn['amount_egp'] ?? ($rawIn['amount'] ?? 0.0))));
        $amtEgp  = $curr === 'EGP' && array_key_exists('amount_egp', $rawIn)
            ? round(max(0.0, (float) $rawIn['amount_egp']), 2)
            : round($amtOrig * ($fx[$curr] ?? 1.0), 2);
        if ($amtEgp <= 0.0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Bonus / Allowance amount must be greater than zero']);
            exit;
        }

        $txDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($rawIn['tx_date'] ?? '')) ? (string) $rawIn['tx_date'] : gmdate('Y-m-d');
        $reqMo  = trim((string) ($rawIn['period_month'] ?? ''));
        $periodMonth = preg_match('/^\d{4}-\d{2}$/', $reqMo) ? $reqMo : substr($txDate, 0, 7);
        $category = strtoupper(clean_line($rawIn['category'] ?? ($entryType === 'BONUS' ? 'PERFORMANCE_BONUS' : ($entryType === 'ADVANCE' ? 'SALARY_ADVANCE' : 'TRANSPORT_ALLOWANCE')), 48));
        $safeCode = strtoupper(clean_line($rawIn['safe_code'] ?? 'NONE_PAYROLL', 32));
        if (!in_array($safeCode, ['NONE_PAYROLL', 'SAFE_CYRENE', 'SAFE_JETTY'], true)) {
            $safeCode = 'NONE_PAYROLL';
        }

        $reasonEn = clean_line($rawIn['reason_en'] ?? ($rawIn['reason'] ?? ($entryType === 'BONUS' ? 'Staff Performance Bonus' : 'Staff Operational Allowance')), 240);
        $reasonAr = clean_line($rawIn['reason_ar'] ?? $reasonEn, 240);
        $nowIso   = gmdate('Y-m-d\TH:i:s\Z');

        // Determine initial workflow status
        if ($canManageAll) {
            $reqStatus = strtoupper(trim((string) ($rawIn['status'] ?? 'APPROVED_PAID')));
            $status = in_array($reqStatus, ['APPROVED_PAID', 'POSTED_TO_PAYROLL', 'REQUESTED'], true) ? $reqStatus : 'APPROVED_PAID';
        } else {
            $status = 'REQUESTED';
            $safeCode = 'NONE_PAYROLL';
        }

        $approvedBy = in_array($status, ['APPROVED_PAID', 'POSTED_TO_PAYROLL'], true) ? (string) $me['username'] : '';
        $decidedAt  = $approvedBy !== '' ? $nowIso : '';
        $branchCode = (string) (($empRow['branch_scope'] ?? '') !== '' && $empRow['branch_scope'] !== 'ALL' ? $empRow['branch_scope'] : 'CYRENE');

        $pdo->prepare(
            'INSERT INTO staff_bonuses_allowances
             (seed_key, created_at, tx_date, period_month, emp_username, emp_name_en, emp_name_ar,
              role_code, branch_code, entry_type, category, amount_egp, currency, amount_orig,
              safe_code, status, requested_by, approved_by, decided_at, reason_en, reason_ar)
             VALUES (NULL, ?,?,?,?,?,?, ?,?,?,?,?,?,?, ?,?,?,?,?,?,?)'
        )->execute([
            $nowIso, $txDate, $periodMonth,
            (string) $empRow['username'],
            (string) $empRow['display_name_en'],
            (string) ($empRow['display_name_ar'] ?: $empRow['display_name_en']),
            (string) $empRow['role_code'],
            $branchCode,
            $entryType,
            $category,
            $amtEgp,
            $curr,
            $amtOrig > 0 ? $amtOrig : $amtEgp,
            $safeCode,
            $status,
            (string) $me['username'],
            $approvedBy,
            $decidedAt,
            $reasonEn,
            $reasonAr,
        ]);
        $recordId = (int) $pdo->lastInsertId();

        // Optional Safe Expense booking when paid immediately out of SAFE_CYRENE or SAFE_JETTY
        if ($status === 'APPROVED_PAID' && in_array($safeCode, ['SAFE_CYRENE', 'SAFE_JETTY'], true)) {
            [$wkE, $moE, $qrE, $yrE] = sc_erp_period_parts($txDate);
            $vSeq = ($entryType === 'BONUS' ? 'BON-' : 'ALW-') . $recordId;
            $cc   = $safeCode === 'SAFE_JETTY' ? 'JETTY' : 'CYRENE';
            $pdo->prepare(
                'INSERT INTO expense_vouchers_fact
                 (exp_date, period_week, period_month, period_quarter, period_year, voucher_seq, description, cost_center, amount_le, amount_usd, amount_eur, amount_gbp, total_egp, source_file)
                 VALUES (?,?,?,?,?,?,?,?, ?,0,0,0, ?, "LIVE_MOBILE_SAFE")'
            )->execute([
                $txDate, $wkE, $moE, $qrE, $yrE, $vSeq,
                "Staff {$entryType}: {$empRow['display_name_en']} — {$reasonEn}",
                $cc, $amtEgp, $amtEgp,
            ]);
        }

        // Push instant notification!
        if ($status === 'REQUESTED') {
            sc_erp_push_notification(
                $pdo,
                'ROLE:CHIEF_ACCOUNTANT',
                'ALLOWANCE_STATUS',
                'SBA-' . $recordId,
                "📝 Staff {$entryType} Request ({$empRow['display_name_en']}: " . number_format($amtEgp) . " EGP)",
                "📝 طلب بدل / سلفة جديد ({$empRow['display_name_ar']}: " . number_format($amtEgp) . " ج.م)",
                $reasonEn,
                $reasonAr
            );
        } else {
            $ico = $entryType === 'BONUS' ? '🎁🎉' : '✅💵';
            sc_erp_push_notification(
                $pdo,
                (string) $empRow['username'],
                $entryType === 'BONUS' ? 'STAFF_BONUS' : 'ALLOWANCE_STATUS',
                'SBA-' . $recordId,
                $entryType === 'BONUS'
                    ? "{$ico} Congratulations! Staff Bonus Awarded: " . number_format($amtEgp) . " EGP"
                    : "{$ico} Staff {$entryType} Approved: " . number_format($amtEgp) . " EGP",
                $entryType === 'BONUS'
                    ? "{$ico} مبروك! تم صرف مكافأة تشجيعية لك بقيمة " . number_format($amtEgp) . " ج.م"
                    : "{$ico} تمت الموافقة على {$entryType} بقيمة " . number_format($amtEgp) . " ج.م",
                $reasonEn,
                $reasonAr
            );

            // Optional celebratory Company Inbox post for Staff Bonuses!
            if ($entryType === 'BONUS' && !empty($rawIn['broadcast_inbox']) && in_array('bi.view_executive', $ctx['perms'], true)) {
                $pdo->prepare(
                    'INSERT INTO company_inbox_messages
                     (seed_key, created_at, sender_username, sender_name_en, sender_name_ar, sender_role_code,
                      sender_title_en, sender_title_ar, sender_avatar_url, msg_type, priority,
                      audience_type, audience_target, spotlight_username, badge_icon,
                      title_en, title_ar, body_en, body_ar, action_tab, is_pinned)
                     VALUES (NULL, ?,?,?,?,?, ?,?,?, "CONGRATULATION", "HIGH", "ALL", "", ?, "🏆", ?,?,?,?, "staff_rewards", 0)'
                )->execute([
                    $nowIso,
                    (string) $me['username'],
                    (string) $me['display_name_en'],
                    (string) ($me['display_name_ar'] ?: $me['display_name_en']),
                    (string) $me['role_code'],
                    (string) ($me['job_title_en'] ?? 'Management'),
                    (string) ($me['job_title_ar'] ?? 'الإدارة'),
                    (string) ($me['avatar_url'] ?? ''),
                    (string) $empRow['username'],
                    "🏆 Well-Deserved Bonus for {$empRow['display_name_en']}!",
                    "🏆 تحية ومكافأة مستحقة للزميل {$empRow['display_name_ar']}!",
                    "Join us in congratulating {$empRow['display_name_en']}! Reason: {$reasonEn}. Great job!",
                    "نبارك للزميل {$empRow['display_name_ar']} تقديراً لجهوده المتميزة ({$reasonAr}). شكراً لتفانيكم!",
                ]);
            }
        }

        sc_erp_audit($pdo, (string) $me['username'], (string) $me['role_code'], 'SAVE_STAFF_BONUS_ALLOWANCE', 'STAFF_REWARDS', 'SBA-' . $recordId, [
            'emp_username' => $empRow['username'],
            'entry_type'   => $entryType,
            'amount_egp'   => $amtEgp,
            'status'       => $status,
        ]);

        echo json_encode([
            'ok'         => true,
            'id'         => $recordId,
            'entry_type' => $entryType,
            'status'     => $status,
            'amount_egp' => $amtEgp,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /* ---------------- APPROVE / REJECT STAFF ALLOWANCE OR ADVANCE (`decide_staff_bonus_allowance` — v3.5) ---------------- */
    if ($action === 'decide_staff_bonus_allowance') {
        erp_require_perm($ctx, 'gl.view_all_ledgers', 'bi.view_executive', 'hr.manage_payroll', 'safe.record_tx');
        $me    = $ctx['user'];
        $id    = (int) ($rawIn['id'] ?? 0);
        $rawDec= strtoupper(trim((string) ($rawIn['decision'] ?? 'APPROVED_PAID')));
        $decision = match ($rawDec) {
            'APPROVED', 'PAID', 'APPROVED_PAID' => 'APPROVED_PAID',
            'PAYROLL', 'POSTED_TO_PAYROLL'      => 'POSTED_TO_PAYROLL',
            'REJECTED', 'DECLINED'              => 'REJECTED',
            default                             => 'APPROVED_PAID',
        };
        $safeCode = strtoupper(clean_line($rawIn['safe_code'] ?? '', 32));

        $rSt = $pdo->prepare('SELECT * FROM staff_bonuses_allowances WHERE id = ? LIMIT 1');
        $rSt->execute([$id]);
        $row = $rSt->fetch();
        if (!$row) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Staff bonus/allowance record not found']);
            exit;
        }

        $finalSafe = in_array($safeCode, ['NONE_PAYROLL', 'SAFE_CYRENE', 'SAFE_JETTY'], true)
            ? $safeCode
            : (string) ($row['safe_code'] ?: 'NONE_PAYROLL');
        $nowIso = gmdate('Y-m-d\TH:i:s\Z');

        $pdo->prepare('UPDATE staff_bonuses_allowances SET status = ?, safe_code = ?, approved_by = ?, decided_at = ? WHERE id = ?')
            ->execute([$decision, $finalSafe, (string) $me['username'], $nowIso, $id]);

        if ($decision === 'APPROVED_PAID' && in_array($finalSafe, ['SAFE_CYRENE', 'SAFE_JETTY'], true) && (string) $row['status'] !== 'APPROVED_PAID') {
            $txDate = (string) ($row['tx_date'] ?: gmdate('Y-m-d'));
            [$wkE, $moE, $qrE, $yrE] = sc_erp_period_parts($txDate);
            $vSeq = 'SBA-' . $id;
            $cc   = $finalSafe === 'SAFE_JETTY' ? 'JETTY' : 'CYRENE';
            $amtEgp = (float) $row['amount_egp'];
            $pdo->prepare(
                'INSERT INTO expense_vouchers_fact
                 (exp_date, period_week, period_month, period_quarter, period_year, voucher_seq, description, cost_center, amount_le, amount_usd, amount_eur, amount_gbp, total_egp, source_file)
                 VALUES (?,?,?,?,?,?,?,?, ?,0,0,0, ?, "LIVE_MOBILE_SAFE")'
            )->execute([
                $txDate, $wkE, $moE, $qrE, $yrE, $vSeq,
                "Approved Staff {$row['entry_type']}: {$row['emp_name_en']} — {$row['reason_en']}",
                $cc, $amtEgp, $amtEgp,
            ]);
        }

        $okApprove = in_array($decision, ['APPROVED_PAID', 'POSTED_TO_PAYROLL'], true);
        sc_erp_push_notification(
            $pdo,
            (string) $row['emp_username'],
            'ALLOWANCE_STATUS',
            'SBA-' . $id,
            $okApprove
                ? '✅ Staff Allowance / Advance Approved (' . number_format((float) $row['amount_egp']) . ' EGP)'
                : '❌ Staff Allowance / Advance Declined',
            $okApprove
                ? '✅ تمت الموافقة وصرف البدل / السلفة (' . number_format((float) $row['amount_egp']) . ' ج.م)'
                : '❌ تم رفض طلب البدل / السلفة',
            (string) $row['reason_en'],
            (string) $row['reason_ar']
        );

        sc_erp_audit($pdo, (string) $me['username'], (string) $me['role_code'], 'DECIDE_STAFF_BONUS_ALLOWANCE', 'STAFF_REWARDS', 'SBA-' . $id, [
            'decision'  => $decision,
            'safe_code' => $finalSafe,
        ]);

        echo json_encode(['ok' => true, 'id' => $id, 'status' => $decision, 'safe_code' => $finalSafe]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Unknown API action']);
