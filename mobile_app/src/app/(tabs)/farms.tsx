import { useFocusEffect, router } from 'expo-router';
import { useCallback, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { Card, EmptyState, ErrorState, LoadingState, PageHeader, Pill, Screen } from '@/components/ui';
import { colors, spacing, typography } from '@/constants/theme';
import { api } from '@/lib/api';
import type { Farm } from '@/types/api';

export default function FarmsScreen() {
  const [farms, setFarms] = useState<Farm[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    setLoading(true); setError('');
    try { setFarms(await api.farms({ per_page: 50 })); }
    catch { setError('We could not load your farms.'); }
    finally { setLoading(false); }
  }, []);
  useFocusEffect(useCallback(() => { void load(); }, [load]));

  return (
    <Screen>
      <PageHeader eyebrow="Fields and crop seasons" title="Your farms" description="Open a farm to see local conditions, guidance and crop history." />
      {loading ? <LoadingState /> : error ? <ErrorState message={error} retry={load} /> : farms.length === 0 ? (
        <EmptyState title="No farms registered" message="Your organisation administrator can register a farm and assign it to you." />
      ) : farms.map((farm) => (
        <Pressable accessibilityRole="button" key={farm.id} onPress={() => router.push(`/farms/${farm.id}`)}>
          <Card style={styles.farmCard}>
            <View style={styles.top}><View style={styles.dot} /><Text style={styles.name}>{farm.name}</Text><Pill label={farm.status} tone={farm.status === 'active' ? 'success' : 'neutral'} /></View>
            <Text style={styles.location}>{[farm.locality, farm.state].filter(Boolean).join(', ') || 'Location pending'}</Text>
            <View style={styles.meta}><Meta label="Crop" value={farm.active_crop_cycle?.crop?.name ?? 'Not set'} /><Meta label="Area" value={farm.area?.hectares ? `${farm.area.hectares.toFixed(1)} ha` : 'Pending'} /></View>
          </Card>
        </Pressable>
      ))}
    </Screen>
  );
}

function Meta({ label, value }: { label: string; value: string }) {
  return <View style={styles.metaItem}><Text style={styles.metaLabel}>{label}</Text><Text style={styles.metaValue}>{value}</Text></View>;
}

const styles = StyleSheet.create({
  farmCard: { gap: spacing.sm },
  top: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  dot: { width: 9, height: 9, borderRadius: 9, backgroundColor: colors.millet },
  name: { flex: 1, fontFamily: typography.display, fontSize: 22, fontWeight: '700', color: colors.ink },
  location: { fontFamily: typography.body, color: colors.muted, fontSize: 14 },
  meta: { flexDirection: 'row', borderTopWidth: 1, borderTopColor: colors.line, paddingTop: spacing.sm, gap: spacing.lg },
  metaItem: { flex: 1, gap: 2 },
  metaLabel: { fontFamily: typography.data, color: colors.muted, fontSize: 10, textTransform: 'uppercase' },
  metaValue: { fontFamily: typography.body, color: colors.ink, fontSize: 14, fontWeight: '700' },
});
