<?php

namespace Database\Factories;

use App\Enums\VoiceAssistanceStatus;
use App\Models\Farm;
use App\Models\User;
use App\Models\VoiceAssistanceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VoiceAssistanceRequest>
 */
class VoiceAssistanceRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'organization_id' => null,
            'farm_id' => null,
            'source_language' => 'ha',
            'response_language' => 'en',
            'audio_disk' => 'private',
            'audio_path' => 'voice-assistance/'.Str::ulid().'.webm',
            'audio_mime' => 'audio/webm',
            'audio_size_bytes' => 1024,
            'audio_checksum' => hash('sha256', fake()->uuid()),
            'status' => VoiceAssistanceStatus::Completed,
            'transcript' => 'The maize leaves are turning yellow.',
            'translated_transcript' => 'The maize leaves are turning yellow.',
            'guidance' => 'Inspect the newest and oldest leaves, then check soil moisture before applying inputs.',
            'safety_note' => 'Confirm treatment and dosage with a qualified extension officer.',
            'provider' => 'fake',
            'completed_at' => now(),
        ];
    }

    public function forFarm(Farm $farm): static
    {
        return $this->state(fn (): array => ['farm_id' => $farm->getKey()]);
    }
}
