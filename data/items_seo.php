<?php
/**
 * Master SEO/AEO aggregator for all 80 catalog items + legacy WordPress /product/<slug> 301 map.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/i18n_data.php';

return sc_overlay_items_seo([
    'items' => array_replace(
        require __DIR__ . '/seo_dives_trips.php',
        require __DIR__ . '/seo_dives_extras.php',
        require __DIR__ . '/seo_courses.php',
        require __DIR__ . '/seo_boats.php',
    ),
    'hubs' => require __DIR__ . '/seo_hubs.php',
    'legacy_products' => [
        'padi-bubblemaker'                               => 'bubblemaker',
        'padi-seal-team'                                 => 'seal-team',
        'discover-scuba-diving'                          => 'dsd',
        'padi-discover-scuba-diving'                     => 'dsd',
        'discover-scuba-diving-2-intro'                  => 'dsd-2-boat',
        'padi-scuba-diver-course'                        => 'scuba-diver',
        'upgrade-from-scuba-diver-to-open-water-diver'   => 'upgrade-owd',
        'open-water-diver'                               => 'owd',
        'padi-open-water-diver'                          => 'owd',
        'padi-advanced-open-water-diver'                 => 'aowd',
        'advanced-open-water-diver'                      => 'aowd',
        'emergency-first-response'                       => 'efr',
        'padi-rescue-diver-course'                       => 'rescue',
        'padi-adaptive-support-diver'                    => 'adaptive',
        'padi-enriched-air-nitrox'                       => 'nitrox',
        '2-dives-specialty'                              => 'spec-2',
        'boat-diver'                                     => 'spec-2',
        'drift-diver'                                    => 'spec-2',
        'peak-performance-buoyancy'                      => 'spec-2',
        'underwater-navigator'                           => 'spec-3',
        'underwater-videographer'                        => 'spec-3',
        '3-dives-specialty'                              => 'spec-3',
        '4-dives-specialty'                              => 'deep',
        'padi-night-diver'                               => 'night',
        'padi-deep-diver'                                => 'deep',
        'padi-wreck-diver'                               => 'wreck',
        'sidemount-diver'                                => 'sidemount',
        'padi-dive-master-course'                        => 'divemaster',
        'padi-divemaster-program'                        => 'divemaster',
    ],
]);
