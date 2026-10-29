<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
$errors = is_array($errors ?? null) ? $errors : [];
$old = is_array($old ?? null) ? $old : [];
$phone = contact_phone();
$sent = (($_GET['sent'] ?? '') === '1');
?>
<section class="section">
  <div class="container split-page">
    <div class="prose">
      <h1>Contact</h1>
      <p>Email <a href="mailto:<?= e((string) ppb('business.email')) ?>"><?= e((string) ppb('business.email')) ?></a></p>
      <p>Phone: <?php if ($phone['callable']): ?><a href="tel:<?= e($phone['tel']) ?>"><?= e($phone['display']) ?></a><?php else: ?><span class="placeholder"><?= e($phone['display'] !== '' ? $phone['display'] : '(TBD)') ?></span><?php endif; ?></p>
      <p>For a cash offer, the <a href="/get-offer">offer form</a> is the direct path. Use this note if you have a question first.</p>
      <?php if ($sent): ?><p class="notice" role="status">We received your message and will reply by email.</p><?php endif; ?>
    </div>
    <form class="form-card" method="post" action="/contact">
      <?= csrf_field() ?>
      <div class="hp">
        <label for="website">Leave this field blank</label>
        <input type="text" name="website" id="website" tabindex="-1" autocomplete="off" value="">
      </div>
      <?php if (!empty($errors['_form'])): ?><p class="form-errors" role="alert"><?= e((string) $errors['_form']) ?></p><?php endif; ?>
      <div class="field">
        <label for="full_name">Name</label>
        <input id="full_name" name="full_name" required maxlength="150" value="<?= e((string) ($old['full_name'] ?? '')) ?>">
        <?php if ($msg = field_error($errors, 'full_name')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
      </div>
      <div class="field">
        <label for="phone">Phone</label>
        <input id="phone" name="phone" type="tel" required maxlength="40" value="<?= e((string) ($old['phone'] ?? '')) ?>">
        <?php if ($msg = field_error($errors, 'phone')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
      </div>
      <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required maxlength="190" value="<?= e((string) ($old['email'] ?? '')) ?>">
        <?php if ($msg = field_error($errors, 'email')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
      </div>
      <div class="field">
        <label for="message">Message</label>
        <textarea id="message" name="message" required rows="5" maxlength="2000"><?= e((string) ($old['message'] ?? '')) ?></textarea>
        <?php if ($msg = field_error($errors, 'message')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
      </div>
      <div class="check">
        <input type="checkbox" id="consent" name="consent" value="1" <?= (($old['consent'] ?? '') === '1') ? 'checked' : '' ?>>
        <label for="consent"><?= e(seller_consent_text()) ?></label>
      </div>
      <button class="button" type="submit">Send</button>
    </form>
  </div>
</section>
