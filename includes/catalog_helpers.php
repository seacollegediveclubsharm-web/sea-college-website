<?php
/**
 * Catalog & SEO/AEO lookup and structured-data helpers for item.php, category.php,
 * services.php, prices.php, index.php and sitemap.php.
 */
declare(strict_types=1);

function item_name(array $it): string
{
    if (LANG === 'ar' && !empty($it['name_ar'])) {
        return (string) $it['name_ar'];
    }
    return (string) $it['name'];
}

function item_desc(array $it): string
{
    if (LANG === 'ar' && !empty($it['desc_ar'])) {
        return (string) $it['desc_ar'];
    }
    return (string) ($it['desc'] ?? '');
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
 * Build Schema.org FAQPage node from [[q_en, a_en, q_ar, a_ar], ...] tuples.
 */
function schema_faq_pairs(array $faqs): array
{
    $entities = [];
    foreach ($faqs as $f) {
        $q = LANG === 'ar' && !empty($f[2]) ? $f[2] : $f[0];
        $a = LANG === 'ar' && !empty($f[3]) ? $f[3] : $f[1];
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
function schema_item_detail(array $it, array $seo, string $catLabel, array $gbp): array
{
    $orgRef  = ['@id' => SITE_URL . '/#organization'];
    $fullUrl = canonical_url();
    $imgUrl  = url('assets/images/' . ($it['img'] ?? 'hero.jpg'));
    $name    = loc($seo['h1'] ?? null) ?: item_name($it);
    $desc    = loc($seo['aeo_answer'] ?? null) ?: item_desc($it);
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
        'image'       => $imgUrl,
        'provider'    => $orgRef,
    ];
    if ($it['price'] !== null) {
        $primary['offers'] = $offer;
    }

    if ($type === 'Course') {
        $primary['educationalCredentialAwarded'] = str_contains($it['name'], 'PADI') || ($it['section'] ?? '') === 'courses'
            ? 'PADI Certification / Training Credit'
            : 'Diving Training';
        $primary['availableLanguage'] = ['en', 'ar', 'ru', 'de', 'it', 'fr'];
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
        if (!empty($seo['steps'])) {
            $stepList = [];
            $pos = 1;
            foreach ($seo['steps'] as $st) {
                $stepList[] = [
                    '@type'       => 'ListItem',
                    'position'    => $pos++,
                    'name'        => LANG === 'ar' ? $st[1] : $st[0],
                    'description' => LANG === 'ar' ? $st[3] : $st[2],
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
            'image'       => $imgUrl,
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

    if (!empty($seo['faqs'])) {
        $nodes[] = schema_faq_pairs($seo['faqs']);
    }

    return $nodes;
}
