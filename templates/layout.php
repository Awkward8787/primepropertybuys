<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
/** @var string $content */
/** @var string $title */
/** @var string $robots */
$description = $meta_description ?? '';
$canonical = $canonical ?? absolute_url('/');
$json = $json_ld ?? null;
$css = '/assets/css/site.css?v=' . filemtime(dirname(__DIR__) . '/assets/css/site.css');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<?php if ($description !== ''): ?><meta name="description" content="<?= e($description) ?>"><?php endif; ?>
<meta name="robots" content="<?= e($robots) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta property="og:title" content="<?= e($title) ?>">
<?php if ($description !== ''): ?><meta property="og:description" content="<?= e($description) ?>"><?php endif; ?>
<meta property="og:type" content="website">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta name="theme-color" content="#1b4636">
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= e($css) ?>">
<?php if (is_string($json) && $json !== ''): ?>
<script type="application/ld+json" nonce="<?= e(csp_nonce()) ?>"><?= $json ?></script>
<?php endif; ?>
</head>
<body>
<a class="skip" href="#content">Skip to content</a>
<?php partial('header'); ?>
<main id="content" tabindex="-1">
<?= $content ?>
</main>
<?php partial('footer'); ?>
<script src="/assets/js/town.js" defer></script>
</body>
</html>
