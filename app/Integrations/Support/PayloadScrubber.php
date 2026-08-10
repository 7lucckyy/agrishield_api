<?php

declare(strict_types=1);

namespace App\Integrations\Support;

use Illuminate\Support\Str;

final class PayloadScrubber
{
    private const int MAX_BYTES = 65536;

    private const array REDACT_KEYS = [
        'api_key',
        'apikey',
        'token',
        'access_token',
        'refresh_token',
        'password',
        'secret',
        'authorization',
        'signature',
        'client_secret',
        'boundary_geojson',
        'geometry',
    ];

    /**
     * @param  array<array-key, mixed>  $payload
     * @return array<array-key, mixed>
     */
    public function scrub(array $payload): array
    {
        $scrubbed = $this->scrubValues($payload);
        $encoded = json_encode($scrubbed);

        if (is_string($encoded) && mb_strlen($encoded, '8bit') > self::MAX_BYTES) {
            return ['_truncated' => true, '_original_bytes' => mb_strlen($encoded, '8bit')];
        }

        return $scrubbed;
    }

    /**
     * @param  array<array-key, mixed>  $payload
     * @return array<array-key, mixed>
     */
    private function scrubValues(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (in_array(mb_strtolower((string) $key), self::REDACT_KEYS, true)) {
                $payload[$key] = '[REDACTED]';

                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->scrubValues($value);

                continue;
            }

            if (is_string($value) && Str::contains($value, 'X-Amz-Signature', ignoreCase: true)) {
                $payload[$key] = '[REDACTED_SIGNED_URL]';
            }
        }

        return $payload;
    }
}
