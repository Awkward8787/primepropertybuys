<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
?>
<section class="section">
  <div class="container prose">
    <h1><?= e((string) ($heading ?? 'Page not found')) ?></h1>
    <p><?= e((string) ($message ?? '')) ?></p>
    <p><a href="/">Back to the home page</a></p>
  </div>
</section>
