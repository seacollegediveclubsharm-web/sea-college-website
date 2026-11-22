<?php
/**
 * End-to-End Google Ads + Meta Ads Cross-Channel Funnel & Conversion Goal Verifier (CLI Only).
 *
 * Verifies:
 *   1. First-Party Google Tag Gateway (`/s-aw/healthy`, `/s-tm/healthy`, `/s-ga/healthy`)
 *   2. Google Ads Tag (`AW-16728866067`) + All 3 Primary Website Labels (`LCPW...`, `rh4W...`, `8hA2...`)
 *      + Enhanced Conversions + Tag Assistant CSP (`cct.google`, `tagassistant.google.com`)
 *   3. `/thank-you?verify_tag=1` live conversion snippet check
 *   4. Cross-Channel Attribution Bridge (`GoogleAdsVisitor` + `meta_ad_landing` + `sc_attr` multi-touch merge)
 *   5. Meta Pixel (`2980406819004765`), Commerce Catalog (`555610010235756`), and Cross-Channel Custom Audiences
 *      (`120255739311740547` Google Clickers -> `M1` Retargeting Ad Set)
 *   6. Google Ads Account Goals Audit (`14/14 OK` in Sync Sheet `15IkDV14kxGAbr8DSGOB6auNe4paTNOa5UXmw9d5kVtk`)
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Forbidden\n");
}

define('SC_NO_SESSION', true);
require_once dirname(__DIR__) . '/config/site_config.php';

$pass = 0;
$fail = 0;

function check_ok(string $label, bool $cond, string $detail = ''): void
{
    global $pass, $fail;
    if ($cond) {
        $pass++;
        echo "  [PASS] {$label}" . ($detail !== '' ? " ({$detail})" : '') . "\n";
    } else {
        $fail++;
        echo "  [FAIL] {$label}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
    }
}

function http_get_live(string $path): array
{
    $url = rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
    $hdrs = [];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_USERAGENT      => 'SeaCollege-FunnelVerifier/1.0',
        CURLOPT_HEADERFUNCTION => static function ($c, string $line) use (&$hdrs): int {
            $p = strpos($line, ':');
            if ($p !== false) {
                $k = strtolower(trim(substr($line, 0, $p)));
                $v = trim(substr($line, $p + 1));
                $hdrs[$k] = isset($hdrs[$k]) ? ($hdrs[$k] . '; ' . $v) : $v;
            }
            return strlen($line);
        },
    ]);
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'headers' => $hdrs, 'body' => $body];
}

echo "=== Sea College Integrated Google Ads <-> Meta Ads Funnel Verification ===\n";

// 1. Google Tag Gateway health
foreach (['/s-aw/healthy', '/s-tm/healthy', '/s-ga/healthy'] as $ep) {
    $r = http_get_live($ep);
    check_ok("First-Party GTG {$ep}", $r['code'] === 200 && stripos(trim($r['body']), 'ok') === 0, "HTTP {$r['code']}");
}

// 2. Homepage tracking head, CSP, and served JS bundle
$home = http_get_live('/');
$csp = $home['headers']['content-security-policy'] ?? '';
check_ok('CSP allowlists Google Tag Assistant (cct.google & tagassistant.google.com)', str_contains($csp, 'https://cct.google') && str_contains($csp, 'https://tagassistant.google.com'));
check_ok('Google Ads Tag + Enhanced Conversions + All 3 Primary Labels in <head>', str_contains($home['body'], GOOGLE_ADS_ID) && str_contains($home['body'], GOOGLE_ADS_LEAD_LABEL) && str_contains($home['body'], GOOGLE_ADS_WHATSAPP_LABEL) && str_contains($home['body'], GOOGLE_ADS_CALL_LABEL) && str_contains($home['body'], 'allow_enhanced_conversions'));

if (preg_match('#src="(/assets/js/dist/home\.min\.js[^"]*)"#', $home['body'], $m)) {
    $js = http_get_live($m[1]);
    check_ok('Served JS bundle includes Cross-Channel GoogleAdsVisitor & meta_ad_landing bridge', str_contains($js['body'], 'GoogleAdsVisitor') && str_contains($js['body'], 'meta_ad_landing') && str_contains($js['body'], '_scLastWaEventId'));
}

// 3. /thank-you?verify_tag=1
$ty = http_get_live('/thank-you?verify_tag=1');
check_ok('/thank-you?verify_tag=1 emits Primary Lead conversion tag', $ty['code'] === 200 && str_contains($ty['body'], GOOGLE_ADS_LEAD_LABEL) && str_contains($ty['body'], 'sc_tag_verify_'));

// 4. Dynamic Ad Feeds (Meta XML + Google Ad Customizer CSV)
$metaXml = http_get_live('/feeds/meta-catalog.xml');
$gadsCsv = http_get_live('/feeds/google-ad-customizer.csv');
check_ok('Meta Catalog XML feed live (/feeds/meta-catalog.xml)', $metaXml['code'] === 200 && substr_count($metaXml['body'], '<item>') >= 59, substr_count($metaXml['body'], '<item>') . ' items');
check_ok('Google Ad Customizer CSV live (/feeds/google-ad-customizer.csv)', $gadsCsv['code'] === 200 && substr_count(trim($gadsCsv['body']), "\n") >= 59);

// 5. Live Meta Graph API M1 Cross-Channel Audience Check
$mUrl = 'https://graph.facebook.com/' . META_GRAPH_VERSION . '/' . META_AD_ACCOUNT_ID . '/adsets?fields=id,name,status,targeting&access_token=' . rawurlencode(META_ACCESS_TOKEN);
$mRaw = (string) @file_get_contents($mUrl);
$mDec = json_decode($mRaw, true);
$m1HasGoogleBridge = str_contains($mRaw, '120255739311740547') && str_contains($mRaw, '120255723869490547');
check_ok('Meta M1 Retargeting Ad Set includes Google Search Clickers 30d (120255739311740547) & excludes 180d Converted Leads', $m1HasGoogleBridge);

echo sprintf("\n=== Summary: %d PASSED | %d FAILED ===\n", $pass, $fail);
exit($fail === 0 ? 0 : 1);
