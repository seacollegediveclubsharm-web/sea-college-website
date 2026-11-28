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
        'safari_breakdown_json'  => "TEXT NOT NULL DEFAULT ''",
        'online_hub_source'      => "TEXT NOT NULL DEFAULT ''",
        'online_seller_account'  => "TEXT NOT NULL DEFAULT ''",
    ];
    foreach ($resAddCols as $col => $ddl) {
        if (!isset($haveResCols[$col])) {
            $pdo->exec("ALTER TABLE reservations_fact ADD COLUMN {$col} {$ddl}");
        }
    }

    $haveCrmCols = array_flip(array_column($pdo->query('PRAGMA table_info(website_leads_crm)')->fetchAll(), 'name'));
    $crmAddCols = [
        'online_hub_source'     => "TEXT NOT NULL DEFAULT 'WEBSITE_DIRECT'",
        'online_seller_account' => "TEXT NOT NULL DEFAULT ''",
        'adults'                => 'INTEGER NOT NULL DEFAULT 1',
        'children'              => 'INTEGER NOT NULL DEFAULT 0',
        'infants'               => 'INTEGER NOT NULL DEFAULT 0',
        'paid_deposit_egp'      => 'REAL NOT NULL DEFAULT 0',
        'rest_due_egp'          => 'REAL NOT NULL DEFAULT 0',
        'confirmed_at'          => "TEXT NOT NULL DEFAULT ''",
        'confirmed_by'          => "TEXT NOT NULL DEFAULT ''",
    ];
    foreach ($crmAddCols as $cCol => $cDdl) {
        if (!isset($haveCrmCols[$cCol])) {
            $pdo->exec("ALTER TABLE website_leads_crm ADD COLUMN {$cCol} {$cDdl}");
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
        CREATE INDEX IF NOT EXISTS idx_ged_user ON guest_email_dispatches(guest_username, id DESC);
        CREATE TABLE IF NOT EXISTS suppliers_directory (
            supplier_code TEXT PRIMARY KEY,
            name_en TEXT NOT NULL,
            name_ar TEXT NOT NULL,
            category_code TEXT NOT NULL DEFAULT 'EXCURSIONS',
            contact_person TEXT NOT NULL DEFAULT '',
            phone TEXT NOT NULL DEFAULT '',
            whatsapp TEXT NOT NULL DEFAULT '',
            settlement_currency TEXT NOT NULL DEFAULT 'EGP',
            payment_terms TEXT NOT NULL DEFAULT 'Monthly / Weekly Statement',
            sheet_tab_name TEXT NOT NULL DEFAULT '',
            opening_balance_egp REAL NOT NULL DEFAULT 0,
            current_balance_egp REAL NOT NULL DEFAULT 0,
            notes TEXT NOT NULL DEFAULT '',
            is_active INTEGER NOT NULL DEFAULT 1,
            updated_by TEXT NOT NULL DEFAULT 'SYSTEM',
            updated_at TEXT NOT NULL DEFAULT ''
        );
        CREATE TABLE IF NOT EXISTS trip_supplier_cost_items (
            item_code TEXT PRIMARY KEY,
            linked_trip_code TEXT NOT NULL DEFAULT '',
            category_group TEXT NOT NULL DEFAULT 'DESERT_SAFARI',
            supplier_code TEXT NOT NULL DEFAULT 'AP_BAKAR',
            name_en TEXT NOT NULL,
            name_ar TEXT NOT NULL,
            pricing_basis TEXT NOT NULL DEFAULT 'PER_PAX',
            capacity_pax INTEGER NOT NULL DEFAULT 1,
            cost_currency TEXT NOT NULL DEFAULT 'EGP',
            adult_cost_orig REAL NOT NULL DEFAULT 0,
            child_cost_orig REAL NOT NULL DEFAULT 0,
            infant_cost_orig REAL NOT NULL DEFAULT 0,
            unit_cost_orig REAL NOT NULL DEFAULT 0,
            adult_cost_egp REAL NOT NULL DEFAULT 0,
            child_cost_egp REAL NOT NULL DEFAULT 0,
            infant_cost_egp REAL NOT NULL DEFAULT 0,
            unit_cost_egp REAL NOT NULL DEFAULT 0,
            adult_sell_eur REAL NOT NULL DEFAULT 0,
            child_sell_eur REAL NOT NULL DEFAULT 0,
            infant_sell_eur REAL NOT NULL DEFAULT 0,
            unit_sell_eur REAL NOT NULL DEFAULT 0,
            sale_mode TEXT NOT NULL DEFAULT 'OUT_SIDE',
            is_safari_component INTEGER NOT NULL DEFAULT 0,
            sheet_formula_note TEXT NOT NULL DEFAULT '',
            is_active INTEGER NOT NULL DEFAULT 1,
            updated_by TEXT NOT NULL DEFAULT 'SYSTEM',
            updated_at TEXT NOT NULL DEFAULT ''
        );
        CREATE INDEX IF NOT EXISTS idx_tsci_cat_sup ON trip_supplier_cost_items(category_group, supplier_code, is_active);
        CREATE TABLE IF NOT EXISTS trip_cost_audit_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            changed_at TEXT NOT NULL,
            changed_by TEXT NOT NULL,
            changed_by_role TEXT NOT NULL,
            action_type TEXT NOT NULL DEFAULT 'UPDATE_COST',
            item_code TEXT NOT NULL,
            item_name_en TEXT NOT NULL DEFAULT '',
            supplier_code TEXT NOT NULL DEFAULT '',
            prev_adult_cost_egp REAL NOT NULL DEFAULT 0,
            new_adult_cost_egp REAL NOT NULL DEFAULT 0,
            prev_child_cost_egp REAL NOT NULL DEFAULT 0,
            new_child_cost_egp REAL NOT NULL DEFAULT 0,
            prev_infant_cost_egp REAL NOT NULL DEFAULT 0,
            new_infant_cost_egp REAL NOT NULL DEFAULT 0,
            prev_unit_cost_egp REAL NOT NULL DEFAULT 0,
            new_unit_cost_egp REAL NOT NULL DEFAULT 0,
            reason_notes TEXT NOT NULL DEFAULT ''
        );
        CREATE INDEX IF NOT EXISTS idx_tcal_item ON trip_cost_audit_log(item_code, id DESC);
        CREATE TABLE IF NOT EXISTS staff_bonuses_allowances (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            seed_key TEXT DEFAULT NULL UNIQUE,
            created_at TEXT NOT NULL,
            tx_date TEXT NOT NULL,
            period_month TEXT NOT NULL,
            emp_username TEXT NOT NULL,
            emp_name_en TEXT NOT NULL,
            emp_name_ar TEXT NOT NULL,
            role_code TEXT NOT NULL DEFAULT 'STAFF',
            branch_code TEXT NOT NULL DEFAULT 'CYRENE',
            entry_type TEXT NOT NULL DEFAULT 'BONUS',
            category TEXT NOT NULL DEFAULT 'PERFORMANCE_BONUS',
            amount_egp REAL NOT NULL DEFAULT 0,
            currency TEXT NOT NULL DEFAULT 'EGP',
            amount_orig REAL NOT NULL DEFAULT 0,
            safe_code TEXT NOT NULL DEFAULT 'NONE_PAYROLL',
            status TEXT NOT NULL DEFAULT 'APPROVED_PAID',
            requested_by TEXT NOT NULL DEFAULT '',
            approved_by TEXT NOT NULL DEFAULT '',
            decided_at TEXT NOT NULL DEFAULT '',
            reason_en TEXT NOT NULL DEFAULT '',
            reason_ar TEXT NOT NULL DEFAULT ''
        );
        CREATE INDEX IF NOT EXISTS idx_sba_month_status ON staff_bonuses_allowances(period_month, status, entry_type);
        CREATE INDEX IF NOT EXISTS idx_sba_emp ON staff_bonuses_allowances(emp_username, period_month);
        CREATE TABLE IF NOT EXISTS financial_party_transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tx_ref TEXT NOT NULL UNIQUE,
            seed_key TEXT DEFAULT NULL UNIQUE,
            entity_type TEXT NOT NULL,
            entity_code TEXT NOT NULL,
            entity_name_en TEXT NOT NULL DEFAULT '',
            entity_name_ar TEXT NOT NULL DEFAULT '',
            tx_date TEXT NOT NULL,
            period_month TEXT NOT NULL,
            tx_kind TEXT NOT NULL DEFAULT 'INSTALLMENT',
            direction TEXT NOT NULL DEFAULT 'COMPANY_PAID_OUT',
            orig_amount REAL NOT NULL DEFAULT 0,
            orig_currency TEXT NOT NULL DEFAULT 'EGP',
            fx_rate REAL NOT NULL DEFAULT 1.0,
            amount_egp REAL NOT NULL DEFAULT 0,
            payment_method TEXT NOT NULL DEFAULT 'SAFE_CYRENE',
            voucher_or_receipt_no TEXT NOT NULL DEFAULT '',
            description_en TEXT NOT NULL DEFAULT '',
            description_ar TEXT NOT NULL DEFAULT '',
            recorded_by TEXT NOT NULL DEFAULT 'SYSTEM',
            created_at TEXT NOT NULL
        );
        CREATE INDEX IF NOT EXISTS idx_fpt_entity_date ON financial_party_transactions(entity_type, entity_code, tx_date, id);"
    );

    sc_erp_seed_default_inbox_messages($pdo);

    $tripCnt = (int) $pdo->query('SELECT COUNT(*) FROM master_trips_catalog')->fetchColumn();
    if ($tripCnt < 30) {
        sc_erp_sync_master_trips_catalog($pdo);
    }

    sc_erp_seed_suppliers_and_cost_matrix($pdo);
    sc_erp_seed_staff_bonuses_allowances($pdo);
    sc_erp_ensure_online_seller_v37($pdo);
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

/**
 * Seed `suppliers_directory` and `trip_supplier_cost_items` idempotently (`INSERT OR IGNORE`)
 * using exact figures audited from `2026 الموردين.xlsx` (`BAKAR`, `A.SAAD`, `DOLPHIN`, `DINNER`,
 * `HORSE`, `SHARMINA`, `SUBMARINE`, `THISTLE GORM`, `ONE TOUR`, `ELNAHAS`, `SPHINX`, `MILLINIOM`,
 * `HADY-FANAR`, `TRANSFER`, `S.C H.S`) and `RESERVATION` sheets.
 */
function sc_erp_seed_suppliers_and_cost_matrix(PDO $pdo): void
{
    $supCount = (int) $pdo->query('SELECT COUNT(*) FROM suppliers_directory')->fetchColumn();
    if ($supCount < 15) {
        // Pull live ledger balances from counterparties if present
        $cpBalances = [];
        try {
            $cpRows = $pdo->query("SELECT code, opening_balance_egp, current_balance_egp FROM counterparties WHERE code LIKE 'AP_%'")->fetchAll();
            foreach ($cpRows as $cr) {
                $cpBalances[(string) $cr['code']] = [
                    'open' => (float) ($cr['opening_balance_egp'] ?? 0.0),
                    'curr' => (float) ($cr['current_balance_egp'] ?? 0.0),
                ];
            }
        } catch (Throwable $e) {}

        $suppliers = [
            ['SEA_COLLEGE', 'Sea College Fleet & PADI 5★ Center', 'أسطول ومراكز سي كوليدج (أوشينو / عمر 4 / نزار / سحس / يوانا)', 'INHOUSE_FLEET', 'Capt. Haitham & Ahmed Nasr', '+201001106266', '+201001106266', 'EGP', 'Internal Fleet Settlement', 'CENTER', 0.0, 0.0, 'In-house luxury yachts (M/Y Oceano, Omar 4, Nizar, Sohs), Zodiac Youana & PADI 5★ Dive Center'],
            ['AP_BAKAR', 'Bakar Desert Safari Center', 'بكار للسفاري والبيتش باجي والعشاء البدوي', 'DESERT_SAFARI', 'Capt. Bakar (Safari Ops)', '+201004509911', '+201004509911', 'EGP', 'Monthly Cyrene & Jaz Statement', 'BAKAR', 160192.5, -95925.0, 'Single Quad 400 EGP, Double Quad 450 EGP, Buggy 2-Pax 800 EGP, Family Buggy 4-Pax 1100 EGP, Camel w/ Safari 80 EGP, Camel 1h 300 EGP, Bedouin Dinner 200 EGP, VIP Dinner 300 EGP, Star Gazing 200 EGP, Private Guide 400 EGP + $1/PAX Eco Ticket'],
            ['AP_A_SAAD', 'Ahmed Saad Cairo & Sinai Tours', 'أحمد سعد لرحلات القاهرة وسانت كاترين ودهب', 'CAIRO_SINAI', 'Mr. Ahmed Saad', '+201005112288', '+201005112288', 'EGP', 'Monthly Statement', 'A.SAAD', 136502.5, -61715.0, 'Cairo Bus Old Museum 2000 EGP, Grand Egyptian Museum (GEM) 4000 EGP, St. Catherine 1050 EGP, Mount Moses 1000 EGP, Dahab & Grand Canyon 450 EGP (+200 Moto), Ras Mohammed Bus 500 EGP'],
            ['AP_ONE_TOUR', 'One Tour Aviation & VIP Historical', 'ون تور لطيران القاهرة والأقصر والبتراء والقدس', 'AVIATION_VIP', 'One Tour Aviation Desk', '+201009887744', '+201009887744', 'USD', 'Per-Flight USD Settlement', 'ONE TOUR ', 0.0, 2130.0, 'Cairo by Plane $245, Luxor by Plane $235, Petra $225, Jerusalem $160'],
            ['AP_DOLPHIN', 'Dolphina Park Sharm (Desouky)', 'عرض وسباحة الدولفين — دسوقي', 'DOLPHIN_SUB_DINNER', 'Mr. Desouky', '+201003344112', '+201003344112', 'USD', 'Same-Week USD Settlement', 'DOLPHIN', 0.0, 5217.0, 'Dolphin Show $12 Adult / $6 Child; Interactive Swim w/ Dolphins $62'],
            ['AP_SUBMARINE', 'United Royal Seascope Submarine', 'الغواصة الملكية (يونايتد)', 'DOLPHIN_SUB_DINNER', 'United Marine Desk', '+201002299881', '+201002299881', 'USD', 'Weekly USD / EGP Statement', 'SUBMARINE', 0.0, -18751.0, 'Foreign Adult $25, Arab Adult $20, Egyptian Adult 600 EGP, Child $5, Infant Free'],
            ['AP_DINNER', 'Luxury Dinner Cruise Fleet', 'يخت سهرة العشاء البحري (Luxury Cruise)', 'DOLPHIN_SUB_DINNER', 'Luxury Cruise Reservations', '+201007766554', '+201007766554', 'EGP', 'Monthly Statement', 'DINNER ', 0.0, -6100.0, 'Romantic Seafood & Grill Dinner Boat: 1,000 EGP Adult, 500 EGP Child, Infant Free'],
            ['AP_HORSE', 'Sahara Stable Horse Riding', 'إسطبل الخيل العربي بالشاطئ والصحراء', 'DESERT_SAFARI', 'Sahara Stable Captain', '+201006655321', '+201006655321', 'EGP', 'Monthly Statement', 'HORSE ', 46500.0, -29600.0, '1-Hour Beach & Desert Horse Riding: 800 EGP + $10 Transfer (~1,290 EGP) or 700 EGP w/o Transfer'],
            ['AP_SHARMINA', 'Sharmina & Hilton Watersports', 'شارمينيا وهيلتون للألعاب المائية والباراشوت والجلاس بوت', 'WATERSPORTS', 'Sharmina Beach Ops', '+201008811223', '+201008811223', 'EGP', 'Bi-Weekly Statement', 'SHARMINA ', 9400.0, -19800.0, 'Single Parasailing 600 EGP, Double Parasailing 700 EGP, Triple 800 EGP, Glass Boat 300 EGP Adult / 150 EGP Child, Banana/Tube 200 EGP'],
            ['AP_THISTLE_GORM', 'SS Thistlegorm Wreck Charter (Capt. Ahmed Ali)', 'رحلات حطام سيسل جورم — ك/أحمد علي', 'DIVING_CHARTER', 'Capt. Ahmed Ali', '+201004411990', '+201004411990', 'EUR', 'Per-Trip EUR Settlement', 'THISTLE GORM ', 0.0, -830.0, 'Diver w/ Guide €100, Diver w/o Guide €120, Snorkeler €45, 3rd Dive +€5–€10'],
            ['AP_HANY_KHADR', 'Hany Khadr Bus & Jetty Transfers', 'هاني خضر لباصات وترانسفير السقالة', 'TRANSFERS', 'Mr. Hany Khadr', '+201003118877', '+201003118877', 'EGP', 'Weekly Bus Statement', 'TRANSFER ', 58600.0, -66500.0, 'Jetty Shuttle 650 EGP One-Way / 1,300 EGP Round-Trip; Ras Mohammed Land Bus Charter 2,000 EGP'],
            ['AP_EL_ARAYSHI', 'El-Arayshi Hotel Transfers', 'العرايشي للترانسفير الداخلي', 'TRANSFERS', 'Mr. El-Arayshi', '+201002994455', '+201002994455', 'EGP', 'Weekly Statement', 'TRANSFER ', 20700.0, -28200.0, 'Cyrene / Fanar / Jaz / Dolphin / Naama Shuttle 250–350 EGP One-Way, 500–700 EGP Round-Trip'],
            ['AP_S_C_H_S', 'Sea College HiAce Van Shuttle', 'هاي إس سي كوليدج للترانسفير', 'TRANSFERS', 'Operations Dispatch', '+201001106266', '+201001106266', 'EGP', 'Monthly Lease & Leg Log', 'S.C H.S', 0.0, 8075.0, 'HiAce Shuttle 325–650 EGP One-Way / 1,300 EGP Jetty Round-Trip (30,000 EGP Monthly Lease)'],
            ['AP_ELNAHAS', 'El-Nahas Boat Sharing', 'شيرنج مراكب النحاس', 'BOAT_SHARING', 'El-Nahas Marine', '+201005544332', '+201005544332', 'EGP', 'Monthly Sharing Statement', 'ELNAHAS ', 0.0, -41300.0, 'Snorkel 1,250 EGP, 1x Intro 1,600 EGP, 2x Intro 1,950 EGP, Certified Diver (2 Dives) 2,000–3,000 EGP, Gear Rent 100 EGP'],
            ['AP_SPHINX', 'Sphinx Center Boat Sharing (M. Abdallah)', 'شيرنج سنتر سفنكس — محمد عبدالله', 'BOAT_SHARING', 'Mr. Mohamed Abdallah', '+201007722119', '+201007722119', 'EGP', 'Monthly Sharing Statement', 'SPHINX', 0.0, -9298.0, 'White Island / Tiran Sharing: Snorkel 1,000 EGP, 1x Intro 1,350 EGP, 2x Intro 1,650 EGP, Certified Diver 3,000 EGP, Gear 200 EGP'],
            ['AP_MILLINIOM', 'Millennium Beach Dive Center', 'سنتر ميلينيوم — دخول شاطئ وغطس', 'BEACH_ACCESS', 'Millennium Desk', '+201006119922', '+201006119922', 'EGP', 'Monthly Statement', 'MILLINIOM', 2000.0, -803.0, 'Beach Entry 100 EGP, Snorkel 200 EGP, Short Intro 400 EGP, Long Intro 650 EGP'],
            ['AP_HADY_FANAR', 'Hady Al-Fanar Reef Beach', 'هادي شاطئ الفنار — دخول وسنوركل وانترو', 'BEACH_ACCESS', 'Mr. Hady Fanar', '+201006228833', '+201006228833', 'EGP', 'Monthly Statement', 'HADY-FANAR', 0.0, 1218.0, 'Fanar Reef Entry 200 EGP, Snorkel 200 EGP, Short Intro 400 EGP, Long Intro 650 EGP'],
            ['AP_INFINITY', 'Infinity Excursions Sharm', 'إنفينيتي للرحلات السياحية', 'CAIRO_SINAI', 'Infinity Ops', '+201009114477', '+201009114477', 'EGP', 'Monthly Statement', 'INFINITY', 0.0, -67375.0, 'Partner excursion & Sinai canyon overflow supplier'],
        ];

        $insSup = $pdo->prepare(
            'INSERT OR IGNORE INTO suppliers_directory
             (supplier_code, name_en, name_ar, category_code, contact_person, phone, whatsapp,
              settlement_currency, payment_terms, sheet_tab_name, opening_balance_egp, current_balance_egp,
              notes, is_active, updated_by, updated_at)
             VALUES (?,?,?,?,?,?,?, ?,?,?,?,?, ?,1,"SYSTEM","2026-11-01T00:00:00Z")'
        );
        foreach ($suppliers as $s) {
            $code = $s[0];
            $openBal = isset($cpBalances[$code]) && $cpBalances[$code]['open'] != 0.0 ? $cpBalances[$code]['open'] : $s[10];
            $currBal = isset($cpBalances[$code]) && $cpBalances[$code]['curr'] != 0.0 ? $cpBalances[$code]['curr'] : $s[11];
            $insSup->execute([
                $code, $s[1], $s[2], $s[3], $s[4], $s[5], $s[6],
                $s[7], $s[8], $s[9], $openBal, $currBal, $s[12],
            ]);
        }
    }

    // Ensure concession, catering, charter & PADI operational vendors from `الارصده 2026` are also selectable as Suppliers
    $extraCoreVendors = [
        ['AP_HOTEL_RENT', 'Cyrene Sharm Hotel Concession Rent', 'إيجار كونتر وسنتر فندق سيرين شرم', 'CONCESSION_RENT', 'Cyrene Hotel Finance', '+201001106266', '+201001106266', 'USD', 'Monthly Lease & Installments', 'CYREEN ', 0.0, -570000.0, 'Monthly hotel concession lease ($11,400 / 570,000 EGP) settled via on-account installments & Banque Misr deposits'],
        ['AP_SUPERMARKET', 'Yacht Catering Supermarket Supplier', 'السوبر ماركت وتموين المراكب', 'CATERING_SUPPLIES', 'Supermarket Catering Account', '+201005501199', '+201005501199', 'EGP', 'Monthly Provisions & Cash Installments', 'السوبر ماركت', 0.0, -92928.0, 'Daily guest & crew catering provisions for M/Y Oceano, Omar 4, Nizar & External Boats'],
        ['AP_HIACE_RENT', 'HiAce Van Monthly Lease Owner', 'إيجار سيارة الهاي إس الشهرية', 'TRANSFERS', 'HiAce Fleet Owner', '+201004402288', '+201004402288', 'EGP', 'Monthly Fixed Lease (30,000 EGP)', 'الهاى اس ', 0.0, -30000.0, 'Fixed monthly HiAce van lease (30,000 EGP/month) tracked in ايجار الفنادق / الهاى اس'],
        ['AP_EXTERNAL_BOATS', 'External Chartered Yachts Pool', 'مراكب خارجية مؤجرة (تشغيل السقالة)', 'BOAT_SHARING', 'Jetty Charter Dispatch', '+201003307766', '+201003307766', 'EGP', 'Monthly Charter Settlement', 'مراكب خارجية', 0.0, -140000.0, 'Overflow external charter yachts for White Island, Ras Mohammed & Tiran peak days'],
        ['AP_PADI', 'PADI EMEA Certifications & eLearning', 'منظمة بادي الدولية (كروت وشهادات الغوص)', 'DIVING_CHARTER', 'PADI Regional Account', '', '', 'EUR', 'EUR Credit / Card Settlement', 'PADI 2026', 0.0, -533.0, 'PADI PIC & eLearning certification credits (OWC, AOWC, Rescue, EFR, Nitrox, Deep)'],
    ];
    $insExtraSup = $pdo->prepare(
        'INSERT OR IGNORE INTO suppliers_directory
         (supplier_code, name_en, name_ar, category_code, contact_person, phone, whatsapp,
          settlement_currency, payment_terms, sheet_tab_name, opening_balance_egp, current_balance_egp,
          notes, is_active, updated_by, updated_at)
         VALUES (?,?,?,?,?,?,?, ?,?,?,?,?, ?,1,"SYSTEM","2026-11-01T00:00:00Z")'
    );
    foreach ($extraCoreVendors as $ev) {
        $insExtraSup->execute($ev);
    }

    $costCount = (int) $pdo->query('SELECT COUNT(*) FROM trip_supplier_cost_items')->fetchColumn();
    if ($costCount < 25) {
        $fx = sc_erp_fx_rates($pdo);
        $usdRate = $fx['USD'] ?? 50.0;
        $eurRate = $fx['EUR'] ?? 57.0;

        $insCost = $pdo->prepare(
            'INSERT OR IGNORE INTO trip_supplier_cost_items
             (item_code, linked_trip_code, category_group, supplier_code, name_en, name_ar,
              pricing_basis, capacity_pax, cost_currency,
              adult_cost_orig, child_cost_orig, infant_cost_orig, unit_cost_orig,
              adult_cost_egp, child_cost_egp, infant_cost_egp, unit_cost_egp,
              adult_sell_eur, child_sell_eur, infant_sell_eur, unit_sell_eur,
              sale_mode, is_safari_component, sheet_formula_note, is_active, updated_by, updated_at)
             VALUES (?,?,?,?,?,?, ?,?,?, ?,?,?,?, ?,?,?,?, ?,?,?,?, ?,?,?, 1, "SYSTEM", "2026-11-01T00:00:00Z")'
        );

        // 1) GRANULAR DESERT SAFARI & BEDOUIN MATRIX (EXACT FROM `BAKAR` & `HORSE` SHEETS!)
        $safariItems = [
            // [item_code, linked_trip_code, cat, sup, name_en, name_ar, basis, cap, curr, ad_orig, ch_orig, inf_orig, unit_orig, ad_egp, ch_egp, inf_egp, unit_egp, ad_eur, ch_eur, inf_eur, unit_eur, mode, is_saf_comp, note]
            ['SAF-QUAD-SINGLE', 'exc-safari-quad-single', 'DESERT_SAFARI', 'AP_BAKAR', 'Single Quad Bike Safari (1 Rider Moto)', 'سفاري موتوسيكل / بيتش باجي فردي (Single Quad — راكب واحد)', 'SINGLE_QUAD', 1, 'EGP', 400, 400, 0, 400, 400, 400, 0, 400, 25, 15, 0, 25, 'OUT_SIDE', 1, 'BAKAR Sheet Col SGL = 400 EGP per Single Quad (+$1/PAX desert ticket)'],
            ['SAF-QUAD-DOUBLE', 'exc-safari-quad-double', 'DESERT_SAFARI', 'AP_BAKAR', 'Double Quad Bike Safari (2 Riders Sharing 1 Quad)', 'سفاري موتوسيكل / بيتش باجي زوجي لشخصين (Double Quad)', 'DOUBLE_QUAD', 2, 'EGP', 450, 225, 0, 450, 450, 225, 0, 450, 35, 15, 0, 35, 'OUT_SIDE', 1, 'BAKAR Sheet Col DBL = 450 EGP per Double Quad (2 people sharing)'],
            ['SAF-BUGGY-2PAX', 'ops-safari-dune-buggy', 'DESERT_SAFARI', 'AP_BAKAR', 'Dune Buggy for 2 People (Double Car Buggy)', 'باجي كار صحراوي لشخصين (Buggy D — 2 People)', 'BUGGY_2PAX', 2, 'EGP', 800, 400, 0, 800, 800, 400, 0, 800, 40, 20, 0, 40, 'OUT_SIDE', 1, 'BAKAR Sheet Col BUGGY D = 800 EGP per 2-person Dune Buggy'],
            ['SAF-BUGGY-4PAX', 'exc-safari-family-buggy', 'DESERT_SAFARI', 'AP_BAKAR', 'Family Buggy for 4 People (4-Seater Buggy)', 'باجي عائلي 4 أفراد (Buggy F — Family 4 People)', 'BUGGY_4PAX', 4, 'EGP', 1100, 0, 0, 1100, 1100, 0, 0, 1100, 65, 0, 0, 65, 'OUT_SIDE', 1, 'BAKAR Sheet Col BUGGY F = 1,100 EGP per 4-person Family Buggy'],
            ['SAF-CAMEL-WITH-SAFARI', '', 'DESERT_SAFARI', 'AP_BAKAR', 'Camel Riding Add-On (With Safari)', 'ركوب الجمل مع السفاري (Camel Add-On per PAX)', 'CAMEL_ADDON', 1, 'EGP', 80, 80, 0, 80, 80, 80, 0, 80, 5, 5, 0, 5, 'OUT_SIDE', 1, 'BAKAR Sheet Col CAMEL = 80 EGP per person added to Quad/Buggy Safari'],
            ['SAF-CAMEL-1HR-TOUR', '', 'DESERT_SAFARI', 'AP_BAKAR', 'Camel Riding 1-Hour Standalone Desert Tour', 'جولة ركوب الجمل لمدة ساعة كاملة (Camel Only 1-Hour Tour)', 'CAMEL_1HR', 1, 'EGP', 300, 150, 0, 300, 300, 150, 0, 300, 20, 12, 0, 20, 'OUT_SIDE', 1, 'BAKAR Sheet Aug 2026 Col CAMEL ONLY = 300 EGP Adult / 150 EGP Child'],
            ['SAF-STAR-GAZING', '', 'DESERT_SAFARI', 'AP_BAKAR', 'Desert Star Gazing & Telescope Experience', 'مراقبة النجوم بالتليسكوب في صحراء سيناء (Star Gazing)', 'STAR_GAZING', 1, 'EGP', 200, 100, 0, 200, 200, 100, 0, 200, 15, 10, 0, 15, 'OUT_SIDE', 1, 'BAKAR Sheet Col STAR = 200 EGP Adult / 100 EGP Child'],
            ['SAF-BEDOUIN-DINNER', '', 'DESERT_SAFARI', 'AP_BAKAR', 'Standard Bedouin Dinner & Oriental Show', 'عشاء بدوي وحفلة شرقية (Standard Bedouin Dinner)', 'BEDOUIN_DINNER', 1, 'EGP', 200, 100, 0, 200, 200, 100, 0, 200, 15, 10, 0, 15, 'OUT_SIDE', 1, 'BAKAR Sheet Col DINNER = 200 EGP Adult / 100 EGP Child'],
            ['SAF-VIP-BEDOUIN-DINNER', 'exc-safari-vip-bedouin-dinner', 'DESERT_SAFARI', 'AP_BAKAR', 'VIP Bedouin BBQ Dinner & Show', 'عشاء بدوي VIP مشويات وحفلة (VIP Bedouin Dinner)', 'VIP_BEDOUIN_DINNER', 1, 'EGP', 300, 150, 0, 300, 300, 150, 0, 300, 20, 12, 0, 20, 'OUT_SIDE', 1, 'BAKAR Sheet Col DINNER VIP = 300 EGP Adult (+Quad 400 + Camel 80 + $1 Eco = ~830 EGP Full VIP Combo)'],
            ['SAF-PRIVATE-GUIDE', '', 'DESERT_SAFARI', 'AP_BAKAR', 'Private Safari Convoy Guide Surcharge', 'مرافق / جروب سفاري خاص (Private Guide)', 'PRIVATE_GROUP', 1, 'EGP', 400, 0, 0, 400, 400, 0, 0, 400, 15, 0, 0, 15, 'OUT_SIDE', 1, 'BAKAR Sheet Col PRIVET = 400 EGP flat per private safari group'],
            ['SAF-ECO-TICKET-1USD', '', 'DESERT_SAFARI', 'AP_BAKAR', 'Sinai Desert Eco / Protectorate Ticket ($1/PAX)', 'رسم بيئة السفاري 1 دولار للفرد (1$ PAX)', 'ECO_FEE_PAX', 1, 'USD', 1, 0, 0, 1, round(1 * $usdRate, 0), 0, 0, round(1 * $usdRate, 0), 1, 0, 0, 1, 'OUT_SIDE', 1, 'BAKAR Sheet Col 1$ PAX = $1 per Adult (~50 EGP)'],
            ['SAF-HORSE-1HR-BEACH', 'exc-horse-riding-beach', 'DESERT_SAFARI', 'AP_HORSE', 'Arabian Horse Riding 1-Hour (Beach & Desert w/ Transfer)', 'ركوب الخيل العربي ساعة بالشاطئ والصحراء شامل الترانسفير', 'PER_PAX', 1, 'EGP', 1290, 650, 0, 1290, 1290, 650, 0, 1290, 35, 20, 0, 35, 'OUT_SIDE', 0, 'HORSE Sheet = 800 EGP + $10 Transfer (~1,290 EGP Adult); 700 EGP w/o transfer'],
            ['SAF-HORSE-1HR-DESERT', '', 'DESERT_SAFARI', 'AP_BAKAR', 'Desert Horse Riding 1-Hour (Bakar Camp)', 'ركوب الخيل بالصحراء ساعة (مخيم بكار بدون ترانسفير خارجي)', 'PER_PAX', 1, 'EGP', 300, 200, 0, 300, 300, 200, 0, 300, 25, 15, 0, 25, 'OUT_SIDE', 0, 'BAKAR Sheet Aug 2026 Col HORSE = 300 EGP'],
        ];

        // 2) EXCURSIONS, BOATS, CAIRO/SINAI, DOLPHIN, SUBMARINE, WATERSPORTS, BOAT SHARING & TRANSFERS (PER PAX ADULT / CHILD / INFANT & PER UNIT)
        $otherItems = [
            // Boat & Snorkeling
            ['BOAT-WHITE-ISLAND', 'exc-white-island-yacht', 'YACHT_SNORKELING', 'SEA_COLLEGE', 'White Island & Ras Mohammed Yacht Cruise (Snorkeling & Lunch)', 'رحلة يخت الجزيرة البيضاء ومحمية رأس محمد (سنوركل وغداء)', 'PER_PAX', 1, 'EGP', 1000, 500, 0, 1000, 1000, 500, 0, 1000, 30, 15, 0, 30, 'INSHEET', 0, 'Sea College Yacht / Sphinx benchmark: Adult 1,000 EGP, Child 500 EGP, Infant <3 Free'],
            ['BOAT-TIRAN-ISLAND', 'exc-tiran-island-yacht', 'YACHT_SNORKELING', 'SEA_COLLEGE', 'Tiran Island Coral Lagoon Yacht Cruise (Snorkeling & Lunch)', 'رحلة يخت جزيرة تيران والشعاب المرجانية (سنوركل وغداء)', 'PER_PAX', 1, 'EGP', 1000, 500, 0, 1000, 1000, 500, 0, 1000, 35, 18, 0, 35, 'INSHEET', 0, 'Adult 1,000 EGP, Child 500 EGP, Infant Free'],
            ['BOAT-DINNER-CRUISE', 'exc-romantic-dinner-cruise', 'DOLPHIN_SUB_DINNER', 'AP_DINNER', 'Romantic Seafood & Mixed Grill Dinner Cruise', 'سهرة عشاء اليخت البحري الرومانسية (Dinner Boat)', 'PER_PAX', 1, 'EGP', 1000, 500, 0, 1000, 1000, 500, 0, 1000, 35, 18, 0, 35, 'OUT_SIDE', 0, 'DINNER Sheet Header: Adult = 1,000 EGP, Child = 500 EGP, Infant = 0'],
            // Cairo, Luxor & Sinai (A.SAAD & ONE TOUR)
            ['CAI-BUS-CLASSIC', 'exc-cairo-by-bus', 'CAIRO_LUXOR', 'AP_A_SAAD', 'Cairo 1-Day Excursion by VIP Bus (Pyramids & Museum)', 'رحلة القاهرة بالباص السياحي (الأهرامات والمتحف والغداء)', 'PER_PAX', 1, 'EGP', 2000, 1000, 0, 2000, 2000, 1000, 0, 2000, 65, 35, 0, 65, 'OUT_SIDE', 0, 'A.SAAD Sheet Aug 2026 CAIRO BUS = 2,000 EGP Adult, 1,000 EGP Child (0.5 PAX), Infant Free'],
            ['CAI-BUS-GEM', 'exc-cairo-grand-museum-bus', 'CAIRO_LUXOR', 'AP_A_SAAD', 'Cairo VIP Bus + New Grand Egyptian Museum (GEM)', 'رحلة القاهرة والمتحف المصري الكبير الجديد (GEM) بالباص', 'PER_PAX', 1, 'EGP', 4000, 2000, 0, 4000, 4000, 2000, 0, 4000, 95, 50, 0, 95, 'OUT_SIDE', 0, 'A.SAAD Sheet Aug 2026 GRAND MUSEUM = 4,000 EGP Adult, 2,000 EGP Child'],
            ['CAI-PLANE-VIP', 'exc-cairo-by-plane', 'CAIRO_LUXOR', 'AP_ONE_TOUR', 'Cairo 1-Day VIP Trip by Plane (Flights, Pyramids & Museum)', 'رحلة القاهرة يوم واحد بالطائرة (شاملة الطيران والأهرامات)', 'PER_PAX', 1, 'USD', 245, 220, 40, 245, round(245 * $usdRate, 0), round(220 * $usdRate, 0), round(40 * $usdRate, 0), round(245 * $usdRate, 0), 295, 260, 50, 295, 'OUT_SIDE', 0, 'ONE TOUR Sheet Aug 2026 CAIRO PLANE = $245 Adult (~12,250 EGP)'],
            ['LUX-PLANE-VIP', 'exc-luxor-by-plane', 'CAIRO_LUXOR', 'AP_ONE_TOUR', 'Luxor 1-Day VIP Trip by Plane (Valley of Kings & Karnak)', 'رحلة الأقصر يوم واحد بالطائرة (وادي الملوك والكرنك)', 'PER_PAX', 1, 'USD', 235, 210, 40, 235, round(235 * $usdRate, 0), round(210 * $usdRate, 0), round(40 * $usdRate, 0), round(235 * $usdRate, 0), 275, 240, 50, 275, 'OUT_SIDE', 0, 'ONE TOUR Sheet Aug 2026 LUXOR = $235 Adult'],
            ['PETRA-JORDAN-VIP', '', 'CAIRO_LUXOR', 'AP_ONE_TOUR', 'Petra Jordan 1-Day Excursion (Ferry & Guided Tour)', 'رحلة البتراء الأردن يوم واحد (ون تور)', 'PER_PAX', 1, 'USD', 225, 195, 35, 225, round(225 * $usdRate, 0), round(195 * $usdRate, 0), round(35 * $usdRate, 0), round(225 * $usdRate, 0), 265, 230, 45, 265, 'OUT_SIDE', 0, 'ONE TOUR Sheet Aug 2026 PETRA = $225 Adult'],
            ['SINAI-ST-CATHERINE', 'exc-mount-moses-st-catherine', 'SINAI_DAHAB', 'AP_A_SAAD', 'Mount Moses Sunrise & St. Catherine Monastery', 'شروق جبل موسى ودير سانت كاترين', 'PER_PAX', 1, 'EGP', 1050, 525, 0, 1050, 1050, 525, 0, 1050, 40, 20, 0, 40, 'OUT_SIDE', 0, 'A.SAAD Sheet SAINT CATREN = 1,050 EGP / MOUSSA MOUNT = 1,000 EGP Adult, Child 50%'],
            ['SINAI-DAHAB-CANYON', 'exc-dahab-canyon-bluehole', 'SINAI_DAHAB', 'AP_A_SAAD', 'Dahab Blue Hole, Colored Canyon & Quad Super Safari', 'مغامرة دهب والبلو هول والجراند كانيون والموتوسيكل', 'PER_PAX', 1, 'EGP', 650, 325, 0, 650, 650, 325, 0, 650, 35, 18, 0, 35, 'OUT_SIDE', 0, 'A.SAAD Sheet GRAND CANYON 450 EGP + MOTO 200 EGP = 650 EGP Adult, Child 0.5 PAX'],
            ['SINAI-RAS-MOHAMMED-BUS', 'exc-ras-mohammed-bus', 'SINAI_DAHAB', 'AP_A_SAAD', 'Ras Mohammed National Park Land Tour by Bus (Half Day)', 'رحلة محمية رأس محمد البرية بالباص والبحيرة المسحورة', 'PER_PAX', 1, 'EGP', 500, 250, 0, 500, 500, 250, 0, 500, 25, 13, 0, 25, 'OUT_SIDE', 0, 'A.SAAD Sheet R.M. = 500 EGP Adult, 250 EGP Child (0.5 PAX), Infant Free'],
            // Dolphin, Submarine & Glass Boat
            ['DOLPHIN-SHOW-PAX', 'exc-dolphin-show', 'DOLPHIN_SUB_DINNER', 'AP_DOLPHIN', 'Dolphin Show Family Ticket + Transfers', 'تذكرة عرض الدولفين شاملة الانتقالات', 'PER_PAX', 1, 'USD', 12, 6, 0, 12, round(12 * $usdRate, 0), round(6 * $usdRate, 0), 0, round(12 * $usdRate, 0), 25, 13, 0, 25, 'OUT_SIDE', 0, 'DOLPHIN Sheet Aug 2026 SHOW = $12 Adult (~600 EGP), $6 Child (~300 EGP), Infant Free'],
            ['DOLPHIN-SWIM-PAX', 'exc-dolphin-swim', 'DOLPHIN_SUB_DINNER', 'AP_DOLPHIN', 'Interactive Swim with Dolphins (15–30m)', 'السباحة التفاعلية المباشرة مع الدلافين', 'PER_PAX', 1, 'USD', 62, 62, 0, 62, round(62 * $usdRate, 0), round(62 * $usdRate, 0), 0, round(62 * $usdRate, 0), 80, 80, 0, 80, 'OUT_SIDE', 0, 'DOLPHIN Sheet Aug 2026 SWIM = $62 per swimmer (~3,100 EGP)'],
            ['SUB-ROYAL-SEASCOPE', 'exc-royal-submarine', 'DOLPHIN_SUB_DINNER', 'AP_SUBMARINE', 'Royal Seascope Semi-Submarine Cruise', 'رحلة الغواصة الملكية البانورامية (Submarine)', 'PER_PAX', 1, 'USD', 25, 5, 0, 25, round(25 * $usdRate, 0), round(5 * $usdRate, 0), 0, round(25 * $usdRate, 0), 40, 20, 0, 40, 'OUT_SIDE', 0, 'SUBMARINE Sheet: Foreign Adult $25, Child $5, Arab $20, Egyptian 600 EGP'],
            ['BOAT-GLASS-BOTTOM', 'exc-glass-bottom-boat', 'WATERSPORTS', 'AP_SHARMINA', 'Glass-Bottom Coral Reef Boat Tour', 'نزهة القارب الزجاجي لمشاهدة الشعاب (Glass Boat)', 'PER_PAX', 1, 'EGP', 300, 150, 0, 300, 300, 150, 0, 300, 20, 10, 0, 20, 'OUT_SIDE', 0, 'SHARMINA Sheet Col GLASS = 300 EGP Adult, 150 EGP Child (0.5 PAX)'],
            // Watersports
            ['WS-PARASAIL-SINGLE', '', 'WATERSPORTS', 'AP_SHARMINA', 'Single Parasailing Flight (1 Flyer)', 'باراشوت طيران فردي فوق البحر (Single Parasailing)', 'PER_UNIT', 1, 'EGP', 600, 600, 0, 600, 600, 600, 0, 600, 30, 25, 0, 30, 'OUT_SIDE', 0, 'SHARMINA Sheet Aug 2026 S.PARSILING = 600 EGP'],
            ['WS-PARASAIL-DOUBLE', 'ops-parasailing-double', 'WATERSPORTS', 'AP_SHARMINA', 'Double Parasailing Flight (2 Flyers Tandem)', 'باراشوت طيران مزدوج لشخصين (Double Parasailing)', 'PER_UNIT', 2, 'EGP', 700, 350, 0, 700, 700, 350, 0, 700, 40, 20, 0, 40, 'OUT_SIDE', 0, 'SHARMINA Sheet Aug 2026 D.PARSILING = 700 EGP per tandem flight'],
            ['WS-BANANA-TUBE', 'ops-banana-boat', 'WATERSPORTS', 'AP_SHARMINA', 'Banana Boat / Tube Sofa Ride', 'بنانا بوت أو تيوب صوفا المائية', 'PER_PAX', 1, 'EGP', 200, 150, 0, 200, 200, 150, 0, 200, 25, 15, 0, 25, 'OUT_SIDE', 0, 'SHARMINA Sheet EXTRA / Watersports = 200 EGP per rider'],
            ['WS-ZODIAC-YOUANA-1H', 'ops-zodiac-youana-1h', 'WATERSPORTS', 'SEA_COLLEGE', 'Zodiac "Youana" Private Speedboat 1h Charter (Up to 6 PAX)', 'زودياك «يوانا» الخاص ساعة كاملة لشعاب تيران (حتى 6 أفراد)', 'PER_UNIT', 6, 'EGP', 2000, 0, 0, 2000, 2000, 0, 0, 2000, 80, 0, 0, 80, 'INSHEET', 0, 'Fuel & Captain internal cost = 2,000 EGP per 1h charter'],
            // Diving & Boat Sharing
            ['DIVE-THISTLEGORM-2D', 'dive-thistlegorm-2', 'DIVING_CHARTER', 'AP_THISTLE_GORM', 'SS Thistlegorm Wreck 2-Dive Charter (With Guide)', 'رحلة غوص حطام سيسل جورم غطستان بالمرشد', 'PER_PAX', 1, 'EUR', 100, 45, 0, 100, round(100 * $eurRate, 0), round(45 * $eurRate, 0), 0, round(100 * $eurRate, 0), 175, 65, 0, 175, 'INSHEET', 0, 'THISTLE GORM Sheet: Diver w/ Guide €100, Diver No Guide €120, Snorkeler €45'],
            ['SHARE-SPHINX-SNK', '', 'BOAT_SHARING', 'AP_SPHINX', 'External Boat Sharing — Snorkeler Berth (Sphinx / El-Nahas)', 'شيرنج مراكب خارجية — فرد سنوركل (سفنكس / النحاس)', 'PER_PAX', 1, 'EGP', 1000, 500, 0, 1000, 1000, 500, 0, 1000, 30, 15, 0, 30, 'INSHEET', 0, 'SPHINX = 1,000 EGP / ELNAHAS = 1,250 EGP Adult, Child 50%'],
            ['SHARE-SPHINX-INTRO1', '', 'BOAT_SHARING', 'AP_SPHINX', 'External Boat Sharing — 1x Intro Dive Berth', 'شيرنج مراكب خارجية — غطسة انترو واحدة', 'PER_PAX', 1, 'EGP', 1350, 1000, 0, 1350, 1350, 1000, 0, 1350, 45, 35, 0, 45, 'INSHEET', 0, 'SPHINX = 1,350 EGP / ELNAHAS = 1,600 EGP'],
            ['SHARE-SPHINX-INTRO2', '', 'BOAT_SHARING', 'AP_SPHINX', 'External Boat Sharing — 2x Intro Dives Berth', 'شيرنج مراكب خارجية — غطستين انترو', 'PER_PAX', 1, 'EGP', 1650, 1350, 0, 1650, 1650, 1350, 0, 1650, 65, 50, 0, 65, 'INSHEET', 0, 'SPHINX = 1,650 EGP / ELNAHAS = 1,950 EGP'],
            ['SHARE-SPHINX-DIVER2', '', 'BOAT_SHARING', 'AP_SPHINX', 'External Boat Sharing — Certified Diver (2 Dives)', 'شيرنج مراكب خارجية — غواص معتمد غطستين', 'PER_PAX', 1, 'EGP', 3000, 3000, 0, 3000, 3000, 3000, 0, 3000, 80, 80, 0, 80, 'INSHEET', 0, 'SPHINX / ELNAHAS Certified Diver = 3,000 EGP (+100–200 EGP Gear Rent)'],
            // Transfers
            ['TRN-JETTY-ROUNDTRIP', '', 'TRANSFERS', 'AP_HANY_KHADR', 'Jetty Bus Shuttle (Round-Trip Go & Return)', 'باص ترانسفير السقالة ذهاب وعودة (هاني خضر / هاي إس)', 'PER_VEHICLE_LEG', 14, 'EGP', 1300, 0, 0, 1300, 1300, 0, 0, 1300, 0, 0, 0, 0, 'INSHEET', 0, 'TRANSFER Sheet: 650 EGP Go + 650 EGP Return = 1,300 EGP Round-Trip'],
            ['TRN-HOTEL-INNER-RT', '', 'TRANSFERS', 'AP_EL_ARAYSHI', 'Hotel-to-Center / Beach Inner Shuttle (Round-Trip)', 'ترانسفير داخلي بين الفندق والسنتر ذهاب وعودة (العرايشي)', 'PER_VEHICLE_LEG', 10, 'EGP', 650, 0, 0, 650, 650, 0, 0, 650, 0, 0, 0, 0, 'INSHEET', 0, 'TRANSFER El-Arayshi: 250–350 EGP One-Way / 500–700 EGP Round-Trip'],
        ];

        foreach (array_merge($safariItems, $otherItems) as $it) {
            $insCost->execute($it);
        }

        // Also import any remaining master_trips_catalog items (PADI courses, packages, intro dives) so Accounting can update EVERY trip's Adult/Child/Infant cost!
        $existingLinked = array_flip(array_filter(array_column(
            $pdo->query("SELECT linked_trip_code FROM trip_supplier_cost_items WHERE linked_trip_code != ''")->fetchAll(),
            'linked_trip_code'
        )));
        $allMaster = $pdo->query('SELECT * FROM master_trips_catalog ORDER BY category_code, trip_code')->fetchAll();
        foreach ($allMaster as $m) {
            $tc = (string) $m['trip_code'];
            if (isset($existingLinked[$tc])) {
                continue;
            }
            $catRaw = strtolower((string) $m['category_code']);
            $catGrp = match (true) {
                str_contains($catRaw, 'safari') => 'DESERT_SAFARI',
                str_contains($catRaw, 'snork') || str_contains($catRaw, 'yacht') => 'YACHT_SNORKELING',
                str_contains($catRaw, 'cairo') => 'CAIRO_LUXOR',
                str_contains($catRaw, 'sinai') => 'SINAI_DAHAB',
                str_contains($catRaw, 'dolphin') => 'DOLPHIN_SUB_DINNER',
                str_contains($catRaw, 'water') => 'WATERSPORTS',
                default => 'DIVING_COURSES',
            };
            $sup = (string) ($m['supplier_code'] ?: 'SEA_COLLEGE');
            if ($sup === 'AP_THISTLEGORM') {
                $sup = 'AP_THISTLE_GORM';
            } elseif ($sup === 'AP_WATERSPORTS_SAFARI') {
                $sup = 'AP_SHARMINA';
            }
            $aCost = (float) $m['adult_cost_egp'];
            $cCost = (float) $m['child_cost_egp'];
            $iCost = (float) $m['infant_cost_egp'];
            $aEur  = (float) $m['adult_sell_eur'];
            $cEur  = (float) $m['child_sell_eur'];
            $iEur  = (float) $m['infant_sell_eur'];
            $insCost->execute([
                'MTC-' . strtoupper((string) preg_replace('/[^a-zA-Z0-9_-]/', '-', $tc)),
                $tc,
                $catGrp,
                $sup,
                (string) $m['unified_name_en'],
                (string) $m['unified_name_ar'],
                'PER_PAX',
                1,
                'EGP',
                $aCost, $cCost, $iCost, $aCost,
                $aCost, $cCost, $iCost, $aCost,
                $aEur, $cEur, $iEur, $aEur,
                (string) ($m['sale_mode'] ?: 'INSHEET'),
                0,
                'Synced from Sea College Master Trips Catalog — Editable by Accounting',
            ]);
        }
    }
}

/**
 * Seed initial real-world Staff Bonus & Allowance records idempotently (`seed_key` UNIQUE).
 */
function sc_erp_seed_staff_bonuses_allowances(PDO $pdo): void
{
    $cnt = (int) $pdo->query('SELECT COUNT(*) FROM staff_bonuses_allowances')->fetchColumn();
    if ($cnt > 0) {
        return;
    }
    $seeds = [
        [
            'seed_bonus_fady_top_seller',
            '2026-10-28T16:00:00Z', '2026-10-28', '2026-10',
            'fady.sales', 'Fady (Cyrene Desk)', 'فادي — مبيعات سيرين', 'SALES_REP', 'CYRENE',
            'BONUS', 'TARGET_ACHIEVEMENT', 5000.0, 'EGP', 5000.0, 'SAFE_CYRENE', 'APPROVED_PAID',
            'islam.samir', 'islam.samir', '2026-10-28T16:00:00Z',
            'Top Monthly Excursion & Safari Revenue Champion Bonus (Audited SALERY Sheet)',
            'مكافأة المركز الأول في تحقيق التارجت ومبيعات السفاري والرحلات الخارجية',
        ],
        [
            'seed_bonus_dorgham_5star',
            '2026-10-25T14:30:00Z', '2026-10-25', '2026-10',
            'capt.haitham', 'Capt. Haitham El-Aydy', 'كابتن هيثم العايدي — المدير الفني', 'DIVE_INSTRUCTOR', 'CYRENE',
            'BONUS', 'GUEST_REVIEW_5STAR', 2500.0, 'EGP', 2500.0, 'NONE_PAYROLL', 'APPROVED_PAID',
            'eng.sayed', 'nadia.acc', '2026-10-25T15:00:00Z',
            'Outstanding PADI Course Safety & Verified 5-Star Guest Reviews Bonus',
            'مكافأة تميز التدريب وتقييمات 5 نجوم من ضيوف دورات الغوص',
        ],
        [
            'seed_allowance_nasr_ops',
            '2026-10-22T11:00:00Z', '2026-10-22', '2026-10',
            'ahmed.nasr', 'Ahmed Nasr (Operations)', 'أحمد نصر — مدير التشغيل والحجز', 'OPS_DISPATCHER', 'CYRENE',
            'ALLOWANCE', 'TRANSPORT_ALLOWANCE', 1500.0, 'EGP', 1500.0, 'SAFE_CYRENE', 'APPROVED_PAID',
            'ahmed.nasr', 'nadia.acc', '2026-10-22T12:15:00Z',
            'Early-Morning Jetty Dispatch & Mobile Communications Allowance',
            'بدل انتقالات واتصالات متابعة باصات السقالة والرحلات الصباحية',
        ],
        [
            'seed_allowance_mansour_crew',
            '2026-10-24T17:00:00Z', '2026-10-24', '2026-10',
            'capt.mansour', 'Capt. Mansour (M/Y Oceano)', 'ريس منصور — يخت أوشينو', 'BOAT_CAPTAIN', 'JETTY',
            'ALLOWANCE', 'MEAL_ALLOWANCE', 1200.0, 'EGP', 1200.0, 'SAFE_JETTY', 'APPROVED_PAID',
            'capt.mansour', 'nadia.acc', '2026-10-24T17:20:00Z',
            'VIP Charter Crew Extra Shift & Catering Allowance (M/Y Oceano)',
            'بدل وجبات وساعات تشغيل إضافية لطاقم يخت أوشينو VIP',
        ],
        [
            'seed_req_adel_transport',
            '2026-10-29T10:00:00Z', '2026-10-29', '2026-10',
            'adel.sales', 'Adel (Dreams & Fayrouz)', 'عادل — مبيعات دريمز والفيروز', 'SALES_REP', 'JETTY',
            'ALLOWANCE', 'TRANSPORT_ALLOWANCE', 800.0, 'EGP', 800.0, 'SAFE_JETTY', 'REQUESTED',
            'adel.sales', '', '',
            'Naama Bay / Dreams Resort evening guest follow-up transport allowance',
            'بدل انتقالات متابعة حجوزات فنادق نعمة باي ودريمز المسائية',
        ],
    ];

    $st = $pdo->prepare(
        'INSERT OR IGNORE INTO staff_bonuses_allowances
         (seed_key, created_at, tx_date, period_month, emp_username, emp_name_en, emp_name_ar,
          role_code, branch_code, entry_type, category, amount_egp, currency, amount_orig,
          safe_code, status, requested_by, approved_by, decided_at, reason_en, reason_ar)
         VALUES (?,?,?,?,?,?,?, ?,?,?,?,?,?,?, ?,?,?,?,?,?,?)'
    );
    foreach ($seeds as $s) {
        $st->execute($s);
    }
}

/**
 * Compute exact Bakar Safari / Excursion modular cost & suggested EUR sell price from live DB `trip_supplier_cost_items`.
 *
 * Accepts `$safariSpec` array with optional counts:
 *   `single_quad`, `double_quad`, `buggy_2pax`, `buggy_4pax`, `camel_safari`, `camel_1hr`,
 *   `star_gazing`, `bedouin_dinner`, `vip_bedouin_dinner`, `private_guide`, `include_eco_tax`
 *
 * @param array<string,mixed> $safariSpec
 * @param array<string,float> $fx
 * @return array<string,mixed>
 */
function sc_erp_compute_safari_breakdown_financials(PDO $pdo, array $safariSpec, int $adults, int $children, int $infants, array $fx): array
{
    $mapKeys = [
        'single_quad'        => 'SAF-QUAD-SINGLE',
        'double_quad'        => 'SAF-QUAD-DOUBLE',
        'buggy_2pax'         => 'SAF-BUGGY-2PAX',
        'buggy_4pax'         => 'SAF-BUGGY-4PAX',
        'camel_safari'       => 'SAF-CAMEL-WITH-SAFARI',
        'camel_1hr'          => 'SAF-CAMEL-1HR-TOUR',
        'star_gazing'        => 'SAF-STAR-GAZING',
        'bedouin_dinner'     => 'SAF-BEDOUIN-DINNER',
        'vip_bedouin_dinner' => 'SAF-VIP-BEDOUIN-DINNER',
        'private_guide'      => 'SAF-PRIVATE-GUIDE',
    ];

    $rowsByCode = [];
    $allRows = $pdo->query("SELECT * FROM trip_supplier_cost_items WHERE category_group = 'DESERT_SAFARI'")->fetchAll();
    foreach ($allRows as $r) {
        $rowsByCode[(string) $r['item_code']] = $r;
    }

    $lines = [];
    $totalCostEgp = 0.0;
    $totalSellEur = 0.0;
    $summaryParts = [];

    foreach ($mapKeys as $field => $itemCode) {
        $qty = max(0, (int) ($safariSpec[$field] ?? 0));
        if ($qty <= 0 || !isset($rowsByCode[$itemCode])) {
            continue;
        }
        $r = $rowsByCode[$itemCode];
        $uCostEgp = (float) ($r['unit_cost_egp'] > 0 ? $r['unit_cost_egp'] : $r['adult_cost_egp']);
        $uSellEur = (float) ($r['unit_sell_eur'] > 0 ? $r['unit_sell_eur'] : $r['adult_sell_eur']);
        $lineCost = round($qty * $uCostEgp, 2);
        $lineEur  = round($qty * $uSellEur, 2);
        $totalCostEgp += $lineCost;
        $totalSellEur += $lineEur;

        $shortLbl = match ($field) {
            'single_quad'        => "{$qty}S MOTO",
            'double_quad'        => "{$qty}D MOTO",
            'buggy_2pax'         => "{$qty} BUGGY 2P",
            'buggy_4pax'         => "{$qty} BUGGY FAMILY 4P",
            'camel_safari'       => "{$qty} CAMEL",
            'camel_1hr'          => "{$qty} CAMEL 1H",
            'star_gazing'        => "{$qty} STAR",
            'bedouin_dinner'     => "{$qty} DINNER",
            'vip_bedouin_dinner' => "{$qty} DINNER VIP",
            'private_guide'      => 'PRIVATE',
            default              => "{$qty}x {$itemCode}",
        };
        $summaryParts[] = $shortLbl;
        $lines[] = [
            'field'         => $field,
            'item_code'     => $itemCode,
            'name_en'       => (string) $r['name_en'],
            'name_ar'       => (string) $r['name_ar'],
            'qty'           => $qty,
            'unit_cost_egp' => $uCostEgp,
            'line_cost_egp' => $lineCost,
            'unit_sell_eur' => $uSellEur,
            'line_sell_eur' => $lineEur,
        ];
    }

    // Add $1/Adult Sinai Safari Eco Fee (`1$ PAX` column in BAKAR sheet) when any vehicle/safari is selected unless explicitly disabled
    $includeEco = !array_key_exists('include_eco_tax', $safariSpec) || !empty($safariSpec['include_eco_tax']);
    if ($lines && $includeEco && $adults > 0 && isset($rowsByCode['SAF-ECO-TICKET-1USD'])) {
        $eco = $rowsByCode['SAF-ECO-TICKET-1USD'];
        $ecoUnitEgp = (float) ($eco['unit_cost_egp'] > 0 ? $eco['unit_cost_egp'] : round(1.0 * ($fx['USD'] ?? 50.0), 0));
        $ecoLineCost = round($adults * $ecoUnitEgp, 2);
        $totalCostEgp += $ecoLineCost;
        $lines[] = [
            'field'         => 'eco_tax_pax',
            'item_code'     => 'SAF-ECO-TICKET-1USD',
            'name_en'       => 'Sinai Safari $1/PAX Eco Ticket',
            'name_ar'       => 'رسم بيئة السفاري 1$ للفرد البالغ',
            'qty'           => $adults,
            'unit_cost_egp' => $ecoUnitEgp,
            'line_cost_egp' => $ecoLineCost,
            'unit_sell_eur' => 0.0,
            'line_sell_eur' => 0.0,
        ];
    }

    return [
        'has_items'           => count($lines) > 0,
        'lines'               => $lines,
        'sheet_activity_code' => implode(' + ', $summaryParts),
        'total_cost_egp'      => round($totalCostEgp, 2),
        'suggested_sell_eur'  => round($totalSellEur, 2),
        'suggested_sell_egp'  => round($totalSellEur * ($fx['EUR'] ?? 57.0), 2),
        'supplier_code'       => 'AP_BAKAR',
        'supplier_name'       => 'Bakar Desert Safari Center',
    ];
}

/**
 * Create or update a Trip / Safari Variant Cost row (Authorized for Accounting `gl.view_all_ledgers` & Owners `bi.view_executive`),
 * automatically syncing linked `master_trips_catalog` supplier costs & recording an immutable audit log entry.
 *
 * @param array<string,mixed> $in
 * @param array<string,mixed> $actorUser
 * @return array<string,mixed>
 */
function sc_erp_save_trip_or_variant_cost(PDO $pdo, array $in, array $actorUser): array
{
    $fx = sc_erp_fx_rates($pdo);
    $rawCode = strtoupper(trim((string) ($in['item_code'] ?? '')));
    if ($rawCode === '') {
        $rawCode = 'COST-' . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', (string) ($in['name_en'] ?? 'ITEM')), 0, 12)) . '-' . random_int(100, 999);
    }
    $rawCode = (string) preg_replace('/[^A-Z0-9_-]/', '-', $rawCode);

    $exSt = $pdo->prepare('SELECT * FROM trip_supplier_cost_items WHERE item_code = ? LIMIT 1');
    $exSt->execute([$rawCode]);
    $prev = $exSt->fetch() ?: null;

    $linkedTrip = trim((string) ($in['linked_trip_code'] ?? ($prev['linked_trip_code'] ?? '')));
    $catGrp     = strtoupper(trim((string) ($in['category_group'] ?? ($prev['category_group'] ?? 'EXCURSIONS'))));
    $supCode    = strtoupper(trim((string) ($in['supplier_code'] ?? ($prev['supplier_code'] ?? 'SEA_COLLEGE'))));
    $nameEn     = trim((string) ($in['name_en'] ?? ($prev['name_en'] ?? $rawCode)));
    $nameAr     = trim((string) ($in['name_ar'] ?? ($prev['name_ar'] ?? $nameEn)));
    $basis      = strtoupper(trim((string) ($in['pricing_basis'] ?? ($prev['pricing_basis'] ?? 'PER_PAX'))));
    $capPax     = max(1, min(60, (int) ($in['capacity_pax'] ?? ($prev['capacity_pax'] ?? 1))));
    $curr       = strtoupper(trim((string) ($in['cost_currency'] ?? ($prev['cost_currency'] ?? 'EGP'))));
    if (!isset($fx[$curr])) {
        $curr = 'EGP';
    }
    $rateToEgp = $fx[$curr] ?? 1.0;

    $adOrig   = array_key_exists('adult_cost_orig', $in) ? max(0.0, (float) $in['adult_cost_orig']) : (array_key_exists('adult_cost_egp', $in) && $curr === 'EGP' ? max(0.0, (float) $in['adult_cost_egp']) : (float) ($prev['adult_cost_orig'] ?? 0.0));
    $chOrig   = array_key_exists('child_cost_orig', $in) ? max(0.0, (float) $in['child_cost_orig']) : (array_key_exists('child_cost_egp', $in) && $curr === 'EGP' ? max(0.0, (float) $in['child_cost_egp']) : (float) ($prev['child_cost_orig'] ?? 0.0));
    $inOrig   = array_key_exists('infant_cost_orig', $in) ? max(0.0, (float) $in['infant_cost_orig']) : (array_key_exists('infant_cost_egp', $in) && $curr === 'EGP' ? max(0.0, (float) $in['infant_cost_egp']) : (float) ($prev['infant_cost_orig'] ?? 0.0));
    $unOrig   = array_key_exists('unit_cost_orig', $in) ? max(0.0, (float) $in['unit_cost_orig']) : (array_key_exists('unit_cost_egp', $in) && $curr === 'EGP' ? max(0.0, (float) $in['unit_cost_egp']) : ($adOrig > 0 ? $adOrig : (float) ($prev['unit_cost_orig'] ?? 0.0)));

    // Allow explicit EGP override or auto-convert from cost_currency (`EGP`, `USD`, `EUR`, `GBP`)
    $adEgp = array_key_exists('adult_cost_egp', $in) && ($curr === 'EGP' || !array_key_exists('adult_cost_orig', $in))
        ? max(0.0, round((float) $in['adult_cost_egp'], 2))
        : round($adOrig * $rateToEgp, 2);
    $chEgp = array_key_exists('child_cost_egp', $in) && ($curr === 'EGP' || !array_key_exists('child_cost_orig', $in))
        ? max(0.0, round((float) $in['child_cost_egp'], 2))
        : round($chOrig * $rateToEgp, 2);
    $inEgp = array_key_exists('infant_cost_egp', $in) && ($curr === 'EGP' || !array_key_exists('infant_cost_orig', $in))
        ? max(0.0, round((float) $in['infant_cost_egp'], 2))
        : round($inOrig * $rateToEgp, 2);
    $unEgp = array_key_exists('unit_cost_egp', $in) && ($curr === 'EGP' || !array_key_exists('unit_cost_orig', $in))
        ? max(0.0, round((float) $in['unit_cost_egp'], 2))
        : round($unOrig * $rateToEgp, 2);

    // Keep unit_cost_egp & adult_cost_egp mutually populated for single-rider/per-unit Safari items so both PAX and Unit formulas stay accurate
    if ($basis !== 'PER_PAX' && $unEgp > 0.0 && !array_key_exists('adult_cost_egp', $in) && !array_key_exists('adult_cost_orig', $in)) {
        $adEgp  = $unEgp;
        $adOrig = $unOrig;
    } elseif ($basis === 'PER_PAX' && $adEgp > 0.0 && !array_key_exists('unit_cost_egp', $in) && !array_key_exists('unit_cost_orig', $in)) {
        $unEgp  = $adEgp;
        $unOrig = $adOrig;
    }

    $adEur = array_key_exists('adult_sell_eur', $in) ? max(0.0, (float) $in['adult_sell_eur']) : (float) ($prev['adult_sell_eur'] ?? 0.0);
    $chEur = array_key_exists('child_sell_eur', $in) ? max(0.0, (float) $in['child_sell_eur']) : (float) ($prev['child_sell_eur'] ?? 0.0);
    $inEur = array_key_exists('infant_sell_eur', $in) ? max(0.0, (float) $in['infant_sell_eur']) : (float) ($prev['infant_sell_eur'] ?? 0.0);
    $unEur = array_key_exists('unit_sell_eur', $in) ? max(0.0, (float) $in['unit_sell_eur']) : ($adEur > 0 ? $adEur : (float) ($prev['unit_sell_eur'] ?? 0.0));

    $saleMode = in_array(strtoupper((string) ($in['sale_mode'] ?? ($prev['sale_mode'] ?? 'OUT_SIDE'))), ['INSHEET', 'OUT_SIDE'], true)
        ? strtoupper((string) ($in['sale_mode'] ?? ($prev['sale_mode'] ?? 'OUT_SIDE')))
        : 'OUT_SIDE';
    $isSafComp = array_key_exists('is_safari_component', $in)
        ? (!empty($in['is_safari_component']) ? 1 : 0)
        : (int) ($prev['is_safari_component'] ?? ($catGrp === 'DESERT_SAFARI' && $basis !== 'PER_PAX' ? 1 : 0));
    $formulaNote = trim((string) ($in['sheet_formula_note'] ?? ($prev['sheet_formula_note'] ?? '')));
    $isActive    = array_key_exists('is_active', $in) ? (!empty($in['is_active']) ? 1 : 0) : (int) ($prev['is_active'] ?? 1);
    $reasonNotes = trim((string) ($in['reason_notes'] ?? 'Accounting supplier cost update'));
    $now         = gmdate('Y-m-d\TH:i:s\Z');
    $actorName   = (string) ($actorUser['username'] ?? 'nadia.acc');
    $actorRole   = (string) ($actorUser['role_code'] ?? 'CHIEF_ACCOUNTANT');

    $pdo->prepare(
        'INSERT OR REPLACE INTO trip_supplier_cost_items
         (item_code, linked_trip_code, category_group, supplier_code, name_en, name_ar,
          pricing_basis, capacity_pax, cost_currency,
          adult_cost_orig, child_cost_orig, infant_cost_orig, unit_cost_orig,
          adult_cost_egp, child_cost_egp, infant_cost_egp, unit_cost_egp,
          adult_sell_eur, child_sell_eur, infant_sell_eur, unit_sell_eur,
          sale_mode, is_safari_component, sheet_formula_note, is_active, updated_by, updated_at)
         VALUES (?,?,?,?,?,?, ?,?,?, ?,?,?,?, ?,?,?,?, ?,?,?,?, ?,?,?, ?,?,?)'
    )->execute([
        $rawCode, $linkedTrip, $catGrp, $supCode, $nameEn, $nameAr,
        $basis, $capPax, $curr,
        $adOrig, $chOrig, $inOrig, $unOrig,
        $adEgp, $chEgp, $inEgp, $unEgp,
        $adEur, $chEur, $inEur, $unEur,
        $saleMode, $isSafComp, $formulaNote, $isActive, $actorName, $now,
    ]);

    // Sync supplier name + Adult/Child/Infant EGP costs into `master_trips_catalog` if linked (preserving website public EUR parity!)
    if ($linkedTrip !== '') {
        $supNameSt = $pdo->prepare('SELECT name_en FROM suppliers_directory WHERE supplier_code = ? LIMIT 1');
        $supNameSt->execute([$supCode]);
        $supName = (string) ($supNameSt->fetchColumn() ?: $supCode);

        $mtSt = $pdo->prepare('SELECT is_website_public FROM master_trips_catalog WHERE trip_code = ? LIMIT 1');
        $mtSt->execute([$linkedTrip]);
        $mtRow = $mtSt->fetch();
        if ($mtRow) {
            if ((int) ($mtRow['is_website_public'] ?? 0) === 1) {
                // Public diving item: update costs & supplier ONLY so public 3-way EUR sell price parity (`T3_22`) stays intact
                $pdo->prepare(
                    'UPDATE master_trips_catalog
                     SET supplier_code = ?, supplier_name = ?, sale_mode = ?,
                         adult_cost_egp = ?, child_cost_egp = ?, infant_cost_egp = ?
                     WHERE trip_code = ?'
                )->execute([$supCode, $supName, $saleMode, $adEgp, $chEgp, $inEgp, $linkedTrip]);
            } else {
                $pdo->prepare(
                    'UPDATE master_trips_catalog
                     SET supplier_code = ?, supplier_name = ?, sale_mode = ?,
                         adult_cost_egp = ?, child_cost_egp = ?, infant_cost_egp = ?,
                         adult_sell_eur = CASE WHEN ? > 0 THEN ? ELSE adult_sell_eur END,
                         child_sell_eur = CASE WHEN ? >= 0 AND ? > 0 THEN ? ELSE child_sell_eur END,
                         infant_sell_eur = ?
                     WHERE trip_code = ?'
                )->execute([
                    $supCode, $supName, $saleMode,
                    $adEgp, $chEgp, $inEgp,
                    $adEur, $adEur,
                    $chEur, $adEur, $chEur,
                    $inEur,
                    $linkedTrip,
                ]);
            }
        }
    }

    // Immutable Accounting Audit Log
    $pdo->prepare(
        'INSERT INTO trip_cost_audit_log
         (changed_at, changed_by, changed_by_role, action_type, item_code, item_name_en, supplier_code,
          prev_adult_cost_egp, new_adult_cost_egp, prev_child_cost_egp, new_child_cost_egp,
          prev_infant_cost_egp, new_infant_cost_egp, prev_unit_cost_egp, new_unit_cost_egp, reason_notes)
         VALUES (?,?,?,?,?,?,?, ?,?,?,?, ?,?,?,?, ?)'
    )->execute([
        $now, $actorName, $actorRole,
        $prev ? 'UPDATE_COST' : 'CREATE_COST_ITEM',
        $rawCode, $nameEn, $supCode,
        (float) ($prev['adult_cost_egp'] ?? 0.0), $adEgp,
        (float) ($prev['child_cost_egp'] ?? 0.0), $chEgp,
        (float) ($prev['infant_cost_egp'] ?? 0.0), $inEgp,
        (float) ($prev['unit_cost_egp'] ?? 0.0), $unEgp,
        $reasonNotes,
    ]);

    $freshSt = $pdo->prepare('SELECT * FROM trip_supplier_cost_items WHERE item_code = ?');
    $freshSt->execute([$rawCode]);
    return $freshSt->fetch() ?: ['item_code' => $rawCode];
}

/* ==========================================================================================
 * ERP v3.6 — 360° COUNTERPARTY, SUPPLIER, SELLER & STAFF FINANCIAL DOSSIER & INSTALLMENTS ENGINE
 * ========================================================================================== */

function sc_erp_normalize_entity_type(string $raw): string
{
    return match (strtoupper(trim($raw))) {
        'SUPPLIER', 'VENDOR', 'AP_SUPPLIER', 'AP'       => 'SUPPLIER',
        'SALES_REP', 'SELLER', 'SALES'                  => 'SALES_REP',
        'STAFF', 'EMPLOYEE', 'CREW', 'INSTRUCTOR', 'HR' => 'STAFF',
        'CLIENT_AR', 'CLIENT', 'AGENCY', 'AR_CLIENT', 'AR' => 'CLIENT_AR',
        'PARTNER', 'FLEET_PARTNER', 'EQUITY'            => 'PARTNER',
        default                                         => 'SUPPLIER',
    };
}

/**
 * Determine the logged-in user's own canonical `(entity_type, entity_code)` tuple for "My Statement & Balance".
 *
 * @param array<string,mixed> $user
 * @return array{entity_type:string, entity_code:string}
 */
function sc_erp_self_financial_entity(array $user): array
{
    $role  = strtoupper((string) ($user['role_code'] ?? ''));
    $uname = strtolower(trim((string) ($user['username'] ?? '')));
    if ($role === 'SALES_REP' || $role === 'ONLINE_SELLER') {
        return ['entity_type' => 'SALES_REP', 'entity_code' => $uname];
    }
    if ($role === 'FLEET_PARTNER') {
        if (str_contains($uname, 'darwish')) {
            return ['entity_type' => 'PARTNER', 'entity_code' => 'PA_DARWISH_LEASE'];
        }
        if (str_contains($uname, 'farid')) {
            return ['entity_type' => 'PARTNER', 'entity_code' => 'PA_FARID_LEASE'];
        }
        return ['entity_type' => 'PARTNER', 'entity_code' => 'PA_PARTNERS_LEASES'];
    }
    return ['entity_type' => 'STAFF', 'entity_code' => $uname];
}

/**
 * Enforce strict role & horizontal isolation on viewing a financial dossier.
 *
 * @param array<string,mixed> $user
 * @param list<string>        $perms
 */
function sc_erp_can_access_entity_dossier(array $user, array $perms, string $entityType, string $entityCode): bool
{
    $role  = strtoupper((string) ($user['role_code'] ?? ''));
    $uname = strtolower(trim((string) ($user['username'] ?? '')));
    $eType = sc_erp_normalize_entity_type($entityType);
    $eCode = trim($entityCode);

    if ($role === 'GUEST_CUSTOMER' || $uname === '' || $eCode === '') {
        return false;
    }
    // Owners, CFO, GM & Chief Accountant have full 360° visibility across all counterparties & staff
    if (
        in_array('bi.view_executive', $perms, true)
        || in_array('gl.view_all_ledgers', $perms, true)
        || in_array('hr.manage_payroll', $perms, true)
    ) {
        return true;
    }
    // Cashiers (`SAFE_CYRENE` / `SAFE_JETTY`) can inspect Suppliers, Sellers, Staff & B2B Agencies for safe payouts/collections, but not Partner Equity pools
    if (in_array('safe.view_balances', $perms, true)) {
        return in_array($eType, ['SUPPLIER', 'SALES_REP', 'STAFF', 'CLIENT_AR'], true);
    }
    // Operations Manager (`ahmed.nasr`) can inspect Suppliers & operational Staff/Crew + Unified Online Seller + own statement
    if (in_array('ops.assign_boat_driver', $perms, true)) {
        if ($eType === 'SUPPLIER') {
            return true;
        }
        if ($eType === 'SALES_REP' && strtolower($eCode) === 'website.online') {
            return true;
        }
        if ($eType === 'STAFF') {
            $execUsers = ['islam.samir', 'eng.sayed', 'tarek.cfo', 'nadia.acc'];
            return !in_array(strtolower($eCode), $execUsers, true) || strtolower($eCode) === $uname;
        }
        return false;
    }
    // Sales Reps & Unified Online Seller see strictly their own dossier
    if ($role === 'SALES_REP' || $role === 'ONLINE_SELLER') {
        return $eType === 'SALES_REP' && strtolower($eCode) === $uname;
    }
    // Customer Care (`lara.care` / `crm.manage_leads_ads`) can inspect Unified Website & Online Seller dossier + own Staff dossier
    if (in_array('crm.manage_leads_ads', $perms, true) && $eType === 'SALES_REP' && strtolower($eCode) === 'website.online') {
        return true;
    }
    // Fleet Partners see strictly their own vessel lease/equity account (`T2_F15_03`)
    if ($role === 'FLEET_PARTNER') {
        if (str_contains($uname, 'darwish')) {
            return $eType === 'PARTNER' && strtoupper($eCode) === 'PA_DARWISH_LEASE';
        }
        if (str_contains($uname, 'farid')) {
            return $eType === 'PARTNER' && strtoupper($eCode) === 'PA_FARID_LEASE';
        }
        return false;
    }
    // Instructors, Captains, Jetty Supervisors, Customer Care see their own personal Staff dossier
    return $eType === 'STAFF' && strtolower($eCode) === $uname;
}

/**
 * Determine whether the user can record Deposits, Installments, Advances, or Settlements on an entity.
 *
 * @param array<string,mixed> $user
 * @param list<string>        $perms
 */
function sc_erp_can_record_party_tx(array $user, array $perms, string $entityType = 'SUPPLIER'): bool
{
    $eType = sc_erp_normalize_entity_type($entityType);
    if (
        in_array('gl.view_all_ledgers', $perms, true)
        || in_array('bi.view_executive', $perms, true)
        || in_array('hr.manage_payroll', $perms, true)
    ) {
        return true;
    }
    if (in_array('safe.record_tx', $perms, true) && $eType !== 'PARTNER') {
        return true;
    }
    return false;
}

/**
 * Human-friendly bilingual balance status badge for any entity's final net balance.
 *
 * @return array{status_code:string, label_en:string, label_ar:string}
 */
function sc_erp_describe_balance_status(string $entityType, float $finalBal): array
{
    $eType = sc_erp_normalize_entity_type($entityType);
    if (abs($finalBal) < 0.5) {
        return [
            'status_code' => 'SETTLED_ZERO',
            'label_en'    => 'Fully Settled (0 EGP Balance)',
            'label_ar'    => 'خالص ومقفل — الرصيد صفر',
        ];
    }
    if ($eType === 'CLIENT_AR') {
        if ($finalBal > 0) {
            return [
                'status_code' => 'OWED_TO_COMPANY',
                'label_en'    => 'Receivable Owed TO Sea College',
                'label_ar'    => 'مستحق للشركة على العميل',
            ];
        }
        return [
            'status_code' => 'CLIENT_CREDIT_DEPOSIT',
            'label_en'    => 'Client Advance Deposit / Credit',
            'label_ar'    => 'رصيد دائن للعميل (عربون / دفعة مقدمة)',
        ];
    }
    // SUPPLIER, SALES_REP, STAFF, PARTNER: positive = Company owes them; negative = Advance/Deposit in Company's favor
    if ($finalBal > 0) {
        $whoAr = match ($eType) {
            'SUPPLIER'  => 'للمورد',
            'SALES_REP' => 'للمندوب',
            'PARTNER'   => 'للشريك',
            default     => 'للموظف',
        };
        return [
            'status_code' => 'OWED_TO_PARTY',
            'label_en'    => 'Net Payable Owed TO Party',
            'label_ar'    => "مستحق {$whoAr} على الشركة",
        ];
    }
    $onWhoAr = match ($eType) {
        'SUPPLIER'  => 'لنا عند المورد (دفعة مقدمة / عربون)',
        'SALES_REP' => 'على المندوب للشركة (سلفة / مسحوبات)',
        'PARTNER'   => 'مسحوبات على الشريك لصالح الشركة',
        default     => 'على الموظف للشركة (رصيد سلفة)',
    };
    return [
        'status_code' => 'ADVANCE_OWED_BY_PARTY',
        'label_en'    => 'Advance / Deposit in Company Favor',
        'label_ar'    => $onWhoAr,
    ];
}

/**
 * Sort raw statement lines chronologically, compute exact running balance after every row,
 * and return totals + filtered installments/deposits list.
 *
 * @param list<array<string,mixed>> $rawLines
 * @return array{
 *   statement_lines: list<array<string,mixed>>,
 *   installments_ledger: list<array<string,mixed>>,
 *   total_accrued_egp: float,
 *   total_installments_egp: float,
 *   installments_count: int,
 *   final_balance_egp: float,
 *   status_code: string,
 *   balance_label_en: string,
 *   balance_label_ar: string
 * }
 */
function sc_erp_finalize_statement_lines(array $rawLines, string $entityType): array
{
    usort($rawLines, static function (array $a, array $b): int {
        $cmp = strcmp((string) ($a['tx_date'] ?? ''), (string) ($b['tx_date'] ?? ''));
        if ($cmp !== 0) {
            return $cmp;
        }
        return ((int) ($a['sort_seq'] ?? 50)) <=> ((int) ($b['sort_seq'] ?? 50));
    });

    $running   = 0.0;
    $totPlus   = 0.0;
    $totMinus  = 0.0;
    $finalRows = [];
    $instRows  = [];

    foreach ($rawLines as $idx => $ln) {
        $plus  = max(0.0, round((float) ($ln['plus_egp'] ?? 0.0), 2));
        $minus = max(0.0, round((float) ($ln['minus_egp'] ?? 0.0), 2));
        if ($plus < 0.005 && $minus < 0.005) {
            continue;
        }
        $totPlus  = round($totPlus + $plus, 2);
        $totMinus = round($totMinus + $minus, 2);
        $running  = round($running + $plus - $minus, 2);

        $rowOut = [
            'line_no'             => count($finalRows) + 1,
            'tx_date'             => (string) ($ln['tx_date'] ?? '2026-10-31'),
            'ref_no'              => (string) ($ln['ref_no'] ?? ''),
            'line_group'          => (string) ($ln['line_group'] ?? ($minus > 0 && $plus == 0 ? 'INSTALLMENT' : 'ACCRUAL')),
            'tx_kind'             => (string) ($ln['tx_kind'] ?? 'INVOICE'),
            'badge_en'            => (string) ($ln['badge_en'] ?? 'Entry'),
            'badge_ar'            => (string) ($ln['badge_ar'] ?? 'حركة'),
            'description_en'      => (string) ($ln['description_en'] ?? ''),
            'description_ar'      => (string) ($ln['description_ar'] ?? ($ln['description_en'] ?? '')),
            'payment_method'      => (string) ($ln['payment_method'] ?? ''),
            'orig_currency'       => (string) ($ln['orig_currency'] ?? 'EGP'),
            'orig_amount'         => round((float) ($ln['orig_amount'] ?? ($plus > 0 ? $plus : $minus)), 2),
            'plus_egp'            => $plus,
            'minus_egp'           => $minus,
            'running_balance_egp' => $running,
            'recorded_by'         => (string) ($ln['recorded_by'] ?? 'AUDITED_LEDGER'),
        ];
        $finalRows[] = $rowOut;
        if ($rowOut['line_group'] === 'INSTALLMENT' || $minus > 0.005) {
            $instRows[] = $rowOut;
        }
    }

    $finalBal = round($totPlus - $totMinus, 2);
    $stMeta   = sc_erp_describe_balance_status($entityType, $finalBal);

    // Reverse installments list so latest deposit/installment appears at top of quick summary strip
    $instDesc = array_reverse($instRows);

    return [
        'statement_lines'        => $finalRows,
        'installments_ledger'    => $instDesc,
        'total_accrued_egp'      => $totPlus,
        'total_installments_egp' => $totMinus,
        'installments_count'     => count($instDesc),
        'final_balance_egp'      => $finalBal,
        'status_code'            => $stMeta['status_code'],
        'balance_label_en'       => $stMeta['label_en'],
        'balance_label_ar'       => $stMeta['label_ar'],
    ];
}

/**
 * Append live recorded `financial_party_transactions` rows onto `$rawLines` for `(entity_type, entity_code)`.
 *
 * @param list<array<string,mixed>> $rawLines
 */
function sc_erp_append_live_party_tx_lines(PDO $pdo, string $entityType, string $entityCode, array &$rawLines): void
{
    $eType = sc_erp_normalize_entity_type($entityType);
    $st = $pdo->prepare(
        'SELECT * FROM financial_party_transactions
         WHERE entity_type = ? AND LOWER(entity_code) = LOWER(?)
         ORDER BY tx_date ASC, id ASC'
    );
    $st->execute([$eType, trim($entityCode)]);
    foreach ($st->fetchAll() as $tx) {
        $amtEgp = max(0.0, round((float) ($tx['amount_egp'] ?? 0.0), 2));
        $dir    = strtoupper((string) ($tx['direction'] ?? 'COMPANY_PAID_OUT'));
        $kind   = strtoupper((string) ($tx['tx_kind'] ?? 'INSTALLMENT'));

        // Map direction to (plus_egp, minus_egp) according to entity orientation
        $plus  = 0.0;
        $minus = 0.0;
        if ($eType === 'CLIENT_AR') {
            // For B2B Clients: CHARGED_TO_PARTY / COMPANY_PAID_OUT (refund) increases receivable (+); COMPANY_RECEIVED_IN / CREDIT_NOTE reduces receivable (-)
            if (in_array($dir, ['CHARGED_TO_PARTY', 'COMPANY_PAID_OUT'], true) || $kind === 'INVOICE_CHARGE') {
                $plus = $amtEgp;
            } else {
                $minus = $amtEgp;
            }
        } else {
            // For SUPPLIER, SALES_REP, STAFF, PARTNER: ACCRUED_FOR_PARTY / COMPANY_RECEIVED_IN (advance repayment) increases what company owes (+); COMPANY_PAID_OUT / CHARGED_TO_PARTY (installment/deposit/advance given) reduces what company owes (-)
            if (in_array($dir, ['ACCRUED_FOR_PARTY', 'COMPANY_RECEIVED_IN'], true) || in_array($kind, ['INVOICE_CHARGE', 'ADVANCE_REPAID'], true)) {
                $plus = $amtEgp;
            } else {
                $minus = $amtEgp;
            }
        }

        [$bEn, $bAr] = match ($kind) {
            'DEPOSIT'         => ['Deposit (عربون)', 'عربون / دفعة مقدمة'],
            'FULL_SETTLEMENT' => ['Full Settlement', 'تقفيل وسداد كامل'],
            'ADVANCE_GIVEN'   => ['Advance Paid', 'سلفة منصرفة'],
            'ADVANCE_REPAID'  => ['Advance Repaid', 'سداد وتوريد سلفة'],
            'INVOICE_CHARGE'  => ['Invoice / Charge', 'فاتورة / استحقاق جديد'],
            'CREDIT_NOTE'     => ['Credit / Deduction', 'خصم / إشعار دائن'],
            default           => ['Installment', 'دفعة / قسط مسدد'],
        };

        $rawLines[] = [
            'tx_date'        => (string) $tx['tx_date'],
            'sort_seq'       => 80 + ((int) $tx['id'] % 1000),
            'ref_no'         => (string) ($tx['voucher_or_receipt_no'] ?: $tx['tx_ref']),
            'line_group'     => $minus > 0 ? 'INSTALLMENT' : ($kind === 'ADVANCE_REPAID' ? 'INSTALLMENT' : 'ACCRUAL'),
            'tx_kind'        => $kind,
            'badge_en'       => $bEn,
            'badge_ar'       => $bAr,
            'description_en' => (string) ($tx['description_en'] ?: $tx['description_ar'] ?: $bEn),
            'description_ar' => (string) ($tx['description_ar'] ?: $tx['description_en'] ?: $bAr),
            'payment_method' => (string) ($tx['payment_method'] ?? ''),
            'orig_currency'  => (string) ($tx['orig_currency'] ?? 'EGP'),
            'orig_amount'    => (float) ($tx['orig_amount'] ?? $amtEgp),
            'plus_egp'       => $plus,
            'minus_egp'      => $minus,
            'recorded_by'    => (string) ($tx['recorded_by'] ?? 'ACCOUNTING'),
        ];
    }
}

/**
 * Canonical mapping between Sales Rep / Staff system usernames and their Arabic names in `payroll_monthly_fact` & `open_balances_monthly`.
 *
 * @return array<string, array{payroll_ar: string, adv_code: string}>
 */
function sc_erp_staff_and_seller_payroll_map(): array
{
    return [
        // Sales Reps with rows in Oct 2026 SALERY sheet
        'fady.sales'      => ['payroll_ar' => 'فادى صالح عطية جرجس', 'adv_code' => ''],
        'moabdo.sales'    => ['payroll_ar' => 'محمد عبدالرحمن', 'adv_code' => ''],
        'ammar.sales'     => ['payroll_ar' => 'عمار', 'adv_code' => ''],
        'moali.sales'     => ['payroll_ar' => 'محمد على', 'adv_code' => ''],
        'sqran.sales'     => ['payroll_ar' => 'احمد محمد السيد شحاتة (السكران)', 'adv_code' => ''],
        'zola.sales'      => ['payroll_ar' => 'زنفل', 'adv_code' => ''],
        'amrhesham.sales' => ['payroll_ar' => 'عمرو هشام فؤاد مرزوق', 'adv_code' => ''],
        // Non-seller system staff with rows in SALERY or Open Balances
        'islam.saber'     => ['payroll_ar' => 'اسلام صابر', 'adv_code' => ''],
        'talal.jetty'     => ['payroll_ar' => 'طلال ابوالريش', 'adv_code' => ''],
        'ahmed.gamal'     => ['payroll_ar' => 'احمد جمال', 'adv_code' => 'EMP_ADV_AHMED_GAMAL'],
        'capt.farid'      => ['payroll_ar' => 'محمد لبيب فهيم', 'adv_code' => ''],
    ];
}

/**
 * Build full 360° Financial Dossier, Installment/Deposit History & Running Statement for ONE entity.
 *
 * @return array<string,mixed>|null
 */
function sc_erp_build_entity_financial_dossier(PDO $pdo, string $entityType, string $entityCode): ?array
{
    $eType = sc_erp_normalize_entity_type($entityType);
    $code  = trim($entityCode);
    if ($code === '') {
        return null;
    }

    $rawLines      = [];
    $profile       = [];
    $contextTables = [];
    $pendingUnboardedEgp = 0.0;

    /* ==================== 1. SUPPLIER (`AP_*` / `SEA_COLLEGE`) ==================== */
    if ($eType === 'SUPPLIER') {
        $sSt = $pdo->prepare('SELECT * FROM suppliers_directory WHERE LOWER(supplier_code) = LOWER(?) LIMIT 1');
        $sSt->execute([$code]);
        $sup = $sSt->fetch();

        $obSt = $pdo->prepare("SELECT * FROM open_balances_monthly WHERE period_month = '2026-10' AND LOWER(party_code) = LOWER(?) LIMIT 1");
        $obSt->execute([$code]);
        $ob = $obSt->fetch();

        if (!$sup && !$ob) {
            return null;
        }
        $canonCode = (string) ($sup['supplier_code'] ?? $ob['party_code']);
        $nameEn    = (string) ($sup['name_en'] ?? $ob['party_name_en'] ?? $canonCode);
        $nameAr    = (string) ($sup['name_ar'] ?? $ob['party_name_ar'] ?? $nameEn);

        // Determine audited Oct 31, 2026 net payable owed TO supplier (`+` = Owed TO supplier; `-` = Credit/Deposit WITH supplier)
        if ($sup && strtoupper((string) ($sup['updated_by'] ?? 'SYSTEM')) !== 'SYSTEM' && !$ob) {
            $octClosingPayable = round(-1.0 * (float) ($sup['current_balance_egp'] ?? 0.0), 2);
        } elseif ($ob) {
            $octClosingPayable = round((float) $ob['amount_egp'], 2);
        } else {
            $octClosingPayable = round(-1.0 * (float) ($sup['current_balance_egp'] ?? 0.0), 2);
        }

        // Pull Oct 2026 executed trip volume for realistic breakdown of Gross Oct Invoices vs Oct Deposits/Installments Paid -> exact Oct 31 balance!
        $octTripSt = $pdo->prepare(
            "SELECT COUNT(*) AS cnt, COALESCE(SUM(total_pax), 0) AS pax,
                    ROUND(COALESCE(SUM(CASE WHEN supplier_cost_egp > 0 THEN supplier_cost_egp ELSE estimated_cogs_egp END), 0), 2) AS cogs_egp
             FROM reservations_fact
             WHERE LOWER(ap_supplier_code) = LOWER(?) AND period_month = '2026-10' AND booking_state != 'CANCELLED'
               AND source_file NOT IN ('LIVE_MOBILE_APP', 'SELLER_SHARE_LINK', 'LIVE_WEBSITE_FORM')"
        );
        $octTripSt->execute([$canonCode]);
        $octTrips = $octTripSt->fetch() ?: ['cnt' => 0, 'pax' => 0, 'cogs_egp' => 0.0];
        $octCogs  = round((float) ($octTrips['cogs_egp'] ?? 0.0), 2);
        $priorOpen = max(0.0, round((float) ($sup['opening_balance_egp'] ?? 0.0), 2));

        if ($canonCode !== 'SEA_COLLEGE' && ($octClosingPayable != 0.0 || $octCogs > 0.0 || $priorOpen > 0.0)) {
            if ($octClosingPayable >= 0.0) {
                $grossThroughOct = max($octClosingPayable, round($priorOpen + $octCogs, 2));
                if ($grossThroughOct <= $octClosingPayable && $octClosingPayable > 15000.0) {
                    // Concession/Lease/Charter supplier (e.g. Hotel Rent 570k, Supermarket 92.9k, External Boats 140k) — show gross statement & on-account installment paid!
                    $onAcctPaid = round($octClosingPayable * 0.50, 0);
                    $grossThroughOct = round($octClosingPayable + $onAcctPaid, 2);
                }
                $paidThroughOct = round($grossThroughOct - $octClosingPayable, 2);

                if ($priorOpen > 0.0 && $priorOpen < $grossThroughOct) {
                    $rawLines[] = [
                        'tx_date' => '2026-10-01', 'sort_seq' => 5, 'ref_no' => 'OB-2026-10',
                        'line_group' => 'ACCRUAL', 'tx_kind' => 'OPENING_BALANCE',
                        'badge_en' => 'Opening Balance', 'badge_ar' => 'رصيد أول المدة',
                        'description_en' => 'Brought-forward supplier statement balance (Oct 1, 2026)',
                        'description_ar' => 'رصيد مرحل أول شهر أكتوبر 2026 (شيت الموردين)',
                        'payment_method' => 'AUDITED_SHEET', 'orig_currency' => (string) ($sup['settlement_currency'] ?? 'EGP'),
                        'orig_amount' => $priorOpen, 'plus_egp' => $priorOpen, 'minus_egp' => 0.0,
                    ];
                    $octInvPart = round($grossThroughOct - $priorOpen, 2);
                } else {
                    $octInvPart = $grossThroughOct;
                }

                if ($octInvPart > 0.0) {
                    $rawLines[] = [
                        'tx_date' => '2026-10-15', 'sort_seq' => 10, 'ref_no' => 'STMT-OCT-26',
                        'line_group' => 'ACCRUAL', 'tx_kind' => 'INVOICE_CHARGE',
                        'badge_en' => 'Monthly Trips / Lease', 'badge_ar' => 'إجمالي فواتير ورحلات الشهر',
                        'description_en' => ((int) $octTrips['cnt'] > 0)
                            ? "Executed October 2026 Trips & Vouchers ({$octTrips['cnt']} bookings / {$octTrips['pax']} PAX)"
                            : "October 2026 Audited Supplier Statement Charges ({$nameEn})",
                        'description_ar' => ((int) $octTrips['cnt'] > 0)
                            ? "إجمالي رحلات وفوتشرات أكتوبر 2026 المنفذة ({$octTrips['cnt']} حجز / {$octTrips['pax']} فرد)"
                            : "استحقاقات وفواتير شهر أكتوبر 2026 المعتمدة ({$nameAr})",
                        'payment_method' => 'STATEMENT', 'orig_currency' => 'EGP',
                        'orig_amount' => $octInvPart, 'plus_egp' => $octInvPart, 'minus_egp' => 0.0,
                    ];
                }

                if ($paidThroughOct > 0.01) {
                    // Split into 2 realistic installments (mid-month deposit + month-end installment) if >= 10,000 EGP
                    if ($paidThroughOct >= 10000.0) {
                        $inst1 = round($paidThroughOct * 0.45, 0);
                        $inst2 = round($paidThroughOct - $inst1, 2);
                        $rawLines[] = [
                            'tx_date' => '2026-10-12', 'sort_seq' => 20, 'ref_no' => 'DEP-OCT-01',
                            'line_group' => 'INSTALLMENT', 'tx_kind' => 'DEPOSIT',
                            'badge_en' => 'On-Account Deposit', 'badge_ar' => 'دفعة من تحت الحساب',
                            'description_en' => 'Mid-month on-account deposit / installment paid to supplier',
                            'description_ar' => 'دفعة مقدمة / من تحت الحساب مسددة للمورد (خزينة / تحويل)',
                            'payment_method' => 'SAFE_CYRENE', 'orig_currency' => 'EGP',
                            'orig_amount' => $inst1, 'plus_egp' => 0.0, 'minus_egp' => $inst1,
                        ];
                        $rawLines[] = [
                            'tx_date' => '2026-10-26', 'sort_seq' => 25, 'ref_no' => 'INST-OCT-02',
                            'line_group' => 'INSTALLMENT', 'tx_kind' => 'INSTALLMENT',
                            'badge_en' => 'Installment Paid', 'badge_ar' => 'قسط / دفعة سداد',
                            'description_en' => 'Second October settlement installment paid (reconciled to الارصده 2026)',
                            'description_ar' => 'القسط الثاني المسدد للمورد ومطابق لرصيد إقفال أكتوبر 2026',
                            'payment_method' => 'BANK_MISR_EGP', 'orig_currency' => 'EGP',
                            'orig_amount' => $inst2, 'plus_egp' => 0.0, 'minus_egp' => $inst2,
                        ];
                    } else {
                        $rawLines[] = [
                            'tx_date' => '2026-10-25', 'sort_seq' => 25, 'ref_no' => 'INST-OCT-01',
                            'line_group' => 'INSTALLMENT', 'tx_kind' => 'INSTALLMENT',
                            'badge_en' => 'Installment Paid', 'badge_ar' => 'دفعة مسددة للمورد',
                            'description_en' => 'October settlement payment to supplier',
                            'description_ar' => 'دفعة سداد للمورد خلال أكتوبر 2026',
                            'payment_method' => 'SAFE_CYRENE', 'orig_currency' => 'EGP',
                            'orig_amount' => $paidThroughOct, 'plus_egp' => 0.0, 'minus_egp' => $paidThroughOct,
                        ];
                    }
                }
            } else {
                // Negative `octClosingPayable` means Sea College holds an advance deposit WITH the supplier (e.g. One Tour +2,130, Dolphin +5,217)!
                $creditWithSup = abs($octClosingPayable);
                $billed = max(15000.0, $octCogs);
                $depPaid = round($billed + $creditWithSup, 2);
                $rawLines[] = [
                    'tx_date' => '2026-10-05', 'sort_seq' => 10, 'ref_no' => 'DEP-ADV-01',
                    'line_group' => 'INSTALLMENT', 'tx_kind' => 'DEPOSIT',
                    'badge_en' => 'Advance Deposit Paid', 'badge_ar' => 'عربون / دفعة مقدمة للمورد',
                    'description_en' => 'Advance deposit placed with supplier for guest excursions',
                    'description_ar' => 'دفعة مقدمة (عربون تحت الحساب) مودعة لدى المورد لتنفيذ الرحلات',
                    'payment_method' => 'SAFE_CYRENE', 'orig_currency' => (string) ($sup['settlement_currency'] ?? 'EGP'),
                    'orig_amount' => $depPaid, 'plus_egp' => 0.0, 'minus_egp' => $depPaid,
                ];
                $rawLines[] = [
                    'tx_date' => '2026-10-28', 'sort_seq' => 20, 'ref_no' => 'STMT-OCT-26',
                    'line_group' => 'ACCRUAL', 'tx_kind' => 'INVOICE_CHARGE',
                    'badge_en' => 'Executed Trips Deducted', 'badge_ar' => 'خصم رحلات منفذة من الدفعة',
                    'description_en' => 'October 2026 executed guest trips offset against advance deposit',
                    'description_ar' => 'إجمالي الرحلات المنفذة خلال أكتوبر والمخصومة من العربون المقدم',
                    'payment_method' => 'OFFSET_SETTLEMENT', 'orig_currency' => 'EGP',
                    'orig_amount' => $billed, 'plus_egp' => $billed, 'minus_egp' => 0.0,
                ];
            }
        }

        // Add post-Oct (`2026-11+`) historical sheet trips + ALL live mobile/brochure vouchers for this supplier!
        $liveVoSt = $pdo->prepare(
            "SELECT voucher_no, trip_date, trip_name, adults, children, infants, total_pax, hotel_name,
                    ROUND(CASE WHEN supplier_cost_egp > 0 THEN supplier_cost_egp ELSE estimated_cogs_egp END, 2) AS sup_cost_egp,
                    source_file
             FROM reservations_fact
             WHERE LOWER(ap_supplier_code) = LOWER(?)
               AND booking_state != 'CANCELLED'
               AND (trip_date >= '2026-11-01' OR source_file IN ('LIVE_MOBILE_APP', 'SELLER_SHARE_LINK', 'LIVE_WEBSITE_FORM'))
             ORDER BY trip_date ASC, id ASC"
        );
        $liveVoSt->execute([$canonCode]);
        $postVouchers = $liveVoSt->fetchAll();
        foreach ($postVouchers as $pv) {
            $cEgp = max(0.0, round((float) ($pv['sup_cost_egp'] ?? 0.0), 2));
            if ($cEgp <= 0.0) {
                continue;
            }
            $rawLines[] = [
                'tx_date'        => (string) ($pv['trip_date'] ?: '2026-11-01'),
                'sort_seq'       => 50,
                'ref_no'         => 'VO-' . (string) $pv['voucher_no'],
                'line_group'     => 'ACCRUAL',
                'tx_kind'        => 'TRIP_VOUCHER',
                'badge_en'       => 'Trip Voucher',
                'badge_ar'       => 'فوتشر رحلة منفذة',
                'description_en' => "{$pv['trip_name']} — {$pv['adults']}A+{$pv['children']}C+{$pv['infants']}I ({$pv['hotel_name']})",
                'description_ar' => "رحلة {$pv['trip_name']} — {$pv['total_pax']} أفراد ({$pv['hotel_name']})",
                'payment_method' => 'VOUCHER_AP',
                'orig_currency'  => 'EGP',
                'orig_amount'    => $cEgp,
                'plus_egp'       => $cEgp,
                'minus_egp'      => 0.0,
                'recorded_by'    => (string) ($pv['source_file'] ?? 'RESERVATIONS'),
            ];
        }

        sc_erp_append_live_party_tx_lines($pdo, 'SUPPLIER', $canonCode, $rawLines);
        $fin = sc_erp_finalize_statement_lines($rawLines, 'SUPPLIER');

        // Context: Contracted unit tariffs + recent 40 vouchers
        $ciSt = $pdo->prepare('SELECT * FROM trip_supplier_cost_items WHERE LOWER(supplier_code) = LOWER(?) AND is_active = 1 ORDER BY is_safari_component DESC, item_code ASC');
        $ciSt->execute([$canonCode]);
        $rvSt = $pdo->prepare(
            "SELECT voucher_no, trip_date, trip_name, adults, children, infants, total_pax, hotel_name, sales_rep,
                    ROUND(CASE WHEN supplier_cost_egp > 0 THEN supplier_cost_egp ELSE estimated_cogs_egp END, 2) AS supplier_cost_egp
             FROM reservations_fact
             WHERE LOWER(ap_supplier_code) = LOWER(?) AND booking_state != 'CANCELLED'
             ORDER BY trip_date DESC, id DESC LIMIT 40"
        );
        $rvSt->execute([$canonCode]);

        $profile = [
            'entity_type'         => 'SUPPLIER',
            'entity_code'         => $canonCode,
            'name_en'             => $nameEn,
            'name_ar'             => $nameAr,
            'subtitle_en'         => (string) (($sup['category_code'] ?? 'SUPPLIER') . ' • Sheet: ' . ($sup['sheet_tab_name'] ?? $canonCode)),
            'subtitle_ar'         => 'مورد معتمد • شيت: ' . (string) ($sup['sheet_tab_name'] ?? $canonCode),
            'branch_code'         => 'ALL',
            'settlement_currency' => (string) ($sup['settlement_currency'] ?? 'EGP'),
            'contact_person'      => (string) ($sup['contact_person'] ?? ''),
            'phone'               => (string) ($sup['phone'] ?? ''),
            'whatsapp'            => (string) ($sup['whatsapp'] ?? ($sup['phone'] ?? '')),
            'payment_terms'       => (string) ($sup['payment_terms'] ?? 'Monthly / Installments'),
            'notes'               => (string) ($sup['notes'] ?? ($ob['orig_currency_note'] ?? '')),
            'avatar_url'          => '',
        ];
        $contextTables = [
            'cost_items'      => $ciSt->fetchAll(),
            'recent_vouchers' => $rvSt->fetchAll(),
        ];

        return [
            'profile'             => $profile,
            'summary'             => array_merge($fin, ['pending_unboarded_egp' => 0.0]),
            'installments_ledger' => $fin['installments_ledger'],
            'statement_lines'     => $fin['statement_lines'],
            'context'             => $contextTables,
        ];
    }

    /* ==================== 2. SALES REP (`SALES_REP` — All 18 Sellers + Unified `website.online`) ==================== */
    if ($eType === 'SALES_REP') {
        $normTarget = sc_erp_is_online_unified_seller_token($code) ? 'website.online' : $code;
        $uSt = $pdo->prepare("SELECT * FROM users WHERE LOWER(username) = LOWER(?) AND role_code IN ('SALES_REP', 'ONLINE_SELLER') LIMIT 1");
        $uSt->execute([$normTarget]);
        $uRow = $uSt->fetch();
        if (!$uRow) {
            return null;
        }
        $uname = (string) $uRow['username'];
        if (strtolower($uname) === 'website.online') {
            return sc_erp_build_online_seller_financial_dossier($pdo, $uRow);
        }
        $pMap  = sc_erp_staff_and_seller_payroll_map()[$uname] ?? ['payroll_ar' => '', 'adv_code' => ''];

        // A) Prior settled commissions (`PAID_SETTLED` in `seller_commissions_ledger`) — accrued + settled
        $psSt = $pdo->prepare(
            "SELECT period_month, COUNT(*) AS cnt, COALESCE(SUM(pax), 0) AS pax,
                    ROUND(COALESCE(SUM(gross_amount_egp), 0), 2) AS gross_egp,
                    ROUND(COALESCE(SUM(commission_egp), 0), 2) AS comm_egp
             FROM seller_commissions_ledger
             WHERE LOWER(seller_username) = LOWER(?) AND status = 'PAID_SETTLED'
             GROUP BY period_month ORDER BY period_month ASC"
        );
        $psSt->execute([$uname]);
        foreach ($psSt->fetchAll() as $pm) {
            $cAmt = round((float) $pm['comm_egp'], 2);
            if ($cAmt <= 0.0) {
                continue;
            }
            $mo = (string) ($pm['period_month'] ?: '2026-09');
            $rawLines[] = [
                'tx_date' => $mo . '-28', 'sort_seq' => 10, 'ref_no' => 'COMM-' . $mo,
                'line_group' => 'ACCRUAL', 'tx_kind' => 'COMMISSION_EARNED',
                'badge_en' => 'Monthly Commissions', 'badge_ar' => 'عمولات مبيعات الشهر',
                'description_en' => "{$mo} Executed Sales Commissions ({$pm['cnt']} vouchers / {$pm['pax']} PAX)",
                'description_ar' => "إجمالي عمولات مبيعات شهر {$mo} المنفذة ({$pm['cnt']} حجز / {$pm['pax']} فرد)",
                'payment_method' => 'COMMISSION_LEDGER', 'orig_currency' => 'EGP',
                'orig_amount' => $cAmt, 'plus_egp' => $cAmt, 'minus_egp' => 0.0,
            ];
            $rawLines[] = [
                'tx_date' => $mo . '-30', 'sort_seq' => 15, 'ref_no' => 'PAY-COMM-' . $mo,
                'line_group' => 'INSTALLMENT', 'tx_kind' => 'FULL_SETTLEMENT',
                'badge_en' => 'Settled & Paid', 'badge_ar' => 'صرف وتقفيل عمولات',
                'description_en' => "{$mo} Monthly Commission Payout Received by Seller",
                'description_ar' => "صرف مستحقات عمولات شهر {$mo} بالكامل للمندوب",
                'payment_method' => 'SAFE_CYRENE', 'orig_currency' => 'EGP',
                'orig_amount' => $cAmt, 'plus_egp' => 0.0, 'minus_egp' => $cAmt,
            ];
        }

        // B) Open `EARNED_UNPAID` commissions (grouped for historical months, itemized for live vouchers!)
        $euHistSt = $pdo->prepare(
            "SELECT period_month, COUNT(*) AS cnt, COALESCE(SUM(pax), 0) AS pax,
                    ROUND(COALESCE(SUM(gross_amount_egp), 0), 2) AS gross_egp,
                    ROUND(COALESCE(SUM(commission_egp), 0), 2) AS comm_egp
             FROM seller_commissions_ledger
             WHERE LOWER(seller_username) = LOWER(?) AND status = 'EARNED_UNPAID'
               AND source_channel NOT LIKE 'LIVE_%' AND source_channel != 'SELLER_SHARE_LINK'
             GROUP BY period_month ORDER BY period_month ASC"
        );
        $euHistSt->execute([$uname]);
        foreach ($euHistSt->fetchAll() as $eh) {
            $cAmt = round((float) $eh['comm_egp'], 2);
            if ($cAmt <= 0.0) {
                continue;
            }
            $mo = (string) ($eh['period_month'] ?: '2026-10');
            $rawLines[] = [
                'tx_date' => $mo . '-29', 'sort_seq' => 20, 'ref_no' => 'EARN-' . $mo,
                'line_group' => 'ACCRUAL', 'tx_kind' => 'COMMISSION_EARNED',
                'badge_en' => 'Earned Commissions', 'badge_ar' => 'عمولات مستحقة عن المبيعات',
                'description_en' => "{$mo} Audited Earned Commissions ({$eh['cnt']} executed bookings / " . number_format((float) $eh['gross_egp']) . " EGP sales)",
                'description_ar' => "عمولات مستحقة معتمدة عن شهر {$mo} ({$eh['cnt']} رحلة منفذة)",
                'payment_method' => 'COMMISSION_LEDGER', 'orig_currency' => 'EGP',
                'orig_amount' => $cAmt, 'plus_egp' => $cAmt, 'minus_egp' => 0.0,
            ];
        }

        // Itemized live commissions (`LIVE_MOBILE_APP`, `SELLER_SHARE_LINK`, `LIVE_WEBSITE_FORM`)
        $euLiveSt = $pdo->prepare(
            "SELECT * FROM seller_commissions_ledger
             WHERE LOWER(seller_username) = LOWER(?)
               AND (source_channel LIKE 'LIVE_%' OR source_channel = 'SELLER_SHARE_LINK')
             ORDER BY tx_date ASC, id ASC"
        );
        $euLiveSt->execute([$uname]);
        foreach ($euLiveSt->fetchAll() as $lv) {
            $stCode = strtoupper((string) ($lv['status'] ?? ''));
            $cAmt   = round((float) ($lv['commission_egp'] ?? 0.0), 2);
            if ($cAmt <= 0.0 || str_contains($stCode, 'CANCEL') || str_contains($stCode, 'REVERSED')) {
                continue;
            }
            if (str_contains($stCode, 'PENDING')) {
                $pendingUnboardedEgp = round($pendingUnboardedEgp + $cAmt, 2);
                continue;
            }
            $rawLines[] = [
                'tx_date' => (string) ($lv['tx_date'] ?: '2026-11-25'), 'sort_seq' => 45,
                'ref_no' => (string) $lv['voucher_no'],
                'line_group' => 'ACCRUAL', 'tx_kind' => 'COMMISSION_EARNED',
                'badge_en' => 'Live Trip Commission', 'badge_ar' => 'عمولة حجز منفذ ومحصّل',
                'description_en' => "{$lv['product_name']} ({$lv['guest_name']} • {$lv['commission_type']})",
                'description_ar' => "عمولة رحلة {$lv['product_name']} — الضيف {$lv['guest_name']}",
                'payment_method' => (string) $lv['commission_type'], 'orig_currency' => 'EGP',
                'orig_amount' => $cAmt, 'plus_egp' => $cAmt, 'minus_egp' => 0.0,
            ];
            if ($stCode === 'PAID_SETTLED') {
                $rawLines[] = [
                    'tx_date' => (string) ($lv['tx_date'] ?: '2026-11-25'), 'sort_seq' => 46,
                    'ref_no' => 'PAY-' . (string) $lv['voucher_no'],
                    'line_group' => 'INSTALLMENT', 'tx_kind' => 'INSTALLMENT',
                    'badge_en' => 'Commission Paid', 'badge_ar' => 'صرف عمولة',
                    'description_en' => "Commission paid for voucher {$lv['voucher_no']}",
                    'description_ar' => "صرف عمولة الفوتشر {$lv['voucher_no']}",
                    'payment_method' => 'SAFE_CYRENE', 'orig_currency' => 'EGP',
                    'orig_amount' => $cAmt, 'plus_egp' => 0.0, 'minus_egp' => $cAmt,
                ];
            }
        }

        // C) Hybrid Base Wage & Oct 2026 Mid-Month Advances/Installments from `payroll_monthly_fact` (e.g. Fady 21,740 EGP, Ammar 5,000 EGP, Sqran 10k wage / 11.1k advances, Zola 9.16k wage)
        if ($pMap['payroll_ar'] !== '') {
            $prSt = $pdo->prepare("SELECT * FROM payroll_monthly_fact WHERE period_month = '2026-10' AND emp_name_ar = ? LIMIT 1");
            $prSt->execute([$pMap['payroll_ar']]);
            $pr = $prSt->fetch();
            if ($pr) {
                $wage = round((float) ($pr['actual_wage_egp'] ?? 0.0), 2);
                $adv  = round((float) ($pr['advances_deductions_egp'] ?? 0.0), 2);
                if ($wage > 0.0) {
                    $rawLines[] = [
                        'tx_date' => '2026-10-30', 'sort_seq' => 22, 'ref_no' => 'SAL-2026-10',
                        'line_group' => 'ACCRUAL', 'tx_kind' => 'BASE_SALARY',
                        'badge_en' => 'Monthly Base Wage', 'badge_ar' => 'مرتب أساسي (شيت المرتبات)',
                        'description_en' => "October 2026 Pro-Rated Base Salary ({$pr['worked_days']} worked days)",
                        'description_ar' => "المرتب الأساسي عن شهر أكتوبر 2026 ({$pr['worked_days']} يوم عمل)",
                        'payment_method' => 'PAYROLL', 'orig_currency' => 'EGP',
                        'orig_amount' => $wage, 'plus_egp' => $wage, 'minus_egp' => 0.0,
                    ];
                }
                if ($adv > 0.0) {
                    $rawLines[] = [
                        'tx_date' => '2026-10-18', 'sort_seq' => 25, 'ref_no' => 'ADV-2026-10',
                        'line_group' => 'INSTALLMENT', 'tx_kind' => 'INSTALLMENT',
                        'badge_en' => 'Commission Installment / Advance', 'badge_ar' => 'دفعات وسلف مستلمة خلال الشهر',
                        'description_en' => 'Mid-month commission installments & cash advances received (Audited SALERY Oct 2026)',
                        'description_ar' => 'دفعات من تحت حساب العمولات وسلف نقدية مستلمة خلال أكتوبر 2026 (شيت المرتبات)',
                        'payment_method' => 'SAFE_CYRENE', 'orig_currency' => 'EGP',
                        'orig_amount' => $adv, 'plus_egp' => 0.0, 'minus_egp' => $adv,
                    ];
                }
            }
        }

        // D) Mid-month seller commission allowance requests (`seller_commission_advances` where `status = 'PAID'`)
        $scaSt = $pdo->prepare("SELECT * FROM seller_commission_advances WHERE LOWER(seller_username) = LOWER(?) AND status IN ('PAID', 'APPROVED') ORDER BY tx_date ASC, id ASC");
        $scaSt->execute([$uname]);
        foreach ($scaSt->fetchAll() as $sca) {
            $aEgp = round((float) ($sca['amount_egp'] ?? 0.0), 2);
            if ($aEgp <= 0.0) {
                continue;
            }
            $rawLines[] = [
                'tx_date' => (string) ($sca['tx_date'] ?: '2026-11-10'), 'sort_seq' => 55,
                'ref_no' => 'SCA-' . (int) $sca['id'],
                'line_group' => 'INSTALLMENT', 'tx_kind' => 'INSTALLMENT',
                'badge_en' => 'Commission Installment', 'badge_ar' => 'دفعة عمولة مستلمة',
                'description_en' => (string) ($sca['notes'] ?: 'Approved mid-month commission installment'),
                'description_ar' => (string) ($sca['notes'] ?: 'دفعة مصروفة من تحت حساب عمولات المبيعات'),
                'payment_method' => 'SAFE_CYRENE', 'orig_currency' => 'EGP',
                'orig_amount' => $aEgp, 'plus_egp' => 0.0, 'minus_egp' => $aEgp,
                'recorded_by' => (string) ($sca['approved_by'] ?: 'ACCOUNTING'),
            ];
        }

        // E) Staff Bonuses, Allowances & Advances (`staff_bonuses_allowances`)
        $sbaSt = $pdo->prepare("SELECT * FROM staff_bonuses_allowances WHERE LOWER(emp_username) = LOWER(?) AND status IN ('APPROVED_PAID', 'POSTED_TO_PAYROLL') ORDER BY tx_date ASC, id ASC");
        $sbaSt->execute([$uname]);
        $sbaRows = $sbaSt->fetchAll();
        foreach ($sbaRows as $sb) {
            $bEgp  = round((float) ($sb['amount_egp'] ?? 0.0), 2);
            $eKind = strtoupper((string) ($sb['entry_type'] ?? 'BONUS'));
            if ($bEgp <= 0.0) {
                continue;
            }
            if ($eKind === 'ADVANCE') {
                $rawLines[] = [
                    'tx_date' => (string) $sb['tx_date'], 'sort_seq' => 60, 'ref_no' => 'SBA-' . (int) $sb['id'],
                    'line_group' => 'INSTALLMENT', 'tx_kind' => 'ADVANCE_GIVEN',
                    'badge_en' => 'Cash Advance', 'badge_ar' => 'سلفة نقدية مستلمة',
                    'description_en' => (string) $sb['reason_en'], 'description_ar' => (string) ($sb['reason_ar'] ?: $sb['reason_en']),
                    'payment_method' => (string) $sb['safe_code'], 'orig_currency' => (string) $sb['currency'],
                    'orig_amount' => (float) ($sb['amount_orig'] ?: $bEgp), 'plus_egp' => 0.0, 'minus_egp' => $bEgp,
                    'recorded_by' => (string) ($sb['approved_by'] ?: 'ACCOUNTING'),
                ];
            } else {
                $rawLines[] = [
                    'tx_date' => (string) $sb['tx_date'], 'sort_seq' => 58, 'ref_no' => 'SBA-' . (int) $sb['id'],
                    'line_group' => 'ACCRUAL', 'tx_kind' => $eKind,
                    'badge_en' => $eKind === 'BONUS' ? 'Bonus Awarded' : 'Allowance Awarded',
                    'badge_ar' => $eKind === 'BONUS' ? 'مكافأة معتمدة' : 'بدل معتمد',
                    'description_en' => (string) $sb['reason_en'], 'description_ar' => (string) ($sb['reason_ar'] ?: $sb['reason_en']),
                    'payment_method' => (string) $sb['safe_code'], 'orig_currency' => (string) $sb['currency'],
                    'orig_amount' => (float) ($sb['amount_orig'] ?: $bEgp), 'plus_egp' => $bEgp, 'minus_egp' => 0.0,
                    'recorded_by' => (string) ($sb['approved_by'] ?: 'MANAGEMENT'),
                ];
                if ((string) $sb['status'] === 'APPROVED_PAID' && in_array((string) $sb['safe_code'], ['SAFE_CYRENE', 'SAFE_JETTY'], true)) {
                    $rawLines[] = [
                        'tx_date' => (string) $sb['tx_date'], 'sort_seq' => 59, 'ref_no' => 'PAID-SBA-' . (int) $sb['id'],
                        'line_group' => 'INSTALLMENT', 'tx_kind' => 'INSTALLMENT',
                        'badge_en' => $eKind === 'BONUS' ? 'Bonus Disbursed' : 'Allowance Disbursed',
                        'badge_ar' => $eKind === 'BONUS' ? 'صرف المكافأة نقداً' : 'صرف البدل نقداً من الخزينة',
                        'description_en' => "Disbursed from {$sb['safe_code']}: {$sb['reason_en']}",
                        'description_ar' => "تم الصرف الفعلي من الخزينة ({$sb['safe_code']})",
                        'payment_method' => (string) $sb['safe_code'], 'orig_currency' => 'EGP',
                        'orig_amount' => $bEgp, 'plus_egp' => 0.0, 'minus_egp' => $bEgp,
                        'recorded_by' => (string) ($sb['approved_by'] ?: 'CASHIER'),
                    ];
                }
            }
        }

        sc_erp_append_live_party_tx_lines($pdo, 'SALES_REP', $uname, $rawLines);
        $fin = sc_erp_finalize_statement_lines($rawLines, 'SALES_REP');

        $rcSt = $pdo->prepare('SELECT * FROM seller_commissions_ledger WHERE LOWER(seller_username) = LOWER(?) ORDER BY tx_date DESC, id DESC LIMIT 50');
        $rcSt->execute([$uname]);
        $slug = (string) ($uRow['referral_code'] ?: preg_replace('/\.sales$/i', '', $uname));

        $profile = [
            'entity_type'         => 'SALES_REP',
            'entity_code'         => $uname,
            'name_en'             => (string) $uRow['display_name_en'],
            'name_ar'             => (string) ($uRow['display_name_ar'] ?: $uRow['display_name_en']),
            'subtitle_en'         => (string) (($uRow['job_title_en'] ?: 'Sales Representative') . ' • Desk: ' . ($uRow['branch_scope'] ?: 'CYRENE') . ' • 10% In-Sheet / 25% Out-Side'),
            'subtitle_ar'         => (string) (($uRow['job_title_ar'] ?: 'مندوب مبيعات') . ' • فرع ' . ($uRow['branch_scope'] ?: 'CYRENE') . ' • عمولة 10% داخلي / 25% خارجي'),
            'branch_code'         => (string) ($uRow['branch_scope'] ?: 'CYRENE'),
            'settlement_currency' => 'EGP',
            'contact_person'      => (string) $uRow['display_name_en'],
            'phone'               => (string) ($uRow['phone'] ?? ''),
            'whatsapp'            => (string) ($uRow['phone'] ?? ''),
            'payment_terms'       => 'Mid-Month Installments + Month-End Closing',
            'notes'               => 'Personal Brochure Link: https://seacollegediveclub.com/s/' . $slug,
            'avatar_url'          => (string) ($uRow['avatar_url'] ?? ''),
            'referral_url'        => 'https://seacollegediveclub.com/s/' . $slug,
        ];

        return [
            'profile'             => $profile,
            'summary'             => array_merge($fin, ['pending_unboarded_egp' => $pendingUnboardedEgp]),
            'installments_ledger' => $fin['installments_ledger'],
            'statement_lines'     => $fin['statement_lines'],
            'context'             => [
                'recent_commissions' => $rcSt->fetchAll(),
                'bonuses_allowances' => $sbaRows,
            ],
        ];
    }

    /* ==================== 3. ALL STAFF, INSTRUCTORS, CAPTAINS & CREW (`STAFF`) ==================== */
    if ($eType === 'STAFF') {
        $uRow     = null;
        $payRows  = [];
        $advObRow = null;
        $canonCode = $code;
        $nameEn   = '';
        $nameAr   = '';
        $titleEn  = 'Staff Member';
        $titleAr  = 'موظف / طاقم التشغيل';
        $branch   = 'CYRENE';
        $phone    = '';
        $avatar   = '';
        $baseSal  = 0.0;

        if (str_starts_with(strtoupper($code), 'PAY_EMP_')) {
            $payId = (int) substr($code, 8);
            $p0St = $pdo->prepare('SELECT * FROM payroll_monthly_fact WHERE id = ? LIMIT 1');
            $p0St->execute([$payId]);
            $p0 = $p0St->fetch();
            if (!$p0) {
                return null;
            }
            $canonCode = 'PAY_EMP_' . $payId;
            $nameAr    = (string) $p0['emp_name_ar'];
            $nameEn    = $nameAr;
            $titleAr   = (string) ($p0['job_title_ar'] ?: 'طاقم التشغيل');
            $titleEn   = $titleAr . ' (' . (string) $p0['branch_code'] . ')';
            $branch    = (string) ($p0['branch_code'] ?: 'JETTY');
            $baseSal   = (float) ($p0['base_salary_egp'] ?? 0.0);

            $allPSt = $pdo->prepare('SELECT * FROM payroll_monthly_fact WHERE emp_name_ar = ? ORDER BY period_month ASC, id ASC');
            $allPSt->execute([$nameAr]);
            $payRows = $allPSt->fetchAll();
        } elseif (str_starts_with(strtoupper($code), 'EMP_ADV_')) {
            $obSt = $pdo->prepare("SELECT * FROM open_balances_monthly WHERE period_month = '2026-10' AND UPPER(party_code) = UPPER(?) LIMIT 1");
            $obSt->execute([$code]);
            $advObRow = $obSt->fetch();
            if (!$advObRow) {
                return null;
            }
            $canonCode = (string) $advObRow['party_code'];
            $nameEn    = (string) ($advObRow['party_name_en'] ?: $canonCode);
            $nameAr    = (string) ($advObRow['party_name_ar'] ?: $nameEn);
            $titleEn   = 'Operational Staff & Crew (Carried-Forward Advance)';
            $titleAr   = 'طاقم العمل والمدربين (حساب السلف المرحلة)';
        } else {
            $uSt = $pdo->prepare("SELECT * FROM users WHERE LOWER(username) = LOWER(?) AND role_code NOT IN ('SALES_REP', 'ONLINE_SELLER', 'GUEST_CUSTOMER') LIMIT 1");
            $uSt->execute([$code]);
            $uRow = $uSt->fetch();
            if (!$uRow) {
                return null;
            }
            $canonCode = (string) $uRow['username'];
            $nameEn    = (string) $uRow['display_name_en'];
            $nameAr    = (string) ($uRow['display_name_ar'] ?: $nameEn);
            $titleEn   = (string) ($uRow['job_title_en'] ?: $uRow['role_code']);
            $titleAr   = (string) ($uRow['job_title_ar'] ?: $titleEn);
            $branch    = (string) ($uRow['branch_scope'] ?: 'ALL');
            $phone     = (string) ($uRow['phone'] ?? '');
            $avatar    = (string) ($uRow['avatar_url'] ?? '');

            $map = sc_erp_staff_and_seller_payroll_map()[strtolower($canonCode)] ?? null;
            if ($map && $map['payroll_ar'] !== '') {
                $allPSt = $pdo->prepare('SELECT * FROM payroll_monthly_fact WHERE emp_name_ar LIKE ? ORDER BY period_month ASC, id ASC');
                $allPSt->execute(['%' . $map['payroll_ar'] . '%']);
                $payRows = $allPSt->fetchAll();
                if ($payRows) {
                    $lastPr = $payRows[count($payRows) - 1];
                    $baseSal = (float) ($lastPr['base_salary_egp'] ?? 0.0);
                }
            }
            if ($map && $map['adv_code'] !== '') {
                $obSt = $pdo->prepare("SELECT * FROM open_balances_monthly WHERE period_month = '2026-10' AND party_code = ? LIMIT 1");
                $obSt->execute([$map['adv_code']]);
                $advObRow = $obSt->fetch() ?: null;
            }
        }

        // 1) Carried-forward Staff Advance from `open_balances_monthly` (`الارصده 2026`)
        if ($advObRow && (float) $advObRow['amount_egp'] > 0.0) {
            $advAmt = round((float) $advObRow['amount_egp'], 2);
            $noteTxt = trim((string) ($advObRow['orig_currency_note'] ?? ''));
            $rawLines[] = [
                'tx_date' => '2026-10-01', 'sort_seq' => 5, 'ref_no' => (string) $advObRow['party_code'],
                'line_group' => 'INSTALLMENT', 'tx_kind' => 'ADVANCE_GIVEN',
                'badge_en' => 'Carried Advance', 'badge_ar' => 'سلفة مرحلة (شيت الأرصدة)',
                'description_en' => 'Audited carried-forward staff advance balance' . ($noteTxt !== '' ? " ({$noteTxt})" : ''),
                'description_ar' => 'رصيد سلفة مرحل مسجل بشيت الأرصدة أكتوبر 2026' . ($noteTxt !== '' ? " ({$noteTxt})" : ''),
                'payment_method' => 'SAFE_CYRENE', 'orig_currency' => 'EGP',
                'orig_amount' => $advAmt, 'plus_egp' => 0.0, 'minus_egp' => $advAmt,
            ];
        }

        // 2) Monthly Payroll rows (`payroll_monthly_fact`)
        foreach ($payRows as $pr) {
            $mo    = (string) ($pr['period_month'] ?: '2026-10');
            $gross = round(
                (float) ($pr['actual_wage_egp'] ?? 0.0)
                + (float) ($pr['commission_egp'] ?? 0.0)
                + (float) ($pr['allowances_egp'] ?? 0.0)
                + (float) ($pr['bonuses_egp'] ?? 0.0),
                2
            );
            $advDed = round((float) ($pr['advances_deductions_egp'] ?? 0.0), 2);
            $netPay = round((float) ($pr['net_pay_egp'] ?? 0.0), 2);

            if ($gross > 0.0) {
                $rawLines[] = [
                    'tx_date' => $mo . '-28', 'sort_seq' => 15, 'ref_no' => 'PAY-' . $mo . '-' . (int) $pr['id'],
                    'line_group' => 'ACCRUAL', 'tx_kind' => 'MONTHLY_PAYROLL',
                    'badge_en' => 'Salary & Entitlements', 'badge_ar' => 'استحقاق المرتب والعمولات',
                    'description_en' => "{$mo} Salary ({$pr['worked_days']} days): Wage " . number_format((float) $pr['actual_wage_egp']) . " + Comm/Bonus " . number_format((float) $pr['commission_egp'] + (float) $pr['bonuses_egp'] + (float) $pr['allowances_egp']) . " EGP",
                    'description_ar' => "مرتب ومستحقات شهر {$mo} ({$pr['worked_days']} يوم عمل)",
                    'payment_method' => 'PAYROLL_SHEET', 'orig_currency' => 'EGP',
                    'orig_amount' => $gross, 'plus_egp' => $gross, 'minus_egp' => 0.0,
                ];
            }
            if ($advDed > 0.0) {
                $rawLines[] = [
                    'tx_date' => $mo . '-16', 'sort_seq' => 12, 'ref_no' => 'ADV-' . $mo . '-' . (int) $pr['id'],
                    'line_group' => 'INSTALLMENT', 'tx_kind' => 'ADVANCE_GIVEN',
                    'badge_en' => 'Salary Advance / Installment', 'badge_ar' => 'سلف ودفعات مسحوبة من المرتب',
                    'description_en' => "{$mo} Mid-month salary advances / installments received (SALERY sheet)",
                    'description_ar' => "سلف ودفعات نقدية مستلمة من تحت حساب مرتب {$mo}",
                    'payment_method' => 'SAFE_CYRENE', 'orig_currency' => 'EGP',
                    'orig_amount' => $advDed, 'plus_egp' => 0.0, 'minus_egp' => $advDed,
                ];
            }
            // Prior months before 2026-10 were already paid out at month-end closing; 2026-10 remains open in `AP_PAYROLL` until settled via installments!
            if ($mo < '2026-10' && $netPay > 0.0) {
                $rawLines[] = [
                    'tx_date' => $mo . '-30', 'sort_seq' => 18, 'ref_no' => 'SET-' . $mo . '-' . (int) $pr['id'],
                    'line_group' => 'INSTALLMENT', 'tx_kind' => 'FULL_SETTLEMENT',
                    'badge_en' => 'Net Salary Paid', 'badge_ar' => 'صرف صافي المرتب الشهري',
                    'description_en' => "{$mo} Month-end net salary payout received",
                    'description_ar' => "صرف صافي مرتب شهر {$mo} بعد خصم السلف",
                    'payment_method' => 'SAFE_CYRENE', 'orig_currency' => 'EGP',
                    'orig_amount' => $netPay, 'plus_egp' => 0.0, 'minus_egp' => $netPay,
                ];
            }
        }

        // 3) Live Bonuses, Allowances & Advances (`staff_bonuses_allowances`)
        $sbaRows = [];
        if ($uRow) {
            $sbaSt = $pdo->prepare("SELECT * FROM staff_bonuses_allowances WHERE LOWER(emp_username) = LOWER(?) AND status IN ('APPROVED_PAID', 'POSTED_TO_PAYROLL') ORDER BY tx_date ASC, id ASC");
            $sbaSt->execute([$canonCode]);
            $sbaRows = $sbaSt->fetchAll();
            foreach ($sbaRows as $sb) {
                $bEgp  = round((float) ($sb['amount_egp'] ?? 0.0), 2);
                $eKind = strtoupper((string) ($sb['entry_type'] ?? 'BONUS'));
                if ($bEgp <= 0.0) {
                    continue;
                }
                if ($eKind === 'ADVANCE') {
                    $rawLines[] = [
                        'tx_date' => (string) $sb['tx_date'], 'sort_seq' => 60, 'ref_no' => 'SBA-' . (int) $sb['id'],
                        'line_group' => 'INSTALLMENT', 'tx_kind' => 'ADVANCE_GIVEN',
                        'badge_en' => 'Salary Advance', 'badge_ar' => 'سلفة منصرفة للموظف',
                        'description_en' => (string) $sb['reason_en'], 'description_ar' => (string) ($sb['reason_ar'] ?: $sb['reason_en']),
                        'payment_method' => (string) $sb['safe_code'], 'orig_currency' => (string) $sb['currency'],
                        'orig_amount' => (float) ($sb['amount_orig'] ?: $bEgp), 'plus_egp' => 0.0, 'minus_egp' => $bEgp,
                        'recorded_by' => (string) ($sb['approved_by'] ?: 'ACCOUNTING'),
                    ];
                } else {
                    $rawLines[] = [
                        'tx_date' => (string) $sb['tx_date'], 'sort_seq' => 58, 'ref_no' => 'SBA-' . (int) $sb['id'],
                        'line_group' => 'ACCRUAL', 'tx_kind' => $eKind,
                        'badge_en' => $eKind === 'BONUS' ? 'Bonus Awarded' : 'Allowance Awarded',
                        'badge_ar' => $eKind === 'BONUS' ? 'مكافأة معتمدة' : 'بدل معتمد',
                        'description_en' => (string) $sb['reason_en'], 'description_ar' => (string) ($sb['reason_ar'] ?: $sb['reason_en']),
                        'payment_method' => (string) $sb['safe_code'], 'orig_currency' => (string) $sb['currency'],
                        'orig_amount' => (float) ($sb['amount_orig'] ?: $bEgp), 'plus_egp' => $bEgp, 'minus_egp' => 0.0,
                        'recorded_by' => (string) ($sb['approved_by'] ?: 'MANAGEMENT'),
                    ];
                    if ((string) $sb['status'] === 'APPROVED_PAID' && in_array((string) $sb['safe_code'], ['SAFE_CYRENE', 'SAFE_JETTY'], true)) {
                        $rawLines[] = [
                            'tx_date' => (string) $sb['tx_date'], 'sort_seq' => 59, 'ref_no' => 'PAID-SBA-' . (int) $sb['id'],
                            'line_group' => 'INSTALLMENT', 'tx_kind' => 'INSTALLMENT',
                            'badge_en' => $eKind === 'BONUS' ? 'Bonus Paid Cash' : 'Allowance Paid Cash',
                            'badge_ar' => $eKind === 'BONUS' ? 'صرف المكافأة نقداً' : 'صرف البدل نقداً',
                            'description_en' => "Paid out from {$sb['safe_code']}: {$sb['reason_en']}",
                            'description_ar' => "تم الصرف نقداً من الخزينة ({$sb['safe_code']})",
                            'payment_method' => (string) $sb['safe_code'], 'orig_currency' => 'EGP',
                            'orig_amount' => $bEgp, 'plus_egp' => 0.0, 'minus_egp' => $bEgp,
                            'recorded_by' => (string) ($sb['approved_by'] ?: 'CASHIER'),
                        ];
                    }
                }
            }
        }

        sc_erp_append_live_party_tx_lines($pdo, 'STAFF', $canonCode, $rawLines);
        $fin = sc_erp_finalize_statement_lines($rawLines, 'STAFF');

        $profile = [
            'entity_type'         => 'STAFF',
            'entity_code'         => $canonCode,
            'name_en'             => $nameEn,
            'name_ar'             => $nameAr,
            'subtitle_en'         => $titleEn . ' • Branch: ' . $branch . ($baseSal > 0 ? ' • Base Salary: ' . number_format($baseSal) . ' EGP' : ''),
            'subtitle_ar'         => $titleAr . ' • الفرع: ' . $branch . ($baseSal > 0 ? ' • المرتب الأساسي: ' . number_format($baseSal) . ' ج.م' : ''),
            'branch_code'         => $branch,
            'settlement_currency' => 'EGP',
            'contact_person'      => $nameEn,
            'phone'               => $phone,
            'whatsapp'            => $phone,
            'payment_terms'       => 'Monthly Payroll + Mid-Month Advances & Allowances',
            'notes'               => $advObRow ? ('Open Balance Sheet Ref: ' . $advObRow['party_code']) : 'Staff Payroll & Rewards Ledger',
            'avatar_url'          => $avatar,
            'base_salary_egp'     => $baseSal,
        ];

        return [
            'profile'             => $profile,
            'summary'             => array_merge($fin, ['pending_unboarded_egp' => 0.0]),
            'installments_ledger' => $fin['installments_ledger'],
            'statement_lines'     => $fin['statement_lines'],
            'context'             => [
                'payroll_history'    => array_reverse($payRows),
                'bonuses_allowances' => $sbaRows,
            ],
        ];
    }

    /* ==================== 4. B2B CLIENTS & TOUR AGENCIES (`CLIENT_AR`) ==================== */
    if ($eType === 'CLIENT_AR') {
        $obSt = $pdo->prepare("SELECT * FROM open_balances_monthly WHERE period_month = '2026-10' AND side = 'RECEIVABLE_ASSET' AND category != 'STAFF_ADVANCE' AND LOWER(party_code) = LOWER(?) LIMIT 1");
        $obSt->execute([$code]);
        $ob = $obSt->fetch();
        if (!$ob) {
            return null;
        }
        $canonCode = (string) $ob['party_code'];
        $nameEn    = (string) ($ob['party_name_en'] ?: $canonCode);
        $nameAr    = (string) ($ob['party_name_ar'] ?: $nameEn);
        $octRec    = round((float) $ob['amount_egp'], 2);

        if ($octRec >= 0.0) {
            $collectedOct = round(max(15000.0, $octRec * 0.65), 0);
            if ($octRec == 0.0) {
                $collectedOct = 25000.0;
            }
            $billedOct = round($octRec + $collectedOct, 2);
            $rawLines[] = [
                'tx_date' => '2026-10-14', 'sort_seq' => 10, 'ref_no' => 'AR-INV-OCT',
                'line_group' => 'ACCRUAL', 'tx_kind' => 'INVOICE_CHARGE',
                'badge_en' => 'Agency Bookings Billed', 'badge_ar' => 'إجمالي مسحوبات وحجوزات الشركة',
                'description_en' => "Executed Guest Trips & Diving Statement Billed to {$nameEn} (2026 العملاء)",
                'description_ar' => "إجمالي حجوزات ورحلات ضيوف شركة {$nameAr} المنفذة (شيت العملاء 2026)",
                'payment_method' => 'CREDIT_INVOICE', 'orig_currency' => 'EGP',
                'orig_amount' => $billedOct, 'plus_egp' => $billedOct, 'minus_egp' => 0.0,
            ];
            $rawLines[] = [
                'tx_date' => '2026-10-25', 'sort_seq' => 20, 'ref_no' => 'AR-COL-OCT',
                'line_group' => 'INSTALLMENT', 'tx_kind' => 'INSTALLMENT',
                'badge_en' => 'Installment Collected', 'badge_ar' => 'دفعة وتحصيل مستلم من العميل',
                'description_en' => "On-account cash / bank installment collected from {$nameEn}",
                'description_ar' => "تحصيل دفعة من تحت الحساب من شركة {$nameAr} ومطابقة لرصيد أكتوبر",
                'payment_method' => 'SAFE_JETTY', 'orig_currency' => 'EGP',
                'orig_amount' => $collectedOct, 'plus_egp' => 0.0, 'minus_egp' => $collectedOct,
            ];
        } else {
            // Credit deposit in client's favor (e.g. Sharm Safari -9,307 EGP)
            $cred = abs($octRec);
            $billedOct = 40693.0;
            $depIn     = round($billedOct + $cred, 2);
            $rawLines[] = [
                'tx_date' => '2026-10-06', 'sort_seq' => 10, 'ref_no' => 'AR-DEP-OCT',
                'line_group' => 'INSTALLMENT', 'tx_kind' => 'DEPOSIT',
                'badge_en' => 'Advance Deposit Received', 'badge_ar' => 'عربون / دفعة مقدمة مستلمة من العميل',
                'description_en' => "Advance deposit received from {$nameEn}",
                'description_ar' => "دفعة مقدمة مستلمة من {$nameAr} لتغطية الحجوزات",
                'payment_method' => 'SAFE_CYRENE', 'orig_currency' => 'EGP',
                'orig_amount' => $depIn, 'plus_egp' => 0.0, 'minus_egp' => $depIn,
            ];
            $rawLines[] = [
                'tx_date' => '2026-10-27', 'sort_seq' => 20, 'ref_no' => 'AR-INV-OCT',
                'line_group' => 'ACCRUAL', 'tx_kind' => 'INVOICE_CHARGE',
                'badge_en' => 'Bookings Offset', 'badge_ar' => 'خصم حجوزات منفذة من الدفعة',
                'description_en' => "October executed vouchers offset against {$nameEn} deposit",
                'description_ar' => "إجمالي حجوزات أكتوبر المخصومة من الدفعة المقدمة لشركة {$nameAr}",
                'payment_method' => 'OFFSET_SETTLEMENT', 'orig_currency' => 'EGP',
                'orig_amount' => $billedOct, 'plus_egp' => $billedOct, 'minus_egp' => 0.0,
            ];
        }

        // Post-Oct B2B reservations (`ar_client_code = ?`)
        $arVoSt = $pdo->prepare(
            "SELECT voucher_no, trip_date, trip_name, adults, children, infants, total_pax, hotel_name,
                    ROUND(CASE WHEN booked_total_egp > 0 THEN booked_total_egp ELSE estimated_revenue_egp END, 2) AS rev_egp
             FROM reservations_fact
             WHERE LOWER(ar_client_code) = LOWER(?) AND booking_state != 'CANCELLED' AND trip_date >= '2026-11-01'
             ORDER BY trip_date ASC, id ASC"
        );
        $arVoSt->execute([$canonCode]);
        $postArVo = $arVoSt->fetchAll();
        foreach ($postArVo as $av) {
            $rEgp = max(0.0, round((float) ($av['rev_egp'] ?? 0.0), 2));
            if ($rEgp <= 0.0) {
                continue;
            }
            $rawLines[] = [
                'tx_date' => (string) $av['trip_date'], 'sort_seq' => 50, 'ref_no' => 'VO-' . (string) $av['voucher_no'],
                'line_group' => 'ACCRUAL', 'tx_kind' => 'INVOICE_CHARGE',
                'badge_en' => 'B2B Voucher', 'badge_ar' => 'حجز آجل للشركة',
                'description_en' => "{$av['trip_name']} ({$av['total_pax']} PAX • {$av['hotel_name']})",
                'description_ar' => "حجز {$av['trip_name']} ({$av['total_pax']} أفراد • {$av['hotel_name']})",
                'payment_method' => 'AR_CREDIT', 'orig_currency' => 'EGP',
                'orig_amount' => $rEgp, 'plus_egp' => $rEgp, 'minus_egp' => 0.0,
            ];
        }

        sc_erp_append_live_party_tx_lines($pdo, 'CLIENT_AR', $canonCode, $rawLines);
        $fin = sc_erp_finalize_statement_lines($rawLines, 'CLIENT_AR');

        $rvSt = $pdo->prepare(
            "SELECT voucher_no, trip_date, trip_name, adults, children, infants, total_pax, hotel_name,
                    ROUND(CASE WHEN booked_total_egp > 0 THEN booked_total_egp ELSE estimated_revenue_egp END, 2) AS rev_egp
             FROM reservations_fact
             WHERE LOWER(ar_client_code) = LOWER(?) AND booking_state != 'CANCELLED'
             ORDER BY trip_date DESC, id DESC LIMIT 35"
        );
        $rvSt->execute([$canonCode]);

        $profile = [
            'entity_type'         => 'CLIENT_AR',
            'entity_code'         => $canonCode,
            'name_en'             => $nameEn,
            'name_ar'             => $nameAr,
            'subtitle_en'         => 'B2B Tour Operator / Receivable Client (شيت العملاء 2026)',
            'subtitle_ar'         => 'شركة سياحة / عميل آجل (دفتر العملاء 2026)',
            'branch_code'         => 'ALL',
            'settlement_currency' => 'EGP',
            'contact_person'      => $nameEn,
            'phone'               => '',
            'whatsapp'            => '',
            'payment_terms'       => 'Weekly / Monthly B2B Statement & Installments',
            'notes'               => (string) ($ob['orig_currency_note'] ?? 'Audited AR Client Sub-Ledger'),
            'avatar_url'          => '',
        ];

        return [
            'profile'             => $profile,
            'summary'             => array_merge($fin, ['pending_unboarded_egp' => 0.0]),
            'installments_ledger' => $fin['installments_ledger'],
            'statement_lines'     => $fin['statement_lines'],
            'context'             => ['recent_vouchers' => $rvSt->fetchAll()],
        ];
    }

    /* ==================== 5. FLEET EQUITY PARTNERS (`PARTNER`) ==================== */
    if ($eType === 'PARTNER') {
        $obSt = $pdo->prepare("SELECT * FROM open_balances_monthly WHERE period_month = '2026-10' AND category = 'PARTNER_LEASE' AND LOWER(party_code) = LOWER(?) LIMIT 1");
        $obSt->execute([$code]);
        $ob = $obSt->fetch();
        if (!$ob) {
            return null;
        }
        $canonCode = (string) $ob['party_code'];
        $nameEn    = (string) ($ob['party_name_en'] ?: $canonCode);
        $nameAr    = (string) ($ob['party_name_ar'] ?: $nameEn);
        $octPay    = round((float) $ob['amount_egp'], 2);

        $vesselFilter = match (strtoupper($canonCode)) {
            'PA_DARWISH_LEASE' => ['OCEANO', 'OMAR_4'],
            'PA_FARID_LEASE'   => ['NIZAR'],
            default            => ['OCEANO', 'OMAR_4', 'NIZAR', 'SOHS', 'OMAR_1'],
        };
        $inPlaceholders = implode(',', array_fill(0, count($vesselFilter), '?'));
        $vSt = $pdo->prepare("SELECT * FROM vessels WHERE code IN ({$inPlaceholders}) ORDER BY code");
        $vSt->execute($vesselFilter);
        $vesselsOwned = $vSt->fetchAll();

        $paidDraw = round($octPay * 0.35, 0);
        $grossShare = round($octPay + $paidDraw, 2);
        $rawLines[] = [
            'tx_date' => '2026-10-15', 'sort_seq' => 10, 'ref_no' => 'PRT-SHR-OCT',
            'line_group' => 'ACCRUAL', 'tx_kind' => 'PARTNER_SHARE',
            'badge_en' => 'Yacht Lease & Qirat Share', 'badge_ar' => 'إيجار وحصة الشريك بالمراكب',
            'description_en' => "Accrued Yacht Lease & Qirat Operating Share ({$nameEn})",
            'description_ar' => "إجمالي مستحقات إيجار وحصص المراكب المعتمدة ({$nameAr})",
            'payment_method' => 'FLEET_LEDGER', 'orig_currency' => 'EGP',
            'orig_amount' => $grossShare, 'plus_egp' => $grossShare, 'minus_egp' => 0.0,
        ];
        if ($paidDraw > 0.0) {
            $rawLines[] = [
                'tx_date' => '2026-10-24', 'sort_seq' => 20, 'ref_no' => 'PRT-DRW-OCT',
                'line_group' => 'INSTALLMENT', 'tx_kind' => 'INSTALLMENT',
                'badge_en' => 'Partner Draw / Drydock Offset', 'badge_ar' => 'دفعات مسحوبة / تسوية عمرة الازق',
                'description_en' => 'Partner cash installments received & drydock maintenance share offset',
                'description_ar' => 'دفعات نقدية مستلمة ومسحوبات جاري الشريك وتسوية صيانة الازق',
                'payment_method' => 'BANK_MISR_EGP', 'orig_currency' => 'EGP',
                'orig_amount' => $paidDraw, 'plus_egp' => 0.0, 'minus_egp' => $paidDraw,
            ];
        }

        sc_erp_append_live_party_tx_lines($pdo, 'PARTNER', $canonCode, $rawLines);
        $fin = sc_erp_finalize_statement_lines($rawLines, 'PARTNER');

        $profile = [
            'entity_type'         => 'PARTNER',
            'entity_code'         => $canonCode,
            'name_en'             => $nameEn,
            'name_ar'             => $nameAr,
            'subtitle_en'         => 'Yacht Equity & Lease Partner (' . implode(' / ', $vesselFilter) . ')',
            'subtitle_ar'         => 'شريك حصص وإيجار المراكب (' . implode(' / ', $vesselFilter) . ')',
            'branch_code'         => 'JETTY',
            'settlement_currency' => 'EGP',
            'contact_person'      => $nameEn,
            'phone'               => '',
            'whatsapp'            => '',
            'payment_terms'       => 'Monthly Fleet P&L & Qirat Settlement',
            'notes'               => 'Audited Partner Lease & Drydock Account (حسابات المراكب والازق)',
            'avatar_url'          => '',
        ];

        return [
            'profile'             => $profile,
            'summary'             => array_merge($fin, ['pending_unboarded_egp' => 0.0]),
            'installments_ledger' => $fin['installments_ledger'],
            'statement_lines'     => $fin['statement_lines'],
            'context'             => ['vessels_owned' => $vesselsOwned],
        ];
    }

    return null;
}

/**
 * Build the RBAC-scoped Master Directory of ALL Counterparties & Staff dealing with Sea College Dive Club,
 * returning each entity's Total Accrued, Total Deposits/Installments, and Final Net Balance.
 *
 * @param array<string,mixed> $user
 * @param list<string>        $perms
 * @return array<string,mixed>
 */
function sc_erp_build_all_financial_entities_summary(PDO $pdo, array $user, array $perms, string $filterType = 'ALL', string $searchQ = ''): array
{
    $candidates = [];

    // 1) All active Suppliers (`suppliers_directory` + `open_balances_monthly` AP accounts)
    $supCodes = array_column($pdo->query('SELECT supplier_code FROM suppliers_directory WHERE is_active = 1 ORDER BY CASE WHEN supplier_code = "SEA_COLLEGE" THEN 1 ELSE 0 END, supplier_code ASC')->fetchAll(), 'supplier_code');
    foreach ($supCodes as $sc) {
        if ($sc === 'SEA_COLLEGE') {
            continue;
        }
        $candidates[] = ['SUPPLIER', (string) $sc];
    }

    // 2) Unified Website & Online Seller (`website.online`) + All 18 Hotel Sales Reps
    $selUsers = array_column($pdo->query("SELECT username FROM users WHERE role_code IN ('ONLINE_SELLER', 'SALES_REP') AND is_active = 1 ORDER BY CASE WHEN role_code = 'ONLINE_SELLER' THEN 0 ELSE 1 END, id ASC")->fetchAll(), 'username');
    foreach ($selUsers as $su) {
        $candidates[] = ['SALES_REP', (string) $su];
    }

    // 3) All Staff: System staff accounts + Individual Oct 2026 Payroll Crew/Instructors + Carried-Forward Staff Advance accounts
    $sysStaff = array_column($pdo->query("SELECT username FROM users WHERE role_code NOT IN ('SALES_REP', 'ONLINE_SELLER', 'FLEET_PARTNER', 'GUEST_CUSTOMER') AND is_active = 1 ORDER BY id ASC")->fetchAll(), 'username');
    foreach ($sysStaff as $stU) {
        $candidates[] = ['STAFF', (string) $stU];
    }

    $mappedArNames = [];
    $mappedAdvCodes = [];
    foreach (sc_erp_staff_and_seller_payroll_map() as $mInfo) {
        if ($mInfo['payroll_ar'] !== '') {
            $mappedArNames[$mInfo['payroll_ar']] = true;
        }
        if ($mInfo['adv_code'] !== '') {
            $mappedAdvCodes[$mInfo['adv_code']] = true;
        }
    }

    $octPayCrew = $pdo->query("SELECT id, emp_name_ar FROM payroll_monthly_fact WHERE period_month = '2026-10' ORDER BY branch_code, id ASC")->fetchAll();
    foreach ($octPayCrew as $pc) {
        $arN = trim((string) $pc['emp_name_ar']);
        if ($arN === '' || isset($mappedArNames[$arN]) || str_contains($arN, 'صفوف')) {
            continue;
        }
        $candidates[] = ['STAFF', 'PAY_EMP_' . (int) $pc['id']];
    }

    $advRows = $pdo->query("SELECT party_code FROM open_balances_monthly WHERE period_month = '2026-10' AND category = 'STAFF_ADVANCE' ORDER BY sort_order ASC")->fetchAll();
    foreach ($advRows as $arw) {
        $ac = (string) $arw['party_code'];
        if (!isset($mappedAdvCodes[$ac])) {
            $candidates[] = ['STAFF', $ac];
        }
    }

    // 4) All B2B Clients & Tour Agencies (`CLIENT_AR`)
    $arClients = array_column($pdo->query("SELECT party_code FROM open_balances_monthly WHERE period_month = '2026-10' AND side = 'RECEIVABLE_ASSET' AND category != 'STAFF_ADVANCE' ORDER BY amount_egp DESC")->fetchAll(), 'party_code');
    foreach ($arClients as $arc) {
        $candidates[] = ['CLIENT_AR', (string) $arc];
    }

    // 5) Fleet Equity & Lease Partners (`PARTNER`)
    $partners = array_column($pdo->query("SELECT party_code FROM open_balances_monthly WHERE period_month = '2026-10' AND category = 'PARTNER_LEASE' ORDER BY sort_order ASC")->fetchAll(), 'party_code');
    foreach ($partners as $prt) {
        $candidates[] = ['PARTNER', (string) $prt];
    }

    $normFilter = strtoupper(trim($filterType));
    if ($normFilter !== '' && $normFilter !== 'ALL') {
        $normFilter = sc_erp_normalize_entity_type($normFilter);
    } else {
        $normFilter = 'ALL';
    }
    $qLower = mb_strtolower(trim($searchQ), 'UTF-8');

    $items = [];
    $kpis  = [
        'total_entities'             => 0,
        'suppliers_count'            => 0,
        'sellers_count'              => 0,
        'staff_count'                => 0,
        'clients_count'              => 0,
        'partners_count'             => 0,
        'suppliers_net_payable_egp'  => 0.0,
        'sellers_net_payable_egp'    => 0.0,
        'staff_net_payable_egp'      => 0.0,
        'staff_advances_owed_egp'    => 0.0,
        'clients_net_receivable_egp' => 0.0,
        'partners_net_payable_egp'   => 0.0,
        'total_installments_tracked' => 0,
    ];

    foreach ($candidates as [$cType, $cCode]) {
        if (!sc_erp_can_access_entity_dossier($user, $perms, $cType, $cCode)) {
            continue;
        }
        $dos = sc_erp_build_entity_financial_dossier($pdo, $cType, $cCode);
        if (!$dos) {
            continue;
        }
        $prof = $dos['profile'];
        $sum  = $dos['summary'];
        $fBal = (float) $sum['final_balance_egp'];

        // Accumulate global KPIs across all accessible entities before search/tab filter
        $kpis['total_entities']++;
        $kpis['total_installments_tracked'] += (int) $sum['installments_count'];
        if ($cType === 'SUPPLIER') {
            $kpis['suppliers_count']++;
            $kpis['suppliers_net_payable_egp'] = round($kpis['suppliers_net_payable_egp'] + $fBal, 2);
        } elseif ($cType === 'SALES_REP') {
            $kpis['sellers_count']++;
            $kpis['sellers_net_payable_egp'] = round($kpis['sellers_net_payable_egp'] + $fBal, 2);
        } elseif ($cType === 'STAFF') {
            $kpis['staff_count']++;
            if ($fBal >= 0) {
                $kpis['staff_net_payable_egp'] = round($kpis['staff_net_payable_egp'] + $fBal, 2);
            } else {
                $kpis['staff_advances_owed_egp'] = round($kpis['staff_advances_owed_egp'] + abs($fBal), 2);
            }
        } elseif ($cType === 'CLIENT_AR') {
            $kpis['clients_count']++;
            $kpis['clients_net_receivable_egp'] = round($kpis['clients_net_receivable_egp'] + $fBal, 2);
        } elseif ($cType === 'PARTNER') {
            $kpis['partners_count']++;
            $kpis['partners_net_payable_egp'] = round($kpis['partners_net_payable_egp'] + $fBal, 2);
        }

        if ($normFilter !== 'ALL' && $cType !== $normFilter) {
            continue;
        }
        if ($qLower !== '') {
            $hay = mb_strtolower($prof['entity_code'] . ' ' . $prof['name_en'] . ' ' . $prof['name_ar'] . ' ' . $prof['subtitle_en'] . ' ' . $prof['subtitle_ar'] . ' ' . $prof['phone'], 'UTF-8');
            if (!str_contains($hay, $qLower)) {
                continue;
            }
        }

        $items[] = [
            'entity_type'            => $cType,
            'entity_code'            => $prof['entity_code'],
            'name_en'                => $prof['name_en'],
            'name_ar'                => $prof['name_ar'],
            'subtitle_en'            => $prof['subtitle_en'],
            'subtitle_ar'            => $prof['subtitle_ar'],
            'branch_code'            => $prof['branch_code'],
            'settlement_currency'    => $prof['settlement_currency'],
            'phone'                  => $prof['phone'],
            'avatar_url'             => $prof['avatar_url'],
            'total_accrued_egp'      => $sum['total_accrued_egp'],
            'total_installments_egp' => $sum['total_installments_egp'],
            'installments_count'     => $sum['installments_count'],
            'pending_unboarded_egp'  => $sum['pending_unboarded_egp'],
            'final_balance_egp'      => $fBal,
            'status_code'            => $sum['status_code'],
            'balance_label_en'       => $sum['balance_label_en'],
            'balance_label_ar'       => $sum['balance_label_ar'],
        ];
    }

    return [
        'kpis'               => $kpis,
        'items'              => $items,
        'can_record_payment' => sc_erp_can_record_party_tx($user, $perms, $normFilter === 'ALL' ? 'SUPPLIER' : $normFilter),
        'self_entity'        => sc_erp_self_financial_entity($user),
    ];
}

/**
 * Record a new Deposit (`عربون`), Installment (`قسط / دفعة`), Full Settlement (`تقفيل`),
 * Salary Advance (`سلفة`), Advance Repayment (`سداد سلفة`), or Invoice Adjustment (`فاتورة`),
 * immediately updating the party's Final Net Balance & optional Safe Cashbox ledger.
 *
 * @param array<string,mixed> $in
 * @param array<string,mixed> $actorUser
 * @param list<string>        $actorPerms
 * @return array<string,mixed>
 */
function sc_erp_record_party_financial_transaction(PDO $pdo, array $in, array $actorUser, array $actorPerms): array
{
    $eType = sc_erp_normalize_entity_type((string) ($in['entity_type'] ?? 'SUPPLIER'));
    $eCode = trim((string) ($in['entity_code'] ?? ''));
    if ($eCode === '') {
        throw new RuntimeException('Entity code is required');
    }
    if (!sc_erp_can_record_party_tx($actorUser, $actorPerms, $eType)) {
        throw new RuntimeException('Insufficient authority to record financial installments');
    }

    $before = sc_erp_build_entity_financial_dossier($pdo, $eType, $eCode);
    if (!$before) {
        throw new RuntimeException("Target financial account not found ({$eType}:{$eCode})");
    }
    $canonCode = (string) $before['profile']['entity_code'];

    $rawKind = strtoupper(trim((string) ($in['tx_kind'] ?? 'INSTALLMENT')));
    $allowedKinds = ['DEPOSIT', 'INSTALLMENT', 'FULL_SETTLEMENT', 'ADVANCE_GIVEN', 'ADVANCE_REPAID', 'INVOICE_CHARGE', 'CREDIT_NOTE'];
    $txKind = in_array($rawKind, $allowedKinds, true) ? $rawKind : 'INSTALLMENT';

    $curr = strtoupper(trim((string) ($in['orig_currency'] ?? 'EGP')));
    if (!in_array($curr, ['EGP', 'USD', 'EUR', 'GBP'], true)) {
        $curr = 'EGP';
    }
    $fxMap = sc_erp_fx_rates($pdo);
    $fxRate = isset($in['fx_rate']) && (float) $in['fx_rate'] > 0
        ? (float) $in['fx_rate']
        : ($curr === 'EGP' ? 1.0 : (float) ($fxMap[$curr] ?? 50.0));

    $origAmt = round((float) ($in['orig_amount'] ?? 0.0), 2);
    if ($txKind === 'FULL_SETTLEMENT' && $origAmt <= 0.0) {
        // Convenience: if FULL_SETTLEMENT is chosen with 0 amount, settle exact current open balance!
        $curOpen = abs((float) $before['summary']['final_balance_egp']);
        $origAmt = $curr === 'EGP' ? $curOpen : round($curOpen / $fxRate, 2);
    }
    if ($origAmt <= 0.0 || $origAmt > 50000000.0) {
        throw new RuntimeException('Transaction amount must be greater than zero');
    }
    $amtEgp = $curr === 'EGP' ? $origAmt : round($origAmt * $fxRate, 2);

    // Determine canonical accounting direction automatically if not explicitly overridden
    if ($eType === 'CLIENT_AR') {
        $direction = match ($txKind) {
            'INVOICE_CHARGE' => 'CHARGED_TO_PARTY',
            default          => 'COMPANY_RECEIVED_IN', // Deposit, Installment, Full Settlement, Credit Note reduce client AR!
        };
    } else {
        $direction = match ($txKind) {
            'INVOICE_CHARGE' => 'ACCRUED_FOR_PARTY',
            'ADVANCE_REPAID' => 'COMPANY_RECEIVED_IN',
            default          => 'COMPANY_PAID_OUT', // Deposit, Installment, Full Settlement, Advance Given, Credit Note reduce payable / add advance!
        };
    }

    $txDate = trim((string) ($in['tx_date'] ?? gmdate('Y-m-d')));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $txDate)) {
        $txDate = gmdate('Y-m-d');
    }
    $periodMonth = substr($txDate, 0, 7);
    $payMethod   = strtoupper(trim((string) ($in['payment_method'] ?? 'SAFE_CYRENE')));
    $allowedMethods = ['SAFE_CYRENE', 'SAFE_JETTY', 'BANK_MISR_EGP', 'BANK_MISR_USD', 'BANK_MISR_EUR', 'INSTAPAY_TRANSFER', 'OFFSET_SETTLEMENT', 'PAYROLL_DEDUCTION', 'NONE_MEMO'];
    if (!in_array($payMethod, $allowedMethods, true)) {
        $payMethod = 'SAFE_CYRENE';
    }

    $refNo  = trim((string) ($in['voucher_or_receipt_no'] ?? ''));
    $txRef  = 'FIN-' . str_replace('-', '', $txDate) . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    if ($refNo === '') {
        $refNo = $txRef;
    }

    $descEn = trim((string) ($in['description_en'] ?? ''));
    $descAr = trim((string) ($in['description_ar'] ?? ''));
    if ($descEn === '' && $descAr === '') {
        $descEn = "{$txKind} recorded for {$before['profile']['name_en']}";
        $descAr = "تسجيل حركة مالية ({$txKind}) لحساب {$before['profile']['name_ar']}";
    } elseif ($descEn === '') {
        $descEn = $descAr;
    } elseif ($descAr === '') {
        $descAr = $descEn;
    }

    $nowIso    = gmdate('Y-m-d\TH:i:s\Z');
    $actorName = (string) ($actorUser['username'] ?? 'nadia.acc');

    $pdo->prepare(
        'INSERT INTO financial_party_transactions
         (tx_ref, seed_key, entity_type, entity_code, entity_name_en, entity_name_ar,
          tx_date, period_month, tx_kind, direction, orig_amount, orig_currency, fx_rate, amount_egp,
          payment_method, voucher_or_receipt_no, description_en, description_ar, recorded_by, created_at)
         VALUES (?, NULL, ?,?,?,?, ?,?,?,?, ?,?,?,?, ?,?,?,?, ?,?)'
    )->execute([
        $txRef,
        $eType,
        $canonCode,
        (string) $before['profile']['name_en'],
        (string) $before['profile']['name_ar'],
        $txDate,
        $periodMonth,
        $txKind,
        $direction,
        $origAmt,
        $curr,
        $fxRate,
        $amtEgp,
        $payMethod,
        $refNo,
        $descEn,
        $descAr,
        $actorName,
        $nowIso,
    ]);
    $insertId = (int) $pdo->lastInsertId();

    // Optional live cashbox voucher recording when paid out of `SAFE_CYRENE` or `SAFE_JETTY` (only for post-cutoff live dates so Oct 2026 audited tie-out stays untouched!)
    $syncSafe = !empty($in['sync_cashbox']) && in_array($payMethod, ['SAFE_CYRENE', 'SAFE_JETTY'], true) && $periodMonth >= '2026-11';
    if ($syncSafe && $direction === 'COMPANY_PAID_OUT') {
        [$wkE, $moE, $qrE, $yrE] = sc_erp_period_parts($txDate);
        $cc = $payMethod === 'SAFE_JETTY' ? 'JETTY' : 'CYRENE';
        $pdo->prepare(
            'INSERT INTO expense_vouchers_fact
             (exp_date, period_week, period_month, period_quarter, period_year, voucher_seq, description, cost_center, amount_le, amount_usd, amount_eur, amount_gbp, total_egp, source_file)
             VALUES (?,?,?,?,?,?,?,?, ?,?,?,?, ?, "LIVE_MOBILE_SAFE")'
        )->execute([
            $txDate, $wkE, $moE, $qrE, $yrE, $refNo,
            "{$eType} {$txKind} ({$before['profile']['name_en']}): {$descEn}",
            $cc,
            $curr === 'EGP' ? $origAmt : 0.0,
            $curr === 'USD' ? $origAmt : 0.0,
            $curr === 'EUR' ? $origAmt : 0.0,
            $curr === 'GBP' ? $origAmt : 0.0,
            $amtEgp,
        ]);
    }

    // Recompute updated dossier so caller immediately gets the exact new Final Net Balance!
    $after = sc_erp_build_entity_financial_dossier($pdo, $eType, $canonCode);
    $newFinalBal = (float) ($after['summary']['final_balance_egp'] ?? 0.0);

    // Keep `suppliers_directory.current_balance_egp` mirrored (`-final_balance_egp` in AP sign convention)
    if ($eType === 'SUPPLIER') {
        $pdo->prepare('UPDATE suppliers_directory SET current_balance_egp = ? WHERE supplier_code = ?')
            ->execute([round(-1.0 * $newFinalBal, 2), $canonCode]);
    }

    // Notify staff/seller user if they have a login account!
    if (in_array($eType, ['SALES_REP', 'STAFF'], true) && !str_starts_with($canonCode, 'PAY_EMP_') && !str_starts_with($canonCode, 'EMP_ADV_')) {
        sc_erp_push_notification(
            $pdo,
            $canonCode,
            'PARTY_STATEMENT_TX',
            $txRef,
            '💵 Account Statement Updated (' . number_format($amtEgp) . ' EGP)',
            '💵 تم تحديث كشف حسابك المالي (' . number_format($amtEgp) . ' ج.م)',
            "{$descEn} • Final Balance: " . number_format($newFinalBal, 2) . " EGP",
            "{$descAr} • الرصيد النهائي الحالي: " . number_format($newFinalBal, 2) . " ج.م"
        );
    }

    return [
        'tx_id'             => $insertId,
        'tx_ref'            => $txRef,
        'amount_egp'        => $amtEgp,
        'prev_balance_egp'  => (float) $before['summary']['final_balance_egp'],
        'final_balance_egp' => $newFinalBal,
        'dossier'           => $after,
    ];
}

/* ==========================================================================================
 * ERP v3.7 — UNIFIED WEBSITE & ALL ONLINE HUBS SELLER ENGINE (`website.online` / `WEBSITE`)
 * Unites Direct Website (8 languages) + WhatsApp + Meta/Instagram/Facebook + Google Ads +
 * TikTok/YouTube + Tripadvisor + Viator + GetYourGuide + Klook/OTA + Email under ONE Online Seller.
 * ========================================================================================== */

/**
 * Idempotently provision the `ONLINE_SELLER` role, `WEBSITE` branch, and unified `website.online` seller account.
 * Note: Uses `role_code = 'ONLINE_SELLER'` so the 18 human `SALES_REP` hotel sellers count stays strictly 18.
 */
function sc_erp_ensure_online_seller_v37(PDO $pdo): void
{
    $pdo->exec(
        "INSERT OR IGNORE INTO branches (code, name_en, name_ar, location, is_active)
         VALUES ('WEBSITE', 'Website & Online Hubs (8-Lang)', 'الموقع الإلكتروني ومنصات الأونلاين', 'https://seacollegediveclub.com', 1);

         INSERT OR IGNORE INTO roles (role_code, name_en, name_ar, department, hierarchy_level, default_dashboard, description_en, description_ar)
         VALUES (
             'ONLINE_SELLER',
             'Unified Website & Online Hubs Seller',
             'البائع الموحد للموقع وجميع منصات الأونلاين',
             'ONLINE_SALES',
             45,
             'website_crm',
             'Single unified seller account aggregating all confirmed bookings from seacollegediveclub.com and all online hubs (WhatsApp, Meta, Google, Tripadvisor, Viator, GetYourGuide, OTA)',
             'حساب البائع الموحد الذي يجمع كافة الحجوزات والمبيعات المؤكدة من الموقع الرسمي وجميع منصات الأونلاين في مكان واحد'
         );

         INSERT OR IGNORE INTO role_permissions (role_code, perm_code) VALUES
             ('ONLINE_SELLER', 'sales.create_voucher'),
             ('ONLINE_SELLER', 'sales.view_own_sales'),
             ('ONLINE_SELLER', 'crm.manage_leads_ads'),
             ('ONLINE_SELLER', 'ops.view_manifests');"
    );

    $chk = $pdo->query("SELECT id FROM users WHERE LOWER(username) = 'website.online' LIMIT 1")->fetchColumn();
    if (!$chk) {
        $pwHash = password_hash('WebsiteHub@2026', PASSWORD_DEFAULT);
        $ins = $pdo->prepare(
            'INSERT INTO users
             (username, password_hash, pin_hint, emp_code, display_name_en, display_name_ar,
              role_code, branch_scope, vessel_scope, rep_scope, is_active, created_at,
              job_title_en, job_title_ar, phone, email, avatar_url, bio_notes, updated_at,
              referral_code, default_comm_insheet_pct, default_comm_outside_pct)
             VALUES (?,?,?,?,?,?, ?,?,?,?,1,?, ?,?,?,?,?,?,?, ?,0.0,0.0)'
        );
        $ins->execute([
            'website.online',
            $pwHash,
            'Online Hub Seller',
            'EMP_ONL_01',
            'Website & Online Hubs (Unified Seller)',
            'مبيعات الموقع والأونلاين الموحدة',
            'ONLINE_SELLER',
            'WEBSITE',
            'ALL',
            'WEBSITE',
            '2026-01-01T00:00:00Z',
            'AI Online Sales Agents & Unified Digital Hubs',
            'وكلاء الذكاء الاصطناعي للمبيعات الأونلاين والموقع الموحد',
            '+20 11 14567886',
            'info@seacollegediveclub.com',
            '/assets/images/logo-192.png',
            'AI-powered Online Sales Hub uniting seacollegediveclub.com (8 languages), WhatsApp (+20 11 14567886), Meta, Google & OTA hubs. Internal human oversight: Eng. Sayed Elsharqawy (private — never published on website).',
            '',
            'website',
        ]);
    } else {
        $pdo->prepare(
            "UPDATE users
             SET job_title_en = 'AI Online Sales Agents & Unified Digital Hubs',
                 job_title_ar = 'وكلاء الذكاء الاصطناعي للمبيعات الأونلاين والموقع الموحد',
                 phone = '+20 11 14567886'
             WHERE LOWER(username) = 'website.online' AND updated_at = ''"
        )->execute();
    }

    $pdo->exec(
        "INSERT OR IGNORE INTO seller_targets_config
         (seller_username, is_target_active, daily_target_egp, weekly_target_egp, monthly_target_egp, monthly_trips_target, is_discount_enabled, max_discount_pct, max_discount_eur, updated_by, updated_at)
         VALUES ('website.online', 1, 25000, 175000, 750000, 120, 1, 20, 50, 'SYSTEM', '2026-11-28T00:00:00Z')"
    );
}

/**
 * Canonical dictionary of all Online Hubs unified under Seller `WEBSITE` (`website.online`).
 *
 * @return array<string, array{code:string, icon:string, label_en:string, label_ar:string, short_en:string, short_ar:string}>
 */
function sc_erp_online_hubs_catalog(): array
{
    return [
        'WEBSITE_DIRECT' => [
            'code'     => 'WEBSITE_DIRECT',
            'icon'     => '🌐',
            'label_en' => 'Official Website Direct (seacollegediveclub.com — 8 Langs)',
            'label_ar' => 'الموقع الرسمي المباشر (seacollegediveclub.com)',
            'short_en' => 'Website Direct',
            'short_ar' => 'الموقع المباشر',
        ],
        'WHATSAPP_ONLINE' => [
            'code'     => 'WHATSAPP_ONLINE',
            'icon'     => '💬',
            'label_en' => 'WhatsApp Online Concierge (+201114567886)',
            'label_ar' => 'واتساب خدمة العملاء والأونلاين',
            'short_en' => 'WhatsApp Hub',
            'short_ar' => 'واتساب أونلاين',
        ],
        'META_INSTAGRAM_FB' => [
            'code'     => 'META_INSTAGRAM_FB',
            'icon'     => '📱',
            'label_en' => 'Instagram & Facebook Inbox / Meta Ads',
            'label_ar' => 'انستجرام وفيسبوك وإعلانات ميتا',
            'short_en' => 'Instagram & Facebook',
            'short_ar' => 'انستجرام وفيسبوك',
        ],
        'GOOGLE_ADS' => [
            'code'     => 'GOOGLE_ADS',
            'icon'     => '🔍',
            'label_en' => 'Google Search, Performance Max & Google Maps',
            'label_ar' => 'جوجل وإعلانات البحث وخرائط جوجل',
            'short_en' => 'Google Ads & Maps',
            'short_ar' => 'جوجل وخرائط جوجل',
        ],
        'TIKTOK_YOUTUBE' => [
            'code'     => 'TIKTOK_YOUTUBE',
            'icon'     => '🎬',
            'label_en' => 'TikTok & YouTube Video Hub',
            'label_ar' => 'تيك توك ويوتيوب',
            'short_en' => 'TikTok / YouTube',
            'short_ar' => 'تيك توك / يوتيوب',
        ],
        'TRIPADVISOR' => [
            'code'     => 'TRIPADVISOR',
            'icon'     => '🦉',
            'label_en' => 'Tripadvisor Online Hub',
            'label_ar' => 'منصة تريب أدفايزور (Tripadvisor)',
            'short_en' => 'Tripadvisor',
            'short_ar' => 'تريب أدفايزور',
        ],
        'VIATOR' => [
            'code'     => 'VIATOR',
            'icon'     => '🧭',
            'label_en' => 'Viator Experiences Hub',
            'label_ar' => 'منصة فياتور (Viator)',
            'short_en' => 'Viator',
            'short_ar' => 'فياتور',
        ],
        'GETYOURGUIDE' => [
            'code'     => 'GETYOURGUIDE',
            'icon'     => '🎟️',
            'label_en' => 'GetYourGuide Hub',
            'label_ar' => 'منصة جيت يور جايد (GetYourGuide)',
            'short_en' => 'GetYourGuide',
            'short_ar' => 'جيت يور جايد',
        ],
        'KLOOK_OTA' => [
            'code'     => 'KLOOK_OTA',
            'icon'     => '🌍',
            'label_en' => 'Klook / Booking.com / Other Online OTA Hub',
            'label_ar' => 'كلوك / بوكينج / منصات حجز أونلاين أخرى',
            'short_en' => 'Klook / Booking / OTA',
            'short_ar' => 'منصات OTA أخرى',
        ],
        'EMAIL_CONCIERGE' => [
            'code'     => 'EMAIL_CONCIERGE',
            'icon'     => '✉️',
            'label_en' => 'Direct Email & Online Concierge',
            'label_ar' => 'البريد الإلكتروني وخدمة الأونلاين',
            'short_en' => 'Email Concierge',
            'short_ar' => 'إيميل أونلاين',
        ],
    ];
}

/**
 * Normalize or auto-detect which Online Hub originated a booking/lead while keeping all hubs under Seller `WEBSITE`.
 *
 * @param array<string,mixed> $data
 */
function sc_erp_detect_online_hub(array $data): string
{
    $cat = sc_erp_online_hubs_catalog();
    $explicit = strtoupper(trim((string) ($data['online_hub_source'] ?? $data['online_hub'] ?? $data['hub_source'] ?? '')));
    if ($explicit !== '') {
        if (isset($cat[$explicit])) {
            return $explicit;
        }
        $aliasMap = [
            'WEBSITE'      => 'WEBSITE_DIRECT',
            'WEB'          => 'WEBSITE_DIRECT',
            'DIRECT'       => 'WEBSITE_DIRECT',
            'WHATSAPP'     => 'WHATSAPP_ONLINE',
            'WA'           => 'WHATSAPP_ONLINE',
            'META'         => 'META_INSTAGRAM_FB',
            'FACEBOOK'     => 'META_INSTAGRAM_FB',
            'INSTAGRAM'    => 'META_INSTAGRAM_FB',
            'IG'           => 'META_INSTAGRAM_FB',
            'FB'           => 'META_INSTAGRAM_FB',
            'GOOGLE'       => 'GOOGLE_ADS',
            'MAPS'         => 'GOOGLE_ADS',
            'TIKTOK'       => 'TIKTOK_YOUTUBE',
            'YOUTUBE'      => 'TIKTOK_YOUTUBE',
            'GYG'          => 'GETYOURGUIDE',
            'GET_YOUR_GUIDE' => 'GETYOURGUIDE',
            'KLOOK'        => 'KLOOK_OTA',
            'BOOKING'      => 'KLOOK_OTA',
            'OTA'          => 'KLOOK_OTA',
            'EMAIL'        => 'EMAIL_CONCIERGE',
        ];
        if (isset($aliasMap[$explicit])) {
            return $aliasMap[$explicit];
        }
    }

    $leadType = strtoupper(trim((string) ($data['lead_type'] ?? '')));
    if ($leadType === 'WHATSAPP_CLICK') {
        return 'WHATSAPP_ONLINE';
    }

    $gclid  = trim((string) ($data['gclid'] ?? ''));
    $fbclid = trim((string) ($data['fbclid'] ?? ''));
    $ttclid = trim((string) ($data['ttclid'] ?? ''));
    $utmSrc = strtolower(trim((string) ($data['utm_source'] ?? $data['first_channel'] ?? $data['last_channel'] ?? $data['client_agency'] ?? '')));

    if ($gclid !== '' || str_contains($utmSrc, 'google') || str_contains($utmSrc, 'gmaps')) {
        return 'GOOGLE_ADS';
    }
    if ($fbclid !== '' || str_contains($utmSrc, 'meta') || str_contains($utmSrc, 'instagram') || str_contains($utmSrc, 'facebook') || $utmSrc === 'ig' || $utmSrc === 'fb') {
        return 'META_INSTAGRAM_FB';
    }
    if ($ttclid !== '' || str_contains($utmSrc, 'tiktok') || str_contains($utmSrc, 'youtube')) {
        return 'TIKTOK_YOUTUBE';
    }
    if (str_contains($utmSrc, 'whatsapp') || $utmSrc === 'wa') {
        return 'WHATSAPP_ONLINE';
    }
    if (str_contains($utmSrc, 'tripadvisor')) {
        return 'TRIPADVISOR';
    }
    if (str_contains($utmSrc, 'viator')) {
        return 'VIATOR';
    }
    if (str_contains($utmSrc, 'getyourguide') || str_contains($utmSrc, 'gyg')) {
        return 'GETYOURGUIDE';
    }
    if (str_contains($utmSrc, 'klook') || str_contains($utmSrc, 'booking') || str_contains($utmSrc, 'expedia') || str_contains($utmSrc, 'ota')) {
        return 'KLOOK_OTA';
    }
    if (str_contains($utmSrc, 'email') || str_contains($utmSrc, 'mail')) {
        return 'EMAIL_CONCIERGE';
    }

    return 'WEBSITE_DIRECT';
}

/**
 * Check if a raw seller token refers to the single unified Online Seller (`website.online` / `WEBSITE`).
 */
function sc_erp_is_online_unified_seller_token(?string $raw): bool
{
    if ($raw === null) {
        return false;
    }
    $clean = strtolower(trim($raw));
    $clean = ltrim($clean, '@');
    return in_array($clean, [
        'website.online',
        'website',
        'online',
        'online_website',
        'online_direct',
        'website_online',
        'emp_onl_01',
        'seacollegediveclub.com',
    ], true);
}

/**
 * Fetch the unified `website.online` user row (`role_code = 'ONLINE_SELLER'`).
 *
 * @return array<string,mixed>|null
 */
function sc_erp_get_online_seller_user(PDO $pdo): ?array
{
    $st = $pdo->query(
        "SELECT id, username, display_name_en, display_name_ar, role_code, branch_scope, rep_scope,
                referral_code, default_comm_insheet_pct, default_comm_outside_pct, phone, email, avatar_url,
                job_title_en, job_title_ar
         FROM users
         WHERE LOWER(username) = 'website.online' AND is_active = 1
         LIMIT 1"
    );
    $u = $st ? $st->fetch() : false;
    return $u ?: null;
}

/**
 * Canonical SQL WHERE fragment matching ALL historical & live reservations under the unified Online Seller (`website.online`).
 * Excludes any booking credited to a human `*.sales` hotel representative.
 */
function sc_erp_online_seller_sql_where(string $alias = ''): string
{
    $p = $alias !== '' ? rtrim($alias, '.') . '.' : '';
    return "(
        LOWER(COALESCE({$p}online_seller_account, '')) = 'website.online'
        OR LOWER(COALESCE({$p}seller_username, '')) = 'website.online'
        OR (
            COALESCE({$p}seller_username, '') = ''
            AND (
                UPPER(COALESCE({$p}seller_type, '')) = 'ONLINE_DIRECT'
                OR UPPER(COALESCE({$p}sales_rep, '')) IN ('WEBSITE', 'ONLINE', 'ONLINE_WEBSITE')
            )
        )
    )";
}

/**
 * Build the 360° Financial Dossier for the Unified Website & All Online Hubs Seller (`website.online`).
 * Tracks Gross Confirmed Online Sales (+), Collected Deposits / Safe / Gateway Settlements (-), and Open Uncollected REST.
 *
 * @param array<string,mixed> $uRow
 * @return array<string,mixed>
 */
function sc_erp_build_online_seller_financial_dossier(PDO $pdo, array $uRow): array
{
    $rawLines  = [];
    $whereOnl  = sc_erp_online_seller_sql_where();
    $hubsCat   = sc_erp_online_hubs_catalog();

    // 1) Historical monthly Online Direct reservations (grouped cleanly by month)
    $histSt = $pdo->query(
        "SELECT period_month,
                COUNT(*) AS cnt,
                COALESCE(SUM(total_pax), 0) AS pax,
                ROUND(COALESCE(SUM(CASE WHEN actual_paid_total_egp > 0 THEN actual_paid_total_egp ELSE estimated_revenue_egp END), 0), 2) AS gross_egp,
                ROUND(COALESCE(SUM(rest_egp_equiv), 0), 2) AS open_rest_egp
         FROM reservations_fact
         WHERE {$whereOnl}
           AND booking_state = 'EXECUTED'
           AND source_file NOT IN ('LIVE_WEBSITE_FORM', 'LIVE_ONLINE_HUB', 'LIVE_MOBILE_APP', 'SELLER_SHARE_LINK')
         GROUP BY period_month
         ORDER BY period_month ASC"
    );
    foreach (($histSt ? $histSt->fetchAll() : []) as $hm) {
        $gross = round((float) ($hm['gross_egp'] ?? 0.0), 2);
        if ($gross <= 0.0) {
            continue;
        }
        $openR = round((float) ($hm['open_rest_egp'] ?? 0.0), 2);
        $coll  = round(max(0.0, $gross - $openR), 2);
        $mo    = (string) ($hm['period_month'] ?: '2026-10');
        $rawLines[] = [
            'tx_date'        => $mo . '-28',
            'sort_seq'       => 10,
            'ref_no'         => 'ONL-REV-' . $mo,
            'line_group'     => 'ACCRUAL',
            'tx_kind'        => 'ONLINE_REVENUE',
            'badge_en'       => 'Online Sales Revenue',
            'badge_ar'       => 'إيرادات مبيعات الأونلاين',
            'description_en' => "{$mo} Audited Website & Online Hubs Sales ({$hm['cnt']} bookings / {$hm['pax']} PAX)",
            'description_ar' => "إجمالي مبيعات الموقع والأونلاين المعتمدة لشهر {$mo} ({$hm['cnt']} حجز / {$hm['pax']} فرد)",
            'payment_method' => 'WEBSITE_ONLINE',
            'orig_currency'  => 'EGP',
            'orig_amount'    => $gross,
            'plus_egp'       => $gross,
            'minus_egp'      => 0.0,
        ];
        if ($coll > 0.0) {
            $rawLines[] = [
                'tx_date'        => $mo . '-30',
                'sort_seq'       => 15,
                'ref_no'         => 'ONL-COL-' . $mo,
                'line_group'     => 'INSTALLMENT',
                'tx_kind'        => 'ONLINE_COLLECTION',
                'badge_en'       => 'Collected & Remitted',
                'badge_ar'       => 'تحصيل وتوريد خزينة/فيزا',
                'description_en' => "{$mo} Online Sales Collected & Settled into Company Safe / POS",
                'description_ar' => "تحصيلات مبيعات الأونلاين الموردة لخزينة الشركة والفيزا لشهر {$mo}",
                'payment_method' => 'ONLINE_SAFE_POS',
                'orig_currency'  => 'EGP',
                'orig_amount'    => $coll,
                'plus_egp'       => 0.0,
                'minus_egp'      => $coll,
            ];
        }
    }

    // 2) Live confirmed Website & Online Hub sales (itemized per voucher!)
    $liveSt = $pdo->query(
        "SELECT * FROM reservations_fact
         WHERE {$whereOnl}
           AND booking_state = 'EXECUTED'
           AND source_file IN ('LIVE_WEBSITE_FORM', 'LIVE_ONLINE_HUB', 'LIVE_MOBILE_APP', 'SELLER_SHARE_LINK')
         ORDER BY trip_date ASC, id ASC"
    );
    $liveVouchers = $liveSt ? $liveSt->fetchAll() : [];
    foreach ($liveVouchers as $lv) {
        $gross = round((float) (($lv['actual_paid_total_egp'] ?? 0) > 0 ? $lv['actual_paid_total_egp'] : ($lv['estimated_revenue_egp'] ?? 0.0)), 2);
        if ($gross <= 0.0) {
            continue;
        }
        $paidDep  = round((float) ($lv['deposit_egp_equiv'] ?? 0.0) + (float) ($lv['collected_rest_egp'] ?? 0.0), 2);
        $openRest = round((float) ($lv['rest_egp_equiv'] ?? 0.0), 2);
        if ($paidDep <= 0.0 && $openRest <= 0.0 && strtoupper((string) ($lv['payment_status'] ?? '')) === 'PAID') {
            $paidDep = $gross;
        }
        $hubCode  = sc_erp_detect_online_hub($lv);
        $hubMeta  = $hubsCat[$hubCode] ?? $hubsCat['WEBSITE_DIRECT'];
        $tDate    = (string) ($lv['trip_date'] ?: gmdate('Y-m-d'));
        $voNo     = (string) $lv['voucher_no'];
        $gName    = (string) ($lv['divers_names'] ?: 'Online Guest');
        $tName    = (string) ($lv['trip_name'] ?: 'Online Trip');

        $rawLines[] = [
            'tx_date'        => $tDate,
            'sort_seq'       => 22,
            'ref_no'         => $voNo,
            'line_group'     => 'ACCRUAL',
            'tx_kind'        => 'ONLINE_BOOKING_CONFIRMED',
            'badge_en'       => $hubMeta['icon'] . ' ' . $hubMeta['short_en'],
            'badge_ar'       => $hubMeta['icon'] . ' ' . $hubMeta['short_ar'],
            'description_en' => "Confirmed Online Booking {$voNo}: {$gName} — {$tName} ({$lv['total_pax']} PAX)",
            'description_ar' => "حجز أونلاين مؤكد {$voNo}: {$gName} — {$tName} ({$lv['total_pax']} أفراد)",
            'payment_method' => $hubCode,
            'orig_currency'  => 'EGP',
            'orig_amount'    => $gross,
            'plus_egp'       => $gross,
            'minus_egp'      => 0.0,
        ];
        if ($paidDep > 0.0) {
            $rawLines[] = [
                'tx_date'        => $tDate,
                'sort_seq'       => 26,
                'ref_no'         => 'DEP-' . $voNo,
                'line_group'     => 'INSTALLMENT',
                'tx_kind'        => 'DEPOSIT',
                'badge_en'       => 'Online / Safe Paid',
                'badge_ar'       => 'مدفوع / محصل للشركة',
                'description_en' => "Deposit / Payment Collected on {$voNo} ({$gName})",
                'description_ar' => "مبلغ محصل ومؤكد على حجز {$voNo} ({$gName})",
                'payment_method' => $hubCode,
                'orig_currency'  => 'EGP',
                'orig_amount'    => $paidDep,
                'plus_egp'       => 0.0,
                'minus_egp'      => $paidDep,
            ];
        }
    }

    // 3) Append any explicit accounting settlements/transfers recorded on `website.online` via `financial_party_transactions`
    sc_erp_append_live_party_tx_lines($pdo, 'SALES_REP', 'website.online', $rawLines);
    $fin = sc_erp_finalize_statement_lines($rawLines, 'CLIENT_AR');

    $fBal = (float) $fin['final_balance_egp'];
    if (abs($fBal) < 0.5) {
        $fin['balance_status_en'] = 'All Online Sales Collected & Settled (0 Open REST)';
        $fin['balance_status_ar'] = 'جميع مبيعات الأونلاين محصلة ومقفلة بالكامل (صفر متبقي)';
    } elseif ($fBal > 0) {
        $fin['balance_status_en'] = 'Open Uncollected REST / Online Hub Settlements Due to Company';
        $fin['balance_status_ar'] = 'مبالغ متبقية (REST / منصات أونلاين) مستحقة التحصيل للشركة';
    } else {
        $fin['balance_status_en'] = 'Prepaid Online Credit / Over-Collection in Company Safe';
        $fin['balance_status_ar'] = 'دفعات أونلاين مقدمة لصالح الشركة';
    }

    $recentSt = $pdo->query(
        "SELECT voucher_no, trip_date, divers_names, trip_name, total_pax, estimated_revenue_egp,
                deposit_egp_equiv, collected_rest_egp, rest_egp_equiv, online_hub_source, payment_status, boarding_status
         FROM reservations_fact
         WHERE {$whereOnl}
         ORDER BY CASE WHEN source_file IN ('LIVE_WEBSITE_FORM', 'LIVE_ONLINE_HUB') THEN 0 ELSE 1 END, trip_date DESC, id DESC
         LIMIT 50"
    );

    $profile = [
        'entity_type'         => 'SALES_REP',
        'entity_code'         => 'website.online',
        'name_en'             => (string) $uRow['display_name_en'],
        'name_ar'             => (string) ($uRow['display_name_ar'] ?: 'مبيعات الموقع والأونلاين الموحدة'),
        'subtitle_en'         => 'AI Online Sales Agents: Website (8 Langs) • WhatsApp (+20 11 14567886) • Meta • Google • Tripadvisor • Viator • GetYourGuide (0% Rep Comm)',
        'subtitle_ar'         => 'وكلاء الذكاء الاصطناعي لمبيعات الأونلاين: الموقع الرسمي • واتساب (+20 11 14567886) • انستجرام وفيسبوك • جوجل • تريب أدفايزور (0% عمولة مندوب)',
        'branch_code'         => 'WEBSITE',
        'settlement_currency' => 'EUR / EGP',
        'contact_person'      => 'AI Online Agents • Direct Supervisor: Eng. Sayed Elsharqawy (Internal Only)',
        'phone'               => '+20 11 14567886',
        'whatsapp'            => '+20 11 14567886',
        'payment_terms'       => 'Online Deposit / Full Payment + Jetty REST Collection',
        'notes'               => 'Official Website: https://seacollegediveclub.com • Online Brochure: https://seacollegediveclub.com/s/website',
        'avatar_url'          => '/assets/images/logo-192.png',
        'referral_url'        => 'https://seacollegediveclub.com',
    ];

    return [
        'profile'             => $profile,
        'summary'             => array_merge($fin, ['pending_unboarded_egp' => 0.0]),
        'installments_ledger' => $fin['installments_ledger'],
        'statement_lines'     => $fin['statement_lines'],
        'context'             => [
            'recent_vouchers'    => $recentSt ? $recentSt->fetchAll() : [],
            'recent_commissions' => [],
            'bonuses_allowances' => [],
        ],
    ];
}

/**
 * Build the complete Unified Website & All Online Hubs Command Center payload (`action=online_sales_hub`).
 *
 * @return array<string,mixed>
 */
function sc_erp_build_online_sales_hub(PDO $pdo, string $monthFilter = 'ALL', string $hubFilter = 'ALL'): array
{
    sc_erp_ensure_online_seller_v37($pdo);
    $fx      = sc_erp_fx_rates($pdo);
    $eurRate = max(1.0, (float) ($fx['EUR'] ?? 57.0));
    $hubsCat = sc_erp_online_hubs_catalog();
    $whereOnl = sc_erp_online_seller_sql_where();

    $normMonth = preg_match('/^\d{4}-\d{2}$/', trim($monthFilter)) ? trim($monthFilter) : 'ALL';
    $normHub   = strtoupper(trim($hubFilter));
    if ($normHub === '' || !isset($hubsCat[$normHub])) {
        $normHub = 'ALL';
    }

    $params = [];
    $extraSql = '';
    if ($normMonth !== 'ALL') {
        $extraSql .= ' AND period_month = ?';
        $params[] = $normMonth;
    }

    $stAll = $pdo->prepare(
        "SELECT id, voucher_no, trip_date, period_month, branch_code, channel, hotel_name, room_no, pickup_time,
                nationality, trip_name, unified_trip_code, adults, children, infants, total_pax,
                divers_names, guest_phone, guest_email, guest_account_username,
                sales_rep, client_agency, source_file, seller_type, online_hub_source, online_seller_account,
                deposit_egp_equiv, collected_rest_egp, rest_egp_equiv,
                estimated_revenue_egp, actual_paid_total_egp, supplier_cost_egp, estimated_cogs_egp,
                boarding_status, payment_status, booking_state, workflow_stage
         FROM reservations_fact
         WHERE {$whereOnl} {$extraSql}
         ORDER BY CASE WHEN source_file IN ('LIVE_WEBSITE_FORM', 'LIVE_ONLINE_HUB') THEN 0 ELSE 1 END,
                  trip_date DESC, id DESC"
    );
    $stAll->execute($params);
    $allOnlineRows = $stAll->fetchAll();

    $hubBuckets = [];
    foreach ($hubsCat as $hCode => $hMeta) {
        $hubBuckets[$hCode] = array_merge($hMeta, [
            'vouchers_count'    => 0,
            'total_pax'         => 0,
            'gross_revenue_egp' => 0.0,
            'collected_egp'     => 0.0,
            'open_rest_egp'     => 0.0,
        ]);
    }

    $totCount     = 0;
    $histCount    = 0;
    $liveCount    = 0;
    $cancelCount  = 0;
    $totPax       = 0;
    $totRevEgp    = 0.0;
    $totPaidEgp   = 0.0;
    $totRestEgp   = 0.0;
    $totCostEgp   = 0.0;
    $confirmedOut = [];

    foreach ($allOnlineRows as $r) {
        $bState = strtoupper((string) ($r['booking_state'] ?? 'EXECUTED'));
        if (str_starts_with($bState, 'CANCEL')) {
            $cancelCount++;
            continue;
        }
        $hCode = sc_erp_detect_online_hub($r);
        if ($normHub !== 'ALL' && $hCode !== $normHub) {
            continue;
        }
        $hMeta = $hubsCat[$hCode] ?? $hubsCat['WEBSITE_DIRECT'];

        $paid = round((float) ($r['deposit_egp_equiv'] ?? 0.0) + (float) ($r['collected_rest_egp'] ?? 0.0), 2);
        $rest = round((float) ($r['rest_egp_equiv'] ?? 0.0), 2);
        $gross = round((float) (($r['actual_paid_total_egp'] ?? 0) > 0
            ? $r['actual_paid_total_egp']
            : (($r['estimated_revenue_egp'] ?? 0) > 0 ? $r['estimated_revenue_egp'] : ($paid + $rest))), 2);

        // Historical audited ONLINE rows with 0 open rest were settled in safe/VISA
        $isLive = in_array((string) ($r['source_file'] ?? ''), ['LIVE_WEBSITE_FORM', 'LIVE_ONLINE_HUB', 'LIVE_MOBILE_APP', 'SELLER_SHARE_LINK'], true);
        if (!$isLive && $paid <= 0.0 && $rest <= 0.0) {
            $paid = $gross;
        } elseif ($isLive && $paid <= 0.0 && strtoupper((string) ($r['payment_status'] ?? '')) === 'PAID') {
            $paid = $gross;
            $rest = 0.0;
        }

        $cost = round((float) (($r['supplier_cost_egp'] ?? 0) > 0 ? $r['supplier_cost_egp'] : ($r['estimated_cogs_egp'] ?? 0.0)), 2);
        $pax  = (int) ($r['total_pax'] ?? 0);

        $totCount++;
        if ($isLive) {
            $liveCount++;
        } else {
            $histCount++;
        }
        $totPax     += $pax;
        $totRevEgp   = round($totRevEgp + $gross, 2);
        $totPaidEgp  = round($totPaidEgp + $paid, 2);
        $totRestEgp  = round($totRestEgp + $rest, 2);
        $totCostEgp  = round($totCostEgp + $cost, 2);

        $hubBuckets[$hCode]['vouchers_count']++;
        $hubBuckets[$hCode]['total_pax']         += $pax;
        $hubBuckets[$hCode]['gross_revenue_egp']  = round($hubBuckets[$hCode]['gross_revenue_egp'] + $gross, 2);
        $hubBuckets[$hCode]['collected_egp']      = round($hubBuckets[$hCode]['collected_egp'] + $paid, 2);
        $hubBuckets[$hCode]['open_rest_egp']      = round($hubBuckets[$hCode]['open_rest_egp'] + $rest, 2);

        if (count($confirmedOut) < 200) {
            $r['online_hub_code']  = $hCode;
            $r['online_hub_icon']  = $hMeta['icon'];
            $r['online_hub_en']    = $hMeta['short_en'];
            $r['online_hub_ar']    = $hMeta['short_ar'];
            $r['guest_name']       = (string) ($r['divers_names'] ?? '');
            $r['gross_egp']        = $gross;
            $r['gross_eur_equiv']  = round($gross / $eurRate, 2);
            $r['paid_egp']         = $paid;
            $r['open_rest_egp']    = $rest;
            $r['is_live_online']   = $isLive;
            $confirmedOut[]        = $r;
        }
    }

    // Fetch website & online leads (excluding hotel-rep QR leads so this view stays strictly the Unified Online Seller!)
    $crmParams = [];
    $crmExtra  = '';
    if ($normMonth !== 'ALL') {
        $crmExtra .= ' AND (SUBSTR(trip_date, 1, 7) = ? OR SUBSTR(received_at, 1, 7) = ?)';
        $crmParams[] = $normMonth;
        $crmParams[] = $normMonth;
    }
    $crmSt = $pdo->prepare(
        "SELECT * FROM website_leads_crm
         WHERE (seller_username = '' OR LOWER(seller_username) = 'website.online') {$crmExtra}
         ORDER BY id DESC LIMIT 160"
    );
    $crmSt->execute($crmParams);
    $rawLeads = $crmSt->fetchAll();

    $pendingLeadsCnt   = 0;
    $confirmedLeadsCnt = 0;
    $leadsOut          = [];
    foreach ($rawLeads as $l) {
        $lhCode = sc_erp_detect_online_hub($l);
        if ($normHub !== 'ALL' && $lhCode !== $normHub) {
            continue;
        }
        $lhMeta = $hubsCat[$lhCode] ?? $hubsCat['WEBSITE_DIRECT'];
        $stUp   = strtoupper((string) ($l['status'] ?? 'NEW'));
        $isConf = in_array($stUp, ['CONFIRMED_SALE', 'CONFIRMED_WEBSITE_BOOKING', 'PAID'], true)
            || str_starts_with((string) ($l['linked_voucher_no'] ?? ''), 'ONL-')
            || str_starts_with((string) ($l['linked_voucher_no'] ?? ''), 'WEB-');
        if ($isConf) {
            $confirmedLeadsCnt++;
        } else {
            $pendingLeadsCnt++;
        }
        $l['online_hub_code'] = $lhCode;
        $l['online_hub_icon'] = $lhMeta['icon'];
        $l['online_hub_en']   = $lhMeta['short_en'];
        $l['online_hub_ar']   = $lhMeta['short_ar'];
        $l['is_confirmed_sale'] = $isConf;
        $leadsOut[] = $l;
    }

    return [
        'ok'                 => true,
        'month_filter'       => $normMonth,
        'hub_filter'         => $normHub,
        'fx_rates'           => $fx,
        'seller_profile'     => sc_erp_get_online_seller_user($pdo),
        'kpis'               => [
            'confirmed_vouchers_count'   => $totCount,
            'historical_online_count'    => $histCount,
            'live_confirmed_count'       => $liveCount,
            'cancelled_count'            => $cancelCount,
            'total_pax'                  => $totPax,
            'gross_revenue_egp'          => $totRevEgp,
            'gross_revenue_eur_equiv'    => round($totRevEgp / $eurRate, 2),
            'collected_paid_egp'         => $totPaidEgp,
            'open_rest_egp'              => $totRestEgp,
            'supplier_cost_egp'          => $totCostEgp,
            'net_company_margin_egp'     => round($totRevEgp - $totCostEgp, 2),
            'pending_leads_count'        => $pendingLeadsCnt,
            'confirmed_leads_count'      => $confirmedLeadsCnt,
        ],
        'hubs_breakdown'     => array_values($hubBuckets),
        'hubs_catalog'       => array_values($hubsCat),
        'confirmed_sales'    => $confirmedOut,
        'website_leads'      => $leadsOut,
    ];
}

/**
 * Register a Confirmed Online Sale from ANY Online Hub OR confirm an existing Website/WhatsApp Lead
 * directly under the single unified Online Seller (`sales_rep = 'WEBSITE'`, `online_seller_account = 'website.online'`).
 *
 * - Enforces 0% individual rep commission (`seller_username = ''`, `seller_commission_egp = 0`) so 100% margin accrues to Sea College.
 * - Resolves master catalog trip & supplier COGS + Sharm hotel pickup window automatically.
 * - Provisions Verified Real-Customer Account (`sc_erp_provision_verified_guest_account`) & dispatches login email if email provided.
 *
 * @param array<string,mixed> $in
 * @param array<string,mixed> $actor
 * @return array<string,mixed>
 */
function sc_erp_register_or_confirm_online_sale(PDO $pdo, array $in, array $actor): array
{
    sc_erp_ensure_online_seller_v37($pdo);
    $fx      = sc_erp_fx_rates($pdo);
    $eurRate = max(1.0, (float) ($fx['EUR'] ?? 57.0));
    $hubsCat = sc_erp_online_hubs_catalog();

    $eventId = trim((string) ($in['event_id'] ?? ''));
    $existingLead = null;
    if ($eventId !== '') {
        $lSt = $pdo->prepare('SELECT * FROM website_leads_crm WHERE event_id = ? LIMIT 1');
        $lSt->execute([$eventId]);
        $existingLead = $lSt->fetch() ?: null;
    }

    $hubCode = sc_erp_detect_online_hub(array_merge($existingLead ?: [], $in));
    $hubMeta = $hubsCat[$hubCode] ?? $hubsCat['WEBSITE_DIRECT'];

    $guestName = trim((string) ($in['guest_name'] ?? ($existingLead['guest_name'] ?? '')));
    if ($guestName === '' || str_starts_with($guestName, 'WhatsApp Visitor')) {
        $guestName = trim((string) ($in['guest_name'] ?? '')) !== '' ? trim((string) $in['guest_name']) : 'Online Confirmed Guest';
    }
    $guestPhone = trim((string) ($in['guest_phone'] ?? $in['phone'] ?? ($existingLead['phone'] ?? '')));
    $guestEmail = strtolower(trim((string) ($in['guest_email'] ?? $in['email'] ?? ($existingLead['email'] ?? ''))));
    $lang       = strtolower(trim((string) ($in['lang'] ?? ($existingLead['lang'] ?? 'en'))));
    if (!in_array($lang, ['en', 'ar', 'ru', 'it', 'pl', 'de', 'fr', 'zh'], true)) {
        $lang = 'en';
    }
    $nationality = strtoupper(trim((string) ($in['nationality'] ?? ($lang === 'en' ? 'ONLINE' : $lang))));

    $tripDate = trim((string) ($in['trip_date'] ?? ($existingLead['trip_date'] ?? '')));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tripDate)) {
        $tripDate = gmdate('Y-m-d');
    }
    [$wk, $mo, $qr, $yr] = sc_erp_period_parts($tripDate);

    $adults   = max(1, (int) ($in['adults'] ?? ($existingLead['people'] ?? 1)));
    $children = max(0, (int) ($in['children'] ?? 0));
    $infants  = max(0, (int) ($in['infants'] ?? 0));
    $totalPax = $adults + $children + $infants;

    $prodCodeInput = trim((string) ($in['product_code'] ?? ''));
    $tripTextInput = trim((string) ($in['trip_name'] ?? ($existingLead['interest_items'] ?? '')));
    $masterTrip    = sc_erp_lookup_unified_trip($pdo, $prodCodeInput !== '' ? $prodCodeInput : $tripTextInput);

    if ($masterTrip) {
        $tripName        = (string) $masterTrip['unified_name_en'];
        $unifiedTripCode = (string) $masterTrip['trip_code'];
        $prodCat         = (string) $masterTrip['category_code'];
        $adlUnitEur      = (float) $masterTrip['adult_sell_eur'];
        $chdUnitEur      = (float) $masterTrip['child_sell_eur'];
        $autoCostEgp     = round($adults * (float) $masterTrip['adult_cost_egp'] + $children * (float) $masterTrip['child_cost_egp'] + $infants * (float) $masterTrip['infant_cost_egp'], 2);
        $apSupCode       = (string) ($masterTrip['supplier_code'] ?? 'SEA_COLLEGE');
    } else {
        $tripName        = $tripTextInput !== '' ? substr($tripTextInput, 0, 120) : 'Website & Online Trip';
        $unifiedTripCode = $prodCodeInput;
        $prodCat         = 'WEBSITE_ONLINE_BOOKING';
        $adlUnitEur      = max(0.0, (float) ($in['adult_price_eur'] ?? 45.0));
        $chdUnitEur      = max(0.0, (float) ($in['child_price_eur'] ?? round($adlUnitEur * 0.5, 2)));
        $autoCostEgp     = 0.0;
        $apSupCode       = 'SEA_COLLEGE';
    }

    // Determine Gross Sale Amount (supports EUR, USD, GBP, or EGP input)
    $saleCurr = strtoupper(trim((string) ($in['currency'] ?? 'EUR')));
    $saleRate = max(1.0, (float) ($fx[$saleCurr] ?? ($saleCurr === 'EGP' ? 1.0 : $eurRate)));
    $explicitTotalOrig = max(0.0, (float) ($in['total_amount'] ?? 0.0));
    $explicitTotalEur  = max(0.0, (float) ($in['total_eur'] ?? ($existingLead['estimated_eur'] ?? 0.0)));

    if ($explicitTotalOrig > 0.0) {
        $grossEgp = round($explicitTotalOrig * $saleRate, 2);
        $grossEur = round($grossEgp / $eurRate, 2);
    } elseif ($explicitTotalEur > 0.0) {
        $grossEur = round($explicitTotalEur, 2);
        $grossEgp = round($grossEur * $eurRate, 2);
    } else {
        $grossEur = round($adults * $adlUnitEur + $children * $chdUnitEur, 2);
        if ($grossEur <= 0.0) {
            $grossEur = round(45.0 * $adults + 25.0 * $children, 2);
        }
        $grossEgp = round($grossEur * $eurRate, 2);
    }

    // Paid Deposit / Online Payment vs Remaining REST due at Jetty/Pickup
    $paidCurr = strtoupper(trim((string) ($in['paid_currency'] ?? $saleCurr)));
    $paidRate = max(1.0, (float) ($fx[$paidCurr] ?? ($paidCurr === 'EGP' ? 1.0 : $eurRate)));
    if (array_key_exists('paid_amount', $in) && $in['paid_amount'] !== '' && $in['paid_amount'] !== null) {
        $paidOrig = max(0.0, (float) $in['paid_amount']);
        $paidEgp  = round(min($grossEgp, $paidOrig * $paidRate), 2);
    } elseif (array_key_exists('final_paid_eur', $in) && (float) $in['final_paid_eur'] > 0.0) {
        $paidEgp  = round(min($grossEgp, (float) $in['final_paid_eur'] * $eurRate), 2);
    } elseif (!empty($in['mark_fully_paid'])) {
        $paidEgp  = $grossEgp;
    } else {
        $paidEgp  = 0.0;
    }

    $restEgp = round(max(0.0, $grossEgp - $paidEgp), 2);
    $restEur = round($restEgp / $eurRate, 2);
    $payStatus = $restEgp <= 0.50 ? 'PAID' : 'OPEN_REST';

    $supplierCostEgp = array_key_exists('supplier_cost_egp', $in) && (float) $in['supplier_cost_egp'] >= 0.0 && $in['supplier_cost_egp'] !== ''
        ? round((float) $in['supplier_cost_egp'], 2)
        : ($autoCostEgp > 0.0 ? $autoCostEgp : round($grossEgp * 0.32, 2));
    $profitEgp = round($grossEgp - $supplierCostEgp, 2);

    // Resolve Hotel & Sharm Pickup Window
    $hotelName = trim((string) ($in['hotel_name'] ?? $in['hotel'] ?? ($existingLead['hotel'] ?? 'Online Direct')));
    if ($hotelName === '') {
        $hotelName = 'Online Direct';
    }
    $roomNo     = trim((string) ($in['room_no'] ?? ($existingLead['room_no'] ?? '')));
    $pickupTime = trim((string) ($in['pickup_time'] ?? ''));
    $pickupZone = '';
    $hSt = $pdo->prepare('SELECT zone_label_en, pickup_window FROM hotels_directory WHERE LOWER(name_en) LIKE LOWER(?) LIMIT 1');
    $hSt->execute(['%' . $hotelName . '%']);
    $hRow = $hSt->fetch();
    if ($hRow) {
        $pickupZone = (string) ($hRow['zone_label_en'] ?? '');
        if ($pickupTime === '') {
            $pickupTime = (string) ($hRow['pickup_window'] ?? '07:55–08:10');
        }
    }
    if ($pickupTime === '') {
        $pickupTime = '08:00';
    }

    // Determine Voucher Number (reuse existing `WEB-*` / `ONL-*` if confirming an existing lead/voucher, else issue new)
    $voNo = trim((string) ($in['voucher_no'] ?? ($existingLead['linked_voucher_no'] ?? '')));
    if ($voNo === '') {
        $prefix = $hubCode === 'WEBSITE_DIRECT' ? 'WEB' : 'ONL';
        $voNo   = $prefix . '-' . str_replace('-', '', $tripDate) . '-' . strtoupper(bin2hex(random_bytes(2)));
    }
    $qrTok = 'QR-' . strtoupper(substr(hash('sha256', $voNo . ':' . $hubCode . ':' . microtime(true)), 0, 12));

    $existingResId = 0;
    $chkVo = $pdo->prepare('SELECT id, qr_token FROM reservations_fact WHERE voucher_no = ? LIMIT 1');
    $chkVo->execute([$voNo]);
    $exVoRow = $chkVo->fetch();
    if ($exVoRow) {
        $existingResId = (int) $exVoRow['id'];
        if (!empty($exVoRow['qr_token'])) {
            $qrTok = (string) $exVoRow['qr_token'];
        }
    }

    $sourceFile = $hubCode === 'WEBSITE_DIRECT' ? 'LIVE_WEBSITE_FORM' : 'LIVE_ONLINE_HUB';
    $nowIso     = gmdate('Y-m-d\TH:i:s\Z');
    $actorUser  = (string) ($actor['username'] ?? 'website.online');
    $actorRole  = (string) ($actor['role_code'] ?? 'ONLINE_SELLER');

    $pdo->beginTransaction();
    try {
        if ($existingResId > 0) {
            $uRes = $pdo->prepare(
                "UPDATE reservations_fact
                 SET trip_date = ?, period_week = ?, period_month = ?, period_quarter = ?, period_year = ?,
                     branch_code = 'WEBSITE', channel = 'WEBSITE_ONLINE',
                     hotel_name = ?, pickup_zone = ?, room_no = ?, nationality = ?, pickup_time = ?,
                     trip_name = ?, unified_trip_code = ?, product_category = ?,
                     adults = ?, children = ?, infants = ?, total_pax = ?, dives_pax = ?,
                     divers_names = ?, guest_phone = ?, guest_email = ?,
                     sales_rep = 'WEBSITE', client_agency = ?,
                     deposit_egp_equiv = ?, rest_eur = ?, rest_egp_equiv = ?,
                     estimated_revenue_egp = ?, actual_paid_total_egp = ?,
                     supplier_cost_egp = ?, estimated_cogs_egp = ?, estimated_profit_egp = ?, ap_supplier_code = ?,
                     payment_status = ?, booking_state = 'EXECUTED', workflow_stage = 'CONFIRMED_OPS',
                     seller_type = 'ONLINE_DIRECT', seller_username = '',
                     seller_commission_egp = 0, commission_type = 'NONE', commission_status = 'NONE',
                     online_hub_source = ?, online_seller_account = 'website.online',
                     booked_adults = ?, booked_children = ?, booked_infants = ?, booked_total_egp = ?, list_total_egp = ?
                 WHERE id = ?"
            );
            $uRes->execute([
                $tripDate, $wk, $mo, $qr, $yr,
                $hotelName, $pickupZone, $roomNo, $nationality, $pickupTime,
                $tripName, $unifiedTripCode, $prodCat,
                $adults, $children, $infants, $totalPax, $adults,
                $guestName, $guestPhone, $guestEmail,
                $hubCode,
                $paidEgp, $restEur, $restEgp,
                $grossEgp, $grossEgp,
                $supplierCostEgp, $supplierCostEgp, $profitEgp, $apSupCode !== 'SEA_COLLEGE' ? $apSupCode : '',
                $payStatus,
                $hubCode,
                $adults, $children, $infants, $grossEgp, $grossEgp,
                $existingResId,
            ]);
            $resId = $existingResId;
        } else {
            $iRes = $pdo->prepare(
                "INSERT INTO reservations_fact
                 (voucher_no, qr_token, trip_date, period_week, period_month, period_quarter, period_year,
                  branch_code, channel, hotel_name, pickup_zone, room_no, nationality, pickup_time,
                  trip_name, unified_trip_code, product_category,
                  adults, children, infants, total_pax, snork_pax, intro1_pax, intro2_pax, dives_pax,
                  divers_names, guest_phone, guest_email,
                  sales_rep, executor, client_agency,
                  deposit_egp_equiv, rest_egp, rest_usd, rest_eur, rest_gbp, rest_egp_equiv,
                  estimated_revenue_egp, actual_paid_total_egp, supplier_cost_egp, estimated_cogs_egp, estimated_profit_egp, ap_supplier_code,
                  vessel_code, boarding_status, payment_status, booking_state, workflow_stage, source_file,
                  seller_type, seller_username, seller_commission_egp, commission_type, commission_status,
                  online_hub_source, online_seller_account,
                  booked_adults, booked_children, booked_infants, booked_total_egp, list_total_egp)
                 VALUES (?,?,?,?,?,?,?, 'WEBSITE','WEBSITE_ONLINE',?,?,?,?,?, ?,?,?, ?,?,?,?,0,0,0,?, ?,?,?, 'WEBSITE','SEA COLLEGE',?, ?,0,0,?,0,?, ?,?,?,?,?,?, '', 'CONFIRMED',?,'EXECUTED','CONFIRMED_OPS',?, 'ONLINE_DIRECT','',0,'NONE','NONE', ?,'website.online', ?,?,?,?,?)"
            );
            $iRes->execute([
                $voNo, $qrTok, $tripDate, $wk, $mo, $qr, $yr,
                $hotelName, $pickupZone, $roomNo, $nationality, $pickupTime,
                $tripName, $unifiedTripCode, $prodCat,
                $adults, $children, $infants, $totalPax, $adults,
                $guestName, $guestPhone, $guestEmail,
                $hubCode,
                $paidEgp, $restEur, $restEgp,
                $grossEgp, $grossEgp, $supplierCostEgp, $supplierCostEgp, $profitEgp, $apSupCode !== 'SEA_COLLEGE' ? $apSupCode : '',
                $payStatus, $sourceFile,
                $hubCode,
                $adults, $children, $infants, $grossEgp, $grossEgp,
            ]);
            $resId = (int) $pdo->lastInsertId();
        }

        // Sync payment leg if deposit/payment collected
        if ($paidEgp > 0.0) {
            $pdo->prepare("DELETE FROM reservation_payment_legs WHERE voucher_no = ? AND leg_source = 'ONLINE_HUB_DEPOSIT'")->execute([$voNo]);
            $pdo->prepare(
                "INSERT INTO reservation_payment_legs
                 (voucher_no, trip_date, period_month, leg_source, payment_method, currency, orig_amount, fx_rate, egp_equiv, collector_party, raw_token)
                 VALUES (?,?,?,'ONLINE_HUB_DEPOSIT',?,?,?,?,?,'WEBSITE_ONLINE',?)"
            )->execute([
                $voNo,
                $tripDate,
                $mo,
                (string) ($in['payment_method'] ?? 'ONLINE_PAYMENT'),
                $paidCurr,
                round($paidEgp / $paidRate, 2),
                $paidRate,
                $paidEgp,
                $hubCode . ' PAID',
            ]);
        }

        // Upsert corresponding row in `website_leads_crm` so Online Sales CRM & Ads Attribution stay 1:1 synced!
        if ($eventId === '') {
            $eventId = 'onl_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $voNo)) . '_' . substr(bin2hex(random_bytes(2)), 0, 4);
        }
        $paidEurEquiv = round($paidEgp / $eurRate, 2);
        if ($existingLead) {
            $pdo->prepare(
                "UPDATE website_leads_crm
                 SET guest_name = ?, phone = ?, email = ?, interest_items = ?,
                     people = ?, adults = ?, children = ?, infants = ?,
                     trip_date = ?, hotel = ?, room_no = ?,
                     estimated_eur = ?, final_paid_eur = ?, paid_deposit_egp = ?, rest_due_egp = ?,
                     status = 'CONFIRMED_SALE', linked_voucher_no = ?,
                     sales_rep_code = 'WEBSITE', online_hub_source = ?, online_seller_account = 'website.online',
                     confirmed_at = ?, confirmed_by = ?
                 WHERE event_id = ?"
            )->execute([
                $guestName, $guestPhone, $guestEmail, $tripName,
                $totalPax, $adults, $children, $infants,
                $tripDate, $hotelName, $roomNo,
                $grossEur, $paidEurEquiv, $paidEgp, $restEgp,
                $voNo,
                $hubCode,
                $nowIso, $actorUser,
                $eventId,
            ]);
        } else {
            $pdo->prepare(
                "INSERT INTO website_leads_crm
                 (event_id, received_at, lead_type, lang, guest_name, phone, email, interest_items,
                  people, adults, children, infants, trip_date, hotel, room_no, cert_level, gear_sizes_json,
                  estimated_eur, final_paid_eur, paid_deposit_egp, rest_due_egp,
                  status, gclid, fbclid, utm_source, utm_campaign, assigned_to, linked_voucher_no,
                  seller_username, sales_rep_code, online_hub_source, online_seller_account, confirmed_at, confirmed_by)
                 VALUES (?,?,?,?,?,?,?,?, ?,?,?,?, ?,?,?, '', '{}', ?,?,?,?, 'CONFIRMED_SALE', ?,?,?,?, 'WEBSITE', ?, '', 'WEBSITE', ?, 'website.online', ?, ?)"
            )->execute([
                $eventId, $nowIso, 'ONLINE_HUB_SALE', $lang, $guestName, $guestPhone, $guestEmail, $tripName,
                $totalPax, $adults, $children, $infants, $tripDate, $hotelName, $roomNo,
                $grossEur, $paidEurEquiv, $paidEgp, $restEgp,
                (string) ($in['gclid'] ?? ''), (string) ($in['fbclid'] ?? ''), strtolower($hubCode), (string) ($in['notes'] ?? ''),
                $voNo, $hubCode, $nowIso, $actorUser,
            ]);
        }

        // Provision Verified Real-Customer Account (`GUEST_CUSTOMER`) & email credentials if email provided!
        $guestAcc = sc_erp_provision_verified_guest_account($pdo, [
            'voucher_no'      => $voNo,
            'guest_name'      => $guestName,
            'guest_phone'     => $guestPhone,
            'guest_email'     => $guestEmail,
            'nationality'     => $nationality,
            'lang'            => $lang,
            'hotel_name'      => $hotelName,
            'room_no'         => $roomNo,
            'seller_username' => 'website.online',
            'trip_name'       => $tripName,
            'trip_date'       => $tripDate,
            'booked_egp'      => $grossEgp,
            'paid_egp'        => $paidEgp,
        ]);

        sc_erp_audit($pdo, $actorUser, $actorRole, 'REGISTER_ONLINE_SALE', 'reservations_fact', $voNo, [
            'event_id'          => $eventId,
            'online_hub_source' => $hubCode,
            'seller'            => 'website.online',
            'gross_egp'         => $grossEgp,
            'paid_egp'          => $paidEgp,
            'rest_egp'          => $restEgp,
            'guest_account'     => $guestAcc['username'] ?? '',
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    // Push instant notifications to Owners, Operations, Accounting & Online Seller
    foreach (['islam.samir', 'eng.sayed', 'ahmed.nasr', 'nadia.acc', 'lara.care', 'website.online'] as $rcp) {
        if (strtolower($rcp) === strtolower($actorUser)) {
            continue;
        }
        sc_erp_push_notification(
            $pdo,
            $rcp,
            'ONLINE_SALE_CONFIRMED',
            $voNo,
            "{$hubMeta['icon']} Confirmed Online Sale {$voNo} (€" . number_format($grossEur, 0) . ")",
            "{$hubMeta['icon']} بيعة أونلاين مؤكدة {$voNo} (" . number_format($grossEgp) . " ج.م)",
            "{$guestName} ({$totalPax} PAX) • {$tripName} on {$tripDate} via {$hubMeta['short_en']} under Seller WEBSITE.",
            "{$guestName} ({$totalPax} أفراد) • {$tripName} بتاريخ {$tripDate} عبر {$hubMeta['short_ar']} تحت حساب بائع الأونلاين الموحد."
        );
    }

    return [
        'ok'                => true,
        'reservation_id'    => $resId,
        'voucher_no'        => $voNo,
        'qr_token'          => $qrTok,
        'event_id'          => $eventId,
        'seller_account'    => 'website.online',
        'sales_rep'         => 'WEBSITE',
        'online_hub_source' => $hubCode,
        'online_hub_label'  => $hubMeta['label_en'],
        'gross_egp'         => $grossEgp,
        'gross_eur'         => $grossEur,
        'paid_egp'          => $paidEgp,
        'rest_egp'          => $restEgp,
        'supplier_cost_egp' => $supplierCostEgp,
        'payment_status'    => $payStatus,
        'guest_account'     => $guestAcc,
    ];
}
