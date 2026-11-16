# Google Ads + GTM Setup on Temporary Staging Domain (Ready for Final Domain Switch)

This setup connects your **Google Ads** and **Google Tag Manager (GTM)** to the **temporary staging site** (`https://papayawhip-pheasant-312330.hostingersite.com`) right now — **without touching your current live website** — and switches cleanly to your final domain on launch day without recreating any tags or IDs.

Pre-built GTM import file: [`docs/gtm_container_sea_college.json`](gtm_container_sea_college.json)

---

## Phase A — Setup Now on the Temporary Link

### Step 1 — Create a New GTM Web Container
1. Open https://tagmanager.google.com → **Create Account** (or **Create Container** inside your existing account):
   - **Account Name:** `Sea College Dive Club`
   - **Country:** `Egypt`
   - **Container Name:** `Sea College - New Website`
   - **Target Platform:** **Web**
2. Copy the Container ID at the top (**`GTM-XXXXXXX`**).  
   *(GTM containers work on any domain, so this exact `GTM-XXXXXXX` stays the same on your temporary link and on your final domain.)*

### Step 2 — Create the Conversion Action in Google Ads (Safe for Existing Campaigns)
1. Open https://ads.google.com → **Goals** → **Conversions** → **Summary** → **+ New conversion action** → **Website**.
2. Paste your temporary URL: `https://papayawhip-pheasant-312330.hostingersite.com` → click **Scan**.
3. Scroll down and click **+ Add a conversion action manually**:
   - **Goal / Category:** `Submit lead form`
   - **Action optimization:** Select **Secondary action (observe only)** while on staging *(important if you already run live Google Ads on your current website — this stops staging test bookings from affecting live bidding; you will flip it to **Primary** at launch)*.
   - **Conversion name:** `New Website - Booking / Enquiry`
   - **Value:** `Use different values for each conversion` (default `50` EUR — the website sends the real EUR basket total automatically).
   - **Count:** `One`
   - **Enhanced conversions:** Turn **ON** (choose **Google Tag Manager**).
   - Click **Done** → **Save and continue**.
4. Open the **Use Google Tag Manager** tab and copy:
   - **Conversion ID** (digits only, e.g. `1234567890` — **without** `AW-`)
   - **Conversion Label** (e.g. `AbCdEfGhIjKlMnOpQr`)

### Step 3 — Import `gtm_container_sea_college.json` into GTM
1. Download [`gtm_container_sea_college.json`](gtm_container_sea_college.json) (on GitHub: open `docs/gtm_container_sea_college.json` and click **Download raw file**).
2. In Google Tag Manager → **Admin** → **Import Container**:
   - **Choose container file:** `gtm_container_sea_college.json`
   - **Choose workspace:** **Existing** → `Default Workspace`
   - **Import option:** **Merge** → *Overwrite conflicting tags, triggers and variables*
   - Click **Add to workspace**.
3. Go to **Variables** (left menu) → **User-Defined Variables** and fill in:
   - **`Const - Google Ads Conversion ID`** → digits only from Step 2 (no `AW-`).
   - **`Const - Google Ads Lead Label`** → Conversion Label from Step 2.
   - **`Const - GA4 Measurement ID`** → your GA4 Web Stream ID (`G-XXXXXXXXXX`), or leave as placeholder until GA4 is ready *(in GA4, set the Web Stream URL to `https://papayawhip-pheasant-312330.hostingersite.com` for now)*.
   - *(Optional)* **`Const - Google Ads WhatsApp Label`** → if you also create a `Contact` conversion action for WhatsApp clicks, paste its label here and unpause **`Google Ads - Conversion - WhatsApp Click (Optional)`** under **Tags**.
4. Click **Submit** → **Publish** in GTM.

### Step 4 — Activate `GTM-XXXXXXX` on Staging
- **Reply here with your `GTM-XXXXXXX` ID** — I will add it to [`config/site_config.php`](../config/site_config.php) and push it live to `https://papayawhip-pheasant-312330.hostingersite.com` automatically.
- Then in GTM click **Preview**, open `https://papayawhip-pheasant-312330.hostingersite.com`, click **Accept all** on the cookie banner, and submit one test booking to see **`Google Ads - Conversion - Booking / Lead Form`** fire on `/thank-you.php` (status in Google Ads will change from *Unverified* to *No recent conversions* / *Recording conversions*).

---

## Phase B — When You Connect the Final Domain Later (3-Minute Switch)
You do **not** need to recreate GTM or Google Ads tags:
1. **Website (`config/secrets.php` or `config/site_config.php`):** change `SITE_ENV` to `'production'` and `SITE_URL` to `'https://www.seacollegediveclub.com'`.
2. **Google Ads:** under **Goals → Conversions → Summary**, edit `New Website - Booking / Enquiry` and switch **Action optimization** from *Secondary* to **Primary**; update Ad Final URLs to the new pages.
3. **GA4:** under **Admin → Data Streams**, edit the Web Stream URL from the temporary Hostinger URL to `https://www.seacollegediveclub.com`.
