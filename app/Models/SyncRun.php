<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SyncStatus;
use App\Enums\SyncTrigger;
use App\Enums\SyncType;
use Database\Factories\SyncRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $farm_id
 * @property SyncType $sync_type
 * @property SyncStatus $status
 * @property string $provider
 * @property string|null $provider_request_id
 * @property SyncTrigger $trigger
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property int|null $duration_ms
 * @property int $attempts
 * @property int $records_written
 * @property string|null $error_code
 * @property string|null $error_message
 * @property string|null $idempotency_key
 * @property-read Farm|null $farm
 */
#[Fillable(['uuid', 'sync_type', 'status', 'provider', 'provider_request_id', 'trigger', 'started_at', 'completed_at', 'duration_ms', 'attempts', 'records_written', 'error_code', 'error_message', 'idempotency_key'])]
final class SyncRun extends Model
{
    /** @use HasFactory<SyncRunFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'pending',
        'trigger' => 'schedule',
        'attempts' => 0,
        'records_written' => 0,
    ];

    /** @return BelongsTo<Farm, $this> */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /** @return BelongsTo<User, $this> */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }

    /** @return MorphTo<Model, $this> */
    public function syncable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'sync_type' => SyncType::class,
            'status' => SyncStatus::class,
            'trigger' => SyncTrigger::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'attempts' => 'integer',
            'records_written' => 'integer',
        ];
    }
}
