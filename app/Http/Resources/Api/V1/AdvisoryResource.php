<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Advisory;
use App\Models\AdvisoryAcknowledgement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class AdvisoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Advisory) {
            throw new LogicException('AdvisoryResource requires an Advisory model.');
        }

        $advisory = $this->resource;
        /** @var AdvisoryAcknowledgement|null $acknowledgement */
        $acknowledgement = $advisory->relationLoaded('acknowledgements')
            ? $advisory->acknowledgements->first()
            : null;

        return [
            'id' => $advisory->getKey(),
            'type' => $advisory->type->value,
            'severity' => $advisory->severity?->value,
            'title' => $advisory->title,
            'summary' => $advisory->summary,
            'payload' => $advisory->payload,
            'observed_at' => $advisory->observed_at?->toISOString(),
            'valid_from' => $advisory->valid_from?->toISOString(),
            'valid_until' => $advisory->valid_until?->toISOString(),
            'source' => $advisory->source,
            'crop_cycle_id' => $advisory->farm_crop_cycle_id,
            'acknowledgement' => [
                'read_at' => $acknowledgement?->read_at?->toISOString(),
                'acted_at' => $acknowledgement?->acted_at?->toISOString(),
                'feedback' => $acknowledgement?->feedback,
            ],
        ];
    }
}
