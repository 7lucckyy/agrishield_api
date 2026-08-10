<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\CropCycle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class CropCycleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof CropCycle) {
            throw new LogicException('CropCycleResource requires a CropCycle model.');
        }

        $cycle = $this->resource;

        return [
            'id' => $cycle->getKey(),
            'farm_id' => $cycle->farm->uuid,
            'crop' => new CropResource($cycle->crop),
            'planting_date' => $cycle->planting_date?->toDateString(),
            'expected_harvest_date' => $cycle->expected_harvest_date?->toDateString(),
            'actual_harvest_date' => $cycle->actual_harvest_date?->toDateString(),
            'season' => $cycle->season,
            'status' => $cycle->status->value,
            'notes' => $cycle->notes,
            'created_at' => $cycle->created_at?->toISOString(),
        ];
    }
}
