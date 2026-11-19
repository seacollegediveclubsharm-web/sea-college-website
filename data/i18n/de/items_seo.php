<?php
/** Master German (`de`) bespoke SEO/AEO overlay for all 35 priority Google Ads landing pages. */
declare(strict_types=1);

return array_replace(
    require __DIR__ . '/items_seo_courses.php',
    require __DIR__ . '/items_seo_tours.php',
);
