/**
 * Sea College Dive Club — Google Ads <-> Google Sheets Sync Hub
 * -------------------------------------------------------------
 * How to install (takes ~2 minutes, no API token needed):
 *   1. Open https://ads.google.com -> Tools -> Bulk actions -> Scripts
 *   2. Click the blue "+" button -> "New script"
 *   3. Name it: Sea College Sync Hub
 *   4. Delete the placeholder code, paste this ENTIRE file, click "Authorize", then click "Run"
 *   5. In the Scripts list, set Frequency = "Hourly" so your sheet stays live automatically.
 */

const SPREADSHEET_URL = 'https://docs.google.com/spreadsheets/d/15IkDV14kxGAbr8DSGOB6auNe4paTNOa5UXmw9d5kVtk/edit';

function main() {
  const ss = SpreadsheetApp.openByUrl(SPREADSHEET_URL);
  const isPreview = AdsApp.getExecutionInfo().isPreview();

  // 1) Process any user-approved actions in Action_Queue first (skipped in Preview mode)
  processActionQueue(ss, isPreview);

  // 2) Export live Google Ads data into each tab
  syncAccountOverview(ss);
  syncCampaigns(ss);
  syncAdGroups(ss);
  syncKeywords(ss);
  syncSearchTerms(ss);
  syncAds(ss);
  syncConversionActions(ss);

  Logger.log('Sea College Google Ads Sync completed successfully.');
}

/* ---------------- Helpers ---------------- */

function getOrCreateSheet(ss, name, headers) {
  let sheet = ss.getSheetByName(name);
  if (!sheet) {
    sheet = ss.insertSheet(name);
    sheet.getRange(1, 1, 1, headers.length).setValues([headers]);
  }
  return sheet;
}

function writeRows(ss, name, headers, rows) {
  const sheet = getOrCreateSheet(ss, name, headers);
  const lastRow = sheet.getLastRow();
  if (lastRow > 1) {
    sheet.getRange(2, 1, lastRow - 1, Math.max(sheet.getLastColumn(), headers.length)).clearContent();
  }
  if (rows.length > 0) {
    sheet.getRange(2, 1, rows.length, headers.length).setValues(rows);
  }
}

function micros(v) {
  return v ? Number((Number(v) / 1e6).toFixed(2)) : 0;
}

function pct(v) {
  return v ? Number((Number(v) * 100).toFixed(2)) : 0;
}

/* ---------------- 1. Account Overview ---------------- */

function syncAccountOverview(ss) {
  const acct = AdsApp.currentAccount();
  let imp = 0, clk = 0, cost = 0, conv = 0, val = 0;

  try {
    const it = AdsApp.search(
      "SELECT metrics.impressions, metrics.clicks, metrics.cost_micros, " +
      "metrics.conversions, metrics.conversions_value " +
      "FROM customer WHERE segments.date DURING LAST_30_DAYS"
    );
    while (it.hasNext()) {
      const r = it.next();
      imp += Number(r.metrics.impressions || 0);
      clk += Number(r.metrics.clicks || 0);
      cost += micros(r.metrics.costMicros);
      conv += Number(r.metrics.conversions || 0);
      val += Number(r.metrics.conversionsValue || 0);
    }
  } catch (e) {
    Logger.log('Account metrics note: ' + e);
  }

  const ctr = imp > 0 ? Number(((clk / imp) * 100).toFixed(2)) : 0;
  const cpc = clk > 0 ? Number((cost / clk).toFixed(2)) : 0;
  const cpa = conv > 0 ? Number((cost / conv).toFixed(2)) : 0;

  const rows = [
    ['Sync Status', 'CONNECTED', 'Live sync via Google Ads Scripts'],
    ['Last Synced (UTC)', new Date().toISOString(), 'Set script schedule to Hourly in Google Ads'],
    ['Customer ID', acct.getCustomerId(), 'Google Ads 10-digit account ID'],
    ['Account Name', acct.getName() || '(Unnamed Account)', ''],
    ['Currency', acct.getCurrencyCode(), 'All cost columns use this currency'],
    ['Time Zone', acct.getTimeZone(), ''],
    ['30d Impressions', imp, 'Last 30 days'],
    ['30d Clicks', clk, 'Last 30 days'],
    ['30d CTR (%)', ctr, 'Last 30 days'],
    ['30d Avg CPC', Number(cpc.toFixed(2)), 'Last 30 days'],
    ['30d Spend', Number(cost.toFixed(2)), 'Last 30 days'],
    ['30d Conversions', Number(conv.toFixed(2)), 'Last 30 days'],
    ['30d Conversion Value', Number(val.toFixed(2)), 'Last 30 days'],
    ['30d Cost / Conversion', cpa, 'Last 30 days']
  ];
  writeRows(ss, 'Account_Overview', ['Metric', 'Value', 'Notes'], rows);
}

/* ---------------- 2. Campaigns (All types incl. Search & PMax) ---------------- */

function syncCampaigns(ss) {
  const headers = [
    'Campaign ID', 'Campaign Name', 'Status', 'Channel Type', 'Bidding Strategy',
    'Daily Budget', '30d Impressions', '30d Clicks', '30d CTR (%)', '30d Avg CPC',
    '30d Cost', '30d Conversions', '30d Conv. Value', 'Search Impr. Share'
  ];
  const byId = {};

  // First grab ALL non-removed campaigns (even those with 0 impressions in the last 30 days)
  try {
    const base = AdsApp.search(
      "SELECT campaign.id, campaign.name, campaign.status, " +
      "campaign.advertising_channel_type, campaign.bidding_strategy_type, " +
      "campaign_budget.amount_micros " +
      "FROM campaign WHERE campaign.status != 'REMOVED'"
    );
    while (base.hasNext()) {
      const r = base.next();
      const id = String(r.campaign.id);
      byId[id] = {
        id: id,
        name: r.campaign.name,
        status: r.campaign.status,
        channel: r.campaign.advertisingChannelType || '',
        bidding: r.campaign.biddingStrategyType || '',
        budget: r.campaignBudget ? micros(r.campaignBudget.amountMicros) : 0,
        imp: 0, clk: 0, ctr: 0, cpc: 0, cost: 0, conv: 0, val: 0, sis: ''
      };
    }
  } catch (e) {
    Logger.log('Campaign base query error: ' + e);
  }

  // Overlay 30-day metrics
  try {
    const met = AdsApp.search(
      "SELECT campaign.id, metrics.impressions, metrics.clicks, metrics.ctr, " +
      "metrics.average_cpc, metrics.cost_micros, metrics.conversions, " +
      "metrics.conversions_value, metrics.search_impression_share " +
      "FROM campaign WHERE campaign.status != 'REMOVED' AND segments.date DURING LAST_30_DAYS"
    );
    while (met.hasNext()) {
      const r = met.next();
      const c = byId[String(r.campaign.id)];
      if (!c) continue;
      c.imp = Number(r.metrics.impressions || 0);
      c.clk = Number(r.metrics.clicks || 0);
      c.ctr = pct(r.metrics.ctr);
      c.cpc = micros(r.metrics.averageCpc);
      c.cost = micros(r.metrics.costMicros);
      c.conv = Number((Number(r.metrics.conversions || 0)).toFixed(2));
      c.val = Number((Number(r.metrics.conversionsValue || 0)).toFixed(2));
      c.sis = r.metrics.searchImpressionShare != null ? pct(r.metrics.searchImpressionShare) + '%' : '';
    }
  } catch (e) {
    Logger.log('Campaign metrics query error: ' + e);
  }

  const rows = Object.keys(byId).map(function (k) {
    const c = byId[k];
    return [c.id, c.name, c.status, c.channel, c.bidding, c.budget, c.imp, c.clk, c.ctr, c.cpc, c.cost, c.conv, c.val, c.sis];
  });
  writeRows(ss, 'Campaigns', headers, rows);
}

/* ---------------- 3. Ad Groups ---------------- */

function syncAdGroups(ss) {
  const headers = [
    'Campaign Name', 'Ad Group ID', 'Ad Group Name', 'Status', 'Type',
    '30d Impressions', '30d Clicks', '30d CTR (%)', '30d Avg CPC', '30d Cost', '30d Conversions'
  ];
  const rows = [];
  try {
    const it = AdsApp.search(
      "SELECT campaign.name, ad_group.id, ad_group.name, ad_group.status, ad_group.type, " +
      "metrics.impressions, metrics.clicks, metrics.ctr, metrics.average_cpc, " +
      "metrics.cost_micros, metrics.conversions " +
      "FROM ad_group " +
      "WHERE campaign.status != 'REMOVED' AND ad_group.status != 'REMOVED' " +
      "AND segments.date DURING LAST_30_DAYS " +
      "ORDER BY metrics.cost_micros DESC LIMIT 500"
    );
    while (it.hasNext()) {
      const r = it.next();
      rows.push([
        r.campaign.name, String(r.adGroup.id), r.adGroup.name, r.adGroup.status, r.adGroup.type || '',
        Number(r.metrics.impressions || 0), Number(r.metrics.clicks || 0), pct(r.metrics.ctr),
        micros(r.metrics.averageCpc), micros(r.metrics.costMicros), Number((Number(r.metrics.conversions || 0)).toFixed(2))
      ]);
    }
  } catch (e) {
    Logger.log('AdGroups query error: ' + e);
  }
  writeRows(ss, 'Ad_Groups', headers, rows);
}

/* ---------------- 4. Keywords ---------------- */

function syncKeywords(ss) {
  const headers = [
    'Campaign Name', 'Ad Group Name', 'Keyword', 'Match Type', 'Status',
    'Quality Score', '30d Impressions', '30d Clicks', '30d CTR (%)', '30d Avg CPC', '30d Cost', '30d Conversions'
  ];
  const rows = [];
  try {
    const it = AdsApp.search(
      "SELECT campaign.name, ad_group.name, ad_group_criterion.keyword.text, " +
      "ad_group_criterion.keyword.match_type, ad_group_criterion.status, " +
      "ad_group_criterion.quality_info.quality_score, " +
      "metrics.impressions, metrics.clicks, metrics.ctr, metrics.average_cpc, " +
      "metrics.cost_micros, metrics.conversions " +
      "FROM keyword_view " +
      "WHERE campaign.status != 'REMOVED' AND ad_group.status != 'REMOVED' " +
      "AND ad_group_criterion.status != 'REMOVED' AND segments.date DURING LAST_30_DAYS " +
      "ORDER BY metrics.cost_micros DESC LIMIT 1000"
    );
    while (it.hasNext()) {
      const r = it.next();
      const kw = r.adGroupCriterion && r.adGroupCriterion.keyword ? r.adGroupCriterion.keyword : {};
      const qi = r.adGroupCriterion && r.adGroupCriterion.qualityInfo ? r.adGroupCriterion.qualityInfo : {};
      rows.push([
        r.campaign.name, r.adGroup.name, kw.text || '', kw.matchType || '',
        r.adGroupCriterion.status || '', qi.qualityScore || '',
        Number(r.metrics.impressions || 0), Number(r.metrics.clicks || 0), pct(r.metrics.ctr),
        micros(r.metrics.averageCpc), micros(r.metrics.costMicros), Number((Number(r.metrics.conversions || 0)).toFixed(2))
      ]);
    }
  } catch (e) {
    Logger.log('Keywords query error: ' + e);
  }
  writeRows(ss, 'Keywords', headers, rows);
}

/* ---------------- 5. Search Terms (Last 30 days) ---------------- */

function syncSearchTerms(ss) {
  const headers = [
    'Search Term', 'Status', 'Campaign Name', 'Ad Group Name',
    '30d Impressions', '30d Clicks', '30d Cost', '30d Conversions', '30d Conv. Value'
  ];
  const rows = [];
  try {
    const it = AdsApp.search(
      "SELECT search_term_view.search_term, search_term_view.status, " +
      "campaign.name, ad_group.name, metrics.impressions, metrics.clicks, " +
      "metrics.cost_micros, metrics.conversions, metrics.conversions_value " +
      "FROM search_term_view " +
      "WHERE segments.date DURING LAST_30_DAYS AND metrics.impressions > 0 " +
      "ORDER BY metrics.clicks DESC, metrics.impressions DESC LIMIT 1000"
    );
    while (it.hasNext()) {
      const r = it.next();
      rows.push([
        r.searchTermView.searchTerm || '', r.searchTermView.status || '',
        r.campaign.name, r.adGroup.name,
        Number(r.metrics.impressions || 0), Number(r.metrics.clicks || 0),
        micros(r.metrics.costMicros), Number((Number(r.metrics.conversions || 0)).toFixed(2)),
        Number((Number(r.metrics.conversionsValue || 0)).toFixed(2))
      ]);
    }
  } catch (e) {
    Logger.log('Search terms query error: ' + e);
  }
  writeRows(ss, 'Search_Terms', headers, rows);
}

/* ---------------- 6. Ads (RSA & copy) ---------------- */

function syncAds(ss) {
  const headers = [
    'Campaign Name', 'Ad Group Name', 'Ad ID', 'Ad Type', 'Status', 'Ad Strength',
    'Headlines', 'Descriptions', 'Final URL', '30d Impressions', '30d Clicks', '30d Cost', '30d Conversions'
  ];
  const rows = [];
  try {
    const it = AdsApp.search(
      "SELECT campaign.name, ad_group.name, ad_group_ad.ad.id, ad_group_ad.ad.type, " +
      "ad_group_ad.status, ad_group_ad.ad_strength, " +
      "ad_group_ad.ad.responsive_search_ad.headlines, " +
      "ad_group_ad.ad.responsive_search_ad.descriptions, " +
      "ad_group_ad.ad.final_urls, " +
      "metrics.impressions, metrics.clicks, metrics.cost_micros, metrics.conversions " +
      "FROM ad_group_ad " +
      "WHERE campaign.status != 'REMOVED' AND ad_group_ad.status != 'REMOVED' " +
      "AND segments.date DURING LAST_30_DAYS " +
      "ORDER BY metrics.impressions DESC LIMIT 500"
    );
    while (it.hasNext()) {
      const r = it.next();
      const ad = r.adGroupAd.ad || {};
      const rsa = ad.responsiveSearchAd || {};
      const headlines = (rsa.headlines || []).map(function (h) { return h.text; }).join(' | ');
      const descs = (rsa.descriptions || []).map(function (d) { return d.text; }).join(' | ');
      const url = (ad.finalUrls && ad.finalUrls.length) ? ad.finalUrls[0] : '';
      rows.push([
        r.campaign.name, r.adGroup.name, String(ad.id || ''), ad.type || '',
        r.adGroupAd.status || '', r.adGroupAd.adStrength || '',
        headlines, descs, url,
        Number(r.metrics.impressions || 0), Number(r.metrics.clicks || 0),
        micros(r.metrics.costMicros), Number((Number(r.metrics.conversions || 0)).toFixed(2))
      ]);
    }
  } catch (e) {
    Logger.log('Ads query error: ' + e);
  }
  writeRows(ss, 'Ads', headers, rows);
}

/* ---------------- 7. Conversion Actions (incl. AW-ID/Label) ---------------- */

function syncConversionActions(ss) {
  const headers = [
    'Conversion ID', 'Name', 'Category', 'Status', 'Primary for Goal',
    'Counting Type', 'Send-To Tag (AW-ID/Label)', 'Global Site Tag ID'
  ];
  const rows = [];
  try {
    const it = AdsApp.search(
      "SELECT conversion_action.id, conversion_action.name, conversion_action.category, " +
      "conversion_action.status, conversion_action.primary_for_goal, " +
      "conversion_action.counting_type, conversion_action.tag_snippets " +
      "FROM conversion_action WHERE conversion_action.status != 'REMOVED'"
    );
    while (it.hasNext()) {
      const r = it.next();
      const ca = r.conversionAction;
      let sendTo = '';
      let gtagId = '';
      const snippets = ca.tagSnippets || [];
      for (let i = 0; i < snippets.length; i++) {
        const ev = snippets[i].eventSnippet || '';
        const gs = snippets[i].globalSiteTag || '';
        const m1 = ev.match(/AW-[0-9A-Za-z_-]+\/[0-9A-Za-z_-]+/);
        if (m1 && !sendTo) sendTo = m1[0];
        const m2 = gs.match(/AW-[0-9]+/);
        if (m2 && !gtagId) gtagId = m2[0];
      }
      rows.push([
        String(ca.id || ''), ca.name || '', ca.category || '', ca.status || '',
        String(ca.primaryForGoal), ca.countingType || '', sendTo, gtagId
      ]);
    }
  } catch (e) {
    Logger.log('Conversion actions query error: ' + e);
  }
  writeRows(ss, 'Conversion_Actions', headers, rows);
}

/* ---------------- 8. Two-Way Safe Action Queue ---------------- */

function processActionQueue(ss, isPreview) {
  const sheet = ss.getSheetByName('Action_Queue');
  if (!sheet || sheet.getLastRow() < 2) return;

  const range = sheet.getRange(2, 1, sheet.getLastRow() - 1, 10);
  const data = range.getValues();
  let changed = false;

  for (let i = 0; i < data.length; i++) {
    const approved = String(data[i][1] || '').trim().toUpperCase();
    const type = String(data[i][2] || '').trim().toUpperCase();
    const campName = String(data[i][3] || '').trim();
    const agName = String(data[i][4] || '').trim();
    const targetVal = String(data[i][5] || '').trim();
    const matchType = String(data[i][6] || 'PHRASE').trim().toUpperCase();
    const status = String(data[i][7] || '').trim().toUpperCase();

    if (approved !== 'YES' || (status !== '' && status !== 'PENDING')) continue;

    if (isPreview) {
      Logger.log('[PREVIEW] Would run ' + type + ' on ' + campName + ' -> ' + targetVal);
      continue;
    }

    try {
      executeQueuedAction(type, campName, agName, targetVal, matchType);
      data[i][7] = 'DONE';
      data[i][8] = new Date().toISOString();
      changed = true;
    } catch (err) {
      data[i][7] = 'ERROR: ' + String(err).slice(0, 120);
      data[i][8] = new Date().toISOString();
      changed = true;
    }
  }

  if (changed) range.setValues(data);
}

function executeQueuedAction(type, campName, agName, targetVal, matchType) {
  const campIt = AdsApp.campaigns().withCondition('campaign.name = "' + campName.replace(/"/g, '\\"') + '"').get();
  if (!campIt.hasNext()) throw new Error('Campaign not found: ' + campName);
  const camp = campIt.next();

  if (type === 'PAUSE_CAMPAIGN') {
    camp.pause();
  } else if (type === 'ENABLE_CAMPAIGN') {
    camp.enable();
  } else if (type === 'SET_DAILY_BUDGET') {
    const amt = parseFloat(targetVal);
    if (!(amt > 0)) throw new Error('Invalid budget: ' + targetVal);
    camp.getBudget().setAmount(amt);
  } else if (type === 'ADD_CAMPAIGN_NEGATIVE') {
    const formatted = matchType === 'EXACT' ? '[' + targetVal + ']' : matchType === 'BROAD' ? targetVal : '"' + targetVal + '"';
    camp.createNegativeKeyword(formatted);
  } else if (type === 'PAUSE_KEYWORD') {
    const kwIt = camp.keywords()
      .withCondition('ad_group.name = "' + agName.replace(/"/g, '\\"') + '"')
      .withCondition('ad_group_criterion.keyword.text = "' + targetVal.replace(/"/g, '\\"') + '"')
      .get();
    if (!kwIt.hasNext()) throw new Error('Keyword not found: ' + targetVal);
    kwIt.next().pause();
  } else {
    throw new Error('Unsupported Action_Type: ' + type);
  }
}
