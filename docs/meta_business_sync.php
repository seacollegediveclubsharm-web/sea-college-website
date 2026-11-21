<?php
/**
 * Hossam — Meta Business Suite, Catalog & Ads Graph API Controller (CLI-only).
 *
 * Connects directly to https://graph.facebook.com/{META_GRAPH_VERSION}/ using
 * META_ACCESS_TOKEN (or META_CAPI_TOKEN) from env / config/secrets.php to manage:
 *   - Business Portfolio: 660290192579947 (seacollegediveclub)
 *   - Product Catalog:    555610010235756 (SEA COLLEGE TRIPS)
 *   - Dataset / Pixel:    2980406819004765
 *   - Ad Accounts, Product Feeds, Product Sets & Campaigns
 *
 * Usage:
 *   php docs/meta_business_sync.php status
 *   php docs/meta_business_sync.php setup-catalog
 *   php docs/meta_business_sync.php campaigns
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: CLI only.');
}

require_once dirname(__DIR__) . '/config/site_config.php';

$cmd = strtolower(trim((string) ($argv[1] ?? 'status')));
$token = META_ACCESS_TOKEN;

if ($token === '') {
    echo "=== Hossam — Meta Graph API Controller ===\n";
    echo "Business ID : " . META_BUSINESS_ID . " (seacollegediveclub)\n";
    echo "Catalog ID  : " . META_CATALOG_ID . " (SEA COLLEGE TRIPS)\n";
    echo "Pixel ID    : " . META_PIXEL_ID . "\n";
    echo "Status      : Waiting for META_ACCESS_TOKEN (System User token in env or config/secrets.php)\n";
    exit(0);
}

/**
 * Execute an authenticated Meta Graph API call.
 *
 * @return array{status:int, data:array<string,mixed>}
 */
function sc_meta_api(string $method, string $path, array $params = []): array
{
    $url = 'https://graph.facebook.com/' . META_GRAPH_VERSION . '/' . ltrim($path, '/');
    $params['access_token'] = META_ACCESS_TOKEN;

    $ch = curl_init();
    if (strtoupper($method) === 'GET') {
        $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    } else {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    }
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
    ]);
    $raw = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($raw, true);
    return [
        'status' => $code,
        'data'   => is_array($decoded) ? $decoded : ['raw' => $raw],
    ];
}

if ($cmd === 'status') {
    echo "=== Hossam — Meta Business Portfolio Live Status ===\n";
    $biz = sc_meta_api('GET', META_BUSINESS_ID, ['fields' => 'id,name,verification_status']);
    echo "Business    : " . json_encode($biz['data'], JSON_UNESCAPED_UNICODE) . "\n";

    $adAccounts = sc_meta_api('GET', META_BUSINESS_ID . '/owned_ad_accounts', ['fields' => 'id,name,account_status,currency,amount_spent']);
    echo "Ad Accounts : " . json_encode($adAccounts['data'], JSON_UNESCAPED_UNICODE) . "\n";

    $cat = sc_meta_api('GET', META_CATALOG_ID, ['fields' => 'id,name,product_count,vertical']);
    echo "Catalog     : " . json_encode($cat['data'], JSON_UNESCAPED_UNICODE) . "\n";

    $feeds = sc_meta_api('GET', META_CATALOG_ID . '/product_feeds', ['fields' => 'id,name,product_count,schedule,latest_upload']);
    echo "Feeds       : " . json_encode($feeds['data'], JSON_UNESCAPED_UNICODE) . "\n";

    $sources = sc_meta_api('GET', META_CATALOG_ID . '/external_event_sources', ['fields' => 'id,name']);
    echo "Event Srcs  : " . json_encode($sources['data'], JSON_UNESCAPED_UNICODE) . "\n";
    exit(0);
}

if ($cmd === 'setup-catalog') {
    echo "=== Hossam — Automated Meta Catalog Setup (Catalog " . META_CATALOG_ID . ") ===\n";

    // 1. Connect Meta Pixel / Dataset 2980406819004765 to Catalog 555610010235756
    $evRes = sc_meta_api('POST', META_CATALOG_ID . '/external_event_sources', [
        'external_event_sources' => json_encode([META_PIXEL_ID]),
    ]);
    echo "[1/4] Connect Pixel " . META_PIXEL_ID . " -> HTTP {$evRes['status']} " . json_encode($evRes['data']) . "\n";

    // 2. Check existing feeds to avoid duplicates, else create primary scheduled XML feed
    $existingFeeds = sc_meta_api('GET', META_CATALOG_ID . '/product_feeds', ['fields' => 'id,name']);
    $primaryFeedName = 'Sea College Live Website Feed (EUR)';
    $i18nFeedName    = 'Sea College 7-Language Override Feed';
    $foundPrimaryId  = '';
    $foundI18nId     = '';
    foreach (($existingFeeds['data']['data'] ?? []) as $f) {
        if (($f['name'] ?? '') === $primaryFeedName) {
            $foundPrimaryId = (string) $f['id'];
        }
        if (($f['name'] ?? '') === $i18nFeedName) {
            $foundI18nId = (string) $f['id'];
        }
    }

    if ($foundPrimaryId === '') {
        $fRes = sc_meta_api('POST', META_CATALOG_ID . '/product_feeds', [
            'name'     => $primaryFeedName,
            'schedule' => json_encode([
                'interval' => 'DAILY',
                'url'      => SITE_URL . '/feeds/meta-catalog.xml',
                'hour'     => 4,
            ]),
        ]);
        $foundPrimaryId = (string) ($fRes['data']['id'] ?? '');
        echo "[2/4] Created Primary Feed -> HTTP {$fRes['status']} " . json_encode($fRes['data']) . "\n";
    } else {
        $uRes = sc_meta_api('POST', $foundPrimaryId . '/uploads', [
            'url' => SITE_URL . '/feeds/meta-catalog.xml',
        ]);
        echo "[2/4] Triggered Upload on Existing Primary Feed ({$foundPrimaryId}) -> HTTP {$uRes['status']} " . json_encode($uRes['data']) . "\n";
    }

    // 3. Attach 7-language override feed
    if ($foundI18nId === '') {
        $lRes = sc_meta_api('POST', META_CATALOG_ID . '/product_feeds', [
            'name'      => $i18nFeedName,
            'feed_type' => 'LANGUAGE',
            'schedule'  => json_encode([
                'interval' => 'DAILY',
                'url'      => SITE_URL . '/feeds/meta-catalog-i18n.xml',
                'hour'     => 5,
            ]),
        ]);
        echo "[3/4] Created 7-Language Override Feed -> HTTP {$lRes['status']} " . json_encode($lRes['data']) . "\n";
    } else {
        $ulRes = sc_meta_api('POST', $foundI18nId . '/uploads', [
            'url' => SITE_URL . '/feeds/meta-catalog-i18n.xml',
        ]);
        echo "[3/4] Triggered Upload on 7-Language Feed ({$foundI18nId}) -> HTTP {$ulRes['status']} " . json_encode($ulRes['data']) . "\n";
    }

    // 4. Create segmented Product Sets for Advantage+ / Carousel Ads
    $sets = [
        'PADI Courses (Sharm)'            => ['custom_label_0' => ['eq' => 'PADI Course']],
        'Value Course Bundles (Save EUR)' => ['custom_label_0' => ['eq' => 'Course Bundle']],
        'Beginner & Intro Dives'          => ['custom_label_0' => ['eq' => 'Intro Dive']],
        'Certified Dive Packages'         => ['custom_label_0' => ['eq' => 'Certified Dive Package']],
        'Wreck & Specialty Dives'         => ['custom_label_0' => ['eq' => 'Wreck & Specialty Dive']],
        'Snorkeling & Private Boats'      => ['custom_label_0' => ['eq' => 'Snorkeling & Boat Charter']],
    ];
    foreach ($sets as $setName => $filter) {
        $sRes = sc_meta_api('POST', META_CATALOG_ID . '/product_sets', [
            'name'   => $setName,
            'filter' => json_encode($filter),
        ]);
        echo "[4/4] Product Set '{$setName}' -> HTTP {$sRes['status']} " . json_encode($sRes['data']) . "\n";
    }
    exit(0);
}

if ($cmd === 'campaigns') {
    $adAccounts = sc_meta_api('GET', META_BUSINESS_ID . '/owned_ad_accounts', ['fields' => 'id,name,currency']);
    foreach (($adAccounts['data']['data'] ?? []) as $act) {
        $actId = (string) ($act['id'] ?? '');
        echo "=== Ad Account {$actId} ({$act['name']} · {$act['currency']}) ===\n";
        $camps = sc_meta_api('GET', $actId . '/campaigns', [
            'fields' => 'id,name,status,effective_status,objective,daily_budget,insights.date_preset(last_30d){spend,impressions,clicks,cpc,ctr,actions}',
        ]);
        echo json_encode($camps['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
    exit(0);
}

echo "Unknown command '{$cmd}'. Use: status | setup-catalog | campaigns\n";
exit(1);
