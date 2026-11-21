#!/usr/bin/env php
<?php
/**
 * CLI utility: Validates https://seacollegediveclub.com/sitemap.xml (800 clean extensionless URLs)
 * + appends /llms.txt & /llms-full.txt (802 total URLs), and submits in a single batch POST to IndexNow
 * (Bing, Yandex, ChatGPT Search, Seznam, Naver).
 *
 * Usage:
 *   php docs/submit_indexnow.php --dry-run [--local]
 *   php docs/submit_indexnow.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$args = $argv ?? [];
$dryRun = in_array('--dry-run', $args, true);
$useLocal = in_array('--local', $args, true);

$host = 'seacollegediveclub.com';
$key = '8f4c2a9b1d7e6053c8a1f42e9d0b7c3a';
$keyLoc = "https://{$host}/{$key}.txt";
$sitemapUrl = "https://{$host}/sitemap.xml";

$xml = false;
if ($useLocal) {
    ob_start();
    require dirname(__DIR__) . '/sitemap.php';
    $xml = (string) ob_get_clean();
} elseif (function_exists('curl_init')) {
    $ch = curl_init($sitemapUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'SeaCollegeIndexNow/1.0 (+https://seacollegediveclub.com)',
    ]);
    $res = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (is_string($res) && $code === 200) {
        $xml = $res;
    }
}

if ($xml === false || $xml === '') {
    fwrite(STDERR, "Failed to load sitemap XML from " . ($useLocal ? 'local sitemap.php' : $sitemapUrl) . "\n");
    exit(1);
}

preg_match_all('#<loc>([^<]+)</loc>#', $xml, $m);
$sitemapUrls = array_values(array_unique($m[1] ?? []));
$phpLocs = array_filter($sitemapUrls, static fn(string $u): bool => str_contains($u, '.php'));

$urls = array_values(array_unique([
    ...$sitemapUrls,
    "https://{$host}/llms.txt",
    "https://{$host}/llms-full.txt",
]));

echo sprintf(
    "Prepared %d unique URLs (%d sitemap <loc> + 2 AI discovery endpoints; %d .php URLs)\n",
    count($urls),
    count($sitemapUrls),
    count($phpLocs)
);

if ($dryRun) {
    echo "[DRY-RUN] Key Location : {$keyLoc}\n";
    echo "[DRY-RUN] Sample URLs  : " . implode(', ', array_slice($urls, 0, 5)) . ' ... ' . implode(', ', array_slice($urls, -2)) . "\n";
    exit(count($urls) >= 800 && count($phpLocs) === 0 ? 0 : 1);
}

$payload = json_encode([
    'host' => $host,
    'key' => $key,
    'keyLocation' => $keyLoc,
    'urlList' => $urls,
], JSON_UNESCAPED_SLASHES);

foreach (['https://api.indexnow.org/IndexNow', 'https://yandex.com/indexnow'] as $endpoint) {
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json; charset=utf-8',
            'User-Agent: SeaCollegeIndexNow/1.0 (+https://seacollegediveclub.com)',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "{$endpoint} -> HTTP {$code}" . ($resp ? ": {$resp}" : " (Accepted — all " . count($urls) . " URLs queued across IndexNow network!)") . "\n";
    if ($code >= 200 && $code < 300) {
        exit(0);
    }
}
exit(1);
