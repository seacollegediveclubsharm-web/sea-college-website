<?php
/** Footer, floating WhatsApp button and consent banner. Closes <main>, <body>, <html>. */
declare(strict_types=1);
$sc_lq = LANG !== DEFAULT_LANG ? '?lang=' . LANG : '';
?>
</main>

<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-col footer-col--brand">
      <a class="brand brand--light" href="/<?= e($sc_lq) ?>">
        <img src="<?= e(asset('images/logo.jpg')) ?>" alt="" width="48" height="48" loading="lazy">
        <span class="brand__text"><strong>Sea College</strong><small>Dive Club · Sharm</small></span>
      </a>
      <p><?= te('footer_about') ?></p>
      <?php require_once ROOT_DIR . '/includes/google_reviews.php'; $sc_footer_gbp = gbp_reviews_data(); ?>
      <p class="badge-row">
        <a class="chip chip--light" href="<?= e(PADI_PROFILE_URL) ?>" target="_blank" rel="noopener" data-track="outbound_link">🏅 <?= te('padi_badge') ?></a>
        <a class="chip chip--light" href="<?= e(GOOGLE_REVIEWS_URL) ?>" target="_blank" rel="noopener" data-track="outbound_link">⭐ <?= te('rating_badge', ['rating' => $sc_footer_gbp['rating'], 'count' => number_format((int) $sc_footer_gbp['review_count'])]) ?></a>
      </p>
    </div>
    <div class="footer-col">
      <h2 class="footer-title"><?= te('footer_explore') ?></h2>
      <ul class="footer-links">
        <li><a href="/diving-trips-sharm-el-sheikh<?= e($sc_lq) ?>"><?= e(LANG === 'ar' ? 'رحلات وباقات الغوص' : 'Diving Trips & Packages') ?></a></li>
        <li><a href="/diving-courses-sharm-el-sheikh<?= e($sc_lq) ?>"><?= e(LANG === 'ar' ? 'دورات PADI للغوص' : 'PADI Diving Courses') ?></a></li>
        <li><a href="/padi-course-bundles-sharm-el-sheikh<?= e($sc_lq) ?>"><?= e(LANG === 'ar' ? 'باقات وعروض الدورات' : 'PADI Course Bundles') ?></a></li>
        <li><a href="/snorkeling-and-boats-sharm-el-sheikh<?= e($sc_lq) ?>"><?= e(LANG === 'ar' ? 'السنوركل والقوارب الخاصة' : 'Snorkeling & Private Boats') ?></a></li>
        <li><a href="/prices.php<?= e($sc_lq) ?>"><?= te('nav_prices') ?></a></li>
        <li><a href="/about.php<?= e($sc_lq) ?>"><?= te('nav_about') ?></a></li>
        <li><a href="/contact.php<?= e($sc_lq) ?>"><?= te('nav_contact') ?></a></li>
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
        <?php foreach (SOCIAL as $net => $href): if ($href === '') { continue; } ?>
          <li><a href="<?= e($href) ?>" target="_blank" rel="noopener" data-track="outbound_link" aria-label="<?= e(ucfirst($net)) ?>"><?= e(ucfirst($net)) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <?php require ROOT_DIR . '/includes/newsletter_form.php'; ?>
    </div>
  </div>
  <div class="container footer-bottom">
    <span>© <?= e(date('Y')) ?> <?= e(SITE_NAME) ?>. <?= te('footer_rights') ?></span>
    <span class="footer-bottom__links">
      <a href="/privacy-policy.php<?= e($sc_lq) ?>"><?= te('footer_privacy') ?></a>
      <a href="/terms.php<?= e($sc_lq) ?>"><?= te('footer_terms') ?></a>
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
  <a class="btn btn--primary btn--sm" href="<?= e(($page['id'] ?? '') === 'item' ? '#booking' : '/prices.php' . $sc_lq . '#booking') ?>" data-track="cta_click" data-placement="minicart_book"><?= te('minicart_review') ?> →</a>
</div>
<?php endif; ?>

<a class="wa-float" href="<?= e(whatsapp_link('', 'floating_button')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="floating" aria-label="<?= te('cta_whatsapp') ?>">
  <?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?>
</a>

<div class="consent" id="consent" role="dialog" aria-live="polite" aria-labelledby="consent-title" <?= consent_state() === 'unset' ? '' : 'hidden' ?>>
  <div class="consent__inner">
    <div>
      <h2 id="consent-title" class="consent__title"><?= te('consent_title') ?></h2>
      <p><?= te('consent_text') ?> <a href="/privacy-policy.php<?= e($sc_lq) ?>"><?= te('consent_policy') ?></a></p>
    </div>
    <div class="consent__actions">
      <button type="button" class="btn btn--ghost" data-consent="essential"><?= te('consent_essential') ?></button>
      <button type="button" class="btn btn--primary" data-consent="all"><?= te('consent_accept') ?></button>
    </div>
  </div>
</div>
</body>
</html>
