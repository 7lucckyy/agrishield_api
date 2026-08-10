<?php

declare(strict_types=1);

namespace App\Services\Geometry;

use RuntimeException;

final class CentroidCalculator
{
    /**
     * @param  array{type: 'Polygon'|'MultiPolygon', coordinates: array<mixed>}  $geometry
     * @return array{latitude: float, longitude: float}
     */
    public function calculate(array $geometry): array
    {
        $polygons = $geometry['type'] === 'Polygon'
            ? [$geometry['coordinates']]
            : $geometry['coordinates'];

        $weightedLongitude = 0.0;
        $weightedLatitude = 0.0;
        $totalArea = 0.0;

        foreach ($polygons as $polygon) {
            foreach ($polygon as $ring) {
                $centroid = $this->ringCentroid($ring);
                $weightedLongitude += $centroid['longitude'] * $centroid['signed_area'];
                $weightedLatitude += $centroid['latitude'] * $centroid['signed_area'];
                $totalArea += $centroid['signed_area'];
            }
        }

        if (abs($totalArea) < 1e-15) {
            throw new RuntimeException('Cannot calculate a centroid for zero-area geometry.');
        }

        return [
            'latitude' => $weightedLatitude / $totalArea,
            'longitude' => $weightedLongitude / $totalArea,
        ];
    }

    /**
     * @param  array<mixed>  $ring
     * @return array{latitude: float, longitude: float, signed_area: float}
     */
    private function ringCentroid(array $ring): array
    {
        $crossProductSum = 0.0;
        $longitudeSum = 0.0;
        $latitudeSum = 0.0;

        foreach (array_slice($ring, 0, -1) as $index => $position) {
            $next = $ring[$index + 1];
            $crossProduct = ($position[0] * $next[1]) - ($next[0] * $position[1]);
            $crossProductSum += $crossProduct;
            $longitudeSum += ($position[0] + $next[0]) * $crossProduct;
            $latitudeSum += ($position[1] + $next[1]) * $crossProduct;
        }

        $signedArea = $crossProductSum / 2;

        return [
            'longitude' => $longitudeSum / (6 * $signedArea),
            'latitude' => $latitudeSum / (6 * $signedArea),
            'signed_area' => $signedArea,
        ];
    }
}
