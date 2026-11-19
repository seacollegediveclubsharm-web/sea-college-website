<?php
/** XML sitemap with hreflang alternates for core pages, 4 Category Hubs & all 80 item pages. */
declare(strict_types=1);

require_once __DIR__ . '/config/site_config.php';
$catalog = require __DIR__ . '/data/catalog.php';

$pages = [
    ['/', '1.0', 'weekly', '/index.php'],
    ['/services.php', '0.9', 'weekly', '/services.php'],
    ['/dive-sites-sharm-el-sheikh', '0.9', 'weekly', '/dive-sites.php'],
    ['/prices.php', '0.9', 'weekly', '/prices.php'],
    ['/gallery', '0.8', 'weekly', '/gallery.php'],
    ['/about.php', '0.6', 'monthly', '/about.php'],
    ['/contact.php', '0.7', 'monthly', '/contact.php'],
    ['/privacy-policy.php', '0.2', 'yearly', '/privacy-policy.php'],
    ['/terms.php', '0.2', 'yearly', '/terms.php'],
];

foreach ($catalog as $cat) {
    if (!empty($cat['hub_slug'])) {
        $pages[] = ['/' . $cat['hub_slug'], '0.9', 'weekly', '/category.php'];
    }
    foreach ($cat['groups'] as $g) {
        $prio = !empty($g['compact']) ? '0.65' : '0.85';
        foreach ($g['items'] as $it) {
            if (!empty($it['slug'])) {
                $sec = ($it['section'] ?? 'tours') === 'courses' ? 'courses' : 'tours';
                $pages[] = ['/' . $sec . '/' . $it['slug'], $prio, 'weekly', '/item.php'];
            }
        }
    }
}

$loc = static fn(string $path, string $lang): string => SITE_URL . ($lang !== DEFAULT_LANG ? '/' . $lang . ($path === '/' ? '/' : $path) : $path);
$x = static fn(string $s): string => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
$catMod = is_file(__DIR__ . '/data/catalog.php') ? (int) filemtime(__DIR__ . '/data/catalog.php') : time();

header('Content-Type: application/xml; charset=UTF-8');
if (SITE_NOINDEX) {
    header('X-Robots-Tag: noindex');
}
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
foreach ($pages as [$path, $prio, $freq, $srcFile]) {
    $file = __DIR__ . $srcFile;
    $ts = max($catMod, is_file($file) ? (int) filemtime($file) : 0);
    $mod = gmdate('Y-m-d', $ts);
    foreach (array_keys(SUPPORTED_LANGS) as $lang) {
        echo "  <url>\n    <loc>" . $x($loc($path, $lang)) . "</loc>\n    <lastmod>{$mod}</lastmod>\n    <changefreq>{$freq}</changefreq>\n    <priority>{$prio}</priority>\n";
        foreach (array_keys(SUPPORTED_LANGS) as $alt) {
            echo '    <xhtml:link rel="alternate" hreflang="' . $x($alt) . '" href="' . $x($loc($path, $alt)) . "\"/>\n";
        }
        echo '    <xhtml:link rel="alternate" hreflang="x-default" href="' . $x($loc($path, DEFAULT_LANG)) . "\"/>\n  </url>\n";
    }
}
echo "</urlset>\n";
