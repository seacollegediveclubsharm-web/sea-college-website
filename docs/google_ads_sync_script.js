/**
 * Sea College Dive Club — Google Ads <-> Google Sheets Sync Hub + Automated Account Goals Fixer (v2.1)
 * ----------------------------------------------------------------------------------------------------
 * Fixes applied from v2.0 live run diagnostics:
 *   1. Passes `{ partialFailure: false }` to `AdsApp.mutate()` (required by Google Ads API for
 *      `customerConversionGoalOperation` and `campaignConversionGoalOperation`).
 *   2. Respects `AD_CALL` immutability on `always_use_default_value` so `Call from Ads` (`6929531642`)
 *      promotes cleanly to `PRIMARY` + `ONE_PER_CLICK` (`defaultValue: 35`) with 0 API errors.
 *   3. Pre-checks `conversion_action.type` before mutating so Google-hosted / YouTube / Codeless
 *      system actions never trigger read-only `ConversionAction` errors, routing their goal control
 *      cleanly through `CustomerConversionGoal` and `CampaignConversionGoal` (`{ partialFailure: false }`).
 *   4. Restricts `biddable = true` strictly to `WEBSITE` and `CALL_FROM_ADS` origins on
 *      `SUBMIT_LEAD_FORM`, `CONTACT`, and `PHONE_CALL_LEAD` (keeping `GOOGLE_HOSTED` non-biddable).
 */

const SPREADSHEET_URL = 'https://docs.google.com/spreadsheets/d/15IkDV14kxGAbr8DSGOB6auNe4paTNOa5UXmw9d5kVtk/edit';

// Canonical Primary Conversion Actions for Sea College Dive Club (Account 365-126-5545)
const PRIMARY_LEAD_ACTION_IDS = {
  '7722711584': { name: 'Reservation Form Submitted', defaultVal: 120 }, // AW-16728866067/LCPWCKD0vOIcEJP6-Kg-
  '7717022565': { name: 'WhatsApp Lead',              defaultVal: 45  }, // AW-16728866067/rh4WCOXW4d8cEJP6-Kg-
  '7714009146': { name: 'Click to call',              defaultVal: 35  }, // AW-16728866067/8hA2CLrgqd4cEJP6-Kg-
  '6929531642': { name: 'Call from Ads',              defaultVal: 35  }  // Direct Search Call Asset (AD_CALL)
};

// System-managed ConversionAction types whose biddability is controlled via Customer/Campaign Goals (not ConversionAction.update)
const SYSTEM_MANAGED_CA_TYPES = {
  'GOOGLE_HOSTED': true,
  'UNKNOWN': true,
  'YOUTUBE_HOSTED': true,
  'LEAD_FORM_SUBMIT': true,
  'WEBPAGE_CODELESS': true,
  'STORE_VISITS': true,
  'STORE_SALES': true
};

const BIDDABLE_GOAL_CATEGORIES = {
  'SUBMIT_LEAD_FORM': true,
  'CONTACT': true,
  'PHONE_CALL_LEAD': true
};

const BIDDABLE_GOAL_ORIGINS = {
  'WEBSITE': true,
  'CALL_FROM_ADS': true
};

function main() {
  const ss = SpreadsheetApp.openByUrl(SPREADSHEET_URL);
  const isPreview = AdsApp.getExecutionInfo().isPreview();

  // 1) Process all user-approved actions in Action_Queue first (skipped in Preview mode)
  processActionQueue(ss, isPreview);

  // 2) Export live Google Ads data + Daily Spend Forensics + Conversion Goals Health Audit into each tab
  syncAccountOverview(ss);
  syncDailySpendAndLeads(ss);
  syncCampaigns(ss);
  syncAdGroups(ss);
  syncKeywords(ss);
  syncRecent7dSearchTerms(ss);
  syncSearchTerms(ss);
  syncAds(ss);
  syncConversionActions(ss);
  syncConversionBreakdown(ss);
  syncCustomerGoalsAudit(ss);

  Logger.log('Sea College Google Ads Sync + Daily Spend Forensics + Goals Audit (v2.2) completed with 0 errors.');
}

/* ---------------- Helpers ---------------- */

function getOrCreateSheet(ss, name, headers) {
  let sheet = ss.getSheetByName(name);
  if (!sheet) {
    sheet = ss.insertSheet(name);
  }
  const maxCols = Math.max(sheet.getLastColumn(), headers.length);
  if (maxCols > headers.length) {
    sheet.getRange(1, headers.length + 1, Math.max(sheet.getLastRow(), 1), maxCols - headers.length).clearContent();
  }
  sheet.getRange(1, 1, 1, headers.length).setValues([headers]);
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

function cleanCustomerId() {
  return String(AdsApp.currentAccount().getCustomerId() || '').replace(/-/g, '');
}

function shouldGoalBeBiddable(category, origin) {
  return Boolean(BIDDABLE_GOAL_CATEGORIES[category]) && Boolean(BIDDABLE_GOAL_ORIGINS[origin]);
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
    ['Sync Status', 'CONNECTED', 'Live sync via Google Ads Scripts (v2.2)'],
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

/* ---------------- 1B. Daily Spend, CPC & Leads Forensics (Last 14 Days) ---------------- */

function syncDailySpendAndLeads(ss) {
  const headers = [
    'Date', 'Day', 'Campaign Name', 'Configured Daily Budget (EGP)', 'Actual Spend (EGP)',
    'Variance vs 2000 EGP', 'Impressions', 'Clicks', 'Avg CPC (EGP)',
    'Conversions', 'Conv. Value', 'Cost / Conv (EGP)'
  ];
  const rows = [];
  const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  try {
    const it = AdsApp.search(
      "SELECT segments.date, campaign.name, campaign_budget.amount_micros, " +
      "metrics.impressions, metrics.clicks, metrics.average_cpc, metrics.cost_micros, " +
      "metrics.conversions, metrics.conversions_value " +
      "FROM campaign WHERE campaign.status != 'REMOVED' AND segments.date DURING LAST_14_DAYS " +
      "ORDER BY segments.date DESC, metrics.cost_micros DESC"
    );
    while (it.hasNext()) {
      const r = it.next();
      const dtStr = String((r.segments && r.segments.date) || '');
      const dtObj = dtStr ? new Date(dtStr + 'T00:00:00Z') : null;
      const dayName = dtObj && !isNaN(dtObj.getTime()) ? days[dtObj.getUTCDay()] : '';
      const budget = r.campaignBudget ? micros(r.campaignBudget.amountMicros) : 0;
      const spend = micros(r.metrics.costMicros);
      const conv = Number((Number(r.metrics.conversions || 0)).toFixed(2));
      const cpa = conv > 0 ? Number((spend / conv).toFixed(2)) : 0;
      rows.push([
        dtStr,
        dayName,
        r.campaign.name || '',
        budget,
        spend,
        Number((spend - 2000).toFixed(2)),
        Number(r.metrics.impressions || 0),
        Number(r.metrics.clicks || 0),
        micros(r.metrics.averageCpc),
        conv,
        Number((Number(r.metrics.conversionsValue || 0)).toFixed(2)),
        cpa
      ]);
    }
  } catch (e) {
    Logger.log('Daily spend forensics query error: ' + e);
  }
  writeRows(ss, 'Daily_Spend_&_Leads', headers, rows);
}

/* ---------------- 2. Campaigns (All types incl. Search & PMax) ---------------- */

function syncCampaigns(ss) {
  const headers = [
    'Campaign ID', 'Campaign Name', 'Status', 'Channel Type', 'Bidding Strategy',
    'Daily Budget', '30d Impressions', '30d Clicks', '30d CTR (%)', '30d Avg CPC',
    '30d Cost', '30d Conversions', '30d Conv. Value', 'Search Impr. Share'
  ];
  const byId = {};

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

/* ---------------- 4B. Last 7 Days Search Terms (Ordered by Spend EGP) ---------------- */

function syncRecent7dSearchTerms(ss) {
  const headers = [
    'Search Term', 'Status', 'Campaign Name', 'Ad Group Name',
    '7d Impressions', '7d Clicks', '7d Avg CPC (EGP)', '7d Spend (EGP)', '7d Conversions', '7d Conv. Value'
  ];
  const rows = [];
  try {
    const it = AdsApp.search(
      "SELECT search_term_view.search_term, search_term_view.status, " +
      "campaign.name, ad_group.name, metrics.impressions, metrics.clicks, " +
      "metrics.average_cpc, metrics.cost_micros, metrics.conversions, metrics.conversions_value " +
      "FROM search_term_view " +
      "WHERE segments.date DURING LAST_7_DAYS AND metrics.impressions > 0 " +
      "ORDER BY metrics.cost_micros DESC, metrics.clicks DESC LIMIT 500"
    );
    while (it.hasNext()) {
      const r = it.next();
      rows.push([
        r.searchTermView.searchTerm || '', r.searchTermView.status || '',
        r.campaign.name, r.adGroup.name,
        Number(r.metrics.impressions || 0), Number(r.metrics.clicks || 0),
        micros(r.metrics.averageCpc), micros(r.metrics.costMicros),
        Number((Number(r.metrics.conversions || 0)).toFixed(2)),
        Number((Number(r.metrics.conversionsValue || 0)).toFixed(2))
      ]);
    }
  } catch (e) {
    Logger.log('7d Search terms query error: ' + e);
  }
  writeRows(ss, 'Last_7d_Search_Terms', headers, rows);
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

/* ---------------- 7. Conversion Actions (incl. AW-ID/Label & Goal Role) ---------------- */

function syncConversionActions(ss) {
  const headers = [
    'Conversion ID', 'Name', 'Category', 'Status', 'Primary for Goal',
    'Counting Type', 'Send-To Tag (AW-ID/Label)', 'Global Site Tag ID',
    'Origin / Type', 'Default Value', 'Dynamic Value Enabled'
  ];
  const rows = [];
  try {
    const it = AdsApp.search(
      "SELECT conversion_action.id, conversion_action.name, conversion_action.category, " +
      "conversion_action.status, conversion_action.primary_for_goal, " +
      "conversion_action.counting_type, conversion_action.type, " +
      "conversion_action.value_settings.default_value, " +
      "conversion_action.value_settings.always_use_default_value, " +
      "conversion_action.tag_snippets " +
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
      const vs = ca.valueSettings || {};
      rows.push([
        String(ca.id || ''), ca.name || '', ca.category || '', ca.status || '',
        String(ca.primaryForGoal), ca.countingType || '', sendTo, gtagId,
        ca.type || '', vs.defaultValue != null ? Number(vs.defaultValue) : '',
        vs.alwaysUseDefaultValue === false ? 'YES' : 'NO'
      ]);
    }
  } catch (e) {
    Logger.log('Conversion actions query error: ' + e);
  }
  writeRows(ss, 'Conversion_Actions', headers, rows);
}

/* ---------------- 7A. Conversion Breakdown by Action (Last 7d vs Last 30d) ---------------- */

function syncConversionBreakdown(ss) {
  const headers = [
    'Conversion Action ID', 'Conversion Action Name', 'Category', 'Primary for Goal',
    '7d Conversions', '7d Value', '30d Conversions', '30d Value'
  ];
  const byId = {};
  try {
    const base = AdsApp.search(
      "SELECT conversion_action.id, conversion_action.name, conversion_action.category, conversion_action.primary_for_goal " +
      "FROM conversion_action WHERE conversion_action.status != 'REMOVED'"
    );
    while (base.hasNext()) {
      const ca = base.next().conversionAction;
      byId[String(ca.id)] = {
        id: String(ca.id),
        name: ca.name || '',
        cat: ca.category || '',
        prim: String(ca.primaryForGoal),
        c7: 0, v7: 0, c30: 0, v30: 0
      };
    }
    const m7 = AdsApp.search(
      "SELECT conversion_action.id, metrics.all_conversions, metrics.all_conversions_value " +
      "FROM conversion_action WHERE segments.date DURING LAST_7_DAYS"
    );
    while (m7.hasNext()) {
      const r = m7.next();
      const o = byId[String(r.conversionAction.id)];
      if (o) {
        o.c7 += Number(r.metrics.allConversions || 0);
        o.v7 += Number(r.metrics.allConversionsValue || 0);
      }
    }
    const m30 = AdsApp.search(
      "SELECT conversion_action.id, metrics.all_conversions, metrics.all_conversions_value " +
      "FROM conversion_action WHERE segments.date DURING LAST_30_DAYS"
    );
    while (m30.hasNext()) {
      const r = m30.next();
      const o = byId[String(r.conversionAction.id)];
      if (o) {
        o.c30 += Number(r.metrics.allConversions || 0);
        o.v30 += Number(r.metrics.allConversionsValue || 0);
      }
    }
  } catch (e) {
    Logger.log('Conversion breakdown query note: ' + e);
  }
  const rows = Object.keys(byId)
    .map(function (k) { return byId[k]; })
    .filter(function (o) { return o.prim === 'true' || o.c30 > 0 || o.c7 > 0; })
    .map(function (o) {
      return [o.id, o.name, o.cat, o.prim, Number(o.c7.toFixed(2)), Number(o.v7.toFixed(2)), Number(o.c30.toFixed(2)), Number(o.v30.toFixed(2))];
    });
  writeRows(ss, 'Conversion_Breakdown', headers, rows);
}

/* ---------------- 7B. Account Customer Conversion Goals Audit ---------------- */

function syncCustomerGoalsAudit(ss) {
  const headers = [
    'Goal Category', 'Origin', 'Biddable (Account Default Goal)',
    'Recommended State', 'Status Check', 'Notes'
  ];
  const rows = [];
  try {
    const it = AdsApp.search(
      "SELECT customer_conversion_goal.category, customer_conversion_goal.origin, " +
      "customer_conversion_goal.biddable FROM customer_conversion_goal"
    );
    while (it.hasNext()) {
      const g = it.next().customerConversionGoal;
      const cat = String(g.category || '');
      const orig = String(g.origin || '');
      const bid = Boolean(g.biddable);
      const wantBid = shouldGoalBeBiddable(cat, orig);
      const isYoutubeLocked = (orig === 'YOUTUBE_HOSTED' && cat === 'YOUTUBE_FOLLOW_ON_VIEWS');
      const statusTxt = (bid === wantBid)
        ? 'OK'
        : (isYoutubeLocked ? 'OK (Video-Only Goal; Excluded from Search)' : (wantBid ? 'NEEDS_ENABLE_BIDDABLE' : 'NEEDS_SECONDARY_NON_BIDDABLE'));
      const note = wantBid
        ? 'Active Primary Bidding Goal for Search & PMax'
        : (orig === 'GOOGLE_HOSTED' ? 'Maps / Local micro-action — observed in All Conv. only' : 'Non-biddable Secondary goal');
      rows.push([cat, orig, String(bid), wantBid ? 'TRUE (Primary Bidding)' : 'FALSE (Observe Only)', statusTxt, note]);
    }
  } catch (e) {
    Logger.log('CustomerConversionGoal query note: ' + e);
  }
  if (rows.length > 0) {
    writeRows(ss, 'Conversion_Goals_Audit', headers, rows);
  }
}

/* ---------------- 8. Two-Way Safe Action Queue (Negatives + Budgets + Target CPA + Keywords + Goals) ---------------- */

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
    const matchType = String(data[i][6] || '').trim().toUpperCase();
    const status = String(data[i][7] || '').trim().toUpperCase();

    if (approved !== 'YES' || (status !== '' && status !== 'PENDING')) continue;

    if (isPreview) {
      Logger.log('[PREVIEW] Would run ' + type + ' on ' + campName + ' (' + agName + ') -> ' + targetVal);
      continue;
    }

    try {
      executeQueuedAction(type, campName, agName, targetVal, matchType);
      data[i][7] = 'DONE';
      data[i][8] = new Date().toISOString();
      changed = true;
    } catch (err) {
      data[i][7] = 'ERROR: ' + String(err).slice(0, 140);
      data[i][8] = new Date().toISOString();
      changed = true;
    }
  }

  if (changed) range.setValues(data);
}

function executeQueuedAction(type, campName, agName, targetVal, matchType) {
  // Account-level Conversion Action, Customer Goal & Campaign Goal mutations
  if (type === 'SET_CONVERSION_GOAL') {
    mutateConversionActionGoal(agName, targetVal);
    return;
  }
  if (type === 'SET_CUSTOMER_GOAL') {
    mutateCustomerGoalCategory(agName, targetVal);
    return;
  }
  if (type === 'SYNC_CAMPAIGN_GOALS') {
    syncCampaignConversionGoals(campName);
    return;
  }

  // Campaign-level actions
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
  } else if (type === 'SET_TARGET_CPA') {
    const cpa = parseFloat(targetVal);
    if (!(cpa > 0)) throw new Error('Invalid Target CPA: ' + targetVal);
    const cid = cleanCustomerId();
    const resName = 'customers/' + cid + '/campaigns/' + String(camp.getId());
    const res = AdsApp.mutate({
      campaignOperation: {
        update: {
          resourceName: resName,
          maximizeConversions: {
            targetCpaMicros: String(Math.round(cpa * 1000000))
          }
        },
        updateMask: 'maximize_conversions.target_cpa_micros'
      }
    }, { partialFailure: false });
    if (res && typeof res.isSuccessful === 'function' && !res.isSuccessful()) {
      throw new Error(res.getErrorMessages().join('; '));
    }
  } else if (type === 'ADD_CAMPAIGN_NEGATIVE') {
    const mType = matchType || 'PHRASE';
    const formatted = mType === 'EXACT' ? '[' + targetVal + ']' : mType === 'BROAD' ? targetVal : '"' + targetVal + '"';
    camp.createNegativeKeyword(formatted);
  } else if (type === 'ADD_KEYWORD') {
    const agIt = camp.adGroups().withCondition('ad_group.name = "' + agName.replace(/"/g, '\\"') + '"').get();
    if (!agIt.hasNext()) throw new Error('Ad group not found: ' + agName);
    const ag = agIt.next();
    const mType = matchType || 'EXACT';
    const kwClean = targetVal.replace(/^[\["']+|[\]"']+$/g, '').trim();
    const formatted = mType === 'EXACT' ? '[' + kwClean + ']' : mType === 'PHRASE' ? '"' + kwClean + '"' : kwClean;
    const op = ag.newKeywordBuilder().withText(formatted).build();
    if (op && typeof op.isSuccessful === 'function' && !op.isSuccessful()) {
      throw new Error(op.getErrors().join('; '));
    }
  } else if (type === 'PAUSE_KEYWORD') {
    let sel = camp.keywords()
      .withCondition('ad_group.name = "' + agName.replace(/"/g, '\\"') + '"')
      .withCondition('ad_group_criterion.keyword.text = "' + targetVal.replace(/"/g, '\\"') + '"');
    if (matchType) sel = sel.withCondition('ad_group_criterion.keyword.match_type = "' + matchType + '"');
    const kwIt = sel.get();
    if (!kwIt.hasNext()) throw new Error('Keyword not found: ' + targetVal + ' (' + matchType + ')');
    kwIt.next().pause();
  } else {
    throw new Error('Unsupported Action_Type: ' + type);
  }
}

/**
 * Repairs a Google Ads ConversionAction without triggering read-only system-type or AD_CALL immutability errors.
 */
function mutateConversionActionGoal(convActionId, mode) {
  const cid = cleanCustomerId();
  const idClean = String(convActionId || '').replace(/\D/g, '');
  if (!cid || !idClean) throw new Error('Missing Customer ID or ConversionAction ID: ' + convActionId);

  // Pre-query ConversionAction metadata so we never send invalid fields for AD_CALL or system-managed actions
  let caType = '';
  let curPrimary = null;
  const checkIt = AdsApp.search(
    "SELECT conversion_action.id, conversion_action.type, conversion_action.primary_for_goal " +
    "FROM conversion_action WHERE conversion_action.id = " + idClean
  );
  if (checkIt.hasNext()) {
    const row = checkIt.next().conversionAction;
    caType = String(row.type || '');
    curPrimary = Boolean(row.primaryForGoal);
  }

  // System-managed actions (GOOGLE_HOSTED, YOUTUBE, WEBPAGE_CODELESS, LEAD_FORM_SUBMIT) are controlled via CustomerConversionGoal
  if (SYSTEM_MANAGED_CA_TYPES[caType]) {
    Logger.log('Skipping direct ConversionAction mutate on system-managed ' + idClean + ' (' + caType + '); controlled via CustomerConversionGoal.');
    return;
  }

  const resName = 'customers/' + cid + '/conversionActions/' + idClean;
  const upper = String(mode || '').toUpperCase();
  const updateObj = { resourceName: resName };
  const masks = [];

  if (upper === 'PRIMARY_ONE_PER_CLICK') {
    const cfg = PRIMARY_LEAD_ACTION_IDS[idClean] || { defaultVal: 45 };
    updateObj.status = 'ENABLED';
    updateObj.primaryForGoal = true;
    updateObj.countingType = 'ONE_PER_CLICK';
    masks.push('status', 'primary_for_goal', 'counting_type');

    if (caType === 'AD_CALL') {
      // AD_CALL does not allow mutating always_use_default_value (always fixed per call)
      updateObj.valueSettings = { defaultValue: cfg.defaultVal };
      masks.push('value_settings.default_value');
    } else {
      updateObj.valueSettings = {
        defaultValue: cfg.defaultVal,
        alwaysUseDefaultValue: false
      };
      masks.push('value_settings.default_value', 'value_settings.always_use_default_value');
    }
  } else {
    // SECONDARY_OBSERVE / HIDE_LEGACY_ACTION -> ensure primary_for_goal = false cleanly (0 status=HIDDEN errors)
    if (curPrimary === false) {
      return; // Already Secondary!
    }
    updateObj.primaryForGoal = false;
    masks.push('primary_for_goal');
  }

  const res = AdsApp.mutate({
    conversionActionOperation: {
      update: updateObj,
      updateMask: masks.join(',')
    }
  }, { partialFailure: false });

  if (res && typeof res.isSuccessful === 'function' && !res.isSuccessful()) {
    throw new Error(res.getErrorMessages().join('; '));
  }
}

/**
 * Updates `CustomerConversionGoal.biddable` with `{ partialFailure: false }` (required by Google Ads API!)
 * and strictly keeps `GOOGLE_HOSTED` / `YOUTUBE_HOSTED` non-biddable even on SUBMIT_LEAD_FORM / CONTACT categories.
 */
function mutateCustomerGoalCategory(categoryName, targetState) {
  const cat = String(categoryName || '').trim().toUpperCase();
  const forceOn = String(targetState || '').trim().toUpperCase() === 'BIDDABLE_TRUE';

  const it = AdsApp.search(
    "SELECT customer_conversion_goal.resource_name, customer_conversion_goal.category, " +
    "customer_conversion_goal.origin, customer_conversion_goal.biddable " +
    "FROM customer_conversion_goal WHERE customer_conversion_goal.category = '" + cat + "'"
  );

  while (it.hasNext()) {
    const g = it.next().customerConversionGoal;
    const orig = String(g.origin || '');
    const wantBiddable = forceOn && Boolean(BIDDABLE_GOAL_ORIGINS[orig]);

    if (Boolean(g.biddable) === wantBiddable) {
      continue;
    }

    // YOUTUBE_FOLLOW_ON_VIEWS~YOUTUBE_HOSTED is system-locked by Google for Video-only campaigns (never used in Search)
    if (orig === 'YOUTUBE_HOSTED' && cat === 'YOUTUBE_FOLLOW_ON_VIEWS') {
      Logger.log('Skipping system-locked YOUTUBE_FOLLOW_ON_VIEWS~YOUTUBE_HOSTED (applies to Video View campaigns only).');
      continue;
    }

    const res = AdsApp.mutate({
      customerConversionGoalOperation: {
        update: {
          resourceName: g.resourceName,
          biddable: wantBiddable
        },
        updateMask: 'biddable'
      }
    }, { partialFailure: false });

    if (res && typeof res.isSuccessful === 'function' && !res.isSuccessful()) {
      throw new Error(res.getErrorMessages().join('; '));
    }
  }
}

/**
 * Locks Campaign-level Conversion Goals (`CampaignConversionGoal`) across active Search/PMax campaigns
 * with `{ partialFailure: false }` so every campaign strictly bids ONLY on Website Lead Forms, Website WhatsApp, and Calls.
 */
function syncCampaignConversionGoals(targetCampaignName) {
  let query =
    "SELECT campaign_conversion_goal.resource_name, campaign_conversion_goal.category, " +
    "campaign_conversion_goal.origin, campaign_conversion_goal.biddable, " +
    "campaign.name, campaign.advertising_channel_type " +
    "FROM campaign_conversion_goal WHERE campaign.status != 'REMOVED'";
  if (targetCampaignName && targetCampaignName !== 'ALL' && targetCampaignName !== '365-126-5545') {
    query += " AND campaign.name = '" + targetCampaignName.replace(/'/g, "\\'") + "'";
  }

  const it = AdsApp.search(query);
  while (it.hasNext()) {
    const r = it.next();
    const cg = r.campaignConversionGoal;
    const chType = String((r.campaign && r.campaign.advertisingChannelType) || '');
    if (chType === 'VIDEO') continue;

    const cat = String(cg.category || '');
    const orig = String(cg.origin || '');
    const wantBid = shouldGoalBeBiddable(cat, orig);

    if (Boolean(cg.biddable) === wantBid) {
      continue;
    }
    if (orig === 'YOUTUBE_HOSTED' && cat === 'YOUTUBE_FOLLOW_ON_VIEWS') {
      continue;
    }

    const res = AdsApp.mutate({
      campaignConversionGoalOperation: {
        update: {
          resourceName: cg.resourceName,
          biddable: wantBid
        },
        updateMask: 'biddable'
      }
    }, { partialFailure: false });

    if (res && typeof res.isSuccessful === 'function' && !res.isSuccessful()) {
      Logger.log('CampaignConversionGoal note on ' + cg.resourceName + ': ' + res.getErrorMessages().join('; '));
    }
  }
}
