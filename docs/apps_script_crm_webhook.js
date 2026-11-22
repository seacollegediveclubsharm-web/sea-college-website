/**
 * Sea College Dive Club — Google Sheets CRM & Cross-Channel (Google + Meta) Offline Conversion Webhook
 * ----------------------------------------------------------------------------------------------------
 * Setup (takes 60 seconds):
 *   1. Open the Sheet: https://docs.google.com/spreadsheets/d/1ItvQGWnrHwnSbyGDoGNtV2CkVwNJXIyU_aaIFxIOGbc/edit
 *   2. Extensions -> Apps Script -> delete placeholder code -> paste this ENTIRE file -> Save.
 *   3. Deploy -> Manage deployments -> Edit -> New version -> Deploy (or New deployment -> Web app).
 */

function inferChannel(data) {
  const gclid = String(data.gclid || data.gbraid || data.wbraid || '').trim();
  const fbclid = String(data.fbclid || data.fbc || data._fbc || '').trim();
  const ttclid = String(data.ttclid || '').trim();
  const msclkid = String(data.msclkid || '').trim();
  const src = String(data.utm_source || '').trim().toLowerCase();

  if (data.channel_source) return String(data.channel_source);
  if (gclid && fbclid) return 'Cross-Channel (Google + Meta)';
  if (gclid || src === 'google') return 'Google Ads';
  if (fbclid || src === 'meta' || src === 'facebook' || src === 'instagram' || src === 'ig' || src === 'fb') return 'Meta Ads';
  if (ttclid || src === 'tiktok') return 'TikTok Ads';
  if (msclkid || src === 'bing' || src === 'microsoft') return 'Microsoft Ads';
  if (src) return src.toUpperCase();
  return 'Organic / Direct';
}

function doPost(e) {
  try {
    const data = JSON.parse((e && e.postData && e.postData.contents) || '{}');
    const ss = SpreadsheetApp.getActiveSpreadsheet();
    const type = String(data.type || 'lead').toLowerCase();
    const chSource = inferChannel(data);
    const firstCh = String(data.first_channel || chSource);
    const journey = String(data.journey_path || chSource);

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
      const sh = ss.getSheetByName('Checkins') || ss.insertSheet('Checkins');
      sh.appendRow([
        data.submitted_at || new Date().toISOString(),
        data.lead_event_id || data.checkin_id || '',
        data.name || '',
        data.phone || '',
        data.arrival_date || '',
        data.hotel || '',
        data.room_number || '',
        data.divers_count || 1,
        data.cert_agency || '',
        data.cert_number || '',
        data.logged_dives || '',
        data.last_dive_date || '',
        data.height_cm || '',
        data.weight_kg || '',
        data.wetsuit_size || '',
        data.fin_size || '',
        data.bcd_size || '',
        data.nitrox_request || 'no',
        data.medical_flags || 'all_no',
        data.medical_waiver || 'yes',
        data.gear_notes || '',
        data.lang || 'en',
        data.gclid || data.gbraid || data.wbraid || '',
        data.fbclid || '',
        data.ttclid || '',
        data.msclkid || ''
      ]);
    } else if (type === 'whatsapp_click') {
      const waSheet = ss.getSheetByName('WhatsApp_Clicks');
      if (waSheet) {
        waSheet.appendRow([
          data.received_at || new Date().toISOString(),
          data.event_id || '',
          data.kind || 'whatsapp_click',
          data.placement || '',
          data.lang || 'en',
          data.interest || '',
          data.people || 1,
          data.date || '',
          data.hotel || '',
          data.estimated_total_eur || 0,
          data.gclid || data.gbraid || data.wbraid || '',
          data.fbclid || '',
          data.ttclid || '',
          data.msclkid || '',
          data.fbc || data._fbc || '',
          data.fbp || data._fbp || '',
          data.utm_source || '',
          data.utm_medium || '',
          data.utm_campaign || '',
          data.page_url || '',
          chSource,
          journey
        ]);
      }
      // Preserves exact columns A..Y (1..25) and appends Channel_Source, First_Touch_Channel, Journey_Path in Z..AB (26..28)
      const sh = ss.getSheetByName('Leads');
      if (sh && (data.gclid || data.gbraid || data.wbraid || data.fbclid || data.fbc || data._fbc || data.ttclid || data.msclkid || !waSheet)) {
        sh.appendRow([
          data.received_at || new Date().toISOString(),
          data.event_id || '',
          'WhatsApp_Clicked',
          '',
          '(WhatsApp Click — ' + (data.placement || 'site') + ')',
          '',
          '',
          data.interest || '',
          data.people || 1,
          data.date || '',
          data.hotel || '',
          '',
          data.lang || 'en',
          data.estimated_total_eur || 0,
          JSON.stringify([{ item_id: data.item_id || '', page: data.page_url || '' }]),
          data.gclid || data.gbraid || data.wbraid || '',
          data.fbclid || '',
          data.utm_source || '',
          data.utm_medium || '',
          data.utm_campaign || '',
          '',
          data.ttclid || '',
          data.msclkid || '',
          data.fbc || data._fbc || '',
          data.fbp || data._fbp || '',
          chSource,
          firstCh,
          journey
        ]);
      }
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
        data.gclid || data.gbraid || data.wbraid || '',
        data.fbclid || '',
        data.utm_source || '',
        data.utm_medium || '',
        data.utm_campaign || '',
        '',
        data.ttclid || '',
        data.msclkid || '',
        data.fbc || data._fbc || '',
        data.fbp || data._fbp || '',
        chSource,
        firstCh,
        journey
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
 * and fill Leads!D (Final_Paid_EUR): queues row in BOTH Google_Ads_OCT_Export (if gclid)
 * AND Meta_Offline_CAPI_Export (if fbclid / _fbc / email / phone)!
 */
function onEdit(e) {
  if (!e || !e.range) return;
  const sh = e.range.getSheet();
  if (sh.getName() !== 'Leads') return;
  const row = e.range.getRow();
  if (row < 2) return;

  const vals = sh.getRange(row, 1, 1, 28).getValues()[0];
  const eventId = String(vals[1] || '').trim();
  const status = String(vals[2] || '').trim();
  const paidEur = parseFloat(vals[3] || vals[13] || 0);
  const phone = String(vals[5] || '').trim();
  const email = String(vals[6] || '').trim();
  const interest = String(vals[7] || '').trim();
  const gclid = String(vals[15] || '').trim();
  const fbclid = String(vals[16] || '').trim();
  const exported = String(vals[20] || '').trim();
  const fbc = String(vals[23] || '').trim();
  const fbp = String(vals[24] || '').trim();

  if (status === 'Paid' && !exported) {
    const convTime = Utilities.formatDate(new Date(), 'Africa/Cairo', 'yyyy-MM-dd HH:mm:ss+02:00');
    const tags = [];
    if (gclid) {
      const oct = e.source.getSheetByName('Google_Ads_OCT_Export');
      if (oct) {
        const convName = eventId.indexOf('wa_') === 0 ? 'WhatsApp Lead' : 'Reservation Form Submitted';
        oct.appendRow([gclid, convName, convTime, paidEur > 0 ? paidEur : 50, 'EUR']);
        tags.push('GADS');
      }
    }
    if (fbclid || fbc || email || phone) {
      const metaOct = e.source.getSheetByName('Meta_Offline_CAPI_Export') || e.source.insertSheet('Meta_Offline_CAPI_Export');
      metaOct.appendRow([convTime, eventId, 'Purchase', paidEur > 0 ? paidEur : 50, 'EUR', fbclid, fbc, fbp, email, phone, interest, 'READY_FOR_CAPI']);
      tags.push('META');
    }
    if (tags.length > 0) {
      sh.getRange(row, 21).setValue('QUEUED(' + tags.join('+') + ') ' + convTime);
    }
  }
}
