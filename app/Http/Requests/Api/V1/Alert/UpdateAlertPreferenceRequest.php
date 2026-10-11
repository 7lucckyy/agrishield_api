<?php

namespace App\Http\Requests\Api\V1\Alert;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAlertPreferenceRequest extends FormRequest
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
            'in_app_enabled' => ['required', 'boolean'],
            'push_enabled' => ['required', 'boolean'],
            'sms_enabled' => ['required', 'boolean'],
            'whatsapp_enabled' => ['required', 'boolean'],
            'voice_call_enabled' => ['required', 'boolean'],
            'phone' => ['nullable', 'required_if:sms_enabled,true', 'string', 'max:32'],
            'whatsapp_phone' => ['nullable', 'required_if:whatsapp_enabled,true', 'string', 'max:32'],
            'language' => ['required', Rule::in(['en', 'ha', 'yo', 'ig', 'pcm'])],
            'minimum_severity' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'consent' => ['accepted_if:sms_enabled,true', 'accepted_if:whatsapp_enabled,true', 'accepted_if:voice_call_enabled,true'],
        ];
    }
}
