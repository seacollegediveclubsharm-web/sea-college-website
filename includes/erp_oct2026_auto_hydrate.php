<?php
/**
 * One-time idempotent auto-hydration of October 2026 closing schedules & commissions
 * for existing Hostinger SQLite warehouses (`storage/erp/sea_college_bigdata.sqlite3`).
 *
 * - No-op (<0.1ms) when `bank_balances_monthly` already contains `2026-10` rows.
 * - Preserves all `LIVE_MOBILE_APP` / `LIVE_WEBSITE_FORM` / `TRIAL-*` rows & user profiles.
 */
declare(strict_types=1);

function sc_erp_auto_hydrate_oct2026_if_needed(PDO $pdo): void
{
    try {
        $hasOctBanks = (int) $pdo->query("SELECT COUNT(*) FROM bank_balances_monthly WHERE period_month='2026-10'")->fetchColumn();
        if ($hasOctBanks > 0) {
            return;
        }
        $binPath = __DIR__ . '/erp_oct2026_pack.bin';
        if (!is_file($binPath)) {
            return;
        }
        $rawBin = @file_get_contents($binPath);
        if ($rawBin === false || $rawBin === '') {
            return;
        }
        $jsonStr = @zlib_decode($rawBin);
        if (!is_string($jsonStr) || $jsonStr === '') {
            return;
        }
        $data = json_decode($jsonStr, true);
        if (!is_array($data)) {
            return;
        }

        $insertSpec = static function (PDO $db, string $table, array $spec, bool $orIgnore = false): void {
            $cols = $spec['cols'] ?? [];
            $rows = $spec['rows'] ?? [];
            if (!$cols || !$rows) {
                return;
            }
            $haveCols = array_flip(array_column($db->query("PRAGMA table_info({$table})")->fetchAll(), 'name'));
            $keepIdx = [];
            $keepCols = [];
            foreach ($cols as $idx => $c) {
                if (isset($haveCols[$c])) {
                    $keepIdx[] = $idx;
                    $keepCols[] = $c;
                }
            }
            if (!$keepCols) {
                return;
            }
            $verb = $orIgnore ? 'INSERT OR IGNORE INTO' : 'INSERT OR REPLACE INTO';
            $ph = implode(',', array_fill(0, count($keepCols), '?'));
            $colSql = implode(',', $keepCols);
            $stmt = $db->prepare("{$verb} {$table} ({$colSql}) VALUES ({$ph})");
            foreach ($rows as $r) {
                $vals = [];
                foreach ($keepIdx as $ki) {
                    $vals[] = $r[$ki] ?? null;
                }
                $stmt->execute($vals);
            }
        };

        $pdo->beginTransaction();

        $insertSpec($pdo, 'currencies_fx', $data['currencies_fx'] ?? []);

        $pdo->exec("DELETE FROM daily_revenue_fact WHERE period_month='2026-10'");
        $insertSpec($pdo, 'daily_revenue_fact', $data['daily_revenue_fact'] ?? []);

        $pdo->exec("DELETE FROM pnl_monthly_lines WHERE period_month IN ('2026-08','2026-09','2026-10')");
        $insertSpec($pdo, 'pnl_monthly_lines', $data['pnl_monthly_lines'] ?? []);

        $pdo->exec("DELETE FROM cashbox_daily_fact WHERE period_month='2026-10'");
        $insertSpec($pdo, 'cashbox_daily_fact', $data['cashbox_daily_fact'] ?? []);

        $pdo->exec("DELETE FROM payroll_monthly_fact WHERE period_month='2026-10'");
        $insertSpec($pdo, 'payroll_monthly_fact', $data['payroll_monthly_fact'] ?? []);

        $pdo->exec("DELETE FROM bank_balances_monthly WHERE period_month='2026-10'");
        $insertSpec($pdo, 'bank_balances_monthly', $data['bank_balances_monthly'] ?? []);

        $pdo->exec("DELETE FROM open_balances_monthly WHERE period_month='2026-10'");
        $insertSpec($pdo, 'open_balances_monthly', $data['open_balances_monthly'] ?? []);

        $pdo->exec("DELETE FROM charter_trips_monthly WHERE period_month='2026-10'");
        $insertSpec($pdo, 'charter_trips_monthly', $data['charter_trips_monthly'] ?? []);

        $pdo->exec("DELETE FROM partner_statements_monthly WHERE period_month='2026-10'");
        $insertSpec($pdo, 'partner_statements_monthly', $data['partner_statements_monthly'] ?? []);

        $pdo->exec("DELETE FROM supermarket_monthly_lines WHERE period_month='2026-10'");
        $insertSpec($pdo, 'supermarket_monthly_lines', $data['supermarket_monthly_lines'] ?? []);

        $pdo->exec("DELETE FROM reservation_accounting_audit WHERE period_month='2026-10'");
        $insertSpec($pdo, 'reservation_accounting_audit', $data['reservation_accounting_audit'] ?? []);

        $pdo->exec("DELETE FROM seller_commissions_ledger WHERE source_channel NOT IN ('LIVE_MOBILE_APP','WEBSITE_QR','LIVE_WEBSITE_FORM') AND voucher_no NOT LIKE 'TRIAL-%'");
        $insertSpec($pdo, 'seller_commissions_ledger', $data['seller_commissions_ledger'] ?? [], true);

        $pdo->exec("DELETE FROM journal_lines WHERE journal_id IN (SELECT id FROM journal_entries WHERE entry_date LIKE '2026-10%' AND source_module NOT IN ('LIVE_MOBILE_APP','LIVE_SAFE_TX'))");
        $pdo->exec("DELETE FROM journal_entries WHERE entry_date LIKE '2026-10%' AND source_module NOT IN ('LIVE_MOBILE_APP','LIVE_SAFE_TX')");
        $insertSpec($pdo, 'journal_entries', $data['journal_entries'] ?? [], true);

        $jlSpec = $data['journal_lines'] ?? [];
        if (!empty($jlSpec['rows'])) {
            $idMap = [];
            foreach ($pdo->query("SELECT id, entry_no FROM journal_entries WHERE entry_date LIKE '2026-10%'")->fetchAll() as $jh) {
                $idMap[(string) $jh['entry_no']] = (int) $jh['id'];
            }
            $stJl = $pdo->prepare(
                'INSERT INTO journal_lines (journal_id, account_code, debit_egp, credit_egp, orig_currency, orig_amount, vessel_code)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($jlSpec['rows'] as $r) {
                $eno = (string) ($r[0] ?? '');
                if (!isset($idMap[$eno])) {
                    continue;
                }
                $stJl->execute([$idMap[$eno], $r[1], $r[2], $r[3], $r[4], $r[5], $r[6]]);
            }
        }

        $pdo->exec("DELETE FROM reservations_fact WHERE period_month='2026-10' AND source_file NOT IN ('LIVE_MOBILE_APP','LIVE_WEBSITE_FORM') AND voucher_no NOT LIKE 'TRIAL-%'");
        $insertSpec($pdo, 'reservations_fact', $data['reservations_oct'] ?? []);

        $pdo->exec("DELETE FROM reservation_payment_legs WHERE period_month='2026-10' AND voucher_no NOT LIKE 'TRIAL-%' AND voucher_no NOT LIKE 'WEB-%'");
        $insertSpec($pdo, 'reservation_payment_legs', $data['reservation_payment_legs'] ?? []);

        foreach (sc_erp_canonical_sellers_spec() as $s) {
            [$uName, , , $repScope] = $s;
            $stBack = $pdo->prepare("UPDATE reservations_fact SET seller_username = ? WHERE UPPER(TRIM(sales_rep)) = ? AND (seller_username IS NULL OR seller_username = '')");
            $stBack->execute([$uName, strtoupper($repScope)]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('SC_ERP Oct 2026 auto-hydration warning: ' . $e->getMessage());
    }
}
