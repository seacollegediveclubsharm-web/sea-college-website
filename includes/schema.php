<?php
/**
 * JSON-LD structured data. Always prints Organization/LocalBusiness + WebSite;
 * pages add extra nodes through $page['schema'] (FAQPage, OfferCatalog…) and
 * $page['breadcrumbs'] ([label, path] pairs).
 */
declare(strict_types=1);

$sc_org_id = SITE_URL . '/#organization';
$sc_sameAs = array_values(array_filter([...array_values(SOCIAL), PADI_PROFILE_URL, MAPS_URL]));

$sc_business = [
    '@type' => ['LocalBusiness', 'SportsActivityLocation'],
    '@id' => $sc_org_id,
    'name' => SITE_NAME,
    'description' => t('meta_home_desc'),
    'url' => SITE_URL . '/',
    'logo' => url('assets/images/logo.jpg'),
    'image' => url('assets/images/og-default.jpg'),
    'telephone' => BUSINESS['phone'],
    'email' => BUSINESS['email'],
    'priceRange' => BUSINESS['price_range'],
    'currenciesAccepted' => 'EUR, USD, EGP',
    'paymentAccepted' => 'Cash, Credit Card, Debit Card, Bank Transfer',
    'openingHours' => BUSINESS['hours'],
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => BUSINESS['street'],
        'addressLocality' => BUSINESS['city'],
        'addressRegion' => BUSINESS['region'],
        'addressCountry' => BUSINESS['country'],
    ],
    'hasMap' => MAPS_URL,
    'award' => BUSINESS['padi_level'],
    'knowsLanguage' => ['en', 'ar', 'ru', 'de', 'it', 'fr'],
    'sameAs' => $sc_sameAs,
];
if (BUSINESS['lat'] !== null && BUSINESS['lng'] !== null) {
    $sc_business['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => BUSINESS['lat'], 'longitude' => BUSINESS['lng']];
}
require_once __DIR__ . '/google_reviews.php';
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
    ],
];

if (!empty($page['breadcrumbs'])) {
    $items = [];
    $pos = 1;
    foreach ([[t('nav_home'), '/'], ...$page['breadcrumbs']] as [$label, $path]) {
        $items[] = ['@type' => 'ListItem', 'position' => $pos++, 'name' => $label, 'item' => url($path)];
    }
    $sc_graph[] = ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
}

foreach ($page['schema'] ?? [] as $node) {
    $sc_graph[] = $node;
}

echo '<script type="application/ld+json">'
    . json_encode(['@context' => 'https://schema.org', '@graph' => $sc_graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP)
    . '</script>' . "\n";
