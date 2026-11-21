<?php
/** Services directory: all 4 categories with live search, compact add-on rows & Category Hub links. */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require ROOT_DIR . '/includes/components.php';
require ROOT_DIR . '/includes/promo_banners.php';
$catalog = require ROOT_DIR . '/data/catalog.php';

$page['id'] = 'services';
$page['canonical_path'] = '/services';
$page['title'] = t('meta_services_title');
$page['description'] = t('meta_services_desc');
$page['image'] = url('assets/' . seo_img('group-boat-dive-red-sea-divers'));
$page['breadcrumbs'] = [[t('breadcrumb_home'), '/'], [t('nav_services'), '/services']];

$intro = ['dive-packages' => 'svc_dive_d', 'padi-courses' => 'svc_courses_d', 'offers' => 'svc_offers_d', 'snorkeling' => 'svc_snorkel_d'];
$fallback = [
    'dive-packages' => seo_img('group-boat-dive-red-sea-divers'),
    'padi-courses' => seo_img('padi-diving-course-students-underwater'),
    'offers' => 'images/courses/05_padi_open_water_diver.jpg',
    'snorkeling' => seo_img('snorkeling-trip-family-red-sea'),
];
$page['schema'][] = [
    '@type' => 'OfferCatalog',
    'name' => t('services_title'),
    'itemListElement' => array_values(array_map(static fn($c) => schema_offer_catalog(loc($c['label']), $c), $catalog)),
];

require ROOT_DIR . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <?= breadcrumbs_html($page['breadcrumbs'], true) ?>
    <h1><?= te('services_title') ?></h1>
    <p><?= te('services_sub') ?></p>
    <nav class="chips" aria-label="<?= te('services_jump') ?>">
      <?php foreach ($catalog as $slug => $cat): ?>
        <a class="chip" href="#<?= e($slug) ?>"><?= e($cat['icon'] . ' ' . loc($cat['label'])) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="filter-bar filter-bar--hero" data-service-filter>
      <input type="search" class="filter-bar__input" data-filter-input placeholder="<?= te('filter_placeholder') ?>" aria-label="<?= te('filter_placeholder') ?>">
      <button type="button" class="btn btn--ghost btn--sm" data-filter-reset hidden><?= te('filter_clear') ?></button>
    </div>
  </div>
</section>

<div class="container promo-wrap-top">
  <?= render_top_promo_banner('bundles', 'services_top_banner') ?>
  <p class="alert" data-filter-empty hidden style="margin-top:1rem"><?= te('filter_empty') ?></p>
</div>

<?php foreach ($catalog as $slug => $cat): ?>
<section class="section<?= in_array($slug, ['padi-courses', 'snorkeling'], true) ? ' section--tint' : '' ?>" id="<?= e($slug) ?>" data-filter-section>
  <div class="container">
    <header class="section__head section__head--row">
      <div>
        <h2><a class="head-link" href="<?= e(hub_url($cat)) ?>"><?= e($cat['icon'] . ' ' . loc($cat['label'])) ?></a></h2>
        <p><?= te($intro[$slug] ?? '') ?></p>
      </div>
      <a class="btn btn--ghost btn--sm" href="<?= e(hub_url($cat)) ?>"><?= te('all_category_items') ?> →</a>
    </header>
    <?php foreach ($cat['groups'] as $g): ?>
      <div class="catalog-group" id="<?= e($g['id']) ?>" data-filter-group>
        <h3 class="group-title"><?= e(loc($g['title'])) ?></h3>
        <?php if (!empty($g['compact'])): ?>
          <div class="crows">
            <?php foreach ($g['items'] as $it): ?><?= compact_item_row($it) ?><?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="cards">
            <?php $sc_svc_idx = ($sc_svc_idx ?? 0); foreach ($g['items'] as $it): ?><?= product_card($it, $fallback[$slug], ($sc_svc_idx++) < 3) ?><?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <?php $notes = $cat['notes'][LANG] ?? $cat['notes']['en'] ?? []; if ($notes): ?>
      <ul class="notes"><?php foreach ($notes as $n): ?><li><?= e($n) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
  </div>
</section>
<?php endforeach; ?>

<section class="cta-band">
  <div class="container cta-band__inner">
    <div><h2><?= te('cta_band_title') ?></h2><p><?= te('prices_disclaimer') ?></p></div>
    <div class="cta-band__btns">
      <a class="btn btn--primary btn--lg" href="<?= e(page_link('/prices')) ?>" data-track="cta_click" data-placement="services_prices"><?= te('nav_prices') ?></a>
      <a class="btn btn--wa btn--lg" href="<?= e(whatsapp_link('', 'services')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="services"><?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?><span><?= te('cta_whatsapp') ?></span></a>
    </div>
  </div>
</section>
<?php require ROOT_DIR . '/includes/footer.php';
