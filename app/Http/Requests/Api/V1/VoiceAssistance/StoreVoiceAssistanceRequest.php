<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\VoiceAssistance;

use App\Enums\DiagnosisStatus;
use App\Models\DiagnosisRequest;
use App\Models\Farm;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreVoiceAssistanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->filled('farm_id')) {
            $farm = Farm::query()->where('uuid', $this->string('farm_id')->toString())->first();
            if ($farm === null || $this->user()?->can('view', $farm) !== true) {
                return false;
            }
        }

        if ($this->filled('diagnosis_id')) {
            $diagnosis = DiagnosisRequest::query()->where('uuid', $this->string('diagnosis_id')->toString())->first();

            return $diagnosis !== null
                && $this->user()?->can('view', $diagnosis->farm) === true
                && $diagnosis->farm->uuid === $this->string('farm_id')->toString();
        }

        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'audio' => ['required', 'file', 'max:20480', 'mimetypes:audio/mpeg,audio/mp4,audio/x-m4a,audio/wav,audio/x-wav,audio/ogg,audio/webm,video/webm'],
            'source_language' => ['required', 'string', Rule::in(array_keys(config('voice-assistance.languages')))],
            'response_language' => ['required', 'string', Rule::in(array_keys(config('voice-assistance.response_languages')))],
            'farm_id' => ['nullable', 'uuid', 'exists:farms,uuid'],
            'purpose' => ['sometimes', 'string', Rule::in(['general', 'agronomy', 'diagnosis_followup'])],
            'diagnosis_id' => ['required_if:purpose,diagnosis_followup', 'nullable', 'uuid', 'exists:diagnosis_requests,uuid'],
        ];
    }

    /** @return array<callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('purpose', 'general') !== 'general' && ! $this->filled('farm_id')) {
                $validator->errors()->add('farm_id', 'A farm is required for this voice question.');
            }
            if ($this->filled('diagnosis_id') && $this->input('purpose') !== 'diagnosis_followup') {
                $validator->errors()->add('diagnosis_id', 'A diagnosis can only be attached to a follow-up question.');
            }
            if ($this->input('purpose') === 'diagnosis_followup' && $this->filled('diagnosis_id')) {
                $diagnosis = DiagnosisRequest::query()->where('uuid', $this->string('diagnosis_id')->toString())->first();
                if ($diagnosis !== null && $diagnosis->status !== DiagnosisStatus::Completed) {
                    $validator->errors()->add('diagnosis_id', 'Wait until the crop photo analysis is complete before asking a follow-up.');
                }
            }
        }];
    }
}
