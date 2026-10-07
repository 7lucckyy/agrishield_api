import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';

import '../../core/theme/app_theme.dart';

/// Centre of Nigeria, used before the farmer's own location is known.
const nigeriaCenter = LatLng(9.0820, 8.6753);

/// Corners can only be placed at or above this zoom, so a stray tap on a
/// country-wide view cannot create a boundary hundreds of kilometres wide.
const minimumCornerZoom = 14.0;

const _esriImageryUrl =
    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}';
const _esriPlaceLabelsUrl =
    'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}';

/// Esri satellite imagery with place-name labels on top.
List<Widget> satelliteBaseLayers({TileProvider? tileProvider}) => [
  TileLayer(
    urlTemplate: _esriImageryUrl,
    userAgentPackageName: 'ng.agrishield.app',
    maxNativeZoom: 18,
    tileProvider: tileProvider,
  ),
  TileLayer(
    urlTemplate: _esriPlaceLabelsUrl,
    userAgentPackageName: 'ng.agrishield.app',
    maxNativeZoom: 18,
    tileProvider: tileProvider,
  ),
];

/// Required credit for the Esri imagery.
const satelliteCredit = 'Imagery © Esri, Maxar, Earthstar Geographics';

/// Overlays [satelliteCredit] in the bottom-right corner of a map.
class SatelliteAttribution extends StatelessWidget {
  const SatelliteAttribution({super.key});

  @override
  Widget build(BuildContext context) => Align(
    alignment: Alignment.bottomRight,
    child: Container(
      margin: const EdgeInsets.only(left: 40),
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      color: const Color(0xB3FFFFFF),
      child: const Text(
        satelliteCredit,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: TextStyle(color: AgriColors.ink, fontSize: 10),
      ),
    ),
  );
}

/// Draws the farm outline and numbered corner markers.
List<Widget> boundaryOverlayLayers(
  List<LatLng> corners, {
  bool showCornerNumbers = true,
  bool hasCrossingEdges = false,
}) {
  final edgeColor = hasCrossingEdges ? AgriColors.clay : AgriColors.millet;
  return [
    if (corners.length >= 3)
      PolygonLayer(
        polygons: [
          Polygon(
            points: corners,
            color: edgeColor.withValues(alpha: .28),
            borderColor: edgeColor,
            borderStrokeWidth: 3,
          ),
        ],
      )
    else if (corners.length == 2)
      PolylineLayer(
        polylines: [
          Polyline(points: corners, color: edgeColor, strokeWidth: 3),
        ],
      ),
    MarkerLayer(
      markers: [
        for (var index = 0; index < corners.length; index++)
          Marker(
            point: corners[index],
            width: showCornerNumbers ? 28 : 12,
            height: showCornerNumbers ? 28 : 12,
            child: _CornerMarker(
              number: showCornerNumbers ? index + 1 : null,
              isFirst: index == 0,
            ),
          ),
      ],
    ),
  ];
}

class _CornerMarker extends StatelessWidget {
  const _CornerMarker({required this.number, required this.isFirst});

  final int? number;
  final bool isFirst;

  @override
  Widget build(BuildContext context) => DecoratedBox(
    decoration: BoxDecoration(
      color: isFirst ? AgriColors.millet : Colors.white,
      shape: BoxShape.circle,
      border: Border.all(color: AgriColors.forest, width: 2),
      boxShadow: const [BoxShadow(color: Color(0x66000000), blurRadius: 4)],
    ),
    child: number == null
        ? null
        : Center(
            child: Text(
              '$number',
              style: const TextStyle(
                color: AgriColors.forest,
                fontSize: 12,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
  );
}

/// A small, non-interactive satellite view framed around the boundary.
class BoundaryPreviewMap extends StatelessWidget {
  const BoundaryPreviewMap({
    super.key,
    required this.corners,
    this.height = 180,
    this.tileProvider,
  });

  final List<LatLng> corners;
  final double height;
  final TileProvider? tileProvider;

  @override
  Widget build(BuildContext context) => ClipRRect(
    borderRadius: BorderRadius.circular(AgriRadius.md),
    child: SizedBox(
      height: height,
      child: FlutterMap(
        options: MapOptions(
          initialCameraFit: CameraFit.coordinates(
            coordinates: corners,
            padding: const EdgeInsets.all(28),
            maxZoom: 19,
          ),
          interactionOptions: const InteractionOptions(
            flags: InteractiveFlag.none,
          ),
        ),
        children: [
          ...satelliteBaseLayers(tileProvider: tileProvider),
          ...boundaryOverlayLayers(corners, showCornerNumbers: false),
          const SatelliteAttribution(),
        ],
      ),
    ),
  );
}
