<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DiagnosisStatus;
use App\Models\DiagnosisRequest;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DiagnosisRequest> */
class DiagnosisRequestFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'farm_id' => Farm::factory(),
            'requested_by_user_id' => User::factory(),
            'image_disk' => 'private',
            'image_path' => 'diagnosis/'.fake()->uuid().'.jpg',
            'image_mime' => 'image/jpeg',
            'image_size_bytes' => 1024,
            'image_checksum' => hash('sha256', fake()->unique()->uuid()),
            'status' => DiagnosisStatus::Queued,
        ];
    }
}
