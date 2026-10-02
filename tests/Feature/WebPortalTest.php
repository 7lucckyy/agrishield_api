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
        ->assertSee('Field Voice')
        ->assertSee('Farmers and field teams work from the same record.')
        ->assertSee('Satellite crop monitoring')
        ->assertSee('Soil health and moisture')
        ->assertSee('Crop image diagnosis')
        ->assertSee('Voice notes from the field')
        ->assertSee('Access to farm loans')
        ->assertSee('Weather risk and advisories')
        ->assertSee('NDVI · healthy signal')
        ->assertSee('A simpler path from “what changed?” to “what happens next?”')
        ->assertSee('The farm plan stays visible wherever the work moves.')
        ->assertSee('The same clear field story in English, Hausa and French.')
        ->assertSee('Can one farm contain different crops?')
        ->assertSee('jigawa-farmer.webp')
        ->assertSee('hawul-borno-farmland.webp')
        ->assertSee('favicon.svg')
        ->assertSee('site.webmanifest')
        ->assertSee('agrishield-social.png')
        ->assertSee('aria-label="Primary navigation"', escape: false)
        ->assertSee('aria-controls="primary-navigation"', escape: false)
        ->assertSeeInOrder(['Platform', 'How it works', 'Field Voice', 'About', 'Sign in', 'Plan a deployment'])
        ->assertSeeInOrder(['jigawa-farmer.webp', 'Plan each part of the farm around what is planted there.', 'North farm'])
        ->assertDontSee('API readiness')
        ->assertSee('Discuss a deployment');
});

test('the public landing page switches and remembers supported languages', function () {
    $this->get(route('home', ['lang' => 'ha']))
        ->assertSuccessful()
        ->assertSee('<html lang="ha">', escape: false)
        ->assertSee('Ka ga kowane fili. Ka san abin da ke bukatar kulawa.')
        ->assertSee('Gonar Arewa');

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('<html lang="ha">', escape: false)
        ->assertSee('Tsara gona sashe bayan sashe.');

    $this->get(route('home', ['lang' => 'fr']))
        ->assertSuccessful()
        ->assertSee('<html lang="fr">', escape: false)
        ->assertSee('Voyez chaque parcelle. Sachez où agir.')
        ->assertSee('Ferme Nord');
});

test('the landing page presents only accountable product evidence', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSeeInOrder([
            'Map',
            'Monitor',
            'Review',
            'Act',
        ])
        ->assertSee('Illustrative interface · example data')
        ->assertSee('It keeps data freshness, provider status and human review visible.')
        ->assertSee('Does the platform invent advice when data is missing?')
        ->assertDontSee('Trusted by thousands')
        ->assertDontSee('industry-leading accuracy')
        ->assertDontSee('Yield prediction')
        ->assertDontSee('Automated irrigation');
});

test('the public navigation identifies the current page', function () {
    $this->get(route('solutions'))
        ->assertSuccessful()
        ->assertSee('aria-current="page"', escape: false)
        ->assertSee('>Platform</a>', escape: false);

    $this->get(route('impact'))
        ->assertSuccessful()
        ->assertSee('aria-current="page"', escape: false)
        ->assertSee('>How it works</a>', escape: false);
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
        ->assertSee(route('field-voice'))
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
    'favicon' => 'favicon.svg',
    'legacy favicon' => 'favicon.ico',
    'small favicon' => 'favicon-32x32.png',
    'apple touch icon' => 'apple-touch-icon.png',
    'web app icon' => 'icons/icon-192.png',
    'large web app icon' => 'icons/icon-512.png',
    'social preview' => 'images/og/agrishield-social.png',
    'web manifest' => 'site.webmanifest',
]);

test('the public company pages present the current agrishield offering', function (string $route, string $content) {
    $this->get(route($route))
        ->assertSuccessful()
        ->assertSee($content)
        ->assertSee('hello@agrishield.ai');
})->with([
    'about' => ['about', 'Crop support needs'],
    'solutions' => ['solutions', 'Keep every farm signal'],
    'impact' => ['impact', 'From farm record'],
    'partners' => ['partners', 'Start with a field workflow'],
    'team' => ['team', 'Agricultural, data and field expertise'],
    'contact' => ['contact', 'Bring us the crop'],
    'field voice' => ['field-voice', 'Capture the question'],
]);

test('every public company page is localized in Hausa and French', function (string $route) {
    $this->get(route($route, ['lang' => 'ha']))
        ->assertSuccessful()
        ->assertSee('<html lang="ha">', escape: false)
        ->assertDontSee('marketing.pages.');

    $this->get(route($route, ['lang' => 'fr']))
        ->assertSuccessful()
        ->assertSee('<html lang="fr">', escape: false)
        ->assertDontSee('marketing.pages.');
})->with([
    'about',
    'solutions',
    'impact',
    'partners',
    'team',
    'contact',
    'field-voice',
]);

test('the product scope is crop only and keeps the startup offer focused', function () {
    $this->get(route('solutions'))
        ->assertSuccessful()
        ->assertSee('The AgriShield platform')
        ->assertSee('Field Voice')
        ->assertSee('Satellite and NDVI')
        ->assertSee('Soil moisture and weather')
        ->assertSee('Image diagnosis and Field Voice')
        ->assertSee('Farm loans and services')
        ->assertSee('Advisories and follow-through')
        ->assertDontSee('Livestock')
        ->assertDontSee('Agricultural MEAL')
        ->assertDontSee('Market Access &amp; Trade Linkages', escape: false)
        ->assertDontSee('Mechanisation &amp; Equipment Access', escape: false);

    $this->get(route('impact'))
        ->assertSuccessful()
        ->assertSee('Retain evidence')
        ->assertDontSee('Livestock intelligence');
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

    $this->get(route('team'))
        ->assertSuccessful()
        ->assertDontSee('Dr. Amina Bello')
        ->assertDontSee('Engr. Musa Ibrahim');

    $this->get(route('partners'))
        ->assertSuccessful()
        ->assertDontSee('FAO')
        ->assertDontSee('World Bank')
        ->assertSee('Every deployment starts with named responsibilities');
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
