<?php

namespace App\Http\Requests\Api\V1\AssetFinance;

use App\Models\AssetFinanceProduct;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreFarmerAssetFinanceApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'farm_id' => [
                'required',
                'uuid',
                Rule::exists((new Farm)->getTable(), 'uuid')->where('owner_user_id', $this->user()?->getKey()),
            ],
            'farm_crop_cycle_id' => ['nullable', 'integer', Rule::exists((new CropCycle)->getTable(), 'id')],
            'asset_finance_product_id' => ['required', 'integer', Rule::exists((new AssetFinanceProduct)->getTable(), 'id')->where('is_active', true)],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'requested_amount' => ['required', 'numeric', 'min:1', 'max:999999999999.99'],
            'purpose' => ['required', 'string', 'min:20', 'max:2000'],
            'consent' => ['accepted'],
        ];
    }
}
