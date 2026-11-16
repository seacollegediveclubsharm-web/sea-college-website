<?php
/**
 * Copy to config/secrets.php (git-ignored) and fill in. Any key here overrides the
 * default in site_config.php. Leave a value '' to keep that integration disabled.
 */
declare(strict_types=1);

return [
    // 'SITE_ENV' => 'production',          // switch on at launch (enables indexing)
    // 'SITE_URL' => 'https://www.seacollegediveclub.com',
    'GTM_ID' => '',
    'GA4_ID' => '',
    'GOOGLE_ADS_ID' => '',
    'GOOGLE_ADS_LEAD_LABEL' => '',
    'GSC_VERIFICATION' => '',
    'META_PIXEL_ID' => '',
    'META_CAPI_TOKEN' => '',
    'META_CAPI_TEST_CODE' => '',
    'TIKTOK_PIXEL_ID' => '',
    'LINKEDIN_PARTNER_ID' => '',
    'CRM_WEBHOOK_URL' => '',
    'LEAD_NOTIFY_EMAIL' => '',
    'SMTP_USER' => '',
    'SMTP_PASS' => '',
];
