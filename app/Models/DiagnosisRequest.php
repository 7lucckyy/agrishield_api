<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DiagnosisStatus;
use Database\Factories\DiagnosisRequestFactory;
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
 * @property int $farm_id
 * @property int|null $farm_crop_cycle_id
 * @property int $requested_by_user_id
 * @property string $image_disk
 * @property string $image_path
 * @property string $image_mime
 * @property int $image_size_bytes
 * @property string $image_checksum
 * @property string|null $note
 * @property string $response_language
 * @property DiagnosisStatus $status
 * @property string|null $diagnosis
 * @property string|null $recommendation
 * @property string|null $confidence
 * @property array<int, array{label: string, confidence: float}>|null $detected_labels
 * @property string|null $external_reference
 * @property int|null $reviewed_by_user_id
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $submitted_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $expires_at
 * @property string|null $failure_reason
 * @property Carbon|null $created_at
 * @property-read Farm $farm
 */
#[Fillable(['uuid', 'client_request_id', 'farm_crop_cycle_id', 'image_disk', 'image_path', 'image_mime', 'image_size_bytes', 'image_checksum', 'note', 'response_language', 'status', 'diagnosis', 'recommendation', 'confidence', 'detected_labels', 'provider_payload', 'external_reference', 'reviewed_at', 'submitted_at', 'completed_at', 'expires_at', 'failure_reason'])]
final class DiagnosisRequest extends Model
{
    /** @use HasFactory<DiagnosisRequestFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'queued', 'image_disk' => 'private', 'response_language' => 'en'];

    /** @return BelongsTo<Farm, $this> */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /** @return BelongsTo<CropCycle, $this> */
    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class, 'farm_crop_cycle_id');
    }

    /** @return BelongsTo<User, $this> */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if (($field ?? $this->getRouteKeyName()) === 'uuid' && (! is_string($value) || ! Str::isUuid($value))) {
            return null;
        }

        return parent::resolveRouteBinding($value, $field);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => DiagnosisStatus::class,
            'confidence' => 'decimal:4',
            'detected_labels' => 'array',
            'provider_payload' => 'array',
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
