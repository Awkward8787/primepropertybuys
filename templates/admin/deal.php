<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
/** @var array $deal */
$money = static function ($value): string {
    return $value === null || $value === '' ? '' : (string) (int) $value;
};
$priceGuess = $deal['contract_price'] ?: ($deal['offer_amt'] ?: $deal['asking_price']);
?>
<p class="fine"><a href="/admin/deals">Pipeline</a></p>
<h1><?= e((string) $deal['address']) ?></h1>
<p><?= e((string) $deal['city']) ?> · <?= e((string) $deal['full_name']) ?> · <?= e((string) $deal['phone']) ?> · <?= e((string) $deal['email']) ?></p>
<p><?php if ((int) $deal['tcpa_consent'] === 1): ?><span class="badge badge-yes">Call and text OK</span><?php else: ?><span class="badge badge-no">Email only — do not call or text</span><?php endif; ?></p>
<?php if (!empty($saved)): ?><p class="notice" role="status"><?= e($saved) ?></p><?php endif; ?>
<?php if (!empty($error)): ?><p class="form-errors" role="alert"><?= e($error) ?></p><?php endif; ?>

<section class="admin-panel">
  <h2>Property</h2>
  <p><?= e((string) $deal['property_type']) ?> · <?= e((string) $deal['beds']) ?> bed · <?= e((string) $deal['baths']) ?> bath · <?= e((string) $deal['condition_label']) ?> · <?= e((string) $deal['occupancy']) ?></p>
  <p>Timeline: <?= e((string) $deal['timeline']) ?><?php if ($deal['reason'] !== ''): ?> · Reason: <?= e((string) $deal['reason']) ?><?php endif; ?><?php if ($deal['asking_price'] !== null): ?> · Asking <?= e(format_money((int) $deal['asking_price'])) ?><?php endif; ?></p>
  <p>Best time: <?= e((string) $deal['best_time']) ?> · From <?= e((string) $deal['source_path']) ?></p>
  <?php if ($photos): ?>
    <div class="photo-row">
      <?php foreach ($photos as $photo): ?>
        <?php if (displayable_mime((string) $photo['mime'])): ?>
          <img src="/admin/media/<?= (int) $photo['id'] ?>" alt="Photo from the seller">
        <?php else: ?>
          <a href="/admin/media/<?= (int) $photo['id'] ?>">Download file</a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<form class="admin-panel" method="post" action="/admin/deals/<?= (int) $deal['id'] ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save">
  <h2>Deal</h2>
  <div class="form-row two">
    <div class="field">
      <label for="stage">Stage</label>
      <select id="stage" name="stage"><?= options_html(deal_stages(), (string) $deal['stage']) ?></select>
    </div>
    <div class="field">
      <label for="dead_reason">Dead reason</label>
      <input id="dead_reason" name="dead_reason" value="<?= e((string) $deal['dead_reason']) ?>">
    </div>
  </div>
  <p class="fine">Assigned/Marketed stays inside this screen. Seller emails for that stage use the under-contract note and do not name the stage.</p>
  <div class="form-row two">
    <div class="field"><label for="arv">ARV</label><input id="arv" name="arv" inputmode="numeric" value="<?= e($money($deal['arv'])) ?>"></div>
    <div class="field"><label for="repair_est">Repair estimate</label><input id="repair_est" name="repair_est" inputmode="numeric" value="<?= e($money($deal['repair_est'])) ?>"></div>
    <div class="field"><label for="mao">MAO</label><input id="mao" name="mao" inputmode="numeric" value="<?= e($money($deal['mao'])) ?>"></div>
    <div class="field"><label for="offer_amt">Offer amount</label><input id="offer_amt" name="offer_amt" inputmode="numeric" value="<?= e($money($deal['offer_amt'])) ?>"></div>
    <div class="field"><label for="contract_price">Contract price</label><input id="contract_price" name="contract_price" inputmode="numeric" value="<?= e($money($deal['contract_price'])) ?>"></div>
    <div class="field"><label for="assignment_fee">Assignment fee (internal)</label><input id="assignment_fee" name="assignment_fee" inputmode="numeric" value="<?= e($money($deal['assignment_fee'])) ?>"></div>
  </div>
  <div class="form-row two">
    <div class="field"><label for="contract_date">Contract date</label><input id="contract_date" type="date" name="contract_date" value="<?= e((string) ($deal['contract_date'] ?? '')) ?>"></div>
    <div class="field"><label for="inspection_deadline">Inspection deadline</label><input id="inspection_deadline" type="date" name="inspection_deadline" value="<?= e((string) ($deal['inspection_deadline'] ?? '')) ?>"></div>
    <div class="field"><label for="closing_date">Closing date</label><input id="closing_date" type="date" name="closing_date" value="<?= e((string) ($deal['closing_date'] ?? '')) ?>"></div>
    <div class="field"><label for="title_company">Title company or closing attorney</label><input id="title_company" name="title_company" value="<?= e((string) $deal['title_company']) ?>"></div>
  </div>
  <div class="field">
    <label for="access_notes">Internal access notes</label>
    <textarea id="access_notes" name="access_notes" rows="3"><?= e((string) $deal['access_notes']) ?></textarea>
  </div>
  <button class="button" type="submit">Save deal</button>
</form>

<section class="admin-panel">
  <h2>Seller emails</h2>
  <form method="post" action="/admin/deals/<?= (int) $deal['id'] ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="email">
    <input type="hidden" name="kind" value="walkthrough">
    <button class="button button-quiet" type="submit">Send walkthrough email</button>
  </form>
  <form method="post" action="/admin/deals/<?= (int) $deal['id'] ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="email">
    <input type="hidden" name="kind" value="closing">
    <button class="button button-quiet" type="submit">Send closing email</button>
  </form>
</section>

<section class="admin-panel">
  <h2>Stage history</h2>
  <ul>
    <?php foreach ($history as $item): ?>
      <li><?= e(format_when((string) $item['changed_at'])) ?> · <?= e((string) ($item['from_stage'] !== '' ? (deal_stages()[$item['from_stage']] ?? $item['from_stage']) : '—')) ?> → <?= e(deal_stages()[$item['to_stage']] ?? (string) $item['to_stage']) ?></li>
    <?php endforeach; ?>
  </ul>
</section>

<section class="admin-panel">
  <h2>Notes</h2>
  <form method="post" action="/admin/deals/<?= (int) $deal['id'] ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="note">
    <div class="field"><label for="body">Note</label><textarea id="body" name="body" rows="3" required></textarea></div>
    <button class="button" type="submit">Add note</button>
  </form>
  <?php foreach ($notes as $note): ?>
    <article class="note"><p><?= nl2br(e((string) $note['body'])) ?></p><p class="fine"><?= e(format_when((string) $note['created_at'])) ?></p></article>
  <?php endforeach; ?>
</section>

<section class="admin-panel">
  <h2>Tasks</h2>
  <form method="post" action="/admin/deals/<?= (int) $deal['id'] ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="task">
    <div class="form-row two">
      <div class="field"><label for="title">Task</label><input id="title" name="title" required maxlength="200"></div>
      <div class="field"><label for="due_at">Due</label><input id="due_at" type="date" name="due_at"></div>
    </div>
    <button class="button" type="submit">Add task</button>
  </form>
  <ul>
    <?php foreach ($tasks as $task): ?>
      <li><?= e((string) $task['title']) ?><?php if ($task['due_at']): ?> · due <?= e((string) $task['due_at']) ?><?php endif; ?><?php if ($task['done_at']): ?> · done<?php endif; ?></li>
    <?php endforeach; ?>
  </ul>
</section>

<section class="admin-panel">
  <h2>Manual deal sheet</h2>
  <p class="fine">Only the fields below are published. Do not type the seller’s name, phone, email, or reason.</p>
  <form method="post" action="/admin/deals/<?= (int) $deal['id'] ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="sheet">
    <div class="field"><label for="location_label">Location shown</label><input id="location_label" name="location_label" required value="<?= e((string) $deal['city']) ?>"></div>
    <div class="form-row two">
      <div class="field"><label for="price_text">Price</label><input id="price_text" name="price_text" value="<?= $priceGuess ? e(format_money((int) $priceGuess)) : '' ?>"></div>
      <div class="field"><label for="arv_text">ARV</label><input id="arv_text" name="arv_text" value="<?= $deal['arv'] ? e(format_money((int) $deal['arv'])) : '' ?>"></div>
      <div class="field"><label for="rehab_text">Rehab estimate</label><input id="rehab_text" name="rehab_text" value="<?= $deal['repair_est'] ? e(format_money((int) $deal['repair_est'])) : '' ?>"></div>
      <div class="field"><label for="deadline_text">Deadline</label><input id="deadline_text" name="deadline_text" value="<?= e((string) ($deal['inspection_deadline'] ?? '')) ?>"></div>
    </div>
    <div class="field"><label for="attorney_text">Closing attorney</label><input id="attorney_text" name="attorney_text" value="<?= e((string) $deal['title_company']) ?>"></div>
    <div class="field"><label for="access_text">Access</label><textarea id="access_text" name="access_text" rows="2" placeholder="Lockbox or gate. No seller name or phone."></textarea></div>
    <div class="field"><label for="expires_days">Expires in days</label><input id="expires_days" name="expires_days" inputmode="numeric" value="14"></div>
    <label class="check-inline"><input type="checkbox" name="show_photos" value="1"> Include property photos</label>
    <button class="button" type="submit">Create deal sheet</button>
  </form>
  <?php if ($sheets): ?>
    <ul>
      <?php foreach ($sheets as $sheet): ?>
        <li><a href="/d/<?= e((string) $sheet['token']) ?>">Sheet</a> · expires <?= e(format_when((string) $sheet['expires_at'])) ?> · <?= e((string) $sheet['location_label']) ?></li>
      <?php endforeach; ?>
    </ul>
    <form method="post" action="/admin/deals/<?= (int) $deal['id'] ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="blast">
      <div class="field">
        <label for="sheet_id">Email this sheet to opted-in buyers</label>
        <select id="sheet_id" name="sheet_id">
          <?php foreach ($sheets as $sheet): ?>
            <option value="<?= (int) $sheet['id'] ?>"><?= e((string) $sheet['location_label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if (!$buyers): ?>
        <p>No opted-in buyers yet.</p>
      <?php else: ?>
        <?php foreach ($buyers as $buyer): ?>
          <label class="check-inline"><input type="checkbox" name="buyer_ids[]" value="<?= (int) $buyer['id'] ?>"> <?= e((string) $buyer['company']) ?> (<?= e((string) $buyer['tier']) ?>)</label>
        <?php endforeach; ?>
        <button class="button" type="submit">Send</button>
      <?php endif; ?>
    </form>
  <?php endif; ?>
</section>
