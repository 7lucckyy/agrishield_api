import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import 'package:go_router/go_router.dart';
import 'package:latlong2/latlong.dart';

import '../../app/providers.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/agri_widgets.dart';
import '../../models/models.dart';
import '../map/map_screen.dart';
import 'farm_boundary.dart';
import 'farm_boundary_map.dart';

class FarmsScreen extends ConsumerWidget {
  const FarmsScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final farms = ref.watch(farmsProvider);
    return AgriPage(
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push('/farms/new'),
        icon: const Icon(Icons.add_rounded),
        label: const Text('Add farm'),
      ),
      children: [
        const PageHeading(
          eyebrow: 'Your fields',
          title: 'Crop farms',
          description: 'Each farm keeps its own weather, crop checks and recommendations.',
        ),
        farms.when(
          loading: () => const Center(
            child: Padding(
              padding: EdgeInsets.all(40),
              child: CircularProgressIndicator(),
            ),
          ),
          error: (error, _) => ErrorPanel(
            message: friendlyError(error),
            retry: () => ref.invalidate(farmsProvider),
          ),
          data: (items) => items.isEmpty
              ? AgriCard(
                  color: AgriColors.leafSoft,
                  child: Column(
                    children: [
                      const Icon(
                        Icons.add_location_alt_outlined,
                        size: 54,
                        color: AgriColors.forest,
                      ),
                      const SizedBox(height: 12),
                      Text(
                        'Register your first field',
                        style: Theme.of(context).textTheme.titleLarge,
                      ),
                      const SizedBox(height: 6),
                      const Text(
                        'Find your field on the satellite map and tap its corners.',
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 16),
                      FilledButton(
                        onPressed: () => context.push('/farms/new'),
                        child: const Text('Add my farm'),
                      ),
                    ],
                  ),
                )
              : Column(
                  children: items
                      .map(
                        (farm) => Padding(
                          padding: const EdgeInsets.only(bottom: 12),
                          child: _FarmCard(farm: farm),
                        ),
                      )
                      .toList(),
                ),
        ),
      ],
    );
  }
}

class _FarmCard extends StatelessWidget {
  const _FarmCard({required this.farm});
  final Farm farm;
  @override
  Widget build(BuildContext context) => AgriCard(
    onTap: () => context.push('/farms/${farm.id}'),
    child: Row(
      children: [
        Container(
          width: 54,
          height: 54,
          decoration: BoxDecoration(
            color: AgriColors.leafSoft,
            borderRadius: BorderRadius.circular(16),
          ),
          child: const Icon(
            Icons.grass_rounded,
            color: AgriColors.forest,
            size: 29,
          ),
        ),
        const SizedBox(width: 14),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(farm.name, style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(height: 4),
              Text(
                [farm.locality, farm.state]
                    .whereType<String>()
                    .where((value) => value.isNotEmpty)
                    .join(', '),
                style: const TextStyle(color: AgriColors.muted),
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  StatusPill(farm.status),
                  if (farm.hectares != null) ...[
                    const SizedBox(width: 8),
                    Text(
                      '${farm.hectares!.toStringAsFixed(1)} ha',
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                  ],
                ],
              ),
            ],
          ),
        ),
        const Icon(Icons.chevron_right_rounded),
      ],
    ),
  );
}

class AddFarmScreen extends ConsumerStatefulWidget {
  const AddFarmScreen({
    super.key,
    @visibleForTesting this.tileProvider,
    @visibleForTesting this.initialCenter,
    @visibleForTesting this.initialZoom,
  });

  /// Overrides map tile loading, e.g. to avoid network access in tests.
  final TileProvider? tileProvider;
  final LatLng? initialCenter;
  final double? initialZoom;

  @override
  ConsumerState<AddFarmScreen> createState() => _AddFarmScreenState();
}

enum _AddFarmStep { boundary, details }

class _AddFarmScreenState extends ConsumerState<AddFarmScreen> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _locality = TextEditingController();
  String? _state;
  final _mapController = MapController();
  final List<LatLng> _corners = [];
  _AddFarmStep _step = _AddFarmStep.boundary;
  LatLng? _deviceLocation;
  bool _locating = false;
  bool _busy = false;

  FarmBoundary get _boundary => FarmBoundary([
    for (final corner in _corners)
      BoundaryPoint(corner.latitude, corner.longitude),
  ]);

  @override
  void initState() {
    super.initState();
    if (widget.initialCenter == null) {
      _centerOnDeviceIfAllowed();
    }
  }

  @override
  void dispose() {
    _mapController.dispose();
    _name.dispose();
    _locality.dispose();
    super.dispose();
  }

  /// Jumps to the farmer's position on open, without prompting for access.
  Future<void> _centerOnDeviceIfAllowed() async {
    try {
      final permission = await Geolocator.checkPermission();
      if (permission != LocationPermission.always &&
          permission != LocationPermission.whileInUse) {
        return;
      }
      final position = await Geolocator.getLastKnownPosition();
      if (position != null && mounted && _corners.isEmpty) {
        _showDeviceLocation(position);
      }
    } catch (_) {
      // Location is a convenience here; the farmer can still pan the map.
    }
  }

  Future<void> _locateDevice() async {
    setState(() => _locating = true);
    try {
      if (!await Geolocator.isLocationServiceEnabled()) {
        if (mounted) {
          showMessage(context, 'Turn on Location Services, then try again.');
        }
        return;
      }
      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        if (mounted) {
          showMessage(
            context,
            'Allow location access to jump to where you are, or move the map yourself.',
          );
        }
        return;
      }
      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 20),
        ),
      );
      if (mounted) {
        _showDeviceLocation(position);
      }
    } catch (_) {
      if (mounted) {
        showMessage(
          context,
          'Your location could not be found. Move the map to your farm instead.',
        );
      }
    } finally {
      if (mounted) {
        setState(() => _locating = false);
      }
    }
  }

  void _showDeviceLocation(Position position) {
    final location = LatLng(position.latitude, position.longitude);
    setState(() => _deviceLocation = location);
    _mapController.move(location, 17);
  }

  void _addCorner(LatLng point) {
    if (_mapController.camera.zoom < minimumCornerZoom) {
      showMessage(
        context,
        'Zoom in closer to your field, then tap its corners.',
      );
      return;
    }
    setState(() => _corners.add(point));
  }

  void _continueToDetails() {
    final boundary = _boundary;
    if (!boundary.isValid) {
      return;
    }
    setState(() => _step = _AddFarmStep.details);
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }
    setState(() => _busy = true);
    try {
      await ref.read(apiClientProvider).createFarm({
        'name': _name.text.trim(),
        'locality': _locality.text.trim(),
        'state': _state,
        'country': 'NG',
        'boundary_geojson': _boundary.toGeoJson(),
      });
      ref.invalidate(farmsProvider);
      if (mounted) {
        showMessage(context, 'Farm registered successfully.');
        context.pop();
      }
    } catch (error) {
      if (mounted) {
        showMessage(context, friendlyError(error));
      }
    }
    if (mounted) {
      setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => PopScope(
    canPop: _step == _AddFarmStep.boundary,
    onPopInvokedWithResult: (didPop, _) {
      if (!didPop) {
        setState(() => _step = _AddFarmStep.boundary);
      }
    },
    child: _step == _AddFarmStep.boundary
        ? _buildBoundaryStep(context)
        : _buildDetailsStep(context),
  );

  Widget _buildBoundaryStep(BuildContext context) {
    final boundary = _boundary;
    final crossing = boundary.hasSelfIntersection;
    final String guidance;
    if (_corners.isEmpty) {
      guidance = 'Zoom in to your field, then tap each corner in order.';
    } else if (crossing) {
      guidance = 'Two edges cross. Undo and tap the corners in order around the field.';
    } else if (_corners.length < 3) {
      guidance =
          'Tap ${3 - _corners.length} more corner${_corners.length == 2 ? '' : 's'} to outline the field.';
    } else {
      guidance = 'Keep tapping to add corners, or continue when it matches.';
    }
    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light,
      child: Scaffold(
        body: Stack(
          children: [
            FlutterMap(
              mapController: _mapController,
              options: MapOptions(
                initialCenter: widget.initialCenter ?? nigeriaCenter,
                initialZoom: widget.initialZoom ?? 6,
                minZoom: 4,
                maxZoom: 20,
                onTap: (_, point) => _addCorner(point),
                interactionOptions: const InteractionOptions(
                  // Double-tap gestures would delay every corner tap while
                  // waiting for a second tap, so zoom is pinch-only here.
                  flags:
                      InteractiveFlag.all &
                      ~InteractiveFlag.rotate &
                      ~InteractiveFlag.doubleTapZoom &
                      ~InteractiveFlag.doubleTapDragZoom,
                ),
              ),
              children: [
                ...satelliteBaseLayers(tileProvider: widget.tileProvider),
                ...boundaryOverlayLayers(_corners, hasCrossingEdges: crossing),
                if (_deviceLocation != null)
                  MarkerLayer(
                    markers: [
                      Marker(
                        point: _deviceLocation!,
                        width: 22,
                        height: 22,
                        child: const _DeviceLocationDot(),
                      ),
                    ],
                  ),
              ],
            ),
            SafeArea(
              child: Padding(
                padding: const EdgeInsets.all(AgriSpacing.md),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _MapCircleButton(
                      icon: Icons.arrow_back_rounded,
                      tooltip: 'Back',
                      onPressed: () => context.pop(),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: _MapHint(
                        title: 'Mark your farm',
                        message: guidance,
                        warning: crossing,
                      ),
                    ),
                  ],
                ),
              ),
            ),
            Positioned(
              right: AgriSpacing.md,
              bottom: 196,
              child: _MapCircleButton(
                icon: Icons.my_location_rounded,
                tooltip: 'Go to my location',
                busy: _locating,
                onPressed: _locating ? null : _locateDevice,
              ),
            ),
            Align(
              alignment: Alignment.bottomCenter,
              child: _BoundaryPanel(
                corners: _corners.length,
                hectares: boundary.hectares,
                canContinue: boundary.isValid,
                onUndo: _corners.isEmpty
                    ? null
                    : () => setState(_corners.removeLast),
                onClear: _corners.isEmpty
                    ? null
                    : () => setState(_corners.clear),
                onContinue: _continueToDetails,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDetailsStep(BuildContext context) => AgriPage(
    appBar: AppBar(
      title: const Text('Farm details'),
      leading: IconButton(
        tooltip: 'Edit boundary',
        icon: const Icon(Icons.arrow_back_rounded),
        onPressed: () => setState(() => _step = _AddFarmStep.boundary),
      ),
    ),
    children: [
      BoundaryPreviewMap(
        corners: List.of(_corners),
        tileProvider: widget.tileProvider,
      ),
      const SizedBox(height: 12),
      Row(
        children: [
          Expanded(
            child: Text(
              '${_boundary.hectares.toStringAsFixed(2)} ha · ${_corners.length} corners',
              style: Theme.of(context).textTheme.titleMedium,
            ),
          ),
          TextButton.icon(
            onPressed: () => setState(() => _step = _AddFarmStep.boundary),
            icon: const Icon(Icons.edit_location_alt_outlined, size: 18),
            label: const Text('Edit outline'),
          ),
        ],
      ),
      const Text(
        'Area from the map is an estimate, not a land survey.',
        style: TextStyle(color: AgriColors.muted, fontSize: 12),
      ),
      const SectionHeading('About this farm'),
      Form(
        key: _formKey,
        child: Column(
          children: [
            TextFormField(
              controller: _name,
              textCapitalization: TextCapitalization.sentences,
              textInputAction: TextInputAction.next,
              decoration: const InputDecoration(
                labelText: 'Farm name',
                hintText: 'Example: North maize field',
              ),
              validator: (value) => (value?.trim().length ?? 0) < 2
                  ? 'Give this farm a name'
                  : null,
            ),
            const SizedBox(height: 14),
            TextFormField(
              controller: _locality,
              textCapitalization: TextCapitalization.words,
              textInputAction: TextInputAction.done,
              decoration: const InputDecoration(
                labelText: 'Village or local area',
              ),
              validator: (value) => (value?.trim().length ?? 0) < 2
                  ? 'Enter the nearest locality'
                  : null,
            ),
            const SizedBox(height: 14),
            DropdownButtonFormField<String>(
              initialValue: _state,
              isExpanded: true,
              menuMaxHeight: 360,
              borderRadius: BorderRadius.circular(AgriRadius.md),
              decoration: const InputDecoration(
                labelText: 'State',
                prefixIcon: Icon(Icons.map_outlined),
              ),
              items: [
                for (final state in nigerianStates)
                  DropdownMenuItem(value: state, child: Text(state)),
              ],
              onChanged: (state) => setState(() => _state = state),
              validator: (state) => state == null ? 'Choose the state' : null,
            ),
          ],
        ),
      ),
      const SizedBox(height: AgriSpacing.lg),
      FilledButton(
        onPressed: _busy ? null : _save,
        child: _busy
            ? const SizedBox.square(
                dimension: 22,
                child: CircularProgressIndicator(strokeWidth: 2),
              )
            : const Text('Register farm'),
      ),
    ],
  );
}

class _BoundaryPanel extends StatelessWidget {
  const _BoundaryPanel({
    required this.corners,
    required this.hectares,
    required this.canContinue,
    required this.onUndo,
    required this.onClear,
    required this.onContinue,
  });

  final int corners;
  final double hectares;
  final bool canContinue;
  final VoidCallback? onUndo;
  final VoidCallback? onClear;
  final VoidCallback onContinue;

  @override
  Widget build(BuildContext context) => Container(
    padding: EdgeInsets.fromLTRB(
      20,
      16,
      20,
      16 + MediaQuery.paddingOf(context).bottom,
    ),
    decoration: const BoxDecoration(
      color: AgriColors.paper,
      borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      boxShadow: [BoxShadow(color: Color(0x33000000), blurRadius: 16)],
    ),
    child: Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Row(
          children: [
            Expanded(
              child: _PanelStat(value: '$corners', label: 'Corners'),
            ),
            Expanded(
              child: _PanelStat(
                value: corners < 3 ? '–' : hectares.toStringAsFixed(2),
                label: 'Hectares (est.)',
              ),
            ),
            IconButton(
              tooltip: 'Undo last corner',
              onPressed: onUndo,
              icon: const Icon(Icons.undo_rounded),
            ),
            IconButton(
              tooltip: 'Clear all corners',
              onPressed: onClear,
              icon: const Icon(Icons.delete_outline_rounded),
            ),
          ],
        ),
        const SizedBox(height: 12),
        FilledButton(
          onPressed: canContinue ? onContinue : null,
          child: const Text('Continue'),
        ),
        const SizedBox(height: 8),
        const Text(
          satelliteCredit,
          textAlign: TextAlign.center,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: TextStyle(color: AgriColors.muted, fontSize: 10),
        ),
      ],
    ),
  );
}

class _PanelStat extends StatelessWidget {
  const _PanelStat({required this.value, required this.label});

  final String value;
  final String label;

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text(
        value,
        style: const TextStyle(
          color: AgriColors.forest,
          fontSize: 20,
          fontWeight: FontWeight.w700,
        ),
      ),
      Text(
        label,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: const TextStyle(color: AgriColors.muted, fontSize: 12),
      ),
    ],
  );
}

class _MapHint extends StatelessWidget {
  const _MapHint({
    required this.title,
    required this.message,
    required this.warning,
  });

  final String title;
  final String message;
  final bool warning;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
    decoration: BoxDecoration(
      color: warning ? AgriColors.claySoft : const Color(0xF2FCFCF8),
      borderRadius: BorderRadius.circular(AgriRadius.md),
      boxShadow: const [BoxShadow(color: Color(0x33000000), blurRadius: 10)],
    ),
    child: Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title, style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: 2),
        Text(
          message,
          style: TextStyle(
            color: warning ? AgriColors.clay : AgriColors.muted,
            fontSize: 13,
            height: 1.35,
          ),
        ),
      ],
    ),
  );
}

class _MapCircleButton extends StatelessWidget {
  const _MapCircleButton({
    required this.icon,
    required this.tooltip,
    required this.onPressed,
    this.busy = false,
  });

  final IconData icon;
  final String tooltip;
  final VoidCallback? onPressed;
  final bool busy;

  @override
  Widget build(BuildContext context) => Material(
    color: AgriColors.paper,
    shape: const CircleBorder(),
    elevation: 3,
    child: IconButton(
      tooltip: tooltip,
      color: AgriColors.forest,
      onPressed: onPressed,
      icon: busy
          ? const SizedBox.square(
              dimension: 20,
              child: CircularProgressIndicator(strokeWidth: 2),
            )
          : Icon(icon),
    ),
  );
}

class _DeviceLocationDot extends StatelessWidget {
  const _DeviceLocationDot();

  @override
  Widget build(BuildContext context) => DecoratedBox(
    decoration: BoxDecoration(
      color: const Color(0xFF2F80ED),
      shape: BoxShape.circle,
      border: Border.all(color: Colors.white, width: 3),
      boxShadow: const [
        BoxShadow(color: Color(0x552F80ED), blurRadius: 10, spreadRadius: 4),
      ],
    ),
  );
}

class FarmDetailScreen extends ConsumerStatefulWidget {
  const FarmDetailScreen({super.key, required this.farmId});
  final String farmId;
  @override
  ConsumerState<FarmDetailScreen> createState() => _FarmDetailScreenState();
}

class _FarmDetailScreenState extends ConsumerState<FarmDetailScreen> {
  late Future<
    ({
      Farm farm,
      List<WeatherDay> weather,
      List<Advisory> advisories,
      Json soil,
      bool savedFarm,
      DateTime farmUpdatedAt,
      bool weatherUnavailable,
      bool advisoriesUnavailable,
      bool soilUnavailable,
    })
  >
  _future;
  @override
  void initState() {
    super.initState();
    _load();
  }

  void _load() {
    final api = ref.read(apiClientProvider);
    _future = (() async {
      final repository = await ref.read(farmRepositoryProvider.future);
      final farmResult = await repository.detail(widget.farmId);
      var weather = <WeatherDay>[];
      var advisories = <Advisory>[];
      var soil = <String, dynamic>{};
      var weatherUnavailable = false;
      var advisoriesUnavailable = false;
      var soilUnavailable = false;

      try {
        weather = await api.weather(widget.farmId);
      } catch (_) {
        weatherUnavailable = true;
      }
      try {
        advisories = await api.advisories(widget.farmId);
      } catch (_) {
        advisoriesUnavailable = true;
      }
      try {
        soil = await api.soil(widget.farmId);
      } catch (_) {
        soilUnavailable = true;
      }

      return (
        farm: farmResult.farm,
        weather: weather,
        advisories: advisories,
        soil: soil,
        savedFarm: farmResult.isOffline,
        farmUpdatedAt: farmResult.updatedAt,
        weatherUnavailable: weatherUnavailable,
        advisoriesUnavailable: advisoriesUnavailable,
        soilUnavailable: soilUnavailable,
      );
    })();
  }

  Future<void> _sync() async {
    try {
      await ref.read(apiClientProvider).syncFarm(widget.farmId);
      if (mounted) {
        showMessage(context, 'Farm refresh started.');
        setState(_load);
      }
    } catch (error) {
      if (mounted) showMessage(context, friendlyError(error));
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(
      title: const Text('Farm briefing'),
      actions: [
        IconButton(
          onPressed: _sync,
          tooltip: 'Refresh farm data',
          icon: const Icon(Icons.sync_rounded),
        ),
      ],
    ),
    body: FutureBuilder(
      future: _future,
      builder: (context, snapshot) => ListView(
        padding: const EdgeInsets.fromLTRB(
          AgriSpacing.md,
          AgriSpacing.md,
          AgriSpacing.md,
          100,
        ),
        children: [
          if (snapshot.connectionState != ConnectionState.done)
            const Center(
              child: Padding(
                padding: EdgeInsets.all(60),
                child: CircularProgressIndicator(),
              ),
            )
          else if (snapshot.hasError)
            ErrorPanel(
              message: friendlyError(snapshot.error),
              retry: () => setState(_load),
            )
          else
            ..._detail(context, snapshot.data!),
        ],
      ),
    ),
  );

  List<Widget> _detail(
    BuildContext context,
    ({
      Farm farm,
      List<WeatherDay> weather,
      List<Advisory> advisories,
      Json soil,
      bool savedFarm,
      DateTime farmUpdatedAt,
      bool weatherUnavailable,
      bool advisoriesUnavailable,
      bool soilUnavailable,
    })
    data,
  ) {
    final today = data.weather.firstOrNull;
    return [
      _FarmHero(farm: data.farm),
      if (data.farm.boundaryGeoJson != null)
        Padding(
          padding: const EdgeInsets.only(top: AgriSpacing.md),
          child: FarmOutlineMap(
            farms: [data.farm],
            sections: data.farm.sections,
          ),
        ),
      if (data.savedFarm)
        Padding(
          padding: const EdgeInsets.only(top: AgriSpacing.md),
          child: AgriCard(
            color: AgriColors.milletSoft,
            child: Row(
              children: [
                const Icon(Icons.cloud_off_rounded, color: AgriColors.ink),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    'Saved farm details from ${data.farmUpdatedAt.toLocal()}. Reconnect to refresh.',
                  ),
                ),
              ],
            ),
          ),
        ),
      const SectionHeading('Today at this farm'),
      if (data.weatherUnavailable)
        const AgriCard(
          child: Text(
            'Weather is unavailable. Do not use older conditions for a field decision.',
          ),
        ),
      Row(
        children: [
          Expanded(
            child: _Metric(
              icon: Icons.thermostat_rounded,
              value: today?.maximum == null
                  ? '—'
                  : '${today!.maximum!.round()}°',
              label: 'High today',
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: _Metric(
              icon: Icons.water_drop_outlined,
              value: today?.rainProbability == null
                  ? '—'
                  : '${today!.rainProbability!.round()}%',
              label: 'Rain chance',
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: _Metric(
              icon: Icons.landscape_outlined,
              value: data.farm.hectares?.toStringAsFixed(1) ?? '—',
              label: 'Hectares',
            ),
          ),
        ],
      ),
      SectionHeading(
        'Farm sections',
        action: TextButton(
          onPressed: () => context.push('/farms/${data.farm.id}/sections'),
          child: const Text('Manage'),
        ),
      ),
      _FarmSectionsPreview(farm: data.farm),
      const SectionHeading('Crop actions'),
      Row(
        children: [
          Expanded(
            child: FilledButton.icon(
              onPressed: () =>
                  context.push('/diagnosis/new?farmId=${data.farm.id}'),
              icon: const Icon(Icons.camera_alt_outlined),
              label: const Text('Check crop'),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: OutlinedButton.icon(
              style: OutlinedButton.styleFrom(
                minimumSize: const Size.fromHeight(56),
              ),
              onPressed: () =>
                  context.push('/voice/new?farmId=${data.farm.id}'),
              icon: const Icon(Icons.mic_none_rounded),
              label: const Text('Ask by voice'),
            ),
          ),
        ],
      ),
      const SectionHeading('Risk and guidance'),
      if (data.advisoriesUnavailable)
        const AgriCard(
          child: Text(
            'Guidance is unavailable. Reconnect to check for new advisories.',
          ),
        )
      else if (data.advisories.isEmpty)
        const AgriCard(
          child: Text(
            'No urgent guidance. New crop and weather advice will appear after a farm sync.',
          ),
        )
      else
        ...data.advisories
            .take(5)
            .map(
              (advisory) => Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: AgriCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      StatusPill(
                        advisory.severity ?? advisory.type,
                        warning: advisory.severity == 'warning',
                      ),
                      const SizedBox(height: 10),
                      Text(
                        advisory.title,
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
                      const SizedBox(height: 4),
                      Text(
                        advisory.summary,
                        style: const TextStyle(color: AgriColors.muted),
                      ),
                    ],
                  ),
                ),
              ),
            ),
      const SectionHeading('Soil intelligence'),
      if (data.soilUnavailable)
        const AgriCard(
          child: Text(
            'Soil information is unavailable. Reconnect to check for observations.',
          ),
        )
      else
        _SoilSnapshot(data: data.soil),
    ];
  }
}

class _FarmSectionsPreview extends StatelessWidget {
  const _FarmSectionsPreview({required this.farm});

  final Farm farm;

  @override
  Widget build(BuildContext context) {
    final count = farm.sectionSummary?.count ?? farm.sections.length;
    final allocated =
        farm.sectionSummary?.allocatedHectares ??
        farm.sections.fold<double>(
          0,
          (total, section) => total + section.hectares,
        );

    return AgriCard(
      onTap: () => context.push('/farms/${farm.id}/sections'),
      color: count == 0 ? AgriColors.leafSoft : null,
      child: Row(
        children: [
          Container(
            width: 52,
            height: 52,
            decoration: BoxDecoration(
              color: count == 0 ? AgriColors.paper : AgriColors.leafSoft,
              borderRadius: BorderRadius.circular(14),
            ),
            child: const Icon(
              Icons.grid_view_rounded,
              color: AgriColors.forest,
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  count == 0
                      ? 'Divide this farm into sections'
                      : '$count growing ${count == 1 ? 'section' : 'sections'}',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: 4),
                Text(
                  count == 0
                      ? 'Assign onions, tomatoes or another crop to each area.'
                      : '${allocated.toStringAsFixed(2)} ha allocated across your crop plan',
                  style: const TextStyle(color: AgriColors.muted),
                ),
              ],
            ),
          ),
          const Icon(Icons.chevron_right_rounded),
        ],
      ),
    );
  }
}

class _FarmHero extends StatelessWidget {
  const _FarmHero({required this.farm});

  final Farm farm;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(20),
    decoration: BoxDecoration(
      color: AgriColors.forest,
      borderRadius: BorderRadius.circular(AgriRadius.lg),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'FARM INTELLIGENCE',
                    style: TextStyle(
                      color: AgriColors.millet,
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 1,
                    ),
                  ),
                  const SizedBox(height: 7),
                  Text(
                    farm.name,
                    style: Theme.of(context).textTheme.headlineMedium
                        ?.copyWith(color: Colors.white),
                  ),
                  const SizedBox(height: 5),
                  Text(
                    [farm.locality, farm.state]
                        .whereType<String>()
                        .where((value) => value.isNotEmpty)
                        .join(', '),
                    style: const TextStyle(color: Color(0xFFC4D3CB)),
                  ),
                ],
              ),
            ),
            const Icon(
              Icons.landscape_rounded,
              color: AgriColors.millet,
              size: 34,
            ),
          ],
        ),
        const SizedBox(height: 22),
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
        const SizedBox(height: 16),
        DataFreshness(
          label: farm.lastSyncedAt == null
              ? 'No field sync completed yet'
              : 'Field intelligence synchronized',
        ),
      ],
    ),
  );
}

class _SoilSnapshot extends StatelessWidget {
  const _SoilSnapshot({required this.data});

  final Json data;

  @override
  Widget build(BuildContext context) {
    final metrics = (data['metrics'] as List? ?? []).whereType<Json>().toList();
    if (metrics.isEmpty) {
      return const AgriCard(
        color: AgriColors.soilSoft,
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(Icons.science_outlined, color: AgriColors.soil),
            SizedBox(width: 12),
            Expanded(
              child: Text(
                'Soil measurements are not available yet. They appear after a supported seasonal observation.',
              ),
            ),
          ],
        ),
      );
    }

    return AgriCard(
      child: Column(
        children: [
          for (var index = 0; index < metrics.length; index++) ...[
            _SoilMetric(metric: metrics[index]),
            if (index < metrics.length - 1) const Divider(height: 24),
          ],
        ],
      ),
    );
  }
}

class _SoilMetric extends StatelessWidget {
  const _SoilMetric({required this.metric});

  final Json metric;

  @override
  Widget build(BuildContext context) {
    final value = metric['value'];
    final unit = metric['unit']?.toString() ?? '';
    final quality = metric['quality_flag']?.toString();
    return Row(
      children: [
        Container(
          width: 40,
          height: 40,
          decoration: BoxDecoration(
            color: AgriColors.soilSoft,
            borderRadius: BorderRadius.circular(AgriRadius.sm),
          ),
          child: const Icon(Icons.science_outlined, color: AgriColors.soil),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                metric['label']?.toString() ??
                    metric['metric_type']?.toString().replaceAll('_', ' ') ??
                    'Soil metric',
                style: const TextStyle(fontWeight: FontWeight.w800),
              ),
              if (quality != null)
                Text(
                  'Data quality: ${quality.replaceAll('_', ' ')}',
                  style: const TextStyle(color: AgriColors.muted, fontSize: 11),
                ),
            ],
          ),
        ),
        Text(
          value == null ? '—' : '$value $unit'.trim(),
          style: const TextStyle(
            color: AgriColors.soil,
            fontSize: 18,
            fontWeight: FontWeight.w700,
          ),
        ),
      ],
    );
  }
}

class _Metric extends StatelessWidget {
  const _Metric({required this.icon, required this.value, required this.label});
  final IconData icon;
  final String value;
  final String label;
  @override
  Widget build(BuildContext context) => AgriCard(
    padding: const EdgeInsets.all(12),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, color: AgriColors.grove),
        const SizedBox(height: 14),
        Text(value, style: Theme.of(context).textTheme.titleLarge),
        Text(
          label,
          style: const TextStyle(color: AgriColors.muted, fontSize: 12),
        ),
      ],
    ),
  );
}

/// Nigeria's 36 states and the Federal Capital Territory, alphabetically.
const nigerianStates = [
  'Abia',
  'Adamawa',
  'Akwa Ibom',
  'Anambra',
  'Bauchi',
  'Bayelsa',
  'Benue',
  'Borno',
  'Cross River',
  'Delta',
  'Ebonyi',
  'Edo',
  'Ekiti',
  'Enugu',
  'Federal Capital Territory',
  'Gombe',
  'Imo',
  'Jigawa',
  'Kaduna',
  'Kano',
  'Katsina',
  'Kebbi',
  'Kogi',
  'Kwara',
  'Lagos',
  'Nasarawa',
  'Niger',
  'Ogun',
  'Ondo',
  'Osun',
  'Oyo',
  'Plateau',
  'Rivers',
  'Sokoto',
  'Taraba',
  'Yobe',
  'Zamfara',
];
