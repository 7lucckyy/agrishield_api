import 'package:agrishield_ai/app/providers.dart';
import 'package:agrishield_ai/core/api/api_client.dart';
import 'package:agrishield_ai/core/theme/app_theme.dart';
import 'package:agrishield_ai/features/home/home_screen.dart';
import 'package:agrishield_ai/models/models.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

const _farm = Farm(
  id: 'farm-1',
  name: 'Kaduna North Field',
  status: 'active',
  locality: 'Zaria',
  state: 'Kaduna',
  hectares: 4.2,
  activeCropCycle: CropCycle(status: 'active', cropName: 'Maize'),
);

class _SignedInAuthController extends AuthController {
  @override
  Future<AuthState> build() async => const AuthState(
    user: UserProfile(id: 1, name: 'Amina Bello'),
    isRestoring: false,
  );
}

class _FakeApiClient extends ApiClient {
  @override
  Future<List<WeatherDay>> weather(String farmId) async => [
    for (var day = 0; day < 5; day++)
      WeatherDay(
        date: DateTime(2026, 10, 5 + day).toIso8601String(),
        maximum: 33.0 - day,
        rainProbability: day * 20.0,
      ),
  ];

  @override
  Future<List<Advisory>> advisories(String farmId) async => const [
    Advisory(
      id: 1,
      type: 'pest',
      severity: 'high',
      title: 'Fall armyworm risk is rising',
      summary: 'Warm nights favour armyworm. Scout ten plants per row today.',
    ),
  ];
}

Future<void> pumpHome(WidgetTester tester) async {
  await tester.pumpWidget(
    ProviderScope(
      overrides: [
        authControllerProvider.overrideWith(_SignedInAuthController.new),
        apiClientProvider.overrideWithValue(_FakeApiClient()),
        farmsProvider.overrideWith((ref) async => [_farm]),
        outboxStatusProvider.overrideWith((ref) async => []),
      ],
      child: MaterialApp(
        debugShowCheckedModeBanner: false,
        theme: buildAgriShieldTheme(),
        home: const HomeScreen(),
      ),
    ),
  );
  await tester.pumpAndSettle();
}

void main() {
  testWidgets('home tab greets the farmer and summarises the focus farm', (
    tester,
  ) async {
    tester.view.physicalSize = const Size(1170, 2532);
    tester.view.devicePixelRatio = 3;
    addTearDown(tester.view.reset);

    await pumpHome(tester);

    expect(find.textContaining('Amina'), findsOneWidget);
    expect(find.text('A'), findsOneWidget);
    expect(find.text('Kaduna North Field'), findsWidgets);
    expect(find.text('33°C'), findsOneWidget);
    expect(find.text('Fall armyworm risk is rising'), findsOneWidget);
    expect(find.text('Maize'), findsOneWidget);
  });
}
