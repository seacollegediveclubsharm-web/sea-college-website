<?php
/**
 * Reusable interactive UI components:
 * 1. Interactive Dive Budget & Fee Calculator ("True Total Estimator")
 * 2. 30-Second "Find My Dive / Course" Recommender Quiz
 * 3. Named Instructor & Guide Team Showcase
 */
declare(strict_types=1);

require_once __DIR__ . '/components.php';

/** Build compact JS-ready metadata map for catalog IDs used by Calculator & Quiz. */
function sc_widget_catalog_map(array $catalog, array $ids): array
{
    $flat = catalog_all_items($catalog);
    $out = [];
    foreach ($ids as $id) {
        if (!isset($flat[$id])) {
            continue;
        }
        $it = $flat[$id];
        $out[$id] = [
            'id'       => $it['id'],
            'name'     => item_name($it),
            'raw_name' => $it['name'],
            'desc'     => item_desc($it),
            'price'    => $it['price'],
            'unit'     => $it['unit'] ?? '',
            'save'     => $it['save'] ?? 0,
            'duration' => item_duration($it),
            'url'      => !empty($it['slug']) ? item_url($it) : page_link('/prices.php'),
        ];
    }
    return $out;
}

/**
 * Feature #1: Interactive Dive Budget & Fee Calculator.
 * Pushes exact catalog line items to sc_cart or formats a line-by-line WhatsApp quote.
 */
function render_budget_calculator(array $catalog, string $bookingHref = '#booking'): string
{
    $ids = [
        'dive-1', 'dive-2', 'dive-3', 'dive-4', 'dive-5', 'dive-6', 'dive-7', 'dive-8', 'dive-9', 'dive-10', 'dive-extra',
        'check-dive', 'scuba-review', 'fee-boat', 'fee-park', 'private-guide',
        'eq-full', 'eq-computer', 'eq-torch', 'eq-12nx', 'eq-15air', 'eq-15nx',
        'sup-night', 'sup-dunraven', 'sup-millionhope', 'sup-zodiac', 'sup-dahab', 'sup-thistlegorm',
        'dsd-quick-reef', 'dsd-2-reef', 'dsd-quick-boat', 'dsd-2-boat',
        'owd', 'aowd', 'rescue', 'bundle-stars', 'bundle-super', 'bundle-mega', 'bundle-heros',
    ];
    $itemsMap = sc_widget_catalog_map($catalog, $ids);
    $cfgJson = e((string) json_encode([
        'items'       => $itemsMap,
        'bookingHref' => $bookingHref,
        'labels'      => [
            'perPerson'   => t('calc_per_person'),
            'divers'      => t('calc_divers_unit'),
            'days'        => t('calc_days_unit'),
            'padiFeeName' => t('calc_padi_fee_line'),
            'waHeader'    => t('calc_wa_header'),
            'totalLabel'  => t('calc_total_estimate'),
            'pushedToast' => t('calc_pushed_toast'),
        ],
    ], JSON_UNESCAPED_UNICODE));

    $courses = ['dsd-quick-reef', 'dsd-2-boat', 'owd', 'aowd', 'bundle-stars', 'bundle-super', 'bundle-mega', 'bundle-heros', 'rescue'];
    $sups = ['sup-thistlegorm', 'sup-dunraven', 'sup-millionhope', 'sup-night', 'sup-zodiac', 'sup-dahab'];

    ob_start();
    ?>
    <div class="dcalc card no-print" id="dive-calculator" data-dive-calc="<?= $cfgJson ?>">
      <div class="dcalc__head">
        <div>
          <span class="eyebrow"><?= te('calc_eyebrow') ?></span>
          <h2><?= te('calc_title') ?></h2>
          <p><?= te('calc_sub') ?></p>
        </div>
        <div class="dcalc__modes" role="tablist" aria-label="<?= te('calc_mode_label') ?>">
          <button type="button" class="dcalc__mode is-active" data-calc-mode="dives" aria-pressed="true">🤿 <?= te('calc_mode_dives') ?></button>
          <button type="button" class="dcalc__mode" data-calc-mode="course" aria-pressed="false">🎓 <?= te('calc_mode_course') ?></button>
        </div>
      </div>

      <div class="dcalc__grid">
        <div class="dcalc__controls">
          <div class="dcalc__row dcalc__row--two">
            <div class="dcalc__field">
              <label for="calc-divers"><?= te('calc_divers_label') ?>: <strong data-calc-divers-val>1</strong></label>
              <input type="range" id="calc-divers" min="1" max="10" value="1" data-calc-divers>
            </div>
            <div class="dcalc__field" data-calc-only="dives">
              <label for="calc-dives"><?= te('calc_dives_label') ?>: <strong data-calc-dives-val>6</strong></label>
              <input type="range" id="calc-dives" min="1" max="16" value="6" data-calc-dives>
            </div>
            <div class="dcalc__field" data-calc-only="course" hidden>
              <label for="calc-course"><?= te('calc_course_select') ?></label>
              <select id="calc-course" data-calc-course>
                <?php foreach ($courses as $cid): if (!isset($itemsMap[$cid])) { continue; } $ci = $itemsMap[$cid]; ?>
                  <option value="<?= e($cid) ?>"<?= $cid === 'owd' ? ' selected' : '' ?>><?= e($ci['name']) ?> (<?= e(money($ci['price'])) ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="dcalc__row dcalc__row--two">
            <div class="dcalc__field">
              <label for="calc-boat-days"><?= te('calc_boat_days') ?>: <strong data-calc-boat-val>3</strong></label>
              <input type="range" id="calc-boat-days" min="0" max="8" value="3" data-calc-boat-days>
              <small class="dcalc__hint"><?= te('calc_boat_hint') ?></small>
            </div>
            <div class="dcalc__field">
              <span class="dcalc__lbl"><?= te('calc_daily_fees') ?></span>
              <label class="dcalc__check"><input type="checkbox" data-calc-fee-boat checked> <span><?= e($itemsMap['fee-boat']['name']) ?> (<bdi dir="ltr">€10</bdi><?= te('per_day') ?>)</span></label>
              <label class="dcalc__check"><input type="checkbox" data-calc-fee-park checked> <span><?= e($itemsMap['fee-park']['name']) ?> (<bdi dir="ltr">€5</bdi><?= te('per_day') ?>)</span></label>
            </div>
          </div>

          <div class="dcalc__row dcalc__row--two" data-calc-only="dives">
            <div class="dcalc__field">
              <label for="calc-refresh"><?= te('calc_refresher_label') ?></label>
              <select id="calc-refresh" data-calc-refresh>
                <option value="none"><?= te('calc_refresher_none') ?> (€0)</option>
                <option value="check-dive"><?= e($itemsMap['check-dive']['name']) ?> (+€25)</option>
                <option value="scuba-review"><?= e($itemsMap['scuba-review']['name']) ?> (+€50)</option>
              </select>
            </div>
            <div class="dcalc__field">
              <label for="calc-gas"><?= te('calc_gas_label') ?></label>
              <select id="calc-gas" data-calc-gas>
                <option value="air12"><?= te('calc_gas_12air') ?> (€0)</option>
                <option value="eq-12nx"><?= e($itemsMap['eq-12nx']['name']) ?> (+€8<?= te('per_day') ?>)</option>
                <option value="eq-15air"><?= e($itemsMap['eq-15air']['name']) ?> (+€6<?= te('per_day') ?>)</option>
                <option value="eq-15nx"><?= e($itemsMap['eq-15nx']['name']) ?> (+€10<?= te('per_day') ?>)</option>
              </select>
            </div>
          </div>

          <div class="dcalc__field" data-calc-only="dives">
            <span class="dcalc__lbl"><?= te('calc_gear_label') ?></span>
            <div class="dcalc__pills">
              <label class="dcalc__pill"><input type="checkbox" data-calc-gear="eq-full"> <span>🤿 <?= e($itemsMap['eq-full']['name']) ?> (+€25<?= te('per_day') ?>)</span></label>
              <label class="dcalc__pill"><input type="checkbox" data-calc-gear="eq-computer"> <span>⌚ <?= e($itemsMap['eq-computer']['name']) ?> (+€10<?= te('per_day') ?>)</span></label>
              <label class="dcalc__pill"><input type="checkbox" data-calc-gear="eq-torch"> <span>🔦 <?= e($itemsMap['eq-torch']['name']) ?> (+€6<?= te('per_day') ?>)</span></label>
            </div>
          </div>

          <div class="dcalc__field" data-calc-only="course" hidden>
            <span class="dcalc__lbl"><?= te('calc_course_extras') ?></span>
            <label class="dcalc__check"><input type="checkbox" data-calc-padi-fee checked> <span><?= te('calc_padi_fee_toggle') ?></span></label>
            <small class="dcalc__hint"><?= te('calc_course_gear_note') ?></small>
          </div>

          <div class="dcalc__field">
            <span class="dcalc__lbl"><?= te('calc_supplements_label') ?></span>
            <div class="dcalc__pills">
              <?php foreach ($sups as $sid): if (!isset($itemsMap[$sid])) { continue; } $si = $itemsMap[$sid]; ?>
                <label class="dcalc__pill"><input type="checkbox" data-calc-sup="<?= e($sid) ?>"> <span><?= e($si['name']) ?> (+<bdi dir="ltr">€<?= (int) $si['price'] ?></bdi>)</span></label>
              <?php endforeach; ?>
              <label class="dcalc__pill"><input type="checkbox" data-calc-private-guide> <span>🧭 <?= e($itemsMap['private-guide']['name']) ?> (+<bdi dir="ltr">€75</bdi><?= te('per_day') ?>)</span></label>
            </div>
          </div>
        </div>

        <aside class="dcalc__summary" aria-live="polite">
          <h3><?= te('calc_breakdown_title') ?></h3>
          <ul class="dcalc__lines" data-calc-lines></ul>
          <div class="dcalc__totals">
            <div class="dcalc__per"><span><?= te('calc_per_person') ?></span> <strong data-calc-pp>€0</strong></div>
            <div class="dcalc__grand"><span><?= te('calc_total_estimate') ?></span> <strong data-calc-grand>€0</strong></div>
          </div>
          <p class="dcalc__included">✓ <?= te('calc_included_note') ?></p>
          <div class="dcalc__actions">
            <button type="button" class="btn btn--primary btn--block" data-calc-push-cart><?= te('calc_push_cart') ?></button>
            <button type="button" class="btn btn--wa btn--block" data-calc-wa><?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?><span><?= te('calc_send_wa') ?></span></button>
          </div>
          <p class="dcalc__toast" data-calc-toast hidden></p>
        </aside>
      </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

/**
 * Feature #2: 30-Second "Find My Dive / Course" Recommender Quiz.
 */
function render_dive_quiz(array $catalog): string
{
    $ids = [
        'bubblemaker', 'seal-team', 'dsd-quick-reef', 'dsd-2-reef', 'dsd-quick-boat', 'dsd-2-boat', 'dsd',
        'scuba-diver', 'owd', 'aowd', 'rescue', 'nitrox',
        'bundle-stars', 'bundle-super', 'bundle-mega', 'bundle-heros',
        'check-dive', 'scuba-review', 'dive-1', 'dive-2', 'dive-6', 'dive-10',
        'sup-thistlegorm', 'sup-dunraven',
        'snork-ras-adult', 'snork-beach-day', 'speed-2h', 'private-1-5', 'private-6-10',
    ];
    $itemsMap = sc_widget_catalog_map($catalog, $ids);
    $quizCfg = e((string) json_encode([
        'items' => $itemsMap,
        'i18n'  => [
            'bestMatch' => t('quiz_best_match'),
            'altMatch'  => t('quiz_alt_match'),
            'from'      => t('from'),
            'save'      => t('save'),
            'details'   => t('cta_details'),
            'add'       => t('add_to_booking'),
            'askWa'     => t('ask_whatsapp'),
            'reasons'   => [
                'kids'    => t('quiz_reason_kids'),
                'snorkel' => t('quiz_reason_snorkel'),
                'private' => t('quiz_reason_private'),
                'intro'   => t('quiz_reason_intro'),
                'course'  => t('quiz_reason_course'),
                'bundle'  => t('quiz_reason_bundle'),
                'refresh' => t('quiz_reason_refresh'),
                'dives'   => t('quiz_reason_dives'),
                'wreck'   => t('quiz_reason_wreck'),
            ],
        ],
    ], JSON_UNESCAPED_UNICODE));

    $steps = [
        1 => ['q' => 'quiz_q1', 'opts' => [
            ['solo', '🤿', 'quiz_q1_solo'],
            ['kids8', '🧒', 'quiz_q1_kids8'],
            ['family10', '👨‍👩‍👧', 'quiz_q1_family10'],
            ['vipgroup', '🛥️', 'quiz_q1_vip'],
        ]],
        2 => ['q' => 'quiz_q2', 'opts' => [
            ['never', '🌊', 'quiz_q2_never'],
            ['owd', '🪪', 'quiz_q2_owd'],
            ['adv', '🦈', 'quiz_q2_adv'],
            ['rusty', '⏳', 'quiz_q2_rusty'],
            ['surface', '🏝️', 'quiz_q2_surface'],
        ]],
        3 => ['q' => 'quiz_q3', 'opts' => [
            ['half', '⏱️', 'quiz_q3_half'],
            ['day1', '☀️', 'quiz_q3_day1'],
            ['days3', '📅', 'quiz_q3_days3'],
            ['week', '🗓️', 'quiz_q3_week'],
        ]],
        4 => ['q' => 'quiz_q4', 'opts' => [
            ['easy', '😊', 'quiz_q4_easy'],
            ['cert', '🎓', 'quiz_q4_cert'],
            ['reefs', '🪸', 'quiz_q4_reefs'],
            ['private', '🥂', 'quiz_q4_private'],
        ]],
    ];

    ob_start();
    ?>
    <div class="dquiz card no-print" id="dive-finder-quiz" data-dive-quiz="<?= $quizCfg ?>">
      <div class="dquiz__head">
        <div>
          <span class="eyebrow"><?= te('quiz_eyebrow') ?></span>
          <h2><?= te('quiz_title') ?></h2>
          <p><?= te('quiz_sub') ?></p>
        </div>
        <div class="dquiz__prog" aria-hidden="true">
          <?php for ($s = 1; $s <= 4; $s++): ?>
            <span class="dquiz__dot<?= $s === 1 ? ' is-active' : '' ?>" data-quiz-dot="<?= $s ?>"><?= $s ?></span>
          <?php endfor; ?>
        </div>
      </div>

      <?php foreach ($steps as $num => $st): ?>
        <div class="dquiz__step" data-quiz-step="<?= $num ?>"<?= $num === 1 ? '' : ' hidden' ?>>
          <h3 class="dquiz__q"><?= te($st['q']) ?></h3>
          <div class="dquiz__opts">
            <?php foreach ($st['opts'] as [$val, $ico, $key]): ?>
              <button type="button" class="dquiz__opt" data-quiz-pick="<?= e($val) ?>" data-step="<?= $num ?>">
                <span class="dquiz__ico" aria-hidden="true"><?= $ico ?></span>
                <span><?= te($key) ?></span>
              </button>
            <?php endforeach; ?>
          </div>
          <?php if ($num > 1): ?>
            <button type="button" class="linklike dquiz__back" data-quiz-back>← <?= te('quiz_back') ?></button>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <div class="dquiz__results" data-quiz-results hidden aria-live="polite">
        <div class="dquiz__res-head">
          <h3><?= te('quiz_result_title') ?></h3>
          <button type="button" class="btn btn--ghost btn--sm" data-quiz-reset>↺ <?= te('quiz_restart') ?></button>
        </div>
        <div class="dquiz__cards" data-quiz-cards></div>
      </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

/**
 * Feature #4b: Named Instructor & Dive Team Showcase cards.
 */
function render_team_showcase(array $team, int $limit = 0): string
{
    $list = $limit > 0 ? array_slice($team, 0, $limit) : $team;
    $html = '<div class="team-grid">';
    foreach ($list as $m) {
        $nameKey = 'name_' . LANG;
        $name = (LANG !== 'en' && !empty($m[$nameKey])) ? (string) $m[$nameKey] : (string) $m['name'];
        $revKey = 'reviewer_' . LANG;
        $reviewer = (LANG !== 'en' && !empty($m[$revKey])) ? (string) $m[$revKey] : (string) $m['reviewer'];
        $initials = mb_strtoupper(mb_substr($m['name'], 0, 2));
        $html .= '<article class="team-card card">';
        $html .= '<div class="team-card__top">';
        $html .= '<span class="team-card__avatar" aria-hidden="true">' . e($initials) . '</span>';
        $html .= '<div><h3 class="team-card__name" dir="auto">' . e($name) . '</h3>';
        $html .= '<p class="team-card__role" dir="auto">' . e(loc($m['role'])) . '</p></div>';
        $html .= '<span class="team-card__langs" title="' . te('lang_label') . '"><bdi dir="ltr">' . e($m['langs']) . '</bdi></span>';
        $html .= '</div>';
        $html .= '<p class="team-card__focus" dir="auto">' . e(loc($m['focus'])) . '</p>';
        $html .= '<blockquote class="team-card__quote"><p dir="auto">' . e(loc($m['quote'])) . '</p>';
        $html .= '<cite><bdi dir="ltr">★★★★★</bdi> · ' . e($reviewer) . '</cite></blockquote>';
        $html .= '</article>';
    }
    $html .= '</div>';
    return $html;
}

/**
 * Real Client Moments & Verified Diver Graduations photo showcase (2026 authentic guest photos).
 */
function render_real_guests_showcase(bool $dark = false): string
{
    $cards = [
        [
            'img'  => 'images/clients/sea-college-real-guests-padi-certificates-sharm.jpg',
            't'    => t('real_guest_1_t'),
            'd'    => t('real_guest_1_d'),
            'href' => page_link('/courses/padi-advanced-open-water-diver-sharm'),
            'tag'  => 'PADI 5★',
        ],
        [
            'img'  => 'images/clients/sea-college-real-guests-boat-group-red-sea.jpg',
            't'    => t('real_guest_2_t'),
            'd'    => t('real_guest_2_d'),
            'href' => page_link('/diving-trips-sharm-el-sheikh'),
            'tag'  => '4.9★ Google',
        ],
        [
            'img'  => 'images/clients/sea-college-real-guests-wetsuits-al-montazah-beach.jpg',
            't'    => t('real_guest_3_t'),
            'd'    => t('real_guest_3_d'),
            'href' => page_link('/tours/discover-scuba-diving-2-dives-house-reef'),
            'tag'  => 'Al Montazah',
        ],
        [
            'img'  => 'images/clients/sea-college-real-guests-sunset-speedboat-sharm.jpg',
            't'    => t('real_guest_4_t'),
            'd'    => t('real_guest_4_d'),
            'href' => page_link('/tours/private-speedboat-tiran-2-hours'),
            'tag'  => '4.9 ●●●●● Tripadvisor',
        ],
    ];

    ob_start(); ?>
    <div class="real-guests<?= $dark ? ' real-guests--dark' : '' ?>">
      <div class="real-guests__head">
        <div>
          <span class="eyebrow">📸 <?= te('real_guests_eyebrow') ?></span>
          <h3><?= te('real_guests_title') ?></h3>
          <p><?= te('real_guests_sub') ?></p>
        </div>
        <a class="btn btn--detail btn--sm" href="<?= e(page_link('/gallery')) ?>"><?= te('real_guests_cta') ?> →</a>
      </div>
      <div class="cards cards--4 real-guests__grid">
        <?php foreach ($cards as $c): ?>
          <article class="pcard real-guest-card">
            <a class="pcard__media" href="<?= e($c['href']) ?>">
              <?= sc_picture($c['img'], $c['t'] . ' — ' . SITE_NAME, 480, 360, ['sizes' => '(max-width: 599px) 92vw, (max-width: 1199px) 46vw, 290px']) ?>
              <span class="pcard__badge"><bdi dir="ltr"><?= e($c['tag']) ?></bdi></span>
            </a>
            <div class="pcard__body">
              <h4 class="pcard__title" dir="auto"><a class="pcard__link" href="<?= e($c['href']) ?>"><?= e($c['t']) ?></a></h4>
              <p class="pcard__desc" dir="auto"><?= e($c['d']) ?></p>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
    return (string) ob_get_clean();
}
