<?php
/**
 * Hossam — Meta Ads 4-Campaign Funnel (M1–M4) & Graph API PAUSED Stager (CLI-only).
 *
 * Programmatically uploads branded social ad media (adimages & advideos) and stages
 * Sea College Dive Club's 4 ODAX OUTCOME_SALES campaigns, ad sets, creatives, and ads
 * strictly in PAUSED mode (0 EGP spend) on Ad Account act_1810425550247894:
 *   - M1: Retargeting Calculator & Catalog (26,000 piastres = 260 EGP/day)
 *   - M2: In-Sharm 15km Beginner DSD & Mixed-Couple Same Yacht (31,200 piastres = 312 EGP/day)
 *   - M3: Outbound EU/UK/CN PADI Courses & Value Bundles Carousel (26,000 piastres = 260 EGP/day)
 *   - M4: GCC & Sharm VIP Private Boats & Tiran Speedboats (20,800 piastres = 208 EGP/day)
 *
 * Usage:
 *   php docs/meta_ads_campaigns.php status [--json]
 *   php docs/meta_ads_campaigns.php stage [--dry-run]
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: CLI only.');
}

require_once dirname(__DIR__) . '/config/site_config.php';

const SC_META_CONVERTED_LEADS_180D = '120255723869490547';
const SC_META_RETARGET_AUDIENCES = [
    '120255723887270547', // Catalog Product Viewers & Cart (30d, Excl Leads)
    '120255723868580547', // AddToCart & Quick-Book Intenders (30d)
    '120255723868400547', // Courses, Trips & Prices Viewers (30d)
    '120255723867980547', // Website All Visitors (30d)
];

const SC_META_PRODUCT_SETS = [
    'all'       => '930441435813873',
    'courses'   => '1449790436667901',
    'bundles'   => '1144259525014883',
    'beginners' => '1700437361802352',
    'packages'  => '1457415763233719',
    'wrecks'    => '1458325092451018',
    'boats'     => '28496955579979986',
];

const SC_VIDEO_EXPORT_DIR = '/home/antigravity/video/exports';

/**
 * Scrub any accidental token string before printing to stdout/stderr.
 */
function sc_scrub_secrets(string $text): string
{
    $secrets = array_filter([
        defined('META_ACCESS_TOKEN') ? META_ACCESS_TOKEN : '',
        defined('META_CAPI_TOKEN') ? META_CAPI_TOKEN : '',
    ], static fn(string $s): bool => strlen($s) >= 12);

    foreach ($secrets as $sec) {
        $text = str_replace([$sec, urlencode($sec)], '[REDACTED_META_TOKEN]', $text);
    }
    return (string) preg_replace('/EAA[A-Za-z0-9]{20,}/', '[REDACTED_META_TOKEN]', $text);
}

/**
 * Execute an authenticated Meta Graph API request without leaking tokens.
 *
 * @param array<string,mixed> $params
 * @return array{http:int, body:array<string,mixed>}
 */
function sc_meta_graph(string $method, string $path, array $params = [], bool $multipart = false, int $timeout = 60): array
{
    $url = 'https://graph.facebook.com/' . META_GRAPH_VERSION . '/' . ltrim($path, '/');
    $params['access_token'] = META_ACCESS_TOKEN;

    $ch = curl_init();
    $methodU = strtoupper($method);
    if ($methodU === 'GET') {
        $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    } else {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $methodU);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $multipart ? $params : http_build_query($params));
    }

    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
    ]);
    $raw = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        $decoded = ['raw' => sc_scrub_secrets($raw !== '' ? $raw : $err)];
    }
    return ['http' => $code, 'body' => $decoded];
}

/**
 * Canonical 4-Campaign ODAX Blueprint (1,040 EGP/day = 104,000 piastres total, strictly PAUSED).
 *
 * @return array<string,array<string,mixed>>
 */
function sc_meta_funnel_blueprint(): array
{
    $excl = [['id' => SC_META_CONVERTED_LEADS_180D]];
    $retargetInc = array_map(static fn(string $id): array => ['id' => $id], SC_META_RETARGET_AUDIENCES);

    return [
        'M1' => [
            'code'             => 'M1',
            'campaign_name'    => 'SC_Meta_M1_Retargeting_Calculator_Catalog',
            'adset_name'       => 'SC_Meta_M1_AdSet_30d_Visitors_Cart_Excl_Leads',
            'creative_name'    => 'SC_Meta_M1_Creative_Dive_Calculator_Zero_Hidden_Fees',
            'ad_name'          => 'SC_Meta_M1_Ad_Calculator_Retargeting',
            'objective'        => 'OUTCOME_SALES',
            'daily_budget'     => 26000, // 260 EGP/day in piastres
            'product_set_id'   => SC_META_PRODUCT_SETS['all'],
            'eu_dsa'           => true,
            'targeting'        => [
                'geo_locations'             => [
                    'countries' => ['GB', 'DE', 'IT', 'FR', 'PL', 'CH', 'AT', 'NL', 'BE', 'SE', 'SA', 'AE', 'KW', 'QA', 'EG', 'CN', 'TR'],
                ],
                'age_min'                   => 18,
                'age_max'                   => 65,
                'custom_audiences'          => $retargetInc,
                'excluded_custom_audiences' => $excl,
                'targeting_automation'      => ['advantage_audience' => 0],
            ],
            'image_file'       => SC_VIDEO_EXPORT_DIR . '/ad_images/sea-college-dive-club-sharm-el-sheikh-dive-budget-calculator-zero-hidden-fees-4x5.jpg',
            'landing_url'      => SITE_URL . '/prices?utm_source=meta&utm_medium=paid_social&utm_campaign=sc_meta_m1_retargeting#calculator',
            'url_tags'         => 'utm_source=meta&utm_medium=paid_social&utm_campaign=sc_meta_m1_retargeting&utm_content={{ad.name}}',
            'primary_text'     => "Still planning your Red Sea dives in Sharm El Sheikh? Build your exact package in 30 seconds with our transparent EUR Dive Calculator — Max 4 divers per PADI guide, free hotel pickup & 100% zero hidden marina fees! (€0 upfront deposit)",
            'headline'         => 'PADI 5★ Sharm Dive Calculator — Zero Hidden Fees',
            'description'      => '4.9★ (3,082+ Reviews) · Max 4 Divers/Guide · Free Pickup',
            'cta'              => 'LEARN_MORE',
        ],
        'M2' => [
            'code'             => 'M2',
            'campaign_name'    => 'SC_Meta_M2_InSharm_15km_Beginner_DSD_Couples_Yacht',
            'adset_name'       => 'SC_Meta_M2_AdSet_Sharm_15km_Tourists_18plus',
            'creative_name'    => 'SC_Meta_M2_Creative_Beginner_DSD_And_Couples_Same_Yacht',
            'ad_name'          => 'SC_Meta_M2_Ad_InSharm_Beginner_Couples_Yacht',
            'objective'        => 'OUTCOME_SALES',
            'daily_budget'     => 31200, // 312 EGP/day in piastres
            'product_set_id'   => SC_META_PRODUCT_SETS['beginners'],
            'eu_dsa'           => false,
            'targeting'        => [
                'geo_locations'             => [
                    'custom_locations' => [[
                        'latitude'      => 27.9158,
                        'longitude'     => 34.3300,
                        'radius'        => 15,
                        'distance_unit' => 'kilometer',
                    ]],
                ],
                'age_min'                   => 18,
                'age_max'                   => 60,
                'excluded_custom_audiences' => $excl,
                'targeting_automation'      => ['advantage_audience' => 0],
            ],
            'image_file'       => SC_VIDEO_EXPORT_DIR . '/ad_images/sea-college-dive-club-sharm-el-sheikh-couples-same-yacht-oceano-nizar-omar4-partner-intro-45eur-4x5.jpg',
            'secondary_image'  => SC_VIDEO_EXPORT_DIR . '/ad_images/sea-college-dive-club-sharm-el-sheikh-beginner-intro-dive-reef-30eur-boat-100eur-4x5.jpg',
            'landing_url'      => SITE_URL . '/tours/discover-scuba-diving-2-dives-boat-sharm?utm_source=meta&utm_medium=paid_social&utm_campaign=sc_meta_m2_insharm_couples',
            'url_tags'         => 'utm_source=meta&utm_medium=paid_social&utm_campaign=sc_meta_m2_insharm_couples&utm_content={{ad.name}}',
            'primary_text'     => "In Sharm El Sheikh right now? Try first-time Scuba from €30 (House Reef) or €100 Full Yacht Day (2 Guided Intro Dives)! Diver + non-diver partner? Ride the exact same big yacht (Oceano, Nizar, Omar 4) together — Partner Intro Dive +€45, free hotel transfer & zero hidden fees! WhatsApp +201114567886",
            'headline'         => 'First-Time Scuba €30 · Couples Same Yacht (+€45)',
            'description'      => 'Free Sharm Hotel Pickup · PADI 1-on-1 Instructor',
            'cta'              => 'BOOK_TRAVEL',
        ],
        'M3' => [
            'code'             => 'M3',
            'campaign_name'    => 'SC_Meta_M3_Outbound_EU_CN_PADI_Courses_Bundles_Carousel',
            'adset_name'       => 'SC_Meta_M3_AdSet_EU_UK_CN_PADI_Courses_Bundles',
            'creative_name'    => 'SC_Meta_M3_Creative_PADI_Courses_Value_Bundles_Carousel',
            'ad_name'          => 'SC_Meta_M3_Ad_PADI_Courses_And_Bundles_Carousel',
            'objective'        => 'OUTCOME_SALES',
            'daily_budget'     => 26000, // 260 EGP/day in piastres
            'product_set_id'   => SC_META_PRODUCT_SETS['bundles'],
            'eu_dsa'           => true,
            'targeting'        => [
                'geo_locations'             => [
                    'countries' => ['DE', 'IT', 'FR', 'PL', 'GB', 'CH', 'AT', 'ES', 'NL', 'CN', 'KZ'],
                ],
                'age_min'                   => 18,
                'age_max'                   => 58,
                'excluded_custom_audiences' => $excl,
                'targeting_automation'      => ['advantage_audience' => 0],
            ],
            'image_file'       => SC_VIDEO_EXPORT_DIR . '/ad_images/sea-college-dive-club-sharm-el-sheikh-padi-courses-value-bundles-overview-4x5.jpg',
            'carousel_cards'   => [
                [
                    'image_file'  => SC_VIDEO_EXPORT_DIR . '/ad_images/sea-college-dive-club-sharm-el-sheikh-padi-open-water-course-400eur-carousel-01-1x1.jpg',
                    'headline'    => 'PADI Open Water Course — €400',
                    'description' => '3.5 Days · Max 4 Divers/Guide',
                    'link'        => SITE_URL . '/courses/padi-open-water-diver-sharm-el-sheikh?utm_source=meta&utm_medium=paid_social&utm_campaign=sc_meta_m3_padi_carousel',
                ],
                [
                    'image_file'  => SC_VIDEO_EXPORT_DIR . '/ad_images/sea-college-dive-club-sharm-el-sheikh-padi-stars-bundle-owd-nitrox-500eur-carousel-02-1x1.jpg',
                    'headline'    => 'Stars Bundle (OWD + Nitrox) — €500',
                    'description' => 'Save €45 · PADI eLearning Included',
                    'link'        => SITE_URL . '/courses/stars-bundle-open-water-nitrox-sharm?utm_source=meta&utm_medium=paid_social&utm_campaign=sc_meta_m3_padi_carousel',
                ],
                [
                    'image_file'  => SC_VIDEO_EXPORT_DIR . '/ad_images/sea-college-dive-club-sharm-el-sheikh-padi-mega-bundle-owd-aowd-600eur-carousel-03-1x1.jpg',
                    'headline'    => 'Mega Bundle (OWD + AOWD) — €600',
                    'description' => '0 to 30m Certified · Gear Included',
                    'link'        => SITE_URL . '/courses/mega-bundle-open-water-advanced-sharm?utm_source=meta&utm_medium=paid_social&utm_campaign=sc_meta_m3_padi_carousel',
                ],
                [
                    'image_file'  => SC_VIDEO_EXPORT_DIR . '/ad_images/sea-college-dive-club-sharm-el-sheikh-certified-10-dive-package-350eur-carousel-04-1x1.jpg',
                    'headline'    => '10 Guided Red Sea Dives — €350',
                    'description' => '€35/Dive · Ras Mohammed & Tiran',
                    'link'        => SITE_URL . '/tours/10-dives-package-sharm-el-sheikh?utm_source=meta&utm_medium=paid_social&utm_campaign=sc_meta_m3_padi_carousel',
                ],
            ],
            'landing_url'      => SITE_URL . '/diving-courses-sharm-el-sheikh?utm_source=meta&utm_medium=paid_social&utm_campaign=sc_meta_m3_padi_carousel',
            'url_tags'         => 'utm_source=meta&utm_medium=paid_social&utm_campaign=sc_meta_m3_padi_carousel&utm_content={{ad.name}}',
            'primary_text'     => "Get PADI Certified in warm crystal-clear Red Sea waters at Sea College Dive Club (PADI 5★ Resort, Sharm El Sheikh): Open Water €400 · Advanced €350 · Mega Bundle (OWD+AOWD) €600 · 10-Dive Pack €350 (€35/dive). Max 4 divers per guide & zero hidden fees!",
            'headline'         => 'PADI 5★ Courses & Value Bundles in Sharm',
            'description'      => 'OWD €400 · Mega Bundle €600 · €0 Online Deposit',
            'cta'              => 'BOOK_TRAVEL',
        ],
        'M4' => [
            'code'             => 'M4',
            'campaign_name'    => 'SC_Meta_M4_GCC_Sharm_VIP_Private_Boats_Speedboats',
            'adset_name'       => 'SC_Meta_M4_AdSet_GCC_Sharm_VIP_Private_Boats',
            'creative_name'    => 'SC_Meta_M4_Creative_VIP_Private_Speedboat_150EUR_Yacht_550EUR',
            'ad_name'          => 'SC_Meta_M4_Ad_VIP_Private_Boats_AR_EN',
            'objective'        => 'OUTCOME_SALES',
            'daily_budget'     => 20800, // 208 EGP/day in piastres
            'product_set_id'   => SC_META_PRODUCT_SETS['boats'],
            'eu_dsa'           => false,
            'targeting'        => [
                'geo_locations'             => [
                    'countries' => ['SA', 'AE', 'KW', 'QA', 'BH', 'EG'],
                ],
                'age_min'                   => 21,
                'age_max'                   => 65,
                'excluded_custom_audiences' => $excl,
                'targeting_automation'      => ['advantage_audience' => 0],
            ],
            'image_file'       => SC_VIDEO_EXPORT_DIR . '/ad_images/sea-college-dive-club-sharm-el-sheikh-vip-private-tiran-speedboat-150eur-yacht-550eur-bilingual-4x5.jpg',
            'landing_url'      => SITE_URL . '/tours/tiran-island-private-speedboat-2-hours?utm_source=meta&utm_medium=paid_social&utm_campaign=sc_meta_m4_vip_boats',
            'arabic_url'       => SITE_URL . '/ar/snorkeling-and-boats-sharm-el-sheikh?utm_source=meta&utm_medium=paid_social&utm_campaign=sc_meta_m4_vip_boats',
            'url_tags'         => 'utm_source=meta&utm_medium=paid_social&utm_campaign=sc_meta_m4_vip_boats&utm_content={{ad.name}}',
            'primary_text'     => "استأجر لنش سريع خاص لجزيرة تيران (€150 ساعتين) أو يخت خاص ليوم كامل في شرم الشيخ (€550–€1,100) مع توصيل مجاني من الفندق! Private Tiran Island Speedboat (€150 / 2h) & Full-Day Private Red Sea Yachts (€550–€1,100) — Zero hidden fees!",
            'headline'         => 'VIP Private Tiran Speedboat €150 · Yacht €550',
            'description'      => 'رحلات بحرية خاصة في شرم الشيخ · Free Hotel VIP Transfer',
            'cta'              => 'BOOK_TRAVEL',
        ],
    ];
}

/**
 * Fetch complete read-only status snapshot from Meta Graph API.
 *
 * @return array<string,mixed>
 */
function sc_meta_collect_status(): array
{
    $blueprint = sc_meta_funnel_blueprint();

    $acct      = sc_meta_graph('GET', META_AD_ACCOUNT_ID, ['fields' => 'id,name,account_status,currency,amount_spent,funding_source,timezone_name,default_dsa_beneficiary,default_dsa_payor']);
    $camps     = sc_meta_graph('GET', META_AD_ACCOUNT_ID . '/campaigns', ['limit' => 100, 'fields' => 'id,name,status,effective_status,objective,daily_budget,buying_type']);
    $adsets    = sc_meta_graph('GET', META_AD_ACCOUNT_ID . '/adsets', ['limit' => 100, 'fields' => 'id,name,campaign_id,status,effective_status,daily_budget,billing_event,optimization_goal,destination_type,promoted_object,targeting,dsa_beneficiary,dsa_payor']);
    $ads       = sc_meta_graph('GET', META_AD_ACCOUNT_ID . '/ads', ['limit' => 100, 'fields' => 'id,name,adset_id,campaign_id,status,effective_status,creative{id,name,object_story_spec,url_tags,product_set_id}']);
    $creatives = sc_meta_graph('GET', META_AD_ACCOUNT_ID . '/adcreatives', ['limit' => 100, 'fields' => 'id,name,object_story_spec,url_tags,product_set_id']);
    $images    = sc_meta_graph('GET', META_AD_ACCOUNT_ID . '/adimages', ['limit' => 100, 'fields' => 'hash,name,width,height,status']);
    $videos    = sc_meta_graph('GET', META_AD_ACCOUNT_ID . '/advideos', ['limit' => 100, 'fields' => 'id,title,length,status']);
    $audiences = sc_meta_graph('GET', META_AD_ACCOUNT_ID . '/customaudiences', ['limit' => 100, 'fields' => 'id,name,subtype']);
    $sets      = sc_meta_graph('GET', META_CATALOG_ID . '/product_sets', ['limit' => 50, 'fields' => 'id,name,product_count']);

    $stagedSpecs = [];
    foreach ($blueprint as $code => $bp) {
        $cardsOut = [];
        foreach (($bp['carousel_cards'] ?? []) as $card) {
            $cardsOut[] = [
                'headline' => $card['headline'],
                'link'     => $card['link'],
            ];
        }
        $stagedSpecs[$code] = [
            'campaign_name'        => $bp['campaign_name'],
            'adset_name'           => $bp['adset_name'],
            'creative_name'        => $bp['creative_name'],
            'ad_name'              => $bp['ad_name'],
            'daily_budget_piastre' => $bp['daily_budget'],
            'daily_budget_egp'     => $bp['daily_budget'] / 100,
            'product_set_id'       => $bp['product_set_id'],
            'landing_url'          => $bp['landing_url'],
            'arabic_url'           => $bp['arabic_url'] ?? null,
            'carousel_cards'       => $cardsOut,
            'excluded_audience_id' => SC_META_CONVERTED_LEADS_180D,
            'eu_dsa_beneficiary'   => $bp['eu_dsa'] ? 'Sea College Dive Club' : null,
            'eu_dsa_payor'         => $bp['eu_dsa'] ? 'Sea College Dive Club' : null,
            'whatsapp_sales_desk'  => '+' . WHATSAPP_NUMBER,
        ];
    }

    $hasFunding = !empty($acct['body']['funding_source']);
    $ownerActions = [];
    if (!$hasFunding) {
        $ownerActions[] = 'Add payment method (EGP/USD card) in Meta Billing & Payments on act_1810425550247894 before switching PAUSED campaigns to ACTIVE.';
    }
    $ownerActions[] = 'Switch Meta App "Sea College Marketing" (1547970967215250) from Development to Live mode in developers.facebook.com, then re-run `php docs/meta_ads_campaigns.php stage` to publish Page-backed AdCreatives & Ads.';
    $ownerActions[] = 'Ensure System User or Admin holds Advertise (ADS_CREATE / promote_pages) task on Facebook Page 114089131683616.';

    return [
        'generated_at'           => gmdate('Y-m-d\TH:i:s\Z'),
        'ad_account_id'          => META_AD_ACCOUNT_ID,
        'business_id'            => META_BUSINESS_ID,
        'page_id'                => META_PAGE_ID,
        'instagram_id'           => defined('META_IG_USER_ID') ? META_IG_USER_ID : '17841459801345654',
        'pixel_id'               => META_PIXEL_ID,
        'catalog_id'             => META_CATALOG_ID,
        'account'                => $acct['body'],
        'campaigns_count'        => count($camps['body']['data'] ?? []),
        'adsets_count'           => count($adsets['body']['data'] ?? []),
        'adcreatives_count'      => count($creatives['body']['data'] ?? []),
        'ads_count'              => count($ads['body']['data'] ?? []),
        'adimages_count'         => count($images['body']['data'] ?? []),
        'advideos_count'         => count($videos['body']['data'] ?? []),
        'campaigns'              => $camps['body']['data'] ?? [],
        'adsets'                 => $adsets['body']['data'] ?? [],
        'adcreatives'            => $creatives['body']['data'] ?? [],
        'ads'                    => $ads['body']['data'] ?? [],
        'adimages'               => $images['body']['data'] ?? [],
        'advideos'               => $videos['body']['data'] ?? [],
        'custom_audiences'       => $audiences['body']['data'] ?? [],
        'product_sets'           => $sets['body']['data'] ?? [],
        'staged_funnel_specs'    => $stagedSpecs,
        'owner_ui_actions_left'  => $ownerActions,
    ];
}

$cmd = strtolower(trim((string) ($argv[1] ?? 'status')));
$flags = array_slice($argv, 2);
$wantJson = in_array('--json', $flags, true);
$dryRun   = in_array('--dry-run', $flags, true);

if (META_ACCESS_TOKEN === '') {
    $errPayload = ['error' => 'META_ACCESS_TOKEN missing in config/secrets.php', 'ad_account_id' => META_AD_ACCOUNT_ID];
    echo $wantJson ? json_encode($errPayload, JSON_PRETTY_PRINT) . "\n" : "Missing META_ACCESS_TOKEN\n";
    exit(1);
}

if ($cmd === 'status') {
    $snap = sc_meta_collect_status();
    if ($wantJson) {
        echo sc_scrub_secrets((string) json_encode($snap, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "\n";
    } else {
        echo "=== Hossam — Meta Ads 4-Campaign Funnel Status ({$snap['ad_account_id']}) ===\n";
        echo "Currency / Spend : " . ($snap['account']['currency'] ?? 'EGP') . ' / ' . ($snap['account']['amount_spent'] ?? '0') . "\n";
        echo "Campaigns Staged : {$snap['campaigns_count']} | Ad Sets: {$snap['adsets_count']} | Creatives: {$snap['adcreatives_count']} | Ads: {$snap['ads_count']}\n";
        echo "Uploaded Media   : {$snap['adimages_count']} adimages | {$snap['advideos_count']} advideos\n";
        foreach ($snap['campaigns'] as $c) {
            echo "  - [{$c['status']}] {$c['name']} (id={$c['id']}, obj={$c['objective']})\n";
        }
    }
    exit(0);
}

if ($cmd === 'stage') {
    $blueprint = sc_meta_funnel_blueprint();
    echo "=== Hossam — Staging M1–M4 Meta Campaigns (PAUSED, 0 Spend) on " . META_AD_ACCOUNT_ID . ($dryRun ? ' [DRY-RUN]' : '') . " ===\n";

    // 1. Index existing objects for strict idempotency
    $existingImagesResp = sc_meta_graph('GET', META_AD_ACCOUNT_ID . '/adimages', ['limit' => 100, 'fields' => 'hash,name']);
    $imagesByName = [];
    foreach (($existingImagesResp['body']['data'] ?? []) as $img) {
        if (!empty($img['name']) && !empty($img['hash'])) {
            $imagesByName[(string) $img['name']] = (string) $img['hash'];
        }
    }

    $existingVideosResp = sc_meta_graph('GET', META_AD_ACCOUNT_ID . '/advideos', ['limit' => 100, 'fields' => 'id,title']);
    $videosByTitle = [];
    foreach (($existingVideosResp['body']['data'] ?? []) as $vid) {
        if (!empty($vid['title']) && !empty($vid['id'])) {
            $videosByTitle[(string) $vid['title']] = (string) $vid['id'];
        }
    }

    // 2. Upload adimages needed across M1..M4
    $allImagePaths = [];
    foreach ($blueprint as $bp) {
        $allImagePaths[] = (string) $bp['image_file'];
        if (!empty($bp['secondary_image'])) {
            $allImagePaths[] = (string) $bp['secondary_image'];
        }
        foreach (($bp['carousel_cards'] ?? []) as $card) {
            $allImagePaths[] = (string) $card['image_file'];
        }
    }
    $allImagePaths = array_values(array_unique($allImagePaths));

    $hashesByPath = [];
    foreach ($allImagePaths as $imgPath) {
        $base = basename($imgPath);
        if (isset($imagesByName[$base])) {
            $hashesByPath[$imgPath] = $imagesByName[$base];
            echo "[Image OK] {$base} -> hash {$hashesByPath[$imgPath]}\n";
            continue;
        }
        if (!is_file($imgPath)) {
            echo "[Image WARN] Missing local file {$imgPath}\n";
            continue;
        }
        if ($dryRun) {
            echo "[Image DRY] Would upload {$base}\n";
            continue;
        }
        $up = sc_meta_graph('POST', META_AD_ACCOUNT_ID . '/adimages', [
            'filename' => new CURLFile($imgPath, 'image/jpeg', $base),
        ], true, 60);
        $hash = '';
        foreach (($up['body']['images'] ?? []) as $info) {
            if (!empty($info['hash'])) {
                $hash = (string) $info['hash'];
                break;
            }
        }
        if ($hash !== '') {
            $hashesByPath[$imgPath] = $hash;
            $imagesByName[$base] = $hash;
            echo "[Image UP] {$base} -> hash {$hash}\n";
        } else {
            echo "[Image ERR] HTTP {$up['http']} " . sc_scrub_secrets((string) json_encode($up['body'])) . "\n";
        }
    }

    // 3. Upload mastered vertical Reel video to advideos (idempotent by title)
    $videosToUpload = [
        SC_VIDEO_EXPORT_DIR . '/videos/sea-college-dive-club-sharm-el-sheikh-tiran-dive-briefing-thomas-jackson-reef-shorts.mp4',
        SC_VIDEO_EXPORT_DIR . '/videos/sea-college-dive-club-sharm-el-sheikh-red-sea-coral-wall-scuba-diving-reel.mp4',
    ];
    foreach ($videosToUpload as $vPath) {
        $vTitle = pathinfo($vPath, PATHINFO_FILENAME);
        if (isset($videosByTitle[$vTitle])) {
            echo "[Video OK] {$vTitle} -> id {$videosByTitle[$vTitle]}\n";
            continue;
        }
        if (!is_file($vPath)) {
            echo "[Video WARN] Missing {$vPath}\n";
            continue;
        }
        if ($dryRun) {
            echo "[Video DRY] Would upload {$vTitle}\n";
            continue;
        }
        $vUp = sc_meta_graph('POST', META_AD_ACCOUNT_ID . '/advideos', [
            'title'  => $vTitle,
            'source' => new CURLFile($vPath, 'video/mp4', basename($vPath)),
        ], true, 180);
        $vidId = (string) ($vUp['body']['id'] ?? '');
        if ($vidId !== '') {
            $videosByTitle[$vTitle] = $vidId;
            echo "[Video UP] {$vTitle} -> id {$vidId}\n";
        } else {
            echo "[Video ERR] HTTP {$vUp['http']} " . sc_scrub_secrets((string) json_encode($vUp['body'])) . "\n";
        }
    }

    // 4. Index existing campaigns, adsets, creatives, and ads
    $campsByName = [];
    foreach ((sc_meta_graph('GET', META_AD_ACCOUNT_ID . '/campaigns', ['limit' => 100, 'fields' => 'id,name,status'])['body']['data'] ?? []) as $c) {
        $campsByName[(string) $c['name']] = (string) $c['id'];
    }
    $adsetsByName = [];
    foreach ((sc_meta_graph('GET', META_AD_ACCOUNT_ID . '/adsets', ['limit' => 100, 'fields' => 'id,name,campaign_id'])['body']['data'] ?? []) as $a) {
        $adsetsByName[(string) $a['name']] = (string) $a['id'];
    }
    $creativesByName = [];
    foreach ((sc_meta_graph('GET', META_AD_ACCOUNT_ID . '/adcreatives', ['limit' => 100, 'fields' => 'id,name'])['body']['data'] ?? []) as $cr) {
        $creativesByName[(string) $cr['name']] = (string) $cr['id'];
    }
    $adsByName = [];
    foreach ((sc_meta_graph('GET', META_AD_ACCOUNT_ID . '/ads', ['limit' => 100, 'fields' => 'id,name'])['body']['data'] ?? []) as $ad) {
        $adsByName[(string) $ad['name']] = (string) $ad['id'];
    }

    // 5. Stage each M1..M4 Campaign -> Ad Set -> Creative -> Ad (strictly PAUSED)
    foreach ($blueprint as $code => $bp) {
        $cName = (string) $bp['campaign_name'];
        $campId = $campsByName[$cName] ?? '';
        if ($campId === '') {
            if ($dryRun) {
                echo "[{$code} DRY] Would create PAUSED campaign {$cName}\n";
                continue;
            }
            $cParams = [
                'name'                            => $cName,
                'objective'                       => $bp['objective'],
                'status'                          => 'PAUSED',
                'special_ad_categories'           => '[]',
                'is_adset_budget_sharing_enabled' => 'false',
            ];
            $cRes = sc_meta_graph('POST', META_AD_ACCOUNT_ID . '/campaigns', $cParams);
            $campId = (string) ($cRes['body']['id'] ?? '');
            if ($campId === '') {
                echo "[{$code} Campaign ERR] HTTP {$cRes['http']} " . sc_scrub_secrets((string) json_encode($cRes['body'])) . "\n";
                continue;
            }
            $campsByName[$cName] = $campId;
            echo "[{$code} Campaign CREATED] {$cName} -> {$campId} (PAUSED)\n";
        } else {
            echo "[{$code} Campaign OK] {$cName} -> {$campId}\n";
        }

        // Create Ad Set (PAUSED, EGP piastres, 180d Lead exclusion, EU DSA if applicable)
        $asName = (string) $bp['adset_name'];
        $adsetId = $adsetsByName[$asName] ?? '';
        if ($adsetId === '') {
            $asParams = [
                'name'              => $asName,
                'campaign_id'       => $campId,
                'daily_budget'      => (string) $bp['daily_budget'],
                'billing_event'     => 'IMPRESSIONS',
                'optimization_goal' => 'OFFSITE_CONVERSIONS',
                'bid_strategy'      => 'LOWEST_COST_WITHOUT_CAP',
                'destination_type'  => 'WEBSITE',
                'promoted_object'   => json_encode([
                    'pixel_id'          => META_PIXEL_ID,
                    'custom_event_type' => 'PURCHASE',
                ]),
                'targeting'         => json_encode($bp['targeting']),
                'status'            => 'PAUSED',
            ];
            if (!empty($bp['eu_dsa'])) {
                $asParams['dsa_beneficiary'] = 'Sea College Dive Club';
                $asParams['dsa_payor']       = 'Sea College Dive Club';
            }
            $asRes = sc_meta_graph('POST', META_AD_ACCOUNT_ID . '/adsets', $asParams);
            $adsetId = (string) ($asRes['body']['id'] ?? '');
            if ($adsetId === '') {
                echo "[{$code} AdSet ERR] HTTP {$asRes['http']} " . sc_scrub_secrets((string) json_encode($asRes['body'])) . "\n";
                continue;
            }
            $adsetsByName[$asName] = $adsetId;
            echo "[{$code} AdSet CREATED] {$asName} -> {$adsetId} ({$bp['daily_budget']} piastres, PAUSED)\n";
        } else {
            echo "[{$code} AdSet OK] {$asName} -> {$adsetId}\n";
        }

        // Create AdCreative
        $crName = (string) $bp['creative_name'];
        $creativeId = $creativesByName[$crName] ?? '';
        $mainHash = $hashesByPath[(string) $bp['image_file']] ?? '';
        if ($creativeId === '' && $mainHash !== '') {
            $storySpec = [
                'page_id' => META_PAGE_ID,
            ];
            if ($code === 'M3' && !empty($bp['carousel_cards'])) {
                $attachments = [];
                foreach ($bp['carousel_cards'] as $card) {
                    $cHash = $hashesByPath[(string) $card['image_file']] ?? $mainHash;
                    $attachments[] = [
                        'name'           => $card['headline'],
                        'description'    => $card['description'],
                        'link'           => $card['link'],
                        'image_hash'     => $cHash,
                        'call_to_action' => ['type' => $bp['cta'], 'value' => ['link' => $card['link']]],
                    ];
                }
                $storySpec['link_data'] = [
                    'message'               => $bp['primary_text'],
                    'link'                  => $bp['landing_url'],
                    'multi_share_end_card'  => false,
                    'child_attachments'     => $attachments,
                ];
            } else {
                $storySpec['link_data'] = [
                    'message'        => $bp['primary_text'],
                    'link'           => $bp['landing_url'],
                    'name'           => $bp['headline'],
                    'description'    => $bp['description'],
                    'image_hash'     => $mainHash,
                    'call_to_action' => ['type' => $bp['cta'], 'value' => ['link' => $bp['landing_url']]],
                ];
            }

            $crParams = [
                'name'              => $crName,
                'object_story_spec' => json_encode($storySpec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'url_tags'          => $bp['url_tags'],
            ];
            $crRes = sc_meta_graph('POST', META_AD_ACCOUNT_ID . '/adcreatives', $crParams);
            $creativeId = (string) ($crRes['body']['id'] ?? '');
            if ($creativeId !== '') {
                $creativesByName[$crName] = $creativeId;
                echo "[{$code} Creative CREATED] {$crName} -> {$creativeId}\n";
            } else {
                echo "[{$code} Creative WARN] HTTP {$crRes['http']} " . sc_scrub_secrets((string) json_encode($crRes['body'])) . "\n";
            }
        } elseif ($creativeId !== '') {
            echo "[{$code} Creative OK] {$crName} -> {$creativeId}\n";
        }

        // Create Ad (strictly PAUSED) if Creative succeeded
        $adName = (string) $bp['ad_name'];
        $adId = $adsByName[$adName] ?? '';
        if ($adId === '' && $creativeId !== '' && $adsetId !== '') {
            $adRes = sc_meta_graph('POST', META_AD_ACCOUNT_ID . '/ads', [
                'name'     => $adName,
                'adset_id' => $adsetId,
                'creative' => json_encode(['creative_id' => $creativeId]),
                'status'   => 'PAUSED',
            ]);
            $adId = (string) ($adRes['body']['id'] ?? '');
            if ($adId !== '') {
                $adsByName[$adName] = $adId;
                echo "[{$code} Ad CREATED] {$adName} -> {$adId} (PAUSED)\n";
            } else {
                echo "[{$code} Ad WARN] HTTP {$adRes['http']} " . sc_scrub_secrets((string) json_encode($adRes['body'])) . "\n";
            }
        } elseif ($adId !== '') {
            echo "[{$code} Ad OK] {$adName} -> {$adId}\n";
        }
    }

    echo "=== Done staging M1–M4 on " . META_AD_ACCOUNT_ID . " ===\n";
    exit(0);
}

echo "Unknown command '{$cmd}'. Use: status [--json] | stage [--dry-run]\n";
exit(1);
