<?php
/**
 * Underwater Photo & Video Lightbox Gallery + Grouped Fleet Albums (Oceano, Nizar, Omar 4, Speedboats)
 * + Physical Sea College Dive Club Center Showcase + Named Instructor Team Showcase.
 * Clean URL: /gallery
 */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require ROOT_DIR . '/includes/interactive_widgets.php';
require ROOT_DIR . '/includes/promo_banners.php';

$catalog = require ROOT_DIR . '/data/catalog.php';
$flat    = catalog_all_items($catalog);
$galData = require ROOT_DIR . '/data/gallery_team.php';
$galUi   = $galData['ui'];

$page['id']             = 'gallery';
$page['nav_active']     = 'gallery';
$page['canonical_path'] = '/gallery';
$page['title']          = t('meta_gallery_title');
$page['description']    = t('meta_gallery_desc');
$page['image']          = url('assets/images/fleet/sea-college-oceano-yacht-01-exterior-nov2026.jpg');
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

/** Render a single <figure data-gal-item> with sequential index for Lightbox navigation. */
$galIdx = 0;
$renderGalFigure = static function (array $m) use (&$galIdx, $flat): string {
    $idx       = $galIdx++;
    $isVid     = ($m['type'] ?? 'photo') === 'video';
    $thumb     = $isVid ? $m['poster'] : $m['src'];
    $linked    = isset($m['item_id'], $flat[$m['item_id']]) ? $flat[$m['item_id']] : null;
    $itemHref  = $linked ? item_url($linked) : page_link('/prices.php');
    $itemLabel = $linked ? (item_name($linked) . ' (' . money($linked['price']) . ')') : t('cta_view_prices');
    $title     = loc($m['title']);
    $caption   = loc($m['caption']);

    ob_start(); ?>
    <figure class="gal-item"
            data-gal-item
            data-gal-index="<?= $idx ?>"
            data-gal-cat="<?= e($m['cat']) ?>"
            data-gal-type="<?= e($m['type'] ?? 'photo') ?>"
            data-gal-src="<?= e(asset($m['src'])) ?>"
            data-gal-poster="<?= e($isVid ? asset($m['poster']) : '') ?>"
            data-gal-title="<?= e($title) ?>"
            data-gal-caption="<?= e($caption) ?>"
            data-gal-link="<?= e($itemHref) ?>"
            data-gal-link-text="<?= e($itemLabel) ?>">
      <button type="button" class="gal-item__btn" data-gal-open="<?= $idx ?>" aria-label="<?= e($title) ?>">
        <img src="<?= e(asset($thumb)) ?>" alt="<?= e($title) ?> — <?= e(SITE_NAME) ?>" loading="<?= $idx < 4 ? 'eager' : 'lazy' ?>" width="480" height="360">
        <?php if ($isVid): ?>
          <span class="gal-item__play" aria-hidden="true">▶ <small><?= e($m['dur'] ?? 'HD') ?></small></span>
        <?php endif; ?>
        <span class="gal-item__overlay">
          <strong dir="auto"><?= e($title) ?></strong>
          <small dir="auto"><?= e($caption) ?></small>
        </span>
      </button>
    </figure>
    <?php
    return (string) ob_get_clean();
};

require ROOT_DIR . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <?= breadcrumbs_html($page['breadcrumbs'], true) ?>
    <p class="eyebrow">📸 <?= te('gallery_eyebrow') ?></p>
    <h1><?= te('gallery_title') ?></h1>
    <p class="hero__sub"><?= te('gallery_sub') ?></p>
    <nav class="chips" aria-label="<?= te('services_jump') ?>">
      <a class="chip" href="#fleet">🛥️ <?= e(loc($galUi['jump_fleet'])) ?></a>
      <a class="chip" href="#dive-center">🏛️ <?= e(loc($galUi['jump_center'])) ?></a>
      <a class="chip" href="#underwater-media">🎬 <?= te('gallery_jump_media') ?></a>
      <a class="chip" href="#instructors">🤿 <?= te('gallery_jump_team') ?></a>
    </nav>
  </div>
</section>

<section class="section" id="media-grid">
  <div class="container" data-gallery>
    <?= render_top_promo_banner('boats', 'gallery_top_banner') ?>

    <div class="zone-bar" role="toolbar" aria-label="<?= te('gallery_filter_label') ?>">
      <?php foreach ($galData['filters'] as $fKey => $fLabel): ?>
        <button type="button" class="zone-btn<?= $fKey === 'all' ? ' is-active' : '' ?>" data-gal-filter="<?= e($fKey) ?>" aria-pressed="<?= $fKey === 'all' ? 'true' : 'false' ?>"><?= e(loc($fLabel)) ?></button>
      <?php endforeach; ?>
    </div>

    <div class="fleet-stack">
      <?php
      $fleetIntroShown = false;
      foreach ($galData['collections'] as $col):
          $isCenter  = $col['cat'] === 'center';
          $colLinked = isset($col['item_id'], $flat[$col['item_id']]) ? $flat[$col['item_id']] : null;
          $colHref   = $isCenter ? page_link('/about.php', 'dive-center') : ($colLinked ? item_url($colLinked) : page_link('/snorkeling-and-boats-sharm-el-sheikh'));
          $colBtnTxt = $isCenter ? t('nav_about') : ($colLinked ? (item_name($colLinked) . ' · ' . money($colLinked['price'])) : t('cta_details'));
          $anchorId  = $isCenter ? 'dive-center' : (!$fleetIntroShown ? 'fleet' : 'boat-' . $col['id']);
          if (!$isCenter) {
              $fleetIntroShown = true;
          }
          $pCount = count($col['photos']);
          $gridMod = ($pCount === 4 || $pCount === 8) ? ' gal-grid--4' : '';
      ?>
        <article class="fleet-group<?= $isCenter ? ' fleet-group--center' : '' ?>" id="<?= e($anchorId) ?>" data-gal-group>
          <header class="fleet-group__head">
            <div>
              <div class="fleet-group__badges">
                <span class="fleet-group__badge"><?= e($col['icon']) ?> <span dir="auto"><?= e(loc($col['badge'])) ?></span></span>
                <span class="fleet-group__count"><bdi dir="ltr"><?= $pCount ?></bdi>&nbsp;<span dir="auto"><?= e(loc($galUi['photos_count_unit'])) ?></span></span>
              </div>
              <h2 class="fleet-group__title" dir="auto"><?= e(loc($col['title'])) ?></h2>
              <p class="fleet-group__desc" dir="auto"><?= e(loc($col['desc'])) ?></p>
            </div>
            <div class="fleet-group__cta">
              <a class="btn btn--detail btn--sm" href="<?= e($colHref) ?>" dir="auto"><?= e($colBtnTxt) ?> →</a>
            </div>
          </header>
          <div class="gal-grid<?= $gridMod ?>">
            <?php foreach ($col['photos'] as $ph): ?>
              <?= $renderGalFigure($ph) ?>
            <?php endforeach; ?>
          </div>
        </article>
      <?php endforeach; ?>

      <div class="fleet-group fleet-group--plain" id="underwater-media" data-gal-group>
        <header class="fleet-group__head">
          <div>
            <h2 class="fleet-group__title" dir="auto">🎬 <?= e(loc($galUi['other_media_title'])) ?></h2>
          </div>
        </header>
        <div class="gal-grid">
          <?php foreach ($galData['media'] as $m): ?>
            <?= $renderGalFigure($m) ?>
          <?php endforeach; ?>
        </div>
      </div>
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
