<?php
/**
 * JSON-LD structured data. Always prints Organization/LocalBusiness + WebSite;
 * pages add extra nodes through $page['schema'] (FAQPage, OfferCatalog…) and
 * $page['breadcrumbs'] ([label, path] pairs).
 */
declare(strict_types=1);

$sc_org_id = SITE_URL . '/#organization';
$sc_sameAs = array_values(array_unique(array_filter([
    ...array_values(SOCIAL),
    'https://wa.me/' . preg_replace('/\D/', '', WHATSAPP_NUMBER),
    PADI_PROFILE_URL,
    CDWS_PROFILE_URL,
    MAPS_URL,
])));

$sc_alt_names = [
    'en' => 'Sea College Dive Club — PADI 5 Star Dive Resort Sharm El Sheikh',
    'ru' => 'Sea College Dive Club — Русскоязычный дайвинг-центр PADI 5★ Шарм-эль-Шейх',
    'it' => 'Sea College Dive Club — Centro Diving PADI 5 Stelle Sharm El Sheikh',
    'de' => 'Sea College Dive Club — PADI 5 Sterne Tauchbasis & Tauchschule Sharm El Sheikh',
    'pl' => 'Sea College Dive Club — Baza i Centrum Nurkowe PADI 5★ Sharm El Sheikh',
    'fr' => 'Sea College Dive Club — Club de Plongée Francophone PADI 5★ Charm el-Cheikh',
    'ar' => 'سي كوليدج دايف كلوب — مركز غوص وغطس بادي PADI 5★ شرم الشيخ',
    'zh' => 'Sea College Dive Club — 埃及红海沙姆沙伊赫 PADI 五星潜水中心',
];

$sc_business = [
    '@type' => ['LocalBusiness', 'SportsActivityLocation'],
    '@id' => $sc_org_id,
    'name' => SITE_NAME,
    'alternateName' => $sc_alt_names[LANG] ?? $sc_alt_names['en'],
    'description' => t('meta_home_desc'),
    'url' => SITE_URL . '/',
    'logo' => url('assets/images/logo.jpg'),
    'image' => [
        url('assets/images/hero.jpg'),
        url('assets/images/center/sea-college-dive-club-center-01-entrance-al-montazah-nov2026.jpg'),
        url('assets/images/fleet/sea-college-oceano-yacht-01-exterior-nov2026.jpg'),
    ],
    'telephone' => BUSINESS['phone'],
    'email' => BUSINESS['email'],
    'contactPoint' => [
        '@type' => 'ContactPoint',
        'telephone' => BUSINESS['phone'],
        'contactType' => 'customer support',
        'url' => 'https://wa.me/' . preg_replace('/\D/', '', WHATSAPP_NUMBER),
        'availableLanguage' => array_keys(SUPPORTED_LANGS),
    ],
    'priceRange' => BUSINESS['price_range'],
    'currenciesAccepted' => implode(', ', array_keys(CURRENCY_RATES)),
    'paymentAccepted' => 'Cash, Credit Card, Debit Card, Bank Transfer',
    'openingHours' => BUSINESS['hours'],
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => BUSINESS['street'],
        'addressLocality' => BUSINESS['city'],
        'addressRegion' => BUSINESS['region'],
        'postalCode' => BUSINESS['postal_code'] ?? '46619',
        'addressCountry' => BUSINESS['country'],
    ],
    'hasMap' => MAPS_URL,
    'award' => t('padi_badge') !== 'padi_badge' ? t('padi_badge') : BUSINESS['padi_level'],
    'knowsLanguage' => array_keys(SUPPORTED_LANGS),
    'sameAs' => $sc_sameAs,
];
if (BUSINESS['lat'] !== null && BUSINESS['lng'] !== null) {
    $sc_business['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => BUSINESS['lat'], 'longitude' => BUSINESS['lng']];
}
require_once __DIR__ . '/google_reviews.php';
require_once __DIR__ . '/tripadvisor_reviews.php';
$sc_gbp = gbp_reviews_data();
$sc_business['aggregateRating'] = [
    '@type'       => 'AggregateRating',
    'ratingValue' => (string) $sc_gbp['rating'],
    'bestRating'  => '5',
    'worstRating' => '1',
    'reviewCount' => (string) $sc_gbp['review_count'],
];
if (!empty($sc_gbp['reviews'])) {
    $sc_business['review'] = array_map(static fn(array $r): array => [
        '@type'        => 'Review',
        'author'       => ['@type' => 'Person', 'name' => (string) $r['author']],
        'reviewRating' => ['@type' => 'Rating', 'ratingValue' => (string) ($r['rating'] ?? 5), 'bestRating' => '5'],
        'reviewBody'   => (string) $r['text'],
    ], array_slice($sc_gbp['reviews'], 0, 5));
}

$sc_graph = [
    $sc_business,
    [
        '@type' => 'WebSite',
        '@id' => SITE_URL . '/#website',
        'url' => SITE_URL . '/',
        'name' => SITE_NAME,
        'inLanguage' => array_keys(SUPPORTED_LANGS),
        'publisher' => ['@id' => $sc_org_id],
        'copyrightHolder' => ['@id' => $sc_org_id],
        'copyrightYear' => (int) date('Y'),
        'license' => SITE_URL . '/terms#copyright',
    ],
];

if (!empty($page['breadcrumbs'])) {
    $crumbs = $page['breadcrumbs'];
    if (($crumbs[0][1] ?? '') !== '/') {
        array_unshift($crumbs, [t('nav_home'), '/']);
    }
    $items = [];
    $pos = 1;
    foreach ($crumbs as [$label, $path]) {
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $pos++,
            'name'     => $label,
            'item'     => SITE_URL . page_link($path),
        ];
    }
    $sc_graph[] = ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
}

if (!empty($page['speakable'])) {
    $sc_graph[] = [
        '@type'      => 'WebPage',
        '@id'        => canonical_url() . '#webpage',
        'url'        => canonical_url(),
        'name'       => $page['title'] ?? SITE_NAME,
        'inLanguage' => LANG,
        'isPartOf'   => ['@id' => SITE_URL . '/#website'],
        'about'      => ['@id' => $sc_org_id],
        'speakable'  => [
            '@type'       => 'SpeakableSpecification',
            'cssSelector' => (array) $page['speakable'],
        ],
    ];
}

foreach ($page['schema'] ?? [] as $node) {
    $sc_graph[] = $node;
}

echo '<script type="application/ld+json">'
    . json_encode(['@context' => 'https://schema.org', '@graph' => $sc_graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP)
    . '</script>' . "\n";
