<?php
/**
 * French (fr) overlay for Sharm El Sheikh Dive Sites & 12-Month Red Sea Conditions Guide.
 */
declare(strict_types=1);

return [
    'aeo_answer' => 'Charm el-Cheikh (Sharm El Sheikh) occupe la pointe sud de la péninsule du Sinaï, à la rencontre des golfes de Suez et d’Aqaba avec la Mer Rouge, concentrant plus de 30 tombants coralliens, récifs dérivants et épaves historiques à seulement 5 à 90 minutes de navigation d’Al Montazah. La température de l’eau oscille entre 22 °C en février et 29 °C en août avec une visibilité exceptionnelle de 20 à 40 m toute l’année. Les débutants s’initient au calme sur le récif maison de Sea College à Al Montazah (2–14 m) ou à Jackfish Alley, tandis que les plongeurs Open Water et Advanced explorent Shark & Yolanda Reef, les quatre récifs du détroit de Tiran, le SS Dunraven, le Million Hope et la mythique épave de la Seconde Guerre mondiale SS Thistlegorm (16–30 m).',

    'zones' => [
        'all'          => 'Tous les sites (10)',
        'local'        => 'Récif Maison & Locaux',
        'tiran'        => 'Détroit de Tiran',
        'ras-mohammed' => 'Parc de Ras Mohammed',
        'wrecks'       => 'Épaves de Mer Rouge',
        'dahab'        => 'Excursion Dahab',
    ],

    'sites' => [
        'house-reef' => [
            'level'   => 'Débutant / Baptême & Tous niveaux',
            'current' => 'Calme / Abrité',
            'name'    => 'Récif Maison d’Al Montazah (Cyrene Sharm)',
            'summary' => 'À quelques pas de notre piscine au Cyrene Sharm : plateau sablonneux de 3 à 5 m descendant en pente douce vers des patates coralliennes colorées — 0 € de taxe parc marin.',
            'marine'  => ['Poissons-clowns & anémones', 'Raies pastenagues à points bleus', 'Poissons-lions (rascasses)', 'Murènes', 'Nuages d’anthias'],
        ],
        'ras-nasrani' => [
            'level'   => 'Open Water / Snorkeling',
            'current' => 'Léger',
            'name'    => 'Ras Nasrani & Récifs proches d’Al Montazah',
            'summary' => 'À 5 minutes de notre jetée d’Al Montazah : gorgones géantes, bénitiers colorés et jardins de corail peu profonds idéaux pour les plongées de réadaptation et sorties d’après-midi.',
            'marine'  => ['Gorgones géantes', 'Poissons-globes', 'Poissons-perroquets', 'Tortues vertes', 'Chromis bleu-vert'],
        ],
        'jackson-reef' => [
            'level'   => 'Open Water / Advanced',
            'current' => 'Modéré – Dérivant',
            'name'    => 'Jackson Reef & Épave du Lara (Détroit de Tiran)',
            'summary' => 'Récif le plus septentrional du détroit de Tiran, repérable à l’épave échouée du cargo Lara : tombants vertigineux tapissés de corail de feu et alcyonaires, célèbre l’été à l’aube pour ses requins-marteaux.',
            'marine'  => ['Requins-marteaux halicornes (juil.–sept.)', 'Tortues imbriquées', 'Carangues & bancs pélagiques', 'Poissons-cochers de Mer Rouge'],
        ],
        'thomas-gordon' => [
            'level'   => 'Open Water & Tous niveaux',
            'current' => 'Léger à modéré',
            'name'    => 'Gordon, Thomas & Woodhouse Reefs (Tiran)',
            'summary' => 'Trois récifs mythiques au milieu du chenal de Tiran combinant plateau sablonneux ensoleillé (fûts de Gordon), canyon profond spectaculaire (Thomas) et dérive palpitante le long du mur de Woodhouse.',
            'marine'  => ['Raies aigles', 'Requins corail à pointe blanche', 'Labres Napoléon', 'Thons & barracudas'],
        ],
        'million-hope' => [
            'level'   => 'Open Water (dès 18 m)',
            'current' => 'Faible à modéré',
            'name'    => 'Épave du MV Million Hope (Nabq)',
            'summary' => 'Avec ses 175 m de long, le vraquier Million Hope échoué en 1996 à Nabq est la plus vaste épave de Charm el-Cheikh — baignée de lumière naturelle et accessible dès le niveau Open Water.',
            'marine'  => ['Bancs de platax', 'Poissons-crocodiles', 'Nudibranches', 'Poissons de verre', 'Poissons-coffres'],
        ],
        'shark-yolanda' => [
            'level'   => 'Open Water / Advanced',
            'current' => 'Dérive modérée à forte',
            'name'    => 'Shark & Yolanda Reef (Parc de Ras Mohammed)',
            'summary' => 'Classé parmi le top 10 mondial : saut dans le bleu à Anemone City, dérive devant le tombant vertical de 700 m de Shark Reef, et final mythique au milieu des sanitaires et baignoires de l’épave du Yolanda (1980).',
            'marine'  => ['Bancs de barracudas & vivaneaux', 'Murènes géantes', 'Labres Napoléon', 'Requins à pointes noires', 'Murs de coraux mous'],
        ],
        'jackfish-alley' => [
            'level'   => 'Baptême Bateau / Open Water',
            'current' => 'Calme à léger',
            'name'    => 'Jackfish Alley & Lagon de l’Île Blanche',
            'summary' => 'Grottes peu profondes traversées de rayons de soleil entre 6 et 14 m et couloir de sable blanc fréquenté par les carangues et les raies, juste à côté du banc de sable de l’Île Blanche.',
            'marine'  => ['Grottes de poissons de verre', 'Raies à points bleus', 'Carangues', 'Balistes titans', 'Anthias orangés'],
        ],
        'dunraven' => [
            'level'   => 'Open Water / Advanced',
            'current' => 'Léger à modéré',
            'name'    => 'Épave du SS Dunraven (Beacon Rock, 1876)',
            'summary' => 'Navire vapeur-voilier victorien britannique ayant heurté Beacon Rock en avril 1876 ; sa coque retournée couverte de coraux abrite une véritable cathédrale scintillante de milliers de poissons de verre.',
            'marine'  => ['Nuées de poissons de verre', 'Mérous géants', 'Rascasses volantes', 'Poissons-pierres', 'Rougets-barbets'],
        ],
        'thistlegorm' => [
            'level'   => 'Advanced + 20 plongées enregistrées',
            'current' => 'Modéré à fort',
            'name'    => 'Épave du SS Thistlegorm — 2de Guerre Mondiale (Sha’ab Ali)',
            'summary' => 'L’épave n°1 au monde : cargo armé britannique de 128 m coulé en octobre 1941 avec ses motos BSA, camions Bedford, ailes d’avion, locomotives à vapeur et canons antiaériens intacts.',
            'marine'  => ['Motos & camions de 1941', 'Platax géants', 'Barracudas', 'Poissons-crocodiles', 'Rascasses & poissons-scorpions'],
        ],
        'dahab-blue-hole' => [
            'level'   => 'Advanced Open Water',
            'current' => 'Mise à l’eau calme du bord',
            'name'    => 'Blue Hole de Dahab, El Bells & Le Canyon',
            'summary' => 'Excursion d’une journée au nord vers Dahab : descente par la cheminée d’El Bells le long d’un tombant bleu électrique jusqu’à la selle corallienne du célèbre Blue Hole, couplée à la faille volcanique du Canyon.',
            'marine'  => ['Tombant bleu intense', 'Caverne volcanique de poissons de verre', 'Nasons à éperons', 'Poulpes', 'Déjeuner bédouin en bord de mer'],
        ],
    ],

    'months' => [
        0  => ['m' => 'Janv.', 'suit' => 'Intégrale 5 mm (+ souris)', 'note' => 'Eau limpide d’hiver, récifs paisibles & raies aigles'],
        1  => ['m' => 'Févr.', 'suit' => 'Intégrale 5–7 mm', 'note' => 'Eau la plus fraîche de l’année ; après-midis doux et ensoleillés'],
        2  => ['m' => 'Mars',  'suit' => 'Intégrale 5 mm', 'note' => 'Floraison planctonique printanière attirant raies & faune macro'],
        3  => ['m' => 'Avr.',  'suit' => 'Intégrale 5 mm', 'note' => 'Avant-saison idéale : air chaud & traversées vers le Thistlegorm'],
        4  => ['m' => 'Mai',   'suit' => 'Intégrale 3–5 mm', 'note' => 'Matinées mer d’huile & premières observations de requins-baleines'],
        5  => ['m' => 'Juin',  'suit' => '3 mm ou shorty', 'note' => 'Immenses bancs de vivaneaux et barracudas à Shark Reef'],
        6  => ['m' => 'Juil.', 'suit' => 'Shorty 3 mm / lycra', 'note' => 'Pleine saison des requins-marteaux à l’aube sur Jackson Reef (Tiran)'],
        7  => ['m' => 'Août',  'suit' => 'Shorty 3 mm / lycra', 'note' => 'Eau la plus chaude (29 °C) — parfait pour les enfants & plongées de nuit'],
        8  => ['m' => 'Sept.', 'suit' => 'Intégrale 3 mm ou shorty', 'note' => 'Mer chaude comme un bain + brises agréables de fin d’été'],
        9  => ['m' => 'Oct.',  'suit' => 'Intégrale 3–5 mm', 'note' => 'Mois favori des plongeurs : eau à 27 °C & visibilité de 35 m+'],
        10 => ['m' => 'Nov.',  'suit' => 'Intégrale 5 mm', 'note' => 'Équilibre parfait 26 °C air et eau ; idéal pour les cours PADI'],
        11 => ['m' => 'Déc.',  'suit' => 'Intégrale 5 mm', 'note' => 'Parenthèse ensoleillée d’hiver avec tombants cristallins'],
    ],

    'faqs' => [
        [
            'q' => 'Quelle est la meilleure période pour plonger à Charm el-Cheikh ?',
            'a' => 'On plonge à Charm el-Cheikh 12 mois sur 12. Les périodes de septembre à novembre et d’avril à juin offrent le compromis idéal avec une eau entre 25 et 28 °C, un air agréable (26–32 °C) et 30 à 40 m de visibilité. Juillet et août affichent l’eau la plus chaude (29 °C) et la saison des requins-marteaux à Tiran.',
        ],
        [
            'q' => 'Quels sont les sites de plongée les plus proches de Sea College Dive Club à Al Montazah ?',
            'a' => 'Notre base en front de mer au Cyrene Sharm à Al Montazah fait directement face au détroit de Tiran ! Notre récif maison est à 0 minute, Ras Nasrani à 5 minutes, et les quatre récifs de Tiran (Gordon, Thomas, Woodhouse et Jackson) à seulement 15–25 minutes en bateau ou speedboat.',
        ],
        [
            'q' => 'Un débutant sans brevet peut-il plonger à Ras Mohammed ou Tiran ?',
            'a' => 'Oui ! Les débutants non certifiés peuvent réserver un baptême Discover Scuba Diving en bateau (70 € pour 1 plongée ou 100 € pour 2 plongées tout compris) encadré individuellement par un instructeur PADI jusqu’à 12 m sur les récifs abrités de Tiran ou Ras Mohammed, ou débuter dès 30 € sur notre House Reef.',
        ],
        [
            'q' => 'Quel brevet faut-il pour plonger sur l’épave du SS Thistlegorm ?',
            'a' => 'Pour plonger sur le SS Thistlegorm (16–30 m à Sha’ab Ali), il faut être titulaire du PADI Advanced Open Water (ou Niveau 2 / équivalent 30 m) et justifier d’au moins 20 plongées enregistrées. Les plongeurs Open Water (18 m) peuvent quant à eux explorer l’impressionnante épave du Million Hope à Nabq.',
        ],
        [
            'q' => 'Quand s’appliquent la taxe parc marin (5 €/jour) et le supplément bateau (10 €/jour) ?',
            'a' => 'Les forfaits plongeurs certifiés incluent le guide, les blocs 12L air, les plombs et les transferts hôtel. Les jours de sortie bateau s’ajoutent 10 €/jour de supplément bateau (incluant déjeuner buffet chaud et boissons à bord) et 5 €/jour de taxe parc marin. Les plongées sur notre récif maison d’Al Montazah sont exonérées des deux (0 € bateau et 0 € parc).',
        ],
    ],
];
