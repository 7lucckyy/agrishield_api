<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:255'],
            'phone' => [
                'sometimes',
                'nullable',
                'regex:/^\+[1-9]\d{7,14}$/',
                Rule::unique('users')
                    ->ignore($this->user())
                    ->whereNull('deleted_at'),
            ],
            'locale' => ['sometimes', 'string', Rule::in(config('app.supported_locales', ['en']))],
        ];
    }
}
