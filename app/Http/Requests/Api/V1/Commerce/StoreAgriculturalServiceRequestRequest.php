<?php

namespace App\Http\Requests\Api\V1\Commerce;

use App\Models\AgriculturalOffer;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAgriculturalServiceRequestRequest extends FormRequest
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
            'offer_id' => ['required', 'integer', Rule::exists((new AgriculturalOffer)->getTable(), 'id')],
            'farm_id' => ['required', 'uuid', Rule::exists((new Farm)->getTable(), 'uuid')->where('owner_user_id', $this->user()?->getKey())],
            'farm_crop_cycle_id' => ['nullable', 'integer', Rule::exists((new CropCycle)->getTable(), 'id')],
            'quantity' => ['nullable', 'numeric', 'min:0.001', 'max:999999999.999'],
            'unit' => ['nullable', 'string', 'max:80'],
            'budget_amount' => ['nullable', 'numeric', 'min:1', 'max:999999999999.99'],
            'requested_for' => ['nullable', 'date', 'after_or_equal:today'],
            'purpose' => ['nullable', 'string', 'max:2000'],
            'consent' => ['accepted'],
        ];
    }
}
