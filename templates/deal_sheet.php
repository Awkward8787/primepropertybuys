<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
/** @var array $sheet */
/** @var array $photos */
?>
<p class="eyebrow"><?= e((string) ppb('business.name')) ?></p>
<h1><?= e((string) $sheet['location_label']) ?></h1>
<p class="fine">We may not own this property. Availability and price can change. This page expires <?= e(format_when((string) $sheet['expires_at'])) ?>.</p>
<dl class="sheet-facts">
  <div><dt>Price</dt><dd><?= e((string) ($sheet['price_text'] !== '' ? $sheet['price_text'] : '—')) ?></dd></div>
  <div><dt>ARV</dt><dd><?= e((string) ($sheet['arv_text'] !== '' ? $sheet['arv_text'] : '—')) ?></dd></div>
  <div><dt>Rehab estimate</dt><dd><?= e((string) ($sheet['rehab_text'] !== '' ? $sheet['rehab_text'] : '—')) ?></dd></div>
  <div><dt>Access</dt><dd><?= e((string) ($sheet['access_text'] !== '' ? $sheet['access_text'] : '—')) ?></dd></div>
  <div><dt>Closing attorney</dt><dd><?= e((string) ($sheet['attorney_text'] !== '' ? $sheet['attorney_text'] : '—')) ?></dd></div>
  <div><dt>Deadline</dt><dd><?= e((string) ($sheet['deadline_text'] !== '' ? $sheet['deadline_text'] : '—')) ?></dd></div>
</dl>
<?php if ($photos): ?>
  <div class="sheet-photos">
    <?php foreach ($photos as $photo): ?>
      <?php if (displayable_mime((string) $photo['mime'])): ?>
        <img src="/d/<?= e((string) $sheet['token']) ?>/photo/<?= (int) $photo['id'] ?>" alt="Property photo">
      <?php else: ?>
        <p><a href="/d/<?= e((string) $sheet['token']) ?>/photo/<?= (int) $photo['id'] ?>">Download photo</a></p>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
