<?php
/** About us. */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require ROOT_DIR . '/includes/components.php';

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
    <h1><?= te('about_title') ?></h1>
    <p><?= te('about_lead') ?></p>
  </div>
</section>

<section class="section">
  <div class="container split">
    <div class="split__text">
      <p><?= te('about_p1') ?></p>
      <p><?= te('about_p2') ?></p>
      <ul class="chips">
        <li class="chip">🏅 <?= te('padi_badge') ?></li>
        <li class="chip">⭐ <?= te('rating_badge', ['rating' => BUSINESS['rating'], 'count' => number_format((int) BUSINESS['review_count'])]) ?></li>
      </ul>
      <p><a class="linklike" href="<?= e(PADI_PROFILE_URL) ?>" target="_blank" rel="noopener">PADI profile ↗</a></p>
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

<section class="section">
  <div class="container split split--rev">
    <div class="split__text">
      <h2><?= te('about_team_title') ?></h2>
      <p><?= te('about_team_text') ?></p>
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
