<?php

declare(strict_types=1);

namespace App\Services\Geometry;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class FarmSectionContainment
{
    private const float EPSILON = 0.0000000001;

    /**
     * @param  array{type: string, coordinates: array<mixed>}  $farmBoundary
     * @param  array{type: string, coordinates: array<mixed>}  $sectionBoundary
     */
    public function covers(array $farmBoundary, array $sectionBoundary): bool
    {
        if (DB::getDriverName() === 'pgsql' && Schema::hasColumn('farm_sections', 'boundary')) {
            return (bool) DB::scalar(
                'SELECT ST_Covers(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326), ST_SetSRID(ST_GeomFromGeoJSON(?), 4326))',
                [json_encode($farmBoundary, JSON_THROW_ON_ERROR), json_encode($sectionBoundary, JSON_THROW_ON_ERROR)],
            );
        }

        $farmPolygons = $this->polygons($farmBoundary);
        $sectionPolygons = $this->polygons($sectionBoundary);
        $farmRings = [];

        foreach ($farmPolygons as $polygon) {
            array_push($farmRings, ...$polygon);
        }

        foreach ($sectionPolygons as $polygon) {
            foreach ($polygon as $ring) {
                for ($index = 0; $index < count($ring) - 1; $index++) {
                    if (! $this->segmentCovered($ring[$index], $ring[$index + 1], $farmRings, $farmPolygons)) {
                        return false;
                    }
                }
            }
        }

        foreach ($farmPolygons as $polygon) {
            foreach (array_slice($polygon, 1) as $hole) {
                foreach (array_slice($hole, 0, -1) as $vertex) {
                    if ($this->coveredByPolygons($vertex, $sectionPolygons, false)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /** @param array{type: string, coordinates: array<mixed>} $geometry
     * @return array<mixed>
     */
    private function polygons(array $geometry): array
    {
        return $geometry['type'] === 'Polygon' ? [$geometry['coordinates']] : $geometry['coordinates'];
    }

    /** @param array<mixed> $start
     * @param  array<mixed>  $end
     * @param  array<mixed>  $farmRings
     * @param  array<mixed>  $farmPolygons
     */
    private function segmentCovered(array $start, array $end, array $farmRings, array $farmPolygons): bool
    {
        $breakpoints = [0.0, 1.0];

        foreach ($farmRings as $ring) {
            for ($index = 0; $index < count($ring) - 1; $index++) {
                $this->addIntersectionParameters($start, $end, $ring[$index], $ring[$index + 1], $breakpoints);
            }
        }

        sort($breakpoints);
        $breakpoints = array_values(array_unique($breakpoints, SORT_REGULAR));

        foreach ($breakpoints as $index => $parameter) {
            if (! $this->coveredByPolygons($this->interpolate($start, $end, $parameter), $farmPolygons)) {
                return false;
            }

            if (isset($breakpoints[$index + 1])) {
                $midpoint = ($parameter + $breakpoints[$index + 1]) / 2;
                if (! $this->coveredByPolygons($this->interpolate($start, $end, $midpoint), $farmPolygons)) {
                    return false;
                }
            }
        }

        return true;
    }

    /** @param array<mixed> $start
     * @param  array<mixed>  $end
     * @param  array<mixed>  $otherStart
     * @param  array<mixed>  $otherEnd
     * @param  array<float>  $breakpoints
     */
    private function addIntersectionParameters(array $start, array $end, array $otherStart, array $otherEnd, array &$breakpoints): void
    {
        $dx = $end[0] - $start[0];
        $dy = $end[1] - $start[1];
        $otherDx = $otherEnd[0] - $otherStart[0];
        $otherDy = $otherEnd[1] - $otherStart[1];
        $denominator = $dx * $otherDy - $dy * $otherDx;

        if (abs($denominator) < self::EPSILON) {
            if (abs(($otherStart[0] - $start[0]) * $dy - ($otherStart[1] - $start[1]) * $dx) < self::EPSILON) {
                $lengthSquared = $dx * $dx + $dy * $dy;
                if ($lengthSquared > 0) {
                    foreach ([$otherStart, $otherEnd] as $point) {
                        $parameter = fdiv((($point[0] - $start[0]) * $dx + ($point[1] - $start[1]) * $dy), $lengthSquared);
                        if ($parameter > 0 && $parameter < 1) {
                            $breakpoints[] = $parameter;
                        }
                    }
                }
            }

            return;
        }

        $offsetX = $otherStart[0] - $start[0];
        $offsetY = $otherStart[1] - $start[1];
        $parameter = fdiv($offsetX * $otherDy - $offsetY * $otherDx, $denominator);
        $otherParameter = fdiv($offsetX * $dy - $offsetY * $dx, $denominator);

        if ($parameter >= 0 && $parameter <= 1 && $otherParameter >= 0 && $otherParameter <= 1) {
            $breakpoints[] = $parameter;
        }
    }

    /** @param array<mixed> $start
     * @param  array<mixed>  $end
     * @return array{float, float}
     */
    private function interpolate(array $start, array $end, float $parameter): array
    {
        return [
            $start[0] + ($end[0] - $start[0]) * $parameter,
            $start[1] + ($end[1] - $start[1]) * $parameter,
        ];
    }

    /** @param array<mixed> $point
     * @param  array<mixed>  $polygons
     */
    private function coveredByPolygons(array $point, array $polygons, bool $includeBoundary = true): bool
    {
        foreach ($polygons as $polygon) {
            if (! $includeBoundary) {
                foreach ($polygon as $ring) {
                    if ($this->pointOnRing($point, $ring)) {
                        continue 2;
                    }
                }
            }

            if (! $this->pointInRing($point, $polygon[0], $includeBoundary)) {
                continue;
            }

            $insideHole = false;
            foreach (array_slice($polygon, 1) as $hole) {
                if ($this->pointInRing($point, $hole, false)) {
                    $insideHole = true;
                    break;
                }
            }

            if (! $insideHole) {
                return true;
            }
        }

        return false;
    }

    /** @param array<mixed> $point
     * @param  array<mixed>  $ring
     */
    private function pointInRing(array $point, array $ring, bool $includeBoundary): bool
    {
        if ($this->pointOnRing($point, $ring)) {
            return $includeBoundary;
        }

        $inside = false;
        for ($index = 0, $previous = count($ring) - 2; $index < count($ring) - 1; $previous = $index++) {
            $start = $ring[$previous];
            $end = $ring[$index];
            if (($start[1] > $point[1]) !== ($end[1] > $point[1])
                && $point[0] < ($end[0] - $start[0]) * ($point[1] - $start[1]) / ($end[1] - $start[1]) + $start[0]) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /** @param array<mixed> $point
     * @param  array<mixed>  $ring
     */
    private function pointOnRing(array $point, array $ring): bool
    {
        for ($index = 0; $index < count($ring) - 1; $index++) {
            $start = $ring[$index];
            $end = $ring[$index + 1];
            $cross = ($point[0] - $start[0]) * ($end[1] - $start[1])
                - ($point[1] - $start[1]) * ($end[0] - $start[0]);

            if (abs($cross) < self::EPSILON
                && $point[0] >= min($start[0], $end[0]) - self::EPSILON
                && $point[0] <= max($start[0], $end[0]) + self::EPSILON
                && $point[1] >= min($start[1], $end[1]) - self::EPSILON
                && $point[1] <= max($start[1], $end[1]) + self::EPSILON) {
                return true;
            }
        }

        return false;
    }
}
