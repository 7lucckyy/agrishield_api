<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VoiceAssistanceStatus;
use Database\Factories\VoiceAssistanceRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string|null $client_request_id
 * @property int $user_id
 * @property int|null $organization_id
 * @property int|null $farm_id
 * @property string $source_language
 * @property string $response_language
 * @property string $audio_disk
 * @property string $audio_path
 * @property string $audio_mime
 * @property VoiceAssistanceStatus $status
 * @property string|null $transcript
 * @property string|null $translated_transcript
 * @property string|null $guidance
 * @property string|null $safety_note
 * @property string $provider
 * @property string|null $provider_reference
 * @property string|null $failure_reason
 * @property Carbon|null $completed_at
 * @property-read User $user
 * @property-read Farm|null $farm
 * @property-read Organization|null $organization
 */
#[Fillable(['uuid', 'client_request_id', 'source_language', 'response_language', 'audio_disk', 'audio_path', 'audio_mime', 'audio_size_bytes', 'audio_checksum', 'status', 'transcript', 'translated_transcript', 'guidance', 'safety_note', 'provider', 'provider_reference', 'failure_reason', 'completed_at'])]
final class VoiceAssistanceRequest extends Model
{
    /** @use HasFactory<VoiceAssistanceRequestFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = ['audio_disk' => 'private', 'status' => 'processing'];

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

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if (($field ?? 'uuid') === 'uuid' && (! is_string($value) || ! Str::isUuid($value))) {
            return null;
        }

        return parent::resolveRouteBinding($value, $field);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['status' => VoiceAssistanceStatus::class, 'completed_at' => 'datetime'];
    }
}
