<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Crop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class CropResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Crop) {
            throw new LogicException('CropResource requires a Crop model.');
        }

        $crop = $this->resource;

        return [
            'id' => $crop->id,
            'name' => $crop->name,
            'scientific_name' => $crop->scientific_name,
            'code' => $crop->code,
            'category' => $crop->category?->value,
            'default_cycle_days' => $crop->default_cycle_days,
            'active' => $crop->active,
        ];
    }
}
