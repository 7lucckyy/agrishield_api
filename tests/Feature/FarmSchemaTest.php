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
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('The maximum-area check is a PostgreSQL constraint.');
    }

    expect(fn () => Farm::factory()->create(['area_hectares' => 10000.0001]))
        ->toThrow(QueryException::class);
});

test('the spatial column is guarded by PostGIS availability', function () {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('PostGIS extension discovery requires PostgreSQL.');
    }

    $postgisInstalled = (bool) DB::scalar("SELECT EXISTS (SELECT 1 FROM pg_extension WHERE extname = 'postgis')");

    if (config('database.postgis_required')) {
        expect($postgisInstalled)->toBeTrue('PostGIS is required for this test environment.');
    }

    expect(Schema::hasColumn('farms', 'boundary'))->toBe($postgisInstalled);
    expect(Schema::hasColumn('farm_sections', 'boundary'))->toBe($postgisInstalled);
});

test('PostGIS provides indexed geography columns and exact boundary containment', function () {
    if (DB::getDriverName() !== 'pgsql' || ! (bool) DB::scalar("SELECT EXISTS (SELECT 1 FROM pg_extension WHERE extname = 'postgis')")) {
        $this->markTestSkipped('This assertion requires a PostgreSQL database with PostGIS enabled.');
    }

    $indexes = DB::table('pg_indexes')
        ->where('schemaname', 'public')
        ->whereIn('indexname', ['idx_farms_boundary_gist', 'idx_farm_sections_boundary_gist'])
        ->pluck('indexdef', 'indexname');

    expect($indexes)->toHaveCount(2);
    foreach ($indexes as $definition) {
        expect($definition)->toContain('USING gist');
    }

    $farm = json_encode(['type' => 'Polygon', 'coordinates' => [[[8, 12], [8.01, 12], [8.01, 12.01], [8, 12.01], [8, 12]]]], JSON_THROW_ON_ERROR);
    $inside = json_encode(['type' => 'Polygon', 'coordinates' => [[[8.001, 12.001], [8.002, 12.001], [8.002, 12.002], [8.001, 12.002], [8.001, 12.001]]]], JSON_THROW_ON_ERROR);
    $outside = json_encode(['type' => 'Polygon', 'coordinates' => [[[8.02, 12.02], [8.021, 12.02], [8.021, 12.021], [8.02, 12.021], [8.02, 12.02]]]], JSON_THROW_ON_ERROR);

    expect((bool) DB::scalar('SELECT ST_Covers(ST_GeomFromGeoJSON(?), ST_GeomFromGeoJSON(?))', [$farm, $inside]))->toBeTrue()
        ->and((bool) DB::scalar('SELECT ST_Covers(ST_GeomFromGeoJSON(?), ST_GeomFromGeoJSON(?))', [$farm, $outside]))->toBeFalse();
});
