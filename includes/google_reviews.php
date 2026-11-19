<?php
/**
 * Google Business Profile (GBP) Reviews & Rating Sync Engine.
 *
 * 1. If GOOGLE_PLACES_API_KEY is configured in config/secrets.php (or env),
 *    fetches live rating, userRatingCount, and latest reviews from Google Places API (New v1)
 *    with fallback to Places Details API (legacy), and caches to storage/google_reviews_cache_{lang}.json
 *    for GOOGLE_REVIEWS_CACHE_TTL seconds (default 6 hours).
 * 2. If no API key is configured yet (or Google API is unreachable), falls back to
 *    storage/google_reviews_seed.json (or localized /lang/{en,ar}.php highlights) so the page
 *    never breaks or blocks rendering.
 */
declare(strict_types=1);

function sc_gbp_seed_reviews(string $lang): array
{
    $seedFile = ROOT_DIR . '/storage/google_reviews_seed.json';
    if (is_file($seedFile)) {
        $raw = @file_get_contents($seedFile);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($decoded) && !empty($decoded['reviews'])) {
            return $decoded;
        }
    }
    $reviews = [];
    for ($i = 1; $i <= 4; $i++) {
        $reviews[] = [
            'author' => t("rev_{$i}_name"),
            'rating' => 5,
            'text'   => t("rev_{$i}_text"),
            'time'   => 'Google Review',
            'url'    => GOOGLE_REVIEWS_URL,
        ];
    }
    return [
        'rating'       => (float) BUSINESS['rating'],
        'review_count' => (int) BUSINESS['review_count'],
        'reviews'      => $reviews,
        'synced_at'    => null,
        'source'       => 'seed',
    ];
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

function sc_http_get(string $url, array $headers = [], int $timeout = 4): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($body !== false && $code >= 200 && $code < 300) ? (string) $body : null;
    }
    $ctx = stream_context_create(['http' => [
        'method'  => 'GET',
        'header'  => implode("\r\n", $headers),
        'timeout' => $timeout,
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    return $body !== false ? (string) $body : null;
}

/**
 * Returns ['rating' => float, 'review_count' => int, 'reviews' => array, 'synced_at' => ?string, 'source' => string].
 */
function gbp_reviews_data(): array
{
    static $memo = null;
    if ($memo !== null) {
        return $memo;
    }
    $lang = defined('LANG') ? LANG : 'en';
    $seed = sc_gbp_seed_reviews($lang);
    $cacheDir = LEADS_STORAGE_DIR;
    $cacheFile = $cacheDir . '/google_reviews_cache_' . preg_replace('/[^a-z]/', '', $lang) . '.json';

    if (is_file($cacheFile)) {
        $age = time() - (int) @filemtime($cacheFile);
        $cached = json_decode((string) @file_get_contents($cacheFile), true);
        if (is_array($cached) && !empty($cached['rating'])) {
            if ($age < GOOGLE_REVIEWS_CACHE_TTL || GOOGLE_PLACES_API_KEY === '') {
                if (empty($cached['reviews'])) {
                    $cached['reviews'] = $seed['reviews'];
                }
                return $memo = $cached;
            }
        }
    }

    if (GOOGLE_PLACES_API_KEY !== '' && GOOGLE_PLACE_ID !== '') {
        $live = sc_fetch_places_v1(GOOGLE_PLACE_ID, GOOGLE_PLACES_API_KEY, $lang)
            ?? sc_fetch_places_legacy(GOOGLE_PLACE_ID, GOOGLE_PLACES_API_KEY, $lang);
        if ($live !== null) {
            if (empty($live['reviews'])) {
                $live['reviews'] = $seed['reviews'];
            }
            if (is_dir($cacheDir) || @mkdir($cacheDir, 0750, true)) {
                @file_put_contents($cacheFile, json_encode($live, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
            }
            return $memo = $live;
        }
    }

    return $memo = $seed;
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
