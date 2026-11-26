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
  <link rel="icon" href="/assets/images/logo.png" type="image/png">
  <link rel="apple-touch-icon" href="/assets/images/logo.png">
  <title>Sea College Dive Club — Official Ecosystem &amp; Mobile App</title>
  <style>
    :root {
      --bg: #051322;
      --surface: #0a2138;
      --surface-2: #102e4c;
      --border: rgba(56, 189, 248, 0.2);
      --text: #f0f9ff;
      --muted: #94a3b8;
      --cyan: #00d2ff;
      --teal: #10b981;
      --amber: #f59e0b;
      --gold: #fbbf24;
      --rose: #f43f5e;
      --indigo: #6366f1;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Cairo", Tahoma, sans-serif;
      background:
        radial-gradient(circle at 88% 0%, rgba(0, 210, 255, 0.16) 0%, transparent 42%),
        radial-gradient(circle at 10% 12%, rgba(245, 158, 11, 0.09) 0%, transparent 36%),
        linear-gradient(180deg, #081e36 0%, var(--bg) 65%);
      color: var(--text);
      min-height: 100vh;
      padding-bottom: 84px;
    }
    .topbar {
      position: sticky; top: 0; z-index: 50;
      background: linear-gradient(90deg, rgba(5, 19, 34, 0.96) 0%, rgba(9, 33, 58, 0.94) 55%, rgba(5, 19, 34, 0.96) 100%);
      backdrop-filter: blur(14px);
      border-bottom: 1px solid rgba(0, 210, 255, 0.28);
      box-shadow: 0 6px 24px rgba(0, 0, 0, 0.35);
      padding: 10px 16px;
      display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;
    }
    .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; }
    .brand-crest-wrap {
      width: 48px; height: 48px; border-radius: 50%; flex-shrink: 0;
      padding: 2px;
      background: conic-gradient(from 140deg, #00d2ff, #fbbf24, #10b981, #00d2ff);
      box-shadow: 0 0 18px rgba(0, 210, 255, 0.38), 0 3px 10px rgba(0, 0, 0, 0.45);
      display: grid; place-items: center;
    }
    .brand-crest-img {
      width: 100%; height: 100%; border-radius: 50%; object-fit: cover; display: block; background: #fff;
    }
    .brand-overline {
      display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
      font-size: 10px; font-weight: 900; letter-spacing: 0.9px; text-transform: uppercase;
      color: #fde68a; margin-bottom: 2px;
    }
    .brand h1 { font-size: 15.5px; font-weight: 900; letter-spacing: 0.25px; color: #f8fafc; display: flex; align-items: center; gap: 7px; flex-wrap: wrap; }
    .brand p { font-size: 11px; color: #93c5fd; }
    .login-hero-brand {
      display: flex; flex-direction: column; align-items: center; text-align: center;
      padding: 14px 12px 14px; margin-bottom: 12px; border-radius: 14px;
      background: linear-gradient(145deg, rgba(0, 210, 255, 0.13), rgba(245, 158, 11, 0.09), rgba(6, 21, 38, 0.85));
      border: 1px solid rgba(0, 210, 255, 0.32);
    }
    .login-hero-crest {
      width: 84px; height: 84px; border-radius: 50%; padding: 3px; margin-bottom: 8px;
      background: conic-gradient(from 140deg, #00d2ff, #fbbf24, #10b981, #00d2ff);
      box-shadow: 0 0 26px rgba(0, 210, 255, 0.42);
    }
    .login-hero-crest img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; background: #fff; display: block; }
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
    .avatar-circle {
      border-radius: 50%; object-fit: cover; object-position: center;
      border: 2px solid rgba(0, 210, 255, 0.45);
      background: linear-gradient(135deg, #0284c7, #0f172a);
      color: #f0f9ff; font-weight: 800; display: inline-flex; align-items: center; justify-content: center;
      flex-shrink: 0; overflow: hidden; text-transform: uppercase;
    }
    .profile-card {
      background: rgba(11, 34, 57, 0.9); border: 1px solid var(--border); border-radius: 14px;
      padding: 14px; display: flex; flex-direction: column; justify-content: space-between; gap: 10px;
      transition: 0.15s;
    }
    .profile-card:hover { border-color: rgba(0, 210, 255, 0.45); transform: translateY(-1px); }
    .contact-chip {
      display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 600;
      padding: 4px 9px; border-radius: 8px; background: rgba(6, 21, 38, 0.78);
      border: 1px solid var(--border); color: var(--text); text-decoration: none;
    }
    .contact-chip:hover { border-color: var(--cyan); color: var(--cyan); }
    .pitch-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(305px, 1fr)); gap: 14px; }
    .pitch-card {
      background: rgba(11, 34, 57, 0.94); border: 1px solid var(--border); border-radius: 16px;
      overflow: hidden; display: flex; flex-direction: column; justify-content: space-between;
      box-shadow: 0 12px 28px rgba(0,0,0,0.32); transition: 0.16s;
    }
    .pitch-card:hover { border-color: rgba(0,210,255,0.5); transform: translateY(-2px); }
    .pitch-media { position: relative; height: 184px; background: #04101d; overflow: hidden; }
    .pitch-media img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .pitch-price-tag {
      position: absolute; bottom: 10px; right: 10px; padding: 5px 11px; border-radius: 10px;
      background: rgba(4, 19, 34, 0.92); border: 1px solid rgba(0,210,255,0.55);
      color: var(--cyan); font-weight: 900; font-size: 15px; backdrop-filter: blur(6px);
    }
    .pitch-cat-tag {
      position: absolute; top: 10px; left: 10px; padding: 4px 9px; border-radius: 999px;
      background: rgba(4, 19, 34, 0.85); color: #e2e8f0; font-size: 10.5px; font-weight: 800;
      border: 1px solid rgba(255,255,255,0.18);
    }
    .info-badges { display: flex; flex-wrap: wrap; gap: 5px; margin: 9px 0; }
    .info-badge {
      display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 7px;
      background: rgba(6, 21, 38, 0.82); border: 1px solid rgba(125,211,252,0.18);
      font-size: 11px; color: #cbd5e1; font-weight: 600;
    }
    .qr-box {
      background: #ffffff; padding: 12px; border-radius: 14px; display: inline-flex;
      align-items: center; justify-content: center; box-shadow: 0 8px 24px rgba(0,0,0,0.35);
    }
    .qr-box svg { display: block; max-width: 100%; height: auto; }
    .modal-backdrop {
      display: none; position: fixed; inset: 0; z-index: 140; background: rgba(3, 12, 24, 0.86);
      padding: 14px; align-items: center; justify-content: center; overflow-y: auto;
    }
    .xp-pill {
      display: inline-flex; align-items: center; gap: 6px; padding: 4px 11px; border-radius: 999px;
      background: linear-gradient(135deg, rgba(99,102,241,0.28), rgba(0,210,255,0.22));
      border: 1px solid rgba(0,210,255,0.45); color: #e0f2fe; font-size: 11.5px; font-weight: 800;
    }
    .award-medal {
      display: inline-flex; align-items: center; gap: 5px; padding: 5px 11px; border-radius: 999px;
      background: linear-gradient(135deg, rgba(245,158,11,0.28), rgba(234,88,12,0.2));
      border: 1px solid rgba(251,191,36,0.55); color: #fde68a; font-size: 11.5px; font-weight: 800;
      box-shadow: 0 4px 12px rgba(245,158,11,0.18);
    }
    .target-track {
      width: 100%; height: 12px; border-radius: 999px; background: rgba(6,21,38,0.85);
      border: 1px solid var(--border); overflow: hidden; margin-top: 6px;
    }
    .target-fill {
      height: 100%; border-radius: 999px;
      background: linear-gradient(90deg, #00d2ff, #10b981, #fbbf24);
      transition: width 0.35s ease;
    }
    .podium-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
    .podium-card {
      background: linear-gradient(145deg, rgba(17,46,74,0.92), rgba(11,34,57,0.95));
      border: 1px solid rgba(251,191,36,0.38); border-radius: 15px; padding: 13px;
      position: relative; overflow: hidden;
    }
    .notif-badge {
      background: #f43f5e; color: #fff; font-size: 10.5px; font-weight: 900;
      padding: 1px 6px; border-radius: 999px; margin-inline-start: 3px;
    }
    .bottom-dock {
      display: none; position: fixed; bottom: 0; left: 0; right: 0; z-index: 85;
      background: rgba(6, 21, 38, 0.98); backdrop-filter: blur(14px);
      border-top: 1px solid var(--border); padding: 6px 8px calc(6px + env(safe-area-inset-bottom));
      justify-content: space-around; gap: 4px;
    }
    .dock-btn {
      flex: 1; border: none; background: transparent; color: var(--muted);
      display: flex; flex-direction: column; align-items: center; gap: 2px; position: relative;
      font-size: 11px; font-weight: 800; padding: 6px 4px; border-radius: 11px; cursor: pointer;
    }
    .dock-btn.active { background: rgba(0,210,255,0.18); color: var(--cyan); }
    .dock-btn .ico { font-size: 19px; line-height: 1.1; }
    .stepper-wrap {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(6,21,38,0.92); border: 1px solid var(--border); border-radius: 11px; padding: 3px;
    }
    .stepper-btn {
      width: 36px; height: 36px; border-radius: 9px; border: 1px solid rgba(125,211,252,0.28);
      background: var(--surface-2); color: #fff; font-size: 19px; font-weight: 900;
      display: inline-grid; place-items: center; cursor: pointer; user-select: none; line-height: 1;
    }
    .stepper-btn:active { transform: scale(0.95); background: var(--cyan); color: #041322; }
    .easy-cat-strip {
      display: flex; gap: 8px; overflow-x: auto; padding-bottom: 6px;
      scrollbar-width: thin; -webkit-overflow-scrolling: touch;
    }
    .easy-cat-tile {
      min-width: 96px; padding: 8px 10px; border-radius: 13px; border: 1px solid var(--border);
      background: rgba(11,34,57,0.92); color: var(--text); cursor: pointer; flex-shrink: 0;
      display: flex; flex-direction: column; align-items: center; gap: 3px; text-align: center;
      font-size: 11.5px; font-weight: 800; transition: 0.14s;
    }
    .easy-cat-tile .ico { font-size: 21px; line-height: 1.1; }
    .easy-cat-tile.active {
      background: linear-gradient(135deg, #00d2ff, #0284c7); color: #041322;
      border-color: transparent; box-shadow: 0 6px 18px rgba(0,210,255,0.28);
    }
    .seller-login-grid {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(136px, 1fr)); gap: 8px;
    }
    .seller-login-tile {
      padding: 9px 8px; border-radius: 12px; border: 1px solid var(--border);
      background: rgba(6,21,38,0.82); color: var(--text); cursor: pointer;
      display: flex; align-items: center; gap: 8px; text-align: start; transition: 0.14s;
    }
    .seller-login-tile:hover, .seller-login-tile.selected {
      border-color: var(--cyan); background: rgba(0,210,255,0.15);
    }
    .react-chip {
      border: 1px solid var(--border); background: rgba(6,21,38,0.85); color: var(--text);
      padding: 5px 10px; border-radius: 999px; font-size: 13px; font-weight: 800;
      cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: 0.12s;
    }
    .react-chip.active {
      border-color: var(--cyan); background: rgba(0,210,255,0.22); color: #fff; transform: scale(1.04);
    }
    .trip-mobile-card {
      background: rgba(11,34,57,0.94); border: 1px solid var(--border); border-radius: 14px;
      padding: 13px; display: grid; gap: 9px;
    }
    body.role-seller #appSub,
    body.role-seller #permPills,
    body.role-seller #roleBannerExtraBtns,
    body.role-seller #fabFeedback { display: none !important; }
    body.role-seller #roleBanner { padding: 10px 13px; margin-bottom: 10px; }
    @media (max-width: 920px) {
      .bottom-dock.ready { display: flex; }
      body { padding-bottom: 104px; }
      body.role-seller #navTabs { display: none; }
      #fabFeedback { bottom: 72px !important; }
    }
    @media (max-width: 640px) {
      .kpi-val { font-size: 18px; }
      .container { padding: 10px; }
      body.role-seller #siteBtn, body.role-seller #installTopBtn { display: none; }
    }
  </style>
</head>
<body>

<header class="topbar">
  <div class="brand">
    <div class="brand-crest-wrap" title="Sea College Dive Club — Official Sharm El Sheikh Crest">
      <img src="/assets/images/erp-brand-crest.webp" onerror="this.onerror=null;this.src='/assets/images/logo.png';" alt="Sea College Dive Club Logo" class="brand-crest-img" width="44" height="44">
    </div>
    <div>
      <div class="brand-overline">
        <span>⚓ PADI 5★ DIVE RESORT • CDWS SHARM EL SHEIKH</span>
        <span class="pill" style="padding:1px 7px;font-size:10px;background:rgba(16,185,129,0.2);color:#34d399;border-color:rgba(16,185,129,0.45);">🚀 v3.5 LIVE</span>
      </div>
      <h1 id="appTitle">SEA COLLEGE DIVE CLUB — Ecosystem &amp; Concierge</h1>
      <p id="appSub">Cyrene • Travco Jetty • Luxury Fleet • Suppliers &amp; Staff Rewards</p>
    </div>
  </div>
  <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;">
    <button type="button" id="inboxTopBtn" class="btn" style="display:none;position:relative;border-color:rgba(251,191,36,0.45);" onclick="openCompanyInboxTab()">📨 <span id="inboxTopLbl">Inbox</span> <span id="inboxCountBadge" class="notif-badge" style="display:none;">0</span></button>
    <button type="button" id="notifBtn" class="btn" style="display:none;position:relative;" onclick="openNotificationsModal()">🔔 <span id="notifCountBadge" class="notif-badge" style="display:none;">0</span></button>
    <button type="button" id="userBadge" class="btn" style="display:none;padding:5px 11px;border-radius:999px;" onclick="STATE.activeTab='my_profile';renderApp();"></button>
    <button type="button" id="installTopBtn" class="btn btn-emerald" onclick="triggerInstallApp()">📲 Install</button>
    <button type="button" class="btn" id="langBtn" style="font-weight:900;border-color:rgba(0,210,255,0.45);" onclick="toggleLang()">🇪🇬 عربي / AR</button>
    <a href="/" class="btn" id="siteBtn">🌐 Website</a>
    <button type="button" class="btn btn-amber" id="logoutBtn" style="display:none;" onclick="doLogout()">Logout</button>
  </div>
</header>

<main class="container">
  <!-- ================= LOGIN VIEW (1-TAP EASY PHOTO LOGIN FOR SELLERS & STAFF) ================= -->
  <section id="loginView" class="login-box card" style="display:none;max-width:560px;">
    <div class="login-hero-brand">
      <div class="login-hero-crest">
        <img src="/assets/images/erp-brand-crest.webp" onerror="this.onerror=null;this.src='/assets/images/logo.png';" alt="Sea College Dive Club Official Emblem" width="80" height="80">
      </div>
      <div style="font-size:10.5px;font-weight:900;letter-spacing:1.1px;color:#fde68a;text-transform:uppercase;">⚓ EST. SHARM EL SHEIKH • PADI 5★ DIVE RESORT</div>
      <div style="font-size:19px;font-weight:900;color:#f8fafc;margin-top:2px;">SEA COLLEGE DIVE CLUB</div>
      <div style="font-size:11.5px;color:#93c5fd;margin-top:2px;">نادي سي كوليدج للغوص والرحلات البحرية والسفاري — شرم الشيخ</div>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
      <h2 style="font-size:17.5px;" id="loginHeading">👋 تسجيل الدخول السريع • Staff &amp; Verified Guest Sign In</h2>
      <button type="button" class="btn" onclick="toggleLang()">🌍 عربي / EN</button>
    </div>
    <p style="font-size:12.5px;color:var(--muted);margin-bottom:8px;" id="loginDesc">
      اضغط على اسمك بالأسفل أو اكتب اسم المستخدم / بريد العميل المؤكد • Tap your staff profile or sign in with your Verified Guest Email!
    </p>
    <div style="margin-bottom:11px;padding:7px 11px;border-radius:9px;background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.32);font-size:11.5px;color:#a7f3d0;">
      🔒 <b>Verified Club Members &amp; Team Only:</b> Guest accounts are created automatically when your Sea College booking is confirmed (no public spam sign-ups).
    </div>
    <form onsubmit="doLogin(event)" class="grid" style="gap:11px;">
      <div>
        <label id="lblUser" style="font-weight:700;color:var(--text);">👤 اسم المستخدم أو إيميل الضيف • Username or Verified Guest Email</label>
        <input type="text" id="inpUser" required autocomplete="username" style="font-size:15px;padding:11px;font-weight:700;" placeholder="Tap your name below or enter username / guest email">
      </div>
      <div>
        <label id="lblPass" style="font-weight:700;color:var(--text);">🔑 كلمة المرور • Password</label>
        <div style="display:flex;gap:6px;">
          <input type="password" id="inpPass" required autocomplete="current-password" style="font-size:15px;padding:11px;" placeholder="••••••••••••">
          <button type="button" class="btn" style="padding:0 13px;font-size:15px;" onclick="const p=document.getElementById('inpPass');p.type=p.type==='password'?'text':'password';" title="Show / Hide Password">👁️</button>
        </div>
      </div>
      <div id="loginErr" style="color:var(--rose);font-size:13px;font-weight:700;display:none;"></div>
      <button type="submit" class="btn btn-primary" style="justify-content:center;padding:13px;font-size:15px;font-weight:900;border-radius:12px;" id="btnSignIn">
        🔐 دخول الآن • Sign In
      </button>
    </form>

    <div style="margin-top:15px;border-top:1px solid var(--border);padding-top:12px;">
      <div style="font-size:12.5px;color:var(--cyan);font-weight:800;margin-bottom:8px;">
        ⚡ اضغط على اسمك للدخول السريع • 1-Tap Select Your Account:
      </div>
      <div id="roleDirectoryList" style="display:grid;gap:8px;max-height:310px;overflow-y:auto;"></div>
    </div>
  </section>

  <!-- ================= AUTHENTICATED APP SHELL ================= -->
  <section id="appView" style="display:none;">
    <div id="roleBanner" class="card" style="margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;background:linear-gradient(90deg,rgba(0,210,255,0.12),rgba(16,185,129,0.08));">
      <div style="display:flex;align-items:center;gap:11px;flex-wrap:wrap;">
        <div id="welcomeAvatar"></div>
        <div>
          <div style="font-size:16px;font-weight:800;" id="welcomeName"></div>
          <div style="font-size:11.5px;color:var(--muted);margin-top:2px;" id="welcomeScope"></div>
          <div id="welcomeContacts" style="display:flex;gap:6px;flex-wrap:wrap;margin-top:5px;"></div>
        </div>
      </div>
      <div id="roleBannerExtraBtns" style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
        <div id="roleBannerStaffQuickBtns" style="display:flex;gap:6px;flex-wrap:wrap;">
          <button type="button" class="btn btn-emerald" id="btnBannerMyStatement" onclick="openMyOwnStatement()">📒 My Statement &amp; Balance</button>
          <button type="button" class="btn btn-amber" id="btnBannerRewards" onclick="openStaffRewardsTab()">🎁 Bonus &amp; Allowances</button>
          <button type="button" class="btn" id="btnBannerInbox" onclick="openCompanyInboxTab()">📨 Inbox</button>
          <button type="button" class="btn" onclick="STATE.activeTab='my_profile';renderApp();">👤 My Profile</button>
          <button type="button" class="btn" id="btnBannerTeamDir" onclick="STATE.activeTab='team_profiles';renderApp();">👥 Team Directory</button>
        </div>
        <div id="permPills" style="display:flex;gap:5px;flex-wrap:wrap;"></div>
      </div>
    </div>

    <div id="pinnedInboxBannerSlot"></div>
    <nav id="navTabs" class="tabs"></nav>
    <div id="tabContent"></div>
  </section>
</main>

<!-- User Profile Edit / Create Modal -->
<div id="profileEditModal" style="display:none;position:fixed;inset:0;z-index:130;background:rgba(3,12,24,0.85);padding:14px;align-items:center;justify-content:center;overflow-y:auto;">
  <div class="card" style="width:100%;max-width:540px;max-height:92vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
      <h3 style="font-size:16px;" id="profModalTitle">👤 Edit User Profile</h3>
      <button type="button" class="btn" onclick="closeProfileModal()">✕</button>
    </div>
    <form onsubmit="submitProfileForm(event)" class="grid" style="gap:10px;">
      <input type="hidden" id="pfMode" value="update">
      <input type="hidden" id="pfTargetUser" value="">
      <input type="hidden" id="pfAvatarDataUrl" value="">

      <div style="display:flex;align-items:center;gap:14px;padding:10px;border-radius:11px;background:rgba(6,21,38,0.65);border:1px solid var(--border);">
        <div id="pfPreviewBox"></div>
        <div style="flex:1;">
          <label style="color:var(--cyan);font-weight:700;">📷 Profile Photo (Camera or Gallery)</label>
          <input type="file" id="pfPhotoInput" accept="image/*" onchange="previewProfilePhoto(this)" style="font-size:12px;padding:6px;">
          <div style="font-size:11px;color:var(--muted);margin-top:4px;">Auto-crops &amp; optimizes square portrait cleanly for mobile speed.</div>
        </div>
      </div>

      <div id="pfCreateUsernameRow" style="display:none;grid-template-columns:1fr 1fr;gap:8px;">
        <div>
          <label>Username (Login ID) *</label>
          <input type="text" id="pfNewUsername" placeholder="e.g. rami.sales">
        </div>
        <div>
          <label>Initial Password *</label>
          <input type="text" id="pfInitPass" placeholder="Min 6 chars (e.g. Rami@2026)">
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
        <div>
          <label>Full Name (English) *</label>
          <input type="text" id="pfNameEn" required placeholder="e.g. Islam Samir">
        </div>
        <div>
          <label>Full Name (Arabic)</label>
          <input type="text" id="pfNameAr" dir="rtl" placeholder="مثال: إسلام سمير">
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
        <div>
          <label>Job Title (English)</label>
          <input type="text" id="pfTitleEn" placeholder="e.g. Founder & Managing Owner">
        </div>
        <div>
          <label>Job Title (Arabic)</label>
          <input type="text" id="pfTitleAr" dir="rtl" placeholder="مثال: المؤسس والشريك المالك">
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
        <div>
          <label>📞 Phone / WhatsApp Number</label>
          <input type="tel" id="pfPhone" placeholder="+20 10 00344544">
        </div>
        <div>
          <label>✉️ Email Address</label>
          <input type="email" id="pfEmail" placeholder="name@seacollegediveclub.com">
        </div>
      </div>

      <div id="pfAdminScopeBox" style="display:none;padding:10px;border-radius:10px;background:rgba(0,210,255,0.06);border:1px solid var(--border);">
        <div style="font-size:11px;font-weight:800;color:var(--cyan);margin-bottom:8px;">🛡️ ROLE &amp; DATA ACCESS AUTHORITIES (OWNER / ADMIN)</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
          <div>
            <label>Role Authority</label>
            <select id="pfRoleCode"></select>
          </div>
          <div>
            <label>Branch Scope</label>
            <select id="pfBranchScope">
              <option value="ALL">ALL BRANCHES</option>
              <option value="CYRENE">CYRENE</option>
              <option value="DREAMS_FAYROUZ">DREAMS_FAYROUZ</option>
              <option value="JETTY">JETTY</option>
              <option value="JAZ">JAZ</option>
              <option value="WEBSITE">WEBSITE</option>
            </select>
          </div>
          <div>
            <label>Vessel Scope (ALL or CSV)</label>
            <input type="text" id="pfVesselScope" placeholder="ALL or OCEANO,OMAR_4">
          </div>
          <div>
            <label>Sales Rep Filter Code</label>
            <input type="text" id="pfRepScope" placeholder="e.g. ADEL, FADY (or empty)">
          </div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
        <div>
          <label>Bio / Shift / Certification Notes</label>
          <input type="text" id="pfBio" placeholder="Optional PADI #, shift, or contact note">
        </div>
        <div id="pfPassResetCol">
          <label>Change Password (Optional)</label>
          <input type="password" id="pfNewPass" autocomplete="new-password" placeholder="Leave blank to keep current">
        </div>
      </div>

      <div id="pfErr" style="color:var(--rose);font-size:12px;display:none;"></div>
      <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:4px;">
        <button type="button" class="btn" onclick="closeProfileModal()">Cancel</button>
        <button type="submit" id="pfSaveBtn" class="btn btn-primary">💾 Save User Profile</button>
      </div>
    </form>
  </div>
</div>

<!-- Floating Staff Trial Feedback Button -->
<button type="button" id="fabFeedback" onclick="openFeedbackModal()" style="display:none;position:fixed;bottom:18px;left:18px;z-index:90;background:linear-gradient(135deg,#6366f1,#0284c7);color:#fff;border:none;padding:11px 16px;border-radius:999px;font-weight:800;font-size:13px;cursor:pointer;box-shadow:0 8px 24px rgba(0,0,0,0.45);">
  🧪 Send Trial Note / Improve
</button>

<!-- Trial Feedback Modal -->
<div id="feedbackModal" style="display:none;position:fixed;inset:0;z-index:120;background:rgba(3,12,24,0.82);padding:16px;align-items:center;justify-content:center;">
  <div class="card" style="width:100%;max-width:460px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
      <h3 style="font-size:16px;">🧪 Pilot Trial Feedback & Improvement</h3>
      <button type="button" class="btn" onclick="document.getElementById('feedbackModal').style.display='none'">✕</button>
    </div>
    <p style="font-size:12px;color:var(--muted);margin-bottom:12px;">
      Spotted an issue, missing price/hotel rule, or idea to make your daily work faster? Send it directly to Management & the AI Engineering Team!
    </p>
    <form onsubmit="submitTrialFeedback(event)" class="grid" style="gap:10px;">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
        <div>
          <label>Feedback Type</label>
          <select id="fbType">
            <option value="IMPROVEMENT">💡 Feature / Workflow Idea</option>
            <option value="BUG">🐞 Bug / Error</option>
            <option value="ACCOUNTING_RULE">⚖️ Accounting / Price Rule</option>
            <option value="UX_SPEED">📱 Mobile Screen / Speed</option>
          </select>
        </div>
        <div>
          <label>Priority</label>
          <select id="fbPrio">
            <option value="NORMAL">Normal</option>
            <option value="HIGH">🔥 Urgent / Blocker</option>
          </select>
        </div>
      </div>
      <div>
        <label>Your Note (Arabic or English)</label>
        <input type="text" id="fbMsg" required placeholder="اكتب ملاحظتك أو اقتراحك هنا / Describe what to improve...">
      </div>
      <button type="submit" class="btn btn-primary" style="justify-content:center;padding:10px;">🚀 Submit Feedback to Pilot Log</button>
    </form>
  </div>
</div>

<!-- Install to Phone Guide Modal -->
<div id="installModal" style="display:none;position:fixed;inset:0;z-index:120;background:rgba(3,12,24,0.82);padding:16px;align-items:center;justify-content:center;">
  <div class="card" style="width:100%;max-width:440px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
      <h3 style="font-size:16px;">📲 Install Sea College App on Your Phone</h3>
      <button type="button" class="btn" onclick="document.getElementById('installModal').style.display='none'">✕</button>
    </div>
    <div style="font-size:13px;line-height:1.6;display:grid;gap:10px;">
      <div style="padding:10px;border-radius:9px;background:rgba(0,210,255,0.08);border:1px solid var(--border);">
        <b>🍎 iPhone / iPad (Safari):</b><br>
        1. Tap the <b>Share</b> icon (⬆️) at the bottom of Safari.<br>
        2. Scroll down and tap <b>"Add to Home Screen" (إضافة إلى الشاشة الرئيسية)</b>.<br>
        3. Tap <b>Add</b> — the <b>SeaCollege ERP</b> icon launches full-screen like a native app!
      </div>
      <div style="padding:10px;border-radius:9px;background:rgba(16,185,129,0.08);border:1px solid var(--border);">
        <b>🤖 Android (Chrome):</b><br>
        1. Tap the <b>⋮ Menu</b> at the top-right of Chrome.<br>
        2. Tap <b>"Install App"</b> or <b>"Add to Home screen" (تثبيت التطبيق)</b>.
      </div>
    </div>
  </div>
</div>

<!-- Product / Client QR & Social Media Share Modal -->
<div id="productQrModal" class="modal-backdrop">
  <div class="card" style="width:100%;max-width:490px;text-align:center;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
      <h3 style="font-size:15px;" id="pqrTitle">📱 Scan or Share Personal Booking Link</h3>
      <button type="button" class="btn" onclick="document.getElementById('productQrModal').style.display='none'">✕</button>
    </div>
    <p style="font-size:12px;color:var(--muted);margin-bottom:10px;" id="pqrSub"></p>
    <input type="hidden" id="pqrItemCode" value="">
    <div id="pqrPromoControls" style="margin-bottom:12px;padding:9px 10px;border-radius:10px;background:rgba(245,158,11,0.09);border:1px solid rgba(245,158,11,0.35);text-align:left;">
      <div style="font-size:11.5px;font-weight:800;color:#fde68a;margin-bottom:6px;">🎁 Attach Special Discount to Share Link &amp; QR (Optional)</div>
      <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
        <select id="pqrDiscType" style="width:128px;padding:6px;" onchange="if(this.value==='NONE'){document.getElementById('pqrDiscVal').value='0';applyPromoToProductQrModal();}">
          <option value="NONE">No Discount</option>
          <option value="PERCENT">Percentage (%)</option>
          <option value="AMOUNT">Fixed Amount (€)</option>
        </select>
        <input type="number" step="0.5" min="0" id="pqrDiscVal" value="0" style="width:86px;padding:6px;" placeholder="e.g. 10">
        <button type="button" class="btn btn-amber" onclick="applyPromoToProductQrModal()">✨ Apply Promo</button>
      </div>
    </div>
    <div id="pqrSvgWrap" class="qr-box" style="margin:0 auto 12px;"></div>
    <div style="font-size:11.5px;word-break:break-all;color:var(--cyan);margin-bottom:12px;font-weight:700;" id="pqrUrlTxt"></div>
    <div style="display:flex;gap:7px;justify-content:center;flex-wrap:wrap;">
      <a id="pqrWaBtn" href="https://wa.me/" target="_blank" rel="noopener" class="btn btn-emerald">💬 WhatsApp</a>
      <a id="pqrFbBtn" href="https://www.facebook.com/sharer/sharer.php" target="_blank" rel="noopener" class="btn" style="border-color:#3b82f6;color:#93c5fd;">📘 Facebook</a>
      <a id="pqrTgBtn" href="https://t.me/share/url" target="_blank" rel="noopener" class="btn" style="border-color:#38bdf8;color:#7dd3fc;">✈️ Telegram</a>
      <button type="button" class="btn btn-primary" onclick="shareNativeOrCopy(document.getElementById('pqrUrlTxt').textContent, document.getElementById('pqrTitle').textContent)">📲 Share / Instagram Copy</button>
    </div>
  </div>
</div>

<!-- 1-Tap Guest-Safe Tablet Booking Modal (Zero Commission Shown to Guest) -->
<div id="fastBookModal" class="modal-backdrop">
  <div class="card" style="width:100%;max-width:600px;max-height:92vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
      <h3 style="font-size:16px;" id="fbkTitle">⚡ Guest Booking &amp; Instant Boarding Pass</h3>
      <button type="button" class="btn" onclick="document.getElementById('fastBookModal').style.display='none'">✕</button>
    </div>
    <form onsubmit="submitFastBookForm(event)" class="grid grid-2" style="gap:10px;">
      <input type="hidden" id="fbkProdCode" value="">
      <div>
        <label style="font-weight:700;color:var(--text);">👤 اسم الضيف • Guest Name *</label>
        <input type="text" id="fbkGuest" required style="padding:10px;font-size:14px;font-weight:700;" placeholder="e.g. Marco Rossi">
      </div>
      <div>
        <label style="font-weight:700;color:var(--text);">📱 واتساب / تليفون • WhatsApp</label>
        <input type="tel" id="fbkPhone" style="padding:10px;font-size:14px;" placeholder="+39 333 1234567">
      </div>
      <div style="grid-column:1/-1;">
        <label style="font-weight:700;color:#7dd3fc;">✉️ إيميل الضيف (اختياري — يُرسل له حساب التطبيق تلقائياً) • Guest Email (Optional — Auto-Send VIP App Login)</label>
        <input type="email" id="fbkEmail" style="padding:10px;font-size:14px;" placeholder="Optional: guest@example.com (creates real customer account & emails login!)">
      </div>
      <div>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:4px;margin-bottom:4px;">
          <label style="margin:0;font-weight:700;color:var(--text);">📅 الموعد • Date</label>
          <div style="display:flex;gap:4px;">
            <button type="button" class="btn" style="padding:3px 7px;font-size:11px;" onclick="setFastBookQuickDate(0)">☀️ Today</button>
            <button type="button" class="btn btn-primary" style="padding:3px 7px;font-size:11px;" onclick="setFastBookQuickDate(1)">🌅 Tomorrow</button>
          </div>
        </div>
        <input type="date" id="fbkDate" required value="<?= gmdate('Y-m-d') ?>">
      </div>
      <div>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:3px;margin-bottom:4px;flex-wrap:wrap;">
          <label style="margin:0;">🌍 Nationality</label>
          <div style="display:flex;gap:3px;">
            <button type="button" class="btn" style="padding:2px 5px;font-size:12px;" onclick="document.getElementById('fbkNat').value='ITALY'">🇮🇹</button>
            <button type="button" class="btn" style="padding:2px 5px;font-size:12px;" onclick="document.getElementById('fbkNat').value='RUSSIA'">🇷🇺</button>
            <button type="button" class="btn" style="padding:2px 5px;font-size:12px;" onclick="document.getElementById('fbkNat').value='UK'">🇬🇧</button>
            <button type="button" class="btn" style="padding:2px 5px;font-size:12px;" onclick="document.getElementById('fbkNat').value='GERMANY'">🇩🇪</button>
            <button type="button" class="btn" style="padding:2px 5px;font-size:12px;" onclick="document.getElementById('fbkNat').value='POLAND'">🇵🇱</button>
            <button type="button" class="btn" style="padding:2px 5px;font-size:12px;" onclick="document.getElementById('fbkNat').value='EGYPT'">🇪🇬</button>
          </div>
        </div>
        <input type="text" id="fbkNat" value="ITALY">
      </div>
      <div>
        <label style="font-weight:700;color:var(--text);">🏨 الفندق • Hotel (Auto Pickup)</label>
        <select id="fbkHotel" onchange="const o=this.selectedOptions[0];if(o&&o.dataset.pick)document.getElementById('fbkPick').value=o.dataset.pick;"></select>
      </div>
      <div>
        <label style="font-weight:700;color:var(--text);">🚪 الغرفة والتحرك • Room &amp; Pickup</label>
        <div style="display:flex;gap:6px;">
          <input type="text" id="fbkRoom" placeholder="Room 312">
          <input type="text" id="fbkPick" value="07:55–08:10">
        </div>
      </div>
      <div style="grid-column:1/-1;padding:10px;border-radius:12px;background:rgba(6,21,38,0.72);border:1px solid var(--border);">
        <label style="font-weight:800;color:var(--cyan);margin-bottom:7px;">👨‍👩‍👧 عدد الضيوف بلمسة واحدة • Tap − / + for Guests (Infants &lt;3 FREE)</label>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(145px,1fr));gap:8px;">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
            <span style="font-size:12px;font-weight:800;">👤 كبار Adults</span>
            <div class="stepper-wrap">
              <button type="button" class="stepper-btn" onclick="stepFastBookField('fbkAdl',-1,1,true)">−</button>
              <input type="number" id="fbkAdl" value="2" min="1" max="60" style="width:46px;text-align:center;padding:5px;border:none;font-weight:900;" oninput="recalcFastBookGuestTotal(true)">
              <button type="button" class="stepper-btn" onclick="stepFastBookField('fbkAdl',1,1,true)">+</button>
            </div>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
            <span style="font-size:12px;font-weight:800;">🧒 أطفال Kids</span>
            <div class="stepper-wrap">
              <button type="button" class="stepper-btn" onclick="stepFastBookField('fbkCh',-1,0,true)">−</button>
              <input type="number" id="fbkCh" value="0" min="0" max="40" style="width:46px;text-align:center;padding:5px;border:none;font-weight:900;" oninput="recalcFastBookGuestTotal(true)">
              <button type="button" class="stepper-btn" onclick="stepFastBookField('fbkCh',1,0,true)">+</button>
            </div>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
            <span style="font-size:12px;font-weight:800;color:var(--teal);">👶 رضع Free</span>
            <div class="stepper-wrap">
              <button type="button" class="stepper-btn" onclick="stepFastBookField('fbkInf',-1,0,false)">−</button>
              <input type="number" id="fbkInf" value="0" min="0" max="20" style="width:46px;text-align:center;padding:5px;border:none;font-weight:900;" oninput="recalcFastBookGuestTotal(false)">
              <button type="button" class="stepper-btn" onclick="stepFastBookField('fbkInf',1,0,false)">+</button>
            </div>
          </div>
        </div>
      </div>
      <div id="fbkSafariBuilderRow" style="display:none;grid-column:1/-1;padding:11px 12px;border-radius:12px;background:linear-gradient(135deg,rgba(245,158,11,0.12),rgba(0,210,255,0.08));border:1px solid rgba(251,191,36,0.4);">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:8px;">
          <label style="margin:0;font-weight:900;color:#fde68a;font-size:12.5px;">🏜️ خيارات السفاري السريعة (موتوسيكل / باجي / جمل / نجوم / عشاء) • Safari Vehicles &amp; Add-Ons</label>
          <button type="button" class="btn" style="padding:3px 8px;font-size:10.5px;" onclick="resetFastBookSafariSpec()">↺ Reset</button>
        </div>
        <div id="fbkSafariBuilderGrid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:7px;"></div>
      </div>
      <div id="fbkDiscountRow" style="grid-column:1/-1;padding:10px 12px;border-radius:10px;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.32);">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px;margin-bottom:6px;">
          <label style="margin:0;color:#fde68a;font-weight:800;">🎁 خصم خاص للضيف • Special Discount</label>
          <div style="display:flex;gap:5px;flex-wrap:wrap;">
            <button type="button" class="btn" style="padding:4px 9px;font-size:11.5px;" onclick="setFastBookPresetDiscount('NONE', 0)">بدون 0%</button>
            <button type="button" class="btn btn-amber" style="padding:4px 9px;font-size:11.5px;" onclick="setFastBookPresetDiscount('PERCENT', 5)">-5%</button>
            <button type="button" class="btn btn-amber" style="padding:4px 9px;font-size:11.5px;" onclick="setFastBookPresetDiscount('PERCENT', 10)">-10%</button>
            <button type="button" class="btn btn-amber" style="padding:4px 9px;font-size:11.5px;" onclick="setFastBookPresetDiscount('PERCENT', 15)">-15%</button>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1.1fr 0.9fr 1.4fr;gap:7px;">
          <select id="fbkDiscType" onchange="if(this.value==='NONE')document.getElementById('fbkDiscVal').value='0';recalcFastBookGuestTotal(true)">
            <option value="NONE">No Discount</option>
            <option value="PERCENT">Percentage (%)</option>
            <option value="AMOUNT">Fixed Amount</option>
          </select>
          <input type="number" step="0.5" min="0" id="fbkDiscVal" value="0" placeholder="0" oninput="recalcFastBookGuestTotal(true)">
          <input type="text" id="fbkDiscReason" placeholder="Optional note (e.g. Family / Repeat Guest)">
        </div>
      </div>
      <div>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:4px;margin-bottom:4px;flex-wrap:wrap;">
          <label style="margin:0;font-weight:700;color:var(--text);">💵 المدفوع الآن • Deposit Now</label>
          <div style="display:flex;gap:3px;">
            <button type="button" class="btn btn-emerald" style="padding:2px 6px;font-size:10.5px;" onclick="setFastBookQuickDeposit('FULL')">Full</button>
            <button type="button" class="btn" style="padding:2px 6px;font-size:10.5px;" onclick="setFastBookQuickDeposit(20)">20</button>
            <button type="button" class="btn" style="padding:2px 6px;font-size:10.5px;" onclick="setFastBookQuickDeposit(0)">0 (At Bus)</button>
          </div>
        </div>
        <div style="display:flex;gap:6px;">
          <input type="number" step="0.01" min="0" id="fbkDepAmt" value="40" oninput="recalcFastBookRestFromDeposit()">
          <select id="fbkDepCur" style="width:92px;" onchange="syncFastBookCurrency(this.value)">
            <option value="EUR">EUR</option>
            <option value="USD">USD</option>
            <option value="GBP">GBP</option>
            <option value="EGP">EGP</option>
          </select>
        </div>
      </div>
      <div>
        <label style="font-weight:700;color:var(--text);">🚐 الباقي عند الباص • Rest at Pickup</label>
        <div style="display:flex;gap:6px;">
          <input type="number" step="0.01" min="0" id="fbkRestAmt" value="0" oninput="recalcFastBookSummaryBox()">
          <select id="fbkRestCur" style="width:92px;" onchange="recalcFastBookSummaryBox()">
            <option value="EUR">EUR</option>
            <option value="USD">USD</option>
            <option value="GBP">GBP</option>
            <option value="EGP">EGP</option>
          </select>
        </div>
      </div>
      <div id="fbkGuestSummary" style="grid-column:1/-1;padding:11px 13px;border-radius:11px;background:rgba(0,210,255,0.09);border:1px solid rgba(0,210,255,0.35);font-size:12.5px;line-height:1.55;"></div>
      <div id="fbkResultBox" style="display:none;grid-column:1/-1;padding:12px;border-radius:12px;background:rgba(16,185,129,0.12);border:1px solid rgba(16,185,129,0.45);text-align:center;"></div>
      <div style="grid-column:1/-1;display:flex;gap:8px;justify-content:flex-end;">
        <button type="button" class="btn" onclick="document.getElementById('fastBookModal').style.display='none'">Close</button>
        <button type="submit" id="fbkSubmitBtn" class="btn btn-primary" style="padding:11px 18px;font-size:14px;font-weight:900;">🎫 تأكيد الحجز وإصدار الباركود • Confirm Booking</button>
      </div>
    </form>
  </div>
</div>

<!-- Live Notifications Drawer Modal -->
<div id="notifModal" class="modal-backdrop">
  <div class="card" style="width:100%;max-width:560px;max-height:90vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
      <h3 style="font-size:16px;">🔔 Live Booking, Confirmation &amp; Audit Notifications</h3>
      <div style="display:flex;gap:6px;">
        <button type="button" class="btn" onclick="markAllNotifsRead()">✓ Mark All Read</button>
        <button type="button" class="btn" onclick="document.getElementById('notifModal').style.display='none'">✕</button>
      </div>
    </div>
    <div id="notifListBox" style="display:grid;gap:8px;"></div>
  </div>
</div>

<!-- Flexible Voucher PAX / Age Upgrade / Partial Cancel / REST Settlement Modal -->
<div id="adjustVoucherModal" class="modal-backdrop">
  <div class="card" style="width:100%;max-width:600px;max-height:92vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
      <h3 style="font-size:16px;" id="adjTitle">✏️ Flexible Reservation &amp; Financial Settlement</h3>
      <button type="button" class="btn" onclick="document.getElementById('adjustVoucherModal').style.display='none'">✕</button>
    </div>
    <p style="font-size:12px;color:var(--muted);margin-bottom:10px;">
      Adjust actual guest count (e.g. Infant ≥3 yrs upgraded to Child, or 1 guest cancelled), collect extra charge / remaining REST, and auto-recalculate seller commission &amp; audit trail.
    </p>
    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px;">
      <button type="button" class="btn btn-amber" onclick="applyAdjPreset('INFANT_TO_CHILD')">🎂 Infant ≥3 yrs → Child (+Extra Fare)</button>
      <button type="button" class="btn" onclick="applyAdjPreset('MINUS_1_ADULT')">➖ 1 Adult Cancelled / No-Show</button>
      <button type="button" class="btn" style="border-color:var(--rose);color:#fda4af;" onclick="applyAdjPreset('FULL_CANCEL')">❌ Cancel Entire Trip (&lt;N&gt;)</button>
    </div>
    <form onsubmit="submitAdjustVoucherForm(event)" class="grid grid-2" style="gap:10px;">
      <input type="hidden" id="adjVid" value="">
      <input type="hidden" id="adjFullCancel" value="0">
      <div style="grid-column:1/-1;display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;">
        <div>
          <label>Actual Adults</label>
          <input type="number" id="adjAdl" min="0" max="80" value="2" oninput="recalcAdjustPreview()">
        </div>
        <div>
          <label>Actual Children</label>
          <input type="number" id="adjCh" min="0" max="60" value="0" oninput="recalcAdjustPreview()">
        </div>
        <div>
          <label>Actual Infants (&lt;3 Free)</label>
          <input type="number" id="adjInf" min="0" max="30" value="0" oninput="recalcAdjustPreview()">
        </div>
      </div>
      <div>
        <label>Adult Unit Price (EUR)</label>
        <input type="number" step="0.5" min="0" id="adjAdultEur" value="30" oninput="recalcAdjustPreview()">
      </div>
      <div>
        <label>Child Unit Price (EUR)</label>
        <input type="number" step="0.5" min="0" id="adjChildEur" value="15" oninput="recalcAdjustPreview()">
      </div>
      <div>
        <label>Deposit Already Paid (EGP Equiv)</label>
        <input type="number" step="1" min="0" id="adjPaidEgp" readonly style="opacity:0.8;">
      </div>
      <div>
        <label>Extra / Remaining Collected Now (EGP)</label>
        <input type="number" step="1" min="0" id="adjCollectNowEgp" value="0">
      </div>
      <div style="grid-column:1/-1;display:flex;gap:14px;flex-wrap:wrap;padding:9px 11px;border-radius:10px;background:rgba(6,21,38,0.7);border:1px solid var(--border);">
        <label style="display:inline-flex;align-items:center;gap:7px;margin:0;color:var(--text);cursor:pointer;">
          <input type="checkbox" id="adjCollectRestChk" style="width:auto;" checked onchange="recalcAdjustPreview()">
          💵 Collect All Remaining Balance / Extra Charge Now (100% Paid)
        </label>
        <label style="display:inline-flex;align-items:center;gap:7px;margin:0;color:var(--text);cursor:pointer;">
          <input type="checkbox" id="adjBoardedChk" style="width:auto;" checked>
          🛥️ Guest Completed / Boarded Trip
        </label>
      </div>
      <div style="grid-column:1/-1;">
        <label>Audit Reason / Note (Visible to Accounting, Audit &amp; Owners) *</label>
        <input type="text" id="adjReason" required placeholder="e.g. Jetty age check: infant is 3 yrs -> upgraded to child (+15 EUR collected)">
      </div>
      <div id="adjPreviewBox" style="grid-column:1/-1;padding:10px 12px;border-radius:10px;background:rgba(0,210,255,0.08);border:1px solid var(--border);font-size:12px;"></div>
      <div style="grid-column:1/-1;display:flex;gap:8px;justify-content:flex-end;">
        <button type="button" class="btn" onclick="document.getElementById('adjustVoucherModal').style.display='none'">Cancel</button>
        <button type="submit" class="btn btn-emerald">💾 Save Actual Settlement &amp; Sync Accounting</button>
      </div>
    </form>
  </div>
</div>

<!-- Mid-Month Commission Allowance / Installment Modal -->
<div id="allowanceModal" class="modal-backdrop">
  <div class="card" style="width:100%;max-width:440px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
      <h3 style="font-size:16px;">💸 Request Mid-Month Commission Allowance</h3>
      <button type="button" class="btn" onclick="document.getElementById('allowanceModal').style.display='none'">✕</button>
    </div>
    <p style="font-size:12px;color:var(--muted);margin-bottom:12px;" id="alwAvailableHint"></p>
    <form onsubmit="submitAllowanceRequest(event)" class="grid" style="gap:10px;">
      <div>
        <label>Requested Allowance / Installment Amount (EGP) *</label>
        <input type="number" step="50" min="50" id="alwAmount" required placeholder="e.g. 2500">
      </div>
      <div>
        <label>Note for Accounting / Cashier</label>
        <input type="text" id="alwNotes" placeholder="Mid-month installment against actual completed & paid trips">
      </div>
      <div style="display:flex;gap:8px;justify-content:flex-end;">
        <button type="button" class="btn" onclick="document.getElementById('allowanceModal').style.display='none'">Cancel</button>
        <button type="submit" class="btn btn-primary">🚀 Submit Allowance Request</button>
      </div>
    </form>
  </div>
</div>

<!-- Verified Club Guest Future-Trip Booking Modal -->
<div id="guestBookModal" class="modal-backdrop">
  <div class="card" style="width:100%;max-width:520px;max-height:92vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
      <h3 style="font-size:16px;" id="gbkTitle">🌴 Book Your Next Sea College Trip</h3>
      <button type="button" class="btn" onclick="document.getElementById('guestBookModal').style.display='none'">✕</button>
    </div>
    <form onsubmit="submitGuestFutureBooking(event)" class="grid grid-2" style="gap:10px;">
      <input type="hidden" id="gbkTripCode" value="">
      <div>
        <label>📅 Preferred Trip Date *</label>
        <input type="date" id="gbkDate" required value="<?= gmdate('Y-m-d', time() + 86400) ?>">
      </div>
      <div>
        <label>📱 WhatsApp / Phone *</label>
        <input type="tel" id="gbkPhone" required placeholder="+39 333 1234567">
      </div>
      <div>
        <label>🏨 Hotel in Sharm El Sheikh</label>
        <input type="text" id="gbkHotel" list="gbkHotelsList" placeholder="Your Sharm hotel">
        <datalist id="gbkHotelsList"></datalist>
      </div>
      <div>
        <label>🚪 Room # (Optional)</label>
        <input type="text" id="gbkRoom" placeholder="e.g. 312">
      </div>
      <div>
        <label>👤 Adults (12+)</label>
        <input type="number" id="gbkAdl" min="1" max="50" value="2" oninput="recalcGuestBookModalTotal()">
      </div>
      <div>
        <label>🧒 Children (3–11 yrs)</label>
        <input type="number" id="gbkCh" min="0" max="30" value="0" oninput="recalcGuestBookModalTotal()">
      </div>
      <div style="grid-column:1/-1;display:grid;grid-template-columns:1fr 1.6fr;gap:8px;">
        <div>
          <label>👶 Infants (&lt;3 FREE)</label>
          <input type="number" id="gbkInf" min="0" max="15" value="0" oninput="recalcGuestBookModalTotal()">
        </div>
        <div>
          <label>📝 Special Request (Optional)</label>
          <input type="text" id="gbkNotes" placeholder="Vegetarian lunch, gear size, etc.">
        </div>
      </div>
      <div id="gbkTotalBar" style="grid-column:1/-1;padding:11px 13px;border-radius:11px;background:rgba(0,210,255,0.1);border:1px solid rgba(0,210,255,0.35);display:flex;justify-content:space-between;align-items:center;font-weight:800;"></div>
      <div id="gbkSuccessBox" style="display:none;grid-column:1/-1;padding:12px;border-radius:12px;background:rgba(16,185,129,0.15);border:1px solid rgba(16,185,129,0.45);text-align:center;"></div>
      <div style="grid-column:1/-1;display:flex;gap:8px;justify-content:flex-end;">
        <button type="button" class="btn" onclick="document.getElementById('guestBookModal').style.display='none'">Close</button>
        <button type="submit" id="gbkSubmitBtn" class="btn btn-primary" style="padding:11px 18px;font-weight:900;">✅ Confirm Future Trip (Pay at Pickup)</button>
      </div>
    </form>
  </div>
</div>

<!-- Accounting Trip & Safari Variant Cost Create / Edit Modal -->
<div id="tripCostModal" class="modal-backdrop">
  <div class="card" style="width:100%;max-width:640px;max-height:92vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
      <h3 style="font-size:16px;" id="tcmTitle">💰 Update Trip / Safari Variant Supplier Cost</h3>
      <button type="button" class="btn" onclick="document.getElementById('tripCostModal').style.display='none'">✕</button>
    </div>
    <form onsubmit="submitTripCostForm(event)" class="grid grid-2" style="gap:10px;">
      <div>
        <label>Item Code (Unique Cost SKU) *</label>
        <input type="text" id="tcmItemCode" required placeholder="e.g. SAF-QUAD-SINGLE">
      </div>
      <div>
        <label>Linked Master Trip (Optional Sync)</label>
        <select id="tcmLinkedTrip"></select>
      </div>
      <div>
        <label>Trip / Variant Name (English) *</label>
        <input type="text" id="tcmNameEn" required placeholder="e.g. Single Quad Bike Safari (1 Rider)">
      </div>
      <div>
        <label>الاسم بالعربي (Arabic Name) *</label>
        <input type="text" id="tcmNameAr" dir="rtl" required placeholder="مثال: سفاري موتوسيكل فردي">
      </div>
      <div>
        <label>Category Group *</label>
        <select id="tcmCatGrp">
          <option value="DESERT_SAFARI">🏜️ Desert Safari &amp; Bedouin (Quad/Buggy/Camel/Star/Dinner)</option>
          <option value="YACHT_SNORKELING">🏝️ Yacht &amp; Snorkeling Cruises</option>
          <option value="CAIRO_LUXOR">✈️ Cairo, Luxor &amp; Petra</option>
          <option value="SINAI_DAHAB">⛰️ St. Catherine, Moses, Dahab &amp; Ras Mohammed Bus</option>
          <option value="DOLPHIN_SUB_DINNER">🐬 Dolphin, Submarine &amp; Dinner Cruise</option>
          <option value="WATERSPORTS">🪂 Watersports &amp; Speedboats</option>
          <option value="DIVING_CHARTER">🤿 Diving &amp; SS Thistlegorm</option>
          <option value="BOAT_SHARING">⛵ External Boat Sharing (Sphinx / El-Nahas)</option>
          <option value="TRANSFERS">🚐 Bus &amp; Hotel Transfers</option>
          <option value="DIVING_COURSES">🎓 PADI Courses &amp; Packages</option>
        </select>
      </div>
      <div>
        <label>Assigned Supplier / Vendor *</label>
        <select id="tcmSupplier"></select>
      </div>
      <div>
        <label>Pricing Basis (PAX vs Safari Vehicle / Add-On) *</label>
        <select id="tcmBasis" onchange="syncTripCostBasisFields()">
          <option value="PER_PAX">👨‍👩‍👧 Per PAX (Adult / Child / Infant)</option>
          <option value="SINGLE_QUAD">🏍️ Single Quad (1 Rider Vehicle)</option>
          <option value="DOUBLE_QUAD">🏍️🏍️ Double Quad (2 Riders Vehicle)</option>
          <option value="BUGGY_2PAX">🏎️ Buggy for 2 People (Double Buggy)</option>
          <option value="BUGGY_4PAX">🚙 Buggy for 4 People (Family Buggy)</option>
          <option value="CAMEL_ADDON">🐪 Camel Riding with Safari (Add-On per PAX)</option>
          <option value="CAMEL_1HR">🐪⏱️ Camel Riding 1-Hour Standalone Tour</option>
          <option value="STAR_GAZING">🔭 Star Gazing Safari (Per PAX)</option>
          <option value="BEDOUIN_DINNER">🍖 Standard Bedouin Dinner (Per PAX)</option>
          <option value="VIP_BEDOUIN_DINNER">👑 VIP Bedouin Dinner (Per PAX)</option>
          <option value="PRIVATE_GROUP">🧭 Private Safari Guide Surcharge</option>
          <option value="PER_UNIT">🎯 Per Activity / Charter Unit</option>
          <option value="PER_VEHICLE_LEG">🚐 Per Transfer Bus / Van Leg</option>
        </select>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
        <div>
          <label>Capacity (PAX)</label>
          <input type="number" id="tcmCapPax" min="1" max="60" value="1">
        </div>
        <div>
          <label>Cost Currency</label>
          <select id="tcmCurr">
            <option value="EGP">EGP (ج.م)</option>
            <option value="USD">USD ($)</option>
            <option value="EUR">EUR (€)</option>
            <option value="GBP">GBP (£)</option>
          </select>
        </div>
      </div>

      <div style="grid-column:1/-1;padding:10px 12px;border-radius:11px;background:rgba(0,210,255,0.08);border:1px solid rgba(0,210,255,0.32);">
        <div style="font-size:12px;font-weight:900;color:var(--cyan);margin-bottom:8px;">💵 Supplier Cost Input (In Selected Cost Currency — Auto-Converts to EGP)</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(125px,1fr));gap:8px;">
          <div>
            <label>👤 Adult Cost *</label>
            <input type="number" step="0.01" min="0" id="tcmAdultCost" value="0" required>
          </div>
          <div>
            <label>🧒 Child Cost</label>
            <input type="number" step="0.01" min="0" id="tcmChildCost" value="0">
          </div>
          <div>
            <label>👶 Infant Cost</label>
            <input type="number" step="0.01" min="0" id="tcmInfantCost" value="0">
          </div>
          <div>
            <label>🏍️/🚙 Vehicle/Unit Cost</label>
            <input type="number" step="0.01" min="0" id="tcmUnitCost" value="0">
          </div>
        </div>
      </div>

      <div style="grid-column:1/-1;padding:10px 12px;border-radius:11px;background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.3);">
        <div style="font-size:12px;font-weight:900;color:#34d399;margin-bottom:8px;">🏷️ Guest Selling Tariff Reference (EUR) &amp; Commission Formula</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(125px,1fr));gap:8px;">
          <div>
            <label>Adult / Unit Sell (€)</label>
            <input type="number" step="0.5" min="0" id="tcmAdultEur" value="0">
          </div>
          <div>
            <label>Child Sell (€)</label>
            <input type="number" step="0.5" min="0" id="tcmChildEur" value="0">
          </div>
          <div>
            <label>Infant Sell (€)</label>
            <input type="number" step="0.5" min="0" id="tcmInfantEur" value="0">
          </div>
          <div>
            <label>Accounting Mode</label>
            <select id="tcmSaleMode">
              <option value="OUT_SIDE">OUT_SIDE (25% Net)</option>
              <option value="INSHEET">INSHEET (10% Gross)</option>
            </select>
          </div>
        </div>
      </div>

      <div style="grid-column:1/-1;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
        <label style="display:inline-flex;align-items:center;gap:7px;margin:0;cursor:pointer;color:#fde68a;font-weight:700;">
          <input type="checkbox" id="tcmIsSafariComp" style="width:auto;">
          🏜️ Show as Modular Option in Seller Safari Builder
        </label>
      </div>

      <div>
        <label>Spreadsheet Formula / Note</label>
        <input type="text" id="tcmFormulaNote" placeholder="e.g. BAKAR Sheet Col SGL = 400 EGP">
      </div>
      <div>
        <label>Audit Reason for Cost Update *</label>
        <input type="text" id="tcmReason" required value="Accounting supplier tariff update">
      </div>

      <div style="grid-column:1/-1;display:flex;gap:8px;justify-content:flex-end;">
        <button type="button" class="btn" onclick="document.getElementById('tripCostModal').style.display='none'">Cancel</button>
        <button type="submit" class="btn btn-emerald" style="padding:10px 18px;font-weight:900;">💾 Save Supplier Cost &amp; Sync ERP</button>
      </div>
    </form>
  </div>
</div>

<!-- Supplier / Vendor Directory Create / Edit Modal -->
<div id="supplierEditModal" class="modal-backdrop">
  <div class="card" style="width:100%;max-width:560px;max-height:92vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
      <h3 style="font-size:16px;" id="semTitle">🏭 Add / Edit Supplier or Vendor</h3>
      <button type="button" class="btn" onclick="document.getElementById('supplierEditModal').style.display='none'">✕</button>
    </div>
    <form onsubmit="submitSupplierForm(event)" class="grid grid-2" style="gap:10px;">
      <div>
        <label>Supplier Code (AP_*) *</label>
        <input type="text" id="semCode" placeholder="e.g. AP_BAKAR (auto if blank)">
      </div>
      <div>
        <label>Category *</label>
        <select id="semCat">
          <option value="DESERT_SAFARI">🏜️ Desert Safari &amp; Horse</option>
          <option value="CAIRO_SINAI">⛰️ Cairo, St. Catherine &amp; Dahab</option>
          <option value="AVIATION_VIP">✈️ Flight Excursions (Cairo/Luxor/Petra)</option>
          <option value="DOLPHIN_SUB_DINNER">🐬 Dolphin, Submarine &amp; Dinner Boat</option>
          <option value="WATERSPORTS">🪂 Watersports &amp; Glass Boat</option>
          <option value="DIVING_CHARTER">🤿 Diving &amp; Wreck Charter</option>
          <option value="BOAT_SHARING">⛵ External Boat Sharing</option>
          <option value="BEACH_ACCESS">🏖️ Reef &amp; Beach Entry</option>
          <option value="TRANSFERS">🚐 Bus &amp; Van Transfers</option>
          <option value="INHOUSE_FLEET">⚓ In-House Sea College Fleet</option>
        </select>
      </div>
      <div>
        <label>Supplier Name (English) *</label>
        <input type="text" id="semNameEn" required placeholder="e.g. Bakar Desert Safari Center">
      </div>
      <div>
        <label>اسم المورد بالعربي *</label>
        <input type="text" id="semNameAr" dir="rtl" required placeholder="مثال: بكار للسفاري والعشاء البدوي">
      </div>
      <div>
        <label>Contact Person</label>
        <input type="text" id="semContact" placeholder="e.g. Capt. Bakar">
      </div>
      <div>
        <label>📞 Phone / WhatsApp</label>
        <input type="tel" id="semPhone" placeholder="+20 100 450 9911">
      </div>
      <div>
        <label>Settlement Currency</label>
        <select id="semCurr">
          <option value="EGP">EGP</option>
          <option value="USD">USD</option>
          <option value="EUR">EUR</option>
          <option value="GBP">GBP</option>
        </select>
      </div>
      <div>
        <label>Accounting Sheet Tab Name</label>
        <input type="text" id="semSheetTab" placeholder="e.g. BAKAR">
      </div>
      <div>
        <label>Opening Balance (EGP)</label>
        <input type="number" step="0.01" id="semOpenBal" value="0">
      </div>
      <div>
        <label>Current Ledger Balance (EGP, - = Payable)</label>
        <input type="number" step="0.01" id="semCurrBal" value="0">
      </div>
      <div style="grid-column:1/-1;">
        <label>Payment Terms &amp; Contracted Rates Summary</label>
        <input type="text" id="semNotes" placeholder="Contracted rates, settlement day, bank/cash notes">
      </div>
      <div style="grid-column:1/-1;display:flex;gap:8px;justify-content:flex-end;">
        <button type="button" class="btn" onclick="document.getElementById('supplierEditModal').style.display='none'">Cancel</button>
        <button type="submit" class="btn btn-primary" style="padding:10px 18px;font-weight:900;">💾 Save Supplier</button>
      </div>
    </form>
  </div>
</div>

<!-- Staff Bonus & Allowance / Advance Modal -->
<div id="staffRewardModal" class="modal-backdrop">
  <div class="card" style="width:100%;max-width:540px;max-height:92vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
      <h3 style="font-size:16px;" id="srmTitle">🎁 Staff Bonus &amp; Allowance</h3>
      <button type="button" class="btn" onclick="document.getElementById('staffRewardModal').style.display='none'">✕</button>
    </div>
    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px;" id="srmPresetBar">
      <button type="button" class="btn btn-amber" style="padding:4px 9px;font-size:11px;" onclick="applyStaffRewardPreset('BONUS','PERFORMANCE_BONUS','Outstanding Performance & Guest Care Bonus','مكافأة أداء متميز وخدمة ضيوف رائعة')">🏆 Performance Bonus</button>
      <button type="button" class="btn btn-amber" style="padding:4px 9px;font-size:11px;" onclick="applyStaffRewardPreset('BONUS','GUEST_REVIEW_5STAR','5-Star Guest Review Recognition Bonus','مكافأة تقييم 5 نجوم من الضيوف')">⭐ 5★ Review Bonus</button>
      <button type="button" class="btn btn-emerald" style="padding:4px 9px;font-size:11px;" onclick="applyStaffRewardPreset('ALLOWANCE','TRANSPORT_ALLOWANCE','Hotel / Jetty Transport Allowance','بدل انتقالات التشغيل والفنادق')">🚐 Transport Allowance</button>
      <button type="button" class="btn btn-emerald" style="padding:4px 9px;font-size:11px;" onclick="applyStaffRewardPreset('ALLOWANCE','MEAL_ALLOWANCE','Extra Shift & Meal Allowance','بدل وجبة وساعات عمل إضافية')">🍽️ Meal / Shift Allowance</button>
      <button type="button" class="btn" style="padding:4px 9px;font-size:11px;" onclick="applyStaffRewardPreset('ADVANCE','SALARY_ADVANCE','Mid-Month Salary Advance','سلفة موظف على المرتب الشهري')">💵 Salary Advance</button>
    </div>
    <form onsubmit="submitStaffRewardForm(event)" class="grid grid-2" style="gap:10px;">
      <div id="srmEmpWrap">
        <label>👤 Staff Member *</label>
        <select id="srmEmpUser"></select>
      </div>
      <div>
        <label>Entry Type *</label>
        <select id="srmEntryType" onchange="syncStaffRewardTypeUI()">
          <option value="BONUS">🎁 Bonus / Reward (مكافأة تشجيعية)</option>
          <option value="ALLOWANCE">🍱 Allowance (بدل انتقال / وجبة / إقامة)</option>
          <option value="ADVANCE">💵 Salary Advance (سلفة على المرتب)</option>
        </select>
      </div>
      <div>
        <label>Category</label>
        <select id="srmCategory">
          <option value="PERFORMANCE_BONUS">🏆 Performance Bonus</option>
          <option value="GUEST_REVIEW_5STAR">⭐ 5-Star Guest Review</option>
          <option value="TARGET_ACHIEVEMENT">🎯 Target Achievement</option>
          <option value="TRANSPORT_ALLOWANCE">🚐 Transport Allowance (بدل انتقال)</option>
          <option value="MEAL_ALLOWANCE">🍽️ Meal / Shift Allowance (بدل وجبة / إضافي)</option>
          <option value="HOUSING_ALLOWANCE">🏠 Housing / Mobile Allowance (بدل سكن / اتصالات)</option>
          <option value="SALARY_ADVANCE">💵 Salary Advance (سلفة)</option>
        </select>
      </div>
      <div style="display:grid;grid-template-columns:1.3fr 0.9fr;gap:6px;">
        <div>
          <label>💵 Amount *</label>
          <input type="number" step="50" min="50" id="srmAmount" required placeholder="e.g. 1500">
        </div>
        <div>
          <label>Currency</label>
          <select id="srmCurr">
            <option value="EGP">EGP</option>
            <option value="EUR">EUR</option>
            <option value="USD">USD</option>
          </select>
        </div>
      </div>
      <div id="srmSafeWrap">
        <label>🏦 Payout Source / Safe</label>
        <select id="srmSafeCode">
          <option value="NONE_PAYROLL">📋 Add to Monthly Payroll Sheet</option>
          <option value="SAFE_CYRENE">💵 Pay Cash — Cyrene Safe</option>
          <option value="SAFE_JETTY">💵 Pay Cash — Jetty Safe</option>
        </select>
      </div>
      <div>
        <label>📅 Date</label>
        <input type="date" id="srmDate" value="<?= gmdate('Y-m-d') ?>">
      </div>
      <div style="grid-column:1/-1;">
        <label>📝 Reason / Note (English &amp; Arabic) *</label>
        <input type="text" id="srmReasonEn" required placeholder="e.g. Great guest service & extra effort this week">
      </div>
      <div style="grid-column:1/-1;">
        <input type="text" id="srmReasonAr" dir="rtl" placeholder="مثال: مكافأة التميز والمجهود الرائع مع الضيوف">
      </div>
      <div id="srmInboxCelebrateWrap" style="grid-column:1/-1;padding:8px 11px;border-radius:10px;background:rgba(245,158,11,0.12);border:1px solid rgba(251,191,36,0.38);">
        <label style="display:inline-flex;align-items:center;gap:8px;margin:0;cursor:pointer;color:#fde68a;font-weight:800;">
          <input type="checkbox" id="srmBroadcastInbox" style="width:auto;" checked>
          🎉 Post Congratulations in Company Inbox so the whole team celebrates!
        </label>
      </div>
      <div style="grid-column:1/-1;display:flex;gap:8px;justify-content:flex-end;">
        <button type="button" class="btn" onclick="document.getElementById('staffRewardModal').style.display='none'">Cancel</button>
        <button type="submit" id="srmSubmitBtn" class="btn btn-primary" style="padding:10px 18px;font-weight:900;">✅ Save Bonus / Allowance</button>
      </div>
    </form>
  </div>
</div>

<!-- v3.6 Counterparty & Staff Deposit / Installment / Settlement Modal -->
<div id="partyTransactionModal" class="modal-backdrop">
  <div class="card" style="width:100%;max-width:580px;max-height:92vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
      <h3 style="font-size:16px;" id="ptmTitle">💵 Record Deposit / Installment / Settlement</h3>
      <button type="button" class="btn" onclick="document.getElementById('partyTransactionModal').style.display='none'">✕</button>
    </div>

    <div id="ptmAccountSummaryBanner" style="margin-bottom:12px;padding:10px 12px;border-radius:11px;background:rgba(0,210,255,0.09);border:1px solid rgba(0,210,255,0.34);display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;"></div>

    <form onsubmit="submitPartyTxForm(event)" class="grid grid-2" style="gap:10px;">
      <input type="hidden" id="ptmEntityType" value="">
      <input type="hidden" id="ptmEntityCode" value="">
      <input type="hidden" id="ptmCurrentBalEgp" value="0">

      <div>
        <label>Transaction Type / نوع الحركة *</label>
        <select id="ptmTxKind" onchange="syncPartyTxKindUI()">
          <option value="INSTALLMENT">💵 Installment / دفعة قسط من الحساب</option>
          <option value="DEPOSIT">🏦 Deposit / عربون أو دفعة مقدمة</option>
          <option value="FULL_SETTLEMENT">✅ Full Settlement / تقفيل وسداد كامل الرصيد</option>
          <option value="ADVANCE_GIVEN">💸 Advance Given / صرف سلفة نقدية</option>
          <option value="ADVANCE_REPAID">↩️ Advance Repaid / توريد وسداد سلفة</option>
          <option value="INVOICE_CHARGE">🧾 New Invoice / إضافة فاتورة أو استحقاق</option>
          <option value="CREDIT_NOTE">✂️ Credit / خصم أو إشعار تسوية</option>
        </select>
      </div>

      <div>
        <label>Payment / Settlement Channel *</label>
        <select id="ptmMethod">
          <option value="SAFE_CYRENE">💵 Cyrene Safe (خزينة سيرين)</option>
          <option value="SAFE_JETTY">⚓ Travco Jetty Safe (خزينة السقالة)</option>
          <option value="BANK_MISR_EGP">🏦 Banque Misr EGP (بنك مصر ج.م)</option>
          <option value="BANK_MISR_USD">🏦 Banque Misr USD (بنك مصر دولار)</option>
          <option value="BANK_MISR_EUR">🏦 Banque Misr EUR (بنك مصر يورو)</option>
          <option value="INSTAPAY_TRANSFER">📲 InstaPay / Bank Transfer (إنستا باي)</option>
          <option value="OFFSET_SETTLEMENT">⚖️ Account Offset (مقاصة / تسوية)</option>
          <option value="PAYROLL_DEDUCTION">📋 Payroll Deduction (خصم شيت المرتبات)</option>
        </select>
      </div>

      <div>
        <label>Amount / المبلغ *</label>
        <input type="number" step="0.01" min="0.01" id="ptmAmount" required placeholder="e.g. 15000">
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
        <div>
          <label>Currency / العملة</label>
          <select id="ptmCurrency">
            <option value="EGP">EGP (ج.م)</option>
            <option value="USD">USD ($)</option>
            <option value="EUR">EUR (€)</option>
            <option value="GBP">GBP (£)</option>
          </select>
        </div>
        <div>
          <label>Date / التاريخ</label>
          <input type="date" id="ptmDate" value="<?= gmdate('Y-m-d') ?>">
        </div>
      </div>

      <div>
        <label>Receipt / Voucher # (رقم الإيصال)</label>
        <input type="text" id="ptmReceiptNo" placeholder="Auto-generated if left blank">
      </div>

      <div style="display:flex;align-items:flex-end;">
        <label style="display:inline-flex;align-items:center;gap:7px;margin:0;cursor:pointer;color:#34d399;font-weight:700;padding-bottom:8px;">
          <input type="checkbox" id="ptmSyncCashbox" checked style="width:auto;">
          🧾 Post Live Safe Voucher (إذن خزينة)
        </label>
      </div>

      <div>
        <label>Description (English)</label>
        <input type="text" id="ptmDescEn" placeholder="e.g. Second installment on Safari statement">
      </div>
      <div>
        <label>البيان بالعربي</label>
        <input type="text" id="ptmDescAr" dir="rtl" placeholder="مثال: دفعة قسط تحت حساب رحلات الشهر">
      </div>

      <div style="grid-column:1/-1;display:flex;gap:8px;justify-content:flex-end;">
        <button type="button" class="btn" onclick="document.getElementById('partyTransactionModal').style.display='none'">Cancel</button>
        <button type="submit" class="btn btn-emerald" style="padding:10px 18px;font-weight:900;">💾 Save &amp; Update Final Balance</button>
      </div>
    </form>
  </div>
</div>

<nav id="mobileBottomNav" class="bottom-dock"></nav>

<div id="toast" class="toast"></div>

<script src="/assets/erp/qrcode.js"></script>
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
  reportMonth: '2026-10',
  walletMonth: '2026-10',
  walletSeller: '',
  suppliersHub: null,
  suppliersSubTab: 'costs',
  suppliersCat: 'ALL',
  suppliersFilterSup: 'ALL',
  suppliersSearch: '',
  staffRewards: null,
  staffRewardsType: 'ALL',
  staffRewardsMonth: 'ALL',
  accountsDir: null,
  accountsFilterType: 'ALL',
  accountsSearch: '',
  activeDossier: null,
  dossierTargetType: '',
  dossierTargetCode: '',
  dossierLineFilter: 'ALL',
  prevTabBeforeDossier: '',
  fbkSafariSpec: {},
  pnlViewMode: 'consolidated',
  bankViewMode: 'all3',
  pitchLang: 'en',
  pitchCurr: 'EUR',
  pitchCat: 'ALL',
  pitchQuery: '',
  pitchHotel: 'Cyrene Sharm Hotel',
  pitchHotelSearch: '',
  pitchDrawerOpen: false,
  pitchPickup: '07:55–08:10',
  pitchSaleMode: 'INSHEET',
  pitchSupplierCost: 0,
  pitchPax: 2,
  pitchChildren: 0,
  pitchInfants: 0,
  pitchDiscType: 'NONE',
  pitchDiscVal: 0,
  walletPromoType: 'PERCENT',
  walletPromoVal: 10,
  walletPromoUrl: '',
  sellerBiHorizon: 'monthly',
  sellerBiFrom: '2026-10-01',
  sellerBiTo: '2026-10-31',
  sellerBi: null,
  notifications: [],
  unreadNotifs: 0,
  inboxData: null,
  inboxFilter: 'all',
  unreadInbox: 0,
  dismissedPinId: 0,
  inboxDraft: {
    msg_type: 'CONGRATULATIONS',
    priority: 'CELEBRATION',
    audience_type: 'ALL',
    audience_target: '',
    spotlight_username: '',
    badge_icon: '🎉',
    title_en: '',
    title_ar: '',
    body_en: '',
    body_ar: '',
    action_tab: '',
    is_pinned: true
  },
  bi: null,
  ws: null,
  sellerCatalog: null,
  sellerWallet: null,
  monthlyPack: null,
  guestCatalog: null,
  guestPortal: null,
  guestLang: 'en',
  guestCurr: 'EUR',
  guestCat: 'ALL',
  guestQuery: '',
  guestCrm: null,
  guestCrmFilter: 'all',
  guestCrmSearch: '',
  directory: []
};

function isGuestCustomerUser() {
  return Boolean(STATE.user && STATE.user.role_code === 'GUEST_CUSTOMER');
}

function isSalesRepUser() {
  const u = STATE.user || {};
  return u.role_code === 'SALES_REP' || (
    hasPerm('sales.create_voucher', 'sales.view_own_sales') &&
    !hasPerm('bi.view_executive', 'gl.view_all_ledgers', 'ops.view_manifests', 'safe.view_balances', 'bi.view_partner_fleet', 'crew.view_own_manifest')
  );
}

function fillAccount(uname) {
  document.getElementById('inpUser').value = uname;
  renderDirectory();
  const pw = document.getElementById('inpPass');
  pw.value = '';
  pw.focus();
}

function fmt(n, dec = 0) {
  const raw = Number(n);
  const v = Number.isFinite(raw) ? raw : 0;
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
  document.getElementById('langBtn').textContent = STATE.lang === 'ar' ? '🇬🇧 English' : '🇪🇬 عربي / AR';
  document.getElementById('appTitle').textContent = STATE.lang === 'ar'
    ? 'سي كولدج دايف كلوب — التطبيق السريع للمبيعات والإدارة'
    : 'Sea College Dive Club — Unified Ecosystem App';
  return renderApp();
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
  if (dirRes && dirRes.ok) {
    STATE.directory = dirRes.accounts || [];
    if (dirRes.csrf_token) STATE.csrf = dirRes.csrf_token;
    renderDirectory();
  }
  const me = await apiGet({ action: 'me' });
  if (me && me.csrf_token) STATE.csrf = me.csrf_token;
  if (me && me.authenticated) {
    STATE.user = me.user;
    STATE.perms = me.permissions || [];
    STATE.unreadInbox = Number(me.unread_inbox_count || 0);
    await loadUserData();
  } else {
    document.getElementById('loginView').style.display = 'block';
    document.getElementById('appView').style.display = 'none';
  }
}

function renderAvatarHtml(u, size = 44) {
  const name = String((u && (u.display_name_en || u.username)) || 'SC').trim();
  const initials = name.split(/\s+/).slice(0, 2).map((w) => (w[0] || '')).join('').toUpperCase() || 'SC';
  const fSize = Math.max(11, Math.round(size * 0.36));
  if (u && u.avatar_url) {
    return `<img src="${esc(u.avatar_url)}" alt="${esc(name)}" class="avatar-circle" style="width:${size}px;height:${size}px;" onerror="this.outerHTML='<span class=\\'avatar-circle\\' style=\\'width:${size}px;height:${size}px;font-size:${fSize}px;\\'>${esc(initials)}</span>'">`;
  }
  return `<span class="avatar-circle" style="width:${size}px;height:${size}px;font-size:${fSize}px;">${esc(initials)}</span>`;
}

function waDigits(phone) {
  return String(phone || '').replace(/[^0-9]/g, '');
}

function renderDirectory() {
  const box = document.getElementById('roleDirectoryList');
  if (!box) return;
  const curVal = (document.getElementById('inpUser') && document.getElementById('inpUser').value) || '';
  const sellers = STATE.directory.filter((a) => a.role_code === 'SALES_REP');
  const others = STATE.directory.filter((a) => a.role_code !== 'SALES_REP');
  const tileHtml = (a) => {
    const shortEn = String(a.display_name_en || a.username).replace(/\s*\(.*?\)\s*/g, '');
    const shortAr = String(a.display_name_ar || '').replace(/\s*\(.*?\)\s*/g, '');
    const sel = curVal && curVal.toLowerCase() === String(a.username || '').toLowerCase();
    return `
      <button type="button" class="seller-login-tile ${sel ? 'selected' : ''}" onclick="fillAccount('${esc(a.username)}')">
        ${renderAvatarHtml(a, 38)}
        <div style="min-width:0;overflow:hidden;">
          <div style="font-size:12.5px;font-weight:900;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc(STATE.lang === 'ar' ? (shortAr || shortEn) : shortEn)}</div>
          <div style="font-size:10.5px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">@${esc(a.username)}</div>
        </div>
      </button>
    `;
  };
  box.innerHTML = `
    ${sellers.length ? `
      <div style="font-size:11.5px;font-weight:800;color:#34d399;margin-bottom:2px;">💼 ${STATE.lang === 'ar' ? 'فريق المبيعات (اضغط على اسمك):' : 'Sales Team (Tap Your Name):'}</div>
      <div class="seller-login-grid">${sellers.map(tileHtml).join('')}</div>
    ` : ''}
    ${others.length ? `
      <details style="margin-top:6px;padding-top:6px;border-top:1px solid var(--border);">
        <summary style="cursor:pointer;font-size:12px;font-weight:800;color:var(--cyan);">👑 ${STATE.lang === 'ar' ? 'الملاك والحسابات والتشغيل والمراكب' : 'Owners, Accounting, Ops & Boat Crew'} (${others.length})</summary>
        <div class="seller-login-grid" style="margin-top:8px;">${others.map(tileHtml).join('')}</div>
      </details>
    ` : ''}
  `;
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
  STATE.sellerCatalog = null;
  STATE.sellerWallet = null;
  STATE.monthlyPack = null;
  STATE.inboxData = null;
  await loadUserData();
}

async function doLogout() {
  await apiGet({ action: 'logout' });
  STATE.user = null;
  STATE.perms = [];
  STATE.bi = null;
  STATE.ws = null;
  STATE.sellerBi = null;
  STATE.sellerCatalog = null;
  STATE.sellerWallet = null;
  STATE.monthlyPack = null;
  STATE.inboxData = null;
  STATE.suppliersHub = null;
  STATE.staffRewards = null;
  STATE.accountsDir = null;
  STATE.activeDossier = null;
  STATE.notifications = [];
  STATE.unreadNotifs = 0;
  STATE.unreadInbox = 0;
  if (document.body) document.body.className = '';
  document.getElementById('userBadge').style.display = 'none';
  document.getElementById('notifBtn').style.display = 'none';
  const ib = document.getElementById('inboxTopBtn');
  if (ib) ib.style.display = 'none';
  document.getElementById('logoutBtn').style.display = 'none';
  document.getElementById('fabFeedback').style.display = 'none';
  document.getElementById('mobileBottomNav').className = 'bottom-dock';
  document.getElementById('appView').style.display = 'none';
  document.getElementById('loginView').style.display = 'block';
  renderDirectory();
}

function syncNotifBadge() {
  const nb = document.getElementById('notifBtn');
  const badge = document.getElementById('notifCountBadge');
  const ib = document.getElementById('inboxTopBtn');
  const ibLbl = document.getElementById('inboxTopLbl');
  const ibBadge = document.getElementById('inboxCountBadge');
  if (!STATE.user) {
    if (nb) nb.style.display = 'none';
    if (ib) ib.style.display = 'none';
    return;
  }
  if (nb && badge) {
    nb.style.display = 'inline-flex';
    const cnt = Number(STATE.unreadNotifs || 0);
    badge.textContent = String(cnt);
    badge.style.display = cnt > 0 ? 'inline-block' : 'none';
  }
  if (ib && ibBadge) {
    ib.style.display = 'inline-flex';
    if (ibLbl) ibLbl.textContent = STATE.lang === 'ar' ? 'الرسائل' : 'Inbox';
    const icnt = Number(STATE.unreadInbox || 0);
    ibBadge.textContent = String(icnt);
    ibBadge.style.display = icnt > 0 ? 'inline-block' : 'none';
  }
}

async function openCompanyInboxTab() {
  STATE.activeTab = 'company_inbox';
  const res = await apiGet({ action: 'inbox_messages', filter: STATE.inboxFilter || 'all' });
  if (res && res.ok) {
    STATE.inboxData = res;
    STATE.unreadInbox = Number(res.unread_count || 0);
    syncNotifBadge();
  }
  return renderApp();
}

async function openNotificationsModal() {
  const res = await apiGet({ action: 'notifications' });
  if (res && res.ok) {
    STATE.notifications = res.notifications || [];
    STATE.unreadNotifs = Number(res.unread_count || 0);
    if (res.unread_inbox_count !== undefined) STATE.unreadInbox = Number(res.unread_inbox_count || 0);
    syncNotifBadge();
  }
  const box = document.getElementById('notifListBox');
  const list = STATE.notifications || [];
  if (!list.length) {
    box.innerHTML = `<div style="padding:18px;text-align:center;color:var(--muted);font-size:13px;">No notifications yet — new shareable link bookings, confirmations &amp; settlements appear here instantly!</div>`;
  } else {
    box.innerHTML = list.map((n) => {
      const unread = Number(n.is_read || 0) === 0;
      const vno = String(n.related_voucher_no || '');
      const isNewLink = String(n.notif_type || '') === 'NEW_LINK_BOOKING';
      const isInboxMsg = String(n.notif_type || '').startsWith('INBOX_');
      return `
        <div style="padding:11px 12px;border-radius:11px;background:${unread ? 'rgba(0,210,255,0.11)' : 'rgba(6,21,38,0.68)'};border:1px solid ${unread ? 'rgba(0,210,255,0.45)' : 'var(--border)'};">
          <div style="display:flex;justify-content:space-between;gap:8px;align-items:center;">
            <b style="font-size:13px;color:${unread ? 'var(--cyan)' : 'var(--text)'};">${esc(n.title)}</b>
            <span style="font-size:10.5px;color:var(--muted);">${esc(String(n.created_at || '').slice(0, 16))}</span>
          </div>
          <div style="font-size:12px;color:#cbd5e1;margin-top:4px;line-height:1.45;">${esc(n.body)}</div>
          <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px;">
            ${isNewLink && vno ? `<button type="button" class="btn btn-emerald" onclick="sellerConfirmByVoucher('${esc(vno)}')">✅ Confirm Booking to Reservations</button>` : ''}
            ${vno ? `<button type="button" class="btn" onclick="document.getElementById('notifModal').style.display='none';STATE.activeTab='vouchers_ops';renderApp();">🎫 Open Voucher ${esc(vno)}</button>` : ''}
            ${isInboxMsg ? `<button type="button" class="btn btn-amber" onclick="document.getElementById('notifModal').style.display='none';openCompanyInboxTab();">📨 Open Inbox</button>` : ''}
          </div>
        </div>
      `;
    }).join('');
  }
  document.getElementById('notifModal').style.display = 'flex';
}

async function markAllNotifsRead() {
  await apiPost({ action: 'mark_notifications_read' });
  STATE.unreadNotifs = 0;
  STATE.notifications = (STATE.notifications || []).map((n) => ({ ...n, is_read: 1 }));
  syncNotifBadge();
  openNotificationsModal();
}

async function loadUserData() {
  document.getElementById('loginView').style.display = 'none';
  document.getElementById('appView').style.display = 'block';
  document.getElementById('logoutBtn').style.display = 'inline-flex';
  const isGuest = isGuestCustomerUser();
  document.getElementById('fabFeedback').style.display = isGuest ? 'none' : 'block';
  const ub = document.getElementById('userBadge');
  ub.style.display = 'inline-flex';
  const dispName = STATE.lang === 'ar'
    ? (STATE.user.display_name_ar || STATE.user.display_name_en || STATE.user.username)
    : (STATE.user.display_name_en || STATE.user.username);
  ub.innerHTML = `${renderAvatarHtml(STATE.user, 24)} <span>${esc(dispName)}</span>`;

  if (isGuest) {
    await Promise.all([
      apiGet({ action: 'role_workspace' }).then((r) => {
        if (r && r.ok) {
          STATE.ws = r;
          STATE.notifications = r.notifications || [];
          STATE.unreadNotifs = Number(r.unread_notifications_count || 0);
          STATE.unreadInbox = Number(r.unread_inbox_count || 0);
        }
      }),
      apiGet({ action: 'guest_portal_data' }).then((r) => {
        if (r && r.ok) {
          STATE.guestPortal = r;
          if (r.guest_account && r.guest_account.preferred_lang) {
            STATE.guestLang = r.guest_account.preferred_lang;
          }
        }
      })
    ]);
    const gcRes = await apiGet({ action: 'guest_catalog', lang: STATE.guestLang || 'en' });
    if (gcRes && gcRes.ok) STATE.guestCatalog = gcRes;
    syncNotifBadge();
    await renderApp();
    return;
  }

  const jobs = [
    apiGet({ action: 'role_workspace' }).then((r) => {
      if (r && r.ok) {
        STATE.ws = r;
        STATE.notifications = r.notifications || [];
        STATE.unreadNotifs = Number(r.unread_notifications_count || 0);
        if (r.unread_inbox_count !== undefined) STATE.unreadInbox = Number(r.unread_inbox_count || 0);
      }
    }),
    apiGet({ action: 'inbox_messages', filter: STATE.inboxFilter || 'all' }).then((r) => {
      if (r && r.ok) {
        STATE.inboxData = r;
        STATE.unreadInbox = Number(r.unread_count || 0);
      }
    })
  ];
  if (hasPerm('bi.view_executive')) {
    jobs.push(apiGet({ action: 'executive_bi', horizon: STATE.horizon, year: STATE.yearFilter, month: STATE.monthFilter }).then((r) => { if (r && r.ok) STATE.bi = r; }));
  }
  if (hasPerm('bi.view_executive', 'gl.view_all_ledgers')) {
    jobs.push(apiGet({ action: 'monthly_report_pack', month: STATE.reportMonth }).then((r) => { if (r && r.ok) STATE.monthlyPack = r; }));
    jobs.push(apiGet({ action: 'seller_performance_bi', horizon: STATE.sellerBiHorizon, date_from: STATE.sellerBiFrom, date_to: STATE.sellerBiTo }).then((r) => { if (r && r.ok) STATE.sellerBi = r; }));
  }
  if (hasPerm('sales.create_voucher', 'sales.view_own_sales')) {
    jobs.push(apiGet({ action: 'seller_catalog', lang: STATE.pitchLang }).then((r) => { if (r && r.ok) STATE.sellerCatalog = r; }));
  }
  if (hasPerm('sales.view_own_sales', 'bi.view_executive', 'gl.view_all_ledgers', 'hr.manage_payroll')) {
    const wParams = { action: 'seller_wallet', month: STATE.walletMonth };
    if (STATE.walletSeller) wParams.seller = STATE.walletSeller;
    jobs.push(apiGet(wParams).then((r) => { if (r && r.ok) STATE.sellerWallet = r; }));
  }
  if (hasPerm('crm.manage_leads_ads', 'bi.view_executive')) {
    jobs.push(apiGet({ action: 'marketing_guest_accounts', filter: STATE.guestCrmFilter || 'all' }).then((r) => { if (r && r.ok) STATE.guestCrm = r; }));
  }
  await Promise.all(jobs);
  syncNotifBadge();
  await renderApp();
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

async function changeSellerBiHorizon(h, fromDt = null, toDt = null) {
  if (h) STATE.sellerBiHorizon = h;
  if (fromDt !== null) STATE.sellerBiFrom = fromDt;
  if (toDt !== null) STATE.sellerBiTo = toDt;
  const res = await apiGet({
    action: 'seller_performance_bi',
    horizon: STATE.sellerBiHorizon,
    date_from: STATE.sellerBiFrom,
    date_to: STATE.sellerBiTo
  });
  if (res && res.ok) {
    STATE.sellerBi = res;
    renderApp();
  }
}

async function saveOwnerTargets(toggleActive = null) {
  const curCfg = (STATE.sellerBi && STATE.sellerBi.target_config) || { is_active: true, target_daily_egp: 15000, target_weekly_egp: 90000, target_monthly_egp: 350000 };
  const nextActive = toggleActive !== null ? Boolean(toggleActive) : Boolean(curCfg.is_active);
  const dEl = document.getElementById('tgtDailyInp');
  const wEl = document.getElementById('tgtWeeklyInp');
  const mEl = document.getElementById('tgtMonthlyInp');
  const res = await apiPost({
    action: 'save_seller_targets',
    seller_username: '*',
    is_active: nextActive ? 1 : 0,
    target_daily_egp: dEl ? Number(dEl.value || curCfg.target_daily_egp) : curCfg.target_daily_egp,
    target_weekly_egp: wEl ? Number(wEl.value || curCfg.target_weekly_egp) : curCfg.target_weekly_egp,
    target_monthly_egp: mEl ? Number(mEl.value || curCfg.target_monthly_egp) : curCfg.target_monthly_egp
  });
  if (res && res.ok) {
    showToast(nextActive ? '🎯 Seller Targets Activated!' : '⏸ Seller Targets Hidden from Profiles');
    await changeSellerBiHorizon(STATE.sellerBiHorizon);
    if (STATE.sellerWallet) {
      const wp = { action: 'seller_wallet', month: STATE.walletMonth };
      if (STATE.walletSeller) wp.seller = STATE.walletSeller;
      const wRes = await apiGet(wp);
      if (wRes && wRes.ok) STATE.sellerWallet = wRes;
    }
    renderApp();
  }
}

async function saveOwnerDiscountPolicy(toggleEnabled = null) {
  const curPol = (STATE.sellerBi && STATE.sellerBi.discount_policy) || { is_enabled: true, max_discount_pct: 20, max_discount_eur: 50 };
  const nextEnabled = toggleEnabled !== null ? Boolean(toggleEnabled) : Boolean(curPol.is_enabled);
  const pEl = document.getElementById('discMaxPctInp');
  const eEl = document.getElementById('discMaxEurInp');
  const res = await apiPost({
    action: 'save_seller_discount_policy',
    seller_username: '*',
    is_discount_enabled: nextEnabled ? 1 : 0,
    max_discount_pct: pEl ? Number(pEl.value || curPol.max_discount_pct) : curPol.max_discount_pct,
    max_discount_eur: eEl ? Number(eEl.value || curPol.max_discount_eur) : curPol.max_discount_eur
  });
  if (res && res.ok) {
    showToast(nextEnabled ? '🎁 Seller Special Discounts Enabled!' : '⏸ Seller Special Discounts Disabled');
    await changeSellerBiHorizon(STATE.sellerBiHorizon);
    if (STATE.sellerCatalog) {
      const cRes = await apiGet({ action: 'seller_catalog', lang: STATE.pitchLang });
      if (cRes && cRes.ok) STATE.sellerCatalog = cRes;
    }
    if (STATE.sellerWallet) {
      const wp = { action: 'seller_wallet', month: STATE.walletMonth };
      if (STATE.walletSeller) wp.seller = STATE.walletSeller;
      const wRes = await apiGet(wp);
      if (wRes && wRes.ok) STATE.sellerWallet = wRes;
    }
    renderApp();
  }
}

function exportUrl(report, format, extra = {}) {
  const qs = new URLSearchParams({
    report,
    format,
    horizon: STATE.horizon,
    year: STATE.yearFilter,
    month: extra.month || STATE.monthFilter || STATE.reportMonth || '2026-10',
    ...extra
  });
  return '/handlers/erp_export.php?' + qs.toString();
}

function buildTabsForUser() {
  const ar = STATE.lang === 'ar';
  if (isGuestCustomerUser()) {
    return [
      { id: 'guest_book_trips', ico: '🌴', short: ar ? 'احجز رحلة' : 'Book Trips', label: ar ? '🌴 احجز رحلات شرم الشيخ القادمة' : '🌴 Book Future Trips' },
      { id: 'guest_my_trips', ico: '🎫', short: ar ? 'حجوزاتي' : 'My Trips', label: ar ? '🎫 حجوزاتي ومواعيد الباص' : '🎫 My Bookings & Pickup' },
      { id: 'my_profile', ico: '👤', short: ar ? 'حسابي' : 'Profile', label: ar ? '👤 بياناتي وكلمة المرور' : '👤 My VIP Profile' }
    ];
  }
  const isSalesRepPrimary = isSalesRepUser();
  const inbBadgeTxt = STATE.unreadInbox > 0 ? ` (${STATE.unreadInbox})` : '';

  if (isSalesRepPrimary) {
    return [
      { id: 'seller_pitch', ico: '⚡', short: ar ? 'بيع رحلة' : 'Pitch', label: ar ? '⚡ بيع رحلة للضيف (Pitch)' : '⚡ Seller Pitch & Fast Book' },
      { id: 'seller_wallet', ico: '💰', short: ar ? 'عمولتي و QR' : 'QR & Cash', label: ar ? '💰 عمولتي وباركود QR' : '💰 My QR & Commissions' },
      { id: 'vouchers_ops', ico: '🎫', short: ar ? 'حجوزاتي' : 'My Trips', label: ar ? '🎫 حجوزاتي وتأكيدها' : '🎫 My Trips & Confirmations' },
      { id: 'company_inbox', ico: '📨', short: ar ? 'الرسائل' : 'Inbox', badge: STATE.unreadInbox, label: (ar ? '📨 رسائل الإدارة والتهاني' : '📨 Inbox & Announcements') + inbBadgeTxt },
      { id: 'my_profile', ico: '👤', short: ar ? 'حسابي' : 'Profile', label: ar ? '👤 صورتي وأرقام الفريق' : '👤 My Photo & Settings' }
    ];
  }

  const tabs = [];
  if (hasPerm('bi.view_executive')) {
    tabs.push({ id: 'exec_bi', ico: '📊', short: 'Owner BI', label: ar ? '📊 لوحة الملاك وأداء المناديب' : '📊 Owner BI & Seller Awards' });
  }
  tabs.push({ id: 'company_inbox', ico: '📨', short: ar ? 'الرسائل' : 'Inbox', badge: STATE.unreadInbox, label: (ar ? '📨 صندوق الرسائل وإعلانات الإدارة' : '📨 Company Inbox & Broadcasts') + inbBadgeTxt });
  if (hasPerm('bi.view_executive', 'gl.view_all_ledgers')) {
    tabs.push({ id: 'monthly_reports', ico: '📑', short: 'Monthly', label: ar ? '📑 التقارير الشهرية الختامية (Monthly)' : '📑 Monthly Reports Pack' });
  }
  if (hasPerm('gl.view_all_ledgers')) {
    tabs.push({ id: 'accounting_gl', ico: '📚', short: 'Accounting', label: ar ? '📚 الحسابات والتسويات والمرتبات' : '📚 Accounting, Settlements & Payroll' });
  }
  if (hasPerm('gl.view_all_ledgers', 'bi.view_executive', 'hr.manage_payroll', 'safe.view_balances', 'ops.assign_boat_driver')) {
    tabs.push({ id: 'accounts_hub', ico: '📒', short: ar ? 'كشوف الحساب' : 'Accounts', label: ar ? '📒 كشوف حسابات الموردين والمبيعات والموظفين والدفعات' : '📒 Accounts, Installments & Final Balances' });
  }
  if (hasPerm('gl.view_all_ledgers', 'bi.view_executive', 'ops.view_manifests')) {
    tabs.push({ id: 'suppliers_costs_hub', ico: '🏭', short: ar ? 'الموردين والتكلفة' : 'Suppliers', label: ar ? '🏭 الموردين وتكلفة الرحلات والسفاري' : '🏭 Suppliers & Trip Cost Matrix' });
  }
  if (hasPerm('bi.view_executive', 'gl.view_all_ledgers', 'hr.manage_payroll', 'safe.record_tx')) {
    tabs.push({ id: 'staff_rewards', ico: '🎁', short: ar ? 'مكافآت وبدلات' : 'Bonuses', label: ar ? '🎁 مكافآت وبدلات وسلف الموظفين' : '🎁 Staff Bonus & Allowances' });
  }
  if (hasPerm('bi.view_executive')) {
    tabs.push({ id: 'profitability', ico: '🏆', short: 'ROI', label: ar ? '🏆 الأعلى مبيعاً وربحية الرحلات والمراكب' : '🏆 Best Sellers, Trips & Boats ROI' });
    tabs.push({ id: 'exports_hub', ico: '📥', short: 'Exports', label: ar ? '📥 تصدير الشيتات والتقارير' : '📥 Export Sheets (.xlsx / .csv)' });
  }
  if (hasPerm('bi.view_partner_fleet') && !hasPerm('bi.view_executive')) {
    tabs.push({ id: 'partner_fleet', ico: '🛥️', short: 'Vessels', label: ar ? '🛥️ حصص المراكب وعمرة الازق' : '🛥️ My Partner Vessels & Drydock' });
  }
  if (hasPerm('safe.view_balances')) {
    tabs.push({ id: 'treasury_safe', ico: '💵', short: 'Safe', label: ar ? '💵 الخزينة والعهد متعددة العملات' : '💵 Multi-Currency Safe & Cashbox' });
  }
  if (hasPerm('ops.view_manifests', 'jetty.scan_boarding_collect_rest', 'sales.view_own_sales', 'crew.view_own_manifest')) {
    tabs.push({ id: 'vouchers_ops', ico: '🎫', short: 'Reservations', label: ar ? '🎫 الحجوزات والتشغيل والسقالة' : '🎫 Reservations, Dispatch & Jetty' });
  }
  if (hasPerm('sales.create_voucher')) {
    tabs.push({ id: 'seller_pitch', ico: '⚡', short: 'Pitch', label: ar ? '⚡ عرض المبيعات الذكي (Pitch)' : '⚡ Seller Pitch & Fast Book' });
    tabs.push({ id: 'new_voucher', ico: '➕', short: 'Voucher', label: ar ? '➕ إصدار فوتشر باركود' : '➕ Issue QR Voucher' });
  }
  if (hasPerm('sales.view_own_sales', 'bi.view_executive', 'gl.view_all_ledgers', 'hr.manage_payroll')) {
    tabs.push({ id: 'seller_wallet', ico: '💰', short: 'Wallets', label: ar ? '💰 محافظ عمولات المناديب و QR' : '💰 Seller QR & Commissions' });
  }
  if (hasPerm('jetty.manage_gear_photo')) {
    tabs.push({ id: 'gear_assets', ico: '🤿', short: 'Gear', label: ar ? '🤿 جرد معدات الغوص S.C#' : '🤿 Dive Gear Assets (S.C #)' });
  }
  if (hasPerm('crm.manage_leads_ads', 'bi.view_executive')) {
    tabs.push({ id: 'guest_crm_vault', ico: '🎯', short: ar ? 'عملاء فعليون' : 'Real Guests', label: ar ? '🎯 حسابات وإيميلات العملاء الفعليين (Marketing)' : '🎯 Real Guests Accounts & Marketing CRM' });
  }
  if (hasPerm('crm.manage_leads_ads')) {
    tabs.push({ id: 'website_crm', ico: '🌐', short: 'CRM', label: ar ? '🌐 حجوزات الموقع والواتساب والإعلانات' : '🌐 Website Leads, Check-In & Ads CRM' });
  }
  if (hasPerm('bi.view_executive')) {
    tabs.push({ id: 'team_profiles', ico: '👥', short: 'Team', label: ar ? '👥 ملفات فريق العمل (34)' : '👥 Team Profiles & Directory' });
    tabs.push({ id: 'trial_pilot', ico: '🧪', short: 'Feedback', label: ar ? '🧪 إدارة النسخة التجريبية والملاحظات' : '🧪 Trial Launch & Feedback' });
  } else {
    if (hasPerm('gl.view_all_ledgers')) {
      tabs.push({ id: 'trial_pilot', ico: '🧪', short: 'Feedback', label: ar ? '🧪 ملاحظات النسخة التجريبية' : '🧪 Trial Feedback Inbox' });
    }
    tabs.push({ id: 'team_profiles', ico: '👥', short: 'Team', label: ar ? '👥 دليل أرقام وإيميلات الفريق' : '👥 Team Contacts Directory' });
  }
  tabs.push({ id: 'my_profile', ico: '👤', short: 'Profile', label: ar ? '👤 ملفي الشخصي وصورتي' : '👤 My Profile' });
  return tabs;
}

function renderPinnedInboxBanner(ar) {
  const slot = document.getElementById('pinnedInboxBannerSlot');
  if (!slot) return;
  // Never display internal company announcements on guest-facing tabs!
  if (isGuestCustomerUser() || STATE.activeTab === 'seller_pitch' || STATE.activeTab === 'company_inbox') {
    slot.innerHTML = '';
    return;
  }
  const pin = (STATE.inboxData && STATE.inboxData.pinned_banner) || (STATE.ws && STATE.ws.inbox_pinned_banner) || null;
  if (!pin || Number(pin.id) === Number(STATE.dismissedPinId)) {
    slot.innerHTML = '';
    return;
  }
  const title = ar ? (pin.title_ar || pin.title_en) : (pin.title_en || pin.title_ar);
  const body = ar ? (pin.body_ar || pin.body_en) : (pin.body_en || pin.body_ar);
  const sName = ar ? (pin.sender_name_ar || pin.sender_name_en) : (pin.sender_name_en || pin.sender_name_ar);
  const quickReacts = ['👏', '🎉', '🔥', '❤️', '👍'];
  slot.innerHTML = `
    <div class="card" style="margin-bottom:12px;padding:11px 14px;background:linear-gradient(135deg,rgba(245,158,11,0.16),rgba(0,210,255,0.11));border-color:rgba(251,191,36,0.5);">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:220px;">
          <span style="font-size:24px;line-height:1;">${esc(pin.badge_icon || '🎉')}</span>
          <div style="min-width:0;">
            <div style="font-size:13.5px;font-weight:900;color:#fde68a;">${esc(title)}</div>
            <div style="font-size:12px;color:#e2e8f0;margin-top:2px;">${esc(String(body || '').slice(0, 140))} <span style="color:var(--muted);">— ${esc(sName)}</span></div>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;">
          ${quickReacts.map((em) => `<button type="button" class="react-chip ${pin.my_reaction === em ? 'active' : ''}" style="padding:4px 8px;font-size:12px;" onclick="reactToInboxMessage(${Number(pin.id)}, '${em}')">${em}</button>`).join('')}
          <button type="button" class="btn btn-amber" style="padding:5px 10px;" onclick="openCompanyInboxTab()">📨 ${ar ? 'افتح الرسائل' : 'Inbox'}</button>
          <button type="button" class="btn" style="padding:5px 8px;" onclick="STATE.dismissedPinId=${Number(pin.id)};renderPinnedInboxBanner(STATE.lang==='ar');">✕</button>
        </div>
      </div>
    </div>
  `;
}

function renderApp() {
  if (!STATE.user) {
    renderDirectory();
    return;
  }
  const ar = STATE.lang === 'ar';
  const u = STATE.user;
  const sellerMode = isSalesRepUser();
  const guestMode = isGuestCustomerUser();
  if (document.body) {
    document.body.className = sellerMode ? 'role-seller' : '';
  }
  syncNotifBadge();
  const ibTop = document.getElementById('inboxTopBtn');
  if (ibTop && guestMode) ibTop.style.display = 'none';
  const btnBanStmt = document.getElementById('btnBannerMyStatement');
  const btnBanRew = document.getElementById('btnBannerRewards');
  const btnBanInb = document.getElementById('btnBannerInbox');
  const btnBanDir = document.getElementById('btnBannerTeamDir');
  if (btnBanStmt) {
    btnBanStmt.style.display = guestMode ? 'none' : 'inline-flex';
    btnBanStmt.innerHTML = ar ? '📒 كشف حسابي والرصيد' : '📒 My Statement &amp; Balance';
  }
  if (btnBanRew) {
    btnBanRew.style.display = guestMode ? 'none' : 'inline-flex';
    btnBanRew.innerHTML = ar ? '🎁 المكافآت والبدلات' : '🎁 Bonus &amp; Allowances';
  }
  if (btnBanInb) btnBanInb.style.display = guestMode ? 'none' : 'inline-flex';
  if (btnBanDir) btnBanDir.style.display = guestMode ? 'none' : 'inline-flex';

  const dispName = ar ? (u.display_name_ar || u.display_name_en || u.username || '') : (u.display_name_en || u.username || '');
  document.getElementById('userBadge').innerHTML = `${renderAvatarHtml(u, 24)} <span>${esc(dispName)}</span>`;
  document.getElementById('welcomeAvatar').innerHTML = renderAvatarHtml(u, (sellerMode || guestMode) ? 44 : 62);
  document.getElementById('welcomeName').textContent = dispName;

  if (guestMode) {
    const gp = (STATE.guestPortal && STATE.guestPortal.guest_account) || {};
    const waNum = (STATE.guestPortal && STATE.guestPortal.whatsapp_phone) || '201001106266';
    const conc = ar ? (gp.concierge_name_ar || 'مستشار رحلات سي كوليدج') : (gp.concierge_name_en || 'Sea College VIP Concierge');
    document.getElementById('welcomeScope').textContent = ar
      ? `🌟 عميل سي كوليدج المعتمد • مستشارك: ${conc}`
      : `🌟 Verified Sea College Club Member • Personal Concierge: ${conc}`;
    document.getElementById('welcomeContacts').innerHTML = `
      <button type="button" class="btn btn-primary" style="padding:6px 12px;font-size:12px;" onclick="STATE.activeTab='guest_book_trips';renderApp();">🌴 ${ar ? 'احجز رحلة جديدة' : 'Book Future Trip'}</button>
      <a class="btn btn-emerald" style="padding:6px 12px;font-size:12px;" href="https://wa.me/${esc(waNum)}" target="_blank" rel="noopener">💬 ${ar ? 'تواصل مع المستشار واتساب' : 'WhatsApp Concierge'}</a>
    `;
    document.getElementById('permPills').innerHTML = `<span class="pill" style="border-color:rgba(16,185,129,0.5);color:#34d399;">✅ Verified Club Member</span>`;
  } else if (sellerMode) {
    const slug = u.referral_code || String(u.username || 'fady').replace(/\.sales$/i, '');
    const myBrochureUrl = 'https://seacollegediveclub.com/s/' + encodeURIComponent(slug);
    document.getElementById('welcomeScope').textContent = ar
      ? `مندوب مبيعات معتمد • فرع ${u.branch_scope || 'CYRENE'}`
      : `Official Concierge Seller • ${u.branch_scope || 'CYRENE'}`;
    document.getElementById('welcomeContacts').innerHTML = `
      <a class="btn btn-emerald" style="padding:6px 11px;font-size:12px;" href="https://wa.me/?text=${encodeURIComponent('🌴 Explore all Sharm El Sheikh Diving, Boat, Safari & Cairo Trips with me: ' + myBrochureUrl)}" target="_blank" rel="noopener">💬 ${ar ? 'ابعت الكتالوج واتساب' : 'WhatsApp My Trips'}</a>
      <button type="button" class="btn btn-primary" style="padding:6px 11px;font-size:12px;" onclick="STATE.activeTab='seller_wallet';renderApp();">📱 ${ar ? 'باركودي وفلوسي' : 'My QR & Cash'}</button>
      <button type="button" class="btn btn-emerald" style="padding:6px 11px;font-size:12px;" onclick="openMyOwnStatement()">📒 ${ar ? 'كشف حسابي والرصيد' : 'My Statement'}</button>
      <button type="button" class="btn btn-amber" style="padding:6px 11px;font-size:12px;" onclick="openStaffRewardsTab()">🎁 ${ar ? 'مكافآتي وبدلاتي' : 'Bonuses & Allowances'}</button>
      <button type="button" class="btn" style="padding:6px 11px;font-size:12px;" onclick="openCompanyInboxTab()">📨 ${ar ? 'الرسائل' : 'Inbox'}${STATE.unreadInbox > 0 ? ` (${STATE.unreadInbox})` : ''}</button>
    `;
    document.getElementById('permPills').innerHTML = STATE.perms.slice(0, 5).map((p) => `<span class="pill">${esc(p)}</span>`).join('');
  } else {
    const titleTxt = ar
      ? (u.job_title_ar || u.role_name_ar || u.job_title_en || u.role_code || '')
      : (u.job_title_en || u.role_name_en || u.role_code || '');
    document.getElementById('welcomeScope').textContent =
      `${titleTxt} • Role: ${u.role_code || 'STAFF'} • Branch: ${u.branch_scope || 'ALL'} • Vessels: ${u.vessel_scope || 'ALL'}`;

    const cChips = [];
    if (u.phone) {
      cChips.push(`<a class="contact-chip" href="tel:${esc(String(u.phone).replace(/\s+/g, ''))}">📞 ${esc(u.phone)}</a>`);
      const wd = waDigits(u.phone);
      if (wd.length >= 8) {
        cChips.push(`<a class="contact-chip" href="https://wa.me/${esc(wd)}" target="_blank" rel="noopener">💬 WhatsApp</a>`);
      }
    }
    if (u.email) {
      cChips.push(`<a class="contact-chip" href="mailto:${esc(u.email)}">✉️ ${esc(u.email)}</a>`);
    }
    if (u.referral_code) {
      const myBrochureUrl = 'https://seacollegediveclub.com/s/' + encodeURIComponent(u.referral_code);
      cChips.push(`<a class="contact-chip" href="${esc(myBrochureUrl)}" target="_blank" rel="noopener" style="border-color:rgba(16,185,129,0.5);color:#34d399;">📲 Share All-Trips Link: ${esc(myBrochureUrl)}</a>`);
    }
    document.getElementById('welcomeContacts').innerHTML = cChips.join('');
    document.getElementById('permPills').innerHTML = STATE.perms.slice(0, 5).map((p) => `<span class="pill">${esc(p)}</span>`).join('')
      + (STATE.perms.length > 5 ? `<span class="pill">+${STATE.perms.length - 5} more</span>` : '');
  }

  const tabs = buildTabsForUser();
  const allowSecondaryStaffViews = !guestMode && (STATE.activeTab === 'staff_rewards' || STATE.activeTab === 'entity_dossier');
  if (!STATE.activeTab || (!tabs.some((t) => t.id === STATE.activeTab) && !allowSecondaryStaffViews)) {
    STATE.activeTab = tabs[0] ? tabs[0].id : '';
  }
  renderPinnedInboxBanner(ar);

  document.getElementById('navTabs').innerHTML = tabs.map((t) => `
    <button type="button" class="tab-btn ${STATE.activeTab === t.id ? 'active' : ''}" onclick="STATE.activeTab='${t.id}';renderApp();">${esc(t.label)}</button>
  `).join('');

  const dock = document.getElementById('mobileBottomNav');
  if (dock) {
    dock.className = 'bottom-dock ready';
    dock.innerHTML = tabs.slice(0, 5).map((t) => `
      <button type="button" class="dock-btn ${STATE.activeTab === t.id ? 'active' : ''}" onclick="STATE.activeTab='${t.id}';renderApp();">
        <span class="ico">${esc(t.ico || '•')}${Number(t.badge || 0) > 0 ? `<span class="notif-badge" style="position:absolute;top:2px;right:14%;font-size:9.5px;padding:0 5px;">${Number(t.badge)}</span>` : ''}</span>
        <span>${esc(t.short || t.label.slice(0, 9))}</span>
      </button>
    `).join('');
  }

  const box = document.getElementById('tabContent');
  if (STATE.activeTab === 'accounts_hub') {
    if (STATE.accountsDir) {
      box.innerHTML = renderAccountsHubTab(ar);
      return;
    }
    box.innerHTML = `<div class="card">⏳ ${ar ? 'جاري تحميل كشوف الحسابات والأرصدة والدفعات لجميع الموردين والمبيعات والموظفين والعملاء...' : 'Loading 360° Counterparty & Staff Accounts, Installments & Final Balances...'}</div>`;
    return apiGet({ action: 'accounts_directory', filter_type: STATE.accountsFilterType || 'ALL', q: STATE.accountsSearch || '' }).then((res) => {
      if (res && res.ok) STATE.accountsDir = res;
      box.innerHTML = renderAccountsHubTab(STATE.lang === 'ar');
    });
  } else if (STATE.activeTab === 'entity_dossier') {
    if (STATE.activeDossier && STATE.activeDossier.dossier) {
      box.innerHTML = renderEntityDossierTab(ar);
      return;
    }
    box.innerHTML = `<div class="card">⏳ ${ar ? 'جاري فتح كشف الحساب الشامل والدفعات والرصيد النهائي...' : 'Opening Full Financial Statement, Installments & Final Balance...'}</div>`;
    const qp = { action: 'entity_financial_dossier' };
    if (STATE.dossierTargetType) qp.entity_type = STATE.dossierTargetType;
    if (STATE.dossierTargetCode) qp.entity_code = STATE.dossierTargetCode;
    return apiGet(qp).then((res) => {
      if (res && res.ok) {
        STATE.activeDossier = res;
        box.innerHTML = renderEntityDossierTab(STATE.lang === 'ar');
      } else {
        box.innerHTML = `<div class="card" style="color:#fda4af;">⚠️ ${esc((res && res.error) || 'Unable to load financial dossier')}</div>`;
      }
    });
  } else if (STATE.activeTab === 'suppliers_costs_hub') {
    if (STATE.suppliersHub) {
      box.innerHTML = renderSuppliersCostsHubTab(ar);
      return;
    }
    box.innerHTML = `<div class="card">⏳ ${ar ? 'جاري تحميل الموردين ومصفوفة تكاليف الرحلات والسفاري...' : 'Loading Suppliers & Trip/Safari Cost Matrix...'}</div>`;
    return apiGet({ action: 'suppliers_hub', category: STATE.suppliersCat || 'ALL', supplier: STATE.suppliersFilterSup || 'ALL', q: STATE.suppliersSearch || '' }).then((res) => {
      if (res && res.ok) STATE.suppliersHub = res;
      box.innerHTML = renderSuppliersCostsHubTab(STATE.lang === 'ar');
    });
  } else if (STATE.activeTab === 'staff_rewards') {
    if (STATE.staffRewards) {
      box.innerHTML = renderStaffRewardsTab(ar);
      return;
    }
    box.innerHTML = `<div class="card">⏳ ${ar ? 'جاري تحميل المكافآت والبدلات وسلف الموظفين...' : 'Loading Staff Bonuses, Allowances & Advances...'}</div>`;
    return apiGet({ action: 'staff_bonuses_allowances', type: STATE.staffRewardsType || 'ALL', month: STATE.staffRewardsMonth || 'ALL' }).then((res) => {
      if (res && res.ok) STATE.staffRewards = res;
      box.innerHTML = renderStaffRewardsTab(STATE.lang === 'ar');
    });
  } else if (STATE.activeTab === 'guest_book_trips') {
    if (STATE.guestCatalog) {
      box.innerHTML = renderGuestBookTripsTab(ar);
      return;
    }
    box.innerHTML = `<div class="card">⏳ ${ar ? 'جاري تحميل رحلات شرم الشيخ...' : 'Loading Sharm El Sheikh Trips...'}</div>`;
    return apiGet({ action: 'guest_catalog', lang: STATE.guestLang || 'en' }).then((res) => {
      if (res && res.ok) STATE.guestCatalog = res;
      box.innerHTML = renderGuestBookTripsTab(STATE.lang === 'ar');
    });
  } else if (STATE.activeTab === 'guest_my_trips') {
    if (STATE.guestPortal) {
      box.innerHTML = renderGuestMyTripsTab(ar);
      return;
    }
    box.innerHTML = `<div class="card">⏳ ${ar ? 'جاري تحميل حجوزاتك...' : 'Loading Your Bookings...'}</div>`;
    return apiGet({ action: 'guest_portal_data' }).then((res) => {
      if (res && res.ok) STATE.guestPortal = res;
      box.innerHTML = renderGuestMyTripsTab(STATE.lang === 'ar');
    });
  } else if (STATE.activeTab === 'guest_crm_vault') {
    if (STATE.guestCrm) {
      box.innerHTML = renderGuestCrmVaultTab(ar);
      return;
    }
    box.innerHTML = `<div class="card">⏳ ${ar ? 'جاري تحميل سجل العملاء الفعليين للتسويق...' : 'Loading Verified Real-Customers CRM Vault...'}</div>`;
    return apiGet({ action: 'marketing_guest_accounts', filter: STATE.guestCrmFilter || 'all' }).then((res) => {
      if (res && res.ok) STATE.guestCrm = res;
      box.innerHTML = renderGuestCrmVaultTab(STATE.lang === 'ar');
    });
  } else if (STATE.activeTab === 'company_inbox') {
    if (STATE.inboxData) {
      box.innerHTML = renderCompanyInboxTab(ar);
      return;
    }
    box.innerHTML = `<div class="card">⏳ ${ar ? 'جاري فتح صندوق الرسائل والتهاني...' : 'Loading Company Inbox & Recognition Feed...'}</div>`;
    return apiGet({ action: 'inbox_messages', filter: STATE.inboxFilter || 'all' }).then((res) => {
      if (res && res.ok) {
        STATE.inboxData = res;
        STATE.unreadInbox = Number(res.unread_count || 0);
        syncNotifBadge();
      }
      box.innerHTML = renderCompanyInboxTab(STATE.lang === 'ar');
    });
  } else if (STATE.activeTab === 'seller_pitch') {
    if (STATE.sellerCatalog) {
      box.innerHTML = renderSellerPitchTab(ar);
      return;
    }
    box.innerHTML = `<div class="card">⏳ ${ar ? 'جاري تحميل كتالوج العروض والفيديوهات...' : 'Loading Smart Seller Pitch Catalog...'}</div>`;
    return apiGet({ action: 'seller_catalog', lang: STATE.pitchLang }).then((res) => {
      if (res && res.ok) STATE.sellerCatalog = res;
      box.innerHTML = renderSellerPitchTab(STATE.lang === 'ar');
    });
  } else if (STATE.activeTab === 'seller_wallet') {
    if (STATE.sellerWallet) {
      box.innerHTML = renderSellerWalletTab(ar);
      return;
    }
    box.innerHTML = `<div class="card">⏳ ${ar ? 'جاري تحميل باركود QR ومحفظة العمولات...' : 'Loading Seller QR & Commission Wallet...'}</div>`;
    const wp = { action: 'seller_wallet', month: STATE.walletMonth };
    if (STATE.walletSeller) wp.seller = STATE.walletSeller;
    return apiGet(wp).then((res) => {
      if (res && res.ok) STATE.sellerWallet = res;
      box.innerHTML = renderSellerWalletTab(STATE.lang === 'ar');
    });
  } else if (STATE.activeTab === 'monthly_reports') {
    if (STATE.monthlyPack) {
      box.innerHTML = renderMonthlyReportsPack(ar);
      return;
    }
    box.innerHTML = `<div class="card">⏳ ${ar ? 'جاري تحميل حزمة التقارير الشهرية الختامية...' : 'Loading Monthly Management Closing Pack...'}</div>`;
    return apiGet({ action: 'monthly_report_pack', month: STATE.reportMonth }).then((res) => {
      if (res && res.ok) STATE.monthlyPack = res;
      box.innerHTML = renderMonthlyReportsPack(STATE.lang === 'ar');
    });
  } else if (STATE.activeTab === 'exec_bi' && STATE.bi) {
    box.innerHTML = renderExecBi(ar);
    drawTrendChart();
    if (!STATE.sellerBi) {
      apiGet({ action: 'seller_performance_bi', horizon: STATE.sellerBiHorizon, date_from: STATE.sellerBiFrom, date_to: STATE.sellerBiTo }).then((r) => {
        if (r && r.ok) { STATE.sellerBi = r; if (STATE.activeTab === 'exec_bi') { box.innerHTML = renderExecBi(STATE.lang === 'ar'); drawTrendChart(); } }
      });
    }
  } else if (STATE.activeTab === 'team_profiles') {
    box.innerHTML = renderTeamProfilesTab(ar);
  } else if (STATE.activeTab === 'my_profile') {
    box.innerHTML = renderMyProfileTab(ar) + (sellerMode ? `<div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;"><button type="button" class="btn btn-primary" onclick="STATE.activeTab='team_profiles';renderApp();">👥 ${ar ? 'دليل أرقام الفريق والإدارة' : 'Open Team Phone Directory'}</button><button type="button" class="btn" onclick="openFeedbackModal()">🧪 ${ar ? 'إرسال ملاحظة للإدارة' : 'Send Note / Idea'}</button></div>` : '');
  } else if (STATE.activeTab === 'trial_pilot') {
    box.innerHTML = renderTrialPilotTab(ar);
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

/* ================= COMPANY-WIDE INBOX, CONGRATULATIONS & BROADCAST STUDIO ================= */
function applyInboxPreset(presetKey) {
  const d = STATE.inboxDraft;
  if (presetKey === 'CONGRATS_SELLER') {
    d.msg_type = 'CONGRATULATIONS';
    d.priority = 'CELEBRATION';
    d.audience_type = 'ROLE';
    d.audience_target = 'SALES_REP';
    d.badge_icon = '🏆';
    d.title_en = '🏆 Congratulations to Our Star Seller — Outstanding Performance!';
    d.title_ar = '🏆 ألف مبروك لنجم المبيعات — أداء مشرّف ومجهود رائع!';
    d.body_en = 'Proud of your energy, honesty with guests, and strong bookings! Keep shining — your bonus & Best-Seller medal are active in your wallet.';
    d.body_ar = 'فخورين جداً بمجهودك وأمانتك مع الضيوف وأرقام مبيعاتك القوية! استمر بنفس الحماس — وسام التفوق وعمولتك جاهزين في حسابك.';
    d.action_tab = 'seller_wallet';
    d.is_pinned = true;
  } else if (presetKey === 'ANNOUNCE_ALL') {
    d.msg_type = 'ANNOUNCEMENT';
    d.priority = 'IMPORTANT';
    d.audience_type = 'ALL';
    d.audience_target = '';
    d.spotlight_username = '';
    d.badge_icon = '📢';
    d.title_en = '📢 Important Update from Sea College Management';
    d.title_ar = '📢 إعلان هام من إدارة سي كوليدج لكل الفريق';
    d.body_en = 'Please review today’s schedule and pickup windows carefully so every guest enjoys a smooth 5-star Red Sea experience.';
    d.body_ar = 'يرجى مراجعة مواعيد التحرك والتشغيل اليوم بدقة عشان نضمن لكل ضيف رحلة ممتازة وتجربة 5 نجوم.';
    d.action_tab = '';
    d.is_pinned = true;
  } else if (presetKey === 'TARGET_BONUS') {
    d.msg_type = 'TARGET_ALERT';
    d.priority = 'IMPORTANT';
    d.audience_type = 'ROLE';
    d.audience_target = 'SALES_REP';
    d.spotlight_username = '';
    d.badge_icon = '🎯';
    d.title_en = '🎯 Special Sales Target & Extra Bonus Challenge!';
    d.title_ar = '🎯 تحدي المبيعات ومكافأة إضافية لأبطال الحجز!';
    d.body_en = 'Special reward unlocked this week for top Safari, White Island & Diving bookings! Check your live rank and progress bar in your account.';
    d.body_ar = 'مكافأة خاصة هذا الأسبوع لأعلى مبيعات في رحلات السفاري، وايت أيلاند والغطس! تابع ترتيبك والتارجت في حسابك الآن.';
    d.action_tab = 'seller_wallet';
    d.is_pinned = true;
  } else if (presetKey === 'URGENT_OPS') {
    d.msg_type = 'POLICY_UPDATE';
    d.priority = 'URGENT';
    d.audience_type = 'ALL';
    d.audience_target = '';
    d.spotlight_username = '';
    d.badge_icon = '🚨';
    d.title_en = '🚨 Urgent Operations & Jetty Schedule Notice';
    d.title_ar = '🚨 تنبيه عاجل بخصوص مواعيد السقالة والتحرك';
    d.body_en = 'Please check updated boat and transfer assignments immediately with Operations.';
    d.body_ar = 'يرجى مراجعة تحديث توزيع المراكب والباصات فوراً مع قسم التشغيل.';
    d.action_tab = 'vouchers_ops';
    d.is_pinned = false;
  }
  renderApp();
}

function openQuickCongratsToSeller(username, nameEn, nameAr) {
  STATE.inboxDraft = {
    msg_type: 'CONGRATULATIONS',
    priority: 'CELEBRATION',
    audience_type: 'ROLE',
    audience_target: 'SALES_REP',
    spotlight_username: username,
    badge_icon: '🏆',
    title_en: `🎉 Congratulations ${nameEn} — Star Seller Recognition!`,
    title_ar: `🎉 ألف مبروك يا ${nameAr || nameEn} — تكريم خاص من الإدارة!`,
    body_en: `Big applause from Management to ${nameEn} for outstanding guest bookings and teamwork! Keep leading by example!`,
    body_ar: `تحية كبيرة وشكر خاص من الإدارة للزميل ${nameAr || nameEn} على الأداء المميز والمبيعات القوية! استمر يا بطل!`,
    action_tab: 'seller_wallet',
    is_pinned: true
  };
  openCompanyInboxTab();
}

async function changeInboxFilter(flt) {
  STATE.inboxFilter = flt;
  const res = await apiGet({ action: 'inbox_messages', filter: flt });
  if (res && res.ok) {
    STATE.inboxData = res;
    STATE.unreadInbox = Number(res.unread_count || 0);
    syncNotifBadge();
  }
  renderApp();
}

async function submitCompanyInboxMessage(e) {
  e.preventDefault();
  const audSel = document.getElementById('inbAudQuick').value;
  let audType = 'ALL';
  let audTarget = '';
  if (audSel === 'ALL_SELLERS') {
    audType = 'ROLE';
    audTarget = 'SALES_REP';
  } else if (audSel.startsWith('ROLE:')) {
    audType = 'ROLE';
    audTarget = audSel.slice(5);
  } else if (audSel.startsWith('BRANCH:')) {
    audType = 'BRANCH';
    audTarget = audSel.slice(7);
  } else if (audSel === 'DIRECT_USER') {
    audType = 'DIRECT';
    audTarget = document.getElementById('inbDirectUser').value;
  }
  const msgType = document.getElementById('inbMsgType').value;
  const spotUser = document.getElementById('inbSpotlightUser') ? document.getElementById('inbSpotlightUser').value : '';
  const titleTxt = document.getElementById('inbTitleEn').value.trim();
  const titleAr = document.getElementById('inbTitleAr').value.trim() || titleTxt;
  const bodyTxt = document.getElementById('inbBodyEn').value.trim();
  const bodyAr = document.getElementById('inbBodyAr').value.trim() || bodyTxt;
  const isPin = document.getElementById('inbPinChk').checked ? 1 : 0;

  const res = await apiPost({
    action: 'send_inbox_message',
    msg_type: msgType,
    priority: msgType === 'CONGRATULATIONS' ? 'CELEBRATION' : 'IMPORTANT',
    audience_type: audType,
    audience_target: audTarget,
    spotlight_username: spotUser,
    title_en: titleTxt || titleAr,
    title_ar: titleAr,
    body_en: bodyTxt || bodyAr,
    body_ar: bodyAr,
    is_pinned: isPin
  });
  if (!res || !res.ok) {
    alert((res && res.error) || 'Could not send message');
    return;
  }
  showToast(STATE.lang === 'ar' ? '🚀 تم إرسال الرسالة لصندوق الفريق فوراً!' : '🚀 Sent to Staff Inbox!');
  STATE.inboxDraft.title_en = '';
  STATE.inboxDraft.title_ar = '';
  STATE.inboxDraft.body_en = '';
  STATE.inboxDraft.body_ar = '';
  await openCompanyInboxTab();
}

async function reactToInboxMessage(messageId, emoji) {
  const res = await apiPost({ action: 'react_inbox_message', message_id: messageId, emoji });
  if (res && res.ok) {
    const fresh = await apiGet({ action: 'inbox_messages', filter: STATE.inboxFilter || 'all' });
    if (fresh && fresh.ok) {
      STATE.inboxData = fresh;
      STATE.unreadInbox = Number(fresh.unread_count || 0);
      syncNotifBadge();
    }
    renderApp();
  }
}

async function markSingleInboxRead(messageId) {
  const res = await apiPost({ action: 'mark_inbox_read', message_id: messageId });
  if (res && res.ok) {
    STATE.unreadInbox = Number(res.unread_count || 0);
    if (STATE.inboxData && Array.isArray(STATE.inboxData.messages)) {
      STATE.inboxData.messages = STATE.inboxData.messages.map((m) => (Number(m.id) === Number(messageId) ? { ...m, is_read: true } : m));
    }
    syncNotifBadge();
    renderApp();
  }
}

async function markAllInboxRead() {
  const res = await apiPost({ action: 'mark_inbox_read', mark_all: 1 });
  if (res && res.ok) {
    STATE.unreadInbox = 0;
    if (STATE.inboxData && Array.isArray(STATE.inboxData.messages)) {
      STATE.inboxData.messages = STATE.inboxData.messages.map((m) => ({ ...m, is_read: true }));
    }
    syncNotifBadge();
    showToast(STATE.lang === 'ar' ? '✓ تم تعليم كل الرسائل كمقروءة' : '✓ All messages marked read');
    renderApp();
  }
}

async function togglePinInboxMsg(messageId) {
  const res = await apiPost({ action: 'toggle_pin_inbox_message', message_id: messageId });
  if (res && res.ok) {
    showToast(res.is_pinned ? '📌 Message pinned to top' : 'Unpinned message');
    await openCompanyInboxTab();
  }
}

async function deleteInboxMsg(messageId) {
  if (!confirm('Delete this message from Company Inbox?')) return;
  const res = await apiPost({ action: 'delete_inbox_message', message_id: messageId });
  if (res && res.ok) {
    showToast('🗑️ Message deleted');
    await openCompanyInboxTab();
  }
}

function renderCompanyInboxTab(ar) {
  const data = STATE.inboxData || { can_send: hasPerm('bi.view_executive'), messages: [], allowed_reactions: ['👏', '🎉', '🔥', '❤️', '🏆', '🫡', '👍'], recipients_directory: { users: [], roles: [], branches: [] } };
  const canSend = Boolean(data.can_send);
  const canPinDel = Boolean(data.can_pin_delete);
  const msgs = data.messages || [];
  const reacts = data.allowed_reactions || ['👏', '🎉', '🔥', '❤️', '🏆', '🫡', '👍'];
  const dirUsers = (data.recipients_directory && data.recipients_directory.users) || [];
  const d = STATE.inboxDraft || {};

  const curAudQuick = d.audience_type === 'DIRECT'
    ? 'DIRECT_USER'
    : (d.audience_type === 'ROLE' && d.audience_target === 'SALES_REP'
        ? 'ALL_SELLERS'
        : (d.audience_type === 'ROLE' ? 'ROLE:' + d.audience_target : (d.audience_type === 'BRANCH' ? 'BRANCH:' + d.audience_target : 'ALL')));

  const filters = [
    ['all', ar ? '📬 كل الرسائل' : '📬 All Messages'],
    ['unread', ar ? `🔴 غير مقروء (${STATE.unreadInbox || 0})` : `🔴 Unread (${STATE.unreadInbox || 0})`],
    ['congratulations', ar ? '🎉 التهاني والجوائز' : '🎉 Congratulations'],
    ['target_alerts', ar ? '🎯 التارجت والمكافآت' : '🎯 Targets & Bonuses'],
    ['direct', ar ? '💌 رسائل خاصة لي' : '💌 Direct to Me']
  ];

  const audBadgeLabel = (m) => {
    if (m.audience_type === 'ALL') return ar ? '🌍 لكل الشركة (34)' : '🌍 Everyone (All Staff)';
    if (m.audience_type === 'DIRECT') return ar ? `💌 رسالة خاصة (@${m.audience_target})` : `💌 Private Direct (@${m.audience_target})`;
    if (m.audience_type === 'ROLE' && m.audience_target === 'SALES_REP') return ar ? '💼 كل المناديب (18)' : '💼 All 18 Sellers';
    if (m.audience_type === 'ROLE') return `🏢 ${m.audience_target}`;
    if (m.audience_type === 'BRANCH') return `🏨 ${m.audience_target}`;
    return m.audience_type;
  };

  return `
    ${canSend ? `
      <div class="card" style="margin-bottom:14px;background:linear-gradient(135deg,rgba(245,158,11,0.14),rgba(0,210,255,0.1));border-color:rgba(251,191,36,0.42);">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
          <div>
            <h3 style="font-size:16px;color:#fde68a;">👑 ${ar ? 'إرسال تهنئة، مكافأة أو إعلان رسمي من الملاك والإدارة' : 'Owner & Admin Broadcast, Congratulations & Direct Message Studio'}</h3>
            <p style="font-size:11.5px;color:var(--muted);">${ar ? 'اختر قالباً جاهزاً بلمسة واحدة أو اكتب رسالتك لكل الشركة أو للمناديب أو لموظف محدد' : 'Pick a 1-tap preset below or send an announcement / personal recognition to Everyone, All Sellers, a Branch, or 1 Person.'}</p>
          </div>
          <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <button type="button" class="btn btn-amber" onclick="applyInboxPreset('CONGRATS_SELLER')">🎉 ${ar ? 'قالب تهنئة نجم' : 'Congrats Preset'}</button>
            <button type="button" class="btn btn-primary" onclick="applyInboxPreset('ANNOUNCE_ALL')">📢 ${ar ? 'قالب إعلان عام' : 'Announcement'}</button>
            <button type="button" class="btn btn-emerald" onclick="applyInboxPreset('TARGET_BONUS')">🎯 ${ar ? 'قالب مكافأة وتارجت' : 'Bonus Alert'}</button>
            <button type="button" class="btn" onclick="applyInboxPreset('URGENT_OPS')">🚨 ${ar ? 'تنبيه عاجل' : 'Urgent Notice'}</button>
          </div>
        </div>

        <form onsubmit="submitCompanyInboxMessage(event)" class="grid grid-2" style="gap:10px;">
          <div>
            <label style="font-weight:700;color:var(--text);">🎯 ${ar ? 'لمن ترسل الرسالة؟' : 'Send To Audience'}</label>
            <select id="inbAudQuick" onchange="document.getElementById('inbDirectWrap').style.display=this.value==='DIRECT_USER'?'block':'none';">
              <option value="ALL" ${curAudQuick === 'ALL' ? 'selected' : ''}>🌍 ${ar ? 'كل الموظفين والشركاء (34 حساب)' : 'Everyone — All 34 Staff & Partners'}</option>
              <option value="ALL_SELLERS" ${curAudQuick === 'ALL_SELLERS' ? 'selected' : ''}>💼 ${ar ? 'كل مناديب المبيعات فقط (18 مندوب)' : 'All 18 Sales Representatives Only'}</option>
              <option value="DIRECT_USER" ${curAudQuick === 'DIRECT_USER' ? 'selected' : ''}>👤 ${ar ? 'رسالة خاصة لموظف واحد بالاسم' : 'Specific Person (Private Direct Message)'}</option>
              <option value="BRANCH:CYRENE" ${curAudQuick === 'BRANCH:CYRENE' ? 'selected' : ''}>🏨 Branch: Cyrene</option>
              <option value="BRANCH:DREAMS_FAYROUZ" ${curAudQuick === 'BRANCH:DREAMS_FAYROUZ' ? 'selected' : ''}>🏨 Branch: Dreams &amp; Fayrouz</option>
              <option value="BRANCH:JETTY" ${curAudQuick === 'BRANCH:JETTY' ? 'selected' : ''}>🛥️ Branch: Travco Jetty</option>
              <option value="ROLE:OPERATIONS_MANAGER" ${curAudQuick === 'ROLE:OPERATIONS_MANAGER' ? 'selected' : ''}>🚐 Role: Operations &amp; Dispatch</option>
              <option value="ROLE:DIVE_INSTRUCTOR" ${curAudQuick === 'ROLE:DIVE_INSTRUCTOR' ? 'selected' : ''}>🤿 Role: Dive Instructors &amp; Boat Captains</option>
              <option value="ROLE:CASHIER_TREASURY" ${curAudQuick === 'ROLE:CASHIER_TREASURY' ? 'selected' : ''}>💵 Role: Cashiers &amp; Safe</option>
            </select>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
            <div>
              <label style="font-weight:700;color:var(--text);">🏷️ ${ar ? 'نوع الرسالة' : 'Message Type'}</label>
              <select id="inbMsgType">
                <option value="CONGRATULATIONS" ${d.msg_type === 'CONGRATULATIONS' ? 'selected' : ''}>🎉 Congratulations / Award</option>
                <option value="ANNOUNCEMENT" ${d.msg_type === 'ANNOUNCEMENT' ? 'selected' : ''}>📢 Official Announcement</option>
                <option value="TARGET_ALERT" ${d.msg_type === 'TARGET_ALERT' ? 'selected' : ''}>🎯 Sales Target / Bonus</option>
                <option value="POLICY_UPDATE" ${d.msg_type === 'POLICY_UPDATE' ? 'selected' : ''}>📋 Price / Policy Update</option>
                <option value="DIRECT" ${d.msg_type === 'DIRECT' ? 'selected' : ''}>💌 Personal Note</option>
              </select>
            </div>
            <div>
              <label style="font-weight:700;color:#fde68a;">🌟 ${ar ? 'تكريم اسم موظف (اختياري)' : 'Spotlight Star (Optional)'}</label>
              <select id="inbSpotlightUser">
                <option value="">— ${ar ? 'بدون تحديد' : 'None'} —</option>
                ${dirUsers.map((u) => `<option value="${esc(u.username)}" ${d.spotlight_username === u.username ? 'selected' : ''}>${esc(ar ? (u.display_name_ar || u.display_name_en) : u.display_name_en)} (@${esc(u.username)})</option>`).join('')}
              </select>
            </div>
          </div>

          <div id="inbDirectWrap" style="display:${curAudQuick === 'DIRECT_USER' ? 'block' : 'none'};grid-column:1/-1;">
            <label style="font-weight:700;color:var(--cyan);">👤 ${ar ? 'اختر الموظف المستلم للرسالة الخاصة:' : 'Choose Direct Recipient:'}</label>
            <select id="inbDirectUser">
              ${dirUsers.map((u) => `<option value="${esc(u.username)}" ${d.audience_target === u.username ? 'selected' : ''}>${esc(ar ? (u.display_name_ar || u.display_name_en) : u.display_name_en)} — ${esc(u.role_code)} (@${esc(u.username)})</option>`).join('')}
            </select>
          </div>

          <div>
            <label>${ar ? 'العنوان (إنجليزي أو عربي) *' : 'Headline (English or Arabic) *'}</label>
            <input type="text" id="inbTitleEn" required value="${esc(d.title_en || '')}" placeholder="e.g. 🎉 Bravo Fady — #1 Seller Today!">
          </div>
          <div>
            <label>${ar ? 'العنوان بالعربي (اختياري — يُملأ تلقائياً)' : 'Arabic Headline (Optional — auto-fills if blank)'}</label>
            <input type="text" id="inbTitleAr" dir="rtl" value="${esc(d.title_ar || '')}" placeholder="مثال: 🎉 ألف مبروك يا فادي — نجم مبيعات اليوم!">
          </div>
          <div>
            <label>${ar ? 'نص الرسالة *' : 'Message Text *'}</label>
            <input type="text" id="inbBodyEn" required value="${esc(d.body_en || '')}" placeholder="Write a short, clear message...">
          </div>
          <div>
            <label>${ar ? 'النص بالعربي (اختياري)' : 'Arabic Message (Optional)'}</label>
            <input type="text" id="inbBodyAr" dir="rtl" value="${esc(d.body_ar || '')}" placeholder="اكتب رسالة قصيرة وواضحة للفريق...">
          </div>

          <div style="grid-column:1/-1;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
            <label style="margin:0;display:inline-flex;align-items:center;gap:7px;color:#fde68a;font-weight:800;cursor:pointer;">
              <input type="checkbox" id="inbPinChk" style="width:auto;" ${d.is_pinned ? 'checked' : ''}>
              📌 ${ar ? 'تثبيت الرسالة في أعلى شاشة الموظفين' : 'Pin at Top of Staff Screens'}
            </label>
            <button type="submit" class="btn btn-primary" style="padding:11px 22px;font-size:14px;font-weight:900;">🚀 ${ar ? 'إرسال الرسالة الآن' : 'Send to Inbox Now'}</button>
          </div>
        </form>
      </div>
    ` : ''}

    <div class="horizon-bar" style="margin-bottom:12px;">
      <div style="display:flex;gap:6px;flex-wrap:wrap;">
        ${filters.map(([fk, flbl]) => `<button type="button" class="btn ${(STATE.inboxFilter || 'all') === fk ? 'btn-primary' : ''}" onclick="changeInboxFilter('${fk}')">${esc(flbl)}</button>`).join('')}
      </div>
      <button type="button" class="btn btn-emerald" onclick="markAllInboxRead()">✓ ${ar ? 'تعليم الكل كمقروء' : 'Mark All Read'}</button>
    </div>

    ${!msgs.length ? `
      <div class="card" style="text-align:center;padding:28px;color:var(--muted);">
        📭 ${ar ? 'لا توجد رسائل في هذا القسم حالياً.' : 'No messages in this filter right now.'}
      </div>
    ` : `
      <div style="display:grid;gap:12px;">
        ${msgs.map((m) => {
          const isCeleb = m.msg_type === 'CONGRATULATIONS' || m.priority === 'CELEBRATION';
          const isUrg = m.priority === 'URGENT';
          const borderCol = isCeleb ? 'rgba(251,191,36,0.55)' : (isUrg ? 'rgba(244,63,94,0.55)' : (!m.is_read ? 'rgba(0,210,255,0.55)' : 'var(--border)'));
          const bgGrad = isCeleb
            ? 'linear-gradient(135deg,rgba(245,158,11,0.14),rgba(11,34,57,0.95))'
            : (!m.is_read ? 'linear-gradient(135deg,rgba(0,210,255,0.13),rgba(11,34,57,0.95))' : 'rgba(11,34,57,0.92)');
          const mTitle = ar ? (m.title_ar || m.title_en) : (m.title_en || m.title_ar);
          const mBody = ar ? (m.body_ar || m.body_en) : (m.body_en || m.body_ar);
          const sName = ar ? (m.sender_name_ar || m.sender_name_en) : (m.sender_name_en || m.sender_name_ar);
          const sTitle = ar ? (m.sender_title_ar || m.sender_title_en) : (m.sender_title_en || m.sender_title_ar);
          const sp = m.spotlight_user;
          return `
            <article class="card" style="border-color:${borderCol};background:${bgGrad};" onclick="if(${!m.is_read ? 'true' : 'false'})markSingleInboxRead(${Number(m.id)})">
              <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:8px;">
                <div style="display:flex;align-items:center;gap:10px;">
                  ${renderAvatarHtml({ display_name_en: m.sender_name_en, avatar_url: m.sender_avatar_url }, 42)}
                  <div>
                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                      <b style="font-size:13.5px;">${esc(sName)}</b>
                      <span class="pill" style="font-size:10.5px;">👑 ${esc(sTitle || m.sender_role_code)}</span>
                      <span class="pill" style="background:rgba(16,185,129,0.14);color:#34d399;border-color:rgba(16,185,129,0.35);font-size:10.5px;">${esc(audBadgeLabel(m))}</span>
                      ${m.is_pinned ? `<span class="award-medal" style="padding:2px 8px;font-size:10.5px;">📌 ${ar ? 'مثبت' : 'Pinned'}</span>` : ''}
                      ${!m.is_read ? `<span class="notif-badge">${ar ? 'جديد' : 'NEW'}</span>` : ''}
                    </div>
                    <div style="font-size:11px;color:var(--muted);margin-top:2px;">🕒 ${esc(String(m.created_at || '').slice(0, 16).replace('T', ' '))}</div>
                  </div>
                </div>
                ${canPinDel ? `
                  <div style="display:flex;gap:6px;align-items:center;">
                    <span class="pill" title="Staff who opened this message">👁️ ${fmt(m.read_count || 0)}</span>
                    <button type="button" class="btn" style="padding:4px 8px;font-size:11px;" onclick="event.stopPropagation();togglePinInboxMsg(${Number(m.id)})">${m.is_pinned ? 'Unpin' : '📌 Pin'}</button>
                    <button type="button" class="btn" style="padding:4px 8px;font-size:11px;border-color:var(--rose);color:#fda4af;" onclick="event.stopPropagation();deleteInboxMsg(${Number(m.id)})">🗑️</button>
                  </div>
                ` : ''}
              </div>

              ${sp ? `
                <div style="display:inline-flex;align-items:center;gap:9px;padding:7px 12px;border-radius:12px;background:rgba(245,158,11,0.16);border:1px solid rgba(251,191,36,0.45);margin-bottom:8px;">
                  ${renderAvatarHtml(sp, 34)}
                  <div>
                    <div style="font-size:11px;color:#fde68a;font-weight:800;">🌟 ${ar ? 'لوحة الشرف والتكريم' : 'Star Recognition Spotlight'}</div>
                    <div style="font-size:13px;font-weight:900;">${esc(ar ? (sp.display_name_ar || sp.display_name_en) : sp.display_name_en)}</div>
                  </div>
                </div>
              ` : ''}

              <div style="font-size:16px;font-weight:900;line-height:1.35;margin-bottom:5px;color:${isCeleb ? '#fde68a' : 'var(--text)'};">
                ${esc(m.badge_icon || '📢')} ${esc(mTitle)}
              </div>
              <p style="font-size:13.5px;line-height:1.6;color:#e2e8f0;white-space:pre-line;">${esc(mBody)}</p>

              <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-top:11px;padding-top:9px;border-top:1px solid rgba(148,163,184,0.14);">
                <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                  <span style="font-size:11.5px;color:var(--muted);font-weight:700;">${ar ? 'تفاعل بلمسة:' : 'Tap to react:'}</span>
                  ${reacts.map((em) => {
                    const cnt = Number((m.reactions_summary && m.reactions_summary[em]) || 0);
                    const active = m.my_reaction === em;
                    return `<button type="button" class="react-chip ${active ? 'active' : ''}" onclick="event.stopPropagation();reactToInboxMessage(${Number(m.id)}, '${em}')">${em}${cnt > 0 ? ` <span style="font-size:11.5px;">${cnt}</span>` : ''}</button>`;
                  }).join('')}
                </div>
                <div style="display:flex;gap:6px;">
                  ${m.action_tab ? `<button type="button" class="btn btn-primary" onclick="event.stopPropagation();markSingleInboxRead(${Number(m.id)});STATE.activeTab='${esc(m.action_tab)}';renderApp();">🚀 ${ar ? 'افتح القسم' : 'Open Now'}</button>` : ''}
                  ${!m.is_read ? `<button type="button" class="btn btn-emerald" onclick="event.stopPropagation();markSingleInboxRead(${Number(m.id)})">✓ ${ar ? 'تمت القراءة' : 'Got It'}</button>` : ''}
                </div>
              </div>
            </article>
          `;
        }).join('')}
      </div>
    `}
  `;
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

function renderOwnerSellerCommandCenter(ar) {
  const sb = STATE.sellerBi;
  if (!sb) {
    return `<div class="card" style="margin-bottom:14px;">⏳ ${ar ? 'جاري تحميل لوحة أداء المناديب والجوائز...' : 'Loading Seller Performance, Best-Seller Awards & Audit Feed...'}</div>`;
  }
  const aw = (sb.awards && sb.awards.winners) || {};
  const tCfg = sb.target_config || { is_active: true, target_daily_egp: 15000, target_weekly_egp: 90000, target_monthly_egp: 350000 };
  const rows = sb.sellers_leaderboard || [];
  const mods = sb.voucher_modifications_audit || [];
  const tot = sb.totals || {};

  const podiumSlots = [
    ['daily', aw.daily, ar ? '🥇 نجم اليوم (Daily Best)' : '🥇 Best Seller Today (Daily)'],
    ['weekly', aw.weekly, ar ? '🏆 بطل الأسبوع (Weekly Best)' : '🏆 Best Seller This Week'],
    ['monthly', aw.monthly, ar ? '👑 ملك الشهر (Monthly Best)' : '👑 Best Seller This Month'],
    ['yearly', aw.yearly, ar ? '💎 أسطورة العام (Yearly Best)' : '💎 Best Seller of the Year']
  ];

  const sHorizons = [
    ['daily', ar ? 'يومي' : 'Daily'],
    ['weekly', ar ? 'أسبوعي' : 'Weekly'],
    ['monthly', ar ? 'شهري' : 'Monthly'],
    ['yearly', ar ? 'سنوي' : 'Yearly'],
    ['custom', ar ? 'فترة مخصصة' : 'Custom Range']
  ];

  return `
    <div class="card" style="margin-bottom:14px;border-color:rgba(251,191,36,0.4);background:linear-gradient(135deg,rgba(11,34,57,0.95),rgba(17,46,74,0.88));">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:12px;">
        <div>
          <h3 style="font-size:16px;color:#fde68a;">🏆 ${ar ? 'لوحة الملاك لمتابعة أداء المناديب، جوائز الأفضل، والتارجت' : 'Owners Seller Performance, Best-Seller Awards & Target Center'}</h3>
          <div style="font-size:11.5px;color:var(--muted);margin-top:2px;">
            Window: <b>${esc(sb.date_from)} → ${esc(sb.date_to)}</b> • Trips Sold: <b>${fmt(tot.trips_sold)}</b> • Actual PAX: <b>${fmt(tot.actual_pax)}</b> • Actual Sales: <b class="pos">${fmt(tot.actual_sales_egp)} EGP</b> • Collected: <b>${fmt(tot.collected_egp)} EGP</b>
          </div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
          <div class="seg-group">
            ${sHorizons.map(([hk, hlbl]) => `<button type="button" class="seg-btn ${STATE.sellerBiHorizon === hk ? 'active' : ''}" onclick="changeSellerBiHorizon('${hk}')">${esc(hlbl)}</button>`).join('')}
          </div>
          ${STATE.sellerBiHorizon === 'custom' ? `
            <div style="display:flex;gap:6px;align-items:center;">
              <input type="date" id="sbiFrom" value="${esc(STATE.sellerBiFrom)}" style="width:142px;padding:6px;">
              <span style="color:var(--muted);">→</span>
              <input type="date" id="sbiTo" value="${esc(STATE.sellerBiTo)}" style="width:142px;padding:6px;">
              <button type="button" class="btn btn-primary" onclick="changeSellerBiHorizon('custom', document.getElementById('sbiFrom').value, document.getElementById('sbiTo').value)">Apply</button>
            </div>
          ` : ''}
        </div>
      </div>

      <!-- 4 Best Seller Award Podium Cards -->
      <div class="podium-grid" style="margin-bottom:14px;">
        ${podiumSlots.map(([_pk, win, pTitle]) => `
          <div class="podium-card">
            <div style="font-size:11px;font-weight:800;color:#fde68a;margin-bottom:6px;">${esc(pTitle)}</div>
            ${win ? `
              <div style="font-size:16px;font-weight:900;color:#fff;">${esc(win.icon)} ${esc(win.rep_code)}</div>
              <div style="font-size:12.5px;color:var(--teal);font-weight:800;margin-top:3px;">${fmt(win.sales_egp)} EGP</div>
              <div style="font-size:11px;color:var(--muted);margin-top:2px;">${fmt(win.trips_count)} Trips • ${fmt(win.pax)} PAX (${esc(win.window_start)} → ${esc(win.window_end)})</div>
            ` : `<div style="font-size:12px;color:var(--muted);">No trips in window</div>`}
          </div>
        `).join('')}
      </div>

      <!-- Owner Target Activation & Threshold Controls -->
      <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;padding:10px 12px;border-radius:11px;background:rgba(6,21,38,0.75);border:1px solid var(--border);margin-bottom:8px;">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
          <span style="font-size:12px;font-weight:800;color:var(--cyan);">🎯 ${ar ? 'تارجت المناديب داخل حساباتهم:' : 'Seller Account Target Bar:'}</span>
          <button type="button" class="btn ${tCfg.is_active ? 'btn-emerald' : 'btn-amber'}" onclick="saveOwnerTargets(${tCfg.is_active ? 'false' : 'true'})">
            ${tCfg.is_active ? '✅ ACTIVE (Shown in Seller Profiles)' : '⏸ DEACTIVATED (Hidden from Sellers)'}
          </button>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;font-size:11.5px;">
          <span>Daily: <input type="number" id="tgtDailyInp" value="${Number(tCfg.target_daily_egp || 15000)}" style="width:92px;padding:5px;"></span>
          <span>Weekly: <input type="number" id="tgtWeeklyInp" value="${Number(tCfg.target_weekly_egp || 90000)}" style="width:98px;padding:5px;"></span>
          <span>Monthly: <input type="number" id="tgtMonthlyInp" value="${Number(tCfg.target_monthly_egp || 350000)}" style="width:106px;padding:5px;"></span>
          <button type="button" class="btn btn-primary" onclick="saveOwnerTargets(null)">💾 Save Targets</button>
        </div>
      </div>

      <!-- Owner Seller Special Discounts Policy Controls (% or Fixed Amount) -->
      <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;padding:10px 12px;border-radius:11px;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.35);margin-bottom:12px;">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
          <span style="font-size:12px;font-weight:800;color:#fde68a;">🎁 ${ar ? 'صلاحية الخصم الخاص للمناديب (% أو مبلغ ثابت):' : 'Seller Special Discounts (% or Amount):'}</span>
          <button type="button" class="btn ${(sb.discount_policy ? sb.discount_policy.is_enabled : true) ? 'btn-emerald' : 'btn-amber'}" onclick="saveOwnerDiscountPolicy(${(sb.discount_policy ? sb.discount_policy.is_enabled : true) ? 'false' : 'true'})">
            ${(sb.discount_policy ? sb.discount_policy.is_enabled : true) ? '✅ ENABLED (Protected Above Vendor Cost)' : '⏸ DISABLED'}
          </button>
          <span style="font-size:11px;color:var(--muted);">Window Discounts: <b>${fmt(tot.discounts_granted_egp || 0)} EGP</b></span>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;font-size:11.5px;">
          <span>Max %: <input type="number" step="1" min="0" max="80" id="discMaxPctInp" value="${Number((sb.discount_policy && sb.discount_policy.max_discount_pct) ?? 20)}" style="width:74px;padding:5px;"></span>
          <span>Max Fixed (€): <input type="number" step="1" min="0" max="500" id="discMaxEurInp" value="${Number((sb.discount_policy && sb.discount_policy.max_discount_eur) ?? 50)}" style="width:82px;padding:5px;"></span>
          <button type="button" class="btn btn-amber" onclick="saveOwnerDiscountPolicy(null)">💾 Save Discount Policy</button>
        </div>
      </div>

      <!-- Full 18-Seller Leaderboard -->
      <div class="table-wrap" style="max-height:360px;margin-bottom:${mods.length ? '12px' : '0'};">
        <table>
          <thead>
            <tr>
              <th>#</th><th>Seller</th><th>Awards</th><th>Branch</th><th>Trips</th><th>Actual PAX</th>
              <th>Actual Total (EGP)</th><th>🎁 Discounts</th><th>Collected Paid</th><th>Open REST</th>
              <th>Actual Due Comm</th><th>Allowances Paid</th><th>Net Payable</th>
              ${tCfg.is_active ? '<th>Target %</th>' : ''}
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            ${rows.map((s) => {
              const medals = (s.awards || []).map((a) => `<span title="${esc(a.title_en)}">${esc(a.icon)}</span>`).join(' ');
              const dGrant = Number(s.discounts_granted_egp || 0);
              const cleanEn = String(s.display_name_en || s.username).replace(/\s*\(.*?\)\s*/g, '');
              const cleanAr = String(s.display_name_ar || '').replace(/\s*\(.*?\)\s*/g, '');
              return `
                <tr>
                  <td class="num"><b>#${s.rank || '—'}</b></td>
                  <td>
                    <div style="display:flex;align-items:center;gap:8px;cursor:pointer;" onclick="openEntityDossier('SALES_REP', '${esc(s.username)}')" title="${ar ? 'اضغط لفتح كشف الحساب الكامل والدفعات والرصيد النهائي' : 'Click to open full Financial Statement & Installments'}">
                      ${renderAvatarHtml(s, 28)}
                      <div><b style="text-decoration:underline;text-decoration-color:rgba(0,210,255,0.45);">${esc(ar ? (s.display_name_ar || s.display_name_en) : s.display_name_en)}</b><div style="font-size:10px;color:var(--muted);">@${esc(s.username)}</div></div>
                    </div>
                  </td>
                  <td style="font-size:15px;">${medals || '—'}</td>
                  <td><span class="pill">${esc(s.branch_scope)}</span></td>
                  <td class="num"><b>${fmt(s.trips_sold)}</b></td>
                  <td class="num">${fmt(s.actual_pax)}</td>
                  <td class="num"><b>${fmt(s.actual_total_egp)}</b></td>
                  <td class="num" style="color:${dGrant > 0 ? '#fcd34d' : 'var(--muted)'};">${dGrant > 0 ? '-' + fmt(dGrant) + ` (${s.discounted_trips || 1})` : '—'}</td>
                  <td class="num pos">${fmt(s.actual_paid_egp)}</td>
                  <td class="num ${s.open_rest_egp > 0 ? 'neg' : ''}">${fmt(s.open_rest_egp)}</td>
                  <td class="num pos"><b>${fmt(s.actual_due_comm_egp)}</b></td>
                  <td class="num neg">${fmt(s.allowances_paid_egp)}</td>
                  <td class="num" style="color:var(--cyan);"><b>${fmt(s.net_payable_comm_egp)}</b></td>
                  ${tCfg.is_active ? `<td class="num"><span class="pill">${fmt(s.target_pct, 1)}%</span></td>` : ''}
                  <td style="display:flex;gap:5px;flex-wrap:wrap;">
                    <button type="button" class="btn btn-emerald" style="padding:5px 8px;font-size:11px;" onclick="openEntityDossier('SALES_REP', '${esc(s.username)}')">📒 ${ar ? 'كشف الحساب' : 'Statement'}</button>
                    <button type="button" class="btn btn-amber" style="padding:5px 8px;font-size:11px;" onclick="openQuickCongratsToSeller('${esc(s.username)}', '${esc(cleanEn)}', '${esc(cleanAr)}')">🎉 ${ar ? 'تهنئة' : 'Congrats'}</button>
                    <button type="button" class="btn" style="padding:5px 8px;font-size:11px;" onclick="STATE.walletSeller='${esc(s.username)}';STATE.sellerWallet=null;STATE.activeTab='seller_wallet';renderApp();">🔍 Wallet</button>
                  </td>
                </tr>
              `;
            }).join('')}
          </tbody>
        </table>
      </div>

      ${mods.length ? `
        <div style="padding:10px 12px;border-radius:11px;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.35);">
          <div style="font-size:12.5px;font-weight:800;color:#fcd34d;margin-bottom:6px;">🛡️ Audit Feed: Field PAX / Infant-to-Child Upgrades &amp; Partial Cancellations (${mods.length})</div>
          <div class="table-wrap" style="max-height:185px;">
            <table>
              <thead><tr><th>Time</th><th>VO.NO</th><th>Change Type</th><th>PAX Before → After</th><th>Total Before → After</th><th>Delta EGP</th><th>Reason</th><th>By</th></tr></thead>
              <tbody>
                ${mods.slice(0, 15).map((m) => `
                  <tr>
                    <td>${esc(String(m.created_at || '').slice(0, 16))}</td>
                    <td><b>${esc(m.voucher_no)}</b></td>
                    <td><span class="pill">${esc(m.change_type)}</span></td>
                    <td>${m.old_adults}A+${m.old_children}C+${m.old_infants}I → <b>${m.new_adults}A+${m.new_children}C+${m.new_infants}I</b></td>
                    <td class="num">${fmt(m.old_total_egp)} → <b>${fmt(m.new_total_egp)}</b></td>
                    <td class="num ${Number(m.extra_charge_egp || 0) >= 0 ? 'pos' : 'neg'}"><b>${Number(m.extra_charge_egp || 0) >= 0 ? '+' : ''}${fmt(m.extra_charge_egp)} EGP</b></td>
                    <td>${esc(m.reason)}</td>
                    <td>@${esc(m.actor_username)}</td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        </div>
      ` : ''}
    </div>
  `;
}

function renderExecBi(ar) {
  const k = STATE.bi.kpis || {};
  const wh = STATE.bi.warehouse_counts || {};
  const marginPct = k.total_revenue_egp > 0 ? ((k.net_profit_egp / k.total_revenue_egp) * 100).toFixed(1) : '0.0';
  return `
    ${renderOwnerSellerCommandCenter(ar)}
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
    { key: 'monthly_pack', icon: '📑', title: ar ? 'حزمة التقارير الشهرية الختامية (14 شيت مطابق للإدارة)' : 'Monthly Management Closing Pack (14-Sheet Workbook)', desc: ar ? 'مطابق تماماً لتقارير الإدارة الشهرية (أكتوبر 2026): الإيرادات، العمولات، P&L الموحد، الخزينة، المرتبات، السوبر ماركت، الشركاء، البنوك والأرصدة المفتوحة' : 'Complete 14-tab monthly closing workbook: Daily Cash, Seller Commissions, Branch & Consolidated P&L, Cashbox, Payroll, Supermarket, Charters, Partners, Masked Banks & Open Balances' },
    { key: 'seller_commissions', icon: '💰', title: ar ? 'كشف حساب عمولات المناديب وحجوزات QR' : 'Seller Commissions & QR Attribution Ledger', desc: ar ? 'عمولات 10% داخل الشيت + 10% موقع QR + 25% صافي ربح خارجي لكل مندوب' : '10% In-Sheet, 10% Website QR & 25% Net Outside commission ledger + payroll reconciliation' },
    { key: 'res_accounting_2026', icon: '🔗', title: ar ? 'ربط حجوزات مايو–نوفمبر 2026 بالحسابات والقيود' : 'May–Nov 2026 Reservations-to-Accounting & GL Pack', desc: ar ? 'مطابقة الـ 7 شهور الفعلية + قيود يومية مزدوجة متوازنة + طاقم البحر' : '7-month audit bridge + all executed rows + balanced GL journals + Crew' },
    { key: 'pnl', icon: '📊', title: ar ? 'قائمة الأرباح والخسائر المستمرة (18 شهر)' : 'Continuous 18-Month Profit & Loss (Jun 25–Nov 26)', desc: ar ? 'ملخص الفروع + كافة بنود الإيراد والمصروف النقدي والمستحق' : 'Period summary + all Cash & Accrued P&L lines through Nov 2026' },
    { key: 'cashflow', icon: '💵', title: ar ? 'التدفقات النقدية وإذون الصرف' : 'Multi-Currency Cash Flow & Expenses', desc: ar ? 'حركة الخزينة اليومية (LE/$/€/£/VISA) + إذون الصرف' : 'Daily multi-currency cashbox + expense vouchers' },
    { key: 'best_sellers', icon: '🏆', title: ar ? 'الأعلى مبيعاً في الشهر والفترة' : 'Best Sellers of the Month / Period', desc: ar ? 'ترتيب مناديب المبيعات والشركات والجنسيات وأعلى 100 فندق' : 'Hotel Desk Sellers vs B2B Agencies, Nationalities & Top 100 Hotels' },
    { key: 'profitable_trips', icon: '🤿', title: ar ? 'أكثر الرحلات والكورسات ربحية' : 'Most Profitable Trips, Tours & Courses', desc: ar ? 'الإيراد الفعلي مقابل التكلفة المباشرة وصافي هامش الربح %' : 'Revenue vs Direct COGS, Net Margin EGP & Margin % per activity' },
    { key: 'profitable_boats', icon: '🛥️', title: ar ? 'ربحية المراكب وعمرة الازق والأصول' : 'Most Profitable Boats, Drydock & Assets', desc: ar ? 'أوشينو، عمر 4، نزار، سحس، الاسبيد + رحلات المراكب حتى نوفمبر 2026' : 'Fleet ROI + daily boat trips through Nov 2026 + Drydock + 119 Gear tags' },
    { key: 'full_data_pack', icon: '🗂️', title: ar ? 'سجل الحجوزات الكامل والعملاء والمرتبات' : 'Full Row-Level Reservations, AR/AP & Payroll', desc: ar ? 'حجوزات مربوطة بالحسابات + عملاء وموردين + سجل مرتبات' : 'GL-linked reservations + AR/AP accounts + payroll records' },
    { key: 'team_profiles', icon: '👥', title: ar ? 'دليل ملفات وصور وأرقام وإيميلات فريق العمل' : 'Team User Profiles, Phones & Emails Directory', desc: ar ? 'بيانات وصور وأرقام واتساب وإيميلات وصلاحيات الـ 34 مستخدم' : 'Complete 34-user profile sheet with phones, emails, QR slugs & RBAC scopes' }
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
  const sb = STATE.sellerBi || {};
  const cps = ws.counterparties || [];
  const exps = ws.recent_expenses || [];
  const pay = ws.payroll_recent || [];
  const etl = ws.etl_catalog || [];
  const resAudit = ws.res_acc_audit || [];
  const glJ = ws.gl_journals || [];
  const pipeline = sb.live_confirmation_pipeline || (ws.vouchers || []).slice(0, 25);
  const allowances = sb.commission_allowances || [];
  const modLogs = sb.voucher_modifications_audit || ws.voucher_audit_recent || [];
  return `
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
      <a class="btn btn-primary" href="${exportUrl('res_accounting_2026', 'xlsx')}">🔗 Export May–Nov 2026 Reservations & GL Bridge (.xlsx)</a>
      <a class="btn btn-emerald" href="${exportUrl('pnl', 'xlsx')}">⬇ Export 18M P&L (.xlsx)</a>
      <a class="btn btn-emerald" href="${exportUrl('cashflow', 'xlsx')}">⬇ Export Cash Flow & Expenses (.xlsx)</a>
      <a class="btn" href="${exportUrl('full_data_pack', 'xlsx')}">⬇ Export Master AR/AP & Payroll (.xlsx)</a>
    </div>

    <!-- Live Confirmed Reservations Financial & REST Collection Board -->
    <div class="card" style="margin-bottom:14px;border-color:rgba(16,185,129,0.4);">
      <h3 style="font-size:15px;margin-bottom:6px;color:#34d399;">📋 ${ar ? 'لوحة متابعة الحجوزات المؤكدة والمحصل الفعلي والباقي (REST) والتعديلات' : 'Confirmed Reservations Financial Board — Actual Total, Paid Deposit & Remaining REST'}</h3>
      <p style="font-size:11.5px;color:var(--muted);margin-bottom:10px;">
        Shows reservations confirmed by Sellers &amp; Reservations Manager, actual guest counts (Adults / Kids / Infants), actual collected amounts, remaining pickup/jetty REST, and 1-click flexible settlement.
      </p>
      <div class="table-wrap" style="max-height:270px;">
        <table>
          <thead>
            <tr>
              <th>VO.NO</th><th>Date</th><th>Seller</th><th>Guest / Hotel</th><th>Trip</th>
              <th>Actual PAX</th><th>Actual Total (EGP)</th><th>Actual Paid</th><th>Remaining REST</th>
              <th>Workflow / Status</th><th>Settle / Adjust</th>
            </tr>
          </thead>
          <tbody>
            ${pipeline.map((v) => {
              const totEgp = Number(v.actual_total_egp || v.gross_amount_egp || (Number(v.paid_egp_equiv || 0) + Number(v.rest_egp_equiv || 0)));
              const paidEgp = Number(v.paid_egp_equiv || 0);
              const restEgp = Number(v.rest_egp_equiv || 0);
              const adl = Number(v.adults ?? v.total_pax ?? 1);
              const ch = Number(v.children ?? 0);
              const inf = Number(v.infants ?? 0);
              const wf = String(v.workflow_status || v.boarding_status || 'CONFIRMED');
              return `
                <tr>
                  <td><b>${esc(v.voucher_no)}</b></td>
                  <td>${esc(v.trip_date)}</td>
                  <td><b>${esc(v.sales_rep)}</b></td>
                  <td>${esc(v.guest_name || v.hotel_name || '—')}</td>
                  <td>${esc(v.trip_name)}${Number(v.discount_egp || 0) > 0 ? `<div style="font-size:10px;color:#fcd34d;">🎁 Special Discount: -${fmt(v.discount_egp)} EGP (${v.discount_type === 'PERCENT' ? '-' + esc(v.discount_value) + '%' : '-' + esc(v.discount_value) + ' ' + esc(v.discount_currency || 'EUR')})</div>` : ''}</td>
                  <td class="num">${adl}A + ${ch}C${inf > 0 ? ' + ' + inf + 'I' : ''}</td>
                  <td class="num">${Number(v.discount_egp || 0) > 0 ? `<s style="opacity:0.6;font-size:10.5px;">${fmt(Number(v.list_total_egp || (totEgp + Number(v.discount_egp))))}</s> ` : ''}<b>${fmt(totEgp)}</b></td>
                  <td class="num pos">${fmt(paidEgp)}</td>
                  <td class="num ${restEgp > 0.5 ? 'neg' : 'pos'}"><b>${restEgp > 0.5 ? fmt(restEgp) + ' EGP' : 'PAID 100%'}</b></td>
                  <td><span class="pill">${esc(wf)}</span></td>
                  <td>
                    <button type="button" class="btn btn-amber" onclick='openAdjustVoucherModalById(${Number(v.id)})'>✏️ Adjust / Collect REST</button>
                  </td>
                </tr>
              `;
            }).join('')}
          </tbody>
        </table>
      </div>
    </div>

    ${(allowances.length || modLogs.length) ? `
      <div class="grid grid-2" style="margin-bottom:14px;">
        <div class="card">
          <h3 style="font-size:14px;margin-bottom:8px;color:var(--cyan);">💸 ${ar ? 'طلبات سلف ودفعات عمولات المناديب خلال الشهر' : 'Seller Mid-Month Commission Allowances / Installments'} (${allowances.length})</h3>
          <div class="table-wrap" style="max-height:240px;">
            <table>
              <thead><tr><th>Date</th><th>Seller</th><th>Amount (EGP)</th><th>Status</th><th>Notes</th><th>Action</th></tr></thead>
              <tbody>
                ${allowances.length ? allowances.map((a) => `
                  <tr>
                    <td>${esc(a.request_date)}</td>
                    <td><b>@${esc(a.seller_username)}</b> (${esc(a.rep_code)})</td>
                    <td class="num"><b>${fmt(a.amount_egp)} EGP</b></td>
                    <td><span class="pill">${esc(a.status)}</span></td>
                    <td>${esc(a.notes || '—')}</td>
                    <td>
                      ${a.status === 'REQUESTED' ? `
                        <button type="button" class="btn btn-emerald" onclick="decideAllowance(${Number(a.id)}, 'PAID')">✅ Pay</button>
                        <button type="button" class="btn" onclick="decideAllowance(${Number(a.id)}, 'REJECTED')">✕</button>
                      ` : `<span style="font-size:11px;color:var(--muted);">${esc(a.approved_by || 'Settled')}</span>`}
                    </td>
                  </tr>
                `).join('') : `<tr><td colspan="6" style="color:var(--muted);text-align:center;">No mid-month allowance requests yet</td></tr>`}
              </tbody>
            </table>
          </div>
        </div>

        <div class="card">
          <h3 style="font-size:14px;margin-bottom:8px;color:#fcd34d;">🛡️ ${ar ? 'سجل المراجعة لتعديلات الأفراد وفروق الأعمار والإلغاء الجزئي' : 'Audit Log: PAX / Infant-to-Child Upgrades & Partial Cancellations'} (${modLogs.length})</h3>
          <div class="table-wrap" style="max-height:240px;">
            <table>
              <thead><tr><th>VO.NO</th><th>Change</th><th>PAX</th><th>Delta EGP</th><th>Reason</th><th>By</th></tr></thead>
              <tbody>
                ${modLogs.length ? modLogs.map((m) => `
                  <tr>
                    <td><b>${esc(m.voucher_no)}</b></td>
                    <td><span class="pill">${esc(m.change_type)}</span></td>
                    <td>${m.old_adults}A+${m.old_children}C+${m.old_infants}I → <b>${m.new_adults}A+${m.new_children}C+${m.new_infants}I</b></td>
                    <td class="num ${Number(m.extra_charge_egp || 0) >= 0 ? 'pos' : 'neg'}"><b>${Number(m.extra_charge_egp || 0) >= 0 ? '+' : ''}${fmt(m.extra_charge_egp)}</b></td>
                    <td>${esc(m.reason)}</td>
                    <td>@${esc(m.actor_username)}</td>
                  </tr>
                `).join('') : `<tr><td colspan="6" style="color:var(--muted);text-align:center;">No voucher modifications recorded yet</td></tr>`}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    ` : ''}

    ${resAudit.length ? `
    <div class="card" style="margin-bottom:14px;">
      <h3 style="font-size:15px;margin-bottom:6px;">🔗 ${ar ? 'مطابقة وربط حجوزات 2026 الفعلية (مايو – نوفمبر 2026) بالحسابات والأستاذ العام' : 'Actual 2026 Reservations-to-Accounting & Double-Entry GL Reconciliation (May – Nov 2026)'}</h3>
      <p style="font-size:11px;color:var(--muted);margin-bottom:10px;">
        Every executed reservation across Cyrene, Dreams & El Fayrouz, Out Side B2B Agencies, and Discovery is linked 1:1 to Cashbox, VISA POS, B2B AR, Supplier AP, Fleet Boats, and balanced Double-Entry GL Journals (DR == CR = 63,207,105.80 EGP).
      </p>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Month</th><th>Days</th><th>Executed</th><th>&lt;N&gt; Cancelled</th><th>Executed PAX</th>
              <th>Cyrene</th><th>Dreams/Fayrouz</th><th>Out Side B2B</th><th>Discovery</th>
              <th>Desk Cash</th><th>Jetty REST</th><th>AR Credit</th><th>Total Revenue</th><th>Direct COGS</th><th>Gross Margin</th><th>GL Journals</th>
            </tr>
          </thead>
          <tbody>
            ${resAudit.map((m) => `
              <tr>
                <td><b>${esc(m.period_month)}</b></td>
                <td class="num">${m.days_covered}</td>
                <td class="num pos">${fmt(m.executed_vouchers)}</td>
                <td class="num neg">${fmt(m.cancelled_vouchers)} (${fmt(m.cancelled_pax)}p)</td>
                <td class="num"><b>${fmt(m.executed_pax)}</b></td>
                <td class="num">${fmt(m.cyrene_pax)}</td>
                <td class="num">${fmt(m.dreams_fayrouz_pax)}</td>
                <td class="num">${fmt(m.outside_b2b_pax)}</td>
                <td class="num">${fmt(m.discovery_pax)}</td>
                <td class="num">${fmt(m.cash_paid_at_desk_egp)}</td>
                <td class="num">${fmt(m.jetty_rest_cash_egp)}</td>
                <td class="num" style="color:#fcd34d;">${fmt(m.ar_b2b_credit_egp)}</td>
                <td class="num"><b>${fmt(m.total_accrued_rev_egp)}</b></td>
                <td class="num">${fmt(m.direct_trip_cogs_egp)}</td>
                <td class="num pos"><b>${fmt(m.gross_operating_margin_egp)}</b></td>
                <td><span class="pill">${m.gl_journal_entries_count} JEs ✓</span></td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    </div>` : ''}

    ${glJ.length ? `
    <div class="card" style="margin-bottom:14px;">
      <h3 style="font-size:14px;margin-bottom:8px;">⚖️ ${ar ? 'دفتر القيود اليومية المزدوجة المتوازنة (Double-Entry General Ledger)' : 'Balanced Double-Entry General Ledger Journals (DR == CR Verified)'}</h3>
      <div class="table-wrap" style="max-height:240px;">
        <table>
          <thead><tr><th>Journal #</th><th>Date</th><th>Description</th><th>Lines</th><th>Total Debit (EGP)</th><th>Total Credit (EGP)</th></tr></thead>
          <tbody>
            ${glJ.map((j) => `
              <tr>
                <td><b>${esc(j.entry_no)}</b></td><td>${esc(j.entry_date)}</td><td>${esc(j.description_en)}</td>
                <td class="num">${j.lines_count}</td>
                <td class="num pos">${fmt(j.total_debit_egp, 2)}</td>
                <td class="num pos">${fmt(j.total_credit_egp, 2)}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    </div>` : ''}

    <div class="grid grid-2" style="margin-bottom:14px;">
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:8px;">
          <h3 style="font-size:14px;">🤝 AR Clients &amp; AP Suppliers Sub-Ledgers (63 Accounts)</h3>
          <button type="button" class="btn btn-emerald" style="padding:4px 9px;font-size:11px;" onclick="STATE.activeTab='accounts_hub';renderApp();">📒 ${ar ? 'فتح دليل الأرصدة والدفعات' : 'All Accounts &amp; Installments'}</button>
        </div>
        <div class="table-wrap" style="max-height:310px;">
          <table>
            <thead><tr><th>Party</th><th>Type</th><th>Total Debit</th><th>Total Credit</th><th>Balance (EGP)</th></tr></thead>
            <tbody>
              ${cps.map((c) => {
                const cCode = String(c.code || '');
                const mappedType = cCode.startsWith('AP_') ? 'SUPPLIER' : (cCode.startsWith('PA_') ? 'PARTNER' : 'CLIENT_AR');
                return `
                <tr style="cursor:pointer;" onclick="openEntityDossier('${mappedType}', '${esc(cCode)}')" title="Click to open Statement &amp; Installments">
                  <td><b style="color:#7dd3fc;text-decoration:underline;">${esc(c.name_ar)}</b></td>
                  <td><span class="pill">${esc(c.party_type)}</span></td>
                  <td class="num">${fmt(c.total_debit_egp)}</td>
                  <td class="num">${fmt(c.total_credit_egp)}</td>
                  <td class="num ${c.current_balance_egp >= 0 ? 'pos' : 'neg'}">${fmt(c.current_balance_egp)}</td>
                </tr>
              `;
              }).join('')}
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:8px;">
          <h3 style="font-size:14px;">👥 Monthly Staff Payroll &amp; Commissions (SALERY)</h3>
          <button type="button" class="btn btn-primary" style="padding:4px 9px;font-size:11px;" onclick="STATE.accountsFilterType='STAFF';STATE.accountsDir=null;STATE.activeTab='accounts_hub';renderApp();">👷 ${ar ? 'كشوف حسابات الموظفين' : 'Staff Statements'}</button>
        </div>
        <div class="table-wrap" style="max-height:310px;">
          <table>
            <thead>
              <tr>
                <th>Month</th><th>Employee</th><th>Role</th><th>Days</th>
                <th>Base Salary</th><th>Commission</th><th>Advances / Deductions</th><th>Net Pay (EGP)</th>
              </tr>
            </thead>
            <tbody>
              ${pay.map((p) => `
                <tr style="${p.id ? 'cursor:pointer;' : ''}" ${p.id ? `onclick="openPayrollRowDossier(${Number(p.id)}, '${esc(p.emp_name_ar || '')}')"` : ''}>
                  <td>${esc(p.period_month)}</td>
                  <td><b style="${p.id ? 'color:#7dd3fc;text-decoration:underline;' : ''}">${esc(p.emp_name_ar)}</b></td>
                  <td>${esc(p.job_title_ar)}</td>
                  <td class="num">${fmt(p.worked_days)}</td>
                  <td class="num">${fmt(p.base_salary_egp)}</td>
                  <td class="num" style="color:var(--cyan);">${fmt(p.commission_egp)}</td>
                  <td class="num neg">${fmt(p.advances_deductions_egp)}</td>
                  <td class="num pos"><b>${fmt(p.net_pay_egp)}</b></td>
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
  const sellerOnlyView = isSalesRepUser();
  const canAdjust = hasPerm('ops.view_manifests', 'ops.assign_boat_driver', 'jetty.scan_boarding_collect_rest', 'safe.record_tx', 'gl.view_all_ledgers', 'bi.view_executive');
  const canOpsConfirm = hasPerm('ops.view_manifests', 'ops.assign_boat_driver', 'bi.view_executive');

  if (sellerOnlyView) {
    const waitingConfirm = list.filter((v) => String(v.workflow_status || '') === 'AWAITING_SELLER_CONFIRM').length;
    return `
      <div class="grid grid-3" style="margin-bottom:12px;">
        <div class="card" style="padding:12px;"><div class="kpi-label">${ar ? 'إجمالي حجوزاتي' : 'My Bookings'}</div><div class="kpi-val">${fmt(myStats ? myStats.total_vouchers : list.length)}</div></div>
        <div class="card" style="padding:12px;"><div class="kpi-label">${ar ? 'بانتظار تأكيدي' : 'Need My Confirm'}</div><div class="kpi-val" style="color:${waitingConfirm > 0 ? '#fcd34d' : 'var(--teal)'};">${fmt(waitingConfirm)}</div></div>
        <div class="card" style="padding:12px;"><div class="kpi-label">${ar ? 'إجمالي مبيعاتي المنفذة' : 'Completed Sales'}</div><div class="kpi-val pos">${fmt(myStats ? myStats.est_rev_egp : 0)} EGP</div></div>
      </div>

      ${!list.length ? `<div class="card" style="text-align:center;padding:24px;color:var(--muted);">🎫 ${ar ? 'لا توجد حجوزات مسجلة بعد — ابدأ الحجز من شاشة بيع رحلة!' : 'No bookings yet — tap Pitch to book your first trip!'}</div>` : `
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(290px,1fr));gap:10px;">
          ${list.map((v) => {
            const wf = String(v.workflow_status || v.boarding_status || 'CONFIRMED');
            const adl = Number(v.adults ?? v.total_pax ?? 1);
            const ch = Number(v.children ?? 0);
            const inf = Number(v.infants ?? 0);
            const totEgp = Number(v.actual_total_egp || v.gross_amount_egp || (Number(v.paid_egp_equiv || 0) + Number(v.rest_egp_equiv || 0)));
            const paidEgp = Number(v.paid_egp_equiv || 0);
            const restEgp = Number(v.rest_egp_equiv || 0);
            const needsMe = wf === 'AWAITING_SELLER_CONFIRM';
            const isDone = (v.boarding_status === 'BOARDED' || v.boarding_status === 'EXECUTED') && restEgp <= 0.5;
            const statusHuman = needsMe
              ? (ar ? '🔔 حجز جديد من الرابط — اضغط تأكيد!' : '🔔 New Link Booking — Tap Confirm!')
              : (isDone ? (ar ? '✅ تمت الرحلة ومحصّلة بالكامل' : '✅ Trip Done & 100% Paid') : (ar ? '⏳ مؤكد وبانتظار التنفيذ/الباقي' : '⏳ Confirmed (Waiting Trip/Rest)'));
            return `
              <div class="trip-mobile-card" style="border-color:${needsMe ? '#f59e0b' : (isDone ? 'rgba(16,185,129,0.5)' : 'var(--border)')};background:${needsMe ? 'rgba(245,158,11,0.12)' : 'rgba(11,34,57,0.94)'};">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:6px;">
                  <span style="font-size:13px;font-weight:900;color:var(--cyan);">🎫 ${esc(v.voucher_no)}</span>
                  <span class="pill" style="${needsMe ? 'border-color:#f59e0b;color:#fde68a;' : (isDone ? 'border-color:#10b981;color:#34d399;' : '')}">${esc(statusHuman)}</span>
                </div>
                <div style="font-size:15px;font-weight:900;">${esc(v.trip_name)}</div>
                <div style="font-size:12.5px;color:#e2e8f0;">
                  👤 <b>${esc(v.guest_name || 'Guest')}</b> • 🏨 ${esc(v.hotel_name)} ${v.room_no ? '#' + esc(v.room_no) : ''}<br>
                  📅 ${esc(v.trip_date)} • 👨‍👩‍👧 ${adl} ${ar ? 'كبار' : 'Adults'}${ch > 0 ? ` + ${ch} ${ar ? 'أطفال' : 'Kids'}` : ''}${inf > 0 ? ` + ${inf} Free` : ''}
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding-top:8px;border-top:1px solid rgba(148,163,184,0.14);font-size:12.5px;">
                  <span>💵 ${ar ? 'مدفوع:' : 'Paid:'} <b class="pos">${fmt(paidEgp)}</b> / <b>${fmt(totEgp)} EGP</b></span>
                  <span class="${restEgp > 0.5 ? 'neg' : 'pos'}" style="font-weight:900;">${restEgp > 0.5 ? (ar ? `الباقي: ${fmt(restEgp)} ج` : `Rest: ${fmt(restEgp)} EGP`) : '✅ PAID'}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;gap:6px;flex-wrap:wrap;font-size:11.5px;color:#93c5fd;">
                  <span>${v.guest_account_username ? `🔐 VIP: <b>@${esc(v.guest_account_username)}</b>` : `⏳ ${ar ? 'يُفعّل حساب الضيف عند التأكيد' : 'VIP account activates on confirm'}`}</span>
                  <button type="button" class="btn" style="padding:4px 9px;font-size:11px;border-color:rgba(56,189,248,0.45);color:#7dd3fc;" onclick="promptAndSendGuestLoginEmail('${esc(v.voucher_no)}', '${esc(v.guest_account_username || '')}', '${esc(v.guest_email || '')}')">
                    📧 ${v.guest_email ? (ar ? 'إعادة إرسال الدخول' : 'Resend Login') : (ar ? 'إضافة إيميل وإرسال حساب' : 'Add Email & Send Login')}
                  </button>
                </div>
                ${needsMe ? `
                  <button type="button" class="btn btn-emerald" style="width:100%;justify-content:center;padding:12px;font-size:14px;font-weight:900;" onclick="sellerConfirmBooking(${Number(v.id)})">
                    ✅ ${ar ? 'تأكيد الحجز وتفعيل حساب الضيف الآن' : 'Confirm Booking & Activate Guest Account'}
                  </button>
                ` : ''}
              </div>
            `;
          }).join('')}
        </div>
      `}
    `;
  }

  return `
    ${myStats ? `
      <div class="grid grid-3" style="margin-bottom:14px;">
        <div class="card"><div class="kpi-label">My Personal Vouchers</div><div class="kpi-val">${fmt(myStats.total_vouchers)}</div></div>
        <div class="card"><div class="kpi-label">My Total Guest PAX</div><div class="kpi-val">${fmt(myStats.total_pax)} PAX</div></div>
        <div class="card"><div class="kpi-label">My Actual Sales Volume</div><div class="kpi-val pos">${fmt(myStats.est_rev_egp)} EGP</div></div>
      </div>
    ` : ''}
    <div class="card">
      <h3 style="font-size:14px;margin-bottom:10px;">🎫 ${ar ? 'كشف الحجوزات والتأكيدات والتشغيل وتحصيل الباقي (REST)' : 'Live Reservations, Multi-Stage Confirmations, Dispatch & Flexible REST Settlement'}</h3>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>VO.NO</th><th>Date</th><th>Guest / Hotel &amp; Room</th><th>PAX (A/C/I)</th><th>Trip / Activity</th>
              <th>Seller</th><th>Paid / Total</th><th>REST Due</th><th>Boat</th><th>Workflow &amp; Status</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            ${list.map((v) => {
              const wf = String(v.workflow_status || v.boarding_status || 'CONFIRMED');
              const adl = Number(v.adults ?? v.total_pax ?? 1);
              const ch = Number(v.children ?? 0);
              const inf = Number(v.infants ?? 0);
              const totEgp = Number(v.actual_total_egp || v.gross_amount_egp || (Number(v.paid_egp_equiv || 0) + Number(v.rest_egp_equiv || 0)));
              const paidEgp = Number(v.paid_egp_equiv || 0);
              const restEgp = Number(v.rest_egp_equiv || 0);
              return `
                <tr>
                  <td><b>${esc(v.voucher_no)}</b></td>
                  <td>${esc(v.trip_date)}</td>
                  <td>
                    <b>${esc(v.guest_name || 'Guest')}</b>
                    <div style="font-size:10.5px;color:var(--muted);">${esc(v.hotel_name)} ${v.room_no ? '#' + esc(v.room_no) : ''} (${esc(v.nationality || '—')})</div>
                    ${v.guest_account_username ? `<div style="font-size:10px;color:#34d399;">🔐 @${esc(v.guest_account_username)}${v.guest_email ? ' · ✉️ ' + esc(v.guest_email) : ''}</div>` : ''}
                  </td>
                  <td class="num">${adl}A${ch > 0 ? '+' + ch + 'C' : ''}${inf > 0 ? '+' + inf + 'I' : ''} (${fmt(v.total_pax)})</td>
                  <td>${esc(v.trip_name)} ${Number(v.discount_egp || 0) > 0 ? `<div style="font-size:10px;color:#fcd34d;">🎁 -${fmt(v.discount_egp)} EGP (${v.discount_type === 'PERCENT' ? '-' + esc(v.discount_value) + '%' : '-' + esc(v.discount_value) + ' ' + esc(v.discount_currency || 'EUR')})</div>` : ''} ${v.modification_note ? `<div style="font-size:10px;color:#fcd34d;">✏️ ${esc(String(v.modification_note).slice(0, 48))}</div>` : ''}</td>
                  <td>${esc(v.sales_rep)}</td>
                  <td class="num">${fmt(paidEgp)} / <b>${fmt(totEgp)}</b></td>
                  <td class="num ${restEgp > 0.5 ? 'neg' : 'pos'}"><b>${restEgp > 0.5 ? fmt(restEgp) + ' EGP' : 'PAID'}</b></td>
                  <td>${esc(v.vessel_code || '—')}</td>
                  <td>
                    <span class="pill" style="${wf === 'AWAITING_SELLER_CONFIRM' ? 'border-color:#f59e0b;color:#fcd34d;' : (wf === 'BOARDED' ? 'border-color:#10b981;color:#34d399;' : '')}">${esc(wf)}</span>
                  </td>
                  <td style="display:flex;gap:5px;flex-wrap:wrap;">
                    ${wf === 'AWAITING_SELLER_CONFIRM' ? `<button type="button" class="btn btn-emerald" onclick="sellerConfirmBooking(${Number(v.id)})">✅ Seller Confirm</button>` : ''}
                    ${canOpsConfirm && (wf === 'SELLER_CONFIRMED_PENDING_OPS' || wf === 'AWAITING_SELLER_CONFIRM') ? `<button type="button" class="btn btn-primary" onclick="opsConfirmReservation(${Number(v.id)})">📋 Ops Confirm</button>` : ''}
                    ${hasPerm('ops.assign_boat_driver') ? `<button type="button" class="btn" onclick="assignBoat(${Number(v.id)})">🛥️ Assign Boat</button>` : ''}
                    ${hasPerm('jetty.scan_boarding_collect_rest') && v.boarding_status !== 'BOARDED' ? `<button type="button" class="btn btn-emerald" onclick="boardGuest(${Number(v.id)})">✅ Collect REST &amp; Board</button>` : ''}
                    ${canAdjust ? `<button type="button" class="btn btn-amber" onclick="openAdjustVoucherModalById(${Number(v.id)})">✏️ Adjust PAX / REST</button>` : ''}
                    ${hasPerm('sales.create_voucher', 'crm.manage_leads_ads', 'bi.view_executive') ? `<button type="button" class="btn" style="border-color:rgba(56,189,248,0.45);color:#7dd3fc;" onclick="promptAndSendGuestLoginEmail('${esc(v.voucher_no)}', '${esc(v.guest_account_username || '')}', '${esc(v.guest_email || '')}')">📧 Guest Login</button>` : ''}
                  </td>
                </tr>
              `;
            }).join('')}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

async function promptAndSendGuestLoginEmail(voucherNo, guestUsername, currentEmail = '') {
  const ar = STATE.lang === 'ar';
  const entered = prompt(
    ar
      ? 'أدخل البريد الإلكتروني للضيف لإرسال بيانات دخول تطبيق VIP للرحلات القادمة:'
      : 'Enter Guest Email to send their Verified Club App Login Details:',
    currentEmail || ''
  );
  if (entered === null) return;
  const email = entered.trim();
  if (!email) return;
  const res = await apiPost({
    action: 'send_guest_login_email',
    voucher_no: voucherNo,
    guest_username: guestUsername,
    guest_email: email
  });
  if (!res || !res.ok) {
    alert((res && res.error) || 'Could not send login email');
    return;
  }
  showToast(`📧 Login credentials sent to ${res.guest_email} (@${res.guest_username})!`);
  await loadUserData();
}

async function sellerConfirmBooking(id) {
  const res = await apiPost({ action: 'seller_confirm_booking', id });
  if (res && res.ok) {
    const ga = res.guest_account || {};
    const extra = ga.email_sent
      ? ` & Login Emailed to ${ga.guest_email}!`
      : (ga.guest_username ? ` (VIP Account @${ga.guest_username} Active!)` : '!');
    showToast(`✅ Confirmed ${res.voucher_no}${extra}`);
    document.getElementById('notifModal').style.display = 'none';
    await loadUserData();
  } else {
    alert((res && res.error) || 'Could not confirm booking');
  }
}

async function sellerConfirmByVoucher(voucherNo) {
  const res = await apiPost({ action: 'seller_confirm_booking', voucher_no: voucherNo });
  if (res && res.ok) {
    const ga = res.guest_account || {};
    const extra = ga.email_sent
      ? ` & Login Emailed to ${ga.guest_email}!`
      : (ga.guest_username ? ` (VIP Account @${ga.guest_username} Active!)` : '!');
    showToast(`✅ Confirmed ${res.voucher_no}${extra}`);
    document.getElementById('notifModal').style.display = 'none';
    await loadUserData();
  } else {
    alert((res && res.error) || 'Could not confirm booking');
  }
}

async function opsConfirmReservation(id) {
  const vessel = prompt('Optional Vessel / Bus Assignment (e.g. OCEANO, OMAR_4, BUS_SAFARI) or leave blank:', 'OCEANO') || '';
  const res = await apiPost({ action: 'ops_confirm_reservation', id, vessel_code: vessel.toUpperCase(), pickup_time: '07:55–08:10' });
  if (res && res.ok) {
    showToast(`📋 Reservation ${res.voucher_no} Confirmed! Sent to Accounting & Jetty.`);
    await loadUserData();
  } else {
    alert((res && res.error) || 'Could not confirm reservation');
  }
}

let CURRENT_ADJ_VOUCHER = null;
function openAdjustVoucherModalById(id) {
  const wsList = (STATE.ws && STATE.ws.vouchers) || [];
  const pipeList = (STATE.sellerBi && STATE.sellerBi.live_confirmation_pipeline) || [];
  const v = [...wsList, ...pipeList].find((x) => Number(x.id) === Number(id));
  if (!v) return;
  CURRENT_ADJ_VOUCHER = v;
  document.getElementById('adjVid').value = String(v.id);
  document.getElementById('adjFullCancel').value = '0';
  document.getElementById('adjTitle').textContent = `✏️ Adjust / Settle ${v.voucher_no} — ${v.trip_name}`;
  const adl = Number(v.adults ?? v.total_pax ?? 1);
  const ch = Number(v.children ?? 0);
  const inf = Number(v.infants ?? 0);
  document.getElementById('adjAdl').value = String(adl);
  document.getElementById('adjCh').value = String(ch);
  document.getElementById('adjInf').value = String(inf);

  const fxEur = 57;
  const curTotEgp = Number(v.actual_total_egp || v.gross_amount_egp || (Number(v.paid_egp_equiv || 0) + Number(v.rest_egp_equiv || 0)));
  const effUnits = Math.max(0.5, adl + ch * 0.5);
  const estAdultEur = Math.max(5, Math.round(((curTotEgp / fxEur) / effUnits) * 2) / 2);
  document.getElementById('adjAdultEur').value = String(estAdultEur);
  document.getElementById('adjChildEur').value = String(Math.round(estAdultEur * 0.5 * 2) / 2);
  document.getElementById('adjPaidEgp').value = String(Math.round(Number(v.paid_egp_equiv || 0)));
  document.getElementById('adjCollectRestChk').checked = true;
  document.getElementById('adjBoardedChk').checked = true;
  document.getElementById('adjReason').value = '';
  recalcAdjustPreview();
  document.getElementById('adjustVoucherModal').style.display = 'flex';
}

function applyAdjPreset(preset) {
  if (preset === 'INFANT_TO_CHILD') {
    const curInf = Math.max(0, Number(document.getElementById('adjInf').value || 0));
    const curCh = Math.max(0, Number(document.getElementById('adjCh').value || 0));
    document.getElementById('adjInf').value = String(Math.max(0, curInf - 1));
    document.getElementById('adjCh').value = String(curCh + 1);
    document.getElementById('adjFullCancel').value = '0';
    document.getElementById('adjReason').value = 'Jetty age check: infant found ≥3 years -> upgraded to child fare & extra collected';
  } else if (preset === 'MINUS_1_ADULT') {
    const curAdl = Math.max(0, Number(document.getElementById('adjAdl').value || 1));
    document.getElementById('adjAdl').value = String(Math.max(0, curAdl - 1));
    document.getElementById('adjFullCancel').value = '0';
    document.getElementById('adjReason').value = '1 guest cancelled trip / did not come — adjusted to actual guests & actual amount';
  } else if (preset === 'FULL_CANCEL') {
    document.getElementById('adjAdl').value = '0';
    document.getElementById('adjCh').value = '0';
    document.getElementById('adjInf').value = '0';
    document.getElementById('adjFullCancel').value = '1';
    document.getElementById('adjCollectRestChk').checked = false;
    document.getElementById('adjBoardedChk').checked = false;
    document.getElementById('adjReason').value = 'Full group cancellation (<N>)';
  }
  recalcAdjustPreview();
}

function recalcAdjustPreview() {
  const adl = Math.max(0, Number(document.getElementById('adjAdl').value || 0));
  const ch = Math.max(0, Number(document.getElementById('adjCh').value || 0));
  const inf = Math.max(0, Number(document.getElementById('adjInf').value || 0));
  const adlEur = Math.max(0, Number(document.getElementById('adjAdultEur').value || 0));
  const chEur = Math.max(0, Number(document.getElementById('adjChildEur').value || 0));
  const paidEgp = Math.max(0, Number(document.getElementById('adjPaidEgp').value || 0));
  const fullCancel = document.getElementById('adjFullCancel').value === '1' || (adl + ch) === 0;
  const newEur = fullCancel ? 0 : (adl * adlEur + ch * chEur);
  const newTotalEgp = Math.round(newEur * 57);
  const remNeedEgp = Math.max(0, newTotalEgp - paidEgp);
  const collectAll = document.getElementById('adjCollectRestChk').checked;
  document.getElementById('adjCollectNowEgp').value = collectAll ? String(remNeedEgp) : '0';
  document.getElementById('adjPreviewBox').innerHTML = fullCancel
    ? `<span class="neg"><b>❌ Full Cancellation (&lt;N&gt;):</b> New Total = 0 EGP • Seller Commission = 0 EGP (Reversed).</span>`
    : `👥 <b>Actual Guests:</b> ${adl} Adults + ${ch} Children + ${inf} Infants (&lt;3 Free) = <b>€${fmt(newEur, 1)} EUR (${fmt(newTotalEgp)} EGP)</b><br>`
      + `💵 Deposit Already Paid: <b>${fmt(paidEgp)} EGP</b> • Remaining / Extra Charge Due: <b class="${remNeedEgp > 0 ? 'pos' : ''}">${fmt(remNeedEgp)} EGP</b>`;
}

async function submitAdjustVoucherForm(e) {
  e.preventDefault();
  const fullCancel = document.getElementById('adjFullCancel').value === '1';
  const res = await apiPost({
    action: 'modify_voucher_settlement',
    id: Number(document.getElementById('adjVid').value),
    new_adults: Number(document.getElementById('adjAdl').value || 0),
    new_children: Number(document.getElementById('adjCh').value || 0),
    new_infants: Number(document.getElementById('adjInf').value || 0),
    adult_price_eur: Number(document.getElementById('adjAdultEur').value || 0),
    child_price_eur: Number(document.getElementById('adjChildEur').value || 0),
    extra_collected_egp: Number(document.getElementById('adjCollectNowEgp').value || 0),
    collect_all_remaining_rest: document.getElementById('adjCollectRestChk').checked ? 1 : 0,
    mark_boarded: document.getElementById('adjBoardedChk').checked ? 1 : 0,
    cancel_trip: fullCancel ? 1 : 0,
    reason: document.getElementById('adjReason').value.trim()
  });
  if (!res || !res.ok) {
    alert((res && res.error) || 'Failed to update settlement');
    return;
  }
  document.getElementById('adjustVoucherModal').style.display = 'none';
  showToast(`✅ Updated ${res.voucher_no} (${res.change_type})! Logged to Audit & Accounting.`);
  await loadUserData();
}

async function decideAllowance(advanceId, decision) {
  const res = await apiPost({ action: 'decide_commission_advance', advance_id: advanceId, decision });
  if (res && res.ok) {
    showToast(`Allowance #${advanceId} marked ${decision}!`);
    await loadUserData();
  } else {
    alert((res && res.error) || 'Failed to process allowance');
  }
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
        <div style="grid-column:1/-1;display:grid;grid-template-columns:1fr 1fr;gap:8px;padding:9px 11px;border-radius:10px;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.32);">
          <div>
            <label style="color:#fde68a;">🎁 Special Discount Type</label>
            <select id="nvDiscType">
              <option value="NONE">No Discount</option>
              <option value="PERCENT">Percentage (%)</option>
              <option value="AMOUNT">Fixed Amount (EUR)</option>
            </select>
          </div>
          <div>
            <label style="color:#fde68a;">Discount Value (% or €)</label>
            <input type="number" step="0.5" min="0" id="nvDiscVal" value="0" placeholder="0">
          </div>
        </div>
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
    rest_eur: document.getElementById('nvRest').value,
    discount_type: document.getElementById('nvDiscType') ? document.getElementById('nvDiscType').value : 'NONE',
    discount_value: document.getElementById('nvDiscVal') ? Number(document.getElementById('nvDiscVal').value || 0) : 0,
    discount_currency: 'EUR'
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

let deferredInstallPrompt = null;
if (typeof window.addEventListener === 'function') {
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredInstallPrompt = e;
  });
}

function triggerInstallApp() {
  if (deferredInstallPrompt) {
    deferredInstallPrompt.prompt();
    deferredInstallPrompt.userChoice.finally(() => { deferredInstallPrompt = null; });
  } else {
    document.getElementById('installModal').style.display = 'flex';
  }
}

function openFeedbackModal() {
  document.getElementById('feedbackModal').style.display = 'flex';
  document.getElementById('fbMsg').focus();
}

async function submitTrialFeedback(e) {
  e.preventDefault();
  const res = await apiPost({
    action: 'submit_trial_feedback',
    feedback_type: document.getElementById('fbType').value,
    priority: document.getElementById('fbPrio').value,
    module_tab: STATE.activeTab || 'general',
    device_info: navigator.userAgent.slice(0, 100),
    message: document.getElementById('fbMsg').value
  });
  if (res.ok) {
    document.getElementById('fbMsg').value = '';
    document.getElementById('feedbackModal').style.display = 'none';
    showToast('Thank you! Trial feedback logged for Management & AI Team.');
    await loadUserData();
  }
}

function renderTrialPilotTab(ar) {
  const ws = STATE.ws || {};
  const st = ws.trial_stats || {};
  const fbList = ws.trial_feedback || [];
  const audits = ws.recent_audits || [];
  const whReady = !!st.full_warehouse_loaded;
  return `
    <div class="grid grid-kpi" style="margin-bottom:14px;">
      <div class="card">
        <div class="kpi-label">${ar ? 'حالة مستودع البيانات الكامل' : 'Warehouse Sync State'}</div>
        <div class="kpi-val ${whReady ? 'pos' : ''}" style="font-size:17px;">${whReady ? '✅ Full 165K+ Rows Active' : '⚡ Summary Seed Active'}</div>
        <div class="kpi-sub">${fmt(st.raw_rows_count)} Raw Sheet Rows Loaded</div>
      </div>
      <div class="card">
        <div class="kpi-label">${ar ? 'دخول الموظفين في التجربة' : 'Staff Pilot Logins'}</div>
        <div class="kpi-val">${fmt(st.logins_count)}</div>
        <div class="kpi-sub">Across 24 Role Accounts</div>
      </div>
      <div class="card">
        <div class="kpi-label">${ar ? 'فوتشرات التدريب التجريبية' : 'Practice TRIAL- Vouchers'}</div>
        <div class="kpi-val" style="color:var(--cyan);">${fmt(st.trial_vouchers_count)}</div>
        <div class="kpi-sub">Isolated from Historical GL</div>
      </div>
      <div class="card">
        <div class="kpi-label">${ar ? 'ملاحظات واقتراحات الفريق' : 'Open Staff Feedback'}</div>
        <div class="kpi-val" style="color:#fcd34d;">${fmt(st.open_feedback_count)}</div>
        <div class="kpi-sub"><a href="/handlers/erp_export.php?report=trial_feedback&format=xlsx" style="color:var(--cyan);">⬇ Export Feedback .xlsx</a></div>
      </div>
    </div>

    ${hasPerm('bi.view_executive') ? `
    <div class="grid grid-2" style="margin-bottom:14px;">
      <div class="card">
        <h3 style="font-size:14px;margin-bottom:6px;">📦 ${ar ? 'رفع قاعدة البيانات التاريخية الكاملة بضغطة واحدة (بدون cPanel)' : '1-Click Full Historical Warehouse Hydrator (No cPanel Needed!)'}</h3>
        <p style="font-size:12px;color:var(--muted);margin-bottom:10px;">
          Select <b>sea_college_bigdata_dump.sql.gz</b> (5.4 MB from your Google Drive AI Outputs folder) to hydrate all 160,163 raw rows, 15,667 vouchers, 5,692 expense vouchers & 209 GL journals directly through your browser!
        </p>
        <form onsubmit="uploadWarehouseDump(event)" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
          <input type="file" id="whFile" accept=".gz,.sqlite3,.sqlite,.db" required style="flex:1;">
          <button type="submit" id="btnWhUp" class="btn btn-primary">⬆ Hydrate Full SQL Warehouse</button>
        </form>
      </div>

      <div class="card">
        <h3 style="font-size:14px;margin-bottom:6px;">🧹 ${ar ? 'تصفير حركات التدريب التجريبية (Sandbox Reset)' : 'Trial Practice Sandbox Reset (Safe Go-Live Clean)'}</h3>
        <p style="font-size:12px;color:var(--muted);margin-bottom:10px;">
          During the trial week, let Fady, Adel, Ahmed Nasr, Talal & Cashiers create practice <b>TRIAL-*</b> vouchers freely. Click below anytime to wipe only practice entries while keeping 100% of historical accounting intact!
        </p>
        <button type="button" class="btn btn-amber" onclick="resetSandbox()">🧹 Clear Practice TRIAL-* Entries Only</button>
      </div>
    </div>` : ''}

    <div class="grid grid-2">
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
          <h3 style="font-size:14px;">🧪 ${ar ? 'صندوق ملاحظات واقتراحات الموظفين أثناء التجربة' : 'Live Staff Trial Feedback & Improvement Queue'}</h3>
          <a class="btn btn-emerald" href="/handlers/erp_export.php?report=trial_feedback&format=xlsx">⬇ .xlsx</a>
        </div>
        <div class="table-wrap" style="max-height:320px;">
          <table>
            <thead><tr><th>Time</th><th>Staff / Role</th><th>Type</th><th>Tab</th><th>Note / Request</th><th>Status</th></tr></thead>
            <tbody>
              ${fbList.map((f) => `
                <tr>
                  <td>${esc(f.created_at)}</td>
                  <td><b>${esc(f.username)}</b><br><span style="font-size:10px;color:var(--muted);">${esc(f.role_code)}</span></td>
                  <td><span class="pill" style="${f.priority === 'HIGH' ? 'color:#fb7185;border-color:#f43f5e;' : ''}">${esc(f.feedback_type)}</span></td>
                  <td>${esc(f.module_tab)}</td>
                  <td style="white-space:normal;min-width:200px;">${esc(f.message)}</td>
                  <td>
                    ${f.status === 'RESOLVED'
                      ? '<span class="pos">✓ RESOLVED</span>'
                      : `<button class="btn" onclick="markFeedbackResolved(${f.id})">✓ Resolve</button>`}
                  </td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <h3 style="font-size:14px;margin-bottom:8px;">🛡️ ${ar ? 'سجل نشاط الموظفين المباشر (Audit Trail)' : 'Live Pilot Activity & Role Audit Log'}</h3>
        <div class="table-wrap" style="max-height:320px;">
          <table>
            <thead><tr><th>UTC Time</th><th>User</th><th>Role</th><th>Action</th><th>Ref</th></tr></thead>
            <tbody>
              ${audits.map((a) => `
                <tr>
                  <td>${esc(String(a.created_at).slice(0, 19).replace('T', ' '))}</td>
                  <td><b>${esc(a.username)}</b></td>
                  <td><span class="pill">${esc(a.role_code)}</span></td>
                  <td>${esc(a.action_type)}</td>
                  <td>${esc(a.entity_id)}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  `;
}

async function markFeedbackResolved(id) {
  const res = await apiPost({ action: 'update_feedback_status', id, status: 'RESOLVED' });
  if (res.ok) { showToast('Feedback marked resolved!'); await loadUserData(); }
}

async function resetSandbox() {
  if (!confirm('Clear all TRIAL-* practice vouchers & safe entries created during mobile testing? (Historical 2024–2026 accounting stays 100% untouched)')) return;
  const res = await apiPost({ action: 'reset_trial_sandbox' });
  if (res.ok) {
    showToast(`Cleared ${res.deleted_vouchers} practice vouchers & ${res.deleted_expenses} practice safe entries!`);
    await loadUserData();
  }
}

async function uploadWarehouseDump(e) {
  e.preventDefault();
  const fInput = document.getElementById('whFile');
  if (!fInput.files || !fInput.files[0]) return;
  const btn = document.getElementById('btnWhUp');
  btn.disabled = true;
  btn.textContent = '⏳ Importing SQL Warehouse...';
  const fd = new FormData();
  fd.append('action', 'upload_warehouse_gz');
  fd.append('csrf_token', STATE.csrf);
  fd.append('warehouse_file', fInput.files[0]);
  try {
    const r = await fetch('/handlers/erp_api.php?action=upload_warehouse_gz', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-CSRF-Token': STATE.csrf },
      body: fd
    });
    const res = await r.json();
    if (res.ok) {
      showToast('Full 2-Year SQL Warehouse imported successfully!');
      await loadUserData();
    } else {
      alert(res.error || 'Import failed');
    }
  } catch (err) {
    alert('Upload error: ' + err.message);
  } finally {
    btn.disabled = false;
    btn.textContent = '⬆ Hydrate Full SQL Warehouse';
  }
}

/* ================= USER PROFILES & TEAM DIRECTORY ================= */
let PROFILE_FILTER = { q: '', role: '' };

function renderTeamProfilesTab(ar) {
  const ws = STATE.ws || {};
  const list = ws.team_profiles || [];
  const isAdmin = hasPerm('bi.view_executive');
  const photosCnt = list.filter((p) => !!p.avatar_url).length;
  const phonesCnt = list.filter((p) => !!p.phone).length;
  const emailsCnt = list.filter((p) => !!p.email).length;

  const filtered = list.filter((p) => {
    if (PROFILE_FILTER.role && p.role_code !== PROFILE_FILTER.role) return false;
    if (!PROFILE_FILTER.q) return true;
    const hay = `${p.username} ${p.display_name_en} ${p.display_name_ar} ${p.job_title_en} ${p.job_title_ar} ${p.phone} ${p.email} ${p.branch_scope}`.toLowerCase();
    return hay.includes(PROFILE_FILTER.q.toLowerCase());
  });

  const uniqueRoles = [...new Set(list.map((p) => p.role_code))];

  return `
    <div class="grid grid-kpi" style="margin-bottom:14px;">
      <div class="card">
        <div class="kpi-label">${ar ? 'إجمالي حسابات الفريق' : 'Active Team Profiles'}</div>
        <div class="kpi-val">${fmt(list.length)}</div>
        <div class="kpi-sub">${ar ? 'ملفات شخصية بصلاحيات محددة' : 'Role-Scoped Company Accounts'}</div>
      </div>
      <div class="card">
        <div class="kpi-label">${ar ? 'صور شخصية مرفوعة' : 'Verified Profile Photos'}</div>
        <div class="kpi-val" style="color:var(--cyan);">${fmt(photosCnt)} / ${fmt(list.length)}</div>
        <div class="kpi-sub">${ar ? 'ارفع صورة أي موظف بضغطة واحدة' : 'Tap Edit on any card to upload photo'}</div>
      </div>
      <div class="card">
        <div class="kpi-label">${ar ? 'أرقام الهاتف والواتساب' : 'Direct Phone / WhatsApp'}</div>
        <div class="kpi-val pos">${fmt(phonesCnt)} / ${fmt(list.length)}</div>
        <div class="kpi-sub">${ar ? 'اتصال مباشر أو واتساب بضغطة' : '1-Tap Call & WhatsApp Ready'}</div>
      </div>
      <div class="card">
        <div class="kpi-label">${ar ? 'البريد الإلكتروني الرسمي' : 'Corporate Emails'}</div>
        <div class="kpi-val">${fmt(emailsCnt)} / ${fmt(list.length)}</div>
        <div class="kpi-sub">@seacollegediveclub.com</div>
      </div>
    </div>

    <div class="card" style="margin-bottom:14px;">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:12px;">
        <div>
          <h3 style="font-size:16px;">👥 ${ar ? 'دليل ملفات فريق العمل والملاك والشركاء' : 'Sea College Team Profiles — Photos, Phones & Emails'}</h3>
          <p style="font-size:12px;color:var(--muted);">
            ${ar ? 'اضغط للتواصل المباشر عبر الهاتف أو الواتساب أو الإيميل، أو اضغط تعديل لإضافة الصورة والرقم.' : 'Tap Call, WhatsApp, or Email for instant contact — or click Edit Profile to upload a staff photo & phone number.'}
          </p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
          ${isAdmin ? `<button type="button" class="btn btn-primary" onclick="openProfileModal(null, 'create')">➕ ${ar ? 'إضافة مستخدم جديد' : 'Add Staff Profile'}</button>` : ''}
          ${hasPerm('bi.view_executive', 'gl.view_all_ledgers') ? `<a class="btn btn-emerald" href="/handlers/erp_export.php?report=team_profiles&format=xlsx">⬇ ${ar ? 'تصدير Excel' : 'Export .xlsx'}</a>` : ''}
        </div>
      </div>

      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;">
        <input type="text" style="max-width:280px;" placeholder="${ar ? 'بحث بالاسم أو الرقم أو الإيميل...' : 'Search name, phone, email, branch...'}" value="${esc(PROFILE_FILTER.q)}" oninput="PROFILE_FILTER.q=this.value;renderApp();">
        <select style="max-width:240px;" onchange="PROFILE_FILTER.role=this.value;renderApp();">
          <option value="">${ar ? 'كل الأدوار الوظيفية' : 'All Roles'}</option>
          ${uniqueRoles.map((rc) => `<option value="${esc(rc)}" ${PROFILE_FILTER.role === rc ? 'selected' : ''}>${esc(rc)}</option>`).join('')}
        </select>
      </div>

      <div class="grid grid-3">
        ${filtered.map((p) => {
          const canEdit = isAdmin || (STATE.user && STATE.user.username === p.username);
          const wd = waDigits(p.phone);
          return `
            <div class="profile-card">
              <div>
                <div style="display:flex;align-items:center;gap:12px;">
                  ${renderAvatarHtml(p, 60)}
                  <div style="min-width:0;flex:1;">
                    <div style="font-size:14.5px;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                      ${esc(ar ? p.display_name_ar : p.display_name_en)}
                    </div>
                    <div style="font-size:12px;color:var(--cyan);font-weight:700;margin-top:1px;">
                      ${esc(ar ? (p.job_title_ar || p.role_ar) : (p.job_title_en || p.role_en))}
                    </div>
                    <div style="font-size:11px;color:var(--muted);margin-top:2px;">
                      @${esc(p.username)} • <span class="pill" style="padding:1px 7px;font-size:10px;">${esc(p.branch_scope)}</span>
                    </div>
                  </div>
                </div>

                <div style="margin-top:11px;display:grid;gap:5px;font-size:12px;">
                  <div>
                    <span style="color:var(--muted);">📞 ${ar ? 'الهاتف:' : 'Phone:'}</span>
                    ${p.phone
                      ? `<a href="tel:${esc(p.phone.replace(/\s+/g, ''))}" style="color:var(--text);font-weight:700;text-decoration:none;margin-inline-start:4px;">${esc(p.phone)}</a>`
                      : `<span style="color:#f59e0b;font-size:11px;margin-inline-start:4px;">${ar ? 'بانتظار إضافة الرقم' : 'Pending phone #'}</span>`}
                  </div>
                  <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <span style="color:var(--muted);">✉️ ${ar ? 'الإيميل:' : 'Email:'}</span>
                    ${p.email
                      ? `<a href="mailto:${esc(p.email)}" style="color:#93c5fd;text-decoration:none;margin-inline-start:4px;">${esc(p.email)}</a>`
                      : `<span style="color:var(--muted);margin-inline-start:4px;">—</span>`}
                  </div>
                  ${p.bio_notes ? `<div style="font-size:11px;color:var(--muted);margin-top:2px;">📝 ${esc(p.bio_notes)}</div>` : ''}
                </div>
              </div>

              <div style="display:flex;gap:6px;flex-wrap:wrap;border-top:1px solid rgba(148,163,184,0.12);padding-top:9px;">
                ${p.phone ? `<a class="btn" style="padding:5px 9px;font-size:11px;" href="tel:${esc(p.phone.replace(/\s+/g, ''))}">📞 Call</a>` : ''}
                ${wd.length >= 8 ? `<a class="btn btn-emerald" style="padding:5px 9px;font-size:11px;" href="https://wa.me/${esc(wd)}" target="_blank" rel="noopener">💬 WhatsApp</a>` : ''}
                ${p.email ? `<a class="btn" style="padding:5px 9px;font-size:11px;" href="mailto:${esc(p.email)}">✉️ Email</a>` : ''}
                ${(hasPerm('bi.view_executive', 'gl.view_all_ledgers', 'hr.manage_payroll', 'safe.view_balances') || (STATE.user && STATE.user.username === p.username)) ? `<button type="button" class="btn btn-primary" style="padding:5px 9px;font-size:11px;" onclick="openTeamProfileDossier('${esc(p.username)}', '${esc(p.role_code)}')">📒 ${ar ? 'كشف الحساب' : 'Statement'}</button>` : ''}
                ${canEdit ? `<button type="button" class="btn btn-amber" style="padding:5px 9px;font-size:11px;margin-inline-start:auto;" onclick="openProfileModal('${esc(p.username)}', 'update')">✏️ ${ar ? 'تعديل / صورة' : 'Edit / Photo'}</button>` : ''}
              </div>
            </div>
          `;
        }).join('')}
      </div>
    </div>
  `;
}

function renderMyProfileTab(ar) {
  const u = STATE.user || {};
  const wd = waDigits(u.phone);
  return `
    <div class="grid grid-2">
      <div class="card">
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:14px;">
          ${renderAvatarHtml(u, 92)}
          <div>
            <span class="pill">${esc(u.role_code)}</span>
            <h2 style="font-size:20px;margin-top:6px;">${esc(ar ? u.display_name_ar : u.display_name_en)}</h2>
            <div style="font-size:13px;color:var(--cyan);font-weight:700;margin-top:2px;">
              ${esc(ar ? (u.job_title_ar || u.role_name_ar) : (u.job_title_en || u.role_name_en))}
            </div>
            <div style="font-size:12px;color:var(--muted);margin-top:3px;">@${esc(u.username)} • Employee ID: ${esc(u.emp_code || 'SC-STAFF')}</div>
          </div>
        </div>

        <div class="grid" style="gap:9px;padding:12px;border-radius:11px;background:rgba(6,21,38,0.65);border:1px solid var(--border);margin-bottom:14px;">
          <div><b>📞 ${ar ? 'رقم الهاتف / واتساب:' : 'Phone / WhatsApp:'}</b> ${u.phone ? `<span class="pos">${esc(u.phone)}</span>` : '<span style="color:#f59e0b;">Not set yet — tap Edit below</span>'}</div>
          <div><b>✉️ ${ar ? 'البريد الإلكتروني:' : 'Email Address:'}</b> ${esc(u.email || '—')}</div>
          <div><b>🏢 ${ar ? 'نطاق الفروع والمراكب:' : 'Branch & Vessel Scope:'}</b> Branch: <code>${esc(u.branch_scope)}</code> • Vessels: <code>${esc(u.vessel_scope)}</code> ${u.rep_scope ? `• Sales Code: <code>${esc(u.rep_scope)}</code>` : ''}</div>
          ${u.bio_notes ? `<div><b>📝 ${ar ? 'ملاحظات:' : 'Notes:'}</b> ${esc(u.bio_notes)}</div>` : ''}
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;">
          <button type="button" class="btn btn-primary" onclick="openProfileModal('${esc(u.username)}', 'update')">📷 ${ar ? 'تعديل بياناتي وصورتي الشخصية' : 'Edit My Profile, Photo & Password'}</button>
          ${wd.length >= 8 ? `<a class="btn btn-emerald" href="https://wa.me/${esc(wd)}" target="_blank" rel="noopener">💬 Test My WhatsApp Link</a>` : ''}
        </div>
      </div>

      <div class="card">
        <h3 style="font-size:15px;margin-bottom:8px;">🛡️ ${ar ? 'الصلاحيات المفعلة لحسابك' : 'Your Active Role Authorities & Permissions'}</h3>
        <p style="font-size:12px;color:var(--muted);margin-bottom:10px;">
          ${ar ? 'تحدد هذه الصلاحيات اللوحات والتقارير والعمليات المتاحة لك داخل تطبيق سي كوليدج:' : 'These RBAC permissions govern which dashboards, sheets, and operational actions your account can access:'}
        </p>
        <div style="display:flex;gap:7px;flex-wrap:wrap;">
          ${STATE.perms.map((p) => `<span class="pill" style="padding:5px 10px;">✓ ${esc(p)}</span>`).join('')}
        </div>
      </div>
    </div>
  `;
}

function openProfileModal(username, mode = 'update') {
  const ws = STATE.ws || {};
  const isAdmin = hasPerm('bi.view_executive');
  const modal = document.getElementById('profileEditModal');
  document.getElementById('pfErr').style.display = 'none';
  document.getElementById('pfMode').value = mode;
  document.getElementById('pfAvatarDataUrl').value = '';
  document.getElementById('pfPhotoInput').value = '';
  document.getElementById('pfNewPass').value = '';

  const rolesList = ws.all_roles || [
    { role_code: 'OWNER_ADMIN', name_en: 'Owner / Executive & CFO' },
    { role_code: 'FLEET_PARTNER', name_en: 'Yacht Equity Partner' },
    { role_code: 'CHIEF_ACCOUNTANT', name_en: 'Chief Accountant & Auditor' },
    { role_code: 'CASHIER_TREASURY', name_en: 'Cashier & Multi-Currency Safe' },
    { role_code: 'OPERATIONS_MANAGER', name_en: 'Operations & Boat Dispatch' },
    { role_code: 'JETTY_SUPERVISOR', name_en: 'Jetty Supervisor, Gear & Photo' },
    { role_code: 'DIVE_INSTRUCTOR', name_en: 'PADI Instructor / Boat Captain' },
    { role_code: 'SALES_REP', name_en: 'Hotel Desk Sales Representative' },
    { role_code: 'CUSTOMER_CARE', name_en: 'Multilingual Concierge & CRM' }
  ];
  const roleSel = document.getElementById('pfRoleCode');
  roleSel.innerHTML = rolesList.map((r) => `<option value="${esc(r.role_code)}">${esc(r.role_code)} — ${esc(r.name_en)}</option>`).join('');

  document.getElementById('pfAdminScopeBox').style.display = isAdmin ? 'block' : 'none';

  if (mode === 'create') {
    document.getElementById('profModalTitle').textContent = '➕ Create New Staff User Profile';
    document.getElementById('pfCreateUsernameRow').style.display = 'grid';
    document.getElementById('pfPassResetCol').style.display = 'none';
    document.getElementById('pfTargetUser').value = '';
    document.getElementById('pfNewUsername').value = '';
    document.getElementById('pfInitPass').value = '';
    document.getElementById('pfNameEn').value = '';
    document.getElementById('pfNameAr').value = '';
    document.getElementById('pfTitleEn').value = '';
    document.getElementById('pfTitleAr').value = '';
    document.getElementById('pfPhone').value = '+20 ';
    document.getElementById('pfEmail').value = '';
    document.getElementById('pfBio').value = '';
    roleSel.value = 'SALES_REP';
    document.getElementById('pfBranchScope').value = 'CYRENE';
    document.getElementById('pfVesselScope').value = 'ALL';
    document.getElementById('pfRepScope').value = '';
    document.getElementById('pfPreviewBox').innerHTML = renderAvatarHtml({ display_name_en: 'New User' }, 64);
  } else {
    const target = ((ws.team_profiles || []).find((p) => p.username === username))
      || (STATE.user && STATE.user.username === username ? STATE.user : null);
    if (!target) return;
    document.getElementById('profModalTitle').textContent = `✏️ Edit Profile — @${target.username}`;
    document.getElementById('pfCreateUsernameRow').style.display = 'none';
    document.getElementById('pfPassResetCol').style.display = 'block';
    document.getElementById('pfTargetUser').value = target.username;
    document.getElementById('pfNameEn').value = target.display_name_en || '';
    document.getElementById('pfNameAr').value = target.display_name_ar || '';
    document.getElementById('pfTitleEn').value = target.job_title_en || '';
    document.getElementById('pfTitleAr').value = target.job_title_ar || '';
    document.getElementById('pfPhone').value = target.phone || '';
    document.getElementById('pfEmail').value = target.email || '';
    document.getElementById('pfBio').value = target.bio_notes || '';
    if (isAdmin) {
      roleSel.value = target.role_code || 'SALES_REP';
      document.getElementById('pfBranchScope').value = target.branch_scope || 'ALL';
      document.getElementById('pfVesselScope').value = target.vessel_scope || 'ALL';
      document.getElementById('pfRepScope').value = target.rep_scope || '';
    }
    document.getElementById('pfPreviewBox').innerHTML = renderAvatarHtml(target, 64);
  }

  modal.style.display = 'flex';
}

function closeProfileModal() {
  document.getElementById('profileEditModal').style.display = 'none';
}

function previewProfilePhoto(input) {
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];
  const reader = new FileReader();
  reader.onload = (ev) => {
    const img = new Image();
    img.onload = () => {
      const size = 480;
      const canvas = document.createElement('canvas');
      canvas.width = size;
      canvas.height = size;
      const ctx = canvas.getContext('2d');
      const minSide = Math.min(img.width, img.height);
      const sx = Math.floor((img.width - minSide) / 2);
      const sy = Math.floor((img.height - minSide) * 0.25); // bias slightly toward top for portrait faces
      ctx.drawImage(img, sx, Math.max(0, sy), minSide, minSide, 0, 0, size, size);
      const dataUrl = canvas.toDataURL('image/jpeg', 0.88);
      document.getElementById('pfAvatarDataUrl').value = dataUrl;
      document.getElementById('pfPreviewBox').innerHTML = `<img src="${dataUrl}" class="avatar-circle" style="width:64px;height:64px;" alt="Preview">`;
    };
    img.src = ev.target.result;
  };
  reader.readAsDataURL(file);
}

async function submitProfileForm(e) {
  e.preventDefault();
  const errEl = document.getElementById('pfErr');
  errEl.style.display = 'none';
  const mode = document.getElementById('pfMode').value;
  const btn = document.getElementById('pfSaveBtn');
  btn.disabled = true;
  btn.textContent = '⏳ Saving...';

  const payload = {
    action: 'save_user_profile',
    mode,
    target_username: document.getElementById('pfTargetUser').value,
    username: document.getElementById('pfNewUsername').value.trim().toLowerCase(),
    display_name_en: document.getElementById('pfNameEn').value.trim(),
    display_name_ar: document.getElementById('pfNameAr').value.trim(),
    job_title_en: document.getElementById('pfTitleEn').value.trim(),
    job_title_ar: document.getElementById('pfTitleAr').value.trim(),
    phone: document.getElementById('pfPhone').value.trim(),
    email: document.getElementById('pfEmail').value.trim(),
    bio_notes: document.getElementById('pfBio').value.trim(),
    avatar_data_url: document.getElementById('pfAvatarDataUrl').value,
    new_password: mode === 'create'
      ? document.getElementById('pfInitPass').value
      : document.getElementById('pfNewPass').value
  };

  if (hasPerm('bi.view_executive')) {
    payload.role_code = document.getElementById('pfRoleCode').value;
    payload.branch_scope = document.getElementById('pfBranchScope').value;
    payload.vessel_scope = document.getElementById('pfVesselScope').value.trim() || 'ALL';
    payload.rep_scope = document.getElementById('pfRepScope').value.trim().toUpperCase();
  }

  try {
    const res = await apiPost(payload);
    if (!res.ok) {
      errEl.textContent = res.error || 'Could not save profile';
      errEl.style.display = 'block';
      return;
    }
    if (res.me) {
      STATE.user = res.me;
    }
    closeProfileModal();
    showToast(mode === 'create' ? `Created profile @${res.username}!` : `Saved profile @${res.username}!`);
    await loadUserData();
  } catch (err) {
    errEl.textContent = 'Network error: ' + err.message;
    errEl.style.display = 'block';
  } finally {
    btn.disabled = false;
    btn.textContent = '💾 Save User Profile';
  }
}

/* ================= F12: GUEST-SAFE TABLET PITCH, ALL SHARM/EGYPT TRIPS & 1-TAP BOOKING ================= */
function getSellerReferralSlug() {
  const u = STATE.user || {};
  if (u.referral_code) return String(u.referral_code);
  if (u.username && String(u.username).endsWith('.sales')) return String(u.username).replace(/\.sales$/i, '');
  return 'fady';
}

function copyTextPrompt(txt) {
  const val = String(txt || '');
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(val).then(() => showToast('📋 Copied link to clipboard!')).catch(() => {});
  } else {
    showToast('Link ready: ' + val);
  }
}

function shareNativeOrCopy(url, titleTxt) {
  const cleanUrl = String(url || '');
  if (navigator.share) {
    navigator.share({ title: String(titleTxt || 'Sea College Sharm Trips'), url: cleanUrl }).catch(() => copyTextPrompt(cleanUrl));
  } else {
    copyTextPrompt(cleanUrl);
  }
}

function getUnifiedPitchItems(catObj) {
  const cat = catObj || STATE.sellerCatalog || {};
  const seen = new Set();
  const out = [];
  const pushItem = (raw, sourceTag) => {
    const code = String(raw.code || raw.id || '');
    if (!code || seen.has(code)) return;
    seen.add(code);
    out.push({ ...raw, code, _sourceTag: sourceTag });
  };
  (cat.excursions || []).forEach((x) => pushItem(x, 'EXCURSION'));
  (cat.items || []).forEach((x) => pushItem(x, 'DIVING'));
  (cat.operational_skus || []).forEach((x) => pushItem(x, 'OPS'));
  return out;
}

async function changePitchLang(lg) {
  STATE.pitchLang = lg;
  const res = await apiGet({ action: 'seller_catalog', lang: lg });
  if (res && res.ok) {
    STATE.sellerCatalog = res;
    renderApp();
  }
}

function getAdultChildUnitInCurr(item, curr) {
  const cat = STATE.sellerCatalog || {};
  const fx = cat.fx_rates || { EUR: 57, USD: 50, GBP: 65, EGP: 1 };
  const c = curr || STATE.pitchCurr || 'EUR';
  const adlEur = Number(item.adult_price_eur ?? item.price_eur ?? 35);
  const chEur = Number(item.child_price_eur ?? Math.round(adlEur * 0.5 * 2) / 2);
  const factor = c === 'EUR' ? 1 : (c === 'EGP' ? Number(fx.EUR || 57) : (Number(fx.EUR || 57) / Number(fx[c] || 50)));
  return {
    adult: Math.round(adlEur * factor * 100) / 100,
    child: Math.round(chEur * factor * 100) / 100,
    curr: c,
    sym: c === 'EUR' ? '€' : (c === 'USD' ? '$' : (c === 'GBP' ? '£' : ''))
  };
}

function formatPitchPrice(item) {
  const cur = STATE.pitchCurr || 'EUR';
  const u = getAdultChildUnitInCurr(item, cur);
  if (cur === 'EGP') return fmt(u.adult, 0) + ' EGP';
  return u.sym + fmt(u.adult, 0) + ' ' + cur;
}

function getSellerDiscountPolicy() {
  const isOwner = hasPerm('bi.view_executive');
  const p = (STATE.sellerCatalog && STATE.sellerCatalog.discount_policy)
    || (STATE.sellerWallet && STATE.sellerWallet.discount_policy)
    || { is_enabled: true, max_discount_pct: 20, max_discount_eur: 50 };
  return {
    is_enabled: isOwner ? true : Boolean(p.is_enabled),
    max_discount_pct: isOwner ? 80 : Number(p.max_discount_pct ?? 20),
    max_discount_eur: isOwner ? 500 : Number(p.max_discount_eur ?? 50)
  };
}

function calcClientDiscountForTrip(item, adl, ch, curr, discType, discVal) {
  const cat = STATE.sellerCatalog || {};
  const fx = cat.fx_rates || { EUR: 57, USD: 50, GBP: 65, EGP: 1 };
  const pol = getSellerDiscountPolicy();
  const u = getAdultChildUnitInCurr(item, curr);
  const listInCurr = Math.round((adl * u.adult + ch * u.child) * 100) / 100;
  const rawType = String(discType || 'NONE').toUpperCase();
  const rawVal = Math.max(0, Number(discVal || 0));
  if (!pol.is_enabled || listInCurr <= 0 || rawType === 'NONE' || rawVal <= 0) {
    return { listTotal: listInCurr, netTotal: listInCurr, discInCurr: 0, appliedType: 'NONE', appliedVal: 0, badge: '', clamped: false };
  }
  const currRateEgp = Number(fx[u.curr] || fx.EUR || 57);
  const fxEur = Number(fx.EUR || 57);
  const listEgp = listInCurr * currRateEgp;
  const isOutside = String(item.default_sale_mode || '').toUpperCase().includes('OUT');
  const supCostEgp = isOutside
    ? (adl * Number(item.adult_cost_egp || item.default_supplier_cost_egp || 0) + ch * Number(item.child_cost_egp || 0))
    : 0;
  const maxAboveVendorInCurr = Math.max(0, (listEgp - supCostEgp) / currRateEgp);

  let discInCurr = 0;
  let appliedVal = rawVal;
  let clamped = false;
  if (rawType === 'PERCENT') {
    if (appliedVal > pol.max_discount_pct) {
      appliedVal = pol.max_discount_pct;
      clamped = true;
    }
    discInCurr = listInCurr * (appliedVal / 100);
    if (discInCurr > maxAboveVendorInCurr) {
      discInCurr = maxAboveVendorInCurr;
      appliedVal = listInCurr > 0 ? Math.round((discInCurr / listInCurr) * 1000) / 10 : 0;
      clamped = true;
    }
  } else {
    const maxAllowedInCurr = (pol.max_discount_eur * fxEur) / currRateEgp;
    const halfCapInCurr = hasPerm('bi.view_executive') ? listInCurr : (listInCurr * 0.5);
    const ceilInCurr = Math.min(maxAllowedInCurr, halfCapInCurr, maxAboveVendorInCurr);
    discInCurr = Math.min(rawVal, Math.max(0, ceilInCurr));
    if (rawVal > discInCurr + 0.01) clamped = true;
    appliedVal = Math.round(discInCurr * 100) / 100;
  }
  discInCurr = Math.round(Math.max(0, discInCurr) * 100) / 100;
  const netTotal = Math.round(Math.max(0, listInCurr - discInCurr) * 100) / 100;
  if (discInCurr <= 0) {
    return { listTotal: listInCurr, netTotal: listInCurr, discInCurr: 0, appliedType: 'NONE', appliedVal: 0, badge: '', clamped };
  }
  const badge = rawType === 'PERCENT'
    ? `🎁 -${appliedVal}%`
    : `🎁 -${u.curr === 'EGP' ? fmt(appliedVal, 0) + ' EGP' : u.sym + fmt(appliedVal, 1)}`;
  return { listTotal: listInCurr, netTotal, discInCurr, appliedType: rawType, appliedVal, badge, clamped };
}

function buildAttributedProductUrl(item) {
  const slug = getSellerReferralSlug();
  const code = String((item && (item.code || item.id)) || '');
  const lg = STATE.pitchLang && STATE.pitchLang !== 'en' ? ('/' + encodeURIComponent(STATE.pitchLang)) : '';
  return `https://seacollegediveclub.com${lg}/s/${encodeURIComponent(slug)}${code ? '/' + encodeURIComponent(code) : ''}`;
}

function syncProductQrModalShareTargets(item, url, promoLabel = '') {
  const uPrice = getAdultChildUnitInCurr(item, STATE.pitchCurr);
  const chTxt = uPrice.curr === 'EGP' ? `${fmt(uPrice.child, 0)} EGP` : `${uPrice.sym}${fmt(uPrice.child, 0)}`;
  document.getElementById('pqrUrlTxt').textContent = url;
  const svgHtml = (window.SCQR && typeof window.SCQR.toSvg === 'function')
    ? window.SCQR.toSvg(url, { size: 220 })
    : `<div style="color:#041322;padding:16px;font-weight:700;">${esc(url)}</div>`;
  document.getElementById('pqrSvgWrap').innerHTML = svgHtml;
  const promoLine = promoLabel ? `\n🎁 *Special VIP Discount Included: ${promoLabel}*` : '';
  const msg = `🌊 *Sea College Sharm El Sheikh — Official Concierge Offer*\n✨ *${item.name || item.name_en}*${promoLine}\n👤 Adult: ${formatPitchPrice(item)} | 🧒 Child: ${chTxt} | 👶 Infant (<3 yrs): FREE\n🚐 Hotel Pickup (${STATE.pitchHotel || 'Hotel'}): ${STATE.pitchPickup || '07:55–08:10'}\n✅ View photos & confirm your booking anytime:\n${url}`;
  document.getElementById('pqrWaBtn').href = 'https://wa.me/?text=' + encodeURIComponent(msg);
  const fbBtn = document.getElementById('pqrFbBtn');
  if (fbBtn) fbBtn.href = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url);
  const tgBtn = document.getElementById('pqrTgBtn');
  if (tgBtn) tgBtn.href = 'https://t.me/share/url?url=' + encodeURIComponent(url) + '&text=' + encodeURIComponent((item.name || item.name_en || '') + (promoLabel ? ` (${promoLabel})` : ''));
}

function openProductQrModal(itemCode) {
  const all = getUnifiedPitchItems(STATE.sellerCatalog);
  const item = all.find((x) => String(x.code || x.id) === String(itemCode)) || all[0];
  if (!item) return;
  const code = String(item.code || item.id || '');
  document.getElementById('pqrItemCode').value = code;
  const pol = getSellerDiscountPolicy();
  const promoRow = document.getElementById('pqrPromoControls');
  if (promoRow) promoRow.style.display = pol.is_enabled ? 'block' : 'none';

  const dTypeEl = document.getElementById('pqrDiscType');
  const dValEl = document.getElementById('pqrDiscVal');
  if (dTypeEl && dValEl) {
    dTypeEl.value = pol.is_enabled && STATE.pitchDiscType !== 'NONE' ? STATE.pitchDiscType : 'NONE';
    dValEl.value = pol.is_enabled && STATE.pitchDiscType !== 'NONE' ? String(STATE.pitchDiscVal || 0) : '0';
  }

  const url = buildAttributedProductUrl(item);
  const uPrice = getAdultChildUnitInCurr(item, STATE.pitchCurr);
  const chTxt = uPrice.curr === 'EGP' ? `${fmt(uPrice.child, 0)} EGP` : `${uPrice.sym}${fmt(uPrice.child, 0)}`;
  document.getElementById('pqrTitle').textContent = '📱 ' + (item.name || item.name_en || 'Scan or Share Booking Link');
  document.getElementById('pqrSub').textContent = `Adult: ${formatPitchPrice(item)} • Child: ${chTxt} • Infant (<3): FREE • Pickup: ${STATE.pitchPickup || '07:55–08:10'}`;
  syncProductQrModalShareTargets(item, url, '');
  document.getElementById('productQrModal').style.display = 'flex';
  if (pol.is_enabled && STATE.pitchDiscType !== 'NONE' && Number(STATE.pitchDiscVal || 0) > 0) {
    applyPromoToProductQrModal();
  }
}

async function applyPromoToProductQrModal() {
  const code = document.getElementById('pqrItemCode').value;
  const all = getUnifiedPitchItems(STATE.sellerCatalog);
  const item = all.find((x) => String(x.code || x.id) === String(code)) || all[0];
  if (!item) return;
  const dType = document.getElementById('pqrDiscType').value || 'NONE';
  const dVal = Math.max(0, Number(document.getElementById('pqrDiscVal').value || 0));
  if (dType === 'NONE' || dVal <= 0) {
    syncProductQrModalShareTargets(item, buildAttributedProductUrl(item), '');
    return;
  }
  const res = await apiPost({
    action: 'create_promo_share_link',
    trip_code: code,
    lang: STATE.pitchLang || 'en',
    discount_type: dType,
    discount_value: dVal,
    discount_currency: 'EUR'
  });
  if (!res || !res.ok) {
    alert((res && res.error) || 'Could not generate promo link');
    return;
  }
  if (res.was_clamped) {
    document.getElementById('pqrDiscVal').value = String(res.discount_value);
    showToast(`ℹ️ Capped to policy max: ${res.discount_type === 'PERCENT' ? res.discount_value + '%' : '€' + res.discount_value}`);
  } else {
    showToast('🎁 Signed Special Promo QR & Link Ready!');
  }
  const lbl = res.discount_type === 'PERCENT' ? `-${res.discount_value}% Off` : `-€${res.discount_value} Off`;
  syncProductQrModalShareTargets(item, res.promo_url, lbl);
}

async function sharePitchWhatsApp(itemCode) {
  const all = getUnifiedPitchItems(STATE.sellerCatalog);
  const item = all.find((x) => String(x.code || x.id) === String(itemCode));
  if (!item) return;
  const code = String(item.code || item.id || '');
  let url = buildAttributedProductUrl(item);
  const adl = Math.max(1, Number(STATE.pitchPax || 2));
  const ch = Math.max(0, Number(STATE.pitchChildren || 0));
  const inf = Math.max(0, Number(STATE.pitchInfants || 0));
  const uPrice = getAdultChildUnitInCurr(item, STATE.pitchCurr);
  const dCalc = calcClientDiscountForTrip(item, adl, ch, STATE.pitchCurr, STATE.pitchDiscType, STATE.pitchDiscVal);
  if (dCalc.discInCurr > 0) {
    const pRes = await apiPost({
      action: 'create_promo_share_link',
      trip_code: code,
      lang: STATE.pitchLang || 'en',
      discount_type: dCalc.appliedType,
      discount_value: dCalc.appliedVal,
      discount_currency: STATE.pitchCurr
    });
    if (pRes && pRes.ok && pRes.promo_url) url = pRes.promo_url;
  }
  const totTxt = uPrice.curr === 'EGP' ? `${fmt(dCalc.netTotal, 0)} EGP` : `${uPrice.sym}${fmt(dCalc.netTotal, 0)} ${uPrice.curr}`;
  const promoNote = dCalc.discInCurr > 0 ? ` (${dCalc.badge} Special Offer!)` : '';
  const msg = `🌊 *Sea College Sharm El Sheikh — Personal Trip Proposal*\n`
    + `✨ *${item.name || item.name_en}*\n`
    + `👥 Group: ${adl} Adults${ch > 0 ? ` + ${ch} Kids` : ''}${inf > 0 ? ` + ${inf} Infants (FREE)` : ''} • Total: *${totTxt}*${promoNote}\n`
    + `🏨 Hotel: ${STATE.pitchHotel || 'Sharm Resort'} (Pickup: ${STATE.pitchPickup || '07:55–08:10'})\n`
    + `📲 Take your time & confirm your booking directly via my link:\n${url}`;
  window.open('https://wa.me/?text=' + encodeURIComponent(msg), '_blank', 'noopener');
}

let CURRENT_FBK_ITEM = null;
function setFastBookQuickDate(offsetDays) {
  const dt = new Date(Date.now() + Number(offsetDays || 0) * 86400000);
  const iso = dt.toISOString().slice(0, 10);
  const el = document.getElementById('fbkDate');
  if (el) el.value = iso;
}

function stepFastBookField(elId, delta, minVal = 0, resetDeposit = true) {
  const el = document.getElementById(elId);
  if (!el) return;
  const nxt = Math.max(minVal, Math.min(60, Number(el.value || 0) + delta));
  el.value = String(nxt);
  recalcFastBookGuestTotal(resetDeposit);
}

function setFastBookQuickDeposit(mode) {
  if (!CURRENT_FBK_ITEM) return;
  const dCalc = getFastBookComputedDiscount();
  const depEl = document.getElementById('fbkDepAmt');
  if (!depEl) return;
  if (mode === 'FULL') {
    depEl.value = String(dCalc.netTotal);
  } else {
    depEl.value = String(Math.min(dCalc.netTotal, Math.max(0, Number(mode || 0))));
  }
  recalcFastBookRestFromDeposit();
}

function setFastBookPresetDiscount(dType, dVal) {
  const tEl = document.getElementById('fbkDiscType');
  const vEl = document.getElementById('fbkDiscVal');
  if (tEl) tEl.value = dType;
  if (vEl) vEl.value = String(dVal);
  recalcFastBookGuestTotal(true);
}

function stepPitchPax(kind, delta) {
  if (kind === 'adl') STATE.pitchPax = Math.max(1, Math.min(40, Number(STATE.pitchPax || 2) + delta));
  if (kind === 'ch') STATE.pitchChildren = Math.max(0, Math.min(30, Number(STATE.pitchChildren || 0) + delta));
  if (kind === 'inf') STATE.pitchInfants = Math.max(0, Math.min(15, Number(STATE.pitchInfants || 0) + delta));
  renderApp();
}

function selectQuickPitchHotel(hName, pickWin) {
  STATE.pitchHotel = hName;
  if (pickWin) STATE.pitchPickup = pickWin;
  renderApp();
}

function isSafariPitchItem(item) {
  if (!item) return false;
  const cg = String(item.category_group || item.category_key || '').toLowerCase();
  const cd = String(item.code || item.id || '').toLowerCase();
  return (cg.includes('safari') || cd.includes('safari')) && !cd.includes('horse');
}

function getActiveSafariSpecFromState() {
  const sp = STATE.fbkSafariSpec || {};
  const out = {};
  let hasAny = false;
  Object.keys(sp).forEach((k) => {
    const q = Math.max(0, Math.floor(Number(sp[k] || 0)));
    if (q > 0) {
      out[k] = q;
      hasAny = true;
    }
  });
  return hasAny ? out : null;
}

function renderFastBookSafariBuilder() {
  const row = document.getElementById('fbkSafariBuilderRow');
  const grid = document.getElementById('fbkSafariBuilderGrid');
  if (!row || !grid) return;
  if (!isSafariPitchItem(CURRENT_FBK_ITEM)) {
    row.style.display = 'none';
    return;
  }
  row.style.display = 'block';
  const cat = STATE.sellerCatalog || {};
  const variants = cat.safari_variants || [];
  const ar = STATE.lang === 'ar';
  const icons = {
    single_quad: '🏍️',
    double_quad: '🏍️🏍️',
    buggy_2pax: '🏎️',
    buggy_4pax: '🚙',
    camel_safari: '🐪',
    camel_1hr: '🐪⏱️',
    star_gazing: '🔭',
    bedouin_dinner: '🍖',
    vip_bedouin_dinner: '👑',
    private_guide: '🧭'
  };
  grid.innerHTML = variants.map((v) => {
    const fld = String(v.spec_field || '');
    const qty = Math.max(0, Number((STATE.fbkSafariSpec || {})[fld] || 0));
    const lbl = ar
      ? String(v.name_ar || v.name_en).split('(')[0].trim()
      : String(v.name_en || v.item_code).split('(')[0].trim();
    return `
      <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;padding:6px 8px;border-radius:9px;background:${qty > 0 ? 'rgba(245,158,11,0.18)' : 'rgba(6,21,38,0.75)'};border:1px solid ${qty > 0 ? 'rgba(251,191,36,0.6)' : 'var(--border)'};">
        <div style="min-width:0;">
          <div style="font-size:11.5px;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${icons[fld] || '🏜️'} ${esc(lbl)}</div>
          <div style="font-size:10.5px;color:#7dd3fc;">€${fmt(v.unit_sell_eur, 0)}</div>
        </div>
        <div class="stepper-wrap" style="padding:1px 3px;">
          <button type="button" class="stepper-btn" style="width:26px;height:26px;font-size:14px;" onclick="stepFastBookSafari('${esc(fld)}', -1)">−</button>
          <span style="min-width:20px;text-align:center;font-size:12.5px;font-weight:900;">${qty}</span>
          <button type="button" class="stepper-btn" style="width:26px;height:26px;font-size:14px;" onclick="stepFastBookSafari('${esc(fld)}', 1)">+</button>
        </div>
      </div>
    `;
  }).join('');
}

function stepFastBookSafari(field, delta) {
  if (!STATE.fbkSafariSpec) STATE.fbkSafariSpec = {};
  const next = Math.max(0, Math.min(30, Number(STATE.fbkSafariSpec[field] || 0) + delta));
  STATE.fbkSafariSpec[field] = next;
  renderFastBookSafariBuilder();
  recalcFastBookGuestTotal(true);
}

function resetFastBookSafariSpec() {
  STATE.fbkSafariSpec = {};
  renderFastBookSafariBuilder();
  recalcFastBookGuestTotal(true);
}

function openFastBookModal(itemCode) {
  const cat = STATE.sellerCatalog || {};
  const all = getUnifiedPitchItems(cat);
  const item = all.find((x) => String(x.code || x.id) === String(itemCode)) || all[0];
  if (!item) return;
  CURRENT_FBK_ITEM = item;
  STATE.fbkSafariSpec = {};
  document.getElementById('fbkResultBox').style.display = 'none';
  document.getElementById('fbkTitle').textContent = `⚡ Guest Booking: ${item.name || item.name_en}`;
  document.getElementById('fbkProdCode').value = String(item.code || item.id);
  const hotels = cat.hotels || [];
  const hSel = document.getElementById('fbkHotel');
  hSel.innerHTML = hotels.map((h) => `<option value="${esc(h.name)}" data-pick="${esc(h.pickup_window)}" ${h.name === STATE.pitchHotel ? 'selected' : ''}>${esc(h.name)} (${esc(h.pickup_window)})</option>`).join('');
  document.getElementById('fbkPick').value = STATE.pitchPickup || '07:55–08:10';
  document.getElementById('fbkAdl').value = String(Math.max(1, Number(STATE.pitchPax || 2)));
  document.getElementById('fbkCh').value = String(Math.max(0, Number(STATE.pitchChildren || 0)));
  document.getElementById('fbkInf').value = String(Math.max(0, Number(STATE.pitchInfants || 0)));
  const cur = STATE.pitchCurr || 'EUR';
  document.getElementById('fbkDepCur').value = cur;
  document.getElementById('fbkRestCur').value = cur;

  const pol = getSellerDiscountPolicy();
  const dRow = document.getElementById('fbkDiscountRow');
  if (dRow) dRow.style.display = pol.is_enabled ? 'block' : 'none';
  const emEl = document.getElementById('fbkEmail');
  if (emEl) emEl.value = '';
  document.getElementById('fbkDiscType').value = pol.is_enabled ? (STATE.pitchDiscType || 'NONE') : 'NONE';
  document.getElementById('fbkDiscVal').value = pol.is_enabled && STATE.pitchDiscType !== 'NONE' ? String(STATE.pitchDiscVal || 0) : '0';
  document.getElementById('fbkDiscReason').value = '';

  renderFastBookSafariBuilder();
  recalcFastBookGuestTotal(true);
  document.getElementById('fastBookModal').style.display = 'flex';
}

function syncFastBookCurrency(newCur) {
  document.getElementById('fbkRestCur').value = newCur;
  recalcFastBookGuestTotal(true);
}

function getFastBookComputedDiscount() {
  const cur = document.getElementById('fbkDepCur').value || 'EUR';
  const adl = Math.max(1, Number(document.getElementById('fbkAdl').value || 1));
  const ch = Math.max(0, Number(document.getElementById('fbkCh').value || 0));
  const dType = document.getElementById('fbkDiscType') ? document.getElementById('fbkDiscType').value : 'NONE';
  const dVal = document.getElementById('fbkDiscVal') ? Number(document.getElementById('fbkDiscVal').value || 0) : 0;

  const activeSaf = getActiveSafariSpecFromState();
  if (activeSaf && isSafariPitchItem(CURRENT_FBK_ITEM)) {
    const variants = (STATE.sellerCatalog && STATE.sellerCatalog.safari_variants) || [];
    let safEur = 0;
    variants.forEach((v) => {
      const q = Number(activeSaf[v.spec_field] || 0);
      if (q > 0) safEur += q * Number(v.unit_sell_eur || 0);
    });
    if (safEur > 0) {
      const synthItem = Object.assign({}, CURRENT_FBK_ITEM, { price_eur: safEur, child_price_eur: 0 });
      return calcClientDiscountForTrip(synthItem, 1, 0, cur, dType, dVal);
    }
  }
  return calcClientDiscountForTrip(CURRENT_FBK_ITEM, adl, ch, cur, dType, dVal);
}

function recalcFastBookGuestTotal(resetDepositFull = false) {
  if (!CURRENT_FBK_ITEM) return;
  const dCalc = getFastBookComputedDiscount();
  const tot = dCalc.netTotal;
  if (resetDepositFull) {
    document.getElementById('fbkDepAmt').value = String(tot);
    document.getElementById('fbkRestAmt').value = '0';
  } else {
    recalcFastBookRestFromDeposit();
    return;
  }
  recalcFastBookSummaryBox();
}

function recalcFastBookRestFromDeposit() {
  if (!CURRENT_FBK_ITEM) return;
  const cur = document.getElementById('fbkDepCur').value || 'EUR';
  const dCalc = getFastBookComputedDiscount();
  const tot = dCalc.netTotal;
  const dep = Math.max(0, Number(document.getElementById('fbkDepAmt').value || 0));
  const rest = Math.max(0, Math.round((tot - dep) * 100) / 100);
  document.getElementById('fbkRestCur').value = cur;
  document.getElementById('fbkRestAmt').value = String(rest);
  recalcFastBookSummaryBox();
}

function recalcFastBookSummaryBox() {
  if (!CURRENT_FBK_ITEM) return;
  const cur = document.getElementById('fbkDepCur').value || 'EUR';
  const restCur = document.getElementById('fbkRestCur').value || cur;
  const u = getAdultChildUnitInCurr(CURRENT_FBK_ITEM, cur);
  const adl = Math.max(1, Number(document.getElementById('fbkAdl').value || 1));
  const ch = Math.max(0, Number(document.getElementById('fbkCh').value || 0));
  const inf = Math.max(0, Number(document.getElementById('fbkInf').value || 0));
  const dCalc = getFastBookComputedDiscount();
  const dep = Math.max(0, Number(document.getElementById('fbkDepAmt').value || 0));
  const rest = Math.max(0, Number(document.getElementById('fbkRestAmt').value || 0));
  const activeSaf = getActiveSafariSpecFromState();
  let safLine = '';
  if (activeSaf && isSafariPitchItem(CURRENT_FBK_ITEM)) {
    const variants = (STATE.sellerCatalog && STATE.sellerCatalog.safari_variants) || [];
    const parts = [];
    variants.forEach((v) => {
      const q = Number(activeSaf[v.spec_field] || 0);
      if (q > 0) parts.push(`${q}× ${String(v.name_en || v.item_code).split('(')[0].trim()}`);
    });
    if (parts.length) safLine = `<div style="color:#fde68a;font-weight:800;">🏜️ Safari Selection: ${esc(parts.join(' + '))}</div>`;
  }
  const priceLine = dCalc.discInCurr > 0
    ? ` = <s style="opacity:0.65;">${u.sym}${fmt(dCalc.listTotal, 1)}</s> <b class="pos">${u.sym}${fmt(dCalc.netTotal, 1)} ${esc(cur)}</b> <span style="color:#fcd34d;font-weight:800;">(${esc(dCalc.badge)} Special Offer!)</span>`
    : ` = <b>${u.sym}${fmt(dCalc.netTotal, 1)} ${esc(cur)}</b>`;
  document.getElementById('fbkGuestSummary').innerHTML =
    safLine
    + `🌊 <b>Trip Summary:</b> ${adl} Adult` + (!activeSaf ? ` (${u.sym}${fmt(u.adult, 0)})` : '')
    + (ch > 0 ? ` + ${ch} Child` + (!activeSaf ? ` (${u.sym}${fmt(u.child, 0)})` : '') : '')
    + ` + ${inf} Infant (<span class="pos">FREE &lt;3 yrs</span>)`
    + `${priceLine}<br>`
    + `✅ Deposit Paid Now: <b class="pos">${fmt(dep, 1)} ${esc(cur)}</b> • Remaining at Pickup Time: <b style="color:${rest > 0 ? '#fcd34d' : '#34d399'};">${fmt(rest, 1)} ${esc(restCur)}</b>`;
}

async function submitFastBookForm(e) {
  e.preventDefault();
  const btn = document.getElementById('fbkSubmitBtn');
  btn.disabled = true;
  btn.textContent = '⏳ Issuing Boarding Pass...';
  try {
    const dCalc = getFastBookComputedDiscount();
    const cur = document.getElementById('fbkDepCur').value || 'EUR';
    const guestName = document.getElementById('fbkGuest').value.trim();
    const guestPhone = document.getElementById('fbkPhone').value.trim();
    const guestEmail = document.getElementById('fbkEmail') ? document.getElementById('fbkEmail').value.trim() : '';
    const tripDate = document.getElementById('fbkDate').value;
    const hotelName = document.getElementById('fbkHotel').value;
    const roomNo = document.getElementById('fbkRoom').value.trim();
    const pickWin = document.getElementById('fbkPick').value.trim();
    const depNow = Number(document.getElementById('fbkDepAmt').value || 0);
    const restLater = Number(document.getElementById('fbkRestAmt').value || 0);
    const restCurr = document.getElementById('fbkRestCur').value;
    const activeSaf = isSafariPitchItem(CURRENT_FBK_ITEM) ? getActiveSafariSpecFromState() : null;
    const payload = {
      action: 'create_voucher',
      guest_name: guestName,
      guest_phone: guestPhone,
      guest_email: guestEmail,
      lang: STATE.pitchLang || 'en',
      trip_date: tripDate,
      nationality: document.getElementById('fbkNat').value.trim(),
      hotel_name: hotelName,
      room_no: roomNo,
      pickup_time: pickWin,
      product_code: document.getElementById('fbkProdCode').value,
      adults: Number(document.getElementById('fbkAdl').value || 1),
      children: Number(document.getElementById('fbkCh').value || 0),
      infants: Number(document.getElementById('fbkInf').value || 0),
      discount_type: dCalc.appliedType,
      discount_value: dCalc.appliedVal,
      discount_currency: cur,
      discount_reason: document.getElementById('fbkDiscReason') ? document.getElementById('fbkDiscReason').value.trim() : '',
      deposit_amount: depNow,
      deposit_currency: cur,
      rest_amount: restLater,
      rest_currency: restCurr
    };
    if (activeSaf) {
      payload.safari_spec = activeSaf;
    }
    const res = await apiPost(payload);
    if (!res.ok) {
      alert(res.error || 'Could not create voucher');
      return;
    }
    const rBox = document.getElementById('fbkResultBox');
    const ga = res.guest_account || {};
    const qrSvg = (window.SCQR && typeof window.SCQR.toSvg === 'function')
      ? `<div class="qr-box" style="margin:8px auto;">${window.SCQR.toSvg(String(res.qr_token || res.voucher_no), { size: 150 })}</div>`
      : '';
    const waDigitsGuest = waDigits(guestPhone);
    const passMsg = `🌊 *Sea College Sharm El Sheikh — Official Boarding Voucher*\n`
      + `🎫 Voucher: *${res.voucher_no}* (${res.qr_token})\n`
      + `✨ Trip: *${(CURRENT_FBK_ITEM && (CURRENT_FBK_ITEM.name || CURRENT_FBK_ITEM.name_en)) || 'Sharm Trip'}*\n`
      + `📅 Date: *${tripDate}* • 🚐 Pickup: *${pickWin}* (${hotelName}${roomNo ? ' #' + roomNo : ''})\n`
      + `💵 Paid Deposit: *${fmt(depNow, 1)} ${cur}* • Balance at Pickup: *${fmt(restLater, 1)} ${restCurr}*`
      + (ga.guest_username && ga.initial_password ? `\n\n🔐 *Your VIP Club App Account (Book Future Trips):*\n🔗 https://seacollegediveclub.com/app\n👤 Login: *${ga.guest_email || ga.guest_username}*\n🔑 Password: *${ga.initial_password}*` : '');
    const waHref = `https://wa.me/${waDigitsGuest.length >= 8 ? waDigitsGuest : ''}?text=${encodeURIComponent(passMsg)}`;
    rBox.style.display = 'block';
    rBox.innerHTML = `
      <div style="font-size:15px;font-weight:900;color:var(--teal);">✅ Booking Confirmed — Voucher ${esc(res.voucher_no)}</div>
      <div style="font-size:12px;margin-top:4px;">Pickup Window: <b>${esc(pickWin)}</b> • Guests: <b>${Number(res.adults || 1)} Adults${Number(res.children || 0) > 0 ? ` + ${res.children} Kids` : ''}${Number(res.infants || 0) > 0 ? ` + ${res.infants} Infants` : ''}</b>${Number(res.discount_egp || 0) > 0 ? ` • <span style="color:#fcd34d;">🎁 Special Discount Applied</span>` : ''}</div>
      ${ga.guest_username ? `
        <div style="margin-top:7px;padding:7px 10px;border-radius:9px;background:rgba(0,210,255,0.1);border:1px solid rgba(0,210,255,0.35);font-size:11.5px;color:#e0f2fe;">
          🔐 <b>Verified Guest Account Active:</b> <code>@${esc(ga.guest_username)}</code>
          ${ga.email_sent
            ? ` • <span style="color:#34d399;font-weight:800;">📧 Login Emailed to ${esc(ga.guest_email)}!</span>`
            : ` • <button type="button" class="btn" style="padding:2px 8px;font-size:11px;margin-left:4px;" onclick="promptAndSendGuestLoginEmail('${esc(res.voucher_no)}', '${esc(ga.guest_username)}', '')">📧 Add Email &amp; Send Login</button>`}
        </div>
      ` : ''}
      ${qrSvg}
      <div style="font-size:11px;color:var(--muted);margin-bottom:8px;">Digital Boarding Pass: ${esc(res.qr_token)}</div>
      <a href="${esc(waHref)}" target="_blank" rel="noopener" class="btn btn-emerald" style="justify-content:center;padding:10px 16px;font-size:13.5px;font-weight:900;">💬 Send Boarding Pass &amp; VIP Login on WhatsApp</a>
    `;
    showToast(`✅ Booking ${res.voucher_no} Confirmed${ga.email_sent ? ' + Login Email Sent!' : '!'}`);
    STATE.sellerWallet = null;
    const wp = { action: 'seller_wallet', month: STATE.walletMonth };
    if (STATE.walletSeller) wp.seller = STATE.walletSeller;
    apiGet(wp).then((w) => { if (w && w.ok) STATE.sellerWallet = w; });
    apiGet({ action: 'role_workspace' }).then((w) => { if (w && w.ok) STATE.ws = w; });
    if (hasPerm('crm.manage_leads_ads', 'bi.view_executive')) {
      apiGet({ action: 'marketing_guest_accounts', filter: STATE.guestCrmFilter || 'all' }).then((g) => { if (g && g.ok) STATE.guestCrm = g; });
    }
  } finally {
    btn.disabled = false;
    btn.textContent = '🎫 تأكيد الحجز وإصدار الباركود • Confirm Booking';
  }
}

function setPitchDiscount(dType, dVal) {
  STATE.pitchDiscType = dType;
  STATE.pitchDiscVal = Math.max(0, Number(dVal || 0));
  renderApp();
}

function renderSellerPitchTab(ar) {
  const cat = STATE.sellerCatalog || {};
  const hotels = cat.hotels || [];
  const langs = cat.languages || ['en', 'ru', 'it', 'pl', 'de', 'fr', 'ar', 'zh'];
  const langLabels = { en: '🇬🇧 EN', ru: '🇷🇺 RU', it: '🇮🇹 IT', pl: '🇵🇱 PL', de: '🇩🇪 DE', fr: '🇫🇷 FR', ar: '🇪🇬 AR', zh: '🇨🇳 ZH' };
  const discPol = getSellerDiscountPolicy();

  const selectedHotelObj = hotels.find((h) => h.name === STATE.pitchHotel) || hotels[0] || { name: 'Cyrene Sharm Hotel', pickup_window: '07:55–08:10', zone_label: 'Montazah / Ras Nasrani' };
  STATE.pitchPickup = selectedHotelObj.pickup_window || '07:55–08:10';

  const combined = getUnifiedPitchItems(cat);

  const catTiles = [
    ['ALL', '🌟', ar ? `الكل (${combined.length})` : `All (${combined.length})`],
    ['snorkeling', '🏝️', ar ? 'مراكب ووايت' : 'Boat & White'],
    ['desert-safari', '🏜️', ar ? 'سفاري وباجي' : 'Quad Safari'],
    ['intro-diving', '🤿', ar ? 'غطس مبتدئ' : 'Intro Dive'],
    ['cairo-luxor', '✈️', ar ? 'القاهرة والأقصر' : 'Cairo & Luxor'],
    ['sinai-dahab', '⛰️', ar ? 'كاترين ودهب' : 'Moses & Dahab'],
    ['marine-family', '🐬', ar ? 'دولفين وغواصة' : 'Dolphin & Sub'],
    ['watersports', '🪂', ar ? 'ألعاب مائية' : 'Watersports'],
    ['daily-diving', '🚤', ar ? 'غطس محترفين' : 'Pro Dives'],
    ['padi-courses', '🎓', ar ? 'كورسات PADI' : 'PADI Courses'],
    ['equipment', '🤿', ar ? 'معدات الغوص' : 'Dive Gear']
  ];

  const qLow = String(STATE.pitchQuery || '').trim().toLowerCase();
  const filtered = combined.filter((it) => {
    const itemCat = String(it.category || it.category_group || it.category_key || '');
    if (STATE.pitchCat !== 'ALL' && itemCat !== STATE.pitchCat) return false;
    if (!qLow) return true;
    const hay = `${it.name || ''} ${it.name_en || ''} ${it.name_ar || ''} ${it.summary || ''} ${it.category_label || ''} ${it.code || ''}`.toLowerCase();
    return hay.includes(qLow);
  });

  const slug = getSellerReferralSlug();
  const brochureUrl = 'https://seacollegediveclub.com' + (STATE.pitchLang && STATE.pitchLang !== 'en' ? '/' + encodeURIComponent(STATE.pitchLang) : '') + '/s/' + encodeURIComponent(slug);
  const adlCount = Math.max(1, Number(STATE.pitchPax || 2));
  const chCount = Math.max(0, Number(STATE.pitchChildren || 0));
  const infCount = Math.max(0, Number(STATE.pitchInfants || 0));

  const quickHotels = hotels.filter((h) => /cyrene|dreams|fayrouz|jaz|sunrise|rixos/i.test(String(h.name || ''))).slice(0, 5);
  const hQueryLow = String(STATE.pitchHotelSearch || '').trim().toLowerCase();
  const visibleHotels = hQueryLow
    ? hotels.filter((h) => String(h.name || '').toLowerCase().includes(hQueryLow))
    : hotels;

  return `
    <!-- ZERO-SCROLL COMPACT CONCIERGE BAR (Trips Appear Immediately Above the Fold!) -->
    <div class="card" style="padding:11px 13px;margin-bottom:10px;background:linear-gradient(135deg,rgba(0,210,255,0.14),rgba(16,185,129,0.09));">
      <!-- Row 1: Guest Language Flags + Currency + 1-Tap WhatsApp Brochure -->
      <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
        <div class="seg-group" style="gap:2px;">
          ${langs.map((lg) => `<button type="button" class="seg-btn ${STATE.pitchLang === lg ? 'active' : ''}" style="padding:5px 8px;font-size:11.5px;" onclick="changePitchLang('${esc(lg)}')">${langLabels[lg] || esc(lg.toUpperCase())}</button>`).join('')}
        </div>
        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
          <div class="seg-group">
            ${['EUR', 'USD', 'GBP', 'EGP'].map((c) => `<button type="button" class="seg-btn ${STATE.pitchCurr === c ? 'active' : ''}" style="padding:5px 9px;font-size:11.5px;" onclick="STATE.pitchCurr='${c}';renderApp();">${c}</button>`).join('')}
          </div>
          <a class="btn btn-emerald" style="padding:6px 11px;" href="https://wa.me/?text=${encodeURIComponent('🌴 Explore all Sharm El Sheikh Boat, Diving, Safari & Cairo Trips and confirm your booking anytime with me: ' + brochureUrl)}" target="_blank" rel="noopener">💬 ${ar ? 'إرسال الكتالوج واتساب' : 'WhatsApp All Trips'}</a>
        </div>
      </div>

      <!-- Row 2: 1-Tap Summary Pills + Special Discount Presets + Expandable Hotel/Guest Touch Drawer -->
      <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;">
        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
          <button type="button" class="btn ${STATE.pitchDrawerOpen ? 'btn-primary' : ''}" style="padding:6px 11px;font-size:12px;" onclick="STATE.pitchDrawerOpen=!STATE.pitchDrawerOpen;renderApp();">
            🏨 ${esc(selectedHotelObj.name)} (🕒 ${esc(STATE.pitchPickup)}) • 👨‍👩‍👧 ${adlCount}A${chCount > 0 ? `+${chCount}C` : ''}${infCount > 0 ? `+${infCount}I` : ''} ▾
          </button>
          <div class="stepper-wrap">
            <button type="button" class="stepper-btn" style="width:30px;height:30px;font-size:16px;" onclick="stepPitchPax('adl', -1)">−</button>
            <span style="font-size:12px;font-weight:900;padding:0 4px;">👤 ${adlCount}</span>
            <button type="button" class="stepper-btn" style="width:30px;height:30px;font-size:16px;" onclick="stepPitchPax('adl', 1)">+</button>
          </div>
          <div class="stepper-wrap">
            <button type="button" class="stepper-btn" style="width:30px;height:30px;font-size:16px;" onclick="stepPitchPax('ch', -1)">−</button>
            <span style="font-size:12px;font-weight:900;padding:0 4px;">🧒 ${chCount}</span>
            <button type="button" class="stepper-btn" style="width:30px;height:30px;font-size:16px;" onclick="stepPitchPax('ch', 1)">+</button>
          </div>
        </div>

        ${discPol.is_enabled ? `
          <div id="pitchSpecialDiscountBar" style="display:flex;gap:5px;align-items:center;flex-wrap:wrap;">
            <span style="font-size:11.5px;font-weight:800;color:#fde68a;">🎁 ${ar ? 'خصم سريع:' : 'Offer:'}</span>
            <button type="button" class="btn ${STATE.pitchDiscType === 'NONE' ? 'btn-primary' : ''}" style="padding:4px 9px;font-size:11.5px;" onclick="setPitchDiscount('NONE', 0)">${ar ? 'عادي' : '0%'}</button>
            <button type="button" class="btn ${STATE.pitchDiscType === 'PERCENT' && Number(STATE.pitchDiscVal) === 5 ? 'btn-amber' : ''}" style="padding:4px 9px;font-size:11.5px;" onclick="setPitchDiscount('PERCENT', 5)">🎁 -5%</button>
            <button type="button" class="btn ${STATE.pitchDiscType === 'PERCENT' && Number(STATE.pitchDiscVal) === 10 ? 'btn-amber' : ''}" style="padding:4px 9px;font-size:11.5px;" onclick="setPitchDiscount('PERCENT', 10)">🎁 -10%</button>
            <button type="button" class="btn ${STATE.pitchDiscType === 'PERCENT' && Number(STATE.pitchDiscVal) === 15 ? 'btn-amber' : ''}" style="padding:4px 9px;font-size:11.5px;" onclick="setPitchDiscount('PERCENT', 15)">🎁 -15%</button>
          </div>
        ` : ''}
      </div>

      ${STATE.pitchDrawerOpen ? `
        <div style="margin-top:10px;padding:11px;border-radius:12px;background:rgba(6,21,38,0.88);border:1px solid var(--border);display:grid;gap:10px;">
          <div>
            <label style="font-weight:800;color:var(--cyan);">🏨 ${ar ? 'اختر الفندق بلمسة أو من القائمة (72 فندق):' : 'Quick Select Hotel & Pickup Window (72 Resorts):'}</label>
            <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:7px;">
              ${quickHotels.map((qh) => `<button type="button" class="btn ${qh.name === selectedHotelObj.name ? 'btn-primary' : ''}" style="padding:4px 9px;font-size:11px;" onclick="selectQuickPitchHotel('${esc(qh.name)}', '${esc(qh.pickup_window)}')">🏨 ${esc(qh.name)} (${esc(qh.pickup_window)})</button>`).join('')}
            </div>
            <div style="display:grid;grid-template-columns:1fr 1.4fr;gap:7px;">
              <input type="search" placeholder="${ar ? 'ابحث باسم الفندق...' : 'Filter hotel name...'}" value="${esc(STATE.pitchHotelSearch || '')}" oninput="STATE.pitchHotelSearch=this.value;renderApp();">
              <select onchange="STATE.pitchHotel=this.value;const o=this.selectedOptions[0];if(o&&o.dataset.pick)STATE.pitchPickup=o.dataset.pick;renderApp();">
                ${(visibleHotels.length ? visibleHotels : hotels).map((h) => `<option value="${esc(h.name)}" data-pick="${esc(h.pickup_window)}" ${h.name === selectedHotelObj.name ? 'selected' : ''}>${esc(h.name)} (${esc(h.pickup_window)})</option>`).join('')}
              </select>
            </div>
          </div>

          <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
              <span style="font-size:12px;font-weight:800;color:var(--teal);">👶 ${ar ? 'رضع تحت 3 سنوات (مجاناً):' : 'Infants <3 yrs (FREE):'}</span>
              <div class="stepper-wrap">
                <button type="button" class="stepper-btn" style="width:32px;height:32px;" onclick="stepPitchPax('inf', -1)">−</button>
                <span style="font-size:13px;font-weight:900;padding:0 6px;">${infCount}</span>
                <button type="button" class="stepper-btn" style="width:32px;height:32px;" onclick="stepPitchPax('inf', 1)">+</button>
              </div>
            </div>

            ${discPol.is_enabled ? `
              <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                <span style="font-size:11.5px;color:#fde68a;font-weight:800;">🎁 ${ar ? 'خصم مخصص (% أو مبلغ):' : 'Custom Discount:'}</span>
                <select style="width:auto;padding:5px 8px;" onchange="STATE.pitchDiscType=this.value;if(this.value==='NONE')STATE.pitchDiscVal=0;else if(!STATE.pitchDiscVal)STATE.pitchDiscVal=10;renderApp();">
                  <option value="NONE" ${STATE.pitchDiscType === 'NONE' ? 'selected' : ''}>Standard</option>
                  <option value="PERCENT" ${STATE.pitchDiscType === 'PERCENT' ? 'selected' : ''}>% (Max ${discPol.max_discount_pct}%)</option>
                  <option value="AMOUNT" ${STATE.pitchDiscType === 'AMOUNT' ? 'selected' : ''}>Fixed (${esc(STATE.pitchCurr)})</option>
                </select>
                ${STATE.pitchDiscType !== 'NONE' ? `<input type="number" step="0.5" min="0" value="${Number(STATE.pitchDiscVal || 0)}" style="width:80px;padding:5px;" onchange="STATE.pitchDiscVal=Math.max(0,Number(this.value||0));renderApp();">` : ''}
              </div>
            ` : ''}
          </div>
        </div>
      ` : ''}
    </div>

    <!-- Single-Row Swipeable Big Visual Category Tiles + Instant Search -->
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px;">
      <div class="easy-cat-strip" style="flex:1;min-width:240px;">
        ${catTiles.map(([ck, ico, cl]) => `
          <button type="button" class="easy-cat-tile ${STATE.pitchCat === ck ? 'active' : ''}" onclick="STATE.pitchCat='${ck}';renderApp();">
            <span class="ico">${ico}</span>
            <span>${esc(cl)}</span>
          </button>
        `).join('')}
      </div>
      <input type="search" style="max-width:240px;padding:9px 11px;" placeholder="${ar ? '🔍 ابحث سريعاً: وايت، سفاري، القاهرة...' : '🔍 Quick search trip...'}" value="${esc(STATE.pitchQuery)}" oninput="STATE.pitchQuery=this.value;renderApp();">
    </div>

    <div class="pitch-grid">
      ${filtered.slice(0, 120).map((it) => {
        const code = String(it.code || it.id || '');
        const isGear = /^(eq|ins)-/.test(code);
        const info = it.infographics || {};
        const uPrice = getAdultChildUnitInCurr(it, STATE.pitchCurr);
        const unitCurrTxt = formatPitchPrice(it);
        const childCurrTxt = uPrice.curr === 'EGP' ? `${fmt(uPrice.child, 0)} EGP` : `${uPrice.sym}${fmt(uPrice.child, 0)}`;
        const dCalc = calcClientDiscountForTrip(it, adlCount, isGear ? 0 : chCount, STATE.pitchCurr, STATE.pitchDiscType, STATE.pitchDiscVal);
        const listGroupTxt = uPrice.curr === 'EGP' ? `${fmt(dCalc.listTotal, 0)} EGP` : `${uPrice.sym}${fmt(dCalc.listTotal, 0)} ${uPrice.curr}`;
        const netGroupTxt = uPrice.curr === 'EGP' ? `${fmt(dCalc.netTotal, 0)} EGP` : `${uPrice.sym}${fmt(dCalc.netTotal, 0)} ${uPrice.curr}`;
        const coverImg = it.cover_webp || '/assets/images/pitch/ras-mohammed-boat.webp';
        const vidSrc = it.video_src || '/assets/videos/sea-college-tiran-dive-briefing-sharm.mp4';
        const vidPoster = it.video_poster || coverImg;
        return `
          <article class="pitch-card">
            <div>
              <div class="pitch-media">
                <img src="${esc(coverImg)}" alt="${esc(it.name || it.name_en)}" loading="lazy" onerror="this.onerror=null;this.src='/assets/images/pitch/ras-mohammed-boat.webp';">
                <span class="pitch-cat-tag">${esc(it.category_label || 'Sharm Experience')}</span>
                <span class="pitch-price-tag">${esc(unitCurrTxt)}</span>
              </div>
              <div style="padding:12px 13px;">
                <div style="font-size:15.5px;font-weight:900;line-height:1.3;">${esc(it.name || it.name_en)}</div>
                <p style="font-size:11.5px;color:var(--muted);margin-top:4px;line-height:1.42;">${esc(String(it.summary || '').slice(0, 120))}</p>

                <div class="info-badges">
                  ${isGear ? `
                    <span class="info-badge">🔧 Rate: ${esc(unitCurrTxt)}</span>
                    <span class="info-badge" style="color:#34d399;">✅ Sanitised &amp; Tested S.C Gear</span>
                    <span class="info-badge">⏱ ${esc(it.duration || 'Per Dive Day')}</span>
                    <span class="info-badge">🤿 ${esc(info.max_depth || 'Pro Scuba Rental')}</span>
                    <span class="info-badge">🚤 Ready on Boat / Dive Center</span>
                  ` : `
                    <span class="info-badge">👤 Adult: ${esc(unitCurrTxt)}</span>
                    <span class="info-badge">🧒 Child: ${esc(childCurrTxt)}</span>
                    <span class="info-badge" style="color:#34d399;">👶 Infant &lt;3: FREE</span>
                    <span class="info-badge">⏱ Duration: ${esc(it.duration || 'Full Day')}</span>
                    <span class="info-badge">🌊 Depth: ${esc(info.max_depth || 'Surface / Guided')}</span>
                    <span class="info-badge">🎂 Age: ${esc(info.min_age || 'All Ages')}</span>
                    <span class="info-badge">🚐 Pickup: ${esc(STATE.pitchPickup)}</span>
                  `}
                </div>

                <details style="margin-top:6px;border-radius:9px;background:rgba(6,21,38,0.72);border:1px solid var(--border);padding:6px 9px;">
                  <summary style="cursor:pointer;font-size:11.5px;font-weight:800;color:var(--cyan);">🎬 ${ar ? 'شاهد فيديو الرحلة' : 'Watch Video Preview'}</summary>
                  <video controls preload="none" playsinline poster="${esc(vidPoster)}" src="${esc(vidSrc)}" style="width:100%;max-height:240px;border-radius:8px;margin-top:8px;background:#000;"></video>
                </details>
              </div>
            </div>

            <div style="padding:10px 13px 13px;border-top:1px solid rgba(148,163,184,0.14);background:rgba(6,21,38,0.5);">
              <div style="display:flex;justify-content:space-between;align-items:center;font-size:12.5px;margin-bottom:8px;gap:6px;flex-wrap:wrap;">
                <span>${isGear ? `Total (${adlCount} ${adlCount === 1 ? 'Diver' : 'Divers'}):` : `Group (${adlCount}A${chCount > 0 ? `+${chCount}C` : ''}${infCount > 0 ? `+${infCount}I` : ''}):`}</span>
                ${dCalc.discInCurr > 0
                  ? `<span><s style="opacity:0.65;font-size:11.5px;margin-right:4px;">${esc(listGroupTxt)}</s><b style="font-size:15px;color:#34d399;">${esc(netGroupTxt)}</b> <span style="font-size:11px;color:#fcd34d;font-weight:800;">(${esc(dCalc.badge)})</span></span>`
                  : `<b style="font-size:15px;color:var(--cyan);">${esc(netGroupTxt)}</b>`
                }
              </div>
              <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <button type="button" class="btn btn-primary" style="flex:1.5;justify-content:center;padding:10px 12px;font-size:13.5px;font-weight:900;" onclick="openFastBookModal('${esc(code)}')">⚡ ${ar ? 'احجز الآن' : 'Book Now'}</button>
                <button type="button" class="btn" style="padding:10px 11px;" onclick="openProductQrModal('${esc(code)}')">📱 QR</button>
                <button type="button" class="btn btn-emerald" style="padding:10px 11px;" onclick="sharePitchWhatsApp('${esc(code)}')">💬 ${ar ? 'واتساب' : 'WhatsApp'}</button>
              </div>
            </div>
          </article>
        `;
      }).join('')}
    </div>
  `;
}

/* ================= F13: PRIVATE GAMIFIED SELLER PROFILE, QR, ACTUAL COMMISSIONS & ALLOWANCES ================= */
async function changeWalletFilter(monthVal, sellerVal) {
  if (monthVal !== null) STATE.walletMonth = monthVal;
  if (sellerVal !== null) {
    STATE.walletSeller = sellerVal;
    STATE.walletPromoUrl = '';
  }
  const wp = { action: 'seller_wallet', month: STATE.walletMonth };
  if (STATE.walletSeller) wp.seller = STATE.walletSeller;
  const res = await apiGet(wp);
  if (res && res.ok) {
    STATE.sellerWallet = res;
    renderApp();
  }
}

async function generateWalletPromoShareLink() {
  const w = STATE.sellerWallet || {};
  const prof = w.seller_profile || {};
  const dType = document.getElementById('walPromoType') ? document.getElementById('walPromoType').value : 'PERCENT';
  const dVal = document.getElementById('walPromoVal') ? Math.max(0, Number(document.getElementById('walPromoVal').value || 0)) : 0;
  STATE.walletPromoType = dType;
  STATE.walletPromoVal = dVal;
  const res = await apiPost({
    action: 'create_promo_share_link',
    seller_username: prof.username || '',
    trip_code: '',
    lang: STATE.lang === 'ar' ? 'ar' : 'en',
    discount_type: dType,
    discount_value: dVal,
    discount_currency: 'EUR'
  });
  if (!res || !res.ok) {
    alert((res && res.error) || 'Could not generate promo share link');
    return;
  }
  STATE.walletPromoVal = Number(res.discount_value || 0);
  STATE.walletPromoUrl = String(res.promo_url || '');
  showToast(res.promo_token ? '🎁 Signed VIP Promo Brochure Link & QR Ready!' : 'Reset to standard brochure link');
  renderApp();
}

function downloadSellerQrPng(qrUrl, slug) {
  if (!window.SCQR || typeof window.SCQR.toDataUrl !== 'function') {
    showToast('QR generator ready');
    return;
  }
  const dataUrl = window.SCQR.toDataUrl(String(qrUrl), { size: 512 });
  const a = document.createElement('a');
  a.href = dataUrl;
  a.download = `SeaCollege_Seller_QR_${slug || 'badge'}.png`;
  document.body.appendChild(a);
  a.click();
  a.remove();
  showToast('Downloaded printable PNG QR badge!');
}

function openAllowanceModal() {
  const w = STATE.sellerWallet || {};
  const brk = w.actual_due_breakdown || {};
  const netNow = Number(brk.net_due_now_egp ?? 0);
  document.getElementById('alwAvailableHint').innerHTML =
    `✅ Your Actual Due Commission (Completed &amp; 100% Paid Trips) minus past allowances = <b class="pos">${fmt(netNow)} EGP</b> available right now.`;
  document.getElementById('alwAmount').value = netNow > 500 ? String(Math.min(2000, Math.floor(netNow / 100) * 100)) : '500';
  document.getElementById('alwNotes').value = '';
  document.getElementById('allowanceModal').style.display = 'flex';
}

async function submitAllowanceRequest(e) {
  e.preventDefault();
  const amt = Number(document.getElementById('alwAmount').value || 0);
  const notes = document.getElementById('alwNotes').value.trim();
  const res = await apiPost({
    action: 'request_commission_advance',
    amount_egp: amt,
    month: STATE.walletMonth === 'ALL' ? '2026-10' : STATE.walletMonth,
    notes
  });
  if (!res || !res.ok) {
    alert((res && res.error) || 'Could not submit allowance request');
    return;
  }
  document.getElementById('allowanceModal').style.display = 'none';
  showToast(`💸 Requested ${fmt(amt)} EGP mid-month allowance! Sent to Accounting.`);
  await changeWalletFilter(STATE.walletMonth, STATE.walletSeller || null);
}

function renderSellerWalletTab(ar) {
  const w = STATE.sellerWallet || {};
  const prof = w.seller_profile || {};
  const k = w.kpis || {};
  const brk = w.actual_due_breakdown || {};
  const tgt = w.target_progress || { is_active: false };
  const discPol = w.discount_policy || { is_enabled: true, max_discount_pct: 20, max_discount_eur: 50 };
  const discSum = w.discounts_summary || { discounted_trips_count: 0, total_discount_egp: 0 };
  const myAwards = w.awards || [];
  const myTrips = w.my_trips || [];
  const advRows = w.advances_rows || [];
  const rows = w.ledger_rows || [];
  const leads = w.attributed_leads || [];
  const allSellers = w.all_sellers || [];
  const slug = prof.referral_code || getSellerReferralSlug();
  const qrUrl = prof.qr_url || ('https://seacollegediveclub.com/r/' + encodeURIComponent(slug));
  const baseBrochureUrl = prof.share_brochure_url || ('https://seacollegediveclub.com/s/' + encodeURIComponent(slug));
  const activeShareBrochure = STATE.walletPromoUrl || baseBrochureUrl;
  const qrTargetUrl = STATE.walletPromoUrl || qrUrl;
  const locUrls = prof.localized_brochure_urls || prof.localized_urls || { en: baseBrochureUrl };
  const svgQr = (window.SCQR && typeof window.SCQR.toSvg === 'function')
    ? window.SCQR.toSvg(qrTargetUrl, { size: 196 })
    : `<div style="color:#041322;padding:14px;font-weight:800;">${esc(qrTargetUrl)}</div>`;

  const expParams = new URLSearchParams({
    report: 'seller_commissions',
    format: 'xlsx',
    month: w.month || STATE.walletMonth || '2026-10'
  });
  if (prof.username) expParams.set('seller', prof.username);
  const walletExportHref = '/handlers/erp_export.php?' + expParams.toString();

  const monthsOpts = ['2026-10', 'ALL', '2026-11', '2026-09', '2026-08', '2026-07', '2026-06', '2026-05'];

  const actualDueComm = Number(brk.actual_done_paid_commission_egp ?? (Number(k.earned_commission_egp || 0) + Number(k.paid_commission_egp || 0)));
  const settledAndAllowances = Number(brk.settled_and_allowances_egp ?? Number(k.payroll_deductions_egp || 0));
  const netDueNow = Number(brk.net_due_now_egp ?? Math.max(0, actualDueComm - settledAndAllowances));
  const waitingRestOrTrip = Number(brk.waiting_rest_or_trip_comm_egp ?? Number(k.pending_commission_egp || 0));
  const urgentConfirms = myTrips.filter((t) => String(t.workflow_status || '') === 'AWAITING_SELLER_CONFIRM');

  return `
    ${urgentConfirms.length ? `
      <div class="card" style="margin-bottom:12px;border-color:#f59e0b;background:rgba(245,158,11,0.14);">
        <div style="font-size:14px;font-weight:900;color:#fde68a;margin-bottom:8px;">🔔 ${ar ? `لديك (${urgentConfirms.length}) حجز جديد من الرابط يحتاج ضغطة تأكيد الآن!` : `${urgentConfirms.length} New Client Link Booking(s) Need Your 1-Tap Confirm!`}</div>
        <div style="display:grid;gap:8px;">
          ${urgentConfirms.map((ut) => `
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;padding:9px 11px;border-radius:10px;background:rgba(6,21,38,0.8);border:1px solid var(--border);">
              <div><b>${esc(ut.voucher_no)}</b> • ${esc(ut.guest_name)} — ${esc(ut.trip_name)} (${esc(ut.trip_date)})</div>
              <button type="button" class="btn btn-emerald" style="padding:8px 14px;font-size:13px;font-weight:900;" onclick="sellerConfirmBooking(${Number(ut.id)})">✅ ${ar ? 'تأكيد الحجز الآن' : 'Confirm Now'}</button>
            </div>
          `).join('')}
        </div>
      </div>
    ` : ''}

    <div class="horizon-bar" style="margin-bottom:12px;">
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <span style="font-weight:900;color:var(--cyan);">💰 ${ar ? 'عمولتي وباركود QR والجوائز' : 'My Cash Wallet, QR & Awards'}</span>
        ${allSellers.length ? `
          <select style="width:auto;" onchange="changeWalletFilter(null, this.value)">
            ${allSellers.map((s) => `<option value="${esc(s.username)}" ${s.username === prof.username ? 'selected' : ''}>${esc(s.display_name_en)} (@${esc(s.username)} • ${esc(s.branch_scope)})</option>`).join('')}
          </select>
        ` : ''}
        <select style="width:auto;" onchange="changeWalletFilter(this.value, null)">
          ${monthsOpts.map((m) => `<option value="${esc(m)}" ${(w.month || STATE.walletMonth) === m ? 'selected' : ''}>${m === 'ALL' ? (ar ? 'كل الشهور' : 'All Months') : esc(m)}</option>`).join('')}
        </select>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <button type="button" class="btn btn-primary" style="padding:9px 14px;font-weight:900;" onclick="openEntityDossier('SALES_REP', '${esc(prof.username || (STATE.user && STATE.user.username) || '')}')">📒 ${ar ? 'كشف الحساب الكامل والدفعات والرصيد' : 'Full Statement &amp; Installments'}</button>
        <button type="button" class="btn btn-amber" style="padding:9px 14px;font-weight:900;" onclick="openAllowanceModal()">💸 ${ar ? 'اطلب سلفة / دفعة عمولة' : 'Request Cash Allowance'}</button>
        <a class="btn btn-emerald" href="${esc(walletExportHref)}">⬇ .xlsx</a>
      </div>
    </div>

    <!-- 3 GIANT COLOR-CODED PLAIN-LANGUAGE MONEY CARDS (Zero Accounting Jargon!) -->
    <div class="grid grid-3" style="margin-bottom:12px;">
      <div class="card" style="border-color:rgba(16,185,129,0.55);background:linear-gradient(135deg,rgba(16,185,129,0.16),rgba(11,34,57,0.95));">
        <div class="kpi-label" style="color:#6ee7b7;font-weight:800;">🟢 ${ar ? 'جاهز للصرف الآن (بعد السلف)' : 'Ready Cash to Take Now'}</div>
        <div class="kpi-val pos" style="font-size:26px;">${fmt(netDueNow)} <span style="font-size:12px;color:var(--muted);">EGP</span></div>
        <div class="kpi-sub">${ar ? `السلف المسحوبة: ${fmt(settledAndAllowances)} ج` : `Past Allowances Taken: ${fmt(settledAndAllowances)} EGP`}</div>
      </div>

      <div class="card" style="border-color:rgba(245,158,11,0.5);background:linear-gradient(135deg,rgba(245,158,11,0.13),rgba(11,34,57,0.95));">
        <div class="kpi-label" style="color:#fde68a;font-weight:800;">🟡 ${ar ? 'عمولة منتظرة (الرحلة أو الباقي)' : 'Waiting Guest Trip or Rest'}</div>
        <div class="kpi-val" style="color:#fcd34d;font-size:26px;">${fmt(waitingRestOrTrip)} <span style="font-size:12px;color:var(--muted);">EGP</span></div>
        <div class="kpi-sub">${ar ? 'تنزل في الجاهز فور انتهاء الرحلة وتحصيل الباقي' : 'Unlocks automatically once guest completes trip & pays rest'}</div>
      </div>

      <div class="card" style="border-color:rgba(0,210,255,0.5);">
        <div class="kpi-label" style="color:var(--cyan);font-weight:800;">🔵 ${ar ? 'إجمالي عمولتي المؤكدة' : 'Total Confirmed Commission'}</div>
        <div class="kpi-val" style="color:var(--cyan);font-size:26px;">${fmt(actualDueComm)} <span style="font-size:12px;color:var(--muted);">EGP</span></div>
        <div class="kpi-sub">${fmt(k.entries_count)} ${ar ? 'رحلة' : 'Trips'} • ${ar ? 'مبيعات:' : 'Sales:'} ${fmt(k.gross_sales_egp)} EGP</div>
      </div>
    </div>

    <!-- Gamification XP, Earned Best-Seller Awards & Toggleable Target Bar -->
    <div class="card" style="margin-bottom:12px;background:linear-gradient(135deg,rgba(99,102,241,0.14),rgba(0,210,255,0.1));">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
          <span class="xp-pill">⚡ ${esc(prof.level_title || '🌊 Rising Star')} • ${fmt(prof.xp_points || 0)} XP</span>
          ${myAwards.length
            ? myAwards.map((a) => `<span class="award-medal">${esc(a.icon)} ${esc(ar ? a.title_ar : a.title_en)}</span>`).join('')
            : `<span class="pill" style="opacity:0.9;">🎯 ${ar ? 'احجز أكثر لتفوز بوسام نجم اليوم 🥇 وبطل الأسبوع 🏆!' : 'Book trips to win 🥇 Daily & 🏆 Weekly Best-Seller Medals!'}</span>`
          }
        </div>
        <span style="font-size:11.5px;color:var(--muted);">🎁 ${ar ? 'خصومات ممنوحة:' : 'Discounts:'} ${fmt(discSum.total_discount_egp || 0)} EGP</span>
      </div>

      ${tgt && tgt.is_active ? `
        <div style="margin-top:12px;padding-top:10px;border-top:1px solid var(--border);">
          <div style="display:flex;justify-content:space-between;font-size:12px;font-weight:800;">
            <span>🎯 ${ar ? 'التارجت الشهري والمرحلة المحققة:' : 'Active Monthly Performance Target:'} <b class="pos">${fmt(tgt.achieved_monthly_egp)} EGP</b> / ${fmt(tgt.target_monthly_egp)} EGP</span>
            <span style="color:var(--cyan);">${fmt(tgt.pct_monthly, 1)}%</span>
          </div>
          <div class="target-track"><div class="target-fill" style="width:${Math.min(100, Math.max(2, Number(tgt.pct_monthly || 0)))}%;"></div></div>
        </div>
      ` : ''}
    </div>

    <!-- Shareable Personal QR & 1-Tap WhatsApp Brochure Card -->
    <div class="card" style="margin-bottom:12px;display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
      <div class="qr-box">${svgQr}</div>
      <div style="flex:1;min-width:230px;">
        <span class="pill">${esc(prof.branch_scope || 'CYRENE')} • REP: ${esc(prof.rep_scope || slug.toUpperCase())}</span>
        <h3 style="font-size:18px;margin-top:5px;">${esc(ar ? (prof.display_name_ar || prof.display_name_en) : (prof.display_name_en || prof.username))}</h3>
        <div style="font-size:11.5px;color:#34d399;word-break:break-all;margin:4px 0 2px;font-weight:800;">📲 ${ar ? 'رابط كل الرحلات لعميلك:' : 'All-Trips Client Link:'} ${esc(activeShareBrochure)}</div>
        <div style="font-size:11px;color:var(--cyan);word-break:break-all;margin-bottom:8px;">🤿 Diving QR: ${esc(qrUrl)}</div>

        <div style="display:flex;gap:7px;flex-wrap:wrap;margin-bottom:8px;">
          <a class="btn btn-emerald" style="padding:9px 14px;font-size:13px;font-weight:900;" href="https://wa.me/?text=${encodeURIComponent('🌴 Explore all Sharm El Sheikh Diving, Boat, Safari & Cairo Trips and confirm your booking directly with me: ' + activeShareBrochure)}" target="_blank" rel="noopener">💬 ${ar ? 'ابعت على واتساب' : 'Share on WhatsApp'}</a>
          <button type="button" class="btn btn-primary" style="padding:9px 13px;" onclick="downloadSellerQrPng('${esc(qrTargetUrl)}', '${esc(slug)}')">⬇ ${ar ? 'تحميل صورة QR' : 'Download PNG QR'}</button>
          <button type="button" class="btn" style="padding:9px 12px;" onclick="shareNativeOrCopy('${esc(activeShareBrochure)}', 'Sea College Trips')">📲 ${ar ? 'نسخ الرابط' : 'Copy Link'}</button>
          <a class="btn" style="border-color:#3b82f6;color:#93c5fd;" href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(activeShareBrochure)}" target="_blank" rel="noopener">📘 FB</a>
          <a class="btn" style="border-color:#38bdf8;color:#7dd3fc;" href="https://t.me/share/url?url=${encodeURIComponent(activeShareBrochure)}" target="_blank" rel="noopener">✈️ TG</a>
        </div>

        ${discPol.is_enabled ? `
          <div style="padding:8px 10px;border-radius:10px;background:rgba(245,158,11,0.09);border:1px solid rgba(245,158,11,0.34);">
            <div style="font-size:11.5px;font-weight:800;color:#fde68a;margin-bottom:5px;">🎁 ${ar ? 'إنشاء رابط خصم VIP خاص لعميلك (% أو €):' : 'Create VIP Discount Link & QR (% or €):'}</div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
              <select id="walPromoType" style="width:auto;padding:5px 7px;font-size:11.5px;">
                <option value="PERCENT" ${STATE.walletPromoType === 'PERCENT' ? 'selected' : ''}>% (Max ${discPol.max_discount_pct}%)</option>
                <option value="AMOUNT" ${STATE.walletPromoType === 'AMOUNT' ? 'selected' : ''}>€ (Max €${discPol.max_discount_eur})</option>
              </select>
              <input type="number" step="0.5" min="0" id="walPromoVal" value="${Number(STATE.walletPromoVal || 10)}" style="width:74px;padding:5px;font-size:11.5px;">
              <button type="button" class="btn btn-amber" style="padding:5px 10px;font-size:11.5px;" onclick="generateWalletPromoShareLink()">✨ ${ar ? 'تفعيل خصم الرابط' : 'Create VIP Link'}</button>
              ${STATE.walletPromoUrl ? `<button type="button" class="btn" style="padding:5px 8px;font-size:11px;" onclick="STATE.walletPromoUrl='';renderApp();">Reset</button>` : ''}
            </div>
          </div>
        ` : ''}

        <div style="display:flex;gap:5px;flex-wrap:wrap;margin-top:8px;">
          ${Object.entries(locUrls).map(([lg, uLink]) => `<button type="button" class="contact-chip" style="cursor:pointer;" onclick="copyTextPrompt('${esc(uLink)}')">🌐 ${esc(lg.toUpperCase())}</button>`).join('')}
        </div>
      </div>
    </div>

    <!-- Collapsible Detailed Tables (Zero Scroll Clutter by Default!) -->
    <details class="card" style="margin-bottom:10px;">
      <summary style="cursor:pointer;font-size:13.5px;font-weight:800;color:var(--cyan);">🎫 ${ar ? `قائمة رحلاتي وحالة التحصيل (${myTrips.length})` : `My Trips & Payment Status (${myTrips.length})`}</summary>
      <div class="table-wrap" style="max-height:310px;margin-top:10px;">
        <table>
          <thead>
            <tr>
              <th>Date</th><th>VO.NO</th><th>Guest &amp; Hotel</th><th>Trip</th><th>PAX</th>
              <th>Actual Net Total</th><th>Paid</th><th>REST Due</th><th>Status</th><th>Action</th>
            </tr>
          </thead>
          <tbody>
            ${myTrips.map((t) => {
              const wf = String(t.workflow_status || t.boarding_status || 'CONFIRMED');
              const restEgp = Number(t.rest_egp_equiv || 0);
              const totEgp = Number(t.actual_total_egp || (Number(t.paid_egp_equiv || 0) + restEgp));
              const dEgp = Number(t.discount_egp || 0);
              const listEgp = Number(t.list_total_egp || (totEgp + dEgp));
              const isDonePaid = (t.boarding_status === 'BOARDED' || t.boarding_status === 'EXECUTED') && restEgp <= 0.5;
              return `
                <tr>
                  <td>${esc(t.trip_date)}</td>
                  <td><b>${esc(t.voucher_no)}</b></td>
                  <td><b>${esc(t.guest_name || 'Guest')}</b><div style="font-size:10.5px;color:var(--muted);">${esc(t.hotel_name || '')} ${t.room_no ? '#' + esc(t.room_no) : ''}</div></td>
                  <td>
                    ${esc(t.trip_name)}
                    ${dEgp > 0 ? `<div style="font-size:10px;color:#fcd34d;">🎁 Special Discount: -${fmt(dEgp)} EGP (${t.discount_type === 'PERCENT' ? '-' + esc(t.discount_value) + '%' : '-' + esc(t.discount_value) + ' ' + esc(t.discount_currency || 'EUR')})</div>` : ''}
                    ${t.modification_note ? `<div style="font-size:10px;color:#fcd34d;">✏️ ${esc(t.modification_note)}</div>` : ''}
                  </td>
                  <td class="num">${Number(t.adults || 1)}A${Number(t.children || 0) > 0 ? `+${t.children}C` : ''}${Number(t.infants || 0) > 0 ? `+${t.infants}I` : ''}</td>
                  <td class="num">${dEgp > 0 ? `<s style="opacity:0.6;font-size:10.5px;">${fmt(listEgp)}</s> ` : ''}<b>${fmt(totEgp)} EGP</b></td>
                  <td class="num pos">${fmt(t.paid_egp_equiv)}</td>
                  <td class="num ${restEgp > 0.5 ? 'neg' : 'pos'}">${restEgp > 0.5 ? fmt(restEgp) + ' EGP' : 'PAID 100%'}</td>
                  <td><span class="pill" style="${isDonePaid ? 'border-color:#10b981;color:#34d399;' : ''}">${isDonePaid ? '✅ DONE & PAID' : esc(wf)}</span></td>
                  <td>
                    ${wf === 'AWAITING_SELLER_CONFIRM' ? `<button type="button" class="btn btn-emerald" onclick="sellerConfirmBooking(${Number(t.id)})">✅ Confirm to Reservations</button>` : '—'}
                  </td>
                </tr>
              `;
            }).join('')}
          </tbody>
        </table>
      </div>
    </details>

    ${advRows.length ? `
      <details class="card" style="margin-bottom:10px;">
        <summary style="cursor:pointer;font-size:13.5px;font-weight:800;color:#fde68a;">💸 ${ar ? `سجل السلف والدفعات (${advRows.length})` : `My Allowances & Installments (${advRows.length})`}</summary>
        <div class="table-wrap" style="max-height:200px;margin-top:10px;">
          <table>
            <thead><tr><th>Date</th><th>Month</th><th>Amount (EGP)</th><th>Status</th><th>Approved By</th><th>Notes</th></tr></thead>
            <tbody>
              ${advRows.map((a) => `
                <tr>
                  <td>${esc(a.request_date)}</td>
                  <td>${esc(a.period_month)}</td>
                  <td class="num"><b>${fmt(a.amount_egp)} EGP</b></td>
                  <td><span class="pill">${esc(a.status)}</span></td>
                  <td>${esc(a.approved_by || 'Pending Accounting')}</td>
                  <td>${esc(a.notes || '—')}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </details>
    ` : ''}

    <details class="card" style="margin-bottom:10px;">
      <summary style="cursor:pointer;font-size:13.5px;font-weight:800;color:var(--muted);">📋 ${ar ? `تفاصيل الحساب المحاسبي للعمولات (${rows.length})` : `Detailed Accounting Ledger — INSHEET 10% vs OUT_SIDE 25% (${rows.length})`}</summary>
      <div class="table-wrap" style="max-height:340px;margin-top:10px;">
        <table>
          <thead>
            <tr>
              <th>Date</th><th>VO.NO</th><th>Channel</th><th>Rule</th><th>Actual Collected / Gross (EGP)</th>
              <th>Vendor Cost</th><th>Net Base</th><th>Rate</th><th>Commission (EGP)</th><th>Status</th>
            </tr>
          </thead>
          <tbody>
            ${rows.map((r) => `
              <tr>
                <td>${esc(r.tx_date)}</td>
                <td><b>${esc(r.voucher_no)}</b></td>
                <td>${esc(r.source_channel)}</td>
                <td><span class="pill">${esc(r.commission_type)}</span></td>
                <td class="num">${fmt(r.gross_amount_egp)}</td>
                <td class="num">${fmt(r.supplier_cost_egp)}</td>
                <td class="num">${fmt(r.commissionable_base_egp)}</td>
                <td class="num">${fmt(Number(r.commission_rate || 0) * 100, 1)}%</td>
                <td class="num pos"><b>${fmt(r.commission_egp, 2)}</b></td>
                <td><span class="pill" style="${r.status === 'REVERSED_CANCELLED' ? 'color:#fb7185;border-color:#f43f5e;' : ''}">${esc(r.status)}</span></td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    </details>

    ${leads.length ? `
      <details class="card">
        <summary style="cursor:pointer;font-size:13.5px;font-weight:800;color:var(--muted);">🌐 ${ar ? `عملاء الروابط والباركود (${leads.length})` : `Shareable Link & QR Leads (${leads.length})`}</summary>
        <div class="table-wrap" style="max-height:240px;margin-top:10px;">
          <table>
            <thead><tr><th>Received</th><th>Type</th><th>Lang</th><th>Guest</th><th>Trip Date</th><th>Interest</th><th>Est. EUR</th><th>Voucher</th></tr></thead>
            <tbody>
              ${leads.map((l) => `
                <tr>
                  <td>${esc(String(l.received_at || '').slice(0, 16))}</td>
                  <td><span class="pill">${esc(l.lead_type)}</span></td>
                  <td>${esc(l.lang)}</td>
                  <td><b>${esc(l.guest_name || 'WhatsApp Visitor')}</b></td>
                  <td>${esc(l.trip_date || '—')}</td>
                  <td>${esc(l.interest_items || '—')}</td>
                  <td class="num pos">€${fmt(l.estimated_eur, 2)}</td>
                  <td><b>${esc(l.linked_voucher_no || '—')}</b></td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </details>
    ` : ''}
  `;
}

/* ================= F14: MONTHLY MANAGEMENT REPORTS PACK (ALL 12 SCHEDULES) ================= */
async function changeReportMonth(m) {
  STATE.reportMonth = m || '2026-10';
  const res = await apiGet({ action: 'monthly_report_pack', month: STATE.reportMonth });
  if (res && res.ok) {
    STATE.monthlyPack = res;
    renderApp();
  }
}

function renderMonthlyReportsPack(ar) {
  const pack = STATE.monthlyPack || {};
  const m = pack.month || STATE.reportMonth || '2026-10';
  const months = pack.available_months || ['2026-10'];
  const fx = pack.fx_rates || { USD: 50, EUR: 57, GBP: 65, EGP: 1 };
  const k = pack.kpis || {};
  const sc = pack.schedules || {};

  const dcr = sc.daily_cash_revenue || { totals: {}, rows: [] };
  const scomm = sc.seller_commissions || { totals: {}, rows: [] };
  const pCyr = sc.pnl_cyrene || { totals: {}, rows: [] };
  const pJet = sc.pnl_jetty || { totals: {}, rows: [] };
  const pCon = sc.pnl_consolidated || { totals: {}, rows: [] };
  const cbox = sc.treasury_cashbox || { totals: {}, rows: [] };
  const prCyr = sc.payroll_cyrene || { totals: {}, rows: [] };
  const prJet = sc.payroll_jetty_boats || { totals: {}, rows: [] };
  const sm = sc.supermarket || { totals: {}, rows: [] };
  const chIn = sc.charters_in || { totals: {}, rows: [] };
  const chOut = sc.charters_out || { totals: {}, rows: [] };
  const pStmt = sc.partner_statements || { rows: [] };
  const ytd = sc.profit_share_ytd || { rows: [] };
  const banks = sc.bank_balances || { totals: {}, rows: [] };
  const ob = sc.open_balances || { totals: {}, receivables: [], payables: [], liquidity_bridge: [] };
  const bridge = (sc.res_accounting_bridge && sc.res_accounting_bridge.summary) || {};

  const xlsxPackUrl = `/handlers/erp_export.php?report=monthly_pack&format=xlsx&month=${encodeURIComponent(m)}`;
  const csvPackUrl = `/handlers/erp_export.php?report=monthly_pack&format=csv&month=${encodeURIComponent(m)}`;
  const commXlsxUrl = `/handlers/erp_export.php?report=seller_commissions&format=xlsx&month=${encodeURIComponent(m)}`;

  const showConsolidated = STATE.pnlViewMode !== 'branch';
  const showAll3Banks = STATE.bankViewMode !== 'operating';
  const shownBankTotal = showAll3Banks ? banks.totals.all_3_banks_egp : banks.totals.operating_banks_egp;

  return `
    <div class="horizon-bar" style="margin-bottom:14px;">
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <span style="font-size:14px;font-weight:900;color:var(--cyan);">📑 ${ar ? 'الحزمة الشهرية الختامية الموحدة للإدارة' : 'Monthly Management Closing & Reconciliation Pack'}</span>
        <select style="width:auto;font-weight:800;" onchange="changeReportMonth(this.value)">
          ${months.map((mo) => `<option value="${esc(mo)}" ${mo === m ? 'selected' : ''}>📅 ${esc(mo)}</option>`).join('')}
        </select>
        <span class="pill">FX: $1=${fmt(fx.USD)} • €1=${fmt(fx.EUR)} • £1=${fmt(fx.GBP)} EGP</span>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a class="btn btn-emerald" href="${esc(xlsxPackUrl)}">⬇ 14-Sheet Monthly Pack (.xlsx)</a>
        <a class="btn" href="${esc(csvPackUrl)}">⬇ Summary (.csv)</a>
        <a class="btn" href="${esc(commXlsxUrl)}">⬇ Seller Commissions (.xlsx)</a>
      </div>
    </div>

    <div class="grid grid-kpi" style="margin-bottom:14px;">
      <div class="card">
        <div class="kpi-label">${ar ? 'إجمالي النقدية اليومية (سيرين + السقالة)' : 'Combined Daily Cash Rev'}</div>
        <div class="kpi-val">${fmt(k.combined_cash_rev_egp)} <span style="font-size:11px;color:var(--muted);">EGP</span></div>
        <div class="kpi-sub">Cyrene: ${fmt(k.cyrene_cash_rev_egp)} • Jetty: ${fmt(k.jetty_cash_rev_egp)}</div>
      </div>
      <div class="card">
        <div class="kpi-label">${ar ? 'مقبوضات الفيزا + عمولات سيرين' : 'Combined VISA & Cyrene Comm'}</div>
        <div class="kpi-val" style="color:var(--cyan);">${fmt(k.combined_visa_egp)} <span style="font-size:11px;color:var(--muted);">VISA</span></div>
        <div class="kpi-sub">Cyrene Seller Comm: <b>${fmt(k.cyrene_commissions_egp)} EGP</b></div>
      </div>
      <div class="card">
        <div class="kpi-label">${ar ? 'صافي ربح التشغيل الموحد' : 'Consolidated Net Profit'}</div>
        <div class="kpi-val ${Number(k.consolidated_net_profit_egp || 0) >= 0 ? 'pos' : 'neg'}">${fmt(k.consolidated_net_profit_egp)} <span style="font-size:11px;color:var(--muted);">EGP</span></div>
        <div class="kpi-sub">Cyrene: ${fmt(k.cyrene_net_profit_egp)} • Jetty: +${fmt(k.jetty_net_profit_egp)}</div>
      </div>
      <div class="card">
        <div class="kpi-label">${ar ? 'صافي المرتبات (سيرين + السقالة والمراكب)' : 'Combined Net Payroll'}</div>
        <div class="kpi-val">${fmt(k.combined_net_payroll_egp)} <span style="font-size:11px;color:var(--muted);">EGP</span></div>
        <div class="kpi-sub">Cyrene: ${fmt(prCyr.totals.net_pay_egp)} • Jetty/Boats: ${fmt(prJet.totals.net_pay_egp)}</div>
      </div>
      <div class="card">
        <div class="kpi-label">${ar ? 'إجمالي البنوك المؤمنة' : 'Corporate Bank Treasury'}</div>
        <div class="kpi-val pos">${fmt(shownBankTotal, 2)} <span style="font-size:11px;color:var(--muted);">EGP</span></div>
        <div class="kpi-sub">Operating (2): ${fmt(banks.totals.operating_banks_egp, 2)} • All 3: ${fmt(banks.totals.all_3_banks_egp, 2)}</div>
      </div>
      <div class="card">
        <div class="kpi-label">${ar ? 'الأرصدة المفتوحة وصافي السيولة' : 'Open AR / AP & Free Liquidity'}</div>
        <div class="kpi-val" style="font-size:16px;">AR ${fmt(k.open_receivables_egp)} / AP ${fmt(k.open_payables_egp)}</div>
        <div class="kpi-sub">Net Free Liquidity: <b>${fmt(k.net_free_liquidity_egp)} EGP</b></div>
      </div>
    </div>

    <!-- Schedule 3/4/5: Branch vs Consolidated P&L Toggle -->
    <div class="card" style="margin-bottom:14px;">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
        <h3 style="font-size:15px;">⚖️ ${ar ? 'قائمة الأرباح والخسائر الموحدة واستبعاد التحويلات المتبادلة' : 'Schedule 3–5: Branch vs Consolidated Profit & Loss (Inter-Branch Eliminated)'}</h3>
        <div class="seg-group">
          <button type="button" class="seg-btn ${showConsolidated ? 'active' : ''}" onclick="STATE.pnlViewMode='consolidated';renderApp();">Consolidated (Eliminate ${fmt(pCon.totals.interbranch_elimination_egp)} Interbranch)</button>
          <button type="button" class="seg-btn ${!showConsolidated ? 'active' : ''}" onclick="STATE.pnlViewMode='branch';renderApp();">Uneliminated Branch Gross (${fmt(pCon.totals.gross_branch_rev_egp)})</button>
        </div>
      </div>
      <div style="font-size:12px;color:var(--muted);margin-bottom:10px;">
        Gross Branch Revenue: <b>${fmt(pCon.totals.gross_branch_rev_egp)} EGP</b> − Interbranch Elimination: <b>${fmt(pCon.totals.interbranch_elimination_egp)} EGP</b> = Consolidated External Revenue: <b>${fmt(pCon.totals.consolidated_ext_rev_egp)} EGP</b> • External Expenses: <b>${fmt(pCon.totals.consolidated_ext_exp_egp)} EGP</b> • Consolidated Net Profit: <b class="pos">${fmt(pCon.totals.consolidated_net_profit_egp)} EGP</b>
      </div>
      <div class="grid grid-2">
        <div class="table-wrap" style="max-height:290px;">
          <table>
            <thead><tr><th>Cyrene P&amp;L Line</th><th>Type</th><th>Amount (EGP)</th></tr></thead>
            <tbody>
              ${(pCyr.rows || []).map((r) => `
                <tr>
                  <td>${esc(ar ? r.line_name_ar : (r.line_name_en || r.line_name_ar))} ${r.is_interbranch ? '<span class="pill">Interbranch</span>' : ''}</td>
                  <td>${esc(r.section_type)}</td>
                  <td class="num ${r.section_type === 'NET_PROFIT' ? (r.amount_egp >= 0 ? 'pos' : 'neg') : ''}">${fmt(r.amount_egp)}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
        <div class="table-wrap" style="max-height:290px;">
          <table>
            <thead><tr><th>Travco Jetty P&amp;L Line</th><th>Type</th><th>Amount (EGP)</th></tr></thead>
            <tbody>
              ${(pJet.rows || []).map((r) => `
                <tr>
                  <td>${esc(ar ? r.line_name_ar : (r.line_name_en || r.line_name_ar))} ${r.is_interbranch ? '<span class="pill">Interbranch</span>' : ''}</td>
                  <td>${esc(r.section_type)}</td>
                  <td class="num ${r.section_type === 'NET_PROFIT' ? (r.amount_egp >= 0 ? 'pos' : 'neg') : ''}">${fmt(r.amount_egp)}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Schedule 1 & Schedule 2: Daily Cash Revenue & Seller Commissions -->
    <div class="grid grid-2" style="margin-bottom:14px;">
      <div class="card">
        <h3 style="font-size:14px;margin-bottom:8px;">💵 Schedule 1: ${esc(ar ? dcr.title_ar : dcr.title_en)} (Total: ${fmt(dcr.totals.combined_cash_egp)} EGP)</h3>
        <div class="table-wrap" style="max-height:270px;">
          <table>
            <thead><tr><th>Date</th><th>Cyrene Cash</th><th>Jetty Cash</th><th>Combined Cash</th><th>VISA</th></tr></thead>
            <tbody>
              ${(dcr.rows || []).map((r) => `
                <tr>
                  <td>${esc(r.rev_date)}</td>
                  <td class="num">${fmt(r.cyrene_total_egp)}</td>
                  <td class="num">${fmt(r.jetty_total_egp)}</td>
                  <td class="num pos"><b>${fmt(r.combined_total_egp)}</b></td>
                  <td class="num">${fmt(Number(r.cyrene_visa || 0) + Number(r.jetty_visa || 0))}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <h3 style="font-size:14px;margin-bottom:8px;">🏆 Schedule 2: ${esc(ar ? scomm.title_ar : scomm.title_en)} (Cyrene: ${fmt(scomm.totals.cyrene_comm_rounded_egp)} EGP on ${fmt(scomm.totals.cyrene_sales_egp)} Sales)</h3>
        <div class="table-wrap" style="max-height:270px;">
          <table>
            <thead><tr><th>Rep</th><th>Branch</th><th>In-Sheet 10%</th><th>Outside 25%</th><th>QR 10%</th><th>Total Sales</th><th>Total Comm</th></tr></thead>
            <tbody>
              ${(scomm.rows || []).map((r) => `
                <tr>
                  <td><b>${esc(r.rep_scope)}</b></td>
                  <td><span class="pill">${esc(r.branch_code || 'CYRENE')}</span></td>
                  <td class="num">${fmt(r.insheet_comm_egp)}</td>
                  <td class="num">${fmt(r.outside_comm_egp)}</td>
                  <td class="num">${fmt(r.qr_comm_egp)}</td>
                  <td class="num">${fmt(r.total_sales_egp)}</td>
                  <td class="num pos"><b>${fmt(r.total_comm_rounded_egp ?? r.total_comm_egp)}</b></td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Schedule 6 & Schedule 14: Cashbox Movement & Masked Bank Balances -->
    <div class="grid grid-2" style="margin-bottom:14px;">
      <div class="card">
        <h3 style="font-size:14px;margin-bottom:8px;">🔐 Schedule 6: ${esc(ar ? cbox.title_ar : cbox.title_en)}</h3>
        <div style="font-size:11.5px;color:var(--muted);margin-bottom:8px;">
          Closing Cashbox: <b>${fmt(cbox.totals.closing_le)} LE</b> • <b>$${fmt(cbox.totals.closing_usd)}</b> • <b>€${fmt(cbox.totals.closing_eur)}</b> • <b>£${fmt(cbox.totals.closing_gbp)}</b> • VISA Cumulative: <b>${fmt(cbox.totals.inc_visa)} EGP</b>
        </div>
        <div class="table-wrap" style="max-height:240px;">
          <table>
            <thead><tr><th>Date</th><th>Bal LE</th><th>Bal $</th><th>Bal €</th><th>Bal £</th><th>In VISA</th></tr></thead>
            <tbody>
              ${(cbox.rows || []).map((r) => `
                <tr>
                  <td>${esc(r.tx_date)}</td>
                  <td class="num ${Number(r.bal_le || 0) < 0 ? 'neg' : ''}">${fmt(r.bal_le)}</td>
                  <td class="num">${fmt(r.bal_usd)}</td>
                  <td class="num">${fmt(r.bal_eur)}</td>
                  <td class="num">${fmt(r.bal_gbp)}</td>
                  <td class="num">${fmt(r.inc_visa)}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px;margin-bottom:8px;">
          <h3 style="font-size:14px;">🏦 Schedule 14: ${esc(ar ? banks.title_ar : banks.title_en)}</h3>
          <div class="seg-group">
            <button type="button" class="seg-btn ${showAll3Banks ? 'active' : ''}" onclick="STATE.bankViewMode='all3';renderApp();">All 3 Banks (${fmt(banks.totals.all_3_banks_egp, 2)})</button>
            <button type="button" class="seg-btn ${!showAll3Banks ? 'active' : ''}" onclick="STATE.bankViewMode='operating';renderApp();">Operating (${fmt(banks.totals.operating_banks_egp, 2)})</button>
          </div>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Bank</th><th>Masked Account</th><th>EGP</th><th>USD</th><th>EUR</th><th>GBP</th><th>Total EGP Equiv</th></tr></thead>
            <tbody>
              ${(banks.rows || []).filter((b) => showAll3Banks || Number(b.is_operating_bank || 0) === 1).map((b) => `
                <tr>
                  <td><b>${esc(ar ? (b.bank_name_ar || b.bank_code) : (b.bank_name_en || b.bank_code))}</b></td>
                  <td><code>${esc(b.account_masked || '****')}</code></td>
                  <td class="num">${fmt(b.bal_egp, 2)}</td>
                  <td class="num">$${fmt(b.bal_usd, 2)}</td>
                  <td class="num">€${fmt(b.bal_eur, 2)}</td>
                  <td class="num">£${fmt(b.bal_gbp, 2)}</td>
                  <td class="num pos"><b>${fmt(b.total_egp_equiv, 2)}</b></td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Schedule 7/8 Payroll & Schedule 9/10/11 Supermarket + Charters -->
    <div class="grid grid-2" style="margin-bottom:14px;">
      <div class="card">
        <h3 style="font-size:14px;margin-bottom:8px;">👥 Schedule 7–8: Staff Payroll (Combined Net: ${fmt(k.combined_net_payroll_egp)} EGP)</h3>
        <div style="font-size:11.5px;color:var(--muted);margin-bottom:8px;">
          Cyrene Net: <b>${fmt(prCyr.totals.net_pay_egp)} EGP</b> (Gross ${fmt(prCyr.totals.gross_egp)}) • Jetty &amp; Boats Net: <b>${fmt(prJet.totals.net_pay_egp)} EGP</b> (Gross ${fmt(prJet.totals.gross_egp)})
        </div>
        <div class="table-wrap" style="max-height:250px;">
          <table>
            <thead><tr><th>Branch</th><th>Employee</th><th>Base</th><th>Comm</th><th>Ded.</th><th>Net Pay</th></tr></thead>
            <tbody>
              ${[...(prCyr.rows || []), ...(prJet.rows || [])].map((p) => `
                <tr>
                  <td><span class="pill">${esc(p.branch_code || 'STAFF')}</span></td>
                  <td><b>${esc(p.emp_name_ar || p.emp_name_en || '—')}</b></td>
                  <td class="num">${fmt(p.base_salary_egp)}</td>
                  <td class="num">${fmt(p.commission_egp)}</td>
                  <td class="num neg">${fmt(p.advances_deductions_egp)}</td>
                  <td class="num pos"><b>${fmt(p.net_pay_egp)}</b></td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <h3 style="font-size:14px;margin-bottom:8px;">🛒🛥️ Schedules 9–11: Supermarket Catering &amp; Boat Charters IN / OUT</h3>
        <div class="grid grid-3" style="gap:8px;margin-bottom:10px;">
          <div style="padding:9px;border-radius:9px;background:rgba(6,21,38,0.72);border:1px solid var(--border);">
            <div class="kpi-label">Supermarket Total</div>
            <div style="font-size:15px;font-weight:800;margin-top:3px;">${fmt(sm.totals.total_egp)} EGP</div>
            <div style="font-size:11px;color:var(--muted);">Paid ${fmt(sm.totals.paid_egp)} • Due ${fmt(sm.totals.payable_egp)}</div>
          </div>
          <div style="padding:9px;border-radius:9px;background:rgba(6,21,38,0.72);border:1px solid var(--border);">
            <div class="kpi-label">Charters IN (${fmt(chIn.totals.trips_count)} Trips)</div>
            <div style="font-size:15px;font-weight:800;margin-top:3px;">${fmt(chIn.totals.total_egp)} EGP</div>
            <div style="font-size:11px;color:var(--muted);">Paid ${fmt(chIn.totals.paid_or_collected_egp)} • AP ${fmt(chIn.totals.remaining_egp)}</div>
          </div>
          <div style="padding:9px;border-radius:9px;background:rgba(6,21,38,0.72);border:1px solid var(--border);">
            <div class="kpi-label">Charters OUT (${fmt(chOut.totals.trips_count)} Trips)</div>
            <div style="font-size:15px;font-weight:800;margin-top:3px;" class="pos">${fmt(chOut.totals.total_egp)} EGP</div>
            <div style="font-size:11px;color:var(--muted);">Coll. ${fmt(chOut.totals.paid_or_collected_egp)} • AR ${fmt(chOut.totals.remaining_egp)}</div>
          </div>
        </div>
        <div class="table-wrap" style="max-height:175px;">
          <table>
            <thead><tr><th>Dir</th><th>Date</th><th>Boat / Counterparty</th><th>PAX</th><th>Total EGP</th><th>Remaining</th></tr></thead>
            <tbody>
              ${[...(chIn.rows || []), ...(chOut.rows || [])].map((c) => `
                <tr>
                  <td><span class="pill">${esc(c.direction)}</span></td>
                  <td>${esc(c.trip_date)}</td>
                  <td>${esc(c.boat_name)} — ${esc(c.counterparty_name)}</td>
                  <td class="num">${fmt(Number(c.adults || 0) + Number(c.children || 0))}</td>
                  <td class="num">${fmt(c.total_egp)}</td>
                  <td class="num">${fmt(c.remaining_egp)}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Schedule 12 Partner Statements, Schedule 13 YTD Profit Share & Schedule 15/16 Open Balances + Res Bridge -->
    <div class="grid grid-2" style="margin-bottom:14px;">
      <div class="card">
        <h3 style="font-size:14px;margin-bottom:8px;">🤝 Schedule 12–13: Partner Yacht Statements &amp; YTD 10% Profit Pool</h3>
        <div class="table-wrap" style="max-height:260px;">
          <table>
            <thead><tr><th>Schedule</th><th>Description</th><th>Credit / Pool</th><th>Debit / Share</th><th>Balance (EGP)</th></tr></thead>
            <tbody>
              ${[...(ytd.rows || []), ...(pStmt.rows || [])].map((p) => `
                <tr>
                  <td><span class="pill">${esc(p.schedule_code)}</span></td>
                  <td>${esc(ar ? (p.line_description_ar || p.line_description_en) : (p.line_description_en || p.line_description_ar || '—'))}</td>
                  <td class="num">${fmt(p.credit_egp)}</td>
                  <td class="num">${fmt(p.debit_egp)}</td>
                  <td class="num ${Number(p.balance_egp || 0) >= 0 ? 'pos' : 'neg'}"><b>${fmt(p.balance_egp)}</b></td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <h3 style="font-size:14px;margin-bottom:8px;">📊 Schedule 15–16: Open Balances (${fmt(ob.totals.receivables_advances_egp)} AR vs ${fmt(ob.totals.payables_accruals_egp)} AP) &amp; Reservations Bridge</h3>
        <div style="font-size:11.5px;color:var(--muted);margin-bottom:8px;">
          Executed Vouchers: <b>${fmt(bridge.executed_vouchers)}</b> (Cancelled &lt;N&gt;: ${fmt(bridge.cancelled_vouchers)}) • Net Free Liquidity: <b class="pos">${fmt(ob.totals.net_free_liquidity_egp)} EGP</b>
        </div>
        <div class="table-wrap" style="max-height:230px;">
          <table>
            <thead><tr><th>Side</th><th>Counterparty / Item</th><th>Amount (EGP)</th></tr></thead>
            <tbody>
              ${[...(ob.receivables || []), ...(ob.payables || []), ...(ob.liquidity_bridge || [])].map((o) => `
                <tr>
                  <td><span class="pill">${esc(o.side)}</span></td>
                  <td><b>${esc(ar ? (o.party_name_ar || o.party_name_en) : (o.party_name_en || o.party_name_ar))}</b></td>
                  <td class="num">${fmt(o.amount_egp)}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  `;
}

/* ================= VERIFIED REAL-CUSTOMER VIP PORTAL & MARKETING GUEST VAULT ================= */
let ACTIVE_GUEST_TRIP = null;

async function changeGuestCatalogLang(nextLang) {
  STATE.guestLang = nextLang;
  const res = await apiGet({ action: 'guest_catalog', lang: nextLang });
  if (res && res.ok) STATE.guestCatalog = res;
  renderApp();
}

function fmtGuestMoney(eurVal) {
  const fx = (STATE.guestCatalog && STATE.guestCatalog.fx_rates) || { EUR: 57, USD: 50, GBP: 65, EGP: 1 };
  const eurEgp = Number(fx.EUR || 57);
  const c = STATE.guestCurr || 'EUR';
  if (c === 'EUR') return '€' + fmt(eurVal, 0);
  if (c === 'USD') return '$' + fmt((eurVal * eurEgp) / Number(fx.USD || 50), 0);
  if (c === 'GBP') return '£' + fmt((eurVal * eurEgp) / Number(fx.GBP || 65), 0);
  return fmt(eurVal * eurEgp, 0) + ' EGP';
}

function renderGuestBookTripsTab(ar) {
  const cat = STATE.guestCatalog || {};
  const trips = cat.trips || [];
  const gp = (STATE.guestPortal && STATE.guestPortal.guest_account) || {};
  const counts = { ALL: trips.length };
  const labels = { ALL: ar ? '🌟 كل الرحلات' : '🌟 All Trips' };
  trips.forEach((t) => {
    counts[t.category_group] = (counts[t.category_group] || 0) + 1;
    labels[t.category_group] = t.category_label;
  });
  const q = String(STATE.guestQuery || '').trim().toLowerCase();
  const visible = trips.filter((t) => (STATE.guestCat === 'ALL' || t.category_group === STATE.guestCat) && (!q || `${t.name} ${t.summary} ${t.category_label}`.toLowerCase().includes(q)));

  const langOpts = [
    ['en', '🇬🇧 EN'], ['ar', '🇪🇬 AR'], ['ru', '🇷🇺 RU'], ['de', '🇩🇪 DE'],
    ['it', '🇮🇹 IT'], ['fr', '🇫🇷 FR'], ['pl', '🇵🇱 PL'], ['zh', '🇨🇳 中文']
  ];

  return `
    <div class="card" style="margin-bottom:12px;background:linear-gradient(135deg,rgba(0,210,255,0.14),rgba(16,185,129,0.1));">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
        <div>
          <h3 style="font-size:16px;">🌊 ${ar ? 'مرحباً بعودتك في نادي عملاء سي كوليدج شرم الشيخ!' : 'Welcome Back to Sea College Dive Club Sharm El Sheikh!'}</h3>
          <p style="font-size:12px;color:var(--muted);margin-top:3px;">
            ${ar ? 'اختر أي رحلة بحرية أو غوص أو سفاري أو القاهرة واحجز بلمسة واحدة بدون عربون مسبق (الدفع عند التحرك من الفندق).' : 'Browse all Red Sea diving, boat, safari & Egypt excursions — book in 1 tap and pay at hotel pickup.'}
          </p>
        </div>
        <div style="display:flex;gap:7px;flex-wrap:wrap;align-items:center;">
          <select style="width:auto;" onchange="STATE.guestCurr=this.value;renderApp();">
            ${['EUR', 'USD', 'GBP', 'EGP'].map((cc) => `<option value="${cc}" ${STATE.guestCurr === cc ? 'selected' : ''}>${cc}</option>`).join('')}
          </select>
          <select style="width:auto;" onchange="changeGuestCatalogLang(this.value)">
            ${langOpts.map(([lc, lbl]) => `<option value="${lc}" ${STATE.guestLang === lc ? 'selected' : ''}>${lbl}</option>`).join('')}
          </select>
        </div>
      </div>
      ${!gp.guest_email ? `
        <div style="margin-top:9px;padding:8px 11px;border-radius:9px;background:rgba(245,158,11,0.12);border:1px solid rgba(245,158,11,0.38);font-size:12px;display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;">
          <span>✉️ ${ar ? 'أضف بريدك الإلكتروني في حسابك لاستلام العروض الحصرية وتأكيدات الرحلات القادمة!' : 'Add your email in My Profile to receive exclusive Red Sea VIP offers & trip confirmations!'}</span>
          <button type="button" class="btn btn-amber" style="padding:4px 10px;font-size:11.5px;" onclick="STATE.activeTab='my_profile';renderApp();">➕ ${ar ? 'إضافة الإيميل' : 'Add Email'}</button>
        </div>
      ` : ''}
    </div>

    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:10px;">
      <div class="easy-cat-strip" style="flex:1;min-width:220px;">
        ${Object.keys(counts).map((k) => `
          <button type="button" class="easy-cat-tile ${STATE.guestCat === k ? 'active' : ''}" onclick="STATE.guestCat='${esc(k)}';renderApp();">
            <span>${esc(labels[k])} (${counts[k]})</span>
          </button>
        `).join('')}
      </div>
      <input type="search" style="max-width:240px;" placeholder="${ar ? '🔍 ابحث عن رحلة...' : '🔍 Search trip...'}" value="${esc(STATE.guestQuery || '')}" oninput="STATE.guestQuery=this.value;renderApp();">
    </div>

    <div class="pitch-grid">
      ${visible.map((t) => `
        <article class="pitch-card">
          <div>
            <div class="pitch-media">
              <img src="${esc(t.cover_webp || '/assets/images/pitch/ras-mohammed-boat.webp')}" alt="${esc(t.name)}" loading="lazy" onerror="this.onerror=null;this.src='/assets/images/pitch/ras-mohammed-boat.webp';">
              <span class="pitch-cat-tag">${esc(t.category_label)}</span>
              <span class="pitch-price-tag">${esc(fmtGuestMoney(t.adult_eur))}</span>
            </div>
            <div style="padding:12px 13px;">
              <div style="font-size:15.5px;font-weight:900;line-height:1.3;">${esc(t.name)}</div>
              <p style="font-size:11.5px;color:var(--muted);margin-top:4px;line-height:1.45;">${esc(String(t.summary || '').slice(0, 130))}</p>
              <div class="info-badges">
                <span class="info-badge">👤 Adult: ${esc(fmtGuestMoney(t.adult_eur))}</span>
                <span class="info-badge">🧒 Child: ${t.child_eur > 0 ? esc(fmtGuestMoney(t.child_eur)) : 'Included'}</span>
                <span class="info-badge" style="color:#34d399;">👶 Infant &lt;3: FREE</span>
                <span class="info-badge">⏱ ${esc(t.duration || 'Full Day')}</span>
                <span class="info-badge">🚐 ${esc(t.pickup || 'Free Hotel Pickup')}</span>
              </div>
            </div>
          </div>
          <div style="padding:10px 13px 13px;border-top:1px solid rgba(148,163,184,0.14);background:rgba(6,21,38,0.5);">
            <button type="button" class="btn btn-primary" style="width:100%;justify-content:center;padding:11px;font-size:13.5px;font-weight:900;" onclick="openGuestBookModal('${esc(t.id)}')">
              ⚡ ${ar ? 'احجز هذه الرحلة الآن' : 'Book This Trip Now'}
            </button>
          </div>
        </article>
      `).join('')}
    </div>
  `;
}

function openGuestBookModal(tripId) {
  const cat = STATE.guestCatalog || {};
  const trips = cat.trips || [];
  const hotels = cat.hotels || [];
  const gp = (STATE.guestPortal && STATE.guestPortal.guest_account) || {};
  const trip = trips.find((x) => String(x.id) === String(tripId)) || trips[0];
  if (!trip) return;
  ACTIVE_GUEST_TRIP = trip;
  document.getElementById('gbkTitle').textContent = `🌴 ${trip.name}`;
  document.getElementById('gbkTripCode').value = String(trip.id);
  document.getElementById('gbkPhone').value = gp.guest_phone || (STATE.user && STATE.user.phone) || '';
  document.getElementById('gbkHotel').value = gp.hotel_name || '';
  document.getElementById('gbkRoom').value = gp.room_no || '';
  document.getElementById('gbkNotes').value = '';
  document.getElementById('gbkSuccessBox').style.display = 'none';
  document.getElementById('gbkSubmitBtn').style.display = 'inline-flex';
  const dl = document.getElementById('gbkHotelsList');
  if (dl) {
    dl.innerHTML = hotels.map((h) => `<option value="${esc(h.name)}">Pickup ~${esc(h.pickup)}</option>`).join('');
  }
  recalcGuestBookModalTotal();
  document.getElementById('guestBookModal').style.display = 'flex';
}

function recalcGuestBookModalTotal() {
  if (!ACTIVE_GUEST_TRIP) return;
  const a = Math.max(1, Number(document.getElementById('gbkAdl').value || 1));
  const c = Math.max(0, Number(document.getElementById('gbkCh').value || 0));
  const i = Math.max(0, Number(document.getElementById('gbkInf').value || 0));
  const totEur = a * Number(ACTIVE_GUEST_TRIP.adult_eur || 0) + c * Number(ACTIVE_GUEST_TRIP.child_eur || 0) + i * Number(ACTIVE_GUEST_TRIP.infant_eur || 0);
  document.getElementById('gbkTotalBar').innerHTML = `
    <span>Total (${a} Adult${c > 0 ? ` + ${c} Child` : ''}${i > 0 ? ` + ${i} Infant Free` : ''}):</span>
    <span style="font-size:18px;color:var(--cyan);">${esc(fmtGuestMoney(totEur))} (€${fmt(totEur, 0)})</span>
  `;
}

async function submitGuestFutureBooking(e) {
  e.preventDefault();
  if (!ACTIVE_GUEST_TRIP) return;
  const btn = document.getElementById('gbkSubmitBtn');
  btn.disabled = true;
  try {
    const hVal = document.getElementById('gbkHotel').value.trim();
    const hotels = (STATE.guestCatalog && STATE.guestCatalog.hotels) || [];
    const matchedH = hotels.find((h) => String(h.name || '').toLowerCase() === hVal.toLowerCase());
    const res = await apiPost({
      action: 'guest_create_booking',
      trip_code: document.getElementById('gbkTripCode').value,
      trip_date: document.getElementById('gbkDate').value,
      guest_phone: document.getElementById('gbkPhone').value.trim(),
      hotel_name: hVal,
      room_no: document.getElementById('gbkRoom').value.trim(),
      pickup_time: matchedH ? matchedH.pickup : '08:00',
      adults: Number(document.getElementById('gbkAdl').value || 2),
      children: Number(document.getElementById('gbkCh').value || 0),
      infants: Number(document.getElementById('gbkInf').value || 0),
      notes: document.getElementById('gbkNotes').value.trim()
    });
    if (!res || !res.ok) {
      alert((res && res.error) || 'Could not confirm booking');
      return;
    }
    const sBox = document.getElementById('gbkSuccessBox');
    const waMsg = encodeURIComponent(`Hello Sea College! Confirmed VIP App booking ${res.voucher_no}: ${res.trip_name} on ${res.trip_date} (€${res.total_eur})`);
    sBox.style.display = 'block';
    sBox.innerHTML = `
      <div style="font-size:15px;font-weight:900;color:#34d399;">✅ Trip Confirmed — Ref ${esc(res.voucher_no)}!</div>
      <div style="font-size:12px;margin-top:4px;">Pickup: <b>~${esc(res.pickup_time)}</b> • Total at Pickup: <b>€${fmt(res.total_eur, 0)}</b></div>
      <a class="btn btn-emerald" style="margin-top:8px;justify-content:center;width:100%;" target="_blank" rel="noopener" href="https://wa.me/${esc(res.whatsapp_phone || '201001106266')}?text=${waMsg}">💬 Message Concierge on WhatsApp</a>
    `;
    btn.style.display = 'none';
    showToast(`🎉 Future Trip ${res.voucher_no} Confirmed!`);
    const gpRes = await apiGet({ action: 'guest_portal_data' });
    if (gpRes && gpRes.ok) STATE.guestPortal = gpRes;
  } finally {
    btn.disabled = false;
  }
}

function renderGuestMyTripsTab(ar) {
  const gp = STATE.guestPortal || {};
  const list = gp.bookings || [];
  const waNum = gp.whatsapp_phone || '201001106266';
  if (!list.length) {
    return `
      <div class="card" style="text-align:center;padding:28px;">
        <h3 style="font-size:16px;margin-bottom:8px;">🎫 ${ar ? 'لا توجد حجوزات مسجلة بعد' : 'No Trips Yet'}</h3>
        <button type="button" class="btn btn-primary" onclick="STATE.activeTab='guest_book_trips';renderApp();">🌴 ${ar ? 'استعرض رحلات شرم الشيخ واحجز الآن' : 'Browse Trips & Book Now'}</button>
      </div>
    `;
  }
  return `
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(295px,1fr));gap:12px;">
      ${list.map((b) => {
        const isCancelled = String(b.booking_state || '').includes('CANCEL');
        const qrSvg = (!isCancelled && window.SCQR && typeof window.SCQR.toSvg === 'function')
          ? `<div class="qr-box" style="margin:6px auto;">${window.SCQR.toSvg(String(b.qr_token || b.voucher_no), { size: 120 })}</div>`
          : '';
        return `
          <div class="trip-mobile-card" style="border-color:${isCancelled ? 'rgba(244,63,94,0.45)' : 'rgba(0,210,255,0.4)'};">
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <b style="color:var(--cyan);">🎫 ${esc(b.voucher_no)}</b>
              <span class="pill" style="${isCancelled ? 'color:#fda4af;border-color:#f43f5e;' : 'color:#34d399;border-color:#10b981;'}">${isCancelled ? (ar ? 'ملغى — احجز موعداً جديداً' : 'Cancelled — Rebook Anytime') : esc(b.boarding_status)}</span>
            </div>
            <div style="font-size:15.5px;font-weight:900;">${esc(b.trip_name)}</div>
            <div style="font-size:12.5px;color:#cbd5e1;">
              📅 <b>${esc(b.trip_date)}</b> • 🚐 Pickup: <b>${esc(b.pickup_time)}</b><br>
              🏨 ${esc(b.hotel_name || 'Sharm Hotel')} ${b.room_no ? '#' + esc(b.room_no) : ''} • 👥 ${Number(b.adults || 1)} Adults${Number(b.children || 0) > 0 ? ` + ${b.children} Kids` : ''}${Number(b.infants || 0) > 0 ? ` + ${b.infants} Infants` : ''}
            </div>
            ${qrSvg}
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:12.5px;border-top:1px solid rgba(148,163,184,0.14);padding-top:8px;">
              <span>Total: <b>€${fmt(b.total_eur, 0)}</b></span>
              <span class="${Number(b.rest_egp || 0) > 1 ? '' : 'pos'}" style="font-weight:800;">${isCancelled ? '—' : (Number(b.rest_egp || 0) > 1 ? `Due at Pickup: €${fmt(b.rest_eur, 0)}` : '✅ Paid')}</span>
            </div>
            <div style="display:flex;gap:6px;">
              <button type="button" class="btn btn-primary" style="flex:1;justify-content:center;" onclick="STATE.activeTab='guest_book_trips';renderApp();">🌴 ${ar ? 'احجز رحلة أخرى' : 'Book Another Trip'}</button>
              <a class="btn btn-emerald" target="_blank" rel="noopener" href="https://wa.me/${esc(waNum)}?text=${encodeURIComponent('Hi Sea College! Question about my booking ' + b.voucher_no)}">💬 WhatsApp</a>
            </div>
          </div>
        `;
      }).join('')}
    </div>
  `;
}

async function changeGuestCrmFilter(nextFilter) {
  STATE.guestCrmFilter = nextFilter;
  const res = await apiGet({ action: 'marketing_guest_accounts', filter: nextFilter, q: STATE.guestCrmSearch || '' });
  if (res && res.ok) STATE.guestCrm = res;
  renderApp();
}

let GUEST_CRM_SEARCH_TIMER = null;
function onGuestCrmSearchInput(val) {
  STATE.guestCrmSearch = val;
  if (GUEST_CRM_SEARCH_TIMER) clearTimeout(GUEST_CRM_SEARCH_TIMER);
  GUEST_CRM_SEARCH_TIMER = setTimeout(async () => {
    const res = await apiGet({ action: 'marketing_guest_accounts', filter: STATE.guestCrmFilter || 'all', q: STATE.guestCrmSearch || '' });
    if (res && res.ok) {
      STATE.guestCrm = res;
      renderApp();
    }
  }, 240);
}

function renderGuestCrmVaultTab(ar) {
  const data = STATE.guestCrm || {};
  const k = data.kpis || {};
  const guests = data.guests || [];
  const dispatches = data.email_dispatches || [];
  const curFilter = STATE.guestCrmFilter || 'all';
  const csvHref = `/handlers/erp_api.php?action=export_marketing_guests_csv&filter=${encodeURIComponent(curFilter)}`;

  const filters = [
    ['all', ar ? `🌟 كل العملاء الفعليين (${fmt(k.total_verified_guests)})` : `🌟 All Verified Guests (${fmt(k.total_verified_guests)})`],
    ['with_email', ar ? `✉️ لديهم إيميل (${fmt(k.guests_with_email)})` : `✉️ With Email (${fmt(k.guests_with_email)})`],
    ['without_email', ar ? '📭 بدون إيميل بعد' : '📭 Missing Email'],
    ['unpaid', ar ? `⏳ مؤكد بدون عربون (${fmt(k.unpaid_open_guests)})` : `⏳ Confirmed / 0 Deposit (${fmt(k.unpaid_open_guests)})`],
    ['cancelled', ar ? `🔄 ألغى الحجز — إعادة استهداف (${fmt(k.cancelled_winback_guests)})` : `🔄 Cancelled Win-Back (${fmt(k.cancelled_winback_guests)})`],
    ['paid', ar ? `💰 عملاء دافعون (${fmt(k.paid_vip_guests)})` : `💰 Paid VIPs (${fmt(k.paid_vip_guests)})`],
    ['repeat', ar ? `🔁 حجزوا أكثر من مرة (${fmt(k.repeat_app_bookers)})` : `🔁 Repeat Bookers (${fmt(k.repeat_app_bookers)})`]
  ];

  return `
    <div class="grid grid-kpi" style="margin-bottom:12px;">
      <div class="card">
        <div class="kpi-label">🔐 ${ar ? 'إجمالي حسابات العملاء الفعليين' : 'Verified Real Customer Accounts'}</div>
        <div class="kpi-val" style="color:var(--cyan);">${fmt(k.total_verified_guests)}</div>
        <div class="kpi-sub">${ar ? 'عملاء حقيقيون فقط بعد تأكيد الحجز (صفر حسابات وهمية)' : '100% Real Confirmed Customers (Zero Public Spam)'}</div>
      </div>
      <div class="card">
        <div class="kpi-label">✉️ ${ar ? 'إيميلات مسجلة للتسويق' : 'Captured Guest Emails'}</div>
        <div class="kpi-val pos">${fmt(k.guests_with_email)}</div>
        <div class="kpi-sub">📨 ${fmt(k.credentials_emails_sent)} ${ar ? 'رسالة بيانات دخول مرسلة' : 'App Login Emails Sent'}</div>
      </div>
      <div class="card">
        <div class="kpi-label">⏳ ${ar ? 'مؤكد بدون دفع / عربون صفر' : 'Confirmed Unpaid (0 Deposit)'}</div>
        <div class="kpi-val" style="color:#fcd34d;">${fmt(k.unpaid_open_guests)}</div>
        <div class="kpi-sub">${ar ? 'حسابهم نشط وجاهز لحجز رحلات قادمة' : 'Active app accounts retained pre-payment'}</div>
      </div>
      <div class="card">
        <div class="kpi-label">🔄 ${ar ? 'ألغوا الحجز (Win-Back CRM)' : 'Cancelled (Retained for Win-Back)'}</div>
        <div class="kpi-val" style="color:#fb7185;">${fmt(k.cancelled_winback_guests)}</div>
        <div class="kpi-sub">🔁 ${fmt(k.repeat_app_bookers)} ${ar ? 'عميل كرر الحجز من التطبيق' : 'Repeat App Bookers'}</div>
      </div>
    </div>

    <div class="card" style="margin-bottom:14px;">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
        <div>
          <h3 style="font-size:15.5px;">🎯 ${ar ? 'قاعدة بيانات العملاء الفعليين وحسابات تطبيق VIP للتسويق وإعادة الاستهداف' : 'Real-Customers App Vault — Emails, Win-Back Segments & Repeat Bookings'}</h3>
          <p style="font-size:12px;color:var(--muted);">
            ${ar ? 'يتم إنشاء حساب العميل والاحتفاظ به نشطاً بمجرد تأكيد المسوق للحجز حتى لو لم يدفع عربوناً أو ألغى الرحلة، لاستخدام البيانات لاحقاً بواسطة فريق التسويق.' : 'Accounts are created automatically when a Seller confirms a booking — and stay active even if the guest paid 0 deposit or cancelled, giving Marketing clean 1st-party customer data.'}
          </p>
        </div>
        <a class="btn btn-emerald" href="${esc(csvHref)}">📥 ${ar ? 'تصدير CSV لحملات التسويق' : 'Export Marketing CSV'}</a>
      </div>

      <div style="display:flex;gap:7px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">
        ${filters.map(([fk, fl]) => `
          <button type="button" class="btn ${curFilter === fk ? 'btn-primary' : ''}" style="padding:6px 11px;font-size:12px;" onclick="changeGuestCrmFilter('${fk}')">${esc(fl)}</button>
        `).join('')}
        <input type="search" style="max-width:250px;margin-inline-start:auto;" placeholder="${ar ? '🔍 بحث بالاسم، الإيميل، الهاتف، الفندق...' : '🔍 Search guest, email, phone, hotel...'}" value="${esc(STATE.guestCrmSearch || '')}" oninput="onGuestCrmSearchInput(this.value)">
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Guest &amp; App Login</th><th>Email &amp; WhatsApp</th><th>Hotel &amp; Nat.</th><th>Seller</th>
              <th>Latest Trip / Voucher</th><th>Segment / Lifecycle</th><th>Bookings</th><th>Booked / Paid (EGP)</th><th>Login Email</th>
            </tr>
          </thead>
          <tbody>
            ${!guests.length ? `<tr><td colspan="9" style="text-align:center;color:var(--muted);padding:18px;">No verified guest accounts match this filter yet.</td></tr>` : guests.map((g) => {
              const seg = String(g.segment || g.latest_booking_status || '');
              const isCanc = seg.includes('CANCEL');
              const isPaid = seg === 'PAID_VIP' || String(g.latest_booking_status || '') === 'CONFIRMED_PAID';
              const wd = waDigits(g.guest_phone);
              return `
                <tr>
                  <td>
                    <b>${esc(g.guest_name)}</b>
                    <div style="font-size:10.5px;color:#34d399;"><code>@${esc(g.username)}</code> • ${Number(g.account_is_active ?? 1) === 1 ? 'ACTIVE' : 'OFF'}</div>
                  </td>
                  <td>
                    ${g.guest_email ? `<div style="color:#93c5fd;font-weight:700;">✉️ ${esc(g.guest_email)}</div>` : `<div style="color:#f59e0b;font-size:11px;">📭 Optional email not added</div>`}
                    <div style="font-size:11px;color:var(--muted);">${esc(g.guest_phone || '—')} ${wd.length >= 8 ? `<a href="https://wa.me/${esc(wd)}" target="_blank" rel="noopener" style="color:#34d399;">💬 WA</a>` : ''}</div>
                  </td>
                  <td>${esc(g.hotel_name || '—')} ${g.room_no ? '#' + esc(g.room_no) : ''}<div style="font-size:10.5px;color:var(--muted);">${esc(g.nationality || '—')} (${esc(g.preferred_lang || 'en')})</div></td>
                  <td>${esc(g.seller_display_name || g.referred_by_seller || 'Direct')}</td>
                  <td><b>${esc(g.latest_voucher_no || '—')}</b><div style="font-size:10.5px;color:var(--muted);">${esc(g.latest_trip_name || '')} (${esc(g.latest_trip_date || '')})</div></td>
                  <td>
                    <span class="pill" style="${isCanc ? 'border-color:#f43f5e;color:#fda4af;' : (isPaid ? 'border-color:#10b981;color:#34d399;' : 'border-color:#f59e0b;color:#fde68a;')}">
                      ${esc(g.latest_booking_status || seg)}
                    </span>
                  </td>
                  <td class="num"><b>${fmt(g.total_bookings_count)}</b>${Number(g.cancelled_bookings_count || 0) > 0 ? `<div style="font-size:10px;color:#fb7185;">(${fmt(g.cancelled_bookings_count)} canc)</div>` : ''}</td>
                  <td class="num">${fmt(g.lifetime_booked_egp)} / <b class="pos">${fmt(g.lifetime_paid_egp)}</b></td>
                  <td>
                    <button type="button" class="btn ${Number(g.credentials_email_sent || 0) === 1 ? '' : 'btn-primary'}" style="padding:5px 9px;font-size:11px;" onclick="promptAndSendGuestLoginEmail('${esc(g.latest_voucher_no || '')}', '${esc(g.username)}', '${esc(g.guest_email || '')}')">
                      ${Number(g.credentials_email_sent || 0) === 1 ? '✅ Resend Login' : '📧 Add / Send Login'}
                    </button>
                  </td>
                </tr>
              `;
            }).join('')}
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <h3 style="font-size:14px;margin-bottom:8px;">📨 ${ar ? 'سجل إرسال إيميلات بيانات الدخول للعملاء' : 'Recent Guest Login Credentials Email Dispatch Log'} (${dispatches.length})</h3>
      <div class="table-wrap" style="max-height:220px;">
        <table>
          <thead><tr><th>Sent UTC</th><th>Guest Account</th><th>Recipient Email</th><th>Voucher</th><th>Subject</th></tr></thead>
          <tbody>
            ${!dispatches.length ? `<tr><td colspan="5" style="text-align:center;color:var(--muted);">No login credential emails dispatched yet.</td></tr>` : dispatches.map((d) => `
              <tr>
                <td>${esc(String(d.sent_at || '').slice(0, 19).replace('T', ' '))}</td>
                <td><code>@${esc(d.guest_username)}</code></td>
                <td style="color:#93c5fd;">${esc(d.recipient_email)}</td>
                <td><b>${esc(d.voucher_no || '—')}</b></td>
                <td>${esc(d.subject)}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

/* ================= v3.5 SUPPLIERS / VENDORS HUB & GRANULAR TRIP/SAFARI COST MATRIX ================= */
async function reloadSuppliersHub() {
  const res = await apiGet({
    action: 'suppliers_hub',
    category: STATE.suppliersCat || 'ALL',
    supplier: STATE.suppliersFilterSup || 'ALL',
    q: STATE.suppliersSearch || ''
  });
  if (res && res.ok) {
    STATE.suppliersHub = res;
  }
  renderApp();
}

function setSuppliersFilter(cat, sup) {
  if (cat !== null && cat !== undefined) STATE.suppliersCat = cat;
  if (sup !== null && sup !== undefined) STATE.suppliersFilterSup = sup;
  reloadSuppliersHub();
}

function renderSuppliersCostsHubTab(ar) {
  const d = STATE.suppliersHub || {};
  const k = d.kpis || {};
  const canEdit = !!d.can_edit_costs;
  const suppliers = d.suppliers || [];
  const costs = d.cost_items || [];
  const audits = d.audit_logs || [];
  const sub = STATE.suppliersSubTab || 'costs';

  const cats = [
    ['ALL', ar ? '🌐 كل الرحلات' : '🌐 All Trips & Variants'],
    ['DESERT_SAFARI', ar ? '🏜️ سفاري (موتوسيكل/باجي/جمل/نجوم/عشاء)' : '🏜️ Safari (Quad/Buggy/Camel/Star/Dinner)'],
    ['YACHT_SNORKELING', ar ? '🏝️ اليخوت والسنوركل' : '🏝️ Yachts & Snorkeling'],
    ['CAIRO_LUXOR', ar ? '✈️ القاهرة والأقصر والبتراء' : '✈️ Cairo, Luxor & Petra'],
    ['SINAI_DAHAB', ar ? '⛰️ كاترين ودهب ورأس محمد بر' : '⛰️ Catherine, Dahab & RM Bus'],
    ['DOLPHIN_SUB_DINNER', ar ? '🐬 الدولفين والغواصة والعشاء' : '🐬 Dolphin, Sub & Dinner Boat'],
    ['WATERSPORTS', ar ? '🪂 الألعاب المائية والباراشوت' : '🪂 Watersports & Parasailing'],
    ['DIVING_CHARTER', ar ? '🤿 الغوص وسيسل جورم' : '🤿 Diving & SS Thistlegorm'],
    ['BOAT_SHARING', ar ? '⛵ شيرنج المراكب' : '⛵ Boat Sharing'],
    ['TRANSFERS', ar ? '🚐 الترانسفير والباصات' : '🚐 Transfers'],
    ['DIVING_COURSES', ar ? '🎓 دورات PADI' : '🎓 PADI Courses']
  ];

  const basisLabel = (b, cap) => {
    const map = {
      PER_PAX: ar ? '👨‍👩‍👧 للفرد (بالغ/طفل/رضيع)' : '👨‍👩‍👧 Per PAX (Adl/Ch/Inf)',
      SINGLE_QUAD: ar ? '🏍️ موتوسيكل فردي (1 راكب)' : '🏍️ Single Quad (1P)',
      DOUBLE_QUAD: ar ? '🏍️🏍️ موتوسيكل زوجي (2 راكب)' : '🏍️🏍️ Double Quad (2P)',
      BUGGY_2PAX: ar ? '🏎️ باجي كار لشخصين (2P)' : '🏎️ Buggy 2-People',
      BUGGY_4PAX: ar ? '🚙 باجي عائلي 4 أفراد (4P)' : '🚙 Buggy 4-People',
      CAMEL_ADDON: ar ? '🐪 جمل مع السفاري (للفرد)' : '🐪 Camel w/ Safari',
      CAMEL_1HR: ar ? '🐪⏱️ جولة جمل ساعة' : '🐪⏱️ Camel 1h Tour',
      STAR_GAZING: ar ? '🔭 مراقبة النجوم' : '🔭 Star Gazing',
      BEDOUIN_DINNER: ar ? '🍖 عشاء بدوي عادي' : '🍖 Bedouin Dinner',
      VIP_BEDOUIN_DINNER: ar ? '👑 عشاء بدوي VIP' : '👑 VIP Bedouin Dinner',
      PRIVATE_GROUP: ar ? '🧭 جروب سفاري خاص' : '🧭 Private Guide',
      ECO_FEE_PAX: ar ? '🎫 رسم بيئة 1$ للفرد' : '🎫 $1/PAX Eco Fee',
      PER_UNIT: ar ? `🎯 للوحدة (${cap || 1} أفراد)` : `🎯 Per Unit (${cap || 1}P)`,
      PER_VEHICLE_LEG: ar ? `🚐 للباص / النقلة (${cap || 14} راكب)` : `🚐 Per Shuttle (${cap || 14}P)`
    };
    return map[b] || b;
  };

  let bodyHtml = '';
  if (sub === 'costs') {
    bodyHtml = `
      <div class="card" style="margin-bottom:12px;">
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px;">
          ${cats.map(([cId, cLbl]) => `
            <button type="button" class="btn ${(STATE.suppliersCat || 'ALL') === cId ? 'btn-primary' : ''}" style="padding:6px 10px;font-size:11.5px;" onclick="setSuppliersFilter('${cId}', null)">${esc(cLbl)}</button>
          `).join('')}
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:8px;align-items:center;">
          <select onchange="setSuppliersFilter(null, this.value)">
            <option value="ALL">${ar ? '🏭 كل الموردين (All Suppliers)' : '🏭 All Suppliers / Vendors'}</option>
            ${suppliers.map((s) => `<option value="${esc(s.supplier_code)}" ${(STATE.suppliersFilterSup || 'ALL') === s.supplier_code ? 'selected' : ''}>${esc(ar ? (s.name_ar || s.name_en) : s.name_en)} (${esc(s.supplier_code)})</option>`).join('')}
          </select>
          <div style="display:flex;gap:6px;">
            <input type="search" id="supCostSearchInp" value="${esc(STATE.suppliersSearch || '')}" placeholder="${ar ? 'ابحث باسم الرحلة أو Quad أو Buggy أو Camel...' : 'Search Quad, Buggy, Camel, Star, Cairo, Supplier...'}" onkeydown="if(event.key==='Enter'){STATE.suppliersSearch=this.value;reloadSuppliersHub();}">
            <button type="button" class="btn" onclick="STATE.suppliersSearch=document.getElementById('supCostSearchInp').value;reloadSuppliersHub();">🔍</button>
          </div>
          <div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap;">
            ${canEdit ? `<button type="button" class="btn btn-emerald" onclick="openTripCostModal(null)">➕ ${ar ? 'إضافة تكلفة رحلة / سفاري' : 'Add Trip / Safari Cost'}</button>` : ''}
            ${canEdit ? `<a class="btn" href="/handlers/erp_api.php?action=export_suppliers_costs_csv">📥 CSV</a>` : ''}
          </div>
        </div>
      </div>

      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
          <h3 style="font-size:15px;">📋 ${ar ? 'مصفوفة تكاليف الرحلات والسفاري المعتمدة من شيتات الحجز والحسابات' : 'Audited Trip & Desert Safari Supplier Cost Matrix'} (${costs.length})</h3>
          <span style="font-size:11.5px;color:#fde68a;">${canEdit ? (ar ? '✅ صلاحية المحاسبة مفعّلة: اضغط ✏️ لتحديث أي تكلفة فوراً' : '✅ Accounting Authority Active: Tap ✏️ Edit to update any cost live') : (ar ? '👁️ عرض فقط (التعديل متاح لقسم الحسابات والإدارة)' : '👁️ Read-Only (Cost updates restricted to Accounting & Owners)')}</span>
        </div>
        <div class="table-wrap" style="max-height:560px;">
          <table>
            <thead>
              <tr>
                <th>${ar ? 'الرحلة / نوع السفاري' : 'Trip / Safari Variant'}</th>
                <th>${ar ? 'المورد' : 'Supplier'}</th>
                <th>${ar ? 'وحدة التسعير' : 'Pricing Basis'}</th>
                <th class="num">${ar ? 'تكلفة البالغ / الوحدة' : 'Adult / Unit Cost'}</th>
                <th class="num">${ar ? 'تكلفة الطفل' : 'Child Cost'}</th>
                <th class="num">${ar ? 'تكلفة الرضيع' : 'Infant Cost'}</th>
                <th class="num">${ar ? 'سعر البيع (€)' : 'Sell Tariff (€)'}</th>
                <th>${ar ? 'نظام العمولة' : 'Mode'}</th>
                ${canEdit ? `<th>${ar ? 'تحديث المحاسبة' : 'Update'}</th>` : ''}
              </tr>
            </thead>
            <tbody>
              ${!costs.length ? `<tr><td colspan="9" style="text-align:center;color:var(--muted);padding:18px;">No matching cost items found.</td></tr>` : costs.map((c) => {
                const origBadge = c.cost_currency && c.cost_currency !== 'EGP'
                  ? `<div style="font-size:10.5px;color:#7dd3fc;">(${fmt(c.adult_cost_orig || c.unit_cost_orig, 1)} ${esc(c.cost_currency)})</div>`
                  : '';
                const mainCostEgp = Number(c.pricing_basis !== 'PER_PAX' && Number(c.unit_cost_egp) > 0 ? c.unit_cost_egp : c.adult_cost_egp);
                const encoded = encodeURIComponent(JSON.stringify(c));
                return `
                  <tr>
                    <td>
                      <div style="font-weight:800;color:#f8fafc;">${esc(ar ? (c.name_ar || c.name_en) : c.name_en)}</div>
                      <div style="font-size:10.5px;color:var(--muted);"><code>${esc(c.item_code)}</code> ${c.sheet_formula_note ? '• ' + esc(c.sheet_formula_note) : ''}</div>
                    </td>
                    <td>
                      <button type="button" class="pill" style="font-size:10.5px;cursor:pointer;background:rgba(0,210,255,0.12);border-color:rgba(0,210,255,0.4);color:#7dd3fc;" onclick="openEntityDossier('SUPPLIER', '${esc(c.supplier_code)}')" title="${ar ? 'افتح كشف حساب المورد والدفعات' : 'Open Supplier Statement & Installments'}">📒 ${esc(ar ? (c.supplier_name_ar || c.supplier_name_en) : c.supplier_name_en)}</button>
                    </td>
                    <td style="font-size:11.5px;font-weight:700;color:#fde68a;">${esc(basisLabel(c.pricing_basis, c.capacity_pax))}</td>
                    <td class="num"><b style="color:#fca5a5;">${fmt(mainCostEgp)} EGP</b>${origBadge}</td>
                    <td class="num">${Number(c.child_cost_egp) > 0 ? `${fmt(c.child_cost_egp)} EGP` : '<span style="color:var(--muted);">0</span>'}</td>
                    <td class="num">${Number(c.infant_cost_egp) > 0 ? `${fmt(c.infant_cost_egp)} EGP` : '<span class="pos">FREE (0)</span>'}</td>
                    <td class="num"><b class="pos">€${fmt(c.adult_sell_eur || c.unit_sell_eur, 0)}</b>${Number(c.child_sell_eur) > 0 ? `<div style="font-size:10px;color:var(--muted);">Ch €${fmt(c.child_sell_eur, 0)}</div>` : ''}</td>
                    <td><span class="pill" style="${c.sale_mode === 'OUT_SIDE' ? 'border-color:#f59e0b;color:#fde68a;' : ''}">${esc(c.sale_mode)}</span></td>
                    ${canEdit ? `<td><button type="button" class="btn btn-amber" style="padding:5px 10px;font-size:11.5px;" onclick="openTripCostModal('${encoded}')">✏️ ${ar ? 'تعديل' : 'Edit'}</button></td>` : ''}
                  </tr>
                `;
              }).join('')}
            </tbody>
          </table>
        </div>
      </div>
    `;
  } else if (sub === 'vendors') {
    bodyHtml = `
      <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
        <h3 style="font-size:15px;">🏭 ${ar ? 'دليل الموردين وشركات التنفيذ وحساباتهم والدفعات المسددة (شيت الموردين 2026)' : 'Active Suppliers & Vendors — Click Any Supplier for Full Statement & Installments'} (${suppliers.length})</h3>
        ${canEdit ? `<button type="button" class="btn btn-primary" onclick="openSupplierModal(null)">➕ ${ar ? 'إضافة مورد جديد' : 'Add New Supplier'}</button>` : ''}
      </div>
      <div class="grid grid-2">
        ${suppliers.map((s) => {
          const finalBal = Number(s.final_balance_egp !== undefined ? s.final_balance_egp : -Number(s.current_balance_egp || 0));
          const instPaid = Number(s.total_installments_egp || 0);
          const totAcc = Number(s.total_accrued_egp || s.booked_cogs_egp || 0);
          const wd = waDigits(s.whatsapp || s.phone || '');
          const encS = encodeURIComponent(JSON.stringify(s));
          const isInternal = s.supplier_code === 'SEA_COLLEGE';
          return `
            <div class="card" style="display:flex;flex-direction:column;justify-content:space-between;gap:10px;border-color:${finalBal > 0 ? 'rgba(245,158,11,0.38)' : 'var(--border)'};">
              <div>
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                  <div style="${!isInternal ? 'cursor:pointer;' : ''}" ${!isInternal ? `onclick="openEntityDossier('SUPPLIER', '${esc(s.supplier_code)}')"` : ''}>
                    <div style="font-size:15px;font-weight:900;color:#f8fafc;${!isInternal ? 'text-decoration:underline;text-decoration-color:rgba(0,210,255,0.5);' : ''}">${esc(ar ? (s.name_ar || s.name_en) : s.name_en)}</div>
                    <div style="font-size:11px;color:var(--cyan);"><code>${esc(s.supplier_code)}</code> • Sheet Tab: <b>${esc(s.sheet_tab_name || '—')}</b> • Currency: <b>${esc(s.settlement_currency || 'EGP')}</b></div>
                  </div>
                  <span class="pill">${esc(s.category_code)}</span>
                </div>
                <div style="font-size:12px;color:#cbd5e1;margin-top:8px;line-height:1.45;">${esc(s.notes || '—')}</div>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:10px;padding:8px;border-radius:10px;background:rgba(6,21,38,0.68);border:1px solid var(--border);">
                  <div>
                    <div style="font-size:10px;color:var(--muted);">${ar ? 'إجمالي المستحق' : 'Total Billed'}</div>
                    <div style="font-size:13px;font-weight:800;color:#fde68a;">${fmt(totAcc)} EGP</div>
                  </div>
                  <div>
                    <div style="font-size:10px;color:var(--muted);">${ar ? 'العربون والأقساط المسددة' : 'Deposits & Installments'}</div>
                    <div style="font-size:13px;font-weight:800;color:#34d399;">${fmt(instPaid)} EGP</div>
                  </div>
                  <div>
                    <div style="font-size:10px;color:var(--muted);">${ar ? 'الرصيد النهائي الصافي' : 'Final Net Balance'}</div>
                    <div style="font-size:13.5px;font-weight:900;color:${finalBal > 0 ? '#fda4af' : '#34d399'};">${fmt(finalBal)} EGP</div>
                  </div>
                </div>
              </div>
              <div style="display:flex;justify-content:space-between;align-items:center;gap:6px;flex-wrap:wrap;border-top:1px solid var(--border);padding-top:8px;">
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                  ${!isInternal ? `<button type="button" class="btn btn-primary" style="padding:5px 10px;font-size:11.5px;font-weight:900;" onclick="openEntityDossier('SUPPLIER', '${esc(s.supplier_code)}')">📒 ${ar ? 'كشف الحساب والدفعات' : 'Statement & Installments'}</button>` : ''}
                  ${s.phone ? `<a class="btn" style="padding:4px 9px;font-size:11px;" href="tel:${esc(s.phone)}">📞 ${esc(s.contact_person || s.phone)}</a>` : ''}
                  ${wd.length >= 8 ? `<a class="btn btn-emerald" style="padding:4px 9px;font-size:11px;" href="https://wa.me/${esc(wd)}" target="_blank" rel="noopener">💬 WhatsApp</a>` : ''}
                  <button type="button" class="btn" style="padding:4px 9px;font-size:11px;" onclick="STATE.suppliersSubTab='costs';setSuppliersFilter('ALL','${esc(s.supplier_code)}');">📋 ${ar ? 'أسعار المورد' : 'View Rates'}</button>
                </div>
                ${canEdit ? `<button type="button" class="btn btn-amber" style="padding:4px 9px;font-size:11px;" onclick="openSupplierModal('${encS}')">✏️ ${ar ? 'تعديل' : 'Edit'}</button>` : ''}
              </div>
            </div>
          `;
        }).join('')}
      </div>
    `;
  } else {
    bodyHtml = `
      <div class="card">
        <h3 style="font-size:15px;margin-bottom:10px;">🛡️ ${ar ? 'سجل الرقابة المحاسبية لتعديلات تكاليف الرحلات والموردين' : 'Immutable Accounting Cost Update Audit Log'} (${audits.length})</h3>
        <div class="table-wrap" style="max-height:480px;">
          <table>
            <thead>
              <tr>
                <th>UTC Timestamp</th>
                <th>Changed By</th>
                <th>Item / Trip</th>
                <th>Supplier</th>
                <th class="num">Adult / Unit Cost (Prev → New)</th>
                <th class="num">Child Cost (Prev → New)</th>
                <th>Reason</th>
              </tr>
            </thead>
            <tbody>
              ${!audits.length ? `<tr><td colspan="7" style="text-align:center;color:var(--muted);padding:16px;">No cost changes logged yet — initial tariff seeded from 2026 الموردين &amp; RESERVATION sheets.</td></tr>` : audits.map((a) => `
                <tr>
                  <td>${esc(String(a.changed_at || '').slice(0, 19).replace('T', ' '))}</td>
                  <td><b>@${esc(a.changed_by)}</b> <span style="font-size:10px;color:var(--muted);">(${esc(a.changed_by_role)})</span></td>
                  <td><b>${esc(a.item_name_en || a.item_code)}</b><div style="font-size:10px;color:var(--muted);"><code>${esc(a.item_code)}</code></div></td>
                  <td><span class="pill">${esc(a.supplier_code)}</span></td>
                  <td class="num">${fmt(a.prev_adult_cost_egp || a.prev_unit_cost_egp)} → <b class="pos">${fmt(a.new_adult_cost_egp || a.new_unit_cost_egp)} EGP</b></td>
                  <td class="num">${fmt(a.prev_child_cost_egp)} → <b>${fmt(a.new_child_cost_egp)} EGP</b></td>
                  <td>${esc(a.reason_notes || '')}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>
    `;
  }

  return `
    <div class="grid grid-kpi" style="margin-bottom:14px;">
      <div class="card">
        <div class="kpi-label">🏭 ${ar ? 'الموردين المعتمدين' : 'Active Suppliers & Vendors'}</div>
        <div class="kpi-val">${fmt(k.total_suppliers || 0)}</div>
        <div class="kpi-sub">Bakar Safari • A.Saad • One Tour • Dolphin • Sub • Sharmina</div>
      </div>
      <div class="card">
        <div class="kpi-label">📋 ${ar ? 'بنود تكلفة الرحلات' : 'Trip & Variant Cost SKUs'}</div>
        <div class="kpi-val" style="color:var(--cyan);">${fmt(k.total_cost_items || 0)}</div>
        <div class="kpi-sub">${ar ? 'بالغ / طفل / رضيع + وحدات السفاري' : 'Adult / Child / Infant + Vehicle Units'}</div>
      </div>
      <div class="card">
        <div class="kpi-label">🏜️ ${ar ? 'خيارات السفاري والبدوي' : 'Safari & Bedouin Variants'}</div>
        <div class="kpi-val" style="color:#fde68a;">${fmt(k.safari_variants_count || 0)}</div>
        <div class="kpi-sub">1S/2D Quad • Buggy 2P/4P • Camel • 1h • Star • VIP Dinner</div>
      </div>
      <div class="card">
        <div class="kpi-label">💳 ${ar ? 'إجمالي مستحقات الموردين (AP)' : 'Net Suppliers Payable (AP)'}</div>
        <div class="kpi-val" style="color:#fda4af;">${fmt(k.net_ap_payable_egp || 0)} EGP</div>
        <div class="kpi-sub">${ar ? 'مربوط بشيت الموردين والحسابات' : 'Synced with AP Counterparties'}</div>
      </div>
    </div>

    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
      <button type="button" class="btn ${sub === 'costs' ? 'btn-primary' : ''}" onclick="STATE.suppliersSubTab='costs';renderApp();">🏜️🛥️ ${ar ? 'مصفوفة تكاليف الرحلات والسفاري (تحديث المحاسبة)' : 'Trip & Safari Cost Matrix (Accounting Editable)'}</button>
      <button type="button" class="btn ${sub === 'vendors' ? 'btn-primary' : ''}" onclick="STATE.suppliersSubTab='vendors';renderApp();">🏭 ${ar ? 'دليل وحسابات الموردين' : 'Suppliers & Vendors Directory'} (${suppliers.length})</button>
      <button type="button" class="btn ${sub === 'audit' ? 'btn-primary' : ''}" onclick="STATE.suppliersSubTab='audit';renderApp();">🛡️ ${ar ? 'سجل تعديلات التكلفة' : 'Cost Update Audit Log'} (${audits.length})</button>
    </div>

    ${bodyHtml}
  `;
}

function syncTripCostBasisFields() {
  const b = document.getElementById('tcmBasis').value;
  const capEl = document.getElementById('tcmCapPax');
  if (b === 'DOUBLE_QUAD' || b === 'BUGGY_2PAX') capEl.value = '2';
  else if (b === 'BUGGY_4PAX') capEl.value = '4';
  else if (b === 'SINGLE_QUAD' || b === 'PER_PAX' || b === 'CAMEL_ADDON' || b === 'CAMEL_1HR' || b === 'STAR_GAZING' || b === 'BEDOUIN_DINNER' || b === 'VIP_BEDOUIN_DINNER') capEl.value = '1';
}

function openTripCostModal(encodedRow) {
  const d = STATE.suppliersHub || {};
  const sups = d.suppliers || [];
  const mt = d.master_trips || [];
  const row = encodedRow ? JSON.parse(decodeURIComponent(encodedRow)) : null;

  const supSel = document.getElementById('tcmSupplier');
  supSel.innerHTML = sups.map((s) => `<option value="${esc(s.supplier_code)}">${esc(s.name_en)} (${esc(s.supplier_code)})</option>`).join('');

  const mtSel = document.getElementById('tcmLinkedTrip');
  mtSel.innerHTML = `<option value="">— Standalone Safari Variant / Add-On (No Master Link) —</option>`
    + mt.map((m) => `<option value="${esc(m.trip_code)}">${esc(m.unified_name_en)} [${esc(m.trip_code)}]</option>`).join('');

  document.getElementById('tcmTitle').textContent = row
    ? `✏️ Update Supplier Cost: ${row.name_en}`
    : '➕ Add New Trip or Safari Variant Cost';

  document.getElementById('tcmItemCode').value = row ? row.item_code : '';
  document.getElementById('tcmItemCode').readOnly = !!row;
  mtSel.value = row ? (row.linked_trip_code || '') : '';
  document.getElementById('tcmNameEn').value = row ? row.name_en : '';
  document.getElementById('tcmNameAr').value = row ? row.name_ar : '';
  document.getElementById('tcmCatGrp').value = row ? row.category_group : 'DESERT_SAFARI';
  supSel.value = row ? row.supplier_code : 'AP_BAKAR';
  document.getElementById('tcmBasis').value = row ? row.pricing_basis : 'PER_PAX';
  document.getElementById('tcmCapPax').value = String(row ? (row.capacity_pax || 1) : 1);
  document.getElementById('tcmCurr').value = row ? (row.cost_currency || 'EGP') : 'EGP';

  const curr = row ? (row.cost_currency || 'EGP') : 'EGP';
  document.getElementById('tcmAdultCost').value = String(row ? (curr !== 'EGP' && Number(row.adult_cost_orig) > 0 ? row.adult_cost_orig : row.adult_cost_egp) : 0);
  document.getElementById('tcmChildCost').value = String(row ? (curr !== 'EGP' && Number(row.child_cost_orig) > 0 ? row.child_cost_orig : row.child_cost_egp) : 0);
  document.getElementById('tcmInfantCost').value = String(row ? (curr !== 'EGP' && Number(row.infant_cost_orig) > 0 ? row.infant_cost_orig : row.infant_cost_egp) : 0);
  document.getElementById('tcmUnitCost').value = String(row ? (curr !== 'EGP' && Number(row.unit_cost_orig) > 0 ? row.unit_cost_orig : row.unit_cost_egp) : 0);

  document.getElementById('tcmAdultEur').value = String(row ? (row.adult_sell_eur || row.unit_sell_eur || 0) : 0);
  document.getElementById('tcmChildEur').value = String(row ? (row.child_sell_eur || 0) : 0);
  document.getElementById('tcmInfantEur').value = String(row ? (row.infant_sell_eur || 0) : 0);
  document.getElementById('tcmSaleMode').value = row ? (row.sale_mode || 'OUT_SIDE') : 'OUT_SIDE';
  document.getElementById('tcmIsSafariComp').checked = row ? Number(row.is_safari_component || 0) === 1 : false;
  document.getElementById('tcmFormulaNote').value = row ? (row.sheet_formula_note || '') : '';
  document.getElementById('tcmReason').value = 'Updated by Accounting per latest supplier tariff';

  document.getElementById('tripCostModal').style.display = 'flex';
}

async function submitTripCostForm(e) {
  e.preventDefault();
  const curr = document.getElementById('tcmCurr').value || 'EGP';
  const adVal = Number(document.getElementById('tcmAdultCost').value || 0);
  const chVal = Number(document.getElementById('tcmChildCost').value || 0);
  const inVal = Number(document.getElementById('tcmInfantCost').value || 0);
  const unVal = Number(document.getElementById('tcmUnitCost').value || adVal);
  const payload = {
    action: 'save_trip_cost',
    item_code: document.getElementById('tcmItemCode').value.trim(),
    linked_trip_code: document.getElementById('tcmLinkedTrip').value,
    name_en: document.getElementById('tcmNameEn').value.trim(),
    name_ar: document.getElementById('tcmNameAr').value.trim(),
    category_group: document.getElementById('tcmCatGrp').value,
    supplier_code: document.getElementById('tcmSupplier').value,
    pricing_basis: document.getElementById('tcmBasis').value,
    capacity_pax: Number(document.getElementById('tcmCapPax').value || 1),
    cost_currency: curr,
    adult_cost_orig: adVal,
    child_cost_orig: chVal,
    infant_cost_orig: inVal,
    unit_cost_orig: unVal,
    adult_sell_eur: Number(document.getElementById('tcmAdultEur').value || 0),
    child_sell_eur: Number(document.getElementById('tcmChildEur').value || 0),
    infant_sell_eur: Number(document.getElementById('tcmInfantEur').value || 0),
    unit_sell_eur: Number(document.getElementById('tcmAdultEur').value || 0),
    sale_mode: document.getElementById('tcmSaleMode').value,
    is_safari_component: document.getElementById('tcmIsSafariComp').checked ? 1 : 0,
    sheet_formula_note: document.getElementById('tcmFormulaNote').value.trim(),
    reason_notes: document.getElementById('tcmReason').value.trim()
  };
  if (curr === 'EGP') {
    payload.adult_cost_egp = adVal;
    payload.child_cost_egp = chVal;
    payload.infant_cost_egp = inVal;
    payload.unit_cost_egp = unVal;
  }
  const res = await apiPost(payload);
  if (!res || !res.ok) {
    alert((res && res.error) || 'Failed to save supplier cost');
    return;
  }
  document.getElementById('tripCostModal').style.display = 'none';
  STATE.sellerCatalog = null; // refresh live catalog costs too!
  showToast('✅ Supplier cost saved & synced across ERP!');
  await reloadSuppliersHub();
}

function openSupplierModal(encodedSup) {
  const s = encodedSup ? JSON.parse(decodeURIComponent(encodedSup)) : null;
  document.getElementById('semTitle').textContent = s ? `✏️ Edit Supplier: ${s.name_en}` : '➕ Add New Supplier / Vendor';
  document.getElementById('semCode').value = s ? s.supplier_code : '';
  document.getElementById('semCode').readOnly = !!s;
  document.getElementById('semCat').value = s ? (s.category_code || 'DESERT_SAFARI') : 'DESERT_SAFARI';
  document.getElementById('semNameEn').value = s ? s.name_en : '';
  document.getElementById('semNameAr').value = s ? s.name_ar : '';
  document.getElementById('semContact').value = s ? (s.contact_person || '') : '';
  document.getElementById('semPhone').value = s ? (s.phone || '') : '';
  document.getElementById('semCurr').value = s ? (s.settlement_currency || 'EGP') : 'EGP';
  document.getElementById('semSheetTab').value = s ? (s.sheet_tab_name || '') : '';
  document.getElementById('semOpenBal').value = String(s ? (s.opening_balance_egp || 0) : 0);
  document.getElementById('semCurrBal').value = String(s ? (s.current_balance_egp || 0) : 0);
  document.getElementById('semNotes').value = s ? (s.notes || '') : '';
  document.getElementById('supplierEditModal').style.display = 'flex';
}

async function submitSupplierForm(e) {
  e.preventDefault();
  const res = await apiPost({
    action: 'save_supplier',
    supplier_code: document.getElementById('semCode').value.trim(),
    category_code: document.getElementById('semCat').value,
    name_en: document.getElementById('semNameEn').value.trim(),
    name_ar: document.getElementById('semNameAr').value.trim(),
    contact_person: document.getElementById('semContact').value.trim(),
    phone: document.getElementById('semPhone').value.trim(),
    settlement_currency: document.getElementById('semCurr').value,
    sheet_tab_name: document.getElementById('semSheetTab').value.trim(),
    opening_balance_egp: Number(document.getElementById('semOpenBal').value || 0),
    current_balance_egp: Number(document.getElementById('semCurrBal').value || 0),
    notes: document.getElementById('semNotes').value.trim()
  });
  if (!res || !res.ok) {
    alert((res && res.error) || 'Failed to save supplier');
    return;
  }
  document.getElementById('supplierEditModal').style.display = 'none';
  showToast('🏭 Supplier saved to directory & AP ledger!');
  await reloadSuppliersHub();
}

/* ================= v3.5 STAFF BONUS & ALLOWANCE / ADVANCE MODULE ================= */
async function openStaffRewardsTab() {
  STATE.activeTab = 'staff_rewards';
  STATE.staffRewards = null;
  return renderApp();
}

async function reloadStaffRewards() {
  const res = await apiGet({
    action: 'staff_bonuses_allowances',
    type: STATE.staffRewardsType || 'ALL',
    month: STATE.staffRewardsMonth || 'ALL'
  });
  if (res && res.ok) {
    STATE.staffRewards = res;
  }
  renderApp();
}

function renderStaffRewardsTab(ar) {
  const d = STATE.staffRewards || {};
  const k = d.kpis || {};
  const canManage = !!d.can_manage_all;
  const canBonus = !!d.can_award_bonus;
  const rows = d.items || [];
  const tf = STATE.staffRewardsType || 'ALL';

  return `
    <div class="grid grid-kpi" style="margin-bottom:14px;">
      <div class="card" style="border-color:rgba(251,191,36,0.4);">
        <div class="kpi-label">🎁 ${ar ? 'إجمالي المكافآت المعتمدة (Bonuses)' : 'Approved Staff Bonuses'}</div>
        <div class="kpi-val" style="color:#fde68a;">${fmt(k.total_bonuses_egp || 0)} EGP</div>
        <div class="kpi-sub">${ar ? 'مكافآت التميز والتارجت وتقييم الضيوف' : 'Performance, 5★ Reviews & Target Rewards'}</div>
      </div>
      <div class="card" style="border-color:rgba(16,185,129,0.4);">
        <div class="kpi-label">🍱 ${ar ? 'البدلات المعتمدة (Allowances)' : 'Approved Staff Allowances'}</div>
        <div class="kpi-val" style="color:#34d399;">${fmt(k.total_allowances_egp || 0)} EGP</div>
        <div class="kpi-sub">${ar ? 'بدل انتقالات • وجبات • سكن • شيفت إضافي' : 'Transport • Meals • Housing • Extra Shifts'}</div>
      </div>
      <div class="card">
        <div class="kpi-label">💵 ${ar ? 'السلف المنصرفة (Advances)' : 'Approved Salary Advances'}</div>
        <div class="kpi-val" style="color:var(--cyan);">${fmt(k.total_advances_egp || 0)} EGP</div>
        <div class="kpi-sub">${ar ? 'مرتبطة تلقائياً بشيت المرتبات والخزينة' : 'Linked to Monthly Payroll & Cash Safe'}</div>
      </div>
      <div class="card">
        <div class="kpi-label">⏳ ${ar ? 'طلبات بانتظار الاعتماد' : 'Pending Staff Requests'}</div>
        <div class="kpi-val" style="color:${Number(k.pending_count || 0) > 0 ? '#f59e0b' : '#94a3b8'};">${fmt(k.pending_count || 0)}</div>
        <div class="kpi-sub">${fmt(k.approved_count || 0)} ${ar ? 'عملية معتمدة' : 'approved records'}</div>
      </div>
    </div>

    <div class="card" style="margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
      <div style="display:flex;gap:6px;flex-wrap:wrap;">
        <button type="button" class="btn ${tf === 'ALL' ? 'btn-primary' : ''}" onclick="STATE.staffRewardsType='ALL';reloadStaffRewards();">${ar ? 'الكل' : 'All'}</button>
        <button type="button" class="btn ${tf === 'BONUS' ? 'btn-primary' : ''}" onclick="STATE.staffRewardsType='BONUS';reloadStaffRewards();">🎁 ${ar ? 'المكافآت (Bonus)' : 'Bonuses'}</button>
        <button type="button" class="btn ${tf === 'ALLOWANCE' ? 'btn-primary' : ''}" onclick="STATE.staffRewardsType='ALLOWANCE';reloadStaffRewards();">🍱 ${ar ? 'البدلات (Allowance)' : 'Allowances'}</button>
        <button type="button" class="btn ${tf === 'ADVANCE' ? 'btn-primary' : ''}" onclick="STATE.staffRewardsType='ADVANCE';reloadStaffRewards();">💵 ${ar ? 'السلف (Advance)' : 'Advances'}</button>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        ${canBonus ? `<button type="button" class="btn btn-amber" onclick="openStaffRewardModal('BONUS')">🏆 ${ar ? '+ صرف مكافأة لموظف (Bonus)' : '+ Award Staff Bonus'}</button>` : ''}
        <button type="button" class="btn btn-emerald" onclick="openStaffRewardModal('ALLOWANCE')">🍱 ${canManage ? (ar ? '+ تسجيل بدل / سلفة موظف' : '+ Record Allowance / Advance') : (ar ? '+ طلب بدل أو سلفة' : '+ Request Allowance / Advance')}</button>
      </div>
    </div>

    <div class="card">
      <h3 style="font-size:15px;margin-bottom:10px;">📋 ${canManage ? (ar ? 'سجل مكافآت وبدلات وسلف جميع الموظفين' : 'All Staff Bonuses, Allowances & Salary Advances Ledger') : (ar ? 'سجل مكافآتي وبدلاتي الخاصة' : 'My Personal Bonuses & Allowances')} (${rows.length})</h3>
      <div class="table-wrap" style="max-height:520px;">
        <table>
          <thead>
            <tr>
              <th>${ar ? 'التاريخ' : 'Date'}</th>
              <th>${ar ? 'الموظف' : 'Staff Member'}</th>
              <th>${ar ? 'النوع' : 'Type'}</th>
              <th>${ar ? 'التصنيف والسبب' : 'Category & Reason'}</th>
              <th class="num">${ar ? 'المبلغ (EGP)' : 'Amount (EGP)'}</th>
              <th>${ar ? 'طريقة الصرف' : 'Payout Safe'}</th>
              <th>${ar ? 'الحالة' : 'Status'}</th>
              ${canManage ? `<th>${ar ? 'اعتماد المحاسبة' : 'Action'}</th>` : ''}
            </tr>
          </thead>
          <tbody>
            ${!rows.length ? `<tr><td colspan="8" style="text-align:center;color:var(--muted);padding:18px;">${ar ? 'لا توجد مكافآت أو بدلات مسجلة بعد.' : 'No staff bonuses or allowances recorded yet.'}</td></tr>` : rows.map((r) => {
              const isPend = r.status === 'REQUESTED';
              const isRej = r.status === 'REJECTED';
              const typeBadge = r.entry_type === 'BONUS'
                ? `<span class="pill" style="background:rgba(245,158,11,0.18);border-color:#f59e0b;color:#fde68a;">🎁 BONUS</span>`
                : (r.entry_type === 'ALLOWANCE'
                    ? `<span class="pill" style="background:rgba(16,185,129,0.16);border-color:#10b981;color:#34d399;">🍱 ALLOWANCE</span>`
                    : `<span class="pill">💵 ADVANCE</span>`);
              return `
                <tr>
                  <td><b>${esc(r.tx_date)}</b><div style="font-size:10px;color:var(--muted);">${esc(r.period_month)}</div></td>
                  <td>
                    <div style="font-weight:800;cursor:pointer;color:#7dd3fc;text-decoration:underline;" onclick="openTeamProfileDossier('${esc(r.emp_username)}', '${esc(r.role_code || 'STAFF')}')" title="${ar ? 'افتح كشف حساب الموظف الكامل' : 'Open full financial statement'}">${esc(ar ? (r.emp_name_ar || r.emp_name_en) : r.emp_name_en)}</div>
                    <div style="font-size:10.5px;color:var(--muted);">@${esc(r.emp_username)} • ${esc(r.branch_code)}</div>
                  </td>
                  <td>${typeBadge}</td>
                  <td>
                    <div style="font-size:11px;font-weight:800;color:#7dd3fc;">${esc(r.category)}</div>
                    <div style="font-size:12px;">${esc(ar ? (r.reason_ar || r.reason_en) : r.reason_en)}</div>
                  </td>
                  <td class="num"><b style="font-size:14px;color:${r.entry_type === 'BONUS' ? '#fde68a' : '#34d399'};">${fmt(r.amount_egp)} EGP</b></td>
                  <td style="font-size:11.5px;">${esc(r.safe_code === 'NONE_PAYROLL' ? (ar ? 'شيت المرتبات' : 'Monthly Payroll') : r.safe_code)}</td>
                  <td>
                    <span class="pill" style="${isPend ? 'border-color:#f59e0b;color:#fde68a;' : (isRej ? 'border-color:#f43f5e;color:#fda4af;' : 'border-color:#10b981;color:#34d399;')}">
                      ${esc(r.status)}
                    </span>
                    ${r.approved_by ? `<div style="font-size:10px;color:var(--muted);">by @${esc(r.approved_by)}</div>` : ''}
                  </td>
                  ${canManage ? `
                    <td>
                      ${isPend ? `
                        <div style="display:flex;gap:4px;flex-wrap:wrap;">
                          <button type="button" class="btn btn-emerald" style="padding:4px 8px;font-size:11px;" onclick="decideStaffReward(${Number(r.id)}, 'APPROVED_PAID', '${esc(r.safe_code || 'SAFE_CYRENE')}')">✅ Pay</button>
                          <button type="button" class="btn" style="padding:4px 8px;font-size:11px;" onclick="decideStaffReward(${Number(r.id)}, 'POSTED_TO_PAYROLL', 'NONE_PAYROLL')">📋 Payroll</button>
                          <button type="button" class="btn" style="padding:4px 8px;font-size:11px;border-color:#f43f5e;color:#fda4af;" onclick="decideStaffReward(${Number(r.id)}, 'REJECTED', 'NONE_PAYROLL')">✕</button>
                        </div>
                      ` : '<span style="font-size:11px;color:var(--muted);">✓ Done</span>'}
                    </td>
                  ` : ''}
                </tr>
              `;
            }).join('')}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

function syncStaffRewardTypeUI() {
  const d = STATE.staffRewards || {};
  const canManage = !!d.can_manage_all;
  const canBonus = !!d.can_award_bonus;
  const tEl = document.getElementById('srmEntryType');
  if (!canBonus && tEl.value === 'BONUS') {
    tEl.value = 'ALLOWANCE';
  }
  const isBonus = tEl.value === 'BONUS';
  const celWrap = document.getElementById('srmInboxCelebrateWrap');
  if (celWrap) {
    celWrap.style.display = (isBonus && hasPerm('bi.view_executive')) ? 'block' : 'none';
  }
  const safeWrap = document.getElementById('srmSafeWrap');
  if (safeWrap) {
    safeWrap.style.display = canManage ? 'block' : 'none';
  }
}

function applyStaffRewardPreset(eType, cat, rEn, rAr) {
  const d = STATE.staffRewards || {};
  if (eType === 'BONUS' && !d.can_award_bonus) return;
  document.getElementById('srmEntryType').value = eType;
  document.getElementById('srmCategory').value = cat;
  document.getElementById('srmReasonEn').value = rEn;
  document.getElementById('srmReasonAr').value = rAr;
  syncStaffRewardTypeUI();
}

function openStaffRewardModal(defaultType) {
  const d = STATE.staffRewards || {};
  const canManage = !!d.can_manage_all;
  const canBonus = !!d.can_award_bonus;
  const staff = d.staff_directory || [];
  const empSel = document.getElementById('srmEmpUser');
  empSel.innerHTML = staff.map((s) => `<option value="${esc(s.username)}">${esc(s.display_name_en)} (${esc(s.role_code)} • ${esc(s.branch_scope || 'ALL')})</option>`).join('');
  document.getElementById('srmEmpWrap').style.display = canManage ? 'block' : 'none';

  const typeSel = document.getElementById('srmEntryType');
  Array.from(typeSel.options).forEach((opt) => {
    if (opt.value === 'BONUS') opt.disabled = !canBonus;
  });
  typeSel.value = (defaultType === 'BONUS' && canBonus) ? 'BONUS' : 'ALLOWANCE';
  document.getElementById('srmPresetBar').style.display = canManage ? 'flex' : 'none';
  document.getElementById('srmAmount').value = typeSel.value === 'BONUS' ? '1500' : '800';
  document.getElementById('srmReasonEn').value = typeSel.value === 'BONUS' ? 'Performance & Guest Care Bonus' : 'Operational Transport / Shift Allowance';
  document.getElementById('srmReasonAr').value = typeSel.value === 'BONUS' ? 'مكافأة تميز وأداء رائع مع الضيوف' : 'بدل انتقالات وتشغيل';
  document.getElementById('srmTitle').textContent = canManage
    ? (typeSel.value === 'BONUS' ? '🏆 Award Staff Bonus (صرف مكافأة موظف)' : '🍱 Record Staff Allowance / Advance')
    : '📝 Request Staff Allowance / Salary Advance';
  syncStaffRewardTypeUI();
  document.getElementById('staffRewardModal').style.display = 'flex';
}

async function submitStaffRewardForm(e) {
  e.preventDefault();
  const payload = {
    action: 'save_staff_bonus_allowance',
    emp_username: document.getElementById('srmEmpUser').value,
    entry_type: document.getElementById('srmEntryType').value,
    category: document.getElementById('srmCategory').value,
    amount_orig: Number(document.getElementById('srmAmount').value || 0),
    currency: document.getElementById('srmCurr').value,
    safe_code: document.getElementById('srmSafeCode').value,
    tx_date: document.getElementById('srmDate').value,
    reason_en: document.getElementById('srmReasonEn').value.trim(),
    reason_ar: document.getElementById('srmReasonAr').value.trim(),
    broadcast_inbox: document.getElementById('srmBroadcastInbox').checked ? 1 : 0
  };
  const res = await apiPost(payload);
  if (!res || !res.ok) {
    alert((res && res.error) || 'Could not save staff bonus/allowance');
    return;
  }
  document.getElementById('staffRewardModal').style.display = 'none';
  STATE.inboxData = null;
  STATE.accountsDir = null;
  STATE.activeDossier = null;
  showToast(res.status === 'REQUESTED' ? '📝 Request sent to Accounting for approval!' : '🎉 Staff Bonus / Allowance saved!');
  await reloadStaffRewards();
}

async function decideStaffReward(id, decision, safeCode) {
  const res = await apiPost({
    action: 'decide_staff_bonus_allowance',
    id,
    decision,
    safe_code: safeCode
  });
  if (!res || !res.ok) {
    alert((res && res.error) || 'Failed to update status');
    return;
  }
  STATE.accountsDir = null;
  STATE.activeDossier = null;
  showToast(`✅ Updated: ${res.status}`);
  await reloadStaffRewards();
}

/* ================= v3.6 360° ACCOUNTS, INSTALLMENTS & ENTITY FINANCIAL DOSSIERS ================= */
async function reloadAccountsHub(filterType = null, searchQ = null) {
  if (filterType !== null) STATE.accountsFilterType = filterType;
  if (searchQ !== null) STATE.accountsSearch = searchQ;
  const res = await apiGet({
    action: 'accounts_directory',
    filter_type: STATE.accountsFilterType || 'ALL',
    q: STATE.accountsSearch || ''
  });
  if (res && res.ok) {
    STATE.accountsDir = res;
  }
  renderApp();
}

async function openEntityDossier(entityType, entityCode) {
  if (!entityCode) return;
  if (STATE.activeTab && STATE.activeTab !== 'entity_dossier') {
    STATE.prevTabBeforeDossier = STATE.activeTab;
  }
  STATE.dossierTargetType = entityType || 'SUPPLIER';
  STATE.dossierTargetCode = entityCode;
  STATE.dossierLineFilter = 'ALL';
  STATE.activeDossier = null;
  STATE.activeTab = 'entity_dossier';
  renderApp();
}

function openMyOwnStatement() {
  const u = STATE.user || {};
  const uname = String(u.username || '');
  if (!uname) return;
  if (u.role_code === 'SALES_REP') {
    return openEntityDossier('SALES_REP', uname);
  }
  if (u.role_code === 'FLEET_PARTNER') {
    const pCode = uname.includes('darwish') ? 'PA_DARWISH_LEASE' : (uname.includes('farid') ? 'PA_FARID_LEASE' : 'PA_PARTNERS_LEASES');
    return openEntityDossier('PARTNER', pCode);
  }
  return openEntityDossier('STAFF', uname);
}

function openTeamProfileDossier(uname, roleCode) {
  const rc = String(roleCode || '').toUpperCase();
  const un = String(uname || '');
  if (rc === 'SALES_REP') return openEntityDossier('SALES_REP', un);
  if (rc === 'FLEET_PARTNER') {
    const pCode = un.toLowerCase().includes('darwish') ? 'PA_DARWISH_LEASE' : 'PA_FARID_LEASE';
    return openEntityDossier('PARTNER', pCode);
  }
  return openEntityDossier('STAFF', un);
}

function openPayrollRowDossier(payId, empNameAr) {
  const arName = String(empNameAr || '');
  if (arName.includes('فادى صالح')) return openEntityDossier('SALES_REP', 'fady.sales');
  if (arName === 'عمار') return openEntityDossier('SALES_REP', 'ammar.sales');
  if (arName === 'محمد على') return openEntityDossier('SALES_REP', 'moali.sales');
  if (arName === 'محمد عبدالرحمن') return openEntityDossier('SALES_REP', 'moabdo.sales');
  if (arName.includes('السكران')) return openEntityDossier('SALES_REP', 'sqran.sales');
  if (arName === 'زنفل') return openEntityDossier('SALES_REP', 'zola.sales');
  if (arName.includes('عمرو هشام')) return openEntityDossier('SALES_REP', 'amrhesham.sales');
  if (arName === 'اسلام صابر') return openEntityDossier('STAFF', 'islam.saber');
  if (arName.includes('طلال ابوالريش')) return openEntityDossier('STAFF', 'talal.jetty');
  return openEntityDossier('STAFF', 'PAY_EMP_' + Number(payId));
}

function entityTypeBadgeHtml(eType, ar) {
  const t = String(eType || '').toUpperCase();
  if (t === 'SUPPLIER') return `<span class="pill" style="border-color:#f59e0b;color:#fde68a;">🏭 ${ar ? 'مورد / مقاول' : 'Supplier'}</span>`;
  if (t === 'SALES_REP') return `<span class="pill" style="border-color:#10b981;color:#34d399;">💼 ${ar ? 'مندوب مبيعات' : 'Sales Rep'}</span>`;
  if (t === 'STAFF') return `<span class="pill" style="border-color:#00d2ff;color:#7dd3fc;">👷 ${ar ? 'موظف / طاقم' : 'Staff & Crew'}</span>`;
  if (t === 'CLIENT_AR') return `<span class="pill" style="border-color:#a855f7;color:#d8b4fe;">🏢 ${ar ? 'شركة سياحة / عميل' : 'B2B Client'}</span>`;
  return `<span class="pill" style="border-color:#fb7185;color:#fda4af;">⚓ ${ar ? 'شريك مراكب' : 'Fleet Partner'}</span>`;
}

function balanceStatusColor(statusCode) {
  if (statusCode === 'SETTLED_ZERO') return { fg: '#94a3b8', bg: 'rgba(148,163,184,0.12)', bd: 'rgba(148,163,184,0.4)' };
  if (statusCode === 'OWED_TO_COMPANY') return { fg: '#34d399', bg: 'rgba(16,185,129,0.14)', bd: 'rgba(16,185,129,0.5)' };
  if (statusCode === 'OWED_TO_PARTY') return { fg: '#fde68a', bg: 'rgba(245,158,11,0.15)', bd: 'rgba(245,158,11,0.55)' };
  return { fg: '#7dd3fc', bg: 'rgba(0,210,255,0.14)', bd: 'rgba(0,210,255,0.5)' };
}

function renderAccountsHubTab(ar) {
  const d = STATE.accountsDir || {};
  const k = d.kpis || {};
  const items = d.items || [];
  const canRec = !!d.can_record_payment;
  const fType = STATE.accountsFilterType || 'ALL';

  const catPills = [
    { id: 'ALL', lbl: ar ? `الكل (${k.total_entities || items.length})` : `All (${k.total_entities || items.length})` },
    { id: 'SUPPLIER', lbl: ar ? `🏭 الموردين (${k.suppliers_count || 0})` : `🏭 Suppliers (${k.suppliers_count || 0})` },
    { id: 'SALES_REP', lbl: ar ? `💼 المبيعات (${k.sellers_count || 0})` : `💼 Sales Reps (${k.sellers_count || 0})` },
    { id: 'STAFF', lbl: ar ? `👷 الموظفين والطقم (${k.staff_count || 0})` : `👷 Staff & Crew (${k.staff_count || 0})` },
    { id: 'CLIENT_AR', lbl: ar ? `🏢 شركات السياحة والعملاء (${k.clients_count || 0})` : `🏢 B2B Clients (${k.clients_count || 0})` },
    { id: 'PARTNER', lbl: ar ? `⚓ الشركاء والمراكب (${k.partners_count || 0})` : `⚓ Partners (${k.partners_count || 0})` }
  ];

  return `
    <div class="grid grid-kpi" style="margin-bottom:14px;">
      <div class="card" style="border-color:rgba(245,158,11,0.42);">
        <div class="kpi-label">🏭 ${ar ? 'صافي مستحقات الموردين (Suppliers AP)' : 'Suppliers Net Payable'}</div>
        <div class="kpi-val" style="color:#fde68a;">${fmt(k.suppliers_net_payable_egp || 0)} EGP</div>
        <div class="kpi-sub">${fmt(k.suppliers_count || 0)} ${ar ? 'مورد نشط • اضغط لفتح كشف الحساب' : 'active vendors • tap any row'}</div>
      </div>
      <div class="card" style="border-color:rgba(16,185,129,0.42);">
        <div class="kpi-label">💼 ${ar ? 'صافي مستحقات المبيعات (Sellers Net)' : 'Sales Reps Net Payable'}</div>
        <div class="kpi-val pos">${fmt(k.sellers_net_payable_egp || 0)} EGP</div>
        <div class="kpi-sub">${fmt(k.sellers_count || 0)} ${ar ? 'مندوب مبيعات (العمولات بعد السلف)' : 'sellers (earned minus installments)'}</div>
      </div>
      <div class="card" style="border-color:rgba(0,210,255,0.42);">
        <div class="kpi-label">👷 ${ar ? 'صافي مستحقات وسلف الموظفين والطقم' : 'Staff Payable / Advances'}</div>
        <div class="kpi-val" style="color:var(--cyan);">${fmt(k.staff_net_payable_egp || 0)} EGP</div>
        <div class="kpi-sub">${ar ? `سلف مرحلة على الموظفين: ${fmt(k.staff_advances_owed_egp || 0)} ج.م` : `Carried staff advances: ${fmt(k.staff_advances_owed_egp || 0)} EGP`}</div>
      </div>
      <div class="card" style="border-color:rgba(168,85,247,0.42);">
        <div class="kpi-label">🏢 ${ar ? 'مديونيات العملاء وشركات السياحة (AR)' : 'B2B Clients Net Receivable'}</div>
        <div class="kpi-val" style="color:#d8b4fe;">${fmt(k.clients_net_receivable_egp || 0)} EGP</div>
        <div class="kpi-sub">${fmt(k.total_installments_tracked || 0)} ${ar ? 'حركة دفعات وأقساط مسجلة' : 'tracked deposit/installment entries'}</div>
      </div>
    </div>

    <div class="card" style="margin-bottom:12px;">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
        <div>
          <h3 style="font-size:16px;">📒 ${ar ? 'دليل كشوف الحسابات والأرصدة والدفعات الشامل (360° Financial Situation)' : '360° Counterparty & Staff Accounts — Deposits, Installments & Final Balances'}</h3>
          <p style="font-size:12px;color:var(--muted);margin-top:2px;">
            ${ar ? 'اضغط على أي مورد أو مندوب مبيعات أو موظف أو شركة سياحة لفتح صفحته المالية الكاملة ومعرفة كل دفعة أو قسط أو سلفة والرصيد النهائي الصافي.' : 'Select any Supplier, Sales Rep, Staff/Crew Member, B2B Agency, or Partner to open their dedicated statement page with all deposits, installments, and final net balance.'}
          </p>
        </div>
        <input type="text" style="max-width:290px;" placeholder="${ar ? '🔍 ابحث بالاسم أو الكود أو التليفون...' : '🔍 Search supplier, seller, staff, agency...'}" value="${esc(STATE.accountsSearch || '')}" oninput="STATE.accountsSearch=this.value;reloadAccountsHub(null, this.value);">
      </div>

      <div style="display:flex;gap:6px;flex-wrap:wrap;">
        ${catPills.map((cp) => `<button type="button" class="btn ${fType === cp.id ? 'btn-primary' : ''}" style="padding:6px 12px;font-size:12px;" onclick="reloadAccountsHub('${cp.id}', null)">${esc(cp.lbl)}</button>`).join('')}
      </div>
    </div>

    <div class="card">
      <div class="table-wrap" style="max-height:620px;">
        <table>
          <thead>
            <tr>
              <th>${ar ? 'الاسم والحساب' : 'Counterparty / Staff'}</th>
              <th>${ar ? 'التصنيف' : 'Category'}</th>
              <th class="num">${ar ? 'إجمالي الفواتير / المستحقات (+)' : 'Total Accrued / Billed (+)'}</th>
              <th class="num">${ar ? 'العربون والدفعات والأقساط (-)' : 'Deposits & Installments (-)'}</th>
              <th class="num">${ar ? 'الرصيد النهائي الصافي' : 'Final Net Balance'}</th>
              <th>${ar ? 'الموقف المالي الحالي' : 'Financial Status'}</th>
              <th>${ar ? 'فتح الصفحة / دفعة' : 'Open / Pay'}</th>
            </tr>
          </thead>
          <tbody>
            ${!items.length ? `<tr><td colspan="7" style="text-align:center;color:var(--muted);padding:20px;">${ar ? 'لا توجد حسابات مطابقة للبحث.' : 'No matching financial accounts found.'}</td></tr>` : items.map((it) => {
              const col = balanceStatusColor(it.status_code);
              const encItem = encodeURIComponent(JSON.stringify({
                entity_type: it.entity_type,
                entity_code: it.entity_code,
                name_en: it.name_en,
                name_ar: it.name_ar,
                final_balance_egp: it.final_balance_egp,
                balance_label_en: it.balance_label_en,
                balance_label_ar: it.balance_label_ar,
                settlement_currency: it.settlement_currency || 'EGP'
              }));
              return `
                <tr style="cursor:pointer;" onclick="openEntityDossier('${esc(it.entity_type)}', '${esc(it.entity_code)}')">
                  <td>
                    <div style="font-weight:900;font-size:13.5px;color:#f8fafc;text-decoration:underline;text-decoration-color:rgba(0,210,255,0.45);">${esc(ar ? (it.name_ar || it.name_en) : it.name_en)}</div>
                    <div style="font-size:11px;color:var(--muted);"><code>${esc(it.entity_code)}</code> • ${esc(ar ? (it.subtitle_ar || it.subtitle_en) : it.subtitle_en)}</div>
                  </td>
                  <td>${entityTypeBadgeHtml(it.entity_type, ar)}</td>
                  <td class="num"><b>${fmt(it.total_accrued_egp)} EGP</b></td>
                  <td class="num" style="color:#34d399;">
                    <b>${fmt(it.total_installments_egp)} EGP</b>
                    <div style="font-size:10px;color:var(--muted);">${fmt(it.installments_count || 0)} ${ar ? 'دفعة/قسط' : 'installment(s)'}</div>
                  </td>
                  <td class="num">
                    <span style="font-size:14.5px;font-weight:900;color:${col.fg};">${fmt(it.final_balance_egp)} EGP</span>
                  </td>
                  <td>
                    <span class="pill" style="background:${col.bg};border-color:${col.bd};color:${col.fg};font-weight:800;">
                      ${esc(ar ? it.balance_label_ar : it.balance_label_en)}
                    </span>
                  </td>
                  <td onclick="event.stopPropagation();" style="display:flex;gap:5px;flex-wrap:wrap;">
                    <button type="button" class="btn btn-primary" style="padding:5px 10px;font-size:11.5px;font-weight:800;" onclick="openEntityDossier('${esc(it.entity_type)}', '${esc(it.entity_code)}')">📒 ${ar ? 'فتح الصفحة' : 'Open Page'}</button>
                    ${canRec && !(it.entity_type === 'PARTNER' && !hasPerm('bi.view_executive', 'gl.view_all_ledgers')) ? `<button type="button" class="btn btn-emerald" style="padding:5px 9px;font-size:11.5px;" onclick="openPartyTxModal('${encItem}')">➕ ${ar ? 'دفعة' : 'Pay'}</button>` : ''}
                  </td>
                </tr>
              `;
            }).join('')}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

function renderEntityDossierTab(ar) {
  const wrap = STATE.activeDossier || {};
  const dos = wrap.dossier || {};
  const p = dos.profile || {};
  const s = dos.summary || {};
  const instList = dos.installments_ledger || [];
  const allLines = dos.statement_lines || [];
  const ctx = dos.context || {};
  const canRec = !!wrap.can_record_payment;
  const col = balanceStatusColor(s.status_code);
  const lf = STATE.dossierLineFilter || 'ALL';

  const filteredLines = allLines.filter((ln) => {
    if (lf === 'INSTALLMENT') return ln.line_group === 'INSTALLMENT' || Number(ln.minus_egp) > 0;
    if (lf === 'ACCRUAL') return ln.line_group === 'ACCRUAL' && Number(ln.plus_egp) > 0;
    return true;
  });

  const wd = waDigits(p.whatsapp || p.phone || '');
  const csvHref = `/handlers/erp_api.php?action=export_entity_statement_csv&entity_type=${encodeURIComponent(p.entity_type || '')}&entity_code=${encodeURIComponent(p.entity_code || '')}`;
  const backTab = STATE.prevTabBeforeDossier || (hasPerm('gl.view_all_ledgers', 'bi.view_executive', 'safe.view_balances') ? 'accounts_hub' : '');

  const encParty = encodeURIComponent(JSON.stringify({
    entity_type: p.entity_type,
    entity_code: p.entity_code,
    name_en: p.name_en,
    name_ar: p.name_ar,
    final_balance_egp: s.final_balance_egp,
    balance_label_en: s.balance_label_en,
    balance_label_ar: s.balance_label_ar,
    settlement_currency: p.settlement_currency || 'EGP'
  }));

  const isClientAr = p.entity_type === 'CLIENT_AR';
  const plusColLabel = isClientAr
    ? (ar ? 'مسحوبات وفواتير على العميل (+)' : 'Billed to Client (+)')
    : (ar ? 'مستحق له / فواتير وعمولات ومرتبات (+)' : 'Earned / Billed to Company (+)');
  const minusColLabel = isClientAr
    ? (ar ? 'عربون وأقساط وتحصيلات مستلمة (-)' : 'Deposits & Installments Collected (-)')
    : (ar ? 'عربون ودفعات وأقساط وسلف مستلمة (-)' : 'Deposits, Installments & Advances Paid (-)');

  // Contextual bottom section per entity type
  let contextHtml = '';
  if (p.entity_type === 'SUPPLIER') {
    const cItems = ctx.cost_items || [];
    const rVos = ctx.recent_vouchers || [];
    const canEditCost = hasPerm('gl.view_all_ledgers', 'bi.view_executive');
    contextHtml = `
      <div class="grid grid-2" style="margin-top:14px;">
        <div class="card">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <h3 style="font-size:14px;">🏷️ ${ar ? 'قائمة أسعار تكلفة الرحلات المعتمدة مع المورد' : 'Contracted Trip & Safari Unit Costs'} (${cItems.length})</h3>
            ${canEditCost ? `<button type="button" class="btn btn-amber" style="padding:4px 9px;font-size:11px;" onclick="STATE.activeTab='suppliers_costs_hub';STATE.suppliersSubTab='costs';setSuppliersFilter('ALL','${esc(p.entity_code)}');">✏️ ${ar ? 'تعديل التكاليف' : 'Manage Costs'}</button>` : ''}
          </div>
          <div class="table-wrap" style="max-height:280px;">
            <table>
              <thead><tr><th>${ar ? 'الرحلة / البند' : 'Item'}</th><th>Basis</th><th class="num">Adult / Unit</th><th class="num">Child</th><th class="num">Infant</th></tr></thead>
              <tbody>
                ${!cItems.length ? `<tr><td colspan="5" style="text-align:center;color:var(--muted);">${ar ? 'تسوية شهرية إجمالية' : 'Monthly statement settlement'}</td></tr>` : cItems.map((ci) => `
                  <tr>
                    <td><b>${esc(ar ? (ci.name_ar || ci.name_en) : ci.name_en)}</b><div style="font-size:10px;color:var(--muted);"><code>${esc(ci.item_code)}</code></div></td>
                    <td>${esc(ci.pricing_basis)}</td>
                    <td class="num"><b style="color:#fca5a5;">${fmt(ci.pricing_basis !== 'PER_PAX' && Number(ci.unit_cost_egp) > 0 ? ci.unit_cost_egp : ci.adult_cost_egp)} EGP</b></td>
                    <td class="num">${fmt(ci.child_cost_egp)} EGP</td>
                    <td class="num">${fmt(ci.infant_cost_egp)} EGP</td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        </div>
        <div class="card">
          <h3 style="font-size:14px;margin-bottom:8px;">🎫 ${ar ? 'أحدث الفوتشرات والرحلات المنفذة مع المورد' : 'Recent Executed Vouchers with Supplier'} (${rVos.length})</h3>
          <div class="table-wrap" style="max-height:280px;">
            <table>
              <thead><tr><th>VO.NO</th><th>Date</th><th>Trip</th><th>PAX</th><th class="num">Supplier Cost</th></tr></thead>
              <tbody>
                ${!rVos.length ? `<tr><td colspan="5" style="text-align:center;color:var(--muted);">No individual trip vouchers linked</td></tr>` : rVos.map((v) => `
                  <tr>
                    <td><b>${esc(v.voucher_no)}</b></td>
                    <td>${esc(v.trip_date)}</td>
                    <td>${esc(v.trip_name)}</td>
                    <td>${v.adults}A+${v.children}C</td>
                    <td class="num"><b>${fmt(v.supplier_cost_egp)} EGP</b></td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    `;
  } else if (p.entity_type === 'SALES_REP') {
    const rComms = ctx.recent_commissions || [];
    contextHtml = `
      <div class="card" style="margin-top:14px;">
        <h3 style="font-size:14px;margin-bottom:8px;">💼 ${ar ? 'تفاصيل عمولات الحجوزات المنفذة للمندوب' : 'Detailed Voucher Commissions Breakdown'} (${rComms.length})</h3>
        <div class="table-wrap" style="max-height:300px;">
          <table>
            <thead><tr><th>Date</th><th>VO.NO</th><th>Guest / Product</th><th>Mode</th><th class="num">Gross EGP</th><th class="num">Supplier Cost</th><th class="num">Commission EGP</th><th>Status</th></tr></thead>
            <tbody>
              ${!rComms.length ? `<tr><td colspan="8" style="text-align:center;color:var(--muted);">No commission vouchers recorded</td></tr>` : rComms.map((rc) => `
                <tr>
                  <td>${esc(rc.tx_date)}</td>
                  <td><b>${esc(rc.voucher_no)}</b></td>
                  <td>${esc(rc.product_name)} <span style="color:var(--muted);">(${esc(rc.guest_name)})</span></td>
                  <td><span class="pill">${esc(rc.commission_type)}</span></td>
                  <td class="num">${fmt(rc.gross_amount_egp)}</td>
                  <td class="num">${fmt(rc.supplier_cost_egp)}</td>
                  <td class="num pos"><b>${fmt(rc.commission_egp)} EGP</b></td>
                  <td><span class="pill">${esc(rc.status)}</span></td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>
    `;
  } else if (p.entity_type === 'STAFF') {
    const pHist = ctx.payroll_history || [];
    if (pHist.length) {
      contextHtml = `
        <div class="card" style="margin-top:14px;">
          <h3 style="font-size:14px;margin-bottom:8px;">📋 ${ar ? 'سجل المرتبات الشهري وأيام العمل والسلف المخصومة (SALERY)' : 'Monthly Payroll, Worked Days & Advances History (SALERY)'} (${pHist.length})</h3>
          <div class="table-wrap" style="max-height:280px;">
            <table>
              <thead><tr><th>Month</th><th>Days</th><th class="num">Base Salary</th><th class="num">Actual Wage</th><th class="num">Commission</th><th class="num">Bonuses/Allow.</th><th class="num">Advances Deducted</th><th class="num">Net Pay</th></tr></thead>
              <tbody>
                ${pHist.map((pr) => `
                  <tr>
                    <td><b>${esc(pr.period_month)}</b></td>
                    <td class="num">${fmt(pr.worked_days)}</td>
                    <td class="num">${fmt(pr.base_salary_egp)}</td>
                    <td class="num">${fmt(pr.actual_wage_egp)}</td>
                    <td class="num" style="color:var(--cyan);">${fmt(pr.commission_egp)}</td>
                    <td class="num" style="color:#fde68a;">${fmt(Number(pr.bonuses_egp || 0) + Number(pr.allowances_egp || 0))}</td>
                    <td class="num neg">${fmt(pr.advances_deductions_egp)}</td>
                    <td class="num pos"><b>${fmt(pr.net_pay_egp)} EGP</b></td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        </div>
      `;
    }
  } else if (p.entity_type === 'PARTNER') {
    const vList = ctx.vessels_owned || [];
    if (vList.length) {
      contextHtml = `
        <div class="card" style="margin-top:14px;">
          <h3 style="font-size:14px;margin-bottom:8px;">🛥️ ${ar ? 'حصص الشريك بالمراكب وعمرة الازق (قيراط)' : 'Partner Yacht Qirat Ownership & Drydock CapEx Shares'}</h3>
          <div class="table-wrap">
            <table>
              <thead><tr><th>Vessel</th><th>Partner Qirat</th><th>Sea College Qirat</th><th class="num">Monthly Lease</th><th class="num">Drydock Partner Share</th></tr></thead>
              <tbody>
                ${vList.map((v) => `
                  <tr>
                    <td><b>${esc(ar ? v.name_ar : v.name_en)}</b></td>
                    <td class="num"><b>${v.partner_qirat} / 24 قيراط</b></td>
                    <td class="num">${v.sea_college_qirat} / 24 قيراط</td>
                    <td class="num">${fmt(v.monthly_fixed_rent_egp)} EGP</td>
                    <td class="num">${fmt(v.drydock_partner_share_egp)} EGP</td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        </div>
      `;
    }
  }

  return `
    <!-- Top Action & Navigation Bar -->
    <div class="card" style="margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;background:linear-gradient(135deg,rgba(6,24,44,0.96),rgba(12,38,66,0.92));border-color:${col.bd};">
      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        ${backTab ? `<button type="button" class="btn" onclick="STATE.activeTab='${esc(backTab)}';renderApp();">← ${ar ? 'رجوع' : 'Back'}</button>` : ''}
        <div>
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            ${entityTypeBadgeHtml(p.entity_type, ar)}
            <h2 style="font-size:18px;font-weight:900;margin:0;color:#f8fafc;">${esc(ar ? (p.name_ar || p.name_en) : p.name_en)}</h2>
            <code style="font-size:11.5px;color:var(--cyan);">${esc(p.entity_code)}</code>
          </div>
          <div style="font-size:12px;color:#cbd5e1;margin-top:4px;">${esc(ar ? (p.subtitle_ar || p.subtitle_en) : p.subtitle_en)}</div>
          ${p.notes ? `<div style="font-size:11px;color:var(--muted);margin-top:2px;">📌 ${esc(p.notes)}</div>` : ''}
        </div>
      </div>

      <div style="display:flex;gap:7px;flex-wrap:wrap;">
        ${p.phone ? `<a class="btn" href="tel:${esc(p.phone)}">📞 ${esc(p.phone)}</a>` : ''}
        ${wd.length >= 8 ? `<a class="btn btn-emerald" href="https://wa.me/${esc(wd)}" target="_blank" rel="noopener">💬 WhatsApp</a>` : ''}
        ${canRec ? `<button type="button" class="btn btn-amber" style="font-weight:900;padding:8px 14px;" onclick="openPartyTxModal('${encParty}')">➕ ${ar ? 'تسجيل عربون / قسط / دفعة / تسوية' : 'Record Deposit / Installment / Payout'}</button>` : ''}
        <a class="btn" href="${esc(csvHref)}">📥 ${ar ? 'تصدير CSV' : 'Export CSV'}</a>
      </div>
    </div>

    <!-- 4 Big Financial Situation KPI Cards -->
    <div class="grid grid-kpi" style="margin-bottom:14px;">
      <div class="card">
        <div class="kpi-label">🧾 ${plusColLabel}</div>
        <div class="kpi-val" style="color:#fde68a;">${fmt(s.total_accrued_egp || 0)} EGP</div>
        <div class="kpi-sub">${ar ? 'إجمالي الفواتير أو العمولات والمرتبات المستحقة' : 'Cumulative Invoices, Wages & Commissions'}</div>
      </div>

      <div class="card" style="border-color:rgba(16,185,129,0.45);">
        <div class="kpi-label">💵 ${minusColLabel}</div>
        <div class="kpi-val pos">${fmt(s.total_installments_egp || 0)} EGP</div>
        <div class="kpi-sub">${fmt(s.installments_count || 0)} ${ar ? 'دفعة / عربون / قسط / سلفة مسجلة' : 'recorded deposits, installments & advances'}</div>
      </div>

      <div class="card">
        <div class="kpi-label">${p.entity_type === 'SALES_REP' ? (ar ? '⏳ عمولات رحلات لم تنفذ بعد' : '⏳ Pending Unboarded Comm.') : (ar ? '📑 إجمالي حركات الكشف' : '📑 Total Ledger Entries')}</div>
        <div class="kpi-val" style="color:var(--cyan);">${p.entity_type === 'SALES_REP' ? fmt(s.pending_unboarded_egp || 0) + ' EGP' : fmt(allLines.length)}</div>
        <div class="kpi-sub">${ar ? `عملة التسوية: ${p.settlement_currency || 'EGP'} • ${p.payment_terms || ''}` : `Currency: ${p.settlement_currency || 'EGP'} • ${p.payment_terms || ''}`}</div>
      </div>

      <div class="card" style="background:${col.bg};border:2px solid ${col.bd};">
        <div class="kpi-label" style="color:${col.fg};font-weight:900;">⚖️ ${ar ? 'الرصيد النهائي الصافي (Final Balance)' : 'FINAL NET BALANCE'}</div>
        <div class="kpi-val" style="font-size:25px;color:${col.fg};">${fmt(s.final_balance_egp || 0)} EGP</div>
        <div class="kpi-sub" style="color:#f8fafc;font-weight:800;">${esc(ar ? s.balance_label_ar : s.balance_label_en)}</div>
      </div>
    </div>

    <!-- Dedicated Deposits, Installments & Advances Quick Box -->
    <div class="card" style="margin-bottom:14px;border-color:rgba(16,185,129,0.38);">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
        <h3 style="font-size:14.5px;color:#34d399;">💵 ${ar ? 'سجل العربون والأقساط والدفعات والسلف المستلمة / المسددة' : 'Deposits, Installments, Advances & Payouts Received / Paid'} (${instList.length})</h3>
        ${canRec ? `<button type="button" class="btn btn-emerald" style="padding:5px 11px;font-size:11.5px;" onclick="openPartyTxModal('${encParty}')">➕ ${ar ? 'إضافة دفعة / قسط جديد' : 'Add Installment / Deposit'}</button>` : ''}
      </div>
      ${!instList.length ? `<div style="font-size:12.5px;color:var(--muted);padding:8px 0;">${ar ? 'لم يتم تسجيل دفعات أو أقساط بعد لهذا الحساب.' : 'No deposits or installments recorded yet on this account.'}</div>` : `
        <div class="table-wrap" style="max-height:230px;">
          <table>
            <thead>
              <tr>
                <th>${ar ? 'التاريخ' : 'Date'}</th>
                <th>${ar ? 'رقم الإيصال / المرجع' : 'Ref #'}</th>
                <th>${ar ? 'نوع الدفعة' : 'Installment Type'}</th>
                <th>${ar ? 'البيان والتفاصيل' : 'Description'}</th>
                <th>${ar ? 'طريقة الدفع / الخزينة' : 'Channel / Safe'}</th>
                <th class="num">${ar ? 'المبلغ المسدد / المستلم' : 'Installment Amount'}</th>
                <th class="num">${ar ? 'الرصيد بعد الدفعة' : 'Balance After'}</th>
              </tr>
            </thead>
            <tbody>
              ${instList.map((il) => `
                <tr>
                  <td><b>${esc(il.tx_date)}</b></td>
                  <td><code>${esc(il.ref_no)}</code></td>
                  <td><span class="pill" style="border-color:#10b981;color:#34d399;">${esc(ar ? il.badge_ar : il.badge_en)}</span></td>
                  <td>${esc(ar ? il.description_ar : il.description_en)}</td>
                  <td><span class="pill">${esc(il.payment_method || 'SAFE')}</span></td>
                  <td class="num"><b class="pos">${fmt(il.minus_egp > 0 ? il.minus_egp : il.plus_egp)} EGP</b>${il.orig_currency && il.orig_currency !== 'EGP' ? `<div style="font-size:10px;color:#7dd3fc;">(${fmt(il.orig_amount, 2)} ${esc(il.orig_currency)})</div>` : ''}</td>
                  <td class="num"><b>${fmt(il.running_balance_egp)} EGP</b></td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      `}
    </div>

    <!-- Complete Chronological Running-Balance Statement -->
    <div class="card">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
        <h3 style="font-size:15px;">📊 ${ar ? 'كشف الحساب التفصيلي الكامل بالرصيد التراكمي' : 'Complete Chronological Account Statement & Running Balance'} (${filteredLines.length})</h3>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
          <button type="button" class="btn ${lf === 'ALL' ? 'btn-primary' : ''}" style="padding:5px 10px;font-size:11.5px;" onclick="STATE.dossierLineFilter='ALL';renderApp();">${ar ? 'كل الحركات' : 'All Entries'} (${allLines.length})</button>
          <button type="button" class="btn ${lf === 'INSTALLMENT' ? 'btn-primary' : ''}" style="padding:5px 10px;font-size:11.5px;" onclick="STATE.dossierLineFilter='INSTALLMENT';renderApp();">💵 ${ar ? 'الدفعات والأقساط فقط' : 'Deposits & Installments'} (${instList.length})</button>
          <button type="button" class="btn ${lf === 'ACCRUAL' ? 'btn-primary' : ''}" style="padding:5px 10px;font-size:11.5px;" onclick="STATE.dossierLineFilter='ACCRUAL';renderApp();">🧾 ${ar ? 'الفواتير والمستحقات فقط' : 'Invoices / Accruals'}</button>
        </div>
      </div>

      <div class="table-wrap" style="max-height:480px;">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>${ar ? 'التاريخ' : 'Date'}</th>
              <th>${ar ? 'المرجع' : 'Ref'}</th>
              <th>${ar ? 'النوع' : 'Type'}</th>
              <th>${ar ? 'البيان' : 'Description'}</th>
              <th class="num">${plusColLabel}</th>
              <th class="num">${minusColLabel}</th>
              <th class="num">${ar ? 'الرصيد التراكمي (EGP)' : 'Running Balance (EGP)'}</th>
            </tr>
          </thead>
          <tbody>
            ${!filteredLines.length ? `<tr><td colspan="8" style="text-align:center;color:var(--muted);padding:18px;">${ar ? 'لا توجد حركات مسجلة.' : 'No statement entries found.'}</td></tr>` : filteredLines.map((ln) => `
              <tr>
                <td class="num" style="color:var(--muted);">${ln.line_no}</td>
                <td><b>${esc(ln.tx_date)}</b></td>
                <td><code>${esc(ln.ref_no)}</code></td>
                <td><span class="pill" style="${ln.minus_egp > 0 ? 'border-color:#10b981;color:#34d399;' : 'border-color:#f59e0b;color:#fde68a;'}">${esc(ar ? ln.badge_ar : ln.badge_en)}</span></td>
                <td>
                  <div>${esc(ar ? ln.description_ar : ln.description_en)}</div>
                  <div style="font-size:10px;color:var(--muted);">${esc(ln.payment_method || '')}${ln.recorded_by ? ' • by ' + esc(ln.recorded_by) : ''}</div>
                </td>
                <td class="num">${ln.plus_egp > 0 ? `<b style="color:#fde68a;">+${fmt(ln.plus_egp)}</b>` : '—'}</td>
                <td class="num">${ln.minus_egp > 0 ? `<b class="pos">-${fmt(ln.minus_egp)}</b>` : '—'}</td>
                <td class="num"><b style="font-size:13.5px;color:${ln.running_balance_egp > 0 ? '#fde68a' : (ln.running_balance_egp < 0 ? '#7dd3fc' : '#94a3b8')};">${fmt(ln.running_balance_egp)} EGP</b></td>
              </tr>
            `).join('')}
          </tbody>
          <tfoot>
            <tr style="background:rgba(0,210,255,0.09);font-weight:900;">
              <td colspan="5" style="text-align:end;">${ar ? 'الإجمالي والرصيد النهائي الصافي:' : 'Totals & Final Net Balance:'}</td>
              <td class="num" style="color:#fde68a;">+${fmt(s.total_accrued_egp || 0)} EGP</td>
              <td class="num pos">-${fmt(s.total_installments_egp || 0)} EGP</td>
              <td class="num" style="font-size:14.5px;color:${col.fg};">${fmt(s.final_balance_egp || 0)} EGP</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    ${contextHtml}
  `;
}

function openPartyTxModal(encodedParty) {
  const p = encodedParty ? JSON.parse(decodeURIComponent(encodedParty)) : null;
  if (!p) return;
  const ar = STATE.lang === 'ar';
  const curBal = Number(p.final_balance_egp || 0);
  document.getElementById('ptmEntityType').value = p.entity_type || 'SUPPLIER';
  document.getElementById('ptmEntityCode').value = p.entity_code || '';
  document.getElementById('ptmCurrentBalEgp').value = String(curBal);

  document.getElementById('ptmTitle').textContent = ar
    ? `💵 تسجيل دفعة / عربون / قسط: ${p.name_ar || p.name_en}`
    : `💵 Record Deposit / Installment: ${p.name_en}`;

  document.getElementById('ptmAccountSummaryBanner').innerHTML = `
    <div>
      <div style="font-size:13.5px;font-weight:900;color:#f8fafc;">${esc(ar ? (p.name_ar || p.name_en) : p.name_en)} <code style="color:var(--cyan);">${esc(p.entity_code)}</code></div>
      <div style="font-size:11.5px;color:#fde68a;margin-top:2px;">${ar ? 'الرصيد الحالي:' : 'Current Balance:'} <b>${fmt(curBal)} EGP</b> (${esc(ar ? p.balance_label_ar : p.balance_label_en)})</div>
    </div>
    ${Math.abs(curBal) > 0.5 ? `<button type="button" class="btn btn-amber" style="padding:5px 10px;font-size:11.5px;" onclick="document.getElementById('ptmTxKind').value='FULL_SETTLEMENT';document.getElementById('ptmCurrency').value='EGP';document.getElementById('ptmAmount').value='${Math.abs(curBal).toFixed(2)}';syncPartyTxKindUI();">✅ ${ar ? 'تقفيل كامل الرصيد' : 'Settle Exact Balance'} (${fmt(Math.abs(curBal))} EGP)</button>` : ''}
  `;

  document.getElementById('ptmTxKind').value = 'INSTALLMENT';
  document.getElementById('ptmCurrency').value = 'EGP';
  document.getElementById('ptmAmount').value = Math.abs(curBal) > 0 ? String(Math.min(Math.abs(curBal), 10000)) : '5000';
  document.getElementById('ptmReceiptNo').value = '';
  syncPartyTxKindUI();
  document.getElementById('partyTransactionModal').style.display = 'flex';
}

function syncPartyTxKindUI() {
  const kind = document.getElementById('ptmTxKind').value;
  const eType = document.getElementById('ptmEntityType').value;
  const dEn = document.getElementById('ptmDescEn');
  const dAr = document.getElementById('ptmDescAr');
  if (kind === 'DEPOSIT') {
    dEn.value = eType === 'CLIENT_AR' ? 'Advance deposit received from client' : 'On-account deposit paid to counterparty';
    dAr.value = eType === 'CLIENT_AR' ? 'عربون / دفعة مقدمة مستلمة من العميل' : 'دفعة مقدمة (عربون من تحت الحساب)';
  } else if (kind === 'FULL_SETTLEMENT') {
    const curBal = Math.abs(Number(document.getElementById('ptmCurrentBalEgp').value || 0));
    if (curBal > 0 && document.getElementById('ptmCurrency').value === 'EGP') {
      document.getElementById('ptmAmount').value = curBal.toFixed(2);
    }
    dEn.value = 'Full account balance settlement';
    dAr.value = 'تقفيل وسداد كامل الرصيد المتبقي بالحساب';
  } else if (kind === 'ADVANCE_GIVEN') {
    dEn.value = 'Cash advance disbursed';
    dAr.value = 'صرف سلفة نقدية على الحساب';
  } else if (kind === 'ADVANCE_REPAID') {
    dEn.value = 'Cash advance repayment received';
    dAr.value = 'توريد وسداد من رصيد السلفة';
  } else if (kind === 'INVOICE_CHARGE') {
    dEn.value = 'New statement invoice / entitlement';
    dAr.value = 'إضافة فاتورة / استحقاق جديد بكشف الحساب';
  } else {
    dEn.value = eType === 'CLIENT_AR' ? 'Installment collected on account' : 'Installment payment disbursed on account';
    dAr.value = eType === 'CLIENT_AR' ? 'تحصيل دفعة قسط من حساب العميل' : 'سداد دفعة / قسط من الحساب';
  }
}

async function submitPartyTxForm(e) {
  e.preventDefault();
  const payload = {
    action: 'record_party_transaction',
    entity_type: document.getElementById('ptmEntityType').value,
    entity_code: document.getElementById('ptmEntityCode').value,
    tx_kind: document.getElementById('ptmTxKind').value,
    payment_method: document.getElementById('ptmMethod').value,
    orig_amount: Number(document.getElementById('ptmAmount').value || 0),
    orig_currency: document.getElementById('ptmCurrency').value,
    tx_date: document.getElementById('ptmDate').value,
    voucher_or_receipt_no: document.getElementById('ptmReceiptNo').value.trim(),
    sync_cashbox: document.getElementById('ptmSyncCashbox').checked ? 1 : 0,
    description_en: document.getElementById('ptmDescEn').value.trim(),
    description_ar: document.getElementById('ptmDescAr').value.trim()
  };
  const res = await apiPost(payload);
  if (!res || !res.ok) {
    alert((res && res.error) || 'Failed to record financial transaction');
    return;
  }
  document.getElementById('partyTransactionModal').style.display = 'none';
  STATE.accountsDir = null;
  STATE.suppliersHub = null;
  showToast(`✅ Recorded ${fmt(res.amount_egp)} EGP • New Final Balance: ${fmt(res.final_balance_egp)} EGP`);

  if (STATE.activeTab === 'entity_dossier' && res.dossier) {
    STATE.activeDossier = {
      ok: true,
      can_record_payment: true,
      dossier: res.dossier
    };
    renderApp();
  } else if (STATE.activeTab === 'accounts_hub') {
    await reloadAccountsHub();
  } else {
    renderApp();
  }
}

boot();
</script>
</body>
</html>
