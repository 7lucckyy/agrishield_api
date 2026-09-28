import { router, useFocusEffect, useLocalSearchParams } from 'expo-router';
import { useCallback, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';

import { Card, ErrorState, LoadingState, PageHeader, Pill, PrimaryButton, Screen, SectionTitle, TextButton } from '@/components/ui';
import { colors, radii, spacing, typography } from '@/constants/theme';
import { ApiError, api } from '@/lib/api';
import type { Advisory, Farm, WeatherDay } from '@/types/api';

export default function FarmDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const [farm, setFarm] = useState<Farm | null>(null);
  const [weather, setWeather] = useState<WeatherDay[]>([]);
  const [advisories, setAdvisories] = useState<Advisory[]>([]);
  const [soil, setSoil] = useState<{ label: string; value?: number | null; unit?: string | null }[]>([]);
  const [loading, setLoading] = useState(true);
  const [syncing, setSyncing] = useState(false);
  const [syncError, setSyncError] = useState('');
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    setLoading(true); setError('');
    try {
      const [farmData, forecast, advisoryData, soilResponse] = await Promise.all([
        api.farm(id), api.weather(id), api.advisories(id, { per_page: 10 }), api.soilHealth(id) as Promise<{ data: { metrics: typeof soil } }>,
      ]);
      setFarm(farmData); setWeather(forecast); setAdvisories(advisoryData); setSoil(soilResponse.data.metrics ?? []);
    } catch { setError('This farm’s latest information could not be loaded.'); }
    finally { setLoading(false); }
  }, [id]);

  useFocusEffect(useCallback(() => { void load(); }, [load]));

  const sync = async () => {
    setSyncing(true); setSyncError('');
    try {
      await api.syncFarm(id);
      await load();
    } catch (reason) {
      const isProviderPending = reason instanceof ApiError && reason.message.toLowerCase().includes('not registered');
      setSyncError(isProviderPending
        ? 'Farm setup is still finishing. Refresh will be available after satellite registration completes.'
        : 'The farm could not be refreshed. Check your connection and try again.');
    } finally {
      setSyncing(false);
    }
  };

  return (
    <Screen>
      <TextButton label="Back to farms" onPress={() => router.back()} />
      {loading ? <LoadingState /> : error || !farm ? <ErrorState message={error || 'Farm not found.'} retry={load} /> : (
        <>
          <PageHeader eyebrow={[farm.locality, farm.state].filter(Boolean).join(', ')} title={farm.name} description={`${farm.area?.hectares?.toFixed(1) ?? '—'} hectares • ${farm.active_crop_cycle?.crop?.name ?? 'No active crop'}`} />
          <View style={styles.actions}><View style={styles.button}><PrimaryButton label="Check crop photo" onPress={() => router.push({ pathname: '/diagnosis/new', params: { farmId: id } })} /></View><View style={styles.button}><PrimaryButton label="Ask by voice" onPress={() => router.push({ pathname: '/voice/new', params: { farmId: id } })} tone="secondary" /></View></View>
          <SectionTitle title="7-day weather" action={<TextButton label="Refresh farm" onPress={sync} />} />
          {syncing ? <Text style={styles.syncing}>Starting a fresh farm sync…</Text> : null}
          {syncError ? <Text accessibilityRole="alert" style={styles.syncError}>{syncError}</Text> : null}
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.weatherRow}>
            {weather.slice(0, 7).map((day) => <WeatherCard day={day} key={day.forecast_date} />)}
          </ScrollView>
          <SectionTitle title="Soil snapshot" />
          <Card><View style={styles.metrics}>{soil.length ? soil.slice(0, 5).map((metric) => <View key={metric.label} style={styles.metric}><Text style={styles.metricValue}>{metric.value ?? '—'}{metric.unit ? ` ${metric.unit}` : ''}</Text><Text style={styles.metricLabel}>{metric.label}</Text></View>) : <Text style={styles.body}>Soil metrics will appear after the first seasonal observation.</Text>}</View></Card>
          <SectionTitle title="Guidance" />
          {advisories.slice(0, 5).map((item) => <Card key={item.id}><Pill label={item.severity ?? item.type} tone={item.severity === 'critical' ? 'danger' : item.severity === 'warning' ? 'warning' : 'success'} /><Text style={styles.cardTitle}>{item.title}</Text><Text style={styles.body}>{item.summary}</Text></Card>)}
        </>
      )}
    </Screen>
  );
}

function WeatherCard({ day }: { day: WeatherDay }) {
  const date = new Date(`${day.forecast_date}T12:00:00`);
  return <View style={styles.weather}><Text style={styles.weatherDay}>{date.toLocaleDateString('en-NG', { weekday: 'short' })}</Text><Text style={styles.weatherTemp}>{Math.round(day.temperature_max ?? 0)}°</Text><Text style={styles.weatherRain}>{Math.round(day.rainfall_probability ?? 0)}% rain</Text></View>;
}

const styles = StyleSheet.create({
  actions: { flexDirection: 'row', gap: spacing.sm }, button: { flex: 1 },
  syncing: { fontFamily: typography.body, color: colors.leaf, fontSize: 13 },
  syncError: { fontFamily: typography.body, color: colors.danger, fontSize: 14, lineHeight: 20 },
  weatherRow: { gap: spacing.sm, paddingVertical: spacing.xs },
  weather: { width: 104, minHeight: 122, borderRadius: radii.md, backgroundColor: colors.sky, padding: spacing.md, justifyContent: 'space-between' },
  weatherDay: { fontFamily: typography.data, fontSize: 11, color: colors.forest, textTransform: 'uppercase', fontWeight: '700' },
  weatherTemp: { fontFamily: typography.display, fontSize: 30, color: colors.ink, fontWeight: '700' },
  weatherRain: { fontFamily: typography.body, fontSize: 12, color: colors.muted },
  metrics: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.md },
  metric: { width: '45%', gap: 2 }, metricValue: { fontFamily: typography.data, fontSize: 17, fontWeight: '700', color: colors.ink }, metricLabel: { fontFamily: typography.body, fontSize: 12, color: colors.muted },
  cardTitle: { fontFamily: typography.body, color: colors.ink, fontSize: 17, fontWeight: '700' }, body: { fontFamily: typography.body, color: colors.muted, fontSize: 15, lineHeight: 22 },
});
