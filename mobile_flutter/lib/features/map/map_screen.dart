import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../app/providers.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/agri_widgets.dart';
import '../../models/models.dart';

class FarmMapScreen extends ConsumerStatefulWidget {
  const FarmMapScreen({super.key});

  @override
  ConsumerState<FarmMapScreen> createState() => _FarmMapScreenState();
}

class _FarmMapScreenState extends ConsumerState<FarmMapScreen> {
  Farm? _selectedFarm;

  @override
  Widget build(BuildContext context) {
    final farms = ref.watch(farmsProvider);
    return Scaffold(
      backgroundColor: const Color(0xFFE8ECE3),
      body: Stack(
        children: [
          Positioned.fill(
            child: farms.when(
              loading: () => const _MapSkeleton(),
              error: (error, _) => Center(
                child: Padding(
                  padding: const EdgeInsets.all(24),
                  child: ErrorPanel(
                    message: friendlyError(error),
                    retry: () => ref.invalidate(farmsProvider),
                  ),
                ),
              ),
              data: (items) => _FieldMap(
                farms: items,
                selectedFarm: _selectedFarm,
                onSelect: (farm) => setState(() => _selectedFarm = farm),
              ),
            ),
          ),
          Positioned(
            top: 0,
            left: 0,
            right: 0,
            child: SafeArea(
              bottom: false,
              child: Padding(
                padding: const EdgeInsets.all(AgriSpacing.md),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Material(
                        color: AgriColors.paper,
                        shape: RoundedRectangleBorder(
                          side: const BorderSide(color: AgriColors.line),
                          borderRadius: BorderRadius.circular(AgriRadius.sm),
                        ),
                        child: const Padding(
                          padding: EdgeInsets.symmetric(
                            horizontal: 14,
                            vertical: 12,
                          ),
                          child: Row(
                            children: [
                              Icon(
                                Icons.map_outlined,
                                color: AgriColors.forest,
                              ),
                              SizedBox(width: 10),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      'Field intelligence map',
                                      style: TextStyle(
                                        fontWeight: FontWeight.w900,
                                      ),
                                    ),
                                    Text(
                                      'Registered farm locations',
                                      style: TextStyle(
                                        color: AgriColors.muted,
                                        fontSize: 12,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Material(
                      color: AgriColors.paper,
                      shape: const CircleBorder(
                        side: BorderSide(color: AgriColors.line),
                      ),
                      child: IconButton(
                        tooltip: 'Refresh farms',
                        onPressed: () => ref.invalidate(farmsProvider),
                        icon: const Icon(Icons.refresh_rounded),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
          Positioned(
            left: 16,
            bottom: _selectedFarm == null ? 24 : 210,
            child: const _MapLegend(),
          ),
          if (_selectedFarm != null)
            Positioned(
              left: 12,
              right: 12,
              bottom: 12,
              child: _FarmSheet(
                farm: _selectedFarm!,
                onClose: () => setState(() => _selectedFarm = null),
              ),
            ),
        ],
      ),
    );
  }
}

class _FieldMap extends StatelessWidget {
  const _FieldMap({
    required this.farms,
    required this.selectedFarm,
    required this.onSelect,
  });

  final List<Farm> farms;
  final Farm? selectedFarm;
  final ValueChanged<Farm> onSelect;

  @override
  Widget build(BuildContext context) {
    if (farms.isEmpty) {
      return CustomPaint(
        painter: const _TerrainPainter(),
        child: Center(
          child: Padding(
            padding: const EdgeInsets.all(32),
            child: AgriCard(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(
                    Icons.add_location_alt_outlined,
                    size: 44,
                    color: AgriColors.forest,
                  ),
                  const SizedBox(height: 12),
                  Text(
                    'No farms on the map yet',
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  const SizedBox(height: 6),
                  const Text(
                    'Register a farm with GPS to place it in your field view.',
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 16),
                  FilledButton(
                    onPressed: () => context.push('/farms/new'),
                    child: const Text('Register a farm'),
                  ),
                ],
              ),
            ),
          ),
        ),
      );
    }

    return LayoutBuilder(
      builder: (context, constraints) => CustomPaint(
        painter: const _TerrainPainter(),
        child: Stack(
          children: [
            for (var index = 0; index < farms.length; index++)
              _FarmMarker(
                farm: farms[index],
                index: index,
                count: farms.length,
                selected: selectedFarm?.id == farms[index].id,
                availableSize: constraints.biggest,
                onTap: () => onSelect(farms[index]),
              ),
          ],
        ),
      ),
    );
  }
}

class _FarmMarker extends StatelessWidget {
  const _FarmMarker({
    required this.farm,
    required this.index,
    required this.count,
    required this.selected,
    required this.availableSize,
    required this.onTap,
  });

  final Farm farm;
  final int index;
  final int count;
  final bool selected;
  final Size availableSize;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final latitude = farm.latitude ?? (index * 1.7);
    final longitude = farm.longitude ?? (index * 2.3);
    final normalizedX =
        ((longitude + 180) / 360 + index / math.max(count, 1)) % 1;
    final normalizedY = ((90 - latitude) / 180 + index * .19) % 1;
    final left = 28 + normalizedX * math.max(availableSize.width - 104, 1);
    final top = 120 + normalizedY * math.max(availableSize.height - 310, 1);

    return Positioned(
      left: left,
      top: top,
      child: Semantics(
        button: true,
        selected: selected,
        label: '${farm.name}, ${farm.status}',
        child: InkResponse(
          onTap: onTap,
          radius: 32,
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 220),
            width: selected ? 58 : 48,
            height: selected ? 58 : 48,
            decoration: BoxDecoration(
              color: selected ? AgriColors.millet : AgriColors.forest,
              shape: BoxShape.circle,
              border: Border.all(color: AgriColors.paper, width: 4),
              boxShadow: const [
                BoxShadow(
                  color: Color(0x3317231E),
                  blurRadius: 10,
                  offset: Offset(0, 4),
                ),
              ],
            ),
            child: Icon(
              Icons.grass_rounded,
              color: selected ? AgriColors.ink : Colors.white,
              size: selected ? 28 : 23,
            ),
          ),
        ),
      ),
    );
  }
}

class _FarmSheet extends StatelessWidget {
  const _FarmSheet({required this.farm, required this.onClose});

  final Farm farm;
  final VoidCallback onClose;

  @override
  Widget build(BuildContext context) => Material(
    color: AgriColors.paper,
    elevation: 12,
    shadowColor: const Color(0x3317231E),
    borderRadius: BorderRadius.circular(AgriRadius.lg),
    child: Padding(
      padding: const EdgeInsets.fromLTRB(18, 14, 10, 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  farm.name,
                  style: Theme.of(context).textTheme.titleLarge,
                ),
              ),
              IconButton(
                tooltip: 'Close farm preview',
                onPressed: onClose,
                icon: const Icon(Icons.close_rounded),
              ),
            ],
          ),
          Text(
            [
              farm.locality,
              farm.state,
            ].whereType<String>().where((value) => value.isNotEmpty).join(', '),
            style: const TextStyle(color: AgriColors.muted),
          ),
          const SizedBox(height: 12),
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
          const SizedBox(height: 12),
          FilledButton.icon(
            onPressed: () => context.push('/farms/${farm.id}'),
            icon: const Icon(Icons.arrow_outward_rounded),
            label: const Text('Open farm intelligence'),
          ),
        ],
      ),
    ),
  );
}

class _MapLegend extends StatelessWidget {
  const _MapLegend();

  @override
  Widget build(BuildContext context) => Material(
    color: AgriColors.paper,
    shape: RoundedRectangleBorder(
      borderRadius: BorderRadius.circular(AgriRadius.sm),
      side: const BorderSide(color: AgriColors.line),
    ),
    child: const Padding(
      padding: EdgeInsets.symmetric(horizontal: 12, vertical: 9),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.circle, size: 12, color: AgriColors.forest),
          SizedBox(width: 7),
          Text(
            'Registered farm',
            style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800),
          ),
        ],
      ),
    ),
  );
}

class _MapSkeleton extends StatelessWidget {
  const _MapSkeleton();

  @override
  Widget build(BuildContext context) => const CustomPaint(
    painter: _TerrainPainter(),
    child: Center(child: CircularProgressIndicator()),
  );
}

class _TerrainPainter extends CustomPainter {
  const _TerrainPainter();

  @override
  void paint(Canvas canvas, Size size) {
    canvas.drawColor(const Color(0xFFE8ECE3), BlendMode.srcOver);
    final contour = Paint()
      ..color = const Color(0xFFBFCABD)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1;
    for (var row = 0; row < 9; row++) {
      final path = Path()..moveTo(-20, 90 + row * 70);
      for (var x = 0.0; x <= size.width + 40; x += 24) {
        final y = 90 + row * 70 + math.sin((x / 54) + row) * 15;
        path.lineTo(x, y);
      }
      canvas.drawPath(path, contour);
    }

    final field = Paint()
      ..color = const Color(0x333F7B55)
      ..style = PaintingStyle.fill;
    canvas.drawPath(
      Path()
        ..moveTo(size.width * .08, size.height * .32)
        ..lineTo(size.width * .54, size.height * .22)
        ..lineTo(size.width * .82, size.height * .48)
        ..lineTo(size.width * .36, size.height * .62)
        ..close(),
      field,
    );
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
