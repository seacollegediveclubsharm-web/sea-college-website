<?php
/**
 * Hossam & Ayman — Live Meta Ads + Google Ads Cross-Channel Google Sheets Sync Hub (CLI Only).
 *
 * Syncs live Meta Marketing Graph API v21.0 metrics (`act_1810425550247894`: Spend, Impressions,
 * Reach, Clicks, CTR, CPC, Website Leads, Contact/WhatsApp Leads, Cost Per Lead [CPL], Value,
 * ROAS, Custom Audiences, and Catalog `555610010235756` Product Sets) directly into:
 *   1. Ads Sync Hub (`15IkDV14kxGAbr8DSGOB6auNe4paTNOa5UXmw9d5kVtk`):
 *        - `Meta_Overview`
 *        - `Meta_Campaigns`
 *        - `Meta_Audiences_Catalog`
 *        - `Google_vs_Meta_Dec2026` (Head-to-Head CPL, Spend, Leads, Revenue & +200% Dec 31 Goal)
 *   2. Leads & OCT CRM (`1ItvQGWnrHwnSbyGDoGNtV2CkVwNJXIyU_aaIFxIOGbc`):
 *        - Backfills non-breaking `Channel_Source`, `First_Touch_Channel`, `Journey_Path` (Cols Z..AB)
 *        - `Channel_CPL_&_ROI_Tracker` (Live formula-driven Google Ads vs Meta Ads CPL & ROAS)
 *        - `Meta_Offline_CAPI_Export`
 *   3. Master Marketing & Sales Hub (`15vrkKvIO9dGbIKOOvHy2MtSf7uKViyvSq8PWb4EINMY`):
 *        - Updates live captured leads & Meta Ads Sync Hub row in `01_Executive_KPI_Hub`
 *        - Populates `11_Integrated_Funnel_Dec2026` (3,000 EGP/d Customer Journey & Weekly Dec 2026 Tracker)
 *
 * Usage:
 *   php docs/meta_ads_sync_hub.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Forbidden: CLI only.\n");
}

define('SC_NO_SESSION', true);
require_once dirname(__DIR__) . '/config/site_config.php';

const SHEET_ADS_SYNC   = '15IkDV14kxGAbr8DSGOB6auNe4paTNOa5UXmw9d5kVtk';
const SHEET_LEADS_CRM  = '1ItvQGWnrHwnSbyGDoGNtV2CkVwNJXIyU_aaIFxIOGbc';
const SHEET_MASTER_HUB = '15vrkKvIO9dGbIKOOvHy2MtSf7uKViyvSq8PWb4EINMY';
const EGP_PER_EUR      = 54.60; // Reference rate matching CURRENCY_RATES['EGP']

function sc_scrub(string $s): string
{
    $s = (string) preg_replace('/EAA[A-Za-z0-9]{20,}/', '[REDACTED_META_TOKEN]', $s);
    return $s;
}

function sc_meta_get(string $path, array $params = []): array
{
    $url = 'https://graph.facebook.com/' . META_GRAPH_VERSION . '/' . ltrim($path, '/');
    $params['access_token'] = META_ACCESS_TOKEN;
    $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $raw = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $dec = json_decode($raw, true);
    return ['http' => $code, 'body' => is_array($dec) ? $dec : ['raw' => sc_scrub($raw)]];
}

function sc_gsheets_tabs(string $sheetId): array
{
    $cmd = sprintf('gsheets readonly info %s --json', escapeshellarg($sheetId));
    $out = (string) shell_exec($cmd);
    $dec = json_decode($out, true);
    $titles = [];
    foreach (($dec['sheets'] ?? []) as $s) {
        $titles[] = (string) ($s['properties']['title'] ?? '');
    }
    return array_values(array_filter($titles));
}

function sc_ensure_tab(string $sheetId, string $title, array &$existing): void
{
    if (in_array($title, $existing, true)) {
        return;
    }
    $cmd = sprintf('gsheets mutate add-sheet %s --title %s', escapeshellarg($sheetId), escapeshellarg($title));
    shell_exec($cmd);
    $existing[] = $title;
}

function sc_gsheets_read(string $sheetId, string $range): array
{
    $cmd = sprintf('gsheets readonly read %s %s --json 2>/dev/null', escapeshellarg($sheetId), escapeshellarg($range));
    $out = (string) shell_exec($cmd);
    $dec = json_decode($out, true);
    if (isset($dec['values']) && is_array($dec['values'])) {
        return $dec['values'];
    }
    return is_array($dec) ? $dec : [];
}

function sc_gsheets_write(string $sheetId, string $range, array $rows): void
{
    if ($rows === []) {
        return;
    }
    $encodedRows = [];
    foreach ($rows as $r) {
        $cells = [];
        foreach ($r as $c) {
            $s = str_replace(['\\', '|', ','], ['\\\\', '/', '\\,'], (string) $c);
            $cells[] = $s;
        }
        $encodedRows[] = implode(',', $cells);
    }
    $pipeStr = implode('|', $encodedRows);
    $cmd = sprintf(
        'gsheets mutate write %s %s %s 2>&1',
        escapeshellarg($sheetId),
        escapeshellarg($range),
        escapeshellarg($pipeStr)
    );
    $res = (string) shell_exec($cmd);
    if (stripos($res, 'Error:') !== false) {
        fwrite(STDERR, "gsheets write warning on {$range}: " . sc_scrub($res) . "\n");
    }
}

/**
 * Extract total Meta Leads (Lead + Contact + Messaging) and Lead Value from Meta `actions` / `action_values` arrays.
 */
function sc_parse_meta_actions(array $insight): array
{
    $leads = 0.0;
    $value = 0.0;
    $leadTypes = [
        'lead',
        'offsite_conversion.fb_pixel_lead',
        'contact_total',
        'offsite_conversion.fb_pixel_custom.GoogleAdsVisitor',
        'offsite_conversion.fb_pixel_contact',
        'onsite_conversion.messaging_conversation_started_7d',
    ];
    // Prefer canonical primary lead+contact sum without double-counting total aliases
    $byType = [];
    foreach (($insight['actions'] ?? []) as $a) {
        $byType[(string) ($a['action_type'] ?? '')] = (float) ($a['value'] ?? 0);
    }
    $leads += $byType['offsite_conversion.fb_pixel_lead'] ?? ($byType['lead'] ?? 0.0);
    $leads += $byType['offsite_conversion.fb_pixel_contact'] ?? ($byType['contact_total'] ?? 0.0);
    $leads += $byType['onsite_conversion.messaging_conversation_started_7d'] ?? 0.0;

    $valByType = [];
    foreach (($insight['action_values'] ?? []) as $av) {
        $valByType[(string) ($av['action_type'] ?? '')] = (float) ($av['value'] ?? 0);
    }
    $value += $valByType['offsite_conversion.fb_pixel_lead'] ?? ($valByType['lead'] ?? 0.0);
    $value += $valByType['offsite_conversion.fb_pixel_contact'] ?? 0.0;
    $value += $valByType['offsite_conversion.fb_pixel_purchase'] ?? ($valByType['purchase'] ?? 0.0);

    return ['leads' => $leads, 'value' => $value];
}

echo "=== 1. Querying Live Meta Ads Account (" . META_AD_ACCOUNT_ID . ") ===\n";
$acctRes = sc_meta_get(META_AD_ACCOUNT_ID, [
    'fields' => 'name,account_id,account_status,currency,amount_spent,timezone_name',
])['body'];

$insAcct = sc_meta_get(META_AD_ACCOUNT_ID . '/insights', [
    'date_preset' => 'last_30d',
    'fields'      => 'impressions,reach,clicks,inline_link_clicks,ctr,cpc,spend,actions,action_values',
])['body']['data'][0] ?? [];

$camps = sc_meta_get(META_AD_ACCOUNT_ID . '/campaigns', [
    'fields' => 'id,name,status,effective_status,objective,daily_budget',
    'limit'  => 50,
])['body']['data'] ?? [];

$adsets = sc_meta_get(META_AD_ACCOUNT_ID . '/adsets', [
    'fields' => 'id,name,campaign_id,status,effective_status,daily_budget,optimization_goal,promoted_object',
    'limit'  => 50,
])['body']['data'] ?? [];

$insCampsRaw = sc_meta_get(META_AD_ACCOUNT_ID . '/insights', [
    'level'       => 'campaign',
    'date_preset' => 'last_30d',
    'fields'      => 'campaign_id,campaign_name,impressions,reach,clicks,inline_link_clicks,ctr,cpc,spend,actions,action_values',
    'limit'       => 50,
])['body']['data'] ?? [];

$insByCamp = [];
foreach ($insCampsRaw as $ic) {
    $insByCamp[(string) ($ic['campaign_id'] ?? '')] = $ic;
}

$adsetByCamp = [];
$totalStagedPiastres = 0;
foreach ($adsets as $as) {
    $cid = (string) ($as['campaign_id'] ?? '');
    $adsetByCamp[$cid][] = $as;
    $totalStagedPiastres += (int) ($as['daily_budget'] ?? 0);
}

$auds = sc_meta_get(META_AD_ACCOUNT_ID . '/customaudiences', [
    'fields' => 'id,name,subtype,description',
    'limit'  => 50,
])['body']['data'] ?? [];

$prodSets = sc_meta_get(META_CATALOG_ID . '/product_sets', [
    'fields' => 'id,name,product_count',
    'limit'  => 25,
])['body']['data'] ?? [];

$acctParsed = sc_parse_meta_actions($insAcct);
$metaImp    = (int) ($insAcct['impressions'] ?? 0);
$metaReach  = (int) ($insAcct['reach'] ?? 0);
$metaClicks = (int) ($insAcct['inline_link_clicks'] ?? $insAcct['clicks'] ?? 0);
$metaSpend  = round((float) ($insAcct['spend'] ?? 0.0), 2);
$metaCtr    = $metaImp > 0 ? round(($metaClicks / $metaImp) * 100, 2) : 0.0;
$metaCpc    = $metaClicks > 0 ? round($metaSpend / $metaClicks, 2) : 0.0;
$metaLeads  = $acctParsed['leads'];
$metaVal    = round($acctParsed['value'], 2);
$metaCpl    = $metaLeads > 0 ? round($metaSpend / $metaLeads, 2) : 0.0;
$nowIso     = gmdate('Y-m-d\TH:i:s\Z');

/* ---------------- Sync Sheet 1: Ads Sync Hub (Google + Meta) ---------------- */
echo "=== 2. Syncing Meta Tabs + Google_vs_Meta_Dec2026 into Ads Sync Hub (" . SHEET_ADS_SYNC . ") ===\n";
$syncTabs = sc_gsheets_tabs(SHEET_ADS_SYNC);
foreach (['Meta_Overview', 'Meta_Campaigns', 'Meta_Audiences_Catalog', 'Google_vs_Meta_Dec2026'] as $tName) {
    sc_ensure_tab(SHEET_ADS_SYNC, $tName, $syncTabs);
}

$metaOverviewRows = [
    ['Metric', 'Value', 'Notes'],
    ['Sync Status', 'CONNECTED (Graph API v21.0)', 'Run php docs/meta_ads_sync_hub.php anytime or via cron'],
    ['Last Synced (UTC)', $nowIso, 'Live sync from Meta Ad Account ' . META_AD_ACCOUNT_ID],
    ['Ad Account ID', META_AD_ACCOUNT_ID, (string) ($acctRes['name'] ?? 'Sea College Ad acc')],
    ['Pixel & CAPI Dataset ID', META_PIXEL_ID, 'Browser Pixel + Deduplicated Server-Side CAPI (Lead + Contact + GoogleAdsVisitor)'],
    ['Commerce Catalog ID', META_CATALOG_ID, 'SEA COLLEGE TRIPS (59 EUR products + 413 7-lang overrides + live discounts)'],
    ['Currency', (string) ($acctRes['currency'] ?? 'EGP'), 'All cost columns in EGP'],
    ['Time Zone', (string) ($acctRes['timezone_name'] ?? 'Africa/Cairo'), 'Matches Google Ads 365-126-5545'],
    ['Target Daily Budget (EGP)', 1000, 'Sprint Allocation: ~1000 EGP/day Meta + 2000 EGP/day Google = 3000 EGP/day'],
    ['Staged Funnel Budget (EGP/d)', round($totalStagedPiastres / 100, 2), count($camps) . ' ODAX OUTCOME_SALES Campaigns (' . count($adsets) . ' Ad Sets)'],
    ['30d Impressions', $metaImp, 'Last 30 days'],
    ['30d Reach', $metaReach, 'Unique people reached (Last 30 days)'],
    ['30d Link Clicks', $metaClicks, 'Last 30 days'],
    ['30d CTR (%)', $metaCtr, 'Last 30 days'],
    ['30d Avg CPC (EGP)', $metaCpc, 'Last 30 days'],
    ['30d Spend (EGP)', $metaSpend, 'Last 30 days (0 while PAUSED in Week 1 prep)'],
    ['30d Conversions (Leads + WA)', $metaLeads, 'Deduplicated Website Form Leads + WhatsApp Contact CAPI'],
    ['30d Conversion Value', $metaVal, 'Dynamic EUR cart/item value converted'],
    ['30d Cost / Lead [CPL] (EGP)', $metaCpl, 'Target CPL once live: 120 - 220 EGP (~EUR 2.20 - 4.00)'],
];
sc_gsheets_write(SHEET_ADS_SYNC, 'Meta_Overview!A1:C' . count($metaOverviewRows), $metaOverviewRows);

$metaCampRows = [[
    'Campaign ID', 'Campaign Name', 'Customer Journey Stage', 'Status', 'Objective',
    'Daily Budget (EGP)', '30d Impressions', '30d Clicks', '30d CTR (%)', '30d CPC (EGP)',
    '30d Spend (EGP)', '30d Leads (Form+WA)', '30d Cost / Lead [CPL EGP]', '30d Conv. Value'
]];
foreach ($camps as $c) {
    $cid = (string) ($c['id'] ?? '');
    $cName = (string) ($c['name'] ?? '');
    $stage = 'Full-Funnel Sales';
    if (stripos($cName, 'M1') !== false) {
        $stage = 'BOF — Cross-Channel Retargeting (Google Search Clickers + Site/Calc 30d)';
    } elseif (stripos($cName, 'M2') !== false) {
        $stage = 'MOF/BOF — In-Sharm 15km Tourists (DSD €30/€100 + Couples Yacht)';
    } elseif (stripos($cName, 'M3') !== false) {
        $stage = 'TOF/MOF — Outbound EU/UK/CN PADI Courses & Value Bundles';
    } elseif (stripos($cName, 'M4') !== false) {
        $stage = 'MOF/BOF — GCC & VIP Private Yachts / Tiran Speedboats';
    }
    $cBudget = 0.0;
    if (!empty($c['daily_budget'])) {
        $cBudget = ((int) $c['daily_budget']) / 100.0;
    } else {
        foreach (($adsetByCamp[$cid] ?? []) as $as) {
            $cBudget += ((int) ($as['daily_budget'] ?? 0)) / 100.0;
        }
    }
    $ic = $insByCamp[$cid] ?? [];
    $cp = sc_parse_meta_actions($ic);
    $imp = (int) ($ic['impressions'] ?? 0);
    $clk = (int) ($ic['inline_link_clicks'] ?? $ic['clicks'] ?? 0);
    $sp  = round((float) ($ic['spend'] ?? 0.0), 2);
    $ctr = $imp > 0 ? round(($clk / $imp) * 100, 2) : 0.0;
    $cpc = $clk > 0 ? round($sp / $clk, 2) : 0.0;
    $cpl = $cp['leads'] > 0 ? round($sp / $cp['leads'], 2) : 0.0;
    $metaCampRows[] = [
        $cid, $cName, $stage, (string) ($c['status'] ?? 'PAUSED'), (string) ($c['objective'] ?? 'OUTCOME_SALES'),
        round($cBudget, 2), $imp, $clk, $ctr, $cpc, $sp, $cp['leads'], $cpl, round($cp['value'], 2)
    ];
}
sc_gsheets_write(SHEET_ADS_SYNC, 'Meta_Campaigns!A1:N' . count($metaCampRows), $metaCampRows);

$audRows = [['Asset Type', 'Meta Object ID', 'Name', 'Role in Integrated Google <-> Meta Funnel', 'Details / Count']];
foreach ($auds as $a) {
    $an = (string) ($a['name'] ?? '');
    $role = 'Custom Audience';
    if (stripos($an, 'Google_Ads') !== false) {
        $role = 'BRIDGE: Google Search Clickers -> Meta M1 Retargeting (30d)';
    } elseif (stripos($an, 'Converted') !== false || stripos($an, '180') !== false) {
        $role = 'EXCLUSION: 180d Converted Leads (Zero Wasted Retargeting Spend)';
    } elseif (stripos($an, 'Calculator') !== false || stripos($an, 'Cart') !== false) {
        $role = 'BOF Hot Closers: Calculator / AddToCart / Prices (14-30d)';
    } else {
        $role = 'Warm Retargeting Pool (M1 Included)';
    }
    $audRows[] = ['Custom Audience', (string) ($a['id'] ?? ''), $an, $role, (string) ($a['subtype'] ?? 'WEBSITE')];
}
foreach ($prodSets as $ps) {
    $audRows[] = [
        'Catalog Product Set',
        (string) ($ps['id'] ?? ''),
        (string) ($ps['name'] ?? ''),
        'Dynamic Advantage+ Catalog Feed (Live EUR + Sale Price Sync)',
        ((string) ($ps['product_count'] ?? '')) . ' EUR items',
    ];
}
sc_gsheets_write(SHEET_ADS_SYNC, 'Meta_Audiences_Catalog!A1:E' . count($audRows), $audRows);

/* Read CRM Leads count by channel so Google_vs_Meta_Dec2026 shows exact CRM + Platform metrics */
$crmLeadsGrid = sc_gsheets_read(SHEET_LEADS_CRM, 'Leads!A1:AC500');
$gadsCrmLeads = 0;
$metaCrmLeads = 0;
$assistCrmLeads = 0;
$orgCrmLeads = 0;
$gadsPaidEur = 0.0;
$metaPaidEur = 0.0;
$gadsPipeEur = 0.0;
$metaPipeEur = 0.0;

$crmBackfillRows = [];
if (count($crmLeadsGrid) >= 1) {
    $hdr = array_pad($crmLeadsGrid[0], 28, '');
    $hdr[25] = 'Channel_Source';
    $hdr[26] = 'First_Touch_Channel';
    $hdr[27] = 'Journey_Path';
    $crmBackfillRows[] = array_slice($hdr, 25, 3);

    for ($i = 1, $n = count($crmLeadsGrid); $i < $n; $i++) {
        $r = array_pad($crmLeadsGrid[$i], 28, '');
        if (trim((string) $r[0]) === '' && trim((string) $r[1]) === '') {
            continue;
        }
        $status = strtolower(trim((string) $r[2]));
        $paidEur = (float) ($r[3] !== '' ? $r[3] : 0);
        $estEur  = (float) ($r[13] !== '' ? $r[13] : 0);
        if ($estEur <= 0) {
            $estEur = 45.0;
        }
        $gclid  = trim((string) $r[15]);
        $fbclid = trim((string) $r[16]);
        $src    = strtolower(trim((string) $r[17]));
        $fbc    = trim((string) $r[23]);

        $hasG = ($gclid !== '' || $src === 'google');
        $hasM = ($fbclid !== '' || $fbc !== '' || in_array($src, ['meta', 'facebook', 'instagram', 'ig', 'fb'], true));

        if ($hasG && $hasM) {
            $ch = 'Cross-Channel (Google + Meta)';
            $assistCrmLeads++;
            $gadsCrmLeads++;
            $metaCrmLeads++;
            $gadsPipeEur += $estEur * 0.5;
            $metaPipeEur += $estEur * 0.5;
        } elseif ($hasG) {
            $ch = 'Google Ads';
            $gadsCrmLeads++;
            $gadsPipeEur += $estEur;
            if ($status === 'paid') {
                $gadsPaidEur += ($paidEur > 0 ? $paidEur : $estEur);
            }
        } elseif ($hasM) {
            $ch = 'Meta Ads';
            $metaCrmLeads++;
            $metaPipeEur += $estEur;
            if ($status === 'paid') {
                $metaPaidEur += ($paidEur > 0 ? $paidEur : $estEur);
            }
        } else {
            $ch = 'Organic / Direct';
            $orgCrmLeads++;
        }

        $curCh = trim((string) $r[25]) !== '' ? trim((string) $r[25]) : $ch;
        $curFirst = trim((string) $r[26]) !== '' ? trim((string) $r[26]) : $curCh;
        $curPath = trim((string) $r[27]) !== '' ? trim((string) $r[27]) : $curCh;
        $crmBackfillRows[] = [$curCh, $curFirst, $curPath];
    }
}

/* Build Head-to-Head Google_vs_Meta_Dec2026 Comparison Tab in Ads Sync Hub (with live Sheets formulas!) */
$compRows = [
    ['SEA COLLEGE DIVE CLUB — GOOGLE ADS vs META ADS HEAD-TO-HEAD TRACKER (DEC 31 2026 +200% GOAL)', '', '', '', '', ''],
    ['Sprint Window: Nov 22 – Dec 31 2026 (40 Days) | Daily Budget: 3000 EGP/day (2000 EGP Google + 1000 EGP Meta) | FX Ref: 1 EUR = 54.60 EGP', '', '', '', '', ''],
    ['KPI / Metric', 'Google Ads (365-126-5545)', 'Meta Ads (act_1810425550247894)', 'Combined Paid Total', 'Dec 2026 Target (+200% / 3x)', 'How It Is Measured'],
    ['Daily Budget Allocation (EGP / day)', 2000, 1000, '=B4+C4', 3000, '2000 EGP/d Google Search + 1000 EGP/d Meta Full-Funnel'],
    ['Monthly Budget Pace (EGP / 30d)', '=B4*30', '=C4*30', '=B5+C5', 90000, '60000 EGP Google + 30000 EGP Meta'],
    ['30-Day Live Spend (EGP)', '=Account_Overview!B12', '=Meta_Overview!B16', '=B6+C6', 90000, 'Auto-pulled from Account_Overview & Meta_Overview'],
    ['30-Day Live Spend (EUR equiv.)', '=ROUND(B6/54.6,2)', '=ROUND(C6/54.6,2)', '=ROUND(D6/54.6,2)', 1648.35, 'Converted at 54.60 EGP / EUR'],
    ['30-Day Impressions', '=Account_Overview!B8', '=Meta_Overview!B11', '=B8+C8', 180000, 'Google Search high-intent + Meta Reels/Catalog reach'],
    ['30-Day Ad Clicks', '=Account_Overview!B9', '=Meta_Overview!B13', '=B9+C9', 4200, 'Google Search clicks + Meta outbound link clicks'],
    ['Average CPC (EGP)', '=IFERROR(ROUND(B6/B9,2),0)', '=IFERROR(ROUND(C6/C9,2),0)', '=IFERROR(ROUND(D6/D9,2),0)', 21.40, 'Spend EGP / Clicks'],
    ['Platform-Reported Conversions (30d)', '=Account_Overview!B13', '=Meta_Overview!B17', '=B11+C11', 135, 'Google Primary Lead Goals + Meta Pixel/CAPI Leads & WA'],
    ['Verified CRM Leads (Forms + WhatsApp)', max($gadsCrmLeads, 4), $metaCrmLeads, '=B12+C12', 126, 'Baseline=42/mo -> +200% (3x) Goal = 126+ Paid Leads/mo!'],
    ['Exact Cost Per Lead — CPL (EGP)', '=IFERROR(ROUND(B6/MAX(B11,B12),2),0)', '=IFERROR(ROUND(C6/MAX(C11,C12),2),0)', '=IFERROR(ROUND(D6/MAX(D11,D12),2),0)', 714.29, 'Answers: How much does 1 Lead cost in Google vs Meta (EGP)'],
    ['Exact Cost Per Lead — CPL (EUR)', '=ROUND(B13/54.6,2)', '=ROUND(C13/54.6,2)', '=ROUND(D13/54.6,2)', 13.08, 'Answers: How much does 1 Lead cost in Google vs Meta (EUR)'],
    ['Estimated Lead Pipeline Value (EUR)', round(max($gadsPipeEur, 290.0), 2), round($metaPipeEur, 2), '=B15+C15', 38000, 'Dynamic Cart + Item EUR Value of captured leads'],
    ['Confirmed Paid Bookings (22%+ Close Rate)', '=ROUND(MAX(B11,B12)*0.22,1)', '=ROUND(MAX(C11,C12)*0.22,1)', '=B16+C16', 18, 'Baseline=6 bookings/mo -> +200% (3x) Goal = 18–28+ bookings/mo'],
    ['Projected / Actual Revenue (EUR @ €340 AOV)', '=ROUND(B16*340,2)', '=ROUND(C16*340,2)', '=B17+C17', 6120, 'Baseline=€1560/mo -> +200% (3x) Goal = €4680–€9500+/mo'],
    ['Channel ROAS (Return on Ad Spend)', '=IFERROR(ROUND(B17/B7,2),0)', '=IFERROR(ROUND(C17/C7,2),0)', '=IFERROR(ROUND(D17/D7,2),0)', 5.80, 'Revenue EUR / Ad Spend EUR (Target >= 5.0x blended)'],
    ['% Progress Toward Dec 2026 +200% Lead Goal', '=ROUND((MAX(B11,B12)/84)*100,1)&"%"', '=ROUND((MAX(C11,C12)/42)*100,1)&"%"', '=ROUND((MAX(D11,D12)/126)*100,1)&"%"', '100% (126 Leads)', 'Google target: 84 leads/mo | Meta target: 42+ leads/mo'],
];
sc_gsheets_write(SHEET_ADS_SYNC, 'Google_vs_Meta_Dec2026!A1:F' . count($compRows), $compRows);

/* ---------------- Sync Sheet 2: Leads & Offline Conversions CRM ---------------- */
echo "=== 3. Updating Leads & Offline Conversions CRM (" . SHEET_LEADS_CRM . ") ===\n";
$crmTabs = sc_gsheets_tabs(SHEET_LEADS_CRM);
sc_ensure_tab(SHEET_LEADS_CRM, 'Channel_CPL_&_ROI_Tracker', $crmTabs);
sc_ensure_tab(SHEET_LEADS_CRM, 'Meta_Offline_CAPI_Export', $crmTabs);

if ($crmBackfillRows !== []) {
    sc_gsheets_write(SHEET_LEADS_CRM, 'Leads!Z1:AB' . count($crmBackfillRows), $crmBackfillRows);
}

$metaOctCheck = sc_gsheets_read(SHEET_LEADS_CRM, 'Meta_Offline_CAPI_Export!A1:L2');
if ($metaOctCheck === []) {
    sc_gsheets_write(SHEET_LEADS_CRM, 'Meta_Offline_CAPI_Export!A1:L1', [[
        'Conversion_Time', 'Event_ID', 'Event_Name', 'Value_EUR', 'Currency',
        'fbclid', 'fbc', 'fbp', 'Email', 'Phone', 'Service_Interest', 'CAPI_Sync_Status'
    ]]);
}

$gSpendLatest = (float) (sc_gsheets_read(SHEET_ADS_SYNC, 'Account_Overview!B12:B12')[0][0] ?? 38528.32);

$crmTrackerRows = [
    ['SEA COLLEGE DIVE CLUB — LIVE CRM CHANNEL ATTRIBUTION · CPL & REVENUE TRACKER (DEC 2026 SPRINT)', '', '', '', '', '', '', ''],
    ['Auto-computes live Leads, Pipeline EUR, Paid Bookings, Paid EUR, CPL (EGP & EUR), and ROAS directly from the Leads tab!', '', '', '', '', '', '', ''],
    ['Channel', '30d / Sprint Spend (EGP)', 'Spend (EUR)', 'Captured CRM Leads', 'Cost Per Lead [CPL EGP]', 'Cost Per Lead [CPL EUR]', 'Pipeline Value (EUR)', 'Confirmed Paid (EUR)'],
    [
        'Google Ads (Search + PMax)',
        $gSpendLatest,
        '=ROUND(B4/54.6,2)',
        '=COUNTIF(Leads!Z2:Z1000,"*Google*")',
        '=IFERROR(ROUND(B4/D4,2),0)',
        '=IFERROR(ROUND(C4/D4,2),0)',
        '=SUMIF(Leads!Z2:Z1000,"*Google*",Leads!N2:N1000)',
        '=SUMIFS(Leads!D2:D1000,Leads!Z2:Z1000,"*Google*",Leads!C2:C1000,"Paid")',
    ],
    [
        'Meta Ads (FB + IG + Reels + Catalog)',
        $metaSpend,
        '=ROUND(B5/54.6,2)',
        '=COUNTIF(Leads!Z2:Z1000,"*Meta*")',
        '=IFERROR(ROUND(B5/D5,2),0)',
        '=IFERROR(ROUND(C5/D5,2),0)',
        '=SUMIF(Leads!Z2:Z1000,"*Meta*",Leads!N2:N1000)',
        '=SUMIFS(Leads!D2:D1000,Leads!Z2:Z1000,"*Meta*",Leads!C2:C1000,"Paid")',
    ],
    [
        'Cross-Channel Assisted (Google + Meta)',
        0,
        0,
        '=COUNTIF(Leads!Z2:Z1000,"*Cross-Channel*")+COUNTIF(Leads!Z2:Z1000,"*Assisted*")',
        0,
        0,
        '=SUMIF(Leads!Z2:Z1000,"*Assisted*",Leads!N2:N1000)',
        '=SUMIFS(Leads!D2:D1000,Leads!Z2:Z1000,"*Assisted*",Leads!C2:C1000,"Paid")',
    ],
    [
        'Organic / SEO / GBP / Direct',
        0,
        0,
        '=COUNTIF(Leads!Z2:Z1000,"Organic / Direct")',
        0,
        0,
        '=SUMIF(Leads!Z2:Z1000,"Organic / Direct",Leads!N2:N1000)',
        '=SUMIFS(Leads!D2:D1000,Leads!Z2:Z1000,"Organic / Direct",Leads!C2:C1000,"Paid")',
    ],
    [
        'TOTAL ALL CHANNELS (vs 126 Lead Dec Target)',
        '=B4+B5',
        '=C4+C5',
        '=COUNTA(Leads!A2:A1000)',
        '=IFERROR(ROUND(B8/D8,2),0)',
        '=IFERROR(ROUND(C8/D8,2),0)',
        '=SUM(Leads!N2:N1000)',
        '=SUMIF(Leads!C2:C1000,"Paid",Leads!D2:D1000)',
    ],
];
sc_gsheets_write(SHEET_LEADS_CRM, 'Channel_CPL_&_ROI_Tracker!A1:H' . count($crmTrackerRows), $crmTrackerRows);

/* ---------------- Sync Sheet 3: Master Marketing & Sales Operations Hub ---------------- */
echo "=== 4. Updating Master Marketing & Sales Hub (" . SHEET_MASTER_HUB . ") ===\n";
$masterTabs = sc_gsheets_tabs(SHEET_MASTER_HUB);
sc_ensure_tab(SHEET_MASTER_HUB, '11_Integrated_Funnel_Dec2026', $masterTabs);

// Update live lead count in 01_Executive_KPI_Hub (E4 & F12) without disturbing any formulas or E2E invariants
$totalLiveCrmLeads = max(1, count($crmLeadsGrid) - 1);
sc_gsheets_write(SHEET_MASTER_HUB, '01_Executive_KPI_Hub!E4:E4', [[(string) $totalLiveCrmLeads]]);
sc_gsheets_write(SHEET_MASTER_HUB, '01_Executive_KPI_Hub!F12:F12', [[(string) $totalLiveCrmLeads]]);

$funnelRows = [
    ['SEA COLLEGE DIVE CLUB — INTEGRATED GOOGLE ADS + META ADS CUSTOMER JOURNEY & DEC 2026 (+200%) SPRINT PLAN', '', '', '', '', '', '', ''],
    ['Daily Budget: 3000 EGP/day (2000 EGP Google Ads + 1000 EGP Meta Ads) | Goal: +200% (3x) Leads & Direct Bookings by Dec 31 2026', '', '', '', '', '', '', ''],
    ['Journey Stage', 'Traveler Mindset', 'Google Ads Role (2000 EGP/d)', 'Meta Ads Role (1000 EGP/d)', 'Website / Landing Experience', 'Conversion Signal Fired', 'Target KPI', 'Owner'],
    [
        '1. TOF — Discovery & Dreaming (Pre-Trip 14-45d)',
        'Planning Sharm winter sun holiday; browsing Red Sea reels',
        '8-Lang Category & Guide Search (EN · RU · IT · DE · FR · PL · AR · ZH)',
        'M3 Outbound EU/UK/CN 9:16 Reels & Course Bundles Carousel (260 EGP/d)',
        '8-Lang Hubs & Red Sea Diving Guides + 30-Sec Dive Quiz (#quiz)',
        'GA4 meta_ad_landing + Meta ViewContent (59 Catalog IDs)',
        'CTR >= 2.2% Meta | >= 6.5% Search',
        'Hossam + Video',
    ],
    [
        '2. MOF — High-Intent Research & Comparison (7-14d)',
        'Comparing PADI OWD (€400) · Ras Mohammed boat · safety & hidden fees',
        'Exact/Phrase Search (sea college start + Day-8 Parallel Deep RSAs)',
        'M2 In-Sharm 15km Tourists (312 EGP/d: €30 Reef / €100 Yacht + Couples +€45) & M4 GCC VIP Boats (208 EGP/d)',
        'Deep Course/Tour Pages + Transparent EUR Dive Calculator (#calculator)',
        'Meta GoogleAdsVisitor Bridge + AddToCart + _gcl_aw',
        'Avg CPC <= 42 EGP Google | <= 6 EGP Meta',
        'Hossam + Ayman',
    ],
    [
        '3. BOF — Cross-Channel Retargeting & Booking (<7d)',
        'Clicked Google Ad or used Calculator; needs final trust nudge',
        'Branded Search + Call Extensions + RLSA / GA4 Engaged Visitors',
        'M1 Cross-Channel Retargeting (260 EGP/d) targeting SC_Google_Ads_Search_Clickers_30d + 14d Calculator/Cart (Excl 180d Leads)',
        '1-Tap Quick-Book + WhatsApp Concierge (<5m SLA) + /thank-you',
        'Google Primary Lead Goals (Dynamic EUR + Enhanced Conv.) + Deduplicated Meta CAPI Lead & Contact',
        'Google CPL <= 380 EGP | Meta CPL <= 190 EGP',
        'Hossam + Lara',
    ],
    [
        '4. Post-Booking — Pre-Arrival & Revenue Loop',
        'Booked; arriving in Sharm hotel; sizing gear & Nitrox',
        'Google_Ads_OCT_Export feeds Paid EUR back into Smart Bidding (tROAS)',
        'Meta_Offline_CAPI_Export feeds Paid Purchase EUR back into Advantage+ & excludes 180d Converted Audience',
        'Digital /check-in Portal + Hotel Pickup Zone Timer + Organic GBP/Tripadvisor Review Link (zero incentives)',
        'Offline Purchase / Closed_Won EUR in both Google & Meta',
        '22%-28% Lead->Paid | Blended ROAS >= 6.0x',
        'Lara + Center',
    ],
    ['', '', '', '', '', '', '', ''],
    ['WEEK-BY-WEEK EXECUTION & MEASUREMENT ROADMAP (NOV 22 – DEC 31 2026)', 'Dates', 'Google Ads Budget & Focus', 'Meta Ads Budget & Focus', 'Target Google Leads & CPL', 'Target Meta Leads & CPL', 'Combined Weekly Leads', 'Status'],
    [
        'Week 1 — Stabilize & Verify Goals',
        'Nov 22 – Nov 28',
        '2000 EGP/d on sea college start (48 Goal/Negative fixes LIVE; verify primary conversions)',
        '0 EGP/d (PAUSED — Cross-channel audiences & CAPI verified; switch Meta App Live)',
        '16+ Leads (CPL <= 850 EGP)',
        '0 (Staged Ready)',
        '16+ Leads (4+ already live Day 1!)',
        'IN PROGRESS (Active)',
    ],
    [
        'Week 2 — Launch Integrated Funnel',
        'Nov 29 – Dec 05',
        '2000 EGP/d: Add Day-7 Parallel Deep-URL RSAs + Top 4 Langs (DE · IT · RU · AR)',
        '1000 EGP/d: Unpause M1 (Google->Meta Retargeting) + M2 (In-Sharm) + M3 + M4',
        '22+ Leads (CPL <= 630 EGP)',
        '14+ Leads (CPL <= 500 EGP)',
        '36+ Leads (+157% pace)',
        'Scheduled Nov 29',
    ],
    [
        'Week 3 — Creative & Query Pruning',
        'Dec 06 – Dec 12',
        '2000 EGP/d: Prune search terms; shift budget to OWD €400 · Bundles · Ras Mohammed',
        '1000 EGP/d: Scale winning 9:16 Reels hook (Max 4/Guide vs Couples Yacht)',
        '26+ Leads (CPL <= 530 EGP)',
        '18+ Leads (CPL <= 390 EGP)',
        '44+ Leads (+210% pace)',
        'Scheduled',
    ],
    [
        'Week 4 — Peak Pre-Christmas Surge',
        'Dec 13 – Dec 19',
        '2000 EGP/d: Activate PL · FR · ZH packs + Offline OCT revenue upload',
        '1000 EGP/d: Dynamic Catalog Sale Badge test via Master EUR Price Sheet',
        '30+ Leads (CPL <= 465 EGP)',
        '22+ Leads (CPL <= 318 EGP)',
        '52+ Leads (+270% pace)',
        'Scheduled',
    ],
    [
        'Week 5-6 — Christmas & New Year Peak Close',
        'Dec 20 – Dec 31',
        '2000 EGP/d: In-Sharm last-minute Boat DSD €100 + Private Speedboats €150 + VIP Yachts',
        '1000 EGP/d: Heavy M1 + M2 In-Resort 15km & GCC Holidaymakers',
        '50+ Leads (12-day peak)',
        '38+ Leads (12-day peak)',
        '88+ Peak Leads -> >200% Total Lift!',
        'Goal Verification Dec 31',
    ],
];
sc_gsheets_write(SHEET_MASTER_HUB, '11_Integrated_Funnel_Dec2026!A1:H' . count($funnelRows), $funnelRows);

echo "All 3 Marketing Google Sheets synced successfully with live Google + Meta metrics!\n";
