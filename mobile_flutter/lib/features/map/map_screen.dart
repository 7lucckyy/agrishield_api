import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart' as google;
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
                      'Pan and zoom your recorded farm outlines on Google Maps. Boundaries are farmer-recorded, not surveyed. Farms without location data remain in the list.',
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
    this.productivityZones,
  });

  final List<Farm> farms;
  final List<FarmSection> sections;
  final Json? productivityZones;

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

    final rawGroups = widget.productivityZones?['crop_groups'];
    final zoneBySectionId = <int, String>{};
    if (rawGroups is Map) {
      for (final group in rawGroups.values.whereType<Map>()) {
        if (group['state'] != 'ready') continue;
        for (final zone in (group['zones'] as List? ?? []).whereType<Map>()) {
          final sectionId = zone['section_id'];
          final label = zone['zone'];
          if (sectionId is int && label is String) {
            zoneBySectionId[sectionId] = label;
          }
        }
      }
    }

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
              child: google.GoogleMap(
                mapType: google.MapType.hybrid,
                initialCameraPosition: google.CameraPosition(
                  target: google.LatLng(
                    located.first.latitude ?? 9.082,
                    located.first.longitude ?? 8.6753,
                  ),
                  zoom: 12,
                ),
                zoomControlsEnabled: false,
                onMapCreated: (controller) {
                  final points = <MapCoordinate>[
                    for (final farm in located) ...[
                      if (farm.latitude != null && farm.longitude != null)
                        MapCoordinate(farm.latitude!, farm.longitude!),
                      for (final polygon
                          in FarmMapGeometry.fromGeoJson(farm.boundaryGeoJson)
                                  ?.polygons ??
                              <List<MapCoordinate>>[])
                        ...polygon,
                    ],
                    for (final section in widget.sections)
                      for (final polygon
                          in FarmMapGeometry.fromGeoJson(
                                section.boundaryGeoJson,
                              )?.polygons ??
                              <List<MapCoordinate>>[])
                        ...polygon,
                  ];
                  if (points.isEmpty) return;
                  controller.moveCamera(
                    google.CameraUpdate.newLatLngBounds(
                      google.LatLngBounds(
                        southwest: google.LatLng(
                          points
                              .map((point) => point.latitude)
                              .reduce(math.min),
                          points
                              .map((point) => point.longitude)
                              .reduce(math.min),
                        ),
                        northeast: google.LatLng(
                          points
                              .map((point) => point.latitude)
                              .reduce(math.max),
                          points
                              .map((point) => point.longitude)
                              .reduce(math.max),
                        ),
                      ),
                      32,
                    ),
                  );
                },
                polygons: _showOutlines
                    ? {
                        for (final farm in located)
                          for (
                            var index = 0;
                            index <
                                (FarmMapGeometry.fromGeoJson(
                                      farm.boundaryGeoJson,
                                    )?.polygons.length ??
                                    0);
                            index++
                          )
                            google.Polygon(
                              polygonId: google.PolygonId('${farm.id}-$index'),
                              points: [
                                for (final point in FarmMapGeometry.fromGeoJson(
                                  farm.boundaryGeoJson,
                                )!.polygons[index])
                                  google.LatLng(
                                    point.latitude,
                                    point.longitude,
                                  ),
                              ],
                              strokeColor: AgriColors.forest,
                              strokeWidth: 2,
                              fillColor: AgriColors.forest.withValues(
                                alpha: .16,
                              ),
                            ),
                        for (final section in widget.sections)
                          for (
                            var index = 0;
                            index <
                                (FarmMapGeometry.fromGeoJson(
                                      section.boundaryGeoJson,
                                    )?.polygons.length ??
                                    0);
                            index++
                          )
                            google.Polygon(
                              polygonId: google.PolygonId(
                                'section-${section.id}-$index',
                              ),
                              points: [
                                for (final point in FarmMapGeometry.fromGeoJson(
                                  section.boundaryGeoJson,
                                )!.polygons[index])
                                  google.LatLng(
                                    point.latitude,
                                    point.longitude,
                                  ),
                              ],
                              strokeColor: AgriColors.clay,
                              strokeWidth: 2,
                              fillColor: switch (zoneBySectionId[section.id]) {
                                'lower_relative_vigor' =>
                                  AgriColors.clay.withValues(alpha: .48),
                                'higher_relative_vigor' =>
                                  AgriColors.forest.withValues(alpha: .48),
                                'middle_relative_vigor' =>
                                  AgriColors.millet.withValues(alpha: .48),
                                _ => Colors.transparent,
                              },
                            ),
                      }
                    : {},
                markers: _showNames
                    ? {
                        for (final farm in located)
                          if (farm.latitude != null && farm.longitude != null)
                            google.Marker(
                              markerId: google.MarkerId(farm.id),
                              position: google.LatLng(
                                farm.latitude!,
                                farm.longitude!,
                              ),
                              infoWindow: google.InfoWindow(title: farm.name),
                            ),
                      }
                    : {},
              ),
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Recorded boundaries · pinch to zoom · tap a farm below for details',
            style: TextStyle(color: AgriColors.muted, fontSize: 12),
          ),
        ],
      ),
    );
  }
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
