<?php
declare(strict_types=1);

define('PPB_APP', true);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/uploads.php';
require __DIR__ . '/includes/dispatch.php';

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$path = request_path();

try {
    dispatch($method, $path);
} catch (Throwable $e) {
    log_exception($e);
    http_response_code(500);
    $message = ppb('debug') === true
        ? $e->getMessage()
        : 'Something went wrong. Email ' . (string) ppb('business.email', 'info@primepropertybuys.com') . '.';
    if (!headers_sent()) {
        send_html_headers('noindex, nofollow');
    }
    render('error', [
        'title' => 'Something went wrong',
        'heading' => 'Something went wrong',
        'message' => $message,
        'robots' => 'noindex, nofollow',
    ]);
}
