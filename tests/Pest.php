<?php

use App\Enums\OrganizationMembershipStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature');

function validFarmBoundary(float $offset = 0.0): array
{
    $side = 0.0089932;

    return [
        'type' => 'Polygon',
        'coordinates' => [[
            [$offset, $offset],
            [$offset + $side, $offset],
            [$offset + $side, $offset + $side],
            [$offset, $offset + $side],
            [$offset, $offset],
        ]],
    ];
}

function attachOrganizationRole(User $user, Organization $organization, OrganizationRole $role): void
{
    $user->organizations()->attach($organization, [
        'role' => $role->value,
        'status' => OrganizationMembershipStatus::Active->value,
        'joined_at' => now(),
    ]);
}
