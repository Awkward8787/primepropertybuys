<?php
declare(strict_types=1);

$base = getenv('BASE') ?: 'http://127.0.0.1:8080';
$fail = 0;

function check(bool $ok, string $label): void
{
    global $fail;
    if ($ok) {
        echo "ok  {$label}\n";
        return;
    }
    $fail++;
    echo "FAIL {$label}\n";
}

function req(string $method, string $url, array $opts = []): array
{
    $ch = curl_init($url);
    $headers = [];
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $opts['jar'] ?? '',
        CURLOPT_COOKIEFILE => $opts['jar'] ?? '',
    ]);
    if (isset($opts['body'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['body']);
    }
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $raw = is_string($raw) ? $raw : '';
    return ['status' => $status, 'headers' => substr($raw, 0, $headerSize), 'body' => substr($raw, $headerSize)];
}

function csrf_from(string $html): string
{
    if (preg_match('/name="_csrf" value="([^"]+)"/', $html, $m) !== 1) {
        return '';
    }
    return $m[1];
}

$jar = tempnam(sys_get_temp_dir(), 'ppb');
$sellerPaths = ['/', '/get-offer', '/how-it-works', '/faq', '/about', '/contact', '/privacy', '/terms', '/valdosta', '/hahira', '/lake-park', '/remerton', '/lowndes-county', '/request-received'];
$disclosure = 'We may purchase directly or partner with or assign our purchase contract to other buyers.';
$forbidden = '/\b(funds?|hedge|institutional|investors?|assignment|wholesale|buyers?|partner|assign)\b/i';

foreach ($sellerPaths as $path) {
    $res = req('GET', $base . $path, ['jar' => $jar]);
    check($res['status'] === 200, "GET {$path} {$res['status']}");
    $stripped = str_replace($disclosure, '', $res['body']);
    $hit = preg_match($forbidden, $stripped, $m) === 1;
    check(!$hit, "seller copy {$path}" . ($hit ? " has {$m[0]}" : ''));
    check(!str_contains($res['body'], 'href="/investors"') && !str_contains($res['body'], 'href="/admin"'), "no private links {$path}");
}

$home = req('GET', $base . '/', ['jar' => $jar]);
check(str_contains($home['body'], 'Sell your house fast for cash, as-is'), 'headline');
check(str_contains($home['body'], 'info@primepropertybuys.com'), 'public email');
check(str_contains($home['body'], '(TBD)'), 'phone placeholder');
check(str_contains($home['body'], '[LEGAL ENTITY]'), 'legal entity placeholder');
check(str_contains($home['body'], $disclosure), 'footer disclosure');
check(str_contains($home['body'], 'LocalBusiness'), 'local business schema');
check(!str_contains($home['body'], 'RealEstateAgent'), 'not marked up as an agent');
check(!preg_match('/name="consent"[^>]*checked/', $home['body']), 'consent not prechecked on home');

$terms = req('GET', $base . '/terms', ['jar' => $jar]);
check(substr_count($terms['body'], $disclosure) >= 1, 'terms disclosure');
$faq = req('GET', $base . '/faq', ['jar' => $jar]);
check(str_contains($faq['body'], $disclosure), 'faq disclosure');
check(str_contains(req('GET', $base . '/how-it-works', ['jar' => $jar])['body'], '[BOOKING TOOL]'), 'booking placeholder');

$investors = req('GET', $base . '/investors', ['jar' => $jar]);
check(str_contains($investors['body'], 'noindex'), 'investors noindex');
check(str_contains($investors['body'], 'Institutional'), 'investors page is the buyer page');
check(!str_contains($investors['body'], 'href="/get-offer"'), 'investors do not link to the seller form');

$sitemap = req('GET', $base . '/sitemap.xml', ['jar' => $jar]);
check(!str_contains($sitemap['body'], '/investors') && !str_contains($sitemap['body'], '/admin') && !str_contains($sitemap['body'], '/d/'), 'sitemap exclusions');
check(str_contains($sitemap['body'], '/valdosta'), 'sitemap towns');
$robots = req('GET', $base . '/robots.txt', ['jar' => $jar]);
check(str_contains($robots['body'], 'Disallow: /investors') && str_contains($robots['body'], 'Disallow: /admin'), 'robots rules');

$blocked = req('GET', $base . '/config.sample.php', ['jar' => $jar]);
check($blocked['status'] === 403, 'config sample blocked');

$setup = req('GET', $base . '/admin/setup', ['jar' => $jar]);
$token = csrf_from($setup['body']);
check($token !== '', 'setup csrf');
$created = req('POST', $base . '/admin/setup', ['jar' => $jar, 'body' => http_build_query([
    '_csrf' => $token,
    'setup_key' => 'local-dev-setup-key-e02e',
    'username' => 'jaamal',
    'password' => 'correct-horse-battery',
    'confirm' => 'correct-horse-battery',
])]);
check($created['status'] === 303, 'setup redirects');

$offer = req('GET', $base . '/', ['jar' => $jar]);
$token = csrf_from($offer['body']);
$step1 = req('POST', $base . '/get-offer', ['jar' => $jar, 'body' => http_build_query([
    '_csrf' => $token,
    'step' => 1,
    'source' => '/',
    'website' => '',
    'address' => '123 Test Street, Valdosta, GA',
    'city' => '',
    'zip' => '31601',
])]);
check($step1['status'] === 303 && str_contains($step1['headers'], 'step=2'), 'town auto-detected');

$offer = req('GET', $base . '/get-offer?step=2', ['jar' => $jar]);
$token = csrf_from($offer['body']);
$step2 = req('POST', $base . '/get-offer', ['jar' => $jar, 'body' => http_build_query([
    '_csrf' => $token, 'step' => 2, 'source' => '/get-offer', 'website' => '',
    'property_type' => 'single_family', 'beds' => '3', 'baths' => '2',
    'condition_label' => 'some_work', 'occupancy' => 'vacant',
])]);
check($step2['status'] === 303, 'step 2');

$offer = req('GET', $base . '/get-offer?step=3', ['jar' => $jar]);
$token = csrf_from($offer['body']);
$step3 = req('POST', $base . '/get-offer', ['jar' => $jar, 'body' => http_build_query([
    '_csrf' => $token, 'step' => 3, 'source' => '/get-offer', 'website' => '',
    'timeline' => 'asap', 'reason' => 'inherited', 'asking_price' => '150000',
])]);
check($step3['status'] === 303, 'step 3');

$offer = req('GET', $base . '/get-offer?step=4', ['jar' => $jar]);
check(!preg_match('/id="consent"[^>]*checked/', $offer['body']), 'step 4 consent unchecked');
$token = csrf_from($offer['body']);
$step4 = req('POST', $base . '/get-offer', ['jar' => $jar, 'body' => http_build_query([
    '_csrf' => $token, 'step' => 4, 'source' => '/get-offer', 'website' => '',
    'full_name' => 'Test Seller', 'phone' => '2295550100', 'email' => 'seller@example.com',
    'best_time' => 'morning',
])]);
check($step4['status'] === 303 && str_contains($step4['headers'], '/request-received'), 'lead saved');

$pipe = req('GET', $base . '/admin/deals', ['jar' => $jar]);
check(str_contains($pipe['body'], '123 Test Street, Valdosta, GA'), 'lead in pipeline');
check(str_contains($pipe['body'], 'Email only'), 'missing consent is visible');

if (preg_match('#/admin/deals/(\d+)#', $pipe['body'], $m) !== 1) {
    check(false, 'deal link');
    $dealId = '0';
} else {
    $dealId = $m[1];
    check(true, 'deal link');
}
$deal = req('GET', $base . '/admin/deals/' . $dealId, ['jar' => $jar]);
check(!str_contains($deal['body'], 'hedge fund') === false || !str_contains(strtolower($deal['body']), 'seller@example.com') === false, 'deal has seller email');
check(str_contains($deal['body'], 'seller@example.com'), 'deal shows seller');
$token = csrf_from($deal['body']);
$saved = req('POST', $base . '/admin/deals/' . $dealId, ['jar' => $jar, 'body' => http_build_query([
    '_csrf' => $token, 'action' => 'save', 'stage' => 'under_contract', 'dead_reason' => '',
    'arv' => '180000', 'repair_est' => '25000', 'mao' => '120000', 'offer_amt' => '90000',
    'contract_price' => '90000', 'assignment_fee' => '10000',
    'contract_date' => '2026-11-01', 'inspection_deadline' => '2026-11-10', 'closing_date' => '2026-11-20',
    'title_company' => 'Lowndes Closing', 'access_notes' => 'Seller phone must stay internal',
])]);
check($saved['status'] === 303, 'deal save');

$deal = req('GET', $base . '/admin/deals/' . $dealId, ['jar' => $jar]);
$token = csrf_from($deal['body']);
$sheet = req('POST', $base . '/admin/deals/' . $dealId, ['jar' => $jar, 'body' => http_build_query([
    '_csrf' => $token, 'action' => 'sheet', 'location_label' => 'Valdosta, near downtown',
    'price_text' => '$90,000', 'arv_text' => '$180,000', 'rehab_text' => '$25,000',
    'access_text' => 'Lockbox on the front door', 'attorney_text' => 'Lowndes Closing',
    'deadline_text' => 'November 10', 'expires_days' => '14', 'show_photos' => '0',
])]);
check($sheet['status'] === 303, 'sheet created');
$deal = req('GET', $base . '/admin/deals/' . $dealId, ['jar' => $jar]);
if (preg_match('#/d/([a-f0-9]{32})#', $deal['body'], $sm) !== 1) {
    check(false, 'sheet token');
} else {
    $public = req('GET', $base . '/d/' . $sm[1], ['jar' => $jar]);
    check(str_contains($public['body'], 'noindex'), 'sheet noindex');
    check(str_contains($public['body'], 'Valdosta, near downtown'), 'sheet location');
    check(!str_contains($public['body'], 'Test Seller') && !str_contains($public['body'], 'seller@example.com') && !str_contains($public['body'], '2295550100'), 'sheet hides seller');
    check(!str_contains($public['body'], 'inherited'), 'sheet hides situation');
}

$buyers = req('GET', $base . '/admin/buyers', ['jar' => $jar]);
$token = csrf_from($buyers['body']);
$made = req('POST', $base . '/admin/buyers', ['jar' => $jar, 'body' => http_build_query([
    '_csrf' => $token, 'company' => 'Example Capital', 'contact_name' => 'Casey Buyer',
    'email' => 'buyer@example.com', 'phone' => '2295550199', 'buyer_type' => 'local', 'tier' => 'A',
    'areas' => 'Valdosta', 'price_min' => '50000', 'price_max' => '200000', 'min_beds' => '2',
    'property_types' => ['single_family'], 'email_opt_in' => '1', 'pof_on_file' => '1', 'closings_count' => '3',
])]);
check($made['status'] === 303, 'buyer created');

$csv = req('GET', $base . '/admin/export/leads.csv', ['jar' => $jar]);
check($csv['status'] === 200 && str_contains($csv['body'], '123 Test Street'), 'csv export');

$hpJar = tempnam(sys_get_temp_dir(), 'ppbhp');
$hpPage = req('GET', $base . '/', ['jar' => $hpJar]);
$hp = req('POST', $base . '/get-offer', ['jar' => $hpJar, 'body' => http_build_query([
    '_csrf' => csrf_from($hpPage['body']), 'step' => 1, 'source' => '/', 'website' => 'http://spam.test',
    'address' => '999 Honeypot Lane, Valdosta', 'city' => 'Valdosta',
])]);
check($hp['status'] === 303, 'honeypot pretends success');
$again = req('GET', $base . '/admin/deals?q=' . rawurlencode('Honeypot'), ['jar' => $jar]);
check(!str_contains($again['body'], '999 Honeypot Lane'), 'honeypot not stored');

define('PPB_APP', true);
putenv('PPB_CONFIG=' . dirname(__DIR__) . '/config.php');
require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/uploads.php';
$sample = [
    'address' => '123 Test Street', 'full_name' => 'Test Seller', 'phone' => '2295550100',
    'email' => 'seller@example.com', 'tcpa_consent' => 0,
];
foreach (['received', 'reviewing', 'walkthrough', 'offer', 'under_contract', 'closing', 'closed'] as $kind) {
    $message = seller_message($kind, $sample);
    $blob = $message['subject'] . "\n" . $message['body'];
    $bad = preg_match($forbidden, $blob, $mm) === 1;
    check(!$bad, "seller email {$kind}" . ($bad ? " has {$mm[0]}" : ''));
}

echo $fail === 0 ? "ALL PASSED\n" : "{$fail} FAILED\n";
exit($fail === 0 ? 0 : 1);
