<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MetricType;
use App\Enums\QualityFlag;
use Database\Factories\SatelliteObservationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $farm_id
 * @property int|null $farm_crop_cycle_id
 * @property MetricType $metric_type
 * @property string|null $value
 * @property string|null $unit
 * @property string|null $map_url
 * @property array<string, mixed>|null $statistics
 * @property int|null $resolution_meters
 * @property Carbon $captured_at
 * @property Carbon $fetched_at
 * @property Carbon|null $next_expected_update_at
 * @property QualityFlag|null $quality_flag
 * @property string $source
 * @property string|null $external_reference
 */
#[Fillable(['metric_type', 'value', 'unit', 'map_url', 'statistics', 'resolution_meters', 'captured_at', 'fetched_at', 'next_expected_update_at', 'quality_flag', 'source', 'external_reference', 'raw_payload'])]
final class SatelliteObservation extends Model
{
    /** @use HasFactory<SatelliteObservationFactory> */
    use HasFactory;

    /** @return BelongsTo<Farm, $this> */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /** @return BelongsTo<CropCycle, $this> */
    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class, 'farm_crop_cycle_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'metric_type' => MetricType::class,
            'value' => 'decimal:4',
            'statistics' => 'array',
            'quality_flag' => QualityFlag::class,
            'captured_at' => 'datetime',
            'fetched_at' => 'datetime',
            'next_expected_update_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }
}
