<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AgriculturalOfferCategory;
use Database\Factories\AgriculturalOfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $agricultural_partner_id
 * @property int|null $organization_id
 * @property int|null $crop_id
 * @property AgriculturalOfferCategory $category
 * @property string $title
 * @property string|null $description
 * @property string $pricing_model
 * @property string|null $unit_price
 * @property string $currency
 * @property string|null $unit
 * @property string|null $minimum_quantity
 * @property string|null $available_quantity
 * @property string|null $state
 * @property string|null $locality
 * @property array<int, string>|null $requirements
 * @property array<int, string>|null $fulfillment_channels
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property bool $is_active
 * @property-read AgriculturalPartner $partner
 * @property-read Organization|null $organization
 * @property-read Crop|null $crop
 * @property-read Collection<int, AgriculturalServiceRequest> $serviceRequests
 */
#[Fillable(['category', 'title', 'description', 'pricing_model', 'unit_price', 'currency', 'unit', 'minimum_quantity', 'available_quantity', 'state', 'locality', 'requirements', 'fulfillment_channels', 'starts_at', 'ends_at', 'is_active'])]
final class AgriculturalOffer extends Model
{
    /** @use HasFactory<AgriculturalOfferFactory> */
    use HasFactory;

    protected $attributes = ['pricing_model' => 'fixed', 'currency' => 'NGN', 'is_active' => true];

    /** @return BelongsTo<AgriculturalPartner, $this> */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(AgriculturalPartner::class, 'agricultural_partner_id');
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<Crop, $this> */
    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    /** @return HasMany<AgriculturalServiceRequest, $this> */
    public function serviceRequests(): HasMany
    {
        return $this->hasMany(AgriculturalServiceRequest::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereHas('partner', fn (Builder $partner): Builder => $partner->where('is_active', true)->whereNotNull('verified_at'))
            ->where(fn (Builder $dates): Builder => $dates->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $dates): Builder => $dates->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    protected function casts(): array
    {
        return [
            'category' => AgriculturalOfferCategory::class,
            'unit_price' => 'decimal:2',
            'minimum_quantity' => 'decimal:3',
            'available_quantity' => 'decimal:3',
            'requirements' => 'array',
            'fulfillment_channels' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
