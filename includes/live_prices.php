<?php
/**
 * Live Google Sheet EUR Price & Instant Discount Engine.
 *
 * Reads atomic local snapshot `storage/live_prices.json` (updated in <0.5s on every
 * Google Sheet cell edit via `handlers/price_sync_webhook.php`, or pulled automatically
 * from `PRICE_SHEET_CSV_URL` if configured) and overlays live EUR prices, percentage
 * discounts, and manual post-discount EUR prices across 100% of `data/catalog.php`.
 */
declare(strict_types=1);

function sc_live_prices_path(): string
{
    $dir = defined('LEADS_STORAGE_DIR') ? LEADS_STORAGE_DIR : (dirname(__DIR__) . '/storage');
    return rtrim($dir, '/') . '/live_prices.json';
}

/**
 * Normalize a numeric cell value (accepts 15, "15%", "0.15", "€349", etc.).
 */
function sc_parse_sheet_num(mixed $val): ?float
{
    if ($val === null || $val === '' || is_bool($val)) {
        return null;
    }
    if (is_int($val) || is_float($val)) {
        return is_finite((float) $val) ? (float) $val : null;
    }
    $s = trim((string) $val);
    if ($s === '' || strcasecmp($s, 'on request') === 0 || $s === '—' || $s === '-') {
        return null;
    }
    $clean = preg_replace('/[^0-9.\-]/', '', str_replace(',', '.', $s));
    if ($clean === null || $clean === '' || !is_numeric($clean)) {
        return null;
    }
    return (float) $clean;
}

/**
 * Normalize a percentage input so typing `15`, `15%`, or `0.15` all evaluate to `0.15` (15%).
 */
function sc_parse_sheet_pct(mixed $val): float
{
    if ($val === null || $val === '') {
        return 0.0;
    }
    $rawStr = trim((string) $val);
    $hasPctSign = str_contains($rawStr, '%');
    $num = sc_parse_sheet_num($val);
    if ($num === null || $num <= 0) {
        return 0.0;
    }
    if ($hasPctSign) {
        return min(0.95, $num / 100.0);
    }
    $frac = $num > 1.0 ? ($num / 100.0) : $num;
    return min(0.95, max(0.0, $frac));
}

/**
 * Parse raw CSV text from `01_Master_Prices_EUR` (Published-to-Web CSV fallback) into normalized state.
 *
 * @return array<string,mixed>|null
 */
function sc_parse_price_sheet_csv(string $csvText): ?array
{
    $lines = preg_split('/\r\n|\n|\r/', ltrim($csvText, "\xEF\xBB\xBF"));
    if (!is_array($lines) || count($lines) < 7) {
        return null;
    }
    $grid = array_map(static fn(string $ln): array => str_getcsv($ln, ',', '"', ''), $lines);
    $row3 = $grid[2] ?? [];
    $row4 = $grid[3] ?? [];

    $payload = [
        'synced_at'  => gmdate('c'),
        'source'     => 'csv_pull',
        'global'     => [
            'discount_pct'   => $row3[1] ?? 0,
            'activate'       => strtoupper(trim((string) ($row3[3] ?? 'AUTO'))),
            'include_addons' => strtoupper(trim((string) ($row3[5] ?? 'NO'))),
            'promo_label'    => trim((string) ($row3[7] ?? '')),
        ],
        'categories' => [
            'dive-packages' => $row4[2] ?? 0,
            'padi-courses'  => $row4[4] ?? 0,
            'offers'        => $row4[6] ?? 0,
            'snorkeling'    => $row4[8] ?? 0,
        ],
        'items'      => [],
    ];

    for ($r = 6, $n = count($grid); $r < $n; $r++) {
        $row = $grid[$r];
        $id = trim((string) ($row[0] ?? ''));
        if ($id === '' || !preg_match('/^[a-z0-9\-]+$/', $id)) {
            continue;
        }
        $payload['items'][$id] = [
            'standard_eur'       => $row[7] ?? '',
            'activate_discount'  => strtoupper(trim((string) ($row[8] ?? 'AUTO'))),
            'discount_price_eur' => $row[9] ?? '',
            'discount_pct'       => $row[10] ?? '',
            'child_price_eur'    => $row[11] ?? '',
            'bundle_save_eur'    => $row[12] ?? '',
        ];
    }

    return count($payload['items']) >= 10 ? sc_normalize_live_price_payload($payload) : null;
}

/**
 * Normalize and validate an incoming webhook or CSV payload before persisting to disk.
 *
 * @param array<string,mixed> $raw
 * @return array<string,mixed>
 */
function sc_normalize_live_price_payload(array $raw): array
{
    $globalRaw = is_array($raw['global'] ?? null) ? $raw['global'] : [];
    $catsRaw   = is_array($raw['categories'] ?? null) ? $raw['categories'] : [];
    $itemsRaw  = is_array($raw['items'] ?? null) ? $raw['items'] : [];

    $globalAct = strtoupper(trim((string) ($globalRaw['activate'] ?? 'AUTO')));
    if (!in_array($globalAct, ['AUTO', 'YES', 'NO'], true)) {
        $globalAct = 'AUTO';
    }
    $incAddons = strtoupper(trim((string) ($globalRaw['include_addons'] ?? 'NO'))) === 'YES';
    $globalPct = ($globalAct === 'NO') ? 0.0 : sc_parse_sheet_pct($globalRaw['discount_pct'] ?? 0);

    $normCats = [];
    foreach (['dive-packages', 'padi-courses', 'offers', 'snorkeling'] as $ck) {
        $normCats[$ck] = sc_parse_sheet_pct($catsRaw[$ck] ?? 0);
    }

    $normItems = [];
    foreach ($itemsRaw as $id => $row) {
        $cleanId = trim((string) $id);
        if ($cleanId === '' || !preg_match('/^[a-z0-9\-]{1,64}$/', $cleanId) || !is_array($row)) {
            continue;
        }
        $act = strtoupper(trim((string) ($row['activate_discount'] ?? 'AUTO')));
        if (!in_array($act, ['AUTO', 'YES', 'NO'], true)) {
            $act = 'AUTO';
        }
        $std   = sc_parse_sheet_num($row['standard_eur'] ?? null);
        $discP = sc_parse_sheet_num($row['discount_price_eur'] ?? null);
        $discK = sc_parse_sheet_pct($row['discount_pct'] ?? 0);
        $child = sc_parse_sheet_num($row['child_price_eur'] ?? null);
        $bSave = sc_parse_sheet_num($row['bundle_save_eur'] ?? null);

        $normItems[$cleanId] = [
            'standard_eur'       => ($std !== null && $std > 0) ? round($std, 2) : null,
            'activate_discount'  => $act,
            'discount_price_eur' => ($discP !== null && $discP > 0) ? round($discP, 2) : null,
            'discount_pct'       => $discK,
            'child_price_eur'    => ($child !== null && $child > 0) ? round($child, 2) : null,
            'bundle_save_eur'    => ($bSave !== null && $bSave > 0) ? round($bSave, 2) : null,
        ];
    }

    return [
        'sheet_id'   => trim((string) ($raw['sheet_id'] ?? (defined('PRICE_SHEET_ID') ? PRICE_SHEET_ID : ''))),
        'synced_at'  => gmdate('c'),
        'global'     => [
            'discount_pct'   => $globalPct,
            'activate'       => $globalAct,
            'include_addons' => $incAddons,
            'promo_label'    => substr(trim((string) ($globalRaw['promo_label'] ?? '')), 0, 80),
        ],
        'categories' => $normCats,
        'items'      => $normItems,
    ];
}

/**
 * Atomically write normalized live prices JSON to `storage/live_prices.json`.
 *
 * @param array<string,mixed> $normalized
 */
function sc_save_live_prices(array $normalized): bool
{
    $path = sc_live_prices_path();
    $dir  = dirname($path);
    if (!is_dir($dir) && !@mkdir($dir, 0750, true)) {
        return false;
    }
    $json = json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($json)) {
        return false;
    }
    $tmp = $path . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));
    if (@file_put_contents($tmp, $json . "\n", LOCK_EX) === false) {
        return false;
    }
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }
    $GLOBALS['SC_LIVE_PRICES_MEMO'] = $normalized;
    return true;
}

/**
 * Load normalized live price overrides from `storage/live_prices.json` (with optional CSV pull refresh).
 *
 * @return array<string,mixed>|null
 */
function sc_load_live_prices(): ?array
{
    if (array_key_exists('SC_LIVE_PRICES_MEMO', $GLOBALS)) {
        return $GLOBALS['SC_LIVE_PRICES_MEMO'];
    }

    $path = sc_live_prices_path();
    $csvUrl = defined('PRICE_SHEET_CSV_URL') ? PRICE_SHEET_CSV_URL : '';
    $ttl = defined('PRICE_SHEET_PULL_TTL') ? max(1, PRICE_SHEET_PULL_TTL) : 5;
    $forceRefresh = isset($_GET['refresh_prices']) && $_GET['refresh_prices'] === '1';

    if ($csvUrl !== '' && str_starts_with($csvUrl, 'https://docs.google.com/spreadsheets/')) {
        $stale = !is_file($path) || $forceRefresh || ((time() - (int) @filemtime($path)) >= $ttl);
        if ($stale && function_exists('curl_init')) {
            $ch = curl_init($csvUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_TIMEOUT_MS     => 1600,
                CURLOPT_CONNECTTIMEOUT_MS => 900,
            ]);
            $csvRaw = curl_exec($ch);
            $code   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (is_string($csvRaw) && $code === 200 && $csvRaw !== '') {
                $parsed = sc_parse_price_sheet_csv($csvRaw);
                if ($parsed !== null) {
                    sc_save_live_prices($parsed);
                    return $parsed;
                }
            }
        }
    }

    if (!is_file($path)) {
        return $GLOBALS['SC_LIVE_PRICES_MEMO'] = null;
    }

    $raw = @file_get_contents($path);
    if (!is_string($raw) || $raw === '') {
        return $GLOBALS['SC_LIVE_PRICES_MEMO'] = null;
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded) || empty($decoded['items']) || !is_array($decoded['items'])) {
        return $GLOBALS['SC_LIVE_PRICES_MEMO'] = null;
    }

    return $GLOBALS['SC_LIVE_PRICES_MEMO'] = $decoded;
}

/**
 * Format numeric EUR cleanly as int when whole (400) or 2-decimal float when fractional (349.5).
 */
function sc_clean_eur_val(float $v): int|float
{
    $r = round($v, 2);
    return (abs($r - round($r)) < 0.001) ? (int) round($r) : $r;
}

/**
 * Overlay live Google Sheet EUR prices & discounts onto the master catalog structure.
 */
function sc_apply_live_prices(array $catalog): array
{
    $live = sc_load_live_prices();
    if ($live === null) {
        return $catalog;
    }

    $globalPct    = (float) ($live['global']['discount_pct'] ?? 0.0);
    $globalAct    = strtoupper((string) ($live['global']['activate'] ?? 'AUTO'));
    $incAddons    = !empty($live['global']['include_addons']);
    $catPcts      = is_array($live['categories'] ?? null) ? $live['categories'] : [];
    $itemOverrides = is_array($live['items'] ?? null) ? $live['items'] : [];

    foreach ($catalog as $catKey => &$cat) {
        $catPct = (float) ($catPcts[$catKey] ?? 0.0);
        foreach ($cat['groups'] as &$g) {
            foreach ($g['items'] as &$it) {
                $id = (string) ($it['id'] ?? '');
                if ($id === '' || !isset($itemOverrides[$id])) {
                    continue;
                }
                $ov = $itemOverrides[$id];

                // 1. Base / Standard EUR price
                $baseEur = $ov['standard_eur'] ?? null;
                if ($baseEur === null) {
                    $it['price'] = null;
                    unset($it['regular_price'], $it['discount_pct']);
                    continue;
                }
                $baseFloat = (float) $baseEur;
                $baseBundleSave = isset($ov['bundle_save_eur']) && $ov['bundle_save_eur'] !== null
                    ? (float) $ov['bundle_save_eur']
                    : (isset($it['save']) && is_numeric($it['save']) ? (float) $it['save'] : 0.0);

                // Determine if item is an add-on / rental / fee
                $isAddon = ($id === 'dive-extra' || $id === 'padi-cert-fee'
                    || str_starts_with($id, 'eq-') || str_starts_with($id, 'fee-') || str_starts_with($id, 'ins-'));

                // 2. Resolve active discount hierarchy
                $act = strtoupper((string) ($ov['activate_discount'] ?? 'AUTO'));
                $finalFloat = $baseFloat;
                $appliedPct = 0.0;

                if ($act !== 'NO' && $baseFloat > 0) {
                    $manualSale = isset($ov['discount_price_eur']) && is_numeric($ov['discount_price_eur'])
                        ? (float) $ov['discount_price_eur']
                        : null;
                    $itemPct = (float) ($ov['discount_pct'] ?? 0.0);

                    if ($manualSale !== null && $manualSale > 0 && $manualSale < $baseFloat) {
                        $finalFloat = $manualSale;
                        $appliedPct = ($baseFloat - $finalFloat) / $baseFloat;
                    } elseif ($itemPct > 0.0) {
                        $finalFloat = round($baseFloat * (1.0 - $itemPct));
                        $appliedPct = $itemPct;
                    } elseif ($catPct > 0.0) {
                        $finalFloat = round($baseFloat * (1.0 - $catPct));
                        $appliedPct = $catPct;
                    } elseif ($globalPct > 0.0 && $globalAct !== 'NO' && (!$isAddon || $incAddons)) {
                        $finalFloat = round($baseFloat * (1.0 - $globalPct));
                        $appliedPct = $globalPct;
                    }
                }

                $finalFloat = max(1.0, $finalFloat);
                $it['price'] = sc_clean_eur_val($finalFloat);

                if ($finalFloat < ($baseFloat - 0.009)) {
                    $diff = $baseFloat - $finalFloat;
                    $it['regular_price'] = sc_clean_eur_val($baseFloat);
                    $it['discount_pct']  = max(1, (int) round(($diff / $baseFloat) * 100));
                    $it['save']          = sc_clean_eur_val($baseBundleSave + $diff);
                } else {
                    unset($it['regular_price'], $it['discount_pct']);
                    if ($baseBundleSave > 0) {
                        $it['save'] = sc_clean_eur_val($baseBundleSave);
                    } else {
                        unset($it['save']);
                    }
                }

                // 3. Optional Child EUR price (scales by applied percentage if no manual child override)
                if (isset($ov['child_price_eur']) && is_numeric($ov['child_price_eur']) && (float) $ov['child_price_eur'] > 0) {
                    $childBase = (float) $ov['child_price_eur'];
                    $childFinal = ($appliedPct > 0.0 && $finalFloat < $baseFloat)
                        ? max(1.0, round($childBase * (1.0 - $appliedPct)))
                        : $childBase;
                    $it['child_price'] = sc_clean_eur_val($childFinal);
                }
            }
        }
    }
    unset($cat, $g, $it);

    return $catalog;
}
