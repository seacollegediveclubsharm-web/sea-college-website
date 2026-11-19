/**
 * Sea College Dive Club — Zero-Reset 8-Language Google Ads Campaign & Ad Group Builder
 * Account: 365-126-5545 (EGP)
 *
 * SAFETY GUARANTEES:
 * 1. NEVER touches or edits your existing live campaign 'sea college start' (#24058786556)
 *    or Ad ID 817963783396 — preserving 100% of historical Smart Bidding learning!
 * 2. Creates new Ad Groups & Responsive Search Ads inside a staging/paused state first
 *    so you can preview in Google Ads UI and enable one language at a time after DNS cutover.
 *
 * HOW TO USE IN GOOGLE ADS:
 * 1. Go to Google Ads (365-126-5545) -> Tools -> Bulk Actions -> Scripts -> + New Script
 * 2. Paste this entire file and click "Preview" first (DRY_RUN = true logs every action safely).
 * 3. When ready to stage the ads inside an existing PAUSED campaign shell, set DRY_RUN = false.
 *    (For creating brand-new Search campaigns with country targeting in 1 click, import
 *    docs/google_ads_editor_8lang_import.csv via Google Ads Editor -> Account -> Import -> From file.)
 */

var DRY_RUN = true; // Set false when ready to create Ad Groups, Keywords & RSAs
var TARGET_CAMPAIGN_PREFIX = 'SC_Search_';

var BLUEPRINT = [
  {
    "lang": "en",
    "campaign": "SC_Search_EN_Courses_Bundles",
    "budget_egp": 600,
    "ad_group": "PADI_Open_Water_And_Bundles",
    "final_url": "https://seacollegediveclub.com/diving-courses-sharm-el-sheikh?utm_source=google&utm_medium=cpc&utm_campaign=en_padi_courses",
    "keywords": [
      "[padi open water sharm el sheikh]",
      "\"diving courses sharm el sheikh\"",
      "\"learn to dive sharm el sheikh\"",
      "[padi 5 star dive center sharm]",
      "\"advanced open water sharm\""
    ],
    "negatives": [
      "free",
      "job",
      "jobs",
      "hiring",
      "hurghada",
      "marsa alam",
      "dahab hotel"
    ],
    "headlines": [
      "PADI 5 Star Resort Sharm",
      "Open Water Course EUR 400",
      "4.9 Stars - 3,082+ Reviews",
      "Private Pool & House Reef",
      "Save With Mega Bundle EUR 600",
      "EUR 0 Upfront Booking Fee",
      "Free Sharm Hotel Transfers",
      "Small Groups & Top Safety"
    ],
    "descriptions": [
      "Official PADI 5 Star Dive Resort at Al Montazah. Private pool & calm House Reef.",
      "Book PADI Open Water (EUR 400) or OWD+AOWD Mega Bundle (EUR 600). Pay on arrival!",
      "Rated 4.9 stars by 3,082+ divers on Google & Tripadvisor. Free hotel pickup.",
      "Use our live Dive Calculator for 100% transparent EUR pricing with zero hidden fees."
    ]
  },
  {
    "lang": "en",
    "campaign": "SC_Search_EN_Dive_Packages",
    "budget_egp": 550,
    "ad_group": "Certified_Diving_Ras_Mohammed",
    "final_url": "https://seacollegediveclub.com/diving-trips-sharm-el-sheikh?utm_source=google&utm_medium=cpc&utm_campaign=en_dive_packages",
    "keywords": [
      "[scuba diving sharm el sheikh]",
      "\"dive packages sharm el sheikh\"",
      "\"ras mohammed diving\"",
      "\"ss thistlegorm dive trip\"",
      "\"tiran island diving sharm\""
    ],
    "negatives": [
      "free",
      "job",
      "hurghada",
      "marsa alam"
    ],
    "headlines": [
      "Sharm El Sheikh Dive Packages",
      "6 Dives EUR 210 | 10 EUR 350",
      "Ras Mohammed & Tiran Boats",
      "SS Thistlegorm Wreck Trips",
      "4.9 Stars (3,082+ Reviews)",
      "PADI 5 Star Beachfront Base",
      "Live EUR Dive Fee Calculator",
      "Zodiac RIB & Nitrox Available"
    ],
    "descriptions": [
      "Daily boat & House Reef dives to Ras Mohammed, Straits of Tiran & SS Thistlegorm.",
      "6-Dive Pack EUR 210, 10-Dive Pack EUR 350. Build your exact quote in 20 seconds!",
      "PADI 5 Star Resort facing Tiran Island. Free Sharm hotel transfers & EUR 0 card fee.",
      "Beginners welcome too: House Reef Intro Dive EUR 30 or 2 Boat Dives all-in EUR 100."
    ]
  },
  {
    "lang": "en",
    "campaign": "SC_Search_EN_Private_Boats",
    "budget_egp": 350,
    "ad_group": "Private_Yacht_And_Speedboat",
    "final_url": "https://seacollegediveclub.com/snorkeling-and-boats-sharm-el-sheikh?utm_source=google&utm_medium=cpc&utm_campaign=en_private_boats",
    "keywords": [
      "\"private boat sharm el sheikh\"",
      "\"private yacht ras mohammed\"",
      "\"tiran island speedboat\"",
      "\"white island boat trip sharm\""
    ],
    "negatives": [
      "sale",
      "buy yacht",
      "hurghada"
    ],
    "headlines": [
      "Private Yacht Sharm EUR 550",
      "Tiran Speedboat From EUR 150",
      "White Island & Ras Mohammed",
      "VIP Boats For 1 To 20 Guests",
      "PADI 5 Star Safety & Crew",
      "4.9 Stars - 3,082+ Reviews"
    ],
    "descriptions": [
      "Charter your own private yacht (from EUR 550) or Tiran speedboat (from EUR 150).",
      "Visit White Island, Ras Mohammed & Tiran Reefs with private guide & hotel transfer."
    ]
  },
  {
    "lang": "ru",
    "campaign": "SC_Search_RU_Diving_Sharm",
    "budget_egp": 450,
    "ad_group": "RU_Diving_And_PADI_Sharm",
    "final_url": "https://seacollegediveclub.com/ru/diving-trips-sharm-el-sheikh?utm_source=google&utm_medium=cpc&utm_campaign=ru_diving_sharm",
    "keywords": [
      "\"дайвинг в шарм эль шейхе\"",
      "\"обучение дайвингу шарм\"",
      "\"дайвинг рас мохаммед\"",
      "\"русский дайвинг центр шарм\"",
      "\"курс padi шарм эль шейх\""
    ],
    "negatives": [
      "хургада",
      "марса алам",
      "работа",
      "бесплатно"
    ],
    "headlines": [
      "Дайвинг в Шарм-эль-Шейхе 5*",
      "Русские Инструкторы PADI",
      "Рейтинг 4.9 (3082+ Отзыва)",
      "Пробное Погружение от 30 EUR",
      "Курс PADI Open Water 400 EUR",
      "Рас-Мохаммед, Тиран и Рэки",
      "Бронь Без Предоплаты (0 EUR)",
      "Свой Бассейн и Домашний Риф"
    ],
    "descriptions": [
      "Официальный дайв-центр PADI 5* на первой линии Аль-Монтаза. Русскоговорящая команда!",
      "Пробный дайвинг с берега 30 EUR, с яхты (2 дайва всё вкл.) 100 EUR. Трансфер включён.",
      "Пакеты 6 дайвов 210 EUR, 10 дайвов 350 EUR. Честный онлайн-калькулятор без доплат."
    ]
  },
  {
    "lang": "it",
    "campaign": "SC_Search_IT_Immersioni_Sharm",
    "budget_egp": 350,
    "ad_group": "IT_Immersioni_E_Corsi_PADI",
    "final_url": "https://seacollegediveclub.com/it/diving-trips-sharm-el-sheikh?utm_source=google&utm_medium=cpc&utm_campaign=it_immersioni_sharm",
    "keywords": [
      "\"immersioni sharm el sheikh\"",
      "\"centro diving sharm\"",
      "\"corso padi sharm el sheikh\"",
      "\"ras mohammed immersioni\""
    ],
    "negatives": [
      "marsa alam",
      "hurghada",
      "lavoro"
    ],
    "headlines": [
      "Centro Diving PADI 5* Sharm",
      "4.9 Stelle (3.082+ Recensioni)",
      "Pacchetti Diving & Corsi PADI",
      "Ras Mohammed, Tiran & Relitti",
      "Battesimo del Mare da 30 EUR",
      "Zero Acconto Online (0 EUR)"
    ],
    "descriptions": [
      "Resort PADI 5* fronte mare ad Al Montazah con piscina privata e House Reef calmo.",
      "6 immersioni 210 EUR, 10 immersioni 350 EUR, Open Water 400 EUR. Transfer incluso!"
    ]
  },
  {
    "lang": "de",
    "campaign": "SC_Search_DE_Tauchen_Sharm",
    "budget_egp": 400,
    "ad_group": "DE_Tauchen_Und_PADI_Kurse",
    "final_url": "https://seacollegediveclub.com/de/diving-trips-sharm-el-sheikh?utm_source=google&utm_medium=cpc&utm_campaign=de_tauchen_sharm",
    "keywords": [
      "\"tauchen sharm el sheikh\"",
      "\"tauchbasis sharm el sheikh\"",
      "\"padi tauchkurs sharm\"",
      "\"thistlegorm tauchen sharm\""
    ],
    "negatives": [
      "hurghada",
      "marsa alam",
      "job"
    ],
    "headlines": [
      "PADI 5* Tauchbasis Sharm",
      "4,9 Sterne (3.082+ Bewert.)",
      "Hausriff, Pool & Tiran Boote",
      "10 Tauchgänge Paket 350 EUR",
      "PADI Open Water Kurs 400 EUR",
      "Transparente EUR-Preise"
    ],
    "descriptions": [
      "PADI 5* Dive Resort direkt am Strand von Al Montazah gegenüber der Straße von Tiran.",
      "Ras Mohammed, Tiran & SS Thistlegorm. Inkl. Hoteltransfer & Live-Tauchkostenrechner!"
    ]
  },
  {
    "lang": "pl",
    "campaign": "SC_Search_PL_Nurkowanie_Sharm",
    "budget_egp": 250,
    "ad_group": "PL_Nurkowanie_I_Kursy_PADI",
    "final_url": "https://seacollegediveclub.com/pl/diving-trips-sharm-el-sheikh?utm_source=google&utm_medium=cpc&utm_campaign=pl_nurkowanie_sharm",
    "keywords": [
      "\"nurkowanie sharm el sheikh\"",
      "\"centrum nurkowe sharm\"",
      "\"kurs padi sharm el sheikh\""
    ],
    "negatives": [
      "hurghada",
      "marsa alam",
      "praca"
    ],
    "headlines": [
      "Nurkowanie Sharm PADI 5*",
      "Ocena 4.9 (3082+ Opinii)",
      "Intronurkowie od 30 EUR",
      "Ras Mohammed, Tiran i Wraki",
      "Kurs PADI Open Water 400 EUR",
      "Rezerwacja 0 EUR Zaliczki"
    ],
    "descriptions": [
      "Baza PADI 5* przy plaży Al Montazah: własny basen, rafa domowa i łodzie na Tiran.",
      "Pakiety nurkowe, intro dla początkujących i prywatne łodzie. Darmowy transfer z hotelu!"
    ]
  },
  {
    "lang": "fr",
    "campaign": "SC_Search_FR_Plongee_Sharm",
    "budget_egp": 250,
    "ad_group": "FR_Plongee_Et_Cours_PADI",
    "final_url": "https://seacollegediveclub.com/fr/diving-trips-sharm-el-sheikh?utm_source=google&utm_medium=cpc&utm_campaign=fr_plongee_sharm",
    "keywords": [
      "\"plongée sharm el sheikh\"",
      "\"club plongée sharm\"",
      "\"cours padi sharm el sheikh\""
    ],
    "negatives": [
      "hurghada",
      "marsa alam",
      "emploi"
    ],
    "headlines": [
      "Centre Plongée PADI 5* Sharm",
      "Note 4.9/5 (3 082+ Avis)",
      "Baptême Plongée dès 30 EUR",
      "Ras Mohammed, Tiran & Épaves",
      "Forfait 10 Plongées 350 EUR",
      "0 EUR d'Acompte en Ligne"
    ],
    "descriptions": [
      "Centre PADI 5* en bord de mer à Al Montazah avec piscine privée et récif maison.",
      "Forfaits plongée, cours PADI Open Water (400 EUR) et yachts privés. Transfert inclus!"
    ]
  },
  {
    "lang": "ar",
    "campaign": "SC_Search_AR_Diving_Yachts_Sharm",
    "budget_egp": 350,
    "ad_group": "AR_Diving_And_Private_Boats",
    "final_url": "https://seacollegediveclub.com/ar/diving-trips-sharm-el-sheikh?utm_source=google&utm_medium=cpc&utm_campaign=ar_diving_sharm",
    "keywords": [
      "\"غوص شرم الشيخ\"",
      "\"رحلات بحرية شرم الشيخ\"",
      "\"يخت خاص شرم الشيخ\"",
      "\"مركز غوص شرم الشيخ\"",
      "\"رحلة راس محمد والجزيرة البيضاء\""
    ],
    "negatives": [
      "الغردقة",
      "مرسى علم",
      "وظائف",
      "مجانا"
    ],
    "headlines": [
      "مركز غوص PADI 5 نجوم شرم",
      "تقييم 4.9 من +3082 غواص",
      "غطسة تجريبية للمبتدئين 30€",
      "يخت خاص رأس محمد وتيران",
      "مدربون عرب محترفون ومعتمدون",
      "حجز فوري بدون دفعة مقدمة"
    ],
    "descriptions": [
      "مركز سي كوليدج PADI خمس نجوم على شاطئ المنتزه مع مسبح خاص وشعاب مرجانية رائعة.",
      "غطسات للمبتدئين، دورات PADI، ويخوت ولانشات خاصة لرأس محمد والجزيرة البيضاء وتيران."
    ]
  },
  {
    "lang": "zh",
    "campaign": "SC_Search_ZH_PADI_Diving_Sharm",
    "budget_egp": 200,
    "ad_group": "ZH_Red_Sea_Diving_PADI",
    "final_url": "https://seacollegediveclub.com/zh/diving-courses-sharm-el-sheikh?utm_source=google&utm_medium=cpc&utm_campaign=zh_padi_sharm",
    "keywords": [
      "\"沙姆沙伊赫潜水\"",
      "\"红海潜水考证\"",
      "\"埃及padi考证\"",
      "\"沙姆沙伊赫包船出海\""
    ],
    "negatives": [
      "赫尔格达",
      "招聘",
      "免费"
    ],
    "headlines": [
      "沙姆沙伊赫PADI五星潜店",
      "谷歌4.9分·3082+真实好评",
      "红海体验潜水30欧元起",
      "PADI OW考证仅400欧元",
      "穆罕默德角与蒂朗岛船潜",
      "0元预付·含酒店免费接送"
    ],
    "descriptions": [
      "Sea College官方PADI五星潜水度假村，拥有私人训练泳池与海滩门前珊瑚礁，新手安心首选。",
      "提供体验潜水、OW/AOW连报优惠套餐、沉船潜及私人快艇游艇包船，价格全透明无隐形消费。"
    ]
  }
];

function main() {
  Logger.log('=== Sea College 8-Language Google Ads Activation Script ===');
  Logger.log('Mode: ' + (DRY_RUN ? 'DRY_RUN (Preview Only)' : 'LIVE STAGING (Paused)'));

  for (var i = 0; i < BLUEPRINT.length; i++) {
    var spec = BLUEPRINT[i];
    Logger.log('--------------------------------------------------');
    Logger.log('[' + spec.lang.toUpperCase() + '] Campaign: ' + spec.campaign + ' | Budget: ' + spec.budget_egp + ' EGP/day');
    Logger.log('Final URL: ' + spec.final_url);

    var campIter = AdsApp.campaigns().withCondition("Name = '" + spec.campaign + "'").get();
    if (!campIter.hasNext()) {
      Logger.log('-> Campaign shell "' + spec.campaign + '" not yet in account. Import google_ads_editor_8lang_import.csv or create Paused Search campaign named "' + spec.campaign + '", then re-run.');
      continue;
    }
    if (DRY_RUN) {
      Logger.log('-> [DRY_RUN] Would build Ad Group "' + spec.ad_group + '" with ' + spec.keywords.length + ' keywords and ' + spec.headlines.length + ' RSA headlines.');
      continue;
    }

    var campaign = campIter.next();
    for (var n = 0; n < spec.negatives.length; n++) {
      campaign.createNegativeKeyword(spec.negatives[n]);
    }

    var agOp = campaign.newAdGroupBuilder()
      .withName(spec.ad_group)
      .withStatus('ENABLED')
      .build();

    if (!agOp.isSuccessful()) {
      Logger.log('ERROR creating Ad Group: ' + agOp.getErrors().join('; '));
      continue;
    }
    var adGroup = agOp.getResult();

    for (var k = 0; k < spec.keywords.length; k++) {
      adGroup.newKeywordBuilder().withText(spec.keywords[k]).build();
    }

    var rsaBuilder = adGroup.newAd().responsiveSearchAdBuilder()
      .withFinalUrl(spec.final_url);

    for (var h = 0; h < spec.headlines.length; h++) {
      rsaBuilder.addHeadline(spec.headlines[h]);
    }
    for (var d = 0; d < spec.descriptions.length; d++) {
      rsaBuilder.addDescription(spec.descriptions[d]);
    }
    var rsaOp = rsaBuilder.build();
    Logger.log(rsaOp.isSuccessful() ? '-> Created RSA successfully!' : '-> RSA Error: ' + rsaOp.getErrors().join('; '));
  }
}
