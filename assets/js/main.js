/* Sea College — site behaviour (vanilla JS, defer-loaded).
 * Nav, language & currency switchers, booking cart, tabs/stepper, AJAX forms,
 * WhatsApp message builder, click tracking (GA4 dataLayer + consented pixels), map loader. */
(function () {
  'use strict';
  var SC = window.SC || {};
  var T = SC.i18n || {};
  var dl = (window.dataLayer = window.dataLayer || []);
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var granted = function () { return !!(window.SCConsent && window.SCConsent.granted()); };
  var store = {
    get: function (k, d) { try { var v = localStorage.getItem(k); return v ? JSON.parse(v) : d; } catch (e) { return d; } },
    set: function (k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* private mode */ } }
  };
  function track(event, params) {
    var p = Object.assign({}, params || {});
    if (SC.ga4Direct && SC.ga4 && typeof window.gtag === 'function') {
      var gp = Object.assign({ send_to: SC.ga4 }, p);
      if (gp.ecommerce && typeof gp.ecommerce === 'object') {
        Object.assign(gp, gp.ecommerce);
        delete gp.ecommerce;
      }
      window.gtag('event', event, gp);
    }
    p.event = event;
    dl.push(p);
  }

  /* ---------------- Navigation ---------------- */
  var toggle = $('.nav-toggle');
  var nav = $('#site-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = toggle.getAttribute('aria-expanded') !== 'true';
      toggle.setAttribute('aria-expanded', String(open));
      nav.classList.toggle('is-open', open);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && nav.classList.contains('is-open')) { toggle.click(); toggle.focus(); }
    });
  }

  /* ---------------- Language switch & Smart Browser-Language Suggestion Pill ---------------- */
  $$('[data-lang-switch]').forEach(function (sel) {
    sel.addEventListener('change', function () {
      try { localStorage.setItem('sc_lang_dismiss', '1'); } catch (e) { /* ignore */ }
      if (sel.value) { location.href = sel.value; }
    });
  });

  (function initLangSuggestPill() {
    if ((SC.lang || 'en') !== 'en') { return; }
    var ua = String((navigator && navigator.userAgent) || '');
    if (/bot|crawl|spider|slurp|lighthouse|pagespeed|gtmetrix|headless/i.test(ua)) { return; }
    try { if (localStorage.getItem('sc_lang_dismiss') === '1') { return; } } catch (e) { /* ignore */ }
    var rawLang = String((navigator.languages && navigator.languages[0]) || navigator.language || '').toLowerCase();
    var code = rawLang.split(/[-_]/)[0];
    var copy = {
      ru: { flag: '🇷🇺', msg: 'Открыть сайт на русском?', cta: 'Переключить', dir: 'ltr' },
      it: { flag: '🇮🇹', msg: 'Vedere il sito in italiano?', cta: 'Cambia', dir: 'ltr' },
      pl: { flag: '🇵🇱', msg: 'Otworzyć stronę po polsku?', cta: 'Przełącz', dir: 'ltr' },
      de: { flag: '🇩🇪', msg: 'Seite auf Deutsch anzeigen?', cta: 'Wechseln', dir: 'ltr' },
      fr: { flag: '🇫🇷', msg: 'Voir le site en français ?', cta: 'Changer', dir: 'ltr' },
      ar: { flag: '🇪🇬', msg: 'هل تفضل تصفح الموقع بالعربية؟', cta: 'تبديل اللغة', dir: 'rtl' },
      zh: { flag: '🇨🇳', msg: '切换到中文版网站？', cta: '切换中文', dir: 'ltr' }
    }[code];
    if (!copy) { return; }
    var altLink = document.querySelector('link[rel="alternate"][hreflang="' + code + '"]');
    if (!altLink || !altLink.href) { return; }
    var dest = altLink.href;
    try {
      var u = new URL(altLink.href, location.origin);
      dest = u.pathname + (location.search || '') + (location.hash || '');
    } catch (e) { /* fallback to full href */ }
    var pill = document.createElement('div');
    pill.className = 'lang-pill no-print';
    pill.setAttribute('dir', copy.dir);
    pill.setAttribute('lang', code);
    pill.innerHTML = '<span class="lang-pill__msg">' + copy.flag + ' ' + copy.msg + '</span>' +
      '<a class="lang-pill__go" href=""></a>' +
      '<button type="button" class="lang-pill__x" aria-label="Close">✕</button>';
    var goA = pill.querySelector('.lang-pill__go');
    goA.href = dest;
    goA.textContent = copy.cta;
    goA.addEventListener('click', function () {
      try { localStorage.setItem('sc_lang_dismiss', '1'); } catch (e) { /* ignore */ }
      track('lang_suggest_accept', { target_lang: code });
    });
    pill.querySelector('.lang-pill__x').addEventListener('click', function () {
      try { localStorage.setItem('sc_lang_dismiss', '1'); } catch (e) { /* ignore */ }
      pill.remove();
      track('lang_suggest_dismiss', { target_lang: code });
    });
    document.body.appendChild(pill);
  })();

  /* ---------------- CRO Anchor Aliases (#calculator, #quiz, #checkin) ---------------- */
  function isCatId(id) {
    return SC.isCatalogId ? SC.isCatalogId(id) : (!!id && id !== 'dive-extra' && id !== 'padi-cert-fee' && !/^(eq|fee|ins)-/.test(String(id)));
  }
  function langPrefix() {
    return (SC.lang && SC.lang !== 'en') ? ('/' + SC.lang) : '';
  }
  function resolveCroHash(rawHash, smooth) {
    var h = String(rawHash || '').replace(/^#/, '').toLowerCase();
    if (!h) { return false; }
    if (h === 'checkin' || h === 'check-in') {
      if (SC.page === 'checkin' || SC.page === 'thanks') {
        var ciForm = $('[data-checkin-form]');
        if (ciForm) { ciForm.scrollIntoView({ behavior: smooth ? 'smooth' : 'auto', block: 'start' }); }
        if (history.replaceState) { history.replaceState(null, '', location.pathname + location.search); }
      } else {
        location.replace(langPrefix() + '/check-in' + (location.search || ''));
      }
      return true;
    }
    if (h === 'calculator' || h === 'dive-calculator') {
      var calcEl = document.getElementById('dive-calculator');
      if (calcEl) {
        calcEl.scrollIntoView({ behavior: smooth ? 'smooth' : 'auto', block: 'start' });
        return true;
      }
      if (h === 'calculator') {
        location.replace(langPrefix() + '/prices' + (location.search || '') + '#dive-calculator');
        return true;
      }
    }
    if (h === 'quiz' || h === 'dive-finder-quiz') {
      var quizEl = document.getElementById('dive-finder-quiz');
      if (quizEl) {
        quizEl.scrollIntoView({ behavior: smooth ? 'smooth' : 'auto', block: 'start' });
        return true;
      }
      if (h === 'quiz') {
        location.replace((langPrefix() || '/') + (location.search || '') + '#dive-finder-quiz');
        return true;
      }
    }
    return false;
  }
  if (location.hash) {
    resolveCroHash(location.hash, true);
  }
  window.addEventListener('hashchange', function () {
    resolveCroHash(location.hash, true);
  });
  document.addEventListener('click', function (ev) {
    var link = ev.target.closest && ev.target.closest('a[href*="#"]');
    if (!link) { return; }
    var href = link.getAttribute('href') || '';
    try {
      var u = new URL(href, location.origin);
      if (u.origin !== location.origin) { return; }
      var targetHash = (u.hash || '').replace(/^#/, '').toLowerCase();
      if (targetHash === 'checkin' || targetHash === 'check-in') {
        ev.preventDefault();
        location.replace(langPrefix() + '/check-in' + (u.search || location.search || ''));
        return;
      }
      if (u.pathname === location.pathname && (targetHash === 'calculator' || targetHash === 'dive-calculator' || targetHash === 'quiz' || targetHash === 'dive-finder-quiz')) {
        if (resolveCroHash(targetHash, true)) {
          ev.preventDefault();
          var canonHash = (targetHash === 'calculator') ? '#dive-calculator' : (targetHash === 'quiz' ? '#dive-finder-quiz' : '#' + targetHash);
          if (history.replaceState) { history.replaceState(null, '', location.pathname + location.search + canonHash); }
        }
      }
    } catch (err) { /* ignore malformed href */ }
  });

  /* ---------------- Attribution cache & Server-Side Click Beacon + Saved Hotel ---------------- */
  function readCookie(name) {
    var m = document.cookie.match(new RegExp('(?:^|;\\s*)' + name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '=([^;]*)'));
    return m ? decodeURIComponent(m[1]) : '';
  }

  var clickAttr = (function () {
    var keys = ['gclid', 'gbraid', 'wbraid', 'fbclid', 'ttclid', 'msclkid', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
    var cached = {};
    try { cached = JSON.parse(sessionStorage.getItem('sc_click_attr') || '{}') || {}; } catch (e) { cached = {}; }
    try {
      var sp = new URLSearchParams(location.search);
      var updated = false;
      keys.forEach(function (k) {
        var v = sp.get(k);
        if (v) { cached[k] = v; updated = true; }
      });
      var urlFbclid = sp.get('fbclid');
      if (urlFbclid) {
        var existingFbc = readCookie('_fbc');
        if (existingFbc && existingFbc.slice(-(urlFbclid.length + 1)) === '.' + urlFbclid) {
          cached.fbc = existingFbc;
        } else {
          cached.fbc = 'fb.1.' + Date.now() + '.' + urlFbclid;
        }
        updated = true;
      }
      if (updated) { sessionStorage.setItem('sc_click_attr', JSON.stringify(cached)); }
    } catch (e) { /* ignore */ }
    return cached;
  })();

  function getMetaCookies() {
    var cFbc = readCookie('_fbc');
    var cFbp = readCookie('_fbp');
    var fbc = cFbc || clickAttr.fbc || (clickAttr.fbclid ? ('fb.1.' + Date.now() + '.' + clickAttr.fbclid) : '');
    var fbp = cFbp || clickAttr.fbp || '';
    return { fbc: fbc, fbp: fbp };
  }

  function syncAttributionInputs(root) {
    var scope = root || document;
    var meta = getMetaCookies();
    var all = Object.assign({}, clickAttr, meta);
    ['gclid', 'gbraid', 'wbraid', 'fbclid', 'ttclid', 'msclkid', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'fbc', 'fbp'].forEach(function (k) {
      if (!all[k]) { return; }
      $$('input[name="' + k + '"]', scope).forEach(function (inp) {
        if (!inp.value || k === 'fbc' || k === 'fbp') { inp.value = all[k]; }
      });
    });
  }
  syncAttributionInputs(document);

  var savedHotel = store.get('sc_hotel', null);
  var hotelHooks = [];
  function syncHotelInputs() {
    var hName = savedHotel && savedHotel.name ? String(savedHotel.name) : '';
    if (hName) {
      $$('input[name="hotel"]').forEach(function (inp) {
        if (!inp.value) { inp.value = hName; }
      });
    }
    hotelHooks.forEach(function (fn) { fn(savedHotel); });
  }
  function setSavedHotel(obj, overwriteInputs) {
    savedHotel = obj;
    store.set('sc_hotel', obj);
    if (overwriteInputs && obj && obj.name) {
      $$('input[name="hotel"]').forEach(function (inp) { inp.value = obj.name; });
    }
    syncHotelInputs();
  }

  function sendClickBeacon(payload) {
    var meta = getMetaCookies();
    var p = Object.assign({
      kind: 'whatsapp_click',
      placement: 'general',
      page_url: location.href,
      lang: SC.lang || 'en',
      hotel: (savedHotel && savedHotel.name) ? savedHotel.name : ''
    }, clickAttr, meta, payload || {});
    if (typeof p.value === 'number' && p.estimated_eur === undefined) {
      p.estimated_eur = p.value;
    }
    var json = JSON.stringify(p);
    try {
      if (navigator.sendBeacon) {
        var blob = new Blob([json], { type: 'application/json' });
        if (navigator.sendBeacon('/handlers/wa_click_beacon.php', blob)) { return; }
      }
    } catch (e) { /* fallback to fetch */ }
    try {
      fetch('/handlers/wa_click_beacon.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: json,
        keepalive: true,
        credentials: 'same-origin'
      }).catch(function () {});
    } catch (e) { /* ignore */ }
  }

  /* ---------------- Currency (display only; bookings are in EUR) ---------------- */
  var rates = SC.currencyRates || { EUR: 1 };
  var symbols = SC.currencySymbols || { EUR: '€' };
  var currency = store.get('sc_currency', 'EUR');
  if (!rates[currency]) { currency = 'EUR'; }
  function fmt(eur) {
    var v = Math.round(eur * (rates[currency] || 1));
    return (symbols[currency] || '') + v.toLocaleString('en-US');
  }
  function applyCurrency() {
    $$('[data-eur]').forEach(function (el) {
      var eur = parseFloat(el.getAttribute('data-eur'));
      if (isNaN(eur)) { return; }
      var target = el.querySelector('bdi') || el;
      target.textContent = fmt(eur);
    });
    renderCart();
  }
  $$('[data-currency-switch]').forEach(function (sel) {
    sel.value = currency;
    sel.addEventListener('change', function () {
      currency = sel.value;
      store.set('sc_currency', currency);
      track('currency_change', { currency: currency });
      applyCurrency();
    });
  });

  /* ---------------- Booking cart ---------------- */
  var cart = store.get('sc_cart', []);
  if (!Array.isArray(cart)) { cart = []; }
  function saveCart() { store.set('sc_cart', cart); renderCart(); }
  function cartTotal() {
    return cart.reduce(function (s, l) { return s + (l.price ? l.price * l.qty : 0); }, 0);
  }
  function renderCart() {
    var box = $('[data-cart]');
    $$('[data-cart-input]').forEach(function (inp) {
      inp.value = JSON.stringify(cart.map(function (l) { return { id: l.id, qty: l.qty }; }));
    });
    $$('[data-add-to-cart]').forEach(function (b) {
      var inCart = cart.some(function (l) { return l.id === b.getAttribute('data-id'); });
      b.classList.toggle('is-added', inCart);
      b.textContent = (inCart ? '✓ ' + (T.added || 'Added') : '+ ' + (T.add || 'Add'));
    });
    var mini = $('[data-minicart]');
    if (mini) {
      var totalQty = cart.reduce(function (s, l) { return s + (l.qty || 1); }, 0);
      mini.hidden = totalQty === 0;
      var mc = $('[data-minicart-count]', mini);
      var mt = $('[data-minicart-total]', mini);
      if (mc) { mc.textContent = (T.minicartItems || '{n} selected').replace('{n}', String(totalQty)); }
      if (mt) { mt.textContent = fmt(cartTotal()); }
    }
    if (!box) { return; }
    var list = $('[data-cart-items]', box);
    var empty = $('[data-cart-empty]', box);
    var total = $('[data-cart-total]', box);
    list.innerHTML = '';
    cart.forEach(function (l, i) {
      var domBtn = document.querySelector('[data-add-to-cart][data-id="' + l.id + '"]');
      if (domBtn && domBtn.getAttribute('data-name')) {
        l.name = domBtn.getAttribute('data-name');
      }
      var li = document.createElement('li');
      li.className = 'cart__item';
      var name = document.createElement('span');
      name.className = 'cart__item-name';
      name.textContent = l.name;
      var price = document.createElement('span');
      price.textContent = l.price ? fmt(l.price * l.qty) : (T.onRequest || 'On request');
      var ctrl = document.createElement('span');
      ctrl.className = 'cart__item-ctrl';
      ctrl.innerHTML = '<button type="button" data-qty="-1" aria-label="-">−</button><span>' + l.qty + '</span><button type="button" data-qty="1" aria-label="+">+</button><button type="button" class="cart__rm" data-rm>' + (T.remove || 'Remove') + '</button>';
      ctrl.setAttribute('data-index', String(i));
      li.appendChild(name); li.appendChild(price); li.appendChild(ctrl);
      list.appendChild(li);
    });
    if (empty) { empty.hidden = cart.length > 0; }
    if (total) { total.textContent = fmt(cartTotal()); }
    var step = cart.length ? 2 : 1;
    $$('.stepper__step').forEach(function (s) {
      var n = parseInt(s.getAttribute('data-step'), 10);
      s.classList.toggle('is-active', n === step);
      s.classList.toggle('is-done', n < step);
    });
  }
  document.addEventListener('click', function (e) {
    var add = e.target.closest('[data-add-to-cart]');
    if (add) {
      var id = add.getAttribute('data-id');
      var existing = cart.filter(function (l) { return l.id === id; })[0];
      if (existing) { existing.qty += 1; } else {
        var p = parseFloat(add.getAttribute('data-price'));
        cart.push({ id: id, name: add.getAttribute('data-name'), price: isNaN(p) ? null : p, qty: 1 });
      }
      saveCart();
      var itemPrice = parseFloat(add.getAttribute('data-price')) || 0;
      var itemName = add.getAttribute('data-name') || '';
      track('add_to_cart', { ecommerce: { currency: 'EUR', items: [{ item_id: id, item_name: itemName, price: itemPrice, quantity: 1 }] } });
      if (granted() && window.fbq) {
        if (isCatId(id) && itemPrice > 0) {
          window.fbq('track', 'AddToCart', { content_ids: [id], content_name: itemName, content_type: 'product', value: itemPrice, currency: 'EUR' });
        } else {
          window.fbq('track', 'AddToCart', { content_name: itemName, content_category: 'addon', value: itemPrice, currency: 'EUR' });
        }
      }
      if (granted() && window.ttq) { window.ttq.track('AddToCart', { content_id: id, value: itemPrice, currency: 'EUR' }); }
      if (granted() && SC.uetId && window.uetq) { window.uetq.push('event', 'add_to_cart', { event_category: 'ecommerce', revenue_value: itemPrice, currency: 'EUR' }); }
      return;
    }
    var ctrl = e.target.closest('.cart__item-ctrl');
    if (ctrl) {
      var i = parseInt(ctrl.getAttribute('data-index'), 10);
      if (!cart[i]) { return; }
      if (e.target.closest('[data-rm]')) { cart.splice(i, 1); }
      else if (e.target.closest('[data-qty]')) {
        cart[i].qty = Math.max(1, Math.min(60, cart[i].qty + parseInt(e.target.closest('[data-qty]').getAttribute('data-qty'), 10)));
      }
      saveCart();
      return;
    }
    if (e.target.closest('[data-cart-clear]')) { cart = []; saveCart(); return; }
    if (e.target.closest('[data-print]')) { track('print_prices'); window.print(); }
  });

  /* ---------------- Tabs ---------------- */
  var tabs = $$('[data-tab]');
  function showTab(slug, focus) {
    tabs.forEach(function (t) {
      var on = t.getAttribute('data-tab') === slug;
      t.setAttribute('aria-selected', String(on));
      t.tabIndex = on ? 0 : -1;
      var panel = document.getElementById(t.getAttribute('aria-controls'));
      if (panel) { panel.hidden = !on; }
      if (on && focus) { t.focus(); }
    });
  }
  tabs.forEach(function (t, i) {
    t.addEventListener('click', function () { showTab(t.getAttribute('data-tab')); history.replaceState(null, '', '#' + t.getAttribute('data-tab')); });
    t.addEventListener('keydown', function (e) {
      var dir = document.documentElement.dir === 'rtl' ? -1 : 1;
      var k = e.key === 'ArrowRight' ? dir : e.key === 'ArrowLeft' ? -dir : 0;
      if (k) { var n = tabs[(i + k + tabs.length) % tabs.length]; showTab(n.getAttribute('data-tab'), true); }
    });
  });
  if (tabs.length && location.hash) {
    var h = location.hash.slice(1);
    if (tabs.some(function (t) { return t.getAttribute('data-tab') === h; })) { showTab(h); }
  }

  /* ---------------- AJAX forms ---------------- */
  $$('[data-ajax-form]').forEach(function (form) {
    var started = false;
    form.addEventListener('input', function () {
      if (!started) { started = true; track('form_start', { form_id: form.id || form.className }); }
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var status = $('.form-status', form);
      $$('.field', form).forEach(function (f) { f.classList.remove('has-error'); var m = $('.field__err', f); if (m) { m.remove(); } });
      if (!form.checkValidity()) {
        $$(':invalid', form).forEach(function (el) { var f = el.closest('.field'); if (f) { f.classList.add('has-error'); } });
        var first = $(':invalid', form); if (first) { first.focus(); }
        if (status) { status.className = 'form-status is-error'; status.textContent = form.getAttribute('data-invalid-msg') || ''; }
        form.reportValidity();
        return;
      }
      renderCart();
      syncAttributionInputs(form);
      form.classList.add('is-loading');
      fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
      }).then(function (r) { return r.json().catch(function () { return { ok: false }; }); })
        .then(function (res) {
          form.classList.remove('is-loading');
          var ev = form.getAttribute('data-event') || 'form_submit';
          if (res.ok) {
            track('form_submit', { form_id: form.id || ev, form_event: ev, event_id: res.event_id || '' });
            if (ev === 'newsletter_signup') {
              track('sign_up', { method: 'newsletter' });
              if (granted() && window.fbq) { window.fbq('track', 'CompleteRegistration', {}, { eventID: res.event_id }); }
            }
            if (res.redirect) {
              if (form.querySelector('[data-cart-input]')) { cart = []; store.set('sc_cart', []); }
              var dest = res.redirect;
              try {
                var curParams = new URLSearchParams(location.search);
                var pass = [];
                ['gclid', 'gbraid', 'wbraid', 'fbclid', 'ttclid', 'msclkid'].forEach(function (k) {
                  var kv = curParams.get(k) || clickAttr[k];
                  if (kv && dest.indexOf(k + '=') === -1) { pass.push(k + '=' + encodeURIComponent(kv)); }
                });
                if (pass.length) { dest += (dest.indexOf('?') === -1 ? '?' : '&') + pass.join('&'); }
              } catch (err) { /* ignore URLSearchParams issues */ }
              location.href = dest; return;
            }
            if (status) { status.className = 'form-status is-ok'; status.textContent = res.message || ''; }
            form.reset();
          } else {
            if (status) { status.className = 'form-status is-error'; status.textContent = res.message || ''; }
            var errs = res.errors || {};
            Object.keys(errs).forEach(function (name) {
              var el = form.elements[name];
              var f = el && (el.closest ? el.closest('.field') : null);
              if (f) {
                f.classList.add('has-error');
                var m = document.createElement('span'); m.className = 'field__err'; m.textContent = errs[name]; f.appendChild(m);
              }
            });
          }
        }).catch(function () {
          form.classList.remove('is-loading');
          form.submit(); // network/JS issue → classic POST fallback
        });
    });
  });

  /* ---------------- WhatsApp from form + cart & Dynamic Item EUR Resolver ---------------- */
  function resolveItemContext() {
    var id = (SC.item && SC.item.id) || '';
    var name = (SC.item && SC.item.name) || '';
    var eur = 0;
    var qbTot = $('[data-qb-total][data-eur]');
    if (qbTot) {
      eur = parseFloat(qbTot.getAttribute('data-eur')) || 0;
    }
    if (eur <= 0 && SC.item && SC.item.price) {
      eur = parseFloat(SC.item.price) || 0;
    }
    if (eur <= 0) {
      var qbEl = $('[data-quick-book][data-qb-price]');
      if (qbEl) {
        eur = parseFloat(qbEl.getAttribute('data-qb-price')) || 0;
        if (!id) { id = qbEl.getAttribute('data-qb-id') || ''; }
        if (!name) { name = qbEl.getAttribute('data-qb-raw-name') || qbEl.getAttribute('data-qb-name') || ''; }
      }
    }
    if (eur <= 0) {
      var metaPrice = $('meta[property="product:price:amount"]');
      if (metaPrice) { eur = parseFloat(metaPrice.getAttribute('content')) || 0; }
    }
    if (eur <= 0) {
      var ct = cartTotal();
      if (ct > 0) { eur = ct; }
    }
    return { id: id, name: name, eur: Math.round(eur * 100) / 100 };
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-wa-from-form]');
    if (!btn) { return; }
    var form = btn.closest('form');
    var get = function (n) { var el = form && form.elements[n]; return el && el.value ? String(el.value).trim() : ''; };
    var lines = [T.waGreeting || 'Hello!'];
    var totalVal = cartTotal();
    if (cart.length) {
      cart.forEach(function (l) { lines.push('• ' + l.name + ' × ' + l.qty + (l.price ? ' (' + fmt(l.price * l.qty) + ')' : '')); });
    }
    [['name', T.name], ['interest', T.interest], ['people', T.people], ['date', T.date], ['hotel', T.hotel], ['level', T.level]].forEach(function (p) {
      var v = get(p[0]);
      if (p[0] === 'level' && v && form.elements.level.selectedIndex > 0) { v = form.elements.level.options[form.elements.level.selectedIndex].text; }
      if (v) { lines.push((p[1] || p[0]) + ': ' + v); }
    });
    var msg = get('message'); if (msg) { lines.push('', msg); }
    var placement = btn.getAttribute('data-placement') || 'form_wa';
    var src = get('utm_source') || clickAttr.utm_source || 'website';
    var camp = get('utm_campaign') || clickAttr.utm_campaign || placement;
    lines.push('', '[ref: ' + src + ' / ' + camp + ' / ' + (SC.lang || 'en') + ']');
    var itemCtx = resolveItemContext();
    var convVal = totalVal > 0 ? totalVal : (itemCtx.eur > 0 ? itemCtx.eur : 30);
    track('begin_checkout', { value: convVal, currency: 'EUR' });
    track('whatsapp_click', { placement: placement, value: convVal, currency: 'EUR' });
    track('contact', { method: 'whatsapp', placement: placement });
    sendClickBeacon({
      kind: 'whatsapp_click',
      placement: placement,
      item_id: itemCtx.id || (cart[0] && cart[0].id) || '',
      item_name: get('interest') || (cart[0] && cart[0].name) || itemCtx.name || '',
      value: convVal,
      estimated_eur: convVal,
      date: get('date'),
      qty: parseInt(get('people'), 10) || 1,
      hotel: get('hotel') || ((savedHotel && savedHotel.name) || '')
    });
    if (window.scLoadTags) { window.scLoadTags(); }
    if (SC.adsDirect && SC.adsId && SC.adsWhatsappLabel && typeof window.gtag === 'function') {
      var rawEm = get('email').toLowerCase();
      var rawPh = get('phone').replace(/[^\d+]/g, '').replace(/^00/, '+');
      if (/^0(1[0125]\d{8})$/.test(rawPh)) { rawPh = '+20' + rawPh.slice(1); }
      else if (rawPh && rawPh.charAt(0) !== '+' && rawPh.length >= 8) { rawPh = '+' + rawPh.replace(/^0+/, ''); }
      var ecData = {};
      if (rawEm && rawEm.indexOf('@') > 0) { ecData.email = rawEm; }
      if (rawPh && rawPh.length >= 9) { ecData.phone_number = rawPh; }
      if (ecData.email || ecData.phone_number) { window.gtag('set', 'user_data', ecData); }
      window.gtag('event', 'conversion', { send_to: SC.adsId + '/' + SC.adsWhatsappLabel, value: convVal, currency: 'EUR', transaction_id: 'wa_form_' + Date.now(), transport_type: 'beacon' });
    }
    var fbFormContact = { content_name: 'form_whatsapp', value: convVal, currency: 'EUR' };
    var formCatIds = cart.map(function (c) { return c.id; }).filter(isCatId);
    if (!formCatIds.length && itemCtx.id && isCatId(itemCtx.id)) { formCatIds = [itemCtx.id]; }
    if (formCatIds.length) { fbFormContact.content_ids = formCatIds; fbFormContact.content_type = 'product'; }
    if (granted() && window.fbq) { window.fbq('track', 'Contact', fbFormContact); }
    if (granted() && window.ttq) { window.ttq.track('Contact', { content_name: 'form_whatsapp', value: convVal, currency: 'EUR' }); }
    if (granted() && SC.uetId && window.uetq) { window.uetq.push('event', 'whatsapp_click', { event_category: 'contact', revenue_value: convVal, currency: 'EUR' }); }
    window.open('https://wa.me/' + (SC.whatsapp || '') + '?text=' + encodeURIComponent(lines.join('\n')), '_blank', 'noopener');
  });

  /* ---------------- Click tracking ---------------- */
  document.addEventListener('click', function (e) {
    var a = e.target.closest('a, [data-track]');
    if (!a) { return; }
    if (a.hasAttribute('data-wa-from-form') || a.hasAttribute('data-calc-wa') || a.hasAttribute('data-qb-wa') || a.hasAttribute('data-checkin-wa')) { return; }
    var kind = a.getAttribute('data-track');
    var href = a.getAttribute('href') || '';
    if (!kind && a.tagName === 'A') {
      if (/^https?:\/\/wa\.me\//.test(href)) { kind = 'whatsapp_click'; }
      else if (href.indexOf('tel:') === 0) { kind = 'phone_click'; }
      else if (href.indexOf('mailto:') === 0) { kind = 'email_click'; }
      else if (/^https?:\/\//.test(href) && a.hostname !== location.hostname) { kind = 'outbound_link'; }
    }
    if (!kind || kind === 'form_submit_click') { return; }
    var params = { placement: a.getAttribute('data-placement') || '', link_url: href, link_text: (a.textContent || '').trim().slice(0, 80) };
    var itCtx = resolveItemContext();
    if (kind === 'whatsapp_click' && itCtx.eur > 0) {
      params.value = itCtx.eur;
      params.currency = 'EUR';
      if (itCtx.id) { params.item_id = itCtx.id; }
    }
    track(kind, params);
    if (kind === 'cta_click' || kind === 'outbound_link') { return; }
    // whatsapp / phone / email = contact intent
    if (kind === 'whatsapp_click' || kind === 'phone_click') {
      sendClickBeacon({
        kind: kind,
        placement: params.placement || 'general',
        item_id: itCtx.id,
        item_name: itCtx.name,
        value: itCtx.eur,
        estimated_eur: itCtx.eur
      });
    }
    if (window.scLoadTags) { window.scLoadTags(); }
    track('contact', { method: kind.replace('_click', ''), placement: params.placement });
    if (SC.adsDirect && SC.adsId && typeof window.gtag === 'function') {
      var dynEur = itCtx.eur > 0 ? itCtx.eur : 35;
      if (kind === 'whatsapp_click' && SC.adsWhatsappLabel) {
        var awPayload = { send_to: SC.adsId + '/' + SC.adsWhatsappLabel, value: dynEur, currency: 'EUR', transaction_id: 'wa_' + Date.now(), transport_type: 'beacon' };
        window.gtag('event', 'conversion', awPayload);
      } else if (kind === 'phone_click' && SC.adsCallLabel) {
        window.gtag('event', 'conversion', { send_to: SC.adsId + '/' + SC.adsCallLabel, value: dynEur, currency: 'EUR', transaction_id: 'call_' + Date.now(), transport_type: 'beacon' });
      }
    }
    var pxContact = { content_name: kind };
    if (kind === 'whatsapp_click' && itCtx.eur > 0) {
      pxContact.value = itCtx.eur;
      pxContact.currency = 'EUR';
      if (itCtx.id && isCatId(itCtx.id)) { pxContact.content_ids = [itCtx.id]; pxContact.content_type = 'product'; }
    }
    if (granted() && window.fbq) { window.fbq('track', 'Contact', pxContact); }
    if (granted() && window.ttq) { window.ttq.track('Contact', itCtx.eur > 0 ? { content_name: kind, value: itCtx.eur, currency: 'EUR' } : { content_name: kind }); }
    if (granted() && SC.uetId && window.uetq) { window.uetq.push('event', kind, itCtx.eur > 0 ? { event_category: 'contact', revenue_value: itCtx.eur, currency: 'EUR' } : { event_category: 'contact' }); }
  });

  /* ---------------- Interactive Sharm Hotel Pickup Zone & Transfer Time Checker ---------------- */
  $$('[data-hotel-checker]').forEach(function (box) {
    var cfg = {};
    try { cfg = JSON.parse(box.getAttribute('data-hotel-checker') || '{}'); } catch (e) { return; }
    var zones = cfg.zones || {};
    var hotels = cfg.hotels || [];
    var H = cfg.i18n || {};
    var inp = $('[data-hc-input]', box);
    var clr = $('[data-hc-clear]', box);
    var zBtns = $$('[data-hc-zone]', box);
    var res = $('[data-hc-result]', box);
    var elBadge = $('[data-hc-badge]', box);
    var elHotelWrap = $('[data-hc-hotel-wrap]', box);
    var elHotel = $('[data-hc-hotel]', box);
    var elZName = $('[data-hc-zname]', box);
    var elDrive = $('[data-hc-drive]', box);
    var elPickup = $('[data-hc-pickup]', box);

    function showResult(obj, persist) {
      if (!obj || !zones[obj.zone]) {
        if (res) { res.hidden = true; }
        zBtns.forEach(function (b) { b.classList.remove('is-active'); b.setAttribute('aria-pressed', 'false'); });
        return;
      }
      var z = zones[obj.zone];
      zBtns.forEach(function (b) {
        var on = b.getAttribute('data-hc-zone') === obj.zone;
        b.classList.toggle('is-active', on);
        b.setAttribute('aria-pressed', String(on));
      });
      if (res) {
        res.hidden = false;
        res.classList.toggle('is-onsite', !!obj.onsite);
      }
      if (elBadge) { elBadge.textContent = obj.onsite ? (H.onsiteBadge || '🏖️ On-Site Base') : (H.freeBadge || '✓ Free Transfer'); }
      if (elHotelWrap && elHotel) {
        elHotelWrap.hidden = !obj.name;
        elHotel.textContent = obj.name || '';
      }
      if (elZName) { elZName.textContent = z.label || ''; }
      if (elDrive) { elDrive.textContent = obj.onsite ? '0 min' : (z.drive || ''); }
      if (elPickup) { elPickup.textContent = obj.onsite ? '08:10' : (z.pickup || ''); }
      if (clr) { clr.hidden = false; }
      if (persist) {
        var record = {
          name: obj.name || z.label,
          zone: obj.zone,
          zoneLabel: z.label,
          drive: obj.onsite ? '0 min' : z.drive,
          pickup: obj.onsite ? '08:10' : z.pickup,
          onsite: !!obj.onsite
        };
        setSavedHotel(record, true);
        track('hotel_pickup_check', { hotel_zone: obj.zone, hotel_name: record.name });
      }
    }

    function matchHotel(q) {
      var clean = String(q || '').trim().toLowerCase();
      if (clean.length < 2) { return null; }
      for (var i = 0; i < hotels.length; i++) {
        if (hotels[i].n.toLowerCase() === clean) { return hotels[i]; }
      }
      for (var j = 0; j < hotels.length; j++) {
        if (hotels[j].n.toLowerCase().indexOf(clean) !== -1) { return hotels[j]; }
      }
      return null;
    }

    if (inp) {
      inp.addEventListener('input', function () {
        var raw = String(inp.value || '').trim();
        if (clr) { clr.hidden = raw === ''; }
        var m = matchHotel(raw);
        if (m) {
          showResult({ name: m.n, zone: m.z, onsite: !!m.o }, true);
        } else if (raw.length >= 3 && savedHotel && savedHotel.zone) {
          showResult({ name: raw, zone: savedHotel.zone, onsite: false }, true);
        }
      });
      inp.addEventListener('change', function () {
        var m = matchHotel(inp.value);
        if (m) { inp.value = m.n; showResult({ name: m.n, zone: m.z, onsite: !!m.o }, true); }
      });
    }

    zBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var zk = btn.getAttribute('data-hc-zone');
        var typed = inp ? String(inp.value || '').trim() : '';
        var m = matchHotel(typed);
        var hLabel = (m && m.z === zk) ? m.n : (typed && !m ? typed : '');
        showResult({ name: hLabel, zone: zk, onsite: !!(m && m.o && m.z === zk) }, true);
      });
    });

    if (clr) {
      clr.addEventListener('click', function () {
        if (inp) { inp.value = ''; }
        clr.hidden = true;
        setSavedHotel(null, false);
        showResult(null, false);
      });
    }

    if (savedHotel && savedHotel.zone && zones[savedHotel.zone]) {
      if (inp && savedHotel.name && savedHotel.name !== zones[savedHotel.zone].label) {
        inp.value = savedHotel.name;
      }
      showResult(savedHotel, false);
    }
  });
  syncHotelInputs();

  /* ---------------- Click-to-load & scroll-triggered Google Map + Tripadvisor ---------------- */
  function mountMap(box) {
    if (!box || box.classList.contains('is-loaded')) { return; }
    var f = document.createElement('iframe');
    f.src = box.getAttribute('data-map-src');
    f.title = box.getAttribute('data-map-title') || 'Map';
    f.loading = 'lazy';
    f.referrerPolicy = 'no-referrer-when-downgrade';
    box.innerHTML = '';
    box.removeAttribute('style');
    box.appendChild(f);
    box.classList.add('is-loaded');
  }
  function mountTaWidget(box) {
    if (!box || box.getAttribute('data-ta-mounted')) { return; }
    box.setAttribute('data-ta-mounted', '1');
    var src = box.getAttribute('data-ta-widget-src');
    if (!src) { return; }
    var s = document.createElement('script');
    s.async = true;
    s.src = src;
    box.appendChild(s);
  }
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-map-load]');
    if (b) { mountMap(b.closest('[data-map-src]')); }
  });
  function initDeferredEmbeds() {
    if (!('IntersectionObserver' in window)) {
      $$('[data-ta-widget-src]').forEach(mountTaWidget);
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) { return; }
        io.unobserve(en.target);
        if (en.target.hasAttribute('data-map-src')) { mountMap(en.target); }
        if (en.target.hasAttribute('data-ta-widget-src')) { mountTaWidget(en.target); }
      });
    }, { rootMargin: '280px 0px' });
    $$('[data-map-src], [data-ta-widget-src]').forEach(function (el) { io.observe(el); });
  }
  if (window._scUserInteracted) { initDeferredEmbeds(); }
  else { window.addEventListener('sc:interact', initDeferredEmbeds, { once: true }); }

  /* ---------------- Live service & hub search filter ---------------- */
  $$('[data-service-filter]').forEach(function (bar) {
    var inp = $('[data-filter-input]', bar);
    var rst = $('[data-filter-reset]', bar);
    var emptyMsg = $('[data-filter-empty]');
    if (!inp) { return; }
    function applyFilter() {
      var q = String(inp.value || '').trim().toLowerCase();
      if (rst) { rst.hidden = q === ''; }
      var shown = 0;
      $$('[data-filter-item]').forEach(function (el) {
        var hay = (el.getAttribute('data-filter-item') || '').toLowerCase();
        var match = q === '' || hay.indexOf(q) !== -1;
        el.hidden = !match;
        if (match) { shown += 1; }
      });
      $$('[data-filter-group]').forEach(function (g) {
        var vis = $$('[data-filter-item]', g).some(function (el) { return !el.hidden; });
        g.hidden = !vis;
      });
      $$('[data-filter-section]').forEach(function (sec) {
        var vis = $$('[data-filter-group]', sec).some(function (g) { return !g.hidden; });
        sec.hidden = !vis;
      });
      if (emptyMsg) { emptyMsg.hidden = shown > 0; }
    }
    inp.addEventListener('input', applyFilter);
    if (rst) { rst.addEventListener('click', function () { inp.value = ''; applyFilter(); inp.focus(); }); }
  });

  /* ---------------- Item page view_item / ViewContent ---------------- */
  if (SC.item && SC.item.id) {
    var itPrice = parseFloat(SC.item.price) || 0;
    track('view_item', {
      ecommerce: {
        currency: 'EUR',
        value: itPrice,
        items: [{ item_id: SC.item.id, item_name: SC.item.name, item_category: SC.item.category || '', price: itPrice, quantity: 1 }]
      }
    });
    var fbSent = false;
    var ttSent = false;
    var uetSent = false;
    var isCatalogItem = (SC.item.in_catalog !== false) && isCatId(SC.item.id) && itPrice > 0;
    var firePixelView = function () {
      if (!fbSent && window.fbq) {
        fbSent = true;
        if (isCatalogItem) {
          window.fbq('track', 'ViewContent', { content_ids: [SC.item.id], content_name: SC.item.name, content_category: SC.item.category || '', content_type: 'product', value: itPrice, currency: 'EUR' });
        } else if (itPrice > 0) {
          window.fbq('track', 'ViewContent', { content_name: SC.item.name, content_category: 'addon', value: itPrice, currency: 'EUR' });
        }
      }
      if (!ttSent && window.ttq) {
        ttSent = true;
        window.ttq.track('ViewContent', { content_id: SC.item.id, content_name: SC.item.name, value: itPrice, currency: 'EUR' });
      }
      if (!uetSent && SC.uetId && window.uetq) {
        uetSent = true;
        window.uetq.push('event', 'view_item', { event_category: 'ecommerce', event_label: SC.item.id, revenue_value: itPrice, currency: 'EUR' });
      }
    };
    if (granted()) { firePixelView(); }
    document.addEventListener('sc:marketing_loaded', function () { if (granted()) { firePixelView(); } });
    document.addEventListener('sc:consent', function (ev) { if (ev.detail === 'all') { firePixelView(); } });
  }

  /* ---------------- Feature 1: Interactive Dive Budget & Fee Calculator ---------------- */
  var calcHooks = [];
  $$('[data-dive-calc]').forEach(function (root) {
    var cfg = {};
    try { cfg = JSON.parse(root.getAttribute('data-dive-calc') || '{}'); } catch (e) { return; }
    var items = cfg.items || {};
    var L = cfg.labels || {};
    var mode = 'dives';

    var elDivers = $('[data-calc-divers]', root);
    var elDiversVal = $('[data-calc-divers-val]', root);
    var elDives = $('[data-calc-dives]', root);
    var elDivesVal = $('[data-calc-dives-val]', root);
    var elCourse = $('[data-calc-course]', root);
    var elBoat = $('[data-calc-boat-days]', root);
    var elBoatVal = $('[data-calc-boat-val]', root);
    var elFeeBoat = $('[data-calc-fee-boat]', root);
    var elFeePark = $('[data-calc-fee-park]', root);
    var elRefresh = $('[data-calc-refresh]', root);
    var elGas = $('[data-calc-gas]', root);
    var elPadiFee = $('[data-calc-padi-fee]', root);
    var elPrivGuide = $('[data-calc-private-guide]', root);
    var elLines = $('[data-calc-lines]', root);
    var elPp = $('[data-calc-pp]', root);
    var elGrand = $('[data-calc-grand]', root);
    var elToast = $('[data-calc-toast]', root);

    function courseBoatDefault(cid) {
      if (cid === 'dsd-quick-reef') { return 0; }
      if (cid === 'dsd-quick-boat' || cid === 'dsd-2-boat') { return 1; }
      if (cid === 'bundle-mega' || cid === 'bundle-heros') { return 4; }
      return 2;
    }
    function padiCourseCount(cid) {
      if (cid.indexOf('dsd') === 0) { return 0; }
      if (cid === 'bundle-heros') { return 3; }
      if (cid.indexOf('bundle-') === 0) { return 2; }
      return 1;
    }

    function computeLines() {
      var divers = Math.max(1, parseInt(elDivers.value, 10) || 1);
      var boatDays = Math.max(0, parseInt(elBoat.value, 10) || 0);
      var lines = [];
      var addLine = function (id, qty, customLabel, unitEur, cartable) {
        if (qty <= 0) { return; }
        var it = items[id];
        if (!it && !customLabel) { return; }
        var p = it ? parseFloat(it.price) : unitEur;
        lines.push({
          id: id,
          name: customLabel || it.name,
          rawName: it ? (it.raw_name || it.name) : customLabel,
          price: p,
          qty: qty,
          cartable: cartable !== false && !!it
        });
      };

      var diveDays = Math.max(1, boatDays);
      if (mode === 'dives') {
        var n = Math.max(1, parseInt(elDives.value, 10) || 1);
        diveDays = Math.max(1, boatDays || Math.ceil(n / 2));
        if (n <= 10) {
          addLine('dive-' + n, divers);
        } else {
          addLine('dive-10', divers);
          addLine('dive-extra', (n - 10) * divers);
        }
        if (elRefresh && elRefresh.value !== 'none') {
          addLine(elRefresh.value, divers);
        }
        if (elGas && elGas.value !== 'air12') {
          addLine(elGas.value, diveDays * divers);
        }
        $$('[data-calc-gear]:checked', root).forEach(function (chk) {
          addLine(chk.getAttribute('data-calc-gear'), diveDays * divers);
        });
      } else {
        var cid = elCourse ? elCourse.value : 'owd';
        addLine(cid, divers);
        var pCount = padiCourseCount(cid);
        if (elPadiFee && elPadiFee.checked && pCount > 0) {
          addLine('padi-cert-fee', pCount * divers, L.padiFeeName || 'PADI Manual & Cert Fee', 85, false);
        }
      }

      if (boatDays > 0) {
        if (elFeeBoat && elFeeBoat.checked) { addLine('fee-boat', boatDays * divers); }
        if (elFeePark && elFeePark.checked) { addLine('fee-park', boatDays * divers); }
      }
      $$('[data-calc-sup]:checked', root).forEach(function (chk) {
        addLine(chk.getAttribute('data-calc-sup'), divers);
      });
      if (elPrivGuide && elPrivGuide.checked) {
        addLine('private-guide', diveDays);
      }
      return { divers: divers, lines: lines };
    }

    function renderCalc() {
      var st = computeLines();
      elDiversVal.textContent = String(st.divers);
      if (elDivesVal && elDives) { elDivesVal.textContent = String(elDives.value); }
      if (elBoatVal && elBoat) { elBoatVal.textContent = String(elBoat.value); }

      elLines.innerHTML = '';
      var grand = 0;
      st.lines.forEach(function (ln) {
        var sub = ln.price * ln.qty;
        grand += sub;
        var li = document.createElement('li');
        var left = document.createElement('span');
        left.textContent = ln.name + ' × ' + ln.qty;
        var right = document.createElement('span');
        right.textContent = fmt(sub);
        li.appendChild(left);
        li.appendChild(right);
        elLines.appendChild(li);
      });
      var pp = st.divers > 0 ? (grand / st.divers) : grand;
      if (elPp) { elPp.textContent = fmt(pp); }
      if (elGrand) { elGrand.textContent = fmt(grand); }
    }

    $$('[data-calc-mode]', root).forEach(function (btn) {
      btn.addEventListener('click', function () {
        mode = btn.getAttribute('data-calc-mode');
        $$('[data-calc-mode]', root).forEach(function (b) {
          var on = b === btn;
          b.classList.toggle('is-active', on);
          b.setAttribute('aria-pressed', String(on));
        });
        $$('[data-calc-only]', root).forEach(function (sec) {
          sec.hidden = sec.getAttribute('data-calc-only') !== mode;
        });
        if (mode === 'course' && elCourse) {
          elBoat.value = String(courseBoatDefault(elCourse.value));
        } else if (mode === 'dives' && elDives) {
          var n = parseInt(elDives.value, 10) || 6;
          elBoat.value = String(n === 1 ? 0 : Math.min(8, Math.ceil(n / 2)));
        }
        renderCalc();
      });
    });

    if (elDives) {
      elDives.addEventListener('input', function () {
        var n = parseInt(elDives.value, 10) || 1;
        elBoat.value = String(n === 1 ? 0 : Math.min(8, Math.ceil(n / 2)));
        renderCalc();
      });
    }
    if (elCourse) {
      elCourse.addEventListener('change', function () {
        elBoat.value = String(courseBoatDefault(elCourse.value));
        renderCalc();
      });
    }
    root.addEventListener('input', function (ev) {
      if (ev.target !== elDives && ev.target !== elCourse) { renderCalc(); }
    });
    root.addEventListener('change', function (ev) {
      if (ev.target !== elCourse) { renderCalc(); }
    });

    var pushBtn = $('[data-calc-push-cart]', root);
    if (pushBtn) {
      pushBtn.addEventListener('click', function () {
        var st = computeLines();
        cart = [];
        var ecomItems = [];
        var totalEur = 0;
        st.lines.forEach(function (ln) {
          if (!ln.cartable) { return; }
          cart.push({ id: ln.id, name: ln.name, price: ln.price, qty: ln.qty });
          totalEur += ln.price * ln.qty;
          ecomItems.push({ item_id: ln.id, item_name: ln.rawName, price: ln.price, quantity: ln.qty });
        });
        saveCart();
        track('add_to_cart', { ecommerce: { currency: 'EUR', value: totalEur, items: ecomItems } });
        var catIds = ecomItems.map(function (x) { return x.item_id; }).filter(isCatId);
        if (granted() && window.fbq) {
          if (catIds.length) {
            window.fbq('track', 'AddToCart', { content_ids: catIds, content_type: 'product', value: totalEur, currency: 'EUR' });
          } else {
            window.fbq('track', 'AddToCart', { content_name: 'Dive Calculator Estimate', value: totalEur, currency: 'EUR' });
          }
        }
        if (granted() && window.ttq) {
          window.ttq.track('AddToCart', { content_id: catIds[0] || 'dive_calculator', value: totalEur, currency: 'EUR' });
        }
        if (granted() && SC.uetId && window.uetq) {
          window.uetq.push('event', 'add_to_cart', { event_category: 'ecommerce', revenue_value: totalEur, currency: 'EUR' });
        }
        if (elToast) { elToast.textContent = L.pushedToast || 'Added!'; elToast.hidden = false; }
        var peopleInput = document.querySelector('#booking input[name="people"]');
        if (peopleInput) { peopleInput.value = String(st.divers); }
        var target = document.querySelector(cfg.bookingHref || '#booking');
        if (target) { target.scrollIntoView({ behavior: 'smooth' }); }
      });
    }

    var waBtn = $('[data-calc-wa]', root);
    if (waBtn) {
      waBtn.addEventListener('click', function () {
        var st = computeLines();
        var grand = 0;
        var out = [T.waGreeting || 'Hello!', L.waHeader || 'Dive estimate:'];
        st.lines.forEach(function (ln) {
          var sub = ln.price * ln.qty;
          grand += sub;
          out.push('• ' + ln.name + ' × ' + ln.qty + ' = ' + fmt(sub));
        });
        if (savedHotel && savedHotel.name) {
          out.push((T.hotel || 'Hotel') + ': ' + savedHotel.name + (savedHotel.pickup ? ' (' + savedHotel.pickup + ')' : ''));
        }
        out.push('', (L.totalLabel || 'Total') + ' (' + st.divers + ' ' + (L.divers || 'divers') + '): ' + fmt(grand));
        out.push('[ref: ' + (clickAttr.utm_source || 'website') + ' / ' + (clickAttr.utm_campaign || 'dive_calculator') + ' / ' + (SC.lang || 'en') + ']');
        track('whatsapp_click', { placement: 'dive_calculator', value: grand, currency: 'EUR' });
        track('contact', { method: 'whatsapp', placement: 'dive_calculator' });
        sendClickBeacon({
          kind: 'whatsapp_click',
          placement: 'dive_calculator',
          item_id: (st.lines[0] && isCatId(st.lines[0].id)) ? st.lines[0].id : '',
          item_name: st.lines[0] ? st.lines[0].rawName : 'Dive Calculator Bundle',
          value: grand,
          estimated_eur: grand,
          qty: st.divers,
          hotel: (savedHotel && savedHotel.name) || ''
        });
        if (window.scLoadTags) { window.scLoadTags(); }
        if (SC.adsDirect && SC.adsId && SC.adsWhatsappLabel && typeof window.gtag === 'function') {
          window.gtag('event', 'conversion', { send_to: SC.adsId + '/' + SC.adsWhatsappLabel, value: grand, currency: 'EUR', transaction_id: 'wa_calc_' + Date.now(), transport_type: 'beacon' });
        }
        var calcCatIds = st.lines.map(function (ln) { return ln.id; }).filter(isCatId);
        var calcContact = { content_name: 'dive_calculator_wa', value: grand, currency: 'EUR' };
        if (calcCatIds.length) { calcContact.content_ids = calcCatIds; calcContact.content_type = 'product'; }
        if (granted() && window.fbq) { window.fbq('track', 'Contact', calcContact); }
        if (granted() && window.ttq) { window.ttq.track('Contact', { content_name: 'dive_calculator_wa', value: grand, currency: 'EUR' }); }
        if (granted() && SC.uetId && window.uetq) { window.uetq.push('event', 'whatsapp_click', { event_category: 'contact', revenue_value: grand, currency: 'EUR' }); }
        window.open('https://wa.me/' + (SC.whatsapp || '') + '?text=' + encodeURIComponent(out.join('\n')), '_blank', 'noopener');
      });
    }

    calcHooks.push(renderCalc);
    renderCalc();
  });

  /* ---------------- Feature 2: 30-Second Dive / Course Recommender Quiz ---------------- */
  var quizHooks = [];
  $$('[data-dive-quiz]').forEach(function (root) {
    var cfg = {};
    try { cfg = JSON.parse(root.getAttribute('data-dive-quiz') || '{}'); } catch (e) { return; }
    var items = cfg.items || {};
    var Q = cfg.i18n || {};
    var R = Q.reasons || {};
    var ans = {};
    var curStep = 1;

    function showStep(n) {
      curStep = n;
      $$('[data-quiz-step]', root).forEach(function (el) {
        el.hidden = parseInt(el.getAttribute('data-quiz-step'), 10) !== n;
      });
      $$('[data-quiz-dot]', root).forEach(function (d) {
        var s = parseInt(d.getAttribute('data-quiz-dot'), 10);
        d.classList.toggle('is-active', s === n);
        d.classList.toggle('is-done', s < n);
      });
      var resBox = $('[data-quiz-results]', root);
      if (resBox) { resBox.hidden = n <= 4; }
    }

    function recommend() {
      var g = ans[1] || 'solo';
      var exp = ans[2] || 'never';
      var dur = ans[3] || 'day1';
      var goal = ans[4] || 'easy';
      var picks = [];
      var push = function (id, reasonKey) {
        if (items[id] && !picks.some(function (p) { return p.id === id; })) {
          picks.push({ id: id, reason: R[reasonKey] || '' });
        }
      };

      if (g === 'kids8') {
        push('bubblemaker', 'kids');
        push('seal-team', 'kids');
        push('snork-ras-adult', 'snorkel');
      } else if (exp === 'surface') {
        if (g === 'vipgroup' || goal === 'private') {
          push(dur === 'half' ? 'speed-2h' : 'private-6-10', 'private');
          push('snork-ras-adult', 'snorkel');
          push('speed-2h', 'private');
        } else {
          push(dur === 'half' ? 'snork-beach-day' : 'snork-ras-adult', 'snorkel');
          push('speed-2h', 'private');
          push('dsd-quick-reef', 'intro');
        }
      } else if (g === 'vipgroup' || goal === 'private') {
        push(dur === 'half' ? 'speed-2h' : 'private-6-10', 'private');
        push('private-1-5', 'private');
        push(exp === 'never' ? 'dsd-2-boat' : 'dive-2', exp === 'never' ? 'intro' : 'dives');
      } else if (exp === 'never') {
        if (goal === 'cert' && (dur === 'days3' || dur === 'week')) {
          if (dur === 'week') {
            push('bundle-mega', 'bundle');
            push('bundle-stars', 'bundle');
            push('owd', 'course');
          } else {
            push('owd', 'course');
            push('scuba-diver', 'course');
            push('dsd-2-boat', 'intro');
          }
        } else if (dur === 'days3' && goal === 'cert') {
          push('scuba-diver', 'course');
          push('owd', 'course');
          push('dsd-2-boat', 'intro');
        } else if (dur === 'half') {
          push('dsd-quick-reef', 'intro');
          push('dsd-2-reef', 'intro');
          push('dsd-quick-boat', 'intro');
        } else {
          push('dsd-2-boat', 'intro');
          push('dsd', 'intro');
          push('owd', 'course');
        }
      } else if (exp === 'rusty') {
        push('scuba-review', 'refresh');
        push('check-dive', 'refresh');
        push(dur === 'week' ? 'dive-6' : 'dive-2', 'dives');
      } else if (exp === 'adv') {
        if (goal === 'cert') {
          push('rescue', 'course');
          push('nitrox', 'course');
          push('sup-thistlegorm', 'wreck');
        } else if (dur === 'week') {
          push('dive-10', 'dives');
          push('sup-thistlegorm', 'wreck');
          push('dive-6', 'dives');
        } else {
          push('sup-thistlegorm', 'wreck');
          push('dive-6', 'dives');
          push('sup-dunraven', 'wreck');
        }
      } else {
        // Certified Open Water
        if (goal === 'cert') {
          push(dur === 'week' ? 'bundle-super' : 'aowd', dur === 'week' ? 'bundle' : 'course');
          push('aowd', 'course');
          push('nitrox', 'course');
        } else if (dur === 'half') {
          push('dive-1', 'dives');
          push('dive-2', 'dives');
          push('check-dive', 'refresh');
        } else if (dur === 'week') {
          push('dive-10', 'dives');
          push('dive-6', 'dives');
          push('bundle-super', 'bundle');
        } else if (dur === 'days3') {
          push('dive-6', 'dives');
          push('aowd', 'course');
          push('dive-2', 'dives');
        } else {
          push('dive-2', 'dives');
          push('dive-6', 'dives');
          push('aowd', 'course');
        }
      }
      return picks.slice(0, 3);
    }

    function renderQuizResults() {
      var resBox = $('[data-quiz-results]', root);
      var cardsEl = $('[data-quiz-cards]', root);
      if (!resBox || resBox.hidden || !cardsEl) { return; }
      var recs = recommend();
      cardsEl.innerHTML = '';
      recs.forEach(function (rec, idx) {
        var it = items[rec.id];
        if (!it) { return; }
        var inCart = cart.some(function (l) { return l.id === it.id; });
        var art = document.createElement('article');
        art.className = 'qres' + (idx === 0 ? ' qres--best' : '');
        var tag = '<span class="qres__tag">' + (idx === 0 ? (Q.bestMatch || '#1 Best Match') : (Q.altMatch || 'Alternative')) + '</span>';
        var title = '<h4 class="qres__title" dir="auto"><a class="pcard__link" href="' + it.url + '">' + it.name + '</a></h4>';
        var why = '<p class="qres__why" dir="auto">✓ ' + rec.reason + '</p>';
        var desc = '<p class="qres__desc" dir="auto">' + (it.duration ? '⏱ ' + it.duration + ' · ' : '') + it.desc + '</p>';
        var pr = it.price !== null ? '<span class="price" data-eur="' + it.price + '"><bdi dir="ltr">' + fmt(it.price) + '</bdi></span>' : '';
        var foot = '<div class="qres__foot">' + pr +
          '<span class="pcard__actions">' +
          '<a class="btn btn--detail btn--sm" href="' + it.url + '">' + (Q.details || 'Details') + '</a>' +
          '<button type="button" class="btn btn--add' + (inCart ? ' is-added' : '') + '" data-add-to-cart data-id="' + it.id + '" data-name="' + it.name.replace(/"/g, '&quot;') + '" data-price="' + (it.price || '') + '">' +
          (inCart ? '✓ ' + (T.added || 'Added') : '+ ' + (T.add || 'Add')) + '</button></span></div>';
        art.innerHTML = '<div>' + tag + title + why + desc + '</div>' + foot;
        cardsEl.appendChild(art);
      });
    }

    root.addEventListener('click', function (ev) {
      var pick = ev.target.closest('[data-quiz-pick]');
      if (pick) {
        var s = parseInt(pick.getAttribute('data-step'), 10);
        ans[s] = pick.getAttribute('data-quiz-pick');
        if (s < 4) {
          showStep(s + 1);
        } else {
          showStep(5);
          renderQuizResults();
          track('quiz_complete', { group: ans[1], experience: ans[2], duration: ans[3], goal: ans[4] });
        }
        return;
      }
      if (ev.target.closest('[data-quiz-back]')) {
        showStep(Math.max(1, curStep - 1));
        return;
      }
      if (ev.target.closest('[data-quiz-reset]')) {
        ans = {};
        showStep(1);
      }
    });

    quizHooks.push(renderQuizResults);
  });

  /* ---------------- Feature 3: Interactive Dive Sites SVG Map & Zone Filter ---------------- */
  var mapBox = $('[data-dive-map]');
  if (mapBox) {
    var pins = $$('[data-map-pin]', mapBox);
    var details = $$('[data-map-detail]', mapBox);
    var siteCards = $$('[data-site-card]');

    var selectSite = function (siteId, highlightCard) {
      pins.forEach(function (p) { p.classList.toggle('is-active', p.getAttribute('data-map-pin') === siteId); });
      details.forEach(function (d) { d.hidden = d.getAttribute('data-map-detail') !== siteId; });
      siteCards.forEach(function (c) { c.classList.toggle('is-highlighted', highlightCard && c.getAttribute('data-site-card') === siteId); });
      track('dive_site_select', { site_id: siteId });
    };

    pins.forEach(function (pin) {
      pin.addEventListener('click', function () { selectSite(pin.getAttribute('data-map-pin'), true); });
      pin.addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); selectSite(pin.getAttribute('data-map-pin'), true); }
      });
    });

    $$('[data-zone-filter]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var z = btn.getAttribute('data-zone-filter');
        $$('[data-zone-filter]').forEach(function (b) {
          var on = b === btn;
          b.classList.toggle('is-active', on);
          b.setAttribute('aria-pressed', String(on));
        });
        var firstMatch = null;
        pins.forEach(function (p) {
          var ok = z === 'all' || p.getAttribute('data-zone') === z;
          p.classList.toggle('is-dim', !ok);
          if (ok && !firstMatch) { firstMatch = p.getAttribute('data-map-pin'); }
        });
        siteCards.forEach(function (c) {
          c.hidden = !(z === 'all' || c.getAttribute('data-zone') === z);
        });
        if (firstMatch) { selectSite(firstMatch, false); }
      });
    });
  }

  /* ---------------- Feature 4: Photo & Video Gallery Filter + Lightbox ---------------- */
  $$('[data-gallery]').forEach(function (gal) {
    var items = $$('[data-gal-item]', gal);
    var dlg = $('[data-lightbox]', gal);
    var lbMedia = $('[data-lb-media]', gal);
    var lbTitle = $('[data-lb-title]', gal);
    var lbCap = $('[data-lb-cap]', gal);
    var lbLink = $('[data-lb-link]', gal);
    var activeIdx = 0;

    function visibleIndices() {
      var out = [];
      items.forEach(function (el, i) { if (!el.hidden) { out.push(i); } });
      return out;
    }

    function stopVideo() {
      if (!lbMedia) { return; }
      var v = $('video', lbMedia);
      if (v) { v.pause(); v.removeAttribute('src'); v.load(); }
      lbMedia.innerHTML = '';
    }

    function openAt(idx) {
      var el = items[idx];
      if (!el || !dlg) { return; }
      activeIdx = idx;
      stopVideo();
      var type = el.getAttribute('data-gal-type');
      var src = el.getAttribute('data-gal-src');
      if (type === 'video') {
        var vid = document.createElement('video');
        vid.src = src;
        vid.poster = el.getAttribute('data-gal-poster') || '';
        vid.controls = true;
        vid.playsInline = true;
        vid.autoplay = true;
        lbMedia.appendChild(vid);
      } else {
        var img = document.createElement('img');
        img.src = src;
        img.alt = el.getAttribute('data-gal-title') || '';
        lbMedia.appendChild(img);
      }
      if (lbTitle) { lbTitle.textContent = el.getAttribute('data-gal-title') || ''; }
      if (lbCap) { lbCap.textContent = el.getAttribute('data-gal-caption') || ''; }
      if (lbLink) {
        lbLink.href = el.getAttribute('data-gal-link') || (langPrefix() + '/prices');
        lbLink.textContent = el.getAttribute('data-gal-link-text') || '';
      }
      if (!dlg.open && typeof dlg.showModal === 'function') { dlg.showModal(); }
      else { dlg.setAttribute('open', ''); }
      track('gallery_open', { media_type: type, media_title: el.getAttribute('data-gal-title') || '' });
    }

    function step(delta) {
      var vis = visibleIndices();
      if (!vis.length) { return; }
      var pos = vis.indexOf(activeIdx);
      var next = vis[(pos + delta + vis.length) % vis.length];
      openAt(next);
    }

    function closeLb() {
      stopVideo();
      if (dlg && dlg.open && typeof dlg.close === 'function') { dlg.close(); }
      else if (dlg) { dlg.removeAttribute('open'); }
    }

    var groups = $$('[data-gal-group]', gal);

    function applyFilter(f) {
      $$('[data-gal-filter]', gal).forEach(function (b) {
        var on = b.getAttribute('data-gal-filter') === f;
        b.classList.toggle('is-active', on);
        b.setAttribute('aria-pressed', String(on));
      });
      items.forEach(function (el) {
        el.hidden = !(f === 'all' || el.getAttribute('data-gal-cat') === f);
      });
      groups.forEach(function (grp) {
        var hasVis = $$('[data-gal-item]', grp).some(function (el) { return !el.hidden; });
        grp.hidden = !hasVis;
      });
    }

    $$('[data-gal-filter]', gal).forEach(function (btn) {
      btn.addEventListener('click', function () {
        applyFilter(btn.getAttribute('data-gal-filter') || 'all');
      });
    });

    gal.addEventListener('click', function (ev) {
      var op = ev.target.closest('[data-gal-open]');
      if (op) { openAt(parseInt(op.getAttribute('data-gal-open'), 10)); return; }
      if (ev.target.closest('[data-lb-close]') || ev.target === dlg) { closeLb(); return; }
      if (ev.target.closest('[data-lb-prev]')) { step(-1); return; }
      if (ev.target.closest('[data-lb-next]')) { step(1); }
    });

    if (dlg) {
      dlg.addEventListener('close', stopVideo);
      dlg.addEventListener('keydown', function (ev) {
        var rtl = document.documentElement.dir === 'rtl' ? -1 : 1;
        if (ev.key === 'ArrowRight') { step(rtl); }
        else if (ev.key === 'ArrowLeft') { step(-rtl); }
      });
    }
  });

  /* ---------------- Item Page Multi-Photo & Underwater Video Gallery ---------------- */
  $$('[data-item-gallery]').forEach(function (wrap) {
    var stageImg = $('[data-item-stage-img]', wrap);
    var stageVid = $('[data-item-stage-vid]', wrap);
    var thumbs = $$('[data-item-thumb]', wrap);
    if (!thumbs.length) { return; }

    function resetVid() {
      if (!stageVid) { return; }
      stageVid.pause();
      stageVid.hidden = true;
    }

    thumbs.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var kind = btn.getAttribute('data-item-thumb');
        var src = btn.getAttribute('data-src') || '';
        thumbs.forEach(function (b) {
          var on = b === btn;
          b.classList.toggle('is-active', on);
          b.setAttribute('aria-pressed', String(on));
        });
        if (kind === 'video' && stageVid) {
          if (stageImg) { stageImg.hidden = true; }
          if (stageVid.getAttribute('src') !== src) {
            stageVid.src = src;
            var poster = btn.getAttribute('data-poster');
            if (poster) { stageVid.poster = poster; }
          }
          stageVid.hidden = false;
          var p = stageVid.play();
          if (p && typeof p.catch === 'function') { p.catch(function () {}); }
          track('item_gallery_select', { media_type: 'video', item_id: (SC.item && SC.item.id) || '' });
        } else if (stageImg) {
          resetVid();
          var webp = btn.getAttribute('data-webp') || src;
          var pic = stageImg.closest('picture');
          if (pic) {
            $$('source', pic).forEach(function (s) { s.srcset = webp; s.removeAttribute('sizes'); });
          }
          stageImg.src = src;
          var alt = btn.getAttribute('data-alt');
          if (alt) { stageImg.alt = alt; }
          stageImg.hidden = false;
          track('item_gallery_select', { media_type: 'photo', item_id: (SC.item && SC.item.id) || '' });
        }
      });
    });
  });

  /* ---------------- Item Page Quick-Book Configurator & Mobile Sticky Bar ---------------- */
  var qbHooks = [];
  var qbBox = $('[data-quick-book]');
  if (qbBox) {
    var qbId = qbBox.getAttribute('data-qb-id') || '';
    var qbName = qbBox.getAttribute('data-qb-name') || '';
    var qbRawName = qbBox.getAttribute('data-qb-raw-name') || qbName;
    var qbUnitPrice = parseFloat(qbBox.getAttribute('data-qb-price')) || 0;
    var qbUnit = qbBox.getAttribute('data-qb-unit') || '';
    var qbDate = $('[data-qb-date]', qbBox);
    var qbQty = $('[data-qb-qty]', qbBox);
    var qbTotal = $('[data-qb-total]', qbBox);
    var qbFormula = $('[data-qb-formula]', qbBox);
    var qbHotelBadge = $('[data-qb-hotel-badge]', qbBox);
    var qbHotelText = $('[data-qb-hotel-text]', qbBox);
    var qbBarTotal = $('[data-qb-bar-total]');
    var qbBarQty = $('[data-qb-bar-qty]');

    var getQbQty = function () {
      var n = parseInt(qbQty && qbQty.value, 10);
      return isNaN(n) ? 1 : Math.max(1, Math.min(30, n));
    };

    var renderQuickBook = function () {
      var q = getQbQty();
      var tot = q * qbUnitPrice;
      if (qbTotal) {
        qbTotal.setAttribute('data-eur', String(tot));
        var bdi = qbTotal.querySelector('bdi') || qbTotal;
        bdi.textContent = fmt(tot);
      }
      if (qbFormula) {
        qbFormula.textContent = q + ' × ' + fmt(qbUnitPrice) + ' = ' + fmt(tot);
      }
      if (qbBarTotal) {
        qbBarTotal.setAttribute('data-eur', String(tot));
        var barBdi = qbBarTotal.querySelector('bdi') || qbBarTotal;
        barBdi.textContent = fmt(tot);
      }
      if (qbBarQty && q > 1) {
        qbBarQty.textContent = q + ' × ' + fmt(qbUnitPrice);
      }
      if (qbHotelBadge && qbHotelText) {
        if (savedHotel && savedHotel.name) {
          qbHotelBadge.hidden = false;
          qbHotelText.textContent = (T.qbPickupTag || 'Pickup: {hotel}').replace('{hotel}', savedHotel.name + (savedHotel.pickup ? ' (' + savedHotel.pickup + ')' : ''));
        } else {
          qbHotelBadge.hidden = true;
        }
      }
    };

    $$('[data-qb-step]', qbBox).forEach(function (btn) {
      btn.addEventListener('click', function () {
        var delta = parseInt(btn.getAttribute('data-qb-step'), 10) || 0;
        if (qbQty) { qbQty.value = String(Math.max(1, Math.min(30, getQbQty() + delta))); }
        renderQuickBook();
      });
    });
    if (qbQty) {
      qbQty.addEventListener('input', renderQuickBook);
      qbQty.addEventListener('change', function () { qbQty.value = String(getQbQty()); renderQuickBook(); });
    }
    if (qbDate) {
      qbDate.addEventListener('change', function () {
        var formDate = document.querySelector('#booking input[name="date"]');
        if (formDate && qbDate.value) { formDate.value = qbDate.value; }
      });
    }
    qbHooks.push(renderQuickBook);
    hotelHooks.push(renderQuickBook);
    renderQuickBook();
  }

  document.addEventListener('click', function (ev) {
    var resBtn = ev.target.closest('[data-qb-reserve]');
    if (resBtn && qbBox) {
      ev.preventDefault();
      var q = Math.max(1, Math.min(30, parseInt(($('[data-qb-qty]', qbBox) || {}).value, 10) || 1));
      var id = qbBox.getAttribute('data-qb-id') || '';
      var dispName = qbBox.getAttribute('data-qb-name') || '';
      var unitP = parseFloat(qbBox.getAttribute('data-qb-price'));
      var unitKind = qbBox.getAttribute('data-qb-unit') || '';
      if (id) {
        var ex = cart.filter(function (l) { return l.id === id; })[0];
        if (ex) { ex.qty = q; }
        else { cart.push({ id: id, name: dispName, price: isNaN(unitP) ? null : unitP, qty: q }); }
        saveCart();
        var totEur = (isNaN(unitP) ? 0 : unitP) * q;
        track('add_to_cart', { ecommerce: { currency: 'EUR', value: totEur, items: [{ item_id: id, item_name: dispName, price: unitP || 0, quantity: q }] } });
        track('begin_checkout', { value: totEur, currency: 'EUR' });
        if (granted() && window.fbq) {
          if (isCatId(id) && totEur > 0) {
            window.fbq('track', 'AddToCart', { content_ids: [id], content_name: dispName, content_type: 'product', value: totEur, currency: 'EUR' });
          } else {
            window.fbq('track', 'AddToCart', { content_name: dispName, content_category: 'addon', value: totEur, currency: 'EUR' });
          }
        }
        if (granted() && window.ttq) { window.ttq.track('AddToCart', { content_id: id, value: totEur, currency: 'EUR' }); }
        if (granted() && SC.uetId && window.uetq) { window.uetq.push('event', 'add_to_cart', { event_category: 'ecommerce', revenue_value: totEur, currency: 'EUR' }); }
      }
      var dVal = ($('[data-qb-date]', qbBox) || {}).value || '';
      var fDate = document.querySelector('#booking input[name="date"]');
      if (fDate && dVal) { fDate.value = dVal; }
      var fPeople = document.querySelector('#booking input[name="people"]');
      if (fPeople && unitKind !== 'boat') { fPeople.value = String(q); }
      if (savedHotel && savedHotel.name) {
        var fHotel = document.querySelector('#booking input[name="hotel"]');
        if (fHotel) { fHotel.value = savedHotel.name; }
      }
      var bk = document.getElementById('booking');
      if (bk) { bk.scrollIntoView({ behavior: 'smooth' }); }
      return;
    }

    var waBtn = ev.target.closest('[data-qb-wa]');
    if (waBtn) {
      ev.preventDefault();
      var pl = waBtn.getAttribute('data-placement') || 'item_quickbook';
      var q2 = qbBox ? Math.max(1, Math.min(30, parseInt(($('[data-qb-qty]', qbBox) || {}).value, 10) || 1)) : 1;
      var iId = qbBox ? (qbBox.getAttribute('data-qb-id') || '') : ((SC.item && SC.item.id) || '');
      var iDisp = qbBox ? (qbBox.getAttribute('data-qb-name') || '') : ((SC.item && SC.item.name) || '');
      var iRaw = qbBox ? (qbBox.getAttribute('data-qb-raw-name') || iDisp) : iDisp;
      var uPrice = qbBox ? (parseFloat(qbBox.getAttribute('data-qb-price')) || 0) : ((SC.item && parseFloat(SC.item.price)) || 0);
      var dStr = qbBox ? (($('[data-qb-date]', qbBox) || {}).value || '') : '';
      var hInput = document.querySelector('#booking input[name="hotel"]');
      var hStr = (savedHotel && savedHotel.name) ? (savedHotel.name + (savedHotel.pickup ? ' (' + savedHotel.pickup + ')' : '')) : (hInput && hInput.value ? hInput.value.trim() : '');
      var estEur = uPrice * q2;

      var lines = [T.waGreeting || 'Hello Sea College Dive Club!'];
      if (iDisp) {
        lines.push('• ' + iDisp + ' × ' + q2 + (uPrice > 0 ? ' = ' + fmt(estEur) : ''));
      }
      if (dStr) { lines.push((T.date || 'Date') + ': ' + dStr); }
      if (hStr) { lines.push((T.hotel || 'Hotel') + ': ' + hStr); }
      if (uPrice > 0) { lines.push((T.qbTotal || 'Estimated total') + ': ' + fmt(estEur)); }
      var src = clickAttr.utm_source || 'website';
      var camp = clickAttr.utm_campaign || pl;
      lines.push('', '[ref: ' + src + ' / ' + camp + ' / ' + (SC.lang || 'en') + ']');

      var convVal = estEur > 0 ? estEur : 35;
      track('whatsapp_click', { placement: pl, item_id: iId, value: convVal, currency: 'EUR' });
      track('contact', { method: 'whatsapp', placement: pl });
      sendClickBeacon({
        kind: 'whatsapp_click',
        placement: pl,
        item_id: iId,
        item_name: iRaw,
        value: convVal,
        estimated_eur: convVal,
        date: dStr,
        qty: q2,
        hotel: (savedHotel && savedHotel.name) || (hInput && hInput.value ? hInput.value.trim() : '')
      });
      if (window.scLoadTags) { window.scLoadTags(); }
      if (SC.adsDirect && SC.adsId && SC.adsWhatsappLabel && typeof window.gtag === 'function') {
        window.gtag('event', 'conversion', { send_to: SC.adsId + '/' + SC.adsWhatsappLabel, value: convVal, currency: 'EUR', transaction_id: 'wa_qb_' + Date.now(), transport_type: 'beacon' });
      }
      var qbContact = { content_name: pl, value: convVal, currency: 'EUR' };
      if (iId && isCatId(iId)) { qbContact.content_ids = [iId]; qbContact.content_type = 'product'; }
      if (granted() && window.fbq) { window.fbq('track', 'Contact', qbContact); }
      if (granted() && window.ttq) { window.ttq.track('Contact', { content_name: pl, value: convVal, currency: 'EUR' }); }
      if (granted() && SC.uetId && window.uetq) { window.uetq.push('event', 'whatsapp_click', { event_category: 'contact', revenue_value: convVal, currency: 'EUR' }); }
      window.open('https://wa.me/' + (SC.whatsapp || '') + '?text=' + encodeURIComponent(lines.join('\n')), '_blank', 'noopener');
    }
  });

  /* ---------------- Pre-Arrival Diver Check-In Portal Helpers ---------------- */
  $$('[data-checkin-form]').forEach(function (form) {
    var lastDiveInp = $('[data-last-dive-input]', form);
    var lastDiveWrap = $('[data-last-dive-wrap]', form);
    var lastDiveHint = $('[data-last-dive-hint]', form);
    if (lastDiveInp && lastDiveWrap && lastDiveHint) {
      var evalLastDive = function () {
        var v = String(lastDiveInp.value || '').trim();
        if (!v) { lastDiveWrap.hidden = true; return; }
        var dt = new Date(v + 'T00:00:00');
        if (isNaN(dt.getTime())) { lastDiveWrap.hidden = true; return; }
        var days = Math.floor((Date.now() - dt.getTime()) / 86400000);
        lastDiveWrap.hidden = false;
        if (days > 365) {
          lastDiveHint.className = 'alert alert--error checkin-hint';
          lastDiveHint.textContent = T.adviseReview || 'Last dive > 12 months ago: Scuba Review (€50) required.';
        } else if (days > 180) {
          lastDiveHint.className = 'alert checkin-hint';
          lastDiveHint.textContent = T.adviseCheck || 'Last dive > 6 months ago: Check Dive (€25) recommended.';
        } else {
          lastDiveHint.className = 'alert alert--ok checkin-hint';
          lastDiveHint.textContent = T.adviseRecent || '✓ Dived within 6 months — ready for boat dives!';
        }
      };
      lastDiveInp.addEventListener('change', evalLastDive);
      lastDiveInp.addEventListener('input', evalLastDive);
    }

    var medBox = $('[data-med-check]', form);
    var medAlert = $('[data-med-alert]', form);
    if (medBox && medAlert) {
      var evalMed = function () {
        var anyYes = $$('[data-med-select]', medBox).some(function (s) { return s.value === 'yes'; });
        medAlert.className = 'med-box__status alert ' + (anyYes ? '' : 'alert--ok');
        medAlert.textContent = anyYes ? (T.medYesNote || '') : (T.medAllNo || '');
      };
      $$('[data-med-select]', medBox).forEach(function (s) { s.addEventListener('change', evalMed); });
    }

    var copyBtn = $('[data-copy-checkin]', form);
    if (copyBtn) {
      copyBtn.addEventListener('click', function () {
        var link = copyBtn.getAttribute('data-copy-checkin') || location.href;
        var span = $('span', copyBtn) || copyBtn;
        var orig = span.textContent;
        var done = function () {
          span.textContent = T.checkinCopied || '✓ Link copied!';
          setTimeout(function () { span.textContent = orig; }, 2200);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(link).then(done).catch(done);
        } else {
          done();
        }
      });
    }

    var ciWaBtn = $('[data-checkin-wa]', form);
    if (ciWaBtn) {
      ciWaBtn.addEventListener('click', function () {
        var g = function (n) { var el = form.elements[n]; return el && el.value ? String(el.value).trim() : ''; };
        var out = [T.waGreeting || 'Hello!', T.checkinWaHeader || 'Pre-Arrival Diver Check-In:'];
        if (g('name')) { out.push('• ' + (T.name || 'Name') + ': ' + g('name')); }
        if (g('arrival_date')) { out.push('• ' + (T.date || 'Arrival') + ': ' + g('arrival_date')); }
        if (g('hotel')) { out.push('• ' + (T.hotel || 'Hotel') + ': ' + g('hotel') + (g('room_number') ? ' (#' + g('room_number') + ')' : '')); }
        if (g('divers_count')) { out.push('• Divers: ' + g('divers_count')); }
        if (g('cert_agency')) { out.push('• Cert: ' + g('cert_agency') + (g('cert_number') ? ' #' + g('cert_number') : '')); }
        if (g('logged_dives') || g('last_dive_date')) {
          out.push('• Logged: ' + (g('logged_dives') || '0') + (g('last_dive_date') ? ' (Last: ' + g('last_dive_date') + ')' : ''));
        }
        var gearParts = [];
        if (g('height_cm')) { gearParts.push(g('height_cm') + 'cm'); }
        if (g('weight_kg')) { gearParts.push(g('weight_kg') + 'kg'); }
        if (g('wetsuit_size')) { gearParts.push('Suit ' + g('wetsuit_size')); }
        if (g('bcd_size')) { gearParts.push('BCD ' + g('bcd_size')); }
        if (g('fin_size')) { gearParts.push('Fins EU ' + g('fin_size')); }
        if (gearParts.length) { out.push('• Gear: ' + gearParts.join(' · ')); }
        if (form.elements.nitrox_request && form.elements.nitrox_request.checked) { out.push('• Nitrox 32%: Yes'); }
        var medYes = $$('[data-med-select]', form).some(function (s) { return s.value === 'yes'; });
        out.push('• Medical Pre-Check: ' + (medYes ? 'YES flag (advise form)' : 'All No'));
        if (g('gear_notes')) { out.push('• Notes: ' + g('gear_notes')); }
        var pl = ciWaBtn.getAttribute('data-placement') || 'checkin_wa';
        out.push('', '[ref: ' + (clickAttr.utm_source || 'website') + ' / ' + pl + ' / ' + (SC.lang || 'en') + ']');

        track('whatsapp_click', { placement: pl });
        track('contact', { method: 'whatsapp', placement: pl });
        sendClickBeacon({
          kind: 'whatsapp_click',
          placement: pl,
          item_name: 'Pre-Arrival Diver Check-In',
          date: g('arrival_date'),
          qty: parseInt(g('divers_count'), 10) || 1,
          hotel: g('hotel')
        });
        if (window.scLoadTags) { window.scLoadTags(); }
        if (SC.adsDirect && SC.adsId && SC.adsWhatsappLabel && typeof window.gtag === 'function') {
          var ciPh = g('phone').replace(/[^\d+]/g, '').replace(/^00/, '+');
          if (/^0(1[0125]\d{8})$/.test(ciPh)) { ciPh = '+20' + ciPh.slice(1); }
          else if (ciPh && ciPh.charAt(0) !== '+' && ciPh.length >= 8) { ciPh = '+' + ciPh.replace(/^0+/, ''); }
          if (ciPh && ciPh.length >= 9) { window.gtag('set', 'user_data', { phone_number: ciPh }); }
          window.gtag('event', 'conversion', { send_to: SC.adsId + '/' + SC.adsWhatsappLabel, value: 35, currency: 'EUR', transaction_id: 'wa_ci_' + Date.now(), transport_type: 'beacon' });
        }
        window.open('https://wa.me/' + (SC.whatsapp || '') + '?text=' + encodeURIComponent(out.join('\n')), '_blank', 'noopener');
      });
    }
  });

  /* Re-sync calculator & quiz numbers whenever currency changes */
  $$('[data-currency-switch]').forEach(function (sel) {
    sel.addEventListener('change', function () {
      if (typeof calcHooks !== 'undefined') { calcHooks.forEach(function (fn) { fn(); }); }
      if (typeof quizHooks !== 'undefined') { quizHooks.forEach(function (fn) { fn(); }); }
      if (typeof qbHooks !== 'undefined') { qbHooks.forEach(function (fn) { fn(); }); }
    });
  });

  applyCurrency();

  /* ---------------- Content & Copyright Protection Guard ---------------- */
  function isEditableTarget(el) {
    return !!(el && el.closest && el.closest('input, textarea, select, [contenteditable="true"], .allow-select'));
  }
  var cgToast = null;
  var cgTimer = 0;
  function showCopyGuardToast() {
    var msg = '🔒 © ' + new Date().getFullYear() + ' Sea College Dive Club — Protected Content';
    if (!cgToast) {
      cgToast = document.createElement('div');
      cgToast.className = 'copy-guard-toast';
      cgToast.setAttribute('role', 'status');
      cgToast.setAttribute('aria-live', 'polite');
      document.body.appendChild(cgToast);
    }
    cgToast.textContent = msg;
    cgToast.classList.add('is-visible');
    clearTimeout(cgTimer);
    cgTimer = setTimeout(function () {
      if (cgToast) { cgToast.classList.remove('is-visible'); }
    }, 2400);
  }
  document.addEventListener('contextmenu', function (e) {
    if (isEditableTarget(e.target)) { return; }
    e.preventDefault();
    showCopyGuardToast();
  });
  document.addEventListener('selectstart', function (e) {
    if (isEditableTarget(e.target)) { return; }
    e.preventDefault();
  });
  document.addEventListener('dragstart', function (e) {
    if (isEditableTarget(e.target)) { return; }
    e.preventDefault();
  });
  ['copy', 'cut'].forEach(function (evName) {
    document.addEventListener(evName, function (e) {
      if (isEditableTarget(e.target)) { return; }
      e.preventDefault();
      if (e.clipboardData) {
        e.clipboardData.setData('text/plain', '© ' + new Date().getFullYear() + ' Sea College Dive Club (Sharm El Sheikh) — All rights reserved. ' + location.href);
      }
      showCopyGuardToast();
    });
  });
  document.addEventListener('keydown', function (e) {
    if (isEditableTarget(e.target)) { return; }
    var k = String(e.key || '').toLowerCase();
    var cmd = e.ctrlKey || e.metaKey;
    if (cmd && (k === 'c' || k === 'x' || k === 'u' || k === 's' || k === 'a')) {
      e.preventDefault();
      showCopyGuardToast();
    } else if (e.key === 'F12' || (cmd && e.shiftKey && (k === 'i' || k === 'j' || k === 'c'))) {
      e.preventDefault();
      showCopyGuardToast();
    }
  });

  /* Unregister any stale legacy WordPress Service Worker on returning devices */
  if ('serviceWorker' in navigator && navigator.serviceWorker.getRegistrations) {
    navigator.serviceWorker.getRegistrations().then(function (regs) {
      regs.forEach(function (r) { r.unregister(); });
    }).catch(function () {});
  }
})();
