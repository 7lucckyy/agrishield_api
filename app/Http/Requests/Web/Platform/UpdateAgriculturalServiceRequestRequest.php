<?php

namespace App\Http\Requests\Web\Platform;

use App\Enums\AgriculturalServiceRequestStatus;
use App\Models\AgriculturalServiceRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAgriculturalServiceRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('accessPlatform') === true
            && $this->route('agriculturalServiceRequest') instanceof AgriculturalServiceRequest;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var AgriculturalServiceRequest $serviceRequest */
        $serviceRequest = $this->route('agriculturalServiceRequest');
        $allowedStatuses = array_map(
            static fn (AgriculturalServiceRequestStatus $status): string => $status->value,
            $serviceRequest->status->allowedTransitions(),
        );

        return [
            'status' => ['required', Rule::in($allowedStatuses)],
            'decision_note' => ['required', 'string', 'min:20', 'max:2000'],
        ];
    }
}
