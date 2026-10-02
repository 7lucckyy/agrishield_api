import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import 'package:go_router/go_router.dart';

import '../../app/providers.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/agri_widgets.dart';
import '../../models/models.dart';

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
  Position? _position;
  double _hectares = 1;
  bool _busy = false;
  @override
  void dispose() {
    _name.dispose();
    _locality.dispose();
    _state.dispose();
    super.dispose();
  }

  Future<void> _locate() async {
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
    setState(() => _busy = true);
    try {
      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 20),
        ),
      );
      if (mounted) setState(() => _position = position);
    } catch (_) {
      if (mounted) {
        showMessage(
          context,
          'GPS is weak here. Move into an open area and try again.',
        );
      }
    }
    if (mounted) setState(() => _busy = false);
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate() || _position == null) {
      if (_position == null) {
        showMessage(context, 'Capture the farm location before continuing.');
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
        'boundary_geojson': _squareBoundary(
          _position!.latitude,
          _position!.longitude,
          _hectares,
        ),
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
        description: 'Stand at the field. We use your GPS point and estimated size to create a simple boundary.',
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
      const SectionHeading('Farm size'),
      SegmentedButton<double>(
        segments: const [
          ButtonSegment(value: 1, label: Text('1 ha')),
          ButtonSegment(value: 3, label: Text('3 ha')),
          ButtonSegment(value: 5, label: Text('5 ha')),
        ],
        selected: {_hectares},
        onSelectionChanged: (value) => setState(() => _hectares = value.first),
      ),
      const SectionHeading('GPS position'),
      AgriCard(
        color: _position == null ? AgriColors.indigoSoft : AgriColors.leafSoft,
        child: Row(
          children: [
            Icon(
              _position == null
                  ? Icons.my_location_rounded
                  : Icons.check_circle_rounded,
              color: AgriColors.forest,
              size: 34,
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                _position == null
                    ? 'No position captured yet'
                    : '${_position!.latitude.toStringAsFixed(6)}, ${_position!.longitude.toStringAsFixed(6)}\nAccuracy ±${_position!.accuracy.toStringAsFixed(0)} m',
                style: const TextStyle(fontWeight: FontWeight.w700),
              ),
            ),
            TextButton(
              onPressed: _busy ? null : _locate,
              child: Text(_position == null ? 'Capture' : 'Retake'),
            ),
          ],
        ),
      ),
      const SizedBox(height: AgriSpacing.lg),
      FilledButton(
        onPressed: _busy ? null : _save,
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
    _future = (() async => (
      farm: await api.farm(widget.farmId),
      weather: await api.weather(widget.farmId),
      advisories: await api.advisories(widget.farmId),
      soil: await api.soil(widget.farmId),
    ))();
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
    })
    data,
  ) {
    final today = data.weather.firstOrNull;
    return [
      _FarmHero(farm: data.farm),
      const SectionHeading('Today at this farm'),
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
      if (data.advisories.isEmpty)
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
                      fontWeight: FontWeight.w900,
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
            fontWeight: FontWeight.w900,
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

Json _squareBoundary(double latitude, double longitude, double hectares) {
  final halfSideMetres = math.sqrt(hectares * 10000) / 2;
  final latitudeDelta = halfSideMetres / 111320;
  final longitudeDelta =
      halfSideMetres / (111320 * math.cos(latitude * math.pi / 180));
  final coordinates = [
    [longitude - longitudeDelta, latitude - latitudeDelta],
    [longitude + longitudeDelta, latitude - latitudeDelta],
    [longitude + longitudeDelta, latitude + latitudeDelta],
    [longitude - longitudeDelta, latitude + latitudeDelta],
    [longitude - longitudeDelta, latitude - latitudeDelta],
  ];
  return {
    'type': 'Polygon',
    'coordinates': [coordinates],
  };
}
