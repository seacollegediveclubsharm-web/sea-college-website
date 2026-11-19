#!/usr/bin/env bash
# Sea College Dive Club — Pre-Cutover Automated Smoke Test
# Usage:
#   ./docs/pre_cutover_check.sh https://papayawhip-pheasant-312330.hostingersite.com
#   ./docs/pre_cutover_check.sh https://127.0.0.1:8080 www.seacollegediveclub.com
set -euo pipefail

BASE_URL="${1:-https://papayawhip-pheasant-312330.hostingersite.com}"
HOST_HEADER="${2:-}"
CURL_ARGS=(-sS --max-time 12)
if [[ -n "$HOST_HEADER" ]]; then
  CURL_ARGS+=(-H "Host: $HOST_HEADER")
fi

pass=0; fail=0
check_code() {
  local path="$1" expect="$2"
  local code
  code=$(curl "${CURL_ARGS[@]}" -o /dev/null -w "%{http_code}" "${BASE_URL}${path}")
  if [[ "$code" == "$expect" ]]; then
    echo "PASS [$code] $path"
    pass=$((pass + 1))
  else
    echo "FAIL [$code != $expect] $path"
    fail=$((fail + 1))
  fi
}

echo "=== 1. Core Routes, 4 Category Hubs & Security Boundaries ($BASE_URL) ==="
for p in \
  "/" "/services.php" "/prices.php" "/about.php" "/contact.php" "/privacy-policy.php" "/terms.php" "/sitemap.xml" "/robots.txt" \
  "/diving-trips-sharm-el-sheikh" \
  "/diving-courses-sharm-el-sheikh" \
  "/padi-course-bundles-sharm-el-sheikh" \
  "/snorkeling-and-boats-sharm-el-sheikh" \
  "/tours/discover-scuba-diving-2-dives-boat-sharm" \
  "/tours/ss-thistlegorm-wreck-dive-sharm-el-sheikh" \
  "/tours/6-dives-package-sharm-el-sheikh" \
  "/tours/white-island-ras-mohammed-boat-trip" \
  "/tours/full-scuba-equipment-rental-sharm" \
  "/courses/padi-open-water-diver-sharm-el-sheikh" \
  "/courses/padi-open-water-diver-sharm-el-sheikh?lang=ar" \
  "/courses/mega-bundle-open-water-advanced-sharm" \
  "/dive-sites-sharm-el-sheikh" \
  "/gallery" \
  "/ar/" "/ru/" "/de/" "/it/" "/fr/" "/pl/" "/zh/" \
  "/ru/courses/padi-open-water-diver-sharm-el-sheikh?gclid=EAIaIQobChMI_test_ru" \
  "/de/prices.php?gclid=EAIaIQobChMI_test_de" \
  "/it/diving-trips-sharm-el-sheikh" \
  "/fr/dive-sites-sharm-el-sheikh" \
  "/pl/tours/ss-thistlegorm-wreck-dive-sharm-el-sheikh" \
  "/ar/courses/padi-open-water-diver-sharm-el-sheikh" \
  "/zh/courses/padi-open-water-diver-sharm-el-sheikh?gclid=EAIaIQobChMI_test_zh" \
  "/zh/prices.php" \
  "/assets/images/items/sea-college-sharm-padi-open-water-diver-sharm-el-sheikh.jpg" \
  "/assets/images/items/sea-college-sharm-ss-thistlegorm-wreck-dive-sharm-el-sheikh.jpg" \
  "/assets/images/items/sea-college-sharm-white-island-ras-mohammed-nizar-boat-nov2026.jpg" \
  "/assets/images/items/sea-college-sharm-private-boat-charter-1-5-omar4-nov2026.jpg" \
  "/assets/images/items/sea-college-sharm-private-yacht-charter-11-15-oceano-nov2026.jpg" \
  "/assets/images/items/sea-college-sharm-private-boat-charter-16-20-oceano-nov2026.jpg" \
  "/assets/images/gallery/sea-college-dive-club-sharm-el-sheikh-oceano-luxury-yacht-nov2026.jpg" \
  "/assets/images/clients/sea-college-real-guests-padi-certificates-sharm.jpg" \
  "/assets/images/clients/sea-college-real-guests-boat-group-red-sea.jpg"; do
  check_code "$p" "200"
done

check_code "/product/open-water-diver/" "301"
check_code "/product/thistlegorm-wreck/" "301"
check_code "/product/million-hope-jackson-reef/" "301"
check_code "/about-us/" "301"
check_code "/checkout/" "301"
check_code "/tours/padi-open-water-diver-sharm-el-sheikh" "301"
check_code "/tours/non-existent-slug-xyz" "404"

# Verify ?gclid= survives 301 redirects (.htaccess + PHP item.php) for active Google Ads continuity
for legacy_gclid in \
  "/product/open-water-diver/?gclid=EAIaIQobChMI_test_legacy" \
  "/about-us/?gclid=EAIaIQobChMI_test_legacy"; do
  loc_hdr=$(curl "${CURL_ARGS[@]}" -I "${BASE_URL}${legacy_gclid}" | tr -d '\r' | grep -i '^location:' || true)
  if grep -q "gclid=EAIaIQobChMI_test_legacy" <<<"$loc_hdr"; then
    echo "PASS [301 gclid preserved] $legacy_gclid -> ${loc_hdr#*: }"
    pass=$((pass + 1))
  else
    echo "FAIL [301 gclid lost] $legacy_gclid -> ${loc_hdr}"
    fail=$((fail + 1))
  fi
done

for p in "/config/site_config.php" "/storage/" "/.env.template" "/docs/architecture_governance.md"; do
  check_code "$p" "403"
done

echo "=== 2. Unified Tracking, GBP, Tripadvisor, YouTube, Banners, Dropdown & Home SEO Checks ==="
html=$(curl "${CURL_ARGS[@]}" "${BASE_URL}/")
for needle in "GTM-PBVQ56XC" "G-99528RZN9Z" "AW-16728866067" "url_passthrough" "2980406819004765" "MUaOhb7KY5wwxWBDLVgRYIGRxrsU_cW2o36ihx1Kv1k" "ChIJ75cBQp5LUxQRMjrNYQx93g0" "AggregateRating" "GeoCoordinates" "data-dive-quiz" "TA_cdswritereviewlgvi409" "locationId=26104236" "d26104236" "youtube.com/@seacollege.diveclub" "brand-svg--youtube" "brand-svg--tripadvisor" "promo-banner--top" "diveoneclub.com/Listing/sea-college-dive-club" "sea-college-real-guests-padi-certificates-sharm.jpg" 'hreflang="ru"' 'hreflang="it"' 'hreflang="pl"' 'hreflang="de"' 'hreflang="fr"' 'hreflang="ar"' 'hreflang="zh"' 'hreflang="x-default"'; do
  if grep -q "$needle" <<<"$html"; then
    echo "PASS [present] $needle"
    pass=$((pass + 1))
  else
    echo "FAIL [missing] $needle"
    fail=$((fail + 1))
  fi
done

ar_html=$(curl "${CURL_ARGS[@]}" "${BASE_URL}/ar/")
for needle in 'lang="ar" dir="rtl"' "TA_cdswritereviewlgvi969" "uniq=969" "lang=ar_EG" "tripadvisor.com.eg" 'href="/ar/prices.php"'; do
  if grep -q "$needle" <<<"$ar_html"; then
    echo "PASS [ar clean URL & tripadvisor] $needle"
    pass=$((pass + 1))
  else
    echo "FAIL [ar clean URL & tripadvisor missing] $needle"
    fail=$((fail + 1))
  fi
done

echo "=== 3. 8-Language Zero-Drop & Google Ads Subdirectory Checks ==="
for lg in ru it pl de fr zh; do
  lg_html=$(curl "${CURL_ARGS[@]}" "${BASE_URL}/${lg}/courses/padi-open-water-diver-sharm-el-sheikh")
  for needle in "lang=\"${lg}\"" "href=\"/${lg}/prices.php\"" "\"inLanguage\":\"${lg}\"" "\"@type\":\"Course\""; do
    if grep -q "$needle" <<<"$lg_html"; then
      echo "PASS [${lg} course zero-drop] $needle"
      pass=$((pass + 1))
    else
      echo "FAIL [${lg} course zero-drop] $needle"
      fail=$((fail + 1))
    fi
  done
done

echo "=== 4. Item Page Gallery + VideoObject, Side Banners, Calculator, Dive Map & Gallery Checks ==="
course_html=$(curl "${CURL_ARGS[@]}" "${BASE_URL}/courses/padi-open-water-diver-sharm-el-sheikh")
for needle in '"@type":"Course"' '"@type":"Product"' '"@type":"VideoObject"' '"@type":"FAQPage"' 'SpeakableSpecification' 'quick-answer' 'data-item-gallery' 'promo-banner--side'; do
  if grep -q "$needle" <<<"$course_html"; then
    echo "PASS [course AEO/gallery/banner] $needle"
    pass=$((pass + 1))
  else
    echo "FAIL [course AEO/gallery/banner] $needle"
    fail=$((fail + 1))
  fi
done

prices_html=$(curl "${CURL_ARGS[@]}" "${BASE_URL}/prices.php")
if grep -q "data-dive-calc" <<<"$prices_html"; then
  echo "PASS [prices calculator] data-dive-calc"
  pass=$((pass + 1))
else
  echo "FAIL [prices calculator] data-dive-calc missing"
  fail=$((fail + 1))
fi

sites_html=$(curl "${CURL_ARGS[@]}" "${BASE_URL}/dive-sites-sharm-el-sheikh")
for needle in "data-dive-map" "TouristAttraction" "conditions-chart"; do
  if grep -q "$needle" <<<"$sites_html"; then
    echo "PASS [dive-sites] $needle"
    pass=$((pass + 1))
  else
    echo "FAIL [dive-sites] $needle missing"
    fail=$((fail + 1))
  fi
done

gal_html=$(curl "${CURL_ARGS[@]}" "${BASE_URL}/gallery")
for needle in "data-gallery" "VideoObject" "ImageGallery" "team-card"; do
  if grep -q "$needle" <<<"$gal_html"; then
    echo "PASS [gallery & team] $needle"
    pass=$((pass + 1))
  else
    echo "FAIL [gallery & team] $needle missing"
    fail=$((fail + 1))
  fi
done

smap_count=$(curl "${CURL_ARGS[@]}" "${BASE_URL}/sitemap.xml" | grep -c "<loc>" || true)
if [[ "$smap_count" -ge 744 ]]; then
  echo "PASS [sitemap] $smap_count <loc> URLs (93 routes x 8 languages)"
  pass=$((pass + 1))
else
  echo "FAIL [sitemap] expected >= 744 <loc> URLs, got $smap_count"
  fail=$((fail + 1))
fi

echo "Summary: $pass passed, $fail failed"
[[ "$fail" -eq 0 ]]

