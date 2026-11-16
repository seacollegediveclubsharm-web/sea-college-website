<?php
/**
 * Sea College Dive Club — Service & price catalog.
 * Source: Sea_College_Price_List-Updated.xlsx (+ Sea_College_Courses_offers.docx for
 * courses missing in the sheet). Prices in EUR per person unless 'unit' says otherwise.
 * null price = "on request". Edit here only — prices.php, services.php, schema and
 * the booking cart all read from this file.
 */
declare(strict_types=1);

return [
    /* ============================================================ */
    'dive-packages' => [
        'label' => ['en' => 'Dive Packages', 'ar' => 'باقات الغوص'],
        'icon'  => '🤿',
        'notes' => [
            'en' => [
                'Diving packages are personal (price per person) and cannot be shared between divers.',
                'Included: 12L air tank, weight belt & weights, dive guide, hotel transfers in Sharm El Sheikh, drinks on half-day trips; lunch & drinks on full-day trips.',
                'Not included: Boat Trip Supplement (€10/day), Marine Park Fee (€5/day, all areas except House Reef), Nitrox, insurance, equipment rental, night/early dives and supplements.',
                'Private guide is mandatory for children aged 10–11 and for PADI Scuba Divers (12 m limit).',
                'Online booking up to 72 hours before the activity (48 h for Million Hope, Zodiac and Dahab).',
            ],
            'ar' => [
                'باقات الغوص شخصية (السعر للفرد) ولا يمكن مشاركتها بين أكثر من غواص.',
                'تشمل: أسطوانة هواء 12 لتر، حزام وأوزان، مرشد غوص، الانتقالات من وإلى الفندق في شرم الشيخ، ومشروبات في الرحلات نصف اليوم وغداء ومشروبات في رحلات اليوم الكامل.',
                'لا تشمل: رسوم القارب (10€ يوميًا)، رسوم المحمية (5€ يوميًا)، النيتروكس، التأمين، تأجير المعدات، الغطسات الليلية أو المبكرة والإضافات.',
                'المرشد الخاص إلزامي للأطفال من 10 إلى 11 سنة ولحاملي شهادة PADI Scuba Diver (حد 12 مترًا).',
                'الحجز أونلاين متاح حتى 72 ساعة قبل النشاط (48 ساعة لمليون هوب والزودياك ودهب).',
            ],
        ],
        'groups' => [
            [
                'title' => ['en' => 'Diving for Beginners (Intro Dives)', 'ar' => 'غوص للمبتدئين (غطسات تجريبية)'],
                'items' => [
                    ['id' => 'dsd-quick-reef', 'name' => 'Quick Discover Scuba Diving — House Reef', 'desc' => 'Briefing + 1 open water shore dive (10 min). Equipment included.', 'price' => 30, 'popular' => true, 'img' => 'sea-college-dive-club-sharm-el-sheikh-pool-training-scuba-instructor.jpg'],
                    ['id' => 'dsd-2-reef', 'name' => 'Discover Scuba Diving (2 Intro Dives) — House Reef', 'desc' => 'Briefing + 2 open water shore dives (15 min each). Equipment included.', 'price' => 75, 'img' => 'sea-college-dive-club-sharm-el-sheikh-pool-training-scuba-instructor.jpg'],
                    ['id' => 'dsd-quick-boat', 'name' => 'Quick Discover Scuba Diving — By Boat', 'desc' => 'Briefing + 1 boat dive (10 min). Food, soft drinks, transfers, park fees & equipment included.', 'price' => 70, 'duration' => '8h', 'img' => 'sea-college-dive-club-sharm-el-sheikh-discover-scuba-diving-intro-dive-red-sea.jpg'],
                    ['id' => 'dsd-2-boat', 'name' => 'Discover Scuba Diving (2 Intro Dives) — By Boat', 'desc' => 'Briefing + 2 boat dives (15 min each). Food, soft drinks, transfers, park fees & equipment included.', 'price' => 100, 'duration' => '8h', 'popular' => true, 'img' => 'sea-college-dive-club-sharm-el-sheikh-discover-scuba-diving-intro-dive-red-sea.jpg'],
                ],
            ],
            [
                'title' => ['en' => 'Refresh Dives', 'ar' => 'غطسات تنشيطية'],
                'items' => [
                    ['id' => 'check-dive', 'name' => 'Check Dive', 'desc' => 'Mandatory if you have not dived for more than 6 months. Briefing + confined/shore dive.', 'price' => 25, 'img' => 'sea-college-dive-club-sharm-el-sheikh-discover-scuba-diving-happy-divers.jpg'],
                    ['id' => 'scuba-review', 'name' => 'Scuba Review', 'desc' => 'Mandatory if you have not dived for more than 1 year. Confined + open water shore dive.', 'price' => 50, 'img' => 'sea-college-dive-club-sharm-el-sheikh-discover-scuba-diving-happy-divers.jpg'],
                ],
            ],
            [
                'title' => ['en' => 'Certified Dive Packages — by Boat or Shore', 'ar' => 'باقات الغوص للغواصين المعتمدين — بالقارب أو من الشاطئ'],
                'items' => [
                    ['id' => 'dive-1', 'name' => 'Single Shore Dive', 'desc' => 'Guide, 12L tank and weights.', 'price' => 45, 'img' => 'sea-college-dive-club-sharm-el-sheikh-group-boat-dive-red-sea-divers.jpg'],
                    ['id' => 'dive-2', 'name' => '2 Dives by Boat or Shore', 'desc' => 'Guide, 12L tank and weights. + boat & park fee on boat days.', 'price' => 85, 'popular' => true, 'img' => 'sea-college-dive-club-sharm-el-sheikh-group-boat-dive-red-sea-divers.jpg'],
                    ['id' => 'dive-3', 'name' => '3 Dives by Boat or Shore', 'desc' => 'Guide, 12L tank and weights.', 'price' => 105, 'img' => 'sea-college-dive-club-sharm-el-sheikh-group-boat-dive-red-sea-divers.jpg'],
                    ['id' => 'dive-4', 'name' => '4 Dives by Boat or Shore', 'desc' => 'Guide, 12L tank and weights.', 'price' => 140, 'img' => 'sea-college-dive-club-sharm-el-sheikh-group-boat-dive-red-sea-divers.jpg'],
                    ['id' => 'dive-5', 'name' => '5 Dives by Boat or Shore', 'desc' => 'Guide, 12L tank and weights.', 'price' => 175, 'img' => 'sea-college-dive-club-sharm-el-sheikh-group-boat-dive-red-sea-divers.jpg'],
                    ['id' => 'dive-6', 'name' => '6 Dives by Boat or Shore', 'desc' => 'Guide, 12L tank and weights.', 'price' => 210, 'img' => 'sea-college-dive-club-sharm-el-sheikh-dive-boat-briefing-instructor.jpg'],
                    ['id' => 'dive-7', 'name' => '7 Dives by Boat or Shore', 'desc' => 'Guide, 12L tank and weights.', 'price' => 245, 'img' => 'sea-college-dive-club-sharm-el-sheikh-dive-boat-briefing-instructor.jpg'],
                    ['id' => 'dive-8', 'name' => '8 Dives by Boat or Shore', 'desc' => 'Guide, 12L tank and weights.', 'price' => 280, 'img' => 'sea-college-dive-club-sharm-el-sheikh-dive-boat-briefing-instructor.jpg'],
                    ['id' => 'dive-9', 'name' => '9 Dives by Boat or Shore', 'desc' => 'Guide, 12L tank and weights.', 'price' => 315, 'img' => 'sea-college-dive-club-sharm-el-sheikh-dive-boat-briefing-instructor.jpg'],
                    ['id' => 'dive-10', 'name' => '10 Dives by Boat or Shore', 'desc' => 'Guide, 12L tank and weights.', 'price' => 350, 'img' => 'sea-college-dive-club-sharm-el-sheikh-dive-boat-briefing-instructor.jpg'],
                    ['id' => 'dive-extra', 'name' => 'Each Additional Dive after the 10th', 'desc' => 'Per dive.', 'price' => 30, 'img' => 'sea-college-dive-club-sharm-el-sheikh-dive-boat-briefing-instructor.jpg'],
                ],
            ],
            [
                'title' => ['en' => 'Supplements (on top of a dive package)', 'ar' => 'إضافات (فوق باقة الغوص)'],
                'items' => [
                    ['id' => 'sup-night', 'name' => 'Night & Early Morning Dive', 'desc' => 'Supplement on 1 dive of your package.', 'price' => 30, 'img' => 'sea-college-dive-club-sharm-el-sheikh-dive-boat-scuba-equipment-tanks.jpg'],
                    ['id' => 'sup-thistlegorm', 'name' => 'SS Thistlegorm Wreck', 'desc' => 'Min. Advanced Open Water + 20 logged dives. Book 72 h ahead.', 'price' => 150, 'popular' => true, 'img' => 'sea-college-dive-club-sharm-el-sheikh-ss-thistlegorm-wreck-dive.jpg'],
                    ['id' => 'sup-dunraven', 'name' => 'SS Dunraven Wreck', 'desc' => '1 wreck dive. Book 72 h ahead.', 'price' => 30, 'img' => 'sea-college-dive-club-sharm-el-sheikh-ss-thistlegorm-wreck-dive.jpg'],
                    ['id' => 'sup-millionhope', 'name' => 'Million Hope Wreck', 'desc' => '1 wreck dive, min. 4 divers. Book 48 h ahead.', 'price' => 30, 'img' => 'sea-college-dive-club-sharm-el-sheikh-ss-thistlegorm-wreck-dive.jpg'],
                    ['id' => 'sup-zodiac', 'name' => 'Dive by Zodiac', 'desc' => 'Min. 4 divers. Book 48 h ahead.', 'price' => 50, 'img' => 'sea-college-dive-club-sharm-el-sheikh-dive-boat-scuba-equipment-tanks.jpg'],
                    ['id' => 'sup-zodiac-early', 'name' => 'Early Zodiac Dive (Hammerheads / Million Hope)', 'desc' => 'Min. 4 divers. Book 48 h ahead.', 'price' => 70, 'img' => 'sea-college-dive-club-sharm-el-sheikh-dive-boat-scuba-equipment-tanks.jpg'],
                    ['id' => 'sup-dahab', 'name' => 'Dahab Dive Trip Supplement', 'desc' => 'Min. 4 divers. Book 48 h ahead.', 'price' => 115, 'img' => 'sea-college-dive-club-sharm-el-sheikh-dive-boat-scuba-equipment-tanks.jpg'],
                ],
            ],
            [
                'title' => ['en' => 'Daily Fees & Extras', 'ar' => 'رسوم يومية وإضافات'],
                'items' => [
                    ['id' => 'fee-boat', 'name' => 'Boat Trip Supplement', 'desc' => 'Per person, per boat day.', 'price' => 10, 'unit' => 'day', 'img' => 'sea-college-dive-club-sharm-el-sheikh-dive-boat-scuba-equipment-tanks.jpg'],
                    ['id' => 'fee-park', 'name' => 'Marine Park Fee', 'desc' => 'Per person, per day (all Sharm areas except House Reef).', 'price' => 5, 'unit' => 'day', 'img' => 'sea-college-dive-club-sharm-el-sheikh-dive-boat-scuba-equipment-tanks.jpg'],
                    ['id' => 'private-guide', 'name' => 'Private Guide', 'desc' => 'Per day.', 'price' => 75, 'unit' => 'day', 'img' => 'sea-college-dive-club-sharm-el-sheikh-dive-boat-briefing-instructor.jpg'],
                ],
            ],
            [
                'title' => ['en' => 'Equipment Rental (per day)', 'ar' => 'تأجير المعدات (لليوم)'],
                'items' => [
                    ['id' => 'eq-full', 'name' => 'Full Equipment Set', 'desc' => 'BCD, regulator, wetsuit, mask & fins for 2–3 dives/day (no torch/computer).', 'price' => 25, 'unit' => 'day'],
                    ['id' => 'eq-mask', 'name' => 'Mask', 'desc' => '', 'price' => 4, 'unit' => 'day'],
                    ['id' => 'eq-fins', 'name' => 'Fins', 'desc' => '', 'price' => 4, 'unit' => 'day'],
                    ['id' => 'eq-bcd', 'name' => 'BCD', 'desc' => '', 'price' => 10, 'unit' => 'day'],
                    ['id' => 'eq-reg', 'name' => 'Regulator', 'desc' => '', 'price' => 13, 'unit' => 'day'],
                    ['id' => 'eq-wetsuit', 'name' => 'Wetsuit', 'desc' => '', 'price' => 10, 'unit' => 'day'],
                    ['id' => 'eq-boots', 'name' => 'Boots', 'desc' => '', 'price' => 5, 'unit' => 'day'],
                    ['id' => 'eq-computer', 'name' => 'Dive Computer', 'desc' => '', 'price' => 10, 'unit' => 'day'],
                    ['id' => 'eq-torch', 'name' => 'Underwater Torch', 'desc' => '', 'price' => 6, 'unit' => 'day'],
                    ['id' => 'eq-15air', 'name' => '15L Tank — Air', 'desc' => '', 'price' => 6, 'unit' => 'day'],
                    ['id' => 'eq-15nx', 'name' => '15L Tank — Nitrox', 'desc' => 'Certified Nitrox divers.', 'price' => 10, 'unit' => 'day'],
                    ['id' => 'eq-12nx', 'name' => '12L Tank — Nitrox', 'desc' => 'Certified Nitrox divers.', 'price' => 8, 'unit' => 'day'],
                    ['id' => 'eq-sm-tank', 'name' => '12L Tank — Air Sidemount', 'desc' => '', 'price' => 5, 'unit' => 'day'],
                    ['id' => 'eq-sm-full', 'name' => 'Full Sidemount Equipment', 'desc' => '', 'price' => 50, 'unit' => 'day'],
                ],
            ],
            [
                'title' => ['en' => 'Diving Insurance', 'ar' => 'تأمين الغوص'],
                'items' => [
                    ['id' => 'ins-3', 'name' => 'Diving Insurance — 3 days', 'desc' => 'Price confirmed at booking.', 'price' => null],
                    ['id' => 'ins-7', 'name' => 'Diving Insurance — 7 days', 'desc' => 'Price confirmed at booking.', 'price' => null],
                    ['id' => 'ins-10', 'name' => 'Diving Insurance — 10 days', 'desc' => 'Price confirmed at booking.', 'price' => null],
                    ['id' => 'ins-14', 'name' => 'Diving Insurance — 14 days', 'desc' => 'Price confirmed at booking.', 'price' => null],
                ],
            ],
        ],
    ],

    /* ============================================================ */
    'padi-courses' => [
        'label' => ['en' => 'PADI Courses', 'ar' => 'دورات PADI'],
        'icon'  => '🎓',
        'notes' => [
            'en' => [
                'Equipment is included in all course prices.',
                'PADI manual & certification fee (€85 per course) not included — except Discover Scuba Diving.',
                'Boat & marine park supplements for trips are not included in the course price.',
                'Online booking up to 72 hours before the course starts.',
            ],
            'ar' => [
                'المعدات مشمولة في جميع أسعار الدورات.',
                'رسوم كتاب وشهادة PADI (85€ لكل دورة) غير مشمولة — باستثناء Discover Scuba Diving.',
                'رسوم القارب والمحمية للرحلات غير مشمولة في سعر الدورة.',
                'الحجز أونلاين متاح حتى 72 ساعة قبل بدء الدورة.',
            ],
        ],
        'groups' => [
            [
                'title' => ['en' => 'Courses for Children', 'ar' => 'دورات الأطفال'],
                'items' => [
                    ['id' => 'bubblemaker', 'name' => 'PADI Bubblemaker', 'desc' => 'Age 8+. 2–3 hours in the pool (under 2 m) with a PADI Instructor.', 'price' => 70, 'img' => 'courses/01_padi_bubblemaker.jpg', 'duration' => '2–3h', 'popular' => true],
                    ['id' => 'seal-team', 'name' => 'PADI Seal Team', 'desc' => 'Age 8+. 1 day of pool AquaMissions 1–5.', 'price' => 170, 'img' => 'courses/02_padi_seal_team.jpg', 'duration' => '1 day'],
                ],
            ],
            [
                'title' => ['en' => 'Courses for Beginners', 'ar' => 'دورات للمبتدئين'],
                'items' => [
                    ['id' => 'dsd', 'name' => 'PADI Discover Scuba Diving', 'desc' => 'Age 10+. Pool skills + supervised open water dive. Manual & certificate included.', 'price' => 85, 'img' => 'courses/03_padi_discover_scuba_diving.jpg', 'duration' => '1 day'],
                    ['id' => 'scuba-diver', 'name' => 'PADI Scuba Diver', 'desc' => 'Age 10+. 3 pool + 2 open water dives; dive to 12 m with a PADI Pro.', 'price' => 250, 'img' => 'courses/04_padi_scuba_diver.jpg', 'duration' => '2 days'],
                    ['id' => 'upgrade-owd', 'name' => 'Upgrade PADI Scuba Diver → Open Water', 'desc' => 'Complete the remaining Open Water training.', 'price' => 100],
                    ['id' => 'owd', 'name' => 'PADI Open Water Diver', 'desc' => 'Age 10+. The world\'s most popular scuba certification: 5 pool + 4 open water dives, up to 18 m.', 'price' => 380, 'img' => 'courses/05_padi_open_water_diver.jpg', 'duration' => '3–3.5 days', 'popular' => true],
                ],
            ],
            [
                'title' => ['en' => 'Advanced & Rescue', 'ar' => 'دورات متقدمة والإنقاذ'],
                'items' => [
                    ['id' => 'aowd', 'name' => 'PADI Advanced Open Water Diver', 'desc' => 'Age 12+. 5 Adventure Dives incl. Deep & Navigation, up to 30 m.', 'price' => 280, 'img' => 'courses/06_padi_advanced_open_water_diver.jpg', 'duration' => '2.5–3 days', 'popular' => true],
                    ['id' => 'aowd-green', 'name' => 'Advanced Open Water "Green" (AOWD + Nitrox)', 'desc' => 'Advanced course combined with Enriched Air Nitrox.', 'price' => 380],
                    ['id' => 'efr', 'name' => 'Emergency First Response (EFR)', 'desc' => 'CPR, first aid, AED & care for children. Open to everyone.', 'price' => 143, 'img' => 'courses/08_emergency_first_response.jpg', 'duration' => '1 day'],
                    ['id' => 'rescue', 'name' => 'PADI Rescue Diver', 'desc' => 'Age 12+. Self-rescue, diver stress and emergency management.', 'price' => 394, 'img' => 'courses/07_padi_rescue_diver.jpg', 'duration' => '3–4 days'],
                ],
            ],
            [
                'title' => ['en' => 'Specialty Courses', 'ar' => 'دورات التخصص'],
                'items' => [
                    ['id' => 'nitrox', 'name' => 'PADI Enriched Air Nitrox', 'desc' => 'Age 12+. Longer no-stop times; analyse tanks and set your computer.', 'price' => 145, 'img' => 'courses/10_padi_enriched_air_nitrox.jpg', 'duration' => '1 day'],
                    ['id' => 'spec-2', 'name' => '2-Dive Specialty', 'desc' => 'e.g. Peak Performance Buoyancy, Digital Underwater Photography.', 'price' => 145],
                    ['id' => 'spec-3', 'name' => '3-Dive Specialty / Underwater Navigator', 'desc' => '', 'price' => 250],
                    ['id' => 'deep', 'name' => 'PADI Deep Diver', 'desc' => 'Age 15+. 4 deep dives down to 40 m.', 'price' => 325, 'img' => 'courses/11_padi_deep_diver.jpg', 'duration' => '2 days'],
                    ['id' => 'wreck', 'name' => 'PADI Wreck Diver', 'desc' => 'Age 15+. 4 wreck dives on famous Red Sea wrecks.', 'price' => 325, 'img' => 'courses/12_padi_wreck_diver.jpg', 'duration' => '2 days'],
                    ['id' => 'night', 'name' => 'PADI Night Diver', 'desc' => 'Age 12+. 3 night dives.', 'price' => 265, 'img' => 'courses/13_padi_night_diver.jpg'],
                    ['id' => 'sidemount', 'name' => 'PADI Sidemount Diver', 'desc' => 'Age 15+. 1 confined + 3 open water dives.', 'price' => 350, 'img' => 'courses/14_padi_sidemount_diver.jpg', 'duration' => '2–3 days'],
                    ['id' => 'adaptive', 'name' => 'PADI Adaptive Support Diver', 'desc' => 'Age 15+. Support buddies with physical or mental challenges.', 'price' => 120, 'img' => 'courses/15_padi_adaptive_support_diver.jpg', 'duration' => 'Half day'],
                ],
            ],
            [
                'title' => ['en' => 'Go Pro', 'ar' => 'الاحتراف'],
                'items' => [
                    ['id' => 'divemaster', 'name' => 'PADI Divemaster Program', 'desc' => 'Age 18+, 40 logged dives, AOWD + Rescue + EFR. 4–6 weeks incl. transfers, breakfast & lunch, equipment, insurance and mentoring.', 'price' => 850, 'img' => 'courses/09_padi_divemaster_program.jpg', 'duration' => '4–6 weeks'],
                ],
            ],
        ],
    ],

    /* ============================================================ */
    'offers' => [
        'label' => ['en' => 'Course Bundles', 'ar' => 'عروض الدورات'],
        'icon'  => '🏷️',
        'notes' => [
            'en' => [
                'Bundles include tax, all listed courses, equipment rental and dive insurance.',
                'PADI manual & certification fee (€85 per course) not included.',
            ],
            'ar' => [
                'العروض تشمل الضريبة وكل الدورات المذكورة وتأجير المعدات وتأمين الغوص.',
                'رسوم كتاب وشهادة PADI (85€ لكل دورة) غير مشمولة.',
            ],
        ],
        'groups' => [
            [
                'title' => ['en' => 'Save with a bundle', 'ar' => 'وفّر مع العروض'],
                'items' => [
                    ['id' => 'bundle-stars', 'name' => 'Stars — Open Water + Nitrox', 'desc' => 'Save €50.', 'price' => 500, 'save' => 50, 'img' => 'sea-college-dive-club-sharm-el-sheikh-padi-diving-course-students-underwater.jpg'],
                    ['id' => 'bundle-super', 'name' => 'Super — Advanced Open Water + Nitrox', 'desc' => 'Save €75.', 'price' => 400, 'save' => 75, 'img' => 'sea-college-dive-club-sharm-el-sheikh-padi-diving-course-students-underwater.jpg'],
                    ['id' => 'bundle-mega', 'name' => 'Mega — Open Water + Advanced Open Water', 'desc' => 'Save €90.', 'price' => 600, 'save' => 90, 'popular' => true, 'img' => 'sea-college-dive-club-sharm-el-sheikh-padi-diving-course-students-underwater.jpg'],
                    ['id' => 'bundle-heros', 'name' => 'Heros — Open Water + Advanced + Nitrox', 'desc' => 'Save €120.', 'price' => 750, 'save' => 120, 'img' => 'sea-college-dive-club-sharm-el-sheikh-padi-diving-course-students-underwater.jpg'],
                ],
            ],
        ],
    ],

    /* ============================================================ */
    'snorkeling' => [
        'label' => ['en' => 'Snorkeling & Boats', 'ar' => 'السنوركل والقوارب'],
        'icon'  => '🚤',
        'notes' => [
            'en' => [
                'Snorkeling trips include mask, life jacket, transfers and marine park fees; full-day boat trips include lunch & soft drinks.',
                'Activities booked on the spot are charged at the standard price.',
                'Online booking up to 72 hours before the activity.',
            ],
            'ar' => [
                'رحلات السنوركل تشمل القناع وسترة النجاة والانتقالات ورسوم المحمية؛ رحلات القارب ليوم كامل تشمل الغداء والمشروبات.',
                'الأنشطة المحجوزة في الموقع تُحسب بالسعر القياسي.',
                'الحجز أونلاين متاح حتى 72 ساعة قبل النشاط.',
            ],
        ],
        'groups' => [
            [
                'title' => ['en' => 'Snorkeling Trips', 'ar' => 'رحلات السنوركل'],
                'items' => [
                    ['id' => 'snork-ras-adult', 'name' => 'White Island & Ras Mohammed by Boat — Adult', 'desc' => 'Full day. Mask, life jacket, lunch & soft drinks, transfers, park fees.', 'price' => 55, 'child_price' => 30, 'duration' => '8h', 'popular' => true, 'img' => 'sea-college-dive-club-sharm-el-sheikh-snorkeling-trip-family-red-sea.jpg'],
                    ['id' => 'snork-ras-child', 'name' => 'White Island & Ras Mohammed by Boat — Child (5–12)', 'desc' => 'Full day. Same inclusions as adult.', 'price' => 30, 'duration' => '8h', 'img' => 'sea-college-dive-club-sharm-el-sheikh-snorkeling-trip-family-red-sea.jpg'],
                    ['id' => 'snork-beach-day', 'name' => 'Snorkeling from the Beach — Day (House Reef)', 'desc' => 'Min. 6 guests. Mask, life jacket, transfers.', 'price' => 35, 'img' => 'sea-college-dive-club-sharm-el-sheikh-snorkeling-trip-family-red-sea.jpg'],
                    ['id' => 'snork-beach-night', 'name' => 'Night Snorkeling from the Beach (House Reef)', 'desc' => 'Min. 6 guests. Includes torch.', 'price' => 40, 'img' => 'sea-college-dive-club-sharm-el-sheikh-snorkeling-trip-family-red-sea.jpg'],
                    ['id' => 'snork-ras-land', 'name' => 'Ras Mohammed Snorkeling by Land (Bus)', 'desc' => 'Min. 6 guests. Mask, life jacket, transfers, park fees.', 'price' => 60, 'img' => 'sea-college-dive-club-sharm-el-sheikh-snorkeling-trip-family-red-sea.jpg'],
                ],
            ],
            [
                'title' => ['en' => 'Private Speedboat Snorkeling', 'ar' => 'سنوركل بقارب سريع خاص'],
                'items' => [
                    ['id' => 'speed-1h', 'name' => '1 Hour Speedboat — Local Reefs', 'desc' => 'Boat price for up to 3 guests (max 5; 4th & 5th +€50 each).', 'price' => 75, 'unit' => 'boat', 'img' => 'sea-college-dive-club-sharm-el-sheikh-tiran-island-private-speedboat.jpg'],
                    ['id' => 'speed-2h', 'name' => '2 Hours Speedboat — Tiran Island', 'desc' => 'Boat price for 2 guests (max 5; extra guest +€75 each).', 'price' => 150, 'unit' => 'boat', 'popular' => true, 'img' => 'sea-college-dive-club-sharm-el-sheikh-tiran-island-private-speedboat.jpg'],
                    ['id' => 'speed-3h', 'name' => '3 Hours Speedboat — Tiran Island', 'desc' => 'Boat price for 2 guests (max 5; extra guest +€100 each).', 'price' => 225, 'unit' => 'boat', 'img' => 'sea-college-dive-club-sharm-el-sheikh-tiran-island-private-speedboat.jpg'],
                ],
            ],
            [
                'title' => ['en' => 'Private Boat Charter (full day)', 'ar' => 'تأجير قارب خاص (يوم كامل)'],
                'items' => [
                    ['id' => 'private-1-5', 'name' => 'Private Boat — 1 to 5 guests', 'desc' => 'Snorkeling & diving day on your own boat.', 'price' => 550, 'unit' => 'boat', 'img' => 'sea-college-dive-club-sharm-el-sheikh-private-yacht-boat-trip.jpg'],
                    ['id' => 'private-6-10', 'name' => 'Private Boat — 6 to 10 guests', 'desc' => 'Ideal for families and friends.', 'price' => 650, 'unit' => 'boat', 'popular' => true, 'img' => 'sea-college-dive-club-sharm-el-sheikh-private-yacht-boat-trip.jpg'],
                    ['id' => 'private-11-15', 'name' => 'Private Boat — 11 to 15 guests', 'desc' => '', 'price' => 900, 'unit' => 'boat', 'img' => 'sea-college-dive-club-sharm-el-sheikh-private-yacht-boat-trip.jpg'],
                    ['id' => 'private-16-20', 'name' => 'Private Boat — 16 to 20 guests', 'desc' => '', 'price' => 1100, 'unit' => 'boat', 'img' => 'sea-college-dive-club-sharm-el-sheikh-private-yacht-boat-trip.jpg'],
                ],
            ],
        ],
    ],
];
