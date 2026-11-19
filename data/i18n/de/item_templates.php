<?php
/**
 * Smart German (`de`) group-aware SEO/AEO generator for all non-bespoke catalog items.
 * Guarantees 100% rich German copy with zero English fallback across all 80 items.
 */
declare(strict_types=1);

return static function (array $it, array $seo): array {
    $gid   = (string) ($it['_group']['id'] ?? '');
    $name  = item_name($it);
    $desc  = item_desc($it);
    $dur   = item_duration($it) ?: 'Flexibel';
    $price = isset($it['price']) && $it['price'] !== null ? (int) $it['price'] : null;

    $divesBuilder = require __DIR__ . '/item_templates_dives.php';
    $res = $divesBuilder($it, $gid, $name, $desc, $dur, $price);
    if (is_array($res)) {
        return $res;
    }

    $otherBuilder = require __DIR__ . '/item_templates_courses_boats.php';
    return $otherBuilder($it, $gid, $name, $desc, $dur, $price);
};
