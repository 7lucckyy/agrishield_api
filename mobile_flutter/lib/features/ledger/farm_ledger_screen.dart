import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../app/providers.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/agri_widgets.dart';
import '../../models/models.dart';

final _naira = NumberFormat.currency(symbol: '₦', decimalDigits: 0);

class FarmLedgerScreen extends ConsumerStatefulWidget {
  const FarmLedgerScreen({super.key});

  @override
  ConsumerState<FarmLedgerScreen> createState() => _FarmLedgerScreenState();
}

class _FarmLedgerScreenState extends ConsumerState<FarmLedgerScreen> {
  String? _farmId;

  @override
  Widget build(BuildContext context) {
    final farms = ref.watch(farmsProvider);
    final items = farms.value ?? const <Farm>[];
    _farmId ??= items.firstOrNull?.id;
    final farmId = _farmId;
    return AgriPage(
      appBar: AppBar(title: const Text('Farm money book')),
      floatingActionButton: farmId == null
          ? null
          : FloatingActionButton.extended(
              onPressed: () => context.push('/ledger/$farmId/new'),
              icon: const Icon(Icons.add_rounded),
              label: const Text('Add record'),
            ),
      children: [
        const PageHeading(
          eyebrow: 'Private to you',
          title: 'Know what the farm earns',
          description: 'Record expenses, harvest and sales by typing or speaking. Profit is only as complete as the records you add.',
        ),
        farms.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => ErrorPanel(
            message: friendlyError(error),
            retry: () => ref.invalidate(farmsProvider),
          ),
          data: (available) => available.isEmpty
              ? const AgriCard(
                  child: Text('Add a farm before creating money records.'),
                )
              : DropdownButtonFormField<String>(
                  initialValue: farmId,
                  decoration: const InputDecoration(labelText: 'Farm'),
                  items: available
                      .map(
                        (farm) => DropdownMenuItem(
                          value: farm.id,
                          child: Text(farm.name),
                        ),
                      )
                      .toList(),
                  onChanged: (value) => setState(() => _farmId = value),
                ),
        ),
        if (farmId != null) ...[
          const SectionHeading('This farm'),
          ref
              .watch(farmProfitabilityProvider(farmId))
              .when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => ErrorPanel(
                  message: friendlyError(error),
                  retry: () =>
                      ref.invalidate(farmProfitabilityProvider(farmId)),
                ),
                data: (summary) => _ProfitSummary(summary: summary),
              ),
          const SectionHeading('Recent records'),
          ref
              .watch(ledgerEntriesProvider(farmId))
              .when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => ErrorPanel(
                  message: friendlyError(error),
                  retry: () => ref.invalidate(ledgerEntriesProvider(farmId)),
                ),
                data: (entries) => entries.isEmpty
                    ? const AgriCard(
                        child: Text(
                          'No records yet. Add your first farm expense, harvest or sale.',
                        ),
                      )
                    : Column(
                        children: entries
                            .map(
                              (entry) => Padding(
                                padding: const EdgeInsets.only(bottom: 10),
                                child: _LedgerRow(entry: entry),
                              ),
                            )
                            .toList(),
                      ),
              ),
        ],
      ],
    );
  }
}

class _ProfitSummary extends StatelessWidget {
  const _ProfitSummary({required this.summary});
  final FarmProfitability summary;

  @override
  Widget build(BuildContext context) => Column(
    children: [
      Row(
        children: [
          Expanded(
            child: _MoneyTile(
              label: 'Sales',
              value: _naira.format(summary.income),
              color: AgriColors.leafSoft,
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: _MoneyTile(
              label: 'Costs',
              value: _naira.format(summary.expenses),
              color: AgriColors.claySoft,
            ),
          ),
        ],
      ),
      const SizedBox(height: 10),
      AgriCard(
        color: summary.grossProfit >= 0
            ? AgriColors.milletSoft
            : AgriColors.claySoft,
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Estimated farm profit',
                    style: TextStyle(fontWeight: FontWeight.w700),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    _naira.format(summary.grossProfit),
                    style: Theme.of(context).textTheme.headlineMedium,
                  ),
                  if (summary.grossMarginPercent != null)
                    Text(
                      '${summary.grossMarginPercent!.toStringAsFixed(1)}% gross margin',
                    ),
                ],
              ),
            ),
            Icon(
              summary.grossProfit >= 0
                  ? Icons.trending_up_rounded
                  : Icons.trending_down_rounded,
              size: 36,
              color: summary.grossProfit >= 0
                  ? AgriColors.grove
                  : AgriColors.clay,
            ),
          ],
        ),
      ),
      const SizedBox(height: 8),
      Text(summary.notice, style: Theme.of(context).textTheme.bodySmall),
    ],
  );
}

class _MoneyTile extends StatelessWidget {
  const _MoneyTile({
    required this.label,
    required this.value,
    required this.color,
  });
  final String label;
  final String value;
  final Color color;
  @override
  Widget build(BuildContext context) => AgriCard(
    color: color,
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label),
        const SizedBox(height: 5),
        FittedBox(
          child: Text(value, style: Theme.of(context).textTheme.titleLarge),
        ),
      ],
    ),
  );
}

class _LedgerRow extends StatelessWidget {
  const _LedgerRow({required this.entry});
  final FarmLedgerEntry entry;
  @override
  Widget build(BuildContext context) => AgriCard(
    child: Row(
      children: [
        CircleAvatar(
          backgroundColor: entry.entryType == 'income'
              ? AgriColors.leafSoft
              : entry.entryType == 'expense'
              ? AgriColors.claySoft
              : AgriColors.milletSoft,
          child: Icon(
            entry.entryType == 'income'
                ? Icons.south_west_rounded
                : entry.entryType == 'expense'
                ? Icons.north_east_rounded
                : Icons.agriculture_rounded,
            color: AgriColors.ink,
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                entry.description,
                style: Theme.of(context).textTheme.titleMedium,
              ),
              Text(
                '${entry.occurredOn} · ${entry.category.replaceAll('_', ' ')}${entry.source == 'voice' ? ' · voice' : ''}',
                style: const TextStyle(color: AgriColors.muted),
              ),
            ],
          ),
        ),
        Text(
          entry.entryType == 'income'
              ? '+${_naira.format(entry.amount)}'
              : entry.entryType == 'expense'
              ? '-${_naira.format(entry.amount)}'
              : '${entry.quantity?.toStringAsFixed(1) ?? ''} ${entry.unit ?? ''}',
          style: const TextStyle(fontWeight: FontWeight.w800),
        ),
      ],
    ),
  );
}

class AddFarmLedgerEntryScreen extends ConsumerStatefulWidget {
  const AddFarmLedgerEntryScreen({super.key, required this.farmId});
  final String farmId;

  @override
  ConsumerState<AddFarmLedgerEntryScreen> createState() =>
      _AddFarmLedgerEntryScreenState();
}

class _AddFarmLedgerEntryScreenState
    extends ConsumerState<AddFarmLedgerEntryScreen> {
  final _formKey = GlobalKey<FormState>();
  final _description = TextEditingController();
  final _amount = TextEditingController();
  final _quantity = TextEditingController();
  final _unit = TextEditingController();
  final _notes = TextEditingController();
  String _type = 'expense';
  String _category = 'inputs';
  bool _busy = false;
  VoiceRequest? _voice;

  @override
  void dispose() {
    for (final controller in [
      _description,
      _amount,
      _quantity,
      _unit,
      _notes,
    ]) {
      controller.dispose();
    }
    super.dispose();
  }

  Future<void> _recordVoice() async {
    final result = await context.push<VoiceRequest>(
      '/voice/new?farmId=${widget.farmId}&purpose=ledger',
    );
    if (result == null || !mounted) {
      return;
    }
    setState(() {
      _voice = result;
      _notes.text = result.transcript ?? '';
      if (_description.text.isEmpty) {
        _description.text = result.transcript ?? 'Voice farm record';
      }
    });
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }
    setState(() => _busy = true);
    try {
      await ref.read(apiClientProvider).createLedgerEntry(widget.farmId, {
        'entry_type': _type,
        'category': _category,
        'occurred_on': DateFormat('yyyy-MM-dd').format(DateTime.now()),
        'description': _description.text.trim(),
        'amount': double.parse(_amount.text),
        if (_quantity.text.isNotEmpty) 'quantity': double.parse(_quantity.text),
        if (_unit.text.isNotEmpty) 'unit': _unit.text.trim(),
        'source': _voice == null ? 'manual' : 'voice',
        if (_voice != null) 'voice_request_id': _voice!.id,
        if (_voice != null) 'voice_transcript': _voice!.transcript,
        if (_notes.text.isNotEmpty) 'notes': _notes.text.trim(),
      });
      ref.invalidate(ledgerEntriesProvider(widget.farmId));
      ref.invalidate(farmProfitabilityProvider(widget.farmId));
      if (mounted) context.pop();
    } catch (error) {
      if (mounted) showMessage(context, friendlyError(error));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => AgriPage(
    appBar: AppBar(title: const Text('Add farm record')),
    children: [
      const PageHeading(
        eyebrow: 'Type or speak',
        title: 'What happened on the farm?',
        description: 'Choose one type, record the amount, and keep the wording simple. You can attach a voice note in your language.',
      ),
      SegmentedButton<String>(
        segments: const [
          ButtonSegment(
            value: 'expense',
            label: Text('Expense'),
            icon: Icon(Icons.shopping_bag_outlined),
          ),
          ButtonSegment(
            value: 'income',
            label: Text('Sale'),
            icon: Icon(Icons.payments_outlined),
          ),
          ButtonSegment(
            value: 'harvest',
            label: Text('Harvest'),
            icon: Icon(Icons.agriculture_outlined),
          ),
        ],
        selected: {_type},
        onSelectionChanged: (selection) => setState(() {
          _type = selection.first;
          _category = _type == 'expense'
              ? 'inputs'
              : _type == 'income'
              ? 'produce_sale'
              : 'harvest';
        }),
      ),
      const SizedBox(height: 14),
      OutlinedButton.icon(
        onPressed: _busy ? null : _recordVoice,
        icon: const Icon(Icons.mic_rounded),
        label: Text(
          _voice == null
              ? 'Describe this record by voice'
              : 'Voice note attached · record again',
        ),
      ),
      if (_voice?.transcript != null)
        Padding(
          padding: const EdgeInsets.only(top: 8),
          child: AgriCard(
            color: AgriColors.skySoft,
            child: Text(_voice!.transcript!),
          ),
        ),
      const SizedBox(height: 14),
      Form(
        key: _formKey,
        child: Column(
          children: [
            TextFormField(
              controller: _description,
              decoration: const InputDecoration(labelText: 'Short description'),
              validator: (value) => (value?.trim().isEmpty ?? true)
                  ? 'Describe the record'
                  : null,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _amount,
              keyboardType: const TextInputType.numberWithOptions(
                decimal: true,
              ),
              decoration: InputDecoration(
                labelText: _type == 'harvest'
                    ? 'Value in naira (enter 0 if not sold)'
                    : 'Amount in naira',
              ),
              validator: (value) => double.tryParse(value ?? '') == null
                  ? 'Enter an amount, or 0'
                  : null,
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: TextFormField(
                    controller: _quantity,
                    keyboardType: const TextInputType.numberWithOptions(
                      decimal: true,
                    ),
                    decoration: const InputDecoration(
                      labelText: 'Quantity (optional)',
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: TextFormField(
                    controller: _unit,
                    decoration: const InputDecoration(
                      labelText: 'Unit',
                      hintText: 'bag, kg, day',
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _notes,
              minLines: 2,
              maxLines: 5,
              decoration: const InputDecoration(labelText: 'Notes (optional)'),
            ),
          ],
        ),
      ),
      const SizedBox(height: 14),
      FilledButton(
        onPressed: _busy ? null : _submit,
        child: Text(_busy ? 'Saving record…' : 'Save farm record'),
      ),
    ],
  );
}
