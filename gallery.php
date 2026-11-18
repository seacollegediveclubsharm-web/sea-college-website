<?php
/**
 * Underwater Photo & Video Lightbox Gallery + Named Instructor Team Showcase.
 * Clean URL: /gallery
 */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require ROOT_DIR . '/includes/interactive_widgets.php';

$catalog = require ROOT_DIR . '/data/catalog.php';
$flat    = catalog_all_items($catalog);
$galData = require ROOT_DIR . '/data/gallery_team.php';

$page['id']             = 'gallery';
$page['nav_active']     = 'gallery';
$page['canonical_path'] = '/gallery';
$page['title']          = t('meta_gallery_title');
$page['description']    = t('meta_gallery_desc');
$page['image']          = url('assets/images/gallery/sea-college-dive-club-sharm-el-sheikh-divers-yacht-deck-red-sea.jpg');
$page['breadcrumbs']    = [
    [t('breadcrumb_home'), '/'],
    [t('nav_gallery'), '/gallery'],
];

$videosSchema = [];
foreach ($galData['media'] as $m) {
    if ($m['type'] === 'video') {
        $videosSchema[] = [
            '@type'        => 'VideoObject',
            'name'         => loc($m['title']),
            'description'  => loc($m['caption']),
            'thumbnailUrl' => url('assets/' . $m['poster']),
            'contentUrl'   => url('assets/' . $m['src']),
            'uploadDate'   => '2026-01-15',
            'publisher'    => ['@id' => SITE_URL . '/#organization'],
        ];
    }
}
$page['schema'][] = [
    '@type'       => 'ImageGallery',
    'name'        => t('gallery_title'),
    'description' => t('gallery_sub'),
    'about'       => ['@id' => SITE_URL . '/#organization'],
    'hasPart'     => $videosSchema,
];

require ROOT_DIR . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <?= breadcrumbs_html($page['breadcrumbs'], true) ?>
    <p class="eyebrow">📸 <?= te('gallery_eyebrow') ?></p>
    <h1><?= te('gallery_title') ?></h1>
    <p class="hero__sub"><?= te('gallery_sub') ?></p>
    <nav class="chips" aria-label="<?= te('services_jump') ?>">
      <a class="chip" href="#media-grid">🎬 <?= te('gallery_jump_media') ?></a>
      <a class="chip" href="#instructors">🤿 <?= te('gallery_jump_team') ?></a>
    </nav>
  </div>
</section>

<section class="section" id="media-grid">
  <div class="container" data-gallery>
    <div class="zone-bar" role="toolbar" aria-label="<?= te('gallery_filter_label') ?>">
      <?php foreach ($galData['filters'] as $fKey => $fLabel): ?>
        <button type="button" class="zone-btn<?= $fKey === 'all' ? ' is-active' : '' ?>" data-gal-filter="<?= e($fKey) ?>" aria-pressed="<?= $fKey === 'all' ? 'true' : 'false' ?>"><?= e(loc($fLabel)) ?></button>
      <?php endforeach; ?>
    </div>

    <div class="gal-grid">
      <?php foreach ($galData['media'] as $idx => $m):
          $isVid = $m['type'] === 'video';
          $thumb = $isVid ? $m['poster'] : $m['src'];
          $linked = isset($flat[$m['item_id']]) ? $flat[$m['item_id']] : null;
          $itemHref = $linked ? item_url($linked) : page_link('/prices.php');
          $itemLabel = $linked ? (item_name($linked) . ' (' . money($linked['price']) . ')') : t('cta_view_prices');
      ?>
        <figure class="gal-item"
                data-gal-item
                data-gal-index="<?= $idx ?>"
                data-gal-cat="<?= e($m['cat']) ?>"
                data-gal-type="<?= e($m['type']) ?>"
                data-gal-src="<?= e(asset($m['src'])) ?>"
                data-gal-poster="<?= e($isVid ? asset($m['poster']) : '') ?>"
                data-gal-title="<?= e(loc($m['title'])) ?>"
                data-gal-caption="<?= e(loc($m['caption'])) ?>"
                data-gal-link="<?= e($itemHref) ?>"
                data-gal-link-text="<?= e($itemLabel) ?>">
          <button type="button" class="gal-item__btn" data-gal-open="<?= $idx ?>" aria-label="<?= e(loc($m['title'])) ?>">
            <img src="<?= e(asset($thumb)) ?>" alt="<?= e(loc($m['title'])) ?> — <?= e(SITE_NAME) ?>" loading="<?= $idx < 4 ? 'eager' : 'lazy' ?>" width="480" height="360">
            <?php if ($isVid): ?>
              <span class="gal-item__play" aria-hidden="true">▶ <small><?= e($m['dur'] ?? 'HD') ?></small></span>
            <?php endif; ?>
            <span class="gal-item__overlay">
              <strong dir="auto"><?= e(loc($m['title'])) ?></strong>
              <small dir="auto"><?= e(loc($m['caption'])) ?></small>
            </span>
          </button>
        </figure>
      <?php endforeach; ?>
    </div>

    <!-- Accessible Lightbox Modal -->
    <dialog class="lightbox" data-lightbox aria-label="<?= te('gallery_title') ?>">
      <div class="lightbox__inner">
        <button type="button" class="lightbox__close" data-lb-close aria-label="<?= te('close') ?>">✕</button>
        <div class="lightbox__stage">
          <button type="button" class="lightbox__nav lightbox__nav--prev" data-lb-prev aria-label="<?= te('gallery_prev') ?>">‹</button>
          <div class="lightbox__media" data-lb-media></div>
          <button type="button" class="lightbox__nav lightbox__nav--next" data-lb-next aria-label="<?= te('gallery_next') ?>">›</button>
        </div>
        <div class="lightbox__foot">
          <div>
            <h3 class="lightbox__title" data-lb-title dir="auto"></h3>
            <p class="lightbox__cap" data-lb-cap dir="auto"></p>
          </div>
          <a class="btn btn--primary btn--sm" href="#" data-lb-link></a>
        </div>
      </div>
    </dialog>
  </div>
</section>

<section class="section section--tint" id="instructors">
  <div class="container">
    <header class="section__head section__head--row">
      <div>
        <span class="eyebrow">⭐ <?= te('rating_badge', ['rating' => BUSINESS['rating'], 'count' => number_format((int) BUSINESS['review_count'])]) ?></span>
        <h2><?= te('team_showcase_title') ?></h2>
        <p><?= te('team_showcase_sub') ?></p>
      </div>
      <a class="btn btn--ghost btn--sm" href="<?= e(GOOGLE_REVIEWS_URL) ?>" target="_blank" rel="noopener" data-track="outbound_link"><?= te('see_all_reviews') ?> ↗</a>
    </header>
    <?= render_team_showcase($galData['team']) ?>
  </div>
</section>

<section class="cta-band">
  <div class="container cta-band__inner">
    <div><h2><?= te('cta_band_title') ?></h2><p><?= te('cta_band_sub') ?></p></div>
    <div class="cta-band__btns">
      <a class="btn btn--wa btn--lg" href="<?= e(whatsapp_link('Dive / Snorkel Booking', 'gallery_cta')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="gallery_cta"><?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?><span><?= te('cta_whatsapp') ?></span></a>
      <a class="btn btn--ghost btn--lg" href="<?= e(page_link('/prices.php')) ?>" data-track="cta_click" data-placement="gallery_prices"><?= te('cta_view_prices') ?></a>
    </div>
  </div>
</section>
<?php require ROOT_DIR . '/includes/footer.php';
