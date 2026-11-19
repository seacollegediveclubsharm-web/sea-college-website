<?php
/**
 * Site-wide Internal Promo, Discount, Announcement & Brand Banners.
 *
 * Provides two responsive, RTL/CJK-ready, zero-language-drop components:
 * 1. render_top_promo_banner($theme): Horizontal campaign bar inside .container at top of pages/categories.
 * 2. render_side_promo_banner($theme): Vertical campaign & Sea College brand card inside sidebars (item.php, prices.php, contact.php).
 */
declare(strict_types=1);

require_once __DIR__ . '/icons.php';

function sc_promo_banner_config(string $theme): array
{
    return match ($theme) {
        'dives' => [
            'theme' => 'dives',
            'badge' => t('banner_dives_badge'),
            'title' => t('banner_dives_title'),
            'desc'  => t('banner_dives_desc'),
            'cta'   => t('banner_dives_cta'),
            'href'  => page_link('/prices.php', 'dive-calculator'),
            'img'   => 'images/sea-college-dive-club-sharm-el-sheikh-group-boat-dive-red-sea-divers.jpg',
            'wa'    => 'Certified Dive Package (€35/dive)',
        ],
        'intro' => [
            'theme' => 'intro',
            'badge' => t('banner_intro_badge'),
            'title' => t('banner_intro_title'),
            'desc'  => t('banner_intro_desc'),
            'cta'   => t('banner_intro_cta'),
            'href'  => page_link('/tours/quick-discover-scuba-diving-house-reef'),
            'img'   => 'images/sea-college-dive-club-sharm-el-sheikh-discover-scuba-diving-intro-dive-red-sea.jpg',
            'wa'    => 'Beginner Discover Scuba Diving',
        ],
        'boats' => [
            'theme' => 'boats',
            'badge' => t('banner_boats_badge'),
            'title' => t('banner_boats_title'),
            'desc'  => t('banner_boats_desc'),
            'cta'   => t('banner_boats_cta'),
            'href'  => page_link('/snorkeling-and-boats-sharm-el-sheikh'),
            'img'   => 'images/sea-college-dive-club-sharm-el-sheikh-oceano-private-yacht-nov2026.jpg',
            'wa'    => 'Private Boat / Snorkeling Trip',
        ],
        'brand' => [
            'theme' => 'brand',
            'badge' => t('banner_brand_badge'),
            'title' => t('banner_brand_title'),
            'desc'  => t('banner_brand_desc'),
            'cta'   => t('banner_brand_cta'),
            'href'  => whatsapp_link('', 'brand_banner'),
            'ext'   => true,
            'img'   => 'images/clients/sea-college-real-guests-padi-certificates-sharm.jpg',
            'wa'    => 'Direct Booking Inquiry',
        ],
        default => [
            'theme' => 'bundles',
            'badge' => t('banner_bundles_badge'),
            'title' => t('banner_bundles_title'),
            'desc'  => t('banner_bundles_desc'),
            'cta'   => t('banner_bundles_cta'),
            'href'  => page_link('/padi-course-bundles-sharm-el-sheikh'),
            'img'   => 'images/sea-college-dive-club-sharm-el-sheikh-padi-diving-course-students-underwater.jpg',
            'wa'    => 'PADI Course Saver Bundle (Save up to €120)',
        ],
    };
}

function render_top_promo_banner(string $theme = 'bundles', string $placement = 'top_banner'): string
{
    $cfg = sc_promo_banner_config($theme);
    $isExt = !empty($cfg['ext']);
    $targetAttrs = $isExt ? ' target="_blank" rel="noopener" data-track="whatsapp_click"' : ' data-track="cta_click"';

    ob_start(); ?>
    <aside class="promo-banner promo-banner--top promo-banner--<?= e($cfg['theme']) ?> no-print" aria-label="<?= e($cfg['badge']) ?>">
      <div class="promo-banner__media">
        <img src="<?= e(asset($cfg['img'])) ?>" alt="<?= e($cfg['title']) ?>" width="240" height="180" loading="lazy">
        <span class="promo-banner__logo-chip">
          <img src="<?= e(asset('images/logo.jpg')) ?>" alt="" width="26" height="26" loading="lazy">
          <span>PADI 5★</span>
        </span>
      </div>
      <div class="promo-banner__body">
        <span class="promo-banner__badge"><?= e($cfg['badge']) ?></span>
        <h2 class="promo-banner__title" dir="auto"><?= e($cfg['title']) ?></h2>
        <p class="promo-banner__desc" dir="auto"><?= e($cfg['desc']) ?></p>
      </div>
      <div class="promo-banner__actions">
        <a class="btn btn--primary" href="<?= e($cfg['href']) ?>"<?= $targetAttrs ?> data-placement="<?= e($placement) ?>"><?= e($cfg['cta']) ?> →</a>
        <?php if (!$isExt): ?>
        <a class="btn btn--wa btn--sm" href="<?= e(whatsapp_link($cfg['wa'], $placement . '_wa')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="<?= e($placement) ?>_wa">
          <?= brand_icon('whatsapp', 16) ?><span><?= te('ask_whatsapp') ?></span>
        </a>
        <?php endif; ?>
      </div>
    </aside>
    <?php
    return (string) ob_get_clean();
}

function render_side_promo_banner(string $theme = 'bundles', string $placement = 'side_banner'): string
{
    $cfg = sc_promo_banner_config($theme);
    $isExt = !empty($cfg['ext']);
    $targetAttrs = $isExt ? ' target="_blank" rel="noopener" data-track="whatsapp_click"' : ' data-track="cta_click"';

    ob_start(); ?>
    <aside class="promo-banner promo-banner--side promo-banner--<?= e($cfg['theme']) ?> card no-print" aria-label="<?= e($cfg['badge']) ?>">
      <div class="promo-side__brand">
        <img src="<?= e(asset('images/logo.jpg')) ?>" alt="<?= e(SITE_NAME) ?>" width="34" height="34" loading="lazy">
        <div>
          <strong><?= e(SITE_NAME) ?></strong>
          <small><?= te('padi_badge') ?></small>
        </div>
      </div>
      <div class="promo-side__thumb">
        <img src="<?= e(asset($cfg['img'])) ?>" alt="<?= e($cfg['title']) ?>" width="360" height="220" loading="lazy">
        <span class="promo-banner__badge"><?= e($cfg['badge']) ?></span>
      </div>
      <h3 class="promo-side__title" dir="auto"><?= e($cfg['title']) ?></h3>
      <p class="promo-side__desc" dir="auto"><?= e($cfg['desc']) ?></p>
      <ul class="promo-side__perks">
        <li dir="auto">✓ <?= te('side_promo_1') ?></li>
        <li dir="auto">✓ <?= te('side_promo_2') ?></li>
        <li dir="auto">✓ <?= te('side_promo_3') ?></li>
      </ul>
      <a class="btn btn--primary btn--block" href="<?= e($cfg['href']) ?>"<?= $targetAttrs ?> data-placement="<?= e($placement) ?>"><?= e($cfg['cta']) ?> →</a>
      <div class="promo-side__socials">
        <?php foreach (['youtube', 'tripadvisor', 'instagram', 'facebook', 'tiktok'] as $net):
            $sHref = ($net === 'tripadvisor') ? (function_exists('ta_profile_url') ? ta_profile_url() : TRIPADVISOR_URL) : (SOCIAL[$net] ?? '');
            if ($sHref === '') { continue; }
        ?>
          <a href="<?= e($sHref) ?>" target="_blank" rel="noopener" data-track="outbound_link" aria-label="<?= e(social_label($net)) ?>" title="<?= e(social_label($net)) ?>">
            <?= brand_icon($net, 18) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </aside>
    <?php
    return (string) ob_get_clean();
}
