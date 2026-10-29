<?php
declare(strict_types=1);

if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}

function start_session(): void
{
    $sessionDir = dirname(__DIR__) . '/storage/sessions';
    if (!is_dir($sessionDir) && !mkdir($sessionDir, 0700, true) && !is_dir($sessionDir)) {
        throw new RuntimeException('Could not create the session directory.');
    }

    $secure = request_is_https();
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.cookie_secure', $secure ? '1' : '0');
    ini_set('session.gc_maxlifetime', '43200');
    ini_set('session.save_path', $sessionDir);
    session_name((string) ppb('security.session_name', 'ppbsess'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function admin_count(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
}

function find_admin_by_username(string $username): ?array
{
    $stmt = db()->prepare('SELECT id, username, password_hash FROM admin_users WHERE username = ?');
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function find_admin_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT id, username FROM admin_users WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function current_admin(): ?array
{
    $id = $_SESSION['admin_id'] ?? null;
    if (!is_int($id) && !is_string($id)) {
        return null;
    }
    $id = (int) $id;
    if ($id <= 0) {
        return null;
    }
    $last = (int) ($_SESSION['admin_last'] ?? 0);
    $started = (int) ($_SESSION['admin_started'] ?? $last);
    if (($last > 0 && (time() - $last) > 14400) || ($started > 0 && (time() - $started) > 43200)) {
        unset($_SESSION['admin_id'], $_SESSION['admin_last'], $_SESSION['admin_started']);
        return null;
    }
    $user = find_admin_by_id($id);
    if (!$user) {
        unset($_SESSION['admin_id'], $_SESSION['admin_last']);
        return null;
    }
    $_SESSION['admin_last'] = time();
    return $user;
}

function require_admin(): array
{
    $user = current_admin();
    if ($user) {
        return $user;
    }
    $next = request_path();
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    if (is_string($qs) && $qs !== '') {
        $next .= '?' . $qs;
    }
    redirect('/admin/login?next=' . rawurlencode($next), 302);
}

function safe_admin_next(mixed $next): string
{
    if (!is_string($next)) {
        return '/admin/leads';
    }
    if (!preg_match('#^/admin(?:/[A-Za-z0-9._~/-]*)?(?:\?[A-Za-z0-9._~%=&-]*)?$#', $next)) {
        return '/admin/leads';
    }
    if (str_starts_with($next, '/admin/login') || str_starts_with($next, '/admin/setup')) {
        return '/admin/leads';
    }
    return $next;
}

function login_admin(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $user['id'];
    $_SESSION['admin_last'] = time();
    $_SESSION['admin_started'] = time();
    csrf_rotate();
    db()->prepare('UPDATE admin_users SET last_login = ? WHERE id = ?')->execute([now_sql(), (int) $user['id']]);
}

function logout_admin(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?: '/',
            'domain' => $params['domain'] ?? '',
            'secure' => (bool) $params['secure'],
            'httponly' => (bool) $params['httponly'],
            'samesite' => 'Strict',
        ]);
    }
    session_destroy();
}

function setup_key_is_configured(): bool
{
    $key = (string) ppb('security.setup_key', '');
    if (strlen($key) < 12) {
        return false;
    }
    $blocked = [
        'CHANGE_ME_BEFORE_SETUP',
        'changeme',
        'password',
        'password1234',
    ];
    return !in_array($key, $blocked, true);
}

function setup_key_matches(string $provided): bool
{
    $known = (string) ppb('security.setup_key', '');
    return hash_equals(hash('sha256', $known), hash('sha256', $provided));
}

function create_admin(string $username, string $password): void
{
    $now = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
    $stmt = db()->prepare('INSERT INTO admin_users (username, password_hash, role, created_at) VALUES (?, ?, ?, ?)');
    $stmt->execute([
        $username,
        password_hash($password, PASSWORD_DEFAULT),
        'owner',
        $now,
    ]);
}

function dummy_password_hash(): string
{
    return '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
}
