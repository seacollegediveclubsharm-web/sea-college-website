<?php
/**
 * CLI Sync Utility: Google Sheet Master EUR Prices -> Website (`storage/live_prices.json` + Live Webhook Push).
 *
 * Usage:
 *   php docs/sync_price_sheet.php            # Pull Google Sheet via `gsheets` CLI & save local storage/live_prices.json
 *   php docs/sync_price_sheet.php --push     # Also push signed snapshot to https://seacollegediveclub.com/handlers/price_sync_webhook.php
 *   php docs/sync_price_sheet.php --status   # Query live production sync health endpoint
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Forbidden\n");
}

define('SC_NO_SESSION', true);
require_once dirname(__DIR__) . '/config/site_config.php';
require_once dirname(__DIR__) . '/includes/live_prices.php';

$args   = $argv ?? [];
$doPush = in_array('--push', $args, true);
$statusOnly = in_array('--status', $args, true);
$webhookUrl = rtrim(SITE_URL, '/') . '/handlers/price_sync_webhook.php';

if ($statusOnly) {
    $ch = curl_init($webhookUrl . '?status=1');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "HTTP {$code}: " . ($resp ?: "(empty)\n");
    exit($code === 200 ? 0 : 1);
}

$sheetId = PRICE_SHEET_ID;
$cmd = sprintf(
    'gsheets readonly read %s %s --json 2>&1',
    escapeshellarg($sheetId),
    escapeshellarg('01_Master_Prices_EUR!A1:P120')
);

$out = shell_exec($cmd);
if (!is_string($out) || trim($out) === '') {
    fwrite(STDERR, "ERROR: Failed reading Google Sheet {$sheetId} via gsheets CLI.\n");
    exit(1);
}

$json = json_decode($out, true);
$grid = isset($json['values']) && is_array($json['values']) ? $json['values'] : (is_array($json) ? $json : null);
if (!is_array($grid) || count($grid) < 7) {
    fwrite(STDERR, "ERROR: Unexpected Google Sheet response: " . substr($out, 0, 240) . "\n");
    exit(1);
}

$row3 = $grid[2] ?? [];
$row4 = $grid[3] ?? [];

$rawPayload = [
    'sheet_id'   => $sheetId,
    'synced_at'  => gmdate('c'),
    'global'     => [
        'discount_pct'   => $row3[1] ?? 0,
        'activate'       => strtoupper(trim((string) ($row3[3] ?? 'AUTO'))),
        'include_addons' => strtoupper(trim((string) ($row3[5] ?? 'NO'))),
        'promo_label'    => trim((string) ($row3[7] ?? 'Special Offer')),
    ],
    'categories' => [
        'dive-packages' => $row4[2] ?? 0,
        'padi-courses'  => $row4[4] ?? 0,
        'offers'        => $row4[6] ?? 0,
        'snorkeling'    => $row4[8] ?? 0,
    ],
    'items'      => [],
];

for ($r = 6, $n = count($grid); $r < $n; $r++) {
    $row = $grid[$r];
    $id  = trim((string) ($row[0] ?? ''));
    if ($id === '' || !preg_match('/^[a-z0-9\-]{1,64}$/', $id)) {
        continue;
    }
    $rawPayload['items'][$id] = [
        'standard_eur'       => $row[7] ?? '',
        'activate_discount'  => strtoupper(trim((string) ($row[8] ?? 'AUTO'))),
        'discount_price_eur' => $row[9] ?? '',
        'discount_pct'       => $row[10] ?? '',
        'child_price_eur'    => $row[11] ?? '',
        'bundle_save_eur'    => $row[12] ?? '',
    ];
}

$normalized = sc_normalize_live_price_payload($rawPayload);
if (!sc_save_live_prices($normalized)) {
    fwrite(STDERR, "ERROR: Could not write local storage/live_prices.json\n");
    exit(1);
}

echo sprintf(
    "Local snapshot saved: %d items | Global Discount: %s%% (%s)\n",
    count($normalized['items']),
    (string) round(((float) $normalized['global']['discount_pct']) * 100, 2),
    $normalized['global']['activate']
);

if ($doPush) {
    $secret = sc_secret('PRICE_SYNC_SECRET', '');
    if ($secret === '') {
        fwrite(STDERR, "ERROR: PRICE_SYNC_SECRET missing in config/secrets.php\n");
        exit(1);
    }
    $body = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $ch = curl_init($webhookUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json; charset=utf-8',
            'X-SC-Price-Sync-Token: ' . $secret,
        ],
    ]);
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($code !== 200) {
        fwrite(STDERR, "ERROR: Live webhook push failed (HTTP {$code}): {$err} {$resp}\n");
        exit(1);
    }
    echo "Live production webhook synced (HTTP 200): " . trim((string) $resp) . "\n";
}
