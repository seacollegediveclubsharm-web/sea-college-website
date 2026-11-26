<?php
/**
 * Sea College Dive Club — Central site configuration.
 *
 * Every tracking ID, social URL, webhook and contact detail lives here ONLY.
 * Empty string = integration disabled (no script is printed).
 * Secrets (tokens, SMTP passwords) should be supplied as environment variables
 * on Hostinger (hPanel → Advanced → PHP Configuration / .htaccess SetEnv) rather
 * than committed to Git. Each constant reads env first, then the fallback.
 */
declare(strict_types=1);

/* Optional, git-ignored: config/secrets.php returning ['META_CAPI_TOKEN' => '...', ...].
 * Upload it with hPanel File Manager; see config/secrets.example.php. */
$GLOBALS['SC_SECRETS'] = [];
if (is_file(__DIR__ . '/secrets.php')) {
    try {
        $GLOBALS['SC_SECRETS'] = (array) (require __DIR__ . '/secrets.php');
    } catch (\Throwable $e) {
        error_log('Sea College secrets.php error: ' . $e->getMessage());
    }
}

if (!function_exists('sc_env')) {
    function sc_env(string $key, string $default = ''): string
    {
        $v = $GLOBALS['SC_SECRETS'][$key] ?? getenv($key);
        if ($v === false || $v === '') {
            $v = $_SERVER[$key] ?? $_ENV[$key] ?? $default;
        }
        return trim((string) $v);
    }
}

if (!function_exists('sc_secret')) {
    function sc_secret(string $key, string $default = ''): string
    {
        return sc_env($key, $default);
    }
}

/* ---------------------------------------------------------------
 * Site basics
 * ------------------------------------------------------------- */
define('SITE_NAME', 'Sea College Dive Club');
$sc_req_host = strtolower((string) preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '')));
$sc_is_staging_host = ($sc_req_host !== '') && (str_contains($sc_req_host, 'hostingersite.com') || in_array($sc_req_host, ['localhost', '127.0.0.1'], true));
define('SITE_ENV', sc_env('SITE_ENV', $sc_is_staging_host ? 'staging' : 'production')); // production | staging
define('SITE_URL', rtrim(sc_env('SITE_URL', 'https://seacollegediveclub.com'), '/'));
define('SITE_NOINDEX', SITE_ENV !== 'production'); // keep staging out of Google, index production automatically
define('DEFAULT_LANG', 'en');
define('SUPPORTED_LANGS', [
    'en' => 'English',
    'ru' => 'Русский',
    'it' => 'Italiano',
    'pl' => 'Polski',
    'de' => 'Deutsch',
    'fr' => 'Français',
    'ar' => 'العربية',
    'zh' => '中文',
]);
define('RTL_LANGS', ['ar']);
define('PRIMARY_CURRENCY', 'EUR');
// Indicative conversion rates for the currency switcher (display only; checkout locked in EUR).
define('CURRENCY_RATES', ['EUR' => 1.0, 'USD' => 1.08, 'GBP' => 0.85, 'PLN' => 4.30, 'AED' => 3.97, 'SAR' => 4.05, 'CNY' => 7.85, 'EGP' => 52.0]);
define('CURRENCY_SYMBOLS', ['EUR' => '€', 'USD' => '$', 'GBP' => '£', 'PLN' => 'zł', 'AED' => 'د.إ', 'SAR' => 'ر.س', 'CNY' => '¥', 'EGP' => 'E£']);
define('PRICE_VALIDITY_NOTE', 'Prices in EUR per person unless stated. Final price confirmed at booking.');

/* ---------------------------------------------------------------
 * Business / NAP (Name-Address-Phone) — keep identical to Google Business Profile
 * ------------------------------------------------------------- */
define('BUSINESS', [
    'legal_name'   => 'Sea College Dive Club',
    'street'       => 'Cyrene Sharm Hotel / Ivy Cyrene Island Aqua Park Resort, Al Montazah',
    'postal_code'  => '46619',
    'city'         => 'Sharm El Sheikh',
    'region'       => 'South Sinai',
    'country'      => 'EG',
    'lat'          => 27.9805263, // Verified GBP pin latitude
    'lng'          => 34.4239091, // Verified GBP pin longitude
    'phone'        => '+201114567886',
    'phone_display'=> '+201114567886',
    'phone_alt'    => [],
    'email'        => 'info@seacollegediveclub.com',
    'hours'        => 'Mo-Su 08:00-18:00',
    'hours_display'=> 'Mon–Sun · 08:00–18:00',
    'rating'       => 4.9,
    'review_count' => 3082,
    'padi_level'   => 'PADI 5 Star Dive Resort',
    'founded'      => '2019',
    'price_range'  => '€€',
]);

define('MAPS_URL', 'https://maps.app.goo.gl/JweBzniP2hS1VpgK7');
define('MAPS_EMBED_QUERY', 'Sea College Dive Club, Sharm El Sheikh');
define('GOOGLE_PLACE_ID', sc_env('GOOGLE_PLACE_ID', 'ChIJ75cBQp5LUxQRMjrNYQx93g0')); // GBP Place ID (0x14534b9e420197ef:0x0dde7d0c61cd3a32)
define('GBP_LOCATION_ID', sc_env('GBP_LOCATION_ID', '15687555981863554557'));       // Verified Google Business Profile Location ID
define('GBP_FID', sc_env('GBP_FID', '999373659456748082'));
define('GBP_ACCOUNT_ID', sc_secret('GBP_ACCOUNT_ID', ''));                          // Auto-discovered via GBP v4 accounts.list if empty
define('GBP_CLIENT_ID', sc_secret('GBP_CLIENT_ID', ''));                            // OAuth 2.0 Client ID (scope: business.manage)
define('GBP_CLIENT_SECRET', sc_secret('GBP_CLIENT_SECRET', ''));
define('GBP_REFRESH_TOKEN', sc_secret('GBP_REFRESH_TOKEN', ''));
define('GOOGLE_PLACES_API_KEY', sc_env('GOOGLE_PLACES_API_KEY', '')); // secret: set in config/secrets.php for live auto-sync
define('GOOGLE_REVIEWS_CACHE_TTL', 21600); // 6 hours server-side cache
define('GOOGLE_REVIEWS_URL', 'https://www.google.com/maps/place/sea+college+dive+club/@27.9805263,34.4239091,17z/data=!4m8!3m7!1s0x14534b9e420197ef:0xdde7d0c61cd3a32!8m2!3d27.9805263!4d34.4239091!9m1!1b1!16s%2Fg%2F11kjd4n05q');
define('GOOGLE_WRITE_REVIEW_URL', 'https://search.google.com/local/writereview?placeid=' . GOOGLE_PLACE_ID);
define('PADI_PROFILE_URL', 'https://www.padi.com/dive-center/egypt/sea-college-dive-club/');
define('CDWS_PROFILE_URL', 'https://www.diveoneclub.com/Listing/sea-college-dive-club/');

/* ---------------------------------------------------------------
 * Tripadvisor Official Listing & Review Sync (Location ID 26104236)
 * ------------------------------------------------------------- */
define('TRIPADVISOR_LOCATION_ID', sc_env('TRIPADVISOR_LOCATION_ID', '26104236'));
define('TRIPADVISOR_UNIQ_ID', sc_env('TRIPADVISOR_UNIQ_ID', '409'));
define('TRIPADVISOR_UNIQ_ID_AR', sc_env('TRIPADVISOR_UNIQ_ID_AR', '969'));
define('TRIPADVISOR_URL', 'https://www.tripadvisor.com/Attraction_Review-g297555-d26104236-Reviews-Sea_college_dive_club-Sharm_El_Sheikh_South_Sinai_Red_Sea_and_Sinai.html');
define('TRIPADVISOR_WRITE_REVIEW_URL', 'https://www.tripadvisor.com/UserReview-g297555-d26104236-Sea_college_dive_club-Sharm_El_Sheikh_South_Sinai_Red_Sea_and_Sinai.html');
define('TRIPADVISOR_OWNER_URL', 'https://www.tripadvisor.com/ManageListing-g297555-d26104236');
define('TRIPADVISOR_URL_AR', 'https://www.tripadvisor.com.eg/Attraction_Review-g297555-d26104236-Reviews-Sea_college_dive_club-Sharm_El_Sheikh_South_Sinai_Red_Sea_and_Sinai.html');
define('TRIPADVISOR_WRITE_REVIEW_URL_AR', 'https://www.tripadvisor.com.eg/UserReview-g297555-d26104236-Sea_college_dive_club-Sharm_El_Sheikh_South_Sinai_Red_Sea_and_Sinai.html');
define('TRIPADVISOR_API_KEY', sc_env('TRIPADVISOR_API_KEY', '')); // optional Content/Terra API key in config/secrets.php
define('TRIPADVISOR_CACHE_TTL', 21600); // 6 hours server-side cache

/* ---------------------------------------------------------------
 * Social profiles (empty = hidden)
 * ------------------------------------------------------------- */
define('SOCIAL', [
    'youtube'     => sc_env('SOCIAL_YOUTUBE', 'https://www.youtube.com/@seacollege.diveclub'),
    'instagram'   => 'https://www.instagram.com/seacollege.diveclub/',
    'facebook'    => 'https://www.facebook.com/seacollegediveclub',
    'tiktok'      => sc_env('SOCIAL_TIKTOK', 'https://www.tiktok.com/@seacollegediveclub'),
    'tripadvisor' => sc_env('SOCIAL_TRIPADVISOR', TRIPADVISOR_URL),
]);

/* ---------------------------------------------------------------
 * WhatsApp click-to-chat
 * ------------------------------------------------------------- */
define('WHATSAPP_NUMBER', '201114567886'); // digits only, intl format (+201114567886)

/* ---------------------------------------------------------------
 * Google ecosystem
 * ------------------------------------------------------------- */
define('GTM_ID', sc_env('GTM_ID', 'GTM-PBVQ56XC'));           // Google Tag / GTM container ID
define('GA4_ID', sc_env('GA4_ID', 'G-99528RZN9Z'));           // GA4 Web Stream Measurement ID
define('GA4_VIA_GTM', sc_env('GA4_VIA_GTM', '') === '1');     // set '1' only if firing GA4 inside GTM
define('GOOGLE_ADS_ID', sc_env('GOOGLE_ADS_ID', 'AW-16728866067')); // Google Ads account 365-126-5545
define('GOOGLE_ADS_LEAD_LABEL', sc_env('GOOGLE_ADS_LEAD_LABEL', 'LCPWCKD0vOIcEJP6-Kg-')); // Reservation Form Submitted
define('GOOGLE_ADS_WHATSAPP_LABEL', sc_env('GOOGLE_ADS_WHATSAPP_LABEL', 'rh4WCOXW4d8cEJP6-Kg-')); // WhatsApp Lead
define('GOOGLE_ADS_CALL_LABEL', sc_env('GOOGLE_ADS_CALL_LABEL', '8hA2CLrgqd4cEJP6-Kg-')); // Click to call
define('GOOGLE_ADS_VIA_GTM', sc_env('GOOGLE_ADS_VIA_GTM', '') === '1'); // set '1' only if firing Ads conversions inside GTM
define('GSC_VERIFICATION', sc_env('GSC_VERIFICATION', 'MUaOhb7KY5wwxWBDLVgRYIGRxrsU_cW2o36ihx1Kv1k')); // Search Console meta token
define('GTG_ENABLED', sc_env('GTG_ENABLED', '1') === '1');    // Google Tag Gateway (First-Party Mode via *.fps.goog)
define('GTG_GTM_PATH', '/s-tm');                              // First-party path for GTM-PBVQ56XC
define('GTG_ADS_PATH', '/s-aw');                              // First-party path for unified Google Tag (AW-16728866067 + G-99528RZN9Z)
define('GTG_GA4_PATH', '/s-ga');                              // First-party path for standalone GA4 (G-99528RZN9Z)

/* ---------------------------------------------------------------
 * Paid social pixels
 * ------------------------------------------------------------- */
define('META_PIXEL_ID', sc_env('META_PIXEL_ID', '2980406819004765'));
define('FB_DOMAIN_VERIFICATION', sc_env('FB_DOMAIN_VERIFICATION', 'hgespqg872v6rcmkb8zglk4jx9y61v')); // Meta Business Suite domain verification meta tag
define('META_BUSINESS_ID', sc_env('META_BUSINESS_ID', '660290192579947'));   // Meta Business Portfolio: seacollegediveclub
define('META_AD_ACCOUNT_ID', sc_env('META_AD_ACCOUNT_ID', 'act_1810425550247894')); // Meta Ad Account: Sea College Ad acc (EGP)
define('META_PAGE_ID', sc_env('META_PAGE_ID', '114089131683616'));           // Facebook Page: sea college dive club
define('META_CATALOG_ID', sc_env('META_CATALOG_ID', '555610010235756'));     // Meta Catalog: SEA COLLEGE TRIPS
define('META_CAPI_TOKEN', sc_env('META_CAPI_TOKEN', ''));     // secret: env only (System User token)
define('META_ACCESS_TOKEN', sc_env('META_ACCESS_TOKEN', META_CAPI_TOKEN)); // System User token for Meta Marketing & Catalog Graph API
define('META_CAPI_TEST_CODE', sc_env('META_CAPI_TEST_CODE', ''));
define('META_IG_ID', sc_env('META_IG_ID', '17841459801345654'));             // Instagram Business Account: @seacollege.diveclub
define('META_WABA_ID', sc_env('META_WABA_ID', '1466166818696930'));         // WhatsApp Business Account: Sea college dive club
define('META_WABA_PHONE_ID', sc_env('META_WABA_PHONE_ID', '1394410417097578')); // WABA Cloud Phone ID
define('META_WEBHOOK_VERIFY_TOKEN', sc_env('META_WEBHOOK_VERIFY_TOKEN', 'sc_lara_inbox_2026'));
define('META_APP_SECRET', sc_secret('META_APP_SECRET', ''));
define('META_GRAPH_VERSION', 'v21.0');
define('LARA_WHATSAPP_AUTOREPLY', sc_env('LARA_WHATSAPP_AUTOREPLY', '0') === '1'); // OFF by default: protects live WhatsApp Web team/vendor/captain chats
define('GEMINI_API_KEY', sc_secret('GEMINI_API_KEY', '')); // Optional Gemini 2.5 Flash key for natural contextual multi-turn concierge replies
define('TIKTOK_PIXEL_ID', sc_secret('TIKTOK_PIXEL_ID', ''));
define('MICROSOFT_UET_ID', sc_secret('MICROSOFT_UET_ID', ''));
define('LINKEDIN_PARTNER_ID', sc_env('LINKEDIN_PARTNER_ID', ''));

/* Feature flags (allow switching a pixel off without deleting its ID) */
define('ENABLE_META_PIXEL', true);
define('ENABLE_TIKTOK_PIXEL', true);
define('ENABLE_MICROSOFT_UET', true);
define('ENABLE_LINKEDIN_TAG', false);

/* ---------------------------------------------------------------
 * Consent
 * ------------------------------------------------------------- */
define('CONSENT_COOKIE', 'sc_consent');       // values: all | essential
define('CONSENT_COOKIE_DAYS', 180);

/* ---------------------------------------------------------------
 * Leads: email, SMTP, CRM webhook, storage
 * ------------------------------------------------------------- */
define('LEAD_NOTIFY_EMAIL', sc_env('LEAD_NOTIFY_EMAIL', 'info@seacollegediveclub.com'));
define('SMTP_HOST', sc_env('SMTP_HOST', 'smtp.hostinger.com'));
define('SMTP_PORT', (int) sc_env('SMTP_PORT', '465'));
define('SMTP_USER', sc_env('SMTP_USER', ''));
define('SMTP_PASS', sc_env('SMTP_PASS', ''));                // secret: env only
define('MAIL_FROM', sc_env('MAIL_FROM', SMTP_USER !== '' ? SMTP_USER : 'no-reply@seacollegediveclub.com'));
define('IMAP_HOST', sc_env('IMAP_HOST', 'imap.hostinger.com'));
define('IMAP_PORT', (int) sc_env('IMAP_PORT', '993'));
define('LARA_EMAIL_AUTOREPLY', sc_env('LARA_EMAIL_AUTOREPLY', '1') === '1');
define('OWNER_COPY_EMAIL', sc_env('OWNER_COPY_EMAIL', 'seacollegediveclubsharm@gmail.com'));
define('CRM_WEBHOOK_URL', sc_env('CRM_WEBHOOK_URL', 'https://script.google.com/macros/s/AKfycbwu3oBfPJ8394AYuoBTB5FjPO5lTmlJMz-jVxK8BaWJ1-YOX3sSrvFd-YBDQDgUFx3C/exec')); // Leads & OCT CRM Sheet webhook
define('LEADS_STORAGE_DIR', sc_env('LEADS_STORAGE_DIR', dirname(__DIR__) . '/storage'));
define('RATE_LIMIT_SECONDS', 20);                             // min seconds between submissions per session

/* ---------------------------------------------------------------
 * Attribution & Canonical 18-Seller Directory (Zero-DB Public Resolver)
 * ------------------------------------------------------------- */
define('UTM_KEYS', ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'gbraid', 'wbraid', 'fbclid', 'ttclid', 'msclkid', 'seller', 'rep']);
define('UTM_COOKIE_DAYS', 90);

define('SC_SELLERS_DIRECTORY', [
    'fady' => [
        'username' => 'fady.sales', 'referral_code' => 'fady', 'rep_scope' => 'FADY', 'branch_scope' => 'CYRENE',
        'name_en' => 'Fady', 'name_ar' => 'فادي',
        'default_comm_insheet_pct' => 0.10, 'default_comm_outside_pct' => 0.25,
        'aliases' => ['fady', 'fadysales', 'فادى', 'فادي'],
    ],
    'adel' => [
        'username' => 'adel.sales', 'referral_code' => 'adel', 'rep_scope' => 'ADEL', 'branch_scope' => 'DREAMS_FAYROUZ',
        'name_en' => 'Adel', 'name_ar' => 'عادل',
        'default_comm_insheet_pct' => 0.10, 'default_comm_outside_pct' => 0.25,
        'aliases' => ['adel', 'adelsales', 'عادل'],
    ],
    'moali' => [
        'username' => 'moali.sales', 'referral_code' => 'moali', 'rep_scope' => 'MO.ALI', 'branch_scope' => 'CYRENE',
        'name_en' => 'Mohamed Ali', 'name_ar' => 'محمد علي',
        'default_comm_insheet_pct' => 0.10, 'default_comm_outside_pct' => 0.25,
        'aliases' => ['moali', 'moalisales', 'mali', 'mohamedali', 'mohammedali', 'محمدعلى', 'محمدعلي'],
    ],
    'sqran' => [
        'username' => 'sqran.sales', 'referral_code' => 'sqran', 'rep_scope' => 'SQRAN', 'branch_scope' => 'CYRENE',
        'name_en' => 'Ahmed El-Sakran', 'name_ar' => 'أحمد السكران',
        'default_comm_insheet_pct' => 0.10, 'default_comm_outside_pct' => 0.25,
        'aliases' => ['sqran', 'sqransales', 'saqran', 'sakran', 'ssqran', 'elsakran', 'سكران', 'السكران'],
    ],
    'zola' => [
        'username' => 'zola.sales', 'referral_code' => 'zola', 'rep_scope' => 'ZOLA', 'branch_scope' => 'CYRENE',
        'name_en' => 'Zola', 'name_ar' => 'زولا',
        'default_comm_insheet_pct' => 0.10, 'default_comm_outside_pct' => 0.25,
        'aliases' => ['zola', 'zolasales', 'zonfol', 'زولا', 'زنفل'],
    ],
    'edy' => [
        'username' => 'edy.sales', 'referral_code' => 'edy', 'rep_scope' => 'EDY', 'branch_scope' => 'CYRENE',
        'name_en' => 'Edy', 'name_ar' => 'إيدي',
        'default_comm_insheet_pct' => 0.10, 'default_comm_outside_pct' => 0.25,
        'aliases' => ['edy', 'edysales', 'edward', 'ادي', 'إيدي', 'ادوارد', 'إدوارد'],
    ],
    'yassin' => [
        'username' => 'yassin.sales', 'referral_code' => 'yassin', 'rep_scope' => 'YASSIN', 'branch_scope' => 'CYRENE',
        'name_en' => 'Yassin', 'name_ar' => 'ياسين',
        'default_comm_insheet_pct' => 0.10, 'default_comm_outside_pct' => 0.25,
        'aliases' => ['yassin', 'yassinsales', 'yassen', 'yaseen', 'ياسين', 'يس'],
    ],
    'alishawky' => [
        'username' => 'alishawky.sales', 'referral_code' => 'alishawky', 'rep_scope' => 'ALI SHAWKY', 'branch_scope' => 'CYRENE',
        'name_en' => 'Ali Shawky', 'name_ar' => 'علي شوقي',
        'default_comm_insheet_pct' => 0.10, 'default_comm_outside_pct' => 0.25,
        'aliases' => ['alishawky', 'alishawkysales', 'علىشوقى', 'عليشوقي'],
    ],
    'ammar' => [
        'username' => 'ammar.sales', 'referral_code' => 'ammar', 'rep_scope' => 'AMMAR', 'branch_scope' => 'CYRENE',
        'name_en' => 'Ammar', 'name_ar' => 'عمار',
        'default_comm_insheet_pct' => 0.10, 'default_comm_outside_pct' => 0.25,
        'aliases' => ['ammar', 'ammarsales', 'amar', 'عمار'],
    ],
    'moabdo' => [
        'username' => 'moabdo.sales', 'referral_code' => 'moabdo', 'rep_scope' => 'MO.ABDO', 'branch_scope' => 'CYRENE',
        'name_en' => 'Mohamed Abdo', 'name_ar' => 'محمد عبده',
        'default_comm_insheet_pct' => 0.10, 'default_comm_outside_pct' => 0.25,
        'aliases' => ['moabdo', 'moabdosales', 'mabdo', 'abdo', 'mohamedabdo', 'محمدعبده', 'محمدعبدالرحمن'],
    ],
    'mando' => [
        'username' => 'mando.sales', 'referral_code' => 'mando', 'rep_scope' => 'MANDO', 'branch_scope' => 'DREAMS_FAYROUZ',
        'name_en' => 'Mando', 'name_ar' => 'ماندو',
        'default_comm_insheet_pct' => 0.10, 'default_comm_outside_pct' => 0.25,
        'aliases' => ['mando', 'mandosales', 'ماندو'],
    ],
    'chicago' => [
        'username' => 'chicago.sales', 'referral_code' => 'chicago', 'rep_scope' => 'CHICAGO', 'branch_scope' => 'DREAMS_FAYROUZ',
        'name_en' => 'Ali Chicago', 'name_ar' => 'علي شيكاغو',
        'default_comm_insheet_pct' => 0.10, 'default_comm_outside_pct' => 0.25,
        'aliases' => ['chicago', 'chicagosales', 'chacago', 'chikago', 'alichicago', 'alichacago', 'شيكاغو', 'شيكاجو'],
    ],
    'khairy' => [
        'username' => 'khairy.sales', 'referral_code' => 'khairy', 'rep_scope' => 'KHAIRY', 'branch_scope' => 'DREAMS_FAYROUZ',
        'name_en' => 'Khairy', 'name_ar' => 'خيري',
        'default_comm_insheet_pct' => 0.10, 'default_comm_outside_pct' => 0.25,
        'aliases' => ['khairy', 'khairysales', 'khiry', 'خيرى', 'خيري'],
    ],
    'eldeeb' => [
        'username' => 'eldeeb.sales', 'referral_code' => 'eldeeb', 'rep_scope' => 'ELDEEB', 'branch_scope' => 'DREAMS_FAYROUZ',
        'name_en' => 'Eldeeb', 'name_ar' => 'الديب',
        'default_comm_insheet_pct' => 0.10, 'default_comm_outside_pct' => 0.25,
        'aliases' => ['eldeeb', 'eldeebsales', 'eldib', 'deeb', 'الديب'],
    ],
    'fawzy' => [
        'username' => 'fawzy.sales', 'referral_code' => 'fawzy', 'rep_scope' => 'FAWZY', 'branch_scope' => 'DREAMS_FAYROUZ',
        'name_en' => 'Fawzy', 'name_ar' => 'فوزي',
        'default_comm_insheet_pct' => 0.10, 'default_comm_outside_pct' => 0.25,
        'aliases' => ['fawzy', 'fawzysales', 'fawzi', 'فوزى', 'فوزي'],
    ],
    'lemo' => [
        'username' => 'lemo.sales', 'referral_code' => 'lemo', 'rep_scope' => 'LEMO', 'branch_scope' => 'DREAMS_FAYROUZ',
        'name_en' => 'Lemo', 'name_ar' => 'ليمو',
        'default_comm_insheet_pct' => 0.10, 'default_comm_outside_pct' => 0.25,
        'aliases' => ['lemo', 'lemosales', 'limo', 'ليمو'],
    ],
    'shaldm' => [
        'username' => 'shaldm.sales', 'referral_code' => 'shaldm', 'rep_scope' => 'SHALDM', 'branch_scope' => 'CYRENE',
        'name_en' => 'Ahmed Shaldm', 'name_ar' => 'أحمد شلضم',
        'default_comm_insheet_pct' => 0.10, 'default_comm_outside_pct' => 0.25,
        'aliases' => ['shaldm', 'shaldmsales', 'shaldam', 'shaltam', 'شلضم', 'شلطم'],
    ],
    'amrhesham' => [
        'username' => 'amrhesham.sales', 'referral_code' => 'amrhesham', 'rep_scope' => 'AMR HESHAM', 'branch_scope' => 'JETTY',
        'name_en' => 'Amr Hesham', 'name_ar' => 'عمرو هشام',
        'default_comm_insheet_pct' => 0.10, 'default_comm_outside_pct' => 0.25,
        'aliases' => ['amrhesham', 'amrheshamsales', 'amr', 'عمروهشام'],
    ],
]);

if (!function_exists('sc_resolve_seller_public')) {
    function sc_resolve_seller_public(?string $raw): ?array
    {
        if ($raw === null) {
            return null;
        }
        $s = trim($raw);
        if ($s === '') {
            return null;
        }
        static $lookup = null;
        if ($lookup === null) {
            $lookup = [];
            foreach (SC_SELLERS_DIRECTORY as $code => $info) {
                $lookup[$code] = $info;
                $lookup[strtolower($info['username'])] = $info;
                $lookup[preg_replace('/[\s._\-]+/u', '', strtolower($info['rep_scope']))] = $info;
                foreach ($info['aliases'] as $al) {
                    $lookup[preg_replace('/[\s._\-]+/u', '', mb_strtolower($al, 'UTF-8'))] = $info;
                }
            }
        }
        $clean = preg_replace('/^(?:out|os|rep|seller)[\s._\-:]+/iu', '', $s) ?? $s;
        $clean = preg_replace('/\.sales$/i', '', trim($clean)) ?? trim($clean);
        $candidates = [$clean];
        if (preg_match('/[\-\/]/', $clean)) {
            foreach (preg_split('/[\-\/]+/', $clean) ?: [] as $part) {
                if (trim($part) !== '') {
                    $candidates[] = trim($part);
                }
            }
        }
        foreach ($candidates as $cand) {
            $key = preg_replace('/[\s._\-]+/u', '', mb_strtolower($cand, 'UTF-8')) ?? '';
            if ($key !== '' && isset($lookup[$key])) {
                return $lookup[$key];
            }
        }
        return null;
    }
}

if (!function_exists('sc_normalize_seller_token')) {
    function sc_normalize_seller_token(?string $raw): string
    {
        if ($raw === null) {
            return '';
        }
        $resolved = sc_resolve_seller_public($raw);
        if ($resolved !== null) {
            return (string) $resolved['referral_code'];
        }
        $fallback = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9._\-]/', '', $raw), '._-'));
        return substr($fallback, 0, 64);
    }
}

/* ---------------------------------------------------------------
 * Live Google Sheet EUR Price & Instant Discount Sync
 * Sheet: https://docs.google.com/spreadsheets/d/1qFlbTo8EXccEZOt2zgmAP7bUjMVLJrPbSd9NqaVty4g/edit
 * ------------------------------------------------------------- */
define('PRICE_SHEET_ID', sc_env('PRICE_SHEET_ID', '1qFlbTo8EXccEZOt2zgmAP7bUjMVLJrPbSd9NqaVty4g'));
define('PRICE_SHEET_CSV_URL', sc_env('PRICE_SHEET_CSV_URL', '')); // Optional Published-to-Web CSV URL for pull fallback
define('PRICE_SHEET_PULL_TTL', (int) sc_env('PRICE_SHEET_PULL_TTL', '5')); // 5s micro-cache when CSV pull is used
define('PRICE_SYNC_TOKEN_SHA256', sc_env('PRICE_SYNC_TOKEN_SHA256', 'ed545145ecdb0d9996a5202e977dfd49b1a70acb4d049e0131e7473fe9669a76'));

