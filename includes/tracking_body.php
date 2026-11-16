<?php
/** Tracking (body open). GTM noscript fallback only — pixels load via consent.js. */
declare(strict_types=1);
if (GTM_ID !== ''): ?>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= e(GTM_ID) ?>" height="0" width="0" style="display:none;visibility:hidden" title="GTM"></iframe></noscript>
<?php endif;
