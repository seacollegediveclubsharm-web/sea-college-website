# Connecting Google Ads + GA4 via Google Tag Manager (Sea College Dive Club)

The website is already wired for **Google Tag Manager (GTM)**, **Consent Mode v2**, **Enhanced Conversions (SHA-256 hashed email & E.164 phone)**, and **`gclid` / `gbraid` / `wbraid` click-ID attribution**.

Use the pre-built container file [`gtm_container_sea_college.json`](gtm_container_sea_college.json) so you do not have to create tags, triggers, or variables by hand.

---

## Step 1 — Create (or open) your Google Tag Manager Web Container
1. Go to https://tagmanager.google.com and sign in with the Google account that manages **Sea College Dive Club**.
2. Click **Create Account** (or open your existing account):
   - **Account Name:** `Sea College Dive Club`
   - **Country:** `Egypt`
   - **Container Name:** `seacollegediveclub.com`
   - **Target Platform:** **Web**
3. Copy your Container ID from the top bar — it looks like **`GTM-XXXXXXX`**.

---

## Step 2 — Create Conversion Actions in Google Ads
1. Go to https://ads.google.com → **Goals** → **Conversions** → **Summary**.
2. Click **+ New conversion action** → **Website** → enter your website URL → **Scan**.
3. Scroll down and click **+ Add a conversion action manually**:
   - **Goal / Category:** `Submit lead form`
   - **Conversion name:** `Website Booking / Enquiry`
   - **Value:** `Use different values for each conversion` (default `50` EUR) — the website sends the exact EUR basket value automatically.
   - **Count:** `One` (recommended for leads)
   - **Enhanced conversions:** Turn **ON** (select **Google Tag Manager** or **Google tag**; in **Goals → Conversions → Settings → Enhanced conversions for web**, accept customer data terms).
   - Click **Done** → **Save and continue**.
4. Click the **Use Google Tag Manager** tab on the setup screen and copy:
   - **Conversion ID** (digits only, e.g. `1234567890` — **without** `AW-`)
   - **Conversion Label** (e.g. `AbCdEfGhIjKlMnOpQr`)
5. *(Optional)* Repeat once if you also want **WhatsApp Click** as a secondary conversion action (`Contact` category, Value = `1` EUR, Count = `One`), and copy its **Conversion Label**.

---

## Step 3 — Import the Pre-Built Container into GTM
1. Download [`docs/gtm_container_sea_college.json`](gtm_container_sea_college.json) from your GitHub repository (`sea-college-website/docs/gtm_container_sea_college.json`).
2. In Google Tag Manager, go to **Admin** → **Import Container**:
   - **Choose container file:** select `gtm_container_sea_college.json`
   - **Choose workspace:** **Existing** → `Default Workspace`
   - **Choose an import option:** **Merge** → *Overwrite conflicting tags, triggers and variables*
   - Click **Add to workspace**.
3. In the left menu, open **Variables** → scroll to **User-Defined Variables** and update the Constants:
   - **`Const - GA4 Measurement ID`** → your GA4 Web Stream ID (`G-XXXXXXXXXX` from Google Analytics → Admin → Data Streams).
   - **`Const - Google Ads Conversion ID`** → digits only from Step 2 (e.g. `1234567890`, **no `AW-` prefix**).
   - **`Const - Google Ads Lead Label`** → the Lead Conversion Label from Step 2.
   - *(Optional)* **`Const - Google Ads WhatsApp Label`** → if you created a WhatsApp conversion action, paste its label here and unpause the tag **`Google Ads - Conversion - WhatsApp Click (Optional)`** under **Tags**.

---

## Step 4 — Connect `GTM-XXXXXXX` to the Website
Pick either option:
- **Fastest:** Send me your **`GTM-XXXXXXX`** ID here in chat — I will add it to [`config/site_config.php`](../config/site_config.php) and push it to Hostinger staging automatically.
- **Or on Hostinger yourself:** In hPanel → **Files → File Manager** → `public_html/config/`, copy `secrets.example.php` to `secrets.php` and set:
  ```php
  'GTM_ID' => 'GTM-XXXXXXX',
  ```

---

## Step 5 — Test with Tag Assistant & Publish
1. In Google Tag Manager, click **Preview** (top right) and enter `https://papayawhip-pheasant-312330.hostingersite.com`.
2. On the staging site, click **Accept all** on the cookie banner → verify `Conversion Linker - All Pages` and `Google Tag - GA4 Base` fire.
3. Submit a test booking on `/prices.php` or `/contact.php` → on `/thank-you.php`, verify **`Google Ads - Conversion - Booking / Lead Form`** and **`GA4 Event - generate_lead`** fire with the EUR value and `event_id`.
4. Back in Google Tag Manager, click **Submit** → **Publish**.

---

## Included in `gtm_container_sea_college.json`
| Type | Name | Trigger / Purpose |
|---|---|---|
| Tag (`gclidw`) | `Conversion Linker - All Pages` | Stores `gclid`/`gbraid`/`wbraid` first-party cookies + URL passthrough for Consent Mode v2 |
| Tag (`googtag`) | `Google Tag - GA4 Base` | Loads GA4 (`{{Const - GA4 Measurement ID}}`) on all pages |
| Tag (`awct`) | `Google Ads - Conversion - Booking / Lead Form` | Fires on `generate_lead` (`/thank-you.php`) with dynamic EUR `value`, `currency`, and deduplicated `orderId` (`event_id`) |
| Tag (`awct`) | `Google Ads - Conversion - WhatsApp Click (Optional)` | Paused by default; fires on `whatsapp_click` once unpaused |
| Tag (`sp`) | `Google Ads - Remarketing - All Pages` | Builds Google Ads remarketing audiences (respects Consent Mode v2) |
| Tags (`gaawe` ×6) | `generate_lead`, `add_to_cart`, `whatsapp_click`, `contact`, `cta_click`, `sign_up` | Sends full booking funnel & ecommerce item data to GA4 |
