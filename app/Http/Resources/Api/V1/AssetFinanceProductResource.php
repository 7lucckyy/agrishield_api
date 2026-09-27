<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\AssetFinanceProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class AssetFinanceProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof AssetFinanceProduct) {
            throw new LogicException('AssetFinanceProductResource requires an AssetFinanceProduct model.');
        }

        $product = $this->resource;

        return [
            'id' => $product->getKey(),
            'name' => $product->name,
            'category' => $product->category->value,
            'financing_structure' => $product->financing_structure,
            'currency' => $product->currency,
            'minimum_amount' => $product->minimum_amount === null ? null : (float) $product->minimum_amount,
            'maximum_amount' => $product->maximum_amount === null ? null : (float) $product->maximum_amount,
            'minimum_deposit_percent' => $product->minimum_deposit_percent === null ? null : (float) $product->minimum_deposit_percent,
            'maximum_tenor_months' => $product->maximum_tenor_months,
            'eligibility_summary' => $product->eligibility_summary,
            'partner' => [
                'id' => $product->financePartner->uuid,
                'name' => $product->financePartner->name,
                'legal_name' => $product->financePartner->legal_name,
                'type' => $product->financePartner->type->value,
                'financing_model' => $product->financePartner->financing_model,
                'relationship_status' => $product->financePartner->metadata['relationship_status'] ?? 'proposed',
            ],
        ];
    }
}
