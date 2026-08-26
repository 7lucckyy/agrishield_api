<?php

declare(strict_types=1);

namespace App\Actions\Diagnosis;

use App\Jobs\SubmitDiagnosisToProvider;
use App\Models\DiagnosisRequest;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;
use Throwable;

final class CreateDiagnosisRequest
{
    /** @param array{farm_crop_cycle_id?: int|null, note?: string|null} $data */
    public function execute(Farm $farm, User $user, UploadedFile $upload, array $data): DiagnosisRequest
    {
        $original = file_get_contents($upload->getRealPath());
        if ($original === false) {
            throw new \RuntimeException('The uploaded image could not be read.');
        }

        $checksum = hash('sha256', $original);
        $existing = DiagnosisRequest::query()->whereBelongsTo($farm)->where('image_checksum', $checksum)->first();
        if ($existing !== null) {
            return $existing;
        }

        [$contents, $extension] = $this->sanitise($original, (string) $upload->getMimeType());
        $path = 'diagnosis/'.$farm->uuid.'/'.Str::ulid().'.'.$extension;
        Storage::disk('private')->put($path, $contents, ['visibility' => 'private']);

        try {
            $diagnosis = DB::transaction(function () use ($farm, $user, $data, $upload, $checksum, $path, $contents): DiagnosisRequest {
                $diagnosis = new DiagnosisRequest;
                $diagnosis->fill([
                    'uuid' => (string) Str::uuid(),
                    'farm_crop_cycle_id' => $data['farm_crop_cycle_id'] ?? null,
                    'image_disk' => 'private',
                    'image_path' => $path,
                    'image_mime' => $upload->getMimeType(),
                    'image_size_bytes' => strlen($contents),
                    'image_checksum' => $checksum,
                    'note' => $data['note'] ?? null,
                ]);
                $diagnosis->farm()->associate($farm);
                $diagnosis->requestedBy()->associate($user);
                $diagnosis->save();

                return $diagnosis;
            });
        } catch (Throwable $exception) {
            Storage::disk('private')->delete($path);

            throw $exception;
        }

        SubmitDiagnosisToProvider::dispatch($diagnosis->getKey())->afterCommit();

        return $diagnosis;
    }

    /** @return array{string, string} */
    private function sanitise(string $contents, string $mime): array
    {
        $format = match ($mime) {
            'image/jpeg' => 'jpeg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/heic', 'image/heif' => 'heic',
            default => throw new \InvalidArgumentException('Unsupported image type.'),
        };

        $image = new Imagick;
        $image->readImageBlob($contents);
        $image->setIteratorIndex(0);
        $image->autoOrient();
        $image->stripImage();
        $image->setImageFormat($format);
        $sanitised = $image->getImageBlob();
        $image->clear();

        return [$sanitised, $format === 'jpeg' ? 'jpg' : $format];
    }
}
