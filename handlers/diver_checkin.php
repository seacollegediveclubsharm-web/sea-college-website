<?php
/**
 * POST /handlers/diver_checkin.php
 * Optional post-booking pre-arrival diver intake (cert agency/number, gear sizes, medical/waiver ack).
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/lead_lib.php';

require_post_and_csrf();

$leadEventId = clean_line($_POST['lead_event_id'] ?? '', 40);
$name        = clean_line($_POST['name'] ?? '', 100);
$agency      = clean_line($_POST['cert_agency'] ?? '', 30);
$certNo      = clean_line($_POST['cert_number'] ?? '', 50);
$wetsuit     = clean_line($_POST['wetsuit_size'] ?? '', 16);
$finSize     = clean_line($_POST['fin_size'] ?? '', 16);
$bcdSize     = clean_line($_POST['bcd_size'] ?? '', 16);
$waiverAck   = !empty($_POST['medical_waiver']);

if (!$waiverAck) {
    respond(false, ['message' => t('form_error_validation'), 'errors' => ['medical_waiver' => t('form_required')]], '/thank-you.php');
}

$_SESSION['last_submit'] = time();

$row = [
    'submitted_at'   => gmdate('c'),
    'lead_event_id'  => $leadEventId,
    'name'           => $name,
    'cert_agency'    => $agency,
    'cert_number'    => $certNo,
    'wetsuit_size'   => $wetsuit,
    'fin_size'       => $finSize,
    'bcd_size'       => $bcdSize,
    'medical_waiver' => 'yes',
];

store_csv('checkins.csv', $row);

$lines = [];
foreach ($row as $k => $v) {
    if ($v !== '') {
        $lines[] = str_pad($k, 18) . ': ' . $v;
    }
}
send_mail(LEAD_NOTIFY_EMAIL, 'Pre-arrival diver check-in — ' . ($name !== '' ? $name : $leadEventId), implode("\n", $lines));

if (CRM_WEBHOOK_URL !== '') {
    post_json(CRM_WEBHOOK_URL, ['type' => 'checkin'] + $row);
}

respond(true, ['message' => t('checkin_ok')], '/thank-you.php');
