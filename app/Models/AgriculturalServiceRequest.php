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
use Illuminate\Support\Str;

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

    public function offer(): BelongsTo
    {
        return $this->belongsTo(AgriculturalOffer::class, 'agricultural_offer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

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
