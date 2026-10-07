<?php

use App\Enums\GlobalRole;
use App\Enums\OrganizationMembershipStatus;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\User;
use App\Policies\OrganizationPolicy;
use Database\Seeders\GlobalRoleSeeder;
use Illuminate\Support\Facades\Gate;

test('the organization policy is discovered automatically', function () {
    expect(Gate::getPolicyFor(Organization::class))->toBeInstanceOf(OrganizationPolicy::class);
});

test('platform admins can perform every organization policy action', function () {
    $this->seed(GlobalRoleSeeder::class);

    $platformAdmin = User::factory()->create();
    $platformAdmin->assignRole(GlobalRole::PlatformAdmin->value);
    $organization = Organization::factory()->create(['status' => OrganizationStatus::Suspended]);

    expect($platformAdmin->can('viewAny', Organization::class))->toBeTrue()
        ->and($platformAdmin->can('create', Organization::class))->toBeTrue()
        ->and($platformAdmin->can('view', $organization))->toBeTrue()
        ->and($platformAdmin->can('changeStatus', $organization))->toBeTrue()
        ->and($platformAdmin->can('updateMemberRole', $organization))->toBeTrue();
});

test('organization access is concealed from non-members', function () {
    $outsider = User::factory()->create();
    $organization = Organization::factory()->create();

    $response = Gate::forUser($outsider)->inspect('view', $organization);

    expect($response->denied())->toBeTrue()
        ->and($response->status())->toBe(404);
});

test('farmers receive a forbidden response for organization management', function () {
    $farmer = User::factory()->create();
    $organization = Organization::factory()->create();

    $farmer->organizations()->attach($organization, [
        'role' => OrganizationRole::Farmer->value,
        'status' => OrganizationMembershipStatus::Active->value,
        'joined_at' => now(),
    ]);

    $response = Gate::forUser($farmer)->inspect('view', $organization);

    expect($response->denied())->toBeTrue()
        ->and($response->status())->toBeNull();
});

test('organization admins and agronomists receive their respective documented abilities', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create();
    $agronomist = User::factory()->create();

    $admin->organizations()->attach($organization, [
        'role' => OrganizationRole::OrganizationAdmin->value,
        'status' => OrganizationMembershipStatus::Active->value,
        'joined_at' => now(),
    ]);
    $agronomist->organizations()->attach($organization, [
        'role' => OrganizationRole::Agronomist->value,
        'status' => OrganizationMembershipStatus::Active->value,
        'joined_at' => now(),
    ]);

    expect($admin->can('view', $organization))->toBeTrue()
        ->and($admin->can('update', $organization))->toBeTrue()
        ->and($admin->can('viewMembers', $organization))->toBeTrue()
        ->and($admin->can('updateMemberRole', $organization))->toBeTrue()
        ->and($agronomist->can('view', $organization))->toBeTrue()
        ->and($agronomist->cannot('viewMembers', $organization))->toBeTrue()
        ->and($agronomist->can('viewFarms', $organization))->toBeTrue()
        ->and($agronomist->cannot('update', $organization))->toBeTrue()
        ->and($agronomist->cannot('updateMemberRole', $organization))->toBeTrue();
});

test('inactive organizations are read only and suspended organizations block members', function () {
    $admin = User::factory()->create();
    $inactiveOrganization = Organization::factory()->create(['status' => OrganizationStatus::Inactive]);
    $suspendedOrganization = Organization::factory()->create(['status' => OrganizationStatus::Suspended]);

    foreach ([$inactiveOrganization, $suspendedOrganization] as $organization) {
        $admin->organizations()->attach($organization, [
            'role' => OrganizationRole::OrganizationAdmin->value,
            'status' => OrganizationMembershipStatus::Active->value,
            'joined_at' => now(),
        ]);
    }

    expect($admin->can('view', $inactiveOrganization))->toBeTrue()
        ->and($admin->cannot('update', $inactiveOrganization))->toBeTrue()
        ->and($admin->cannot('view', $suspendedOrganization))->toBeTrue();
});

test('removed memberships no longer grant organization access', function () {
    $admin = User::factory()->create();
    $organization = Organization::factory()->create();

    $admin->organizations()->attach($organization, [
        'role' => OrganizationRole::OrganizationAdmin->value,
        'status' => OrganizationMembershipStatus::Removed->value,
        'joined_at' => now()->subDay(),
    ]);

    $response = Gate::forUser($admin)->inspect('view', $organization);

    expect($response->denied())->toBeTrue()
        ->and($response->status())->toBe(404);
});
