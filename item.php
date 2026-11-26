<?php
/**
 * Dedicated SEO + AEO landing page for every tour, package, course, boat, rental & fee
 * (/tours/<slug> and /courses/<slug>), plus 301 handler for legacy /product/<slug>/ URLs.
 */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require ROOT_DIR . '/includes/components.php';
require ROOT_DIR . '/includes/interactive_widgets.php';
require ROOT_DIR . '/includes/promo_banners.php';

$catalog       = require ROOT_DIR . '/data/catalog.php';
$seoAll        = require ROOT_DIR . '/data/items_seo.php';
$itemGalleries = is_file(ROOT_DIR . '/data/item_galleries.php') ? (require ROOT_DIR . '/data/item_galleries.php') : [];

/* ---- 1. Legacy WooCommerce /product/<slug>/ & Shinetheme /st_tour/<slug>/ 301 redirect ---- */
$legacy = strtolower(trim(sc_get_str('legacy'), '/'));
if ($legacy !== '') {
    $allById = catalog_all_items($catalog);
    $mappedId = $seoAll['legacy_products'][$legacy] ?? ($seoAll['legacy_slugs'][$legacy] ?? null);
    if ($mappedId && isset($allById[$mappedId])) {
        header('Location: ' . SITE_URL . sc_with_campaign_query(item_url($allById[$mappedId])), true, 301);
        exit;
    }
    $byDirectSlug = catalog_find_by_slug($catalog, $legacy);
    if ($byDirectSlug) {
        header('Location: ' . SITE_URL . sc_with_campaign_query(item_url($byDirectSlug)), true, 301);
        exit;
    }
    header('Location: ' . SITE_URL . sc_with_campaign_query(page_link('/services')), true, 301);
    exit;
}

/* ---- 2. Lookup item by slug & enforce canonical section (/tours vs /courses) ---- */
$reqSection = strtolower(sc_get_str('section'));
$reqSlug    = strtolower(trim(sc_get_str('slug'), '/'));
$it         = $reqSlug !== '' ? catalog_find_by_slug($catalog, $reqSlug) : null;

if (!$it && $reqSlug !== '') {
    $mappedSlugId = $seoAll['legacy_slugs'][$reqSlug] ?? ($seoAll['legacy_products'][$reqSlug] ?? null);
    if ($mappedSlugId) {
        $allById = catalog_all_items($catalog);
        if (isset($allById[$mappedSlugId])) {
            header('Location: ' . SITE_URL . sc_with_campaign_query(item_url($allById[$mappedSlugId])), true, 301);
            exit;
        }
    }
}

if (!$it) {
    require ROOT_DIR . '/404.php';
    exit;
}

$expectedSection = ($it['section'] ?? 'tours') === 'courses' ? 'courses' : 'tours';
if ($reqSection !== '' && $reqSection !== $expectedSection) {
    header('Location: ' . SITE_URL . sc_with_campaign_query(item_url($it)), true, 301);
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
$page['body_class']     = 'has-mobile-bar';
$page['nav_active']     = 'services';
$page['canonical_path'] = $itemPath;
$page['title']          = $metaTitle;
$page['description']    = $metaDesc;
$page['type']           = 'product';
$page['image']          = url('assets/' . $imgRel);
$page['preload_image']  = asset($imgRel);
$page['preload_rel']    = $imgRel;
$page['breadcrumbs']    = [
    [t('breadcrumb_home'), '/'],
    [$catLabel, $hubPath],
    [item_name($it), $itemPath],
];
$page['speakable']      = ['.item-hero__title', '.aeo-box__text'];
$page['schema']         = schema_item_detail($it, $seo, $catLabel, $gbp, $resolved, $itGal);
$page['item_track']     = [
    'id'         => $it['id'],
    'group'      => $catKey,
    'name'       => $it['name'],
    'category'   => $cat['label']['en'] ?? $catLabel,
    'price'      => $it['price'] ?? 0,
    'in_catalog' => sc_is_catalog_item($it),
];

$overview = $resolved['overview'];
$specs    = $resolved['specs'];
$steps    = $resolved['steps'];
$included = $resolved['included'];
$excluded = $resolved['excluded'];
$idealFor = $resolved['ideal_for'];
$faqs     = $resolved['faqs'];
$upgrades = sc_recommended_upgrades($catalog, $it, $seo['related'] ?? [], 4);

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

$sc_webp_url = static function (string $rel): string {
    $clean = ltrim($rel, '/');
    $dot = strrpos($clean, '.');
    $webp = ($dot !== false ? substr($clean, 0, $dot) : $clean) . '.webp';
    return is_file(ROOT_DIR . '/assets/' . $webp) ? asset($webp) : asset($clean);
};

require ROOT_DIR . '/includes/header.php';
?>
<section class="item-hero">
  <div class="container">
    <?= breadcrumbs_html($page['breadcrumbs'], true) ?>
    <div class="item-hero__grid">
      <div class="item-hero__copy">
        <?= sc_render_seller_referral_pill() ?>
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
          <?= sc_picture($imgRel, $h1, 960, 720, ['loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 959px) 92vw, 46vw', 'extra_attrs' => 'data-item-stage-img']) ?>
          <?php if (!empty($itGal['video']['src'])): ?>
          <video class="item-hero__video" data-item-stage-vid controls playsinline preload="metadata" poster="<?= e(asset((string) ($itGal['video']['poster'] ?? $imgRel))) ?>" hidden></video>
          <?php endif; ?>
          <?php if (!empty($it['popular'])): ?><span class="pcard__badge"><?= te('popular') ?></span><?php endif; ?>
        </figure>
        <?php if (!empty($itGal['photos']) || !empty($itGal['video']['src'])): ?>
        <div class="item-media-thumbs no-print" role="group" aria-label="<?= te('item_gallery_title') ?>">
          <button type="button" class="item-thumb is-active" data-item-thumb="photo" data-src="<?= e(asset($imgRel)) ?>" data-webp="<?= e($sc_webp_url($imgRel)) ?>" data-alt="<?= e($h1) ?>" aria-pressed="true">
            <?= sc_picture($imgRel, $h1, 120, 90, ['sizes' => '120px']) ?>
          </button>
          <?php foreach (($itGal['photos'] ?? []) as $pIdx => $gPhoto):
              $pNum = $pIdx + 2;
              $pAlt = $h1 . ' — ' . SITE_NAME . ' (' . $pNum . ')';
          ?>
          <button type="button" class="item-thumb" data-item-thumb="photo" data-src="<?= e(asset((string) $gPhoto)) ?>" data-webp="<?= e($sc_webp_url((string) $gPhoto)) ?>" data-alt="<?= e($pAlt) ?>" aria-pressed="false">
            <?= sc_picture((string) $gPhoto, $pAlt, 120, 90, ['sizes' => '120px']) ?>
          </button>
          <?php endforeach; ?>
          <?php if (!empty($itGal['video']['src'])): $v = $itGal['video']; ?>
          <button type="button" class="item-thumb item-thumb--video" data-item-thumb="video" data-src="<?= e(asset((string) $v['src'])) ?>" data-poster="<?= e(asset((string) ($v['poster'] ?? $imgRel))) ?>" aria-pressed="false" aria-label="<?= te('item_gallery_video_badge') ?>">
            <?= sc_picture((string) ($v['poster'] ?? $imgRel), t('item_gallery_video_badge') . ' — ' . $h1, 120, 90, ['sizes' => '120px']) ?>
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

        <?= render_hotel_pickup_checker('item') ?>

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

      </article>

      <aside class="item-sidebar">
        <div class="book-card card">
          <div class="book-card__price">
            <span class="book-card__lbl"><?= $it['price'] !== null ? te('from') : '' ?></span>
            <?= price_html($it['price'], $it['unit'] ?? '', $it['regular_price'] ?? null) ?>
            <?php if (isset($it['child_price'])): ?>
              <span class="book-card__sub"><?= te('child') ?> <bdi dir="ltr" data-eur="<?= e((string) $it['child_price']) ?>"><?= e(money($it['child_price'])) ?></bdi></span>
            <?php endif; ?>
            <?php $itSaveTxt = sc_save_badge_text($it); if ($itSaveTxt !== ''): ?>
              <span class="book-card__save"><?= $itSaveTxt ?></span>
            <?php endif; ?>
          </div>
          <p class="book-card__unit"><?= te('indicative_rate') ?></p>

          <?php if ($it['price'] !== null): ?>
          <div class="qbook no-print"
               data-quick-book
               data-qb-id="<?= e($it['id']) ?>"
               data-qb-name="<?= e(item_name($it)) ?>"
               data-qb-raw-name="<?= e($it['name']) ?>"
               data-qb-price="<?= e((string) $it['price']) ?>"
               data-qb-unit="<?= e((string) ($it['unit'] ?? '')) ?>">
            <div class="qbook__fields">
              <div class="qbook__field">
                <label for="qb-date"><?= te('qb_date_label') ?></label>
                <input id="qb-date" type="date" min="<?= e(date('Y-m-d')) ?>" data-qb-date>
              </div>
              <div class="qbook__field">
                <label for="qb-qty"><?= te(($it['unit'] ?? '') === 'boat' ? 'qb_boats_label' : 'qb_divers_label') ?></label>
                <div class="qbook__qty">
                  <button type="button" class="qbook__step" data-qb-step="-1" aria-label="−">−</button>
                  <input id="qb-qty" type="number" min="1" max="30" value="1" data-qb-qty>
                  <button type="button" class="qbook__step" data-qb-step="1" aria-label="+">+</button>
                </div>
              </div>
            </div>
            <p class="qbook__hotel" data-qb-hotel-badge hidden>🚐 <span data-qb-hotel-text></span></p>
            <div class="qbook__total">
              <div>
                <span class="qbook__total-lbl"><?= te('qb_est_total') ?></span>
                <small class="qbook__formula" data-qb-formula dir="ltr">1 × <?= e(money($it['price'])) ?> = <?= e(money($it['price'])) ?></small>
              </div>
              <strong class="price" data-qb-total data-eur="<?= e((string) $it['price']) ?>"><bdi dir="ltr"><?= e(money($it['price'])) ?></bdi></strong>
            </div>
          </div>
          <?php endif; ?>

          <div class="book-card__actions">
            <?= add_button($it) ?>
            <a class="btn btn--primary btn--block" href="#booking" data-qb-reserve data-track="cta_click" data-placement="item_book_anchor"><?= te('reserve_online') ?></a>
            <a class="btn btn--wa btn--block" href="<?= e(whatsapp_link(item_name($it), 'item_sidebar')) ?>" target="_blank" rel="noopener" data-qb-wa data-placement="item_quickbook">
              <?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?><span><?= te('ask_whatsapp') ?></span>
            </a>
          </div>
          <p class="book-card__note"><?= te('instant_confirm_note') ?></p>
          <hr class="book-card__hr">
          <a class="book-card__hub" href="<?= e(hub_url($cat)) ?>"><?= e(sc_arrow_prev()) ?> <?= te('view_category_hub') ?>: <?= e($catLabel) ?></a>
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

<?php if ($upgrades): ?>
<section class="section section--upgrades" id="upgrades">
  <div class="container">
    <header class="section__head">
      <p class="eyebrow"><?= te('upgrades_eyebrow') ?></p>
      <h2 dir="auto"><?= te('upgrades_title') ?></h2>
      <p dir="auto"><?= te('upgrades_sub', ['item' => item_name($it)]) ?></p>
    </header>
    <div class="cards cards--4">
      <?php foreach ($upgrades as $up): ?><?= upgrade_product_card($up['item'], $up['badge_key'], $it) ?><?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php
require_once ROOT_DIR . '/includes/guides_helpers.php';
$scItemGuidesAll = sc_load_guides();
$scMatchedGuides = [];
foreach ($scItemGuidesAll as $ig) {
    if (in_array($it['id'], $ig['related_ids'] ?? [], true)) {
        $scMatchedGuides[] = $ig;
    }
}
if (count($scMatchedGuides) < 2) {
    foreach (['sharm-el-sheikh-scuba-diving-prices-guide', 'best-time-to-dive-sharm-el-sheikh', 'flying-after-diving-and-medical-rules-egypt'] as $fbGSlug) {
        if (isset($scItemGuidesAll[$fbGSlug]) && !in_array($scItemGuidesAll[$fbGSlug], $scMatchedGuides, true)) {
            $scMatchedGuides[] = $scItemGuidesAll[$fbGSlug];
        }
        if (count($scMatchedGuides) >= 3) {
            break;
        }
    }
}
$scMatchedGuides = array_slice($scMatchedGuides, 0, 3);
if ($scMatchedGuides): ?>
<section class="section no-print" id="related-guides">
  <div class="container">
    <header class="section__head section__head--row">
      <div>
        <p class="eyebrow">📘 <?= te('nav_guides') ?></p>
        <h2 dir="auto"><?= e(t('related_guides_title') !== 'related_guides_title' ? t('related_guides_title') : t('nav_guides')) ?></h2>
      </div>
      <a class="btn btn--ghost btn--sm" href="<?= e(page_link('/guides')) ?>"><?= te('nav_guides') ?> <?= e(sc_arrow_next()) ?></a>
    </header>
    <div class="chips">
      <?php foreach ($scMatchedGuides as $mg): ?>
        <a class="chip" href="<?= e(guide_url($mg)) ?>" dir="auto">📘 <?= e($mg['title']) ?> <?= e(sc_arrow_next()) ?></a>
      <?php endforeach; ?>
      <a class="chip" href="<?= e(page_link('/dive-sites-sharm-el-sheikh')) ?>" dir="auto">🗺️ <?= te('nav_dive_sites') ?> <?= e(sc_arrow_next()) ?></a>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($faqs): ?>
<section class="section section--tint section--item-faq" id="faq">
  <div class="container">
    <header class="section__head">
      <p class="eyebrow"><?= te('item_faq_eyebrow') ?></p>
      <h2 dir="auto"><?= te('item_faq_title', ['item' => item_name($it)]) ?></h2>
      <p dir="auto"><?= te('item_faq_sub') ?></p>
      <ul class="faq-trust-pills">
        <li><?= te('item_faq_pill_1') ?></li>
        <li><?= te('item_faq_pill_2') ?></li>
        <li><?= te('item_faq_pill_3') ?></li>
        <li><?= te('item_faq_pill_4') ?></li>
      </ul>
    </header>
    <div class="item-faq-grid">
      <?= faq_pairs_html($faqs) ?>
    </div>
    <p class="item-faq-more no-print">
      <a class="btn btn--ghost" href="<?= e(page_link('/faq')) ?>" data-track="cta_click" data-placement="item_faq_hub">❓ <?= te('item_faq_view_all') ?> <?= e(sc_arrow_next()) ?></a>
      <a class="btn btn--wa" href="<?= e(whatsapp_link(item_name($it) . ' — FAQ', 'item_faq_wa')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="item_faq_wa">
        <?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?><span><?= te('ask_whatsapp') ?></span>
      </a>
    </p>
  </div>
</section>
<?php endif; ?>
<div class="mobile-item-bar no-print" role="region" aria-label="<?= e(item_name($it)) ?>">
  <div class="mobile-item-bar__price">
    <?php if ($it['price'] !== null): ?>
      <small class="mobile-item-bar__lbl" data-qb-bar-qty><?= te('from') ?></small>
      <span class="price" data-qb-bar-total data-eur="<?= e((string) $it['price']) ?>"><bdi dir="ltr"><?= e(money($it['price'])) ?></bdi></span>
    <?php else: ?>
      <?= price_html($it['price'], $it['unit'] ?? '') ?>
    <?php endif; ?>
  </div>
  <div class="mobile-item-bar__btns">
    <a class="btn btn--wa btn--sm" href="<?= e(whatsapp_link(item_name($it), 'item_sticky_bar')) ?>" target="_blank" rel="noopener" data-qb-wa data-placement="item_sticky_bar">
      <?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?><span><?= te('qb_wa_cta') ?></span>
    </a>
    <a class="btn btn--primary btn--sm" href="#booking" data-qb-reserve data-track="cta_click" data-placement="item_sticky_reserve"><?= te('qb_reserve_cta') ?></a>
  </div>
</div>
<?php require ROOT_DIR . '/includes/footer.php';
