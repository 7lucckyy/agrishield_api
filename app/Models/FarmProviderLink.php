<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FarmProviderLinkStatus;
use Database\Factories\FarmProviderLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $farm_id
 * @property int $integration_account_id
 * @property string $provider
 * @property string $provider_farm_id
 * @property FarmProviderLinkStatus $status
 * @property string|null $boundary_hash
 * @property array<string, mixed>|null $provider_metadata
 * @property Carbon|null $registered_at
 * @property string|null $last_error
 * @property-read Farm $farm
 * @property-read IntegrationAccount $integrationAccount
 */
#[Fillable(['provider', 'provider_farm_id', 'status', 'boundary_hash', 'provider_metadata', 'registered_at', 'last_error'])]
final class FarmProviderLink extends Model
{
    /** @use HasFactory<FarmProviderLinkFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'pending'];

    /** @return BelongsTo<Farm, $this> */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /** @return BelongsTo<IntegrationAccount, $this> */
    public function integrationAccount(): BelongsTo
    {
        return $this->belongsTo(IntegrationAccount::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => FarmProviderLinkStatus::class,
            'provider_metadata' => 'array',
            'registered_at' => 'datetime',
        ];
    }
}
