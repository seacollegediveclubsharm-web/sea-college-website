<?php
/**
 * Standalone 8-Language Sharm El Sheikh Scuba Diving, PADI Course, Boat Trip,
 * Hotel Pickup & Pricing FAQ Hub (/faq & /<lang>/faq).
 *
 * Emits FAQPage + SpeakableSpecification JSON-LD across en, ru, it, pl, de, fr, ar, zh.
 */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require ROOT_DIR . '/includes/components.php';
require ROOT_DIR . '/includes/interactive_widgets.php';
require ROOT_DIR . '/includes/promo_banners.php';

$faqHub = require ROOT_DIR . '/data/faq_hub_i18n.php';
$cats   = $faqHub['categories'] ?? [];

$allFaqPairs = [];
$resolvedCats = [];
foreach ($cats as $catKey => $cData) {
    $pairs = [];
    foreach (($cData['faqs'] ?? []) as $entry) {
        $q = loc($entry['q'] ?? '');
        $a = loc($entry['a'] ?? '');
        if ($q !== '' && $a !== '') {
            $pairs[]       = [$q, $a];
            $allFaqPairs[] = [$q, $a];
        }
    }
    if ($pairs) {
        $resolvedCats[$catKey] = [
            'icon'  => (string) ($cData['icon'] ?? '❓'),
            'label' => loc($cData['label'] ?? $catKey),
            'faqs'  => $pairs,
        ];
    }
}

$page['id']             = 'faq';
$page['nav_active']     = 'faq';
$page['canonical_path'] = '/faq';
$page['title']          = t('meta_faq_title');
$page['description']    = t('meta_faq_desc');
$page['image']          = url('assets/' . seo_img('group-boat-dive-red-sea-divers'));
$page['breadcrumbs']    = [
    [t('breadcrumb_home'), '/'],
    [t('nav_faq'), '/faq'],
];
$page['speakable']      = ['.page-hero h1', '.aeo-box__text'];
if ($allFaqPairs) {
    $page['schema'][] = schema_faq_pairs($allFaqPairs);
}

require ROOT_DIR . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <?= breadcrumbs_html($page['breadcrumbs'], true) ?>
    <p class="eyebrow">❓ <?= te('faq_hub_eyebrow') ?></p>
    <h1 dir="auto"><?= te('faq_hub_title') ?></h1>
    <p class="hero__sub" dir="auto"><?= te('faq_hub_sub') ?></p>
    <nav class="chips no-print" aria-label="<?= te('nav_faq') ?>">
      <?php foreach ($resolvedCats as $cKey => $cMeta): ?>
        <a class="chip" href="#faq-<?= e($cKey) ?>"><?= e($cMeta['icon'] . ' ' . $cMeta['label']) ?></a>
      <?php endforeach; ?>
      <a class="chip" href="<?= e(page_link('/prices', 'dive-calculator')) ?>">🧮 <?= te('calc_jump_chip') ?></a>
      <a class="chip" href="#hotel-pickup-checker">🚐 <?= te('nav_checkin') ?></a>
    </nav>
  </div>
</section>

<section class="section">
  <div class="container">
    <?= render_top_promo_banner('bundles', 'faq_top_banner') ?>

    <aside class="aeo-box" id="quick-answer" aria-labelledby="faq-aeo-heading">
      <h2 id="faq-aeo-heading" class="aeo-box__label"><?= te('faq_hub_aeo_label') ?></h2>
      <p class="aeo-box__text" dir="auto"><?= te('faq_hub_aeo_text') ?></p>
    </aside>

    <div class="faq-hub" data-faq-hub>
      <div class="faq-hub__controls card no-print">
        <div class="faq-hub__search-wrap">
          <label class="sr-only" for="faq-search-input"><?= te('faq_hub_search_ph') ?></label>
          <input id="faq-search-input" class="faq-hub__search" type="search" placeholder="<?= te('faq_hub_search_ph') ?>" data-faq-search autocomplete="off">
        </div>
        <div class="faq-hub__pills" role="tablist" aria-label="<?= te('nav_faq') ?>">
          <button type="button" class="faq-pill is-active" data-faq-filter="all" aria-selected="true"><?= te('faq_hub_all_filter') ?> (<bdi dir="ltr"><?= count($allFaqPairs) ?></bdi>)</button>
          <?php foreach ($resolvedCats as $cKey => $cMeta): ?>
            <button type="button" class="faq-pill" data-faq-filter="<?= e($cKey) ?>" aria-selected="false"><?= e($cMeta['icon'] . ' ' . $cMeta['label']) ?> (<bdi dir="ltr"><?= count($cMeta['faqs']) ?></bdi>)</button>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="faq-hub__sections">
        <?php foreach ($resolvedCats as $cKey => $cMeta): ?>
          <section class="faq-cat" id="faq-<?= e($cKey) ?>" data-faq-section="<?= e($cKey) ?>">
            <h2 class="faq-cat__title" dir="auto"><span><?= e($cMeta['icon']) ?></span> <?= e($cMeta['label']) ?></h2>
            <div class="faq">
              <?php foreach ($cMeta['faqs'] as $idx => [$q, $a]):
                  $hay = mb_strtolower($q . ' ' . $a . ' ' . $cMeta['label']);
              ?>
                <details class="faq__item" data-faq-entry="<?= e($hay) ?>"<?= $idx === 0 ? ' open' : '' ?>>
                  <summary dir="auto"><?= e($q) ?></summary>
                  <p dir="auto"><?= e($a) ?></p>
                </details>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endforeach; ?>
      </div>

      <p class="alert faq-hub__empty" data-faq-empty hidden><?= te('faq_hub_empty') ?></p>
    </div>
  </div>
</section>

<section class="section section--tint" id="hotel-pickup-checker">
  <div class="container">
    <?= render_hotel_pickup_checker('faq') ?>
  </div>
</section>

<section class="cta-band">
  <div class="container cta-band__inner">
    <div>
      <h2 dir="auto"><?= te('faq_hub_cta_t') ?></h2>
      <p dir="auto"><?= te('faq_hub_cta_d') ?></p>
    </div>
    <div class="cta-band__btns">
      <a class="btn btn--wa btn--lg" href="<?= e(whatsapp_link(t('nav_faq'), 'faq_cta_band')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="faq_cta_band">
        <?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?><span><?= te('cta_whatsapp') ?></span>
      </a>
      <a class="btn btn--primary btn--lg" href="<?= e(page_link('/prices', 'dive-calculator')) ?>" data-track="cta_click" data-placement="faq_prices"><?= te('nav_prices') ?></a>
    </div>
  </div>
</section>
<?php require ROOT_DIR . '/includes/footer.php';
