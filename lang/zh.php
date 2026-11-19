<?php
/** Simplified Chinese dictionary (`zh` — 中文) merged from modular parts. */
declare(strict_types=1);

return array_replace(
    require __DIR__ . '/zh_part1.php',
    require __DIR__ . '/zh_part2.php',
);
