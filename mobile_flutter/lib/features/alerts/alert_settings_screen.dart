import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../app/providers.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/agri_widgets.dart';
import '../../models/models.dart';

class AlertSettingsScreen extends ConsumerStatefulWidget {
  const AlertSettingsScreen({super.key});

  @override
  ConsumerState<AlertSettingsScreen> createState() =>
      _AlertSettingsScreenState();
}

class _AlertSettingsScreenState extends ConsumerState<AlertSettingsScreen> {
  final _phone = TextEditingController();
  final _whatsapp = TextEditingController();
  bool _loaded = false;
  bool _inApp = true;
  bool _push = false;
  bool _sms = false;
  bool _whatsappEnabled = false;
  bool _voice = false;
  bool _consent = false;
  bool _busy = false;
  String _language = 'en';
  String _severity = 'medium';

  @override
  void dispose() {
    _phone.dispose();
    _whatsapp.dispose();
    super.dispose();
  }

  void _load(AlertPreference preference) {
    if (_loaded) return;
    _loaded = true;
    _inApp = preference.inAppEnabled;
    _push = preference.pushEnabled;
    _sms = preference.smsEnabled;
    _whatsappEnabled = preference.whatsappEnabled;
    _voice = preference.voiceCallEnabled;
    _consent = preference.consent;
    _language = preference.language;
    _severity = preference.minimumSeverity;
    _phone.text = preference.phone ?? '';
    _whatsapp.text = preference.whatsappPhone ?? '';
  }

  Future<void> _save() async {
    if ((_sms || _voice) && _phone.text.trim().isEmpty) {
      showMessage(
        context,
        'Enter the phone number that should receive alerts.',
      );
      return;
    }
    if (_whatsappEnabled && _whatsapp.text.trim().isEmpty) {
      showMessage(context, 'Enter your WhatsApp number.');
      return;
    }
    if ((_sms || _whatsappEnabled || _voice) && !_consent) {
      showMessage(
        context,
        'Confirm consent before enabling phone, WhatsApp or voice-call alerts.',
      );
      return;
    }
    setState(() => _busy = true);
    try {
      await ref.read(apiClientProvider).updateAlertPreference({
        'in_app_enabled': _inApp,
        'push_enabled': _push,
        'sms_enabled': _sms,
        'whatsapp_enabled': _whatsappEnabled,
        'voice_call_enabled': _voice,
        'phone': _phone.text.trim().isEmpty ? null : _phone.text.trim(),
        'whatsapp_phone': _whatsapp.text.trim().isEmpty
            ? null
            : _whatsapp.text.trim(),
        'language': _language,
        'minimum_severity': _severity,
        'consent': _consent,
      });
      ref.invalidate(alertPreferenceProvider);
      if (mounted) showMessage(context, 'Alert choices saved.');
    } catch (error) {
      if (mounted) showMessage(context, friendlyError(error));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final preference = ref.watch(alertPreferenceProvider);
    return AgriPage(
      appBar: AppBar(title: const Text('Farm alerts')),
      children: [
        const PageHeading(
          eyebrow: 'You stay in control',
          title: 'Choose how warnings reach you',
          description: 'In-app alerts are private. SMS, WhatsApp and voice calls require your permission and a configured delivery partner.',
        ),
        preference.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => ErrorPanel(
            message: friendlyError(error),
            retry: () => ref.invalidate(alertPreferenceProvider),
          ),
          data: (value) {
            _load(value);
            return Column(
              children: [
                _ChannelTile(
                  icon: Icons.notifications_active_outlined,
                  title: 'Inside AgriShield',
                  subtitle: 'Keep alerts in the app',
                  value: _inApp,
                  onChanged: (next) => setState(() => _inApp = next),
                ),
                _ChannelTile(
                  icon: Icons.phone_android_rounded,
                  title: 'Phone notification',
                  subtitle: 'When supported on this device',
                  value: _push,
                  onChanged: (next) => setState(() => _push = next),
                ),
                _ChannelTile(
                  icon: Icons.sms_outlined,
                  title: 'SMS',
                  subtitle: 'Short text message for important warnings',
                  value: _sms,
                  onChanged: (next) => setState(() => _sms = next),
                ),
                _ChannelTile(
                  icon: Icons.chat_outlined,
                  title: 'WhatsApp',
                  subtitle: 'Message through the connected partner',
                  value: _whatsappEnabled,
                  onChanged: (next) => setState(() => _whatsappEnabled = next),
                ),
                _ChannelTile(
                  icon: Icons.call_outlined,
                  title: 'Voice call',
                  subtitle: 'Spoken critical warning for low-literacy access',
                  value: _voice,
                  onChanged: (next) => setState(() => _voice = next),
                ),
                if (_sms || _voice) ...[
                  const SizedBox(height: 12),
                  TextField(
                    controller: _phone,
                    keyboardType: TextInputType.phone,
                    decoration: const InputDecoration(
                      labelText: 'Phone number',
                      hintText: '+234…',
                    ),
                  ),
                ],
                if (_whatsappEnabled) ...[
                  const SizedBox(height: 12),
                  TextField(
                    controller: _whatsapp,
                    keyboardType: TextInputType.phone,
                    decoration: const InputDecoration(
                      labelText: 'WhatsApp number',
                      hintText: '+234…',
                    ),
                  ),
                ],
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: DropdownButtonFormField<String>(
                        initialValue: _language,
                        decoration: const InputDecoration(
                          labelText: 'Alert language',
                        ),
                        items: const [
                          DropdownMenuItem(value: 'en', child: Text('English')),
                          DropdownMenuItem(value: 'ha', child: Text('Hausa')),
                          DropdownMenuItem(value: 'yo', child: Text('Yorùbá')),
                          DropdownMenuItem(value: 'ig', child: Text('Igbo')),
                          DropdownMenuItem(value: 'pcm', child: Text('Pidgin')),
                        ],
                        onChanged: (value) =>
                            setState(() => _language = value!),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: DropdownButtonFormField<String>(
                        initialValue: _severity,
                        decoration: const InputDecoration(
                          labelText: 'Send from',
                        ),
                        items: const [
                          DropdownMenuItem(
                            value: 'low',
                            child: Text('Low risk'),
                          ),
                          DropdownMenuItem(
                            value: 'medium',
                            child: Text('Medium'),
                          ),
                          DropdownMenuItem(value: 'high', child: Text('High')),
                          DropdownMenuItem(
                            value: 'critical',
                            child: Text('Critical'),
                          ),
                        ],
                        onChanged: (value) =>
                            setState(() => _severity = value!),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                CheckboxListTile(
                  contentPadding: EdgeInsets.zero,
                  value: _consent,
                  onChanged: (value) =>
                      setState(() => _consent = value ?? false),
                  title: const Text(
                    'I give permission for AgriShield and its contracted delivery provider to contact these numbers with farm alerts.',
                  ),
                  subtitle: const Text(
                    'You can withdraw this permission here at any time.',
                  ),
                ),
                FilledButton(
                  onPressed: _busy ? null : _save,
                  child: Text(_busy ? 'Saving choices…' : 'Save alert choices'),
                ),
              ],
            );
          },
        ),
      ],
    );
  }
}

class _ChannelTile extends StatelessWidget {
  const _ChannelTile({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.value,
    required this.onChanged,
  });
  final IconData icon;
  final String title;
  final String subtitle;
  final bool value;
  final ValueChanged<bool> onChanged;
  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.only(bottom: 8),
    child: AgriCard(
      child: SwitchListTile(
        contentPadding: EdgeInsets.zero,
        secondary: Icon(icon, color: AgriColors.grove),
        title: Text(title, style: Theme.of(context).textTheme.titleMedium),
        subtitle: Text(subtitle),
        value: value,
        onChanged: onChanged,
      ),
    ),
  );
}
