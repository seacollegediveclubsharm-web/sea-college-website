<?php
/**
 * Lara — Meta Inbox & Social Engagement CLI Manager (Facebook Messenger, Instagram DMs & Comments).
 *
 * Uses the System User token (META_ACCESS_TOKEN in config/secrets.php) to derive the
 * Page Access Token for Page 114089131683616 (`sea college dive club`) + IG 17841459801345654 (`@seacollege.diveclub`).
 *
 * Usage (CLI only):
 *   php docs/lara_meta_inbox.php status
 *   php docs/lara_meta_inbox.php conversations [messenger|instagram]
 *   php docs/lara_meta_inbox.php thread <conversation_id>
 *   php docs/lara_meta_inbox.php comments
 *   php docs/lara_meta_inbox.php reply-message <ig_or_psid_recipient_id> "<message_text>"
 *   php docs/lara_meta_inbox.php reply-comment <comment_id> "<reply_text>"
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: CLI only.');
}

require_once dirname(__DIR__) . '/config/site_config.php';

$cmd = strtolower(trim((string) ($argv[1] ?? 'status')));
if (META_ACCESS_TOKEN === '') {
    echo "META_ACCESS_TOKEN is empty in config/secrets.php.\n";
    exit(1);
}

/**
 * Call Meta Graph API.
 *
 * @return array{status:int, data:array<string,mixed>}
 */
function lara_graph(string $method, string $path, array $params, string $token): array
{
    $url = 'https://graph.facebook.com/' . META_GRAPH_VERSION . '/' . ltrim($path, '/');
    $params['access_token'] = $token;
    $ch = curl_init();
    if (strtoupper($method) === 'GET') {
        $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
    } else {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    }
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
    ]);
    $raw = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $decoded = json_decode($raw, true);
    return [
        'status' => $code,
        'data'   => is_array($decoded) ? $decoded : ['raw' => $raw],
    ];
}

// Exchange System User token for Page Access Token
$pgRes = lara_graph('GET', META_PAGE_ID, [
    'fields' => 'id,name,access_token,instagram_business_account{id,username}',
], META_ACCESS_TOKEN);

$pageToken = (string) ($pgRes['data']['access_token'] ?? '');
$igId      = (string) ($pgRes['data']['instagram_business_account']['id'] ?? '');
$igHandle  = (string) ($pgRes['data']['instagram_business_account']['username'] ?? '');

if ($pageToken === '') {
    echo "Could not retrieve Page Access Token for Page " . META_PAGE_ID . ": " . json_encode($pgRes['data']) . "\n";
    exit(1);
}

if ($cmd === 'status') {
    echo "=== Lara — Meta Inbox & Engagement Status ===\n";
    echo "Facebook Page : {$pgRes['data']['name']} (ID: " . META_PAGE_ID . ")\n";
    echo "Instagram     : @" . ($igHandle ?: 'not-linked') . " (ID: " . ($igId ?: 'none') . ")\n";
    echo "WhatsApp      : +" . WHATSAPP_NUMBER . " (https://wa.me/" . WHATSAPP_NUMBER . ")\n";

    $fbConv = lara_graph('GET', META_PAGE_ID . '/conversations', ['platform' => 'messenger', 'limit' => 3], $pageToken);
    $igConv = lara_graph('GET', META_PAGE_ID . '/conversations', ['platform' => 'instagram', 'limit' => 3], $pageToken);
    echo "Messenger API : HTTP {$fbConv['status']} " . ($fbConv['status'] === 200 ? ('OK (' . count($fbConv['data']['data'] ?? []) . ' recent threads)') : ($fbConv['data']['error']['message'] ?? '')) . "\n";
    echo "Instagram DM  : HTTP {$igConv['status']} " . ($igConv['status'] === 200 ? ('OK (' . count($igConv['data']['data'] ?? []) . ' recent threads)') : ($igConv['data']['error']['message'] ?? '')) . "\n";
    exit(0);
}

if ($cmd === 'conversations') {
    $platform = strtolower(trim((string) ($argv[2] ?? 'messenger')));
    $res = lara_graph('GET', META_PAGE_ID . '/conversations', [
        'platform' => $platform,
        'fields'   => 'id,updated_time,unread_count,snippet,participants,messages.limit(3){id,created_time,from,message}',
        'limit'    => 15,
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
        'recipient'      => json_encode(['id' => $recipientId]),
        'messaging_type' => 'RESPONSE',
        'message'        => json_encode(['text' => $text]),
    ], $pageToken);
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

echo "Unknown command '{$cmd}'. Use: status | conversations | thread | comments | reply-message | reply-comment\n";
exit(1);
