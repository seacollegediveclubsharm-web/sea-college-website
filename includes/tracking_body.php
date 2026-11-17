<?php
/** Tracking (body open). GTM + consent-gated Meta Pixel noscript fallback — JS pixels load via consent.js. */
declare(strict_types=1);
if (GTM_ID !== ''): ?>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= e(GTM_ID) ?>" height="0" width="0" style="display:none;visibility:hidden" title="GTM"></iframe></noscript>
<?php endif;
if (ENABLE_META_PIXEL && META_PIXEL_ID !== '' && consent_state() === 'all'): ?>
<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?= e(rawurlencode(META_PIXEL_ID)) ?>&amp;ev=PageView&amp;noscript=1" alt=""></noscript>
<?php endif;
