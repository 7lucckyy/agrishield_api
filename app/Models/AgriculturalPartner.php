<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AgriculturalPartnerType;
use Database\Factories\AgriculturalPartnerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'type', 'contact_phone', 'contact_email', 'state', 'locality', 'is_active', 'metadata'])]
final class AgriculturalPartner extends Model
{
    /** @use HasFactory<AgriculturalPartnerFactory> */
    use HasFactory;

    protected $attributes = ['is_active' => true];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(AgriculturalOffer::class);
    }

    protected function casts(): array
    {
        return ['type' => AgriculturalPartnerType::class, 'verified_at' => 'datetime', 'is_active' => 'boolean', 'metadata' => 'array'];
    }
}
