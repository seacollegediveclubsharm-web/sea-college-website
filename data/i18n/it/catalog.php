<?php
/**
 * Italian (`it`) catalog overlay: 4 categories, 16 groups, 32 durations & all 80 items.
 */
declare(strict_types=1);

return [
    'categories' => [
        'dive-packages' => [
            'label' => 'Pacchetti Immersioni',
            'notes' => [
                'I pacchetti immersioni sono strettamente personali (prezzo a persona) e non possono essere divisi tra più subacquei.',
                'Incluso nel prezzo: bombola aria da 12L, cintura e piombi, guida PADI, transfer hotel A/R a Sharm El Sheikh, bevande nelle uscite di mezza giornata; pranzo a buffet e bevande nelle uscite di giornata intera.',
                'Non incluso: Supplemento Barca (€10/giorno), Tassa Parco Marino (€5/giorno, tutte le zone tranne l’House Reef), Nitrox, assicurazione sub, noleggio attrezzatura, immersioni notturne/alba e supplementi relitti/gommone.',
                'La guida privata (€75/giorno) è obbligatoria per i bambini di 10–11 anni e per i brevettati PADI Scuba Diver (limite 12 m).',
                'Prenotazione online fino a 72 ore prima dell’attività (48 ore per Million Hope, Zodiac e Dahab).',
            ],
        ],
        'padi-courses' => [
            'label' => 'Corsi PADI',
            'notes' => [
                'Il noleggio completo dell’attrezzatura subacquea è incluso nel prezzo di tutti i corsi PADI.',
                'Manuale didattico e tassa di certificazione PADI (€85 per corso) non inclusi — ad eccezione del programma PADI Discover Scuba Diving dove sono già inclusi.',
                'I supplementi giornalieri barca (€10/giorno) e parco marino (€5/giorno) per le uscite in mare non sono inclusi nel prezzo del corso.',
                'Prenotazione online disponibile fino a 72 ore prima dell’inizio del corso.',
            ],
        ],
        'offers' => [
            'label' => 'Offerte Corsi Combinati',
            'notes' => [
                'I pacchetti offerta includono tasse, tutti i corsi elencati, il noleggio completo dell’attrezzatura e l’assicurazione subacquea.',
                'Manuale didattico e tassa di certificazione PADI (€85 per ciascun corso) non inclusi.',
            ],
        ],
        'snorkeling' => [
            'label' => 'Snorkeling & Barche Private',
            'notes' => [
                'Le escursioni snorkeling includono maschera, giubbotto salvagente, transfer hotel e tasse parco marino; le crociere di giornata intera includono pranzo a buffet e bevande analcoliche.',
                'Le attività prenotate direttamente in loco senza pre-booking seguono il listino standard.',
                'Prenotazione online disponibile fino a 72 ore prima dell’escursione.',
            ],
        ],
    ],

    'groups' => [
        'intro-dives'         => 'Immersioni per Principianti (Battesimo del Mare)',
        'refresh-dives'       => 'Immersioni di Ripasso (Check Dive & Scuba Review)',
        'certified-packages'  => 'Pacchetti Sub Brevettati — in Barca o da Riva',
        'wreck-special-dives' => 'Supplementi Relitti, Gommone Zodiac, Notturna & Dahab',
        'daily-fees'          => 'Supplementi Giornalieri & Guida Privata',
        'equipment-rental'    => 'Noleggio Attrezzatura & Bombole (al giorno)',
        'diving-insurance'    => 'Assicurazione Subacquea Temporanea',
        'kids-courses'        => 'Corsi PADI per Bambini (dagli 8 anni)',
        'beginner-courses'    => 'Corsi PADI per Principianti',
        'advanced-rescue'     => 'Corsi Avanzati, Primo Soccorso & Rescue',
        'specialty-courses'   => 'Corsi di Specialità PADI',
        'go-pro'              => 'Corsi Professionali (Go Pro)',
        'saver-bundles'       => 'Risparmia con i Pacchetti Corsi PADI',
        'snorkeling-trips'    => 'Escursioni Snorkeling Guidate',
        'private-speedboats'  => 'Snorkeling in Motoscafo Privato',
        'private-charters'    => 'Noleggio Barca & Yacht Privato (Giornata Intera)',
    ],

    'durations' => [
        '1h'          => '1 ora',
        '1.5–2h'      => '1,5–2 ore',
        '2h'          => '2 ore',
        '2–3h'        => '2–3 ore',
        '3h'          => '3 ore',
        '3–4h'        => '3–4 ore',
        '5–6h'        => '5–6 ore',
        '7–8h'        => '7–8 ore',
        '8h'          => '8 ore',
        'Half day'    => 'Mezza giornata',
        'Full day'    => 'Giornata intera',
        '1 day'       => '1 giorno',
        '1–2 days'    => '1–2 giorni',
        '1.5–2 days'  => '1,5–2 giorni',
        '2 days'      => '2 giorni',
        '2–3 days'    => '2–3 giorni',
        '2.5–3 days'  => '2,5–3 giorni',
        '3 days'      => '3 giorni',
        '3–3.5 days'  => '3–3,5 giorni',
        '3–4 days'    => '3–4 giorni',
        '4 days'      => '4 giorni',
        '4–5 days'    => '4–5 giorni',
        '5 days'      => '5 giorni',
        '6 days'      => '6 giorni',
        '6–7 days'    => '6–7 giorni',
        '7 days'      => '7 giorni',
        '10 days'     => '10 giorni',
        '14 days'     => '14 giorni',
        '3 evenings'  => '3 sere',
        '4–6 weeks'   => '4–6 settimane',
        'Per dive'    => 'A immersione',
        'Per day'     => 'Al giorno',
    ],

    'items' => array_replace(
        require __DIR__ . '/catalog_items_dives.php',
        require __DIR__ . '/catalog_items_courses_boats.php',
    ),
];
