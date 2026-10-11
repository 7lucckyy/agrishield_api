import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../app/providers.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/agri_widgets.dart';
import '../../models/models.dart';
import '../assist/speech_answer_button.dart';

const _serviceCategories = <String?, String>{
  null: 'All',
  'produce_purchase': 'Sell produce',
  'input_purchase': 'Group inputs',
  'input_finance': 'Input finance',
  'machinery_hire': 'Hire machinery',
  'storage': 'Storage',
  'logistics': 'Transport',
  'crop_insurance': 'Crop cover',
};

class AgriculturalServicesScreen extends ConsumerStatefulWidget {
  const AgriculturalServicesScreen({super.key});

  @override
  ConsumerState<AgriculturalServicesScreen> createState() =>
      _AgriculturalServicesScreenState();
}

class _AgriculturalServicesScreenState
    extends ConsumerState<AgriculturalServicesScreen> {
  String? _category;

  @override
  Widget build(BuildContext context) {
    final offers = ref.watch(agriculturalOffersProvider(_category));
    final requests = ref.watch(agriculturalServiceRequestsProvider);
    return AgriPage(
      appBar: AppBar(title: const Text('Farm services')),
      children: [
        const PageHeading(
          eyebrow: 'Verified partner network',
          title: 'Sell, buy and access equipment',
          description: 'Browse offers from reviewed buyers and service providers. Every request is confirmed before money, produce or equipment changes hands.',
        ),
        SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          child: Row(
            children: _serviceCategories.entries
                .map(
                  (entry) => Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: ChoiceChip(
                      label: Text(entry.value),
                      selected: _category == entry.key,
                      onSelected: (_) => setState(() => _category = entry.key),
                    ),
                  ),
                )
                .toList(),
          ),
        ),
        const SectionHeading('Available near your farm'),
        offers.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => ErrorPanel(
            message: friendlyError(error),
            retry: () => ref.invalidate(agriculturalOffersProvider(_category)),
          ),
          data: (items) => items.isEmpty
              ? const AgriCard(
                  child: Text(
                    'No verified offers match this category and farm location yet. Check again later or ask your cooperative.',
                  ),
                )
              : Column(
                  children: items
                      .map(
                        (offer) => Padding(
                          padding: const EdgeInsets.only(bottom: 12),
                          child: _OfferCard(offer: offer),
                        ),
                      )
                      .toList(),
                ),
        ),
        const SectionHeading('My requests'),
        requests.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => ErrorPanel(
            message: friendlyError(error),
            retry: () => ref.invalidate(agriculturalServiceRequestsProvider),
          ),
          data: (items) => items.isEmpty
              ? const AgriCard(
                  child: Text(
                    'Your buyer, input and machinery requests will appear here.',
                  ),
                )
              : Column(
                  children: items
                      .map(
                        (item) => Padding(
                          padding: const EdgeInsets.only(bottom: 10),
                          child: AgriCard(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Expanded(
                                      child: Text(
                                        item.offerTitle,
                                        style: Theme.of(context)
                                            .textTheme
                                            .titleMedium,
                                      ),
                                    ),
                                    StatusPill(item.status),
                                  ],
                                ),
                                const SizedBox(height: 6),
                                Text(
                                  '${item.partnerName} · ${item.farmName}',
                                  style: const TextStyle(
                                    color: AgriColors.muted,
                                  ),
                                ),
                                if (item.decisionNote != null) ...[
                                  const SizedBox(height: 8),
                                  Text(item.decisionNote!),
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

class _OfferCard extends StatelessWidget {
  const _OfferCard({required this.offer});
  final AgriculturalOffer offer;

  @override
  Widget build(BuildContext context) {
    final price = offer.unitPrice == null
        ? 'Price confirmed by partner'
        : '${NumberFormat.currency(symbol: '₦', decimalDigits: 0).format(offer.unitPrice)}${offer.unit == null ? '' : ' / ${offer.unit}'}';
    final spoken =
        '${offer.title}. Offered by ${offer.partnerName}. $price. ${offer.description ?? ''}';
    return AgriCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Icon(
                Icons.verified_rounded,
                color: AgriColors.leaf,
                semanticLabel: 'Verified partner',
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      offer.title,
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                    Text(
                      offer.partnerName,
                      style: const TextStyle(
                        color: AgriColors.grove,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          if (offer.description != null) ...[
            const SizedBox(height: 10),
            Text(offer.description!),
          ],
          const SizedBox(height: 10),
          Text(price, style: const TextStyle(fontWeight: FontWeight.w800)),
          if (offer.minimumQuantity != null)
            Text(
              'Minimum: ${offer.minimumQuantity!.toStringAsFixed(1)} ${offer.unit ?? ''}',
              style: const TextStyle(color: AgriColors.muted),
            ),
          if (offer.remainingQuantity != null)
            Text(
              '${offer.remainingQuantity!.toStringAsFixed(1)} ${offer.unit ?? ''} still available',
              style: const TextStyle(
                color: AgriColors.grove,
                fontWeight: FontWeight.w700,
              ),
            ),
          const SizedBox(height: 12),
          SpeechAnswerButton(text: spoken, language: 'en'),
          const SizedBox(height: 8),
          FilledButton(
            onPressed: () => context.push('/services/request/${offer.id}'),
            child: Text(_actionLabel(offer.category)),
          ),
        ],
      ),
    );
  }

  String _actionLabel(String category) => switch (category) {
    'produce_purchase' => 'Offer my produce',
    'input_purchase' => 'Join group order',
    'input_finance' => 'Request input finance',
    'machinery_hire' => 'Request machinery',
    _ => 'Request this service',
  };
}

class AgriculturalServiceRequestScreen extends ConsumerStatefulWidget {
  const AgriculturalServiceRequestScreen({super.key, required this.offerId});
  final int offerId;

  @override
  ConsumerState<AgriculturalServiceRequestScreen> createState() =>
      _AgriculturalServiceRequestScreenState();
}

class _AgriculturalServiceRequestScreenState
    extends ConsumerState<AgriculturalServiceRequestScreen> {
  final _formKey = GlobalKey<FormState>();
  final _quantity = TextEditingController();
  final _budget = TextEditingController();
  final _purpose = TextEditingController();
  String? _farmId;
  bool _consent = false;
  bool _busy = false;

  @override
  void dispose() {
    _quantity.dispose();
    _budget.dispose();
    _purpose.dispose();
    super.dispose();
  }

  Future<void> _submit(AgriculturalOffer offer) async {
    if (!_formKey.currentState!.validate() || !_consent || _farmId == null) {
      showMessage(
        context,
        'Choose a farm, complete the form and confirm your consent.',
      );
      return;
    }
    setState(() => _busy = true);
    try {
      await ref.read(apiClientProvider).requestAgriculturalService({
        'offer_id': offer.id,
        'farm_id': _farmId,
        if (_quantity.text.trim().isNotEmpty)
          'quantity': double.parse(_quantity.text),
        'unit': offer.unit,
        if (_budget.text.trim().isNotEmpty)
          'budget_amount': double.parse(_budget.text),
        'purpose': _purpose.text.trim().isEmpty ? null : _purpose.text.trim(),
        'consent': true,
      });
      ref.invalidate(agriculturalServiceRequestsProvider);
      if (mounted) {
        showMessage(
          context,
          'Request sent. The verified partner will confirm the final terms.',
        );
        context.pop();
      }
    } catch (error) {
      if (mounted) showMessage(context, friendlyError(error));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final offers =
        ref.watch(agriculturalOffersProvider(null)).value ??
        const <AgriculturalOffer>[];
    final farms = ref.watch(farmsProvider).value ?? const <Farm>[];
    final offer = offers.where((item) => item.id == widget.offerId).firstOrNull;
    _farmId ??= farms.firstOrNull?.id;
    if (offer == null) {
      return AgriPage(
        appBar: AppBar(),
        children: const [
          AgriCard(
            child: Text(
              'This offer is no longer available. Return to Farm services and choose another.',
            ),
          ),
        ],
      );
    }
    return AgriPage(
      appBar: AppBar(title: const Text('Send request')),
      children: [
        PageHeading(
          eyebrow: 'No payment yet',
          title: offer.title,
          description:
              'This records your interest. ${offer.partnerName} must confirm availability, price, eligibility and delivery before you agree.',
        ),
        Form(
          key: _formKey,
          child: Column(
            children: [
              DropdownButtonFormField<String>(
                initialValue: _farmId,
                decoration: const InputDecoration(labelText: 'Farm'),
                items: farms
                    .map(
                      (farm) => DropdownMenuItem(
                        value: farm.id,
                        child: Text(farm.name),
                      ),
                    )
                    .toList(),
                onChanged: _busy
                    ? null
                    : (value) => setState(() => _farmId = value),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _quantity,
                keyboardType: const TextInputType.numberWithOptions(
                  decimal: true,
                ),
                decoration: InputDecoration(
                  labelText:
                      'Quantity${offer.unit == null ? '' : ' (${offer.unit})'}',
                ),
                validator: (value) {
                  final amount = double.tryParse(value ?? '');
                  if (offer.minimumQuantity != null &&
                      (amount ?? 0) < offer.minimumQuantity!) {
                    return 'Minimum is ${offer.minimumQuantity} ${offer.unit ?? ''}';
                  }
                  if (offer.remainingQuantity != null &&
                      (amount ?? 0) > offer.remainingQuantity!) {
                    return 'Only ${offer.remainingQuantity} ${offer.unit ?? ''} remain';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _budget,
                keyboardType: const TextInputType.numberWithOptions(
                  decimal: true,
                ),
                decoration: const InputDecoration(
                  labelText: 'Budget in naira (optional)',
                ),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _purpose,
                minLines: 3,
                maxLines: 6,
                decoration: const InputDecoration(
                  labelText: 'What do you need?',
                  hintText: 'Describe timing, quality, location or equipment work needed',
                ),
              ),
            ],
          ),
        ),
        CheckboxListTile(
          contentPadding: EdgeInsets.zero,
          value: _consent,
          onChanged: _busy
              ? null
              : (value) => setState(() => _consent = value ?? false),
          title: const Text(
            'I agree to share this request and selected farm details with the named partner for review.',
          ),
        ),
        FilledButton(
          onPressed: _busy ? null : () => _submit(offer),
          child: Text(_busy ? 'Sending request…' : 'Send request'),
        ),
      ],
    );
  }
}
