<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
?>
<h1>Email preferences</h1>
<?php if (!empty($ok)): ?>
  <p>You will not receive further property emails from us.</p>
<?php else: ?>
  <p>That unsubscribe link is not valid.</p>
<?php endif; ?>
