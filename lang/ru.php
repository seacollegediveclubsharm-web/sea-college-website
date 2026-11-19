<?php
/** Russian dictionary (`ru` — Русский), merged from modular parts (<400 lines each). */
declare(strict_types=1);

return array_replace(
    require __DIR__ . '/ru_part1.php',
    require __DIR__ . '/ru_part2.php',
    (require __DIR__ . '/extra_banners_i18n.php')['ru'],
);
