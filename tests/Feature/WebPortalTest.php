<?php

declare(strict_types=1);

use App\Enums\GlobalRole;
use App\Enums\OrganizationRole;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

test('the public product website explains the agrishield proposition', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('See every field. Know what needs attention.')
        ->assertSee('Agricultural intelligence for every farm')
        ->assertSee('Farmers and field teams work from the same record.')
        ->assertSee('A field can change overnight.')
        ->assertSee('Know the farm')
        ->assertSee('Notice change')
        ->assertSee('Hear from the field')
        ->assertSee('Follow through')
        ->assertSee('cassava-field.webp')
        ->assertSee('jos-farmers.webp')
        ->assertSee('agrishield-social-cover.png')
        ->assertSee('favicon.svg')
        ->assertSee('site.webmanifest')
        ->assertSee('aria-label="Primary navigation"', escape: false)
        ->assertSee('aria-controls="primary-navigation"', escape: false)
        ->assertSeeInOrder(['Solutions', 'About', 'Resources', 'Careers', 'Sign in', 'Contact'])
        ->assertSeeInOrder(['cassava-field.webp', 'A field can change overnight.', 'Know the farm'])
        ->assertDontSee('North farm')
        ->assertDontSee('API readiness')
        ->assertSee('Discuss a deployment');
});

test('the public website supports English and French without exposing the Hausa website translation', function () {
    $this->get(route('home', ['lang' => 'ha']))
        ->assertSuccessful()
        ->assertSee('<html lang="en">', escape: false)
        ->assertDontSee('hreflang="ha"', escape: false)
        ->assertDontSee('Hausa');

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('<html lang="en">', escape: false);

    $this->get(route('home', ['lang' => 'fr']))
        ->assertSuccessful()
        ->assertSee('<html lang="fr">', escape: false)
        ->assertSee('Voyez chaque parcelle. Sachez où agir.')
        ->assertSee('Connaître la ferme');
});

test('a previously selected Hausa locale cannot localize a public marketing page', function () {
    expect(config('app.supported_locales'))->toContain('ha');

    $this->withSession(['website_locale' => 'ha'])
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('<html lang="en">', escape: false)
        ->assertDontSee('hreflang="ha"', escape: false);
});

test('public pages omit regional positioning and implementation details', function (string $route) {
    $this->get(route($route))
        ->assertSuccessful()
        ->assertDontSee('Northern Nigeria')
        ->assertDontSee('Nord du Nigeria')
        ->assertDontSee('geo.region')
        ->assertDontSee('geo.placename')
        ->assertDontSee('areaServed')
        ->assertDontSee('hreflang="ha"', escape: false)
        ->assertDontSee('agrishield-social.png')
        ->assertDontSee('₦80,000')
        ->assertDontSee('Maize buying window');
})->with(['home', 'about', 'solutions', 'partners', 'resources', 'faqs', 'careers', 'contact']);

test('the public sign-in page has no regional positioning', function () {
    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('Agricultural intelligence')
        ->assertDontSee('Northern Nigeria');
});

test('the landing page explains value without publishing illustrative operations or commercial terms', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('A field-first point of view')
        ->assertDontSee('id="workflow"', escape: false)
        ->assertDontSee('class="gs-proof-band"', escape: false)
        ->assertDontSee('class="gs-language"', escape: false)
        ->assertDontSee('class="gs-faq"', escape: false)
        ->assertDontSee('North farm')
        ->assertDontSee('Maize buying window')
        ->assertDontSee('₦85,000')
        ->assertDontSee('₦80,000')
        ->assertDontSee('Illustrative interface')
        ->assertDontSee('Trusted by thousands')
        ->assertDontSee('industry-leading accuracy')
        ->assertDontSee('Yield prediction')
        ->assertDontSee('Automated irrigation');
});

test('the public navigation identifies the current page', function () {
    $this->get(route('solutions'))
        ->assertSuccessful()
        ->assertSee('aria-current="page"', escape: false)
        ->assertSee('>Solutions</a>', escape: false);

    $this->get(route('partners'))
        ->assertSuccessful()
        ->assertSee('aria-current="page"', escape: false)
        ->assertSee('>Partners</a>', escape: false);

    $this->get(route('resources'))
        ->assertSuccessful()
        ->assertSee('>Resources</summary>', escape: false);

    $this->get(route('careers'))
        ->assertSuccessful()
        ->assertSee('>Careers</a>', escape: false);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSeeInOrder(['>Solutions</a>', '>About</summary>', '>Resources</summary>', '>Careers</a>', '>Contact</a>'], escape: false)
        ->assertSeeInOrder(['>Our story</a>', '>Partners</a>', '>Resources</a>', '>FAQs</a>'], escape: false)
        ->assertDontSee('Our Approach')
        ->assertSeeInOrder(['<strong>Explore</strong>', '<strong>Company</strong>', '<strong>Help &amp; resources</strong>'], escape: false);
});

test('the public footer and contact page display the supplied office details without unverified social links', function () {
    foreach (['home', 'contact', 'careers'] as $route) {
        $this->get(route($route))
            ->assertSuccessful()
            ->assertSee('No. J8 Kashim Shettima Plaza, Opposite UNIMAID Gate 1, Maiduguri, Nigeria')
            ->assertSee('href="tel:09021135021"', escape: false)
            ->assertSee('09021135021')
            ->assertDontSee('linkedin.com')
            ->assertDontSee('instagram.com');
    }

    $this->get(route('home'))
        ->assertSee('"telephone": "09021135021"', escape: false)
        ->assertSee('"addressLocality": "Maiduguri"', escape: false);
});

test('resources faqs and careers offer useful public destinations without fabricated claims', function () {
    $this->get(route('resources'))
        ->assertSuccessful()
        ->assertSee('Read the field before you decide.')
        ->assertSeeInOrder(['Know the context', 'Look for patterns', 'Close the loop'])
        ->assertSee('Evidence before certainty.')
        ->assertSee(route('faqs'));

    $this->get(route('faqs'))
        ->assertSuccessful()
        ->assertSee('Straight answers, useful next steps.')
        ->assertSee('Does AgriShield replace an agronomist?')
        ->assertSee('No. It helps organise useful information')
        ->assertSee(route('contact'));

    $this->get(route('careers'))
        ->assertSuccessful()
        ->assertSee('Build things that hold up in the field.')
        ->assertSee('No roles are listed right now.')
        ->assertSee('mailto:hello@agrishield.ai?subject=Careers%20enquiry')
        ->assertDontSee('Apply now');

    $this->get(route('resources', ['lang' => 'fr']))
        ->assertSuccessful()
        ->assertSee('Lire le terrain avant de décider.')
        ->assertSee('Navigation principale');

    $this->get(route('careers', ['lang' => 'fr']))
        ->assertSuccessful()
        ->assertSee('Construire des outils qui tiennent sur le terrain.');
});

test('the company page explains its purpose values and theory of change in supported languages', function () {
    $this->get(route('about'))
        ->assertSuccessful()
        ->assertSee('aria-label="Explore our company"', escape: false)
        ->assertSee('href="#purpose"', escape: false)
        ->assertSeeInOrder(['href="#purpose"', 'href="#values"', 'href="#change"'], escape: false)
        ->assertSee('href="#change"', escape: false)
        ->assertSee('href="#values"', escape: false)
        ->assertSee('Our mission')
        ->assertSee('Our vision')
        ->assertSee('Theory of Change')
        ->assertSeeInOrder(['Scattered information', 'Shared understanding', 'More confident choices', 'Stronger farm resilience'])
        ->assertSee('Effects on livelihoods and yields require field evaluation')
        ->assertSee('Our Values');

    $aboutPage = $this->get(route('about'));
    $aboutPage->assertSeeInOrder(['id="purpose"', 'id="values"', 'id="change"'], escape: false);

    $this->get(route('about', ['lang' => 'fr']))
        ->assertSuccessful()
        ->assertSee('Notre mission')
        ->assertSee('Notre vision')
        ->assertSee('Théorie du changement');

    $this->get(route('about', ['lang' => 'ha']))
        ->assertSuccessful()
        ->assertSee('<html lang="en">', escape: false)
        ->assertSee('Our mission');
});

test('the public website uses one restrained editorial design system', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('class="marketing-page gs-site', escape: false)
        ->assertSee('class="gs-hero"', escape: false)
        ->assertSee('class="gs-capability-list"', escape: false)
        ->assertSee('class="gs-conviction"', escape: false)
        ->assertDontSee('class="gs-farm-record"', escape: false)
        ->assertDontSee('class="agri-kicker', escape: false)
        ->assertDontSee('class="audience-card', escape: false)
        ->assertDontSee('class="capability-module', escape: false);

    $this->get(route('about'))
        ->assertSuccessful()
        ->assertSee('class="gs-about-hero gs-container"', escape: false)
        ->assertSee('jos-planting.webp')
        ->assertSee('id="about-principles"', escape: false)
        ->assertDontSee('Always remember that you are absolutely unique');
    $this->get(route('solutions'))->assertSuccessful()->assertSee('class="gs-platform-hero"', escape: false);
    $this->get(route('partners'))->assertSuccessful()->assertSee('class="gs-partner-hero"', escape: false);
    $this->get(route('contact'))->assertSuccessful()->assertSee('class="gs-contact-intro gs-container"', escape: false);
});

test('the public marketing pages keep transactional details for private conversations', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertDontSee('id="product-reality"', escape: false)
        ->assertDontSee('data-product-showcase', escape: false)
        ->assertDontSee('Record fulfilment and farm profit')
        ->assertDontSee('Application requested');

    $this->get(route('solutions'))
        ->assertSuccessful()
        ->assertDontSee('Verified markets and group access')
        ->assertDontSee('Voice money book and profitability')
        ->assertDontSee('CORE / AVAILABLE');
});

test('the public platform describes four broad capabilities without demo internals', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSeeInOrder([
            'Know the farm',
            'Notice change',
            'Hear from the field',
            'Follow through',
        ]);

    $this->get(route('solutions'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Know the farm', 'Notice change', 'Hear from the field', 'Follow through'])
        ->assertSee('Decisions remain in human hands.');
});

test('the public website keeps the detailed workflow private', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertDontSee('id="workflow"', escape: false)
        ->assertDontSee('href="#workflow"', escape: false);

    expect(Route::has('impact'))->toBeFalse()
        ->and(Route::has('team'))->toBeFalse()
        ->and(Route::has('field-voice'))->toBeFalse();

    $this->get('/impact')->assertNotFound();
    $this->get('/team')->assertNotFound();
    $this->get('/field-voice')->assertNotFound();
});

test('public crawler metadata is production ready', function () {
    $this->get(route('robots'))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('User-agent: *')
        ->assertSee('Disallow: /platform/')
        ->assertSee(route('sitemap'));

    $this->get(route('sitemap'))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee(route('home'))
        ->assertSee(route('solutions'))
        ->assertSee(route('resources'))
        ->assertSee(route('faqs'))
        ->assertSee(route('careers'))
        ->assertDontSee('/field-voice')
        ->assertDontSee('/impact')
        ->assertDontSee('/team')
        ->assertDontSee(route('login'))
        ->assertDontSee('/platform');

    $this->get(route('security'))
        ->assertSuccessful()
        ->assertSee('security@agrishield.ai')
        ->assertSee('Preferred-Languages: en');
});

test('public responses include baseline security headers', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), geolocation=(), microphone=(self)')
        ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
});

test('production brand assets are present', function (string $asset) {
    $path = public_path($asset);

    expect($path)->toBeFile()
        ->and(filesize($path))->toBeGreaterThan(0);
})->with([
    'brand mark' => 'brand/agrishield-mark.svg',
    'reversed brand mark' => 'brand/agrishield-mark-reversed.svg',
    'horizontal brand logo' => 'brand/agrishield-logo-horizontal.svg',
    'reversed horizontal brand logo' => 'brand/agrishield-logo-reversed.svg',
    'stacked brand logo' => 'brand/agrishield-logo-stacked.svg',
    'favicon' => 'favicon.svg',
    'legacy favicon' => 'favicon.ico',
    'small favicon' => 'favicon-32x32.png',
    'apple touch icon' => 'apple-touch-icon.png',
    'web app icon' => 'icons/icon-192.png',
    'large web app icon' => 'icons/icon-512.png',
    'social profile image' => 'images/agrishield-ai-social-profile.png',
    'social cover image' => 'images/agrishield-social-cover.png',
    'web manifest' => 'site.webmanifest',
    'Kano field work' => 'images/field/commons/kano-farmer-tending-field.webp',
    'Kano rice harvest' => 'images/field/commons/kano-rice-harvest.webp',
    'Kano wheat harvest' => 'images/field/commons/kano-wheat-harvest.webp',
    'Kano hand irrigation' => 'images/field/commons/kano-hand-irrigation.webp',
    'Bauchi solar rice farm' => 'images/field/commons/bauchi-solar-rice.webp',
    'Bauchi irrigation workers' => 'images/field/commons/bauchi-watering-workers.webp',
]);

test('the website uses the current agrishield identity system', function () {
    $brandMark = file_get_contents(public_path('brand/agrishield-mark.svg'));
    $horizontalLogo = file_get_contents(public_path('brand/agrishield-logo-horizontal.svg'));
    $manifest = file_get_contents(public_path('site.webmanifest'));

    expect($brandMark)
        ->toContain('#38E75E')
        ->toContain('Two growing leaves')
        ->toContain('fill-rule="evenodd"')
        ->and($horizontalLogo)
        ->toContain('AgriShield')
        ->toContain('#00A86B')
        ->toContain('#38E75E')
        ->not->toContain('>AI</text>')
        ->and($manifest)
        ->toContain('#006837')
        ->toContain('#F6F0E3');
});

test('social identity assets contain no generative provenance metadata', function () {
    foreach (['images/agrishield-ai-social-profile.png', 'images/agrishield-social-cover.png'] as $asset) {
        $contents = file_get_contents(public_path($asset));

        expect($contents)
            ->not->toContain('c2pa')
            ->not->toContain('dcterms:provenance')
            ->not->toContain('Content Credentials')
            ->not->toContain('Adobe Photoshop');
    }
});

test('active social campaign graphics contain no generative provenance metadata', function (string $asset) {
    $contents = file_get_contents(public_path('images/social/'.$asset));

    expect($contents)
        ->not->toContain('c2pa')
        ->not->toContain('dcterms:provenance')
        ->not->toContain('Content Credentials')
        ->not->toContain('Adobe Photoshop')
        ->not->toContain('OpenAI');
})->with([
    'field context' => 'agrishield-social-pro-01-field-context.png',
    'field work' => 'agrishield-social-pro-02-field-work.png',
    'field to action' => 'agrishield-social-pro-03-field-to-action.png',
    'progress context' => 'agrishield-social-pro-04-progress-context.png',
    'enumerator recruitment' => 'agrishield-hiring-field-enumerators-2026-11-04-pro.png',
    'field intelligence campaign' => 'agrishield-social-01-field-intelligence.png',
    'crop health campaign' => 'agrishield-social-02-crop-health.png',
    'weather field action campaign' => 'agrishield-social-03-weather-field-action.png',
    'farmer first campaign' => 'agrishield-social-04-farmer-first.png',
    'enumerator recruitment original' => 'agrishield-hiring-field-enumerators-2026-11-04.png',
    'enumerator recruitment version two' => 'agrishield-hiring-field-enumerators-2026-11-04-v2.png',
    'enumerator recruitment version three' => 'agrishield-hiring-field-enumerators-2026-11-04-v3.png',
    'creative field patterns' => 'agrishield-creative-01-field-patterns.png',
    'creative crop evidence' => 'agrishield-creative-02-crop-evidence.png',
    'creative weather action' => 'agrishield-creative-03-weather-action.png',
    'creative farmer stories' => 'agrishield-creative-04-farmer-stories.png',
    'human farmer behind the data' => 'agrishield-human-01-farmer-behind-data.png',
    'human every harvest' => 'agrishield-human-02-every-harvest.png',
    'human field reality' => 'agrishield-human-03-field-reality.png',
    'human whole field' => 'agrishield-human-04-whole-field.png',
]);

test('the twenty-post social campaign is complete and metadata clean', function () {
    $pngAssets = glob(public_path('images/social/campaign-20/*.png')) ?: [];
    $jpgAssets = glob(public_path('images/social/campaign-20/*.jpg')) ?: [];

    expect($pngAssets)->toHaveCount(20)
        ->and($jpgAssets)->toHaveCount(20);

    foreach (array_merge($pngAssets, $jpgAssets) as $asset) {
        $dimensions = getimagesize($asset);
        $contents = file_get_contents($asset);

        expect($dimensions)
            ->not->toBeFalse()
            ->and($dimensions[0])->toBe(1350)
            ->and($dimensions[1])->toBe(1350)
            ->and($contents)
            ->not->toContain('c2pa')
            ->not->toContain('dcterms:provenance')
            ->not->toContain('Content Credentials')
            ->not->toContain('Adobe Photoshop')
            ->not->toContain('OpenAI');
    }
});

test('public pages use distinct field photographs without visible source captions', function () {
    $photos = [];

    foreach (['home', 'solutions', 'about', 'partners', 'resources', 'faqs', 'careers', 'contact'] as $route) {
        $response = $this->get(route($route))->assertSuccessful();
        $content = $response->getContent();

        preg_match('~<main\b[^>]*>(.*?)</main>~s', $content, $main);
        preg_match_all('~images/field/[^"?]+\.(?:webp|jpeg)~', $main[1] ?? '', $matches);

        foreach (array_unique($matches[0]) as $photo) {
            expect($photo)->not->toBeIn($photos);
            $photos[] = $photo;
        }

        expect($content)->not->toContain('Wikimedia Commons', 'CC BY-SA 4.0', 'Photo:');
    }

    expect($photos)->toHaveCount(8);
});

test('public field photography meets the high-resolution presentation standard', function (string $asset) {
    $dimensions = getimagesize(public_path("images/field/commons/{$asset}"));

    expect($dimensions)->not->toBeFalse()
        ->and($dimensions[0])->toBeGreaterThanOrEqual(1900)
        ->and($dimensions[1])->toBeGreaterThanOrEqual(1200);
})->with([
    'Kano field work' => 'kano-farmer-tending-field.webp',
    'Kano rice harvest' => 'kano-rice-harvest.webp',
    'Kano wheat harvest' => 'kano-wheat-harvest.webp',
    'Kano hand irrigation' => 'kano-hand-irrigation.webp',
    'Bauchi solar rice farm' => 'bauchi-solar-rice.webp',
    'Bauchi irrigation workers' => 'bauchi-watering-workers.webp',
]);

test('the public company pages present the current agrishield offering', function (string $route, string $content) {
    $this->get(route($route))
        ->assertSuccessful()
        ->assertSee($content)
        ->assertSee('hello@agrishield.ai');
})->with([
    'about' => ['about', 'The next farm visit'],
    'solutions' => ['solutions', 'A farm record that knows'],
    'partners' => ['partners', 'Start with the farms'],
    'contact' => ['contact', 'Bring us the crop'],
    'resources' => ['resources', 'Read the field before you decide.'],
    'faqs' => ['faqs', 'Straight answers, useful next steps.'],
    'careers' => ['careers', 'Build things that hold up in the field.'],
]);

test('every public company page keeps Hausa off the website while retaining French', function (string $route) {
    $this->get(route($route, ['lang' => 'ha']))
        ->assertSuccessful()
        ->assertSee('<html lang="en">', escape: false)
        ->assertDontSee('hreflang="ha"', escape: false)
        ->assertDontSee('marketing.pages.');

    $this->get(route($route, ['lang' => 'fr']))
        ->assertSuccessful()
        ->assertSee('<html lang="fr">', escape: false)
        ->assertDontSee('marketing.pages.');
})->with([
    'about',
    'solutions',
    'partners',
    'contact',
    'resources',
    'faqs',
    'careers',
]);

test('the platform page presents a public overview without the operating model', function () {
    $this->get(route('solutions'))
        ->assertSuccessful()
        ->assertSee('The AgriShield platform')
        ->assertSee('Know the farm')
        ->assertSee('Notice change')
        ->assertSee('Hear from the field')
        ->assertSee('Follow through')
        ->assertSee('Responsible by design')
        ->assertDontSee('Farm loans and services')
        ->assertDontSee('Livestock')
        ->assertDontSee('Agricultural MEAL');

});

test('public platform capabilities are backed by registered routes', function (string $routeName) {
    expect(Route::has($routeName))->toBeTrue();
})->with([
    'crop catalogue' => 'crops.index',
    'farms' => 'farms.index',
    'crop cycles' => 'farms.crop-cycles.index',
    'farm sections' => 'farms.sections.index',
    'satellite observations' => 'farms.satellite-observations.index',
    'satellite summary' => 'farms.satellite-observations.summary',
    'soil health' => 'farms.soil-health.show',
    'weather' => 'farms.weather.show',
    'voice intake' => 'voice-assistance.store',
    'crop cases' => 'farms.diagnosis-requests.store',
    'advisories' => 'farms.advisories.store',
    'advisory acknowledgement' => 'farms.advisories.acknowledge',
    'farm finance products' => 'asset-finance.products.index',
    'farm finance applications' => 'asset-finance.applications.store',
    'organization overview' => 'organizations.overview.show',
    'integration operations' => 'integrations.index',
    'health check' => 'health.show',
]);

test('the public website does not borrow unverified credibility', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertDontSee('Illustrative service network')
        ->assertDontSee('95%')
        ->assertDontSee('30% higher yields')
        ->assertDontSee('RESPONSIBLE BY DESIGN');

    $this->get(route('partners'))
        ->assertSuccessful()
        ->assertDontSee('FAO')
        ->assertDontSee('World Bank')
        ->assertSee('Good partnerships begin with shared responsibility');
});

test('a platform administrator signs in and reaches platform control', function () {
    Role::findOrCreate(GlobalRole::PlatformAdmin->value, 'web');
    $administrator = User::factory()->create([
        'email' => 'platform@example.com',
        'password' => 'correct-password',
    ]);
    $administrator->assignRole(GlobalRole::PlatformAdmin->value);

    $this->post(route('login.store'), [
        'email' => $administrator->email,
        'password' => 'correct-password',
    ])->assertRedirect(route('platform.dashboard'));

    $this->assertAuthenticatedAs($administrator);
    $this->get(route('platform.dashboard'))
        ->assertSuccessful()
        ->assertSee('Operating picture')
        ->assertSee('Operations queue');
});

test('an organization administrator signs in and sees only their workspace', function () {
    $administrator = User::factory()->create([
        'email' => 'organization@example.com',
        'password' => 'correct-password',
    ]);
    $organization = Organization::factory()->create(['name' => 'Greenbelt Cooperative']);
    $otherOrganization = Organization::factory()->create();
    attachOrganizationRole($administrator, $organization, OrganizationRole::OrganizationAdmin);
    Farm::factory()->for($organization)->for($administrator, 'owner')->create(['name' => 'River Bend Farm']);

    $this->post(route('login.store'), [
        'email' => $administrator->email,
        'password' => 'correct-password',
    ])->assertRedirect(route('organization.dashboard', $organization));

    $this->get(route('organization.dashboard', $organization))
        ->assertSuccessful()
        ->assertSee('Greenbelt Cooperative')
        ->assertSee('River Bend Farm');

    $this->get(route('organization.dashboard', $otherOrganization))->assertNotFound();
    $this->get(route('platform.dashboard'))->assertForbidden();
});

test('failed web authentication returns a useful validation error', function () {
    User::factory()->create(['email' => 'field@example.com']);

    $this->from(route('login'))
        ->post(route('login.store'), ['email' => 'field@example.com', 'password' => 'wrong-password'])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});
