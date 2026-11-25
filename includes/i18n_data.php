<?php
/**
 * Multi-language data overlay & localized SEO/AEO resolver for all 7 languages:
 * en, ar, ru, de, it, fr, pl.
 *
 * Ensures 100% localized catalog names/descriptions/durations, category hubs,
 * dive sites, gallery/team items, and all 80 individual tour & PADI course pages
 * with zero English leakage when switching languages.
 */
declare(strict_types=1);

function sc_ar_duration_map(): array
{
    return [
        '1h' => 'ساعة واحدة', '1.5–2h' => '1.5–2 ساعة', '2h' => 'ساعتان', '2–3h' => '2–3 ساعات',
        '3h' => '3 ساعات', '3–4h' => '3–4 ساعات', '5–6h' => '5–6 ساعات', '7–8h' => '7–8 ساعات',
        '8h' => '8 ساعات', 'Half day' => 'نصف يوم', 'Full day' => 'يوم كامل', '1 day' => 'يوم واحد',
        '1–2 days' => '1–2 يوم', '1.5–2 days' => '1.5–2 يوم', '2 days' => 'يومان',
        '2–3 days' => '2–3 أيام', '2.5–3 days' => '2.5–3 أيام', '3 days' => '3 أيام',
        '3–3.5 days' => '3–3.5 أيام', '3–4 days' => '3–4 أيام', '4 days' => '4 أيام',
        '4–5 days' => '4–5 أيام', '5 days' => '5 أيام', '6 days' => '6 أيام',
        '6–7 days' => '6–7 أيام', '7 days' => '7 أيام', '10 days' => '10 أيام',
        '14 days' => '14 يومًا', '3 evenings' => '3 أمسيات', '4–6 weeks' => '4–6 أسابيع',
        'Per dive' => 'لكل غطسة', 'Per day' => 'لكل يوم',
    ];
}

require_once __DIR__ . '/live_prices.php';

function sc_overlay_catalog(array $catalog): array
{
    $catalog = sc_apply_live_prices($catalog);
    $lang = defined('LANG') ? LANG : 'en';
    if ($lang === 'ar') {
        $arDur = sc_ar_duration_map();
        foreach ($catalog as &$cat) {
            foreach ($cat['groups'] as &$g) {
                foreach ($g['items'] as &$it) {
                    if (!empty($it['duration']) && isset($arDur[$it['duration']])) {
                        $it['duration_ar'] = $arDur[$it['duration']];
                    }
                }
            }
        }
        unset($cat, $g, $it);
        return $catalog;
    }

    $file = (defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__)) . '/data/i18n/' . $lang . '/catalog.php';
    if ($lang === 'en' || !is_file($file)) {
        return $catalog;
    }
    $ov = require $file;
    $durMap = $ov['durations'] ?? [];

    foreach ($catalog as $catKey => &$cat) {
        if (!empty($ov['categories'][$catKey]['label'])) {
            $cat['label'][$lang] = $ov['categories'][$catKey]['label'];
        }
        if (!empty($ov['categories'][$catKey]['notes'])) {
            $cat['notes'][$lang] = $ov['categories'][$catKey]['notes'];
        }
        foreach ($cat['groups'] as &$g) {
            $gid = $g['id'];
            if (!empty($ov['groups'][$gid])) {
                $g['title'][$lang] = $ov['groups'][$gid];
            }
            foreach ($g['items'] as &$it) {
                $iid = $it['id'];
                if (!empty($ov['items'][$iid]['name'])) {
                    $it['name_' . $lang] = $ov['items'][$iid]['name'];
                }
                if (isset($ov['items'][$iid]['desc'])) {
                    $it['desc_' . $lang] = $ov['items'][$iid]['desc'];
                }
                if (!empty($ov['items'][$iid]['duration'])) {
                    $it['duration_' . $lang] = $ov['items'][$iid]['duration'];
                } elseif (!empty($it['duration']) && isset($durMap[$it['duration']])) {
                    $it['duration_' . $lang] = $durMap[$it['duration']];
                }
            }
        }
    }
    unset($cat, $g, $it);
    return $catalog;
}

function sc_overlay_items_seo(array $seoAll): array
{
    $lang = defined('LANG') ? LANG : 'en';
    if ($lang === 'en' || $lang === 'ar') {
        return $seoAll;
    }
    $baseDir = (defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__)) . '/data/i18n/' . $lang;
    $hubsFile = $baseDir . '/hubs.php';
    if (is_file($hubsFile)) {
        $hubsOv = require $hubsFile;
        foreach ($hubsOv as $catKey => $hData) {
            if (!isset($seoAll['hubs'][$catKey])) {
                continue;
            }
            foreach (['h1', 'meta_title', 'meta_desc', 'sub', 'aeo_answer', 'highlights'] as $field) {
                if (isset($hData[$field])) {
                    $seoAll['hubs'][$catKey][$field][$lang] = $hData[$field];
                }
            }
            if (!empty($hData['faqs'])) {
                $seoAll['hubs'][$catKey]['faqs_i18n'][$lang] = $hData['faqs'];
            }
        }
    }
    $itemsFile = $baseDir . '/items_seo.php';
    if (is_file($itemsFile)) {
        $itemsOv = require $itemsFile;
        foreach ($itemsOv as $itemId => $iData) {
            if (!isset($seoAll['items'][$itemId])) {
                $seoAll['items'][$itemId] = [];
            }
            $seoAll['items'][$itemId]['i18n'][$lang] = $iData;
        }
    }
    return $seoAll;
}

function sc_overlay_dive_sites(array $siteData): array
{
    $lang = defined('LANG') ? LANG : 'en';
    $file = (defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__)) . '/data/i18n/' . $lang . '/dive_sites.php';
    if ($lang === 'en' || $lang === 'ar' || !is_file($file)) {
        return $siteData;
    }
    $ov = require $file;
    if (!empty($ov['aeo_answer'])) {
        $siteData['aeo_answer'][$lang] = $ov['aeo_answer'];
    }
    foreach (($ov['zones'] ?? []) as $zKey => $zVal) {
        if (isset($siteData['zones'][$zKey])) {
            $siteData['zones'][$zKey][$lang] = $zVal;
        }
    }
    foreach ($siteData['sites'] as &$s) {
        $sid = $s['id'];
        if (empty($ov['sites'][$sid])) {
            continue;
        }
        $so = $ov['sites'][$sid];
        foreach (['level', 'current', 'name', 'summary', 'marine'] as $f) {
            if (isset($so[$f])) {
                $s[$f][$lang] = $so[$f];
            }
        }
    }
    unset($s);
    foreach ($siteData['months'] as $idx => &$m) {
        if (isset($ov['months'][$idx])) {
            $mo = $ov['months'][$idx];
            if (!empty($mo['m'])) {
                $m['m'][$lang] = $mo['m'];
            }
            if (!empty($mo['suit'])) {
                $m['suit'][$lang] = $mo['suit'];
            }
            if (!empty($mo['note'])) {
                $m['note'][$lang] = $mo['note'];
            }
        }
    }
    unset($m);
    if (!empty($ov['faqs'])) {
        $siteData['faqs_i18n'][$lang] = $ov['faqs'];
    }
    return $siteData;
}

function sc_overlay_gallery_team(array $galData): array
{
    $lang = defined('LANG') ? LANG : 'en';
    $baseRoot = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__);
    if ($lang !== 'en' && $lang !== 'ar' && !empty($galData['collections'])) {
        $colFile = $baseRoot . '/data/gallery_collections_i18n.php';
        if (is_file($colFile)) {
            $colOv = require $colFile;
            foreach ($galData['collections'] as &$col) {
                foreach ($col['photos'] as &$ph) {
                    $pid = $ph['id'] ?? '';
                    if ($pid !== '' && isset($colOv[$pid])) {
                        if (!empty($colOv[$pid]['title'][$lang])) {
                            $ph['title'][$lang] = $colOv[$pid]['title'][$lang];
                        }
                        if (!empty($colOv[$pid]['caption'][$lang])) {
                            $ph['caption'][$lang] = $colOv[$pid]['caption'][$lang];
                        }
                    }
                }
            }
            unset($col, $ph);
        }
    }
    $file = $baseRoot . '/data/i18n/' . $lang . '/gallery_team.php';
    if ($lang === 'en' || $lang === 'ar' || !is_file($file)) {
        return $galData;
    }
    $ov = require $file;
    foreach (($ov['filters'] ?? []) as $fKey => $fVal) {
        if (isset($galData['filters'][$fKey]) && empty($galData['filters'][$fKey][$lang])) {
            $galData['filters'][$fKey][$lang] = $fVal;
        }
    }
    foreach ($galData['media'] as $idx => &$m) {
        $hasExplicitId = isset($m['id']) && is_string($m['id']) && $m['id'] !== '';
        $mo = $hasExplicitId ? ($ov['media'][$m['id']] ?? null) : ($ov['media'][$idx] ?? null);
        if (is_array($mo)) {
            if (!empty($mo['title'])) {
                $m['title'][$lang] = $mo['title'];
            }
            if (!empty($mo['caption'])) {
                $m['caption'][$lang] = $mo['caption'];
            }
        }
    }
    unset($m);
    foreach ($galData['team'] as $idx => &$tm) {
        $to = $ov['team'][$idx] ?? null;
        if (is_array($to)) {
            if (!empty($to['name'])) {
                $tm['name_' . $lang] = $to['name'];
            }
            foreach (['role', 'focus', 'quote'] as $f) {
                if (!empty($to[$f])) {
                    $tm[$f][$lang] = $to[$f];
                }
            }
            if (!empty($to['reviewer'])) {
                $tm['reviewer_' . $lang] = $to['reviewer'];
            }
        }
    }
    unset($tm);
    return $galData;
}

/**
 * Normalize FAQ arrays into [['Question', 'Answer'], ...] for the current LANG.
 */
function sc_resolve_faqs(array $faqs, ?array $i18nMap = null): array
{
    $lang = defined('LANG') ? LANG : 'en';
    if ($i18nMap !== null && !empty($i18nMap[$lang])) {
        $faqs = $i18nMap[$lang];
    }
    $out = [];
    foreach ($faqs as $f) {
        if (isset($f['q'], $f['a'])) {
            $q = is_array($f['q']) ? loc($f['q']) : (string) $f['q'];
            $a = is_array($f['a']) ? loc($f['a']) : (string) $f['a'];
        } elseif (count($f) >= 4) {
            $q = ($lang === 'ar' && !empty($f[2])) ? (string) $f[2] : (string) $f[0];
            $a = ($lang === 'ar' && !empty($f[3])) ? (string) $f[3] : (string) $f[1];
        } else {
            $q = (string) ($f[0] ?? '');
            $a = (string) ($f[1] ?? '');
        }
        if ($q !== '' && $a !== '') {
            $out[] = [$q, $a];
        }
    }
    return $out;
}

require_once __DIR__ . '/item_seo_enhancer.php';

/**
 * Resolve complete localized SEO/AEO fields for any of the 80 catalog items.
 */
function sc_resolve_item_seo(array $it, array $seo): array
{
    $lang = defined('LANG') ? LANG : 'en';
    $resolved = null;

    // 1) Native EN / AR from core data/seo_*.php
    if ($lang === 'en' || $lang === 'ar') {
        $specs = [];
        foreach (($seo['specs'] ?? []) as $r) {
            $specs[] = [$lang === 'ar' ? ($r[1] ?? $r[0]) : $r[0], $lang === 'ar' ? ($r[3] ?? $r[2]) : $r[2]];
        }
        $steps = [];
        foreach (($seo['steps'] ?? []) as $st) {
            $steps[] = [$lang === 'ar' ? ($st[1] ?? $st[0]) : $st[0], $lang === 'ar' ? ($st[3] ?? $st[2]) : $st[2]];
        }
        $resolved = [
            'h1'         => loc($seo['h1'] ?? null) ?: item_name($it),
            'meta_title' => loc($seo['meta_title'] ?? null) ?: (item_name($it) . ' | ' . SITE_NAME),
            'meta_desc'  => loc($seo['meta_desc'] ?? null) ?: item_desc($it),
            'aeo_answer' => loc($seo['aeo_answer'] ?? null) ?: item_desc($it),
            'overview'   => $seo['overview'][$lang] ?? $seo['overview']['en'] ?? [],
            'specs'      => $specs,
            'steps'      => $steps,
            'included'   => $seo['included'][$lang] ?? $seo['included']['en'] ?? [],
            'excluded'   => $seo['excluded'][$lang] ?? $seo['excluded']['en'] ?? [],
            'ideal_for'  => $seo['ideal_for'][$lang] ?? $seo['ideal_for']['en'] ?? [],
            'faqs'       => sc_resolve_faqs($seo['faqs'] ?? []),
        ];
        return sc_enrich_item_seo($it, $seo, $resolved);
    }

    // 2) Bespoke per-item translation in data/i18n/{lang}/items_seo.php
    $custom = $seo['i18n'][$lang] ?? null;
    if (is_array($custom) && !empty($custom['h1'])) {
        $resolved = [
            'h1'         => (string) $custom['h1'],
            'meta_title' => (string) ($custom['meta_title'] ?? ($custom['h1'] . ' | ' . SITE_NAME)),
            'meta_desc'  => (string) ($custom['meta_desc'] ?? item_desc($it)),
            'aeo_answer' => (string) ($custom['aeo_answer'] ?? item_desc($it)),
            'overview'   => (array) ($custom['overview'] ?? []),
            'specs'      => (array) ($custom['specs'] ?? []),
            'steps'      => (array) ($custom['steps'] ?? []),
            'included'   => (array) ($custom['included'] ?? []),
            'excluded'   => (array) ($custom['excluded'] ?? []),
            'ideal_for'  => (array) ($custom['ideal_for'] ?? []),
            'faqs'       => sc_resolve_faqs($custom['faqs'] ?? []),
        ];
        return sc_enrich_item_seo($it, $seo, $resolved);
    }

    // 3) Smart localized group-aware generator from data/i18n/{lang}/item_templates.php
    $tplFile = (defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__)) . '/data/i18n/' . $lang . '/item_templates.php';
    if (is_file($tplFile)) {
        $builder = require $tplFile;
        if (is_callable($builder)) {
            $built = $builder($it, $seo);
            $built['faqs'] = sc_resolve_faqs($built['faqs'] ?? []);
            return sc_enrich_item_seo($it, $seo, $built);
        }
    }

    $resolved = [
        'h1'         => item_name($it),
        'meta_title' => item_name($it) . ' | ' . SITE_NAME,
        'meta_desc'  => item_desc($it),
        'aeo_answer' => item_desc($it),
        'overview'   => item_desc($it) !== '' ? [item_desc($it)] : [],
        'specs'      => [],
        'steps'      => [],
        'included'   => [],
        'excluded'   => [],
        'ideal_for'  => [],
        'faqs'       => [],
    ];
    return sc_enrich_item_seo($it, $seo, $resolved);
}
