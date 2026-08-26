<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Organization;

use App\Enums\GlobalRole;
use App\Enums\OrganizationStatus;
use App\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateOrganizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $organization = $this->route('organization');

        return $organization instanceof Organization && $this->user()?->can('update', $organization) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'status' => [Rule::when($this->user()?->hasRole(GlobalRole::PlatformAdmin->value) === true, ['sometimes', Rule::enum(OrganizationStatus::class)], ['prohibited'])],
            'contact_email' => ['nullable', 'email:rfc', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'size:2'],
        ];
    }
}
