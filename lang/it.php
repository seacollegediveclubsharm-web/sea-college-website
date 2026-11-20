<?php
/**
 * Italian (`it` — Italiano) master UI dictionary for Sea College Dive Club.
 * Merges modular parts to match all keys of lang/en.php with 0 missing keys.
 */
declare(strict_types=1);

return array_replace(
    require __DIR__ . '/it_part1.php',
    require __DIR__ . '/it_part2.php',
    (require __DIR__ . '/extra_banners_i18n.php')['it'],
    (require __DIR__ . '/features_v2_i18n.php')['it'],
);
