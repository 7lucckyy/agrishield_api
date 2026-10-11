<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AlertDeliveryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'channel', 'destination', 'status', 'provider', 'provider_reference', 'attempts', 'last_attempted_at', 'sent_at', 'delivered_at', 'failure_reason'])]
final class AlertDelivery extends Model
{
    /** @use HasFactory<AlertDeliveryFactory> */
    use HasFactory;

    protected $attributes = ['status' => 'pending', 'attempts' => 0];

    public function advisory(): BelongsTo
    {
        return $this->belongsTo(Advisory::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return ['attempts' => 'integer', 'last_attempted_at' => 'datetime', 'sent_at' => 'datetime', 'delivered_at' => 'datetime'];
    }
}
