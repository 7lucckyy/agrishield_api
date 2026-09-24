<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\VoiceAssistanceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class VoiceAssistanceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof VoiceAssistanceRequest) {
            throw new LogicException('VoiceAssistanceResource requires a VoiceAssistanceRequest model.');
        }

        return [
            'id' => $this->resource->uuid,
            'farm' => $this->whenLoaded('farm', fn (): ?array => $this->resource->farm === null ? null : ['id' => $this->resource->farm->uuid, 'name' => $this->resource->farm->name]),
            'source_language' => $this->resource->source_language,
            'response_language' => $this->resource->response_language,
            'status' => $this->resource->status->value,
            'transcript' => $this->resource->transcript,
            'translated_transcript' => $this->resource->translated_transcript,
            'guidance' => $this->resource->guidance,
            'safety_note' => $this->resource->safety_note,
            'provider' => $this->resource->provider,
            'completed_at' => $this->resource->completed_at?->toISOString(),
            'created_at' => $this->resource->created_at?->toISOString(),
        ];
    }
}
