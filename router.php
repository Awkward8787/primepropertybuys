<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (!is_string($path)) {
    $path = '/';
}

$blocked = [
    '#^/(?:includes|templates|storage|tests)(?:/|$)#',
    '#^/config(?:\.sample)?\.php$#',
    '#^/schema\.sql$#',
    '#^/\.git#',
];
foreach ($blocked as $pattern) {
    if (preg_match($pattern, $path) === 1) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Forbidden\n";
        return true;
    }
}

if ($path !== '/' && is_file(__DIR__ . $path)) {
    return false;
}

require __DIR__ . '/index.php';
return true;
