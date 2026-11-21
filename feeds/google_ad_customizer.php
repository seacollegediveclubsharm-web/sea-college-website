<?php
/**
 * Dynamic Google Ads Business Data / Ad Customizer Feed CSV.
 *
 * Endpoints (routed via .htaccess):
 *   - Default (English): https://seacollegediveclub.com/feeds/google-ad-customizer.csv
 *   - Localized:         https://seacollegediveclub.com/feeds/google-ad-customizer-{en|ru|it|pl|de|fr|ar|zh}.csv
 *
 * Schema (matches PROJECT.md Interface Contract):
 *   Item_ID,Title,Price_EUR,Category,Target_URL
 *
 * Always reflects live EUR prices from data/catalog.php (e.g., owd=400, aowd=350, rescue=394).
 */
declare(strict_types=1);

define('SC_NO_SESSION', true);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once ROOT_DIR . '/includes/catalog_helpers.php';

$catalog = require ROOT_DIR . '/data/catalog.php';

$feedLang = LANG;
$commercialOnly = (($_GET['commercial_only'] ?? '') === '1');
$filename = $feedLang === 'en'
    ? 'google-ad-customizer.csv'
    : ('google-ad-customizer-' . $feedLang . '.csv');

if (!headers_sent()) {
    @header_remove('X-Powered-By');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Cache-Control: public, max-age=300, s-maxage=900');
}

$out = fopen('php://output', 'wb');
if ($out === false) {
    exit(1);
}

fputcsv($out, ['Item_ID', 'Title', 'Price_EUR', 'Category', 'Target_URL'], ',', '"', '');

$seen = [];
foreach ($catalog as $catKey => $cat) {
    $catEnLabel = (string) ($cat['label']['en'] ?? ($cat['label'][$feedLang] ?? $catKey));
    foreach ($cat['groups'] as $group) {
        foreach ($group['items'] as $it) {
            if (!isset($it['price']) || !is_numeric($it['price']) || (float) $it['price'] <= 0 || empty($it['slug'])) {
                continue;
            }
            if ($commercialOnly && !sc_is_catalog_item($it)) {
                continue;
            }
            $id = (string) $it['id'];
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;

            $title = $feedLang === 'en' ? (string) $it['name'] : item_name($it);
            $priceFloat = (float) $it['price'];
            $priceStr = ($priceFloat == (int) $priceFloat)
                ? (string) (int) $priceFloat
                : number_format($priceFloat, 2, '.', '');
            $targetUrl = SITE_URL . page_link(item_path($it), '', $feedLang);

            fputcsv($out, [$id, $title, $priceStr, $catEnLabel, $targetUrl], ',', '"', '');
        }
    }
}

fclose($out);
