<?php
/** XML sitemap with hreflang alternates (served at /sitemap.xml via .htaccess). */
declare(strict_types=1);

require_once __DIR__ . '/config/site_config.php';

$pages = [
    ['/', '1.0', 'weekly'],
    ['/services.php', '0.9', 'weekly'],
    ['/prices.php', '0.9', 'weekly'],
    ['/about.php', '0.6', 'monthly'],
    ['/contact.php', '0.7', 'monthly'],
    ['/privacy-policy.php', '0.2', 'yearly'],
    ['/terms.php', '0.2', 'yearly'],
];
$loc = static fn(string $path, string $lang): string => SITE_URL . $path . ($lang !== DEFAULT_LANG ? '?lang=' . $lang : '');
$x = static fn(string $s): string => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');

header('Content-Type: application/xml; charset=UTF-8');
if (SITE_NOINDEX) {
    header('X-Robots-Tag: noindex');
}
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
foreach ($pages as [$path, $prio, $freq]) {
    $file = __DIR__ . ($path === '/' ? '/index.php' : $path);
    $mod = is_file($file) ? gmdate('Y-m-d', (int) filemtime($file)) : gmdate('Y-m-d');
    foreach (array_keys(SUPPORTED_LANGS) as $lang) {
        echo "  <url>\n    <loc>" . $x($loc($path, $lang)) . "</loc>\n    <lastmod>{$mod}</lastmod>\n    <changefreq>{$freq}</changefreq>\n    <priority>{$prio}</priority>\n";
        foreach (array_keys(SUPPORTED_LANGS) as $alt) {
            echo '    <xhtml:link rel="alternate" hreflang="' . $x($alt) . '" href="' . $x($loc($path, $alt)) . "\"/>\n";
        }
        echo '    <xhtml:link rel="alternate" hreflang="x-default" href="' . $x($loc($path, DEFAULT_LANG)) . "\"/>\n  </url>\n";
    }
}
echo "</urlset>\n";
