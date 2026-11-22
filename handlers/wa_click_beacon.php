<?php
/**
 * POST /handlers/wa_click_beacon.php
 * Stateless server-side attribution beacon for WhatsApp & phone click conversions.
 * - Zero session cookie (`SC_NO_SESSION`), preserving 100% CDN edge cache for GET pages.
 * - Enforces same-origin Origin/Referer verification + per-IP rate limiting (90/hr).
 * - Persists `storage/wa_clicks.csv` and forwards attributed / high-intent clicks (`GCLID`, `FBCLID`,
 *   UTM campaigns, Quick-Book, Calculator, Booking Form) to Google Sheets OCT CRM webhook (`2s` timeout).
 */
declare(strict_types=1);

define('SC_NO_SESSION', true);
require_once dirname(__DIR__) . '/includes/lead_lib.php';

header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

/* 1) Same-origin check via HTTP_ORIGIN or HTTP_REFERER */
$origin  = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
$referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
$srcHost = strtolower((string) parse_url($origin !== '' ? $origin : $referer, PHP_URL_HOST));
$reqHost = strtolower((string) preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
$allowedHosts = array_filter([
    $reqHost,
    'seacollegediveclub.com',
    'www.seacollegediveclub.com',
    strtolower((string) parse_url(SITE_URL, PHP_URL_HOST)),
]);
if ($srcHost === '' || !in_array($srcHost, $allowedHosts, true)) {
    http_response_code(403);
    exit;
}

/* 2) Stateless per-IP rate limit (1s min interval, 90/hr max) */
if (sc_ip_rate_limit_exceeded(1, 90)) {
    http_response_code(204);
    exit;
}

/* 3) Parse JSON body (sendBeacon / fetch) or standard POST fields */
$rawBody = (string) @file_get_contents('php://input');
$data = [];
if ($rawBody !== '') {
    $decoded = json_decode(substr($rawBody, 0, 8192), true);
    if (is_array($decoded)) {
        $data = $decoded;
    }
}
if (!$data && !empty($_POST)) {
    $data = $_POST;
}

$kind = clean_line($data['kind'] ?? 'whatsapp_click', 24);
if (!in_array($kind, ['whatsapp_click', 'phone_click'], true)) {
    $kind = 'whatsapp_click';
}

$placement = clean_line($data['placement'] ?? 'general', 60);
$pageUrl   = clean_line($data['page_url'] ?? $referer, 300);
$lang      = clean_line($data['lang'] ?? LANG, 8);
if (!isset(SUPPORTED_LANGS[$lang])) {
    $lang = LANG;
}
$itemId    = clean_line($data['item_id'] ?? '', 60);
$itemName  = clean_line($data['item_name'] ?? '', 180);
$rawVal    = $data['value'] ?? $data['estimated_eur'] ?? $data['estimated_total_eur'] ?? 0;
$value     = is_numeric($rawVal) ? round(max(0.0, min(50000.0, (float) $rawVal)), 2) : 0.0;
$date      = clean_line($data['date'] ?? '', 16);
$qty       = isset($data['qty']) ? max(0, min(60, (int) $data['qty'])) : 0;
$hotel     = clean_line($data['hotel'] ?? '', 140);

/* Merge posted attribution with server-side sc_attr cookie attribution */
$cookieAttr = attribution();
$attr = [];
foreach (['gclid', 'gbraid', 'wbraid', 'fbclid', 'ttclid', 'msclkid', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'] as $k) {
    $val = clean_line($data[$k] ?? '', 240);
    if ($val === '' && !empty($cookieAttr[$k]) && is_string($cookieAttr[$k])) {
        $val = clean_line($cookieAttr[$k], 240);
    }
    $attr[$k] = $val;
}
$fbc = sc_resolve_fbc($attr, clean_line($data['fbc'] ?? '', 300));
$fbp = sc_resolve_fbp($attr, clean_line($data['fbp'] ?? '', 300));

$eventId = 'wa_' . bin2hex(random_bytes(8));

$row = [
    'clicked_at'    => gmdate('c'),
    'event_id'      => $eventId,
    'kind'          => $kind,
    'placement'     => $placement,
    'lang'          => $lang,
    'item_id'       => $itemId,
    'item_name'     => $itemName,
    'value_eur'     => $value,
    'qty'           => $qty > 0 ? $qty : '',
    'date'          => $date,
    'hotel'         => $hotel,
    'page_url'      => $pageUrl,
    'gclid'         => $attr['gclid'],
    'gbraid'        => $attr['gbraid'],
    'wbraid'        => $attr['wbraid'],
    'fbclid'        => $attr['fbclid'],
    'ttclid'        => $attr['ttclid'],
    'msclkid'       => $attr['msclkid'],
    'utm_source'    => $attr['utm_source'],
    'utm_medium'    => $attr['utm_medium'],
    'utm_campaign'  => $attr['utm_campaign'],
    'utm_term'      => $attr['utm_term'],
    'ip_hash'       => hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . date('Y-m')),
    'utm_content'   => $attr['utm_content'],
    'fbc'           => $fbc,
    'fbp'           => $fbp,
    'estimated_eur' => $value,
];

store_csv('wa_clicks.csv', $row);
require_once dirname(__DIR__) . '/includes/erp_db.php';
sc_erp_ingest_wa_beacon($row);

/* 4) Forward attributed or high-intent booking WhatsApp clicks to OCT CRM Sheet (fast 2s timeout) */
$keyPlacements = [
    'item_quickbook', 'item_sticky_bar', 'item_sidebar', 'item_top_banner_wa',
    'booking_form', 'contact_form', 'form_wa',
    'dive_calculator', 'checkin_wa', 'guide_sidebar', 'thank_you',
];
$shouldSyncCrm = CRM_WEBHOOK_URL !== '' && (
    $attr['gclid'] !== ''
    || $attr['gbraid'] !== ''
    || $attr['wbraid'] !== ''
    || $attr['fbclid'] !== ''
    || $attr['ttclid'] !== ''
    || $attr['msclkid'] !== ''
    || $fbc !== ''
    || $itemId !== ''
    || $attr['utm_campaign'] !== ''
    || in_array($placement, $keyPlacements, true)
);

if ($shouldSyncCrm) {
    $crmPayload = [
        'type'                => 'whatsapp_click',
        'received_at'         => $row['clicked_at'],
        'event_id'            => $eventId,
        'kind'                => $kind,
        'placement'           => $placement,
        'lang'                => $lang,
        'interest'            => $itemName !== '' ? $itemName : ($placement . ' (' . $kind . ')'),
        'item_id'             => $itemId,
        'people'              => $qty > 0 ? $qty : 1,
        'date'                => $date,
        'hotel'               => $hotel,
        'estimated_eur'       => $value,
        'estimated_total_eur' => $value,
        'page_url'            => $pageUrl,
        'gclid'               => $attr['gclid'],
        'gbraid'              => $attr['gbraid'],
        'wbraid'              => $attr['wbraid'],
        'fbclid'              => $attr['fbclid'],
        'ttclid'              => $attr['ttclid'],
        'msclkid'             => $attr['msclkid'],
        'fbc'                 => $fbc,
        'fbp'                 => $fbp,
        'utm_source'          => $attr['utm_source'],
        'utm_medium'          => $attr['utm_medium'],
        'utm_campaign'        => $attr['utm_campaign'],
        'utm_term'            => $attr['utm_term'],
        'utm_content'         => $attr['utm_content'],
    ];
    post_json(CRM_WEBHOOK_URL, $crmPayload, 2);
}

http_response_code(204);
