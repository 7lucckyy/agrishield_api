import { StyleSheet, Text, View } from 'react-native';

import { colors, radii, spacing, typography } from '@/constants/theme';
import type { Advisory, Farm, WeatherDay } from '@/types/api';

export function FarmStatusStrip({ farm, weather, advisory }: { farm: Farm; weather?: WeatherDay; advisory?: Advisory }) {
  const crop = farm.active_crop_cycle?.crop?.name ?? 'No active crop';
  const rain = weather?.rainfall_probability == null ? 'Weather syncing' : `${Math.round(weather.rainfall_probability)}% rain`;

  return (
    <View accessibilityLabel={`Farm status. ${crop}. ${rain}. ${advisory?.title ?? 'No urgent advisory'}`} style={styles.wrap}>
      <View style={styles.topRow}>
        <View style={styles.marker} />
        <Text numberOfLines={1} style={styles.name}>{farm.name}</Text>
        <Text style={styles.area}>{farm.area?.hectares ? `${farm.area.hectares.toFixed(1)} ha` : 'Area pending'}</Text>
      </View>
      <View style={styles.rows}>
        <StatusRow label="Crop" value={crop} accent={colors.leafSoft} />
        <StatusRow label="Today" value={rain} accent={colors.sky} />
        <StatusRow label="Watch" value={advisory?.title ?? 'No urgent alerts'} accent={advisory ? colors.milletSoft : colors.canvas} />
      </View>
    </View>
  );
}

function StatusRow({ label, value, accent }: { label: string; value: string; accent: string }) {
  return <View style={[styles.row, { backgroundColor: accent }]}><Text style={styles.label}>{label}</Text><Text numberOfLines={1} style={styles.value}>{value}</Text></View>;
}

const styles = StyleSheet.create({
  wrap: { backgroundColor: colors.forest, borderRadius: radii.lg, padding: spacing.md, gap: spacing.md, overflow: 'hidden' },
  topRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  marker: { width: 9, height: 9, borderRadius: 9, backgroundColor: colors.millet },
  name: { flex: 1, fontFamily: typography.display, fontSize: 24, fontWeight: '700', color: colors.paper },
  area: { fontFamily: typography.data, fontSize: 12, color: colors.leafSoft },
  rows: { gap: 6 },
  row: { minHeight: 42, borderRadius: radii.sm, paddingHorizontal: 12, flexDirection: 'row', alignItems: 'center', gap: spacing.md },
  label: { width: 48, fontFamily: typography.data, fontSize: 11, fontWeight: '700', color: colors.forest, textTransform: 'uppercase' },
  value: { flex: 1, fontFamily: typography.body, fontSize: 14, fontWeight: '600', color: colors.ink },
});
