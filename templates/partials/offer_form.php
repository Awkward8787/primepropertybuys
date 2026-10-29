<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
$step = (int) ($step ?? 1);
$draft = is_array($draft ?? null) ? $draft : [];
$errors = is_array($errors ?? null) ? $errors : [];
$source = (string) ($source ?? '/get-offer');
$pendingCount = (int) ($pending_count ?? 0);
$labels = [1 => 'Address', 2 => 'Property', 3 => 'Situation', 4 => 'Contact'];
$val = static function (string $key) use ($draft): string {
    $value = $draft[$key] ?? '';
    return is_scalar($value) ? (string) $value : '';
};
?>
<form class="offer-form" method="post" action="/get-offer"<?= $step === 4 ? ' enctype="multipart/form-data"' : '' ?>>
  <?= csrf_field() ?>
  <input type="hidden" name="step" value="<?= $step ?>">
  <input type="hidden" name="source" value="<?= e($source) ?>">
  <div class="hp">
    <label for="website">Leave this field blank</label>
    <input type="text" name="website" id="website" tabindex="-1" autocomplete="off" value="">
  </div>
  <p class="form-kicker" id="offer">Get my cash offer</p>
  <p class="step-label">Step <?= $step ?> of 4 · <?= e($labels[$step] ?? '') ?></p>
  <div class="bar bar-<?= $step ?>" role="progressbar" aria-valuemin="1" aria-valuemax="4" aria-valuenow="<?= $step ?>" aria-label="Form progress">
    <span></span>
  </div>
  <?php if (!empty($errors['_form'])): ?>
    <p class="form-errors" role="alert"><?= e((string) $errors['_form']) ?></p>
  <?php elseif ($errors): ?>
    <p class="form-errors" role="alert">Check the highlighted fields and try again.</p>
  <?php endif; ?>

  <?php if ($step === 1): ?>
    <div class="field">
      <label for="address">Property address <span class="req" aria-hidden="true">*</span><span class="sr-only"> required</span></label>
      <input id="address" name="address" data-address required maxlength="300" autocomplete="street-address" value="<?= e($val('address')) ?>" <?= field_error($errors, 'address') ? 'aria-invalid="true"' : '' ?>>
      <?php if ($msg = field_error($errors, 'address')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
    </div>
    <div class="form-row two">
      <div class="field">
        <label for="city">Town <span class="req" aria-hidden="true">*</span><span class="sr-only"> required</span></label>
        <select id="city" name="city" data-town required>
          <?= options_html(service_towns(), $val('city'), true, 'Select') ?>
        </select>
        <?php if ($msg = field_error($errors, 'city')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
      </div>
      <div class="field">
        <label for="zip">ZIP</label>
        <input id="zip" name="zip" inputmode="numeric" maxlength="10" autocomplete="postal-code" value="<?= e($val('zip')) ?>">
        <?php if ($msg = field_error($errors, 'zip')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
      </div>
    </div>
    <p class="fine">If the address includes the town, we fill that in. Lowndes County covers addresses outside a city.</p>
  <?php elseif ($step === 2): ?>
    <div class="field">
      <label for="property_type">Property type <span class="req" aria-hidden="true">*</span></label>
      <select id="property_type" name="property_type" required><?= options_html(property_types(), $val('property_type'), true) ?></select>
      <?php if ($msg = field_error($errors, 'property_type')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
    </div>
    <div class="form-row two">
      <div class="field">
        <label for="beds">Bedrooms <span class="req" aria-hidden="true">*</span></label>
        <select id="beds" name="beds" required><?= options_html(bed_options(), $val('beds'), true) ?></select>
        <?php if ($msg = field_error($errors, 'beds')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
      </div>
      <div class="field">
        <label for="baths">Bathrooms <span class="req" aria-hidden="true">*</span></label>
        <select id="baths" name="baths" required><?= options_html(bath_options(), $val('baths'), true) ?></select>
        <?php if ($msg = field_error($errors, 'baths')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
      </div>
    </div>
    <div class="field">
      <label for="condition_label">Condition <span class="req" aria-hidden="true">*</span></label>
      <select id="condition_label" name="condition_label" required><?= options_html(condition_options(), $val('condition_label'), true) ?></select>
      <?php if ($msg = field_error($errors, 'condition_label')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
    </div>
    <div class="field">
      <label for="occupancy">Who is there now <span class="req" aria-hidden="true">*</span></label>
      <select id="occupancy" name="occupancy" required><?= options_html(occupancy_options(), $val('occupancy'), true) ?></select>
      <?php if ($msg = field_error($errors, 'occupancy')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
    </div>
  <?php elseif ($step === 3): ?>
    <div class="field">
      <label for="timeline">Timeline <span class="req" aria-hidden="true">*</span></label>
      <select id="timeline" name="timeline" required><?= options_html(timeline_options(), $val('timeline'), true) ?></select>
      <?php if ($msg = field_error($errors, 'timeline')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
    </div>
    <div class="field">
      <label for="reason">Reason for selling</label>
      <select id="reason" name="reason"><?= options_html(reason_options(), $val('reason'), true, 'Optional') ?></select>
    </div>
    <div class="field">
      <label for="asking_price">Asking price, if you have one</label>
      <input id="asking_price" name="asking_price" inputmode="numeric" value="<?= e($val('asking_price_raw')) ?>" placeholder="Optional">
      <?php if ($msg = field_error($errors, 'asking_price')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
    </div>
  <?php else: ?>
    <div class="field">
      <label for="full_name">Your name <span class="req" aria-hidden="true">*</span></label>
      <input id="full_name" name="full_name" required autocomplete="name" maxlength="150" value="<?= e($val('full_name')) ?>">
      <?php if ($msg = field_error($errors, 'full_name')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
    </div>
    <div class="form-row two">
      <div class="field">
        <label for="phone">Phone <span class="req" aria-hidden="true">*</span></label>
        <input id="phone" name="phone" type="tel" required autocomplete="tel" maxlength="40" value="<?= e($val('phone')) ?>">
        <?php if ($msg = field_error($errors, 'phone')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
      </div>
      <div class="field">
        <label for="email">Email <span class="req" aria-hidden="true">*</span></label>
        <input id="email" name="email" type="email" required autocomplete="email" maxlength="190" value="<?= e($val('email')) ?>">
        <?php if ($msg = field_error($errors, 'email')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
      </div>
    </div>
    <div class="field">
      <label for="best_time">Best time to reach you <span class="req" aria-hidden="true">*</span></label>
      <select id="best_time" name="best_time" required><?= options_html(best_time_options(), $val('best_time'), true) ?></select>
      <?php if ($msg = field_error($errors, 'best_time')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
    </div>
    <div class="field">
      <label for="photos">Photos, optional</label>
      <input id="photos" name="photos[]" type="file" accept="image/jpeg,image/png,image/webp,image/heic,.heic,.heif" multiple>
      <p class="fine">Up to 10 photos, 10 MB each. JPG, PNG, WEBP, or HEIC.<?php if ($pendingCount > 0): ?> <?= (int) $pendingCount ?> already attached.<?php endif; ?></p>
      <?php if ($msg = field_error($errors, 'photos')): ?><p class="field-error"><?= e($msg) ?></p><?php endif; ?>
    </div>
    <div class="check">
      <input type="checkbox" id="consent" name="consent" value="1" <?= (($_POST['consent'] ?? '') === '1') ? 'checked' : '' ?>>
      <label for="consent"><?= e(seller_consent_text()) ?></label>
    </div>
  <?php endif; ?>

  <div class="form-actions">
    <?php if ($step > 1): ?>
      <a class="button button-quiet" href="/get-offer?step=<?= $step - 1 ?>">Back</a>
    <?php endif; ?>
    <button class="button" type="submit"><?= $step === 4 ? 'Get my cash offer' : 'Continue' ?></button>
  </div>
</form>
