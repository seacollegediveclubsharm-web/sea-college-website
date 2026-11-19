<?php
/**
 * French (fr) group-aware & item-aware SEO/AEO template generator for all catalog items
 * not explicitly overridden in items_seo.php — zero English fallback.
 */
declare(strict_types=1);

return static function (array $it, array $seo): array {
    $id    = (string) ($it['id'] ?? '');
    $gid   = (string) ($it['_group']['id'] ?? '');
    $name  = item_name($it);
    $desc  = item_desc($it);
    $dur   = item_duration($it) ?: 'Par jour';
    $price = $it['price'] ?? null;
    $pStr  = $price !== null ? number_format((float) $price, ((float) $price == (int) $price ? 0 : 2), ',', ' ') . ' €' : 'Sur demande';

    switch ($gid) {
        case 'certified-packages':
            $dives = (int) preg_replace('/\D+/', '', $id);
            $isShore = ($id === 'dive-1');
            $isExtra = ($id === 'dive-extra');
            $perDive = ($dives > 0 && $price !== null) ? number_format((float) $price / $dives, ($price % $dives === 0 ? 0 : 2), ',', ' ') . ' €' : '30 €';
            return [
                'h1'         => $name . ' à Charm el-Cheikh — Exploration Mer Rouge (' . $pStr . ')',
                'meta_title' => $name . ' Charm el-Cheikh (Sharm El Sheikh) | ' . $pStr . ' | Sea College PADI 5★',
                'meta_desc'  => 'Réservez votre ' . $name . ' à Charm el-Cheikh (Sharm El Sheikh) pour ' . $pStr . ' (' . $dur . '). Plongées guidées PADI, bouteilles 12 L, plombs & transferts hôtel inclus.',
                'aeo_answer' => $name . ' chez Sea College Dive Club à Charm el-Cheikh (Sharm El Sheikh) coûte ' . $pStr . ($isExtra ? ' par plongée additionnelle après votre Forfait 10 Plongées' : ($dives > 1 ? ' par plongeur certifié (soit ' . $perDive . '/plongée, durée indicative ' . $dur . ')' : ' depuis notre récif maison (0 € de frais bateau ou parc marin, durée ' . $dur . ')')) . '. Ce tarif inclut le guidage par un professionnel PADI, les bouteilles 12 L air, les plombs et les transferts hôtel gratuits' . ($isShore ? '.' : ' ; les journées bateau ajoutent 10 €/jour (buffet déjeuner & boissons inclus) et 5 €/jour de taxe parc marin.'),
                'overview'   => [
                    $isShore
                        ? 'Parfaite pour une arrivée d’après-midi ou une reprise en douceur, cette plongée unique part directement de notre plage privée au Cyrene Sharm sur les jardins coralliens de Ras Nasrani, sans aucun supplément bateau ni taxe parc marin.'
                        : ($isExtra
                            ? 'Vous avez terminé votre Forfait 10 Plongées et ne voulez plus quitter la Mer Rouge ? Dès la 11e plongée, vous bénéficiez automatiquement de notre tarif fidélité le plus bas à 30 € par plongée guidée !'
                            : 'Explorez à votre rythme les plus beaux tombants et jardins coralliens de Charm el-Cheikh — Parc national de Ras Mohammed, Détroit de Tiran (Jackson, Thomas, Woodhouse, Gordon) et récifs locaux — en petites palanquées de niveau homogène.'),
                    'Nos instructeurs et Divemasters PADI francophones et internationaux (Mo, Sam, Alex, Jimmy, Mosab, Saleh) assurent des briefings précis, une sécurité irréprochable et des immersions longues et détendues. ' . $desc,
                ],
                'specs'      => [
                    ['Tarif officiel', $pStr . ($dives > 1 ? ' (' . $perDive . ' par plongée · forfait nominatif)' : ' par plongeur certifié')],
                    ['Durée / Rythme', $dur . ($dives >= 2 ? ' · 2 plongées guidées par sortie bateau (jours flexibles)' : '')],
                    ['Niveau requis', 'Open Water Diver / CMAS 1★ minimum (Check Dive 25 € si > 6 mois d’inactivité)'],
                    ['Inclus de série', 'Guide PADI, bouteilles 12 L air, plombs & transferts hôtel Charm el-Cheikh'],
                    ['Frais bateau sur place', $isShore ? '0 € (départ direct plage privée House Reef)' : '+10 €/jour bateau (buffet chaud & boissons compris) + 5 €/jour taxe parc marin'],
                    ['Options matériel', 'Équipement complet 25 €/jour · Nitrox 12 L +8 €/plongée (15 L +10 €)'],
                ],
                'steps'      => [
                    ['Prise en charge hôtel & vérification des niveaux', 'Transfert gratuit depuis votre hôtel de Charm el-Cheikh, contrôle rapide de votre carte de certification/carnet et préparation de votre scaphandre.'],
                    ['Briefings détaillés & plongées guidées Mer Rouge', $isShore ? 'Entrée progressive depuis la plage privée et exploration de 45–60 min le long du tombant fleuri de Ras Nasrani.' : 'Navigation confortable en yacht vers Tiran ou Ras Mohammed pour vos plongées guidées en petit groupe homogène.'],
                    ['Pause gourmande & carnet de plongée', $isShore ? 'Rinçage du matériel au centre, douche chaude et tampon officiel Sea College sur votre carnet.' : 'Déjeuner buffet fraîchement préparé à bord entre les plongées, rinçage/gardiennage de votre équipement au centre et validation du carnet.'],
                ],
                'included'   => [
                    'Guidage sous-marin par un instructeur ou Divemaster PADI certifié',
                    'Bouteille(s) 12 L aluminium air et lestage (ceinture ou plombs intégrés)',
                    'Transferts aller-retour climatisés depuis tous les hôtels de Charm el-Cheikh',
                    'Rinçage et stockage sécurisé de votre matériel personnel au centre Sea College',
                ],
                'excluded'   => [
                    $isShore ? 'Supplément bateau (uniquement si vous choisissez de monter sur le bateau : +10 € bateau + 5 € parc)' : 'Supplément bateau (10 €/jour incluant buffet déjeuner chaud & boissons) et taxe parc marin (5 €/jour)',
                    'Location d’équipement personnel si besoin (25 €/jour complet ou location à la pièce)',
                    'Suppléments spéciaux optionnels (SS Thistlegorm +150 €, Dahab +115 €, Dunraven / Million Hope / Nuit +30 €)',
                ],
                'ideal_for'  => [
                    'Plongeurs certifiés (PADI, FFESSM/CMAS, SSI, BSAC, NAUI) cherchant des palanquées conviviales à taille humaine',
                    'Voyageurs souhaitant planifier librement leurs journées de plongée avec transparence tarifaire totale',
                    'Amoureux de récifs intacts, tortues imbriquées, raies aigles, napoléons et bancs de barracudas',
                ],
                'faqs'       => [
                    ['q' => 'Mes journées de forfait doivent-elles être consécutives ?', 'a' => 'Non ! Vous pouvez répartir librement vos journées de plongée sur toute la durée de votre séjour à Charm el-Cheikh.'],
                    ['q' => 'Deux plongeurs peuvent-ils partager un même forfait ?', 'a' => 'Non, conformément aux règles tarifaires, chaque forfait est strictement personnel et individuel. Choisissez le nombre exact de plongées adapté à chaque plongeur !'],
                    ['q' => 'Que se passe-t-il si je n’ai pas plongé depuis plus de 6 mois ?', 'a' => 'Une Check Dive sur notre récif maison (25 €) est prévue pour retrouver vos repères ; au-delà d’un an sans plonger, optez pour le PADI Scuba Review (50 €).'],
                ],
            ];

        case 'wreck-special-dives':
            $isNight = ($id === 'sup-night');
            $isEarly = ($id === 'sup-zodiac-early');
            return [
                'h1'         => $name . ' à Charm el-Cheikh — Supplément Plongée Spéciale (+' . $pStr . ')',
                'meta_title' => $name . ' Charm el-Cheikh (Sharm El Sheikh) | +' . $pStr . ' | Sea College',
                'meta_desc'  => 'Ajoutez ' . $name . ' (+' . $pStr . ', ' . $dur . ') à votre forfait plongées à Charm el-Cheikh (Sharm El Sheikh) : ' . $desc,
                'aeo_answer' => 'Le supplément ' . $name . ' chez Sea College Dive Club à Charm el-Cheikh (Sharm El Sheikh) est proposé à +' . $pStr . ' par plongeur en complément d’une plongée de votre forfait certifié (durée ' . $dur . ($isNight ? ', départ au coucher du soleil sur notre récif maison' : ', minimum 4 plongeurs, réservation 48 h à l’avance') . '). ' . $desc,
                'overview'   => [
                    $isNight
                        ? 'Lorsque le soleil disparaît derrière les montagnes du Sinaï, notre récif maison de Ras Nasrani change totalement de visage : danseuses espagnoles écarlates, poulpes en chasse, langoustes, crabes éponges et rascasses surgissent dans le faisceau de votre lampe !'
                        : ($isEarly
                            ? 'Partir en zodiac rapide dès les premières lueurs de l’aube permet d’atteindre le bleu profond de Jackson Reef / Ras Nasrani ou l’épave du Million Hope avant le moindre bateau de journée — le créneau roi pour croiser requins-marteaux et grands pélagiques !'
                            : 'Nos sorties en semi-rigide (zodiac rapide) depuis Ras Nasrani vous déposent en quelques minutes pile sur les meilleurs points de mise à l’eau dérivants du Détroit de Tiran et de la côte nord sans temps mort.'),
                    'Encadrée par nos guides PADI chevronnés en petite palanquée, cette expérience spéciale vient sublimer votre forfait d’exploration en Mer Rouge.',
                ],
                'specs'      => [
                    ['Supplément tarifaire', '+' . $pStr . ' par plongeur (s’ajoute à 1 plongée de votre forfait)'],
                    ['Durée totale', $dur],
                    ['Conditions & Réservation', $isNight ? 'Plongeur certifié · Départ crépuscule depuis notre plage privée' : 'Minimum 4 plongeurs certifiés · Réservation min. 48 h à l’avance'],
                    ['Site principal', $isNight ? 'Récif maison du Cyrene Sharm (Ras Nasrani, 5–16 m)' : ($isEarly ? 'Bleu de Tiran (requins-marteaux) ou épave Million Hope' : 'Sites express Détroit de Tiran & récifs côtiers nord')],
                    ['Équipement conseillé', $isNight ? 'Lampe principale + lampe de secours (location +6 €/jour)' : 'Ordinateur de plongée & parachute de palier (SMB)'],
                ],
                'steps'      => [
                    ['Planification & briefing spécialisé', $isNight ? 'Briefing au coucher du soleil sur les signaux lumineux, l’orientation nocturne et la faune de nuit.' : 'Briefing technique sur la mise à l’eau bascule arrière depuis le zodiac et la gestion de la dérive.'],
                    ['Immersion privilège hors foule', $isNight ? 'Entrée calme depuis la plage et exploration magique de 45 minutes sous les étoiles de la Mer Rouge.' : 'Trajet express en zodiac rapide et plongée sur des récifs vierges de toute palanquée concurrente !'],
                    ['Retour direct au centre Sea College', 'Douche chaude, boisson conviviale et débriefing passionné des observations rares du jour.'],
                ],
                'included'   => [
                    $isNight ? 'Organisation nocturne dédiée et encadrement PADI de nuit' : 'Sortie express en zodiac rapide avec pilote marin expérimenté et guide PADI',
                    'Logistique bloc et sécurité surface dédiée',
                    'Transferts hôtel aller-retour adaptés aux horaires spéciaux à Charm el-Cheikh',
                    'Briefing approfondi sur les espèces et procédures spécifiques',
                ],
                'excluded'   => [
                    'La plongée de base décomptée de votre forfait plongées certifiées',
                    $isNight ? 'Location de lampe de plongée (+6 €) si vous n’avez pas la vôtre' : 'Taxe parc marin éventuelle (5 €) et location d’équipement personnel (25 €/jour)',
                    'Bouteille Nitrox optionnelle (+8 € en 12 L)',
                ],
                'ideal_for'  => [
                    $isNight ? 'Plongeurs curieux de vivre leur première plongée de nuit sur un récif calme et rassurant' : 'Plongeurs dynamiques aimant les mises à l’eau rapides loin des grands yachts',
                    'Photographes macro (nuit) ou amateurs de grands pélagiques et requins à l’aube (zodiac matinal)',
                    'Titulaires de forfaits 6 à 10 plongées voulant varier les sensations',
                ],
                'faqs'       => [
                    ['q' => 'Comment fonctionne le tarif en supplément ?', 'a' => 'Vous utilisez simplement 1 plongée de votre forfait certifié et réglez le supplément de +' . $pStr . ' pour la logistique spéciale (' . $name . ').'],
                    ['q' => $isNight ? 'Faut-il la spécialité Night Diver pour faire une plongée de nuit ?' : 'Que faire si nous ne sommes que 2 plongeurs pour le zodiac ?', 'a' => $isNight ? 'Un niveau Open Water avec une bonne flottabilité suffit sur notre récif maison calme (et cette plongée peut même compter comme Plongée 1 du brevet PADI Night Diver !).' : 'Inscrivez votre souhait dès votre arrivée (48 h avant) : nous regroupons très régulièrement 4 à 6 plongeurs motivés !'],
                    ['q' => 'La sécurité surface est-elle assurée ?', 'a' => 'Oui, oxygène médical d’urgence, trousse de secours, radio VHF et équipe surface dédiée accompagnent 100 % de nos sorties spéciales.'],
                ],
            ];

        case 'daily-fees':
            $isBoat = ($id === 'fee-boat');
            $isPark = ($id === 'fee-park');
            return [
                'h1'         => $name . ' à Charm el-Cheikh — Tarif Transparent (' . $pStr . ')',
                'meta_title' => $name . ' Charm el-Cheikh (Sharm El Sheikh) | ' . $pStr . ' | Sea College',
                'meta_desc'  => 'Transparence tarifaire Sea College à Charm el-Cheikh (Sharm El Sheikh) : ' . $name . ' à ' . $pStr . ' (' . $dur . '). ' . $desc,
                'aeo_answer' => $name . ' chez Sea College Dive Club à Charm el-Cheikh s’élève à ' . $pStr . ' (' . $dur . '). ' . ($isBoat ? 'Contrairement à de nombreux centres qui facturent le repas à part à bord, nos 10 €/jour de supplément bateau incluent déjà la journée yacht complète, le grand déjeuner buffet chaud préparé à bord ainsi que l’eau, le thé, le café et les boissons fraîches !' : ($isPark ? 'Il s’agit de la redevance officielle égyptienne de préservation environnementale applicable uniquement les jours de sortie maritime vers le Parc national de Ras Mohammed ou la réserve du Détroit de Tiran (0 € sur le récif maison).' : 'Votre instructeur ou Divemaster PADI privé vous est exclusivement dédié toute la journée pour plonger à votre propre rythme de consommation et de photographie sous-marine.')),
                'overview'   => [
                    'Chez Sea College Dive Club, nous affichons chaque tarif noir sur blanc avant même votre réservation afin que vous puissiez calculer votre budget vacances à l’euro près, sans aucune surprise en fin de séjour.',
                    $isBoat
                        ? 'À seulement 10 € par jour en mer, ce supplément couvre à la fois votre place sur nos yachts spacieux ET toute la restauration chaude et boissons de la journée — un rapport qualité-prix imbattable à Charm el-Cheikh !'
                        : ($isPark
                            ? 'Collectée pour la protection des récifs coralliens et des bouées d’amarrage écologiques du Sud-Sinaï, la taxe de 5 €/jour ne s’applique que lors des sorties bateau (déjà incluse dans nos baptêmes bateau à 70 €/100 € et nos journées snorkeling à 55 € !).'
                            : 'Envie de prendre 20 minutes devant un poisson-clown pour vos photos macro ou de plonger uniquement en couple sans suivre une palanquée ? Le Guide Privé à 75 €/jour transforme n’importe quel forfait en expérience sur mesure.'),
                ],
                'specs'      => [
                    ['Montant officiel', $pStr . ' (' . $dur . ')'],
                    ['Quand s’applique-t-il ?', $isBoat ? 'Jours de sortie bateau pour forfaits certifiés & cours (déjà inclus dans baptêmes bateau/snorkeling)' : ($isPark ? 'Jours de navigation vers Ras Mohammed ou Tiran (0 € depuis la plage)' : 'Option facultative sur réservation pour 1 plongeur, un couple ou une famille')],
                    ['Ce qui est couvert', $desc],
                    ['Règlement', 'Transparent sur place au centre en EUR, USD, GBP, EGP ou carte'],
                    ['Centre', 'Sea College Dive Club · PADI 5★ #26104236 à Charm el-Cheikh'],
                ],
                'steps'      => [
                    ['Information claire dès le devis', 'Votre récapitulatif WhatsApp ou e-mail détaille exactement vos jours bateau et vos options choisies.'],
                    ['Prestation complète le jour J', $isBoat ? 'Embarquement sur grand yacht, buffet chaud fraîchement cuisiné le midi et boissons à volonté.' : ($isPark ? 'Enregistrement officiel du manifeste maritime auprès des autorités des parcs nationaux.' : 'Briefing privé sur mesure et rythme d’immersion 100 % calé sur vos envies.')],
                    ['Facturation limpide sans frais cachés', 'Seuls les jours réellement effectués en mer ou réservés sont comptabilisés.'],
                ],
                'included'   => $isBoat ? [
                    'Journée complète à bord de nos yachts de plongée spacieux avec pont soleil et carré ombragé',
                    'Déjeuner buffet fraîchement préparé à bord par le chef cuisinier',
                    'Eau minérale, thé, café et boissons rafraîchissantes tout au long de la journée',
                    'Acheminement de vos bouteilles et de votre équipement à bord par notre équipe',
                ] : ($isPark ? [
                    'Droit d’accès maritime journalier officiel aux réserves protégées de Ras Mohammed et Tiran',
                    'Contribution directe à l’entretien des mouillages écologiques protégeant le corail',
                    'Formalités administratives et manifeste gérés intégralement par Sea College',
                    'Exonération totale (0 €) lors des plongées depuis notre récif maison privé',
                ] : [
                    'Instructeur ou Divemaster PADI dédié exclusivement à vous ou votre binôme/famille',
                    'Choix personnalisé du profil, du rythme de palmage et des sujets observés',
                    'Accompagnement idéal pour photographes sous-marins ou plongeurs reprenant confiance',
                    'Couvre les 2 plongées (et 3e éventuelle) de la journée complète',
                ]),
                'excluded'   => [
                    'Forfait de plongées certifiées ou cours PADI de base',
                    'Location d’équipement personnel (25 €/jour complet)',
                    'Pourboires facultatifs à votre discrétion',
                ],
                'ideal_for'  => [
                    'Plongeurs appréciant une politique tarifaire honnête et détaillée à l’avance',
                    $isBoat ? 'Amateurs de confort en yacht avec vrai repas chaud cuisiné à bord' : ($isPark ? 'Visiteurs des parcs marins classés parmi les plus beaux récifs du monde' : 'Photographes/vidéastes sous-marins, couples et plongeurs souhaitant un coaching VIP'),
                    'Voyageurs organisant leur budget plongée à Charm el-Cheikh sans mauvaise surprise',
                ],
                'faqs'       => [
                    ['q' => 'Ce supplément est-il déjà inclus dans les baptêmes bateau (70 € / 100 €) et le snorkeling bateau (55 €) ?', 'a' => 'Oui ! Nos excursions découverte bateau (70 €, 100 €) et snorkeling bateau (55 € adulte / 30 € enfant) sont déjà 100 % tout compris. Les frais journaliers (10 € bateau + 5 € parc) ne s’ajoutent qu’aux forfaits certifiés et aux sorties bateau de cours.'],
                    ['q' => 'Y a-t-il des frais de bateau ou de parc si je plonge sur le récif maison ?', 'a' => 'Non, zéro euro ! Depuis notre plage privée du Cyrene Sharm, il n’y a ni supplément bateau ni taxe parc marin.'],
                    ['q' => 'Le tarif du Guide Privé (75 €/jour) est-il par personne ou pour notre binôme ?', 'a' => '75 €/jour couvre le guide privé dédié pour vous seul ou votre binôme/petit groupe familial de même niveau !'],
                ],
            ];

        case 'equipment-rental':
            $isTank = in_array($id, ['eq-15air', 'eq-15nx', 'eq-12nx', 'eq-sm-tank'], true);
            $isFull = ($id === 'eq-full' || $id === 'eq-sm-full');
            $unit   = $isTank ? 'par plongée' : 'par jour';
            return [
                'h1'         => 'Location ' . $name . ' à Charm el-Cheikh (' . $pStr . ' ' . $unit . ')',
                'meta_title' => 'Location ' . $name . ' Plongée Charm el-Cheikh (Sharm El Sheikh) | ' . $pStr,
                'meta_desc'  => 'Voyagez léger à Charm el-Cheikh (Sharm El Sheikh) ! Location ' . $name . ' entretenu selon normes PADI 5★ à ' . $pStr . ' ' . $unit . ' chez Sea College Dive Club.',
                'aeo_answer' => 'La location de « ' . $name . ' » chez Sea College Dive Club à Charm el-Cheikh (Sharm El Sheikh) coûte ' . $pStr . ' ' . $unit . '. ' . ($isFull ? 'Bon à savoir : l’équipement de plongée est déjà inclus gratuitement dans tous nos baptêmes et tous nos cours PADI ! Pour les forfaits d’exploration certifiés, notre parc Scubapro / Mares révisé et désinfecté couvre toutes les tailles de XXS enfant à 3XL.' : 'Chaque pièce et bouteille est révisée périodiquement par nos techniciens agréés, désinfectée après chaque usage et acheminée directement sur le bateau pour vous.'),
                'overview'   => [
                    'Inutile de payer des excédents bagages coûteux en avion : notre salle d’équipement climatisée au Cyrene Sharm dispose d’un parc moderne (Scubapro, Mares, Aqualung) dans toutes les tailles adultes et juniors.',
                    $isTank
                        ? 'Nos bouteilles aluminium 12 L air standard sont toujours incluses gratuitement dans vos plongées ; optez pour nos blocs 15 L (gros consommateurs d’air), Nitrox 32 % analysé ensemble avant départ ou blocs Sidemount gréés.'
                        : 'Essayé au sec avec notre équipe le premier jour, votre matériel est identifié à votre nom dans une caisse personnelle que notre staff transporte, rince et fait sécher pour vous chaque jour !',
                ],
                'specs'      => [
                    ['Tarif de location', $pStr . ' ' . $unit],
                    ['Disponibilité tailles', $isTank ? 'Robinetterie DIN & Étrier (INT) compatible tous détendeurs' : 'Toutes tailles disponibles (Enfant / XXS jusqu’à XXL / 3XL)'],
                    ['Entretien & Hygiène', 'Révision constructeur certifiée & désinfection antibactérienne quotidienne'],
                    ['Inclus dans les cours', 'Déjà inclus à 0 € dans tous les baptêmes et tous les cours PADI !'],
                    ['Logistique incluse', 'Transport quotidien vers le bateau, rinçage eau douce et séchage au centre'],
                ],
                'steps'      => [
                    ['Essayage personnalisé au centre (ou indication de vos tailles)', 'Vérification du confort parfait avant le départ (ou préparation anticipée via WhatsApp si vous embarquez direct).'],
                    ['Acheminement automatique sur votre bateau', 'Notre équipe charge votre caisse nominative ou vos blocs analysés chaque matin à bord.'],
                    ['Rinçage & désinfection en fin de journée', 'Rentrez les mains libres à votre hôtel pendant que notre staff rince et stocke tout au centre !'],
                ],
                'included'   => [
                    $name . ' révisé, testé et parfaitement entretenu',
                    $isTank ? 'Analyseur d’oxygène calibré à disposition pour contrôle personnel (blocs Nitrox)' : 'Essayage sur mesure et échange immédiat de taille si souhaité',
                    'Transport entre le centre de plongée et le bateau chaque jour',
                    'Rinçage à l’eau douce et désinfection complète après utilisation',
                ],
                'excluded'   => [
                    'Forfait de plongées ou cours PADI correspondant',
                    $isFull ? 'Ordinateur de plongée (+10 €/jour) et lampe (+6 €/jour) louables en option' : 'Autres pièces d’équipement non comprises dans cet article unitaire (forfait complet plafonné à 25 €/jour !)',
                    'Perte ou casse accidentelle hors usure normale',
                ],
                'ideal_for'  => [
                    'Voyageurs prenant un vol avec bagage cabine ou souhaitant voyager léger vers l’Égypte',
                    $isTank ? 'Plongeurs certifiés Nitrox, grands gabarits préférant un 15 L ou adeptes du Sidemount' : 'Plongeurs possédant une partie de leur matériel et ne louant que les pièces volumineuses',
                    'Plongeurs exigeants sur la fiabilité des détendeurs, gilets et ordinateurs de location',
                ],
                'faqs'       => [
                    ['q' => 'À partir de combien de pièces vaut-il mieux prendre le forfait équipement complet à 25 €/jour ?', 'a' => 'Dès que vous louez gilet BCD (10 €) + détendeur (13 €) + combinaison (10 €), le forfait Équipement Complet à 25 €/jour est immédiatement plus avantageux puisqu’il inclut tout (masque, palmes, chaussons, combar, BCD, détendeur) !'],
                    ['q' => 'Vos détendeurs et bouteilles acceptent-ils le montage DIN et Étrier (INT) ?', 'a' => 'Oui ! Tous nos blocs disposent d’inserts amovibles compatibles aussi bien DIN 200/300 bars qu’Étrier international.'],
                    ['q' => 'Quelle épaisseur ont vos combinaisons de location ?', 'a' => 'Nous proposons des combinaisons intégrales 5 mm néoprène souple parfaites pour l’hiver/printemps ainsi que des modèles légers/shortys pour les eaux à 29 °C de l’été.'],
                ],
            ];

        case 'diving-insurance':
            return [
                'h1'         => $name . ' — Couverture Plongée & Caisson Hyperbare Mer Rouge',
                'meta_title' => $name . ' Charm el-Cheikh (Sharm El Sheikh) | Assurance Plongée Mer Rouge',
                'meta_desc'  => 'Plongez l’esprit tranquille à Charm el-Cheikh (Sharm El Sheikh) : souscrivez une ' . $name . ' temporaire couvrant médecine de plongée et caisson hyperbare local.',
                'aeo_answer' => 'La ' . $name . ' proposée par Sea College Dive Club à Charm el-Cheikh (durée ' . $dur . ', tarif officiel actualisé sur demande / à l’enregistrement) offre une couverture temporaire immédiate spécialisée pour la plongée sous-marine en Égypte, incluant les frais médicaux d’urgence et la prise en charge au centre hyperbare spécialisé de Charm el-Cheikh pour les plongeurs non couverts par DAN, Lafont/FFESSM ou leur assurance voyage.',
                'overview'   => [
                    'En Égypte, disposer d’une couverture d’assurance incluant explicitement la plongée sous-marine et le traitement en caisson hyperbare est indispensable pour tout plongeur certifié (les cartes bancaires classiques excluent souvent la plongée au-delà de 18 m ou les soins hyperbares).',
                    'Charm el-Cheikh possède l’un des meilleurs centres médicaux hyperbares du Moyen-Orient dirigé par le célèbre Dr Adel Taher : cette police courte durée (' . $dur . ') s’active en quelques minutes directement au centre avant votre première mise à l’eau !',
                ],
                'specs'      => [
                    ['Durée de validité', $dur . ' consécutifs dès activation'],
                    ['Tarif', 'Sur demande (tarif officiel assureur partenaire sans majoration)'],
                    ['Garanties clés', 'Urgences médicales de plongée, évacuation marine & caisson hyperbare'],
                    ['Souscription', 'Immédiate sur place au centre Sea College ou la veille via WhatsApp'],
                    ['Qui est exempté ?', 'Élèves en cours débutants PADI (couverts pendant le cours) & assurés DAN / FFESSM / DiveAssure'],
                ],
                'steps'      => [
                    ['Vérification de votre couverture existante', 'Si vous avez déjà DAN, DiveAssure ou une licence FFESSM/LIFRAS incluant l’étranger, présentez simplement votre attestation : rien à payer !'],
                    ['Activation express (' . $dur . ')', 'Dans le cas contraire, nous enregistrons vos nom, date de naissance et passeport en 2 minutes avant l’embarquement.'],
                    ['Plongez 100 % serein en Mer Rouge', 'Vous êtes couvert sur l’ensemble de vos plongées loisirs (air & Nitrox dans les prérogatives de votre brevet) à Charm el-Cheikh.'],
                ],
                'included'   => [
                    'Couverture spécialisée accidents de plongée loisir pendant ' . $dur,
                    'Prise en charge directe auprès du centre hyperbare de Charm el-Cheikh',
                    'Enregistrement administratif immédiat par notre équipe au centre',
                    'Tranquillité d’esprit totale sur Ras Mohammed, Tiran et les épaves',
                ],
                'excluded'   => [
                    'Plongées hors des limites de profondeur de votre niveau de certification',
                    'Affections médicales préexistantes non déclarées sur le questionnaire médical',
                    'Assurance annulation vol/bagages générale de voyage',
                ],
                'ideal_for'  => [
                    'Plongeurs vacanciers plongeant une fois par an sans abonnement annuel DAN',
                    'Voyageurs dont l’assurance voyage standard exclut les sports subaquatiques',
                    'Séjours de plongée correspondant à une fenêtre de ' . $dur . ' en Mer Rouge',
                ],
                'faqs'       => [
                    ['q' => 'Ma licence FFESSM + assurance Lafont ou mon contrat DAN suffisent-ils ?', 'a' => 'Oui, tout à fait ! Apportez simplement votre numéro de police DAN, DiveAssure ou votre attestation d’assurance fédérale valide à l’étranger et vous n’aurez pas besoin de souscrire cette assurance temporaire.'],
                    ['q' => 'Combien coûte environ l’assurance courte durée sur place ?', 'a' => 'Le tarif officiel de la chambre/assureur local est très modique (quelques euros par jour selon le palier de ' . $dur . ') ; écrivez-nous sur WhatsApp pour le barème exact en vigueur cette saison.'],
                    ['q' => 'Puis-je la prendre le matin même de ma première sortie ?', 'a' => 'Oui, l’enregistrement numérique prend moins de 3 minutes à votre arrivée au centre avant le départ !'],
                ],
            ];

        default:
            $builderB = require __DIR__ . '/item_templates_b.php';
            return $builderB($it, $seo, $id, $gid, $name, $desc, $dur, $price, $pStr);
    }
};
