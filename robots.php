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
    . "Disallow: /handlers/\n"
    . "Disallow: /thank-you\n"
    . "Disallow: /thank-you.php\n\n"
    . "Sitemap: " . SITE_URL . "/sitemap.xml\n"
    . "# AI / LLM Discovery: " . SITE_URL . "/llms.txt\n"
    . "# AI / LLM Full Catalog: " . SITE_URL . "/llms-full.txt\n";
