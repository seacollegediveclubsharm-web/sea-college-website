<?php
/**
 * Interactive Sharm El Sheikh Dive Sites Map & 12-Month Red Sea Conditions Guide.
 * Clean URL: /dive-sites-sharm-el-sheikh
 */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require ROOT_DIR . '/includes/components.php';
require ROOT_DIR . '/includes/promo_banners.php';

$catalog  = require ROOT_DIR . '/data/catalog.php';
$flat     = catalog_all_items($catalog);
$siteData = require ROOT_DIR . '/data/dive_sites.php';

$page['id']             = 'dive-sites';
$page['nav_active']     = 'dive-sites';
$page['canonical_path'] = '/dive-sites-sharm-el-sheikh';
$page['title']          = t('meta_sites_title');
$page['description']    = t('meta_sites_desc');
$page['image']          = url('assets/' . seo_img('ss-thistlegorm-wreck-dive'));
$page['breadcrumbs']    = [
    [t('breadcrumb_home'), '/'],
    [t('nav_dive_sites'), '/dive-sites-sharm-el-sheikh'],
];
$page['speakable'] = ['.page-hero h1', '.aeo-box__text'];

$listElements = [];
foreach ($siteData['sites'] as $idx => $s) {
    $listElements[] = [
        '@type'    => 'ListItem',
        'position' => $idx + 1,
        'item'     => [
            '@type'       => 'TouristAttraction',
            'name'        => loc($s['name']),
            'description' => loc($s['summary']),
            'url'         => canonical_url() . '#site-' . $s['id'],
            'image'       => url('assets/images/' . $s['img']),
            'touristType' => loc($s['level']),
        ],
    ];
}
$page['schema'][] = [
    '@type'           => 'ItemList',
    'name'            => t('sites_title'),
    'numberOfItems'   => count($listElements),
    'itemListElement' => $listElements,
];
$page['schema'][] = schema_faq_pairs($siteData['faqs'], $siteData['faqs_i18n'] ?? null);

require ROOT_DIR . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <?= breadcrumbs_html($page['breadcrumbs'], true) ?>
    <p class="eyebrow">🗺️ <?= te('sites_eyebrow') ?></p>
    <h1><?= te('sites_title') ?></h1>
    <p class="hero__sub"><?= te('sites_sub') ?></p>
    <nav class="chips" aria-label="<?= te('services_jump') ?>">
      <a class="chip" href="#dive-map">🗺️ <?= te('sites_jump_map') ?></a>
      <a class="chip" href="#site-cards">🪸 <?= te('sites_jump_list') ?></a>
      <a class="chip" href="#conditions-chart">🌡️ <?= te('sites_jump_seasons') ?></a>
      <a class="chip" href="#faq">❓ <?= te('faq_title') ?></a>
    </nav>
  </div>
</section>

<section class="section" id="dive-map">
  <div class="container">
    <?= render_top_promo_banner('dives', 'sites_top_banner') ?>
    <aside class="aeo-box" id="quick-answer" aria-labelledby="sites-aeo-heading">
      <h2 id="sites-aeo-heading" class="aeo-box__label"><?= te('quick_answer_label') ?></h2>
      <p class="aeo-box__text" dir="auto"><?= e(loc($siteData['aeo_answer'])) ?></p>
    </aside>

    <div class="zone-bar" role="toolbar" aria-label="<?= te('sites_filter_label') ?>" data-site-zones>
      <?php foreach ($siteData['zones'] as $zKey => $zLabel): ?>
        <button type="button" class="zone-btn<?= $zKey === 'all' ? ' is-active' : '' ?>" data-zone-filter="<?= e($zKey) ?>" aria-pressed="<?= $zKey === 'all' ? 'true' : 'false' ?>"><?= e(loc($zLabel)) ?></button>
      <?php endforeach; ?>
    </div>

    <div class="dmap card" data-dive-map>
      <div class="dmap__canvas">
        <svg viewBox="0 0 640 500" role="img" aria-label="<?= te('sites_map_aria') ?>" class="dmap__svg">
          <defs>
            <linearGradient id="seaGrad" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0%" stop-color="#0b2942"/>
              <stop offset="55%" stop-color="#0a5e82"/>
              <stop offset="100%" stop-color="#083d59"/>
            </linearGradient>
            <linearGradient id="sinaiGrad" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#efe6d5"/>
              <stop offset="100%" stop-color="#dfd0b5"/>
            </linearGradient>
          </defs>
          <rect width="640" height="500" rx="12" fill="url(#seaGrad)"/>
          <!-- Depth contours -->
          <path d="M90,260 Q210,330 295,445 Q390,460 510,250 Q555,140 540,20" fill="none" stroke="rgba(255,255,255,.09)" stroke-width="2" stroke-dasharray="6 6"/>
          <!-- South Sinai Peninsula landmass -->
          <path d="M70,0 L455,0 L446,68 L412,155 L398,215 L378,248 L350,298 L334,348 L314,394 L298,374 L268,342 L212,292 L145,222 Z" fill="url(#sinaiGrad)" stroke="#c7b595" stroke-width="2"/>
          <!-- Tiran & Sanafir Islands -->
          <path d="M466,196 Q492,188 506,208 Q500,234 472,232 Q456,218 466,196 Z" fill="url(#sinaiGrad)" stroke="#c7b595" stroke-width="1.5"/>
          <path d="M525,208 Q544,204 550,220 Q540,232 522,226 Z" fill="url(#sinaiGrad)" stroke="#c7b595" stroke-width="1.5"/>
          <!-- Reef halos -->
          <circle cx="168" cy="362" r="22" fill="rgba(37,211,102,.12)"/>
          <circle cx="312" cy="404" r="24" fill="rgba(242,140,40,.16)"/>
          <circle cx="448" cy="208" r="26" fill="rgba(10,132,168,.28)"/>
          <!-- Geographic labels -->
          <text x="260" y="145" fill="#5e4f37" font-size="14" font-weight="800" text-anchor="middle">SOUTH SINAI</text>
          <text x="115" y="310" fill="#9cc6dc" font-size="11" font-weight="700" text-anchor="middle">GULF OF SUEZ</text>
          <text x="538" y="110" fill="#9cc6dc" font-size="11" font-weight="700" text-anchor="middle">GULF OF AQABA</text>
          <text x="488" y="252" fill="#cfe6f2" font-size="11" font-weight="700" text-anchor="middle">Tiran Island</text>
          <text x="315" y="446" fill="#cfe6f2" font-size="11" font-weight="700" text-anchor="middle">Ras Mohammed</text>
          <!-- Sea College Base star -->
          <g transform="translate(376,242)">
            <circle r="7" fill="#25D366" stroke="#fff" stroke-width="2"/>
          </g>
          <text x="364" y="236" fill="#3a2f1d" font-size="10.5" font-weight="800" text-anchor="end">★ Sea College Base</text>

          <!-- Interactive Dive Site Pins -->
          <?php foreach ($siteData['sites'] as $idx => $s): $num = $idx + 1; ?>
            <g class="dmap__pin<?= $idx === 0 ? ' is-active' : '' ?>"
               tabindex="0" role="button"
               aria-label="<?= e($num . '. ' . loc($s['name']) . ' (' . $s['depth'] . ')') ?>"
               data-map-pin="<?= e($s['id']) ?>"
               data-zone="<?= e($s['zone']) ?>"
               transform="translate(<?= (int) $s['map_x'] ?>, <?= (int) $s['map_y'] ?>)">
              <circle class="dmap__pulse" r="16" fill="rgba(224,90,54,.28)"/>
              <circle class="dmap__dot" r="11" fill="#E05A36" stroke="#fff" stroke-width="2"/>
              <text y="4" text-anchor="middle" fill="#fff" font-size="10.5" font-weight="800"><?= $num ?></text>
            </g>
          <?php endforeach; ?>
        </svg>
      </div>

      <div class="dmap__panel" aria-live="polite">
        <p class="dmap__hint">👆 <?= te('sites_map_hint') ?></p>
        <?php foreach ($siteData['sites'] as $idx => $s):
            $linked = isset($flat[$s['item_id']]) ? $flat[$s['item_id']] : null;
        ?>
          <div class="dmap__detail" data-map-detail="<?= e($s['id']) ?>"<?= $idx === 0 ? '' : ' hidden' ?>>
            <span class="badge"><?= e(($idx + 1) . ' · ' . loc($siteData['zones'][$s['zone']] ?? '')) ?></span>
            <h3 dir="auto"><?= e(loc($s['name'])) ?></h3>
            <p class="dmap__specs">
              <span>📏 <bdi dir="ltr"><?= e($s['depth']) ?></bdi></span>
              <span>🪪 <?= e(loc($s['level'])) ?></span>
              <span>🌊 <?= e(loc($s['current'])) ?></span>
            </p>
            <p dir="auto"><?= e(loc($s['summary'])) ?></p>
            <div class="dmap__tags">
              <?php foreach (($s['marine'][LANG] ?? $s['marine']['en']) as $tag): ?>
                <span class="chip"><?= e($tag) ?></span>
              <?php endforeach; ?>
            </div>
            <div class="dmap__actions">
              <a class="btn btn--detail btn--sm" href="#site-<?= e($s['id']) ?>"><?= te('cta_details') ?> ↓</a>
              <?php if ($linked): ?>
                <a class="btn btn--primary btn--sm" href="<?= e(item_url($linked)) ?>"><?= te('sites_book_trip') ?> (<?= e(money($linked['price'])) ?>)</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="site-grid" id="site-cards">
      <?php foreach ($siteData['sites'] as $idx => $s):
          $linked = isset($flat[$s['item_id']]) ? $flat[$s['item_id']] : null;
      ?>
        <article class="site-card card" id="site-<?= e($s['id']) ?>" data-site-card="<?= e($s['id']) ?>" data-zone="<?= e($s['zone']) ?>">
          <div class="site-card__media">
            <?= sc_picture('images/' . $s['img'], loc($s['name']) . ' — ' . t('location_sharm'), 480, 320, ['sizes' => '(max-width: 699px) 92vw, 46vw']) ?>
            <span class="site-card__num">#<?= $idx + 1 ?></span>
            <span class="site-card__depth"><bdi dir="ltr"><?= e($s['depth']) ?></bdi></span>
          </div>
          <div class="site-card__body">
            <p class="site-card__meta"><?= e(loc($s['level'])) ?> · 🌊 <?= e(loc($s['current'])) ?></p>
            <h2 class="site-card__title" dir="auto"><?= e(loc($s['name'])) ?></h2>
            <p class="site-card__desc" dir="auto"><?= e(loc($s['summary'])) ?></p>
            <ul class="site-card__marine">
              <?php foreach (($s['marine'][LANG] ?? $s['marine']['en']) as $tag): ?>
                <li>🐠 <?= e($tag) ?></li>
              <?php endforeach; ?>
            </ul>
            <?php if ($linked): ?>
              <div class="site-card__foot">
                <span><?= te('sites_matched_pkg') ?>: <a href="<?= e(item_url($linked)) ?>"><strong><?= e(item_name($linked)) ?></strong></a> (<?= price_html($linked['price'], $linked['unit'] ?? '') ?>)</span>
                <span class="pcard__actions"><?= add_button($linked) ?></span>
              </div>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--tint" id="conditions-chart">
  <div class="container">
    <?= section_head('seasons_title', 'seasons_sub') ?>
    <div class="ptable-wrap">
      <table class="ptable season-table">
        <caption><?= te('seasons_caption') ?></caption>
        <thead>
          <tr>
            <th><?= te('seasons_col_month') ?></th>
            <th><?= te('seasons_col_water') ?></th>
            <th><?= te('seasons_col_air') ?></th>
            <th><?= te('seasons_col_vis') ?></th>
            <th><?= te('seasons_col_suit') ?></th>
            <th><?= te('seasons_col_highlights') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($siteData['months'] as $row):
              $pct = (int) round((($row['water'] - 20) / 10) * 100);
          ?>
            <tr>
              <th scope="row"><?= e(loc($row['m'])) ?></th>
              <td>
                <div class="temp-meter">
                  <strong><bdi dir="ltr"><?= (int) $row['water'] ?> °C</bdi></strong>
                  <span class="temp-meter__bar"><i style="width:<?= $pct ?>%"></i></span>
                </div>
              </td>
              <td><bdi dir="ltr"><?= (int) $row['air'] ?> °C</bdi></td>
              <td><bdi dir="ltr"><?= e($row['vis']) ?></bdi></td>
              <td dir="auto"><?= e(loc($row['suit'])) ?></td>
              <td dir="auto"><?= e(loc($row['note'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<section class="section" id="faq">
  <div class="container container--narrow">
    <?= section_head('faq_title') ?>
    <?= faq_pairs_html($siteData['faqs'], $siteData['faqs_i18n'] ?? null) ?>
  </div>
</section>

<section class="cta-band">
  <div class="container cta-band__inner">
    <div><h2><?= te('sites_cta_title') ?></h2><p><?= te('sites_cta_sub') ?></p></div>
    <div class="cta-band__btns">
      <a class="btn btn--wa btn--lg" href="<?= e(whatsapp_link('Sharm Dive Sites Plan', 'dive_sites')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="dive_sites"><?php include ROOT_DIR . '/assets/images/icon-whatsapp.svg'; ?><span><?= te('cta_whatsapp') ?></span></a>
      <a class="btn btn--ghost btn--lg" href="<?= e(page_link('/prices', 'dive-calculator')) ?>" data-track="cta_click" data-placement="sites_calc"><?= te('sites_cta_calc') ?></a>
    </div>
  </div>
</section>
<?php require ROOT_DIR . '/includes/footer.php';
