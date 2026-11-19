<?php
/** Contact & booking request. */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require ROOT_DIR . '/includes/components.php';
require ROOT_DIR . '/includes/promo_banners.php';

$page['id'] = 'contact';
$page['title'] = t('meta_contact_title');
$page['description'] = t('meta_contact_desc');
$page['breadcrumbs'] = [[t('breadcrumb_home'), '/'], [t('nav_contact'), '/contact.php']];
$page['schema'][] = ['@type' => 'ContactPage', 'name' => t('contact_title'), 'about' => ['@id' => SITE_URL . '/#organization']];
$sc_map_src = 'https://www.google.com/maps?q=' . rawurlencode(MAPS_EMBED_QUERY) . '&output=embed';

require ROOT_DIR . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <h1><?= te('contact_title') ?></h1>
    <p><?= te('contact_sub') ?></p>
  </div>
</section>

<section class="section">
  <div class="container">
    <?= render_top_promo_banner('bundles', 'contact_top_banner') ?>
    <div class="contact-layout">
      <div>
        <?php $form_id = 'contact'; $form_interest_default = (string) ($_GET['interest'] ?? ''); include ROOT_DIR . '/includes/lead_form.php'; ?>
      </div>
      <div class="contact-side">
        <aside class="card contact-card">
          <a class="center-preview-img" href="<?= e(page_link('/about.php', 'dive-center')) ?>" aria-label="<?= e(SITE_NAME) ?> — <?= e(BUSINESS['street']) ?>">
            <img src="<?= e(asset('images/center/sea-college-dive-club-center-01-entrance-al-montazah-nov2026.jpg')) ?>" alt="<?= e(SITE_NAME) ?> — <?= e(BUSINESS['street'] . ', ' . BUSINESS['city']) ?>" width="960" height="720" loading="lazy">
          </a>
          <h2><?= te('contact_details_title') ?></h2>
          <p><a class="btn btn--wa btn--block" href="<?= e(whatsapp_link('', 'contact')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="contact"><?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?><span><?= te('cta_whatsapp') ?></span></a></p>
          <dl class="nap">
            <dt>WhatsApp</dt>
            <dd><a href="<?= e(whatsapp_link('', 'contact_nap')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="contact_nap"><bdi dir="ltr"><?= e(BUSINESS['phone_display']) ?></bdi></a></dd>
            <dt><?= te('phone') ?></dt>
            <dd><a href="tel:<?= e(BUSINESS['phone']) ?>" data-track="phone_click"><bdi dir="ltr"><?= e(BUSINESS['phone_display']) ?></bdi></a></dd>
            <dt><?= te('email') ?></dt>
            <dd><a href="mailto:<?= e(BUSINESS['email']) ?>" data-track="email_click"><?= e(BUSINESS['email']) ?></a></dd>
            <dt><?= te('address') ?></dt>
            <dd><?= e(BUSINESS['street'] . ', ' . BUSINESS['city']) ?></dd>
            <dt><?= te('hours') ?></dt>
            <dd><bdi dir="ltr"><?= e(BUSINESS['hours_display']) ?></bdi></dd>
          </dl>
          <h3><?= te('map_title') ?></h3>
          <?= gbp_map_embed() ?>
          <p class="badge-row" style="margin-top:.75rem">
            <a class="btn btn--ghost btn--sm" href="<?= e(MAPS_URL) ?>" target="_blank" rel="noopener" data-track="outbound_link"><?= brand_icon('google-maps', 15) ?> <span><?= te('open_maps') ?> ↗</span></a>
            <a class="btn btn--ghost btn--sm" href="<?= e(GOOGLE_WRITE_REVIEW_URL) ?>" target="_blank" rel="noopener" data-track="outbound_link"><?= brand_icon('google', 15) ?> <span><?= te('write_review') ?> ↗</span></a>
            <a class="btn btn--ta btn--sm" href="<?= e(ta_write_review_url()) ?>" target="_blank" rel="noopener" data-track="outbound_link"><?= brand_icon('tripadvisor', 16) ?> <span><?= te('ta_write_review') ?> ↗</span></a>
          </p>
          <div class="contact-socials">
            <?php foreach (SOCIAL as $net => $href): if ($href === '') { continue; } $netHref = ($net === 'tripadvisor') ? ta_profile_url() : $href; ?>
              <a class="social__btn social__btn--light" href="<?= e($netHref) ?>" target="_blank" rel="noopener" data-track="outbound_link" aria-label="<?= e(social_label($net)) ?>"><?= brand_icon($net, 17) ?><span><?= e(social_label($net)) ?></span></a>
            <?php endforeach; ?>
          </div>
        </aside>
        <?= render_side_promo_banner('brand', 'contact_side_banner') ?>
      </div>
    </div>
  </div>
</section>

<section class="section section--tint">
  <div class="container">
    <?= ta_official_widget_html('contact') ?>
  </div>
</section>
<?php require ROOT_DIR . '/includes/footer.php';
