<?php
/**
 * Group-aware Italian SEO/AEO builder for all remaining catalog items (zero English fallback).
 * Returns: static function (array $it, array $seo): array
 */
declare(strict_types=1);

return static function (array $it, array $seo): array {
    $id    = (string) ($it['id'] ?? '');
    $gid   = (string) ($it['_group']['id'] ?? '');
    $name  = item_name($it);
    $desc  = item_desc($it);
    $dur   = item_duration($it) ?: 'Flessibile';
    $price = isset($it['price']) && $it['price'] !== null ? '€' . (int) $it['price'] : 'Su richiesta';
    $unit  = (string) ($it['unit'] ?? '');
    $save  = !empty($it['save']) ? ' (risparmi €' . (int) $it['save'] . '!)' : '';
    $pLbl  = $price . ($unit === 'day' ? '/giorno' : ($unit === 'boat' ? '/barca' : ''));

    switch ($gid) {
        case 'certified-packages':
            return [
                'h1' => $name . ' a Sharm El Sheikh — Subacquei Brevettati',
                'meta_title' => $name . ' Sharm El Sheikh ' . $price . ' | Centro PADI 5 Stelle',
                'meta_desc' => 'Prenota ' . $name . ' a Sharm El Sheikh a ' . $price . $save . '. Immersioni guidate a Ras Mohammed, Stretto di Tiran e reef locali con guida PADI, bombole 12L e transfer inclusi.',
                'aeo_answer' => $name . ' presso Sea College Dive Club a Sharm El Sheikh costa ' . $price . $save . ' (' . $dur . '), è strettamente personale per subacquei brevettati e include guida PADI 5 Stelle, bombole aria da 12L, zavorra e trasferimenti A/R dagli hotel di Sharm (+€10/giorno barca con pranzo & +€5/giorno tassa parco nei giorni su yacht).',
                'overview' => [
                    'Esplora i fondali leggendari del Mar Rosso con ' . $name . ' presso il nostro Centro Diving PADI 5 Stelle ad Al Montazah (' . $price . $save . '). Ogni giornata viene pianificata in piccoli gruppi omogenei per brevetto ed esperienza, alternando i muri corallini del Parco Nazionale di Ras Mohammed, i quattro reef dello Stretto di Tiran e i giardini locali ricchi di vita marina.',
                    $desc . ' Le nostre tariffe sono trasparenti al 100%: guida professionale PADI, bombole da 12L in alluminio, pesi e trasferimenti climatizzati dal tuo resort a Sharm El Sheikh sono sempre inclusi, con la libertà di distribuire i giorni di immersione come preferisci durante la tua vacanza.',
                ],
                'specs' => [
                    ['Tariffa Ufficiale', $price . $save],
                    ['Durata Indicativa', $dur],
                    ['Requisito Minimo', 'Subacqueo brevettato (PADI, SSI, CMAS, FIPSAS o equiv.)'],
                    ['Siti d’Immersione', 'Ras Mohammed, Stretto di Tiran & Reef Locali'],
                    ['Inclusi Base', 'Guida PADI, bombole 12L, zavorra & transfer hotel'],
                    ['Extra Giorni Yacht', '+€10/giorno barca (pranzo incl.) & +€5/giorno tassa parco'],
                ],
                'steps' => [
                    ['Pick-up in Resort & Check Documenti', 'Ritiro gratuito in hotel a Sharm El Sheikh, verifica brevetto/logbook e preparazione ceste attrezzatura.'],
                    ['Briefing Dettagliato del Sito', 'Mappa delle correnti, profondità, punti d’interesse biologico e procedure di sicurezza con la tua guida PADI.'],
                    ['Immersioni Guidate nel Mar Rosso', 'Esplorazione rilassata tra gorgonie, tartarughe, banchi di carangidi e barriere coralline incontaminate.'],
                    ['Logbook, Risciacquo & Rientro', 'Compilazione e timbro ufficiale PADI 5 Stelle sul logbook, deposito attrezzatura custodito e rientro in hotel.'],
                ],
                'included' => [
                    'Immersioni guidate in piccoli gruppi con Guida / Istruttore PADI 5 Stelle',
                    'Bombole aria da 12L, cintura e piombi per ogni immersione',
                    'Deposito e risciacquo attrezzatura presso il centro di Al Montazah',
                    'Trasferimenti A/R dagli hotel di Sharm El Sheikh e tasse locali',
                ],
                'excluded' => [
                    'Supplemento barca (€10/giorno incluso pranzo caldo a buffet!) e tassa parco (€5/giorno)',
                    'Noleggio attrezzatura personale (€25/giorno set completo o singoli pezzi)',
                    'Assicurazione subacquea (€7/giorno o settimanale €32) ed eventuale Nitrox (€8/bombola 12L)',
                ],
                'ideal_for' => [
                    'Subacquei brevettati in vacanza a Sharm El Sheikh che cercano piccoli gruppi e sicurezza 5 Stelle',
                    'Chi desidera flessibilità nei giorni di uscita senza obbligo di giorni consecutivi',
                    'Subacquei che vogliono abbinare barriere coralline ed eventuali supplementi relitti (Thistlegorm / Dunraven)',
                ],
                'faqs' => [
                    ['q' => 'Il pacchetto immersioni può essere diviso tra due persone?', 'a' => 'No, per standard assicurativi e PADI tutti i pacchetti immersioni sono strettamente individuali e non condivisibili tra più subacquei.'],
                    ['q' => 'Cosa succede se non mi immergo da più di 6 o 12 mesi?', 'a' => 'Per la tua sicurezza, tra 6 e 12 mesi di pausa effettuiamo una rapida Check Dive (€25), mentre oltre i 12 mesi di inattività è richiesto lo Scuba Review (€50).'],
                    ['q' => 'Posso aggiungere il Nitrox alle mie immersioni?', 'a' => 'Certamente! Se possiedi il brevetto Enriched Air Nitrox, le bombole EANx32 costano €8 (12L) o €10 (15L) ciascuna.'],
                ],
            ];

        case 'daily-fees':
            return [
                'h1' => $name . ' — Supplementi & Servizi Sub a Sharm El Sheikh',
                'meta_title' => $name . ' Sharm El Sheikh ' . $pLbl . ' | Listino Ufficiale Sea College',
                'meta_desc' => $name . ' presso Sea College Dive Club Sharm El Sheikh (' . $pLbl . '): ' . $desc,
                'aeo_answer' => $name . ' presso Sea College Dive Club a Sharm El Sheikh ha una tariffa ufficiale trasparente di ' . $pLbl . ' (' . $dur . '): ' . $desc,
                'overview' => [
                    'Chiarezza e trasparenza totale prima ancora di partire dall’Italia: presso Sea College Dive Club ogni supplemento operativo o servizio personalizzato — come ' . $name . ' (' . $pLbl . ') — è pubblicato chiaramente a listino senza sorprese in loco.',
                    $desc . ' Puoi aggiungere questo servizio in anticipo durante la prenotazione online via WhatsApp oppure richiederlo direttamente al desk del nostro Centro PADI 5 Stelle ad Al Montazah.',
                ],
                'specs' => [
                    ['Servizio / Supplemento', $name],
                    ['Tariffa Ufficiale', $pLbl],
                    ['Applicazione', $dur],
                    ['Centro Operativo', 'Sea College Dive Club (PADI 5★ Al Montazah)'],
                    ['Prenotazione', 'Online, WhatsApp o direttamente al check-in'],
                ],
                'steps' => [
                    ['Selezione Servizio', 'Indica il servizio desiderato nella tua richiesta o al check-in mattutino.'],
                    ['Predisposizione Tecnica / Logistica', 'Il nostro staff prepara permessi, guida dedicata o ricarica gas/bombola verificata prima dell’imbarco.'],
                    ['Controllo & Utilizzo in Giornata', 'Per le miscele Nitrox analizziamo insieme la % di O₂ con analizzatore digitale prima di caricare la cesta in barca.'],
                    ['Riepilogo Chiaro', 'Conteggio limpido e trasparente abbinato al tuo pacchetto immersioni o corso PADI.'],
                ],
                'included' => [
                    $name . ' come da descrizione ufficiale (' . $desc . ')',
                    'Gestione logistica completa da parte dello staff Sea College',
                    'Assistenza a terra e a bordo dei nostri yacht diving',
                    'Imposte locali incluse',
                ],
                'excluded' => [
                    'Pacchetto immersioni base o corso PADI a cui il supplemento si abbina',
                    'Noleggio attrezzatura personale e assicurazione subacquea',
                    'Servizi non espressamente indicati nella voce selezionata',
                ],
                'ideal_for' => [
                    'Subacquei brevettati e allievi che vogliono calcolare un preventivo trasparente al 100%',
                    'Chi desidera personalizzare la propria giornata (Nitrox, bombola 15L o guida privata 1-a-1)',
                    'Ospiti sulle uscite giornaliere in yacht verso Ras Mohammed e Stretto di Tiran',
                ],
                'faqs' => [
                    ['q' => 'Cosa comprende esattamente il supplemento barca di €10/giorno?', 'a' => 'Il supplemento barca di €10/giorno copre l’intera giornata sullo yacht diving E include anche il pranzo caldo a buffet cucinato fresco a bordo con acqua, tè e caffè!'],
                    ['q' => 'La tassa del Parco Marino di €5/giorno va pagata ogni giorno?', 'a' => 'Si applica esclusivamente nei giorni in cui si esce nelle aree marine protette del Parco Nazionale di Ras Mohammed, Nabq o Stretto di Tiran.'],
                    ['q' => 'Serve il brevetto Nitrox per richiedere le ricariche EANx da €8 / €10?', 'a' => 'Sì, per riempire e utilizzare bombole Nitrox è richiesto il brevetto Enriched Air Diver (conseguibile da noi in 1 solo giorno a €145!).'],
                ],
            ];

        case 'equipment-rental':
            return [
                'h1' => 'Noleggio ' . $name . ' a Sharm El Sheikh (' . $pLbl . ')',
                'meta_title' => 'Noleggio ' . $name . ' Sharm El Sheikh ' . $pLbl . ' | Attrezzatura Sub PADI 5★',
                'meta_desc' => 'Viaggia leggero in aereo! Noleggio ' . $name . ' revisionato e igienizzato a Sharm El Sheikh a ' . $pLbl . ' presso Sea College Dive Club PADI 5 Stelle.',
                'aeo_answer' => 'Il noleggio di ' . $name . ' presso Sea College Dive Club a Sharm El Sheikh costa ' . $pLbl . ' (' . $desc . '), con attrezzatura delle migliori marche subacquee regolarmente revisionata, sanificata e disponibile in tutte le taglie dal XS al XXL.',
                'overview' => [
                    'Risparmia il costo del bagaglio speciale in stiva sui voli dall’Italia! Nella sala attrezzature del nostro Centro Diving PADI 5 Stelle ad Al Montazah trovi ' . $name . ' (' . $pLbl . ') sottoposto a manutenzione periodica certificata e disinfezione accurata dopo ogni singolo utilizzo.',
                    $desc . ' Al momento del check-in provi con calma le taglie insieme al nostro staff: prepariamo una cesta numerata personale che il nostro team farà trovare direttamente a bordo dello yacht ogni mattina!',
                ],
                'specs' => [
                    ['Articolo a Noleggio', $name],
                    ['Tariffa Giornaliera', $pLbl],
                    ['Taglie Disponibili', 'Bambino / Junior e Adulto XS – XXL'],
                    ['Manutenzione & Igiene', 'Revisionato secondo standard PADI 5 Stelle & igienizzato'],
                    ['Nota Corsi PADI', 'Già INCLUSO gratis in Open Water, Advanced e Offerte Bundle!'],
                ],
                'steps' => [
                    ['Prova Taglia al Check-In', 'Verifica vestibilità e funzionamento insieme ai nostri tecnici attrezzatura ad Al Montazah.'],
                    ['Assegnazione Cesta Numerata', 'Il tuo equipaggiamento viene etichettato e trasportato per te sulle barche giornaliere.'],
                    ['Risciacquo in Acqua Dolce', 'Lavaggio in vasche dedicate e asciugatura all’ombra a fine giornata al centro diving.'],
                    ['Riconsegna Semplice a Fine Vacanza', 'Zero valigie bagnate e pesanti da riportare in aeroporto!'],
                ],
                'included' => [
                    'Utilizzo giornaliero di ' . $name . ' controllato e igienizzato',
                    'Cambio taglia gratuito in qualsiasi momento se desideri maggiore comfort',
                    'Trasporto nelle ceste subacquee sulle nostre uscite guidate e custodia notturna',
                    'Tasse locali incluse',
                ],
                'excluded' => [
                    'Pacchetto immersioni guidate (nota: bombola 12L e piombi sono sempre inclusi nei pacchetti!)',
                    'Smarrimento o danneggiamento colposo dell’attrezzatura in acqua',
                    'Assicurazione subacquea personale (€7/giorno)',
                ],
                'ideal_for' => [
                    'Subacquei italiani che volano con solo bagaglio a mano o valigia leggera',
                    'Chi possiede già maschera e computer ma preferisce noleggiare GAV, erogatore o muta in loco',
                    'Subacquei che vogliono aggiungere computer subacqueo (€6/g) o torcia LED (€6/g) per relitti e notturne',
                ],
                'faqs' => [
                    ['q' => 'Conviene noleggiare i singoli pezzi o il Set Completo (€25/giorno)?', 'a' => 'Se ti servono più di 3–4 articoli, il Noleggio Attrezzatura Completa a €25/giorno (muta, GAV, erogatore, maschera e pinne) è nettamente più conveniente rispetto ai pezzi singoli!'],
                    ['q' => 'Devo pagare il noleggio se mi iscrivo a un corso PADI?', 'a' => 'No! Nei corsi principali (Bubblemaker, Discover Scuba, Scuba Diver, Open Water, Advanced e pacchetti Stars/Super/Mega/Heros) l’attrezzatura completa è già inclusa nel prezzo del corso!'],
                    ['q' => 'Che spessore hanno le mute a noleggio a Sharm El Sheikh?', 'a' => 'Disponiamo di mute intere in neoprene da 5 mm (perfette in inverno/primavera con acqua a 22–24°C) e mute/shorty da 3–5 mm per i caldi mesi estivi e autunnali (27–29°C).'],
                ],
            ];

        case 'diving-insurance':
            return [
                'h1' => $name . ' a Sharm El Sheikh — Copertura Camera Iperbarica',
                'meta_title' => $name . ' Sharm El Sheikh ' . $price . ' | Polizza Sub Temporanea',
                'meta_desc' => 'Immergiti protetto nel Mar Rosso! Attiva subito ' . $name . ' (' . $price . ') presso Sea College Dive Club Sharm El Sheikh: copertura medica subacquea e camera iperbarica.',
                'aeo_answer' => $name . ' presso Sea College Dive Club a Sharm El Sheikh costa ' . $price . ' per ' . $dur . ' e garantisce copertura assicurativa immediata per incidenti subacquei e trattamenti in camera iperbarica nel Sud Sinai per i subacquei privi di polizza annuale DAN attiva.',
                'overview' => [
                    'Nel governatorato del Sud Sinai (Sharm El Sheikh e Dahab) l’assicurazione specifica per attività subacquee e camera iperbarica è essenziale — le normali assicurazioni viaggio o sanitarie di carta di credito spesso escludono le immersioni con autorespiratore o impongono massimali ridotti oltre i 12–18 metri.',
                    'Se non possiedi già una polizza annuale DAN Europe attiva, con ' . $name . ' (' . $price . ') attivi in due minuti direttamente al nostro desk la tua copertura temporanea valida ' . $dur . ', immergendoti a Ras Mohammed, Tiran e sui relitti in assoluta serenità!',
                ],
                'specs' => [
                    ['Formula Assicurativa', $name],
                    ['Prezzo Totale', $price],
                    ['Durata Copertura', $dur],
                    ['Copertura Principale', 'Medicina subacquea d’emergenza & Camera Iperbarica locale'],
                    ['Attivazione', 'Immediata al check-in prima di entrare in acqua'],
                ],
                'steps' => [
                    ['Verifica Polizza Personale', 'Se hai già DAN attiva mostri la tessera digitale e non paghi nulla; altrimenti scegli la durata più adatta.'],
                    ['Registrazione Nominativa al Desk', 'Inserimento di nome, cognome, passaporto e data di inizio prima della prima immersione.'],
                    ['Validità Immediata in Tutto il Mar Rosso', 'Copertura attiva per tutte le immersioni guidate svolte entro i limiti del tuo brevetto.'],
                    ['Supporto Diretto del Centro 5★', 'Coordinamento diretto con il Centro di Medicina Iperbarica di Sharm El Sheikh in caso di necessità.'],
                ],
                'included' => [
                    'Copertura assicurativa subacquea temporanea valida per ' . $dur,
                    'Copertura per trattamenti d’emergenza in camera di decompressione locale',
                    'Attivazione immediata senza burocrazia presso il centro diving',
                    'Imposte incluse',
                ],
                'excluded' => [
                    'Spese mediche generiche non correlate all’attività subacquea o annullamento volo/hotel',
                    'Immersioni effettuate oltre i limiti di profondità del proprio brevetto o in violazione delle norme di sicurezza',
                    'Nota: nei corsi Open Water e nei pacchetti Stars/Super/Mega/Heros l’assicurazione è GIÀ INCLUSA!',
                ],
                'ideal_for' => [
                    'Subacquei certificati in vacanza che si immergono 1–2 volte l’anno senza abbonamento annuale DAN',
                    'Allievi di corsi avanzati o specialità singole che necessitano di copertura di 1, 3, 7 o 14 giorni',
                    'Chi vuole evitare qualsiasi scoperto sanitario all’estero',
                ],
                'faqs' => [
                    ['q' => 'Ho già l’assicurazione viaggio del tour operator: basta per fare immersioni?', 'a' => 'Controlla bene le condizioni: la maggior parte delle polizze viaggio standard esclude le immersioni oltre i 10–18 metri o non anticipa i costi della camera iperbarica. In dubbio, con pochi euro sei coperto al 100%!'],
                    ['q' => 'Quale taglio conviene scegliere per una settimana a Sharm?', 'a' => 'Se ti immergi 3 giorni conviene la polizza 3 Giorni (€20 invece di €21); per 4 o più giorni conviene decisamente la 7 Giorni (€32)!'],
                    ['q' => 'Se ho la tessera annuale DAN Europe devo comprarla?', 'a' => 'Assolutamente no! Ti basterà mostrarci il numero di polizza DAN in corso di validità al momento del check-in.'],
                ],
            ];

        case 'specialty-courses':
            return [
                'h1' => 'Corso ' . $name . ' a Sharm El Sheikh — Specialità Ufficiale PADI',
                'meta_title' => $name . ' Sharm El Sheikh ' . $price . ' | Specialità PADI 5 Stelle',
                'meta_desc' => 'Consegui il brevetto ' . $name . ' a Sharm El Sheikh a ' . $price . ' (' . $dur . ') presso Sea College Dive Club PADI 5 Stelle. Valido per PADI Master Scuba Diver!',
                'aeo_answer' => 'Il corso ' . $name . ' presso il Centro Diving PADI 5 Stelle Sea College a Sharm El Sheikh costa ' . $price . ' (' . $dur . '), include istruzione pratica dedicata nel Mar Rosso, noleggio attrezzatura standard e trasferimenti hotel, ed è valido come credito per il grado d’élite PADI Master Scuba Diver.',
                'overview' => [
                    'Perfeziona la tua tecnica subacquea nelle acque calde e limpidissime di Sharm El Sheikh con il corso ufficiale ' . $name . ' (' . $price . ', durata ' . $dur . ')! Presso il nostro Centro PADI 5 Stelle ad Al Montazah trasformiamo ogni immersione didattica in un’esperienza entusiasmante sui reef di Tiran, Ras Mohammed e dell’House Reef.',
                    $desc . ' Ogni Specialità PADI che completi ti rende un subacqueo più sicuro, consapevole e rilassato E ti avvicina al traguardo più ambito della subacquea ricreativa: il riconoscimento PADI Master Scuba Diver (ottenibile con Rescue Diver, 5 Specialità PADI e 50 immersioni registrate)!',
                ],
                'specs' => [
                    ['Prezzo Corso', $price . ' (+€85 manuale/brevetto PADI per specialità)'],
                    ['Durata', $dur],
                    ['Centro Formazione', 'Centro Diving PADI 5 Stelle — Al Montazah, Sharm'],
                    ['Attrezzatura Subacquea', 'Attrezzatura standard inclusa nel corso'],
                    ['Credito Master Scuba Diver', 'Sì — certificazione internazionale PADI a vita'],
                ],
                'steps' => [
                    ['Briefing Tecnico & Revisione Conoscenze', 'Analisi degli obiettivi pratici, dell’attrezzatura specifica e delle tecniche chiave con il tuo Specialty Instructor PADI.'],
                    ['Esercitazioni Pratiche nel Mar Rosso', 'Applicazione reale in acqua con feedback personalizzato su assetto, sicurezza ed esecuzione.'],
                    ['Padronanza Completa in Acque Libere', 'Consolidamento delle nuove abilità esplorando le splendide formazioni coralline di Sharm El Sheikh.'],
                    ['Certificazione Ufficiale PADI', 'Rilascio immediato della tua nuova eCard PADI Specialty Diver e timbro sul logbook!'],
                ],
                'included' => [
                    'Formazione teorico-pratica completa con PADI Specialty Instructor',
                    'Immersioni di addestramento previste dagli standard della specialità',
                    'Noleggio attrezzatura subacquea standard per tutta la durata del corso',
                    'Trasferimenti A/R dagli hotel di Sharm El Sheikh e tasse governative',
                ],
                'excluded' => [
                    'Manuale digitale PADI e tassa di certificazione (€85 per ogni specialità certificata)',
                    'Supplemento barca (€10/giorno incluso pranzo) e tassa parco (€5/giorno) nei giorni su yacht',
                    'Assicurazione subacquea giornaliera (€7/giorno)',
                ],
                'ideal_for' => [
                    'Subacquei Open Water e Advanced che vogliono affinare assetto, corrente, navigazione o fotografia',
                    'Candidati al grado d’élite PADI Master Scuba Diver (approfitta dei pacchetti 2 Specialità a €470 o 3 Specialità a €675!)',
                    'Chi ama combinare divertimento in vacanza e crescita tecnica reale',
                ],
                'faqs' => [
                    ['q' => 'Le Adventure Dive fatte durante il corso Advanced valgono come credito?', 'a' => 'Sì! Se hai registrato la relativa Adventure Dive (es. Drift, Peak Performance Buoyancy, Navigation, Fish ID) con un istruttore PADI, può valere come Immersione 1 della corrispondente Specialità!'],
                    ['q' => 'Come funzionano i pacchetti risparmio 2 Specialità (€470) e 3 Specialità (€675)?', 'a' => 'Ti permettono di combinare più specialità a scelta durante la settimana a una tariffa scontata (escluse solo Wreck e Sidemount per via della logistica/attrezzatura dedicata).'],
                    ['q' => 'L’attrezzatura è inclusa nel prezzo della specialità?', 'a' => 'Sì, il noleggio dell’attrezzatura subacquea standard è incluso per tutti i giorni del corso.'],
                ],
            ];

        case 'snorkeling-trips':
            return [
                'h1' => $name . ' a Sharm El Sheikh — Snorkeling Guidato Mar Rosso',
                'meta_title' => $name . ' Sharm El Sheikh ' . $price . ' | Escursione Snorkeling',
                'meta_desc' => 'Prenota ' . $name . ' a Sharm El Sheikh a ' . $price . ' (' . $dur . '): ' . $desc,
                'aeo_answer' => $name . ' presso Sea College Dive Club a Sharm El Sheikh costa ' . $price . ' (' . $dur . ') e offre snorkeling guidato in totale sicurezza sopra le barriere coralline più ricche del Mar Rosso con guida PADI, attrezzatura snorkeling e trasferimenti hotel inclusi.',
                'overview' => [
                    'Non serve scendere in profondità per restare a bocca aperta davanti alla meraviglia del Mar Rosso: i primi 3 metri sotto la superficie di Sharm El Sheikh ospitano il 90% dei colori più accesi, tra coralli cervello, anemoni con pesci pagliaccio, pesci farfalla, pesci pappagallo e tartarughe! Con ' . $name . ' (' . $price . ') vivi la barriera corallina accompagnato dalla sicurezza di un vero Centro PADI 5 Stelle.',
                    $desc . ' Che si tratti della quota ridotta bambini sulla nostra crociera a Ras Mohammed & Isola Bianca, di un’avventura via terra tra le porte monumentali e le mangrovie del Parco Nazionale o di un emozionante snorkeling notturno con torcia sull’House Reef, pensiamo a tutto noi!',
                ],
                'specs' => [
                    ['Esperienza', $name],
                    ['Prezzo Ufficiale', $price],
                    ['Durata', $dur],
                    ['Adatto a Principianti', 'Sì — giubbotti galleggianti e guida PADI in acqua'],
                    ['Trasferimenti Hotel', 'Inclusi A/R da tutta Sharm El Sheikh'],
                ],
                'steps' => [
                    ['Pick-up Climatizzato dal Tuo Resort', 'Prelievo puntuale in hotel e arrivo alla spiaggia di Al Montazah, al porto o al Parco di Ras Mohammed.'],
                    ['Consegna Kit & Briefing Sicurezza', 'Regolazione maschera, giubbotto salvagente (e torcia subacquea per l’uscita notturna!) e spiegazione della fauna marina.'],
                    ['Snorkeling Guidato sulla Barriera Corallina', 'Nuotata rilassata seguendo la nostra guida esperta sopra i giardini di corallo più spettacolari senza mai toccare il reef.'],
                    ['Relax & Rientro in Hotel', 'Rientro comodo al tuo resort carico di ricordi indimenticabili!'],
                ],
                'included' => [
                    'Guida PADI esperta in acqua per tutta l’attività di snorkeling',
                    'Maschera, boccaglio e giubbotto galleggiante (più torcia subacquea nello snorkeling notturno!)',
                    'Ingressi e pasti come specificato nella singola formula (' . $desc . ')',
                    'Trasferimenti A/R dagli hotel di Sharm El Sheikh e tasse',
                ],
                'excluded' => [
                    'Pinne a noleggio opzionali (€4/giorno se desiderate)',
                    'Foto e video subacquei professionali opzionali',
                    'Telo mare personale e crema solare biodegradabile',
                ],
                'ideal_for' => [
                    'Famiglie con bambini, coppie e amanti della natura di tutte le età',
                    'Anche chi ha poca confidenza con l’acqua profonda (grazie ai giubbotti salvagente)',
                    'Accompagnatori non subacquei che vogliono godersi il Mar Rosso in sicurezza',
                ],
                'faqs' => [
            ['q' => 'Anche chi non sa nuotare bene può partecipare?', 'a' => 'Assolutamente sì! Indossando il giubbotto galleggiante resti in superficie senza alcuno sforzo e la nostra guida in acqua dispone sempre di salvagente di supporto.'],
            ['q' => 'Com’è lo Snorkeling Notturno sull’House Reef (€45)?', 'a' => 'È un’esperienza incredibile: armato di torcia subacquea osservi dall’alto pesci leone, murene, ricci diadema, stelle gorgonocefale e il plancton bioluminescente nell’acqua calma di Al Montazah!'],
            ['q' => 'Serve il visto da $25 per Ras Mohammed (via mare o via terra)?', 'a' => 'No! Il Parco Nazionale di Ras Mohammed è accessibile con il normale timbro gratuito «Sinai Only» (per l’escursione via terra porta con te il passaporto originale).'],
                ],
            ];

        case 'private-speedboats':
        case 'private-charters':
        default:
            $isSpeed = ($gid === 'private-speedboats');
            return [
                'h1' => $name . ' a Sharm El Sheikh — Barca Esclusiva nel Mar Rosso',
                'meta_title' => $name . ' Sharm El Sheikh ' . $pLbl . ' | Noleggio Barca Privata',
                'meta_desc' => 'Vivi il Mar Rosso in totale privacy! ' . $name . ' a Sharm El Sheikh a ' . $pLbl . ' (' . $dur . '): Stretto di Tiran o Ras Mohammed & Isola Bianca su misura per voi.',
                'aeo_answer' => $name . ' presso Sea College Dive Club a Sharm El Sheikh costa ' . $pLbl . ' (' . $dur . '): ' . $desc . ' Ideale per coppie, famiglie e gruppi italiani che desiderano esplorare Tiran o Ras Mohammed con orari e soste 100% personalizzati.',
                'overview' => [
                    'Regalati il lusso della libertà assoluta sul Mar Rosso con ' . $name . ' (' . $pLbl . ', durata ' . $dur . ')! ' . ($isSpeed
                        ? 'Partendo direttamente davanti al nostro centro sulla spiaggia di Al Montazah raggiungi i reef incontaminati e le lagune smeraldo dell’Isola di Tiran in appena 15 minuti di navigazione veloce, anticipando tutte le grandi imbarcazioni turistiche.'
                        : 'Avere uno yacht a motore riservato esclusivamente alla tua famiglia o al tuo gruppo di amici significa scegliere l’orario di partenza, la rotta (Parco Nazionale di Ras Mohammed & Isola Bianca oppure Stretto di Tiran) e godersi il mare senza estranei a bordo.'),
                    $desc . ' Il nostro team PADI 5 Stelle cura ogni minimo dettaglio: su richiesta possiamo personalizzare l’uscita con guida in acqua, catering fresco a bordo, battesimi del mare 1-a-1 per principianti o immersioni guidate per subacquei brevettati!',
                ],
                'specs' => [
                    ['Imbarcazione Esclusiva', $name],
                    ['Tariffa Barca Privata', $pLbl],
                    ['Durata', $dur],
                    ['Destinazioni', $isSpeed ? 'Lagune & Reef dell’Isola di Tiran (15 min da Al Montazah!)' : 'Ras Mohammed & Isola Bianca OPPURE Stretto di Tiran'],
                    ['Tassa Parco Marino', '+€5 a persona'],
                    ['Extra Personalizzabili', 'Guida PADI, pranzo a bordo (su yacht) & immersioni sub'],
                ],
                'steps' => [
                    ['Scelta di Orario e Itinerario via WhatsApp', 'Concordiamo l’orario perfetto e le tappe preferite per il vostro gruppo.'],
                    ['Imbarco Rapido & Benvenuto del Capitano', $isSpeed ? 'Partenza immediata dalla baia di Al Montazah con trasferimento hotel A/R incluso.' : 'Accoglienza sul vostro yacht privato con ponti solarium e salone riservati solo a voi.'],
                    ['Snorkeling (e Sub!) nei Punti Più Esclusivi', 'Ormeggi selezionati lontano dalla folla tra tartarughe marine, coralli colorati e acque turchesi.'],
                    ['Rientro Rilassato a Sharm El Sheikh', 'Ritorno all’orario concordato con ricordi e fotografie da sogno!'],
                ],
                'included' => [
                    'Noleggio esclusivo dell’imbarcazione con capitano esperto e carburante per ' . $dur,
                    $isSpeed ? 'Maschere da snorkeling, giubbotti salvagente e trasferimenti hotel A/R a Sharm' : 'Equipaggio dedicato, permessi di navigazione e giubbotti salvagente',
                    'Itinerario flessibile adattato ai desideri e al ritmo dei vostri ospiti',
                    'Assistenza organizzativa Sea College PADI 5 Stelle',
                ],
                'excluded' => [
                    'Tassa governativa Parco Marino (€5 a persona)',
                    $isSpeed ? 'Ospiti aggiuntivi dal 3° al 5° passeggero (vedi tariffa per fascia oraria)' : 'Pranzo a buffet / bevande e trasferimenti privati (quotabili su misura per il gruppo)',
                    'Istruttore subacqueo privato e attrezzatura scuba (disponibili su richiesta)',
                ],
                'ideal_for' => [
                    $isSpeed ? 'Coppie e piccole famiglie (fino a 5 ospiti) che cercano velocità, privacy e zero folla a Tiran' : 'Famiglie numerose, gruppi di amici italiani, compleanni ed eventi speciali sul Mar Rosso',
                    'Fotografi, amanti della privacy e famiglie con bambini o nonni a bordo',
                    'Gruppi misti di snorkelisti e subacquei che vogliono condividere una barca tutta per sé',
                ],
                'faqs' => [
                    ['q' => 'Il prezzo indicato è a persona o per l’intera barca?', 'a' => 'La tariffa di ' . $price . ' si intende per l’intera imbarcazione privata per la capienza indicata (' . $desc . '), rendendola convenientissima se divisa tra i partecipanti!'],
                    ['q' => 'Possiamo fare anche immersioni con le bombole dalla nostra barca privata?', 'a' => 'Assolutamente sì su gli yacht giornalieri (e snorkeling su motoscafi): possiamo imbarcare un istruttore PADI dedicato, bombole e attrezzature sia per brevettati sia per chi vuole fare il Battesimo del Mare!'],
                    ['q' => 'Cosa succede in caso di vento forte o mare mosso?', 'a' => 'La sicurezza viene sempre al primo posto: se la Capitaneria chiude la navigazione per meteo avverso, spostiamo gratuitamente l’uscita al primo giorno utile o rimborsiamo senza penali.'],
                ],
            ];
    }
};
