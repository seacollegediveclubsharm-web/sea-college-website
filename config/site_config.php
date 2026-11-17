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
$GLOBALS['SC_SECRETS'] = is_file(__DIR__ . '/secrets.php') ? (array) (require __DIR__ . '/secrets.php') : [];

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

/* ---------------------------------------------------------------
 * Site basics
 * ------------------------------------------------------------- */
define('SITE_NAME', 'Sea College Dive Club');
// Staging URL — switch to https://seacollegediveclub.com at launch.
define('SITE_URL', rtrim(sc_env('SITE_URL', 'https://papayawhip-pheasant-312330.hostingersite.com'), '/'));
define('SITE_ENV', sc_env('SITE_ENV', 'staging')); // staging | production
define('SITE_NOINDEX', SITE_ENV !== 'production'); // keep staging out of Google
define('DEFAULT_LANG', 'en');
define('SUPPORTED_LANGS', ['en' => 'English', 'ar' => 'العربية']);
define('RTL_LANGS', ['ar']);
define('PRIMARY_CURRENCY', 'EUR');
// Indicative conversion rates for the currency switcher (display only).
define('CURRENCY_RATES', ['EUR' => 1.0, 'USD' => 1.08, 'GBP' => 0.85, 'EGP' => 52.0]);
define('CURRENCY_SYMBOLS', ['EUR' => '€', 'USD' => '$', 'GBP' => '£', 'EGP' => 'E£']);
define('PRICE_VALIDITY_NOTE', 'Prices in EUR per person unless stated. Final price confirmed at booking.');

/* ---------------------------------------------------------------
 * Business / NAP (Name-Address-Phone) — keep identical to Google Business Profile
 * ------------------------------------------------------------- */
define('BUSINESS', [
    'legal_name'   => 'Sea College Dive Club',
    'street'       => 'Cyrene Sharm Hotel / Ivy Cyrene Island Aqua Park Resort, Al Montazah',
    'city'         => 'Sharm El Sheikh',
    'region'       => 'South Sinai',
    'country'      => 'EG',
    'lat'          => 27.9805263, // Verified GBP pin latitude
    'lng'          => 34.4239091, // Verified GBP pin longitude
    'phone'        => '+201000344544',
    'phone_display'=> '+20 100 034 4544',
    'phone_alt'    => ['+201114567886', '+201006145500'],
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
define('GOOGLE_PLACE_ID', sc_env('GOOGLE_PLACE_ID', 'ChIJ75cBQp5LUxQRMapTYQ593g0')); // GBP Place ID (0x14534b9e420197ef:0xdde7d0c61cd3a32)
define('GOOGLE_PLACES_API_KEY', sc_env('GOOGLE_PLACES_API_KEY', '')); // secret: set in config/secrets.php for live auto-sync
define('GOOGLE_REVIEWS_CACHE_TTL', 21600); // 6 hours server-side cache
define('GOOGLE_REVIEWS_URL', 'https://search.google.com/local/reviews?placeid=' . GOOGLE_PLACE_ID);
define('GOOGLE_WRITE_REVIEW_URL', 'https://search.google.com/local/writereview?placeid=' . GOOGLE_PLACE_ID);
define('PADI_PROFILE_URL', 'https://www.padi.com/dive-center/egypt/sea-college-dive-club/');

/* ---------------------------------------------------------------
 * Social profiles (empty = hidden)
 * ------------------------------------------------------------- */
define('SOCIAL', [
    'facebook'  => 'https://www.facebook.com/seacollegediveclub',
    'instagram' => 'https://www.instagram.com/seacollege.diveclub/',
    'tiktok'    => sc_env('SOCIAL_TIKTOK', ''),
    'youtube'   => sc_env('SOCIAL_YOUTUBE', ''),
    'tripadvisor' => sc_env('SOCIAL_TRIPADVISOR', ''),
]);

/* ---------------------------------------------------------------
 * WhatsApp click-to-chat
 * ------------------------------------------------------------- */
define('WHATSAPP_NUMBER', sc_env('WHATSAPP_NUMBER', '201000344544')); // digits only, intl format

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

/* ---------------------------------------------------------------
 * Paid social pixels
 * ------------------------------------------------------------- */
define('META_PIXEL_ID', sc_env('META_PIXEL_ID', '2622478794929488'));
define('META_CAPI_TOKEN', sc_env('META_CAPI_TOKEN', ''));     // secret: env only
define('META_CAPI_TEST_CODE', sc_env('META_CAPI_TEST_CODE', ''));
define('META_GRAPH_VERSION', 'v19.0');
define('TIKTOK_PIXEL_ID', sc_env('TIKTOK_PIXEL_ID', ''));
define('LINKEDIN_PARTNER_ID', sc_env('LINKEDIN_PARTNER_ID', ''));

/* Feature flags (allow switching a pixel off without deleting its ID) */
define('ENABLE_META_PIXEL', true);
define('ENABLE_TIKTOK_PIXEL', true);
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
define('MAIL_FROM', sc_env('MAIL_FROM', 'no-reply@seacollegediveclub.com'));
define('SMTP_HOST', sc_env('SMTP_HOST', 'smtp.hostinger.com'));
define('SMTP_PORT', (int) sc_env('SMTP_PORT', '465'));
define('SMTP_USER', sc_env('SMTP_USER', ''));
define('SMTP_PASS', sc_env('SMTP_PASS', ''));                // secret: env only
define('CRM_WEBHOOK_URL', sc_env('CRM_WEBHOOK_URL', ''));     // Zapier / Make / Sheets / CRM endpoint
define('LEADS_STORAGE_DIR', dirname(__DIR__) . '/storage');
define('RATE_LIMIT_SECONDS', 20);                             // min seconds between submissions per session

/* ---------------------------------------------------------------
 * Attribution
 * ------------------------------------------------------------- */
define('UTM_KEYS', ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'gbraid', 'wbraid', 'fbclid', 'ttclid', 'msclkid']);
define('UTM_COOKIE_DAYS', 90);
