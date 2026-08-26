<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Insight;

use App\Enums\MetricType;
use App\Enums\QualityFlag;
use App\Models\Farm;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListSatelliteObservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $farm = $this->route('farm');

        return $farm instanceof Farm && $this->user()?->can('view', $farm) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'filter' => ['sometimes', 'array:metric_type,from,to,quality,crop_cycle_id'],
            'filter.metric_type' => ['sometimes', 'string'],
            'filter.from' => ['sometimes', 'date'],
            'filter.to' => ['sometimes', 'date', 'after_or_equal:filter.from'],
            'filter.quality' => ['sometimes', Rule::enum(QualityFlag::class)],
            'filter.crop_cycle_id' => ['sometimes', 'integer'],
            'latest_only' => ['sometimes', 'boolean'],
            'sort' => ['sometimes', Rule::in(['captured_at', '-captured_at'])],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /** @return list<string> */
    public function metricTypes(): array
    {
        $value = $this->validated('filter.metric_type');
        if (! is_string($value) || $value === '') {
            return [];
        }

        return array_values(array_filter(explode(',', $value), static fn (string $metric): bool => MetricType::tryFrom($metric) !== null));
    }
}
