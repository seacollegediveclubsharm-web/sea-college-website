<?php
/**
 * Shared lead-processing library for /handlers/*.
 * Validation, storage (CSV outside public reach), email (SMTP or mail()),
 * CRM webhook and Meta Conversions API (server-side, consent-aware).
 */
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function is_ajax(): bool
{
    return (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
        || str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
}

/** Sanitize redirect targets to same-origin relative paths only (prevents Open Redirect attacks). */
function safe_redirect_target(string $target, string $default = '/'): string
{
    $target = trim(str_replace(["\r", "\n", "\0"], '', $target));
    if ($target === '') {
        return $default;
    }
    if (str_starts_with($target, '/') && !str_starts_with($target, '//') && !str_starts_with($target, '/\\')) {
        return $target;
    }
    $host = strtolower((string) parse_url($target, PHP_URL_HOST));
    $allowedHosts = array_filter([
        strtolower((string) parse_url(SITE_URL, PHP_URL_HOST)),
        'seacollegediveclub.com',
        'www.seacollegediveclub.com',
        strtolower((string) preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? ''))),
    ]);
    if ($host !== '' && in_array($host, $allowedHosts, true)) {
        $path = (string) (parse_url($target, PHP_URL_PATH) ?: '/');
        $query = (string) (parse_url($target, PHP_URL_QUERY) ?: '');
        return $path . ($query !== '' ? '?' . $query : '');
    }
    return $default;
}

/** Finish request: JSON for fetch(), redirect for classic form posts. */
function respond(bool $ok, array $data = [], string $fallback = '/'): never
{
    $safeFallback = safe_redirect_target($fallback, '/');
    if (isset($data['redirect'])) {
        $data['redirect'] = safe_redirect_target((string) $data['redirect'], $safeFallback);
    }
    if (is_ajax()) {
        http_response_code($ok ? 200 : (int) ($data['status'] ?? 422));
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['ok' => $ok] + $data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!$ok) {
        $_SESSION['form_error'] = $data['message'] ?? t('form_error_generic');
        $_SESSION['form_old'] = array_map(static fn($v) => is_string($v) ? substr($v, 0, 500) : '', $_POST);
        unset($_SESSION['form_old']['csrf_token']);
    }
    header('Location: ' . ($data['redirect'] ?? $safeFallback), true, 303);
    exit;
}

/** Per-IP file-backed rate limiter so cookie-less bots cannot flood POST handlers. */
function sc_ip_rate_limit_exceeded(int $minIntervalSec = RATE_LIMIT_SECONDS, int $maxPerHour = 25): bool
{
    $ip = (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    if (in_array($ip, ['127.0.0.1', '::1', '0.0.0.0'], true)) {
        return false;
    }
    $ipHash = substr(hash_hmac('sha256', $ip, sc_app_secret()), 0, 24);
    $file = LEADS_STORAGE_DIR . '/.ratelimit_ip.json';
    $now = time();
    $fh = @fopen($file, 'c+');
    if ($fh === false) {
        return false;
    }
    flock($fh, LOCK_EX);
    $raw = stream_get_contents($fh);
    $map = is_string($raw) && $raw !== '' ? (json_decode($raw, true) ?: []) : [];
    foreach ($map as $k => $entry) {
        if (($now - (int) ($entry['last'] ?? 0)) > 3600) {
            unset($map[$k]);
        }
    }
    $rec = $map[$ipHash] ?? ['last' => 0, 'count' => 0, 'window' => $now];
    if (($now - (int) $rec['window']) > 3600) {
        $rec = ['last' => 0, 'count' => 0, 'window' => $now];
    }
    $blocked = (($now - (int) $rec['last']) < max(3, min($minIntervalSec, 8))) || ((int) $rec['count'] >= $maxPerHour);
    if (!$blocked) {
        $rec['last'] = $now;
        $rec['count'] = (int) $rec['count'] + 1;
        $map[$ipHash] = $rec;
    }
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, (string) json_encode($map));
    flock($fh, LOCK_UN);
    fclose($fh);
    return $blocked;
}

function require_post_and_csrf(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Method Not Allowed');
    }
    $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    if ($origin !== '') {
        $oHost = strtolower((string) parse_url($origin, PHP_URL_HOST));
        $reqHost = strtolower((string) preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
        $allowed = array_filter([$reqHost, 'seacollegediveclub.com', 'www.seacollegediveclub.com', strtolower((string) parse_url(SITE_URL, PHP_URL_HOST))]);
        if ($oHost !== '' && !in_array($oHost, $allowed, true)) {
            respond(false, ['message' => t('form_error_csrf'), 'status' => 403], '/');
        }
    }
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        respond(false, ['message' => t('form_error_csrf'), 'status' => 419], $_SERVER['HTTP_REFERER'] ?? '/');
    }
    // Honeypot: bots fill hidden "website" field → pretend success, store nothing.
    if (!empty($_POST['website'])) {
        respond(true, ['redirect' => '/thank-you.php']);
    }
    $last = (int) ($_SESSION['last_submit'] ?? 0);
    if (($last && (time() - $last) < RATE_LIMIT_SECONDS) || sc_ip_rate_limit_exceeded()) {
        respond(false, ['message' => t('form_error_rate'), 'status' => 429], $_SERVER['HTTP_REFERER'] ?? '/');
    }
}

/** Trim, strip control chars, cap length. */
function clean(mixed $v, int $max = 200): string
{
    $v = is_string($v) ? $v : '';
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v) ?? '';
    return mb_substr(trim($v), 0, $max);
}

function clean_line(mixed $v, int $max = 200): string
{
    return str_replace(["\r", "\n"], ' ', clean($v, $max));
}

function collect_attribution_from_post(): array
{
    $out = [];
    foreach ([...UTM_KEYS, 'landing_page', 'referrer'] as $k) {
        $out[$k] = clean_line($_POST[$k] ?? '', 300);
    }
    // Server-side session attribution wins if the hidden fields were stripped.
    foreach (attribution() as $k => $v) {
        if (($out[$k] ?? '') === '' && is_string($v)) {
            $out[$k] = $v;
        }
    }
    return $out;
}

/** Append a row to storage/<file>.csv (CSV-injection safe). */
function store_csv(string $file, array $row): bool
{
    $dir = LEADS_STORAGE_DIR;
    if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
        return false;
    }
    $path = $dir . '/' . basename($file);
    $new = !is_file($path);
    $fh = fopen($path, 'ab');
    if ($fh === false) {
        return false;
    }
    flock($fh, LOCK_EX);
    if ($new) {
        fputcsv($fh, array_keys($row));
    }
    $safe = array_map(static function ($v): string {
        $v = is_scalar($v) ? (string) $v : (string) json_encode($v, JSON_UNESCAPED_UNICODE);
        return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
    }, $row);
    fputcsv($fh, $safe);
    flock($fh, LOCK_UN);
    fclose($fh);
    return true;
}

/** Send an email via SMTP (if credentials set) or PHP mail(). */
function send_mail(string $to, string $subject, string $body, string $replyTo = ''): bool
{
    $subject = '=?UTF-8?B?' . base64_encode(clean_line($subject, 200)) . '?=';
    $replyTo = filter_var($replyTo, FILTER_VALIDATE_EMAIL) ? $replyTo : '';
    $headers = [
        'From: ' . SITE_NAME . ' <' . MAIL_FROM . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];
    if ($replyTo !== '') {
        $headers[] = 'Reply-To: ' . $replyTo;
    }
    if (SMTP_USER !== '' && SMTP_PASS !== '') {
        return smtp_send($to, $subject, $body, $headers);
    }
    return @mail($to, $subject, $body, implode("\r\n", $headers));
}

/** Minimal SMTPS (implicit TLS, AUTH LOGIN) client — no external dependencies. */
function smtp_send(string $to, string $subject, string $body, array $headers): bool
{
    $fp = @stream_socket_client('ssl://' . SMTP_HOST . ':' . SMTP_PORT, $errno, $errstr, 10);
    if (!$fp) {
        error_log("SMTP connect failed: $errstr");
        return false;
    }
    stream_set_timeout($fp, 10);
    $read = static function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };
    $cmd = static function (string $c, string $expect) use ($fp, $read): bool {
        fwrite($fp, $c . "\r\n");
        return str_starts_with($read(), $expect);
    };
    $read();
    $host = parse_url(SITE_URL, PHP_URL_HOST) ?: 'localhost';
    $ok = $cmd('EHLO ' . $host, '250')
        && $cmd('AUTH LOGIN', '334')
        && $cmd(base64_encode(SMTP_USER), '334')
        && $cmd(base64_encode(SMTP_PASS), '235')
        && $cmd('MAIL FROM:<' . MAIL_FROM . '>', '250')
        && $cmd('RCPT TO:<' . $to . '>', '250')
        && $cmd('DATA', '354');
    if ($ok) {
        $msg = implode("\r\n", [...$headers, 'To: <' . $to . '>', 'Subject: ' . $subject, 'Date: ' . date('r')])
            . "\r\n\r\n" . preg_replace('/^\./m', '..', str_replace("\n", "\r\n", $body));
        $ok = $cmd($msg . "\r\n.", '250');
    }
    $cmd('QUIT', '221');
    fclose($fp);
    return $ok;
}

/** POST JSON to an external endpoint (CRM webhook, Meta CAPI). */
function post_json(string $endpoint, array $payload, int $timeout = 5): bool
{
    if ($endpoint === '' || !str_starts_with($endpoint, 'https://')) {
        return false;
    }
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (function_exists('curl_init')) {
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => $timeout,
        ]);
        $res = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($res === false || $code >= 400) {
            error_log("post_json $endpoint failed ($code)");
            return false;
        }
        return true;
    }
    $ctx = stream_context_create(['http' => [
        'method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => $json, 'timeout' => $timeout,
    ]]);
    return @file_get_contents($endpoint, false, $ctx) !== false;
}

function sha256_norm(string $v): string
{
    $v = strtolower(trim($v));
    return $v === '' ? '' : hash('sha256', $v);
}

function phone_digits(string $p): string
{
    return preg_replace('/\D/', '', $p) ?? '';
}

/**
 * Meta Conversions API — sends the same event_id as the browser pixel for de-duplication.
 * Fires only when a token + pixel are configured AND the visitor accepted marketing cookies.
 */
function meta_capi_event(string $eventName, string $eventId, array $user, array $custom = []): bool
{
    if (META_PIXEL_ID === '' || META_CAPI_TOKEN === '' || consent_state() !== 'all') {
        return false;
    }
    $ud = array_filter([
        'em' => isset($user['email']) && $user['email'] !== '' ? [sha256_norm($user['email'])] : null,
        'ph' => isset($user['phone']) && $user['phone'] !== '' ? [hash('sha256', phone_digits($user['phone']))] : null,
        'fn' => isset($user['first_name']) && $user['first_name'] !== '' ? [sha256_norm($user['first_name'])] : null,
        'client_ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'client_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'fbp' => $_COOKIE['_fbp'] ?? null,
        'fbc' => $_COOKIE['_fbc'] ?? (!empty($user['fbclid']) ? 'fb.1.' . (time() * 1000) . '.' . $user['fbclid'] : null),
    ]);
    $payload = ['data' => [[
        'event_name' => $eventName,
        'event_time' => time(),
        'event_id' => $eventId,
        'action_source' => 'website',
        'event_source_url' => $_SERVER['HTTP_REFERER'] ?? SITE_URL,
        'user_data' => $ud,
        'custom_data' => $custom,
    ]]];
    if (META_CAPI_TEST_CODE !== '') {
        $payload['test_event_code'] = META_CAPI_TEST_CODE;
    }
    $endpoint = 'https://graph.facebook.com/' . META_GRAPH_VERSION . '/' . rawurlencode(META_PIXEL_ID)
        . '/events?access_token=' . rawurlencode(META_CAPI_TOKEN);
    return post_json($endpoint, $payload);
}

/** Map catalog id → item (flattened) for server-side cart validation. */
function catalog_index(): array
{
    static $idx = null;
    if ($idx === null) {
        $idx = [];
        foreach (require ROOT_DIR . '/data/catalog.php' as $catKey => $cat) {
            foreach ($cat['groups'] as $g) {
                foreach ($g['items'] as $it) {
                    $idx[$it['id']] = $it + ['category' => $catKey];
                }
            }
        }
    }
    return $idx;
}
