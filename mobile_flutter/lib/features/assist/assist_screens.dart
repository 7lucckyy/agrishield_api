import 'dart:async';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import 'package:path_provider/path_provider.dart';
import 'package:record/record.dart';

import '../../app/providers.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/agri_widgets.dart';
import '../../models/models.dart';

final voiceHistoryProvider = FutureProvider.autoDispose<List<VoiceRequest>>(
  (ref) => ref.read(apiClientProvider).voiceRequests(),
);
final diagnosisHistoryProvider = FutureProvider.autoDispose
    .family<List<Diagnosis>, String>(
      (ref, farmId) => ref.read(apiClientProvider).diagnoses(farmId),
    );

class AssistScreen extends ConsumerWidget {
  const AssistScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final farms = ref.watch(farmsProvider).value ?? [];
    final farmId = farms.firstOrNull?.id;
    final voices = ref.watch(voiceHistoryProvider);
    final diagnoses = farmId == null
        ? const AsyncData<List<Diagnosis>>([])
        : ref.watch(diagnosisHistoryProvider(farmId));
    return AgriPage(
      children: [
        const PageHeading(
          eyebrow: 'AgriShield assistance',
          title: 'Show us or speak',
          description:
              'Get crop support without long forms or technical words.',
        ),
        _AssistChoice(
          color: AgriColors.milletSoft,
          icon: Icons.camera_alt_rounded,
          number: '01',
          title: 'Check a crop photo',
          description: 'Take a clear photo of the affected leaf, stem or fruit. AgriShield analyses visible symptoms and returns next steps.',
          action: 'Open camera',
          onTap: farmId == null
              ? null
              : () => context.push('/diagnosis/new?farmId=$farmId'),
        ),
        const SizedBox(height: 14),
        _AssistChoice(
          color: AgriColors.indigoSoft,
          icon: Icons.mic_rounded,
          number: '02',
          title: 'Ask by voice',
          description: 'Speak in Hausa, English, Yoruba or Igbo. Your question is transcribed and answered in your chosen language.',
          action: 'Record a question',
          onTap: () => context.push(
            '/voice/new${farmId == null ? '' : '?farmId=$farmId'}',
          ),
        ),
        if (farmId == null) ...[
          const SizedBox(height: 14),
          const AgriCard(
            color: AgriColors.claySoft,
            child: Text(
              'Add a farm before sending a crop photo. Voice questions can be asked without a farm.',
            ),
          ),
        ],
        const SizedBox(height: 14),
        const AgriCard(
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(Icons.health_and_safety_outlined, color: AgriColors.grove),
              SizedBox(width: 12),
              Expanded(
                child: Text(
                  'AI guidance supports—not replaces—an agronomist. For severe or rapidly spreading damage, contact a local extension officer.',
                ),
              ),
            ],
          ),
        ),
        const SectionHeading('Recent requests'),
        if (voices.isLoading || diagnoses.isLoading)
          const LinearProgressIndicator()
        else if (voices.hasError && diagnoses.hasError)
          ErrorPanel(
            message: 'Recent assistance could not be loaded.',
            retry: () {
              ref.invalidate(voiceHistoryProvider);
              if (farmId != null) {
                ref.invalidate(diagnosisHistoryProvider(farmId));
              }
            },
          )
        else if ((voices.value?.isEmpty ?? true) &&
            (diagnoses.value?.isEmpty ?? true))
          const AgriCard(
            child: Text(
              'Your crop checks and voice questions will appear here.',
            ),
          )
        else ...[
          ...(diagnoses.value ?? [])
              .take(3)
              .map(
                (item) => Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: _HistoryItem(
                    icon: Icons.camera_alt_outlined,
                    status: item.status,
                    title: item.diagnosis ?? 'Crop photo check',
                    detail:
                        item.recommendation ?? 'Analysis is being prepared.',
                  ),
                ),
              ),
          ...(voices.value ?? [])
              .take(3)
              .map(
                (item) => Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: _HistoryItem(
                    icon: Icons.mic_none_rounded,
                    status: item.status,
                    title: item.transcript ?? 'Voice question',
                    detail: item.guidance ?? 'Guidance is being prepared.',
                  ),
                ),
              ),
        ],
      ],
    );
  }
}

class _HistoryItem extends StatelessWidget {
  const _HistoryItem({
    required this.icon,
    required this.status,
    required this.title,
    required this.detail,
  });
  final IconData icon;
  final String status;
  final String title;
  final String detail;
  @override
  Widget build(BuildContext context) => AgriCard(
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, color: AgriColors.grove),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              StatusPill(status, warning: status == 'pending'),
              const SizedBox(height: 8),
              Text(
                title,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: Theme.of(context).textTheme.titleMedium,
              ),
              const SizedBox(height: 3),
              Text(
                detail,
                maxLines: 3,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(color: AgriColors.muted),
              ),
            ],
          ),
        ),
      ],
    ),
  );
}

class _AssistChoice extends StatelessWidget {
  const _AssistChoice({
    required this.color,
    required this.icon,
    required this.number,
    required this.title,
    required this.description,
    required this.action,
    required this.onTap,
  });
  final Color color;
  final IconData icon;
  final String number;
  final String title;
  final String description;
  final String action;
  final VoidCallback? onTap;
  @override
  Widget build(BuildContext context) => AgriCard(
    color: color,
    onTap: onTap,
    padding: const EdgeInsets.all(20),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Container(
              width: 52,
              height: 52,
              decoration: BoxDecoration(
                color: AgriColors.paper,
                borderRadius: BorderRadius.circular(17),
              ),
              child: Icon(icon, color: AgriColors.forest, size: 28),
            ),
            Text(
              number,
              style: const TextStyle(
                fontSize: 34,
                color: AgriColors.line,
                fontWeight: FontWeight.w900,
              ),
            ),
          ],
        ),
        const SizedBox(height: 24),
        Text(title, style: Theme.of(context).textTheme.titleLarge),
        const SizedBox(height: 7),
        Text(
          description,
          style: const TextStyle(color: AgriColors.muted, height: 1.45),
        ),
        const SizedBox(height: 18),
        Row(
          children: [
            Text(
              action,
              style: const TextStyle(
                color: AgriColors.forest,
                fontWeight: FontWeight.w800,
              ),
            ),
            const SizedBox(width: 5),
            const Icon(
              Icons.arrow_forward_rounded,
              size: 18,
              color: AgriColors.forest,
            ),
          ],
        ),
      ],
    ),
  );
}

class DiagnosisScreen extends ConsumerStatefulWidget {
  const DiagnosisScreen({super.key, required this.farmId});
  final String farmId;
  @override
  ConsumerState<DiagnosisScreen> createState() => _DiagnosisScreenState();
}

class _DiagnosisScreenState extends ConsumerState<DiagnosisScreen> {
  final _picker = ImagePicker();
  final _note = TextEditingController();
  XFile? _image;
  bool _busy = false;
  Map<String, dynamic>? _result;
  @override
  void dispose() {
    _note.dispose();
    super.dispose();
  }

  Future<void> _pick(ImageSource source) async {
    final image = await _picker.pickImage(
      source: source,
      imageQuality: 88,
      maxWidth: 2400,
      maxHeight: 2400,
    );
    if (image == null) return;
    final bytes = await image.length();
    if (bytes > 8 * 1024 * 1024) {
      if (mounted) showMessage(context, 'Choose a photo smaller than 8 MB.');
      return;
    }
    setState(() {
      _image = image;
      _result = null;
    });
  }

  Future<void> _submit() async {
    if (_image == null) {
      showMessage(context, 'Take or choose a crop photo first.');
      return;
    }
    setState(() => _busy = true);
    try {
      final result = await ref
          .read(apiClientProvider)
          .submitDiagnosis(
            farmId: widget.farmId,
            imagePath: _image!.path,
            note: _note.text.trim().isEmpty ? null : _note.text.trim(),
          );
      ref.invalidate(diagnosisHistoryProvider(widget.farmId));
      if (mounted) setState(() => _result = result);
    } catch (error) {
      if (mounted) showMessage(context, friendlyError(error));
    }
    if (mounted) setState(() => _busy = false);
  }

  @override
  Widget build(BuildContext context) => AgriPage(
    appBar: AppBar(title: const Text('Crop photo check')),
    children: [
      const PageHeading(
        eyebrow: 'N-ATLAS crop support',
        title: 'Show the affected area',
        description: 'Use daylight, keep the symptom in focus, and include one nearby healthy leaf if possible.',
      ),
      if (_image == null)
        AgriCard(
          color: AgriColors.leafSoft,
          child: Column(
            children: [
              const Icon(
                Icons.add_a_photo_outlined,
                size: 58,
                color: AgriColors.forest,
              ),
              const SizedBox(height: 12),
              Text(
                'Add one clear photo',
                style: Theme.of(context).textTheme.titleLarge,
              ),
              const SizedBox(height: 16),
              FilledButton.icon(
                onPressed: () => _pick(ImageSource.camera),
                icon: const Icon(Icons.camera_alt_outlined),
                label: const Text('Take photo'),
              ),
              const SizedBox(height: 8),
              TextButton.icon(
                onPressed: () => _pick(ImageSource.gallery),
                icon: const Icon(Icons.photo_library_outlined),
                label: const Text('Choose from phone'),
              ),
            ],
          ),
        )
      else ...[
        ClipRRect(
          borderRadius: BorderRadius.circular(AgriRadius.md),
          child: AspectRatio(
            aspectRatio: 4 / 3,
            child: Image.file(File(_image!.path), fit: BoxFit.cover),
          ),
        ),
        Align(
          alignment: Alignment.centerRight,
          child: TextButton.icon(
            onPressed: () => _pick(ImageSource.camera),
            icon: const Icon(Icons.refresh_rounded),
            label: const Text('Retake'),
          ),
        ),
        TextField(
          controller: _note,
          maxLength: 1000,
          minLines: 2,
          maxLines: 4,
          decoration: const InputDecoration(
            labelText: 'What have you noticed? (optional)',
            hintText: 'Example: yellow spots started three days ago',
          ),
        ),
        const SizedBox(height: 14),
        FilledButton.icon(
          onPressed: _busy ? null : _submit,
          icon: const Icon(Icons.auto_awesome_outlined),
          label: Text(_busy ? 'Uploading crop photo…' : 'Analyse crop'),
        ),
      ],
      if (_result != null) ...[
        const SectionHeading('Your crop check'),
        _DiagnosisResult(result: _result!),
      ],
    ],
  );
}

class _DiagnosisResult extends StatelessWidget {
  const _DiagnosisResult({required this.result});
  final Map<String, dynamic> result;
  @override
  Widget build(BuildContext context) => AgriCard(
    color: AgriColors.milletSoft,
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        StatusPill(result['status']?.toString() ?? 'submitted'),
        const SizedBox(height: 12),
        Text(
          result['diagnosis']?.toString() ?? 'Your photo was received',
          style: Theme.of(context).textTheme.titleLarge,
        ),
        const SizedBox(height: 8),
        Text(
          result['recommendation']?.toString() ??
              'Analysis is running. You can return later to see the result.',
          style: const TextStyle(height: 1.5),
        ),
      ],
    ),
  );
}

class VoiceScreen extends ConsumerStatefulWidget {
  const VoiceScreen({super.key, this.farmId});
  final String? farmId;
  @override
  ConsumerState<VoiceScreen> createState() => _VoiceScreenState();
}

class _VoiceScreenState extends ConsumerState<VoiceScreen> {
  final _recorder = AudioRecorder();
  String _source = 'ha';
  String _response = 'ha';
  bool _recording = false;
  bool _busy = false;
  int _seconds = 0;
  String? _path;
  Timer? _timer;
  Map<String, dynamic>? _result;
  @override
  void dispose() {
    _timer?.cancel();
    _recorder.dispose();
    super.dispose();
  }

  Future<void> _toggle() async {
    if (_recording) {
      final path = await _recorder.stop();
      _timer?.cancel();
      setState(() {
        _recording = false;
        _path = path;
      });
      return;
    }
    if (!await _recorder.hasPermission()) {
      if (mounted) {
        showMessage(context, 'Allow microphone access to ask by voice.');
      }
      return;
    }
    final directory = await getApplicationDocumentsDirectory();
    final path =
        '${directory.path}/agrishield_${DateTime.now().millisecondsSinceEpoch}.m4a';
    await _recorder.start(
      const RecordConfig(
        encoder: AudioEncoder.aacLc,
        bitRate: 64000,
        sampleRate: 44100,
      ),
      path: path,
    );
    setState(() {
      _recording = true;
      _seconds = 0;
      _path = null;
      _result = null;
    });
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) async {
      if (!mounted) return;
      setState(() => _seconds++);
      if (_seconds >= 120) await _toggle();
    });
  }

  Future<void> _submit() async {
    if (_path == null) return;
    setState(() => _busy = true);
    try {
      final result = await ref
          .read(apiClientProvider)
          .submitVoice(
            path: _path!,
            sourceLanguage: _source,
            responseLanguage: _response,
            farmId: widget.farmId,
          );
      ref.invalidate(voiceHistoryProvider);
      if (mounted) setState(() => _result = result);
    } catch (error) {
      if (mounted) showMessage(context, friendlyError(error));
    }
    if (mounted) setState(() => _busy = false);
  }

  @override
  Widget build(BuildContext context) => AgriPage(
    appBar: AppBar(title: const Text('Ask by voice')),
    children: [
      const PageHeading(
        eyebrow: 'N-ATLAS language support',
        title: 'Speak as you normally do',
        description: 'Ask one clear farming question. You can receive the guidance in a different supported language.',
      ),
      Row(
        children: [
          Expanded(
            child: DropdownButtonFormField(
              initialValue: _source,
              decoration: const InputDecoration(labelText: 'I will speak'),
              items: _languages,
              onChanged: (value) => setState(() => _source = value!),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: DropdownButtonFormField(
              initialValue: _response,
              decoration: const InputDecoration(labelText: 'Reply in'),
              items: _languages,
              onChanged: (value) => setState(() => _response = value!),
            ),
          ),
        ],
      ),
      const SizedBox(height: AgriSpacing.xl),
      Center(
        child: Semantics(
          button: true,
          label: _recording ? 'Stop recording' : 'Start recording',
          child: InkWell(
            onTap: _toggle,
            borderRadius: BorderRadius.circular(80),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 220),
              width: 144,
              height: 144,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: _recording ? AgriColors.clay : AgriColors.forest,
                border: Border.all(
                  color: _recording ? AgriColors.claySoft : AgriColors.leafSoft,
                  width: 12,
                ),
              ),
              child: Icon(
                _recording ? Icons.stop_rounded : Icons.mic_rounded,
                color: Colors.white,
                size: 56,
              ),
            ),
          ),
        ),
      ),
      const SizedBox(height: 16),
      Center(
        child: Text(
          _recording
              ? '${_seconds ~/ 60}:${(_seconds % 60).toString().padLeft(2, '0')} · tap to stop'
              : _path == null
              ? 'Tap to start recording'
              : 'Recording ready · ${_seconds}s',
          style: const TextStyle(
            fontWeight: FontWeight.w800,
            color: AgriColors.muted,
          ),
        ),
      ),
      if (_path != null && !_recording) ...[
        const SizedBox(height: 22),
        FilledButton.icon(
          onPressed: _busy ? null : _submit,
          icon: const Icon(Icons.send_rounded),
          label: Text(_busy ? 'Sending question…' : 'Send voice question'),
        ),
        TextButton(onPressed: _toggle, child: const Text('Record again')),
      ],
      if (_result != null) ...[
        const SectionHeading('AgriShield guidance'),
        AgriCard(
          color: AgriColors.indigoSoft,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              StatusPill(_result!['status']?.toString() ?? 'submitted'),
              const SizedBox(height: 12),
              Text(
                _result!['guidance']?.toString() ??
                    'Your recording was received. Guidance is being prepared.',
                style: Theme.of(context).textTheme.bodyLarge,
              ),
              if (_result!['safety_note'] != null) ...[
                const Divider(height: 28),
                Text(
                  _result!['safety_note'].toString(),
                  style: const TextStyle(color: AgriColors.muted),
                ),
              ],
            ],
          ),
        ),
      ],
    ],
  );
}

const _languages = [
  DropdownMenuItem(value: 'ha', child: Text('Hausa')),
  DropdownMenuItem(value: 'en', child: Text('English')),
  DropdownMenuItem(value: 'yo', child: Text('Yorùbá')),
  DropdownMenuItem(value: 'ig', child: Text('Igbo')),
];
