<?php
declare(strict_types=1);

if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}

function service_towns(): array
{
    return [
        'Valdosta' => 'Valdosta',
        'Hahira' => 'Hahira',
        'Lake Park' => 'Lake Park',
        'Remerton' => 'Remerton',
        'Lowndes County' => 'Lowndes County',
    ];
}

function detect_town(string $address): string
{
    $hay = strtolower($address);
    foreach (['Hahira' => 'hahira', 'Lake Park' => 'lake park', 'Remerton' => 'remerton', 'Valdosta' => 'valdosta'] as $label => $needle) {
        if (str_contains($hay, $needle)) {
            return $label;
        }
    }
    if (str_contains($hay, 'lowndes')) {
        return 'Lowndes County';
    }
    return '';
}

function property_types(): array
{
    return [
        'single_family' => 'Single-family house',
        'multifamily' => 'Duplex or small multifamily',
        'condo' => 'Condo or townhouse',
        'mobile' => 'Mobile home',
        'other' => 'Other',
    ];
}

function bed_options(): array
{
    return ['0' => 'Studio / 0', '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6+', 'unsure' => 'Not sure'];
}

function bath_options(): array
{
    return ['1' => '1', '1.5' => '1.5', '2' => '2', '2.5' => '2.5', '3' => '3', '4' => '4+', 'unsure' => 'Not sure'];
}

function condition_options(): array
{
    return [
        'excellent' => 'Excellent',
        'good' => 'Good',
        'some_work' => 'Needs some work',
        'major_work' => 'Needs major work',
    ];
}

function occupancy_options(): array
{
    return [
        'owner' => 'Owner occupied',
        'vacant' => 'Vacant',
        'tenant' => 'Tenant occupied',
    ];
}

function timeline_options(): array
{
    return [
        'asap' => 'As soon as possible',
        '30' => 'Within 30 days',
        '60' => '60 days or more',
        'flexible' => 'Flexible',
    ];
}

function reason_options(): array
{
    return [
        '' => 'Optional',
        'relocating' => 'Relocating',
        'inherited' => 'Inherited the house',
        'financial' => 'Financial reasons',
        'divorce' => 'Divorce or separation',
        'managing' => 'Tired of managing it',
        'repairs' => 'Repairs are more than I want',
        'downsizing' => 'Downsizing',
        'prefer_not' => 'Prefer not to say',
    ];
}

function best_time_options(): array
{
    return [
        'morning' => 'Morning',
        'afternoon' => 'Afternoon',
        'evening' => 'Evening',
        'anytime' => 'Anytime',
    ];
}

function buyer_type_options(): array
{
    return [
        'institutional' => 'Institutional fund',
        'hedge' => 'Hedge fund',
        'ibuyer' => 'iBuyer',
        'local' => 'Local investor',
        'landlord' => 'Landlord',
        'flipper' => 'Flipper',
        'other' => 'Other',
    ];
}

function deal_stages(): array
{
    return [
        'lead' => 'Lead',
        'contacted' => 'Contacted',
        'offer_made' => 'Offer made',
        'under_contract' => 'Under contract',
        'assigned' => 'Assigned/Marketed',
        'closed' => 'Closed',
        'dead' => 'Dead',
    ];
}

function seller_stage_notice(string $stage): ?string
{
    return match ($stage) {
        'contacted' => 'reviewing',
        'offer_made' => 'offer',
        'under_contract', 'assigned' => 'under_contract',
        'closed' => 'closed',
        default => null,
    };
}

function option_label(array $options, string $key): string
{
    return $options[$key] ?? $key;
}

function locations(): array
{
    return [
        'valdosta' => [
            'name' => 'Valdosta',
            'title' => 'Sell your Valdosta house for cash, as-is',
            'description' => 'Request a cash offer for a Valdosta, Georgia house. As-is, no repairs, and no fee from Prime Property Buys.',
            'card' => 'County seat of Lowndes County. Older blocks near downtown and newer subdivisions are both fine as they stand.',
            'paragraphs' => [
                'Valdosta is the county seat and the main city in this part of south Georgia. The houses range from older streets near downtown and Valdosta State University to subdivisions north and west of town.',
                'People write when a house needs work, when they live somewhere else, or when they would rather not list it. We look at the address, the condition, and what a resale would take. A cash price is often below a retail asking price. That is a normal trade for an as-is sale. You compare the number with the time and repairs of listing, and you decide.',
                'Mail sometimes says Valdosta for a house that is actually in Remerton or in the unincorporated county. Send the street address you have. We sort the city from that.',
            ],
            'points' => [
                'Houses that need a roof, mechanical work, or a clean-out',
                'Vacant or inherited houses, including ones that still have belongings',
                'A rental you no longer want to manage',
            ],
        ],
        'hahira' => [
            'name' => 'Hahira',
            'title' => 'Sell your Hahira house for cash, as-is',
            'description' => 'Cash offers for houses in Hahira, Georgia. Sell as-is to Prime Property Buys, north of Valdosta in Lowndes County.',
            'card' => 'A smaller city north of Valdosta along I-75, including houses that need work before anyone would list them.',
            'paragraphs' => [
                'Hahira sits north of Valdosta on the I-75 corridor. It is known locally for the Hahira Honeybee Festival, and it is quieter than the county seat. A listed sale here can mean fewer showings, especially if the house needs work.',
                'We make offers inside the city and on nearby county roads. You do not need a new kitchen or an empty garage before we can give you a number. If the property is a house, not a commercial site, the address is enough to start.',
                'Tell us if the house is easy to reach. A gate, a shared drive, or a long lane belongs in the condition notes so the offer matches the property.',
            ],
            'points' => [
                'Moving closer to work in Valdosta, or leaving the area',
                'An estate that includes a house you do not want to renovate',
                'Deferred maintenance that would start a listing with a repair list',
            ],
        ],
        'lake-park' => [
            'name' => 'Lake Park',
            'title' => 'Sell your Lake Park house for cash, as-is',
            'description' => 'Cash offers for Lake Park, Georgia houses near the Florida line. As-is, with a local closing.',
            'card' => 'South Lowndes County, close to the Florida line and I-75. Useful if you are handling a house from somewhere else.',
            'paragraphs' => [
                'Lake Park is at the south end of Lowndes County, along I-75 and close to the Georgia–Florida line. Some owners are leaving the area, settling a relative’s house, or trying to manage a sale from another city.',
                'The steps do not change because Lake Park is a short drive from Valdosta. You send the address and the condition. We review it and reply with a cash offer. You do not hold an open house, and you do not repair the property for us.',
                'If the house is south of town but still in Lowndes County, use the same form. If it is across the state line in Florida, say so. We focus on Lowndes County, Georgia, and we will tell you if an address is outside the area we can close.',
            ],
            'points' => [
                'A house you inherited and do not plan to move into',
                'A property that has been empty, including an overgrown yard',
                'A sale you want to handle by email because you are not local',
            ],
        ],
        'remerton' => [
            'name' => 'Remerton',
            'title' => 'Sell your Remerton house for cash, as-is',
            'description' => 'Cash offers for houses in Remerton, Georgia, the city surrounded by Valdosta. As-is, no listing required.',
            'card' => 'A separate city wrapped around by Valdosta, including residential streets near the Baytree area.',
            'paragraphs' => [
                'Remerton is easy to miss on a map because Valdosta surrounds it. It is its own city, with residential streets and property near the Baytree area. Mail and GPS often say Valdosta even when the house is in Remerton.',
                'That label does not block a sale. Put the street address in the form and choose the condition the house is in today, not the condition you wish it were in.',
                'An as-is cash offer is a trade. You may net less than a successful retail sale, and you skip the repair list and the showings. Read the written offer. You are free to say no.',
            ],
            'points' => [
                'Repairs have piled up and you do not want a contractor project',
                'You want one offer instead of weeks of showings',
                'You are done maintaining a house after a move',
            ],
        ],
        'lowndes-county' => [
            'name' => 'Lowndes County',
            'title' => 'Sell your Lowndes County house for cash, as-is',
            'description' => 'Cash offers across Lowndes County, Georgia, including unincorporated addresses outside Valdosta, Hahira, Lake Park, and Remerton.',
            'card' => 'Unincorporated addresses, county roads, and houses on a well or septic. You do not need a city label to start.',
            'paragraphs' => [
                'Lowndes County runs from the Florida line north past Hahira, with Valdosta in the middle. Outside the cities, houses sit on county roads and older homesites. Some are on a well or septic instead of city utilities. Those facts change the price. They do not stop an offer.',
                'If you are not sure which city limits you are in, start here. Tell us the street address, the city you use for mail, and the condition. If access is unusual, say so when we follow up.',
                'Prime Property Buys is operated by Anthony Jaamal Jones in Valdosta and is part of Vibe Virtue Solutions. ' . brokerage_disclosure(),
            ],
            'points' => [
                'Houses that would not photograph well: dated finishes, damage, or a full clean-out',
                'An inherited house with extra acreage, when you want to sell the house rather than develop the land',
                'A vacant house relatives are still paying taxes and insurance on',
            ],
        ],
    ];
}

function location_by_slug(string $slug): ?array
{
    $all = locations();
    if (!isset($all[$slug])) {
        return null;
    }
    $row = $all[$slug];
    $row['slug'] = $slug;
    return $row;
}

function home_faqs(): array
{
    $email = (string) ppb('business.email', 'info@primepropertybuys.com');
    $owner = (string) ppb('business.owner', 'Anthony Jaamal Jones');
    $parent = (string) ppb('business.parent', 'Vibe Virtue Solutions');
    return [
        [
            'q' => 'Do you charge a fee?',
            'a' => 'We do not charge a fee to review the house or to make an offer. Any cost at closing is written into the contract before you sign. Ask the closing attorney if you want a second reading.',
        ],
        [
            'q' => 'Do I need to repair the house first?',
            'a' => 'No. We look at houses as they are, including houses that need work or are full of belongings. You can remove anything you want to keep before closing.',
        ],
        [
            'q' => 'How fast can we close?',
            'a' => 'After we have the facts, we can usually put a written offer in front of you quickly. The closing date is agreed in the contract. Title work, a payoff, or probate can add time. We do not advertise a guaranteed number of days.',
        ],
        [
            'q' => 'What if there is a mortgage or a lien?',
            'a' => 'Send the address anyway. Liens and mortgages are paid from the sale when title allows it. If the payoff is higher than the offer, we will say so before you sign anything.',
        ],
        [
            'q' => 'What if a tenant lives there?',
            'a' => 'Choose “Tenant occupied” on the form. A lease can continue or end depending on the lease and what you want. We will ask before we quote a price.',
        ],
        [
            'q' => 'What if I inherited the house?',
            'a' => 'You can still ask for an offer. If a probate case is open, or someone else must sign, the closing waits on that authority. We do not give legal advice.',
        ],
        [
            'q' => 'How is the offer calculated?',
            'a' => 'We look at condition, location, repairs, and what similar houses have sold for. A cash, as-is number is often lower than a retail asking price, because of the repairs and the time we take on. We do not publish a percentage or promise a retail price.',
        ],
        [
            'q' => 'Who closes?',
            'a' => 'A Georgia closing attorney or a title company handles the paperwork. ' . disclosure_line() . ' ' . brokerage_disclosure() . ' The written contract is where you see the terms before you sign.',
        ],
        [
            'q' => 'Am I obligated if I ask for an offer?',
            'a' => 'No. The form is a request for a number. You are committed only if you sign a contract.',
        ],
        [
            'q' => 'Who operates Prime Property Buys?',
            'a' => $owner . ' in Valdosta and Lowndes County, Georgia. The business is part of ' . $parent . '. The legal entity name on file is ' . legal_entity_label() . '. Email ' . $email . '.',
        ],
    ];
}

function json_ld_graph(string $path, string $pageName, string $description): string
{
    $url = absolute_url($path);
    $businessId = site_url() . '/#business';
    $phone = contact_phone();
    $business = [
        '@type' => 'LocalBusiness',
        '@id' => $businessId,
        'name' => (string) ppb('business.name', 'Prime Property Buys'),
        'description' => 'Prime Property Buys makes cash offers on houses in Valdosta and Lowndes County, Georgia. ' . brokerage_disclosure(),
        'url' => site_url() . '/',
        'email' => (string) ppb('business.email', 'info@primepropertybuys.com'),
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => 'Valdosta',
            'addressRegion' => 'GA',
            'addressCountry' => 'US',
        ],
        'areaServed' => [
            ['@type' => 'City', 'name' => 'Valdosta'],
            ['@type' => 'City', 'name' => 'Hahira'],
            ['@type' => 'City', 'name' => 'Lake Park'],
            ['@type' => 'City', 'name' => 'Remerton'],
            ['@type' => 'AdministrativeArea', 'name' => 'Lowndes County'],
        ],
        'founder' => [
            '@type' => 'Person',
            'name' => (string) ppb('business.owner', 'Anthony Jaamal Jones'),
        ],
        'parentOrganization' => [
            '@type' => 'Organization',
            'name' => (string) ppb('business.parent', 'Vibe Virtue Solutions'),
        ],
    ];
    if ($phone['callable']) {
        $business['telephone'] = $phone['tel'];
    }
    $graph = [
        $business,
        [
            '@type' => 'WebSite',
            '@id' => site_url() . '/#website',
            'url' => site_url() . '/',
            'name' => (string) ppb('business.name', 'Prime Property Buys'),
            'publisher' => ['@id' => $businessId],
        ],
        [
            '@type' => 'WebPage',
            '@id' => $url . '#webpage',
            'url' => $url,
            'name' => $pageName,
            'description' => $description,
            'isPartOf' => ['@id' => site_url() . '/#website'],
            'about' => ['@id' => $businessId],
            'inLanguage' => 'en-US',
        ],
    ];
    return json_encode(
        ['@context' => 'https://schema.org', '@graph' => $graph],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) ?: '{}';
}

function sitemap_paths(): array
{
    $paths = ['/', '/get-offer', '/how-it-works', '/faq', '/about', '/contact', '/privacy', '/terms'];
    foreach (array_keys(locations()) as $slug) {
        $paths[] = '/' . $slug;
    }
    return $paths;
}
