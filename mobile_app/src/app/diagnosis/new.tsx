import * as Haptics from 'expo-haptics';
import * as ImagePicker from 'expo-image-picker';
import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Image, StyleSheet, Text, View } from 'react-native';

import { Field, PageHeader, Pill, PrimaryButton, Screen, TextButton } from '@/components/ui';
import { colors, radii, spacing, typography } from '@/constants/theme';
import { ApiError, api } from '@/lib/api';
import type { Diagnosis } from '@/types/api';

export default function NewDiagnosisScreen() {
  const { farmId } = useLocalSearchParams<{ farmId: string }>();
  const [asset, setAsset] = useState<ImagePicker.ImagePickerAsset | null>(null);
  const [note, setNote] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [result, setResult] = useState<Diagnosis | null>(null);

  const returnToAsk = () => {
    router.dismissAll();
    router.replace('/(tabs)/assistant');
  };

  const choose = async (camera: boolean) => {
    setError('');
    if (camera) {
      const permission = await ImagePicker.requestCameraPermissionsAsync();
      if (!permission.granted) { setError('Camera permission is needed to photograph crop symptoms.'); return; }
    }
    const response = camera
      ? await ImagePicker.launchCameraAsync({ mediaTypes: ['images'], quality: 0.82, allowsEditing: false })
      : await ImagePicker.launchImageLibraryAsync({ mediaTypes: ['images'], quality: 0.82, allowsEditing: false });
    if (!response.canceled) setAsset(response.assets[0]);
  };

  const submit = async () => {
    if (!asset || !farmId) return;
    setLoading(true); setError('');
    try {
      const response = await api.submitDiagnosis(farmId, { uri: asset.uri, name: asset.fileName ?? `crop-${Date.now()}.jpg`, type: asset.mimeType ?? 'image/jpeg' }, note || undefined);
      setResult(response); await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
    } catch (reason) { setError(reason instanceof ApiError ? reason.message : 'The crop photo could not be uploaded. Try again.'); }
    finally { setLoading(false); }
  };

  if (result) return <Screen><TextButton label="Close" onPress={() => router.back()} /><PageHeader eyebrow="Crop check received" title="Analysis has started" description="You can leave this screen. The result will appear in Ask when ready." /><View style={styles.result}><Pill label={result.status} tone="warning" /><Text style={styles.resultTitle}>What happens next</Text><Text style={styles.body}>AgriShield checks the image and farm context, then returns a likely issue, confidence and practical recommendation. An agronomist can review the result.</Text></View><PrimaryButton label="Return to Ask" onPress={returnToAsk} /></Screen>;

  return (
    <Screen>
      <TextButton label="Cancel" onPress={() => router.back()} />
      <PageHeader eyebrow="Crop health" title="Show us what you see" description="Use a clear, close photo in natural light. Keep the affected leaf, stem or fruit in focus." />
      {asset ? <Image accessibilityLabel="Selected crop photo" source={{ uri: asset.uri }} style={styles.preview} /> : <View style={styles.guide}><View style={styles.frame}><View style={styles.focus} /></View><Text style={styles.guideText}>Fill the frame with one affected crop part</Text></View>}
      <View style={styles.photoActions}><View style={styles.flex}><PrimaryButton label="Take photo" onPress={() => choose(true)} /></View><View style={styles.flex}><PrimaryButton label="Choose photo" onPress={() => choose(false)} tone="secondary" /></View></View>
      <Field label="What have you noticed? (optional)" multiline onChangeText={setNote} placeholder="Example: Yellow spots appeared three days ago…" value={note} />
      {error ? <Text accessibilityRole="alert" style={styles.error}>{error}</Text> : null}
      <PrimaryButton disabled={!asset || !farmId} label="Start crop check" loading={loading} onPress={submit} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  preview: { width: '100%', aspectRatio: 4 / 3, borderRadius: radii.lg, backgroundColor: colors.line },
  guide: { aspectRatio: 4 / 3, borderRadius: radii.lg, backgroundColor: colors.forest, alignItems: 'center', justifyContent: 'center', gap: spacing.md },
  frame: { width: '58%', aspectRatio: 1, borderWidth: 2, borderColor: colors.leafSoft, borderRadius: radii.lg, alignItems: 'center', justifyContent: 'center' }, focus: { width: 18, height: 18, borderRadius: 18, borderWidth: 2, borderColor: colors.millet }, guideText: { fontFamily: typography.body, color: colors.leafSoft, fontSize: 14 },
  photoActions: { flexDirection: 'row', gap: spacing.sm }, flex: { flex: 1 }, error: { color: colors.danger, fontFamily: typography.body, fontSize: 14 },
  result: { backgroundColor: colors.milletSoft, borderRadius: radii.lg, padding: spacing.lg, gap: spacing.md }, resultTitle: { fontFamily: typography.display, fontSize: 24, fontWeight: '700', color: colors.ink }, body: { fontFamily: typography.body, fontSize: 16, lineHeight: 24, color: colors.muted },
});
