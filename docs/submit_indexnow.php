#!/usr/bin/env php
<?php
/**
 * CLI utility: Validates live https://seacollegediveclub.com/sitemap.xml (800 URLs)
 * and submits all URLs in a single batch POST to IndexNow (Bing, Yandex, ChatGPT Search, Seznam, Naver).
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$host = 'seacollegediveclub.com';
$key = '8f4c2a9b1d7e6053c8a1f42e9d0b7c3a';
$keyLoc = "https://{$host}/{$key}.txt";
$sitemapUrl = "https://{$host}/sitemap.xml";

$xml = file_get_contents($sitemapUrl);
if ($xml === false) {
    fwrite(STDERR, "Failed to fetch {$sitemapUrl}\n");
    exit(1);
}
preg_match_all('#<loc>([^<]+)</loc>#', $xml, $m);
$urls = array_values(array_unique($m[1] ?? []));
echo "Fetched " . count($urls) . " unique <loc> URLs from {$sitemapUrl}\n";

$payload = json_encode([
    'host' => $host,
    'key' => $key,
    'keyLocation' => $keyLoc,
    'urlList' => $urls,
], JSON_UNESCAPED_SLASHES);

$ch = curl_init('https://api.indexnow.org/IndexNow');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 20,
]);
$resp = curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "IndexNow response HTTP {$code}" . ($resp ? ": {$resp}" : " (Accepted!)") . "\n";
exit(($code >= 200 && $code < 300) ? 0 : 1);
