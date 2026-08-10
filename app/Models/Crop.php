<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CropCategory;
use Database\Factories\CropFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $scientific_name
 * @property string $code
 * @property CropCategory|null $category
 * @property int|null $default_cycle_days
 * @property bool $active
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'scientific_name', 'code', 'category', 'default_cycle_days', 'active', 'metadata'])]
final class Crop extends Model
{
    /** @use HasFactory<CropFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'active' => true,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'category' => CropCategory::class,
            'default_cycle_days' => 'integer',
            'metadata' => 'array',
        ];
    }
}
