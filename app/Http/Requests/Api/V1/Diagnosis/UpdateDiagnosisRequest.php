<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Diagnosis;

use App\Enums\DiagnosisStatus;
use App\Enums\GlobalRole;
use App\Enums\OrganizationRole;
use App\Models\DiagnosisRequest;
use App\Models\Farm;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateDiagnosisRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $farm = $this->route('farm');
        $diagnosis = $this->route('diagnosis');
        $user = $this->user();

        return $farm instanceof Farm
            && $diagnosis instanceof DiagnosisRequest
            && $diagnosis->status === DiagnosisStatus::Completed
            && $user !== null
            && ($user->hasRole(GlobalRole::PlatformAdmin->value)
                || ($farm->organization_id !== null && $user->hasOrganizationRole($farm->organization_id, OrganizationRole::Agronomist)));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'diagnosis' => ['required', 'string', 'max:4000'],
            'recommendation' => ['required', 'string', 'max:8000'],
            'confidence' => ['required', 'numeric', 'between:0,1'],
        ];
    }
}
