<?php
declare(strict_types=1);

/**
 * Copy this file to config.php on the server and fill in the real values.
 * config.php is not committed. Do not put production passwords in this sample.
 *
 * Public contact email and the default notification address are
 * info@primepropertybuys.com. Change notification_email if lead alerts
 * should go to a different inbox than the public mailbox.
 *
 * Do not invent a phone number here. Leave the placeholder until you have
 * the number you want on the site, then set phone_display and phone_tel.
 */
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}

return [
    'debug' => false,
    'timezone' => 'America/New_York',

    'db' => [
        // Use mysql on Hostinger. Use sqlite only for local PHP testing.
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'YOUR_DATABASE_NAME',
        'user' => 'YOUR_DATABASE_USER',
        'pass' => 'YOUR_DATABASE_PASSWORD',
        'charset' => 'utf8mb4',
        'sqlite_path' => __DIR__ . '/storage/dev.sqlite',
    ],

    'business' => [
        'name' => 'Prime Property Buys',
        'owner' => 'Anthony Jaamal Jones',
        'parent' => 'Vibe Virtue Solutions',
        // Replace [LEGAL ENTITY] when the public registration name should appear.
        'legal_entity' => '[LEGAL ENTITY]',
        'email' => 'info@primepropertybuys.com',
        'notification_email' => 'info@primepropertybuys.com',
        // Leave (TBD) until you have a real public number. Do not invent one.
        'phone_display' => '(TBD)',
        'phone_tel' => '',
        // Paste an https Calendly or Cal.com link when you choose a booking tool.
        'booking_url' => '',
        'booking_label' => '[BOOKING TOOL]',
        // Shown on the About page until a real, permitted quote or count replaces them.
        'testimonial' => '[TESTIMONIAL — add when real]',
        'deals_closed' => '[DEALS CLOSED — add when real]',
        // Physical address for buyer-list emails. Leave the placeholder until you have one.
        'mailing_address' => '[MAILING ADDRESS]',
        'city' => 'Valdosta',
        'region' => 'GA',
        'country' => 'US',
        'url' => 'https://primepropertybuys.com',
    ],

    'mail' => [
        // Hostinger can send mail from a mailbox on this domain.
        // Leads are still saved when mail is disabled or mail() fails.
        'enabled' => true,
        'from_email' => 'info@primepropertybuys.com',
        'from_name' => 'Prime Property Buys',
    ],

    'security' => [
        'session_name' => 'ppbsess',
        // Type this once on /admin/setup. Use at least 12 random characters.
        // The sample value is rejected on purpose.
        'setup_key' => 'CHANGE_ME_BEFORE_SETUP',
        'rate_limit_max' => 8,
        'rate_limit_window' => 3600,
        'login_max' => 5,
        'login_window' => 900,
        // Leave false on Hostinger unless HTTPS login will not stay signed in.
        'trust_proxy' => false,
    ],
];
