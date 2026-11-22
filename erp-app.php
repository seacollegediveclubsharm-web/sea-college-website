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
      <h1 id="appTitle">Sea College Dive Club — Unified Ecosystem App <span class="pill" style="background:rgba(16,185,129,0.2);color:#34d399;border-color:rgba(16,185,129,0.4);">🧪 TRIAL v1.0</span></h1>
      <p id="appSub">Big-Data SQL Warehouse • Role-Based Employee & Owner Pilot Edition</p>
    </div>
  </div>
  <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
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
          <div style="font-size:12px;font-weight:700;">${esc(STATE.lang === 'ar' ? a.display_name_ar : a.display_name_en)}</div>
          <div style="font-size:11px;color:var(--muted);">@${esc(a.username)} • ${esc(STATE.lang === 'ar' ? (a.job_title_ar || a.role_code) : (a.job_title_en || a.role_code))} (${esc(a.branch_scope)})</div>
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
  document.getElementById('fabFeedback').style.display = 'none';
  document.getElementById('appView').style.display = 'none';
  document.getElementById('loginView').style.display = 'block';
}

async function loadUserData() {
  document.getElementById('loginView').style.display = 'none';
  document.getElementById('appView').style.display = 'block';
  document.getElementById('logoutBtn').style.display = 'inline-flex';
  document.getElementById('fabFeedback').style.display = 'block';
  const ub = document.getElementById('userBadge');
  ub.style.display = 'inline-flex';
  ub.innerHTML = `${renderAvatarHtml(STATE.user, 24)} <span>${esc(STATE.lang === 'ar' ? STATE.user.display_name_ar : STATE.user.display_name_en)}</span>`;

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
    tabs.push({ id: 'team_profiles', label: ar ? '👥 ملفات فريق العمل (24)' : '👥 Team Profiles & Directory' });
    tabs.push({ id: 'trial_pilot', label: ar ? '🧪 إدارة النسخة التجريبية والملاحظات' : '🧪 Trial Launch & Feedback' });
    tabs.push({ id: 'exports_hub', label: ar ? '📥 تصدير الشيتات والتقارير' : '📥 Export Sheets (.xlsx / .csv)' });
    tabs.push({ id: 'profitability', label: ar ? '🏆 الأعلى مبيعاً وربحية الرحلات والمراكب' : '🏆 Best Sellers, Trips & Boats ROI' });
  }
  if (hasPerm('bi.view_partner_fleet') && !hasPerm('bi.view_executive')) {
    tabs.push({ id: 'partner_fleet', label: ar ? '🛥️ حصص المراكب وعمرة الازق' : '🛥️ My Partner Vessels & Drydock' });
  }
  if (hasPerm('gl.view_all_ledgers')) {
    tabs.push({ id: 'accounting_gl', label: ar ? '📚 الحسابات والعملاء والمرتبات' : '📚 Ledgers, AR/AP & Payroll' });
    if (!hasPerm('bi.view_executive')) {
      tabs.push({ id: 'trial_pilot', label: ar ? '🧪 ملاحظات النسخة التجريبية' : '🧪 Trial Feedback Inbox' });
    }
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
  if (!hasPerm('bi.view_executive')) {
    tabs.push({ id: 'team_profiles', label: ar ? '👥 دليل أرقام وإيميلات الفريق' : '👥 Team Contacts Directory' });
  }
  tabs.push({ id: 'my_profile', label: ar ? '👤 ملفي الشخصي وصورتي' : '👤 My Profile' });
  return tabs;
}

function renderApp() {
  if (!STATE.user) {
    renderDirectory();
    return;
  }
  const ar = STATE.lang === 'ar';
  const u = STATE.user;
  document.getElementById('userBadge').innerHTML = `${renderAvatarHtml(u, 24)} <span>${esc(ar ? u.display_name_ar : u.display_name_en)}</span>`;
  document.getElementById('welcomeAvatar').innerHTML = renderAvatarHtml(u, 62);
  document.getElementById('welcomeName').textContent = ar ? u.display_name_ar : u.display_name_en;
  const titleTxt = ar ? (u.job_title_ar || u.role_name_ar) : (u.job_title_en || u.role_name_en);
  document.getElementById('welcomeScope').textContent =
    `${titleTxt} • Role: ${u.role_code} • Branch: ${u.branch_scope} • Vessels: ${u.vessel_scope}`;

  const cChips = [];
  if (u.phone) {
    cChips.push(`<a class="contact-chip" href="tel:${esc(u.phone.replace(/\s+/g, ''))}">📞 ${esc(u.phone)}</a>`);
    const wd = waDigits(u.phone);
    if (wd.length >= 8) {
      cChips.push(`<a class="contact-chip" href="https://wa.me/${esc(wd)}" target="_blank" rel="noopener">💬 WhatsApp</a>`);
    }
  }
  if (u.email) {
    cChips.push(`<a class="contact-chip" href="mailto:${esc(u.email)}">✉️ ${esc(u.email)}</a>`);
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

  const box = document.getElementById('tabContent');
  if (STATE.activeTab === 'exec_bi' && STATE.bi) {
    box.innerHTML = renderExecBi(ar);
    drawTrendChart();
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
    { key: 'res_accounting_2026', icon: '🔗', title: ar ? 'ربط حجوزات مايو–نوفمبر 2026 بالحسابات والقيود' : 'May–Nov 2026 Reservations-to-Accounting & GL Pack', desc: ar ? 'مطابقة الـ 7 شهور الفعلية + 209 قيد يومية مزدوج متوازن + طاقم البحر' : '7-month audit bridge + all 7,286 rows + 209 balanced GL journals + 117 Crew' },
    { key: 'pnl', icon: '📊', title: ar ? 'قائمة الأرباح والخسائر المستمرة (18 شهر)' : 'Continuous 18-Month Profit & Loss (Jun 25–Nov 26)', desc: ar ? 'ملخص الفروع + كافة بنود الإيراد والمصروف النقدي والمستحق' : 'Period summary + all Cash & Accrued P&L lines through Nov 2026' },
    { key: 'cashflow', icon: '💵', title: ar ? 'التدفقات النقدية وإذون الصرف' : 'Multi-Currency Cash Flow & Expenses', desc: ar ? 'حركة الخزينة اليومية (LE/$/€/£/VISA) + 5,692 إذن صرف' : 'Daily multi-currency cashbox + 5,692 expense vouchers' },
    { key: 'best_sellers', icon: '🏆', title: ar ? 'الأعلى مبيعاً في الشهر والفترة' : 'Best Sellers of the Month / Period', desc: ar ? 'ترتيب مناديب المبيعات والشركات والجنسيات وأعلى 100 فندق' : 'Top Sales Reps, B2B Agencies, Nationalities & Top 100 Hotels' },
    { key: 'profitable_trips', icon: '🤿', title: ar ? 'أكثر الرحلات والكورسات ربحية' : 'Most Profitable Trips, Tours & Courses', desc: ar ? 'الإيراد الفعلي مقابل التكلفة المباشرة وصافي هامش الربح %' : 'Revenue vs Direct COGS, Net Margin EGP & Margin % per activity' },
    { key: 'profitable_boats', icon: '🛥️', title: ar ? 'ربحية المراكب وعمرة الازق والأصول' : 'Most Profitable Boats, Drydock & Assets', desc: ar ? 'أوشينو، عمر 4، نزار، سحس، الاسبيد + رحلات المراكب حتى نوفمبر 2026' : 'Fleet ROI + daily boat trips through Nov 2026 + Drydock + 119 Gear tags' },
    { key: 'full_data_pack', icon: '🗂️', title: ar ? 'سجل الحجوزات الكامل والعملاء والمرتبات' : 'Full Row-Level Reservations, AR/AP & Payroll', desc: ar ? '15,667 حجز مربوط بالحسابات + 63 عميل ومورد + 758 سجل مرتبات' : '15,667 GL-linked reservations + 63 AR/AP accounts + 758 payroll records' },
    { key: 'team_profiles', icon: '👥', title: ar ? 'دليل ملفات وصور وأرقام وإيميلات فريق العمل' : 'Team User Profiles, Phones & Emails Directory', desc: ar ? 'بيانات وصور وأرقام واتساب وإيميلات وصلاحيات الـ 24 مستخدم' : 'Complete 24-user profile sheet with phones, emails, photos & RBAC scopes' }
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
  const resAudit = ws.res_acc_audit || [];
  const glJ = ws.gl_journals || [];
  return `
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
      <a class="btn btn-primary" href="${exportUrl('res_accounting_2026', 'xlsx')}">🔗 Export May–Nov 2026 Reservations & GL Bridge (.xlsx)</a>
      <a class="btn btn-emerald" href="${exportUrl('pnl', 'xlsx')}">⬇ Export 18M P&L (.xlsx)</a>
      <a class="btn btn-emerald" href="${exportUrl('cashflow', 'xlsx')}">⬇ Export Cash Flow & Expenses (.xlsx)</a>
      <a class="btn" href="${exportUrl('full_data_pack', 'xlsx')}">⬇ Export Master AR/AP & Payroll (.xlsx)</a>
    </div>

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

let deferredInstallPrompt = null;
window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredInstallPrompt = e;
});

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

boot();
</script>
</body>
</html>
