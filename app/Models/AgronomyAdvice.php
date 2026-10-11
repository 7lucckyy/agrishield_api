<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AgronomyAdviceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['uuid', 'language', 'question', 'answer', 'next_steps', 'uncertainty', 'safety_note', 'provider', 'context_snapshot'])]
final class AgronomyAdvice extends Model
{
    /** @use HasFactory<AgronomyAdviceFactory> */
    use HasFactory;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Farm, $this> */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'next_steps' => 'array',
            'context_snapshot' => 'array',
        ];
    }
}
