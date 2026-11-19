<?php
/**
 * Italian (`it` — Italiano) master UI dictionary for Sea College Dive Club.
 * Merges modular part 1 + part 2 to match all 443 keys of lang/en.php with 0 missing/extra keys.
 */
declare(strict_types=1);

return array_replace(
    require __DIR__ . '/it_part1.php',
    require __DIR__ . '/it_part2.php',
);
