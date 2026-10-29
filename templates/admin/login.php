<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
?>
<section class="auth-card">
  <h1>Sign in</h1>
  <p>Staff only.</p>
  <?php if (!empty($error)): ?><p class="form-errors" role="alert"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="/admin/login">
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e((string) ($next ?? '/admin/deals')) ?>">
    <div class="field">
      <label for="username">Username</label>
      <input id="username" name="username" autocomplete="username" required>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" autocomplete="current-password" required>
    </div>
    <button class="button" type="submit">Sign in</button>
  </form>
</section>
