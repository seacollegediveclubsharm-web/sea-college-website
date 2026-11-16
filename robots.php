<?php
/** robots.txt (served at /robots.txt via .htaccess). Staging blocks all crawlers. */
declare(strict_types=1);

require_once __DIR__ . '/config/site_config.php';

header('Content-Type: text/plain; charset=UTF-8');
if (SITE_NOINDEX) {
    echo "User-agent: *\nDisallow: /\n";
    exit;
}
echo "User-agent: *\nAllow: /\nDisallow: /handlers/\nDisallow: /thank-you.php\n\nSitemap: " . SITE_URL . "/sitemap.xml\n";
