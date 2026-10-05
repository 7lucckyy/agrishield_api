import 'package:agrishield_ai/core/theme/app_theme.dart';
import 'package:agrishield_ai/core/widgets/agri_widgets.dart';
import 'package:agrishield_ai/features/auth/auth_screens.dart';
import 'package:agrishield_ai/features/map/map_screen.dart';
import 'package:agrishield_ai/app/providers.dart';
import 'package:agrishield_ai/models/models.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('farm model reads crop sections and allocation summary', () {
    final farm = Farm.fromJson({
      'id': 'farm-1',
      'name': 'North Field',
      'status': 'active',
      'area': {'hectares': 5},
      'sections': [
        {
          'id': 10,
          'name': 'Section A',
          'crop': {'id': 2, 'name': 'Onion'},
          'area': {'hectares': 1.5, 'acres': 3.7065, 'farm_percentage': 30},
          'boundary_geojson': {
            'type': 'Polygon',
            'coordinates': [
              [
                [8.0, 12.0],
                [8.001, 12.0],
                [8.001, 12.001],
                [8.0, 12.0],
              ],
            ],
          },
        },
      ],
      'section_summary': {
        'count': 1,
        'allocated_hectares': 1.5,
        'remaining_hectares': 3.5,
      },
    });

    expect(farm.sections, hasLength(1));
    expect(farm.sections.single.name, 'Section A');
    expect(farm.sections.single.crop.name, 'Onion');
    expect(farm.sections.single.boundaryGeoJson?['type'], 'Polygon');
    expect(farm.sectionSummary?.remainingHectares, 3.5);
  });

  testWidgets('welcome screen communicates farmer-first value', (tester) async {
    await tester.pumpWidget(
      MaterialApp(theme: buildAgriShieldTheme(), home: const WelcomeScreen()),
    );
    expect(
      find.text('Know what your crop needs. Act with confidence.'),
      findsOneWidget,
    );
    expect(find.text('Create my farmer account'), findsOneWidget);
    expect(find.text('I already have an account'), findsOneWidget);
    expect(find.text('Photo: Mike Blyth · CC BY 2.5'), findsOneWidget);
  });

  testWidgets('critical status includes a visible severity label and icon', (
    tester,
  ) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: buildAgriShieldTheme(),
        home: const Scaffold(body: StatusPill('critical')),
      ),
    );

    expect(find.text('CRITICAL'), findsOneWidget);
    expect(find.byIcon(Icons.crisis_alert_rounded), findsOneWidget);
  });

  testWidgets('farm locations never invent a missing map position', (
    tester,
  ) async {
    const farm = Farm(
      id: 'farm-1',
      name: 'Kaduna North Field',
      status: 'active',
      locality: 'Zaria',
      state: 'Kaduna',
      hectares: 4.2,
    );
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          farmsProvider.overrideWith((ref) async => [farm]),
        ],
        child: MaterialApp(
          theme: buildAgriShieldTheme(),
          home: const FarmMapScreen(),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Farm locations'), findsOneWidget);
    expect(find.text('Kaduna North Field'), findsOneWidget);
    expect(find.text('Map position unavailable'), findsOneWidget);
    expect(
      find.textContaining('Farms without location data remain in the list'),
      findsOneWidget,
    );
  });
}
