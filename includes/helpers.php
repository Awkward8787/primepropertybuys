<?php
declare(strict_types=1);

if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}

function ppb(string $path, mixed $default = null): mixed
{
    $node = $GLOBALS['ppb_config'] ?? null;
    foreach (explode('.', $path) as $part) {
        if (!is_array($node) || !array_key_exists($part, $node)) {
            return $default;
        }
        $node = $node[$part];
    }
    return $node;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function request_path(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url(is_string($uri) ? $uri : '/', PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        $path = '/';
    }
    $path = '/' . trim($path, '/');
    return $path === '/' ? '/' : $path;
}

function request_is_https(): bool
{
    $https = $_SERVER['HTTPS'] ?? '';
    if (is_string($https) && $https !== '' && strtolower($https) !== 'off') {
        return true;
    }
    if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
        return true;
    }
    if (ppb('security.trust_proxy', false) === true) {
        $proto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        return $proto === 'https';
    }
    return false;
}

function site_url(): string
{
    $url = rtrim((string) ppb('business.url', ''), '/');
    if ($url === '' || !preg_match('#^https?://#i', $url)) {
        return 'https://primepropertybuys.com';
    }
    return $url;
}

function absolute_url(string $path): string
{
    if ($path === '' || $path === '/') {
        return site_url() . '/';
    }
    return site_url() . '/' . ltrim($path, '/');
}

function redirect(string $path, int $code = 303): void
{
    if (!str_starts_with($path, '/')) {
        $path = '/' . $path;
    }
    header('Location: ' . $path, true, $code);
    exit;
}

function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!is_string($ip) || filter_var($ip, FILTER_VALIDATE_IP) === false) {
        return '0.0.0.0';
    }
    return $ip;
}

function client_ua(): string
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (!is_string($ua)) {
        return '';
    }
    $ua = str_replace(["\0", "\r", "\n"], '', $ua);
    return mb_substr($ua, 0, 255);
}

function clean_line(string $value, int $max): string
{
    $value = str_replace(["\0", "\r", "\n"], '', trim($value));
    if (mb_strlen($value) > $max) {
        $value = mb_substr($value, 0, $max);
    }
    return $value;
}

function clean_text(string $value, int $max): string
{
    $value = str_replace("\0", '', trim($value));
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    if (mb_strlen($value) > $max) {
        $value = mb_substr($value, 0, $max);
    }
    return $value;
}

function phone_digits(string $value): string
{
    return preg_replace('/\D+/', '', $value) ?? '';
}

function contact_phone(): array
{
    $display = trim((string) ppb('business.phone_display', ''));
    $tel = preg_replace('/[^\d+]/', '', (string) ppb('business.phone_tel', '')) ?? '';
    $callable = $tel !== '' && preg_match('/^\+?[0-9]{10,15}$/', $tel) === 1;
    return [
        'display' => $display,
        'tel' => $callable ? $tel : '',
        'callable' => $callable,
    ];
}

function disclosure_line(): string
{
    return 'We may purchase directly or partner with or assign our purchase contract to other buyers.';
}

function brokerage_disclosure(): string
{
    return 'We are not a licensed real estate agent or brokerage.';
}

function footer_disclaimer(): string
{
    $name = (string) ppb('business.name', 'Prime Property Buys');
    return $name . ' buys residential property. ' . disclosure_line() . ' ' . brokerage_disclosure();
}

function now_sql(): string
{
    return (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
}

function booking_url(): string
{
    $url = trim((string) ppb('business.booking_url', ''));
    return preg_match('#^https://#i', $url) === 1 ? $url : '';
}

function booking_label(): string
{
    $label = trim((string) ppb('business.booking_label', '[BOOKING TOOL]'));
    return $label !== '' ? $label : '[BOOKING TOOL]';
}

function legal_entity_label(): string
{
    $label = trim((string) ppb('business.legal_entity', '[LEGAL ENTITY]'));
    return $label !== '' ? $label : '[LEGAL ENTITY]';
}

function is_placeholder(string $value): bool
{
    return $value === '' || str_starts_with($value, '[') || $value === '(TBD)';
}

function seller_consent_text(): string
{
    $name = (string) ppb('business.name', 'Prime Property Buys');
    return 'I agree ' . $name
        . ' may call or text me at this number, including by automated means, about my property. '
        . 'Consent is not required to get an offer. Message and data rates may apply. Reply STOP to opt out.';
}

function buyer_consent_text(): string
{
    $name = (string) ppb('business.name', 'Prime Property Buys');
    return 'By checking this box, I agree that ' . $name
        . ' may call and text the phone number I entered about properties that match the buy box I submitted, including with an automatic telephone dialing system or a prerecorded or artificial voice. '
        . 'This is optional. Leave it unchecked if you only want email. '
        . 'Consent is not required to join the list and is not a condition of any purchase. '
        . 'Message and data rates may apply. Reply STOP to opt out of texts.';
}

function old_value(array $old, string $key, string $default = ''): string
{
    if (!array_key_exists($key, $old)) {
        return $default;
    }
    $value = $old[$key];
    if (is_array($value)) {
        return $default;
    }
    return (string) $value;
}

function field_error(array $errors, string $key): string
{
    $value = $errors[$key] ?? '';
    return is_string($value) ? $value : '';
}

function flash_set(string $key, string $value): void
{
    $_SESSION['_flash'][$key] = $value;
}

function flash_get(string $key): ?string
{
    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }
    $value = (string) $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $value;
}

function log_exception(Throwable $e): void
{
    error_log($e::class . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
}

function render(string $template, array $data = [], string $layout = 'layout'): void
{
    $views = dirname(__DIR__) . '/templates/';
    $templateFile = $views . $template . '.php';
    $layoutFile = $views . $layout . '.php';
    if (!is_file($templateFile) || !is_file($layoutFile)) {
        throw new RuntimeException('Missing template.');
    }
    extract($data, EXTR_SKIP);
    ob_start();
    include $templateFile;
    $content = ob_get_clean();
    include $layoutFile;
}

function partial(string $name, array $data = []): void
{
    $file = dirname(__DIR__) . '/templates/partials/' . $name . '.php';
    if (!is_file($file)) {
        throw new RuntimeException('Missing partial.');
    }
    extract($data, EXTR_SKIP);
    include $file;
}

function send_html_headers(string $robots): void
{
    header('Content-Type: text/html; charset=utf-8');
    header('X-Robots-Tag: ' . $robots);
    header('Cache-Control: private, no-store');
    header('Content-Language: en-US');
}

function csp_nonce(): string
{
    $nonce = $GLOBALS['ppb_csp_nonce'] ?? '';
    return is_string($nonce) ? $nonce : '';
}

function selected_attr(string $current, string $value): string
{
    return $current === $value ? ' selected' : '';
}

function checked_attr(bool $on): string
{
    return $on ? ' checked' : '';
}

function format_money(int|string $amount): string
{
    $n = (int) $amount;
    return '$' . number_format($n);
}

function format_when(string $timestamp): string
{
    $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $timestamp);
    if (!$dt) {
        return $timestamp;
    }
    return $dt->format('M j, Y g:i a');
}

function csv_safe(string $value): string
{
    if ($value !== '' && preg_match('/^[=+\-@\t\r]/', $value) === 1) {
        return "'" . $value;
    }
    return $value;
}

function like_pattern(string $q): string
{
    $q = str_replace(['%', '_'], '', $q);
    return '%' . $q . '%';
}

function options_html(array $options, string $selected, bool $placeholder = false, string $placeholderLabel = 'Select'): string
{
    $html = '';
    if ($placeholder) {
        $html .= '<option value="">' . e($placeholderLabel) . '</option>';
    }
    foreach ($options as $value => $label) {
        $value = (string) $value;
        if ($value === '') {
            continue;
        }
        $html .= '<option value="' . e($value) . '"' . selected_attr($selected, $value) . '>' . e((string) $label) . '</option>';
    }
    return $html;
}
