<?php
/**
 * Lara & Hossam — Google Business Profile (GBP) & Tripadvisor Review Response Engine.
 *
 * Capabilities:
 *   1. Direct Google Business Profile v4 API Auto-Responder (`mybusiness.googleapis.com/v4`):
 *      Paginates across all 3,270+ Google reviews on Location `15687555981863554557` and posts
 *      native-language, Local-SEO-optimized owner replies (`PUT .../reviews/{id}/reply`) once
 *      Google approves GBP API quota (`GBP_CLIENT_ID`, `GBP_CLIENT_SECRET`, `GBP_REFRESH_TOKEN`).
 *   2. Instant Email & Tripadvisor `#26104236` Review Queue Parser:
 *      Extracts individual reviews + direct 1-click reply URLs from Google Business Profile
 *      notification emails and Tripadvisor `#26104236` feeds, generating ready-to-post replies
 *      for the `Reviews_Queue` tab in Google Sheets CRM.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/site_config.php';

/**
 * Generate a warm, human, Local-SEO-smart owner response (Lara + Hossam) in the reviewer's native language.
 *
 * @return array{lang:string,sentiment:string,reply:string}
 */
function lara_generate_review_reply(string $platform, string $author, int $rating, string $reviewText): array
{
    $cleanAuthor = trim((string) preg_replace('/\bTripadvisor Traveler\b/i', '', $author));
    $firstName = $cleanAuthor !== '' ? explode(' ', $cleanAuthor)[0] : '';

    // Extract original untranslated text if Google wrapped "(Translated by Google) ... (Original) ..."
    $effectiveText = trim($reviewText);
    if (preg_match('/\(Original\)\s*(.+)$/isu', $effectiveText, $om)) {
        $origPart = trim($om[1]);
        if ($origPart !== '') {
            $effectiveText = $origPart . ' [Context: ' . trim(preg_replace('/\(Original\).*$/isu', '', $effectiveText) ?? '') . ']';
        }
    }

    if (defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '') {
        $sysPrompt = <<<'SYS'
You are writing official Owner / Management Responses for **Sea College Dive Club** (Official PADI 5-Star Dive Resort in Al Montazah, Sharm El Sheikh, Egypt — rated 4.9★ on Google & Tripadvisor), co-crafted by **Lara** (Head of Customer Care) and **Hossam** (Marketing & Local SEO Director).

RULES:
1. Reply in the **reviewer's original native language** (if `(Original)` is French, reply in French; if Italian, reply in Italian; if Russian/Cyrillic, reply in Russian; if German, German; if Arabic, Arabic; otherwise English).
2. Keep it **2 to 3 warm, natural, human sentences** (40–85 words) — NEVER robotic, NEVER copy-paste identical openings across reviews!
3. Address the reviewer by first name if known (omit name naturally if unknown / "Tripadvisor Traveler").
4. Reference the **specific highlight** they mentioned (e.g., crew kindness, Ras Mohammed / White Island yacht day, snorkeling, PADI course, organization) and weave in **one** natural Local SEO phrase (*Sea College Dive Club*, *PADI 5-Star Dive Resort in Sharm El Sheikh*, *Ras Mohammed*, *White Island*, or *Red Sea*) without keyword stuffing.
5. **Constructive / 1–3★ Reviews** (or reviews mentioning "room for improvement" / short dive time / issue): Be genuinely appreciative and never defensive! Thank them for their honest feedback, note that we review every comment with our PADI instructor team to uphold unhurried, top-tier dives, and warmly invite them to message Operations Management directly at info@seacollegediveclub.com or WhatsApp +201114567886 before their next visit so we can personally look after them.
6. Sign off warmly (e.g., `Warm regards, Lara & the Sea College Dive Club Team` translated naturally into the reply language). No Markdown asterisks (`**`).

Return ONLY JSON matching schema.
SYS;

        $userPrompt = "Platform: {$platform}\nReviewer Name: " . ($firstName ?: 'Guest') . "\nStar Rating: {$rating}/5\nReview Text:\n{$effectiveText}";

        $payloadJson = json_encode([
            'systemInstruction' => ['parts' => [['text' => $sysPrompt]]],
            'contents'          => [['role' => 'user', 'parts' => [['text' => $userPrompt]]]],
            'generationConfig'  => [
                'temperature'      => 0.45,
                'maxOutputTokens'  => 1024,
                'responseMimeType' => 'application/json',
                'responseSchema'   => [
                    'type'       => 'OBJECT',
                    'properties' => [
                        'lang'      => ['type' => 'STRING'],
                        'sentiment' => ['type' => 'STRING'],
                        'reply'     => ['type' => 'STRING'],
                    ],
                    'required'   => ['lang', 'sentiment', 'reply'],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $models = ['gemini-3.5-flash-lite', 'gemini-flash-lite-latest', 'gemini-3.1-flash-lite', 'gemini-flash-latest'];
        foreach ($models as $idx => $model) {
            if ($idx > 0) {
                usleep(500000);
            }
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . rawurlencode(GEMINI_API_KEY);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS     => $payloadJson,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 18,
            ]);
            $raw = (string) curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code !== 200 || $raw === '') {
                continue;
            }
            $outer = json_decode($raw, true);
            $jsonText = '';
            foreach (($outer['candidates'][0]['content']['parts'] ?? []) as $part) {
                if (empty($part['thought']) && isset($part['text']) && is_string($part['text'])) {
                    $jsonText = $part['text'];
                }
            }
            $dec = $jsonText !== '' ? json_decode($jsonText, true) : null;
            if (is_array($dec) && !empty($dec['reply'])) {
                return [
                    'lang'      => (string) ($dec['lang'] ?? 'en'),
                    'sentiment' => (string) ($dec['sentiment'] ?? ($rating >= 4 ? 'positive' : 'constructive')),
                    'reply'     => trim(str_replace('**', '', (string) $dec['reply'])),
                ];
            }
        }
    }

    // Deterministic fallback if AI is temporarily unreachable
    $hi = $firstName !== '' ? "Thank you so much, {$firstName}!" : "Thank you so much for your review!";
    if ($rating <= 3 || preg_match('/room for improvement|disappointed|short|problem/i', $reviewText)) {
        return [
            'lang'      => 'en',
            'sentiment' => 'constructive',
            'reply'     => "Thank you for sharing your honest feedback" . ($firstName !== '' ? ", {$firstName}" : "") . ". At Sea College Dive Club in Sharm El Sheikh, we take every guest comment seriously and have shared your note with our PADI operations team to ensure every dive is unhurried and memorable. Please feel free to reach us directly at info@seacollegediveclub.com or WhatsApp +201114567886 so we can personally welcome you back on the Red Sea. — Lara & the Sea College Team",
        ];
    }
    return [
        'lang'      => 'en',
        'sentiment' => 'positive',
        'reply'     => "{$hi} Everyone on our crew at Sea College Dive Club (PADI 5-Star Dive Resort, Sharm El Sheikh) loved having you out on the Red Sea with us. We cannot wait to welcome you back for more diving and snorkeling adventures soon! — Lara & the Sea College Team",
    ];
}

/**
 * Exchange Google OAuth2 Refresh Token for a live Bearer access token (`business.manage` scope).
 */
function lara_gbp_access_token(): ?string
{
    if (GBP_CLIENT_ID === '' || GBP_CLIENT_SECRET === '' || GBP_REFRESH_TOKEN === '') {
        return null;
    }
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'client_id'     => GBP_CLIENT_ID,
            'client_secret' => GBP_CLIENT_SECRET,
            'refresh_token' => GBP_REFRESH_TOKEN,
            'grant_type'    => 'refresh_token',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 12,
    ]);
    $raw = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || $raw === '') {
        return null;
    }
    $dec = json_decode($raw, true);
    return isset($dec['access_token']) ? (string) $dec['access_token'] : null;
}

/**
 * Auto-discover `accounts/{accountId}` from Google My Business Account Management API v1.
 */
function lara_gbp_resolve_account_name(string $accessToken): ?string
{
    if (GBP_ACCOUNT_ID !== '') {
        return str_starts_with(GBP_ACCOUNT_ID, 'accounts/') ? GBP_ACCOUNT_ID : ('accounts/' . GBP_ACCOUNT_ID);
    }
    $ch = curl_init('https://mybusinessaccountmanagement.googleapis.com/v1/accounts');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken, 'Accept: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 12,
    ]);
    $raw = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) {
        return null;
    }
    $dec = json_decode($raw, true);
    return isset($dec['accounts'][0]['name']) ? (string) $dec['accounts'][0]['name'] : null;
}

/**
 * Sweep unanswered Google Business Profile reviews via Official GBP API v4 and reply automatically.
 *
 * @return array{ok:bool,checked:int,unanswered:int,replied:int,error?:string,items:array<int,array<string,mixed>>}
 */
function lara_gbp_api_sweep_and_reply(bool $doSend = false, int $maxPages = 10): array
{
    $tok = lara_gbp_access_token();
    if ($tok === null) {
        return [
            'ok'         => false,
            'checked'    => 0,
            'unanswered' => 0,
            'replied'    => 0,
            'error'      => 'GBP_CLIENT_ID / GBP_CLIENT_SECRET / GBP_REFRESH_TOKEN not configured yet in config/secrets.php.',
            'items'      => [],
        ];
    }
    $accName = lara_gbp_resolve_account_name($tok);
    if ($accName === null) {
        return [
            'ok'         => false,
            'checked'    => 0,
            'unanswered' => 0,
            'replied'    => 0,
            'error'      => 'Could not resolve GBP account (ensure Google Business Profile API quota is approved on your Cloud project).',
            'items'      => [],
        ];
    }

    $starMap = ['ONE' => 1, 'TWO' => 2, 'THREE' => 3, 'FOUR' => 4, 'FIVE' => 5];
    $checked = 0;
    $unanswered = 0;
    $replied = 0;
    $items = [];
    $pageToken = '';

    for ($p = 0; $p < $maxPages; $p++) {
        $url = "https://mybusiness.googleapis.com/v4/{$accName}/locations/" . GBP_LOCATION_ID . "/reviews?pageSize=50"
            . ($pageToken !== '' ? '&pageToken=' . rawurlencode($pageToken) : '');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $tok, 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
        ]);
        $raw = (string) curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200) {
            return [
                'ok'         => false,
                'checked'    => $checked,
                'unanswered' => $unanswered,
                'replied'    => $replied,
                'error'      => "GBP reviews.list HTTP {$code}: {$raw}",
                'items'      => $items,
            ];
        }
        $dec = json_decode($raw, true);
        foreach (($dec['reviews'] ?? []) as $rev) {
            $checked++;
            if (!empty($rev['reviewReply']['comment'])) {
                continue; // Already answered!
            }
            $unanswered++;
            $revName = (string) ($rev['name'] ?? '');
            $author  = (string) ($rev['reviewer']['displayName'] ?? 'Guest');
            $stars   = $starMap[(string) ($rev['starRating'] ?? 'FIVE')] ?? 5;
            $comment = trim((string) ($rev['comment'] ?? '5-star rating'));
            $plan    = lara_generate_review_reply('Google Business Profile', $author, $stars, $comment);

            $status = 'preview';
            if ($doSend && $revName !== '') {
                $putCh = curl_init("https://mybusiness.googleapis.com/v4/{$revName}/reply");
                curl_setopt_array($putCh, [
                    CURLOPT_CUSTOMREQUEST  => 'PUT',
                    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $tok, 'Content-Type: application/json'],
                    CURLOPT_POSTFIELDS     => json_encode(['comment' => $plan['reply']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 15,
                ]);
                $putRaw = (string) curl_exec($putCh);
                $putCode = (int) curl_getinfo($putCh, CURLINFO_HTTP_CODE);
                curl_close($putCh);
                if ($putCode >= 200 && $putCode < 300) {
                    $replied++;
                    $status = 'replied_live';
                } else {
                    $status = "error_{$putCode}";
                }
            }

            $items[] = [
                'author'  => $author,
                'rating'  => $stars,
                'comment' => $comment,
                'lang'    => $plan['lang'],
                'reply'   => $plan['reply'],
                'status'  => $status,
            ];
        }
        $pageToken = (string) ($dec['nextPageToken'] ?? '');
        if ($pageToken === '') {
            break;
        }
    }

    return [
        'ok'         => true,
        'checked'    => $checked,
        'unanswered' => $unanswered,
        'replied'    => $replied,
        'items'      => $items,
    ];
}

/**
 * Parse a Google Business Profile notification email body (`businessprofile-noreply@google.com`)
 * into individual review items with clean direct 1-click reply URLs.
 *
 * @return array<int, array{review_id:string,author:string,text:string,reply_url:string,rating:int}>
 */
function lara_parse_gbp_email_reviews(string $emailBody): array
{
    $norm = str_replace("\r\n", "\n", $emailBody);
    if (preg_match('/when you[’\']ve replied\.\s*\n+/iu', $norm, $hm, PREG_OFFSET_CAPTURE)) {
        $norm = "\n" . substr($norm, $hm[0][1] + strlen($hm[0][0]));
    }
    $pattern = '/\n([^\n<>]{2,60})\n\n(.*?)\n\nReply to review\s*\n<(https:\/\/business\.google\.com\/n\/\d+\/reviews\/([^?>]+)[^>]+)>/su';
    $out = [];
    if (preg_match_all($pattern, $norm, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $author = trim($m[1]);
            // If multi-line block captured header text, take only last non-empty line as author
            if (str_contains($author, "\n")) {
                $parts = array_values(array_filter(array_map('trim', explode("\n", $author))));
                $author = (string) end($parts);
            }
            $text = trim(preg_replace('/\s+/', ' ', $m[2]) ?? $m[2]);
            $revId = trim($m[4]);
            $cleanUrl = 'https://business.google.com/n/' . GBP_LOCATION_ID . '/reviews/' . $revId . '?fid=' . GBP_FID;
            $rating = preg_match('/room for improvement|okay experience|average|disappointed/i', $text) ? 3 : 5;
            $out[] = [
                'review_id' => $revId,
                'author'    => $author,
                'text'      => $text,
                'reply_url' => $cleanUrl,
                'rating'    => $rating,
            ];
        }
    }
    return $out;
}
