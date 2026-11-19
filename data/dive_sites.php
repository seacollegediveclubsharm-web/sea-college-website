<?php
/**
 * Sharm El Sheikh & South Sinai Dive Sites + 12-Month Red Sea Conditions Guide.
 * Structured with localized ['en' => ..., 'ar' => ...] keys so future languages drop right in.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/i18n_data.php';

return sc_overlay_dive_sites([
    'aeo_answer' => [
        'en' => 'Sharm El Sheikh sits at the southern tip of the Sinai Peninsula where the Gulf of Suez and Gulf of Aqaba meet the open Red Sea, creating over 30 world-class coral walls, drift reefs and historic shipwrecks within 5 to 90 minutes of Al Montazah. Water temperatures range from 22 °C in February to 29 °C in August with year-round 20–40 m visibility. Beginners can dive calm sheltered sites like Sea College\'s Al Montazah House Reef (2–14 m) and Ras Mohammed\'s Jackfish Alley, while Open Water and Advanced divers explore Shark & Yolanda Reef, the four reefs of the Straits of Tiran, SS Dunraven, Million Hope and the legendary WWII wreck SS Thistlegorm (16–30 m).',
        'ar' => 'تقع شرم الشيخ عند الطرف الجنوبي لشبه جزيرة سيناء حيث يلتقي خليجا السويس والعقبة بالبحر الأحمر المفتوح، مما يخلق أكثر من 30 موقع غوص عالمي من الجدران المرجانية والشعاب المنجرفة وحطام السفن التاريخية على بُعد 5 إلى 90 دقيقة من المنتزه. تتراوح حرارة المياه بين 22°م في فبراير و29°م في أغسطس مع رؤية مائية 20–40 مترًا طوال العام. يستمتع المبتدئون بمواقع هادئة مثل الشعاب المنزلية بالمنتزه (2–14م)، بينما يستكشف الغواصون المعتمدون شارك ويولاندا ريف برأس محمد، وشعاب مضيق تيران الأربعة، وحطام دنرافين ومليون هوب وسفينة الحرب العالمية الثانية الأسطورية ثيستلجورم (16–30م).',
    ],

    'zones' => [
        'all'          => ['en' => 'All Dive Sites (10)', 'ar' => 'كل مواقع الغوص (10)'],
        'local'        => ['en' => 'House Reef & Local', 'ar' => 'الشعاب المنزلية والمحلية'],
        'tiran'        => ['en' => 'Straits of Tiran', 'ar' => 'مضيق وجزيرة تيران'],
        'ras-mohammed' => ['en' => 'Ras Mohammed Park', 'ar' => 'محمية رأس محمد'],
        'wrecks'       => ['en' => 'Red Sea Wrecks', 'ar' => 'حطام السفن التاريخية'],
        'dahab'        => ['en' => 'Dahab Day Trip', 'ar' => 'رحلة دهب اليومية'],
    ],

    'sites' => [
        [
            'id'      => 'house-reef',
            'zone'    => 'local',
            'map_x'   => 382,
            'map_y'   => 246,
            'depth'   => '2 – 18 m',
            'level'   => ['en' => 'Beginner / Intro & All Levels', 'ar' => 'مبتدئ / غطسات تجريبية وكل المستويات'],
            'current' => ['en' => 'Calm / Sheltered', 'ar' => 'هادئ / محمي'],
            'name'    => ['en' => 'Al Montazah House Reef (Cyrene Sharm)', 'ar' => 'الشعاب المنزلية بالمنتزه (سيرين شرم)'],
            'summary' => [
                'en' => 'Steps from our training pool at Cyrene Sharm: sandy 3–5 m training plateau sloping gently into colourful coral pinnacles with zero marine park fee.',
                'ar' => 'على بُعد خطوات من حمام السباحة التدريبي في سيرين شرم: قاع رملي ضحل (3–5م) ينحدر تدريجيًا إلى شعاب مرجانية ملونة بدون رسوم محمية.',
            ],
            'marine'  => [
                'en' => ['Clownfish & anemones', 'Blue-spotted rays', 'Lionfish', 'Moray eels', 'Anthias clouds'],
                'ar' => ['سمك المهرج وشقائق النعمان', 'الراي اللاسع الأزرق', 'سمكة الأسد', 'ثعبان الموراي', 'أسراب الأنثياس'],
            ],
            'img'     => 'sea-college-dive-club-sharm-el-sheikh-discover-scuba-diving-intro-dive-red-sea.jpg',
            'item_id' => 'dive-1',
        ],
        [
            'id'      => 'ras-nasrani',
            'zone'    => 'local',
            'map_x'   => 395,
            'map_y'   => 228,
            'depth'   => '5 – 30 m',
            'level'   => ['en' => 'Open Water / Snorkeling', 'ar' => 'Open Water / سنوركل'],
            'current' => ['en' => 'Mild', 'ar' => 'خفيف'],
            'name'    => ['en' => 'Ras Nasrani & Gordon Near Reefs', 'ar' => 'رأس نصراني والشعاب القريبة بالمنتزه'],
            'summary' => [
                'en' => 'Five minutes from our jetty in Al Montazah: giant gorgonian sea fans, giant clams and pristine shallow coral gardens ideal for check dives and afternoon boats.',
                'ar' => 'على بُعد 5 دقائق من مرسى المنتزه: مراوح بحرية عملاقة ومحار ملون وحدائق مرجانية ضحلة مثالية للغطسات التنشيطية ورحلات القارب.',
            ],
            'marine'  => [
                'en' => ['Gorgonian sea fans', 'Pufferfish', 'Parrotfish', 'Green turtles', 'Chromis'],
                'ar' => ['مراوح البحر المرجانية', 'سمك القراض', 'الببغاء', 'السلاحف الخضراء', 'أسماك الكروميس'],
            ],
            'img'     => 'gallery/sea-college-dive-club-sharm-el-sheikh-al-montazah-speedboat-lagoon.jpg',
            'item_id' => 'dive-2',
        ],
        [
            'id'      => 'jackson-reef',
            'zone'    => 'tiran',
            'map_x'   => 458,
            'map_y'   => 196,
            'depth'   => '5 – 40 m',
            'level'   => ['en' => 'Open Water / Advanced', 'ar' => 'Open Water / متقدم'],
            'current' => ['en' => 'Moderate – Drift', 'ar' => 'متوسط – غوص منجرف'],
            'name'    => ['en' => 'Jackson Reef & Lara Wreck (Tiran)', 'ar' => 'جاكسون ريف وحطام لارا (مضيق تيران)'],
            'summary' => [
                'en' => 'Northernmost reef in the Straits of Tiran, marked by theexposed wreck of MV Lara. Famous for sheer walls carpeted in fire and soft corals plus summer dawn hammerhead sightings.',
                'ar' => 'أقصى شعاب مضيق تيران شمالًا والمميزة ببقايا سفينة لارا؛ تشتهر بجدرانها المرجانية العميقة ورؤية أسماك القرش المطرقة فجرًا في الصيف.',
            ],
            'marine'  => [
                'en' => ['Scalloped hammerheads (Jul–Sep)', 'Hawksbill turtles', 'Jacks & trevally', 'Red Sea bannerfish'],
                'ar' => ['قرش المطرقة (يوليو–سبتمبر)', 'سلاحف صقرية المنقار', 'أسماك البياض والتريفالي', 'سمكة الراية'],
            ],
            'img'     => 'sea-college-dive-club-sharm-el-sheikh-tiran-island-private-speedboat.jpg',
            'item_id' => 'sup-zodiac-early',
        ],
        [
            'id'      => 'thomas-gordon',
            'zone'    => 'tiran',
            'map_x'   => 436,
            'map_y'   => 218,
            'depth'   => '5 – 35 m',
            'level'   => ['en' => 'Open Water & All Levels', 'ar' => 'Open Water وكل المستويات'],
            'current' => ['en' => 'Mild – Moderate', 'ar' => 'خفيف – متوسط'],
            'name'    => ['en' => 'Gordon, Thomas & Woodhouse Reefs (Tiran)', 'ar' => 'جوردون وتوماس وودهاوس ريف (تيران)'],
            'summary' => [
                'en' => 'Three iconic mid-channel reefs between Sinai and Tiran Island offering sunny shallow plateaus (Gordon drums), dramatic deep canyons (Thomas) and exhilarating drift walls (Woodhouse).',
                'ar' => 'ثلاث شعاب شهيرة في قلب مضيق تيران تجمع بين الهضاب الضحلة المشمسة (جوردون) والأخاديد العميقة (توماس) والانجراف الممتع بمحاذاة الجدران (وودهاوس).',
            ],
            'marine'  => [
                'en' => ['Eagle rays', 'White-tip reef sharks', 'Napoleon wrasse', 'Tuna & barracuda'],
                'ar' => ['الراي النسر', 'قرش الشعاب أبيض الزعنفة', 'سمكة نابليون', 'التونة والباراكودا'],
            ],
            'img'     => 'gallery/sea-college-dive-club-sharm-el-sheikh-divers-yacht-deck-red-sea.jpg',
            'item_id' => 'dive-6',
        ],
        [
            'id'      => 'million-hope',
            'zone'    => 'wrecks',
            'map_x'   => 412,
            'map_y'   => 172,
            'depth'   => '5 – 24 m',
            'level'   => ['en' => 'Open Water (18 m+)', 'ar' => 'Open Water (من 18م)'],
            'current' => ['en' => 'Light – Moderate', 'ar' => 'خفيف – متوسط'],
            'name'    => ['en' => 'MV Million Hope Wreck (Nabq)', 'ar' => 'حطام سفينة مليون هوب (نبق)'],
            'summary' => [
                'en' => 'At 175 m long, the 1996 bulk carrier Million Hope at Nabq is the largest shipwreck in Sharm El Sheikh — shallow enough for sunlit Open Water exploration with a mobile crane beside it.',
                'ar' => 'بطول 175 مترًا، تُعد سفينة البضائع «مليون هوب» الغارقة عام 1996 في نبق أضخم حطام سفينة في شرم الشيخ، وأعماقها الضحلة المشمسة تجعلها مثالية لحاملي Open Water.',
            ],
            'marine'  => [
                'en' => ['Batfish schools', 'Crocodilefish', 'Nudibranchs', 'Glassfish', 'Boxfish'],
                'ar' => ['أسراب سمك الوطواط', 'سمكة التمساح', 'عاريات الخياشيم الملونة', 'الأسماك الزجاجية', 'سمك الصندوق'],
            ],
            'img'     => 'courses/12_padi_wreck_diver.jpg',
            'item_id' => 'sup-millionhope',
        ],
        [
            'id'      => 'shark-yolanda',
            'zone'    => 'ras-mohammed',
            'map_x'   => 312,
            'map_y'   => 404,
            'depth'   => '5 – 40 m+',
            'level'   => ['en' => 'Open Water / Advanced', 'ar' => 'Open Water / متقدم'],
            'current' => ['en' => 'Moderate – Strong Drift', 'ar' => 'انجراف متوسط إلى قوي'],
            'name'    => ['en' => 'Shark & Yolanda Reef (Ras Mohammed)', 'ar' => 'شارك ويولاندا ريف (محمية رأس محمد)'],
            'summary' => [
                'en' => 'Ranked among the top 10 dive sites on Earth: drop into the blue at Anemone City, drift past Shark Reef\'s 700 m vertical drop-off, and finish among the famous toilets and bathtubs of the 1980 Yolanda cargo wreck.',
                'ar' => 'مصنف ضمن أفضل 10 مواقع غوص في العالم: ابدأ عند مدينة شقائق النعمان وانجرف بمحاذاة جدار شارك ريف السحيق حتى حطام سفينة البضائع الشهيرة يولاندا (1980).',
            ],
            'marine'  => [
                'en' => ['Schooling barracuda & snappers', 'Giant morays', 'Napoleon wrasse', 'Blacktip sharks', 'Soft coral walls'],
                'ar' => ['أسراب الباراكودا والنهاش', 'ثعابين الموراي العملاقة', 'سمكة نابليون', 'قروش الشعاب', 'حدائق المرجان اللين'],
            ],
            'img'     => 'sea-college-dive-club-sharm-el-sheikh-group-boat-dive-red-sea-divers.jpg',
            'item_id' => 'dive-6',
        ],
        [
            'id'      => 'jackfish-alley',
            'zone'    => 'ras-mohammed',
            'map_x'   => 332,
            'map_y'   => 382,
            'depth'   => '5 – 25 m',
            'level'   => ['en' => 'Beginner Boat / Open Water', 'ar' => 'مبتدئ بالقارب / Open Water'],
            'current' => ['en' => 'Calm – Mild', 'ar' => 'هادئ – خفيف'],
            'name'    => ['en' => 'Jackfish Alley & White Island Lagoon', 'ar' => 'جاك فيش آلي والجزيرة البيضاء (رأس محمد)'],
            'summary' => [
                'en' => 'Sunbeam-filled shallow caverns at 6–14 m, a bright white sandy alley patrolled by jacks and stingrays, right beside Ras Mohammed\'s famous White Island sandbar.',
                'ar' => 'كهوف ضحلة تخترقها أشعة الشمس على عمق 6–14م وممر رملي أبيض تسبح فيه أسماك البياض والراي بجوار لسان الجزيرة البيضاء الساحر.',
            ],
            'marine'  => [
                'en' => ['Glassfish caves', 'Blue-spotted stingrays', 'Trevally', 'Triggerfish', 'Anthias'],
                'ar' => ['كهوف الأسماك الزجاجية', 'الراي الأزرق المرقط', 'أسماك التريفالي', 'سمكة الزناد', 'الأنثياس البرتقالي'],
            ],
            'img'     => 'sea-college-dive-club-sharm-el-sheikh-snorkeling-trip-family-red-sea.jpg',
            'item_id' => 'dsd-2-boat',
        ],
        [
            'id'      => 'dunraven',
            'zone'    => 'wrecks',
            'map_x'   => 246,
            'map_y'   => 392,
            'depth'   => '15 – 29 m',
            'level'   => ['en' => 'Open Water / Advanced', 'ar' => 'Open Water / متقدم'],
            'current' => ['en' => 'Mild – Moderate', 'ar' => 'خفيف – متوسط'],
            'name'    => ['en' => 'SS Dunraven Wreck (Beacon Rock, 1876)', 'ar' => 'حطام سفينة دنرافين التاريخية (1876)'],
            'summary' => [
                'en' => 'Victorian British steam-sailship that struck Beacon Rock in April 1876 and rests upside-down in two coral-encrusted sections swarming with thousands of shimmering glassfish.',
                'ar' => 'سفينة بخارية بريطانية غرقت في أبريل 1876 عند صخرة بيكون روك وتستقر مقلوبة في قسمين مكسوين بالمرجان وتملأهما آلاف الأسماك الزجاجية اللامعة.',
            ],
            'marine'  => [
                'en' => ['Cathedral of glassfish', 'Giant groupers', 'Lionfish', 'Stonefish', 'Goatfish'],
                'ar' => ['أسراب كثيفة من الأسماك الزجاجية', 'الوقار العملاق', 'سمكة الأسد', 'السمكة الصخرية', 'السلطان إبراهيم'],
            ],
            'img'     => 'courses/12_padi_wreck_diver.jpg',
            'item_id' => 'sup-dunraven',
        ],
        [
            'id'      => 'thistlegorm',
            'zone'    => 'wrecks',
            'map_x'   => 168,
            'map_y'   => 362,
            'depth'   => '16 – 30 m',
            'level'   => ['en' => 'Advanced + 20 Logged Dives', 'ar' => 'Advanced + 20 غطسة مسجلة'],
            'current' => ['en' => 'Moderate – Strong', 'ar' => 'متوسط – قوي'],
            'name'    => ['en' => 'SS Thistlegorm WWII Wreck (Sha\'ab Ali)', 'ar' => 'حطام سفينة ثيستلجورم الأسطورية (شعب علي)'],
            'summary' => [
                'en' => 'The world\'s #1 shipwreck dive: a 128 m British armed merchant ship sunk in October 1941 with intact BSA motorcycles, Bedford trucks, aircraft wings, steam locomotives and anti-aircraft guns.',
                'ar' => 'أشهر غطسة حطام سفينة في العالم: سفينة إمداد بريطانية بطول 128 مترًا غرقت عام 1941 وبداخلها دراجات نارية وشاحنات بيدفورد وقاطرات بخارية ومدافع مضادة للطائرات.',
            ],
            'marine'  => [
                'en' => ['WWII motorcycles & trucks', 'Giant batfish', 'Barracuda', 'Crocodilefish', 'Scorpionfish'],
                'ar' => ['دراجات نارية وشاحنات الحرب العالمية الثانية', 'سمك الوطواط الكبير', 'الباراكودا', 'سمكة التمساح', 'سمكة العقرب'],
            ],
            'img'     => 'sea-college-dive-club-sharm-el-sheikh-ss-thistlegorm-wreck-dive.jpg',
            'item_id' => 'sup-thistlegorm',
        ],
        [
            'id'      => 'dahab-blue-hole',
            'zone'    => 'dahab',
            'map_x'   => 450,
            'map_y'   => 72,
            'depth'   => '6 – 30 m (Rec)',
            'level'   => ['en' => 'Advanced Open Water', 'ar' => 'Advanced Open Water'],
            'current' => ['en' => 'Calm Shore Entry', 'ar' => 'شاطئي هادئ'],
            'name'    => ['en' => 'Dahab Blue Hole, Bells & Canyon', 'ar' => 'البلو هول والكانيون (رحلة دهب)'],
            'summary' => [
                'en' => 'Full-day Sinai adventure north to Dahab: descend through the dramatic chimney at The Bells along an electric-blue wall into the saddle of the famous Blue Hole, paired with the volcanic Canyon.',
                'ar' => 'مغامرة يوم كامل شمالًا إلى دهب: النزول عبر مدخنة «ذا بيلز» بمحاذاة الجدار الأزرق السحيق وصولًا إلى حافة البلو هول الشهير مع غطسة الكانيون الصخري.',
            ],
            'marine'  => [
                'en' => ['Electric-blue drop-off', 'Glassfish volcanic cavern', 'Unicornfish', 'Octopus', 'Bedouin shore lunch'],
                'ar' => ['جدار أزرق عميق', 'تجويف الكانيون البركاني', 'سمك وحيد القرن', 'الأخطبوط', 'غداء بدوي على الشاطئ'],
            ],
            'img'     => 'courses/11_padi_deep_diver.jpg',
            'item_id' => 'sup-dahab',
        ],
    ],

    'months' => [
        ['m' => ['en' => 'Jan', 'ar' => 'يناير'],  'water' => 23, 'air' => 22, 'vis' => '25–35 m', 'suit' => ['en' => '5 mm full (+ vest)', 'ar' => 'بدلة كاملة 5 مم'], 'note' => ['en' => 'Crisp winter clarity, uncrowded reefs & eagle rays', 'ar' => 'صفاء شتوي عالٍ وشعاب هادئة وأسماك الراي']],
        ['m' => ['en' => 'Feb', 'ar' => 'فبراير'], 'water' => 22, 'air' => 23, 'vis' => '25–35 m', 'suit' => ['en' => '5–7 mm full', 'ar' => 'بدلة كاملة 5–7 مم'], 'note' => ['en' => 'Coolest water of the year; calm sunny afternoons', 'ar' => 'أبرد مياه العام؛ أجواء مشمسة لطيفة نهارًا']],
        ['m' => ['en' => 'Mar', 'ar' => 'مارس'],   'water' => 22, 'air' => 25, 'vis' => '20–30 m', 'suit' => ['en' => '5 mm full', 'ar' => 'بدلة كاملة 5 مم'], 'note' => ['en' => 'Spring plankton bloom attracts rays & macro life', 'ar' => 'بداية الربيع وجذب الكائنات البحرية المتنوعة']],
        ['m' => ['en' => 'Apr', 'ar' => 'أبريل'],  'water' => 24, 'air' => 28, 'vis' => '25–30 m', 'suit' => ['en' => '5 mm full', 'ar' => 'بدلة كاملة 5 مم'], 'note' => ['en' => 'Prime shoulder season: warm air & Thistlegorm crossings', 'ar' => 'موسم مثالي للغوص ورحلات حطام ثيستلجورم']],
        ['m' => ['en' => 'May', 'ar' => 'مايو'],   'water' => 25, 'air' => 32, 'vis' => '25–35 m', 'suit' => ['en' => '3–5 mm full', 'ar' => 'بدلة 3–5 مم'], 'note' => ['en' => 'Early whale shark sightings & glass-flat mornings', 'ar' => 'هدوء البحر صباحًا وفرص رؤية القرش الحوتي']],
        ['m' => ['en' => 'Jun', 'ar' => 'يونيو'],  'water' => 27, 'air' => 35, 'vis' => '25–35 m', 'suit' => ['en' => '3 mm or shorty', 'ar' => 'بدلة 3 مم أو قصيرة'], 'note' => ['en' => 'Huge schooling snappers & barracuda at Shark Reef', 'ar' => 'تجمعات ضخمة للنهاش والباراكودا في شارك ريف']],
        ['m' => ['en' => 'Jul', 'ar' => 'يوليو'],  'water' => 28, 'air' => 37, 'vis' => '25–35 m', 'suit' => ['en' => '3 mm shorty / rashguard', 'ar' => 'بدلة قصيرة 3 مم'], 'note' => ['en' => 'Peak hammerhead dawn season at Jackson Reef (Tiran)', 'ar' => 'موسم قرش المطرقة فجرًا عند جاكسون ريف بتيران']],
        ['m' => ['en' => 'Aug', 'ar' => 'أغسطس'],  'water' => 29, 'air' => 37, 'vis' => '25–35 m', 'suit' => ['en' => '3 mm shorty / rashguard', 'ar' => 'بدلة قصيرة 3 مم'], 'note' => ['en' => 'Warmest water (29 °C) — ideal for kids & night dives', 'ar' => 'أدفأ مياه (29°م) — مثالية للأطفال والغطس الليلي']],
        ['m' => ['en' => 'Sep', 'ar' => 'سبتمبر'], 'water' => 28, 'air' => 34, 'vis' => '30–40 m', 'suit' => ['en' => '3 mm full or shorty', 'ar' => 'بدلة 3 مم'], 'note' => ['en' => 'Warm bath-like sea + milder late-summer breezes', 'ar' => 'بحر دافئ جدًا ورؤية فائقة مع اعتدال الجو']],
        ['m' => ['en' => 'Oct', 'ar' => 'أكتوبر'], 'water' => 27, 'air' => 30, 'vis' => '30–40 m', 'suit' => ['en' => '3–5 mm full', 'ar' => 'بدلة 3–5 مم'], 'note' => ['en' => 'Divers\' favourite month: 27 °C water & 35 m+ visibility', 'ar' => 'الشهر المفضل للغواصين: مياه 27°م ورؤية تتجاوز 35م']],
        ['m' => ['en' => 'Nov', 'ar' => 'نوفمبر'], 'water' => 26, 'air' => 26, 'vis' => '25–35 m', 'suit' => ['en' => '5 mm full', 'ar' => 'بدلة كاملة 5 مم'], 'note' => ['en' => 'Comfortable 26 °C air & water; great for PADI courses', 'ar' => 'توازن رائع 26°م للهواء والماء؛ ممتاز لدورات PADI']],
        ['m' => ['en' => 'Dec', 'ar' => 'ديسمبر'], 'water' => 24, 'air' => 23, 'vis' => '25–35 m', 'suit' => ['en' => '5 mm full', 'ar' => 'بدلة كاملة 5 مم'], 'note' => ['en' => 'Winter sunshine escape with crystal-clear walls', 'ar' => 'شمس شتوية دافئة وصفاء مذهل للجدران المرجانية']],
    ],

    'faqs' => [
        [
            'What is the best month to scuba dive in Sharm El Sheikh?',
            'Sharm El Sheikh is a true 12-month diving destination, with September to November and April to June offering the sweet spot of 25–28 °C water, comfortable 26–32 °C air temperatures and 30–40 m visibility. July and August have the warmest 29 °C water and dawn hammerhead sightings at Jackson Reef.',
            'ما هو أفضل شهر للغوص في شرم الشيخ؟',
            'تُعد شرم الشيخ وجهة غوص طوال الـ 12 شهرًا، وتُعتبر الفترة من سبتمبر إلى نوفمبر ومن أبريل إلى يونيو الأفضل من حيث اعتدال الحرارة (25–28°م للماء) وصفاء الرؤية (30–40م)، بينما يتميز يوليو وأغسطس بأدفأ مياه (29°م) وموسم قرش المطرقة في تيران.',
        ],
        [
            'Which dive sites are closest to Sea College Dive Club in Al Montazah?',
            'Our beachfront base at Cyrene Sharm in Al Montazah is directly opposite the Straits of Tiran! Our Al Montazah House Reef is 0 minutes away, Ras Nasrani is 5 minutes away, and Gordon, Thomas, Woodhouse and Jackson Reefs in Tiran are just 15–25 minutes away by boat or speedboat.',
            'ما هي أقرب مواقع الغوص إلى مركز سي كوليدج في المنتزه؟',
            'يقع مقرنا على شاطئ سيرين شرم بالمنتزه في مواجهة مضيق تيران مباشرة؛ فالشعاب المنزلية أمامنا مباشرة، ورأس نصراني على بُعد 5 دقائق، وشعاب جزيرة تيران الأربعة (جوردون وتوماس وودهاوس وجاكسون) على بُعد 15–25 دقيقة فقط بالقارب.',
        ],
        [
            'Can beginners dive Ras Mohammed or Tiran Island?',
            'Yes! Non-certified beginners can book Discover Scuba Diving by Boat (€70 for 1 dive or €100 for 2 dives) with 1-on-1 instructor supervision on sheltered reefs in Tiran or Ras Mohammed up to 12 m, or start on our calm Al Montazah House Reef from €30.',
            'هل يمكن للمبتدئين الغوص في رأس محمد أو جزيرة تيران؟',
            'نعم! يمكن لغير المعتمدين حجز تجربة الغوص بالقارب Discover Scuba Diving (70€ لغطسة أو 100€ لغطستين) برفقة مدرب خاص على الشعاب الهادئة في تيران أو رأس محمد حتى عمق 12م، أو البدء من الشعاب المنزلية بـ 30€.',
        ],
        [
            'What certification do I need to dive the SS Thistlegorm wreck?',
            'To dive the SS Thistlegorm (16–30 m in open water at Sha\'ab Ali), divers require PADI Advanced Open Water (or equivalent 30 m certification) and at least 20 logged dives. Open Water divers with 18 m certification can dive the shallower Million Hope wreck at Nabq.',
            'ما الشهادة المطلوبة للغوص على حطام سفينة ثيستلجورم؟',
            'يتطلب الغوص على حطام ثيستلجورم (عمق 16–30م في شعب علي) شهادة PADI Advanced Open Water أو ما يعادلها مع 20 غطسة مسجلة على الأقل، بينما يمكن لحاملي Open Water الغوص على حطام مليون هوب الأقل عمقًا في نبق.',
        ],
        [
            'When do marine park fees (€5/day) and boat fees (€10/day) apply?',
            'Certified dive packages cover guides, 12L air tanks, weights and Sharm hotel transfers. On boat days, a €10/day Boat Supplement (including hot buffet lunch & drinks on full-day boats) and €5/day Marine Park Fee apply. Dives on our Al Montazah House Reef have €0 boat fee and €0 park fee.',
            'متى تُطبق رسوم المحمية (5€/يوم) ورسوم القارب (10€/يوم)؟',
            'تشمل باقات المعتمدين المرشد والأسطوانات والأوزان والانتقالات. في أيام القارب تُضاف 10€ يوميًا رسوم القارب (تشمل الغداء والمشروبات في رحلات اليوم الكامل) و5€ رسوم المحمية، بينما الغوص على الشعاب المنزلية معفى تمامًا (0€ قارب و0€ محمية).',
        ],
    ],
]);
