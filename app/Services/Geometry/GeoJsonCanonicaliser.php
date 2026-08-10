<?php

declare(strict_types=1);

namespace App\Services\Geometry;

final class GeoJsonCanonicaliser
{
    private const int COORDINATE_PRECISION = 7;

    /**
     * @param  array{type: 'Polygon'|'MultiPolygon', coordinates: array<mixed>}  $geometry
     * @return array{type: 'Polygon'|'MultiPolygon', coordinates: array<mixed>}
     */
    public function canonicalise(array $geometry): array
    {
        $polygons = $geometry['type'] === 'Polygon'
            ? [$geometry['coordinates']]
            : $geometry['coordinates'];

        $canonicalPolygons = array_map($this->canonicalisePolygon(...), $polygons);

        if ($geometry['type'] === 'MultiPolygon') {
            usort($canonicalPolygons, fn (array $left, array $right): int => $this->encoded($left) <=> $this->encoded($right));
        }

        return [
            'type' => $geometry['type'],
            'coordinates' => $geometry['type'] === 'Polygon'
                ? $canonicalPolygons[0]
                : $canonicalPolygons,
        ];
    }

    /**
     * @param  array<mixed>  $polygon
     * @return array<mixed>
     */
    private function canonicalisePolygon(array $polygon): array
    {
        $exterior = $this->canonicaliseRing($polygon[0], clockwise: false);
        $holes = array_map(
            fn (array $ring): array => $this->canonicaliseRing($ring, clockwise: true),
            array_slice($polygon, 1),
        );
        usort($holes, fn (array $left, array $right): int => $this->encoded($left) <=> $this->encoded($right));

        return [$exterior, ...$holes];
    }

    /**
     * @param  array<mixed>  $ring
     * @return array<int, array{0: float, 1: float}>
     */
    private function canonicaliseRing(array $ring, bool $clockwise): array
    {
        /** @var array<int, array{0: float, 1: float}> $positions */
        $positions = array_map(fn (array $position): array => [
            $this->normaliseCoordinate((float) $position[0]),
            $this->normaliseCoordinate((float) $position[1]),
        ], array_slice($ring, 0, -1));

        $minimumIndex = 0;
        foreach ($positions as $index => $position) {
            if ($position < $positions[$minimumIndex]) {
                $minimumIndex = $index;
            }
        }

        $positions = [
            ...array_slice($positions, $minimumIndex),
            ...array_slice($positions, 0, $minimumIndex),
        ];
        $positions[] = $positions[0];

        if (($this->signedArea($positions) < 0) !== $clockwise) {
            $positions = array_reverse($positions);
        }

        return $positions;
    }

    private function normaliseCoordinate(float $coordinate): float
    {
        $rounded = round($coordinate, self::COORDINATE_PRECISION);

        return abs($rounded) < 10 ** -self::COORDINATE_PRECISION ? 0.0 : $rounded;
    }

    /** @param array<int, array{0: float, 1: float}> $ring */
    private function signedArea(array $ring): float
    {
        $twiceArea = 0.0;
        foreach (array_slice($ring, 0, -1) as $index => $position) {
            $next = $ring[$index + 1];
            $twiceArea += ($position[0] * $next[1]) - ($next[0] * $position[1]);
        }

        return $twiceArea / 2;
    }

    /** @param array<mixed> $value */
    private function encoded(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
    }
}
