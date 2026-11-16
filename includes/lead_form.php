<?php
/**
 * Reusable lead/booking form. Set before include:
 *   $form_id  = 'booking' | 'contact'
 *   $form_interest_default = '' (optional prefill)
 * Contains CSRF token, honeypot, attribution hidden fields and the cart payload slot.
 */
declare(strict_types=1);

$form_id = $form_id ?? 'contact';
$old = $_SESSION['form_old'] ?? [];
$err = $_SESSION['form_error'] ?? '';
unset($_SESSION['form_old'], $_SESSION['form_error']);
$val = static fn(string $k, string $d = ''): string => e((string) ($old[$k] ?? $d));
$catalog = $catalog ?? require ROOT_DIR . '/data/catalog.php';
$today = date('Y-m-d');
?>
<form class="lead-form card" id="<?= e($form_id) ?>-form" action="/handlers/lead_capture.php" method="post" data-ajax-form data-event="generate_lead" novalidate>
  <?= csrf_field() ?>
  <?= attribution_fields() ?>
  <input type="hidden" name="form_id" value="<?= e($form_id) ?>">
  <input type="hidden" name="lang" value="<?= e(LANG) ?>">
  <input type="hidden" name="cart" value="[]" data-cart-input>
  <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

  <?php if ($err !== ''): ?><p class="alert alert--error" role="alert"><?= e($err) ?></p><?php endif; ?>

  <div class="form-grid">
    <div class="field">
      <label for="<?= e($form_id) ?>-name"><?= te('form_name') ?> *</label>
      <input id="<?= e($form_id) ?>-name" name="name" required minlength="2" maxlength="100" autocomplete="name" value="<?= $val('name') ?>">
    </div>
    <div class="field">
      <label for="<?= e($form_id) ?>-phone"><?= te('form_phone') ?> *</label>
      <input id="<?= e($form_id) ?>-phone" name="phone" type="tel" required pattern="\+?[0-9 ()\-]{6,24}" maxlength="30" autocomplete="tel" dir="ltr" placeholder="+44 7700 900000" value="<?= $val('phone') ?>">
    </div>
    <div class="field">
      <label for="<?= e($form_id) ?>-email"><?= te('form_email') ?></label>
      <input id="<?= e($form_id) ?>-email" name="email" type="email" maxlength="160" autocomplete="email" dir="ltr" value="<?= $val('email') ?>">
    </div>
    <div class="field">
      <label for="<?= e($form_id) ?>-interest"><?= te('form_interest') ?></label>
      <select id="<?= e($form_id) ?>-interest" name="interest" data-interest-select>
        <option value=""><?= te('select_placeholder') ?></option>
        <option value="<?= te('opt_general') ?>"><?= te('opt_general') ?></option>
        <?php foreach ($catalog as $cat): ?>
          <optgroup label="<?= e(loc($cat['label'])) ?>">
            <?php foreach ($cat['groups'] as $g): foreach ($g['items'] as $it): if (str_starts_with($it['id'], 'eq-') || str_starts_with($it['id'], 'fee-')) { continue; } ?>
              <option value="<?= e($it['name']) ?>"<?= (($old['interest'] ?? ($form_interest_default ?? '')) === $it['name']) ? ' selected' : '' ?>><?= e($it['name'] . ' — ' . money($it['price'])) ?></option>
            <?php endforeach; endforeach; ?>
          </optgroup>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field field--sm">
      <label for="<?= e($form_id) ?>-people"><?= te('form_people') ?></label>
      <input id="<?= e($form_id) ?>-people" name="people" type="number" min="1" max="60" value="<?= $val('people', '2') ?>">
    </div>
    <div class="field field--sm">
      <label for="<?= e($form_id) ?>-date"><?= te('form_date') ?></label>
      <input id="<?= e($form_id) ?>-date" name="date" type="date" min="<?= e($today) ?>" value="<?= $val('date') ?>">
    </div>
    <div class="field">
      <label for="<?= e($form_id) ?>-hotel"><?= te('form_hotel') ?></label>
      <input id="<?= e($form_id) ?>-hotel" name="hotel" maxlength="120" autocomplete="off" value="<?= $val('hotel') ?>">
    </div>
    <div class="field">
      <label for="<?= e($form_id) ?>-level"><?= te('form_level') ?></label>
      <select id="<?= e($form_id) ?>-level" name="level">
        <option value=""><?= te('select_placeholder') ?></option>
        <?php foreach (['none' => 'level_none', 'sd' => 'level_sd', 'owd' => 'level_owd', 'aowd' => 'level_aowd'] as $k => $lbl): ?>
          <option value="<?= e($k) ?>"<?= (($old['level'] ?? '') === $k) ? ' selected' : '' ?>><?= te($lbl) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field field--full">
      <label for="<?= e($form_id) ?>-message"><?= te('form_message') ?></label>
      <textarea id="<?= e($form_id) ?>-message" name="message" rows="4" maxlength="2000"><?= $val('message') ?></textarea>
    </div>
    <div class="field field--full field--check">
      <label><input type="checkbox" name="consent" value="1" required> <span><?= te('form_consent') ?> <a href="/privacy-policy.php" target="_blank">↗</a></span></label>
      <label><input type="checkbox" name="marketing" value="1"> <span><?= te('form_marketing') ?></span></label>
    </div>
  </div>

  <div class="form-actions">
    <button class="btn btn--primary btn--lg" type="submit" data-track="form_submit_click"><?= te('form_submit') ?></button>
    <span class="form-or"><?= te('form_or') ?></span>
    <button class="btn btn--wa" type="button" data-wa-from-form data-track="whatsapp_click" data-placement="<?= e($form_id) ?>_form">
      <?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?><span><?= te('form_whatsapp_alt') ?></span>
    </button>
  </div>
  <p class="form-status" role="status" aria-live="polite"></p>
</form>
