import 'package:agrishield_ai/app/providers.dart';
import 'package:agrishield_ai/core/theme/app_theme.dart';
import 'package:agrishield_ai/features/more/more_screen.dart';
import 'package:agrishield_ai/models/models.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

class _ProfileAuthController extends AuthController {
  int signOuts = 0;

  @override
  Future<AuthState> build() async => const AuthState(
    user: UserProfile(id: 1, name: 'Amina Bello', phone: '+2348012345678'),
    organizations: [
      Organization(id: 7, name: 'Zaria Maize Cluster', role: 'farmer'),
    ],
    activeOrganization: Organization(
      id: 7,
      name: 'Zaria Maize Cluster',
      role: 'farmer',
    ),
    isRestoring: false,
  );

  @override
  Future<void> signOut() async => signOuts++;
}

Future<_ProfileAuthController> _pumpProfile(WidgetTester tester) async {
  final controller = _ProfileAuthController();
  tester.view.physicalSize = const Size(1170, 3000);
  tester.view.devicePixelRatio = 3;
  addTearDown(tester.view.reset);
  await tester.pumpWidget(
    ProviderScope(
      overrides: [
        authControllerProvider.overrideWith(() => controller),
        farmsProvider.overrideWith(
          (ref) async => const [
            Farm(
              id: 'farm-1',
              name: 'North Field',
              status: 'active',
              hectares: 2.5,
              activeCropCycle: CropCycle(status: 'active', cropName: 'Maize'),
            ),
          ],
        ),
      ],
      child: MaterialApp(
        debugShowCheckedModeBanner: false,
        theme: buildAgriShieldTheme(),
        home: const MoreScreen(),
      ),
    ),
  );
  await tester.pumpAndSettle();
  return controller;
}

void main() {
  testWidgets('profile shows the farmer, their group and farm totals', (
    tester,
  ) async {
    await _pumpProfile(tester);

    expect(find.text('Amina Bello'), findsOneWidget);
    expect(find.text('AB'), findsOneWidget);
    expect(find.text('+2348012345678'), findsOneWidget);
    expect(find.text('Zaria Maize Cluster'), findsNWidgets(2));
    expect(find.text('2.5'), findsOneWidget);
    expect(find.byIcon(Icons.check_circle_rounded), findsOneWidget);
  });

  testWidgets('sign out asks for confirmation first', (tester) async {
    final controller = await _pumpProfile(tester);

    await tester.tap(find.text('Sign out'));
    await tester.pumpAndSettle();
    expect(find.text('Sign out of AgriShield?'), findsOneWidget);

    await tester.tap(find.text('Cancel'));
    await tester.pumpAndSettle();
    expect(controller.signOuts, 0);

    await tester.tap(find.text('Sign out'));
    await tester.pumpAndSettle();
    await tester.tap(find.widgetWithText(TextButton, 'Sign out'));
    await tester.pumpAndSettle();
    expect(controller.signOuts, 1);
  });
}
