<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CropCycleStatus;
use App\Enums\FarmStatus;
use App\Enums\GlobalRole;
use App\Enums\OrganizationMembershipStatus;
use App\Enums\OrganizationRole;
use App\Enums\ProviderStatus;
use Database\Factories\FarmFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $organization_id
 * @property int $owner_user_id
 * @property string $name
 * @property array{type: 'Polygon'|'MultiPolygon', coordinates: array<mixed>} $boundary_geojson
 * @property string $boundary_hash
 * @property string|float|null $centroid_latitude
 * @property string|float|null $centroid_longitude
 * @property string|float|null $area_hectares
 * @property string|float|null $area_acres
 * @property string|null $locality
 * @property string|null $state
 * @property string|null $country
 * @property FarmStatus $status
 * @property ProviderStatus $provider_status
 * @property Carbon|null $last_synced_at
 * @property Carbon|null $created_at
 * @property-read User $owner
 * @property-read Organization|null $organization
 * @property-read Collection<int, CropCycle> $cropCycles
 * @property-read Collection<int, FarmSection> $sections
 * @property-read Collection<int, FarmSection> $farmSections
 * @property-read int|null $sections_count
 * @property-read CropCycle|null $activeCropCycle
 * @property-read Collection<int, FarmProviderLink> $providerLinks
 * @property-read Collection<int, SyncRun> $syncRuns
 * @property-read Collection<int, SatelliteObservation> $satelliteObservations
 * @property-read Collection<int, WeatherForecast> $weatherForecasts
 * @property-read Collection<int, Advisory> $advisories
 * @property-read Collection<int, DiagnosisRequest> $diagnosisRequests
 * @property-read Collection<int, VoiceAssistanceRequest> $voiceAssistanceRequests
 */
#[Fillable(['name', 'boundary_geojson', 'locality', 'state', 'country', 'status'])]
final class Farm extends Model
{
    /** @use HasFactory<FarmFactory> */
    use HasFactory, SoftDeletes;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'active',
        'provider_status' => 'pending',
    ];

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return HasMany<CropCycle, $this> */
    public function cropCycles(): HasMany
    {
        return $this->hasMany(CropCycle::class);
    }

    /** @return HasMany<FarmSection, $this> */
    public function sections(): HasMany
    {
        return $this->farmSections();
    }

    /** @return HasMany<FarmSection, $this> */
    public function farmSections(): HasMany
    {
        return $this->hasMany(FarmSection::class);
    }

    /** @return HasOne<CropCycle, $this> */
    public function activeCropCycle(): HasOne
    {
        return $this->hasOne(CropCycle::class)->where('status', CropCycleStatus::Active);
    }

    /** @return HasMany<FarmProviderLink, $this> */
    public function providerLinks(): HasMany
    {
        return $this->hasMany(FarmProviderLink::class);
    }

    /** @return HasMany<SyncRun, $this> */
    public function syncRuns(): HasMany
    {
        return $this->hasMany(SyncRun::class);
    }

    /** @return HasMany<SatelliteObservation, $this> */
    public function satelliteObservations(): HasMany
    {
        return $this->hasMany(SatelliteObservation::class);
    }

    /** @return HasMany<WeatherForecast, $this> */
    public function weatherForecasts(): HasMany
    {
        return $this->hasMany(WeatherForecast::class);
    }

    /** @return HasMany<Advisory, $this> */
    public function advisories(): HasMany
    {
        return $this->hasMany(Advisory::class);
    }

    /** @return HasMany<DiagnosisRequest, $this> */
    public function diagnosisRequests(): HasMany
    {
        return $this->hasMany(DiagnosisRequest::class);
    }

    /** @return HasMany<DiagnosisRequest, $this> */
    public function diagnoses(): HasMany
    {
        return $this->diagnosisRequests();
    }

    /** @return HasMany<VoiceAssistanceRequest, $this> */
    public function voiceAssistanceRequests(): HasMany
    {
        return $this->hasMany(VoiceAssistanceRequest::class);
    }

    /** @return HasMany<AssetFinanceApplication, $this> */
    public function assetFinanceApplications(): HasMany
    {
        return $this->hasMany(AssetFinanceApplication::class);
    }

    /**
     * @param  Builder<Farm>  $query
     * @return Builder<Farm>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole(GlobalRole::PlatformAdmin->value)) {
            return $query;
        }

        $organizationIds = $user->organizationIdsWhereRoleIn([
            OrganizationRole::OrganizationAdmin,
            OrganizationRole::Agronomist,
        ]);
        $ledClusters = OrganizationMembership::query()
            ->where('user_id', $user->getKey())
            ->where('status', OrganizationMembershipStatus::Active->value)
            ->where('role', OrganizationRole::ClusterLead->value)
            ->whereNotNull('cluster_name')
            ->get(['organization_id', 'cluster_name']);

        return $query->where(function (Builder $visible) use ($user, $organizationIds, $ledClusters): void {
            $visible->where('owner_user_id', $user->getKey())
                ->orWhereIn('organization_id', $organizationIds);

            foreach ($ledClusters as $membership) {
                $visible->orWhere(function (Builder $clusterFarms) use ($membership): void {
                    $clusterFarms->where('organization_id', $membership->organization_id)
                        ->whereIn('owner_user_id', OrganizationMembership::query()
                            ->select('user_id')
                            ->where('organization_id', $membership->organization_id)
                            ->where('status', OrganizationMembershipStatus::Active->value)
                            ->where('cluster_name', $membership->cluster_name));
                });
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @param  mixed  $value
     * @param  string|null  $field
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $routeField = $field ?? $this->getRouteKeyName();
        if ($routeField === 'uuid' && (! is_string($value) || ! Str::isUuid($value))) {
            return null;
        }

        return parent::resolveRouteBinding($value, $field);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'boundary_geojson' => 'array',
            'area_hectares' => 'decimal:4',
            'area_acres' => 'decimal:4',
            'centroid_latitude' => 'decimal:7',
            'centroid_longitude' => 'decimal:7',
            'status' => FarmStatus::class,
            'provider_status' => ProviderStatus::class,
            'last_synced_at' => 'datetime',
        ];
    }
}
