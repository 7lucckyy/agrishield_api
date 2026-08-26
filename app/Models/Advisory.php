<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdvisorySeverity;
use App\Enums\AdvisoryType;
use Database\Factories\AdvisoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $farm_id
 * @property int|null $farm_crop_cycle_id
 * @property AdvisoryType $type
 * @property string $title
 * @property string|null $summary
 * @property array<string, mixed>|null $payload
 * @property AdvisorySeverity|null $severity
 * @property Carbon|null $observed_at
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_until
 * @property string $source
 * @property-read Collection<int, AdvisoryAcknowledgement> $acknowledgements
 */
#[Fillable(['type', 'title', 'summary', 'payload', 'severity', 'locale', 'observed_at', 'valid_from', 'valid_until', 'source', 'external_reference', 'dedupe_key'])]
final class Advisory extends Model
{
    /** @use HasFactory<AdvisoryFactory> */
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

    /** @return BelongsTo<User, $this> */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    /** @return HasMany<AdvisoryAcknowledgement, $this> */
    public function acknowledgements(): HasMany
    {
        return $this->hasMany(AdvisoryAcknowledgement::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => AdvisoryType::class,
            'severity' => AdvisorySeverity::class,
            'payload' => 'array',
            'observed_at' => 'datetime',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
        ];
    }
}
