<?php
/**
 * POST /handlers/diver_checkin.php
 * Pre-arrival diver check-in processor (standalone /check-in & post-booking /thank-you.php).
 * Stores full intake fields to storage/diver_checkins.csv, emails team, and syncs to CRM Checkins tab.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/lead_lib.php';

require_post_and_csrf();

$leadEventId  = clean_line($_POST['lead_event_id'] ?? '', 40);
$name         = clean_line($_POST['name'] ?? '', 100);
$phone        = clean_line($_POST['phone'] ?? '', 30);
$arrivalDate  = clean_line($_POST['arrival_date'] ?? '', 12);
$hotel        = clean_line($_POST['hotel'] ?? '', 120);
$roomNo       = clean_line($_POST['room_number'] ?? '', 24);
$diversCount  = max(1, min(6, (int) ($_POST['divers_count'] ?? 1)));
$agency       = clean_line($_POST['cert_agency'] ?? '', 30);
$certNo       = clean_line($_POST['cert_number'] ?? '', 50);
$loggedDives  = isset($_POST['logged_dives']) && $_POST['logged_dives'] !== '' ? (string) max(0, min(9999, (int) $_POST['logged_dives'])) : '';
$lastDiveDate = clean_line($_POST['last_dive_date'] ?? '', 12);
$heightCm     = isset($_POST['height_cm']) && $_POST['height_cm'] !== '' ? (string) max(90, min(230, (int) $_POST['height_cm'])) : '';
$weightKg     = isset($_POST['weight_kg']) && $_POST['weight_kg'] !== '' ? (string) max(20, min(200, (int) $_POST['weight_kg'])) : '';
$wetsuit      = clean_line($_POST['wetsuit_size'] ?? '', 16);
$finSize      = clean_line($_POST['fin_size'] ?? '', 16);
$bcdSize      = clean_line($_POST['bcd_size'] ?? '', 16);
$gearNotes    = clean($_POST['gear_notes'] ?? '', 1000);
$nitroxReq    = !empty($_POST['nitrox_request']) ? 'yes' : 'no';
$waiverAck    = !empty($_POST['medical_waiver']);

$medFlags = [];
for ($i = 1; $i <= 3; $i++) {
    if ((($_POST["med_q{$i}"] ?? 'no') === 'yes')) {
        $medFlags[] = "Q{$i}:YES";
    }
}
$medSummary = $medFlags ? implode(', ', $medFlags) : 'all_no';

$errors = [];
if (mb_strlen($name) < 2) {
    $errors['name'] = t('form_required');
}
if ($leadEventId === '' && !preg_match('/^\+?[0-9 ()\-]{6,24}$/', $phone)) {
    $errors['phone'] = t('form_required');
} elseif ($phone !== '' && !preg_match('/^\+?[0-9 ()\-]{6,24}$/', $phone)) {
    $errors['phone'] = t('form_error_validation');
}
if (!$waiverAck) {
    $errors['medical_waiver'] = t('form_required');
}

$fallbackUrl = $leadEventId !== '' ? page_link('/thank-you.php') : page_link('/check-in');
if ($errors) {
    respond(false, ['message' => t('form_error_validation'), 'errors' => $errors], $fallbackUrl);
}

$_SESSION['last_submit'] = time();
$checkinId = 'ci_' . bin2hex(random_bytes(6));

$row = [
    'submitted_at'   => gmdate('c'),
    'checkin_id'     => $checkinId,
    'lead_event_id'  => $leadEventId,
    'lang'           => LANG,
    'name'           => $name,
    'phone'          => $phone,
    'arrival_date'   => $arrivalDate,
    'hotel'          => $hotel,
    'room_number'    => $roomNo,
    'divers_count'   => $diversCount,
    'cert_agency'    => $agency,
    'cert_number'    => $certNo,
    'logged_dives'   => $loggedDives,
    'last_dive_date' => $lastDiveDate,
    'height_cm'      => $heightCm,
    'weight_kg'      => $weightKg,
    'wetsuit_size'   => $wetsuit,
    'fin_size'       => $finSize,
    'bcd_size'       => $bcdSize,
    'nitrox_request' => $nitroxReq,
    'medical_flags'  => $medSummary,
    'medical_waiver' => 'yes',
    'gear_notes'     => $gearNotes,
];

store_csv('diver_checkins.csv', $row);

$lines = [];
foreach ($row as $k => $v) {
    if ($v !== '' && $v !== null) {
        $lines[] = str_pad($k, 18) . ': ' . $v;
    }
}
send_mail(LEAD_NOTIFY_EMAIL, 'Pre-arrival diver check-in — ' . $name . ($arrivalDate !== '' ? ' (' . $arrivalDate . ')' : ''), implode("\n", $lines));

if (CRM_WEBHOOK_URL !== '') {
    post_json(CRM_WEBHOOK_URL, ['type' => 'checkin'] + $row);
}

respond(true, ['message' => t('checkin_ok'), 'event_id' => $checkinId], $fallbackUrl);
