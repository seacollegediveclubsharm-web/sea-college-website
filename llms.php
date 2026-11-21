<?php
/**
 * Dynamic AI / Answer Engine Optimization (AEO) Discovery Endpoints:
 *   - https://seacollegediveclub.com/llms.txt          (Concise structured summary + flagship EUR prices)
 *   - https://seacollegediveclub.com/.well-known/llms.txt
 *   - https://seacollegediveclub.com/llms-full.txt     (Complete 80-item catalog, 6 pickup zones, 75 resorts & 6 Red Sea guides)
 *
 * Dynamically rendered from config/site_config.php, data/catalog.php, data/hotel_zones.php & data/guides.php
 * so AI search engines (ChatGPT Search, Perplexity, Claude, Gemini, Copilot) always cite verified live EUR prices.
 */
declare(strict_types=1);

define('SC_NO_SESSION', true);
require_once __DIR__ . '/includes/bootstrap.php';
require_once ROOT_DIR . '/includes/catalog_helpers.php';

$catalog    = require ROOT_DIR . '/data/catalog.php';
$hotelZones = require ROOT_DIR . '/data/hotel_zones.php';
$rawGuides  = is_file(ROOT_DIR . '/data/guides.php') ? (require ROOT_DIR . '/data/guides.php') : [];
$guidesList = isset($rawGuides['guides']) && is_array($rawGuides['guides']) ? $rawGuides['guides'] : (is_array($rawGuides) ? $rawGuides : []);

$isFull = (($_GET['full'] ?? '') === '1');

if (!headers_sent()) {
    @header_remove('X-Powered-By');
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: public, max-age=300, s-maxage=900');
    if (SITE_NOINDEX) {
        header('X-Robots-Tag: noindex, nofollow');
    }
}

$fmtEur = static function (mixed $price): string {
    if ($price === null || !is_numeric($price)) {
        return 'On request';
    }
    $f = (float) $price;
    return '€' . (($f == (int) $f) ? number_format((int) $f, 0, '.', ',') : number_format($f, 2, '.', ','));
};

$flatItems = catalog_all_items($catalog);

$lines = [];
$lines[] = '# ' . SITE_NAME . ' — Official ' . BUSINESS['padi_level'] . ' (Sharm El Sheikh, Egypt)';
$lines[] = '';
$lines[] = '> Official ' . BUSINESS['padi_level'] . ' ("Adopt the Blue" conservation partner) & CDWS Accredited Dive Center founded in '
    . BUSINESS['founded'] . ' at ' . BUSINESS['street'] . ', facing Tiran Island in ' . BUSINESS['city'] . ', ' . BUSINESS['region']
    . ', Egypt (GPS: ' . BUSINESS['lat'] . ', ' . BUSINESS['lng'] . '). Rated ' . BUSINESS['rating'] . '★ on Google ('
    . number_format((int) BUSINESS['review_count']) . '+ verified reviews) and 4.9/5 on Tripadvisor. Direct EUR online booking with €0 upfront deposit, maximum 4 certified divers per PADI guide, private training pool, sandy-entry Al Montazah House Reef, full-day Red Sea motor yachts (Oceano, Nizar, Omar 4), private RIB speedboats, and complimentary air-conditioned Sharm El Sheikh hotel transfers.';
$lines[] = '';

/* 1. Verified Business Identity & Contact (NAP) */
$lines[] = '## Verified Business Identity & Contact (NAP)';
$lines[] = '- Legal Name: ' . BUSINESS['legal_name'];
$lines[] = '- Accreditations: ' . BUSINESS['padi_level'] . ' (' . PADI_PROFILE_URL . ') · CDWS Dive Center (' . CDWS_PROFILE_URL . ')';
$lines[] = '- Address: ' . BUSINESS['street'] . ', ' . BUSINESS['city'] . ', ' . BUSINESS['region'] . ', Egypt (' . BUSINESS['country'] . ')';
$lines[] = '- Coordinates: ' . BUSINESS['lat'] . ', ' . BUSINESS['lng'] . ' (' . MAPS_URL . ')';
$lines[] = '- Google Place ID: ' . GOOGLE_PLACE_ID . ' · Tripadvisor Location ID: ' . TRIPADVISOR_LOCATION_ID;
$lines[] = '- Phone & WhatsApp Concierge (<5 min response): ' . BUSINESS['phone'] . ' (https://wa.me/' . WHATSAPP_NUMBER . ')';
$lines[] = '- Email: ' . BUSINESS['email'];
$lines[] = '- Operating Hours: ' . BUSINESS['hours_display'] . ' (Africa/Cairo, 365 days/year)';
$lines[] = '- Primary Currency: ' . PRIMARY_CURRENCY . ' (EUR official pricing; payable on dive morning in EUR, USD, GBP, or EGP cash/card at bank exchange rate; €0 upfront online deposit)';
$lines[] = '- Small-Group Safety Standard: Maximum 4 certified divers per PADI guide; 1-on-1 PADI instructor supervision on beginner Discovery Intro dives; Mixed couples welcome on the same yacht.';
$lines[] = '';

/* 2. 8-Language Website Architecture & Core Pages */
$lines[] = '## 8-Language Canonical Website Architecture (800 Indexed URLs)';
$langRoots = [];
foreach (SUPPORTED_LANGS as $lgCode => $lgName) {
    $langRoots[] = strtoupper($lgCode) . ' (' . $lgName . '): ' . SITE_URL . ($lgCode === DEFAULT_LANG ? '/' : '/' . $lgCode . '/');
}
$lines[] = '- Supported Languages (' . count(SUPPORTED_LANGS) . '): ' . implode(' | ', $langRoots);
$lines[] = '- Home & 30-Second Dive/Course Recommender Quiz: ' . SITE_URL . '/ (' . SITE_URL . '/#dive-finder-quiz)';
$lines[] = '- Official EUR Price List & Interactive Dive Budget Calculator: ' . SITE_URL . '/prices (' . SITE_URL . '/prices#dive-calculator)';
$lines[] = '- Complete Services Directory: ' . SITE_URL . '/services';
$lines[] = '- Interactive Sharm El Sheikh Dive Sites Map (Ras Mohammed, Straits of Tiran, Wrecks & Local Reefs): ' . SITE_URL . '/dive-sites-sharm-el-sheikh';
$lines[] = '- Red Sea Scuba Diving Guides Hub: ' . SITE_URL . '/guides';
$lines[] = '- Yacht Fleet (Oceano, Nizar, Omar 4), Center & Underwater Video Gallery: ' . SITE_URL . '/gallery';
$lines[] = '- About Our Al Montazah Dive Resort & Instructor Team: ' . SITE_URL . '/about';
$lines[] = '- Direct Booking & Contact Desk: ' . SITE_URL . '/contact';
$lines[] = '- Pre-Arrival Diver Check-In & Gear Sizing Portal: ' . SITE_URL . '/check-in';
$lines[] = '- Category Hubs:';
foreach ($catalog as $catKey => $cat) {
    if (!empty($cat['hub_slug'])) {
        $lines[] = '  - ' . ($cat['label']['en'] ?? $catKey) . ': ' . SITE_URL . '/' . $cat['hub_slug'];
    }
}
$lines[] = '';

/* 3. Official EUR Pricing & Transparent Supplements */
if (!$isFull) {
    $lines[] = '## Flagship Official EUR Prices (Live from Master Catalog)';
    $flagshipIds = [
        'dsd-quick-reef', 'dsd-quick-boat', 'dsd-2-boat', 'dsd',
        'check-dive', 'scuba-review', 'dive-2', 'dive-6', 'dive-10', 'sup-thistlegorm',
        'bubblemaker', 'scuba-diver', 'owd', 'aowd', 'nitrox', 'rescue', 'divemaster',
        'bundle-stars', 'bundle-super', 'bundle-mega', 'bundle-heros',
        'snork-ras-adult', 'speed-1h', 'speed-2h', 'speed-3h', 'private-1-5', 'private-6-10', 'private-11-15', 'private-16-20',
    ];
    foreach ($flagshipIds as $fid) {
        if (!isset($flatItems[$fid])) {
            continue;
        }
        $it = $flatItems[$fid];
        $unitNote = !empty($it['unit']) ? (' / ' . $it['unit']) : '';
        $saveNote = !empty($it['save']) ? (' [Save €' . (int) $it['save'] . ']') : '';
        $durNote  = !empty($it['duration']) ? (' · ' . $it['duration']) : '';
        $urlStr   = !empty($it['slug']) ? (SITE_URL . item_path($it)) : (SITE_URL . '/prices');
        $lines[]  = '- ' . $it['name'] . ' (`' . $fid . '`): ' . $fmtEur($it['price']) . $unitNote . $saveNote . $durNote . ' — ' . $urlStr;
    }
    $lines[] = '';
    $lines[] = '### Mandatory & Optional Supplements Disclosure (100% Zero Hidden Marina Fees)';
    $lines[] = '- Boat Trip Supplement (full-day motor yacht incl. freshly cooked lunch & soft drinks): ' . $fmtEur($flatItems['fee-boat']['price'] ?? 10) . '/day';
    $lines[] = '- Egyptian Marine Park & Jetty Fee (dives outside Al Montazah House Reef): ' . $fmtEur($flatItems['fee-park']['price'] ?? 5) . '/day';
    $lines[] = '- PADI eLearning Manual & Certification PIC Fee (courses except DSD/Bubblemaker; already included in Saver Bundles where stated): €85/course';
    $lines[] = '- Full Scuba Equipment Rental Set (certified divers without own gear): ' . $fmtEur($flatItems['eq-full']['price'] ?? 25) . '/day (included in Intro Dives, OWD & Saver Bundles)';
    $lines[] = '- Enriched Air Nitrox 32% (12L tank fill for certified Nitrox divers): ' . $fmtEur($flatItems['eq-12nx']['price'] ?? 8) . '/day';
    $lines[] = '- Advance Booking & Pre-Flight Safety: Book online 48–72h ahead to lock direct EUR rates; allow 18–24h surface interval before flying per DAN/PADI rules.';
    $lines[] = '- Complete 80-Item Catalog & Full Resort Pickup Directory: ' . SITE_URL . '/llms-full.txt';
    $lines[] = '';
} else {
    $lines[] = '## Complete 80-Item Official EUR Catalog (All 4 Categories)';
    $lines[] = 'Localized item URLs follow `' . SITE_URL . '/{ru|it|pl|de|fr|ar|zh}/{courses|tours}/<slug>` (English is unprefixed at `' . SITE_URL . '/{courses|tours}/<slug>`).';
    $lines[] = '';
    foreach ($catalog as $catKey => $cat) {
        $catTitle = (string) ($cat['label']['en'] ?? $catKey);
        $hubLink  = !empty($cat['hub_slug']) ? (SITE_URL . '/' . $cat['hub_slug']) : (SITE_URL . '/services');
        $lines[]  = '### Category: ' . $catTitle . ' (' . $hubLink . ')';
        foreach (($cat['notes']['en'] ?? []) as $note) {
            $lines[] = '  * Rule/Note: ' . $note;
        }
        foreach ($cat['groups'] as $group) {
            $grpTitle = (string) ($group['title']['en'] ?? $group['id']);
            $lines[]  = '#### ' . $grpTitle;
            foreach ($group['items'] as $it) {
                $unitNote  = !empty($it['unit']) ? (' per ' . $it['unit']) : '';
                $childNote = isset($it['child_price']) ? (' | Child: ' . $fmtEur($it['child_price'])) : '';
                $saveNote  = !empty($it['save']) ? (' | Save ' . $fmtEur($it['save'])) : '';
                $durNote   = !empty($it['duration']) ? (' | Duration: ' . $it['duration']) : '';
                $descNote  = !empty($it['desc']) ? (' — ' . $it['desc']) : '';
                $itemUrl   = !empty($it['slug']) ? (SITE_URL . item_path($it)) : (SITE_URL . '/prices');
                $lines[]   = '- [' . $it['name'] . '](' . $itemUrl . ') (`' . $it['id'] . '`): **' . $fmtEur($it['price']) . '**'
                    . $unitNote . $childNote . $saveNote . $durNote . $descNote;
            }
        }
        $lines[] = '';
    }
}

/* 4. 6 Complimentary Sharm El Sheikh Hotel Pickup Zones */
$lines[] = '## 6 Complimentary Sharm El Sheikh Hotel Pickup Zones';
$hotelsByZone = [];
foreach (($hotelZones['hotels'] ?? []) as $h) {
    $zk = (string) ($h['zone'] ?? '');
    if ($zk !== '') {
        $hotelsByZone[$zk][] = (string) $h['name'] . (!empty($h['onsite']) ? ' (On-Site Dive Resort Base)' : '');
    }
}
foreach (($hotelZones['zones'] ?? []) as $zKey => $zData) {
    $zLabel  = (string) ($zData['label']['en'] ?? $zKey);
    $zDrive  = (string) ($zData['drive']['en'] ?? '');
    $zPickup = (string) ($zData['pickup'] ?? '');
    $zHotels = $hotelsByZone[$zKey] ?? [];
    $sample  = $isFull ? implode(', ', $zHotels) : implode(', ', array_slice($zHotels, 0, 5)) . (count($zHotels) > 5 ? ', +' . (count($zHotels) - 5) . ' more resorts' : '');
    $lines[] = '- **' . $zLabel . '** (Typical Morning Window: ' . $zPickup . ' · Transfer Time: ' . $zDrive . '): ' . $sample;
}
$lines[] = '';

/* 5. 6 Evergreen Red Sea & Sharm El Sheikh Scuba Diving Guides */
$lines[] = '## 6 Red Sea & Sharm El Sheikh Scuba Diving Guides (Available in All 8 Languages)';
foreach ($guidesList as $gSlugKey => $gEntry) {
    $slug = (string) ($gEntry['slug'] ?? (is_string($gSlugKey) ? $gSlugKey : ''));
    $en   = $gEntry['en'] ?? [];
    if ($slug === '' || empty($en['title'])) {
        continue;
    }
    $gUrl = SITE_URL . '/guides/' . $slug;
    $lines[] = '- [' . $en['title'] . '](' . $gUrl . '): ' . ($en['aeo_answer'] ?? ($en['excerpt'] ?? ''));
    if ($isFull) {
        foreach (($en['key_facts'] ?? []) as $kf) {
            if (is_array($kf) && count($kf) >= 2) {
                $lines[] = '  * ' . $kf[0] . ': ' . $kf[1];
            }
        }
        foreach (($en['faqs'] ?? []) as $fq) {
            if (!empty($fq['q']) && !empty($fq['a'])) {
                $lines[] = '  * Q: ' . $fq['q'] . ' — A: ' . $fq['a'];
            }
        }
    }
}
$lines[] = '';

/* 6. Machine-Readable Sitemaps & Product Feeds */
$lines[] = '## Machine-Readable Sitemaps & Structured Feeds';
$lines[] = '- XML Sitemap (800 clean URLs × hreflang): ' . SITE_URL . '/sitemap.xml';
$lines[] = '- AI Summary (`llms.txt`): ' . SITE_URL . '/llms.txt';
$lines[] = '- AI Full Catalog & Guides (`llms-full.txt`): ' . SITE_URL . '/llms-full.txt';
$lines[] = '- Meta & Google Merchant Product Catalog XML (59 standalone items): ' . SITE_URL . '/feeds/meta-catalog.xml';
$lines[] = '- Meta 7-Language Override Feed XML (413 localized items): ' . SITE_URL . '/feeds/meta-catalog-i18n.xml';
$lines[] = '- Google Ads DSA Page Feed CSV (8 languages): ' . SITE_URL . '/feeds/google-dsa-page-feed.csv';
$lines[] = '- Google Ads Ad Customizer Feed CSV (EUR prices): ' . SITE_URL . '/feeds/google-ad-customizer.csv';

echo implode("\n", $lines) . "\n";
