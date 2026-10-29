<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
/** @var string $content */
/** @var string $title */
/** @var string $robots */
$adminUser = $admin_user ?? null;
$css = '/assets/css/site.css?v=' . filemtime(dirname(__DIR__, 2) . '/assets/css/site.css');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · Private</title>
<meta name="robots" content="<?= e($robots) ?>">
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= e($css) ?>">
</head>
<body class="admin-body">
<a class="skip" href="#content">Skip to content</a>
<header class="admin-header">
  <div class="container admin-bar">
    <p class="admin-brand">Prime Property Buys <span>Private</span></p>
    <?php if (is_array($adminUser)): ?>
      <nav class="admin-nav" aria-label="Admin">
        <a href="/admin/deals">Pipeline</a>
        <a href="/admin/buyers">Buyers</a>
        <a href="/admin/tasks">Tasks</a>
        <a href="/admin/messages">Messages</a>
        <form method="post" action="/admin/logout"><?= csrf_field() ?><button type="submit">Log out</button></form>
      </nav>
    <?php endif; ?>
  </div>
</header>
<main id="content" class="container admin-main" tabindex="-1">
<?= $content ?>
</main>
</body>
</html>
