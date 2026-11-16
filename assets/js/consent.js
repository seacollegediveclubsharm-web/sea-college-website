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

  function googleUpdate(granted) {
    if (typeof window.gtag !== 'function') { return; }
    var v = granted ? 'granted' : 'denied';
    window.gtag('consent', 'update', { ad_storage: v, ad_user_data: v, ad_personalization: v, analytics_storage: v });
  }

  function loadMeta(id) {
    /* eslint-disable */
    !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
    /* eslint-enable */
    window.fbq('init', id);
    window.fbq('track', 'PageView');
  }

  function loadTikTok(id) {
    /* eslint-disable */
    !function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie","holdConsent","revokeConsent","grantConsent"];ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};ttq.load=function(e,n){var r="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{};ttq._i[e]=[];ttq._i[e]._u=r;ttq._t=ttq._t||{};ttq._t[e]=+new Date;ttq._o=ttq._o||{};ttq._o[e]=n||{};var o=d.createElement("script");o.type="text/javascript";o.async=!0;o.src=r+"?sdkid="+e+"&lib="+t;var a=d.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};}(window,document,'ttq');
    /* eslint-enable */
    window.ttq.load(id);
    window.ttq.page();
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

  /* Fire Meta Lead / TikTok SubmitForm on the thank-you page (same event_id as CAPI → de-duplicated). */
  function fireConversion() {
    var c = window.SC_CONVERSION;
    if (!c) { return; }
    if (window.fbq) { window.fbq('track', 'Lead', { value: c.value, currency: c.currency }, { eventID: c.event_id }); }
    if (window.ttq) { window.ttq.track('SubmitForm', { value: c.value, currency: c.currency }, { event_id: c.event_id }); }
  }

  function loadMarketing() {
    if (loaded) { return; }
    loaded = true;
    try {
      if (SC.metaPixel) { loadMeta(SC.metaPixel); }
      if (SC.tiktokPixel) { loadTikTok(SC.tiktokPixel); }
      if (SC.linkedin) { loadLinkedIn(SC.linkedin); }
      fireConversion();
    } catch (err) { /* never break the page because of a tag */ }
  }

  function choose(value) {
    SC.consent = value;
    setCookie(value);
    googleUpdate(value === 'all');
    (window.dataLayer = window.dataLayer || []).push({ event: 'consent_update', consent_state: value });
    if (value === 'all') { loadMarketing(); }
    var box = document.getElementById('consent');
    if (box) { box.hidden = true; }
  }

  window.SCConsent = {
    granted: function () { return SC.consent === 'all'; },
    choose: choose
  };

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-consent]');
    if (btn) { choose(btn.getAttribute('data-consent')); return; }
    if (e.target.closest('[data-consent-open]')) {
      var box = document.getElementById('consent');
      if (box) { box.hidden = false; var b = box.querySelector('button'); if (b) { b.focus(); } }
    }
  });

  if (SC.consent === 'all') { loadMarketing(); }
})();
