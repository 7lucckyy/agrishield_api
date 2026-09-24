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
        ->assertSee('From a farmer’s question to the next field action.')
        ->assertSee('Northern Nigeria')
        ->assertSee('Field Voice')
        ->assertSee('One record.')
        ->assertSee('Five responsible handoffs.')
        ->assertSee('Private organisation workspaces')
        ->assertSee('Clear answers before field work begins.')
        ->assertSee('jigawa-farmer.webp')
        ->assertSee('hawul-borno-farmland.webp')
        ->assertSee('favicon.svg')
        ->assertSee('site.webmanifest')
        ->assertSee('agrishield-social.png')
        ->assertSee('aria-label="Primary navigation"', escape: false)
        ->assertSee('aria-controls="primary-navigation"', escape: false)
        ->assertSeeInOrder(['Platform', 'Field Voice', 'How it works', 'Company', 'Sign in', 'Plan a deployment'])
        ->assertDontSee('API readiness')
        ->assertSee('Discuss a focused deployment');
});

test('the landing page presents only accountable product evidence', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSeeInOrder([
            'Register the farm',
            'Open the crop season',
            'Capture the question',
            'Review the evidence',
            'Close the loop',
        ])
        ->assertSee('Illustrative workflow · not live farmer data')
        ->assertSee('Transcription and translation can then be enabled')
        ->assertDontSee('Trusted by thousands')
        ->assertDontSee('industry-leading accuracy');
});

test('the public navigation identifies the current page', function () {
    $this->get(route('solutions'))
        ->assertSuccessful()
        ->assertSee('aria-current="page"', escape: false)
        ->assertSee('>Platform</a>', escape: false);

    $this->get(route('partners'))
        ->assertSuccessful()
        ->assertSee('aria-current="page"', escape: false)
        ->assertSee('>Company</a>', escape: false);
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
    'solutions' => ['solutions', 'One crop workflow'],
    'impact' => ['impact', 'From farm record'],
    'partners' => ['partners', 'Bring a defined'],
    'team' => ['team', 'Built across product'],
    'contact' => ['contact', 'Bring us the crop'],
    'field voice' => ['field-voice', 'Capture the question'],
]);

test('the product scope is crop only and keeps the startup offer focused', function () {
    $this->get(route('solutions'))
        ->assertSuccessful()
        ->assertSee('The AgriShield platform')
        ->assertSee('Field Voice')
        ->assertSee('Crop cases')
        ->assertSee('Advisories')
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
    'voice intake' => 'voice-assistance.store',
    'crop cases' => 'farms.diagnosis-requests.store',
    'advisories' => 'farms.advisories.store',
    'advisory acknowledgement' => 'farms.advisories.acknowledge',
    'organization overview' => 'organizations.overview.show',
    'integration operations' => 'integrations.index',
    'health check' => 'health.show',
]);

test('the public website does not borrow unverified credibility', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertDontSee('Illustrative service network')
        ->assertDontSee('WEATHER &amp; SATELLITE', escape: false)
        ->assertDontSee('RESPONSIBLE BY DESIGN');

    $this->get(route('team'))
        ->assertSuccessful()
        ->assertDontSee('Dr. Amina Bello')
        ->assertDontSee('Engr. Musa Ibrahim');

    $this->get(route('partners'))
        ->assertSuccessful()
        ->assertDontSee('FAO')
        ->assertDontSee('World Bank')
        ->assertSee('Only confirmed relationships');
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
