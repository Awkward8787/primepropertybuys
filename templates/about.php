<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
$phone = contact_phone();
?>
<section class="section">
  <div class="container prose">
    <h1>About Prime Property Buys</h1>
    <p>Prime Property Buys makes cash offers on houses in Valdosta and Lowndes County, Georgia. <?= e((string) ppb('business.owner')) ?> operates it. It is part of <?= e((string) ppb('business.parent')) ?>.</p>
    <p>Legal entity: <span class="<?= is_placeholder(legal_entity_label()) ? 'placeholder' : '' ?>"><?= e(legal_entity_label()) ?></span></p>
    <p><?= e(brokerage_disclosure()) ?> When a sale moves forward, a licensed Georgia closing attorney or a title company handles the closing.</p>
    <h2>Contact</h2>
    <p>Email <a href="mailto:<?= e((string) ppb('business.email')) ?>"><?= e((string) ppb('business.email')) ?></a></p>
    <p>Phone: <?php if ($phone['callable']): ?><a href="tel:<?= e($phone['tel']) ?>"><?= e($phone['display']) ?></a><?php else: ?><span class="placeholder"><?= e($phone['display'] !== '' ? $phone['display'] : '(TBD)') ?></span><?php endif; ?></p>
    <p>We do not publish reviews, star ratings, purchase counts, or years in business on this site. Those lines stay off the page until there is a documented fact to put there.</p>
  </div>
</section>
