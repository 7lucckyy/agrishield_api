<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AgriculturalOfferCategory;
use App\Enums\AgriculturalServiceRequestStatus;
use Database\Factories\AgriculturalServiceRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $agricultural_offer_id
 * @property int $user_id
 * @property int $farm_id
 * @property int|null $organization_id
 * @property int|null $farm_crop_cycle_id
 * @property AgriculturalOfferCategory $request_type
 * @property AgriculturalServiceRequestStatus $status
 * @property string|null $quantity
 * @property string|null $unit
 * @property string|null $budget_amount
 * @property Carbon|null $requested_for
 * @property string|null $purpose
 * @property string $consent_version
 * @property Carbon $consented_at
 * @property Carbon $submitted_at
 * @property string|null $decision_note
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property-read AgriculturalOffer $offer
 * @property-read User $user
 * @property-read Farm $farm
 * @property-read Organization|null $organization
 * @property-read CropCycle|null $cropCycle
 */
#[Fillable(['quantity', 'unit', 'budget_amount', 'requested_for', 'purpose', 'decision_note', 'metadata'])]
final class AgriculturalServiceRequest extends Model
{
    /** @use HasFactory<AgriculturalServiceRequestFactory> */
    use HasFactory;

    protected $attributes = ['status' => 'submitted', 'consent_version' => 'agricultural-services-v1'];

    protected static function booted(): void
    {
        self::creating(fn (self $request) => $request->uuid ??= (string) Str::uuid());
    }

    /** @return BelongsTo<AgriculturalOffer, $this> */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(AgriculturalOffer::class, 'agricultural_offer_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Farm, $this> */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<CropCycle, $this> */
    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class, 'farm_crop_cycle_id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'request_type' => AgriculturalOfferCategory::class,
            'status' => AgriculturalServiceRequestStatus::class,
            'quantity' => 'decimal:3',
            'budget_amount' => 'decimal:2',
            'requested_for' => 'date',
            'consented_at' => 'datetime',
            'submitted_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
