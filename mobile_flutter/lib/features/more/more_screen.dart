import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../app/providers.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/agri_widgets.dart';
import '../../models/models.dart';

class MoreScreen extends ConsumerWidget {
  const MoreScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider).value;
    final user = auth?.user;
    final organizations = auth?.organizations ?? const <Organization>[];
    final farms = ref.watch(farmsProvider).value;
    return AgriPage(
      children: [
        Text('Profile', style: Theme.of(context).textTheme.headlineMedium),
        const SizedBox(height: AgriSpacing.md),
        _ProfileHeader(
          name: user?.name ?? 'Your account',
          phone: user?.phone,
          groupLabel: auth?.activeOrganization?.name ?? 'Independent farmer',
        ),
        const SizedBox(height: 12),
        _FarmStats(farms: farms),
        const SectionHeading('Farmer groups'),
        if (organizations.isEmpty)
          const _SettingsGroup(
            children: [
              _SettingsTile(
                icon: Icons.person_pin_circle_outlined,
                tint: AgriColors.leaf,
                title: 'Farming independently',
                subtitle: 'You can still use every farmer service. Join a group with a cluster code.',
              ),
            ],
          )
        else
          _SettingsGroup(
            children: [
              for (final organization in organizations)
                _SettingsTile(
                  icon: Icons.groups_outlined,
                  tint: AgriColors.leaf,
                  title: organization.name,
                  subtitle: _roleLabel(organization),
                  trailing: auth?.activeOrganization?.id == organization.id
                      ? const Icon(
                          Icons.check_circle_rounded,
                          color: AgriColors.leaf,
                          semanticLabel: 'Active group',
                        )
                      : const Icon(
                          Icons.radio_button_unchecked_rounded,
                          color: AgriColors.lineStrong,
                        ),
                  onTap: () => ref
                      .read(authControllerProvider.notifier)
                      .selectOrganization(organization),
                ),
            ],
          ),
        const SectionHeading('Services'),
        _SettingsGroup(
          children: [
            _SettingsTile(
              icon: Icons.landscape_outlined,
              tint: AgriColors.grove,
              title: 'My farms',
              subtitle: 'Boundaries, sections and crops',
              onTap: () => context.go('/farms'),
            ),
            _SettingsTile(
              icon: Icons.agriculture_outlined,
              tint: AgriColors.indigo,
              title: 'Asset access',
              subtitle: 'Equipment, inputs and finance programmes',
              onTap: () => context.push('/finance'),
            ),
          ],
        ),
        const SectionHeading('App'),
        _SettingsGroup(
          children: [
            _SettingsTile(
              icon: Icons.translate_rounded,
              tint: AgriColors.sky,
              title: 'Language',
              value: _languageName(user?.locale),
            ),
            const _SettingsTile(
              icon: Icons.lock_outline_rounded,
              tint: AgriColors.soil,
              title: 'Data protection',
              value: 'Encrypted on device',
            ),
            const _SettingsTile(
              icon: Icons.auto_awesome_outlined,
              tint: AgriColors.water,
              title: 'AI support',
              value: 'N-ATLaS + crop vision',
            ),
          ],
        ),
        const SizedBox(height: AgriSpacing.lg),
        _SettingsGroup(
          children: [
            _SettingsTile(
              icon: Icons.logout_rounded,
              tint: AgriColors.clay,
              title: 'Sign out',
              titleColor: AgriColors.clay,
              trailing: const SizedBox.shrink(),
              onTap: () => _confirmSignOut(context, ref),
            ),
          ],
        ),
        const SizedBox(height: AgriSpacing.lg),
        const Text(
          'N-ATLaS is an initiative of Nigeria’s Federal Ministry of Communications, Innovation & Digital Economy, powered by Awarri Technologies.',
          textAlign: TextAlign.center,
          style: TextStyle(color: AgriColors.muted, fontSize: 11, height: 1.5),
        ),
      ],
    );
  }

  Future<void> _confirmSignOut(BuildContext context, WidgetRef ref) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Sign out of AgriShield?'),
        content: const Text(
          'You will need your phone number and password to sign back in.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text('Cancel'),
          ),
          TextButton(
            style: TextButton.styleFrom(foregroundColor: AgriColors.clay),
            onPressed: () => Navigator.of(context).pop(true),
            child: const Text('Sign out'),
          ),
        ],
      ),
    );
    if (confirmed ?? false) {
      await ref.read(authControllerProvider.notifier).signOut();
    }
  }
}

class _ProfileHeader extends StatelessWidget {
  const _ProfileHeader({
    required this.name,
    required this.phone,
    required this.groupLabel,
  });

  final String name;
  final String? phone;
  final String groupLabel;

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
        Container(
          width: 60,
          height: 60,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: AgriColors.millet,
            shape: BoxShape.circle,
            border: Border.all(color: const Color(0x40FFFFFF), width: 3),
          ),
          child: Text(
            _initials(name),
            style: const TextStyle(
              color: AgriColors.ink,
              fontSize: 20,
              fontWeight: FontWeight.w700,
            ),
          ),
        ),
        const SizedBox(width: 16),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                name,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: Theme.of(context).textTheme.titleLarge
                    ?.copyWith(color: Colors.white),
              ),
              if (phone != null && phone!.isNotEmpty) ...[
                const SizedBox(height: 2),
                Text(
                  phone!,
                  style: const TextStyle(
                    color: Color(0xFFC7D4CD),
                    fontSize: 13,
                  ),
                ),
              ],
              const SizedBox(height: 10),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 5,
                ),
                decoration: BoxDecoration(
                  color: const Color(0x1FFFFFFF),
                  borderRadius: BorderRadius.circular(99),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(
                      Icons.eco_outlined,
                      size: 14,
                      color: AgriColors.millet,
                    ),
                    const SizedBox(width: 5),
                    Flexible(
                      child: Text(
                        groupLabel,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ],
    ),
  );
}

class _FarmStats extends StatelessWidget {
  const _FarmStats({required this.farms});

  /// Null while farms are still loading.
  final List<Farm>? farms;

  @override
  Widget build(BuildContext context) {
    final farms = this.farms;
    final hectares = farms?.fold<double>(
      0,
      (total, farm) => total + (farm.hectares ?? 0),
    );
    final crops = farms
        ?.map((farm) => farm.activeCropCycle?.cropName)
        .whereType<String>()
        .toSet()
        .length;
    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _StatTile(
            value: farms == null ? '–' : '${farms.length}',
            label: 'Farms',
          ),
          const SizedBox(width: 10),
          _StatTile(
            value: hectares == null ? '–' : hectares.toStringAsFixed(1),
            label: 'Hectares',
          ),
          const SizedBox(width: 10),
          _StatTile(value: crops == null ? '–' : '$crops', label: 'Crops'),
        ],
      ),
    );
  }
}

class _StatTile extends StatelessWidget {
  const _StatTile({required this.value, required this.label});

  final String value;
  final String label;

  @override
  Widget build(BuildContext context) => Expanded(
    child: Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: AgriColors.paper,
        border: Border.all(color: AgriColors.line),
        borderRadius: BorderRadius.circular(AgriRadius.md),
      ),
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
          Text(
            label,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(color: AgriColors.muted, fontSize: 12),
          ),
        ],
      ),
    ),
  );
}

/// A rounded card that stacks [_SettingsTile]s with hairline dividers.
class _SettingsGroup extends StatelessWidget {
  const _SettingsGroup({required this.children});

  final List<Widget> children;

  @override
  Widget build(BuildContext context) => Card(
    child: Column(
      children: [
        for (var index = 0; index < children.length; index++) ...[
          if (index > 0) const Divider(height: 1, indent: 64),
          children[index],
        ],
      ],
    ),
  );
}

class _SettingsTile extends StatelessWidget {
  const _SettingsTile({
    required this.icon,
    required this.tint,
    required this.title,
    this.subtitle,
    this.value,
    this.trailing,
    this.titleColor,
    this.onTap,
  });

  final IconData icon;
  final Color tint;
  final String title;
  final String? subtitle;
  final String? value;
  final Widget? trailing;
  final Color? titleColor;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) => InkWell(
    onTap: onTap,
    child: Padding(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      child: Row(
        children: [
          Container(
            width: 36,
            height: 36,
            decoration: BoxDecoration(
              color: tint.withValues(alpha: .12),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, color: tint, size: 20),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: TextStyle(
                    color: titleColor ?? AgriColors.ink,
                    fontSize: 15,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                if (subtitle != null) ...[
                  const SizedBox(height: 2),
                  Text(
                    subtitle!,
                    style: const TextStyle(
                      color: AgriColors.muted,
                      fontSize: 12,
                      height: 1.35,
                    ),
                  ),
                ],
              ],
            ),
          ),
          if (value != null) ...[
            const SizedBox(width: 12),
            Flexible(
              child: Text(
                value!,
                textAlign: TextAlign.end,
                style: const TextStyle(color: AgriColors.muted, fontSize: 13),
              ),
            ),
          ],
          if (trailing != null) ...[
            const SizedBox(width: 8),
            trailing!,
          ] else if (onTap != null) ...[
            const SizedBox(width: 8),
            const Icon(Icons.chevron_right_rounded, color: AgriColors.muted),
          ],
        ],
      ),
    ),
  );
}

String _initials(String name) {
  final parts = name
      .trim()
      .split(RegExp(r'\s+'))
      .where((part) => part.isNotEmpty);
  if (parts.isEmpty) {
    return '?';
  }
  return parts
      .take(2)
      .map((part) => part.characters.first.toUpperCase())
      .join();
}

String _roleLabel(Organization organization) {
  final role = organization.role?.replaceAll('_', ' ').trim() ?? '';
  final label = role.isEmpty
      ? 'Member'
      : '${role[0].toUpperCase()}${role.substring(1)}';
  return organization.clusterName == null
      ? label
      : '$label · ${organization.clusterName}';
}

String _languageName(String? locale) => switch (locale) {
  'ha' => 'Hausa',
  'yo' => 'Yorùbá',
  'ig' => 'Igbo',
  _ => 'English',
};
