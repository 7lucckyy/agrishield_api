import 'package:agrishield_ai/features/farms/soil_guidance.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('turns technical readings into cautious farmer actions', () {
    final guidance = buildSoilGuidance([
      {'metric_type': 'soil_ph', 'value': 7.4157},
      {'metric_type': 'soil_organic_carbon', 'value': 3.4455},
      {'metric_type': 'soil_nitrogen', 'value': 38.1081},
    ]);

    expect(guidance.headline, 'Check your soil before spending money');
    expect(guidance.signals[0].status, 'May be too alkaline');
    expect(guidance.signals[0].action, contains('Do not add lime'));
    expect(guidance.signals[1].status, 'Looks good');
    expect(guidance.signals[2].status, 'Confirm before buying');
  });

  test('does not invent a reading when soil values are absent', () {
    final guidance = buildSoilGuidance([]);

    expect(guidance.signals[0].status, 'Not checked');
    expect(guidance.signals[1].status, 'Not checked');
  });
}
