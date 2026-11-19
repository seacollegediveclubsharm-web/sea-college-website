<?php
/** German (`de`) Dive Sites Map & 12-Month Red Sea Conditions Guide. */
declare(strict_types=1);

return [
    'aeo_answer' => 'Sharm El Sheikh liegt an der Südspitze der Sinai-Halbinsel, wo der Golf von Suez und der Golf von Akaba auf das offene Rote Meer treffen — mit über 30 erstklassigen Steilwänden, Strömungsriffen und historischen Schiffswracks in 5 bis 90 Minuten Entfernung ab Al Montazah. Die Wassertemperaturen liegen ganzjährig zwischen 22 °C im Februar und 29 °C im August bei 20–40 m Sichtweite. Einsteiger genießen ruhige Plätze wie das Hausriff des Sea College in Al Montazah (2–18 m) oder die Jackfish Alley, während Open-Water- und Advanced-Taucher das Shark & Yolanda Reef, die vier Riffe der Straße von Tiran, die SS Dunraven, die Million Hope und das legendäre WK2-Wrack SS Thistlegorm (16–30 m) betauchen.',

    'zones' => [
        'all'          => 'Alle Tauchplätze (10)',
        'local'        => 'Hausriff & Lokale Riffe',
        'tiran'        => 'Straße von Tiran',
        'ras-mohammed' => 'Nationalpark Ras Mohammed',
        'wrecks'       => 'Berühmte Schiffswracks',
        'dahab'        => 'Tagesausflug Dahab',
    ],

    'sites' => [
        'house-reef' => [
            'level'   => 'Einsteiger / Schnuppertauchen & Alle Level',
            'current' => 'Ruhig / Geschützt',
            'name'    => 'Hausriff Al Montazah (Cyrene Sharm)',
            'summary' => 'Nur wenige Schritte von unserem Ausbildungspool im Cyrene Sharm entfernt: ein sandiges 3–5-m-Übungsplateau, das sanft zu farbenfrohen Korallenblöcken abfällt — komplett ohne Nationalparkgebühr.',
            'marine'  => ['Clownfische & Anemonen', 'Blaupunktrochen', 'Rotfeuerfische', 'Riesenmuränen', 'Schwärme von Fahnenbarschen'],
        ],
        'ras-nasrani' => [
            'level'   => 'Open Water / Schnorcheln',
            'current' => 'Leicht',
            'name'    => 'Ras Nasrani & Nahegelegene Riffe (Al Montazah)',
            'summary' => 'Nur fünf Bootsminuten von unserem Steg in Al Montazah: riesige Gorgonien-Fächerkorallen, Riesenmuscheln und intakte flache Korallengärten — ideal für Check Dives und entspannte Ausfahrten.',
            'marine'  => ['Gorgonien-Fächerkorallen', 'Kugelfische', 'Papageifische', 'Grüne Meeresschildkröten', 'Schwalbenschwänzchen'],
        ],
        'jackson-reef' => [
            'level'   => 'Open Water / Advanced',
            'current' => 'Mittel – Strömungstauchgang',
            'name'    => 'Jackson Reef & Wrack der Lara (Tiran)',
            'summary' => 'Das nördlichste Riff in der Straße von Tiran, markiert durch das aufgelaufene Frachtschiff MV Lara. Weltbekannt für prachtvoll bewachsene Steilwände aus Feuer- und Weichkorallen sowie Hammerhai-Sichtungen im Frühsommer.',
            'marine'  => ['Bogenstirn-Hammerhaie (Juli–Sept.)', 'Echte Karettschildkröten', 'Stachelmakrelen', 'Rotmeer-Wimpelfische'],
        ],
        'thomas-gordon' => [
            'level'   => 'Open Water & Alle Erfahrungsstufen',
            'current' => 'Leicht – Mittel',
            'name'    => 'Gordon, Thomas & Woodhouse Reef (Straße von Tiran)',
            'summary' => 'Drei legendäre Mittelriffe zwischen Sinai und der Insel Tiran: sonnendurchflutete Plateaus mit den berühmten Ölfässern (Gordon), tiefe Schluchten (Thomas) und genussvolle Drift-Wände (Woodhouse).',
            'marine'  => ['Adlerrochen', 'Weißspitzen-Riffhaie', 'Napoleon-Lippfische', 'Thunfische & Barrakudas'],
        ],
        'million-hope' => [
            'level'   => 'Open Water Diver (bis 18 m+)',
            'current' => 'Gering – Mittel',
            'name'    => 'Wrack der MV Million Hope (Nabq)',
            'summary' => 'Mit 175 Metern Länge ist der 1996 gestrandete Massengutfrachter Million Hope bei Nabq das größte Schiffswrack in Sharm El Sheikh — dank 0–22 m Tiefe lichtdurchflutet und schon für Open-Water-Taucher betauchbar.',
            'marine'  => ['Fledermausfisch-Schwärme', 'Krokodilfische', 'Bunte Nacktschnecken', 'Glasfische', 'Kofferfische'],
        ],
        'shark-yolanda' => [
            'level'   => 'Open Water / Advanced',
            'current' => 'Mittel – Kräftige Strömung',
            'name'    => 'Shark & Yolanda Reef (Nationalpark Ras Mohammed)',
            'summary' => 'Einer der Top-10-Tauchplätze weltweit: Sprung ins tiefe Blau bei Anemone City, Drift entlang der 700 Meter abfallenden Steilwand des Shark Reef und Finale zwischen den berühmten Toiletten und Badewannen des Frachters Yolanda (1980).',
            'marine'  => ['Große Barrakuda- & Schnapperschwärme', 'Riesenmuränen', 'Napoleon-Lippfische', 'Schwarzspitzen-Riffhaie', 'Üppige Weichkorallenwände'],
        ],
        'jackfish-alley' => [
            'level'   => 'Boots-Schnuppertauchen / Open Water',
            'current' => 'Ruhig – Leicht',
            'name'    => 'Jackfish Alley & Lagune der White Island',
            'summary' => 'Lichtdurchflutete flache Grotten auf 6–14 m Tiefe und eine schneeweiße Sandstraße mit Makrelen und Stechrochen, direkt neben der berühmten Sandbank White Island in Ras Mohammed.',
            'marine'  => ['Glasfisch-Grotten', 'Blaupunkt-Stechrochen', 'Stachelmakrelen', 'Drückerfische', 'Juwelen-Fahnenbarsche'],
        ],
        'dunraven' => [
            'level'   => 'Open Water / Advanced',
            'current' => 'Leicht – Mittel',
            'name'    => 'Historisches Wrack SS Dunraven (Beacon Rock, 1876)',
            'summary' => 'Britisches Dampfsegelschiff aus der Viktorianischen Ära, das im April 1876 am Beacon Rock sank und kieloben in zwei korallenbewachsenen Hälften liegt — gefüllt mit tausenden glitzernden Glasfischen.',
            'marine'  => ['Kathedrale aus Glasfischen', 'Riesen-Zackenbarsche', 'Rotfeuerfische', 'Steinfische', 'Meerbarben'],
        ],
        'thistlegorm' => [
            'level'   => 'Advanced Open Water + 20 Tauchgänge',
            'current' => 'Mittel – Kräftig',
            'name'    => 'WK2-Wrack SS Thistlegorm (Sha\'ab Ali)',
            'summary' => 'Der berühmteste Wracktauchgang der Welt: Ein 128 m langes britisches Versorgungsschiff, im Oktober 1941 versenkt, mit erhaltenen BSA-Motorrädern, Bedford-LKWs, Flugzeugtragflächen, Dampflokomotiven und Flakgeschützen.',
            'marine'  => ['Original WK2-Motorräder & LKWs', 'Große Fledermausfische', 'Barrakudas', 'Krokodilfische', 'Drachenköpfe'],
        ],
        'dahab-blue-hole' => [
            'level'   => 'Advanced Open Water Diver',
            'current' => 'Ruhiger Landeinstieg',
            'name'    => 'Dahab Blue Hole, The Bells & Canyon',
            'summary' => 'Ganztägiges Sinai-Abenteuer Richtung Norden nach Dahab: Abstieg durch den spektakulären Felsschacht „The Bells“ entlang einer tiefblauen Steilwand bis zum Sattel des weltberühmten Blue Hole, kombiniert mit dem vulkanischen Canyon.',
            'marine'  => ['Tiefblauer Steilwand-Abhang', 'Glasfische in der Canyon-Grotte', 'Nasenfische', 'Oktopusse', 'Frisches Beduinen-Mittagessen am Meer'],
        ],
    ],

    'months' => [
        0  => ['m' => 'Jan', 'suit' => '5 mm Lang (+ Eisweste)', 'note' => 'Kristallklare Wintersichtweiten, ruhige Riffe & Adlerrochen'],
        1  => ['m' => 'Feb', 'suit' => '5–7 mm Langarm',         'note' => 'Kühlster Wassermonat (22 °C); sonnige, windstille Nachmittage'],
        2  => ['m' => 'Mär', 'suit' => '5 mm Langarm',           'note' => 'Frühlingsbeginn lockt Rochen und spannende Makrobewohner an'],
        3  => ['m' => 'Apr', 'suit' => '5 mm Langarm',           'note' => 'Ideale Nebensaison: warme Luft & perfekte Bedingungen für die Thistlegorm'],
        4  => ['m' => 'Mai', 'suit' => '3–5 mm Langarm',         'note' => 'Spiegelglatte Morgenstunden & erste Chancen auf Walhaie'],
        5  => ['m' => 'Jun', 'suit' => '3 mm Lang oder Shorty',  'note' => 'Riesige Schnapper- und Barrakudaschwärme am Shark Reef'],
        6  => ['m' => 'Jul', 'suit' => '3 mm Shorty / Rashguard','note' => 'Hauptsaison für frühe Hammerhai-Sichtungen am Jackson Reef (Tiran)'],
        7  => ['m' => 'Aug', 'suit' => '3 mm Shorty / Rashguard','note' => 'Wärmstes Wasser des Jahres (29 °C) — traumhaft für Kinder & Nachttauchgänge'],
        8  => ['m' => 'Sep', 'suit' => '3 mm Lang oder Shorty',  'note' => 'Badewannenwarmes Meer bei bis zu 40 m Sichtweite & angenehmer Brise'],
        9  => ['m' => 'Okt', 'suit' => '3–5 mm Langarm',         'note' => 'Lieblingsmonat vieler Taucher: 27 °C Wasser & 35 m+ Sichtweite'],
        10 => ['m' => 'Nov', 'suit' => '5 mm Langarm',           'note' => 'Perfekte 26 °C Luft & Wasser — beste Reisezeit für PADI Tauchkurse'],
        11 => ['m' => 'Dez', 'suit' => '5 mm Langarm',           'note' => 'Sonnige Winterflucht aus Europa mit glasklaren Korallenwänden'],
    ],

    'faqs' => [
        [
            'q' => 'Welcher Monat ist die beste Reisezeit zum Tauchen in Sharm El Sheikh?',
            'a' => 'Sharm El Sheikh ist ein echtes Ganzjahres-Tauchziel. Besonders beliebt bei Tauchern aus Deutschland, Österreich und der Schweiz sind April bis Juni sowie September bis November mit 25–28 °C Wassertemperatur, angenehmen 26–32 °C Lufttemperatur und 30–40 m Sichtweite. Juli und August bieten 29 °C warmes Wasser und Hammerhai-Saison am Jackson Reef.',
        ],
        [
            'q' => 'Welche Tauchplätze liegen am nächsten zur Sea College Tauchbasis in Al Montazah?',
            'a' => 'Unsere Strandbasis im Cyrene Sharm (Al Montazah) liegt direkt gegenüber der Insel Tiran! Unser Hausriff liegt 0 Minuten vor der Tür, Ras Nasrani ist 5 Bootsminuten entfernt, und die berühmten vier Riffe der Straße von Tiran (Gordon, Thomas, Woodhouse & Jackson Reef) erreichen wir in nur 15–25 Minuten.',
        ],
        [
            'q' => 'Können auch Anfänger ohne Tauchschein in Ras Mohammed oder vor Tiran tauchen?',
            'a' => 'Ja! Einsteiger ohne Vorkenntnisse können „Discover Scuba Diving vom Boot“ (70 € für 1 Tauchgang oder 100 € für 2 Tauchgänge) buchen und Hand in Hand mit einem PADI Tauchlehrer an geschützten Riffen in Ras Mohammed oder Tiran bis maximal 12 m tauchen — oder für 30 € direkt an unserem ruhigen Hausriff starten.',
        ],
        [
            'q' => 'Welchen Tauchschein benötige ich für die SS Thistlegorm?',
            'a' => 'Für die SS Thistlegorm (16–30 m Tiefe bei Sha\'ab Ali) setzen wir mindestens PADI Advanced Open Water Diver (bzw. CMAS** / äquivalente 30-m-Zertifizierung) und 20 geloggte Tauchgänge voraus. Open-Water-Taucher (bis 18 m) können das beeindruckende, flachere Wrack der MV Million Hope bei Nabq betauchen.',
        ],
        [
            'q' => 'Wann fallen die 10 € Bootszuschlag und 5 € Nationalparkgebühr an?',
            'a' => 'Unsere Tauchpakete beinhalten Guide, 12L-Flaschen, Blei und Hoteltransfer. Nur an Tagen, an denen Sie vom Tauchboot aus tauchen, kommen 10 €/Tag Bootszuschlag (inkl. warmem Mittagsbuffet & Getränken auf Ganztagesbooten) und 5 €/Tag Nationalparkgebühr hinzu. Am Hausriff in Al Montazah zahlen Sie 0 € Boots- und 0 € Parkgebühr.',
        ],
    ],
];
