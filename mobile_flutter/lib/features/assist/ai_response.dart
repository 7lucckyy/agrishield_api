import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../core/theme/app_theme.dart';

/// One piece of a formatted AI answer. Text may contain `**bold**` spans.
sealed class AiBlock {
  const AiBlock();

  /// Characters this block contributes to the typing animation.
  int get length;
}

class AiHeading extends AiBlock {
  const AiHeading(this.text);
  final String text;
  @override
  int get length => text.length;
}

class AiParagraph extends AiBlock {
  const AiParagraph(this.text);
  final String text;
  @override
  int get length => text.length;
}

class AiBullets extends AiBlock {
  const AiBullets(this.items);
  final List<String> items;
  @override
  int get length => items.fold(0, (total, item) => total + item.length);
}

class AiSteps extends AiBlock {
  const AiSteps(this.items);
  final List<String> items;
  @override
  int get length => items.fold(0, (total, item) => total + item.length);
}

class AiCallout extends AiBlock {
  const AiCallout(this.text);
  final String text;
  @override
  int get length => text.length;
}

class AiResponse {
  const AiResponse({required this.blocks, this.badge});

  final List<AiBlock> blocks;

  /// Short label shown above the answer, e.g. a diagnosis and confidence.
  final String? badge;

  int get length => blocks.fold(0, (total, block) => total + block.length);

  /// The answer as plain text, for copying.
  String get plainText => blocks
      .map(
        (block) => switch (block) {
          AiHeading(:final text) || AiParagraph(:final text) => text,
          AiCallout(:final text) => '⚠️ $text',
          AiBullets(:final items) => items.map((item) => '• $item').join('\n'),
          AiSteps(:final items) => [
            for (var index = 0; index < items.length; index++)
              '${index + 1}. ${items[index]}',
          ].join('\n'),
        },
      )
      .join('\n\n')
      .replaceAll('**', '');
}

/// Renders an [AiResponse] like a chat assistant: a short "thinking" pause,
/// then the answer types itself out, followed by copy and feedback actions.
class AiResponseView extends StatefulWidget {
  const AiResponseView({
    super.key,
    required this.response,
    this.thinkingDuration = const Duration(milliseconds: 900),
  });

  final AiResponse response;
  final Duration thinkingDuration;

  @override
  State<AiResponseView> createState() => _AiResponseViewState();
}

class _AiResponseViewState extends State<AiResponseView>
    with TickerProviderStateMixin {
  late final AnimationController _thinking = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 900),
  )..repeat();
  late final AnimationController _typing = AnimationController(
    vsync: this,
    // Roughly 220 characters a second, capped so long answers stay snappy.
    duration: Duration(
      milliseconds: (widget.response.length * 4.5).clamp(900, 5000).round(),
    ),
  );
  bool _isThinking = true;
  bool? _helpful;

  @override
  void initState() {
    super.initState();
    Future<void>.delayed(widget.thinkingDuration, () {
      if (!mounted) {
        return;
      }
      _thinking.stop();
      setState(() => _isThinking = false);
      _typing.forward();
    });
  }

  @override
  void dispose() {
    _thinking.dispose();
    _typing.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Row(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Container(
        width: 32,
        height: 32,
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [AgriColors.grove, AgriColors.forest],
          ),
          shape: BoxShape.circle,
        ),
        child: const Icon(
          Icons.auto_awesome_rounded,
          color: AgriColors.millet,
          size: 17,
        ),
      ),
      const SizedBox(width: 12),
      Expanded(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Padding(
              padding: EdgeInsets.only(top: 6, bottom: 8),
              child: Text(
                'AgriShield AI',
                style: TextStyle(fontWeight: FontWeight.w700),
              ),
            ),
            if (_isThinking)
              _ThinkingDots(animation: _thinking)
            else
              AnimatedBuilder(
                animation: _typing,
                builder: (context, _) => _buildAnswer(
                  (widget.response.length * _typing.value).round(),
                ),
              ),
          ],
        ),
      ),
    ],
  );

  Widget _buildAnswer(int visibleCharacters) {
    final isTyping = visibleCharacters < widget.response.length;
    var remaining = visibleCharacters;
    final children = <Widget>[];

    String take(String text) {
      final shown = text.substring(0, remaining.clamp(0, text.length));
      remaining -= text.length;
      return shown;
    }

    if (widget.response.badge != null) {
      children.add(_Badge(label: widget.response.badge!));
    }
    for (final block in widget.response.blocks) {
      if (remaining <= 0) {
        break;
      }
      children.add(switch (block) {
        AiHeading(:final text) => Padding(
          padding: const EdgeInsets.only(top: 6, bottom: 6),
          child: Text(
            take(text),
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
          ),
        ),
        AiParagraph(:final text) => Padding(
          padding: const EdgeInsets.only(bottom: 10),
          child: _RichLine(take(text)),
        ),
        AiCallout(:final text) => _Callout(text: take(text)),
        AiBullets(:final items) => _ListBlock(
          items: [
            for (final item in items)
              if (remaining > 0) take(item),
          ],
          numbered: false,
        ),
        AiSteps(:final items) => _ListBlock(
          items: [
            for (final item in items)
              if (remaining > 0) take(item),
          ],
          numbered: true,
        ),
      });
    }
    if (isTyping) {
      children.add(const _Cursor());
    } else {
      children.add(
        _ResponseActions(
          helpful: _helpful,
          onCopy: () {
            Clipboard.setData(ClipboardData(text: widget.response.plainText));
            ScaffoldMessenger.of(context)
                .showSnackBar(const SnackBar(content: Text('Answer copied')));
          },
          onFeedback: (helpful) => setState(() => _helpful = helpful),
        ),
      );
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: children,
    );
  }
}

class _ThinkingDots extends StatelessWidget {
  const _ThinkingDots({required this.animation});

  final Animation<double> animation;

  @override
  Widget build(BuildContext context) => Row(
    children: [
      AnimatedBuilder(
        animation: animation,
        builder: (context, _) => Row(
          children: [
            for (var index = 0; index < 3; index++)
              Container(
                width: 7,
                height: 7,
                margin: const EdgeInsets.only(right: 4),
                decoration: BoxDecoration(
                  color: AgriColors.grove.withValues(
                    alpha:
                        .25 +
                        .75 *
                            (1 -
                                    ((animation.value * 3 - index) % 3 - .5)
                                            .abs() *
                                        2)
                                .clamp(0, 1),
                  ),
                  shape: BoxShape.circle,
                ),
              ),
          ],
        ),
      ),
      const SizedBox(width: 6),
      const Text(
        'Thinking…',
        style: TextStyle(color: AgriColors.muted, fontSize: 13),
      ),
    ],
  );
}

class _Badge extends StatelessWidget {
  const _Badge({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) => Container(
    margin: const EdgeInsets.only(bottom: 10),
    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
    decoration: BoxDecoration(
      color: AgriColors.leafSoft,
      borderRadius: BorderRadius.circular(99),
    ),
    child: Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        const Icon(Icons.biotech_outlined, size: 15, color: AgriColors.forest),
        const SizedBox(width: 6),
        Flexible(
          child: Text(
            label,
            style: const TextStyle(
              color: AgriColors.forest,
              fontSize: 12,
              fontWeight: FontWeight.w700,
            ),
          ),
        ),
      ],
    ),
  );
}

/// Text with `**bold**` spans.
class _RichLine extends StatelessWidget {
  const _RichLine(this.text);

  final String text;

  @override
  Widget build(BuildContext context) {
    final parts = text.split('**');
    return Text.rich(
      TextSpan(
        style: const TextStyle(
          color: AgriColors.ink,
          fontSize: 15,
          height: 1.5,
        ),
        children: [
          for (var index = 0; index < parts.length; index++)
            TextSpan(
              text: parts[index],
              style: index.isOdd
                  ? const TextStyle(fontWeight: FontWeight.w700)
                  : null,
            ),
        ],
      ),
    );
  }
}

class _ListBlock extends StatelessWidget {
  const _ListBlock({required this.items, required this.numbered});

  final List<String> items;
  final bool numbered;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.only(bottom: 10),
    child: Column(
      children: [
        for (var index = 0; index < items.length; index++)
          Padding(
            padding: const EdgeInsets.only(bottom: 6),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                SizedBox(
                  width: 28,
                  child: numbered
                      ? Text(
                          '${index + 1}.',
                          style: const TextStyle(
                            fontWeight: FontWeight.w700,
                            height: 1.5,
                          ),
                        )
                      : const Padding(
                          padding: EdgeInsets.only(top: 9, left: 4),
                          child: CircleAvatar(
                            radius: 2.5,
                            backgroundColor: AgriColors.ink,
                          ),
                        ),
                ),
                Expanded(child: _RichLine(items[index])),
              ],
            ),
          ),
      ],
    ),
  );
}

class _Callout extends StatelessWidget {
  const _Callout({required this.text});

  final String text;

  @override
  Widget build(BuildContext context) => Container(
    margin: const EdgeInsets.only(top: 4, bottom: 10),
    padding: const EdgeInsets.all(12),
    decoration: BoxDecoration(
      color: AgriColors.milletSoft,
      borderRadius: BorderRadius.circular(AgriRadius.sm),
    ),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Icon(
          Icons.health_and_safety_outlined,
          size: 18,
          color: Color(0xFF76510F),
        ),
        const SizedBox(width: 8),
        Expanded(child: _RichLine(text)),
      ],
    ),
  );
}

class _Cursor extends StatelessWidget {
  const _Cursor();

  @override
  Widget build(BuildContext context) => Container(
    width: 8,
    height: 16,
    margin: const EdgeInsets.only(top: 2),
    color: AgriColors.grove,
  );
}

class _ResponseActions extends StatelessWidget {
  const _ResponseActions({
    required this.helpful,
    required this.onCopy,
    required this.onFeedback,
  });

  final bool? helpful;
  final VoidCallback onCopy;
  final ValueChanged<bool> onFeedback;

  @override
  Widget build(BuildContext context) => Row(
    children: [
      IconButton(
        tooltip: 'Copy answer',
        visualDensity: VisualDensity.compact,
        color: AgriColors.muted,
        onPressed: onCopy,
        icon: const Icon(Icons.copy_rounded, size: 18),
      ),
      IconButton(
        tooltip: 'Helpful',
        visualDensity: VisualDensity.compact,
        color: helpful == true ? AgriColors.forest : AgriColors.muted,
        onPressed: () => onFeedback(true),
        icon: Icon(
          helpful == true
              ? Icons.thumb_up_alt_rounded
              : Icons.thumb_up_alt_outlined,
          size: 18,
        ),
      ),
      IconButton(
        tooltip: 'Not helpful',
        visualDensity: VisualDensity.compact,
        color: helpful == false ? AgriColors.clay : AgriColors.muted,
        onPressed: () => onFeedback(false),
        icon: Icon(
          helpful == false
              ? Icons.thumb_down_alt_rounded
              : Icons.thumb_down_alt_outlined,
          size: 18,
        ),
      ),
      if (helpful != null)
        const Flexible(
          child: Text(
            'Thanks for the feedback',
            style: TextStyle(color: AgriColors.muted, fontSize: 12),
          ),
        ),
    ],
  );
}

/// A chat bubble for the farmer's own question.
class UserQuestionBubble extends StatelessWidget {
  const UserQuestionBubble({super.key, required this.text, this.caption});

  final String text;
  final String? caption;

  @override
  Widget build(BuildContext context) => Align(
    alignment: Alignment.centerRight,
    child: ConstrainedBox(
      constraints: const BoxConstraints(maxWidth: 300),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: const BoxDecoration(
              color: AgriColors.forest,
              borderRadius: BorderRadius.only(
                topLeft: Radius.circular(18),
                topRight: Radius.circular(18),
                bottomLeft: Radius.circular(18),
                bottomRight: Radius.circular(4),
              ),
            ),
            child: Text(
              text,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 15,
                height: 1.4,
              ),
            ),
          ),
          if (caption != null)
            Padding(
              padding: const EdgeInsets.only(top: 4, right: 4),
              child: Text(
                caption!,
                style: const TextStyle(color: AgriColors.muted, fontSize: 11),
              ),
            ),
        ],
      ),
    ),
  );
}
