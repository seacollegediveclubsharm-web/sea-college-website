<?php
/**
 * Sea College Dive Club — Production Secrets Template (`config/secrets.example.php`).
 *
 * Usage on Hostinger (hPanel):
 *   1. Copy this file to `config/secrets.php` (git-ignored) via hPanel File Manager or SSH.
 *   2. Fill in the live tokens/passwords below. Leave any unused key as `''`.
 *   3. Run `php docs/verify_integrations.php` via SSH to verify live connectivity.
 */
declare(strict_types=1);

return [
    // 1. Environment ('production' on seacollegediveclub.com, 'staging' on preview hosts)
    'SITE_ENV' => 'production',
    'SITE_URL' => 'https://seacollegediveclub.com',

    // 2. Google Business Profile (Places API New v1 — live 6h auto-sync of 4.9★ & 3,082+ reviews)
    'GOOGLE_PLACES_API_KEY' => '',

    // 3. Tripadvisor Content API (optional — falls back to verified #26104236 seed + official widget)
    'TRIPADVISOR_API_KEY' => '',

    // 4. Meta Business Suite, Marketing API & Server-Side CAPI (Business 660290192579947 · Catalog 555610010235756 · Pixel 2980406819004765)
    'META_CAPI_TOKEN'     => '', // Meta System User Access Token (powers both Server-Side CAPI & Hossam's Graph API sync)
    'META_ACCESS_TOKEN'   => '', // Optional separate Marketing/Catalog API token (defaults to META_CAPI_TOKEN if empty)
    'META_CAPI_TEST_CODE' => '',

    // 5. Hostinger SMTPS (smtp.hostinger.com:465 implicit TLS — falls back to PHP mail() if empty)
    'SMTP_HOST' => 'smtp.hostinger.com',
    'SMTP_PORT' => '465',
    'SMTP_USER' => 'info@seacollegediveclub.com',
    'SMTP_PASS' => '',
    'MAIL_FROM' => 'info@seacollegediveclub.com',

    // 6. Google Sheets Leads & Google Ads OCT CRM Webhook (Sheet 1ItvQGWnrHwnSbyGDoGNtV2CkVwNJXIyU_aaIFxIOGbc)
    'CRM_WEBHOOK_URL' => 'https://script.google.com/macros/s/AKfycbwu3oBfPJ8394AYuoBTB5FjPO5lTmlJMz-jVxK8BaWJ1-YOX3sSrvFd-YBDQDgUFx3C/exec',
];
