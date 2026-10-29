<?php
declare(strict_types=1);

if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}

function send_plain_mail(string $to, string $subject, string $body, ?string $fromName = null): bool
{
    if (ppb('mail.enabled', false) !== true) {
        return false;
    }
    $to = filter_var(trim($to), FILTER_VALIDATE_EMAIL);
    $fromEmail = filter_var(trim((string) ppb('mail.from_email', '')), FILTER_VALIDATE_EMAIL);
    if (!is_string($to) || !is_string($fromEmail)) {
        return false;
    }
    $name = $fromName ?? (string) ppb('mail.from_name', 'Prime Property Buys');
    $name = str_replace(["\r", "\n", '"'], '', $name);
    $subject = str_replace(["\r", "\n"], '', $subject);
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: "' . $name . '" <' . $fromEmail . '>',
        'Reply-To: ' . $fromEmail,
    ];
    $sent = @mail($to, $subject, $body, implode("\r\n", $headers), '-f' . $fromEmail);
    if ($sent !== true) {
        error_log('Mail send failed for a lead notice.');
    }
    return $sent === true;
}

function mail_subject_piece(string $value): string
{
    $value = str_replace(["\r", "\n"], ' ', trim($value));
    if (mb_strlen($value) > 80) {
        $value = mb_substr($value, 0, 77) . '...';
    }
    return $value;
}

function seller_mail_footer(): string
{
    $phone = contact_phone();
    $display = $phone['display'] !== '' ? $phone['display'] : '(TBD)';
    return implode("\n", [
        (string) ppb('business.name', 'Prime Property Buys'),
        'Valdosta and Lowndes County, Georgia',
        'Email: ' . (string) ppb('business.email', 'info@primepropertybuys.com'),
        'Phone: ' . $display,
    ]);
}

function seller_booking_line(): string
{
    $url = booking_url();
    if ($url !== '') {
        return 'Schedule a time: ' . $url;
    }
    return 'Scheduling: ' . booking_label();
}

/**
 * Seller-facing messages. Keep these free of end-buyer, fund, and contract-transfer language.
 *
 * @return array{subject: string, body: string}
 */
function render_email(string $template, array $vars): string
{
    extract($vars, EXTR_SKIP);
    ob_start();
    include dirname(__DIR__) . '/templates/emails/' . $template . '.php';
    return trim((string) ob_get_clean());
}

function seller_message(string $kind, array $lead): array
{
    $body = render_email('seller/message', ['kind' => $kind, 'lead' => $lead]);

    $subjects = [
        'received' => 'We received your property request',
        'reviewing' => 'We are reviewing your property',
        'walkthrough' => 'Walkthrough for your property',
        'offer' => 'Your cash offer is ready',
        'under_contract' => 'Your sale is under contract',
        'closing' => 'Closing is scheduled',
        'closed' => 'Thank you for closing',
    ];

    return [
        'subject' => $subjects[$kind] ?? $subjects['received'],
        'body' => $body,
    ];
}

function deliver_seller_notice(array $lead, string $kind = 'received'): void
{
    $message = seller_message($kind, $lead);
    send_plain_mail((string) $lead['email'], $message['subject'], $message['body']);
    $notify = (string) ppb('business.notification_email', '');
    if ($notify === '' || $kind !== 'received') {
        return;
    }
    $internal = implode("\n", [
        'New property request',
        '',
        'Address: ' . ($lead['address'] ?? ''),
        'City: ' . ($lead['city'] ?? ''),
        'Name: ' . ($lead['full_name'] ?? ''),
        'Phone: ' . ($lead['phone'] ?? ''),
        'Email: ' . ($lead['email'] ?? ''),
        'Condition: ' . ($lead['condition_label'] ?? ''),
        'Timeline: ' . ($lead['timeline'] ?? ''),
        'Call/text consent: ' . ((int) ($lead['tcpa_consent'] ?? 0) === 1 ? 'YES' : 'NO'),
        'Page: ' . ($lead['source_path'] ?? ''),
        '',
        (int) ($lead['tcpa_consent'] ?? 0) === 1
            ? 'Consent is YES. You may call or text this person about this request.'
            : 'Consent is NO. Do not call or text. Reply by email only.',
    ]);
    send_plain_mail($notify, 'New property request: ' . mail_subject_piece((string) ($lead['address'] ?? '')), $internal);
}

/**
 * Buyer-list mail. Never use these strings on seller pages or seller emails.
 *
 * @return array{subject: string, body: string}
 */
function buyer_blast_message(array $buyer, array $sheet): array
{
    return [
        'subject' => 'Property to review: ' . mail_subject_piece((string) $sheet['location_label']),
        'body' => render_email('buyer/blast', ['buyer' => $buyer, 'sheet' => $sheet]),
    ];
}

function deliver_buyer_blast(array $buyer, array $sheet): bool
{
    if ((int) ($buyer['email_opt_in'] ?? 0) !== 1 || !empty($buyer['unsubscribed_at'])) {
        return false;
    }
    $message = buyer_blast_message($buyer, $sheet);
    if (ppb('mail.enabled', false) !== true) {
        return true;
    }
    return send_plain_mail(
        (string) $buyer['email'],
        $message['subject'],
        $message['body'],
        'Prime Property Buys – Acquisitions'
    );
}

function deliver_buyer_signup_mail(array $buyer): void
{
    $body = render_email('buyer/signup', ['buyer' => $buyer]);
    send_plain_mail((string) $buyer['email'], 'We received your buy box', $body, 'Prime Property Buys – Acquisitions');
    $notify = (string) ppb('business.notification_email', '');
    if ($notify === '') {
        return;
    }
    send_plain_mail(
        $notify,
        'New buyer list signup: ' . mail_subject_piece((string) ($buyer['company'] ?? '')),
        "New buyer list signup\n\nCompany: {$buyer['company']}\nContact: {$buyer['contact_name']}\nEmail: {$buyer['email']}\nPhone: {$buyer['phone']}\n",
        'Prime Property Buys – Acquisitions'
    );
}

function unsub_token(string $email): string
{
    $secret = (string) ppb('security.setup_key', 'prime-property-buys');
    return hash_hmac('sha256', strtolower(trim($email)), $secret);
}
