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

/* 4. Meta Pixel & Server-Side Conversions API (CAPI) */
if (META_PIXEL_ID !== '' && META_CAPI_TOKEN !== '') {
    $line('OK', 'Meta Pixel + CAPI', 'Pixel ' . META_PIXEL_ID . ' + CAPI token configured (' . META_GRAPH_VERSION . ')');
} elseif (META_PIXEL_ID !== '') {
    $line('WARN', 'Meta Pixel + CAPI', 'Browser Pixel ' . META_PIXEL_ID . ' active; META_CAPI_TOKEN empty (set in config/secrets.php for server-side CAPI)');
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

echo "\nTip: pass --ping-network to test live TLS/HTTP reachability without writing dummy rows.\n";
