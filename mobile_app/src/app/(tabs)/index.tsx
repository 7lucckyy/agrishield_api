import { useFocusEffect, router } from 'expo-router';
import { useCallback, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { FarmStatusStrip } from '@/components/farm-status-strip';
import { Card, EmptyState, ErrorState, LoadingState, PageHeader, Pill, PrimaryButton, Screen, SectionTitle } from '@/components/ui';
import { colors, radii, spacing, typography } from '@/constants/theme';
import { useAuth } from '@/context/auth-context';
import { api } from '@/lib/api';
import type { Advisory, Farm, WeatherDay } from '@/types/api';

export default function HomeScreen() {
  const { user, activeOrganization } = useAuth();
  const [farm, setFarm] = useState<Farm | null>(null);
  const [weather, setWeather] = useState<WeatherDay>();
  const [advisories, setAdvisories] = useState<Advisory[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    setLoading(true); setError('');
    try {
      const farms = await api.farms({ 'filter[status]': 'active', per_page: 20 });
      const firstFarm = farms[0] ?? null;
      setFarm(firstFarm);
      if (firstFarm) {
        const [forecast, farmAdvisories] = await Promise.all([api.weather(firstFarm.id), api.advisories(firstFarm.id, { per_page: 5 })]);
        setWeather(forecast[0]); setAdvisories(farmAdvisories);
      }
    } catch { setError('Your latest farm information could not be reached. Check the connection and try again.'); }
    finally { setLoading(false); }
  }, []);

  useFocusEffect(useCallback(() => { void load(); }, [load]));

  return (
    <Screen>
      <PageHeader eyebrow={activeOrganization?.name ?? 'My farms'} title={`Good day, ${user?.name.split(' ')[0] ?? 'farmer'}`} description="Here is what needs your attention." />
      {loading ? <LoadingState /> : error ? <ErrorState message={error} retry={load} /> : !farm ? (
        <EmptyState action={<PrimaryButton label="Add my farm" onPress={() => router.push('/farms/new')} />} title="Add your first crop farm" message="Use your location to register a field and receive local crop guidance." />
      ) : (
        <>
          <Pressable accessibilityRole="button" onPress={() => router.push(`/farms/${farm.id}`)}><FarmStatusStrip advisory={advisories[0]} farm={farm} weather={weather} /></Pressable>
          <SectionTitle title="Do this next" />
          <View style={styles.actions}>
            <ActionCard accent={colors.milletSoft} kicker="Crop health" label="Check a crop photo" onPress={() => router.push({ pathname: '/diagnosis/new', params: { farmId: farm.id } })} />
            <ActionCard accent={colors.sky} kicker="Voice guidance" label="Ask in your language" onPress={() => router.push({ pathname: '/voice/new', params: { farmId: farm.id } })} />
          </View>
          <SectionTitle title="Current guidance" />
          {advisories.length ? advisories.slice(0, 3).map((item) => (
            <Card key={item.id}>
              <View style={styles.advisoryTop}><Pill label={item.severity ?? item.type} tone={item.severity === 'critical' ? 'danger' : item.severity === 'warning' ? 'warning' : 'success'} /><Text style={styles.source}>{item.source ?? 'AgriShield'}</Text></View>
              <Text style={styles.cardTitle}>{item.title}</Text><Text style={styles.body}>{item.summary}</Text>
            </Card>
          )) : <EmptyState title="No urgent guidance" message="New crop and weather advice will appear here after your farm syncs." />}
        </>
      )}
      <Pressable accessibilityRole="button" onPress={() => router.push('/finance')} style={({ pressed }) => [styles.accessCard, pressed && styles.pressed]}>
        <View style={styles.accessMark} />
        <View style={styles.accessCopy}>
          <Text style={styles.accessKicker}>ASSET ACCESS</Text>
          <Text style={styles.accessTitle}>Equipment and farm inputs</Text>
          <Text style={styles.accessBody}>Explore available programmes and apply with your own farm.</Text>
        </View>
        <Text style={styles.accessArrow}>›</Text>
      </Pressable>
    </Screen>
  );
}

function ActionCard({ accent, kicker, label, onPress }: { accent: string; kicker: string; label: string; onPress: () => void }) {
  return <Pressable accessibilityRole="button" onPress={onPress} style={({ pressed }) => [styles.action, { backgroundColor: accent }, pressed && styles.pressed]}><View style={styles.actionMark} /><Text style={styles.actionKicker}>{kicker}</Text><Text style={styles.actionLabel}>{label}</Text></Pressable>;
}

const styles = StyleSheet.create({
  actions: { flexDirection: 'row', gap: spacing.sm },
  action: { flex: 1, minHeight: 150, borderRadius: radii.md, padding: spacing.md, justifyContent: 'flex-end', gap: spacing.xs },
  actionMark: { width: 30, height: 5, borderRadius: 6, backgroundColor: colors.forest, marginBottom: 'auto' },
  actionKicker: { fontFamily: typography.data, fontSize: 11, fontWeight: '700', textTransform: 'uppercase', color: colors.forest },
  actionLabel: { fontFamily: typography.display, fontSize: 20, lineHeight: 24, fontWeight: '700', color: colors.ink },
  pressed: { opacity: 0.82, transform: [{ scale: 0.98 }] },
  advisoryTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: spacing.sm },
  source: { fontFamily: typography.body, color: colors.muted, fontSize: 12 },
  cardTitle: { fontFamily: typography.body, color: colors.ink, fontSize: 17, fontWeight: '700' },
  body: { fontFamily: typography.body, color: colors.muted, fontSize: 15, lineHeight: 22 },
  accessCard: { minHeight: 112, borderRadius: radii.md, backgroundColor: colors.forest, padding: spacing.md, flexDirection: 'row', alignItems: 'center', gap: spacing.md },
  accessMark: { width: 44, height: 44, borderRadius: 14, backgroundColor: colors.millet, borderWidth: 8, borderColor: colors.milletSoft },
  accessCopy: { flex: 1, gap: 2 },
  accessKicker: { fontFamily: typography.data, fontSize: 10, fontWeight: '700', letterSpacing: 1, color: colors.millet },
  accessTitle: { fontFamily: typography.display, fontSize: 19, fontWeight: '700', color: colors.paper },
  accessBody: { fontFamily: typography.body, fontSize: 12, lineHeight: 17, color: colors.leafSoft },
  accessArrow: { fontFamily: typography.display, fontSize: 34, color: colors.paper },
});
