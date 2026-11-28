/**
 * Sea College Dive Club — Marketing & Sales Hub Automation + Built-In Gemini CMO Assistant
 *
 * Attach this script to:
 *   Sea College Dive Club - Marketing & Sales Operations Hub
 *   (Spreadsheet ID: 15vrkKvIO9dGbIKOOvHy2MtSf7uKViyvSq8PWb4EINMY)
 *
 * HOW TO INSTALL (Takes 60 seconds):
 * 1. Open the Marketing & Sales Operations Hub Google Sheet -> Extensions -> Apps Script.
 * 2. Paste this entire file into Code.gs and click Save (💾).
 * 3. Reload the Google Sheet -> a new top menu "🤿 Sea College Marketing" appears!
 * 4. Optional (for in-Sheet Gemini AI generation): Project Settings -> Script Properties ->
 *    Add property GEMINI_API_KEY = <your Google AI Studio key>.
 */

var CONFIG = {
  MKT_HUB_ID: '15vrkKvIO9dGbIKOOvHy2MtSf7uKViyvSq8PWb4EINMY',
  LEADS_CRM_ID: '1ItvQGWnrHwnSbyGDoGNtV2CkVwNJXIyU_aaIFxIOGbc',
  ADS_SYNC_ID: '15IkDV14kxGAbr8DSGOB6auNe4paTNOa5UXmw9d5kVtk',
  PROD_DOMAIN: 'https://seacollegediveclub.com',
  GOOGLE_REVIEW_URL: 'https://search.google.com/local/writereview?placeid=ChIJ75cBQp5LUxQRMjrNYQx93g0',
  TRIPADVISOR_REVIEW_URL: 'https://www.tripadvisor.com/UserReview-g297555-d26104236-Sea_college_dive_club-Sharm_El_Sheikh_South_Sinai_Red_Sea_and_Sinai.html',
  EUR_TO_EGP: 52.0
};

function onOpen() {
  SpreadsheetApp.getUi()
    .createMenu('🤿 Sea College Marketing')
    .addItem('1. Sync Live CRM & Google Ads KPIs Now', 'syncAllLiveMetrics')
    .addItem('2. Build 1-Click WhatsApp Sales & Review Queue', 'buildWhatsAppQuickQueue')
    .addItem('3. Check Website & 8-Language URLs Health', 'verifyWebsiteHealth')
    .addSeparator()
    .addItem('4. Ask Gemini CMO (Generate Weekly Ads & Social Plan)', 'askGeminiCMO')
    .addItem('5. Install Automatic Daily 08:00 Sync Trigger', 'installDailyTrigger')
    .addToUi();
}

/**
 * 1. Pulls live Leads, Paid Bookings, EUR Revenue & 30d Google Ads Spend directly into Tab 01.
 */
function syncAllLiveMetrics() {
  var hub = SpreadsheetApp.openById(CONFIG.MKT_HUB_ID);
  var kpiSheet = hub.getSheetByName('01_Executive_KPI_Hub');

  var crm = SpreadsheetApp.openById(CONFIG.LEADS_CRM_ID);
  var leadsSheet = crm.getSheetByName('Leads');
  var leadsData = leadsSheet.getDataRange().getValues();

  var totalLeads = Math.max(0, leadsData.length - 1);
  var paidBookings = 0;
  var paidRevenueEur = 0;

  for (var i = 1; i < leadsData.length; i++) {
    var status = String(leadsData[i][2] || '').trim().toLowerCase();
    var finalEur = parseFloat(leadsData[i][3]) || 0;
    if (status === 'paid') {
      paidBookings++;
      paidRevenueEur += finalEur;
    }
  }

  var ads = SpreadsheetApp.openById(CONFIG.ADS_SYNC_ID);
  var campSheet = ads.getSheetByName('Campaigns');
  var campData = campSheet.getDataRange().getValues();
  var spendEgp30d = 0;
  for (var c = 1; c < campData.length; c++) {
    spendEgp30d += parseFloat(campData[c][10]) || 0; // Cost_30d_EGP
  }
  var spendEur30d = spendEgp30d / CONFIG.EUR_TO_EGP;
  var aovEur = paidBookings > 0 ? (paidRevenueEur / paidBookings) : 340;
  var closeRate = totalLeads > 0 ? (paidBookings / totalLeads) : 0.22;

  // Write Month 1 Actuals into Column F (rows 10, 12, 15, 16, 17)
  kpiSheet.getRange('F10').setValue(Math.round(spendEur30d * 100) / 100);
  kpiSheet.getRange('F12').setValue(totalLeads);
  kpiSheet.getRange('F15').setValue(closeRate);
  kpiSheet.getRange('F16').setValue(paidBookings);
  kpiSheet.getRange('F17').setValue(Math.round(aovEur * 100) / 100);

  SpreadsheetApp.getActiveSpreadsheet().toast(
    'Synced! Leads: ' + totalLeads + ' | Paid Bookings: ' + paidBookings + ' | Paid EUR: €' + paidRevenueEur.toFixed(2),
    'Sea College Sync Complete',
    6
  );
}

/**
 * 2. Generates a live '10_WhatsApp_Sales_Queue' tab with 1-click WhatsApp links in each guest's language!
 */
function buildWhatsAppQuickQueue() {
  var hub = SpreadsheetApp.openById(CONFIG.MKT_HUB_ID);
  var qSheet = hub.getSheetByName('10_WhatsApp_Sales_Queue');
  if (!qSheet) {
    qSheet = hub.insertSheet('10_WhatsApp_Sales_Queue');
  } else {
    qSheet.clearContents();
  }

  var crm = SpreadsheetApp.openById(CONFIG.LEADS_CRM_ID);
  var rows = crm.getSheetByName('Leads').getDataRange().getValues();

  var out = [[
    'Received_At', 'Name', 'Phone', 'Lang', 'Status', 'Interest', 'Est_EUR',
    '1-Click Instant Quote WhatsApp', '1-Click Pre-Arrival Check-In WhatsApp', '1-Click Post-Dive Review WhatsApp'
  ]];

  for (var i = 1; i < rows.length; i++) {
    var r = rows[i];
    var name = r[4] || 'Diver';
    var rawPhone = String(r[5] || '').replace(/[^0-9]/g, '');
    if (!rawPhone) continue;
    var lang = String(r[12] || 'en').toLowerCase();
    var status = r[2] || 'New';
    var interest = r[7] || 'Red Sea Dive';
    var estEur = r[13] || '';
    var langPfx = (lang && lang !== 'en') ? ('/' + lang) : '';

    var quoteMsg = getLocalizedQuoteGreeting(lang, name, interest, estEur);
    var checkinMsg = 'Hi ' + name + '! Your Sea College Dive Club reservation is confirmed. Complete your 60-second Online Diver Check-In before arrival: ' + CONFIG.PROD_DOMAIN + langPfx + '/check-in';
    var reviewMsg = 'Thank you for diving with Sea College Dive Club, ' + name + '! 🤿 Whenever you have 30 seconds, sharing your honest experience helps fellow divers find us:\nGoogle: ' + CONFIG.GOOGLE_REVIEW_URL + '\nTripadvisor: ' + CONFIG.TRIPADVISOR_REVIEW_URL;

    out.push([
      r[0], name, rawPhone, lang, status, interest, estEur,
      '=HYPERLINK("https://wa.me/' + rawPhone + '?text=' + encodeURIComponent(quoteMsg) + '","📲 Send Quote")',
      '=HYPERLINK("https://wa.me/' + rawPhone + '?text=' + encodeURIComponent(checkinMsg) + '","📋 Send Check-In")',
      '=HYPERLINK("https://wa.me/' + rawPhone + '?text=' + encodeURIComponent(reviewMsg) + '","⭐ Request Review")'
    ]);
  }

  qSheet.getRange(1, 1, out.length, out[0].length).setValues(out);
  qSheet.setFrozenRows(1);
}

function getLocalizedQuoteGreeting(lang, name, interest, eur) {
  var pricePart = eur ? (' (€' + eur + ' total, €0 upfront fee)') : '';
  var map = {
    ru: 'Здравствуйте, ' + name + '! 🤿 Дайв-центр Sea College PADI 5* (Шарм-эль-Шейх). Получили вашу заявку: ' + interest + pricePart + '. Подскажите ваш отель и дату для бесплатного трансфера?',
    it: 'Ciao ' + name + '! 🤿 Benvenuto a Sea College Dive Club PADI 5* Sharm. Abbiamo ricevuto la tua richiesta per ' + interest + pricePart + '. In quale hotel alloggi per il transfer gratuito?',
    de: 'Hallo ' + name + '! 🤿 Willkommen im Sea College Dive Club PADI 5* Sharm. Wir haben deine Anfrage für ' + interest + pricePart + ' erhalten. Welches Hotel dürfen wir für den kostenlosen Transfer eintragen?',
    pl: 'Cześć ' + name + '! 🤿 Tu baza PADI 5* Sea College w Sharm. Otrzymaliśmy Twoje zapytanie: ' + interest + pricePart + '. Z jakiego hotelu zorganizować darmowy transfer?',
    fr: 'Bonjour ' + name + ' ! 🤿 Centre PADI 5* Sea College Sharm. Nous avons bien reçu votre demande pour ' + interest + pricePart + '. Quel est votre hôtel pour le transfert gratuit ?',
    ar: 'مرحباً ' + name + '! 🤿 أهلاً بك في مركز غوص سي كوليدج PADI 5 نجوم بشرم الشيخ. استلمنا طلبك بخصوص ' + interest + pricePart + '. ما اسم الفندق لترتيب الانتقالات المجانية؟',
    zh: '您好 ' + name + '！🤿 这里是沙姆沙伊赫 Sea College PADI五星潜店。已收到您的预订咨询：' + interest + pricePart + '。请问您入住哪家酒店以便安排免费接送？'
  };
  return map[lang] || ('Hi ' + name + '! 🤿 Welcome to Sea College Dive Club (PADI 5★ Resort, Sharm El Sheikh). We received your inquiry for ' + interest + pricePart + '. Which hotel are you staying at so we can arrange your free pickup?');
}

/**
 * 3. Verifies HTTP 200 across all 8 language hubs & sitemap.xml.
 */
function verifyWebsiteHealth() {
  var base = CONFIG.PROD_DOMAIN;
  var paths = ['/', '/sitemap.xml', '/ru/', '/it/', '/pl/', '/de/', '/fr/', '/ar/', '/zh/'];
  var report = [];
  for (var i = 0; i < paths.length; i++) {
    var res = UrlFetchApp.fetch(base + paths[i], { muteHttpExceptions: true, followRedirects: true });
    report.push(paths[i] + ' -> HTTP ' + res.getResponseCode());
  }
  SpreadsheetApp.getUi().alert('Website Health Check (' + base + '):\n\n' + report.join('\n'));
}

/**
 * 4. Calls Gemini API directly using live KPI data from Tab 01 to output this week's action plan.
 */
function askGeminiCMO() {
  var apiKey = PropertiesService.getScriptProperties().getProperty('GEMINI_API_KEY');
  if (!apiKey) {
    SpreadsheetApp.getUi().alert('Set GEMINI_API_KEY in Extensions -> Apps Script -> Project Settings -> Script Properties first.');
    return;
  }
  var hub = SpreadsheetApp.openById(CONFIG.MKT_HUB_ID);
  var kpiValues = hub.getSheetByName('01_Executive_KPI_Hub').getDataRange().getDisplayValues();
  var prompt = 'You are the CMO of Sea College Dive Club (PADI 5* Resort, Sharm El Sheikh, 8-language website, 4.9 stars across 3,270 Google reviews). Based on this live KPI scorecard, give 5 concrete actions for the next 7 days to grow direct EUR bookings:\n' + JSON.stringify(kpiValues);

  var url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro:generateContent?key=' + apiKey;
  var resp = UrlFetchApp.fetch(url, {
    method: 'post',
    contentType: 'application/json',
    payload: JSON.stringify({ contents: [{ parts: [{ text: prompt }] }] }),
    muteHttpExceptions: true
  });
  var json = JSON.parse(resp.getContentText());
  var answer = (((json.candidates || [])[0] || {}).content || {}).parts[0].text || resp.getContentText();

  var aiSheet = hub.getSheetByName('11_Gemini_Weekly_Briefs') || hub.insertSheet('11_Gemini_Weekly_Briefs');
  aiSheet.appendRow([new Date(), answer]);
  SpreadsheetApp.getActiveSpreadsheet().toast('Saved new weekly plan to 11_Gemini_Weekly_Briefs!', 'Gemini CMO', 6);
}

function installDailyTrigger() {
  ScriptApp.newTrigger('syncAllLiveMetrics').timeBased().everyDays(1).atHour(8).create();
  SpreadsheetApp.getActiveSpreadsheet().toast('Daily 08:00 sync trigger installed!', 'Automation Active', 5);
}
