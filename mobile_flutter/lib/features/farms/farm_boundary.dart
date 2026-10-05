import 'dart:math' as math;

class BoundaryPoint {
  const BoundaryPoint(this.latitude, this.longitude);

  final double latitude;
  final double longitude;
}

class FarmBoundary {
  const FarmBoundary(this.points);

  final List<BoundaryPoint> points;

  bool get isValid =>
      points.length >= 3 &&
      points.every(
        (point) => point.latitude.abs() <= 90 && point.longitude.abs() <= 180,
      ) &&
      hectares > 0 &&
      !hasSelfIntersection;

  bool get hasSelfIntersection {
    if (points.length < 4) return false;
    for (var first = 0; first < points.length; first++) {
      for (var second = first + 2; second < points.length; second++) {
        if (first == 0 && second == points.length - 1) continue;
        if (_segmentsIntersect(
          points[first],
          points[(first + 1) % points.length],
          points[second],
          points[(second + 1) % points.length],
        )) {
          return true;
        }
      }
    }
    return false;
  }

  bool _segmentsIntersect(
    BoundaryPoint a,
    BoundaryPoint b,
    BoundaryPoint c,
    BoundaryPoint d,
  ) {
    double cross(BoundaryPoint p, BoundaryPoint q, BoundaryPoint r) =>
        (q.longitude - p.longitude) * (r.latitude - p.latitude) -
        (q.latitude - p.latitude) * (r.longitude - p.longitude);
    final abC = cross(a, b, c);
    final abD = cross(a, b, d);
    final cdA = cross(c, d, a);
    final cdB = cross(c, d, b);
    return abC * abD < 0 && cdA * cdB < 0;
  }

  double get hectares {
    if (points.length < 3) return 0;
    final meanLatitude =
        points.map((point) => point.latitude).reduce((a, b) => a + b) /
        points.length;
    final longitudeScale = 111320 * math.cos(meanLatitude * math.pi / 180);
    final origin = points.first;
    var twiceArea = 0.0;
    for (var index = 0; index < points.length; index++) {
      final current = points[index];
      final next = points[(index + 1) % points.length];
      final currentEast =
          (current.longitude - origin.longitude) * longitudeScale;
      final currentNorth = (current.latitude - origin.latitude) * 111320;
      final nextEast = (next.longitude - origin.longitude) * longitudeScale;
      final nextNorth = (next.latitude - origin.latitude) * 111320;
      twiceArea += currentEast * nextNorth - nextEast * currentNorth;
    }
    return twiceArea.abs() / 2 / 10000;
  }

  Map<String, dynamic> toGeoJson() {
    if (!isValid) {
      throw StateError('A farm boundary needs three valid perimeter points.');
    }
    final ring = [
      for (final point in points) [point.longitude, point.latitude],
      [points.first.longitude, points.first.latitude],
    ];
    return {
      'type': 'Polygon',
      'coordinates': [ring],
    };
  }
}
