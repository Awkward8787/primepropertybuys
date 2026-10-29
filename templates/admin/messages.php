<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
?>
<h1>Messages</h1>
<?php if (!$messages): ?>
  <p>No contact-form messages yet.</p>
<?php else: ?>
  <?php foreach ($messages as $message): ?>
    <article class="note">
      <p><strong><?= e((string) $message['full_name']) ?></strong> · <?= e((string) $message['email']) ?> · <?= e((string) $message['phone']) ?></p>
      <p><?= (int) $message['tcpa_consent'] === 1 ? 'Call and text OK' : 'Email only' ?> · <?= e(format_when((string) $message['created_at'])) ?></p>
      <p><?= nl2br(e((string) $message['message'])) ?></p>
    </article>
  <?php endforeach; ?>
<?php endif; ?>
