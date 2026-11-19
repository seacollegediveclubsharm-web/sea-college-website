<?php
/**
 * Bootstrap: loaded first by every page and handler.
 * Session, security headers, i18n, CSRF, UTM/attribution capture, helpers.
 */
declare(strict_types=1);

define('ROOT_DIR', dirname(__DIR__));
require_once ROOT_DIR . '/config/site_config.php';

/* ---------------- Session (hardened cookie) ---------------- */
if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('sc_sid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* ---------------- Security headers ---------------- */
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
    if (SITE_NOINDEX) {
        header('X-Robots-Tag: noindex, nofollow');
    }
}

/* ---------------- Escaping helpers ---------------- */
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return SITE_URL . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = ROOT_DIR . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? (string) filemtime($file) : '1';
    return '/assets/' . ltrim($path, '/') . '?v=' . $v;
}

/* ---------------- i18n & Clean Language Subdirectory Routing ---------------- */
function sc_detect_lang(): string
{
    $supported = array_keys(SUPPORTED_LANGS);
    // 1) Explicit ?lang= (populated by .htaccess /ru/... rewrite or legacy ?lang=xx links)
    $q = isset($_GET['lang']) ? strtolower(trim((string) $_GET['lang'])) : '';
    if ($q !== '' && in_array($q, $supported, true)) {
        return $q;
    }
    // 2) Explicit POST lang (for /handlers/lead_capture.php, diver_checkin.php, newsletter.php)
    $p = isset($_POST['lang']) ? strtolower(trim((string) $_POST['lang'])) : '';
    if ($p !== '' && in_array($p, $supported, true)) {
        return $p;
    }
    // 3) Direct URL path prefix fallback (e.g. ErrorDocument 404 on /ru/missing-page or CLI tests)
    $reqPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    $pattern = '#^/(' . implode('|', array_map('preg_quote', $supported)) . ')(?=/|$)#i';
    if (preg_match($pattern, $reqPath, $m)) {
        $detected = strtolower($m[1]);
        if (in_array($detected, $supported, true)) {
            return $detected;
        }
    }
    // 4) Unprefixed URLs (/, /prices.php, /tours/...) are deterministically English (DEFAULT_LANG)
    return DEFAULT_LANG;
}

define('LANG', sc_detect_lang());
define('IS_RTL', in_array(LANG, RTL_LANGS, true));
define('DIR', IS_RTL ? 'rtl' : 'ltr');

$GLOBALS['SC_STRINGS'] = require ROOT_DIR . '/lang/en.php';
if (LANG !== 'en' && is_file(ROOT_DIR . '/lang/' . LANG . '.php')) {
    $GLOBALS['SC_STRINGS'] = array_replace($GLOBALS['SC_STRINGS'], require ROOT_DIR . '/lang/' . LANG . '.php');
}

/** Translate (raw). Use te() when printing into HTML. */
function t(string $key, array $vars = []): string
{
    $s = $GLOBALS['SC_STRINGS'][$key] ?? $key;
    foreach ($vars as $k => $v) {
        $s = str_replace('{' . $k . '}', (string) $v, $s);
    }
    return $s;
}

function te(string $key, array $vars = []): string
{
    return e(t($key, $vars));
}

/** Pick localized value from ['en'=>..,'ar'=>..,'ru'=>..] arrays or return string. */
function loc(array|string|null $v): string
{
    if (is_array($v)) {
        return (string) ($v[LANG] ?? $v['en'] ?? reset($v));
    }
    return (string) $v;
}

/** Strip any leading /{lang} prefix from a path and normalize /index.php -> /. */
function strip_lang_prefix(string $path): string
{
    $p = '/' . ltrim($path, '/');
    $pattern = '#^/(' . implode('|', array_map('preg_quote', array_keys(SUPPORTED_LANGS))) . ')(?=/|$)#i';
    $p = (string) preg_replace($pattern, '', $p);
    if ($p === '' || $p === '/index.php') {
        return '/';
    }
    return str_starts_with($p, '/') ? $p : '/' . $p;
}

/** Canonical unprefixed path of the current page (e.g. '/', '/prices.php', '/courses/padi-open-water-diver-sharm-el-sheikh'). */
function base_request_path(): string
{
    $raw = (string) ($GLOBALS['page']['canonical_path'] ?? (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));
    return strip_lang_prefix($raw);
}

/**
 * Build an internal link in the active (or specified) language using clean Google Ads subdirectories:
 * - en (default): '/prices.php', '/courses/padi-open-water-diver-sharm-el-sheikh', '/'
 * - ru/de/it/fr/pl/ar: '/ru/prices.php', '/ru/courses/padi-open-water-diver-sharm-el-sheikh', '/ru/'
 */
function page_link(string $path = '/', string $hash = '', ?string $lang = null): string
{
    $targetLang = $lang ?? LANG;
    if (!isset(SUPPORTED_LANGS[$targetLang])) {
        $targetLang = DEFAULT_LANG;
    }
    if ($path !== '' && str_starts_with($path, '#')) {
        return $path;
    }
    $inlineHash = '';
    if (str_contains($path, '#')) {
        [$path, $inlineHash] = explode('#', $path, 2);
    }
    $query = '';
    if (str_contains($path, '?')) {
        [$path, $query] = explode('?', $path, 2);
        $query = $query !== '' ? '?' . $query : '';
    }
    $cleanPath = strip_lang_prefix($path === '' ? '/' : $path);
    $finalHash = $hash !== '' ? $hash : $inlineHash;

    if ($targetLang === DEFAULT_LANG) {
        $out = $cleanPath;
    } else {
        $out = '/' . $targetLang . ($cleanPath === '/' ? '/' : $cleanPath);
    }
    return $out . $query . ($finalHash !== '' ? '#' . $finalHash : '');
}

/** Current page URL in another language (preserves campaign query params like gclid/utm_*). */
function lang_url(string $lang): string
{
    $base = page_link(base_request_path(), '', $lang);
    $params = $_GET;
    unset($params['lang'], $params['section'], $params['slug'], $params['hub'], $params['legacy']);
    return $base . ($params ? '?' . http_build_query($params) : '');
}

function canonical_url(?string $lang = null): string
{
    $targetLang = $lang ?? LANG;
    return SITE_URL . page_link(base_request_path(), '', $targetLang);
}

/* ---------------- Money ---------------- */
function money(float|int|null $eur): string
{
    if ($eur === null) {
        return t('price_on_request');
    }
    return '€' . number_format((float) $eur, 0, '.', ',');
}

/* ---------------- CSRF ---------------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/* ---------------- UTM / click-ID attribution ---------------- */
function sc_capture_attribution(): void
{
    $found = [];
    foreach (UTM_KEYS as $k) {
        if (isset($_GET[$k]) && $_GET[$k] !== '') {
            $found[$k] = substr(preg_replace('/[^\w\-\.\~\:\/@ ]/u', '', (string) $_GET[$k]) ?? '', 0, 200);
        }
    }
    if ($found) {
        $found['landing_page'] = substr((string) ($_SERVER['REQUEST_URI'] ?? '/'), 0, 300);
        $found['captured_at'] = gmdate('c');
        $_SESSION['attribution'] = $found; // last non-direct click wins
        setcookie('sc_attr', base64_encode((string) json_encode($found)), [
            'expires' => time() + UTM_COOKIE_DAYS * 86400, 'path' => '/', 'samesite' => 'Lax', 'httponly' => true,
        ]);
    }
    if (empty($_SESSION['attribution']) && !empty($_COOKIE['sc_attr'])) {
        $d = json_decode((string) base64_decode((string) $_COOKIE['sc_attr'], true), true);
        if (is_array($d)) {
            $_SESSION['attribution'] = array_intersect_key($d, array_flip([...UTM_KEYS, 'landing_page', 'captured_at']));
        }
    }
    if (empty($_SESSION['first_referrer']) && !empty($_SERVER['HTTP_REFERER'])) {
        $_SESSION['first_referrer'] = substr((string) $_SERVER['HTTP_REFERER'], 0, 300);
    }
}
sc_capture_attribution();

function attribution(): array
{
    return is_array($_SESSION['attribution'] ?? null) ? $_SESSION['attribution'] : [];
}

/** Hidden inputs carrying attribution into every lead form. */
function attribution_fields(): string
{
    $a = attribution();
    $html = '';
    foreach (UTM_KEYS as $k) {
        $html .= '<input type="hidden" name="' . e($k) . '" value="' . e($a[$k] ?? '') . '">';
    }
    $html .= '<input type="hidden" name="landing_page" value="' . e($a['landing_page'] ?? '') . '">';
    $html .= '<input type="hidden" name="referrer" value="' . e($_SESSION['first_referrer'] ?? '') . '">';
    return $html;
}

/* ---------------- WhatsApp link builder ---------------- */
function whatsapp_link(string $topic = '', string $placement = 'general'): string
{
    $a = attribution();
    $msg = t('wa_greeting');
    if ($topic !== '') {
        $msg .= ' ' . t('wa_interest', ['topic' => $topic]);
    }
    $src = $a['utm_source'] ?? 'website';
    $camp = $a['utm_campaign'] ?? $placement;
    $msg .= "\n\n[ref: " . $src . ' / ' . $camp . ' / ' . LANG . ']';
    return 'https://wa.me/' . preg_replace('/\D/', '', WHATSAPP_NUMBER) . '?text=' . rawurlencode($msg);
}

/* ---------------- Consent ---------------- */
function consent_state(): string
{
    $c = (string) ($_COOKIE[CONSENT_COOKIE] ?? '');
    return in_array($c, ['all', 'essential'], true) ? $c : 'unset';
}

/** Helper used by pages: FAQPage node from translation keys faq_N_q / faq_N_a. */
function schema_faq(array $nums): array
{
    $q = [];
    foreach ($nums as $n) {
        $q[] = [
            '@type' => 'Question',
            'name' => t("faq_{$n}_q"),
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => t("faq_{$n}_a")],
        ];
    }
    return ['@type' => 'FAQPage', 'mainEntity' => $q];
}

/* ---------------- Page meta defaults ---------------- */
$page = [
    'id' => 'home',
    'title' => t('meta_home_title'),
    'description' => t('meta_home_desc'),
    'image' => url('assets/images/og-default.jpg'),
    'type' => 'website',
    'schema' => [],
    'breadcrumbs' => [],
];
