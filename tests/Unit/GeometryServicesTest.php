<?php

declare(strict_types=1);

use App\Enums\GeometryValidationError;
use App\Exceptions\InvalidGeometryException;
use App\Services\Geometry\CentroidCalculator;
use App\Services\Geometry\GeometryProcessor;

function polygon(array $rings): array
{
    return ['type' => 'Polygon', 'coordinates' => $rings];
}

test('it calculates a one square kilometre parcel at the equator within half a percent', function () {
    $side = 0.0089932;
    $geometry = polygon([[[-$side / 2, -$side / 2], [$side / 2, -$side / 2], [$side / 2, $side / 2], [-$side / 2, $side / 2], [-$side / 2, -$side / 2]]]);

    $processed = app(GeometryProcessor::class)->process($geometry);

    expect($processed->areaHectares)->toBeBetween(99.5, 100.5)
        ->and($processed->centroidLatitude)->toBe(0.0)
        ->and($processed->centroidLongitude)->toBe(0.0);
});

test('it calculates a one square kilometre parcel at sixty degrees north within half a percent', function () {
    $latitudeSide = 0.0089932;
    $longitudeSide = $latitudeSide / cos(deg2rad(60));
    $geometry = polygon([[
        [-$longitudeSide / 2, 60 - $latitudeSide / 2],
        [$longitudeSide / 2, 60 - $latitudeSide / 2],
        [$longitudeSide / 2, 60 + $latitudeSide / 2],
        [-$longitudeSide / 2, 60 + $latitudeSide / 2],
        [-$longitudeSide / 2, 60 - $latitudeSide / 2],
    ]]);

    expect(app(GeometryProcessor::class)->process($geometry)->areaHectares)
        ->toBeBetween(99.5, 100.5);
});

test('it subtracts polygon holes from the computed area', function () {
    $outerSide = 0.0179864;
    $holeSide = 0.0089932;
    $geometry = polygon([
        [[0, 0], [$outerSide, 0], [$outerSide, $outerSide], [0, $outerSide], [0, 0]],
        [[0.004, 0.004], [0.004, 0.004 + $holeSide], [0.004 + $holeSide, 0.004 + $holeSide], [0.004 + $holeSide, 0.004], [0.004, 0.004]],
    ]);

    expect(app(GeometryProcessor::class)->process($geometry)->areaHectares)
        ->toBeBetween(298.5, 301.5);
});

test('it calculates the centroid of a concave L shaped polygon', function () {
    $geometry = polygon([[[0, 0], [2, 0], [2, 1], [1, 1], [1, 2], [0, 2], [0, 0]]]);

    $centroid = app(CentroidCalculator::class)->calculate($geometry);

    expect(round($centroid['latitude'], 7))->toBe(0.8333333)
        ->and(round($centroid['longitude'], 7))->toBe(0.8333333);
});

test('canonicalisation gives equivalent coordinate precision the same hash', function () {
    $first = polygon([[[0, 0], [0.0089831528, 0], [0.0089831528, 0.0089831528], [0, 0.0089831528], [0, 0]]]);
    $second = polygon([[[0.0, 0.0], [0.00898315, 0.0], [0.00898315, 0.00898315], [0.0, 0.00898315], [0.0, 0.0]]]);

    $processor = app(GeometryProcessor::class);

    expect($processor->process($first)->hash)->toBe($processor->process($second)->hash);
});

test('it rejects malformed and unsafe geometries', function (array $geometry, GeometryValidationError $error) {
    try {
        app(GeometryProcessor::class)->process($geometry);
        $this->fail('Expected invalid geometry to be rejected.');
    } catch (InvalidGeometryException $exception) {
        expect($exception->error)->toBe($error);
    }
})->with([
    'wrong type' => [['type' => 'Point', 'coordinates' => [0, 0]], GeometryValidationError::Type],
    'unclosed ring' => [polygon([[[0, 0], [1, 0], [1, 1], [0, 1]]]), GeometryValidationError::NotClosed],
    'too few points' => [polygon([[[0, 0], [1, 0], [0, 0]]]), GeometryValidationError::MinPoints],
    'coordinate outside range' => [polygon([[[181, 0], [181, 1], [180, 1], [181, 0]]]), GeometryValidationError::CoordinateRange],
    'self intersection' => [polygon([[[0, 0], [1, 1], [0, 1], [1, 0], [0, 0]]]), GeometryValidationError::SelfIntersecting],
    'hole outside exterior' => [polygon([
        [[0, 0], [2, 0], [2, 2], [0, 2], [0, 0]],
        [[3, 3], [3, 4], [4, 4], [4, 3], [3, 3]],
    ]), GeometryValidationError::InvalidHole],
    'zero area' => [polygon([[[0, 0], [1, 0], [2, 0], [0, 0]]]), GeometryValidationError::AreaOutOfRange],
]);
