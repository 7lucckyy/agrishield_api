<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $advisory_id
 * @property int $user_id
 * @property Carbon|null $read_at
 * @property Carbon|null $acted_at
 * @property string|null $feedback
 */
#[Fillable(['read_at', 'acted_at', 'feedback'])]
final class AdvisoryAcknowledgement extends Model
{
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

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['read_at' => 'datetime', 'acted_at' => 'datetime'];
    }
}
