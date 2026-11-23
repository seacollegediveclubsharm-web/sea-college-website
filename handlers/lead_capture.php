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
$matchedInterestId = '';
if ($interest === '' && $cart) {
    $interest = implode(', ', array_column($cart, 'name'));
} elseif ($interest !== '') {
    foreach ($idx as $catItem) {
        if ($catItem['name'] === $interest) {
            $matchedInterestId = (string) $catItem['id'];
            if ($total <= 0 && $catItem['price'] !== null) {
                $mult = (($catItem['unit'] ?? '') === 'boat') ? 1 : max(1, $people);
                $total = (float) $catItem['price'] * $mult;
            }
            break;
        }
    }
}

if ($errors) {
    respond(false, ['message' => t('form_error_validation'), 'errors' => $errors], $_SERVER['HTTP_REFERER'] ?? page_link('/contact'));
}

$_SESSION['last_submit'] = time();
$eventId = 'lead_' . bin2hex(random_bytes(8));
$attr = collect_attribution_from_post();
$sellerInfo = [
    'seller'          => (string) ($attr['seller'] ?? ''),
    'seller_username' => (string) ($attr['seller_username'] ?? ''),
    'sales_rep'       => (string) ($attr['sales_rep'] ?? ''),
    'seller_branch'   => (string) ($attr['seller_branch'] ?? ''),
];
$repRaw = (string) ($attr['rep'] ?? '');
$attrBase = $attr;
$fbcVal = (string) ($attrBase['fbc'] ?? '');
$fbpVal = (string) ($attrBase['fbp'] ?? '');
unset($attrBase['fbc'], $attrBase['fbp'], $attrBase['seller'], $attrBase['rep'], $attrBase['seller_username'], $attrBase['sales_rep'], $attrBase['seller_branch']);

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
] + $attrBase + [
    'ip_hash' => hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . date('Y-m')),
    'user_agent' => clean_line($_SERVER['HTTP_USER_AGENT'] ?? '', 250),
    'fbc' => $fbcVal,
    'fbp' => $fbpVal,
    'estimated_eur' => round($total, 2),
    'seller' => $sellerInfo['seller'],
    'rep' => $repRaw,
    'seller_username' => $sellerInfo['seller_username'],
    'sales_rep' => $sellerInfo['sales_rep'],
    'seller_branch' => $sellerInfo['seller_branch'],
];

/* 1) Persist CSV + Unified SQL Warehouse */
store_csv('leads.csv', $lead);
require_once dirname(__DIR__) . '/includes/erp_db.php';
sc_erp_ingest_website_lead($lead);

/* 2) Notify team */
$lines = [];
foreach ($lead as $k => $v) {
    if ($v === '' || $v === [] || in_array($k, ['ip_hash', 'user_agent', 'estimated_eur'], true)) {
        continue;
    }
    $lines[] = str_pad($k, 22) . ': ' . (is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v);
}
$sellerTag = $sellerInfo['sales_rep'] !== '' ? (' [Seller: ' . $sellerInfo['sales_rep'] . ']') : ($sellerInfo['seller'] !== '' ? (' [Seller: ' . $sellerInfo['seller'] . ']') : '');
send_mail(LEAD_NOTIFY_EMAIL, 'New ' . $source_form . ' lead — ' . $name . ' (' . ($interest ?: 'general') . ')' . $sellerTag, implode("\n", $lines), $email);

/* 3) CRM / automation webhook */
if (CRM_WEBHOOK_URL !== '') {
    $chSummary = sc_resolve_channel_summary($attr);
    post_json(CRM_WEBHOOK_URL, ['type' => 'lead', 'estimated_eur' => round($total, 2)] + $lead + $chSummary);
}

/* 4) Meta Conversions API (server event, same event_id as browser pixel; catalog-clean content_ids) */
$capiIds = array_values(array_filter(array_column($cart, 'id'), 'sc_is_catalog_product_id'));
if ($capiIds === [] && $matchedInterestId !== '' && sc_is_catalog_product_id($matchedInterestId)) {
    $capiIds = [$matchedInterestId];
}
$capiCustom = [
    'currency' => 'EUR',
    'value' => round($total, 2),
    'content_name' => $interest,
];
if ($capiIds !== []) {
    $capiCustom['content_type'] = 'product';
    $capiCustom['content_ids'] = $capiIds;
}
meta_capi_event('Lead', $eventId, [
    'email' => $email,
    'phone' => $phone,
    'first_name' => explode(' ', $name)[0] ?? '',
    'fbclid' => $attr['fbclid'] ?? '',
    'fbc' => $fbcVal,
    'fbp' => $fbpVal,
], $capiCustom);

/* 5) Hand conversion data to thank-you page (fires GA4 / Ads / Meta / TikTok / UET in browser) */
$normDigits = preg_replace('/^00/', '', phone_digits($phone)) ?? '';
if (preg_match('/^0(1[0125]\d{8})$/', $normDigits, $egMatch)) {
    $normDigits = '20' . $egMatch[1]; // Local Egyptian mobile -> E.164 +201...
} elseif (str_starts_with($normDigits, '0') && strlen($normDigits) >= 9) {
    $normDigits = ltrim($normDigits, '0');
}
$_SESSION['conversion'] = [
    'event_id' => $eventId,
    'value' => round($total, 2),
    'currency' => 'EUR',
    'form' => $source_form,
    'items' => array_map(static fn($c) => ['item_id' => $c['id'], 'item_name' => $c['name'], 'quantity' => $c['qty'], 'price' => $c['unit_price']], $cart),
    'em_sha256' => sha256_norm($email),
    'ph_sha256' => strlen($normDigits) >= 8 ? hash('sha256', '+' . $normDigits) : '',
];
unset($_SESSION['form_old'], $_SESSION['form_error']);

$tyUrl = page_link('/thank-you') . ($sellerInfo['seller'] !== '' ? '?seller=' . rawurlencode($sellerInfo['seller']) : '');
respond(true, ['redirect' => $tyUrl, 'event_id' => $eventId]);
