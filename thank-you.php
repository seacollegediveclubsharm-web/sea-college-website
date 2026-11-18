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
<?php if (!GA4_VIA_GTM && GA4_ID !== ''): ?>
  // Direct GA4 generate_lead event (set GA4_VIA_GTM=1 if firing via GTM tag instead).
  gtag('event', 'generate_lead', { value: c.value, currency: c.currency, form_id: c.form, transaction_id: c.event_id, items: c.items });
<?php endif; ?>
<?php if (!GOOGLE_ADS_VIA_GTM && GOOGLE_ADS_ID !== '' && GOOGLE_ADS_LEAD_LABEL !== ''): ?>
  // Direct Google Ads conversion (set GOOGLE_ADS_VIA_GTM=1 if firing via GTM tag instead).
  gtag('event', 'conversion', { send_to: <?= json_encode(GOOGLE_ADS_ID . '/' . GOOGLE_ADS_LEAD_LABEL, JSON_HEX_TAG) ?>, value: c.value, currency: c.currency, transaction_id: c.event_id });
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
  <?php if ($conv !== null): ?>
  <div class="container container--narrow" style="margin-top:2.5rem;text-align:start">
    <form class="lead-form card" action="/handlers/diver_checkin.php" method="post" data-ajax-form data-event="diver_checkin" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="lead_event_id" value="<?= e((string) $conv['event_id']) ?>">
      <input type="hidden" name="lang" value="<?= e(LANG) ?>">
      <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
      <h2><?= te('checkin_title') ?></h2>
      <p><?= te('checkin_sub') ?></p>
      <div class="form-grid">
        <div class="field">
          <label for="ci-name"><?= te('form_name') ?></label>
          <input id="ci-name" name="name" maxlength="100" autocomplete="name">
        </div>
        <div class="field">
          <label for="ci-agency"><?= te('checkin_agency') ?></label>
          <select id="ci-agency" name="cert_agency">
            <option value="None / Beginner">None / Beginner</option>
            <option value="PADI">PADI</option>
            <option value="SSI">SSI</option>
            <option value="CMAS">CMAS</option>
            <option value="NAUI / BSAC / Other">NAUI / BSAC / Other</option>
          </select>
        </div>
        <div class="field">
          <label for="ci-cert"><?= te('checkin_cert') ?></label>
          <input id="ci-cert" name="cert_number" maxlength="50" dir="ltr">
        </div>
        <div class="field field--sm">
          <label for="ci-suit"><?= te('checkin_wetsuit') ?></label>
          <select id="ci-suit" name="wetsuit_size">
            <option value="">—</option>
            <?php foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL', 'Child'] as $sz): ?>
              <option value="<?= e($sz) ?>"><?= e($sz) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field field--sm">
          <label for="ci-fin"><?= te('checkin_fin') ?></label>
          <select id="ci-fin" name="fin_size">
            <option value="">—</option>
            <?php foreach (['35-37', '38-39', '40-41', '42-43', '44-45', '46-47'] as $sz): ?>
              <option value="<?= e($sz) ?>"><?= e($sz) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field field--sm">
          <label for="ci-bcd"><?= te('checkin_bcd') ?></label>
          <select id="ci-bcd" name="bcd_size">
            <option value="">—</option>
            <?php foreach (['XS', 'S', 'M', 'L', 'XL'] as $sz): ?>
              <option value="<?= e($sz) ?>"><?= e($sz) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field field--full field--check">
          <label><input type="checkbox" name="medical_waiver" value="1" required> <span><?= te('checkin_waiver') ?></span></label>
        </div>
      </div>
      <div class="form-actions">
        <button class="btn btn--primary" type="submit"><?= te('checkin_submit') ?></button>
      </div>
      <p class="form-status" role="status" aria-live="polite"></p>
    </form>
  </div>
  <?php endif; ?>
</section>
<?php require ROOT_DIR . '/includes/footer.php';
