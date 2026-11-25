<?php
/**
 * Dynamic Google Ads Dynamic Search Ads (DSA) Page Feed CSV.
 *
 * Endpoint (routed via .htaccess):
 *   https://seacollegediveclub.com/feeds/google-dsa-page-feed.csv
 *
 * Optional query parameters:
 *   ?lang=en|ru|it|pl|de|fr|ar|zh  Filter to a single locale (default: all 8 languages)
 *   ?include_addons=1              Include rental/fee/insurance item URLs (default: 59 commercial items)
 *
 * Google Ads Business Data DSA Page Feed schema:
 *   Page URL,Custom label  (multiple labels separated by ';')
 */
declare(strict_types=1);

define('SC_NO_SESSION', true);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once ROOT_DIR . '/includes/catalog_helpers.php';

$catalog = require ROOT_DIR . '/data/catalog.php';

$reqLang = isset($_GET['lang']) ? strtolower(trim((string) $_GET['lang'])) : '';
$langs = ($reqLang !== '' && isset(SUPPORTED_LANGS[$reqLang]))
    ? [$reqLang]
    : array_keys(SUPPORTED_LANGS);
$includeAddons = (($_GET['include_addons'] ?? '') === '1');

/**
 * Build semicolon-separated DSA custom labels for a catalog item.
 */
function sc_dsa_item_labels(string $langCode, string $catKey, array $it): string
{
    $id = (string) ($it['id'] ?? '');
    $labels = ['LANG_' . strtoupper($langCode)];

    if ($catKey === 'padi-courses' || $catKey === 'offers' || str_starts_with($id, 'bundle-')) {
        $labels[] = 'C2_COURSES';
        if ($catKey === 'offers' || str_starts_with($id, 'bundle-')) {
            $labels[] = 'BUNDLES';
        } elseif (in_array($id, ['bubblemaker', 'seal-team', 'dsd'], true)) {
            $labels[] = 'INTRO_DIVES';
        } elseif ($id === 'divemaster') {
            $labels[] = 'GO_PRO';
        } else {
            $labels[] = 'PADI_COURSES';
        }
    } elseif ($catKey === 'snorkeling') {
        $labels[] = 'C3_PRIVATE_BOATS';
        if (str_starts_with($id, 'private-')) {
            $labels[] = 'PRIVATE_YACHT';
        } elseif (str_starts_with($id, 'speed-')) {
            $labels[] = 'SPEEDBOAT';
        } else {
            $labels[] = 'SNORKELING';
        }
    } else {
        $labels[] = 'C1_DIVE_TRIPS';
        if (str_starts_with($id, 'dsd-')) {
            $labels[] = 'INTRO_DIVES';
        } elseif ($id === 'sup-thistlegorm' || str_starts_with($id, 'sup-')) {
            $labels[] = 'WRECK_SPECIALTY';
        } else {
            $labels[] = 'CERTIFIED_PACKAGES';
        }
    }

    $labels[] = !empty($it['popular']) ? 'POPULAR' : 'STANDARD';
    if (!empty($it['save'])) {
        $labels[] = 'SAVER';
    }
    $labels[] = 'ID_' . strtoupper((string) preg_replace('/[^a-zA-Z0-9]+/', '_', $id));

    return implode(';', array_values(array_unique($labels)));
}

if (!headers_sent()) {
    @header_remove('X-Powered-By');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: inline; filename="google-dsa-page-feed.csv"');
    header('Cache-Control: public, max-age=300, s-maxage=900');
}

$out = fopen('php://output', 'wb');
if ($out === false) {
    exit(1);
}

fwrite($out, "Page URL,Custom label\n");

$seen = [];
$emit = static function (string $url, string $labelStr) use ($out, &$seen): void {
    if (isset($seen[$url])) {
        return;
    }
    $seen[$url] = true;
    fputcsv($out, [$url, $labelStr], ',', '"', '');
};

$corePages = [
    ['/', 'CORE_HOME;HIGH_INTENT'],
    ['/prices', 'CORE_PRICES;CALCULATOR;HIGH_INTENT'],
    ['/faq', 'CORE_FAQ;HIGH_INTENT'],
    ['/services', 'CORE_SERVICES'],
    ['/dive-sites-sharm-el-sheikh', 'DIVE_SITES;C1_DIVE_TRIPS'],
];

$hubLabels = [
    'dive-packages' => 'HUB;C1_DIVE_TRIPS;CERTIFIED_PACKAGES',
    'padi-courses'  => 'HUB;C2_COURSES;PADI_COURSES',
    'offers'        => 'HUB;C2_COURSES;BUNDLES;SAVER',
    'snorkeling'    => 'HUB;C3_PRIVATE_BOATS;SNORKELING',
];

foreach ($langs as $lg) {
    $langTag = 'LANG_' . strtoupper($lg);

    foreach ($corePages as [$path, $baseLabels]) {
        $emit(SITE_URL . page_link($path, '', $lg), $langTag . ';' . $baseLabels);
    }

    foreach ($catalog as $catKey => $cat) {
        if (!empty($cat['hub_slug'])) {
            $hLbl = $hubLabels[$catKey] ?? 'HUB';
            $emit(SITE_URL . page_link('/' . $cat['hub_slug'], '', $lg), $langTag . ';' . $hLbl);
        }
    }

    foreach ($catalog as $catKey => $cat) {
        foreach ($cat['groups'] as $group) {
            foreach ($group['items'] as $it) {
                $ok = $includeAddons
                    ? (isset($it['price']) && is_numeric($it['price']) && (float) $it['price'] > 0 && !empty($it['slug']))
                    : sc_is_catalog_item($it);
                if (!$ok) {
                    continue;
                }
                $url = SITE_URL . page_link(item_path($it), '', $lg);
                $emit($url, sc_dsa_item_labels($lg, (string) $catKey, $it));
            }
        }
    }
}

fclose($out);
