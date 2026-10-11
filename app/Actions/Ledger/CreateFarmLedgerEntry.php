<?php

declare(strict_types=1);

namespace App\Actions\Ledger;

use App\Enums\VoiceAssistanceStatus;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\FarmLedgerEntry;
use App\Models\User;
use App\Models\VoiceAssistanceRequest;
use Illuminate\Validation\ValidationException;

final class CreateFarmLedgerEntry
{
    /** @param array<string, mixed> $data */
    public function execute(Farm $farm, User $user, array $data): FarmLedgerEntry
    {
        $cropCycleId = $data['farm_crop_cycle_id'] ?? null;
        if ($cropCycleId !== null && ! CropCycle::query()->whereKey($cropCycleId)->whereBelongsTo($farm)->exists()) {
            throw ValidationException::withMessages(['farm_crop_cycle_id' => 'Select a crop season attached to this farm.']);
        }

        $voiceRequestUuid = $data['voice_request_id'] ?? null;
        $voiceRequest = $voiceRequestUuid === null ? null : VoiceAssistanceRequest::query()
            ->where('uuid', $voiceRequestUuid)
            ->whereBelongsTo($user)
            ->whereBelongsTo($farm)
            ->where('purpose', 'ledger')
            ->where('status', VoiceAssistanceStatus::Completed)
            ->first();
        if ($voiceRequestUuid !== null && $voiceRequest === null) {
            throw ValidationException::withMessages(['voice_request_id' => 'Select a completed money-book voice note for this farm.']);
        }
        if (($data['source'] ?? 'manual') === 'voice' && $voiceRequest === null) {
            throw ValidationException::withMessages(['voice_request_id' => 'A completed money-book voice note is required.']);
        }

        $data['voice_transcript'] = $voiceRequest?->transcript;

        $entry = new FarmLedgerEntry($data);
        $entry->farm()->associate($farm);
        $entry->user()->associate($user);
        $entry->farm_crop_cycle_id = $cropCycleId;
        $entry->voice_assistance_request_id = $voiceRequest?->getKey();
        $entry->save();

        return $entry;
    }
}
