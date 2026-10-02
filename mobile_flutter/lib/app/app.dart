import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:connectivity_plus/connectivity_plus.dart';

import '../core/theme/app_theme.dart';
import '../features/assist/assist_screens.dart';
import '../features/auth/auth_screens.dart';
import '../features/farms/farm_screens.dart';
import '../features/farms/farm_sections_screen.dart';
import '../features/finance/finance_screens.dart';
import '../features/home/home_screen.dart';
import '../features/map/map_screen.dart';
import '../features/more/more_screen.dart';
import 'providers.dart';

class AgriShieldApp extends ConsumerWidget {
  const AgriShieldApp({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) => MaterialApp.router(
    title: 'AgriShield AI',
    debugShowCheckedModeBanner: false,
    theme: buildAgriShieldTheme(),
    routerConfig: ref.watch(routerProvider),
  );
}

final routerProvider = Provider<GoRouter>((ref) {
  final auth = ref.watch(authControllerProvider);
  return GoRouter(
    initialLocation: '/splash',
    redirect: (context, state) {
      final location = state.matchedLocation;
      if (auth.isLoading) return location == '/splash' ? null : '/splash';
      final signedIn = auth.value?.isAuthenticated ?? false;
      final public =
          location == '/welcome' ||
          location == '/sign-in' ||
          location == '/register';
      if (!signedIn && !public) return '/welcome';
      if (signedIn && (public || location == '/splash')) return '/';
      return null;
    },
    routes: [
      GoRoute(path: '/splash', builder: (_, _) => const SplashScreen()),
      GoRoute(path: '/welcome', builder: (_, _) => const WelcomeScreen()),
      GoRoute(path: '/sign-in', builder: (_, _) => const SignInScreen()),
      GoRoute(path: '/register', builder: (_, _) => const RegisterScreen()),
      StatefulShellRoute.indexedStack(
        builder: (_, _, shell) => AppShell(shell: shell),
        branches: [
          StatefulShellBranch(
            routes: [GoRoute(path: '/', builder: (_, _) => const HomeScreen())],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(path: '/map', builder: (_, _) => const FarmMapScreen()),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(path: '/farms', builder: (_, _) => const FarmsScreen()),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(path: '/assist', builder: (_, _) => const AssistScreen()),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(path: '/more', builder: (_, _) => const MoreScreen()),
            ],
          ),
        ],
      ),
      GoRoute(path: '/farms/new', builder: (_, _) => const AddFarmScreen()),
      GoRoute(
        path: '/farms/:id',
        builder: (_, state) =>
            FarmDetailScreen(farmId: state.pathParameters['id']!),
      ),
      GoRoute(
        path: '/farms/:id/sections',
        builder: (_, state) =>
            FarmSectionsScreen(farmId: state.pathParameters['id']!),
      ),
      GoRoute(
        path: '/farms/:id/sections/new',
        builder: (_, state) =>
            FarmSectionFormScreen(farmId: state.pathParameters['id']!),
      ),
      GoRoute(
        path: '/farms/:id/sections/:sectionId/edit',
        builder: (_, state) => FarmSectionFormScreen(
          farmId: state.pathParameters['id']!,
          sectionId: int.parse(state.pathParameters['sectionId']!),
        ),
      ),
      GoRoute(
        path: '/diagnosis/new',
        builder: (_, state) =>
            DiagnosisScreen(farmId: state.uri.queryParameters['farmId']!),
      ),
      GoRoute(
        path: '/voice/new',
        builder: (_, state) =>
            VoiceScreen(farmId: state.uri.queryParameters['farmId']),
      ),
      GoRoute(path: '/finance', builder: (_, _) => const FinanceScreen()),
      GoRoute(
        path: '/finance/apply/:id',
        builder: (_, state) => FinanceApplyScreen(
          productId: int.parse(state.pathParameters['id']!),
        ),
      ),
    ],
  );
});

class AppShell extends ConsumerWidget {
  const AppShell({super.key, required this.shell});
  final StatefulNavigationShell shell;
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final connectivity = ref.watch(connectivityProvider).value;
    final offline =
        connectivity?.every((result) => result == ConnectivityResult.none) ??
        false;
    return Scaffold(
      body: Column(
        children: [
          if (offline)
            const SafeArea(
              bottom: false,
              child: ColoredBox(
                color: AgriColors.milletSoft,
                child: Padding(
                  padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  child: Row(
                    children: [
                      Icon(Icons.cloud_off_rounded, size: 18),
                      SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Offline · showing saved farm information',
                          style: TextStyle(fontWeight: FontWeight.w700),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          Expanded(child: shell),
        ],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: shell.currentIndex,
        onDestinationSelected: (index) =>
            shell.goBranch(index, initialLocation: index == shell.currentIndex),
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home_rounded),
            label: 'Today',
          ),
          NavigationDestination(
            icon: Icon(Icons.map_outlined),
            selectedIcon: Icon(Icons.map_rounded),
            label: 'Map',
          ),
          NavigationDestination(
            icon: Icon(Icons.landscape_outlined),
            selectedIcon: Icon(Icons.landscape_rounded),
            label: 'Farms',
          ),
          NavigationDestination(
            icon: Icon(Icons.graphic_eq_rounded),
            selectedIcon: Icon(Icons.mic_rounded),
            label: 'Ask',
          ),
          NavigationDestination(
            icon: Icon(Icons.person_outline_rounded),
            selectedIcon: Icon(Icons.person_rounded),
            label: 'Profile',
          ),
        ],
      ),
    );
  }
}
