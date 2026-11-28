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
    return '/' . ltrim((string) ($cat['hub_slug'] ?? 'services'), '/');
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
        'priceValidUntil' => ((int) date('Y') + 1) . '-12-31',
        'seller'        => $orgRef,
        'hasMerchantReturnPolicy' => [
            '@type'                => 'MerchantReturnPolicy',
            'applicableCountry'    => 'EG',
            'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
            'merchantReturnDays'   => 2,
            'returnMethod'         => 'https://schema.org/ReturnAtKiosk',
            'returnFees'           => 'https://schema.org/FreeReturn',
        ],
    ];
    if ($it['price'] !== null) {
        $offer['price'] = (string) $it['price'];
    }

    $credMap = [
        'en' => ['PADI Certification / Training Credit', 'Scuba Diving Training'],
        'ru' => ['Международный сертификат PADI / Зачёт обучения', 'Обучение дайвингу PADI'],
        'it' => ['Brevetto Internazionale PADI / Credito Formativo', 'Formazione Subacquea PADI'],
        'de' => ['Offizielles PADI Tauchbrevet / Internationaler Tauchschein', 'PADI Tauchausbildung'],
        'pl' => ['Międzynarodowy Certyfikat PADI / Zaliczenie Szkolenia', 'Szkolenie Nurkowe PADI'],
        'fr' => ['Brevet International PADI / Crédit de Formation', 'Formation Plongée PADI'],
        'ar' => ['شهادة غوص بادي PADI دولية معتمدة', 'تدريب غوص معتمد'],
        'zh' => ['PADI 国际潜水认证 / 官方学分', 'PADI 水肺潜水培训'],
    ];
    $touristMap = [
        'en' => ['Scuba Divers', 'Snorkelers', 'Red Sea Travellers'],
        'ru' => ['Дайверы', 'Любители снорклинга и морских прогулок', 'Отдыхающие в Шарм-эль-Шейхе'],
        'it' => ['Subacquei', 'Amanti dello Snorkeling & Famiglie', 'Viaggiatori a Sharm El Sheikh'],
        'de' => ['Gerätetaucher', 'Schnuppertaucher & Einsteiger', 'Schnorchler & Familien'],
        'pl' => ['Płetwonurkowie', 'Miłośnicy Snurkowania i Rodziny', 'Turyści w Sharm El Sheikh'],
        'fr' => ['Plongeurs sous-marins', 'Amateurs de snorkeling & Familles', 'Voyageurs en Mer Rouge'],
        'ar' => ['الغواصون', 'عشاق السنوركلينج والرحلات البحرية', 'زوار شرم الشيخ والبحر الأحمر'],
        'zh' => ['水肺潜水员', '浮潜游客与家庭', '埃及红海旅行者'],
    ];
    $areaMap = [
        'en' => ['Sharm El Sheikh', 'Ras Mohammed', 'Straits of Tiran', 'South Sinai'],
        'ru' => ['Шарм-эль-Шейх', 'Заповедник Рас-Мохаммед', 'Остров и пролив Тиран', 'Южный Синай'],
        'it' => ['Sharm El Sheikh', 'Parco Nazionale di Ras Mohammed', 'Isola e Stretto di Tiran', 'Sud Sinai'],
        'de' => ['Sharm El Sheikh', 'Nationalpark Ras Mohammed', 'Straße von Tiran', 'Süd-Sinai'],
        'pl' => ['Sharm El Sheikh (Szarm el-Szejk)', 'Park Narodowy Ras Mohammed', 'Cieśnina Tiran', 'Południowy Synaj'],
        'fr' => ['Charm el-Cheikh (Sharm El Sheikh)', 'Parc National Ras Mohammed', 'Détroit de Tiran', 'Sud-Sinaï'],
        'ar' => ['شرم الشيخ', 'محمية رأس محمد', 'جزيرة ومضيق تيران', 'خليج نبق - منطقة الغرقانة'],
        'zh' => ['沙姆沙伊赫', '拉斯穆罕默德国家公园', '蒂朗海峡', '南西奈半岛'],
    ];
    $credPair = $credMap[LANG] ?? $credMap['en'];

    $primary = [
        '@type'       => $type,
        '@id'         => $fullUrl . '#entity',
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
            ? $credPair[0]
            : $credPair[1];
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
        $locDur = item_duration($it);
        if ($locDur !== '') {
            $inst['courseWorkload'] = $locDur;
        }
        $primary['hasCourseInstance'] = $inst;
    } elseif ($type === 'TouristTrip') {
        $primary['touristType'] = $touristMap[LANG] ?? $touristMap['en'];
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
        $primary['areaServed'] = $areaMap[LANG] ?? $areaMap['en'];
    }

    $nodes = [$primary];

    if ($it['price'] !== null && sc_is_catalog_item($it)) {
        $nodes[] = [
            '@type'       => 'Product',
            '@id'         => $fullUrl . '#product',
            'sku'         => $it['id'],
            'name'        => item_name($it),
            'description' => item_desc($it) ?: $desc,
            'image'       => count($images) > 1 ? $images : $imgUrl,
            'inLanguage'  => LANG,
            'category'    => 'Sporting Goods > Outdoor Recreation > Water Sports > Scuba Diving & Snorkeling',
            'brand'       => ['@type' => 'Brand', 'name' => SITE_NAME],
            'offers'      => $offer,
            'aggregateRating' => [
                '@type'       => 'AggregateRating',
                'ratingValue' => (string) ($gbp['rating'] ?? BUSINESS['rating']),
                'bestRating'  => '5',
                'worstRating' => '1',
                'reviewCount' => (string) ($gbp['review_count'] ?? BUSINESS['review_count']),
            ],
        ];
    }

    if (!empty($gallery['video']['src'])) {
        $vid = $gallery['video'];
        $isoDur = 'PT30S';
        if (!empty($vid['dur']) && preg_match('/^(?:(\d+):)?(\d+)$/', trim((string) $vid['dur']), $dm)) {
            $mins = (int) ($dm[1] ?? 0);
            $secs = (int) $dm[2];
            $isoDur = 'PT' . ($mins > 0 ? $mins . 'M' : '') . $secs . 'S';
        }
        $nodes[] = [
            '@type'        => 'VideoObject',
            '@id'          => $fullUrl . '#video',
            'name'         => $name . ' — ' . SITE_NAME,
            'description'  => $desc,
            'thumbnailUrl' => [url('assets/' . ltrim((string) ($vid['poster'] ?? ('images/' . ($it['img'] ?? 'hero.jpg'))), '/'))],
            'contentUrl'   => url('assets/' . ltrim((string) $vid['src'], '/')),
            'uploadDate'   => '2026-01-15T08:00:00+02:00',
            'duration'     => $isoDur,
            'publisher'    => $orgRef,
        ];
    }

    if (!empty($res['faqs'])) {
        $nodes[] = schema_faq_pairs($res['faqs']);
    }

    return $nodes;
}
