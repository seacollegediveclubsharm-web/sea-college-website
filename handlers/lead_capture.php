<?php
/**
 * POST /handlers/lead_capture.php
 * Booking / contact lead processor: CSRF + honeypot + rate limit, server-side validation,
 * cart re-pricing from the catalog, CSV storage, email, CRM webhook, Meta CAPI,
 * then hands the conversion payload to thank-you.php (browser events, de-duplicated).
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/lead_lib.php';

require_post_and_csrf();

$errors = [];

$name    = clean_line($_POST['name'] ?? '', 100);
$phone   = clean_line($_POST['phone'] ?? '', 30);
$email   = clean_line($_POST['email'] ?? '', 160);
$interest= clean_line($_POST['interest'] ?? '', 200);
$people  = (int) ($_POST['people'] ?? 1);
$date    = clean_line($_POST['date'] ?? '', 10);
$hotel   = clean_line($_POST['hotel'] ?? '', 120);
$level   = clean_line($_POST['level'] ?? '', 20);
$message = clean($_POST['message'] ?? '', 2000);
$consent = !empty($_POST['consent']);
$marketing = !empty($_POST['marketing']);
$source_form = in_array($_POST['form_id'] ?? '', ['booking', 'contact'], true) ? $_POST['form_id'] : 'contact';

if (mb_strlen($name) < 2) {
    $errors['name'] = t('form_required');
}
if (!preg_match('/^\+?[0-9 ()\-]{6,24}$/', $phone)) {
    $errors['phone'] = t('form_required');
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = t('form_error_validation');
}
if ($people < 1 || $people > 60) {
    $errors['people'] = t('form_error_validation');
}
if ($date !== '') {
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date || $d < new DateTimeImmutable('today')) {
        $errors['date'] = t('form_error_validation');
    }
}
if (!in_array($level, ['', 'none', 'sd', 'owd', 'aowd'], true)) {
    $level = '';
}
if (!$consent) {
    $errors['consent'] = t('form_required');
}

/* ---- Cart: re-price every line server-side (never trust client totals) ---- */
$cart = [];
$total = 0.0;
$rawCart = json_decode((string) ($_POST['cart'] ?? '[]'), true);
$idx = catalog_index();
if (is_array($rawCart)) {
    foreach (array_slice($rawCart, 0, 40) as $line) {
        $id = is_array($line) ? (string) ($line['id'] ?? '') : '';
        $qty = is_array($line) ? max(1, min(60, (int) ($line['qty'] ?? 1))) : 1;
        if (!isset($idx[$id])) {
            continue;
        }
        $price = $idx[$id]['price'];
        $cart[] = ['id' => $id, 'name' => $idx[$id]['name'], 'qty' => $qty, 'unit_price' => $price];
        if ($price !== null) {
            $total += $price * $qty;
        }
    }
}
if ($interest === '' && $cart) {
    $interest = implode(', ', array_column($cart, 'name'));
} elseif ($total <= 0 && $interest !== '') {
    foreach ($idx as $catItem) {
        if ($catItem['name'] === $interest && $catItem['price'] !== null) {
            $mult = (($catItem['unit'] ?? '') === 'boat') ? 1 : max(1, $people);
            $total = (float) $catItem['price'] * $mult;
            break;
        }
    }
}

if ($errors) {
    respond(false, ['message' => t('form_error_validation'), 'errors' => $errors], $_SERVER['HTTP_REFERER'] ?? '/contact.php');
}

$_SESSION['last_submit'] = time();
$eventId = 'lead_' . bin2hex(random_bytes(8));
$attr = collect_attribution_from_post();

$lead = [
    'received_at' => gmdate('c'),
    'event_id' => $eventId,
    'form' => $source_form,
    'lang' => LANG,
    'name' => $name,
    'phone' => $phone,
    'email' => $email,
    'interest' => $interest,
    'people' => $people,
    'date' => $date,
    'hotel' => $hotel,
    'level' => $level,
    'message' => $message,
    'cart' => $cart,
    'estimated_total_eur' => round($total, 2),
    'marketing_opt_in' => $marketing ? 'yes' : 'no',
    'consent_state' => consent_state(),
] + $attr + [
    'ip_hash' => hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . date('Y-m')),
    'user_agent' => clean_line($_SERVER['HTTP_USER_AGENT'] ?? '', 250),
];

/* 1) Persist */
store_csv('leads.csv', $lead);

/* 2) Notify team */
$lines = [];
foreach ($lead as $k => $v) {
    if ($v === '' || $v === [] || in_array($k, ['ip_hash', 'user_agent'], true)) {
        continue;
    }
    $lines[] = str_pad($k, 22) . ': ' . (is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v);
}
send_mail(LEAD_NOTIFY_EMAIL, 'New ' . $source_form . ' lead — ' . $name . ' (' . ($interest ?: 'general') . ')', implode("\n", $lines), $email);

/* 3) CRM / automation webhook */
if (CRM_WEBHOOK_URL !== '') {
    post_json(CRM_WEBHOOK_URL, ['type' => 'lead'] + $lead);
}

/* 4) Meta Conversions API (server event, same event_id as browser pixel) */
meta_capi_event('Lead', $eventId, [
    'email' => $email, 'phone' => $phone, 'first_name' => explode(' ', $name)[0] ?? '', 'fbclid' => $attr['fbclid'] ?? '',
], ['currency' => 'EUR', 'value' => round($total, 2), 'content_name' => $interest, 'content_ids' => array_column($cart, 'id')]);

/* 5) Hand conversion data to thank-you page (fires GA4 / Ads / Meta / TikTok in browser) */
$_SESSION['conversion'] = [
    'event_id' => $eventId,
    'value' => round($total, 2),
    'currency' => 'EUR',
    'form' => $source_form,
    'items' => array_map(static fn($c) => ['item_id' => $c['id'], 'item_name' => $c['name'], 'quantity' => $c['qty'], 'price' => $c['unit_price']], $cart),
    'em_sha256' => sha256_norm($email),
    'ph_sha256' => $phone !== '' ? hash('sha256', '+' . phone_digits($phone)) : '',
];
unset($_SESSION['form_old'], $_SESSION['form_error']);

$lq = LANG !== DEFAULT_LANG ? '?lang=' . LANG : '';
respond(true, ['redirect' => '/thank-you.php' . $lq, 'event_id' => $eventId]);
