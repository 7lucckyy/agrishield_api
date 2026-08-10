<?php

declare(strict_types=1);

namespace App\Services\Geometry;

use App\Data\Geometry\ProcessedGeometry;
use App\Enums\GeometryValidationError;
use App\Exceptions\InvalidGeometryException;

final class GeometryProcessor
{
    private const float ACRES_PER_HECTARE = 2.471054;

    public function __construct(
        private GeometryValidator $validator,
        private GeoJsonCanonicaliser $canonicaliser,
        private AreaCalculator $areaCalculator,
        private CentroidCalculator $centroidCalculator,
    ) {}

    public function process(mixed $geometry): ProcessedGeometry
    {
        $validated = $this->validator->validate($geometry);
        $canonical = $this->canonicaliser->canonicalise($validated);
        $this->validator->validate($canonical);

        $areaHectares = $this->areaCalculator->hectares($canonical);
        if ($areaHectares <= 0 || $areaHectares > 10000) {
            throw new InvalidGeometryException(GeometryValidationError::AreaOutOfRange);
        }

        $centroid = $this->centroidCalculator->calculate($canonical);
        $encoded = json_encode($canonical, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);

        return new ProcessedGeometry(
            geoJson: $canonical,
            hash: hash('sha256', $encoded),
            areaHectares: round($areaHectares, 4),
            areaAcres: round($areaHectares * self::ACRES_PER_HECTARE, 4),
            centroidLatitude: round($centroid['latitude'], 7),
            centroidLongitude: round($centroid['longitude'], 7),
        );
    }
}
