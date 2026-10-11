<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FarmLedgerEntryType;
use Database\Factories\FarmLedgerEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $farm_id
 * @property int $user_id
 * @property int|null $farm_crop_cycle_id
 * @property int|null $voice_assistance_request_id
 * @property FarmLedgerEntryType $entry_type
 * @property string $category
 * @property Carbon $occurred_on
 * @property string $description
 * @property string|null $quantity
 * @property string|null $unit
 * @property string|null $unit_price
 * @property string $amount
 * @property string|null $payment_method
 * @property string|null $counterparty
 * @property string $source
 * @property string|null $voice_transcript
 * @property string|null $notes
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property-read Farm $farm
 * @property-read User $user
 * @property-read CropCycle|null $cropCycle
 * @property-read VoiceAssistanceRequest|null $voiceAssistanceRequest
 */
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

    /** @return BelongsTo<Farm, $this> */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<CropCycle, $this> */
    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class, 'farm_crop_cycle_id');
    }

    /** @return BelongsTo<VoiceAssistanceRequest, $this> */
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
