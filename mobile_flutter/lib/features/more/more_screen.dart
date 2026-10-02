import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../app/providers.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/agri_widgets.dart';

class MoreScreen extends ConsumerWidget {
  const MoreScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider).value;
    return AgriPage(
      children: [
        PageHeading(
          eyebrow: 'Account and services',
          title: auth?.user?.name ?? 'Your account',
          description: auth?.user?.phone ?? '',
        ),
        const SectionHeading('Farmer groups'),
        if (auth?.organizations.isEmpty ?? true)
          const AgriCard(
            child: Text(
              'You are using AgriShield independently. You can still use every farmer service.',
            ),
          )
        else
          ...auth!.organizations.map((organization) {
            final selected = auth.activeOrganization?.id == organization.id;
            return Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: AgriCard(
                onTap: () => ref
                    .read(authControllerProvider.notifier)
                    .selectOrganization(organization),
                child: Row(
                  children: [
                    Container(
                      width: 10,
                      height: 42,
                      decoration: BoxDecoration(
                        color: selected ? AgriColors.leaf : AgriColors.line,
                        borderRadius: BorderRadius.circular(8),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            organization.name,
                            style: Theme.of(context).textTheme.titleMedium,
                          ),
                          Text(
                            organization.role?.replaceAll('_', ' ') ?? 'Member',
                            style: const TextStyle(color: AgriColors.muted),
                          ),
                        ],
                      ),
                    ),
                    if (selected) const StatusPill('Active'),
                  ],
                ),
              ),
            );
          }),
        const SectionHeading('Services'),
        AgriCard(
          onTap: () => context.push('/finance'),
          child: const Row(
            children: [
              Icon(
                Icons.agriculture_outlined,
                color: AgriColors.indigo,
                size: 32,
              ),
              SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Asset access',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    Text(
                      'Equipment, inputs and finance programmes',
                      style: TextStyle(color: AgriColors.muted),
                    ),
                  ],
                ),
              ),
              Icon(Icons.chevron_right_rounded),
            ],
          ),
        ),
        const SectionHeading('App information'),
        const AgriCard(
          child: Column(
            children: [
              _Info(label: 'Language', value: 'English'),
              Divider(),
              _Info(
                label: 'Data protection',
                value: 'Encrypted device storage',
              ),
              Divider(),
              _Info(label: 'AI support', value: 'N-ATLAS + crop vision'),
            ],
          ),
        ),
        const SizedBox(height: 14),
        const Text(
          'N-ATLaS is an initiative of Nigeria’s Federal Ministry of Communications, Innovation & Digital Economy, powered by Awarri Technologies.',
          textAlign: TextAlign.center,
          style: TextStyle(color: AgriColors.muted, fontSize: 11, height: 1.5),
        ),
        const SizedBox(height: 18),
        OutlinedButton.icon(
          style: OutlinedButton.styleFrom(
            minimumSize: const Size.fromHeight(56),
            foregroundColor: AgriColors.clay,
          ),
          onPressed: () => ref.read(authControllerProvider.notifier).signOut(),
          icon: const Icon(Icons.logout_rounded),
          label: const Text('Sign out'),
        ),
      ],
    );
  }
}

class _Info extends StatelessWidget {
  const _Info({required this.label, required this.value});
  final String label;
  final String value;
  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 8),
    child: Row(
      children: [
        Expanded(
          child: Text(
            label,
            style: const TextStyle(fontWeight: FontWeight.w700),
          ),
        ),
        Text(
          value,
          style: const TextStyle(color: AgriColors.muted, fontSize: 12),
        ),
      ],
    ),
  );
}
