import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../app/providers.dart';
import '../../core/widgets/agri_widgets.dart';
import '../../models/models.dart';
import 'speech_answer_button.dart';

final agronomyHistoryProvider = FutureProvider.autoDispose
    .family<List<AgronomyAdvice>, String>(
      (ref, farmId) => ref.read(apiClientProvider).agronomyAdvice(farmId),
    );

class AgronomyScreen extends ConsumerStatefulWidget {
  const AgronomyScreen({super.key});

  @override
  ConsumerState<AgronomyScreen> createState() => _AgronomyScreenState();
}

class _AgronomyScreenState extends ConsumerState<AgronomyScreen> {
  final _question = TextEditingController();
  String? _farmId;
  String _language = 'en';
  bool _busy = false;

  static const _languages = {
    'en': 'English',
    'ha': 'Hausa',
    'yo': 'Yorùbá',
    'ig': 'Igbo',
    'pcm': 'Nigerian Pidgin',
  };

  @override
  void dispose() {
    _question.dispose();
    super.dispose();
  }

  Future<void> _ask(String farmId) async {
    final question = _question.text.trim();
    if (question.length < 10) {
      showMessage(context, 'Describe your question in at least 10 characters.');
      return;
    }
    setState(() => _busy = true);
    try {
      await ref
          .read(apiClientProvider)
          .askAgronomist(farmId, question, _language);
      _question.clear();
      ref.invalidate(agronomyHistoryProvider(farmId));
    } catch (error) {
      if (mounted) showMessage(context, friendlyError(error));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final farms = ref.watch(farmsProvider);
    final farmList = farms.value ?? const <Farm>[];
    final selectedFarmId = farmList.any((farm) => farm.id == _farmId)
        ? _farmId
        : farmList.firstOrNull?.id;

    return AgriPage(
      appBar: AppBar(title: const Text('AI agronomist')),
      children: [
        const PageHeading(
          eyebrow: 'Ask about your crops',
          title: 'Field guidance in your language',
          description: 'Ask a question about your farm. Advice is based on the details you recorded and cannot replace a field visit.',
        ),
        const SectionHeading('Farm and language'),
        farms.when(
          loading: () => const LinearProgressIndicator(),
          error: (error, _) => ErrorPanel(
            message: friendlyError(error),
            retry: () => ref.invalidate(farmsProvider),
          ),
          data: (items) => items.isEmpty
              ? const AgriCard(
                  child: Text(
                    'Add a farm before asking for field-specific guidance.',
                  ),
                )
              : Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: items
                      .map(
                        (farm) => ChoiceChip(
                          label: Text(farm.name),
                          selected: selectedFarmId == farm.id,
                          onSelected: (_) => setState(() => _farmId = farm.id),
                        ),
                      )
                      .toList(),
                ),
        ),
        const SizedBox(height: 12),
        DropdownButtonFormField<String>(
          initialValue: _language,
          decoration: const InputDecoration(labelText: 'Response language'),
          items: _languages.entries
              .map(
                (entry) => DropdownMenuItem(
                  value: entry.key,
                  child: Text(entry.value),
                ),
              )
              .toList(),
          onChanged: (value) => setState(() => _language = value ?? 'en'),
        ),
        const SizedBox(height: 12),
        TextField(
          controller: _question,
          minLines: 3,
          maxLines: 6,
          maxLength: 1500,
          decoration: const InputDecoration(
            labelText: 'Your farming question',
            hintText: 'What are you seeing in the field?',
          ),
        ),
        const SizedBox(height: 8),
        FilledButton(
          onPressed: selectedFarmId == null || _busy
              ? null
              : () => _ask(selectedFarmId),
          child: Text(_busy ? 'Getting guidance…' : 'Ask agronomist'),
        ),
        const SizedBox(height: 8),
        OutlinedButton.icon(
          onPressed: selectedFarmId == null
              ? null
              : () => context.push(
                  '/voice/new?farmId=$selectedFarmId&purpose=agronomy',
                ),
          icon: const Icon(Icons.mic_rounded),
          label: const Text('Ask about this farm by voice'),
        ),
        const SectionHeading('Previous answers'),
        if (selectedFarmId != null)
          ref
              .watch(agronomyHistoryProvider(selectedFarmId))
              .when(
                loading: () => const LinearProgressIndicator(),
                error: (error, _) => ErrorPanel(
                  message: friendlyError(error),
                  retry: () =>
                      ref.invalidate(agronomyHistoryProvider(selectedFarmId)),
                ),
                data: (items) => items.isEmpty
                    ? const AgriCard(
                        child: Text(
                          'Your questions and answers will appear here.',
                        ),
                      )
                    : Column(
                        children: items
                            .map(
                              (advice) => Padding(
                                padding: const EdgeInsets.only(bottom: 12),
                                child: AgriCard(
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        advice.question,
                                        style: Theme.of(context)
                                            .textTheme
                                            .titleMedium,
                                      ),
                                      const SizedBox(height: 8),
                                      Text(advice.answer),
                                      SpeechAnswerButton(
                                        key: ValueKey(
                                          'agronomy-speech-${advice.id}',
                                        ),
                                        text: advice.answer,
                                        language: advice.language,
                                      ),
                                      if (advice.nextSteps.isNotEmpty) ...[
                                        const SizedBox(height: 8),
                                        for (final step in advice.nextSteps)
                                          Text('• $step'),
                                      ],
                                      const SizedBox(height: 8),
                                      Text(
                                        'What is uncertain: ${advice.uncertainty}',
                                      ),
                                      const SizedBox(height: 4),
                                      Text('Safety: ${advice.safetyNote}'),
                                    ],
                                  ),
                                ),
                              ),
                            )
                            .toList(),
                      ),
              ),
      ],
    );
  }
}
