<?php
/** Small render helpers shared by pages (cards, price rows, FAQ, reviews). */
declare(strict_types=1);

require_once __DIR__ . '/google_reviews.php';

function price_html(?float $eur, string $unit = ''): string
{
    if ($eur === null) {
        return '<span class="price price--req">' . te('price_on_request') . '</span>';
    }
    $suffix = match ($unit) {
        'day' => ' <small>' . te('per_day') . '</small>',
        'boat' => ' <small>' . te('per_boat') . '</small>',
        default => '',
    };
    return '<span class="price" data-eur="' . e((string) $eur) . '"><bdi dir="ltr">' . e(money($eur)) . '</bdi></span>' . $suffix;
}

function add_button(array $it): string
{
    return '<button type="button" class="btn btn--add" data-add-to-cart'
        . ' data-id="' . e($it['id']) . '" data-name="' . e($it['name']) . '"'
        . ' data-price="' . e($it['price'] === null ? '' : (string) $it['price']) . '"'
        . ' aria-label="' . te('add_to_booking') . ': ' . e($it['name']) . '">+ ' . te('add_to_booking') . '</button>';
}

/** Experience/product card used on home & services. */
function product_card(array $it, string $image = 'images/hero.jpg'): string
{
    $img = !empty($it['img']) ? 'images/' . $it['img'] : $image;
    $child = isset($it['child_price']) ? ' <small>· ' . te('child') . ' <bdi dir="ltr">' . e(money($it['child_price'])) . '</bdi></small>' : '';
    $html = '<article class="pcard">';
    $html .= '<div class="pcard__media"><img src="' . e(asset($img)) . '" alt="' . e($it['name']) . '" loading="lazy" width="400" height="300">';
    if (!empty($it['popular'])) {
        $html .= '<span class="pcard__badge">' . te('popular') . '</span>';
    }
    $html .= '</div><div class="pcard__body">';
    $html .= '<p class="pcard__meta">📍 ' . te('location_sharm') . (!empty($it['duration']) ? ' · ⏱ ' . e($it['duration']) : '') . '</p>';
    $html .= '<h3 class="pcard__title" dir="auto">' . e($it['name']) . '</h3>';
    if (!empty($it['desc'])) {
        $html .= '<p class="pcard__desc" dir="auto">' . e($it['desc']) . '</p>';
    }
    $html .= '<div class="pcard__foot"><span class="pcard__price">' . te('from') . ' ' . price_html($it['price'], $it['unit'] ?? '') . $child . '</span>' . add_button($it) . '</div>';
    $html .= '</div></article>';
    return $html;
}

function faq_list(array $nums): string
{
    $html = '<div class="faq">';
    foreach ($nums as $n) {
        $html .= '<details class="faq__item"><summary>' . te("faq_{$n}_q") . '</summary><p>' . te("faq_{$n}_a") . '</p></details>';
    }
    return $html . '</div>';
}

/** Find catalog items by id (keeps order). */
function catalog_pick(array $catalog, array $ids): array
{
    $flat = [];
    foreach ($catalog as $cat) {
        foreach ($cat['groups'] as $g) {
            foreach ($g['items'] as $it) {
                $flat[$it['id']] = $it;
            }
        }
    }
    return array_values(array_filter(array_map(static fn($id) => $flat[$id] ?? null, $ids)));
}

/** Schema.org OfferCatalog for a catalog category. */
function schema_offer_catalog(string $name, array $cat): array
{
    $offers = [];
    foreach ($cat['groups'] as $g) {
        foreach ($g['items'] as $it) {
            if ($it['price'] === null) {
                continue;
            }
            $offers[] = [
                '@type' => 'Offer',
                'price' => (string) $it['price'],
                'priceCurrency' => 'EUR',
                'availability' => 'https://schema.org/InStock',
                'itemOffered' => ['@type' => 'Service', 'name' => $it['name'], 'description' => $it['desc'] ?: $it['name'],
                    'provider' => ['@id' => SITE_URL . '/#organization'], 'areaServed' => 'Sharm El Sheikh'],
            ];
        }
    }
    return ['@type' => 'OfferCatalog', 'name' => $name, 'itemListElement' => $offers];
}

/** Internal link that keeps the active language (?lang=xx for non-default). */
function page_link(string $path, string $hash = ''): string
{
    $q = LANG !== DEFAULT_LANG ? '?lang=' . LANG : '';
    return $path . $q . ($hash !== '' ? '#' . $hash : '');
}

/** Asset path of a branded, SEO-named photo (see assets/images/). */
function seo_img(string $slug): string
{
    return 'images/sea-college-dive-club-sharm-el-sheikh-' . $slug . '.jpg';
}

/** Section heading block. */
function section_head(string $titleKey, string $subKey = '', string $id = ''): string
{
    $html = '<header class="section__head">';
    $html .= '<h2' . ($id !== '' ? ' id="' . e($id) . '"' : '') . '>' . te($titleKey) . '</h2>';
    if ($subKey !== '') {
        $html .= '<p>' . te($subKey) . '</p>';
    }
    return $html . '</header>';
}
