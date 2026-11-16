<?php
/** Newsletter mini-form (CSRF + attribution). Posts to handlers/newsletter.php. */
declare(strict_types=1);
?>
<form class="newsletter" action="/handlers/newsletter.php" method="post" data-ajax-form data-event="newsletter_signup" novalidate>
  <h3 class="newsletter__title"><?= te('newsletter_title') ?></h3>
  <p class="newsletter__sub"><?= te('newsletter_sub') ?></p>
  <?= csrf_field() ?>
  <?= attribution_fields() ?>
  <input type="hidden" name="lang" value="<?= e(LANG) ?>">
  <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
  <div class="newsletter__row">
    <label class="sr-only" for="nl-email"><?= te('newsletter_placeholder') ?></label>
    <input id="nl-email" type="email" name="email" required maxlength="160" placeholder="<?= te('newsletter_placeholder') ?>" autocomplete="email">
    <button class="btn btn--primary btn--sm" type="submit"><?= te('newsletter_btn') ?></button>
  </div>
  <p class="form-status" role="status" aria-live="polite"></p>
</form>
