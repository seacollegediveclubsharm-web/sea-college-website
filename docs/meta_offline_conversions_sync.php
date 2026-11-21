#!/usr/bin/env php
<?php
/**
 * Sea College Dive Club — Offline Meta Conversions API (`Purchase`) Sync CLI.
 *
 * Reads `Status=Paid` rows (with `Final_Paid_EUR > 0`) from the Website Leads & OCT CRM Sheet
 * (`1ItvQGWnrHwnSbyGDoGNtV2CkVwNJXIyU_aaIFxIOGbc`, `Leads` tab) or `--csv=<path>`, builds
 * Meta Events API v21.0 `Purchase` payloads for Dataset `2980406819004765`, and either:
 *   - `--dry-run` (DEFAULT): validates and reports payloads with zero live network mutations.
 *   - `--execute`: uploads new `order_<Event_ID>` events to Meta Graph API and records synced IDs
 *                  in git-ignored `storage/meta_offline_synced.json` for idempotent deduplication.
 *
 * Usage:
 *   php docs/meta_offline_conversions_sync.php --dry-run [--json]
 *   php docs/meta_offline_conversions_sync.php --dry-run --csv=/path/to/leads.csv [--json]
 *   php docs/meta_offline_conversions_sync.php --execute [--test-code=TEST1234] [--force]
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: CLI only.');
}

require_once dirname(__DIR__) . '/config/site_config.php';

/**
 * Parse CLI flags supporting both `--key=value` and `--key value`.
 * @return array<string, mixed>
 */
function sc_parse_cli_args(array $argv): array
{
    $opts = [
        'execute'       => false,
        'dry_run'       => true,
        'csv'           => '',
        'sheet_id'      => '1ItvQGWnrHwnSbyGDoGNtV2CkVwNJXIyU_aaIFxIOGbc',
        'tab'           => 'Leads',
        'action_source' => 'physical_store',
        'test_code'     => META_CAPI_TEST_CODE,
        'force'         => false,
        'json'          => false,
        'help'          => false,
    ];
    $count = count($argv);
    for ($i = 1; $i < $count; $i++) {
        $arg = (string) $argv[$i];
        if ($arg === '--execute') {
            $opts['execute'] = true;
            $opts['dry_run'] = false;
        } elseif ($arg === '--dry-run') {
            $opts['dry_run'] = true;
            $opts['execute'] = false;
        } elseif ($arg === '--force') {
            $opts['force'] = true;
        } elseif ($arg === '--json') {
            $opts['json'] = true;
        } elseif ($arg === '--help' || $arg === '-h') {
            $opts['help'] = true;
        } elseif (str_starts_with($arg, '--')) {
            $eq = strpos($arg, '=');
            if ($eq !== false) {
                $k = str_replace('-', '_', substr($arg, 2, $eq - 2));
                $v = substr($arg, $eq + 1);
            } else {
                $k = str_replace('-', '_', substr($arg, 2));
                $v = (($i + 1) < $count && !str_starts_with((string) $argv[$i + 1], '--'))
                    ? (string) $argv[++$i]
                    : '1';
            }
            if (array_key_exists($k, $opts)) {
                $opts[$k] = $v;
            }
        }
    }
    return $opts;
}

$opts = sc_parse_cli_args($argv ?? []);
if (!empty($opts['help'])) {
    echo "Usage: php docs/meta_offline_conversions_sync.php [--dry-run|--execute] [--csv=FILE] [--sheet-id=ID] [--tab=Leads] [--action-source=physical_store|chat|other|website] [--test-code=CODE] [--force] [--json]\n";
    exit(0);
}

$allowedSources = ['physical_store', 'chat', 'other', 'website'];
$actionSource = strtolower(trim((string) $opts['action_source']));
if (!in_array($actionSource, $allowedSources, true)) {
    fwrite(STDERR, "Invalid --action-source={$actionSource}. Allowed: " . implode(', ', $allowedSources) . "\n");
    exit(1);
}

/**
 * Build standalone catalog ID allowlist (exact 59 published catalog products; excludes eq-*, fee-*, ins-*, dive-extra).
 * @return array<string, true>
 */
function sc_load_standalone_catalog_ids(): array
{
    $rawCatalog = require dirname(__DIR__) . '/data/catalog.php';
    $valid = [];
    foreach ($rawCatalog as $cat) {
        foreach (($cat['groups'] ?? []) as $g) {
            foreach (($g['items'] ?? []) as $it) {
                $id = trim((string) ($it['id'] ?? ''));
                if ($id === '' || empty($it['slug']) || !isset($it['price']) || !is_numeric($it['price']) || (float) $it['price'] <= 0) {
                    continue;
                }
                if ($id === 'dive-extra' || $id === 'padi-cert-fee' || str_starts_with($id, 'eq-') || str_starts_with($id, 'fee-') || str_starts_with($id, 'ins-')) {
                    continue;
                }
                $valid[$id] = true;
            }
        }
    }
    return $valid;
}

/**
 * Load 2D table rows (`[headers, row1, row2, ...]`) from `--csv`, live `gsheets readonly read`, or local fallback.
 * @return array{source:string, rows:array<int, array<int, string>>}
 */
function sc_load_crm_table(string $csvPath, string $sheetId, string $tab): array
{
    if ($csvPath !== '') {
        if (!is_file($csvPath) || !is_readable($csvPath)) {
            throw new RuntimeException("CSV file not found or unreadable: {$csvPath}");
        }
        $fh = fopen($csvPath, 'rb');
        if ($fh === false) {
            throw new RuntimeException("Unable to open CSV: {$csvPath}");
        }
        $rows = [];
        while (($r = fgetcsv($fh, 0, ',', '"', '')) !== false) {
            if ($r === [null] || $r === []) {
                continue;
            }
            $rows[] = array_map(static fn($v): string => (string) ($v ?? ''), $r);
        }
        fclose($fh);
        return ['source' => 'csv:' . $csvPath, 'rows' => $rows];
    }

    $gsheetsBin = is_executable('/opt/antigravity/bin/gsheets')
        ? '/opt/antigravity/bin/gsheets'
        : trim((string) shell_exec('command -v gsheets 2>/dev/null'));

    if ($gsheetsBin !== '') {
        $cmd = escapeshellarg($gsheetsBin) . ' readonly read '
            . escapeshellarg($sheetId) . ' '
            . escapeshellarg($tab . '!A1:AZ5000') . ' --json';
        $desc = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = proc_open($cmd, $desc, $pipes);
        if (is_resource($proc)) {
            fclose($pipes[0]);
            $stdout = (string) stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $stderr = (string) stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            $code = proc_close($proc);
            if ($code === 0 && trim($stdout) !== '') {
                $decoded = json_decode($stdout, true);
                $matrix = isset($decoded['values']) && is_array($decoded['values']) ? $decoded['values'] : $decoded;
                if (is_array($matrix)) {
                    $rows = [];
                    foreach ($matrix as $r) {
                        if (is_array($r)) {
                            $rows[] = array_map(static fn($v): string => (string) ($v ?? ''), $r);
                        }
                    }
                    return ['source' => "gsheets:{$sheetId}!{$tab}", 'rows' => $rows];
                }
            }
        }
    }

    $localCsv = LEADS_STORAGE_DIR . '/leads.csv';
    if (is_file($localCsv) && is_readable($localCsv)) {
        return sc_load_crm_table($localCsv, $sheetId, $tab);
    }

    return ['source' => 'empty_fallback', 'rows' => []];
}

function sc_norm_header_key(string $raw): string
{
    $clean = ltrim(trim($raw), "\xEF\xBB\xBF_");
    return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '_', $clean), '_'));
}

function sc_parse_eur_amount(string $raw): float
{
    $s = trim((string) preg_replace('/[^\d,\.\-]/', '', $raw));
    if ($s === '' || $s === '-') {
        return 0.0;
    }
    if (preg_match('/^\-?\d{1,3}(,\d{3})+(\.\d+)?$/', $s)) {
        $s = str_replace(',', '', $s);
    } elseif (preg_match('/^\-?\d+(,\d{1,2})$/', $s)) {
        $s = str_replace(',', '.', $s);
    } else {
        $s = str_replace(',', '', $s);
    }
    return is_numeric($s) ? round((float) $s, 2) : 0.0;
}

function sc_infer_iso_country(string $explicitCountry, string $digits): string
{
    $cc = strtolower(trim($explicitCountry));
    if (preg_match('/^[a-z]{2}$/', $cc) && !in_array($cc, ['xx', 'zz'], true)) {
        return $cc;
    }
    $prefixMap = [
        '966' => 'sa', '971' => 'ae', '965' => 'kw', '974' => 'qa', '973' => 'bh', '968' => 'om',
        '20'  => 'eg', '49'  => 'de', '44'  => 'gb', '39'  => 'it', '33'  => 'fr', '48'  => 'pl',
        '86'  => 'cn', '90'  => 'tr', '54'  => 'ar', '41'  => 'ch', '43'  => 'at', '34'  => 'es',
        '31'  => 'nl', '32'  => 'be', '420' => 'cz', '7'   => 'ru', '1'   => 'us',
    ];
    foreach ($prefixMap as $pfx => $iso2) {
        if (str_starts_with($digits, (string) $pfx)) {
            return $iso2;
        }
    }
    return '';
}

try {
    $loaded = sc_load_crm_table((string) $opts['csv'], (string) $opts['sheet_id'], (string) $opts['tab']);
} catch (Throwable $e) {
    if (!empty($opts['json'])) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    } else {
        fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    }
    exit(1);
}

$matrix = $loaded['rows'];
$headers = $matrix[0] ?? [];
$colMap = [];
foreach ($headers as $idx => $hRaw) {
    $norm = sc_norm_header_key($hRaw);
    if ($norm !== '' && !isset($colMap[$norm])) {
        $colMap[$norm] = $idx;
    }
}

$pickCol = static function (array $row, array $aliases) use ($colMap): string {
    foreach ($aliases as $al) {
        if (isset($colMap[$al])) {
            $i = $colMap[$al];
            $val = isset($row[$i]) ? trim((string) $row[$i]) : '';
            if ($val !== '') {
                return $val;
            }
        }
    }
    return '';
};

$validCatalogIds = sc_load_standalone_catalog_ids();
$ledgerFile = LEADS_STORAGE_DIR . '/meta_offline_synced.json';
$ledger = [];
if (is_file($ledgerFile)) {
    $rawLedger = json_decode((string) @file_get_contents($ledgerFile), true);
    if (is_array($rawLedger)) {
        $ledger = $rawLedger;
    }
}

$now = time();
$minAllowedTs = $now - (7 * 86400) + 3600; // Safe 6d 23h floor within Meta CAPI's 7-day event_time window

$totalDataRows = max(0, count($matrix) - 1);
$paidRowsFound = 0;
$alreadySynced = 0;
$skipped = [];
$eventsToSync = [];

for ($rIdx = 1, $rTotal = count($matrix); $rIdx < $rTotal; $rIdx++) {
    $r = $matrix[$rIdx];
    $status = strtolower($pickCol($r, ['status', 'lead_status', 'stage']));
    if ($status !== 'paid') {
        continue;
    }

    $paidRaw = $pickCol($r, ['final_paid_eur', 'paid_eur', 'final_eur', 'conversion_value', 'paid_amount', 'revenue_eur']);
    if ($paidRaw === '') {
        $paidRaw = $pickCol($r, ['estimated_eur', 'estimated_total_eur']);
    }
    $paidEur = sc_parse_eur_amount($paidRaw);
    if ($paidEur <= 0.0) {
        $skipped[] = ['row' => $rIdx + 1, 'reason' => 'non_positive_paid_eur'];
        continue;
    }
    $paidRowsFound++;

    $receivedAt = $pickCol($r, ['received_at', 'clicked_at', 'timestamp', 'created_at']);
    $paidAt     = $pickCol($r, ['paid_at', 'converted_at', 'conversion_time', 'payment_date']);
    $rawEventId = $pickCol($r, ['event_id', 'id', 'lead_event_id']);
    $name       = $pickCol($r, ['name', 'full_name', 'customer_name']);
    $phone      = $pickCol($r, ['phone', 'whatsapp', 'phone_number', 'tel']);
    $email      = $pickCol($r, ['email', 'email_address']);
    $interest   = $pickCol($r, ['interest', 'item_name', 'service']);
    $cartJson   = $pickCol($r, ['cart_json', 'cart']);
    $fbclid     = $pickCol($r, ['fbclid']);
    $rawFbc     = $pickCol($r, ['fbc']);
    $rawFbp     = $pickCol($r, ['fbp']);
    $countryRaw = $pickCol($r, ['country', 'country_code']);
    $pageUrl    = $pickCol($r, ['page_url', 'landing_page']);
    $userAgent  = $pickCol($r, ['user_agent', 'client_user_agent']);

    $baseId = $rawEventId !== ''
        ? $rawEventId
        : substr(hash('sha256', $receivedAt . '|' . $email . '|' . $phone . '|' . $paidEur), 0, 16);
    $orderEventId = str_starts_with($baseId, 'order_') ? $baseId : ('order_' . $baseId);

    if (isset($ledger[$orderEventId]) && empty($opts['force'])) {
        $alreadySynced++;
        continue;
    }

    // Event timestamp: clamp strictly within [now - 6d23h, now] per Meta Graph API v21.0
    $receivedTs = $receivedAt !== '' ? (strtotime($receivedAt) ?: 0) : 0;
    $paidTs     = $paidAt !== '' ? (strtotime($paidAt) ?: 0) : 0;
    $candidateTs = $paidTs > 0 ? $paidTs : ($receivedTs >= $minAllowedTs ? max($receivedTs, $now) : $now);
    $eventTime = min($now, max($minAllowedTs, $candidateTs));

    // Normalized user_data
    $userData = [];
    $cleanEmail = strtolower(trim($email));
    if ($cleanEmail !== '' && filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
        $userData['em'] = [hash('sha256', $cleanEmail)];
    }

    $digits = preg_replace('/\D/', '', $phone) ?? '';
    $digits = preg_replace('/^00/', '', $digits) ?? '';
    if (strlen($digits) === 11 && str_starts_with($digits, '01')) {
        $digits = '2' . $digits; // Egyptian local mobile 01xxxxxxxxx -> 201xxxxxxxxx
    }
    if (strlen($digits) >= 7) {
        $userData['ph'] = [hash('sha256', $digits)];
    }

    if ($name !== '' && stripos($name, 'whatsapp') === false && !str_contains($name, '(')) {
        $cleanName = trim((string) preg_replace('/[^\p{L}\s\-]/u', ' ', $name));
        $parts = array_values(array_filter(preg_split('/\s+/u', $cleanName) ?: []));
        if ($parts !== []) {
            $fn = mb_strtolower($parts[0], 'UTF-8');
            if ($fn !== '') {
                $userData['fn'] = [hash('sha256', $fn)];
            }
            if (count($parts) >= 2) {
                $ln = mb_strtolower((string) end($parts), 'UTF-8');
                if ($ln !== '') {
                    $userData['ln'] = [hash('sha256', $ln)];
                }
            }
        }
    }

    $isoCountry = sc_infer_iso_country($countryRaw, $digits);
    if ($isoCountry !== '') {
        $userData['country'] = [hash('sha256', $isoCountry)];
    }

    $userData['external_id'] = [hash('sha256', strtolower($baseId))];

    // UNHASHED, case-sensitive fbc & fbp!
    $fbc = '';
    if ($rawFbc !== '' && str_starts_with($rawFbc, 'fb.')) {
        $fbc = $rawFbc;
    } elseif ($fbclid !== '') {
        $clickSec = $receivedTs > 0 ? $receivedTs : $eventTime;
        $fbc = 'fb.1.' . ($clickSec * 1000) . '.' . $fbclid;
    }
    if ($fbc !== '') {
        $userData['fbc'] = $fbc;
    }
    if ($rawFbp !== '' && str_starts_with($rawFbp, 'fb.')) {
        $userData['fbp'] = $rawFbp;
    }

    // Require at least one high-signal match key (em, ph, fbc, or fbp)
    if (empty($userData['em']) && empty($userData['ph']) && empty($userData['fbc']) && empty($userData['fbp'])) {
        $skipped[] = ['row' => $rIdx + 1, 'event_id' => $baseId, 'reason' => 'missing_meta_match_keys'];
        continue;
    }

    // Catalog-clean custom_data
    $customData = [
        'currency' => 'EUR',
        'value'    => round($paidEur, 2),
        'order_id' => $baseId,
    ];
    if ($interest !== '') {
        $customData['content_name'] = $interest;
    }

    $validIds = [];
    if ($cartJson !== '' && $cartJson !== '[]') {
        $parsedCart = json_decode($cartJson, true);
        if (is_array($parsedCart)) {
            foreach ($parsedCart as $cLine) {
                $cid = is_array($cLine) ? trim((string) ($cLine['id'] ?? $cLine['item_id'] ?? '')) : trim((string) $cLine);
                if ($cid !== '' && isset($validCatalogIds[$cid])) {
                    $validIds[] = $cid;
                }
            }
        }
    }
    if ($validIds !== []) {
        $customData['content_type'] = 'product';
        $customData['content_ids']  = array_values(array_unique($validIds));
    }

    $ev = [
        'event_name'    => 'Purchase',
        'event_time'    => $eventTime,
        'event_id'      => $orderEventId,
        'action_source' => $actionSource,
        'user_data'     => $userData,
        'custom_data'   => $customData,
    ];
    if ($actionSource === 'website') {
        $ev['event_source_url'] = $pageUrl !== '' ? $pageUrl : (SITE_URL . '/');
        $ev['user_data']['client_user_agent'] = $userAgent !== '' ? $userAgent : 'Mozilla/5.0 (compatible; SeaCollegeCRM/1.0)';
    }

    $eventsToSync[] = [
        'row'        => $rIdx + 1,
        'base_id'    => $baseId,
        'value_eur'  => round($paidEur, 2),
        'event'      => $ev,
    ];
}

$uploaded = 0;
$apiFailures = [];

if (!empty($opts['execute']) && $eventsToSync !== []) {
    $token = META_CAPI_TOKEN !== '' ? META_CAPI_TOKEN : META_ACCESS_TOKEN;
    if ($token === '' || META_PIXEL_ID === '') {
        $msg = 'META_CAPI_TOKEN / META_ACCESS_TOKEN or META_PIXEL_ID is empty in config/secrets.php.';
        if (!empty($opts['json'])) {
            echo json_encode(['ok' => false, 'error' => $msg], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        } else {
            fwrite(STDERR, "Error: {$msg}\n");
        }
        exit(1);
    }

    $endpoint = 'https://graph.facebook.com/' . META_GRAPH_VERSION . '/' . rawurlencode(META_PIXEL_ID)
        . '/events?access_token=' . rawurlencode($token);
    $testCode = trim((string) $opts['test_code']);

    foreach ($eventsToSync as $item) {
        $reqBody = ['data' => [$item['event']]];
        if ($testCode !== '') {
            $reqBody['test_event_code'] = $testCode;
        }
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($reqBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $rawResp = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $respArr = is_string($rawResp) ? (json_decode($rawResp, true) ?: []) : [];
        if ($httpCode >= 200 && $httpCode < 300 && (int) ($respArr['events_received'] ?? 0) >= 1) {
            $uploaded++;
            $oid = (string) $item['event']['event_id'];
            $ledger[$oid] = [
                'synced_at'       => gmdate('c'),
                'row_event_id'    => $item['base_id'],
                'value_eur'       => $item['value_eur'],
                'events_received' => (int) ($respArr['events_received'] ?? 1),
                'fbtrace_id'      => (string) ($respArr['fbtrace_id'] ?? ''),
            ];
        } else {
            $apiFailures[] = [
                'event_id'  => $item['event']['event_id'],
                'http_code' => $httpCode,
                'response'  => $respArr ?: $rawResp,
            ];
        }
    }

    if ($uploaded > 0) {
        if (!is_dir(LEADS_STORAGE_DIR)) {
            @mkdir(LEADS_STORAGE_DIR, 0750, true);
        }
        $fh = fopen($ledgerFile, 'c+');
        if ($fh !== false) {
            flock($fh, LOCK_EX);
            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, (string) json_encode($ledger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }
}

$report = [
    'ok'              => $apiFailures === [],
    'mode'            => !empty($opts['execute']) ? 'execute' : 'dry_run',
    'dataset_id'      => META_PIXEL_ID,
    'graph_version'   => META_GRAPH_VERSION,
    'action_source'   => $actionSource,
    'source'          => $loaded['source'],
    'total_rows_read' => $totalDataRows,
    'paid_rows_found' => $paidRowsFound,
    'already_synced'  => $alreadySynced,
    'ready_events'    => count($eventsToSync),
    'uploaded_events' => $uploaded,
    'skipped'         => $skipped,
    'api_failures'    => $apiFailures,
    'events_preview'  => array_map(static fn(array $x): array => $x['event'], $eventsToSync),
];

if (!empty($opts['json'])) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "=== Sea College Dive Club — Meta Offline CAPI Purchase Sync ===\n";
    echo "Mode            : " . strtoupper($report['mode']) . "\n";
    echo "Dataset / Pixel : " . META_PIXEL_ID . " (" . META_GRAPH_VERSION . ", action_source={$actionSource})\n";
    echo "CRM Source      : " . $loaded['source'] . " ({$totalDataRows} data rows)\n";
    echo "Paid Rows Found : {$paidRowsFound} (ready: " . count($eventsToSync) . ", already synced: {$alreadySynced}, skipped: " . count($skipped) . ")\n";
    if (!empty($opts['execute'])) {
        echo "Uploaded Events : {$uploaded} (failures: " . count($apiFailures) . ")\n";
    } else {
        echo "Dry-Run Status  : Validated " . count($eventsToSync) . " Purchase event payload(s) — 0 Graph API calls made.\n";
    }
}

exit($apiFailures === [] ? 0 : 1);
