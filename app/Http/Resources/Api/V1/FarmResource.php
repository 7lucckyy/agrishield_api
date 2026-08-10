<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Farm;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class FarmResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Farm) {
            throw new LogicException('FarmResource requires a Farm model.');
        }

        $farm = $this->resource;

        return [
            'id' => $farm->uuid,
            'name' => $farm->name,
            'owner' => $this->whenLoaded('owner', fn (): array => [
                'id' => $farm->owner->getKey(),
                'name' => $farm->owner->name,
            ]),
            'organization' => $this->whenLoaded('organization', fn (): ?array => $farm->organization === null ? null : [
                'id' => $farm->organization->getKey(),
                'name' => $farm->organization->name,
            ]),
            'boundary_geojson' => $farm->boundary_geojson,
            'centroid' => [
                'latitude' => $farm->centroid_latitude === null ? null : (float) $farm->centroid_latitude,
                'longitude' => $farm->centroid_longitude === null ? null : (float) $farm->centroid_longitude,
            ],
            'area' => [
                'hectares' => $farm->area_hectares === null ? null : (float) $farm->area_hectares,
                'acres' => $farm->area_acres === null ? null : (float) $farm->area_acres,
            ],
            'locality' => $farm->locality,
            'state' => $farm->state,
            'country' => $farm->country,
            'status' => $farm->status->value,
            'provider_status' => $farm->provider_status->value,
            'active_crop_cycle' => $this->whenLoaded(
                'activeCropCycle',
                fn (): ?CropCycleResource => $farm->activeCropCycle === null
                    ? null
                    : new CropCycleResource($farm->activeCropCycle),
            ),
            'last_synced_at' => $farm->last_synced_at?->toISOString(),
            'created_at' => $farm->created_at?->toISOString(),
        ];
    }
}
