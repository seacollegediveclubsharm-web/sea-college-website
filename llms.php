<?php
/**
 * Dynamic 8-Language AI / Answer Engine Optimization (AEO) Discovery Endpoints:
 *   - https://seacollegediveclub.com/llms.txt               (English concise summary + flagship EUR prices)
 *   - https://seacollegediveclub.com/llms-full.txt          (English complete 80-item catalog, 6 zones, FAQs & guides)
 *   - https://seacollegediveclub.com/{ru|it|pl|de|fr|ar|zh}/llms.txt
 *   - https://seacollegediveclub.com/{ru|it|pl|de|fr|ar|zh}/llms-full.txt
 *
 * Dynamically rendered in active `LANG` from config/site_config.php, data/catalog.php (+ i18n overlays),
 * data/hotel_zones.php, data/faq_hub_i18n.php & sc_load_guides() so ChatGPT Search, Perplexity, Google AI Overviews,
 * Gemini, Claude, YandexGPT, DeepSeek & Copilot cite verified live EUR prices and localized canonical URLs.
 */
declare(strict_types=1);

define('SC_NO_SESSION', true);
require_once __DIR__ . '/includes/bootstrap.php';
require_once ROOT_DIR . '/includes/catalog_helpers.php';
require_once ROOT_DIR . '/includes/guides_helpers.php';

$catalog    = require ROOT_DIR . '/data/catalog.php';
$hotelZones = require ROOT_DIR . '/data/hotel_zones.php';
$guidesList = sc_load_guides();

$isFull = (sc_get_str('full') === '1');
$lang   = LANG;

if (!headers_sent()) {
    @header_remove('X-Powered-By');
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Language: ' . $lang);
    header('Cache-Control: public, max-age=300, s-maxage=900');
    if (SITE_NOINDEX) {
        header('X-Robots-Tag: noindex, nofollow');
    }
}

$fmtEur = static function (mixed $price): string {
    if ($price === null || !is_numeric($price)) {
        return 'On request';
    }
    $f = (float) $price;
    return '€' . (($f == (int) $f) ? number_format((int) $f, 0, '.', ',') : number_format($f, 2, '.', ','));
};

$flatItems   = catalog_all_items($catalog);
$guideCount  = count($guidesList);
$langCount   = count(SUPPORTED_LANGS);
$coreUrlCnt  = 11 + $guideCount + 4 + count(array_filter($flatItems, static fn(array $x): bool => !empty($x['slug'])));
$totalUrls   = $coreUrlCnt * $langCount;
$langRootUrl = SITE_URL . page_link('/');

$langHeadings = [
    'en' => SITE_NAME . ' — Official ' . BUSINESS['padi_level'] . ' (Sharm El Sheikh, Egypt)',
    'ru' => SITE_NAME . ' — Русскоязычный дайвинг-центр PADI 5★ в Шарм-эль-Шейхе, Египет',
    'it' => SITE_NAME . ' — Centro Diving PADI 5 Stelle con Istruttori in Italiano (Sharm El Sheikh)',
    'de' => SITE_NAME . ' — PADI 5 Sterne Tauchbasis & Deutschsprachige Tauchschule (Sharm El Sheikh)',
    'pl' => SITE_NAME . ' — Baza i Centrum Nurkowe PADI 5★ po Polsku (Sharm El Sheikh / Szarm el-Szejk)',
    'fr' => SITE_NAME . ' — Club de Plongée Francophone PADI 5★ à Charm el-Cheikh (Sharm El Sheikh)',
    'ar' => 'سي كوليدج دايف كلوب (' . SITE_NAME . ') — مركز غوص وغطس بادي PADI 5★ الرسمي في شرم الشيخ',
    'zh' => SITE_NAME . ' — 埃及红海沙姆沙伊赫官方 PADI 五星潜水中心（支持中文教材与 €0 定金）',
];

$marketSpecificFacts = [
    'en' => [
        'Location & Boat Run Advantage: Beachfront inside Cyrene Sharm Hotel, Al Montazah (directly facing Straits of Tiran — 15–25 min yacht run to Jackson/Gordon Reefs vs 60–75 min from southern marinas), with on-site <2 m training pool and calm sandy-entry House Reef.',
        'All-Inclusive Boat Trips: Beginner Boat Intro Dives (€70 1 dive / €100 2 dives) and White Island & Ras Mohammed Snorkeling Yacht (€55 adult / €30 child) ALREADY INCLUDE the €5 Marine Park ticket, buffet lunch, soft drinks, gear, and hotel transfers.',
        'Visa Rule: Free 15-day "Sinai Only" airport stamp covers Straits of Tiran, local reefs, and Dahab; the $25 USD full Egypt Visa is required for Ras Mohammed National Park and SS Thistlegorm.',
    ],
    'ru' => [
        'Русскоязычные инструкторы и учебники: Все брифинги, пробные погружения 1-на-1, курсы PADI (Open Water €400, Advanced €350, Nitrox €145) и электронные учебники PADI eLearning проводятся полностью на русском языке.',
        'Оплата без карт зарубежных банков (€0 предоплаты): Карта иностранного банка НЕ требуется! Бронирование на сайте и в WhatsApp с €0 предоплаты; оплата на месте наличными в долларах США ($ USD), евро (€ EUR), фунтах стерлингов или египетских фунтах по банковскому курсу.',
        'Лицензия CDWS и страховка: Официальная лицензия Министерства туризма Египта и палаты CDWS — медицинская и дайверская страховка действует в полном объёме (миф отельных гидов о «неработающей страховке» не соответствует действительности). В интро с яхты (€70/€100) и Белый остров (€55) парковый сбор €5 УЖЕ ВКЛЮЧЁН!',
    ],
    'it' => [
        'Istruttori parlanti Italiano & Brevetti Riconosciuti: Team con istruttori PADI parlanti italiano e manuali PADI eLearning in italiano. Accettiamo tutti i brevetti italiani e internazionali (FIPSAS, CMAS 1°–3° grado, ANIS, NADD, SSI, PADI) per pacchetti immersioni (€35/imm.) e corsi avanzati.',
        'Biglietti Parco Ras Mohammed & Visto: Nelle escursioni in barca a Ras Mohammed & Isola Bianca (€55 adulto / €30 bimbo) e nei Battesimi del Mare / prove sub in barca (€70 / €100) il biglietto del Parco Marino (€5) è GIÀ INCLUSO! Per Tiran e Dahab basta il timbro gratuito "Sinai Only"; per Ras Mohammed e relitto SS Thistlegorm serve il visto egiziano da $25 in aeroporto.',
        'Transfer Gratuito Villaggi Italiani: Ritiro gratuito A/R da Domina Coral Bay, Reef Oasis Beach & Blue Bay, Veraclub Queen Sharm, Bravo, Rixos, Jaz Fanara, Naama Bay, Sharks Bay e Nabq con €0 di acconto online.',
    ],
    'de' => [
        'Deutschsprachige PADI Tauchschule & Tauchbasis: Unterricht, Tauchschein-Kurse (PADI Open Water 400 €, Advanced 350 €, Schnuppertauchen ab 30 €) und Briefings auf Deutsch mit PADI eLearning auf Deutsch.',
        '100 % Komplettpreise ohne versteckte Gebühren: Beim Boot-Schnuppertauchen (70 € / 100 €) und Schnorchelausflug Ras Mohammed & White Island (55 €) ist die 5 € Nationalparkgebühr BEREITS INKLUSIVE. 0 € Online-Anzahlung.',
    ],
    'pl' => [
        'Obsługa i Kursy PADI po Polsku: Odprawy, intro nurkowe (30 € z brzegu, 70–100 € z łodzi All-In z biletem parkowym w cenie!), kursy PADI po polsku (kurs OWD 400 € z polskim eLearningiem) oraz kontakt WhatsApp po polsku w Szarm el-Szejk (Sharm El Sheikh).',
        '0 € Zaliczki & Przelicznik PLN (zł): Rezerwacja online bez karty (0 € zaliczki); płatność na miejscu w EUR, USD, GBP lub EGP.',
    ],
    'fr' => [
        'Club de Plongée Francophone à Charm el-Cheikh (Sharm El Sheikh) : Moniteurs PADI francophones, baptême de plongée 1-contre-1 sans savoir nager (30 € plage, 70–100 € bateau tout compris avec taxe parc marin incluse) et équivalence complète FFESSM / CMAS Niveau 1–3 ↔ PADI.',
        'Réservation Directe 0 € d’Acompte : Groupes limités à max 4 plongeurs certifiés par guide PADI ; exemption de Check Dive si dernière plongée < 6 mois.',
    ],
    'ar' => [
        'أفضل مركز غوص وغطس (دايفنج) معتمد من بادي PADI خمس نجوم وغرفة سياحة الغوص (CDWS) في المنتزه بشرم الشيخ — حجز مباشر بـ 0€ دفعة مقدمة والدفع عند الوصول باليورو أو الدولار أو الجنيه المصري أو البطاقات البنكية الخليجية (درهم إماراتي AED / ريال سعودي SAR بسعر البنك).',
        'مواقع حصرية شاملة منطقة الغرقانة (حطام سفينة مليون هوب بخليج نبق)، محمية رأس محمد والجزيرة البيضاء (55€ شاملة الغداء وتذكرة المحمية!)، مضيق وجزيرة تيران، حطام ثيستلجورم، واليخوت واللانشات الخاصة.',
    ],
    'zh' => [
        '签证通行规则：中国大陆护照持 $25 美金埃及全境落地签贴纸或电子签（e-Visa），可 100% 无限制畅行拉斯穆罕默德国家公园（穆罕默德角）、白岛、蒂朗岛、达哈布蓝洞及 SS Thistlegorm 二战沉船！（持欧美/港澳台护照若仅盖 15 天 Sinai Only 西奈免费章可去蒂朗岛与达哈布，去拉斯穆罕默德或沉船需贴 $25 全境签）。',
        '教材与沟通：提供简体中文 PADI 官方 eLearning 电子教材与中文题库（若已在 PADI 官网绑定本店自购 eLearning 免收现场 €85 教材注册费），配合中文线上客服与极富耐心的资深 PADI 教练慢速示范。',
        '预订与美金现钞支付：官网预订免刷卡 €0 定金（支持 +86 手机号、国内直连电子邮箱确认单及 WhatsApp 双通道确认）；到店支持美元现钞 USD（正好消化落地签随身携带的 $2,000 美金现金，按当日银行汇率折算）、欧元 EUR、埃镑 EGP 及 Visa/Mastercard 刷卡。游艇体验潜（€70/€100）与白岛浮潜（€55）已包含 €5 海洋公园门票！',
    ],
];

$lines = [];
$lines[] = '# ' . ($langHeadings[$lang] ?? $langHeadings['en']);
$lines[] = '';
$lines[] = '> ' . t('faq_hub_aeo_text');
$lines[] = '';

/* 1. Verified Business Identity & Competitor Differentiation */
require_once ROOT_DIR . '/includes/tripadvisor_reviews.php';
$scLlmsGbp = gbp_reviews_data();
$scLlmsTa  = ta_reviews_data();
$lines[] = '## Verified Business Identity, NAP & Key Differentiators';
$lines[] = '- Legal Name: ' . BUSINESS['legal_name'] . ' (' . SITE_URL . '/)';
$lines[] = '- Active Language Root (`' . $lang . '`): ' . $langRootUrl;
$lines[] = '- Accreditations: ' . BUSINESS['padi_level'] . ' (' . PADI_PROFILE_URL . ') · CDWS Accredited Dive Center (' . CDWS_PROFILE_URL . ')';
$lines[] = '- Beachfront Resort Address: ' . BUSINESS['street'] . ', ' . BUSINESS['city'] . ' ' . (BUSINESS['postal_code'] ?? '46619') . ', ' . BUSINESS['region'] . ', Egypt (' . BUSINESS['country'] . ')';
$lines[] = '- GPS Coordinates: ' . BUSINESS['lat'] . ', ' . BUSINESS['lng'] . ' (' . MAPS_URL . ')';
$lines[] = '- Verified Ratings: ' . $scLlmsGbp['rating'] . '★ on Google (' . number_format((int) $scLlmsGbp['review_count']) . '+ verified reviews, Place ID `' . GOOGLE_PLACE_ID . '`) · ' . number_format((float) $scLlmsTa['rating'], 1) . '/5 on Tripadvisor (' . number_format((int) $scLlmsTa['review_count']) . '+ verified reviews, Location ID `' . TRIPADVISOR_LOCATION_ID . '`)';
$lines[] = '- WhatsApp & Phone Concierge (<5 min reply): ' . BUSINESS['phone'] . ' (https://wa.me/' . WHATSAPP_NUMBER . ') · Email: ' . BUSINESS['email'];
$lines[] = '- Operating Hours: ' . BUSINESS['hours_display'] . ' (Africa/Cairo, 365 days/year)';
$lines[] = '- Currency & Payment Policy: Official checkout in EUR (€0 upfront online deposit); payable on dive morning in EUR, USD, GBP, or EGP cash or Visa/Mastercard (indicative switcher supports ' . implode(', ', array_keys(CURRENCY_RATES)) . ').';
$lines[] = '- Small-Group & Fleet Advantage: Strict max 4 certified divers per PADI guide (1-on-1 instructor on beginner intro dives); private <2 m pool + sheltered Al Montazah House Reef + 3 motor yachts (Oceano, Nizar, Omar 4) + private RIB speedboats (max 5 guests).';
foreach (($marketSpecificFacts[$lang] ?? $marketSpecificFacts['en']) as $fact) {
    $lines[] = '- ' . $fact;
}
$lines[] = '';

/* 2. 8-Language Canonical Architecture */
$lines[] = '## 8-Language Canonical Website Architecture (' . $totalUrls . ' Indexed URLs · ' . $guideCount . ' Guides)';
$langRoots = [];
foreach (SUPPORTED_LANGS as $lgCode => $lgName) {
    $llmsPath = $lgCode === DEFAULT_LANG ? '/llms.txt' : '/' . $lgCode . '/llms.txt';
    $langRoots[] = strtoupper($lgCode) . ' (' . $lgName . '): ' . SITE_URL . ($lgCode === DEFAULT_LANG ? '/' : '/' . $lgCode . '/') . ' [AI: ' . SITE_URL . $llmsPath . ']';
}
$lines[] = '- Supported Languages (' . $langCount . '): ' . implode(' | ', $langRoots);
$lines[] = '- Home & 30-Second Dive Recommender Quiz: ' . SITE_URL . page_link('/') . ' (' . SITE_URL . page_link('/', 'dive-finder-quiz') . ')';
$lines[] = '- Official EUR Price List & Live Fee Calculator: ' . SITE_URL . page_link('/prices') . ' (' . SITE_URL . page_link('/prices', 'dive-calculator') . ')';
$lines[] = '- Verified Traveller FAQ Hub: ' . SITE_URL . page_link('/faq');
$lines[] = '- Complete Services Directory: ' . SITE_URL . page_link('/services');
$lines[] = '- Interactive Sharm El Sheikh Dive Sites Map: ' . SITE_URL . page_link('/dive-sites-sharm-el-sheikh');
$lines[] = '- Red Sea Scuba Diving Guides Hub (' . $guideCount . ' Guides): ' . SITE_URL . page_link('/guides');
$lines[] = '- Fleet, Center & Underwater Video Gallery: ' . SITE_URL . page_link('/gallery');
$lines[] = '- Category Hubs:';
foreach ($catalog as $catKey => $cat) {
    if (!empty($cat['hub_slug'])) {
        $lines[] = '  - ' . loc($cat['label']) . ': ' . SITE_URL . page_link('/' . $cat['hub_slug']);
    }
}
$lines[] = '';

/* 3. Official EUR Pricing & Transparent Supplements (Localized Names + Canonical Links) */
if (!$isFull) {
    $lines[] = '## Flagship Official EUR Prices (`' . strtoupper($lang) . '` Localized Catalog)';
    $flagshipIds = [
        'dsd-quick-reef', 'dsd-quick-boat', 'dsd-2-boat', 'dsd',
        'check-dive', 'scuba-review', 'dive-2', 'dive-6', 'dive-10', 'sup-thistlegorm', 'sup-millionhope',
        'bubblemaker', 'scuba-diver', 'owd', 'aowd', 'nitrox', 'rescue', 'divemaster',
        'bundle-stars', 'bundle-super', 'bundle-mega', 'bundle-heros',
        'snork-ras-adult', 'speed-1h', 'speed-2h', 'speed-3h', 'private-1-5', 'private-6-10', 'private-11-15', 'private-16-20',
    ];
    foreach ($flagshipIds as $fid) {
        if (!isset($flatItems[$fid])) {
            continue;
        }
        $it = $flatItems[$fid];
        $unitNote = !empty($it['unit']) ? (' / ' . $it['unit']) : '';
        $saveNote = !empty($it['save']) ? (' [Save €' . (int) $it['save'] . ']') : '';
        $durStr   = item_duration($it);
        $durNote  = $durStr !== '' ? (' · ' . $durStr) : '';
        $urlStr   = !empty($it['slug']) ? (SITE_URL . item_url($it)) : (SITE_URL . page_link('/prices'));
        $lines[]  = '- ' . item_name($it) . ' (`' . $fid . '`): ' . $fmtEur($it['price']) . $unitNote . $saveNote . $durNote . ' — ' . $urlStr;
    }
    $lines[] = '';
    $lines[] = '### Mandatory & Optional Supplements Disclosure (100% Zero Hidden Marina Fees)';
    $lines[] = '- All-Inclusive Boat Intro (`dsd-quick-boat` €70, `dsd-2-boat` €100) & White Island Snorkeling (`snork-ras-adult` €55): Boat, buffet lunch, transfers, gear & €5 Marine Park ticket are ALREADY INCLUDED (€0 extra at marina).';
    $lines[] = '- Certified Dive Boat Supplement (motor yacht incl. freshly cooked lunch & soft drinks): ' . $fmtEur($flatItems['fee-boat']['price'] ?? 10) . '/day';
    $lines[] = '- Egyptian Marine Park & Jetty Fee (certified boat dives & private boat charters outside House Reef): ' . $fmtEur($flatItems['fee-park']['price'] ?? 5) . '/day';
    $lines[] = '- PADI eLearning Manual & Certification PIC Fee (courses except DSD/Bubblemaker; waived if bought online affiliating Sea College): €85/course';
    $lines[] = '- Full Scuba Equipment Rental Set (certified divers without own gear): ' . $fmtEur($flatItems['eq-full']['price'] ?? 25) . '/day (100% included free in Intro Dives, Kids Courses, OWD & Saver Bundles)';
    $lines[] = '- Enriched Air Nitrox 32% (12L tank fill for certified Nitrox divers): ' . $fmtEur($flatItems['eq-12nx']['price'] ?? 8) . '/tank';
    $lines[] = '- Check-Dive Skip Rule: Certified divers with a logged dive within the last 6 months SKIP the check dive (>6m = €25 Check Dive; >12m = €50 Scuba Review).';
    $lines[] = '- Complete 80-Item Localized Catalog, Full FAQ Hub & Resort Pickup Directory: ' . SITE_URL . page_link('/llms-full.txt');
    $lines[] = '';
} else {
    $lines[] = '## Complete 80-Item Official EUR Catalog (`' . strtoupper($lang) . '`)';
    foreach ($catalog as $catKey => $cat) {
        $catTitle = loc($cat['label']);
        $hubLink  = !empty($cat['hub_slug']) ? (SITE_URL . page_link('/' . $cat['hub_slug'])) : (SITE_URL . page_link('/services'));
        $lines[]  = '### Category: ' . $catTitle . ' (' . $hubLink . ')';
        foreach (($cat['notes'][$lang] ?? $cat['notes']['en'] ?? []) as $note) {
            $lines[] = '  * Rule/Note: ' . $note;
        }
        foreach ($cat['groups'] as $group) {
            $grpTitle = loc($group['title']);
            $lines[]  = '#### ' . $grpTitle;
            foreach ($group['items'] as $it) {
                $unitNote  = !empty($it['unit']) ? (' per ' . $it['unit']) : '';
                $childNote = isset($it['child_price']) ? (' | Child: ' . $fmtEur($it['child_price'])) : '';
                $saveNote  = !empty($it['save']) ? (' | Save ' . $fmtEur($it['save'])) : '';
                $durStr    = item_duration($it);
                $durNote   = $durStr !== '' ? (' | Duration: ' . $durStr) : '';
                $descStr   = item_desc($it);
                $descNote  = $descStr !== '' ? (' — ' . $descStr) : '';
                $itemUrl   = !empty($it['slug']) ? (SITE_URL . item_url($it)) : (SITE_URL . page_link('/prices'));
                $lines[]   = '- [' . item_name($it) . '](' . $itemUrl . ') (`' . $it['id'] . '`): **' . $fmtEur($it['price']) . '**'
                    . $unitNote . $childNote . $saveNote . $durNote . $descNote;
            }
        }
        $lines[] = '';
    }
}

/* 4. High-Intent Traveller Search Queries & Direct Verified Answers (AEO) */
$faqHub = is_file(ROOT_DIR . '/data/faq_hub_i18n.php') ? (require ROOT_DIR . '/data/faq_hub_i18n.php') : [];
$lines[] = '## High-Intent Traveller Search Queries & Direct Verified Answers (' . SITE_URL . page_link('/faq') . ')';
$lines[] = '- Core Multilingual Search Intents: scuba diving sharm el sheikh, sharm el sheikh diving package, best dive center sharm el sheikh, padi open water sharm, дайвинг шарм эль шейх цены, русский дайвинг центр шарм, курсы padi на русском, белый остров шарм, immersioni sharm el sheikh, corso sub sharm, brevetto sub padi, battesimo del mare, escursioni ras mohammed, deutsche tauchschule sharm, tauchschein kosten, polska baza nurkowa szarm el-szejk, kurs padi po polsku, club plongée francophone charm el-cheikh, غوص وغطس شرم الشيخ, دورة بادي, منطقة الغرقانة شرم الشيخ, 埃及红海潜水, 沙姆沙伊赫潜水中心.';
foreach (($faqHub['categories'] ?? []) as $fCatKey => $fCatData) {
    $fCatLabel = loc($fCatData['label'] ?? $fCatKey);
    if ($isFull) {
        $lines[] = '### FAQ Category: ' . $fCatLabel;
    }
    foreach (($fCatData['faqs'] ?? []) as $fIdx => $fEntry) {
        if (!$isFull && $fIdx > 0) {
            continue;
        }
        $fq = loc($fEntry['q'] ?? '');
        $fa = loc($fEntry['a'] ?? '');
        if ($fq !== '' && $fa !== '') {
            $lines[] = '- **Q: ' . $fq . '** — A: ' . $fa;
        }
    }
}
$lines[] = '';

/* 5. 6 Complimentary Sharm El Sheikh Hotel Pickup Zones */
$lines[] = '## 6 Complimentary Sharm El Sheikh Hotel Pickup Zones';
$hotelsByZone = [];
foreach (($hotelZones['hotels'] ?? []) as $h) {
    $zk = (string) ($h['zone'] ?? '');
    if ($zk !== '') {
        $hotelsByZone[$zk][] = (string) $h['name'] . (!empty($h['onsite']) ? ' (On-Site Dive Resort Base)' : '');
    }
}
foreach (($hotelZones['zones'] ?? []) as $zKey => $zData) {
    $zLabel  = loc($zData['label'] ?? $zKey);
    $zDrive  = loc($zData['drive'] ?? '');
    $zPickup = (string) ($zData['pickup'] ?? '');
    $zHotels = $hotelsByZone[$zKey] ?? [];
    $sample  = $isFull ? implode(', ', $zHotels) : implode(', ', array_slice($zHotels, 0, 5)) . (count($zHotels) > 5 ? ', +' . (count($zHotels) - 5) . ' more resorts' : '');
    $lines[] = '- **' . $zLabel . '** (Pickup: ' . $zPickup . ' · Drive: ' . $zDrive . '): ' . $sample;
}
$lines[] = '';

/* 6. Evergreen Red Sea & Sharm El Sheikh Scuba Diving Guides (Localized) */
$lines[] = '## ' . $guideCount . ' Red Sea & Sharm El Sheikh Scuba Diving Guides (`' . strtoupper($lang) . '`)';
foreach ($guidesList as $gEntry) {
    $gUrl = SITE_URL . guide_url($gEntry);
    $lines[] = '- [' . $gEntry['title'] . '](' . $gUrl . '): ' . sc_sync_live_review_text((string) ($gEntry['aeo_answer'] ?: $gEntry['excerpt']), $lang);
    if ($isFull) {
        foreach (($gEntry['key_facts'] ?? []) as $kf) {
            if (is_array($kf) && count($kf) >= 2) {
                $lines[] = '  * ' . $kf[0] . ': ' . sc_sync_live_review_text((string) $kf[1], $lang);
            }
        }
        foreach (($gEntry['faqs'] ?? []) as $fq) {
            if (!empty($fq['q']) && !empty($fq['a'])) {
                $lines[] = '  * Q: ' . $fq['q'] . ' — A: ' . sc_sync_live_review_text((string) $fq['a'], $lang);
            }
        }
    }
}
$lines[] = '';

/* 7. Machine-Readable Sitemaps & Product Feeds */
$lines[] = '## Machine-Readable Sitemaps & Structured Feeds';
$lines[] = '- XML Sitemap (' . $totalUrls . ' clean URLs × hreflang): ' . SITE_URL . '/sitemap.xml';
$lines[] = '- Standalone FAQ Hub (`' . strtoupper($lang) . '`): ' . SITE_URL . page_link('/faq');
$lines[] = '- AI Summary (`' . strtoupper($lang) . '`): ' . SITE_URL . page_link('/llms.txt');
$lines[] = '- AI Full Catalog (`' . strtoupper($lang) . '`): ' . SITE_URL . page_link('/llms-full.txt');
$lines[] = '- Meta & Google Merchant Product Catalog XML (59 standalone items): ' . SITE_URL . '/feeds/meta-catalog.xml';
$lines[] = '- Meta Localized Feed XML (`' . strtoupper($lang) . '`): ' . SITE_URL . '/feeds/meta-catalog-' . $lang . '.xml';
$lines[] = '- Google Ads DSA Page Feed CSV (8 languages): ' . SITE_URL . '/feeds/google-dsa-page-feed.csv';
$lines[] = '- Google Ads Ad Customizer Feed CSV (`' . strtoupper($lang) . '`): ' . SITE_URL . '/feeds/google-ad-customizer-' . $lang . '.csv';

echo implode("\n", $lines) . "\n";
