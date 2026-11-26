<?php
/** Footer, floating WhatsApp button and consent banner. Closes <main>, <body>, <html>. */
declare(strict_types=1);
?>
</main>

<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-col footer-col--brand">
      <a class="brand brand--light" href="<?= e(page_link('/')) ?>">
        <?= sc_picture('images/logo.jpg', '', 48, 48, ['loading' => 'lazy', 'sizes' => '48px']) ?>
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
        <li><a href="<?= e(page_link('/guides')) ?>"><?= te('nav_guides') ?></a></li>
        <li><a href="<?= e(page_link('/faq')) ?>"><?= te('footer_faq') ?></a></li>
        <li><a href="<?= e(page_link('/gallery')) ?>"><?= te('nav_gallery') ?></a></li>
        <li><a href="<?= e(page_link('/prices', 'dive-calculator')) ?>"><?= te('calc_jump_chip') ?></a></li>
        <li><a href="<?= e(page_link('/check-in')) ?>"><?= te('nav_checkin') ?></a></li>
        <li><a href="<?= e(page_link('/about')) ?>"><?= te('nav_about') ?></a></li>
        <li><a href="<?= e(page_link('/contact')) ?>"><?= te('nav_contact') ?></a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h2 class="footer-title"><?= te('footer_contact') ?></h2>
      <ul class="footer-links footer-links--contact">
        <li>📍 <?= e(t('business_address_val') !== 'business_address_val' ? t('business_address_val') : (BUSINESS['street'] . ', ' . BUSINESS['city'])) ?></li>
        <li>💬 WhatsApp: <a href="<?= e(whatsapp_link('', 'footer_contact')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="footer_contact"><bdi dir="ltr"><?= e(BUSINESS['phone_display']) ?></bdi></a></li>
        <li>📞 <a href="tel:<?= e(BUSINESS['phone']) ?>" data-track="phone_click"><bdi dir="ltr"><?= e(BUSINESS['phone_display']) ?></bdi></a></li>
        <li>✉️ <a href="mailto:<?= e(BUSINESS['email']) ?>" data-track="email_click"><?= e(BUSINESS['email']) ?></a></li>
        <li>🕗 <?= te('hours') ?>: <bdi dir="auto"><?= e(t('business_hours_val') !== 'business_hours_val' ? t('business_hours_val') : BUSINESS['hours_display']) ?></bdi></li>
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
    <span>© <bdi dir="ltr"><?= e(BUSINESS['founded'] . '–' . date('Y')) ?></bdi> <strong><?= e(SITE_NAME) ?></strong> (<?= e(t('padi_badge') !== 'padi_badge' ? t('padi_badge') : BUSINESS['padi_level']) ?>). <?= te('footer_rights') ?> 🔒</span>
    <span class="footer-bottom__links">
      <a href="<?= e(page_link('/privacy-policy')) ?>"><?= te('footer_privacy') ?></a>
      <a href="<?= e(page_link('/terms', 'copyright')) ?>">© <?= te('footer_terms') ?></a>
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
  <a class="btn btn--primary btn--sm" href="<?= e(($page['id'] ?? '') === 'item' ? '#booking' : page_link('/prices', 'booking')) ?>" data-track="cta_click" data-placement="minicart_book"><?= te('minicart_review') ?> <?= e(sc_arrow_next()) ?></a>
</div>
<?php endif; ?>

<?php if (!in_array(($page['id'] ?? ''), ['item', 'thanks', 'checkin'], true)): ?>
<div class="mobile-quick-bar no-print" role="region" aria-label="<?= te('footer_contact') ?>">
  <a class="btn btn--wa mobile-quick-bar__wa" href="<?= e(whatsapp_link('', 'mobile_sticky_bar')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="mobile_sticky_bar">
    <?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?><span><?= te('cta_whatsapp') ?></span>
  </a>
  <a class="btn btn--ghost-dark mobile-quick-bar__call" href="tel:<?= e(BUSINESS['phone']) ?>" data-track="phone_click" data-placement="mobile_sticky_bar" aria-label="<?= te('cta_call') ?> <?= e(BUSINESS['phone_display']) ?>">📞</a>
</div>
<?php endif; ?>

<a class="wa-float" href="<?= e(whatsapp_link('', 'floating_button')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="floating" aria-label="<?= te('cta_whatsapp') ?>">
  <?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?>
</a>

<div class="consent" id="consent" role="dialog" aria-live="polite" aria-labelledby="consent-title" <?= consent_state() === 'unset' ? '' : 'hidden' ?>>
  <div class="consent__inner">
    <div>
      <h2 id="consent-title" class="consent__title"><?= te('consent_title') ?></h2>
      <p><?= te('consent_text') ?> <a href="<?= e(page_link('/privacy-policy')) ?>"><?= te('consent_policy') ?></a></p>
    </div>
    <div class="consent__actions">
      <button type="button" class="btn btn--ghost" data-consent="essential"><?= te('consent_essential') ?></button>
      <button type="button" class="btn btn--primary" data-consent="all"><?= te('consent_accept') ?></button>
    </div>
  </div>
</div>
</body>
</html>
<?php
if (
    defined('LARA_EMAIL_AUTOREPLY') && LARA_EMAIL_AUTOREPLY
    && SMTP_USER !== '' && SMTP_PASS !== ''
    && defined('GEMINI_API_KEY') && GEMINI_API_KEY !== ''
    && (function_exists('litespeed_finish_request') || function_exists('fastcgi_finish_request'))
) {
    $scEmailStamp = LEADS_STORAGE_DIR . '/.lara_email_poll.stamp';
    if (!is_file($scEmailStamp) || (time() - (int) @filemtime($scEmailStamp)) >= 180) {
        @touch($scEmailStamp);
        @ignore_user_abort(true);
        if (function_exists('litespeed_finish_request')) {
            @litespeed_finish_request();
        } elseif (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }
        try {
            require_once ROOT_DIR . '/includes/lara_email_concierge.php';
            lara_email_poll_and_reply(true, 2, null);
        } catch (\Throwable $e) {
            error_log('Lara email background poll error: ' . $e->getMessage());
        }
    }
}

