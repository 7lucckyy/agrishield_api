<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Integration;

use App\Enums\IntegrationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class UpdateIntegrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('manageIntegrations');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label' => ['sometimes', 'nullable', 'string', 'max:120'],
            'status' => ['sometimes', Rule::enum(IntegrationStatus::class)],
            'config' => ['sometimes', 'array:timeout,region,enabled_features'],
            'config.timeout' => ['sometimes', 'integer', 'between:1,120'],
            'config.region' => ['sometimes', 'string', 'max:80'],
            'config.enabled_features' => ['sometimes', 'array'],
            'config.enabled_features.*' => ['string', 'max:80'],
        ];
    }
}
