import {
  RecordingPresets,
  requestRecordingPermissionsAsync,
  setAudioModeAsync,
  useAudioRecorder,
  useAudioRecorderState,
} from 'expo-audio';
import * as Haptics from 'expo-haptics';
import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader, Pill, PrimaryButton, Screen, TextButton } from '@/components/ui';
import { colors, radii, spacing, typography } from '@/constants/theme';
import { ApiError, api } from '@/lib/api';
import type { VoiceRequest } from '@/types/api';

const sourceLanguages = [
  ['auto', 'Auto detect'], ['ha', 'Hausa'], ['en', 'English'], ['ff', 'Fulfulde'], ['kr', 'Kanuri'], ['pcm', 'Pidgin'],
] as const;
const responseLanguages = [['ha', 'Hausa'], ['en', 'English'], ['yo', 'Yoruba'], ['ig', 'Igbo']] as const;

export default function NewVoiceScreen() {
  const { farmId } = useLocalSearchParams<{ farmId?: string }>();
  const recorder = useAudioRecorder(RecordingPresets.HIGH_QUALITY);
  const recorderState = useAudioRecorderState(recorder, 200);
  const [sourceLanguage, setSourceLanguage] = useState('auto');
  const [responseLanguage, setResponseLanguage] = useState('ha');
  const [audioUri, setAudioUri] = useState<string | null>(null);
  const [recordedDuration, setRecordedDuration] = useState(0);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [result, setResult] = useState<VoiceRequest | null>(null);

  const start = async () => {
    setError(''); setAudioUri(null); setRecordedDuration(0);
    const permission = await requestRecordingPermissionsAsync();
    if (!permission.granted) { setError('Microphone permission is needed to record your question.'); return; }
    await setAudioModeAsync({ allowsRecording: true, playsInSilentMode: true });
    await recorder.prepareToRecordAsync();
    recorder.record({ forDuration: 120 });
    await Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Medium);
  };

  const stop = async () => {
    const duration = Math.max(1, Math.round(recorderState.durationMillis / 1000));
    await recorder.stop();
    setAudioUri(recorder.uri ?? recorderState.url ?? null);
    setRecordedDuration(duration);
    await Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
  };

  const returnToAsk = () => {
    router.dismissAll();
    router.replace('/(tabs)/assistant');
  };

  const submit = async () => {
    if (!audioUri) return;
    setLoading(true); setError('');
    try {
      const response = await api.submitVoice({ uri: audioUri, name: `question-${Date.now()}.m4a`, type: 'audio/mp4' }, sourceLanguage, responseLanguage, farmId);
      setResult(response); await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
    } catch (reason) { setError(reason instanceof ApiError ? reason.message : 'Your voice note could not be sent. Try again.'); }
    finally { setLoading(false); }
  };

  if (result) return (
    <Screen>
      <TextButton label="Close" onPress={() => router.back()} />
      <PageHeader eyebrow="Voice question received" title="Your guidance is being prepared" description="AgriShield will keep the question in your history. You can safely leave this screen." />
      <View style={styles.result}><Pill label={result.status} tone={result.status === 'completed' ? 'success' : 'warning'} /><Text style={styles.resultTitle}>{result.translated_transcript ?? result.transcript ?? 'Transcribing your question'}</Text><Text style={styles.body}>{result.guidance ?? 'The response will appear in Ask when processing completes.'}</Text>{result.safety_note ? <Text style={styles.safety}>{result.safety_note}</Text> : null}</View>
      <PrimaryButton label="Return to Ask" onPress={returnToAsk} />
    </Screen>
  );

  const seconds = recorderState.isRecording ? Math.round(recorderState.durationMillis / 1000) : recordedDuration;
  return (
    <Screen>
      <TextButton label="Cancel" onPress={() => router.back()} />
      <PageHeader eyebrow="Voice guidance" title="Ask in your own words" description="Speak for up to two minutes. Mention the crop, what changed and when you first noticed it." />
      <Text style={styles.label}>I am speaking</Text><View style={styles.chips}>{sourceLanguages.map(([code, label]) => <LanguageChip key={code} active={sourceLanguage === code} label={label} onPress={() => setSourceLanguage(code)} />)}</View>
      <Text style={styles.label}>Reply to me in</Text><View style={styles.chips}>{responseLanguages.map(([code, label]) => <LanguageChip key={code} active={responseLanguage === code} label={label} onPress={() => setResponseLanguage(code)} />)}</View>
      <View style={styles.recorder}>
        <View style={styles.wave}><View style={styles.barSmall} /><View style={styles.bar} /><View style={styles.barTall} /><View style={styles.bar} /><View style={styles.barSmall} /></View>
        <Text style={styles.timer}>{String(Math.floor(seconds / 60)).padStart(2, '0')}:{String(seconds % 60).padStart(2, '0')}</Text>
        <Text style={styles.status}>{recorderState.isRecording ? 'Recording… tap to finish' : audioUri ? 'Voice note ready to send' : 'Tap when you are ready'}</Text>
        <Pressable accessibilityLabel={recorderState.isRecording ? 'Stop recording' : 'Start recording'} accessibilityRole="button" onPress={recorderState.isRecording ? stop : start} style={({ pressed }) => [styles.recordButton, recorderState.isRecording && styles.recording, pressed && styles.pressed]}><View style={recorderState.isRecording ? styles.stop : styles.mic} /></Pressable>
      </View>
      {audioUri ? <TextButton label="Record again" onPress={start} /> : null}
      {error ? <Text accessibilityRole="alert" style={styles.error}>{error}</Text> : null}
      <PrimaryButton disabled={!audioUri || recorderState.isRecording} label="Send voice question" loading={loading} onPress={submit} />
    </Screen>
  );
}

function LanguageChip({ label, active, onPress }: { label: string; active: boolean; onPress: () => void }) {
  return <Pressable accessibilityRole="button" accessibilityState={{ selected: active }} onPress={onPress} style={[styles.chip, active && styles.chipActive]}><Text style={[styles.chipText, active && styles.chipTextActive]}>{label}</Text></Pressable>;
}

const styles = StyleSheet.create({
  label: { fontFamily: typography.body, fontSize: 14, fontWeight: '700', color: colors.ink }, chips: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  chip: { minHeight: 44, justifyContent: 'center', borderRadius: radii.pill, borderWidth: 1, borderColor: colors.line, paddingHorizontal: 15, backgroundColor: colors.paper }, chipActive: { backgroundColor: colors.forest, borderColor: colors.forest }, chipText: { fontFamily: typography.body, fontSize: 14, color: colors.ink, fontWeight: '600' }, chipTextActive: { color: colors.paper },
  recorder: { minHeight: 300, borderRadius: radii.lg, backgroundColor: colors.forest, alignItems: 'center', justifyContent: 'center', padding: spacing.lg, gap: spacing.sm },
  wave: { height: 52, flexDirection: 'row', alignItems: 'center', gap: 7 }, barSmall: { width: 5, height: 16, borderRadius: 5, backgroundColor: colors.millet }, bar: { width: 5, height: 30, borderRadius: 5, backgroundColor: colors.millet }, barTall: { width: 5, height: 48, borderRadius: 5, backgroundColor: colors.millet },
  timer: { fontFamily: typography.data, fontSize: 30, color: colors.paper, fontWeight: '700' }, status: { fontFamily: typography.body, fontSize: 14, color: colors.leafSoft },
  recordButton: { width: 76, height: 76, borderRadius: 76, backgroundColor: colors.paper, alignItems: 'center', justifyContent: 'center', marginTop: spacing.md }, recording: { backgroundColor: '#F4DDDA' }, mic: { width: 22, height: 32, borderRadius: 14, backgroundColor: colors.forest }, stop: { width: 24, height: 24, borderRadius: 5, backgroundColor: colors.danger }, pressed: { transform: [{ scale: 0.94 }] },
  error: { color: colors.danger, fontFamily: typography.body, fontSize: 14 }, result: { backgroundColor: colors.sky, borderRadius: radii.lg, padding: spacing.lg, gap: spacing.md }, resultTitle: { fontFamily: typography.display, color: colors.ink, fontSize: 23, lineHeight: 29, fontWeight: '700' }, body: { fontFamily: typography.body, color: colors.muted, fontSize: 16, lineHeight: 24 }, safety: { fontFamily: typography.body, color: colors.warning, fontSize: 13, lineHeight: 19 },
});
