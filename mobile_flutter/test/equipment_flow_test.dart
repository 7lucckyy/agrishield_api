import 'package:agrishield_ai/app/providers.dart';
import 'package:agrishield_ai/core/api/api_client.dart';
import 'package:agrishield_ai/core/theme/app_theme.dart';
import 'package:agrishield_ai/features/finance/finance_screens.dart';
import 'package:agrishield_ai/models/models.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';

class _EquipmentApi extends ApiClient {
  String? requestedFarmId;
  Json? submitted;

  @override
  Future<List<FinanceProduct>> financeProducts(String farmId) async {
    requestedFarmId = farmId;
    return const [
      FinanceProduct(
        id: 17,
        name: 'Tractor',
        category: 'mechanization',
        acquisitionOptions: ['lease', 'finance_purchase'],
        reasons: ['20.0 hectare large farm'],
      ),
    ];
  }

  @override
  Future<List<FinanceApplication>> financeApplications() async => [];

  @override
  Future<Json> applyForFinance(Json payload) async {
    submitted = payload;
    return {'id': 'request-1'};
  }
}

void main() {
  testWidgets('farmer sees farm-specific tractor and sends a lease request', (
    tester,
  ) async {
    tester.view.physicalSize = const Size(1170, 2532);
    tester.view.devicePixelRatio = 3;
    addTearDown(tester.view.reset);
    final api = _EquipmentApi();
    final router = GoRouter(
      initialLocation: '/finance',
      routes: [
        GoRoute(path: '/finance', builder: (_, _) => const FinanceScreen()),
        GoRoute(
          path: '/finance/apply/:id',
          builder: (_, state) => FinanceApplyScreen(
            productId: int.parse(state.pathParameters['id']!),
            farmId: state.uri.queryParameters['farmId']!,
          ),
        ),
      ],
    );
    addTearDown(router.dispose);

    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          apiClientProvider.overrideWithValue(api),
          farmsProvider.overrideWith(
            (ref) async => const [
              Farm(
                id: 'farm-1',
                name: 'North Field',
                status: 'active',
                hectares: 20,
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

    expect(api.requestedFarmId, 'farm-1');
    expect(find.text('Tractor'), findsOneWidget);
    expect(find.text('Leasing and purchase request available'), findsOneWidget);
    await tester.ensureVisible(find.text('Request this equipment'));
    await tester.tap(find.text('Request this equipment'));
    await tester.pumpAndSettle();

    expect(find.text('Lease'), findsOneWidget);
    await tester.enterText(
      find.widgetWithText(TextFormField, 'Your estimated budget (NGN)'),
      '250000',
    );
    await tester.enterText(
      find.widgetWithText(TextFormField, 'How will you use it?'),
      'I will prepare the farm for maize planting this season.',
    );
    await tester.ensureVisible(find.byType(CheckboxListTile));
    await tester.tap(find.byType(CheckboxListTile));
    await tester.ensureVisible(find.text('Send equipment request'));
    await tester.tap(find.text('Send equipment request'));
    await tester.pumpAndSettle();

    expect(api.submitted?['farm_id'], 'farm-1');
    expect(api.submitted?['asset_finance_product_id'], 17);
    expect(api.submitted?['acquisition_mode'], 'lease');
    expect(find.text('Track your request'), findsOneWidget);
  });
}
