<?php
/**
 * French (fr) group-aware SEO/AEO template generator — Part B (courses, snorkeling & boats).
 */
declare(strict_types=1);

return static function (array $it, array $seo, string $id, string $gid, string $name, string $desc, string $dur, $price, string $pStr): array {
    switch ($gid) {
        case 'beginner-courses':
        case 'advanced-rescue':
        case 'specialty-courses':
            $isSpec2 = ($id === 'spec-2');
            $isSpec3 = ($id === 'spec-3');
            $isAdapt = ($id === 'adaptive');
            return [
                'h1'         => $name . ' à Charm el-Cheikh — Certification Officielle PADI (' . $pStr . ')',
                'meta_title' => $name . ' Charm el-Cheikh (Sharm El Sheikh) | ' . $pStr . ' | PADI 5★',
                'meta_desc'  => 'Passez votre ' . $name . ' à Charm el-Cheikh (Sharm El Sheikh) pour ' . $pStr . ' (' . $dur . ', équipement complet inclus) chez Sea College PADI 5★ !',
                'aeo_answer' => 'La formation officielle ' . $name . ' chez Sea College Dive Club (Centre PADI 5 Étoiles #26104236 à Charm el-Cheikh) coûte ' . $pStr . ' (durée ' . $dur . ', équipement complet de plongée inclus ; +85 € manuel officiel & carte de certification PADI). ' . ($isSpec2 ? 'Vous choisissez votre spécialité à 2 plongées parmi Maîtrise de la flottabilité (PPB), Plongée dérivante (Drift), Bateau (Boat), Naturaliste sous-marin ou Photographie numérique.' : ($isSpec3 ? 'En 3 plongées dédiées (ex. Orientation sous-marine / Underwater Navigator), vous maîtrisez compas, repères naturels et retour précis au mouillage.' : 'En une demi-journée (1 atelier piscine + 1 atelier mer, dès 15 ans, prérequis OWD + EFR < 24 mois), le brevet PADI Adaptive Support Diver vous apprend à accompagner avec empathie et sécurité des plongeurs en situation de handicap physique ou cognitif.')),
                'overview'   => [
                    $isAdapt
                        ? 'La plongée sous-marine offre une liberté d’apesanteur extraordinaire aux personnes à mobilité réduite ou neuro-atypiques : ce cours inspirant vous transmet les techniques concrètes d’adaptation d’équipement, de mise à l’eau et de communication sous-marine.'
                        : 'Se former dans les eaux chaudes et translucides de Charm el-Cheikh (visibilité 25–35 m) transforme chaque plongée technique en véritable exploration récifale loin du froid des carrières européennes !',
                    'Chaque spécialité PADI validée chez Sea College inclut le prêt complet de l’équipement et compte directement vers le prestigieux grade PADI Master Scuba Diver (5 spécialités + Rescue + 50 plongées). ' . $desc,
                ],
                'specs'      => [
                    ['Tarif du cours', $pStr . ' par personne (équipement de plongée complet inclus)'],
                    ['Frais PADI', '+85 € manuel officiel / eLearning & carte eCard PADI'],
                    ['Durée du cours', $dur],
                    ['Plongées incluses', $isSpec2 ? '2 plongées en mer ouverte avec instructeur PADI Specialty' : ($isSpec3 ? '3 plongées en mer ouverte avec instructeur PADI Specialty' : '1 atelier milieu protégé (piscine) + 1 atelier mer sur récif maison')],
                    ['Prérequis', $isAdapt ? '15 ans min. · PADI OWD (ou Scuba Diver) + EFR de moins de 24 mois' : 'PADI Open Water Diver (ou Junior OWD dès 10–12 ans selon spécialité)'],
                ],
                'steps'      => [
                    ['Théorie ciblée & briefing personnalisé', 'Étude claire des concepts clés avec votre instructeur PADI Specialty autour d’ateliers concrets sans examen rébarbatif.'],
                    ['Mise en pratique progressive sous l’eau', $isAdapt ? 'Mises en situation réelles (simulation cécité, mobilité réduite, transferts) en piscine puis sur notre récif maison.' : 'Réalisation de vos plongées de spécialité sur les récifs de Ras Nasrani, Tiran ou Ras Mohammed avec coaching individuel.'],
                    ['Débriefing & délivrance de votre eCard PADI', 'Conseils personnalisés pour la suite de votre parcours et émission rapide de votre carte numérique officielle dans l’application PADI !'],
                ],
                'included'   => [
                    'Enseignement personnalisé par un PADI Specialty Instructor certifié',
                    'Location complète de l’équipement de plongée pendant toute la durée du cours',
                    'Bouteilles 12 L air et plombs pour toutes les plongées du cours',
                    'Transferts hôtel aller-retour gratuits à Charm el-Cheikh',
                ],
                'excluded'   => [
                    'Manuel officiel / eLearning & frais de certification eCard PADI (85 €)',
                    'Supplément bateau (10 €/jour avec buffet) et taxe parc (5 €/jour) uniquement les jours en bateau',
                    'Assurance plongée et caméra personnelle (si option Digital Underwater Photographer)',
                ],
                'ideal_for'  => [
                    $isSpec2 ? 'Plongeurs OWD/AOWD voulant diviser leur consommation d’air grâce à Peak Performance Buoyancy (PPB) ou maîtriser les dérivantes de Tiran' : ($isSpec3 ? 'Plongeurs souhaitant ne plus jamais dépendre aveuglément des autres pour s’orienter au compas sous l’eau' : 'Binômes, proches, plongeurs Rescue et futurs Divemasters voulant rendre la plongée accessible à tous'),
                    'Plongeurs en route vers le titre d’élite PADI Master Scuba Diver',
                    'Vacanciers voulant valider un brevet officiel en 1 à 2 jours seulement',
                ],
                'faqs'       => [
                    ['q' => 'L’équipement complet est-il inclus dans le tarif de ' . $pStr . ' ?', 'a' => 'Oui, à 100 % ! Chez Sea College, l’équipement de plongée est inclus dans tous nos cours PADI ; seuls le manuel et la certification officielle PADI (+85 €) ainsi que les éventuels suppléments bateau/parc s’ajoutent.'],
                    ['q' => $isSpec2 ? 'Quelle spécialité 2 plongées recommandez-vous le plus à Charm el-Cheikh ?' : 'Ma plongée Adventure du cours Advanced compte-t-elle comme Plongée 1 ?', 'a' => $isSpec2 ? 'Maîtrise de la flottabilité (PPB) et Plongée dérivante (Drift Diver) sont nos deux favorites absolues pour planer sans effort sur les tombants de Tiran et Ras Mohammed !' : 'Oui ! Si vous avez déjà validé la plongée Adventure correspondante lors de votre Advanced Open Water, elle peut être créditée vers votre spécialité selon les standards PADI.'],
                    ['q' => 'Puis-je combiner cette spécialité avec un forfait exploration ?', 'a' => 'Tout à fait, nous intégrons vos plongées de spécialité directement dans votre programme bateau de la semaine !'],
                ],
            ];

        case 'snorkeling-trips':
            $isChild = ($id === 'snork-ras-child');
            $isNight = ($id === 'snork-beach-night');
            $isLand  = ($id === 'snork-ras-land');
            return [
                'h1'         => $name . ' à Charm el-Cheikh — Mer Rouge (' . $pStr . ')',
                'meta_title' => $name . ' Charm el-Cheikh (Sharm El Sheikh) | ' . $pStr . ' | Sea College',
                'meta_desc'  => 'Réservez ' . $name . ' à Charm el-Cheikh (Sharm El Sheikh) pour ' . $pStr . ' (' . $dur . ') : récifs coralliens de Mer Rouge, guide pro & transferts inclus !',
                'aeo_answer' => $name . ' chez Sea College Dive Club à Charm el-Cheikh (Sharm El Sheikh) coûte ' . $pStr . ' par personne (durée ' . $dur . ($isChild ? ', tarif enfant de 5 à 12 ans en formule bateau 100 % tout compris vers Ras Mohammed & l’Île Blanche' : ', départ dès 6 participants') . '). ' . $desc,
                'overview'   => [
                    $isChild
                        ? 'Conçu spécialement pour les jeunes explorateurs de 5 à 12 ans (les moins de 5 ans embarquent gratuitement !), ce tarif enfant tout compris offre la même journée de rêve en yacht vers l’Île Blanche et Ras Mohammed que les adultes, avec petits masques et gilets de sécurité adaptés.'
                        : ($isLand
                            ? 'Découvrez la beauté sauvage terrestre ET sous-marine du Parc national de Ras Mohammed en minibus climatisé : Porte monumentale d’Allah, forêt de mangroves uniques au nord du tropique, faille du tremblement de terre, baignade dans le Lac Magique salé et snorkeling spectaculaire depuis le rivage !'
                            : ($isNight
                                ? 'Armé d’une puissante lampe sous-marine LED fournie, flottez paisiblement en surface à quelques mètres de la plage pour observer coraux ouverts, rascasses, oursins diadèmes, calamars et poissons-perroquets endormis dans leur cocon !'
                                : 'Pas envie de passer 8 heures en bateau ? En 2 à 3 heures depuis la plage, notre guide vous emmène directement au-dessus d’un jardin corallien peu profond grouillant de milliers de poissons multicolores.')),
                    'Encadrée dans l’eau par un guide attentif muni d’une bouée de flottabilité, chaque sortie garantit sécurité et émerveillement même aux nageurs débutants.',
                ],
                'specs'      => [
                    ['Tarif', $pStr . ($isChild ? ' par enfant (5–12 ans · gratuit < 5 ans)' : ' par personne')],
                    ['Durée totale', $dur],
                    ['Conditions', $isChild ? 'Formule Yacht 100 % Tout Compris (taxes, buffet, boissons & matériel inclus)' : 'Minimum 6 participants · Transferts hôtel inclus'],
                    ['Équipement fourni', 'Masque, tuba, palmes et gilet de flottaison' . ($isNight ? ' + lampe étanche LED' : '')],
                    ['Sites explorés', $isChild || $isLand ? 'Parc national protégé de Ras Mohammed' . ($isChild ? ' & Île Blanche (White Island)' : ' (sites terrestres & récifs côtiers)') : 'Jardin corallien protégé en départ plage à Charm el-Cheikh'],
                ],
                'steps'      => [
                    ['Prise en charge hôtel & équipement sur mesure', 'Transfert climatisé depuis votre hôtel de Charm el-Cheikh et essayage confortable de vos masque, palmes et gilet.'],
                    ['Exploration guidée des merveilles de Mer Rouge', $isLand ? 'Arrêts panoramiques commentés (Porte d’Allah, Mangroves, Lac Magique) et sessions snorkeling guidées sur des tombants d’exception.' : 'Mise à l’eau progressive en compagnie de votre guide qui vous montre poissons-clowns, raies pastenagues et bénitiers géants.'],
                    ['Détente & retour confortable à l’hôtel', $isChild ? 'Grand déjeuner buffet à bord, jeux dans le lagon peu profond de White Island et retour vers 16h30.' : 'Boisson rafraîchissante/rinçage et retour direct à votre hôtel pour profiter du reste de votre journée ou soirée.'],
                ],
                'included'   => array_values(array_filter([
                    'Transferts aller-retour climatisés depuis votre hôtel à Charm el-Cheikh',
                    'Équipement complet de snorkeling (masque, tuba, palmes, gilet de sécurité)' . ($isNight ? ' + lampe sous-marine LED' : ''),
                    'Guide professionnel de snorkeling vous accompagnant dans l’eau',
                    ($isChild || $isLand) ? 'Taxes d’entrée au Parc national de Ras Mohammed incluses' : 'Accès plage et vestiaires/douches',
                    $isChild ? 'Déjeuner buffet chaud à bord + eau et boissons toute la journée' : null,
                ])),
                'excluded'   => [
                    'Photos sous-marines optionnelles',
                    'Serviette de plage personnelle et crème solaire respectueuse du corail',
                    $isChild ? 'Pourboire équipage facultatif' : 'Repas complet (sortie demi-journée / soirée)',
                ],
                'ideal_for'  => [
                    $isChild ? 'Familles avec enfants de 5 à 12 ans souhaitant vivre l’Île Blanche en toute sécurité' : ($isLand ? 'Voyageurs sujets au mal de mer voulant malgré tout voir Ras Mohammed sans monter sur un bateau !' : 'Amoureux de vie marine cherchant une sortie courte et intense depuis le bord'),
                    'Débutants complets rassurés par le port du gilet de flottaison et la bouée du guide',
                    'Groupes d’amis et familles en séjour à Charm el-Cheikh',
                ],
                'faqs'       => [
                    ['q' => 'Mon enfant ou moi ne sommes pas de grands nageurs, est-ce possible ?', 'a' => 'Oui sans aucun souci ! Les gilets de flottaison vous maintiennent en surface sans le moindre effort et votre guide garde une bouée de sécurité à portée de main.'],
                    ['q' => $isChild ? 'Y a-t-il des suppléments à payer à la marina pour les enfants ?' : 'Comment s’organise le minimum de 6 participants ?', 'a' => $isChild ? 'Aucun ! Les 30 € enfant couvrent déjà la taxe parc national, le transfert, le buffet déjeuner, les boissons et le petit matériel.' : 'Si votre famille/groupe compte moins de 6 personnes, contactez-nous sur WhatsApp pour rejoindre un départ groupé de la semaine !'],
                    ['q' => 'À quelle distance du bord se trouve le corail ?', 'a' => 'À Charm el-Cheikh, le récif frangeant commence à quelques coups de palmes seulement du rivage avec une eau limpide !'],
                ],
            ];

        case 'private-speedboats':
            $is1h = ($id === 'speed-1h');
            return [
                'h1'         => $name . ' à Charm el-Cheikh — Excursion Privée Rapide (' . $pStr . ')',
                'meta_title' => $name . ' Charm el-Cheikh (Sharm El Sheikh) | ' . $pStr . ' Bateau Privé',
                'meta_desc'  => 'Privatisez votre ' . $name . ' à Charm el-Cheikh (Sharm El Sheikh) dès ' . $pStr . ' : départ direct face à Tiran, capitaine privé, snorkeling & boissons fraîches !',
                'aeo_answer' => 'La location du ' . $name . ' chez Sea College Dive Club à Charm el-Cheikh coûte ' . $pStr . ' pour le bateau privatisé (' . ($is1h ? 'jusqu’à 3 passagers inclus ; 4e et 5e pers. +50 €/pers. sur les récifs locaux de Ras Nasrani' : 'pour 2 passagers inclus ; 3e à 5e pers. +100 €/pers. pour 3 heures complètes autour des lagons et récifs de l’Île de Tiran en 15 minutes de traversée, +5 €/pers. taxe parc') . ').',
                'overview'   => [
                    $is1h
                        ? 'Envie d’une escapade marine privée express entre le petit-déjeuner et la piscine ? En 1 heure intense au départ de notre plage nord, filez cheveux au vent vers les plus beaux jardins coralliens locaux de Ras Nasrani loin des grands bateaux !'
                        : 'Trois heures complètes en speedboat privé constituent le format royal pour explorer l’Île de Tiran : seulement 15 minutes de traversée depuis Al Montazah, vous laissant 2 h 30 pleines pour enchaîner deux superbes récifs coralliens ET flâner dans le lagon turquoise !',
                    'Horaires 100 % au choix (tôt le matin avec les dauphins ou lumière dorée de fin d’après-midi), jusqu’à 5 passagers maximum en intimité absolue.',
                ],
                'specs'      => [
                    ['Tarif bateau privé', $pStr . ($is1h ? ' (1 à 3 pers. incluses · 4e/5e +50 €/pers.)' : ' (1 à 2 pers. incluses · 3e–5e +100 €/pers.)')],
                    ['Durée privatisée', $dur . ' · Horaires libres matin ou après-midi'],
                    ['Zone explorée', $is1h ? 'Récifs coralliens locaux de Ras Nasrani / Al Montazah' : 'Lagons turquoise & récifs du Détroit de Tiran (traversée 15 min !)'],
                    ['Inclus à bord', 'Capitaine privé, carburant, masques/tubas/palmes/gilets & boissons fraîches'],
                    ['Taxes parc', $is1h ? '0 € sur récifs côtiers locaux' : '+5 € par personne (taxe officielle réserve de Tiran)'],
                ],
                'steps'      => [
                    ['Choix de votre créneau horaire idéal', 'Transfert depuis votre hôtel de Charm el-Cheikh et embarquement immédiat sans file d’attente à la marina.'],
                    ['Navigation rapide & arrêts snorkeling exclusifs', $is1h ? 'Mouillage direct au-dessus d’un aquarium naturel peu profond pour 40–45 min de snorkeling privé.' : '15 min de glisse vers l’épave du Lara et deux longs mouillages dans les eaux cristallines de Tiran.'],
                    ['Boissons fraîches à bord & retour plage', 'Rafraîchissements au soleil sur les banquettes coussinées et retour rapide à votre hôtel.'],
                ],
                'included'   => [
                    'Privatisation exclusive du speedboat rapide avec capitaine et carburant inclus',
                    'Équipement complet de snorkeling (masques, tubas, palmes, gilets) pour tous',
                    'Eau minérale et boissons fraîches en glacière à bord',
                    'Transferts aller-retour gratuits depuis votre hôtel à Charm el-Cheikh',
                ],
                'excluded'   => [
                    $is1h ? 'Passagers supplémentaires (4e et 5e personne : +50 €/pers., max. 5)' : 'Taxe parc marin de Tiran (+5 €/pers.) et passagers 3 à 5 (+100 €/pers., max. 5)',
                    'Guide privé dans l’eau optionnel sur demande',
                    'Serviettes de plage personnelles',
                ],
                'ideal_for'  => [
                    'Couples cherchant une sortie romantique privée loin des bateaux collectifs',
                    'Familles avec jeunes enfants voulant gérer librement la durée passée en mer',
                    'Amateurs de sensations nautiques et de récifs déserts aux heures creuses',
                ],
                'faqs'       => [
                    ['q' => 'Pourquoi partez-vous si vite vers Tiran ?', 'a' => 'Notre base d’Al Montazah / Ras Nasrani fait directement face au détroit de Tiran : il ne faut que 15 minutes de speedboat contre plus d’une heure depuis le sud de Charm el-Cheikh !'],
                    ['q' => 'Combien de personnes peuvent monter au maximum ?', 'a' => 'Jusqu’à 5 passagers privés par speedboat ; au-delà de 5 personnes, regardez nos grands Yachts Privés Journée dès 550 € !'],
                    ['q' => 'Y a-t-il un taud d’ombrage et une échelle de bain sur le speedboat ?', 'a' => 'Oui, tous nos speedboats disposent d’un bimini pare-soleil, d’une échelle confortable de remontée et de gilets de sécurité.'],
                ],
            ];

        case 'private-charters':
        default:
            $maxPax = ($id === 'private-1-5') ? 5 : (($id === 'private-11-15') ? 15 : 20);
            $minPax = ($id === 'private-1-5') ? 1 : (($id === 'private-11-15') ? 11 : 16);
            $fromPp = ($price !== null && $maxPax > 0) ? (int) round((float) $price / $maxPax) . ' €' : '';
            return [
                'h1'         => $name . ' à Charm el-Cheikh — Croisière Privée Mer Rouge (' . $pStr . ')',
                'meta_title' => $name . ' Charm el-Cheikh (Sharm El Sheikh) | ' . $pStr . ' | Yacht Privé',
                'meta_desc'  => 'Privatisez un grand yacht avec chef & équipage à Charm el-Cheikh (Sharm El Sheikh) : ' . $name . ' à ' . $pStr . ($fromPp ? ' (dès ' . $fromPp . '/pers.)' : '') . ' vers Ras Mohammed ou Tiran !',
                'aeo_answer' => 'La formule ' . $name . ' chez Sea College Dive Club à Charm el-Cheikh (Sharm El Sheikh) coûte ' . $pStr . ' pour l’intégralité du yacht privatisé pendant 7 à 8 heures' . ($fromPp ? ' (soit dès ' . $fromPp . ' par personne à ' . $maxPax . ' convives !)' : '') . '. Elle comprend votre yacht exclusif avec capitaine, marins, chef préparant un grand déjeuner buffet frais à bord, boissons toute la journée, matériel et guide snorkeling, transferts privés et itinéraire au choix vers Ras Mohammed & l’Île Blanche ou le Détroit de Tiran.',
                'overview'   => [
                    'Rien n’égale la liberté d’un yacht motorisé égyptien rien que pour votre couple, votre famille élargie ou votre groupe d’amis (' . $minPax . ' à ' . $maxPax . ' personnes) : pont supérieur ensoleillé avec matelas, grand salon intérieur, plateforme de mise à l’eau au ras des flots et service attentionné du matin au soir.',
                    'Naviguez loin de l’agitation vers le banc de sable turquoise de White Island, les falaises de Ras Mohammed ou les lagons de Tiran, et ajoutez à la carte baptêmes de plongée ou plongées certifiées avec un instructeur PADI privé à bord !',
                ],
                'specs'      => [
                    ['Forfait yacht privé', $pStr . ' bateau entier (' . $minPax . ' à ' . $maxPax . ' pers.' . ($fromPp ? ' · dès ' . $fromPp . '/pers.' : '') . ')'],
                    ['Durée en mer', $dur . ' (horaire de départ flexible selon vos envies)'],
                    ['Destinations au choix', 'Parc national de Ras Mohammed & Île Blanche OU Détroit de Tiran'],
                    ['Restauration à bord', 'Déjeuner buffet chaud préparé frais par le chef + boissons chaudes & fraîches'],
                    ['Options plongée', 'Ajout possible de baptêmes débutants ou plongées bouteille certifiées à bord'],
                ],
                'steps'      => [
                    ['Conception sur mesure de votre croisière', 'Définissez sur WhatsApp votre itinéraire préféré, vos préférences culinaires et les éventuels plongeurs bouteille du groupe.'],
                    ['Prise en charge privée & journée en Mer Rouge', 'Transfert privé depuis votre hôtel/villa, embarquement VIP et 2 à 3 mouillages paradisiaques pour nager, plonger et bronzer.'],
                    ['Déjeuner du chef à bord & retour tout en douceur', 'Buffet généreux servi dans le carré, escale baignade sur l’Île Blanche et retour en fin d’après-midi à votre hôtel.'],
                ],
                'included'   => [
                    'Privatisation exclusive du yacht, carburant, capitaine, équipage et chef cuisinier (' . $dur . ')',
                    'Déjeuner buffet complet fraîchement cuisiné à bord + eau, thé, café et sodas toute la journée',
                    'Équipement complet de snorkeling (masques, tubas, palmes, gilets) et guide snorkeling dans l’eau',
                    'Transferts privés climatisés aller-retour depuis votre hébergement à Charm el-Cheikh',
                ],
                'excluded'   => [
                    'Taxes officielles de parc marin (+5 € par personne)',
                    'Baptêmes de plongée ou plongées certifiées bouteille en option sur demande',
                    'Menus spéciaux langoustes/fruits de mer royaux ou photographe privé sur commande',
                ],
                'ideal_for'  => [
                    $maxPax <= 5 ? 'Couples en lune de miel, demandes en mariage et petites familles VIP (1 à 5 pers.)' : 'Grandes familles réunies, groupes d’amis, anniversaires et séminaires (' . $minPax . ' à ' . $maxPax . ' pers.)',
                    'Groupes souhaitant mêler sur le même bateau non-plongeurs, enfants, snorkelers et plongeurs bouteille',
                    'Voyageurs exigeant intimité, rythme libre et service privé haut de gamme à tarif direct centre',
                ],
                'faqs'       => [
                    ['q' => 'Le prix de ' . $pStr . ' est-il bien pour tout le bateau et non par personne ?', 'a' => 'Oui ! ' . $pStr . ' est le prix global du yacht privé pour l’ensemble de votre groupe de ' . $minPax . ' à ' . $maxPax . ' personnes (soit seulement ' . $fromPp . ' par personne à capacité pleine !).'],
                    ['q' => 'Peut-on ajouter des baptêmes ou des plongées certifiées sur notre yacht privé ?', 'a' => 'Absolument ! Prévenez-nous à la réservation et un instructeur PADI embarquera avec tout le matériel et les bouteilles nécessaires pour votre groupe.'],
                    ['q' => 'Combien de temps à l’avance faut-il réserver un yacht privé ?', 'a' => 'Nous recommandons de réserver au moins 3 à 7 jours à l’avance (notamment en haute saison d’avril à novembre) afin de bloquer le yacht et d’enregistrer les permis maritimes.'],
                ],
            ];
    }
};
