<?php
declare(strict_types=1);

if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}

function rate_limit_settings(string $bucket): array
{
    if ($bucket === 'login') {
        return [
            'max' => max(1, (int) ppb('security.login_max', 5)),
            'window' => max(60, (int) ppb('security.login_window', 900)),
        ];
    }
    return [
        'max' => max(1, (int) ppb('security.rate_limit_max', 8)),
        'window' => max(60, (int) ppb('security.rate_limit_window', 3600)),
    ];
}

function rate_limit_too_many(string $ip, string $bucket): bool
{
    $settings = rate_limit_settings($bucket);
    $cutoff = (new DateTimeImmutable('now'))
        ->modify('-' . $settings['window'] . ' seconds')
        ->format('Y-m-d H:i:s');
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM rate_limits WHERE ip_address = ? AND bucket = ? AND created_at >= ?'
    );
    $stmt->execute([$ip, $bucket, $cutoff]);
    return (int) $stmt->fetchColumn() >= $settings['max'];
}

function rate_limit_hit(string $ip, string $bucket): void
{
    $now = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
    $purge = (new DateTimeImmutable('now'))->modify('-2 days')->format('Y-m-d H:i:s');
    $pdo = db();
    $pdo->prepare('DELETE FROM rate_limits WHERE created_at < ?')->execute([$purge]);
    $pdo->prepare('INSERT INTO rate_limits (ip_address, bucket, created_at) VALUES (?, ?, ?)')
        ->execute([$ip, $bucket, $now]);
}
