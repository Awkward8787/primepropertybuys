<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
/** @var string $content */
/** @var string $title */
/** @var string $robots */
$css = '/assets/css/site.css?v=' . filemtime(dirname(__DIR__) . '/assets/css/site.css');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="robots" content="<?= e($robots) ?>">
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= e($css) ?>">
</head>
<body class="sheet-body">
<main id="content" class="sheet">
<?= $content ?>
</main>
</body>
</html>
