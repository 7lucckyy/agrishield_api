<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Insight;

use App\Enums\AdvisorySeverity;
use App\Enums\AdvisoryType;
use App\Enums\GlobalRole;
use App\Enums\OrganizationRole;
use App\Models\Farm;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAdvisoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $farm = $this->route('farm');
        $user = $this->user();

        return $farm instanceof Farm && $user !== null && (
            $user->hasRole(GlobalRole::PlatformAdmin->value)
            || ($farm->organization_id !== null && $user->hasOrganizationRole($farm->organization_id, OrganizationRole::Agronomist))
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(AdvisoryType::class)],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'payload' => ['nullable', 'array'],
            'severity' => ['nullable', Rule::enum(AdvisorySeverity::class)],
            'farm_crop_cycle_id' => ['nullable', 'integer', Rule::exists('farm_crop_cycles', 'id')->where('farm_id', $this->route('farm')?->getKey())],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ];
    }
}
