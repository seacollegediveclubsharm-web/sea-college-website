<?php
/**
 * Individual 8-Language SEO + AEO Diving Guide Article (/guides/<slug> & /<lang>/guides/<slug>).
 * Uses $page['id'] = 'item' to share route-split item.min.css & item.min.js with zero bloat.
 */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require ROOT_DIR . '/includes/guides_helpers.php';
require ROOT_DIR . '/includes/interactive_widgets.php';
require ROOT_DIR . '/includes/promo_banners.php';

$guides  = sc_load_guides();
$reqSlug = strtolower(trim((string) ($_GET['slug'] ?? ''), '/'));
$g       = ($reqSlug !== '' && isset($guides[$reqSlug])) ? $guides[$reqSlug] : null;

if (!$g) {
    require ROOT_DIR . '/404.php';
    exit;
}

$catalog  = require ROOT_DIR . '/data/catalog.php';
$related  = catalog_pick($catalog, $g['related_ids']);
$gbp      = gbp_reviews_data();
$ta       = ta_reviews_data();
$gPath    = guide_path($g);

$page['id']             = 'item';
$page['nav_active']     = 'guides';
$page['canonical_path'] = $gPath;
$page['title']          = $g['meta_title'];
$page['description']    = $g['meta_desc'];
$page['type']           = 'article';
$page['image']          = url('assets/' . $g['img']);
$page['preload_image']  = asset($g['img']);
$page['preload_rel']    = $g['img'];
$page['breadcrumbs']    = [
    [t('breadcrumb_home'), '/'],
    [t('nav_guides'), '/guides'],
    [$g['title'], $gPath],
];
$page['speakable']      = ['.item-hero__title', '.aeo-box__text'];
$page['schema']         = schema_guide_article($g);

$otherGuides = [];
foreach ($guides as $slug => $cand) {
    if ($slug !== $g['slug']) {
        $otherGuides[] = $cand;
    }
}

require ROOT_DIR . '/includes/header.php';
?>
<section class="item-hero">
  <div class="container">
    <?= breadcrumbs_html($page['breadcrumbs'], true) ?>
    <div class="item-hero__grid">
      <div class="item-hero__copy">
        <p class="eyebrow"><a class="eyebrow__link" href="<?= e(page_link('/guides')) ?>">📘 <?= te('nav_guides') ?></a> · <?= e($g['badge']) ?></p>
        <h1 class="item-hero__title" dir="auto"><?= e($g['title']) ?></h1>
        <p class="item-hero__lead" dir="auto"><?= e($g['excerpt']) ?></p>
        <ul class="item-badges">
          <li>⏱ <?= te('guides_read_time', ['min' => (string) $g['read_min']]) ?></li>
          <li>📅 <?= te('guides_updated', ['date' => $g['updated']]) ?></li>
          <li><?= brand_icon('padi', 15) ?> <span><?= te('padi_badge') ?></span></li>
          <li><?= brand_icon('google', 14) ?> <bdi dir="ltr"><?= e((string) $gbp['rating']) ?>★</bdi> (<?= e(number_format((int) $gbp['review_count'])) ?>)</li>
        </ul>
      </div>
      <div class="item-media-wrap">
        <figure class="item-hero__media">
          <?= sc_picture($g['img'], $g['title'] . ' — ' . SITE_NAME, 960, 720, ['loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 959px) 92vw, 46vw']) ?>
          <span class="pcard__badge"><?= e($g['badge']) ?></span>
        </figure>
      </div>
    </div>
  </div>
</section>

<section class="section section--item">
  <div class="container">
    <?= render_top_promo_banner('bundles', 'guide_top_banner') ?>
    <div class="item-layout">
      <article class="item-main">
        <?php if ($g['aeo_answer'] !== ''): ?>
        <aside class="aeo-box" id="quick-answer" aria-labelledby="aeo-heading">
          <h2 id="aeo-heading" class="aeo-box__label"><?= te('quick_answer_label') ?></h2>
          <p class="aeo-box__text" dir="auto"><?= e($g['aeo_answer']) ?></p>
        </aside>
        <?php endif; ?>

        <?php if (count($g['sections']) > 1): ?>
        <nav class="guide-toc card no-print" aria-label="<?= te('guides_toc') ?>">
          <strong class="guide-toc__title">📑 <?= te('guides_toc') ?></strong>
          <ol class="guide-toc__list">
            <?php foreach ($g['sections'] as $sIdx => $sec): if ($sec['h2'] === '') { continue; } ?>
              <li><a href="#sec-<?= $sIdx + 1 ?>" dir="auto"><?= e($sec['h2']) ?></a></li>
            <?php endforeach; ?>
            <?php if ($g['faqs']): ?>
              <li><a href="#faq"><?= te('faq_title') ?></a></li>
            <?php endif; ?>
          </ol>
        </nav>
        <?php endif; ?>

        <?php if ($g['key_facts']): ?>
        <div class="item-block">
          <h2><?= te('guides_key_facts') ?></h2>
          <dl class="spec-grid">
            <?php foreach ($g['key_facts'] as [$fk, $fv]): ?>
              <div class="spec-grid__row">
                <dt dir="auto"><?= e($fk) ?></dt>
                <dd dir="auto"><?= e($fv) ?></dd>
              </div>
            <?php endforeach; ?>
          </dl>
        </div>
        <?php endif; ?>

        <?php foreach ($g['sections'] as $sIdx => $sec): ?>
        <div class="item-block guide-sec" id="sec-<?= $sIdx + 1 ?>">
          <?php if ($sec['h2'] !== ''): ?><h2 dir="auto"><?= e($sec['h2']) ?></h2><?php endif; ?>
          <?php foreach ($sec['paras'] as $para): ?>
            <p dir="auto"><?= e($para) ?></p>
          <?php endforeach; ?>
          <?php if ($sec['bullets']): ?>
            <ul class="ticks">
              <?php foreach ($sec['bullets'] as $b): ?><li dir="auto"><?= e($b) ?></li><?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <?= render_hotel_pickup_checker('guide') ?>

        <?php if ($g['faqs']): ?>
        <div class="item-block" id="faq">
          <h2><?= te('faq_title') ?></h2>
          <?= faq_pairs_html($g['faqs']) ?>
        </div>
        <?php endif; ?>
      </article>

      <aside class="item-sidebar">
        <div class="book-card card">
          <span class="eyebrow">🤿 <?= te('padi_badge') ?></span>
          <h2 class="guide-side-title" dir="auto"><?= te('guides_book_box_t') ?></h2>
          <p class="book-card__note" style="margin:0 0 1rem"><?= te('guides_book_box_d') ?></p>
          <div class="book-card__actions">
            <a class="btn btn--wa btn--block" href="<?= e(whatsapp_link($g['title'], 'guide_sidebar')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="guide_sidebar">
              <?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?><span><?= te('ask_whatsapp') ?></span>
            </a>
            <a class="btn btn--primary btn--block" href="<?= e(page_link('/prices', 'dive-calculator')) ?>" data-track="cta_click" data-placement="guide_calc"><?= te('calc_jump_chip') ?></a>
            <a class="btn btn--detail btn--block" href="<?= e(page_link('/services')) ?>"><?= te('nav_services') ?></a>
          </div>
          <hr class="book-card__hr">
          <a class="book-card__hub" href="<?= e(page_link('/guides')) ?>">← <?= te('guides_all_back') ?></a>
        </div>

        <?= render_side_promo_banner('dives', 'guide_side_banner') ?>
      </aside>
    </div>
  </div>
</section>

<?php if ($related): ?>
<section class="section section--tint">
  <div class="container">
    <header class="section__head">
      <h2><?= te('guides_related_exp') ?></h2>
    </header>
    <div class="cards cards--4">
      <?php foreach ($related as $rel): ?><?= product_card($rel) ?><?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($otherGuides): ?>
<section class="section">
  <div class="container">
    <header class="section__head section__head--row">
      <h2><?= te('guides_all_back') ?></h2>
      <a class="btn btn--ghost btn--sm" href="<?= e(page_link('/guides')) ?>"><?= te('nav_guides') ?> →</a>
    </header>
    <div class="cards">
      <?php foreach (array_slice($otherGuides, 0, 3) as $og): $ogHref = guide_url($og); ?>
        <article class="pcard guide-card">
          <a class="pcard__media" href="<?= e($ogHref) ?>" tabindex="-1" aria-hidden="true">
            <?= sc_picture($og['img'], $og['title'] . ' — ' . SITE_NAME, 480, 360, ['sizes' => '(max-width: 599px) 92vw, (max-width: 899px) 46vw, 340px']) ?>
            <span class="pcard__badge"><?= e($og['badge']) ?></span>
          </a>
          <div class="pcard__body">
            <p class="pcard__meta">⏱ <?= te('guides_read_time', ['min' => (string) $og['read_min']]) ?></p>
            <h3 class="pcard__title" dir="auto"><a class="pcard__link" href="<?= e($ogHref) ?>"><?= e($og['title']) ?></a></h3>
            <p class="pcard__desc" dir="auto"><?= e($og['excerpt']) ?></p>
            <div class="pcard__foot">
              <span class="pcard__price">📍 <?= te('location_sharm') ?></span>
              <a class="btn btn--detail btn--sm" href="<?= e($ogHref) ?>"><?= te('guides_read_btn') ?> →</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php require ROOT_DIR . '/includes/footer.php';
