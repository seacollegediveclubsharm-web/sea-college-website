<?php
/**
 * Google Ads Offline Conversions & Enhanced Conversions for Leads CSV Exporter (CLI Only).
 *
 * Exports closed/qualified leads from `storage/leads.csv` & `storage/wa_clicks.csv`
 * into Google Ads Data Manager's exact template format:
 *   Parameters:TimeZone=+0000
 *   Google Click ID,Conversion Name,Conversion Time,Conversion Value,Conversion Currency,Email,Phone Number
 *
 * Usage:
 *   php docs/google_offline_conversions_export.php > /tmp/google_ads_offline_conversions.csv
 *   php docs/google_offline_conversions_export.php --conversion-name="WhatsApp Lead"
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Forbidden\n");
}

define('SC_NO_SESSION', true);
require_once dirname(__DIR__) . '/config/site_config.php';

$convNameOverride = '';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--conversion-name=')) {
        $convNameOverride = trim(substr($arg, strlen('--conversion-name=')));
    }
}

function sc_gads_norm_email(string $em): string
{
    $em = strtolower(trim($em));
    if ($em === '' || !str_contains($em, '@')) {
        return '';
    }
    [$local, $domain] = explode('@', $em, 2);
    if ($domain === 'gmail.com' || $domain === 'googlemail.com') {
        $local = str_replace('.', '', explode('+', $local)[0]);
        $domain = 'gmail.com';
    }
    return hash('sha256', $local . '@' . $domain);
}

function sc_gads_norm_phone(string $ph): string
{
    $digits = preg_replace('/\D+/', '', $ph) ?? '';
    $digits = preg_replace('/^00/', '', $digits) ?? '';
    if (preg_match('/^0(1[0125]\d{8})$/', $digits, $m)) {
        $digits = '20' . $m[1];
    } elseif (str_starts_with($digits, '0') && strlen($digits) >= 9) {
        $digits = ltrim($digits, '0');
    }
    return strlen($digits) >= 8 ? hash('sha256', '+' . $digits) : '';
}

function sc_read_csv_assoc(string $path): array
{
    if (!is_file($path)) {
        return [];
    }
    $fp = @fopen($path, 'rb');
    if (!$fp) {
        return [];
    }
    $headers = fgetcsv($fp, 0, ',', '"', '');
    if (!is_array($headers)) {
        fclose($fp);
        return [];
    }
    $rows = [];
    while (($line = fgetcsv($fp, 0, ',', '"', '')) !== false) {
        $row = [];
        foreach ($headers as $idx => $h) {
            $row[trim((string) $h)] = isset($line[$idx]) ? trim((string) $line[$idx]) : '';
        }
        $rows[] = $row;
    }
    fclose($fp);
    return $rows;
}

$out = fopen('php://stdout', 'wb');
fwrite($out, "Parameters:TimeZone=+0000\n");
fputcsv($out, [
    'Google Click ID',
    'Conversion Name',
    'Conversion Time',
    'Conversion Value',
    'Conversion Currency',
    'Email',
    'Phone Number',
], ',', '"', '');

$seen = [];
$storageDir = defined('LEADS_STORAGE_DIR') ? LEADS_STORAGE_DIR : (dirname(__DIR__) . '/storage');

// 1) Form leads (storage/leads.csv)
foreach (sc_read_csv_assoc($storageDir . '/leads.csv') as $r) {
    $gclid = $r['gclid'] ?? '';
    $emSha = sc_gads_norm_email($r['email'] ?? '');
    $phSha = sc_gads_norm_phone($r['phone'] ?? '');
    if ($gclid === '' && $emSha === '' && $phSha === '') {
        continue;
    }
    $ts = strtotime((string) ($r['created_at'] ?? $r['timestamp'] ?? ''));
    if ($ts === false) {
        continue;
    }
    $dedupe = ($gclid ?: $emSha ?: $phSha) . '|' . gmdate('Y-m-d H', $ts);
    if (isset($seen[$dedupe])) {
        continue;
    }
    $seen[$dedupe] = true;
    $val = (float) ($r['total_eur'] ?? $r['estimated_eur'] ?? 0);
    if ($val <= 0) {
        $val = 120.0;
    }
    fputcsv($out, [
        $gclid,
        $convNameOverride !== '' ? $convNameOverride : 'Reservation Form Submitted',
        gmdate('Y-m-d H:i:s', $ts),
        number_format($val, 2, '.', ''),
        'EUR',
        $emSha,
        $phSha,
    ], ',', '"', '');
}

// 2) Attributed gclid WhatsApp / Phone clicks (storage/wa_clicks.csv)
foreach (sc_read_csv_assoc($storageDir . '/wa_clicks.csv') as $r) {
    $gclid = $r['gclid'] ?? '';
    if ($gclid === '') {
        continue;
    }
    $ts = strtotime((string) ($r['created_at'] ?? $r['timestamp'] ?? ''));
    if ($ts === false) {
        continue;
    }
    $kind = strtolower((string) ($r['kind'] ?? 'whatsapp_click'));
    $cName = $convNameOverride !== ''
        ? $convNameOverride
        : ($kind === 'phone_click' ? 'Click to call' : 'WhatsApp Lead');
    $dedupe = $gclid . '|' . $cName . '|' . gmdate('Y-m-d', $ts);
    if (isset($seen[$dedupe])) {
        continue;
    }
    $seen[$dedupe] = true;
    $val = (float) ($r['estimated_eur'] ?? $r['value'] ?? 0);
    if ($val <= 0) {
        $val = 45.0;
    }
    fputcsv($out, [
        $gclid,
        $cName,
        gmdate('Y-m-d H:i:s', $ts),
        number_format($val, 2, '.', ''),
        'EUR',
        '',
        '',
    ], ',', '"', '');
}

fclose($out);
