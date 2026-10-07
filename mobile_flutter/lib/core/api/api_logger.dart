import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

/// Prints each API request, response and error to the debug console.
///
/// Password fields are always redacted. Bearer tokens are redacted too unless
/// [showTokens] is set, which the app only does in debug builds.
/// Validation messages under `errors` are kept, since they never echo values.
class ApiLogInterceptor extends Interceptor {
  ApiLogInterceptor({
    void Function(String message)? logPrint,
    this.maxBodyLength = 2000,
    this.showTokens = false,
  }) : _logPrint = logPrint ?? debugPrint;

  static const _startedAtKey = 'api_log_started_at';
  static const _redacted = '***';
  static const _passwordKeys = {
    'password',
    'password_confirmation',
    'current_password',
  };
  static const _tokenKeys = {'token', 'access_token', 'refresh_token'};

  final void Function(String message) _logPrint;
  final int maxBodyLength;

  /// Logs bearer tokens in full, e.g. to copy them into an API client.
  final bool showTokens;

  bool _isSensitive(String key) {
    final normalized = key.toLowerCase();
    return _passwordKeys.contains(normalized) ||
        (!showTokens && _tokenKeys.contains(normalized));
  }

  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) {
    options.extra[_startedAtKey] = DateTime.now();
    final lines = ['┌── → ${options.method} ${options.uri}'];
    final headers = _redactHeaders(options.headers);
    if (headers.isNotEmpty) {
      lines.add('│ headers: $headers');
    }
    if (options.data != null) {
      lines.add('│ body: ${_describeBody(options.data)}');
    }
    lines.add('└──');
    _logPrint(lines.join('\n'));
    handler.next(options);
  }

  @override
  void onResponse(Response response, ResponseInterceptorHandler handler) {
    final options = response.requestOptions;
    _logPrint(
      [
        '┌── ← ${response.statusCode} ${options.method} ${options.uri} ${_elapsed(options)}',
        '│ body: ${_describeBody(response.data)}',
        '└──',
      ].join('\n'),
    );
    handler.next(response);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    final error = err;
    final options = error.requestOptions;
    final status = error.response?.statusCode;
    final lines = [
      '┌── ✕ ${status ?? error.type.name} ${options.method} ${options.uri} ${_elapsed(options)}',
      if (error.message != null) '│ error: ${error.message}',
      if (error.response?.data != null)
        '│ body: ${_describeBody(error.response!.data)}',
      '└──',
    ];
    _logPrint(lines.join('\n'));
    handler.next(error);
  }

  String _elapsed(RequestOptions options) {
    final startedAt = options.extra[_startedAtKey];
    if (startedAt is! DateTime) {
      return '';
    }
    return '(${DateTime.now().difference(startedAt).inMilliseconds}ms)';
  }

  Map<String, dynamic> _redactHeaders(Map<String, dynamic> headers) => {
    for (final entry in headers.entries)
      entry.key: !showTokens && entry.key.toLowerCase() == 'authorization'
          ? 'Bearer $_redacted'
          : entry.value,
  };

  String _describeBody(Object? data) {
    final String text;
    if (data is FormData) {
      text = jsonEncode({
        'fields': _redact({
          for (final field in data.fields) field.key: field.value,
        }),
        'files': [
          for (final file in data.files)
            '${file.key}: ${file.value.filename} (${file.value.length} bytes)',
        ],
      });
    } else if (data is Map || data is List) {
      text = jsonEncode(_redact(data));
    } else {
      text = '$data';
    }
    return text.length > maxBodyLength
        ? '${text.substring(0, maxBodyLength)}… (${text.length} chars)'
        : text;
  }

  Object? _redact(Object? value) {
    if (value is Map) {
      return {
        for (final entry in value.entries)
          entry.key.toString(): _isSensitive(entry.key.toString())
              ? _redacted
              : entry.key == 'errors'
              ? entry.value
              : _redact(entry.value),
      };
    }
    if (value is List) {
      return value.map(_redact).toList();
    }
    return value;
  }
}
