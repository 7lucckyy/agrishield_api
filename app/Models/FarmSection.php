<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\FarmSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Farm $farm
 * @property-read Crop $crop
 */
#[Fillable(['name', 'crop_id', 'area_hectares', 'area_acres', 'position', 'notes'])]
final class FarmSection extends Model
{
    /** @use HasFactory<FarmSectionFactory> */
    use HasFactory;

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

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'area_hectares' => 'decimal:4',
            'area_acres' => 'decimal:4',
            'position' => 'integer',
        ];
    }
}
