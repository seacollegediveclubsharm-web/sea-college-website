<?php
/**
 * Polish (`pl`) full catalog overlay: categories, groups, durations (32) & all 80 items.
 */
declare(strict_types=1);

return [
    'categories' => [
        'dive-packages' => [
            'label' => 'Pakiety Nurkowe',
            'notes' => [
                'Pakiety nurkowe są imienne (cena za osobę) i nie mogą być dzielone pomiędzy dwóch lub więcej nurków.',
                'W cenie: butla aluminiowa 12 l z powietrzem, pas balastowy i ołów, przewodnik nurkowy PADI, transfer z/do hotelu w Sharm El Sheikh, napoje na rejsach półdniowych oraz obiad i napoje na rejsach całodniowych.',
                'Cena nie obejmuje: dopłaty łodziowej (10 €/dzień), opłaty za park morski (5 €/dzień — wszystkie rejony oprócz rafy domowej House Reef), Nitroxu, ubezpieczenia, wypożyczenia sprzętu oraz dopłat za wraki, Zodiaka i nurkowania nocne.',
                'Prywatny przewodnik (75 €/dzień) jest obowiązkowy dla dzieci w wieku 10–11 lat oraz posiadaczy certyfikatu PADI Scuba Diver (limit 12 m).',
                'Rezerwacja online dostępna do 72 godzin przed aktywnością (48 h dla wraku Million Hope, Zodiaka i wycieczki do Dahab).',
            ],
        ],
        'padi-courses' => [
            'label' => 'Kursy PADI',
            'notes' => [
                'Wypożyczenie pełnego sprzętu nurkowego jest wliczone w cenę każdego kursu PADI.',
                'Opłata za oficjalny podręcznik eLearning i certyfikat PADI (85 € za kurs) nie jest wliczona — z wyjątkiem programu PADI Discover Scuba Diving (85 €), gdzie certyfikat jest już w cenie.',
                'Dzienne dopłaty łodziowe (10 €) i opłaty parkowe (5 €) podczas dni na łodzi nie są wliczone w cenę kursu.',
                'Rezerwacja online dostępna do 72 godzin przed rozpoczęciem szkolenia.',
            ],
        ],
        'offers' => [
            'label' => 'Pakiety Kursów',
            'notes' => [
                'Pakiety promocyjne zawierają podatek, wszystkie wymienione kursy PADI, wypożyczenie pełnego sprzętu oraz ubezpieczenie nurkowe.',
                'Opłata za podręcznik eLearning i certyfikat PADI (85 € za każdy kurs w pakiecie) płatna osobno.',
            ],
        ],
        'snorkeling' => [
            'label' => 'Snorkeling i Łodzie',
            'notes' => [
                'Wycieczki snorkelingowe obejmują maskę, kamizelkę asekuracyjną, transfery hotelowe oraz opłaty za park morski; całodniowe rejsy łodzią obejmują obiad w formie bufetu i napoje bezalkoholowe.',
                'Atrakcje rezerwowane bezpośrednio na miejscu rozliczane są według cennika standardowego.',
                'Rezerwacja online dostępna do 72 godzin przed wycieczką.',
            ],
        ],
    ],

    'groups' => [
        'intro-dives'         => 'Nurkowanie dla początkujących (Intro / Discover Scuba)',
        'refresh-dives'       => 'Nurkowania przypominające (Refresher)',
        'certified-packages'  => 'Pakiety dla certyfikowanych nurków — z łodzi lub z brzegu',
        'wreck-special-dives' => 'Dopłaty: Wraki, Zodiac, Nocne i Dahab',
        'daily-fees'          => 'Opłaty dzienne i prywatny przewodnik',
        'equipment-rental'    => 'Wypożyczalnia sprzętu i butli (za dzień)',
        'diving-insurance'    => 'Krótkoterminowe ubezpieczenie nurkowe',
        'kids-courses'        => 'Kursy PADI dla dzieci (od 8 lat)',
        'beginner-courses'    => 'Kursy PADI dla początkujących',
        'advanced-rescue'     => 'Kursy zaawansowane i ratownicze',
        'specialty-courses'   => 'Specjalizacje PADI',
        'go-pro'              => 'Szkolenia zawodowe (Go Pro)',
        'saver-bundles'       => 'Oszczędź w pakiecie kursów PADI',
        'snorkeling-trips'    => 'Wycieczki snorkelingowe',
        'private-speedboats'  => 'Prywatna motorówka (Speedboat)',
        'private-charters'    => 'Czarter prywatnego jachtu / łodzi (cały dzień)',
    ],

    'durations' => [
        '1h'          => '1 godz.',
        '1.5–2h'      => '1,5–2 godz.',
        '2h'          => '2 godz.',
        '2–3h'        => '2–3 godz.',
        '3h'          => '3 godz.',
        '3–4h'        => '3–4 godz.',
        '5–6h'        => '5–6 godz.',
        '7–8h'        => '7–8 godz.',
        '8h'          => '8 godz.',
        'Half day'    => 'Pół dnia',
        'Full day'    => 'Cały dzień',
        '1 day'       => '1 dzień',
        '1–2 days'    => '1–2 dni',
        '1.5–2 days'  => '1,5–2 dni',
        '2 days'      => '2 dni',
        '2–3 days'    => '2–3 dni',
        '2.5–3 days'  => '2,5–3 dni',
        '3 days'      => '3 dni',
        '3–3.5 days'  => '3–3,5 dnia',
        '3–4 days'    => '3–4 dni',
        '4 days'      => '4 dni',
        '4–5 days'    => '4–5 dni',
        '5 days'      => '5 dni',
        '6 days'      => '6 dni',
        '6–7 days'    => '6–7 dni',
        '7 days'      => '7 dni',
        '10 days'     => '10 dni',
        '14 days'     => '14 dni',
        '3 evenings'  => '3 wieczory',
        '4–6 weeks'   => '4–6 tygodni',
        'Per dive'    => 'Za nurkowanie',
        'Per day'     => 'Za dzień',
    ],

    'items' => array_replace(
        require __DIR__ . '/catalog_items_tours.php',
        require __DIR__ . '/catalog_items_courses.php'
    ),
];
