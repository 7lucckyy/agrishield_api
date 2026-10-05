import '../../models/models.dart';

class MapCoordinate {
  const MapCoordinate(this.latitude, this.longitude);

  final double latitude;
  final double longitude;
}

class FarmMapGeometry {
  const FarmMapGeometry(this.polygons);

  final List<List<MapCoordinate>> polygons;

  static FarmMapGeometry? fromGeoJson(Json? json) {
    if (json == null) return null;
    final coordinates = json['coordinates'];
    if (coordinates is! List) return null;
    final rawPolygons = switch (json['type']) {
      'Polygon' => [coordinates],
      'MultiPolygon' => coordinates,
      _ => null,
    };
    if (rawPolygons == null) return null;

    final polygons = <List<MapCoordinate>>[];
    for (final rawPolygon in rawPolygons) {
      if (rawPolygon is! List ||
          rawPolygon.isEmpty ||
          rawPolygon.first is! List) {
        return null;
      }
      final ring = rawPolygon.first as List;
      final points = <MapCoordinate>[];
      for (final rawPoint in ring) {
        if (rawPoint is! List ||
            rawPoint.length < 2 ||
            rawPoint[0] is! num ||
            rawPoint[1] is! num) {
          return null;
        }
        final longitude = (rawPoint[0] as num).toDouble();
        final latitude = (rawPoint[1] as num).toDouble();
        if (latitude.abs() > 90 || longitude.abs() > 180) return null;
        points.add(MapCoordinate(latitude, longitude));
      }
      if (points.length < 4) return null;
      polygons.add(points);
    }
    return polygons.isEmpty ? null : FarmMapGeometry(polygons);
  }
}
