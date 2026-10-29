<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
/** @var array $buyer */
/** @var array $sheet */
$email = (string) ($buyer['email'] ?? '');
$token = unsub_token($email);
$link = absolute_url('/d/' . $sheet['token']);
$unsub = absolute_url('/investors/unsubscribe?e=' . rawurlencode($email) . '&t=' . $token);
$address = (string) ppb('business.mailing_address', '[MAILING ADDRESS]');
echo implode("\n", [
    'A property matching the buy box you sent is available to review.',
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
]);
