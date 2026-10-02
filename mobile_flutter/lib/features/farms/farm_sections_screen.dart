import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../app/providers.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/agri_widgets.dart';
import '../../models/models.dart';

class FarmSectionsScreen extends ConsumerStatefulWidget {
  const FarmSectionsScreen({super.key, required this.farmId});

  final String farmId;

  @override
  ConsumerState<FarmSectionsScreen> createState() => _FarmSectionsScreenState();
}

class _FarmSectionsScreenState extends ConsumerState<FarmSectionsScreen> {
  late Future<Farm> _future;

  @override
  void initState() {
    super.initState();
    _load();
  }

  void _load() {
    _future = ref.read(apiClientProvider).farm(widget.farmId);
  }

  Future<void> _openForm([FarmSection? section]) async {
    final path = section == null
        ? '/farms/${widget.farmId}/sections/new'
        : '/farms/${widget.farmId}/sections/${section.id}/edit';
    final changed = await context.push<bool>(path);
    if (changed == true && mounted) {
      setState(_load);
      ref.invalidate(farmsProvider);
    }
  }

  Future<void> _delete(FarmSection section) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Remove ${section.name}?'),
        content: Text(
          'This removes the ${section.crop.name} allocation from this farm. The farm itself will not be deleted.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Keep section'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(
              backgroundColor: AgriColors.critical,
              minimumSize: const Size(0, 48),
            ),
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Remove'),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;

    try {
      await ref
          .read(apiClientProvider)
          .deleteFarmSection(widget.farmId, section.id);
      if (mounted) {
        showMessage(context, '${section.name} removed.');
        setState(_load);
        ref.invalidate(farmsProvider);
      }
    } catch (error) {
      if (mounted) showMessage(context, friendlyError(error));
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('Farm sections')),
    floatingActionButton: FloatingActionButton.extended(
      onPressed: _openForm,
      icon: const Icon(Icons.add_rounded),
      label: const Text('Add section'),
    ),
    body: FutureBuilder<Farm>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snapshot.hasError) {
          return ListView(
            padding: const EdgeInsets.all(AgriSpacing.md),
            children: [
              ErrorPanel(
                message: friendlyError(snapshot.error),
                retry: () => setState(_load),
              ),
            ],
          );
        }

        final farm = snapshot.data!;
        return ListView(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 112),
          children: [
            _AllocationHeader(farm: farm),
            const SizedBox(height: 24),
            Row(
              children: [
                Expanded(
                  child: Text(
                    'Planted sections',
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                ),
                Text(
                  '${farm.sections.length} total',
                  style: const TextStyle(
                    color: AgriColors.muted,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            if (farm.sections.isEmpty)
              _EmptySections(onAdd: _openForm)
            else
              ...farm.sections.map(
                (section) => Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: _SectionCard(
                    section: section,
                    onEdit: () => _openForm(section),
                    onDelete: () => _delete(section),
                  ),
                ),
              ),
          ],
        );
      },
    ),
  );
}

class _AllocationHeader extends StatelessWidget {
  const _AllocationHeader({required this.farm});

  final Farm farm;

  @override
  Widget build(BuildContext context) {
    final allocated =
        farm.sectionSummary?.allocatedHectares ??
        farm.sections.fold<double>(
          0,
          (total, section) => total + section.hectares,
        );
    final total = farm.hectares;
    final remaining =
        farm.sectionSummary?.remainingHectares ??
        (total == null ? null : (total - allocated).clamp(0, total));
    final progress = total == null || total <= 0
        ? 0.0
        : (allocated / total).clamp(0, 1).toDouble();

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AgriColors.forest,
        borderRadius: BorderRadius.circular(AgriRadius.lg),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'FIELD PLAN',
            style: TextStyle(
              color: AgriColors.millet,
              fontSize: 10,
              fontWeight: FontWeight.w900,
              letterSpacing: 1.1,
            ),
          ),
          const SizedBox(height: 7),
          Text(
            farm.name,
            style: Theme.of(context).textTheme.headlineMedium
                ?.copyWith(color: Colors.white),
          ),
          const SizedBox(height: 8),
          const Text(
            'Divide this farm into practical growing areas and assign one crop to each section.',
            style: TextStyle(color: Color(0xFFC4D3CB), height: 1.45),
          ),
          const SizedBox(height: 22),
          ClipRRect(
            borderRadius: BorderRadius.circular(99),
            child: LinearProgressIndicator(
              minHeight: 10,
              value: progress,
              backgroundColor: Colors.white.withValues(alpha: .14),
              valueColor: const AlwaysStoppedAnimation(AgriColors.millet),
            ),
          ),
          const SizedBox(height: 14),
          Row(
            children: [
              Expanded(
                child: _AllocationMetric(
                  label: 'Allocated',
                  value: '${allocated.toStringAsFixed(2)} ha',
                ),
              ),
              Expanded(
                child: _AllocationMetric(
                  label: 'Available',
                  value: remaining == null
                      ? 'Not set'
                      : '${remaining.toStringAsFixed(2)} ha',
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _AllocationMetric extends StatelessWidget {
  const _AllocationMetric({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text(
        label.toUpperCase(),
        style: const TextStyle(
          color: Color(0xFF9EB6AA),
          fontSize: 9,
          fontWeight: FontWeight.w900,
          letterSpacing: .9,
        ),
      ),
      const SizedBox(height: 3),
      Text(
        value,
        style: const TextStyle(
          color: Colors.white,
          fontSize: 18,
          fontWeight: FontWeight.w900,
        ),
      ),
    ],
  );
}

class _EmptySections extends StatelessWidget {
  const _EmptySections({required this.onAdd});

  final VoidCallback onAdd;

  @override
  Widget build(BuildContext context) => AgriCard(
    color: AgriColors.leafSoft,
    child: Column(
      children: [
        const Icon(Icons.grid_view_rounded, size: 44, color: AgriColors.forest),
        const SizedBox(height: 12),
        Text(
          'Plan your first section',
          style: Theme.of(context).textTheme.titleLarge,
        ),
        const SizedBox(height: 6),
        const Text(
          'Start with Section A, choose its size and assign the crop you want to grow there.',
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 16),
        FilledButton.icon(
          onPressed: onAdd,
          icon: const Icon(Icons.add_rounded),
          label: const Text('Create first section'),
        ),
      ],
    ),
  );
}

class _SectionCard extends StatelessWidget {
  const _SectionCard({
    required this.section,
    required this.onEdit,
    required this.onDelete,
  });

  final FarmSection section;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  @override
  Widget build(BuildContext context) => AgriCard(
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Container(
              width: 46,
              height: 46,
              decoration: BoxDecoration(
                color: AgriColors.leafSoft,
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(Icons.eco_rounded, color: AgriColors.grove),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    section.name,
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  const SizedBox(height: 2),
                  Text(
                    section.crop.name,
                    style: const TextStyle(
                      color: AgriColors.grove,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ],
              ),
            ),
            PopupMenuButton<String>(
              tooltip: 'Section options',
              onSelected: (value) => value == 'edit' ? onEdit() : onDelete(),
              itemBuilder: (_) => const [
                PopupMenuItem(value: 'edit', child: Text('Edit section')),
                PopupMenuItem(value: 'delete', child: Text('Remove section')),
              ],
            ),
          ],
        ),
        const Divider(height: 26),
        Row(
          children: [
            Expanded(
              child: Text(
                '${section.hectares.toStringAsFixed(2)} hectares',
                style: const TextStyle(fontWeight: FontWeight.w900),
              ),
            ),
            if (section.farmPercentage != null)
              Text(
                '${section.farmPercentage!.toStringAsFixed(1)}% of farm',
                style: const TextStyle(
                  color: AgriColors.muted,
                  fontWeight: FontWeight.w700,
                ),
              ),
          ],
        ),
        if (section.notes case final notes? when notes.isNotEmpty) ...[
          const SizedBox(height: 9),
          Text(notes, style: const TextStyle(color: AgriColors.muted)),
        ],
      ],
    ),
  );
}

class FarmSectionFormScreen extends ConsumerStatefulWidget {
  const FarmSectionFormScreen({
    super.key,
    required this.farmId,
    this.sectionId,
  });

  final String farmId;
  final int? sectionId;

  @override
  ConsumerState<FarmSectionFormScreen> createState() =>
      _FarmSectionFormScreenState();
}

class _FarmSectionFormScreenState extends ConsumerState<FarmSectionFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _area = TextEditingController();
  final _notes = TextEditingController();
  late Future<({Farm farm, List<Crop> crops})> _future;
  int? _cropId;
  bool _initialized = false;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  void _load() {
    final api = ref.read(apiClientProvider);
    _future = (() async =>
        (farm: await api.farm(widget.farmId), crops: await api.crops()))();
  }

  @override
  void dispose() {
    _name.dispose();
    _area.dispose();
    _notes.dispose();
    super.dispose();
  }

  void _initialize(Farm farm) {
    if (_initialized) return;
    _initialized = true;
    final section = farm.sections
        .where((item) => item.id == widget.sectionId)
        .firstOrNull;
    if (section == null) {
      final nextNumber = farm.sections.length + 1;
      final suffix = nextNumber <= 26
          ? String.fromCharCode(64 + nextNumber)
          : nextNumber.toString();
      _name.text = 'Section $suffix';
      return;
    }
    _name.text = section.name;
    _area.text = section.hectares.toString();
    _notes.text = section.notes ?? '';
    _cropId = section.crop.id;
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate() || _cropId == null) return;
    setState(() => _busy = true);
    final payload = <String, dynamic>{
      'name': _name.text.trim(),
      'crop_id': _cropId,
      'area_hectares': double.parse(_area.text.trim()),
      'notes': _notes.text.trim().isEmpty ? null : _notes.text.trim(),
    };
    try {
      final api = ref.read(apiClientProvider);
      if (widget.sectionId == null) {
        await api.createFarmSection(widget.farmId, payload);
      } else {
        await api.updateFarmSection(widget.farmId, widget.sectionId!, payload);
      }
      if (mounted) context.pop(true);
    } catch (error) {
      if (mounted) showMessage(context, friendlyError(error));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(
      title: Text(
        widget.sectionId == null ? 'Add farm section' : 'Edit section',
      ),
    ),
    body: FutureBuilder<({Farm farm, List<Crop> crops})>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snapshot.hasError) {
          return ListView(
            padding: const EdgeInsets.all(AgriSpacing.md),
            children: [
              ErrorPanel(
                message: friendlyError(snapshot.error),
                retry: () => setState(_load),
              ),
            ],
          );
        }

        final data = snapshot.data!;
        _initialize(data.farm);
        final current = data.farm.sections
            .where((item) => item.id == widget.sectionId)
            .firstOrNull;
        final remaining =
            data.farm.sectionSummary?.remainingHectares ?? data.farm.hectares;
        final available = remaining == null
            ? null
            : remaining + (current?.hectares ?? 0);

        return Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 40),
            children: [
              PageHeading(
                eyebrow: data.farm.name,
                title: widget.sectionId == null
                    ? 'Plan a growing section'
                    : 'Update this section',
                description: 'Give the section a clear label, choose its crop and allocate part of the farm area.',
              ),
              TextFormField(
                controller: _name,
                textCapitalization: TextCapitalization.words,
                decoration: const InputDecoration(
                  labelText: 'Section name',
                  hintText: 'e.g. Section A',
                  prefixIcon: Icon(Icons.grid_view_rounded),
                ),
                validator: (value) => value == null || value.trim().isEmpty
                    ? 'Enter a section name.'
                    : null,
              ),
              const SizedBox(height: 14),
              DropdownButtonFormField<int>(
                initialValue: _cropId,
                decoration: const InputDecoration(
                  labelText: 'Crop to grow',
                  prefixIcon: Icon(Icons.eco_outlined),
                ),
                items: data.crops
                    .map(
                      (crop) => DropdownMenuItem(
                        value: crop.id,
                        child: Text(crop.name),
                      ),
                    )
                    .toList(),
                onChanged: _busy ? null : (value) => _cropId = value,
                validator: (value) => value == null ? 'Choose a crop.' : null,
              ),
              const SizedBox(height: 14),
              TextFormField(
                controller: _area,
                keyboardType: const TextInputType.numberWithOptions(
                  decimal: true,
                ),
                inputFormatters: [
                  FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,4}')),
                ],
                decoration: InputDecoration(
                  labelText: 'Section size',
                  suffixText: 'hectares',
                  prefixIcon: const Icon(Icons.straighten_rounded),
                  helperText: available == null
                      ? 'Farm area is not set'
                      : '${available.toStringAsFixed(2)} ha available',
                ),
                validator: (value) {
                  final parsed = double.tryParse(value?.trim() ?? '');
                  if (parsed == null || parsed <= 0) {
                    return 'Enter an area greater than zero.';
                  }
                  if (available != null && parsed > available + .0001) {
                    return 'This is larger than the available farm area.';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 14),
              TextFormField(
                controller: _notes,
                minLines: 3,
                maxLines: 5,
                textCapitalization: TextCapitalization.sentences,
                decoration: const InputDecoration(
                  labelText: 'Notes (optional)',
                  hintText: 'Irrigation, planting plan or landmarks',
                  alignLabelWithHint: true,
                ),
              ),
              const SizedBox(height: 24),
              FilledButton.icon(
                onPressed: _busy ? null : _save,
                icon: _busy
                    ? const SizedBox.square(
                        dimension: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.check_rounded),
                label: Text(
                  widget.sectionId == null ? 'Add section' : 'Save changes',
                ),
              ),
            ],
          ),
        );
      },
    ),
  );
}
