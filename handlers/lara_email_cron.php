<?php
/**
 * Lara — Direct Email Auto-Responder Endpoint & Health Check (`/handlers/lara_email_cron.php`).
 *
 * - GET without token: read-only JSON health status (zero secrets exposed).
 * - GET/POST with `?token=<META_WEBHOOK_VERIFY_TOKEN>`: polls `info@seacollegediveclub.com` via IMAPS
 *   and replies to any new genuine guest inquiry emails since `last_uid`.
 */
declare(strict_types=1);

define('SC_NO_SESSION', true);
require_once dirname(__DIR__) . '/includes/lara_email_concierge.php';

@header_remove('X-Powered-By');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$stateFile = dirname(__DIR__) . '/storage/lara_email_state.json';
$logFile   = dirname(__DIR__) . '/storage/lara_email_log.jsonl';

if ($token === '') {
    $state = is_file($stateFile) ? (json_decode((string) @file_get_contents($stateFile), true) ?: []) : [];
    $logCount = is_file($logFile) ? count((array) @file($logFile, FILE_SKIP_EMPTY_LINES)) : 0;
    echo json_encode([
        'ok'              => true,
        'agent'           => 'Lara — Direct Email Concierge (info@seacollegediveclub.com)',
        'email_autoreply' => LARA_EMAIL_AUTOREPLY,
        'smtp_configured' => SMTP_USER !== '' && SMTP_PASS !== '',
        'ai_configured'   => defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '',
        'last_uid'        => (int) ($state['last_uid'] ?? 0),
        'replied_total'   => $logCount,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!hash_equals(META_WEBHOOK_VERIFY_TOKEN, $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Invalid token']);
    exit;
}

if (!LARA_EMAIL_AUTOREPLY) {
    echo json_encode(['ok' => true, 'email_autoreply' => false]);
    exit;
}

@ignore_user_abort(true);
@set_time_limit(60);
$res = lara_email_poll_and_reply(true, 4, null);
echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
