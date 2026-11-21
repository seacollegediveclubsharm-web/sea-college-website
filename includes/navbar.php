<?php
/** Top utility bar + main navigation with language & currency switchers. */
declare(strict_types=1);

$sc_nav = [
    ['home', '/', 'nav_home'],
    ['services', '/services', 'nav_services'],
    ['dive-sites', '/dive-sites-sharm-el-sheikh', 'nav_dive_sites'],
    ['prices', '/prices', 'nav_prices'],
    ['gallery', '/gallery', 'nav_gallery'],
    ['about', '/about', 'nav_about'],
    ['contact', '/contact', 'nav_contact'],
];
?>
<div class="topbar">
  <div class="container topbar__inner">
    <span class="topbar__msg">🚐 <?= te('topbar_pickup') ?></span>
    <span class="topbar__links">
      <a href="<?= e(whatsapp_link('', 'topbar')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="topbar">💬 WhatsApp: <bdi dir="ltr"><?= e(BUSINESS['phone_display']) ?></bdi></a>
      <a href="tel:<?= e(BUSINESS['phone']) ?>" data-track="phone_click" class="hide-sm">📞 <bdi dir="ltr"><?= e(BUSINESS['phone_display']) ?></bdi></a>
      <a href="mailto:<?= e(BUSINESS['email']) ?>" data-track="email_click" class="hide-sm"><?= e(BUSINESS['email']) ?></a>
    </span>
  </div>
</div>
<header class="site-header" id="top">
  <div class="container site-header__inner">
    <a class="brand" href="<?= e(page_link('/')) ?>" aria-label="<?= e(SITE_NAME) ?>">
      <?= sc_picture('images/logo.jpg', SITE_NAME . ' logo', 48, 48, ['loading' => 'eager', 'sizes' => '48px']) ?>
      <span class="brand__text"><strong>Sea College</strong><small>Dive Club · Sharm</small></span>
    </a>

    <button class="nav-toggle" type="button" aria-controls="site-nav" aria-expanded="false">
      <span class="nav-toggle__bar"></span><span class="sr-only"><?= te('menu') ?></span>
    </button>

    <nav class="site-nav" id="site-nav" aria-label="Main">
      <ul class="site-nav__list">
        <?php $sc_active_nav = $page['nav_active'] ?? ($page['id'] ?? ''); ?>
        <?php foreach ($sc_nav as [$id, $href, $key]): ?>
          <li><a href="<?= e(page_link($href)) ?>"<?= ($sc_active_nav === $id) ? ' aria-current="page"' : '' ?>><?= te($key) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="site-nav__tools">
        <label class="sr-only" for="lang-switch"><?= te('lang_label') ?></label>
        <select id="lang-switch" class="switcher" data-lang-switch aria-label="<?= te('lang_label') ?>">
          <?php foreach (SUPPORTED_LANGS as $code => $name): ?>
            <option value="<?= e(lang_url($code)) ?>"<?= $code === LANG ? ' selected' : '' ?>><?= e(strtoupper($code) . ' · ' . $name) ?></option>
          <?php endforeach; ?>
        </select>
        <label class="sr-only" for="cur-switch"><?= te('currency_label') ?></label>
        <select id="cur-switch" class="switcher" data-currency-switch aria-label="<?= te('currency_label') ?>">
          <?php foreach (CURRENCY_SYMBOLS as $code => $sym): ?>
            <option value="<?= e($code) ?>"><?= e($sym . ' ' . $code) ?></option>
          <?php endforeach; ?>
        </select>
        <a class="btn btn--wa btn--sm" href="<?= e(whatsapp_link('', 'header')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="header">
          <?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?><span><?= te('cta_whatsapp') ?></span>
        </a>
      </div>
    </nav>
  </div>
</header>
