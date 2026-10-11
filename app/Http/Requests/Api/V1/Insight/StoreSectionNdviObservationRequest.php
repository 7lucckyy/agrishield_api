<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Insight;

use App\Enums\GlobalRole;
use App\Models\Farm;
use App\Models\FarmSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreSectionNdviObservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $farm = $this->route('farm');
        $section = $this->route('farmSection');

        return $farm instanceof Farm && $section instanceof FarmSection
            && $section->farm_id === $farm->getKey()
            && $this->user()?->hasRole(GlobalRole::PlatformAdmin->value) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'value' => ['required', 'numeric', 'between:-1,1'],
            'captured_at' => ['required', 'date', 'before_or_equal:now', 'after_or_equal:'.now()->subDays(12)->toDateTimeString()],
            'quality_flag' => ['required', Rule::in(['good'])],
            'source' => ['required', 'string', Rule::in(['sentinel2', 'landsat8', 'landsat9', 'satyukt'])],
            'external_reference' => ['required', 'string', 'max:191', Rule::unique('satellite_observations')->where('source', $this->input('source'))],
            'resolution_meters' => ['required', 'integer', 'between:1,100'],
        ];
    }
}
