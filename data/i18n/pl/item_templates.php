<?php
/**
 * Polish (`pl`) smart group-aware SEO/AEO generator for the remaining 45+ catalog items.
 * Guarantees 100% rich native Polish h1, meta_title, meta_desc, aeo_answer, overview, specs,
 * steps, included, excluded, ideal_for and faqs with ZERO English leakage.
 */
declare(strict_types=1);

return static function (array $it, array $seo): array {
    $id    = (string) ($it['id'] ?? '');
    $gid   = (string) ($it['_group']['id'] ?? '');
    $name  = item_name($it);
    $desc  = item_desc($it);
    $dur   = item_duration($it);
    $price = $it['price'] ?? null;
    $unit  = (string) ($it['unit'] ?? '');

    if ($price === null) {
        $priceLabel = 'Cena na zapytanie';
    } elseif ($unit === 'day') {
        $priceLabel = $price . ' € / dzień';
    } elseif ($unit === 'boat') {
        $priceLabel = $price . ' € / łódź';
    } else {
        $priceLabel = $price . ' €';
    }

    // 1) Certified Dive Packages (dive-1..dive-9, dive-extra)
    if ($gid === 'certified-packages') {
        $dives = 1;
        if (preg_match('/^dive-(\d+)$/', $id, $m)) {
            $dives = (int) $m[1];
        }
        $isExtra = ($id === 'dive-extra');
        $perDive = $isExtra ? 30 : (int) round(((int) $price) / max(1, $dives));
        $saving  = (!$isExtra && $dives > 1) ? (($dives * 45) - (int) $price) : 0;
        $saveTxt = $saving > 0 ? " (oszczędzasz {$saving} € względem pojedynczych nurkowań)" : '';

        return [
            'h1'         => "{$name} w Sharm El Sheikh — Nurkowanie dla Certyfikowanych",
            'meta_title' => "{$name} Sharm El Sheikh | {$price} € ({$perDive} €/Nurk.) | Sea College",
            'meta_desc'  => "Zarezerwuj imienny {$name} w Sharm El Sheikh (Szarm el-Szejk) za {$price} € ({$perDive} €/nurkowanie). Przewodnik PADI, butle 12 l, balast i transfer z hotelu w cenie!",
            'aeo_answer' => "{$name} w bazie Sea College Dive Club (PADI 5 Gwiazdek w Al Montazah, Sharm El Sheikh) kosztuje {$price} € za certyfikowanego nurka ({$perDive} € za nurkowanie{$saveTxt}) i realizowany jest w czasie: {$dur}. W cenę wliczony jest certyfikowany przewodnik PADI, aluminiowe butle 12 l z powietrzem, pas balastowy z ołowiem oraz transfery z/do hotelu w Sharm El Sheikh (w dni z łodzi obowiązuje dzienna dopłata łodziowa 10 € z obiadem i napojami oraz opłata za park morski 5 €).",
            'overview'   => [
                $isExtra
                    ? 'Po ukończeniu pełnego pakietu 10 nurkowań w Sea College Dive Club każde kolejne zejście — zarówno z pokładu naszej łodzi do Ras Mohammed i Cieśniny Tiran, jak i na popołudniową rafę domową — rozliczane jest w ulgowej cenie 30 € za nurkowanie.'
                    : "Imienny {$name} daje Ci pełną swobodę łączenia całodniowych rejsów łodzią do Parku Narodowego Ras Mohammed (Shark & Yolanda Reef) i Cieśniny Tiran (Jackson, Thomas, Woodhouse, Gordon) ze spokojnymi zejściami na naszej rafie domowej Al Montazah.",
                'Grupy na łodziach dzielimy kameralnie według stopnia zaawansowania i zużycia powietrza, dzięki czemu cieszysz się długimi, bezpiecznymi profilami przy widoczności 25–35 metrów.',
            ],
            'specs' => [
                ['Cena pakietu', "{$price} € za osobę ({$perDive} € za nurkowanie)"],
                ['Czas realizacji', $dur !== '' ? $dur : 'Elastyczny rozkład dni'],
                ['Wymagane uprawnienia', 'Certyfikat OWD / CMAS P1 lub wyższy (Scuba Diver z prywatnym przewodnikiem)'],
                ['W cenie pakietu', 'Przewodnik PADI, butle 12 l z powietrzem, pas i ciężarki ołowiane'],
                ['Transfer hotelowy', 'Bezpłatny odbiór i powrót klimatyzowanym busem w Sharm El Sheikh'],
                ['Opłaty w dni z łodzi', '10 €/dzień dopłata łodziowa (z ciepłym obiadem i napojami) + 5 €/dzień opłata parkowa'],
            ],
            'steps' => [
                ['Odbiór spod hotelu i odprawa w bazie', 'Poranny transfer do Cyrene Sharm (Al Montazah), weryfikacja certyfikatu i logbooka oraz przygotowanie skrzynek sprzętowych.'],
                ['Wypłynięcie łodzią lub wejście na Rafę Domową', 'Rejs (~08:30) na dwie różne rafy koralowe Morza Czerwonego lub zejście z brzegu przy naszej bazie (0 € opłaty parkowej!).'],
                ['Przerwa powierzchniowa i świeży obiad', 'Odpoczynek na zacienionym pokładzie oraz gorący obiad w formie bufetu, kawa, herbata i zimne napoje podczas rejsów całodniowych.'],
                ['Popołudniowe nurkowanie i wpisy do logbooka', 'Drugie nurkowanie w nowym ogrodzie koralowym, pieczątki PADI 5 Star Resort w logbooku i powrót do hotelu ok. 16:30.'],
            ],
            'included' => [
                ($isExtra ? 'Dodatkowe nurkowanie z przewodnikiem PADI (z łodzi lub z brzegu)' : "{$dives} nurkowań/nurkowania na wodach otwartych z przewodnikiem PADI"),
                'Aluminiowe butle 12 l z powietrzem, pas balastowy i ołów',
                'Transfer w obie strony z hotelu na terenie Sharm El Sheikh',
                'Napoje na rejsach półdniowych; obiad bufetowy i napoje na całodniowych łodziach (przy dziennej dopłacie łodziowej)',
            ],
            'excluded' => [
                'Dopłata łodziowa: 10 € za osobę/dzień w dni nurkowania z łodzi',
                'Opłata za Park Morski: 5 € za osobę/dzień (wszystkie strefy poza Rafą Domową)',
                'Wypożyczenie pełnego sprzętu (25 €/dzień, jeśli nie masz własnego), Nitrox (8 €/butla 12 l) i ubezpieczenie nurkowe',
            ],
            'ideal_for' => [
                'Certyfikowani nurkowie planujący rejsy do Ras Mohammed, Cieśniny Tiran i na lokalne rafy Sharm',
                'Osoby po przerwie >6 mies. mogą zacząć pierwszy dzień od Check Dive (25 €) lub Scuba Review (50 € po >12 mies.)',
                'Nurkowie ceniący elastyczność — dni nurkowych nie trzeba realizować ciągiem dzień po dniu!',
            ],
            'faqs' => [
                ['q' => 'Czy pakiet nurkowy można podzielić pomiędzy dwie osoby?', 'a' => 'Nie — zgodnie z regulaminem bazy wszystkie pakiety są imienne dla jednego nurka i nie mogą być współdzielone.'],
                ['q' => 'Ile wynoszą opłaty dzienne podczas wypłynięcia na łódź?', 'a' => 'Każdy dzień na łodzi to 10 € dopłaty łodziowej (obejmującej obiad bufetowy i napoje na rejsach całodniowych) oraz 5 € opłaty za park morski. Zejścia na Rafie Domowej mają 0 € opłat dodatkowych!'],
                ['q' => 'Czy mogę dodać wrak lub nurkowanie nocne do mojego pakietu?', 'a' => 'Tak! Dopłacasz jedynie dodatek specjalny (np. Nocne +30 €, Dunraven +30 €, Million Hope +30 €, SS Thistlegorm +150 €) do nurkowania ze swojego pakietu.'],
            ],
        ];
    }

    // 2) Wreck, Zodiac & Night Supplements (sup-night, sup-zodiac, sup-zodiac-early)
    if ($gid === 'wreck-special-dives') {
        $minPax = str_contains($desc, '4') ? 'Minimum 4 certyfikowanych nurków (rezerwacja min. 48 h wcześniej)' : 'Certyfikowany nurek OWD lub wyżej';
        return [
            'h1'         => "{$name} w Sharm El Sheikh — Specjalna Dopłata do Pakietu",
            'meta_title' => "{$name} Sharm El Sheikh | Dopłata +{$price} € | Sea College PADI",
            'meta_desc'  => "Wzbogać swój pakiet nurkowy w Sharm El Sheikh o {$name} (+{$price} €, {$dur}). {$desc}",
            'aeo_answer' => "Opcja specjalna {$name} w Sea College Dive Club w Sharm El Sheikh kosztuje +{$price} € dopłaty za osobę ponad nurkowanie z Twojego pakietu certyfikowanego i trwa około {$dur}. {$desc} Zapewnia opiekę przewodnika PADI, butlę 12 l, balast oraz pełną logistykę łodzi/pontonu RIB lub zejścia nocnego.",
            'overview'   => [
                "Dodatek {$name} (+{$price} €) został stworzony dla certyfikowanych nurków chcących wyjść poza standardowy harmonogram codziennych jachtów — niezależnie czy marzysz o spotkaniu rekinów młotów o świcie z szybkiego Zodiaka, czy o polujących ośmiornicach i skrzydlicach po zmroku.",
                "Dzięki położeniu naszej bazy przy samej plaży Al Montazah naprzeciwko Cieśniny Tiran docieramy pontonem RIB na zewnętrzne ściany Jackson i Woodhouse Reef w kilkanaście minut — zanim pojawią się tam jakiekolwiek duże łodzie!",
            ],
            'specs' => [
                ['Wysokość dopłaty', "+{$price} € za osobę (doliczane do 1 nurkowania z pakietu)"],
                ['Czas trwania', $dur],
                ['Wymagana grupa / poziom', $minPax],
                ['Punkt startowy', 'Baza plażowa Sea College — Cyrene Sharm, Al Montazah'],
                ['W cenie', 'Przewodnik PADI, logistyka Zodiaka / zabezpieczenie nocne, transfer w Sharm'],
            ],
            'steps' => [
                ['Specjalna odprawa taktyczna', 'Szczegółowe omówienie procedur (wskakiwanie na komendę z Zodiaka „negative entry” lub sygnały świetlne na nurkowaniu nocnym).'],
                ['Wejście do wody w idealnym oknie czasowym', 'Zanurzenie o świcie, po zmroku lub w punkcie niedostępnym dla dużych jednostek cumujących.'],
                ['Eksploracja z przewodnikiem PADI', 'Prowadzenie wzdłuż najciekawszej strefy rafy z pełną asekuracją powierzchniową (bojka DSMB / światło brzegowe).'],
                ['Powrót do bazy i gorąca herbata', 'Szybki powrót do Cyrene Sharm, płukanie sprzętu i wpis specjalny w logbooku!'],
            ],
            'included' => [
                "Organizacja i asekuracja wyprawy specjalnej: {$name}",
                'Prowadzenie przez doświadczonego przewodnika PADI',
                'Obsługa pontonu RIB / zabezpieczenia brzegowego oraz transfery z hotelu w Sharm',
                'Standardowa butla 12 l i balast (w ramach łączonego pakietu nurkowego)',
            ],
            'excluded' => [
                '1 nurkowanie bazowe odliczane z Twojego pakietu certyfikowanego',
                'Latarka podwodna LED (6 €/dzień przy nurkowaniu nocnym, jeśli nie masz własnej) oraz wypożyczenie sprzętu (25 €/dzień)',
                'Opłata parkowa 5 € (jeśli wyprawa wychodzi poza Rafę Domową)',
            ],
            'ideal_for' => [
                'Certyfikowani nurkowie szukający adrenaliny, spotkań z dużymi rybami pelagicznymi lub magii rafy nocą',
                'Fotografowie podwodni polujący na kadry bez innych grup w tle',
                'Grupy 4+ znajomych lub osoby dopisujące się do naszej tablicy wypraw specjalnych w bazie',
            ],
            'faqs' => [
                ['q' => "Czy mogę wykupić {$name} bez dużego pakietu nurkowego?", 'a' => 'Tak — wystarczy połączyć dopłatę chociażby z pojedynczym nurkowaniem lub pakietem 2 nurkowań!'],
                ['q' => 'Co jeśli podróżuję sam, a na Zodiaka wymagane są minimum 4 osoby?', 'a' => 'Napisz do nas na WhatsApp! Regularnie łączymy chętnych nurków w grupy 4–6-osobowe na poranne i popołudniowe Zodiaki.'],
                ['q' => 'Czy na nurkowanie z Zodiaka potrzebna jest bojka DSMB?', 'a' => 'Nasz przewodnik PADI posiada pełne wyposażenie sygnalizacyjne DSMB, a posiadanie własnej bojki i komputera nurkowego jest zawsze dobrą praktyką.'],
            ],
        ];
    }

    // 3) Daily Fees & Private Guide (fee-boat, fee-park, private-guide)
    if ($gid === 'daily-fees') {
        $isGuide = ($id === 'private-guide');
        return [
            'h1'         => "{$name} w Sharm El Sheikh — Przejrzyste Zasady i Stawki ({$priceLabel})",
            'meta_title' => "{$name} Sharm El Sheikh | {$priceLabel} | Sea College Cennik",
            'meta_desc'  => "Sprawdź dokładne zasady pozycji {$name} ({$priceLabel}) w bazie Sea College Dive Club w Sharm El Sheikh: {$desc}",
            'aeo_answer' => "Pozycja cennikowa {$name} w Sea College Dive Club w Sharm El Sheikh wynosi {$priceLabel}. {$desc} Publikujemy każdą stawkę dzienną otwarcie na stronie i w kalkulatorze budżetu, abyś znał co do euro całkowity koszt swojego wyjazdu jeszcze przed wylotem z Polski.",
            'overview'   => [
                $isGuide
                    ? 'Wynajęcie Prywatnego Przewodnika PADI (75 € za dzień) zapewnia indywidualne tempo pod wodą, trasy dobrane pod fotografię makro/szerokokątną oraz spokój ducha — jest też obowiązkowe ze względów bezpieczeństwa dla dzieci 10–11 lat oraz posiadaczy stopnia PADI Scuba Diver (limit 12 m).'
                    : "W Sea College Dive Club rozdzielamy cenę samego pakietu nurkowego od dziennych opłat łodziowych i środowiskowych ({$name}: {$priceLabel}), ponieważ wielu naszych gości łączy rejsy łodzią z bezpłatnymi pod względem opłat parkowych nurkowaniami z brzegu na naszej Rafie Domowej Al Montazah!",
                'Możesz łatwo zasymulować dokładną liczbę dni łodziowych w naszym Interaktywnym Kalkulatorze Budżetu Nurkowego na stronie Cennika.',
            ],
            'specs' => [
                ['Stawka oficjalna', $priceLabel],
                ['Jednostka rozliczenia', 'Za dzień korzystania (płatne tylko w dni, których dotyczy)'],
                ['Kiedy NIE obowiązuje', $isGuide ? 'Opcjonalny dla dorosłych nurków OWD+ w grupach standardowych' : '0 € podczas dni nurkowania z brzegu na Rafie Domowej House Reef!'],
                ['Przejrzystość', 'Potwierdzane w podsumowaniu rezerwacji bez ukrytych dopłat'],
                ['Baza', 'Sea College Dive Club — PADI 5 Star Resort, Sharm El Sheikh'],
            ],
            'steps' => [
                ['Wybór liczby dni na łodzi lub z brzegu', 'Określasz w koszyku lub na WhatsApp, ile dni swojego pakietu chcesz spędzić na jachcie, a ile na Rafie Domowej.'],
                ['Automatyczne podliczenie przed przyjazdem', 'Nasz zespół (lub Kalkulator Budżetu) przesyła dokładne zestawienie dzienne w EUR.'],
                ['Pełna obsługa formalności przez bazę', 'Załatwiamy wszystkie pozwolenia portowe, bilety Parku Narodowego Ras Mohammed / Tiran i logistykę pokładową.'],
                ['Wygodne rozliczenie w bazie', 'Płatność na miejscu kartą, przelewem lub gotówką zgodnie z faktycznie zrealizowanymi dniami.'],
            ],
            'included' => [
                $id === 'fee-boat' ? 'Całodniowe miejsce na przestronnej łodzi nurkowej ze świeżym obiadem w formie bufetu, kawą, herbatą, wodą i napojami!' : ($isGuide ? 'Dedykowany przewodnik PADI na wyłączność dla Ciebie lub Twojej prywatnej grupy przez cały dzień' : 'Oficjalna dzienna opłata środowiskowa na rzecz ochrony parków morskich Synaju Południowego'),
                'Pełna obsługa formalności manifestu portowego i biletów przez Sea College',
                'Zero prowizji pośredników — oficjalna stawka cennikowa bazy',
                'Gwarancja 0 € opłaty łodziowej i 0 € parkowej w dni spędzone wyłącznie na Rafie Domowej',
            ],
            'excluded' => [
                'Bazowy pakiet nurkowań lub kurs PADI',
                'Wypożyczenie sprzętu osobistego (25 €/dzień pełny zestaw)',
                'Opcjonalne dopłaty wrakowe (np. SS Thistlegorm, Dahab)',
            ],
            'ideal_for' => [
                $isGuide ? 'Dzieci 10–11 lat (Junior OWD) oraz nurkowie PADI Scuba Diver (wymóg obowiązkowy)' : 'Certyfikowani nurkowie wypływający na całodniowe rejsy do Ras Mohammed i Cieśniny Tiran',
                $isGuide ? 'Fotografowie podwodni i pary ceniące prowadzenie 1-na-1 we własnym tempie' : 'Osoby ceniące uczciwy, rozpisany co do euro cennik przed rezerwacją',
                'Wszyscy goście korzystający z pakietów certyfikowanych i kursów z łodzi',
            ],
            'faqs' => [
                ['q' => 'Czy w cenie dopłaty łodziowej 10 €/dzień jest już zawarty obiad na jachcie?', 'a' => 'Tak! Na rejsach całodniowych dopłata łodziowa 10 €/dzień obejmuje już świeżo przygotowany gorący obiad bufetowy oraz kawę, herbatę, wodę i napoje chłodzące!'],
                ['q' => 'Czy na biletach Intro z łodzi (70 € / 100 €) lub snorkelingu (55 €) też dopłacam 10 € + 5 €?', 'a' => 'Nie — wycieczki Intro z łodzi oraz rejsy snorkelingowe mają już opłaty łodziowe i parkowe wliczone w cenę biletu! Opłaty dzienne dotyczą pakietów certyfikowanych i dni łodziowych do kursów.'],
                ['q' => 'Czy jeden Prywatny Przewodnik (75 €/dzień) może prowadzić parę znajomych?', 'a' => 'Tak, prywatny przewodnik może opiekować się Tobą indywidualnie (1-na-1) lub małą, spójną parą/rodziną na tym samym poziomie.'],
            ],
        ];
    }

    // 4) Equipment & Tank Rental (eq-*)
    if ($gid === 'equipment-rental') {
        $isTank = str_starts_with($id, 'eq-12') || str_starts_with($id, 'eq-15') || $id === 'eq-sm-tank';
        return [
            'h1'         => "Wypożyczenie: {$name} w Sharm El Sheikh ({$priceLabel})",
            'meta_title' => "{$name} — Wypożyczalnia Sprzętu Nurkowego Sharm | {$priceLabel} | Sea College",
            'meta_desc'  => "Podróżuj z lekkim bagażem do Egiptu! Wypożycz {$name} w Sharm El Sheikh (Szarm el-Szejk) za {$priceLabel}. {$desc} Serwisowany sprzęt w bazie PADI 5★.",
            'aeo_answer' => "Dzienne wypożyczenie pozycji „{$name}” w bazie Sea College Dive Club w Sharm El Sheikh kosztuje {$priceLabel}. {$desc} Cały nasz sprzęt przechodzi regularne przeglądy autoryzowanych techników oraz dezynfekcję po każdym użyciu (pamiętaj: na wszystkich kursach PADI oraz nurkowaniach Intro pełny sprzęt podstawowy jest już wliczony w cenę 0 €!).",
            'overview'   => [
                $isTank
                    ? "Nasza własna sprężarkownia w Cyrene Sharm zapewnia certyfikowaną czystość powietrza oddechowego (audyt CDWS) oraz precyzyjnie mieszany Nitrox (EANx28–EANx36) i butle 15 l lub Sidemount gotowe na pokładzie łodzi każdego ranka."
                    : "Nie chcesz płacić liniom lotniczym za nadbagaż sportowy na lot czarterowy z Polski? W naszej wypożyczalni w Al Montazah dobierzesz {$name} w pełnej rozmiarówce od XS do XXL (oraz rozmiary dziecięce).",
                'Jeśli potrzebujesz więcej niż dwóch pojedynczych elementów sprzętu, najbardziej opłacalny jest Pełny Zestaw Sprzętu Nurkowego (BCD, automat, pianka, maska i płetwy) w cenie zaledwie 25 € za cały dzień nurkowy!',
            ],
            'specs' => [
                ['Cena wypożyczenia', $priceLabel . ' (obejmuje 2–3 nurkowania w danym dniu)'],
                ['Opis pozycji', $desc],
                ['Serwis i higiena', 'Regularny serwis techniczny i pełna dezynfekcja po każdym kliencie'],
                ['Na kursach PADI i Intro', '0 € — podstawowy sprzęt jest już wliczony w cenę kursów i Intro!'],
                ['Odbiór i transport', 'Załoga ładuje Twój opisany boks ze sprzętem prosto na łódź nurkową'],
            ],
            'steps' => [
                ['Podaj rozmiary online lub na miejscu', 'Po rezerwacji możesz wypełnić formularz Fast-Track Check-In z rozmiarami pianki, BCD i płetw.'],
                ['Przymiarka i test przed wypłynięciem', 'Technik sprzętowy dobiera idealny rozmiar (lub analizujesz mieszankę O₂ w przypadku butli Nitrox).'],
                ['Transport skrzynek na jacht przez załogę', 'Nie dźwigasz ciężkich toreb — Twój zestaw czeka gotowy na łodzi lub przy Rafie Domowej.'],
                ['Płukanie i suszenie w przechowalni', 'Po dniu nurkowym sprzęt płuczemy w słodkiej wodzie i przechowujemy w wentylowanej zbrojowni bazy.'],
            ],
            'included' => [
                "Całodzienne użytkowanie pozycji: {$name} (na 2–3 nurkowania dziennie)",
                'Profesjonalne dopasowanie rozmiaru i sprawdzenie szczelności / ciśnienia w bazie',
                'Codzienny transport sprzętu pomiędzy bazą a naszą łodzią nurkową',
                'Dezynfekcja oraz bezpieczne przechowywanie między dniami pakietu',
            ],
            'excluded' => [
                $id === 'eq-full' ? 'Komputer nurkowy (10 €/dzień) oraz latarka podwodna LED (6 €/dzień)' : 'Pozostałe elementy ekwipunku (sprawdź Pełny Zestaw za 25 €/dzień, aby zaoszczędzić!)',
                $isTank && str_contains($id, 'nx') ? 'Wymagany ważny certyfikat Enriched Air Nitrox (kurs 1-dniowy dostępny u nas za 145 €)' : 'Odpowiedzialność za zgubienie sprzętu pod wodą z winy użytkownika',
                'Pakiet nurkowy dla certyfikowanych',
            ],
            'ideal_for' => [
                'Certyfikowani nurkowie lecący do Szarm el-Szejk wyłącznie z bagażem podręcznym lub jedną walizką rodzinną',
                $isTank ? 'Nurkowie o większym zużyciu powietrza (butla 15 l) lub wydłużający czas bezdekompresyjny (Nitrox)' : 'Osoby posiadające własną maskę i komputer, którym brakuje tylko wybranego elementu',
                'Goście ceniący pewny, serwisowany sprzęt w bazie PADI 5 Gwiazdek',
            ],
            'faqs' => [
                ['q' => 'Czy cena dzienna obejmuje jedno nurkowanie czy cały dzień?', 'a' => "Stawka {$priceLabel} obejmuje cały dzień nurkowy (standardowo 2 nurkowania z łodzi oraz ewentualne 3. zejście dzienne)."],
                ['q' => 'Jakie złącza zaworów mają Wasze butle (INT czy DIN)?', 'a' => 'Nasze butle obsługują zarówno automaty DIN (po wykręceniu wkładki), jak i INT (Yoke) — pasują do każdego automatu z Polski!'],
                ['q' => 'Czy podczas kursu OWD lub AOWD muszę dopłacać za wypożyczenie sprzętu?', 'a' => 'Nie! We wszystkich kursach PADI, pakietach kursów oraz nurkowaniach Intro pełny sprzęt jest już wliczony w cenę szkolenia.'],
            ],
        ];
    }

    // 5) Short-Term Diving Insurance (ins-3, ins-7, ins-10, ins-14)
    if ($gid === 'diving-insurance') {
        return [
            'h1'         => "{$name} w Sharm El Sheikh — Ochrona Komory Dekompresyjnej",
            'meta_title' => "{$name} Sharm El Sheikh | Ubezpieczenie Nurkowe | Sea College",
            'meta_desc'  => "Wykup krótkoterminowe {$name} w Sharm El Sheikh (Szarm el-Szejk). Obejmuje leczenie w komorze hiperbarycznej i ratownictwo medyczne nad Morzem Czerwonym.",
            'aeo_answer' => "Krótkoterminowe {$name} w Sea College Dive Club w Sharm El Sheikh zapewnia pełną ochronę kosztów leczenia nurkowego oraz komory dekompresyjnej (hiperbarycznej) przez okres {$dur}. Dokładną stawkę operatora ubezpieczeniowego potwierdzamy przy rezerwacji, a aktywacja polisy trwa zaledwie 2 minuty w naszej bazie przed pierwszym zejściem pod wodę.",
            'overview'   => [
                'Standardowe turystyczne ubezpieczenie podróżne z biura podróży zazwyczaj wyłącza nurkowanie z akwalungiem lub ogranicza je do 10–18 metrów bez pokrycia specjalistycznego leczenia w komorze hiperbarycznej w Sharm El Sheikh.',
                "Jeśli nie posiadasz rocznej karty DAN (Divers Alert Network), krótkoterminowa polisa na {$dur} zdejmuje z Ciebie wszelkie ryzyko finansowe na czas pakietu nurkowego (uwaga: w kursie OWD oraz we wszystkich 4 Pakietach Kursów ubezpieczenie jest już u nas wliczone w cenę!).",
            ],
            'specs' => [
                ['Okres ochrony', $dur],
                ['Cena', 'Potwierdzana przy rezerwacji (aktualna taryfa ubezpieczyciela CDWS/DAN)'],
                ['Zakres ochrony', 'Komora dekompresyjna w Sharm El Sheikh i nagła pomoc medyczna nurkowa'],
                ['Aktywacja', 'Natychmiastowa na miejscu przed pierwszym nurkowaniem'],
                ['Wliczone gratis w', 'Kursach OWD, Scuba Diver, Divemaster oraz Pakietach Stars, Super, Mega i Heros!'],
            ],
            'steps' => [
                ['Sprawdź swoją obecną polisę', 'Upewnij się, czy masz aktywne ubezpieczenie DAN lub rozszerzenie „sporty wysokiego ryzyka — nurkowanie do 30/40 m”.'],
                ['Dodaj wybrany wariant do zapytania', "Zaznacz {$name} w koszyku rezerwacyjnym, dopasowując liczbę dni do długości urlopu."],
                ['Rejestracja imienna w bazie', 'Podczas porannej odprawy wpisujemy Twoje dane paszportowe do systemu ubezpieczeniowego.'],
                ['Spokojne nurkowanie przez cały urlop', "Korzystasz z pełnej ochrony przez {$dur} na wszystkich rafach i wrakach Sharm El Sheikh!"],
            ],
            'included' => [
                "Imienna polisa nurkowa ważna przez {$dur} w rejonie Morza Czerwonego",
                'Pokrycie kosztów ewentualnej terapii tlenowej i komory hiperbarycznej w Sharm El Sheikh',
                'Natychmiastowa rejestracja bez zbędnych formalności',
                'Honorowanie na wszystkich rejsach do Ras Mohammed, Tiranu, Dahab i na SS Thistlegorm',
            ],
            'excluded' => [
                'Ogólne ubezpieczenie turystyczne od rezygnacji z lotu lub zgubienia bagażu',
                'Nurkowania wykraczające poza limity posiadanego certyfikatu rekreacyjnego',
                ' Koszty samego pakietu nurkowego',
            ],
            'ideal_for' => [
                'Certyfikowani nurkowie nieposiadający rocznego członkostwa DAN Europe',
                'Turyści z podstawową polisą biura podróży wyłączającą nurkowanie swobodne',
                'Uczestnicy pojedynczych kursów AOWD lub Specjalizacji (chyba że wybierają Pakiet Kursów z ubezpieczeniem w cenie!)',
            ],
            'faqs' => [
                ['q' => 'Mam roczne ubezpieczenie DAN Europe — czy muszę wykupywać tę polisę?', 'a' => 'Nie! Jeśli posiadasz aktywną polisę DAN lub inne specjalistyczne ubezpieczenie obejmujące komorę hiperbaryczną do Twojej głębokości uprawnień, wystarczy okazać numer polisy przy odprawie.'],
                ['q' => 'Czy w Sharm El Sheikh działa profesjonalna komora dekompresyjna?', 'a' => 'Tak, w Szarm el-Szejk funkcjonuje jedno z najlepiej wyposażonych centrów medycyny hiperbarycznej nad całym Morzem Czerwonym.'],
                ['q' => 'Którą długość polisy wybrać na tygodniowy wyjazd?', 'a' => 'Na standardowy pakiet 2–3 dni wybierz wariant 3-dniowy, na pełny tydzień nurkowy — 7-dniowy, a na dwutygodniowe wczasy — 10 lub 14 dni.'],
            ],
        ];
    }

    // 6) Delegate Courses, Snorkeling, Speedboats & Private Charters to modular closure
    $subBuilder = require __DIR__ . '/item_templates_boats_courses.php';
    $delegated  = $subBuilder($it, $gid, $name, $desc, $dur, $priceLabel);
    if (is_array($delegated)) {
        return $delegated;
    }

    // 7) Safe 100% Polish fallback
    return [
        'h1'         => "{$name} — Sea College Dive Club Sharm El Sheikh",
        'meta_title' => "{$name} Sharm El Sheikh | {$priceLabel} | Sea College PADI 5★",
        'meta_desc'  => "{$name} w Sharm El Sheikh (Szarm el-Szejk): {$desc} Rezerwuj w autoryzowanej bazie PADI 5 Gwiazdek.",
        'aeo_answer' => "{$name} w Sea College Dive Club w Sharm El Sheikh kosztuje {$priceLabel}" . ($dur !== '' ? " ({$dur})" : '') . ". {$desc}",
        'overview'   => [
            "{$name} realizujemy z naszej nadmorskiej bazy PADI 5 Gwiazdek w resorcie Cyrene Sharm (Al Montazah) w Sharm El Sheikh.",
            'Nasz wielojęzyczny zespół dba o najwyższe standardy bezpieczeństwa CDWS i PADI, potwierdzone oceną 4.9★ w ponad 3 082 opiniach Google.',
        ],
        'specs'    => [['Cena', $priceLabel], ['Czas trwania', $dur ?: 'Elastyczny'], ['Lokalizacja', 'Cyrene Sharm, Al Montazah, Sharm El Sheikh']],
        'steps'    => [['Rezerwacja online lub WhatsApp', 'Wybierz termin i potwierdź dostępność z naszym zespołem bez przedpłat.'], ['Realizacja na miejscu', 'Spotkaj się z naszą kadrą PADI w bazie lub skorzystaj z transferu hotelowego.']],
        'included' => [$desc, 'Obsługa w autoryzowanym centrum PADI 5 Star Dive Resort'],
        'excluded' => ['Wydatki osobiste i opcjonalne dodatki spoza opisu'],
        'ideal_for'=> ['Goście wypoczywający w Sharm El Sheikh (Szarm el-Szejk)'],
        'faqs'     => [['q' => 'Jak zarezerwować tę usługę?', 'a' => 'Dodaj pozycję do koszyka na stronie lub napisz do nas na WhatsApp — potwierdzimy szczegóły w kilka minut.']],
    ];
};
