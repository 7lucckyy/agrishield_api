<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Enums\ProviderStatus;
use App\Models\Crop;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\User;

test('a farmer creates a farm with server computed ownership and geometry', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/farms', [
        'name' => ' North Plot ',
        'boundary_geojson' => validFarmBoundary(),
        'locality' => ' Kano ',
        'country' => 'ng',
        'owner_user_id' => $otherUser->getKey(),
        'area_hectares' => 99999,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.name', 'North Plot')
        ->assertJsonPath('data.owner.id', $user->getKey())
        ->assertJsonPath('data.country', 'NG')
        ->assertJsonPath('data.provider_status', ProviderStatus::Pending->value)
        ->assertJsonPath('meta.message', 'Farm created. Satellite registration is in progress.');

    expect((float) $response->json('data.area.hectares'))->toBeBetween(99.5, 100.5);

    $farm = Farm::query()->sole();
    expect($farm->owner_user_id)->toBe($user->getKey())
        ->and((float) $farm->area_hectares)->not->toBe(99999.0)
        ->and($farm->boundary_hash)->toHaveLength(64);
});

test('a farm is assigned only to an organization where its creator has an allowed role', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    attachOrganizationRole($user, $organization, OrganizationRole::Farmer);

    $this->actingAs($user)->postJson('/api/v1/farms', [
        'name' => 'Member Plot',
        'boundary_geojson' => validFarmBoundary(),
        'organization_id' => $organization->getKey(),
    ])->assertCreated()->assertJsonPath('data.organization.id', $organization->getKey());

    expect(Farm::query()->sole()->organization_id)->toBe($organization->getKey());
});

test('an explicitly asserted organization outside the creator membership is forbidden', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    $this->actingAs($user)->postJson('/api/v1/farms', [
        'name' => 'Unauthorized Plot',
        'boundary_geojson' => validFarmBoundary(),
        'organization_id' => $organization->getKey(),
    ])->assertForbidden()->assertJsonPath('error_code', 'forbidden');

    expect(Farm::query()->count())->toBe(0);
});

test('updating a boundary recomputes geometry and resets provider registration', function () {
    $user = User::factory()->create();
    $farm = Farm::factory()->for($user, 'owner')->registered()->create();
    $originalHash = $farm->boundary_hash;

    $this->actingAs($user)->patchJson("/api/v1/farms/{$farm->uuid}", [
        'name' => 'Updated Plot',
        'boundary_geojson' => validFarmBoundary(0.02),
        'area_hectares' => 777,
    ])->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated Plot')
        ->assertJsonPath('data.provider_status', ProviderStatus::Pending->value);

    expect($farm->refresh()->boundary_hash)->not->toBe($originalHash)
        ->and($farm->provider_status)->toBe(ProviderStatus::Pending)
        ->and((float) $farm->area_hectares)->not->toBe(777.0);
});

test('farm deletion is soft and returns no content', function () {
    $user = User::factory()->create();
    $farm = Farm::factory()->for($user, 'owner')->create();

    $this->actingAs($user)
        ->deleteJson("/api/v1/farms/{$farm->uuid}")
        ->assertNoContent();

    expect(Farm::query()->count())->toBe(0)
        ->and(Farm::withTrashed()->find($farm->getKey())?->trashed())->toBeTrue();
});

test('farm lists are scoped filtered paginated and include aggregate totals', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();
    Farm::factory()->for($user, 'owner')->create(['name' => 'Alpha Plot', 'area_hectares' => 10, 'area_acres' => 24.7105]);
    Farm::factory()->for($user, 'owner')->create(['name' => 'Beta Plot', 'area_hectares' => 20, 'area_acres' => 49.4211]);
    Farm::factory()->for($stranger, 'owner')->create(['name' => 'Hidden Plot']);

    $this->actingAs($user)
        ->getJson('/api/v1/farms?filter[search]=plot&sort=name&per_page=1')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Alpha Plot')
        ->assertJsonPath('meta.pagination.total', 2)
        ->assertJsonPath('meta.totals.farms', 2)
        ->assertJsonPath('meta.totals.hectares', 30);
});

test('farm lists can be filtered by active or historical crop', function () {
    $user = User::factory()->create();
    $maize = Crop::factory()->create();
    $rice = Crop::factory()->create();
    $maizeFarm = Farm::factory()->for($user, 'owner')->create();
    $riceFarm = Farm::factory()->for($user, 'owner')->create();
    CropCycle::factory()->for($maizeFarm)->for($maize)->create();
    CropCycle::factory()->for($riceFarm)->for($rice)->create();

    $this->actingAs($user)
        ->getJson("/api/v1/farms?filter[crop_id]={$maize->getKey()}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $maizeFarm->uuid);
});

test('malformed boundaries return field level validation errors', function (array $boundary) {
    $this->actingAs(User::factory()->create())->postJson('/api/v1/farms', [
        'name' => 'Bad Plot',
        'boundary_geojson' => $boundary,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('boundary_geojson');
})->with([
    'wrong type' => [['type' => 'Point', 'coordinates' => [8.5, 12.0]]],
    'unclosed ring' => [['type' => 'Polygon', 'coordinates' => [[[8.5, 12.0], [8.6, 12.0], [8.6, 11.9], [8.5, 11.9]]]]],
    'too few points' => [['type' => 'Polygon', 'coordinates' => [[[8.5, 12.0], [8.6, 12.0], [8.5, 12.0]]]]],
    'latitude out of range' => [['type' => 'Polygon', 'coordinates' => [[[8.5, 91], [8.6, 91], [8.6, 90], [8.5, 91]]]]],
    'self intersecting' => [['type' => 'Polygon', 'coordinates' => [[[0, 0], [1, 1], [0, 1], [1, 0], [0, 0]]]]],
    'zero area' => [['type' => 'Polygon', 'coordinates' => [[[0, 0], [1, 0], [2, 0], [0, 0]]]]],
]);
