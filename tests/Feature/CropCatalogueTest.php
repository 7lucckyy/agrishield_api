<?php

declare(strict_types=1);

use App\Enums\CropCategory;
use App\Enums\GlobalRole;
use App\Models\Crop;
use App\Models\User;
use Database\Seeders\CropSeeder;
use Database\Seeders\GlobalRoleSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

test('the crop seeder creates 41 catalogue entries idempotently', function () {
    $this->seed(CropSeeder::class);
    Crop::query()->where('code', 'MAIZE')->update(['active' => false]);
    $this->seed(CropSeeder::class);

    expect(Crop::query()->count())->toBe(41)
        ->and(Crop::query()->where('code', 'MAIZE')->value('scientific_name'))->toBe('Zea mays')
        ->and(Crop::query()->where('code', 'MAIZE')->value('active'))->toBeFalse();
});

test('crop endpoints require authentication', function () {
    $crop = Crop::factory()->create();

    $this->getJson('/api/v1/crops')->assertUnauthorized();
    $this->getJson("/api/v1/crops/{$crop->getKey()}")->assertUnauthorized();
});

test('authenticated users can search and paginate active crops', function () {
    $user = User::factory()->create();
    Crop::factory()->create([
        'name' => 'Maize',
        'scientific_name' => 'Zea mays',
        'code' => 'MAIZE',
        'category' => CropCategory::Cereal,
    ]);
    Crop::factory()->create([
        'name' => 'Cassava',
        'scientific_name' => 'Manihot esculenta',
        'code' => 'CASSAVA',
        'category' => CropCategory::Other,
    ]);
    Crop::factory()->create([
        'name' => 'Inactive Maize',
        'code' => 'INACTIVE_MAIZE',
        'category' => CropCategory::Cereal,
        'active' => false,
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/v1/crops?filter[search]=zea&filter[category]=cereal&per_page=1&sort=-name')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', 'MAIZE')
        ->assertJsonPath('meta.pagination.current_page', 1)
        ->assertJsonPath('meta.pagination.per_page', 1)
        ->assertJsonPath('meta.pagination.total', 1)
        ->assertJsonPath('meta.pagination.last_page', 1);

    expect($response->headers->get('Cache-Control'))
        ->toContain('public')
        ->toContain('max-age=3600');
});

test('inactive crops can be requested explicitly', function () {
    $user = User::factory()->create();
    Crop::factory()->create(['name' => 'Active Crop', 'code' => 'ACTIVE_CROP']);
    $inactiveCrop = Crop::factory()->create([
        'name' => 'Inactive Crop',
        'code' => 'INACTIVE_CROP',
        'active' => false,
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/crops?filter[active]=false')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $inactiveCrop->getKey());
});

test('the seeded active crop catalogue is returned and served from cache', function () {
    Cache::flush();

    $user = User::factory()->create();
    $this->seed(CropSeeder::class);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($user)
        ->getJson('/api/v1/crops?per_page=100')
        ->assertSuccessful()
        ->assertJsonCount(41, 'data')
        ->assertJsonPath('data.0.active', true);
    $this->actingAs($user)->getJson('/api/v1/crops')->assertSuccessful();

    $cropSelects = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'from "crops"'));

    expect($cropSelects)->toHaveCount(1);
});

test('authenticated users can view a crop', function () {
    $user = User::factory()->create();
    $crop = Crop::factory()->create(['code' => 'MAIZE']);

    $this->actingAs($user)
        ->getJson("/api/v1/crops/{$crop->getKey()}")
        ->assertSuccessful()
        ->assertJsonPath('data.code', 'MAIZE');
});

test('non platform admins cannot mutate crops', function (string $method, string $path) {
    $user = User::factory()->create();
    $crop = Crop::factory()->create();
    $resolvedPath = str_replace('{crop}', (string) $crop->getKey(), $path);

    $response = $this->actingAs($user)
        ->json($method, $resolvedPath, ['name' => 'Forbidden Crop', 'code' => 'FORBIDDEN']);

    $response
        ->assertForbidden()
        ->assertJsonPath('error_code', 'forbidden')
        ->assertJsonStructure(['message', 'error_code', 'request_id']);
})->with([
    'create' => ['POST', '/api/v1/crops'],
    'update' => ['PATCH', '/api/v1/crops/{crop}'],
    'delete' => ['DELETE', '/api/v1/crops/{crop}'],
]);

test('platform admins can create crops and the catalogue cache is invalidated', function () {
    $this->seed(GlobalRoleSeeder::class);

    $platformAdmin = User::factory()->create();
    $platformAdmin->assignRole(GlobalRole::PlatformAdmin->value);

    $this->actingAs($platformAdmin)->getJson('/api/v1/crops')->assertSuccessful();

    $this->actingAs($platformAdmin)
        ->postJson('/api/v1/crops', [
            'name' => '  Maize Sweet  ',
            'scientific_name' => ' Zea mays ',
            'code' => 'maize_sweet',
            'category' => 'CEREAL',
            'default_cycle_days' => 95,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Maize Sweet')
        ->assertJsonPath('data.code', 'MAIZE_SWEET')
        ->assertJsonPath('data.category', CropCategory::Cereal->value);

    $this->actingAs($platformAdmin)
        ->getJson('/api/v1/crops?filter[search]=MAIZE_SWEET')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

test('platform admins can update and deactivate crops', function () {
    $this->seed(GlobalRoleSeeder::class);

    $platformAdmin = User::factory()->create();
    $platformAdmin->assignRole(GlobalRole::PlatformAdmin->value);
    $crop = Crop::factory()->create(['name' => 'Old Name', 'code' => 'OLD_NAME']);

    $this->actingAs($platformAdmin)
        ->patchJson("/api/v1/crops/{$crop->getKey()}", [
            'name' => 'Updated Crop',
            'category' => CropCategory::Vegetable->value,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated Crop')
        ->assertJsonPath('data.category', CropCategory::Vegetable->value);

    $this->actingAs($platformAdmin)
        ->deleteJson("/api/v1/crops/{$crop->getKey()}")
        ->assertNoContent();

    expect($crop->refresh()->active)->toBeFalse();

    $this->actingAs($platformAdmin)
        ->getJson('/api/v1/crops?filter[active]=false&filter[search]=Updated')
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', $crop->getKey());
});

test('crop writes validate unique names codes and cycle length', function () {
    $this->seed(GlobalRoleSeeder::class);

    $platformAdmin = User::factory()->create();
    $platformAdmin->assignRole(GlobalRole::PlatformAdmin->value);
    Crop::factory()->create(['name' => 'Maize', 'code' => 'MAIZE']);

    $this->actingAs($platformAdmin)
        ->postJson('/api/v1/crops', [
            'name' => 'Maize',
            'code' => 'MAIZE',
            'category' => 'not-a-category',
            'default_cycle_days' => 731,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'code', 'category', 'default_cycle_days']);
});
