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
    'gtg'         => defined('GTG_ENABLED') && GTG_ENABLED,
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
        'qbTotal' => t('qb_est_total'), 'qbPickupTag' => t('qb_pickup_tag'),
        'checkinWaHeader' => t('checkin_wa_header'), 'checkinCopied' => t('checkin_copied'),
        'adviseRecent' => t('advise_recent') !== 'advise_recent' ? t('advise_recent') : t('checkin_advise_recent'),
        'adviseCheck' => t('checkin_advise_check'),
        'adviseReview' => t('checkin_advise_review'),
        'medYesNote' => t('checkin_med_yes_note'), 'medAllNo' => t('checkin_med_all_no'),
    ],
];
$sc_client['i18n']['adviseRecent'] = t('checkin_advise_recent');
$sc_gtg_on = defined('GTG_ENABLED') && GTG_ENABLED;
$sc_gtm_primary = $sc_gtg_on ? (GTG_GTM_PATH . '/?id=' . rawurlencode(GTM_ID)) : ('https://www.googletagmanager.com/gtm.js?id=' . rawurlencode(GTM_ID));
$sc_gtm_fallback = $sc_gtg_on ? ('https://www.googletagmanager.com/gtm.js?id=' . rawurlencode(GTM_ID)) : '';
$sc_gtag_id = $sc_ads_direct ? GOOGLE_ADS_ID : GA4_ID;
$sc_gtag_primary = $sc_gtg_on ? (($sc_ads_direct ? GTG_ADS_PATH : GTG_GA4_PATH) . '/') : ('https://www.googletagmanager.com/gtag/js?id=' . rawurlencode($sc_gtag_id));
$sc_gtag_fallback = $sc_gtg_on ? ('https://www.googletagmanager.com/gtag/js?id=' . rawurlencode($sc_gtag_id)) : '';
$sc_json = json_encode($sc_client, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
?>
<?php if (GSC_VERIFICATION !== ''): ?>
<meta name="google-site-verification" content="<?= e(GSC_VERIFICATION) ?>">
<?php endif; ?>
<script>window.SC=<?= $sc_json ?>;window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}(function(w,d){var m=d.cookie.match(/(?:^|;\s*)<?= e(CONSENT_COOKIE) ?>=(all|essential)(?:;|$)/),c=m?m[1]:(w.SC.consent||'unset');w.SC.consent=c;d.documentElement.setAttribute('data-consent-state',c);if(c==='all'){gtag('consent','default',{ad_storage:'granted',ad_user_data:'granted',ad_personalization:'granted',analytics_storage:'granted',functionality_storage:'granted',security_storage:'granted'});}else if(c==='essential'){gtag('consent','default',{ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied',analytics_storage:'denied',functionality_storage:'granted',security_storage:'granted'});gtag('set','ads_data_redaction',true);}else{gtag('consent','default',{ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied',analytics_storage:'denied',region:['AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IS','IE','IT','LV','LI','LT','LU','MT','NL','NO','PL','PT','RO','SK','SI','ES','SE','GB','CH'],wait_for_update:500});gtag('consent','default',{ad_storage:'granted',ad_user_data:'granted',ad_personalization:'granted',analytics_storage:'granted',functionality_storage:'granted',security_storage:'granted'});}gtag('set','url_passthrough',true);gtag('js',new Date());<?php if ($sc_ga4_direct): ?>gtag('config','<?= e(GA4_ID) ?>',{language:w.SC.lang});<?php endif; ?><?php if ($sc_ads_direct): ?>gtag('config','<?= e(GOOGLE_ADS_ID) ?>',{allow_enhanced_conversions:true});<?php endif; ?>w.dataLayer.push({event:'sc_init',page_type:w.SC.page,site_language:w.SC.lang,consent_state:c});<?php if (ENABLE_META_PIXEL && META_PIXEL_ID !== ''): ?>!function(f,n){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[]}(w);fbq('consent',c==='all'?'grant':'revoke');fbq('init','<?= e(META_PIXEL_ID) ?>');fbq('track','PageView');<?php endif; ?>var done=false;function inject(u,fb){var s=d.createElement('script');s.async=true;s.src=u;if(fb){s.onerror=function(){s.onerror=null;inject(fb);};}d.head.appendChild(s);}w.scLoadTags=function(){if(done)return;done=true;<?php if (GTM_ID !== ''): ?>w.dataLayer.push({'gtm.start':new Date().getTime(),event:'gtm.js'});inject('<?= e($sc_gtm_primary) ?>'<?php if ($sc_gtm_fallback !== ''): ?>,'<?= e($sc_gtm_fallback) ?>'<?php endif; ?>);<?php endif; ?><?php if ($sc_ads_direct || $sc_ga4_direct): ?>inject('<?= e($sc_gtag_primary) ?>'<?php if ($sc_gtag_fallback !== ''): ?>,'<?= e($sc_gtag_fallback) ?>'<?php endif; ?>);<?php endif; ?><?php if (ENABLE_META_PIXEL && META_PIXEL_ID !== ''): ?>if(w.SC&&w.SC.consent==='all'&&!w._scFbLoaded){w._scFbLoaded=true;inject('https://connect.facebook.net/en_US/fbevents.js');}<?php endif; ?>if(w.SCConsent&&w.SC&&w.SC.consent==='all'&&w.SCConsent.loadMarketing){w.SCConsent.loadMarketing();}};var evs=['pointerdown','touchstart','mousemove','keydown','scroll','focusin'];function trig(){w._scUserInteracted=true;evs.forEach(function(e){w.removeEventListener(e,trig,{passive:true});});w.scLoadTags();w.dispatchEvent(new Event('sc:interact'));}evs.forEach(function(e){w.addEventListener(e,trig,{once:true,passive:true});});if(w.SC.page==='thanks'||/[?&](gclid|gbraid|wbraid|fbclid|ttclid|msclkid|utm_|gtm_debug)/i.test(location.search)){if(d.readyState==='complete'){setTimeout(w.scLoadTags,40);}else{w.addEventListener('load',function(){setTimeout(w.scLoadTags,40);},{once:true});}}})(window,document);</script>

