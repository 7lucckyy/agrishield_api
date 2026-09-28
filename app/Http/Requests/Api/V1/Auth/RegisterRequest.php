<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Services\Referral\ReferralCodeResolver;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
    public function rules(ReferralCodeResolver $referralCodeResolver): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['prohibited'],
            'phone' => [
                'required',
                'regex:/^\+[1-9]\d{7,14}$/',
                Rule::unique('users')->whereNull('deleted_at'),
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
            'password_confirmation' => ['required', 'string'],
            'referral_code' => [
                'nullable',
                'string',
                'between:4,32',
                function (string $attribute, mixed $value, \Closure $fail) use ($referralCodeResolver): void {
                    if (is_string($value) && $referralCodeResolver->resolve($value) === null) {
                        $fail('The referral code is invalid or no longer active.');
                    }
                },
            ],
            'locale' => ['nullable', 'string', Rule::in(config('app.supported_locales', ['en']))],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => $this->filled('phone') ? (string) $this->string('phone')->trim() : null,
            'referral_code' => $this->filled('referral_code')
                ? Str::upper($this->string('referral_code')->trim())
                : null,
        ]);
    }
}
