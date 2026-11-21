<?php
/** 404 page (wired via .htaccess ErrorDocument). */
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once ROOT_DIR . '/includes/components.php';

http_response_code(404);
$page['id'] = '404';
$page['title'] = t('meta_404_title');
$page['description'] = t('404_text');
$page['noindex'] = true;

require ROOT_DIR . '/includes/header.php';
?>
<section class="section section--center">
  <div class="container container--narrow center">
    <p class="big-emoji" aria-hidden="true">🪸</p>
    <h1><?= te('404_title') ?></h1>
    <p><?= te('404_text') ?></p>
    <p class="hero__ctas hero__ctas--center">
      <a class="btn btn--primary" href="<?= e(page_link('/')) ?>"><?= te('thanks_back') ?></a>
      <a class="btn btn--ghost" href="<?= e(page_link('/prices')) ?>"><?= te('nav_prices') ?></a>
    </p>
  </div>
</section>
<?php require ROOT_DIR . '/includes/footer.php';
