<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
/** @var string $kind */
/** @var array $lead */
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

echo implode("\n\n", [
    $intro,
    'You do not have to make repairs for us to look at the house, and asking for an offer does not commit you to sell.',
    'If you did not ask us to contact you, ignore this email or write to ' . $email . '.',
    seller_mail_footer(),
]);
