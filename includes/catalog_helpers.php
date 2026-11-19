<?php
/**
 * Catalog & SEO/AEO lookup and structured-data helpers for item.php, category.php,
 * services.php, prices.php, index.php and sitemap.php.
 */
declare(strict_types=1);

require_once __DIR__ . '/i18n_data.php';

function item_name(array $it): string
{
    $k = 'name_' . LANG;
    if (LANG !== 'en' && !empty($it[$k])) {
        return (string) $it[$k];
    }
    return (string) $it['name'];
}

function item_desc(array $it): string
{
    $k = 'desc_' . LANG;
    if (LANG !== 'en' && isset($it[$k]) && $it[$k] !== '') {
        return (string) $it[$k];
    }
    return (string) ($it['desc'] ?? '');
}

function item_duration(array $it): string
{
    $k = 'duration_' . LANG;
    if (LANG !== 'en' && !empty($it[$k])) {
        return (string) $it[$k];
    }
    return (string) ($it['duration'] ?? '');
}

function item_path(array $it): string
{
    $sec = ($it['section'] ?? 'tours') === 'courses' ? 'courses' : 'tours';
    return '/' . $sec . '/' . $it['slug'];
}

function item_url(array $it): string
{
    return page_link(item_path($it));
}

function hub_path(array $cat): string
{
    return '/' . ltrim((string) ($cat['hub_slug'] ?? 'services.php'), '/');
}

function hub_url(array $cat): string
{
    return page_link(hub_path($cat));
}

/**
 * Flatten every item in the catalog with category & group context attached.
 * @return array<string, array>
 */
function catalog_all_items(array $catalog): array
{
    $out = [];
    foreach ($catalog as $catKey => $cat) {
        foreach ($cat['groups'] as $g) {
            foreach ($g['items'] as $it) {
                $it['_cat_key']   = $catKey;
                $it['_cat']       = $cat;
                $it['_group']     = $g;
                $it['_compact']   = !empty($g['compact']);
                $out[$it['id']]   = $it;
            }
        }
    }
    return $out;
}

function catalog_find_by_slug(array $catalog, string $slug): ?array
{
    foreach (catalog_all_items($catalog) as $it) {
        if (($it['slug'] ?? '') === $slug) {
            return $it;
        }
    }
    return null;
}

function catalog_find_hub(array $catalog, string $hubSlug): ?array
{
    foreach ($catalog as $catKey => $cat) {
        if (($cat['hub_slug'] ?? '') === $hubSlug) {
            return ['key' => $catKey, 'cat' => $cat];
        }
    }
    return null;
}

function catalog_related(array $catalog, array $current, array $ids, int $limit = 4): array
{
    $all = catalog_all_items($catalog);
    $picked = [];
    foreach ($ids as $id) {
        if ($id !== $current['id'] && isset($all[$id])) {
            $picked[$id] = $all[$id];
        }
        if (count($picked) >= $limit) {
            break;
        }
    }
    if (count($picked) < $limit) {
        foreach ($all as $id => $cand) {
            if ($id === $current['id'] || isset($picked[$id])) {
                continue;
            }
            if ($cand['_cat_key'] === $current['_cat_key'] && empty($cand['_compact'])) {
                $picked[$id] = $cand;
                if (count($picked) >= $limit) {
                    break;
                }
            }
        }
    }
    return array_values($picked);
}

/**
 * Build Schema.org FAQPage node from normalized or multi-language FAQ tuples.
 */
function schema_faq_pairs(array $faqs, ?array $i18nMap = null): array
{
    $entities = [];
    foreach (sc_resolve_faqs($faqs, $i18nMap) as [$q, $a]) {
        $entities[] = [
            '@type'          => 'Question',
            'name'           => $q,
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a],
        ];
    }
    return ['@type' => 'FAQPage', 'mainEntity' => $entities];
}

/**
 * Rich Schema.org entity (Course / TouristTrip / Service) + Product/Offer for an item detail page.
 * @return array<int, array>
 */
function schema_item_detail(array $it, array $seo, string $catLabel, array $gbp, ?array $resolved = null, ?array $gallery = null): array
{
    $res     = $resolved ?? sc_resolve_item_seo($it, $seo);
    $orgRef  = ['@id' => SITE_URL . '/#organization'];
    $fullUrl = canonical_url();
    $imgUrl  = url('assets/images/' . ($it['img'] ?? 'hero.jpg'));
    $images  = [$imgUrl];
    if (!empty($gallery['photos']) && is_array($gallery['photos'])) {
        foreach ($gallery['photos'] as $gp) {
            $u = url('assets/' . ltrim((string) $gp, '/'));
            if (!in_array($u, $images, true)) {
                $images[] = $u;
            }
        }
    }
    $name    = $res['h1'] ?: item_name($it);
    $desc    = $res['aeo_answer'] ?: item_desc($it);
    $type    = $seo['schema_type'] ?? (($it['section'] ?? 'tours') === 'courses' ? 'Course' : 'TouristTrip');

    $offer = [
        '@type'         => 'Offer',
        'url'           => $fullUrl,
        'priceCurrency' => 'EUR',
        'availability'  => 'https://schema.org/InStock',
        'seller'        => $orgRef,
    ];
    if ($it['price'] !== null) {
        $offer['price'] = (string) $it['price'];
    }

    $primary = [
        '@type'       => $type,
        '@id'         => SITE_URL . item_path($it) . '#entity',
        'name'        => $name,
        'description' => $desc,
        'url'         => $fullUrl,
        'image'       => count($images) > 1 ? $images : $imgUrl,
        'inLanguage'  => LANG,
        'provider'    => $orgRef,
    ];
    if ($it['price'] !== null) {
        $primary['offers'] = $offer;
    }

    if ($type === 'Course') {
        $primary['educationalCredentialAwarded'] = str_contains($it['name'], 'PADI') || ($it['section'] ?? '') === 'courses'
            ? 'PADI Certification / Training Credit'
            : 'Diving Training';
        $primary['availableLanguage'] = array_keys(SUPPORTED_LANGS);
        $inst = [
            '@type'      => 'CourseInstance',
            'courseMode' => 'onsite',
            'location'   => [
                '@type'   => 'Place',
                'name'    => SITE_NAME . ' — Cyrene Sharm, Al Montazah',
                'address' => BUSINESS['street'] . ', ' . BUSINESS['city'],
            ],
        ];
        if (!empty($it['duration'])) {
            $inst['courseWorkload'] = (string) $it['duration'];
        }
        $primary['hasCourseInstance'] = $inst;
    } elseif ($type === 'TouristTrip') {
        $primary['touristType'] = ['Scuba Divers', 'Snorkelers', 'Red Sea Travellers'];
        if (!empty($res['steps'])) {
            $stepList = [];
            $pos = 1;
            foreach ($res['steps'] as [$stName, $stDesc]) {
                $stepList[] = [
                    '@type'       => 'ListItem',
                    'position'    => $pos++,
                    'name'        => $stName,
                    'description' => $stDesc,
                ];
            }
            $primary['itinerary'] = ['@type' => 'ItemList', 'itemListElement' => $stepList];
        }
    } else {
        $primary['category']   = $catLabel;
        $primary['areaServed'] = ['Sharm El Sheikh', 'Ras Mohammed', 'Straits of Tiran', 'South Sinai'];
    }

    $nodes = [$primary];

    if ($it['price'] !== null) {
        $nodes[] = [
            '@type'       => 'Product',
            '@id'         => SITE_URL . item_path($it) . '#product',
            'sku'         => $it['id'],
            'name'        => item_name($it),
            'description' => item_desc($it) ?: $desc,
            'image'       => count($images) > 1 ? $images : $imgUrl,
            'category'    => $catLabel,
            'brand'       => ['@type' => 'Brand', 'name' => SITE_NAME],
            'offers'      => $offer,
            'aggregateRating' => [
                '@type'       => 'AggregateRating',
                'ratingValue' => (string) ($gbp['rating'] ?? '4.9'),
                'bestRating'  => '5',
                'worstRating' => '1',
                'reviewCount' => (string) ($gbp['review_count'] ?? '3082'),
            ],
        ];
    }

    if (!empty($gallery['video']['src'])) {
        $vid = $gallery['video'];
        $nodes[] = [
            '@type'        => 'VideoObject',
            '@id'          => SITE_URL . item_path($it) . '#video',
            'name'         => $name . ' — ' . SITE_NAME,
            'description'  => $desc,
            'thumbnailUrl' => [url('assets/' . ltrim((string) ($vid['poster'] ?? ('images/' . ($it['img'] ?? 'hero.jpg'))), '/'))],
            'contentUrl'   => url('assets/' . ltrim((string) $vid['src'], '/')),
            'uploadDate'   => '2026-01-15T08:00:00+02:00',
            'publisher'    => $orgRef,
        ];
    }

    if (!empty($res['faqs'])) {
        $nodes[] = schema_faq_pairs($res['faqs']);
    }

    return $nodes;
}
