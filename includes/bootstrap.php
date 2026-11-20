<?php
/**
 * Bootstrap: loaded first by every page and handler.
 * Session, security headers, i18n, CSRF, UTM/attribution capture, helpers.
 */
declare(strict_types=1);

define('ROOT_DIR', dirname(__DIR__));
require_once ROOT_DIR . '/config/site_config.php';

/* ---------------- Session (lazy: only on POST or existing session cookie) ---------------- */
function sc_ensure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    if (!headers_sent()) {
        @ini_set('session.use_strict_mode', '1');
        @ini_set('session.use_only_cookies', '1');
        @ini_set('session.cookie_httponly', '1');
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
}
if (!defined('SC_NO_SESSION') && ((($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') || !empty($_COOKIE['sc_sid']))) {
    sc_ensure_session();
}

/* ---------------- Security headers & WAF guard ---------------- */
if (!headers_sent()) {
    @header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Permitted-Cross-Domain-Policies: none');
    header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
    header('Permissions-Policy: geolocation=(), camera=(), microphone=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()');
    header("Content-Security-Policy: default-src 'self' https: data: blob:; script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://www.google-analytics.com https://googleads.g.doubleclick.net https://www.googleadservices.com https://connect.facebook.net https://analytics.tiktok.com https://snap.licdn.com https://www.tripadvisor.com https://www.tripadvisor.com.eg https://static.tacdn.com https://www.jscache.com; style-src 'self' 'unsafe-inline' https://static.tacdn.com https://fonts.googleapis.com; img-src 'self' https: data: blob:; font-src 'self' https: data:; connect-src 'self' https:; frame-src 'self' https://www.google.com https://maps.google.com https://www.googletagmanager.com https://td.doubleclick.net https://www.tripadvisor.com https://www.tripadvisor.com.eg https://www.facebook.com; frame-ancestors 'self'; base-uri 'self'; form-action 'self' https://wa.me; object-src 'none'; upgrade-insecure-requests");
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

/** Preserve Google Ads / Meta / UTM query parameters across PHP 301 redirects so client-side gtag.js sees gclid. */
function sc_with_campaign_query(string $destUrl): string
{
    $keep = [];
    foreach (UTM_KEYS as $k) {
        if (isset($_GET[$k]) && $_GET[$k] !== '') {
            $keep[$k] = (string) $_GET[$k];
        }
    }
    if (!$keep) {
        return $destUrl;
    }
    $sep = str_contains($destUrl, '?') ? '&' : '?';
    return $destUrl . $sep . http_build_query($keep);
}

/* ---------------- Money ---------------- */
function money(float|int|null $eur): string
{
    if ($eur === null) {
        return t('price_on_request');
    }
    return '€' . number_format((float) $eur, 0, '.', ',');
}

/* ---------------- Responsive WebP <picture> helper ---------------- */
function sc_picture(string $relPath, string $alt, int $w, int $h, array $opts = []): string
{
    $clean = ltrim($relPath, '/');
    $absOrig = ROOT_DIR . '/assets/' . $clean;
    $dotPos = strrpos($clean, '.');
    $stem = $dotPos !== false ? substr($clean, 0, $dotPos) : $clean;
    $webpRel = $stem . '.webp';
    $webp480Rel = $stem . '-480w.webp';
    $hasWebp = is_file(ROOT_DIR . '/assets/' . $webpRel);
    $has480 = is_file(ROOT_DIR . '/assets/' . $webp480Rel);

    $loading = $opts['loading'] ?? 'lazy';
    $decoding = $opts['decoding'] ?? 'async';
    $fetchpriority = $opts['fetchpriority'] ?? '';
    $sizes = $opts['sizes'] ?? '(max-width: 600px) 92vw, (max-width: 1200px) 46vw, 320px';
    $class = $opts['class'] ?? '';
    $extra = $opts['extra_attrs'] ?? '';

    $imgAttrs = 'src="' . e(asset($clean)) . '" alt="' . e($alt) . '" width="' . $w . '" height="' . $h . '"';
    if ($class !== '') {
        $imgAttrs .= ' class="' . e($class) . '"';
    }
    if ($loading !== '') {
        $imgAttrs .= ' loading="' . e($loading) . '"';
    }
    if ($decoding !== '') {
        $imgAttrs .= ' decoding="' . e($decoding) . '"';
    }
    if ($fetchpriority !== '') {
        $imgAttrs .= ' fetchpriority="' . e($fetchpriority) . '"';
    }
    if ($extra !== '') {
        $imgAttrs .= ' ' . $extra;
    }

    if (!$hasWebp && !$has480) {
        return '<img ' . $imgAttrs . '>';
    }
    if ($has480 && $hasWebp) {
        $fullW = max(520, min(1200, $w >= 520 ? $w : 960));
        $srcset = e(asset($webp480Rel)) . ' 480w, ' . e(asset($webpRel)) . ' ' . $fullW . 'w';
        return '<picture><source type="image/webp" srcset="' . $srcset . '" sizes="' . e($sizes) . '"><img ' . $imgAttrs . '></picture>';
    }
    $singleWebp = $hasWebp ? $webpRel : $webp480Rel;
    return '<picture><source type="image/webp" srcset="' . e(asset($singleWebp)) . '"><img ' . $imgAttrs . '></picture>';
}

/* ---------------- CSRF (stateless HMAC-SHA256 + session fallback) ---------------- */
function sc_app_secret(): string
{
    static $key = null;
    if ($key !== null) {
        return $key;
    }
    $secretFile = LEADS_STORAGE_DIR . '/.csrf_secret';
    if (is_file($secretFile)) {
        $raw = trim((string) @file_get_contents($secretFile));
        if (strlen($raw) >= 32) {
            return $key = $raw;
        }
    }
    if (is_dir(LEADS_STORAGE_DIR) || @mkdir(LEADS_STORAGE_DIR, 0750, true)) {
        $gen = bin2hex(random_bytes(32));
        if (@file_put_contents($secretFile, $gen, LOCK_EX) !== false) {
            return $key = $gen;
        }
    }
    return $key = hash('sha256', __DIR__ . '|' . SITE_URL . '|sc_hmac_v1');
}

function csrf_token(): string
{
    $ts = (string) time();
    return $ts . '.' . hash_hmac('sha256', 'sc_csrf|' . $ts, sc_app_secret());
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(?string $token): bool
{
    if (!is_string($token) || $token === '') {
        return false;
    }
    if (str_contains($token, '.')) {
        [$tsStr, $sig] = explode('.', $token, 2);
        if (ctype_digit($tsStr) && strlen($sig) === 64) {
            $ts = (int) $tsStr;
            if (abs(time() - $ts) <= 86400) {
                $expected = hash_hmac('sha256', 'sc_csrf|' . $tsStr, sc_app_secret());
                if (hash_equals($expected, $sig)) {
                    return true;
                }
            }
        }
    }
    return !empty($_SESSION['csrf_token']) && hash_equals((string) $_SESSION['csrf_token'], $token);
}

/* ---------------- UTM / click-ID attribution ---------------- */
$GLOBALS['SC_ATTR'] = [];
$GLOBALS['SC_HAS_CAMPAIGN_QUERY'] = false;

function sc_capture_attribution(): void
{
    $found = [];
    foreach (UTM_KEYS as $k) {
        if (isset($_GET[$k]) && $_GET[$k] !== '') {
            $found[$k] = substr(preg_replace('/[^\w\-\.\~\:\/@ ]/u', '', (string) $_GET[$k]) ?? '', 0, 200);
        }
    }
    if ($found) {
        $GLOBALS['SC_HAS_CAMPAIGN_QUERY'] = true;
        $found['landing_page'] = substr((string) ($_SERVER['REQUEST_URI'] ?? '/'), 0, 300);
        $found['captured_at'] = gmdate('c');
        $GLOBALS['SC_ATTR'] = $found; // last non-direct click wins
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['attribution'] = $found;
        }
        if (!headers_sent()) {
            setcookie('sc_attr', base64_encode((string) json_encode($found)), [
                'expires' => time() + UTM_COOKIE_DAYS * 86400, 'path' => '/', 'samesite' => 'Lax', 'httponly' => true,
            ]);
        }
    } elseif (!empty($_COOKIE['sc_attr'])) {
        $d = json_decode((string) base64_decode((string) $_COOKIE['sc_attr'], true), true);
        if (is_array($d)) {
            $GLOBALS['SC_ATTR'] = array_intersect_key($d, array_flip([...UTM_KEYS, 'landing_page', 'captured_at']));
        }
    } elseif (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['attribution']) && is_array($_SESSION['attribution'])) {
        $GLOBALS['SC_ATTR'] = $_SESSION['attribution'];
    }
}
sc_capture_attribution();

function attribution(): array
{
    return is_array($GLOBALS['SC_ATTR'] ?? null) ? $GLOBALS['SC_ATTR'] : [];
}

/** Hidden inputs carrying attribution into every lead form. */
function attribution_fields(): string
{
    $a = attribution();
    $ref = $_SESSION['first_referrer'] ?? substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 300);
    $html = '';
    foreach (UTM_KEYS as $k) {
        $html .= '<input type="hidden" name="' . e($k) . '" value="' . e($a[$k] ?? '') . '">';
    }
    $html .= '<input type="hidden" name="landing_page" value="' . e($a['landing_page'] ?? '') . '">';
    $html .= '<input type="hidden" name="referrer" value="' . e((string) $ref) . '">';
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
