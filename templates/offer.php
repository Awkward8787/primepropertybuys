<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
?>
<section class="section">
  <div class="container narrow">
    <h1>Get my cash offer</h1>
    <p class="lede">Four short steps. You can stop at any time. Asking does not commit you to sell.</p>
    <div class="form-card">
      <?php partial('offer_form', [
          'step' => $step,
          'draft' => $draft,
          'errors' => $errors,
          'source' => $source,
          'pending_count' => $pending_count ?? 0,
      ]); ?>
    </div>
  </div>
</section>
