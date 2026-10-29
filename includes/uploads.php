<?php
declare(strict_types=1);

if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}

function upload_root(): string
{
    $dir = dirname(__DIR__) . '/storage/uploads';
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('Upload directory is not writable.');
    }
    return $dir;
}

function media_path(string $stored): string
{
    return upload_root() . '/' . $stored;
}

/**
 * @return array{stored_name: string, original_name: string, mime: string, bytes: int}|string
 */
function take_upload(array $file, bool $allowPdf = false): array|string
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return 'skip';
    }
    if ($error !== UPLOAD_ERR_OK) {
        return 'One of the files did not upload. Try a smaller photo.';
    }
    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 10 * 1024 * 1024) {
        return 'Each photo must be 10 MB or smaller.';
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return 'One of the files did not upload.';
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmp);
    $original = clean_line((string) ($file['name'] ?? 'photo'), 180);
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $map = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/heic' => 'heic',
        'image/heif' => 'heif',
    ];
    if ($allowPdf) {
        $map['application/pdf'] = 'pdf';
    }
    if (!isset($map[$mime]) && $mime === 'application/octet-stream' && in_array($ext, ['heic', 'heif'], true)) {
        $map[$mime] = $ext;
    }
    if (!isset($map[$mime])) {
        return $allowPdf
            ? 'Upload a JPG, PNG, WEBP, HEIC, or PDF.'
            : 'Photos must be JPG, PNG, WEBP, or HEIC.';
    }
    $stored = bin2hex(random_bytes(16)) . '.' . $map[$mime];
    if (!move_uploaded_file($tmp, media_path($stored))) {
        return 'Could not store a photo.';
    }
    return [
        'stored_name' => $stored,
        'original_name' => $original !== '' ? $original : ('photo.' . $map[$mime]),
        'mime' => $mime === 'application/octet-stream' ? 'image/' . $map[$mime] : $mime,
        'bytes' => $size,
    ];
}

function collect_uploads(string $field, int $already, int $max = 10, bool $allowPdf = false): array
{
    $saved = [];
    $error = null;
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field]['name'] ?? null)) {
        return ['files' => [], 'error' => null];
    }
    $names = $_FILES[$field]['name'];
    $count = is_array($names) ? count($names) : 0;
    if ($already + $count > $max) {
        return ['files' => [], 'error' => 'You can attach up to ' . $max . ' photos.'];
    }
    for ($i = 0; $i < $count; $i++) {
        $one = [
            'name' => $_FILES[$field]['name'][$i] ?? '',
            'type' => $_FILES[$field]['type'][$i] ?? '',
            'tmp_name' => $_FILES[$field]['tmp_name'][$i] ?? '',
            'error' => $_FILES[$field]['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $_FILES[$field]['size'][$i] ?? 0,
        ];
        $taken = take_upload($one, $allowPdf);
        if ($taken === 'skip') {
            continue;
        }
        if (is_string($taken)) {
            $error = $taken;
            break;
        }
        $saved[] = $taken;
    }
    return ['files' => $saved, 'error' => $error];
}

function displayable_mime(string $mime): bool
{
    return in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true);
}
