<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
?>
<section class="section">
  <div class="container prose">
    <h1>Questions about selling your house</h1>
    <div class="faq">
      <?php foreach ($faqs as $item): ?>
        <details>
          <summary><?= e($item['q']) ?></summary>
          <p><?= e($item['a']) ?></p>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
