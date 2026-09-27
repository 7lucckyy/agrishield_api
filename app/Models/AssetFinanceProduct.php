<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AssetCategory;
use Database\Factories\AssetFinanceProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['finance_partner_id', 'name', 'category', 'financing_structure', 'currency', 'minimum_amount', 'maximum_amount', 'minimum_deposit_percent', 'maximum_tenor_months', 'eligibility_summary', 'is_active', 'metadata'])]
final class AssetFinanceProduct extends Model
{
    /** @use HasFactory<AssetFinanceProductFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = ['currency' => 'NGN', 'is_active' => true];

    /** @return BelongsTo<FinancePartner, $this> */
    public function financePartner(): BelongsTo
    {
        return $this->belongsTo(FinancePartner::class);
    }

    /** @return HasMany<AssetFinanceApplication, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(AssetFinanceApplication::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'category' => AssetCategory::class,
            'minimum_amount' => 'decimal:2',
            'maximum_amount' => 'decimal:2',
            'minimum_deposit_percent' => 'decimal:2',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }
}
