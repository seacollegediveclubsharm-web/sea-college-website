# Sea College Dive Club — Digital Marketing Final Owner Launch Checklist

> **Everything technical, automated, and content-related is 100% implemented, verified, and staged safely in `PAUSED` mode (`0` unapproved spend).**
> Below are the **only external platform UI actions** that require your owner click in Meta, Google Ads, Microsoft/TikTok, or Google Search Console when you are ready to turn spend live.

---

## 1. Meta Business Suite & Ads Manager (`act_1810425550247894`)
- **Already Staged (`PAUSED`, `0 EGP` Spend):**
  - Domain `seacollegediveclub.com` verified (`facebook-domain-verification` meta tag live).
  - Catalog `555610010235756` (`SEA COLLEGE TRIPS`) synced daily with `59` standalone EUR items + `413` 7-language overrides + `7` Product Sets + `5` Website Custom Audiences (including `180d` Converted Leads exclusion `120255723869490547`).
  - `9` branded social ad images (`4:5`, `1:1`) + `2` mastered `-14 LUFS` Reels uploaded (`adimages` & `advideos`).
  - All **4 ODAX `OUTCOME_SALES` Campaigns & Ad Sets (`M1` `260 EGP/d`, `M2` `312 EGP/d`, `M3` `260 EGP/d`, `M4` `208 EGP/d` = `1,040 EGP/d` total)** staged in `PAUSED` mode with EU DSA compliance (`Sea College Dive Club`).
- **Your 3 Owner Clicks Before Unpausing:**
  1. **Add Payment Method:** In Meta Business Suite → **Billing & Payments** for Ad Account `act_1810425550247894`, attach your EGP/USD card and set an optional monthly account spend cap.
  2. **Switch App Mode to Live (for API AdCreatives & Lara Inbox):** In [Meta for Developers](https://developers.facebook.com/apps/1547970967215250/settings/basic/), toggle `Sea College Marketing` (`1547970967215250`) from **Development** to **Live**, then run `php docs/meta_ads_campaigns.php stage` (or attach the already-uploaded media in Ads Manager UI). Enable **Messenger** (`pages_messaging`) & **Instagram** (`instagram_manage_messages`) use cases if you want Lara (`docs/lara_meta_inbox.php`) to reply directly via API.
  3. **WhatsApp Sales Desk Routing:** Always route Click-to-WhatsApp / website CTAs to your primary verified WhatsApp desk **`+201114567886`** (`https://wa.me/201114567886`) — never route ads to unverified backup numbers.

---

## 2. Google Ads (`365-126-5545` — Zero-Reset Cutover & 8-Language Expansion)
- **Already Completed:**
  - First-Party Google Tag Gateway (`/s-tm`, `/s-aw`, `/s-ga`) live for `GTM-PBVQ56XC`, `AW-16728866067`, and `G-99528RZN9Z` with dynamic `EUR` values on reservation forms (`LCPWCKD0vOIcEJP6-Kg-`) and WhatsApp clicks (`rh4WCOXW4d8cEJP6-Kg-`).
  - All `19` negative-keyword protection rules (`AQ-01`..`AQ-19`) in Google Ads Live Sync Hub (`15IkDV14kxGAbr8DSGOB6auNe4paTNOa5UXmw9d5kVtk`) set to `Approved = YES` while keeping active campaign `sea college start` (`24058786556`) and RSA `817963783396` untouched.
- **Your Owner Steps:**
  1. **Verify Sync Script Schedule:** In Google Ads (`365-126-5545`) → **Tools → Bulk Actions → Scripts**, confirm `docs/google_ads_sync_script.js` is scheduled **Hourly** so `AQ-01..AQ-19` negatives apply automatically.
  2. **Import 10-Campaign 8-Language Search Pack (`Paused`):** Open **Google Ads Editor** → **Account → Import → From file** → select `docs/google_ads_editor_8lang_import.csv` (`3,750 EGP/day` across `EN, RU, IT, DE, PL, FR, AR, ZH`, all `Paused`). Enable `EN` + `DE` + `RU` + `IT` first, then `AR` + `FR` + `PL` + `ZH`.
  3. **Day-7 Parallel Deep-URL RSAs:** After 7 days of clean conversion tracking on the new website, import `docs/google_ads_day7_parallel_rsas_import.csv` (fixes legacy `"VIP Private Boat"` & `"PADI 5 Star"` headlines and routes to deep hubs without resetting RSA `817963783396`).
  4. **Attach Live Dynamic Feeds:** In Google Ads → **Tools → Business Data**, link:
     - **Page Feed (DSA):** `https://seacollegediveclub.com/feeds/google-dsa-page-feed.csv`
     - **Ad Customizer Feed (Live EUR Prices):** `https://seacollegediveclub.com/feeds/google-ad-customizer.csv`

---

## 3. Microsoft (Bing) Ads & TikTok Pixel Configuration
- **Already Completed:**
  - Website strict CSP (`includes/bootstrap.php`), `config/site_config.php` (`MICROSOFT_UET_ID`, `TIKTOK_PIXEL_ID`), and `assets/js/consent.js` are pre-wired with regional consent gating and `msclkid` + `ttclid` capture.
  - 4-Language Microsoft Ads Editor pack ready at `docs/microsoft_ads_editor_4lang_import.csv` (`EN, DE, FR, IT`, `Paused`).
- **Your Owner Steps:**
  1. Create your Microsoft Advertising UET Tag and paste the tag ID into `MICROSOFT_UET_ID` inside git-ignored `config/secrets.php` on Hostinger, then import `docs/microsoft_ads_editor_4lang_import.csv` in Microsoft Ads Editor.
  2. Optional: Paste your TikTok Pixel ID into `TIKTOK_PIXEL_ID` inside `config/secrets.php` when launching TikTok Spark/Video ads from the Google Drive `Creatives/TikTok` folder.

---

## 4. Google Search Console, Bing Webmaster & Closed-Loop CRM Hygiene
1. **Submit Clean `800`-URL Sitemap:** In Google Search Console & Bing Webmaster Tools, submit `https://seacollegediveclub.com/sitemap.xml` (`800` extensionless 8-language URLs; IndexNow already pinged `800+` URLs + `/llms.txt` & `/llms-full.txt` live).
2. **Update CRM Apps Script Web App (`60s`):** In Website Leads & OCT CRM (`1ItvQGWnrHwnSbyGDoGNtV2CkVwNJXIyU_aaIFxIOGbc`) → **Extensions → Apps Script**, paste the updated `docs/apps_script_crm_webhook.js` so new `ttclid`, `msclkid`, `_fbc`, and `_fbp` fields populate columns `V..Y` automatically.
3. **Daily Offline ROAS Ritual (Lara + Sales Desk):** Whenever a WhatsApp or form guest pays at the dive center, set `Leads!C` (`Status`) = `Paid` and enter `Leads!D` (`Final_Paid_EUR`). Run:
   - `php docs/meta_offline_conversions_sync.php --dry-run` (then `--execute` to push `Purchase` events to Meta Dataset `2980406819004765`)
   - Link `Google_Ads_OCT_Export` in Google Ads → **Goals → Conversions → Uploads → Schedules** (Daily at `23:00`).
4. **Strict Review Compliance Rule:** Print the 300-DPI QR cards from Google Drive `Creatives/Website` (`Ivy Cyrene Pool`, `Hotel Concierge`, and `Official Google + Tripadvisor #26104236 Review Card`). Never offer gifts, discounts, or free Nitrox in exchange for Google or Tripadvisor reviews.
