<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Crop;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\User;

test('a crop cycle cannot be accessed through a different farm', function () {
    $owner = User::factory()->create();
    $firstFarm = Farm::factory()->for($owner, 'owner')->create();
    $secondFarm = Farm::factory()->for($owner, 'owner')->create();
    $cycle = CropCycle::factory()->for($secondFarm)->create();

    $this->actingAs($owner)
        ->getJson("/api/v1/farms/{$firstFarm->uuid}/crop-cycles/{$cycle->getKey()}")
        ->assertNotFound();
});

test('an unrelated farmer cannot list or create crop cycles on another farm', function (string $method) {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create();
    $crop = Crop::factory()->create();

    $this->actingAs($stranger)->json($method, "/api/v1/farms/{$farm->uuid}/crop-cycles", [
        'crop_id' => $crop->getKey(),
        'planting_date' => now()->toDateString(),
    ])->assertNotFound();
})->with(['GET', 'POST']);

test('organization agronomists may manage crop cycles but not delete them', function () {
    $owner = User::factory()->create();
    $agronomist = User::factory()->create();
    $organization = Organization::factory()->create();
    attachOrganizationRole($agronomist, $organization, OrganizationRole::Agronomist);
    $farm = Farm::factory()->for($owner, 'owner')->for($organization)->create();
    $crop = Crop::factory()->create();

    $response = $this->actingAs($agronomist)->postJson("/api/v1/farms/{$farm->uuid}/crop-cycles", [
        'crop_id' => $crop->getKey(),
        'planting_date' => now()->toDateString(),
        'status' => 'planned',
    ])->assertCreated();

    $this->actingAs($agronomist)
        ->patchJson("/api/v1/farms/{$farm->uuid}/crop-cycles/{$response->json('data.id')}", ['notes' => 'Reviewed'])
        ->assertSuccessful();

    $this->actingAs($agronomist)
        ->deleteJson("/api/v1/farms/{$farm->uuid}/crop-cycles/{$response->json('data.id')}")
        ->assertForbidden();
});
