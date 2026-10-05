import 'package:agrishield_ai/features/farms/farm_boundary.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('perimeter walk produces a closed polygon and measured area', () {
    final boundary = FarmBoundary([
      const BoundaryPoint(12, 8),
      const BoundaryPoint(12, 8.001),
      const BoundaryPoint(12.001, 8.001),
      const BoundaryPoint(12.001, 8),
    ]);

    expect(boundary.isValid, isTrue);
    expect(boundary.hectares, closeTo(1.21, 0.05));
    final polygon = boundary.toGeoJson();
    expect(polygon['type'], 'Polygon');
    final ring = (polygon['coordinates'] as List).single as List;
    expect(ring.length, 5);
    expect(ring.first, ring.last);
  });

  test('one GPS position is never treated as a farm boundary', () {
    final boundary = FarmBoundary([const BoundaryPoint(12, 8)]);

    expect(boundary.isValid, isFalse);
    expect(() => boundary.toGeoJson(), throwsStateError);
  });

  test('a crossed perimeter is rejected before upload', () {
    final boundary = FarmBoundary([
      const BoundaryPoint(12, 8),
      const BoundaryPoint(12.001, 8.001),
      const BoundaryPoint(12, 8.001),
      const BoundaryPoint(12.001, 8),
    ]);

    expect(boundary.hasSelfIntersection, isTrue);
    expect(boundary.isValid, isFalse);
  });
}
