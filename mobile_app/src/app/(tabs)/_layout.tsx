import { Redirect, Tabs } from 'expo-router';
import { SymbolView, type SymbolViewProps } from 'expo-symbols';
import type { ColorValue } from 'react-native';

import { colors, typography } from '@/constants/theme';
import { useAuth } from '@/context/auth-context';

function TabIcon({ name, color }: { name: SymbolViewProps['name']; color: ColorValue }) {
  return <SymbolView name={name} size={22} tintColor={color} />;
}

export default function TabsLayout() {
  const { token } = useAuth();
  if (!token) return <Redirect href="/(auth)/sign-in" />;

  return (
    <Tabs screenOptions={{
      headerShown: false,
      tabBarActiveTintColor: colors.forest,
      tabBarInactiveTintColor: colors.muted,
      tabBarLabelStyle: { fontFamily: typography.body, fontSize: 12, fontWeight: '700' },
      tabBarStyle: { height: 76, paddingTop: 8, paddingBottom: 10, borderTopColor: colors.line, backgroundColor: colors.paper },
      tabBarHideOnKeyboard: true,
    }}>
      <Tabs.Screen name="index" options={{ title: 'Today', tabBarIcon: ({ color }) => <TabIcon color={color} name="house.fill" /> }} />
      <Tabs.Screen name="farms" options={{ title: 'Farms', tabBarIcon: ({ color }) => <TabIcon color={color} name="leaf.fill" /> }} />
      <Tabs.Screen name="assistant" options={{ title: 'Ask', tabBarIcon: ({ color }) => <TabIcon color={color} name="waveform" /> }} />
      <Tabs.Screen name="more" options={{ title: 'More', tabBarIcon: ({ color }) => <TabIcon color={color} name="ellipsis.circle.fill" /> }} />
    </Tabs>
  );
}
