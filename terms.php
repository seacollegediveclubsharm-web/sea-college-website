<?php
/** Terms & Conditions — text supplied by the owner (Sea_college_Terms_&_conditions.docx). */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require ROOT_DIR . '/includes/components.php';

$page['id'] = 'terms';
$page['canonical_path'] = '/terms';
$page['title'] = t('meta_terms_title');
$page['description'] = t('meta_terms_desc');
$page['breadcrumbs'] = [[t('breadcrumb_home'), '/'], [t('footer_terms'), '/terms']];

require ROOT_DIR . '/includes/header.php';
?>
<section class="page-hero"><div class="container"><h1><?= te('footer_terms') ?></h1></div></section>
<section class="section">
  <div class="container container--narrow prose" lang="en" dir="ltr">
    <?php if (t('legal_en_only') !== ''): ?><p class="alert" lang="<?= e(LANG) ?>" dir="<?= e(DIR) ?>"><?= te('legal_en_only') ?></p><?php endif; ?>
    <p>Welcome to Sea College Dive Club! We're thrilled you're considering exploring the wonders of the Red Sea with us. These Terms &amp; Conditions ("Terms") outline the agreement between Sea College Dive Club ("Sea College," "we," "us," or "our") and you ("you," "your," or "client").</p>
    <p>By using our website, booking a trip, course, or tour, or participating in any of our services, you agree to these Terms.</p>

    <h2>1. Introduction</h2>
    <p>Sea College Dive Club offers a range of diving and snorkeling experiences in Sharm El Sheikh, Egypt. We provide daily trips, private tours, and PADI-certified courses to divers of all levels. Our qualified instructors and local knowledge ensure a safe and unforgettable underwater adventure.</p>

    <h2>2. Booking &amp; Payment</h2>
    <ul>
      <li><strong>Bookings:</strong> You can book online through our website or contact us directly.</li>
      <li><strong>Confirmation:</strong> All bookings are confirmed within 48 hours.</li>
      <li><strong>Payment:</strong> Payment can be made online, at our dive center, or a combination of both, depending on the service booked.</li>
      <li><strong>Prices:</strong> Prices listed on our website or quoted via email include all service charges and taxes.</li>
    </ul>

    <h2>3. Cancellation Policy</h2>
    <ul>
      <li>We understand unforeseen circumstances may arise. However, cancellations impact our operations.</li>
      <li>Please refer to our cancellation policy available on our website or communicated during booking for specific details on cancellation fees.</li>
      <li>Cancellations made within a certain timeframe (e.g., 24 hours) may result in a full or partial cancellation fee.</li>
    </ul>

    <h2>4. Medical Requirements</h2>
    <ul>
      <li>Diving safety is paramount.</li>
      <li>All participants must be in good health and complete a medical questionnaire before diving.</li>
      <li>If you have any pre-existing medical conditions, a doctor's clearance may be required.</li>
    </ul>

    <h2>5. Disclaimer of Liability</h2>
    <ul>
      <li>While we take all reasonable precautions for your safety, diving inherently involves risks.</li>
      <li>You participate in our activities at your own risk.</li>
      <li>Sea College Dive Club is not liable for any injury, loss, or damage arising from participation in our services.</li>
    </ul>

    <h2>6. Equipment Rental</h2>
    <ul>
      <li>We offer a variety of rental equipment.</li>
      <li>Participants are responsible for inspecting rented equipment and notifying us of any damage or malfunction before use.</li>
      <li>Lost or damaged equipment may incur a replacement fee.</li>
    </ul>

    <h2>7. Photography &amp; Videography</h2>
    <ul>
      <li>We may take photos &amp; videos during your trip/course for promotional purposes.</li>
      <li>If you prefer not to be included, please inform us in writing.</li>
    </ul>

    <h2 id="copyright">8. Intellectual Property, Copyright &amp; Anti-Scraping Protection</h2>
    <ul>
      <li><strong>Exclusive Ownership:</strong> All content on this website — including text in all 8 languages, course and tour descriptions, pricing tables, interactive calculators, dive site maps, custom code, logos, and original underwater, boat fleet (<em>Oceano</em>, <em>Nizar</em>, <em>Omar 4</em>, and RIB Speedboats), and dive center photography and videography — is the exclusive intellectual property of <strong>Sea College Dive Club</strong> (© <?= e(BUSINESS['founded'] . '–' . date('Y')) ?> Sea College Dive Club, All Rights Reserved) and is protected under Egyptian Copyright Law No. 82 of 2002 and international copyright treaties (Berne Convention &amp; DMCA).</li>
      <li><strong>Strict Prohibition of Copying &amp; Scraping:</strong> Any unauthorized copying, reproduction, translation, mirroring, hotlinking, automated scraping, or commercial reuse of our text, photographs, videos, or code on any third-party website, social media page, or OTA listing without prior written consent from Sea College Dive Club is strictly prohibited and will result in immediate DMCA takedown notices and legal enforcement.</li>
    </ul>

    <h2>9. Governing Law</h2>
    <p>These Terms are governed by the laws of Egypt.</p>

    <h2>10. Modifications to Terms &amp; Conditions</h2>
    <p>We reserve the right to modify these Terms at any time. Updated Terms will be posted on our website.</p>

    <h2>11. Contact Us</h2>
    <p>If you have any questions regarding these Terms, please contact us at <a href="mailto:<?= e(BUSINESS['email']) ?>"><?= e(BUSINESS['email']) ?></a>, by phone at <a href="tel:<?= e(BUSINESS['phone']) ?>" data-track="phone_click"><bdi dir="ltr"><?= e(BUSINESS['phone_display']) ?></bdi></a>, or on <a href="<?= e(whatsapp_link('', 'terms')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-placement="terms">WhatsApp (<bdi dir="ltr"><?= e(BUSINESS['phone_display']) ?></bdi>)</a>.</p>
  </div>
</section>
<?php require ROOT_DIR . '/includes/footer.php';
