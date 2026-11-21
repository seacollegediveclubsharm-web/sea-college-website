<?php
/**
 * <head> + opening <body>. Expects $page (from bootstrap, overridden by the page).
 */
declare(strict_types=1);

$sc_title = $page['title'] ?? t('meta_home_title');
$sc_desc = $page['description'] ?? t('meta_home_desc');
$sc_canonical = canonical_url();
$sc_og_locale = [
    'en' => 'en_GB',
    'ru' => 'ru_RU',
    'it' => 'it_IT',
    'pl' => 'pl_PL',
    'de' => 'de_DE',
    'fr' => 'fr_FR',
    'ar' => 'ar_EG',
    'zh' => 'zh_CN',
];
$sc_pid = (string) ($page['id'] ?? 'home');
if (!headers_sent() && in_array(($_SERVER['REQUEST_METHOD'] ?? 'GET'), ['GET', 'HEAD'], true)) {
    header_remove('Pragma');
    header_remove('Expires');
    $sc_can_edge_cache = !SITE_NOINDEX
        && empty($page['noindex'])
        && $sc_pid !== '404'
        && $sc_pid !== 'thanks'
        && empty($GLOBALS['SC_HAS_CAMPAIGN_QUERY'])
        && empty($_COOKIE['sc_sid'])
        && empty($_COOKIE['sc_attr']);
    if ($sc_can_edge_cache) {
        header('Cache-Control: public, max-age=120, s-maxage=300, stale-while-revalidate=86400');
    } elseif (empty($_COOKIE['sc_sid'])) {
        header('Cache-Control: private, no-cache');
    }
}

$sc_css_key = match (true) {
    $sc_pid === 'home' => 'home',
    str_starts_with($sc_pid, 'hub-') => 'hub',
    $sc_pid === 'prices' => 'prices',
    $sc_pid === 'item' => 'item',
    $sc_pid === 'dive-sites' => 'sites',
    $sc_pid === 'gallery' => 'gallery',
    $sc_pid === 'about' => 'about',
    default => 'base',
};
$sc_js_key = match (true) {
    $sc_pid === 'home' || str_starts_with($sc_pid, 'hub-') => 'home',
    $sc_pid === 'prices' => 'prices',
    $sc_pid === 'item' => 'item',
    $sc_pid === 'dive-sites' => 'sites',
    $sc_pid === 'gallery' || $sc_pid === 'about' => 'gallery',
    default => 'base',
};
$sc_css_file = ROOT_DIR . '/assets/css/dist/' . $sc_css_key . '.min.css';
?><!DOCTYPE html>
<html lang="<?= e(LANG) ?>" dir="<?= e(DIR) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($sc_title) ?></title>
<meta name="description" content="<?= e($sc_desc) ?>">
<?php if (SITE_NOINDEX || !empty($page['noindex'])): ?>
<meta name="robots" content="noindex, nofollow">
<?php else: ?>
<meta name="robots" content="index, follow, max-image-preview:large">
<link rel="canonical" href="<?= e($sc_canonical) ?>">
<?php foreach (array_keys(SUPPORTED_LANGS) as $sc_l): ?>
<link rel="alternate" hreflang="<?= e($sc_l) ?>" href="<?= e(canonical_url($sc_l)) ?>">
<?php endforeach; ?>
<link rel="alternate" hreflang="x-default" href="<?= e(canonical_url(DEFAULT_LANG)) ?>">
<?php endif; ?>
<link rel="sitemap" type="application/xml" title="Sitemap" href="<?= e(SITE_URL . '/sitemap.xml') ?>">
<meta name="theme-color" content="#0B1D2C">
<meta name="author" content="<?= e(SITE_NAME) ?>">
<meta name="copyright" content="© <?= e(BUSINESS['founded'] . '–' . date('Y') . ' ' . SITE_NAME) ?>. All rights reserved.">

<!-- Open Graph / Twitter -->
<meta property="og:type" content="<?= e($page['type'] ?? 'website') ?>">
<meta property="og:site_name" content="<?= e(SITE_NAME) ?>">
<meta property="og:title" content="<?= e($sc_title) ?>">
<meta property="og:description" content="<?= e($sc_desc) ?>">
<meta property="og:url" content="<?= e($sc_canonical) ?>">
<meta property="og:image" content="<?= e($page['image']) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="<?= e($sc_og_locale[LANG] ?? 'en_GB') ?>">
<?php foreach ($sc_og_locale as $sc_l => $sc_loc): if ($sc_l !== LANG): ?>
<meta property="og:locale:alternate" content="<?= e($sc_loc) ?>">
<?php endif; endforeach; ?>
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($sc_title) ?>">
<meta name="twitter:description" content="<?= e($sc_desc) ?>">
<meta name="twitter:image" content="<?= e($page['image']) ?>">

<link rel="icon" href="<?= e(asset('images/favicon.png')) ?>" type="image/png">
<link rel="apple-touch-icon" href="<?= e(asset('images/apple-touch-icon.png')) ?>">
<?php if ($sc_pid === 'home'): ?>
<link rel="preload" as="image" type="image/webp" imagesrcset="<?= e(asset('images/hero-640.webp')) ?> 640w, <?= e(asset('images/hero-1024.webp')) ?> 1024w, <?= e(asset('images/hero-1600.webp')) ?> 1600w" imagesizes="100vw" fetchpriority="high">
<?php elseif (!empty($page['preload_rel'])):
    $sc_pr_clean = ltrim((string) $page['preload_rel'], '/');
    $sc_pr_dot = strrpos($sc_pr_clean, '.');
    $sc_pr_stem = $sc_pr_dot !== false ? substr($sc_pr_clean, 0, $sc_pr_dot) : $sc_pr_clean;
    if (is_file(ROOT_DIR . '/assets/' . $sc_pr_stem . '-480w.webp') && is_file(ROOT_DIR . '/assets/' . $sc_pr_stem . '.webp')): ?>
<link rel="preload" as="image" type="image/webp" imagesrcset="<?= e(asset($sc_pr_stem . '-480w.webp')) ?> 480w, <?= e(asset($sc_pr_stem . '.webp')) ?> 960w" imagesizes="(max-width: 959px) 92vw, 46vw" fetchpriority="high">
<?php elseif (is_file(ROOT_DIR . '/assets/' . $sc_pr_stem . '.webp')): ?>
<link rel="preload" as="image" type="image/webp" href="<?= e(asset($sc_pr_stem . '.webp')) ?>" fetchpriority="high">
<?php endif; elseif (!empty($page['preload_image'])): ?>
<link rel="preload" as="image" href="<?= e((string) $page['preload_image']) ?>" fetchpriority="high">
<?php endif; ?>
<?php if (is_file($sc_css_file)): ?>
<style><?= file_get_contents($sc_css_file) ?></style>
<?php else: ?>
<link rel="stylesheet" href="<?= e(asset('css/main.min.css')) ?>">
<?php endif; ?>

<?php require ROOT_DIR . '/includes/tracking_head.php'; ?>
<?php require ROOT_DIR . '/includes/schema.php'; ?>
<script src="<?= e(asset('js/dist/' . $sc_js_key . '.min.js')) ?>" defer></script>
</head>
<body class="page-<?= e($page['id']) ?><?= !empty($page['body_class']) ? ' ' . e((string) $page['body_class']) : '' ?>">
<?php require ROOT_DIR . '/includes/tracking_body.php'; ?>
<a class="skip-link" href="#main"><?= te('skip_to_content') ?></a>
<?php require ROOT_DIR . '/includes/navbar.php'; ?>
<main id="main">
