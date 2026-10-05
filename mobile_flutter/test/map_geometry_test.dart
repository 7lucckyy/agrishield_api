import 'package:agrishield_ai/features/map/map_geometry.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('map geometry reads every polygon in a multipolygon', () {
    final geometry = FarmMapGeometry.fromGeoJson({
      'type': 'MultiPolygon',
      'coordinates': [
        [
          [
            [8.0, 12.0],
            [8.1, 12.0],
            [8.1, 12.1],
            [8.0, 12.0],
          ],
        ],
        [
          [
            [9.0, 13.0],
            [9.1, 13.0],
            [9.1, 13.1],
            [9.0, 13.0],
          ],
        ],
      ],
    });

    expect(geometry, isNotNull);
    expect(geometry!.polygons.length, 2);
    expect(geometry.polygons.first.first.latitude, 12);
    expect(geometry.polygons.last.first.longitude, 9);
  });

  test('invalid coordinates do not produce a displayed boundary', () {
    expect(
      FarmMapGeometry.fromGeoJson({'type': 'Polygon', 'coordinates': []}),
      isNull,
    );
    expect(
      FarmMapGeometry.fromGeoJson({
        'type': 'Polygon',
        'coordinates': [
          [
            [800, 12],
            [8, 12],
            [8, 13],
            [800, 12],
          ],
        ],
      }),
      isNull,
    );
  });
}
