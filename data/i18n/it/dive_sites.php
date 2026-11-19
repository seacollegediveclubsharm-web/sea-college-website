<?php
/**
 * Italian (`it`) Sharm El Sheikh & South Sinai Dive Sites + 12-Month Red Sea Conditions Guide.
 */
declare(strict_types=1);

return [
    'aeo_answer' => 'Sharm El Sheikh sorge sulla punta meridionale della Penisola del Sinai, dove il Golfo di Suez e il Golfo di Aqaba incontrano il Mar Rosso aperto, dando vita a oltre 30 pareti coralline, immersioni in corrente e relitti storici a soli 5–90 minuti da Al Montazah. La temperatura dell’acqua varia dai 22 °C di febbraio ai 29 °C di agosto, con una visibilità costante tra 20 e 40 metri tutto l’anno. I principianti si immergono in baie riparate come l’House Reef di Sea College ad Al Montazah (2–14 m) e Jackfish Alley, mentre i sub Open Water e Advanced esplorano Shark & Yolanda Reef a Ras Mohammed, i quattro reef dello Stretto di Tiran, SS Dunraven, Million Hope e il mitico relitto della Seconda Guerra Mondiale SS Thistlegorm (16–30 m).',

    'zones' => [
        'all'          => 'Tutti i Siti (10)',
        'local'        => 'House Reef & Locali',
        'tiran'        => 'Stretto di Tiran',
        'ras-mohammed' => 'Parco di Ras Mohammed',
        'wrecks'       => 'Relitti del Mar Rosso',
        'dahab'        => 'Escursione Dahab',
    ],

    'sites' => [
        'house-reef' => [
            'level'   => 'Principianti / Battesimo & Tutti i Livelli',
            'current' => 'Calma / Baia Riparata',
            'name'    => 'House Reef di Al Montazah (Cyrene Sharm)',
            'summary' => 'A pochi passi dalla nostra piscina didattica al Cyrene Sharm: pianoro sabbioso a 3–5 m che degrada dolcemente tra torrioni di corallo coloratissimi, con €0 di tassa parco marino.',
            'marine'  => ['Pesci pagliaccio e anemoni', 'Trigoni a macchie blu', 'Pesci leone', 'Murene', 'Nuvole di anthias arancioni'],
        ],
        'ras-nasrani' => [
            'level'   => 'Open Water / Snorkeling',
            'current' => 'Leggera',
            'name'    => 'Ras Nasrani & Barriere Vicine di Al Montazah',
            'summary' => 'A soli 5 minuti dal nostro pontile ad Al Montazah: grandi gorgonie a ventaglio, tridacne giganti e giardini corallini poco profondi ideali per Check Dive e immersioni pomeridiane.',
            'marine'  => ['Gorgonie giganti', 'Pesci palla', 'Pesci pappagallo', 'Tartarughe verdi', 'Damigelle verdi-azzurre'],
        ],
        'jackson-reef' => [
            'level'   => 'Open Water / Advanced',
            'current' => 'Moderata – Corrente (Drift)',
            'name'    => 'Jackson Reef & Relitto Lara (Stretto di Tiran)',
            'summary' => 'Il reef più settentrionale dello Stretto di Tiran, riconoscibile dal relitto emerso del mercantile Lara. Celebre per le pareti verticali tappezzate di alcionari e coralli di fuoco e gli avvistamenti estivi di squali martello all’alba.',
            'marine'  => ['Squali martello (lug–set all’alba)', 'Tartarughe embricate', 'Carangidi in caccia', 'Pesci bandiera del Mar Rosso'],
        ],
        'thomas-gordon' => [
            'level'   => 'Open Water & Tutti i Livelli',
            'current' => 'Leggera – Moderata',
            'name'    => 'Gordon, Thomas & Woodhouse Reef (Tiran)',
            'summary' => 'Tre celebri barriere al centro del canale tra il Sinai e l’Isola di Tiran: altopiani sabbiosi soleggiati (i bidoni di Gordon), spettacolari canyon profondi (Thomas) ed emozionanti drift wall (Woodhouse).',
            'marine'  => ['Aquile di mare', 'Squali pinna bianca di barriera', 'Pesce Napoleone', 'Tonni e barracuda'],
        ],
        'million-hope' => [
            'level'   => 'Open Water (18 m+)',
            'current' => 'Debole – Moderata',
            'name'    => 'Relitto MV Million Hope (Nabq)',
            'summary' => 'Con i suoi 175 metri di lunghezza, il mercantile Million Hope naufragato nel 1996 a Nabq è il più grande relitto di Sharm El Sheikh — illuminato dalla luce solare e perfetto anche per brevetti Open Water con una gru sommersa accanto allo scafo.',
            'marine'  => ['Banchi di pesci pipistrello', 'Pesci coccodrillo', 'Nudibranchi colorati', 'Pesci vetro', 'Pesci scatola'],
        ],
        'shark-yolanda' => [
            'level'   => 'Open Water / Advanced',
            'current' => 'Drift Moderata – Intensa',
            'name'    => 'Shark & Yolanda Reef (Parco di Ras Mohammed)',
            'summary' => 'Classificato tra le 10 immersioni più belle del pianeta: tuffo nel blu ad Anemone City, volo in corrente lungo la parete verticale da 700 m di Shark Reef e arrivo tra i celebri sanitari e vasche da bagno del relitto cipriota Yolanda (1980).',
            'marine'  => ['Muri di barracuda e lutianidi', 'Murene giganti', 'Pesce Napoleone', 'Squali pinna nera', 'Foreste di corallo molle'],
        ],
        'jackfish-alley' => [
            'level'   => 'Battesimo in Barca / Open Water',
            'current' => 'Calma – Leggera',
            'name'    => 'Jackfish Alley & Laguna dell’Isola Bianca',
            'summary' => 'Grotte passanti inondate di raggi di sole tra 6 e 14 m e un candido canalone sabbioso pattugliato da carangidi e trigoni, proprio accanto alla celebre lingua di sabbia dell’Isola Bianca a Ras Mohammed.',
            'marine'  => ['Grotte di pesci vetro', 'Trigoni a macchie blu', 'Carangidi (Trevally)', 'Pesci balestra titano', 'Anthias'],
        ],
        'dunraven' => [
            'level'   => 'Open Water / Advanced',
            'current' => 'Leggera – Moderata',
            'name'    => 'Relitto SS Dunraven (Beacon Rock, 1876)',
            'summary' => 'Piroscafo britannico d’epoca vittoriana affondato nell’aprile 1876 contro Beacon Rock: giace capovolto in due tronconi incrostati di coralli e gremiti da migliaia di scintillanti pesci vetro.',
            'marine'  => ['Cattedrale di pesci vetro', 'Cernie giganti', 'Pesci leone', 'Pesci pietra', 'Triglie tropicali'],
        ],
        'thistlegorm' => [
            'level'   => 'Advanced + 20 Immersioni Registrate',
            'current' => 'Moderata – Forte',
            'name'    => 'Relitto SS Thistlegorm WWII (Sha’ab Ali)',
            'summary' => 'Il relitto n°1 al mondo: nave da carico armata britannica di 128 m affondata nell’ottobre 1941 con il carico bellico intatto di motociclette BSA, camion Bedford, ali d’aereo, locomotive a vapore e cannoni contraerei.',
            'marine'  => ['Moto BSA e camion WWII', 'Grandi pesci pipistrello', 'Barracuda', 'Pesci coccodrillo', 'Scorfani'],
        ],
        'dahab-blue-hole' => [
            'level'   => 'Advanced Open Water',
            'current' => 'Ingresso Calmo da Riva',
            'name'    => 'Blue Hole, El Bells & Canyon (Dahab)',
            'summary' => 'Avventura di un’intera giornata nel Sinai fino a Dahab: discesa attraverso il suggestivo camino di El Bells lungo una parete blu cobalto fino alla sella corallina del celebre Blue Hole, abbinata alla faglia vulcanica del Canyon.',
            'marine'  => ['Parete verticale nel blu', 'Caverna vulcanica di pesci vetro', 'Pesci unicorno', 'Polpi del reef', 'Relax beduino fronte mare'],
        ],
    ],

    'months' => [
        0  => ['m' => 'Gen', 'suit' => 'Umida 5 mm intera (+ sottomuta)', 'note' => 'Acqua limpida invernale, barriere tranquille e aquile di mare'],
        1  => ['m' => 'Feb', 'suit' => 'Umida 5–7 mm intera',             'note' => 'Mese più fresco in acqua; pomeriggi soleggiati e mare calmo'],
        2  => ['m' => 'Mar', 'suit' => 'Umida 5 mm intera',               'note' => 'Fioritura primaverile di plancton che attira mante e vita macro'],
        3  => ['m' => 'Apr', 'suit' => 'Umida 5 mm intera',               'note' => 'Clima primaverile perfetto per crociere sul relitto Thistlegorm'],
        4  => ['m' => 'Mag', 'suit' => 'Umida 3–5 mm intera',             'note' => 'Mattinate piatte come uno specchio e primi passaggi di squalo balena'],
        5  => ['m' => 'Giu', 'suit' => 'Umida 3 mm o shorty',             'note' => 'Enormi banchi di lutianidi e barracuda in riproduzione a Shark Reef'],
        6  => ['m' => 'Lug', 'suit' => 'Shorty 3 mm / rashguard',         'note' => 'Alta stagione per gli squali martello all’alba a Jackson Reef (Tiran)'],
        7  => ['m' => 'Ago', 'suit' => 'Shorty 3 mm / rashguard',         'note' => 'Acqua più calda dell’anno (29 °C) — top per bambini e notturne'],
        8  => ['m' => 'Set', 'suit' => 'Umida 3 mm intera o shorty',      'note' => 'Mare caldo come una piscina, visibilità 35 m+ e brezza piacevole'],
        9  => ['m' => 'Ott', 'suit' => 'Umida 3–5 mm intera',             'note' => 'Il mese preferito dai sub italiani: acqua a 27 °C e visibilità fino a 40 m'],
        10 => ['m' => 'Nov', 'suit' => 'Umida 5 mm intera',               'note' => 'Equilibrio perfetto a 26 °C tra aria e mare; ideale per corsi PADI'],
        11 => ['m' => 'Dic', 'suit' => 'Umida 5 mm intera',               'note' => 'Fuga al sole d’inverno con pareti coralline dalla nitidezza cristallina'],
    ],

    'faqs' => [
        [
            'q' => 'Qual è il periodo migliore dell’anno per fare immersioni a Sharm El Sheikh?',
            'a' => 'A Sharm El Sheikh si fa sub tutto l’anno! I periodi da settembre a novembre e da aprile a giugno offrono il mix ideale di acqua calda (25–28 °C), aria gradevole (26–32 °C) e visibilità tra 30 e 40 metri. Luglio e agosto regalano l’acqua a 29 °C e gli squali martello all’alba a Tiran, mentre l’inverno garantisce sole e reef pochissimo affollati.',
        ],
        [
            'q' => 'Quali siti di immersione sono più vicini alla base di Sea College ad Al Montazah?',
            'a' => 'Il nostro centro fronte mare al Cyrene Sharm di Al Montazah si affaccia direttamente sullo Stretto di Tiran! L’House Reef è a 0 minuti dalla spiaggia, Ras Nasrani dista 5 minuti, mentre Gordon, Thomas, Woodhouse e Jackson Reef a Tiran si raggiungono in soli 15–25 minuti di barca o motoscafo.',
        ],
        [
            'q' => 'Chi non ha il brevetto può immergersi a Ras Mohammed o all’Isola di Tiran?',
            'a' => 'Sì! I principianti assoluti dai 10 anni in su possono prenotare il Battesimo del Mare in Barca (€70 per 1 immersione o €100 per 2 immersioni tutto incluso) accompagnati 1-a-1 da un istruttore PADI fino a 12 m sui reef riparati di Tiran o Ras Mohammed, oppure iniziare dal nostro House Reef a €30.',
        ],
        [
            'q' => 'Quale livello serve per visitare il relitto SS Thistlegorm?',
            'a' => 'Per immergersi sul Thistlegorm (16–30 m a Sha’ab Ali) sono richiesti il brevetto PADI Advanced Open Water (o equivalente 30 m) e almeno 20 immersioni registrate. I sub Open Water (18 m) possono invece esplorare lo spettacolare relitto Million Hope a Nabq.',
        ],
        [
            'q' => 'Quando si pagano la tassa parco marino (€5/giorno) e il supplemento barca (€10/giorno)?',
            'a' => 'I pacchetti per sub brevettati includono guida, bombole aria 12L, zavorra e transfer hotel. Nei giorni di uscita in barca si aggiungono €10/giorno di Supplemento Barca (comprensivo di pranzo caldo a buffet e bevande) e €5/giorno di Tassa Parco Marino. Le immersioni da riva sul nostro House Reef hanno €0 barca e €0 tassa parco!',
        ],
    ],
];
