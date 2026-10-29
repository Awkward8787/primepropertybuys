<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
$email = (string) ppb('business.email');
?>
<section class="section">
  <div class="container prose">
    <h1>Terms</h1>
    <p>Effective October 28, 2026. These terms cover use of this website. They are not a substitute for advice from your own attorney.</p>
    <h2>How a purchase can close</h2>
    <p><?= e(disclosure_line()) ?></p>
    <p>We include that sentence as our own practice, on this page and in the footer. It is not a claim that Georgia has a statute requiring a particular disclosure. Any purchase is only in a written contract you choose to sign. Requesting an offer on this website does not obligate you or us. <?= e(brokerage_disclosure()) ?></p>
    <h2>Offers</h2>
    <p>An offer is not an appraisal and not a promise of a particular price or closing date. A cash price is often below a retail asking price. Read the contract before you sign, and ask a Georgia closing attorney if you want independent advice.</p>
    <h2>Your submissions</h2>
    <p>You agree that the information you send is yours to share and that the photos, if any, show the property you named.</p>
    <h2>Calls and texts</h2>
    <p>The consent checkbox is optional. If you check it, you agree we may call or text the number you entered about your property, including by automated means. Consent is not required to get an offer. Message and data rates may apply. Reply STOP to opt out.</p>
    <h2>Acceptable use</h2>
    <p>Do not misuse the forms, attempt to break the site, or send someone else’s information without their permission.</p>
    <h2>Georgia</h2>
    <p>These terms are governed by the laws of the State of Georgia. Questions: <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>.</p>
  </div>
</section>
