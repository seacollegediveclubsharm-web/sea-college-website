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
    'lat'          => null, // TODO owner: exact GBP pin latitude
    'lng'          => null, // TODO owner: exact GBP pin longitude
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
define('GOOGLE_REVIEWS_URL', 'https://www.google.com/search?q=sea+college+dive+club');
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
define('GTM_ID', sc_env('GTM_ID', ''));                       // e.g. GTM-XXXXXXX
define('GA4_ID', sc_env('GA4_ID', ''));                       // e.g. G-XXXXXXXXXX (loaded directly only if GTM_ID empty)
define('GOOGLE_ADS_ID', sc_env('GOOGLE_ADS_ID', ''));         // e.g. AW-XXXXXXXXX
define('GOOGLE_ADS_LEAD_LABEL', sc_env('GOOGLE_ADS_LEAD_LABEL', '')); // conversion label for Lead
define('GSC_VERIFICATION', sc_env('GSC_VERIFICATION', ''));   // Search Console meta token

/* ---------------------------------------------------------------
 * Paid social pixels
 * ------------------------------------------------------------- */
define('META_PIXEL_ID', sc_env('META_PIXEL_ID', ''));
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
