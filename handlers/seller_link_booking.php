<?php
/**
 * POST /handlers/seller_link_booking.php
 * Handles guest self-booking from a Seller's Shareable Digital Brochure Link (`/s/<code>` or `/<lang>/s/<code>`).
 * - Strictly `noindex, nofollow`.
 * - Resolves Seller + Unified Trip (`master_trips_catalog`), computes Adult/Child/Infant EUR & EGP total,
 *   hidden Supplier Adult/Child cost, and hidden `PENDING_EXECUTION` Seller Commission.
 * - Inserts reservation under the Seller's name with `workflow_stage = 'AWAITING_SELLER_CONFIRM'`
 *   and fires an instant in-app notification to the Seller!
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/lead_lib.php';
require_once dirname(__DIR__) . '/includes/erp_db.php';

sc_ensure_session();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POST required']);
    exit;
}

$raw = (string) @file_get_contents('php://input');
$in  = json_decode($raw, true);
if (!is_array($in)) {
    $in = $_POST;
}

if (!empty($in['website_url'])) {
    // Honeypot bot trap
    echo json_encode(['ok' => true, 'voucher_no' => 'SLB-00000']);
    exit;
}

$csrf = (string) ($in['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
if (!csrf_verify($csrf)) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'error' => 'Session token expired — please refresh the page']);
    exit;
}

$pdo = sc_erp_pdo();
if (!$pdo) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'Booking service temporarily unavailable']);
    exit;
}

$sellerToken = clean_line($in['seller'] ?? ($_COOKIE['sc_seller'] ?? ''), 64);
$seller      = sc_erp_resolve_seller($pdo, $sellerToken) ?: sc_erp_resolve_seller($pdo, 'fady.sales');

$guestName = clean_line($in['guest_name'] ?? ($in['name'] ?? ''), 120);
$phone     = clean_line($in['phone'] ?? ($in['guest_phone'] ?? ''), 48);
$hotel     = clean_line($in['hotel_name'] ?? ($in['hotel'] ?? ''), 120);
$room      = clean_line($in['room_no'] ?? '', 32);
$nat       = clean_line($in['nationality'] ?? strtoupper($lang), 40);
$tripCode  = clean_line($in['trip_code'] ?? ($in['product_code'] ?? 'exc-white-island-ras-mohammed-boat'), 64);
$tripDate  = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($in['trip_date'] ?? '')) ? (string) $in['trip_date'] : gmdate('Y-m-d', time() + 86400);
$adults    = max(1, min(50, (int) ($in['adults'] ?? 2)));
$children  = max(0, min(30, (int) ($in['children'] ?? 0)));
$infants   = max(0, min(15, (int) ($in['infants'] ?? 0)));
$notes     = clean_line($in['notes'] ?? '', 240);
$lang      = clean_line($in['lang'] ?? 'en', 8);

if ($guestName === '' || $phone === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Please enter your name and WhatsApp / phone number']);
    exit;
}

$fx       = sc_erp_fx_rates($pdo);
$tripMeta = sc_erp_lookup_unified_trip($pdo, $tripCode);
$fin      = sc_erp_compute_trip_financials($tripMeta, $adults, $children, $infants, $fx);

$voNo   = 'SLB-' . random_int(10000, 99999);
$qrTok  = 'QR-' . strtoupper(bin2hex(random_bytes(6)));
[$wk, $mo, $qr, $yr] = sc_erp_period_parts($tripDate);

$tripName   = $tripMeta ? (string) $tripMeta['unified_name_en'] : $tripCode;
$totalEur   = (float) $fin['catalog_total_eur'];
$grossEgp   = (float) $fin['gross_total_egp'];
$supCostEgp = (float) $fin['supplier_cost_egp'];
$saleMode   = (string) $fin['sale_mode'];

$inCurr    = strtoupper(clean_line($in['currency'] ?? 'EUR', 8));
$depRaw    = max(0.0, (float) ($in['deposit_paid_now'] ?? 0.0));
$depInEur  = $inCurr === 'EUR' ? $depRaw : round(($depRaw * ($fx[$inCurr] ?? $fx['EUR'])) / $fx['EUR'], 2);
$paidEur   = round(min($totalEur, $depInEur), 2);
$remEur    = round(max(0.0, $totalEur - $paidEur), 2);
$depEgp    = round($paidEur * $fx['EUR'], 2);
$restEgp   = round($remEur * $fx['EUR'], 2);
$payStatus = $restEgp <= 0.01 ? 'PAID' : 'OPEN_REST';

$sUser   = $seller ? (string) $seller['username'] : 'fady.sales';
$sRep    = $seller ? (string) $seller['rep_scope'] : 'FADY';
$sBranch = $seller ? (string) ($seller['branch_scope'] ?: 'CYRENE') : 'CYRENE';

$commRes = sc_erp_record_seller_commission($pdo, [
    'seller_token'     => $sUser,
    'sale_mode'        => $saleMode,
    'tx_date'          => $tripDate,
    'voucher_no'       => $voNo,
    'guest_name'       => $guestName,
    'product_name'     => $tripName,
    'pax'              => $adults + $children + $infants,
    'gross_amount_egp' => $grossEgp,
    'supplier_cost_egp'=> $saleMode === 'OUT_SIDE' ? $supCostEgp : 0.0,
    'status'           => 'PENDING_EXECUTION',
    'source_channel'   => 'SELLER_SHARE_LINK',
    'branch_code'      => $sBranch,
    'notes'            => 'Guest booked via shareable seller link (' . $lang . ')',
]);

$pickupWindow = clean_line($in['pickup_time'] ?? '08:00', 24);
$ins = $pdo->prepare(
    'INSERT INTO reservations_fact
     (voucher_no, qr_token, trip_date, period_week, period_month, period_quarter, period_year,
      branch_code, channel, hotel_name, pickup_zone, room_no, nationality, pickup_time,
      trip_name, unified_trip_code, product_category, adults, children, infants, total_pax,
      adult_unit_eur, child_unit_eur, infant_unit_eur,
      booked_adults, booked_children, booked_infants, booked_total_egp,
      snork_pax, intro1_pax, intro2_pax, dives_pax, divers_names,
      sales_rep, executor, ap_supplier_code, client_agency,
      rest_egp, rest_usd, rest_eur, rest_gbp, rest_egp_equiv,
      desk_paid_egp, deposit_egp_equiv, actual_paid_total_egp, supplier_cost_egp,
      estimated_revenue_egp, estimated_cogs_egp, estimated_profit_egp, vessel_code,
      boarding_status, payment_status, booking_state, workflow_stage, source_file,
      seller_type, seller_username, seller_commission_egp, commission_type, commission_status)
     VALUES (?,?,?,?,?,?,?, ?,?,?,?,?,?,?, ?,?,?,?,?,?,?, ?,?,?, ?,?,?,?, 0,0,0,0,?, ?,?,?,?, 0,0,?,0,?, ?,?,?,?, ?,?,?, ?,?,?,?,?, ?, ?,?,?,?,?)'
);
$ins->execute([
    $voNo, $qrTok, $tripDate, $wk, $mo, $qr, $yr,
    $sBranch, 'SELLER_SHARE_LINK', $hotel !== '' ? $hotel : 'Sharm Hotel', '', $room, $nat !== '' ? $nat : strtoupper($lang), $pickupWindow,
    $tripName, $tripMeta ? (string) $tripMeta['trip_code'] : $tripCode, 'SHARE_LINK_BOOKING',
    $adults, $children, $infants, $adults + $children + $infants,
    $fin['adult_unit_eur'], $fin['child_unit_eur'], $fin['infant_unit_eur'],
    $adults, $children, $infants, $grossEgp,
    $guestName . ' (' . $phone . ')',
    $sRep, $fin['supplier_name'], $fin['supplier_code'] !== 'SEA_COLLEGE' ? $fin['supplier_code'] : '', 'DIRECT',
    $remEur, $restEgp,
    $depEgp, $depEgp, $depEgp, $supCostEgp,
    $grossEgp, $supCostEgp, round($grossEgp - $supCostEgp, 2),
    '', 'BOOKED', $payStatus, 'EXECUTED', 'AWAITING_SELLER_CONFIRM', 'SELLER_SHARE_LINK',
    'HOTEL_SELLER', $sUser,
    $commRes['commission_egp'], $commRes['commission_type'], $commRes['commission_status'],
]);
$newVid = (int) $pdo->lastInsertId();

// Also log in website_leads_crm so Lara & CRM stay in sync
$pdo->prepare(
    'INSERT OR REPLACE INTO website_leads_crm
     (event_id, received_at, lead_type, lang, guest_name, phone, email, interest_items,
      people, trip_date, hotel, room_no, cert_level, gear_sizes_json, estimated_eur, final_paid_eur,
      status, gclid, fbclid, utm_source, utm_campaign, assigned_to, linked_voucher_no,
      seller_username, sales_rep_code)
     VALUES (?,?,?,?,?,?, "", ?,?,?,?,?, "", "{}", ?, ?, "NEW", "", "", "seller_share_link", "brochure", ?, ?, ?, ?)'
)->execute([
    'slb_' . strtolower($voNo), gmdate('c'), 'SELLER_LINK_BOOKING', $lang,
    $guestName, $phone, $tripName,
    $adults + $children + $infants, $tripDate, $hotel, $room,
    $totalEur, $paidEur, $sRep, $voNo, $sUser, $sRep,
]);

sc_erp_push_notification(
    $pdo,
    $sUser,
    'NEW_LINK_BOOKING',
    $voNo,
    "🎉 New Booking Confirmed by Guest ({$voNo})!",
    "🎉 حجز جديد من العميل عبر رابطك الشخصي ({$voNo})!",
    "{$guestName} ({$phone}) booked {$tripName} for {$tripDate} ({$adults} Adl, {$children} Ch, {$infants} Inf — Total €{$totalEur}, Deposit €{$paidEur}, Rest €{$remEur}). Tap to confirm to Reservations!",
    "حجز العميل {$guestName} ({$phone}) رحلة {$tripName} ليوم {$tripDate} ({$adults} بالغ، {$children} طفل، {$infants} رضيع — الإجمالي €{$totalEur}، العربون €{$paidEur}، المتبقي €{$remEur}). اضغط للتأكيد وإرسال الحجز لقسم الحجوزات!"
);

echo json_encode([
    'ok'             => true,
    'voucher_id'     => $newVid,
    'voucher_no'     => $voNo,
    'qr_token'       => $qrTok,
    'trip_name'      => $tripName,
    'trip_date'      => $tripDate,
    'adults'         => $adults,
    'children'       => $children,
    'infants'        => $infants,
    'total_eur'      => $totalEur,
    'deposit_eur'    => $paidEur,
    'remaining_eur'  => $remEur,
    'pickup_time'    => $pickupWindow,
    'seller_name'    => $seller ? (string) $seller['display_name_en'] : 'Sea College Concierge',
    'whatsapp_phone' => WHATSAPP_NUMBER,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
