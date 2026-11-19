<?php
/** Aggregator for all 20 bespoke German (`de`) PADI Course & Bundle SEO/AEO entries. */
declare(strict_types=1);

return array_replace(
    require __DIR__ . '/items_seo_courses_1.php',
    require __DIR__ . '/items_seo_courses_2.php',
    require __DIR__ . '/items_seo_courses_3.php',
);
