import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../app/providers.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/agri_widgets.dart';
import '../../models/models.dart';

final financeProductsProvider = FutureProvider.autoDispose
    .family<List<FinanceProduct>, String>(
      (ref, farmId) => ref.read(apiClientProvider).financeProducts(farmId),
    );
final financeApplicationsProvider =
    FutureProvider.autoDispose<List<FinanceApplication>>(
      (ref) => ref.read(apiClientProvider).financeApplications(),
    );

class FinanceScreen extends ConsumerStatefulWidget {
  const FinanceScreen({super.key});

  @override
  ConsumerState<FinanceScreen> createState() => _FinanceScreenState();
}

class _FinanceScreenState extends ConsumerState<FinanceScreen> {
  String? _selectedFarmId;

  @override
  Widget build(BuildContext context) {
    final farms = ref.watch(farmsProvider);
    final farmList = farms.value ?? const <Farm>[];
    final selectedFarmId = farmList.any((farm) => farm.id == _selectedFarmId)
        ? _selectedFarmId
        : farmList.firstOrNull?.id;
    final applications = ref.watch(financeApplicationsProvider);

    return AgriPage(
      appBar: AppBar(title: const Text('Equipment for your farm')),
      children: [
        const PageHeading(
          eyebrow: 'Farm equipment',
          title: 'Find a practical tool',
          description:
              'Suggestions use your farm size, active crop season, irrigation setup and recorded activity.',
        ),
        const AgriCard(
          color: AgriColors.milletSoft,
          child: Text(
            'Availability and any purchase or lease terms are confirmed after your request is reviewed. No offer or approval is guaranteed.',
          ),
        ),
        const SectionHeading('Choose a farm'),
        farms.when(
          loading: () => const LinearProgressIndicator(),
          error: (error, _) => ErrorPanel(
            message: friendlyError(error),
            retry: () => ref.invalidate(farmsProvider),
          ),
          data: (items) => items.isEmpty
              ? const AgriCard(
                  child: Text(
                    'Add a farm and active crop season to see tailored equipment.',
                  ),
                )
              : Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: items
                      .map(
                        (farm) => ChoiceChip(
                          label: Text(farm.name),
                          selected: selectedFarmId == farm.id,
                          onSelected: (_) =>
                              setState(() => _selectedFarmId = farm.id),
                        ),
                      )
                      .toList(),
                ),
        ),
        if (selectedFarmId != null) ...[
          const SectionHeading('Recommended equipment'),
          ref
              .watch(financeProductsProvider(selectedFarmId))
              .when(
                loading: () => const Center(
                  child: Padding(
                    padding: EdgeInsets.all(32),
                    child: CircularProgressIndicator(),
                  ),
                ),
                error: (error, _) => ErrorPanel(
                  message: friendlyError(error),
                  retry: () =>
                      ref.invalidate(financeProductsProvider(selectedFarmId)),
                ),
                data: (items) => items.isEmpty
                    ? const AgriCard(
                        child: Text(
                          'No suitable equipment is available for this farm yet. Check its active crop season and irrigation details, then try again.',
                        ),
                      )
                    : Column(
                        children: items
                            .map(
                              (product) => Padding(
                                padding: const EdgeInsets.only(bottom: 12),
                                child: _EquipmentCard(
                                  product: product,
                                  farmId: selectedFarmId,
                                ),
                              ),
                            )
                            .toList(),
                      ),
              ),
        ],
        const SectionHeading('Your requests'),
        applications.when(
          loading: () => const LinearProgressIndicator(),
          error: (error, _) => ErrorPanel(
            message: friendlyError(error),
            retry: () => ref.invalidate(financeApplicationsProvider),
          ),
          data: (items) => items.isEmpty
              ? const AgriCard(
                  child: Text('You have not requested equipment yet.'),
                )
              : Column(
                  children: items
                      .map(
                        (application) => Padding(
                          padding: const EdgeInsets.only(bottom: 10),
                          child: AgriCard(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                StatusPill(
                                  application.status,
                                  warning:
                                      application.status != 'approved' &&
                                      application.status != 'delivered',
                                ),
                                const SizedBox(height: 10),
                                Text(
                                  application.productName,
                                  style: Theme.of(
                                    context,
                                  ).textTheme.titleMedium,
                                ),
                                Text(
                                  application.farmName,
                                  style: const TextStyle(
                                    color: AgriColors.muted,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  application.purpose,
                                  maxLines: 3,
                                  overflow: TextOverflow.ellipsis,
                                ),
                                if (application.decisionNote != null &&
                                    application.decisionNote!.isNotEmpty) ...[
                                  const SizedBox(height: 8),
                                  Text(
                                    'Desk update: ${application.decisionNote}',
                                  ),
                                ],
                              ],
                            ),
                          ),
                        ),
                      )
                      .toList(),
                ),
        ),
      ],
    );
  }
}

class _EquipmentCard extends StatelessWidget {
  const _EquipmentCard({required this.product, required this.farmId});
  final FinanceProduct product;
  final String farmId;

  @override
  Widget build(BuildContext context) => AgriCard(
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            const Icon(Icons.agriculture_outlined, color: AgriColors.indigo),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                product.name,
                style: Theme.of(context).textTheme.titleMedium,
              ),
            ),
          ],
        ),
        if (product.description != null) ...[
          const SizedBox(height: 8),
          Text(product.description!),
        ],
        if (product.capacity != null)
          Text(
            'Capacity: ${product.capacity}',
            style: const TextStyle(color: AgriColors.muted),
          ),
        const SizedBox(height: 8),
        for (final reason in product.reasons)
          Text('• $reason', style: const TextStyle(color: AgriColors.muted)),
        const SizedBox(height: 12),
        Text(
          product.acquisitionOptions.contains('lease')
              ? 'Leasing and purchase request available'
              : 'Purchase request available',
        ),
        const SizedBox(height: 12),
        FilledButton.tonal(
          onPressed: () =>
              context.push('/finance/apply/${product.id}?farmId=$farmId'),
          child: const Text('Request this equipment'),
        ),
      ],
    ),
  );
}

class FinanceApplyScreen extends ConsumerStatefulWidget {
  const FinanceApplyScreen({
    super.key,
    required this.productId,
    required this.farmId,
  });
  final int productId;
  final String farmId;

  @override
  ConsumerState<FinanceApplyScreen> createState() => _FinanceApplyScreenState();
}

class _FinanceApplyScreenState extends ConsumerState<FinanceApplyScreen> {
  final _formKey = GlobalKey<FormState>();
  final _amount = TextEditingController();
  final _quantity = TextEditingController(text: '1');
  final _purpose = TextEditingController();
  String? _mode;
  bool _consent = false;
  bool _busy = false;
  bool _complete = false;

  @override
  void dispose() {
    _amount.dispose();
    _quantity.dispose();
    _purpose.dispose();
    super.dispose();
  }

  Future<void> _submit(FinanceProduct product, String mode) async {
    if (!_formKey.currentState!.validate() || !_consent) {
      showMessage(context, 'Complete the form and confirm your consent.');
      return;
    }
    setState(() => _busy = true);
    try {
      await ref.read(apiClientProvider).applyForFinance({
        'farm_id': widget.farmId,
        'asset_finance_product_id': product.id,
        'acquisition_mode': mode,
        'quantity': int.parse(_quantity.text),
        'requested_amount': double.parse(_amount.text),
        'purpose': _purpose.text.trim(),
        'consent': true,
      });
      ref.invalidate(financeApplicationsProvider);
      if (mounted) setState(() => _complete = true);
    } catch (error) {
      if (mounted) showMessage(context, friendlyError(error));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_complete) {
      return AgriPage(
        appBar: AppBar(),
        children: [
          const PageHeading(
            eyebrow: 'Request recorded',
            title: 'Track your request',
            description:
                'Your equipment request is awaiting review. Availability and terms will be confirmed before any agreement.',
          ),
          FilledButton(
            onPressed: () => context.go('/finance'),
            child: const Text('View my requests'),
          ),
        ],
      );
    }

    final products = ref.watch(financeProductsProvider(widget.farmId));
    return AgriPage(
      appBar: AppBar(title: const Text('Request equipment')),
      children: products.when(
        loading: () => [const Center(child: CircularProgressIndicator())],
        error: (error, _) => [
          ErrorPanel(
            message: friendlyError(error),
            retry: () => ref.invalidate(financeProductsProvider(widget.farmId)),
          ),
        ],
        data: (items) {
          final product = items
              .where((item) => item.id == widget.productId)
              .firstOrNull;
          if (product == null) {
            return [
              const AgriCard(
                child: Text(
                  'This equipment is no longer recommended for the selected farm.',
                ),
              ),
            ];
          }
          final mode = product.acquisitionOptions.contains(_mode)
              ? _mode!
              : product.acquisitionOptions.first;
          return [
            PageHeading(
              eyebrow: 'Equipment request',
              title: product.name,
              description:
                  'Choose how you would like to access this tool. This is a request, not an approved loan or lease.',
            ),
            const SectionHeading('Access option'),
            SegmentedButton<String>(
              segments: product.acquisitionOptions
                  .map(
                    (option) => ButtonSegment(
                      value: option,
                      label: Text(option == 'lease' ? 'Lease' : 'Purchase'),
                    ),
                  )
                  .toList(),
              selected: {mode},
              onSelectionChanged: (selection) =>
                  setState(() => _mode = selection.first),
            ),
            const SizedBox(height: 18),
            Form(
              key: _formKey,
              child: Column(
                children: [
                  TextFormField(
                    controller: _quantity,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(labelText: 'Quantity'),
                    validator: (value) {
                      final count = int.tryParse(value ?? '');
                      return count == null || count < 1 || count > 100
                          ? 'Enter 1–100'
                          : null;
                    },
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _amount,
                    keyboardType: const TextInputType.numberWithOptions(
                      decimal: true,
                    ),
                    decoration: const InputDecoration(
                      labelText: 'Your estimated budget (NGN)',
                    ),
                    validator: (value) =>
                        (double.tryParse(value ?? '') ?? 0) <= 0
                        ? 'Enter your estimated budget'
                        : null,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _purpose,
                    minLines: 3,
                    maxLines: 6,
                    maxLength: 2000,
                    decoration: const InputDecoration(
                      labelText: 'How will you use it?',
                      hintText: 'Describe your crop, season and intended use',
                    ),
                    validator: (value) => (value?.trim().length ?? 0) < 20
                        ? 'Please enter at least 20 characters'
                        : null,
                  ),
                ],
              ),
            ),
            CheckboxListTile(
              value: _consent,
              onChanged: (value) => setState(() => _consent = value ?? false),
              title: const Text(
                'I agree to share this request and my farm details for equipment review.',
              ),
            ),
            const SizedBox(height: 12),
            FilledButton(
              onPressed: _busy ? null : () => _submit(product, mode),
              child: Text(
                _busy ? 'Sending request…' : 'Send equipment request',
              ),
            ),
          ];
        },
      ),
    );
  }
}
