<?php
/**
 * Helpers for loading, localizing, and generating Schema.org structured data
 * for the 8-language Red Sea Diving Guides Hub (`guides.php` & `guide.php`).
 */
declare(strict_types=1);

require_once __DIR__ . '/components.php';

function guide_path(array|string $g): string
{
    $slug = is_array($g) ? (string) ($g['slug'] ?? '') : $g;
    return '/guides/' . ltrim($slug, '/');
}

function guide_url(array|string $g): string
{
    return page_link(guide_path($g));
}

/**
 * Load all guides from data/guides.php and overlay data/i18n/{lang}/guides.php
 * into a normalized localized structure for the active LANG.
 *
 * @return array<string, array>
 */
function sc_load_guides(): array
{
    $file = ROOT_DIR . '/data/guides.php';
    if (!is_file($file)) {
        return [];
    }
    $raw = require $file;
    if (isset($raw['guides']) && is_array($raw['guides'])) {
        $raw = $raw['guides'];
    }
    if (!is_array($raw)) {
        return [];
    }

    $ov = [];
    if (LANG !== 'en' && LANG !== 'ar') {
        $ovFile = ROOT_DIR . '/data/i18n/' . LANG . '/guides.php';
        if (is_file($ovFile)) {
            $loaded = require $ovFile;
            $ov = isset($loaded['guides']) && is_array($loaded['guides']) ? $loaded['guides'] : (is_array($loaded) ? $loaded : []);
        }
    }

    $out = [];
    foreach ($raw as $key => $g) {
        if (!is_array($g)) {
            continue;
        }
        $slug = (string) ($g['slug'] ?? (is_string($key) ? $key : ''));
        if ($slug === '') {
            continue;
        }
        $en = is_array($g['en'] ?? null) ? $g['en'] : [];
        if (LANG === 'ar') {
            $locBlock = is_array($g['ar'] ?? null) ? $g['ar'] : $en;
        } elseif (LANG === 'en') {
            $locBlock = $en;
        } else {
            $locBlock = is_array($ov[$slug] ?? null) ? $ov[$slug]
                : (is_array($ov[$key] ?? null) ? $ov[$key]
                : (is_array($g[LANG] ?? null) ? $g[LANG] : $en));
        }

        $imgRaw = ltrim((string) ($g['img'] ?? 'images/hero.jpg'), '/');
        $imgRel = str_starts_with($imgRaw, 'images/') ? $imgRaw : ('images/' . $imgRaw);

        $facts = [];
        foreach (($locBlock['key_facts'] ?? $en['key_facts'] ?? []) as $f) {
            if (is_array($f)) {
                $lbl = (string) ($f['label'] ?? $f[0] ?? '');
                $val = (string) ($f['val'] ?? $f[1] ?? '');
                if ($lbl !== '' && $val !== '') {
                    $facts[] = [$lbl, $val];
                }
            }
        }

        $sections = [];
        foreach (($locBlock['sections'] ?? $en['sections'] ?? []) as $sec) {
            if (!is_array($sec)) {
                continue;
            }
            $h2 = (string) ($sec['h2'] ?? '');
            $pRaw = $sec['p'] ?? '';
            $paras = is_array($pRaw) ? array_values(array_filter(array_map('strval', $pRaw))) : ($pRaw !== '' ? [(string) $pRaw] : []);
            $bullets = array_values(array_filter(array_map('strval', (array) ($sec['bullets'] ?? []))));
            if ($h2 !== '' || $paras || $bullets) {
                $sections[] = ['h2' => $h2, 'paras' => $paras, 'bullets' => $bullets];
            }
        }

        $faqs = sc_resolve_faqs((array) ($locBlock['faqs'] ?? $en['faqs'] ?? []));

        $title = (string) ($locBlock['title'] ?? $en['title'] ?? $slug);
        $out[$slug] = [
            'slug'        => $slug,
            'img'         => $imgRel,
            'read_min'    => max(1, (int) ($g['read_min'] ?? 5)),
            'updated'     => (string) ($g['updated'] ?? date('Y-m-d')),
            'related_ids' => array_values((array) ($g['related_ids'] ?? [])),
            'badge'       => (string) ($locBlock['badge'] ?? $en['badge'] ?? t('nav_guides')),
            'title'       => $title,
            'meta_title'  => (string) ($locBlock['meta_title'] ?? $en['meta_title'] ?? ($title . ' | ' . SITE_NAME)),
            'meta_desc'   => (string) ($locBlock['meta_desc'] ?? $en['meta_desc'] ?? ($locBlock['excerpt'] ?? $en['excerpt'] ?? '')),
            'excerpt'     => (string) ($locBlock['excerpt'] ?? $en['excerpt'] ?? ''),
            'aeo_answer'  => (string) ($locBlock['aeo_answer'] ?? $en['aeo_answer'] ?? ''),
            'key_facts'   => $facts,
            'sections'    => $sections,
            'faqs'        => $faqs,
        ];
    }

    return $out;
}

/**
 * Schema.org Article + FAQPage nodes for an individual diving guide page.
 *
 * @return array<int, array>
 */
function schema_guide_article(array $g): array
{
    $orgRef = ['@id' => SITE_URL . '/#organization'];
    $fullUrl = canonical_url();
    $imgUrl = url('assets/' . ltrim($g['img'], '/'));

    $wordText = $g['aeo_answer'] . ' ';
    foreach ($g['sections'] as $s) {
        $wordText .= $s['h2'] . ' ' . implode(' ', $s['paras']) . ' ' . implode(' ', $s['bullets']) . ' ';
    }
    $wordCount = LANG === 'zh'
        ? max(350, (int) preg_match_all('/\p{Han}|[A-Za-z0-9]+/u', $wordText))
        : max(350, count(preg_split('/\s+/u', trim($wordText)) ?: []));

    $aboutMap = [
        'en' => ['Sharm El Sheikh, Red Sea, Egypt', 'Scuba Diving'],
        'ru' => ['Шарм-эль-Шейх, Красное море, Египет', 'Дайвинг и обучение PADI'],
        'it' => ['Sharm El Sheikh, Mar Rosso, Egitto', 'Immersioni e Corsi Sub PADI'],
        'de' => ['Sharm El Sheikh, Rotes Meer, Ägypten', 'Tauchen & PADI Tauchschule'],
        'pl' => ['Sharm El Sheikh (Szarm el-Szejk), Morze Czerwone, Egipt', 'Nurkowanie i Kursy PADI'],
        'fr' => ['Charm el-Cheikh (Sharm El Sheikh), Mer Rouge, Égypte', 'Plongée sous-marine & Cours PADI'],
        'ar' => ['شرم الشيخ، البحر الأحمر، مصر', 'الغوص والغطس ودورات بادي PADI'],
        'zh' => ['埃及红海沙姆沙伊赫', '水肺潜水与 PADI 考证'],
    ];
    $aboutPair = $aboutMap[LANG] ?? $aboutMap['en'];

    $article = [
        '@type'            => 'Article',
        '@id'              => $fullUrl . '#article',
        'headline'         => $g['title'],
        'description'      => $g['meta_desc'] ?: $g['excerpt'],
        'image'            => [$imgUrl],
        'inLanguage'       => LANG,
        'datePublished'    => '2026-01-15',
        'dateModified'     => $g['updated'],
        'wordCount'        => $wordCount,
        'author'           => $orgRef,
        'publisher'        => $orgRef,
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $fullUrl],
        'about'            => [
            ['@type' => 'Place', 'name' => $aboutPair[0]],
            ['@type' => 'Thing', 'name' => $aboutPair[1]],
        ],
    ];

    $nodes = [$article];
    if (!empty($g['faqs'])) {
        $nodes[] = schema_faq_pairs($g['faqs']);
    }
    return $nodes;
}
