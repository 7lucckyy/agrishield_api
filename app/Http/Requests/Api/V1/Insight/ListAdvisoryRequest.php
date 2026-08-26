<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Insight;

use App\Models\Farm;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class ListAdvisoryRequest extends FormRequest
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
            'filter' => ['sometimes', 'array:type,severity,crop_cycle_id,active,unread,from,to'],
            'filter.type' => ['sometimes', 'string'],
            'filter.severity' => ['sometimes', 'string'],
            'filter.crop_cycle_id' => ['sometimes', 'integer'],
            'filter.active' => ['sometimes', 'boolean'],
            'filter.unread' => ['sometimes', 'boolean'],
            'filter.from' => ['sometimes', 'date'],
            'filter.to' => ['sometimes', 'date', 'after_or_equal:filter.from'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
