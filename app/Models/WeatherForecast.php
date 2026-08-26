<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WeatherForecastFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $farm_id
 * @property Carbon $forecast_date
 * @property string|null $temperature_min
 * @property string|null $temperature_max
 * @property string|null $humidity
 * @property string|null $rainfall
 * @property string|null $rainfall_probability
 * @property string|null $wind_speed
 * @property string|null $wind_direction
 * @property string|null $cloud_cover
 * @property string|null $condition_code
 * @property array<string, mixed>|null $payload
 * @property string $source
 * @property Carbon $fetched_at
 */
#[Fillable(['forecast_date', 'temperature_min', 'temperature_max', 'humidity', 'rainfall', 'rainfall_probability', 'wind_speed', 'wind_direction', 'cloud_cover', 'condition_code', 'payload', 'source', 'fetched_at'])]
final class WeatherForecast extends Model
{
    /** @use HasFactory<WeatherForecastFactory> */
    use HasFactory;

    /** @return BelongsTo<Farm, $this> */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'forecast_date' => 'date',
            'temperature_min' => 'decimal:2',
            'temperature_max' => 'decimal:2',
            'humidity' => 'decimal:2',
            'rainfall' => 'decimal:2',
            'rainfall_probability' => 'decimal:2',
            'wind_speed' => 'decimal:2',
            'wind_direction' => 'decimal:2',
            'cloud_cover' => 'decimal:2',
            'payload' => 'array',
            'fetched_at' => 'datetime',
        ];
    }
}
