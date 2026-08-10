<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CircuitState;
use App\Enums\IntegrationStatus;
use Database\Factories\IntegrationAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $provider
 * @property string|null $label
 * @property IntegrationStatus $status
 * @property string|null $credentials_ref
 * @property array<string, mixed>|null $config
 * @property Carbon|null $last_success_at
 * @property Carbon|null $last_failure_at
 * @property int $consecutive_failures
 * @property CircuitState $circuit_state
 * @property Carbon|null $circuit_opened_at
 * @property-read Collection<int, FarmProviderLink> $farmProviderLinks
 */
#[Fillable(['provider', 'label', 'status', 'credentials_ref', 'config'])]
#[Hidden(['credentials_ref', 'config'])]
final class IntegrationAccount extends Model
{
    /** @use HasFactory<IntegrationAccountFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'inactive',
        'consecutive_failures' => 0,
        'circuit_state' => 'closed',
    ];

    /** @return HasMany<FarmProviderLink, $this> */
    public function farmProviderLinks(): HasMany
    {
        return $this->hasMany(FarmProviderLink::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => IntegrationStatus::class,
            'config' => 'array',
            'last_success_at' => 'datetime',
            'last_failure_at' => 'datetime',
            'consecutive_failures' => 'integer',
            'circuit_state' => CircuitState::class,
            'circuit_opened_at' => 'datetime',
        ];
    }
}
