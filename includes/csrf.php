<?php
declare(strict_types=1);

if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}

function csrf_token(): string
{
    $token = $_SESSION['_csrf'] ?? '';
    if (!is_string($token) || strlen($token) < 32) {
        $token = bin2hex(random_bytes(32));
        $_SESSION['_csrf'] = $token;
    }
    return $token;
}

function csrf_rotate(): void
{
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
}

function csrf_check(mixed $token): bool
{
    $known = $_SESSION['_csrf'] ?? '';
    if (!is_string($token) || !is_string($known) || $known === '' || $token === '') {
        return false;
    }
    return hash_equals($known, $token);
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}
