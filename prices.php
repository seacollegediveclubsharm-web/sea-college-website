<?php
/** Prices & online booking (model: competitor #1 standard price list + booking stepper). */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require ROOT_DIR . '/includes/interactive_widgets.php';
require ROOT_DIR . '/includes/promo_banners.php';
$catalog = require ROOT_DIR . '/data/catalog.php';

$page['id'] = 'prices';
$page['canonical_path'] = '/prices';
$page['title'] = t('meta_prices_title');
$page['description'] = t('meta_prices_desc');
$page['breadcrumbs'] = [[t('breadcrumb_home'), '/'], [t('nav_prices'), '/prices']];
foreach ($catalog as $cat) {
    $page['schema'][] = schema_offer_catalog(loc($cat['label']), $cat);
}
$first = array_key_first($catalog);

require ROOT_DIR . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <?= breadcrumbs_html($page['breadcrumbs'], true) ?>
    <h1><?= te('prices_title') ?></h1>
    <p><?= te('prices_sub') ?></p>
    <nav class="chips no-print" aria-label="<?= te('services_jump') ?>">
      <a class="chip" href="#dive-calculator">🧮 <?= te('calc_jump_chip') ?></a>
      <a class="chip" href="#price-tables">📋 <?= te('prices_jump_tables') ?></a>
      <a class="chip" href="#dive-finder-quiz">🧭 <?= te('quiz_jump_chip') ?></a>
      <a class="chip" href="<?= e(page_link('/guides')) ?>">📘 <?= te('nav_guides') ?></a>
    </nav>
    <ol class="stepper" aria-label="Booking steps">
      <?php for ($i = 1; $i <= 4; $i++): ?>
        <li class="stepper__step<?= $i === 1 ? ' is-active' : '' ?>" data-step="<?= $i ?>"><span class="stepper__num"><?= $i ?></span><span><?= te("step_$i") ?></span></li>
      <?php endfor; ?>
    </ol>
  </div>
</section>

<section class="section section--tint no-print">
  <div class="container">
    <?= render_top_promo_banner('dives', 'prices_top_banner') ?>
    <?= render_budget_calculator($catalog, '#booking') ?>
    <?= render_hotel_pickup_checker('prices') ?>
  </div>
</section>

<section class="section section--prices" id="price-tables">
  <div class="container prices-layout">
    <div class="prices-main">
      <div class="tabs" role="tablist" aria-label="<?= te('nav_prices') ?>">
        <?php foreach ($catalog as $slug => $cat): ?>
          <button class="tabs__tab" type="button" role="tab" id="tab-<?= e($slug) ?>" aria-controls="panel-<?= e($slug) ?>" aria-selected="<?= $slug === $first ? 'true' : 'false' ?>" data-tab="<?= e($slug) ?>"><?= e($cat['icon'] . ' ' . loc($cat['label'])) ?></button>
        <?php endforeach; ?>
        <button class="btn btn--ghost btn--sm tabs__print" type="button" data-print><?= te('print_btn') ?></button>
      </div>

      <?php foreach ($catalog as $slug => $cat): ?>
        <div class="tabs__panel" role="tabpanel" id="panel-<?= e($slug) ?>" aria-labelledby="tab-<?= e($slug) ?>"<?= $slug === $first ? '' : ' hidden' ?>>
          <h2 class="print-only"><?= e(loc($cat['label'])) ?></h2>
          <?php $notes = $cat['notes'][LANG] ?? $cat['notes']['en'] ?? []; if ($notes): ?>
            <ul class="notes"><?php foreach ($notes as $n): ?><li><?= e($n) ?></li><?php endforeach; ?></ul>
          <?php endif; ?>
          <?php foreach ($cat['groups'] as $g): ?>
            <div class="ptable-wrap">
              <table class="ptable">
                <caption><?= e(loc($g['title'])) ?></caption>
                <tbody>
                  <?php foreach ($g['items'] as $it): ?>
                    <tr>
                      <th scope="row">
                        <span class="ptable__name" dir="auto"><a class="ptable__link" href="<?= e(item_url($it)) ?>"><?= e(item_name($it)) ?></a><?php if (!empty($it['popular'])): ?> <span class="badge"><?= te('popular') ?></span><?php endif; ?></span>
                        <?php $itDesc = item_desc($it); if ($itDesc !== ''): ?><small class="ptable__desc" dir="auto"><?= e($itDesc) ?></small><?php endif; ?>
                      </th>
                      <?php $itDur = item_duration($it); ?>
                      <td class="ptable__dur"><?= $itDur !== '' ? '⏱ ' . e($itDur) : '' ?></td>
                      <td class="ptable__price">
                        <?= price_html($it['price'], $it['unit'] ?? '') ?>
                        <?php if (isset($it['child_price'])): ?><small><?= te('child') ?> <bdi dir="ltr"><?= e(money($it['child_price'])) ?></bdi></small><?php endif; ?>
                        <?php if (!empty($it['save'])): ?><small class="save"><?= te('save') ?> <bdi dir="ltr"><?= e(money($it['save'])) ?></bdi></small><?php endif; ?>
                      </td>
                      <td class="ptable__act no-print"><a class="btn btn--detail btn--sm" href="<?= e(item_url($it)) ?>"><?= te('cta_details') ?></a> <?= add_button($it) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
      <p class="disclaimer"><?= te('prices_disclaimer') ?> <?= te('indicative_rate') ?></p>
    </div>

    <div class="prices-side no-print">
      <aside class="cart card" data-cart aria-labelledby="cart-title">
        <h2 id="cart-title"><?= te('cart_title') ?></h2>
        <p class="cart__empty" data-cart-empty><?= te('cart_empty') ?></p>
        <ul class="cart__items" data-cart-items></ul>
        <p class="cart__total"><span><?= te('cart_total') ?></span> <strong data-cart-total>€0</strong></p>
        <p class="cart__note"><?= te('cart_note') ?></p>
        <div class="cart__btns">
          <a class="btn btn--primary" href="#booking" data-cart-next data-track="cta_click" data-placement="cart_continue"><?= te('cart_next') ?></a>
          <button class="linklike" type="button" data-cart-clear><?= te('cart_clear') ?></button>
        </div>
      </aside>
      <?= render_side_promo_banner('bundles', 'prices_side_banner') ?>
    </div>
  </div>
</section>

<section class="section no-print">
  <div class="container">
    <?= render_dive_quiz($catalog) ?>
  </div>
</section>

<section class="section section--tint no-print" id="booking">
  <div class="container container--narrow">
    <h2><?= te('form_title_booking') ?></h2>
    <?php $form_id = 'booking'; $form_interest_default = ''; include ROOT_DIR . '/includes/lead_form.php'; ?>
  </div>
</section>
<?php require ROOT_DIR . '/includes/footer.php';
