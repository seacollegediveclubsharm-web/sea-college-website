<?php
/**
 * Privacy & Cookie Policy — template drafted from the site's actual data flows.
 * NOTE for owner: have it reviewed by your legal adviser before launch.
 */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require ROOT_DIR . '/includes/components.php';

$page['id'] = 'privacy';
$page['title'] = t('meta_privacy_title');
$page['description'] = t('meta_privacy_desc');
$page['breadcrumbs'] = [[t('breadcrumb_home'), '/'], [t('footer_privacy'), '/privacy-policy.php']];

$cookies = [
    ['sc_sid', 'Sea College (essential)', 'Session & security token (CSRF) for forms', 'Session'],
    ['sc_lang', 'Sea College (essential)', 'Remembers your language', '1 year'],
    [CONSENT_COOKIE, 'Sea College (essential)', 'Stores your cookie choice', CONSENT_COOKIE_DAYS . ' days'],
    ['sc_attr', 'Sea College (essential)', 'Remembers the campaign (UTM / click ID) that brought you, attached to your enquiry', UTM_COOKIE_DAYS . ' days'],
];
if (GTM_ID !== '' || GA4_ID !== '') {
    $cookies[] = ['_ga, _ga_*', 'Google Analytics (analytics)', 'Measures visits and usage — only with consent', 'Up to 2 years'];
}
if (GOOGLE_ADS_ID !== '' || GTM_ID !== '') {
    $cookies[] = ['_gcl_*', 'Google Ads (marketing)', 'Measures ad conversions — only with consent', '90 days'];
}
if (META_PIXEL_ID !== '' && ENABLE_META_PIXEL) {
    $cookies[] = ['_fbp, _fbc', 'Meta (marketing)', 'Facebook/Instagram ads measurement — only with consent', '90 days'];
}
if (TIKTOK_PIXEL_ID !== '' && ENABLE_TIKTOK_PIXEL) {
    $cookies[] = ['_ttp', 'TikTok (marketing)', 'TikTok ads measurement — only with consent', '13 months'];
}
if (LINKEDIN_PARTNER_ID !== '' && ENABLE_LINKEDIN_TAG) {
    $cookies[] = ['li_*', 'LinkedIn (marketing)', 'LinkedIn ads measurement — only with consent', 'Up to 6 months'];
}

require ROOT_DIR . '/includes/header.php';
?>
<section class="page-hero"><div class="container"><h1><?= te('footer_privacy') ?></h1></div></section>
<section class="section">
  <div class="container container--narrow prose" lang="en" dir="ltr">
    <?php if (t('legal_en_only') !== ''): ?><p class="alert" lang="<?= e(LANG) ?>" dir="<?= e(DIR) ?>"><?= te('legal_en_only') ?></p><?php endif; ?>
    <p><?= e(SITE_NAME) ?> ("we") respects your privacy. This policy explains what we collect on this website, why, and the choices you have.</p>

    <h2>1. What we collect</h2>
    <ul>
      <li><strong>Enquiry &amp; booking forms:</strong> name, phone/WhatsApp, email, the services you select, number of people, preferred date, hotel, diving level and your message.</li>
      <li><strong>Newsletter:</strong> your email address.</li>
      <li><strong>Campaign information:</strong> the advert or link that brought you (UTM parameters, Google/Meta click IDs, landing page, referrer).</li>
      <li><strong>Technical data:</strong> IP address and browser information, used for security and spam protection.</li>
    </ul>

    <h2>2. Why we use it</h2>
    <ul>
      <li>To answer your request, confirm availability and arrange your booking (our legitimate interest / steps before a contract).</li>
      <li>To send offers by email only if you opted in (consent — you can unsubscribe anytime).</li>
      <li>To measure which adverts work, only if you click "Accept all" in the cookie banner (consent).</li>
    </ul>

    <h2>3. Sharing</h2>
    <p>We do not sell your data. We share it only with service providers that help us run the site and our bookings (web hosting, email, CRM) and — only with your consent — advertising platforms (Google, Meta, TikTok, LinkedIn) in hashed (encrypted) form to measure conversions.</p>

    <h2>4. Cookies</h2>
    <p>Essential cookies are always on. Analytics and marketing cookies load only after you click "Accept all". You can change your choice at any time with the <button type="button" class="linklike" data-consent-open><?= te('footer_cookie_settings') ?></button> link in the footer.</p>
    <div class="ptable-wrap">
      <table class="ptable ptable--simple">
        <thead><tr><th>Cookie</th><th>Provider</th><th>Purpose</th><th>Duration</th></tr></thead>
        <tbody>
          <?php foreach ($cookies as [$n, $p, $u, $d]): ?>
            <tr><td><code><?= e($n) ?></code></td><td><?= e($p) ?></td><td><?= e($u) ?></td><td><?= e($d) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <h2>5. Retention</h2>
    <p>Enquiries are kept for as long as needed to handle your booking and our legal obligations, then deleted. Newsletter data is kept until you unsubscribe.</p>

    <h2>6. Your rights</h2>
    <p>You can ask to access, correct or delete your data, or withdraw consent at any time, by emailing <a href="mailto:<?= e(BUSINESS['email']) ?>"><?= e(BUSINESS['email']) ?></a>.</p>

    <h2>7. Contact</h2>
    <p><?= e(SITE_NAME) ?>, <?= e(BUSINESS['street'] . ', ' . BUSINESS['city']) ?>, Egypt · <a href="mailto:<?= e(BUSINESS['email']) ?>"><?= e(BUSINESS['email']) ?></a> · <a href="tel:<?= e(BUSINESS['phone']) ?>" data-track="phone_click"><bdi dir="ltr"><?= e(BUSINESS['phone_display']) ?></bdi></a> · <a href="<?= e(whatsapp_link('', 'privacy')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="privacy">WhatsApp: <bdi dir="ltr"><?= e(BUSINESS['phone_display']) ?></bdi></a></p>
  </div>
</section>
<?php require ROOT_DIR . '/includes/footer.php';
