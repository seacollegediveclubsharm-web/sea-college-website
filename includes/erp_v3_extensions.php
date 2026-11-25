<?php
/**
 * Sea College Dive Club — ERP v3 Extensions (`includes/erp_v3_extensions.php`)
 * Built collaboratively by:
 *   - Karim (ERP & Mobile Gamification Architect)
 *   - Nadia (Chief Operational Accountant & Reservations-to-Cash Auditor)
 *   - Tarek (CFO & Multi-Horizon Seller Performance / Awards BI)
 *   - Ayman (Full-Stack Integrations & Shareable Seller Brochure Engineer)
 *   - Hana (Visual Creative & Excursion Catalog Custodian)
 *
 * Provides:
 *   1. Idempotent v3 tables (`master_trips_catalog`, `voucher_change_audit_log`, `user_notifications`,
 *      `seller_commission_advances`, `seller_targets_config`) & `reservations_fact` adjustment columns.
 *   2. Unified trip & Sharm excursion catalog seeding + historical sheet alias resolution + Adult/Child cost engine.
 *   3. Bulletproof image path resolver (`sc_erp_resolve_asset_webp`) eliminating broken trip photos.
 *   4. Strict "Actual Executed + 100% Paid" commission wallet engine + mid-month allowance/installment tracker.
 *   5. Multi-horizon Seller Performance BI (Daily, Weekly, Monthly, Yearly, Custom Range) & Best Seller Trophy Awards.
 */
declare(strict_types=1);

/**
 * Resolve any catalog/excursion/operational image path to a verified web path on disk.
 */
function sc_erp_resolve_asset_webp(string $relPath): string
{
    $fallback = '/assets/images/fleet/sea-college-oceano-yacht-01-exterior-nov2026.webp';
    $trim = trim($relPath);
    if ($trim === '') {
        return $fallback;
    }
    $clean = ltrim($trim, '/');
    $cleanNoAssets = (string) preg_replace('#^assets/#i', '', $clean);
    $cleanNoImages = (string) preg_replace('#^(?:assets/)?images/#i', '', $clean);

    $candidates = [
        '/assets/' . $cleanNoAssets,
        '/assets/images/' . $cleanNoImages,
        '/' . $clean,
    ];
    foreach ($candidates as $cand) {
        $webp = (string) preg_replace('/\.(jpe?g|png)$/i', '.webp', $cand);
        if ($webp !== $cand && is_file(ROOT_DIR . $webp)) {
            return $webp;
        }
        if (is_file(ROOT_DIR . $cand)) {
            return $cand;
        }
    }
    return $fallback;
}

/**
 * Ensure all v3 tables, columns, unified master trips, and default target settings exist idempotently.
 */
function sc_erp_ensure_v3_extensions(PDO $pdo): void
{
    $haveResCols = array_flip(array_column($pdo->query('PRAGMA table_info(reservations_fact)')->fetchAll(), 'name'));
    $resAddCols = [
        'unified_trip_code'    => "TEXT NOT NULL DEFAULT ''",
        'adult_unit_eur'       => 'REAL NOT NULL DEFAULT 0',
        'child_unit_eur'       => 'REAL NOT NULL DEFAULT 0',
        'infant_unit_eur'      => 'REAL NOT NULL DEFAULT 0',
        'booked_adults'        => 'INTEGER NOT NULL DEFAULT 0',
        'booked_children'      => 'INTEGER NOT NULL DEFAULT 0',
        'booked_infants'       => 'INTEGER NOT NULL DEFAULT 0',
        'booked_total_egp'     => 'REAL NOT NULL DEFAULT 0',
        'collected_rest_egp'   => 'REAL NOT NULL DEFAULT 0',
        'actual_paid_total_egp'=> 'REAL NOT NULL DEFAULT 0',
        'workflow_stage'       => "TEXT NOT NULL DEFAULT 'CONFIRMED_OPS'",
        'has_modification'     => 'INTEGER NOT NULL DEFAULT 0',
        'modification_summary' => "TEXT NOT NULL DEFAULT ''",
        'list_total_egp'       => 'REAL NOT NULL DEFAULT 0',
        'discount_type'        => "TEXT NOT NULL DEFAULT 'NONE'",
        'discount_value'       => 'REAL NOT NULL DEFAULT 0',
        'discount_currency'    => "TEXT NOT NULL DEFAULT 'EUR'",
        'discount_egp'           => 'REAL NOT NULL DEFAULT 0',
        'discount_reason'        => "TEXT NOT NULL DEFAULT ''",
        'guest_phone'            => "TEXT NOT NULL DEFAULT ''",
        'guest_email'            => "TEXT NOT NULL DEFAULT ''",
        'guest_account_username' => "TEXT NOT NULL DEFAULT ''",
    ];
    foreach ($resAddCols as $col => $ddl) {
        if (!isset($haveResCols[$col])) {
            $pdo->exec("ALTER TABLE reservations_fact ADD COLUMN {$col} {$ddl}");
        }
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS master_trips_catalog (
            trip_code TEXT PRIMARY KEY,
            unified_name_en TEXT NOT NULL,
            unified_name_ar TEXT NOT NULL,
            category_code TEXT NOT NULL,
            supplier_code TEXT NOT NULL DEFAULT 'SEA_COLLEGE',
            supplier_name TEXT NOT NULL DEFAULT 'Sea College Dive Club',
            sale_mode TEXT NOT NULL DEFAULT 'INSHEET',
            comm_rate REAL NOT NULL DEFAULT 0.10,
            adult_sell_eur REAL NOT NULL DEFAULT 0,
            child_sell_eur REAL NOT NULL DEFAULT 0,
            infant_sell_eur REAL NOT NULL DEFAULT 0,
            adult_cost_egp REAL NOT NULL DEFAULT 0,
            child_cost_egp REAL NOT NULL DEFAULT 0,
            infant_cost_egp REAL NOT NULL DEFAULT 0,
            is_website_public INTEGER NOT NULL DEFAULT 0,
            cover_webp TEXT NOT NULL DEFAULT '',
            aliases_json TEXT NOT NULL DEFAULT '[]'
        );
        CREATE TABLE IF NOT EXISTS voucher_change_audit_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            created_at TEXT NOT NULL,
            voucher_no TEXT NOT NULL,
            trip_date TEXT NOT NULL,
            period_month TEXT NOT NULL,
            seller_username TEXT NOT NULL DEFAULT '',
            changed_by_username TEXT NOT NULL,
            changed_by_role TEXT NOT NULL,
            change_type TEXT NOT NULL,
            prev_adults INTEGER NOT NULL DEFAULT 0,
            prev_children INTEGER NOT NULL DEFAULT 0,
            prev_infants INTEGER NOT NULL DEFAULT 0,
            new_adults INTEGER NOT NULL DEFAULT 0,
            new_children INTEGER NOT NULL DEFAULT 0,
            new_infants INTEGER NOT NULL DEFAULT 0,
            prev_total_egp REAL NOT NULL DEFAULT 0,
            new_total_egp REAL NOT NULL DEFAULT 0,
            delta_total_egp REAL NOT NULL DEFAULT 0,
            extra_collected_egp REAL NOT NULL DEFAULT 0,
            prev_comm_egp REAL NOT NULL DEFAULT 0,
            new_comm_egp REAL NOT NULL DEFAULT 0,
            reason_notes TEXT NOT NULL DEFAULT ''
        );
        CREATE INDEX IF NOT EXISTS idx_vcal_voucher ON voucher_change_audit_log(voucher_no, period_month);
        CREATE TABLE IF NOT EXISTS user_notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            created_at TEXT NOT NULL,
            recipient_username TEXT NOT NULL,
            notif_type TEXT NOT NULL,
            voucher_no TEXT NOT NULL DEFAULT '',
            title_en TEXT NOT NULL,
            title_ar TEXT NOT NULL,
            body_en TEXT NOT NULL,
            body_ar TEXT NOT NULL,
            is_read INTEGER NOT NULL DEFAULT 0
        );
        CREATE INDEX IF NOT EXISTS idx_unotif_recip ON user_notifications(recipient_username, is_read, id);
        CREATE TABLE IF NOT EXISTS seller_commission_advances (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            created_at TEXT NOT NULL,
            period_month TEXT NOT NULL,
            tx_date TEXT NOT NULL,
            seller_username TEXT NOT NULL,
            rep_scope TEXT NOT NULL DEFAULT '',
            tx_type TEXT NOT NULL DEFAULT 'ALLOWANCE_ADVANCE',
            amount_egp REAL NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT 'REQUESTED',
            approved_by TEXT NOT NULL DEFAULT '',
            notes TEXT NOT NULL DEFAULT ''
        );
        CREATE INDEX IF NOT EXISTS idx_sca_seller ON seller_commission_advances(seller_username, period_month, status);
        CREATE TABLE IF NOT EXISTS seller_targets_config (
            seller_username TEXT PRIMARY KEY,
            is_target_active INTEGER NOT NULL DEFAULT 1,
            daily_target_egp REAL NOT NULL DEFAULT 15000,
            weekly_target_egp REAL NOT NULL DEFAULT 95000,
            monthly_target_egp REAL NOT NULL DEFAULT 380000,
            monthly_trips_target INTEGER NOT NULL DEFAULT 65,
            is_discount_enabled INTEGER NOT NULL DEFAULT 1,
            max_discount_pct REAL NOT NULL DEFAULT 20,
            max_discount_eur REAL NOT NULL DEFAULT 50,
            updated_by TEXT NOT NULL DEFAULT 'SYSTEM',
            updated_at TEXT NOT NULL DEFAULT ''
        );"
    );

    $haveTgtCols = array_flip(array_column($pdo->query('PRAGMA table_info(seller_targets_config)')->fetchAll(), 'name'));
    $tgtAddCols = [
        'is_discount_enabled' => 'INTEGER NOT NULL DEFAULT 1',
        'max_discount_pct'    => 'REAL NOT NULL DEFAULT 20',
        'max_discount_eur'    => 'REAL NOT NULL DEFAULT 50',
    ];
    foreach ($tgtAddCols as $col => $ddl) {
        if (!isset($haveTgtCols[$col])) {
            $pdo->exec("ALTER TABLE seller_targets_config ADD COLUMN {$col} {$ddl}");
        }
    }

    $pdo->exec(
        "INSERT OR IGNORE INTO seller_targets_config
         (seller_username, is_target_active, daily_target_egp, weekly_target_egp, monthly_target_egp, monthly_trips_target, is_discount_enabled, max_discount_pct, max_discount_eur, updated_by, updated_at)
         VALUES ('__GLOBAL__', 1, 15000, 95000, 380000, 65, 1, 20, 50, 'SYSTEM', '2026-10-01T00:00:00Z')"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS company_inbox_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            seed_key TEXT DEFAULT NULL UNIQUE,
            created_at TEXT NOT NULL,
            sender_username TEXT NOT NULL,
            sender_name_en TEXT NOT NULL,
            sender_name_ar TEXT NOT NULL,
            sender_role_code TEXT NOT NULL,
            sender_title_en TEXT NOT NULL DEFAULT '',
            sender_title_ar TEXT NOT NULL DEFAULT '',
            sender_avatar_url TEXT NOT NULL DEFAULT '',
            msg_type TEXT NOT NULL DEFAULT 'ANNOUNCEMENT',
            priority TEXT NOT NULL DEFAULT 'NORMAL',
            audience_type TEXT NOT NULL DEFAULT 'ALL',
            audience_target TEXT NOT NULL DEFAULT '',
            spotlight_username TEXT NOT NULL DEFAULT '',
            badge_icon TEXT NOT NULL DEFAULT '📣',
            title_en TEXT NOT NULL,
            title_ar TEXT NOT NULL,
            body_en TEXT NOT NULL,
            body_ar TEXT NOT NULL,
            action_tab TEXT NOT NULL DEFAULT '',
            is_pinned INTEGER NOT NULL DEFAULT 0,
            is_deleted INTEGER NOT NULL DEFAULT 0,
            deleted_by TEXT NOT NULL DEFAULT '',
            deleted_at TEXT NOT NULL DEFAULT ''
        );
        CREATE INDEX IF NOT EXISTS idx_cim_active_pin ON company_inbox_messages(is_deleted, is_pinned DESC, id DESC);
        CREATE INDEX IF NOT EXISTS idx_cim_audience ON company_inbox_messages(is_deleted, audience_type, audience_target);
        CREATE TABLE IF NOT EXISTS company_inbox_reads (
            message_id INTEGER NOT NULL,
            username TEXT NOT NULL,
            read_at TEXT NOT NULL DEFAULT '',
            reaction_emoji TEXT NOT NULL DEFAULT '',
            reacted_at TEXT NOT NULL DEFAULT '',
            PRIMARY KEY (message_id, username),
            FOREIGN KEY (message_id) REFERENCES company_inbox_messages(id) ON DELETE CASCADE
        );
        CREATE INDEX IF NOT EXISTS idx_cir_user_read ON company_inbox_reads(username, read_at);
        INSERT OR IGNORE INTO roles (role_code, name_en, name_ar, department, hierarchy_level, default_dashboard, description_en, description_ar)
        VALUES ('GUEST_CUSTOMER', 'Verified Club Guest', 'عميل سي كوليدج المعتمد', 'GUEST_PORTAL', 5, 'guest_book_trips', 'Verified real Sea College customer eligible to view bookings and book future trips', 'عميل حقيقي معتمد لدى سي كوليدج يمكنه متابعة حجوزاته وحجز رحلاته القادمة');
        INSERT OR IGNORE INTO permissions (perm_code, module, name_en, name_ar)
        VALUES ('guest.book_future_trips', 'GUEST_PORTAL', 'Book Future Trips as Verified Guest', 'حجز الرحلات القادمة كعميل معتمد');
        INSERT OR IGNORE INTO role_permissions (role_code, perm_code)
        VALUES ('GUEST_CUSTOMER', 'guest.book_future_trips');
        CREATE TABLE IF NOT EXISTS guest_customer_accounts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            guest_name TEXT NOT NULL,
            guest_phone TEXT NOT NULL DEFAULT '',
            guest_phone_norm TEXT NOT NULL DEFAULT '',
            guest_email TEXT NOT NULL DEFAULT '',
            nationality TEXT NOT NULL DEFAULT '',
            preferred_lang TEXT NOT NULL DEFAULT 'en',
            hotel_name TEXT NOT NULL DEFAULT '',
            room_no TEXT NOT NULL DEFAULT '',
            referred_by_seller TEXT NOT NULL DEFAULT '',
            first_voucher_no TEXT NOT NULL DEFAULT '',
            latest_voucher_no TEXT NOT NULL DEFAULT '',
            latest_trip_name TEXT NOT NULL DEFAULT '',
            latest_trip_date TEXT NOT NULL DEFAULT '',
            latest_booking_status TEXT NOT NULL DEFAULT 'CONFIRMED_UNPAID',
            total_bookings_count INTEGER NOT NULL DEFAULT 1,
            cancelled_bookings_count INTEGER NOT NULL DEFAULT 0,
            lifetime_booked_egp REAL NOT NULL DEFAULT 0,
            lifetime_paid_egp REAL NOT NULL DEFAULT 0,
            credentials_email_sent INTEGER NOT NULL DEFAULT 0,
            credentials_emailed_at TEXT NOT NULL DEFAULT '',
            marketing_opt_in INTEGER NOT NULL DEFAULT 1
        );
        CREATE INDEX IF NOT EXISTS idx_gca_email ON guest_customer_accounts(guest_email);
        CREATE INDEX IF NOT EXISTS idx_gca_phone ON guest_customer_accounts(guest_phone_norm);
        CREATE INDEX IF NOT EXISTS idx_gca_status ON guest_customer_accounts(latest_booking_status, id DESC);
        CREATE TABLE IF NOT EXISTS guest_email_dispatches (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sent_at TEXT NOT NULL,
            guest_username TEXT NOT NULL,
            recipient_email TEXT NOT NULL,
            voucher_no TEXT NOT NULL DEFAULT '',
            subject TEXT NOT NULL,
            body_preview TEXT NOT NULL,
            transport_ok INTEGER NOT NULL DEFAULT 1
        );
        CREATE INDEX IF NOT EXISTS idx_ged_user ON guest_email_dispatches(guest_username, id DESC);"
    );

    sc_erp_seed_default_inbox_messages($pdo);

    $tripCnt = (int) $pdo->query('SELECT COUNT(*) FROM master_trips_catalog')->fetchColumn();
    if ($tripCnt < 30) {
        sc_erp_sync_master_trips_catalog($pdo);
    }
}

/**
 * Populate `master_trips_catalog` from `data/app_excursions_catalog.php`, `data/operational_skus.php`, and `data/catalog.php`.
 */
function sc_erp_sync_master_trips_catalog(PDO $pdo): void
{
    $fx = sc_erp_fx_rates($pdo);
    $eurRate = $fx['EUR'] ?? 57.0;

    $stmt = $pdo->prepare(
        'INSERT OR REPLACE INTO master_trips_catalog
         (trip_code, unified_name_en, unified_name_ar, category_code, supplier_code, supplier_name,
          sale_mode, comm_rate, adult_sell_eur, child_sell_eur, infant_sell_eur,
          adult_cost_egp, child_cost_egp, infant_cost_egp, is_website_public, cover_webp, aliases_json)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    );

    $startedTx = !$pdo->inTransaction();
    if ($startedTx) {
        $pdo->beginTransaction();
    }
    try {
        if (is_file(ROOT_DIR . '/data/app_excursions_catalog.php')) {
            $excs = require ROOT_DIR . '/data/app_excursions_catalog.php';
            foreach ($excs as $ex) {
                $stmt->execute([
                    (string) $ex['id'],
                    (string) ($ex['names']['en'] ?? $ex['id']),
                    (string) ($ex['names']['ar'] ?? $ex['names']['en'] ?? $ex['id']),
                    (string) ($ex['category_group'] ?? 'excursions'),
                    (string) ($ex['supplier_code'] ?? 'OUTSIDE_VENDOR'),
                    (string) ($ex['supplier_name'] ?? 'Sharm Excursion Partner'),
                    (string) ($ex['default_sale_mode'] ?? 'OUT_SIDE'),
                    (float) ($ex['default_comm_pct'] ?? 0.25),
                    (float) ($ex['price_eur'] ?? 30),
                    (float) ($ex['child_price_eur'] ?? round(((float) ($ex['price_eur'] ?? 30)) * 0.5, 2)),
                    (float) ($ex['infant_price_eur'] ?? 0),
                    (float) ($ex['adult_cost_egp'] ?? 500),
                    (float) ($ex['child_cost_egp'] ?? 250),
                    (float) ($ex['infant_cost_egp'] ?? 0),
                    0,
                    sc_erp_resolve_asset_webp((string) ($ex['cover_webp'] ?? '')),
                    json_encode($ex['aliases'] ?? [], JSON_UNESCAPED_UNICODE) ?: '[]',
                ]);
            }
        }

        if (is_file(ROOT_DIR . '/data/operational_skus.php')) {
            $ops = require ROOT_DIR . '/data/operational_skus.php';
            foreach ($ops as $sk) {
                $pEur = (float) ($sk['price_eur'] ?? 30);
                $cEur = isset($sk['child_price_eur']) && $sk['child_price_eur'] !== null ? (float) $sk['child_price_eur'] : $pEur;
                $aCost = (float) ($sk['default_supplier_cost_egp'] ?? 0);
                $stmt->execute([
                    (string) $sk['id'],
                    (string) ($sk['names']['en'] ?? $sk['id']),
                    (string) ($sk['names']['ar'] ?? $sk['names']['en'] ?? $sk['id']),
                    (string) ($sk['category_group'] ?? 'operational'),
                    ((string) ($sk['default_sale_mode'] ?? 'INSHEET')) === 'OUT_SIDE' ? 'AP_WATERSPORTS_SAFARI' : 'SEA_COLLEGE',
                    ((string) ($sk['default_sale_mode'] ?? 'INSHEET')) === 'OUT_SIDE' ? 'Contracted Watersports / Safari Partner' : 'Sea College Dive Club',
                    (string) ($sk['default_sale_mode'] ?? 'INSHEET'),
                    (float) ($sk['default_comm_pct'] ?? 0.10),
                    $pEur,
                    $cEur,
                    0.0,
                    $aCost,
                    round($aCost * 0.5, 2),
                    0.0,
                    0,
                    sc_erp_resolve_asset_webp((string) ($sk['cover_webp'] ?? '')),
                    '[]',
                ]);
            }
        }

        if (is_file(ROOT_DIR . '/data/catalog.php')) {
            $prevLg = $GLOBALS['SC_LANG'] ?? 'en';
            $GLOBALS['SC_LANG'] = 'en';
            $cat = require ROOT_DIR . '/data/catalog.php';
            $GLOBALS['SC_LANG'] = $prevLg;
            foreach ($cat as $catKey => $catMeta) {
                foreach (($catMeta['groups'] ?? []) as $grp) {
                    foreach (($grp['items'] ?? []) as $it) {
                        $id = (string) ($it['id'] ?? '');
                        if ($id === '' || ($it['price'] ?? null) === null) {
                            continue;
                        }
                        $pEur = (float) $it['price'];
                        $isThistlegorm = str_contains($id, 'thistlegorm');
                        $supCode = $isThistlegorm ? 'AP_THISTLEGORM' : 'SEA_COLLEGE';
                        $supName = $isThistlegorm ? 'SS Thistlegorm Marine Charter' : 'Sea College PADI 5* Dive Center';
                        $aCost = $isThistlegorm ? round(100.0 * $eurRate, 0) : round($pEur * $eurRate * 0.35, 0);
                        $cEur  = str_contains($catKey, 'snork') ? round($pEur * 0.5, 0) : $pEur;
                        $stmt->execute([
                            $id,
                            (string) ($it['name'] ?? $id),
                            (string) ($it['name_ar'] ?? $it['name'] ?? $id),
                            (string) $catKey,
                            $supCode,
                            $supName,
                            'INSHEET',
                            0.10,
                            $pEur,
                            $cEur,
                            0.0,
                            $aCost,
                            round($aCost * 0.5, 0),
                            0.0,
                            1,
                            sc_erp_resolve_asset_webp((string) ($it['img'] ?? '')),
                            '[]',
                        ]);
                    }
                }
            }
        }
        if ($startedTx && $pdo->inTransaction()) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($startedTx && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('SC_ERP master_trips_catalog sync warning: ' . $e->getMessage());
    }
}

/**
 * Resolve any product code OR raw reservation trip name to its unified `master_trips_catalog` row.
 */
function sc_erp_lookup_unified_trip(PDO $pdo, string $codeOrName): ?array
{
    $needle = trim($codeOrName);
    if ($needle === '') {
        return null;
    }
    $st = $pdo->prepare('SELECT * FROM master_trips_catalog WHERE LOWER(trip_code) = LOWER(?) OR LOWER(unified_name_en) = LOWER(?) LIMIT 1');
    $st->execute([$needle, $needle]);
    $direct = $st->fetch();
    if ($direct) {
        return $direct;
    }

    $up = strtoupper((string) preg_replace('/\s+/', ' ', $needle));
    $all = $pdo->query('SELECT * FROM master_trips_catalog')->fetchAll();
    foreach ($all as $row) {
        $aliases = json_decode((string) ($row['aliases_json'] ?? '[]'), true);
        if (is_array($aliases)) {
            foreach ($aliases as $al) {
                $alUp = strtoupper(trim((string) $al));
                if ($alUp !== '' && ($up === $alUp || str_starts_with($up, $alUp))) {
                    return $row;
                }
            }
        }
    }
    // Keyword heuristic fallback for historical spreadsheet variants
    $kwMap = [
        'WHITE ISLAND' => 'exc-white-island-yacht',
        'TIRAN'        => 'exc-tiran-island-yacht',
        'DINNER'       => 'exc-romantic-dinner-cruise',
        'GRAND MUSEUM' => 'exc-cairo-grand-museum-bus',
        'CAIRO BY PLA' => 'exc-cairo-by-plane',
        'CAIRO FLY'    => 'exc-cairo-by-plane',
        'CAIRO'        => 'exc-cairo-by-bus',
        'LUXOR'        => 'exc-luxor-by-plane',
        'MOUSA'        => 'exc-mount-moses-st-catherine',
        'MOUSSA'       => 'exc-mount-moses-st-catherine',
        'CATH'         => 'exc-mount-moses-st-catherine',
        'CATR'         => 'exc-mount-moses-st-catherine',
        'CANYON'       => 'exc-dahab-canyon-bluehole',
        'DAHAB'        => 'exc-dahab-canyon-bluehole',
        'R M BY BUS'   => 'exc-ras-mohammed-bus',
        'R.M'          => 'exc-ras-mohammed-bus',
        'SWIM'         => 'exc-dolphin-swim',
        'DOLPH'        => 'exc-dolphin-show',
        'SUB'          => 'exc-royal-submarine',
        'GLASS'        => 'exc-glass-bottom-boat',
        'HORSE'        => 'exc-horse-riding-beach',
        'HOURS'        => 'exc-horse-riding-beach',
        'BUGGY F'      => 'exc-safari-family-buggy',
        'BAGGY F'      => 'exc-safari-family-buggy',
        'BUGGY'        => 'ops-safari-dune-buggy',
        'BAGGY'        => 'ops-safari-dune-buggy',
        'MOTO 1D'      => 'exc-safari-quad-double',
        '1D MOTO'      => 'exc-safari-quad-double',
        'MOTO'         => 'exc-safari-quad-single',
        'SAFAR'        => 'exc-safari-vip-bedouin-dinner',
        'PARAS'        => 'ops-parasailing-double',
        'PARAC'        => 'ops-parasailing-double',
        'BANAN'        => 'ops-banana-boat',
        'TUBE'         => 'ops-tube-sofa',
        'TUBA'         => 'ops-tube-sofa',
        'ZODIC'        => 'ops-zodiac-youana-1h',
        'SPEED'        => 'ops-zodiac-youana-1h',
    ];
    foreach ($kwMap as $kw => $targetCode) {
        if (str_contains($up, $kw)) {
            $st2 = $pdo->prepare('SELECT * FROM master_trips_catalog WHERE trip_code = ? LIMIT 1');
            $st2->execute([$targetCode]);
            $m = $st2->fetch();
            if ($m) {
                return $m;
            }
        }
    }
    return null;
}

/**
 * Compute exact catalog selling price (EUR & EGP), Adult/Child vendor cost (EGP), and Seller Commission
 * using Sea College's real accounting spreadsheet formula:
 *   - INSHEET / WEBSITE_QR: 10% * Actual_Paid_Gross_EGP
 *   - OUT_SIDE:             25% * MAX(0, Actual_Paid_Gross_EGP - (Adults*AdultCost + Children*ChildCost + Infants*InfantCost))
 */
function sc_erp_compute_trip_financials(?array $trip, int $adults, int $children, int $infants, array $fx, ?float $overrideGrossEgp = null, ?string $overrideMode = null): array
{
    $eurRate = $fx['EUR'] ?? 57.0;
    $adlEur  = $trip ? (float) $trip['adult_sell_eur'] : 30.0;
    $chdEur  = $trip ? (float) $trip['child_sell_eur'] : 15.0;
    $infEur  = $trip ? (float) $trip['infant_sell_eur'] : 0.0;

    $adlCost = $trip ? (float) $trip['adult_cost_egp'] : 0.0;
    $chdCost = $trip ? (float) $trip['child_cost_egp'] : 0.0;
    $infCost = $trip ? (float) $trip['infant_cost_egp'] : 0.0;

    $sellEur = round($adults * $adlEur + $children * $chdEur + $infants * $infEur, 2);
    $sellEgp = $overrideGrossEgp !== null && $overrideGrossEgp >= 0
        ? round($overrideGrossEgp, 2)
        : round($sellEur * $eurRate, 2);

    $supplierCostEgp = round($adults * $adlCost + $children * $chdCost + $infants * $infCost, 2);
    $mode = strtoupper(trim($overrideMode ?: ($trip ? (string) $trip['sale_mode'] : 'INSHEET')));
    if ($mode === 'OUTSIDE') {
        $mode = 'OUT_SIDE';
    }

    if ($mode === 'CENTER') {
        $commRate = 0.0;
        $commBase = 0.0;
        $commEgp  = 0.0;
    } elseif ($mode === 'OUT_SIDE') {
        $commRate = 0.25;
        $commBase = round(max(0.0, $sellEgp - $supplierCostEgp), 2);
        $commEgp  = round($commBase * $commRate, 2);
    } else {
        $commRate = 0.10;
        $commBase = $sellEgp;
        $commEgp  = round($commBase * $commRate, 2);
    }

    return [
        'adult_unit_eur'          => $adlEur,
        'child_unit_eur'          => $chdEur,
        'infant_unit_eur'         => $infEur,
        'catalog_total_eur'       => $sellEur,
        'gross_total_egp'         => $sellEgp,
        'supplier_cost_egp'       => $supplierCostEgp,
        'sale_mode'               => $mode,
        'commission_rate'         => $commRate,
        'commissionable_base_egp' => $commBase,
        'commission_egp'          => $commEgp,
        'supplier_code'           => $trip ? (string) $trip['supplier_code'] : 'SEA_COLLEGE',
        'supplier_name'           => $trip ? (string) $trip['supplier_name'] : 'Sea College Dive Club',
    ];
}

/**
 * Push a real-time in-app notification to a user (`fady.sales`) or role (`ROLE:OPERATIONS_MANAGER`, `ROLE:CHIEF_ACCOUNTANT`, `ROLE:OWNER_ADMIN`).
 */
function sc_erp_push_notification(
    PDO $pdo,
    string $recipient,
    string $notifType,
    string $voucherNo,
    string $titleEn,
    string $titleAr,
    string $bodyEn,
    string $bodyAr
): void {
    try {
        $st = $pdo->prepare(
            'INSERT INTO user_notifications
             (created_at, recipient_username, notif_type, voucher_no, title_en, title_ar, body_en, body_ar, is_read)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)'
        );
        $st->execute([
            gmdate('Y-m-d\TH:i:s\Z'),
            trim($recipient),
            $notifType,
            $voucherNo,
            $titleEn,
            $titleAr,
            $bodyEn,
            $bodyAr,
        ]);
    } catch (Throwable $e) {
        // Non-fatal
    }
}

/**
 * Fetch target settings for a seller (falling back to `__GLOBAL__`).
 */
function sc_erp_get_seller_target(PDO $pdo, string $sellerUsername): array
{
    $st = $pdo->prepare('SELECT * FROM seller_targets_config WHERE LOWER(seller_username) = LOWER(?) LIMIT 1');
    $st->execute([$sellerUsername]);
    $row = $st->fetch();

    $gSt = $pdo->query("SELECT * FROM seller_targets_config WHERE seller_username = '__GLOBAL__' LIMIT 1");
    $glob = $gSt ? ($gSt->fetch() ?: []) : [];

    $isActive = isset($glob['is_target_active']) ? (int) $glob['is_target_active'] : 1;
    if ($row && isset($row['is_target_active'])) {
        // Global switch controls company-wide visibility; when global is active, per-seller row overrides numbers
        $isActive = $isActive === 1 ? (int) $row['is_target_active'] : 0;
    }

    return [
        'is_active'            => $isActive === 1,
        'daily_target_egp'     => (float) ($row['daily_target_egp'] ?? $glob['daily_target_egp'] ?? 15000.0),
        'weekly_target_egp'    => (float) ($row['weekly_target_egp'] ?? $glob['weekly_target_egp'] ?? 95000.0),
        'monthly_target_egp'   => (float) ($row['monthly_target_egp'] ?? $glob['monthly_target_egp'] ?? 380000.0),
        'monthly_trips_target' => (int) ($row['monthly_trips_target'] ?? $glob['monthly_trips_target'] ?? 65),
    ];
}

/**
 * Compute Best Seller Champions across Daily, Weekly, Monthly & Yearly horizons from executed reservations & commissions.
 * Uses latest operational anchor date if today has zero rows so awards & leaderboards always reflect active operations.
 */
function sc_erp_compute_best_seller_awards(PDO $pdo, ?string $anchorDate = null): array
{
    $today = $anchorDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $anchorDate) ? $anchorDate : gmdate('Y-m-d');
    $cntToday = (int) $pdo->query("SELECT COUNT(*) FROM reservations_fact WHERE trip_date = " . $pdo->quote($today) . " AND booking_state = 'EXECUTED' AND seller_type = 'HOTEL_SELLER'")->fetchColumn();
    if ($cntToday === 0) {
        $latest = (string) $pdo->query("SELECT COALESCE(MAX(trip_date), '2026-10-31') FROM reservations_fact WHERE booking_state = 'EXECUTED' AND seller_type = 'HOTEL_SELLER'")->fetchColumn();
        if ($latest !== '') {
            $today = $latest;
        }
    }

    $dtObj     = new DateTimeImmutable($today);
    $weekStart = $dtObj->modify('-6 days')->format('Y-m-d');
    $monthKey  = substr($today, 0, 7);
    $yearKey   = substr($today, 0, 4);

    $queryTop = static function (PDO $db, string $whereSql, array $params): ?array {
        $sql = "SELECT u.username, u.display_name_en, u.display_name_ar, u.rep_scope, u.branch_scope, u.avatar_url,
                       COUNT(r.id) AS trips_count,
                       COALESCE(SUM(r.total_pax), 0) AS total_pax,
                       ROUND(COALESCE(SUM(CASE WHEN r.actual_paid_total_egp > 0 THEN r.actual_paid_total_egp ELSE r.estimated_revenue_egp END), 0), 2) AS actual_sales_egp,
                       ROUND(COALESCE(SUM(r.seller_commission_egp), 0), 2) AS actual_comm_egp
                FROM reservations_fact r
                JOIN users u ON (LOWER(u.username) = LOWER(r.seller_username) OR UPPER(u.rep_scope) = UPPER(r.sales_rep))
                WHERE u.role_code = 'SALES_REP'
                  AND r.booking_state = 'EXECUTED'
                  AND {$whereSql}
                GROUP BY u.username
                ORDER BY actual_sales_egp DESC, trips_count DESC
                LIMIT 1";
        $st = $db->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();
        return $row ?: null;
    };

    return [
        'anchor_date'   => $today,
        'week_from'     => $weekStart,
        'month_key'     => $monthKey,
        'year_key'      => $yearKey,
        'daily_best'    => $queryTop($pdo, 'r.trip_date = ?', [$today]),
        'weekly_best'   => $queryTop($pdo, 'r.trip_date BETWEEN ? AND ?', [$weekStart, $today]),
        'monthly_best'  => $queryTop($pdo, 'r.period_month = ?', [$monthKey]),
        'yearly_best'   => $queryTop($pdo, 'r.period_year = ?', [$yearKey]),
    ];
}

/**
 * Fetch seller discount policy (`is_enabled`, `max_discount_pct`, `max_discount_eur`), falling back to `__GLOBAL__`.
 */
function sc_erp_get_seller_discount_policy(PDO $pdo, string $sellerUsername): array
{
    $st = $pdo->prepare('SELECT * FROM seller_targets_config WHERE LOWER(seller_username) = LOWER(?) LIMIT 1');
    $st->execute([trim($sellerUsername)]);
    $row = $st->fetch();

    $gSt  = $pdo->query("SELECT * FROM seller_targets_config WHERE seller_username = '__GLOBAL__' LIMIT 1");
    $glob = $gSt ? ($gSt->fetch() ?: []) : [];

    $globEnabled = isset($glob['is_discount_enabled']) ? (int) $glob['is_discount_enabled'] : 1;
    $isEnabled   = $globEnabled === 1
        ? (isset($row['is_discount_enabled']) ? ((int) $row['is_discount_enabled'] === 1) : true)
        : false;

    $maxPct = (float) ($row['max_discount_pct'] ?? $glob['max_discount_pct'] ?? 20.0);
    $maxEur = (float) ($row['max_discount_eur'] ?? $glob['max_discount_eur'] ?? 50.0);

    return [
        'is_enabled'       => $isEnabled,
        'max_discount_pct' => max(0.0, min(80.0, $maxPct)),
        'max_discount_eur' => max(0.0, min(1000.0, $maxEur)),
    ];
}

/**
 * Calculate validated special discount by Percentage (`PERCENT`) or Fixed Amount (`AMOUNT`),
 * enforcing:
 *   1. Seller discount policy caps (`is_enabled`, `max_discount_pct`, `max_discount_eur`) unless `$isOwnerOverride`.
 *   2. Strict vendor-cost floor (`net_total_egp >= $supplierCostEgp`) so outside supplier trips NEVER go negative.
 *   3. 50% sanity cap on fixed-amount seller discounts on in-house (`INSHEET`) trips unless `$isOwnerOverride`.
 */
function sc_erp_calculate_discount(
    float $listTotalEgp,
    float $supplierCostEgp,
    string $discType,
    float $discVal,
    string $discCurr,
    array $fx,
    array $policy,
    bool $isOwnerOverride = false
): array {
    $listTotalEgp    = round(max(0.0, $listTotalEgp), 2);
    $supplierCostEgp = round(max(0.0, $supplierCostEgp), 2);
    $fxEur           = max(1.0, (float) ($fx['EUR'] ?? 57.0));

    $rawType = strtoupper(trim($discType));
    $normType = match ($rawType) {
        'PERCENT', 'PCT', 'PERCENTAGE', '%' => 'PERCENT',
        'AMOUNT', 'FIXED', 'CASH', 'VALUE'  => 'AMOUNT',
        default                             => 'NONE',
    };

    $curr = strtoupper(trim($discCurr));
    if (!isset($fx[$curr])) {
        $curr = 'EUR';
    }
    $currRate = max(1.0, (float) ($fx[$curr] ?? $fxEur));

    $enabled = $isOwnerOverride || !empty($policy['is_enabled']);
    if ($listTotalEgp <= 0.0 || $normType === 'NONE' || $discVal <= 0.0 || !$enabled) {
        return [
            'list_total_egp'     => $listTotalEgp,
            'discount_type'      => 'NONE',
            'discount_value'     => 0.0,
            'discount_currency'  => $curr,
            'discount_egp'       => 0.0,
            'discount_eur_equiv' => 0.0,
            'effective_pct'      => 0.0,
            'net_total_egp'      => $listTotalEgp,
            'was_clamped'        => ($normType !== 'NONE' && $discVal > 0.0 && !$enabled),
        ];
    }

    $wasClamped = false;
    $maxRoomAboveVendor = round(max(0.0, $listTotalEgp - $supplierCostEgp), 2);

    if ($normType === 'PERCENT') {
        $maxPct = $isOwnerOverride ? 100.0 : max(0.0, (float) ($policy['max_discount_pct'] ?? 20.0));
        $appliedPct = min($discVal, $maxPct);
        if ($discVal > $maxPct + 0.001) {
            $wasClamped = true;
        }
        $discEgp = round($listTotalEgp * ($appliedPct / 100.0), 2);
        if ($discEgp > $maxRoomAboveVendor) {
            $discEgp = $maxRoomAboveVendor;
            $appliedPct = $listTotalEgp > 0 ? round(($discEgp / $listTotalEgp) * 100.0, 2) : 0.0;
            $wasClamped = true;
        }
        $appliedVal = round($appliedPct, 2);
    } else {
        $reqEgp = round($discVal * $currRate, 2);
        $maxEurCapEgp = $isOwnerOverride
            ? $listTotalEgp
            : round(max(0.0, (float) ($policy['max_discount_eur'] ?? 50.0)) * $fxEur, 2);
        $sanityHalfCapEgp = $isOwnerOverride ? $listTotalEgp : round($listTotalEgp * 0.50, 2);
        $ceilEgp = min($maxEurCapEgp, $sanityHalfCapEgp, $maxRoomAboveVendor);
        $discEgp = round(min($reqEgp, max(0.0, $ceilEgp)), 2);
        if ($reqEgp > $discEgp + 0.05) {
            $wasClamped = true;
        }
        $appliedVal = round($discEgp / $currRate, 2);
    }

    if ($discEgp <= 0.0) {
        return [
            'list_total_egp'     => $listTotalEgp,
            'discount_type'      => 'NONE',
            'discount_value'     => 0.0,
            'discount_currency'  => $curr,
            'discount_egp'       => 0.0,
            'discount_eur_equiv' => 0.0,
            'effective_pct'      => 0.0,
            'net_total_egp'      => $listTotalEgp,
            'was_clamped'        => $wasClamped,
        ];
    }

    $netEgp = round(max($supplierCostEgp, $listTotalEgp - $discEgp), 2);
    $actualDiscEgp = round(max(0.0, $listTotalEgp - $netEgp), 2);

    return [
        'list_total_egp'     => $listTotalEgp,
        'discount_type'      => $normType,
        'discount_value'     => $appliedVal,
        'discount_currency'  => $curr,
        'discount_egp'       => $actualDiscEgp,
        'discount_eur_equiv' => round($actualDiscEgp / $fxEur, 2),
        'effective_pct'      => $listTotalEgp > 0 ? round(($actualDiscEgp / $listTotalEgp) * 100.0, 2) : 0.0,
        'net_total_egp'      => $netEgp,
        'was_clamped'        => $wasClamped,
    ];
}

/**
 * Internal server HMAC key for tamper-proof seller promo links (`/s/<slug>?promo=<token>`).
 */
function sc_erp_promo_secret(): string
{
    return hash('sha256', __DIR__ . '|SC_SELLER_PROMO_HMAC_V31|' . SC_ERP_DB_PATH);
}

/**
 * Create a tamper-proof HMAC-signed promo token for a seller share link or QR code.
 */
function sc_erp_sign_promo_token(
    string $sellerSlug,
    string $tripCode,
    string $discType,
    float $discVal,
    string $discCurr = 'EUR',
    int $ttlDays = 14
): string {
    $normType = strtoupper(trim($discType)) === 'AMOUNT' ? 'AMOUNT' : 'PERCENT';
    $payload = [
        's'   => strtolower(trim($sellerSlug)),
        't'   => trim($tripCode),
        'dt'  => $normType,
        'dv'  => round(max(0.0, $discVal), 2),
        'dc'  => strtoupper(trim($discCurr ?: 'EUR')),
        'exp' => time() + max(1, $ttlDays) * 86400,
    ];
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
    $b64  = rtrim(strtr(base64_encode((string) $json), '+/', '-_'), '=');
    $sig  = substr(hash_hmac('sha256', $b64, sc_erp_promo_secret()), 0, 22);
    return $b64 . '.' . $sig;
}

/**
 * Verify a `?promo=<token>` parameter from a shareable seller link.
 * Returns decoded promo metadata on exact HMAC + expiry + seller/trip match, or `null` if tampered/expired.
 */
function sc_erp_verify_promo_token(string $token, string $expectedSellerSlug = '', string $tripCode = ''): ?array
{
    $token = trim($token);
    if ($token === '' || !str_contains($token, '.')) {
        return null;
    }
    [$b64, $sig] = explode('.', $token, 2);
    if ($b64 === '' || $sig === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $b64) || !preg_match('/^[a-f0-9]{22}$/', $sig)) {
        return null;
    }
    $expectedSig = substr(hash_hmac('sha256', $b64, sc_erp_promo_secret()), 0, 22);
    if (!hash_equals($expectedSig, $sig)) {
        return null;
    }
    $rawJson = base64_decode(strtr($b64, '-_', '+/'), true);
    if ($rawJson === false) {
        return null;
    }
    $data = json_decode($rawJson, true);
    if (!is_array($data)) {
        return null;
    }
    $exp = (int) ($data['exp'] ?? 0);
    if ($exp < time()) {
        return null;
    }
    $tokSeller = strtolower(trim((string) ($data['s'] ?? '')));
    if ($expectedSellerSlug !== '' && $tokSeller !== '' && strtolower(trim($expectedSellerSlug)) !== $tokSeller) {
        return null;
    }
    $tokTrip = trim((string) ($data['t'] ?? ''));
    if ($tripCode !== '' && $tokTrip !== '' && $tokTrip !== '*' && strtolower($tokTrip) !== strtolower(trim($tripCode))) {
        return null;
    }
    $dt = strtoupper((string) ($data['dt'] ?? 'NONE'));
    $dv = round((float) ($data['dv'] ?? 0.0), 2);
    if (!in_array($dt, ['PERCENT', 'AMOUNT'], true) || $dv <= 0.0) {
        return null;
    }
    return [
        'seller_slug'       => $tokSeller,
        'trip_code'         => $tokTrip,
        'discount_type'     => $dt,
        'discount_value'    => $dv,
        'discount_currency' => strtoupper((string) ($data['dc'] ?? 'EUR')),
        'expires_at'        => $exp,
    ];
}

/**
 * Allowed 1-tap celebration / acknowledgment reactions on Company Inbox messages.
 *
 * @return list<string>
 */
function sc_erp_allowed_inbox_reactions(): array
{
    return ['👏', '🎉', '🔥', '❤️', '🏆', '🫡', '👍'];
}

/**
 * Seed default welcome, congratulations, and seller award motivation messages idempotently via `seed_key`.
 */
function sc_erp_seed_default_inbox_messages(PDO $pdo): void
{
    $ins = $pdo->prepare(
        'INSERT OR IGNORE INTO company_inbox_messages
         (seed_key, created_at, sender_username, sender_name_en, sender_name_ar, sender_role_code,
          sender_title_en, sender_title_ar, sender_avatar_url, msg_type, priority,
          audience_type, audience_target, spotlight_username, badge_icon,
          title_en, title_ar, body_en, body_ar, action_tab, is_pinned)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    );

    $seeds = [
        [
            'SEED_WELCOME_ALL_V32',
            '2026-11-24T08:00:00Z',
            'islam.samir',
            'Islam Samir',
            'إسلام سمير',
            'OWNER_ADMIN',
            'Founder & Managing Owner',
            'المؤسس والشريك المالك',
            '/assets/erp/avatars/islam-samir.jpg',
            'ANNOUNCEMENT',
            'IMPORTANT',
            'ALL',
            '',
            '',
            '🌊',
            'Welcome to Sea College Inbox & Fast Easy Mode!',
            'أهلاً بكل أبطال سي كوليدج في صندوق الرسائل والواجهة السريعة!',
            'Every team member now has a direct Inbox for official announcements, recognition awards, and updates from Management. Tap any emoji below to acknowledge in 1 second!',
            'دلوقتي كل فرد في الفريق عنده صندوق رسائل مباشر لاستلام التهاني، المكافآت، وأهم التحديثات من الإدارة بلمسة واحدة وبأسهل طريقة بدون تعقيد!',
            '',
            1,
        ],
        [
            'SEED_CONGRATS_OCT2026_TEAM',
            '2026-11-24T09:15:00Z',
            'eng.sayed',
            'Eng. Sayed Elsharqawy',
            'م. سيد الشرقاوي',
            'OWNER_ADMIN',
            'Co-Founder & Managing Owner',
            'الشريك المؤسس والمدير العام',
            '/assets/erp/avatars/sayed-elsharqawy.jpg',
            'CONGRATULATIONS',
            'CELEBRATION',
            'ALL',
            '',
            'fady.sales',
            '🏆',
            'Congratulations to Our Top Sellers, Boat Crew & Operations!',
            'ألف مبروك لأبطال المبيعات وطاقم المراكب والتشغيل!',
            'Thank you to our entire sales, jetty, boat, and operations family for outstanding guest care and teamwork. Daily, Weekly, Monthly & Yearly Best-Seller Awards are live!',
            'شكراً من القلب لكل رجال المبيعات والسقالة والمراكب والتشغيل على المجهود الرائع مع الضيوف. جوائز نجم اليوم وبطل الأسبوع وملك الشهر شغالة دلوقتي في حساباتكم!',
            'seller_wallet',
            1,
        ],
        [
            'SEED_TARGET_SELLERS_V32',
            '2026-11-24T10:00:00Z',
            'tarek.cfo',
            'Tarek El-Masry',
            'طارق المصري',
            'OWNER_ADMIN',
            'Chief Financial Officer (CFO)',
            'المدير المالي',
            '',
            'TARGET_ALERT',
            'IMPORTANT',
            'ROLE',
            'SALES_REP',
            '',
            '🎯',
            'Easy 1-Tap Booking, WhatsApp Brochure & Mid-Month Cash Allowance',
            'حجز بلمسة واحدة، رابط واتساب لعميلك، وسلفة فورية من عمولتك!',
            'Use the new big buttons to pick language, currency, guest count, or special discount in 1 tap. Share your /s/ link on WhatsApp — when your guest books, you get an instant alert!',
            'استخدم الأزرار الكبيرة الجديدة لاختيار اللغة والعملة وعدد الضيوف أو الخصم بلمسة واحدة بدون كتابة كتير. ابعت رابطك على الواتساب للعميل وأي حجز هينزل باسمك فوراً!',
            'seller_pitch',
            0,
        ],
    ];

    foreach ($seeds as $s) {
        $ins->execute($s);
    }
}

/**
 * Strictly verify whether `$user` is authorized to view `$msg` (prevents horizontal IDOR on DIRECT, ROLE, and BRANCH).
 *
 * @param array<string,mixed> $msg
 * @param array<string,mixed> $user
 * @param list<string>        $perms
 */
function sc_erp_can_user_see_inbox_message(array $msg, array $user, array $perms): bool
{
    if ((int) ($msg['is_deleted'] ?? 0) !== 0) {
        return false;
    }
    $uLower  = strtolower(trim((string) ($user['username'] ?? '')));
    $role    = strtoupper(trim((string) ($user['role_code'] ?? '')));
    $branch  = strtoupper(trim((string) ($user['branch_scope'] ?? 'ALL')));
    $isExec  = in_array('bi.view_executive', $perms, true);

    $senderLower = strtolower(trim((string) ($msg['sender_username'] ?? '')));
    if ($uLower !== '' && $senderLower === $uLower) {
        return true;
    }

    $audType   = strtoupper(trim((string) ($msg['audience_type'] ?? 'ALL')));
    $audTarget = trim((string) ($msg['audience_target'] ?? ''));

    // Real guest accounts never receive internal staff broadcasts unless specifically sent DIRECT to them
    if ($role === 'GUEST_CUSTOMER') {
        return $audType === 'DIRECT' && $uLower !== '' && strtolower($audTarget) === $uLower;
    }

    if ($audType === 'ALL') {
        return true;
    }
    if ($audType === 'DIRECT') {
        return $uLower !== '' && strtolower($audTarget) === $uLower;
    }
    if ($audType === 'ROLE') {
        return $isExec || strtoupper($audTarget) === $role;
    }
    if ($audType === 'BRANCH') {
        if ($role === 'FLEET_PARTNER') {
            return false;
        }
        return $isExec || $branch === 'ALL' || strtoupper($audTarget) === $branch;
    }
    return false;
}

/**
 * Fetch visible Company Inbox messages, unread count, reaction tallies, and top pinned banner for `$user`.
 *
 * @param array<string,mixed> $user
 * @param list<string>        $perms
 * @return array{unread_count:int, pinned_banner:?array<string,mixed>, messages:list<array<string,mixed>>}
 */
function sc_erp_fetch_inbox_for_user(PDO $pdo, array $user, array $perms, string $filter = 'all', int $limit = 50): array
{
    $uLower = strtolower(trim((string) ($user['username'] ?? '')));
    $limit  = max(1, min(100, $limit));
    $filter = strtolower(trim($filter));

    $rawMsgs = $pdo->query(
        'SELECT * FROM company_inbox_messages
         WHERE is_deleted = 0
         ORDER BY is_pinned DESC, id DESC
         LIMIT 200'
    )->fetchAll();

    $visible = [];
    $msgIds  = [];
    $spotUsers = [];
    foreach ($rawMsgs as $m) {
        if (!sc_erp_can_user_see_inbox_message($m, $user, $perms)) {
            continue;
        }
        $visible[] = $m;
        $msgIds[]  = (int) $m['id'];
        $sp = strtolower(trim((string) ($m['spotlight_username'] ?? '')));
        if ($sp !== '') {
            $spotUsers[$sp] = true;
        }
    }

    $readsByMsg = [];
    if ($msgIds) {
        $ph = implode(',', array_fill(0, count($msgIds), '?'));
        $rSt = $pdo->prepare("SELECT message_id, username, read_at, reaction_emoji FROM company_inbox_reads WHERE message_id IN ({$ph})");
        $rSt->execute($msgIds);
        foreach ($rSt->fetchAll() as $rr) {
            $mid = (int) $rr['message_id'];
            if (!isset($readsByMsg[$mid])) {
                $readsByMsg[$mid] = [
                    'read_count' => 0,
                    'my_read_at' => '',
                    'my_reaction'=> '',
                    'reactions'  => [],
                ];
            }
            if ((string) ($rr['read_at'] ?? '') !== '') {
                $readsByMsg[$mid]['read_count']++;
            }
            $em = (string) ($rr['reaction_emoji'] ?? '');
            if ($em !== '') {
                $readsByMsg[$mid]['reactions'][$em] = ($readsByMsg[$mid]['reactions'][$em] ?? 0) + 1;
            }
            if (strtolower((string) ($rr['username'] ?? '')) === $uLower) {
                $readsByMsg[$mid]['my_read_at']  = (string) ($rr['read_at'] ?? '');
                $readsByMsg[$mid]['my_reaction'] = $em;
            }
        }
    }

    $spotMap = [];
    if ($spotUsers) {
        $spList = array_keys($spotUsers);
        $phSp   = implode(',', array_fill(0, count($spList), '?'));
        $spSt   = $pdo->prepare("SELECT username, display_name_en, display_name_ar, job_title_en, job_title_ar, avatar_url, branch_scope FROM users WHERE LOWER(username) IN ({$phSp})");
        $spSt->execute($spList);
        foreach ($spSt->fetchAll() as $su) {
            $spotMap[strtolower((string) $su['username'])] = $su;
        }
    }

    $unreadCount  = 0;
    $pinnedBanner = null;
    $enrichedAll  = [];

    foreach ($visible as $m) {
        $mid  = (int) $m['id'];
        $meta = $readsByMsg[$mid] ?? ['read_count' => 0, 'my_read_at' => '', 'my_reaction' => '', 'reactions' => []];
        $isSender = strtolower((string) ($m['sender_username'] ?? '')) === $uLower;
        $isRead   = $isSender || ($meta['my_read_at'] !== '');
        if (!$isRead) {
            $unreadCount++;
        }
        $reactTotal = 0;
        foreach ($meta['reactions'] as $cnt) {
            $reactTotal += (int) $cnt;
        }
        $spKey = strtolower(trim((string) ($m['spotlight_username'] ?? '')));
        $item = [
            'id'                 => $mid,
            'created_at'         => (string) $m['created_at'],
            'sender_username'    => (string) $m['sender_username'],
            'sender_name_en'     => (string) $m['sender_name_en'],
            'sender_name_ar'     => (string) $m['sender_name_ar'],
            'sender_role_code'   => (string) $m['sender_role_code'],
            'sender_title_en'    => (string) $m['sender_title_en'],
            'sender_title_ar'    => (string) $m['sender_title_ar'],
            'sender_avatar_url'  => (string) $m['sender_avatar_url'],
            'msg_type'           => (string) $m['msg_type'],
            'priority'           => (string) $m['priority'],
            'audience_type'      => (string) $m['audience_type'],
            'audience_target'    => (string) $m['audience_target'],
            'spotlight_username' => (string) $m['spotlight_username'],
            'spotlight_user'     => $spKey !== '' && isset($spotMap[$spKey]) ? $spotMap[$spKey] : null,
            'badge_icon'         => (string) $m['badge_icon'],
            'title_en'           => (string) $m['title_en'],
            'title_ar'           => (string) $m['title_ar'],
            'body_en'            => (string) $m['body_en'],
            'body_ar'            => (string) $m['body_ar'],
            'action_tab'         => (string) $m['action_tab'],
            'is_pinned'          => ((int) $m['is_pinned']) === 1,
            'is_read'            => $isRead,
            'read_at'            => $meta['my_read_at'],
            'my_reaction'        => $meta['my_reaction'],
            'reactions_summary'  => $meta['reactions'],
            'reactions_total'    => $reactTotal,
            'read_count'         => (int) $meta['read_count'],
        ];
        if ($pinnedBanner === null && $item['is_pinned']) {
            $pinnedBanner = $item;
        }
        $enrichedAll[] = $item;
    }

    $filteredOut = [];
    foreach ($enrichedAll as $it) {
        if ($filter === 'unread' && $it['is_read']) {
            continue;
        }
        if ($filter === 'pinned' && !$it['is_pinned']) {
            continue;
        }
        if ($filter === 'congratulations' && $it['msg_type'] !== 'CONGRATULATIONS') {
            continue;
        }
        if ($filter === 'target_alerts' && $it['msg_type'] !== 'TARGET_ALERT') {
            continue;
        }
        if ($filter === 'direct' && $it['audience_type'] !== 'DIRECT') {
            continue;
        }
        if ($filter === 'sent' && strtolower($it['sender_username']) !== $uLower) {
            continue;
        }
        $filteredOut[] = $it;
        if (count($filteredOut) >= $limit) {
            break;
        }
    }

    return [
        'unread_count'  => $unreadCount,
        'pinned_banner' => $pinnedBanner,
        'messages'      => $filteredOut,
    ];
}

/**
 * Normalize phone number to digits (minimum 7 digits for deduplication).
 */
function sc_erp_normalize_guest_phone(string $phone): string
{
    $digits = (string) preg_replace('/\D+/', '', $phone);
    if (str_starts_with($digits, '00')) {
        $digits = substr($digits, 2);
    }
    return strlen($digits) >= 7 ? $digits : '';
}

/**
 * Generate a readable, unambiguous initial password for verified guests (hashed with bcrypt in DB).
 */
function sc_erp_generate_guest_password(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $len = strlen($alphabet);
    $out = 'SC-';
    for ($i = 0; $i < 8; $i++) {
        $out .= $alphabet[random_int(0, $len - 1)];
    }
    return $out;
}

/**
 * Extract guest display name + embedded phone from `divers_names` when stored as `Name (+phone)`.
 *
 * @return array{name:string, phone:string}
 */
function sc_erp_split_guest_name_phone(string $rawDiversNames, string $explicitPhone = ''): array
{
    $raw = trim($rawDiversNames);
    $foundPhone = trim($explicitPhone);
    if (preg_match('/^(.*?)\s*\(([+\d\s\-]{6,32})\)\s*$/u', $raw, $m)) {
        $cleanName = trim($m[1]);
        if ($foundPhone === '') {
            $foundPhone = trim($m[2]);
        }
        return ['name' => $cleanName !== '' ? $cleanName : $raw, 'phone' => $foundPhone];
    }
    return ['name' => $raw, 'phone' => $foundPhone];
}

/**
 * Send welcome email with VIP App login credentials to a verified Sea College guest, and log the dispatch.
 *
 * @param array<string,mixed> $guestAcct
 */
function sc_erp_send_guest_credentials_email(
    PDO $pdo,
    array $guestAcct,
    string $plainPassword,
    string $voucherNo,
    string $tripName = '',
    string $tripDate = ''
): bool {
    $email = strtolower(trim((string) ($guestAcct['guest_email'] ?? '')));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    if (!function_exists('send_mail')) {
        $leadLib = ROOT_DIR . '/includes/lead_lib.php';
        if (is_file($leadLib)) {
            require_once $leadLib;
        }
    }

    $guestName = trim((string) ($guestAcct['guest_name'] ?? 'Valued Guest'));
    $username  = (string) ($guestAcct['username'] ?? '');
    $appUrl    = 'https://seacollegediveclub.com/app';
    $subject   = 'Welcome to Sea College Dive Club Sharm — Your VIP App Login' . ($voucherNo !== '' ? " ({$voucherNo})" : '');

    $lines = [
        "Dear {$guestName},",
        '',
        'Welcome to the Sea College Dive Club family in Sharm El Sheikh!',
        'Your personal Verified Club Guest account is now active so you can view your trip details and book any future Red Sea diving, boat, safari, or Egypt excursions directly in our app:',
        '',
        "App Link: {$appUrl}",
        "Login Email (or Username): {$email}  (or {$username})",
        "Temporary Password: {$plainPassword}",
    ];
    if ($voucherNo !== '' || $tripName !== '') {
        $lines[] = '';
        $lines[] = 'Latest Trip Reference: ' . ($voucherNo !== '' ? $voucherNo : 'Confirmed')
            . ($tripName !== '' ? " — {$tripName}" : '')
            . ($tripDate !== '' ? " ({$tripDate})" : '');
    }
    $lines[] = '';
    $lines[] = 'Need anything on WhatsApp? Message our Concierge anytime: https://wa.me/' . WHATSAPP_NUMBER;
    $lines[] = 'Warm regards,';
    $lines[] = 'Sea College Dive Club — Sharm El Sheikh';

    $body = implode("\n", $lines);
    $transportOk = function_exists('send_mail') ? (bool) @send_mail($email, $subject, $body, 'info@seacollegediveclub.com') : false;

    $now = gmdate('Y-m-d\TH:i:s\Z');
    $pdo->prepare(
        'INSERT INTO guest_email_dispatches (sent_at, guest_username, recipient_email, voucher_no, subject, body_preview, transport_ok)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $now,
        $username,
        $email,
        $voucherNo,
        $subject,
        substr($body, 0, 1000),
        $transportOk ? 1 : 0,
    ]);

    $pdo->prepare('UPDATE guest_customer_accounts SET credentials_email_sent = 1, credentials_emailed_at = ?, updated_at = ? WHERE username = ?')
        ->execute([$now, $now, $username]);

    return true;
}

/**
 * Provision (or update/reuse) a Verified Real-Customer Account (`role_code = 'GUEST_CUSTOMER'`)
 * after a Seller/Staff confirms a booking — even if the guest paid 0 deposit (`OPEN_REST`) or later cancels!
 * If a valid optional email is supplied (now or added later), dispatches login credentials via email.
 *
 * @param array<string,mixed> $opts
 * @return array<string,mixed>
 */
function sc_erp_provision_verified_guest_account(PDO $pdo, array $opts): array
{
    $parsed   = sc_erp_split_guest_name_phone((string) ($opts['guest_name'] ?? ''), (string) ($opts['guest_phone'] ?? ''));
    $rawName  = trim($parsed['name']);
    $rawPhone = trim($parsed['phone']);
    $rawEmail = strtolower(trim((string) ($opts['guest_email'] ?? '')));
    $validEmail = ($rawEmail !== '' && filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) ? $rawEmail : '';
    $phoneNorm  = sc_erp_normalize_guest_phone($rawPhone);

    // Guard against B2B tour agency names ever being provisioned as human user rows (T2_F3_02 invariant)
    $b2bTokens = ['SHARM GATE', 'SHARM_GATE', 'I DIVE', 'IDIVE', 'DISCOVERY', 'RED SEA MARINE', 'CRAZY SHARK', 'ABO SAMAHA', 'PALMA'];
    $upCheck   = strtoupper($rawName);
    foreach ($b2bTokens as $badTok) {
        if (str_contains($upCheck, $badTok)) {
            $rawName = trim((string) str_ireplace($badTok, 'Club Guest', $rawName));
        }
    }
    if ($rawName === '' || strtolower($rawName) === 'guest' || strtolower($rawName) === 'resort guest') {
        if ($validEmail === '' && $phoneNorm === '') {
            // Generic walk-in placeholder with neither real name, phone, nor email — still create if explicitly requested or use voucher tag
            $voFallback = preg_replace('/[^a-z0-9]/i', '', (string) ($opts['voucher_no'] ?? ''));
            if ($voFallback === '') {
                return ['ok' => false, 'account_created' => false, 'skipped_reason' => 'missing_guest_identity'];
            }
            $rawName = 'Club Guest ' . substr($voFallback, -4);
        } elseif ($rawName === '') {
            $rawName = 'Club Guest';
        }
    }

    $voNo       = trim((string) ($opts['voucher_no'] ?? ''));
    $nat        = strtoupper(trim((string) ($opts['nationality'] ?? '')));
    $lang       = strtolower(trim((string) ($opts['lang'] ?? 'en')));
    if (!in_array($lang, ['en', 'ar', 'ru', 'it', 'pl', 'de', 'fr', 'zh'], true)) {
        $lang = 'en';
    }
    $hotel      = trim((string) ($opts['hotel_name'] ?? ''));
    $room       = trim((string) ($opts['room_no'] ?? ''));
    $sellerUser = strtolower(trim((string) ($opts['seller_username'] ?? '')));
    $tripName   = trim((string) ($opts['trip_name'] ?? ''));
    $tripDate   = trim((string) ($opts['trip_date'] ?? ''));
    $bookedEgp  = max(0.0, round((float) ($opts['booked_egp'] ?? 0.0), 2));
    $paidEgp    = max(0.0, round((float) ($opts['paid_egp'] ?? 0.0), 2));
    $forceResend= !empty($opts['force_resend_email']);

    $statusIn = strtoupper(trim((string) ($opts['booking_status'] ?? '')));
    if ($statusIn === '') {
        $statusIn = ($paidEgp > 0.01 && $paidEgp + 1.0 >= $bookedEgp) ? 'CONFIRMED_PAID' : 'CONFIRMED_UNPAID';
    }

    $now = gmdate('Y-m-d\TH:i:s\Z');

    // 1) Deduplicate returning real customer by exact email or normalized phone (or existing voucher link)
    $existing = null;
    if ($voNo !== '') {
        $vCheck = $pdo->prepare('SELECT guest_account_username FROM reservations_fact WHERE voucher_no = ? AND guest_account_username != "" LIMIT 1');
        $vCheck->execute([$voNo]);
        $linkedUser = (string) ($vCheck->fetchColumn() ?: '');
        if ($linkedUser !== '') {
            $st = $pdo->prepare('SELECT * FROM guest_customer_accounts WHERE username = ? LIMIT 1');
            $st->execute([$linkedUser]);
            $existing = $st->fetch() ?: null;
        }
    }
    if (!$existing && $validEmail !== '') {
        $st = $pdo->prepare('SELECT * FROM guest_customer_accounts WHERE LOWER(guest_email) = ? LIMIT 1');
        $st->execute([$validEmail]);
        $existing = $st->fetch() ?: null;
    }
    if (!$existing && $phoneNorm !== '') {
        $st = $pdo->prepare('SELECT * FROM guest_customer_accounts WHERE guest_phone_norm = ? LIMIT 1');
        $st->execute([$phoneNorm]);
        $existing = $st->fetch() ?: null;
    }

    $accountCreated = false;
    $emailDispatched = false;
    $generatedPass   = '';

    if (!$existing) {
        $slugBase = strtolower((string) preg_replace('/[^a-z0-9]+/i', '', strtok($rawName, ' ') ?: 'member'));
        if (strlen($slugBase) < 3) {
            $slugBase = 'vip' . $slugBase;
        }
        $slugBase = substr($slugBase, 0, 12);
        // Ensure zero reserved/agency collisions
        if (in_array($slugBase, ['sharm', 'idive', 'discovery', 'palma'], true)) {
            $slugBase = 'diver';
        }

        $username = '';
        for ($try = 0; $try < 12; $try++) {
            $cand = 'guest.' . $slugBase . '.' . random_int(1000, 9999);
            $chk = $pdo->prepare('SELECT COUNT(*) FROM users WHERE LOWER(username) = ?');
            $chk->execute([$cand]);
            if ((int) $chk->fetchColumn() === 0) {
                $username = $cand;
                break;
            }
        }
        if ($username === '') {
            $username = 'guest.' . bin2hex(random_bytes(4));
        }

        $generatedPass = sc_erp_generate_guest_password();
        $pwHash        = password_hash($generatedPass, PASSWORD_BCRYPT);

        $pdo->prepare(
            "INSERT INTO users
             (username, password_hash, pin_hint, emp_code, display_name_en, display_name_ar,
              job_title_en, job_title_ar, phone, email, avatar_url, bio_notes,
              role_code, branch_scope, vessel_scope, rep_scope, referral_code,
              default_comm_insheet_pct, default_comm_outside_pct, is_active, created_at, updated_at)
             VALUES (?, ?, '', NULL, ?, ?, 'Verified Club Guest', 'عميل سي كوليدج المعتمد', ?, ?, '', ?,
                     'GUEST_CUSTOMER', 'WEBSITE', 'NONE', '', '', 0, 0, 1, ?, ?)"
        )->execute([
            $username,
            $pwHash,
            $rawName,
            $rawName,
            $rawPhone,
            $validEmail,
            $hotel !== '' ? ("Hotel: {$hotel}" . ($room !== '' ? " #{$room}" : '')) : 'Verified Sea College Guest',
            $now,
            $now,
        ]);

        $isCancelledInit = str_contains($statusIn, 'CANCEL') ? 1 : 0;
        $pdo->prepare(
            'INSERT INTO guest_customer_accounts
             (username, created_at, updated_at, guest_name, guest_phone, guest_phone_norm, guest_email,
              nationality, preferred_lang, hotel_name, room_no, referred_by_seller,
              first_voucher_no, latest_voucher_no, latest_trip_name, latest_trip_date,
              latest_booking_status, total_bookings_count, cancelled_bookings_count,
              lifetime_booked_egp, lifetime_paid_egp, credentials_email_sent, credentials_emailed_at, marketing_opt_in)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, 0, "", 1)'
        )->execute([
            $username, $now, $now, $rawName, $rawPhone, $phoneNorm, $validEmail,
            $nat, $lang, $hotel, $room, $sellerUser,
            $voNo, $voNo, $tripName, $tripDate,
            $statusIn, $isCancelledInit,
            $bookedEgp, $paidEgp,
        ]);

        $accountCreated = true;
        $guestRow = [
            'username'    => $username,
            'guest_name'  => $rawName,
            'guest_email' => $validEmail,
            'guest_phone' => $rawPhone,
        ];

        if ($validEmail !== '') {
            $emailDispatched = sc_erp_send_guest_credentials_email($pdo, $guestRow, $generatedPass, $voNo, $tripName, $tripDate);
        }
        $finalEmail = $validEmail;
        $finalPhone = $rawPhone;
    } else {
        $username = (string) $existing['username'];
        $mergedEmail = $validEmail !== '' ? $validEmail : (string) ($existing['guest_email'] ?? '');
        $mergedPhone = $rawPhone !== '' ? $rawPhone : (string) ($existing['guest_phone'] ?? '');
        $mergedNorm  = $phoneNorm !== '' ? $phoneNorm : (string) ($existing['guest_phone_norm'] ?? '');
        $mergedHotel = $hotel !== '' ? $hotel : (string) ($existing['hotel_name'] ?? '');
        $mergedRoom  = $room !== '' ? $room : (string) ($existing['room_no'] ?? '');
        $mergedNat   = $nat !== '' ? $nat : (string) ($existing['nationality'] ?? '');
        $mergedSeller= (string) ($existing['referred_by_seller'] ?? '') !== '' ? (string) $existing['referred_by_seller'] : $sellerUser;
        $finalEmail  = $mergedEmail;
        $finalPhone  = $mergedPhone;

        $isNewVoucher = ($voNo !== '' && $voNo !== (string) ($existing['latest_voucher_no'] ?? '') && $voNo !== (string) ($existing['first_voucher_no'] ?? ''));
        $newCount     = (int) ($existing['total_bookings_count'] ?? 1) + ($isNewVoucher ? 1 : 0);
        $newBooked    = round((float) ($existing['lifetime_booked_egp'] ?? 0.0) + ($isNewVoucher ? $bookedEgp : 0.0), 2);
        $newPaid      = round((float) ($existing['lifetime_paid_egp'] ?? 0.0) + ($isNewVoucher ? $paidEgp : 0.0), 2);
        $newCanc      = (int) ($existing['cancelled_bookings_count'] ?? 0) + (($isNewVoucher && str_contains($statusIn, 'CANCEL')) ? 1 : 0);

        $pdo->prepare(
            'UPDATE guest_customer_accounts
             SET updated_at = ?, guest_phone = ?, guest_phone_norm = ?, guest_email = ?,
                 nationality = ?, hotel_name = ?, room_no = ?, referred_by_seller = ?,
                 latest_voucher_no = CASE WHEN ? != "" THEN ? ELSE latest_voucher_no END,
                 latest_trip_name = CASE WHEN ? != "" THEN ? ELSE latest_trip_name END,
                 latest_trip_date = CASE WHEN ? != "" THEN ? ELSE latest_trip_date END,
                 latest_booking_status = ?,
                 total_bookings_count = ?, cancelled_bookings_count = ?,
                 lifetime_booked_egp = ?, lifetime_paid_egp = ?
             WHERE username = ?'
        )->execute([
            $now, $mergedPhone, $mergedNorm, $mergedEmail,
            $mergedNat, $mergedHotel, $mergedRoom, $mergedSeller,
            $voNo, $voNo, $tripName, $tripName, $tripDate, $tripDate,
            $statusIn,
            $newCount, $newCanc, $newBooked, $newPaid,
            $username,
        ]);

        // Always keep users.is_active = 1 even on non-payment or cancellation!
        $pdo->prepare('UPDATE users SET is_active = 1, phone = ?, email = ?, updated_at = ? WHERE username = ?')
            ->execute([$mergedPhone, $mergedEmail, $now, $username]);

        $needFirstEmail = ($mergedEmail !== '' && (int) ($existing['credentials_email_sent'] ?? 0) === 0);
        if ($mergedEmail !== '' && ($needFirstEmail || $forceResend)) {
            $generatedPass = sc_erp_generate_guest_password();
            $pdo->prepare('UPDATE users SET password_hash = ?, updated_at = ? WHERE username = ?')
                ->execute([password_hash($generatedPass, PASSWORD_BCRYPT), $now, $username]);
            $emailDispatched = sc_erp_send_guest_credentials_email(
                $pdo,
                ['username' => $username, 'guest_name' => (string) $existing['guest_name'], 'guest_email' => $mergedEmail],
                $generatedPass,
                $voNo !== '' ? $voNo : (string) ($existing['latest_voucher_no'] ?? ''),
                $tripName !== '' ? $tripName : (string) ($existing['latest_trip_name'] ?? ''),
                $tripDate !== '' ? $tripDate : (string) ($existing['latest_trip_date'] ?? '')
            );
        }
    }

    if ($voNo !== '') {
        $pdo->prepare(
            'UPDATE reservations_fact
             SET guest_account_username = ?,
                 guest_phone = CASE WHEN ? != "" THEN ? ELSE guest_phone END,
                 guest_email = CASE WHEN ? != "" THEN ? ELSE guest_email END
             WHERE voucher_no = ?'
        )->execute([$username, $finalPhone, $finalPhone, $finalEmail, $finalEmail, $voNo]);

        // Enrich Marketing CRM (`website_leads_crm`) so Hossam & Lara can retarget real guests (including unpaid/cancelled!)
        $fx = sc_erp_fx_rates($pdo);
        $eurEq = round($bookedEgp / ($fx['EUR'] ?? 57.0), 2);
        $paidEurEq = round($paidEgp / ($fx['EUR'] ?? 57.0), 2);
        $sRefRow = $sellerUser !== '' ? sc_erp_resolve_seller($pdo, $sellerUser) : null;
        $sRepCode = $sRefRow ? (string) $sRefRow['rep_scope'] : 'DIRECT';
        $pdo->prepare(
            'INSERT OR REPLACE INTO website_leads_crm
             (event_id, received_at, lead_type, lang, guest_name, phone, email, interest_items,
              people, trip_date, hotel, room_no, cert_level, gear_sizes_json, estimated_eur, final_paid_eur,
              status, gclid, fbclid, utm_source, utm_campaign, assigned_to, linked_voucher_no,
              seller_username, sales_rep_code)
             VALUES (?, ?, "VERIFIED_GUEST_ACCOUNT", ?, ?, ?, ?, ?, 1, ?, ?, ?, "", "{}", ?, ?, ?, "", "", "erp_confirmed_guest", "real_customer_vault", ?, ?, ?, ?)'
        )->execute([
            'guest_acct_' . strtolower($username),
            $now,
            $lang,
            $rawName,
            $finalPhone,
            $finalEmail,
            $tripName !== '' ? $tripName : 'Sea College Trip',
            $tripDate,
            $hotel,
            $room,
            $eurEq,
            $paidEurEq,
            $statusIn,
            $sRepCode,
            $voNo,
            $sellerUser,
            $sRepCode,
        ]);
    }

    $resp = [
        'ok'                 => true,
        'account_created'    => $accountCreated,
        'guest_username'     => $username,
        'guest_email'        => $finalEmail,
        'email_sent'         => $emailDispatched,
        'booking_lifecycle'  => $statusIn,
        'app_login_url'      => 'https://seacollegediveclub.com/app',
    ];
    if ($generatedPass !== '') {
        $resp['initial_password'] = $generatedPass;
    }
    return $resp;
}

/**
 * Keep a guest's verified account ACTIVE and sync lifecycle status when a voucher is cancelled or settled.
 * Never deletes or deactivates the guest account on cancellation or non-payment!
 *
 * @param array<string,mixed> $voucherRow
 * @return array<string,mixed>|null
 */
function sc_erp_sync_guest_account_on_voucher_change(PDO $pdo, array $voucherRow, string $newLifecycleStatus): ?array
{
    $guestUser = trim((string) ($voucherRow['guest_account_username'] ?? ''));
    $now = gmdate('Y-m-d\TH:i:s\Z');

    if ($guestUser !== '') {
        $aggSt = $pdo->prepare(
            "SELECT COUNT(*) AS cnt,
                    SUM(CASE WHEN booking_state = 'CANCELLED' THEN 1 ELSE 0 END) AS canc_cnt,
                    ROUND(COALESCE(SUM(CASE WHEN booking_state != 'CANCELLED' THEN (CASE WHEN booked_total_egp > 0 THEN booked_total_egp ELSE estimated_revenue_egp END) ELSE 0 END), 0), 2) AS booked_sum,
                    ROUND(COALESCE(SUM(CASE WHEN booking_state != 'CANCELLED' THEN actual_paid_total_egp ELSE 0 END), 0), 2) AS paid_sum
             FROM reservations_fact
             WHERE guest_account_username = ?"
        );
        $aggSt->execute([$guestUser]);
        $agg = $aggSt->fetch() ?: [];

        $pdo->prepare(
            'UPDATE guest_customer_accounts
             SET latest_booking_status = ?,
                 total_bookings_count = MAX(1, ?),
                 cancelled_bookings_count = ?,
                 lifetime_booked_egp = ?,
                 lifetime_paid_egp = ?,
                 updated_at = ?
             WHERE username = ?'
        )->execute([
            $newLifecycleStatus,
            (int) ($agg['cnt'] ?? 1),
            (int) ($agg['canc_cnt'] ?? (str_contains(strtoupper($newLifecycleStatus), 'CANCEL') ? 1 : 0)),
            (float) ($agg['booked_sum'] ?? 0.0),
            (float) ($agg['paid_sum'] ?? 0.0),
            $now,
            $guestUser,
        ]);
        $pdo->prepare('UPDATE users SET is_active = 1, updated_at = ? WHERE username = ?')
            ->execute([$now, $guestUser]);
        return ['guest_username' => $guestUser, 'booking_lifecycle' => $newLifecycleStatus, 'is_active' => true];
    }

    // If a live booking (mobile app or seller share link) was cancelled before account link, still create & retain the guest account!
    $srcFile = strtoupper((string) ($voucherRow['source_file'] ?? ''));
    if (in_array($srcFile, ['LIVE_MOBILE_APP', 'SELLER_SHARE_LINK', 'LIVE_WEBSITE_FORM'], true)) {
        return sc_erp_provision_verified_guest_account($pdo, [
            'voucher_no'      => (string) ($voucherRow['voucher_no'] ?? ''),
            'guest_name'      => (string) ($voucherRow['divers_names'] ?? ''),
            'guest_phone'     => (string) ($voucherRow['guest_phone'] ?? ''),
            'guest_email'     => (string) ($voucherRow['guest_email'] ?? ''),
            'nationality'     => (string) ($voucherRow['nationality'] ?? ''),
            'hotel_name'      => (string) ($voucherRow['hotel_name'] ?? ''),
            'room_no'         => (string) ($voucherRow['room_no'] ?? ''),
            'seller_username' => (string) ($voucherRow['seller_username'] ?? ''),
            'trip_name'       => (string) ($voucherRow['trip_name'] ?? ''),
            'trip_date'       => (string) ($voucherRow['trip_date'] ?? ''),
            'booked_egp'      => (float) ($voucherRow['booked_total_egp'] ?? ($voucherRow['estimated_revenue_egp'] ?? 0.0)),
            'paid_egp'        => (float) ($voucherRow['actual_paid_total_egp'] ?? ($voucherRow['deposit_egp_equiv'] ?? 0.0)),
            'booking_status'  => $newLifecycleStatus,
        ]);
    }
    return null;
}



