import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../app/providers.dart';
import '../../core/theme/app_theme.dart';
import '../../core/storage/offline_database.dart';
import '../../core/widgets/agri_widgets.dart';
import '../../models/models.dart';

class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider).value;
    final farms = ref.watch(farmsProvider);
    final firstName = auth?.user?.name.split(' ').firstOrNull ?? 'farmer';
    return AgriPage(
      children: [
        _HomeHeader(
          firstName: firstName,
          contextLabel:
              auth?.activeOrganization?.name ??
              DateFormat('EEEE, d MMMM').format(DateTime.now()),
        ),
        const _OutboxStatusCard(),
        farms.when(
          loading: () => const _HomeLoading(),
          error: (error, _) => ErrorPanel(
            message: friendlyError(error),
            retry: () => ref.invalidate(farmsProvider),
          ),
          data: (items) => items.isEmpty
              ? _NoFarm(onAdd: () => context.push('/farms/new'))
              : _IntelligenceBrief(farms: items),
        ),
      ],
    );
  }
}

class _OutboxStatusCard extends ConsumerWidget {
  const _OutboxStatusCard();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final status = ref.watch(outboxStatusProvider);
    return status.when(
      loading: () => const SizedBox.shrink(),
      error: (error, _) => AgriCard(
        child: Text(
          'Saved requests could not be checked: ${friendlyError(error)}',
        ),
      ),
      data: (operations) {
        if (operations.isEmpty) return const SizedBox.shrink();
        final unresolved = operations
            .where((operation) => operation.status != SyncState.synced)
            .toList();
        if (unresolved.isEmpty) return const SizedBox.shrink();
        final conflicts = unresolved
            .where((operation) => operation.status == SyncState.conflict)
            .length;
        final failed = unresolved
            .where((operation) => operation.status == SyncState.failed)
            .toList();
        final retryable = unresolved.any(
          (operation) => operation.status != SyncState.conflict,
        );
        return Padding(
          padding: const EdgeInsets.only(bottom: AgriSpacing.md),
          child: AgriCard(
            color: conflicts > 0 ? AgriColors.claySoft : AgriColors.milletSoft,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Saved on this device',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: 4),
                Text(
                  '${unresolved.length} request${unresolved.length == 1 ? '' : 's'} waiting or needing attention',
                ),
                const SizedBox(height: 8),
                for (final operation in unresolved.take(3))
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 3),
                    child: Text(
                      '${operation.entityType} · ${operation.status.name.toUpperCase()}${operation.error == null ? '' : ' · ${operation.error}'}',
                    ),
                  ),
                if (conflicts > 0) ...[
                  const SizedBox(height: 8),
                  const Text(
                    'A conflict will not retry automatically. Review the saved request before submitting it again.',
                  ),
                ],
                if (retryable) ...[
                  const SizedBox(height: 8),
                  OutlinedButton.icon(
                    onPressed: () async {
                      final ownerUserId = ref
                          .read(authControllerProvider)
                          .value
                          ?.user
                          ?.id;
                      if (ownerUserId == null) return;
                      final database = await ref.read(
                        offlineDatabaseProvider.future,
                      );
                      if (!context.mounted) return;
                      for (final operation in failed) {
                        await database
                            .forUser(ownerUserId)
                            .retry(operation.localId);
                      }
                      if (!context.mounted) return;
                      ref.invalidate(outboxSyncProvider);
                      ref.invalidate(outboxStatusProvider);
                    },
                    icon: const Icon(Icons.sync_rounded),
                    label: Text(
                      failed.isEmpty ? 'Check sync' : 'Retry failed uploads',
                    ),
                  ),
                ],
              ],
            ),
          ),
        );
      },
    );
  }
}

class _HomeHeader extends StatelessWidget {
  const _HomeHeader({required this.firstName, required this.contextLabel});
  final String firstName;
  final String contextLabel;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.only(bottom: AgriSpacing.lg),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                contextLabel,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  color: AgriColors.muted,
                  fontWeight: FontWeight.w600,
                  fontSize: 13,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                '${_greeting()}, $firstName',
                style: Theme.of(context).textTheme.headlineMedium,
              ),
              const SizedBox(height: 4),
              Text(
                'Here is what your farms need today.',
                style: Theme.of(context).textTheme.bodyMedium
                    ?.copyWith(color: AgriColors.muted),
              ),
            ],
          ),
        ),
        const SizedBox(width: 12),
        Tooltip(
          message: 'Open profile',
          child: Material(
            color: AgriColors.leafSoft,
            shape: const CircleBorder(),
            child: InkWell(
              customBorder: const CircleBorder(),
              onTap: () => context.go('/more'),
              child: SizedBox.square(
                dimension: 44,
                child: Center(
                  child: Text(
                    firstName.isEmpty
                        ? '?'
                        : firstName.characters.first.toUpperCase(),
                    style: const TextStyle(
                      color: AgriColors.forest,
                      fontSize: 17,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
      ],
    ),
  );
}

class _IntelligenceBrief extends ConsumerWidget {
  const _IntelligenceBrief({required this.farms});
  final List<Farm> farms;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final focusFarm = farms.first;
    final api = ref.read(apiClientProvider);
    return FutureBuilder(
      future: Future.wait<Object>([
        api.weather(focusFarm.id),
        api.advisories(focusFarm.id),
      ]),
      builder: (context, snapshot) {
        if (snapshot.hasError) {
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              ErrorPanel(
                message:
                    'Current weather and farm guidance are unavailable. '
                    'AgriShield cannot confirm that this farm is clear.',
                retry: () => ref.invalidate(farmsProvider),
              ),
              const SectionHeading('Farm overview'),
              _FarmOverview(farms: farms),
              const SectionHeading('Field actions'),
              _FieldActions(farm: focusFarm),
              const SectionHeading('Access for productivity'),
              _FinanceRow(onTap: () => context.push('/finance')),
            ],
          );
        }

        final weather = snapshot.hasData
            ? snapshot.data![0] as List<WeatherDay>
            : <WeatherDay>[];
        final advisories = snapshot.hasData
            ? snapshot.data![1] as List<Advisory>
            : <Advisory>[];
        final today = weather.firstOrNull;
        final prioritized = [...advisories]
          ..sort(
            (left, right) =>
                _severityRank(right.severity)
                    .compareTo(_severityRank(left.severity)),
          );

        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _SituationPanel(farm: focusFarm, weather: today),
            SectionHeading(
              'Needs your attention',
              action: TextButton(
                onPressed: () => context.push('/farms/${focusFarm.id}'),
                child: const Text('View farm'),
              ),
            ),
            if (snapshot.connectionState != ConnectionState.done)
              const _AttentionLoading()
            else if (prioritized.isEmpty)
              _ClearAttention(farm: focusFarm)
            else
              ...prioritized
                  .take(3)
                  .map(
                    (advisory) => Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: _AttentionItem(
                        farm: focusFarm,
                        advisory: advisory,
                      ),
                    ),
                  ),
            const SectionHeading('Farm overview'),
            _FarmOverview(farms: farms),
            const SectionHeading('Weather outlook'),
            _WeatherStrip(weather: weather),
            const SectionHeading('Field actions'),
            _FieldActions(farm: focusFarm),
            const SectionHeading('Access for productivity'),
            _FinanceRow(onTap: () => context.push('/finance')),
          ],
        );
      },
    );
  }
}

class _SituationPanel extends StatelessWidget {
  const _SituationPanel({required this.farm, required this.weather});
  final Farm farm;
  final WeatherDay? weather;

  @override
  Widget build(BuildContext context) {
    final hasRain = (weather?.rainProbability ?? 0) >= 60;
    return Material(
      color: AgriColors.forest,
      borderRadius: BorderRadius.circular(AgriRadius.lg),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () => context.push('/farms/${farm.id}'),
        child: Stack(
          children: [
            const Positioned.fill(
              child: CustomPaint(painter: _ContourPainter()),
            ),
            Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'FIELD IN FOCUS',
                              style: TextStyle(
                                color: AgriColors.millet,
                                fontWeight: FontWeight.w700,
                                letterSpacing: .8,
                                fontSize: 11,
                              ),
                            ),
                            const SizedBox(height: 6),
                            Text(
                              farm.name,
                              style: Theme.of(context).textTheme.titleLarge
                                  ?.copyWith(color: Colors.white),
                            ),
                            Text(
                              [farm.locality, farm.state]
                                  .whereType<String>()
                                  .where((value) => value.isNotEmpty)
                                  .join(', '),
                              style: const TextStyle(
                                color: Color(0xFFC7D4CD),
                                fontSize: 13,
                              ),
                            ),
                          ],
                        ),
                      ),
                      Icon(
                        hasRain
                            ? Icons.water_drop_outlined
                            : Icons.wb_sunny_outlined,
                        color: hasRain
                            ? const Color(0xFF9FD5E3)
                            : AgriColors.millet,
                        size: 28,
                      ),
                    ],
                  ),
                  const SizedBox(height: 22),
                  Row(
                    children: [
                      _Signal(
                        label: 'TEMPERATURE',
                        value: weather?.maximum == null
                            ? 'Waiting'
                            : '${weather!.maximum!.round()}°C',
                      ),
                      _Signal(
                        label: 'RAIN CHANCE',
                        value: weather?.rainProbability == null
                            ? 'Waiting'
                            : '${weather!.rainProbability!.round()}%',
                      ),
                      _Signal(
                        label: 'CROP',
                        value: farm.activeCropCycle?.cropName ?? 'Not set',
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  DataFreshness(
                    label: farm.lastSyncedAt == null
                        ? 'Waiting for first field sync'
                        : 'Farm data synchronized',
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Signal extends StatelessWidget {
  const _Signal({required this.label, required this.value});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => Expanded(
    child: Padding(
      padding: const EdgeInsets.only(right: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(
              color: Color(0xFF9FB4A9),
              fontSize: 10,
              fontWeight: FontWeight.w600,
              letterSpacing: .5,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 16,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    ),
  );
}

class _AttentionItem extends StatelessWidget {
  const _AttentionItem({required this.farm, required this.advisory});
  final Farm farm;
  final Advisory advisory;

  @override
  Widget build(BuildContext context) {
    final style = severityStyle(advisory.severity);
    return AgriCard(
      onTap: () => context.push('/farms/${farm.id}'),
      padding: EdgeInsets.zero,
      child: IntrinsicHeight(
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Container(width: 5, color: style.foreground),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        StatusPill(advisory.severity ?? advisory.type),
                        const Spacer(),
                        Flexible(
                          child: Text(
                            farm.name,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              color: AgriColors.muted,
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    Text(
                      advisory.title,
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                    const SizedBox(height: 5),
                    Text(
                      advisory.summary,
                      maxLines: 3,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: AgriColors.muted,
                        fontSize: 13,
                      ),
                    ),
                    const SizedBox(height: 12),
                    const Row(
                      children: [
                        Flexible(
                          child: Text(
                            'Review evidence and next step',
                            style: TextStyle(
                              color: AgriColors.grove,
                              fontWeight: FontWeight.w700,
                              fontSize: 13,
                            ),
                          ),
                        ),
                        SizedBox(width: 4),
                        Icon(
                          Icons.arrow_forward_rounded,
                          size: 17,
                          color: AgriColors.grove,
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ClearAttention extends StatelessWidget {
  const _ClearAttention({required this.farm});
  final Farm farm;

  @override
  Widget build(BuildContext context) => AgriCard(
    color: AgriColors.leafSoft,
    onTap: () => context.push('/farms/${farm.id}'),
    child: const Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(Icons.check_circle_outline_rounded, color: AgriColors.grove),
        SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'No active warnings',
                style: TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
              ),
              SizedBox(height: 3),
              Text(
                'There is no urgent guidance for this farm. Check field conditions before major work.',
                style: TextStyle(color: AgriColors.muted, fontSize: 13),
              ),
            ],
          ),
        ),
      ],
    ),
  );
}

class _FarmOverview extends StatelessWidget {
  const _FarmOverview({required this.farms});
  final List<Farm> farms;

  @override
  Widget build(BuildContext context) {
    final hectares = farms.fold<double>(
      0,
      (total, farm) => total + (farm.hectares ?? 0),
    );
    final activeCrops = farms
        .map((farm) => farm.activeCropCycle?.cropName)
        .whereType<String>()
        .toSet()
        .length;
    return AgriCard(
      onTap: () => context.go('/farms'),
      child: Row(
        children: [
          _OverviewValue(value: '${farms.length}', label: 'FARMS'),
          const _OverviewDivider(),
          _OverviewValue(value: hectares.toStringAsFixed(1), label: 'HECTARES'),
          const _OverviewDivider(),
          _OverviewValue(value: '$activeCrops', label: 'ACTIVE CROPS'),
          const Icon(Icons.chevron_right_rounded, color: AgriColors.muted),
        ],
      ),
    );
  }
}

class _OverviewValue extends StatelessWidget {
  const _OverviewValue({required this.value, required this.label});
  final String value;
  final String label;

  @override
  Widget build(BuildContext context) => Expanded(
    child: Column(
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
        const SizedBox(height: 2),
        Text(
          label,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(
            color: AgriColors.muted,
            fontSize: 10,
            fontWeight: FontWeight.w600,
            letterSpacing: .4,
          ),
        ),
      ],
    ),
  );
}

class _OverviewDivider extends StatelessWidget {
  const _OverviewDivider();

  @override
  Widget build(BuildContext context) =>
      const SizedBox(height: 38, child: VerticalDivider(width: 18));
}

class _WeatherStrip extends StatelessWidget {
  const _WeatherStrip({required this.weather});
  final List<WeatherDay> weather;

  @override
  Widget build(BuildContext context) {
    if (weather.isEmpty) {
      return const AgriCard(
        child: Row(
          children: [
            Icon(Icons.cloud_sync_outlined, color: AgriColors.sky),
            SizedBox(width: 12),
            Expanded(
              child: Text('Weather will appear after the next farm sync.'),
            ),
          ],
        ),
      );
    }
    return SizedBox(
      height: 124,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: weather.take(7).length,
        separatorBuilder: (_, _) => const SizedBox(width: 8),
        itemBuilder: (context, index) {
          final day = weather[index];
          final date = DateTime.tryParse(day.date);
          return Container(
            width: 88,
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: index == 0 ? AgriColors.sky : AgriColors.paper,
              borderRadius: BorderRadius.circular(AgriRadius.md),
              border: Border.all(
                color: index == 0 ? AgriColors.sky : AgriColors.line,
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  index == 0
                      ? 'TODAY'
                      : date == null
                      ? 'DAY'
                      : DateFormat('EEE').format(date).toUpperCase(),
                  style: TextStyle(
                    color: index == 0 ? Colors.white70 : AgriColors.muted,
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                    letterSpacing: .6,
                  ),
                ),
                const Spacer(),
                Icon(
                  (day.rainProbability ?? 0) >= 50
                      ? Icons.water_drop_outlined
                      : Icons.wb_sunny_outlined,
                  color: index == 0 ? Colors.white : AgriColors.sky,
                  size: 21,
                ),
                const SizedBox(height: 4),
                Text(
                  day.maximum == null ? '—' : '${day.maximum!.round()}°',
                  style: TextStyle(
                    color: index == 0 ? Colors.white : AgriColors.ink,
                    fontSize: 17,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                Text(
                  '${day.rainProbability?.round() ?? 0}% rain',
                  style: TextStyle(
                    color: index == 0 ? Colors.white70 : AgriColors.muted,
                    fontSize: 10,
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _FieldActions extends StatelessWidget {
  const _FieldActions({required this.farm});
  final Farm farm;

  @override
  Widget build(BuildContext context) => Row(
    children: [
      Expanded(
        child: _ActionButton(
          icon: Icons.camera_alt_outlined,
          label: 'Check crop',
          detail: 'Photo diagnosis',
          color: AgriColors.soilSoft,
          onTap: () => context.push('/diagnosis/new?farmId=${farm.id}'),
        ),
      ),
      const SizedBox(width: 10),
      Expanded(
        child: _ActionButton(
          icon: Icons.graphic_eq_rounded,
          label: 'Ask by voice',
          detail: 'Four languages',
          color: AgriColors.waterSoft,
          onTap: () => context.push('/voice/new?farmId=${farm.id}'),
        ),
      ),
    ],
  );
}

class _ActionButton extends StatelessWidget {
  const _ActionButton({
    required this.icon,
    required this.label,
    required this.detail,
    required this.color,
    required this.onTap,
  });
  final IconData icon;
  final String label;
  final String detail;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => Material(
    color: color,
    borderRadius: BorderRadius.circular(AgriRadius.md),
    child: InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(AgriRadius.md),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              padding: const EdgeInsets.all(9),
              decoration: const BoxDecoration(
                color: Color(0xB3FFFFFF),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: AgriColors.forest, size: 20),
            ),
            const SizedBox(height: 18),
            Text(
              label,
              style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14),
            ),
            const SizedBox(height: 2),
            Text(
              detail,
              style: const TextStyle(color: AgriColors.muted, fontSize: 12),
            ),
          ],
        ),
      ),
    ),
  );
}

class _FinanceRow extends StatelessWidget {
  const _FinanceRow({required this.onTap});
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => AgriCard(
    onTap: onTap,
    child: const Row(
      children: [
        Icon(Icons.agriculture_outlined, color: AgriColors.indigo, size: 26),
        SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Equipment and input programmes',
                style: TextStyle(fontWeight: FontWeight.w700),
              ),
              SizedBox(height: 3),
              Text(
                'Explore verified options for registered farms.',
                style: TextStyle(color: AgriColors.muted, fontSize: 12),
              ),
            ],
          ),
        ),
        Icon(Icons.chevron_right_rounded, color: AgriColors.muted),
      ],
    ),
  );
}

class _NoFarm extends StatelessWidget {
  const _NoFarm({required this.onAdd});
  final VoidCallback onAdd;

  @override
  Widget build(BuildContext context) => AgriCard(
    color: AgriColors.leafSoft,
    child: Column(
      children: [
        const Icon(
          Icons.add_location_alt_outlined,
          color: AgriColors.forest,
          size: 52,
        ),
        const SizedBox(height: 12),
        Text(
          'Start with your first farm',
          style: Theme.of(context).textTheme.titleLarge,
        ),
        const SizedBox(height: 6),
        const Text(
          'Register a field location to begin receiving weather, soil and crop guidance.',
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 18),
        FilledButton(onPressed: onAdd, child: const Text('Register my farm')),
      ],
    ),
  );
}

class _HomeLoading extends StatelessWidget {
  const _HomeLoading();

  @override
  Widget build(BuildContext context) => const Column(
    children: [
      LinearProgressIndicator(),
      SizedBox(height: 12),
      AgriCard(child: SizedBox(height: 170)),
    ],
  );
}

class _AttentionLoading extends StatelessWidget {
  const _AttentionLoading();

  @override
  Widget build(BuildContext context) => const AgriCard(
    child: Row(
      children: [
        SizedBox.square(
          dimension: 22,
          child: CircularProgressIndicator(strokeWidth: 2),
        ),
        SizedBox(width: 12),
        Expanded(child: Text('Checking current farm guidance…')),
      ],
    ),
  );
}

class _ContourPainter extends CustomPainter {
  const _ContourPainter();

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = const Color(0x1FFFFFFF)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1;
    for (var index = 0; index < 5; index++) {
      canvas.drawOval(
        Rect.fromCenter(
          center: Offset(size.width * .92, size.height * .36),
          width: 90.0 + index * 44,
          height: 55.0 + index * 32,
        ),
        paint,
      );
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

int _severityRank(String? severity) => switch (severity?.toLowerCase()) {
  'critical' => 4,
  'high' => 3,
  'moderate' || 'warning' => 2,
  _ => 1,
};

String _greeting() {
  final hour = DateTime.now().hour;
  if (hour < 12) return 'Good morning';
  if (hour < 17) return 'Good afternoon';
  return 'Good evening';
}
