import 'package:agrishield_ai/features/assist/submission_result.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('uses the server request ID for an uploaded submission', () {
    final result = SubmissionResult.fromJson({
      'id': 'request-123',
      'status': 'queued',
    });

    expect(result.remoteId, 'request-123');
    expect(result.isPendingSync, isFalse);
  });

  test('recognises a submission saved in the offline outbox', () {
    final result = SubmissionResult.fromJson({
      'local_id': 'local-123',
      'status': 'pending',
    });

    expect(result.remoteId, isNull);
    expect(result.isPendingSync, isTrue);
  });

  test('does not convert a missing request ID to the string null', () {
    final result = SubmissionResult.fromJson({'status': 'queued'});

    expect(result.remoteId, isNull);
    expect(result.isPendingSync, isFalse);
  });
}
