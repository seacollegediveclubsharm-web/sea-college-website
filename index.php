<?php
/** Home page. */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require ROOT_DIR . '/includes/components.php';
$catalog = require ROOT_DIR . '/data/catalog.php';

$page['id'] = 'home';
$page['title'] = t('meta_home_title');
$page['description'] = t('meta_home_desc');
$page['schema'] = [schema_faq([1, 2, 3, 4, 5, 6])];

$explore = [
    ['intro', page_link('/services.php', 'dive-packages'), seo_img('discover-scuba-diving-intro-dive-red-sea')],
    ['courses', page_link('/services.php', 'padi-courses'), seo_img('padi-diving-course-students-underwater')],
    ['dives', page_link('/services.php', 'dive-packages'), seo_img('group-boat-dive-red-sea-divers')],
    ['bundles', page_link('/services.php', 'offers'), 'images/courses/05_padi_open_water_diver.jpg'],
    ['snorkel', page_link('/services.php', 'snorkeling'), seo_img('snorkeling-trip-family-red-sea')],
    ['private', page_link('/services.php', 'snorkeling'), seo_img('private-yacht-boat-trip')],
    ['wreck', page_link('/services.php', 'dive-packages'), seo_img('ss-thistlegorm-wreck-dive')],
    ['kids', page_link('/services.php', 'padi-courses'), 'images/courses/01_padi_bubblemaker.jpg'],
];
$popular = catalog_pick($catalog, ['dsd-quick-reef', 'dsd-2-boat', 'dive-2', 'owd', 'aowd', 'bubblemaker', 'snork-ras-adult', 'speed-2h', 'private-6-10', 'bundle-mega', 'sup-thistlegorm']);
$popular = array_slice($popular, 0, 8);

require ROOT_DIR . '/includes/header.php';
?>
<section class="hero" style="--hero:url('<?= e(asset('images/hero.jpg')) ?>')">
  <div class="container hero__inner">
    <p class="eyebrow"><?= te('hero_eyebrow') ?></p>
    <h1><?= te('hero_title') ?></h1>
    <p class="hero__sub"><?= te('hero_sub') ?></p>
    <div class="hero__ctas">
      <a class="btn btn--wa btn--lg" href="<?= e(whatsapp_link('', 'hero')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="hero"><?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?><span><?= te('hero_cta_primary') ?></span></a>
      <a class="btn btn--ghost btn--lg" href="<?= e(page_link('/prices.php')) ?>" data-track="cta_click" data-placement="hero_prices"><?= te('hero_cta_secondary') ?></a>
    </div>
    <ul class="trust">
      <?php for ($i = 1; $i <= 4; $i++): ?><li>✓ <?= te("trust_$i") ?></li><?php endfor; ?>
    </ul>
  </div>
</section>

<section class="kpis" aria-label="Key facts">
  <div class="container kpis__grid">
    <div class="kpi"><strong><bdi dir="ltr"><?= e((string) BUSINESS['rating']) ?>★</bdi></strong><span><?= te('kpi_rating') ?></span></div>
    <div class="kpi"><strong><bdi dir="ltr"><?= e(number_format((int) BUSINESS['review_count'])) ?></bdi></strong><span><?= te('kpi_reviews') ?></span></div>
    <div class="kpi"><strong><?= te('kpi_padi') ?></strong><span><?= te('kpi_padi_sub') ?></span></div>
    <div class="kpi"><strong><?= price_html(30) ?></strong><span><?= te('kpi_from') ?></span></div>
  </div>
</section>

<section class="section">
  <div class="container">
    <?= section_head('explore_title', 'explore_sub') ?>
    <div class="explore">
      <?php foreach ($explore as [$key, $href, $img]): ?>
        <a class="explore__card" href="<?= e($href) ?>" data-track="cta_click" data-placement="explore_<?= e($key) ?>">
          <img src="<?= e(asset($img)) ?>" alt="<?= te("exp_{$key}_t") ?> — <?= te('location_sharm') ?>" loading="lazy" width="400" height="300">
          <span class="explore__text"><strong><?= te("exp_{$key}_t") ?></strong><small><?= te("exp_{$key}_d") ?></small></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--tint">
  <div class="container">
    <?= section_head('popular_title', 'popular_sub') ?>
    <div class="cards">
      <?php foreach ($popular as $it): ?><?= product_card($it, seo_img('group-boat-dive-red-sea-divers')) ?><?php endforeach; ?>
    </div>
    <p class="center"><a class="btn btn--primary" href="<?= e(page_link('/prices.php')) ?>" data-track="cta_click" data-placement="popular_all"><?= te('cta_view_prices') ?></a></p>
  </div>
</section>

<section class="section">
  <div class="container">
    <?= section_head('why_title') ?>
    <div class="why">
      <?php $icons = ['🏅', '🏊', '🗣️', '🧾', '🚐', '🫧'];
      for ($i = 1; $i <= 6; $i++): ?>
        <div class="why__item card"><span class="why__icon" aria-hidden="true"><?= $icons[$i - 1] ?></span><h3><?= te("why_{$i}_t") ?></h3><p><?= te("why_{$i}_d") ?></p></div>
      <?php endfor; ?>
    </div>
  </div>
</section>

<section class="section section--dark">
  <div class="container">
    <?= section_head('reviews_title', 'reviews_sub') ?>
    <div class="reviews">
      <?php for ($i = 1; $i <= 4; $i++): ?>
        <figure class="review card">
          <div class="review__stars" aria-label="5/5">★★★★★</div>
          <blockquote><p><?= te("rev_{$i}_text") ?></p></blockquote>
          <figcaption><?= te("rev_{$i}_name") ?> · Google</figcaption>
        </figure>
      <?php endfor; ?>
    </div>
    <p class="center"><a class="btn btn--ghost" href="<?= e(GOOGLE_REVIEWS_URL) ?>" target="_blank" rel="noopener"><?= te('see_all_reviews') ?></a></p>
  </div>
</section>

<section class="section">
  <div class="container container--narrow">
    <?= section_head('faq_title') ?>
    <?= faq_list([1, 2, 3, 4, 5, 6]) ?>
  </div>
</section>

<section class="cta-band">
  <div class="container cta-band__inner">
    <div><h2><?= te('cta_band_title') ?></h2><p><?= te('cta_band_sub') ?></p></div>
    <div class="cta-band__btns">
      <a class="btn btn--wa btn--lg" href="<?= e(whatsapp_link('', 'cta_band')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="cta_band"><?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?><span><?= te('cta_whatsapp') ?></span></a>
      <a class="btn btn--ghost btn--lg" href="<?= e(page_link('/contact.php')) ?>" data-track="cta_click" data-placement="cta_band_contact"><?= te('cta_book') ?></a>
    </div>
  </div>
</section>
<?php require ROOT_DIR . '/includes/footer.php';
