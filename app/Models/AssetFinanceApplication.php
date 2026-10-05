<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AssetFinanceStatus;
use App\Enums\RepaymentStatus;
use Database\Factories\AssetFinanceApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $organization_id
 * @property int $farm_id
 * @property int|null $farm_crop_cycle_id
 * @property int $applicant_user_id
 * @property int $asset_finance_product_id
 * @property AssetFinanceStatus $status
 * @property int $quantity
 * @property string $requested_amount
 * @property string $purpose
 * @property string $consent_channel
 * @property string $consent_version
 * @property Carbon $consented_at
 * @property string|null $partner_reference
 * @property string|null $decision_note
 * @property Carbon $submitted_at
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $approved_at
 * @property Carbon|null $delivery_verified_at
 * @property int|null $delivery_verified_by_user_id
 * @property RepaymentStatus $repayment_status
 * @property string|null $outstanding_amount
 * @property Carbon|null $next_payment_due_at
 * @property Carbon|null $last_partner_sync_at
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property-read Farm $farm
 * @property-read AssetFinanceProduct $product
 * @property-read Collection<int, AssetFinanceEvent> $events
 */
#[Fillable(['uuid', 'farm_crop_cycle_id', 'asset_finance_product_id', 'status', 'quantity', 'requested_amount', 'purpose', 'consent_channel', 'consent_version', 'consented_at', 'partner_reference', 'decision_note', 'submitted_at', 'reviewed_at', 'approved_at', 'delivery_verified_at', 'delivery_verified_by_user_id', 'repayment_status', 'outstanding_amount', 'next_payment_due_at', 'last_partner_sync_at', 'metadata'])]
final class AssetFinanceApplication extends Model
{
    /** @use HasFactory<AssetFinanceApplicationFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'submitted',
        'quantity' => 1,
        'consent_channel' => 'organization_portal',
        'consent_version' => 'asset-access-v1',
        'repayment_status' => 'not_started',
    ];

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

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
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_user_id');
    }

    /** @return BelongsTo<AssetFinanceProduct, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(AssetFinanceProduct::class, 'asset_finance_product_id');
    }

    /** @return BelongsTo<User, $this> */
    public function deliveryVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivery_verified_by_user_id');
    }

    /** @return HasMany<AssetFinanceEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(AssetFinanceEvent::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if (($field ?? $this->getRouteKeyName()) === 'uuid' && (! is_string($value) || ! Str::isUuid($value))) {
            return null;
        }

        return parent::resolveRouteBinding($value, $field);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => AssetFinanceStatus::class,
            'requested_amount' => 'decimal:2',
            'consented_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'delivery_verified_at' => 'datetime',
            'repayment_status' => RepaymentStatus::class,
            'outstanding_amount' => 'decimal:2',
            'next_payment_due_at' => 'date',
            'last_partner_sync_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
