<?php
/**
 * Lara — Meta Inbox & Unanswered Thread Backlog Manager (Facebook Messenger, Instagram DMs & Comments).
 *
 * Commands (CLI only):
 *   php docs/lara_meta_inbox.php status
 *   php docs/lara_meta_inbox.php conversations [messenger|instagram]
 *   php docs/lara_meta_inbox.php thread <conversation_id>
 *   php docs/lara_meta_inbox.php comments
 *   php docs/lara_meta_inbox.php sweep-unanswered [--send]
 *   php docs/lara_meta_inbox.php subscribe-webhooks
 *   php docs/lara_meta_inbox.php simulate "<sample_guest_message>"
 *   php docs/lara_meta_inbox.php reply-message <ig_or_psid_recipient_id> "<message_text>"
 *   php docs/lara_meta_inbox.php reply-comment <comment_id> "<reply_text>"
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: CLI only.');
}

require_once dirname(__DIR__) . '/config/site_config.php';
require_once dirname(__DIR__) . '/includes/lara_concierge_brain.php';

$cmd = strtolower(trim((string) ($argv[1] ?? 'status')));

if ($cmd === 'simulate') {
    $sample = trim((string) ($argv[2] ?? 'Hello, how much for diving tomorrow?'));
    $plan = lara_generate_reply($sample, 'messenger', 'Guest');
    echo "=== Lara Concierge Simulation ===\n";
    echo "Input        : {$sample}\n";
    echo "Should Reply : " . (!empty($plan['should_reply']) ? 'YES' : 'NO (SILENT — vendor/staff/closing/unrelated)') . "\n";
    echo "Lang         : {$plan['lang']} | Intent: {$plan['intent']} | Urgent: " . ($plan['urgent_human'] ? 'YES' : 'NO') . "\n";
    echo "---------------------------------\n";
    echo ($plan['reply'] !== '' ? $plan['reply'] : '[No auto-reply sent]') . "\n";
    exit(0);
}

if (META_ACCESS_TOKEN === '') {
    echo "META_ACCESS_TOKEN is empty in config/secrets.php.\n";
    exit(1);
}

/**
 * Call Meta Graph API.
 *
 * @return array{status:int, data:array<string,mixed>}
 */
function lara_graph(string $method, string $path, array $params, string $token, bool $jsonBody = false): array
{
    $url = 'https://graph.facebook.com/' . META_GRAPH_VERSION . '/' . ltrim($path, '/');
    $ch = curl_init();
    if (strtoupper($method) === 'GET') {
        $params['access_token'] = $token;
        $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
    } elseif ($jsonBody) {
        $url .= (str_contains($url, '?') ? '&' : '?') . 'access_token=' . rawurlencode($token);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    } else {
        $params['access_token'] = $token;
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    }
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 45,
    ]);
    $raw = (string) curl_exec($ch);
    $err = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $decoded = json_decode($raw, true);
    $data = is_array($decoded) ? $decoded : ['raw' => $raw];
    if ($code === 0 && $err !== '') {
        $data['error'] = ['message' => 'cURL timeout/network error: ' . $err];
    }
    return [
        'status' => $code,
        'data'   => $data,
    ];
}

// Exchange System User token for Page Access Token
$pgRes = lara_graph('GET', META_PAGE_ID, [
    'fields' => 'id,name,access_token,instagram_business_account{id,username}',
], META_ACCESS_TOKEN);

$pageToken = (string) ($pgRes['data']['access_token'] ?? '');
$igId      = (string) ($pgRes['data']['instagram_business_account']['id'] ?? META_IG_ID);
$igHandle  = (string) ($pgRes['data']['instagram_business_account']['username'] ?? '');

if ($pageToken === '') {
    echo "Could not retrieve Page Access Token for Page " . META_PAGE_ID . ": " . json_encode($pgRes['data']) . "\n";
    exit(1);
}

if ($cmd === 'status') {
    echo "=== Lara — Meta Inbox & Engagement Status ===\n";
    echo "Facebook Page : {$pgRes['data']['name']} (ID: " . META_PAGE_ID . ")\n";
    echo "Instagram     : @" . ($igHandle ?: 'not-linked') . " (ID: " . ($igId ?: 'none') . ")\n";
    echo "WhatsApp      : +" . WHATSAPP_NUMBER . " (Auto-Reply: " . (LARA_WHATSAPP_AUTOREPLY ? 'ENABLED' : 'OFF — Human WhatsApp Web protected') . ")\n";
    echo "AI Brain      : " . ((defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '') ? 'Gemini 2.5 Flash (multi-turn contextual)' : 'Concise single-greeting fallback (set GEMINI_API_KEY for full AI)') . "\n";
    echo "Webhook URL   : " . SITE_URL . "/webhooks/meta-inbox (Verify Token: " . META_WEBHOOK_VERIFY_TOKEN . ")\n";

    $fbConv = lara_graph('GET', META_PAGE_ID . '/conversations', ['platform' => 'messenger', 'limit' => 3], $pageToken);
    $igConv = lara_graph('GET', META_PAGE_ID . '/conversations', ['platform' => 'instagram', 'limit' => 3], $pageToken);
    echo "Messenger API : HTTP {$fbConv['status']} " . ($fbConv['status'] === 200 ? ('OK (' . count($fbConv['data']['data'] ?? []) . ' recent threads)') : ($fbConv['data']['error']['message'] ?? '')) . "\n";
    echo "Instagram DM  : HTTP {$igConv['status']} " . ($igConv['status'] === 200 ? ('OK (' . count($igConv['data']['data'] ?? []) . ' recent threads)') : ($igConv['data']['error']['message'] ?? '')) . "\n";
    exit(0);
}

if ($cmd === 'subscribe-webhooks') {
    echo "=== Subscribing Page & Instagram to Lara Real-Time Webhook ===\n";
    $desiredFields = ['messages', 'messaging_postbacks', 'feed', 'leadgen'];
    $pgSub = lara_graph('POST', META_PAGE_ID . '/subscribed_apps', [
        'subscribed_fields' => implode(',', $desiredFields),
    ], $pageToken);
    echo "Facebook Page (" . META_PAGE_ID . ") [all fields] : HTTP {$pgSub['status']} " . json_encode($pgSub['data']) . "\n";

    if ($pgSub['status'] !== 200) {
        $permitted = [];
        foreach ($desiredFields as $field) {
            $tryField = lara_graph('POST', META_PAGE_ID . '/subscribed_apps', [
                'subscribed_fields' => $field,
            ], $pageToken);
            if ($tryField['status'] === 200) {
                $permitted[] = $field;
            } else {
                echo "  -> Field '{$field}' skipped: " . ($tryField['data']['error']['message'] ?? "HTTP {$tryField['status']}") . "\n";
            }
        }
        if (!empty($permitted)) {
            $finalPgSub = lara_graph('POST', META_PAGE_ID . '/subscribed_apps', [
                'subscribed_fields' => implode(',', $permitted),
            ], $pageToken);
            echo "Facebook Page (" . META_PAGE_ID . ") [permitted: " . implode(',', $permitted) . "] : HTTP {$finalPgSub['status']} " . json_encode($finalPgSub['data']) . "\n";
        }
    }

    if ($igId !== '') {
        $igSub = lara_graph('POST', $igId . '/subscribed_apps', [
            'subscribed_fields' => 'messages,comments',
        ], $pageToken);
        if ($igSub['status'] !== 200) {
            $igSub = lara_graph('POST', $igId . '/subscribed_apps', [
                'subscribed_fields' => 'messages,comments',
            ], META_ACCESS_TOKEN);
        }
        echo "Instagram (" . $igId . ") : HTTP {$igSub['status']} " . json_encode($igSub['data']) . "\n";
    }

    if (META_WABA_ID !== '') {
        if (LARA_WHATSAPP_AUTOREPLY) {
            $waSub = lara_graph('POST', META_WABA_ID . '/subscribed_apps', [], META_ACCESS_TOKEN);
            echo "WhatsApp WABA (" . META_WABA_ID . ") [ENABLED] : HTTP {$waSub['status']} " . json_encode($waSub['data']) . "\n";
        } else {
            $waUnsub = lara_graph('DELETE', META_WABA_ID . '/subscribed_apps', [], META_ACCESS_TOKEN);
            echo "WhatsApp WABA (" . META_WABA_ID . ") [PROTECTED / OFF] : HTTP {$waUnsub['status']} " . json_encode($waUnsub['data']) . "\n";
        }
    }
    exit(0);
}

if ($cmd === 'sweep-unanswered') {
    $doSend = in_array('--send', $argv, true);
    echo "=== Lara — Scanning Messenger & Instagram DMs for Unanswered Guest Messages (" . ($doSend ? 'LIVE SEND MODE' : 'DRY-RUN PREVIEW') . ") ===\n";
    $totalUnanswered = 0;
    $totalSent = 0;

    foreach (['messenger', 'instagram'] as $platform) {
        $threads = [];
        $after = '';
        $blocked = false;
        for ($page = 0; $page < 5; $page++) {
            $params = [
                'platform' => $platform,
                'fields'   => 'id,updated_time,unread_count,participants,messages.limit(5){id,created_time,from,message}',
                'limit'    => 10,
            ];
            if ($after !== '') {
                $params['after'] = $after;
            }
            $res = lara_graph('GET', META_PAGE_ID . '/conversations', $params, $pageToken);
            if ($res['status'] !== 200) {
                if ($page === 0) {
                    echo "[{$platform}] Blocked (HTTP {$res['status']}): " . ($res['data']['error']['message'] ?? 'Missing permission') . "\n";
                    $blocked = true;
                }
                break;
            }
            $batch = $res['data']['data'] ?? [];
            foreach ($batch as $item) {
                $threads[] = $item;
            }
            $after = (string) ($res['data']['paging']['cursors']['after'] ?? '');
            if ($after === '' || count($batch) < 10) {
                break;
            }
        }

        if ($blocked) {
            continue;
        }
        echo "[{$platform}] Checked " . count($threads) . " conversation threads.\n";

        foreach ($threads as $th) {
            $msgs = $th['messages']['data'] ?? [];
            if (empty($msgs)) {
                continue;
            }
            // Meta returns newest message first at index 0
            $latest = $msgs[0];
            $fromId = (string) ($latest['from']['id'] ?? '');
            $fromName = (string) ($latest['from']['name'] ?? $latest['from']['username'] ?? '');
            if ($fromId === '' || $fromId === META_PAGE_ID || $fromId === $igId) {
                continue; // Already answered by Sea College Dive Club!
            }

            // Combine consecutive trailing guest messages so follow-ups like "??" keep the guest's actual question
            $guestChunks = [];
            foreach ($msgs as $m) {
                $mFromId = (string) ($m['from']['id'] ?? '');
                if ($mFromId === META_PAGE_ID || $mFromId === $igId) {
                    break;
                }
                $mTxt = trim((string) ($m['message'] ?? ''));
                if ($mTxt !== '') {
                    array_unshift($guestChunks, $mTxt);
                }
            }
            $guestText = !empty($guestChunks) ? implode(' ', $guestChunks) : '';
            if ($guestText === '') {
                continue;
            }
            $plan = lara_generate_reply($guestText, $platform, $fromName);
            if (empty($plan['should_reply'])) {
                continue; // Skip non-customer / thank-you / bot threads
            }

            $createdTs = strtotime((string) ($latest['created_time'] ?? '')) ?: 0;
            $ageHours = $createdTs > 0 ? round((time() - $createdTs) / 3600, 1) : 999;

            $totalUnanswered++;

            echo "\n  -> Unanswered [{$platform}] Thread {$th['id']} | Guest: {$fromName} ({$fromId}) | Age: {$ageHours}h\n";
            echo "     Guest said : " . mb_substr($guestText, 0, 180, 'UTF-8') . "\n";
            echo "     Lara ({$plan['lang']}/{$plan['intent']}): " . str_replace("\n", " / ", mb_substr($plan['reply'], 0, 180, 'UTF-8')) . "...\n";

            if ($doSend) {
                if ($ageHours <= 24.0) {
                    $sendRes = lara_graph('POST', META_PAGE_ID . '/messages', [
                        'recipient'      => ['id' => $fromId],
                        'messaging_type' => 'RESPONSE',
                        'message'        => ['text' => $plan['reply']],
                    ], $pageToken, true);
                } elseif ($ageHours <= 168.0) {
                    // 24h–7d window: use Meta's official HUMAN_AGENT message tag for delayed customer care replies
                    $sendRes = lara_graph('POST', META_PAGE_ID . '/messages', [
                        'recipient'      => ['id' => $fromId],
                        'messaging_type' => 'MESSAGE_TAG',
                        'tag'            => 'HUMAN_AGENT',
                        'message'        => ['text' => $plan['reply']],
                    ], $pageToken, true);
                } else {
                    echo "     [SKIPPED AUTO-SEND] Older than Meta's 7-day API window ({$ageHours}h) — reply manually in Meta Business Suite Inbox using Lara's text above.\n";
                    continue;
                }
                echo "     [SEND RESULT] HTTP {$sendRes['status']} " . json_encode($sendRes['data'], JSON_UNESCAPED_UNICODE) . "\n";
                if ($sendRes['status'] === 200) {
                    $totalSent++;
                }
            }
        }
    }

    echo "\n=== Summary: {$totalUnanswered} unanswered thread(s) found | {$totalSent} replied automatically ===\n";
    exit(0);
}

if ($cmd === 'conversations') {
    $platform = strtolower(trim((string) ($argv[2] ?? 'messenger')));
    $res = lara_graph('GET', META_PAGE_ID . '/conversations', [
        'platform' => $platform,
        'fields'   => 'id,updated_time,unread_count,snippet,participants,messages.limit(3){id,created_time,from,message}',
        'limit'    => 25,
    ], $pageToken);
    echo json_encode($res['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    exit($res['status'] === 200 ? 0 : 1);
}

if ($cmd === 'thread') {
    $threadId = trim((string) ($argv[2] ?? ''));
    if ($threadId === '') {
        exit("Usage: php docs/lara_meta_inbox.php thread <conversation_id>\n");
    }
    $res = lara_graph('GET', $threadId, [
        'fields' => 'id,updated_time,participants,messages.limit(25){id,created_time,from,message,attachments}',
    ], $pageToken);
    echo json_encode($res['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    exit($res['status'] === 200 ? 0 : 1);
}

if ($cmd === 'comments') {
    echo "=== Recent Facebook Feed Comments ===\n";
    $fbFeed = lara_graph('GET', META_PAGE_ID . '/feed', [
        'fields' => 'id,message,created_time,comments.limit(5){id,from,message,created_time}',
        'limit'  => 5,
    ], $pageToken);
    echo json_encode($fbFeed['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

    if ($igId !== '') {
        echo "=== Recent Instagram Media Comments ===\n";
        $igMedia = lara_graph('GET', $igId . '/media', [
            'fields' => 'id,caption,timestamp,permalink,comments_count,comments.limit(5){id,username,text,timestamp}',
            'limit'  => 5,
        ], META_ACCESS_TOKEN);
        echo json_encode($igMedia['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
    exit(0);
}

if ($cmd === 'reply-message') {
    $recipientId = trim((string) ($argv[2] ?? ''));
    $text        = trim((string) ($argv[3] ?? ''));
    if ($recipientId === '' || $text === '') {
        exit("Usage: php docs/lara_meta_inbox.php reply-message <recipient_id> \"<text>\"\n");
    }
    $res = lara_graph('POST', META_PAGE_ID . '/messages', [
        'recipient'      => ['id' => $recipientId],
        'messaging_type' => 'RESPONSE',
        'message'        => ['text' => $text],
    ], $pageToken, true);
    echo "HTTP {$res['status']} " . json_encode($res['data'], JSON_UNESCAPED_UNICODE) . "\n";
    exit($res['status'] === 200 ? 0 : 1);
}

if ($cmd === 'reply-comment') {
    $commentId = trim((string) ($argv[2] ?? ''));
    $text      = trim((string) ($argv[3] ?? ''));
    if ($commentId === '' || $text === '') {
        exit("Usage: php docs/lara_meta_inbox.php reply-comment <comment_id> \"<text>\"\n");
    }
    $res = lara_graph('POST', $commentId . '/comments', ['message' => $text], $pageToken);
    if ($res['status'] !== 200) {
        $res = lara_graph('POST', $commentId . '/replies', ['message' => $text], META_ACCESS_TOKEN);
    }
    echo "HTTP {$res['status']} " . json_encode($res['data'], JSON_UNESCAPED_UNICODE) . "\n";
    exit($res['status'] === 200 ? 0 : 1);
}

echo "Unknown command '{$cmd}'. Use: status | conversations | thread | comments | sweep-unanswered | subscribe-webhooks | simulate | reply-message | reply-comment\n";
exit(1);
