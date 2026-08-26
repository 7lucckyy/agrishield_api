<?php

declare(strict_types=1);

use App\Enums\GlobalRole;
use App\Enums\OrganizationRole;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('the public product website explains the agrishield proposition', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Evidence for the field.')
        ->assertSee('Agricultural intelligence for resilient communities')
        ->assertSee('North-East Nigeria')
        ->assertSee('Request a demonstration');
});

test('the public company pages present the current agrishield offering', function (string $route, string $content) {
    $this->get(route($route))
        ->assertSuccessful()
        ->assertSee($content)
        ->assertSee('hello@agrishield.ai');
})->with([
    'about' => ['about', 'Technology grounded'],
    'solutions' => ['solutions', 'Farmer Census & Digital Registry'],
    'impact' => ['impact', 'Impact is the story'],
    'partners' => ['partners', 'Complex challenges'],
    'team' => ['team', 'Multidisciplinary'],
    'contact' => ['contact', 'Bring us the hard'],
]);

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
