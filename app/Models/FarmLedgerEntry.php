<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FarmLedgerEntryType;
use Database\Factories\FarmLedgerEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['entry_type', 'category', 'occurred_on', 'description', 'quantity', 'unit', 'unit_price', 'amount', 'payment_method', 'counterparty', 'source', 'voice_transcript', 'notes', 'metadata'])]
final class FarmLedgerEntry extends Model
{
    /** @use HasFactory<FarmLedgerEntryFactory> */
    use HasFactory;

    protected $attributes = ['source' => 'manual'];

    protected static function booted(): void
    {
        self::creating(fn (self $entry) => $entry->uuid ??= (string) Str::uuid());
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class, 'farm_crop_cycle_id');
    }

    public function voiceAssistanceRequest(): BelongsTo
    {
        return $this->belongsTo(VoiceAssistanceRequest::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'entry_type' => FarmLedgerEntryType::class,
            'occurred_on' => 'date',
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }
}
