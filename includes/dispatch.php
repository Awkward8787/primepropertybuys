<?php
declare(strict_types=1);

if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}

function dispatch(string $method, string $path): void
{
    if ($method === 'HEAD') {
        $method = 'GET';
    }
    if ($path === '/index.php') {
        redirect('/');
    }
    maybe_https_redirect($path);

    if (str_starts_with($path, '/admin')) {
        admin_dispatch($method, $path);
        return;
    }
    public_dispatch($method, $path);
}

function maybe_https_redirect(string $path): void
{
    if (ppb('debug') === true || request_is_https() || !str_starts_with($path, '/admin')) {
        return;
    }
    $base = site_url();
    if (!str_starts_with($base, 'https://')) {
        return;
    }
    header('Location: ' . $base . $path, true, 302);
    exit;
}

function page(string $template, array $data = [], string $robots = 'index, follow', string $layout = 'layout'): void
{
    send_html_headers($robots);
    $data['robots'] = $robots;
    render($template, $data, $layout);
}

function admin_page(string $template, array $data = []): void
{
    $data['admin_user'] = require_admin();
    $data['robots'] = 'noindex, nofollow';
    send_html_headers('noindex, nofollow');
    render($template, $data, 'admin/layout');
}

function not_found(): void
{
    http_response_code(404);
    page('error', [
        'title' => 'Page not found | Prime Property Buys',
        'heading' => 'Page not found',
        'message' => 'That page is not on this site. You can request a cash offer from the home page.',
    ], 'noindex, nofollow');
}

function public_dispatch(string $method, string $path): void
{
    if ($method === 'POST' && $path === '/get-offer') {
        handle_offer_post();
        return;
    }
    if ($method === 'POST' && $path === '/contact') {
        handle_contact_post();
        return;
    }
    if ($method === 'POST' && $path === '/investors') {
        handle_investor_post();
        return;
    }

    if ($method !== 'GET') {
        http_response_code(405);
        header('Allow: GET');
        not_found();
        return;
    }

    if (preg_match('#^/d/([a-f0-9]{32})/photo/(\d+)$#', $path, $m) === 1) {
        stream_sheet_photo($m[1], (int) $m[2]);
        return;
    }
    if (preg_match('#^/d/([a-f0-9]{32})$#', $path, $m) === 1) {
        show_sheet($m[1]);
        return;
    }
    if ($path === '/investors/unsubscribe') {
        show_unsubscribe();
        return;
    }

    $routes = [
        '/' => 'show_home',
        '/get-offer' => 'show_offer',
        '/how-it-works' => 'show_how',
        '/faq' => 'show_faq',
        '/about' => 'show_about',
        '/contact' => 'show_contact',
        '/privacy' => 'show_privacy',
        '/terms' => 'show_terms',
        '/request-received' => 'show_thanks',
        '/investors' => 'show_investors',
        '/sitemap.xml' => 'show_sitemap',
    ];
    if (isset($routes[$path])) {
        $routes[$path]();
        return;
    }
    $loc = location_by_slug(ltrim($path, '/'));
    if ($loc) {
        show_town($loc);
        return;
    }
    not_found();
}

function offer_draft(): array
{
    $draft = $_SESSION['offer_draft'] ?? [];
    return is_array($draft) ? $draft : [];
}

function offer_max_step(array $draft): int
{
    if (trim((string) ($draft['address'] ?? '')) === '' || trim((string) ($draft['city'] ?? '')) === '') {
        return 1;
    }
    foreach (['property_type', 'beds', 'baths', 'condition_label', 'occupancy'] as $key) {
        if (trim((string) ($draft[$key] ?? '')) === '') {
            return 2;
        }
    }
    if (trim((string) ($draft['timeline'] ?? '')) === '') {
        return 3;
    }
    return 4;
}

function require_csrf(): bool
{
    $token = $_POST['_csrf'] ?? null;
    if (csrf_check(is_string($token) ? $token : null)) {
        return true;
    }
    http_response_code(403);
    page('error', [
        'title' => 'Please refresh | Prime Property Buys',
        'heading' => 'Please refresh and try again',
        'message' => 'Your session expired. Go back, refresh the page, and submit the form again.',
    ], 'noindex, nofollow');
    return false;
}

function safe_source(string $source): string
{
    if ($source === '/' || $source === '/get-offer') {
        return $source;
    }
    $loc = location_by_slug(ltrim($source, '/'));
    return $loc ? '/' . $loc['slug'] : '/get-offer';
}

function render_offer_error(string $source, int $step, array $errors, array $draft): void
{
    http_response_code(422);
    if ($step === 1 && $source === '/') {
        show_home($errors, $draft);
        return;
    }
    if ($step === 1) {
        $loc = location_by_slug(ltrim($source, '/'));
        if ($loc) {
            show_town($loc, $errors, $draft);
            return;
        }
    }
    show_offer($step, $errors, $draft);
}

function clear_offer_session(): void
{
    foreach ($_SESSION['pending_photos'] ?? [] as $file) {
        if (is_array($file) && isset($file['stored_name'])) {
            $path = media_path((string) $file['stored_name']);
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
    unset($_SESSION['offer_draft'], $_SESSION['pending_photos']);
}

function handle_offer_post(): void
{
    if (!require_csrf()) {
        return;
    }
    if (trim((string) ($_POST['website'] ?? '')) !== '') {
        rate_limit_hit(client_ip(), 'form');
        clear_offer_session();
        redirect('/request-received');
    }
    if (rate_limit_too_many(client_ip(), 'form')) {
        header('Retry-After: ' . (string) ppb('security.rate_limit_window', 3600));
        render_offer_error(safe_source((string) ($_POST['source'] ?? '/get-offer')), (int) ($_POST['step'] ?? 1), [
            '_form' => 'Too many submissions from this network. Email ' . ppb('business.email') . ' or try again later.',
        ], offer_draft());
        return;
    }
    rate_limit_hit(client_ip(), 'form');

    $step = (int) ($_POST['step'] ?? 1);
    $source = safe_source((string) ($_POST['source'] ?? '/get-offer'));
    $draft = offer_draft();
    $errors = [];

    if ($step === 1) {
        $draft['address'] = clean_line((string) ($_POST['address'] ?? ''), 300);
        $draft['zip'] = clean_line((string) ($_POST['zip'] ?? ''), 12);
        $city = clean_line((string) ($_POST['city'] ?? ''), 80);
        if ($city === '' || !isset(service_towns()[$city])) {
            $city = detect_town($draft['address']);
        }
        $draft['city'] = $city;
        $draft['source_path'] = $source;
        if (mb_strlen($draft['address']) < 5) {
            $errors['address'] = 'Enter the property address.';
        }
        if ($draft['zip'] !== '' && preg_match('/^\d{5}(-\d{4})?$/', $draft['zip']) !== 1) {
            $errors['zip'] = 'Enter a 5-digit ZIP, or leave it blank.';
        }
        if (!isset(service_towns()[$draft['city']])) {
            $errors['city'] = 'Choose the town. If you are outside a city, choose Lowndes County.';
        }
        $_SESSION['offer_draft'] = $draft;
        if ($errors) {
            render_offer_error($source, 1, $errors, $draft);
            return;
        }
        log_form_event('address');
        redirect('/get-offer?step=2');
    }

    if (offer_max_step($draft) < $step) {
        redirect('/get-offer?step=' . offer_max_step($draft));
    }

    if ($step === 2) {
        $draft['property_type'] = (string) ($_POST['property_type'] ?? '');
        $draft['beds'] = (string) ($_POST['beds'] ?? '');
        $draft['baths'] = (string) ($_POST['baths'] ?? '');
        $draft['condition_label'] = (string) ($_POST['condition_label'] ?? '');
        $draft['occupancy'] = (string) ($_POST['occupancy'] ?? '');
        if (!isset(property_types()[$draft['property_type']])) {
            $errors['property_type'] = 'Choose the property type.';
        }
        if (!isset(bed_options()[$draft['beds']])) {
            $errors['beds'] = 'Choose the number of bedrooms.';
        }
        if (!isset(bath_options()[$draft['baths']])) {
            $errors['baths'] = 'Choose the number of bathrooms.';
        }
        if (!isset(condition_options()[$draft['condition_label']])) {
            $errors['condition_label'] = 'Choose the condition.';
        }
        if (!isset(occupancy_options()[$draft['occupancy']])) {
            $errors['occupancy'] = 'Tell us if anyone lives there.';
        }
        $_SESSION['offer_draft'] = $draft;
        if ($errors) {
            render_offer_error($source, 2, $errors, $draft);
            return;
        }
        log_form_event('property');
        redirect('/get-offer?step=3');
    }

    if ($step === 3) {
        $draft['timeline'] = (string) ($_POST['timeline'] ?? '');
        $draft['reason'] = (string) ($_POST['reason'] ?? '');
        [$price, $priceError] = parse_optional_money((string) ($_POST['asking_price'] ?? ''));
        if (!isset(timeline_options()[$draft['timeline']])) {
            $errors['timeline'] = 'Choose a timeline.';
        }
        if ($draft['reason'] !== '' && !isset(reason_options()[$draft['reason']])) {
            $errors['reason'] = 'Choose a reason, or leave it blank.';
        }
        if ($priceError !== null) {
            $errors['asking_price'] = $priceError;
        }
        $draft['asking_price'] = $price;
        $draft['asking_price_raw'] = clean_line((string) ($_POST['asking_price'] ?? ''), 20);
        $_SESSION['offer_draft'] = $draft;
        if ($errors) {
            render_offer_error($source, 3, $errors, $draft);
            return;
        }
        log_form_event('situation');
        redirect('/get-offer?step=4');
    }

    if ($step !== 4) {
        redirect('/get-offer');
    }

    $draft['full_name'] = clean_line((string) ($_POST['full_name'] ?? ''), 150);
    $draft['phone'] = clean_line((string) ($_POST['phone'] ?? ''), 40);
    $draft['email'] = strtolower(clean_line((string) ($_POST['email'] ?? ''), 190));
    $draft['best_time'] = (string) ($_POST['best_time'] ?? '');
    $consent = (($_POST['consent'] ?? '') === '1') ? 1 : 0;
    if (mb_strlen($draft['full_name']) < 2) {
        $errors['full_name'] = 'Enter your name.';
    }
    if (strlen(phone_digits($draft['phone'])) < 10) {
        $errors['phone'] = 'Enter a phone number with at least 10 digits.';
    }
    if (filter_var($draft['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (!isset(best_time_options()[$draft['best_time']])) {
        $errors['best_time'] = 'Choose the best time to reach you.';
    }
    $pending = $_SESSION['pending_photos'] ?? [];
    if (!is_array($pending)) {
        $pending = [];
    }
    $upload = collect_uploads('photos', count($pending));
    if ($upload['error'] !== null) {
        foreach ($upload['files'] as $file) {
            $path = media_path($file['stored_name']);
            if (is_file($path)) {
                unlink($path);
            }
        }
        $errors['photos'] = $upload['error'];
    } else {
        $pending = array_merge($pending, $upload['files']);
        $_SESSION['pending_photos'] = $pending;
    }
    if ($errors) {
        $_SESSION['offer_draft'] = $draft;
        render_offer_error($source, 4, $errors, $draft);
        return;
    }
    if (rate_limit_too_many(client_ip(), 'seller')) {
        header('Retry-After: ' . (string) ppb('security.rate_limit_window', 3600));
        render_offer_error($source, 4, [
            '_form' => 'Too many requests from this network. Email ' . ppb('business.email') . ' or try again later.',
        ], $draft);
        return;
    }
    rate_limit_hit(client_ip(), 'seller');

    $now = now_sql();
    $lead = [
        'full_name' => $draft['full_name'],
        'phone' => $draft['phone'],
        'email' => $draft['email'],
        'best_time' => option_label(best_time_options(), $draft['best_time']),
        'tcpa_consent' => $consent,
        'tcpa_text' => seller_consent_text(),
        'tcpa_at' => $consent === 1 ? $now : null,
        'tcpa_ip' => $consent === 1 ? client_ip() : '',
        'address' => $draft['address'],
        'city' => $draft['city'],
        'zip' => $draft['zip'],
        'property_type' => option_label(property_types(), (string) $draft['property_type']),
        'beds' => option_label(bed_options(), (string) $draft['beds']),
        'baths' => option_label(bath_options(), (string) $draft['baths']),
        'condition_label' => option_label(condition_options(), (string) $draft['condition_label']),
        'occupancy' => option_label(occupancy_options(), (string) $draft['occupancy']),
        'timeline' => option_label(timeline_options(), (string) $draft['timeline']),
        'reason' => $draft['reason'] === '' ? '' : option_label(reason_options(), (string) $draft['reason']),
        'asking_price' => $draft['asking_price'] ?? null,
        'utm' => (string) ($_SESSION['utm'] ?? ''),
        'source_path' => (string) ($draft['source_path'] ?? $source),
        'ip_address' => client_ip(),
        'user_agent' => client_ua(),
    ];
    $leadId = insert_lead($lead);
    open_deal($leadId);
    foreach ($pending as $file) {
        if (!is_array($file)) {
            continue;
        }
        add_media([
            'lead_id' => $leadId,
            'buyer_id' => null,
            'kind' => 'photo',
            'stored_name' => $file['stored_name'],
            'original_name' => $file['original_name'],
            'mime' => $file['mime'],
            'bytes' => $file['bytes'],
        ]);
    }
    unset($_SESSION['offer_draft'], $_SESSION['pending_photos']);
    log_form_event('submitted');
    $lead['id'] = $leadId;
    deliver_seller_notice($lead, 'received');
    redirect('/request-received');
}

function parse_optional_money(string $raw): array
{
    $raw = trim(str_replace(['$', ',', ' '], '', $raw));
    if ($raw === '') {
        return [null, null];
    }
    if (preg_match('/^\d{1,9}$/', $raw) !== 1) {
        return [null, 'Enter a whole dollar amount, or leave it blank.'];
    }
    $amount = (int) $raw;
    if ($amount > 100000000) {
        return [null, 'Enter a smaller amount.'];
    }
    return [$amount, null];
}

function parse_required_money(string $raw): array
{
    [$amount, $error] = parse_optional_money($raw);
    if ($error !== null) {
        return [null, $error];
    }
    if ($amount === null) {
        return [null, 'Enter a whole dollar amount.'];
    }
    return [$amount, null];
}

function show_home(array $errors = [], array $draft = []): void
{
    $description = 'Request a cash offer for your Valdosta or Lowndes County house. As-is, no repairs, and no fee from Prime Property Buys.';
    page('home', [
        'title' => 'Sell your house fast for cash, as-is | Prime Property Buys',
        'meta_description' => $description,
        'canonical' => absolute_url('/'),
        'json_ld' => json_ld_graph('/', 'Sell your house fast for cash, as-is', $description),
        'errors' => $errors,
        'draft' => $draft ?: offer_draft(),
        'source' => '/',
    ]);
}

function show_offer(int $step = 0, array $errors = [], array $draft = []): void
{
    $draft = $draft ?: offer_draft();
    if ($step < 1) {
        $requested = (int) ($_GET['step'] ?? 1);
        $step = min(max(1, $requested), offer_max_step($draft));
    }
    $description = 'Tell us about the house in four short steps. Prime Property Buys replies with a cash offer.';
    page('offer', [
        'title' => 'Get my cash offer | Prime Property Buys',
        'meta_description' => $description,
        'canonical' => absolute_url('/get-offer'),
        'json_ld' => json_ld_graph('/get-offer', 'Get my cash offer', $description),
        'step' => $step,
        'errors' => $errors,
        'draft' => $draft,
        'source' => '/get-offer',
        'pending_count' => count(is_array($_SESSION['pending_photos'] ?? null) ? $_SESSION['pending_photos'] : []),
    ]);
}

function show_town(array $loc, array $errors = [], array $draft = []): void
{
    $description = (string) $loc['description'];
    page('town', [
        'title' => $loc['title'] . ' | Prime Property Buys',
        'meta_description' => $description,
        'canonical' => absolute_url('/' . $loc['slug']),
        'json_ld' => json_ld_graph('/' . $loc['slug'], $loc['title'], $description),
        'loc' => $loc,
        'errors' => $errors,
        'draft' => $draft ?: offer_draft(),
        'source' => '/' . $loc['slug'],
    ]);
}

function show_how(): void
{
    $description = 'Request a look, review a written cash offer, and close with a local closing attorney. No repairs and no fee from us.';
    page('how', [
        'title' => 'How a cash sale works | Prime Property Buys',
        'meta_description' => $description,
        'canonical' => absolute_url('/how-it-works'),
        'json_ld' => json_ld_graph('/how-it-works', 'How a cash sale works', $description),
    ]);
}

function show_faq(): void
{
    $description = 'Fees, timing, repairs, liens, tenants, inherited houses, and who closes a cash sale in Lowndes County.';
    page('faq', [
        'title' => 'Questions about selling your house | Prime Property Buys',
        'meta_description' => $description,
        'canonical' => absolute_url('/faq'),
        'json_ld' => json_ld_graph('/faq', 'Questions', $description),
        'faqs' => home_faqs(),
    ]);
}

function show_about(): void
{
    $description = 'Prime Property Buys is operated by Anthony Jaamal Jones in Valdosta, Georgia, and is part of Vibe Virtue Solutions.';
    page('about', [
        'title' => 'About Prime Property Buys',
        'meta_description' => $description,
        'canonical' => absolute_url('/about'),
        'json_ld' => json_ld_graph('/about', 'About Prime Property Buys', $description),
    ]);
}

function show_contact(array $errors = [], array $old = []): void
{
    $description = 'Email info@primepropertybuys.com or send a short note. Phone number is not published yet.';
    page('contact', [
        'title' => 'Contact Prime Property Buys',
        'meta_description' => $description,
        'canonical' => absolute_url('/contact'),
        'json_ld' => json_ld_graph('/contact', 'Contact', $description),
        'errors' => $errors,
        'old' => $old,
    ]);
}

function show_privacy(): void
{
    $description = 'How Prime Property Buys uses information you send through this website.';
    page('privacy', [
        'title' => 'Privacy policy | Prime Property Buys',
        'meta_description' => $description,
        'canonical' => absolute_url('/privacy'),
        'json_ld' => null,
    ]);
}

function show_terms(): void
{
    $description = 'Terms for using the Prime Property Buys website and requesting a cash offer.';
    page('terms', [
        'title' => 'Terms | Prime Property Buys',
        'meta_description' => $description,
        'canonical' => absolute_url('/terms'),
        'json_ld' => null,
    ]);
}

function show_thanks(): void
{
    page('thanks', [
        'title' => 'Request received | Prime Property Buys',
        'meta_description' => 'We received your request.',
        'canonical' => absolute_url('/request-received'),
        'json_ld' => null,
    ], 'noindex, nofollow');
}

function show_investors(array $errors = [], array $old = []): void
{
    page('investors', [
        'title' => 'Buyer list',
        'meta_description' => 'Private buyer list.',
        'canonical' => absolute_url('/investors'),
        'json_ld' => null,
        'errors' => $errors,
        'old' => $old,
    ], 'noindex, nofollow', 'layout_min');
}

function show_sitemap(): void
{
    header('Content-Type: application/xml; charset=utf-8');
    header('X-Robots-Tag: noindex');
    $lastmod = date('Y-m-d');
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    foreach (sitemap_paths() as $path) {
        echo '  <url><loc>' . e(absolute_url($path)) . '</loc><lastmod>' . e($lastmod) . "</lastmod></url>\n";
    }
    echo "</urlset>\n";
}

function show_sheet(string $token): void
{
    $sheet = get_sheet_by_token($token);
    if (!$sheet || strtotime((string) $sheet['expires_at']) < time()) {
        http_response_code(404);
        page('error', [
            'title' => 'Not available',
            'heading' => 'This page is not available',
            'message' => 'The link is missing or it has expired.',
        ], 'noindex, nofollow', 'layout_min');
        return;
    }
    $photos = ((int) $sheet['show_photos'] === 1) ? list_lead_media((int) $sheet['lead_id']) : [];
    page('deal_sheet', [
        'title' => 'Property sheet',
        'meta_description' => 'Private property sheet.',
        'canonical' => absolute_url('/d/' . $token),
        'json_ld' => null,
        'sheet' => $sheet,
        'photos' => $photos,
    ], 'noindex, nofollow', 'layout_sheet');
}

function stream_sheet_photo(string $token, int $mediaId): void
{
    $sheet = get_sheet_by_token($token);
    $media = get_media($mediaId);
    if (!$sheet || !$media || strtotime((string) $sheet['expires_at']) < time() || (int) $sheet['show_photos'] !== 1) {
        http_response_code(404);
        exit;
    }
    if ((int) $media['lead_id'] !== (int) $sheet['lead_id']) {
        http_response_code(404);
        exit;
    }
    stream_media_file($media);
}

function stream_media_file(array $media): void
{
    $path = media_path((string) $media['stored_name']);
    if (!is_file($path)) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: ' . $media['mime']);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    header('X-Robots-Tag: noindex, nofollow');
    header('Content-Disposition: inline; filename="' . str_replace('"', '', (string) $media['original_name']) . '"');
    readfile($path);
    exit;
}

function show_unsubscribe(): void
{
    $email = strtolower(trim((string) ($_GET['e'] ?? '')));
    $token = (string) ($_GET['t'] ?? '');
    $ok = $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && hash_equals(unsub_token($email), $token);
    if ($ok) {
        unsubscribe_buyer($email);
    }
    page('unsub', [
        'title' => 'Email preferences',
        'meta_description' => 'Email preferences.',
        'canonical' => absolute_url('/investors/unsubscribe'),
        'json_ld' => null,
        'ok' => $ok,
    ], 'noindex, nofollow', 'layout_min');
}

function handle_contact_post(): void
{
    if (!require_csrf()) {
        return;
    }
    if (trim((string) ($_POST['website'] ?? '')) !== '') {
        redirect('/contact?sent=1');
    }
    if (rate_limit_too_many(client_ip(), 'contact')) {
        http_response_code(429);
        show_contact(['_form' => 'Too many messages from this network. Email ' . ppb('business.email') . ' instead.'], $_POST);
        return;
    }
    rate_limit_hit(client_ip(), 'contact');
    $name = clean_line((string) ($_POST['full_name'] ?? ''), 150);
    $phone = clean_line((string) ($_POST['phone'] ?? ''), 40);
    $email = strtolower(clean_line((string) ($_POST['email'] ?? ''), 190));
    $message = clean_text((string) ($_POST['message'] ?? ''), 2000);
    $errors = [];
    if (mb_strlen($name) < 2) {
        $errors['full_name'] = 'Enter your name.';
    }
    if (strlen(phone_digits($phone)) < 10) {
        $errors['phone'] = 'Enter a phone number with at least 10 digits.';
    }
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (mb_strlen($message) < 5) {
        $errors['message'] = 'Enter a short message.';
    }
    if ($errors) {
        http_response_code(422);
        show_contact($errors, $_POST);
        return;
    }
    $consent = (($_POST['consent'] ?? '') === '1') ? 1 : 0;
    insert_contact([
        'full_name' => $name,
        'phone' => $phone,
        'email' => $email,
        'message' => $message,
        'tcpa_consent' => $consent,
        'tcpa_text' => seller_consent_text(),
        'tcpa_at' => $consent === 1 ? now_sql() : null,
        'tcpa_ip' => $consent === 1 ? client_ip() : '',
    ]);
    $notify = (string) ppb('business.notification_email', '');
    if ($notify !== '') {
        send_plain_mail($notify, 'Website message from ' . mail_subject_piece($name), "Name: {$name}\nPhone: {$phone}\nEmail: {$email}\nCall/text consent: " . ($consent ? 'YES' : 'NO') . "\n\n{$message}\n");
    }
    redirect('/contact?sent=1');
}

function handle_investor_post(): void
{
    if (!require_csrf()) {
        return;
    }
    if (trim((string) ($_POST['website'] ?? '')) !== '') {
        redirect('/investors?sent=1');
    }
    if (rate_limit_too_many(client_ip(), 'buyer')) {
        http_response_code(429);
        show_investors(['_form' => 'Too many submissions. Try again later.'], $_POST);
        return;
    }
    rate_limit_hit(client_ip(), 'buyer');
    $parsed = buyer_from_post($_POST, false);
    if ($parsed['errors']) {
        http_response_code(422);
        show_investors($parsed['errors'], $_POST);
        return;
    }
    $id = insert_buyer($parsed['buyer']);
    insert_box($id, $parsed['box']);
    $upload = collect_uploads('pof', 0, 3, true);
    if ($upload['error'] === null) {
        foreach ($upload['files'] as $file) {
            add_media([
                'lead_id' => null,
                'buyer_id' => $id,
                'kind' => 'pof',
                'stored_name' => $file['stored_name'],
                'original_name' => $file['original_name'],
                'mime' => $file['mime'],
                'bytes' => $file['bytes'],
            ]);
        }
    }
    $mail = $parsed['buyer'];
    $mail['areas'] = $parsed['box']['areas'];
    $mail['tcpa_consent'] = (($_POST['consent'] ?? '') === '1') ? 1 : 0;
    deliver_buyer_signup_mail($mail);
    redirect('/investors?sent=1');
}

function buyer_from_post(array $post, bool $admin): array
{
    $errors = [];
    $company = clean_line((string) ($post['company'] ?? ''), 200);
    $contact = clean_line((string) ($post['contact_name'] ?? ''), 150);
    $email = strtolower(clean_line((string) ($post['email'] ?? ''), 190));
    $phone = clean_line((string) ($post['phone'] ?? ''), 40);
    $type = (string) ($post['buyer_type'] ?? 'other');
    $tier = (string) ($post['tier'] ?? 'C');
    $areas = clean_text((string) ($post['areas'] ?? ''), 500);
    $types = $post['property_types'] ?? [];
    if (!is_array($types)) {
        $types = [];
    }
    $typeLabels = [];
    foreach ($types as $typeKey) {
        $typeKey = (string) $typeKey;
        if (isset(property_types()[$typeKey])) {
            $typeLabels[] = property_types()[$typeKey];
        }
    }
    [$min, $minError] = parse_required_money((string) ($post['price_min'] ?? ''));
    [$max, $maxError] = parse_required_money((string) ($post['price_max'] ?? ''));
    if (mb_strlen($company) < 2) {
        $errors['company'] = 'Enter the company or fund name.';
    }
    if (mb_strlen($contact) < 2) {
        $errors['contact_name'] = 'Enter a contact name.';
    }
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (strlen(phone_digits($phone)) < 10) {
        $errors['phone'] = 'Enter a phone number.';
    }
    if (!isset(buyer_type_options()[$type])) {
        $errors['buyer_type'] = 'Choose a type.';
    }
    if (!in_array($tier, ['A', 'B', 'C'], true)) {
        $tier = 'C';
    }
    if (mb_strlen($areas) < 2) {
        $errors['areas'] = 'List the towns or ZIP codes.';
    }
    if (!$typeLabels) {
        $errors['property_types'] = 'Choose at least one property type.';
    }
    if ($minError) {
        $errors['price_min'] = $minError;
    }
    if ($maxError) {
        $errors['price_max'] = $maxError;
    }
    if ($min !== null && $max !== null && $min > $max) {
        $errors['price_max'] = 'The high price should be at least the low price.';
    }
    $minBeds = trim((string) ($post['min_beds'] ?? ''));
    $beds = null;
    if ($minBeds !== '') {
        if (preg_match('/^\d{1,2}$/', $minBeds) !== 1) {
            $errors['min_beds'] = 'Enter a number of bedrooms, or leave it blank.';
        } else {
            $beds = (int) $minBeds;
        }
    }
    $optIn = (($post['email_opt_in'] ?? '') === '1') ? 1 : 0;
    $pof = $admin && (($post['pof_on_file'] ?? '') === '1') ? 1 : 0;
    $pofDate = null;
    if ($admin) {
        $pofDateRaw = trim((string) ($post['pof_date'] ?? ''));
        if ($pofDateRaw !== '') {
            $dt = DateTimeImmutable::createFromFormat('Y-m-d', $pofDateRaw);
            if ($dt && $dt->format('Y-m-d') === $pofDateRaw) {
                $pofDate = $pofDateRaw;
            } else {
                $errors['pof_date'] = 'Enter a valid date.';
            }
        }
    }
    $closings = 0;
    if ($admin && trim((string) ($post['closings_count'] ?? '')) !== '') {
        if (preg_match('/^\d{1,4}$/', (string) $post['closings_count']) !== 1) {
            $errors['closings_count'] = 'Enter a number.';
        } else {
            $closings = (int) $post['closings_count'];
        }
    }
    return [
        'errors' => $errors,
        'buyer' => [
            'company' => $company,
            'buyer_type' => $type,
            'tier' => $admin ? $tier : 'C',
            'contact_name' => $contact,
            'email' => $email,
            'phone' => $phone,
            'contact_role' => clean_line((string) ($post['contact_role'] ?? ''), 80),
            'pof_on_file' => $pof,
            'pof_date' => $pofDate,
            'closings_count' => $closings,
            'email_opt_in' => $optIn,
            'notes' => $admin ? clean_text((string) ($post['notes'] ?? ''), 5000) : '',
        ],
        'box' => [
            'areas' => $areas,
            'property_types' => implode(', ', $typeLabels),
            'min_beds' => $beds,
            'min_baths' => null,
            'price_min' => $min,
            'price_max' => $max,
            'max_rehab' => null,
            'min_year' => null,
            'occupancy_ok' => 'any',
            'close_speed' => '',
        ],
    ];
}

function admin_dispatch(string $method, string $path): void
{
    if ($path === '/admin/login' && $method === 'GET') {
        show_login();
        return;
    }
    if ($path === '/admin/login' && $method === 'POST') {
        handle_login();
        return;
    }
    if ($path === '/admin/setup' && $method === 'GET') {
        show_setup();
        return;
    }
    if ($path === '/admin/setup' && $method === 'POST') {
        handle_setup();
        return;
    }
    if ($path === '/admin/logout' && $method === 'POST') {
        if (require_csrf()) {
            logout_admin();
        }
        redirect('/admin/login', 302);
    }
    if ($method === 'GET' && preg_match('#^/admin/export/(leads|deals|buyers|boxes)\.csv$#', $path, $m) === 1) {
        require_admin();
        export_csv($m[1]);
        return;
    }
    if ($method === 'GET' && preg_match('#^/admin/media/(\d+)$#', $path, $m) === 1) {
        require_admin();
        $media = get_media((int) $m[1]);
        if (!$media) {
            http_response_code(404);
            exit;
        }
        stream_media_file($media);
    }
    if ($method === 'POST' && preg_match('#^/admin/deals/(\d+)$#', $path, $m) === 1) {
        handle_deal_post((int) $m[1]);
        return;
    }
    if ($method === 'POST' && preg_match('#^/admin/buyers/(\d+)$#', $path, $m) === 1) {
        handle_buyer_post((int) $m[1]);
        return;
    }
    if ($method === 'POST' && $path === '/admin/buyers') {
        handle_buyer_create();
        return;
    }
    if ($method === 'POST' && $path === '/admin/tasks') {
        handle_task_done();
        return;
    }
    if ($method !== 'GET') {
        http_response_code(405);
        not_found();
        return;
    }
    if ($path === '/admin' || $path === '/admin/deals') {
        show_pipeline();
        return;
    }
    if (preg_match('#^/admin/deals/(\d+)$#', $path, $m) === 1) {
        show_deal((int) $m[1]);
        return;
    }
    if ($path === '/admin/buyers') {
        show_buyers();
        return;
    }
    if (preg_match('#^/admin/buyers/(\d+)$#', $path, $m) === 1) {
        show_buyer((int) $m[1]);
        return;
    }
    if ($path === '/admin/tasks') {
        show_tasks();
        return;
    }
    if ($path === '/admin/messages') {
        show_messages();
        return;
    }
    not_found();
}

function show_login(string $error = ''): void
{
    if (current_admin()) {
        redirect('/admin/deals', 302);
    }
    $next = safe_admin_next($_GET['next'] ?? '/admin/deals');
    page('admin/login', [
        'title' => 'Sign in',
        'error' => $error,
        'next' => $next,
    ], 'noindex, nofollow', 'admin/layout');
}

function handle_login(): void
{
    if (!require_csrf()) {
        return;
    }
    $ip = client_ip();
    if (rate_limit_too_many($ip, 'login')) {
        show_login('Too many sign-in attempts. Try again in 15 minutes.');
        return;
    }
    $username = strtolower(clean_line((string) ($_POST['username'] ?? ''), 80));
    $password = (string) ($_POST['password'] ?? '');
    $row = find_admin_by_username($username);
    $hash = is_array($row) ? (string) $row['password_hash'] : dummy_password_hash();
    $ok = password_verify($password, $hash);
    if (!$row || !$ok) {
        rate_limit_hit($ip, 'login');
        show_login('Those credentials do not match.');
        return;
    }
    login_admin($row);
    redirect(safe_admin_next($_POST['next'] ?? '/admin/deals'));
}

function show_setup(string $error = ''): void
{
    page('admin/setup', [
        'title' => 'Create the admin login',
        'error' => $error,
        'ready' => admin_count() > 0,
        'key_ok' => setup_key_is_configured(),
    ], 'noindex, nofollow', 'admin/layout');
}

function handle_setup(): void
{
    if (!require_csrf()) {
        return;
    }
    if (admin_count() > 0) {
        show_setup('An administrator account already exists.');
        return;
    }
    if (!setup_key_is_configured()) {
        show_setup('Set a setup_key of at least 12 characters in config.php before creating the login.');
        return;
    }
    $provided = (string) ($_POST['setup_key'] ?? '');
    $username = strtolower(clean_line((string) ($_POST['username'] ?? ''), 80));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');
    if (!setup_key_matches($provided)) {
        show_setup('The setup key does not match config.php.');
        return;
    }
    if (preg_match('/^[a-z0-9._-]{3,40}$/', $username) !== 1) {
        show_setup('Username must be 3–40 characters: letters, numbers, dot, underscore, or hyphen.');
        return;
    }
    if (strlen($password) < 12 || strlen($password) > 200) {
        show_setup('Use a password of at least 12 characters.');
        return;
    }
    if (!hash_equals($password, $confirm)) {
        show_setup('The passwords do not match.');
        return;
    }
    create_admin($username, $password);
    $user = find_admin_by_username($username);
    if (!$user) {
        show_setup('The account could not be created.');
        return;
    }
    login_admin($user);
    redirect('/admin/deals');
}

function filters_from_request(): array
{
    return [
        'stage' => clean_line((string) ($_GET['stage'] ?? ''), 40),
        'city' => clean_line((string) ($_GET['city'] ?? ''), 80),
        'q' => clean_line((string) ($_GET['q'] ?? ''), 100),
        'from' => clean_line((string) ($_GET['from'] ?? ''), 10),
        'to' => clean_line((string) ($_GET['to'] ?? ''), 10),
        'view' => (($_GET['view'] ?? '') === 'board') ? 'board' : 'table',
    ];
}

function show_pipeline(): void
{
    $filters = filters_from_request();
    $pageNo = max(1, (int) ($_GET['page'] ?? 1));
    $result = $filters['view'] === 'board'
        ? list_deals($filters, 1, 300)
        : list_deals($filters, $pageNo, 50);
    admin_page('admin/pipeline', [
        'title' => 'Pipeline',
        'filters' => $filters,
        'result' => $result,
        'counts' => stage_counts(),
        'events' => form_event_counts(),
        'saved' => flash_get('ok'),
    ]);
}

function show_deal(int $id): void
{
    $deal = get_deal($id);
    if (!$deal) {
        not_found();
        return;
    }
    admin_page('admin/deal', [
        'title' => 'Deal',
        'deal' => $deal,
        'history' => list_history($id),
        'notes' => list_notes('deal', $id),
        'tasks' => list_tasks('deal', $id),
        'photos' => list_lead_media((int) $deal['lead_id']),
        'sheets' => list_sheets($id),
        'buyers' => opted_in_buyers(),
        'saved' => flash_get('ok'),
        'error' => flash_get('err'),
    ]);
}

function nullable_date(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return [null, null];
    }
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $raw);
    if (!$dt || $dt->format('Y-m-d') !== $raw) {
        return [null, 'Use a real date.'];
    }
    return [$raw, null];
}

function handle_deal_post(int $id): void
{
    $deal = get_deal($id);
    if (!$deal || !require_csrf()) {
        return;
    }
    require_admin();
    $action = (string) ($_POST['action'] ?? 'save');
    if ($action === 'note') {
        $body = clean_text((string) ($_POST['body'] ?? ''), 5000);
        if ($body !== '') {
            add_note('deal', $id, $body);
            flash_set('ok', 'Note saved.');
        }
        redirect('/admin/deals/' . $id);
    }
    if ($action === 'task') {
        $title = clean_line((string) ($_POST['title'] ?? ''), 200);
        [$due, $dueError] = nullable_date((string) ($_POST['due_at'] ?? ''));
        if ($title === '' || $dueError) {
            flash_set('err', $dueError ?: 'Enter a task.');
        } else {
            add_task('deal', $id, $title, $due);
            flash_set('ok', 'Task added.');
        }
        redirect('/admin/deals/' . $id);
    }
    if ($action === 'email') {
        $kind = (string) ($_POST['kind'] ?? '');
        if (!in_array($kind, ['walkthrough', 'closing'], true)) {
            flash_set('err', 'Unknown email.');
            redirect('/admin/deals/' . $id);
        }
        deliver_seller_notice($deal, $kind);
        add_note('deal', $id, 'Sent the ' . $kind . ' email to the seller.');
        flash_set('ok', 'Email sent.');
        redirect('/admin/deals/' . $id);
    }
    if ($action === 'sheet') {
        create_sheet_from_post($deal);
        return;
    }
    if ($action === 'blast') {
        send_blast($deal);
        return;
    }

    $stage = (string) ($_POST['stage'] ?? $deal['stage']);
    $dead = clean_line((string) ($_POST['dead_reason'] ?? ''), 255);
    if (!isset(deal_stages()[$stage])) {
        flash_set('err', 'Unknown stage.');
        redirect('/admin/deals/' . $id);
    }
    if ($stage === 'dead' && $dead === '') {
        flash_set('err', 'Add a reason when the stage is Dead.');
        redirect('/admin/deals/' . $id);
    }
    $numbers = [];
    foreach (['arv', 'repair_est', 'mao', 'offer_amt', 'contract_price', 'assignment_fee'] as $key) {
        [$amount, $error] = parse_optional_money((string) ($_POST[$key] ?? ''));
        if ($error) {
            flash_set('err', $error);
            redirect('/admin/deals/' . $id);
        }
        $numbers[$key] = $amount;
    }
    foreach (['contract_date', 'inspection_deadline', 'closing_date'] as $key) {
        [$date, $error] = nullable_date((string) ($_POST[$key] ?? ''));
        if ($error) {
            flash_set('err', $error);
            redirect('/admin/deals/' . $id);
        }
        $numbers[$key] = $date;
    }
    $numbers['title_company'] = clean_line((string) ($_POST['title_company'] ?? ''), 200);
    $numbers['access_notes'] = clean_text((string) ($_POST['access_notes'] ?? ''), 2000);
    save_deal_fields($id, $numbers);
    $newStage = change_deal_stage($deal, $stage, $dead);
    $notice = $newStage ? seller_stage_notice($newStage) : null;
    if ($notice && (string) $deal['stage'] !== $newStage && (string) $deal['last_seller_notice'] !== $notice) {
        deliver_seller_notice($deal, $notice);
        mark_seller_notice($id, $notice);
    }
    audit('deal_update', 'deal:' . $id);
    flash_set('ok', 'Deal saved.');
    redirect('/admin/deals/' . $id);
}

function create_sheet_from_post(array $deal): void
{
    $location = clean_line((string) ($_POST['location_label'] ?? ''), 200);
    if ($location === '') {
        flash_set('err', 'Enter a location label. Do not include the seller’s name or phone.');
        redirect('/admin/deals/' . (int) $deal['id']);
    }
    $days = (int) ($_POST['expires_days'] ?? 14);
    if ($days < 1 || $days > 90) {
        $days = 14;
    }
    $token = bin2hex(random_bytes(16));
    $id = insert_sheet([
        'deal_id' => (int) $deal['id'],
        'token' => $token,
        'expires_at' => (new DateTimeImmutable('now'))->modify('+' . $days . ' days')->format('Y-m-d H:i:s'),
        'location_label' => $location,
        'price_text' => clean_line((string) ($_POST['price_text'] ?? ''), 80),
        'arv_text' => clean_line((string) ($_POST['arv_text'] ?? ''), 80),
        'rehab_text' => clean_line((string) ($_POST['rehab_text'] ?? ''), 80),
        'access_text' => clean_text((string) ($_POST['access_text'] ?? ''), 1000),
        'attorney_text' => clean_line((string) ($_POST['attorney_text'] ?? ''), 200),
        'deadline_text' => clean_line((string) ($_POST['deadline_text'] ?? ''), 120),
        'show_photos' => (($_POST['show_photos'] ?? '') === '1') ? 1 : 0,
    ]);
    audit('deal_sheet', 'sheet:' . $id);
    flash_set('ok', 'Deal sheet created.');
    redirect('/admin/deals/' . (int) $deal['id']);
}

function send_blast(array $deal): void
{
    $sheetId = (int) ($_POST['sheet_id'] ?? 0);
    $sheets = list_sheets((int) $deal['id']);
    $sheet = null;
    foreach ($sheets as $row) {
        if ((int) $row['id'] === $sheetId) {
            $sheet = $row;
        }
    }
    if (!$sheet) {
        flash_set('err', 'Choose a deal sheet first.');
        redirect('/admin/deals/' . (int) $deal['id']);
    }
    $ids = $_POST['buyer_ids'] ?? [];
    if (!is_array($ids)) {
        $ids = [];
    }
    $sent = 0;
    foreach ($ids as $buyerId) {
        $buyer = get_buyer((int) $buyerId);
        if (!$buyer) {
            continue;
        }
        if (deliver_buyer_blast($buyer, $sheet)) {
            log_blast((int) $sheet['id'], (int) $buyer['id']);
            $sent++;
        }
    }
    audit('blast', 'sheet:' . (int) $sheet['id'] . ' sent:' . $sent);
    flash_set('ok', $sent . ' buyer email' . ($sent === 1 ? '' : 's') . ' sent.');
    redirect('/admin/deals/' . (int) $deal['id']);
}

function show_buyers(): void
{
    admin_page('admin/buyers', [
        'title' => 'Buyers',
        'buyers' => list_buyers(),
        'saved' => flash_get('ok'),
        'error' => flash_get('err'),
    ]);
}

function handle_buyer_create(): void
{
    if (!require_csrf()) {
        return;
    }
    require_admin();
    $parsed = buyer_from_post($_POST, true);
    if ($parsed['errors']) {
        flash_set('err', 'Check the buyer form and try again.');
        redirect('/admin/buyers');
    }
    $id = insert_buyer($parsed['buyer']);
    insert_box($id, $parsed['box']);
    audit('buyer_create', 'buyer:' . $id);
    flash_set('ok', 'Buyer saved.');
    redirect('/admin/buyers/' . $id);
}

function show_buyer(int $id): void
{
    $buyer = get_buyer($id);
    if (!$buyer) {
        not_found();
        return;
    }
    admin_page('admin/buyer', [
        'title' => 'Buyer',
        'buyer' => $buyer,
        'boxes' => list_boxes($id),
        'saved' => flash_get('ok'),
        'error' => flash_get('err'),
    ]);
}

function handle_buyer_post(int $id): void
{
    $buyer = get_buyer($id);
    if (!$buyer || !require_csrf()) {
        return;
    }
    require_admin();
    $action = (string) ($_POST['action'] ?? 'save');
    if ($action === 'delete_box') {
        delete_box((int) ($_POST['box_id'] ?? 0), $id);
        flash_set('ok', 'Buy box removed.');
        redirect('/admin/buyers/' . $id);
    }
    if ($action === 'box') {
        $areas = clean_text((string) ($_POST['areas'] ?? ''), 500);
        [$min, $minError] = parse_optional_money((string) ($_POST['price_min'] ?? ''));
        [$max, $maxError] = parse_optional_money((string) ($_POST['price_max'] ?? ''));
        if ($areas === '' || $minError || $maxError) {
            flash_set('err', $minError ?: ($maxError ?: 'Enter the areas for the buy box.'));
            redirect('/admin/buyers/' . $id);
        }
        $beds = trim((string) ($_POST['min_beds'] ?? ''));
        $baths = trim((string) ($_POST['min_baths'] ?? ''));
        $year = trim((string) ($_POST['min_year'] ?? ''));
        [$rehab, $rehabError] = parse_optional_money((string) ($_POST['max_rehab'] ?? ''));
        if ($rehabError) {
            flash_set('err', $rehabError);
            redirect('/admin/buyers/' . $id);
        }
        if ($year !== '' && preg_match('/^(18|19|20)\d{2}$/', $year) !== 1) {
            flash_set('err', 'Year built should be a four-digit year, or blank.');
            redirect('/admin/buyers/' . $id);
        }
        $speed = (string) ($_POST['close_speed'] ?? '');
        if (!in_array($speed, ['', '7', '14', '30', 'flexible'], true)) {
            $speed = '';
        }
        insert_box($id, [
            'areas' => $areas,
            'property_types' => clean_line((string) ($_POST['property_types'] ?? ''), 255),
            'min_beds' => preg_match('/^\d{1,2}$/', $beds) ? (int) $beds : null,
            'min_baths' => preg_match('/^\d{1,2}$/', $baths) ? (int) $baths : null,
            'price_min' => $min,
            'price_max' => $max,
            'max_rehab' => $rehab,
            'min_year' => $year === '' ? null : (int) $year,
            'occupancy_ok' => in_array(($_POST['occupancy_ok'] ?? 'any'), ['any', 'vacant', 'occupied'], true) ? (string) $_POST['occupancy_ok'] : 'any',
            'close_speed' => $speed,
        ]);
        flash_set('ok', 'Buy box added.');
        redirect('/admin/buyers/' . $id);
    }
    $parsed = buyer_from_post($_POST, true);
    if ($parsed['errors']) {
        flash_set('err', 'Check the buyer fields.');
        redirect('/admin/buyers/' . $id);
    }
    update_buyer($id, $parsed['buyer']);
    audit('buyer_update', 'buyer:' . $id);
    flash_set('ok', 'Buyer saved.');
    redirect('/admin/buyers/' . $id);
}

function show_tasks(): void
{
    admin_page('admin/tasks', [
        'title' => 'Tasks',
        'tasks' => tasks_today(),
    ]);
}

function handle_task_done(): void
{
    if (!require_csrf()) {
        return;
    }
    require_admin();
    complete_task((int) ($_POST['task_id'] ?? 0));
    redirect('/admin/tasks');
}

function show_messages(): void
{
    admin_page('admin/messages', [
        'title' => 'Messages',
        'messages' => list_contacts(),
    ]);
}

function export_csv(string $which): void
{
    $map = [
        'leads' => 'csv_leads',
        'deals' => 'csv_deals',
        'buyers' => 'csv_buyers',
        'boxes' => 'csv_boxes',
    ];
    $rows = $map[$which]();
    audit('export', $which);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $which . '.csv"');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    if ($out === false) {
        exit;
    }
    if (!$rows) {
        fputcsv($out, ['empty']);
        exit;
    }
    fputcsv($out, array_keys($rows[0]));
    foreach ($rows as $row) {
        $line = [];
        foreach ($row as $value) {
            $line[] = csv_safe((string) ($value ?? ''));
        }
        fputcsv($out, $line);
    }
    exit;
}
