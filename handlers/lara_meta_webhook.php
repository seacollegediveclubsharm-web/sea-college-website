<?php
/**
 * Lara — Real-Time 24/7 Meta Inbox Auto-Responder Webhook (`https://seacollegediveclub.com/webhooks/meta-inbox`).
 *
 * Automatically responds within seconds to incoming customer messages across:
 *   1. Facebook Page Messenger (`object === "page"`)
 *   2. Instagram Direct Messages (`object === "instagram"`)
 *   3. WhatsApp Business Cloud API (`object === "whatsapp_business_account"`)
 *
 * Safety & Loop Prevention:
 *   - Ignores `is_echo`, Page/IG self-messages, delivery/read receipts, and reactions.
 *   - Deduplicates message IDs (`mid` / `wamid`) + enforces per-sender 24h cap (`storage/lara_inbox_seen.json`).
 *   - Logs every handled thread to `storage/lara_inbox_log.jsonl` and forwards lead context to CRM Sheet webhook.
 */
declare(strict_types=1);

define('SC_NO_SESSION', true);
require_once dirname(__DIR__) . '/config/site_config.php';
require_once dirname(__DIR__) . '/includes/lara_concierge_brain.php';

@header_remove('X-Powered-By');
header('Cache-Control: no-store, no-cache, must-revalidate');

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$storageDir = dirname(__DIR__) . '/storage';
if (!is_dir($storageDir)) {
    @mkdir($storageDir, 0775, true);
}
$logFile  = $storageDir . '/lara_inbox_log.jsonl';
$seenFile = $storageDir . '/lara_inbox_seen.json';

// 1) Meta Webhook Verification Challenge (GET ?hub.mode=subscribe&hub.verify_token=...&hub.challenge=...)
if ($method === 'GET') {
    $hubMode      = (string) ($_GET['hub_mode'] ?? $_GET['hub.mode'] ?? '');
    $hubToken     = (string) ($_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '');
    $hubChallenge = (string) ($_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '');

    if ($hubMode === 'subscribe') {
        if ($hubToken !== '' && hash_equals(META_WEBHOOK_VERIFY_TOKEN, $hubToken)) {
            header('Content-Type: text/plain; charset=utf-8');
            http_response_code(200);
            echo $hubChallenge;
            exit;
        }
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Invalid verify token']);
        exit;
    }

    // Read-only health status check (zero secrets exposed)
    header('Content-Type: application/json; charset=utf-8');
    $logCount = is_file($logFile) ? count((array) @file($logFile, FILE_SKIP_EMPTY_LINES)) : 0;
    echo json_encode([
        'ok'             => true,
        'agent'          => 'Lara — Head of Customer Care & Meta Inbox Concierge',
        'webhook_url'    => SITE_URL . '/webhooks/meta-inbox',
        'channels'       => ['messenger', 'instagram', 'whatsapp_cloud'],
        'page_id'        => META_PAGE_ID,
        'ig_id'          => META_IG_ID,
        'waba_phone_id'  => META_WABA_PHONE_ID,
        'token_present'  => META_ACCESS_TOKEN !== '',
        'handled_events' => $logCount,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    exit;
}

$rawBody = (string) file_get_contents('php://input');

// Optional HMAC-SHA256 verification if META_APP_SECRET is configured in config/secrets.php
if (META_APP_SECRET !== '') {
    $sigHeader = (string) ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '');
    $expected  = 'sha256=' . hash_hmac('sha256', $rawBody, META_APP_SECRET);
    if ($sigHeader === '' || !hash_equals($expected, $sigHeader)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Invalid signature']);
        exit;
    }
}

$payload = json_decode($rawBody, true);
if (!is_array($payload) || empty($payload['object'])) {
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'ignored' => true]);
    exit;
}

/**
 * Helper: Graph API request.
 *
 * @return array{status:int,data:array<string,mixed>}
 */
function lara_wh_graph(string $httpMethod, string $path, array $params, string $token, bool $jsonBody = false): array
{
    $url = 'https://graph.facebook.com/' . META_GRAPH_VERSION . '/' . ltrim($path, '/');
    $ch = curl_init();
    if (strtoupper($httpMethod) === 'GET') {
        $params['access_token'] = $token;
        $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
    } elseif ($jsonBody) {
        $url .= (str_contains($url, '?') ? '&' : '?') . 'access_token=' . rawurlencode($token);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($httpMethod));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    } else {
        $params['access_token'] = $token;
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($httpMethod));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    }
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 12,
    ]);
    $raw = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $dec = json_decode($raw, true);
    return ['status' => $code, 'data' => is_array($dec) ? $dec : ['raw' => $raw]];
}

// Load dedupe & rate-limit state (purge entries older than 24h)
$now = time();
$seen = ['mids' => [], 'senders' => []];
if (is_file($seenFile)) {
    $decSeen = json_decode((string) @file_get_contents($seenFile), true);
    if (is_array($decSeen)) {
        $seen['mids'] = is_array($decSeen['mids'] ?? null) ? $decSeen['mids'] : [];
        $seen['senders'] = is_array($decSeen['senders'] ?? null) ? $decSeen['senders'] : [];
    }
}
foreach ($seen['mids'] as $k => $ts) {
    if (($now - (int) $ts) > 86400) {
        unset($seen['mids'][$k]);
    }
}
foreach ($seen['senders'] as $sId => $info) {
    if (($now - (int) ($info['window_start'] ?? 0)) > 86400) {
        unset($seen['senders'][$sId]);
    }
}

$pageTokenCache = null;
$getPageToken = static function () use (&$pageTokenCache): string {
    if ($pageTokenCache !== null) {
        return $pageTokenCache;
    }
    if (META_ACCESS_TOKEN === '') {
        return $pageTokenCache = '';
    }
    $pg = lara_wh_graph('GET', META_PAGE_ID, ['fields' => 'access_token'], META_ACCESS_TOKEN);
    return $pageTokenCache = (string) ($pg['data']['access_token'] ?? META_ACCESS_TOKEN);
};

$object = strtolower((string) $payload['object']);
$replied = 0;

foreach (($payload['entry'] ?? []) as $entry) {
    // A) Facebook Messenger & Instagram Direct Messages
    if (in_array($object, ['page', 'instagram'], true) && !empty($entry['messaging']) && is_array($entry['messaging'])) {
        foreach ($entry['messaging'] as $ev) {
            if (!empty($ev['message']['is_echo']) || isset($ev['delivery']) || isset($ev['read']) || isset($ev['reaction'])) {
                continue;
            }
            $senderId = (string) ($ev['sender']['id'] ?? '');
            if ($senderId === '' || $senderId === META_PAGE_ID || $senderId === META_IG_ID) {
                continue;
            }
            $mid = (string) ($ev['message']['mid'] ?? '');
            $text = trim((string) ($ev['message']['text'] ?? ($ev['postback']['title'] ?? '')));
            if ($text === '' && !empty($ev['message']['attachments'])) {
                $text = 'Hello';
            }
            if ($text === '') {
                continue;
            }
            if ($mid !== '' && isset($seen['mids'][$mid])) {
                continue;
            }
            $sInfo = $seen['senders'][$senderId] ?? ['window_start' => $now, 'count' => 0, 'last_ts' => 0];
            if ((int) $sInfo['count'] >= 6 || ($now - (int) $sInfo['last_ts']) < 15) {
                continue;
            }

            $plan = lara_generate_reply($text, $object === 'instagram' ? 'instagram' : 'messenger');
            $pTok = $getPageToken();
            $sendStatus = 0;
            if ($pTok !== '') {
                $sendRes = lara_wh_graph('POST', META_PAGE_ID . '/messages', [
                    'recipient'      => ['id' => $senderId],
                    'messaging_type' => 'RESPONSE',
                    'message'        => ['text' => $plan['reply']],
                ], $pTok, true);
                $sendStatus = $sendRes['status'];
            }

            if ($mid !== '') {
                $seen['mids'][$mid] = $now;
            }
            $seen['senders'][$senderId] = [
                'window_start' => (int) $sInfo['window_start'],
                'count'        => (int) $sInfo['count'] + 1,
                'last_ts'      => $now,
            ];
            $replied++;

            @file_put_contents($logFile, json_encode([
                'ts'           => gmdate('c'),
                'channel'      => $object === 'instagram' ? 'instagram_dm' : 'facebook_messenger',
                'sender_id'    => $senderId,
                'mid'          => $mid,
                'lang'         => $plan['lang'],
                'intent'       => $plan['intent'],
                'urgent_human' => $plan['urgent_human'],
                'guest_text'   => mb_substr($text, 0, 500, 'UTF-8'),
                'http_status'  => $sendStatus,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);

            // Sync new Meta Inbox inquiry to Leads CRM Sheet if first reply in 24h window
            if ((int) $sInfo['count'] === 0 && defined('GOOGLE_SHEETS_WEBHOOK_URL') && GOOGLE_SHEETS_WEBHOOK_URL !== '') {
                $crmCh = curl_init(GOOGLE_SHEETS_WEBHOOK_URL);
                curl_setopt_array($crmCh, [
                    CURLOPT_POST           => true,
                    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                    CURLOPT_POSTFIELDS     => json_encode([
                        'type'                => 'lead',
                        'received_at'         => gmdate('c'),
                        'event_id'            => 'lara_' . substr(sha1($senderId . '|' . $now), 0, 14),
                        'name'                => ($object === 'instagram' ? 'IG Guest #' : 'FB Guest #') . substr($senderId, -6),
                        'phone'               => '',
                        'email'               => '',
                        'interest'            => ($plan['urgent_human'] ? '[URGENT_HUMAN] ' : '') . 'Meta Inbox (' . $plan['intent'] . '): ' . mb_substr($text, 0, 120, 'UTF-8'),
                        'people'              => 1,
                        'lang'                => $plan['lang'],
                        'estimated_total_eur' => 0,
                        'utm_source'          => $object === 'instagram' ? 'instagram' : 'facebook',
                        'utm_medium'          => 'meta_inbox_lara',
                        'channel_source'      => 'Meta Ads',
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT        => 5,
                ]);
                @curl_exec($crmCh);
                curl_close($crmCh);
            }
        }
    }

    // B) WhatsApp Business Cloud API (`whatsapp_business_account`)
    if ($object === 'whatsapp_business_account' && !empty($entry['changes']) && is_array($entry['changes'])) {
        foreach ($entry['changes'] as $chg) {
            $val = $chg['value'] ?? [];
            $phoneId = (string) ($val['metadata']['phone_number_id'] ?? META_WABA_PHONE_ID);
            $contacts = $val['contacts'] ?? [];
            $guestName = (string) ($contacts[0]['profile']['name'] ?? '');
            foreach (($val['messages'] ?? []) as $msg) {
                $from = (string) ($msg['from'] ?? '');
                $wamid = (string) ($msg['id'] ?? '');
                $mType = (string) ($msg['type'] ?? 'text');
                $text = '';
                if ($mType === 'text') {
                    $text = trim((string) ($msg['text']['body'] ?? ''));
                } elseif ($mType === 'button') {
                    $text = trim((string) ($msg['button']['text'] ?? ''));
                } elseif ($mType === 'interactive') {
                    $text = trim((string) ($msg['interactive']['button_reply']['title'] ?? $msg['interactive']['list_reply']['title'] ?? ''));
                }
                if ($from === '' || $text === '') {
                    continue;
                }
                if ($wamid !== '' && isset($seen['mids'][$wamid])) {
                    continue;
                }
                $sInfo = $seen['senders'][$from] ?? ['window_start' => $now, 'count' => 0, 'last_ts' => 0];
                if ((int) $sInfo['count'] >= 6 || ($now - (int) $sInfo['last_ts']) < 15) {
                    continue;
                }

                $plan = lara_generate_reply($text, 'whatsapp', $guestName);
                $sendStatus = 0;
                if (META_ACCESS_TOKEN !== '' && $phoneId !== '') {
                    $sendRes = lara_wh_graph('POST', $phoneId . '/messages', [
                        'messaging_product' => 'whatsapp',
                        'to'                => $from,
                        'type'              => 'text',
                        'text'              => ['preview_url' => true, 'body' => $plan['reply']],
                    ], META_ACCESS_TOKEN, true);
                    $sendStatus = $sendRes['status'];
                }

                if ($wamid !== '') {
                    $seen['mids'][$wamid] = $now;
                }
                $seen['senders'][$from] = [
                    'window_start' => (int) $sInfo['window_start'],
                    'count'        => (int) $sInfo['count'] + 1,
                    'last_ts'      => $now,
                ];
                $replied++;

                @file_put_contents($logFile, json_encode([
                    'ts'           => gmdate('c'),
                    'channel'      => 'whatsapp_cloud',
                    'sender_id'    => $from,
                    'mid'          => $wamid,
                    'lang'         => $plan['lang'],
                    'intent'       => $plan['intent'],
                    'urgent_human' => $plan['urgent_human'],
                    'guest_text'   => mb_substr($text, 0, 500, 'UTF-8'),
                    'http_status'  => $sendStatus,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);
            }
        }
    }
}

@file_put_contents($seenFile, json_encode($seen, JSON_UNESCAPED_SLASHES));

header('Content-Type: application/json; charset=utf-8');
http_response_code(200);
echo json_encode(['ok' => true, 'replied' => $replied]);
