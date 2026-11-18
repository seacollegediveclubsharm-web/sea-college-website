/**
 * Sea College Dive Club — Google Sheets CRM & Offline Conversion Webhook
 * ----------------------------------------------------------------------
 * Setup (takes 60 seconds):
 *   1. Open the Sheet: https://docs.google.com/spreadsheets/d/1ItvQGWnrHwnSbyGDoGNtV2CkVwNJXIyU_aaIFxIOGbc/edit
 *   2. Extensions -> Apps Script -> delete placeholder code -> paste this ENTIRE file -> Save.
 *   3. Deploy -> New deployment -> Select type: Web app
 *      - Execute as: Me (seacollegediveclubsharm@gmail.com)
 *      - Who has access: Anyone
 *      - Click Deploy -> Copy the Web App URL (https://script.google.com/macros/s/.../exec).
 *   4. Paste that URL into CRM_WEBHOOK_URL in config/secrets.php (or .env) on Hostinger.
 */

function doPost(e) {
  try {
    const data = JSON.parse((e && e.postData && e.postData.contents) || '{}');
    const ss = SpreadsheetApp.getActiveSpreadsheet();
    const type = String(data.type || 'lead').toLowerCase();

    if (type === 'newsletter') {
      const sh = ss.getSheetByName('Newsletter');
      sh.appendRow([
        data.subscribed_at || new Date().toISOString(),
        data.event_id || '',
        data.email || '',
        data.lang || 'en',
        data.utm_source || '',
        data.utm_campaign || '',
        data.consent_state || ''
      ]);
    } else if (type === 'checkin') {
      const sh = ss.getSheetByName('Checkins');
      sh.appendRow([
        data.submitted_at || new Date().toISOString(),
        data.lead_event_id || '',
        data.name || '',
        data.cert_agency || '',
        data.cert_number || '',
        data.wetsuit_size || '',
        data.fin_size || '',
        data.bcd_size || '',
        data.medical_waiver || 'yes'
      ]);
    } else {
      const sh = ss.getSheetByName('Leads');
      sh.appendRow([
        data.received_at || new Date().toISOString(),
        data.event_id || '',
        'New',
        '',
        data.name || '',
        data.phone || '',
        data.email || '',
        data.interest || '',
        data.people || 1,
        data.date || '',
        data.hotel || '',
        data.level || '',
        data.lang || 'en',
        data.estimated_total_eur || 0,
        JSON.stringify(data.cart || []),
        data.gclid || '',
        data.fbclid || '',
        data.utm_source || '',
        data.utm_medium || '',
        data.utm_campaign || '',
        ''
      ]);
    }

    return ContentService.createTextOutput(JSON.stringify({ ok: true }))
      .setMimeType(ContentService.MimeType.JSON);
  } catch (err) {
    return ContentService.createTextOutput(JSON.stringify({ ok: false, error: String(err) }))
      .setMimeType(ContentService.MimeType.JSON);
  }
}

/**
 * Trigger automatically when staff change Leads!C (Status) to "Paid"
 * and fill Leads!D (Final_Paid_EUR): queues row in Google_Ads_OCT_Export.
 */
function onEdit(e) {
  if (!e || !e.range) return;
  const sh = e.range.getSheet();
  if (sh.getName() !== 'Leads') return;
  const row = e.range.getRow();
  if (row < 2) return;

  const vals = sh.getRange(row, 1, 1, 21).getValues()[0];
  const status = String(vals[2] || '').trim();
  const paidEur = parseFloat(vals[3] || vals[13] || 0);
  const gclid = String(vals[15] || '').trim();
  const exported = String(vals[20] || '').trim();

  if (status === 'Paid' && gclid && !exported) {
    const oct = e.source.getSheetByName('Google_Ads_OCT_Export');
    const convTime = Utilities.formatDate(new Date(), 'Africa/Cairo', 'yyyy-MM-dd HH:mm:ss+02:00');
    oct.appendRow([gclid, 'Reservation Form Submitted', convTime, paidEur > 0 ? paidEur : 50, 'EUR']);
    sh.getRange(row, 21).setValue('QUEUED ' + convTime);
  }
}
