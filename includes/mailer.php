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
function seller_message(string $kind, array $lead): array
{
    $address = (string) ($lead['address'] ?? '');
    $name = (string) ($lead['full_name'] ?? '');
    $email = (string) ppb('business.email', 'info@primepropertybuys.com');
    $consented = (int) ($lead['tcpa_consent'] ?? 0) === 1;
    $contact = $consented
        ? 'You agreed we may call or text you at ' . ($lead['phone'] ?? '') . ' about this request.'
        : 'You did not agree to calls or texts, so we will use email only.';

    $intro = match ($kind) {
        'walkthrough' => "We can walk through the house.\n\n" . seller_booking_line(),
        'offer' => 'A written cash offer is ready. We will send the number by email. You are not obligated to accept it.',
        'under_contract' => 'Your file is under contract. A closing attorney or title company will be in touch about the paperwork and the date.',
        'closing' => 'A closing date is on the calendar. The closing attorney or title company will confirm what you need to sign.',
        'closed' => 'Thank you. The closing is complete. If something still needs attention, email us.',
        'reviewing' => 'We are reviewing the property details you sent.',
        default => "We received your request for a cash offer.\n\nProperty: {$address}\nName: {$name}\n\nWe will review it and reply by email.\n{$contact}\n\n" . seller_booking_line(),
    };

    $body = implode("\n\n", [
        $intro,
        'You do not have to make repairs for us to look at the house, and asking for an offer does not commit you to sell.',
        'If you did not ask us to contact you, ignore this email or write to ' . $email . '.',
        seller_mail_footer(),
    ]);

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
    $email = (string) ($buyer['email'] ?? '');
    $token = unsub_token($email);
    $link = absolute_url('/d/' . $sheet['token']);
    $unsub = absolute_url('/investors/unsubscribe?e=' . rawurlencode($email) . '&t=' . $token);
    $address = (string) ppb('business.mailing_address', '[MAILING ADDRESS]');
    $lines = [
        'A property matching details you sent us is available to review.',
        '',
        'Location: ' . $sheet['location_label'],
        'Price: ' . ($sheet['price_text'] !== '' ? $sheet['price_text'] : 'See the sheet'),
        'ARV: ' . ($sheet['arv_text'] !== '' ? $sheet['arv_text'] : '—'),
        'Rehab estimate: ' . ($sheet['rehab_text'] !== '' ? $sheet['rehab_text'] : '—'),
        'Deadline: ' . ($sheet['deadline_text'] !== '' ? $sheet['deadline_text'] : '—'),
        '',
        'Private sheet: ' . $link,
        '',
        'We may not own this property. Confirm availability before you rely on it.',
        'This note does not obligate you to purchase.',
        '',
        (string) ppb('business.name', 'Prime Property Buys') . ' – Acquisitions',
        $address,
        (string) ppb('business.email', 'info@primepropertybuys.com'),
        '',
        'You are receiving this because you asked for property emails.',
        'Unsubscribe: ' . $unsub,
    ];
    return [
        'subject' => 'Property to review: ' . mail_subject_piece((string) $sheet['location_label']),
        'body' => implode("\n", $lines),
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
    $body = implode("\n\n", [
        'We received your buy box.',
        'Company: ' . ($buyer['company'] ?? ''),
        'Areas: ' . ($buyer['areas'] ?? ''),
        (int) ($buyer['email_opt_in'] ?? 0) === 1
            ? 'You asked to be emailed when a property matches.'
            : 'You did not opt in to property emails, so we will not add you to those messages.',
        (int) ($buyer['tcpa_consent'] ?? 0) === 1
            ? 'You agreed we may call or text you about matching properties.'
            : 'You did not agree to calls or texts.',
        seller_mail_footer(),
    ]);
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
