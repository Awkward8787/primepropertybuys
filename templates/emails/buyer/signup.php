<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
/** @var array $buyer */
echo implode("\n\n", [
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
