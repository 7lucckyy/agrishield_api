<?php

namespace App\Http\Resources\Api\V1;

use App\Models\FarmLedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class FarmLedgerEntryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof FarmLedgerEntry) {
            throw new LogicException('FarmLedgerEntryResource requires a FarmLedgerEntry.');
        }

        return [
            'id' => $this->resource->uuid,
            'entry_type' => $this->resource->entry_type->value,
            'category' => $this->resource->category,
            'occurred_on' => $this->resource->occurred_on->toDateString(),
            'description' => $this->resource->description,
            'quantity' => $this->resource->quantity === null ? null : (float) $this->resource->quantity,
            'unit' => $this->resource->unit,
            'unit_price' => $this->resource->unit_price === null ? null : (float) $this->resource->unit_price,
            'amount' => (float) $this->resource->amount,
            'payment_method' => $this->resource->payment_method,
            'counterparty' => $this->resource->counterparty,
            'source' => $this->resource->source,
            'voice_transcript' => $this->resource->voice_transcript,
            'notes' => $this->resource->notes,
            'farm_crop_cycle_id' => $this->resource->farm_crop_cycle_id,
            'created_at' => $this->resource->created_at?->toISOString(),
        ];
    }
}
