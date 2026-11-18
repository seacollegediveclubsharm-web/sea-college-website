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
  "/courses/mega-bundle-open-water-advanced-sharm"; do
  check_code "$p" "200"
done

check_code "/product/open-water-diver/" "301"
check_code "/tours/padi-open-water-diver-sharm-el-sheikh" "301"
check_code "/tours/non-existent-slug-xyz" "404"

for p in "/config/site_config.php" "/storage/" "/.env.template" "/docs/architecture_governance.md"; do
  check_code "$p" "403"
done

echo "=== 2. Unified Tracking, GBP & Home SEO Checks ==="
html=$(curl "${CURL_ARGS[@]}" "${BASE_URL}/")
for needle in "GTM-PBVQ56XC" "G-99528RZN9Z" "AW-16728866067" "2980406819004765" "MUaOhb7KY5wwxWBDLVgRYIGRxrsU_cW2o36ihx1Kv1k" "ChIJ75cBQp5LUxQRMjrNYQx93g0" "AggregateRating" "GeoCoordinates"; do
  if grep -q "$needle" <<<"$html"; then
    echo "PASS [present] $needle"
    pass=$((pass + 1))
  else
    echo "FAIL [missing] $needle"
    fail=$((fail + 1))
  fi
done

echo "=== 3. Item Page SEO + AEO Structured Data & Sitemap Coverage ==="
course_html=$(curl "${CURL_ARGS[@]}" "${BASE_URL}/courses/padi-open-water-diver-sharm-el-sheikh")
for needle in '"@type":"Course"' '"@type":"Product"' '"@type":"FAQPage"' 'SpeakableSpecification' 'quick-answer'; do
  if grep -q "$needle" <<<"$course_html"; then
    echo "PASS [course AEO/schema] $needle"
    pass=$((pass + 1))
  else
    echo "FAIL [course AEO/schema] $needle"
    fail=$((fail + 1))
  fi
done

smap_count=$(curl "${CURL_ARGS[@]}" "${BASE_URL}/sitemap.xml" | grep -c "<loc>" || true)
if [[ "$smap_count" -ge 180 ]]; then
  echo "PASS [sitemap] $smap_count <loc> URLs"
  pass=$((pass + 1))
else
  echo "FAIL [sitemap] expected >= 180 <loc> URLs, got $smap_count"
  fail=$((fail + 1))
fi

echo "Summary: $pass passed, $fail failed"
[[ "$fail" -eq 0 ]]
