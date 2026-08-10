<?php

declare(strict_types=1);

namespace App\Enums;

enum GeometryValidationError: string
{
    case TooLarge = 'boundary_geojson.too_large';
    case Type = 'boundary_geojson.type';
    case NotClosed = 'boundary_geojson.not_closed';
    case MinPoints = 'boundary_geojson.min_points';
    case TooComplex = 'boundary_geojson.too_complex';
    case CoordinateRange = 'boundary_geojson.coordinate_range';
    case SelfIntersecting = 'boundary_geojson.self_intersecting';
    case AreaOutOfRange = 'boundary_geojson.area_out_of_range';
    case InvalidHole = 'boundary_geojson.invalid_hole';

    public function message(): string
    {
        return match ($this) {
            self::TooLarge => 'The boundary payload may not exceed 512 KB.',
            self::Type => 'The boundary must be a GeoJSON Polygon or MultiPolygon geometry.',
            self::NotClosed => 'Every boundary ring must be closed.',
            self::MinPoints => 'Every boundary ring must contain at least four positions.',
            self::TooComplex => 'The boundary contains too many rings or positions.',
            self::CoordinateRange => 'Every boundary position must contain a valid longitude and latitude.',
            self::SelfIntersecting => 'The exterior boundary ring may not intersect itself.',
            self::AreaOutOfRange => 'The boundary area must be greater than zero and no more than 10,000 hectares.',
            self::InvalidHole => 'Every boundary hole must be fully contained by its exterior ring.',
        };
    }
}
