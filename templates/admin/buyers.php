<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
?>
<div class="admin-head">
  <h1>Buyers</h1>
  <p><a href="/admin/export/buyers.csv">Export buyers</a> · <a href="/admin/export/boxes.csv">Export buy boxes</a></p>
</div>
<?php if (!empty($saved)): ?><p class="notice" role="status"><?= e($saved) ?></p><?php endif; ?>
<?php if (!empty($error)): ?><p class="form-errors" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if (!$buyers): ?>
  <p>No buyers yet.</p>
<?php else: ?>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Company</th><th>Type</th><th>Tier</th><th>Contact</th><th>Opt-in</th><th>Proof of funds</th></tr></thead>
      <tbody>
        <?php foreach ($buyers as $buyer): ?>
          <tr>
            <td><a href="/admin/buyers/<?= (int) $buyer['id'] ?>"><?= e((string) $buyer['company']) ?></a></td>
            <td><?= e(option_label(buyer_type_options(), (string) $buyer['buyer_type'])) ?></td>
            <td><?= e((string) $buyer['tier']) ?></td>
            <td><?= e((string) $buyer['contact_name']) ?><br><?= e((string) $buyer['email']) ?></td>
            <td><?= (int) $buyer['email_opt_in'] === 1 && empty($buyer['unsubscribed_at']) ? 'Yes' : 'No' ?></td>
            <td><?= (int) $buyer['pof_on_file'] === 1 ? 'Yes' : 'No' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<details class="admin-panel">
  <summary>Add a buyer</summary>
  <form method="post" action="/admin/buyers">
    <?= csrf_field() ?>
    <div class="form-row two">
      <div class="field"><label for="company">Company</label><input id="company" name="company" required></div>
      <div class="field"><label for="contact_name">Contact</label><input id="contact_name" name="contact_name" required></div>
      <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" required></div>
      <div class="field"><label for="phone">Phone</label><input id="phone" name="phone" required></div>
      <div class="field"><label for="buyer_type">Type</label><select id="buyer_type" name="buyer_type"><?= options_html(buyer_type_options(), 'local') ?></select></div>
      <div class="field"><label for="tier">Tier</label><select id="tier" name="tier"><option value="A">A</option><option value="B">B</option><option value="C" selected>C</option></select></div>
    </div>
    <div class="field"><label for="areas">Buy box areas</label><textarea id="areas" name="areas" required rows="2"></textarea></div>
    <fieldset class="field">
      <legend>Property types</legend>
      <?php foreach (property_types() as $key => $label): ?>
        <label class="check-inline"><input type="checkbox" name="property_types[]" value="<?= e((string) $key) ?>"> <?= e($label) ?></label>
      <?php endforeach; ?>
    </fieldset>
    <div class="form-row two">
      <div class="field"><label for="price_min">Price min</label><input id="price_min" name="price_min" required></div>
      <div class="field"><label for="price_max">Price max</label><input id="price_max" name="price_max" required></div>
      <div class="field"><label for="min_beds">Min beds</label><input id="min_beds" name="min_beds"></div>
      <div class="field"><label for="closings_count">Closings with us</label><input id="closings_count" name="closings_count" value="0"></div>
    </div>
    <label class="check-inline"><input type="checkbox" name="pof_on_file" value="1"> Proof of funds on file</label>
    <div class="field"><label for="pof_date">Proof of funds date</label><input id="pof_date" type="date" name="pof_date"></div>
    <label class="check-inline"><input type="checkbox" name="email_opt_in" value="1"> Email opt-in</label>
    <div class="field"><label for="notes">Notes</label><textarea id="notes" name="notes" rows="2"></textarea></div>
    <button class="button" type="submit">Save buyer</button>
  </form>
</details>
