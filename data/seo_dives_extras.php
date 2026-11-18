<?php
/**
 * Rich SEO & AEO content for 21 Daily Supplements, Private Guide, Equipment/Tank Rentals & Diving Insurance items.
 */
declare(strict_types=1);

$buildRental = static function (
    string $h1En,
    string $h1Ar,
    string $titleEn,
    string $titleAr,
    int $price,
    string $aeoEn,
    string $aeoAr,
    string $p1En,
    string $p2En,
    string $p1Ar,
    string $p2Ar,
    string $specItemEn,
    string $specItemAr,
    string $reqEn,
    string $reqAr,
    array $related,
    array $extraFaq
): array {
    return [
        'h1' => ['en' => $h1En, 'ar' => $h1Ar],
        'meta_title' => ['en' => $titleEn, 'ar' => $titleAr],
        'meta_desc' => [
            'en' => mb_substr($aeoEn, 0, 156),
            'ar' => mb_substr($aeoAr, 0, 156),
        ],
        'aeo_answer' => ['en' => $aeoEn, 'ar' => $aeoAr],
        'overview' => [
            'en' => [$p1En, $p2En],
            'ar' => [$p1Ar, $p2Ar],
        ],
        'specs' => [
            ['Daily Rate', 'السعر اليومي', "€{$price} per day (covers 2–3 dives/day)", "{$price}€ لليوم (يغطي 2–3 غطسات يوميًا)"],
            ['Specification', 'المواصفات الفنية', $specItemEn, $specItemAr],
            ['Requirement', 'المتطلبات', $reqEn, $reqAr],
            ['Full Set Tip', 'نصيحة التوفير', 'Renting 3+ main items? Our Full Equipment Set is €25/day', 'عند حاجتك لعدة قطع، الطقم الكامل بـ25€ فقط لليوم'],
            ['Courses & Intro Dives', 'الدورات والغطسات التجريبية', 'Standard scuba equipment is already FREE in all PADI courses & intro dives', 'المعدات القياسية مشمولة مجانًا في جميع دورات PADI والغطسات التجريبية'],
        ],
        'steps' => [
            ['Pre-Select Size at Booking', 'تحديد المقاس عند الحجز', 'Add the item to your booking or share your height, weight and shoe size via Fast-Track Check-In.', 'أضف القطعة إلى سلة الحجز أو سجل مقاسك مسبقًا عبر نموذج التسجيل السريع.'],
            ['Wet-Room Fitting & Pressure Test', 'القياس والفحص الفني بالمركز', 'Our equipment technician fits and bench-tests your gear at Cyrene Sharm before loading your boat crate.', 'يقوم فني المعدات بضبط المقاس وفحص الأداء قبل تحميل صندوق معداتك على القارب.'],
            ['Freshwater Rinse & Sanitization', 'الغسل والتعقيم اليومي', 'Crew transport, rinse with disinfectant and hang your rental gear in our shaded drying room after diving.', 'يتولى فريقنا شطف وتعقيم وتجفيف المعدات في الغرفة المخصصة بعد انتهاء الغوص.'],
        ],
        'included' => [
            'en' => ['Full-day rental across all boat or shore dives that day', 'Technician inspection, sanitization & boat crate loading'],
            'ar' => ['استخدام طوال اليوم في جميع غطسات القارب أو الشاطئ', 'فحص فني وتعقيم ونقل المعدات إلى القارب'],
        ],
        'excluded' => [
            'en' => ['Base certified dive package', 'Loss or negligent damage liability (checked out in diver\'s name)'],
            'ar' => ['باقة الغوص الأساسية للمعتمدين', 'مسؤولية الفقدان أو التلف الناتج عن سوء الاستخدام'],
        ],
        'ideal_for' => [
            'en' => ['Certified divers travelling light on airline baggage allowances to Sharm El Sheikh', 'Divers who pack personal mask/computer and rent only bulky items on site'],
            'ar' => ['الغواصون المعتمدون الراغبون في السفر بحقائب خفيفة دون شحن معدات ثقيلة', 'من يحضر قناعه وكمبيوتره الشخصي ويستأجر القطع الكبيرة فقط في شرم الشيخ'],
        ],
        'schema_type' => 'Service',
        'compare_mode' => 'equipment',
        'review_idx' => 1,
        'related' => $related,
        'faqs' => [
            $extraFaq,
            [
                'Is equipment rental charged per dive or per day?',
                "All Sea College Dive Club rentals are billed per day (€{$price}/day), covering all 2 or 3 dives you do that day.",
                'هل يُحتسب إيجار المعدات بالغطسة أم باليوم؟',
                "يُحتسب الإيجار باليوم الكامل ({$price}€ لليوم) ويشمل جميع غطساتك (غطستان أو 3 غطسات) في نفس اليوم.",
            ],
        ],
    ];
};

$buildInsurance = static function (int $days, string $tripFitEn, string $tripFitAr): array {
    return [
        'h1' => [
            'en' => "Short-Term Diving Insurance ({$days} Days) in Sharm El Sheikh — Hyperbaric Coverage",
            'ar' => "تأمين الغوص قصير المدة ({$days} أيام) في شرم الشيخ — تغطية غرفة الضغط",
        ],
        'meta_title' => [
            'en' => "{$days}-Day Scuba Diving Insurance Sharm El Sheikh | Sea College",
            'ar' => "تأمين الغوص لمدة {$days} أيام في شرم الشيخ | سي كوليدج دايف كلوب",
        ],
        'meta_desc' => [
            'en' => "Arrange {$days}-day short-term scuba diving & hyperbaric chamber insurance at Sea College Dive Club in Sharm El Sheikh. Instant on-site activation.",
            'ar' => "احصل على تأمين الغوص وتغطية غرفة الضغط لمدة {$days} أيام في شرم الشيخ عبر سي كوليدج دايف كلوب بتفعيل فوري قبل الغوص.",
        ],
        'aeo_answer' => [
            'en' => "Sea College Dive Club offers {$days}-day Short-Term Scuba Diving Insurance in Sharm El Sheikh (current underwriter tariff confirmed at booking/check-in). Standard travel policies frequently exclude scuba depth or hyperbaric oxygen therapy; this {$days}-day plan provides dedicated Red Sea decompression chamber and diving medical emergency coverage across your dive days.",
            'ar' => "يوفر مركز سي كوليدج دايف كلوب تأمين غوص قصير المدة لمدة {$days} أيام في شرم الشيخ (يُؤكد السعر الرسمي لجهة التأمين عند الحجز أو تسجيل الوصول). تغطي هذه الوثيقة نفقات الطوارئ الطبية الخاصة بالغوص وعلاج غرفة إعادة الضغط طوال {$days} أيام.",
        ],
        'overview' => [
            'en' => [
                $tripFitEn,
                'Sharm El Sheikh has specialized hyperbaric medical facilities led by Dr. Adel Taher\'s Hyperbaric Medical Center; carrying valid diving insurance ensures immediate cashless/covered hyperbaric assessment and treatment if ever required.',
            ],
            'ar' => [
                $tripFitAr,
                'تضم شرم الشيخ مركز طب الأعماق وغرفة الضغط المتخصصة؛ ويضمن لك تأمين الغوص الساري تغطية فورية لتكاليف الفحص والعلاج الطارئ دون أعباء مالية مفاجئة.',
            ],
        ],
        'specs' => [
            ['Coverage Window', 'مدة التغطية', "{$days} consecutive days", "{$days} أيام متتالية"],
            ['Price', 'السعر', 'On request (official Egyptian/DAN short-term tariff at check-in)', 'عند الطلب (بسعر الوثيقة الرسمي عند الحجز)'],
            ['Covered Scope', 'نطاق التغطية', 'Hyperbaric chamber & acute diving medical emergencies in Egypt', 'غرفة إعادة الضغط والطوارئ الطبية المرتبطة بالغوص'],
            ['Bundle Note', 'ملاحظة العروض', 'Already INCLUDED free inside our 4 PADI Course Bundles!', 'مشمول مجانًا بالفعل ضمن باقات عروض الدورات الأربع!'],
        ],
        'steps' => [
            ['Check Existing Policy First', 'مراجعة وثيقة سفرك الحالية', 'If you already hold active DAN Europe/World or specialist scuba cover to your certified depth, simply show your policy number at check-in.', 'إذا كان لديك تأمين غوص سارٍ مثل DAN يكفي إبراز رقم الوثيقة عند الوصول.'],
            ['Add Short-Term Cover if Needed', 'إضافة التأمين قصير المدة عند الحاجة', "Select {$days}-day Diving Insurance in your booking request so our front desk prepares registration.", "اختر تأمين {$days} أيام في طلب حجزك ليجهزه فريق الاستقبال."],
            ['Instant Activation Before Dive 1', 'تفعيل فوري قبل أول غطسة', 'Policy activates with your passport details prior to boarding the boat or entering the reef.', 'تُفعل الوثيقة ببيانات جواز سفرك مباشرة قبل النزول لأول غطسة.'],
        ],
        'included' => [
            'en' => ["{$days}-day Red Sea scuba medical & hyperbaric chamber coverage", 'Dive center registration assistance'],
            'ar' => ["تغطية طوارئ الغوص وغرفة الضغط لمدة {$days} أيام", 'إجراءات التسجيل والتفعيل في المركز'],
        ],
        'excluded' => [
            'en' => ['Non-diving general travel/luggage claims', 'Diving beyond certified recreational depth limits'],
            'ar' => ['مطالبات السفر العامة غير المرتبطة بالغوص', 'تجاوز حدود العمق المسموح بها لشهادتك'],
        ],
        'ideal_for' => [
            'en' => ["Certified divers without annual DAN coverage booking {$days}-day dive plans", 'Student divers on individual PADI courses wanting extra peace of mind'],
            'ar' => ["الغواصون المعتمدون غير المشتركين في تأمين سنوي خلال إجازة {$days} أيام", 'طلاب الدورات الفردية الراغبون في تغطية إضافية مريحة'],
        ],
        'schema_type' => 'Service',
        'compare_mode' => 'none',
        'review_idx' => 1,
        'related' => ['ins-3', 'ins-7', 'ins-10', 'ins-14'],
        'faqs' => [
            [
                'Is diving insurance mandatory in Sharm El Sheikh?',
                'Valid diving medical/chamber coverage is strongly recommended for all Red Sea divers and mandatory on deep/wreck profiles; if your holiday travel insurance does not explicitly cover scuba to your certified depth (18 m / 30 m / 40 m), short-term cover can be issued on the spot.',
                'هل تأمين الغوص إلزامي في شرم الشيخ؟',
                'يُوصى به بشدة لجميع الغواصين وهو ضروري في غطسات الأعماق والحطام؛ وإذا كانت وثيقة السفر العادية لا تغطي الغوص صراحةً يمكنك إصدار التأمين قصير المدة فورًا بالمركز.',
            ],
        ],
    ];
};

return [
    /* ==================== DAILY FEES & PRIVATE GUIDE ==================== */
    'fee-boat' => [
        'h1' => [
            'en' => 'Boat Trip Daily Supplement in Sharm El Sheikh (€10/Day — Includes Lunch & Drinks)',
            'ar' => 'رسوم القارب اليومية في شرم الشيخ (10€ لليوم — شاملة الغداء والمشروبات)',
        ],
        'meta_title' => [
            'en' => 'Dive Boat Daily Supplement Sharm El Sheikh | €10/Day | Sea College',
            'ar' => 'رسوم القارب اليومية للغوص شرم الشيخ | 10€ شاملة الغداء والمشروبات',
        ],
        'meta_desc' => [
            'en' => 'Transparent breakdown of Sea College Dive Club\'s €10/day Boat Trip Supplement in Sharm El Sheikh: yacht boarding, freshly cooked buffet lunch & onboard drinks.',
            'ar' => 'توضيح كامل لرسوم القارب اليومية (10€ لليوم) في سي كوليدج دايف كلوب بشرم الشيخ: رسوم اليخت وغداء البوفيه الطازج والمشروبات على متن القارب.',
        ],
        'aeo_answer' => [
            'en' => 'The Boat Trip Supplement at Sea College Dive Club is €10 per person, per boat day in Sharm El Sheikh. Applied on days when certified package divers or PADI course students dive from our motor yacht rather than the shore, this €10 daily fee covers marina yacht operations plus freshly cooked buffet lunch, water, tea, coffee and soft drinks on full-day boat trips.',
            'ar' => 'تبلغ رسوم القارب اليومية (Boat Trip Supplement) في سي كوليدج دايف كلوب 10€ للفرد عن كل يوم غوص بالقارب في شرم الشيخ. تُطبق هذه الرسوم فقط في الأيام التي يختار فيها الغواص المعتمد أو طالب الدورة الغوص من اليخت بدلًا من الشاطئ، وتشمل تشغيل اليخت وغداء البوفيه الطازج والمياه والشاي والقهوة والمشروبات في رحلات اليوم الكامل.',
        ],
        'overview' => [
            'en' => [
                'Because our certified dive packages (€85 for 2 dives, €35/dive for 3–10 dives) can be used flexibly from either the shore or our daily dive boat, keeping the €10/day Boat Supplement separate means you never overpay on shore-dive days.',
                'Even better, €10 covers your entire yacht day — including freshly prepared onboard lunch and drinks on full-day trips — whether you do 2 or 3 dives that day!',
            ],
            'ar' => [
                'لأن باقات الغوص المعتمدة لدينا مرنة وتصلح للغوص من الشاطئ أو بالقارب، فإن فصل رسوم القارب اليومية (10€) يضمن عدم دفعك أي تكلفة قارب في أيام الغوص الشاطئي.',
                'ومقابل 10€ فقط لليوم تحصل على يوم كامل على متن اليخت شاملًا غداء البوفيه والمشروبات الساخنة والباردة سواء نفذت غطستين أو 3 غطسات في ذلك اليوم.',
            ],
        ],
        'specs' => [
            ['Price', 'السعر', '€10 per person / per boat day', '10€ للفرد عن كل يوم قارب'],
            ['Meals Included', 'الوجبات المشمولة', 'Buffet lunch + drinks on full-day trips; drinks on half-day trips', 'غداء بوفيه ومشروبات لليوم الكامل؛ مشروبات لنصف اليوم'],
            ['Already Included In', 'مشمولة مسبقًا في', 'Boat Intro Dives (€70/€100), Snorkeling Boats (€55) & Private Boats', 'الغطسات التجريبية بالقارب (70€/100€) ورحلات السنوركل والقوارب الخاصة'],
            ['Exempt On', 'معفاة في', 'House Reef shore dives', 'غطسات الشعاب المنزلية الشاطئية'],
        ],
        'steps' => [
            ['Count Your Planned Boat Days', 'تحديد عدد أيام القارب', 'A 6-dive / 3-day boat package uses 3 units of Boat Supplement (€30 total).', 'على سبيل المثال: باقة 6 غطسات على مدار 3 أيام بالقارب تتطلب 3 وحدات (30€ إجمالًا).'],
            ['Pair with €5/Day Marine Park Fee', 'إضافة رسوم المحمية اليومية (5€)', 'Offshore boat days in Ras Mohammed or Tiran also carry the €5/day environmental park ticket.', 'تُضاف في أيام القارب تذكرة المحمية البيئية (5€ لليوم).'],
            ['Enjoy Full Yacht Hospitality', 'الاستمتاع بضيافة اليخت الكاملة', 'Step aboard with lunch, cold soft drinks, tea, coffee and spacious dive-deck support ready.', 'استمتع بيوم مريح على اليخت مع الغداء والمشروبات ومساعدة الطاقم البحري.'],
        ],
        'included' => [
            'en' => ['Full-day or half-day dive yacht berth & marina port clearance', 'Freshly cooked onboard buffet lunch & drinks (full-day trips)', 'Cylinder handling & swim-platform crew assistance'],
            'ar' => ['مقعدك على يخت الغوص وتصاريح المارينا', 'غداء بوفيه ساخن ومشروبات على رحلات اليوم الكامل', 'مساعدة طاقم البحرية في تجهيز الأسطوانات والنزول والصعود'],
        ],
        'excluded' => [
            'en' => ['€5/day Marine Park Fee (separate environmental ticket)', 'Base dive package or PADI course tuition'],
            'ar' => ['رسوم المحمية البحرية (5€ لليوم)', 'باقة الغوص الأساسية أو رسوم الدورة'],
        ],
        'ideal_for' => [
            'en' => ['Certified divers and PADI Open Water / Advanced students joining daily Ras Mohammed & Tiran boats', 'Non-diving companions wishing to ride along should book the €55 Boat Snorkeling ticket instead'],
            'ar' => ['الغواصون المعتمدون وطلاب الدورات المشاركون في رحلات يخت رأس محمد وتيران', 'المرافقون غير الغواصين يحجزون تذكرة رحلة السنوركل (55€) بدلًا من هذه الإضافة'],
        ],
        'schema_type' => 'Service',
        'compare_mode' => 'none',
        'review_idx' => 0,
        'related' => ['fee-park', 'dive-2', 'dive-6', 'dive-10'],
        'faqs' => [
            ['Do I pay €10 per dive or €10 per day?', 'Only €10 per day! Even if you complete 3 boat dives on the same day, you only pay a single €10 Boat Trip Supplement.', 'هل أدفع 10€ لكل غطسة أم لكل يوم؟', '10€ لليوم الكامل فقط! حتى لو نفذت 3 غطسات من القارب في نفس اليوم تدفع 10€ واحدة فقط.'],
            ['Do beginner Boat Intro Dives (€70 / €100) pay this €10 fee?', 'No — Quick Discover Scuba by Boat (€70), 2-Dive Boat Intro (€100) and Ras Mohammed Snorkeling (€55) already include boat, lunch and park fees.', 'هل تُضاف الـ10€ على الغطسات التجريبية بالقارب (70€ / 100€)؟', 'لا، تذاكر الغطسات التجريبية بالقارب ورحلات السنوركل شاملة مسبقًا لرسوم القارب والغداء والمحمية.'],
        ],
    ],

    'fee-park' => [
        'h1' => [
            'en' => 'Ras Mohammed & Straits of Tiran Marine Park Fee in Sharm El Sheikh (€5/Day)',
            'ar' => 'رسوم المحميات البحرية في شرم الشيخ — رأس محمد ومضيق تيران (5€ لليوم)',
        ],
        'meta_title' => [
            'en' => 'Sharm El Sheikh Marine Park Fee | €5/Day Ras Mohammed & Tiran',
            'ar' => 'رسوم المحمية البحرية شرم الشيخ | 5€ يوميًا لرأس محمد وتيران',
        ],
        'meta_desc' => [
            'en' => 'Guide to the €5 per-person, per-day Marine Park environmental fee in Sharm El Sheikh (Ras Mohammed, Straits of Tiran, Nabq & coastal reef zones).',
            'ar' => 'كل ما تحتاج معرفته عن رسوم المحميات البحرية البيئية (5€ للفرد يوميًا) في شرم الشيخ لمناطق رأس محمد ومضيق تيران ونبق.',
        ],
        'aeo_answer' => [
            'en' => 'The Marine Park Fee in Sharm El Sheikh is €5 per person, per day across all protected reef zones — including Ras Mohammed National Park, the Straits of Tiran, Nabq and local coastal moorings — except Sea College Dive Club\'s own House Reef. Collected on certified dive packages and PADI course boat days, it funds mooring buoys and Red Sea reef conservation.',
            'ar' => 'تبلغ رسوم المحميات البحرية في شرم الشيخ 5€ للفرد يوميًا لجميع مناطق المحميات الطبيعية (رأس محمد، مضيق تيران، نبق والمرابط الساحلية) باستثناء الشعاب المنزلية لمركز سي كوليدج. تُحصل هذه الرسوم في أيام القوارب لدعم صيانة عوامات الربط وحماية الشعاب المرجانية بالبحر الأحمر.',
        ],
        'overview' => [
            'en' => [
                'Almost the entire coastline and offshore island system of South Sinai is protected under Egyptian Environmental Affairs Agency (EEAA) marine reserves — the main reason Sharm El Sheikh\'s coral walls remain among the healthiest on the planet.',
                'Only €5 per diving day applies on certified packages and course boat trips, while House Reef shore dives carry €0 park fees.',
            ],
            'ar' => [
                'تخضع معظم سواحل وجزر جنوب سيناء لحماية المحميات الطبيعية المصرية، وهو السر وراء بقاء الشعاب المرجانية في رأس محمد وتيران من الأكثر حيوية ونقاءً عالميًا.',
                'تبلغ الرسوم 5€ فقط عن كل يوم غوص خارج الشعاب المنزلية، بينما تُعفى غطسات الشعاب المنزلية منها تمامًا.',
            ],
        ],
        'specs' => [
            ['Price', 'السعر', '€5 per person / per day', '5€ للفرد يوميًا'],
            ['Applies To', 'تُطبق على', 'Ras Mohammed, Tiran, Nabq & Sharm marine park zones', 'رأس محمد ومضيق تيران ونبق وكافة مناطق المحمية'],
            ['Exempt Zone', 'الموقع المعفى', 'Al Montazah House Reef shore dives (€0)', 'الشعاب المنزلية بالمنتزه (0€)'],
            ['Already Included In', 'مشمولة مسبقًا في', 'Snorkeling trips (€35–€55) & Boat Intro Dives (€70/€100)', 'رحلات السنوركل والغطسات التجريبية بالقارب'],
        ],
        'steps' => [
            ['Manifest Registration', 'تسجيل الكشف اليومي', 'Provide a clear photo of your passport ID page so our office issues your daily park entry ticket.', 'تزويد مكتب الاستقبال بصورة جواز السفر لإصدار تذكرة المحمية الرسمية.'],
            ['Dive Protected Moorings', 'الغوص على المرابط المحمية', 'Enjoy anchor-free mooring buoys protecting delicate table and soft corals.', 'الاستمتاع بالغوص في مواقع محمية مزودة بمرابط ثابتة تمنع إلقاء المراسي على المرجان.'],
        ],
        'included' => [
            'en' => ['Official daily EEAA South Sinai Marine Park permit processing'],
            'ar' => ['رسوم وتصريح الدخول اليومي الرسمي لمحميات جنوب سيناء'],
        ],
        'excluded' => [
            'en' => ['€10/day Boat Trip Supplement & base dive package'],
            'ar' => ['رسوم القارب اليومية (10€) وباقة الغوص الأساسية'],
        ],
        'ideal_for' => [
            'en' => ['Certified divers calculating an exact, surprise-free Red Sea trip budget online'],
            'ar' => ['الغواصون الراغبون في حساب التكلفة الإجمالية الدقيقة لرحلتهم بشفافية تامة'],
        ],
        'schema_type' => 'Service',
        'compare_mode' => 'none',
        'review_idx' => 0,
        'related' => ['fee-boat', 'dive-6', 'dive-10', 'snork-ras-adult'],
        'faqs' => [
            ['Why is passport information required for Marine Park boat trips?', 'Egyptian Port Police and National Park rangers require every passenger\'s full name, nationality and passport number on the official yacht manifest prior to departure.', 'لماذا يُطلب جواز السفر قبل رحلات القارب؟', 'تشترط شرطة الموانئ وإدارة المحميات تسجيل الاسم والجنسية ورقم جواز السفر لكل راكب في كشف اليخت الرسمي قبل الإبحار.'],
        ],
    ],

    'private-guide' => [
        'h1' => [
            'en' => 'Private PADI Dive Guide in Sharm El Sheikh (€75/Day — 1-on-1 or Private Group)',
            'ar' => 'مرشد غوص PADI خاص في شرم الشيخ (75€ لليوم — فردي أو لمجموعتك الخاصة)',
        ],
        'meta_title' => [
            'en' => 'Private Scuba Dive Guide Sharm El Sheikh | €75/Day | Sea College',
            'ar' => 'مرشد غوص خاص شرم الشيخ | 75€ لليوم | سي كوليدج دايف كلوب',
        ],
        'meta_desc' => [
            'en' => 'Hire a dedicated Private PADI Dive Guide in Sharm El Sheikh (€75/day). Unhurried photography pacing, family safety & mandatory 12 m supervision for ages 10–11.',
            'ar' => 'احجز مرشد غوص PADI خاصًا بك أو بمجموعتك في شرم الشيخ (75€ لليوم): سرعة مخصصة للتصوير الفوتوغرافي وإشراف إلزامي للأطفال 10–11 سنة وScuba Diver.',
        ],
        'aeo_answer' => [
            'en' => 'Hiring a Private PADI Dive Guide at Sea College Dive Club in Sharm El Sheikh costs €75 per day on top of your dive package. Ideal for underwater photographers, nervous returners or private couples/families who want custom pacing without group constraints, a private guide is also mandatory under safety rules for Junior Divers aged 10–11 and certified PADI Scuba Divers (12-metre max depth).',
            'ar' => 'تبلغ تكلفة حجز مرشد غوص PADI خاص في سي كوليدج دايف كلوب بشرم الشيخ 75€ لليوم الواحد فوق باقة الغوص. يُعد الخيار المثالي للمصورين تحت الماء والأزواج والعائلات الباحثين عن خصوصية وإيقاع غوص مخصص، كما أنه إلزامي قانونًا للأطفال المعتمدين بعمر 10–11 سنة ولحاملي شهادة PADI Scuba Diver (بحد عمق 12 مترًا).',
        ],
        'overview' => [
            'en' => [
                'Standard guided boat groups are already small and ability-matched, yet sometimes nothing beats having your own dedicated Red Sea expert: linger 10 minutes over a single clownfish anemone for macro strobes, surface whenever your air dictates, or ascend at your own family pace.',
                'For families with 10- or 11-year-old Junior Open Water divers—or guests holding the 12-metre PADI Scuba Diver certification—booking a Private Guide ensures strict depth adherence without limiting the rest of a deeper boat group.',
            ],
            'ar' => [
                'رغم أن مجموعات الغوص لدينا صغيرة ومتقاربة المستوى، إلا أن وجود مرشد PADI مخصص لك وحدك (أو لأسرتك) يمنحك حرية مطلقة للتوقف الطويل أمام الكائنات النادرة للتصوير وعدم الارتباط باستهلاك هواء الآخرين.',
                'كما يضمن المرشد الخاص الالتزام التام بحدود العمق الآمنة (12 مترًا) للأطفال بعمر 10–11 سنة ولحاملي شهادة PADI Scuba Diver.',
            ],
        ],
        'specs' => [
            ['Daily Rate', 'السعر اليومي', '€75 per day (covers your private buddy pair / family unit)', '75€ لليوم الواحد'],
            ['Mandatory For', 'إلزامي لـ', 'Junior Divers aged 10–11 & PADI Scuba Divers (12 m max)', 'الغواصين الصغار (10–11 سنة) وحاملي شهادة Scuba Diver (حد 12م)'],
            ['Languages', 'اللغات المتاحة', 'English, Arabic, Russian, German, Italian & French', 'العربية، الإنجليزية، الروسية، الألمانية، الإيطالية والفرنسية'],
        ],
        'steps' => [
            ['Request Language & Focus', 'تحديد اللغة وهدف الغوص', 'Let us know whether your priority is underwater photography, relaxed confidence-building or Junior Diver supervision.', 'أخبرنا بلغتك المفضلة وهدفك (تصوير فوتوغرافي، هدوء واطمئنان، أو مرافقة أطفال).'],
            ['Private Pre-Dive Briefings', 'شرح خاص ومسار مفصل لك', 'Plan custom entry points, depth profiles and marine-life wishlists before every dive.', 'تخطيط مسار الغطسة والعمق وفق رغبتك ومستواك قبل كل نزول للماء.'],
            ['Full-Day Dedicated In-Water Support', 'مرافقة حصرية طوال اليوم', 'Your PADI pro stays exclusively with you across all boat or shore dives that day.', 'يبقى مرشدك مخصصًا لك حصريًا في جميع غطسات ذلك اليوم.'],
        ],
        'included' => [
            'en' => ['Dedicated PADI Instructor or Divemaster for the full diving day', 'Tailored briefings, marine life spotting & photography patience'],
            'ar' => ['مدرب أو مرشد PADI متفرغ لك طوال يوم الغوص', 'شرح خاص ومساعدة في رصد الكائنات البحرية النادرة'],
        ],
        'excluded' => [
            'en' => ['Base certified dive package, daily Boat (€10) & Marine Park (€5) fees'],
            'ar' => ['باقة الغوص الأساسية ورسوم القارب (10€) والمحمية (5€)'],
        ],
        'ideal_for' => [
            'en' => ['Underwater wide-angle & macro photographers', 'Junior Divers aged 10–11 & PADI Scuba Divers (mandatory)', 'Couples or anxious divers wanting calm 1-on-1 attention'],
            'ar' => ['المصورون الفوتوغرافيون ومصورو الفيديو تحت الماء', 'الأطفال المعتمدون (10–11 سنة) وحاملو Scuba Diver (إلزامي)', 'الأزواج والغواصون الباحثون عن أقصى درجات الخصوصية والاطمئنان'],
        ],
        'schema_type' => 'Service',
        'compare_mode' => 'none',
        'review_idx' => 0,
        'related' => ['dive-2', 'dive-6', 'scuba-diver', 'private-1-5'],
        'faqs' => [
            ['Is €75 per person or for my couple/buddy pair?', '€75/day hires one dedicated Private Guide who can guide you 1-on-1 or guide you and your buddy/family together (within safe PADI depth and age ratios).', 'هل سعر الـ75€ للفرد أم للزوجين معًا؟', 'سعر الـ75€ لليوم هو أجر المرشد الخاص الواحد، ويمكنه مرافقتك بمفردك أو مرافقتك مع زميلك/عائلتك ضمن نسب الأمان المعتمدة من PADI.'],
        ],
    ],

    /* ==================== EQUIPMENT & TANK RENTAL ==================== */
    'eq-full' => $buildRental(
        'Full Scuba Equipment Rental in Sharm El Sheikh (€25/Day — Save €16/Day)',
        'تأجير طقم معدات غوص كامل في شرم الشيخ (25€ لليوم — وفّر 16€ يوميًا)',
        'Full Scuba Gear Rental Sharm El Sheikh | €25/Day | Sea College',
        'تأجير طقم معدات غوص كامل شرم الشيخ | 25€ لليوم | سي كوليدج',
        25,
        'Full Scuba Equipment Rental at Sea College Dive Club in Sharm El Sheikh costs €25 per day — saving €16/day compared with renting BCD (€10), regulator (€13), wetsuit (€10), mask (€4) and fins (€4) separately (€41). Serviced at our PADI 5 Star equipment room in Al Montazah, one €25 daily fee covers all 2–3 dives you complete that day (dive computer €10 and torch €6 rented separately).',
        'يبلغ إيجار طقم معدات الغوص الكامل في سي كوليدج دايف كلوب بشرم الشيخ 25€ لليوم الواحد — بتوفير 16€ يوميًا مقارنة باستئجار القطع منفردة (BCD بـ10€ + منظم بـ13€ + بدلة بـ10€ + قناع بـ4€ + زعانف بـ4€ = 41€). يشمل الإيجار اليومي جميع غطساتك في نفس اليوم (لا يشمل الكمبيوتر 10€ أو الكشاف 6€).',
        'Skip airline oversized-baggage fees and travel light to Sharm El Sheikh: our PADI 5 Star wet room stocks regularly serviced BCDs, regulators with octopus and pressure gauges, 5mm/shorty Red Sea wetsuits, silicone masks and fins from XS to XXL.',
        'Your personal rental crate is tagged with your name, loaded onto our dive boat each morning and rinsed/sanitized back at Cyrene Sharm each afternoon.',
        'وفّر رسوم الوزن الزائد في الطيران وسافر بخفة إلى شرم الشيخ؛ تضم غرفة المعدات في منتجعنا المعتمد PADI خمس نجوم أطقم غوص مصانة بانتظام بجميع المقاسات من XS إلى XXL.',
        'يُخصص لك صندوق معدات باسمك يُنقل إلى القارب صباحًا ويُشطف ويُعقم بعد انتهاء الغوص يوميًا.',
        'BCD + Regulator (w/ octopus & SPG) + Wetsuit + Mask + Fins',
        'سترة طفو BCD + منظم تنفس كامل + بدلة غوص + قناع + زعانف',
        'Certified divers on dive packages (courses & intro dives already include gear free)',
        'للغواصين المعتمدين (أما الدورات والغطسات التجريبية فتشمل المعدات مجانًا)',
        ['eq-computer', 'eq-12nx', 'dive-6', 'dive-2'],
        ['Does the €25 Full Equipment Set include a dive computer or torch?', 'No — the €25 set includes BCD, regulator, wetsuit, mask and fins (12L tank & weights are already inside your dive package). Add a Dive Computer for €10/day or Underwater Torch for €6/day if needed.', 'هل يشمل الطقم الكامل (25€) كمبيوتر الغوص أو الكشاف؟', 'يشمل الطقم الكامل BCD والمنظم والبدلة والقناع والزعانف (والأسطوانة والأوزان مشمولة في باقة الغوص)، ويمكن إضافة كمبيوتر غوص بـ10€ أو كشاف بـ6€ عند الحاجة.']
    ),

    'eq-mask' => $buildRental(
        'Scuba & Snorkeling Mask Rental in Sharm El Sheikh (€4/Day)',
        'تأجير قناع غوص وسنوركل (Mask) في شرم الشيخ (4€ لليوم)',
        'Dive Mask Rental Sharm El Sheikh | €4/Day | Sea College',
        'تأجير قناع غوص شرم الشيخ | 4€ لليوم | سي كوليدج',
        4,
        'Tempered-glass silicone scuba mask rental at Sea College Dive Club in Sharm El Sheikh costs €4 per day (or is included inside the €25/day Full Equipment Set, all PADI courses, intro dives and boat snorkeling trips).',
        'تبلغ تكلفة تأجير قناع الغوص السيليكون ذي الزجاج المقوى في سي كوليدج دايف كلوب بشرم الشيخ 4€ لليوم (وهو مشمول مجانًا ضمن الطقم الكامل 25€ وفي جميع دورات PADI ورحلات السنوركل).',
        'A leak-free skirt seal is essential for relaxed Red Sea wall and wreck diving. We carry multiple low-volume and wide-vision silicone frame profiles to fit narrow, medium and broad faces.',
        'Test the suction fit dry at our Al Montazah wet room before boarding the boat, with anti-fog prep ready on deck.',
        'يضمن القناع الملائم للوجه غوصًا مريحًا بدون تسريب مياه؛ نوفر تشكيلة واسعة من أقنعة السيليكون الناعمة ذات الزجاج الحراري المقوى لتناسب مختلف أشكال الوجوه.',
        'يمكنك تجربة ملاءمة القناع لوجهك في غرفة المعدات بالمركز قبل التحرك للقارب.',
        'Tempered-glass scuba mask with soft silicone skirt & snorkel',
        'قناع غوص بزجاج مقوى وحواف سيليكون مريحة',
        'Open to all divers & beach snorkelers',
        'متاح لجميع الغواصين وممارسي السنوركل',
        ['eq-fins', 'eq-boots', 'eq-full', 'snork-beach-day'],
        ['Are masks sanitized between guests?', 'Yes — every mask and snorkel mouthpiece is washed in marine-grade disinfectant solution and rinsed in fresh water after use.', 'هل يتم تعقيم الأقنعة بعد كل استخدام؟', 'نعم، تُغسل جميع الأقنعة بمحلول تعقيم طبي مخصص لمعدات الغوص وتُشطف بالمياه العذبة بعد كل استخدام.']
    ),

    'eq-fins' => $buildRental(
        'Scuba & Snorkeling Fins Rental in Sharm El Sheikh (€4/Day)',
        'تأجير زعانف غوص وسنوركل (Fins) في شرم الشيخ (4€ لليوم)',
        'Scuba Fins Rental Sharm El Sheikh | €4/Day | Sea College',
        'تأجير زعانف غوص شرم الشيخ | 4€ لليوم | سي كوليدج',
        4,
        'Scuba and snorkeling fins rental at Sea College Dive Club in Sharm El Sheikh costs €4 per day across full-foot and open-heel blade sizes (EU 34–47). Included automatically inside our €25/day Full Equipment Set and all PADI courses.',
        'تبلغ تكلفة تأجير زعانف الغوص والسنوركل في سي كوليدج دايف كلوب بشرم الشيخ 4€ لليوم بجميع المقاسات الأوروبية (34 إلى 47)، وهي مشمولة تلقائيًا في الطقم الكامل (25€) ودورات PADI.',
        'Fins are the longest, most awkward item to squeeze into cabin luggage — renting responsive Red Sea propulsion blades for €4/day saves suitcase space immediately.',
        'Also popular as an optional €4 upgrade on our €55 Ras Mohammed Boat Snorkeling trip (where mask and life vest come included) for guests wanting effortless glide in mild currents.',
        'تُعد الزعانف أكثر قطعة تشغل مساحة في حقيبة السفر؛ استئجارها بـ4€ لليوم يوفر مساحة كبيرة في أمتعتك.',
        'كما يفضلها ضيوف رحلات السنوركل باليخت (55€) كإضافة اختيارية للسباحة السلسة فوق الشعاب المرجانية.',
        'Full-foot or open-heel scuba fins (EU 34–47)',
        'زعانف غوص مغلقة أو مفتوحة الكعب بمقاسات EU 34–47',
        'Pair open-heel fins with Neoprene Boots (€5/day) on shore dives',
        'يمكن تنسيق الزعانف المفتوحة مع حذاء الغوص النيوبرين (5€) للغطسات الشاطئية',
        ['eq-boots', 'eq-mask', 'eq-full', 'snork-ras-adult'],
        ['Should I rent full-foot fins or open-heel fins with boots?', 'On boat dives, comfortable full-foot fins (€4/day) work great barefoot; if you are doing shore entries or Dahab trips over rocky ledges, pair open-heel fins (€4) with 5mm Neoprene Boots (€5).', 'هل أختار زعانف مغلقة أم مفتوحة مع حذاء؟', 'في رحلات اليخت تناسب الزعانف المغلقة (4€) القدم مباشرة، أما في الغطسات الشاطئية ورحلة دهب فننصح بالزعانف المفتوحة مع حذاء نيوبرين (5€).']
    ),

    'eq-bcd' => $buildRental(
        'BCD (Buoyancy Compensator) Scuba Rental in Sharm El Sheikh (€10/Day)',
        'تأجير سترة التحكم بالطفو (BCD) في شرم الشيخ (10€ لليوم)',
        'Scuba BCD Rental Sharm El Sheikh | €10/Day | Sea College',
        'تأجير سترة غوص BCD شرم الشيخ | 10€ لليوم | سي كوليدج',
        10,
        'Renting a serviced scuba BCD (Buoyancy Control Device) at Sea College Dive Club in Sharm El Sheikh costs €10 per day in sizes XS to XXL (or €25/day as part of our complete scuba set).',
        'تبلغ تكلفة تأجير سترة التحكم بالطفو (BCD) المفحوصة فنيًا في سي كوليدج دايف كلوب بشرم الشيخ 10€ لليوم بجميع المقاسات من XS إلى XXL (أو 25€ ضمن الطقم الكامل).',
        'Many divers pack their own mask, dive computer and regulator in hand luggage while leaving heavy BCDs and wetsuits at home — renting a reliable jacket-style BCD on site for €10/day keeps travel effortless.',
        'Every BCD undergoes regular power-inflator, dump-valve and bladder leak checks in our on-site workshop.',
        'يفضل كثير من الغواصين اصطحاب المنظم والكمبيوتر في حقيبة اليد وترك سترة الـ BCD الثقيلة في المنزل واستئجارها بـ10€ فقط يوميًا.',
        'تخضع جميع سترات الطفو لفحص دوري لصمامات النفخ والتفريغ في ورشة الصيانة الخاصة بالمركز.',
        'Jacket-style BCD (XS–XXL) with low-pressure inflator hose & dump valves',
        'سترة طفو BCD بمقاسات XS–XXL مع خرطوم النفخ وصمامات الأمان',
        'Certified diver card required',
        'يلزم إبراز بطاقة غوص معتمدة',
        ['eq-reg', 'eq-wetsuit', 'eq-full', 'eq-sm-full'],
        ['Do your BCDs fit standard DIN/INT regulators?', 'Yes — standard low-pressure inflator quick-disconnect nipples are fitted, and our cylinders accommodate both INT (yoke) and DIN first stages.', 'هل تتوافق سترات الـ BCD مع منظمي الشخصي؟', 'نعم، تعمل بوصلة النفخ القياسية العالمية، كما تدعم أسطواناتنا منظمات INT وDIN على حد سواء.']
    ),

    'eq-reg' => $buildRental(
        'Scuba Regulator Set Rental in Sharm El Sheikh (€13/Day — Serviced On Site)',
        'تأجير منظم تنفس كامل للغوص (Regulator) في شرم الشيخ (13€ لليوم)',
        'Scuba Regulator Rental Sharm El Sheikh | €13/Day | Sea College',
        'تأجير منظم غوص Regulator شرم الشيخ | 13€ لليوم | سي كوليدج',
        13,
        'Scuba Regulator rental at Sea College Dive Club in Sharm El Sheikh costs €13 per day and includes a workshop-serviced first stage, primary second stage, high-visibility yellow alternate air source (octopus), submersible pressure gauge (SPG) and BCD inflator hose.',
        'تبلغ تكلفة تأجير منظم التنفس الكامل (Regulator) في سي كوليدج دايف كلوب بشرم الشيخ 13€ لليوم، ويشمل المرحلة الأولى والمرحلة الثانية الأساسية والمنظم الاحتياطي (Octopus) وعداد ضغط الهواء (SPG) وخرطوم الـ BCD.',
        'Sea College Dive Club operates its own regulator service room at Cyrene Sharm — so every rental regulator breathes smoothly at 30 metres on Shark & Yolanda Reef or the SS Thistlegorm.',
        'Mouthpieces are thoroughly disinfected after every rental cycle.',
        'يضم مركز سي كوليدج ورشة صيانة متخصصة للمنظمات، مما يضمن تدفق هواء سلسًا وموثوقًا حتى على عمق 30 مترًا في جدران رأس محمد وحطام ثيستلجورم.',
        'يتم تعقيم قطع الفم السيليكونية بعناية فائقة بعد كل استخدام.',
        '1st stage + 2nd stage + yellow octopus + bar SPG + LP inflator hose',
        'مرحلة أولى وثانية + أخطبوط احتياطي + عداد ضغط بالبار + خرطوم نفخ',
        'Certified diver card required',
        'بطاقة غوص معتمدة',
        ['eq-bcd', 'eq-computer', 'eq-full', 'eq-12nx'],
        ['Are your rental regulators compatible with Enriched Air Nitrox 32%?', 'Yes — standard recreational Nitrox mixes (EAN32) used at Sea College Dive Club work seamlessly with our serviced rental regulator sets.', 'هل منظمات الإيجار متوافقة مع غاز النيتروكس 32%؟', 'نعم، تتوافق منظماتنا المصانة بالكامل مع خلطات الهواء المخصب الترفيهية (نيتروكس 32%).']
    ),

    'eq-wetsuit' => $buildRental(
        'Scuba Wetsuit Rental in Sharm El Sheikh (€10/Day — 5mm Full & Shorty)',
        'تأجير بدلة غوص نيوبرين (Wetsuit) في شرم الشيخ (10€ لليوم)',
        'Wetsuit Rental Sharm El Sheikh | €10/Day (5mm & Shorty) | Sea College',
        'تأجير بدلة غوص Wetsuit شرم الشيخ | 10€ لليوم | سي كوليدج',
        10,
        'Neoprene scuba wetsuit rental at Sea College Dive Club in Sharm El Sheikh costs €10 per day across men\'s, women\'s and junior cuts (3mm shorties for 28–29°C summer water and 5mm full suits for 22–24°C winter/spring diving).',
        'تبلغ تكلفة تأجير بدلة الغوص النيوبرين في سي كوليدج دايف كلوب بشرم الشيخ 10€ لليوم لمقاسات الرجال والسيدات والأطفال (بدلات قصيرة للصيف الدافئ 28–29°م وبدلات كاملة 5 مم للشتاء والربيع 22–24°م).',
        'Damp neoprene is heavy to fly home with — renting a fresh, sun-dried suit at our Al Montazah center keeps your suitcase dry and odour-free on departure day.',
        'Even in August, a 3mm/5mm suit protects against sunburn and accidental jellyfish/hydroid brush while conserving core warmth across repetitive 50-minute boat dives.',
        'تُعد بدلة الغوص المبللة أثقل قطعة عند العودة بالطائرة؛ استئجار بدلة نظيفة وجافة في المركز بـ10€ يوفر عليك عناء التجفيف والوزن الزائد.',
        'تحافظ البدلة على حرارة الجسم خلال الغطسات المتتالية وتحميك من أشعة الشمس القوية.',
        '3mm shorty or 5mm full-length Red Sea neoprene wetsuit (XS–3XL)',
        'بدلة غوص قصيرة 3 مم أو طويلة 5 مم بجميع المقاسات',
        'Open to divers & snorkelers wanting thermal comfort',
        'متاحة للغواصين وممارسي السنوركل (خصوصًا شتاءً)',
        ['eq-boots', 'eq-bcd', 'eq-full', 'snork-beach-night'],
        ['What water temperature should I expect in Sharm El Sheikh?', 'Red Sea water in Sharm El Sheikh ranges from ~21–23°C in January–March (5mm full suit ideal) up to 27–29°C in July–October (shorty or 5mm full suit for 3-dive days).', 'كم تبلغ درجة حرارة المياه في شرم الشيخ؟', 'تتراوح حرارة البحر الأحمر في شرم الشيخ بين 21–23°م شتاءً (يناسبها 5 مم كاملة) وتصل إلى 27–29°م صيفًا وخريفًا.']
    ),

    'eq-boots' => $buildRental(
        'Neoprene Dive Boots Rental in Sharm El Sheikh (€5/Day)',
        'تأجير حذاء غوص نيوبرين (Dive Boots) في شرم الشيخ (5€ لليوم)',
        'Neoprene Dive Boots Rental Sharm El Sheikh | €5/Day | Sea College',
        'تأجير حذاء غوص نيوبرين شرم الشيخ | 5€ لليوم | سي كوليدج',
        5,
        'Neoprene scuba dive boots rent for €5 per day at Sea College Dive Club in Sharm El Sheikh — ideal for rocky shore entries (such as Dahab\'s Canyon & Blue Hole), winter warmth and divers wearing open-heel strap fins.',
        'تبلغ تكلفة تأجير حذاء الغوص النيوبرين ذي النعل المطاطي في سي كوليدج دايف كلوب بشرم الشيخ 5€ لليوم، وهو مثالي للغطسات الشاطئية ورحلة دهب وللغواصين المستخدمين للزعانف المفتوحة.',
        'Sturdy vulcanized rubber soles protect your feet on hot dive decks, shore walkways and Dahab fossil-coral entries while preventing heel blisters inside strap fins.',
        'Available across adult and youth European shoe sizes.',
        'يوفر النعل المطاطي المقوى حماية ممتازة للقدمين على الأسطح الصخرية في دهب والشاطئ ويمنع الاحتكاك داخل الزعانف المفتوحة.',
        'متوفر بمختلف المقاسات الأوروبية ويُعقم ويُجفف يوميًا.',
        'Zippered 5mm neoprene boots with non-slip rubber sole',
        'حذاء نيوبرين 5 مم بسحاب جانبي ونعل مانع للانزلاق',
        'Pair with open-heel fins (€4/day or your own strap fins)',
        'يُنسق مع الزعانف مفتوحة الكعب',
        ['eq-fins', 'eq-wetsuit', 'sup-dahab', 'dive-1'],
        ['Are boots included in the €25 Full Equipment Set?', 'Our €25 Full Equipment Set uses comfortable full-foot boat fins by default; if you prefer open-heel fins + zippered boots (especially for Dahab), boots are a €5/day add-on.', 'هل الحذاء مشمول في الطقم الكامل بـ25€؟', 'يعتمد الطقم الكامل (25€) افتراضيًا على زعانف القارب المريحة المغلقة، ويمكن إضافة حذاء النيوبرين بـ5€ لليوم عند الرغبة أو لرحلة دهب.']
    ),

    'eq-computer' => $buildRental(
        'Dive Computer Rental in Sharm El Sheikh (€10/Day — Air & Nitrox Ready)',
        'تأجير كمبيوتر غوص (Dive Computer) في شرم الشيخ (10€ لليوم — هواء ونيتروكس)',
        'Dive Computer Rental Sharm El Sheikh | €10/Day Air & Nitrox | Sea College',
        'تأجير كمبيوتر غوص شرم الشيخ | 10€ لليوم يدعم النيتروكس | سي كوليدج',
        10,
        'Wrist Dive Computer rental at Sea College Dive Club in Sharm El Sheikh costs €10 per day. Supporting both standard Air (21%) and Enriched Air Nitrox (22–40%), personal dive computers are required for Advanced, Deep, Wreck (SS Thistlegorm), Nitrox and multi-day repetitive boat diving.',
        'تبلغ تكلفة تأجير كمبيوتر الغوص المعصمي في سي كوليدج دايف كلوب بشرم الشيخ 10€ لليوم. يدعم الجهاز كلًا من الهواء العادي والنيتروكس (22–40%)، ويُعد ضروريًا للغوص المتكرر متعدد الأيام وغطسات الأعماق وحطام ثيستلجورم.',
        'Tracking your exact personal tissue nitrogen loading, ascent-rate alarms and 3-minute safety stop countdown keeps every Red Sea wall dive safe and compliant with CDWS rules.',
        'If you are diving Nitrox (€8/12L tank), our technician helps verify oxygen analysis and set your computer\'s FO2% and 1.4 bar PO2 MOD alarm right before departure.',
        'يمنحك كمبيوتر الغوص الشخصي متابعة لحظية لحدود اللاتخفيف وسرعة الصعود الآمنة والعد التنازلي لوقفة السلامة.',
        'وعند الغوص بالنيتروكس نساعدك على تحليل نسبة الأكسجين وضبط إعدادات الكمبيوتر بدقة قبل التحرك.',
        'Wrist-mount Air / Nitrox decompression-tracking dive computer',
        'كمبيوتر غوص معصمي رقمي يدعم الهواء والنيتروكس ومنبهات الصعود',
        'Mandatory per diver on Deep, Wreck, Nitrox & Zodiac blue-water dives',
        'ضروري لكل غواص في غطسات الأعماق والحطام والنيتروكس والزودياك',
        ['eq-12nx', 'eq-full', 'sup-thistlegorm', 'deep'],
        ['Can my buddy and I share one dive computer?', 'No — every diver follows a slightly different depth path, so safety regulations require each diver to wear their own personal dive computer rather than sharing.', 'هل يمكن مشاركة كمبيوتر غوص واحد بين زميلين؟', 'لا، تقتضي معايير السلامة ارتداء كل غواص كمبيوتر غوص مستقلًا به لمتابعة ملف عمقه الشخصي بدقة.']
    ),

    'eq-torch' => $buildRental(
        'Underwater Dive Torch / Light Rental in Sharm El Sheikh (€6/Day)',
        'تأجير كشاف غوص تحت الماء (Underwater Torch) في شرم الشيخ (6€ لليوم)',
        'Underwater Dive Torch Rental Sharm El Sheikh | €6/Day LED | Sea College',
        'تأجير كشاف غوص تحت الماء شرم الشيخ | 6€ لليوم | سي كوليدج',
        6,
        'Rechargeable high-output LED Underwater Torch rental at Sea College Dive Club in Sharm El Sheikh costs €6 per day — essential for Night Dives (+€30), the PADI Night Diver course (€230), SS Thistlegorm cargo holds and illuminating true red/orange colours under overhangs.',
        'تبلغ تكلفة تأجير كشاف الغوص القوي بتقنية LED القابل للشحن في سي كوليدج دايف كلوب بشرم الشيخ 6€ لليوم — وهو أساسي للغطسات الليلية (+30€) وعنابر حطام ثيستلجورم وإظهار الألوان الحقيقية للمرجان في الكهوف والظلال.',
        'Water absorbs red and orange wavelengths rapidly below 10 metres; sweeping a crisp LED beam across Ras Umm Sid gorgonian fans or inside SS Dunraven reveals fiery reds and purples invisible to the naked eye.',
        'Supplied fully charged with wrist lanyard.',
        'يمتص الماء اللونين الأحمر والبرتقالي سريعًا مع العمق؛ لذا يكشف كشاف الـ LED القوي الألوان النارية الساحرة للشعاب الرخوة وعنابر السفن الغارقة.',
        'يُسلم الكشاف مشحونًا بالكامل ومزودًا برباط معصم آمن.',
        'High-lumen rechargeable waterproof LED dive light with wrist strap',
        'كشاف غوص LED عالي الإضاءة مقاوم للضغط مع رباط معصم',
        'Mandatory on Night Dives & recommended on SS Thistlegorm / Dunraven / Canyon',
        'إلزامي في الغطسات الليلية وموصى به بشدة لحطام السفن والكانيون',
        ['sup-night', 'sup-thistlegorm', 'night', 'wreck'],
        ['Is the battery fully charged before my night or Thistlegorm dive?', 'Yes — all rental torches are charged overnight at the dive center and tested before dispatch.', 'هل يُسلم الكشاف مشحونًا بالكامل قبل الرحلة؟', 'نعم، يتم شحن وفحص جميع الكشافات في المركز قبل تسليمها للغواصين.']
    ),

    'eq-15air' => $buildRental(
        '15L Scuba Air Tank Upgrade in Sharm El Sheikh (€6/Day — 100 cu. ft.)',
        'ترقية أسطوانة غوص كبيرة 15 لتر هواء في شرم الشيخ (6€ لليوم)',
        '15L Air Scuba Tank Upgrade Sharm El Sheikh | €6/Day | Sea College',
        'ترقية أسطوانة غوص 15 لتر هواء شرم الشيخ | 6€ لليوم | سي كوليدج',
        6,
        'Upgrading from the standard included 12L cylinder to a 15L Air Tank (100 cu. ft.) at Sea College Dive Club in Sharm El Sheikh costs €6 per day — giving heavier-breathing divers 25% more breathing gas across all dives that day.',
        'تبلغ تكلفة الترقية من أسطوانة الـ12 لتر المشمولة إلى أسطوانة كبيرة 15 لتر هواء في سي كوليدج دايف كلوب بشرم الشيخ 6€ لليوم الواحد — مما يمنحك هواءً إضافيًا بنسبة 25% لغطسات أطول وأكثر راحة.',
        'Nothing cuts a spectacular Shark & Yolanda Reef or Jackson Reef drift short faster than hitting 50 bar ten minutes before the rest of the group. A 15L cylinder gives tall divers or higher-SAC-rate divers relaxed, unhurried 50–60 minute bottom times.',
        'Fitted with dual DIN/INT convertible valves.',
        'إذا كان استهلاكك للهواء مرتفعًا وترغب في إتمام غطسة 50–60 دقيقة كاملة مع المجموعة دون قلق، فإن الترقية لأسطوانة 15 لتر بـ6€ فقط لليوم هي الحل المثالي.',
        'مزودة بصمامات متوافقة مع كل من أنظمة INT وDIN.',
        '15-litre (~100 cu. ft.) cylinder filled to ~200 bar clean breathing air',
        'أسطوانة سعة 15 لترًا معبأة بهواء نقي (~200 بار) بصمام DIN/INT',
        'Certified diver on any shore or boat package',
        'متاحة لجميع الغواصين المعتمدين',
        ['eq-15nx', 'eq-12nx', 'dive-6', 'dive-10'],
        ['Does €6 cover one 15L tank or all tanks I use that day?', '€6/day covers upgrading your cylinders to 15L across your standard diving day!', 'هل الـ6€ لأسطوانة واحدة أم لليوم؟', 'يُحتسب السعر (6€) لليوم الواحد وفق جدول الأسعار الرسمي للمركز.']
    ),

    'eq-15nx' => $buildRental(
        '15L Enriched Air Nitrox Tank Upgrade in Sharm El Sheikh (€10/Day)',
        'أسطوانة 15 لتر نيتروكس (Enriched Air Nitrox) في شرم الشيخ (10€ لليوم)',
        '15L Nitrox Tank Rental Sharm El Sheikh | €10/Day | Sea College',
        'أسطوانة 15 لتر نيتروكس شرم الشيخ | 10€ لليوم | سي كوليدج',
        10,
        'A 15L Enriched Air Nitrox cylinder upgrade at Sea College Dive Club in Sharm El Sheikh costs €10 per day for certified Enriched Air (Nitrox) divers — pairing 25% extra gas volume with extended no-decompression limits!',
        'تبلغ تكلفة الترقية إلى أسطوانة 15 لتر هواء مخصب (نيتروكس) في سي كوليدج دايف كلوب بشرم الشيخ 10€ لليوم للغواصين الحاصلين على شهادة Nitrox — لتجمع بين سعة غاز أكبر بـ25% وحدود لاتخفيف أطول.',
        'On 12L Nitrox dives, heavy breathers sometimes run low on gas before reaching their extended Nitrox computer NDL; combining 15L volume with EAN32 makes SS Thistlegorm and multi-day Ras Mohammed trips effortless.',
        'Calibrated O2 analyzers and fill logs are provided so every diver personally verifies and signs their blend before boarding.',
        'الجمع بين سعة الـ15 لتر الكبيرة وخلطة النيتروكس يمنحك أقصى استفادة في غطسات حطام ثيستلجورم والرحلات متعددة الأيام دون نفاد الهواء مبكرًا.',
        'يتوفر جهاز تحليل الأكسجين المعاير ليقوم كل غواص بفحص وتوقيع نسبة الأكسجين بنفسه قبل كل غطسة.',
        '15-litre Enriched Air Nitrox cylinder (typically EAN32) + O2 analyzer access',
        'أسطوانة 15 لتر نيتروكس (عادة 32%) مع جهاز فحص الأكسجين',
        'Valid Enriched Air / Nitrox certification required (or take our €120 Nitrox course!)',
        'يلزم شهادة Nitrox معتمدة (أو الالتحاق بدورة النيتروكس لدينا بـ120€)',
        ['eq-12nx', 'nitrox', 'sup-thistlegorm', 'eq-computer'],
        ['What if I am not Nitrox certified yet?', 'You can complete our dry, half-day PADI Enriched Air Nitrox Specialty course right here at Sea College Dive Club for €120 (+€85 PADI eLearning/cert fee) — even without dedicating an extra training dive!', 'ماذا لو لم أكن حاصلًا على شهادة النيتروكس بعد؟', 'يمكنك الحصول على شهادة تخصص PADI Enriched Air Nitrox لدينا في نصف يوم فقط بسعر 120€ (+85€ رسوم الشهادة) والاستمتاع بالنيتروكس طوال باقي إجازتك!']
    ),

    'eq-12nx' => $buildRental(
        '12L Enriched Air Nitrox Tank Fill in Sharm El Sheikh (€8/Day)',
        'تعبئة أسطوانة 12 لتر نيتروكس (Nitrox EAN32) في شرم الشيخ (8€ لليوم)',
        '12L Nitrox Fill Sharm El Sheikh | €8/Day EAN32 | Sea College',
        'أسطوانة 12 لتر نيتروكس شرم الشيخ | 8€ لليوم | سي كوليدج',
        8,
        'Enriched Air Nitrox (12L cylinder) at Sea College Dive Club in Sharm El Sheikh costs €8 per day for certified Nitrox divers. Blending on-site at our PADI 5 Star gas station (standard EAN32, MOD 33.7 m at 1.4 bar PO2) gives longer no-decompression bottom times and shorter surface-interval fatigue on repetitive Red Sea dives.',
        'تبلغ تكلفة أسطوانة 12 لتر نيتروكس (هواء مخصب بالأكسجين) في سي كوليدج دايف كلوب بشرم الشيخ 8€ لليوم للغواصين المعتمدين في النيتروكس. يتم التعبئة في محطة الغازات الخاصة بالمركز (عادة EAN32 بحد عمق تشغيلي ~33.7م) لزيادة أوقات القاع الآمنة وتقليل الإجهاد.',
        'Whether you are spending 5 consecutive days on a 10-Dive Package (€350) or penetrating Cargo Holds 1 & 2 on the SS Thistlegorm at 22–26 metres, EAN32 adds valuable safety margin.',
        'Analyze your cylinder personally with our calibrated oxygen analyzer and set your Nitrox dive computer before departure.',
        'سواء كنت تغوص في باقة 6 أو 10 غطسات متتالية أو تستكشف عنابر حطام ثيستلجورم على عمق 22–26 مترًا، يمنحك نيتروكس 32% هامش أمان إضافيًا ووقتًا أطول بدون تخفيف ضغط.',
        'يقوم كل غواص بتحليل نسبة الأكسجين وتدوين أقصى عمق تشغيلي (MOD) في سجل الغازات الرسمي قبل الإبحار.',
        '12L aluminum Enriched Air cylinder (EAN32 standard) + O2 analysis',
        'أسطوانة ألومنيوم 12 لتر نيتروكس مع تحليل الأكسجين المعاير',
        'Enriched Air / Nitrox certification + Nitrox-capable dive computer',
        'شهادة غواص نيتروكس + كمبيوتر غوص يدعم النيتروكس',
        ['nitrox', 'eq-15nx', 'eq-computer', 'sup-thistlegorm'],
        ['What oxygen percentage do you blend for recreational Nitrox?', 'Standard recreational fills are EAN32 (32% oxygen, maximum operating depth ~33.7 m at 1.4 bar PO2); custom blends, Trimix and CCR gases are also supported at our on-site gas blending facility.', 'ما نسبة الأكسجين المعتادة في أسطوانات النيتروكس؟', 'النسبة القياسية هي EAN32 (32% أكسجين بحد عمق أقصى ~33.7 متر عند ضغط جزئي 1.4 بار)، كما نوفر خلطات خاصة وترايمكس في محطة الغازات بالمركز.']
    ),

    'eq-sm-tank' => $buildRental(
        '12L Sidemount Air Cylinder Rental in Sharm El Sheikh (€5/Day)',
        'تأجير أسطوانة 12 لتر جانبية (Sidemount Tank) في شرم الشيخ (5€ لليوم)',
        '12L Sidemount Scuba Tank Sharm El Sheikh | €5/Day | Sea College',
        'أسطوانة 12 لتر للغوص الجانبي Sidemount شرم الشيخ | 5€ لليوم',
        5,
        'Renting an extra/rigged 12L Aluminum Sidemount Air Cylinder at Sea College Dive Club in Sharm El Sheikh costs €5 per day — ideal for certified Sidemount divers bringing their own harness and twin regulators who need matched aluminum S80 cylinders with left/right valves and rigging bands.',
        'تبلغ تكلفة تأجير أسطوانة ألومنيوم 12 لتر مجهزة للغوص الجانبي (Sidemount) في سي كوليدج دايف كلوب بشرم الشيخ 5€ لليوم — مثالية لحاملي شهادة Sidemount الذين يحضرون الهارنس والمنظمات الخاصة بهم.',
        'Aluminum 12L (S80) cylinders have neutral-to-slightly-positive buoyancy characteristics as gas is breathed down, making them the global gold standard for clean horizontal sidemount trim.',
        'Pair with our €50/day Full Sidemount Equipment kit if you want to leave your wing and twin regs at home.',
        'تتميز أسطوانات الألومنيوم 12 لتر بخصائص طفو مثالية للغوص الجانبي تمنحك وضعية أفقية انسيابية (Trim) دون إجهاد للظهر.',
        'متوافقة مع أحزمة التثبيت ومشابك Bolt Snaps القياسية.',
        '12L aluminum S80 cylinder configured for sidemount diving',
        'أسطوانة ألومنيوم 12 لتر مهيأة لنظام التعليق الجانبي',
        'Sidemount Diver certification (or enroll in PADI Sidemount Specialty €290)',
        'شهادة غوص جانبي Sidemount معتمدة',
        ['eq-sm-full', 'sidemount', 'eq-12nx', 'sup-thistlegorm'],
        ['Why do Sidemount divers love diving with Sea College in Sharm?', 'Unlike standard holiday dive shops that only carry backmount gear, Sea College Dive Club has sidemount-rigged tanks, full sidemount rental rigs, technical gases (Trimix/Nitrox) and PADI Sidemount Instructors on staff.', 'لماذا يفضل غواصو الـ Sidemount مركز سي كوليدج في شرم الشيخ؟', 'لأننا نوفر تجهيزات الغوص الجانبي الكاملة والأسطوانات المهيأة والغازات التقنية ومدربي تخصص PADI Sidemount في نفس المركز.']
    ),

    'eq-sm-full' => $buildRental(
        'Full Sidemount Scuba Equipment Rental in Sharm El Sheikh (€50/Day)',
        'تأجير طقم معدات الغوص الجانبي الكامل (Sidemount) في شرم الشيخ (50€ لليوم)',
        'Full Sidemount Gear Rental Sharm El Sheikh | €50/Day | Sea College',
        'تأجير طقم غوص جانبي Sidemount كامل شرم الشيخ | 50€ لليوم',
        50,
        'Full Sidemount Equipment rental at Sea College Dive Club in Sharm El Sheikh costs €50 per day for certified Sidemount divers — including a dedicated sidemount harness/bladder wing, matched left-and-right long/short hose regulator sets with SPGs, bungees and cylinder rigging.',
        'تبلغ تكلفة تأجير طقم معدات الغوص الجانبي الكامل (Full Sidemount Equipment) في سي كوليدج دايف كلوب بشرم الشيخ 50€ لليوم للغواصين المعتمدين، وتشمل هارنس وجناح الطفو الجانبي ومنظمين مزدوجين (خرطوم طويل وقصير مع عدادات الضغط) وأحزمة ومشابك تعليق الأسطوانات.',
        'Travelling with twin regulator sets, lead trim pouches and a sidemount wing eats up half your luggage allowance; our €50/day complete sidemount rig lets you fly light and still dive twin-cylinder redundancy on Sharm\'s walls and wrecks.',
        'Spend 20 minutes in our on-site pool or shallow house reef with our tech/sidemount team to dial in bungee tension and weight placement before boarding the boat.',
        'اصطحاب منظمين مزدوجين وهارنس جانبي يستهلك وزنًا كبيرًا في الطيران؛ يتيح لك طقم الـ Sidemount المتكامل لدينا (50€ لليوم) السفر بخفة والاستمتاع بأمان الأسطوانتين المزدوجتين في جدران وحطام شرم الشيخ.',
        'نوفر إمكانية ضبط التوازن والأحزمة في حمام السباحة التدريبي بالمركز قبل الانطلاق لرحلات القارب.',
        'Sidemount wing/harness + twin regulator sets (long hose/necklace) + tank rigging kit',
        'هارنس وجناح Sidemount + منظمان كاملان بخرطوم طويل + طقم تعليق الأسطوانات',
        'Certified Sidemount Diver (note: gear is FREE inside our €290 PADI Sidemount course!)',
        'شهادة Sidemount معتمدة (علمًا أن المعدات مشمولة مجانًا ضمن دورة Sidemount بـ290€)',
        ['sidemount', 'eq-sm-tank', 'sup-thistlegorm', 'sup-dahab'],
        ['Is Sidemount gear included if I take the PADI Sidemount Course (€290)?', 'Yes! All equipment is included in Sea College Dive Club course prices — this €50/day rental applies only to already-certified Sidemount divers joining fun-dive packages.', 'هل هذا الإيجار مطلوب لطلاب دورة PADI Sidemount (290€)؟', 'لا، جميع المعدات مشمولة مجانًا في أسعار دورات PADI لدينا؛ ينطبق إيجار الـ50€ فقط على الغواصين المعتمدين مسبقًا في باقات الغوص الترفيهي.']
    ),

    /* ==================== SHORT-TERM DIVING INSURANCE ==================== */
    'ins-3' => $buildInsurance(
        3,
        'Tailored for weekend breaks, 1- to 3-day certified dive packages (`2 Dives` up to `6 Dives`) or short PADI Specialty courses in Sharm El Sheikh.',
        'مصمم لعطلات نهاية الأسبوع وباقات الغوص القصيرة من يوم إلى 3 أيام (من غطستين حتى 6 غطسات) أو دورات التخصص السريعة.'
    ),
    'ins-7' => $buildInsurance(
        7,
        'Our most popular short-term policy — covers a full 1-week Sharm El Sheikh diving holiday (such as an 8- or 10-Dive Package or Open Water + fun dives).',
        'الوثيقة الأكثر طلبًا لدينا — تغطي أسبوع إجازة كاملًا في شرم الشيخ (مثل باقة 8 أو 10 غطسات أو دورة Open Water متبوعة بغطسات ترفيهية).'
    ),
    'ins-10' => $buildInsurance(
        10,
        'Built for 10-night Red Sea holidays combining multi-day Ras Mohammed, Tiran, SS Thistlegorm and Dahab excursions.',
        'مناسبة لإجازات الـ10 ليالٍ في شرم الشيخ التي تجمع بين باقات الغوص المتعددة ورحلات ثيستلجورم ودهب.'
    ),
    'ins-14' => $buildInsurance(
        14,
        'Covers a full two-week Red Sea stay or back-to-back PADI courses plus 10+ fun dives in Sharm El Sheikh.',
        'تغطي إقامة أسبوعين كاملين في البحر الأحمر للغواصين المكثفين أو الجمع بين عدة دورات وباقات غوص.'
    ),
];
