import '../../models/models.dart';
import 'ai_response.dart';

AiResponse diagnosisResponse(Diagnosis diagnosis) => AiResponse(
  badge: 'Crop photo assessment',
  blocks: [
    AiHeading(diagnosis.diagnosis ?? 'Photo assessed'),
    if (diagnosis.recommendation case final recommendation?)
      AiParagraph(recommendation),
  ],
);

AiResponse voiceResponse(VoiceRequest request) => AiResponse(
  badge: 'Farming guidance',
  blocks: [
    if (request.translatedTranscript case final translation?)
      AiParagraph('Your question: $translation'),
    AiParagraph(request.guidance ?? 'Guidance is unavailable.'),
    if (request.safetyNote case final safetyNote?) AiCallout(safetyNote),
  ],
);
