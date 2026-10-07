import 'ai_response.dart';

/// Placeholder answers shown while the crop and voice AI are not connected.
///
/// Nothing here is a real diagnosis; the screens pick one of these locally
/// instead of calling the API.
const dummyCropDiagnoses = [
  AiResponse(
    badge: 'Northern corn leaf blight · 87% match',
    blocks: [
      AiParagraph(
        'Thanks for the clear photo. The long, **cigar-shaped grey-green lesions** on the leaf look most like **northern corn leaf blight**, a fungal disease that spreads quickly in warm, humid weather.',
      ),
      AiHeading('What I can see'),
      AiBullets([
        'Elongated lesions 3–10 cm long, running along the leaf',
        'Damage starting on the **lower leaves** and moving upward',
        'No sign of insect feeding holes, so pests are unlikely',
      ]),
      AiHeading('What to do this week'),
      AiSteps([
        '**Scout 10 plants in each row.** If more than half show lesions below the ear, treat the field.',
        'Remove and burn badly infected lower leaves. Do not leave them on the field.',
        'If you treat, use a fungicide containing **mancozeb or azoxystrobin**, following the label rate.',
        'Spray early in the morning or late afternoon when there is little wind.',
      ]),
      AiHeading('Next season'),
      AiBullets([
        'Plant a **resistant maize variety** (ask your agro-dealer for blight-tolerant seed)',
        'Rotate with soybean or groundnut instead of planting maize again',
      ]),
      AiCallout(
        'Wear gloves and a face mask when spraying, keep children away, and wait at least **14 days** before harvesting treated maize.',
      ),
    ],
  ),
  AiResponse(
    badge: 'Fall armyworm damage · 91% match',
    blocks: [
      AiParagraph(
        'This looks like **fall armyworm** feeding. The ragged holes and the wet, sawdust-like droppings (**frass**) inside the whorl are classic signs.',
      ),
      AiHeading('Why it matters'),
      AiParagraph(
        'Armyworm larvae feed inside the whorl and can destroy the growing point. Early action, while larvae are small, works best.',
      ),
      AiHeading('What to do now'),
      AiSteps([
        'Check 20 plants across the field. Treat if **more than 1 in 5 plants** have fresh damage.',
        'Apply the spray **directly into the whorl**, where the larvae hide.',
        'Use a product with **emamectin benzoate** or a Bt-based biopesticide, following the label.',
        'Re-check the field after 7 days and repeat only if new damage appears.',
      ]),
      AiHeading('Low-cost options'),
      AiBullets([
        'A pinch of **wood ash or fine sand** in the whorl can reduce feeding on small farms',
        'Plant early and at the same time as your neighbours to break the pest cycle',
      ]),
      AiCallout(
        'Never mix different pesticides together, and store chemicals away from food and water.',
      ),
    ],
  ),
  AiResponse(
    badge: 'Nitrogen deficiency · 78% match',
    blocks: [
      AiParagraph(
        'The **yellowing in a V-shape from the leaf tip** on the older leaves points to **nitrogen deficiency** rather than a disease. The plant is moving nitrogen from old leaves to new growth.',
      ),
      AiHeading('How to confirm'),
      AiBullets([
        'Yellowing starts on the **lowest leaves** first',
        'Newer leaves at the top stay green',
        'Plants look shorter and thinner than usual',
      ]),
      AiHeading('Recommended action'),
      AiSteps([
        'Top-dress with **urea at about 1 bag (50 kg) per hectare**, or follow your extension officer’s rate.',
        'Apply when the soil is moist, about **5 cm from the base** of each plant.',
        'Cover the fertiliser lightly with soil so it is not lost to the air.',
      ]),
      AiParagraph(
        'You should see greener leaves within **7–10 days**. If the yellowing keeps spreading after that, take another photo and I will look again.',
      ),
      AiCallout(
        'Do not apply fertiliser just before heavy rain, as much of it can wash away.',
      ),
    ],
  ),
];

/// A transcribed voice question and its placeholder answer.
class DummyVoiceExchange {
  const DummyVoiceExchange({required this.question, required this.answer});

  final String question;
  final AiResponse answer;
}

const dummyVoiceExchanges = [
  DummyVoiceExchange(
    question: 'When should I apply fertiliser to my maize, and how much?',
    answer: AiResponse(
      blocks: [
        AiParagraph(
          'Good question. For maize in Northern Nigeria, splitting your fertiliser into **two applications** gives the best yield.',
        ),
        AiHeading('Recommended schedule'),
        AiSteps([
          '**At planting or within 2 weeks:** apply NPK 15-15-15, about **4 bags (200 kg) per hectare**.',
          '**At 4–6 weeks (knee height):** top-dress with urea, about **2 bags (100 kg) per hectare**.',
        ]),
        AiHeading('Tips for better results'),
        AiBullets([
          'Apply when the soil is **moist**, not dry or waterlogged',
          'Place fertiliser **5 cm away** from the plant, then cover with soil',
          'Weed the field first so the crop gets the nutrients',
        ]),
        AiParagraph(
          'If you tell me your farm size, I can work out exactly how many bags you need.',
        ),
      ],
    ),
  ),
  DummyVoiceExchange(
    question: 'Something is eating holes in my maize leaves. What can I do?',
    answer: AiResponse(
      blocks: [
        AiParagraph(
          'Holes in maize leaves at this time of year are most often caused by **fall armyworm**.',
        ),
        AiHeading('Check first'),
        AiBullets([
          'Look inside the whorl for small green or brown caterpillars',
          'Look for **sawdust-like droppings** near the holes',
        ]),
        AiHeading('If you find armyworm'),
        AiSteps([
          'Spray directly into the whorl in the **early morning**.',
          'Use emamectin benzoate or a Bt biopesticide, following the label.',
          'Check again after one week.',
        ]),
        AiParagraph(
          'For a more accurate answer, use **Check crop** to send me a photo of the damaged leaves.',
        ),
        AiCallout(
          'Always wear gloves and a mask when spraying, and wash your hands afterwards.',
        ),
      ],
    ),
  ),
  DummyVoiceExchange(
    question: 'When is the best time to plant sorghum this year?',
    answer: AiResponse(
      blocks: [
        AiParagraph(
          'Sorghum does best when it is planted **after the rains are established**, not after the first shower.',
        ),
        AiHeading('A simple rule'),
        AiParagraph(
          'Wait until you have had **at least 20 mm of rain over 2–3 days** and the soil is moist to about a hand’s depth.',
        ),
        AiHeading('Typical planting windows'),
        AiBullets([
          '**Sudan savanna (Kano, Katsina, Jigawa):** late June to mid-July',
          '**Northern Guinea savanna (Kaduna, Bauchi):** mid-June to early July',
        ]),
        AiHeading('Before you plant'),
        AiSteps([
          'Treat seed with a fungicide-insecticide seed dressing.',
          'Space rows **75 cm apart**, with plants about **25 cm apart**.',
          'Plan to weed at 3 and 6 weeks after planting.',
        ]),
        AiParagraph(
          'I will also watch the weather forecast for your farm and remind you when conditions look right.',
        ),
      ],
    ),
  ),
];
