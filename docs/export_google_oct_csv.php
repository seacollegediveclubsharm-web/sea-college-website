#!/usr/bin/env php
<?php
/**
 * Sea College Dive Club — Google Ads Offline Conversion Import (OCT) CSV Exporter CLI.
 *
 * Reads `Status=Paid` rows (with `Final_Paid_EUR > 0` and non-empty `gclid`/`gbraid`/`wbraid`)
 * from CRM Sheet `1ItvQGWnrHwnSbyGDoGNtV2CkVwNJXIyU_aaIFxIOGbc` (`Leads` tab) or `--csv=<path>`,
 * maps `wa_*` events to `'WhatsApp Lead'` and `lead_*` events to `'Reservation Form Submitted'`,
 * and formats timestamps in `Africa/Cairo` per Google Ads Offline Conversion Import spec.
 *
 * Usage:
 *   php docs/export_google_oct_csv.php --dry-run [--json]
 *   php docs/export_google_oct_csv.php --csv=/path/to/leads.csv [--out=/path/to/oct.csv]
 *   php docs/export_google_oct_csv.php --out=storage/google_oct_export.csv
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: CLI only.');
}

require_once dirname(__DIR__) . '/config/site_config.php';

/**
 * @return array<string, mixed>
 */
function sc_oct_parse_args(array $argv): array
{
    $opts = [
        'dry_run'  => false,
        'out'      => '',
        'csv'      => '',
        'sheet_id' => '1ItvQGWnrHwnSbyGDoGNtV2CkVwNJXIyU_aaIFxIOGbc',
        'tab'      => 'Leads',
        'json'     => false,
        'help'     => false,
    ];
    $count = count($argv);
    for ($i = 1; $i < $count; $i++) {
        $arg = (string) $argv[$i];
        if ($arg === '--dry-run') {
            $opts['dry_run'] = true;
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

$opts = sc_oct_parse_args($argv ?? []);
if (!empty($opts['help'])) {
    echo "Usage: php docs/export_google_oct_csv.php [--dry-run] [--csv=FILE] [--out=FILE] [--sheet-id=ID] [--tab=Leads] [--json]\n";
    exit(0);
}

/**
 * @return array{source:string, rows:array<int, array<int, string>>}
 */
function sc_oct_load_rows(string $csvPath, string $sheetId, string $tab): array
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
        return sc_oct_load_rows($localCsv, $sheetId, $tab);
    }

    return ['source' => 'empty_fallback', 'rows' => []];
}

function sc_oct_norm_key(string $raw): string
{
    $clean = ltrim(trim($raw), "\xEF\xBB\xBF_");
    return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '_', $clean), '_'));
}

function sc_oct_parse_eur(string $raw): float
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

try {
    $loaded = sc_oct_load_rows((string) $opts['csv'], (string) $opts['sheet_id'], (string) $opts['tab']);
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
    $norm = sc_oct_norm_key($hRaw);
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

$cairoTz = new DateTimeZone('Africa/Cairo');
$totalDataRows = max(0, count($matrix) - 1);
$paidRows = 0;
$exportRows = [];

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
    $paidEur = sc_oct_parse_eur($paidRaw);
    if ($paidEur <= 0.0) {
        continue;
    }
    $paidRows++;

    $gclid  = $pickCol($r, ['gclid', 'google_click_id']);
    $gbraid = $pickCol($r, ['gbraid']);
    $wbraid = $pickCol($r, ['wbraid']);
    $clickId = $gclid !== '' ? $gclid : ($gbraid !== '' ? $gbraid : $wbraid);
    if ($clickId === '') {
        continue;
    }

    $eventId     = $pickCol($r, ['event_id', 'id', 'lead_event_id']);
    $name        = $pickCol($r, ['name', 'full_name', 'customer_name']);
    $interest    = $pickCol($r, ['interest', 'item_name', 'service']);
    $utmMedium   = $pickCol($r, ['utm_medium']);
    $receivedAt  = $pickCol($r, ['received_at', 'clicked_at', 'timestamp', 'created_at']);
    $paidAt      = $pickCol($r, ['paid_at', 'converted_at', 'conversion_time', 'payment_date']);
    $octExported = $pickCol($r, ['oct_exported']);

    $isWa = str_starts_with(strtolower($eventId), 'wa_')
        || stripos($name, 'whatsapp') !== false
        || stripos($interest, 'whatsapp_click') !== false
        || strtolower($utmMedium) === 'whatsapp';
    $conversionName = $isWa ? 'WhatsApp Lead' : 'Reservation Form Submitted';

    $receivedTs = $receivedAt !== '' ? (strtotime($receivedAt) ?: 0) : 0;
    $convTs = 0;
    if ($paidAt !== '') {
        $convTs = strtotime($paidAt) ?: 0;
    } elseif ($octExported !== '' && preg_match('/(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}(?:[+-]\d{2}:?\d{2}|Z)?)/', $octExported, $m)) {
        $convTs = strtotime($m[1]) ?: 0;
    }
    if ($convTs <= 0) {
        $convTs = max(($receivedTs > 0 ? $receivedTs + 60 : 0), time());
    } elseif ($receivedTs > 0 && $convTs <= $receivedTs) {
        $convTs = $receivedTs + 60;
    }

    $dt = (new DateTimeImmutable('@' . $convTs))->setTimezone($cairoTz);
    $convTimeStr = $dt->format('Y-m-d H:i:sP');

    $exportRows[] = [
        'click_id'        => $clickId,
        'conversion_name' => $conversionName,
        'conversion_time' => $convTimeStr,
        'value_eur'       => number_format($paidEur, 2, '.', ''),
        'currency'        => 'EUR',
        'event_id'        => $eventId,
    ];
}

// Render CSV buffer in memory
$mem = fopen('php://temp', 'r+b');
fwrite($mem, "Parameters:TimeZone=Africa/Cairo\n");
fwrite($mem, "Google Click ID,Conversion Name,Conversion Time,Conversion Value,Conversion Currency\n");
foreach ($exportRows as $er) {
    fputcsv($mem, [$er['click_id'], $er['conversion_name'], $er['conversion_time'], $er['value_eur'], $er['currency']], ',', '"', '');
}
rewind($mem);
$csvOutput = (string) stream_get_contents($mem);
fclose($mem);

$outPath = trim((string) $opts['out']);
if ($outPath !== '' && empty($opts['dry_run'])) {
    $dir = dirname($outPath);
    if ($dir !== '' && !is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    if (file_put_contents($outPath, $csvOutput, LOCK_EX) === false) {
        fwrite(STDERR, "Failed to write OCT CSV to {$outPath}\n");
        exit(1);
    }
}

if (!empty($opts['json'])) {
    echo json_encode([
        'ok'               => true,
        'mode'             => !empty($opts['dry_run']) ? 'dry_run' : 'export',
        'timezone'         => 'Africa/Cairo',
        'source'           => $loaded['source'],
        'total_rows_read'  => $totalDataRows,
        'paid_rows_found'  => $paidRows,
        'oct_rows_ready'   => count($exportRows),
        'out_file'         => (!empty($opts['dry_run']) ? null : ($outPath !== '' ? $outPath : 'stdout')),
        'rows'             => $exportRows,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    exit(0);
}

if (!empty($opts['dry_run'])) {
    echo "=== Sea College Dive Club — Google Ads OCT CSV Exporter (DRY-RUN) ===\n";
    echo "CRM Source     : " . $loaded['source'] . " ({$totalDataRows} data rows)\n";
    echo "Paid Rows      : {$paidRows}\n";
    echo "OCT Rows Ready : " . count($exportRows) . " (TimeZone=Africa/Cairo)\n";
    exit(0);
}

if ($outPath !== '') {
    echo "Exported " . count($exportRows) . " Google Ads OCT row(s) to {$outPath}\n";
} else {
    echo $csvOutput;
}
exit(0);
