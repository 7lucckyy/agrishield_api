<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FarmStatus;
use App\Enums\GlobalRole;
use App\Enums\OrganizationRole;
use App\Enums\ProviderStatus;
use Database\Factories\FarmFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $organization_id
 * @property int $owner_user_id
 * @property string $name
 * @property array{type: 'Polygon'|'MultiPolygon', coordinates: array<mixed>} $boundary_geojson
 * @property string $boundary_hash
 * @property string|null $centroid_latitude
 * @property string|null $centroid_longitude
 * @property string|null $area_hectares
 * @property string|null $area_acres
 * @property string|null $locality
 * @property string|null $state
 * @property string|null $country
 * @property FarmStatus $status
 * @property ProviderStatus $provider_status
 * @property Carbon|null $last_synced_at
 * @property Carbon|null $created_at
 * @property-read User $owner
 * @property-read Organization|null $organization
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

        return $query->where(function (Builder $visible) use ($user, $organizationIds): void {
            $visible->where('owner_user_id', $user->getKey())
                ->orWhereIn('organization_id', $organizationIds);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
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
