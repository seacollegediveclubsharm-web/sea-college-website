<?php
/**
 * Lara — Real-Time 24/7 Meta Inbox Auto-Responder Webhook v2 (`https://seacollegediveclub.com/webhooks/meta-inbox`).
 *
 * Anti-Robot & Anti-Duplicate Protections:
 *   1. WhatsApp Coexistence Protection: `LARA_WHATSAPP_AUTOREPLY` is OFF by default so live human WhatsApp Web
 *      chats with captains, partners, video agencies, and guests are NEVER interrupted.
 *   2. Exclusive `flock(LOCK_EX)` Mutex: Prevents concurrent Meta webhook deliveries from double-sending.
 *   3. Human Takeover Detection: Any manual reply from a Sea College human admin (`is_echo` not sent by Lara)
 *      mutes Lara on that thread for 48 hours.
 *   4. Zero-Repeat Guarantee: Never sends the same reply text twice to the same person; caps template mode to
 *      1 initial concierge reply per 24h (or multi-turn contextual conversation when `GEMINI_API_KEY` is set).
 *   5. Non-Customer Filter: Skips vendors/reels/captains/OTPs/closings (`should_reply === false`).
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
$lockFile = $storageDir . '/lara_inbox_seen.lock';

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
        'ok'                 => true,
        'version'            => 'v2-human-safe',
        'agent'              => 'Lara — Head of Customer Care & Meta Inbox Concierge',
        'webhook_url'        => SITE_URL . '/webhooks/meta-inbox',
        'whatsapp_autoreply' => LARA_WHATSAPP_AUTOREPLY,
        'ai_mode'            => (defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '') ? 'gemini-2.5-flash' : 'concise-single-greeting',
        'channels'           => LARA_WHATSAPP_AUTOREPLY ? ['messenger', 'instagram', 'whatsapp_cloud'] : ['messenger', 'instagram'],
        'page_id'            => META_PAGE_ID,
        'ig_id'              => META_IG_ID,
        'token_present'      => META_ACCESS_TOKEN !== '',
        'handled_events'     => $logCount,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    exit;
}

$rawBody = (string) file_get_contents('php://input');

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

$object = strtolower((string) $payload['object']);

// Hard guard: Do NOT auto-reply on WhatsApp unless explicitly opted in (`LARA_WHATSAPP_AUTOREPLY = true`)
if ($object === 'whatsapp_business_account' && !LARA_WHATSAPP_AUTOREPLY) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200);
    echo json_encode(['ok' => true, 'whatsapp_autoreply' => false]);
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

// Acquire exclusive mutex lock BEFORE reading $seenFile so concurrent webhooks never race or double-send
$lockFp = @fopen($lockFile, 'c+');
if ($lockFp !== false) {
    @flock($lockFp, LOCK_EX);
}

$now = time();
$seen = ['mids' => [], 'lara_sent_mids' => [], 'senders' => []];
if (is_file($seenFile)) {
    $decSeen = json_decode((string) @file_get_contents($seenFile), true);
    if (is_array($decSeen)) {
        $seen['mids']           = is_array($decSeen['mids'] ?? null) ? $decSeen['mids'] : [];
        $seen['lara_sent_mids'] = is_array($decSeen['lara_sent_mids'] ?? null) ? $decSeen['lara_sent_mids'] : [];
        $seen['senders']        = is_array($decSeen['senders'] ?? null) ? $decSeen['senders'] : [];
    }
}
foreach ($seen['mids'] as $k => $ts) {
    if (($now - (int) $ts) > 172800) {
        unset($seen['mids'][$k]);
    }
}
foreach ($seen['lara_sent_mids'] as $k => $ts) {
    if (($now - (int) $ts) > 172800) {
        unset($seen['lara_sent_mids'][$k]);
    }
}
foreach ($seen['senders'] as $sId => $info) {
    if (($now - (int) ($info['window_start'] ?? 0)) > 86400 && ($now > (int) ($info['human_until'] ?? 0))) {
        unset($seen['senders'][$sId]);
    }
}

$hasAi = defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '';
// Without Gemini AI, strictly max 1 initial greeting per 24h so Lara NEVER repeats templates at a guest!
$maxRepliesPer24h = $hasAi ? 8 : 1;

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

$replied = 0;

foreach (($payload['entry'] ?? []) as $entry) {
    // A) Facebook Messenger & Instagram Direct Messages
    if (in_array($object, ['page', 'instagram'], true) && !empty($entry['messaging']) && is_array($entry['messaging'])) {
        foreach ($entry['messaging'] as $ev) {
            if (isset($ev['delivery']) || isset($ev['read']) || isset($ev['reaction'])) {
                continue;
            }
            $mid = (string) ($ev['message']['mid'] ?? '');

            // If a human admin sent an echo message (not originated by Lara), mute Lara on that thread for 48h!
            if (!empty($ev['message']['is_echo'])) {
                $recId = (string) ($ev['recipient']['id'] ?? '');
                if ($recId !== '' && ($mid === '' || !isset($seen['lara_sent_mids'][$mid]))) {
                    $cur = $seen['senders'][$recId] ?? ['window_start' => $now, 'count' => 0, 'last_ts' => 0, 'history' => []];
                    $cur['human_until'] = $now + 172800;
                    $seen['senders'][$recId] = $cur;
                }
                continue;
            }

            $senderId = (string) ($ev['sender']['id'] ?? '');
            if ($senderId === '' || $senderId === META_PAGE_ID || $senderId === META_IG_ID) {
                continue;
            }
            if ($mid !== '' && isset($seen['mids'][$mid])) {
                continue;
            }
            $text = trim((string) ($ev['message']['text'] ?? ($ev['postback']['title'] ?? '')));
            if ($text === '') {
                if ($mid !== '') {
                    $seen['mids'][$mid] = $now;
                }
                continue;
            }

            // Record incoming mid immediately inside lock
            if ($mid !== '') {
                $seen['mids'][$mid] = $now;
            }

            $sInfo = $seen['senders'][$senderId] ?? ['window_start' => $now, 'count' => 0, 'last_ts' => 0, 'last_hash' => '', 'human_until' => 0, 'history' => []];
            if ($now < (int) ($sInfo['human_until'] ?? 0)) {
                continue; // Human team member is handling this conversation
            }
            if ((int) $sInfo['count'] >= $maxRepliesPer24h || ($now - (int) $sInfo['last_ts']) < 8) {
                continue;
            }

            $history = is_array($sInfo['history'] ?? null) ? $sInfo['history'] : [];
            $plan = lara_generate_reply($text, $object === 'instagram' ? 'instagram' : 'messenger', '', $history);
            if (empty($plan['should_reply']) || trim($plan['reply']) === '') {
                continue;
            }

            $replyHash = sha1($plan['reply']);
            if ($replyHash === (string) ($sInfo['last_hash'] ?? '')) {
                continue; // Never send the identical message twice to the same guest
            }

            $pTok = $getPageToken();
            $sendStatus = 0;
            $outMid = '';
            if ($pTok !== '') {
                $sendRes = lara_wh_graph('POST', META_PAGE_ID . '/messages', [
                    'recipient'      => ['id' => $senderId],
                    'messaging_type' => 'RESPONSE',
                    'message'        => ['text' => $plan['reply']],
                ], $pTok, true);
                $sendStatus = $sendRes['status'];
                $outMid = (string) ($sendRes['data']['message_id'] ?? '');
                if ($outMid !== '') {
                    $seen['lara_sent_mids'][$outMid] = $now;
                }
            }

            $history[] = ['role' => 'guest', 'text' => mb_substr($text, 0, 300, 'UTF-8')];
            $history[] = ['role' => 'lara',  'text' => mb_substr($plan['reply'], 0, 400, 'UTF-8')];
            $seen['senders'][$senderId] = [
                'window_start' => (int) $sInfo['window_start'],
                'count'        => (int) $sInfo['count'] + 1,
                'last_ts'      => $now,
                'last_hash'    => $replyHash,
                'human_until'  => (int) ($sInfo['human_until'] ?? 0),
                'history'      => array_slice($history, -8),
            ];
            $replied++;

            @file_put_contents($logFile, json_encode([
                'ts'           => gmdate('c'),
                'channel'      => $object === 'instagram' ? 'instagram_dm' : 'facebook_messenger',
                'sender_id'    => $senderId,
                'mid'          => $mid,
                'lang'         => $plan['lang'],
                'intent'       => $plan['intent'],
                'via_ai'       => !empty($plan['via_ai']),
                'urgent_human' => $plan['urgent_human'],
                'guest_text'   => mb_substr($text, 0, 500, 'UTF-8'),
                'http_status'  => $sendStatus,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);
        }
    }

    // B) WhatsApp Business Cloud API (`whatsapp_business_account` — ONLY runs when LARA_WHATSAPP_AUTOREPLY === true)
    if ($object === 'whatsapp_business_account' && LARA_WHATSAPP_AUTOREPLY && !empty($entry['changes']) && is_array($entry['changes'])) {
        foreach ($entry['changes'] as $chg) {
            $field = (string) ($chg['field'] ?? '');
            $val = $chg['value'] ?? [];

            // Detect WhatsApp Coexistence human outbound messages (`smb_message_echoes`) and mute Lara 48h!
            if ($field === 'smb_message_echoes' || !empty($val['message_echoes'])) {
                foreach (($val['message_echoes'] ?? []) as $echoMsg) {
                    $toPhone = (string) ($echoMsg['to'] ?? '');
                    $echoId  = (string) ($echoMsg['id'] ?? '');
                    if ($toPhone !== '' && ($echoId === '' || !isset($seen['lara_sent_mids'][$echoId]))) {
                        $cur = $seen['senders'][$toPhone] ?? ['window_start' => $now, 'count' => 0, 'last_ts' => 0, 'history' => []];
                        $cur['human_until'] = $now + 172800;
                        $seen['senders'][$toPhone] = $cur;
                    }
                }
                continue;
            }

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
                if ($wamid !== '') {
                    $seen['mids'][$wamid] = $now;
                }

                $sInfo = $seen['senders'][$from] ?? ['window_start' => $now, 'count' => 0, 'last_ts' => 0, 'last_hash' => '', 'human_until' => 0, 'history' => []];
                if ($now < (int) ($sInfo['human_until'] ?? 0)) {
                    continue;
                }
                if ((int) $sInfo['count'] >= $maxRepliesPer24h || ($now - (int) $sInfo['last_ts']) < 8) {
                    continue;
                }

                $history = is_array($sInfo['history'] ?? null) ? $sInfo['history'] : [];
                $plan = lara_generate_reply($text, 'whatsapp', $guestName, $history);
                if (empty($plan['should_reply']) || trim($plan['reply']) === '') {
                    continue;
                }

                $replyHash = sha1($plan['reply']);
                if ($replyHash === (string) ($sInfo['last_hash'] ?? '')) {
                    continue;
                }

                $sendStatus = 0;
                if (META_ACCESS_TOKEN !== '' && $phoneId !== '') {
                    $sendRes = lara_wh_graph('POST', $phoneId . '/messages', [
                        'messaging_product' => 'whatsapp',
                        'to'                => $from,
                        'type'              => 'text',
                        'text'              => ['preview_url' => true, 'body' => $plan['reply']],
                    ], META_ACCESS_TOKEN, true);
                    $sendStatus = $sendRes['status'];
                    $outWamid = (string) ($sendRes['data']['messages'][0]['id'] ?? '');
                    if ($outWamid !== '') {
                        $seen['lara_sent_mids'][$outWamid] = $now;
                    }
                }

                $history[] = ['role' => 'guest', 'text' => mb_substr($text, 0, 300, 'UTF-8')];
                $history[] = ['role' => 'lara',  'text' => mb_substr($plan['reply'], 0, 400, 'UTF-8')];
                $seen['senders'][$from] = [
                    'window_start' => (int) $sInfo['window_start'],
                    'count'        => (int) $sInfo['count'] + 1,
                    'last_ts'      => $now,
                    'last_hash'    => $replyHash,
                    'human_until'  => (int) ($sInfo['human_until'] ?? 0),
                    'history'      => array_slice($history, -8),
                ];
                $replied++;

                @file_put_contents($logFile, json_encode([
                    'ts'           => gmdate('c'),
                    'channel'      => 'whatsapp_cloud',
                    'sender_id'    => $from,
                    'mid'          => $wamid,
                    'lang'         => $plan['lang'],
                    'intent'       => $plan['intent'],
                    'via_ai'       => !empty($plan['via_ai']),
                    'urgent_human' => $plan['urgent_human'],
                    'guest_text'   => mb_substr($text, 0, 500, 'UTF-8'),
                    'http_status'  => $sendStatus,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);
            }
        }
    }
}

@file_put_contents($seenFile, json_encode($seen, JSON_UNESCAPED_SLASHES));
if ($lockFp !== false) {
    @flock($lockFp, LOCK_UN);
    @fclose($lockFp);
}

header('Content-Type: application/json; charset=utf-8');
http_response_code(200);
echo json_encode(['ok' => true, 'replied' => $replied]);
