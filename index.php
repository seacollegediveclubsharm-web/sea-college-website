<?php
/** Home page. */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require ROOT_DIR . '/includes/interactive_widgets.php';
require ROOT_DIR . '/includes/promo_banners.php';
$catalog = require ROOT_DIR . '/data/catalog.php';
$galData = require ROOT_DIR . '/data/gallery_team.php';

$page['id'] = 'home';
$page['canonical_path'] = '/';
$page['title'] = t('meta_home_title');
$page['description'] = t('meta_home_desc');
$page['image'] = url('assets/images/hero.jpg');
$page['speakable'] = ['.hero h1', '.hero__sub'];
$page['schema'] = [schema_faq([1, 2, 3, 4, 5, 6])];

$explore = [
    ['intro', page_link('/tours/discover-scuba-diving-2-dives-boat-sharm'), seo_img('discover-scuba-diving-intro-dive-red-sea')],
    ['courses', page_link('/diving-courses-sharm-el-sheikh'), seo_img('padi-diving-course-students-underwater')],
    ['dives', page_link('/diving-trips-sharm-el-sheikh'), seo_img('group-boat-dive-red-sea-divers')],
    ['bundles', page_link('/padi-course-bundles-sharm-el-sheikh'), 'images/courses/05_padi_open_water_diver.jpg'],
    ['snorkel', page_link('/tours/white-island-ras-mohammed-boat-trip'), seo_img('snorkeling-trip-family-red-sea')],
    ['private', page_link('/snorkeling-and-boats-sharm-el-sheikh', 'private-charters'), seo_img('oceano-private-yacht-nov2026')],
    ['wreck', page_link('/tours/ss-thistlegorm-wreck-dive-sharm-el-sheikh'), seo_img('ss-thistlegorm-wreck-dive')],
    ['kids', page_link('/courses/padi-bubblemaker-sharm-el-sheikh'), 'images/courses/01_padi_bubblemaker.jpg'],
];
$popular = catalog_pick($catalog, ['dsd-quick-reef', 'dsd-2-boat', 'dive-2', 'owd', 'aowd', 'bubblemaker', 'snork-ras-adult', 'speed-2h', 'private-6-10', 'bundle-mega', 'sup-thistlegorm']);
$popular = array_slice($popular, 0, 8);

require ROOT_DIR . '/includes/header.php';
?>
<section class="hero hero--media">
  <picture class="hero__pic">
    <source type="image/webp" srcset="<?= e(asset('images/hero-640.webp')) ?> 640w, <?= e(asset('images/hero-1024.webp')) ?> 1024w, <?= e(asset('images/hero-1600.webp')) ?> 1600w" sizes="100vw">
    <img class="hero__bg" src="<?= e(asset('images/hero.jpg')) ?>" alt="" width="1600" height="960" fetchpriority="high" loading="eager" decoding="async">
  </picture>
  <div class="container hero__inner">
    <?= sc_render_seller_referral_pill() ?>
    <p class="eyebrow"><?= te('hero_eyebrow') ?></p>
    <h1><?= te('hero_title') ?></h1>
    <p class="hero__sub"><?= te('hero_sub') ?></p>
    <div class="hero__ctas">
      <a class="btn btn--wa btn--lg" href="<?= e(whatsapp_link('', 'hero')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="hero"><?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?><span><?= te('hero_cta_primary') ?></span></a>
      <a class="btn btn--ghost btn--lg" href="#dive-finder-quiz" data-track="cta_click" data-placement="hero_quiz">🧭 <?= te('quiz_jump_chip') ?></a>
      <a class="btn btn--ghost btn--lg" href="<?= e(page_link('/prices', 'dive-calculator')) ?>" data-track="cta_click" data-placement="hero_prices">🧮 <?= te('calc_jump_chip') ?></a>
    </div>
    <ul class="trust">
      <?php for ($i = 1; $i <= 4; $i++): ?><li>✓ <?= te("trust_$i") ?></li><?php endfor; ?>
    </ul>
  </div>
</section>

<?php $gbp = gbp_reviews_data(); ?>
<section class="kpis" aria-label="Key facts">
  <div class="container kpis__grid">
    <div class="kpi"><strong><bdi dir="ltr"><?= e((string) $gbp['rating']) ?>★</bdi></strong><span><?= te('kpi_rating') ?></span></div>
    <div class="kpi"><strong><bdi dir="ltr"><?= e(number_format((int) $gbp['review_count'])) ?></bdi></strong><span><?= te('kpi_reviews') ?></span></div>
    <div class="kpi"><strong><?= te('kpi_padi') ?></strong><span><?= te('kpi_padi_sub') ?></span></div>
    <div class="kpi"><strong><?= price_html(30) ?></strong><span><?= te('kpi_from') ?></span></div>
  </div>
</section>

<section class="section section--promo-top">
  <div class="container">
    <?= render_top_promo_banner('bundles', 'home_top_banner') ?>
  </div>
</section>

<section class="section">
  <div class="container">
    <?= section_head('explore_title', 'explore_sub') ?>
    <div class="explore">
      <?php foreach ($explore as $eIdx => [$key, $href, $img]): ?>
        <a class="explore__card" href="<?= e($href) ?>" data-track="cta_click" data-placement="explore_<?= e($key) ?>">
          <?= sc_picture($img, t("exp_{$key}_t") . ' — ' . t('location_sharm'), 400, 300, ['loading' => $eIdx < 4 ? 'eager' : 'lazy', 'sizes' => '(max-width: 899px) 44vw, 230px']) ?>
          <span class="explore__text"><strong><?= te("exp_{$key}_t") ?></strong><small><?= te("exp_{$key}_d") ?></small></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--tint">
  <div class="container">
    <?= render_dive_quiz($catalog) ?>
  </div>
</section>

<section class="section">
  <div class="container">
    <?= section_head('popular_title', 'popular_sub') ?>
    <div class="cards cards--4">
      <?php foreach ($popular as $pIdx => $it): ?><?= product_card($it, seo_img('group-boat-dive-red-sea-divers'), $pIdx < 4) ?><?php endforeach; ?>
    </div>
    <p class="center hero__ctas hero__ctas--center">
      <a class="btn btn--primary" href="<?= e(page_link('/prices', 'dive-calculator')) ?>" data-track="cta_click" data-placement="popular_calc">🧮 <?= te('calc_jump_chip') ?></a>
      <a class="btn btn--ghost" href="<?= e(page_link('/prices')) ?>" data-track="cta_click" data-placement="popular_all"><?= te('cta_view_prices') ?></a>
    </p>
  </div>
</section>

<section class="section section--journey" id="padi-journey-section">
  <div class="container">
    <?php require_once ROOT_DIR . '/includes/courses_journey.php'; ?>
    <?= render_courses_journey($catalog, 'home') ?>
  </div>
</section>

<section class="section section--tint">
  <div class="container">
    <div class="feature-duo">
      <article class="feature-banner card">
        <span class="eyebrow">🗺️ <?= te('sites_eyebrow') ?></span>
        <h2><?= te('home_sites_title') ?></h2>
        <p><?= te('home_sites_sub') ?></p>
        <p><a class="btn btn--primary" href="<?= e(page_link('/dive-sites-sharm-el-sheikh')) ?>" data-track="cta_click" data-placement="home_sites"><?= te('home_sites_btn') ?> <?= e(sc_arrow_next()) ?></a></p>
      </article>
      <article class="feature-banner card">
        <span class="eyebrow">🎬 <?= te('gallery_eyebrow') ?></span>
        <h2><?= te('home_gallery_title') ?></h2>
        <p><?= te('home_gallery_sub') ?></p>
        <p><a class="btn btn--primary" href="<?= e(page_link('/gallery')) ?>" data-track="cta_click" data-placement="home_gallery"><?= te('home_gallery_btn') ?> <?= e(sc_arrow_next()) ?></a></p>
      </article>
    </div>
  </div>
</section>

<?php
require_once ROOT_DIR . '/includes/guides_helpers.php';
$scHomeGuidesAll = sc_load_guides();
$scHomeGuidePicks = [];
foreach (['sharm-el-sheikh-scuba-diving-prices-guide', 'white-island-ras-mohammed-tickets-visa-snorkeling-guide', 'first-time-scuba-diving-sharm-el-sheikh', 'ras-mohammed-vs-tiran-island-diving', 'best-time-to-dive-sharm-el-sheikh'] as $hgSlug) {
    if (isset($scHomeGuidesAll[$hgSlug])) {
        $scHomeGuidePicks[] = $scHomeGuidesAll[$hgSlug];
    }
    if (count($scHomeGuidePicks) >= 3) {
        break;
    }
}
if ($scHomeGuidePicks): ?>
<section class="section" id="planning-guides">
  <div class="container">
    <header class="section__head section__head--row">
      <div>
        <p class="eyebrow">📘 <?= e(t('home_guides_eyebrow') !== 'home_guides_eyebrow' ? t('home_guides_eyebrow') : t('nav_guides')) ?></p>
        <h2><?= e(t('home_guides_title') !== 'home_guides_title' ? t('home_guides_title') : t('nav_guides')) ?></h2>
        <?php if (t('home_guides_sub') !== 'home_guides_sub'): ?><p><?= te('home_guides_sub') ?></p><?php endif; ?>
      </div>
      <a class="btn btn--detail btn--sm" href="<?= e(page_link('/guides')) ?>"><?= te('nav_guides') ?> <?= e(sc_arrow_next()) ?></a>
    </header>
    <div class="cards cards--3">
      <?php foreach ($scHomeGuidePicks as $hg): ?>
        <article class="pcard">
          <a class="pcard__media" href="<?= e(guide_url($hg)) ?>" tabindex="-1" aria-hidden="true">
            <?= sc_picture($hg['img'], $hg['title'], 480, 320, ['loading' => 'lazy', 'sizes' => '(max-width: 699px) 90vw, 350px']) ?>
            <span class="pcard__badge"><?= e($hg['badge']) ?></span>
          </a>
          <div class="pcard__body">
            <h3 class="pcard__title" dir="auto"><a class="pcard__link" href="<?= e(guide_url($hg)) ?>"><?= e($hg['title']) ?></a></h3>
            <p class="pcard__desc" dir="auto"><?= e($hg['excerpt']) ?></p>
            <div class="pcard__foot">
              <a class="btn btn--detail btn--sm" href="<?= e(guide_url($hg)) ?>"><?= te('cta_details') ?> <?= e(sc_arrow_next()) ?></a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container">
    <header class="section__head section__head--row">
      <div>
        <h2><?= te('team_showcase_title') ?></h2>
        <p><?= te('team_showcase_sub') ?></p>
      </div>
      <a class="btn btn--detail btn--sm" href="<?= e(page_link('/gallery', 'instructors')) ?>"><?= te('team_see_all_btn') ?> <?= e(sc_arrow_next()) ?></a>
    </header>
    <?= render_team_showcase($galData['team'], 3) ?>
  </div>
</section>

<section class="section section--tint">
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

<?php $ta = ta_reviews_data(); ?>
<section class="section section--dark" id="google-reviews">
  <div class="container">
    <header class="section__head">
      <p class="eyebrow">⭐ 🦉 <?= te('reviews_combined_eyebrow') ?></p>
      <h2><?= te('reviews_title', ['rating' => (string) $gbp['rating'], 'count' => number_format((int) $gbp['review_count'])]) ?></h2>
      <p><?= te('reviews_sub') ?></p>
    </header>
    <div class="reviews">
      <?php foreach (array_slice($gbp['reviews'], 0, 4) as $rev):
          $stars = max(1, min(5, (int) ($rev['rating'] ?? 5)));
      ?>
        <figure class="review card">
          <div class="review__stars" aria-label="<?= e($stars . '/5') ?>"><?= str_repeat('★', $stars) ?></div>
          <blockquote><p dir="auto"><?= e((string) $rev['text']) ?></p></blockquote>
          <figcaption><bdi dir="auto"><?= e((string) $rev['author']) ?></bdi> · <?= e((string) ($rev['time'] ?? 'Google')) ?></figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
    <p class="center hero__ctas hero__ctas--center">
      <a class="btn btn--ghost" href="<?= e(GOOGLE_REVIEWS_URL) ?>" target="_blank" rel="noopener" data-track="outbound_link"><?= te('see_all_reviews') ?> ↗</a>
      <a class="btn btn--primary" href="<?= e(GOOGLE_WRITE_REVIEW_URL) ?>" target="_blank" rel="noopener" data-track="outbound_link">★ <?= te('write_review') ?> ↗</a>
    </p>

    <div class="ta-showcase" id="tripadvisor-reviews">
      <h3 class="ta-showcase__title">🦉 <?= te('ta_section_title', ['rating' => number_format((float) $ta['rating'], 1), 'count' => number_format((int) $ta['review_count'])]) ?></h3>
      <?php if (!empty($ta['reviews'])): ?>
      <div class="reviews reviews--ta">
        <?php foreach (array_slice($ta['reviews'], 0, 4) as $trev):
            $tbubbles = max(1, min(5, (int) ($trev['rating'] ?? 5)));
        ?>
          <figure class="review review--ta card">
            <div class="review__bubbles" aria-label="<?= e($tbubbles . '/5') ?>"><?= str_repeat('●', $tbubbles) ?></div>
            <blockquote><p dir="auto">“<?= e((string) $trev['text']) ?>”</p></blockquote>
            <figcaption>
              <bdi dir="auto"><?= e((string) $trev['author']) ?></bdi> ·
              <a href="<?= e((string) ($trev['url'] ?? TRIPADVISOR_URL)) ?>" target="_blank" rel="noopener" data-track="outbound_link"><?= e((string) ($trev['time'] ?? 'Tripadvisor')) ?> ↗</a>
            </figcaption>
          </figure>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?= ta_official_widget_html('home') ?>
    </div>

    <?= render_real_guests_showcase(true) ?>

    <?= verified_platforms_hub_html(true) ?>
  </div>
</section>

<section class="section">
  <div class="container container--narrow">
    <?= section_head('faq_title') ?>
    <?= faq_list([1, 2, 3, 4, 5, 6]) ?>
    <p class="center hero__ctas hero__ctas--center">
      <a class="btn btn--ghost" href="<?= e(page_link('/faq')) ?>" data-track="cta_click" data-placement="home_faq_all">❓ <?= te('item_faq_view_all') ?> <?= e(sc_arrow_next()) ?></a>
    </p>
  </div>
</section>

<section class="section section--tint" id="location-map">
  <div class="container split">
    <div>
      <a class="center-preview-img" href="<?= e(page_link('/about', 'dive-center')) ?>" aria-label="<?= e(SITE_NAME) ?>">
        <?= sc_picture('images/center/sea-college-dive-club-center-01-entrance-al-montazah-nov2026.jpg', SITE_NAME . ' — ' . BUSINESS['street'] . ', ' . BUSINESS['city'], 960, 720, ['sizes' => '(max-width: 899px) 92vw, 45vw']) ?>
      </a>
      <h2><?= te('map_title') ?> — <?= te('location_sharm') ?></h2>
      <p><?= e(t('business_address_val') !== 'business_address_val' ? t('business_address_val') : (BUSINESS['street'] . ', ' . BUSINESS['city'])) ?></p>
      <p><strong><?= te('hours') ?>:</strong> <bdi dir="auto"><?= e(t('business_hours_val') !== 'business_hours_val' ? t('business_hours_val') : BUSINESS['hours_display']) ?></bdi></p>
      <p class="hero__ctas">
        <a class="btn btn--primary" href="<?= e(MAPS_URL) ?>" target="_blank" rel="noopener" data-track="outbound_link">📍 <?= te('open_maps') ?> ↗</a>
        <a class="btn btn--detail" href="<?= e(page_link('/about', 'dive-center')) ?>">🏛️ <?= e(loc($galData['ui']['jump_center'])) ?> <?= e(sc_arrow_next()) ?></a>
        <a class="btn btn--wa" href="<?= e(whatsapp_link('', 'home_map')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="home_map"><?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?><span><?= te('cta_whatsapp') ?></span></a>
      </p>
    </div>
    <div>
      <?= gbp_map_embed() ?>
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="container cta-band__inner">
    <div><h2><?= te('cta_band_title') ?></h2><p><?= te('cta_band_sub') ?></p></div>
    <div class="cta-band__btns">
      <a class="btn btn--wa btn--lg" href="<?= e(whatsapp_link('', 'cta_band')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="cta_band"><?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?><span><?= te('cta_whatsapp') ?></span></a>
      <a class="btn btn--ghost btn--lg" href="<?= e(page_link('/contact')) ?>" data-track="cta_click" data-placement="cta_band_contact"><?= te('cta_book') ?></a>
    </div>
  </div>
</section>
<?php require ROOT_DIR . '/includes/footer.php';
