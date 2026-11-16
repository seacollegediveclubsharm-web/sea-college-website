# Sea College Dive Club — website (PHP 8.2)

Modular, mobile-first, bilingual (EN / AR-RTL) website with consent-aware marketing tags.
Blueprint: [`docs/project_blueprint.md`](docs/project_blueprint.md).

## Structure
| Path | Purpose |
|---|---|
| `config/site_config.php` | **All** IDs, URLs, contact data, flags (reads `config/secrets.php` / env first) |
| `config/secrets.example.php` | Copy to `config/secrets.php` (git-ignored) for tracking IDs & secrets |
| `data/catalog.php` | Services & prices (single source for pages, cart, schema, server re-pricing) |
| `lang/en.php`, `lang/ar.php` | Dictionaries (`?lang=ar` → RTL) |
| `includes/` | bootstrap, header/navbar/footer, tracking_head/body, schema, forms, components, lead_lib |
| `handlers/` | `lead_capture.php`, `newsletter.php` (CSRF, honeypot, rate limit, CSV + email + webhook + Meta CAPI) |
| `assets/` | `css/main.css`, `js/consent.js`, `js/main.js`, `images/` (branded, SEO-named) |
| Root | `index`, `services`, `prices`, `about`, `contact`, `privacy-policy`, `terms`, `thank-you`, `404`, `sitemap` (→ `/sitemap.xml`), `robots` (→ `/robots.txt`) |
| `storage/` | Lead CSVs (blocked from the web, git-ignored) |

## Deploy (Hostinger Git → `public_html`)
1. Push to `main` → hPanel **Advanced → Git** auto-deploys to the staging site.
2. Upload `config/secrets.php` once via **File Manager** (it is not in Git).
3. Staging is `noindex` + `robots.txt: Disallow: /` automatically.

## Go-live checklist
- In `config/secrets.php`: `'SITE_ENV' => 'production'`, `'SITE_URL' => 'https://<your-domain>'`.
- Fill GTM / GA4 / Ads / Meta / TikTok IDs (empty = disabled; nothing loads).
- SMTP user/pass (Hostinger email) so lead notifications are delivered reliably.
- Submit `https://<domain>/sitemap.xml` in Google Search Console.

## Local run
```bash
php -d session.save_path=/tmp -S 127.0.0.1:8080 -t .
find . -name '*.php' -not -path './.git/*' -exec php -l {} \;
```
