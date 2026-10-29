<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
$phone = contact_phone();
$email = (string) ppb('business.email');
?>
<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <p class="footer-brand">Prime Property Buys</p>
      <p><?= e((string) ppb('business.owner')) ?><br>
        Valdosta and Lowndes County, Georgia<br>
        Part of <?= e((string) ppb('business.parent')) ?></p>
      <p>Legal entity: <span class="<?= is_placeholder(legal_entity_label()) ? 'placeholder' : '' ?>"><?= e(legal_entity_label()) ?></span></p>
    </div>
    <div>
      <p class="footer-heading">Contact</p>
      <p><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p>
      <p>Phone: <?php if ($phone['callable']): ?><a href="tel:<?= e($phone['tel']) ?>"><?= e($phone['display']) ?></a><?php else: ?><span class="placeholder"><?= e($phone['display'] !== '' ? $phone['display'] : '(TBD)') ?></span><?php endif; ?></p>
    </div>
    <div>
      <p class="footer-heading">Areas</p>
      <ul>
        <?php foreach (locations() as $slug => $area): ?>
          <li><a href="/<?= e($slug) ?>"><?= e($area['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div>
      <p class="footer-heading">Policies</p>
      <ul>
        <li><a href="/privacy">Privacy policy</a></li>
        <li><a href="/terms">Terms</a></li>
        <li><a href="/get-offer">Request a cash offer</a></li>
      </ul>
    </div>
  </div>
  <div class="container footer-legal">
    <p><?= e(disclosure_line()) ?></p>
    <p><?= e(brokerage_disclosure()) ?></p>
  </div>
</footer>
