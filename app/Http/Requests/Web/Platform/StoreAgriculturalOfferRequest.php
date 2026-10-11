<?php

namespace App\Http\Requests\Web\Platform;

use App\Enums\AgriculturalOfferCategory;
use App\Models\AgriculturalPartner;
use App\Models\Crop;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAgriculturalOfferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('accessPlatform') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'agricultural_partner_id' => ['required', 'integer', Rule::exists((new AgriculturalPartner)->getTable(), 'id')->whereNotNull('verified_at')->where('is_active', true)],
            'crop_id' => ['nullable', 'integer', Rule::exists((new Crop)->getTable(), 'id')->where('active', true)],
            'category' => ['required', Rule::enum(AgriculturalOfferCategory::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'pricing_model' => ['required', Rule::in(['fixed', 'indicative', 'quote'])],
            'unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'unit' => ['nullable', 'string', 'max:80'],
            'minimum_quantity' => ['nullable', 'numeric', 'min:0.001'],
            'available_quantity' => ['nullable', 'numeric', 'min:0'],
            'state' => ['nullable', 'string', 'max:120'],
            'locality' => ['nullable', 'string', 'max:160'],
            'requirements_text' => ['nullable', 'string', 'max:2000'],
            'ends_at' => ['nullable', 'date', 'after:today'],
        ];
    }
}
