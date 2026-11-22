<?php
/**
 * Sea College Dive Club — Unified Role-Based Mobile App & Big-Data Command Center (`/app` -> `erp-app.php`)
 * Strictly `noindex, nofollow` internal company PWA connected to `handlers/erp_api.php` & `handlers/erp_export.php`.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/erp_db.php';

sc_ensure_session();
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store, must-revalidate');
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" id="htmlRoot">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#0a3d62">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <link rel="manifest" href="/assets/erp/manifest.json">
  <link rel="icon" href="/assets/images/logo.png">
  <title>Sea College Dive Club — Unified Mobile ERP & Big-Data Command Center</title>
  <style>
    :root {
      --bg: #061526;
      --surface: #0b2239;
      --surface-2: #112e4a;
      --border: rgba(125, 211, 252, 0.16);
      --text: #f0f9ff;
      --muted: #94a3b8;
      --cyan: #00d2ff;
      --teal: #10b981;
      --amber: #f59e0b;
      --rose: #f43f5e;
      --indigo: #6366f1;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Cairo", Tahoma, sans-serif;
      background: radial-gradient(circle at top right, #0e3358 0%, var(--bg) 60%);
      color: var(--text);
      min-height: 100vh;
      padding-bottom: 84px;
    }
    .topbar {
      position: sticky; top: 0; z-index: 50;
      background: rgba(6, 21, 38, 0.92);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--border);
      padding: 12px 16px;
      display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;
    }
    .brand { display: flex; align-items: center; gap: 10px; }
    .brand-badge {
      width: 38px; height: 38px; border-radius: 10px;
      background: linear-gradient(135deg, var(--cyan), #0284c7);
      color: #031525; font-weight: 900; display: grid; place-items: center; font-size: 15px;
    }
    .brand h1 { font-size: 15px; font-weight: 800; letter-spacing: 0.2px; }
    .brand p { font-size: 11px; color: var(--muted); }
    .container { max-width: 1280px; margin: 0 auto; padding: 16px; }
    .btn {
      border: 1px solid var(--border); background: var(--surface-2); color: var(--text);
      padding: 8px 13px; border-radius: 9px; font-size: 12px; font-weight: 700;
      cursor: pointer; transition: 0.15s; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
    }
    .btn:hover { border-color: var(--cyan); transform: translateY(-1px); }
    .btn-primary { background: linear-gradient(135deg, #00d2ff, #0284c7); color: #041322; border: none; }
    .btn-emerald { background: linear-gradient(135deg, #10b981, #059669); color: #fff; border: none; }
    .btn-amber { background: rgba(245,158,11,0.16); color: #fcd34d; border-color: rgba(245,158,11,0.4); }
    .pill {
      display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 700;
      background: rgba(0, 210, 255, 0.14); color: var(--cyan); border: 1px solid rgba(0,210,255,0.3);
    }
    .grid { display: grid; gap: 14px; }
    .grid-kpi { grid-template-columns: repeat(auto-fit, minmax(185px, 1fr)); }
    .grid-2 { grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); }
    .grid-3 { grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
    .card {
      background: rgba(11, 34, 57, 0.86);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 15px;
      box-shadow: 0 10px 28px rgba(0,0,0,0.25);
    }
    .kpi-label { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; }
    .kpi-val { font-size: 22px; font-weight: 900; margin-top: 5px; font-variant-numeric: tabular-nums; }
    .kpi-sub { font-size: 11px; color: var(--teal); margin-top: 4px; }
    .tabs {
      display: flex; gap: 8px; overflow-x: auto; padding-bottom: 8px; margin-bottom: 14px;
      border-bottom: 1px solid var(--border);
    }
    .tab-btn {
      padding: 8px 14px; border-radius: 10px; font-size: 12px; font-weight: 700; white-space: nowrap;
      background: transparent; color: var(--muted); border: 1px solid transparent; cursor: pointer;
    }
    .tab-btn.active { background: rgba(0,210,255,0.16); color: var(--cyan); border-color: rgba(0,210,255,0.4); }
    .horizon-bar {
      display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between;
      margin-bottom: 14px; padding: 12px; border-radius: 12px; background: rgba(17,46,74,0.65); border: 1px solid var(--border);
    }
    .seg-group { display: inline-flex; background: var(--bg); padding: 3px; border-radius: 9px; border: 1px solid var(--border); flex-wrap: wrap; }
    .seg-btn {
      border: none; background: transparent; color: var(--muted); padding: 6px 11px; border-radius: 7px;
      font-size: 12px; font-weight: 700; cursor: pointer;
    }
    .seg-btn.active { background: var(--cyan); color: #041322; }
    select, input {
      background: var(--bg); color: var(--text); border: 1px solid var(--border);
      padding: 8px 10px; border-radius: 8px; font-size: 13px; width: 100%;
    }
    label { font-size: 12px; color: var(--muted); display: block; margin-bottom: 4px; }
    .table-wrap { overflow-x: auto; border-radius: 10px; border: 1px solid var(--border); }
    table { width: 100%; border-collapse: collapse; font-size: 12px; }
    th, td { padding: 9px 10px; text-align: start; border-bottom: 1px solid rgba(148,163,184,0.12); white-space: nowrap; }
    th { background: rgba(6,21,38,0.85); color: var(--cyan); font-weight: 800; position: sticky; top: 0; }
    tr:hover td { background: rgba(255,255,255,0.03); }
    .num { font-variant-numeric: tabular-nums; font-weight: 700; }
    .pos { color: #34d399; }
    .neg { color: #fb7185; }
    .login-box { max-width: 480px; margin: 36px auto; }
    .export-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 10px; margin-top: 10px; }
    .export-item {
      background: rgba(6,21,38,0.65); border: 1px solid var(--border); border-radius: 11px; padding: 11px;
      display: flex; flex-direction: column; justify-content: space-between; gap: 8px;
    }
    .toast {
      position: fixed; bottom: 20px; right: 20px; z-index: 100;
      background: #065f46; color: #fff; padding: 11px 16px; border-radius: 10px;
      font-size: 13px; font-weight: 700; display: none; box-shadow: 0 8px 24px rgba(0,0,0,0.4);
    }
    @media (max-width: 640px) {
      .kpi-val { font-size: 18px; }
      .container { padding: 10px; }
    }
  </style>
</head>
<body>

<header class="topbar">
  <div class="brand">
    <div class="brand-badge">SC</div>
    <div>
      <h1 id="appTitle">Sea College Dive Club — Unified Ecosystem App</h1>
      <p id="appSub">Big-Data SQL Warehouse • Role-Based Employee & Owner Command Center</p>
    </div>
  </div>
  <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
    <span id="userBadge" class="pill" style="display:none;"></span>
    <button type="button" class="btn" id="langBtn" onclick="toggleLang()">العربية RTL</button>
    <a href="/" class="btn" id="siteBtn">🌐 Website</a>
    <button type="button" class="btn btn-amber" id="logoutBtn" style="display:none;" onclick="doLogout()">Logout</button>
  </div>
</header>

<main class="container">
  <!-- ================= LOGIN VIEW ================= -->
  <section id="loginView" class="login-box card" style="display:none;">
    <h2 style="font-size:18px;margin-bottom:6px;" id="loginHeading">Employee & Owner Sign In</h2>
    <p style="font-size:12px;color:var(--muted);margin-bottom:14px;" id="loginDesc">
      Sign in with your assigned Sea College role account. Every user sees only the dashboards, boats, branches, and permissions authorized for their job.
    </p>
    <form onsubmit="doLogin(event)" class="grid" style="gap:12px;">
      <div>
        <label id="lblUser">Username</label>
        <input type="text" id="inpUser" required autocomplete="username" placeholder="e.g. islam.samir, nadia.acc, fady.sales">
      </div>
      <div>
        <label id="lblPass">Password</label>
        <input type="password" id="inpPass" required autocomplete="current-password" placeholder="••••••••••••">
      </div>
      <div id="loginErr" style="color:var(--rose);font-size:12px;display:none;"></div>
      <button type="submit" class="btn btn-primary" style="justify-content:center;padding:11px;font-size:14px;" id="btnSignIn">
        🔐 Sign In to Role Dashboard
      </button>
    </form>

    <details style="margin-top:16px;border-top:1px solid var(--border);padding-top:12px;">
      <summary style="cursor:pointer;font-size:12px;color:var(--cyan);font-weight:700;">
        👥 Quick Role Directory (Select Employee / Owner Account)
      </summary>
      <p style="font-size:11px;color:var(--muted);margin:6px 0;">
        Tap any role account below to pre-fill username during onboarding (admin can rotate passwords anytime):
      </p>
      <div id="roleDirectoryList" style="display:grid;gap:6px;max-height:260px;overflow-y:auto;margin-top:8px;"></div>
    </details>
  </section>

  <!-- ================= AUTHENTICATED APP SHELL ================= -->
  <section id="appView" style="display:none;">
    <div id="roleBanner" class="card" style="margin-bottom:14px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;background:linear-gradient(90deg,rgba(0,210,255,0.12),rgba(16,185,129,0.08));">
      <div>
        <div style="font-size:16px;font-weight:800;" id="welcomeName"></div>
        <div style="font-size:12px;color:var(--muted);margin-top:3px;" id="welcomeScope"></div>
      </div>
      <div id="permPills" style="display:flex;gap:6px;flex-wrap:wrap;"></div>
    </div>

    <nav id="navTabs" class="tabs"></nav>
    <div id="tabContent"></div>
  </section>
</main>

<div id="toast" class="toast"></div>

<script>
let STATE = {
  lang: 'en',
  csrf: <?= json_encode($csrf) ?>,
  user: null,
  perms: [],
  activeTab: '',
  horizon: 'monthly',
  yearFilter: '',
  monthFilter: '',
  bi: null,
  ws: null,
  directory: []
};

function fillAccount(uname) {
  document.getElementById('inpUser').value = uname;
  const pw = document.getElementById('inpPass');
  pw.value = '';
  pw.focus();
}

function fmt(n, dec = 0) {
  const v = Number(n || 0);
  return v.toLocaleString('en-US', { minimumFractionDigits: dec, maximumFractionDigits: dec });
}
function esc(s) {
  const d = document.createElement('div');
  d.textContent = s == null ? '' : String(s);
  return d.innerHTML;
}
function showToast(msg) {
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.style.display = 'block';
  setTimeout(() => { el.style.display = 'none'; }, 3200);
}
function hasPerm(...codes) {
  return codes.some((c) => STATE.perms.includes(c));
}

function toggleLang() {
  STATE.lang = STATE.lang === 'en' ? 'ar' : 'en';
  const root = document.getElementById('htmlRoot');
  root.lang = STATE.lang;
  root.dir = STATE.lang === 'ar' ? 'rtl' : 'ltr';
  document.getElementById('langBtn').textContent = STATE.lang === 'ar' ? 'English LTR' : 'العربية RTL';
  document.getElementById('appTitle').textContent = STATE.lang === 'ar'
    ? 'سي كولدج دايف كلوب — التطبيق الموحد للشركة والملاك'
    : 'Sea College Dive Club — Unified Ecosystem App';
  renderApp();
}

async function apiGet(params) {
  const qs = new URLSearchParams(params).toString();
  const r = await fetch('/handlers/erp_api.php?' + qs, { credentials: 'same-origin' });
  return r.json();
}
async function apiPost(payload) {
  const r = await fetch('/handlers/erp_api.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': STATE.csrf },
    body: JSON.stringify({ ...payload, csrf_token: STATE.csrf })
  });
  return r.json();
}

async function boot() {
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/assets/erp/sw.js').catch(() => {});
  }
  const dirRes = await apiGet({ action: 'directory_roles' });
  if (dirRes.ok) {
    STATE.directory = dirRes.accounts || [];
    if (dirRes.csrf_token) STATE.csrf = dirRes.csrf_token;
    renderDirectory();
  }
  const me = await apiGet({ action: 'me' });
  if (me.csrf_token) STATE.csrf = me.csrf_token;
  if (me.authenticated) {
    STATE.user = me.user;
    STATE.perms = me.permissions || [];
    await loadUserData();
  } else {
    document.getElementById('loginView').style.display = 'block';
    document.getElementById('appView').style.display = 'none';
  }
}

function renderDirectory() {
  const box = document.getElementById('roleDirectoryList');
  if (!box) return;
  box.innerHTML = STATE.directory.map((a) => `
    <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 9px;border-radius:8px;background:rgba(6,21,38,0.7);border:1px solid var(--border);">
      <div>
        <div style="font-size:12px;font-weight:700;">${esc(STATE.lang === 'ar' ? a.display_name_ar : a.display_name_en)}</div>
        <div style="font-size:11px;color:var(--muted);">@${esc(a.username)} • ${esc(a.role_code)} (${esc(a.branch_scope)})</div>
      </div>
      <button type="button" class="btn" onclick="fillAccount('${esc(a.username)}')">Select</button>
    </div>
  `).join('');
}

async function doLogin(e) {
  e.preventDefault();
  const errEl = document.getElementById('loginErr');
  errEl.style.display = 'none';
  const username = document.getElementById('inpUser').value.trim();
  const password = document.getElementById('inpPass').value;
  const res = await apiPost({ action: 'login', username, password });
  if (!res.ok) {
    errEl.textContent = res.error || 'Login failed';
    errEl.style.display = 'block';
    return;
  }
  STATE.user = res.user;
  STATE.perms = res.permissions || [];
  STATE.csrf = res.csrf_token || STATE.csrf;
  STATE.activeTab = '';
  await loadUserData();
}

async function doLogout() {
  await apiGet({ action: 'logout' });
  STATE.user = null;
  STATE.perms = [];
  STATE.bi = null;
  STATE.ws = null;
  document.getElementById('userBadge').style.display = 'none';
  document.getElementById('logoutBtn').style.display = 'none';
  document.getElementById('appView').style.display = 'none';
  document.getElementById('loginView').style.display = 'block';
}

async function loadUserData() {
  document.getElementById('loginView').style.display = 'none';
  document.getElementById('appView').style.display = 'block';
  document.getElementById('logoutBtn').style.display = 'inline-flex';
  const ub = document.getElementById('userBadge');
  ub.style.display = 'inline-block';
  ub.textContent = (STATE.lang === 'ar' ? STATE.user.display_name_ar : STATE.user.display_name_en) + ' [' + STATE.user.role_code + ']';

  const promises = [apiGet({ action: 'role_workspace' })];
  if (hasPerm('bi.view_executive')) {
    promises.push(apiGet({ action: 'executive_bi', horizon: STATE.horizon, year: STATE.yearFilter, month: STATE.monthFilter }));
  }
  const [wsRes, biRes] = await Promise.all(promises);
  if (wsRes && wsRes.ok) STATE.ws = wsRes;
  if (biRes && biRes.ok) STATE.bi = biRes;
  renderApp();
}

async function changeHorizon(h, yr = null, mo = null) {
  if (h) STATE.horizon = h;
  if (yr !== null) { STATE.yearFilter = yr; STATE.monthFilter = ''; }
  if (mo !== null) { STATE.monthFilter = mo; }
  const biRes = await apiGet({ action: 'executive_bi', horizon: STATE.horizon, year: STATE.yearFilter, month: STATE.monthFilter });
  if (biRes.ok) {
    STATE.bi = biRes;
    renderApp();
  }
}

function exportUrl(report, format) {
  const qs = new URLSearchParams({
    report,
    format,
    horizon: STATE.horizon,
    year: STATE.yearFilter,
    month: STATE.monthFilter
  });
  return '/handlers/erp_export.php?' + qs.toString();
}

function buildTabsForUser() {
  const ar = STATE.lang === 'ar';
  const tabs = [];
  if (hasPerm('bi.view_executive')) {
    tabs.push({ id: 'exec_bi', label: ar ? '📊 لوحة الملاك والاتجاهات' : '📊 Owner BI & Trends' });
    tabs.push({ id: 'exports_hub', label: ar ? '📥 تصدير الشيتات والتقارير' : '📥 Export Sheets (.xlsx / .csv)' });
    tabs.push({ id: 'profitability', label: ar ? '🏆 الأعلى مبيعاً وربحية الرحلات والمراكب' : '🏆 Best Sellers, Trips & Boats ROI' });
  }
  if (hasPerm('bi.view_partner_fleet') && !hasPerm('bi.view_executive')) {
    tabs.push({ id: 'partner_fleet', label: ar ? '🛥️ حصص المراكب وعمرة الازق' : '🛥️ My Partner Vessels & Drydock' });
  }
  if (hasPerm('gl.view_all_ledgers')) {
    tabs.push({ id: 'accounting_gl', label: ar ? '📚 الحسابات والعملاء والمرتبات' : '📚 Ledgers, AR/AP & Payroll' });
  }
  if (hasPerm('safe.view_balances')) {
    tabs.push({ id: 'treasury_safe', label: ar ? '💵 الخزينة والعهد متعددة العملات' : '💵 Multi-Currency Safe & Cashbox' });
  }
  if (hasPerm('ops.view_manifests', 'jetty.scan_boarding_collect_rest', 'sales.view_own_sales', 'crew.view_own_manifest')) {
    tabs.push({ id: 'vouchers_ops', label: ar ? '🎫 الحجوزات والتشغيل والسقالة' : '🎫 Reservations, Dispatch & Jetty' });
  }
  if (hasPerm('sales.create_voucher')) {
    tabs.push({ id: 'new_voucher', label: ar ? '➕ إصدار فوتشر باركود' : '➕ Issue QR Voucher' });
  }
  if (hasPerm('jetty.manage_gear_photo')) {
    tabs.push({ id: 'gear_assets', label: ar ? '🤿 جرد معدات الغوص S.C#' : '🤿 Dive Gear Assets (S.C #)' });
  }
  if (hasPerm('crm.manage_leads_ads')) {
    tabs.push({ id: 'website_crm', label: ar ? '🌐 حجوزات الموقع والواتساب والإعلانات' : '🌐 Website Leads, Check-In & Ads CRM' });
  }
  return tabs;
}

function renderApp() {
  if (!STATE.user) {
    renderDirectory();
    return;
  }
  const ar = STATE.lang === 'ar';
  document.getElementById('welcomeName').textContent = ar ? STATE.user.display_name_ar : STATE.user.display_name_en;
  document.getElementById('welcomeScope').textContent =
    `${ar ? STATE.user.role_name_ar : STATE.user.role_name_en} • Branch Scope: ${STATE.user.branch_scope} • Vessel Scope: ${STATE.user.vessel_scope}`;
  document.getElementById('permPills').innerHTML = STATE.perms.slice(0, 6).map((p) => `<span class="pill">${esc(p)}</span>`).join('')
    + (STATE.perms.length > 6 ? `<span class="pill">+${STATE.perms.length - 6} more</span>` : '');

  const tabs = buildTabsForUser();
  if (!STATE.activeTab || !tabs.some((t) => t.id === STATE.activeTab)) {
    STATE.activeTab = tabs[0] ? tabs[0].id : '';
  }
  document.getElementById('navTabs').innerHTML = tabs.map((t) => `
    <button type="button" class="tab-btn ${STATE.activeTab === t.id ? 'active' : ''}" onclick="STATE.activeTab='${t.id}';renderApp();">${esc(t.label)}</button>
  `).join('');

  const box = document.getElementById('tabContent');
  if (STATE.activeTab === 'exec_bi' && STATE.bi) {
    box.innerHTML = renderExecBi(ar);
    drawTrendChart();
  } else if (STATE.activeTab === 'exports_hub') {
    box.innerHTML = renderExportsHub(ar);
  } else if (STATE.activeTab === 'profitability' && STATE.bi) {
    box.innerHTML = renderProfitabilityTab(ar);
  } else if (STATE.activeTab === 'partner_fleet') {
    box.innerHTML = renderPartnerFleet(ar);
  } else if (STATE.activeTab === 'accounting_gl') {
    box.innerHTML = renderAccountingGl(ar);
  } else if (STATE.activeTab === 'treasury_safe') {
    box.innerHTML = renderTreasurySafe(ar);
  } else if (STATE.activeTab === 'vouchers_ops') {
    box.innerHTML = renderVouchersOps(ar);
  } else if (STATE.activeTab === 'new_voucher') {
    box.innerHTML = renderNewVoucherForm(ar);
  } else if (STATE.activeTab === 'gear_assets') {
    box.innerHTML = renderGearAssets(ar);
  } else if (STATE.activeTab === 'website_crm') {
    box.innerHTML = renderWebsiteCrm(ar);
  }
}

function renderHorizonBar(ar) {
  const bi = STATE.bi || { available_months: [] };
  const horizons = [
    ['daily', ar ? 'يومي' : 'Daily'],
    ['weekly', ar ? 'أسبوعي' : 'Weekly'],
    ['monthly', ar ? 'شهري' : 'Monthly'],
    ['quarterly', ar ? 'ربع سنوي' : 'Quarterly'],
    ['yearly', ar ? 'سنوي' : 'Yearly']
  ];
  return `
    <div class="horizon-bar">
      <div class="seg-group">
        ${horizons.map(([k, lbl]) => `<button type="button" class="seg-btn ${STATE.horizon === k ? 'active' : ''}" onclick="changeHorizon('${k}')">${lbl}</button>`).join('')}
      </div>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <select style="width:auto;" onchange="changeHorizon(null, this.value, '')">
          <option value="" ${STATE.yearFilter === '' ? 'selected' : ''}>${ar ? 'كل السنوات (2024–2026)' : 'All Years (2024–2026)'}</option>
          <option value="2025" ${STATE.yearFilter === '2025' ? 'selected' : ''}>2025</option>
          <option value="2026" ${STATE.yearFilter === '2026' ? 'selected' : ''}>2026</option>
        </select>
        <select style="width:auto;" onchange="changeHorizon(null, null, this.value)">
          <option value="" ${STATE.monthFilter === '' ? 'selected' : ''}>${ar ? 'كل الشهور' : 'All Months'}</option>
          ${(bi.available_months || []).map((m) => `<option value="${esc(m)}" ${STATE.monthFilter === m ? 'selected' : ''}>${esc(m)}</option>`).join('')}
        </select>
      </div>
    </div>
  `;
}

function renderExecBi(ar) {
  const k = STATE.bi.kpis || {};
  const wh = STATE.bi.warehouse_counts || {};
  const marginPct = k.total_revenue_egp > 0 ? ((k.net_profit_egp / k.total_revenue_egp) * 100).toFixed(1) : '0.0';
  return `
    ${renderHorizonBar(ar)}
    <div class="grid grid-kpi" style="margin-bottom:14px;">
      <div class="card">
        <div class="kpi-label">${ar ? 'إجمالي الإيرادات (P&L)' : 'Total Revenue (P&L)'}</div>
        <div class="kpi-val">${fmt(k.total_revenue_egp)} <span style="font-size:12px;color:var(--muted);">EGP</span></div>
        <div class="kpi-sub">≈ €${fmt((k.total_revenue_egp || 0) / 54)} EUR Equiv</div>
      </div>
      <div class="card">
        <div class="kpi-label">${ar ? 'إجمالي المصروفات والتكاليف' : 'Total Operating Expenses'}</div>
        <div class="kpi-val" style="color:#fcd34d;">${fmt(k.total_expense_egp)} <span style="font-size:12px;color:var(--muted);">EGP</span></div>
        <div class="kpi-sub">${fmt(wh.expense_vouchers)} ${ar ? 'إذن صرف مسجل' : 'Expense Vouchers'}</div>
      </div>
      <div class="card">
        <div class="kpi-label">${ar ? 'صافي الربح التشغيلي' : 'Net Operating Profit'}</div>
        <div class="kpi-val ${k.net_profit_egp >= 0 ? 'pos' : 'neg'}">${fmt(k.net_profit_egp)} <span style="font-size:12px;color:var(--muted);">EGP</span></div>
        <div class="kpi-sub">${ar ? 'هامش صافي الربح:' : 'Net Profit Margin:'} ${marginPct}%</div>
      </div>
      <div class="card">
        <div class="kpi-label">${ar ? 'إجمالي الحجوزات والأفراد' : 'Total Vouchers & PAX'}</div>
        <div class="kpi-val">${fmt(k.total_pax)} <span style="font-size:12px;color:var(--muted);">PAX</span></div>
        <div class="kpi-sub">${fmt(k.vouchers_count)} Vouchers (VO.NO)</div>
      </div>
      <div class="card">
        <div class="kpi-label">${ar ? 'مستودع البيانات الموحد SQL' : 'Unified SQL Big-Data Rows'}</div>
        <div class="kpi-val" style="color:var(--cyan);">${fmt(wh.raw_rows_archived)}</div>
        <div class="kpi-sub">${fmt(wh.workbooks_ingested)} Sheets + Live Website Sync</div>
      </div>
    </div>

    <div class="card" style="margin-bottom:14px;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;flex-wrap:wrap;gap:8px;">
        <h3 style="font-size:14px;">${ar ? 'منحنى اتجاه الإيرادات والمصروفات وصافي الربح' : 'Performance Trend — Revenue vs Expenses vs Net Profit'} (${STATE.horizon.toUpperCase()})</h3>
        <div style="display:flex;gap:8px;">
          <a class="btn btn-emerald" href="${exportUrl('pnl', 'xlsx')}">⬇ P&L .xlsx</a>
          <a class="btn" href="${exportUrl('cashflow', 'xlsx')}">⬇ Cash Flow .xlsx</a>
        </div>
      </div>
      <canvas id="trendCanvas" height="230" style="width:100%;height:230px;display:block;"></canvas>
    </div>

    <div class="card">
      <h3 style="font-size:14px;margin-bottom:10px;">${ar ? 'جدول الأداء التفصيلي حسب الفترة الزمنية' : 'Period-by-Period Financial Breakdown'}</h3>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>${ar ? 'الفترة' : 'Period'}</th>
              <th>${ar ? 'الإيرادات (EGP)' : 'Revenue (EGP)'}</th>
              <th>${ar ? 'المصروفات (EGP)' : 'Expenses (EGP)'}</th>
              <th>${ar ? 'صافي الربح (EGP)' : 'Net Profit (EGP)'}</th>
              <th>Cyrene</th>
              <th>Travco Jetty</th>
            </tr>
          </thead>
          <tbody>
            ${(STATE.bi.trend_series || []).map((r) => `
              <tr>
                <td class="num">${esc(r.period_key)}</td>
                <td class="num">${fmt(r.revenue_egp)}</td>
                <td class="num">${fmt(r.expense_egp)}</td>
                <td class="num ${r.net_profit_egp >= 0 ? 'pos' : 'neg'}">${fmt(r.net_profit_egp)}</td>
                <td class="num">${fmt(r.cyrene_profit_egp)}</td>
                <td class="num">${fmt(r.jetty_profit_egp)}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

function drawTrendChart() {
  const c = document.getElementById('trendCanvas');
  if (!c || !STATE.bi) return;
  const data = STATE.bi.trend_series || [];
  const dpr = window.devicePixelRatio || 1;
  const w = c.clientWidth || 760;
  const h = 230;
  c.width = w * dpr;
  c.height = h * dpr;
  const ctx = c.getContext('2d');
  ctx.scale(dpr, dpr);
  ctx.clearRect(0, 0, w, h);
  if (!data.length) return;

  const vals = data.flatMap((d) => [Number(d.revenue_egp || 0), Number(d.expense_egp || 0), Number(d.net_profit_egp || 0)]);
  const maxV = Math.max(1, ...vals);
  const minV = Math.min(0, ...vals);
  const padL = 58, padR = 18, padT = 18, padB = 34;
  const plotW = w - padL - padR;
  const plotH = h - padT - padB;
  const yOf = (v) => padT + plotH - ((v - minV) / (maxV - minV || 1)) * plotH;

  ctx.strokeStyle = 'rgba(148,163,184,0.16)';
  ctx.fillStyle = '#94a3b8';
  ctx.font = '10px system-ui';
  for (let i = 0; i <= 4; i++) {
    const gv = minV + ((maxV - minV) * i) / 4;
    const gy = yOf(gv);
    ctx.beginPath(); ctx.moveTo(padL, gy); ctx.lineTo(w - padR, gy); ctx.stroke();
    ctx.fillText((gv / 1e6).toFixed(1) + 'M', 6, gy + 3);
  }

  const step = plotW / Math.max(1, data.length);
  const bw = Math.max(4, Math.min(22, step * 0.32));
  data.forEach((d, idx) => {
    const cx = padL + idx * step + step * 0.5;
    const y0 = yOf(0);
    const yr = yOf(Number(d.revenue_egp || 0));
    const ye = yOf(Number(d.expense_egp || 0));
    ctx.fillStyle = 'rgba(0, 210, 255, 0.75)';
    ctx.fillRect(cx - bw - 1, Math.min(y0, yr), bw, Math.abs(y0 - yr));
    ctx.fillStyle = 'rgba(245, 158, 11, 0.68)';
    ctx.fillRect(cx + 1, Math.min(y0, ye), bw, Math.abs(y0 - ye));
    if (data.length <= 18 || idx % Math.ceil(data.length / 12) === 0) {
      ctx.fillStyle = '#94a3b8';
      ctx.fillText(String(d.period_key).slice(-5), cx - 14, h - 10);
    }
  });

  ctx.strokeStyle = '#10b981';
  ctx.lineWidth = 2.5;
  ctx.beginPath();
  data.forEach((d, idx) => {
    const cx = padL + idx * step + step * 0.5;
    const yn = yOf(Number(d.net_profit_egp || 0));
    if (idx === 0) ctx.moveTo(cx, yn); else ctx.lineTo(cx, yn);
  });
  ctx.stroke();
}

function renderExportsHub(ar) {
  const reports = [
    { key: 'pnl', icon: '📊', title: ar ? 'قائمة الأرباح والخسائر (P&L)' : 'Profit & Loss Statement (P&L)', desc: ar ? 'ملخص الفروع + 1,811 سطر تفصيلي نقدي ومستحق' : 'Period summary + all 1,811 Cash & Accrued P&L lines' },
    { key: 'cashflow', icon: '💵', title: ar ? 'التدفقات النقدية وإذون الصرف' : 'Multi-Currency Cash Flow & Expenses', desc: ar ? 'حركة الخزينة اليومية (LE/$/€/£/VISA) + 5,692 إذن صرف' : 'Daily multi-currency cashbox + 5,692 expense vouchers' },
    { key: 'best_sellers', icon: '🏆', title: ar ? 'الأعلى مبيعاً في الشهر والفترة' : 'Best Sellers of the Month / Period', desc: ar ? 'ترتيب مناديب المبيعات والشركات والجنسيات وأعلى 100 فندق' : 'Top Sales Reps, B2B Agencies, Nationalities & Top 100 Hotels' },
    { key: 'profitable_trips', icon: '🤿', title: ar ? 'أكثر الرحلات والكورسات ربحية' : 'Most Profitable Trips, Tours & Courses', desc: ar ? 'الإيراد الفعلي مقابل التكلفة المباشرة وصافي هامش الربح %' : 'Revenue vs Direct COGS, Net Margin EGP & Margin % per activity' },
    { key: 'profitable_boats', icon: '🛥️', title: ar ? 'ربحية المراكب وعمرة الازق والأصول' : 'Most Profitable Boats, Drydock & Assets', desc: ar ? 'أوشينو، عمر 4، نزار، سحس، الاسبيد + 1,555 طلعة مركب + العدة' : 'Fleet ROI + 1,555 daily boat trips + 6.56M EGP Drydock + 119 Gear tags' },
    { key: 'full_data_pack', icon: '🗂️', title: ar ? 'سجل الحجوزات الكامل والعملاء والمرتبات' : 'Full Row-Level Reservations, AR/AP & Payroll', desc: ar ? '12,054 فوتشر حجز + 63 حساب عميل ومورد + 758 سجل مرتبات' : '12,054 vouchers + 63 AR/AP accounts + 758 monthly payroll records' }
  ];
  return `
    ${renderHorizonBar(ar)}
    <div class="card">
      <h3 style="font-size:16px;margin-bottom:6px;">${ar ? 'مركز تصدير الشيتات والتقارير التنفيذية بضغطة واحدة' : '1-Click Executive & Row-Level Sheet Exports (.xlsx & .csv)'}</h3>
      <p style="font-size:12px;color:var(--muted);">
        ${ar ? 'اختر الفترة الزمنية من الأعلى (يومي / أسبوعي / شهري / ربع سنوي / سنوي أو شهر محدد) ثم اضغط لتحميل ملف Excel متعدد التبويبات أو CSV.' : 'Filter by Horizon, Year, or Month above, then download multi-tab Microsoft Excel (.xlsx) or UTF-8 CSV sheets directly.'}
      </p>
      <div class="export-grid">
        ${reports.map((r) => `
          <div class="export-item">
            <div>
              <div style="font-size:14px;font-weight:800;">${r.icon} ${esc(r.title)}</div>
              <div style="font-size:11px;color:var(--muted);margin-top:4px;">${esc(r.desc)}</div>
            </div>
            <div style="display:flex;gap:8px;margin-top:6px;">
              <a class="btn btn-emerald" style="flex:1;justify-content:center;" href="${exportUrl(r.key, 'xlsx')}">⬇ Excel (.xlsx)</a>
              <a class="btn" style="flex:1;justify-content:center;" href="${exportUrl(r.key, 'csv')}">⬇ CSV</a>
            </div>
          </div>
        `).join('')}
      </div>
    </div>
  `;
}

function renderProfitabilityTab(ar) {
  const trips = STATE.bi.profitable_trips || [];
  const sellers = STATE.bi.best_sellers || [];
  const boats = STATE.bi.fleet_assets || [];
  const nats = STATE.bi.top_nationalities || [];
  return `
    ${renderHorizonBar(ar)}
    <div class="grid grid-2" style="margin-bottom:14px;">
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
          <h3 style="font-size:14px;">🤿 ${ar ? 'أكثر الرحلات والأنشطة ربحية' : 'Most Profitable Trips, Tours & Courses'}</h3>
          <a class="btn btn-emerald" href="${exportUrl('profitable_trips', 'xlsx')}">⬇ .xlsx</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Activity</th><th>Revenue (EGP)</th><th>COGS</th><th>Net Margin</th><th>Margin %</th></tr></thead>
            <tbody>
              ${trips.map((t) => `
                <tr>
                  <td><b>${esc(ar ? t.name_ar : t.name_en)}</b></td>
                  <td class="num">${fmt(t.revenue_egp)}</td>
                  <td class="num">${fmt(t.direct_cogs_egp)}</td>
                  <td class="num pos">${fmt(t.net_margin_egp)}</td>
                  <td class="num"><span class="pill">${t.margin_pct}%</span></td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
          <h3 style="font-size:14px;">🏆 ${ar ? 'الأعلى مبيعاً (المناديب والشركات)' : 'Best Sellers (Sales Reps & Agencies)'}</h3>
          <a class="btn btn-emerald" href="${exportUrl('best_sellers', 'xlsx')}">⬇ .xlsx</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Seller / Rep</th><th>Vouchers</th><th>PAX</th><th>Est. Revenue</th><th>Est. Margin</th></tr></thead>
            <tbody>
              ${sellers.map((s) => `
                <tr>
                  <td><b>${esc(s.seller_name)}</b></td>
                  <td class="num">${fmt(s.vouchers)}</td>
                  <td class="num">${fmt(s.pax)}</td>
                  <td class="num">${fmt(s.est_revenue_egp)}</td>
                  <td class="num pos">${fmt(s.est_profit_egp)}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="grid grid-2">
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
          <h3 style="font-size:14px;">🛥️ ${ar ? 'ربحية المراكب والاسبيد وعمرة الازق' : 'Boats, Speedboats & Drydock Asset Performance'}</h3>
          <a class="btn btn-emerald" href="${exportUrl('profitable_boats', 'xlsx')}">⬇ .xlsx</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Asset</th><th>Trips</th><th>PAX</th><th>Direct Cost</th><th>Operating Margin</th><th>Drydock CapEx</th></tr></thead>
            <tbody>
              ${boats.map((b) => `
                <tr>
                  <td><b>${esc(ar ? b.name_ar : b.name_en)}</b><br><span style="font-size:10px;color:var(--muted);">${esc(b.ownership_structure)}</span></td>
                  <td class="num">${fmt(b.sea_days)}</td>
                  <td class="num">${fmt(b.total_pax)}</td>
                  <td class="num">${fmt(b.direct_trip_cost_egp)}</td>
                  <td class="num ${b.operating_margin_egp >= 0 ? 'pos' : 'neg'}">${fmt(b.operating_margin_egp)}</td>
                  <td class="num">${fmt(b.drydock_capex_total_egp)}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <h3 style="font-size:14px;margin-bottom:10px;">🌍 ${ar ? 'أعلى جنسيات الضيوف (35,324 فرد)' : 'Top Guest Source Nationalities (35,324 PAX)'}</h3>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Nationality</th><th>Vouchers</th><th>Total PAX</th><th>Est. Revenue (EGP)</th></tr></thead>
            <tbody>
              ${nats.map((n) => `
                <tr>
                  <td><b>${esc(n.nationality)}</b></td>
                  <td class="num">${fmt(n.vouchers)}</td>
                  <td class="num">${fmt(n.pax)}</td>
                  <td class="num">${fmt(n.est_revenue_egp)}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  `;
}

function renderPartnerFleet(ar) {
  const ws = STATE.ws || {};
  const vessels = ws.partner_vessels || [];
  const trips = ws.partner_trips || [];
  const drydock = ws.partner_drydock || [];
  return `
    <div class="card" style="margin-bottom:14px;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
        <h3 style="font-size:15px;">🛥️ ${ar ? 'مراكب الشراكة وحصص القيراط المخصصة لك فقط' : 'Your Scoped Partner Vessels & Qirat Equity'}</h3>
        <a class="btn btn-emerald" href="${exportUrl('profitable_boats', 'xlsx')}">⬇ Export My Vessels .xlsx</a>
      </div>
      <div class="grid grid-3">
        ${vessels.map((v) => `
          <div class="card" style="background:rgba(6,21,38,0.7);">
            <div style="font-weight:800;font-size:15px;">${esc(ar ? v.name_ar : v.name_en)}</div>
            <div style="font-size:11px;color:var(--cyan);margin:4px 0;">${esc(v.ownership_structure)}</div>
            <div style="font-size:12px;">Monthly Fixed Charter: <b>${fmt(v.monthly_fixed_rent_egp)} EGP</b></div>
            <div style="font-size:12px;margin-top:4px;">2026 Drydock Total: <b>${fmt(v.drydock_capex_total_egp)} EGP</b></div>
            <div style="font-size:12px;color:var(--amber);">Partner Drydock Share: <b>${fmt(v.drydock_partner_share_egp)} EGP</b></div>
          </div>
        `).join('')}
      </div>
    </div>
    <div class="grid grid-2">
      <div class="card">
        <h3 style="font-size:14px;margin-bottom:8px;">📅 Recent Daily Vessel Trips</h3>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Date</th><th>Vessel</th><th>PAX</th><th>Launch + Catering</th></tr></thead>
            <tbody>
              ${trips.slice(0, 30).map((t) => `
                <tr><td>${esc(t.trip_date)}</td><td>${esc(t.vessel_code)}</td><td class="num">${fmt(t.total_pax)}</td><td class="num">${fmt(t.total_direct_cost_egp)} EGP</td></tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>
      <div class="card">
        <h3 style="font-size:14px;margin-bottom:8px;">🔧 Shipyard Drydock Overhaul Lines (الازق)</h3>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Date</th><th>Vessel</th><th>Description</th><th>Total EGP</th><th>Partner Share</th></tr></thead>
            <tbody>
              ${drydock.slice(0, 30).map((d) => `
                <tr><td>${esc(d.tx_date)}</td><td>${esc(d.vessel_code)}</td><td>${esc(d.item_description)}</td><td class="num">${fmt(d.total_egp)}</td><td class="num">${fmt(d.partner_share_egp)}</td></tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  `;
}

function renderAccountingGl(ar) {
  const ws = STATE.ws || {};
  const cps = ws.counterparties || [];
  const exps = ws.recent_expenses || [];
  const pay = ws.payroll_recent || [];
  const etl = ws.etl_catalog || [];
  return `
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
      <a class="btn btn-emerald" href="${exportUrl('pnl', 'xlsx')}">⬇ Export P&L (.xlsx)</a>
      <a class="btn btn-emerald" href="${exportUrl('cashflow', 'xlsx')}">⬇ Export Cash Flow & 5,692 Expenses (.xlsx)</a>
      <a class="btn btn-primary" href="${exportUrl('full_data_pack', 'xlsx')}">⬇ Export AR/AP & Payroll (.xlsx)</a>
    </div>
    <div class="grid grid-2" style="margin-bottom:14px;">
      <div class="card">
        <h3 style="font-size:14px;margin-bottom:8px;">🤝 AR Clients & AP Suppliers Sub-Ledgers (63 Accounts)</h3>
        <div class="table-wrap" style="max-height:310px;">
          <table>
            <thead><tr><th>Party</th><th>Type</th><th>Total Debit</th><th>Total Credit</th><th>Balance (EGP)</th></tr></thead>
            <tbody>
              ${cps.map((c) => `
                <tr>
                  <td><b>${esc(c.name_ar)}</b></td>
                  <td><span class="pill">${esc(c.party_type)}</span></td>
                  <td class="num">${fmt(c.total_debit_egp)}</td>
                  <td class="num">${fmt(c.total_credit_egp)}</td>
                  <td class="num ${c.current_balance_egp >= 0 ? 'pos' : 'neg'}">${fmt(c.current_balance_egp)}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <h3 style="font-size:14px;margin-bottom:8px;">👥 Monthly Staff Payroll & Commissions (SALERY)</h3>
        <div class="table-wrap" style="max-height:310px;">
          <table>
            <thead><tr><th>Month</th><th>Employee</th><th>Role</th><th>Days</th><th>Net Pay (EGP)</th></tr></thead>
            <tbody>
              ${pay.map((p) => `
                <tr>
                  <td>${esc(p.period_month)}</td>
                  <td><b>${esc(p.emp_name_ar)}</b></td>
                  <td>${esc(p.job_title_ar)}</td>
                  <td class="num">${fmt(p.worked_days)}</td>
                  <td class="num pos">${fmt(p.net_pay_egp)}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="grid grid-2">
      <div class="card">
        <h3 style="font-size:14px;margin-bottom:8px;">🧾 Latest Daily Expense Vouchers (اذن صرف)</h3>
        <div class="table-wrap" style="max-height:280px;">
          <table>
            <thead><tr><th>Date</th><th>#</th><th>Description</th><th>Total EGP</th></tr></thead>
            <tbody>
              ${exps.map((x) => `
                <tr><td>${esc(x.exp_date)}</td><td>${esc(x.voucher_seq)}</td><td>${esc(x.description)}</td><td class="num">${fmt(x.total_egp)}</td></tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>
      <div class="card">
        <h3 style="font-size:14px;margin-bottom:8px;">🗄️ Ingested Google Drive Workbooks Catalog (${etl.length})</h3>
        <div class="table-wrap" style="max-height:280px;">
          <table>
            <thead><tr><th>Workbook</th><th>Category</th><th>Rows</th><th>Status</th></tr></thead>
            <tbody>
              ${etl.map((w) => `
                <tr>
                  <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;">${esc(w.rel_path)}</td>
                  <td>${esc(w.category)}</td>
                  <td class="num">${fmt(w.rows_ingested)}</td>
                  <td><span class="pill" style="${w.status.includes('RESTRICTED') ? 'color:#fcd34d;border-color:#f59e0b;' : ''}">${esc(w.status)}</span></td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  `;
}

function renderTreasurySafe(ar) {
  const ws = STATE.ws || {};
  const safes = ws.safes || [];
  const cb = ws.cashbox_recent || [];
  return `
    <div class="grid grid-3" style="margin-bottom:14px;">
      ${safes.map((s) => `
        <div class="card">
          <div style="display:flex;justify-content:space-between;"><span class="kpi-label">${esc(s.safe_type)}</span><span class="pill">${esc(s.branch_code)}</span></div>
          <div style="font-size:15px;font-weight:800;margin:6px 0;">${esc(ar ? s.name_ar : s.name_en)}</div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:13px;margin-top:8px;">
            <div>🇪🇬 EGP: <b class="pos">${fmt(s.bal_egp)}</b></div>
            <div>🇺🇸 USD: <b>$${fmt(s.bal_usd)}</b></div>
            <div>🇪🇺 EUR: <b>€${fmt(s.bal_eur)}</b></div>
            <div>🇬🇧 GBP: <b>£${fmt(s.bal_gbp)}</b></div>
          </div>
        </div>
      `).join('')}
    </div>

    ${hasPerm('safe.record_tx') ? `
    <div class="card" style="margin-bottom:14px;">
      <h3 style="font-size:14px;margin-bottom:10px;">➕ ${ar ? 'تسجيل حركة خزينة فورية (توريد / إذن صرف)' : 'Record Live Cashbox Income / Expense Voucher'}</h3>
      <form onsubmit="submitSafeTx(event)" class="grid grid-kpi" style="align-items:end;">
        <div><label>Safe</label><select id="txSafe">${safes.map((s) => `<option value="${esc(s.code)}">${esc(s.name_en)}</option>`).join('')}</select></div>
        <div><label>Type</label><select id="txType"><option value="EXPENSE">Expense Outflow (إذن صرف)</option><option value="INCOME">Cash Inflow (توريد نقدية)</option></select></div>
        <div><label>Currency</label><select id="txCurr"><option>EGP</option><option>USD</option><option>EUR</option><option>GBP</option></select></div>
        <div><label>Amount</label><input type="number" step="0.01" id="txAmt" required placeholder="1500"></div>
        <div><label>Description</label><input type="text" id="txDesc" required placeholder="Marine Park / Fuel / Deposit"></div>
        <button type="submit" class="btn btn-primary" style="height:36px;justify-content:center;">Save Entry</button>
      </form>
    </div>` : ''}

    <div class="card">
      <h3 style="font-size:14px;margin-bottom:8px;">📋 Daily Multi-Currency Cashbox Ledger (الخزينة)</h3>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Date</th><th>In LE</th><th>In $</th><th>In €</th><th>VISA</th><th>Out LE</th><th>Out $</th><th>Balance LE</th><th>Balance $</th><th>Balance €</th></tr></thead>
          <tbody>
            ${cb.map((r) => `
              <tr>
                <td>${esc(r.tx_date)}</td>
                <td class="num pos">${fmt(r.inc_le)}</td><td class="num">${fmt(r.inc_usd)}</td><td class="num">${fmt(r.inc_eur)}</td><td class="num">${fmt(r.inc_visa)}</td>
                <td class="num neg">${fmt(r.exp_le)}</td><td class="num">${fmt(r.exp_usd)}</td>
                <td class="num">${fmt(r.bal_le)}</td><td class="num">${fmt(r.bal_usd)}</td><td class="num">${fmt(r.bal_eur)}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

async function submitSafeTx(e) {
  e.preventDefault();
  const res = await apiPost({
    action: 'record_safe_tx',
    safe_code: document.getElementById('txSafe').value,
    tx_type: document.getElementById('txType').value,
    currency: document.getElementById('txCurr').value,
    amount: document.getElementById('txAmt').value,
    description: document.getElementById('txDesc').value
  });
  if (res.ok) {
    showToast('Safe balance & expense ledger updated!');
    await loadUserData();
  }
}

function renderVouchersOps(ar) {
  const ws = STATE.ws || {};
  const list = ws.vouchers || [];
  const myStats = ws.my_sales_stats;
  return `
    ${myStats ? `
      <div class="grid grid-3" style="margin-bottom:14px;">
        <div class="card"><div class="kpi-label">My Personal Vouchers</div><div class="kpi-val">${fmt(myStats.total_vouchers)}</div></div>
        <div class="card"><div class="kpi-label">My Total Guest PAX</div><div class="kpi-val">${fmt(myStats.total_pax)} PAX</div></div>
        <div class="card"><div class="kpi-label">My Generated Sales Volume</div><div class="kpi-val pos">${fmt(myStats.est_rev_egp)} EGP</div></div>
      </div>
    ` : ''}
    <div class="card">
      <h3 style="font-size:14px;margin-bottom:10px;">🎫 ${ar ? 'كشف الفوتشرات والتشغيل وبوابة السقالة' : 'Live Vouchers, Boat Dispatch & Jetty REST Gate'}</h3>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>VO.NO</th><th>Date</th><th>Hotel & Room</th><th>Nat</th><th>PAX</th><th>Trip / Activity</th>
              <th>Seller</th><th>REST Due</th><th>Boat</th><th>Status</th><th>Action</th>
            </tr>
          </thead>
          <tbody>
            ${list.map((v) => `
              <tr>
                <td><b>${esc(v.voucher_no)}</b></td>
                <td>${esc(v.trip_date)}</td>
                <td>${esc(v.hotel_name)} ${v.room_no ? '#' + esc(v.room_no) : ''}</td>
                <td><span class="pill">${esc(v.nationality)}</span></td>
                <td class="num">${fmt(v.total_pax)}</td>
                <td>${esc(v.trip_name)}</td>
                <td>${esc(v.sales_rep)}</td>
                <td class="num ${v.rest_egp_equiv > 0 ? 'neg' : 'pos'}">${v.rest_egp_equiv > 0 ? fmt(v.rest_egp_equiv) + ' EGP' : 'PAID'}</td>
                <td>${esc(v.vessel_code || '—')}</td>
                <td><span class="pill">${esc(v.boarding_status)}</span></td>
                <td>
                  ${hasPerm('ops.assign_boat_driver') ? `<button class="btn" onclick="assignBoat(${v.id})">🛥️ Assign Oceano</button>` : ''}
                  ${hasPerm('jetty.scan_boarding_collect_rest') && v.boarding_status !== 'BOARDED' ? `<button class="btn btn-emerald" onclick="boardGuest(${v.id})">✅ Scan & Collect REST</button>` : ''}
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

async function assignBoat(id) {
  const vessel = prompt('Enter Vessel Code (OCEANO, OMAR_4, NIZAR, SOHS, YOUANA):', 'OCEANO');
  if (!vessel) return;
  const res = await apiPost({ action: 'assign_dispatch', id, vessel_code: vessel.toUpperCase(), pickup_time: '07:45' });
  if (res.ok) { showToast('Assigned to ' + vessel.toUpperCase()); await loadUserData(); }
}

async function boardGuest(id) {
  const res = await apiPost({ action: 'collect_rest_board', id });
  if (res.ok) { showToast('Guest boarded! Collected REST: ' + fmt(res.collected_egp) + ' EGP'); await loadUserData(); }
}

function renderNewVoucherForm(ar) {
  const ws = STATE.ws || {};
  const catalog = ws.catalog || [];
  const hotels = ws.hotels || [];
  return `
    <div class="card" style="max-width:680px;">
      <h3 style="font-size:16px;margin-bottom:6px;">➕ ${ar ? 'إصدار فوتشر حجز رقمي بالباركود (متصل بالموقع والخزينة)' : 'Issue Instant Digital Voucher (VO.NO + QR Code)'}</h3>
      <p style="font-size:12px;color:var(--muted);margin-bottom:14px;">Prices & Sharm hotel pickup windows sync automatically from seacollegediveclub.com master catalog.</p>
      <form onsubmit="submitNewVoucher(event)" class="grid grid-2">
        <div><label>Guest Name</label><input type="text" id="nvGuest" required placeholder="Marco Rossi"></div>
        <div><label>Trip Date</label><input type="date" id="nvDate" required value="<?= gmdate('Y-m-d') ?>"></div>
        <div>
          <label>Sharm Resort / Hotel (Auto Pickup Zone)</label>
          <select id="nvHotel" onchange="const o=this.selectedOptions[0];if(o&&o.dataset.pick)document.getElementById('nvPick').value=o.dataset.pick;">
            ${hotels.map((h) => `<option value="${esc(h.name_en)}" data-pick="${esc(h.pickup_window)}">${esc(h.name_en)} (${esc(h.pickup_window)})</option>`).join('')}
          </select>
        </div>
        <div><label>Room # & Pickup Window</label><div style="display:flex;gap:6px;"><input type="text" id="nvRoom" placeholder="Room 314"><input type="text" id="nvPick" value="07:55–08:10"></div></div>
        <div>
          <label>Official Catalog Trip / Course (EUR)</label>
          <select id="nvProd">
            ${catalog.map((c) => `<option value="${esc(c.code)}">${esc(c.name_en)} — €${c.price_eur}</option>`).join('')}
          </select>
        </div>
        <div><label>Nationality</label><input type="text" id="nvNat" value="ITALY"></div>
        <div><label>Adults / Children</label><div style="display:flex;gap:6px;"><input type="number" id="nvAdl" value="2" min="1"><input type="number" id="nvCh" value="0" min="0"></div></div>
        <div><label>Remaining REST Due at Jetty (EUR)</label><input type="number" step="0.01" id="nvRest" value="40"></div>
        <div style="grid-column:1/-1;">
          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:11px;font-size:14px;">🎫 Generate QR Voucher & Sync to Operations</button>
        </div>
      </form>
    </div>
  `;
}

async function submitNewVoucher(e) {
  e.preventDefault();
  const res = await apiPost({
    action: 'create_voucher',
    guest_name: document.getElementById('nvGuest').value,
    trip_date: document.getElementById('nvDate').value,
    hotel_name: document.getElementById('nvHotel').value,
    room_no: document.getElementById('nvRoom').value,
    pickup_time: document.getElementById('nvPick').value,
    product_code: document.getElementById('nvProd').value,
    nationality: document.getElementById('nvNat').value,
    adults: document.getElementById('nvAdl').value,
    children: document.getElementById('nvCh').value,
    rest_eur: document.getElementById('nvRest').value
  });
  if (res.ok) {
    showToast(`Issued ${res.voucher_no} (${res.qr_token})!`);
    STATE.activeTab = 'vouchers_ops';
    await loadUserData();
  }
}

function renderGearAssets(ar) {
  const assets = (STATE.ws && STATE.ws.gear_assets) || [];
  return `
    <div class="card">
      <h3 style="font-size:14px;margin-bottom:10px;">🤿 Serialized Dive Gear & Fixed Assets Register (جرد أصول 2026 — ${assets.length} Items)</h3>
      <div class="table-wrap">
        <table>
          <thead><tr><th>S.C Tag #</th><th>Branch</th><th>Category</th><th>Qty</th><th>Brand / Model</th><th>Size / Serial</th><th>Status</th></tr></thead>
          <tbody>
            ${assets.map((a) => `
              <tr>
                <td><b>${esc(a.sc_tag)}</b></td><td>${esc(a.location_branch)}</td><td>${esc(a.item_category)}</td>
                <td class="num">${fmt(a.qty)}</td><td>${esc(a.brand_description)}</td><td>${esc(a.serial_or_size)}</td>
                <td><span class="pill">${esc(a.status)}</span></td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

function renderWebsiteCrm(ar) {
  const leads = (STATE.ws && STATE.ws.crm_leads) || [];
  return `
    <div class="card">
      <h3 style="font-size:14px;margin-bottom:6px;">🌐 Live Website Bookings, /check-in Forms & Attributed WhatsApp Clicks (${leads.length})</h3>
      <p style="font-size:12px;color:var(--muted);margin-bottom:10px;">Automatically populated in real time whenever a guest submits a booking, /check-in, or WhatsApp button on seacollegediveclub.com.</p>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Time</th><th>Type</th><th>Lang</th><th>Guest</th><th>Phone / Email</th><th>Interest / Gear</th><th>EUR</th><th>Voucher</th><th>Ads Attribution</th></tr></thead>
          <tbody>
            ${leads.map((l) => `
              <tr>
                <td>${esc(String(l.received_at).slice(0, 16))}</td>
                <td><span class="pill">${esc(l.lead_type)}</span></td>
                <td>${esc(l.lang)}</td>
                <td><b>${esc(l.guest_name)}</b></td>
                <td>${esc(l.phone || l.email || '—')}</td>
                <td style="max-width:240px;overflow:hidden;text-overflow:ellipsis;">${esc(l.interest_items)}</td>
                <td class="num pos">€${fmt(l.estimated_eur, 2)}</td>
                <td>${esc(l.linked_voucher_no || '—')}</td>
                <td>${esc(l.gclid ? 'Google GCLID' : (l.fbclid ? 'Meta FBCLID' : (l.utm_source || 'Organic')))}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

boot();
</script>
</body>
</html>
