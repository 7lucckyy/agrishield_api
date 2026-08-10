<?php

declare(strict_types=1);

use App\Enums\MetricType;
use App\Integrations\Fake\FakeDataGenerator;
use Carbon\CarbonImmutable;

test('seasonal fake data is deterministic for the same seed and dates', function () {
    $generator = new FakeDataGenerator;
    $since = CarbonImmutable::parse('2026-06-01');
    CarbonImmutable::setTestNow('2026-08-01');

    $first = $generator->seasonalIndexSeries(1337, MetricType::Ndvi, $since, 5, 10, 0.25);
    $second = $generator->seasonalIndexSeries(1337, MetricType::Ndvi, $since, 5, 10, 0.25);

    expect($second)->toEqual($first);
});

test('generated vegetation indices remain in the valid range', function () {
    $generator = new FakeDataGenerator;
    CarbonImmutable::setTestNow('2026-08-01');

    $readings = $generator->seasonalIndexSeries(
        42,
        MetricType::Ndvi,
        CarbonImmutable::parse('2025-08-01'),
        5,
        10,
        0.25,
    );

    expect($readings)->not->toBeEmpty();
    foreach ($readings as $reading) {
        expect($reading->value)->toBeGreaterThanOrEqual(-1.0)->toBeLessThanOrEqual(1.0);
    }
});
