<?php
/** About us. */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require ROOT_DIR . '/includes/interactive_widgets.php';
$galData = require ROOT_DIR . '/data/gallery_team.php';

$page['id'] = 'about';
$page['title'] = t('meta_about_title');
$page['description'] = t('meta_about_desc');
$page['image'] = url('assets/' . seo_img('dive-boat-briefing-instructor'));
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
  <div class="container split">
    <div class="split__text">
      <p><?= te('about_p1') ?></p>
      <p><?= te('about_p2') ?></p>
      <?php $aboutTa = ta_reviews_data(); ?>
      <ul class="chips">
        <li class="chip">🏅 <?= te('padi_badge') ?></li>
        <li class="chip">⭐ <?= te('rating_badge', ['rating' => BUSINESS['rating'], 'count' => number_format((int) BUSINESS['review_count'])]) ?></li>
        <li class="chip">🦉 <?= te('ta_badge', ['rating' => number_format((float) $aboutTa['rating'], 1), 'count' => number_format((int) $aboutTa['review_count'])]) ?></li>
        <li class="chip">🛡️ <?= te('cdws_badge') ?></li>
      </ul>
      <p class="hero__ctas">
        <a class="btn btn--detail btn--sm" href="<?= e(page_link('/gallery')) ?>">🎬 <?= te('nav_gallery') ?></a>
        <a class="btn btn--detail btn--sm" href="<?= e(page_link('/dive-sites-sharm-el-sheikh')) ?>">🗺️ <?= te('nav_dive_sites') ?></a>
        <a class="linklike" href="<?= e(PADI_PROFILE_URL) ?>" target="_blank" rel="noopener">PADI ↗</a>
        <a class="linklike" href="<?= e(ta_profile_url()) ?>" target="_blank" rel="noopener">Tripadvisor ↗</a>
      </p>
    </div>
    <div class="split__media">
      <img src="<?= e(asset(seo_img('dive-boat-briefing-instructor'))) ?>" alt="<?= e(SITE_NAME) ?> — dive briefing on the boat, Sharm El Sheikh" width="800" height="600" loading="lazy">
    </div>
  </div>
</section>

<section class="section section--tint">
  <div class="container">
    <?= section_head('about_facilities_title') ?>
    <ul class="facilities">
      <?php $fi = ['🏊', '🪸', '🛥️', '📚', '🫧', '♻️', '🤿', '❄️'];
      for ($i = 1; $i <= 8; $i++): ?>
        <li class="card"><span aria-hidden="true"><?= $fi[$i - 1] ?></span> <?= te("fac_$i") ?></li>
      <?php endfor; ?>
    </ul>
  </div>
</section>

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
      <img src="<?= e(asset(seo_img('pool-training-scuba-instructor'))) ?>" alt="<?= e(SITE_NAME) ?> — scuba instructor with a student in the training pool" width="800" height="600" loading="lazy">
    </div>
  </div>
</section>
<?php require ROOT_DIR . '/includes/footer.php';
