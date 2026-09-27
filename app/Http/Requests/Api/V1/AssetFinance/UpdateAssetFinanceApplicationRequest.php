<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\AssetFinance;

use App\Enums\RepaymentStatus;
use App\Models\AssetFinanceApplication;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetFinanceApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $application = $this->route('assetFinanceApplication');

        return $application instanceof AssetFinanceApplication && $this->user()?->can('update', $application) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $application = $this->route('assetFinanceApplication');
        $allowedStatuses = $application instanceof AssetFinanceApplication
            ? array_map(static fn ($status): string => $status->value, $application->status->allowedTransitions())
            : [];

        return [
            'status' => ['required', Rule::in($allowedStatuses)],
            'partner_reference' => ['nullable', 'string', 'max:191'],
            'decision_note' => ['nullable', 'string', 'max:2000'],
            'repayment_status' => ['nullable', Rule::in(RepaymentStatus::values())],
            'outstanding_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'next_payment_due_at' => ['nullable', 'date'],
        ];
    }
}
