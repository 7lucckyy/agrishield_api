class SubmissionResult {
  const SubmissionResult._(this.remoteId, this.isPendingSync);

  final String? remoteId;
  final bool isPendingSync;

  factory SubmissionResult.fromJson(Map<String, dynamic> json) {
    final rawId = json['id'];
    final remoteId = rawId?.toString().trim();

    return SubmissionResult._(
      remoteId == null || remoteId.isEmpty ? null : remoteId,
      json['status'] == 'pending' && rawId == null,
    );
  }
}
