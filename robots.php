<?php
/** robots.txt (served at /robots.txt via .htaccess). Staging blocks all crawlers. */
declare(strict_types=1);

require_once __DIR__ . '/config/site_config.php';

header('Content-Type: text/plain; charset=UTF-8');
if (SITE_NOINDEX) {
    echo "User-agent: *\nDisallow: /\n";
    exit;
}
echo "User-agent: *\n"
    . "Allow: /\n"
    . "Allow: /llms.txt\n"
    . "Allow: /llms-full.txt\n"
    . "Allow: /*/llms.txt\n"
    . "Allow: /*/llms-full.txt\n"
    . "Disallow: /handlers/\n"
    . "Disallow: /s-tm/\n"
    . "Disallow: /s-aw/\n"
    . "Disallow: /s-ga/\n"
    . "Disallow: /app\n"
    . "Disallow: /thank-you\n"
    . "Disallow: /thank-you.php\n"
    . "Disallow: /*/thank-you\n\n"
    . "Sitemap: " . SITE_URL . "/sitemap.xml\n"
    . "# AI / LLM Discovery (EN): " . SITE_URL . "/llms.txt\n"
    . "# AI / LLM Full Catalog (EN): " . SITE_URL . "/llms-full.txt\n"
    . "# AI / LLM Localized Discovery (RU/IT/DE/PL/FR/AR/ZH): " . SITE_URL . "/ru/llms.txt | " . SITE_URL . "/it/llms.txt | " . SITE_URL . "/de/llms.txt | " . SITE_URL . "/pl/llms.txt | " . SITE_URL . "/fr/llms.txt | " . SITE_URL . "/ar/llms.txt | " . SITE_URL . "/zh/llms.txt\n";
