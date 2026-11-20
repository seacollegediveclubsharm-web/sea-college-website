<?php
/**
 * Reusable Pre-Arrival Diver Check-In & Gear Sizing Form (`check-in.php` & `thank-you.php`).
 * Variables consumed if set before include:
 *   $checkin_event_id   = string (optional lead_event_id from thank-you.php)
 *   $checkin_standalone = bool   (true on check-in.php, false on thank-you.php)
 */
declare(strict_types=1);

$checkin_event_id   = (string) ($checkin_event_id ?? '');
$checkin_standalone = !empty($checkin_standalone);
$ciToday            = date('Y-m-d');
$ciShareUrl         = SITE_URL . page_link('/check-in');
?>
<form class="lead-form checkin-form card" id="diver-checkin-form" action="/handlers/diver_checkin.php" method="post" data-ajax-form data-checkin-form data-event="diver_checkin" novalidate>
  <?= csrf_field() ?>
  <?= attribution_fields() ?>
  <input type="hidden" name="lead_event_id" value="<?= e($checkin_event_id) ?>">
  <input type="hidden" name="lang" value="<?= e(LANG) ?>">
  <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

  <div class="checkin-form__head">
    <div>
      <h2><?= te($checkin_standalone ? 'checkin_page_title' : 'checkin_title') ?></h2>
      <p><?= te($checkin_standalone ? 'checkin_page_sub' : 'checkin_sub') ?></p>
    </div>
    <div class="checkin-share">
      <small><?= te('checkin_share_note') ?></small>
      <button type="button" class="btn btn--ghost btn--sm" data-copy-checkin="<?= e($ciShareUrl) ?>">🔗 <span><?= te('checkin_copy_link') ?></span></button>
    </div>
  </div>

  <div class="form-grid">
    <div class="field">
      <label for="ci-name"><?= te('form_name') ?> *</label>
      <input id="ci-name" name="name" required minlength="2" maxlength="100" autocomplete="name">
    </div>
    <div class="field">
      <label for="ci-phone"><?= te('form_phone') ?><?= $checkin_standalone ? ' *' : '' ?></label>
      <input id="ci-phone" name="phone" type="tel" <?= $checkin_standalone ? 'required ' : '' ?>pattern="\+?[0-9 ()\-]{6,24}" maxlength="30" autocomplete="tel" dir="ltr" placeholder="+44 7700 900000">
    </div>
    <div class="field field--sm">
      <label for="ci-arrival"><?= te('checkin_arrival_date') ?></label>
      <input id="ci-arrival" name="arrival_date" type="date" min="<?= e($ciToday) ?>">
    </div>
    <div class="field field--sm">
      <label for="ci-divers"><?= te('checkin_divers_count') ?></label>
      <select id="ci-divers" name="divers_count">
        <?php for ($d = 1; $d <= 6; $d++): ?>
          <option value="<?= $d ?>"><?= $d ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="field">
      <label for="ci-hotel"><?= te('form_hotel') ?></label>
      <input id="ci-hotel" name="hotel" maxlength="120" autocomplete="off">
    </div>
    <div class="field field--sm">
      <label for="ci-room"><?= te('checkin_room') ?></label>
      <input id="ci-room" name="room_number" maxlength="24" dir="ltr" placeholder="e.g. 412">
    </div>

    <div class="field">
      <label for="ci-agency"><?= te('checkin_agency') ?></label>
      <select id="ci-agency" name="cert_agency">
        <option value="None / Beginner"><?= te('checkin_none') ?></option>
        <option value="PADI">PADI</option>
        <option value="SSI">SSI</option>
        <option value="CMAS">CMAS</option>
        <option value="BSAC">BSAC</option>
        <option value="NAUI">NAUI</option>
        <option value="Other"><?= te('checkin_other') ?></option>
      </select>
    </div>
    <div class="field">
      <label for="ci-cert"><?= te('checkin_cert') ?></label>
      <input id="ci-cert" name="cert_number" maxlength="50" dir="ltr">
    </div>
    <div class="field field--sm">
      <label for="ci-logged"><?= te('checkin_logged_dives') ?></label>
      <input id="ci-logged" name="logged_dives" type="number" min="0" max="9999" placeholder="0" dir="ltr">
    </div>
    <div class="field field--sm">
      <label for="ci-last-dive"><?= te('checkin_last_dive') ?></label>
      <input id="ci-last-dive" name="last_dive_date" type="date" max="<?= e($ciToday) ?>" data-last-dive-input>
    </div>

    <div class="field field--full" data-last-dive-wrap hidden>
      <p class="alert checkin-hint" data-last-dive-hint role="status" aria-live="polite"></p>
    </div>

    <div class="field field--sm">
      <label for="ci-height"><?= te('checkin_height') ?></label>
      <input id="ci-height" name="height_cm" type="number" min="100" max="225" placeholder="175" dir="ltr">
    </div>
    <div class="field field--sm">
      <label for="ci-weight"><?= te('checkin_weight') ?></label>
      <input id="ci-weight" name="weight_kg" type="number" min="25" max="180" placeholder="72" dir="ltr">
    </div>
    <div class="field field--sm">
      <label for="ci-suit"><?= te('checkin_wetsuit') ?></label>
      <select id="ci-suit" name="wetsuit_size">
        <option value="">—</option>
        <option value="Child"><?= te('checkin_child') ?></option>
        <?php foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL'] as $sz): ?>
          <option value="<?= e($sz) ?>"><?= e($sz) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field field--sm">
      <label for="ci-bcd"><?= te('checkin_bcd') ?></label>
      <select id="ci-bcd" name="bcd_size">
        <option value="">—</option>
        <option value="Child"><?= te('checkin_child') ?></option>
        <?php foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL'] as $sz): ?>
          <option value="<?= e($sz) ?>"><?= e($sz) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field field--sm">
      <label for="ci-fin"><?= te('checkin_fin') ?></label>
      <select id="ci-fin" name="fin_size">
        <option value="">—</option>
        <?php foreach (['35-36', '37-38', '39-40', '41-42', '43-44', '45-46', '47'] as $sz): ?>
          <option value="<?= e($sz) ?>">EU <?= e($sz) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field field--full">
      <label for="ci-notes"><?= te('checkin_gear_notes') ?></label>
      <textarea id="ci-notes" name="gear_notes" rows="2" maxlength="1000" placeholder="<?= te('checkin_gear_notes_ph') ?>"></textarea>
    </div>

    <div class="field field--full field--check">
      <label><input type="checkbox" name="nitrox_request" value="1"> <span>🫧 <?= te('checkin_nitrox') ?></span></label>
    </div>

    <fieldset class="field field--full med-box" data-med-check>
      <legend class="med-box__title">🩺 <?= te('checkin_med_title') ?></legend>
      <p class="med-box__intro"><?= te('checkin_med_intro') ?></p>
      <?php for ($mq = 1; $mq <= 3; $mq++): ?>
        <div class="med-box__row">
          <label for="ci-med-<?= $mq ?>" dir="auto"><?= te("checkin_med_q{$mq}") ?></label>
          <select id="ci-med-<?= $mq ?>" name="med_q<?= $mq ?>" data-med-select>
            <option value="no"><?= te('checkin_no') ?></option>
            <option value="yes"><?= te('checkin_yes') ?></option>
          </select>
        </div>
      <?php endfor; ?>
      <p class="med-box__status alert alert--ok" data-med-alert role="status" aria-live="polite"><?= te('checkin_med_all_no') ?></p>
    </fieldset>

    <div class="field field--full field--check">
      <label><input type="checkbox" name="medical_waiver" value="1" required> <span><?= te('checkin_waiver') ?> *</span></label>
    </div>
  </div>

  <div class="form-actions">
    <button class="btn btn--primary btn--lg" type="submit"><?= te('checkin_submit') ?></button>
    <span class="form-or"><?= te('form_or') ?></span>
    <button class="btn btn--wa" type="button" data-checkin-wa data-placement="<?= $checkin_standalone ? 'checkin_portal' : 'thankyou_checkin' ?>">
      <?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?><span><?= te('checkin_wa_btn') ?></span>
    </button>
  </div>
  <p class="form-status" role="status" aria-live="polite"></p>
</form>
