<?php

namespace App\Http\Resources\Api\V1;

use App\Models\SatelliteObservation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class SatelliteObservationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof SatelliteObservation) {
            throw new LogicException('SatelliteObservationResource requires a SatelliteObservation model.');
        }

        $observation = $this->resource;

        return [
            'id' => $observation->getKey(),
            'metric_type' => $observation->metric_type->value,
            'value' => $observation->value === null ? null : (float) $observation->value,
            'unit' => $observation->unit,
            'resolution_meters' => $observation->resolution_meters,
            'captured_at' => $observation->captured_at->toISOString(),
            'quality_flag' => $observation->quality_flag?->value,
            'statistics' => $observation->statistics,
            'map_url' => $observation->map_url === null ? null : route('media.observations.map', $observation),
            'crop_cycle_id' => $observation->farm_crop_cycle_id,
            'source' => $observation->source,
        ];
    }
}
