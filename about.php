<?php
/** About us — Physical Dive Center at Cyrene Sharm (Al Montazah), Facilities, Named Team & Accreditations. */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require ROOT_DIR . '/includes/interactive_widgets.php';
require ROOT_DIR . '/includes/promo_banners.php';
$catalog = require ROOT_DIR . '/data/catalog.php';
$flat    = catalog_all_items($catalog);
$galData = require ROOT_DIR . '/data/gallery_team.php';
$galUi   = $galData['ui'];

$centerCol = null;
foreach ($galData['collections'] as $col) {
    if ($col['id'] === 'dive-center') {
        $centerCol = $col;
        break;
    }
}

$page['id'] = 'about';
$page['canonical_path'] = '/about.php';
$page['title'] = t('meta_about_title');
$page['description'] = t('meta_about_desc');
$page['image'] = url('assets/images/center/sea-college-dive-club-center-01-entrance-al-montazah-nov2026.jpg');
$page['breadcrumbs'] = [[t('breadcrumb_home'), '/'], [t('nav_about'), '/about.php']];
$page['schema'][] = ['@type' => 'AboutPage', 'name' => t('about_title'), 'about' => ['@id' => SITE_URL . '/#organization']];

require ROOT_DIR . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <?= breadcrumbs_html($page['breadcrumbs'], true) ?>
    <h1><?= te('about_title') ?></h1>
    <p><?= te('about_lead') ?></p>
  </div>
</section>

<section class="section">
  <div class="container">
    <?= render_top_promo_banner('brand', 'about_top_banner') ?>
    <div class="split">
      <div class="split__text">
        <p><?= te('about_p1') ?></p>
        <p><?= te('about_p2') ?></p>
        <?php $aboutTa = ta_reviews_data(); ?>
        <ul class="chips">
          <li class="chip"><?= brand_icon('padi', 16) ?> <span><?= te('padi_badge') ?></span></li>
          <li class="chip"><?= brand_icon('google', 15) ?> <span><?= te('rating_badge', ['rating' => BUSINESS['rating'], 'count' => number_format((int) BUSINESS['review_count'])]) ?></span></li>
          <li class="chip"><?= brand_icon('tripadvisor', 16) ?> <span><?= te('ta_badge', ['rating' => number_format((float) $aboutTa['rating'], 1), 'count' => number_format((int) $aboutTa['review_count'])]) ?></span></li>
          <li class="chip"><?= brand_icon('cdws', 16) ?> <span><?= te('cdws_badge') ?></span></li>
        </ul>
        <p class="hero__ctas">
          <a class="btn btn--detail btn--sm" href="#dive-center">🏛️ <?= e(loc($galUi['jump_center'])) ?></a>
          <a class="btn btn--detail btn--sm" href="<?= e(page_link('/gallery', 'fleet')) ?>">🛥️ <?= e(loc($galUi['jump_fleet'])) ?></a>
          <a class="btn btn--detail btn--sm" href="<?= e(page_link('/dive-sites-sharm-el-sheikh')) ?>">🗺️ <?= te('nav_dive_sites') ?></a>
          <a class="btn btn--detail btn--sm" href="<?= e(SOCIAL['youtube']) ?>" target="_blank" rel="noopener" data-track="outbound_link"><?= brand_icon('youtube', 16) ?> <span>YouTube ↗</span></a>
          <a class="linklike" href="<?= e(PADI_PROFILE_URL) ?>" target="_blank" rel="noopener">PADI ↗</a>
          <a class="linklike" href="<?= e(ta_profile_url()) ?>" target="_blank" rel="noopener">Tripadvisor ↗</a>
        </p>
      </div>
      <div class="split__media">
        <?= sc_picture('images/center/sea-college-dive-club-center-01-entrance-al-montazah-nov2026.jpg', SITE_NAME . ' — ' . BUSINESS['street'] . ', ' . BUSINESS['city'], 960, 720, ['loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 899px) 92vw, 45vw']) ?>
      </div>
    </div>
  </div>
</section>

<?php if ($centerCol !== null):
    // Build an 8-photo physical place gallery where image 1 uses our beachfront instructors base so the top hero entrance isn't duplicated on the same page
    $aboutPhotos = $centerCol['photos'];
    $aboutPhotos[0] = [
        'id'      => 'center-base-beach',
        'type'    => 'photo',
        'cat'     => 'center',
        'item_id' => 'dive-1',
        'src'     => 'images/gallery/sea-college-dive-club-sharm-el-sheikh-padi-instructors-dive-base.jpg',
        'title'   => $galData['media'][9]['title'],
        'caption' => $galData['media'][9]['caption'],
    ];
?>
<section class="section section--tint" id="dive-center">
  <div class="container" data-gallery>
    <header class="section__head section__head--row">
      <div>
        <span class="eyebrow">🏛️ <span dir="auto"><?= e(loc($galUi['center_eyebrow'])) ?></span></span>
        <h2 dir="auto"><?= e(loc($galUi['center_title'])) ?></h2>
        <p dir="auto"><?= e(loc($galUi['center_sub'])) ?></p>
      </div>
      <a class="btn btn--primary btn--sm" href="<?= e(page_link('/gallery', 'fleet')) ?>">🛥️ <span dir="auto"><?= e(loc($galUi['jump_fleet'])) ?></span> →</a>
    </header>

    <div class="gal-grid gal-grid--4">
      <?php foreach ($aboutPhotos as $idx => $ph):
          $linked    = isset($ph['item_id'], $flat[$ph['item_id']]) ? $flat[$ph['item_id']] : null;
          $itemHref  = $linked ? item_url($linked) : page_link('/prices.php');
          $itemLabel = $linked ? (item_name($linked) . ' (' . money($linked['price']) . ')') : t('cta_view_prices');
          $pTitle    = loc($ph['title']);
          $pCap      = loc($ph['caption']);
      ?>
        <figure class="gal-item"
                data-gal-item
                data-gal-index="<?= $idx ?>"
                data-gal-cat="center"
                data-gal-type="photo"
                data-gal-src="<?= e(asset($ph['src'])) ?>"
                data-gal-poster=""
                data-gal-title="<?= e($pTitle) ?>"
                data-gal-caption="<?= e($pCap) ?>"
                data-gal-link="<?= e($itemHref) ?>"
                data-gal-link-text="<?= e($itemLabel) ?>">
          <button type="button" class="gal-item__btn" data-gal-open="<?= $idx ?>" aria-label="<?= e($pTitle) ?>">
            <?= sc_picture($ph['src'], $pTitle . ' — ' . SITE_NAME, 480, 360, ['sizes' => '(max-width: 639px) 92vw, (max-width: 1099px) 46vw, 24vw']) ?>
            <span class="gal-item__overlay">
              <strong dir="auto"><?= e($pTitle) ?></strong>
              <small dir="auto"><?= e($pCap) ?></small>
            </span>
          </button>
        </figure>
      <?php endforeach; ?>
    </div>

    <dialog class="lightbox" data-lightbox aria-label="<?= e(loc($galUi['center_title'])) ?>">
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

    <div style="margin-top:2rem">
      <h3 style="margin-bottom:.85rem;color:var(--navy)"><?= te('about_facilities_title') ?></h3>
      <ul class="facilities">
        <?php $fi = ['🏊', '🪸', '🛥️', '📚', '🫧', '♻️', '🤿', '❄️'];
        for ($i = 1; $i <= 8; $i++): ?>
          <li class="card"><span aria-hidden="true"><?= $fi[$i - 1] ?></span> <?= te("fac_$i") ?></li>
        <?php endfor; ?>
      </ul>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section" id="instructors">
  <div class="container">
    <header class="section__head section__head--row">
      <div>
        <h2><?= te('about_team_title') ?></h2>
        <p><?= te('about_team_text') ?></p>
      </div>
      <a class="btn btn--primary btn--sm" href="<?= e(page_link('/gallery')) ?>">📸 <?= te('gallery_see_videos_btn') ?></a>
    </header>
    <?= render_team_showcase($galData['team']) ?>
  </div>
</section>

<section class="section section--tint" id="accreditations">
  <div class="container">
    <?= render_real_guests_showcase(false) ?>
    <?= verified_platforms_hub_html(false) ?>
    <div style="margin-top:1.5rem">
      <?= ta_official_widget_html('about') ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container split split--rev">
    <div class="split__text">
      <h2><?= te('about_values_title') ?></h2>
      <ul class="ticks">
        <?php for ($i = 1; $i <= 4; $i++): ?><li><strong><?= te("why_{$i}_t") ?></strong> — <?= te("why_{$i}_d") ?></li><?php endfor; ?>
      </ul>
      <p><a class="btn btn--wa" href="<?= e(whatsapp_link('', 'about')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="about"><?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?><span><?= te('about_cta') ?></span></a></p>
    </div>
    <div class="split__media">
      <?= sc_picture(seo_img('pool-training-scuba-instructor'), SITE_NAME . ' — scuba instructor with a student in the training pool', 800, 600, ['sizes' => '(max-width: 899px) 92vw, 45vw']) ?>
    </div>
  </div>
</section>
<?php require ROOT_DIR . '/includes/footer.php';
