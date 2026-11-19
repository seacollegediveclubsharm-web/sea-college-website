<?php
/**
 * Official Tripadvisor Integration (Location ID 26104236) & Verified Platforms Hub.
 *
 * 1. Supports official Tripadvisor Content / Terra API when TRIPADVISOR_API_KEY is set
 *    in config/secrets.php.
 * 2. Automatically syncs live rating, review count, category ranking, and recent traveler
 *    review excerpts from Tripadvisor's official public WidgetEmbed endpoints every 6 hours
 *    (zero API key required), cached in storage/tripadvisor_reviews_cache_{lang}.json.
 * 3. Falls back safely to storage/tripadvisor_reviews_seed.json so page render never blocks.
 * 4. Embeds the owner's official Tripadvisor Write-a-Review widget (TA_cdswritereviewlgvi409)
 *    with normalized dynamic locale parameter.
 */
declare(strict_types=1);

require_once __DIR__ . '/google_reviews.php';
require_once __DIR__ . '/icons.php';

function ta_widget_locale(?string $lang = null): string
{
    $l = $lang ?? (defined('LANG') ? LANG : 'en');
    return match ($l) {
        'ar' => 'ar_EG',
        'ru' => 'ru',
        'de' => 'de',
        'it' => 'it',
        'fr' => 'fr',
        'pl' => 'pl',
        'zh' => 'zh_CN',
        default => 'en_US',
    };
}

function sc_ta_seed_reviews(): array
{
    $seedFile = ROOT_DIR . '/storage/tripadvisor_reviews_seed.json';
    if (is_file($seedFile)) {
        $raw = @file_get_contents($seedFile);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($decoded) && !empty($decoded['rating'])) {
            return $decoded;
        }
    }
    return [
        'location_id'  => TRIPADVISOR_LOCATION_ID,
        'rating'       => 4.9,
        'review_count' => 129,
        'ranking'      => '#31 Boat Tours & Water Sports in Sharm El Sheikh',
        'reviews'      => [],
        'synced_at'    => null,
        'source'       => 'fallback',
    ];
}

function sc_fetch_ta_api(string $locId, string $apiKey, string $lang): ?array
{
    $taLang = match ($lang) {
        'ar' => 'ar',
        'ru' => 'ru',
        'de' => 'de',
        'it' => 'it',
        'fr' => 'fr',
        'pl' => 'pl',
        'zh' => 'zh',
        default => 'en',
    };
    $base = 'https://api.content.tripadvisor.com/api/v1/location/' . rawurlencode($locId);
    $detailsJson = sc_http_get($base . '/details?language=' . rawurlencode($taLang) . '&key=' . rawurlencode($apiKey), ['Accept: application/json'], 4);
    if ($detailsJson === null) {
        return null;
    }
    $details = json_decode($detailsJson, true);
    if (!is_array($details) || empty($details['rating'])) {
        return null;
    }
    $reviews = [];
    $revJson = sc_http_get($base . '/reviews?language=' . rawurlencode($taLang) . '&key=' . rawurlencode($apiKey), ['Accept: application/json'], 4);
    if ($revJson !== null) {
        $revData = json_decode($revJson, true);
        foreach (($revData['data'] ?? []) as $r) {
            $text = trim((string) ($r['text'] ?? $r['title'] ?? ''));
            if ($text === '') {
                continue;
            }
            $reviews[] = [
                'author' => (string) ($r['user']['username'] ?? t('ta_traveler_label')),
                'rating' => max(1, min(5, (int) round((float) ($r['rating'] ?? 5)))),
                'text'   => mb_substr($text, 0, 320),
                'time'   => !empty($r['published_date']) ? substr((string) $r['published_date'], 0, 10) : t('ta_verified_label'),
                'url'    => (string) ($r['url'] ?? TRIPADVISOR_URL),
            ];
        }
    }
    return [
        'location_id'  => $locId,
        'rating'       => round((float) $details['rating'], 1),
        'review_count' => (int) ($details['num_reviews'] ?? 129),
        'ranking'      => (string) ($details['ranking_data']['ranking_string'] ?? '#31 Boat Tours & Water Sports in Sharm El Sheikh'),
        'reviews'      => $reviews,
        'synced_at'    => gmdate('c'),
        'source'       => 'tripadvisor_api',
    ];
}

function sc_fetch_ta_widget_feed(string $locId, string $lang): ?array
{
    $locale = ta_widget_locale($lang);
    $narrowUrl = 'https://www.tripadvisor.com/WidgetEmbed-cdsratingsonlynarrow?lang=' . rawurlencode($locale)
        . '&locationId=' . rawurlencode($locId) . '&display_version=2&uniq=412';
    $sspUrl = 'https://www.tripadvisor.com/WidgetEmbed-selfserveprop?lang=en_US&locationId=' . rawurlencode($locId)
        . '&display_version=2&uniq=411&nreviews=4&rating=true&writereviewlink=true&popIdx=true&iswide=true&border=true';

    $narrowBody = sc_http_get($narrowUrl, ['Accept: */*'], 4);
    $sspBody = sc_http_get($sspUrl, ['Accept: */*'], 4);
    if ($narrowBody === null && $sspBody === null) {
        return null;
    }

    $rating = 0.0;
    $count = 0;
    if ($narrowBody !== null) {
        if (preg_match('/bubble_(\d{2})/', $narrowBody, $m)) {
            $rating = round(((int) $m[1]) / 10, 1);
        }
        if (preg_match('/bubble_\d{2}[^>]*><\/span>[^<]*<br\/>\s*<span>\s*([\d,\.]+)/', $narrowBody, $mc)) {
            $count = (int) preg_replace('/\D/', '', $mc[1]);
        }
    }
    if ($rating <= 0) {
        return null;
    }

    $ranking = '';
    $reviews = [];
    if ($sspBody !== null) {
        if (preg_match('/<sup>#<Figures?>?#?<\/sup>\s*(\d+\s+of\s+[\d,]+)\s*<\/span>\s*([^<]+)</i', $sspBody, $rk)
            || preg_match('/<sup>#<\/sup>(\d+\s+of\s+[\d,]+)\s*<\/span>\s*([^<\']+)/i', $sspBody, $rk)) {
            $ranking = '#' . trim($rk[1]) . ' ' . html_entity_decode(trim(str_replace(["' +", "'+\n", "\n"], '', $rk[2])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        if (preg_match_all('/widSSPQuote[^>]*>(.*?)<\/span>.*?href="(https:\/\/www\.tripadvisor\.com\/ShowUserReviews-[^"]+)"/s', $sspBody, $matches, PREG_SET_ORDER)) {
            $seenUrls = [];
            foreach ($matches as $m) {
                if (isset($seenUrls[$m[2]])) {
                    continue;
                }
                $seenUrls[$m[2]] = true;
                $cleanQuote = html_entity_decode(strip_tags(str_replace(["'+\n'", "' +\n'", "'+\r\n'"], '', $m[1])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $cleanQuote = trim($cleanQuote, " \t\n\r\0\x0B\"“”");
                if ($cleanQuote === '') {
                    continue;
                }
                $reviews[] = [
                    'author' => t('ta_traveler_label'),
                    'rating' => 5,
                    'text'   => mb_substr($cleanQuote, 0, 320),
                    'time'   => t('ta_verified_label'),
                    'url'    => $m[2],
                ];
            }
        }
    }

    return [
        'location_id'  => $locId,
        'rating'       => $rating,
        'review_count' => $count > 0 ? $count : 129,
        'ranking'      => $ranking !== '' ? $ranking : '#31 of 575 Boat Tours & Water Sports in Sharm El Sheikh',
        'reviews'      => $reviews,
        'synced_at'    => gmdate('c'),
        'source'       => 'tripadvisor_widget_sync',
    ];
}

function sc_ta_localize_payload(array $data, string $lang): array
{
    if ($lang === 'en' || ($data['source'] ?? '') === 'tripadvisor_api') {
        return $data;
    }
    $rk = t('ta_ranking_text');
    if ($rk !== '' && $rk !== 'ta_ranking_text') {
        $data['ranking'] = $rk;
    }
    $seed = sc_ta_seed_reviews();
    $baseReviews = !empty($data['reviews']) ? $data['reviews'] : ($seed['reviews'] ?? []);
    foreach ($baseReviews as $idx => &$r) {
        $n = $idx + 1;
        $k = "ta_rev_{$n}_text";
        $tr = t($k);
        if ($tr !== '' && $tr !== $k) {
            $r['text'] = $tr;
        }
        $r['author'] = t('ta_traveler_label');
        $r['time'] = t('ta_verified_label');
    }
    unset($r);
    $data['reviews'] = $baseReviews;
    return $data;
}

function ta_reviews_data(): array
{
    static $memo = null;
    if ($memo !== null) {
        return $memo;
    }
    $lang = defined('LANG') ? LANG : 'en';
    $seed = sc_ta_seed_reviews();
    $cacheDir = LEADS_STORAGE_DIR;
    $cacheFile = $cacheDir . '/tripadvisor_reviews_cache_' . preg_replace('/[^a-z]/', '', $lang) . '.json';

    if (is_file($cacheFile)) {
        $age = time() - (int) @filemtime($cacheFile);
        $cached = json_decode((string) @file_get_contents($cacheFile), true);
        if (is_array($cached) && !empty($cached['rating']) && $age < TRIPADVISOR_CACHE_TTL) {
            if (empty($cached['reviews'])) {
                $cached['reviews'] = $seed['reviews'];
            }
            return $memo = sc_ta_localize_payload($cached, $lang);
        }
    }

    $live = null;
    if (TRIPADVISOR_API_KEY !== '' && TRIPADVISOR_LOCATION_ID !== '') {
        $live = sc_fetch_ta_api(TRIPADVISOR_LOCATION_ID, TRIPADVISOR_API_KEY, $lang);
    }
    if ($live === null && TRIPADVISOR_LOCATION_ID !== '') {
        $live = sc_fetch_ta_widget_feed(TRIPADVISOR_LOCATION_ID, $lang);
    }
    if ($live !== null) {
        if (empty($live['reviews'])) {
            $live['reviews'] = $seed['reviews'];
        }
        if (is_dir($cacheDir) || @mkdir($cacheDir, 0750, true)) {
            @file_put_contents($cacheFile, json_encode($live, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
        }
        return $memo = sc_ta_localize_payload($live, $lang);
    }

    return $memo = sc_ta_localize_payload($seed, $lang);
}

/**
 * Localized Tripadvisor listing & write-review URLs across regional Tripadvisor domains.
 */
function ta_profile_url(?string $lang = null): string
{
    $l = $lang ?? (defined('LANG') ? LANG : 'en');
    $path = '/Attraction_Review-g297555-d26104236-Reviews-Sea_college_dive_club-Sharm_El_Sheikh_South_Sinai_Red_Sea_and_Sinai.html';
    return match ($l) {
        'ar' => TRIPADVISOR_URL_AR,
        'ru' => 'https://www.tripadvisor.ru' . $path,
        'de' => 'https://www.tripadvisor.de' . $path,
        'it' => 'https://www.tripadvisor.it' . $path,
        'fr' => 'https://www.tripadvisor.fr' . $path,
        'pl' => 'https://pl.tripadvisor.com' . $path,
        'zh' => 'https://cn.tripadvisor.com' . $path,
        default => TRIPADVISOR_URL,
    };
}

function ta_write_review_url(?string $lang = null): string
{
    $l = $lang ?? (defined('LANG') ? LANG : 'en');
    $path = '/UserReview-g297555-d26104236-Sea_college_dive_club-Sharm_El_Sheikh_South_Sinai_Red_Sea_and_Sinai.html';
    return match ($l) {
        'ar' => TRIPADVISOR_WRITE_REVIEW_URL_AR,
        'ru' => 'https://www.tripadvisor.ru' . $path,
        'de' => 'https://www.tripadvisor.de' . $path,
        'it' => 'https://www.tripadvisor.it' . $path,
        'fr' => 'https://www.tripadvisor.fr' . $path,
        'pl' => 'https://pl.tripadvisor.com' . $path,
        'zh' => 'https://cn.tripadvisor.com' . $path,
        default => TRIPADVISOR_WRITE_REVIEW_URL,
    };
}

/**
 * Renders the owner's official Tripadvisor Write-a-Review Widget:
 * - English / international: TA_cdswritereviewlgvi409 (uniq=409, lang=en_US / locale, tripadvisor.com)
 * - Arabic (ar_EG):          TA_cdswritereviewlgvi969 (uniq=969, lang=ar_EG, tripadvisor.com.eg)
 */
function ta_official_widget_html(string $placement = 'home'): string
{
    static $mounted = false;
    $ta = ta_reviews_data();
    $lang = defined('LANG') ? LANG : 'en';
    $locale = ta_widget_locale($lang);
    $locId = TRIPADVISOR_LOCATION_ID;

    if ($lang === 'ar') {
        $w = [
            'uniq'     => TRIPADVISOR_UNIQ_ID_AR,
            'ul_id'    => 'sO1kCs',
            'ul_class' => 'TA_links Du0OIT',
            'li_id'    => '74bUilQRR',
            'li_class' => '9xQVXKp',
            'home'     => 'https://www.tripadvisor.com.eg/',
        ];
    } else {
        $w = [
            'uniq'     => TRIPADVISOR_UNIQ_ID,
            'ul_id'    => 'Awwm6VK9Dau',
            'ul_class' => 'TA_links BVzU3dd',
            'li_id'    => 'xugibYoB',
            'li_class' => 'hYDhTH2cQK',
            'home'     => 'https://www.tripadvisor.com/',
        ];
    }

    $scriptSrc = 'https://www.jscache.com/wejs?wtype=cdswritereviewlgvi&uniq=' . rawurlencode($w['uniq'])
        . '&locationId=' . rawurlencode($locId)
        . '&lang=' . rawurlencode($locale)
        . '&display_version=2';

    ob_start(); ?>
    <div class="ta-widget-box" data-ta-location="<?= e($locId) ?>">
      <div class="ta-widget-box__summary">
        <div class="ta-widget-box__brand">
          <span class="ta-owl-pill"><?= brand_icon('tripadvisor', 18) ?> <span>Tripadvisor</span></span>
          <strong><bdi dir="ltr"><?= e(number_format((float) $ta['rating'], 1)) ?> / 5.0</bdi> <span class="ta-bubbles" aria-hidden="true">●●●●●</span></strong>
        </div>
        <p class="ta-widget-box__meta">
          <?= te('ta_summary_line', ['count' => number_format((int) $ta['review_count'])]) ?>
          <?php if (!empty($ta['ranking'])): ?> · <bdi dir="ltr"><?= e((string) $ta['ranking']) ?></bdi><?php endif; ?>
        </p>
        <div class="ta-widget-box__actions">
          <a class="btn btn--ta btn--sm" href="<?= e(ta_profile_url()) ?>" target="_blank" rel="noopener" data-track="outbound_link" data-placement="ta_read_<?= e($placement) ?>"><?= brand_icon('tripadvisor', 16) ?> <span><?= te('ta_read_reviews') ?> ↗</span></a>
          <a class="btn btn--detail btn--sm" href="<?= e(ta_write_review_url()) ?>" target="_blank" rel="noopener" data-track="outbound_link" data-placement="ta_write_<?= e($placement) ?>">✎ <?= te('ta_write_review') ?> ↗</a>
        </div>
      </div>
      <?php if (!$mounted): $mounted = true; ?>
      <div class="ta-widget-box__embed">
        <div id="TA_cdswritereviewlgvi<?= e($w['uniq']) ?>" class="TA_cdswritereviewlgvi">
          <ul id="<?= e($w['ul_id']) ?>" class="<?= e($w['ul_class']) ?>">
            <li id="<?= e($w['li_id']) ?>" class="<?= e($w['li_class']) ?>">
              <a target="_blank" rel="noopener" href="<?= e($w['home']) ?>">
                <img src="https://static.tacdn.com/img2/brand_refresh/Tripadvisor_lockup_horizontal_secondary_registered.svg" alt="TripAdvisor" width="180" height="38" loading="lazy">
              </a>
            </li>
          </ul>
        </div>
        <script async src="<?= e($scriptSrc) ?>" data-loadtrk onload="this.loadtrk=true"></script>
      </div>
      <?php endif; ?>
    </div>
    <?php
    return (string) ob_get_clean();
}

/**
 * Renders the "Verified & Recommended Across Official Platforms" Hub with real SVG brand icons.
 */
function verified_platforms_hub_html(bool $dark = false): string
{
    $gbp = gbp_reviews_data();
    $ta = ta_reviews_data();
    $ytUrl = SOCIAL['youtube'] !== '' ? SOCIAL['youtube'] : 'https://www.youtube.com/@seacollege.diveclub';
    $items = [
        [
            'badge' => 'Google',
            'icon'  => brand_icon('google', 24),
            'title' => t('plat_google_t'),
            'stat'  => $gbp['rating'] . '★ · ' . number_format((int) $gbp['review_count']) . '+ ' . t('plat_reviews_word'),
            'desc'  => t('plat_google_d'),
            'href'  => GOOGLE_REVIEWS_URL,
            'cta'   => t('see_all_reviews'),
        ],
        [
            'badge' => 'Tripadvisor · Viator',
            'icon'  => brand_icon('tripadvisor', 24),
            'title' => t('plat_ta_t'),
            'stat'  => number_format((float) $ta['rating'], 1) . ' ●●●●● · ' . number_format((int) $ta['review_count']) . '+ ' . t('plat_reviews_word'),
            'desc'  => t('plat_ta_d'),
            'href'  => ta_profile_url(),
            'cta'   => t('ta_read_reviews'),
        ],
        [
            'badge' => 'YouTube Official',
            'icon'  => brand_icon('youtube', 24),
            'title' => t('plat_yt_t'),
            'stat'  => t('plat_yt_stat'),
            'desc'  => t('plat_yt_d'),
            'href'  => $ytUrl,
            'cta'   => t('plat_yt_cta'),
        ],
        [
            'badge' => 'PADI Worldwide',
            'icon'  => brand_icon('padi', 24),
            'title' => t('plat_padi_t'),
            'stat'  => BUSINESS['padi_level'],
            'desc'  => t('plat_padi_d'),
            'href'  => PADI_PROFILE_URL,
            'cta'   => t('plat_padi_cta'),
        ],
        [
            'badge' => 'CDWS Egypt',
            'icon'  => brand_icon('cdws', 24),
            'title' => t('plat_cdws_t'),
            'stat'  => t('plat_cdws_stat'),
            'desc'  => t('plat_cdws_d'),
            'href'  => CDWS_PROFILE_URL,
            'cta'   => t('plat_cdws_cta'),
        ],
        [
            'badge' => 'Social Community',
            'icon'  => '<span class="platform-card__multi-icons">' . brand_icon('instagram', 20) . brand_icon('facebook', 20) . brand_icon('tiktok', 20) . '</span>',
            'title' => t('plat_social_t'),
            'stat'  => 'Instagram · Facebook · TikTok',
            'desc'  => t('plat_social_d'),
            'href'  => SOCIAL['instagram'],
            'cta'   => t('plat_social_cta'),
        ],
    ];

    ob_start(); ?>
    <div class="platforms-hub <?= $dark ? 'platforms-hub--dark' : '' ?>">
      <div class="platforms-hub__head">
        <h3><?= te('plat_hub_title') ?></h3>
        <p><?= te('plat_hub_sub') ?></p>
      </div>
      <div class="platforms-grid">
        <?php foreach ($items as $p): ?>
          <a class="platform-card" href="<?= e($p['href']) ?>" target="_blank" rel="noopener" data-track="outbound_link">
            <div class="platform-card__top">
              <span class="platform-card__icon" aria-hidden="true"><?= $p['icon'] ?></span>
              <span class="platform-card__badge"><?= e($p['badge']) ?></span>
            </div>
            <strong class="platform-card__title"><?= e($p['title']) ?></strong>
            <span class="platform-card__stat"><bdi dir="ltr"><?= e($p['stat']) ?></bdi></span>
            <p class="platform-card__desc"><?= e($p['desc']) ?></p>
            <span class="platform-card__cta"><?= e($p['cta']) ?> ↗</span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

