<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FarmSectionStatus;
use Database\Factories\FarmSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $farm_id
 * @property int $crop_id
 * @property string $name
 * @property string|float $area_hectares
 * @property string|float $area_acres
 * @property int $position
 * @property string|null $notes
 * @property array{type: 'Polygon'|'MultiPolygon', coordinates: array<mixed>}|null $boundary_geojson
 * @property string|null $boundary_hash
 * @property string|float|null $centroid_latitude
 * @property string|float|null $centroid_longitude
 * @property FarmSectionStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Farm $farm
 * @property-read Crop $crop
 * @property-read Collection<int, CropCycle> $cropCycles
 */
#[Fillable([
    'name',
    'crop_id',
    'area_hectares',
    'area_acres',
    'position',
    'notes',
    'boundary_geojson',
    'boundary_hash',
    'centroid_latitude',
    'centroid_longitude',
    'status',
])]
final class FarmSection extends Model
{
    /** @use HasFactory<FarmSectionFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'active'];

    /** @return BelongsTo<Farm, $this> */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /** @return BelongsTo<Crop, $this> */
    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    /** @return BelongsToMany<CropCycle, $this> */
    public function cropCycles(): BelongsToMany
    {
        return $this->belongsToMany(CropCycle::class)->withTimestamps();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'area_hectares' => 'decimal:4',
            'area_acres' => 'decimal:4',
            'position' => 'integer',
            'boundary_geojson' => 'array',
            'centroid_latitude' => 'decimal:7',
            'centroid_longitude' => 'decimal:7',
            'status' => FarmSectionStatus::class,
        ];
    }
}
