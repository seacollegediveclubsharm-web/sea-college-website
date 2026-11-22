<?php
/**
 * Instant Google Sheet -> Website EUR Price & Discount Webhook Endpoint.
 *
 * - GET  /handlers/price_sync_webhook.php?status=1 -> Read-only sync health JSON (zero secrets)
 * - POST /handlers/price_sync_webhook.php          -> Authenticated atomic update of `storage/live_prices.json`
 *   Verified via `hash_equals(PRICE_SYNC_TOKEN_SHA256, hash('sha256', $token))`.
 */
declare(strict_types=1);

define('SC_NO_SESSION', true);
require_once dirname(__DIR__) . '/config/site_config.php';
require_once dirname(__DIR__) . '/includes/live_prices.php';

@header_remove('X-Powered-By');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'GET') {
    $live = sc_load_live_prices();
    $discountedCount = 0;
    if (is_array($live['items'] ?? null)) {
        // Count items with active discount in current catalog
        require_once dirname(__DIR__) . '/includes/i18n_data.php';
        $cat = require dirname(__DIR__) . '/data/catalog.php';
        foreach ($cat as $c) {
            foreach ($c['groups'] as $g) {
                foreach ($g['items'] as $it) {
                    if (!empty($it['regular_price']) && $it['regular_price'] > $it['price']) {
                        $discountedCount++;
                    }
                }
            }
        }
    }
    echo json_encode([
        'ok'                     => true,
        'sheet_id'               => PRICE_SHEET_ID,
        'has_live_snapshot'      => $live !== null,
        'synced_at'              => $live['synced_at'] ?? null,
        'items_tracked'          => is_array($live['items'] ?? null) ? count($live['items']) : 80,
        'global_discount_pct'    => isset($live['global']['discount_pct']) ? round(((float) $live['global']['discount_pct']) * 100, 2) : 0,
        'active_discounted_items'=> $discountedCount,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method Not Allowed']) . "\n";
    exit;
}

// Extract token from X-SC-Price-Sync-Token or Authorization: Bearer
$providedToken = trim((string) ($_SERVER['HTTP_X_SC_PRICE_SYNC_TOKEN'] ?? ''));
if ($providedToken === '' && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
    if (preg_match('/^Bearer\s+(.+)$/i', (string) $_SERVER['HTTP_AUTHORIZATION'], $m)) {
        $providedToken = trim($m[1]);
    }
}

$expectedSha = strtolower(trim((string) PRICE_SYNC_TOKEN_SHA256));
$directSecret = sc_secret('PRICE_SYNC_SECRET', '');
$authOk = false;

if ($providedToken !== '' && strlen($providedToken) >= 24) {
    if ($expectedSha !== '' && strlen($expectedSha) === 64 && hash_equals($expectedSha, hash('sha256', $providedToken))) {
        $authOk = true;
    } elseif ($directSecret !== '' && hash_equals($directSecret, $providedToken)) {
        $authOk = true;
    }
}

if (!$authOk) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden: invalid sync token']) . "\n";
    exit;
}

$rawBody = (string) file_get_contents('php://input');
if ($rawBody === '' || strlen($rawBody) > 524288) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid payload size']) . "\n";
    exit;
}

$decoded = json_decode($rawBody, true);
if (!is_array($decoded) || empty($decoded['items']) || !is_array($decoded['items'])) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Payload must include items object']) . "\n";
    exit;
}

$normalized = sc_normalize_live_price_payload($decoded);
if (count($normalized['items']) < 10) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Too few valid catalog items in payload']) . "\n";
    exit;
}

if (!sc_save_live_prices($normalized)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Failed writing live_prices.json']) . "\n";
    exit;
}

if (function_exists('opcache_invalidate')) {
    @opcache_invalidate(dirname(__DIR__) . '/data/catalog.php', true);
}
header('X-LiteSpeed-Purge: *');

echo json_encode([
    'ok'                  => true,
    'synced_at'           => $normalized['synced_at'],
    'items_synced'        => count($normalized['items']),
    'global_discount_pct' => round(((float) $normalized['global']['discount_pct']) * 100, 2),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
