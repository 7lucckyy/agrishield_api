<?php

namespace App\Http\Resources\Api\V1;

use App\Models\AgriculturalOffer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class AgriculturalOfferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof AgriculturalOffer) {
            throw new LogicException('AgriculturalOfferResource requires an AgriculturalOffer.');
        }

        $availableQuantity = $this->resource->available_quantity === null ? null : (float) $this->resource->available_quantity;
        $committedQuantity = (float) ($this->resource->getAttribute('committed_quantity') ?? 0);

        return [
            'id' => $this->resource->getKey(),
            'category' => $this->resource->category->value,
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'pricing' => [
                'model' => $this->resource->pricing_model,
                'unit_price' => $this->resource->unit_price === null ? null : (float) $this->resource->unit_price,
                'currency' => $this->resource->currency,
                'unit' => $this->resource->unit,
                'minimum_quantity' => $this->resource->minimum_quantity === null ? null : (float) $this->resource->minimum_quantity,
                'available_quantity' => $availableQuantity,
                'remaining_quantity' => $availableQuantity === null ? null : max(0, $availableQuantity - $committedQuantity),
            ],
            'location' => ['state' => $this->resource->state, 'locality' => $this->resource->locality],
            'requirements' => $this->resource->requirements ?? [],
            'fulfillment_channels' => $this->resource->fulfillment_channels ?? [],
            'starts_at' => $this->resource->starts_at?->toISOString(),
            'ends_at' => $this->resource->ends_at?->toISOString(),
            'crop' => $this->whenLoaded('crop', fn (): ?array => $this->resource->crop === null ? null : ['id' => $this->resource->crop->getKey(), 'name' => $this->resource->crop->name]),
            'partner' => [
                'name' => $this->resource->partner->name,
                'type' => $this->resource->partner->type->value,
                'verified' => $this->resource->partner->verified_at !== null,
                'contact_phone' => $this->resource->partner->contact_phone,
            ],
            'notice' => 'Price, stock, finance and availability are confirmed by the partner before any agreement.',
        ];
    }
}
