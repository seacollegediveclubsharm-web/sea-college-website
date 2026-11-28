<?php
/**
 * Google Business Profile (GBP) Live Reviews & Rating Sync Engine (`ChIJ75cBQp5LUxQRMjrNYQx93g0`).
 *
 * 1. Automatically syncs live rating and review_count every 6 hours (`GOOGLE_REVIEWS_CACHE_TTL` = 21600s)
 *    into `storage/google_reviews_cache.json`:
 *      - Tier 1: Official Google Places API (New v1 -> Legacy Details) when `GOOGLE_PLACES_API_KEY` is set.
 *      - Tier 2: Zero-key live Google Business Profile public Maps Embed (`output=embed` by decoded CID)
 *        with `maps/preview/place` protobuf fallback, verified against Place ID `ChIJ75cBQp5LUxQRMjrNYQx93g0`.
 * 2. Preserves last-known-good cached count with a 15-minute retry backoff if upstream is unreachable,
 *    falling back cleanly to `BUSINESS['review_count']` (`3270`) and localized seed review highlights
 *    so page rendering never blocks or breaks.
 */
declare(strict_types=1);

function sc_gbp_seed_reviews(string $lang): array
{
    $rootDir = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__);
    $seedFile = $rootDir . '/storage/google_reviews_seed.json';
    if (is_file($seedFile)) {
        $raw = @file_get_contents($seedFile);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($decoded) && !empty($decoded['reviews'])) {
            $decoded['rating'] = (float) ($decoded['rating'] ?? BUSINESS['rating']);
            $decoded['review_count'] = max((int) BUSINESS['review_count'], (int) ($decoded['review_count'] ?? 0));
            return $decoded;
        }
    }
    $reviews = [];
    if (function_exists('t')) {
        for ($i = 1; $i <= 4; $i++) {
            $reviews[] = [
                'author' => t("rev_{$i}_name"),
                'rating' => 5,
                'text'   => t("rev_{$i}_text"),
                'time'   => t('gbp_review_label') !== 'gbp_review_label' ? t('gbp_review_label') : 'Google Review',
                'url'    => GOOGLE_REVIEWS_URL,
            ];
        }
    }
    return [
        'rating'       => (float) BUSINESS['rating'],
        'review_count' => (int) BUSINESS['review_count'],
        'reviews'      => $reviews,
        'synced_at'    => null,
        'source'       => 'seed',
    ];
}

/**
 * Decode a Google `ChIJ...` Place ID protobuf into its 64-bit decimal CID and hex FTID (`0x...:0x...`).
 *
 * @return array{cid:string,ftid:string}
 */
function sc_decode_place_id_ids(string $placeId): array
{
    $cid = defined('GBP_FID') && GBP_FID !== '' ? (string) GBP_FID : '';
    $ftid = '';
    if (str_starts_with($placeId, 'ChIJ')) {
        $b64 = strtr($placeId, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad > 0) {
            $b64 .= str_repeat('=', 4 - $pad);
        }
        $bin = base64_decode($b64, true);
        if (is_string($bin) && strlen($bin) >= 20 && ord($bin[0]) === 0x0a && ord($bin[2]) === 0x09 && ord($bin[11]) === 0x11) {
            $u1 = unpack('P', substr($bin, 3, 8));
            $u2 = unpack('P', substr($bin, 12, 8));
            if (is_array($u1) && is_array($u2) && isset($u1[1], $u2[1])) {
                $cid = sprintf('%u', $u2[1]);
                $ftid = sprintf('0x%x:0x%x', $u1[1], $u2[1]);
            }
        }
    }
    return ['cid' => $cid, 'ftid' => $ftid];
}

function sc_fetch_places_v1(string $placeId, string $apiKey, string $lang): ?array
{
    $endpoint = 'https://places.googleapis.com/v1/places/' . rawurlencode($placeId)
        . '?languageCode=' . rawurlencode($lang);
    $headers = [
        'Accept: application/json',
        'X-Goog-Api-Key: ' . $apiKey,
        'X-Goog-FieldMask: rating,userRatingCount,reviews,googleMapsUri',
    ];
    $res = sc_http_get($endpoint, $headers, 4);
    if ($res === null) {
        return null;
    }
    $data = json_decode($res, true);
    if (!is_array($data) || empty($data['rating'])) {
        return null;
    }
    $reviews = [];
    foreach (($data['reviews'] ?? []) as $r) {
        $text = trim((string) ($r['text']['text'] ?? $r['originalText']['text'] ?? ''));
        if ($text === '') {
            continue;
        }
        $reviews[] = [
            'author' => (string) ($r['authorAttribution']['displayName'] ?? 'Google Guest'),
            'rating' => max(1, min(5, (int) ($r['rating'] ?? 5))),
            'text'   => mb_substr($text, 0, 320),
            'time'   => (string) ($r['relativePublishTimeDescription'] ?? 'Google Review'),
            'url'    => (string) ($r['googleMapsUri'] ?? $data['googleMapsUri'] ?? GOOGLE_REVIEWS_URL),
        ];
    }
    return [
        'rating'       => round((float) $data['rating'], 1),
        'review_count' => (int) ($data['userRatingCount'] ?? BUSINESS['review_count']),
        'reviews'      => $reviews,
        'synced_at'    => gmdate('c'),
        'source'       => 'places_v1',
    ];
}

function sc_fetch_places_legacy(string $placeId, string $apiKey, string $lang): ?array
{
    $endpoint = 'https://maps.googleapis.com/maps/api/place/details/json?place_id=' . rawurlencode($placeId)
        . '&fields=rating,user_ratings_total,reviews,url&reviews_sort=newest&language=' . rawurlencode($lang)
        . '&key=' . rawurlencode($apiKey);
    $res = sc_http_get($endpoint, ['Accept: application/json'], 4);
    if ($res === null) {
        return null;
    }
    $data = json_decode($res, true);
    $result = $data['result'] ?? null;
    if (!is_array($result) || empty($result['rating'])) {
        return null;
    }
    $reviews = [];
    foreach (($result['reviews'] ?? []) as $r) {
        $text = trim((string) ($r['text'] ?? ''));
        if ($text === '') {
            continue;
        }
        $reviews[] = [
            'author' => (string) ($r['author_name'] ?? 'Google Guest'),
            'rating' => max(1, min(5, (int) ($r['rating'] ?? 5))),
            'text'   => mb_substr($text, 0, 320),
            'time'   => (string) ($r['relative_time_description'] ?? 'Google Review'),
            'url'    => (string) ($r['author_url'] ?? $result['url'] ?? GOOGLE_REVIEWS_URL),
        ];
    }
    return [
        'rating'       => round((float) $result['rating'], 1),
        'review_count' => (int) ($result['user_ratings_total'] ?? BUSINESS['review_count']),
        'reviews'      => $reviews,
        'synced_at'    => gmdate('c'),
        'source'       => 'places_legacy',
    ];
}

/**
 * Zero-key live Google Business Profile rating & review_count sync for `ChIJ75cBQp5LUxQRMjrNYQx93g0`.
 * Queries Google Maps lightweight `output=embed` JSON payload (~3.5 KB) with `preview/place` fallback.
 */
function sc_fetch_gbp_public_sync(string $placeId): ?array
{
    $ids = sc_decode_place_id_ids($placeId);
    $cid = $ids['cid'];
    $ftid = $ids['ftid'];
    $headers = [
        'Accept: text/html,application/xhtml+xml,application/json;q=0.9,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9',
    ];

    // Primary: Google Maps official spotlit embed (~3.5 KB, includes Place ID + exact rating + review count)
    if ($cid !== '') {
        $embedUrl = 'https://maps.google.com/maps?cid=' . rawurlencode($cid) . '&hl=en&gl=us&output=embed';
        $body = sc_http_get($embedUrl, $headers, 3);
        if ($body !== null && (str_contains($body, $placeId) || str_contains($body, $cid))) {
            if (preg_match('/,([1-5](?:\.\d+)?),"\s*([\d,]+)\s+reviews?"/i', $body, $m)) {
                $rating = round((float) $m[1], 1);
                $count = (int) str_replace(',', '', $m[2]);
                if ($rating >= 3.5 && $rating <= 5.0 && $count >= 1000) {
                    return [
                        'rating'       => $rating,
                        'review_count' => $count,
                        'reviews'      => [],
                        'synced_at'    => gmdate('c'),
                        'source'       => 'gbp_maps_embed',
                    ];
                }
            }
        }
    }

    // Secondary fallback: Google Maps place preview protobuf endpoint
    if ($ftid !== '') {
        $lat = (float) (BUSINESS['lat'] ?? 27.9805263);
        $lng = (float) (BUSINESS['lng'] ?? 34.4239091);
        $pbUrl = 'https://www.google.com/maps/preview/place?authuser=0&hl=en&gl=us&pb=!1m17!1s' . rawurlencode($ftid)
            . '!3m12!1m3!1d3000!2d' . $lng . '!3d' . $lat
            . '!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!4m2!3d' . $lat . '!4d' . $lng
            . '!12m4!2m3!1i360!2i120!4i8';
        $pbBody = sc_http_get($pbUrl, $headers, 3);
        if ($pbBody !== null && (str_contains($pbBody, $ftid) || str_contains($pbBody, $placeId) || stripos($pbBody, 'seacollegediveclub') !== false)) {
            $rating = 0.0;
            $count = 0;
            if (preg_match('/,([1-5]\.\d),(\d{3,6})\]/', $pbBody, $m)) {
                $rating = round((float) $m[1], 1);
                $count = (int) $m[2];
            } elseif (preg_match('/"([\d,]+)\s+reviews?"/i', $pbBody, $mc)) {
                $rating = (float) BUSINESS['rating'];
                $count = (int) str_replace(',', '', $mc[1]);
            }
            if ($rating >= 3.5 && $rating <= 5.0 && $count >= 1000) {
                return [
                    'rating'       => $rating,
                    'review_count' => $count,
                    'reviews'      => [],
                    'synced_at'    => gmdate('c'),
                    'source'       => 'gbp_preview_pb',
                ];
            }
        }
    }

    return null;
}

function sc_http_get(string $url, array $headers = [], int $timeout = 4): ?string
{
    $hasUa = false;
    foreach ($headers as $h) {
        if (stripos($h, 'User-Agent:') === 0) {
            $hasUa = true;
            break;
        }
    }
    $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 2,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(2, $timeout),
        ];
        if (!$hasUa) {
            $opts[CURLOPT_USERAGENT] = $ua;
        }
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($body !== false && $code >= 200 && $code < 300) ? (string) $body : null;
    }
    $hdrs = $headers;
    if (!$hasUa) {
        $hdrs[] = 'User-Agent: ' . $ua;
    }
    $ctx = stream_context_create(['http' => [
        'method'          => 'GET',
        'header'          => implode("\r\n", $hdrs),
        'timeout'         => $timeout,
        'follow_location' => 1,
        'max_redirects'   => 2,
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    return $body !== false ? (string) $body : null;
}

function sc_write_gbp_cache(string $cacheFile, array $payload): void
{
    $cacheDir = dirname($cacheFile);
    if (!is_dir($cacheDir) && !@mkdir($cacheDir, 0750, true)) {
        return;
    }
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if (!is_string($json)) {
        return;
    }
    $tmp = $cacheFile . '.tmp.' . getmypid();
    if (@file_put_contents($tmp, $json, LOCK_EX) !== false) {
        if (!@rename($tmp, $cacheFile)) {
            @file_put_contents($cacheFile, $json, LOCK_EX);
            @unlink($tmp);
        }
    } else {
        @file_put_contents($cacheFile, $json, LOCK_EX);
    }
}

/**
 * Format an integer review count in the target language's thousands-separator convention.
 */
function sc_gbp_format_count(int $count, ?string $lang = null): string
{
    $l = $lang ?? (defined('LANG') ? LANG : 'en');
    return match ($l) {
        'de', 'it' => number_format($count, 0, ',', '.'),
        'fr', 'pl', 'ru' => number_format($count, 0, ',', ' '),
        default => number_format($count, 0, '.', ','),
    };
}

/**
 * Returns ['rating' => float, 'review_count' => int, 'reviews' => array, 'synced_at' => ?string, 'source' => string].
 * Automatically syncs Place ID `ChIJ75cBQp5LUxQRMjrNYQx93g0` every 6 hours into `storage/google_reviews_cache.json`.
 */
function gbp_reviews_data(bool $forceRefresh = false): array
{
    static $memo = [];
    $lang = defined('LANG') ? LANG : 'en';
    if (!$forceRefresh && isset($memo[$lang])) {
        return $memo[$lang];
    }

    $seed = sc_gbp_seed_reviews($lang);
    $cacheFile = defined('GOOGLE_REVIEWS_CACHE_FILE')
        ? GOOGLE_REVIEWS_CACHE_FILE
        : (LEADS_STORAGE_DIR . '/google_reviews_cache.json');

    $cached = null;
    if (is_file($cacheFile)) {
        $age = time() - (int) @filemtime($cacheFile);
        $decoded = json_decode((string) @file_get_contents($cacheFile), true);
        if (is_array($decoded) && !empty($decoded['rating']) && !empty($decoded['review_count'])) {
            $cached = $decoded;
            if (!$forceRefresh && $age < GOOGLE_REVIEWS_CACHE_TTL) {
                $reviews = $cached['reviews_by_lang'][$lang] ?? $cached['reviews'] ?? [];
                if (empty($reviews)) {
                    $reviews = $seed['reviews'];
                }
                return $memo[$lang] = [
                    'place_id'     => (string) ($cached['place_id'] ?? GOOGLE_PLACE_ID),
                    'rating'       => round((float) $cached['rating'], 1),
                    'review_count' => (int) $cached['review_count'],
                    'reviews'      => $reviews,
                    'synced_at'    => $cached['synced_at'] ?? null,
                    'source'       => (string) ($cached['source'] ?? 'cache'),
                ];
            }
        }
    }

    $live = null;
    if (GOOGLE_PLACES_API_KEY !== '' && GOOGLE_PLACE_ID !== '') {
        $live = sc_fetch_places_v1(GOOGLE_PLACE_ID, GOOGLE_PLACES_API_KEY, $lang)
            ?? sc_fetch_places_legacy(GOOGLE_PLACE_ID, GOOGLE_PLACES_API_KEY, $lang);
    }
    if ($live === null && GOOGLE_PLACE_ID !== '') {
        $live = sc_fetch_gbp_public_sync(GOOGLE_PLACE_ID);
    }

    if ($live !== null && $live['rating'] >= 3.5 && $live['rating'] <= 5.0 && $live['review_count'] >= 1000) {
        $reviewsByLang = is_array($cached['reviews_by_lang'] ?? null) ? $cached['reviews_by_lang'] : [];
        if (!empty($live['reviews'])) {
            $reviewsByLang[$lang] = $live['reviews'];
        }
        $payload = [
            'place_id'        => GOOGLE_PLACE_ID,
            'rating'          => round((float) $live['rating'], 1),
            'review_count'    => (int) $live['review_count'],
            'synced_at'       => (string) $live['synced_at'],
            'source'          => (string) $live['source'],
            'reviews_by_lang' => $reviewsByLang,
        ];
        sc_write_gbp_cache($cacheFile, $payload);

        $reviews = !empty($live['reviews'])
            ? $live['reviews']
            : ($reviewsByLang[$lang] ?? $seed['reviews']);

        return $memo[$lang] = [
            'place_id'     => GOOGLE_PLACE_ID,
            'rating'       => $payload['rating'],
            'review_count' => $payload['review_count'],
            'reviews'      => $reviews,
            'synced_at'    => $payload['synced_at'],
            'source'       => $payload['source'],
        ];
    }

    // Upstream temporarily unreachable: preserve last-known-good cache (or seed) with 15-min retry backoff
    $backoffMtime = time() - GOOGLE_REVIEWS_CACHE_TTL + 900;
    if ($cached !== null) {
        @touch($cacheFile, $backoffMtime);
        $reviews = $cached['reviews_by_lang'][$lang] ?? $cached['reviews'] ?? $seed['reviews'];
        return $memo[$lang] = [
            'place_id'     => (string) ($cached['place_id'] ?? GOOGLE_PLACE_ID),
            'rating'       => round((float) $cached['rating'], 1),
            'review_count' => (int) $cached['review_count'],
            'reviews'      => !empty($reviews) ? $reviews : $seed['reviews'],
            'synced_at'    => $cached['synced_at'] ?? null,
            'source'       => (string) ($cached['source'] ?? 'stale_cache'),
        ];
    }

    sc_write_gbp_cache($cacheFile, [
        'place_id'        => GOOGLE_PLACE_ID,
        'rating'          => (float) $seed['rating'],
        'review_count'    => (int) $seed['review_count'],
        'synced_at'       => null,
        'source'          => 'seed_backoff',
        'reviews_by_lang' => [],
    ]);
    @touch($cacheFile, $backoffMtime);

    return $memo[$lang] = $seed;
}

/**
 * Keep any baseline `3,270` / `3.270` / `3 270` / `3270` copy dynamically aligned with live `gbp_reviews_data()['review_count']`
 * whenever new Google Business Profile reviews arrive in `storage/google_reviews_cache.json`.
 */
function sc_sync_live_review_text(string $text, ?string $lang = null): string
{
    if (!str_contains($text, '270')) {
        return $text;
    }
    static $reentrancy = false;
    if ($reentrancy) {
        return $text;
    }
    $reentrancy = true;
    try {
        $liveCount = (int) (gbp_reviews_data()['review_count'] ?? BUSINESS['review_count']);
    } finally {
        $reentrancy = false;
    }
    if ($liveCount <= 0 || $liveCount === 3270) {
        return $text;
    }
    $formatted = sc_gbp_format_count($liveCount, $lang);
    return (string) preg_replace_callback(
        '/(?<![0-9a-fA-F#])3([,\.\s]?)270(?![0-9a-fA-F])/u',
        static function (array $m) use ($liveCount, $formatted): string {
            $sep = $m[1];
            if ($sep === '.') {
                return number_format($liveCount, 0, ',', '.');
            }
            if ($sep === ' ') {
                return number_format($liveCount, 0, ',', ' ');
            }
            if ($sep === ',') {
                return number_format($liveCount, 0, '.', ',');
            }
            return $formatted;
        },
        $text
    );
}

/** Render interactive Google Map card (auto-mounted when scrolled near viewport, or on click). */
function gbp_map_embed(): string
{
    $lat = BUSINESS['lat'];
    $lng = BUSINESS['lng'];
    $q = ($lat !== null && $lng !== null)
        ? MAPS_EMBED_QUERY . ' @' . $lat . ',' . $lng
        : MAPS_EMBED_QUERY;
    $src = 'https://www.google.com/maps?q=' . rawurlencode($q)
        . '&hl=' . rawurlencode(LANG)
        . '&z=16&output=embed';
    $title = SITE_NAME . ' — ' . BUSINESS['city'];
    return '<div class="map" data-map-src="' . e($src) . '" data-map-title="' . e($title) . '" style="min-height:300px;display:grid;place-items:center">'
        . '<button type="button" class="btn btn--detail" data-map-load>📍 ' . te('open_maps') . '</button>'
        . '</div>';
}
