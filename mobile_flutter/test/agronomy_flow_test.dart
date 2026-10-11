import 'package:agrishield_ai/app/providers.dart';
import 'package:agrishield_ai/core/api/api_client.dart';
import 'package:agrishield_ai/core/theme/app_theme.dart';
import 'package:agrishield_ai/features/assist/agronomy_screen.dart';
import 'package:agrishield_ai/models/models.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

class _AgronomyApi extends ApiClient {
  String? farmId;
  String? question;
  String? language;
  bool answered = false;

  @override
  Future<List<AgronomyAdvice>> agronomyAdvice(String farmId) async {
    if (!answered) return [];
    return const [
      AgronomyAdvice(
        id: 'advice-1',
        language: 'ha',
        question: 'How do I inspect my maize crop?',
        answer: 'Inspect affected leaves and soil moisture.',
        nextSteps: ['Check the affected area'],
        uncertainty: 'No field visit has been made.',
        safetyNote: 'Ask an extension worker before treatment.',
      ),
    ];
  }

  @override
  Future<AgronomyAdvice> askAgronomist(
    String farmId,
    String question,
    String language,
  ) async {
    this.farmId = farmId;
    this.question = question;
    this.language = language;
    answered = true;
    return (await agronomyAdvice(farmId)).first;
  }
}

void main() {
  testWidgets(
    'farmer asks a farm-specific question in Hausa and sees safety guidance',
    (tester) async {
      tester.view.physicalSize = const Size(1170, 2532);
      tester.view.devicePixelRatio = 3;
      addTearDown(tester.view.reset);
      final api = _AgronomyApi();

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
                  hectares: 2,
                ),
              ],
            ),
          ],
          child: MaterialApp(
            theme: buildAgriShieldTheme(),
            home: const AgronomyScreen(),
          ),
        ),
      );
      await tester.pumpAndSettle();

      await tester.tap(find.text('English'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Hausa').last);
      await tester.pumpAndSettle();
      await tester.enterText(
        find.byType(TextField),
        'How do I inspect my maize crop?',
      );
      await tester.ensureVisible(find.text('Ask agronomist'));
      await tester.tap(find.text('Ask agronomist'));
      await tester.pumpAndSettle();

      expect(api.farmId, 'farm-1');
      expect(api.language, 'ha');
      expect(
        find.text('Inspect affected leaves and soil moisture.'),
        findsOneWidget,
      );
      expect(
        find.textContaining('Ask an extension worker before treatment.'),
        findsOneWidget,
      );
    },
  );
}
