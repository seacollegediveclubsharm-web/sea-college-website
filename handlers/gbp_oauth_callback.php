<?php
/**
 * Lara & Hossam — 1-Click Google Business Profile OAuth Connector & Auto-Reply Dashboard
 * Canonical URL: https://seacollegediveclub.com/webhooks/google-reviews
 *
 * - Public GET (no key): Read-only JSON health status (zero secrets exposed).
 * - Owner GET/POST (`?key=<HMAC>` or Google OAuth `?state=<HMAC>`):
 *     1. Paste Client ID & Client Secret once (saved in protected `storage/lara_gbp_oauth.json`).
 *     2. Click "Connect Google Business Profile" -> Sign in with `seacollegediveclubsharm@gmail.com` -> Click Allow.
 *     3. Lara automatically stores the Refresh Token and starts replying to Google reviews 24/7.
 */
declare(strict_types=1);

define('SC_NO_SESSION', true);
require_once dirname(__DIR__) . '/includes/lara_reviews_concierge.php';

@header_remove('X-Powered-By');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Robots-Tag: noindex, nofollow');

$adminKey    = lara_gbp_admin_key();
$providedKey = trim((string) ($_GET['key'] ?? $_POST['key'] ?? $_GET['state'] ?? ''));
$redirectUri = rtrim(SITE_URL, '/') . '/webhooks/google-reviews';
$logFile     = (defined('LEADS_STORAGE_DIR') ? LEADS_STORAGE_DIR : dirname(__DIR__) . '/storage') . '/lara_gbp_replies.jsonl';

if ($providedKey === '') {
    header('Content-Type: application/json; charset=utf-8');
    $creds = lara_gbp_credentials();
    $liveCount = is_file($logFile) ? count((array) @file($logFile, FILE_SKIP_EMPTY_LINES)) : 0;
    echo json_encode([
        'ok'                 => true,
        'agent'              => 'Lara & Hossam — Google Business Profile Review Auto-Responder',
        'location_id'        => GBP_LOCATION_ID,
        'place_id'           => GOOGLE_PLACE_ID,
        'client_configured'  => $creds['client_id'] !== '' && $creds['client_secret'] !== '',
        'oauth_connected'    => $creds['refresh_token'] !== '',
        'replied_live_total' => $liveCount,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($adminKey === '' || !hash_equals($adminKey, $providedKey)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Invalid or missing security key.']);
    exit;
}

$flashMsg = '';
$flashErr = '';
$sweepResult = null;

// 1. Handle saving Client ID + Client Secret & redirecting to Google OAuth
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_and_connect') {
    $cid = trim((string) ($_POST['client_id'] ?? ''));
    $csc = trim((string) ($_POST['client_secret'] ?? ''));
    if ($cid === '' || $csc === '') {
        $flashErr = 'Please paste both your Google Client ID and Client Secret.';
    } else {
        lara_gbp_save_stored_oauth([
            'client_id'     => $cid,
            'client_secret' => $csc,
        ]);
        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id'     => $cid,
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => 'https://www.googleapis.com/auth/business.manage',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => $adminKey,
        ]);
        header('Location: ' . $authUrl, true, 302);
        exit;
    }
}

// 2. Handle direct re-connect if Client ID is already stored
if (($_GET['action'] ?? '') === 'connect') {
    $creds = lara_gbp_credentials();
    if ($creds['client_id'] !== '' && $creds['client_secret'] !== '') {
        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id'     => $creds['client_id'],
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => 'https://www.googleapis.com/auth/business.manage',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => $adminKey,
        ]);
        header('Location: ' . $authUrl, true, 302);
        exit;
    }
}

// 3. Handle Google OAuth callback (`?code=...&state=<adminKey>`)
if (!empty($_GET['code'])) {
    $creds = lara_gbp_credentials();
    $code  = trim((string) $_GET['code']);
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'code'          => $code,
            'client_id'     => $creds['client_id'],
            'client_secret' => $creds['client_secret'],
            'redirect_uri'  => $redirectUri,
            'grant_type'    => 'authorization_code',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $raw = (string) curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $dec = json_decode($raw, true);
    if ($httpCode === 200 && is_array($dec) && !empty($dec['refresh_token'])) {
        lara_gbp_save_stored_oauth([
            'refresh_token' => (string) $dec['refresh_token'],
            'connected_at'  => gmdate('Y-m-d H:i:s') . ' UTC',
        ]);
        $flashMsg = 'Google Business Profile connected! Lara & Hossam are now authorized to post owner replies 24/7.';
    } else {
        $errDesc = is_array($dec) ? ((string) ($dec['error_description'] ?? $dec['error'] ?? $raw)) : $raw;
        $flashErr = 'Google token exchange failed: ' . $errDesc;
    }
} elseif (!empty($_GET['error'])) {
    $flashErr = 'Google authorization cancelled or denied: ' . (string) $_GET['error'];
}

// 4. Handle manual "Sweep & Reply Now" button
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sweep_now') {
    @ignore_user_abort(true);
    @set_time_limit(120);
    $doSend = ($_POST['mode'] ?? 'send') === 'send';
    $sweepResult = lara_gbp_api_sweep_and_reply($doSend, 5, 25);
    if ($sweepResult['ok']) {
        $flashMsg = $doSend
            ? "Sweep complete! Checked {$sweepResult['checked']} reviews and posted {$sweepResult['replied']} live owner replies to Google Maps."
            : "Preview complete! Checked {$sweepResult['checked']} reviews ({$sweepResult['unanswered']} unanswered).";
    } else {
        $flashErr = (string) ($sweepResult['error'] ?? 'Sweep error');
    }
}

$creds  = lara_gbp_credentials();
$stored = lara_gbp_load_stored_oauth();
$isConnected = ($creds['client_id'] !== '' && $creds['client_secret'] !== '' && $creds['refresh_token'] !== '');
$h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lara &amp; Hossam — Google Reviews Auto-Reply Connector</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f4f7fb; color: #0f172a; margin: 0; padding: 28px 16px; }
    .card { max-width: 720px; margin: 0 auto 20px; background: #fff; border-radius: 16px; padding: 28px; box-shadow: 0 8px 30px rgba(15,23,42,.07); border: 1px solid #e2e8f0; }
    h1 { font-size: 22px; margin: 0 0 6px; color: #0c4a6e; }
    .sub { color: #475569; font-size: 14px; margin-bottom: 20px; }
    .badge { display: inline-block; padding: 5px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; }
    .badge-ok { background: #dcfce7; color: #166534; }
    .badge-wait { background: #fef3c7; color: #92400e; }
    .alert-ok { background: #ecfdf5; border: 1px solid #6ee7b7; color: #065f46; padding: 14px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 14px; }
    .alert-err { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; padding: 14px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 14px; word-break: break-word; }
    label { display: block; font-weight: 600; font-size: 13px; margin: 14px 0 6px; color: #1e293b; }
    input[type="text"], input[type="password"] { width: 100%; box-sizing: border-box; padding: 11px 13px; border: 1px solid #cbd5e1; border-radius: 9px; font-size: 14px; font-family: monospace; }
    .codebox { background: #f8fafc; border: 1px dashed #94a3b8; padding: 10px 12px; border-radius: 8px; font-family: monospace; font-size: 13px; word-break: break-all; margin: 6px 0 14px; }
    .btn { display: inline-block; background: #0284c7; color: #fff; font-weight: 700; font-size: 15px; padding: 12px 22px; border: none; border-radius: 10px; cursor: pointer; text-decoration: none; margin-top: 14px; }
    .btn:hover { background: #0369a1; }
    .btn-green { background: #059669; }
    .btn-green:hover { background: #047857; }
    .btn-outline { background: #fff; color: #0f172a; border: 1px solid #cbd5e1; font-weight: 600; font-size: 13px; padding: 9px 16px; margin-left: 8px; }
    .rev-item { border-top: 1px solid #e2e8f0; padding: 12px 0; font-size: 13px; }
  </style>
</head>
<body>
  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
      <h1>Sea College Dive Club — Google Reviews Auto-Reply</h1>
      <?php if ($isConnected): ?>
        <span class="badge badge-ok">CONNECTED · 24/7 AUTO-REPLY ACTIVE</span>
      <?php else: ?>
        <span class="badge badge-wait">AWAITING 1-TIME GOOGLE SIGN-IN</span>
      <?php endif; ?>
    </div>
    <p class="sub">Managed by <strong>Lara</strong> (Head of Customer Care) &amp; <strong>Hossam</strong> (Local SEO Director) · Location ID <code><?= $h(GBP_LOCATION_ID) ?></code> (4.9★)</p>

    <?php if ($flashMsg !== ''): ?>
      <div class="alert-ok"><?= $h($flashMsg) ?></div>
    <?php endif; ?>
    <?php if ($flashErr !== ''): ?>
      <div class="alert-err"><?= $h($flashErr) ?></div>
    <?php endif; ?>

    <?php if ($isConnected): ?>
      <p style="font-size:14px;line-height:1.6;margin:0 0 12px;">
        Google Business Profile is connected<?= $stored['connected_at'] !== '' ? ' since <strong>' . $h($stored['connected_at']) . '</strong>' : '' ?>.
        Lara automatically checks for new unanswered Google reviews every 30 minutes and posts warm, native-language replies. You can also run an immediate batch right now:
      </p>
      <form method="post" action="?key=<?= $h($adminKey) ?>">
        <input type="hidden" name="key" value="<?= $h($adminKey) ?>">
        <input type="hidden" name="action" value="sweep_now">
        <button type="submit" name="mode" value="send" class="btn btn-green">Reply to Unanswered Google Reviews Now (Batch of 25)</button>
        <button type="submit" name="mode" value="preview" class="btn btn-outline">Preview Only (Don't Post Yet)</button>
      </form>
    <?php endif; ?>

    <?php if (is_array($sweepResult) && !empty($sweepResult['items'])): ?>
      <div style="margin-top:20px;">
        <strong>Processed Reviews (<?= count($sweepResult['items']) ?>):</strong>
        <?php foreach ($sweepResult['items'] as $it): ?>
          <div class="rev-item">
            <div><strong><?= $h((string) $it['author']) ?></strong> (<?= (int) $it['rating'] ?>★ · <?= $h(strtoupper((string) $it['lang'])) ?>) — <code><?= $h((string) $it['status']) ?></code></div>
            <div style="color:#475569;margin:4px 0;">Guest: <?= $h((string) $it['comment']) ?></div>
            <div style="color:#0f172a;"><strong>Lara's Reply:</strong> <?= $h((string) $it['reply']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2 style="font-size:17px;margin:0 0 8px;color:#0f172a;"><?= $isConnected ? 'Update or Re-Connect Google Credentials' : '1-Time Google Business Profile Connection' ?></h2>
    <p style="font-size:13px;color:#475569;line-height:1.6;margin:0 0 10px;">
      When creating your <strong>Web application</strong> OAuth Client ID in Google Cloud Console, paste this exact URL under <strong>Authorized redirect URIs</strong>:
    </p>
    <div class="codebox"><?= $h($redirectUri) ?></div>

    <form method="post" action="?key=<?= $h($adminKey) ?>">
      <input type="hidden" name="key" value="<?= $h($adminKey) ?>">
      <input type="hidden" name="action" value="save_and_connect">

      <label for="cid">Google OAuth Client ID</label>
      <input id="cid" type="text" name="client_id" placeholder="1234567890-abcdefg.apps.googleusercontent.com" value="<?= $h($creds['client_id']) ?>" required>

      <label for="csc">Google OAuth Client Secret</label>
      <input id="csc" type="password" name="client_secret" placeholder="GOCSPX-..." value="<?= $h($creds['client_secret']) ?>" required>

      <button type="submit" class="btn">Save &amp; Sign in with Google (seacollegediveclubsharm@gmail.com)</button>
    </form>
  </div>
</body>
</html>
