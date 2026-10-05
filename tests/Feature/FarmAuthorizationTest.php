<?php

declare(strict_types=1);

use App\Enums\OrganizationMembershipStatus;
use App\Enums\OrganizationRole;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;

test('an unrelated farmer cannot discover read update or delete another farm', function (string $method) {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create();

    $this->actingAs($stranger)
        ->json($method, "/api/v1/farms/{$farm->uuid}", ['name' => 'Nope'])
        ->assertNotFound()
        ->assertJsonPath('error_code', 'not_found');
})->with(['GET', 'PATCH', 'DELETE']);

test('organization admins can view and update farms in their organization but cannot delete them', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $organization = Organization::factory()->create();
    attachOrganizationRole($admin, $organization, OrganizationRole::OrganizationAdmin);
    $farm = Farm::factory()->for($owner, 'owner')->for($organization)->create();

    $this->actingAs($admin)->getJson("/api/v1/farms/{$farm->uuid}")->assertSuccessful();
    $this->actingAs($admin)->patchJson("/api/v1/farms/{$farm->uuid}", ['name' => 'Admin Updated'])->assertSuccessful();
    $this->actingAs($admin)->deleteJson("/api/v1/farms/{$farm->uuid}")->assertForbidden();
});

test('organization agronomists can view but not update farms in their organization', function () {
    $owner = User::factory()->create();
    $agronomist = User::factory()->create();
    $organization = Organization::factory()->create();
    attachOrganizationRole($agronomist, $organization, OrganizationRole::Agronomist);
    $farm = Farm::factory()->for($owner, 'owner')->for($organization)->create();

    $this->actingAs($agronomist)->getJson("/api/v1/farms/{$farm->uuid}")->assertSuccessful();
    $this->actingAs($agronomist)->patchJson("/api/v1/farms/{$farm->uuid}", ['name' => 'Nope'])->assertForbidden();
});

test('a cluster lead sees farms in their assigned cluster only', function () {
    $organization = Organization::factory()->create();
    $lead = User::factory()->create();
    $clusterFarmer = User::factory()->create();
    $otherFarmer = User::factory()->create();
    attachOrganizationRole($lead, $organization, OrganizationRole::ClusterLead);
    attachOrganizationRole($clusterFarmer, $organization, OrganizationRole::Farmer);
    attachOrganizationRole($otherFarmer, $organization, OrganizationRole::Farmer);
    $organization->users()->updateExistingPivot($lead, ['cluster_name' => 'Kura Cluster']);
    $organization->users()->updateExistingPivot($clusterFarmer, ['cluster_name' => 'Kura Cluster']);
    $organization->users()->updateExistingPivot($otherFarmer, ['cluster_name' => 'Bichi Cluster']);
    $visibleFarm = Farm::factory()->for($organization)->for($clusterFarmer, 'owner')->create();
    Farm::factory()->for($organization)->for($otherFarmer, 'owner')->create();

    $this->actingAs($lead)
        ->getJson("/api/v1/organizations/{$organization->getKey()}/farms")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $visibleFarm->uuid);

    $organization->users()->updateExistingPivot($clusterFarmer, [
        'status' => OrganizationMembershipStatus::Removed->value,
    ]);

    $this->actingAs($lead)
        ->getJson("/api/v1/organizations/{$organization->getKey()}/farms")
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');
});

test('the visible farm scope and view policy agree over mixed ownership', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $organization = Organization::factory()->create();
    attachOrganizationRole($user, $organization, OrganizationRole::Agronomist);

    Farm::factory()->for($user, 'owner')->create();
    Farm::factory()->for($other, 'owner')->for($organization)->create();
    Farm::factory()->for($other, 'owner')->create();

    $visibleIds = Farm::query()->visibleTo($user)->pluck('id')->sort()->values()->all();
    $policyIds = Farm::query()->get()
        ->filter(fn (Farm $farm): bool => Gate::forUser($user)->allows('view', $farm))
        ->pluck('id')->sort()->values()->all();

    expect($visibleIds)->toBe($policyIds)->toHaveCount(2);
});

test('a read only Sanctum token cannot mutate farms', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['farms:read']);

    $this->postJson('/api/v1/farms', [
        'name' => 'Read Only Plot',
        'boundary_geojson' => validFarmBoundary(),
    ])->assertForbidden();
});

test('farms bind by uuid rather than their internal id', function () {
    $user = User::factory()->create();
    $farm = Farm::factory()->for($user, 'owner')->create();

    $this->actingAs($user)->getJson("/api/v1/farms/{$farm->getKey()}")->assertNotFound();
});
