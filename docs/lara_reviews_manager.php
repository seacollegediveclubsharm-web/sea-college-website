<?php
/**
 * Lara & Hossam — Reviews Manager CLI (Google Business Profile + Tripadvisor #26104236).
 *
 * Usage:
 *   php docs/lara_reviews_manager.php status
 *   php docs/lara_reviews_manager.php oauth-url
 *   php docs/lara_reviews_manager.php exchange-code "<code_from_google>"
 *   php docs/lara_reviews_manager.php sweep-gbp [--send]
 *   php docs/lara_reviews_manager.php simulate "<author>" <rating> "<review_text>"
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: CLI only.');
}

require_once dirname(__DIR__) . '/includes/lara_reviews_concierge.php';

$cmd = strtolower(trim((string) ($argv[1] ?? 'status')));

if ($cmd === 'status') {
    echo "=== Lara & Hossam — Reviews Reputation Manager ===\n";
    echo "Google Business Profile : Place ID " . GOOGLE_PLACE_ID . " | Location ID " . GBP_LOCATION_ID . " (4.9★ · 3,082+ reviews)\n";
    echo "GBP Direct API OAuth    : " . ((GBP_CLIENT_ID !== '' && GBP_REFRESH_TOKEN !== '') ? 'CONNECTED (24/7 Auto-Reply Ready)' : 'Awaiting GBP API OAuth token (1-Click Sheet Queue Active)') . "\n";
    echo "Tripadvisor Listing     : #" . TRIPADVISOR_LOCATION_ID . " (4.9● · 129+ reviews) -> " . TRIPADVISOR_OWNER_URL . "\n";
    echo "Gemini AI SEO Brain     : " . ((defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '') ? 'ACTIVE' : 'FALLBACK') . "\n";
    exit(0);
}

if ($cmd === 'oauth-url') {
    if (GBP_CLIENT_ID === '') {
        echo "Set 'GBP_CLIENT_ID' and 'GBP_CLIENT_SECRET' in config/secrets.php first.\n";
        exit(1);
    }
    $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id'     => GBP_CLIENT_ID,
        'redirect_uri'  => 'http://localhost',
        'response_type' => 'code',
        'scope'         => 'https://www.googleapis.com/auth/business.manage',
        'access_type'   => 'offline',
        'prompt'        => 'consent',
    ]);
    echo "Open this URL in your browser (signed in as seacollegediveclubsharm@gmail.com):\n{$authUrl}\n";
    exit(0);
}

if ($cmd === 'exchange-code') {
    $code = trim((string) ($argv[2] ?? ''));
    if ($code === '' || GBP_CLIENT_ID === '' || GBP_CLIENT_SECRET === '') {
        exit("Usage: php docs/lara_reviews_manager.php exchange-code \"<code>\"\n");
    }
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'code'          => $code,
            'client_id'     => GBP_CLIENT_ID,
            'client_secret' => GBP_CLIENT_SECRET,
            'redirect_uri'  => 'http://localhost',
            'grant_type'    => 'authorization_code',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    echo curl_exec($ch) . "\n";
    curl_close($ch);
    exit(0);
}

if ($cmd === 'sweep-gbp') {
    $doSend = in_array('--send', $argv, true);
    $res = lara_gbp_api_sweep_and_reply($doSend, 20);
    echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    exit($res['ok'] ? 0 : 1);
}

if ($cmd === 'simulate') {
    $author = (string) ($argv[2] ?? 'Guest');
    $rating = (int) ($argv[3] ?? 5);
    $text   = (string) ($argv[4] ?? 'Amazing diving day at Ras Mohammed!');
    $res = lara_generate_review_reply('Google Business Profile', $author, $rating, $text);
    print_r($res);
    exit(0);
}

echo "Unknown command '{$cmd}'. Use: status | oauth-url | exchange-code | sweep-gbp [--send] | simulate\n";
exit(1);
