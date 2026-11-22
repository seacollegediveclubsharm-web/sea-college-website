<?php
/**
 * Sea College Dive Club — Unified ERP & Big-Data SQL Bridge (`includes/erp_db.php`)
 * Maintained by Ayman (Website Developer) & Karim (ERP/Mobile Architect).
 *
 * - Connects safely to protected `storage/erp/sea_college_bigdata.sqlite3` (WAL mode).
 * - Provides non-blocking real-time ingestion hooks for `lead_capture.php`,
 *   `diver_checkin.php`, and `wa_click_beacon.php` so website conversions land
 *   immediately inside the unified company database without ever risking form uptime.
 * - Provides RBAC authentication, permission checks, audit logging, and pure-PHP
 *   multi-sheet `.xlsx` (ZipArchive OpenXML) + UTF-8 BOM `.csv` export builders.
 */
declare(strict_types=1);

if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__));
}

define('SC_ERP_DB_PATH', ROOT_DIR . '/storage/erp/sea_college_bigdata.sqlite3');

/**
 * Open PDO connection to the unified SQLite warehouse (returns null safely if unavailable).
 */
function sc_erp_pdo(): ?PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    try {
        $dir = dirname(SC_ERP_DB_PATH);
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
            @file_put_contents($dir . '/.htaccess', "Require all denied\n");
        }
        $pdo = new PDO('sqlite:' . SC_ERP_DB_PATH, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 5,
        ]);
        $pdo->exec('PRAGMA journal_mode = WAL;');
        $pdo->exec('PRAGMA busy_timeout = 4000;');
        $pdo->exec('PRAGMA foreign_keys = ON;');

        $hasFullSchema = (int) $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name IN ('users','branches','reservation_accounting_audit','trial_feedback')")->fetchColumn();
        if ($hasFullSchema < 4) {
            $schemaFile = ROOT_DIR . '/includes/erp_schema_init.sql';
            if (is_file($schemaFile)) {
                $pdo->exec((string) file_get_contents($schemaFile));
            }
        }

        require_once __DIR__ . '/erp_trial_seed.php';
        sc_erp_seed_trial_if_empty($pdo);
        sc_erp_ensure_user_profiles($pdo);
        return $pdo;
    } catch (Throwable $e) {
        error_log('SC_ERP DB connection warning: ' . $e->getMessage());
        return null;
    }
}

/**
 * Idempotently add user profile columns (`phone`, `email`, `avatar_url`, `job_title_en`,
 * `job_title_ar`, `bio_notes`, `updated_at`) to `users` and populate initial profile records
 * (including Islam Samir & Sayed Elsharqawy photos, phones, and corporate emails) without
 * overwriting any subsequent live edits made in `/app`.
 */
function sc_erp_ensure_user_profiles(PDO $pdo): void
{
    $cols = array_column($pdo->query('PRAGMA table_info(users)')->fetchAll(), 'name');
    $needed = [
        'job_title_en' => "TEXT NOT NULL DEFAULT ''",
        'job_title_ar' => "TEXT NOT NULL DEFAULT ''",
        'phone'        => "TEXT NOT NULL DEFAULT ''",
        'email'        => "TEXT NOT NULL DEFAULT ''",
        'avatar_url'   => "TEXT NOT NULL DEFAULT ''",
        'bio_notes'    => "TEXT NOT NULL DEFAULT ''",
        'updated_at'   => "TEXT NOT NULL DEFAULT ''",
    ];
    foreach ($needed as $col => $ddl) {
        if (!in_array($col, $cols, true)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN {$col} {$ddl}");
        }
    }

    // Apply direct owner profiles when not yet customized
    $now = gmdate('Y-m-d\TH:i:s\Z');
    $owners = [
        'islam.samir' => [
            'name_en'  => 'Islam Samir',
            'name_ar'  => 'إسلام سمير',
            'title_en' => 'Founder & Managing Owner',
            'title_ar' => 'المؤسس والشريك المالك',
            'phone'    => '+20 10 00344544',
            'email'    => 'islam.samir@seacollegediveclub.com',
            'avatar'   => '/assets/erp/avatars/islam-samir.jpg',
        ],
        'eng.sayed' => [
            'name_en'  => 'Sayed Elsharqawy',
            'name_ar'  => 'سيد الشرقاوي',
            'title_en' => 'Co-Founder & Managing Owner',
            'title_ar' => 'الشريك المؤسس والمالك',
            'phone'    => '+20 11 14567886',
            'email'    => 'sayed.elsharqawy@seacollegediveclub.com',
            'avatar'   => '/assets/erp/avatars/sayed-elsharqawy.jpg',
        ],
    ];
    $ownChk = $pdo->prepare('SELECT phone, email, avatar_url, updated_at FROM users WHERE username = ?');
    $ownUpd = $pdo->prepare(
        'UPDATE users
         SET display_name_en = CASE WHEN updated_at = "" THEN ? ELSE display_name_en END,
             display_name_ar = CASE WHEN updated_at = "" THEN ? ELSE display_name_ar END,
             job_title_en    = CASE WHEN job_title_en = "" THEN ? ELSE job_title_en END,
             job_title_ar    = CASE WHEN job_title_ar = "" THEN ? ELSE job_title_ar END,
             phone           = CASE WHEN phone = "" THEN ? ELSE phone END,
             email           = CASE WHEN email = "" THEN ? ELSE email END,
             avatar_url      = CASE WHEN avatar_url = "" THEN ? ELSE avatar_url END
         WHERE username = ?'
    );
    foreach ($owners as $uname => $meta) {
        $ownChk->execute([$uname]);
        $cur = $ownChk->fetch();
        if ($cur && ($cur['phone'] === '' || $cur['avatar_url'] === '' || $cur['email'] === '')) {
            $ownUpd->execute([
                $meta['name_en'], $meta['name_ar'],
                $meta['title_en'], $meta['title_ar'],
                $meta['phone'], $meta['email'], $meta['avatar'],
                $uname,
            ]);
        }
    }

    // Backfill default corporate email & clean job titles for any remaining users with empty email/job_title
    $missing = $pdo->query(
        "SELECT u.id, u.username, u.display_name_en, u.display_name_ar, u.email, u.job_title_en, u.job_title_ar,
                r.name_en AS role_en, r.name_ar AS role_ar
         FROM users u JOIN roles r ON r.role_code = u.role_code
         WHERE u.email = '' OR u.job_title_en = ''"
    )->fetchAll();
    if ($missing) {
        $fillSt = $pdo->prepare(
            'UPDATE users
             SET email        = CASE WHEN email = "" THEN ? ELSE email END,
                 job_title_en = CASE WHEN job_title_en = "" THEN ? ELSE job_title_en END,
                 job_title_ar = CASE WHEN job_title_ar = "" THEN ? ELSE job_title_ar END
             WHERE id = ?'
        );
        foreach ($missing as $m) {
            $defEmail = $m['username'] === 'lara.care'
                ? 'info@seacollegediveclub.com'
                : ($m['username'] . '@seacollegediveclub.com');
            $titleEn = preg_match('/\(([^)]+)\)/', (string) $m['display_name_en'], $mtEn) ? trim($mtEn[1]) : (string) $m['role_en'];
            $titleAr = preg_match('/\(([^)]+)\)/u', (string) $m['display_name_ar'], $mtAr) ? trim($mtAr[1]) : (string) $m['role_ar'];
            $fillSt->execute([$defEmail, $titleEn, $titleAr, $m['id']]);
        }
    }
}

/**
 * Verify password against `sha256$<salt>$<hex>` or standard PHP `password_verify`.
 */
function sc_erp_verify_password(string $plain, string $storedHash): bool
{
    if (str_starts_with($storedHash, 'sha256$')) {
        $parts = explode('$', $storedHash, 3);
        if (count($parts) === 3) {
            $calc = hash('sha256', $parts[1] . ':' . $plain);
            return hash_equals($parts[2], $calc);
        }
        return false;
    }
    return password_verify($plain, $storedHash);
}

/**
 * Hash a password in cross-platform Python/PHP format.
 */
function sc_erp_hash_password(string $plain): string
{
    $salt = 'sc_erp_2026_salt';
    return 'sha256$' . $salt . '$' . hash('sha256', $salt . ':' . $plain);
}

/**
 * Compute ISO period buckets (week, month, quarter, year) for any YYYY-MM-DD date.
 *
 * @return array{0:string,1:string,2:string,3:string}
 */
function sc_erp_period_parts(string $isoDate): array
{
    $ts = strtotime($isoDate) ?: time();
    $y  = (int) gmdate('Y', $ts);
    $m  = (int) gmdate('n', $ts);
    $q  = (int) floor(($m - 1) / 3) + 1;
    $wk = gmdate('o-\WW', $ts);
    return [$wk, sprintf('%04d-%02d', $y, $m), sprintf('%04d-Q%d', $y, $q), (string) $y];
}

/**
 * Non-blocking hook called by `handlers/lead_capture.php`.
 * Inserts website booking/contact lead into `website_leads_crm` AND creates a pending
 * `WEB-*` reservation voucher when a trip date or positive EUR cart exists.
 */
function sc_erp_ingest_website_lead(array $lead): void
{
    try {
        $pdo = sc_erp_pdo();
        if (!$pdo) {
            return;
        }
        $eventId = (string) ($lead['event_id'] ?? ('lead_' . bin2hex(random_bytes(6))));
        $now     = (string) ($lead['received_at'] ?? gmdate('c'));
        $tripDt  = (string) ($lead['date'] ?? '');
        if ($tripDt === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tripDt)) {
            $tripDt = gmdate('Y-m-d');
        }
        $eur     = (float) ($lead['estimated_total_eur'] ?? $lead['estimated_eur'] ?? 0.0);
        $people  = max(1, (int) ($lead['people'] ?? 1));
        $voNo    = 'WEB-' . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $eventId), -6));

        $stmt = $pdo->prepare(
            'INSERT OR REPLACE INTO website_leads_crm
             (event_id, received_at, lead_type, lang, guest_name, phone, email, interest_items,
              people, trip_date, hotel, room_no, cert_level, gear_sizes_json, estimated_eur, final_paid_eur,
              status, gclid, fbclid, utm_source, utm_campaign, assigned_to, linked_voucher_no)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $eventId,
            $now,
            strtoupper((string) ($lead['form'] ?? 'BOOKING')) . '_LEAD',
            (string) ($lead['lang'] ?? 'en'),
            (string) ($lead['name'] ?? ''),
            (string) ($lead['phone'] ?? ''),
            (string) ($lead['email'] ?? ''),
            (string) ($lead['interest'] ?? ''),
            $people,
            $tripDt,
            (string) ($lead['hotel'] ?? ''),
            '',
            (string) ($lead['level'] ?? ''),
            json_encode($lead['cart'] ?? [], JSON_UNESCAPED_UNICODE) ?: '[]',
            $eur,
            'NEW',
            (string) ($lead['gclid'] ?? ''),
            (string) ($lead['fbclid'] ?? ''),
            (string) ($lead['utm_source'] ?? ''),
            (string) ($lead['utm_campaign'] ?? ''),
            'LARA',
            $voNo,
        ]);

        if ($eur > 0 || ($lead['form'] ?? '') === 'booking') {
            [$wk, $mo, $qr, $yr] = sc_erp_period_parts($tripDt);
            $revEgp  = round($eur * 54.0, 2);
            $cogsEgp = round($revEgp * 0.36, 2);
            $profEgp = round($revEgp - $cogsEgp, 2);
            $qrTok   = 'QR-' . strtoupper(substr(hash('sha256', $voNo . ':' . $eventId), 0, 12));

            $vStmt = $pdo->prepare(
                'INSERT INTO reservations_fact
                 (voucher_no, qr_token, trip_date, period_week, period_month, period_quarter, period_year,
                  branch_code, channel, hotel_name, pickup_zone, room_no, nationality, pickup_time,
                  trip_name, product_category, adults, children, infants, total_pax,
                  snork_pax, intro1_pax, intro2_pax, dives_pax, divers_names,
                  sales_rep, executor, client_agency, rest_egp, rest_usd, rest_eur, rest_gbp, rest_egp_equiv,
                  estimated_revenue_egp, estimated_cogs_egp, estimated_profit_egp, vessel_code, boarding_status, payment_status, source_file)
                 VALUES (?,?,?,?,?,?,?, ?,?,?,?, ?,?,?, ?,?,?,?,?,?, 0,0,0,?, ?, ?,?,?, 0,0,?,?,?, ?,?,?, ?,?,?,?)'
            );
            $vStmt->execute([
                $voNo, $qrTok, $tripDt, $wk, $mo, $qr, $yr,
                'WEBSITE', 'WEBSITE_ONLINE', (string) ($lead['hotel'] ?? 'Online Direct'), '', '', strtoupper((string) ($lead['lang'] ?? 'EN')), '07:45',
                substr((string) ($lead['interest'] ?? 'Website Booking'), 0, 120), 'WEBSITE_DIRECT_BOOKING',
                $people, 0, 0, $people,
                $people, (string) ($lead['name'] ?? ''),
                'ONLINE_WEBSITE', 'SEA COLLEGE', 'WEBSITE_DIRECT',
                $eur, $revEgp,
                $revEgp, $cogsEgp, $profEgp,
                '', 'BOOKED', 'OPEN_REST', 'LIVE_WEBSITE_FORM',
            ]);
        }
    } catch (Throwable $e) {
        error_log('SC_ERP lead sync warning: ' . $e->getMessage());
    }
}

/**
 * Non-blocking hook called by `handlers/diver_checkin.php`.
 */
function sc_erp_ingest_diver_checkin(array $row, array $attr = []): void
{
    try {
        $pdo = sc_erp_pdo();
        if (!$pdo) {
            return;
        }
        $cid  = (string) ($row['checkin_id'] ?? ('ci_' . bin2hex(random_bytes(6))));
        $gear = [
            'wetsuit' => $row['wetsuit_size'] ?? '',
            'fins'    => $row['fin_size'] ?? '',
            'bcd'     => $row['bcd_size'] ?? '',
            'nitrox'  => $row['nitrox_request'] ?? 'no',
            'height'  => $row['height_cm'] ?? '',
            'weight'  => $row['weight_kg'] ?? '',
            'dives'   => $row['logged_dives'] ?? '',
            'medical' => $row['medical_flags'] ?? 'all_no',
            'notes'   => $row['gear_notes'] ?? '',
        ];
        $stmt = $pdo->prepare(
            'INSERT OR REPLACE INTO website_leads_crm
             (event_id, received_at, lead_type, lang, guest_name, phone, email, interest_items,
              people, trip_date, hotel, room_no, cert_level, gear_sizes_json, estimated_eur, final_paid_eur,
              status, gclid, fbclid, utm_source, utm_campaign, assigned_to, linked_voucher_no)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,0,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $cid,
            (string) ($row['submitted_at'] ?? gmdate('c')),
            'DIVER_CHECKIN',
            (string) ($row['lang'] ?? 'en'),
            (string) ($row['name'] ?? ''),
            (string) ($row['phone'] ?? ''),
            '',
            'Pre-Arrival Diver Check-In (' . trim((string) (($row['cert_agency'] ?? '') . ' ' . ($row['cert_number'] ?? ''))) . ')',
            max(1, (int) ($row['divers_count'] ?? 1)),
            (string) ($row['arrival_date'] ?? ''),
            (string) ($row['hotel'] ?? ''),
            (string) ($row['room_number'] ?? ''),
            (string) ($row['cert_agency'] ?? ''),
            json_encode($gear, JSON_UNESCAPED_UNICODE) ?: '{}',
            'CHECKIN_READY',
            (string) ($attr['gclid'] ?? ''),
            (string) ($attr['fbclid'] ?? ''),
            (string) ($attr['utm_source'] ?? ''),
            (string) ($attr['utm_campaign'] ?? ''),
            'OPERATIONS',
            (string) ($row['lead_event_id'] ?? ''),
        ]);
    } catch (Throwable $e) {
        error_log('SC_ERP checkin sync warning: ' . $e->getMessage());
    }
}

/**
 * Non-blocking hook called by `handlers/wa_click_beacon.php`.
 */
function sc_erp_ingest_wa_beacon(array $row): void
{
    try {
        $pdo = sc_erp_pdo();
        if (!$pdo) {
            return;
        }
        $stmt = $pdo->prepare(
            'INSERT OR IGNORE INTO website_leads_crm
             (event_id, received_at, lead_type, lang, guest_name, phone, email, interest_items,
              people, trip_date, hotel, room_no, cert_level, gear_sizes_json, estimated_eur, final_paid_eur,
              status, gclid, fbclid, utm_source, utm_campaign, assigned_to, linked_voucher_no)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            (string) ($row['event_id'] ?? ('wa_' . bin2hex(random_bytes(6)))),
            (string) ($row['clicked_at'] ?? gmdate('c')),
            'WHATSAPP_CLICK',
            (string) ($row['lang'] ?? 'en'),
            'WhatsApp Visitor (' . (string) ($row['placement'] ?? 'site') . ')',
            '',
            '',
            (string) (($row['item_name'] ?? '') !== '' ? $row['item_name'] : ($row['placement'] ?? 'WhatsApp Click')),
            max(1, (int) ($row['qty'] ?? 1)),
            (string) ($row['date'] ?? ''),
            (string) ($row['hotel'] ?? ''),
            '',
            '',
            json_encode(['page_url' => $row['page_url'] ?? '', 'kind' => $row['kind'] ?? 'whatsapp_click'], JSON_UNESCAPED_UNICODE) ?: '{}',
            (float) ($row['value_eur'] ?? 0.0),
            'WA_CLICKED',
            (string) ($row['gclid'] ?? ''),
            (string) ($row['fbclid'] ?? ''),
            (string) ($row['utm_source'] ?? ''),
            (string) ($row['utm_campaign'] ?? ''),
            'LARA',
            '',
        ]);
    } catch (Throwable $e) {
        error_log('SC_ERP WA beacon sync warning: ' . $e->getMessage());
    }
}

/**
 * Write an immutable RBAC audit log record.
 */
function sc_erp_audit(PDO $pdo, string $username, string $role, string $action, string $entity, string $entityId = '', array $details = []): void
{
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO audit_logs (created_at, username, role_code, action_type, entity_type, entity_id, details_json)
             VALUES (?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            gmdate('Y-m-d\TH:i:s\Z'),
            $username,
            $role,
            $action,
            $entity,
            $entityId,
            json_encode($details, JSON_UNESCAPED_UNICODE) ?: '{}',
        ]);
    } catch (Throwable $e) {
        // Non-fatal
    }
}

/**
 * Stream a UTF-8 BOM CSV file (opens cleanly in Microsoft Excel with Arabic & EUR/EGP columns).
 *
 * @param list<string> $headers
 * @param list<list<scalar|null>> $rows
 */
function sc_erp_stream_csv(string $filename, array $headers, array $rows): never
{
    $safeName = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $filename) ?: 'sea_college_report.csv';
    if (!str_ends_with(strtolower($safeName), '.csv')) {
        $safeName .= '.csv';
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $safeName . '"');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');

    $out = fopen('php://output', 'wb');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel Arabic support
    fputcsv($out, $headers);
    foreach ($rows as $r) {
        $cleanRow = [];
        foreach ($r as $cell) {
            $s = $cell === null ? '' : (string) $cell;
            if ($s !== '' && preg_match('/^[=+\-@]/', $s) && !is_numeric($s)) {
                $s = "'" . $s;
            }
            $cleanRow[] = $s;
        }
        fputcsv($out, $cleanRow);
    }
    fclose($out);
    exit;
}

/**
 * Build & stream a genuine multi-tab OpenXML `.xlsx` Excel workbook using built-in PHP `ZipArchive`.
 *
 * @param array<string, array{headers: list<string>, rows: list<list<scalar|null>>}> $sheets
 */
function sc_erp_stream_xlsx(string $filename, array $sheets): never
{
    if (!class_exists('ZipArchive')) {
        $first = reset($sheets) ?: ['headers' => [], 'rows' => []];
        sc_erp_stream_csv(preg_replace('/\.xlsx$/i', '.csv', $filename) ?: 'report.csv', $first['headers'], $first['rows']);
    }

    $safeName = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $filename) ?: 'Sea_College_Report.xlsx';
    if (!str_ends_with(strtolower($safeName), '.xlsx')) {
        $safeName .= '.xlsx';
    }

    $tmpFile = tempnam(sys_get_temp_dir(), 'sc_xlsx_');
    $zip = new ZipArchive();
    $zip->open($tmpFile, ZipArchive::OVERWRITE | ZipArchive::CREATE);

    $xmlEsc = static fn(string $s): string => htmlspecialchars(
        preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s) ?? '',
        ENT_XML1 | ENT_QUOTES,
        'UTF-8'
    );
    $colLetter = static function (int $idx): string {
        $s = '';
        $n = $idx + 1;
        while ($n > 0) {
            $m = ($n - 1) % 26;
            $s = chr(65 + $m) . $s;
            $n = intdiv($n - 1, 26);
        }
        return $s;
    };

    $sheetNames = [];
    $sIdx = 1;
    foreach ($sheets as $rawTitle => $spec) {
        $cleanTitle = mb_substr(trim(preg_replace('/[\\\\\/\?\*\[\]:]/', '-', (string) $rawTitle) ?? 'Sheet'), 0, 31) ?: ('Sheet' . $sIdx);
        $sheetNames[$sIdx] = $cleanTitle;

        $allRows = array_merge([$spec['headers']], $spec['rows']);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
             . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
             . '<sheetData>';

        foreach ($allRows as $rZero => $rCells) {
            $rNum = $rZero + 1;
            $styleAttr = $rNum === 1 ? ' s="1"' : '';
            $xml .= '<row r="' . $rNum . '">';
            foreach (array_values($rCells) as $cZero => $val) {
                $ref = $colLetter($cZero) . $rNum;
                if ($val === null || $val === '') {
                    continue;
                }
                if ($rNum > 1 && (is_int($val) || is_float($val) || (is_string($val) && preg_match('/^-?\d+(\.\d+)?$/', $val) && !str_starts_with($val, '0')))) {
                    $xml .= '<c r="' . $ref . '" s="2"><v>' . ((float) $val) . '</v></c>';
                } else {
                    $strVal = (string) $val;
                    if ($rNum > 1 && preg_match('/^[=+\-@]/', $strVal)) {
                        $strVal = "'" . $strVal;
                    }
                    $xml .= '<c r="' . $ref . '" t="inlineStr"' . $styleAttr . '><is><t xml:space="preserve">' . $xmlEsc($strVal) . '</t></is></c>';
                }
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet' . $sIdx . '.xml', $xml);
        $sIdx++;
    }

    // Content Types
    $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
    foreach ($sheetNames as $i => $_) {
        $ct .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
    }
    $ct .= '</Types>';
    $zip->addFromString('[Content_Types].xml', $ct);

    // Root rels
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '</Relationships>');

    // Workbook XML & rels
    $wbXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
           . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
    $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
    foreach ($sheetNames as $i => $title) {
        $wbXml .= '<sheet name="' . $xmlEsc($title) . '" sheetId="' . $i . '" r:id="rId' . $i . '"/>';
        $wbRels .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
    }
    $styleRelId = count($sheetNames) + 1;
    $wbRels .= '<Relationship Id="rId' . $styleRelId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    $wbXml .= '</sheets></workbook>';
    $zip->addFromString('xl/workbook.xml', $wbXml);
    $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

    // Minimal clean Styles (Header navy fill + bold white text, numeric thousands format)
    $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0.00"/></numFmts>'
        . '<fonts count="2">'
        . '<font><sz val="11"/><name val="Calibri"/></font>'
        . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
        . '</fonts>'
        . '<fills count="3">'
        . '<fill><patternFill patternType="none"/></fill>'
        . '<fill><patternFill patternType="gray125"/></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FF0A3D62"/></patternFill></fill>'
        . '</fills>'
        . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="3">'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
        . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
        . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
        . '</cellXfs>'
        . '</styleSheet>';
    $zip->addFromString('xl/styles.xml', $stylesXml);
    $zip->close();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $safeName . '"');
    header('Content-Length: ' . (string) filesize($tmpFile));
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');
    readfile($tmpFile);
    @unlink($tmpFile);
    exit;
}
