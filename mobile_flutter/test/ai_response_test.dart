import 'package:agrishield_ai/core/theme/app_theme.dart';
import 'package:agrishield_ai/features/assist/ai_response.dart';
import 'package:agrishield_ai/features/assist/dummy_ai.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

Future<void> _pumpAnswer(WidgetTester tester, AiResponse response) async {
  tester.view.physicalSize = const Size(1170, 4000);
  tester.view.devicePixelRatio = 3;
  addTearDown(tester.view.reset);
  await tester.pumpWidget(
    MaterialApp(
      theme: buildAgriShieldTheme(),
      home: Scaffold(
        body: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: Column(
            children: [
              const UserQuestionBubble(text: 'Is my maize sick?'),
              AiResponseView(response: response),
            ],
          ),
        ),
      ),
    ),
  );
}

void main() {
  testWidgets('answer thinks, types out, then offers copy and feedback', (
    tester,
  ) async {
    final response = dummyCropDiagnoses.first;
    await _pumpAnswer(tester, response);
    expect(find.text('Thinking…'), findsOneWidget);
    expect(find.text('Northern corn leaf blight · 87% match'), findsNothing);

    await tester.pump(const Duration(seconds: 1));
    await tester.pump(const Duration(milliseconds: 300));
    expect(find.text('Thinking…'), findsNothing);
    expect(find.byTooltip('Copy answer'), findsNothing);

    await tester.pumpAndSettle();
    expect(find.text('Northern corn leaf blight · 87% match'), findsOneWidget);
    expect(find.text('What to do this week'), findsOneWidget);

    await tester.ensureVisible(find.byTooltip('Copy answer'));
    await tester.pumpAndSettle();
    await tester.tap(find.byTooltip('Copy answer'));
    await tester.pump();
    expect(find.text('Answer copied'), findsOneWidget);

    await tester.tap(find.byTooltip('Helpful'));
    await tester.pump();
    expect(find.text('Thanks for the feedback'), findsOneWidget);
    await tester.pump(const Duration(seconds: 5));
  });

  test('copied answers are plain text without markup', () {
    final text = dummyCropDiagnoses.first.plainText;
    expect(text, contains('northern corn leaf blight'));
    expect(text, contains('1. '));
    expect(text, contains('• '));
    expect(text, isNot(contains('**')));
  });

  test('every placeholder answer has content to show', () {
    for (final response in [
      ...dummyCropDiagnoses,
      ...dummyVoiceExchanges.map((exchange) => exchange.answer),
    ]) {
      expect(response.length, greaterThan(200));
      expect(response.blocks.whereType<AiHeading>(), isNotEmpty);
    }
    for (final exchange in dummyVoiceExchanges) {
      expect(exchange.question, endsWith('?'));
    }
  });
}
