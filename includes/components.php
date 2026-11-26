<?php
/** Small render helpers shared by pages (cards, compact rows, breadcrumbs, comparison tables, FAQ, schema). */
declare(strict_types=1);

require_once __DIR__ . '/google_reviews.php';
require_once __DIR__ . '/tripadvisor_reviews.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/catalog_helpers.php';

function price_html(int|float|null $eur, string $unit = '', int|float|null $regularEur = null): string
{
    if ($eur === null) {
        return '<span class="price price--req">' . te('price_on_request') . '</span>';
    }
    $suffix = match ($unit) {
        'day' => ' <small>' . te('per_day') . '</small>',
        'boat' => ' <small>' . te('per_boat') . '</small>',
        default => '',
    };
    $hasSale = ($regularEur !== null && (float) $regularEur > ((float) $eur + 0.009));
    $oldHtml = $hasSale
        ? '<del class="price-old" data-eur="' . e((string) $regularEur) . '"><bdi dir="ltr">' . e(money($regularEur)) . '</bdi></del> '
        : '';
    $cls = $hasSale ? 'price price--sale' : 'price';
    return $oldHtml . '<span class="' . $cls . '" data-eur="' . e((string) $eur) . '"><bdi dir="ltr">' . e(money($eur)) . '</bdi></span>' . $suffix;
}

function sc_save_badge_text(array $it): string
{
    $pct  = !empty($it['discount_pct']) ? (int) $it['discount_pct'] : 0;
    $save = !empty($it['save']) ? $it['save'] : null;
    if ($pct > 0 && $save !== null) {
        return '<bdi dir="ltr">-' . $pct . '%</bdi> · ' . te('save') . ' <bdi dir="ltr" data-eur="' . e((string) $save) . '">' . e(money($save)) . '</bdi>';
    }
    if ($pct > 0) {
        return '<bdi dir="ltr">-' . $pct . '%</bdi>';
    }
    if ($save !== null) {
        return te('save') . ' <bdi dir="ltr" data-eur="' . e((string) $save) . '">' . e(money($save)) . '</bdi>';
    }
    return '';
}

function add_button(array $it): string
{
    $disp = item_name($it);
    return '<button type="button" class="btn btn--add" data-add-to-cart'
        . ' data-id="' . e($it['id']) . '" data-name="' . e($disp) . '"'
        . ' data-price="' . e($it['price'] === null ? '' : (string) $it['price']) . '"'
        . ' aria-label="' . te('add_to_booking') . ': ' . e($disp) . '">+ ' . te('add_to_booking') . '</button>';
}

function card_wa_button(array $it): string
{
    $disp = item_name($it);
    $topic = $disp . ($it['price'] !== null ? ' (' . money($it['price']) . ')' : '');
    $href = whatsapp_link($topic, 'card_wa');
    $waSvg = '<svg class="icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91C21.95 6.45 17.5 2 12.04 2zm5.82 14.01c-.24.68-1.41 1.3-1.95 1.38-.5.08-1.13.11-1.82-.11-.42-.13-.96-.31-1.65-.61-2.9-1.25-4.8-4.17-4.94-4.36-.15-.19-1.18-1.57-1.18-3s.75-2.12 1.01-2.41c.27-.29.58-.36.78-.36.19 0 .39 0 .56.01.18.01.42-.07.66.5.24.58.82 2.01.89 2.15.07.15.12.31.02.5-.1.19-.15.31-.29.48-.15.17-.31.38-.44.51-.15.15-.3.3-.13.59.17.29.76 1.25 1.63 2.02 1.12 1 2.06 1.31 2.35 1.46.29.15.46.12.63-.07.17-.19.73-.85.92-1.14.19-.29.39-.24.65-.15.27.1 1.7.8 1.99.95.29.15.48.22.56.34.07.12.07.7-.17 1.38z"/></svg>';
    return '<a class="btn btn--wa btn--sm btn--wa-card" href="' . e($href) . '" target="_blank" rel="noopener"'
        . ' data-track="whatsapp_click" data-placement="card_wa"'
        . ' data-item-id="' . e((string) ($it['id'] ?? '')) . '"'
        . ' data-item-name="' . e($disp) . '"'
        . ' data-item-eur="' . e($it['price'] === null ? '' : (string) $it['price']) . '"'
        . ' aria-label="WhatsApp: ' . e($disp) . '">' . $waSvg . '<span>WhatsApp</span></a>';
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
    $child = isset($it['child_price']) ? ' <small>· ' . te('child') . ' <bdi dir="ltr" data-eur="' . e((string) $it['child_price']) . '">' . e(money($it['child_price'])) . '</bdi></small>' : '';
    $saveTxt = sc_save_badge_text($it);
    $save = $saveTxt !== '' ? '<span class="pcard__save">' . $saveTxt . '</span>' : '';

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
    $html .= '<span class="pcard__price">' . ($it['price'] !== null ? te('from') . ' ' : '') . price_html($it['price'], $it['unit'] ?? '', $it['regular_price'] ?? null) . $child . '</span>';
    $html .= '<span class="pcard__actions">' . card_wa_button($it) . '<a class="btn btn--detail btn--sm" href="' . e($href) . '">' . te('cta_details') . '</a>' . add_button($it) . '</span>';
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
    $html .= '<span class="crow__price">' . price_html($it['price'], $it['unit'] ?? '', $it['regular_price'] ?? null) . '</span>';
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
            $note = '<bdi dir="ltr" data-eur="' . e((string) $r['price']) . '">' . e(money($r['price'])) . '</bdi> ' . te('per_dive_unit') . ' (11+)';
        } elseif (!empty($r['save']) || !empty($r['discount_pct'])) {
            $note = '<span class="save">' . sc_save_badge_text($r) . '</span>';
        } elseif ($mode === 'charters' && preg_match('/-(\d+)$/', $r['id'], $m)) {
            $maxPax = (int) $m[1];
            $fromPp = (int) ceil($r['price'] / $maxPax);
            $note = te('from') . ' ~<bdi dir="ltr">€' . $fromPp . '</bdi> ' . te('per_person');
        } elseif ($mode === 'speedboats' && preg_match('/speed-(\d)h/', $r['id'], $m)) {
            $hrs = (int) $m[1];
            $unitMax = t('per_hour_max5') !== 'per_hour_max5' ? t('per_hour_max5') : '/ h (max 5)';
            $note = '<bdi dir="ltr">€' . (int) round($r['price'] / $hrs) . '</bdi> ' . e($unitMax);
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
        $html .= '<td class="ptable__price">' . price_html($r['price'], $r['unit'] ?? '', $r['regular_price'] ?? null) . '</td>';
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
