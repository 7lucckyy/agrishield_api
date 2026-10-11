enum SoilSignalLevel { good, attention, unknown }

class SoilSignal {
  const SoilSignal({
    required this.title,
    required this.status,
    required this.action,
    required this.level,
  });

  final String title;
  final String status;
  final String action;
  final SoilSignalLevel level;
}

class SoilGuidance {
  const SoilGuidance({
    required this.headline,
    required this.summary,
    required this.signals,
  });

  final String headline;
  final String summary;
  final List<SoilSignal> signals;
}

SoilGuidance buildSoilGuidance(List<Map<String, dynamic>> metrics) {
  final ph = _valueFor(metrics, 'soil_ph');
  final organicCarbon = _valueFor(metrics, 'soil_organic_carbon');

  final balance = switch (ph) {
    null => const SoilSignal(
      title: 'Soil balance',
      status: 'Not checked',
      action: 'Ask for a basic soil test before adding lime.',
      level: SoilSignalLevel.unknown,
    ),
    < 5.5 => const SoilSignal(
      title: 'Soil balance',
      status: 'May be too acidic',
      action: 'Ask an extension worker to confirm it before adding lime.',
      level: SoilSignalLevel.attention,
    ),
    > 7.3 => const SoilSignal(
      title: 'Soil balance',
      status: 'May be too alkaline',
      action: 'Do not add lime. Ask an extension worker to check the soil.',
      level: SoilSignalLevel.attention,
    ),
    _ => const SoilSignal(
      title: 'Soil balance',
      status: 'Looks balanced',
      action: 'Keep using safe soil-building practices.',
      level: SoilSignalLevel.good,
    ),
  };

  final organicMatter = switch (organicCarbon) {
    null => const SoilSignal(
      title: 'Organic matter',
      status: 'Not checked',
      action: 'Keep crop residue or add well-made compost when available.',
      level: SoilSignalLevel.unknown,
    ),
    < 1 => const SoilSignal(
      title: 'Organic matter',
      status: 'Needs building',
      action: 'Keep crop residue and add well-made compost before planting.',
      level: SoilSignalLevel.attention,
    ),
    < 2 => const SoilSignal(
      title: 'Organic matter',
      status: 'Could improve',
      action: 'Add compost and avoid burning crop residue.',
      level: SoilSignalLevel.attention,
    ),
    _ => const SoilSignal(
      title: 'Organic matter',
      status: 'Looks good',
      action: 'Keep crop residue and continue adding compost when available.',
      level: SoilSignalLevel.good,
    ),
  };

  const fertiliser = SoilSignal(
    title: 'Fertiliser plan',
    status: 'Confirm before buying',
    action: 'Show these readings to an extension worker or trusted adviser.',
    level: SoilSignalLevel.attention,
  );

  final needsAttention = [
    balance,
    organicMatter,
  ].any((signal) => signal.level == SoilSignalLevel.attention);

  return SoilGuidance(
    headline: needsAttention
        ? 'Check your soil before spending money'
        : 'Your soil looks stable',
    summary: needsAttention
        ? 'One soil sign needs attention. Follow the simple steps below before adding fertiliser or lime.'
        : 'Keep building the soil, and confirm the right fertiliser for your crop before buying it.',
    signals: [balance, organicMatter, fertiliser],
  );
}

double? _valueFor(List<Map<String, dynamic>> metrics, String metricType) {
  for (final metric in metrics) {
    if (metric['metric_type'] == metricType) {
      return (metric['value'] as num?)?.toDouble();
    }
  }

  return null;
}
