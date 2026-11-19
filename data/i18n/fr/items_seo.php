<?php
/**
 * Master French (fr) bespoke SEO & AEO landing pages loader (35 top Google Ads items).
 */
declare(strict_types=1);

return array_replace(
    require __DIR__ . '/items_seo_courses.php',
    require __DIR__ . '/items_seo_tours.php',
);
