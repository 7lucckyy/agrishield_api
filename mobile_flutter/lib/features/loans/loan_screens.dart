import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../app/providers.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/agri_widgets.dart';
import '../../models/models.dart';

/// What a smallholder can borrow for, with typical items and amount range.
enum LoanCategory {
  tools(
    label: 'Farm tools',
    detail: 'Hoes, sprayers, wheelbarrows',
    icon: Icons.handyman_outlined,
    tint: AgriColors.soil,
    items: ['Knapsack sprayer', 'Hoes and cutlasses', 'Wheelbarrow', 'Planter'],
    minimumAmount: 20000,
    maximumAmount: 500000,
  ),
  tractor(
    label: 'Tractors & machinery',
    detail: 'Tractor hire, tillers, threshers',
    icon: Icons.agriculture_outlined,
    tint: AgriColors.indigo,
    items: ['Tractor hire', 'Power tiller', 'Thresher', 'Used tractor'],
    minimumAmount: 200000,
    maximumAmount: 10000000,
  ),
  seeds(
    label: 'Seeds',
    detail: 'Improved and certified seed',
    icon: Icons.grass_rounded,
    tint: AgriColors.leaf,
    items: ['Maize seed', 'Rice seed', 'Sorghum seed', 'Vegetable seed'],
    minimumAmount: 10000,
    maximumAmount: 500000,
  ),
  fertilizer(
    label: 'Fertiliser',
    detail: 'NPK, urea and organic inputs',
    icon: Icons.science_outlined,
    tint: AgriColors.millet,
    items: ['NPK', 'Urea', 'Organic manure', 'Crop protection'],
    minimumAmount: 20000,
    maximumAmount: 1000000,
  ),
  irrigation(
    label: 'Irrigation',
    detail: 'Pumps, solar kits, drip lines',
    icon: Icons.water_drop_outlined,
    tint: AgriColors.water,
    items: ['Water pump', 'Solar pump kit', 'Drip lines', 'Borehole'],
    minimumAmount: 100000,
    maximumAmount: 3000000,
  ),
  storage(
    label: 'Storage',
    detail: 'Hermetic bags, silos, dryers',
    icon: Icons.warehouse_outlined,
    tint: AgriColors.clay,
    items: ['Hermetic bags', 'Grain silo', 'Crop dryer', 'Cold storage'],
    minimumAmount: 20000,
    maximumAmount: 2000000,
  );

  const LoanCategory({
    required this.label,
    required this.detail,
    required this.icon,
    required this.tint,
    required this.items,
    required this.minimumAmount,
    required this.maximumAmount,
  });

  final String label;
  final String detail;
  final IconData icon;
  final Color tint;
  final List<String> items;
  final int minimumAmount;
  final int maximumAmount;
}

/// Repayment options; `null` months means a single payment after harvest.
const _repaymentMonths = <int?>[null, 3, 6, 12];

final _naira = NumberFormat.currency(
  locale: 'en_NG',
  symbol: '₦',
  decimalDigits: 0,
);

String _repaymentLabel(int? months) =>
    months == null ? 'After harvest' : '$months months';

class LoansScreen extends StatelessWidget {
  const LoansScreen({super.key});

  @override
  Widget build(BuildContext context) => AgriPage(
    appBar: AppBar(title: const Text('Farm loans')),
    children: [
      const _LoansHero(),
      const SectionHeading('What do you need?'),
      GridView.count(
        crossAxisCount: 2,
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        mainAxisSpacing: 10,
        crossAxisSpacing: 10,
        childAspectRatio: 1.15,
        children: [
          for (final category in LoanCategory.values)
            _CategoryCard(
              category: category,
              onTap: () => context.push('/loans/request/${category.name}'),
            ),
        ],
      ),
      const SectionHeading('How it works'),
      const AgriCard(
        child: Column(
          children: [
            _HowItWorksStep(
              number: 1,
              title: 'Tell us what you need',
              detail: 'Choose the item, amount and how you want to repay.',
            ),
            _HowItWorksStep(
              number: 2,
              title: 'A lending partner reviews it',
              detail: 'They may call you to confirm your farm and crop plan.',
            ),
            _HowItWorksStep(
              number: 3,
              title: 'Get your tools or inputs',
              detail: 'Repay monthly or in one payment after harvest.',
              isLast: true,
            ),
          ],
        ),
      ),
    ],
  );
}

class _LoansHero extends StatelessWidget {
  const _LoansHero();

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(20),
    decoration: BoxDecoration(
      gradient: const LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [AgriColors.grove, AgriColors.forest],
      ),
      borderRadius: BorderRadius.circular(AgriRadius.lg),
    ),
    child: Row(
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Grow more this season',
                style: Theme.of(context).textTheme.titleLarge
                    ?.copyWith(color: Colors.white),
              ),
              const SizedBox(height: 6),
              const Text(
                'Request a loan for tools, tractors, seeds and inputs. Repay after harvest.',
                style: TextStyle(
                  color: Color(0xFFC7D4CD),
                  fontSize: 13,
                  height: 1.4,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(width: 12),
        Container(
          width: 56,
          height: 56,
          decoration: const BoxDecoration(
            color: AgriColors.millet,
            shape: BoxShape.circle,
          ),
          child: const Icon(
            Icons.payments_outlined,
            color: AgriColors.ink,
            size: 28,
          ),
        ),
      ],
    ),
  );
}

class _CategoryCard extends StatelessWidget {
  const _CategoryCard({required this.category, required this.onTap});

  final LoanCategory category;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => AgriCard(
    onTap: onTap,
    padding: const EdgeInsets.all(14),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _TintedIcon(icon: category.icon, tint: category.tint),
        const Spacer(),
        Text(
          category.label,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700),
        ),
        const SizedBox(height: 2),
        Text(
          category.detail,
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(
            color: AgriColors.muted,
            fontSize: 12,
            height: 1.3,
          ),
        ),
      ],
    ),
  );
}

class _TintedIcon extends StatelessWidget {
  const _TintedIcon({required this.icon, required this.tint, this.size = 40});

  final IconData icon;
  final Color tint;
  final double size;

  @override
  Widget build(BuildContext context) => Container(
    width: size,
    height: size,
    decoration: BoxDecoration(
      color: tint.withValues(alpha: .14),
      borderRadius: BorderRadius.circular(size * .3),
    ),
    child: Icon(icon, color: tint, size: size * .55),
  );
}

class _HowItWorksStep extends StatelessWidget {
  const _HowItWorksStep({
    required this.number,
    required this.title,
    required this.detail,
    this.isLast = false,
  });

  final int number;
  final String title;
  final String detail;
  final bool isLast;

  @override
  Widget build(BuildContext context) => Padding(
    padding: EdgeInsets.only(bottom: isLast ? 0 : 14),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        CircleAvatar(
          radius: 13,
          backgroundColor: AgriColors.leafSoft,
          child: Text(
            '$number',
            style: const TextStyle(
              color: AgriColors.forest,
              fontSize: 12,
              fontWeight: FontWeight.w700,
            ),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: const TextStyle(fontWeight: FontWeight.w700)),
              const SizedBox(height: 2),
              Text(
                detail,
                style: const TextStyle(color: AgriColors.muted, fontSize: 13),
              ),
            ],
          ),
        ),
      ],
    ),
  );
}

enum _RequestStep { item, amount, farm, review, submitted }

/// A clickthrough loan request. Nothing is sent to the server yet: the
/// final step only shows a locally generated reference.
class LoanRequestScreen extends ConsumerStatefulWidget {
  const LoanRequestScreen({super.key, required this.category});

  final LoanCategory category;

  @override
  ConsumerState<LoanRequestScreen> createState() => _LoanRequestScreenState();
}

class _LoanRequestScreenState extends ConsumerState<LoanRequestScreen> {
  _RequestStep _step = _RequestStep.item;
  late String _item = widget.category.items.first;
  late double _amount = _defaultAmount;
  int? _repayment;
  String? _farmId;
  bool _farmChosen = false;
  bool _consented = false;
  bool _submitting = false;
  String? _reference;

  LoanCategory get _category => widget.category;

  double get _defaultAmount {
    final suggested = math.sqrt(
      _category.minimumAmount * _category.maximumAmount.toDouble(),
    );
    return _roundAmount(suggested);
  }

  double _roundAmount(double amount) {
    final step = _amountStep;
    return ((amount / step).round() * step).clamp(
      _category.minimumAmount.toDouble(),
      _category.maximumAmount.toDouble(),
    );
  }

  double get _amountStep => _category.maximumAmount >= 2000000
      ? 50000
      : _category.maximumAmount >= 500000
      ? 10000
      : 5000;

  bool get _canContinue => switch (_step) {
    _RequestStep.item || _RequestStep.amount => true,
    _RequestStep.farm => _farmChosen,
    _RequestStep.review => _consented && !_submitting,
    _RequestStep.submitted => true,
  };

  void _back() {
    if (_step == _RequestStep.item) {
      context.pop();
      return;
    }
    setState(() => _step = _RequestStep.values[_step.index - 1]);
  }

  Future<void> _next() async {
    if (_step == _RequestStep.review) {
      setState(() => _submitting = true);
      await Future<void>.delayed(const Duration(milliseconds: 700));
      if (!mounted) {
        return;
      }
      final code = (DateTime.now().millisecondsSinceEpoch % 1000000)
          .toString()
          .padLeft(6, '0');
      setState(() {
        _submitting = false;
        _reference = 'AGL-$code';
        _step = _RequestStep.submitted;
      });
      return;
    }
    setState(() => _step = _RequestStep.values[_step.index + 1]);
  }

  @override
  Widget build(BuildContext context) {
    if (_step == _RequestStep.submitted) {
      return _SubmittedView(
        reference: _reference!,
        summary:
            '$_item · ${_naira.format(_amount)} · ${_repaymentLabel(_repayment)}',
        phone: ref.watch(authControllerProvider).value?.user?.phone,
      );
    }
    final stepNumber = _step.index + 1;
    return PopScope(
      canPop: _step == _RequestStep.item,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) {
          _back();
        }
      },
      child: Scaffold(
        appBar: AppBar(
          title: Text(_category.label),
          leading: IconButton(
            tooltip: 'Back',
            icon: const Icon(Icons.arrow_back_rounded),
            onPressed: _back,
          ),
          bottom: PreferredSize(
            preferredSize: const Size.fromHeight(28),
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 0, 20, 12),
              child: Row(
                children: [
                  Text(
                    'Step $stepNumber of 4',
                    style: const TextStyle(
                      color: AgriColors.muted,
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(99),
                      child: LinearProgressIndicator(
                        value: stepNumber / 4,
                        minHeight: 6,
                        color: AgriColors.forest,
                        backgroundColor: AgriColors.line,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
        body: SafeArea(
          child: AnimatedSwitcher(
            duration: const Duration(milliseconds: 220),
            child: ListView(
              key: ValueKey(_step),
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
              children: switch (_step) {
                _RequestStep.item => _itemStep(),
                _RequestStep.amount => _amountStepView(),
                _RequestStep.farm => _farmStep(),
                _RequestStep.review => _reviewStep(),
                _RequestStep.submitted => const [],
              },
            ),
          ),
        ),
        bottomNavigationBar: SafeArea(
          minimum: const EdgeInsets.fromLTRB(20, 8, 20, 16),
          child: FilledButton(
            onPressed: _canContinue ? _next : null,
            child: _submitting
                ? const SizedBox.square(
                    dimension: 22,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: Colors.white,
                    ),
                  )
                : Text(
                    _step == _RequestStep.review
                        ? 'Submit request'
                        : 'Continue',
                  ),
          ),
        ),
      ),
    );
  }

  List<Widget> _itemStep() => [
    const _StepTitle(
      title: 'What do you want to get?',
      subtitle: 'Pick the closest match. The partner will confirm details.',
    ),
    for (final item in _category.items)
      _ChoiceTile(
        title: item,
        leading: _TintedIcon(
          icon: _category.icon,
          tint: _category.tint,
          size: 36,
        ),
        selected: _item == item,
        onTap: () => setState(() => _item = item),
      ),
  ];

  List<Widget> _amountStepView() {
    final monthly = _repayment == null ? null : _amount / _repayment!;
    return [
      const _StepTitle(
        title: 'How much do you need?',
        subtitle: 'Choose an amount and how you would like to repay.',
      ),
      AgriCard(
        child: Column(
          children: [
            Text(
              _naira.format(_amount),
              style: Theme.of(context).textTheme.headlineMedium
                  ?.copyWith(color: AgriColors.forest),
            ),
            Slider(
              value: _amount,
              min: _category.minimumAmount.toDouble(),
              max: _category.maximumAmount.toDouble(),
              divisions:
                  ((_category.maximumAmount - _category.minimumAmount) /
                          _amountStep)
                      .round(),
              activeColor: AgriColors.forest,
              inactiveColor: AgriColors.line,
              label: _naira.format(_amount),
              onChanged: (value) =>
                  setState(() => _amount = _roundAmount(value)),
            ),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  _naira.format(_category.minimumAmount),
                  style: const TextStyle(color: AgriColors.muted, fontSize: 12),
                ),
                Text(
                  _naira.format(_category.maximumAmount),
                  style: const TextStyle(color: AgriColors.muted, fontSize: 12),
                ),
              ],
            ),
          ],
        ),
      ),
      const SectionHeading('Repayment'),
      Wrap(
        spacing: 8,
        runSpacing: 8,
        children: [
          for (final months in _repaymentMonths)
            ChoiceChip(
              label: Text(_repaymentLabel(months)),
              selected: _repayment == months,
              showCheckmark: false,
              selectedColor: AgriColors.leafSoft,
              side: BorderSide(
                color: _repayment == months
                    ? AgriColors.forest
                    : AgriColors.line,
              ),
              labelStyle: TextStyle(
                color: _repayment == months
                    ? AgriColors.forest
                    : AgriColors.ink,
                fontWeight: FontWeight.w600,
              ),
              onSelected: (_) => setState(() => _repayment = months),
            ),
        ],
      ),
      const SizedBox(height: 12),
      Text(
        monthly == null
            ? 'One payment after your harvest is sold.'
            : 'About ${_naira.format(monthly)} a month, before interest.',
        style: const TextStyle(color: AgriColors.muted, fontSize: 13),
      ),
    ];
  }

  List<Widget> _farmStep() {
    final farms = ref.watch(farmsProvider);
    return [
      const _StepTitle(
        title: 'Which farm is this for?',
        subtitle: 'Partners use your farm size and crop to assess the request.',
      ),
      ...farms.when(
        loading: () => const [
          Padding(
            padding: EdgeInsets.all(32),
            child: Center(child: CircularProgressIndicator()),
          ),
        ],
        error: (_, _) => const <Widget>[],
        data: (items) => [
          for (final farm in items)
            _ChoiceTile(
              title: farm.name,
              subtitle: _farmSummary(farm),
              leading: const _TintedIcon(
                icon: Icons.landscape_outlined,
                tint: AgriColors.grove,
                size: 36,
              ),
              selected: _farmChosen && _farmId == farm.id,
              onTap: () => setState(() {
                _farmId = farm.id;
                _farmChosen = true;
              }),
            ),
        ],
      ),
      _ChoiceTile(
        title: 'Not registered yet',
        subtitle: 'You can add your farm later',
        leading: const _TintedIcon(
          icon: Icons.help_outline_rounded,
          tint: AgriColors.muted,
          size: 36,
        ),
        selected: _farmChosen && _farmId == null,
        onTap: () => setState(() {
          _farmId = null;
          _farmChosen = true;
        }),
      ),
    ];
  }

  List<Widget> _reviewStep() {
    final farm = ref
        .watch(farmsProvider)
        .value
        ?.where((farm) => farm.id == _farmId)
        .firstOrNull;
    return [
      const _StepTitle(
        title: 'Check your request',
        subtitle: 'Make sure everything is right before you submit.',
      ),
      AgriCard(
        child: Column(
          children: [
            _ReviewRow(label: 'Category', value: _category.label),
            _ReviewRow(label: 'Item', value: _item),
            _ReviewRow(label: 'Amount', value: _naira.format(_amount)),
            _ReviewRow(label: 'Repayment', value: _repaymentLabel(_repayment)),
            _ReviewRow(
              label: 'Farm',
              value: farm?.name ?? 'Not registered yet',
              isLast: true,
            ),
          ],
        ),
      ),
      const SizedBox(height: 16),
      CheckboxListTile(
        value: _consented,
        onChanged: (value) => setState(() => _consented = value ?? false),
        controlAffinity: ListTileControlAffinity.leading,
        contentPadding: EdgeInsets.zero,
        activeColor: AgriColors.forest,
        title: const Text(
          'I agree that AgriShield can share these details and my phone number with lending partners.',
          style: TextStyle(fontSize: 13, height: 1.4),
        ),
      ),
    ];
  }

  String _farmSummary(Farm farm) => [
    if (farm.hectares != null) '${farm.hectares!.toStringAsFixed(1)} ha',
    ?farm.activeCropCycle?.cropName,
    ?farm.state,
  ].join(' · ');
}

class _StepTitle extends StatelessWidget {
  const _StepTitle({required this.title, required this.subtitle});

  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.only(top: 8, bottom: 20),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title, style: Theme.of(context).textTheme.headlineMedium),
        const SizedBox(height: 6),
        Text(
          subtitle,
          style: Theme.of(context).textTheme.bodyMedium
              ?.copyWith(color: AgriColors.muted),
        ),
      ],
    ),
  );
}

class _ChoiceTile extends StatelessWidget {
  const _ChoiceTile({
    required this.title,
    required this.leading,
    required this.selected,
    required this.onTap,
    this.subtitle,
  });

  final String title;
  final String? subtitle;
  final Widget leading;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.only(bottom: 10),
    child: Material(
      color: selected ? AgriColors.leafSoft : AgriColors.paper,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AgriRadius.md),
        side: BorderSide(
          color: selected ? AgriColors.forest : AgriColors.line,
          width: selected ? 1.6 : 1,
        ),
      ),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              leading,
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    if (subtitle != null && subtitle!.isNotEmpty)
                      Text(
                        subtitle!,
                        style: const TextStyle(
                          color: AgriColors.muted,
                          fontSize: 12,
                        ),
                      ),
                  ],
                ),
              ),
              Icon(
                selected
                    ? Icons.check_circle_rounded
                    : Icons.radio_button_unchecked_rounded,
                color: selected ? AgriColors.forest : AgriColors.lineStrong,
              ),
            ],
          ),
        ),
      ),
    ),
  );
}

class _ReviewRow extends StatelessWidget {
  const _ReviewRow({
    required this.label,
    required this.value,
    this.isLast = false,
  });

  final String label;
  final String value;
  final bool isLast;

  @override
  Widget build(BuildContext context) => Column(
    children: [
      Padding(
        padding: const EdgeInsets.symmetric(vertical: 10),
        child: Row(
          children: [
            Text(label, style: const TextStyle(color: AgriColors.muted)),
            const SizedBox(width: 16),
            Expanded(
              child: Text(
                value,
                textAlign: TextAlign.end,
                style: const TextStyle(fontWeight: FontWeight.w600),
              ),
            ),
          ],
        ),
      ),
      if (!isLast) const Divider(height: 1),
    ],
  );
}

class _SubmittedView extends StatelessWidget {
  const _SubmittedView({
    required this.reference,
    required this.summary,
    required this.phone,
  });

  final String reference;
  final String summary;
  final String? phone;

  @override
  Widget build(BuildContext context) => Scaffold(
    body: SafeArea(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            const Spacer(),
            TweenAnimationBuilder<double>(
              tween: Tween(begin: .6, end: 1),
              duration: const Duration(milliseconds: 450),
              curve: Curves.easeOutBack,
              builder: (context, scale, child) =>
                  Transform.scale(scale: scale, child: child),
              child: Container(
                width: 88,
                height: 88,
                decoration: const BoxDecoration(
                  color: AgriColors.leafSoft,
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.check_rounded,
                  color: AgriColors.forest,
                  size: 48,
                ),
              ),
            ),
            const SizedBox(height: 24),
            Text(
              'Request sent',
              style: Theme.of(context).textTheme.headlineMedium,
            ),
            const SizedBox(height: 8),
            Text(
              phone == null
                  ? 'A lending partner will contact you about the next steps.'
                  : 'A lending partner will call you on $phone about the next steps.',
              textAlign: TextAlign.center,
              style: const TextStyle(color: AgriColors.muted, height: 1.4),
            ),
            const SizedBox(height: 24),
            AgriCard(
              color: AgriColors.canvas,
              child: Row(
                children: [
                  const _TintedIcon(
                    icon: Icons.receipt_long_outlined,
                    tint: AgriColors.forest,
                    size: 36,
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          reference,
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                        Text(
                          summary,
                          style: const TextStyle(
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
            const Spacer(),
            FilledButton(
              onPressed: () => context.go('/'),
              child: const Text('Back to home'),
            ),
          ],
        ),
      ),
    ),
  );
}
