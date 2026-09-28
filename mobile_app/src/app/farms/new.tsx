import * as Haptics from 'expo-haptics';
import * as Location from 'expo-location';
import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { Card, Field, PageHeader, PrimaryButton, Screen, TextButton } from '@/components/ui';
import { colors, radii, spacing, typography } from '@/constants/theme';
import { ApiError, api } from '@/lib/api';

const FARM_SIZES = [
  { label: 'Small', detail: 'About 1 hectare', hectares: 1 },
  { label: 'Medium', detail: 'About 3 hectares', hectares: 3 },
  { label: 'Large', detail: 'About 5 hectares', hectares: 5 },
];

export default function NewFarmScreen() {
  const [name, setName] = useState('');
  const [locality, setLocality] = useState('');
  const [state, setState] = useState('');
  const [hectares, setHectares] = useState(1);
  const [coordinates, setCoordinates] = useState<Location.LocationObjectCoords | null>(null);
  const [locating, setLocating] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');

  const captureLocation = async () => {
    setLocating(true);
    setError('');
    try {
      const permission = await Location.requestForegroundPermissionsAsync();
      if (permission.status !== Location.PermissionStatus.GRANTED) {
        setError('Allow location access so AgriShield can place your farm on the map.');
        return;
      }

      const location = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.High });
      setCoordinates(location.coords);
      await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
    } catch {
      setError('We could not get your location. Move outside and try again.');
    } finally {
      setLocating(false);
    }
  };

  const submit = async () => {
    if (!coordinates) return;
    setSubmitting(true);
    setError('');
    try {
      const farm = await api.createFarm({
        name,
        locality,
        state,
        country: 'NG',
        boundary_geojson: squareBoundary(coordinates.latitude, coordinates.longitude, hectares),
      });
      await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
      router.replace(`/farms/${farm.data.id}`);
    } catch (reason) {
      setError(reason instanceof ApiError ? Object.values(reason.errors)[0]?.[0] ?? reason.message : 'Your farm could not be saved. Try again.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <Screen>
      <TextButton label="Do this later" onPress={() => router.replace('/(tabs)')} />
      <PageHeader eyebrow="Your first farm" title="Stand on the farm and tap once" description="We use your location and farm size to create a starting boundary. You can improve it later." />
      <Card style={styles.locationCard}>
        <View style={[styles.locationMark, coordinates && styles.locationMarkReady]} />
        <View style={styles.locationCopy}>
          <Text style={styles.locationTitle}>{coordinates ? 'Farm location captured' : 'Capture farm location'}</Text>
          <Text style={styles.locationText}>{coordinates ? 'Your position is ready. Continue below.' : 'For the best result, stand near the middle of the farm.'}</Text>
        </View>
        <PrimaryButton label={coordinates ? 'Update location' : 'Use my location'} loading={locating} onPress={captureLocation} tone="secondary" />
      </Card>

      <Field label="Farm name" onChangeText={setName} placeholder="Example: Home maize farm" value={name} />
      <Field label="Village or community" onChangeText={setLocality} placeholder="Example: Dawakin Kudu" value={locality} />
      <Field label="State" onChangeText={setState} placeholder="Example: Kano" value={state} />

      <Text style={styles.label}>Farm size</Text>
      <View style={styles.sizeOptions}>
        {FARM_SIZES.map((size) => {
          const active = hectares === size.hectares;
          return (
            <Pressable accessibilityRole="button" accessibilityState={{ selected: active }} key={size.hectares} onPress={() => setHectares(size.hectares)} style={[styles.size, active && styles.sizeActive]}>
              <Text style={[styles.sizeLabel, active && styles.sizeLabelActive]}>{size.label}</Text>
              <Text style={[styles.sizeDetail, active && styles.sizeDetailActive]}>{size.detail}</Text>
            </Pressable>
          );
        })}
      </View>

      {error ? <Text accessibilityRole="alert" style={styles.error}>{error}</Text> : null}
      <PrimaryButton disabled={!coordinates || name.trim().length < 2 || !locality || !state} label="Save my farm" loading={submitting} onPress={submit} />
      <Text style={styles.privacy}>Your precise farm location is private and is only used to provide farm services.</Text>
    </Screen>
  );
}

function squareBoundary(latitude: number, longitude: number, hectares: number) {
  const halfSideMeters = Math.sqrt(hectares * 10_000) / 2;
  const latitudeOffset = halfSideMeters / 111_320;
  const longitudeOffset = halfSideMeters / (111_320 * Math.cos(latitude * Math.PI / 180));
  const west = longitude - longitudeOffset;
  const east = longitude + longitudeOffset;
  const south = latitude - latitudeOffset;
  const north = latitude + latitudeOffset;

  return {
    type: 'Polygon',
    coordinates: [[
      [west, south],
      [east, south],
      [east, north],
      [west, north],
      [west, south],
    ]],
  };
}

const styles = StyleSheet.create({
  locationCard: { backgroundColor: colors.sky, borderColor: '#BCD9E3' },
  locationMark: { width: 48, height: 48, borderRadius: 24, borderWidth: 9, borderColor: colors.paper, backgroundColor: colors.muted, alignSelf: 'center' },
  locationMarkReady: { backgroundColor: colors.leaf },
  locationCopy: { alignItems: 'center', gap: spacing.xs },
  locationTitle: { fontFamily: typography.display, fontSize: 21, fontWeight: '700', color: colors.ink, textAlign: 'center' },
  locationText: { fontFamily: typography.body, fontSize: 14, lineHeight: 20, color: colors.muted, textAlign: 'center' },
  label: { fontFamily: typography.body, fontSize: 14, fontWeight: '700', color: colors.ink },
  sizeOptions: { gap: spacing.sm },
  size: { minHeight: 62, borderRadius: radii.md, borderWidth: 1, borderColor: colors.line, backgroundColor: colors.paper, paddingHorizontal: spacing.md, justifyContent: 'center' },
  sizeActive: { backgroundColor: colors.forest, borderColor: colors.forest },
  sizeLabel: { fontFamily: typography.body, fontSize: 16, fontWeight: '700', color: colors.ink },
  sizeLabelActive: { color: colors.paper },
  sizeDetail: { fontFamily: typography.body, fontSize: 13, color: colors.muted },
  sizeDetailActive: { color: colors.leafSoft },
  error: { color: colors.danger, fontFamily: typography.body, fontSize: 14, lineHeight: 20 },
  privacy: { color: colors.muted, fontFamily: typography.body, fontSize: 12, lineHeight: 18, textAlign: 'center' },
});
