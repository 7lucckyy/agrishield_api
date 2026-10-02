<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\FarmSection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class FarmSectionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof FarmSection) {
            throw new LogicException('FarmSectionResource requires a FarmSection model.');
        }

        $section = $this->resource;
        $farmAreaHectares = $section->relationLoaded('farm') && $section->farm->area_hectares !== null
            ? (float) $section->farm->area_hectares
            : null;

        return [
            'id' => $section->getKey(),
            'farm_id' => $this->whenLoaded('farm', fn (): string => $section->farm->uuid),
            'name' => $section->name,
            'crop' => $this->whenLoaded('crop', fn (): CropResource => new CropResource($section->crop)),
            'area' => [
                'hectares' => (float) $section->area_hectares,
                'acres' => (float) $section->area_acres,
                'farm_percentage' => $farmAreaHectares === null || $farmAreaHectares <= 0
                    ? null
                    : round(((float) $section->area_hectares / $farmAreaHectares) * 100, 1),
            ],
            'position' => $section->position,
            'notes' => $section->notes,
            'created_at' => $section->created_at?->toISOString(),
            'updated_at' => $section->updated_at?->toISOString(),
        ];
    }
}
