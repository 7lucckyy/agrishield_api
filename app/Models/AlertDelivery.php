<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AlertDeliveryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $advisory_id
 * @property int $user_id
 * @property string $channel
 * @property string|null $destination
 * @property string $status
 * @property string|null $provider
 * @property string|null $provider_reference
 * @property int $attempts
 * @property Carbon|null $last_attempted_at
 * @property Carbon|null $sent_at
 * @property Carbon|null $delivered_at
 * @property string|null $failure_reason
 * @property Carbon|null $created_at
 * @property-read Advisory $advisory
 * @property-read User $user
 */
#[Fillable(['user_id', 'channel', 'destination', 'status', 'provider', 'provider_reference', 'attempts', 'last_attempted_at', 'sent_at', 'delivered_at', 'failure_reason'])]
final class AlertDelivery extends Model
{
    /** @use HasFactory<AlertDeliveryFactory> */
    use HasFactory;

    protected $attributes = ['status' => 'pending', 'attempts' => 0];

    /** @return BelongsTo<Advisory, $this> */
    public function advisory(): BelongsTo
    {
        return $this->belongsTo(Advisory::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return ['attempts' => 'integer', 'last_attempted_at' => 'datetime', 'sent_at' => 'datetime', 'delivered_at' => 'datetime'];
    }
}
