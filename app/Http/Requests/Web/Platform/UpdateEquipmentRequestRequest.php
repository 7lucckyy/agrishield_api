<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Platform;

use App\Enums\AssetFinanceStatus;
use App\Enums\FinancePartnerType;
use App\Models\AssetFinanceApplication;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateEquipmentRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $application = $this->route('assetFinanceApplication');

        return $this->user()?->can('manageEquipment') === true
            && $application instanceof AssetFinanceApplication
            && $application->organization_id === null
            && $application->product->financePartner->type === FinancePartnerType::EquipmentDesk;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $application = $this->route('assetFinanceApplication');
        $transitions = $application instanceof AssetFinanceApplication ? $application->status->allowedTransitions() : [];
        $allowed = array_filter($transitions, static fn (AssetFinanceStatus $status): bool => in_array($status, [
            AssetFinanceStatus::UnderReview,
            AssetFinanceStatus::Approved,
            AssetFinanceStatus::Declined,
            AssetFinanceStatus::AssetOrdered,
            AssetFinanceStatus::Delivered,
        ], true));

        return [
            'status' => ['required', Rule::in(array_map(static fn (AssetFinanceStatus $status): string => $status->value, $allowed))],
            'decision_note' => ['required', 'string', 'min:20', 'max:2000'],
            'delivery_confirmed' => $this->input('status') === AssetFinanceStatus::Delivered->value
                ? ['required', 'accepted']
                : ['sometimes', 'accepted'],
        ];
    }
}
