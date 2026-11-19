<?php
/** Footer, floating WhatsApp button and consent banner. Closes <main>, <body>, <html>. */
declare(strict_types=1);
?>
</main>

<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-col footer-col--brand">
      <a class="brand brand--light" href="<?= e(page_link('/')) ?>">
        <img src="<?= e(asset('images/logo.jpg')) ?>" alt="" width="48" height="48" loading="lazy">
        <span class="brand__text"><strong>Sea College</strong><small>Dive Club · Sharm</small></span>
      </a>
      <p><?= te('footer_about') ?></p>
      <?php
      require_once ROOT_DIR . '/includes/icons.php';
      require_once ROOT_DIR . '/includes/google_reviews.php';
      require_once ROOT_DIR . '/includes/tripadvisor_reviews.php';
      $sc_footer_gbp = gbp_reviews_data();
      $sc_footer_ta = ta_reviews_data();
      ?>
      <p class="badge-row">
        <a class="chip chip--light" href="<?= e(PADI_PROFILE_URL) ?>" target="_blank" rel="noopener" data-track="outbound_link"><?= brand_icon('padi', 16) ?> <span><?= te('padi_badge') ?></span></a>
        <a class="chip chip--light" href="<?= e(GOOGLE_REVIEWS_URL) ?>" target="_blank" rel="noopener" data-track="outbound_link"><?= brand_icon('google', 15) ?> <span><?= te('rating_badge', ['rating' => $sc_footer_gbp['rating'], 'count' => number_format((int) $sc_footer_gbp['review_count'])]) ?></span></a>
        <a class="chip chip--light" href="<?= e(ta_profile_url()) ?>" target="_blank" rel="noopener" data-track="outbound_link"><?= brand_icon('tripadvisor', 16) ?> <span><?= te('ta_badge', ['rating' => number_format((float) $sc_footer_ta['rating'], 1), 'count' => number_format((int) $sc_footer_ta['review_count'])]) ?></span></a>
        <a class="chip chip--light" href="<?= e(CDWS_PROFILE_URL) ?>" target="_blank" rel="noopener" data-track="outbound_link"><?= brand_icon('cdws', 16) ?> <span><?= te('cdws_badge') ?></span></a>
      </p>
    </div>
    <div class="footer-col">
      <h2 class="footer-title"><?= te('footer_explore') ?></h2>
      <ul class="footer-links">
        <li><a href="<?= e(page_link('/diving-trips-sharm-el-sheikh')) ?>"><?= te('footer_hub_dives') ?></a></li>
        <li><a href="<?= e(page_link('/diving-courses-sharm-el-sheikh')) ?>"><?= te('footer_hub_courses') ?></a></li>
        <li><a href="<?= e(page_link('/padi-course-bundles-sharm-el-sheikh')) ?>"><?= te('footer_hub_bundles') ?></a></li>
        <li><a href="<?= e(page_link('/snorkeling-and-boats-sharm-el-sheikh')) ?>"><?= te('footer_hub_boats') ?></a></li>
        <li><a href="<?= e(page_link('/dive-sites-sharm-el-sheikh')) ?>"><?= te('nav_dive_sites') ?></a></li>
        <li><a href="<?= e(page_link('/gallery')) ?>"><?= te('nav_gallery') ?></a></li>
        <li><a href="<?= e(page_link('/prices.php', 'dive-calculator')) ?>"><?= te('calc_jump_chip') ?></a></li>
        <li><a href="<?= e(page_link('/about.php')) ?>"><?= te('nav_about') ?></a></li>
        <li><a href="<?= e(page_link('/contact.php')) ?>"><?= te('nav_contact') ?></a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h2 class="footer-title"><?= te('footer_contact') ?></h2>
      <ul class="footer-links footer-links--contact">
        <li>📍 <?= e(BUSINESS['street']) ?>, <?= e(BUSINESS['city']) ?></li>
        <li>📞 <a href="tel:<?= e(BUSINESS['phone']) ?>" data-track="phone_click"><bdi dir="ltr"><?= e(BUSINESS['phone_display']) ?></bdi></a></li>
        <li>✉️ <a href="mailto:<?= e(BUSINESS['email']) ?>" data-track="email_click"><?= e(BUSINESS['email']) ?></a></li>
        <li>🕗 <?= te('hours') ?>: <bdi dir="ltr"><?= e(BUSINESS['hours_display']) ?></bdi></li>
      </ul>
    </div>
    <div class="footer-col">
      <h2 class="footer-title"><?= te('footer_follow') ?></h2>
      <ul class="social">
        <?php foreach (SOCIAL as $net => $href): if ($href === '') { continue; } $netHref = ($net === 'tripadvisor') ? ta_profile_url() : $href; $netLabel = social_label($net); ?>
          <li><a class="social__btn social__btn--<?= e($net) ?>" href="<?= e($netHref) ?>" target="_blank" rel="noopener" data-track="outbound_link" aria-label="<?= e($netLabel) ?>"><?= brand_icon($net, 18) ?><span><?= e($netLabel) ?></span></a></li>
        <?php endforeach; ?>
      </ul>
      <?php require ROOT_DIR . '/includes/newsletter_form.php'; ?>
    </div>
  </div>
  <div class="container footer-bottom">
    <span>© <?= e(date('Y')) ?> <?= e(SITE_NAME) ?>. <?= te('footer_rights') ?></span>
    <span class="footer-bottom__links">
      <a href="<?= e(page_link('/privacy-policy.php')) ?>"><?= te('footer_privacy') ?></a>
      <a href="<?= e(page_link('/terms.php')) ?>"><?= te('footer_terms') ?></a>
      <button type="button" class="linklike" data-consent-open><?= te('footer_cookie_settings') ?></button>
    </span>
  </div>
</footer>

<?php if (($page['id'] ?? '') !== 'prices'): ?>
<div class="minicart no-print" data-minicart hidden>
  <div class="minicart__info">
    <strong data-minicart-count>0</strong>
    <span class="minicart__sep">·</span>
    <span class="minicart__sum" data-minicart-total>€0</span>
  </div>
  <a class="btn btn--primary btn--sm" href="<?= e(($page['id'] ?? '') === 'item' ? '#booking' : page_link('/prices.php', 'booking')) ?>" data-track="cta_click" data-placement="minicart_book"><?= te('minicart_review') ?> →</a>
</div>
<?php endif; ?>

<a class="wa-float" href="<?= e(whatsapp_link('', 'floating_button')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="floating" aria-label="<?= te('cta_whatsapp') ?>">
  <?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?>
</a>

<div class="consent" id="consent" role="dialog" aria-live="polite" aria-labelledby="consent-title" <?= consent_state() === 'unset' ? '' : 'hidden' ?>>
  <div class="consent__inner">
    <div>
      <h2 id="consent-title" class="consent__title"><?= te('consent_title') ?></h2>
      <p><?= te('consent_text') ?> <a href="<?= e(page_link('/privacy-policy.php')) ?>"><?= te('consent_policy') ?></a></p>
    </div>
    <div class="consent__actions">
      <button type="button" class="btn btn--ghost" data-consent="essential"><?= te('consent_essential') ?></button>
      <button type="button" class="btn btn--primary" data-consent="all"><?= te('consent_accept') ?></button>
    </div>
  </div>
</div>
</body>
</html>
