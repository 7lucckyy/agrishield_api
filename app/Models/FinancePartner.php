<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FinancePartnerType;
use Database\Factories\FinancePartnerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['uuid', 'name', 'legal_name', 'slug', 'type', 'financing_model', 'website', 'contact_email', 'is_active', 'metadata'])]
final class FinancePartner extends Model
{
    /** @use HasFactory<FinancePartnerFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = ['is_active' => true];

    /** @return HasMany<AssetFinanceProduct, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(AssetFinanceProduct::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => FinancePartnerType::class,
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }
}
