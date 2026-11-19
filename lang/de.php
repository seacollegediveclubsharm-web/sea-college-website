<?php
/** German dictionary (`de` — Deutsch) for Sea College Dive Club. */
declare(strict_types=1);

return array_replace(
    require __DIR__ . '/de_part1.php',
    require __DIR__ . '/de_part2.php',
    (require __DIR__ . '/extra_banners_i18n.php')['de'],
);
