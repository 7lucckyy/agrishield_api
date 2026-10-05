<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CropCycleStatus;
use App\Enums\IrrigationContext;
use Database\Factories\CropCycleFactory;
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
 * @property Carbon|null $planting_date
 * @property Carbon|null $expected_harvest_date
 * @property Carbon|null $actual_harvest_date
 * @property string|null $season
 * @property string|null $variety
 * @property string|null $growth_stage
 * @property IrrigationContext|null $irrigation_context
 * @property CropCycleStatus $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property-read Farm $farm
 * @property-read Crop $crop
 * @property-read Collection<int, FarmSection> $sections
 */
#[Fillable([
    'crop_id',
    'planting_date',
    'expected_harvest_date',
    'actual_harvest_date',
    'season',
    'variety',
    'growth_stage',
    'irrigation_context',
    'status',
    'notes',
])]
final class CropCycle extends Model
{
    /** @use HasFactory<CropCycleFactory> */
    use HasFactory;

    protected $table = 'farm_crop_cycles';

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'planned'];

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

    /** @return BelongsToMany<FarmSection, $this> */
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(FarmSection::class)->withTimestamps();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'planting_date' => 'date',
            'expected_harvest_date' => 'date',
            'actual_harvest_date' => 'date',
            'status' => CropCycleStatus::class,
            'irrigation_context' => IrrigationContext::class,
        ];
    }
}
