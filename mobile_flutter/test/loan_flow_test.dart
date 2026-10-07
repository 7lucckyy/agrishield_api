import 'package:agrishield_ai/app/providers.dart';
import 'package:agrishield_ai/core/theme/app_theme.dart';
import 'package:agrishield_ai/features/loans/loan_screens.dart';
import 'package:agrishield_ai/models/models.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';

class _SignedInAuthController extends AuthController {
  @override
  Future<AuthState> build() async => const AuthState(
    user: UserProfile(id: 1, name: 'Amina Bello', phone: '+2348012345678'),
    isRestoring: false,
  );
}

Future<void> _openLoans(WidgetTester tester) async {
  tester.view.physicalSize = const Size(1170, 2532);
  tester.view.devicePixelRatio = 3;
  addTearDown(tester.view.reset);
  final router = GoRouter(
    initialLocation: '/loans',
    routes: [
      GoRoute(
        path: '/',
        builder: (_, _) => const Scaffold(body: Text('Home')),
      ),
      GoRoute(path: '/loans', builder: (_, _) => const LoansScreen()),
      GoRoute(
        path: '/loans/request/:category',
        builder: (_, state) => LoanRequestScreen(
          category: LoanCategory.values.byName(
            state.pathParameters['category']!,
          ),
        ),
      ),
    ],
  );
  addTearDown(router.dispose);
  await tester.pumpWidget(
    ProviderScope(
      overrides: [
        authControllerProvider.overrideWith(_SignedInAuthController.new),
        farmsProvider.overrideWith(
          (ref) async => const [
            Farm(
              id: 'farm-1',
              name: 'North Field',
              status: 'active',
              hectares: 2.5,
            ),
          ],
        ),
      ],
      child: MaterialApp.router(
        theme: buildAgriShieldTheme(),
        routerConfig: router,
      ),
    ),
  );
  await tester.pumpAndSettle();
}

FilledButton _primaryButton(WidgetTester tester) =>
    tester.widget<FilledButton>(find.byType(FilledButton));

Future<void> _continue(WidgetTester tester) async {
  await tester.tap(find.byType(FilledButton));
  await tester.pumpAndSettle();
}

void main() {
  testWidgets('loans screen offers every borrowing category', (tester) async {
    await _openLoans(tester);

    for (final category in LoanCategory.values) {
      expect(find.text(category.label), findsOneWidget);
    }
  });

  testWidgets('farmer clicks through a tractor loan request', (tester) async {
    await _openLoans(tester);

    await tester.tap(find.text('Tractors & machinery'));
    await tester.pumpAndSettle();
    expect(find.text('Step 1 of 4'), findsOneWidget);

    await tester.tap(find.text('Power tiller'));
    await tester.pump();
    await _continue(tester);

    expect(find.text('How much do you need?'), findsOneWidget);
    await tester.tap(find.text('6 months'));
    await tester.pump();
    expect(find.textContaining('a month, before interest'), findsOneWidget);
    await _continue(tester);

    expect(find.text('Which farm is this for?'), findsOneWidget);
    expect(_primaryButton(tester).onPressed, isNull);
    await tester.tap(find.text('North Field'));
    await tester.pump();
    await _continue(tester);

    expect(find.text('Check your request'), findsOneWidget);
    expect(find.text('Power tiller'), findsOneWidget);
    expect(find.text('6 months'), findsOneWidget);
    expect(_primaryButton(tester).onPressed, isNull);
    await tester.tap(find.byType(Checkbox));
    await tester.pump();
    await _continue(tester);

    expect(find.text('Request sent'), findsOneWidget);
    expect(find.textContaining('+2348012345678'), findsOneWidget);
    expect(find.textContaining('AGL-'), findsOneWidget);

    await tester.tap(find.text('Back to home'));
    await tester.pumpAndSettle();
    expect(find.text('Home'), findsOneWidget);
  });

  testWidgets('back steps through the request before leaving it', (
    tester,
  ) async {
    await _openLoans(tester);
    await tester.tap(find.text('Seeds'));
    await tester.pumpAndSettle();
    await _continue(tester);
    expect(find.text('Step 2 of 4'), findsOneWidget);

    await tester.tap(find.byTooltip('Back'));
    await tester.pumpAndSettle();
    expect(find.text('Step 1 of 4'), findsOneWidget);

    await tester.tap(find.byTooltip('Back'));
    await tester.pumpAndSettle();
    expect(find.byType(LoansScreen), findsOneWidget);
  });
}
