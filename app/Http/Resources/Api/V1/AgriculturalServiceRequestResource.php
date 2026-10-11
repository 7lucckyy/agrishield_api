<?php

namespace App\Http\Resources\Api\V1;

use App\Models\AgriculturalServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class AgriculturalServiceRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof AgriculturalServiceRequest) {
            throw new LogicException('AgriculturalServiceRequestResource requires an AgriculturalServiceRequest.');
        }

        return [
            'id' => $this->resource->uuid,
            'request_type' => $this->resource->request_type->value,
            'status' => $this->resource->status->value,
            'quantity' => $this->resource->quantity === null ? null : (float) $this->resource->quantity,
            'unit' => $this->resource->unit,
            'budget_amount' => $this->resource->budget_amount === null ? null : (float) $this->resource->budget_amount,
            'requested_for' => $this->resource->requested_for?->toDateString(),
            'purpose' => $this->resource->purpose,
            'decision_note' => $this->resource->decision_note,
            'submitted_at' => $this->resource->submitted_at?->toISOString(),
            'farm' => ['id' => $this->resource->farm->uuid, 'name' => $this->resource->farm->name],
            'offer' => ['id' => $this->resource->offer->getKey(), 'title' => $this->resource->offer->title, 'partner' => $this->resource->offer->partner->name],
        ];
    }
}
