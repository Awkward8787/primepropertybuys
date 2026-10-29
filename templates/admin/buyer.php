<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
/** @var array $buyer */
?>
<p class="fine"><a href="/admin/buyers">Buyers</a></p>
<h1><?= e((string) $buyer['company']) ?></h1>
<?php if (!empty($saved)): ?><p class="notice" role="status"><?= e($saved) ?></p><?php endif; ?>
<?php if (!empty($error)): ?><p class="form-errors" role="alert"><?= e($error) ?></p><?php endif; ?>
<form class="admin-panel" method="post" action="/admin/buyers/<?= (int) $buyer['id'] ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save">
  <div class="form-row two">
    <div class="field"><label for="company">Company</label><input id="company" name="company" required value="<?= e((string) $buyer['company']) ?>"></div>
    <div class="field"><label for="contact_name">Contact</label><input id="contact_name" name="contact_name" required value="<?= e((string) $buyer['contact_name']) ?>"></div>
    <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" required value="<?= e((string) $buyer['email']) ?>"></div>
    <div class="field"><label for="phone">Phone</label><input id="phone" name="phone" required value="<?= e((string) $buyer['phone']) ?>"></div>
    <div class="field"><label for="buyer_type">Type</label><select id="buyer_type" name="buyer_type"><?= options_html(buyer_type_options(), (string) $buyer['buyer_type']) ?></select></div>
    <div class="field"><label for="tier">Tier</label>
      <select id="tier" name="tier">
        <?php foreach (['A', 'B', 'C'] as $tier): ?>
          <option value="<?= e($tier) ?>"<?= $buyer['tier'] === $tier ? ' selected' : '' ?>><?= e($tier) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field"><label for="closings_count">Closings with us</label><input id="closings_count" name="closings_count" value="<?= (int) $buyer['closings_count'] ?>"></div>
    <div class="field"><label for="pof_date">Proof of funds date</label><input id="pof_date" type="date" name="pof_date" value="<?= e((string) ($buyer['pof_date'] ?? '')) ?>"></div>
  </div>
  <label class="check-inline"><input type="checkbox" name="pof_on_file" value="1" <?= (int) $buyer['pof_on_file'] === 1 ? 'checked' : '' ?>> Proof of funds on file</label>
  <label class="check-inline"><input type="checkbox" name="email_opt_in" value="1" <?= (int) $buyer['email_opt_in'] === 1 ? 'checked' : '' ?>> Email opt-in</label>
  <div class="field"><label for="areas">Areas (kept on the existing buy box; add another below)</label><textarea id="areas" name="areas" required><?= e((string) (($boxes[0]['areas'] ?? '') ?: 'See boxes')) ?></textarea></div>
  <input type="hidden" name="price_min" value="<?= (int) ($boxes[0]['price_min'] ?? 0) ?>">
  <input type="hidden" name="price_max" value="<?= (int) ($boxes[0]['price_max'] ?? 0) ?>">
  <?php
    $storedTypes = array_map('trim', explode(',', (string) ($boxes[0]['property_types'] ?? '')));
    foreach (property_types() as $key => $label):
        $checked = in_array($label, $storedTypes, true) || in_array((string) $key, $storedTypes, true);
  ?>
    <input type="hidden" name="property_types[]" value="<?= e((string) $key) ?>" <?= $checked ? '' : 'disabled' ?>>
  <?php endforeach; ?>
  <?php if (!$storedTypes || $storedTypes === ['']) : ?>
    <input type="hidden" name="property_types[]" value="single_family">
  <?php endif; ?>
  <div class="field"><label for="notes">Notes</label><textarea id="notes" name="notes" rows="3"><?= e((string) $buyer['notes']) ?></textarea></div>
  <button class="button" type="submit">Save buyer</button>
</form>
<p class="fine">Saving requires a price range. Hidden fields keep the first buy box’s prices so this form can update the contact record. Add or remove boxes below.</p>

<section class="admin-panel">
  <h2>Buy boxes</h2>
  <?php foreach ($boxes as $box): ?>
    <article class="note">
      <p><?= e((string) $box['areas']) ?> · <?= e((string) $box['property_types']) ?>
        <?php if ($box['price_min'] !== null): ?> · <?= e(format_money((int) $box['price_min'])) ?>–<?= e(format_money((int) ($box['price_max'] ?? 0))) ?><?php endif; ?>
        <?php if ($box['min_beds'] !== null): ?> · <?= (int) $box['min_beds'] ?>+ beds<?php endif; ?>
        · occupancy <?= e((string) $box['occupancy_ok']) ?></p>
      <form method="post" action="/admin/buyers/<?= (int) $buyer['id'] ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete_box">
        <input type="hidden" name="box_id" value="<?= (int) $box['id'] ?>">
        <button class="button button-quiet" type="submit">Remove</button>
      </form>
    </article>
  <?php endforeach; ?>
  <form method="post" action="/admin/buyers/<?= (int) $buyer['id'] ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="box">
    <div class="field"><label for="box_areas">Areas</label><textarea id="box_areas" name="areas" required rows="2"></textarea></div>
    <div class="form-row two">
      <div class="field"><label for="box_min">Price min</label><input id="box_min" name="price_min"></div>
      <div class="field"><label for="box_max">Price max</label><input id="box_max" name="price_max"></div>
      <div class="field"><label for="box_beds">Min beds</label><input id="box_beds" name="min_beds"></div>
      <div class="field"><label for="property_types">Property types</label><input id="property_types" name="property_types" placeholder="Single-family, multifamily"></div>
      <div class="field"><label for="occupancy_ok">Occupancy</label>
        <select id="occupancy_ok" name="occupancy_ok">
          <option value="any">Any</option>
          <option value="vacant">Vacant only</option>
          <option value="occupied">Occupied OK</option>
        </select>
      </div>
    </div>
    <button class="button" type="submit">Add buy box</button>
  </form>
</section>
