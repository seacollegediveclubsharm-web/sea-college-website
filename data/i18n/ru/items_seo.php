<?php
/**
 * Master Russian (`ru`) bespoke SEO/AEO aggregator (35 top-intent Google Ads landing pages).
 * Remaining 45 catalog items resolve via data/i18n/ru/item_templates.php with zero English leakage.
 */
declare(strict_types=1);

return array_replace(
    require __DIR__ . '/items_seo_courses.php',
    require __DIR__ . '/items_seo_tours.php',
);
