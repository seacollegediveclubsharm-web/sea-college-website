<?php
/**
 * French (fr) catalog overlay: 4 categories, 16 groups, 32 durations & all 80 items.
 */
declare(strict_types=1);

return [
    'categories' => [
        'dive-packages' => [
            'label' => 'Forfaits Plongée',
            'notes' => [
                'Les forfaits plongée sont nominatifs (tarif par personne) et ne peuvent pas être partagés entre plusieurs plongeurs.',
                'Inclus : bloc air 12L, ceinture et plombs, guide de plongée PADI, transferts hôtel à Charm el-Cheikh, boissons en demi-journée ; déjeuner buffet et boissons sur les sorties journée complète.',
                'Non inclus : supplément sortie bateau (10 €/jour), taxe parc marin (5 €/jour, toutes zones sauf récif maison), Nitrox, assurance, location d’équipement, plongées de nuit/aube et suppléments spéciaux.',
                'Un guide privé est obligatoire pour les enfants de 10–11 ans et pour les titulaires PADI Scuba Diver (limite 12 m).',
                'Réservation en ligne jusqu’à 72 h avant l’activité (48 h pour Million Hope, Zodiac et Dahab).',
            ],
        ],
        'padi-courses' => [
            'label' => 'Cours PADI',
            'notes' => [
                'La location complète de l’équipement de plongée est incluse dans le prix de tous les cours.',
                'Manuel PADI & frais de certification (85 € par cours) non inclus — à l’exception du programme Discover Scuba Diving.',
                'Les suppléments journaliers bateau (10 €/jour) et parc marin (5 €/jour) lors des sorties en mer ne sont pas inclus dans le prix du cours.',
                'Réservation en ligne jusqu’à 72 heures avant le début de la formation.',
            ],
        ],
        'offers' => [
            'label' => 'Packs de Cours',
            'notes' => [
                'Les packs incluent les taxes, tous les cours mentionnés, la location complète de l’équipement et l’assurance plongée.',
                'Manuel PADI & frais de certification (85 € par cours) non inclus.',
            ],
        ],
        'snorkeling' => [
            'label' => 'Snorkeling & Bateaux',
            'notes' => [
                'Les excursions snorkeling incluent masque, gilet de sauvetage, transferts hôtel et taxes de parc marin ; les croisières journée complète incluent déjeuner buffet et boissons fraîches.',
                'Les prestations réservées directement sur place sont facturées au tarif standard.',
                'Réservation en ligne jusqu’à 72 heures avant l’excursion.',
            ],
        ],
    ],

    'groups' => [
        'intro-dives'         => 'Plongée Débutants (Baptêmes de plongée)',
        'refresh-dives'       => 'Plongées de Réadaptation',
        'certified-packages'  => 'Forfaits Plongeurs Certifiés — Bateau ou du Bord',
        'wreck-special-dives' => 'Épaves, Zodiac, Plongée de Nuit & Excursion Dahab',
        'daily-fees'          => 'Frais Journaliers & Guide Privé',
        'equipment-rental'    => 'Location d’Équipement & Blocs (par jour)',
        'diving-insurance'    => 'Assurance Plongée Courte Durée',
        'kids-courses'        => 'Cours de Plongée pour Enfants',
        'beginner-courses'    => 'Formations PADI pour Débutants',
        'advanced-rescue'     => 'Perfectionnement & Sauvetage',
        'specialty-courses'   => 'Spécialités PADI',
        'go-pro'              => 'Devenir Professionnel (Go Pro)',
        'saver-bundles'       => 'Économisez avec un Pack Combiné',
        'snorkeling-trips'    => 'Excursions Snorkeling Guidées',
        'private-speedboats'  => 'Snorkeling en Speedboat Privé',
        'private-charters'    => 'Location de Bateau & Yacht Privé (journée complète)',
    ],

    'durations' => [
        '1h'          => '1 h',
        '1.5–2h'      => '1 h 30 – 2 h',
        '2h'          => '2 h',
        '2–3h'        => '2 – 3 h',
        '3h'          => '3 h',
        '3–4h'        => '3 – 4 h',
        '5–6h'        => '5 – 6 h',
        '7–8h'        => '7 – 8 h',
        '8h'          => '8 h',
        'Half day'    => 'Demi-journée',
        'Full day'    => 'Journée complète',
        '1 day'       => '1 jour',
        '1–2 days'    => '1 à 2 jours',
        '1.5–2 days'  => '1,5 à 2 jours',
        '2 days'      => '2 jours',
        '2–3 days'    => '2 à 3 jours',
        '2.5–3 days'  => '2,5 à 3 jours',
        '3 days'      => '3 jours',
        '3–3.5 days'  => '3 à 3,5 jours',
        '3–4 days'    => '3 à 4 jours',
        '4 days'      => '4 jours',
        '4–5 days'    => '4 à 5 jours',
        '5 days'      => '5 jours',
        '6 days'      => '6 jours',
        '6–7 days'    => '6 à 7 jours',
        '7 days'      => '7 jours',
        '10 days'     => '10 jours',
        '14 days'     => '14 jours',
        '3 evenings'  => '3 soirées',
        '4–6 weeks'   => '4 à 6 semaines',
        'Per dive'    => 'Par plongée',
        'Per day'     => 'Par jour',
    ],

    'items' => [
        // Intro Dives
        'dsd-quick-reef' => ['name' => 'Baptême Découverte Rapide — Récif Maison (House Reef)', 'desc' => 'Briefing + 1 plongée départ plage avec moniteur (10 min). Équipement complet inclus.'],
        'dsd-2-reef'     => ['name' => 'Discover Scuba Diving (2 Baptêmes) — Récif Maison', 'desc' => 'Briefing + 2 plongées découvertes départ plage (15 min chacune). Équipement complet inclus.'],
        'dsd-quick-boat' => ['name' => 'Baptême Découverte Rapide — En Bateau', 'desc' => 'Briefing + 1 plongée en bateau (10 min). Déjeuner buffet, boissons, transferts, taxes parc & équipement inclus.'],
        'dsd-2-boat'     => ['name' => 'Discover Scuba Diving (2 Baptêmes) — En Bateau', 'desc' => 'Briefing + 2 plongées en bateau (15 min chacune). Déjeuner buffet, boissons, transferts, taxes parc & équipement inclus.'],

        // Refresh Dives
        'check-dive'   => ['name' => 'Plongée de Réadaptation (Check Dive)', 'desc' => 'Obligatoire après plus de 6 mois sans plonger. Briefing + exercices et plongée sur le récif maison.'],
        'scuba-review' => ['name' => 'Remise à Niveau Complète (Scuba Review)', 'desc' => 'Obligatoire après plus d’un an sans plonger. Séance piscine + plongée guidée en mer sur le récif maison.'],

        // Certified Dive Packages
        'dive-1'     => ['name' => '1 Plongée Guidée du Bord (Shore Dive)', 'desc' => 'Plongée guidée sur notre récif maison d’Al Montazah avec bloc 12L air, plombs & transferts hôtel à Charm el-Cheikh.'],
        'dive-2'     => ['name' => '2 Plongées en Bateau ou du Bord', 'desc' => 'Guide PADI, blocs 12L, plombs & transferts hôtel (+10 € bateau & +5 € parc marin les jours bateau).'],
        'dive-3'     => ['name' => 'Forfait 3 Plongées en Bateau ou du Bord', 'desc' => '35 € par plongée — guide PADI, blocs 12L, plombs & transferts hôtel à Charm el-Cheikh.'],
        'dive-4'     => ['name' => 'Forfait 4 Plongées en Bateau ou du Bord', 'desc' => 'Forfait 2 jours pour plongeurs certifiés (35 €/plongée) avec guide, blocs 12L, plombs & transferts.'],
        'dive-5'     => ['name' => 'Forfait 5 Plongées en Bateau ou du Bord', 'desc' => '35 € par plongée sur Ras Mohammed, Tiran et récifs locaux — guide, blocs 12L, plombs & transferts.'],
        'dive-6'     => ['name' => 'Forfait 6 Plongées en Bateau ou du Bord', 'desc' => 'Forfait 3 jours en Mer Rouge (35 €/plongée) couvrant Ras Mohammed, le détroit de Tiran et les récifs locaux.'],
        'dive-7'     => ['name' => 'Forfait 7 Plongées en Bateau ou du Bord', 'desc' => 'Forfait flexible 3–4 jours pour plongeurs certifiés (35 €/plongée) avec guide, blocs, plombs & transferts.'],
        'dive-8'     => ['name' => 'Forfait 8 Plongées en Bateau ou du Bord', 'desc' => 'Forfait 4 jours de plongée (35 €/plongée) avec guide PADI, blocs 12L, plombs & transferts hôtel.'],
        'dive-9'     => ['name' => 'Forfait 9 Plongées en Bateau ou du Bord', 'desc' => 'Forfait 4–5 jours pour plongeurs certifiés (35 €/plongée) incluant guide, blocs 12L, plombs & transferts.'],
        'dive-10'    => ['name' => 'Forfait 10 Plongées en Bateau ou du Bord', 'desc' => 'Semaine complète 5 jours en Mer Rouge (35 €/plongée) — chaque plongée au-delà de la 10e passe à 30 € !'],
        'dive-extra' => ['name' => 'Plongée Supplémentaire au-delà de la 10e', 'desc' => 'Tarif fidélité réduit à 30 € par plongée supplémentaire ajoutée à un forfait 10 plongées.'],

        // Wreck, Zodiac, Night & Dahab Supplements
        'sup-night'        => ['name' => 'Plongée de Nuit ou à l’Aube', 'desc' => 'Supplément sur 1 plongée de votre forfait pour observer la faune nocturne ou les pélagiques au lever du jour.'],
        'sup-thistlegorm'  => ['name' => 'Épave du SS Thistlegorm', 'desc' => 'Mythique épave de la 2de Guerre mondiale. Min. Advanced Open Water + 20 plongées. Réserver 72 h à l’avance.'],
        'sup-dunraven'     => ['name' => 'Épave du SS Dunraven', 'desc' => 'Navire vapeur-voilier britannique de 1876 près de Beacon Rock. Réserver 72 h à l’avance.'],
        'sup-millionhope'  => ['name' => 'Épave du Million Hope', 'desc' => 'Plus grande épave de Charm el-Cheikh à Nabq (174 m). Min. 4 plongeurs. Réserver 48 h à l’avance.'],
        'sup-zodiac'       => ['name' => 'Plongée en Zodiac (Semi-rigide)', 'desc' => 'Dépose rapide en semi-rigide sur les tombants dérivants de Tiran. Min. 4 plongeurs. Réserver 48 h à l’avance.'],
        'sup-zodiac-early' => ['name' => 'Zodiac à l’Aube (Requins-Marteaux / Million Hope)', 'desc' => 'Premiers à l’eau dès l’aube pour les requins-marteaux à Tiran ou le Million Hope. Min. 4 plongeurs, 48 h avant.'],
        'sup-dahab'        => ['name' => 'Excursion Plongée à Dahab (Blue Hole & Canyon)', 'desc' => 'Journée complète depuis Charm el-Cheikh vers les sites mythiques de Dahab. Min. 4 plongeurs, 48 h avant.'],

        // Daily Fees & Private Guide
        'fee-boat'      => ['name' => 'Supplément Sortie Bateau', 'desc' => 'Supplément journalier par personne les jours de bateau (inclut déjeuner buffet chaud & boissons en journée complète).'],
        'fee-park'      => ['name' => 'Taxe Parc Marin', 'desc' => 'Redevance environnementale par personne et par jour pour toutes les réserves marines (sauf récif maison).'],
        'private-guide' => ['name' => 'Guide de Plongée Privé', 'desc' => 'Guide PADI dédié en individuel ou groupe privé à la journée (obligatoire pour les 10–11 ans & PADI Scuba Divers).'],

        // Equipment & Tank Rental
        'eq-full'     => ['name' => 'Pack Équipement Complet', 'desc' => 'Gilet stabilisateur (BCD), détendeur, combinaison, masque & palmes pour 2–3 plongées/jour (hors lampe/ordinateur).'],
        'eq-mask'     => ['name' => 'Masque de plongée', 'desc' => 'Location journalière d’un masque de plongée silicone en verre trempé.'],
        'eq-fins'     => ['name' => 'Palmes de plongée', 'desc' => 'Location journalière de palmes chaussantes ou réglables toutes pointures.'],
        'eq-bcd'      => ['name' => 'Gilet stabilisateur (BCD)', 'desc' => 'Location journalière d’un gilet stabilisateur révisé (tailles XS à XXL).'],
        'eq-reg'      => ['name' => 'Détendeur complet', 'desc' => 'Location journalière d’un détendeur révisé avec octopus de secours et manomètre.'],
        'eq-wetsuit'  => ['name' => 'Combinaison néoprène', 'desc' => 'Location journalière d’un shorty 3 mm ou d’une combinaison intégrale 5 mm Mer Rouge.'],
        'eq-boots'    => ['name' => 'Bottillons néoprène', 'desc' => 'Location journalière de bottillons néoprène pour mises à l’eau du bord et palmes réglables.'],
        'eq-computer' => ['name' => 'Ordinateur de plongée', 'desc' => 'Location journalière d’un ordinateur poignet compatible Air et Nitrox.'],
        'eq-torch'    => ['name' => 'Lampe / Phare sous-marin', 'desc' => 'Location journalière d’une lampe LED étanche rechargeable pour plongées de nuit et épaves.'],
        'eq-15air'    => ['name' => 'Bloc 15L — Air', 'desc' => 'Supplément journalier pour bouteille aluminium grand volume 15 litres à l’air.'],
        'eq-15nx'     => ['name' => 'Bloc 15L — Nitrox', 'desc' => 'Bouteille 15 litres Air Enrichi Nitrox pour plongeurs certifiés Nitrox.'],
        'eq-12nx'     => ['name' => 'Bloc 12L — Nitrox', 'desc' => 'Gonflage bouteille 12 litres Air Enrichi Nitrox (EANx32) pour plongeurs certifiés Nitrox.'],
        'eq-sm-tank'  => ['name' => 'Bloc 12L — Air Sidemount', 'desc' => 'Bouteille aluminium 12L gréée pour configuration latérale Sidemount (par jour).'],
        'eq-sm-full'  => ['name' => 'Équipement Sidemount Complet', 'desc' => 'Harnais/wing Sidemount complet, double détendeurs et kit de gréement à la journée.'],

        // Short-Term Diving Insurance
        'ins-3'  => ['name' => 'Assurance Plongée — 3 jours', 'desc' => 'Couverture caisson hyperbare et frais médicaux de plongée 3 jours à Charm el-Cheikh (15 € par personne).'],
        'ins-7'  => ['name' => 'Assurance Plongée — 7 jours', 'desc' => 'Couverture caisson hyperbare et frais médicaux de plongée 7 jours à Charm el-Cheikh (25 € par personne).'],
        'ins-10' => ['name' => 'Assurance Plongée — 10 jours', 'desc' => 'Couverture caisson hyperbare et frais médicaux de plongée 10 jours à Charm el-Cheikh (35 € par personne).'],
        'ins-14' => ['name' => 'Assurance Plongée — 14 jours', 'desc' => 'Couverture caisson hyperbare et frais médicaux de plongée 14 jours à Charm el-Cheikh (45 € par personne).'],

        // Courses for Children
        'bubblemaker' => ['name' => 'PADI Bubblemaker (Enfants)', 'desc' => 'Dès 8 ans. 2 à 3 h en piscine peu profonde (< 2 m) avec un instructeur PADI. Équipement enfant inclus.'],
        'seal-team'   => ['name' => 'PADI Seal Team (Enfants)', 'desc' => 'Dès 8 ans. 1 journée ludique de missions sous-marines AquaMissions 1 à 5 en piscine avec équipement complet.'],

        // Courses for Beginners
        'dsd'         => ['name' => 'PADI Discover Scuba Diving', 'desc' => 'Dès 10 ans. Initiation en piscine + plongée guidée en Mer Rouge. Équipement, livret et attestation PADI inclus !'],
        'scuba-diver' => ['name' => 'PADI Scuba Diver', 'desc' => 'Dès 10 ans. 3 séances piscine + 2 plongées en mer ; brevet à vie jusqu’à 12 m encadré par un pro PADI.'],
        'upgrade-owd' => ['name' => 'Passerelle PADI Scuba Diver → Open Water', 'desc' => 'Complétez 2 séances piscine, 2 plongées mer et la théorie finale pour obtenir votre brevet autonome Open Water 18 m.'],
        'owd'         => ['name' => 'PADI Open Water Diver', 'desc' => 'Dès 10 ans. Le brevet de plongée le plus célèbre au monde : 5 séances piscine + 4 plongées en Mer Rouge jusqu’à 18 m.'],

        // Advanced & Rescue
        'aowd'       => ['name' => 'PADI Advanced Open Water Diver', 'desc' => 'Dès 12 ans. 5 plongées Aventure (dont Profonde & Orientation) vous certifiant jusqu’à 30 mètres.'],
        'aowd-green' => ['name' => 'Advanced Open Water « Green » (AOWD + Nitrox)', 'desc' => '5 plongées Aventure (jusqu’à 30 m) combinées avec la spécialité PADI Air Enrichi Nitrox (atelier sec).'],
        'efr'        => ['name' => 'Emergency First Response (EFR — Premiers Secours)', 'desc' => 'RCP, premiers secours, défibrillateur (DAE) et soins aux enfants. Ouvert aux plongeurs comme aux non-plongeurs.'],
        'rescue'     => ['name' => 'PADI Rescue Diver (Plongeur Sauveteur)', 'desc' => 'Dès 12 ans. Autosauvetage, prévention du stress, gestion des urgences et scénarios de sauvetage en mer.'],

        // Specialty Courses
        'nitrox'    => ['name' => 'PADI Air Enrichi Nitrox (Enriched Air)', 'desc' => 'Dès 12 ans. Prolongez vos temps sans palier ; analyse d’O₂ des blocs et réglage d’ordinateur Nitrox.'],
        'spec-2'    => ['name' => 'Spécialité PADI 2 Plongées', 'desc' => 'Au choix : Maîtrise de la Flottabilité (PPB), Plongée Dérivante (Drift), Bateau, Naturaliste ou Photo Sous-Marine.'],
        'spec-3'    => ['name' => 'Spécialité 3 Plongées / Orientation Sous-Marine', 'desc' => 'Maîtrisez la navigation au compas, les repères naturels et l’estimation des distances en 3 plongées mer.'],
        'deep'      => ['name' => 'PADI Deep Diver (Plongée Profonde 40 m)', 'desc' => 'Dès 15 ans. 4 plongées profondes jusqu’à 40 m le long des tombants vertigineux de Charm el-Cheikh.'],
        'wreck'     => ['name' => 'PADI Wreck Diver (Spécialité Épaves)', 'desc' => 'Dès 15 ans. 4 plongées sur épaves : cartographie, maniement du fil d’Ariane et pénétration sécurisée en Mer Rouge.'],
        'night'     => ['name' => 'PADI Night Diver (Plongée de Nuit)', 'desc' => 'Dès 12 ans. 3 plongées nocturnes guidées : signaux lumineux, orientation de nuit et vie récifale nocturne.'],
        'sidemount' => ['name' => 'PADI Sidemount Diver (Montage Latéral)', 'desc' => 'Dès 15 ans. 1 atelier réglage piscine + 3 plongées mer pour un trim horizontal parfait et une double sécurité gaz.'],
        'adaptive'  => ['name' => 'PADI Adaptive Support Diver', 'desc' => 'Dès 15 ans. 1 atelier piscine + 1 atelier mer sur les techniques d’accompagnement adapté de binômes en situation de handicap.'],

        // Go Pro
        'divemaster' => ['name' => 'Programme PADI Divemaster (Stage Pro)', 'desc' => 'Dès 18 ans, 40 plongées, AOWD + Rescue + EFR. 4–6 semaines avec transferts aéroport/quotidiens, petit-déjeuner & déjeuner, matériel, assurance & mentorat Course Director.'],

        // Course Bundles
        'bundle-stars' => ['name' => 'Pack Stars — Open Water + Nitrox', 'desc' => 'Économisez 50 €. Inclut les 2 cours PADI, la location complète d’équipement, l’assurance plongée et les taxes.'],
        'bundle-super' => ['name' => 'Pack Super — Advanced Open Water + Nitrox', 'desc' => 'Économisez 75 €. Certifie les plongeurs Open Water jusqu’à 30 m + Nitrox avec équipement complet et assurance inclus.'],
        'bundle-mega'  => ['name' => 'Pack Mega — Open Water + Advanced Open Water', 'desc' => 'Économisez 90 €. Passez de grand débutant à plongeur Advanced 30 m en un seul séjour — équipement & assurance inclus.'],
        'bundle-heros' => ['name' => 'Pack Heros — Open Water + Advanced + Nitrox', 'desc' => 'Économisez 120 €. Le triple brevet complet (jusqu’à 30 m + Nitrox) avec équipement intégral, assurance & taxes inclus.'],

        // Snorkeling Trips
        'snork-ras-adult'   => ['name' => 'Île Blanche & Ras Mohammed en Bateau — Adulte', 'desc' => 'Croisière yacht journée complète : masque, gilet, déjeuner buffet, boissons fraîches, transferts hôtel & taxe parc marin inclus.'],
        'snork-ras-child'   => ['name' => 'Île Blanche & Ras Mohammed en Bateau — Enfant (5–12 ans)', 'desc' => 'Billet enfant (5 à 12 ans) croisière journée complète avec gilet enfant, masque, déjeuner, boissons, transferts & taxe parc.'],
        'snork-beach-day'   => ['name' => 'Snorkeling Guidé depuis la Plage — Journée (House Reef)', 'desc' => 'Randonnée palmée guidée sur notre récif corallien (min. 6 pers.). Masque, gilet, transferts & frais inclus.'],
        'snork-beach-night' => ['name' => 'Snorkeling de Nuit depuis la Plage (House Reef)', 'desc' => 'Exploration nocturne guidée du récif (min. 6 pers.) incluant lampe étanche LED, masque, gilet & transferts hôtel.'],
        'snork-ras-land'    => ['name' => 'Parc National de Ras Mohammed & Snorkeling en Bus', 'desc' => 'Excursion demi-journée : Porte d’Allah, Mangroves, faille sismique, Lac Magique & snorkeling du bord (min. 6 pers.).'],

        // Private Speedboats
        'speed-1h' => ['name' => 'Speedboat Privé 1 Heure — Récifs Locaux', 'desc' => 'Tarif bateau privé jusqu’à 3 passagers (max 5 ; 4e & 5e +50 €/pers.). Masques, gilets & transferts hôtel inclus.'],
        'speed-2h' => ['name' => 'Speedboat Privé 2 Heures — Île de Tiran', 'desc' => 'Tarif bateau privé pour 2 passagers vers Tiran (max 5 ; pers. suppl. +75 €). Masques, gilets & transferts inclus.'],
        'speed-3h' => ['name' => 'Speedboat Privé 3 Heures — Île de Tiran', 'desc' => 'Privatisation longue 3 h des lagons et récifs de Tiran pour 2 passagers (max 5 ; pers. suppl. +100 €).'],

        // Private Boat Charters
        'private-1-5'   => ['name' => 'Bateau Privé Journée Complète — 1 à 5 personnes', 'desc' => 'Privatisation complète d’un bateau à la journée pour couples ou petites familles (snorkeling & plongée en option).'],
        'private-6-10'  => ['name' => 'Bateau Privé Journée Complète — 6 à 10 personnes', 'desc' => 'Notre yacht privé le plus demandé pour familles & amis — Ras Mohammed ou Tiran à votre propre rythme.'],
        'private-11-15' => ['name' => 'Bateau Privé Journée Complète — 11 à 15 personnes', 'desc' => 'Grand yacht motorisé privé en Mer Rouge pour groupes d’amis, anniversaires et vacances multifamiliales.'],
        'private-16-20' => ['name' => 'Bateau Privé Journée Complète — 16 à 20 personnes', 'desc' => 'Affrètement privé journée complète pour clubs de plongée, grandes réunions de famille et groupes VIP jusqu’à 20 personnes.'],
    ],
];
