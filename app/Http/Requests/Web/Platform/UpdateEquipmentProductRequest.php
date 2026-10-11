<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Platform;

use App\Enums\FinancePartnerType;
use App\Models\AssetFinanceProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateEquipmentProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('assetFinanceProduct');

        return $this->user()?->can('manageEquipment') === true
            && $product instanceof AssetFinanceProduct
            && $product->financePartner->type === FinancePartnerType::EquipmentDesk;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
            'acquisition_options' => ['required', 'array', 'min:1'],
            'acquisition_options.*' => ['required', 'distinct', Rule::in(['lease', 'finance_purchase'])],
        ];
    }
}
