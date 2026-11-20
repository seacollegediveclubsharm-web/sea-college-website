<?php
/**
 * Standalone Shareable Pre-Arrival Diver Check-In Portal (/check-in & /<lang>/check-in).
 * Utility intake page (`noindex => true`) keeping SEO crawl budget on core hubs/items/guides.
 */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require ROOT_DIR . '/includes/interactive_widgets.php';
require ROOT_DIR . '/includes/promo_banners.php';

$page['id']             = 'checkin';
$page['canonical_path'] = '/check-in';
$page['title']          = t('meta_checkin_title');
$page['description']    = t('meta_checkin_desc');
$page['noindex']        = true;
$page['breadcrumbs']    = [
    [t('breadcrumb_home'), '/'],
    [t('nav_checkin'), '/check-in'],
];

require ROOT_DIR . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <?= breadcrumbs_html($page['breadcrumbs'], true) ?>
    <p class="eyebrow">🪪 <?= te('checkin_page_eyebrow') ?></p>
    <h1 dir="auto"><?= te('checkin_page_title') ?></h1>
    <p class="hero__sub" dir="auto"><?= te('checkin_page_sub') ?></p>
  </div>
</section>

<section class="section section--tint">
  <div class="container container--narrow">
    <?= render_hotel_pickup_checker('checkin') ?>
    <?php
    $checkin_event_id   = substr(preg_replace('/[^\w\-]/', '', (string) ($_GET['ref'] ?? '')) ?? '', 0, 40);
    $checkin_standalone = true;
    include ROOT_DIR . '/includes/checkin_form.php';
    ?>
  </div>
</section>
<?php require ROOT_DIR . '/includes/footer.php';
