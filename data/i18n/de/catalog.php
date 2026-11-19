<?php
/** German (`de`) full catalog localization: 4 categories, 16 groups, 32 durations & 80 items. */
declare(strict_types=1);

return [
    'categories' => [
        'dive-packages' => [
            'label' => 'Tauchpakete & Ausflüge',
            'notes' => [
                'Tauchpakete sind personengebunden (Preis pro Taucher) und können nicht zwischen mehreren Personen aufgeteilt werden.',
                'Inklusive: 12L-Pressluftflasche, Bleigurt & Blei, PADI Tauchguide, Hoteltransfer in Sharm El Sheikh sowie Getränke auf Halbtagestouren bzw. Mittagsbuffet & Getränke auf Ganztagesbooten.',
                'Nicht enthalten: Täglicher Bootszuschlag (10 €/Tag), Nationalparkgebühr (5 €/Tag, alle Gebiete außer Hausriff), Nitrox, Versicherung, Leihausrüstung, Nacht-/Frühtauchgänge & Spezialzuschläge.',
                'Ein Privater Guide (75 €/Tag) ist für Kinder von 10–11 Jahren sowie für PADI Scuba Diver (12-m-Limit) verpflichtend.',
                'Online-Vorausbuchung bis 72 Stunden vor Aktivitätsbeginn (48 Std. für Million Hope, Zodiac und Dahab; kurzfristiger per WhatsApp).',
            ],
        ],
        'padi-courses' => [
            'label' => 'PADI Tauchkurse',
            'notes' => [
                'Die komplette Tauchausrüstung ist bei allen PADI Kursen bereits im Kurspreis enthalten.',
                'Offizielles PADI Lehrmaterial / eLearning & Brevetierungsgebühr (85 € pro Kurs) nicht enthalten — Ausnahme: beim PADI Discover Scuba Diving (85 €) bereits inklusive.',
                'Tägliche Boots- (10 €/Tag) und Nationalparkgebühren (5 €/Tag) bei Ausfahrten sind nicht im Kurspreis enthalten.',
                'Online-Buchung bis 72 Stunden vor Kursbeginn (für kurzfristige Termine schreiben Sie uns bitte per WhatsApp).',
            ],
        ],
        'offers' => [
            'label' => 'PADI Sparpakete',
            'notes' => [
                'Alle Kurs-Kombiangebote beinhalten sämtliche aufgeführten PADI Kurse, komplette Leihausrüstung, Tauchversicherung und Steuern.',
                'Offizielles PADI Lehrbuch / eLearning & Zertifizierungsgebühr (85 € pro Kurs) sind nicht im Paketpreis enthalten.',
            ],
        ],
        'snorkeling' => [
            'label' => 'Schnorcheln & Privatboote',
            'notes' => [
                'Schnorchelausflüge beinhalten Maske, Schwimmweste, Hoteltransfer in Sharm El Sheikh und Nationalparkgebühren; Ganztagesboote beinhalten zudem Mittagsbuffet & Softdrinks.',
                'Vor Ort spontan gebuchte Aktivitäten werden zum regulären Vor-Ort-Tarif abgerechnet.',
                'Online-Buchung bis 72 Stunden vor Beginn der Aktivität.',
            ],
        ],
    ],

    'groups' => [
        'intro-dives'         => 'Tauchen für Einsteiger (Schnuppertauchen)',
        'refresh-dives'       => 'Auffrischungstauchgänge (Refresher)',
        'certified-packages'  => 'Tauchpakete für zertifizierte Taucher — Boot oder Hausriff',
        'wreck-special-dives' => 'Wrack-, Zodiac-, Nacht- & Dahab-Zuschläge',
        'daily-fees'          => 'Tagesgebühren & Privater Guide',
        'equipment-rental'    => 'Leihausrüstung & Tauchflaschen (pro Tag)',
        'diving-insurance'    => 'Kurzzeit-Tauchversicherung',
        'kids-courses'        => 'PADI Kurse für Kinder (ab 8 Jahren)',
        'beginner-courses'    => 'PADI Einsteigerkurse',
        'advanced-rescue'     => 'Fortgeschrittene & Rettungstaucher',
        'specialty-courses'   => 'PADI Spezialkurse (Specialties)',
        'go-pro'              => 'Profi-Ausbildung (Go Pro)',
        'saver-bundles'       => 'Clever sparen mit PADI Kurs-Kombipaketen',
        'snorkeling-trips'    => 'Geführte Schnorchelausflüge',
        'private-speedboats'  => 'Private Schnellboot-Touren (Speedboat)',
        'private-charters'    => 'Privatyacht & Bootscharter (Ganztägig)',
    ],

    'durations' => [
        '1h'          => '1 Std.',
        '1.5–2h'      => '1,5–2 Std.',
        '2h'          => '2 Std.',
        '2–3h'        => '2–3 Std.',
        '3h'          => '3 Std.',
        '3–4h'        => '3–4 Std.',
        '5–6h'        => '5–6 Std.',
        '7–8h'        => '7–8 Std.',
        '8h'          => '8 Std.',
        'Half day'    => 'Halber Tag',
        'Full day'    => 'Ganzer Tag',
        '1 day'       => '1 Tag',
        '1–2 days'    => '1–2 Tage',
        '1.5–2 days'  => '1,5–2 Tage',
        '2 days'      => '2 Tage',
        '2–3 days'    => '2–3 Tage',
        '2.5–3 days'  => '2,5–3 Tage',
        '3 days'      => '3 Tage',
        '3–3.5 days'  => '3–3,5 Tage',
        '3–4 days'    => '3–4 Tage',
        '4 days'      => '4 Tage',
        '4–5 days'    => '4–5 Tage',
        '5 days'      => '5 Tage',
        '6 days'      => '6 Tage',
        '6–7 days'    => '6–7 Tage',
        '7 days'      => '7 Tage',
        '10 days'     => '10 Tage',
        '14 days'     => '14 Tage',
        '3 evenings'  => '3 Abende',
        '4–6 weeks'   => '4–6 Wochen',
        'Per dive'    => 'Pro Tauchgang',
        'Per day'     => 'Pro Tag',
    ],

    'items' => array_replace(
        require __DIR__ . '/catalog_dives.php',
        require __DIR__ . '/catalog_courses_boats.php',
    ),
];
