/* Sea College — consent manager.
 * Stores the visitor's choice (sc_consent = all | essential), updates Google Consent Mode,
 * and injects Meta / TikTok / LinkedIn tags ONLY after "Accept all" and only if their IDs
 * are configured in config/site_config.php (exposed via window.SC). */
(function () {
  'use strict';
  var SC = window.SC || {};
  var loaded = false;

  function setCookie(value) {
    var days = SC.consentDays || 180;
    var secure = location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = (SC.consentCookie || 'sc_consent') + '=' + value + '; Max-Age=' + days * 86400 + '; Path=/; SameSite=Lax' + secure;
  }

  function isCatalogId(id) {
    var s = String(id || '');
    return s !== '' && s !== 'dive-extra' && s !== 'padi-cert-fee' && !/^(eq|fee|ins)-/.test(s);
  }
  SC.isCatalogId = isCatalogId;

  function googleUpdate(granted) {
    if (typeof window.gtag !== 'function') { return; }
    var v = granted ? 'granted' : 'denied';
    window.gtag('consent', 'update', { ad_storage: v, ad_user_data: v, ad_personalization: v, analytics_storage: v });
  }

  function uetUpdate(granted) {
    if (!SC.uetId && !window.uetq) { return; }
    window.uetq = window.uetq || [];
    window.uetq.push('consent', 'update', { ad_storage: granted ? 'granted' : 'denied' });
  }

  function loadMeta(id) {
    var ud = (window.SC_CONVERSION && window.SC_CONVERSION.user_data) || {};
    var am = {};
    if (ud.sha256_email_address) { am.em = ud.sha256_email_address; }
    if (ud.sha256_phone_number) { am.ph = ud.sha256_phone_number; }
    if (!window.fbq) {
      /* eslint-disable */
      !function(f,n){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[]}(window);
      /* eslint-enable */
      window.fbq('init', id);
      window.fbq('track', 'PageView');
    }
    window.fbq('consent', 'grant');
    if (am.em || am.ph) { window.fbq('init', id, am); }
    if (!window._scFbLoaded) {
      window._scFbLoaded = true;
      var s = document.createElement('script');
      s.async = true;
      s.src = 'https://connect.facebook.net/en_US/fbevents.js';
      document.head.appendChild(s);
    }
  }

  function loadTikTok(id) {
    if (!id || window._scTtLoaded) { return; }
    window._scTtLoaded = true;
    /* eslint-disable */
    !function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie","holdConsent","revokeConsent","grantConsent"];ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};ttq.load=function(e,n){var r="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{};ttq._i[e]=[];ttq._i[e]._u=r;ttq._t=ttq._t||{};ttq._t[e]=+new Date;ttq._o=ttq._o||{};ttq._o[e]=n||{};var o=d.createElement("script");o.type="text/javascript";o.async=!0;o.src=r+"?sdkid="+e+"&lib="+t;var a=d.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};}(window,document,'ttq');
    /* eslint-enable */
    window.ttq.load(id);
    if (typeof window.ttq.grantConsent === 'function') { window.ttq.grantConsent(); }
    var ud = (window.SC_CONVERSION && window.SC_CONVERSION.user_data) || {};
    var ident = {};
    if (ud.sha256_email_address) { ident.email = ud.sha256_email_address; }
    if (ud.sha256_phone_number) { ident.phone_number = ud.sha256_phone_number; }
    if (ident.email || ident.phone_number) { window.ttq.identify(ident); }
    window.ttq.page();
  }

  function loadMicrosoftUet(id) {
    if (!id || window._scUetLoaded) { return; }
    window._scUetLoaded = true;
    window.uetq = window.uetq || [];
    window.uetq.push('consent', 'update', { ad_storage: 'granted' });
    var o = { ti: String(id), enableAutoSpaTracking: false };
    o.q = window.uetq;
    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://bat.bing.com/bat.js';
    s.onload = s.onreadystatechange = function () {
      var st = this.readyState;
      if (st && st !== 'loaded' && st !== 'complete') { return; }
      try {
        if (typeof window.UET === 'function') {
          window.uetq = new window.UET(o);
          window.uetq.push('pageLoad');
        }
      } catch (err) { /* ignore UET init errors */ }
      s.onload = s.onreadystatechange = null;
    };
    document.head.appendChild(s);
  }

  function loadLinkedIn(id) {
    window._linkedin_partner_id = id;
    window._linkedin_data_partner_ids = window._linkedin_data_partner_ids || [];
    window._linkedin_data_partner_ids.push(id);
    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://snap.licdn.com/li.lms-analytics/insight.min.js';
    document.head.appendChild(s);
  }

  /* Fire Meta Lead / TikTok SubmitForm / Microsoft UET Lead on the thank-you page (same event_id as CAPI → de-duplicated). */
  function fireConversion() {
    var c = window.SC_CONVERSION;
    if (!c) { return; }
    var ids = Array.isArray(c.items) ? c.items.map(function (x) { return x && x.item_id; }).filter(isCatalogId) : [];
    var fbData = { value: c.value, currency: c.currency };
    if (ids.length) { fbData.content_ids = ids; fbData.content_type = 'product'; }
    if (window.fbq) { window.fbq('track', 'Lead', fbData, { eventID: c.event_id }); }
    if (window.ttq) { window.ttq.track('SubmitForm', { value: c.value, currency: c.currency }, { event_id: c.event_id }); }
    if (SC.uetId && window.uetq) {
      window.uetq.push('event', 'submit_lead_form', { event_category: 'lead', event_label: c.form || 'booking', revenue_value: c.value, currency: c.currency || 'EUR' });
    }
  }

  function fireCrossChannelSignals() {
    var qs = location.search || '';
    if (!qs) { return; }
    var isGoogleClick = /[?&](gclid|gbraid|wbraid)=/i.test(qs) || /[?&]utm_source=google\b/i.test(qs);
    var isMetaClick = /[?&]fbclid=/i.test(qs) || /[?&]utm_source=(meta|facebook|fb|instagram|ig)\b/i.test(qs);
    var it = SC.item || {};
    if (isGoogleClick && window.fbq) {
      var gPayload = { traffic_channel: 'google_ads', page_type: SC.page || 'page', lang: SC.lang || 'en' };
      if (it.id && isCatalogId(it.id)) { gPayload.content_ids = [it.id]; gPayload.content_type = 'product'; }
      if (it.price > 0) { gPayload.value = it.price; gPayload.currency = 'EUR'; }
      window.fbq('trackCustom', 'GoogleAdsVisitor', gPayload);
    }
    if (isMetaClick && typeof window.gtag === 'function') {
      window.gtag('event', 'meta_ad_landing', {
        traffic_channel: 'meta_ads',
        page_type: SC.page || 'page',
        site_language: SC.lang || 'en',
        item_id: it.id || ''
      });
    }
  }

  function loadMarketing() {
    if (loaded) { return; }
    loaded = true;
    try {
      if (SC.metaPixel) { loadMeta(SC.metaPixel); }
      if (SC.tiktokPixel) { loadTikTok(SC.tiktokPixel); }
      if (SC.uetId) { loadMicrosoftUet(SC.uetId); }
      if (SC.linkedin) { loadLinkedIn(SC.linkedin); }
      fireCrossChannelSignals();
      fireConversion();
      document.dispatchEvent(new CustomEvent('sc:marketing_loaded'));
    } catch (err) { /* never break the page because of a tag */ }
  }

  function choose(value) {
    var isAll = (value === 'all');
    SC.consent = value;
    SC.metaGranted = isAll;
    document.documentElement.setAttribute('data-consent-state', value);
    setCookie(value);
    googleUpdate(isAll);
    uetUpdate(isAll);
    if (window.fbq) { window.fbq('consent', isAll ? 'grant' : 'revoke'); }
    if (window.ttq && typeof window.ttq[isAll ? 'grantConsent' : 'revokeConsent'] === 'function') {
      window.ttq[isAll ? 'grantConsent' : 'revokeConsent']();
    }
    (window.dataLayer = window.dataLayer || []).push({ event: 'consent_update', consent_state: value });
    if (window.scLoadTags) { window.scLoadTags(); }
    if (isAll) { loadMarketing(); }
    try { document.dispatchEvent(new CustomEvent('sc:consent', { detail: value })); } catch (err2) { /* ignore */ }
    var box = document.getElementById('consent');
    if (box) { box.hidden = true; }
  }

  window.SCConsent = {
    granted: function () { return SC.consent === 'all' || !!SC.metaGranted; },
    choose: choose,
    loadMarketing: loadMarketing,
    isCatalogId: isCatalogId
  };

  if (SC.consent !== 'unset') {
    var initBox = document.getElementById('consent');
    if (initBox) { initBox.hidden = true; }
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-consent]');
    if (btn) { choose(btn.getAttribute('data-consent')); return; }
    if (e.target.closest('[data-consent-open]')) {
      document.documentElement.removeAttribute('data-consent-auto');
      document.documentElement.setAttribute('data-consent-state', 'open');
      var box = document.getElementById('consent');
      if (box) { box.hidden = false; var b = box.querySelector('button'); if (b) { b.focus(); } }
    }
  });

  if ((SC.consent === 'all' || SC.metaGranted) && (window._scUserInteracted || SC.page === 'thanks')) { loadMarketing(); }
})();
