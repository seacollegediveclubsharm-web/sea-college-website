<?php
/**
 * Sea College Dive Club — Shareable Seller Digital Brochure & Guest Booking Link (`/s/<code>` -> `seller-brochure.php`)
 *
 * ARCHITECTURE RULE:
 * - Strictly `noindex, nofollow` (never indexed on Google or linked from public diving website menus).
 * - Designed for Hotel & Jetty Sellers to share via WhatsApp, Facebook, Instagram, Telegram, SMS, or QR Code
 *   with guests who want time to browse all Diving, Yacht, Safari, Cairo, St. Catherine, Dahab, Dolphin & Submarine
 *   trips in their hotel room and confirm their booking later!
 * - Zero commission numbers or internal vendor costs are ever exposed on this page.
 * - Bookings go directly under the Seller's account and trigger an instant 🔔 notification in `/app`.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/erp_db.php';

sc_ensure_session();
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store, must-revalidate');

$pdo = sc_erp_pdo();
$rawSeller = trim((string) ($_GET['seller'] ?? ($_COOKIE['sc_seller'] ?? 'fady')));
$sellerRow = $pdo ? (sc_erp_resolve_seller($pdo, $rawSeller) ?: sc_erp_resolve_seller($pdo, 'fady.sales')) : null;
$sellerSlug = $sellerRow ? (string) ($sellerRow['referral_code'] ?: 'fady') : 'fady';
$sellerNameEn = $sellerRow ? (string) $sellerRow['display_name_en'] : 'Sea College Concierge';
$sellerNameAr = $sellerRow ? (string) $sellerRow['display_name_ar'] : 'مستشار رحلات سي كوليدج';

sc_apply_seller_referral($sellerSlug);

$supportedLangs = ['en', 'ar', 'ru', 'de', 'it', 'fr', 'pl', 'zh'];
$reqLang = strtolower(trim((string) ($_GET['lang'] ?? LANG)));
$lang = in_array($reqLang, $supportedLangs, true) ? $reqLang : 'en';
$isRtl = ($lang === 'ar');
$focusTrip = trim((string) ($_GET['trip'] ?? ''));

$prevLang = $GLOBALS['SC_LANG'] ?? 'en';
$GLOBALS['SC_LANG'] = $lang;
$rawCatalog = require __DIR__ . '/data/catalog.php';
$galleries  = is_file(__DIR__ . '/data/item_galleries.php') ? (require __DIR__ . '/data/item_galleries.php') : [];
$appExcRaw  = is_file(__DIR__ . '/data/app_excursions_catalog.php') ? (require __DIR__ . '/data/app_excursions_catalog.php') : [];
$opSkusRaw  = is_file(__DIR__ . '/data/operational_skus.php') ? (require __DIR__ . '/data/operational_skus.php') : [];
$hotelData  = is_file(__DIR__ . '/data/hotel_zones.php') ? (require __DIR__ . '/data/hotel_zones.php') : ['zones' => [], 'hotels' => []];
$GLOBALS['SC_LANG'] = $prevLang;

$fx = $pdo ? sc_erp_fx_rates($pdo) : ['EGP' => 1.0, 'EUR' => 57.0, 'USD' => 50.0, 'GBP' => 65.0];
$csrf = csrf_token();

$cards = [];
foreach ($appExcRaw as $ex) {
    $pEur = (float) ($ex['price_eur'] ?? 30);
    $cEur = isset($ex['child_price_eur']) && $ex['child_price_eur'] !== null ? (float) $ex['child_price_eur'] : round($pEur * 0.5, 0);
    $cards[] = [
        'id'        => (string) $ex['id'],
        'cat'       => (string) ($ex['category_group'] ?? 'excursions'),
        'cat_label' => (string) ($ex['category_labels'][$lang] ?? $ex['category_labels']['en'] ?? 'Excursions'),
        'name'      => (string) ($ex['names'][$lang] ?? $ex['names']['en'] ?? $ex['id']),
        'summary'   => (string) ($ex['summaries'][$lang] ?? $ex['summaries']['en'] ?? ''),
        'duration'  => (string) ($ex['duration'] ?? 'Full Day'),
        'adult_eur' => $pEur,
        'child_eur' => $cEur,
        'infant_eur'=> (float) ($ex['infant_price_eur'] ?? 0),
        'img'       => sc_erp_resolve_asset_webp((string) ($ex['cover_webp'] ?? '')),
        'video'     => (string) ($ex['video_src'] ?? ''),
        'age'       => (string) ($ex['specs']['min_age'] ?? 'All Ages'),
        'pickup'    => (string) ($ex['specs']['pickup'] ?? 'Free Hotel Pickup'),
        'popular'   => !empty($ex['popular']),
    ];
}
foreach ($opSkusRaw as $sk) {
    if (!empty($sk['is_pass_through_fee'])) {
        continue;
    }
    $pEur = (float) ($sk['price_eur'] ?? 30);
    $cEur = isset($sk['child_price_eur']) && $sk['child_price_eur'] !== null ? (float) $sk['child_price_eur'] : $pEur;
    $cards[] = [
        'id'        => (string) $sk['id'],
        'cat'       => (string) ($sk['category_group'] ?? 'watersports'),
        'cat_label' => (string) ($sk['category_labels'][$lang] ?? $sk['category_labels']['en'] ?? 'Watersports & Extras'),
        'name'      => (string) ($sk['names'][$lang] ?? $sk['names']['en'] ?? $sk['id']),
        'summary'   => (string) ($sk['summaries'][$lang] ?? $sk['summaries']['en'] ?? ''),
        'duration'  => (string) ($sk['duration'] ?? 'Flexible'),
        'adult_eur' => $pEur,
        'child_eur' => $cEur,
        'infant_eur'=> 0.0,
        'img'       => sc_erp_resolve_asset_webp((string) ($sk['cover_webp'] ?? '')),
        'video'     => (string) ($sk['video_src'] ?? ''),
        'age'       => (string) ($sk['specs']['min_age'] ?? '8+'),
        'pickup'    => (string) ($sk['specs']['pickup'] ?? 'Included'),
        'popular'   => !empty($sk['popular']),
    ];
}
foreach ($rawCatalog as $catKey => $catMeta) {
    if (in_array($catKey, ['equipment', 'insurance'], true)) {
        continue;
    }
    $catLabel = is_array($catMeta['label'] ?? null)
        ? ($catMeta['label'][$lang] ?? $catMeta['label']['en'] ?? $catKey)
        : (string) ($catMeta['label'] ?? $catKey);
    foreach (($catMeta['groups'] ?? []) as $grp) {
        foreach (($grp['items'] ?? []) as $it) {
            $id = (string) ($it['id'] ?? '');
            if ($id === '' || ($it['price'] ?? null) === null || preg_match('/^(eq|fee|ins)-/', $id)) {
                continue;
            }
            $pEur = (float) $it['price'];
            $cEur = str_contains($catKey, 'snork') ? round($pEur * 0.5, 0) : $pEur;
            $gal  = $galleries[$id] ?? [];
            $cards[] = [
                'id'        => $id,
                'cat'       => 'diving-' . $catKey,
                'cat_label' => $catLabel,
                'name'      => $lang === 'ar' && !empty($it['name_ar']) ? (string) $it['name_ar'] : (string) ($it['name'] ?? $id),
                'summary'   => $lang === 'ar' && !empty($it['desc_ar']) ? (string) $it['desc_ar'] : (string) ($it['desc'] ?? ''),
                'duration'  => (string) ($it['duration'] ?? 'Full Day'),
                'adult_eur' => $pEur,
                'child_eur' => $cEur,
                'infant_eur'=> 0.0,
                'img'       => sc_erp_resolve_asset_webp((string) ($it['img'] ?? ($gal['photos'][0] ?? ''))),
                'video'     => !empty($gal['video']['src']) ? ('/assets/' . ltrim((string) $gal['video']['src'], '/')) : '/assets/videos/sea-college-sharm-red-sea-overview.mp4',
                'age'       => str_contains($catKey, 'snork') ? 'All Ages' : '10+ Years',
                'pickup'    => 'Free Hotel Transfer',
                'popular'   => !empty($it['popular']),
            ];
        }
    }
}

$hotelsSimple = [];
$zonesMap = $hotelData['zones'] ?? [];
foreach (($hotelData['hotels'] ?? []) as $h) {
    $zk = (string) ($h['zone'] ?? 'montazah');
    $hotelsSimple[] = [
        'name'   => (string) $h['name'],
        'pickup' => (string) ($zonesMap[$zk]['pickup'] ?? '08:00'),
    ];
}

$canonicalShareUrl = 'https://seacollegediveclub.com' . ($lang === 'en' ? '' : '/' . $lang) . '/s/' . rawurlencode($sellerSlug);

$rawPromo = trim((string) ($_GET['promo'] ?? ''));
$sUserForPolicy = $sellerRow ? (string) $sellerRow['username'] : 'fady.sales';
$sellerDiscPolicy = $pdo ? sc_erp_get_seller_discount_policy($pdo, $sUserForPolicy) : ['is_enabled' => true, 'max_discount_pct' => 20.0, 'max_discount_eur' => 50.0];
$verifiedPromo = ($pdo && $rawPromo !== '' && !empty($sellerDiscPolicy['is_enabled']))
    ? sc_erp_verify_promo_token($rawPromo, $sellerSlug, '')
    : null;
$activePromoToken = $verifiedPromo ? $rawPromo : '';
if ($activePromoToken !== '') {
    $canonicalShareUrl .= '?promo=' . rawurlencode($activePromoToken);
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES) ?>" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#07192b">
  <link rel="icon" href="/assets/images/logo.png">
  <title><?= htmlspecialchars($isRtl ? ('كتالوج رحلات شرم الشيخ — ' . $sellerNameAr) : ('Sharm El Sheikh Trips & Diving Concierge — ' . $sellerNameEn), ENT_QUOTES) ?></title>
  <style>
    :root{--bg:#061526;--card:#0c233c;--card2:#123152;--line:rgba(125,211,252,.18);--txt:#f0f9ff;--muted:#94a3b8;--cyan:#00d2ff;--emerald:#10b981;--amber:#f59e0b}
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Cairo",sans-serif;background:radial-gradient(circle at top,#0f355c 0%,var(--bg) 60%);color:var(--txt);min-height:100vh;padding-bottom:90px}
    .hero{padding:20px 16px;background:linear-gradient(135deg,rgba(12,35,60,.95),rgba(6,21,38,.96));border-bottom:1px solid var(--line);position:sticky;top:0;z-index:30;backdrop-filter:blur(12px)}
    .hero-inner{max-width:1180px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
    .rep-badge{display:flex;align-items:center;gap:12px}
    .rep-avatar{width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--cyan),var(--emerald));display:grid;place-items:center;font-weight:900;color:#041322;font-size:18px;flex-shrink:0}
    .rep-title{font-size:16px;font-weight:800}
    .rep-sub{font-size:12px;color:#7dd3fc}
    .controls{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    select,input{background:var(--card2);color:var(--txt);border:1px solid var(--line);border-radius:10px;padding:9px 12px;font-size:13px}
    .btn{border:none;border-radius:11px;padding:10px 15px;font-weight:800;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:6px;text-decoration:none;transition:.15s}
    .btn-cyan{background:linear-gradient(135deg,#00d2ff,#0284c7);color:#031221}
    .btn-wa{background:linear-gradient(135deg,#22c55e,#15803d);color:#fff}
    .btn-ghost{background:rgba(255,255,255,.07);color:var(--txt);border:1px solid var(--line)}
    .wrap{max-width:1180px;margin:0 auto;padding:16px}
    .promo-banner{margin-bottom:14px;padding:13px 16px;border-radius:14px;background:linear-gradient(135deg,rgba(245,158,11,.22),rgba(16,185,129,.22));border:1px solid rgba(251,191,36,.55);display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;font-weight:800;color:#fef3c7}
    .cats{display:flex;gap:8px;overflow-x:auto;padding-bottom:10px;margin-bottom:14px}
    .chip{padding:9px 15px;border-radius:999px;background:var(--card);border:1px solid var(--line);color:var(--muted);font-weight:700;font-size:13px;cursor:pointer;white-space:nowrap}
    .chip.active{background:linear-gradient(135deg,var(--cyan),#0284c7);color:#031221;border-color:transparent}
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(310px,1fr));gap:16px}
    .card{background:var(--card);border:1px solid var(--line);border-radius:18px;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 12px 28px rgba(0,0,0,.32)}
    .card-media{position:relative;aspect-ratio:16/9;background:#07192b;overflow:hidden}
    .card-media img{width:100%;height:100%;object-fit:cover;display:block}
    .cat-pill{position:absolute;top:10px;left:10px;background:rgba(6,21,38,.82);backdrop-filter:blur(6px);padding:4px 10px;border-radius:999px;font-size:11px;font-weight:700;color:#7dd3fc;border:1px solid var(--line)}
    .promo-pill{position:absolute;top:10px;right:10px;background:linear-gradient(135deg,#f59e0b,#d97706);padding:4px 10px;border-radius:999px;font-size:11px;font-weight:900;color:#041322;box-shadow:0 4px 10px rgba(0,0,0,.35)}
    .price-pill{position:absolute;bottom:10px;right:10px;background:linear-gradient(135deg,#0369a1,#0ea5e9);padding:6px 12px;border-radius:10px;font-weight:900;font-size:15px;color:#fff;box-shadow:0 4px 14px rgba(0,0,0,.4)}
    .card-body{padding:15px;display:flex;flex-direction:column;gap:10px;flex:1}
    .card-title{font-size:16px;font-weight:800;line-height:1.35}
    .card-desc{font-size:13px;color:var(--muted);line-height:1.5;flex:1}
    .badges{display:flex;flex-wrap:wrap;gap:6px}
    .b-tag{font-size:11px;background:rgba(255,255,255,.05);border:1px solid var(--line);padding:4px 8px;border-radius:7px;color:#cbd5e1}
    .tier-prices{display:flex;gap:8px;flex-wrap:wrap;font-size:12px;background:rgba(16,185,129,.09);border:1px solid rgba(16,185,129,.25);padding:8px 10px;border-radius:10px;color:#a7f3d0;font-weight:700}
    .modal-bg{position:fixed;inset:0;background:rgba(2,10,20,.82);backdrop-filter:blur(6px);z-index:80;display:none;align-items:center;justify-content:center;padding:14px}
    .modal-bg.open{display:flex}
    .modal{background:var(--card);border:1px solid var(--line);border-radius:20px;max-width:520px;width:100%;padding:20px;max-height:92vh;overflow-y:auto}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:12px}
    .form-grid .full{grid-column:1/-1}
    label{display:block;font-size:12px;color:#93c5fd;margin-bottom:4px;font-weight:700}
    .form-grid input,.form-grid select{width:100%}
    .total-box{margin-top:12px;padding:12px;border-radius:12px;background:rgba(0,210,255,.1);border:1px solid rgba(0,210,255,.3);display:flex;justify-content:space-between;align-items:center;font-weight:800;gap:10px;flex-wrap:wrap}
  </style>
</head>
<body>
  <header class="hero">
    <div class="hero-inner">
      <div class="rep-badge">
        <div class="rep-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($sellerNameEn, 0, 1)), ENT_QUOTES) ?></div>
        <div>
          <div class="rep-title">🌊 <?= htmlspecialchars($isRtl ? ('مستشارك الشخصي: ' . $sellerNameAr) : ('Your Personal Concierge: ' . $sellerNameEn), ENT_QUOTES) ?></div>
          <div class="rep-sub"><?= htmlspecialchars($isRtl ? 'سي كوليدج شرم الشيخ — احجز الآن وادفع عند التحرك من الفندق · الرضع مجانًا' : 'Sea College Sharm El Sheikh — Confirm Now, Pay at Hotel Pickup · Infants <3 Free', ENT_QUOTES) ?></div>
        </div>
      </div>
      <div class="controls">
        <select id="curSelect" aria-label="Currency">
          <option value="EUR">€ EUR</option>
          <option value="USD">$ USD</option>
          <option value="GBP">£ GBP</option>
          <option value="EGP">EGP ج.م</option>
        </select>
        <select id="langSelect" aria-label="Language">
          <?php foreach (['en'=>'🇬🇧 EN','ar'=>'🇪🇬 العربية','ru'=>'🇷🇺 RU','de'=>'🇩🇪 DE','it'=>'🇮🇹 IT','fr'=>'🇫🇷 FR','pl'=>'🇵🇱 PL','zh'=>'🇨🇳 中文'] as $lc => $lbl): ?>
            <option value="<?= $lc ?>" <?= $lc === $lang ? 'selected' : '' ?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select>
        <button type="button" class="btn btn-ghost" id="btnSharePage">📲 <?= $isRtl ? 'مشاركة الكتالوج' : 'Share Link' ?></button>
      </div>
    </div>
  </header>

  <main class="wrap">
    <?php if ($verifiedPromo):
        $pLabel = $verifiedPromo['discount_type'] === 'PERCENT'
            ? ('-' . rtrim(rtrim(number_format((float) $verifiedPromo['discount_value'], 2), '0'), '.') . '%')
            : ('-' . rtrim(rtrim(number_format((float) $verifiedPromo['discount_value'], 2), '0'), '.') . ' ' . $verifiedPromo['discount_currency']);
    ?>
      <div class="promo-banner" id="vipPromoBanner">
        <span>🎁 <?= htmlspecialchars($isRtl ? ('خصم خاص لك من ' . $sellerNameAr . ': ' . $pLabel . ' مطبق تلقائيًا على حجزك!') : ('Personal Special Discount from ' . $sellerNameEn . ': ' . $pLabel . ' automatically applied to your booking!'), ENT_QUOTES) ?></span>
        <span style="font-size:12px;color:#a7f3d0;">✅ <?= $isRtl ? 'عرض موثّق ومعتمد' : 'Verified VIP Offer' ?></span>
      </div>
    <?php endif; ?>
    <div style="margin-bottom:12px;display:flex;gap:10px;flex-wrap:wrap">
      <input type="search" id="searchInp" style="flex:1;min-width:220px" placeholder="<?= htmlspecialchars($isRtl ? 'ابحث عن الجزيرة البيضاء، سفاري، القاهرة، سانت كاترين، غوص...' : 'Search White Island, Quad Safari, Cairo, St. Catherine, Diving...', ENT_QUOTES) ?>">
    </div>
    <div class="cats" id="catBar"></div>
    <div class="grid" id="cardsGrid"></div>
  </main>

  <div class="modal-bg" id="bookModal">
    <div class="modal">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
        <h3 id="mTitle" style="font-size:17px">Confirm Trip</h3>
        <button type="button" class="btn btn-ghost" onclick="closeModal()" style="padding:6px 10px">✕</button>
      </div>
      <form id="bookForm">
        <input type="text" name="website_url" style="display:none" tabindex="-1" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-grid">
          <div class="full">
            <label><?= $isRtl ? 'الاسم الكامل' : 'Your Full Name *' ?></label>
            <input type="text" id="fName" required placeholder="Marco Rossi">
          </div>
          <div>
            <label><?= $isRtl ? 'رقم الواتساب / الهاتف *' : 'WhatsApp / Phone *' ?></label>
            <input type="tel" id="fPhone" required placeholder="+39 333 1234567">
          </div>
          <div>
            <label><?= $isRtl ? 'تاريخ الرحلة المفضل' : 'Preferred Trip Date' ?></label>
            <input type="date" id="fDate" required value="<?= gmdate('Y-m-d', time() + 86400) ?>">
          </div>
          <div>
            <label><?= $isRtl ? 'الفندق في شرم الشيخ' : 'Your Hotel in Sharm' ?></label>
            <input type="text" id="fHotel" list="hotelsList" placeholder="Start typing hotel...">
            <datalist id="hotelsList">
              <?php foreach ($hotelsSimple as $hs): ?>
                <option value="<?= htmlspecialchars($hs['name'], ENT_QUOTES) ?>"><?= htmlspecialchars('Pickup ~' . $hs['pickup'], ENT_QUOTES) ?></option>
              <?php endforeach; ?>
            </datalist>
          </div>
          <div>
            <label><?= $isRtl ? 'رقم الغرفة (اختياري)' : 'Room # (Optional)' ?></label>
            <input type="text" id="fRoom" placeholder="e.g. 214">
          </div>
          <div>
            <label><?= $isRtl ? 'البالغين (12+)' : 'Adults (12+)' ?></label>
            <input type="number" id="fAdults" min="1" max="50" value="2">
          </div>
          <div>
            <label><?= $isRtl ? 'الأطفال (3–11 سنة)' : 'Children (3–11 yrs)' ?></label>
            <input type="number" id="fChildren" min="0" max="30" value="0">
          </div>
          <div>
            <label><?= $isRtl ? 'الرضع (أقل من 3 سنوات — مجانًا)' : 'Infants (<3 yrs — FREE)' ?></label>
            <input type="number" id="fInfants" min="0" max="15" value="0">
          </div>
          <div>
            <label><?= $isRtl ? 'عربون مدفوع الآن (€ اختياري)' : 'Deposit Paid Now (€ Optional)' ?></label>
            <input type="number" step="1" min="0" id="fDeposit" value="0">
          </div>
        </div>
        <div class="total-box">
          <span><?= $isRtl ? 'الإجمالي الفعلي (الباقي عند التحرك من الفندق):' : 'Total Price (Remaining Paid at Pickup):' ?></span>
          <span id="mTotalPrice" style="font-size:18px;color:#38bdf8">€60</span>
        </div>
        <div id="mSuccess" style="display:none;margin-top:12px;padding:12px;border-radius:12px;background:rgba(16,185,129,.16);border:1px solid #10b981;color:#d1fae5;font-size:13px"></div>
        <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap">
          <button type="submit" class="btn btn-cyan" id="btnSubmitBook" style="flex:1">✅ <?= $isRtl ? 'تأكيد الحجز الآن' : 'Confirm Booking Now' ?></button>
        </div>
      </form>
    </div>
  </div>

  <script>
    const CARDS = <?= json_encode($cards, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const HOTELS = <?= json_encode($hotelsSimple, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const FX = <?= json_encode($fx, JSON_UNESCAPED_SLASHES) ?>;
    const SELLER_SLUG = <?= json_encode($sellerSlug) ?>;
    const CSRF_TOKEN = <?= json_encode($csrf) ?>;
    const SHARE_URL = <?= json_encode($canonicalShareUrl) ?>;
    const FOCUS_TRIP = <?= json_encode($focusTrip) ?>;
    const PROMO = <?= json_encode($verifiedPromo) ?>;
    const PROMO_TOKEN = <?= json_encode($activePromoToken) ?>;
    const IS_RTL = <?= $isRtl ? 'true' : 'false' ?>;

    let activeCat = 'ALL';
    let cur = 'EUR';
    let activeTrip = null;

    function promoMatchesTrip(tripId) {
      if (!PROMO) return false;
      if (!PROMO.trip_code || PROMO.trip_code === '*') return true;
      return String(PROMO.trip_code).toLowerCase() === String(tripId || '').toLowerCase();
    }

    function calcPromoNetEur(listEur, tripId) {
      if (!promoMatchesTrip(tripId) || listEur <= 0) return { listEur, netEur: listEur, savedEur: 0, badge: '' };
      const fxEur = Number(FX.EUR || 57);
      let discEur = 0;
      let badge = '';
      if (PROMO.discount_type === 'PERCENT') {
        discEur = Math.round(listEur * (Number(PROMO.discount_value || 0) / 100) * 100) / 100;
        badge = `🎁 -${PROMO.discount_value}%`;
      } else {
        const dc = String(PROMO.discount_currency || 'EUR');
        const rate = Number(FX[dc] || fxEur);
        const valInEur = dc === 'EUR' ? Number(PROMO.discount_value || 0) : ((Number(PROMO.discount_value || 0) * rate) / fxEur);
        discEur = Math.min(listEur * 0.5, Math.round(valInEur * 100) / 100);
        badge = `🎁 -${PROMO.discount_value} ${dc}`;
      }
      const netEur = Math.max(0, Math.round((listEur - discEur) * 100) / 100);
      return { listEur, netEur, savedEur: Math.round((listEur - netEur) * 100) / 100, badge };
    }

    function fmtMoney(eurVal) {
      const eurEgp = Number(FX.EUR || 57);
      if (cur === 'EUR') return '€' + Math.round(eurVal * 100) / 100;
      if (cur === 'USD') return '$' + Math.round((eurVal * eurEgp) / Number(FX.USD || 50));
      if (cur === 'GBP') return '£' + Math.round((eurVal * eurEgp) / Number(FX.GBP || 65));
      return Math.round(eurVal * eurEgp).toLocaleString() + ' EGP';
    }

    function esc(s) {
      return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function renderCategories() {
      const counts = { ALL: CARDS.length };
      const labels = { ALL: IS_RTL ? '🌟 كل الرحلات' : '🌟 All Trips' };
      CARDS.forEach(c => {
        counts[c.cat] = (counts[c.cat] || 0) + 1;
        labels[c.cat] = c.cat_label;
      });
      document.getElementById('catBar').innerHTML = Object.keys(counts).map(k =>
        `<button type="button" class="chip ${activeCat === k ? 'active' : ''}" onclick="activeCat='${esc(k)}';renderCategories();renderGrid();">${esc(labels[k])} (${counts[k]})</button>`
      ).join('');
    }

    function renderGrid() {
      const q = document.getElementById('searchInp').value.trim().toLowerCase();
      const list = CARDS.filter(c => (activeCat === 'ALL' || c.cat === activeCat) && (!q || (c.name + ' ' + c.summary + ' ' + c.cat_label).toLowerCase().includes(q)));
      document.getElementById('cardsGrid').innerHTML = list.map(c => {
        const hasPctCard = promoMatchesTrip(c.id) && PROMO && PROMO.discount_type === 'PERCENT';
        const hasAmtCard = promoMatchesTrip(c.id) && PROMO && PROMO.discount_type === 'AMOUNT';
        const netAdlEur = hasPctCard ? Math.round(c.adult_eur * (1 - Number(PROMO.discount_value) / 100) * 100) / 100 : c.adult_eur;
        return `
        <article class="card">
          <div class="card-media">
            <img src="${esc(c.img)}" alt="${esc(c.name)}" loading="lazy">
            <span class="cat-pill">${esc(c.cat_label)}</span>
            ${hasPctCard ? `<span class="promo-pill">🎁 -${esc(PROMO.discount_value)}% VIP</span>` : (hasAmtCard ? `<span class="promo-pill">🎁 -${esc(PROMO.discount_value)} ${esc(PROMO.discount_currency)}</span>` : '')}
            <span class="price-pill">${hasPctCard ? `<s style="opacity:.75;font-size:12px;margin-right:4px">${fmtMoney(c.adult_eur)}</s>${fmtMoney(netAdlEur)}` : fmtMoney(c.adult_eur)}</span>
          </div>
          <div class="card-body">
            <div class="card-title">${esc(c.name)}</div>
            <div class="card-desc">${esc(c.summary)}</div>
            <div class="tier-prices">
              <span>👤 ${IS_RTL ? 'بالغ' : 'Adult'}: ${fmtMoney(netAdlEur)}</span>
              <span>· 🧒 ${IS_RTL ? 'طفل' : 'Child'}: ${c.child_eur > 0 ? fmtMoney(hasPctCard ? Math.round(c.child_eur * (1 - Number(PROMO.discount_value) / 100) * 100) / 100 : c.child_eur) : (IS_RTL ? 'مجانًا' : 'Included')}</span>
              <span>· 👶 ${IS_RTL ? 'رضيع (<3)' : 'Infant (<3)'}: ${c.infant_eur > 0 ? fmtMoney(c.infant_eur) : (IS_RTL ? 'مجانًا' : 'FREE')}</span>
            </div>
            <div class="badges">
              <span class="b-tag">⏱ ${esc(c.duration)}</span>
              <span class="b-tag">🎂 ${esc(c.age)}</span>
              <span class="b-tag">🚐 ${esc(c.pickup)}</span>
            </div>
            <div style="display:flex;gap:8px;margin-top:4px">
              <button type="button" class="btn btn-cyan" style="flex:1" onclick="openBook('${esc(c.id)}')">⚡ ${IS_RTL ? 'احجز الآن' : 'Confirm Booking'}</button>
              <button type="button" class="btn btn-ghost" onclick="shareTrip('${esc(c.id)}')">📲</button>
            </div>
          </div>
        </article>
      `}).join('');
    }

    function openBook(id) {
      activeTrip = CARDS.find(x => x.id === id) || CARDS[0];
      if (!activeTrip) return;
      document.getElementById('mTitle').textContent = activeTrip.name;
      document.getElementById('mSuccess').style.display = 'none';
      document.getElementById('btnSubmitBook').style.display = 'inline-flex';
      updateModalTotal();
      document.getElementById('bookModal').classList.add('open');
    }
    function closeModal() {
      document.getElementById('bookModal').classList.remove('open');
    }
    function updateModalTotal() {
      if (!activeTrip) return;
      const a = Math.max(1, parseInt(document.getElementById('fAdults').value || '1', 10));
      const c = Math.max(0, parseInt(document.getElementById('fChildren').value || '0', 10));
      const i = Math.max(0, parseInt(document.getElementById('fInfants').value || '0', 10));
      const listEur = a * activeTrip.adult_eur + c * activeTrip.child_eur + i * activeTrip.infant_eur;
      const pr = calcPromoNetEur(listEur, activeTrip.id);
      const el = document.getElementById('mTotalPrice');
      if (pr.savedEur > 0) {
        el.innerHTML = `<s style="opacity:.65;font-size:13px;color:#cbd5e1;margin-right:6px">${fmtMoney(pr.listEur)}</s> <span style="color:#34d399">${fmtMoney(pr.netEur)}</span> <span style="font-size:11.5px;color:#fcd34d">(${esc(pr.badge)})</span>`;
      } else {
        el.textContent = fmtMoney(listEur);
      }
    }
    ['fAdults', 'fChildren', 'fInfants'].forEach(id => document.getElementById(id).addEventListener('input', updateModalTotal));

    document.getElementById('bookForm').addEventListener('submit', async (ev) => {
      ev.preventDefault();
      if (!activeTrip) return;
      const hotelVal = document.getElementById('fHotel').value.trim();
      const matchedH = HOTELS.find(h => h.name.toLowerCase() === hotelVal.toLowerCase());
      const btn = document.getElementById('btnSubmitBook');
      btn.disabled = true;
      try {
        const resp = await fetch('/handlers/seller_link_booking.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
          body: JSON.stringify({
            csrf_token: CSRF_TOKEN,
            seller: SELLER_SLUG,
            promo_token: PROMO_TOKEN,
            lang: <?= json_encode($lang) ?>,
            trip_code: activeTrip.id,
            guest_name: document.getElementById('fName').value.trim(),
            phone: document.getElementById('fPhone').value.trim(),
            trip_date: document.getElementById('fDate').value,
            hotel_name: hotelVal,
            room_no: document.getElementById('fRoom').value.trim(),
            pickup_time: matchedH ? matchedH.pickup : '08:00',
            adults: parseInt(document.getElementById('fAdults').value || '2', 10),
            children: parseInt(document.getElementById('fChildren').value || '0', 10),
            infants: parseInt(document.getElementById('fInfants').value || '0', 10),
            currency: cur,
            deposit_paid_now: parseFloat(document.getElementById('fDeposit').value || '0')
          })
        });
        const j = await resp.json();
        if (!j.ok) throw new Error(j.error || 'Booking failed');
        const box = document.getElementById('mSuccess');
        const waMsg = encodeURIComponent(`Hello Sea College! Confirmed booking ${j.voucher_no}: ${j.trip_name} on ${j.trip_date} (${j.adults} Adl, ${j.children} Ch, ${j.infants} Inf — Total €${j.total_eur}) — Ref: ${SELLER_SLUG}`);
        box.innerHTML = `✅ <strong>${IS_RTL ? 'تم تسجيل حجزك بنجاح!' : 'Booking Confirmed!'}</strong><br>Ref: <strong>${esc(j.voucher_no)}</strong> · Total: <strong>€${esc(j.total_eur)}</strong>${j.promo_applied ? ` <span style="color:#fcd34d">(Saved €${esc(j.discount_eur)})</span>` : ''} · Pickup: ~${esc(j.pickup_time)}<br><a class="btn btn-wa" style="margin-top:8px;width:100%" target="_blank" rel="noopener" href="https://wa.me/${esc(j.whatsapp_phone)}?text=${waMsg}">💬 ${IS_RTL ? 'إرسال التأكيد عبر واتساب' : 'Send Confirmation on WhatsApp'}</a>`;
        box.style.display = 'block';
        btn.style.display = 'none';
      } catch (err) {
        alert(err.message || 'Error');
      } finally {
        btn.disabled = false;
      }
    });

    function shareTrip(id) {
      const baseNoQs = SHARE_URL.split('?')[0];
      const u = baseNoQs + '/' + encodeURIComponent(id) + (PROMO_TOKEN ? '?promo=' + encodeURIComponent(PROMO_TOKEN) : '');
      if (navigator.share) {
        navigator.share({ title: document.title, url: u }).catch(() => {});
      } else {
        navigator.clipboard && navigator.clipboard.writeText(u);
        window.open('https://wa.me/?text=' + encodeURIComponent(u), '_blank', 'noopener');
      }
    }
    document.getElementById('btnSharePage').addEventListener('click', () => {
      if (navigator.share) {
        navigator.share({ title: document.title, url: SHARE_URL }).catch(() => {});
      } else {
        navigator.clipboard && navigator.clipboard.writeText(SHARE_URL);
        window.open('https://wa.me/?text=' + encodeURIComponent(SHARE_URL), '_blank', 'noopener');
      }
    });
    document.getElementById('curSelect').addEventListener('change', e => { cur = e.target.value; renderGrid(); updateModalTotal(); });
    document.getElementById('langSelect').addEventListener('change', e => {
      const lg = e.target.value;
      const qs = PROMO_TOKEN ? ('?promo=' + encodeURIComponent(PROMO_TOKEN)) : '';
      window.location.href = (lg === 'en' ? '' : '/' + lg) + '/s/' + encodeURIComponent(SELLER_SLUG) + (FOCUS_TRIP ? '/' + encodeURIComponent(FOCUS_TRIP) : '') + qs;
    });
    document.getElementById('searchInp').addEventListener('input', renderGrid);

    renderCategories();
    renderGrid();
    if (FOCUS_TRIP) {
      const found = CARDS.find(c => c.id === FOCUS_TRIP);
      if (found) openBook(found.id);
    }
  </script>
</body>
</html>
