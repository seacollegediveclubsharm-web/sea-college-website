<?php
/** German (`de`) group-aware template builders — Part 2: Specialty Courses, Snorkeling, Speedboats & Private Charters. */
declare(strict_types=1);

return static function (array $it, string $gid, string $name, string $desc, string $dur, ?int $price): array {
    $id = (string) $it['id'];
    $pStr = $price !== null ? $price . ' €' : 'Auf Anfrage';

    /* ---- 6. Courses & Specialties (spec-2, spec-3, adaptive & any course fallback) ---- */
    if (in_array($gid, ['specialty-courses', 'beginner-courses', 'advanced-rescue', 'kids-courses', 'go-pro', 'saver-bundles'], true)) {
        $specShort = $id === 'spec-2'
            ? 'PADI 2-TG-Spezialkurs'
            : ($id === 'spec-3'
                ? 'PADI UW-Navigator (3 TG)'
                : ($id === 'adaptive' ? 'PADI Adaptive Support Diver' : $name));
        $specMt = mb_strlen("{$specShort} Sharm El Sheikh | {$pStr}", 'UTF-8') <= 60
            ? "{$specShort} Sharm El Sheikh | {$pStr}"
            : "{$specShort} Sharm | {$pStr}";
        return [
            'h1' => "{$name} in Sharm El Sheikh ({$pStr})",
            'meta_title' => $specMt,
            'meta_desc' => "Absolvieren Sie den {$name} in unserer PADI 5 Sterne Tauchbasis in Sharm El Sheikh ({$pStr}, Dauer: {$dur}) — inklusive kompletter Leihausrüstung!",
            'aeo_answer' => "Der „{$name}“ im Sea College Dive Club in Sharm El Sheikh kostet {$pStr} pro Person und dauert {$dur}. Unter Anleitung unserer erfahrenen, deutsch- und mehrsprachigen PADI Specialty Instructors verfeinern Sie gezielt Ihre Unterwasserfertigkeiten im warmen, glasklaren Roten Meer — inklusive kompletter Leihausrüstung und Sharm-Hoteltransfer (+85 € offizielle PADI Lehrmaterial-/Brevetgebühr).",
            'overview' => [
                "Gezieltes Praxistraining unter Traumbedingungen: Beim „{$name}“ konzentrieren Sie sich ohne Prüfungsstress ganz auf präzise Wasserlage, Sicherheit und neues Fachwissen — sei es an unserem geschützten Hausriff in Al Montazah oder auf unseren täglichen Tauchbooten.",
                'Jeder abgeschlossene PADI Spezialkurs bringt Ihnen nicht nur spürbar mehr Souveränität und geringeren Luftverbrauch unter Wasser, sondern zählt zugleich offiziell für die höchste Sporttauch-Auszeichnung: den PADI Master Scuba Diver!',
            ],
            'specs' => [
                ['Kurspreis', "{$pStr} pro Person (komplette Leihausrüstung inklusive)"],
                ['PADI-Gebühr', '+85 € offizielles PADI Lehrbuch / eLearning & Brevetkarte'],
                ['Kursdauer', $dur],
                ['Ausbildungsstandard', 'Offizielles PADI 5 Sterne Dive Resort · Cyrene Sharm, Al Montazah'],
                ['Master Scuba Diver', 'Zählt offiziell als 1 von 5 benötigten PADI Specialties'],
            ],
            'steps' => [
                ['Praxisorientiertes Briefing & Zielsetzung', 'Besprechung der Spezialtechniken, Ausrüstungskonfiguration und Tauchgangsplanung mit Ihrem PADI Instructor.'],
                ['Geführte Ausbildungs-Übungen im Wasser', 'Schrittweise Umsetzung aller Kursfertigkeiten bei 22–29 °C Wassertemperatur und bester Sicht.'],
                ['Freiwasser-Anwendung am Korallenriff', 'Vertiefung des Gelernten auf realen Rifftauchgängen mit individuellem Feedback Ihres Lehrers.'],
                ['Offizielle PADI Specialty Zertifizierung!', 'Logbuchstempel und Ausstellung Ihrer lebenslang weltweit gültigen PADI Spezialkurs-Lizenz.'],
            ],
            'included' => [
                "Komplette theoretische & praktische PADI Ausbildung ({$dur})",
                'Komplette Tauchausrüstung während des gesamten Spezialkurses',
                'Betreuung durch zertifizierten PADI Specialty Instructor',
                'Kostenloser Hin- & Rücktransfer ab Ihrem Hotel in Sharm El Sheikh',
            ],
            'excluded' => [
                'Offizielles PADI Lehrmaterial / eLearning & Zertifizierungsgebühr (85 €)',
                'Tägliche Boots- (10 €/Tag inkl. Mittagsbuffet) & Nationalparkgebühr (5 €/Tag) an Bootstagen',
                'Kurzzeit-Tauchversicherung (falls keine eigene Jahrespolice vorhanden)',
            ],
            'ideal_for' => [
                'Brevetierte Sporttaucher, die gezielt Tarierung, Kompassnavigation, Strömungstauchen oder Partner-Unterstützung meistern wollen',
                'Taucher auf dem Weg zum PADI Master Scuba Diver (5 Specialties + Rescue + 50 Tauchgänge)',
                'Urlauber mit 1 bis 2 Tagen Zeit, die neben normalen Fun-Dives ein offizielles PADI Brevet mitnehmen möchten',
            ],
            'faqs' => [
                ['q' => 'Ist die komplette Leihausrüstung auch bei PADI Spezialkursen im Preis enthalten?', 'a' => "Ja! Im Kurspreis von {$pStr} ist die komplette reguläre Tauchausrüstung für alle Kurstauchgänge inbegriffen."],
                ['q' => 'Welche 2-Tauchgänge-Spezialkurse stehen bei Ihnen zur Auswahl?', 'a' => 'Beim 2-Dive-Specialty (145 €) wählen Sie frei zwischen Tarierung in Perfektion (Peak Performance Buoyancy), Strömungstauchen (Drift), Bootstauchen, UW-Naturalist oder Digitaler UW-Fotografie!'],
                ['q' => 'Wie lange vor dem Rückflug muss der Kurs abgeschlossen sein?', 'a' => 'Beachten Sie nach mehreren Tauchgängen bitte mindestens 18 bis 24 Stunden Pause bis zum Abflug.'],
            ],
        ];
    }

    /* ---- 7. snorkeling-trips (snork-ras-child, snork-beach-day, snork-beach-night, snork-ras-land) ---- */
    if ($gid === 'snorkeling-trips') {
        $isChild = $id === 'snork-ras-child';
        $isNight = $id === 'snork-beach-night';
        $isLand  = $id === 'snork-ras-land';
        $snkMt = $isChild
            ? 'Ras Mohammed & White Island Kind (5–12 J.) | 30 € Sharm'
            : ($isLand
                ? 'Ras Mohammed Schnorchelausflug Bus Sharm | 60 € All-In'
                : ($isNight
                    ? 'Nachtschnorcheln Hausriff Sharm El Sheikh | 40 € Komplett'
                    : 'Tagesschnorcheln Hausriff Sharm El Sheikh | 35 € Komplett'));
        return [
            'h1' => "{$name} in Sharm El Sheikh ({$pStr})",
            'meta_title' => $snkMt,
            'meta_desc' => "Geführter Schnorchelausflug in Sharm El Sheikh: {$name} für {$pStr} ({$dur}) — inklusive Maske, Schwimmweste, Nationalparkgebühr & Hoteltransfer!",
            'aeo_answer' => "„{$name}“ mit dem Sea College Dive Club in Sharm El Sheikh kostet {$pStr} (" . ($isChild ? 'ermäßigter Kindertarif für 5–12 Jahre' : 'pro Person') . ") und dauert {$dur}. Im Komplettpreis sind der klimatisierte Hoteltransfer in Sharm El Sheikh, Schnorchelmaske, passende Schwimmweste, alle Nationalparkgebühren sowie unsere erfahrenen PADI Schnorchelguides direkt im Wasser enthalten" . ($isChild ? ' — inklusive warmem Mittagsbuffet und Softdrinks an Bord unserer Yacht!' : ($isNight ? ' — inklusive starker LED-Unterwasserlampe!' : '.')),
            'overview' => [
                $isChild
                    ? 'Damit der Familienausflug ins Rote Meer rundum entspannt und fair kalkuliert bleibt, reisen Kinder von 5 bis 12 Jahren für fast den halben Erwachsenenpreis (30 € statt 55 €) auf unserer großen Ras-Mohammed- & White-Island-Yacht mit — inklusive kindgerechter Schwimmweste und liebevoller Unterstützung durch unsere Crew.'
                    : ($isLand
                        ? 'Entdecken Sie den berühmten Nationalpark Ras Mohammed von seiner spektakulären Landseite im klimatisierten Bus: Vom ikonischen „Tor Allahs“ über die nördlichsten Mangrovenwälder bis zum salzreichen „Magischen See“ und zwei traumhaften Korallenriff-Schnorchelstopps vom Ufer aus!'
                        : ($isNight
                            ? 'Wenn es über Sharm El Sheikh dunkel wird, verwandelt der Lichtkegel Ihrer LED-Tauchlampe unser geschütztes Hausriff in ein buntes Nachtaquarium mit Oktopussen, Kalmaren, Federsternen und schlafenden Papageifischen — ganz ohne Tauchschein!'
                            : 'Ideal für alle, die ohne ganztägige Bootsfahrt und garantiert ohne Seekrankheit schnorcheln möchten: Direkt vor unserer Strandbasis in Al Montazah schweben Sie in glasklarem Wasser über bunten Hart- und Weichkorallen voller Clownfische und Falterfische.')),
                'Sicherheit steht an erster Stelle: Unsere PADI Guides schwimmen mit Rettungsring direkt bei der Gruppe im Wasser und zeigen Ihnen die schönsten Meeresbewohner aus nächster Nähe.',
            ],
            'specs' => [
                ['Komplettpreis', "{$pStr} " . ($isChild ? 'pro Kind (5–12 Jahre)' : 'pro Person')],
                ['Dauer', $dur],
                ['Teilnehmerregel', $isChild ? 'In Begleitung von mind. 1 Erwachsenem (55 €)' : 'Ab 6 Gästen (für kleinere Gruppen per WhatsApp anfragen oder Schnellboot wählen)'],
                ['Ausrüstung inklusive', $isNight ? 'LED-Unterwasserlampe, Schnorchelmaske & Schwimmweste' : 'Schnorchelmaske & Schwimmweste in passender Größe'],
                ['Transfer & Gebühren', 'Sharm-Hoteltransfer & Nationalparkgebühren komplett inklusive'],
            ],
            'steps' => [
                ['Bequeme Abholung am Hotel', 'Klimatisierter Transfer von Ihrem Resort in Sharm El Sheikh zum Startpunkt.'],
                ['Anpassen von Weste & Maske + Briefing', 'Sorgfältige Größenauswahl, Dichtheitscheck der Maske und Einweisung zum Schutz der Korallen.'],
                ['Geführtes Schnorchelerlebnis im Roten Meer', $isChild ? 'Sicheres Planschen auf der Sandbank White Island, geführtes Riffschnorcheln und leckeres Mittagsbuffet an Bord.' : 'Entspannte Tour entlang farbenfroher Korallengärten direkt in Begleitung unseres PADI Schnorchelguides.'],
                ['Rücktransfer ins Hotel', 'Bequeme Rückfahrt in Ihr Hotel mit unvergesslichen Eindrücken vom Roten Meer.'],
            ],
            'included' => [
                'Klimatisierter Hin- & Rücktransfer ab allen Hotels in Sharm El Sheikh',
                'Schnorchelmaske & geprüfte Schwimmweste (auch Kindergrößen)',
                'Begleitung durch PADI Schnorchelguide direkt im Wasser',
                'Alle anfallenden Nationalpark- & Küstengebühren',
                $isChild ? 'Warmes Mittagsbuffet, Wasser & Softdrinks auf der Yacht' : ($isNight ? 'Wasserdichte LED-Unterwasserlampe für jeden Gast' : 'Süßwasserduschen (an der Basis) bzw. Panoramastopps (Landtour)'),
            ],
            'excluded' => [
                'Optionale Leihflossen (4 €/Tag) oder Neoprenanzug im Winter (10 €/Tag)',
                'Handtücher & korallenfreundliche Sonnencreme',
                $isChild ? 'Erwachsenen-Begleitticket (55 €, einfach separat auswählen)' : 'Optionales Upgrade auf einen echten 10-Min.-Schnuppertauchgang am Hausriff (nur 30 €!)',
            ],
            'ideal_for' => [
                $isChild ? 'Familien mit Kindern zwischen 5 und 12 Jahren auf dem Ganztagesausflug zur White Island' : 'Familien, Paare und Freundesgruppen, die das Rote Meer bequem an der Oberfläche genießen möchten',
                'Nichtschwimmer und vorsichtige Einsteiger dank Schwimmwesten und Guide-Begleitung',
                'Urlauber, die transparente Komplettpreise inklusive Transfer und Parkgebühr schätzen',
            ],
            'faqs' => [
                ['q' => 'Kommen vor Ort noch Nationalparkgebühren oder Transferkosten hinzu?', 'a' => "Nein, im Preis von {$pStr} sind Hoteltransfer, Nationalparkgebühr, Maske und Schwimmweste bereits vollständig enthalten!"],
                ['q' => 'Was, wenn wir bei den Strand-/Bustouren weniger als 6 Personen sind?', 'a' => 'Schreiben Sie uns kurz auf WhatsApp, ob wir Sie einer bestehenden Gruppe anschließen können — oder buchen Sie zu zweit/dritt unser 1-stündiges Privates Schnellboot für insgesamt nur 75 €!'],
                ['q' => 'Reicht der kostenlose „Sinai Only“-Stempel für diese Tour?', 'a' => 'Ja, für sämtliche Schnorcheltouren rund um Sharm El Sheikh und Ras Mohammed genügt der kostenlose Sinai-Stempel.'],
            ],
        ];
    }

    /* ---- 8. private-speedboats (speed-1h, speed-3h) ---- */
    if ($gid === 'private-speedboats') {
        $is1h = $id === 'speed-1h';
        $basePax = $is1h ? 'bis zu 3 Gästen' : '2 Gästen';
        $extraFee = $is1h ? '+50 € für den 4. & 5. Gast' : '+100 € für den 3., 4. & 5. Gast';
        $spdMt = $is1h
            ? 'Privates Schnellboot Sharm El Sheikh 1 Std. | 75 € / Boot'
            : 'Privates Schnellboot Insel Tiran 3 Std. | 225 € / Boot';
        return [
            'h1' => "{$name} ab Sharm El Sheikh ({$pStr} / Boot)",
            'meta_title' => $spdMt,
            'meta_desc' => "Exklusives Privates Schnellboot ab Al Montazah, Sharm El Sheikh: {$name} für {$pStr} pro Boot ({$basePax}) inkl. Masken, Schwimmwesten & Hoteltransfer!",
            'aeo_answer' => "„{$name}“ im Sea College Dive Club in Sharm El Sheikh kostet {$pStr} pauschal für Ihr eigenes privates Schnellboot inklusive {$basePax} (maximal 5 Personen an Bord; {$extraFee}). Direkt ab unserer türkisblauen Lagune in Al Montazah erreichen Sie ohne Umwege " . ($is1h ? 'die unberührten Korallenriffe von Ras Nasrani und Al Montazah in wenigen Minuten' : 'die berühmten Lagunen und vier Korallenriffe der Insel Tiran mit extra viel Zeit zum Schnorcheln und Entspannen') . ' — inklusive Kapitän, Masken, Schwimmwesten und Hoteltransfer.',
            'overview' => [
                'Genießen Sie das Rote Meer wie ein VIP ganz ohne fremde Mitfahrer: Unser modernes Suzuki-Schnellboot liegt direkt am Strand unserer Basis im Cyrene Sharm (Al Montazah) — dem nördlichsten und besten Startpunkt gegenüber der Insel Tiran.',
                $is1h
                    ? 'Die 1-Stunden-Privatfahrt (75 € für bis zu 3 Personen = nur 25 € pro Person!) ist ideal für Familien mit jüngeren Kindern, Paare oder Gäste mit wenig Zeit, die direkt an kristallklaren lokalen Korallengärten schnorcheln möchten.'
                    : 'Mit vollen 3 Stunden auf dem Wasser (225 € für 2 Gäste) bleibt Ihnen neben der rasanten Überfahrt ausgiebig Zeit für gleich zwei bis drei traumhafte Schnorchelplätze rund um Tiran Island, Schildkröten-Spots und die türkisfarbene Lagune.',
            ],
            'specs' => [
                ['Privatboot-Basispreis', "{$pStr} pro Boot (inklusive {$basePax})"],
                ['Zusatzpersonen (max. 5)', $extraFee],
                ['Dauer auf dem Wasser', $dur],
                ['Fahrtgebiet', $is1h ? 'Ras Nasrani & Lokale Korallenriffe vor Al Montazah' : 'Straße von Tiran (Laguna, Gordon, Thomas & Jackson Reef)'],
                ['Inklusive', 'Exklusives Schnellboot, Kapitän, Masken, Westen & Sharm-Hoteltransfer'],
            ],
            'steps' => [
                ['Wunsch-Uhrzeit wählen & Abholung', 'Wir holen Sie zu Ihrer bevorzugten Tageszeit klimatisiert im Hotel ab und bringen Sie nach Al Montazah.'],
                ['Sofortstart ohne Hafen-Wartezeit', 'Einsteigen am Strandsteg und direkte Fahrt über türkisfarbenes Wasser ans Riff.'],
                ['Privates Schnorcheln im eigenen Tempo', 'Sie entscheiden selbst, wie lange Sie an welchem Korallengarten im Wasser bleiben möchten.'],
                ['Rückkehr & Hoteltransfer', 'Malerische Rückfahrt an die Basis, Süßwasserdusche und direkter Rücktransfer ins Resort.'],
            ],
            'included' => [
                "100 % privates Schnellboot mit eigenem Kapitän für {$dur}",
                'Treibstoff, Schnorchelmasken & Schwimmwesten in allen Größen',
                'Klimatisierter Hin- & Rücktransfer in Sharm El Sheikh',
                'Freie Wahl zwischen Vormittag, Mittag oder Sonnenuntergangs-Slot',
            ],
            'excluded' => [
                "Aufpreis für weitere Mitfahrer über {$basePax} hinaus ({$extraFee}, max. 5 Gäste)",
                'Eventuelle Nationalparkgebühr bei Tiran-Anlandung (5 €/Person) & Leihflossen (4 €/Tag)',
                'Größere Gruppe ab 6 Personen? Wählen Sie unsere Ganztages-Privatyacht (ab 650 €)!',
            ],
            'ideal_for' => [
                'Paare, Familien bis 5 Personen und Fotografen mit Wunsch nach 100 % Privatsphäre',
                $is1h ? 'Kurzentschlossene, die für nur 75 € Gesamtpreis ein eigenes Boot erleben möchten' : 'Genießer, die die Riffe der Insel Tiran ausgiebig an 2–3 verschiedenen Plätzen erkunden wollen',
                'Urlauber in Nabq, Al Montazah, Sharks Bay und Naama Bay dank kürzester Anfahrt',
            ],
            'faqs' => [
                ['q' => 'Sind fremde Gäste mit auf dem Schnellboot?', 'a' => 'Niemals — Sie mieten immer das komplette Schnellboot exklusiv für sich und Ihre Begleitung!'],
                ['q' => 'Wie viele Personen passen maximal auf ein Schnellboot?', 'a' => 'Aus Komfort- und Sicherheitsgründen nehmen wir maximal 5 Gäste plus unseren Kapitän an Bord.'],
                ['q' => 'Können wir auch Taucher mit Flasche aufs Schnellboot mitnehmen?', 'a' => 'Für Gerätetaucher bieten wir spezielle Zodiac-Ausfahrten (+50 € / +70 €) oder unsere komfortablen Privatyachten (ab 550 €) mit Tauchplattform an!'],
            ],
        ];
    }

    /* ---- 9. private-charters (private-1-5, private-11-15, private-16-20) ---- */
    $paxTag = $id === 'private-1-5' ? '1–5 Pers.' : ($id === 'private-11-15' ? '11–15 Pers.' : '16–20 Pers.');
    return [
        'h1' => "{$name} in Sharm El Sheikh ({$pStr} / Tag)",
        'meta_title' => "Privatyacht Sharm El Sheikh ({$paxTag}) | {$pStr}/Tag",
        'meta_desc' => "Mieten Sie Ihre eigene Motoryacht im Roten Meer: {$name} in Sharm El Sheikh ({$pStr} für das gesamte Boot, 7–8 Std.) — Ras Mohammed oder Insel Tiran!",
        'aeo_answer' => "„{$name}“ im Sea College Dive Club in Sharm El Sheikh kostet {$pStr} pauschal für die gesamte Yacht (Dauer: {$dur}). Sie chartern ein komplettes, geräumiges Rotes-Meer-Tauch- und Schnorchelboot samt Kapitän und aufmerksamer Crew exklusiv für Ihren Kreis — wahlweise Richtung Nationalpark Ras Mohammed & White Island oder zu den Korallenriffen der Straße von Tiran, beliebig kombinierbar mit Schnorcheln, 1-zu-1-Schnuppertauchen und zertifiziertem Tauchen.",
        'overview' => [
            "Ob romantischer VIP-Tag zu zweit (550 €), großer Familienurlaub oder Ausflug Ihres gesamten deutschen/österreichischen/schweizerischen Tauchvereins bis 20 Personen (900 €–1.100 €): Auf Ihrer eigenen Privatyacht gehören Ihnen Sonnendeck, schattiger Salon und Badeplattform den ganzen Tag ganz allein!",
            'Unser Charter-Koordinator Mo bespricht vorab jedes Detail mit Ihnen: spätere Ausschläfer-Abfahrt, Lieblingsriffe abseits des Trubels, frisch an Bord gekochtes Mittagsbuffet und auf Wunsch eigene PADI Tauchlehrer für Schnuppertaucher und Brevetierte an Bord.',
        ],
        'specs' => [
            ['Kompletter Yachtpreis', "{$pStr} pauschal für das gesamte Boot"],
            ['Charterdauer', 'Ganzer Tag auf See (ca. 7–8 Stunden)'],
            ['Wählbare Routen', 'Nationalpark Ras Mohammed & White Island ODER Insel Tiran'],
            ['Ausstattung an Bord', 'Sonnendeck, beschatteter Salon, WC, Süßwasserdusche & Teak-Tauchplattform'],
            ['Kombinierbar mit', 'Schnorcheln, 1-zu-1-Schnuppertauchen, Tauchpaketen & Bord-Catering'],
        ],
        'steps' => [
            ['Individuelle Vorab-Planung mit unserem Team', 'Abstimmung von Abholzeit, Route (Ras Mohammed oder Tiran), Verpflegung und gewünschten Tauch-/Schnorchelaktivitäten.'],
            ['Privater Transfer & Herzlicher Empfang an Bord', 'Abholung Ihrer Gruppe am Hotel in Sharm El Sheikh und Begrüßung durch Ihren Kapitän und die Crew.'],
            ['2–3 Exklusive Ankerplätze im Roten Meer', 'Schnorcheln, Tauchen, Baden auf der White Island und Entspannen auf dem Oberdeck völlig ohne Zeitdruck.'],
            ['Entspannte Rückfahrt am Nachmittag', 'Gemütliches Ausklingenlassen in der Nachmittagssonne und Rücktransfer direkt in Ihr Hotel.'],
        ],
        'included' => [
            "Exklusive Ganztages-Motoryacht mit Kapitän & Crew ({$dur})",
            'Freie Routengestaltung (Ras Mohammed oder Straße von Tiran, wetterabhängig)',
            'Schnorchel-Begleitung & Sicherheitsausrüstung an Bord',
            'Koordination des privaten Hoteltransfers in Sharm El Sheikh',
        ],
        'excluded' => [
            'Staatliche Nationalparkgebühren pro Person (5 €/Gast)',
            'Individuelles Mittagsbuffet & Getränkepaket an Bord (vorab günstig pro Person zubuchbar)',
            'Optionale Schnuppertauchgänge oder zertifizierte Tauchgänge inkl. Ausrüstung & PADI Lehrer',
        ],
        'ideal_for' => [
            'Paare, Familien, Geburtstagsrunden, Hochzeitsgesellschaften und Tauchvereine/Tauchschulen aus der DACH-Region',
            'Gruppen mit gemischtem Erfahrungsstand (Taucher, Schnuppertaucher, Kinder & Schnorchler gemeinsam auf einem Schiff)',
            'Gäste, die maximale Privatsphäre, Hygiene und ein frei bestimmbares Tagestempo wünschen',
        ],
        'faqs' => [
            ['q' => 'Wie hoch ist der Preis pro Person bei voller Auslastung der Yacht?', 'a' => 'Ab 10–20 Gästen liegt der reine Yachtanteil bei nur ca. 55 € bis 65 € pro Person — kaum mehr als ein öffentliches Ausflugsboot, aber zu 100 % privat!'],
            ['q' => 'Können wir ein frisch gekochtes Mittagsbuffet und Getränke für alle vorbestellen?', 'a' => 'Ja, selbstverständlich! Unsere Schiffsköche zaubern auf Wunsch ein komplettes Buffet mit Meeresfrüchten, Gegrilltem, Salaten, Obst und gekühlten Getränken.'],
            ['q' => 'Können Tauchvereine mehrere Privatcharter-Tage am Stück buchen?', 'a' => 'Sehr gerne! Für Tauchclubs und Gruppen bis 20 Personen stellen wir mehrtägige Wunschprogramme inkl. Ras Mohammed, Tiran und SS Thistlegorm zusammen.'],
        ],
    ];
};
