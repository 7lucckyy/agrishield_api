<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\AssetFinanceProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class FarmerEquipmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof AssetFinanceProduct) {
            throw new LogicException('FarmerEquipmentResource requires an AssetFinanceProduct.');
        }

        $product = $this->resource;
        $metadata = $product->metadata ?? [];

        return [
            'id' => $product->getKey(),
            'name' => $product->name,
            'category' => $product->category->value,
            'description' => $metadata['description'] ?? null,
            'capacity' => $metadata['capacity'] ?? null,
            'acquisition_options' => $product->getAttribute('acquisition_options'),
            'match_score' => $product->getAttribute('recommendation_score'),
            'why_recommended' => $product->getAttribute('recommendation_reasons'),
            'farm_size_tier' => $product->getAttribute('farm_size_tier'),
        ];
    }
}
