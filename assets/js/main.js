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
      if (granted() && window.fbq) { window.fbq('track', 'AddToCart', { content_ids: [id], content_name: itemName, content_type: 'product', value: itemPrice, currency: 'EUR' }); }
      if (granted() && window.ttq) { window.ttq.track('AddToCart', { content_id: id, value: itemPrice, currency: 'EUR' }); }
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
    var firePixelView = function () {
      if (window.fbq) {
        window.fbq('track', 'ViewContent', { content_ids: [SC.item.id], content_name: SC.item.name, content_category: SC.item.category || '', content_type: 'product', value: itPrice, currency: 'EUR' });
      }
      if (window.ttq) {
        window.ttq.track('ViewContent', { content_id: SC.item.id, content_name: SC.item.name, value: itPrice, currency: 'EUR' });
      }
    };
    if (granted()) { firePixelView(); }
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
        if (granted() && window.fbq) {
          window.fbq('track', 'AddToCart', { content_ids: ecomItems.map(function (x) { return x.item_id; }), content_type: 'product', value: totalEur, currency: 'EUR' });
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
        out.push('', (L.totalLabel || 'Total') + ' (' + st.divers + ' ' + (L.divers || 'divers') + '): ' + fmt(grand));
        out.push('[ref: website / dive_calculator / ' + (SC.lang || 'en') + ']');
        track('whatsapp_click', { placement: 'dive_calculator' });
        track('contact', { method: 'whatsapp', placement: 'dive_calculator' });
        if (SC.adsDirect && SC.adsId && SC.adsWhatsappLabel && typeof window.gtag === 'function') {
          window.gtag('event', 'conversion', { send_to: SC.adsId + '/' + SC.adsWhatsappLabel, transport_type: 'beacon' });
        }
        if (granted() && window.fbq) { window.fbq('track', 'Contact', { content_name: 'dive_calculator_wa' }); }
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
        lbLink.href = el.getAttribute('data-gal-link') || '/prices.php';
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

    $$('[data-gal-filter]', gal).forEach(function (btn) {
      btn.addEventListener('click', function () {
        var f = btn.getAttribute('data-gal-filter');
        $$('[data-gal-filter]', gal).forEach(function (b) {
          var on = b === btn;
          b.classList.toggle('is-active', on);
          b.setAttribute('aria-pressed', String(on));
        });
        items.forEach(function (el) {
          el.hidden = !(f === 'all' || el.getAttribute('data-gal-cat') === f);
        });
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
          stageImg.src = src;
          var alt = btn.getAttribute('data-alt');
          if (alt) { stageImg.alt = alt; }
          stageImg.hidden = false;
          track('item_gallery_select', { media_type: 'photo', item_id: (SC.item && SC.item.id) || '' });
        }
      });
    });
  });

  /* Re-sync calculator & quiz numbers whenever currency changes */
  $$('[data-currency-switch]').forEach(function (sel) {
    sel.addEventListener('change', function () {
      calcHooks.forEach(function (fn) { fn(); });
      quizHooks.forEach(function (fn) { fn(); });
    });
  });

  applyCurrency();
})();
