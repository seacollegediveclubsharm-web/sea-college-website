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

$sc_ads_direct = (!GOOGLE_ADS_VIA_GTM && GOOGLE_ADS_ID !== '');
$sc_ga4_direct = (!GA4_VIA_GTM && GA4_ID !== '');

$sc_client = [
    'lang'        => LANG,
    'page'        => $page['id'] ?? '',
    'consent'     => $sc_consent,
    'consentCookie' => CONSENT_COOKIE,
    'consentDays' => CONSENT_COOKIE_DAYS,
    'ga4'         => GA4_ID,
    'ga4Direct'   => $sc_ga4_direct,
    'adsId'       => GOOGLE_ADS_ID,
    'adsDirect'   => $sc_ads_direct,
    'adsLeadLabel'=> GOOGLE_ADS_LEAD_LABEL,
    'adsWhatsappLabel' => GOOGLE_ADS_WHATSAPP_LABEL,
    'adsCallLabel'=> GOOGLE_ADS_CALL_LABEL,
    'metaPixel'   => (ENABLE_META_PIXEL ? META_PIXEL_ID : ''),
    'tiktokPixel' => (ENABLE_TIKTOK_PIXEL ? TIKTOK_PIXEL_ID : ''),
    'linkedin'    => (ENABLE_LINKEDIN_TAG ? LINKEDIN_PARTNER_ID : ''),
    'whatsapp'    => preg_replace('/\D/', '', WHATSAPP_NUMBER),
    'currencyRates'   => CURRENCY_RATES,
    'currencySymbols' => CURRENCY_SYMBOLS,
    'item' => $page['item_track'] ?? null,
    'i18n' => [
        'added' => t('added'), 'add' => t('add_to_booking'), 'empty' => t('cart_empty'),
        'remove' => t('remove'), 'onRequest' => t('price_on_request'),
        'waGreeting' => t('wa_greeting'), 'interest' => t('form_interest'),
        'people' => t('form_people'), 'date' => t('form_date'), 'hotel' => t('form_hotel'),
        'name' => t('form_name'), 'level' => t('form_level'),
        'minicartItems' => t('minicart_items'),
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
<?php if ($sc_consent === 'all'): ?>
gtag('consent', 'default', {
  ad_storage: 'granted',
  ad_user_data: 'granted',
  ad_personalization: 'granted',
  analytics_storage: 'granted',
  functionality_storage: 'granted',
  security_storage: 'granted'
});
<?php elseif ($sc_consent === 'essential'): ?>
gtag('consent', 'default', {
  ad_storage: 'denied',
  ad_user_data: 'denied',
  ad_personalization: 'denied',
  analytics_storage: 'denied',
  functionality_storage: 'granted',
  security_storage: 'granted'
});
gtag('set', 'ads_data_redaction', true);
<?php else: ?>
gtag('consent', 'default', {
  ad_storage: 'denied',
  ad_user_data: 'denied',
  ad_personalization: 'denied',
  analytics_storage: 'denied',
  region: ['AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IS','IE','IT','LV','LI','LT','LU','MT','NL','NO','PL','PT','RO','SK','SI','ES','SE','GB','CH'],
  wait_for_update: 500
});
gtag('consent', 'default', {
  ad_storage: 'granted',
  ad_user_data: 'granted',
  ad_personalization: 'granted',
  analytics_storage: 'granted',
  functionality_storage: 'granted',
  security_storage: 'granted'
});
<?php endif; ?>
gtag('set', 'url_passthrough', true);
gtag('js', new Date());
dataLayer.push({event: 'sc_init', page_type: SC.page, site_language: SC.lang, consent_state: SC.consent});
</script>
<?php if (GTM_ID !== ''): ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= e(GTM_ID) ?>');</script>
<?php endif; ?>
<?php if ($sc_ads_direct || $sc_ga4_direct): ?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($sc_ads_direct ? GOOGLE_ADS_ID : GA4_ID) ?>"></script>
<script>
<?php if ($sc_ga4_direct): ?>gtag('config', '<?= e(GA4_ID) ?>', {language: SC.lang});<?php endif; ?>
<?php if ($sc_ads_direct): ?>gtag('config', '<?= e(GOOGLE_ADS_ID) ?>', {allow_enhanced_conversions: true});<?php endif; ?>
</script>
<?php endif; ?>
<?php if (ENABLE_META_PIXEL && META_PIXEL_ID !== ''): ?>
<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('consent', '<?= $sc_granted ? 'grant' : 'revoke' ?>');
fbq('init', '<?= e(META_PIXEL_ID) ?>');
fbq('track', 'PageView');
</script>
<!-- End Meta Pixel Code -->
<?php endif; ?>
