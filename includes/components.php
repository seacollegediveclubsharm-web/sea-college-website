<?php
/** Small render helpers shared by pages (cards, compact rows, breadcrumbs, comparison tables, FAQ, schema). */
declare(strict_types=1);

require_once __DIR__ . '/google_reviews.php';
require_once __DIR__ . '/tripadvisor_reviews.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/catalog_helpers.php';

function price_html(int|float|null $eur, string $unit = ''): string
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
    $disp = item_name($it);
    return '<button type="button" class="btn btn--add" data-add-to-cart'
        . ' data-id="' . e($it['id']) . '" data-name="' . e($disp) . '"'
        . ' data-price="' . e($it['price'] === null ? '' : (string) $it['price']) . '"'
        . ' aria-label="' . te('add_to_booking') . ': ' . e($disp) . '">+ ' . te('add_to_booking') . '</button>';
}

/** Experience/product card used on home, services, category hubs & related items. */
function product_card(array $it, string $image = 'images/hero.jpg', bool $eager = false): string
{
    $img = !empty($it['img']) ? 'images/' . $it['img'] : $image;
    $href = !empty($it['slug']) ? item_url($it) : page_link('/prices');
    $title = item_name($it);
    $desc = item_desc($it);
    $dur = item_duration($it);
    $searchText = mb_strtolower($title . ' ' . $desc . ' ' . $it['name'] . ' ' . ($it['desc'] ?? ''));
    $child = isset($it['child_price']) ? ' <small>· ' . te('child') . ' <bdi dir="ltr">' . e(money($it['child_price'])) . '</bdi></small>' : '';
    $save = !empty($it['save']) ? '<span class="pcard__save">' . te('save') . ' <bdi dir="ltr">' . e(money($it['save'])) . '</bdi></span>' : '';

    $html = '<article class="pcard" data-filter-item="' . e($searchText) . '">';
    $html .= '<a class="pcard__media" href="' . e($href) . '" tabindex="-1" aria-hidden="true">';
    $html .= sc_picture($img, $title . ' — ' . t('location_sharm'), 400, 300, [
        'loading' => $eager ? 'eager' : 'lazy',
        'sizes' => '(max-width: 599px) 78vw, 240px',
    ]);
    if (!empty($it['popular'])) {
        $html .= '<span class="pcard__badge">' . te('popular') . '</span>';
    }
    $html .= $save . '</a>';
    $html .= '<div class="pcard__body">';
    $html .= '<p class="pcard__meta">📍 ' . te('location_sharm') . ($dur !== '' ? ' · ⏱ ' . e($dur) : '') . '</p>';
    $html .= '<h3 class="pcard__title" dir="auto"><a class="pcard__link" href="' . e($href) . '">' . e($title) . '</a></h3>';
    if ($desc !== '') {
        $html .= '<p class="pcard__desc" dir="auto">' . e($desc) . '</p>';
    }
    $html .= '<div class="pcard__foot">';
    $html .= '<span class="pcard__price">' . ($it['price'] !== null ? te('from') . ' ' : '') . price_html($it['price'], $it['unit'] ?? '') . $child . '</span>';
    $html .= '<span class="pcard__actions"><a class="btn btn--detail btn--sm" href="' . e($href) . '">' . te('cta_details') . '</a>' . add_button($it) . '</span>';
    $html .= '</div></div></article>';
    return $html;
}

/** Compact row card for add-ons, equipment rentals, daily fees & insurance tiers. */
function compact_item_row(array $it): string
{
    $href = !empty($it['slug']) ? item_url($it) : page_link('/prices');
    $title = item_name($it);
    $desc = item_desc($it);
    $searchText = mb_strtolower($title . ' ' . $desc . ' ' . $it['name'] . ' ' . ($it['desc'] ?? ''));

    $html = '<div class="crow" data-filter-item="' . e($searchText) . '">';
    $html .= '<div class="crow__info">';
    $html .= '<a class="crow__title" href="' . e($href) . '" dir="auto">' . e($title) . '</a>';
    if ($desc !== '') {
        $html .= '<small class="crow__desc" dir="auto">' . e($desc) . '</small>';
    }
    $html .= '</div>';
    $html .= '<div class="crow__meta">';
    $html .= '<span class="crow__price">' . price_html($it['price'], $it['unit'] ?? '') . '</span>';
    $html .= '<a class="btn btn--detail btn--sm" href="' . e($href) . '">' . te('cta_details') . '</a>';
    $html .= add_button($it);
    $html .= '</div></div>';
    return $html;
}

/** Visible semantic breadcrumb navigation matching Schema.org BreadcrumbList. */
function breadcrumbs_html(array $crumbs, bool $onDark = true): string
{
    if (!$crumbs) {
        return '';
    }
    if (($crumbs[0][1] ?? '') !== '/') {
        array_unshift($crumbs, [t('breadcrumb_home'), '/']);
    }
    $last = count($crumbs) - 1;
    $html = '<nav class="crumbs' . ($onDark ? ' crumbs--dark' : '') . '" aria-label="Breadcrumb"><ol>';
    foreach ($crumbs as $i => [$label, $path]) {
        if ($i === $last) {
            $html .= '<li aria-current="page"><span>' . e($label) . '</span></li>';
        } else {
            $html .= '<li><a href="' . e(page_link($path)) . '">' . e($label) . '</a></li>';
        }
    }
    $html .= '</ol></nav>';
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

function faq_pairs_html(array $faqs, ?array $i18nMap = null): string
{
    $resolved = sc_resolve_faqs($faqs, $i18nMap);
    $html = '<div class="faq">';
    foreach ($resolved as $idx => [$q, $a]) {
        $open = $idx === 0 ? ' open' : '';
        $html .= '<details class="faq__item"' . $open . '><summary dir="auto">' . e($q) . '</summary><p dir="auto">' . e($a) . '</p></details>';
    }
    return $html . '</div>';
}

/** Contextual comparison table for packages, bundles, speedboats, charters, equipment & intro dives. */
function comparison_table_html(string $mode, array $catalog, string $currentId): string
{
    if ($mode === '' || $mode === 'none') {
        return '';
    }
    $map = [
        'packages'   => ['compare_packages_title', ['dive-1', 'dive-2', 'dive-3', 'dive-4', 'dive-5', 'dive-6', 'dive-8', 'dive-10', 'dive-extra']],
        'bundles'    => ['compare_bundles_title', ['bundle-stars', 'bundle-super', 'bundle-mega', 'bundle-heros']],
        'speedboats' => ['compare_speedboats_title', ['speed-1h', 'speed-2h', 'speed-3h']],
        'charters'   => ['compare_charters_title', ['private-1-5', 'private-6-10', 'private-11-15', 'private-16-20']],
        'equipment'  => ['compare_equipment_title', ['eq-full', 'eq-bcd', 'eq-reg', 'eq-wetsuit', 'eq-computer', 'eq-torch', 'eq-12nx', 'eq-15nx', 'eq-15air', 'eq-sm-full']],
        'intro'      => ['compare_intro_title', ['dsd-quick-reef', 'dsd-2-reef', 'dsd-quick-boat', 'dsd-2-boat', 'dsd']],
    ];
    if (!isset($map[$mode])) {
        return '';
    }
    [$titleKey, $ids] = $map[$mode];
    if (!in_array($currentId, $ids, true)) {
        $ids[] = $currentId;
    }
    $rows = catalog_pick($catalog, $ids);
    if (!$rows) {
        return '';
    }

    $html = '<div class="cmp-block">';
    $html .= '<h2>' . te($titleKey) . '</h2>';
    $html .= '<div class="ptable-wrap"><table class="ptable cmp-table"><thead><tr>';
    $html .= '<th>' . te('compare_col_option') . '</th>';
    $html .= '<th>' . te('compare_col_duration') . '</th>';
    $html .= '<th>' . te('compare_col_price') . '</th>';
    $html .= '<th>' . te('compare_col_rate') . '</th>';
    $html .= '</tr></thead><tbody>';

    foreach ($rows as $r) {
        $isCur = $r['id'] === $currentId;
        $note = '—';
        if ($mode === 'packages' && preg_match('/^dive-(\d+)$/', $r['id'], $m)) {
            $d = (int) $m[1];
            $per = round($r['price'] / $d, 2);
            $fmtPer = ($per == (int) $per) ? (string) (int) $per : number_format($per, 2);
            $note = '<bdi dir="ltr">€' . e($fmtPer) . '</bdi> ' . te('per_dive_unit');
        } elseif ($r['id'] === 'dive-extra') {
            $note = '<bdi dir="ltr">€30</bdi> ' . te('per_dive_unit') . ' (11+)';
        } elseif (!empty($r['save'])) {
            $note = '<span class="save">' . te('save') . ' <bdi dir="ltr">' . e(money($r['save'])) . '</bdi></span>';
        } elseif ($mode === 'charters' && preg_match('/-(\d+)$/', $r['id'], $m)) {
            $maxPax = (int) $m[1];
            $fromPp = (int) ceil($r['price'] / $maxPax);
            $note = te('from') . ' ~<bdi dir="ltr">€' . $fromPp . '</bdi> ' . te('per_person');
        } elseif ($mode === 'speedboats' && preg_match('/speed-(\d)h/', $r['id'], $m)) {
            $hrs = (int) $m[1];
            $note = '<bdi dir="ltr">€' . (int) round($r['price'] / $hrs) . '</bdi> / h (max 6)';
        } else {
            $note = e(mb_substr(item_desc($r), 0, 68));
        }

        $dur = item_duration($r);
        if ($dur === '') {
            $dur = (($r['unit'] ?? '') === 'day') ? ltrim(t('per_day'), '/ ') : '—';
        }
        $html .= '<tr' . ($isCur ? ' class="is-current"' : '') . '>';
        $html .= '<td><a class="cmp-link" href="' . e(item_url($r)) . '">' . e(item_name($r)) . '</a>'
            . ($isCur ? ' <span class="badge">' . te('compare_current') . '</span>' : '') . '</td>';
        $html .= '<td class="ptable__dur">' . e($dur) . '</td>';
        $html .= '<td class="ptable__price">' . price_html($r['price'], $r['unit'] ?? '') . '</td>';
        $html .= '<td>' . $note . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table></div></div>';
    return $html;
}

/** Find catalog items by id (keeps order). */
function catalog_pick(array $catalog, array $ids): array
{
    $flat = catalog_all_items($catalog);
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
            $itemUrl = !empty($it['slug']) ? SITE_URL . item_url($it) : SITE_URL . page_link('/prices');
            $offers[] = [
                '@type' => 'Offer',
                'url' => $itemUrl,
                'price' => (string) $it['price'],
                'priceCurrency' => 'EUR',
                'availability' => 'https://schema.org/InStock',
                'itemOffered' => [
                    '@type' => 'Service',
                    'name' => item_name($it),
                    'url' => $itemUrl,
                    'description' => item_desc($it) ?: item_name($it),
                    'provider' => ['@id' => SITE_URL . '/#organization'],
                    'areaServed' => 'Sharm El Sheikh',
                ],
            ];
        }
    }
    return ['@type' => 'OfferCatalog', 'name' => $name, 'itemListElement' => $offers];
}

if (!function_exists('page_link')) {
    function page_link(string $path, string $hash = ''): string
    {
        $q = LANG !== DEFAULT_LANG ? '/' . LANG . ($path === '/' ? '/' : $path) : $path;
        return $q . ($hash !== '' ? '#' . $hash : '');
    }
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
