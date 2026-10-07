import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../app/providers.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/agri_widgets.dart';
import '../../models/models.dart';

final financeProductsProvider =
    FutureProvider.autoDispose<List<FinanceProduct>>(
      (ref) => ref.read(apiClientProvider).financeProducts(),
    );
final financeApplicationsProvider =
    FutureProvider.autoDispose<List<FinanceApplication>>(
      (ref) => ref.read(apiClientProvider).financeApplications(),
    );

class FinanceScreen extends ConsumerWidget {
  const FinanceScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final products = ref.watch(financeProductsProvider);
    final applications = ref.watch(financeApplicationsProvider);
    return AgriPage(
      appBar: AppBar(title: const Text('Asset access')),
      children: [
        const PageHeading(
          eyebrow: 'Tools to grow',
          title: 'Equipment and inputs',
          description:
              'Explore verified options for your registered crop farm.',
        ),
        const AgriCard(
          color: AgriColors.milletSoft,
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(Icons.info_outline_rounded, color: Color(0xFF76510F)),
              SizedBox(width: 10),
              Expanded(
                child: Text(
                  'Each partner independently decides eligibility, terms and disbursement. AgriShield does not issue loans.',
                ),
              ),
            ],
          ),
        ),
        const SectionHeading('Available programmes'),
        products.when(
          loading: () => const Center(
            child: Padding(
              padding: EdgeInsets.all(40),
              child: CircularProgressIndicator(),
            ),
          ),
          error: (error, _) => ErrorPanel(
            message: friendlyError(error),
            retry: () => ref.invalidate(financeProductsProvider),
          ),
          data: (items) => items.isEmpty
              ? const AgriCard(
                  child: Text(
                    'No equipment programmes are open today. New verified options will appear here.',
                  ),
                )
              : Column(
                  children: items
                      .map(
                        (product) => Padding(
                          padding: const EdgeInsets.only(bottom: 12),
                          child: _ProductCard(product: product),
                        ),
                      )
                      .toList(),
                ),
        ),
        const SectionHeading('Your applications'),
        applications.when(
          loading: () => const LinearProgressIndicator(),
          error: (error, _) => ErrorPanel(
            message: friendlyError(error),
            retry: () => ref.invalidate(financeApplicationsProvider),
          ),
          data: (items) => items.isEmpty
              ? const AgriCard(
                  child: Text(
                    'You have no applications yet. Choose equipment above when your farm is ready.',
                  ),
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
                                Row(
                                  children: [
                                    StatusPill(
                                      application.status,
                                      warning:
                                          application.status != 'approved' &&
                                          application.status != 'delivered',
                                    ),
                                    const Spacer(),
                                    Text(
                                      application.farmName,
                                      style: const TextStyle(
                                        color: AgriColors.muted,
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 10),
                                Text(
                                  application.productName,
                                  style: Theme.of(context)
                                      .textTheme
                                      .titleMedium,
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  application.purpose,
                                  maxLines: 3,
                                  overflow: TextOverflow.ellipsis,
                                ),
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

class _ProductCard extends StatelessWidget {
  const _ProductCard({required this.product});
  final FinanceProduct product;
  @override
  Widget build(BuildContext context) => AgriCard(
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Container(
              width: 42,
              height: 42,
              decoration: BoxDecoration(
                color: AgriColors.indigoSoft,
                borderRadius: BorderRadius.circular(13),
              ),
              child: const Icon(
                Icons.agriculture_outlined,
                color: AgriColors.indigo,
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                product.partnerName,
                textAlign: TextAlign.right,
                style: const TextStyle(color: AgriColors.muted, fontSize: 12),
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Text(product.name, style: Theme.of(context).textTheme.titleLarge),
        const SizedBox(height: 5),
        Text(
          product.eligibility ??
              '${product.category.replaceAll('_', ' ')} for productive crop use.',
          style: const TextStyle(color: AgriColors.muted, height: 1.45),
        ),
        const SizedBox(height: 14),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: AgriColors.canvas,
            borderRadius: BorderRadius.circular(AgriRadius.sm),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'AVAILABLE RANGE',
                style: TextStyle(
                  fontSize: 10,
                  color: AgriColors.muted,
                  fontWeight: FontWeight.w800,
                  letterSpacing: .8,
                ),
              ),
              const SizedBox(height: 3),
              Text(
                '${_currency(product.minimumAmount, product.currency)} – ${_currency(product.maximumAmount, product.currency)}',
                style: const TextStyle(
                  color: AgriColors.forest,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 14),
        FilledButton.tonal(
          onPressed: () => context.push('/finance/apply/${product.id}'),
          child: const Text('Check eligibility'),
        ),
      ],
    ),
  );
}

class FinanceApplyScreen extends ConsumerStatefulWidget {
  const FinanceApplyScreen({super.key, required this.productId});
  final int productId;
  @override
  ConsumerState<FinanceApplyScreen> createState() => _FinanceApplyScreenState();
}

class _FinanceApplyScreenState extends ConsumerState<FinanceApplyScreen> {
  final _formKey = GlobalKey<FormState>();
  final _amount = TextEditingController();
  final _quantity = TextEditingController(text: '1');
  final _purpose = TextEditingController();
  String? _farmId;
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

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate() || _farmId == null || !_consent) {
      showMessage(
        context,
        'Complete the form and accept data sharing to continue.',
      );
      return;
    }
    setState(() => _busy = true);
    try {
      await ref.read(apiClientProvider).applyForFinance({
        'farm_id': _farmId,
        'asset_finance_product_id': widget.productId,
        'quantity': int.parse(_quantity.text),
        'requested_amount': double.parse(_amount.text),
        'purpose': _purpose.text.trim(),
        'consent': true,
      });
      ref.invalidate(financeApplicationsProvider);
      if (mounted) setState(() => _complete = true);
    } catch (error) {
      if (mounted) showMessage(context, friendlyError(error));
    }
    if (mounted) setState(() => _busy = false);
  }

  @override
  Widget build(BuildContext context) {
    final farms = ref.watch(farmsProvider);
    final product = ref
        .watch(financeProductsProvider)
        .value
        ?.where((item) => item.id == widget.productId)
        .firstOrNull;
    if (_complete) {
      return AgriPage(
        appBar: AppBar(),
        children: [
          const PageHeading(
            eyebrow: 'Application recorded',
            title: 'You can track it here',
            description: 'The proposed finance partner performs its own assessment and makes every financing decision.',
          ),
          FilledButton(
            onPressed: () => context.pop(),
            child: const Text('Return to asset access'),
          ),
        ],
      );
    }
    return AgriPage(
      appBar: AppBar(title: const Text('Apply for equipment')),
      children: [
        PageHeading(
          eyebrow: product?.partnerName ?? 'Asset access',
          title: product?.name ?? 'Productive equipment',
          description: 'Tell the partner how this asset will improve your crop production.',
        ),
        if (product != null)
          AgriCard(
            color: AgriColors.milletSoft,
            child: Text(
              product.eligibility ??
                  'Eligibility is assessed by the named partner.',
            ),
          ),
        const SectionHeading('Choose your farm'),
        farms.when(
          loading: () => const LinearProgressIndicator(),
          error: (error, _) => Text(friendlyError(error)),
          data: (items) => Wrap(
            spacing: 8,
            runSpacing: 8,
            children: items
                .map(
                  (farm) => ChoiceChip(
                    label: Text(farm.name),
                    selected: _farmId == farm.id,
                    onSelected: (_) => setState(() => _farmId = farm.id),
                  ),
                )
                .toList(),
          ),
        ),
        const SizedBox(height: 18),
        Form(
          key: _formKey,
          child: Column(
            children: [
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _quantity,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(labelText: 'Quantity'),
                      validator: (value) {
                        final number = int.tryParse(value ?? '');
                        return number == null || number < 1 || number > 100
                            ? '1–100'
                            : null;
                      },
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    flex: 2,
                    child: TextFormField(
                      controller: _amount,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                      ),
                      decoration: const InputDecoration(
                        labelText: 'Amount (NGN)',
                      ),
                      validator: (value) =>
                          (double.tryParse(value ?? '') ?? 0) <= 0
                          ? 'Enter an amount'
                          : null,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 14),
              TextFormField(
                controller: _purpose,
                minLines: 4,
                maxLines: 7,
                maxLength: 2000,
                decoration: const InputDecoration(
                  labelText: 'How will this improve production?',
                  hintText:
                      'Explain the crop, season and intended productive use…',
                ),
                validator: (value) => (value?.trim().length ?? 0) < 20
                    ? 'Please give at least 20 characters'
                    : null,
              ),
            ],
          ),
        ),
        CheckboxListTile(
          contentPadding: const EdgeInsets.all(12),
          tileColor: AgriColors.milletSoft,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AgriRadius.md),
          ),
          value: _consent,
          onChanged: (value) => setState(() => _consent = value ?? false),
          title: const Text(
            'I agree to share my application and farm details with the named proposed finance partner for assessment.',
            style: TextStyle(fontSize: 13),
          ),
        ),
        const SizedBox(height: 18),
        FilledButton(
          onPressed: _busy ? null : _submit,
          child: Text(_busy ? 'Recording application…' : 'Record application'),
        ),
      ],
    );
  }
}

String _currency(double? amount, String currency) => amount == null
    ? 'Flexible'
    : NumberFormat.simpleCurrency(
        name: currency,
        decimalDigits: 0,
      ).format(amount);
