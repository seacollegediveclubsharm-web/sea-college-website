<?php
/**
 * Google Tag Gateway (First-Party Mode) — Hardened Allowlisted Proxy.
 *
 * Routes reserved first-party paths on seacollegediveclub.com directly to
 * Google's First-Party Servers (*.fps.goog) while preserving the full
 * measurement return path, visitor IP, geolocation headers, and Google cookies:
 *   /s-tm/* -> https://gtm-pbvq56xc.fps.goog/s-tm/*
 *   /s-aw/* -> https://aw-16728866067.fps.goog/s-aw/*
 *   /s-ga/* -> https://g-99528rzn9z.fps.goog/s-ga/*
 *
 * Security & Performance guarantees:
 * - Zero session startup (SC_NO_SESSION) and zero HTML bootstrap overhead (<1ms PHP overhead).
 * - Strict hardcoded tag allowlist: impossible to abuse as an open proxy (SSRF-proof).
 * - Strips internal Sea College cookies (sc_sid, sc_attr, sc_consent, sc_currency).
 * - Sets CDN-Cache-Control: no-store so shared edge CDNs (hcdn) never cross-cache responses.
 */
declare(strict_types=1);

define('SC_NO_SESSION', true);
require_once dirname(__DIR__) . '/config/site_config.php';

@header_remove('X-Powered-By');
header('X-Robots-Tag: noindex, nofollow');

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!in_array($method, ['GET', 'POST', 'HEAD', 'OPTIONS'], true)) {
    http_response_code(405);
    header('Allow: GET, POST, HEAD, OPTIONS');
    exit;
}

if ($method === 'OPTIONS') {
    http_response_code(204);
    header('Cache-Control: no-store');
    header('CDN-Cache-Control: no-store');
    exit;
}

// Strict allowlist mapping first-party prefix -> exact Google Tag ID on *.fps.goog
$allowedRoutes = [];
if (GTM_ID !== '') {
    $allowedRoutes[trim(GTG_GTM_PATH, '/')] = strtolower(GTM_ID) . '.fps.goog';
}
if (GOOGLE_ADS_ID !== '') {
    $allowedRoutes[trim(GTG_ADS_PATH, '/')] = strtolower(GOOGLE_ADS_ID) . '.fps.goog';
}
if (GA4_ID !== '') {
    $allowedRoutes[trim(GTG_GA4_PATH, '/')] = strtolower(GA4_ID) . '.fps.goog';
}

$rawUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$qPos = strpos($rawUri, '?');
$rawPath = $qPos === false ? $rawUri : substr($rawUri, 0, $qPos);
$rawQuery = $qPos === false ? '' : substr($rawUri, $qPos + 1);
if ($rawQuery === '' && !empty($_SERVER['QUERY_STRING'])) {
    $rawQuery = (string) $_SERVER['QUERY_STRING'];
}

if (!preg_match('#^/([a-z0-9-]+)(/.*)?$#i', $rawPath, $m)) {
    http_response_code(404);
    exit;
}

$prefix = strtolower($m[1]);
if (!isset($allowedRoutes[$prefix])) {
    http_response_code(404);
    exit;
}

$upstreamHost = $allowedRoutes[$prefix];
$subPath = (isset($m[2]) && $m[2] !== '') ? $m[2] : '/';
$forwardPath = '/' . $prefix . $subPath;
$upstreamUrl = 'https://' . $upstreamHost . $forwardPath . ($rawQuery !== '' ? '?' . $rawQuery : '');

/**
 * Resolve true visitor IP behind Hostinger CDN (hcdn) / reverse proxies.
 */
function sc_gtg_client_ip(): string
{
    $candidates = [
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
        $_SERVER['HTTP_TRUE_CLIENT_IP'] ?? '',
        $_SERVER['HTTP_X_REAL_IP'] ?? '',
        $_SERVER['HTTP_X_CLIENT_IP'] ?? '',
    ];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        foreach (explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']) as $part) {
            $candidates[] = trim($part);
        }
    }
    $candidates[] = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

    foreach ($candidates as $ip) {
        $ip = trim((string) $ip);
        if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $ip;
        }
    }
    foreach ($candidates as $ip) {
        $ip = trim((string) $ip);
        if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    return '';
}

/**
 * Resolve ISO 3166-1 alpha-2 country, subdivision, and optional geolocation from edge/LiteSpeed GeoIP.
 *
 * @return array{country:string, region:string, geo:string}
 */
function sc_gtg_client_geo(string $clientIp): array
{
    $countryKeys = [
        'HTTP_CF_IPCOUNTRY',
        'HTTP_X_HCDN_COUNTRY',
        'HTTP_X_COUNTRY_CODE',
        'HTTP_X_GEO_COUNTRY',
        'HTTP_X_VIEWER_COUNTRY',
        'HTTP_CLOUDFRONT_VIEWER_COUNTRY',
        'GEOIP_COUNTRY_CODE',
        'REDIRECT_GEOIP_COUNTRY_CODE',
        'MM_COUNTRY_CODE',
        'REDIRECT_MM_COUNTRY_CODE',
    ];
    $country = '';
    foreach ($countryKeys as $k) {
        $val = strtoupper(trim((string) ($_SERVER[$k] ?? '')));
        if (preg_match('/^[A-Z]{2}$/', $val) && !in_array($val, ['XX', 'T1', 'ZZ'], true)) {
            $country = $val;
            break;
        }
    }

    // Fallback to offline binary IPv4 country table if edge/LiteSpeed GeoIP env is absent
    if ($country === '' && $clientIp !== '' && is_file(dirname(__DIR__) . '/data/ip2country_v4.bin')) {
        $country = sc_gtg_lookup_ipv4_country($clientIp, dirname(__DIR__) . '/data/ip2country_v4.bin');
    }

    $regionKeys = [
        'HTTP_CF_REGION_CODE',
        'HTTP_X_HCDN_REGION',
        'HTTP_X_REGION_CODE',
        'HTTP_X_GEO_REGION',
        'GEOIP_REGION',
        'REDIRECT_GEOIP_REGION',
        'MM_REGION_CODE',
        'REDIRECT_MM_REGION_CODE',
    ];
    $region = '';
    foreach ($regionKeys as $k) {
        $val = strtoupper(trim((string) ($_SERVER[$k] ?? '')));
        if ($country !== '' && str_starts_with($val, $country . '-')) {
            $val = substr($val, 3);
        }
        if (preg_match('/^[A-Z0-9]{1,3}$/', $val) && $val !== '00') {
            $region = $val;
            break;
        }
    }

    $lat = trim((string) ($_SERVER['HTTP_CF_IPLATITUDE'] ?? $_SERVER['GEOIP_LATITUDE'] ?? $_SERVER['REDIRECT_GEOIP_LATITUDE'] ?? ''));
    $lon = trim((string) ($_SERVER['HTTP_CF_IPLONGITUDE'] ?? $_SERVER['GEOIP_LONGITUDE'] ?? $_SERVER['REDIRECT_GEOIP_LONGITUDE'] ?? ''));
    $city = trim((string) ($_SERVER['HTTP_CF_IPCITY'] ?? $_SERVER['GEOIP_CITY'] ?? $_SERVER['REDIRECT_GEOIP_CITY'] ?? ''));
    $geoParts = [];
    if ($lat !== '' && $lon !== '' && is_numeric($lat) && is_numeric($lon)) {
        $geoParts[] = 'latlong=' . $lat . ',' . $lon;
    }
    if ($city !== '' && preg_match('/^[\p{L}\p{N}\s._-]+$/u', $city)) {
        $geoParts[] = 'city=' . $city;
    }

    return [
        'country' => $country,
        'region'  => $region,
        'geo'     => implode(';', $geoParts),
    ];
}

/**
 * O(log N) binary search over packed 10-byte IPv4 range records: [uint32 start][uint32 end][char2 cc].
 */
function sc_gtg_lookup_ipv4_country(string $ip, string $binFile): string
{
    $long = ip2long($ip);
    if ($long === false) {
        return '';
    }
    $target = (int) sprintf('%u', $long);
    $fp = @fopen($binFile, 'rb');
    if (!$fp) {
        return '';
    }
    $size = (int) filesize($binFile);
    $count = intdiv($size, 10);
    $lo = 0;
    $hi = $count - 1;
    $found = '';
    while ($lo <= $hi) {
        $mid = ($lo + $hi) >> 1;
        if (fseek($fp, $mid * 10) !== 0) {
            break;
        }
        $rec = fread($fp, 10);
        if ($rec === false || strlen($rec) < 10) {
            break;
        }
        $u = unpack('Nstart/Nend/a2cc', $rec);
        $start = (int) sprintf('%u', $u['start']);
        $end = (int) sprintf('%u', $u['end']);
        if ($target < $start) {
            $hi = $mid - 1;
        } elseif ($target > $end) {
            $lo = $mid + 1;
        } else {
            $cc = strtoupper((string) $u['cc']);
            if (preg_match('/^[A-Z]{2}$/', $cc) && $cc !== 'ZZ') {
                $found = $cc;
            }
            break;
        }
    }
    fclose($fp);
    return $found;
}

$clientIp = sc_gtg_client_ip();
$geo = sc_gtg_client_geo($clientIp);

$reqHeaders = [
    'Host: ' . $upstreamHost,
];
if ($clientIp !== '') {
    $reqHeaders[] = 'X-Forwarded-For: ' . $clientIp;
}
if ($geo['country'] !== '') {
    $reqHeaders[] = 'X-Forwarded-Country: ' . $geo['country'];
    if ($geo['region'] !== '') {
        $reqHeaders[] = 'X-Forwarded-Region: ' . $geo['region'];
    }
}
if ($geo['geo'] !== '') {
    $reqHeaders[] = 'X-Forwarded-Geolocation: ' . $geo['geo'];
}

// Forward safe browser headers so Google Ads / GA4 device & language signals stay intact
$forwardHeaderMap = [
    'HTTP_USER_AGENT'                  => 'User-Agent',
    'HTTP_ACCEPT'                      => 'Accept',
    'HTTP_ACCEPT_LANGUAGE'             => 'Accept-Language',
    'HTTP_REFERER'                     => 'Referer',
    'HTTP_ORIGIN'                      => 'Origin',
    'HTTP_SEC_CH_UA'                   => 'Sec-CH-UA',
    'HTTP_SEC_CH_UA_MOBILE'            => 'Sec-CH-UA-Mobile',
    'HTTP_SEC_CH_UA_PLATFORM'          => 'Sec-CH-UA-Platform',
    'HTTP_SEC_CH_UA_PLATFORM_VERSION'  => 'Sec-CH-UA-Platform-Version',
    'HTTP_SEC_CH_UA_FULL_VERSION_LIST' => 'Sec-CH-UA-Full-Version-List',
    'HTTP_SEC_CH_UA_MODEL'             => 'Sec-CH-UA-Model',
    'HTTP_SEC_CH_UA_ARCH'              => 'Sec-CH-UA-Arch',
    'HTTP_SEC_CH_UA_BITNESS'           => 'Sec-CH-UA-Bitness',
];
foreach ($forwardHeaderMap as $srvKey => $hdrName) {
    if (!empty($_SERVER[$srvKey])) {
        $val = str_replace(["\r", "\n"], '', (string) $_SERVER[$srvKey]);
        if ($val !== '') {
            $reqHeaders[] = $hdrName . ': ' . $val;
        }
    }
}

// Strip internal Sea College cookies; forward only Google measurement cookies
if (!empty($_SERVER['HTTP_COOKIE'])) {
    $internalCookies = ['sc_sid', 'sc_attr', 'sc_consent', 'sc_currency'];
    $keptCookies = [];
    foreach (explode(';', (string) $_SERVER['HTTP_COOKIE']) as $pair) {
        $pair = trim($pair);
        if ($pair === '') {
            continue;
        }
        $eq = strpos($pair, '=');
        $cName = $eq === false ? $pair : trim(substr($pair, 0, $eq));
        if (!in_array($cName, $internalCookies, true)) {
            $keptCookies[] = str_replace(["\r", "\n"], '', $pair);
        }
    }
    if ($keptCookies !== []) {
        $reqHeaders[] = 'Cookie: ' . implode('; ', $keptCookies);
    }
}

$rawBody = '';
if ($method === 'POST') {
    $rawBody = (string) file_get_contents('php://input');
    $contentType = str_replace(["\r", "\n"], '', (string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? 'text/plain;charset=UTF-8'));
    if ($contentType !== '') {
        $reqHeaders[] = 'Content-Type: ' . $contentType;
    }
}

$respHeaders = [];
$ch = curl_init($upstreamUrl);
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST     => $method,
    CURLOPT_RETURNTRANSFER    => true,
    CURLOPT_FOLLOWLOCATION    => false,
    CURLOPT_ENCODING          => '', // Decompress upstream cleanly so LiteSpeed handles framing
    CURLOPT_HTTPHEADER        => $reqHeaders,
    CURLOPT_CONNECTTIMEOUT_MS => 1200,
    CURLOPT_TIMEOUT_MS        => 2500,
    CURLOPT_SSL_VERIFYPEER    => true,
    CURLOPT_SSL_VERIFYHOST    => 2,
    CURLOPT_HEADERFUNCTION    => static function ($curl, string $headerLine) use (&$respHeaders): int {
        $len = strlen($headerLine);
        $trimmed = trim($headerLine);
        if ($trimmed === '' || str_starts_with($trimmed, 'HTTP/')) {
            if (str_starts_with($trimmed, 'HTTP/')) {
                $respHeaders = []; // Reset on new HTTP status block
            }
            return $len;
        }
        $colon = strpos($trimmed, ':');
        if ($colon !== false) {
            $name = strtolower(trim(substr($trimmed, 0, $colon)));
            $val = trim(substr($trimmed, $colon + 1));
            $respHeaders[] = [$name, $val, substr($trimmed, 0, $colon)];
        }
        return $len;
    },
]);

if ($method === 'HEAD') {
    curl_setopt($ch, CURLOPT_NOBODY, true);
} elseif ($method === 'POST') {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $rawBody);
}

$body = curl_exec($ch);
$errno = curl_errno($ch);
$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($errno !== 0 || $status === 0 || $body === false) {
    http_response_code(in_array($method, ['GET', 'HEAD'], true) ? 204 : 202);
    header('Cache-Control: no-store');
    header('CDN-Cache-Control: no-store');
    exit;
}

http_response_code($status);

// Never allow shared CDN edge caching on personalized Google Tag Gateway responses
header('CDN-Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

$passHeaders = [
    'content-type',
    'cache-control',
    'expires',
    'last-modified',
    'pragma',
    'vary',
    'set-cookie',
    'location',
    'service-worker-allowed',
    'cross-origin-resource-policy',
    'access-control-allow-origin',
    'access-control-allow-credentials',
    'access-control-allow-headers',
    'access-control-allow-methods',
    'timing-allow-origin',
];

$hasCacheControl = false;
foreach ($respHeaders as [$lowerName, $val, $origName]) {
    if (!in_array($lowerName, $passHeaders, true)) {
        continue;
    }
    if ($lowerName === 'cache-control') {
        $hasCacheControl = true;
        // Enforce private/no-store so shared proxies never cache across visitors
        $val = preg_replace('/\bpublic\b/i', 'private', $val) ?? $val;
    }
    $replace = ($lowerName !== 'set-cookie');
    header($origName . ': ' . $val, $replace);
}

if (!$hasCacheControl) {
    header('Cache-Control: no-cache, no-store, must-revalidate');
}

if ($method !== 'HEAD') {
    echo $body;
}
