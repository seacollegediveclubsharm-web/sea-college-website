<?php
/**
 * Polish (`pl`) group-aware SEO/AEO template builder — Courses, Snorkeling, Speedboats & Private Charters.
 * Returns a static closure invoked by data/i18n/pl/item_templates.php.
 */
declare(strict_types=1);

return static function (array $it, string $gid, string $name, string $desc, string $dur, string $priceLabel): ?array {
    $id    = (string) ($it['id'] ?? '');
    $price = $it['price'] ?? null;

    if (in_array($gid, ['beginner-courses', 'advanced-rescue', 'specialty-courses', 'kids-courses', 'go-pro', 'saver-bundles'], true)) {
        $minAge = str_contains($desc, '15') ? 'Ukończone 15 lat' : (str_contains($desc, '12') ? 'Ukończone 12 lat' : 'Od 10 lat (lub zgodnie ze standardem PADI)');
        return [
            'h1'         => "{$name} w Sharm El Sheikh — Autoryzowana Baza PADI 5 Gwiazdek",
            'meta_title' => "{$name} Sharm El Sheikh | {$priceLabel} | Sea College PADI 5★",
            'meta_desc'  => "Zrób szkolenie {$name} w Sharm El Sheikh (Szarm el-Szejk) za {$priceLabel} ({$dur}). {$desc} Kompletny sprzęt nurkowy i opieka instruktora PADI w cenie.",
            'aeo_answer' => "Szkolenie {$name} w Sea College Dive Club (autoryzowanym centrum PADI 5 Gwiazdek w Cyrene Sharm, Al Montazah, Sharm El Sheikh) kosztuje {$priceLabel} za osobę i trwa {$dur}. {$desc} W cenę wliczone jest wypożyczenie pełnego sprzętu nurkowego oraz prowadzenie przez wielojęzycznego instruktora PADI (+85 € opłata za oficjalny podręcznik i certyfikat PADI).",
            'overview'   => [
                "Realizacja kursu {$name} w ciepłych, przejrzystych wodach Morza Czerwonego w Sharm El Sheikh pozwala skupić się w 100% na technice, pływalności i przyjemności z nurkowania zamiast walki z zimnem i słabą widocznością.",
                "Zajęcia prowadzone są w kameralnych grupach na bazie naszego basenu szkoleniowego, osłoniętej rafy domowej Al Montazah oraz codziennych rejsów łodziowych przez instruktorów ocenianych na 4.9★ w ponad 3 082 opiniach Google.",
            ],
            'specs' => [
                ['Cena szkolenia', "{$priceLabel} za osobę (pełny sprzęt nurkowy w cenie)"],
                ['Opłata PADI', '+85 € oficjalny podręcznik eLearning i wydanie certyfikatu PADI'],
                ['Czas trwania', $dur !== '' ? $dur : '1–2 dni'],
                ['Wymagania wiekowe', $minAge],
                ['Miejsce szkolenia', 'Sea College Dive Club — Cyrene Sharm, Al Montazah (Sharm El Sheikh)'],
            ],
            'steps' => [
                ['Wprowadzenie teoretyczne i planowanie', 'Omówienie celów szkoleniowych, procedur bezpieczeństwa oraz konfiguracji sprzętu z instruktorem PADI.'],
                ['Praktyczne ćwiczenia kierunkowe', 'Krok po kroku szlifujesz kluczowe techniki specjalizacji pod bezpośrednim okiem instruktora.'],
                ['Nurkowania zaliczeniowe w Morzu Czerwonym', 'Zastosowanie zdobytych umiejętności na żywej rafie koralowej przy widoczności 25–35 m.'],
                ['Podsumowanie i certyfikacja PADI eCard', 'Wpis i pieczątka PADI 5 Star Resort w logbooku oraz wydanie międzynarodowego certyfikatu PADI!'],
            ],
            'included' => [
                "Pełny program szkoleniowy PADI: {$name}",
                'Wypożyczenie kompletnego sprzętu nurkowego na wszystkie zajęcia kursu',
                'Opieka certyfikowanego instruktora PADI w kameralnej grupie',
                'Transfery z i do hotelu na terenie Sharm El Sheikh',
            ],
            'excluded' => [
                'Oficjalny podręcznik PADI eLearning oraz opłata certyfikacyjna (85 € za kurs)',
                'Dzienne dopłaty łodziowe (10 €/dzień) i parkowe (5 €/dzień) w przypadku realizacji z łodzi',
                'Krótkoterminowe ubezpieczenie nurkowe (dostępne na miejscu)',
            ],
            'ideal_for' => [
                'Certyfikowani nurkowie chcący świadomie rozwinąć warsztat podwodny w ciepłych wodach Egiptu',
                'Osoby zbierające 5 specjalizacji do prestiżowego stopnia PADI Master Scuba Diver',
                'Urlopowicze mający 1–2 dni na wartościowy kurs bez rezygnowania z wypoczynku',
            ],
            'faqs' => [
                ['q' => "Ile trwa {$name} w Sharm El Sheikh?", 'a' => "Całe szkolenie zajmuje zazwyczaj {$dur}, dzięki czemu łatwo wkomponujesz je w tygodniowy pobyt."],
                ['q' => 'Czy muszę przywozić z Polski własny sprzęt nurkowy?', 'a' => 'Nie — kompletny, regularnie serwisowany sprzęt nurkowy jest wliczony w cenę każdego kursu PADI w Sea College.'],
                ['q' => 'Czy specjalizacja wlicza się do stopnia PADI Master Scuba Diver?', 'a' => 'Tak! Każda oficjalna specjalizacja PADI przybliża Cię do najwyższego stopnia rekreacyjnego Master Scuba Diver.'],
            ],
        ];
    }

    if ($gid === 'snorkeling-trips') {
        $isNight = $id === 'snork-beach-night';
        return [
            'h1'         => "{$name} w Sharm El Sheikh — Wycieczka Snorkelingowa z Przewodnikiem",
            'meta_title' => "{$name} Sharm El Sheikh | {$priceLabel} | Sea College",
            'meta_desc'  => "Zarezerwuj wycieczkę {$name} w Sharm El Sheikh (Szarm el-Szejk) za {$priceLabel} ({$dur}). {$desc}",
            'aeo_answer' => "Wycieczka {$name} z bazą Sea College Dive Club w Sharm El Sheikh kosztuje {$priceLabel} i trwa {$dur}. {$desc} Obejmuje profesjonalną opiekę przewodnika w wodzie, maskę, kamizelkę asekuracyjną, opłaty parkowe oraz klimatyzowany transfer w obie strony spod hotelu w Sharm El Sheikh.",
            'overview'   => [
                "Ogrody koralowe Morza Czerwonego wokół Sharm El Sheikh (Szarm el-Szejk) zaczynają się tuż pod powierzchnią wody, dzięki czemu podczas programu {$name} zobaczysz setki gatunków kolorowych ryb rafowych bez konieczności zakładania butli nurkowej.",
                $isNight
                    ? 'Po zachodzie słońca w świetle latarki podwodnej LED rafa odsłania zupełnie inne oblicze: polujące skrzydlice, ośmiornice, jeżowce ołówkowe i intensywne czerwone barwy koralowców.'
                    : 'Nasz doświadczony przewodnik towarzyszy grupie bezpośrednio w wodzie z kołem asekuracyjnym, dbając o komfort dzieci, rodzin oraz osób słabiej pływających.',
            ],
            'specs' => [
                ['Cena biletu', $priceLabel],
                ['Czas trwania', $dur],
                ['Sprzęt w cenie', $isNight ? 'Latarka podwodna LED, maska i kamizelka asekuracyjna' : 'Maska do snorkelingu i kamizelka asekuracyjna'],
                ['Transfery i opłaty', 'Odbiór i powrót do hotelu w Sharm El Sheikh oraz opłaty parkowe w cenie'],
                ['Wymagane doświadczenie', 'Brak — dostępne kamizelki wypornościowe dla dorosłych i dzieci'],
            ],
            'steps' => [
                ['Odbiór klimatyzowanym busem z hotelu', 'Wygodny przejazd spod recepcji Twojego hotelu w Sharm El Sheikh na miejsce startu wycieczki.'],
                ['Dobór maski, kamizelki i odprawa', 'Dopasowanie sprzętu oraz krótkie omówienie zasad bezpiecznego pływania nad rafą koralową (zakaz dotykania korali!).'],
                ['Snorkeling z przewodnikiem nad rafą', 'Odkrywanie barwnych koralowców, błazenków, papugoryb i płaszczek pod stałą opieką naszego przewodnika.'],
                ['Odpoczynek i powrót do hotelu', 'Czas na relaks, pamiątkowe zdjęcia oraz odwiezienie prosto do Twojego resortu.'],
            ],
            'included' => [
                'Klimatyzowany transfer w obie strony z dowolnego hotelu w Sharm El Sheikh',
                'Maska do snorkelingu oraz kamizelka asekuracyjna' . ($isNight ? ' + latarka podwodna LED' : ''),
                'Opieka przewodnika Sea College w wodzie oraz bilety wstępu do strefy chronionej',
                str_contains(strtolower($it['name'] ?? ''), 'boat') ? 'Świeży obiad w formie bufetu i napoje bezalkoholowe na jachcie' : 'Organizacja całej trasy i asekuracja z brzegu/łodzi',
            ],
            'excluded' => [
                'Ręcznik plażowy, strój kąpielowy i krem z filtrem bezpieczny dla rafy',
                'Płetwy (opcjonalne wypożyczenie w bazie za 4 €/dzień)',
                'Opcjonalne zdjęcia podwodne',
            ],
            'ideal_for' => [
                'Rodziny z dziećmi, pary i osoby nieposiadające uprawnień nurkowych',
                'Osoby słabiej pływające chcące podziwiać Morze Czerwone w bezpiecznej kamizelce',
                'Turyści szukający sprawdzonej organizacji z oceną 4.9★ w Google i na Tripadvisor',
            ],
            'faqs' => [
                ['q' => 'Czy muszę umieć świetnie pływać?', 'a' => 'Nie — kamizelka wypornościowa utrzymuje Cię bez wysiłku na powierzchni, a przewodnik płynie obok z bojką/kołem asekuracyjnym.'],
                ['q' => 'Czy w podanej cenie są już zawarte transfery z hotelu i opłaty parkowe?', 'a' => 'Tak, wszystkie wycieczki snorkelingowe Sea College zawierają transfer z hotelu w Sharm oraz opłaty parkowe.'],
                ['q' => 'Co warto zabrać ze sobą?', 'a' => 'Strój kąpielowy, ręcznik, krem przeciwsłoneczny oraz zdjęcie paszportu w telefonie.'],
            ],
        ];
    }

    if ($gid === 'private-speedboats') {
        $extraInfo = $id === 'speed-1h'
            ? 'do 3 osób w cenie bazowej (maks. 5 osób; 4. i 5. osoba +50 €/os.)'
            : 'dla 2 osób w cenie bazowej (maks. 5 osób; 3., 4. i 5. osoba +100 €/os.)';
        return [
            'h1'         => "{$name} w Sharm El Sheikh — Prywatny Rejs Speedboat z Al Montazah",
            'meta_title' => "{$name} Sharm El Sheikh | {$price} € / Łódź | Sea College",
            'meta_desc'  => "Wynajmij prywatną motorówkę ({$name}) w Sharm El Sheikh za {$price} € za łódź ({$dur}). {$desc}",
            'aeo_answer' => "Prywatny rejs motorówką {$name} w Sea College Dive Club w Sharm El Sheikh kosztuje {$price} € za całą jednostkę ({$extraInfo}) i trwa {$dur}. Startując bezpośrednio z naszej plaży w Al Montazah naprzeciwko wyspy Tiran, omijasz tłumy dużych statków i w kilka minut docierasz nad krystaliczne ogrody koralowe — z maskami, kamizelkami i transferem hotelowym w cenie.",
            'overview'   => [
                "Dzięki wyjątkowemu położeniu naszej bazy w Cyrene Sharm (Al Montazah) najpiękniejsze lokalne rafy oraz laguny wyspy Tiran znajdują się tuż przed naszym pomostem — nie tracisz godziny na dojazd do dalekich marin na południu miasta.",
                "Podczas {$dur} prywatna motorówka z kapitanem należy wyłącznie do Was: sami decydujecie, jak długo chcecie pływać na danej rafie, robić zdjęcia lub cieszyć się ślizgiem po szafirowej tafli Morza Czerwonego.",
            ],
            'specs' => [
                ['Cena za motorówkę', "{$price} € za łódź ({$extraInfo})"],
                ['Czas na wodzie', $dur],
                ['Maksymalna pojemność', 'Do 5 pasażerów na prywatnej jednostce'],
                ['Punkt wypłynięcia', 'Laguna Al Montazah (Cyrene Sharm) naprzeciwko Cieśniny Tiran'],
                ['W cenie', 'Prywatna łódź z kapitanem, paliwo, maski, kamizelki i transfer w Sharm'],
            ],
            'steps' => [
                ['Odbiór z hotelu do bazy Al Montazah', 'Klimatyzowany transfer spod Twojego hotelu prosto na naszą plażę i dopasowanie masek oraz kamizelek.'],
                ['Wypłynięcie prywatną motorówką', 'Błyskawiczny przelot na wybrane, niezatłoczone ogrody koralowe i turkusowe laguny.'],
                ['Prywatny snorkeling w idealnych warunkach', 'Kąpiel nad koralowcami pełnymi kolorowych ryb, żółwi i płaszczek we własnym tempie.'],
                ['Powrót na plażę i transfer do hotelu', 'Malowniczy powrót przez zatokę Al Montazah i odwiezienie pod hotel.'],
            ],
            'included' => [
                "Prywatna motorówka z kapitanem na wyłączność ({$dur})",
                'Maski do snorkelingu i kamizelki asekuracyjne dla uczestników',
                'Transfer w obie strony z hotelu w Sharm El Sheikh oraz paliwo',
                'Zadaszenie przeciwsłoneczne bimini i drabinka zejściowa do wody',
            ],
            'excluded' => [
                'Dopłata za dodatkowe osoby powyżej limitu bazowego (zgodnie z cennikiem: +50 € / +100 € za os., maks. 5 os.)',
                'Ręczniki plażowe oraz ewentualne indywidualne opłaty parkowe',
                'Posiłki na pokładzie (na całodniowy czarter z obiadem sprawdź Prywatną Łódź od 550 €)',
            ],
            'ideal_for' => [
                'Pary i małe rodziny (2–5 osób) szukające prywatności VIP bez tłumów turystów',
                'Goście chcący zmieścić intensywną morską przygodę rano lub przed zachodem słońca',
                'Miłośnicy fotografii i spokojnych lagun wyspy Tiran',
            ],
            'faqs' => [
                ['q' => 'Ile maksymalnie osób może płynąć jedną motorówką?', 'a' => 'Na pokład jednej prywatnej motorówki zabieramy maksymalnie 5 gości.'],
                ['q' => 'Dlaczego start z Al Montazah jest tak korzystny?', 'a' => 'Al Montazah leży bezpośrednio naprzeciwko wyspy Tiran, więc cały czas rejsu spędzasz przy najpiękniejszych rafach zamiast na długim dolocie.'],
                ['q' => 'Czy dzieci mogą płynąć motorówką?', 'a' => 'Tak! Zapewniamy dziecięce kamizelki ratunkowe, a kapitan dostosowuje prędkość do komfortu rodziny.'],
            ],
        ];
    }

    if ($gid === 'private-charters') {
        return [
            'h1'         => "{$name} w Sharm El Sheikh — Wynajem Prywatnego Jachtu na Cały Dzień",
            'meta_title' => "{$name} Sharm El Sheikh | {$price} € / Jacht | Sea College",
            'meta_desc'  => "Wyczarteruj prywatny jacht motorowy ({$name}) w Sharm El Sheikh za {$price} € za cały dzień (7–8 godz.). Ras Mohammed i Biała Wyspa lub Tiran tylko dla Was!",
            'aeo_answer' => "Całodniowy czarter {$name} w Sea College Dive Club w Sharm El Sheikh kosztuje {$price} € za całą prywatną jednostkę na 7–8 godzin. Wasza rodzina lub grupa przyjaciół otrzymuje przestronny jacht motorowy na Morzu Czerwonym wraz z kapitanem i załogą wyłącznie dla siebie, wybierając kierunek Park Narodowy Ras Mohammed i Biała Wyspa lub Cieśnina Tiran — z opcją snorkelingu oraz nurkowań Intro i certyfikowanych.",
            'overview'   => [
                "Prywatny czarter ({$name}) daje pełną wolność na Morzu Czerwonym: żadnych obcych turystów na pokładzie, brak sztywnego pośpiechu na przystankach i możliwość odwiedzenia najpiękniejszych lagun dokładnie wtedy, gdy odpływają z nich statki grupowe.",
                "Na pokładzie macie do dyspozycji przestronny zacieniony salon, górny pokład słoneczny z materacami, toaletę morską i szeroką platformę kąpielową. Chętni mogą dodać indywidualne nurkowania z instruktorem PADI.",
            ],
            'specs' => [
                ['Cena czarteru', "{$price} € za cały prywatny jacht"],
                ['Czas trwania', 'Cały dzień (7–8 godzin na morzu)'],
                ['Wielkość grupy', $name],
                ['Kierunki do wyboru', 'Ras Mohammed i Biała Wyspa LUB rafy wyspy Tiran'],
                ['Możliwości na pokładzie', 'Snorkeling, relaks VIP oraz opcjonalne nurkowanie Intro / certyfikowane'],
            ],
            'steps' => [
                ['Konsultacja trasy z naszym koordynatorem', 'Ustalacie wygodną godzinę porannego startu, preferowany kierunek (Ras Mohammed lub Tiran) oraz dodatki (obiad, nurkowania).'],
                ['Prywatny transfer grupy do mariny', 'Odbiór całej Waszej grupy spod hotelu w Sharm El Sheikh i powitanie na pokładzie jachtu.'],
                ['2–3 przystanki na rafach i lagunach', 'Swobodne pływanie nad ogrodami koralowymi i mieliznami pod opieką załogi.'],
                ['Popołudniowy rejs powrotny', 'Odpoczynek na pokładzie słonecznym w drodze do portu i powrót do hotelu.'],
            ],
            'included' => [
                "Prywatny jacht motorowy na wyłączność ({$name}) przez 7–8 godzin",
                'Profesjonalny kapitan, załoga pokładowa oraz paliwo',
                'Zacieniony salon wypoczynkowy, taras słoneczny, toaleta i prysznic ze słodką wodą',
                'Elastyczny plan dnia dopasowany do rytmu Twojej rodziny lub grupy',
            ],
            'excluded' => [
                'Opłaty za park morski za osobę oraz catering/obiad bufetowy i napoje (wyceniane przejrzyście przed rejsem według liczby gości)',
                'Opcjonalne nurkowania z butlą (Intro dla początkujących lub przewodnik i butle dla certyfikowanych)',
                'Ręczniki kąpielowe',
            ],
            'ideal_for' => [
                'Rodziny wielopokoleniowe, grupy przyjaciół, urodziny i wyjazdy integracyjne w Szarm el-Szejk',
                'Grupy mieszane, w których część osób chce nurkować z akwalungiem, a reszta snorkelować i opalać się',
                'Goście oczekujący prywatnego standardu obsługi potwierdzonego opiniami 4.9★ w Google',
            ],
            'faqs' => [
                ['q' => 'Czy możemy dodać na pokład świeży obiad bufetowy i napoje dla całej grupy?', 'a' => 'Tak! Nasz kucharz pokładowy przygotowuje świeży obiad w formie bufetu oraz zimne i gorące napoje — potwierdzamy dokładny koszt przy rezerwacji.'],
                ['q' => 'Czy na prywatnej łodzi można zrobić pierwsze w życiu nurkowanie Intro?', 'a' => 'Oczywiście! Zabieramy na jacht instruktora PADI i pełny sprzęt, aby chętni z Waszej grupy zeszli pod wodę 1-na-1.'],
                ['q' => 'Z jakim wyprzedzeniem trzeba zarezerwować prywatny jacht?', 'a' => 'Wymagane jest minimum 72 godziny przed rejsem w celu zgłoszenia imiennej listy pasażerów w kapitanacie portu.'],
            ],
        ];
    }

    return null;
};
