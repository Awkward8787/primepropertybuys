<?php
declare(strict_types=1);

if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}

$configFile = getenv('PPB_CONFIG');
if (!is_string($configFile) || $configFile === '') {
    $configFile = dirname(__DIR__) . '/config.php';
}

if (!is_file($configFile)) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Configuration needed</title></head><body>';
    echo '<p>Copy <code>config.sample.php</code> to <code>config.php</code>, then set the database credentials.</p>';
    echo '</body></html>';
    exit;
}

/** @var mixed $config */
$config = require $configFile;
if (!is_array($config)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "config.php must return an array.\n";
    exit;
}

$GLOBALS['ppb_config'] = $config;
date_default_timezone_set((string) ($config['timezone'] ?? 'America/New_York'));
ini_set('display_errors', '0');
ini_set('log_errors', '1');
$storage = dirname(__DIR__) . '/storage';
if (!is_dir($storage) && !mkdir($storage, 0755, true) && !is_dir($storage)) {
    http_response_code(500);
    echo 'Storage directory is not writable.';
    exit;
}
ini_set('error_log', $storage . '/php-error.log');

require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';
require __DIR__ . '/csrf.php';
require __DIR__ . '/rate_limit.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/mailer.php';
require __DIR__ . '/content.php';
require __DIR__ . '/repo.php';

$nonce = bin2hex(random_bytes(16));
$GLOBALS['ppb_csp_nonce'] = $nonce;
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header(
    "Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}'; style-src 'self'; "
    . "img-src 'self'; font-src 'self'; form-action 'self'; base-uri 'self'; frame-ancestors 'none'; object-src 'none'"
);

try {
    start_session();
    ensure_schema(db());
} catch (Throwable $e) {
    log_exception($e);
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    if (!empty($config['debug'])) {
        echo $e->getMessage();
    } else {
        echo "The database is not available. Import schema.sql and check config.php.\n";
    }
    exit;
}

if (isset($_GET['utm_source']) && is_string($_GET['utm_source'])) {
    $_SESSION['utm'] = clean_line($_GET['utm_source'], 80);
}
