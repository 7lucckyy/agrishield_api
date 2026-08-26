<?php

namespace App\Http\Resources\Api\V1;

use App\Models\DiagnosisRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;
use LogicException;

final class DiagnosisRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof DiagnosisRequest) {
            throw new LogicException('DiagnosisRequestResource requires a DiagnosisRequest model.');
        }

        $diagnosis = $this->resource;
        $expiresAt = now()->addMinutes(5);

        return [
            'id' => $diagnosis->uuid,
            'farm_id' => $diagnosis->farm->uuid,
            'crop_cycle_id' => $diagnosis->farm_crop_cycle_id,
            'status' => $diagnosis->status->value,
            'note' => $diagnosis->note,
            'image' => [
                'url' => URL::temporarySignedRoute('media.diagnosis.image', $expiresAt, ['diagnosis' => $diagnosis]),
                'expires_at' => $expiresAt->toISOString(),
                'mime' => $diagnosis->image_mime,
                'size_bytes' => $diagnosis->image_size_bytes,
            ],
            'diagnosis' => $diagnosis->diagnosis,
            'recommendation' => $diagnosis->recommendation,
            'confidence' => $diagnosis->confidence === null ? null : (float) $diagnosis->confidence,
            'detected_labels' => $diagnosis->detected_labels,
            'reviewed_by' => $diagnosis->reviewed_by_user_id,
            'submitted_at' => $diagnosis->submitted_at?->toISOString(),
            'completed_at' => $diagnosis->completed_at?->toISOString(),
            'expires_at' => $diagnosis->expires_at?->toISOString(),
            'created_at' => $diagnosis->created_at?->toISOString(),
        ];
    }
}
