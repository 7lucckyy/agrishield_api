<?php

declare(strict_types=1);

namespace App\Services\Geometry;

use App\Enums\GeometryValidationError;
use App\Exceptions\InvalidGeometryException;

final class GeometryValidator
{
    private const int MAX_RINGS = 20;

    private const int MAX_POSITIONS_PER_RING = 1000;

    /** @return array{type: 'Polygon'|'MultiPolygon', coordinates: array<mixed>} */
    public function validate(mixed $geometry): array
    {
        if (! is_array($geometry)
            || ! isset($geometry['type'], $geometry['coordinates'])
            || ! in_array($geometry['type'], ['Polygon', 'MultiPolygon'], true)
            || ! is_array($geometry['coordinates'])) {
            $this->fail(GeometryValidationError::Type);
        }

        /** @var 'Polygon'|'MultiPolygon' $type */
        $type = $geometry['type'];
        $polygons = $type === 'Polygon' ? [$geometry['coordinates']] : $geometry['coordinates'];

        if ($polygons === []) {
            $this->fail(GeometryValidationError::MinPoints);
        }

        $ringCount = 0;
        foreach ($polygons as $polygon) {
            if (! is_array($polygon) || $polygon === []) {
                $this->fail(GeometryValidationError::MinPoints);
            }

            $ringCount += count($polygon);
            if ($ringCount > self::MAX_RINGS) {
                $this->fail(GeometryValidationError::TooComplex);
            }

            foreach ($polygon as $ringIndex => $ring) {
                $this->validateRing($ring, $ringIndex === 0);
            }

            $this->validateHoles($polygon);
        }

        return ['type' => $type, 'coordinates' => $geometry['coordinates']];
    }

    private function validateRing(mixed $ring, bool $exterior): void
    {
        if (! is_array($ring) || count($ring) < 4) {
            $this->fail(GeometryValidationError::MinPoints);
        }

        if (count($ring) > self::MAX_POSITIONS_PER_RING) {
            $this->fail(GeometryValidationError::TooComplex);
        }

        foreach ($ring as $position) {
            if (! is_array($position)
                || count($position) !== 2
                || ! is_numeric($position[0])
                || ! is_numeric($position[1])) {
                $this->fail(GeometryValidationError::CoordinateRange);
            }

            $longitude = (float) $position[0];
            $latitude = (float) $position[1];
            if (! is_finite($longitude)
                || ! is_finite($latitude)
                || $longitude < -180
                || $longitude > 180
                || $latitude < -90
                || $latitude > 90) {
                $this->fail(GeometryValidationError::CoordinateRange);
            }
        }

        if ($ring[0] !== $ring[array_key_last($ring)]) {
            $this->fail(GeometryValidationError::NotClosed);
        }

        if ($this->ringSelfIntersects($ring)) {
            $this->fail($exterior
                ? GeometryValidationError::SelfIntersecting
                : GeometryValidationError::InvalidHole);
        }
    }

    /** @param array<mixed> $polygon */
    private function validateHoles(array $polygon): void
    {
        if (count($polygon) === 1) {
            return;
        }

        $exterior = $polygon[0];
        foreach (array_slice($polygon, 1) as $hole) {
            foreach (array_slice($hole, 0, -1) as $position) {
                if (! $this->pointIsStrictlyInsideRing($position, $exterior)) {
                    $this->fail(GeometryValidationError::InvalidHole);
                }
            }

            if ($this->ringsIntersect($hole, $exterior)) {
                $this->fail(GeometryValidationError::InvalidHole);
            }
        }
    }

    /** @param array<mixed> $ring */
    private function ringSelfIntersects(array $ring): bool
    {
        $segmentCount = count($ring) - 1;
        for ($first = 0; $first < $segmentCount; $first++) {
            for ($second = $first + 1; $second < $segmentCount; $second++) {
                if ($second === $first + 1 || ($first === 0 && $second === $segmentCount - 1)) {
                    continue;
                }

                if ($this->segmentsIntersect(
                    $ring[$first],
                    $ring[$first + 1],
                    $ring[$second],
                    $ring[$second + 1],
                )) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<mixed>  $firstRing
     * @param  array<mixed>  $secondRing
     */
    private function ringsIntersect(array $firstRing, array $secondRing): bool
    {
        foreach (array_slice($firstRing, 0, -1) as $first => $ignored) {
            foreach (array_slice($secondRing, 0, -1) as $second => $alsoIgnored) {
                if ($this->segmentsIntersect(
                    $firstRing[$first],
                    $firstRing[$first + 1],
                    $secondRing[$second],
                    $secondRing[$second + 1],
                )) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<mixed>  $point
     * @param  array<mixed>  $ring
     */
    private function pointIsStrictlyInsideRing(array $point, array $ring): bool
    {
        $inside = false;
        for ($current = 0, $previous = count($ring) - 2; $current < count($ring) - 1; $previous = $current++) {
            if ($this->pointOnSegment($point, $ring[$previous], $ring[$current])) {
                return false;
            }

            [$currentX, $currentY] = $ring[$current];
            [$previousX, $previousY] = $ring[$previous];
            [$pointX, $pointY] = $point;

            if (($currentY > $pointY) !== ($previousY > $pointY)
                && $pointX < (($previousX - $currentX) * ($pointY - $currentY) / ($previousY - $currentY)) + $currentX) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * @param  array<mixed>  $firstStart
     * @param  array<mixed>  $firstEnd
     * @param  array<mixed>  $secondStart
     * @param  array<mixed>  $secondEnd
     */
    private function segmentsIntersect(array $firstStart, array $firstEnd, array $secondStart, array $secondEnd): bool
    {
        $firstOrientation = $this->orientation($firstStart, $firstEnd, $secondStart);
        $secondOrientation = $this->orientation($firstStart, $firstEnd, $secondEnd);
        $thirdOrientation = $this->orientation($secondStart, $secondEnd, $firstStart);
        $fourthOrientation = $this->orientation($secondStart, $secondEnd, $firstEnd);

        if ($firstOrientation !== $secondOrientation && $thirdOrientation !== $fourthOrientation) {
            return true;
        }

        return ($firstOrientation === 0 && $this->pointOnSegment($secondStart, $firstStart, $firstEnd))
            || ($secondOrientation === 0 && $this->pointOnSegment($secondEnd, $firstStart, $firstEnd))
            || ($thirdOrientation === 0 && $this->pointOnSegment($firstStart, $secondStart, $secondEnd))
            || ($fourthOrientation === 0 && $this->pointOnSegment($firstEnd, $secondStart, $secondEnd));
    }

    /**
     * @param  array<mixed>  $start
     * @param  array<mixed>  $end
     * @param  array<mixed>  $point
     */
    private function orientation(array $start, array $end, array $point): int
    {
        $crossProduct = (($end[1] - $start[1]) * ($point[0] - $end[0]))
            - (($end[0] - $start[0]) * ($point[1] - $end[1]));

        if (abs($crossProduct) < 1e-12) {
            return 0;
        }

        return $crossProduct > 0 ? 1 : 2;
    }

    /**
     * @param  array<mixed>  $point
     * @param  array<mixed>  $start
     * @param  array<mixed>  $end
     */
    private function pointOnSegment(array $point, array $start, array $end): bool
    {
        return $this->orientation($start, $end, $point) === 0
            && $point[0] <= max($start[0], $end[0]) + 1e-12
            && $point[0] >= min($start[0], $end[0]) - 1e-12
            && $point[1] <= max($start[1], $end[1]) + 1e-12
            && $point[1] >= min($start[1], $end[1]) - 1e-12;
    }

    private function fail(GeometryValidationError $error): never
    {
        throw new InvalidGeometryException($error);
    }
}
