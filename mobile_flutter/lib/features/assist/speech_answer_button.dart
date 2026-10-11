import 'package:flutter/material.dart';
import 'package:flutter_tts/flutter_tts.dart';

/// Uses the phone's installed voices; no recording or answer text is sent to a TTS server.
class SpeechAnswerButton extends StatefulWidget {
  const SpeechAnswerButton({
    super.key,
    required this.text,
    required this.language,
    this.autoPlay = false,
  });

  final String text;
  final String language;
  final bool autoPlay;

  @override
  State<SpeechAnswerButton> createState() => _SpeechAnswerButtonState();
}

class _SpeechAnswerButtonState extends State<SpeechAnswerButton> {
  final _tts = FlutterTts();
  bool _speaking = false;

  static const _locales = {
    'en': 'en-NG',
    'ha': 'ha-NG',
    'yo': 'yo-NG',
    'ig': 'ig-NG',
    'pcm': 'en-NG',
  };

  @override
  void initState() {
    super.initState();
    _tts.setCompletionHandler(() {
      if (mounted) setState(() => _speaking = false);
    });
    _tts.setCancelHandler(() {
      if (mounted) setState(() => _speaking = false);
    });
    _tts.setErrorHandler((_) {
      if (mounted) setState(() => _speaking = false);
    });
    if (widget.autoPlay) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) _toggle();
      });
    }
  }

  Future<void> _toggle() async {
    if (_speaking) {
      await _tts.stop();
      if (mounted) setState(() => _speaking = false);
      return;
    }

    final locale = _locales[widget.language] ?? 'en-NG';
    try {
      final available = await _tts.isLanguageAvailable(locale);
      if (available != true) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(
                'A $locale voice is not installed on this phone. You can read the answer below.',
              ),
            ),
          );
        }
        return;
      }
      await _tts.setLanguage(locale);
      await _tts.setSpeechRate(0.45);
      if (mounted) setState(() => _speaking = true);
      await _tts.speak(widget.text);
    } catch (_) {
      if (mounted) {
        setState(() => _speaking = false);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
              'Speech playback is unavailable on this phone. The written answer remains available.',
            ),
          ),
        );
      }
    }
  }

  @override
  void dispose() {
    _tts.stop();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => OutlinedButton.icon(
    onPressed: widget.text.trim().isEmpty ? null : _toggle,
    icon: Icon(_speaking ? Icons.stop_rounded : Icons.volume_up_rounded),
    label: Text(_speaking ? 'Stop reading' : 'Read answer aloud'),
  );
}
