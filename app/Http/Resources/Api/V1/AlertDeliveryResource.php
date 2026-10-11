<?php

namespace App\Http\Resources\Api\V1;

use App\Models\AlertDelivery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class AlertDeliveryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof AlertDelivery) {
            throw new LogicException('AlertDeliveryResource requires an AlertDelivery.');
        }

        return [
            'id' => $this->resource->getKey(),
            'channel' => $this->resource->channel,
            'status' => $this->resource->status,
            'sent_at' => $this->resource->sent_at?->toISOString(),
            'delivered_at' => $this->resource->delivered_at?->toISOString(),
            'advisory' => [
                'id' => $this->resource->advisory->getKey(),
                'title' => $this->resource->advisory->title,
                'summary' => $this->resource->advisory->summary,
                'severity' => $this->resource->advisory->severity?->value,
            ],
        ];
    }
}
