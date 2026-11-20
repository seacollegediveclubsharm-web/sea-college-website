<?php
/** Polish dictionary (`pl` — Polski) merged from modular sub-files. */
declare(strict_types=1);

return array_replace(
    require __DIR__ . '/pl_part1.php',
    require __DIR__ . '/pl_part2.php',
    (require __DIR__ . '/extra_banners_i18n.php')['pl'],
    (require __DIR__ . '/features_v2_i18n.php')['pl'],
);
