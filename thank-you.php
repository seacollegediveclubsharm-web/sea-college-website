<?php
/** Thank-you page: confirms the request and fires browser conversion events once (de-duplicated with CAPI via event_id). */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require ROOT_DIR . '/includes/components.php';

$conv = is_array($_SESSION['conversion'] ?? null) ? $_SESSION['conversion'] : null;
unset($_SESSION['conversion']); // fire once only (refresh won't double count)

$page['id'] = 'thanks';
$page['title'] = t('meta_thanks_title');
$page['description'] = t('thanks_text');
$page['noindex'] = true;

require ROOT_DIR . '/includes/header.php';

if ($conv !== null):
    $payload = [
        'event_id' => (string) $conv['event_id'],
        'value' => (float) $conv['value'],
        'currency' => (string) $conv['currency'],
        'form' => (string) $conv['form'],
        'items' => $conv['items'],
        'user_data' => array_filter([
            'sha256_email_address' => (string) $conv['em_sha256'],
            'sha256_phone_number' => (string) $conv['ph_sha256'],
        ]),
    ];
    $json = json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
?>
<script>
window.SC_CONVERSION = <?= $json ?>;
(function (c) {
  // Enhanced Conversions: push pre-hashed SHA-256 email/phone to dataLayer for both GTM & gtag.
  if (Object.keys(c.user_data).length) { gtag('set', 'user_data', c.user_data); }
  // GTM / GA4: generate_lead + ecommerce items + user_data in the dataLayer.
  dataLayer.push({ ecommerce: null });
  dataLayer.push({
    event: 'generate_lead', form_id: c.form, event_id: c.event_id,
    value: c.value, currency: c.currency, user_data: c.user_data,
    ecommerce: { currency: c.currency, value: c.value, items: c.items }
  });
<?php if (GTM_ID === ''): ?>
  // No GTM container: send directly with gtag (Consent Mode decides what is stored).
  gtag('event', 'generate_lead', { value: c.value, currency: c.currency, form_id: c.form });
<?php if (GOOGLE_ADS_ID !== '' && GOOGLE_ADS_LEAD_LABEL !== ''): ?>
  gtag('event', 'conversion', { send_to: <?= json_encode(GOOGLE_ADS_ID . '/' . GOOGLE_ADS_LEAD_LABEL, JSON_HEX_TAG) ?>, value: c.value, currency: c.currency, transaction_id: c.event_id });
<?php endif; ?>
<?php endif; ?>
  // Meta "Lead" / TikTok "SubmitForm" are fired by consent.js after the pixels load (consent required).
})(window.SC_CONVERSION);
</script>
<?php endif; ?>
<section class="section section--center">
  <div class="container container--narrow center">
    <p class="big-emoji" aria-hidden="true">🐠</p>
    <h1><?= te('thanks_title') ?></h1>
    <p><?= te('thanks_text') ?></p>
    <p class="hero__ctas hero__ctas--center">
      <a class="btn btn--wa btn--lg" href="<?= e(whatsapp_link('', 'thank_you')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="thank_you"><?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?><span><?= te('cta_whatsapp') ?></span></a>
      <a class="btn btn--ghost btn--lg" href="<?= e(page_link('/')) ?>"><?= te('thanks_back') ?></a>
    </p>
  </div>
</section>
<?php require ROOT_DIR . '/includes/footer.php';
