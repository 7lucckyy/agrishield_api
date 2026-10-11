<?php

namespace App\Http\Requests\Api\V1\Ledger;

use App\Enums\FarmLedgerEntryType;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\VoiceAssistanceRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreFarmLedgerEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $farm = $this->route('farm');

        return $farm instanceof Farm && $farm->owner_user_id === $this->user()?->getKey();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'entry_type' => ['required', Rule::enum(FarmLedgerEntryType::class)],
            'category' => ['required', 'string', 'max:80'],
            'occurred_on' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999.999'],
            'unit' => ['nullable', 'string', 'max:80'],
            'unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'payment_method' => ['nullable', 'string', 'max:80'],
            'counterparty' => ['nullable', 'string', 'max:160'],
            'source' => ['sometimes', Rule::in(['manual', 'voice'])],
            'voice_transcript' => ['nullable', 'string', 'max:4000'],
            'voice_request_id' => ['nullable', 'required_if:source,voice', 'uuid', Rule::exists((new VoiceAssistanceRequest)->getTable(), 'uuid')->where('user_id', $this->user()?->getKey())],
            'farm_crop_cycle_id' => ['nullable', 'integer', Rule::exists((new CropCycle)->getTable(), 'id')],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
