<?php
/**
 * Sea College Dive Club — Live Integrations Diagnostic & Health Verifier (CLI-only).
 *
 * Usage:
 *   php docs/verify_integrations.php
 *   php docs/verify_integrations.php --ping-network
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: CLI only.');
}

require_once dirname(__DIR__) . '/config/site_config.php';

$pingNetwork = in_array('--ping-network', $argv ?? [], true);
$secretsLoaded = is_file(dirname(__DIR__) . '/config/secrets.php');

$line = static function (string $status, string $component, string $detail): void {
    $badge = match ($status) {
        'OK'   => '[  OK  ]',
        'WARN' => '[ WARN ]',
        default=> '[ FAIL ]',
    };
    echo sprintf("%s %-28s %s\n", $badge, $component, $detail);
};

echo "=== Sea College Dive Club — Integrations Health Check ===\n";
echo "Environment : " . SITE_ENV . " (" . SITE_URL . ")\n";
echo "secrets.php : " . ($secretsLoaded ? "Loaded (config/secrets.php found)" : "Not present (using env / defaults; see config/secrets.example.php)") . "\n\n";

/* 1. Storage & CSRF Secret */
$storageDir = LEADS_STORAGE_DIR;
if (is_dir($storageDir) && is_writable($storageDir)) {
    $line('OK', 'Storage Directory', $storageDir . ' is writable');
} else {
    $line('FAIL', 'Storage Directory', $storageDir . ' is missing or not writable');
}

/* 2. Google Tag Manager, GA4 & Google Ads */
if (GTM_ID !== '' && GA4_ID !== '' && GOOGLE_ADS_ID !== '') {
    $line('OK', 'Google Tag / GA4 / Ads', sprintf('%s | %s | %s (WA label: %s)', GTM_ID, GA4_ID, GOOGLE_ADS_ID, GOOGLE_ADS_WHATSAPP_LABEL !== '' ? 'set' : 'missing'));
} else {
    $line('WARN', 'Google Tag / GA4 / Ads', 'One or more Google tracking IDs are empty');
}

/* 2b. Google Tag Gateway (First-Party Mode -> *.fps.goog) */
$gtgProxyFile = dirname(__DIR__) . '/handlers/gtg_proxy.php';
if (defined('GTG_ENABLED') && GTG_ENABLED && is_file($gtgProxyFile)) {
    $gtgDetail = sprintf('Active (%s -> %s.fps.goog, %s -> %s.fps.goog)', GTG_GTM_PATH, strtolower(GTM_ID), GTG_ADS_PATH, strtolower(GOOGLE_ADS_ID));
    if ($pingNetwork && function_exists('curl_init')) {
        $ch1 = curl_init(SITE_URL . GTG_ADS_PATH . '/healthy');
        curl_setopt_array($ch1, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
        $r1 = trim((string) curl_exec($ch1));
        curl_close($ch1);
        $ch2 = curl_init(SITE_URL . GTG_ADS_PATH . '/?validate_geo=healthy');
        curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
        $r2 = trim((string) curl_exec($ch2));
        curl_close($ch2);
        $ok = ($r1 === 'ok' && $r2 === 'ok');
        $line($ok ? 'OK' : 'WARN', 'Google Tag Gateway (Live)', $gtgDetail . " [healthy={$r1}, geo={$r2}]");
    } else {
        $line('OK', 'Google Tag Gateway', $gtgDetail);
    }
} else {
    $line('WARN', 'Google Tag Gateway', 'Disabled or handlers/gtg_proxy.php missing');
}

/* 3. Google Business Profile Places API */
$seedFile = dirname(__DIR__) . '/storage/google_reviews_seed.json';
if (GOOGLE_PLACES_API_KEY !== '') {
    $detail = 'API key configured for Place ID ' . GOOGLE_PLACE_ID;
    if ($pingNetwork && function_exists('curl_init')) {
        $ch = curl_init('https://places.googleapis.com/v1/places/' . rawurlencode(GOOGLE_PLACE_ID) . '?languageCode=en');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'X-Goog-Api-Key: ' . GOOGLE_PLACES_API_KEY,
                'X-Goog-FieldMask: rating,userRatingCount',
            ],
        ]);
        $res = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $detail .= " (HTTP {$code})";
        $line($code === 200 ? 'OK' : 'WARN', 'GBP Places API (Live)', $detail);
    } else {
        $line('OK', 'GBP Places API', $detail);
    }
} else {
    $line('WARN', 'GBP Places API', 'GOOGLE_PLACES_API_KEY empty — serving verified seed (' . BUSINESS['rating'] . '★ / ' . BUSINESS['review_count'] . ' reviews' . (is_file($seedFile) ? ', seed JSON present' : '') . ')');
}

/* 4. Meta Pixel, Domain Verification & Server-Side Conversions API (CAPI) */
$fbDomNote = (defined('FB_DOMAIN_VERIFICATION') && FB_DOMAIN_VERIFICATION !== '') ? '; FB domain meta: ' . FB_DOMAIN_VERIFICATION : '';
if (META_PIXEL_ID !== '' && META_CAPI_TOKEN !== '') {
    $line('OK', 'Meta Pixel + CAPI', 'Pixel ' . META_PIXEL_ID . ' + CAPI token configured (' . META_GRAPH_VERSION . ')' . $fbDomNote);
} elseif (META_PIXEL_ID !== '') {
    $line('WARN', 'Meta Pixel + CAPI', 'Browser Pixel ' . META_PIXEL_ID . ' active' . $fbDomNote . '; META_CAPI_TOKEN empty (set in config/secrets.php for CAPI)');
} else {
    $line('WARN', 'Meta Pixel + CAPI', 'Disabled');
}

/* 5. SMTP Email Delivery */
if (SMTP_USER !== '' && SMTP_PASS !== '') {
    $detail = SMTP_HOST . ':' . SMTP_PORT . ' as ' . SMTP_USER . ' -> ' . LEAD_NOTIFY_EMAIL;
    if ($pingNetwork) {
        $fp = @stream_socket_client('ssl://' . SMTP_HOST . ':' . SMTP_PORT, $errno, $errstr, 5);
        if ($fp) {
            fclose($fp);
            $line('OK', 'SMTPS Mailer (Live TLS)', $detail . ' (socket reachable)');
        } else {
            $line('FAIL', 'SMTPS Mailer (Live TLS)', $detail . " (connect failed: {$errstr})");
        }
    } else {
        $line('OK', 'SMTPS Mailer', $detail);
    }
} else {
    $line('WARN', 'SMTPS Mailer', 'SMTP_USER/SMTP_PASS empty — falling back to PHP mail() -> ' . LEAD_NOTIFY_EMAIL);
}

/* 6. Google Sheets Leads & OCT CRM Webhook */
if (CRM_WEBHOOK_URL !== '' && str_starts_with(CRM_WEBHOOK_URL, 'https://script.google.com/')) {
    $detail = substr(CRM_WEBHOOK_URL, 0, 68) . '…';
    if ($pingNetwork && function_exists('curl_init')) {
        $ch = curl_init(CRM_WEBHOOK_URL);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $line(($code >= 200 && $code < 500) ? 'OK' : 'WARN', 'CRM Apps Script Webhook', $detail . " (HTTP {$code})");
    } else {
        $line('OK', 'CRM Apps Script Webhook', $detail);
    }
} elseif (CRM_WEBHOOK_URL !== '') {
    $line('OK', 'CRM Webhook URL', CRM_WEBHOOK_URL);
} else {
    $line('WARN', 'CRM Webhook URL', 'CRM_WEBHOOK_URL empty — leads stored locally in storage/*.csv only');
}

/* 7. Microsoft/Bing UET & TikTok Pixel Tag Readiness */
$uetStatus = (defined('MICROSOFT_UET_ID') && MICROSOFT_UET_ID !== '') ? ('UET ID: ' . MICROSOFT_UET_ID) : 'UET hook ready (set MICROSOFT_UET_ID when tag created)';
$ttStatus  = (defined('TIKTOK_PIXEL_ID') && TIKTOK_PIXEL_ID !== '') ? ('TikTok ID: ' . TIKTOK_PIXEL_ID) : 'TikTok hook ready (set TIKTOK_PIXEL_ID when pixel created)';
$line('OK', 'Microsoft UET & TikTok', $uetStatus . ' | ' . $ttStatus . ' (CSP allowlisted)');

/* 8. Dynamic Ad Feeds & AI/AEO Discovery (/llms.txt) */
$feedFiles = [
    'feeds/meta_catalog.php',
    'feeds/google_dsa_feed.php',
    'feeds/google_ad_customizer.php',
    'llms.php',
];
$missingFeeds = array_filter($feedFiles, static fn(string $rel): bool => !is_file(dirname(__DIR__) . '/' . $rel));
if ($missingFeeds === []) {
    if ($pingNetwork && function_exists('curl_init')) {
        $probePaths = [
            '/feeds/meta-catalog.xml',
            '/feeds/google-dsa-page-feed.csv',
            '/feeds/google-ad-customizer.csv',
            '/llms.txt',
        ];
        $codes = [];
        $all200 = true;
        foreach ($probePaths as $pp) {
            $ch = curl_init(SITE_URL . $pp);
            curl_setopt_array($ch, [
                CURLOPT_NOBODY         => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 6,
                CURLOPT_USERAGENT      => 'SeaCollegeVerify/1.0',
            ]);
            curl_exec($ch);
            $c = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $codes[] = basename($pp) . '=' . $c;
            if ($c !== 200) {
                $all200 = false;
            }
        }
        $line($all200 ? 'OK' : 'WARN', 'Dynamic Feeds & /llms.txt', implode(', ', $codes));
    } else {
        $line('OK', 'Dynamic Feeds & /llms.txt', 'Meta XML + Google DSA CSV + Google Customizer CSV + /llms.txt present');
    }
} else {
    $line('FAIL', 'Dynamic Feeds & /llms.txt', 'Missing: ' . implode(', ', $missingFeeds));
}

/* 9. Offline Conversion Sync CLIs (Meta CAPI Purchase & Google Ads OCT) */
$metaOfflineCli = dirname(__DIR__) . '/docs/meta_offline_conversions_sync.php';
$googleOctCli   = dirname(__DIR__) . '/docs/export_google_oct_csv.php';
if (is_file($metaOfflineCli) && is_file($googleOctCli)) {
    $line('OK', 'Offline Conversion CLIs', 'meta_offline_conversions_sync.php & export_google_oct_csv.php ready');
} else {
    $line('FAIL', 'Offline Conversion CLIs', 'Missing offline sync CLI script(s)');
}

echo "\nTip: pass --ping-network to test live TLS/HTTP reachability without writing dummy rows.\n";
