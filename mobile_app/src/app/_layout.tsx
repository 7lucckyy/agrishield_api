import NetInfo from '@react-native-community/netinfo';
import { Stack } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';
import { StatusBar } from 'expo-status-bar';
import { useEffect, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { SafeAreaProvider } from 'react-native-safe-area-context';

import { colors, typography } from '@/constants/theme';
import { AuthProvider, useAuth } from '@/context/auth-context';
import { API_BASE_URL } from '@/lib/api';

SplashScreen.preventAutoHideAsync();

function Navigation() {
  const { isReady } = useAuth();
  const [offline, setOffline] = useState(false);

  useEffect(() => {
    let isActive = true;

    const unsubscribe = NetInfo.addEventListener(async (state) => {
      if (state.isConnected !== false) {
        setOffline(false);
        return;
      }

      try {
        const response = await fetch(`${API_BASE_URL}/health`, {
          headers: { Accept: 'application/json' },
        });

        if (isActive) {
          setOffline(!response.ok);
        }
      } catch {
        if (isActive) {
          setOffline(true);
        }
      }
    });

    return () => {
      isActive = false;
      unsubscribe();
    };
  }, []);
  useEffect(() => { if (isReady) void SplashScreen.hideAsync(); }, [isReady]);

  if (!isReady) return null;

  return (
    <View style={styles.root}>
      {offline ? <View accessibilityRole="alert" style={styles.offline}><Text style={styles.offlineText}>Offline — showing what is already on this device</Text></View> : null}
      <Stack screenOptions={{ headerShown: false, contentStyle: { backgroundColor: colors.canvas }, animation: 'slide_from_right' }}>
        <Stack.Screen name="index" />
        <Stack.Screen name="(auth)" />
        <Stack.Screen name="(tabs)" />
        <Stack.Screen name="farms/[id]" />
        <Stack.Screen name="diagnosis/new" options={{ presentation: 'modal', animation: 'slide_from_bottom' }} />
        <Stack.Screen name="voice/new" options={{ presentation: 'modal', animation: 'slide_from_bottom' }} />
        <Stack.Screen name="finance/index" />
        <Stack.Screen name="finance/apply" options={{ presentation: 'modal', animation: 'slide_from_bottom' }} />
      </Stack>
      <StatusBar style="dark" />
    </View>
  );
}

export default function RootLayout() {
  return <SafeAreaProvider><AuthProvider><Navigation /></AuthProvider></SafeAreaProvider>;
}

const styles = StyleSheet.create({
  root: { flex: 1 },
  offline: { backgroundColor: colors.milletSoft, minHeight: 30, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 12 },
  offlineText: { color: colors.warning, fontFamily: typography.body, fontSize: 12, fontWeight: '700' },
});
