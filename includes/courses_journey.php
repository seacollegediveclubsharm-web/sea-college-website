<?php
/**
 * Interactive PADI Diving Courses Chronological Depth-Timeline Infographic Component.
 *
 * Displayed across all 8 languages (`en`, `ru`, `it`, `pl`, `de`, `fr`, `ar`, `zh`) on:
 *   - /diving-courses-sharm-el-sheikh  (category.php: padi-courses)
 *   - /padi-course-bundles-sharm-el-sheikh (category.php: offers)
 *   - / (index.php Diving Courses section)
 *
 * Pulls live synced EUR prices dynamically from $catalog (data/catalog.php + includes/live_prices.php).
 */
declare(strict_types=1);

require_once __DIR__ . '/components.php';

function render_courses_journey(array $catalog, string $context = 'hub'): string
{
    $i18nAll = require ROOT_DIR . '/data/courses_journey_i18n.php';
    $j       = $i18nAll[LANG] ?? $i18nAll['en'];
    $all     = catalog_all_items($catalog);

    $hasOnPageBooking = ($context === 'hub');

    $fmtLivePrice = static function (?array $it): string {
        if (!$it || ($it['price'] ?? null) === null) {
            return '';
        }
        $eur = $it['price'];
        return '<bdi dir="ltr" data-eur="' . e((string) $eur) . '">' . e(money($eur)) . '</bdi>';
    };

    $dsd    = $all['dsd'] ?? null;
    $reef   = $all['dsd-quick-reef'] ?? null;
    $bubble = $all['bubblemaker'] ?? null;
    $sdiver = $all['scuba-diver'] ?? null;
    $upgOwd = $all['upgrade-owd'] ?? null;
    $owd    = $all['owd'] ?? null;
    $aowd   = $all['aowd'] ?? null;
    $nitrox = $all['nitrox'] ?? ($all['nitrox-course'] ?? null);
    $rescue = $all['rescue'] ?? null;
    $efr    = $all['efr'] ?? null;
    $dm     = $all['divemaster'] ?? null;

    $steps = [
        [
            'num'       => 1,
            'stage'     => 'beg',
            'meter'     => '12m',
            'icon'      => '🫧',
            'variant'   => 'entry',
            'badge'     => '',
            'item'      => $dsd,
            'title'     => $j['s1_title'],
            'depth'     => $j['s1_depth'],
            'dur'       => $j['s1_dur'],
            'desc'      => $j['s1_desc'],
            'sub_links' => array_values(array_filter([
                $reef ? ['label' => $j['s1_note_reef'], 'item' => $reef] : null,
                $bubble ? ['label' => $j['s1_note_kids'], 'item' => $bubble] : null,
            ])),
            'perk'      => '',
        ],
        [
            'num'       => 2,
            'stage'     => 'beg',
            'meter'     => '12m',
            'icon'      => '🤿',
            'variant'   => 'std',
            'badge'     => '',
            'item'      => $sdiver,
            'title'     => $j['s2_title'],
            'depth'     => $j['s2_depth'],
            'dur'       => $j['s2_dur'],
            'desc'      => $j['s2_desc'],
            'sub_links' => array_values(array_filter([
                $upgOwd ? ['label' => $j['s2_note_upg'], 'item' => $upgOwd] : null,
            ])),
            'perk'      => '',
        ],
        [
            'num'       => 3,
            'stage'     => 'beg',
            'meter'     => '18m',
            'icon'      => '🌊',
            'variant'   => 'popular',
            'badge'     => $j['badge_popular'],
            'item'      => $owd,
            'title'     => $j['s3_title'],
            'depth'     => $j['s3_depth'],
            'dur'       => $j['s3_dur'],
            'desc'      => $j['s3_desc'],
            'sub_links' => [],
            'perk'      => $j['s3_perk'],
        ],
        [
            'num'       => 4,
            'stage'     => 'adv',
            'meter'     => '30m',
            'icon'      => '⚓',
            'variant'   => 'std',
            'badge'     => '',
            'item'      => $aowd,
            'title'     => $j['s4_title'],
            'depth'     => $j['s4_depth'],
            'dur'       => $j['s4_dur'],
            'desc'      => $j['s4_desc'],
            'sub_links' => array_values(array_filter([
                $nitrox ? ['label' => $j['s4_addon_nx'], 'item' => $nitrox] : null,
            ])),
            'perk'      => '',
        ],
        [
            'num'       => 5,
            'stage'     => 'adv',
            'meter'     => '30m',
            'icon'      => '🛟',
            'variant'   => 'std',
            'badge'     => '',
            'item'      => $rescue,
            'title'     => $j['s5_title'],
            'depth'     => $j['s5_depth'],
            'dur'       => $j['s5_dur'],
            'desc'      => $j['s5_desc'],
            'sub_links' => array_values(array_filter([
                $efr ? ['label' => $j['s5_addon_efr'], 'item' => $efr] : null,
            ])),
            'perk'      => '',
        ],
        [
            'num'       => 6,
            'stage'     => 'adv',
            'meter'     => '40m',
            'icon'      => '🧭',
            'variant'   => 'pro',
            'badge'     => $j['badge_pro'],
            'item'      => $dm,
            'title'     => $j['s6_title'],
            'depth'     => $j['s6_depth'],
            'dur'       => $j['s6_dur'],
            'desc'      => $j['s6_desc'],
            'sub_links' => [],
            'perk'      => $j['s6_perk'],
        ],
    ];

    $bundles = array_values(array_filter([
        $all['bundle-super'] ?? null,
        $all['bundle-stars'] ?? null,
        $all['bundle-mega'] ?? null,
        $all['bundle-heros'] ?? null,
    ]));

    ob_start();
    ?>
    <div class="padi-journey" id="padi-courses-journey" data-courses-journey>
      <div class="padi-journey__head">
        <div>
          <span class="padi-journey__eyebrow"><?= e($j['eyebrow']) ?></span>
          <h2 class="padi-journey__title" dir="auto"><?= e($j['title']) ?></h2>
          <p class="padi-journey__sub" dir="auto"><?= e($j['sub']) ?></p>
        </div>
        <div class="padi-journey__filters no-print" role="tablist" aria-label="<?= e($j['title']) ?>">
          <button type="button" class="padi-jfilter is-active" data-journey-filter="all" aria-selected="true"><?= e($j['filter_all']) ?></button>
          <button type="button" class="padi-jfilter" data-journey-filter="beg" aria-selected="false"><?= e($j['filter_beg']) ?></button>
          <button type="button" class="padi-jfilter" data-journey-filter="adv" aria-selected="false"><?= e($j['filter_adv']) ?></button>
        </div>
      </div>

      <ol class="padi-journey__list">
        <?php foreach ($steps as $st):
            $it = $st['item'];
            if (!$it) {
                continue;
            }
            $detHref  = !empty($it['slug']) ? item_url($it) : page_link('/diving-courses-sharm-el-sheikh');
            $bookHref = $hasOnPageBooking
                ? '#booking'
                : (!empty($it['slug']) ? page_link(item_path($it), 'booking') : page_link('/prices', 'booking'));
            $dispName = item_name($it);
            $eurVal   = $it['price'] !== null ? (string) $it['price'] : '';
            $stepLbl  = str_replace('{n}', (string) $st['num'], $j['step_lbl']);
        ?>
        <li class="padi-step padi-step--<?= e($st['variant']) ?>" data-journey-stage="<?= e($st['stage']) ?>">
          <div class="padi-step__rail" aria-hidden="true">
            <span class="padi-step__node"><?= (int) $st['num'] ?></span>
            <span class="padi-step__depth-tag"><bdi dir="ltr"><?= e($st['meter']) ?></bdi></span>
          </div>

          <article class="padi-step__card">
            <div class="padi-step__top">
              <div class="padi-step__chips">
                <span class="padi-chip padi-chip--step"><?= e($st['icon'] . ' ' . $stepLbl) ?></span>
                <span class="padi-chip padi-chip--depth">🌊 <?= e($st['depth']) ?></span>
                <span class="padi-chip padi-chip--dur">⏱ <?= e($st['dur']) ?></span>
                <?php if ($st['badge'] !== ''): ?>
                  <span class="padi-chip padi-chip--badge"><?= e($st['badge']) ?></span>
                <?php endif; ?>
              </div>
              <div class="padi-step__price">
                <?= price_html($it['price'], $it['unit'] ?? '', $it['regular_price'] ?? null) ?>
              </div>
            </div>

            <h3 class="padi-step__title" dir="auto">
              <a href="<?= e($detHref) ?>"><?= e($st['title']) ?></a>
            </h3>
            <p class="padi-step__desc" dir="auto"><?= e($st['desc']) ?></p>

            <?php if ($st['sub_links']): ?>
            <div class="padi-step__addons">
              <?php foreach ($st['sub_links'] as $sl):
                  $sIt  = $sl['item'];
                  $sUrl = !empty($sIt['slug']) ? item_url($sIt) : page_link('/prices');
              ?>
                <a class="padi-addon-pill" href="<?= e($sUrl) ?>">
                  <span><?= e($sl['label']) ?>:</span>
                  <strong><?= $fmtLivePrice($sIt) ?></strong> →
                </a>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($st['perk'] !== ''): ?>
              <p class="padi-step__perk" dir="auto"><?= e($st['perk']) ?></p>
            <?php endif; ?>

            <div class="padi-step__actions no-print">
              <a class="btn btn--ghost padi-btn--details btn--sm"
                 href="<?= e($detHref) ?>"
                 data-track="cta_click"
                 data-placement="journey_step<?= (int) $st['num'] ?>_details">
                <?= e($j['cta_details']) ?>
              </a>
              <a class="btn btn--primary btn--sm"
                 href="<?= e($bookHref) ?>"
                 data-book-now
                 data-id="<?= e((string) $it['id']) ?>"
                 data-name="<?= e($dispName) ?>"
                 data-price="<?= e($eurVal) ?>"
                 data-item-id="<?= e((string) $it['id']) ?>"
                 data-item-name="<?= e($dispName) ?>"
                 data-item-eur="<?= e($eurVal) ?>"
                 data-track="cta_click"
                 data-placement="journey_step<?= (int) $st['num'] ?>_book">
                <?= e($j['cta_book']) ?>
              </a>
            </div>
          </article>
        </li>
        <?php endforeach; ?>
      </ol>

      <?php if ($bundles): ?>
      <div class="padi-combo">
        <div class="padi-combo__head">
          <div>
            <span class="padi-combo__badge"><?= e($j['combo_badge']) ?></span>
            <h3 class="padi-combo__title" dir="auto"><?= e($j['combo_title']) ?></h3>
            <p class="padi-combo__sub" dir="auto"><?= e($j['combo_sub']) ?></p>
          </div>
          <a class="btn btn--ghost padi-btn--details btn--sm" href="<?= e(page_link('/padi-course-bundles-sharm-el-sheikh')) ?>" data-track="cta_click" data-placement="journey_combo_hub"><?= e($j['combo_cta_all']) ?></a>
        </div>

        <div class="padi-combo__grid">
          <?php foreach ($bundles as $b):
              $bHref    = !empty($b['slug']) ? item_url($b) : page_link('/padi-course-bundles-sharm-el-sheikh');
              $bBook    = $hasOnPageBooking ? '#booking' : (!empty($b['slug']) ? page_link(item_path($b), 'booking') : page_link('/prices', 'booking'));
              $bName    = item_name($b);
              $bEur     = $b['price'] !== null ? (string) $b['price'] : '';
              $bSaveAmt = !empty($b['save']) ? ('<bdi dir="ltr" data-eur="' . e((string) $b['save']) . '">' . e(money($b['save'])) . '</bdi>') : '';
              $bSaveLbl = $bSaveAmt !== '' ? str_replace('{amt}', $bSaveAmt, e($j['combo_save'])) : '';
          ?>
          <div class="padi-combo__card">
            <div class="padi-combo__card-top">
              <strong class="padi-combo__name" dir="auto"><a href="<?= e($bHref) ?>"><?= e($bName) ?></a></strong>
              <?php if ($bSaveLbl !== ''): ?>
                <span class="padi-combo__save"><?= $bSaveLbl ?></span>
              <?php endif; ?>
            </div>
            <div class="padi-combo__price">
              <?= price_html($b['price'], $b['unit'] ?? '', $b['regular_price'] ?? null) ?>
            </div>
            <div class="padi-combo__acts no-print">
              <a class="btn btn--ghost padi-btn--details btn--sm" href="<?= e($bHref) ?>" data-track="cta_click" data-placement="journey_bundle_details"><?= e($j['cta_details']) ?></a>
              <a class="btn btn--primary btn--sm"
                 href="<?= e($bBook) ?>"
                 data-book-now
                 data-id="<?= e((string) $b['id']) ?>"
                 data-name="<?= e($bName) ?>"
                 data-price="<?= e($bEur) ?>"
                 data-item-id="<?= e((string) $b['id']) ?>"
                 data-item-name="<?= e($bName) ?>"
                 data-item-eur="<?= e($bEur) ?>"
                 data-track="cta_click"
                 data-placement="journey_bundle_book"><?= e($j['cta_book']) ?></a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <?php
    return (string) ob_get_clean();
}
