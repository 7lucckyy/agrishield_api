import { useFocusEffect, router } from 'expo-router';
import { useCallback, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { Card, EmptyState, ErrorState, LoadingState, PageHeader, Pill, Screen, SectionTitle } from '@/components/ui';
import { colors, radii, spacing, typography } from '@/constants/theme';
import { api } from '@/lib/api';
import type { Diagnosis, Farm, VoiceRequest } from '@/types/api';

export default function AssistantScreen() {
  const [farm, setFarm] = useState<Farm | null>(null);
  const [voices, setVoices] = useState<VoiceRequest[]>([]);
  const [diagnoses, setDiagnoses] = useState<Diagnosis[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    setLoading(true); setError('');
    try {
      const farms = await api.farms({ per_page: 20 });
      const current = farms[0] ?? null;
      setFarm(current);
      const [voiceData, diagnosisData] = await Promise.all([api.voiceRequests(), current ? api.diagnoses(current.id, { per_page: 10 }) : Promise.resolve([])]);
      setVoices(voiceData); setDiagnoses(diagnosisData);
    } catch { setError('Your guidance history could not be loaded.'); }
    finally { setLoading(false); }
  }, []);
  useFocusEffect(useCallback(() => { void load(); }, [load]));

  return (
    <Screen>
      <PageHeader eyebrow="AI-assisted, agronomist-aware" title="Ask AgriShield" description="Speak naturally or photograph the crop. Guidance is linked to your farm context." />
      <View style={styles.actions}>
        <AssistantAction color={colors.sky} label="Ask by voice" sub="Hausa, English and more" onPress={() => router.push({ pathname: '/voice/new', params: { farmId: farm?.id } })} />
        <AssistantAction color={colors.milletSoft} label="Check a crop" sub="Photograph symptoms clearly" onPress={() => farm && router.push({ pathname: '/diagnosis/new', params: { farmId: farm.id } })} disabled={!farm} />
      </View>
      {loading ? <LoadingState label="Loading guidance history…" /> : error ? <ErrorState message={error} retry={load} /> : (
        <>
          <SectionTitle title="Recent crop checks" />
          {diagnoses.length ? diagnoses.slice(0, 4).map((item) => <Card key={item.id}><View style={styles.top}><Pill label={item.status} tone={item.status === 'completed' ? 'success' : item.status === 'expired' ? 'danger' : 'warning'} /><Text style={styles.date}>{formatDate(item.created_at)}</Text></View><Text style={styles.cardTitle}>{item.diagnosis ?? 'Analysis in progress'}</Text><Text numberOfLines={3} style={styles.body}>{item.recommendation ?? item.note ?? 'We will show the result when analysis finishes.'}</Text></Card>) : <EmptyState title="No crop checks yet" message="Take a clear photo of the affected leaf, stem or fruit to start." />}
          <SectionTitle title="Recent voice questions" />
          {voices.length ? voices.slice(0, 4).map((item) => <Card key={item.id}><View style={styles.top}><Pill label={item.response_language.toUpperCase()} tone="neutral" /><Pill label={item.status} tone={item.status === 'completed' ? 'success' : 'warning'} /></View><Text numberOfLines={2} style={styles.cardTitle}>{item.translated_transcript ?? item.transcript ?? 'Transcribing your question'}</Text><Text numberOfLines={3} style={styles.body}>{item.guidance ?? 'Guidance will appear when processing completes.'}</Text></Card>) : <EmptyState title="No voice questions yet" message="Record a question in the language that feels most natural." />}
        </>
      )}
    </Screen>
  );
}

function AssistantAction({ color, label, sub, onPress, disabled = false }: { color: string; label: string; sub: string; onPress: () => void; disabled?: boolean }) {
  return <Pressable accessibilityRole="button" disabled={disabled} onPress={onPress} style={({ pressed }) => [styles.action, { backgroundColor: color }, pressed && styles.pressed, disabled && styles.disabled]}><View style={styles.line} /><Text style={styles.actionTitle}>{label}</Text><Text style={styles.actionSub}>{sub}</Text></Pressable>;
}

const formatDate = (value?: string) => value ? new Date(value).toLocaleDateString('en-NG', { day: 'numeric', month: 'short' }) : '';
const styles = StyleSheet.create({
  actions: { flexDirection: 'row', gap: spacing.sm }, action: { flex: 1, minHeight: 148, borderRadius: radii.md, padding: spacing.md, justifyContent: 'flex-end', gap: 3 }, line: { width: 28, height: 5, borderRadius: 4, backgroundColor: colors.forest, marginBottom: 'auto' },
  actionTitle: { fontFamily: typography.display, fontSize: 20, fontWeight: '700', color: colors.ink }, actionSub: { fontFamily: typography.body, fontSize: 12, lineHeight: 17, color: colors.muted },
  top: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.sm }, date: { fontFamily: typography.body, fontSize: 12, color: colors.muted }, cardTitle: { fontFamily: typography.body, fontSize: 17, lineHeight: 22, fontWeight: '700', color: colors.ink }, body: { fontFamily: typography.body, fontSize: 14, lineHeight: 21, color: colors.muted },
  pressed: { opacity: 0.82, transform: [{ scale: 0.98 }] }, disabled: { opacity: 0.45 },
});
