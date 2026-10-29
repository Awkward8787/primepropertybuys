<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
?>
<section class="auth-card">
  <h1>Create the admin login</h1>
  <?php if (!empty($ready)): ?>
    <p>An administrator account already exists.</p>
    <p><a href="/admin/login">Sign in</a></p>
  <?php elseif (empty($key_ok)): ?>
    <p>Set <code>security.setup_key</code> in <code>config.php</code> to at least 12 characters, then reload this page. The sample value is rejected on purpose.</p>
  <?php else: ?>
    <p>Do this once, before anyone else can open the site. The first person to submit this form owns the only login.</p>
    <?php if (!empty($error)): ?><p class="form-errors" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post" action="/admin/setup">
      <?= csrf_field() ?>
      <div class="field">
        <label for="setup_key">Setup key from config.php</label>
        <input id="setup_key" name="setup_key" type="password" autocomplete="off" required>
      </div>
      <div class="field">
        <label for="username">Username</label>
        <input id="username" name="username" autocomplete="username" required minlength="3" maxlength="40">
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="new-password" required minlength="12">
      </div>
      <div class="field">
        <label for="confirm">Confirm password</label>
        <input id="confirm" name="confirm" type="password" autocomplete="new-password" required minlength="12">
      </div>
      <button class="button" type="submit">Create login</button>
    </form>
  <?php endif; ?>
</section>
