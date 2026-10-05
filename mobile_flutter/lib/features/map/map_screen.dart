import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../app/providers.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/agri_widgets.dart';
import '../../models/models.dart';
import 'map_geometry.dart';

class FarmMapScreen extends ConsumerWidget {
  const FarmMapScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final farms = ref.watch(farmsProvider);

    return Scaffold(
      backgroundColor: AgriColors.canvas,
      appBar: AppBar(
        title: const Text('Farm locations'),
        actions: [
          IconButton(
            tooltip: 'Refresh farms',
            onPressed: () => ref.invalidate(farmsProvider),
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      body: SafeArea(
        top: false,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(
            AgriSpacing.md,
            AgriSpacing.md,
            AgriSpacing.md,
            100,
          ),
          children: [
            const AgriCard(
              color: AgriColors.milletSoft,
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(Icons.info_outline_rounded, color: AgriColors.ink),
                  SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      'Pan and zoom the recorded farm outlines below. This is a geo-referenced outline view, not a satellite image or surveyed basemap. Farms without location data remain in the list.',
                      style: TextStyle(height: 1.45),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: AgriSpacing.md),
            farms.when(
              loading: () => const Center(
                child: Padding(
                  padding: EdgeInsets.all(48),
                  child: CircularProgressIndicator(),
                ),
              ),
              error: (error, _) => ErrorPanel(
                message: friendlyError(error),
                retry: () => ref.invalidate(farmsProvider),
              ),
              data: (items) => Column(
                children: [
                  FarmOutlineMap(farms: items),
                  const SizedBox(height: AgriSpacing.md),
                  _FarmLocationList(farms: items),
                ],
              ),
            ),
          ],
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push('/farms/new'),
        icon: const Icon(Icons.add_location_alt_outlined),
        label: const Text('Add farm'),
      ),
    );
  }
}

class FarmOutlineMap extends StatefulWidget {
  const FarmOutlineMap({
    super.key,
    required this.farms,
    this.sections = const [],
  });

  final List<Farm> farms;
  final List<FarmSection> sections;

  @override
  State<FarmOutlineMap> createState() => _FarmOutlineMapState();
}

class _FarmOutlineMapState extends State<FarmOutlineMap> {
  bool _showOutlines = true;
  bool _showNames = true;

  @override
  Widget build(BuildContext context) {
    final located = widget.farms
        .where(
          (farm) =>
              FarmMapGeometry.fromGeoJson(farm.boundaryGeoJson) != null ||
              (farm.latitude != null && farm.longitude != null),
        )
        .toList();
    if (located.isEmpty) return const SizedBox.shrink();

    return AgriCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Farm outline map',
            style: Theme.of(context).textTheme.titleMedium,
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            children: [
              FilterChip(
                label: const Text('Boundaries'),
                selected: _showOutlines,
                onSelected: (value) => setState(() => _showOutlines = value),
              ),
              FilterChip(
                label: const Text('Names'),
                selected: _showNames,
                onSelected: (value) => setState(() => _showNames = value),
              ),
            ],
          ),
          const SizedBox(height: 8),
          ClipRRect(
            borderRadius: BorderRadius.circular(AgriRadius.sm),
            child: SizedBox(
              height: 300,
              width: double.infinity,
              child: LayoutBuilder(
                builder: (context, constraints) => InteractiveViewer(
                  minScale: 1,
                  maxScale: 8,
                  child: CustomPaint(
                    size: Size(constraints.maxWidth, 300),
                    painter: _FarmOutlinePainter(
                      located,
                      widget.sections,
                      _showOutlines,
                      _showNames,
                    ),
                  ),
                ),
              ),
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Recorded positions only · pinch to zoom · tap a farm below for details',
            style: TextStyle(color: AgriColors.muted, fontSize: 12),
          ),
        ],
      ),
    );
  }
}

class _FarmOutlinePainter extends CustomPainter {
  const _FarmOutlinePainter(
    this.farms,
    this.sections,
    this.showOutlines,
    this.showNames,
  );

  final List<Farm> farms;
  final List<FarmSection> sections;
  final bool showOutlines;
  final bool showNames;

  @override
  void paint(Canvas canvas, Size size) {
    canvas.drawRect(Offset.zero & size, Paint()..color = AgriColors.leafSoft);
    final geometries = {
      for (final farm in farms)
        farm.id: FarmMapGeometry.fromGeoJson(farm.boundaryGeoJson),
    };
    final sectionGeometries = {
      for (final section in sections)
        section.id: FarmMapGeometry.fromGeoJson(section.boundaryGeoJson),
    };
    final allPoints = <MapCoordinate>[
      for (final farm in farms) ...[
        for (final polygon
            in geometries[farm.id]?.polygons ?? <List<MapCoordinate>>[])
          ...polygon,
        if (farm.latitude != null && farm.longitude != null)
          MapCoordinate(farm.latitude!, farm.longitude!),
      ],
      for (final section in sections)
        for (final polygon
            in sectionGeometries[section.id]?.polygons ??
                <List<MapCoordinate>>[])
          ...polygon,
    ];
    if (allPoints.isEmpty) return;
    final meanLatitude =
        allPoints.map((point) => point.latitude).reduce((a, b) => a + b) /
        allPoints.length;
    final longitudeScale = math.cos(meanLatitude * math.pi / 180);
    double x(MapCoordinate point) => point.longitude * longitudeScale;
    final minX = allPoints.map(x).reduce(math.min);
    final maxX = allPoints.map(x).reduce(math.max);
    final minY = allPoints.map((point) => point.latitude).reduce(math.min);
    final maxY = allPoints.map((point) => point.latitude).reduce(math.max);
    final spanX = math.max(maxX - minX, 0.0001);
    final spanY = math.max(maxY - minY, 0.0001);
    final scale = math.min(
      (size.width - 48) / spanX,
      (size.height - 48) / spanY,
    );
    Offset project(MapCoordinate point) => Offset(
      24 + (x(point) - minX) * scale,
      size.height - 24 - (point.latitude - minY) * scale,
    );

    for (final farm in farms) {
      final geometry = geometries[farm.id];
      if (showOutlines && geometry != null) {
        for (final polygon in geometry.polygons) {
          final boundary = Path();
          var isFirstPoint = true;
          for (final point in polygon) {
            final offset = project(point);
            if (isFirstPoint) {
              boundary.moveTo(offset.dx, offset.dy);
              isFirstPoint = false;
            } else {
              boundary.lineTo(offset.dx, offset.dy);
            }
          }
          boundary.close();
          canvas.drawPath(
            boundary,
            Paint()..color = AgriColors.forest.withValues(alpha: 0.16),
          );
          canvas.drawPath(
            boundary,
            Paint()
              ..color = AgriColors.forest
              ..style = PaintingStyle.stroke
              ..strokeWidth = 2,
          );
        }
      }
      MapCoordinate? marker;
      if (farm.latitude != null && farm.longitude != null) {
        marker = MapCoordinate(farm.latitude!, farm.longitude!);
      } else if (geometry != null && geometry.polygons.isNotEmpty) {
        marker = geometry.polygons.first.first;
      }
      if (marker == null) continue;
      final offset = project(marker);
      canvas.drawCircle(offset, 5, Paint()..color = AgriColors.forest);
      if (showNames) {
        final label = TextPainter(
          text: TextSpan(
            text: farm.name,
            style: const TextStyle(
              color: AgriColors.ink,
              fontSize: 11,
              fontWeight: FontWeight.w700,
            ),
          ),
          textDirection: TextDirection.ltr,
          maxLines: 1,
          ellipsis: '…',
        )..layout(maxWidth: 130);
        label.paint(canvas, offset + const Offset(9, -8));
      }
    }
    if (showOutlines) {
      for (final section in sections) {
        final geometry = sectionGeometries[section.id];
        if (geometry == null) continue;
        for (final polygon in geometry.polygons) {
          final boundary = Path();
          for (var index = 0; index < polygon.length; index++) {
            final offset = project(polygon[index]);
            if (index == 0) {
              boundary.moveTo(offset.dx, offset.dy);
            } else {
              boundary.lineTo(offset.dx, offset.dy);
            }
          }
          boundary.close();
          canvas.drawPath(
            boundary,
            Paint()
              ..color = AgriColors.clay
              ..style = PaintingStyle.stroke
              ..strokeWidth = 2,
          );
          if (showNames) {
            final label = TextPainter(
              text: TextSpan(
                text: section.name,
                style: const TextStyle(
                  color: AgriColors.clay,
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                ),
              ),
              textDirection: TextDirection.ltr,
              maxLines: 1,
              ellipsis: '…',
            )..layout(maxWidth: 130);
            label.paint(canvas, project(polygon.first) + const Offset(8, 6));
          }
        }
      }
    }
  }

  @override
  bool shouldRepaint(covariant _FarmOutlinePainter oldDelegate) => true;
}

class _FarmLocationList extends StatelessWidget {
  const _FarmLocationList({required this.farms});

  final List<Farm> farms;

  @override
  Widget build(BuildContext context) {
    if (farms.isEmpty) {
      return AgriCard(
        child: Column(
          children: [
            const Icon(
              Icons.add_location_alt_outlined,
              size: 48,
              color: AgriColors.forest,
            ),
            const SizedBox(height: 12),
            Text(
              'No farms registered yet',
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: 6),
            const Text(
              'Add a farm to record its verified boundary and location.',
              textAlign: TextAlign.center,
            ),
          ],
        ),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const SectionHeading('Registered farms'),
        for (final farm in farms)
          Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: _FarmLocationCard(farm: farm),
          ),
      ],
    );
  }
}

class _FarmLocationCard extends StatelessWidget {
  const _FarmLocationCard({required this.farm});

  final Farm farm;

  @override
  Widget build(BuildContext context) {
    final hasLocation = farm.latitude != null && farm.longitude != null;
    final place = [
      farm.locality,
      farm.state,
    ].whereType<String>().where((value) => value.isNotEmpty).join(', ');

    return AgriCard(
      onTap: () => context.push('/farms/${farm.id}'),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: hasLocation ? AgriColors.leafSoft : AgriColors.claySoft,
              borderRadius: BorderRadius.circular(AgriRadius.sm),
            ),
            child: Icon(
              hasLocation
                  ? Icons.location_on_outlined
                  : Icons.location_off_outlined,
              color: hasLocation ? AgriColors.forest : AgriColors.clay,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(farm.name, style: Theme.of(context).textTheme.titleMedium),
                if (place.isNotEmpty) ...[
                  const SizedBox(height: 3),
                  Text(place, style: const TextStyle(color: AgriColors.muted)),
                ],
                const SizedBox(height: 8),
                Text(
                  hasLocation
                      ? 'Location recorded from farm data'
                      : 'Map position unavailable',
                  style: TextStyle(
                    color: hasLocation ? AgriColors.grove : AgriColors.clay,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    StatusPill(farm.status),
                    if (farm.activeCropCycle?.cropName != null)
                      StatusPill(
                        farm.activeCropCycle!.cropName!,
                        icon: Icons.eco_outlined,
                      ),
                    if (farm.hectares != null)
                      StatusPill(
                        '${farm.hectares!.toStringAsFixed(1)} ha',
                        icon: Icons.straighten_rounded,
                      ),
                  ],
                ),
              ],
            ),
          ),
          const Icon(Icons.chevron_right_rounded, color: AgriColors.muted),
        ],
      ),
    );
  }
}
