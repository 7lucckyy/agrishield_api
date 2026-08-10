<?php

declare(strict_types=1);

use App\Integrations\Support\PayloadScrubber;

test('payload scrubber redacts nested credentials geometry and signed urls', function () {
    $scrubbed = (new PayloadScrubber)->scrub([
        'api_key' => 'secret-value',
        'nested' => ['authorization' => 'Bearer token', 'safe' => 'kept'],
        'boundary_geojson' => ['type' => 'Polygon'],
        'asset' => 'https://example.test/map?X-Amz-Signature=secret',
    ]);

    expect($scrubbed)->toMatchArray([
        'api_key' => '[REDACTED]',
        'nested' => ['authorization' => '[REDACTED]', 'safe' => 'kept'],
        'boundary_geojson' => '[REDACTED]',
        'asset' => '[REDACTED_SIGNED_URL]',
    ]);
});

test('payload scrubber truncates oversized payloads', function () {
    $scrubbed = (new PayloadScrubber)->scrub(['content' => str_repeat('x', 70_000)]);

    expect($scrubbed)->toHaveKey('_truncated', true)
        ->and($scrubbed['_original_bytes'])->toBeGreaterThan(65_536);
});
