<?php

namespace App\Http\Requests\Api\V1\Commerce;

use App\Enums\AgriculturalOfferCategory;
use App\Models\Crop;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListAgriculturalOfferRequest extends FormRequest
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
            'category' => ['nullable', Rule::enum(AgriculturalOfferCategory::class)],
            'crop_id' => ['nullable', 'integer', Rule::exists((new Crop)->getTable(), 'id')],
            'state' => ['nullable', 'string', 'max:120'],
            'farm_id' => ['nullable', 'uuid'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
