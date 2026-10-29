<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
$url = booking_url();
?>
<section class="section">
  <div class="container prose">
    <h1>We received your request</h1>
    <p>We will review the property and reply by email from <?= e((string) ppb('business.email')) ?>. If you agreed to calls or texts, we may also use the number you entered. If you did not, we will stick to email.</p>
    <h2>What happens next</h2>
    <ol>
      <li>We read the address, condition, and timing.</li>
      <li>We may ask for a walkthrough or more photos.</li>
      <li>You get a written cash offer. You can decline it.</li>
    </ol>
    <p><?php if ($url !== ''): ?><a href="<?= e($url) ?>">Schedule a walkthrough</a><?php else: ?>Scheduling: <span class="placeholder"><?= e(booking_label()) ?></span><?php endif; ?></p>
    <p>Phone: <span class="<?= contact_phone()['callable'] ? '' : 'placeholder' ?>"><?= e(contact_phone()['display'] !== '' ? contact_phone()['display'] : '(TBD)') ?></span></p>
  </div>
</section>
