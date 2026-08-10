<?php

declare(strict_types=1);

namespace App\Data\Geometry;

final readonly class ProcessedGeometry
{
    /** @param array{type: 'Polygon'|'MultiPolygon', coordinates: array<mixed>} $geoJson */
    public function __construct(
        public array $geoJson,
        public string $hash,
        public float $areaHectares,
        public float $areaAcres,
        public float $centroidLatitude,
        public float $centroidLongitude,
    ) {}
}
