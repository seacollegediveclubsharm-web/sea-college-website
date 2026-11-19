<?php
/**
 * Rich SEO & AEO content for 24 Dive Trips & Packages:
 * - 4 Beginner Intro Dives (dsd-quick-reef, dsd-2-reef, dsd-quick-boat, dsd-2-boat)
 * - 2 Refresh Dives (check-dive, scuba-review)
 * - 11 Certified Dive Packages (dive-1 .. dive-10, dive-extra)
 * - 7 Wreck, Zodiac, Night & Dahab Supplements
 */
declare(strict_types=1);

$buildCertifiedPkg = static function (
    int $dives,
    int $price,
    string $daysEn,
    string $daysAr,
    string $planEn,
    string $planAr,
    string $sitesEn,
    string $sitesAr,
    array $related,
    int $revIdx
): array {
    $perDive = (int) round($price / $dives);
    $saveVsSingle = ($dives * 45) - $price;
    return [
        'h1' => [
            'en' => "{$dives} Dives Package in Sharm El Sheikh — Guided Red Sea Boat or Shore Diving",
            'ar' => "باقة {$dives} غطسات في شرم الشيخ — غوص بالقارب أو من الشاطئ للمعتمدين",
        ],
        'meta_title' => [
            'en' => "{$dives} Dives Package Sharm El Sheikh | €{$price} (€{$perDive}/Dive) | Sea College",
            'ar' => "باقة {$dives} غطسات في شرم الشيخ | {$price}€ ({$perDive}€ للغطسة) | سي كوليدج",
        ],
        'meta_desc' => [
            'en' => "Book a personal {$dives}-dive certified scuba package in Sharm El Sheikh for €{$price} (€{$perDive}/dive). Includes PADI guide, 12L air tanks, weights & hotel transfers.",
            'ar' => "احجز باقة {$dives} غطسات للغواصين المعتمدين في شرم الشيخ بسعر {$price}€ ({$perDive}€ للغطسة). شاملة مرشد PADI وأسطوانات 12 لتر والأوزان وانتقالات الفندق.",
        ],
        'aeo_answer' => [
            'en' => "The {$dives} Dives by Boat or Shore package at Sea College Dive Club in Sharm El Sheikh costs €{$price} per certified diver (€{$perDive} per dive — saving €{$saveVsSingle} compared with single shore dives) and typically runs across {$daysEn}. It includes a PADI dive guide, 12L aluminum air cylinders, weight belt and weights, return Sharm El Sheikh hotel transfers, and drinks/lunch on boat days (subject to the €10/day Boat Supplement and €5/day Marine Park Fee).",
            'ar' => "تبلغ تكلفة باقة {$dives} غطسات بالقارب أو من الشاطئ في سي كوليدج دايف كلوب بشرم الشيخ {$price}€ للغواص المعتمد ({$perDive}€ للغطسة الواحدة — بتوفير {$saveVsSingle}€ مقارنة بالغطسات المفردة) وتُنفذ عادةً خلال {$daysAr}. تشمل الباقة مرشد غوص PADI، أسطوانات هواء 12 لتر، حزام وأوزان، والانتقالات من وإلى الفندق في شرم الشيخ (تُضاف في أيام القارب 10€ رسوم قارب و5€ رسوم محمية يوميًا).",
        ],
        'overview' => [
            'en' => [
                $planEn,
                $sitesEn,
            ],
            'ar' => [
                $planAr,
                $sitesAr,
            ],
        ],
        'specs' => [
            ['Package Price', 'سعر الباقة', "€{$price} per person (€{$perDive} per dive)", "{$price}€ للفرد ({$perDive}€ للغطسة)"],
            ['Typical Schedule', 'الجدول المعتاد', $daysEn . ' (flexible boat or shore mix)', $daysAr . ' (مزيج مرن بين القارب والشاطئ)'],
            ['Minimum Level', 'المستوى المطلوب', 'Certified diver (Open Water or higher; Scuba Diver with private guide)', 'غواص معتمد (Open Water فأعلى)'],
            ['Included Gear', 'التجهيزات المشمولة', '12L air cylinders, weight belt & weights', 'أسطوانات هواء 12 لتر وحزام وأوزان'],
            ['Transfers', 'الانتقالات', 'Free return hotel pickup & drop-off in Sharm El Sheikh', 'انتقالات مجانية ذهابًا وإيابًا من فنادق شرم الشيخ'],
            ['Boat Day Fees', 'رسوم أيام القارب', '€10/day Boat Supplement (incl. lunch & drinks) + €5/day Marine Park Fee', '10€ يوميًا رسوم قارب (شاملة الغداء والمشروبات) + 5€ رسوم محمية'],
            ['Personal Policy', 'سياسة الباقة', 'Personal package per diver — cannot be split between divers', 'باقة شخصية لكل غواص ولا تُقسم بين أكثر من شخص'],
        ],
        'steps' => [
            ['Check-In & Certification Verification', 'تسجيل الوصول ومراجعة الشهادة', 'Hotel pickup brings you to our PADI 5 Star base at Cyrene Sharm (Al Montazah) to check your certification card, logbook and gear fit.', 'توصيل من الفندق إلى مركزنا المعتمد في سيرين شرم بالمنتزه لمراجعة بطاقة الغوص وسجل الغطسات وتجهيز المعدات.'],
            ['Morning Boat Departure or House Reef Entry', 'الانطلاق بالقارب أو النزول من الشاطئ', 'Board our spacious Red Sea dive boat (~08:30) for two morning/midday reef dives, or step directly off our Al Montazah house reef.', 'الصعود إلى يخت الغوص المجهز صباحًا لغطستين في مواقع الشعاب، أو النزول المباشر على الشعاب المنزلية بالمنتزه.'],
            ['Surface Interval & Fresh Boat Lunch', 'استراحة السطح والغداء الطازج', 'Relax on the shaded sun deck during a generous surface interval with freshly cooked buffet lunch, hot tea, coffee and cold soft drinks on full-day boats.', 'الاسترخاء على متن اليخت خلال استراحة السطح مع غداء بوفيه ساخن ومشروبات على رحلات اليوم الكامل.'],
            ['Afternoon Dive, Logbook Signing & Drop-Off', 'الغطسة التالية وتوقيع السجل والعودة', 'Complete your afternoon reef or drift dive, log sites and marine sightings with your PADI guide, and return to your hotel around 16:30.', 'إتمام الغطسة التالية وتوثيق الأعماق والمشاهدات البحرية في سجل الغوص مع مرشدك ثم العودة للفندق عصرًا.'],
        ],
        'included' => [
            'en' => [
                "{$dives} guided open water dives (boat or shore)",
                'Certified PADI Dive Guide in small ability-matched groups',
                '12L aluminum air cylinders, weight belt & lead weights',
                'Return air-conditioned hotel transfers in Sharm El Sheikh',
                'Drinks on half-day boats; freshly cooked buffet lunch & drinks on full-day boats (with daily boat supplement)',
            ],
            'ar' => [
                "{$dives} غطسات مفتوحة مع مرشد (بالقارب أو من الشاطئ)",
                'مرشد غوص PADI في مجموعات صغيرة متقاربة المستوى',
                'أسطوانات هواء ألومنيوم 12 لتر وحزام وأوزان',
                'انتقالات مكيفة ذهابًا وإيابًا من فنادق شرم الشيخ',
                'مشروبات في رحلات نصف اليوم وغداء بوفيه ومشروبات في رحلات اليوم الكامل (مع رسوم القارب اليومية)',
            ],
        ],
        'excluded' => [
            'en' => [
                'Boat Trip Supplement: €10 per person/day on boat diving days',
                'Marine Park Fee: €5 per person/day (all zones except House Reef)',
                'Full scuba equipment rental (€25/day) or individual pieces if you do not bring your own gear',
                'Enriched Air Nitrox fills (€8/12L or €10/15L), wreck/night/Zodiac supplements & diving insurance',
            ],
            'ar' => [
                'رسوم القارب اليومية: 10€ للفرد في كل يوم غوص بالقارب',
                'رسوم المحمية البحرية: 5€ للفرد يوميًا (لجميع المناطق عدا الشعاب المنزلية)',
                'تأجير طقم المعدات الكامل (25€ لليوم) لمن لا يحضر معداته الخاصة',
                'تعبئة النيتروكس (8€ لـ12 لتر أو 10€ لـ15 لتر) وإضافات الحطام والغوص الليلي والتأمين',
            ],
        ],
        'ideal_for' => [
            'en' => [
                'Certified Open Water, Advanced, Rescue & Master Scuba Divers holidaying in Sharm El Sheikh',
                'Divers inactive >6 months can add a €25 Check Dive (>12 months: €50 Scuba Review) on day one',
                'PADI Scuba Divers (12 m max) and Junior Divers aged 10–11 (require Private Guide at €75/day)',
            ],
            'ar' => [
                'حاملو شهادات Open Water وAdvanced وما فوقها خلال عطلتهم في شرم الشيخ',
                'المنقطعون لأكثر من 6 أشهر يمكنهم بدء اليوم الأول بـ Check Dive (25€) أو Scuba Review (50€ بعد سنة)',
                'الغواصون الصغار (10–11 سنة) وحاملو Scuba Diver بشرط إضافة مرشد خاص (75€ لليوم)',
            ],
        ],
        'schema_type' => 'TouristTrip',
        'compare_mode' => 'packages',
        'review_idx' => $revIdx,
        'related' => $related,
        'faqs' => [
            [
                "Can two divers share a {$dives}-dive package?",
                "No. All Sea College Dive Club packages are personal per diver and cannot be split between two people; each certified diver selects the package matching their own dive count.",
                "هل يمكن لغواصين اثنين تقاسم باقة الـ {$dives} غطسات؟",
                "لا، جميع باقات الغوص شخصية لكل غواص ولا يمكن تقاسمها بين شخصين؛ يختار كل غواص الباقة المناسبة لعدد غطساته.",
            ],
            [
                "How much are daily boat and park fees on a {$dives}-dive package?",
                "Each day you dive from the boat adds a €10 Boat Trip Supplement (which includes freshly prepared buffet lunch and drinks on full-day trips) plus a €5 Marine Park Fee. House reef shore dives have no boat or park fee.",
                "كم تبلغ رسوم القارب والمحمية اليومية مع باقة {$dives} غطسات؟",
                "يُضاف في كل يوم غوص بالقارب 10€ رسوم قارب (تشمل الغداء والمشروبات في رحلات اليوم الكامل) + 5€ رسوم محمية بحرية، بينما لا تُطبق هذه الرسوم على غطسات الشعاب المنزلية.",
            ],
            [
                "Can I add SS Thistlegorm, Night Dive or Dahab onto this {$dives}-dive package?",
                "Yes! Special trips use 1 or 2 dives from your package plus the trip supplement — for example Night Dive (+€30), SS Dunraven (+€30), Zodiac (+€50), Dahab (+€115) or SS Thistlegorm (+€150, min. Advanced + 20 dives).",
                "هل يمكنني دمج غطسة ثيستلجورم أو غطسة ليلية أو دهب مع باقة {$dives} غطسات؟",
                "نعم! تُحتسب الغطسات الخاصة من رصيد باقتك مع دفع فرق الإضافة فقط مثل الغطسة الليلية (+30€) أو دنرافين (+30€) أو دهب (+115€) أو ثيستلجورم (+150€ لحاملي Advanced مع 20 غطسة).",
            ],
            [
                "Do I have to dive consecutive days?",
                "No — you can space your {$dives} dives across your Sharm El Sheikh stay with rest or sightseeing days in between, as long as you leave a minimum 18–24 hour surface interval before flying.",
                "هل يجب تنفيذ الغطسات في أيام متتالية؟",
                "لا يشترط التتالي؛ يمكنك توزيع غطساتك الـ {$dives} بمرونة خلال إقامتك في شرم الشيخ مع مراعاة ترك 18–24 ساعة قبل رحلة الطيران.",
            ],
        ],
    ];
};

return [
    /* ==================== INTRO DIVES ==================== */
    'dsd-quick-reef' => [
        'h1' => [
            'en' => 'Quick Discover Scuba Diving — House Reef Try Dive in Sharm El Sheikh (€30)',
            'ar' => 'غطسة تجريبية سريعة للمبتدئين على الشعاب المنزلية في شرم الشيخ (30€)',
        ],
        'meta_title' => [
            'en' => 'Quick Try Scuba Dive Sharm El Sheikh | €30 House Reef | Sea College',
            'ar' => 'غطسة تجريبية سريعة في شرم الشيخ | 30€ الشعاب المنزلية | سي كوليدج',
        ],
        'meta_desc' => [
            'en' => 'Never dived before? Try scuba on our calm Al Montazah house reef in Sharm El Sheikh for €30. Safety briefing, full scuba gear & 1-on-1 PADI Instructor supervision.',
            'ar' => 'جرّب الغوص لأول مرة في شرم الشيخ بسعر 30€ على الشعاب المنزلية الهادئة بالمنتزه. شاملة الشرح والمعدات الكاملة وإشراف مدرب PADI.',
        ],
        'aeo_answer' => [
            'en' => 'Quick Discover Scuba Diving — House Reef at Sea College Dive Club costs €30 per person and takes about 2 hours at our beachfront PADI 5 Star resort in Al Montazah, Sharm El Sheikh. Designed for complete beginners aged 10 and up with zero prior experience, it includes a full safety briefing, complete scuba equipment rental, shallow-water breathing practice and 1 supervised 10-minute open water reef dive.',
            'ar' => 'تبلغ تكلفة الغطسة التجريبية السريعة على الشعاب المنزلية في سي كوليدج دايف كلوب 30€ للفرد وتستغرق نحو ساعتين في منتجعنا المعتمد PADI خمس نجوم بالمنتزه في شرم الشيخ. صُممت للمبتدئين تمامًا من سن 10 سنوات بدون أي خبرة سابقة، وتشمل شرح السلامة والمعدات الكاملة وتدريب التنفس في المياه الضحلة وغطسة شاطئية لمدة 10 دقائق برفقة مدرب.',
        ],
        'overview' => [
            'en' => [
                'Curious what breathing underwater feels like without committing to a full day at sea? Our €30 Quick House Reef Try Dive lets you step into calm, clear Red Sea water right in front of our dive center at Cyrene Sharm, Al Montazah.',
                'Your PADI Instructor fits all scuba gear for you, teaches easy ear-equalization and hand signals at standing depth, and guides you side-by-side past colourful Red Sea reef fish.',
            ],
            'ar' => [
                'إذا كنت ترغب في تجربة إحساس التنفس تحت الماء لأول مرة بتكلفة اقتصادية ووقت قصير، فإن الغطسة التجريبية السريعة (30€) على شعابنا المنزلية الهادئة في المنتزه هي البداية المثالية.',
                'يقوم مدرب PADI بتركيب وضبط جميع المعدات لك، وتدريبك على التنفس ومعادلة الأذن وإشارات اليد في مياه ضحلة قبل مرافقتك يدًا بيد لمشاهدة الأسماك المرجانية الملونة.',
            ],
        ],
        'specs' => [
            ['Price', 'السعر', '€30 per person (all scuba gear included)', '30€ للفرد (شاملة جميع المعدات)'],
            ['Total Duration', 'المدة الإجمالية', '~2 hours (briefing, gearing up & 10-min underwater dive)', 'حوالي ساعتين (شاملة الشرح والتجهيز و10 دقائق تحت الماء)'],
            ['Minimum Age', 'الحد الأدنى للعمر', '10 years old (ages 8–9 can join Bubblemaker in pool)', '10 سنوات (وسن 8–9 متاح في حمام السباحة Bubblemaker)'],
            ['Experience Needed', 'الخبرة المطلوبة', 'None — complete beginners welcome', 'لا يلزم أي خبرة سابقة'],
            ['Location', 'الموقع', 'Protected House Reef at Cyrene Sharm, Al Montazah', 'الشعاب المنزلية المحمية في سيرين شرم، المنتزه'],
            ['Max Depth', 'أقصى عمق', 'Up to 5–6 metres under direct instructor hold/supervision', 'حتى 5–6 أمتار بإشراف مباشر من المدرب'],
        ],
        'steps' => [
            ['Arrival & Medical Check', 'الوصول والإقرار الطبي', 'Meet your PADI Instructor at our beachfront center, complete the quick PADI participant check and get fitted into a wetsuit, BCD, mask and fins.', 'لقاء مدربك في المركز واستكمال استمارة السلامة السريعة واختيار مقاسات البدلة والقناع والزعانف.'],
            ['15-Minute Safety & Briefing Session', 'شرح قواعد السلامة والإشارات', 'Learn how the regulator works, how to equalize your ears gently and the key underwater hand signals.', 'تعلّم طريقة التنفس الهادئ من المنظم ومعادلة ضغط الأذن وأهم إشارات اليد تحت الماء.'],
            ['Shallow Water Breathing Comfort', 'التعود على التنفس في المياه الضحلة', 'Take your first breaths at chest depth until you feel 100% relaxed before moving toward the coral edge.', 'أخذ أول أنفاسك على عمق ضحل حتى تشعر بالاطمئنان التام قبل التحرك نحو الشعاب المرجانية.'],
            ['10-Minute Guided Coral Reef Dive', 'غطسة مرجانية لمدة 10 دقائق مع المدرب', 'Glide beside vibrant coral pinnacles, clownfish and butterflyfish while your instructor manages buoyancy and safety.', 'التحرك بهدوء بمحاذاة الشعاب المرجانية والأسماك الملونة بينما يتحكم المدرب في الطفو والأمان.'],
        ],
        'included' => [
            'en' => ['Full scuba equipment set (mask, fins, wetsuit, BCD, regulator, tank & weights)', 'Pre-dive safety & skills briefing', 'Direct supervision by a certified PADI Instructor', '1 open water house reef intro dive (10 minutes underwater)'],
            'ar' => ['طقم معدات الغوص الكامل (قناع، زعانف، بدلة، BCD، منظم، أسطوانة وأوزان)', 'شرح كامل لقواعد السلامة والمهارات الأساسية', 'مرافقة مباشرة من مدرب PADI معتمد', 'غطسة شاطئية مفتوحة لمدة 10 دقائق على الشعاب المنزلية'],
        ],
        'excluded' => [
            'en' => ['PADI Discover Scuba eCard registration (choose the €85 PADI Discover Scuba Diving course if you want official PADI registration & pool session)', 'Underwater photo/video package (optional on site)', 'Towels & swimwear'],
            'ar' => ['تسجيل شهادة PADI التجريبية (متاح ضمن برنامج PADI Discover Scuba Diving بـ85€)', 'التصوير الفوتوغرافي والفيديو تحت الماء (اختياري بالمركز)', 'المناشف وملابس السباحة الشخصية'],
        ],
        'ideal_for' => [
            'en' => ['First-time visitors wanting a low-cost €30 taster before booking a boat trip or Open Water course', 'Nervous beginners who prefer walking in gradually from shore rather than jumping off a yacht', 'Resort guests with just 2 hours free in their morning or afternoon schedule'],
            'ar' => ['الزوار الراغبون في تجربة الغوص لأول مرة بسعر اقتصادي 30€ قبل حجز رحلة قارب أو دورة كاملة', 'المبتدئون الذين يفضلون الدخول التدريجي من الشاطئ بدلًا من القفز من اليخت', 'الضيوف الذين لديهم ساعتان فقط ويرغبون في نشاط بحري مميز'],
        ],
        'schema_type' => 'TouristTrip',
        'compare_mode' => 'intro',
        'review_idx' => 3,
        'related' => ['dsd-2-reef', 'dsd-quick-boat', 'dsd-2-boat', 'dsd'],
        'faqs' => [
            ['Do I need to know how to swim for the €30 House Reef Try Dive?', 'Basic comfort in water helps, though strong swimming skills are not required because your PADI Instructor controls your buoyancy and stays right beside you from standing depth.', 'هل يجب أن أجيد السباحة لخوض الغطسة التجريبية الشاطئية؟', 'يكفي الارتياح للماء دون اشتراط مهارات سباحة متقدمة، لأن مدرب PADI يتحكم في الطفو ويرافقك جنبًا إلى جنب من المياه الضحلة.'],
            ['What is the difference between the €30 Quick Reef Dive and €75 2-Dive Reef Intro?', 'The €30 option includes 1 ten-minute underwater reef dive, whereas the €75 option gives you 2 separate fifteen-minute shore dives (triple the underwater bottom time) so your second dive feels completely natural.', 'ما الفرق بين غطسة الـ30€ وباقة الغطستين بـ75€ على الشعاب المنزلية؟', 'تشمل باقة الـ30€ غطسة واحدة لمدة 10 دقائق، بينما تمنحك باقة الـ75€ غطستين شاطئيتين مدة كل منهما 15 دقيقة (ثلاثة أضعاف الوقت تحت الماء).'],
            ['How long must I wait before flying after an intro dive?', 'PADI and DAN medical guidelines recommend waiting at least 12 hours before flying after a single no-decompression intro dive (and 18 hours after multiple dives).', 'كم ساعة يجب الانتظار قبل ركوب الطائرة بعد الغطسة التجريبية؟', 'توصي إرشادات PADI وDAN الطبية بالانتظار 12 ساعة على الأقل بعد غطسة واحدة (و18 ساعة بعد غطستين أو أكثر) قبل السفر بالطائرة.'],
        ],
    ],

    'dsd-2-reef' => [
        'h1' => [
            'en' => 'Discover Scuba Diving (2 Intro Dives) — House Reef in Sharm El Sheikh (€75)',
            'ar' => 'تجربة الغوص للمبتدئين (غطستان تجريبيتان) — الشعاب المنزلية في شرم الشيخ (75€)',
        ],
        'meta_title' => [
            'en' => '2 Intro Shore Dives Sharm El Sheikh | €75 Gear Included | Sea College',
            'ar' => 'غطستان تجريبيتان من الشاطئ في شرم الشيخ | 75€ شاملة المعدات',
        ],
        'meta_desc' => [
            'en' => 'Enjoy 2 guided 15-minute beginner shore dives on our Al Montazah house reef in Sharm El Sheikh (€75). Briefing, full scuba gear & PADI Instructor included.',
            'ar' => 'استمتع بغطستين تجريبيتين من الشاطئ (15 دقيقة لكل غطسة) على الشعاب المنزلية بالمنتزه في شرم الشيخ (75€) شاملة الشرح والمعدات ومدرب PADI.',
        ],
        'aeo_answer' => [
            'en' => 'Discover Scuba Diving (2 Intro Dives) — House Reef at Sea College Dive Club costs €75 per person and lasts roughly 3 hours at our Al Montazah beachfront center in Sharm El Sheikh. Built for complete beginners aged 10+, it includes a safety briefing, all scuba gear and two 15-minute guided coral reef dives separated by a relaxing surface break.',
            'ar' => 'تبلغ تكلفة تجربة الغوص (غطستان تجريبيتان) على الشعاب المنزلية مع سي كوليدج دايف كلوب 75€ للفرد وتستغرق نحو 3 ساعات في مركزنا الشاطئي بالمنتزه في شرم الشيخ. تشمل التجربة للمبتدئين من سن 10 سنوات شرح السلامة والمعدات الكاملة وغطستين موجهتين (15 دقيقة لكل غطسة) بينهما استراحة مريحة.',
        ],
        'overview' => [
            'en' => [
                'Almost every beginner says their second dive is twice as enjoyable as their first: once the initial sensation of breathing underwater clicks on Dive 1, Dive 2 lets you slow down and truly take in the Red Sea coral gardens.',
                'Conducted directly off our sheltered Al Montazah house reef with zero boat motion, this half-day experience gives you 30 total minutes of underwater exploration with full equipment included.',
            ],
            'ar' => [
                'يتفق معظم المبتدئين على أن الغطسة الثانية تكون أمتع بمراحل من الأولى؛ فبعد التعود على التنفس تحت الماء في الغطسة الأولى، تمنحك الغطسة الثانية ثقة وهدوءًا للاستمتاع الكامل بجمال الشعاب المرجانية.',
                'تُقام التجربة مباشرة من شاطئنا المحمي في المنتزه بدون دوار بحر، وتمنحك 30 دقيقة إجمالية تحت الماء مع شمول جميع المعدات.',
            ],
        ],
        'specs' => [
            ['Price', 'السعر', '€75 per person (all equipment included)', '75€ للفرد (شاملة جميع المعدات)'],
            ['Duration', 'المدة', '~3 hours (half-day shore session)', 'حوالي 3 ساعات (نصف يوم من الشاطئ)'],
            ['Dives Included', 'عدد الغطسات', '2 shore intro dives (15 minutes each)', 'غطستان شاطئيتان (15 دقيقة لكل غطسة)'],
            ['Minimum Age', 'العمر الأدنى', '10 years old', '10 سنوات'],
            ['Max Depth', 'أقصى عمق', 'Up to 6–12 metres with PADI Instructor', 'من 6 إلى 12 مترًا برفقة مدرب PADI'],
        ],
        'steps' => [
            ['Gear Fitting & Safety Briefing', 'ضبط المعدات وشرح السلامة', 'Suit up at our dive center and learn breathing, ear clearing, mask clearing and hand signals.', 'ارتداء المعدات الملائمة وتعلّم قواعد التنفس ومعادلة الأذن وإشارات التواصل.'],
            ['First 15-Minute House Reef Dive', 'الغطسة الأولى (15 دقيقة)', 'Enter gently from shore to build confidence and explore shallow coral heads.', 'النزول التدريجي من الشاطئ لاكتساب الثقة ومشاهدة الشعاب القريبة.'],
            ['Surface Interval & Coaching Tips', 'استراحة السطح ونصائح المدرب', 'Relax with water or tea while your instructor shares posture and finning pointers.', 'استراحة قصيرة على الشاطئ مع نصائح من المدرب لمزيد من الانسيابية.'],
            ['Second 15-Minute Reef Exploration', 'الغطسة الثانية (15 دقيقة)', 'Venture along a fresh coral route spotting moray eels, lionfish and vibrant anthias shoals.', 'جولة ثانية على مسار مرجاني مختلف لمشاهدة أسماك الأسد والسمك المرجاني الملون.'],
        ],
        'included' => [
            'en' => ['Complete scuba equipment rental', 'Instructor briefing & shallow skills practice', '2 guided 15-minute open water shore dives', 'Surface interval refreshments at the dive center'],
            'ar' => ['استئجار طقم معدات الغوص الكامل', 'شرح نظري وتدريب مهارات في المياه الضحلة', 'غطستان شاطئيتان موجهتان (15 دقيقة لكل غطسة)', 'استراحة مريحة بين الغطستين في المركز'],
        ],
        'excluded' => [
            'en' => ['Boat trip & lunch (choose Discover Scuba 2 Dives by Boat at €100 for a full yacht day)', 'Optional underwater photos & videos'],
            'ar' => ['رحلة اليخت والغداء (اختر باقة الغطستين بالقارب بـ100€ ليوم كامل في البحر)', 'التصوير تحت الماء (اختياري)'],
        ],
        'ideal_for' => [
            'en' => ['Beginners wanting proper underwater time without spending a full 8-hour day on a boat', 'People prone to seasickness who prefer calm shore entries', 'Guests testing scuba before starting the 3-day PADI Open Water course (€400)'],
            'ar' => ['المبتدئون الراغبون في وقت أطول تحت الماء خلال نصف يوم فقط', 'من يفضلون الغوص من الشاطئ لتجنب دوار البحر', 'الراغبون في تجربة حقيقية قبل التسجيل في دورة Open Water الكاملة (400€)'],
        ],
        'schema_type' => 'TouristTrip',
        'compare_mode' => 'intro',
        'review_idx' => 1,
        'related' => ['dsd-quick-reef', 'dsd-2-boat', 'dsd', 'owd'],
        'faqs' => [
            ['Should I choose 2 Shore Dives (€75) or 2 Boat Dives (€100)?', 'If you only have a half day or prefer walking in from the beach, choose 2 Shore Dives (€75). If you want a full 8-hour Red Sea yacht cruise with hotel transfers, marine park reefs, buffet lunch and drinks included, the €100 2-Dive Boat package offers extraordinary value.', 'أيهما أفضل: غطستان من الشاطئ (75€) أم غطستان بالقارب (100€)؟', 'إذا كان وقتك نصف يوم أو تفضل الشاطئ فاختر باقة الـ75€. أما إذا أردت يومًا بحريًا كاملًا باليخت شاملًا الانتقالات ورسوم المحمية والغداء والمشروبات فباقة الـ100€ بالقارب هي الأفضل قيمة.'],
            ['Can children aged 10 join with their parents?', 'Yes! Parents and kids aged 10+ can complete the briefing and shore dives together with our PADI Instructor team.', 'هل يمكن للأطفال بعمر 10 سنوات المشاركة مع والديهم؟', 'نعم بالتأكيد، يمكن للوالدين والأبناء من سن 10 سنوات خوض الشرح والغطستين معًا بإشراف مدربي PADI.'],
            ['How long should I leave before my flight home?', 'Because this package includes two dives, wait at least 18 hours after surfacing before flying.', 'كم ساعة يجب تركها قبل رحلة الطيران؟', 'لأن الباقة تتضمن غطستين، يُنصح بترك 18 ساعة على الأقل بعد الخروج من الماء قبل الطيران.'],
        ],
    ],

    'dsd-quick-boat' => [
        'h1' => [
            'en' => 'Quick Discover Scuba Diving by Boat — Sharm El Sheikh Yacht Try Dive (€70)',
            'ar' => 'غطسة تجريبية سريعة بالقارب في شرم الشيخ — يوم كامل باليخت (70€)',
        ],
        'meta_title' => [
            'en' => 'Try Scuba Diving by Boat Sharm El Sheikh | €70 All-Inclusive | Sea College',
            'ar' => 'غطسة تجريبية بالقارب في شرم الشيخ | 70€ شاملة الغداء والانتقالات',
        ],
        'meta_desc' => [
            'en' => 'Full-day Red Sea yacht cruise + 1 beginner intro scuba dive in Sharm El Sheikh for €70. Includes hotel transfers, park fees, buffet lunch, drinks & scuba gear.',
            'ar' => 'رحلة يخت ليوم كامل مع غطسة تجريبية للمبتدئين في شرم الشيخ بـ70€ شاملة الانتقالات ورسوم المحمية وغداء البوفيه والمشروبات والمعدات الكاملة.',
        ],
        'aeo_answer' => [
            'en' => 'Quick Discover Scuba Diving — By Boat at Sea College Dive Club costs €70 per person for a full ~8-hour Red Sea yacht day from Sharm El Sheikh. No prior diving experience is needed (min. age 10): the €70 fare is all-inclusive of hotel pickup and drop-off, Marine Park fees, full scuba equipment, PADI Instructor briefing, 1 guided 10-minute boat reef dive, snorkeling time, freshly cooked buffet lunch and soft drinks.',
            'ar' => 'تبلغ تكلفة الغطسة التجريبية السريعة بالقارب مع سي كوليدج دايف كلوب 70€ للفرد ليوم كامل (~8 ساعات) على متن يخت في شرم الشيخ دون الحاجة لأي خبرة سابقة (من سن 10 سنوات). السعر شامل بالكامل: انتقالات الفندق، رسوم المحمية، معدات الغوص الكاملة، شرح مدرب PADI، غطسة مرجانية لمدة 10 دقائق، وقت للسنوركل، وغداء بوفيه ومشروبات.',
        ],
        'overview' => [
            'en' => [
                'Combine a classic Red Sea motor-yacht excursion with your very first scuba dive! For just €15 more than a standard snorkeling boat ticket, you get all scuba equipment and a personal 1-on-1 or tiny-group guided dive onto pristine offshore coral gardens.',
                'Between dive stops you can snorkel, sunbathe on the upper deck and enjoy freshly cooked Egyptian buffet dishes with non-diving friends or family on the same boat.',
            ],
            'ar' => [
                'اجمع بين متعة رحلة اليخت البحرية الكاملة وتجربة أول غطسة بالمعدات تحت الماء! مقابل فرق بسيط عن تذكرة السنوركل تحصل على طقم غوص كامل وغطسة مرافقة مع مدرب PADI في أجمل الشعاب البحرية.',
                'في باقي وقفات الرحلة يمكنك ممارسة السنوركل والاستجمام على السطح المشمس وتناول الغداء مع عائلتك أو أصدقائك على نفس اليخت.',
            ],
        ],
        'specs' => [
            ['Price', 'السعر', '€70 per person (all-inclusive boat day)', '70€ للفرد (يوم كامل شامل كافة الرسوم)'],
            ['Duration', 'المدة', 'Full day (~8 hours, ~08:00–16:30)', 'يوم كامل (حوالي 8 ساعات من 08:00 إلى 16:30)'],
            ['Scuba Dive', 'الغطسة المشمولة', 'Briefing + 1 guided boat dive (10 min underwater) + snorkeling stops', 'شرح + غطسة واحدة بالقارب (10 دقائق) + وقفات سنوركل'],
            ['Inclusions', 'يشمل السعر', 'Equipment, hotel transfers, park fee, lunch & soft drinks', 'المعدات وانتقالات الفندق ورسوم المحمية والغداء والمشروبات'],
            ['Minimum Age', 'العمر الأدنى', '10 years old', '10 سنوات'],
        ],
        'steps' => [
            ['Hotel Pickup & Yacht Boarding', 'الاستقبال من الفندق والصعود لليخت', 'Morning transfer from your Sharm El Sheikh hotel to the marina and welcome briefing aboard our dive boat.', 'انتقال صباحي مكيف من فندقك إلى المارينا والترحيب بك على متن يخت الغوص.'],
            ['On-Deck Scuba Briefing & Gear Setup', 'شرح الغوص وتجهيز المعدات على اليخت', 'Your PADI Instructor explains breathing, ear equalization and signals while the crew prepare your scuba unit on the swim platform.', 'يشرح المدرب أساسيات التنفس ومعادلة الأذن بينما يجهز الطاقم معداتك على منصة النزول.'],
            ['10-Minute Guided Coral Reef Dive', 'غطسة مرجانية موجهة لمدة 10 دقائق', 'Step into crystal-clear turquoise water at a sheltered mooring and explore Red Sea corals hand-in-hand with your instructor.', 'النزول في مياه فيروزية هادئة واستكشاف الشعاب المرجانية والأسماك برفقة مدربك.'],
            ['Buffet Lunch, Snorkeling & Return', 'غداء البوفيه والسنوركل والعودة', 'Enjoy hot buffet lunch, cold drinks and afternoon reef snorkeling before returning to your hotel.', 'تناول غداء البوفيه والمشروبات وممارسة السنوركل بعد الظهر قبل العودة للفندق.'],
        ],
        'included' => [
            'en' => ['Return air-conditioned hotel transfers in Sharm El Sheikh', 'Marine Park entry fees', 'Full scuba diving equipment + snorkeling mask/vest', '1 guided 10-minute intro dive with a PADI Instructor', 'Freshly prepared buffet lunch, water, tea, coffee & soft drinks'],
            'ar' => ['انتقالات مكيفة ذهابًا وإيابًا من فنادق شرم الشيخ', 'رسوم دخول المحمية البحرية', 'معدات الغوص الكاملة + قناع وسترة للسنوركل', 'غطسة تجريبية (10 دقائق) بإشراف مدرب PADI', 'غداء بوفيه ومياه ومشروبات غازية وشاي وقهوة'],
        ],
        'excluded' => [
            'en' => ['Second boat dive (upgrade to 2 Boat Intro Dives for €100 total)', 'Towels, sunscreen & optional underwater photography'],
            'ar' => ['غطسة ثانية بالقارب (متاحة ضمن باقة الغطستين بالقارب بسعر 100€ إجمالًا)', 'المناشف الشخصية والتصوير الفوتوغرافي الاختياري'],
        ],
        'ideal_for' => [
            'en' => ['First-timers wanting an all-in-one boat excursion + scuba try dive with zero hidden extras', 'Couples or families mixing snorkelers (€55 adult / €30 child) and first-time divers on the same boat'],
            'ar' => ['المبتدئون الباحثون عن رحلة يخت متكاملة مع تجربة الغوص بدون أي مصاريف إضافية', 'العائلات والأصدقاء حيث يجمعون بين تذاكر السنوركل والغوص التجريبي على نفس القارب'],
        ],
        'schema_type' => 'TouristTrip',
        'compare_mode' => 'intro',
        'review_idx' => 0,
        'related' => ['dsd-2-boat', 'snork-ras-adult', 'dsd-quick-reef', 'owd'],
        'faqs' => [
            ['Are Marine Park fees and hotel transfers really included in €70?', 'Yes! Unlike certified dive packages where boat (€10) and park (€5) supplements are itemized per day, our €70 and €100 Boat Intro Dive tickets already bundle transfers, park fees, equipment, lunch and drinks.', 'هل رسوم المحمية والانتقالات مشمولة فعلًا في سعر الـ70€؟', 'نعم، تذاكر الغوص التجريبي بالقارب (70€ و100€) شاملة مسبقًا للانتقالات ورسوم المحمية والمعدات والغداء والمشروبات دون أي إضافات إلزامية.'],
            ['Can I upgrade to a second dive on the boat if I love the first one?', 'Yes — or pre-book the Discover Scuba Diving 2 Boat Dives package (€100) right away to get two longer 15-minute dives at two different reef sites.', 'هل يمكنني الترقية لغطسة ثانية على القارب؟', 'نعم، وننصح بحجز باقة الغطستين بالقارب (100€) مسبقًا لتحصل على غطستين أطول (15 دقيقة لكل غطسة) في موقعين مختلفين.'],
            ['What do I need to bring on the boat?', 'Bring a photo of your passport on your phone (for marina Coast Guard check), swimwear, a towel and reef-safe sunscreen.', 'ماذا أحضر معي على القارب؟', 'صورة جواز السفر على الهاتف لتصريح المارينا، ملابس السباحة، منشفة وواقي شمس.'],
        ],
    ],

    'dsd-2-boat' => [
        'h1' => [
            'en' => 'Discover Scuba Diving by Boat (2 Intro Dives) — Sharm El Sheikh (€100)',
            'ar' => 'تجربة الغوص بالقارب (غطستان تجريبيتان) في شرم الشيخ — يوم كامل شامل (100€)',
        ],
        'meta_title' => [
            'en' => 'Discover Scuba Diving by Boat Sharm | 2 Intro Dives €100 All-Inclusive',
            'ar' => 'تجربة الغوص بالقارب شرم الشيخ | غطستان تجريبيتان 100€ شاملة الكل',
        ],
        'meta_desc' => [
            'en' => 'Our #1 beginner experience in Sharm El Sheikh: full-day Red Sea yacht cruise + 2 guided 15-min scuba dives (€100). Transfers, park fees, lunch, drinks & gear included.',
            'ar' => 'التجربة رقم 1 للمبتدئين في شرم الشيخ: يخت ليوم كامل + غطستان تجريبيتان (15 دقيقة لكل غطسة) بـ100€ شاملة الانتقالات والمحمية والغداء والمشروبات والمعدات.',
        ],
        'aeo_answer' => [
            'en' => 'Discover Scuba Diving (2 Intro Dives) — By Boat is Sea College Dive Club\'s most booked beginner trip in Sharm El Sheikh at €100 per person all-inclusive. Across an ~8-hour Red Sea yacht day, guests aged 10+ with zero prior experience receive hotel pickup/drop-off, Marine Park fees, full scuba equipment, PADI Instructor coaching, two 15-minute guided coral reef dives at different sites, buffet lunch and soft drinks.',
            'ar' => 'تُعد تجربة الغوص بالقارب (غطستان تجريبيتان) بسعر 100€ للفرد الرحلة الأكثر حجزًا للمبتدئين في سي كوليدج دايف كلوب بشرم الشيخ. تمتد الرحلة لنحو 8 ساعات على متن اليخت للضيوف من سن 10 سنوات بدون أي خبرة سابقة، وتشمل بالكامل انتقالات الفندق، رسوم المحمية، المعدات الكاملة، تدريب مدرب PADI، غطستين موجهتين (15 دقيقة لكل غطسة) في موقعين مرجانيين مختلفين، وغداء بوفيه ومشروبات.',
        ],
        'overview' => [
            'en' => [
                'Why stop at one short dip when you can dive two distinct Red Sea coral gardens on a single yacht trip? Dive 1 introduces you gently to weightless breathing in turquoise lagoon water; after a sunny surface break, Dive 2 lets you cruise effortlessly past clownfish,giant clams, moray eels and dazzling anthias clouds.',
                'Everything is bundled into one transparent €100 price — hotel pickup, park tickets, scuba gear, PADI Instructor guidance, hot lunch and unlimited soft drinks/tea/coffee on board.',
            ],
            'ar' => [
                'لماذا تكتفي بغطسة قصيرة واحدة بينما يمكنك استكشاف موقعين مرجانيين مختلفين في البحر الأحمر في رحلة يخت واحدة؟ تمنحك الغطسة الأولى التعود المريح على انعدام الوزن والتنفس تحت الماء، وفي الغطسة الثانية تنطلق بثقة تامة بين الشعاب الملونة وأسماك المهرج والسمك المرجاني.',
                'كل شيء مشمول في سعر واحد واضح (100€): التوصيل من الفندق، رسوم المحمية، طقم الغوص الكامل، مرافقة مدرب PADI، غداء البوفيه والمشروبات الساخنة والباردة.',
            ],
        ],
        'specs' => [
            ['All-Inclusive Price', 'السعر الشامل', '€100 per person (zero extra boat or park fees)', '100€ للفرد (شامل كافة رسوم القارب والمحمية)'],
            ['Duration', 'المدة', 'Full day (~8 hours, ~08:00–16:30)', 'يوم كامل (حوالي 8 ساعات)'],
            ['Dives Included', 'عدد الغطسات', '2 guided boat reef dives (15 min each) + snorkeling time', 'غطستان موجهتان بالقارب (15 دقيقة لكل غطسة) + وقت سنوركل'],
            ['Minimum Age', 'العمر الأدنى', '10 years old (no experience required)', '10 سنوات (بدون أي خبرة سابقة)'],
            ['Included Extras', 'المشمولات', 'Scuba gear, hotel transfers, park fee, buffet lunch & drinks', 'المعدات الكاملة وانتقالات الفندق والمحمية والغداء والمشروبات'],
        ],
        'steps' => [
            ['Hotel Pickup & Marina Departure', 'التوصيل من الفندق والإبحار', 'Air-conditioned pickup from any Sharm El Sheikh hotel to the marina (~08:00) and departure toward calm coral moorings.', 'توصيل مكيف من فندقك في شرم الشيخ إلى المارينا والإبحار نحو مواقع الشعاب المحمية.'],
            ['Briefing & First 15-Minute Reef Dive', 'الشرح والغطسة الأولى (15 دقيقة)', 'Complete a clear safety briefing on deck, test breathing at the surface ladder and enjoy your first 15-minute coral reef dive.', 'شرح وافٍ لقواعد السلامة على اليخت وتجربة التنفس عند السلم ثم النزول لأول غطسة لمدة 15 دقيقة.'],
            ['Buffet Lunch & Sun-Deck Relaxation', 'غداء البوفيه والاسترخاء على اليخت', 'Savour freshly cooked buffet lunch and cold drinks while cruising to a second sheltered reef lagoon.', 'تناول غداء البوفيه والمشروبات الباردة أثناء الإبحار نحو موقع الشعاب الثاني.'],
            ['Second 15-Minute Reef Dive & Hotel Drop-Off', 'الغطسة الثانية (15 دقيقة) والعودة', 'Dive a brand-new reef topography with newfound confidence, snorkel if time permits, and return to your hotel around 16:30.', 'خوض الغطسة الثانية بثقة واستمتاع مضاعف في موقع جديد ثم العودة المريحة إلى فندقك.'],
        ],
        'included' => [
            'en' => ['Return Sharm El Sheikh hotel transfers', 'Marine Park entry ticket', 'Complete scuba equipment set for both dives', '2 guided 15-minute open water dives with a PADI Instructor', 'Freshly cooked buffet lunch, soft drinks, water, tea & coffee'],
            'ar' => ['انتقالات الفندق ذهابًا وإيابًا في شرم الشيخ', 'تذكرة رسوم المحمية البحرية', 'طقم معدات الغوص الكامل للغطستين', 'غطستان موجهتان (15 دقيقة لكل غطسة) برفقة مدرب PADI', 'غداء بوفيه ساخن ومشروبات غازية ومياه وشاي وقهوة'],
        ],
        'excluded' => [
            'en' => ['Official PADI eCard credit (if you decide to get certified afterwards, enroll in PADI Scuba Diver €250 or Open Water Diver €400)', 'Optional underwater photos/videos & personal towels'],
            'ar' => ['شهادة PADI الدولية (في حال رغبتك بالاعتماد الدولي يمكنك الالتحاق بدورة Open Water بـ400€)', 'التصوير تحت الماء والمناشف الشخصية'],
        ],
        'ideal_for' => [
            'en' => ['First-time divers wanting the complete Red Sea boat day and maximum underwater time', 'Couples, honeymooners & groups celebrating a special day in Sharm El Sheikh', 'Mixed groups where non-divers book the €55 Ras Mohammed/Boat Snorkeling ticket to share the yacht'],
            'ar' => ['المبتدئون الباحثون عن التجربة الكاملة في البحر الأحمر بأفضل قيمة مقابل السعر', 'الأزواج وشهر العسل والأصدقاء الراغبون في يوم بحري لا يُنسى', 'المجموعات المشتركة حيث يمكن لغير الغواصين حجز تذكرة السنوركل (55€) لمرافقتكم'],
        ],
        'schema_type' => 'TouristTrip',
        'compare_mode' => 'intro',
        'review_idx' => 0,
        'related' => ['dsd-quick-boat', 'snork-ras-adult', 'dsd', 'owd'],
        'faqs' => [
            ['Why is the 2-Dive Boat Intro (€100) your most popular beginner package?', 'For €30 more than the 1-dive boat ticket (€70), you get triple the total underwater time (two 15-minute dives instead of one 10-minute dive) at two different Red Sea reef sites, with transfers, park fees, gear and lunch all included.', 'لماذا تُعد باقة الغطستين بالقارب (100€) الأكثر طلبًا للمبتدئين؟', 'مقابل 30€ فقط إضافية عن تذكرة الغطسة الواحدة (70€) تحصل على 3 أضعاف الوقت الفعلي تحت الماء (غطستان × 15 دقيقة بدلًا من 10 دقائق) في موقعين مختلفين مع شمول الانتقالات والغداء والمعدات.'],
            ['Do I need any prior scuba or swimming certificate?', 'No certificate or experience is needed. Participants must be at least 10 years old and answer the standard PADI medical questionnaire.', 'هل أحتاج لأي شهادة غوص أو سباحة سابقة؟', 'لا تحتاج لأي خبرة أو شهادة سابقة؛ يشترط فقط ألا يقل العمر عن 10 سنوات واستيفاء الاستبيان الطبي المعتاد.'],
            ['How far ahead should I book?', 'Online booking is open up to 72 hours ahead; if you are already in Sharm El Sheikh for tomorrow or the next day, tap WhatsApp and our team will check live boat seats immediately.', 'قبل كم يوم يجب الحجز؟', 'الحجز عبر الموقع متاح حتى 72 ساعة قبل الموعد، وإذا كنت متواجدًا بالفعل في شرم الشيخ وترغب بالحجز للغد تواصل معنا عبر واتساب للتحقق الفوري من المقاعد.'],
        ],
    ],

    /* ==================== REFRESH DIVES ==================== */
    'check-dive' => [
        'h1' => [
            'en' => 'Check Dive in Sharm El Sheikh — Refresher for Certified Divers (€25)',
            'ar' => 'غطسة اختبارية تنشيطية (Check Dive) في شرم الشيخ للغواصين المعتمدين (25€)',
        ],
        'meta_title' => [
            'en' => 'Check Dive Sharm El Sheikh | €25 Refresher (>6 Months) | Sea College',
            'ar' => 'غطسة اختبارية Check Dive شرم الشيخ | 25€ بعد انقطاع 6 أشهر',
        ],
        'meta_desc' => [
            'en' => 'Haven\'t dived in 6–12 months? Complete your mandatory €25 Check Dive on Sea College\'s calm house reef/pool in Sharm El Sheikh before boat drift dives.',
            'ar' => 'منقطع عن الغوص من 6 إلى 12 شهرًا؟ أتمم غطستك الاختبارية الإلزامية Check Dive بسعر 25€ على الشعاب المنزلية الهادئة بشرم الشيخ قبل رحلات القارب.',
        ],
        'aeo_answer' => [
            'en' => 'A Check Dive at Sea College Dive Club in Sharm El Sheikh costs €25 per certified diver and takes 1.5 to 2 hours. Under Red Sea safety regulations, it is mandatory if your last logged dive was between 6 and 12 months ago (over 12 months requires a €50 Scuba Review). You fine-tune weighting, mask clearing, regulator recovery and neutral buoyancy on our calm Al Montazah house reef or pool before joining offshore boat dives.',
            'ar' => 'تبلغ تكلفة الغطسة الاختبارية (Check Dive) في سي كوليدج دايف كلوب بشرم الشيخ 25€ للغواص المعتمد وتستغرق من ساعة ونصف إلى ساعتين. وهي إلزامية وفق معايير السلامة في البحر الأحمر عند الانقطاع عن الغوص لمدة بين 6 و12 شهرًا (ولأكثر من سنة يلزم Scuba Review بـ50€)، حيث تضبط خلالها أوزانك ومهارات الطفو الأساسية قبل رحلات القارب.',
        ],
        'overview' => [
            'en' => [
                'Red Sea reefs such as Shark & Yolanda and Jackson Reef often involve blue-water descents and drift entries where accurate weighting—especially with salty Red Sea water and aluminum cylinders—makes all the difference.',
                'Our relaxed €25 Check Dive at Cyrene Sharm lets you dial in your exact lead weight, test equipment and refresh core safety drills in calm, current-free water so your very first boat dive is stress-free.',
            ],
            'ar' => [
                'تتميز مواقع الغوص الشهيرة في شرم الشيخ مثل رأس محمد ومضيق تيران بنزول حر وتيارات تتطلب ضبطًا دقيقًا للأوزان، خاصة مع ملوحة مياه البحر الأحمر العالية وأسطوانات الألومنيوم.',
                'تتيح لك غطسة الـ Check Dive (25€) في مياهنا الهادئة بالمنتزه ضبط الوزن المثالي ومراجعة مهارات الأمان السريعة لتستمتع برحلات القارب اللاحقة بكل ثقة.',
            ],
        ],
        'specs' => [
            ['Price', 'السعر', '€25 per certified diver', '25€ للغواص المعتمد'],
            ['When Mandatory', 'متى تكون إلزامية؟', 'Last logged dive 6 to 12 months ago', 'عندما تكون آخر غطسة مسجلة قبل 6 إلى 12 شهرًا'],
            ['Duration', 'المدة', '1.5–2 hours', '1.5 إلى ساعتين'],
            ['Location', 'الموقع', 'Training pool / sheltered Al Montazah House Reef', 'حمام السباحة التدريبي / الشعاب المنزلية بالمنتزه'],
            ['Included', 'يشمل', '12L air tank, weights & PADI professional supervision', 'أسطوانة هواء 12 لتر والأوزان وإشراف مدرب/مرشد PADI'],
        ],
        'steps' => [
            ['Certification & Logbook Check', 'فحص الشهادة وسجل الغوص', 'Verify certification level, last dive date and estimate Red Sea lead weighting.', 'التحقق من بطاقة الغوص وتاريخ آخر غطسة وتقدير الأوزان المناسبة لملوحة البحر الأحمر.'],
            ['Pre-Dive Buddy & Gear Check', 'مراجعة تركيب المعدات وفحص الزميل', 'Assemble your scuba unit and run a relaxed BWRAF pre-dive safety check.', 'تركيب المعدات ومراجعة خطوات الفحص المسبق للسلامة مع المدرب.'],
            ['Quick Skills & Weight Check', 'مهارات سريعة وضبط الطفو', 'Refresh mask clearing, regulator recovery, alternate air source and end-of-dive weighting hover.', 'تنفيذ إخلاء القناع واسترجاع المنظم ومشاركة الهواء واختبار الطفو المتعادل.'],
            ['Guided House Reef Tour', 'جولة غوص على الشعاب المنزلية', 'Enjoy a scenic house reef dive and receive sign-off for daily Ras Mohammed & Tiran boats.', 'جولة ممتعة على الشعاب المنزلية واعتماد جاهزيتك لرحلات القوارب اليومية.'],
        ],
        'included' => [
            'en' => ['PADI Instructor / Divemaster briefing & skill check', '12L air tank, weight belt & weights', 'Guided confined/house reef check dive'],
            'ar' => ['شرح ومراجعة مهارات مع مشرف PADI معتمد', 'أسطوانة هواء 12 لتر وحزام وأوزان', 'غطسة شاطئية موجهة على الشعاب المنزلية'],
        ],
        'excluded' => [
            'en' => ['Scuba gear rental if you do not have your own (€25/day full set)', 'Scuba Review upgrade (€50 total if inactive >12 months)'],
            'ar' => ['تأجير طقم المعدات لمن لا يحضر معداته (25€ لليوم)', 'برنامج Scuba Review الكامل (50€ في حال الانقطاع لأكثر من سنة)'],
        ],
        'ideal_for' => [
            'en' => ['Certified divers whose last dive was 6–12 months ago', 'Divers testing brand-new personal gear or camera housings before boat trips'],
            'ar' => ['الغواصون المعتمدون المنقطعون عن الغوص لفترة بين 6 و12 شهرًا', 'الغواصون الراغبون في تجربة معدات جديدة وضبط الأوزان قبل النزول من اليخت'],
        ],
        'schema_type' => 'TouristTrip',
        'compare_mode' => 'none',
        'review_idx' => 1,
        'related' => ['scuba-review', 'dive-2', 'dive-6', 'eq-full'],
        'faqs' => [
            ['What if my last dive was more than 1 year ago?', 'If more than 12 months have passed since your last logged dive, book our Scuba Review (€50) instead, which adds full confined-water skill coaching before an open water shore dive.', 'ماذا لو كانت آخر غطسة لي منذ أكثر من سنة؟', 'إذا مر أكثر من 12 شهرًا على آخر غطسة مسجلة، يلزم حجز برنامج Scuba Review (50€) الذي يشمل تدريبًا وافيًا في المياه المحصورة يتبعه غطسة شاطئية مفتوحة.'],
            ['Can I do my Check Dive on the afternoon I arrive in Sharm El Sheikh?', 'Yes! Doing your €25 Check Dive at our Al Montazah house reef on arrival afternoon means you can step straight onto the morning Ras Mohammed or Tiran boat the very next day.', 'هل يمكنني إجراء الـ Check Dive بعد ظهر يوم الوصول؟', 'نعم، إجراء الغطسة الاختبارية عصر يوم وصولك على شعابنا المنزلية يتيح لك الانطلاق مباشرة على قارب الصباح في اليوم التالي.'],
        ],
    ],

    'scuba-review' => [
        'h1' => [
            'en' => 'PADI Scuba Review (ReActivate) Refresher in Sharm El Sheikh (€50)',
            'ar' => 'مراجعة غوص شاملة (Scuba Review) في شرم الشيخ بعد انقطاع سنة فأكثر (50€)',
        ],
        'meta_title' => [
            'en' => 'Scuba Review Refresher Sharm El Sheikh | €50 (>1 Year) | Sea College',
            'ar' => 'مراجعة الغوص Scuba Review شرم الشيخ | 50€ بعد انقطاع أكثر من عام',
        ],
        'meta_desc' => [
            'en' => 'Haven\'t dived in over a year? Refresh your scuba skills safely in Sharm El Sheikh for €50: pool/confined coaching + guided Red Sea house reef dive.',
            'ar' => 'منقطع عن الغوص لأكثر من سنة؟ استعد مهاراتك بأمان في شرم الشيخ بسعر 50€: تدريب مهارات بحمام السباحة + غطسة شاطئية موجهة في البحر الأحمر.',
        ],
        'aeo_answer' => [
            'en' => 'The Scuba Review refresher at Sea College Dive Club in Sharm El Sheikh costs €50 per certified diver and takes a half day. Mandatory when you have not dived for more than 12 months, it pairs a patient PADI Instructor briefing and confined-water pool skill refresh with a full guided open water dive on our sheltered Al Montazah house reef.',
            'ar' => 'تبلغ تكلفة برنامج مراجعة الغوص الشاملة (Scuba Review) في سي كوليدج دايف كلوب بشرم الشيخ 50€ للغواص المعتمد ويستغرق نصف يوم. يُعد البرنامج إلزاميًا عند الانقطاع عن الغوص لأكثر من 12 شهرًا، ويجمع بين شرح نظري وتدريب عملي في حمام السباحة مع غطسة مفتوحة موجهة على الشعاب المنزلية بالمنتزه.',
        ],
        'overview' => [
            'en' => [
                'Even experienced divers forget small muscle-memory habits after a year or several years out of the water. Rather than rushing you off the back of a boat in open currents, our PADI Instructors rebuild your comfort step by step.',
                'First we refresh dive planning, equipment assembly and emergency skills in our shallow on-site training pool; then we walk onto our calm house reef for a relaxed Red Sea dive where you lock in your weighting and trim.',
            ],
            'ar' => [
                'من الطبيعي بعد مرور عام أو عدة أعوام بدون غوص أن يحتاج الغواص لتنشيط الذاكرة العضلية ومهارات الطوارئ بهدوء بعيدًا عن ضغط القوارب والتيارات المفتوحة.',
                'نبدأ في حمام السباحة التدريبي بالمركز بمراجعة تركيب المعدات ومهارات القناع والمنظم والطفو، ثم ننتقل لغطسة مفتوحة ممتعة على الشعاب المنزلية لضبط الأوزان واستعادة الثقة الكاملة.',
            ],
        ],
        'specs' => [
            ['Price', 'السعر', '€50 per certified diver', '50€ للغواص المعتمد'],
            ['When Mandatory', 'متى يكون إلزاميًا؟', 'More than 1 year since last logged dive', 'عند الانقطاع عن الغوص لأكثر من سنة واحدة'],
            ['Duration', 'المدة', 'Half day (~3–3.5 hours)', 'نصف يوم (3 إلى 3.5 ساعات)'],
            ['Format', 'محتوى البرنامج', 'Knowledge refresh + confined pool skills + 1 open water shore dive', 'مراجعة نظرية + مهارات بحمام السباحة + غطسة شاطئية مفتوحة'],
            ['Included', 'يشمل', 'PADI Instructor coaching, 12L air cylinders & weights', 'تدريب مدرب PADI وأسطوانات 12 لتر والأوزان'],
        ],
        'steps' => [
            ['Dive Theory & Safety Refresh', 'مراجعة القواعد النظرية والسلامة', 'Review pressure/equalization, dive computer no-decompression limits, hand signals and Red Sea boat procedures.', 'مراجعة معادلة الضغط وحدود اللاتخفيف بكمبيوتر الغوص وإشارات اليد وإجراءات القوارب.'],
            ['Equipment Setup & Pool Skill Circuit', 'تركيب المعدات وتدريب حمام السباحة', 'Practise mask clearing, regulator recovery, out-of-air air sharing, CESA concepts and neutral-buoyancy hovering in our pool.', 'التدريب العملي على إخلاء القناع واستعادة المنظم ومشاركة الهواء والطفو المتعادل في حمام السباحة.'],
            ['Guided House Reef Open Water Dive', 'غطسة مفتوحة على الشعاب المنزلية', 'Put your refreshed skills to work on a full guided Red Sea coral reef dive with proper safety-stop trim.', 'تطبيق المهارات في غطسة بحرية كاملة على الشعاب المنزلية مع تنفيذ وقفة السلامة بدقة.'],
            ['Logbook Stamp & Boat Clearance', 'ختم سجل الغوص واعتماد الجاهزية', 'Log your refresher dive with our PADI 5 Star stamp so you are cleared for daily boat packages.', 'توثيق الغطسة بختم مركز PADI خمس نجوم لتكون جاهزًا تمامًا لباقات القوارب اليومية.'],
        ],
        'included' => [
            'en' => ['PADI Instructor refresher briefing', 'On-site training pool session', '1 guided open water house reef dive', '12L air tanks, weight belt & weights'],
            'ar' => ['شرح نظري وتوجيه مع مدرب PADI', 'حصة تدريبية في حمام السباحة بالمركز', 'غطسة مفتوحة موجهة على الشعاب المنزلية', 'أسطوانات هواء 12 لتر وحزام وأوزان'],
        ],
        'excluded' => [
            'en' => ['Full equipment rental if needed (€25/day)', 'Optional official PADI ReActivate eLearning & replacement card (€85 if requested)'],
            'ar' => ['تأجير طقم المعدات عند الحاجة (25€ لليوم)', 'رسوم بطاقة PADI ReActivate الاختيارية إن رغب الغواص بإصدار بطاقة جديدة'],
        ],
        'ideal_for' => [
            'en' => ['Certified divers who have not dived for 1, 3, 5 or even 10+ years', 'Holiday divers wanting calm 1-on-1 or small-group pool practice before Ras Mohammed walls'],
            'ar' => ['الغواصون المعتمدون المنقطعون عن الغوص لسنة أو عدة سنوات', 'من يرغب في استعادة الثقة بهدوء في حمام السباحة قبل غطسات الجدران المرجانية العميقة'],
        ],
        'schema_type' => 'TouristTrip',
        'compare_mode' => 'none',
        'review_idx' => 1,
        'related' => ['check-dive', 'dive-4', 'dive-6', 'aowd'],
        'faqs' => [
            ['Does my PADI certification ever expire if I haven\'t dived in 10 years?', 'Your PADI certification never expires! Completing this €50 Scuba Review brings your in-water skills right back up to date without retaking the Open Water course.', 'هل تنتهي صلاحية شهادة PADI إذا لم أغطس منذ 10 سنوات؟', 'شهادة PADI لا تنتهي صلاحيتها أبدًا؛ ويكفي إتمام برنامج Scuba Review (50€) لاستعادة مهاراتك العملية بالكامل دون إعادة الدورة.'],
            ['Is scuba equipment included in the €50 Scuba Review?', 'Tanks and weights are included; if you do not bring your own BCD, regulator, wetsuit, mask and fins, add Full Equipment Set rental at €25/day.', 'هل معدات الغوص مشمولة في سعر الـ50€؟', 'الأسطوانات والأوزان مشمولة؛ وإذا لم تحضر معداتك الشخصية يمكنك استئجار الطقم الكامل بسعر 25€ لليوم.'],
        ],
    ],

    /* ==================== CERTIFIED PACKAGES ==================== */
    'dive-1' => [
        'h1' => [
            'en' => 'Single Guided Shore Dive in Sharm El Sheikh — Al Montazah House Reef (€45)',
            'ar' => 'غطسة شاطئية واحدة موجهة للغواصين المعتمدين في شرم الشيخ (45€)',
        ],
        'meta_title' => [
            'en' => 'Single Shore Dive Sharm El Sheikh | €45 House Reef | Sea College',
            'ar' => 'غطسة شاطئية للمعتمدين شرم الشيخ | 45€ شاملة الانتقالات | سي كوليدج',
        ],
        'meta_desc' => [
            'en' => 'Short on time? Book a single guided certified shore dive on our Al Montazah house reef in Sharm El Sheikh (€45) — 12L tank, weights, PADI guide & hotel transfers.',
            'ar' => 'احجز غطسة شاطئية واحدة للغواصين المعتمدين على الشعاب المنزلية بالمنتزه في شرم الشيخ (45€) شاملة أسطوانة 12 لتر والأوزان ومرشد PADI وانتقالات الفندق.',
        ],
        'aeo_answer' => [
            'en' => 'A Single Shore Dive for certified divers at Sea College Dive Club costs €45 per person and takes 2 to 3 hours door-to-door. Conducted on our vibrant Al Montazah house reef opposite Tiran Island, it includes return Sharm El Sheikh hotel transfers, a certified PADI dive guide, a 12L air tank and weights — with zero Boat Supplement or Marine Park Fee required.',
            'ar' => 'تبلغ تكلفة الغطسة الشاطئية الواحدة للغواصين المعتمدين في سي كوليدج دايف كلوب 45€ للفرد وتستغرق من ساعتين إلى 3 ساعات. تُقام الغطسة على الشعاب المنزلية الغنية في المنتزه قبالة مضيق تيران، وتشمل انتقالات الفندق في شرم الشيخ ومرشد غوص PADI وأسطوانة هواء 12 لتر والأوزان بدون أي رسوم قارب أو محمية.',
        ],
        'overview' => [
            'en' => [
                'Ideal when your travel schedule leaves just a morning or afternoon window, our Single Shore Dive steps directly into the clear waters of Al Montazah without spending 8 hours on a yacht.',
                'Because the House Reef is exempt from the €10 Boat Supplement and €5 Marine Park Fee, €45 is your complete dive-and-transfer price (plus gear rental only if you don\'t pack your own).',
            ],
            'ar' => [
                'خيار مثالي للغواص المعتمد الذي لديه بضع ساعات فقط في برنامجه السياحي ويرغب في غطسة بحر أحمر ممتعة دون قضاء يوم كامل على القارب.',
                'ولأن الغطسة تتم على الشعاب المنزلية بالمنتزه، لا تُطبق عليها رسوم القارب (10€) ولا رسوم المحمية (5€)، مع شمول انتقالات الفندق والمرشد.',
            ],
        ],
        'specs' => [
            ['Price', 'السعر', '€45 per certified diver', '45€ للغواص المعتمد'],
            ['Duration', 'المدة', '2–3 hours (morning or afternoon slot)', '2–3 ساعات (فترة صباحية أو مسائية)'],
            ['Boat / Park Fees', 'رسوم القارب والمحمية', '€0 on House Reef', '0€ على الشعاب المنزلية'],
            ['Included', 'يشمل', 'PADI guide, 12L air tank, weights & Sharm hotel transfers', 'مرشد PADI وأسطوانة 12 لتر والأوزان وانتقالات الفندق'],
            ['Minimum Level', 'المستوى المطلوب', 'Open Water Diver or higher (recent dive <6 months)', 'شهادة Open Water فأعلى (وغطسة خلال آخر 6 أشهر)'],
        ],
        'steps' => [
            ['Hotel Pickup & Check-In', 'التوصيل من الفندق وتسجيل البيانات', 'Pickup from your Sharm hotel to Cyrene Sharm for c-card check and briefing.', 'اصطحابك من الفندق إلى مركزنا بالمنتزه لمراجعة الشهادة وشرح مسار الشعاب.'],
            ['Gear Assembly & Reef Entry', 'تجهيز المعدات والنزول للشاطئ', 'Gear up steps from the water and perform your buddy safety check.', 'تجهيز معداتك على بُعد خطوات من البحر وإجراء فحص الأمان مع مرشدك.'],
            ['45–55 Minute Guided House Reef Dive', 'غطسة موجهة لمدة 45–55 دقيقة', 'Explore coral slopes, anemones, moray eels and blue-spotted rays at relaxed depths.', 'استكشاف المنحدرات المرجانية والأسماك الملونة والراي ذي النقاط الزرقاء مع مرشد PADI.'],
            ['Shower, Logbook & Hotel Return', 'الاستحمام وتوقيع السجل والعودة', 'Rinse gear at the center, stamp your logbook and head back to your hotel.', 'غسل المعدات وختم سجل الغوص ثم التوصيل المريح إلى فندقك.'],
        ],
        'included' => [
            'en' => ['1 guided house reef open water dive', 'PADI Dive Guide', '12L air cylinder, weight belt & weights', 'Return Sharm El Sheikh hotel transfers'],
            'ar' => ['غطسة شاطئية مفتوحة واحدة مع مرشد', 'مرشد غوص PADI', 'أسطوانة هواء 12 لتر وحزام وأوزان', 'انتقالات الفندق ذهابًا وإيابًا في شرم الشيخ'],
        ],
        'excluded' => [
            'en' => ['Equipment rental (€25/day full set or per-piece rental)', 'Check Dive (€25) if inactive >6 months', 'Night dive supplement (+€30 if booked after sunset)'],
            'ar' => ['تأجير المعدات الشخصية (25€ للطقم الكامل عند الحاجة)', 'غطسة اختبارية Check Dive (25€) للمنقطعين لأكثر من 6 أشهر', 'إضافة الغوص الليلي (+30€ عند الرغبة بغطسة ليلية)'],
        ],
        'ideal_for' => [
            'en' => ['Certified divers squeezing a quick Red Sea dive into an arrival or pre-no-fly day', 'Underwater macro photographers loving long, unhurried shallow bottom times'],
            'ar' => ['الغواصون المعتمدون الراغبون في غطسة سريعة دون الارتباط بيوم كامل', 'محبو التصوير الدقيق تحت الماء في مياه هادئة وغنية بالكائنات المرجانية'],
        ],
        'schema_type' => 'TouristTrip',
        'compare_mode' => 'packages',
        'review_idx' => 1,
        'related' => ['dive-2', 'dive-3', 'sup-night', 'check-dive'],
        'faqs' => [
            ['Why book 2 or 3 dives instead of a Single Shore Dive?', 'A single dive is €45, two dives are €85 (€42.50/dive), and from 3 dives onwards (€105) the rate drops to €35 per dive across boat or shore sites.', 'لماذا يُعد حجز غطستين أو 3 غطسات أوفر من غطسة واحدة؟', 'تبلغ الغطسة الواحدة 45€، والغطستان 85€ (42.5€ للغطسة)، ومن 3 غطسات فأكثر (105€) ينخفض سعر الغطسة إلى 35€ فقط سواء بالقارب أو الشاطئ.'],
            ['Do I pay the €5 Marine Park Fee on a House Reef dive?', 'No — the €5 Marine Park Fee applies to all Sharm El Sheikh offshore/park zones except our House Reef.', 'هل تُطبق رسوم المحمية (5€) على الشعاب المنزلية؟', 'لا، تُستثنى الشعاب المنزلية من رسوم المحمية اليومية (5€) ومن رسوم القارب (10€).'],
        ],
    ],

    'dive-2' => [
        'h1' => [
            'en' => '2 Dives by Boat or Shore in Sharm El Sheikh — 1-Day Certified Package (€85)',
            'ar' => 'باقة غطستين بالقارب أو من الشاطئ في شرم الشيخ — يوم واحد للمعتمدين (85€)',
        ],
        'meta_title' => [
            'en' => '2 Boat Dives Sharm El Sheikh | €85 Certified Day Trip | Sea College',
            'ar' => 'رحلة غطستين بالقارب شرم الشيخ | 85€ للغواصين المعتمدين | سي كوليدج',
        ],
        'meta_desc' => [
            'en' => '1-day certified scuba trip in Sharm El Sheikh: 2 guided boat or shore dives (€85) to Ras Mohammed, Straits of Tiran or local reefs. Tanks, weights & transfers included.',
            'ar' => 'يوم غوص كامل للمعتمدين في شرم الشيخ: غطستان بالقارب أو الشاطئ (85€) إلى رأس محمد أو مضيق تيران. شاملة الأسطوانات والأوزان والمرشد وانتقالات الفندق.',
        ],
        'aeo_answer' => [
            'en' => 'The 2 Dives by Boat or Shore package at Sea College Dive Club costs €85 per certified diver (€42.50/dive) for a 1-day Red Sea outing in Sharm El Sheikh. It includes a certified PADI dive guide, two 12L air tanks, weights and return hotel transfers; choosing a full-day boat trip to Ras Mohammed or the Straits of Tiran adds the €10 Boat Supplement (including buffet lunch & drinks) and €5 Marine Park Fee.',
            'ar' => 'تبلغ تكلفة باقة غطستين بالقارب أو من الشاطئ في سي كوليدج دايف كلوب 85€ للغواص المعتمد (42.5€ للغطسة) ليوم واحد في شرم الشيخ. تشمل الباقة مرشد PADI وأسطوانتي هواء 12 لتر والأوزان وانتقالات الفندق؛ وعند اختيار رحلة القارب إلى رأس محمد أو تيران تُضاف 10€ رسوم قارب (شاملة الغداء والمشروبات) و5€ رسوم محمية.',
        ],
        'overview' => [
            'en' => [
                'Our classic single-day Red Sea diving experience: spend a full day aboard our comfortable dive yacht exploring two world-class coral walls or drift reefs in Ras Mohammed National Park, the Straits of Tiran or Sharm\'s local coast.',
                'If you later decide to stay underwater for another day or two, upgrading to 3+ dives drops the per-dive price to €35 across your package.',
            ],
            'ar' => [
                'الخيار الكلاسيكي ليوم غوص متكامل في البحر الأحمر: استمتع بغطستين موجهتين على متن يخت الغوص المريح في جدران رأس محمد أو شعاب مضيق تيران الشهيرة أو الشعاب القريبة.',
                'وإذا قررت تمديد أيام الغوص لاحقًا إلى 3 غطسات فأكثر، ينخفض سعر الغطسة إلى 35€ فقط.',
            ],
        ],
        'specs' => [
            ['Price', 'السعر', '€85 per certified diver (€42.50/dive)', '85€ للغواص المعتمد (42.5€ للغطسة)'],
            ['Duration', 'المدة', '1 day (~08:00–16:30 by boat, or half-day shore)', 'يوم واحد (08:00–16:30 بالقارب، أو نصف يوم من الشاطئ)'],
            ['Dive Zones', 'مناطق الغوص', 'Ras Mohammed, Straits of Tiran or Local Reefs', 'رأس محمد، مضيق تيران، أو الشعاب المحلية'],
            ['Included', 'يشمل', 'PADI guide, 2 × 12L air tanks, weights & Sharm hotel transfers', 'مرشد PADI وأسطوانتا هواء 12 لتر والأوزان وانتقالات الفندق'],
            ['Boat Day Supplements', 'رسوم يوم القارب', '+€10 Boat Supplement (incl. lunch/drinks) + €5 Marine Park Fee', '+10€ رسوم قارب (شاملة الغداء والمشروبات) + 5€ رسوم محمية'],
        ],
        'steps' => [
            ['Morning Hotel Pickup (~08:00)', 'الاستقبال الصباحي من الفندق (~08:00)', 'Pickup from your Sharm hotel to check in and board our dive boat.', 'اصطحابك من فندقك في شرم الشيخ لتسجيل البيانات والصعود للقارب.'],
            ['Site Briefing & Dive 1', 'الشرح الفني والغطسة الأولى', 'Detailed reef/current briefing followed by a 45–55 min morning wall or drift dive.', 'شرح تفصيلي لطبيعة الشعاب والتيارات يتبعه الغطسة الصباحية الأولى.'],
            ['Buffet Lunch & Surface Interval', 'غداء البوفيه واستراحة السطح', 'Enjoy freshly cooked lunch, tea, coffee and cold drinks on the shaded deck.', 'تناول غداء البوفيه والمشروبات الساخنة والباردة خلال استراحة السطح.'],
            ['Dive 2, Logbook & Hotel Return', 'الغطسة الثانية والعودة للفندق', 'Complete your second reef dive, log marine sightings and return to your hotel around 16:30.', 'إتمام الغطسة الثانية وتوثيق سجل الغوص ثم العودة إلى الفندق عصرًا.'],
        ],
        'included' => [
            'en' => ['2 guided open water dives (boat or shore)', 'Certified PADI Dive Guide', '2 × 12L air cylinders, weight belt & weights', 'Return hotel transfers in Sharm El Sheikh'],
            'ar' => ['غطستان مفتوحتان مع مرشد (بالقارب أو الشاطئ)', 'مرشد غوص PADI معتمد', 'أسطوانتا هواء 12 لتر وحزام وأوزان', 'انتقالات الفندق ذهابًا وإيابًا في شرم الشيخ'],
        ],
        'excluded' => [
            'en' => ['€10 Boat Trip Supplement + €5 Marine Park Fee on boat days', 'Scuba gear rental (€25/day full set if needed) & Nitrox fills (€8/tank)'],
            'ar' => ['10€ رسوم قارب + 5€ رسوم محمية في يوم القارب', 'تأجير المعدات الشخصية (25€ لليوم عند الحاجة) والنيتروكس (8€ للأسطوانة)'],
        ],
        'ideal_for' => [
            'en' => ['Certified divers dedicating 1 full day of their family holiday to Red Sea diving', 'Divers wanting a warm-up boat day before booking the SS Thistlegorm wreck'],
            'ar' => ['الغواصون المعتمدون الراغبون في تخصيص يوم واحد للغوص خلال إجازتهم', 'من يرغب بيوم غوص تمهيدي قبل حجز رحلة حطام ثيستلجورم'],
        ],
        'schema_type' => 'TouristTrip',
        'compare_mode' => 'packages',
        'review_idx' => 1,
        'related' => ['dive-3', 'dive-4', 'dive-6', 'eq-full'],
        'faqs' => [
            ['Can we add a 3rd dive on the same boat day?', 'Yes! Upgrading from 2 dives (€85) to the 3-Dive Package (€105) adds a 3rd dive for just €20 more—bringing your entire per-dive rate down to €35.', 'هل يمكن إضافة غطسة ثالثة في نفس اليوم؟', 'نعم! الترقية من غطستين (85€) إلى باقة 3 غطسات (105€) تضيف الغطسة الثالثة بفرق 20€ فقط ليصبح سعر الغطسة 35€.'],
            ['Which reefs will we visit on a 1-day 2-dive boat trip?', 'Routes alternate daily based on wind, currents and diver experience between Ras Mohammed National Park (Shark & Yolanda, Ras Za\'atar), the Straits of Tiran (Jackson, Gordon, Thomas, Woodhouse) and Sharm local gardens (Ras Umm Sid, Temple, Far Garden).', 'ما المواقع التي نزورها في رحلة الغطستين بالقارب؟', 'تتنوع المسارات يوميًا حسب حالة الرياح ومستوى الغواصين بين محمية رأس محمد ومضيق تيران والشعاب المحلية الشهيرة في شرم الشيخ.'],
        ],
    ],

    'dive-3' => $buildCertifiedPkg(
        3, 105, '1–2 days', 'يوم إلى يومين',
        'Stepping up to 3 dives unlocks Sea College Dive Club\'s €35 per-dive tier — just €20 more than the 2-dive package (€85)! Complete all 3 dives on a 3-dive boat day or pair a 2-dive boat trip with a relaxed House Reef or Night Dive (+€30).',
        'مع باقة 3 غطسات تحصل مباشرة على السعر المخفض (35€ للغطسة الواحدة) — بفرق 20€ فقط عن باقة الغطستين (85€)! يمكنك تنفيذ الغطسات الثلاث في يوم قارب ممتد أو توزيعها بين يوم قارب وغطسة على الشعاب المنزلية.',
        'Popular combinations include two morning dives in the Straits of Tiran (Jackson & Gordon Reef) plus an afternoon local reef or sunset shore dive at Al Montazah.',
        'من أشهر الخطط تنفيذ غطستين صباحيتين في مضيق تيران (جاكسون وجوردون ريف) مع غطسة ثالثة في موقع محلي هادئ أو على الشعاب المنزلية.',
        ['dive-2', 'dive-4', 'dive-6', 'sup-night'], 1
    ),

    'dive-4' => $buildCertifiedPkg(
        4, 140, '2 days', 'يومان',
        'Our balanced 2-day certified package (€140 total · €35/dive) gives you two full mornings/days on the Red Sea — enough time to experience both Sharm El Sheikh\'s famous northern reefs and southern marine park walls without rushing.',
        'تمنحك باقة 4 غطسات (140€ إجمالًا · 35€ للغطسة) يومين مثاليين في البحر الأحمر لتجربة التنوع المذهل بين مواقع شمال شرم الشيخ ومحمية الجنوب بهدوء وراحة.',
        'Spend Day 1 drifting the coral gardens of the Straits of Tiran across from our Al Montazah base, and Day 2 exploring the dramatic drop-offs of Ras Mohammed National Park.',
        'استمتع في اليوم الأول بشعاب مضيق تيران الغنية بالمرجان اللين، وفي اليوم الثاني بجدران محمية رأس محمد الشهيرة مثل شارك ويولاندا ريف.',
        ['dive-3', 'dive-5', 'dive-6', 'eq-12nx'], 1
    ),

    'dive-5' => $buildCertifiedPkg(
        5, 175, '2–3 days', '2–3 أيام',
        'At €175 (€35/dive), the 5-Dive Package is ideal for a long-weekend Red Sea break: run two 2-dive boat days plus an atmospheric House Reef Sunset/Night Dive (+€30) or a 3-dive signature yacht day.',
        'بسعر 175€ (35€ للغطسة)، تناسب باقة 5 غطسات عطلات نهاية الأسبوع الطويلة؛ حيث يمكنك الجمع بين يومين بالقارب (4 غطسات) مع غطسة خامسة نهارية أو ليلية (+30€).',
        'Covers the best of Tiran Island, Ras Mohammed and Sharm\'s sheltered bays (Ras Nasrani, Far Garden and Ras Umm Sid gorgonian fans).',
        'تغطي الباقة أجمل مواقع تيران ورأس محمد والخلجان المحمية الغنية بمراوح الجورجونيا مثل رأس أم سيد ورأس نصراني.',
        ['dive-4', 'dive-6', 'sup-night', 'sup-dunraven'], 2
    ),

    'dive-6' => $buildCertifiedPkg(
        6, 210, '3 days', '3 أيام',
        'Our most popular multi-day certified package (€210 · €35/dive): three relaxed 2-dive days that hit Sharm El Sheikh\'s "Big Three" — Straits of Tiran, Ras Mohammed National Park and Sharm Local Reefs (or upgrade 2 dives for SS Thistlegorm / Dahab).',
        'الباقة متعددة الأيام الأكثر طلبًا لدينا (210€ · 35€ للغطسة): 3 أيام مريحة بواقع غطستين يوميًا تغطي الثلاثية الذهبية في شرم الشيخ: مضيق تيران، محمية رأس محمد، والشعاب المحلية أو الحطام.',
        'Three days underwater gives our guides time to tailor site difficulty to your comfort and air consumption, moving from calm coral amphitheatres to exhilarating blue-water drifts.',
        'تتيح لك الأيام الثلاثة تدرجًا رائعًا مع مرشدينا من الحدائق المرجانية الهادئة إلى الغطسات الانجرافية المشوقة ومشاهدة أسماك الباراكودا والجاك والسلاحف.',
        ['dive-5', 'dive-8', 'dive-10', 'sup-thistlegorm'], 1
    ),

    'dive-7' => $buildCertifiedPkg(
        7, 245, '3–4 days', '3–4 أيام',
        'Designed for certified divers wanting three full 2-dive boat days plus one extra reef, wreck or night dive (€245 · €35/dive).',
        'صُممت باقة 7 غطسات (245€ · 35€ للغطسة) للغواصين الراغبين في 3 أيام كاملة بالقارب بالإضافة إلى غطسة سابعة على الشعاب المنزلية أو غطسة ليلية.',
        'Pair Tiran and Ras Mohammed boat days with an extra third tank on calm summer days or a twilight dive off Al Montazah.',
        'اجمع بين رحلات القارب إلى تيران ورأس محمد مع غطسة إضافية ثالثة أو غطسة مسائية غنية بالكائنات الليلية.',
        ['dive-6', 'dive-8', 'sup-night', 'sup-dunraven'], 2
    ),

    'dive-8' => $buildCertifiedPkg(
        8, 280, '4 days', '4 أيام',
        'Four full days of Red Sea diving (€280 · €35/dive) — ideal for a 1-week Sharm El Sheikh resort holiday while still keeping Arrival Day, one desert/old-market rest day and your 24-hour pre-flight surface interval free.',
        'أربعة أيام كاملة من الغوص (280€ · 35€ للغطسة) — الخيار الأمثل لإجازة أسبوع في شرم الشيخ مع الاحتفاظ بيوم الوصول ويوم راحة للتسوق و24 ساعة آمنة قبل الطيران.',
        'Enjoy Tiran\'s four famous reefs (Jackson, Woodhouse, Thomas, Gordon), Ras Mohammed\'s Shark & Yolanda and Jackfish Alley, local pinnacles, and optional SS Thistlegorm or Dahab day trips.',
        'استكشف شعاب تيران الأربعة الشهيرة، وجدران رأس محمد وكهف جاك فيش آلي، مع إمكانية تخصيص يوم لحطام ثيستلجورم أو دهب.',
        ['dive-6', 'dive-9', 'dive-10', 'sup-thistlegorm'], 2
    ),

    'dive-9' => $buildCertifiedPkg(
        9, 315, '4–5 days', '4–5 أيام',
        'At €315 (€35/dive), the 9-Dive Package suits avid Red Sea divers combining four 2-dive boat days with a signature night dive or third afternoon tank.',
        'بسعر 315€ (35€ للغطسة)، تمنحك باقة 9 غطسات أربعة أيام كاملة بالقارب مضافًا إليها غطسة تاسعة ليلية أو ثالثة نهارية.',
        'Strongly recommended with Enriched Air Nitrox (€8/12L fill) to reduce repetitive-dive nitrogen loading and extend no-decompression bottom times across 4–5 consecutive days.',
        'نوصي بشدة باستخدام أسطوانات النيتروكس (8€ لـ12 لتر) مع هذه الباقة لتمديد أوقات القاع وتقليل الإجهاد عبر الأيام المتتالية.',
        ['dive-8', 'dive-10', 'eq-12nx', 'nitrox'], 2
    ),

    'dive-10' => $buildCertifiedPkg(
        10, 350, '5 days', '5 أيام',
        'The flagship 5-day Red Sea diving week at Sea College Dive Club (€350 · €35/dive — €100 less than single-dive pricing) — and once you complete 10 dives, every additional dive drops to just €30!',
        'أسبوع الغوص الكامل والأشهر في سي كوليدج دايف كلوب: 5 أيام / 10 غطسات بسعر 350€ (35€ للغطسة — بتوفير 100€ عن السعر المفرد)، وكل غطسة إضافية بعد العاشرة تنخفض إلى 30€ فقط!',
        'Across five boat days you can dive virtually every iconic reef around Sharm El Sheikh: Ras Mohammed National Park, all four Tiran reefs, Dunraven or Million Hope wrecks, SS Thistlegorm (+€150) and Dahab (+€115).',
        'خلال 5 أيام بحرية ستتمكن من زيارة معظم كنوز شرم الشيخ: رأس محمد، شعاب تيران الأربعة، الشعاب المحلية، مع إمكانية دمج حطام ثيستلجورم أو رحلة دهب.',
        ['dive-extra', 'dive-8', 'sup-thistlegorm', 'sup-dahab'], 1
    ),

    'dive-extra' => [
        'h1' => [
            'en' => 'Additional Dive After the 10th Dive — €30 Loyalty Rate in Sharm El Sheikh',
            'ar' => 'غطسة إضافية بعد الغطسة العاشرة — سعر مخفض 30€ في شرم الشيخ',
        ],
        'meta_title' => [
            'en' => 'Extra Dive After 10-Dive Package | €30/Dive | Sea College Sharm',
            'ar' => 'غطسة إضافية بعد باقة 10 غطسات | 30€ للغطسة | سي كوليدج',
        ],
        'meta_desc' => [
            'en' => 'Booked a 10-dive package (€350) in Sharm El Sheikh? Add extra 11th, 12th or 15th guided Red Sea dives at our lowest €30 per-dive rate.',
            'ar' => 'أتممت باقة الـ10 غطسات (350€) في شرم الشيخ؟ أضف الغطسة الـ11 أو الـ12 فأكثر بأقل سعر للغطسة 30€ فقط شاملة الأسطوانة والمرشد.',
        ],
        'aeo_answer' => [
            'en' => 'Every additional guided dive after completing a personal 10-Dive Package (€350) at Sea College Dive Club in Sharm El Sheikh costs €30 per dive — our lowest per-dive rate (33% below a €45 single dive). It includes your PADI dive guide, 12L air cylinder, weights and Sharm hotel transfers.',
            'ar' => 'تبلغ تكلفة كل غطسة إضافية بعد إتمام باقة الـ10 غطسات الشخصية (350€) في سي كوليدج دايف كلوب بشرم الشيخ 30€ فقط للغطسة — وهو أقل سعر للغطسة لدينا (أقل بـ33% من الغطسة المفردة). يشمل السعر مرشد PADI وأسطوانة هواء 12 لتر والأوزان وانتقالات الفندق.',
        ],
        'overview' => [
            'en' => [
                'Staying in Sharm El Sheikh for 10 to 14 nights or adding third afternoon tanks onto your 5-day boat week? Once your personal tally passes 10 dives, every extra boat or shore dive is billed at €30.',
                'Add as many extra dives as you want into your online booking cart alongside the 10-Dive Package, or simply extend day-by-day at the dive center.',
            ],
            'ar' => [
                'إذا كانت إقامتك في شرم الشيخ تمتد لأسبوعين أو رغبت في إضافة غطسات ثالثة خلال أسبوع الغوص، فبمجرد وصولك إلى 10 غطسات شخصية يصبح سعر أي غطسة تالية 30€ فقط.',
                'يمكنك إضافتها مسبقًا مع باقة الـ10 غطسات في السلة أو تمديدها يومًا بيوم مباشرة في المركز.',
            ],
        ],
        'specs' => [
            ['Price', 'السعر', '€30 per additional dive (from 11th dive onwards)', '30€ لكل غطسة إضافية (بدءًا من الغطسة الـ11)'],
            ['Prerequisite', 'الشرط الأساسي', 'Active personal 10-Dive Package (€350) on the same trip', 'حجز باقة 10 غطسات شخصية (350€) في نفس الرحلة'],
            ['Included', 'يشمل', 'PADI guide, 12L air tank, weights & hotel transfers', 'مرشد PADI وأسطوانة 12 لتر والأوزان وانتقالات الفندق'],
        ],
        'steps' => [
            ['Select 10-Dive Package First', 'اختيار باقة الـ10 غطسات أولًا', 'Add the €350 10-Dive Package to your booking.', 'أضف باقة 10 غطسات (350€) إلى سلة الحجز.'],
            ['Add Extra Dives (€30 Each)', 'إضافة الغطسات الإضافية (30€ للغطسة)', 'Choose the quantity of extra dives (e.g. +2 for 12 dives at €410, +4 for 14 dives at €470).', 'حدد عدد الغطسات الإضافية المطلوبة (مثلًا +2 ليصبح الإجمالي 12 غطسة بـ410€).'],
            ['Dive Boat or Shore Flexibly', 'تنفيذ الغطسات بالقارب أو الشاطئ', 'Use extra dives on 3rd boat tanks, extra days or house reef sessions.', 'استخدمها في أيام إضافية بالقارب أو كغطسة ثالثة أو على الشعاب المنزلية.'],
        ],
        'included' => [
            'en' => ['1 guided boat or shore dive per unit added', '12L air cylinder & weights', 'Sharm El Sheikh hotel transfers'],
            'ar' => ['غطسة موجهة بالقارب أو الشاطئ لكل وحدة مضافة', 'أسطوانة هواء 12 لتر وأوزان', 'انتقالات الفندق في شرم الشيخ'],
        ],
        'excluded' => [
            'en' => ['Daily Boat (€10/day) & Marine Park (€5/day) fees if adding a new boat day', 'Gear rental & Nitrox upgrades'],
            'ar' => ['رسوم القارب (10€) والمحمية (5€) في حال إضافة يوم قارب جديد', 'تأجير المعدات الشخصية والنيتروكس'],
        ],
        'ideal_for' => [
            'en' => ['Two-week Red Sea holidaymakers logging 12–20 dives', 'Divers doing 3 dives per boat day across a 4- or 5-day trip'],
            'ar' => ['ضيوف الإجازات الطويلة الراغبون في 12 إلى 20 غطسة', 'الغواصون الذين ينفذون 3 غطسات يوميًا'],
        ],
        'schema_type' => 'TouristTrip',
        'compare_mode' => 'packages',
        'review_idx' => 1,
        'related' => ['dive-10', 'eq-12nx', 'sup-thistlegorm', 'sup-dahab'],
        'faqs' => [
            ['Do I have to pre-book extra dives or can I add them on site?', 'Either works — pre-adding reserves boat space in advance, and you can also add €30 extra dives at the center once your 10-dive package is underway.', 'هل يجب حجز الغطسات الإضافية مسبقًا أم يمكن إضافتها في المركز؟', 'كلاهما متاح؛ الإضافة المسبقة تحجز مقعدك على القارب مبكرًا، كما يمكنك طلب غطسات إضافية بـ30€ مباشرة في المركز.'],
            ['If I do a 3rd dive on an existing boat day, do I pay another €10 boat fee?', 'No — the €10 Boat Supplement and €5 Marine Park Fee are per day, not per dive!', 'إذا نفذت الغطسة الإضافية كغطسة ثالثة في نفس يوم القارب، هل أدفع رسوم قارب مرة أخرى؟', 'لا، رسوم القارب (10€) والمحمية (5€) تُحتسب لليوم الواحد وليس لكل غطسة!'],
        ],
    ],

    /* ==================== WRECK, ZODIAC, NIGHT & DAHAB SUPPLEMENTS ==================== */
    'sup-night' => [
        'h1' => [
            'en' => 'Night Dive & Early Morning Dive in Sharm El Sheikh (+€30 Supplement)',
            'ar' => 'غطسة ليلية أو غطسة الصباح الباكر في شرم الشيخ (+30€ إضافة)',
        ],
        'meta_title' => [
            'en' => 'Night & Dawn Scuba Dive Sharm El Sheikh | +€30 Supplement | Sea College',
            'ar' => 'غطسة ليلية أو فجرية شرم الشيخ | +30€ للغواصين المعتمدين | سي كوليدج',
        ],
        'meta_desc' => [
            'en' => 'Watch Sharm El Sheikh\'s reefs transform after dark or at first light (+€30 supplement on 1 package dive). Lionfish, octopus, Spanish dancers & reef predators.',
            'ar' => 'شاهد تحوّل شعاب شرم الشيخ ليلًا أو عند الفجر (+30€ إضافة على غطسة من باقتك): الأخطبوط، الراقصة الإسبانية وأسماك الأسد.',
        ],
        'aeo_answer' => [
            'en' => 'The Night & Early Morning Dive supplement at Sea College Dive Club costs €30 per certified diver on top of 1 dive from your dive package (about 2 hours total). Entering just after sunset or at dawn with a PADI guide reveals nocturnal Red Sea life — hunting lionfish, octopus, basket stars, moray eels and Spanish dancer nudibranchs.',
            'ar' => 'تبلغ إضافة الغطسة الليلية أو غطسة الفجر في سي كوليدج دايف كلوب 30€ للغواص المعتمد فوق غطسة واحدة من باقتك (نحو ساعتين إجمالًا). تتيح لك النزول بعد الغروب أو مع أول ضوء برفقة مرشد PADI لرؤية الكائنات الليلية النادرة مثل الأخطبوط والراقصة الإسبانية ونجوم السلة.',
        ],
        'overview' => [
            'en' => [
                'When the sun dips behind the Sinai mountains, daytime parrotfish tuck into sleeping cocoons and an entirely new cast of nocturnal creatures emerges across our reef slopes.',
                'Alternatively, book a pre-breakfast Early Morning Dive when water clarity peaks and pelagic hunters patrol shallow reef edges before daytime boat traffic arrives.',
            ],
            'ar' => [
                'مع غروب الشمس خلف جبال سيناء تختبئ الأسماك النهارية وتخرج مجموعة مدهشة من الكائنات الليلية مثل الأخطبوط وثعابين الموراي والقشريات المضيئة تحت ضوء الكشاف.',
                'كما يمكنك اختيار غطسة الفجر المبكرة حيث تبلغ الرؤية ذروتها وتقترب الأسماك الكبيرة من حواف الشعاب قبل حركة القوارب اليومية.',
            ],
        ],
        'specs' => [
            ['Supplement Price', 'سعر الإضافة', '€30 + 1 dive from your certified dive package', '30€ + غطسة واحدة من باقة الغوص الخاصة بك'],
            ['Duration', 'المدة', '~2 hours (dusk/night or dawn)', 'حوالي ساعتين (بعد الغروب أو فجرًا)'],
            ['Minimum Level', 'المستوى المطلوب', 'Open Water Diver or higher (torch required)', 'شهادة Open Water فأعلى'],
            ['Torch Rental', 'كشاف الغوص', 'Bring your own or rent an LED Underwater Torch (€6/day)', 'أحضر كشافك أو استأجر كشاف LED بـ6€'],
        ],
        'steps' => [
            ['Dusk / Pre-Dawn Briefing', 'شرح إشارات الضوء والملاحة', 'Learn torch etiquette (never shine in a buddy\'s eyes), beam signals and entry/exit markers.', 'تعلّم آداب استخدام الكشاف وإشارات الضوء الضوئية ونقاط الدخول والخروج.'],
            ['Guided Nocturnal or Dawn Dive', 'غطسة موجهة ليلًا أو فجرًا', '45-minute shallow-to-moderate reef exploration spotting octopus, crabs, lionfish and bioluminescence.', 'جولة لمدة 45 دقيقة لرصد الأخطبوط والقشريات والبريق الحيوي في الماء.'],
            ['Warm Tea & Logbook Entry', 'مشروب دافئ وتوثيق السجل', 'Surface under the Sinai stars, warm up with hot tea and return to your hotel.', 'الصعود تحت نجوم سيناء وتناول الشاي الدافئ وتوثيق الغطسة والعودة للفندق.'],
        ],
        'included' => [
            'en' => ['Night or early-morning logistics, surface marker lights & PADI night guide', 'Return hotel transfer in Sharm El Sheikh'],
            'ar' => ['تجهيزات الإضاءة السطحية ومرافقة مرشد PADI للغوص الليلي', 'انتقالات الفندق ذهابًا وإيابًا في شرم الشيخ'],
        ],
        'excluded' => [
            'en' => ['Base dive from your certified package (or Single Dive €45)', 'Underwater Torch rental (€6/day if needed) & scuba gear rental'],
            'ar' => ['الغطسة الأساسية من باقتك (أو غطسة مفردة 45€)', 'تأجير كشاف الغوص (6€) والمعدات الشخصية عند الحاجة'],
        ],
        'ideal_for' => [
            'en' => ['Open Water & Advanced divers wanting a completely different Red Sea sensory experience', 'Underwater photographers hunting macro subjects and Spanish dancers'],
            'ar' => ['حاملو Open Water وAdvanced الباحثون عن تجربة ليلية ساحرة ومختلفة', 'مصورو الماكرو تحت الماء والباحثون عن الكائنات الليلية النادرة'],
        ],
        'schema_type' => 'TouristTrip',
        'compare_mode' => 'none',
        'review_idx' => 2,
        'related' => ['eq-torch', 'night', 'dive-3', 'dive-6'],
        'faqs' => [
            ['Can an Open Water Diver join a Night Dive?', 'Yes! Certified Open Water Divers can join guided night dives on calm reefs, or even count the dive toward the 3-dive PADI Night Diver Specialty (€230).', 'هل يمكن لحامل شهادة Open Water المشاركة في الغطسة الليلية؟', 'نعم، يمكن لحاملي Open Water الانضمام للغطسة الليلية الموجهة، أو حتى الترقية لدورة تخصص الغواص الليلي PADI Night Diver (230€).'],
            ['Is an underwater torch included?', 'Bring your own primary dive light or rent a rechargeable LED Underwater Torch from our equipment room for €6.', 'هل كشاف الغوص مشمول؟', 'يمكنك إحضار كشافك الخاص أو استئجار كشاف غوص LED قوي من المركز بسعر 6€ فقط.'],
        ],
    ],

    'sup-thistlegorm' => [
        'h1' => [
            'en' => 'SS Thistlegorm Wreck Dive from Sharm El Sheikh — Iconic WWII Red Sea Wreck (+€150)',
            'ar' => 'رحلة غوص حطام سفينة ثيستلجورم (SS Thistlegorm) من شرم الشيخ (+150€)',
        ],
        'meta_title' => [
            'en' => 'SS Thistlegorm Wreck Dive Sharm El Sheikh | +€150 | Sea College PADI 5★',
            'ar' => 'غوص حطام سفينة ثيستلجورم شرم الشيخ | +150€ | سي كوليدج',
        ],
        'meta_desc' => [
            'en' => 'Dive the world-famous WWII SS Thistlegorm wreck from Sharm El Sheikh (+€150 supplement). BSA motorcycles, Bedford trucks & locomotives at 16–30 m. Book 72h ahead.',
            'ar' => 'اغطس في أشهر حطام سفينة من الحرب العالمية الثانية SS Thistlegorm من شرم الشيخ (+150€): دراجات نارية وشاحنات وقاطرات على عمق 16–30م. احجز قبل 72 ساعة.',
        ],
        'aeo_answer' => [
            'en' => 'Diving the legendary WWII SS Thistlegorm wreck with Sea College Dive Club from Sharm El Sheikh costs a €150 supplement on top of your certified dive package (typically a 2-dive early-morning expedition lasting 7–8+ hours). Because the 128-metre British armed merchant ship rests at 16–30 m in open Gulf of Suez currents, divers must hold at least PADI Advanced Open Water (or equivalent) with 20+ logged dives and book at least 72 hours ahead.',
            'ar' => 'تبلغ إضافة رحلة الغوص على حطام سفينة الحرب العالمية الثانية الشهيرة SS Thistlegorm مع سي كوليدج دايف كلوب 150€ فوق باقة الغوص المعتمدة (رحلة مبكرة تستغرق 7–8 ساعات وتشمل غطستين على الحطام). ونظرًا لوجود السفينة (بطول 128 مترًا) على عمق 16–30 مترًا مع تيارات مفتوحة، يُشترط شهادة Advanced Open Water على الأقل مع 20 غطسة مسجلة كحد أدنى والحجز قبل 72 ساعة.',
        ],
        'overview' => [
            'en' => [
                'Sunk on 6 October 1941 at Sha\'ab Ali while carrying Allied supplies for the North African Eighth Army, the SS Thistlegorm is universally ranked among the top five wreck dives on Earth — an underwater WWII museum frozen in time.',
                'Departing early before peak boat crowds, Dive 1 conducts an exterior orientation tour past the massive stern anti-aircraft gun, blasted munitions zone, locomotive and bow winches; Dive 2 penetrates the wide cargo holds packed with Bedford trucks, Norton & BSA motorcycles, aircraft wings, rubber Wellington boots and batfish schools.',
            ],
            'ar' => [
                'غرقت السفينة البريطانية المسلحة SS Thistlegorm في 6 أكتوبر 1941 بمنطقة شعب علي وهي تحمل إمدادات الحلفاء للجيش الثامن، وتُصنف عالميًا ضمن أعظم 5 مواقع غوص حطام على كوكب الأرض كمتحف عسكري متكامل تحت الماء.',
                'ننطلق مبكرًا لتجنب الزحام؛ حيث تركز الغطسة الأولى على الهيكل الخارجي والمدفع المضاد للطائرات في المؤخرة والقاطرة البخارية، بينما تستكشف الغطسة الثانية عنابر الشحن المليئة بشاحنات بيدفورد ودراجات BSA ونورتون النارية وأجنحة الطائرات.',
            ],
        ],
        'specs' => [
            ['Supplement Price', 'سعر الإضافة', '€150 supplement (paired with your certified dive package)', '150€ إضافة على باقة الغوص للمعتمدين'],
            ['Wreck Depth', 'عمق الحطام', '16–30 metres (deck ~16–18 m, seabed ~30 m)', '16–30 مترًا (السطح ~16–18م، القاع ~30م)'],
            ['Mandatory Level', 'الشرط الإلزامي', 'Min. PADI Advanced Open Water + 20 logged dives', 'شهادة Advanced Open Water كحد أدنى + 20 غطسة مسجلة'],
            ['Duration', 'مدة الرحلة', 'Early-start expedition (~7–8+ hours return from Sharm)', 'انطلاق فجري مبكر (~7–8 ساعات شاملة الإبحار)'],
            ['Advance Booking', 'مهلة الحجز', 'At least 72 hours ahead (Coast Guard manifest)', 'قبل 72 ساعة على الأقل لتصاريح خفر السواحل'],
            ['Recommended Gas', 'الغاز الموصى به', 'Enriched Air Nitrox 32% (€8/12L tank) + Dive Torch (€6)', 'نيتروكس 32% (8€ للأسطوانة) + كشاف غوص (6€)'],
        ],
        'steps' => [
            ['Early Dawn Hotel Pickup (~04:30–05:00)', 'الاستقبال الفجري المبكر (~04:30–05:00)', 'Early pickup from your Sharm hotel to reach Sha\'ab Ali ahead of midday boat traffic, with breakfast/hot drinks aboard.', 'اصطحاب مبكر من الفندق للوصول إلى منطقة شعب علي قبل زحام القوارب مع مشروبات ساخنة وإفطار خفيف على اليخت.'],
            ['Dive 1: Exterior Survey, Stern Guns & Locomotives', 'الغطسة الأولى: الجولة الخارجية والمدافع والقاطرة', 'Descend the mooring line with your PADI guide to tour the stern AA guns, blast area, upright steam locomotive and giant propeller.', 'النزول على حبل الربط لاستكشاف مدافع المؤخرة ومنطقة الانفجار والقاطرة البخارية ومروحة السفينة الضخمة.'],
            ['Surface Interval & Hot Buffet Meal', 'استراحة السطح والوجبة الساخنة', 'Off-gas comfortably on deck while your guide briefs the exact two-level cargo-hold route for Dive 2.', 'استراحة كافية على سطح اليخت مع وجبة طازجة وشرح تفصيلي لمسار عنابر الشحن للغطسة الثانية.'],
            ['Dive 2: Cargo Holds — Motorcycles, Trucks & Armoured Cars', 'الغطسة الثانية: عنابر الشحن — الدراجات والشاحنات', 'Swim cleanly through Holds 1 & 2 to photograph rows of WWII motorcycles, trucks, rifles and Wellington boots before ascending and cruising back to Sharm.', 'دخول منظم لعنابر الشحن لمشاهدة صفوف الدراجات النارية والشاحنات والمعدات التاريخية ثم الصعود والعودة لشرم الشيخ.'],
        ],
        'included' => [
            'en' => ['Long-range yacht crossing to Sha\'ab Ali (Gulf of Suez)', 'Expert PADI Wreck Guide & detailed historical/safety briefings', 'Early hotel pickup & return transfer in Sharm El Sheikh', 'Onboard breakfast/lunch & hot/cold drinks'],
            'ar' => ['رحلة إبحار بعيدة المدى إلى شعب علي بخليج السويس', 'مرشد PADI خبير بالحطام مع شرح تاريخي وفني مفصل', 'انتقالات الفندق ذهابًا وإيابًا في شرم الشيخ', 'وجبات ومشروبات ساخنة وباردة على متن اليخت'],
        ],
        'excluded' => [
            'en' => ['Base dives from your certified dive package (e.g. 2-Dive or 6-Dive package)', 'Marine Park / Boat daily fees if applicable, Underwater Torch (€6) & Nitrox fills (€8/tank)'],
            'ar' => ['الغطسات الأساسية من باقة الغوص المعتمدة', 'كشاف الغوص (6€) وتعبئة النيتروكس الاختيارية (8€ للأسطوانة) وتأجير المعدات الشخصية'],
        ],
        'ideal_for' => [
            'en' => ['Advanced Open Water, Wreck, Deep & Master Scuba Divers with 20+ dives ticking off a bucket-list wreck', 'Underwater photographers & WWII history enthusiasts (strongly pair with Nitrox!)'],
            'ar' => ['حاملو شهادة Advanced فأعلى ممن لديهم 20+ غطسة ويرغبون في تحقيق حلم الغوص على أشهر حطام بالعالم', 'محبو التصوير تحت الماء والتاريخ البحري (ننصح بشدة بإضافة النيتروكس)'],
        ],
        'schema_type' => 'TouristTrip',
        'compare_mode' => 'none',
        'review_idx' => 2,
        'related' => ['wreck', 'aowd', 'eq-12nx', 'sup-dunraven'],
        'faqs' => [
            ['Can I dive SS Thistlegorm with an Open Water certification?', 'No — due to the 16–30 m depth, overhead cargo structures and strong Sha\'ab Ali currents, divers must hold Advanced Open Water (or complete our 2-day PADI Advanced course €350 first) and have at least 20 logged dives.', 'هل يمكنني الغوص في ثيستلجورم بشهادة Open Water فقط؟', 'لا، نظرًا لعمق 16–30 مترًا والتيارات القوية يُشترط شهادة Advanced Open Water على الأقل مع 20 غطسة مسجلة (ويمكنك إتمام دورة Advanced لدينا أولًا في يومين بـ350€).'],
            ['Why is Nitrox so popular on the SS Thistlegorm?', 'Because Cargo Holds 1 & 2 sit between 18 m and 25 m on back-to-back dives, Enriched Air Nitrox 32% (€8/tank) extends your no-decompression limit significantly so air consumption—not computer NDL—governs your dive.', 'لماذا يُنصح بالنيتروكس بشدة في رحلة ثيستلجورم؟', 'لأن عنابر الشحن تقع على عمق 18–25 مترًا في غطستين متتاليتين، يمنحك نيتروكس 32% (8€ للأسطوانة) وقتًا أطول بدون تخفيف ضغط وأمانًا أعلى.'],
            ['Why must I book 72 hours in advance?', 'Egyptian Coast Guard permits for Sha\'ab Ali crossings require passport details and minimum boat numbers submitted in advance; message us on WhatsApp with your travel week so we slot you onto our next scheduled departure.', 'لماذا يلزم الحجز قبل 72 ساعة؟', 'تتطلب تصاريح الإبحار لمنطقة شعب علي تسجيل بيانات جوازات السفر مسبقًا لدى الجهات المختصة؛ راسلنا عبر واتساب بتواريخ إقامتك لتأكيد يوم انطلاق الرحلة.'],
        ],
    ],

    'sup-dunraven' => [
        'h1' => [
            'en' => 'SS Dunraven Wreck Dive from Sharm El Sheikh — 1876 Victorian Shipwreck (+€30)',
            'ar' => 'غطسة حطام سفينة دنرافين التاريخية (SS Dunraven 1876) من شرم الشيخ (+30€)',
        ],
        'meta_title' => [
            'en' => 'SS Dunraven Wreck Dive Sharm El Sheikh | +€30 Supplement | Sea College',
            'ar' => 'غوص حطام سفينة دنرافين شرم الشيخ | +30€ إضافة | سي كوليدج',
        ],
        'meta_desc' => [
            'en' => 'Dive the coral-encrusted 1876 SS Dunraven steam-sailship near Beacon Rock from Sharm El Sheikh (+€30 supplement). Clouds of glassfish at 15–29 m. Book 72h ahead.',
            'ar' => 'اغطس في حطام السفينة البريطانية التاريخية SS Dunraven الغارقة عام 1876 قرب رأس محمد (+30€ إضافة): شعاب مرجانية وأسراب أسماك زجاجية على عمق 15–29م.',
        ],
        'aeo_answer' => [
            'en' => 'The SS Dunraven Wreck supplement at Sea College Dive Club costs €30 per certified diver on top of your dive package (book at least 72 hours ahead). Lying upside-down in two sections at 15–29 metres beside Beacon Rock (Sha\'ab Mahmud), this 1876 British steam-and-sail vessel combines atmospheric hull swim-throughs filled with thousands of shimmering glassfish and a vibrant coral reef ascent.',
            'ar' => 'تبلغ إضافة رحلة حطام سفينة SS Dunraven مع سي كوليدج دايف كلوب 30€ فوق باقة الغوص المعتمدة (تُحجز قبل 72 ساعة). ترقد هذه السفينة البريطانية الغارقة عام 1876 مقلوبة على عمق 15–29 مترًا بجوار بيكون روك، وتجمع بين هيكل تاريخي يعج بآلاف الأسماك الزجاجية وحديقة مرجانية مذهلة أثناء الصعود.',
        ],
        'overview' => [
            'en' => [
                'Built in Newcastle in 1873 and carrying spices and cotton from Bombay to Liverpool when she struck Beacon Rock in April 1876, the SS Dunraven has had 150 years to transform into a living Red Sea reef.',
                'Divers explore the coral-draped stern propeller at ~28 m, peer inside the cavernous upturned hull packed with glassfish, lionfish and giant morays, then finish with a colourful shallow safety stop on Beacon Rock reef.',
            ],
            'ar' => [
                'اصطدمت السفينة البريطانية SS Dunraven بشعاب بيكون روك في أبريل 1876 أثناء عودتها من الهند محملة بالتوابل والقطن، فتحول هيكلها عبر قرن ونصف إلى حديقة مرجانية نابضة بالحياة.',
                'يبدأ الغواصون عند المروحة المغطاة بالمرجان على عمق ~28 مترًا، مع استكشاف التجويف الواسع المليء بالأسماك الزجاجية وأسماك الأسد، ثم الصعود التدريجي على جدار بيكون روك المرجاني.',
            ],
        ],
        'specs' => [
            ['Supplement Price', 'سعر الإضافة', '€30 + package dive', '30€ + غطسة من الباقة'],
            ['Depth', 'العمق', '15–29 metres (Beacon Rock / Sha\'ab Mahmud)', '15–29 مترًا (منطقة شعب محمود)'],
            ['Recommended Level', 'المستوى الموصى به', 'Advanced Open Water or experienced Open Water + Deep/Wreck', 'Advanced Open Water أو غواص متمرس'],
            ['Booking Window', 'موعد الحجز', 'Book at least 72 hours ahead', 'الحجز قبل 72 ساعة على الأقل'],
        ],
        'steps' => [
            ['Morning Boat Crossing South', 'الإبحار جنوبًا نحو شعب محمود', 'Sail past Ras Mohammed to the sheltered southern lagoon of Beacon Rock.', 'الإبحار عبر رأس محمد نحو بحيرة بيكون روك بشعب محمود.'],
            ['Wreck Descent to Stern & Boiler', 'النزول إلى مؤخرة السفينة والمراجل', 'Inspect the coral-covered propeller, rudder and Victorian steam boilers.', 'مشاهدة المروحة الضخمة المغطاة بالمرجان اللين والمراجل البخارية التاريخية.'],
            ['Glassfish Hull & Reef Ascent', 'تجويف الأسماك الزجاجية والصعود المرجاني', 'Glide along the upturned hull before ascending onto Beacon Rock\'s shallow coral garden for Dive 1, followed by lunch and a second Ras Mohammed reef dive.', 'المرور بمحاذاة الهيكل المليء بالأسماك الزجاجية ثم الصعود على الشعاب الضحلة وتناول الغداء قبل الغطسة الثانية.'],
        ],
        'included' => [
            'en' => ['Sha\'ab Mahmud / Dunraven route logistics & PADI wreck guide', 'Sharm El Sheikh hotel transfers'],
            'ar' => ['تنظيم مسار شعب محمود ومرافقة مرشد PADI', 'انتقالات الفندق في شرم الشيخ'],
        ],
        'excluded' => [
            'en' => ['Base package dive, daily Boat (€10) & Marine Park (€5) fees, Torch (€6) & gear rental'],
            'ar' => ['غطسة الباقة الأساسية ورسوم القارب (10€) والمحمية (5€) والكشاف والمعدات الشخصية'],
        ],
        'ideal_for' => [
            'en' => ['Wreck lovers wanting a calmer crossing and lower €30 supplement than SS Thistlegorm', 'Students completing the 4-dive PADI Wreck Diver Specialty (€290)'],
            'ar' => ['عشاق السفن الغارقة الباحثين عن رحلة قريبة بتكلفة إضافة ميسرة (30€)', 'طلاب دورة تخصص غوص الحطام PADI Wreck Diver (290€)'],
        ],
        'schema_type' => 'TouristTrip',
        'compare_mode' => 'none',
        'review_idx' => 2,
        'related' => ['sup-thistlegorm', 'sup-millionhope', 'wreck', 'dive-6'],
        'faqs' => [
            ['Is SS Dunraven easier than SS Thistlegorm?', 'Yes — Beacon Rock is generally more sheltered from open Gulf of Suez swells than Sha\'ab Ali, and you ascend directly onto a sunny shallow coral reef for your safety stop.', 'هل غطسة دنرافين أسهل من ثيستلجورم؟', 'نعم، تتميز منطقة بيكون روك بحماية أكبر من الأمواج المفتوحة مقارنة بشعب علي، كما يتم الصعود التدريجي مباشرة على حديقة مرجانية ضحلة ومشمسة.'],
        ],
    ],

    'sup-millionhope' => [
        'h1' => [
            'en' => 'Million Hope Wreck Dive in Nabq, Sharm El Sheikh — Largest Local Wreck (+€30)',
            'ar' => 'غطسة حطام سفينة مليون هوب (Million Hope) في نبق، شرم الشيخ (+30€)',
        ],
        'meta_title' => [
            'en' => 'Million Hope Wreck Dive Sharm El Sheikh | +€30 | Sea College',
            'ar' => 'غوص حطام سفينة مليون هوب نبق شرم الشيخ | +30€ | سي كوليدج',
        ],
        'meta_desc' => [
            'en' => 'Dive the 174-metre Million Hope bulk carrier wreck in Nabq, Sharm El Sheikh (+€30 supplement, min. 4 divers, 0–22 m). Close to our Al Montazah base!',
            'ar' => 'اغطس في أضخم سفينة غارقة بشرم الشيخ Million Hope بطول 174 مترًا في نبق (+30€، حد أدنى 4 غواصين، عمق 0–22م) بالقرب من مركزنا بالمنتزه.',
        ],
        'aeo_answer' => [
            'en' => 'The Million Hope Wreck supplement at Sea College Dive Club costs €30 per certified diver on top of your dive package (minimum 4 divers, book 48 hours ahead). Stranded in 1996 at Nabq Protected Area just north of our Al Montazah resort, this massive 174-metre carrier sits in 0–22 metres of bright water — accessible even to Open Water Divers!',
            'ar' => 'تبلغ إضافة رحلة حطام سفينة Million Hope في سي كوليدج دايف كلوب 30€ للفرد فوق باقة الغوص (بحد أدنى 4 غواصين وحجز قبل 48 ساعة). جنحت هذه الناقلة العملاقة (بطول 174 مترًا) عام 1996 في محمية نبق شمال مركزنا بالمنتزه على عمق يتراوح بين السطح و22 مترًا، مما يجعلها مناسبة حتى لحاملي شهادة Open Water.',
        ],
        'overview' => [
            'en' => [
                'At over 174 metres long, the Million Hope dwarfs even the SS Thistlegorm in sheer scale — and because her superstructure rises into shallow sunlit water down to a 22-metre seabed, natural light illuminates enormous propellers, cranes, walkways and an adjacent crane barge.',
                'Located right in Nabq north of Al Montazah, transfer and transit times are short, making it a favourite wreck dive for photographers and Open Water / Advanced divers alike.',
            ],
            'ar' => [
                'بطول يتجاوز 174 مترًا، تُعد سفينة Million Hope أضخم حطام سفينة في شرم الشيخ على الإطلاق؛ وبفضل امتداد هيكلها من المياه الضحلة المشمسة حتى عمق 22 مترًا، تخترق أشعة الشمس الممرات والرافعات والمروحة العملاقة بوضوح مذهل.',
                'يقع الموقع في محمية نبق قريبًا جدًا من مقرنا في المنتزه، مما يوفر وقت الانتقال ويجعلها مثالية للتصوير الفوتوغرافي الواسع.',
            ],
        ],
        'specs' => [
            ['Supplement Price', 'سعر الإضافة', '€30 + 1 package dive', '30€ + غطسة من الباقة'],
            ['Wreck Size & Depth', 'الحجم والعمق', '174 m length · 0 to 22 metres depth', 'طول 174 مترًا · عمق من السطح حتى 22 مترًا'],
            ['Minimum Group', 'الحد الأدنى للمجموعة', 'Minimum 4 certified divers', '4 غواصين معتمدين كحد أدنى'],
            ['Booking Window', 'موعد الحجز', 'At least 48 hours ahead (sea/wind dependent)', 'قبل 48 ساعة على الأقل (حسب حالة البحر والرياح)'],
        ],
        'steps' => [
            ['Short Transfer North to Nabq', 'انتقال قصير شمالًا إلى محمية نبق', 'Quick ride from Al Montazah to the Nabq launch point when winds are favourable.', 'انطلاق سريع من المنتزه إلى منطقة نبق في الأيام ذات الطقس المناسب.'],
            ['Guided Tour of Stern, Decks & Crane Barge', 'جولة موجهة حول الهيكل وبارجة الرافعة', 'Swim alongside cathedral-scale hull plates, schools of sweetlips, batfish and nudibranchs.', 'السباحة بمحاذاة الصفائح العملاقة للسفينة ومشاهدة أسراب الأسماك المستوطنة للحطام.'],
            ['Return to Base', 'العودة إلى المركز', 'Back in time for lunch or an afternoon reef dive.', 'العودة المريحة للمركز أو الفندق مع إمكانية إضافة غطسة ثانية.'],
        ],
        'included' => [
            'en' => ['Nabq wreck transport/Zodiac logistics & PADI guide', 'Sharm hotel transfers'],
            'ar' => ['لوجستيات الانتقال لمحمية نبق ومرافقة مرشد PADI', 'انتقالات الفندق في شرم الشيخ'],
        ],
        'excluded' => [
            'en' => ['Base package dive, €5 Nabq Protected Area fee & personal scuba gear'],
            'ar' => ['غطسة الباقة الأساسية ورسوم محمية نبق (5€) والمعدات الشخصية'],
        ],
        'ideal_for' => [
            'en' => ['Open Water & Advanced divers wanting a giant shallow-to-medium depth wreck', 'Groups of 4+ buddies looking for an uncrowded Sharm wreck'],
            'ar' => ['حاملو Open Water وAdvanced الباحثون عن حطام عملاق بإضاءة طبيعية رائعة', 'مجموعات الأصدقاء (4 غواصين فأكثر) الراغبون في موقع فريد قليل الزحام'],
        ],
        'schema_type' => 'TouristTrip',
        'compare_mode' => 'none',
        'review_idx' => 2,
        'related' => ['sup-dunraven', 'sup-thistlegorm', 'sup-zodiac-early', 'wreck'],
        'faqs' => [
            ['Can Open Water Divers dive the Million Hope?', 'Yes — within your 18-metre certification limit you can explore vast sections of the decks, cranes and hull without deep penetration.', 'هل يمكن لحاملي Open Water الغوص في مليون هوب؟', 'نعم، بفضل وجود أجزاء واسعة من السفينة فوق عمق 18 مترًا يمكن لحاملي Open Water الاستمتاع بها بأمان تام.'],
            ['What if there are fewer than 4 divers in my party?', 'Send us a WhatsApp message with your dates — we regularly combine solo and couple divers onto scheduled 4+ diver Million Hope runs.', 'ماذا لو كنا أقل من 4 غواصين؟', 'راسلنا عبر واتساب بتواريخك، حيث نقوم بدمج الغواصين الأفراد والأزواج في المجموعات المجدولة للوصول للحد الأدنى (4 غواصين).'],
        ],
    ],

    'sup-zodiac' => [
        'h1' => [
            'en' => 'Zodiac RIB Speedboat Dive in Sharm El Sheikh — Straits of Tiran Drift (+€50)',
            'ar' => 'غطسة بقارب الزودياك السريع (Zodiac) في شرم الشيخ ومضيق تيران (+50€)',
        ],
        'meta_title' => [
            'en' => 'Zodiac Speedboat Scuba Dive Sharm El Sheikh | +€50 | Sea College',
            'ar' => 'غوص بقارب الزودياك السريع شرم الشيخ | +50€ | سي كوليدج',
        ],
        'meta_desc' => [
            'en' => 'Reach Tiran Island & outer drift reefs in minutes from Al Montazah by Zodiac RIB (+€50 supplement, min. 4 divers, book 48h ahead). Fast drop-offs & live pickups.',
            'ar' => 'انطلق خلال دقائق من المنتزه إلى شعاب مضيق تيران بقارب الزودياك السريع (+50€ إضافة، حد أدنى 4 غواصين، حجز قبل 48 ساعة).',
        ],
        'aeo_answer' => [
            'en' => 'The Dive by Zodiac supplement at Sea College Dive Club costs €50 per certified diver on top of your package dive (minimum 4 divers, book 48 hours ahead). Because our Al Montazah resort sits directly opposite Tiran Island, a fast RIB speedboat puts you onto Jackson, Woodhouse, Thomas or Gordon Reef in minutes with precision drift drop-offs and live surface pickups.',
            'ar' => 'تبلغ إضافة الغوص بقارب الزودياك السريع في سي كوليدج دايف كلوب 50€ للغواص المعتمد فوق غطسة من باقتك (بحد أدنى 4 غواصين وحجز قبل 48 ساعة). وبفضل موقع مركزنا بالمنتزه في مواجهة جزيرة تيران مباشرة، يوصلك الزودياك في دقائق معدودة إلى شعاب جاكسون وودهاوس وتوماس وجوردون لإنزال والتقاط انجرافي احترافي.',
        ],
        'overview' => [
            'en' => [
                'Big day-boats need fixed moorings and hours of cruising; a rigid-inflatable Zodiac skims straight across from Al Montazah to outer Tiran walls and canyons that large yachts cannot moor on.',
                'Back-roll together on the skipper\'s signal, drift effortlessly along pelagic-rich reef corners, deploy an SMB with your PADI guide and get picked up right where you surface.',
            ],
            'ar' => [
                'بينما تحتاج اليخوت الكبيرة لوقت إبحار طويل ونقاط ربط ثابتة، ينطلق قارب الزودياك المطاطي السريع من أمام المنتزه ليصل في دقائق إلى الزوايا الخارجية لشعاب تيران التي يصعب على القوارب الكبيرة الرسو عندها.',
                'استمتع بالقفزة الخلفية المتزامنة والانجراف السلس مع التيار لمشاهدة الأسماك الكبيرة بينما يلتقطك الكابتن مباشرة من نقطة صعودك.',
            ],
        ],
        'specs' => [
            ['Supplement Price', 'سعر الإضافة', '€50 + package dive', '50€ + غطسة من الباقة'],
            ['Minimum Group', 'الحد الأدنى للمجموعة', 'Minimum 4 certified divers', '4 غواصين معتمدين كحد أدنى'],
            ['Duration', 'المدة', 'Half day (fast transit, zero wasted hours)', 'نصف يوم (انتقال سريع بدون ساعات إبحار طويلة)'],
            ['Booking Window', 'موعد الحجز', 'Book at least 48 hours ahead', 'قبل 48 ساعة على الأقل'],
        ],
        'steps' => [
            ['Gear Up at Al Montazah & Negative/Back-Roll Briefing', 'التجهيز بالمنتزه وشرح القفزة الخلفية', 'Prepare your gear at base and review synchronized RIB back-roll entry and drift procedures.', 'تجهيز المعدات في المركز وشرح آلية القفزة الخلفية المتزامنة وإجراءات الغوص الانجرافي.'],
            ['10–15 Minute High-Speed Run to Tiran', 'انطلاق سريع في 10–15 دقيقة إلى تيران', 'Cross straight to Jackson, Thomas or Woodhouse Reef ahead of big day boats.', 'عبور سريع مباشر إلى شعاب تيران الشهيرة بعيدًا عن زحام اليخوت الكبيرة.'],
            ['Live Drift Dive & Instant Return', 'غطسة انجرافية وعودة سريعة', 'Ride clean currents past gorgonians, turtles and trevally, then zip back to base.', 'الاستمتاع بغطسة انجرافية غنية بالسلاحف والأسماك الكبيرة ثم العودة السريعة للمركز.'],
        ],
        'included' => [
            'en' => ['High-speed RIB boat, skipper & PADI drift guide', 'Sharm El Sheikh hotel transfers'],
            'ar' => ['قارب زودياك سريع مع كابتن ومرشد غوص PADI', 'انتقالات الفندق في شرم الشيخ'],
        ],
        'excluded' => [
            'en' => ['Base package dive, €5 Marine Park Fee & personal equipment rental'],
            'ar' => ['غطسة الباقة الأساسية ورسوم المحمية (5€) وتأجير المعدات الشخصية'],
        ],
        'ideal_for' => [
            'en' => ['Experienced drift divers wanting outer Tiran reef corners without crowds', 'Busy travellers wanting Tiran walls in a compact half-day window'],
            'ar' => ['محبو الغوص الانجرافي الراغبون في استكشاف الزوايا البعيدة لتيران بدون زحام', 'من يريد الغوص في مضيق تيران خلال نصف يوم فقط'],
        ],
        'schema_type' => 'TouristTrip',
        'compare_mode' => 'none',
        'review_idx' => 2,
        'related' => ['sup-zodiac-early', 'speed-2h', 'dive-6', 'aowd'],
        'faqs' => [
            ['Why is Al Montazah the best launch point in Sharm for Zodiac dives?', 'Al Montazah (Ras Nasrani) lies right across the narrowest channel to Tiran Island — cutting RIB ride time to a fraction of departures from southern Sharm ports.', 'لماذا يُعد موقعنا في المنتزه الأفضل لرحلات الزودياك إلى تيران؟', 'تقع منطقة المنتزه (رأس نصراني) في أقرب نقطة مواجهة مباشرة لجزيرة تيران، مما يقلص زمن الإبحار إلى دقائق معدودة مقارنة بالموانئ الجنوبية.'],
        ],
    ],

    'sup-zodiac-early' => [
        'h1' => [
            'en' => 'Early Morning Zodiac Dive — Hammerheads & Million Hope in Sharm (+€70)',
            'ar' => 'غطسة زودياك الفجر المبكرة — أسماك المطرقة أو مليون هوب شرم الشيخ (+70€)',
        ],
        'meta_title' => [
            'en' => 'Early Dawn Zodiac Dive Hammerheads Sharm | +€70 | Sea College',
            'ar' => 'غطسة زودياك الفجر أسماك المطرقة شرم الشيخ | +70€ | سي كوليدج',
        ],
        'meta_desc' => [
            'en' => 'Be the very first divers in the water on Jackson Reef (back-reef hammerhead zone) or Million Hope by dawn Zodiac RIB (+€70 supplement, min. 4 divers).',
            'ar' => 'كن أول الغواصين في الماء فجرًا خلف جاكسون ريف (منطقة قروش المطرقة) أو حطام مليون هوب بقارب الزودياك السريع (+70€، حد أدنى 4 غواصين).',
        ],
        'aeo_answer' => [
            'en' => 'The Early Zodiac Dive supplement at Sea College Dive Club costs €70 per certified diver on top of your package dive (minimum 4 divers, book 48 hours ahead). Launching at first light from Al Montazah puts your group into the blue behind Jackson Reef (seasonal scalloped hammerhead territory, June–September) or onto the Million Hope wreck hours before a single day-boat arrives.',
            'ar' => 'تبلغ إضافة غطسة الزودياك المبكرة فجرًا مع سي كوليدج دايف كلوب 70€ للغواص المعتمد فوق غطسة الباقة (بحد أدنى 4 غواصين وحجز قبل 48 ساعة). ينطلق القارب مع أول ضوء من المنتزه لتكونوا أول مجموعة في المياه الزرقاء خلف جاكسون ريف (منطقة أسماك القرش المطرقة صيفًا) أو على حطام مليون هوب قبل وصول أي قوارب يومية.',
        ],
        'overview' => [
            'en' => [
                'Ask any veteran Red Sea instructor when Tiran\'s big pelagics show up and the answer is always the same: dawn, before engine noise pushes sharks and eagle rays deeper.',
                'Departing right opposite Tiran from Al Montazah at sunrise, our RIB drops experienced divers into pristine blue water on the northern saddle of Jackson Reef or onto an empty Million Hope wreck.',
            ],
            'ar' => [
                'يعلم خبراء البحر الأحمر أن أفضل لحظة لرؤية الكائنات البحرية الكبيرة في مضيق تيران هي ساعة الفجر الهادئة قبل وصول ضجيج محركات القوارب اليومية.',
                'ننطلق مع شروق الشمس مباشرة من المنتزه لنضع الغواصين المتمرسين في المياه الزرقاء شمال جاكسون ريف أو على حطام مليون هوب الخالي تمامًا من الزحام.',
            ],
        ],
        'specs' => [
            ['Supplement Price', 'سعر الإضافة', '€70 + package dive', '70€ + غطسة من الباقة'],
            ['Minimum Level', 'المستوى المطلوب', 'Advanced Open Water + strong blue-water buoyancy', 'شهادة Advanced Open Water وتحكم ممتاز بالطفو في المياه المفتوحة'],
            ['Group Size', 'حجم المجموعة', 'Minimum 4 divers · small fast team', '4 غواصين كحد أدنى في مجموعة سريعة وصغيرة'],
            ['Booking Window', 'موعد الحجز', 'At least 48 hours ahead', 'قبل 48 ساعة على الأقل'],
        ],
        'steps' => [
            ['Sunrise Briefing & Gear Setup (~05:30)', 'التجهيز وشرح الفجر (~05:30)', 'Negative-entry, blue-water depth discipline and SMB safety briefing at base.', 'مراجعة النزول السريع والانضباط في العمق وإشارات العوامة السطحية.'],
            ['First-on-Reef Zodiac Drop', 'الإنزال الأول على الشعاب قبل الجميع', 'Drop onto north Jackson Reef blue water or Million Hope with zero other divers in sight.', 'النزول في المياه الزرقاء لجاكسون ريف أو مليون هوب قبل وصول أي قارب آخر.'],
            ['Back in Time for Hotel Breakfast', 'العودة المبكرة قبل انتهاء الإفطار', 'Zip back across the channel with the rest of your day still ahead of you.', 'العودة السريعة للمنتزه والفندق مع بقاء يومك كاملًا مفتوحًا أمامك.'],
        ],
        'included' => [
            'en' => ['Dawn RIB charter, skipper & senior PADI pelagic guide', 'Hotel transfers in Sharm El Sheikh'],
            'ar' => ['قارب زودياك فجري مع كابتن وكبير مرشدي PADI', 'انتقالات الفندق في شرم الشيخ'],
        ],
        'excluded' => [
            'en' => ['Base package dive, €5 Marine Park Fee, Dive Computer (€10/day mandatory for blue water) & gear rental'],
            'ar' => ['غطسة الباقة الأساسية ورسوم المحمية (5€) وكمبيوتر الغوص والمعدات الشخصية'],
        ],
        'ideal_for' => [
            'en' => ['Advanced & Deep-certified divers visiting June–September for seasonal hammerhead patrols', 'Photographers wanting empty-wreck light on Million Hope'],
            'ar' => ['الغواصون المتقدمون الباحثون عن أسماك المطرقة (خصوصًا من يونيو إلى سبتمبر) والجاك والتونة', 'المصورون الباحثون عن حطام مليون هوب في هدوء الفجر التام'],
        ],
        'schema_type' => 'TouristTrip',
        'compare_mode' => 'none',
        'review_idx' => 2,
        'related' => ['sup-zodiac', 'sup-millionhope', 'deep', 'eq-computer'],
        'faqs' => [
            ['Are hammerhead shark sightings guaranteed?', 'Like all wild pelagic marine life, sightings are never guaranteed, though summer dawn blue-water dives behind Jackson Reef offer Sharm El Sheikh\'s highest probability alongside jacks, trevally, tuna and turtles.', 'هل رؤية أسماك القرش المطرقة مضمونة 100%؟', 'مثل سائر الكائنات البحرية البرية لا يمكن ضمان المشاهدة، لكن غطسة الفجر خلف جاكسون ريف صيفًا تمنح أعلى فرصة في شرم الشيخ بجانب أسراب الجاك والتونة والسلاحف.'],
        ],
    ],

    'sup-dahab' => [
        'h1' => [
            'en' => 'Dahab Dive Day Trip from Sharm El Sheikh — Blue Hole & Canyon (+€115)',
            'ar' => 'رحلة غوص دهب ليوم كامل من شرم الشيخ — البلو هول والكانيون (+115€)',
        ],
        'meta_title' => [
            'en' => 'Dahab Blue Hole & Canyon Dive Trip from Sharm | +€115 | Sea College',
            'ar' => 'رحلة غوص دهب البلو هول والكانيون من شرم الشيخ | +115€ | سي كوليدج',
        ],
        'meta_desc' => [
            'en' => 'Day trip from Sharm El Sheikh to dive Dahab\'s world-famous Blue Hole (Bells to Blue Hole) & Canyon (+€115 supplement, min. 4 divers, book 48h ahead).',
            'ar' => 'رحلة يوم كامل من شرم الشيخ للغوص في أشهر مواقع دهب البلو هول والكانيون (+115€ إضافة، حد أدنى 4 غواصين، حجز قبل 48 ساعة).',
        ],
        'aeo_answer' => [
            'en' => 'The Dahab Dive Trip Supplement at Sea College Dive Club costs €115 per certified diver on top of 2 dives from your package (minimum 4 divers, book 48 hours ahead). This full-day Sinai excursion includes air-conditioned highway transfers (~1 hour north of Sharm), equipment/cylinder transport, Bedouin seaside rest-camp facilities and guided dives at Dahab\'s legendary Canyon and Blue Hole / Bells within recreational depth limits.',
            'ar' => 'تبلغ إضافة رحلة غوص دهب ليوم كامل مع سي كوليدج دايف كلوب 115€ للغواص المعتمد فوق غطستين من باقتك (بحد أدنى 4 غواصين وحجز قبل 48 ساعة). تشمل الرحلة الانتقال البري المكيف (~ساعة شمال شرم الشيخ)، نقل الأسطوانات والمعدات، استراحة الشاطئ البدوية، وغطستين موجهتين في أشهر مواقع دهب مثل الكانيون والبلو هول ضمن حدود الغوص الترفيهي الآمن.',
        ],
        'overview' => [
            'en' => [
                'Just 90 km north of Sharm El Sheikh along a dramatic Sinai mountain-and-desert highway lies Dahab — home to two of the Red Sea\'s most iconic shore formations: the volcanic fissure of The Canyon and the cobalt sinkhole of the Blue Hole (dived safely from El Bells along the outer coral wall and across the 7 m coral saddle).',
                'Between dives you relax on seaside cushions at a beachfront Bedouin café overlooking the Gulf of Aqaba and the mountains of Saudi Arabia.',
            ],
            'ar' => [
                'على بُعد نحو ساعة شمال شرم الشيخ عبر طريق سيناء الجبلي الخلاب تقع مدينة دهب التي تضم اثنين من أشهر معالم الغوص عالميًا: شق الكانيون البركاني الصخري والثقب الأزرق الشهير (Blue Hole) الذي نغوصه بأمان عبر مسار Bells والجدار المرجاني الخارجي.',
                'وبين الغطستين تستمتع بالاسترخاء في الكافيهات البدوية المطلة مباشرة على خليج العقبة وجبال الحجاز المقابلة.',
            ],
        ],
        'specs' => [
            ['Supplement Price', 'سعر الإضافة', '€115 + 2 dives from your certified package', '115€ + غطستان من باقتك المعتمدة'],
            ['Duration', 'المدة', 'Full day (~07:30–17:30 return from Sharm)', 'يوم كامل (~07:30 إلى 17:30 ذهابًا وعودة)'],
            ['Minimum Group', 'الحد الأدنى للمجموعة', 'Minimum 4 certified divers', '4 غواصين معتمدين كحد أدنى'],
            ['Recommended Level', 'المستوى الموصى به', 'Advanced Open Water (Canyon/Bells recreational profiles to 30 m; Open Water options at Lighthouse/Islands)', 'Advanced Open Water لمسار الكانيون والبلو هول (حتى 30م)'],
            ['Booking Window', 'موعد الحجز', 'At least 48 hours ahead (bring passport for checkpoint)', 'قبل 48 ساعة على الأقل (مع إحضار جواز السفر)'],
        ],
        'steps' => [
            ['Scenic Sinai Drive to Dahab (~07:30)', 'الانطلاق البري عبر جبال سيناء (~07:30)', 'Air-conditioned minivan collects you and your packed scuba gear for the 1-hour drive north.', 'انطلاق بسيارة مكيفة من فندقك في شرم الشيخ مع نقل كافة معداتك وأسطواناتك شمالًا إلى دهب.'],
            ['Dive 1: The Canyon or Islands', 'الغطسة الأولى: الكانيون أو الجزر المرجانية', 'Enter through a calm sandy lagoon and explore dramatic volcanic rock formations, glassfish caverns and coral domes.', 'النزول عبر البحيرة الرملية لاستكشاف التكوينات البركانية المذهلة وأسراب الأسماك الزجاجية.'],
            ['Bedouin Seafront Relaxation', 'استراحة الشاطئ على الطراز البدوي', 'Unwind by the water\'s edge during a generous surface interval before heading 15 minutes north to the Blue Hole.', 'استراحة مريحة أمام البحر مباشرة قبل التوجه لموقع البلو هول.'],
            ['Dive 2: El Bells to the Blue Hole Saddle & Return', 'الغطسة الثانية: مسار Bells إلى سرج البلو هول والعودة', 'Descend the vertical chimney at El Bells (within 26–30 m recreational max), drift the rich outer coral wall and cross the sunlit 7 m saddle into the Blue Hole before returning to Sharm.', 'النزول الموجه عبر Bells بمحاذاة الجدار المرجاني الخارجي وعبور السرج المرجاني المشمس (7م) إلى داخل البلو هول ثم العودة لشرم الشيخ.'],
        ],
        'included' => [
            'en' => ['Return air-conditioned highway transfers Sharm ↔ Dahab', 'Equipment & cylinder transport + PADI guide', 'Dahab seaside dive camp facilities'],
            'ar' => ['الانتقالات البرية المكيفة ذهابًا وإيابًا بين شرم الشيخ ودهب', 'نقل المعدات والأسطوانات ومرافقة مرشد PADI', 'استخدام مرافق الاستراحة الشاطئية في دهب'],
        ],
        'excluded' => [
            'en' => ['2 base dives from your certified package', 'Blue Hole / Abu Galum National Park entry ticket (~$10 USD payable at gate), lunch & personal gear rental'],
            'ar' => ['غطستا الباقة الأساسيتان', 'تذكرة دخول محمية البلو هول الحكومية والغداء وتأجير المعدات الشخصية'],
        ],
        'ideal_for' => [
            'en' => ['Advanced divers wanting to experience Sinai\'s desert-meets-sea shore diving culture', 'Groups booking a 6-, 8- or 10-dive week wanting 1 unforgettable land-and-sea excursion'],
            'ar' => ['حاملو شهادة Advanced الراغبون في زيارة معلمي البلو هول والكانيون الشهيرين عالميًا', 'حاجزو باقات الـ6 أو 10 غطسات الباحثون عن يوم استكشافي مختلف يجمع بين الجبل والبحر'],
        ],
        'schema_type' => 'TouristTrip',
        'compare_mode' => 'none',
        'review_idx' => 2,
        'related' => ['dive-6', 'dive-10', 'aowd', 'deep'],
        'faqs' => [
            ['Is diving the Dahab Blue Hole safe for recreational divers?', 'Yes — recreational divers strictly stay within 18–30 m along the vibrant outer El Bells wall and finish across the sunlit 7 m coral saddle; deep arch technical diving is strictly off-limits on recreational trips.', 'هل الغوص في البلو هول بدهب آمن للغواصين الترفيهيين؟', 'نعم تمامًا؛ يلتزم مسار الغوص الترفيهي بأعماق آمنة (18–30 مترًا كحد أقصى) على الجدار المرجاني الخارجي الغني بالحياة والعبور عبر السرج الضحل (7 أمتار) دون أي غوص تقني عميق.'],
            ['Do I need my passport for the Dahab trip?', 'Yes, bring your physical passport (the free Sinai-Only airport stamp is valid for Dahab road checkpoints).', 'هل أحتاج لإحضار جواز السفر لرحلة دهب؟', 'نعم، يرجى إحضار جواز السفر الأصلي لنقاط التفتيش على الطريق (وختم سيناء المجاني كافٍ تمامًا لدهب).'],
        ],
    ],
];
