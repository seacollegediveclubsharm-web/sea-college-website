<?php
/**
 * Polish (`pl`) overlay for Sharm El Sheikh Dive Sites & 12-Month Red Sea Conditions Guide.
 */
declare(strict_types=1);

return [
    'aeo_answer' => 'Sharm El Sheikh (Szarm el-Szejk) leży na południowym krańcu Półwyspu Synaj, gdzie wody Zatoki Sueskiej i Zatoki Akaba spotykają się z otwartym Morzem Czerwonym, tworząc ponad 30 światowej klasy ścian koralowych, raf dryfowych i historycznych wraków w odległości od 5 do 90 minut od dzielnicy Al Montazah. Temperatura wody wynosi od 22 °C w lutym do 29 °C w sierpniu, a przejrzystość przez cały rok utrzymuje się na poziomie 20–40 m. Początkujący mogą bezpiecznie nurkować na spokojnych stanowiskach, takich jak rafa domowa Sea College w Al Montazah (2–18 m) czy Jackfish Alley w Ras Mohammed, natomiast certyfikowani nurkowie OWD i AOWD odkrywają Shark & Yolanda Reef, cztery rafy Cieśniny Tiran, wraki SS Dunraven, MV Million Hope oraz najsłynniejszy wrak II wojny światowej SS Thistlegorm (16–30 m).',

    'zones' => [
        'all'          => 'Wszystkie miejsca (10)',
        'local'        => 'Rafa Domowa i Lokalne',
        'tiran'        => 'Cieśnina Tiran',
        'ras-mohammed' => 'Park Ras Mohammed',
        'wrecks'       => 'Wraki Morza Czerwonego',
        'dahab'        => 'Wycieczka do Dahab',
    ],

    'sites' => [
        'house-reef' => [
            'level'   => 'Początkujący / Intro i wszystkie poziomy',
            'current' => 'Spokojnie / Osłonięta zatoka',
            'name'    => 'Rafa Domowa Al Montazah (Cyrene Sharm)',
            'summary' => 'Kilka kroków od naszego basenu szkoleniowego w Cyrene Sharm: piaszczyste wypłaszczenie treningowe (3–5 m) łagodnie opadające ku barwnym wieżom koralowym — całkowicie bez opłat parkowych (0 €).',
            'marine'  => ['Błazenki i ukwiały', 'Ogończe niebieskoplame', 'Skrzydlice', 'Mureny', 'Ławice antiasów'],
        ],
        'ras-nasrani' => [
            'level'   => 'Open Water / Snorkeling',
            'current' => 'Łagodny',
            'name'    => 'Ras Nasrani i pobliskie rafy Al Montazah',
            'summary' => 'Pięć minut od naszego pomostu w Al Montazah: olbrzymie gorgonie wachlarzowe, przydacznie i dziewicze płytkie ogrody koralowe — idealne na Check Dive oraz popołudniowe rejsy.',
            'marine'  => ['Gorgonie wachlarzowe', 'Rozdymki', 'Papugoryby', 'Żółwie zielone', 'Chromisy'],
        ],
        'jackson-reef' => [
            'level'   => 'Open Water / Advanced',
            'current' => 'Umiarkowany – nurkowanie w prądzie (drift)',
            'name'    => 'Jackson Reef i wrak Lara (Cieśnina Tiran)',
            'summary' => 'Najbardziej wysunięta na północ rafa w Cieśninie Tiran z widocznym na powierzchni wrakiem frachtowca MV Lara. Słynie z pionowych ścian porośniętych koralem ognistym i miękkim oraz letnich spotkań z rekinami młotami o świcie.',
            'marine'  => ['Rekiny młoty (lipiec–wrzesień)', 'Żółwie szylkretowe', 'Karanksy (trevally)', 'Ustniczki i pensetniki'],
        ],
        'thomas-gordon' => [
            'level'   => 'Open Water i wszystkie poziomy',
            'current' => 'Łagodny – umiarkowany',
            'name'    => 'Rafy Gordon, Thomas i Woodhouse (Tiran)',
            'summary' => 'Trzy kultowe rafy pośrodku Cieśniny Tiran między Synajem a wyspą Tiran: słoneczne płytkie plateau ze słynnymi beczkami (Gordon), głęboki kanion tektoniczny (Thomas) i ekscytujące ściany prądowe (Woodhouse).',
            'marine'  => ['Orlenie cętkowane', 'Żarłacze białopłetwe rafowe', 'Wargacze napoleońskie', 'Tuńczyki i barrakudy'],
        ],
        'million-hope' => [
            'level'   => 'Open Water Diver (od 18 m)',
            'current' => 'Słaby – umiarkowany',
            'name'    => 'Wrak masowca MV Million Hope (Nabq)',
            'summary' => 'Mierzący aż 175 metrów masowiec z 1996 roku w rezerwacie Nabq to największy wrak statku w Sharm El Sheikh — spoczywa w jasnej, płytkiej wodzie z dźwigiem portowym tuż obok, dzięki czemu jest dostępny już dla nurków OWD.',
            'marine'  => ['Ławice szpadelków (batfish)', 'Ryby krokodyle', 'Ślimaki nagoskrzelne', ' Przeźroczki (glassfish)', 'Kostery'],
        ],
        'shark-yolanda' => [
            'level'   => 'Open Water / Advanced',
            'current' => 'Umiarkowany – silny prąd (drift)',
            'name'    => 'Shark & Yolanda Reef (Park Narodowy Ras Mohammed)',
            'summary' => 'Regularnie zaliczane do TOP 10 miejsc nurkowych na świecie: skok w błękit przy Anemone City, dryf wzdłuż 700-metrowej pionowej ściany Shark Reef i finał wśród słynnych wanien oraz toalet cypryjskiego frachtowca Yolanda (1980).',
            'marine'  => ['Wielkie ławice barrakud i lucjanów', 'Olbrzymie mureny', 'Wargacze napoleońskie', 'Rekiny czarnopłetwe', 'Ściany miękkich koralowców'],
        ],
        'jackfish-alley' => [
            'level'   => 'Intro z łodzi / Open Water',
            'current' => 'Spokojny – łagodny',
            'name'    => 'Jackfish Alley i laguna Białej Wyspy (Ras Mohammed)',
            'summary' => 'Przeszyte promieniami słońca płytkie kawerny na 6–14 m i śnieżnobiała piaszczysta aleja patrolowana przez karanksy i płaszczki, tuż obok bajkowej mielizny Białej Wyspy.',
            'marine'  => ['Groty pełne przeźroczek', 'Ogończe niebieskoplame', 'Karanksy', 'Rogalnice (triggerfish)', 'Chmury antiasów'],
        ],
        'dunraven' => [
            'level'   => 'Open Water / Advanced',
            'current' => 'Łagodny – umiarkowany',
            'name'    => 'Wrak parowca SS Dunraven (Beacon Rock, 1876)',
            'summary' => 'Wiktoriański brytyjski parowiec żaglowy, który zatonął przy Beacon Rock w kwietniu 1876 roku. Spoczywa do góry stępką w dwóch częściach porośniętych koralowcami i wypełnionych tysiącami lśniących przeźroczek.',
            'marine'  => ['Katedra z ławic przeźroczek', 'Wielkie graniki', 'Skrzydlice', 'Szkaradnice (stonefish)', 'Barweny'],
        ],
        'thistlegorm' => [
            'level'   => 'Advanced (AOWD) + min. 20 nurkowań',
            'current' => 'Umiarkowany – silny',
            'name'    => 'Wrak SS Thistlegorm z II wojny światowej (Sha\'ab Ali)',
            'summary' => 'Numer 1 wśród wraków świata: 128-metrowy uzbrojony brytyjski frachtowiec zatopiony w październiku 1941 r. z ładunkiem motocykli BSA i Norton, ciężarówek Bedford, skrzydeł samolotów, lokomotyw parowych i dział przeciwlotniczych.',
            'marine'  => ['Motocykle i ciężarówki z 1941 r.', 'Olbrzymie szpadelki', 'Barrakudy', 'Ryby krokodyle', 'Skorpeny'],
        ],
        'dahab-blue-hole' => [
            'level'   => 'Advanced Open Water (AOWD)',
            'current' => 'Spokojne wejście z brzegu',
            'name'    => 'Dahab: Blue Hole, The Bells i Canyon',
            'summary' => 'Całodniowa wyprawa na północ Synaju do kultowego Dahab: zejście spektakularnym kominem skalnym The Bells wzdłuż kobaltowej ściany na siodło słynnego Blue Hole (w limitach rekreacyjnych do 30 m) oraz wulkaniczna szczelina Canyon.',
            'marine'  => ['Kobaltowa ściana Blue Hole', 'Wulkaniczna grota Canyon', 'Rożce (unicornfish)', 'Ośmiornice', 'Beduński obiad nad samym morzem'],
        ],
    ],

    'months' => [
        ['m' => 'Sty', 'suit' => 'Pianka długa 5 mm (+ żyleta)', 'note' => 'Krystaliczna zimowa przejrzystość, puste rafy i spotkania z orleniami'],
        ['m' => 'Lut', 'suit' => 'Pianka długa 5–7 mm',          'note' => 'Najchłodniejsza woda w roku (22 °C); słoneczne, bezwietrzne popołudnia'],
        ['m' => 'Mar', 'suit' => 'Pianka długa 5 mm',            'note' => 'Wiosenny zakwit planktonu przyciąga płaszczki i bogate życie makro'],
        ['m' => 'Kwi', 'suit' => 'Pianka długa 5 mm',            'note' => 'Idealny początek wiosny: ciepłe powietrze i świetne warunki na SS Thistlegorm'],
        ['m' => 'Maj', 'suit' => 'Pianka długa 3–5 mm',          'note' => 'Lustrzana tafla morza o poranku i pierwsze obserwacje rekinów wielorybich'],
        ['m' => 'Cze', 'suit' => 'Pianka 3 mm lub shorty',       'note' => 'Ogromne ławice lucjanów i barrakud gromadzące się na Shark Reef'],
        ['m' => 'Lip', 'suit' => 'Krótka 3 mm / rashguard',      'note' => 'Szczyt sezonu na rekiny młoty o świcie przy Jackson Reef (Tiran)'],
        ['m' => 'Sie', 'suit' => 'Krótka 3 mm / rashguard',      'note' => 'Najcieplejsza woda (29 °C) — wymarzona dla dzieci, kursów i nurkowań nocnych'],
        ['m' => 'Wrz', 'suit' => 'Pianka 3 mm długa lub shorty', 'note' => 'Woda jak w wannie (28 °C), widoczność do 40 m i łagodniejszy wiatr'],
        ['m' => 'Paź', 'suit' => 'Pianka długa 3–5 mm',          'note' => 'Ulubiony miesiąc polskich nurków: woda 27 °C i przejrzystość 35 m+'],
        ['m' => 'Lis', 'suit' => 'Pianka długa 5 mm',            'note' => 'Idealne 26 °C w wodzie i w powietrzu — znakomity miesiąc na kursy PADI'],
        ['m' => 'Gru', 'suit' => 'Pianka długa 5 mm',            'note' => 'Słoneczna ucieczka od polskiej zimy nad przejrzyste ściany koralowe'],
    ],

    'faqs' => [
        [
            'q' => 'W jakim miesiącu najlepiej nurkować w Sharm El Sheikh?',
            'a' => 'Sharm El Sheikh (Szarm el-Szejk) to całoroczny kierunek nurkowy. Za najbardziej komfortowe uchodzą wrzesień–listopad oraz kwiecień–czerwiec (woda 25–28 °C, powietrze 26–32 °C, widoczność 30–40 m). Lipiec i sierpień oferują najcieplejszą wodę (29 °C) oraz rekiny młoty o świcie na Jackson Reef, a zima jest świetną słoneczną odskocznią od chłodu w Polsce.',
        ],
        [
            'q' => 'Które rafy leżą najbliżej bazy Sea College Dive Club w Al Montazah?',
            'a' => 'Nasza baza na plaży w Cyrene Sharm (Al Montazah) leży dokładnie naprzeciwko Cieśniny Tiran! Rafa Domowa Al Montazah znajduje się 0 minut od brzegu, Ras Nasrani 5 minut, a słynne rafy Gordon, Thomas, Woodhouse i Jackson na Tiranie zaledwie 15–25 minut łodzią lub motorówką.',
        ],
        [
            'q' => 'Czy osoby początkujące bez certyfikatu mogą nurkować w Ras Mohammed lub na Tiranie?',
            'a' => 'Tak! Osoby bez żadnych wcześniejszych uprawnień mogą zarezerwować Discover Scuba Diving z łodzi (70 € za 1 nurkowanie lub 100 € za 2 nurkowania) z opieką instruktora PADI 1-na-1 na osłoniętych rafach Tiranu lub Ras Mohammed do 12 m, albo zacząć od rafy domowej za 30 €.',
        ],
        [
            'q' => 'Jakie uprawnienia trzeba mieć, aby zanurkować na wraku SS Thistlegorm?',
            'a' => 'Wrak SS Thistlegorm spoczywa na 16–30 m w rejonie Sha\'ab Ali, dlatego wymagany jest certyfikat PADI Advanced Open Water Diver (lub równoważny do 30 m) oraz minimum 20 zalogowanych nurkowań. Posiadacze stopnia Open Water (18 m) mogą natomiast swobodnie eksplorować potężny wrak MV Million Hope w Nabq.',
        ],
        [
            'q' => 'Kiedy obowiązują opłaty za park morski (5 €/dzień) i dopłata łodziowa (10 €/dzień)?',
            'a' => 'Pakiety dla certyfikowanych obejmują przewodnika PADI, butle 12 l, balast i transfer z hotelu. W dni wypłynięcia łodzią doliczana jest dopłata łodziowa 10 €/dzień (z ciepłym obiadem bufetowym i napojami na rejsach całodniowych) oraz opłata za park morski 5 €/dzień. Nurkowania na naszej Rafie Domowej Al Montazah są zwolnione z obu opłat (0 € łódź, 0 € park).',
        ],
    ],
];
