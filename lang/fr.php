<?php
/** French dictionary (fr — Français) merged from modular sub-files. */
declare(strict_types=1);

return array_replace(
    require __DIR__ . '/fr_part1.php',
    require __DIR__ . '/fr_part2.php',
    (require __DIR__ . '/extra_banners_i18n.php')['fr'],
);
