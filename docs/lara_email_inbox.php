<?php
/**
 * Lara — Direct Email Inbox CLI (`info@seacollegediveclub.com`).
 *
 * Usage:
 *   php docs/lara_email_inbox.php status
 *   php docs/lara_email_inbox.php list
 *   php docs/lara_email_inbox.php reply-uid <uid> [--send]
 *   php docs/lara_email_inbox.php poll [--send]
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: CLI only.');
}

require_once dirname(__DIR__) . '/includes/lara_email_concierge.php';

$cmd = strtolower(trim((string) ($argv[1] ?? 'status')));

if ($cmd === 'status') {
    echo "=== Lara — Direct Email Concierge Status ===\n";
    echo "Mailbox       : " . SMTP_USER . " (" . IMAP_HOST . ":" . IMAP_PORT . " -> " . SMTP_HOST . ":" . SMTP_PORT . ")\n";
    echo "Owner Copy    : " . OWNER_COPY_EMAIL . "\n";
    echo "Auto-Reply    : " . (LARA_EMAIL_AUTOREPLY ? 'ENABLED (24/7 background + cron)' : 'DISABLED') . "\n";
    echo "Gemini AI     : " . ((defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '') ? 'READY' : 'MISSING') . "\n";
    $imap = new LaraImapClient();
    $ok = $imap->connect(IMAP_HOST, IMAP_PORT, SMTP_USER, SMTP_PASS);
    echo "IMAPS Connect : " . ($ok ? 'OK' : 'FAILED') . "\n";
    if ($ok) {
        $uids = $imap->searchUids('ALL');
        echo "INBOX Count   : " . count($uids) . " message(s) (Highest UID: " . (empty($uids) ? 0 : max($uids)) . ")\n";
        $imap->close();
    }
    exit($ok ? 0 : 1);
}

if ($cmd === 'list') {
    $imap = new LaraImapClient();
    if (!$imap->connect(IMAP_HOST, IMAP_PORT, SMTP_USER, SMTP_PASS)) {
        exit("IMAP connect failed.\n");
    }
    $uids = array_slice($imap->searchUids('ALL'), -20);
    foreach ($uids as $uid) {
        $raw = $imap->fetchRawByUid($uid);
        $p = lara_parse_rfc822_message($raw);
        $skip = lara_email_precheck_skip($p);
        echo sprintf(
            "UID %-4d | %-18s | %-34s | %s\n",
            $uid,
            $skip ? ('SKIP:' . $skip) : 'CANDIDATE',
            mb_substr($p['from_email'], 0, 34, 'UTF-8'),
            mb_substr($p['subject'], 0, 60, 'UTF-8')
        );
    }
    $imap->close();
    exit(0);
}

if ($cmd === 'reply-uid') {
    $uid = (int) ($argv[2] ?? 0);
    if ($uid <= 0) {
        exit("Usage: php docs/lara_email_inbox.php reply-uid <uid> [--send]\n");
    }
    $doSend = in_array('--send', $argv, true);
    $res = lara_email_poll_and_reply($doSend, 1, $uid);
    echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    exit($res['ok'] ? 0 : 1);
}

if ($cmd === 'poll') {
    $doSend = in_array('--send', $argv, true);
    $res = lara_email_poll_and_reply($doSend, 5, null);
    echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    exit($res['ok'] ? 0 : 1);
}

if ($cmd === 'sync-crm') {
    $imap = new LaraImapClient();
    if (!$imap->connect(IMAP_HOST, IMAP_PORT, SMTP_USER, SMTP_PASS)) {
        exit("IMAP connect failed.\n");
    }
    $state = lara_email_with_state(static fn(array $s): array => $s);
    $uids = $imap->searchUids('ALL');
    $synced = 0;
    foreach ($uids as $uid) {
        if (!empty($state['crm_synced_uids'][(string) $uid])) {
            echo "UID {$uid}: already synced to CRM.\n";
            continue;
        }
        $raw = $imap->fetchRawByUid($uid);
        if ($raw === '') {
            continue;
        }
        $parsed = lara_parse_rfc822_message($raw);
        if (lara_email_precheck_skip($parsed) !== null) {
            continue;
        }
        $isReplied = isset($state['processed_uids'][(string) $uid]);
        $statusLabel = $isReplied ? 'Lara Auto-Quoted' : 'Email Inbox Lead';
        $presetEur = ($uid === 18) ? 741.0 : (($uid === 13) ? 1100.0 : 0.0);
        $fields = lara_extract_email_lead_fields($parsed, $presetEur, '');
        $ok = lara_email_sync_lead_to_crm($uid, $parsed, ['estimated_eur' => $fields['estimated_eur'], 'lang' => $fields['lang']], $statusLabel);
        echo "UID {$uid} ({$fields['name']} <{$parsed['from_email']}> | {$fields['people']} pax | {$fields['date']} | €{$fields['estimated_eur']}): CRM sync = " . ($ok ? 'OK' : 'FAILED') . "\n";
        if ($ok) {
            $synced++;
            lara_email_with_state(static function (array $s) use ($uid): array {
                $s['crm_synced_uids'][(string) $uid] = gmdate('c');
                return $s;
            });
        }
    }
    $imap->close();
    echo "=== Synced {$synced} email lead(s) to Google Sheets CRM ===\n";
    exit(0);
}

echo "Unknown command '{$cmd}'. Use: status | list | reply-uid <uid> [--send] | poll [--send] | sync-crm\n";
exit(1);
