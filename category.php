<?php
/**
 * Dedicated SEO + AEO Category Hub page for each of the 4 core verticals:
 * - /diving-trips-sharm-el-sheikh
 * - /diving-courses-sharm-el-sheikh
 * - /padi-course-bundles-sharm-el-sheikh
 * - /snorkeling-and-boats-sharm-el-sheikh
 */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require ROOT_DIR . '/includes/interactive_widgets.php';
require ROOT_DIR . '/includes/promo_banners.php';

$catalog = require ROOT_DIR . '/data/catalog.php';
$seoAll  = require ROOT_DIR . '/data/items_seo.php';

$reqHub = strtolower(trim((string) ($_GET['hub'] ?? ''), '/'));
$found  = $reqHub !== '' ? catalog_find_hub($catalog, $reqHub) : null;

if (!$found) {
    require ROOT_DIR . '/404.php';
    exit;
}

$catKey  = $found['key'];
$cat     = $found['cat'];
$hubSeo  = $seoAll['hubs'][$catKey] ?? [];
$hubPath = hub_path($cat);

$fallback = [
    'dive-packages' => seo_img('group-boat-dive-red-sea-divers'),
    'padi-courses'  => seo_img('padi-diving-course-students-underwater'),
    'offers'        => 'images/courses/05_padi_open_water_diver.jpg',
    'snorkeling'    => seo_img('snorkeling-trip-family-red-sea'),
];
$heroImg = $fallback[$catKey] ?? 'images/hero.jpg';

$h1       = loc($hubSeo['h1'] ?? null) ?: loc($cat['label']);
$sub      = loc($hubSeo['sub'] ?? null);
$aeoText  = loc($hubSeo['aeo_answer'] ?? null);
$hiList   = $hubSeo['highlights'][LANG] ?? $hubSeo['highlights']['en'] ?? [];
$faqs     = sc_resolve_faqs($hubSeo['faqs'] ?? [], $hubSeo['faqs_i18n'] ?? null);
$notes    = $cat['notes'][LANG] ?? $cat['notes']['en'] ?? [];

$page['id']             = 'hub-' . $catKey;
$page['nav_active']     = 'services';
$page['canonical_path'] = $hubPath;
$page['title']          = loc($hubSeo['meta_title'] ?? null) ?: ($h1 . ' | ' . SITE_NAME);
$page['description']    = loc($hubSeo['meta_desc'] ?? null) ?: $sub;
$page['image']          = url('assets/' . $heroImg);
$page['breadcrumbs']    = [
    [t('breadcrumb_home'), '/'],
    [t('nav_services'), '/services'],
    [loc($cat['label']), $hubPath],
];
$page['speakable']      = ['.page-hero h1', '.aeo-box__text'];
$page['schema'][]       = schema_offer_catalog($h1, $cat);
if ($faqs) {
    $page['schema'][] = schema_faq_pairs($faqs);
}

require ROOT_DIR . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <?= breadcrumbs_html($page['breadcrumbs'], true) ?>
    <p class="eyebrow"><?= e($cat['icon']) ?> <?= te('padi_badge') ?></p>
    <h1 dir="auto"><?= e($h1) ?></h1>
    <?php if ($sub !== ''): ?><p class="hero__sub" dir="auto"><?= e($sub) ?></p><?php endif; ?>
    <?php if (count($cat['groups']) > 1): ?>
    <nav class="chips" aria-label="<?= te('services_jump') ?>">
      <?php foreach ($cat['groups'] as $g): ?>
        <a class="chip" href="#<?= e($g['id']) ?>"><?= e(loc($g['title'])) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>
  </div>
</section>

<section class="section">
  <div class="container">
    <?php
    $hubBannerTheme = match ($catKey) {
        'dive-packages' => 'dives',
        'padi-courses'  => 'bundles',
        'offers'        => 'brand',
        'snorkeling'    => 'boats',
        default         => 'bundles',
    };
    echo render_top_promo_banner($hubBannerTheme, 'hub_' . $catKey . '_banner');
    ?>
    <?php if ($aeoText !== ''): ?>
    <aside class="aeo-box" id="quick-answer" aria-labelledby="hub-aeo-heading">
      <h2 id="hub-aeo-heading" class="aeo-box__label"><?= te('quick_answer_label') ?></h2>
      <p class="aeo-box__text" dir="auto"><?= e($aeoText) ?></p>
    </aside>
    <?php endif; ?>

    <?php if ($hiList): ?>
    <div class="hub-highlights card">
      <h2><?= te('hub_highlights_title') ?></h2>
      <ul class="ticks">
        <?php foreach ($hiList as $h): ?><li dir="auto"><?= e($h) ?></li><?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <?php if ($catKey === 'dive-packages'): ?>
      <?= render_budget_calculator($catalog, '#booking') ?>
    <?php elseif ($catKey === 'padi-courses' || $catKey === 'offers'): ?>
      <?= render_dive_quiz($catalog) ?>
    <?php endif; ?>

    <div class="filter-bar" data-service-filter>
      <input type="search" class="filter-bar__input" data-filter-input placeholder="<?= te('filter_placeholder') ?>" aria-label="<?= te('filter_placeholder') ?>">
      <button type="button" class="btn btn--ghost btn--sm" data-filter-reset hidden><?= te('filter_clear') ?></button>
    </div>
    <p class="alert" data-filter-empty hidden><?= te('filter_empty') ?></p>

    <?php foreach ($cat['groups'] as $g): ?>
      <div class="catalog-group" id="<?= e($g['id']) ?>" data-filter-group>
        <h2 class="group-title"><?= e(loc($g['title'])) ?></h2>
        <?php if (!empty($g['compact'])): ?>
          <div class="crows">
            <?php foreach ($g['items'] as $it): ?><?= compact_item_row($it) ?><?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="cards">
            <?php $sc_cat_idx = ($sc_cat_idx ?? 0); foreach ($g['items'] as $it): ?><?= product_card($it, $heroImg, ($sc_cat_idx++) < 3) ?><?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <?php if ($notes): ?>
      <div class="item-block">
        <h2><?= te('hub_rules_title') ?></h2>
        <ul class="notes"><?php foreach ($notes as $n): ?><li dir="auto"><?= e($n) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php if ($faqs): ?>
<section class="section section--tint" id="faq">
  <div class="container container--narrow">
    <?= section_head('faq_title') ?>
    <?= faq_pairs_html($faqs) ?>
  </div>
</section>
<?php endif; ?>

<section class="section no-print" id="booking">
  <div class="container container--narrow">
    <h2><?= te('form_title_booking') ?></h2>
    <p><?= te('instant_confirm_note') ?></p>
    <?php $form_id = 'booking'; $form_interest_default = ''; include ROOT_DIR . '/includes/lead_form.php'; ?>
  </div>
</section>
<?php require ROOT_DIR . '/includes/footer.php';
