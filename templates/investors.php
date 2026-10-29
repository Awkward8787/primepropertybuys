<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
$errors = is_array($errors ?? null) ? $errors : [];
$old = is_array($old ?? null) ? $old : [];
$sent = (($_GET['sent'] ?? '') === '1');
$selected = $old['property_types'] ?? [];
if (!is_array($selected)) {
    $selected = [];
}
?>
<h1>Buyer list</h1>
<p>Institutional buyers and other investors can send a buy box. We email you when a property fits, if you opt in. This does not obligate you to purchase anything.</p>
<?php if ($sent): ?>
  <p class="notice" role="status">We received your buy box.</p>
<?php else: ?>
<form method="post" action="/investors" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="hp">
    <label for="website">Leave this field blank</label>
    <input type="text" name="website" id="website" tabindex="-1" autocomplete="off" value="">
  </div>
  <?php if (!empty($errors['_form'])): ?><p class="form-errors" role="alert"><?= e((string) $errors['_form']) ?></p><?php endif; ?>
  <div class="field">
    <label for="company">Company or fund</label>
    <input id="company" name="company" required maxlength="200" value="<?= e((string) ($old['company'] ?? '')) ?>">
    <?php if ($msg = field_error($errors, 'company')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
  </div>
  <div class="field">
    <label for="contact_name">Contact name</label>
    <input id="contact_name" name="contact_name" required maxlength="150" value="<?= e((string) ($old['contact_name'] ?? '')) ?>">
    <?php if ($msg = field_error($errors, 'contact_name')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
  </div>
  <div class="field">
    <label for="email">Email</label>
    <input id="email" name="email" type="email" required maxlength="190" value="<?= e((string) ($old['email'] ?? '')) ?>">
    <?php if ($msg = field_error($errors, 'email')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
  </div>
  <div class="field">
    <label for="phone">Phone</label>
    <input id="phone" name="phone" type="tel" required maxlength="40" value="<?= e((string) ($old['phone'] ?? '')) ?>">
    <?php if ($msg = field_error($errors, 'phone')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
  </div>
  <div class="field">
    <label for="buyer_type">Type</label>
    <select id="buyer_type" name="buyer_type"><?= options_html(buyer_type_options(), (string) ($old['buyer_type'] ?? 'local')) ?></select>
  </div>
  <div class="field">
    <label for="areas">Towns or ZIP codes</label>
    <textarea id="areas" name="areas" required rows="3"><?= e((string) ($old['areas'] ?? '')) ?></textarea>
    <?php if ($msg = field_error($errors, 'areas')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
  </div>
  <div class="form-row two">
    <div class="field">
      <label for="price_min">Minimum price</label>
      <input id="price_min" name="price_min" inputmode="numeric" required value="<?= e((string) ($old['price_min'] ?? '')) ?>">
      <?php if ($msg = field_error($errors, 'price_min')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
    </div>
    <div class="field">
      <label for="price_max">Maximum price</label>
      <input id="price_max" name="price_max" inputmode="numeric" required value="<?= e((string) ($old['price_max'] ?? '')) ?>">
      <?php if ($msg = field_error($errors, 'price_max')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
    </div>
  </div>
  <fieldset class="field">
    <legend>Property types</legend>
    <?php foreach (property_types() as $key => $label): ?>
      <label class="check-inline"><input type="checkbox" name="property_types[]" value="<?= e((string) $key) ?>" <?= in_array((string) $key, $selected, true) ? 'checked' : '' ?>> <?= e($label) ?></label>
    <?php endforeach; ?>
    <?php if ($msg = field_error($errors, 'property_types')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
  </fieldset>
  <div class="field">
    <label for="min_beds">Minimum bedrooms</label>
    <input id="min_beds" name="min_beds" inputmode="numeric" value="<?= e((string) ($old['min_beds'] ?? '')) ?>">
  </div>
  <div class="field">
    <label for="pof">Proof of funds, optional</label>
    <input id="pof" name="pof[]" type="file" accept="image/jpeg,image/png,image/webp,application/pdf,.pdf,.heic" multiple>
  </div>
  <div class="check">
    <input type="checkbox" id="email_opt_in" name="email_opt_in" value="1" <?= (($old['email_opt_in'] ?? '') === '1') ? 'checked' : '' ?>>
    <label for="email_opt_in">Email me when a property matches this buy box. I can unsubscribe from any message.</label>
  </div>
  <div class="check">
    <input type="checkbox" id="consent" name="consent" value="1" <?= (($old['consent'] ?? '') === '1') ? 'checked' : '' ?>>
    <label for="consent"><?= e(buyer_consent_text()) ?></label>
  </div>
  <button class="button" type="submit">Join the list</button>
</form>
<?php endif; ?>
