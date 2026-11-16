<?php
/**
 * Tracking (head). Prints NOTHING for integrations whose ID is empty in site_config.php.
 * - Google Consent Mode v2 defaults to "denied" until the visitor accepts.
 * - GTM (preferred) or direct gtag.js for GA4 / Google Ads.
 * - Meta / TikTok / LinkedIn are NOT printed here: assets/js/consent.js injects them
 *   only after the visitor clicks "Accept all".
 */
declare(strict_types=1);

$sc_consent = consent_state();
$sc_granted = $sc_consent === 'all';

$sc_client = [
    'lang'        => LANG,
    'page'        => $page['id'] ?? '',
    'consent'     => $sc_consent,
    'consentCookie' => CONSENT_COOKIE,
    'consentDays' => CONSENT_COOKIE_DAYS,
    'ga4'         => GA4_ID,
    'adsId'       => GOOGLE_ADS_ID,
    'adsLeadLabel'=> GOOGLE_ADS_LEAD_LABEL,
    'metaPixel'   => (ENABLE_META_PIXEL ? META_PIXEL_ID : ''),
    'tiktokPixel' => (ENABLE_TIKTOK_PIXEL ? TIKTOK_PIXEL_ID : ''),
    'linkedin'    => (ENABLE_LINKEDIN_TAG ? LINKEDIN_PARTNER_ID : ''),
    'whatsapp'    => preg_replace('/\D/', '', WHATSAPP_NUMBER),
    'currencyRates'   => CURRENCY_RATES,
    'currencySymbols' => CURRENCY_SYMBOLS,
    'i18n' => [
        'added' => t('added'), 'add' => t('add_to_booking'), 'empty' => t('cart_empty'),
        'remove' => t('remove'), 'onRequest' => t('price_on_request'),
        'waGreeting' => t('wa_greeting'), 'interest' => t('form_interest'),
        'people' => t('form_people'), 'date' => t('form_date'), 'hotel' => t('form_hotel'),
        'name' => t('form_name'), 'level' => t('form_level'),
    ],
];
$sc_json = json_encode($sc_client, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
?>
<?php if (GSC_VERIFICATION !== ''): ?>
<meta name="google-site-verification" content="<?= e(GSC_VERIFICATION) ?>">
<?php endif; ?>
<script>
window.SC = <?= $sc_json ?>;
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('consent', 'default', {
  ad_storage: '<?= $sc_granted ? 'granted' : 'denied' ?>',
  ad_user_data: '<?= $sc_granted ? 'granted' : 'denied' ?>',
  ad_personalization: '<?= $sc_granted ? 'granted' : 'denied' ?>',
  analytics_storage: '<?= $sc_granted ? 'granted' : 'denied' ?>',
  functionality_storage: 'granted',
  security_storage: 'granted',
  wait_for_update: 500
});
gtag('set', 'ads_data_redaction', true);
dataLayer.push({event: 'sc_init', page_type: SC.page, site_language: SC.lang, consent_state: SC.consent});
</script>
<?php if (GTM_ID !== ''): ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= e(GTM_ID) ?>');</script>
<?php elseif (GA4_ID !== '' || GOOGLE_ADS_ID !== ''): ?>
<!-- Google tag (gtag.js) — used only when no GTM container is configured -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e(GA4_ID !== '' ? GA4_ID : GOOGLE_ADS_ID) ?>"></script>
<script>
gtag('js', new Date());
<?php if (GA4_ID !== ''): ?>gtag('config', '<?= e(GA4_ID) ?>', {language: SC.lang});<?php endif; ?>
<?php if (GOOGLE_ADS_ID !== ''): ?>gtag('config', '<?= e(GOOGLE_ADS_ID) ?>', {allow_enhanced_conversions: true});<?php endif; ?>
</script>
<?php endif; ?>
