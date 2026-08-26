<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Insight;

use App\Models\Farm;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class AcknowledgeAdvisoryRequest extends FormRequest
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
            'read' => ['required', 'boolean'],
            'acted' => ['required', 'boolean'],
            'feedback' => ['nullable', 'string', 'max:255'],
        ];
    }
}
