<?php

declare(strict_types=1);

use App\Enums\FarmStatus;
use App\Enums\ProviderStatus;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

test('farms persist the documented data shape and bind publicly by uuid', function () {
    $owner = User::factory()->create();
    $organization = Organization::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->for($organization)->create();

    expect(Str::isUuid($farm->uuid))->toBeTrue()
        ->and($farm->getRouteKeyName())->toBe('uuid')
        ->and($farm->boundary_geojson)->toBeArray()
        ->and($farm->status)->toBe(FarmStatus::Active)
        ->and($farm->provider_status)->toBe(ProviderStatus::Pending)
        ->and($farm->owner->is($owner))->toBeTrue()
        ->and($farm->organization?->is($organization))->toBeTrue();

    expect(Schema::hasColumns('farms', [
        'uuid',
        'organization_id',
        'owner_user_id',
        'boundary_geojson',
        'boundary_hash',
        'centroid_latitude',
        'centroid_longitude',
        'area_hectares',
        'area_acres',
        'status',
        'provider_status',
        'deleted_at',
    ]))->toBeTrue();
});

test('the database enforces the maximum farm area', function () {
    expect(fn () => Farm::factory()->create(['area_hectares' => 10000.0001]))
        ->toThrow(QueryException::class);
});

test('the spatial column is guarded by PostGIS availability', function () {
    $postgisInstalled = (bool) DB::scalar("SELECT EXISTS (SELECT 1 FROM pg_extension WHERE extname = 'postgis')");

    expect(Schema::hasColumn('farms', 'boundary'))->toBe($postgisInstalled);
});
