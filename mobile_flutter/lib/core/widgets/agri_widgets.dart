import 'package:flutter/material.dart';

import '../api/api_client.dart';
import '../theme/app_theme.dart';

class AgriPage extends StatelessWidget {
  const AgriPage({
    super.key,
    required this.children,
    this.appBar,
    this.bottomNavigationBar,
    this.floatingActionButton,
  });
  final List<Widget> children;
  final PreferredSizeWidget? appBar;
  final Widget? bottomNavigationBar;
  final Widget? floatingActionButton;
  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: appBar,
    bottomNavigationBar: bottomNavigationBar,
    floatingActionButton: floatingActionButton,
    body: SafeArea(
      child: ListView(
        padding: const EdgeInsets.fromLTRB(
          AgriSpacing.md,
          AgriSpacing.md,
          AgriSpacing.md,
          112,
        ),
        children: children,
      ),
    ),
  );
}

class PageHeading extends StatelessWidget {
  const PageHeading({
    super.key,
    required this.title,
    required this.description,
    this.eyebrow,
  });
  final String title;
  final String description;
  final String? eyebrow;
  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.only(bottom: AgriSpacing.lg),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (eyebrow != null)
          Text(
            eyebrow!.toUpperCase(),
            style: const TextStyle(
              color: AgriColors.grove,
              fontWeight: FontWeight.w800,
              fontSize: 11,
              letterSpacing: 1.2,
            ),
          ),
        const SizedBox(height: 6),
        Text(title, style: Theme.of(context).textTheme.headlineMedium),
        const SizedBox(height: 6),
        Text(
          description,
          style: Theme.of(context).textTheme.bodyLarge
              ?.copyWith(color: AgriColors.muted),
        ),
      ],
    ),
  );
}

class SectionHeading extends StatelessWidget {
  const SectionHeading(this.label, {super.key, this.action});
  final String label;
  final Widget? action;
  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.only(top: AgriSpacing.lg, bottom: AgriSpacing.sm),
    child: Row(
      children: [
        Expanded(
          child: Text(label, style: Theme.of(context).textTheme.titleLarge),
        ),
        ?action,
      ],
    ),
  );
}

class AgriCard extends StatelessWidget {
  const AgriCard({
    super.key,
    required this.child,
    this.color,
    this.onTap,
    this.padding = const EdgeInsets.all(AgriSpacing.md),
  });
  final Widget child;
  final Color? color;
  final VoidCallback? onTap;
  final EdgeInsets padding;
  @override
  Widget build(BuildContext context) => Card(
    color: color,
    child: InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(AgriRadius.md),
      child: Padding(padding: padding, child: child),
    ),
  );
}

class StatusPill extends StatelessWidget {
  const StatusPill(this.label, {super.key, this.warning = false, this.icon});
  final String label;
  final bool warning;
  final IconData? icon;
  @override
  Widget build(BuildContext context) {
    final style = severityStyle(label, warning: warning);
    return DecoratedBox(
      decoration: BoxDecoration(
        color: style.background,
        borderRadius: BorderRadius.circular(99),
        border: Border.all(color: style.foreground.withValues(alpha: .18)),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon ?? style.icon, size: 13, color: style.foreground),
            const SizedBox(width: 5),
            Text(
              label.replaceAll('_', ' ').toUpperCase(),
              style: TextStyle(
                color: style.foreground,
                fontWeight: FontWeight.w900,
                fontSize: 9,
                letterSpacing: .75,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class SeverityStyle {
  const SeverityStyle(this.foreground, this.background, this.icon);
  final Color foreground;
  final Color background;
  final IconData icon;
}

SeverityStyle severityStyle(String? severity, {bool warning = false}) {
  switch (severity?.toLowerCase()) {
    case 'critical':
      return const SeverityStyle(
        AgriColors.critical,
        AgriColors.criticalSoft,
        Icons.crisis_alert_rounded,
      );
    case 'high':
      return const SeverityStyle(
        AgriColors.clay,
        AgriColors.claySoft,
        Icons.priority_high_rounded,
      );
    case 'moderate':
    case 'warning':
      return const SeverityStyle(
        Color(0xFF76510F),
        AgriColors.milletSoft,
        Icons.warning_amber_rounded,
      );
    case 'information':
    case 'info':
      return const SeverityStyle(
        AgriColors.sky,
        AgriColors.skySoft,
        Icons.info_outline_rounded,
      );
    default:
      if (warning) {
        return const SeverityStyle(
          Color(0xFF76510F),
          AgriColors.milletSoft,
          Icons.warning_amber_rounded,
        );
      }
      return const SeverityStyle(
        AgriColors.grove,
        AgriColors.leafSoft,
        Icons.check_circle_outline_rounded,
      );
  }
}

class DataFreshness extends StatelessWidget {
  const DataFreshness({super.key, required this.label, this.offline = false});
  final String label;
  final bool offline;

  @override
  Widget build(BuildContext context) => Row(
    mainAxisSize: MainAxisSize.min,
    children: [
      Icon(
        offline ? Icons.cloud_off_rounded : Icons.schedule_rounded,
        size: 14,
        color: AgriColors.muted,
      ),
      const SizedBox(width: 5),
      Flexible(
        child: Text(
          label,
          style: const TextStyle(
            color: AgriColors.muted,
            fontSize: 11,
            fontWeight: FontWeight.w700,
          ),
        ),
      ),
    ],
  );
}

class AsyncPane<T> extends StatelessWidget {
  const AsyncPane({
    super.key,
    required this.value,
    required this.data,
    required this.retry,
  });
  final AsyncSnapshot<T> value;
  final Widget Function(T) data;
  final VoidCallback retry;
  @override
  Widget build(BuildContext context) {
    if (value.connectionState != ConnectionState.done) {
      return const Center(
        child: Padding(
          padding: EdgeInsets.all(40),
          child: CircularProgressIndicator(),
        ),
      );
    }
    if (value.hasError) {
      return ErrorPanel(message: friendlyError(value.error), retry: retry);
    }
    return data(value.data as T);
  }
}

class ErrorPanel extends StatelessWidget {
  const ErrorPanel({super.key, required this.message, required this.retry});
  final String message;
  final VoidCallback retry;
  @override
  Widget build(BuildContext context) => AgriCard(
    color: AgriColors.claySoft,
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Icon(Icons.cloud_off_rounded, color: AgriColors.clay, size: 32),
        const SizedBox(height: 10),
        Text(
          'We could not load this',
          style: Theme.of(context).textTheme.titleMedium,
        ),
        const SizedBox(height: 4),
        Text(message),
        const SizedBox(height: 12),
        OutlinedButton.icon(
          onPressed: retry,
          icon: const Icon(Icons.refresh_rounded),
          label: const Text('Try again'),
        ),
      ],
    ),
  );
}

String friendlyError(Object? error) => error is ApiException
    ? error.message
    : 'Check your connection and try again.';

void showMessage(BuildContext context, String message) =>
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(message)));
