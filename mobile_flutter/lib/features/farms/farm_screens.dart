import 'dart:async';
import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import 'package:go_router/go_router.dart';

import '../../app/providers.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/agri_widgets.dart';
import '../../models/models.dart';
import '../map/map_screen.dart';
import 'farm_boundary.dart';

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
                        'Stand at the farm, capture your position and choose an estimated field size.',
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
  const AddFarmScreen({super.key});
  @override
  ConsumerState<AddFarmScreen> createState() => _AddFarmScreenState();
}

class _AddFarmScreenState extends ConsumerState<AddFarmScreen> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _locality = TextEditingController();
  final _state = TextEditingController();
  StreamSubscription<Position>? _walkSubscription;
  final List<BoundaryPoint> _perimeter = [];
  double? _lastAccuracy;
  bool _walking = false;
  bool _paused = false;
  bool _finished = false;
  bool _busy = false;
  @override
  void dispose() {
    _walkSubscription?.cancel();
    _name.dispose();
    _locality.dispose();
    _state.dispose();
    super.dispose();
  }

  Future<void> _startWalk() async {
    final enabled = await Geolocator.isLocationServiceEnabled();
    if (!enabled && mounted) {
      showMessage(context, 'Turn on Location Services, then try again.');
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
          'Location permission is needed to register this field.',
        );
      }
      return;
    }
    await _walkSubscription?.cancel();
    setState(() {
      _perimeter.clear();
      _lastAccuracy = null;
      _walking = true;
      _paused = false;
      _finished = false;
    });
    _walkSubscription =
        Geolocator.getPositionStream(
          locationSettings: const LocationSettings(
            accuracy: LocationAccuracy.high,
            distanceFilter: 5,
          ),
        ).listen(
          _recordPosition,
          onError: (Object error) {
            if (!mounted) return;
            setState(() => _walking = false);
            showMessage(
              context,
              'GPS tracking stopped. Check location access and try again.',
            );
          },
        );
  }

  void _recordPosition(Position position) {
    if (!mounted || !_walking || _paused) return;
    setState(() => _lastAccuracy = position.accuracy);
    if (position.accuracy > 30) return;
    if (_perimeter.isNotEmpty &&
        Geolocator.distanceBetween(
              _perimeter.last.latitude,
              _perimeter.last.longitude,
              position.latitude,
              position.longitude,
            ) <
            4) {
      return;
    }
    setState(
      () =>
          _perimeter.add(BoundaryPoint(position.latitude, position.longitude)),
    );
  }

  Future<void> _finishWalk() async {
    final boundary = FarmBoundary(_perimeter);
    if (boundary.hasSelfIntersection) {
      showMessage(
        context,
        'The GPS trail crosses itself. Walk the perimeter again.',
      );
      return;
    }
    if (!boundary.isValid) {
      showMessage(
        context,
        'Walk at least three distinct corners before finishing.',
      );
      return;
    }
    await _walkSubscription?.cancel();
    _walkSubscription = null;
    if (mounted) {
      setState(() {
        _walking = false;
        _finished = true;
      });
    }
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate() || !_finished) {
      if (!_finished) {
        showMessage(context, 'Finish a GPS perimeter walk before registering.');
      }
      return;
    }
    setState(() => _busy = true);
    try {
      await ref.read(apiClientProvider).createFarm({
        'name': _name.text.trim(),
        'locality': _locality.text.trim(),
        'state': _state.text.trim(),
        'country': 'NG',
        'boundary_geojson': FarmBoundary(_perimeter).toGeoJson(),
      });
      ref.invalidate(farmsProvider);
      if (mounted) {
        showMessage(context, 'Farm registered successfully.');
        context.pop();
      }
    } catch (error) {
      if (mounted) showMessage(context, friendlyError(error));
    }
    if (mounted) setState(() => _busy = false);
  }

  @override
  Widget build(BuildContext context) => AgriPage(
    appBar: AppBar(title: const Text('Add a crop farm')),
    children: [
      const PageHeading(
        eyebrow: 'Step 1 of 1',
        title: 'Where is your field?',
        description: 'Walk the farm perimeter with GPS on. Only positions with accuracy within 30 m are recorded.',
      ),
      Form(
        key: _formKey,
        child: Column(
          children: [
            TextFormField(
              controller: _name,
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
              decoration: const InputDecoration(
                labelText: 'Village or local area',
              ),
              validator: (value) => (value?.trim().length ?? 0) < 2
                  ? 'Enter the nearest locality'
                  : null,
            ),
            const SizedBox(height: 14),
            TextFormField(
              controller: _state,
              decoration: const InputDecoration(
                labelText: 'State',
                hintText: 'Kano',
              ),
              validator: (value) =>
                  (value?.trim().length ?? 0) < 2 ? 'Enter the state' : null,
            ),
          ],
        ),
      ),
      const SectionHeading('Walk the boundary'),
      AgriCard(
        color: _finished ? AgriColors.leafSoft : AgriColors.indigoSoft,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              _finished
                  ? 'Boundary ready to confirm'
                  : _walking
                  ? (_paused ? 'Walk paused' : 'Recording perimeter')
                  : 'No boundary recorded',
              style: const TextStyle(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 8),
            Text(
              '${_perimeter.length} points · ${FarmBoundary(_perimeter).hectares.toStringAsFixed(2)} estimated ha',
            ),
            if (_lastAccuracy != null)
              Text(
                'Latest GPS accuracy ±${_lastAccuracy!.toStringAsFixed(0)} m${_lastAccuracy! > 30 ? ' — too weak; point skipped' : ''}',
                style: TextStyle(
                  color: _lastAccuracy! > 30
                      ? AgriColors.clay
                      : AgriColors.grove,
                ),
              ),
            if (_perimeter.isNotEmpty) ...[
              const SizedBox(height: 12),
              _BoundaryPreview(points: _perimeter),
            ],
            const SizedBox(height: 12),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                if (!_walking)
                  OutlinedButton(
                    onPressed: _busy ? null : _startWalk,
                    child: Text(_finished ? 'Start again' : 'Start walk'),
                  ),
                if (_walking)
                  OutlinedButton(
                    onPressed: () {
                      setState(() => _paused = !_paused);
                      if (_paused) {
                        _walkSubscription?.pause();
                      } else {
                        _walkSubscription?.resume();
                      }
                    },
                    child: Text(_paused ? 'Resume' : 'Pause'),
                  ),
                if (_walking)
                  FilledButton(
                    onPressed: _finishWalk,
                    child: const Text('Finish walk'),
                  ),
              ],
            ),
          ],
        ),
      ),
      const SizedBox(height: 8),
      const Text(
        'GPS area is an estimate, not a land survey. Check the outline before saving.',
      ),
      const SizedBox(height: AgriSpacing.lg),
      FilledButton(
        onPressed: _busy || !_finished ? null : _save,
        child: _busy
            ? const CircularProgressIndicator()
            : const Text('Register farm'),
      ),
    ],
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

class _BoundaryPreview extends StatelessWidget {
  const _BoundaryPreview({required this.points});

  final List<BoundaryPoint> points;

  @override
  Widget build(BuildContext context) => Container(
    height: 180,
    width: double.infinity,
    decoration: BoxDecoration(
      color: AgriColors.canvas,
      borderRadius: BorderRadius.circular(AgriRadius.sm),
    ),
    child: CustomPaint(painter: _BoundaryPainter(points)),
  );
}

class _BoundaryPainter extends CustomPainter {
  const _BoundaryPainter(this.points);

  final List<BoundaryPoint> points;

  @override
  void paint(Canvas canvas, Size size) {
    if (points.isEmpty) return;
    final origin = points.first;
    final longitudeScale = math.cos(origin.latitude * math.pi / 180);
    final east = points
        .map((point) => (point.longitude - origin.longitude) * longitudeScale)
        .toList();
    final north = points
        .map((point) => point.latitude - origin.latitude)
        .toList();
    final minEast = east.reduce(math.min);
    final maxEast = east.reduce(math.max);
    final minNorth = north.reduce(math.min);
    final maxNorth = north.reduce(math.max);
    final span = math.max(
      math.max(maxEast - minEast, maxNorth - minNorth),
      0.00001,
    );
    final scale = math.min(size.width - 32, size.height - 32) / span;
    final offsets = List.generate(
      points.length,
      (index) => Offset(
        (east[index] - minEast) * scale + 16,
        size.height - ((north[index] - minNorth) * scale + 16),
      ),
    );
    final path = Path()..moveTo(offsets.first.dx, offsets.first.dy);
    for (final point in offsets.skip(1)) {
      path.lineTo(point.dx, point.dy);
    }
    if (offsets.length >= 3) path.close();
    if (offsets.length >= 3) {
      canvas.drawPath(path, Paint()..color = AgriColors.leafSoft);
    }
    canvas.drawPath(
      path,
      Paint()
        ..color = AgriColors.forest
        ..style = PaintingStyle.stroke
        ..strokeWidth = 2.5,
    );
    for (final point in offsets) {
      canvas.drawCircle(point, 4, Paint()..color = AgriColors.forest);
    }
  }

  @override
  bool shouldRepaint(covariant _BoundaryPainter oldDelegate) => true;
}
