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
    $scIsTagAssistant = isset($_GET['gtm_debug']) || isset($_GET['__gsa']) || isset($_GET['tag_assistant'])
        || (bool) preg_match('#^https://(tagassistant\.google\.com|ads\.google\.com|www\.googletagmanager\.com)(/|$)#i', (string) ($_SERVER['HTTP_REFERER'] ?? ''));
    header('Cross-Origin-Opener-Policy: ' . ($scIsTagAssistant ? 'unsafe-none' : 'same-origin-allow-popups'));
    header('Permissions-Policy: geolocation=(), camera=(), microphone=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()');
    header("Content-Security-Policy: default-src 'self' https: data: blob:; script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://www.google-analytics.com https://www.google.com https://googleads.g.doubleclick.net https://www.googleadservices.com https://pagead2.googlesyndication.com https://adservice.google.com https://cct.google https://tagassistant.google.com https://connect.facebook.net https://analytics.tiktok.com https://bat.bing.com https://*.bing.com https://*.clarity.ms https://snap.licdn.com https://www.tripadvisor.com https://www.tripadvisor.com.eg https://static.tacdn.com https://www.jscache.com; style-src 'self' 'unsafe-inline' https://static.tacdn.com https://fonts.googleapis.com https://tagassistant.google.com https://www.googletagmanager.com; img-src 'self' https: data: blob: https://bat.bing.com https://*.bing.com https://*.clarity.ms; font-src 'self' https: data:; connect-src 'self' https: https://bat.bing.com https://*.bing.com https://*.clarity.ms; frame-src 'self' https://www.google.com https://maps.google.com https://www.googletagmanager.com https://td.doubleclick.net https://bid.g.doubleclick.net https://www.googleadservices.com https://tagassistant.google.com https://cct.google https://bat.bing.com https://www.tripadvisor.com https://www.tripadvisor.com.eg https://www.facebook.com; frame-ancestors 'self'; base-uri 'self'; form-action 'self' https://wa.me; object-src 'none'; upgrade-insecure-requests");
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
    $clean = ltrim($path, '/');
    if (str_starts_with($clean, 'images/') || str_starts_with($clean, 'videos/')) {
        return '/assets/' . $clean;
    }
    $file = ROOT_DIR . '/assets/' . $clean;
    $v = is_file($file) ? (string) filemtime($file) : '1';
    return '/assets/' . $clean . '?v=' . $v;
}

/* ---------------- Scalar-safe GET helper & Clean Language Subdirectory Routing ---------------- */
function sc_get_str(string $key, string $default = ''): string
{
    $v = $_GET[$key] ?? $default;
    return is_scalar($v) ? trim((string) $v) : $default;
}

function sc_detect_lang(): string
{
    $supported = array_keys(SUPPORTED_LANGS);
    // 1) Explicit ?lang= (populated by .htaccess /ru/... rewrite or legacy ?lang=xx links)
    $q = strtolower(sc_get_str('lang'));
    if ($q !== '' && in_array($q, $supported, true)) {
        return $q;
    }
    // 2) Explicit POST lang (for /handlers/lead_capture.php, diver_checkin.php, newsletter.php)
    $rawPost = $_POST['lang'] ?? '';
    $p = is_scalar($rawPost) ? strtolower(trim((string) $rawPost)) : '';
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
    // 4) Unprefixed URLs (/, /prices, /tours/...) are deterministically English (DEFAULT_LANG)
    return DEFAULT_LANG;
}

define('LANG', sc_detect_lang());
define('IS_RTL', in_array(LANG, RTL_LANGS, true));
define('DIR', IS_RTL ? 'rtl' : 'ltr');

function sc_arrow_next(): string
{
    return IS_RTL ? '←' : '→';
}

function sc_arrow_prev(): string
{
    return IS_RTL ? '→' : '←';
}

$GLOBALS['SC_STRINGS'] = require ROOT_DIR . '/lang/en.php';
if (LANG !== 'en' && is_file(ROOT_DIR . '/lang/' . LANG . '.php')) {
    $GLOBALS['SC_STRINGS'] = array_replace($GLOBALS['SC_STRINGS'], require ROOT_DIR . '/lang/' . LANG . '.php');
}

require_once ROOT_DIR . '/includes/google_reviews.php';

/** Translate (raw). Use te() when printing into HTML. */
function t(string $key, array $vars = []): string
{
    $s = $GLOBALS['SC_STRINGS'][$key] ?? $key;
    foreach ($vars as $k => $v) {
        $s = str_replace('{' . $k . '}', (string) $v, $s);
    }
    return sc_sync_live_review_text($s);
}

function te(string $key, array $vars = []): string
{
    return e(t($key, $vars));
}

/** Pick localized value from ['en'=>..,'ar'=>..,'ru'=>..] arrays or return string. */
function loc(array|string|null $v): string
{
    if (is_array($v)) {
        return sc_sync_live_review_text((string) ($v[LANG] ?? $v['en'] ?? reset($v)));
    }
    return sc_sync_live_review_text((string) $v);
}

/** Strip any leading /{lang} prefix from a path and normalize /index.php & core .php routes to clean extensionless paths. */
function strip_lang_prefix(string $path): string
{
    $p = '/' . ltrim($path, '/');
    $pattern = '#^/(' . implode('|', array_map('preg_quote', array_keys(SUPPORTED_LANGS))) . ')(?=/|$)#i';
    $p = (string) preg_replace($pattern, '', $p);
    if ($p === '' || $p === '/index.php' || preg_match('#^/r/[a-zA-Z0-9._-]+/?$#', $p)) {
        return '/';
    }
    if ($p === '/dive-sites.php') {
        return '/dive-sites-sharm-el-sheikh';
    }
    $p = (string) preg_replace('#^/(services|prices|about|contact|faq|privacy-policy|terms|check-in|guides|gallery|thank-you)\.php$#i', '/$1', $p);
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

/** Preserve all non-router query parameters (gclid, gbraid, wbraid, fbclid, ttclid, msclkid, utm_*, gad_source, etc.) across PHP 301 redirects. */
function sc_with_campaign_query(string $destUrl): string
{
    $keep = $_GET;
    unset($keep['lang'], $keep['section'], $keep['slug'], $keep['hub'], $keep['legacy']);
    $keep = array_filter($keep, static fn($v): bool => $v !== '' && $v !== null);
    if (!$keep) {
        return $destUrl;
    }
    $sep = str_contains($destUrl, '?') ? '&' : '?';
    return $destUrl . $sep . http_build_query($keep);
}

/** True iff catalog ID belongs to the 59 standalone Meta Catalog products (excludes add-ons/fees/rentals/insurance). */
function sc_is_catalog_product_id(string $id): bool
{
    $id = trim($id);
    if ($id === '' || $id === 'dive-extra' || $id === 'padi-cert-fee') {
        return false;
    }
    foreach (['eq-', 'fee-', 'ins-'] as $prefix) {
        if (str_starts_with($id, $prefix)) {
            return false;
        }
    }
    return true;
}

function sc_is_catalog_item(array $it): bool
{
    if (!isset($it['price']) || !is_numeric($it['price']) || (float) $it['price'] <= 0) {
        return false;
    }
    if (empty($it['slug'])) {
        return false;
    }
    return sc_is_catalog_product_id((string) ($it['id'] ?? ''));
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
    $decoding = $opts['decoding'] ?? ($loading === 'eager' ? 'sync' : 'async');
    $fetchpriority = $opts['fetchpriority'] ?? '';
    $sizes = $opts['sizes'] ?? ($w <= 480 ? '(max-width: 599px) 78vw, 240px' : '(max-width: 959px) 92vw, 46vw');
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

/* ---------------- UTM / click-ID & Meta _fbc/_fbp attribution ---------------- */
$GLOBALS['SC_ATTR'] = [];
$GLOBALS['SC_HAS_CAMPAIGN_QUERY'] = false;

function sc_is_https_request(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function sc_set_seller_cookie(string $sellerCode): void
{
    if ($sellerCode === '') {
        return;
    }
    $_COOKIE['sc_seller'] = $sellerCode;
    if (!headers_sent()) {
        setcookie('sc_seller', $sellerCode, [
            'expires'  => time() + UTM_COOKIE_DAYS * 86400,
            'path'     => '/',
            'secure'   => sc_is_https_request(),
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
    }
}

function attribution(): array
{
    $a = is_array($GLOBALS['SC_ATTR'] ?? null) ? $GLOBALS['SC_ATTR'] : [];
    if (empty($a['seller']) && !empty($a['rep'])) {
        $a['seller'] = sc_normalize_seller_token((string) $a['rep']);
    }
    if (empty($a['seller']) && !empty($_COOKIE['sc_seller'])) {
        $s = sc_normalize_seller_token((string) $_COOKIE['sc_seller']);
        if ($s !== '') {
            $a['seller'] = $s;
        }
    }
    return $a;
}

function sc_sanitize_meta_cookie(string $val): string
{
    $val = trim((string) preg_replace('/[^\w\-\.]/', '', $val));
    return (str_starts_with($val, 'fb.') && strlen($val) <= 300) ? $val : '';
}

function sc_resolve_fbc(?array $attr = null, string $postedFbc = ''): string
{
    $cookieFbc = sc_sanitize_meta_cookie((string) ($_COOKIE['_fbc'] ?? ''));
    $urlFbclid = trim((string) preg_replace('/[^\w\-]/', '', sc_get_str('fbclid')));
    if ($urlFbclid !== '') {
        if ($cookieFbc !== '' && str_ends_with($cookieFbc, '.' . $urlFbclid)) {
            return $cookieFbc;
        }
        return 'fb.1.' . ((int) round(microtime(true) * 1000)) . '.' . substr($urlFbclid, 0, 220);
    }
    if ($cookieFbc !== '') {
        return $cookieFbc;
    }
    $cleanPosted = sc_sanitize_meta_cookie($postedFbc);
    if ($cleanPosted !== '') {
        return $cleanPosted;
    }
    $a = $attr ?? attribution();
    $storedFbc = sc_sanitize_meta_cookie((string) ($a['fbc'] ?? ''));
    if ($storedFbc !== '') {
        return $storedFbc;
    }
    $fbclid = trim((string) preg_replace('/[^\w\-]/', '', (string) ($a['fbclid'] ?? '')));
    if ($fbclid !== '') {
        return 'fb.1.' . ((int) round(microtime(true) * 1000)) . '.' . substr($fbclid, 0, 220);
    }
    return '';
}

function sc_resolve_fbp(?array $attr = null, string $postedFbp = ''): string
{
    $cookieFbp = sc_sanitize_meta_cookie((string) ($_COOKIE['_fbp'] ?? ''));
    if ($cookieFbp !== '') {
        return $cookieFbp;
    }
    $cleanPosted = sc_sanitize_meta_cookie($postedFbp);
    if ($cleanPosted !== '') {
        return $cleanPosted;
    }
    $a = $attr ?? attribution();
    return sc_sanitize_meta_cookie((string) ($a['fbp'] ?? ''));
}

function sc_infer_channel(array $a): string
{
    $src = strtolower(trim((string) ($a['utm_source'] ?? '')));
    $med = strtolower(trim((string) ($a['utm_medium'] ?? '')));
    if (!empty($a['gclid']) || !empty($a['gbraid']) || !empty($a['wbraid']) || ($src === 'google' && in_array($med, ['cpc', 'ppc', 'paid', 'pmax'], true))) {
        return 'Google Ads';
    }
    if (!empty($a['fbclid']) || !empty($a['fbc']) || in_array($src, ['meta', 'facebook', 'fb', 'instagram', 'ig'], true)) {
        return 'Meta Ads';
    }
    if (!empty($a['ttclid']) || $src === 'tiktok') {
        return 'TikTok Ads';
    }
    if (!empty($a['msclkid']) || in_array($src, ['bing', 'microsoft'], true)) {
        return 'Microsoft Ads';
    }
    if ($src !== '') {
        return ucfirst($src) . ($med !== '' ? ' (' . $med . ')' : '');
    }
    if (!empty($a['seller'])) {
        return 'Seller QR (' . $a['seller'] . ')';
    }
    return 'Organic / Direct';
}

function sc_resolve_channel_summary(array $a): array
{
    $hasGoogle = !empty($a['gclid']) || !empty($a['gbraid']) || !empty($a['wbraid']);
    $hasMeta   = !empty($a['fbclid']) || !empty($a['fbc']);
    $last      = !empty($a['last_channel']) ? (string) $a['last_channel'] : sc_infer_channel($a);
    $first     = !empty($a['first_channel']) ? (string) $a['first_channel'] : $last;

    if ($hasGoogle && $hasMeta) {
        $primary = ($last === 'Meta Ads') ? 'Meta Ads (Google Assisted)' : 'Google Ads (Meta Assisted)';
        $path    = ($first === $last) ? ('Meta Ads + Google Ads') : ($first . ' -> ' . $last);
    } else {
        $primary = $last;
        $path    = ($first !== '' && $first !== $last) ? ($first . ' -> ' . $last) : $last;
    }

    return [
        'channel_source' => $primary,
        'first_channel'  => $first,
        'last_channel'   => $last,
        'journey_path'   => $path,
    ];
}

function sc_capture_attribution(): void
{
    $allowedKeys = [...UTM_KEYS, 'fbc', 'fbp', 'landing_page', 'captured_at', 'first_channel', 'last_channel', 'journey_path'];
    $prev = [];
    if (!empty($_COOKIE['sc_attr'])) {
        $d = json_decode((string) base64_decode((string) $_COOKIE['sc_attr'], true), true);
        if (is_array($d)) {
            $prev = array_intersect_key($d, array_flip($allowedKeys));
        }
    } elseif (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['attribution']) && is_array($_SESSION['attribution'])) {
        $prev = array_intersect_key($_SESSION['attribution'], array_flip($allowedKeys));
    }
    $priorSeller = sc_normalize_seller_token((string) ($prev['seller'] ?? $prev['rep'] ?? ($_COOKIE['sc_seller'] ?? '')));

    $found = [];
    foreach (UTM_KEYS as $k) {
        if (isset($_GET[$k]) && is_scalar($_GET[$k]) && (string) $_GET[$k] !== '') {
            $found[$k] = substr(preg_replace('/[^\w\-\.\~\:\/@ ]/u', '', (string) $_GET[$k]) ?? '', 0, 200);
        }
    }
    if ($found) {
        $GLOBALS['SC_HAS_CAMPAIGN_QUERY'] = true;
        $rawSeller = (string) ($found['seller'] ?? $found['rep'] ?? '');
        if ($rawSeller !== '') {
            $normSeller = sc_normalize_seller_token($rawSeller);
            if ($normSeller !== '') {
                $found['seller'] = $normSeller;
                sc_set_seller_cookie($normSeller);
            } else {
                unset($found['seller']);
            }
        } elseif ($priorSeller !== '') {
            $found['seller'] = $priorSeller;
        }
        $thisTouchChannel = sc_infer_channel($found);
        // Merge with $prev so cross-channel click IDs (e.g. Meta fbclid + Google gclid + Seller QR) are ALL preserved!
        $merged = array_merge($prev, $found);
        if (!empty($found['seller'])) {
            $merged['seller'] = $found['seller'];
        } elseif (empty($merged['seller']) && $priorSeller !== '') {
            $merged['seller'] = $priorSeller;
        }
        if (!empty($found['fbclid'])) {
            $merged['fbc'] = sc_resolve_fbc($found);
        } elseif (!empty($prev['fbc'])) {
            $merged['fbc'] = $prev['fbc'];
        }
        $initFbp = sc_resolve_fbp($merged);
        if ($initFbp !== '') {
            $merged['fbp'] = $initFbp;
        }
        $merged['first_channel'] = !empty($prev['first_channel']) ? (string) $prev['first_channel'] : $thisTouchChannel;
        $merged['last_channel']  = $thisTouchChannel;
        $prevPath = !empty($prev['journey_path']) ? (string) $prev['journey_path'] : $merged['first_channel'];
        if (!str_ends_with($prevPath, $thisTouchChannel)) {
            $merged['journey_path'] = substr($prevPath . ' -> ' . $thisTouchChannel, 0, 180);
        } else {
            $merged['journey_path'] = $prevPath;
        }
        if (empty($merged['landing_page'])) {
            $merged['landing_page'] = substr((string) ($_SERVER['REQUEST_URI'] ?? '/'), 0, 300);
        }
        $merged['captured_at'] = gmdate('c');
        $GLOBALS['SC_ATTR'] = $merged;
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['attribution'] = $merged;
        }
        if (!headers_sent()) {
            setcookie('sc_attr', base64_encode((string) json_encode($merged)), [
                'expires'  => time() + UTM_COOKIE_DAYS * 86400,
                'path'     => '/',
                'secure'   => sc_is_https_request(),
                'samesite' => 'Lax',
                'httponly' => true,
            ]);
        }
    } elseif ($prev !== []) {
        if (empty($prev['seller']) && $priorSeller !== '') {
            $prev['seller'] = $priorSeller;
        } elseif (!empty($prev['seller'])) {
            $prev['seller'] = sc_normalize_seller_token((string) $prev['seller']);
        }
        $GLOBALS['SC_ATTR'] = $prev;
    }

    if (empty($GLOBALS['SC_ATTR']['seller']) && $priorSeller !== '') {
        $GLOBALS['SC_ATTR']['seller'] = $priorSeller;
    }
    if (!empty($GLOBALS['SC_ATTR']['seller'])) {
        $GLOBALS['SC_HAS_CAMPAIGN_QUERY'] = true;
    }
}
sc_capture_attribution();

/** Apply fallback seller referral (e.g. /check-in?ref=<seller_code>) before header.php runs. */
function sc_apply_seller_referral(string $rawSeller): void
{
    $norm = sc_normalize_seller_token($rawSeller);
    if ($norm === '') {
        return;
    }
    $GLOBALS['SC_HAS_CAMPAIGN_QUERY'] = true;
    $cur = attribution();
    $cur['seller'] = $norm;
    if (empty($cur['landing_page'])) {
        $cur['landing_page'] = substr((string) ($_SERVER['REQUEST_URI'] ?? '/'), 0, 300);
    }
    if (empty($cur['captured_at'])) {
        $cur['captured_at'] = gmdate('c');
    }
    $GLOBALS['SC_ATTR'] = $cur;
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['attribution'] = $cur;
    }
    sc_set_seller_cookie($norm);
    if (!headers_sent()) {
        setcookie('sc_attr', base64_encode((string) json_encode($cur)), [
            'expires'  => time() + UTM_COOKIE_DAYS * 86400,
            'path'     => '/',
            'secure'   => sc_is_https_request(),
            'samesite' => 'Lax',
            'httponly' => true,
        ]);
    }
}

/** Hidden inputs carrying attribution into every lead form. */
function attribution_fields(): string
{
    $a = attribution();
    $ref = $_SESSION['first_referrer'] ?? substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 300);
    $html = '';
    foreach (array_unique(UTM_KEYS) as $k) {
        $html .= '<input type="hidden" name="' . e($k) . '" value="' . e((string) ($a[$k] ?? '')) . '">';
    }
    $html .= '<input type="hidden" name="fbc" value="' . e(sc_resolve_fbc($a)) . '">';
    $html .= '<input type="hidden" name="fbp" value="' . e(sc_resolve_fbp($a)) . '">';
    $html .= '<input type="hidden" name="landing_page" value="' . e((string) ($a['landing_page'] ?? '')) . '">';
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
    $src = !empty($a['utm_source']) ? (string) $a['utm_source'] : 'website';
    $camp = !empty($a['utm_campaign']) ? (string) $a['utm_campaign'] : $placement;
    $seller = trim((string) ($a['seller'] ?? ''));
    $sellerSuffix = $seller !== '' ? (' | seller: ' . $seller) : '';
    $msg .= "\n\n[ref: " . $src . ' / ' . $camp . ' / ' . LANG . $sellerSuffix . ']';
    return 'https://wa.me/' . preg_replace('/\D/', '', WHATSAPP_NUMBER) . '?text=' . rawurlencode($msg);
}

/* ---------------- Verified Seller / Concierge Referral Pill ---------------- */
function sc_active_seller_referral(): ?array
{
    $a = attribution();
    $raw = trim((string) ($a['seller'] ?? $a['rep'] ?? ''));
    if ($raw === '') {
        return null;
    }
    return sc_resolve_seller_public($raw);
}

function sc_render_seller_referral_pill(): string
{
    // Indoor/physical location sellers must never have their names displayed on the public website UI.
    return '';
}

/* ---------------- Consent ---------------- */
function consent_state(): string
{
    $c = strtolower(trim((string) ($_COOKIE[CONSENT_COOKIE] ?? '')));
    if ($c === 'all' || $c === 'granted') {
        return 'all';
    }
    if (in_array($c, ['essential', 'denied', 'reject', 'rejected', 'none', '0', 'false'], true)) {
        return 'essential';
    }
    return 'unset';
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
