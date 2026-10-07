import 'package:agrishield_ai/features/assist/ai_response.dart';
import 'package:agrishield_ai/features/assist/assist_response.dart';
import 'package:agrishield_ai/models/models.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('completed Gemini diagnosis shows the returned assessment', () {
    final diagnosis = Diagnosis.fromJson({
      'id': 'diagnosis-1',
      'status': 'completed',
      'diagnosis': 'Possible diagnosis: leaf blight',
      'recommendation':
          'Inspect nearby leaves and consult an extension worker.',
    });

    final answer = diagnosisResponse(diagnosis);

    expect(
      answer.blocks.whereType<AiHeading>().single.text,
      'Possible diagnosis: leaf blight',
    );
    expect(answer.plainText, contains('consult an extension worker'));
  });

  test('completed N-ATLaS voice request shows transcript and safety note', () {
    final request = VoiceRequest.fromJson({
      'id': 'voice-1',
      'status': 'completed',
      'source_language': 'ha',
      'response_language': 'ha',
      'transcript': 'Me ya sa ganyen ya zama rawaya?',
      'translated_transcript': 'Why are the leaves yellow?',
      'guidance': 'Check the soil moisture and look at nearby plants.',
      'safety_note': 'Ask an extension worker before applying treatment.',
    });

    final answer = voiceResponse(request);

    expect(request.transcript, 'Me ya sa ganyen ya zama rawaya?');
    expect(answer.plainText, contains('Why are the leaves yellow?'));
    expect(
      answer.blocks.whereType<AiCallout>().single.text,
      'Ask an extension worker before applying treatment.',
    );
  });
}
