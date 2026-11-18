# Sea College Dive Club — Architectural Governance Rule

## RULE: UNIFIED DIGITAL ECOSYSTEM & ZERO-ERROR CUTOVER

1. **Single Source of Truth:**
   - All external business platforms, ad accounts, pixels, webhooks, and brand assets MUST route through the centralized environment/config layer ([`config/site_config.php`](../config/site_config.php) + git-ignored `config/secrets.php` / `.env`) and the unified GTM container (`GTM-PBVQ56XC`).
   - No orphaned subdomains or legacy hardcoded IDs are permitted in templates (`index.php`, `prices.php`, `thank-you.php`, etc.).
2. **Domain Migration & Cutover Protocol:**
   Before pointing the primary root domain (`seacollegediveclub.com`) to the New Website:
   - Lower DNS `TTL` to `300` seconds **48 hours prior** to cutover.
   - Pre-provision SSL/TLS certificates on Hostinger for both `@` and `www` to prevent `ERR_SSL_PROTOCOL_ERROR`.
   - Validate all webhook endpoints, OAuth redirect URIs, and CORS policies against the production domain.
   - Run the automated smoke test ([`docs/pre_cutover_check.sh`](pre_cutover_check.sh)) in staging using the production `Host` header (or staging URL) before switching DNS `A`/`CNAME` records.
3. **Production Flip Sequence:**
   - Update `SITE_ENV='production'` and `SITE_URL='https://www.seacollegediveclub.com'` (or apex) in `config/secrets.php`.
   - Confirm `/robots.txt` switches from `Disallow: /` to `Allow: /` + `Sitemap: .../sitemap.xml`, and `noindex` meta tag disappears.
   - Submit `/sitemap.xml` in Google Search Console (`MUaOhb7KY5wwxWBDLVgRYIGRxrsU_cW2o36ihx1Kv1k`).
   - Update Google Ads RSA Final URLs (`Ad ID 817963783396` & `823272996900`) and GA4 Web Stream URL (`G-99528RZN9Z`).
