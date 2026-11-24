<?php
/**
 * Lara — Multilingual Diver Concierge & Meta/WhatsApp Inbox Response Engine.
 *
 * Powers both:
 *   1) Real-time 24/7 Meta Webhook auto-replies (`handlers/lara_meta_webhook.php`) across
 *      Facebook Messenger, Instagram Direct Messages, and WhatsApp Business Cloud API.
 *   2) Backlog unanswered thread sweeps (`php docs/lara_meta_inbox.php sweep-unanswered --send`).
 *
 * Reads live EUR prices dynamically from `data/catalog.php` (`includes/live_prices.php`)
 * and Sharm hotel pickup zones from `data/hotel_zones.php`.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/site_config.php';
require_once __DIR__ . '/live_prices.php';

/**
 * Load flat map of item_id => live EUR price from master catalog (with Google Sheet discounts applied).
 *
 * @return array<string, float>
 */
function lara_live_price_map(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = [];
    if (!defined('LANG')) {
        define('LANG', 'en');
    }
    require_once __DIR__ . '/i18n_data.php';
    $cat = require dirname(__DIR__) . '/data/catalog.php';
    foreach ($cat as $c) {
        foreach ($c['groups'] as $g) {
            foreach ($g['items'] as $it) {
                if (isset($it['id'], $it['price'])) {
                    $cache[(string) $it['id']] = (float) $it['price'];
                }
            }
        }
    }
    return $cache;
}

function lara_eur(string $id, float $fallback): string
{
    $map = lara_live_price_map();
    $v = $map[$id] ?? $fallback;
    return '€' . (floor($v) == $v ? (string) (int) $v : number_format($v, 2, '.', ''));
}

/**
 * Detect guest language (`ar`, `ru`, `zh`, `pl`, `it`, `de`, `fr`, `es`, `en`).
 */
function lara_detect_language(string $text): string
{
    $t = trim($text);
    if ($t === '') {
        return 'en';
    }
    if (preg_match('/\p{Arabic}/u', $t)) {
        return 'ar';
    }
    if (preg_match('/\p{Cyrillic}/u', $t)) {
        return 'ru';
    }
    if (preg_match('/\p{Han}/u', $t)) {
        return 'zh';
    }
    $low = mb_strtolower($t, 'UTF-8');
    if (preg_match('/[ąćęłńśźż]|\b(dzień dobry|cześć|witam|ile kosztuje|nurkowanie|cena|ceny)\b/u', $low)) {
        return 'pl';
    }
    if (preg_match('/\b(ciao|buongiorno|buonasera|salve|quanto costa|prezzo|prezzi|immersioni|immersione)\b/u', $low)) {
        return 'it';
    }
    if (preg_match('/[äöüß]|\b(guten tag|hallo|tauchen|tauchkurs|was kostet|preis|preise|schnorcheln)\b/u', $low)) {
        return 'de';
    }
    if (preg_match('/\b(bonjour|bonsoir|salut|plongée|plongee|combien|tarif|tarifs|baptême)\b/u', $low)) {
        return 'fr';
    }
    if (preg_match('/[¿¡ñ]|\b(hola|buenos días|buenos dias|buenas|cuánto cuesta|cuanto cuesta|buceo|precios)\b/u', $low)) {
        return 'es';
    }
    return 'en';
}

/**
 * Classify incoming customer message intent.
 */
function lara_classify_intent(string $text): string
{
    $low = mb_strtolower(trim($text), 'UTF-8');
    if ($low === '') {
        return 'general';
    }
    if (preg_match('/(\b(refund|complaint|angry|scam|emergency|hospital|accident|cancel my paid)\b|استرداد|شكوى|طوارئ|возврат|жалоб|退款|投诉)/u', $low)) {
        return 'escalation';
    }
    if (preg_match('/(\b(private boat|private yacht|yacht|speedboat|vip|charter|barca privata|privatboot|bateau privé)\b|مركب خاص|يخت|لانش|яхт|частн|私人游艇|包船)/u', $low)) {
        return 'private_boat';
    }
    if (preg_match('/(\b(open water|owd|advanced|aowd|padi course|rescue diver|divemaster|certification|license|licence|e-learning|bundle|corso|brevetto|tauchkurs|brevet)\b|كورس|دورة|رخصة|курс|сертификат|обучен|考证|课程)/u', $low)) {
        return 'courses';
    }
    if (preg_match('/(\b(first time|beginner|never dived|try dive|intro|discover scuba|dsd|bubblemaker|snorkel|snorkeling|white island|couple|non-diver|wife|husband|partner|principiante|prima volta|anfänger|schnorcheln|débutant)\b|أول مرة|مبتدئ|انترودايف|سنوركل|нович|первый раз|интро|сноркл|新手|体验潜|浮潜)/u', $low)) {
        return 'beginner';
    }
    if (preg_match('/(\b(ras mohammed|ras mohamed|tiran|thistlegorm|wreck|certified|dive pack|check dive|nitrox|10 dives|6 dives|daily diving)\b|رأس محمد|راس محمد|تيران|ثيسلجورم|غواص معتمد|рас мохаммед|тиран|тистлегорм|дейли|сертифицир|持证|穆罕默德|蒂朗)/u', $low)) {
        return 'certified';
    }
    if (preg_match('/(\b(pickup|pick up|transfer|hotel|zone|what time|morning|main gate)\b|توصيل|باص|فندق|موعد التحرك|трансфер|отел|во сколько|接送|酒店)/u', $low)) {
        return 'hotel_pickup';
    }
    if (preg_match('/(\b(check-in|checkin|passport|egp|usd|gbp|dollar|currency|exchange rate|cash|card)\b|جنيه|دولار|جواز|عملة|فيزا|паспорт|валют|доллар|фунт|护照|汇率)/u', $low)) {
        return 'checkin_currency';
    }
    return 'general';
}

/**
 * Match a mentioned Sharm hotel against the 6 pickup zones (`data/hotel_zones.php`).
 *
 * @return array{hotel:string,zone_name:string,window:string}|null
 */
function lara_match_hotel_zone(string $text): ?array
{
    $zonesFile = dirname(__DIR__) . '/data/hotel_zones.php';
    if (!is_file($zonesFile)) {
        return null;
    }
    $zones = require $zonesFile;
    if (!is_array($zones)) {
        return null;
    }
    $low = mb_strtolower($text, 'UTF-8');
    foreach ($zones as $z) {
        $win = (string) ($z['pickup_window'] ?? $z['window'] ?? '07:30–07:50');
        $zName = (string) ($z['name']['en'] ?? $z['title'] ?? $z['id'] ?? 'Sharm El Sheikh');
        foreach (($z['hotels'] ?? []) as $h) {
            $hName = is_array($h) ? (string) ($h['name'] ?? '') : (string) $h;
            if ($hName !== '' && mb_strlen($hName, 'UTF-8') >= 5 && str_contains($low, mb_strtolower($hName, 'UTF-8'))) {
                return ['hotel' => $hName, 'zone_name' => $zName, 'window' => $win];
            }
        }
    }
    return null;
}

function lara_lang_prefix(string $lang): string
{
    return in_array($lang, ['ru', 'it', 'pl', 'de', 'fr', 'ar', 'zh'], true) ? ('/' . $lang) : '';
}

/**
 * Generate Lara's complete, verified multilingual reply + structured metadata.
 *
 * @return array{lang:string,intent:string,urgent_human:bool,reply:string}
 */
function lara_generate_reply(string $guestMessage, string $channel = 'messenger', string $guestName = ''): array
{
    $lang = lara_detect_language($guestMessage);
    $intent = lara_classify_intent($guestMessage);
    $lp = lara_lang_prefix($lang);
    $base = SITE_URL . $lp;
    $waUrl = 'https://wa.me/' . WHATSAPP_NUMBER;
    $cleanName = trim((string) preg_replace('/[^\p{L}\s\'-]/u', '', $guestName));
    $firstName = $cleanName !== '' ? explode(' ', $cleanName)[0] : '';

    // Live EUR prices (auto-synced with Master EUR Sheet)
    $pIntroShore1 = lara_eur('intro-shore-1', 30);
    $pIntroBoat1  = lara_eur('intro-boat-1', 70);
    $pIntroBoat2  = lara_eur('intro-boat-2', 100);
    $pDsd         = lara_eur('dsd', 85);
    $pOwd         = lara_eur('owd', 400);
    $pAowd        = lara_eur('aowd', 350);
    $pNitrox      = lara_eur('nitrox-course', 145);
    $pStars       = lara_eur('bundle-stars', 500);
    $pMega        = lara_eur('bundle-mega', 600);
    $pDive2       = lara_eur('dive-2', 85);
    $pDive6       = lara_eur('dive-6', 255);
    $pDive10      = lara_eur('dive-10', 350);
    $pThistle     = lara_eur('thistlegorm', 150);
    $pSnorkel     = lara_eur('white-island-adult', 55);
    $pSnorkelKid  = lara_eur('white-island-child', 30);
    $pBoat10      = lara_eur('private-boat-10', 550);
    $pBoat15      = lara_eur('private-boat-15', 900);
    $pBoat22      = lara_eur('private-boat-22', 1100);

    if ($intent === 'escalation') {
        $msg = match ($lang) {
            'ar' => "مرحباً بك من Sea College Dive Club (مركز غوص PADI 5 نجوم بشرم الشيخ) 🌊\nمعك لارا من خدمة العملاء. تم تحويل رسالتك فوراً إلى مدير العمليات لمراجعتها والرد عليك بشكل فوري ومباشر.\nللتواصل الفوري عبر واتساب أو الاتصال المباشر: +201114567886 ({$waUrl})",
            'ru' => "Здравствуйте! Это Лара, Sea College Dive Club (PADI 5★ Resort, Шарм-эль-Шейх) 🌊\nЯ сразу передала ваше сообщение старшему менеджеру — мы свяжемся с вами в приоритетном порядке.\nПрямая связь в WhatsApp / телефон: +201114567886 ({$waUrl})",
            default => "Hello" . ($firstName ? " {$firstName}" : "") . "! This is Lara at Sea College Dive Club (PADI 5★ Dive Resort, Sharm El Sheikh) 🌊\nI have immediately escalated your message to our Operations Manager for priority personal assistance.\nFor instant direct WhatsApp or phone support: +201114567886 ({$waUrl})",
        };
        return ['lang' => $lang, 'intent' => $intent, 'urgent_human' => true, 'reply' => $msg];
    }

    // Arabic native responses
    if ($lang === 'ar') {
        $reply = match ($intent) {
            'beginner' => "أهلاً بك في Sea College Dive Club (مركز PADI 5 نجوم في شرم الشيخ) 🤿🌊 معك لارا!\nنرحب جداً بالمبتدئين وبدون أي خبرة سابقة مع مدرب خاص خطوة بخطوة:\n• غطسة تعريفية من الشاطئ (House Reef): {$pIntroShore1}\n• رحلة يخت ليوم كامل مع غطسة تعريفية: {$pIntroBoat1} (أو غطستين {$pIntroBoat2}) شاملة المعدات والغداء والمشروبات\n• برنامج PADI Discover Scuba Diving الرسمي: {$pDsd}\n• رحلة يخت جزيرة وايت آيلاند ورأس محمد (سنوركلينج): {$pSnorkel} للفرد ({$pSnorkelKid} للطفل)\n\nاحسب السعر الشامل أو احجز فوراً من موقعنا:\n{$base}/prices#calculator\nأو أرسل لنا على واتساب تاريخ رحلتك واسم الفندق وعدد الأفراد: {$waUrl}",
            'courses' => "أهلاً بك في Sea College Dive Club (مركز PADI 5 نجوم بشرم الشيخ) 🎓🤿 معك لارا!\nأقوى دورات وباقات PADI المعتمدة دولياً (شاملة التدريب بحد أقصى 4 غواصين لكل مدرب):\n• دورة PADI Open Water للمبتدئين: {$pOwd}\n• دورة PADI Advanced Open Water: {$pAowd}\n• تخصص الهواء المخصب Nitrox: {$pNitrox}\n• باقة Stars (OWD + AOWD): {$pStars} | باقة Mega: {$pMega}\n\nتفاصيل جميع الدورات والحجز المباشر:\n{$base}/diving-courses-sharm-el-sheikh\nيسعدنا أيضاً متابعتك فوراً عبر واتساب: {$waUrl} — ما هو موعد وصولك إلى شرم الشيخ؟",
            'certified' => "أهلاً بك في Sea College Dive Club (PADI 5★ Resort شرم الشيخ) 🐠🤿 معك لارا!\nللغواصين المعتمدين نضمن مجموعات صغيرة (بحد أقصى 4 غواصين فقط لكل مرشد PADI!) بدون أي رسوم مخفية:\n• باقات الغوص اليومي: غطستان {$pDive2} | 6 غطسات {$pDive6} | 10 غطسات {$pDive10}\n• حطام سفينة SS Thistlegorm الأسطوري: {$pThistle}\n• للمرافق غير الغواص معك على نفس اليخت شاملة غطسة تعريفية والغداء والانتقالات: +€45 فقط!\n• إذا غصت مؤخراً يمكنك تجاوز غطسة الاختبار بإرسال صورة كمبيوتر الغوص.\n\nاحسب باقتك بدقة هنا: {$base}/prices#calculator\nأو تواصل معنا على واتساب لتأكيد مكانك: {$waUrl}",
            'private_boat' => "أهلاً بك في Sea College Dive Club 🛥️🌊 معك لارا!\nنوفر يخوت ولانشات سريعة خاصة VIP في شرم الشيخ (رأس محمد وجزيرة تيران):\n• يخت خاص حتى 10 أفراد: {$pBoat10}\n• يخت خاص من 11 إلى 15 فرداً: {$pBoat15}\n• يخت خاص من 16 إلى 22 فرداً: {$pBoat22}\n• لانشات سريعة خاصة (Speedboat): تبدأ من €75\n\nشاهد الأسطول والتفاصيل: {$base}/snorkeling-and-boats-sharm-el-sheikh\nأرسل لنا التاريخ وعدد الضيوف هنا أو على واتساب للتأكيد الفوري: {$waUrl}",
            default => "أهلاً وسهلاً بك في Sea College Dive Club (مركز غوص PADI 5 نجوم بشرم الشيخ) 🌊🤿 معك لارا!\nيسعدنا مساعدتك فوراً — أسعارنا رسمية وشاملة بدون أي مصاريف مخفية:\n• غطس المبتدئين (بدون خبرة): {$pIntroShore1} شاطئ | {$pIntroBoat1}–{$pIntroBoat2} باليخت\n• الغوص اليومي للمعتمدين (حد أقصى 4 غواصين/مرشد): من {$pDive2}\n• دورة PADI Open Water: {$pOwd} | سنوركلينج باليخت: {$pSnorkel}\n\nللحجز المباشر أو حساب التكلفة: {$base}/prices#calculator\nوللتأكيد السريع عبر واتساب (+201114567886): {$waUrl}\nأخبرنا بموعد زيارتك وعدد الأفراد وهل أنتم مبتدئون أم غواصون معتمدون؟",
        };
        return ['lang' => $lang, 'intent' => $intent, 'urgent_human' => false, 'reply' => $reply];
    }

    // Russian native responses
    if ($lang === 'ru') {
        $reply = match ($intent) {
            'beginner' => "Здравствуйте! Это Лара, Sea College Dive Club (PADI 5★ Resort, Шарм-эль-Шейх) 🤿🌊\nДля новичков без опыта (индивидуально с инструктором, 0% скрытых доплат):\n• Пробное погружение с домашнего рифа: {$pIntroShore1}\n• Прогулка на яхте + 1 погружение: {$pIntroBoat1} (2 погружения: {$pIntroBoat2}) — обед и снаряжение включены!\n• Программа PADI Discover Scuba Diving: {$pDsd}\n• Яхта Белый остров и Рас-Мохаммед (снорклинг): {$pSnorkel} взр. / {$pSnorkelKid} дет.\n\nТочный калькулятор цен и бронь: {$base}/prices#calculator\nИли напишите нам в WhatsApp (+201114567886): {$waUrl} — в какие даты вы в Шарме и в каком отеле?",
            'courses' => "Здравствуйте! Это Лара, Sea College Dive Club (PADI 5★ Resort, Шарм-эль-Шейх) 🎓🤿\nОфициальные курсы и пакеты PADI (мини-группы максимум 4 дайвера на гида!):\n• PADI Open Water Diver: {$pOwd}\n• PADI Advanced Open Water: {$pAowd}\n• Спецкурс Nitrox: {$pNitrox}\n• Пакет Stars (OWD + AOWD): {$pStars} | Mega: {$pMega}\n\nВсе курсы и онлайн-запись: {$base}/diving-courses-sharm-el-sheikh\nБыстрая бронь в WhatsApp: {$waUrl} — подскажите ваши даты прилёта!",
            'certified' => "Здравствуйте! Это Лара, Sea College Dive Club (PADI 5★ Resort) 🐠🤿\nДля сертифицированных дайверов у нас максимум 4 дайвера на PADI-гида и честные цены в EUR без скрытых сборов:\n• Пакеты погружений: 2 дайва {$pDive2} | 6 дайвов {$pDive6} | 10 дайвов {$pDive10}\n• Легендарный рэк SS Thistlegorm: {$pThistle}\n• Пара на одной яхте: партнёр без сертификата может пойти с вами (+1 интро-дайв и обед всего +€45)!\n\nРассчитать пакет онлайн: {$base}/prices#calculator\nИли напишите даты и уровень сертификата здесь или в WhatsApp: {$waUrl}",
            default => "Здравствуйте! Это Лара, Sea College Dive Club (официальный PADI 5★ дайв-курорт в Шарм-эль-Шейхе) 🌊🤿\nБудем рады организовать ваш дайвинг или морскую прогулку без скрытых доплат:\n• Новичкам (интро-дайв): {$pIntroShore1} риф | {$pIntroBoat1}–{$pIntroBoat2} с яхты\n• Сертифицированным (макс. 4 дайвера на гида): от {$pDive2}\n• Курс PADI Open Water: {$pOwd} | Снорклинг на яхте: {$pSnorkel}\n\nКалькулятор и цены на русском: {$base}/prices#calculator\nПрямой WhatsApp (+201114567886): {$waUrl}\nПодскажите, пожалуйста: даты в Шарме, отель и сколько человек?",
        };
        return ['lang' => $lang, 'intent' => $intent, 'urgent_human' => false, 'reply' => $reply];
    }

    // Localized compact greetings for IT / DE / FR / PL / ZH / ES
    if (in_array($lang, ['it', 'de', 'fr', 'pl', 'zh', 'es'], true)) {
        $reply = match ($lang) {
            'it' => "Ciao" . ($firstName ? " {$firstName}" : "") . "! Sono Lara del Sea College Dive Club (PADI 5★ Resort, Sharm El Sheikh) 🌊🤿\nPrezzi ufficiali trasparenti (zero costi nascosti, max 4 sub per guida PADI):\n• Prima immersione (principianti): {$pIntroShore1} riva | {$pIntroBoat1}–{$pIntroBoat2} in barca | DSD {$pDsd}\n• Sub brevettati (Ras Mohammed & Tiran): 2 immersioni {$pDive2} | 6 imm. {$pDive6} | 10 imm. {$pDive10}\n• Corso PADI Open Water: {$pOwd} | Snorkeling White Island: {$pSnorkel}\n\nCalcola il tuo preventivo o prenota in italiano: {$base}/prices#calculator\nWhatsApp diretto (+201114567886): {$waUrl}\nIn quali date sarai a Sharm, in quale hotel e in quanti siete?",
            'de' => "Hallo" . ($firstName ? " {$firstName}" : "") . "! Hier ist Lara vom Sea College Dive Club (PADI 5★ Resort, Sharm El Sheikh) 🌊🤿\nAlle offiziellen EUR-Preise ohne versteckte Gebühren (max. 4 Taucher pro PADI-Guide!):\n• Schnuppertauchen (Anfänger): {$pIntroShore1} Hausriff | {$pIntroBoat1}–{$pIntroBoat2} Boot | DSD {$pDsd}\n• Zertifizierte Taucher: 2 Tauchgänge {$pDive2} | 6 TG {$pDive6} | 10 TG {$pDive10}\n• PADI Open Water Kurs: {$pOwd} | Schnorchelboot Ras Mohammed: {$pSnorkel}\n\nLive-Preisrechner & Buchung auf Deutsch: {$base}/prices#calculator\nDirekter WhatsApp-Kontakt (+201114567886): {$waUrl}\nWann seid ihr in Sharm, in welchem Hotel und wie viele Personen?",
            'fr' => "Bonjour" . ($firstName ? " {$firstName}" : "") . " ! Ici Lara du Sea College Dive Club (PADI 5★ Resort à Charm el-Cheikh) 🌊🤿\nTarifs officiels tout compris sans frais cachés (max 4 plongeurs par guide PADI) :\n• Baptême débutant : {$pIntroShore1} plage | {$pIntroBoat1}–{$pIntroBoat2} bateau | DSD {$pDsd}\n• Plongeurs certifiés : 2 plongées {$pDive2} | 6 plongées {$pDive6} | 10 plongées {$pDive10}\n• Cours PADI Open Water : {$pOwd} | Snorkeling Ras Mohammed : {$pSnorkel}\n\nCalculateur & réservation en français : {$base}/prices#calculator\nWhatsApp direct (+201114567886) : {$waUrl}\nQuelles sont vos dates à Charm, votre hôtel et le nombre de personnes ?",
            'pl' => "Cześć" . ($firstName ? " {$firstName}" : "") . "! Tu Lara z Sea College Dive Club (PADI 5★ Resort, Sharm El Sheikh) 🌊🤿\nOficjalne ceny w EUR bez ukrytych opłat (maks. 4 nurków na przewodnika PADI!):\n• Pierwsze nurkowanie (Intro): {$pIntroShore1} rafa | {$pIntroBoat1}–{$pIntroBoat2} z łodzi | DSD {$pDsd}\n• Certyfikowani nurkowie: 2 nurkowania {$pDive2} | 6 nurkowań {$pDive6} | 10 nurkowań {$pDive10}\n• Kurs PADI Open Water: {$pOwd} | Rejs snorkelingowy White Island: {$pSnorkel}\n\nKalkulator cen i rezerwacja po polsku: {$base}/prices#calculator\nSzybki WhatsApp (+201114567886): {$waUrl}\nW jakich terminach jesteście w Sharm, w jakim hotelu i ile osób?",
            'zh' => "您好" . ($firstName ? " {$firstName}" : "") . "！我是沙姆沙伊赫 Sea College Dive Club（PADI 五星级潜水度假村）的客服 Lara 🌊🤿\n我们坚持小团教学（每位 PADI 潜导最多带 4 名潜水员），透明欧元定价，零隐形码头费：\n• 新手体验潜：岸潜 {$pIntroShore1} | 船潜体验 {$pIntroBoat1}–{$pIntroBoat2} | PADI DSD {$pDsd}\n• 持证船潜套餐（拉斯穆罕默德/蒂朗岛）：2潜 {$pDive2} | 6潜 {$pDive6} | 10潜 {$pDive10}\n• PADI OW 开放水域课程：{$pOwd} | AOW 进阶：{$pAowd}\n\n中文官网实时报价与预订：{$base}/prices#calculator\nWhatsApp 极速确认（+201114567886）：{$waUrl}\n请问您计划哪几天来沙姆沙伊赫、入住哪家酒店、一共几位？",
            default => "¡Hola" . ($firstName ? " {$firstName}" : "") . "! Soy Lara de Sea College Dive Club (PADI 5★ Resort en Sharm El Sheikh) 🌊🤿\nPrecios oficiales en EUR sin cargos ocultos (máximo 4 buzos por guía PADI):\n• Bautismo principiantes: {$pIntroShore1} playa | {$pIntroBoat1}–{$pIntroBoat2} en barco | DSD {$pDsd}\n• Buzos certificados: 2 inmersiones {$pDive2} | 6 inmersiones {$pDive6} | 10 inmersiones {$pDive10}\n• Curso PADI Open Water: {$pOwd} | Barco Snorkel Ras Mohammed: {$pSnorkel}\n\nCalculadora online y reserva: " . SITE_URL . "/prices#calculator\nWhatsApp directo (+201114567886): {$waUrl}\n¿En qué fechas vienes a Sharm, en qué hotel te alojas y cuántas personas sois?",
        };
        return ['lang' => $lang, 'intent' => $intent, 'urgent_human' => false, 'reply' => $reply];
    }

    // English intent-specific precision responses
    $greet = "Hello" . ($firstName ? " {$firstName}" : "") . "! Welcome to Sea College Dive Club (Official PADI 5★ Dive Resort, Sharm El Sheikh) 🌊🤿 This is Lara.";

    if ($intent === 'hotel_pickup') {
        $matched = lara_match_hotel_zone($guestMessage);
        $zoneLine = $matched
            ? "For **{$matched['hotel']}** ({$matched['zone_name']}), standard morning pickup is **{$matched['window']} AM**."
            : "We pick up from all 6 Sharm El Sheikh zones (Al Montazah/Sharks Bay, SOHO, Naama Bay, Nabq, Coral Bay, and Hadaba/Old Market) between **07:20–08:10 AM**.";
        $reply = "{$greet}\n{$zoneLine}\n• Exact pickup time is confirmed at **16:30 the afternoon before** your trip.\n• Per Sharm resort security rules, our driver meets you **outside the main hotel gate by the main road**.\n\nCheck your exact hotel zone & book online: {$base}/prices#calculator\nOr message us on WhatsApp (+201114567886) with your hotel name & date: {$waUrl}";
        return ['lang' => $lang, 'intent' => $intent, 'urgent_human' => false, 'reply' => $reply];
    }

    if ($intent === 'checkin_currency') {
        $reply = "{$greet}\nHere is everything needed to confirm your boat permit & gear fast:\n• **Currency:** All official prices are in **EUR**. You may pay in **EUR, USD, GBP, or EGP** (cash or card) converted at dive morning's official bank exchange rate.\n• **60-Second Online Diver Check-In:** {$base}/check-in (passport photo for mandatory Coastguard boat list, hotel & room #, dive cert/log, and wetsuit/BCD/EU shoe sizes).\n\nNeed instant confirmation? Message us on WhatsApp (+201114567886): {$waUrl}";
        return ['lang' => $lang, 'intent' => $intent, 'urgent_human' => false, 'reply' => $reply];
    }

    if ($intent === 'beginner') {
        $reply = "{$greet}\nNo experience or swimming stress needed — our PADI instructors guide you 1-on-1 with **zero hidden marina fees**:\n• **Quick House Reef Intro Dive:** {$pIntroShore1}\n• **Full-Day Yacht + 1 Guided Intro Dive:** {$pIntroBoat1} (or **2 Boat Intro Dives:** {$pIntroBoat2}) incl. gear & buffet lunch\n• **Official PADI Discover Scuba Diving:** {$pDsd}\n• **White Island & Ras Mohammed Snorkeling Yacht:** {$pSnorkel} adult / {$pSnorkelKid} child\n• **Couples:** Certified diver + non-diver partner on the **same yacht** (incl. 1 Intro Dive + lunch): **+€45**!\n\nSee options & instant quote: {$base}/prices#calculator\nOr confirm your spot on WhatsApp (+201114567886): {$waUrl}\nWhich dates are you in Sharm, which hotel, and how many guests?";
        return ['lang' => $lang, 'intent' => $intent, 'urgent_human' => false, 'reply' => $reply];
    }

    if ($intent === 'courses') {
        $reply = "{$greet}\nHere are our official PADI courses & value bundles (**max 4 divers per PADI instructor**, zero hidden fees):\n• **PADI Open Water Diver (Beginner License):** {$pOwd}\n• **PADI Advanced Open Water:** {$pAowd}\n• **PADI Enriched Air Nitrox:** {$pNitrox}\n• **Combo Bundles:** Stars (OWD + AOWD) **{$pStars}** | Mega Bundle **{$pMega}**\n\nExplore all courses & reserve online: {$base}/diving-courses-sharm-el-sheikh\nOr chat with us on WhatsApp (+201114567886) to schedule your dates: {$waUrl}\nWhen do you arrive in Sharm El Sheikh and how many students?";
        return ['lang' => $lang, 'intent' => $intent, 'urgent_human' => false, 'reply' => $reply];
    }

    if ($intent === 'certified') {
        $reply = "{$greet}\nFor certified divers we keep groups personal — **maximum 4 divers per PADI guide** on spacious Red Sea yachts (*Oceano*, *Nizar*, *Omar 4*) with **100% transparent EUR pricing**:\n• **Daily Dive Packages:** 2 Dives {$pDive2} | 6 Dives {$pDive6} | 10 Dives {$pDive10} (Ras Mohammed & Straits of Tiran)\n• **SS Thistlegorm Wreck Expedition:** {$pThistle}\n• **Recent Diver Perk:** Dived recently? Send a photo of your dive computer log to **skip the Check Dive**!\n• **Bringing a Non-Diver Partner?** Same yacht + 1 Guided Intro Dive + lunch + transfer is just **+€45**.\n\nBuild your exact package: {$base}/prices#calculator\nOr message us on WhatsApp (+201114567886): {$waUrl} — what are your dates, cert level, and hotel?";
        return ['lang' => $lang, 'intent' => $intent, 'urgent_human' => false, 'reply' => $reply];
    }

    if ($intent === 'private_boat') {
        $reply = "{$greet}\nWe arrange private VIP yachts and speedboats for Ras Mohammed, White Island & Tiran:\n• **Private Yacht (up to 10 guests):** {$pBoat10}\n• **Private Yacht (11–15 guests):** {$pBoat15}\n• **Private Yacht (16–22 guests):** {$pBoat22}\n• **Private Speedboats:** from €75\n\nView our fleet & packages: {$base}/snorkeling-and-boats-sharm-el-sheikh\nMessage us your preferred date & group size here or on WhatsApp (+201114567886): {$waUrl}";
        return ['lang' => $lang, 'intent' => $intent, 'urgent_human' => false, 'reply' => $reply];
    }

    $reply = "{$greet}\nHappy to help right away! All our Red Sea trips & PADI courses feature **max 4 divers per guide** and **zero hidden marina fees**:\n• **First-Time Intro Dives:** {$pIntroShore1} shore | {$pIntroBoat1}–{$pIntroBoat2} by yacht | DSD {$pDsd}\n• **Certified Boat Diving (Ras Mohammed & Tiran):** from {$pDive2} (6 dives {$pDive6} | 10 dives {$pDive10})\n• **PADI Open Water Course:** {$pOwd} | **Snorkeling Yacht:** {$pSnorkel}\n\nInstant Trip Calculator & Online Booking: {$base}/prices#calculator\nFastest confirmation on WhatsApp (+201114567886): {$waUrl}\n\nReply here with your **Sharm dates, hotel name, number of guests**, and whether you're **beginners or certified divers**!";
    return ['lang' => $lang, 'intent' => $intent, 'urgent_human' => false, 'reply' => $reply];
}
