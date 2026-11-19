<?php
/** German (`de`) group-aware template builders — Part 1: Packages, Specials, Daily Fees, Rental & Insurance. */
declare(strict_types=1);

return static function (array $it, string $gid, string $name, string $desc, string $dur, ?int $price): ?array {
    $id = (string) $it['id'];
    $pStr = $price !== null ? $price . ' €' : 'Auf Anfrage';

    /* ---- 1. certified-packages (dive-1..9, dive-extra) ---- */
    if ($gid === 'certified-packages') {
        $dives = $id === 'dive-extra' ? 1 : (int) preg_replace('/\D/', '', $id);
        $dives = max(1, $dives);
        $perDive = $price !== null ? (int) round($price / $dives) : 35;
        $saving = max(0, ($dives * 45) - (int) $price);
        $savNote = $saving > 0 ? " (Sie sparen {$saving} € gegenüber Einzeltauchgängen)" : '';
        $h1 = $id === 'dive-extra'
            ? 'Zusatztauchgang nach dem 10. Tauchgang in Sharm El Sheikh (30 €)'
            : ($id === 'dive-1'
                ? 'Einzelner Geführter Hausrifftauchgang in Sharm El Sheikh (45 €)'
                : "{$name} in Sharm El Sheikh — Rotes Meer ({$pStr})");

        return [
            'h1' => $h1,
            'meta_title' => "{$name} Sharm El Sheikh | {$pStr} ({$perDive} €/TG) | Sea College",
            'meta_desc' => "Buchen Sie {$name} in Sharm El Sheikh für {$pStr} ({$perDive} €/Tauchgang). Inklusive PADI Tauchguide, 12L-Flaschen, Blei & kostenlosem Hoteltransfer.",
            'aeo_answer' => "{$name} im Sea College Dive Club (PADI 5 Sterne Tauchbasis in Al Montazah, Sharm El Sheikh) kostet {$pStr} pro zertifiziertem Taucher (effektiv {$perDive} € pro Tauchgang{$savNote}) bei einem Zeitrahmen von {$dur}. Enthalten sind ein erfahrener PADI Tauchguide, 12L-Aluminiumflaschen (DIN/INT), Bleigurt & Blei sowie der kostenlose Hin- und Rücktransfer ab Ihrem Hotel in Sharm El Sheikh (an Bootstagen zuzüglich 10 €/Tag Bootszuschlag inkl. Mittagsbuffet & 5 €/Tag Nationalparkgebühr).",
            'overview' => [
                "Mit „{$name}“ entdecken Sie die weltberühmte Unterwasserwelt von Sharm El Sheikh flexibel vom komfortablen Tagesboot oder direkt an unserem geschützten Hausriff in Al Montazah gegenüber der Insel Tiran. Unsere PADI Guides teilen die Tauchgruppen sorgfältig nach Erfahrungsstand und Luftverbrauch ein.",
                "Je nach Tagesplanung führen unsere Routen zu den farbenprächtigen Steilwänden des Nationalparks Ras Mohammed (Shark & Yolanda Reef), zu den vier Riffen der Straße von Tiran (Jackson, Woodhouse, Thomas & Gordon Reef) sowie zu traumhaften lokalen Korallengärten — optional kombinierbar mit Wrack-, Nacht- oder Zodiac-Zuschlägen.",
            ],
            'specs' => [
                ['Paketpreis', "{$pStr} pro Person ({$perDive} € pro Tauchgang)"],
                ['Zeitrahmen', "{$dur} (frei über Ihren Aufenthalt verteilbar)"],
                ['Mindestbrevet', 'Zertifizierter Taucher (Open Water / CMAS* oder höher)'],
                ['Inklusiv-Technik', '12L-Pressluftflaschen (DIN/INT), Bleigurt & Blei'],
                ['Hoteltransfer', 'Kostenlose Abholung & Rückfahrt in ganz Sharm El Sheikh'],
                ['Bootstag-Gebühren', '10 €/Tag Bootszuschlag (inkl. Mittagsbuffet) + 5 €/Tag Parkgebühr (0 € am Hausriff)'],
            ],
            'steps' => [
                ['Hotelabholung & Basis-Check-in', 'Klimatisierter Transfer zu unserer PADI 5 Sterne Basis im Cyrene Sharm, Brevet-/Logbuchprüfung und Ausrüstungscheck.'],
                ['Auslaufen mit dem Tauchboot oder Hausriff-Start', 'Morgendliche Ausfahrt (~08:30 Uhr) zu zwei abwechslungsreichen Riffen oder entspannter Direkteinstieg an unserem Hausriff.'],
                ['Oberflächenpause & Frisches Mittagsbuffet', 'Erholung auf dem schattigen Sonnendeck mit frisch gekochtem Mittagsbuffet, Tee, Kaffee und Softdrinks an Ganztages-Bootstagen.'],
                ['Zweiter Tauchgang, Logbuch & Rückfahrt', 'Genussvoller Nachmittagstauchgang, gemeinsames Abstempeln des Logbuchs und Rücktransfer ins Hotel gegen 16:30 Uhr.'],
            ],
            'included' => [
                ($id === 'dive-extra' ? '1 zusätzlicher geführter Tauchgang nach Abschluss Ihres 10er-Pakets' : "{$dives} geführte Freiwassertauchgänge (Boot oder Hausriff)"),
                'Zertifizierter PADI Tauchguide in kleinen Gruppen',
                '12L-Aluminium-Pressluftflaschen (DIN/INT), Bleigurt & Blei',
                'Kostenloser klimatisierter Hoteltransfer in Sharm El Sheikh',
                'Getränke auf Halbtagestouren bzw. Mittagsbuffet & Getränke auf Ganztagesbooten (mit Bootszuschlag)',
            ],
            'excluded' => [
                'Täglicher Bootszuschlag: 10 € pro Person/Tag an Bootstauchtagen',
                'Staatliche Nationalparkgebühr: 5 € pro Person/Tag (entfällt am Hausriff)',
                'Leihausrüstung (25 €/Tag Komplettset oder Einzelteile), falls keine eigene vorhanden',
                'Enriched Air Nitrox (8 €/12L), Spezial-/Wrackzuschläge & Tauchversicherung',
            ],
            'ideal_for' => [
                'Brevetierte Sporttaucher (PADI Open Water, Advanced, SSI, CMAS*/**/***) im Urlaub in Sharm El Sheikh',
                'Wiedereinsteiger nach > 6 Monaten Pause planen vorab einfach einen kurzen Check Dive (25 €) ein (> 12 Monate: Scuba Review 50 €)',
                'Junior Diver (10–11 J.) sowie PADI Scuba Diver (mit obligatorischem Privatguide 75 €/Tag)',
            ],
            'faqs' => [
                ['q' => 'Ist dieses Tauchpaket zwischen zwei Personen aufteilbar?', 'a' => 'Nein, alle Tauchpakete im Sea College Dive Club sind personengebunden und gelten jeweils für einen einzelnen zertifizierten Taucher.'],
                ['q' => 'Welche Zusatzkosten entstehen an einem Bootstauchtag?', 'a' => 'Pro Bootstag kommen 10 € Bootszuschlag (inkl. warmem Mittagsbuffet & Getränken auf Ganztagesbooten) sowie 5 € Nationalparkgebühr pro Person hinzu. Am Hausriff entfallen beide Gebühren (0 €).'],
                ['q' => 'Kann ich mein Paket mit Wracks oder einem Nachttauchgang kombinieren?', 'a' => 'Ja, jederzeit! Sie nutzen einfach einen Tauchgang aus Ihrem Paket und buchen den gewünschten Zuschlag hinzu (z. B. Nachttauchgang +30 €, Dunraven +30 €, Zodiac +50 € oder SS Thistlegorm +150 €).'],
            ],
        ];
    }

    /* ---- 2. wreck-special-dives (sup-night, sup-zodiac, sup-zodiac-early) ---- */
    if ($gid === 'wreck-special-dives') {
        $isEarly = $id === 'sup-zodiac-early';
        $isZodiac = str_contains($id, 'zodiac');
        return [
            'h1' => "{$name} in Sharm El Sheikh (+{$pStr})",
            'meta_title' => "{$name} Sharm El Sheikh | +{$pStr} | Sea College",
            'meta_desc' => "{$name} im Roten Meer ab Sharm El Sheikh (+{$pStr} auf Ihr Tauchpaket, Dauer {$dur}). {$desc}",
            'aeo_answer' => "„{$name}“ im Sea College Dive Club in Sharm El Sheikh wird als Spezialzuschlag von {$pStr} pro zertifiziertem Taucher ergänzend zu einem Tauchgang Ihres Tauchpakets gebucht (Dauer: {$dur}). " . ($isZodiac ? 'Direkt von unserer Lagune in Al Montazah bringt Sie unser schnelles RIB-Schlauchboot ab mindestens 4 Tauchern (Buchung mind. 48 Std. vorab) vor den regulären Tagesbooten an die Top-Riffe von Tiran oder zur Million Hope.' : 'Nach Sonnenuntergang oder im ersten Morgenlicht erleben Sie mit Ihrem PADI Guide die faszinierende nachtaktive Unterwasserwelt unseres Korallenriffs.'),
            'overview' => [
                $isEarly
                    ? 'Wenn die Sonne über der Insel Tiran aufgeht und noch kein einziges großes Tagesboot am Riff ankert, startet unser schnelles Zodiac ab Al Montazah: In den Sommermonaten bietet das tiefe Blau vor dem Jackson Reef legendäre Chancen auf Bogenstirn-Hammerhaie, Adlerrochen und große Makrelenschwärme!'
                    : ($isZodiac
                        ? 'Schnell, wendig und direkt am Punkt: Mit unserem leistungsstarken Zodiac-RIB setzen wir Sie punktgenau an den besten Strömungskanten der Straße von Tiran und lokalen Steilwänden ab und nehmen Sie nach dem Drift bequem wieder auf.'
                        : 'Erleben Sie den magischen Schichtwechsel am Korallenriff: Bei Nacht jagen Rotfeuerfische, Muränen und Oktopusse im Lichtkegel Ihrer Lampe, während Frühmorgentauchgänge im ersten Dämmerlicht Begegnungen mit aktiven Raubfischen ermöglichen.'),
                'Der Zuschlag ergänzt einfach einen regulären Tauchgang Ihres zertifizierten Tauchpakets — inklusive ausführlichem Sicherheitsbriefing, 12L-Flasche, Blei und Begleitung durch unseren erfahrenen PADI Guide.',
            ],
            'specs' => [
                ['Spezialzuschlag', "+{$pStr} pro Person (zusätzlich zu 1 Paket-Tauchgang)"],
                ['Dauer', $dur],
                ['Voraussetzung', $isEarly ? 'Erfahrener Open Water / Advanced Diver · Mind. 4 Taucher' : ($isZodiac ? 'Zertifizierter Taucher · Mind. 4 Taucher' : 'Zertifizierter Taucher (OWD / CMAS* oder höher)')],
                ['Vorausbuchung', $isZodiac ? 'Mindestens 48 Stunden im Voraus' : 'Bis am Vortag vor Ort oder online vorab'],
                ['Startpunkt', 'PADI 5 Sterne Strandbasis Al Montazah, Sharm El Sheikh'],
            ],
            'steps' => [
                ['Spezial-Briefing an der Basis', 'Besprechung von Strömung, Einstiegstechnik vom RIB bzw. Lampensignalen bei Dunkelheit.'],
                ['Ausrüstungs- & Sicherheitscheck', 'Sorgfältige Kontrolle von Flaschendruck, Boje/Lampe und Buddy-Einteilung.'],
                ['Geführter Spezial-Tauchgang', 'Intensives Unterwassererlebnis abseits der gewohnten Tageszeiten und Standardrouten.'],
                ['Rückkehr & Logbuch-Eintrag', 'Heißer Tee an der Basis, Austausch über die Sichtungen und Abstempeln Ihres Logbuchs.'],
            ],
            'included' => [
                "Spezialorganisation & Durchführung: {$name}",
                'Erfahrener PADI Tauchguide',
                '12L-Flasche, Blei & Hoteltransfer im Rahmen Ihres Tauchpakets',
                $isZodiac ? 'Schneller Zodiac-RIB-Transfer direkt ab Al Montazah' : 'Beleuchteter Ein-/Ausstieg & Sicherheitskoordination',
            ],
            'excluded' => [
                'Basis-Tauchgang aus Ihrem zertifizierten Tauchpaket',
                'Unterwasser-Tauchlampe bei Nachttauchgängen (6 €/Tag im Verleih, falls keine eigene vorhanden)',
                'Eventuelle Nationalparkgebühr (5 €/Tag außerhalb des Hausriffs) & Leihausrüstung (25 €/Tag)',
            ],
            'ideal_for' => [
                'Zertifizierte Taucher, die ihr 6er- oder 10er-Tauchpaket um unvergessliche Highlights bereichern möchten',
                $isZodiac ? 'Freunde zügiger Ausfahrten ohne ganztägige Bootszeit (ab 4 Personen)' : 'Unterwasserfotografen und Fans nachtaktiver Korallenriff-Bewohner',
            ],
            'faqs' => [
                ['q' => 'Ist dieser Preis ein Einzelpreis oder ein Zuschlag aufs Tauchpaket?', 'a' => "Es handelt sich um einen Zuschlag von +{$pStr} auf einen regulären Tauchgang Ihres gebuchten Tauchpakets."],
                ['q' => 'Was passiert, wenn die Mindestteilnehmerzahl von 4 Tauchern beim Zodiac noch nicht erreicht ist?', 'a' => 'Wir bündeln interessierte Taucher vor Ort gerne zu einer 4er-Gruppe oder bieten Ihnen alternativ ein privates Schnellboot an.'],
                ['q' => 'Brauche ich für einen Nachttauchgang das Night-Diver-Spezialbrevet?', 'a' => 'Nein, zertifizierte Open-Water-Taucher können bereits an geführten Nachttauchgängen an unserem ruhigen Hausriff teilnehmen (Lampe 6 €/Tag).'],
            ],
        ];
    }

    /* ---- 3. daily-fees (fee-boat, fee-park, private-guide) ---- */
    if ($gid === 'daily-fees') {
        $isGuide = $id === 'private-guide';
        $isBoat = $id === 'fee-boat';
        return [
            'h1' => "{$name} in Sharm El Sheikh ({$pStr} / Tag)",
            'meta_title' => "{$name} Sharm El Sheikh | {$pStr} pro Tag | Sea College",
            'meta_desc' => "Transparente Info & Buchung: {$name} ({$pStr}/Tag) beim Tauchen in Sharm El Sheikh. {$desc}",
            'aeo_answer' => "„{$name}“ beträgt im Sea College Dive Club in Sharm El Sheikh exakt {$pStr} pro Tag. " . ($isGuide ? 'Ein eigener privater PADI Guide begleitet Sie oder Ihre Kleingruppe exklusiv den gesamten Tauchtag (100 % individuell; gesetzlich/seitens der Basis verpflichtend für 10–11-jährige Junior-Taucher und PADI Scuba Diver).' : ($isBoat ? 'Dieser verpflichtende Tageszuschlag fällt nur an Tagen an, an denen Sie vom Tauchboot aus tauchen, und beinhaltet auf Ganztagesbooten bereits das frisch gekochte Mittagsbuffet sowie Softdrinks, Wasser, Kaffee und Tee!' : 'Diese staatliche Umweltschutzgebühr gilt pro Person und Tag für alle Meeresparkzonen rund um Ras Mohammed und Tiran — bei reinen Hausrifftauchgängen in Al Montazah entfällt sie komplett (0 €).')),
            'overview' => [
                'Maximale Preistransparenz ist eines der am häufigsten gelobten Merkmale unserer Tauchbasis: Statt versteckter Pauschalen weisen wir jede Tagesgebühr offen in Euro aus, damit Sie vorab auf den Euro genau kalkulieren können.',
                $isGuide
                    ? 'Mit einem privaten PADI Guide bestimmen Sie Fotopausen, Schwimmtempo und Aufmerksamkeit ganz allein — ideal für Unterwasserfotografen, Paare, Familien mit jungen Tauchern (10–11 J.) oder frisch brevetierte Gäste.'
                    : ($isBoat
                        ? 'Für lediglich 10 € pro Bootstag verbringen Sie den Tag auf unserer großzügigen Rotes-Meer-Yacht inklusive warmem Mittagsbuffet, Getränken und Crew-Service auf der Tauchplattform.'
                        : 'Die 5 € Nationalparkgebühr fließt in den Erhalt der Bojenfelder und geschützten Riffe von Süd-Sinai, Ras Mohammed und der Straße von Tiran.'),
            ],
            'specs' => [
                ['Tagessatz', "{$pStr} pro Tag" . ($isGuide ? ' (für Sie / Ihre Privatgruppe)' : ' pro Person')],
                ['Abrechnung', $isGuide ? 'Optional zubuchbar (Pflicht für 10–11 J. & Scuba Diver)' : 'Nur an tatsächlichen Bootstauchtagen / Parktagen fällig'],
                ['Hausriff Al Montazah', $isGuide ? 'Auch für exklusive Hausrifftauchgänge buchbar' : '0 € — am Hausriff fallen weder Boots- noch Parkgebühren an!'],
                ['Transparenz', 'Direkt im Online-Tauchkostenrechner vorab kalkulierbar'],
                ['Zahlung', 'Bequem vor Ort oder gesammelt mit Ihrer Paketbuchung'],
            ],
            'steps' => [
                ['Im Tauchbudget-Rechner auswählen', 'Geben Sie einfach Ihre geplanten Bootstage ein — das System berechnet alle Gebühren automatisch.'],
                ['Bestätigung vor Anreise', 'Unser Team bestätigt Ihre exakte Aufstellung transparent per WhatsApp oder E-Mail.'],
                ['Sorgenfreie Abwicklung vor Ort', 'Wir übernehmen sämtliche Hafen-Manifeste, Parktickets und Bordverpflegung für Sie.'],
            ],
            'included' => $isGuide
                ? ['Exklusiver zertifizierter PADI Tauchguide für den gesamten Tag', 'Individuelles Tempo für Unterwasserfotografie & Familien', 'Erfüllung der Begleitpflicht für 10–11-Jährige & PADI Scuba Diver', 'Persönliches Vor- & Nachbriefing']
                : ($isBoat
                    ? ['Ganztägiger Platz auf unserem ausgestatteten Tauchboot', 'Frisch zubereitetes Mittagsbuffet auf Ganztagesausfahrten', 'Wasser, Tee, Kaffee & Softdrinks an Bord', 'Hilfe durch unsere erfahrene Deckscrew beim Ein- & Ausstieg']
                    : ['Offizielles Tages-Eintrittsticket für die Meeresparkzonen von Sharm El Sheikh', 'Nutzung der geschützten Ankerbojen in Ras Mohammed & Tiran', 'Komplette Behördenabwicklung durch unsere Basis', 'Hinweis: Bei Boots-Schnuppertauchen & Schnorcheltouren bereits inklusive!']),
            'excluded' => [
                'Zertifiziertes Basis-Tauchpaket (ab 35 €/Tauchgang)',
                'Optionale Leihausrüstung (25 €/Tag Komplettset)',
                'Trinkgelder für die Bootscrew (freiwillig)',
            ],
            'ideal_for' => [
                $isGuide ? 'Unterwasserfotografen, Familien mit 10–11-jährigen Kindern, PADI Scuba Diver & Gäste mit Wunsch nach 1-zu-1-Betreuung' : 'Zertifizierte Taucher, die ein mehrtägiges Tauchpaket auf unseren Tagesbooten buchen',
                'Gäste, die ihre Gesamtkosten schon zu Hause ohne Überraschungen im Warenkorb sehen möchten',
                'Reine Hausrifftaucher sparen sich sowohl Boots- als auch Nationalparkgebühr komplett',
            ],
            'faqs' => [
                ['q' => 'Muss ich Boots- und Parkgebühr auch bei Schnorcheltouren oder Boots-Schnuppertauchen extra zahlen?', 'a' => 'Nein! Beim Schnuppertauchen vom Boot (70 € / 100 €) und beim Ras-Mohammed-Schnorchelausflug (55 € / 30 €) sind Boot, Essen und Parkgebühr bereits komplett im Ticketpreis enthalten.'],
                ['q' => 'Fällt am Hausriff in Al Montazah eine Parkgebühr an?', 'a' => 'Nein, Tauchgänge an unserem eigenen Hausriff kosten 0 € Bootszuschlag und 0 € Nationalparkgebühr.'],
                ['q' => 'Wann ist ein Privater Guide (75 €/Tag) Pflicht?', 'a' => 'Für Kinder im Alter von 10–11 Jahren sowie für Inhaber des PADI Scuba Diver Brevets (12-m-Limit).'],
            ],
        ];
    }

    /* ---- 4. equipment-rental (14 items) ---- */
    if ($gid === 'equipment-rental') {
        $isTank = str_contains($id, '15') || str_contains($id, '12') || str_contains($id, 'sm-tank');
        return [
            'h1' => "{$name} in Sharm El Sheikh ({$pStr} / Tag)",
            'meta_title' => "{$name} mieten Sharm El Sheikh | {$pStr}/Tag | Sea College",
            'meta_desc' => "Gepflegte Tauchausrüstung & Nitrox in Sharm El Sheikh mieten: {$name} für {$pStr} pro Tag im PADI 5 Sterne Resort Sea College Dive Club.",
            'aeo_answer' => "„{$name}“ kostet im Sea College Dive Club in Sharm El Sheikh {$pStr} pro Tag. Unsere CDWS- und PADI-5-Sterne-zertifizierte Ausrüstungswerkstatt im Cyrene Sharm wartet und desinfiziert sämtliche Leihgeräte regelmäßig und hält alle gängigen Größen von Kindergrößen und XS bis XXL für Ihre Hausriff- und Bootstauchgänge bereit (Hinweis: Bei allen PADI Kursen und Schnuppertauchgängen ist die komplette Leihausrüstung bereits kostenlos inklusive!).",
            'overview' => [
                "Reisen Sie mit leichtem Fluggepäck ans Rote Meer! Anstatt teures Sportgepäck bei der Airline anzumelden, leihen Sie „{$name}“ einfach tageweise frisch geprüft direkt an unserer Strandbasis in Al Montazah.",
                $isTank
                    ? 'Unsere eigene Füllstation liefert lupenreine Atemluft nach EU-/CDWS-Norm sowie präzise gemischtes Enriched Air Nitrox (EAN32) in 12-Liter- und großen 15-Liter-Aluminiumflaschen mit DIN- und INT-Anschlüssen.'
                    : 'Unser Equipment-Team (Saleh, Mazen, Emad & Mostafa) richtet Ihre Größen vorab anhand Ihres Express-Check-ins her, bringt Ihre Ausrüstungskiste morgens für Sie aufs Tauchboot und spült alles nach dem Tauchtag in Süßwasser.',
            ],
            'specs' => [
                ['Mietpreis / Tagessatz', "{$pStr} pro Tag (für 2–3 Tagestauchgänge)"],
                ['Verfügbare Größen', $isTank ? 'DIN-Gewinde & INT-Bügelanschluss kompatibel' : 'Alle Größen vorrätig (Kinder, XS, S, M, L, XL, XXL)'],
                ['Wartungsstandard', 'Regelmäßige Revision in eigener Basis-Werkstatt (CDWS-geprüft)'],
                ['Bei PADI Kursen?', '0 € — in allen PADI Kursen & Schnuppertauchgängen bereits inklusive!'],
                ['Komplettset-Tipp', 'Komplette Leihausrüstung (BCD, Regler, Anzug, Maske & Flossen) kostet nur 25 €/Tag!'],
            ],
            'steps' => [
                ['Größen online beim Check-in angeben', 'Auf Wunsch übermitteln Sie uns Anzug-, Jacket- und Schuhgröße schon vorab nach Ihrer Anfrage.'],
                ['Anprobe & Funktionscheck an der Basis', 'Gemeinsames Prüfen von Passform, Mundstück, Druckanzeige bzw. Sauerstoffgehalt (bei Nitrox).'],
                ['Komfort-Service an Bootstagen', 'Ihre markierte Box reist mit aufs Boot und wird abends im belüfteten Trockenraum gepflegt.'],
            ],
            'included' => [
                "Tagesmiete bzw. Füllung: {$name} (für den gesamten Tauchtag)",
                'Fachgerechte Desinfektion & Sicherheitsprüfung vor Ausgabe',
                'Kostenloser Umtausch bei Größenänderung während des Urlaubs',
                'Transport- & Spülservice an unserer PADI 5 Sterne Basis',
            ],
            'excluded' => [
                $id === 'eq-full' ? 'Tauchcomputer (10 €/Tag) & Unterwasserlampe (6 €/Tag) sind separat zubuchbar' : 'Übrige Ausrüstungsteile (Tipp: ab 3 Einzelteilen lohnt sich meist das Komplettset für 25 €/Tag!)',
                'Haftung bei Verlust oder mutwilliger Beschädigung unter Wasser',
                'Nitrox-Brevet-Voraussetzung bei Buchung von 12L/15L-Nitrox-Flaschen',
            ],
            'ideal_for' => [
                'Zertifizierte Urlaubstaucher mit Handgepäck oder nur teilweiser eigener Ausrüstung (z. B. eigener Computer + Maske)',
                $isTank ? 'Taucher mit höherem Luftverbrauch (15L) oder Wunsch nach längeren Nullzeiten (Nitrox EAN32)' : 'Gäste, die hochwertige Markenausrüstung ohne Schleppen im Flugzeug schätzen',
                'Sidemount-Taucher, die schwere Flaschen und Rigs nicht im Flugzeug transportieren möchten',
            ],
            'faqs' => [
                ['q' => 'Gilt der Tagespreis pro Tauchgang oder für den ganzen Tag?', 'a' => "Der Preis von {$pStr} gilt pauschal für den gesamten Tauchtag (also für alle 2–3 Tauchgänge des Tages; bei Flaschen entsprechend für den Bootstag)!"],
                ['q' => 'Haben Ihre Atemregler und Flaschen DIN-Anschlüsse für deutsche Regler?', 'a' => 'Ja! Alle Flaschen verfügen über herausnehmbare Einsätze und passen sowohl für 200-bar-DIN-Gewinde als auch für INT-Bügel.'],
                ['q' => 'Muss ich für meinen PADI Open Water oder Advanced Kurs Ausrüstung dazu mieten?', 'a' => 'Nein — bei sämtlichen PADI Kursen, Sparpaketen und Schnuppertauchgängen ist die komplette Leihausrüstung bereits im Kurspreis enthalten!'],
            ],
        ];
    }

    /* ---- 5. diving-insurance (ins-3, ins-7, ins-10, ins-14) ---- */
    if ($gid === 'diving-insurance') {
        return [
            'h1' => "{$name} in Sharm El Sheikh (Druckkammer-Schutz)",
            'meta_title' => "{$name} Sharm El Sheikh | Druckkammer-Schutz | Sea College",
            'meta_desc' => "Sorgenfrei tauchen im Roten Meer: {$name} in Sharm El Sheikh — deckt Druckkammer-Behandlung & tauchmedizinische Notfallversorgung vor Ort ab.",
            'aeo_answer' => "Die „{$name}“ im Sea College Dive Club in Sharm El Sheikh bietet zertifizierten Tauchern für {$dur} vollen tauchmedizinischen Unfall- und Druckkammerschutz am Roten Meer. Da reguläre Auslandsreisekrankenversicherungen Dekompressionsbehandlungen oft ausschließen und in Ägypten ein Nachweis für zertifizierte Taucher vorgeschrieben ist, aktivieren wir diese Kurzzeitpolice unkompliziert direkt beim Check-in zum tagesaktuellen Kammer-Tarif (Hinweis: In unseren 4 PADI Sparpaketen sowie im Open Water / Scuba Diver Kurs ist die Tauchversicherung bereits inklusive!).",
            'overview' => [
                'Sharm El Sheikh verfügt mit seinem modernen hyperbaren Druckkammerzentrum über eine erstklassige tauchmedizinische Infrastruktur. Eine dedizierte Tauchunfallversicherung stellt sicher, dass im unwahrscheinlichen Ernstfall sämtliche Rettungs-, Sauerstoff- und Druckkammerkosten ohne teure Vorkasse übernommen werden.',
                "Falls Sie keine eigene Jahrespolice (wie aqua med, DAN Europe oder VDST) besitzen, schließen Sie diese Kurzzeitdeckung über {$dur} in zwei Minuten direkt bei Ihrer Ankunft an unserer Basis ab.",
            ],
            'specs' => [
                ['Laufzeit', "{$dur} durchgehender Tauchschutz"],
                ['Tarif', 'Tagesaktueller Vor-Ort-Tarif — wir nennen Ihnen den exakten Euro-Betrag bei Buchung'],
                ['Abdeckung', 'Tauchmedizinische Notfallbehandlung & hyperbare Druckkammer in Sharm El Sheikh'],
                ['Eigene DAN / aqua med?', 'Wird selbstverständlich anerkannt — bringen Sie einfach Ihre Policennummer mit!'],
                ['Bei Sparpaketen?', 'Bereits kostenlos in Stars, Super, Mega, Heros, OWD & Scuba Diver enthalten!'],
            ],
            'steps' => [
                ['Zur Buchungsanfrage hinzufügen', 'Wählen Sie die passende Laufzeit (3, 7, 10 oder 14 Tage) passend zu Ihren geplanten Tauchtagen.'],
                ['Sofort-Registrierung beim Check-in', 'Wir tragen Name, Geburtsdatum und Passnummer vor Ihrem ersten Tauchgang ein.'],
                ['100 % geschützt abtauchen', 'Ihre Deckung gilt sofort für alle gebuchten Boots- und Hausrifftauchgänge innerhalb der Laufzeit.'],
            ],
            'included' => [
                "Kurzzeit-Tauchunfall- & Druckkammerdeckung für {$dur}",
                'Sofortige Aktivierung ohne Wartezeit beim Basis-Check-in',
                'Anerkannt für alle Nationalpark- und Bootstauchgänge im Süd-Sinai',
                'Direkte Abwicklung mit dem Druckkammerzentrum Sharm El Sheikh',
            ],
            'excluded' => [
                'Allgemeine Reiserücktritts- oder Gepäckversicherung',
                'Tauchgänge außerhalb der Sporttauch-Tiefengrenzen Ihres Brevets',
                'Nicht erforderlich, falls Sie bereits eine gültige DAN-, aqua-med- oder VDST-Versicherung vorlegen',
            ],
            'ideal_for' => [
                'Urlaubstaucher ohne eigene Jahres-Tauchversicherung (wie DAN oder aqua med)',
                'Gäste, die ein zertifiziertes Tauchpaket oder einzelne Fortgeschrittenenkurse buchen',
                'Sicherheitsbewusste Sporttaucher, die im Urlaub volle Kostendeckung wünschen',
            ],
            'faqs' => [
                ['q' => 'Reicht meine normale ADAC- oder Kreditkarten-Auslandskrankenversicherung zum Tauchen?', 'a' => 'Prüfen Sie Ihre Bedingungen genau: Viele Standardpolicen schließen Tauchunfälle und teure Druckkammerfahrten explizit aus. Mit einer dedizierten Kurzzeit-Tauchversicherung (oder aqua med / DAN) sind Sie auf der sicheren Seite.'],
                ['q' => 'Ich habe bereits aqua med, DAN oder VDST — muss ich diese Versicherung buchen?', 'a' => 'Nein! Bringen Sie einfach Ihre digitale Mitgliedskarte oder Policennummer zum Check-in mit.'],
                ['q' => 'Warum steht der Preis auf „Auf Anfrage“?', 'a' => 'Weil die offizielle ägyptische Kammer-/Versicherungsprämie direkt an den jeweiligen Anbieter weitergereicht wird — wir teilen Ihnen den genauen Betrag (wenige Euro pro Tag) sofort bei Ihrer Anfrage mit!'],
            ],
        ];
    }

    return null;
};
