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
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

final class CreateDiagnosisRequest
{
    /** @param array{farm_crop_cycle_id?: int|null, note?: string|null, response_language?: string} $data */
    public function execute(Farm $farm, User $user, UploadedFile $upload, array $data, ?string $clientRequestId = null): DiagnosisRequest
    {
        $original = file_get_contents($upload->getRealPath());
        if ($original === false) {
            throw new \RuntimeException('The uploaded image could not be read.');
        }

        $checksum = hash('sha256', $original);
        $responseLanguage = $data['response_language'] ?? (array_key_exists($user->locale, config('diagnosis.response_languages')) ? $user->locale : 'en');
        $path = null;
        try {
            /** @var array{DiagnosisRequest, bool} $result */
            $result = DB::transaction(function () use ($farm, $user, $data, $upload, $original, $checksum, $responseLanguage, $clientRequestId, &$path): array {
                User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

                if ($clientRequestId !== null) {
                    $existing = DiagnosisRequest::query()
                        ->whereBelongsTo($user, 'requestedBy')
                        ->where('client_request_id', $clientRequestId)
                        ->first();
                    if ($existing !== null) {
                        if ($existing->farm_id !== $farm->getKey() || $existing->image_checksum !== $checksum
                            || $existing->farm_crop_cycle_id !== ($data['farm_crop_cycle_id'] ?? null)
                            || $existing->note !== ($data['note'] ?? null)
                            || $existing->response_language !== $responseLanguage) {
                            throw new ConflictHttpException('The Idempotency-Key belongs to a different diagnosis request.');
                        }

                        return [$existing, false];
                    }
                } else {
                    $existing = DiagnosisRequest::query()
                        ->whereBelongsTo($farm)
                        ->whereBelongsTo($user, 'requestedBy')
                        ->where('image_checksum', $checksum)
                        ->where('response_language', $responseLanguage)
                        ->first();
                    if ($existing !== null) {
                        return [$existing, false];
                    }
                }

                [$contents, $extension, $mime] = $this->sanitise($original, (string) $upload->getMimeType());
                $path = 'diagnosis/'.$farm->uuid.'/'.Str::ulid().'.'.$extension;
                if (! Storage::disk('private')->put($path, $contents, ['visibility' => 'private'])) {
                    throw new \RuntimeException('The uploaded image could not be stored.');
                }

                $diagnosis = new DiagnosisRequest;
                $diagnosis->fill([
                    'uuid' => (string) Str::uuid(),
                    'client_request_id' => $clientRequestId,
                    'farm_crop_cycle_id' => $data['farm_crop_cycle_id'] ?? null,
                    'image_disk' => 'private',
                    'image_path' => $path,
                    'image_mime' => $mime,
                    'image_size_bytes' => strlen($contents),
                    'image_checksum' => $checksum,
                    'note' => $data['note'] ?? null,
                    'response_language' => $responseLanguage,
                ]);
                $diagnosis->farm()->associate($farm);
                $diagnosis->requestedBy()->associate($user);
                $diagnosis->save();

                return [$diagnosis, true];
            });
        } catch (Throwable $exception) {
            if ($path !== null) {
                Storage::disk('private')->delete($path);
            }

            throw $exception;
        }

        [$diagnosis, $created] = $result;
        if ($created) {
            SubmitDiagnosisToProvider::dispatch($diagnosis->getKey())->afterCommit();
        }

        return $diagnosis;
    }

    /** @return array{string, string, string} */
    private function sanitise(string $contents, string $mime): array
    {
        $format = match ($mime) {
            'image/jpeg' => 'jpeg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/heic', 'image/heif' => 'jpeg',
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

        return [
            $sanitised,
            $format === 'jpeg' ? 'jpg' : $format,
            $format === 'jpeg' ? 'image/jpeg' : 'image/'.$format,
        ];
    }
}
