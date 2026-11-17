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
    var p = params || {};
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

  /* ---------------- Language switch ---------------- */
  $$('[data-lang-switch]').forEach(function (sel) {
    sel.addEventListener('change', function () { if (sel.value) { location.href = sel.value; } });
  });

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
    if (!box) { return; }
    var list = $('[data-cart-items]', box);
    var empty = $('[data-cart-empty]', box);
    var total = $('[data-cart-total]', box);
    list.innerHTML = '';
    cart.forEach(function (l, i) {
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
      track('add_to_cart', { ecommerce: { currency: 'EUR', items: [{ item_id: id, item_name: add.getAttribute('data-name'), price: parseFloat(add.getAttribute('data-price')) || 0, quantity: 1 }] } });
      if (granted() && window.fbq) { window.fbq('track', 'AddToCart', { content_ids: [id], currency: 'EUR' }); }
      if (granted() && window.ttq) { window.ttq.track('AddToCart', { content_id: id, currency: 'EUR' }); }
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
              location.href = res.redirect; return;
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

  /* ---------------- WhatsApp from form + cart ---------------- */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-wa-from-form]');
    if (!btn) { return; }
    var form = btn.closest('form');
    var get = function (n) { var el = form && form.elements[n]; return el && el.value ? String(el.value).trim() : ''; };
    var lines = [T.waGreeting || 'Hello!'];
    if (cart.length) {
      cart.forEach(function (l) { lines.push('• ' + l.name + ' × ' + l.qty + (l.price ? ' (' + fmt(l.price * l.qty) + ')' : '')); });
    }
    [['name', T.name], ['interest', T.interest], ['people', T.people], ['date', T.date], ['hotel', T.hotel], ['level', T.level]].forEach(function (p) {
      var v = get(p[0]);
      if (p[0] === 'level' && v && form.elements.level.selectedIndex > 0) { v = form.elements.level.options[form.elements.level.selectedIndex].text; }
      if (v) { lines.push((p[1] || p[0]) + ': ' + v); }
    });
    var msg = get('message'); if (msg) { lines.push('', msg); }
    var src = get('utm_source') || 'website';
    var camp = get('utm_campaign') || (btn.getAttribute('data-placement') || 'form');
    lines.push('', '[ref: ' + src + ' / ' + camp + ' / ' + (SC.lang || 'en') + ']');
    window.open('https://wa.me/' + (SC.whatsapp || '') + '?text=' + encodeURIComponent(lines.join('\n')), '_blank', 'noopener');
  });

  /* ---------------- Click tracking ---------------- */
  document.addEventListener('click', function (e) {
    var a = e.target.closest('a, [data-track]');
    if (!a) { return; }
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
    track(kind, params);
    if (kind === 'cta_click' || kind === 'outbound_link') { return; }
    // whatsapp / phone / email = contact intent
    track('contact', { method: kind.replace('_click', ''), placement: params.placement });
    if (SC.adsDirect && SC.adsId && typeof window.gtag === 'function') {
      if (kind === 'whatsapp_click' && SC.adsWhatsappLabel) {
        window.gtag('event', 'conversion', { send_to: SC.adsId + '/' + SC.adsWhatsappLabel, transport_type: 'beacon' });
      } else if (kind === 'phone_click' && SC.adsCallLabel) {
        window.gtag('event', 'conversion', { send_to: SC.adsId + '/' + SC.adsCallLabel, transport_type: 'beacon' });
      }
    }
    if (granted() && window.fbq) { window.fbq('track', 'Contact', { content_name: kind }); }
    if (granted() && window.ttq) { window.ttq.track('Contact', { content_name: kind }); }
  });

  /* ---------------- Click-to-load Google Map ---------------- */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-map-load]');
    if (!b) { return; }
    var box = b.closest('[data-map-src]');
    var f = document.createElement('iframe');
    f.src = box.getAttribute('data-map-src');
    f.title = box.getAttribute('data-map-title') || 'Map';
    f.loading = 'lazy';
    f.referrerPolicy = 'no-referrer-when-downgrade';
    box.innerHTML = '';
    box.appendChild(f);
    box.classList.add('is-loaded');
  });

  applyCurrency();
})();
