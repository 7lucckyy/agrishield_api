<?php

namespace App\Http\Requests\Web\Platform;

use App\Enums\AgriculturalPartnerType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAgriculturalPartnerRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(AgriculturalPartnerType::class)],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'state' => ['nullable', 'string', 'max:120'],
            'locality' => ['nullable', 'string', 'max:160'],
            'verification_note' => ['required', 'string', 'min:20', 'max:1000'],
        ];
    }
}
