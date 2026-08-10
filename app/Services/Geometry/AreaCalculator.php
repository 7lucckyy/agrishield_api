<?php

declare(strict_types=1);

namespace App\Services\Geometry;

final class AreaCalculator
{
    private const float EARTH_RADIUS_METERS = 6371007.1809;

    /** @param array{type: 'Polygon'|'MultiPolygon', coordinates: array<mixed>} $geometry */
    public function hectares(array $geometry): float
    {
        $polygons = $geometry['type'] === 'Polygon'
            ? [$geometry['coordinates']]
            : $geometry['coordinates'];

        return array_sum(array_map($this->polygonSquareMeters(...), $polygons)) / 10000;
    }

    /** @param array<mixed> $polygon */
    private function polygonSquareMeters(array $polygon): float
    {
        $area = $this->ringSquareMeters($polygon[0]);
        foreach (array_slice($polygon, 1) as $hole) {
            $area -= $this->ringSquareMeters($hole);
        }

        return max(0.0, $area);
    }

    /** @param array<mixed> $ring */
    private function ringSquareMeters(array $ring): float
    {
        $sum = 0.0;
        foreach (array_slice($ring, 0, -1) as $index => $position) {
            $next = $ring[$index + 1];
            $longitudeDelta = deg2rad((float) $next[0] - (float) $position[0]);

            if ($longitudeDelta > M_PI) {
                $longitudeDelta -= 2 * M_PI;
            } elseif ($longitudeDelta < -M_PI) {
                $longitudeDelta += 2 * M_PI;
            }

            $sum += $longitudeDelta
                * (2 + sin(deg2rad((float) $position[1])) + sin(deg2rad((float) $next[1])));
        }

        return abs($sum * self::EARTH_RADIUS_METERS ** 2 / 2);
    }
}
