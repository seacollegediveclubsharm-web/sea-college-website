<?php
/**
 * POST /handlers/newsletter.php — email opt-in (CSRF, honeypot, rate limit).
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/lead_lib.php';

require_post_and_csrf();

$email = clean_line($_POST['email'] ?? '', 160);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, ['message' => t('newsletter_err'), 'errors' => ['email' => t('newsletter_err')]], $_SERVER['HTTP_REFERER'] ?? '/');
}

$_SESSION['last_submit'] = time();
$eventId = 'nl_' . bin2hex(random_bytes(8));
$attr = collect_attribution_from_post();
$attrBase = $attr;
$fbcVal = (string) ($attrBase['fbc'] ?? '');
$fbpVal = (string) ($attrBase['fbp'] ?? '');
unset($attrBase['fbc'], $attrBase['fbp']);

$row = [
    'subscribed_at' => gmdate('c'),
    'event_id' => $eventId,
    'email' => $email,
    'lang' => LANG,
    'consent_state' => consent_state(),
] + $attrBase + [
    'fbc' => $fbcVal,
    'fbp' => $fbpVal,
];

store_csv('newsletter.csv', $row);

if (CRM_WEBHOOK_URL !== '') {
    post_json(CRM_WEBHOOK_URL, ['type' => 'newsletter', 'estimated_eur' => 0.0] + $row);
}
meta_capi_event('CompleteRegistration', $eventId, [
    'email' => $email,
    'fbclid' => $attr['fbclid'] ?? '',
    'fbc' => $fbcVal,
    'fbp' => $fbpVal,
], ['content_name' => 'newsletter']);

respond(true, ['message' => t('newsletter_ok'), 'event_id' => $eventId], $_SERVER['HTTP_REFERER'] ?? '/');
