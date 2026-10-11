<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AgriculturalPartnerType;
use Database\Factories\AgriculturalPartnerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $organization_id
 * @property string $name
 * @property AgriculturalPartnerType $type
 * @property string|null $contact_phone
 * @property string|null $contact_email
 * @property string|null $state
 * @property string|null $locality
 * @property Carbon|null $verified_at
 * @property int|null $verified_by_user_id
 * @property bool $is_active
 * @property array<string, mixed>|null $metadata
 * @property-read Organization|null $organization
 * @property-read User|null $verifiedBy
 * @property-read Collection<int, AgriculturalOffer> $offers
 */
#[Fillable(['name', 'type', 'contact_phone', 'contact_email', 'state', 'locality', 'is_active', 'metadata'])]
final class AgriculturalPartner extends Model
{
    /** @use HasFactory<AgriculturalPartnerFactory> */
    use HasFactory;

    protected $attributes = ['is_active' => true];

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<User, $this> */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    /** @return HasMany<AgriculturalOffer, $this> */
    public function offers(): HasMany
    {
        return $this->hasMany(AgriculturalOffer::class);
    }

    protected function casts(): array
    {
        return ['type' => AgriculturalPartnerType::class, 'verified_at' => 'datetime', 'is_active' => 'boolean', 'metadata' => 'array'];
    }
}
