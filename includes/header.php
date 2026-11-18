<?php
/**
 * <head> + opening <body>. Expects $page (from bootstrap, overridden by the page).
 */
declare(strict_types=1);

$sc_title = $page['title'] ?? t('meta_home_title');
$sc_desc = $page['description'] ?? t('meta_home_desc');
$sc_canonical = canonical_url();
$sc_og_locale = ['en' => 'en_GB', 'ar' => 'ar_EG'];
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
<?php endif; ?>
<link rel="canonical" href="<?= e($sc_canonical) ?>">
<?php foreach (array_keys(SUPPORTED_LANGS) as $sc_l): ?>
<link rel="alternate" hreflang="<?= e($sc_l) ?>" href="<?= e(canonical_url($sc_l)) ?>">
<?php endforeach; ?>
<link rel="alternate" hreflang="x-default" href="<?= e(canonical_url(DEFAULT_LANG)) ?>">
<meta name="theme-color" content="#0B1D2C">

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
<?php $sc_preload_img = $page['preload_image'] ?? (($page['id'] ?? '') === 'home' ? asset('images/hero.jpg') : ''); ?>
<?php if ($sc_preload_img !== ''): ?>
<link rel="preload" as="image" href="<?= e($sc_preload_img) ?>" fetchpriority="high">
<?php endif; ?>
<link rel="stylesheet" href="<?= e(asset('css/main.css')) ?>">

<?php require ROOT_DIR . '/includes/tracking_head.php'; ?>
<?php require ROOT_DIR . '/includes/schema.php'; ?>
<script src="<?= e(asset('js/consent.js')) ?>" defer></script>
<script src="<?= e(asset('js/main.js')) ?>" defer></script>
</head>
<body class="page-<?= e($page['id']) ?>">
<?php require ROOT_DIR . '/includes/tracking_body.php'; ?>
<a class="skip-link" href="#main"><?= te('skip_to_content') ?></a>
<?php require ROOT_DIR . '/includes/navbar.php'; ?>
<main id="main">
