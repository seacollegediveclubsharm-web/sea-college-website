<?php
/**
 * 8-Language Red Sea & Sharm El Sheikh Scuba Diving Guides Hub (/guides & /<lang>/guides).
 */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require ROOT_DIR . '/includes/guides_helpers.php';
require ROOT_DIR . '/includes/interactive_widgets.php';
require ROOT_DIR . '/includes/promo_banners.php';

$catalog = require ROOT_DIR . '/data/catalog.php';
$guides  = sc_load_guides();

$page['id']             = 'hub-guides';
$page['nav_active']     = 'guides';
$page['canonical_path'] = '/guides';
$page['title']          = t('meta_guides_title');
$page['description']    = t('meta_guides_desc');
$page['image']          = url('assets/' . seo_img('group-boat-dive-red-sea-divers'));
$page['breadcrumbs']    = [
    [t('breadcrumb_home'), '/'],
    [t('nav_guides'), '/guides'],
];
$page['speakable']      = ['.page-hero h1', '.page-hero p'];

$itemList = [];
$pos = 1;
foreach ($guides as $g) {
    $itemList[] = [
        '@type'    => 'ListItem',
        'position' => $pos++,
        'url'      => SITE_URL . guide_url($g),
        'name'     => $g['title'],
    ];
}
$page['schema'][] = [
    '@type'      => 'CollectionPage',
    '@id'        => canonical_url() . '#collection',
    'name'       => t('guides_title'),
    'description'=> t('guides_sub'),
    'url'        => canonical_url(),
    'inLanguage' => LANG,
    'mainEntity' => [
        '@type'           => 'ItemList',
        'itemListElement' => $itemList,
    ],
];

require ROOT_DIR . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <?= breadcrumbs_html($page['breadcrumbs'], true) ?>
    <p class="eyebrow">📘 <?= te('guides_eyebrow') ?></p>
    <h1 dir="auto"><?= te('guides_title') ?></h1>
    <p class="hero__sub" dir="auto"><?= te('guides_sub') ?></p>
    <nav class="chips" aria-label="<?= te('services_jump') ?>">
      <a class="chip" href="<?= e(page_link('/dive-sites-sharm-el-sheikh')) ?>">🗺️ <?= te('nav_dive_sites') ?></a>
      <a class="chip" href="<?= e(page_link('/prices', 'dive-calculator')) ?>">🧮 <?= te('calc_jump_chip') ?></a>
      <a class="chip" href="#dive-finder-quiz">🧭 <?= te('quiz_jump_chip') ?></a>
      <a class="chip" href="<?= e(page_link('/check-in')) ?>">🪪 <?= te('nav_checkin') ?></a>
    </nav>
  </div>
</section>

<section class="section">
  <div class="container">
    <?= render_top_promo_banner('dives', 'guides_hub_banner') ?>

    <div class="cards guide-cards">
      <?php foreach ($guides as $g):
          $gHref = guide_url($g);
      ?>
        <article class="pcard guide-card">
          <a class="pcard__media" href="<?= e($gHref) ?>" tabindex="-1" aria-hidden="true">
            <?= sc_picture($g['img'], $g['title'] . ' — ' . SITE_NAME, 640, 480, ['sizes' => '(max-width: 599px) 92vw, (max-width: 899px) 46vw, 360px']) ?>
            <span class="pcard__badge"><?= e($g['badge']) ?></span>
          </a>
          <div class="pcard__body">
            <p class="pcard__meta">
              ⏱ <?= te('guides_read_time', ['min' => (string) $g['read_min']]) ?>
              · 📅 <bdi dir="ltr"><?= e($g['updated']) ?></bdi>
            </p>
            <h2 class="pcard__title" dir="auto"><a class="pcard__link" href="<?= e($gHref) ?>"><?= e($g['title']) ?></a></h2>
            <p class="pcard__desc" dir="auto"><?= e($g['excerpt']) ?></p>
            <div class="pcard__foot">
              <span class="pcard__price">📍 <?= te('location_sharm') ?></span>
              <a class="btn btn--primary btn--sm" href="<?= e($gHref) ?>"><?= te('guides_read_btn') ?> →</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--tint">
  <div class="container">
    <?= render_dive_quiz($catalog) ?>
  </div>
</section>

<section class="cta-band">
  <div class="container cta-band__inner">
    <div>
      <h2><?= te('guides_book_box_t') ?></h2>
      <p><?= te('guides_book_box_d') ?></p>
    </div>
    <div class="cta-band__btns">
      <a class="btn btn--primary btn--lg" href="<?= e(page_link('/prices')) ?>" data-track="cta_click" data-placement="guides_hub_prices"><?= te('nav_prices') ?></a>
      <a class="btn btn--wa btn--lg" href="<?= e(whatsapp_link(t('nav_guides'), 'guides_hub')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="guides_hub">
        <?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?><span><?= te('cta_whatsapp') ?></span>
      </a>
    </div>
  </div>
</section>
<?php require ROOT_DIR . '/includes/footer.php';
