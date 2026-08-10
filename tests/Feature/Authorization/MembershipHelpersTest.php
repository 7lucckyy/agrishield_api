<?php

use App\Enums\OrganizationMembershipStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

test('membership helpers only consider active organization memberships', function () {
    $user = User::factory()->create();
    $adminOrganization = Organization::factory()->create();
    $farmerOrganization = Organization::factory()->create();
    $removedOrganization = Organization::factory()->create();

    $user->organizations()->attach([
        $adminOrganization->getKey() => [
            'role' => OrganizationRole::OrganizationAdmin->value,
            'status' => OrganizationMembershipStatus::Active->value,
            'joined_at' => now()->subDays(2),
        ],
        $farmerOrganization->getKey() => [
            'role' => OrganizationRole::Farmer->value,
            'status' => OrganizationMembershipStatus::Active->value,
            'joined_at' => now()->subDay(),
        ],
        $removedOrganization->getKey() => [
            'role' => OrganizationRole::OrganizationAdmin->value,
            'status' => OrganizationMembershipStatus::Removed->value,
            'joined_at' => now()->subDays(3),
        ],
    ]);

    expect($user->belongsToOrganization($adminOrganization))->toBeTrue()
        ->and($user->belongsToOrganization($removedOrganization))->toBeFalse()
        ->and($user->hasOrganizationRole($adminOrganization, OrganizationRole::OrganizationAdmin))->toBeTrue()
        ->and($user->hasOrganizationRole($adminOrganization, OrganizationRole::Farmer))->toBeFalse()
        ->and($user->organizationIdsWhereRoleIn([OrganizationRole::OrganizationAdmin]))
        ->toBe([$adminOrganization->getKey()])
        ->and($user->organizationIdsWhereRoleIn([]))->toBe([])
        ->and($user->primaryOrganizationId())->toBe($adminOrganization->getKey());
});

test('organization role ids are memoized on the user for the request', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    $user->organizations()->attach($organization, [
        'role' => OrganizationRole::Agronomist->value,
        'status' => OrganizationMembershipStatus::Active->value,
        'joined_at' => now(),
    ]);

    $membershipQueryCount = 0;

    DB::listen(function (QueryExecuted $query) use (&$membershipQueryCount): void {
        if (str_contains($query->sql, 'organization_user')) {
            $membershipQueryCount++;
        }
    });

    $user->organizationIdsWhereRoleIn([OrganizationRole::Agronomist]);
    $user->organizationIdsWhereRoleIn([OrganizationRole::Agronomist]);

    expect($membershipQueryCount)->toBe(1);
});
