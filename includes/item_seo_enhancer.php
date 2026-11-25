<?php
/**
 * 8-Language Item SEO/AEO Enhancer, Per-Trip Traveller FAQ Generator & Smart Cross-Sell Upgrade Engine.
 *
 * Ensures every single trip/course page (80 items × 8 languages = 640 item URLs):
 *  1) Answers all 6 high-intent Google Ads Search_Terms traveller questions in both HTML & FAQPage JSON-LD:
 *     - Exact EUR price & 100% zero hidden marina fees (€0 upfront deposit)
 *     - Complimentary Sharm El Sheikh hotel pickup zones (6 zones, 75+ resorts)
 *     - Beginner / swimming / minimum age / certification requirements
 *     - Small-group safety: Max 4 divers per PADI guide (or 1-on-1 instructor) + mixed couples on same yacht
 *     - Check-Dive skip rule (<6 months logged dive = skip check dive)
 *     - Scuba equipment inclusion vs €25/day rental & €8 Nitrox 32%
 *  2) Enriches meta descriptions & AEO quick-answers with conversion-proven Search_Terms signals.
 *  3) Powers the "Related Trips & Recommended Upgrades" cross-sell cards with 1-tap WhatsApp & Quick-Book CTAs.
 */
declare(strict_types=1);

/**
 * Classify a catalog item into an operational FAQ profile.
 */
function sc_item_profile(array $it): string
{
    $id  = (string) ($it['id'] ?? '');
    $cat = (string) ($it['_cat_key'] ?? '');
    $grp = (string) ($it['_group']['id'] ?? '');

    if (in_array($id, ['bubblemaker', 'seal-team'], true) || $grp === 'kids-courses') {
        return 'kids';
    }
    if (in_array($id, ['dsd-quick-reef', 'dsd-2-reef'], true)) {
        return 'intro_reef';
    }
    if (in_array($id, ['dsd-quick-boat', 'dsd-2-boat'], true)) {
        return 'intro_boat';
    }
    if ($id === 'dsd') {
        return 'dsd_course';
    }
    if (str_starts_with($id, 'bundle-') || $cat === 'offers') {
        return 'bundle';
    }
    if ($cat === 'padi-courses') {
        return in_array($id, ['scuba-diver', 'owd'], true) ? 'course_beginner' : 'course_cert';
    }
    if ($cat === 'snorkeling') {
        if (str_starts_with($id, 'speed-')) {
            return 'speedboat';
        }
        if (str_starts_with($id, 'private-')) {
            return 'private_yacht';
        }
        return 'snorkeling';
    }
    if (in_array($id, ['check-dive', 'scuba-review'], true)) {
        return 'refresh';
    }
    if (str_starts_with($id, 'sup-')) {
        return 'special_dive';
    }
    if (str_starts_with($id, 'eq-') || str_starts_with($id, 'fee-') || str_starts_with($id, 'ins-') || $id === 'private-guide') {
        return 'addon';
    }
    return 'certified_pack';
}

/**
 * Build 6 localized, item-specific Traveller FAQs in active LANG covering all Search_Terms intent pillars.
 * @return array<int, array{0:string, 1:string}>
 */
function sc_build_item_traveller_faqs(array $it): array
{
    $lang    = defined('LANG') ? LANG : 'en';
    $profile = sc_item_profile($it);
    $name    = item_name($it);
    $price   = $it['price'] !== null ? '€' . number_format((float) $it['price'], 0, '.', ',') : 'EUR';
    $id      = (string) ($it['id'] ?? '');

    $allInGear = in_array($profile, ['kids', 'intro_reef', 'intro_boat', 'dsd_course', 'course_beginner', 'course_cert', 'bundle', 'snorkeling', 'speedboat', 'private_yacht'], true);
    $isBoatAllIn = in_array($profile, ['intro_boat', 'snorkeling', 'speedboat', 'private_yacht'], true);
    $isBeginnerNoSwim = in_array($profile, ['kids', 'intro_reef', 'intro_boat', 'dsd_course', 'snorkeling', 'speedboat', 'private_yacht'], true);

    switch ($lang) {
        case 'ru':
            $qPrice = "Входит ли всё в цену {$price} за «{$name}» и есть ли скрытые сборы в порту?";
            $aPrice = $isBoatAllIn
                ? "Официальная прямая цена «{$name}» составляет {$price} без скрытых портовых сборов и с €0 предоплаты онлайн (оплата на месте в EUR, USD, GBP или EGP). Трансфер, работа экипажа/инструктора и снаряжение уже включены; отдельно оплачивается только государственный сбор морского заповедника €5/чел. при входе в Рас-Мохаммед или Тиран."
                : ($profile === 'bundle' || $profile === 'course_beginner' || $profile === 'course_cert'
                    ? "Прямая онлайн-цена «{$name}» — {$price} с €0 предоплаты онлайн и без скрытых наценок. Всё дайв-снаряжение включено. Дополнительно оплачиваются только официальный учебник/сертификат PADI PIC (€85 за курс, кроме DSD) и сборы в дни выхода на яхте (€10 яхта с обедом + €5 заповедник)."
                    : "Прямая онлайн-цена «{$name}» — {$price} (€0 предоплаты онлайн, оплата в дайв-центре в EUR/USD/EGP). Никаких скрытых портовых сборов: в дни погружений с яхты фиксированно добавляются только €10/день сбор яхты (включая горячий обед и напитки) + €5/день сбор заповедника; с домашнего рифа доплат нет.");

            $qPickup = "Из каких районов Шарм-эль-Шейха предоставляется бесплатный трансфер на «{$name}»?";
            $aPickup = "Бесплатный кондиционированный трансфер туда-обратно охватывает все 6 курортных зон Шарм-эль-Шейха (75+ отелей): Набк-Бей (~07:45), Шаркс-Бей и SOHO (~08:05), Наама-Бей (~07:50), Хадаба и Рас-Ум-эль-Сид (~07:35), Старый город (~07:35) и Рас-Насрани / Аль-Монтаза (0–5 мин, база на пляже Cyrene Sharm). Возвращение с яхты около 16:30.";

            $qReq = $isBeginnerNoSwim
                ? "Нужен ли опыт дайвинга или умение отлично плавать для «{$name}»?"
                : "Какой сертификат, возраст и навыки плавания нужны для «{$name}»?";
            $aReq = $isBeginnerNoSwim
                ? ($profile === 'kids'
                    ? "Опыт и спортивное плавание не требуются! Программа проводится с 8 лет в мелком тренировочном бассейне (<2 м) под прямым контролем инструктора PADI в детском снаряжении."
                    : "Опыт и умение плавать НЕ требуются! Инструктор PADI сопровождает новичков индивидуально 1-на-1 за руку и контролирует плавучесть (для снорклинга выдаются удобные жилеты).")
                : ($id === 'sup-thistlegorm'
                    ? "Для погружения на SS Thistlegorm требуется сертификат не ниже PADI Advanced Open Water (30 м) и минимум 20 зарегистрированных дайвов."
                    : ($profile === 'course_beginner' || $profile === 'bundle'
                        ? "Курс открыт для новичков с 10 лет без опыта дайвинга; по стандартам PADI требуется базовое умение проплыть 200 м без времени и продержаться на воде 10 минут."
                        : "Требуется действующий сертификат дайвера (Open Water или выше; минимальный возраст 10 лет). По стандартам PADI/DAN планируйте 18–24 часа до вылета самолёта."));

            $qGroup = "Сколько дайверов в группе у одного гида PADI и можно ли поехать парой с новичком?";
            $aGroup = "Для сертифицированных дайверов у нас строгий стандарт: МАКСИМУМ 4 человека на одного гида PADI (а на пробных погружениях — индивидуальный контроль 1-на-1 с инструктором). Пары могут ехать на одной большой яхте (Oceano, Nizar, Omar 4): один ныряет по пакету, а второй отдыхает, плавает с маской или берёт пробный дайв!";

            $qCheck = "Нужен ли чек-дайв перед «{$name}» или его можно пропустить?";
            $aCheck = $isBeginnerNoSwim || $profile === 'course_beginner'
                ? "Отдельный чек-дайв не нужен! Вся адаптация и вводные навыки уже входят в вашу программу под руководством инструктора PADI."
                : "Правило пропуска чек-дайва: если у вас есть записанное погружение за последние 6 месяцев (в логбуке или приложении PADI/SSI), вы ПРОПУСКАЕТЕ отдельный чек-дайв и сразу идёте на яхту! При перерыве 6–12 мес. нужен Check Dive (€25), свыше 12 мес. — Scuba Review (€50).";

            $qGear = "Включён ли прокат снаряжения и сколько стоит Nitrox 32%?";
            $aGear = $allInGear
                ? "Полный комплект проверенного снаряжения Cressi/Mares уже БЕСПЛАТНО включён в стоимость «{$name}»! Для сертифицированных Nitrox-дайверов баллон 12L EAN32 доступен за €8."
                : "В стоимость входят 12-литровые алюминиевые баллоны и грузовой пояс с грузами. Если у вас нет личного комплекта, прокат полного набора снаряжения Cressi/Mares стоит €25/день (отдельные элементы €4–€13/день), а баллон Nitrox 32% (12L) — €8.";
            break;

        case 'it':
            $qPrice = "Il prezzo di {$price} per «{$name}» è trasparente e senza costi nascosti al porto?";
            $aPrice = $isBoatAllIn
                ? "Sì: la tariffa diretta online per «{$name}» è {$price} con €0 di acconto online e zero tasse portuali nascoste (paghi al diving in EUR, USD, GBP o EGP). Transfer, yacht, istruttore/guida e attrezzatura sono inclusi; si aggiunge solo il biglietto governativo del Parco Marino (€5/persona a Ras Mohammed o Tiran)."
                : ($profile === 'bundle' || $profile === 'course_beginner' || $profile === 'course_cert'
                    ? "La tariffa diretta di «{$name}» è {$price} (€0 acconto online, attrezzatura completa inclusa). Gli unici extra trasparenti sono il kit digitale/brevetto ufficiale PADI PIC (€85/corso, già incluso nel DSD) e gli eventuali giorni in barca (€10 barca con pranzo + €5 parco marino)."
                    : "La tariffa diretta di «{$name}» è {$price} con €0 di acconto online e zero sorprese al molo. Nelle uscite in barca i supplementi giornalieri sono fissi e dichiarati: €10/giorno barca (con pranzo caldo a buffet e bevande) + €5/giorno Parco Marino; da riva sull’House Reef non c’è alcun supplemento.");

            $qPickup = "Quali zone hotel di Sharm El Sheikh hanno il transfer gratuito per «{$name}»?";
            $aPickup = "Il transfer climatizzato gratuito A/R copre tutte le 6 zone di Sharm El Sheikh (75+ resort): Nabq Bay (~07:45), Sharks Bay & SOHO (~08:05), Naama Bay (~07:50), Hadaba & Ras Um El Sid (~07:35), Old Market (~07:35) e Ras Nasrani / Al Montazah (0–5 min, siamo sulla spiaggia del Cyrene Sharm). Rientro in hotel verso le 16:30.";

            $qReq = $isBeginnerNoSwim
                ? "Serve saper nuotare o avere esperienza per «{$name}»?"
                : "Quali requisiti di brevetto, età o nuoto servono per «{$name}»?";
            $aReq = $isBeginnerNoSwim
                ? ($profile === 'kids'
                    ? "Nessuna esperienza richiesta! Riservato ai bimbi dagli 8 anni nella nostra piscina bassa (<2 m) con attrezzatura baby e istruttore PADI dedicato."
                    : "Non serve alcuna esperienza né saper nuotare! Nei Battesimi del Mare l’istruttore PADI ti segue 1-a-1 per mano controllando l’assetto (e per lo snorkeling forniamo giubbotti salvagente comodi).")
                : ($id === 'sup-thistlegorm'
                    ? "Per il relitto SS Thistlegorm (16–30 m) servono il brevetto PADI Advanced Open Water (o equivalente 30 m) e almeno 20 immersioni registrate."
                    : ($profile === 'course_beginner' || $profile === 'bundle'
                        ? "Aperto ai principianti dai 10 anni in su senza esperienza sub; gli standard PADI richiedono solo una nuotata base di 200 m senza limiti di tempo e 10 minuti di galleggiamento."
                        : "È richiesto il brevetto subacqueo (Open Water o superiore, età min. 10 anni). Ricorda le 18–24 ore di intervallo di superficie prima del volo secondo linee guida PADI/DAN."));

            $qGroup = "Quanti sub ci sono per guida PADI e possiamo salire in coppia con un principiante?";
            $aGroup = "Garantiamo piccoli gruppi con MASSIMO 4 sub brevettati per guida PADI (e supervisione individuale 1-a-1 nei Battesimi). Le coppie miste sono benvenute sullo stesso grande yacht (Oceano, Nizar, Omar 4): chi ha il brevetto si immerge e il partner fa snorkeling o Battesimo del Mare pranzando insieme!";

            $qCheck = "Devo fare il Check Dive prima di «{$name}» o posso saltarlo?";
            $aCheck = $isBeginnerNoSwim || $profile === 'course_beginner'
                ? "Nessun check dive separato richiesto: il briefing e l’ambientamento in acqua bassa sono già parte integrante del programma con il tuo istruttore PADI."
                : "Regola Salta-Check-Dive: se hai almeno un’immersione registrata negli ultimi 6 mesi (logbook o app PADI/SSI), SALTI il check dive separato e sali direttamente in barca! Tra 6 e 12 mesi serve un Check Dive (€25); oltre 12 mesi uno Scuba Review (€50).";

            $qGear = "Il noleggio attrezzatura è incluso e quanto costa il Nitrox 32%?";
            $aGear = $allInGear
                ? "Tutta l’attrezzatura completa Cressi/Mares igienizzata è già INCLUSA gratuitamente nel prezzo di «{$name}»! Per i brevettati Nitrox le bombole 12L EAN32 costano €8."
                : "Bombole da 12L e zavorra sono sempre incluse. Se non porti la tua attrezzatura personale, il set completo Cressi/Mares costa €25/giorno (singoli pezzi €4–€13/giorno) e la ricarica Nitrox 32% (12L) costa €8.";
            break;

        case 'pl':
            $qPrice = "Czy cena {$price} za „{$name}” jest pełna i bez ukrytych opłat portowych?";
            $aPrice = $isBoatAllIn
                ? "Tak! Bezpośrednia cena online za „{$name}” wynosi {$price} przy 0 € zaliczki i zero ukrytych opłat w marinie (płatność na miejscu w EUR, USD, GBP lub EGP). Transfer, rejs, opieka instruktora i sprzęt są w cenie; dochodzi jedynie państwowy bilet do parku morskiego (€5/os. w Ras Mohammed lub Tiran)."
                : ($profile === 'bundle' || $profile === 'course_beginner' || $profile === 'course_cert'
                    ? "Cena bezpośrednia „{$name}” to {$price} (0 € zaliczki online, pełny sprzęt nurkowy w cenie). Dodatkowe koszty są w 100% jawne: oficjalny podręcznik i certyfikat PADI PIC (€85/kurs, w DSD już w cenie) oraz dni łodziowe (€10 łódź z obiadem + €5 park morski)."
                    : "Cena bezpośrednia „{$name}” to {$price} z 0 € zaliczki online i bez niespodzianek w porcie. W dni nurkowania z łodzi obowiązują stałe dopłaty: €10/dzień za jacht (z ciepłym obiadem i napojami) + €5/dzień opłaty parkowej; na rafie domowej brak dopłat.");

            $qPickup = "Z jakich stref hotelowych w Sharm El Sheikh jest darmowy transfer na „{$name}”?";
            $aPickup = "Darmowy klimatyzowany transfer w obie strony obejmuje wszystkie 6 stref Sharm El Sheikh (75+ hoteli): Nabq Bay (~07:45), Sharks Bay i SOHO (~08:05), Naama Bay (~07:50), Hadaba i Ras Um El Sid (~07:35), Old Market (~07:35) oraz Ras Nasrani / Al Montazah (0–5 min, baza na plaży Cyrene Sharm). Powrót około 16:30.";

            $qReq = $isBeginnerNoSwim
                ? "Czy muszę umieć pływać lub mieć doświadczenie, aby zarezerwować „{$name}”?"
                : "Jakie uprawnienia, wiek i umiejętności pływackie są wymagane na „{$name}”?";
            $aReq = $isBeginnerNoSwim
                ? ($profile === 'kids'
                    ? "Nie trzeba doświadczenia ani stylów pływackich! Zajęcia od 8. roku życia odbywają się w płytkim basenie (<2 m) w sprzęcie dziecięcym pod opieką instruktora PADI."
                    : "Nie trzeba umieć pływać ani mieć doświadczenia! Podczas intro instruktor PADI prowadzi Cię indywidualnie 1-na-1 za rękę i kontroluje wyporność (do snorkelingu zapewniamy kamizelki asekuracyjne).")
                : ($id === 'sup-thistlegorm'
                    ? "Na wrak SS Thistlegorm (16–30 m) wymagany jest certyfikat min. PADI Advanced Open Water oraz co najmniej 20 zalogowanych nurkowań."
                    : ($profile === 'course_beginner' || $profile === 'bundle'
                        ? "Kurs jest dostępny od 10 lat bez wcześniejszego doświadczenia; standardy PADI wymagają jedynie spokojnego przepłynięcia 200 m bez limitu czasu i 10 minut unoszenia się na wodzie."
                        : "Wymagany jest certyfikat nurkowy (Open Water lub wyższy, min. 10 lat). Przed lotem powrotnym zachowaj 18–24 godz. przerwy powierzchniowej (PADI/DAN)."));

            $qGroup = "Ilu nurków przypada na przewodnika PADI i czy pary mieszane mogą płynąć razem?";
            $aGroup = "Utrzymujemy kameralne grupy: MAKSYMALNIE 4 certyfikowanych nurków na jednego przewodnika PADI (oraz opieka 1-na-1 podczas intro). Pary mogą płynąć na tym samym dużym jachcie (Oceano, Nizar, Omar 4): jedna osoba nurkuje w pakiecie, a druga snorkeluje lub robi intro!";

            $qCheck = "Czy przed „{$name}” muszę robić Check Dive, czy mogę go pominąć?";
            $aCheck = $isBeginnerNoSwim || $profile === 'course_beginner'
                ? "Osobny check dive nie jest potrzebny — pełne wprowadzenie na płytkiej wodzie z instruktorem PADI jest już częścią Twojego programu."
                : "Zasada pominięcia Check Dive: jeśli masz zalogowane nurkowanie z ostatnich 6 miesięcy (logbook lub aplikacja PADI/SSI), POMIJASZ osobny check dive i od razu płyniesz jachtem! Przy przerwie 6–12 mies. obowiązuje Check Dive (€25), powyżej 12 mies. — Scuba Review (€50).";

            $qGear = "Czy wypożyczenie sprzętu jest w cenie i ile kosztuje Nitrox 32%?";
            $aGear = $allInGear
                ? "Pełny zestaw zdezynfekowanego sprzętu Cressi/Mares jest już WLICZONY w cenę „{$name}” bez dopłat! Dla nurków z uprawnieniami Nitrox butla 12L EAN32 kosztuje €8."
                : "Butle 12L z powietrzem oraz pas balastowy z ołowiem są w cenie. Pełny zestaw wypożyczanego sprzętu Cressi/Mares kosztuje €25/dzień (pojedyncze elementy €4–€13/dzień), a butla Nitrox 32% (12L) — €8.";
            break;

        case 'de':
            $qPrice = "Ist der Preis von {$price} für „{$name}“ transparent und ohne versteckte Hafengebühren?";
            $aPrice = $isBoatAllIn
                ? "Ja! Der direkte Online-Preis für „{$name}“ beträgt {$price} mit 0 € Online-Anzahlung und null versteckten Hafengebühren (Zahlung vor Ort in EUR, USD, GBP oder EGP). Transfer, Boot, Betreuung und Ausrüstung sind inklusive; es fällt lediglich die staatliche Nationalparkgebühr (€5/Person für Ras Mohammed/Tiran) an."
                : ($profile === 'bundle' || $profile === 'course_beginner' || $profile === 'course_cert'
                    ? "Der Direktpreis für „{$name}“ liegt bei {$price} (0 € Online-Anzahlung, komplette Leihausrüstung inklusive). Klar ausgewiesene Extras sind nur die offizielle PADI eLearning-/Zertifikatsgebühr (€85/Kurs, beim DSD bereits enthalten) sowie Bootstage (€10 Boot inkl. Mittagsbuffet + €5 Parkgebühr)."
                    : "Der Direktpreis für „{$name}“ beträgt {$price} bei 0 € Online-Anzahlung und ohne Überraschungen am Hafen. An Bootstagen gelten feste Tageszuschläge: €10/Tag Bootszuschlag (inkl. Mittagsbuffet & Softdrinks) + €5/Tag Nationalparkgebühr; am Hausriff entfallen beide Gebühren.");

            $qPickup = "Aus welchen Hotelzonen in Sharm El Sheikh ist der Transfer für „{$name}“ kostenlos?";
            $aPickup = "Unser kostenloser klimatisierter Hin- & Rücktransfer deckt alle 6 Hotelzonen in Sharm El Sheikh ab (75+ Resorts): Nabq Bay (~07:45), Sharks Bay & SOHO (~08:05), Naama Bay (~07:50), Hadaba & Ras Um El Sid (~07:35), Old Market (~07:35) sowie Ras Nasrani / Al Montazah (0–5 Min. – direkt am Strand des Cyrene Sharm). Rückkehr ca. 16:30 Uhr.";

            $qReq = $isBeginnerNoSwim
                ? "Brauche ich Vorerfahrung oder sichere Schwimmkenntnisse für „{$name}“?"
                : "Welche Voraussetzungen (Brevet, Alter, Schwimmen) gelten für „{$name}“?";
            $aReq = $isBeginnerNoSwim
                ? ($profile === 'kids'
                    ? "Keine Vorerfahrung nötig! Ab 8 Jahren tauchen Kinder im flachen Trainingspool (<2 m) mit speziellem Kinderequipment direkt an der Seite ihres PADI-Instructors."
                    : "Weder Taucherfahrung noch Schwimmabzeichen erforderlich! Beim Schnuppertauchen begleitet Sie Ihr PADI-Instructor 1-zu-1 an der Hand und steuert Ihre Tarierung (für Schnorchler stehen Schwimmwesten bereit).")
                : ($id === 'sup-thistlegorm'
                    ? "Für die SS Thistlegorm (16–30 m) benötigen Sie mindestens PADI Advanced Open Water (oder 30-m-Äquivalent) und 20 geloggte Tauchgänge."
                    : ($profile === 'course_beginner' || $profile === 'bundle'
                        ? "Geeignet für Einsteiger ab 10 Jahren ohne Vorerfahrung; PADI-Standards verlangen lediglich 200 m Schwimmen ohne Zeitlimit und 10 Minuten Wassertreten/Schweben."
                        : "Voraussetzung ist ein Tauchbrevet (Open Water oder höher, Mindestalter 10 Jahre). Bitte 18–24 Stunden Flugpause nach dem letzten Tauchgang einplanen (PADI/DAN)."));

            $qGroup = "Wie groß sind die Gruppen pro PADI-Guide und können Paare gemeinsam aufs Boot?";
            $aGroup = "Zertifizierte Gruppen sind bei uns strikt auf MAXIMAL 4 Taucher pro PADI-Guide begrenzt (beim Schnuppertauchen 1-zu-1 Betreuung). Gemischte Paare fahren gemeinsam auf unseren großen Yachten (Oceano, Nizar, Omar 4): ein Partner taucht im Paket, der andere schnorchelt oder macht einen Boot-Schnuppertauchgang!";

            $qCheck = "Muss ich vor „{$name}“ einen Check-Dive machen oder entfällt er?";
            $aCheck = $isBeginnerNoSwim || $profile === 'course_beginner'
                ? "Kein separater Check-Dive nötig – die ruhige Eingewöhnung im Flachwasser mit Ihrem PADI-Instructor ist bereits fester Bestandteil des Programms."
                : "Check-Dive-Skip-Regel: Mit einem geloggten Tauchgang innerhalb der letzten 6 Monate (Logbuch oder PADI/SSI-App) ÜBERSPRINGEN Sie den separaten Check-Dive und starten an Tag 1 direkt vom Boot! Bei 6–12 Monaten Pause gilt ein €25 Check Dive, ab 12 Monaten ein €50 Scuba Review.";

            $qGear = "Ist die Leihausrüstung inklusive und was kostet Nitrox 32 %?";
            $aGear = $allInGear
                ? "Die komplette gewartete Cressi/Mares-Ausrüstung ist im Preis von „{$name}“ bereits zu 100 % INKLUSIVE! Für zertifizierte Nitrox-Taucher kostet eine 12L-EAN32-Flasche €8."
                : "12L-Aluflaschen und Blei sind inklusive. Ohne eigene Ausrüstung kostet das komplette Cressi/Mares-Leihset €25/Tag (Einzelteile €4–€13/Tag) und eine 12L-Nitrox-32%-Füllung €8.";
            break;

        case 'fr':
            $qPrice = "Le tarif de {$price} pour « {$name} » est-il garanti sans frais cachés à la marina ?";
            $aPrice = $isBoatAllIn
                ? "Oui ! Le tarif direct en ligne de « {$name} » est de {$price} avec 0 € d’acompte et zéro frais cachés au port (règlement sur place en EUR, USD, GBP ou EGP). Transfert hôtel, yacht, moniteur et matériel sont inclus ; seule la taxe gouvernementale du parc marin (€5/pers. à Ras Mohammed ou Tiran) s’ajoute."
                : ($profile === 'bundle' || $profile === 'course_beginner' || $profile === 'course_cert'
                    ? "Le tarif direct de « {$name} » est de {$price} (0 € d’acompte en ligne, équipement complet inclus). Seuls s’ajoutent en toute transparence le kit/certification officielle PADI PIC (€85/cours, déjà inclus pour le DSD) et les éventuelles sorties bateau (€10 bateau déjeuner inclus + €5 parc marin)."
                    : "Le tarif direct de « {$name} » est de {$price} avec 0 € d’acompte en ligne et zéro mauvaise surprise. Les jours bateau ajoutent uniquement €10/jour de supplément yacht (déjeuner buffet chaud & boissons inclus) + €5/jour de parc marin ; aucun supplément sur le récif maison.");

            $qPickup = "Quelles zones hôtelières de Sharm El Sheikh incluent la navette gratuite pour « {$name} » ?";
            $aPickup = "Le transfert climatisé aller-retour gratuit couvre les 6 zones de Sharm El Sheikh (75+ hôtels) : Nabq Bay (~07h45), Sharks Bay & SOHO (~08h05), Naama Bay (~07h50), Hadaba & Ras Um El Sid (~07h35), Old Market (~07h35) et Ras Nasrani / Al Montazah (0–5 min, sur la plage du Cyrene Sharm). Retour vers 16h30.";

            $qReq = $isBeginnerNoSwim
                ? "Faut-il savoir nager ou avoir déjà plongé pour réserver « {$name} » ?"
                : "Quels sont les prérequis (niveau, âge, natation) pour « {$name} » ?";
            $aReq = $isBeginnerNoSwim
                ? ($profile === 'kids'
                    ? "Aucune expérience requise ! Dès 8 ans dans notre piscine peu profonde (<2 m) avec matériel enfant adapté et supervision directe d’un instructeur PADI."
                    : "Aucune expérience ni savoir nager requis ! En baptême, votre instructeur PADI vous guide en 1-contre-1 par la main et gère votre gilet (gilets de flottaison fournis en snorkeling).")
                : ($id === 'sup-thistlegorm'
                    ? "Pour l’épave du SS Thistlegorm (16–30 m), le niveau PADI Advanced Open Water (ou équivalent 30 m) et un minimum de 20 plongées enregistrées sont requis."
                    : ($profile === 'course_beginner' || $profile === 'bundle'
                        ? "Accessible dès 10 ans sans expérience préalable ; les standards PADI demandent simplement une nage de 200 m sans chrono et 10 minutes de flottaison."
                        : "Réservé aux plongeurs certifiés (Open Water ou plus, dès 10 ans). Prévoyez un intervalle de sécurité de 18 à 24 h avant votre vol retour (normes PADI/DAN)."));

            $qGroup = "Combien y a-t-il de plongeurs par guide PADI et un couple mixte peut-il monter sur le même yacht ?";
            $aGroup = "Nous limitons nos palanquées certifiées à un MAXIMUM strict de 4 plongeurs par guide PADI (et 1-contre-1 en baptême). Les couples mixtes embarquent ensemble sur nos grands yachts (Oceano, Nizar, Omar 4) : le certifié plonge pendant que son partenaire fait du snorkeling ou un baptême bateau !";

            $qCheck = "Puis-je éviter le Check Dive avant « {$name} » ?";
            $aCheck = $isBeginnerNoSwim || $profile === 'course_beginner'
                ? "Aucun check dive séparé n’est requis : l’initiation progressive en eau peu profonde avec votre instructeur PADI fait déjà partie du programme."
                : "Règle d’exemption du Check Dive : si vous justifiez d’une plongée enregistrée dans les 6 derniers mois (carnet ou appli PADI/SSI), vous ÉVITEZ le check dive séparé et partez directement en bateau ! Arrêt de 6–12 mois : Check Dive (€25) ; plus de 12 mois : Scuba Review (€50).";

            $qGear = "La location de l’équipement est-elle incluse et combien coûte le Nitrox 32 % ?";
            $aGear = $allInGear
                ? "Tout l’équipement complet Cressi/Mares désinfecté est 100 % INCLUS sans supplément dans « {$name} » ! Pour les certifiés Nitrox, le bloc 12L EAN32 est à €8."
                : "Les blocs 12L air et plombs sont inclus. Sans matériel personnel, le pack complet Cressi/Mares est à €25/jour (pièces à l’unité €4–€13/jour) et le bloc Nitrox 32 % (12L) à €8.";
            break;

        case 'ar':
            $qPrice = "هل سعر {$price} لـ «{$name}» شامل وبدون أي رسوم مارينا خفية؟";
            $aPrice = $isBoatAllIn
                ? "نعم! السعر المباشر عبر الموقع لـ «{$name}» هو {$price} بـ 0€ دفعة مقدمة وبدون أي رسوم مارينا خفية (الدفع صباح الرحلة باليورو أو الدولار أو الجنيه). السعر يشمل انتقالات الفندق واليخت والمعدات والإشراف؛ وتُضاف فقط تذكرة المحمية الحكومية (5€ للفرد عند دخول رأس محمد أو تيران)."
                : ($profile === 'bundle' || $profile === 'course_beginner' || $profile === 'course_cert'
                    ? "السعر المباشر لـ «{$name}» هو {$price} بـ 0€ دفعة مقدمة ويشمل معدات الغوص الكاملة. الإضافات الوحيدة المعلنة بوضوح هي رسوم كتاب وشهادة PADI الرسمية (85€ للدورة، ومشمولة مجانًا في DSD) ورسوم أيام اليخت إن وجدت (10€ لليخت بالغداء + 5€ للمحمية)."
                    : "السعر المباشر لـ «{$name}» هو {$price} بـ 0€ دفعة مقدمة وصفر رسوم خفية في المارينا. في أيام القارب تُضاف فقط رسوم ثابتة وواضحة: 10€ يوميًا لليخت (شاملة الغداء والمشروبات) + 5€ رسوم المحمية؛ بينما لا توجد أي رسوم إضافية على الشعاب المنزلية.");

            $qPickup = "ما هي مناطق فنادق شرم الشيخ المشمولة بالتوصيل المجاني في «{$name}»؟";
            $aPickup = "يشمل التوصيل المجاني المكيف ذهابًا وإيابًا جميع مناطق شرم الشيخ الست (+75 فندقًا): خليج نبق (~07:45)، شاركس باي وسوهو (~08:05)، خليج نعمة (~07:50)، الهضبة ورأس أم السيد (~07:35)، السوق القديم (~07:35)، ورأس نصراني والمنتزه (0–5 دقائق، مقرنا مباشرة على شاطئ سيرين شرم). العودة للفندق نحو 16:30.";

            $qReq = $isBeginnerNoSwim
                ? "هل يُشترط إجادة السباحة أو خبرة غوص سابقة لحجز «{$name}»؟"
                : "ما شروط الشهادة والعمر والسباحة المطلوبة لـ «{$name}»؟";
            $aReq = $isBeginnerNoSwim
                ? ($profile === 'kids'
                    ? "لا تُشترط أي خبرة سابقة! البرنامج مخصص للأطفال من سن 8 سنوات داخل مسبح التدريب الضحل (<2 متر) بمعدات مقاسات الأطفال وإشراف مباشر من مدرب PADI."
                    : "لا يُشترط إجادة السباحة ولا أي خبرة سابقة! في الغطسات التجريبية يرافقك مدرب PADI بشكل فردي (1 إلى 1) يدًا بيد ويتحكم في طفوك بالكامل (وفي السنوركلينج نوفر سترات طفو مريحة).")
                : ($id === 'sup-thistlegorm'
                    ? "للغوص على حطام SS Thistlegorm (عمق 16–30م) يُشترط شهادة PADI Advanced Open Water على الأقل مع 20 غطسة مسجلة."
                    : ($profile === 'course_beginner' || $profile === 'bundle'
                        ? "متاحة للمبتدئين تمامًا من سن 10 سنوات؛ وتشترط معايير PADI للشهادات الدولية فقط سباحة 200 متر بدون وقت محدد والطفو لمدة 10 دقائق."
                        : "يُشترط حمل شهادة غوص معتمدة (Open Water فأعلى، من سن 10 سنوات)، مع مراعاة ترك 18–24 ساعة قبل رحلة الطيران وفق قواعد PADI وDAN."));

            $qGroup = "كم عدد الغواصين مع كل مرشد PADI وهل يمكن للأزواج (معتمد + مبتدئ) ركوب نفس اليخت؟";
            $aGroup = "نلتزم بحد أقصى صارم لا يتجاوز 4 غواصين معتمدين لكل مرشد PADI (ورعاية فردية 1 إلى 1 للمبتدئين). ونرحب بالأزواج والعائلات معًا على نفس اليخت الكبير (Oceano وNizar وOmar 4): يغوص المعتمد مع مجموعته بينما يستمتع شريكه بالسنوركلينج أو بغطسة تجريبية والغداء معًا!";

            $qCheck = "متى أُعفى من غطسة الاختبار (Check Dive) قبل «{$name}»؟";
            $aCheck = $isBeginnerNoSwim || $profile === 'course_beginner'
                ? "لا حاجة لأي غطسة اختبار منفصلة! فالشرح والتدريب التمهيدي في المياه الضحلة مع مدرب PADI مشمول بالفعل ضمن برنامجك."
                : "قاعدة الإعفاء من Check Dive: إذا كان لديك غطسة مسجلة خلال آخر 6 أشهر في السجل أو تطبيق PADI/SSI، تُعفى تمامًا من غطسة الاختبار المنفصلة وتنطلق مباشرة باليخت! عند الانقطاع 6–12 شهرًا تلزم Check Dive (25€)، وأكثر من سنة Scuba Review (50€).";

            $qGear = "هل إيجار معدات الغوص مشمول وكم سعر أسطوانة النيتروكس 32%؟";
            $aGear = $allInGear
                ? "طقم معدات الغوص الكامل والمعقم (Cressi/Mares) مشمول مجانًا 100% في سعر «{$name}»! ولحاملي شهادة النيتروكس تتوفر أسطوانة 12 لتر EAN32 مقابل 8€."
                : "يشمل السعر أسطوانات هواء 12 لتر وحزام الأوزان. لمن لا يحضر معداته الخاصة يتوفر إيجار الطقم الكامل بـ25€ لليوم (أو بالقطعة 4€–13€)، وتعبئة النيتروكس 32% (12 لتر) بـ8€.";
            break;

        case 'zh':
            $qPrice = "「{$name}」的 {$price} 欧元价格是一价透明吗？有没有码头隐形费用？";
            $aPrice = $isBoatAllIn
                ? "完全透明！「{$name}」官网直订价为 {$price}，在线预订 €0 定金，绝无任何码头隐形收费（当天到店支持欧元/美元/英镑/埃镑现金或刷卡）。价格已含接送、游艇、教练与装备；进入穆罕默德角或蒂朗保护区仅需付政府海洋公园门票 €5/人。"
                : ($profile === 'bundle' || $profile === 'course_beginner' || $profile === 'course_cert'
                    ? "「{$name}」官网直订价为 {$price}（€0 预付定金，已免费包含全套潜水装备）。唯一公开透明的另付项仅为 PADI 官方电子教材与证书签发费（€85/门，DSD 已免费包含）及船潜日附加费（€10 游艇含热午餐 + €5 海洋公园费）。"
                    : "「{$name}」官网直订价为 {$price}，€0 预付定金，100% 零隐形消费。船潜日仅收固定透明附加费：€10/天游艇费（含现做自助热午餐与软饮）+ €5/天海洋公园费；门前珊瑚礁岸潜无任何附加费。");

            $qPickup = "预订「{$name}」包含沙姆沙伊赫哪些酒店区域的免费接送？";
            $aPickup = "免费空调专车往返接送覆盖沙姆全部 6 大酒店区（75+ 家度假村）：纳布克湾 Nabq（约07:45）、鲨鱼湾 Sharks Bay & SOHO（约08:05）、娜玛湾 Naama Bay（约07:50）、哈达巴 Hadaba（约07:35）、老市场 Old Market（约07:35）及 Al Montazah（0–5分钟，潜店就在 Cyrene Sharm 海滩内），下午约 16:30 送回酒店。";

            $qReq = $isBeginnerNoSwim
                ? "参加「{$name}」需要潜水经验或必须会游泳吗？"
                : "参加「{$name}」需要什么潜水证书、年龄或游泳条件？";
            $aReq = $isBeginnerNoSwim
                ? ($profile === 'kids'
                    ? "完全不需要经验或泳姿要求！满 8 周岁儿童即可在潜店浅水训练池（<2米）穿戴儿童专属轻便装备，由 PADI 教练全程贴身指导。"
                    : "零基础、不会游泳均可参加！体验潜水由 PADI 教练 1对1 全程牵手带潜并控制浮力背心（浮潜免费提供合身救生衣与浮潜向导）。")
                : ($id === 'sup-thistlegorm'
                    ? "二战沉船 SS Thistlegorm（16–30米）要求持有 PADI AOW 进阶证书（或同等30米资质）且至少 20 潜记录。"
                    : ($profile === 'course_beginner' || $profile === 'bundle'
                        ? "满 10 周岁零基础即可报名；按 PADI 国际考证标准，仅需完成不限时的 200 米游泳及 10 分钟水面漂浮测试。"
                        : "需持有有效潜水员证书（OW 开放水域或以上，满 10 周岁）。按 PADI/DAN 规范，多次潜水后请预留 18–24 小时禁飞时间。"));

            $qGroup = "每个 PADI 导潜最多带几个人？持证潜员能和新手伴侣同船出海吗？";
            $aGroup = "我们严格执行小团制：每位 PADI 导潜最多带 4 名持证潜水员（新手体验潜为 1对1 专属带潜）！非常欢迎情侣与家庭同登我们的大游艇（Oceano、Nizar、Omar 4）：持证者参加船潜，伴侣同船浮潜或加订游艇体验潜，中午一起享用热午餐！";

            $qCheck = "参加「{$name}」前需要做测潜（Check Dive）吗？什么情况可免测潜？";
            $aCheck = $isBeginnerNoSwim || $profile === 'course_beginner'
                ? "无需单独测潜！教练的浅水适应与技巧指导已完整包含在您的行程中。"
                : "免测潜规则：只要潜水日志或 PADI/SSI App 显示最近 6 个月内有过潜水记录，即可免去单独测潜，第一天直接登船出海！若间隔 6–12 个月需做 €25 测潜（Check Dive），超过 12 个月需做 €50 复习潜（Scuba Review）。";

            $qGear = "费用包含全套潜水装备租赁吗？高氧 Nitrox 32% 多少钱？";
            $aGear = $allInGear
                ? "「{$name}」已 100% 免费包含全套消毒保养的 Cressi/Mares 潜水装备，无需额外租借！持高氧证潜员升级 12L EAN32 高氧气瓶仅 €8/瓶。"
                : "套餐已含 12L 铝气瓶与配重铅块；若不自带个人装备，租用全套 Cressi/Mares 装备为 €25/天（单件 €4–€13/天），升级 12L 高氧 Nitrox 32% 仅 €8/瓶。";
            break;

        default: // 'en'
            $qPrice = "Is the {$price} price for {$name} transparent with zero hidden marina fees?";
            $aPrice = $isBoatAllIn
                ? "Yes! Our direct online rate for {$name} is {$price} with €0 upfront card deposit and 100% zero hidden marina fees (pay on arrival in EUR, USD, GBP, or EGP). Hotel transfers, yacht/boat, instructor/guide supervision, and gear are included; only the €5 Egyptian Marine Park entry ticket applies when entering Ras Mohammed or Tiran reserves."
                : ($profile === 'bundle' || $profile === 'course_beginner' || $profile === 'course_cert'
                    ? "Our direct online price for {$name} is {$price} with €0 upfront deposit and full scuba equipment included. Every optional/statutory extra is disclosed upfront: the official PADI eLearning/manual & PIC card fee (€85/course, already included free in DSD) plus €10 Boat Supplement (with buffet lunch) & €5 Marine Park Fee on boat training days."
                    : "Our direct online price for {$name} is {$price} with €0 upfront card deposit and 100% zero surprise marina charges. On boat days, fixed daily supplements are disclosed upfront: €10/day Boat Supplement (includes freshly cooked lunch & soft drinks) + €5/day Marine Park Fee; Al Montazah House Reef shore dives have zero boat or park fees.");

            $qPickup = "Which Sharm El Sheikh hotel zones get free pickup for {$name}?";
            $aPickup = "Complimentary air-conditioned return hotel transfers cover all 6 Sharm El Sheikh resort zones (75+ hotels): Nabq Bay (~07:45), Sharks Bay & SOHO Square (~08:05), Naama Bay (~07:50), Hadaba & Ras Um El Sid (~07:35), Old Market (~07:35), and Ras Nasrani / Al Montazah (0–5 min — right on Cyrene Sharm beach). Full-day boats return around 16:30.";

            $qReq = $isBeginnerNoSwim
                ? "Do I need prior diving experience or strong swimming skills for {$name}?"
                : "What certification, minimum age, or swimming requirements apply to {$name}?";
            $aReq = $isBeginnerNoSwim
                ? ($profile === 'kids'
                    ? "Zero prior experience or competitive swimming strokes needed! Held from age 8 in our calm, shallow resort training pool (<2 m) using lightweight child scuba gear right beside a PADI Instructor."
                    : "Zero prior scuba experience and no swimming test required! On Beginner Intro Dives, your PADI Instructor guides you 1-on-1 holding your hand and controlling your buoyancy (and lifejackets are provided for all snorkelers).")
                : ($id === 'sup-thistlegorm'
                    ? "Diving the WWII SS Thistlegorm wreck (16–30 m) requires at least PADI Advanced Open Water (or 30 m equivalent) and a minimum of 20 logged dives, booked 72h ahead with passport copy."
                    : ($profile === 'course_beginner' || $profile === 'bundle'
                        ? "Open to complete beginners aged 10+ with zero prior diving experience; PADI certification standards simply require an untimed 200 m swim and a relaxed 10-minute water float."
                        : "Requires a valid scuba certification (Open Water or higher, minimum age 10). Always allow an 18–24 hour surface interval before flying per PADI/DAN guidelines."));

            $qGroup = "What is the maximum group size per PADI guide, and can mixed couples join the same yacht?";
            $aGroup = "We enforce a strict MAXIMUM of 4 certified divers per PADI Dive Guide (and 1-on-1 PADI Instructor supervision on Beginner Intro Dives). Mixed couples and families are welcome on the same spacious motor yacht (Oceano, Nizar, Omar 4): certified divers dive reef walls while partners snorkel or add a Boat Intro Dive and share buffet lunch!";

            $qCheck = "When can I skip the Check Dive before {$name}?";
            $aCheck = $isBeginnerNoSwim || $profile === 'course_beginner'
                ? "No separate check dive is needed — calm shallow-water skill orientation with your PADI Instructor is built right into your session."
                : "Check-Dive Skip Rule: If your logbook or PADI/SSI app shows a logged dive within the last 6 months, you SKIP the separate check dive and head straight onto our Ras Mohammed or Tiran boat on Day 1! Under CDWS safety rules, 6–12 months inactive requires a €25 Check Dive; >12 months requires a €50 Scuba Review.";

            $qGear = "Is scuba equipment rental included in {$name}, and how much is Nitrox 32%?";
            $aGear = $allInGear
                ? "Full sanitized Cressi/Mares equipment is 100% INCLUDED in the {$price} price of {$name} at zero extra cost! For certified Nitrox divers, 12L Enriched Air Nitrox (EAN32) fills are €8/tank."
                : "12L aluminum air cylinders, weight belt, and weights are included. Travelling without personal gear? Rent a full sanitized Cressi/Mares scuba set for €25/day (or individual pieces €4–€13/day) and upgrade to Enriched Air Nitrox 32% (12L) for €8/tank.";
            break;
    }

    return [
        [$qPrice, $aPrice],
        [$qPickup, $aPickup],
        [$qReq, $aReq],
        [$qGroup, $aGroup],
        [$qCheck, $aCheck],
        [$qGear, $aGear],
    ];
}

/**
 * Merge bespoke per-item FAQs with the 6 high-intent Traveller FAQs, avoiding duplicate topic questions.
 * @param array<int, array{0:string, 1:string}> $existing
 * @param array<int, array{0:string, 1:string}> $traveller
 * @return array<int, array{0:string, 1:string}>
 */
function sc_merge_item_faqs(array $existing, array $traveller): array
{
    $out = $existing;
    $haystack = '';
    foreach ($existing as $pair) {
        $haystack .= ' ' . mb_strtolower(($pair[0] ?? '') . ' ' . ($pair[1] ?? ''));
    }

    // Topic detectors corresponding to the 6 traveller FAQs:
    // 0: hidden fees / deposit, 1: pickup zones, 2: swim / beginner / age, 3: max 4 / guide / couple, 4: check dive / 6 months, 5: rental / nitrox
    $topicNeedles = [
        0 => ['hidden', 'deposit', 'скрыт', 'предоплат', 'nascost', 'acconto', 'ukryt', 'zaliczk', 'versteckt', 'anzahlung', 'caché', 'acompte', 'خفية', 'دفعة مقدمة', '隐形', '定金'],
        1 => ['nabq', 'sharks bay', 'naama', 'pickup', 'zone', 'набк', 'трансфер', 'transfer', 'stref', 'abhol', 'navette', 'نبق', 'توصيل', 'انتقالات', '接送', '纳布克'],
        2 => ['non-swimmer', 'swim', 'minimum age', 'плава', 'возраст', 'nuot', 'età', 'pływa', 'wiek', 'schwimm', 'alter', 'nager', 'âge', 'سباحة', 'العمر', '游泳', '年龄'],
        3 => ['max 4', '4 divers', 'maximum of 4', '4 человек', '4 sub', '4 nurk', '4 taucher', '4 plongeurs', '4 غواصين', '最多带 4', '最多4'],
        4 => ['check dive', 'check-dive', 'scuba review', '6 month', 'чек-дайв', '6 мес', '6 mesi', '6 mies', '6 monat', '6 dernier', '6 أشهر', 'اختبار', '测潜', '6 个月'],
        5 => ['€25/day', '25€/day', 'nitrox', 'cressi', 'прокат', 'noleggio', 'wypożycz', 'leihausrüstung', 'location', 'تأجير', 'إيجار', 'نيتروكس', '租赁', '高氧'],
    ];

    foreach ($traveller as $idx => $qa) {
        $needles = $topicNeedles[$idx] ?? [];
        $alreadyCovered = false;
        // Always include Max 4/guide (idx 3) and Zero Hidden Fees (idx 0) unless explicitly covered
        foreach ($needles as $n) {
            if ($n !== '' && mb_strpos($haystack, $n) !== false) {
                $alreadyCovered = true;
                break;
            }
        }
        if (!$alreadyCovered || count($out) < 6) {
            // Avoid exact duplicate question string
            $dupQ = false;
            foreach ($out as $ex) {
                if (mb_strtolower(trim($ex[0])) === mb_strtolower(trim($qa[0]))) {
                    $dupQ = true;
                    break;
                }
            }
            if (!$dupQ) {
                $out[] = $qa;
                $haystack .= ' ' . mb_strtolower($qa[0] . ' ' . $qa[1]);
            }
        }
    }

    return array_slice($out, 0, 9);
}

/**
 * Enrich resolved item SEO metadata, AEO answer, and Per-Trip Traveller FAQs across all 8 languages.
 */
function sc_enrich_item_seo(array $it, array $seo, array $resolved): array
{
    $lang = defined('LANG') ? LANG : 'en';

    // Append concise conversion/AEO trust tail to meta_desc if short (<122 chars) so Google Ads QS & organic CTR climb
    $trustTail = match ($lang) {
        'ru' => ' Макс. 4 дайвера/гид · €0 предоплата!',
        'it' => ' Max 4 sub/guida · €0 acconto!',
        'pl' => ' Maks. 4 nurków/przewodnika · 0 € zaliczki!',
        'de' => ' Max. 4 Taucher/Guide · 0 € Anzahlung!',
        'fr' => ' Max 4 plongeurs/guide · 0 € d’acompte !',
        'ar' => ' حد 4 غواصين للمرشد · 0€ دفعة مقدمة!',
        'zh' => ' 每导潜最多4人 · €0定金直订！',
        default => ' Max 4/guide, free hotel pickup & €0 deposit!',
    };

    $desc = trim((string) ($resolved['meta_desc'] ?? ''));
    if ($desc !== '' && mb_strlen($desc) <= 122 && mb_strpos($desc, '4') === false && mb_strpos($desc, '€0') === false && mb_strpos($desc, '0 €') === false) {
        $resolved['meta_desc'] = rtrim($desc, '.') . '.' . $trustTail;
    }

    // Enrich AEO Quick Answer with Check-Dive <6m skip & Max 4/guide + €0 deposit if not already mentioned
    $aeo = trim((string) ($resolved['aeo_answer'] ?? ''));
    if ($aeo !== '' && mb_strpos($aeo, '4') === false) {
        $aeoExtra = match ($lang) {
            'ru' => ' Бронирование онлайн — €0 предоплаты, группы до 4 сертифицированных дайверов на гида PADI и бесплатный трансфер из всех 6 зон Шарм-эль-Шейха.',
            'it' => ' Prenotazione online con €0 di acconto, gruppi di massimo 4 sub brevettati per guida PADI e transfer gratuito da tutte le 6 zone di Sharm El Sheikh.',
            'pl' => ' Rezerwacja online z 0 € zaliczki, grupy maks. 4 certyfikowanych nurków na przewodnika PADI oraz darmowy transfer ze wszystkich 6 stref Sharm El Sheikh.',
            'de' => ' Online-Reservierung mit 0 € Anzahlung, Kleingruppen mit maximal 4 zertifizierten Tauchern pro PADI-Guide und Gratis-Transfer aus allen 6 Hotelzonen in Sharm El Sheikh.',
            'fr' => ' Réservation en ligne avec 0 € d’acompte, palanquées limitées à 4 plongeurs certifiés maximum par guide PADI et transfert gratuit depuis les 6 zones de Sharm El Sheikh.',
            'ar' => ' الحجز الإلكتروني بـ0€ دفعة مقدمة، ومجموعات صغيرة بحد أقصى 4 غواصين معتمدين لكل مرشد PADI مع انتقالات مجانية من جميع مناطق شرم الشيخ الست.',
            'zh' => ' 官网在线预订 €0 定金，严格执行每名 PADI 导潜最多带 4 位持证潜员小团标准，并含沙姆沙伊赫 6 大酒店区免费往返接送。',
            default => ' Book online with €0 upfront deposit, strict max 4 certified divers per PADI guide, and free return hotel pickup across all 6 Sharm El Sheikh zones.',
        };
        $resolved['aeo_answer'] = $aeo . $aeoExtra;
    }

    $travellerFaqs = sc_build_item_traveller_faqs($it);
    $resolved['faqs'] = sc_merge_item_faqs($resolved['faqs'] ?? [], $travellerFaqs);

    return $resolved;
}

/**
 * Smart Cross-Sell & Upgrade Matrix for "Related Trips & Recommended Upgrades" on item.php.
 * Returns array of ['item' => array, 'badge_key' => string].
 *
 * @return array<int, array{item: array, badge_key: string}>
 */
function sc_recommended_upgrades(array $catalog, array $current, array $seoRelated = [], int $limit = 4): array
{
    $all = catalog_all_items($catalog);
    $id  = (string) ($current['id'] ?? '');
    $cat = (string) ($current['_cat_key'] ?? '');

    // Explicit smart cross-sell rules mapped to real booking upgrade paths
    $matrix = [
        // OWD -> Stars €500 Bundle, AOWD €350, Mega €600 Bundle, Heros €750 Bundle
        'owd' => [
            ['bundle-stars', 'upg_badge_bundle'],
            ['aowd',         'upg_badge_next_step'],
            ['bundle-mega',  'upg_badge_bundle'],
            ['bundle-heros', 'upg_badge_bundle'],
        ],
        'scuba-diver' => [
            ['upgrade-owd',  'upg_badge_next_step'],
            ['owd',          'upg_badge_next_step'],
            ['bundle-stars', 'upg_badge_bundle'],
            ['dsd-2-boat',   'upg_badge_boat_upgrade'],
        ],
        'upgrade-owd' => [
            ['nitrox',       'upg_badge_nitrox'],
            ['aowd',         'upg_badge_next_step'],
            ['bundle-super', 'upg_badge_bundle'],
            ['dive-6',       'upg_badge_pack_saver'],
        ],
        // Shore Intro -> Boat Intro (€70 / €100) & Official PADI DSD (€85) & OWD (€400)
        'dsd-quick-reef' => [
            ['dsd-quick-boat', 'upg_badge_boat_upgrade'],
            ['dsd-2-boat',     'upg_badge_boat_upgrade'],
            ['dsd',            'upg_badge_padi_credit'],
            ['owd',            'upg_badge_next_step'],
        ],
        'dsd-2-reef' => [
            ['dsd-2-boat',     'upg_badge_boat_upgrade'],
            ['dsd-quick-boat', 'upg_badge_boat_upgrade'],
            ['dsd',            'upg_badge_padi_credit'],
            ['owd',            'upg_badge_next_step'],
        ],
        // Boat Intro & DSD -> 2-Dive Boat Intro, DSD, Scuba Diver, OWD
        'dsd-quick-boat' => [
            ['dsd-2-boat',   'upg_badge_boat_upgrade'],
            ['dsd',          'upg_badge_padi_credit'],
            ['owd',          'upg_badge_next_step'],
            ['scuba-diver',  'upg_badge_next_step'],
        ],
        'dsd-2-boat' => [
            ['dsd',          'upg_badge_padi_credit'],
            ['owd',          'upg_badge_next_step'],
            ['bundle-stars', 'upg_badge_bundle'],
            ['scuba-diver',  'upg_badge_next_step'],
        ],
        'dsd' => [
            ['dsd-2-boat',   'upg_badge_boat_upgrade'],
            ['owd',          'upg_badge_next_step'],
            ['bundle-stars', 'upg_badge_bundle'],
            ['scuba-diver',  'upg_badge_next_step'],
        ],
        // 1–5 Dive Packs -> 6/10-Dive Packs (€35/dive) & SS Thistlegorm (€150) & Nitrox
        'dive-1' => [
            ['dive-2',         'upg_badge_boat_upgrade'],
            ['dive-6',         'upg_badge_pack_saver'],
            ['dive-10',        'upg_badge_pack_saver'],
            ['sup-thistlegorm','upg_badge_wreck'],
        ],
        'dive-2' => [
            ['dive-6',         'upg_badge_pack_saver'],
            ['dive-10',        'upg_badge_pack_saver'],
            ['sup-thistlegorm','upg_badge_wreck'],
            ['nitrox',         'upg_badge_nitrox'],
        ],
        'dive-3' => [
            ['dive-6',         'upg_badge_pack_saver'],
            ['dive-10',        'upg_badge_pack_saver'],
            ['sup-thistlegorm','upg_badge_wreck'],
            ['nitrox',         'upg_badge_nitrox'],
        ],
        'dive-4' => [
            ['dive-6',         'upg_badge_pack_saver'],
            ['dive-10',        'upg_badge_pack_saver'],
            ['sup-thistlegorm','upg_badge_wreck'],
            ['bundle-super',   'upg_badge_bundle'],
        ],
        'dive-5' => [
            ['dive-6',         'upg_badge_pack_saver'],
            ['dive-10',        'upg_badge_pack_saver'],
            ['sup-thistlegorm','upg_badge_wreck'],
            ['bundle-super',   'upg_badge_bundle'],
        ],
        'dive-6' => [
            ['dive-10',          'upg_badge_pack_saver'],
            ['sup-thistlegorm',  'upg_badge_wreck'],
            ['sup-zodiac-early', 'upg_badge_popular'],
            ['bundle-super',     'upg_badge_bundle'],
        ],
        'dive-7' => [
            ['dive-10',          'upg_badge_pack_saver'],
            ['sup-thistlegorm',  'upg_badge_wreck'],
            ['sup-zodiac-early', 'upg_badge_popular'],
            ['nitrox',           'upg_badge_nitrox'],
        ],
        'dive-8' => [
            ['dive-10',          'upg_badge_pack_saver'],
            ['sup-thistlegorm',  'upg_badge_wreck'],
            ['sup-zodiac-early', 'upg_badge_popular'],
            ['nitrox',           'upg_badge_nitrox'],
        ],
        'dive-9' => [
            ['dive-10',          'upg_badge_pack_saver'],
            ['sup-thistlegorm',  'upg_badge_wreck'],
            ['sup-dahab',        'upg_badge_popular'],
            ['nitrox',           'upg_badge_nitrox'],
        ],
        'dive-10' => [
            ['sup-thistlegorm',  'upg_badge_wreck'],
            ['sup-zodiac-early', 'upg_badge_popular'],
            ['sup-dahab',        'upg_badge_popular'],
            ['bundle-super',     'upg_badge_bundle'],
        ],
        // Snorkeling -> Boat Intro (€70 / €100) & Private Speedboat (€150) & Private Yacht (€550)
        'snork-ras-adult' => [
            ['dsd-quick-boat', 'upg_badge_try_dive'],
            ['dsd-2-boat',     'upg_badge_try_dive'],
            ['speed-2h',       'upg_badge_vip_boat'],
            ['private-1-5',    'upg_badge_vip_boat'],
        ],
        'snork-ras-child' => [
            ['bubblemaker',    'upg_badge_try_dive'],
            ['snork-ras-adult','upg_badge_popular'],
            ['speed-2h',       'upg_badge_vip_boat'],
            ['private-1-5',    'upg_badge_vip_boat'],
        ],
        'snork-beach-day' => [
            ['snork-ras-adult','upg_badge_boat_upgrade'],
            ['dsd-quick-boat', 'upg_badge_try_dive'],
            ['speed-2h',       'upg_badge_vip_boat'],
            ['private-1-5',    'upg_badge_vip_boat'],
        ],
        'snork-beach-night' => [
            ['snork-ras-adult','upg_badge_boat_upgrade'],
            ['dsd-quick-boat', 'upg_badge_try_dive'],
            ['speed-2h',       'upg_badge_vip_boat'],
            ['sup-night',      'upg_badge_popular'],
        ],
        'snork-ras-land' => [
            ['snork-ras-adult','upg_badge_boat_upgrade'],
            ['dsd-quick-boat', 'upg_badge_try_dive'],
            ['speed-2h',       'upg_badge_vip_boat'],
            ['private-1-5',    'upg_badge_vip_boat'],
        ],
        // AOWD, Nitrox & Saver Bundles
        'aowd' => [
            ['bundle-super',   'upg_badge_bundle'],
            ['bundle-mega',    'upg_badge_bundle'],
            ['sup-thistlegorm','upg_badge_wreck'],
            ['deep',           'upg_badge_next_step'],
        ],
        'aowd-green' => [
            ['bundle-super',   'upg_badge_bundle'],
            ['sup-thistlegorm','upg_badge_wreck'],
            ['dive-6',         'upg_badge_pack_saver'],
            ['rescue',         'upg_badge_next_step'],
        ],
        'nitrox' => [
            ['bundle-stars',   'upg_badge_bundle'],
            ['bundle-super',   'upg_badge_bundle'],
            ['dive-6',         'upg_badge_pack_saver'],
            ['sup-thistlegorm','upg_badge_wreck'],
        ],
        'bundle-stars' => [
            ['bundle-mega',    'upg_badge_bundle'],
            ['bundle-heros',   'upg_badge_bundle'],
            ['aowd',           'upg_badge_next_step'],
            ['dive-6',         'upg_badge_pack_saver'],
        ],
        'bundle-super' => [
            ['sup-thistlegorm','upg_badge_wreck'],
            ['dive-6',         'upg_badge_pack_saver'],
            ['dive-10',        'upg_badge_pack_saver'],
            ['rescue',         'upg_badge_next_step'],
        ],
        'bundle-mega' => [
            ['bundle-heros',   'upg_badge_bundle'],
            ['nitrox',         'upg_badge_nitrox'],
            ['sup-thistlegorm','upg_badge_wreck'],
            ['dive-6',         'upg_badge_pack_saver'],
        ],
        'bundle-heros' => [
            ['sup-thistlegorm','upg_badge_wreck'],
            ['dive-6',         'upg_badge_pack_saver'],
            ['deep',           'upg_badge_next_step'],
            ['rescue',         'upg_badge_next_step'],
        ],
        // Kids
        'bubblemaker' => [
            ['seal-team',      'upg_badge_next_step'],
            ['dsd',            'upg_badge_padi_credit'],
            ['snork-ras-child','upg_badge_boat_upgrade'],
            ['speed-2h',       'upg_badge_vip_boat'],
        ],
        'seal-team' => [
            ['dsd',            'upg_badge_padi_credit'],
            ['owd',            'upg_badge_next_step'],
            ['snork-ras-child','upg_badge_boat_upgrade'],
            ['speed-2h',       'upg_badge_vip_boat'],
        ],
        // Refresh dives
        'check-dive' => [
            ['dive-6',         'upg_badge_pack_saver'],
            ['dive-10',        'upg_badge_pack_saver'],
            ['scuba-review',   'upg_badge_next_step'],
            ['sup-thistlegorm','upg_badge_wreck'],
        ],
        'scuba-review' => [
            ['dive-6',         'upg_badge_pack_saver'],
            ['dive-10',        'upg_badge_pack_saver'],
            ['aowd',           'upg_badge_next_step'],
            ['bundle-super',   'upg_badge_bundle'],
        ],
        // Speedboats & Private Yachts
        'speed-1h' => [
            ['speed-2h',       'upg_badge_vip_boat'],
            ['speed-3h',       'upg_badge_vip_boat'],
            ['dsd-quick-boat', 'upg_badge_try_dive'],
            ['private-1-5',    'upg_badge_vip_boat'],
        ],
        'speed-2h' => [
            ['speed-3h',       'upg_badge_vip_boat'],
            ['private-1-5',    'upg_badge_vip_boat'],
            ['dsd-2-boat',     'upg_badge_try_dive'],
            ['snork-ras-adult','upg_badge_popular'],
        ],
        'speed-3h' => [
            ['private-1-5',    'upg_badge_vip_boat'],
            ['private-6-10',   'upg_badge_vip_boat'],
            ['dsd-2-boat',     'upg_badge_try_dive'],
            ['dive-6',         'upg_badge_pack_saver'],
        ],
    ];

    $candidates = $matrix[$id] ?? [];
    if (!$candidates) {
        if (str_starts_with($id, 'private-')) {
            $candidates = [
                ['private-6-10',   'upg_badge_vip_boat'],
                ['private-11-15',  'upg_badge_vip_boat'],
                ['dsd-quick-boat', 'upg_badge_try_dive'],
                ['speed-2h',       'upg_badge_vip_boat'],
                ['dive-6',         'upg_badge_pack_saver'],
            ];
        } elseif (str_starts_with($id, 'sup-')) {
            $candidates = [
                ['dive-6',         'upg_badge_pack_saver'],
                ['dive-10',        'upg_badge_pack_saver'],
                ['sup-thistlegorm','upg_badge_wreck'],
                ['bundle-super',   'upg_badge_bundle'],
                ['wreck',          'upg_badge_next_step'],
            ];
        } elseif ($cat === 'padi-courses') {
            $candidates = [
                ['bundle-super',   'upg_badge_bundle'],
                ['bundle-heros',   'upg_badge_bundle'],
                ['dive-6',         'upg_badge_pack_saver'],
                ['sup-thistlegorm','upg_badge_wreck'],
            ];
        } else {
            $candidates = [
                ['dive-6',         'upg_badge_pack_saver'],
                ['dive-10',        'upg_badge_pack_saver'],
                ['eq-full',        'upg_badge_popular'],
                ['nitrox',         'upg_badge_nitrox'],
            ];
        }
    }

    // Append $seoRelated as fallback with 'upg_badge_popular'
    foreach ($seoRelated as $relId) {
        $candidates[] = [(string) $relId, 'upg_badge_popular'];
    }

    $picked = [];
    foreach ($candidates as [$cid, $bKey]) {
        if ($cid === $id || isset($picked[$cid]) || !isset($all[$cid])) {
            continue;
        }
        $picked[$cid] = [
            'item'      => $all[$cid],
            'badge_key' => $bKey,
        ];
        if (count($picked) >= $limit) {
            break;
        }
    }

    if (count($picked) < $limit) {
        foreach ($all as $cid => $cand) {
            if ($cid === $id || isset($picked[$cid])) {
                continue;
            }
            if ($cand['_cat_key'] === $cat && empty($cand['_compact'])) {
                $picked[$cid] = [
                    'item'      => $cand,
                    'badge_key' => 'upg_badge_popular',
                ];
                if (count($picked) >= $limit) {
                    break;
                }
            }
        }
    }

    return array_values($picked);
}

/**
 * Render a Smart Cross-Sell / Upgrade card with Upgrade Reason Pill, 1-Tap WhatsApp & Quick-Book links.
 */
function upgrade_product_card(array $it, string $badgeKey, array $sourceItem): string
{
    $img     = !empty($it['img']) ? 'images/' . $it['img'] : 'images/hero.jpg';
    $href    = !empty($it['slug']) ? item_url($it) : page_link('/prices');
    $qbHref  = !empty($it['slug']) ? page_link(item_path($it), 'booking') : page_link('/prices', 'booking');
    $title   = item_name($it);
    $desc    = item_desc($it);
    $dur     = item_duration($it);
    $saveTxt = sc_save_badge_text($it);
    $save    = $saveTxt !== '' ? '<span class="pcard__save">' . $saveTxt . '</span>' : '';

    $waTopic = $title . ($it['price'] !== null ? ' (' . money($it['price']) . ')' : '') . ' [Upgrade from ' . item_name($sourceItem) . ']';
    $waHref  = whatsapp_link($waTopic, 'upgrade_card_wa');
    $waSvg   = '<svg class="icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91C21.95 6.45 17.5 2 12.04 2zm5.82 14.01c-.24.68-1.41 1.3-1.95 1.38-.5.08-1.13.11-1.82-.11-.42-.13-.96-.31-1.65-.61-2.9-1.25-4.8-4.17-4.94-4.36-.15-.19-1.18-1.57-1.18-3s.75-2.12 1.01-2.41c.27-.29.58-.36.78-.36.19 0 .39 0 .56.01.18.01.42-.07.66.5.24.58.82 2.01.89 2.15.07.15.12.31.02.5-.1.19-.15.31-.29.48-.15.17-.31.38-.44.51-.15.15-.3.3-.13.59.17.29.76 1.25 1.63 2.02 1.12 1 2.06 1.31 2.35 1.46.29.15.46.12.63-.07.17-.19.73-.85.92-1.14.19-.29.39-.24.65-.15.27.1 1.7.8 1.99.95.29.15.48.22.56.34.07.12.07.7-.17 1.38z"/></svg>';

    $html  = '<article class="pcard pcard--upgrade">';
    $html .= '<a class="pcard__media" href="' . e($href) . '" tabindex="-1" aria-hidden="true">';
    $html .= sc_picture($img, $title . ' — ' . t('location_sharm'), 400, 300, [
        'loading' => 'lazy',
        'sizes'   => '(max-width: 599px) 88vw, 270px',
    ]);
    $html .= '<span class="pcard__badge pcard__badge--upgrade">' . te($badgeKey) . '</span>';
    $html .= $save . '</a>';
    $html .= '<div class="pcard__body">';
    $html .= '<p class="pcard__meta">📍 ' . te('location_sharm') . ($dur !== '' ? ' · ⏱ ' . e($dur) : '') . '</p>';
    $html .= '<h3 class="pcard__title" dir="auto"><a class="pcard__link" href="' . e($href) . '">' . e($title) . '</a></h3>';
    if ($desc !== '') {
        $html .= '<p class="pcard__desc" dir="auto">' . e($desc) . '</p>';
    }
    $html .= '<div class="pcard__foot pcard__foot--upgrade">';
    $html .= '<span class="pcard__price">' . ($it['price'] !== null ? te('from') . ' ' : '') . price_html($it['price'], $it['unit'] ?? '', $it['regular_price'] ?? null) . '</span>';
    $html .= '<div class="pcard__actions pcard__actions--upgrade">';
    $html .= '<a class="btn btn--wa btn--sm btn--wa-card" href="' . e($waHref) . '" target="_blank" rel="noopener"'
        . ' data-track="whatsapp_click" data-placement="upgrade_card_wa"'
        . ' data-item-id="' . e((string) ($it['id'] ?? '')) . '"'
        . ' data-item-name="' . e($title) . '"'
        . ' data-item-eur="' . e($it['price'] === null ? '' : (string) $it['price']) . '">' . $waSvg . '<span>' . te('upgrade_wa_cta') . '</span></a>';
    $html .= '<a class="btn btn--primary btn--sm" href="' . e($qbHref) . '" data-track="cta_click" data-placement="upgrade_quickbook">' . te('upgrade_quickbook_cta') . '</a>';
    $html .= add_button($it);
    $html .= '</div></div></div></article>';

    return $html;
}
