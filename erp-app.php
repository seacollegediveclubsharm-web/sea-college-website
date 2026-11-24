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
      background: rgba(6, 21, 38, 0.96); backdrop-filter: blur(14px);
      border-top: 1px solid var(--border); padding: 6px 8px calc(6px + env(safe-area-inset-bottom));
      justify-content: space-around; gap: 4px;
    }
    .dock-btn {
      flex: 1; border: none; background: transparent; color: var(--muted);
      display: flex; flex-direction: column; align-items: center; gap: 2px;
      font-size: 10.5px; font-weight: 700; padding: 5px 4px; border-radius: 10px; cursor: pointer;
    }
    .dock-btn.active { background: rgba(0,210,255,0.16); color: var(--cyan); }
    .dock-btn .ico { font-size: 17px; line-height: 1.1; }
    @media (max-width: 920px) {
      .bottom-dock.ready { display: flex; }
      body { padding-bottom: 104px; }
      #fabFeedback { bottom: 72px !important; }
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
      <h1 id="appTitle">Sea College Dive Club — Unified Ecosystem App <span class="pill" style="background:rgba(16,185,129,0.2);color:#34d399;border-color:rgba(16,185,129,0.4);">🚀 v3.0 LIVE</span></h1>
      <p id="appSub">Smart Tablet Concierge • Multi-Stage Reservations • Real-Time Accounting &amp; Awards</p>
    </div>
  </div>
  <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
    <button type="button" id="notifBtn" class="btn" style="display:none;position:relative;" onclick="openNotificationsModal()">🔔 <span id="notifCountBadge" class="notif-badge" style="display:none;">0</span></button>
    <button type="button" id="userBadge" class="btn" style="display:none;padding:5px 11px;border-radius:999px;" onclick="STATE.activeTab='my_profile';renderApp();"></button>
    <button type="button" class="btn btn-emerald" onclick="triggerInstallApp()">📲 Install App</button>
    <button type="button" class="btn" id="langBtn" onclick="toggleLang()">العربية RTL</button>
    <a href="/" class="btn" id="siteBtn">🌐 Website</a>
    <button type="button" class="btn btn-amber" id="logoutBtn" style="display:none;" onclick="doLogout()">Logout</button>
  </div>
</header>

<main class="container">
  <!-- ================= LOGIN VIEW ================= -->
  <section id="loginView" class="login-box card" style="display:none;">
    <h2 style="font-size:18px;margin-bottom:6px;" id="loginHeading">Employee & Owner Sign In (Trial Pilot v1.0)</h2>
    <p style="font-size:12px;color:var(--muted);margin-bottom:14px;" id="loginDesc">
      Sign in with your assigned Sea College role account. Test vouchers & safe entries are prefixed with <b>TRIAL-</b> so your team can test freely without affecting closed accounting ledgers!
    </p>
    <form onsubmit="doLogin(event)" class="grid" style="gap:12px;">
      <div>
        <label id="lblUser">Username</label>
        <input type="text" id="inpUser" required autocomplete="username" placeholder="e.g. islam.samir, eng.sayed, nadia.acc">
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
        👥 Quick Role Directory (Select Employee / Owner Profile)
      </summary>
      <p style="font-size:11px;color:var(--muted);margin:6px 0;">
        Tap any profile below to pre-fill username during onboarding:
      </p>
      <div id="roleDirectoryList" style="display:grid;gap:6px;max-height:280px;overflow-y:auto;margin-top:8px;"></div>
    </details>
  </section>

  <!-- ================= AUTHENTICATED APP SHELL ================= -->
  <section id="appView" style="display:none;">
    <div id="roleBanner" class="card" style="margin-bottom:14px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;background:linear-gradient(90deg,rgba(0,210,255,0.12),rgba(16,185,129,0.08));">
      <div style="display:flex;align-items:center;gap:13px;flex-wrap:wrap;">
        <div id="welcomeAvatar"></div>
        <div>
          <div style="font-size:17px;font-weight:800;" id="welcomeName"></div>
          <div style="font-size:12px;color:var(--muted);margin-top:2px;" id="welcomeScope"></div>
          <div id="welcomeContacts" style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px;"></div>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
          <button type="button" class="btn" onclick="STATE.activeTab='my_profile';renderApp();">👤 My Profile</button>
          <button type="button" class="btn" onclick="STATE.activeTab='team_profiles';renderApp();">👥 Team Directory</button>
        </div>
        <div id="permPills" style="display:flex;gap:5px;flex-wrap:wrap;"></div>
      </div>
    </div>

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
  <div class="card" style="width:100%;max-width:580px;max-height:92vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
      <h3 style="font-size:16px;" id="fbkTitle">⚡ Guest Booking &amp; Instant Boarding Pass</h3>
      <button type="button" class="btn" onclick="document.getElementById('fastBookModal').style.display='none'">✕</button>
    </div>
    <form onsubmit="submitFastBookForm(event)" class="grid grid-2" style="gap:10px;">
      <input type="hidden" id="fbkProdCode" value="">
      <div>
        <label>Guest Full Name *</label>
        <input type="text" id="fbkGuest" required placeholder="e.g. Marco Rossi">
      </div>
      <div>
        <label>Guest WhatsApp / Phone</label>
        <input type="tel" id="fbkPhone" placeholder="+39 333 1234567">
      </div>
      <div>
        <label>Trip Date *</label>
        <input type="date" id="fbkDate" required value="<?= gmdate('Y-m-d') ?>">
      </div>
      <div>
        <label>Nationality</label>
        <input type="text" id="fbkNat" value="ITALY">
      </div>
      <div>
        <label>Sharm Hotel / Resort (Auto Pickup Time)</label>
        <select id="fbkHotel" onchange="const o=this.selectedOptions[0];if(o&&o.dataset.pick)document.getElementById('fbkPick').value=o.dataset.pick;"></select>
      </div>
      <div>
        <label>Room # &amp; Pickup Time</label>
        <div style="display:flex;gap:6px;">
          <input type="text" id="fbkRoom" placeholder="Room 312">
          <input type="text" id="fbkPick" value="07:55–08:10">
        </div>
      </div>
      <div style="grid-column:1/-1;">
        <label>Guests Breakdown — Adults / Children (3–11 yrs) / Infants (&lt;3 yrs FREE)</label>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;">
          <div>
            <span style="font-size:11px;color:var(--muted);">👤 Adults</span>
            <input type="number" id="fbkAdl" value="2" min="1" max="60" oninput="recalcFastBookGuestTotal(true)">
          </div>
          <div>
            <span style="font-size:11px;color:var(--muted);">🧒 Children</span>
            <input type="number" id="fbkCh" value="0" min="0" max="40" oninput="recalcFastBookGuestTotal(true)">
          </div>
          <div>
            <span style="font-size:11px;color:var(--teal);">👶 Infants (&lt;3 Free)</span>
            <input type="number" id="fbkInf" value="0" min="0" max="20" oninput="recalcFastBookGuestTotal(false)">
          </div>
        </div>
      </div>
      <div id="fbkDiscountRow" style="grid-column:1/-1;padding:10px 12px;border-radius:10px;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.32);">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px;margin-bottom:6px;">
          <label style="margin:0;color:#fde68a;">🎁 Special Guest Discount (By Percentage % or Fixed Amount)</label>
          <div style="display:flex;gap:4px;flex-wrap:wrap;">
            <button type="button" class="btn" style="padding:3px 8px;font-size:11px;" onclick="setFastBookPresetDiscount('NONE', 0)">None</button>
            <button type="button" class="btn" style="padding:3px 8px;font-size:11px;" onclick="setFastBookPresetDiscount('PERCENT', 5)">-5%</button>
            <button type="button" class="btn" style="padding:3px 8px;font-size:11px;" onclick="setFastBookPresetDiscount('PERCENT', 10)">-10%</button>
            <button type="button" class="btn" style="padding:3px 8px;font-size:11px;" onclick="setFastBookPresetDiscount('PERCENT', 15)">-15%</button>
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
        <label>Deposit Paid Now</label>
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
        <label>Remaining Balance at Pickup Time</label>
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
        <button type="submit" id="fbkSubmitBtn" class="btn btn-primary">🎫 Confirm Guest Booking &amp; Issue QR Pass</button>
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
  pnlViewMode: 'consolidated',
  bankViewMode: 'all3',
  pitchLang: 'en',
  pitchCurr: 'EUR',
  pitchCat: 'ALL',
  pitchQuery: '',
  pitchHotel: 'Cyrene Sharm Hotel',
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
  bi: null,
  ws: null,
  sellerCatalog: null,
  sellerWallet: null,
  monthlyPack: null,
  directory: []
};

function fillAccount(uname) {
  document.getElementById('inpUser').value = uname;
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
  document.getElementById('langBtn').textContent = STATE.lang === 'ar' ? 'English LTR' : 'العربية RTL';
  document.getElementById('appTitle').textContent = STATE.lang === 'ar'
    ? 'سي كولدج دايف كلوب — التطبيق الموحد للشركة والملاك'
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
  box.innerHTML = STATE.directory.map((a) => `
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:7px 9px;border-radius:8px;background:rgba(6,21,38,0.7);border:1px solid var(--border);">
      <div style="display:flex;align-items:center;gap:9px;min-width:0;">
        ${renderAvatarHtml(a, 36)}
        <div style="min-width:0;">
          <div style="font-size:12px;font-weight:700;">${esc(STATE.lang === 'ar' ? (a.display_name_ar || a.display_name_en) : (a.display_name_en || a.username))}</div>
          <div style="font-size:11px;color:var(--muted);">@${esc(a.username)} • ${esc(STATE.lang === 'ar' ? (a.job_title_ar || a.role_code) : (a.job_title_en || a.role_code))} (${esc(a.branch_scope || 'ALL')})</div>
        </div>
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
  STATE.sellerCatalog = null;
  STATE.sellerWallet = null;
  STATE.monthlyPack = null;
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
  STATE.notifications = [];
  STATE.unreadNotifs = 0;
  document.getElementById('userBadge').style.display = 'none';
  document.getElementById('notifBtn').style.display = 'none';
  document.getElementById('logoutBtn').style.display = 'none';
  document.getElementById('fabFeedback').style.display = 'none';
  document.getElementById('mobileBottomNav').className = 'bottom-dock';
  document.getElementById('appView').style.display = 'none';
  document.getElementById('loginView').style.display = 'block';
}

function syncNotifBadge() {
  const nb = document.getElementById('notifBtn');
  const badge = document.getElementById('notifCountBadge');
  if (!nb || !badge) return;
  if (!STATE.user) {
    nb.style.display = 'none';
    return;
  }
  nb.style.display = 'inline-flex';
  const cnt = Number(STATE.unreadNotifs || 0);
  badge.textContent = String(cnt);
  badge.style.display = cnt > 0 ? 'inline-block' : 'none';
}

async function openNotificationsModal() {
  const res = await apiGet({ action: 'notifications' });
  if (res && res.ok) {
    STATE.notifications = res.notifications || [];
    STATE.unreadNotifs = Number(res.unread_count || 0);
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
  document.getElementById('fabFeedback').style.display = 'block';
  const ub = document.getElementById('userBadge');
  ub.style.display = 'inline-flex';
  const dispName = STATE.lang === 'ar'
    ? (STATE.user.display_name_ar || STATE.user.display_name_en || STATE.user.username)
    : (STATE.user.display_name_en || STATE.user.username);
  ub.innerHTML = `${renderAvatarHtml(STATE.user, 24)} <span>${esc(dispName)}</span>`;

  const jobs = [
    apiGet({ action: 'role_workspace' }).then((r) => {
      if (r && r.ok) {
        STATE.ws = r;
        STATE.notifications = r.notifications || [];
        STATE.unreadNotifs = Number(r.unread_notifications_count || 0);
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
  const u = STATE.user || {};
  const isSalesRepPrimary = u.role_code === 'SALES_REP' || (
    hasPerm('sales.create_voucher', 'sales.view_own_sales') &&
    !hasPerm('bi.view_executive', 'gl.view_all_ledgers', 'ops.view_manifests', 'safe.view_balances', 'bi.view_partner_fleet', 'crew.view_own_manifest')
  );

  if (isSalesRepPrimary) {
    return [
      { id: 'seller_pitch', ico: '⚡', short: ar ? 'العرض' : 'Pitch', label: ar ? '⚡ عرض العميل والحجز السريع (Pitch)' : '⚡ Seller Pitch & Fast Book' },
      { id: 'seller_wallet', ico: '🏆', short: ar ? 'حسابي' : 'My Account', label: ar ? '🏆 حسابي، باركود QR وعمولاتي' : '🏆 My Profile, QR & Commissions' },
      { id: 'vouchers_ops', ico: '🎫', short: ar ? 'حجوزاتي' : 'My Trips', label: ar ? '🎫 حجوزاتي وتأكيداتها' : '🎫 My Trips & Confirmations' },
      { id: 'my_profile', ico: '👤', short: ar ? 'صورتي' : 'Settings', label: ar ? '👤 ملفي الشخصي وصورتي' : '👤 My Photo & Settings' },
      { id: 'team_profiles', ico: '👥', short: ar ? 'الفريق' : 'Team', label: ar ? '👥 دليل أرقام الفريق' : '👥 Team Contacts' }
    ];
  }

  const tabs = [];
  if (hasPerm('bi.view_executive')) {
    tabs.push({ id: 'exec_bi', ico: '📊', short: 'Owner BI', label: ar ? '📊 لوحة الملاك وأداء المناديب' : '📊 Owner BI & Seller Awards' });
  }
  if (hasPerm('bi.view_executive', 'gl.view_all_ledgers')) {
    tabs.push({ id: 'monthly_reports', ico: '📑', short: 'Monthly', label: ar ? '📑 التقارير الشهرية الختامية (Monthly)' : '📑 Monthly Reports Pack' });
  }
  if (hasPerm('gl.view_all_ledgers')) {
    tabs.push({ id: 'accounting_gl', ico: '📚', short: 'Accounting', label: ar ? '📚 الحسابات والتسويات والمرتبات' : '📚 Accounting, Settlements & Payroll' });
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

function renderApp() {
  if (!STATE.user) {
    renderDirectory();
    return;
  }
  syncNotifBadge();
  const ar = STATE.lang === 'ar';
  const u = STATE.user;
  const dispName = ar ? (u.display_name_ar || u.display_name_en || u.username || '') : (u.display_name_en || u.username || '');
  document.getElementById('userBadge').innerHTML = `${renderAvatarHtml(u, 24)} <span>${esc(dispName)}</span>`;
  document.getElementById('welcomeAvatar').innerHTML = renderAvatarHtml(u, 62);
  document.getElementById('welcomeName').textContent = dispName;
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

  const tabs = buildTabsForUser();
  if (!STATE.activeTab || !tabs.some((t) => t.id === STATE.activeTab)) {
    STATE.activeTab = tabs[0] ? tabs[0].id : '';
  }
  document.getElementById('navTabs').innerHTML = tabs.map((t) => `
    <button type="button" class="tab-btn ${STATE.activeTab === t.id ? 'active' : ''}" onclick="STATE.activeTab='${t.id}';renderApp();">${esc(t.label)}</button>
  `).join('');

  const dock = document.getElementById('mobileBottomNav');
  if (dock) {
    dock.className = 'bottom-dock ready';
    dock.innerHTML = tabs.slice(0, 5).map((t) => `
      <button type="button" class="dock-btn ${STATE.activeTab === t.id ? 'active' : ''}" onclick="STATE.activeTab='${t.id}';renderApp();">
        <span class="ico">${esc(t.ico || '•')}</span>
        <span>${esc(t.short || t.label.slice(0, 9))}</span>
      </button>
    `).join('');
  }

  const box = document.getElementById('tabContent');
  if (STATE.activeTab === 'seller_pitch') {
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
    box.innerHTML = renderMyProfileTab(ar);
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
              <th>Inspect</th>
            </tr>
          </thead>
          <tbody>
            ${rows.map((s) => {
              const medals = (s.awards || []).map((a) => `<span title="${esc(a.title_en)}">${esc(a.icon)}</span>`).join(' ');
              const dGrant = Number(s.discounts_granted_egp || 0);
              return `
                <tr>
                  <td class="num"><b>#${s.rank || '—'}</b></td>
                  <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                      ${renderAvatarHtml(s, 28)}
                      <div><b>${esc(ar ? (s.display_name_ar || s.display_name_en) : s.display_name_en)}</b><div style="font-size:10px;color:var(--muted);">@${esc(s.username)}</div></div>
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
                  <td><button type="button" class="btn" onclick="STATE.walletSeller='${esc(s.username)}';STATE.sellerWallet=null;STATE.activeTab='seller_wallet';renderApp();">🔍 Wallet</button></td>
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
        <h3 style="font-size:14px;margin-bottom:8px;">👥 Monthly Staff Payroll &amp; Commissions (SALERY)</h3>
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
                <tr>
                  <td>${esc(p.period_month)}</td>
                  <td><b>${esc(p.emp_name_ar)}</b></td>
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
  const canAdjust = hasPerm('ops.view_manifests', 'ops.assign_boat_driver', 'jetty.scan_boarding_collect_rest', 'safe.record_tx', 'gl.view_all_ledgers', 'bi.view_executive');
  const canOpsConfirm = hasPerm('ops.view_manifests', 'ops.assign_boat_driver', 'bi.view_executive');
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

async function sellerConfirmBooking(id) {
  const res = await apiPost({ action: 'seller_confirm_booking', id });
  if (res && res.ok) {
    showToast(`✅ Confirmed ${res.voucher_no} — forwarded to Reservations Manager!`);
    document.getElementById('notifModal').style.display = 'none';
    await loadUserData();
  } else {
    alert((res && res.error) || 'Could not confirm booking');
  }
}

async function sellerConfirmByVoucher(voucherNo) {
  const res = await apiPost({ action: 'seller_confirm_booking', voucher_no: voucherNo });
  if (res && res.ok) {
    showToast(`✅ Confirmed ${res.voucher_no} — forwarded to Reservations Manager!`);
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
function setFastBookPresetDiscount(dType, dVal) {
  const tEl = document.getElementById('fbkDiscType');
  const vEl = document.getElementById('fbkDiscVal');
  if (tEl) tEl.value = dType;
  if (vEl) vEl.value = String(dVal);
  recalcFastBookGuestTotal(true);
}

function openFastBookModal(itemCode) {
  const cat = STATE.sellerCatalog || {};
  const all = getUnifiedPitchItems(cat);
  const item = all.find((x) => String(x.code || x.id) === String(itemCode)) || all[0];
  if (!item) return;
  CURRENT_FBK_ITEM = item;
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
  document.getElementById('fbkDiscType').value = pol.is_enabled ? (STATE.pitchDiscType || 'NONE') : 'NONE';
  document.getElementById('fbkDiscVal').value = pol.is_enabled && STATE.pitchDiscType !== 'NONE' ? String(STATE.pitchDiscVal || 0) : '0';
  document.getElementById('fbkDiscReason').value = '';

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
  const priceLine = dCalc.discInCurr > 0
    ? ` = <s style="opacity:0.65;">${u.sym}${fmt(dCalc.listTotal, 1)}</s> <b class="pos">${u.sym}${fmt(dCalc.netTotal, 1)} ${esc(cur)}</b> <span style="color:#fcd34d;font-weight:800;">(${esc(dCalc.badge)} Special Offer!)</span>`
    : ` = <b>${u.sym}${fmt(dCalc.netTotal, 1)} ${esc(cur)}</b>`;
  document.getElementById('fbkGuestSummary').innerHTML =
    `🌊 <b>Trip Summary:</b> ${adl} Adult (${u.sym}${fmt(u.adult, 0)})`
    + (ch > 0 ? ` + ${ch} Child (${u.sym}${fmt(u.child, 0)})` : '')
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
    const res = await apiPost({
      action: 'create_voucher',
      guest_name: document.getElementById('fbkGuest').value.trim(),
      guest_phone: document.getElementById('fbkPhone').value.trim(),
      trip_date: document.getElementById('fbkDate').value,
      nationality: document.getElementById('fbkNat').value.trim(),
      hotel_name: document.getElementById('fbkHotel').value,
      room_no: document.getElementById('fbkRoom').value.trim(),
      pickup_time: document.getElementById('fbkPick').value.trim(),
      product_code: document.getElementById('fbkProdCode').value,
      adults: Number(document.getElementById('fbkAdl').value || 1),
      children: Number(document.getElementById('fbkCh').value || 0),
      infants: Number(document.getElementById('fbkInf').value || 0),
      discount_type: dCalc.appliedType,
      discount_value: dCalc.appliedVal,
      discount_currency: cur,
      discount_reason: document.getElementById('fbkDiscReason') ? document.getElementById('fbkDiscReason').value.trim() : '',
      deposit_amount: Number(document.getElementById('fbkDepAmt').value || 0),
      deposit_currency: cur,
      rest_amount: Number(document.getElementById('fbkRestAmt').value || 0),
      rest_currency: document.getElementById('fbkRestCur').value
    });
    if (!res.ok) {
      alert(res.error || 'Could not create voucher');
      return;
    }
    const rBox = document.getElementById('fbkResultBox');
    const qrSvg = (window.SCQR && typeof window.SCQR.toSvg === 'function')
      ? `<div class="qr-box" style="margin:8px auto;">${window.SCQR.toSvg(String(res.qr_token || res.voucher_no), { size: 150 })}</div>`
      : '';
    rBox.style.display = 'block';
    rBox.innerHTML = `
      <div style="font-size:15px;font-weight:900;color:var(--teal);">✅ Booking Confirmed — Voucher ${esc(res.voucher_no)}</div>
      <div style="font-size:12px;margin-top:4px;">Pickup Window: <b>${esc(document.getElementById('fbkPick').value)}</b> • Guests: <b>${Number(res.adults || 1)} Adults${Number(res.children || 0) > 0 ? ` + ${res.children} Kids` : ''}${Number(res.infants || 0) > 0 ? ` + ${res.infants} Infants` : ''}</b>${Number(res.discount_egp || 0) > 0 ? ` • <span style="color:#fcd34d;">🎁 Special Discount Applied</span>` : ''}</div>
      ${qrSvg}
      <div style="font-size:11px;color:var(--muted);">Digital Boarding Pass: ${esc(res.qr_token)}</div>
    `;
    showToast(`✅ Booking ${res.voucher_no} Confirmed!`);
    STATE.sellerWallet = null;
    const wp = { action: 'seller_wallet', month: STATE.walletMonth };
    if (STATE.walletSeller) wp.seller = STATE.walletSeller;
    apiGet(wp).then((w) => { if (w && w.ok) STATE.sellerWallet = w; });
    apiGet({ action: 'role_workspace' }).then((w) => { if (w && w.ok) STATE.ws = w; });
  } finally {
    btn.disabled = false;
    btn.textContent = '🎫 Confirm Guest Booking & Issue QR Pass';
  }
}

function setPitchDiscount(dType, dVal) {
  STATE.pitchDiscType = dType;
  STATE.pitchDiscVal = Math.max(0, Number(dVal || 0));
  renderApp();
}

function renderSellerPitchTab(ar) {
  const cat = STATE.sellerCatalog || {};
  const fx = cat.fx_rates || { EUR: 57, USD: 50, GBP: 65, EGP: 1 };
  const hotels = cat.hotels || [];
  const langs = cat.languages || ['en', 'ru', 'it', 'pl', 'de', 'fr', 'ar', 'zh'];
  const langLabels = { en: '🇬🇧 EN', ru: '🇷🇺 RU', it: '🇮🇹 IT', pl: '🇵🇱 PL', de: '🇩🇪 DE', fr: '🇫🇷 FR', ar: '🇪🇬 AR', zh: '🇨🇳 ZH' };
  const discPol = getSellerDiscountPolicy();

  const selectedHotelObj = hotels.find((h) => h.name === STATE.pitchHotel) || hotels[0] || { name: 'Cyrene Sharm Hotel', pickup_window: '07:55–08:10', zone_label: 'Montazah / Ras Nasrani' };
  STATE.pitchPickup = selectedHotelObj.pickup_window || '07:55–08:10';

  const combined = getUnifiedPitchItems(cat);

  const catChips = [
    ['ALL', ar ? `🌟 كل الرحلات (${combined.length})` : `🌟 All Trips (${combined.length})`],
    ['snorkeling', ar ? '🏝️ مراكب، الجزيرة البيضاء وتيران' : '🏝️ Boat, White Island & Tiran'],
    ['intro-diving', ar ? '🤿 غطس تجريبي للمبتدئين' : '🤿 Intro Diving'],
    ['daily-diving', ar ? '🚤 غطس يومي للمحترفين' : '🚤 Certified Boat Dives'],
    ['padi-courses', ar ? '🎓 كورسات PADI' : '🎓 PADI Courses'],
    ['desert-safari', ar ? '🏜️ سفاري بيتش باجي، جمال وعشاء بدوي' : '🏜️ Quad Safari, Camel & Bedouin'],
    ['cairo-luxor', ar ? '✈️ القاهرة والأقصر (طيران / باص)' : '✈️ Cairo & Luxor (Plane / Bus)'],
    ['sinai-dahab', ar ? '⛰️ سانت كاترين وجبل موسى ودهب' : '⛰️ St. Catherine, Moses & Dahab'],
    ['marine-family', ar ? '🐬 دولفين، غواصة، جلاس بوت وعشاء' : '🐬 Dolphin, Submarine & Dinner'],
    ['watersports', ar ? '🪂 براشوت وألعاب مائية' : '🪂 Parasailing & Watersports'],
    ['equipment', ar ? '🤿 معدات وتأمين الغوص' : '🤿 Dive Gear & Insurance']
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

  return `
    <div class="card" style="margin-bottom:14px;background:linear-gradient(135deg,rgba(0,210,255,0.15),rgba(16,185,129,0.1));">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:12px;">
        <div>
          <h2 style="font-size:18px;">🌊 ${ar ? 'كتالوج رحلات شرم الشيخ والبحر الأحمر — عرض تفاعلي للضيف' : 'Sharm El Sheikh Diving, Boat & Safari Concierge Showcase'}</h2>
          <p style="font-size:12px;color:var(--muted);">
            ${ar ? 'مصمم للتابلت والموبايل أمام الضيف مباشرةً — صور حقيقية، فيديوهات، مواعيد التحرك من الفندق، وحجز فوري بالعربون أو كامل المبلغ.' : 'Guest-friendly tablet showcase in 8 languages & 4 currencies — crisp photos, hotel pickup times, and instant deposit or full booking.'}
          </p>
        </div>
        <div style="display:flex;gap:7px;flex-wrap:wrap;">
          <button type="button" class="btn btn-emerald" onclick="shareNativeOrCopy('${esc(brochureUrl)}', 'Sea College Sharm Trips')">📲 ${ar ? 'إرسال رابط كل الرحلات للعميل' : 'Send Full Brochure Link'} (/s/${esc(slug)})</button>
          <a class="btn" href="https://wa.me/?text=${encodeURIComponent('🌴 Explore all Sharm El Sheikh Boat, Diving, Safari & Cairo Trips and confirm your booking anytime with me: ' + brochureUrl)}" target="_blank" rel="noopener">💬 WhatsApp Brochure</a>
        </div>
      </div>

      <div class="grid grid-3" style="gap:10px;margin-bottom:12px;">
        <div>
          <label>🌍 ${ar ? 'لغة الضيف (8 لغات)' : 'Guest Language (8 Languages)'}</label>
          <div class="seg-group" style="width:100%;justify-content:space-between;">
            ${langs.map((lg) => `<button type="button" class="seg-btn ${STATE.pitchLang === lg ? 'active' : ''}" style="padding:5px 7px;font-size:11px;" onclick="changePitchLang('${esc(lg)}')">${langLabels[lg] || esc(lg.toUpperCase())}</button>`).join('')}
          </div>
        </div>

        <div>
          <label>💱 ${ar ? 'عملة العرض للضيف' : 'Guest Display Currency'}</label>
          <div class="seg-group" style="width:100%;">
            ${['EUR', 'USD', 'GBP', 'EGP'].map((c) => `<button type="button" class="seg-btn ${STATE.pitchCurr === c ? 'active' : ''}" style="flex:1;" onclick="STATE.pitchCurr='${c}';renderApp();">${c}</button>`).join('')}
          </div>
        </div>

        <div>
          <label>🚐 ${ar ? 'فندق الإقامة وموعد الباص (72 فندق)' : 'Guest Hotel & Pickup Window (72 Resorts)'}</label>
          <div style="display:flex;gap:6px;align-items:center;">
            <select onchange="STATE.pitchHotel=this.value;const o=this.selectedOptions[0];if(o&&o.dataset.pick)STATE.pitchPickup=o.dataset.pick;renderApp();">
              ${hotels.map((h) => `<option value="${esc(h.name)}" data-pick="${esc(h.pickup_window)}" ${h.name === selectedHotelObj.name ? 'selected' : ''}>${esc(h.name)}</option>`).join('')}
            </select>
            <span class="pill" style="white-space:nowrap;padding:7px 10px;">🕒 ${esc(STATE.pitchPickup)}</span>
          </div>
        </div>
      </div>

      <!-- Guest-Safe Family / Group Calculator Bar -->
      <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;padding:10px 12px;border-radius:11px;background:rgba(6,21,38,0.78);border:1px solid var(--border);">
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
          <span style="font-size:12px;font-weight:800;color:var(--cyan);">👨‍👩‍👧 ${ar ? 'عدد الضيوف لحساب إجمالي الرحلة:' : 'Guest Group Calculator:'}</span>
          <label style="margin:0;display:inline-flex;align-items:center;gap:5px;color:var(--text);">👤 Adults:
            <input type="number" min="1" max="40" value="${adlCount}" style="width:62px;padding:5px;" oninput="STATE.pitchPax=Math.max(1,Number(this.value||1));renderApp();">
          </label>
          <label style="margin:0;display:inline-flex;align-items:center;gap:5px;color:var(--text);">🧒 Kids (3–11):
            <input type="number" min="0" max="30" value="${chCount}" style="width:62px;padding:5px;" oninput="STATE.pitchChildren=Math.max(0,Number(this.value||0));renderApp();">
          </label>
          <label style="margin:0;display:inline-flex;align-items:center;gap:5px;color:var(--teal);">👶 Infants (&lt;3 FREE):
            <input type="number" min="0" max="15" value="${infCount}" style="width:58px;padding:5px;" oninput="STATE.pitchInfants=Math.max(0,Number(this.value||0));renderApp();">
          </label>
        </div>
        <div style="font-size:12px;color:#cbd5e1;">
          ✅ <b>Book with Deposit Now</b> &amp; pay remaining balance easily at hotel pickup!
        </div>
      </div>

      ${discPol.is_enabled ? `
        <div id="pitchSpecialDiscountBar" style="margin-top:8px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:space-between;padding:9px 12px;border-radius:11px;background:rgba(245,158,11,0.09);border:1px solid rgba(245,158,11,0.34);">
          <div style="display:flex;gap:7px;align-items:center;flex-wrap:wrap;">
            <span style="font-size:12px;font-weight:800;color:#fde68a;">🎁 ${ar ? 'خصم خاص للضيف (% أو مبلغ):' : 'Special Guest Discount:'}</span>
            <select style="width:auto;padding:5px 8px;" onchange="STATE.pitchDiscType=this.value;if(this.value==='NONE')STATE.pitchDiscVal=0;else if(!STATE.pitchDiscVal)STATE.pitchDiscVal=10;renderApp();">
              <option value="NONE" ${STATE.pitchDiscType === 'NONE' ? 'selected' : ''}>${ar ? 'بدون خصم' : 'Standard Price'}</option>
              <option value="PERCENT" ${STATE.pitchDiscType === 'PERCENT' ? 'selected' : ''}>${ar ? 'نسبة مئوية (%)' : 'Percentage (%)'}</option>
              <option value="AMOUNT" ${STATE.pitchDiscType === 'AMOUNT' ? 'selected' : ''}>${ar ? `مبلغ ثابت (${STATE.pitchCurr})` : `Fixed Amount (${STATE.pitchCurr})`}</option>
            </select>
            ${STATE.pitchDiscType !== 'NONE' ? `
              <input type="number" step="0.5" min="0" value="${Number(STATE.pitchDiscVal || 0)}" style="width:82px;padding:5px;" onchange="STATE.pitchDiscVal=Math.max(0,Number(this.value||0));renderApp();">
              <span style="font-size:11.5px;color:#fde68a;font-weight:800;">${STATE.pitchDiscType === 'PERCENT' ? `% (Max ${discPol.max_discount_pct}%)` : esc(STATE.pitchCurr)}</span>
            ` : ''}
          </div>
          <div style="display:flex;gap:5px;flex-wrap:wrap;">
            <button type="button" class="btn ${STATE.pitchDiscType === 'NONE' ? 'btn-primary' : ''}" style="padding:4px 9px;font-size:11px;" onclick="setPitchDiscount('NONE', 0)">Standard</button>
            <button type="button" class="btn ${STATE.pitchDiscType === 'PERCENT' && Number(STATE.pitchDiscVal) === 5 ? 'btn-amber' : ''}" style="padding:4px 9px;font-size:11px;" onclick="setPitchDiscount('PERCENT', 5)">🎁 -5%</button>
            <button type="button" class="btn ${STATE.pitchDiscType === 'PERCENT' && Number(STATE.pitchDiscVal) === 10 ? 'btn-amber' : ''}" style="padding:4px 9px;font-size:11px;" onclick="setPitchDiscount('PERCENT', 10)">🎁 -10%</button>
            <button type="button" class="btn ${STATE.pitchDiscType === 'PERCENT' && Number(STATE.pitchDiscVal) === 15 ? 'btn-amber' : ''}" style="padding:4px 9px;font-size:11px;" onclick="setPitchDiscount('PERCENT', 15)">🎁 -15%</button>
          </div>
        </div>
      ` : ''}
    </div>

    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;justify-content:space-between;margin-bottom:14px;">
      <div style="display:flex;gap:6px;flex-wrap:wrap;">
        ${catChips.map(([ck, cl]) => `<button type="button" class="btn ${STATE.pitchCat === ck ? 'btn-primary' : ''}" onclick="STATE.pitchCat='${ck}';renderApp();">${esc(cl)}</button>`).join('')}
      </div>
      <input type="search" style="max-width:290px;" placeholder="${ar ? 'ابحث: وايت أيلاند، سفاري، سانت كاترين، القاهرة، دولفين...' : 'Search White Island, Quad Safari, Cairo, Moses, Dolphin...'}" value="${esc(STATE.pitchQuery)}" oninput="STATE.pitchQuery=this.value;renderApp();">
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
              <div style="padding:13px;">
                <div style="font-size:15px;font-weight:800;line-height:1.3;">${esc(it.name || it.name_en)}</div>
                <p style="font-size:11.5px;color:var(--muted);margin-top:5px;line-height:1.45;">${esc(String(it.summary || '').slice(0, 145))}</p>

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

                <details style="margin-top:8px;border-radius:9px;background:rgba(6,21,38,0.72);border:1px solid var(--border);padding:7px 9px;">
                  <summary style="cursor:pointer;font-size:11.5px;font-weight:700;color:var(--cyan);">🎬 Watch Experience Video Preview</summary>
                  <video controls preload="none" playsinline poster="${esc(vidPoster)}" src="${esc(vidSrc)}" style="width:100%;max-height:240px;border-radius:8px;margin-top:8px;background:#000;"></video>
                </details>
              </div>
            </div>

            <div style="padding:10px 13px 13px;border-top:1px solid rgba(148,163,184,0.14);background:rgba(6,21,38,0.5);">
              <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;margin-bottom:8px;gap:6px;flex-wrap:wrap;">
                <span>${isGear ? `Total (${adlCount} ${adlCount === 1 ? 'Diver' : 'Divers'}):` : `Group (${adlCount}A${chCount > 0 ? `+${chCount}C` : ''}${infCount > 0 ? `+${infCount}I` : ''}):`}</span>
                ${dCalc.discInCurr > 0
                  ? `<span><s style="opacity:0.65;font-size:11.5px;margin-right:4px;">${esc(listGroupTxt)}</s><b style="font-size:14px;color:#34d399;">${esc(netGroupTxt)}</b> <span style="font-size:11px;color:#fcd34d;font-weight:800;">(${esc(dCalc.badge)})</span></span>`
                  : `<b style="font-size:14px;color:var(--cyan);">${esc(netGroupTxt)}</b>`
                }
              </div>
              <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <button type="button" class="btn btn-primary" style="flex:1.3;justify-content:center;" onclick="openFastBookModal('${esc(code)}')">⚡ Book Now</button>
                <button type="button" class="btn" onclick="openProductQrModal('${esc(code)}')">📱 QR / Social</button>
                <button type="button" class="btn btn-emerald" onclick="sharePitchWhatsApp('${esc(code)}')">💬 WhatsApp</button>
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
    ? window.SCQR.toSvg(qrTargetUrl, { size: 204 })
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

  return `
    <div class="horizon-bar" style="margin-bottom:14px;">
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <span style="font-weight:800;color:var(--cyan);">🏆 ${ar ? 'حسابي الخاص، باركود QR، عمولاتي الفعلية والجوائز' : 'My Private Account, Shareable QR, Actual Commissions & Awards'}</span>
        ${allSellers.length ? `
          <select style="width:auto;" onchange="changeWalletFilter(null, this.value)">
            ${allSellers.map((s) => `<option value="${esc(s.username)}" ${s.username === prof.username ? 'selected' : ''}>${esc(s.display_name_en)} (@${esc(s.username)} • ${esc(s.branch_scope)})</option>`).join('')}
          </select>
        ` : ''}
        <select style="width:auto;" onchange="changeWalletFilter(this.value, null)">
          ${monthsOpts.map((m) => `<option value="${esc(m)}" ${(w.month || STATE.walletMonth) === m ? 'selected' : ''}>${m === 'ALL' ? (ar ? 'كل الشهور (تراكمي)' : 'All Months (Cumulative)') : esc(m)}</option>`).join('')}
        </select>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <button type="button" class="btn btn-amber" onclick="openAllowanceModal()">💸 ${ar ? 'طلب سلفة / دفعة عمولة خلال الشهر' : 'Request Mid-Month Allowance'}</button>
        <a class="btn btn-emerald" href="${esc(walletExportHref)}">⬇ Export Commissions (.xlsx)</a>
      </div>
    </div>

    <!-- Gamification XP, Earned Best-Seller Awards & Toggleable Target Bar -->
    <div class="card" style="margin-bottom:14px;background:linear-gradient(135deg,rgba(99,102,241,0.14),rgba(0,210,255,0.1));">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
          <span class="xp-pill">⚡ ${esc(prof.level_title || '🌊 Rising Star')} • ${fmt(prof.xp_points || 0)} XP</span>
          ${myAwards.length
            ? myAwards.map((a) => `<span class="award-medal">${esc(a.icon)} ${esc(ar ? a.title_ar : a.title_en)}</span>`).join('')
            : `<span class="pill" style="opacity:0.85;">🎯 Keep booking to unlock 🥇 Daily / 🏆 Weekly / 👑 Monthly Best Seller Medals!</span>`
          }
        </div>
        <span style="font-size:11.5px;color:var(--muted);">🔒 Private Seller Account — Never shown to guests during pitch</span>
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

    <div class="grid grid-2" style="margin-bottom:14px;">
      <div class="card" style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
        <div class="qr-box">${svgQr}</div>
        <div style="flex:1;min-width:220px;">
          <span class="pill">${esc(prof.branch_scope || 'CYRENE')} • REP: ${esc(prof.rep_scope || slug.toUpperCase())}</span>
          <h3 style="font-size:18px;margin-top:6px;">${esc(ar ? (prof.display_name_ar || prof.display_name_en) : (prof.display_name_en || prof.username))}</h3>
          <div style="font-size:11.5px;color:#34d399;word-break:break-all;margin:5px 0 2px;font-weight:800;">📲 Shareable Brochure Link: ${esc(activeShareBrochure)}</div>
          <div style="font-size:11px;color:var(--cyan);word-break:break-all;margin-bottom:6px;">🤿 Diving QR Link: ${esc(qrUrl)}</div>

          ${discPol.is_enabled ? `
            <div style="margin:8px 0 10px;padding:8px 10px;border-radius:10px;background:rgba(245,158,11,0.09);border:1px solid rgba(245,158,11,0.34);">
              <div style="font-size:11px;font-weight:800;color:#fde68a;margin-bottom:5px;">🎁 ${ar ? 'إنشاء رابط خصم خاص موثّق لعملائك (% أو مبلغ €):' : 'Create Personal VIP Discount Share Link & QR (% or €):'}</div>
              <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                <select id="walPromoType" style="width:auto;padding:5px 7px;font-size:11.5px;">
                  <option value="PERCENT" ${STATE.walletPromoType === 'PERCENT' ? 'selected' : ''}>Percentage (%) — Max ${discPol.max_discount_pct}%</option>
                  <option value="AMOUNT" ${STATE.walletPromoType === 'AMOUNT' ? 'selected' : ''}>Fixed Amount (€) — Max €${discPol.max_discount_eur}</option>
                </select>
                <input type="number" step="0.5" min="0" id="walPromoVal" value="${Number(STATE.walletPromoVal || 10)}" style="width:74px;padding:5px;font-size:11.5px;">
                <button type="button" class="btn btn-amber" style="padding:5px 10px;font-size:11.5px;" onclick="generateWalletPromoShareLink()">✨ Generate VIP Link</button>
                ${STATE.walletPromoUrl ? `<button type="button" class="btn" style="padding:5px 8px;font-size:11px;" onclick="STATE.walletPromoUrl='';renderApp();">Reset</button>` : ''}
              </div>
            </div>
          ` : ''}

          <p style="font-size:11px;color:var(--muted);margin-bottom:10px;">
            Share via WhatsApp, Facebook, Instagram or Telegram — when your client books later, it registers directly under your name &amp; sends you an instant 🔔 notification! (Commission calculated on actual net discounted amount: INSHEET 10% Net • OUT_SIDE 25% Net Profit after Supplier Cost).
          </p>
          <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <a class="btn btn-emerald" href="https://wa.me/?text=${encodeURIComponent('🌴 Explore all Sharm El Sheikh Diving, Boat, Safari & Cairo Trips and confirm your booking directly with me: ' + activeShareBrochure)}" target="_blank" rel="noopener">💬 WhatsApp Link</a>
            <a class="btn" style="border-color:#3b82f6;color:#93c5fd;" href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(activeShareBrochure)}" target="_blank" rel="noopener">📘 Facebook</a>
            <a class="btn" style="border-color:#38bdf8;color:#7dd3fc;" href="https://t.me/share/url?url=${encodeURIComponent(activeShareBrochure)}" target="_blank" rel="noopener">✈️ Telegram</a>
            <button type="button" class="btn" onclick="shareNativeOrCopy('${esc(activeShareBrochure)}', 'Sea College Trips')">📲 Copy / Instagram</button>
            <button type="button" class="btn btn-primary" onclick="downloadSellerQrPng('${esc(qrTargetUrl)}', '${esc(slug)}')">⬇ Download PNG QR</button>
          </div>
          <div style="display:flex;gap:5px;flex-wrap:wrap;margin-top:10px;">
            ${Object.entries(locUrls).map(([lg, uLink]) => `<button type="button" class="contact-chip" style="cursor:pointer;" onclick="copyTextPrompt('${esc(uLink)}')">🌐 ${esc(lg.toUpperCase())}</button>`).join('')}
          </div>
        </div>
      </div>

      <div class="grid grid-kpi" style="align-content:start;">
        <div class="card" style="border-color:rgba(16,185,129,0.45);">
          <div class="kpi-label">${ar ? 'العمولة الفعلية المستحقة (رحلات تمت ومحصّلة 100%)' : 'Actual Due Commission (Done & 100% Paid)'}</div>
          <div class="kpi-val pos">${fmt(actualDueComm)} <span style="font-size:11px;color:var(--muted);">EGP</span></div>
          <div class="kpi-sub">Guest Boarded + Deposit &amp; REST Paid</div>
        </div>
        <div class="card" style="border-color:rgba(0,210,255,0.45);">
          <div class="kpi-label">${ar ? 'صافي متاح للصرف الآن أو كسلفة' : 'Net Payable Now (After Allowances)'}</div>
          <div class="kpi-val" style="color:var(--cyan);">${fmt(netDueNow)} <span style="font-size:11px;color:var(--muted);">EGP</span></div>
          <div class="kpi-sub">End-of-Month / Instant Allowance Ready</div>
        </div>
        <div class="card">
          <div class="kpi-label">${ar ? 'سلف ودفعات مستلمة خلال الشهر' : 'Allowances / Installments Taken'}</div>
          <div class="kpi-val neg">${fmt(settledAndAllowances)} <span style="font-size:11px;color:var(--muted);">EGP</span></div>
          <div class="kpi-sub">Requested Pending: ${fmt(brk.requested_pending_allowances_egp || 0)} EGP</div>
        </div>
        <div class="card">
          <div class="kpi-label">${ar ? 'عمولة بانتظار تنفيذ الرحلة أو تحصيل الباقي' : 'Waiting Trip Execution or Guest REST'}</div>
          <div class="kpi-val" style="color:#fcd34d;">${fmt(waitingRestOrTrip)} <span style="font-size:11px;color:var(--muted);">EGP</span></div>
          <div class="kpi-sub">Unlocks when trip done &amp; REST = 0</div>
        </div>
        <div class="card">
          <div class="kpi-label">${ar ? 'إجمالي المبيعات الصافية والخصومات الممنوحة' : 'My Net Sales & Special Discounts'}</div>
          <div class="kpi-val">${fmt(k.gross_sales_egp)} <span style="font-size:11px;color:var(--muted);">EGP</span></div>
          <div class="kpi-sub">${fmt(k.entries_count)} Trips • 🎁 Discounts: ${fmt(discSum.total_discount_egp || 0)} EGP (${fmt(discSum.discounted_trips_count || 0)})</div>
        </div>
        <div class="card">
          <div class="kpi-label">${ar ? 'صافي المستحق الشامل بالراتب' : 'Full Payroll + Comm Net Balance'}</div>
          <div class="kpi-val ${Number(k.net_balance_egp || 0) >= 0 ? 'pos' : 'neg'}">${fmt(k.net_balance_egp)} <span style="font-size:11px;color:var(--muted);">EGP</span></div>
          <div class="kpi-sub">Base ${fmt(k.payroll_base_egp)} + Comm − Deductions</div>
        </div>
      </div>
    </div>

    <!-- Seller's Isolated Trips & Client Confirmations Table -->
    <div class="card" style="margin-bottom:14px;">
      <h3 style="font-size:14px;margin-bottom:8px;">🎫 ${ar ? 'رحلاتي وحجوزات عملائي وحالة التحصيل والعمولة الفعلية' : 'My Trips Only — Booking Status, Special Discounts, Paid Deposit & Actual Commission'} (${myTrips.length})</h3>
      <div class="table-wrap" style="max-height:310px;">
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
    </div>

    ${advRows.length ? `
      <div class="card" style="margin-bottom:14px;">
        <h3 style="font-size:14px;margin-bottom:8px;">💸 ${ar ? 'سجل السلف والدفعات المسحوبة من العمولة' : 'My Mid-Month Commission Allowances & Installments'} (${advRows.length})</h3>
        <div class="table-wrap" style="max-height:200px;">
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
      </div>
    ` : ''}

    <div class="card" style="margin-bottom:14px;">
      <h3 style="font-size:14px;margin-bottom:8px;">📋 ${ar ? 'دفتر حساب العمولة التفصيلي (10% داخلي / 25% صافي خارجي بعد تكلفة المورد)' : 'Detailed Commission Calculation Ledger (INSHEET 10% Gross vs OUT_SIDE 25% Net)'} (${rows.length})</h3>
      <div class="table-wrap" style="max-height:340px;">
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
    </div>

    <div class="card">
      <h3 style="font-size:14px;margin-bottom:8px;">🌐 ${ar ? 'عملاء الروابط والباركود المسجلين باسمك' : 'Attributed Shareable Link & QR Bookings'} (${leads.length})</h3>
      <div class="table-wrap" style="max-height:240px;">
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
    </div>
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

boot();
</script>
</body>
</html>
