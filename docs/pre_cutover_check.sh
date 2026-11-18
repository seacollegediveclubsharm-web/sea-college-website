#!/usr/bin/env bash
# Sea College Dive Club — Pre-Cutover Automated Smoke Test
# Usage:
#   ./docs/pre_cutover_check.sh https://papayawhip-pheasant-312330.hostingersite.com
#   ./docs/pre_cutover_check.sh https://127.0.0.1:8080 www.seacollegediveclub.com
set -euo pipefail

BASE_URL="${1:-https://papayawhip-pheasant-312330.hostingersite.com}"
HOST_HEADER="${2:-}"
CURL_ARGS=(-sS --max-time 10)
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

echo "=== 1. Core Routes & Security Boundaries ($BASE_URL) ==="
for p in "/" "/services.php" "/prices.php" "/about.php" "/contact.php" "/privacy-policy.php" "/terms.php" "/sitemap.xml" "/robots.txt"; do
  check_code "$p" "200"
done
for p in "/config/site_config.php" "/storage/" "/.env.template" "/docs/architecture_governance.md"; do
  check_code "$p" "403"
done

echo "=== 2. Unified Tracking, GBP & SEO Checks ==="
html=$(curl "${CURL_ARGS[@]}" "${BASE_URL}/")
for needle in "GTM-PBVQ56XC" "G-99528RZN9Z" "AW-16728866067" "2622478794929488" "MUaOhb7KY5wwxWBDLVgRYIGRxrsU_cW2o36ihx1Kv1k" "ChIJ75cBQp5LUxQRMjrNYQx93g0" "AggregateRating" "GeoCoordinates"; do
  if grep -q "$needle" <<<"$html"; then
    echo "PASS [present] $needle"
    pass=$((pass + 1))
  else
    echo "FAIL [missing] $needle"
    fail=$((fail + 1))
  fi
done

echo "Summary: $pass passed, $fail failed"
[[ "$fail" -eq 0 ]]
