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
    <h1>Privacy policy</h1>
    <p>Effective October 28, 2026. This page describes how this website handles information. It is not a substitute for advice from your own attorney.</p>
    <h2>Who we are</h2>
    <p><?= e((string) ppb('business.name')) ?>, operated by <?= e((string) ppb('business.owner')) ?> in Valdosta and Lowndes County, Georgia, part of <?= e((string) ppb('business.parent')) ?>. Legal entity: <?= e(legal_entity_label()) ?>. Email <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>.</p>
    <h2>What we collect</h2>
    <p>If you use a form, we collect the fields you submit, whether you checked the call-and-text box, the text of that box, the time, your IP address, and the browser type. Photos you attach are stored with the request.</p>
    <h2>How we use it</h2>
    <p>We use it to reply, to decide whether to make a cash offer, and to keep a record of the request. We do not sell your personal information.</p>
    <h2>Calls and texts</h2>
    <p>We call or text only if you check the consent box. That box is optional and is not required to receive an offer. You can reply STOP to opt out of texts. Message and data rates may apply.</p>
    <h2>Who else sees it</h2>
    <p>A closing attorney or title company may receive what they need to close a sale you agree to in writing. We also use the email service on our web host to send messages. We do not publish your phone number or email address.</p>
    <h2>How long we keep it</h2>
    <p>We keep inquiry records while we need them to respond, to complete a transaction you agree to, and to meet ordinary recordkeeping needs. Email <?= e($email) ?> if you want to know what we have on file.</p>
  </div>
</section>
