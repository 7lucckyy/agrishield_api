<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\AssetFinance;

use App\Models\AssetFinanceProduct;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetFinanceApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $organization = $this->route('organization');

        return $organization instanceof Organization && $this->user()?->can('manageAssetFinance', $organization) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'farm_id' => ['required', 'uuid', Rule::exists((new Farm)->getTable(), 'uuid')->where('organization_id', $this->organizationId())],
            'farm_crop_cycle_id' => ['nullable', 'integer', Rule::exists((new CropCycle)->getTable(), 'id')],
            'asset_finance_product_id' => ['required', 'integer', Rule::exists((new AssetFinanceProduct)->getTable(), 'id')->where('is_active', true)],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'requested_amount' => ['required', 'numeric', 'min:1', 'max:999999999999.99'],
            'purpose' => ['required', 'string', 'min:20', 'max:2000'],
            'consent' => ['accepted'],
        ];
    }

    private function organizationId(): int
    {
        $organization = $this->route('organization');

        return $organization instanceof Organization ? $organization->getKey() : 0;
    }
}
