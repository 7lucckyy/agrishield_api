<?php

declare(strict_types=1);

use App\Enums\OrganizationMembershipStatus;
use App\Enums\OrganizationRole;
use App\Models\Crop;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\User;

test('an organization administrator lists only farms in their organization', function () {
    $administrator = User::factory()->create();
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    attachOrganizationRole($administrator, $organization, OrganizationRole::OrganizationAdmin);

    $farm = Farm::factory()->for($organization)->create();
    Farm::factory()->for($otherOrganization)->create();
    $cycle = CropCycle::factory()->for($farm)->for(Crop::factory())->active()->create();

    $this->actingAs($administrator)
        ->getJson("/api/v1/organizations/{$organization->getKey()}/farms")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $farm->uuid)
        ->assertJsonPath('data.0.active_crop_cycle.id', $cycle->getKey())
        ->assertJsonPath('meta.totals.farms', 1);
});

test('organization farm access is hidden from outsiders', function () {
    $organization = Organization::factory()->create();
    $outside = User::factory()->create();
    Farm::factory()->for($organization)->create();

    $this->actingAs($outside)
        ->getJson("/api/v1/organizations/{$organization->getKey()}/farms")
        ->assertNotFound();
});

test('organization agronomists can list farms but cannot list members', function () {
    $organization = Organization::factory()->create();
    $agronomist = User::factory()->create();
    attachOrganizationRole($agronomist, $organization, OrganizationRole::Agronomist);
    Farm::factory()->for($organization)->create();

    $this->actingAs($agronomist)
        ->getJson("/api/v1/organizations/{$organization->getKey()}/farms")
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->actingAs($agronomist)
        ->getJson("/api/v1/organizations/{$organization->getKey()}/members")
        ->assertForbidden();
});

test('an organization administrator can list members change a role and remove a member', function () {
    $administrator = User::factory()->create();
    $member = User::factory()->create();
    $organization = Organization::factory()->create();
    attachOrganizationRole($administrator, $organization, OrganizationRole::OrganizationAdmin);
    attachOrganizationRole($member, $organization, OrganizationRole::Farmer);

    $this->actingAs($administrator)
        ->getJson("/api/v1/organizations/{$organization->getKey()}/members")
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->actingAs($administrator)
        ->patchJson("/api/v1/organizations/{$organization->getKey()}/members/{$member->getKey()}", [
            'role' => OrganizationRole::Agronomist->value,
        ])
        ->assertOk()
        ->assertJsonPath('data.role', OrganizationRole::Agronomist->value);

    $this->actingAs($administrator)
        ->deleteJson("/api/v1/organizations/{$organization->getKey()}/members/{$member->getKey()}")
        ->assertNoContent();

    expect($organization->users()->whereKey($member->getKey())->firstOrFail()->membership->status)
        ->toBe(OrganizationMembershipStatus::Removed);
});

test('an organization administrator can group farmers and assign a cluster lead', function () {
    $administrator = User::factory()->create();
    $lead = User::factory()->create();
    $farmer = User::factory()->create();
    $organization = Organization::factory()->create();
    attachOrganizationRole($administrator, $organization, OrganizationRole::OrganizationAdmin);
    attachOrganizationRole($lead, $organization, OrganizationRole::Farmer);
    attachOrganizationRole($farmer, $organization, OrganizationRole::Farmer);

    $this->actingAs($administrator)
        ->patchJson("/api/v1/organizations/{$organization->getKey()}/members/{$lead->getKey()}", [
            'role' => OrganizationRole::ClusterLead->value,
            'cluster_name' => 'Dawakin Kudu Maize Cluster',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.role', OrganizationRole::ClusterLead->value)
        ->assertJsonPath('data.cluster_name', 'Dawakin Kudu Maize Cluster');

    $this->actingAs($administrator)
        ->patchJson("/api/v1/organizations/{$organization->getKey()}/members/{$farmer->getKey()}", [
            'role' => OrganizationRole::Farmer->value,
            'cluster_name' => 'Dawakin Kudu Maize Cluster',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.cluster_name', 'Dawakin Kudu Maize Cluster');
});

test('the final active organization administrator cannot be demoted or removed', function () {
    $administrator = User::factory()->create();
    $organization = Organization::factory()->create();
    attachOrganizationRole($administrator, $organization, OrganizationRole::OrganizationAdmin);

    $endpoint = "/api/v1/organizations/{$organization->getKey()}/members/{$administrator->getKey()}";

    $this->actingAs($administrator)->patchJson($endpoint, [
        'role' => OrganizationRole::Farmer->value,
    ])->assertConflict()->assertJsonPath('error_code', 'last_admin_required');

    $this->actingAs($administrator)
        ->deleteJson($endpoint)
        ->assertConflict()
        ->assertJsonPath('error_code', 'last_admin_required');
});

test('a member from another organization cannot be managed through a nested route', function () {
    $administrator = User::factory()->create();
    $organization = Organization::factory()->create();
    $outsider = User::factory()->create();
    attachOrganizationRole($administrator, $organization, OrganizationRole::OrganizationAdmin);

    $this->actingAs($administrator)
        ->patchJson("/api/v1/organizations/{$organization->getKey()}/members/{$outsider->getKey()}", [
            'role' => OrganizationRole::Farmer->value,
        ])
        ->assertNotFound();
});
