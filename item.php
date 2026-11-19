<?php
/**
 * Dedicated SEO + AEO landing page for every tour, package, course, boat, rental & fee
 * (/tours/<slug> and /courses/<slug>), plus 301 handler for legacy /product/<slug>/ URLs.
 */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require ROOT_DIR . '/includes/components.php';
require ROOT_DIR . '/includes/promo_banners.php';

$catalog       = require ROOT_DIR . '/data/catalog.php';
$seoAll        = require ROOT_DIR . '/data/items_seo.php';
$itemGalleries = is_file(ROOT_DIR . '/data/item_galleries.php') ? (require ROOT_DIR . '/data/item_galleries.php') : [];

/* ---- 1. Legacy WooCommerce /product/<slug>/ 301 redirect ---- */
$legacy = strtolower(trim((string) ($_GET['legacy'] ?? ''), '/'));
if ($legacy !== '') {
    $allById = catalog_all_items($catalog);
    $mappedId = $seoAll['legacy_products'][$legacy] ?? null;
    if ($mappedId && isset($allById[$mappedId])) {
        header('Location: ' . sc_with_campaign_query(item_url($allById[$mappedId])), true, 301);
        exit;
    }
    $byDirectSlug = catalog_find_by_slug($catalog, $legacy);
    if ($byDirectSlug) {
        header('Location: ' . sc_with_campaign_query(item_url($byDirectSlug)), true, 301);
        exit;
    }
    header('Location: ' . sc_with_campaign_query(page_link('/diving-courses-sharm-el-sheikh')), true, 301);
    exit;
}

/* ---- 2. Lookup item by slug & enforce canonical section (/tours vs /courses) ---- */
$reqSection = strtolower(trim((string) ($_GET['section'] ?? '')));
$reqSlug    = strtolower(trim((string) ($_GET['slug'] ?? ''), '/'));
$it         = $reqSlug !== '' ? catalog_find_by_slug($catalog, $reqSlug) : null;

if (!$it) {
    require ROOT_DIR . '/404.php';
    exit;
}

$expectedSection = ($it['section'] ?? 'tours') === 'courses' ? 'courses' : 'tours';
if ($reqSection !== '' && $reqSection !== $expectedSection) {
    header('Location: ' . sc_with_campaign_query(item_url($it)), true, 301);
    exit;
}

$seo      = $seoAll['items'][$it['id']] ?? [];
$resolved = sc_resolve_item_seo($it, $seo);
$cat      = $it['_cat'];
$catKey   = (string) ($it['_cat_key'] ?? 'dives');
$group    = $it['_group'];
$catLabel = loc($cat['label']);
$grpLabel = loc($group['title']);
$hubPath  = hub_path($cat);
$itemPath = item_path($it);
$imgRel   = 'images/' . ($it['img'] ?? 'hero.jpg');
$itGal    = $itemGalleries[$it['id']] ?? [];
$gbp      = gbp_reviews_data();
$ta       = ta_reviews_data();
$itDur    = item_duration($it);

$h1        = $resolved['h1'];
$metaTitle = $resolved['meta_title'];
$metaDesc  = $resolved['meta_desc'];
$aeoText   = $resolved['aeo_answer'];

$page['id']             = 'item';
$page['nav_active']     = 'services';
$page['canonical_path'] = $itemPath;
$page['title']          = $metaTitle;
$page['description']    = $metaDesc;
$page['type']           = 'product';
$page['image']          = url('assets/' . $imgRel);
$page['preload_image']  = asset($imgRel);
$page['breadcrumbs']    = [
    [t('breadcrumb_home'), '/'],
    [$catLabel, $hubPath],
    [item_name($it), $itemPath],
];
$page['speakable']      = ['.item-hero__title', '.aeo-box__text'];
$page['schema']         = schema_item_detail($it, $seo, $catLabel, $gbp, $resolved, $itGal);
$page['item_track']     = [
    'id'       => $it['id'],
    'name'     => $it['name'],
    'category' => $cat['label']['en'] ?? $catLabel,
    'price'    => $it['price'] ?? 0,
];

$overview = $resolved['overview'];
$specs    = $resolved['specs'];
$steps    = $resolved['steps'];
$included = $resolved['included'];
$excluded = $resolved['excluded'];
$idealFor = $resolved['ideal_for'];
$faqs     = $resolved['faqs'];
$related  = catalog_related($catalog, $it, $seo['related'] ?? [], 4);

$revList  = $gbp['reviews'] ?? [];
$revIdx   = (int) ($seo['review_idx'] ?? 0);
$revPick  = $revList[$revIdx % max(1, count($revList))] ?? null;

$sideBannerTheme = match ($catKey) {
    'padi-courses' => 'bundles',
    'offers'       => 'boats',
    'snorkeling'   => 'intro',
    default        => (str_starts_with($it['id'], 'dsd') ? 'bundles' : 'dives'),
};
$topBannerTheme = match ($sideBannerTheme) {
    'bundles' => 'dives',
    'dives'   => 'bundles',
    'boats'   => 'dives',
    default   => 'bundles',
};

require ROOT_DIR . '/includes/header.php';
?>
<section class="item-hero">
  <div class="container">
    <?= breadcrumbs_html($page['breadcrumbs'], true) ?>
    <div class="item-hero__grid">
      <div class="item-hero__copy">
        <p class="eyebrow"><a class="eyebrow__link" href="<?= e(hub_url($cat)) ?>"><?= e($cat['icon'] . ' ' . $catLabel) ?></a> · <?= e($grpLabel) ?></p>
        <h1 class="item-hero__title" dir="auto"><?= e($h1) ?></h1>
        <p class="item-hero__lead" dir="auto"><?= e(item_desc($it)) ?></p>
        <ul class="item-badges">
          <li><?= brand_icon('padi', 15) ?> <span><?= te('padi_badge') ?></span></li>
          <li><?= brand_icon('google', 14) ?> <bdi dir="ltr"><?= e((string) $gbp['rating']) ?>★</bdi> (<?= e(number_format((int) $gbp['review_count'])) ?>)</li>
          <li><?= brand_icon('tripadvisor', 15) ?> <bdi dir="ltr"><?= e(number_format((float) $ta['rating'], 1)) ?>/5</bdi></li>
          <?php if ($itDur !== ''): ?><li>⏱ <?= e($itDur) ?></li><?php endif; ?>
          <li>📍 <?= te('location_sharm') ?></li>
        </ul>
      </div>
      <div class="item-media-wrap" data-item-gallery>
        <figure class="item-hero__media">
          <img src="<?= e(asset($imgRel)) ?>" alt="<?= e($h1) ?>" width="960" height="720" fetchpriority="high" data-item-stage-img>
          <?php if (!empty($itGal['video']['src'])): ?>
          <video class="item-hero__video" data-item-stage-vid controls playsinline preload="metadata" poster="<?= e(asset((string) ($itGal['video']['poster'] ?? $imgRel))) ?>" hidden></video>
          <?php endif; ?>
          <?php if (!empty($it['popular'])): ?><span class="pcard__badge"><?= te('popular') ?></span><?php endif; ?>
        </figure>
        <?php if (!empty($itGal['photos']) || !empty($itGal['video']['src'])): ?>
        <div class="item-media-thumbs no-print" role="group" aria-label="<?= te('item_gallery_title') ?>">
          <button type="button" class="item-thumb is-active" data-item-thumb="photo" data-src="<?= e(asset($imgRel)) ?>" data-alt="<?= e($h1) ?>" aria-pressed="true">
            <img src="<?= e(asset($imgRel)) ?>" alt="<?= e($h1) ?>" width="120" height="90" loading="lazy">
          </button>
          <?php foreach (($itGal['photos'] ?? []) as $pIdx => $gPhoto):
              $pNum = $pIdx + 2;
              $pAlt = $h1 . ' — ' . SITE_NAME . ' (' . $pNum . ')';
          ?>
          <button type="button" class="item-thumb" data-item-thumb="photo" data-src="<?= e(asset((string) $gPhoto)) ?>" data-alt="<?= e($pAlt) ?>" aria-pressed="false">
            <img src="<?= e(asset((string) $gPhoto)) ?>" alt="<?= e($pAlt) ?>" width="120" height="90" loading="lazy">
          </button>
          <?php endforeach; ?>
          <?php if (!empty($itGal['video']['src'])): $v = $itGal['video']; ?>
          <button type="button" class="item-thumb item-thumb--video" data-item-thumb="video" data-src="<?= e(asset((string) $v['src'])) ?>" data-poster="<?= e(asset((string) ($v['poster'] ?? $imgRel))) ?>" aria-pressed="false" aria-label="<?= te('item_gallery_video_badge') ?>">
            <img src="<?= e(asset((string) ($v['poster'] ?? $imgRel))) ?>" alt="<?= te('item_gallery_video_badge') ?> — <?= e($h1) ?>" width="120" height="90" loading="lazy">
            <span class="item-thumb__play">▶ <?= e((string) ($v['dur'] ?? 'HD')) ?></span>
          </button>
          <?php endif; ?>
        </div>
        <p class="item-media-caption no-print">
          <span>📸 <?= te('item_gallery_title') ?></span>
          <a href="<?= e(page_link('/gallery')) ?>"><?= te('item_gallery_more') ?> →</a>
        </p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<section class="section section--item">
  <div class="container">
    <?= render_top_promo_banner($topBannerTheme, 'item_top_banner') ?>
    <div class="item-layout">
      <article class="item-main">
        <?php if ($aeoText !== ''): ?>
        <aside class="aeo-box" id="quick-answer" aria-labelledby="aeo-heading">
          <h2 id="aeo-heading" class="aeo-box__label"><?= te('quick_answer_label') ?></h2>
          <p class="aeo-box__text" dir="auto"><?= e($aeoText) ?></p>
        </aside>
        <?php endif; ?>

        <?php if ($overview): ?>
        <div class="item-block">
          <h2><?= te('overview_title') ?></h2>
          <?php foreach ($overview as $p): ?>
            <p dir="auto"><?= e($p) ?></p>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($specs): ?>
        <div class="item-block">
          <h2><?= te('specs_title') ?></h2>
          <dl class="spec-grid">
            <?php foreach ($specs as [$spK, $spV]): ?>
              <div class="spec-grid__row">
                <dt><?= e($spK) ?></dt>
                <dd dir="auto"><?= e($spV) ?></dd>
              </div>
            <?php endforeach; ?>
          </dl>
        </div>
        <?php endif; ?>

        <?php if ($steps): ?>
        <div class="item-block">
          <h2><?= te($expectedSection === 'courses' ? 'steps_title_course' : 'steps_title_tour') ?></h2>
          <ol class="step-list">
            <?php foreach ($steps as $idx => [$stT, $stB]): ?>
              <li class="step-list__item">
                <span class="step-list__num" aria-hidden="true"><?= $idx + 1 ?></span>
                <div>
                  <h3 dir="auto"><?= e($stT) ?></h3>
                  <p dir="auto"><?= e($stB) ?></p>
                </div>
              </li>
            <?php endforeach; ?>
          </ol>
        </div>
        <?php endif; ?>

        <?php if ($included || $excluded): ?>
        <div class="item-block inc-grid">
          <?php if ($included): ?>
          <div class="inc-card inc-card--yes">
            <h2>✓ <?= te('included_title') ?></h2>
            <ul>
              <?php foreach ($included as $line): ?><li dir="auto"><?= e($line) ?></li><?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>
          <?php if ($excluded): ?>
          <div class="inc-card inc-card--no">
            <h2>ℹ <?= te('excluded_title') ?></h2>
            <ul>
              <?php foreach ($excluded as $line): ?><li dir="auto"><?= e($line) ?></li><?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?= comparison_table_html((string) ($seo['compare_mode'] ?? 'none'), $catalog, $it['id']) ?>

        <?php if ($idealFor): ?>
        <div class="item-block">
          <h2><?= te('ideal_for_title') ?></h2>
          <ul class="ticks">
            <?php foreach ($idealFor as $pt): ?><li dir="auto"><?= e($pt) ?></li><?php endforeach; ?>
          </ul>
          <p class="safety-note">🛫 <?= te('safety_nofly_note') ?></p>
        </div>
        <?php endif; ?>

        <?php if ($revPick): ?>
        <figure class="item-review card">
          <div class="item-review__top">
            <span class="review__stars" aria-label="5/5">★★★★★</span>
            <small><?= te('verified_guest_review') ?> · <a href="<?= e(GOOGLE_REVIEWS_URL) ?>" target="_blank" rel="noopener" data-track="outbound_link"><?= brand_icon('google', 13) ?> <bdi dir="ltr"><?= e((string) $gbp['rating']) ?>★</bdi> / <?= e(number_format((int) $gbp['review_count'])) ?> ↗</a></small>
          </div>
          <blockquote><p dir="auto">“<?= e((string) $revPick['text']) ?>”</p></blockquote>
          <figcaption>— <bdi dir="auto"><?= e((string) $revPick['author']) ?></bdi></figcaption>
        </figure>
        <?php endif; ?>

        <?php if ($faqs): ?>
        <div class="item-block" id="faq">
          <h2><?= te('faq_title') ?></h2>
          <?= faq_pairs_html($faqs) ?>
        </div>
        <?php endif; ?>
      </article>

      <aside class="item-sidebar">
        <div class="book-card card">
          <div class="book-card__price">
            <span class="book-card__lbl"><?= $it['price'] !== null ? te('from') : '' ?></span>
            <?= price_html($it['price'], $it['unit'] ?? '') ?>
            <?php if (isset($it['child_price'])): ?>
              <span class="book-card__sub"><?= te('child') ?> <bdi dir="ltr"><?= e(money($it['child_price'])) ?></bdi></span>
            <?php endif; ?>
            <?php if (!empty($it['save'])): ?>
              <span class="book-card__save"><?= te('save') ?> <bdi dir="ltr"><?= e(money($it['save'])) ?></bdi></span>
            <?php endif; ?>
          </div>
          <p class="book-card__unit"><?= te('indicative_rate') ?></p>

          <div class="book-card__actions">
            <?= add_button($it) ?>
            <a class="btn btn--primary btn--block" href="#booking" data-track="cta_click" data-placement="item_book_anchor"><?= te('reserve_online') ?></a>
            <a class="btn btn--wa btn--block" href="<?= e(whatsapp_link($it['name'], 'item_sidebar')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="item_sidebar">
              <?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?><span><?= te('ask_whatsapp') ?></span>
            </a>
          </div>
          <p class="book-card__note"><?= te('instant_confirm_note') ?></p>
          <hr class="book-card__hr">
          <a class="book-card__hub" href="<?= e(hub_url($cat)) ?>">← <?= te('view_category_hub') ?>: <?= e($catLabel) ?></a>
        </div>

        <div class="cart card no-print" data-cart aria-labelledby="cart-title">
          <h2 id="cart-title"><?= te('cart_title') ?></h2>
          <p class="cart__empty" data-cart-empty><?= te('cart_empty') ?></p>
          <ul class="cart__items" data-cart-items></ul>
          <p class="cart__total"><span><?= te('cart_total') ?></span> <strong data-cart-total>€0</strong></p>
          <p class="cart__note"><?= te('cart_note') ?></p>
          <div class="cart__btns">
            <a class="btn btn--primary btn--sm" href="#booking" data-cart-next data-track="cta_click" data-placement="item_cart_continue"><?= te('cart_next') ?></a>
            <button class="linklike" type="button" data-cart-clear><?= te('cart_clear') ?></button>
          </div>
        </div>

        <?= render_side_promo_banner($sideBannerTheme, 'item_side_banner') ?>
      </aside>
    </div>
  </div>
</section>

<section class="section section--tint no-print" id="booking">
  <div class="container container--narrow">
    <h2><?= te('form_title_booking') ?> — <?= e(item_name($it)) ?></h2>
    <p><?= te('instant_confirm_note') ?></p>
    <?php $form_id = 'booking'; $form_interest_default = $it['name']; include ROOT_DIR . '/includes/lead_form.php'; ?>
  </div>
</section>

<?php if ($related): ?>
<section class="section">
  <div class="container">
    <header class="section__head">
      <h2><?= te('related_title') ?></h2>
    </header>
    <div class="cards cards--4">
      <?php foreach ($related as $rel): ?><?= product_card($rel) ?><?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php require ROOT_DIR . '/includes/footer.php';
