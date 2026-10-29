<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
$phone = contact_phone();
?>
<header class="site-header">
  <div class="container header-bar">
    <a class="brand" href="/">
      <img src="/assets/favicon.svg" width="36" height="36" alt="">
      <span class="brand-text">
        <span class="brand-name">Prime Property Buys</span>
        <span class="brand-meta">Valdosta and Lowndes County</span>
      </span>
    </a>
    <input class="nav-toggle" type="checkbox" id="nav-toggle">
    <label class="nav-button" for="nav-toggle"><span class="nav-lines" aria-hidden="true"></span><span class="sr-only">Menu</span></label>
    <nav class="nav" aria-label="Primary">
      <a href="/how-it-works">How it works</a>
      <a href="/#areas">Areas</a>
      <a href="/faq">FAQ</a>
      <a href="/about">About</a>
      <a href="/contact">Contact</a>
      <?php if ($phone['callable']): ?>
        <a href="tel:<?= e($phone['tel']) ?>"><?= e($phone['display']) ?></a>
      <?php endif; ?>
      <a class="nav-cta" href="/get-offer">Get my cash offer</a>
    </nav>
  </div>
</header>
