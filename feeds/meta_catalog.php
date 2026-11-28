<?php
/**
 * Dynamic Meta Commerce Manager & Google Merchant Product Feed (RSS 2.0 XML).
 *
 * Endpoints (routed via .htaccess):
 * - Primary English Feed:      https://seacollegediveclub.com/feeds/meta-catalog.xml
 * - Per-Language Full Feeds:   https://seacollegediveclub.com/feeds/meta-catalog-{ru|it|pl|de|fr|ar|zh}.xml
 * - Meta Language Override XML:https://seacollegediveclub.com/feeds/meta-catalog-i18n.xml
 *
 * Guarantees 100% 1:1 `<g:id>` alignment with `window.fbq('track', 'ViewContent' / 'AddToCart', { content_ids: [...] })`
 * and live EUR pricing from `data/catalog.php` (`owd` = €400, `aowd` = €350, etc.).
 */
declare(strict_types=1);

define('SC_NO_SESSION', true);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once ROOT_DIR . '/includes/catalog_helpers.php';

$catalog       = require ROOT_DIR . '/data/catalog.php';
$seoAll        = is_file(ROOT_DIR . '/data/items_seo.php') ? (require ROOT_DIR . '/data/items_seo.php') : [];
$itemGalleries = is_file(ROOT_DIR . '/data/item_galleries.php') ? (require ROOT_DIR . '/data/item_galleries.php') : [];

$mode = strtolower(trim((string) ($_GET['mode'] ?? 'primary')));
$includeAddons = (($_GET['include_addons'] ?? '') === '1');

$xmlEsc = static fn(string $s): string => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

/**
 * Map catalog item into a clean ad-ready segment label for Meta Product Sets.
 */
function sc_meta_segment_label(string $catKey, array $it): string
{
    $id = (string) ($it['id'] ?? '');
    if ($catKey === 'offers' || str_starts_with($id, 'bundle-')) {
        return 'Course Bundle';
    }
    if ($catKey === 'padi-courses') {
        if (in_array($id, ['bubblemaker', 'seal-team', 'dsd'], true)) {
            return 'Intro Dive';
        }
        return 'PADI Course';
    }
    if ($catKey === 'snorkeling') {
        return 'Snorkeling & Boat Charter';
    }
    if (str_starts_with($id, 'dsd-')) {
        return 'Intro Dive';
    }
    if (str_starts_with($id, 'sup-') || $id === 'private-guide') {
        return 'Wreck & Specialty Dive';
    }
    return 'Certified Dive Package';
}

/**
 * Filter out non-advertisable micro equipment rentals (€4 mask/fins), mandatory marina surcharges,
 * and on-request insurance items unless explicitly requested via ?include_addons=1.
 */
function sc_meta_is_advertisable(array $it, bool $includeAddons): bool
{
    if ($includeAddons) {
        return isset($it['price']) && is_numeric($it['price']) && (float) $it['price'] > 0 && !empty($it['slug']);
    }
    return sc_is_catalog_item($it);
}

/**
 * Resolve localized title & description in any supported language without mutating global LANG.
 *
 * @return array{title:string, desc:string, link:string}
 */
function sc_meta_localize_item(array $it, array $seoItem, string $langCode): array
{
    $name = (string) ($it['name'] ?? '');
    $desc = (string) ($it['desc'] ?? '');
    $dur  = (string) ($it['duration'] ?? '');

    if ($langCode !== 'en') {
        if (!empty($it['name_' . $langCode])) {
            $name = (string) $it['name_' . $langCode];
        }
        if (!empty($it['desc_' . $langCode])) {
            $desc = (string) $it['desc_' . $langCode];
        }
        if (!empty($it['duration_' . $langCode])) {
            $dur = (string) $it['duration_' . $langCode];
        }
        static $i18nCache = [];
        if (!isset($i18nCache[$langCode])) {
            $i18nCache[$langCode] = [];
            foreach (['seo_courses.php', 'seo_dives_trips.php'] as $fn) {
                $p = ROOT_DIR . '/data/i18n/' . $langCode . '/' . $fn;
                if (is_file($p)) {
                    $loaded = require $p;
                    if (is_array($loaded)) {
                        $arr = $loaded['items'] ?? $loaded;
                        if (is_array($arr)) {
                            $i18nCache[$langCode] = array_replace($i18nCache[$langCode], $arr);
                        }
                    }
                }
            }
        }
        $tr = $i18nCache[$langCode][$it['id']] ?? [];
        if ($name === (string) ($it['name'] ?? '') && !empty($tr['h1'])) {
            $name = (string) $tr['h1'];
        }
        if ($desc === (string) ($it['desc'] ?? '') && !empty($tr['meta_desc'])) {
            $desc = (string) $tr['meta_desc'];
        }
    } else {
        if ($desc === '' && !empty($seoItem['meta_desc'])) {
            $desc = (string) $seoItem['meta_desc'];
        }
    }

    $titleSuffix = match ($langCode) {
        'ar' => ' — شرم الشيخ | Sea College',
        'ru' => ' — Шарм-эль-Шейх | Sea College',
        'de' => ' — Sharm El Sheikh | Sea College',
        'it' => ' — Sharm El Sheikh | Sea College',
        'fr' => ' — Charm el-Cheikh | Sea College',
        'pl' => ' — Sharm El Sheikh | Sea College',
        'zh' => ' — 沙姆沙伊赫 | Sea College',
        default => ' — Sharm El Sheikh | Sea College',
    };
    $fullTitle = mb_strlen($name . $titleSuffix) <= 150 ? ($name . $titleSuffix) : mb_substr($name, 0, 147) . '…';

    $gbpData = gbp_reviews_data();
    $gbpCountRaw = (int) ($gbpData['review_count'] ?? BUSINESS['review_count']);
    $gbpCntFmt = sc_gbp_format_count($gbpCountRaw, $langCode);
    $gbpRating = number_format((float) ($gbpData['rating'] ?? BUSINESS['rating']), 1, '.', '');
    $gbpRatingComma = str_replace('.', ',', $gbpRating);

    $trustTail = match ($langCode) {
        'ar' => "منتجع غوص PADI خمس نجوم في المنتزه، شرم الشيخ (تقييم {$gbpRating}★ من +{$gbpCntFmt} مراجعة) مع توصيل مجاني من الفنادق.",
        'ru' => "PADI 5★ дайв-резорт в Шарм-эль-Шейхе ({$gbpRating}★, {$gbpCountRaw}+ отзывов Google). Бесплатный трансфер из отелей.",
        'de' => "PADI 5★ Dive Resort in Sharm El Sheikh ({$gbpRatingComma}★ bei {$gbpCntFmt}+ Google-Bewertungen). Kostenloser Hoteltransfer inklusive.",
        'it' => "PADI 5★ Dive Resort a Sharm El Sheikh ({$gbpRatingComma}★ con {$gbpCntFmt}+ recensioni Google). Transfer gratuito dagli hotel.",
        'fr' => "Centre PADI 5★ à Charm el-Cheikh ({$gbpRatingComma}★ sur {$gbpCntFmt}+ avis Google). Transfert hôtel gratuit inclus.",
        'pl' => "Baza nurkowa PADI 5★ w Sharm El Sheikh ({$gbpRatingComma}★ i {$gbpCountRaw}+ opinii Google). Bezpłatny transfer z hotelu.",
        'zh' => "沙姆沙伊赫 PADI 五星潜水度假村（Google {$gbpRating}★，{$gbpCntFmt}+ 条好评），含市区酒店免费接送。",
        default => "Official PADI 5 Star Dive Resort in Sharm El Sheikh ({$gbpRating}★ from {$gbpCntFmt}+ Google reviews). Free Sharm hotel transfers on packages.",
    };

    $fullDesc = trim($desc . ($dur !== '' ? " ({$dur}). " : ' ') . $trustTail);
    $link = SITE_URL . page_link(item_path($it), '', $langCode);

    return [
        'title' => $fullTitle,
        'desc'  => mb_substr($fullDesc, 0, 4900),
        'link'  => $link,
    ];
}

@header_remove('X-Powered-By');
header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=300, s-maxage=900');

if ($mode === 'i18n') {
    // Meta Commerce Language Override XML Feed (maps item id + language code -> localized title, description, link)
    $metaLocaleMap = [
        'ru' => 'ru_RU',
        'it' => 'it_IT',
        'pl' => 'pl_PL',
        'de' => 'de_DE',
        'fr' => 'fr_XX',
        'ar' => 'ar_AR',
        'zh' => 'zh_CN',
    ];
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n";
    echo "  <channel>\n";
    echo '    <title>' . $xmlEsc(SITE_NAME . ' — Multilingual Catalog Override Feed') . "</title>\n";
    echo '    <link>' . $xmlEsc(SITE_URL) . "</link>\n";
    echo '    <description>Localized RU, IT, PL, DE, FR, AR, ZH overrides for Meta Advantage+ Catalog Ads</description>' . "\n";

    foreach ($catalog as $catKey => $cat) {
        foreach ($cat['groups'] as $group) {
            foreach ($group['items'] as $it) {
                if (!sc_meta_is_advertisable($it, $includeAddons)) {
                    continue;
                }
                $seoItem = $seoAll['items'][$it['id']] ?? [];
                foreach ($metaLocaleMap as $langCode => $fbLocale) {
                    $locData = sc_meta_localize_item($it, $seoItem, $langCode);
                    echo "    <item>\n";
                    echo '      <g:id>' . $xmlEsc((string) $it['id']) . "</g:id>\n";
                    echo '      <g:override>' . $xmlEsc($fbLocale) . "</g:override>\n";
                    echo '      <g:title>' . $xmlEsc($locData['title']) . "</g:title>\n";
                    echo '      <g:description>' . $xmlEsc($locData['desc']) . "</g:description>\n";
                    echo '      <g:link>' . $xmlEsc($locData['link']) . "</g:link>\n";
                    echo "    </item>\n";
                }
            }
        }
    }
    echo "  </channel>\n</rss>\n";
    exit;
}

// Primary (or single-language ?lang=xx) Meta & Google Merchant Product Catalog XML
$feedLang = LANG;
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n";
echo "  <channel>\n";
echo '    <title>' . $xmlEsc(SITE_NAME . ' — Trips, PADI Courses & Boats (' . strtoupper($feedLang) . ')') . "</title>\n";
echo '    <link>' . $xmlEsc(SITE_URL . page_link('/', '', $feedLang)) . "</link>\n";
echo '    <description>' . $xmlEsc('Official Sea College Dive Club Sharm El Sheikh catalog: PADI courses, value bundles, guided Red Sea boat dives, snorkeling & private yachts.') . "</description>\n";

foreach ($catalog as $catKey => $cat) {
    foreach ($cat['groups'] as $group) {
        foreach ($group['items'] as $it) {
            if (!sc_meta_is_advertisable($it, $includeAddons)) {
                continue;
            }
            $id      = (string) $it['id'];
            $seoItem = $seoAll['items'][$id] ?? [];
            $locData = sc_meta_localize_item($it, $seoItem, $feedLang);
            $segment = sc_meta_segment_label((string) $catKey, $it);

            $saleEur = (float) $it['price'];
            $saveEur = !empty($it['save']) && is_numeric($it['save']) ? (float) $it['save'] : 0.0;
            if (!empty($it['regular_price']) && is_numeric($it['regular_price']) && (float) $it['regular_price'] > $saleEur) {
                $baseEur = (float) $it['regular_price'];
                $saveEur = $baseEur - $saleEur;
            } else {
                $baseEur = $saveEur > 0 ? ($saleEur + $saveEur) : $saleEur;
            }

            $imgRel = !empty($it['img']) ? ('images/' . ltrim((string) $it['img'], '/')) : 'images/og-default.jpg';
            if (!is_file(ROOT_DIR . '/assets/' . $imgRel)) {
                $imgRel = 'images/og-default.jpg';
            }
            $mainImgUrl = SITE_URL . '/assets/' . $imgRel;

            $extraImgs = [];
            $galPhotos = isset($itemGalleries[$id]['photos']) && is_array($itemGalleries[$id]['photos'])
                ? $itemGalleries[$id]['photos']
                : (is_array($itemGalleries[$id] ?? null) ? $itemGalleries[$id] : []);
            foreach ($galPhotos as $gEntry) {
                $gSrc = is_array($gEntry) ? (string) ($gEntry['src'] ?? '') : (string) $gEntry;
                if ($gSrc === '' || str_ends_with(strtolower($gSrc), '.mp4')) {
                    continue;
                }
                $cleanGSrc = ltrim($gSrc, '/');
                $gRel = str_starts_with($cleanGSrc, 'images/') ? $cleanGSrc : ('images/' . $cleanGSrc);
                if ($gRel !== $imgRel && is_file(ROOT_DIR . '/assets/' . $gRel)) {
                    $extraImgs[] = SITE_URL . '/assets/' . $gRel;
                }
                if (count($extraImgs) >= 5) {
                    break;
                }
            }

            echo "    <item>\n";
            echo '      <g:id>' . $xmlEsc($id) . "</g:id>\n";
            echo '      <g:title>' . $xmlEsc($locData['title']) . "</g:title>\n";
            echo '      <g:description>' . $xmlEsc($locData['desc']) . "</g:description>\n";
            echo '      <g:link>' . $xmlEsc($locData['link']) . "</g:link>\n";
            echo '      <g:image_link>' . $xmlEsc($mainImgUrl) . "</g:image_link>\n";
            foreach ($extraImgs as $exUrl) {
                echo '      <g:additional_image_link>' . $xmlEsc($exUrl) . "</g:additional_image_link>\n";
            }
            echo "      <g:availability>in stock</g:availability>\n";
            echo "      <g:condition>new</g:condition>\n";
            echo '      <g:price>' . number_format($baseEur, 2, '.', '') . " EUR</g:price>\n";
            if ($saveEur > 0) {
                echo '      <g:sale_price>' . number_format($saleEur, 2, '.', '') . " EUR</g:sale_price>\n";
            }
            echo '      <g:brand>' . $xmlEsc(SITE_NAME) . "</g:brand>\n";
            echo '      <g:mpn>SC-' . $xmlEsc(strtoupper($id)) . "</g:mpn>\n";
            echo "      <g:identifier_exists>no</g:identifier_exists>\n";
            echo "      <g:google_product_category>Sporting Goods &gt; Outdoor Recreation &gt; Water Sports &gt; Scuba Diving &amp; Snorkeling</g:google_product_category>\n";
            echo '      <g:product_type>' . $xmlEsc($segment) . "</g:product_type>\n";
            echo '      <g:custom_label_0>' . $xmlEsc($segment) . "</g:custom_label_0>\n";
            echo '      <g:custom_label_1>' . (!empty($it['popular']) ? 'Popular' : 'Standard') . "</g:custom_label_1>\n";
            echo '      <g:custom_label_2>' . $xmlEsc(strtoupper($feedLang)) . "</g:custom_label_2>\n";
            if (!empty($it['duration'])) {
                echo '      <g:custom_label_3>' . $xmlEsc((string) $it['duration']) . "</g:custom_label_3>\n";
            }
            echo "    </item>\n";
        }
    }
}

echo "  </channel>\n</rss>\n";
