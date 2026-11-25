<?php
/**
 * Lara — Human-Natural Multilingual Diver Concierge & Meta Inbox Brain (v2).
 *
 * Key Safeguards Against Robotic / Duplicate / Irrelevant Replies:
 *   1) Strict Non-Customer & Ongoing-Chat Filter (`lara_should_skip_message`):
 *      Stays 100% silent (`should_reply = false`) on vendor/agency messages (reels/video/invoices),
 *      boat captains/partner dive centers, OTP codes, emoji/thank-you closings, and mid-thread chatter.
 *   2) Optional Real AI Understanding (`GEMINI_API_KEY` -> Gemini 2.5 Flash):
 *      Reads multi-turn conversation history + live EUR prices & hotel pickup zones, answering ONLY
 *      the guest's specific question in 2–4 warm, human sentences with zero robotic templates.
 *   3) Clean, Short Human Fallback (when `GEMINI_API_KEY` is empty):
 *      Zero broken `**` markdown asterisks; concise 3–4 line human answers tailored to the exact topic.
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
 * Detect messages where an automated dive concierge MUST stay silent (vendors, editors, captains,
 * OTP codes, polite closing acknowledgements, emoji-only reactions, or mid-conversation non-dive chatter).
 */
function lara_should_skip_message(string $text): bool
{
    $trim = trim($text);
    if ($trim === '') {
        return true;
    }
    // Pure emojis / punctuation
    $alphaNum = preg_replace('/[^\p{L}\p{N}]+/u', '', $trim);
    if ($alphaNum === '' || $alphaNum === null) {
        return true;
    }

    $low = mb_strtolower($trim, 'UTF-8');

    // 1. Internal operations, creative/video agencies, boat captains, partner dive centers, OTPs, bot meta-comments
    if (preg_match('/\b(reels?|redo the videos?|competitor\'?s|auto-greeting|no rush|verification code|security reminder|captn|boat list|manifest|invoice|scubadreamer|willy diving|social media manager|content plans|automated bot|instagram more effectively)\b/u', $low)) {
        return true;
    }

    // 2. Short polite closings / acknowledgements / thank-you messages without a new question
    if (mb_strlen($trim, 'UTF-8') <= 80 && !str_contains($low, '?') && !str_contains($low, '؟')) {
        if (preg_match('/^(ok|okay|k|thanks|thank you|thx|ty|got it|sounds good|great|perfect|nice|cool|super|awesome|cheers|fine|noted|sure|inshallah|شكرا|شكراً|شكرا جزيلا|تمام|ماشي|حاضر|إن شاء الله|ان شاء الله|يعطيك العافية|спасибо|ок|хорошо|отлично|понял|поняла|grazie|perfetto|danke|alles klar|merci|super merci|dziękuję|dzieki|gracias)[\s!.,🙏👍❤️😊☺️👌]*$/u', $low)) {
            return true;
        }
        if (preg_match('/\b(thank you|thanks so much|many thanks|gracias por|grazie mille|vielen dank|merci beaucoup|большое спасибо|спасибо за|شكرا لك|شكراً لك|شكرا على|جزاك الله)\b/u', $low)) {
            return true;
        }
    }

    // 3. Past-trip praise / review DMs ("Had an amazing day snorkelling... highly recommend") — don't pitch beginner prices!
    if (preg_match('/\b(had (a |an |the )?(wonderful|amazing|best|great) (time|day|experience)|highly recommend|special shoutout)\b/u', $low) && !str_contains($low, '?')) {
        return true;
    }

    // 4. Longer conversational messages (>= 9 words) that contain ZERO diving/tourism/booking/greeting signals
    $wordCount = count(preg_split('/\s+/u', $trim, -1, PREG_SPLIT_NO_EMPTY) ?: []);
    if ($wordCount >= 9) {
        $hasGuestSignal = (bool) preg_match('/(\b(dive|diving|scuba|snorkel|snorkelling|snorkeling|padi|open water|advanced|owd|aowd|dsd|intro|ras mohammed|ras mohamed|tiran|thistlegorm|white island|boat|yacht|speedboat|sharm|red sea|course|trip|tour|price|prices|cost|how much|book|booking|reserve|reservation|available|availability|hotel|pickup|pick up|transfer|check-in|checkin|passport|beginner|certified|nitrox|equipment|gear|marketing department|advertising)\b|غوص|غطس|سنوركل|دورة|كورس|رحلة|رحلات|سعر|اسعار|أسعار|بكم|تكلفة|حجز|شرم|يخت|مركب|فندق|توصيل|مبتدئ|دايف|дайв|погруж|сноркл|курс|цен|стоим|сколько|шарм|яхт|отел|брон|immersion|prezz|corso|barca|tauch|schnorchel|preis|plong|tarif|nurkow|cen|潜水|浮潜|价格|课程|预订)/u', $low);
        if (!$hasGuestSignal) {
            return true;
        }
    }

    return false;
}

/**
 * Classify incoming customer message intent.
 */
function lara_classify_intent(string $text): string
{
    if (lara_should_skip_message($text)) {
        return 'skip';
    }
    $low = mb_strtolower(trim($text), 'UTF-8');
    if (preg_match('/(\b(refund|complaint|angry|scam|emergency|hospital|accident|cancel my paid)\b|استرداد|شكوى|طوارئ|возврат|жалоб|退款|投诉)/u', $low)) {
        return 'escalation';
    }
    if (preg_match('/(\b(marketing department|advertising opportunity|partnership|collaborat|influencer|media kit|press|b2b|tour operator|travel agency)\b|إدارة التسويق|تعاون إعلامي|شراكة)/u', $low)) {
        return 'partnership';
    }
    if (preg_match('/(\b(private boat|private yacht|yacht|speedboat|vip|charter|barca privata|privatboot|bateau privé)\b|مركب خاص|يخت خاص|لانش|яхт|частн|私人游艇|包船)/u', $low)) {
        return 'private_boat';
    }
    if (preg_match('/(\b(open water|owd|advanced|aowd|padi course|rescue diver|divemaster|certification|license|licence|e-learning|bundle|corso|brevetto|tauchkurs|brevet)\b|كورس|دورة|رخصة|курс|сертификат|обучен|考证|课程)/u', $low)) {
        return 'courses';
    }
    if (preg_match('/(\b(first time|beginner|never dived|try dive|intro|discover scuba|dsd|bubblemaker|snorkel|snorkeling|snorkelling|white island|couple|non-diver|wife|husband|partner|principiante|prima volta|anfänger|schnorcheln|débutant)\b|أول مرة|مبتدئ|انترودايف|سنوركل|нович|первый раз|интро|сноркл|新手|体验潜|浮潜)/u', $low)) {
        return 'beginner';
    }
    if (preg_match('/(\b(ras mohammed|ras mohamed|tiran|thistlegorm|wreck|certified|dive pack|check dive|nitrox|10 dives|6 dives|daily diving)\b|رأس محمد|راس محمد|تيران|ثيسلجورم|غواص معتمد|рас мохаммед|тиран|тистлегорм|дейли|сертифицир|持证|穆罕默德|蒂朗)/u', $low)) {
        return 'certified';
    }
    if (preg_match('/(\b(pickup|pick up|transfer|hotel|zone|what time|morning|main gate)\b|توصيل|باص|فندق|موعد التحرك|трансфер|отел|во сколько|接送|酒店)/u', $low)) {
        return 'hotel_pickup';
    }
    if (preg_match('/(\b(check-in|checkin|passport|egp|usd|gbp|dollar|currency|exchange rate|cash|card|email address)\b|جنيه|دولار|جواز|عملة|فيزا|ايميل|بريد|паспорт|валют|доллар|фунт|护照|汇率)/u', $low)) {
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
 * Call Gemini 2.5 Flash when `GEMINI_API_KEY` is configured so Lara genuinely understands the guest's
 * exact question & conversation history and writes a natural 2–4 sentence human response.
 *
 * @param array<int, array{role:string,text:string}> $history
 * @return array{should_reply:bool,lang:string,intent:string,urgent_human:bool,reply:string}|null
 */
function lara_call_gemini(string $guestMessage, string $channel, string $guestName, array $history): ?array
{
    if (!defined('GEMINI_API_KEY') || GEMINI_API_KEY === '') {
        return null;
    }
    $pIntroShore1 = lara_eur('intro-shore-1', 30);
    $pIntroBoat1  = lara_eur('intro-boat-1', 70);
    $pIntroBoat2  = lara_eur('intro-boat-2', 100);
    $pDsd         = lara_eur('dsd', 85);
    $pOwd         = lara_eur('owd', 400);
    $pAowd        = lara_eur('aowd', 350);
    $pNitrox      = lara_eur('nitrox-course', 145);
    $pStars       = lara_eur('bundle-stars', 500);
    $pDive2       = lara_eur('dive-2', 85);
    $pDive6       = lara_eur('dive-6', 255);
    $pDive10      = lara_eur('dive-10', 350);
    $pThistle     = lara_eur('thistlegorm', 150);
    $pSnorkel     = lara_eur('white-island-adult', 55);
    $pBoat10      = lara_eur('private-boat-10', 550);

    $matchedZone = lara_match_hotel_zone($guestMessage);
    $zoneHint = $matchedZone
        ? "Matched guest hotel: {$matchedZone['hotel']} ({$matchedZone['zone_name']}, pickup {$matchedZone['window']} AM outside main hotel gate by main road)."
        : "6 Sharm hotel pickup zones between 07:20–08:10 AM (exact pickup confirmed 16:30 afternoon before, outside main hotel gate by main road).";

    $histLines = [];
    foreach (array_slice($history, -6) as $turn) {
        $r = ($turn['role'] ?? 'guest') === 'lara' ? 'Lara' : 'Guest';
        $histLines[] = "{$r}: " . trim((string) ($turn['text'] ?? ''));
    }
    $histBlock = !empty($histLines) ? ("Recent conversation history:\n" . implode("\n", $histLines) . "\n\n") : '';

    $sysPrompt = <<<SYS
You are Lara, the warm, real-person Diver Concierge at Sea College Dive Club (Official PADI 5-Star Dive Resort in Sharm El Sheikh, Egypt; website https://seacollegediveclub.com, email info@seacollegediveclub.com, WhatsApp +201114567886).

CRITICAL HUMAN RULES:
1. First decide `should_reply`: set `should_reply` to FALSE if the message is from a supplier/video editor/agency, boat captain, partner dive center, OTP/system alert, a simple "thanks/ok/emoji" closing, or an ongoing internal business chat unrelated to customer diving/snorkeling/courses.
2. Sound 100% like a warm, attentive human concierge texting a guest — NEVER dump a big price menu unless they ask generally "what are your prices?". Answer ONLY what the guest specifically asked in 2 to 4 short, natural sentences!
3. NEVER use Markdown asterisks (`*` or `**`) — write clean plain text only.
4. If `Recent conversation history` shows you already greeted the guest, DO NOT repeat "Hello! Welcome to Sea College Dive Club, this is Lara" — continue the conversation naturally!
5. Reply natively in the guest's language (`en`, `ar`, `ru`, `it`, `pl`, `de`, `fr`, `zh`, `es`) and include at most ONE relevant website link (`https://seacollegediveclub.com/<lang>/prices#calculator` or `/check-in` or `/diving-courses-sharm-el-sheikh`; omit `/<lang>` for English). Do NOT include a WhatsApp link if channel is already `whatsapp`.

VERIFIED FACTS & EUR PRICES (zero hidden marina fees; max 4 divers per PADI guide):
- Beginners: House Reef Intro {$pIntroShore1}, Full-Day Yacht + 1 Intro Dive {$pIntroBoat1} (2 Intro Dives {$pIntroBoat2}), PADI DSD {$pDsd}, White Island & Ras Mohammed Snorkeling Yacht {$pSnorkel}.
- PADI Courses: Open Water {$pOwd}, Advanced {$pAowd}, Nitrox {$pNitrox}, Stars Bundle (OWD+AOWD) {$pStars}.
- Certified Divers: 2 dives {$pDive2}, 6 dives {$pDive6}, 10 dives {$pDive10} (+€10/day boat, +€5/day park, +€25/day full gear if needed), SS Thistlegorm {$pThistle}. Recent divers can skip Check Dive with a dive computer log photo. Non-diver partner on same yacht + 1 Intro Dive + lunch + transfer = +€45.
- Private Yachts: from {$pBoat10} (up to 10 pax).
- Currency: Official prices in EUR; guests may pay EUR, USD, GBP, or EGP (cash or card) at dive morning's official bank rate.
- Transfers: {$zoneHint}

Return ONLY valid JSON matching:
{"should_reply": true, "lang": "en", "intent": "courses|beginner|certified|private_boat|hotel_pickup|checkin_currency|partnership|escalation|general|skip", "urgent_human": false, "reply": "..."}
SYS;

    $userPrompt = "Channel: {$channel}\nSender display name: {$guestName}\n{$histBlock}Latest message from sender:\n{$guestMessage}";

    $payloadJson = json_encode([
        'systemInstruction' => ['parts' => [['text' => $sysPrompt]]],
        'contents'          => [['role' => 'user', 'parts' => [['text' => $userPrompt]]]],
        'generationConfig'  => [
            'temperature'      => 0.35,
            'maxOutputTokens'  => 1200,
            'responseMimeType' => 'application/json',
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $models = ['gemini-3.5-flash-lite', 'gemini-flash-lite-latest', 'gemini-3.1-flash-lite', 'gemini-flash-latest'];
    $raw = '';
    $code = 0;
    foreach ($models as $model) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . rawurlencode(GEMINI_API_KEY);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => $payloadJson,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 12,
        ]);
        $raw = (string) curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 200 && $raw !== '') {
            break;
        }
    }

    if ($code !== 200 || $raw === '') {
        return null;
    }
    $outer = json_decode($raw, true);
    $jsonText = '';
    foreach (($outer['candidates'][0]['content']['parts'] ?? []) as $part) {
        if (empty($part['thought']) && isset($part['text']) && is_string($part['text'])) {
            $jsonText = $part['text'];
        }
    }
    if ($jsonText === '') {
        return null;
    }
    $parsed = json_decode($jsonText, true);
    if (!is_array($parsed) || !isset($parsed['should_reply'])) {
        return null;
    }
    $replyClean = trim(str_replace('**', '', (string) ($parsed['reply'] ?? '')));
    $should = (bool) $parsed['should_reply'] && $replyClean !== '';

    return [
        'should_reply' => $should,
        'lang'         => (string) ($parsed['lang'] ?? lara_detect_language($guestMessage)),
        'intent'       => (string) ($parsed['intent'] ?? ($should ? 'general' : 'skip')),
        'urgent_human' => (bool) ($parsed['urgent_human'] ?? false),
        'reply'        => $replyClean,
        'via_ai'       => true,
    ];
}

/**
 * Generate Lara's reply + structured metadata.
 *
 * @param array<int, array{role:string,text:string}> $history
 * @return array{should_reply:bool,lang:string,intent:string,urgent_human:bool,reply:string,via_ai?:bool}
 */
function lara_generate_reply(string $guestMessage, string $channel = 'messenger', string $guestName = '', array $history = []): array
{
    // 1. Hard guard against internal/vendor/captain/closing/non-customer messages
    if (lara_should_skip_message($guestMessage)) {
        return [
            'should_reply' => false,
            'lang'         => lara_detect_language($guestMessage),
            'intent'       => 'skip',
            'urgent_human' => false,
            'reply'        => '',
        ];
    }

    // 2. Try real conversational AI (Gemini 2.5 Flash) if configured
    $aiPlan = lara_call_gemini($guestMessage, $channel, $guestName, $history);
    if ($aiPlan !== null) {
        return $aiPlan;
    }

    // 3. Clean, concise, zero-asterisk human fallback (only answers what was asked — never repeats!)
    $lang = lara_detect_language($guestMessage);
    $intent = lara_classify_intent($guestMessage);
    $lp = lara_lang_prefix($lang);
    $base = SITE_URL . $lp;
    $waUrl = 'https://wa.me/' . WHATSAPP_NUMBER;
    $waLineEn = $channel === 'whatsapp' ? '' : "\nWhatsApp: +201114567886 ({$waUrl})";
    $waLineAr = $channel === 'whatsapp' ? '' : "\nواتساب مباشر: {$waUrl}";
    $waLineRu = $channel === 'whatsapp' ? '' : "\nWhatsApp: {$waUrl}";

    $isHandle = (bool) preg_match('/[._0-9]/', $guestName);
    $cleanName = $isHandle ? '' : trim((string) preg_replace('/[^\p{L}\s\'-]/u', '', $guestName));
    $firstName = $cleanName !== '' ? explode(' ', $cleanName)[0] : '';

    $pIntroShore1 = lara_eur('intro-shore-1', 30);
    $pIntroBoat1  = lara_eur('intro-boat-1', 70);
    $pIntroBoat2  = lara_eur('intro-boat-2', 100);
    $pDsd         = lara_eur('dsd', 85);
    $pOwd         = lara_eur('owd', 400);
    $pAowd        = lara_eur('aowd', 350);
    $pNitrox      = lara_eur('nitrox-course', 145);
    $pStars       = lara_eur('bundle-stars', 500);
    $pDive2       = lara_eur('dive-2', 85);
    $pDive6       = lara_eur('dive-6', 255);
    $pDive10      = lara_eur('dive-10', 350);
    $pThistle     = lara_eur('thistlegorm', 150);
    $pSnorkel     = lara_eur('white-island-adult', 55);
    $pBoat10      = lara_eur('private-boat-10', 550);

    if ($intent === 'escalation') {
        $msg = match ($lang) {
            'ar' => "أهلاً بك، معك لارا من Sea College Dive Club. تم تحويل رسالتك فوراً لمدير العمليات للرد عليك بشكل شخصي ومباشر الآن." . $waLineAr,
            'ru' => "Здравствуйте! Это Лара, Sea College Dive Club. Я сразу передала ваше сообщение старшему менеджеру — мы свяжемся с вами в ближайшие минуты." . $waLineRu,
            default => "Hi" . ($firstName ? " {$firstName}" : "") . ", this is Lara at Sea College Dive Club. I have passed your message directly to our Operations Manager so we can assist you personally right away." . $waLineEn,
        };
        return ['should_reply' => true, 'lang' => $lang, 'intent' => $intent, 'urgent_human' => true, 'reply' => $msg];
    }

    if ($intent === 'partnership') {
        $msg = match ($lang) {
            'ar' => "أهلاً بك من Sea College Dive Club بشرم الشيخ! للتواصل مع إدارة التسويق والشراكات، يسعدنا استقبال التفاصيل عبر البريد الرسمي info@seacollegediveclub.com وسيرد عليك مدير التسويق مباشرة." . $waLineAr,
            default => "Hi" . ($firstName ? " {$firstName}" : "") . "! Thanks for reaching out to Sea College Dive Club. Please send your marketing or partnership proposal to our Marketing Director at info@seacollegediveclub.com and our team will get right back to you." . $waLineEn,
        };
        return ['should_reply' => true, 'lang' => $lang, 'intent' => $intent, 'urgent_human' => false, 'reply' => $msg];
    }

    if ($lang === 'ar') {
        $reply = match ($intent) {
            'beginner' => "أهلاً بك في Sea College Dive Club بشرم الشيخ! معك لارا 🌊\nللمبتدئين بدون أي خبرة سابقة مع مدرب خاص: غطسة الشاطئ {$pIntroShore1}، أو رحلة يخت شاملة الغداء والمعدات مع غطسة تعريفية {$pIntroBoat1} (غطستان {$pIntroBoat2})، ورحلة سنوركلينج وايت آيلاند {$pSnorkel}.\nما هو موعد زيارتك واسم الفندق وعدد الأفراد؟\n{$base}/prices#calculator" . $waLineAr,
            'courses' => "أهلاً بك في Sea College Dive Club (مركز PADI 5 نجوم بشرم الشيخ)! معك لارا 🤿\nدورة PADI Open Water بـ {$pOwd}، ودورة Advanced بـ {$pAowd}، وباقة الإثنين معاً (Stars) بـ {$pStars} (بحد أقصى 4 متدربين لكل مدرب).\nمتى تصلون إلى شرم الشيخ وكم عدد الأفراد؟\n{$base}/diving-courses-sharm-el-sheikh" . $waLineAr,
            'certified' => "أهلاً بك في Sea College Dive Club بشرم الشيخ! معك لارا 🤿\nللغواصين المعتمدين (حد أقصى 4 غواصين لكل مرشد): غطستان {$pDive2}، 6 غطسات {$pDive6}، 10 غطسات {$pDive10}، وحطام Thistlegorm بـ {$pThistle}.\nما هي تواريخ رحلتك ومستوى شهادتك واسم الفندق؟\n{$base}/prices#calculator" . $waLineAr,
            'private_boat' => "أهلاً بك في Sea College Dive Club! معك لارا 🛥️\nتبدأ اليخوت الخاصة VIP من {$pBoat10} (حتى 10 أفراد) واللانشات السريعة من €75 إلى رأس محمد أو تيران.\nما هو التاريخ المطلوب وعدد الضيوف؟\n{$base}/snorkeling-and-boats-sharm-el-sheikh" . $waLineAr,
            default => "أهلاً وسهلاً بك في Sea College Dive Club بشرم الشيخ! معك لارا 🌊\nيسعدني مساعدتك — أخبرني فقط هل تبحث عن غطس للمبتدئين، غوص للمعتمدين، دورة PADI، أم رحلة سنوركلينج، وما هو موعد رحلتك؟\nيمكنك أيضاً مشاهدة الأسعار الشاملة هنا: {$base}/prices#calculator" . $waLineAr,
        };
        return ['should_reply' => true, 'lang' => $lang, 'intent' => $intent, 'urgent_human' => false, 'reply' => $reply];
    }

    if ($lang === 'ru') {
        $reply = match ($intent) {
            'beginner' => "Здравствуйте! Это Лара, Sea College Dive Club (Шарм-эль-Шейх) 🌊\nДля новичков без опыта индивидуально с инструктором: пробное погружение с рифа {$pIntroShore1}, яхта на весь день + 1 дайв {$pIntroBoat1} (2 дайва {$pIntroBoat2}), снорклинг Белый остров {$pSnorkel}.\nВ какие даты вы в Шарме и сколько вас человек?\n{$base}/prices#calculator" . $waLineRu,
            'courses' => "Здравствуйте! Это Лара, Sea College Dive Club (PADI 5★, Шарм-эль-Шейх) 🤿\nКурс PADI Open Water — {$pOwd}, Advanced — {$pAowd}, комбо Stars (OWD+AOWD) — {$pStars} (мини-группы до 4 человек).\nПодскажите ваши даты в Шарме?\n{$base}/diving-courses-sharm-el-sheikh" . $waLineRu,
            'certified' => "Здравствуйте! Это Лара, Sea College Dive Club 🤿\nДля сертифицированных дайверов (макс. 4 дайвера на гида): 2 дайва {$pDive2}, 6 дайвов {$pDive6}, 10 дайвов {$pDive10}, рэк Thistlegorm {$pThistle}.\nКакие у вас даты, уровень сертификата и отель?\n{$base}/prices#calculator" . $waLineRu,
            default => "Здравствуйте! Это Лара, Sea College Dive Club в Шарм-эль-Шейхе 🌊\nС радостью помогу! Подскажите, вас интересует пробный дайвинг для новичков, дейли-дайвинг для сертифицированных, курс PADI или снорклинг на яхте, и в какие даты?\nВсе цены и калькулятор: {$base}/prices#calculator" . $waLineRu,
        };
        return ['should_reply' => true, 'lang' => $lang, 'intent' => $intent, 'urgent_human' => false, 'reply' => $reply];
    }

    if (in_array($lang, ['it', 'de', 'fr', 'pl', 'zh', 'es'], true)) {
        $reply = match ($lang) {
            'it' => "Ciao" . ($firstName ? " {$firstName}" : "") . "! Sono Lara del Sea College Dive Club a Sharm El Sheikh 🌊\nDimmi pure in quali date sarai a Sharm, in quanti siete e se cercate prima immersione (da {$pIntroShore1}/{$pIntroBoat1}), immersioni con brevetto (da {$pDive2}), corsi PADI ({$pOwd}) o snorkeling ({$pSnorkel})!\nPreventivo rapido: {$base}/prices#calculator" . $waLineEn,
            'de' => "Hallo" . ($firstName ? " {$firstName}" : "") . "! Hier ist Lara vom Sea College Dive Club in Sharm El Sheikh 🌊\nSag mir gern eure Reisedaten, Personenanzahl und ob ihr Schnuppertauchen (ab {$pIntroShore1}/{$pIntroBoat1}), Tauchpakete (ab {$pDive2}) oder einen PADI-Kurs ({$pOwd}) plant!\nPreisrechner: {$base}/prices#calculator" . $waLineEn,
            'fr' => "Bonjour" . ($firstName ? " {$firstName}" : "") . " ! Ici Lara du Sea College Dive Club à Charm el-Cheikh 🌊\nDites-moi vos dates, le nombre de personnes et si vous cherchez un baptême débutant ({$pIntroShore1}/{$pIntroBoat1}), des plongées certifiées (dès {$pDive2}) ou un cours PADI ({$pOwd}) !\nTarifs : {$base}/prices#calculator" . $waLineEn,
            'pl' => "Cześć" . ($firstName ? " {$firstName}" : "") . "! Tu Lara z Sea College Dive Club w Sharm El Sheikh 🌊\nNapisz proszę w jakich terminach jesteście w Sharm, ile osób i czy interesuje Was pierwsze nurkowanie Intro ({$pIntroShore1}/{$pIntroBoat1}), pakiet dla certyfikowanych (od {$pDive2}) czy kurs PADI ({$pOwd})!\nCennik: {$base}/prices#calculator" . $waLineEn,
            'zh' => "您好" . ($firstName ? " {$firstName}" : "") . "！我是沙姆沙伊赫 Sea College Dive Club 的客服 Lara 🌊\n请问您计划哪几天来、一共几位，想了解新手体验潜（{$pIntroShore1}/{$pIntroBoat1}）、持证船潜（{$pDive2}起）还是 PADI 考证课程（OW {$pOwd}）？\n中文实时报价：{$base}/prices#calculator" . $waLineEn,
            default => "¡Hola" . ($firstName ? " {$firstName}" : "") . "! Soy Lara de Sea College Dive Club en Sharm El Sheikh 🌊\nCuéntame en qué fechas vienes, cuántas personas sois y si buscáis bautismo para principiantes ({$pIntroShore1}/{$pIntroBoat1}), buceo certificado (desde {$pDive2}) o curso PADI ({$pOwd}).\nPrecios: " . SITE_URL . "/prices#calculator" . $waLineEn,
        };
        return ['should_reply' => true, 'lang' => $lang, 'intent' => $intent, 'urgent_human' => false, 'reply' => $reply];
    }

    // Concise, natural English responses answering ONLY the specific topic asked
    $hi = "Hi" . ($firstName ? " {$firstName}" : "") . "! This is Lara at Sea College Dive Club in Sharm El Sheikh 🌊";

    if ($intent === 'hotel_pickup') {
        $matched = lara_match_hotel_zone($guestMessage);
        $zoneLine = $matched
            ? "For {$matched['hotel']} ({$matched['zone_name']}), morning pickup is usually {$matched['window']} AM outside the main hotel gate by the main road."
            : "We pick up from all Sharm El Sheikh hotels between 07:20–08:10 AM outside the main hotel gate (exact time is confirmed at 16:30 the day before).";
        $reply = "{$hi}\n{$zoneLine}\nWhich hotel are you staying at and which date would you like to join us?" . $waLineEn;
        return ['should_reply' => true, 'lang' => $lang, 'intent' => $intent, 'urgent_human' => false, 'reply' => $reply];
    }

    if ($intent === 'checkin_currency') {
        $lowMsg = mb_strtolower($guestMessage, 'UTF-8');
        if (str_contains($lowMsg, 'email')) {
            $reply = "{$hi}\nOur official email address is info@seacollegediveclub.com (and direct WhatsApp is +201114567886). Let me know how I can help!";
            return ['should_reply' => true, 'lang' => $lang, 'intent' => $intent, 'urgent_human' => false, 'reply' => $reply];
        }
        $reply = "{$hi}\nAll official prices are in EUR, and you can pay in EUR, USD, GBP, or EGP (cash or card) at dive morning's official bank rate. For Coastguard boat permits and gear sizes, you can complete quick online check-in here: {$base}/check-in" . $waLineEn;
        return ['should_reply' => true, 'lang' => $lang, 'intent' => $intent, 'urgent_human' => false, 'reply' => $reply];
    }

    if ($intent === 'beginner') {
        $reply = "{$hi}\nNo prior experience needed at all — our PADI instructors guide you 1-on-1! Quick House Reef Intro Dive is {$pIntroShore1}, Full-Day Yacht with 1 Intro Dive & lunch is {$pIntroBoat1} (2 dives {$pIntroBoat2}), or White Island Snorkeling Yacht is {$pSnorkel}.\nWhich dates are you in Sharm and how many of you are joining?\n{$base}/prices#calculator" . $waLineEn;
        return ['should_reply' => true, 'lang' => $lang, 'intent' => $intent, 'urgent_human' => false, 'reply' => $reply];
    }

    if ($intent === 'courses') {
        $reply = "{$hi}\nOur PADI Open Water course is {$pOwd}, Advanced Open Water is {$pAowd}, Nitrox is {$pNitrox}, or the Stars OWD+AOWD bundle is {$pStars} (small groups, max 4 students per PADI instructor).\nWhich dates will you be in Sharm El Sheikh?\n{$base}/diving-courses-sharm-el-sheikh" . $waLineEn;
        return ['should_reply' => true, 'lang' => $lang, 'intent' => $intent, 'urgent_human' => false, 'reply' => $reply];
    }

    if ($intent === 'certified') {
        $reply = "{$hi}\nFor certified divers we keep groups small (max 4 divers per PADI guide): 2 boat dives {$pDive2}, 6 dives {$pDive6}, 10 dives {$pDive10}, and SS Thistlegorm {$pThistle} (plus if you dived recently, just send a dive log photo to skip the Check Dive!).\nWhat are your Sharm dates, cert level, and hotel?\n{$base}/prices#calculator" . $waLineEn;
        return ['should_reply' => true, 'lang' => $lang, 'intent' => $intent, 'urgent_human' => false, 'reply' => $reply];
    }

    if ($intent === 'private_boat') {
        $reply = "{$hi}\nPrivate yachts for Ras Mohammed or Tiran start at {$pBoat10} (up to 10 guests), and private speedboats start from €75.\nWhich date are you looking at and for how many guests?\n{$base}/snorkeling-and-boats-sharm-el-sheikh" . $waLineEn;
        return ['should_reply' => true, 'lang' => $lang, 'intent' => $intent, 'urgent_human' => false, 'reply' => $reply];
    }

    $reply = "{$hi}\nHappy to help! Are you looking for beginner intro dives, certified daily diving, a PADI course, or a snorkeling yacht — and which dates are you in Sharm?\nYou can also check live packages anytime here: {$base}/prices#calculator" . $waLineEn;
    return ['should_reply' => true, 'lang' => $lang, 'intent' => $intent, 'urgent_human' => false, 'reply' => $reply];
}
