import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { Card, PageHeader, Pill, PrimaryButton, Screen, SectionTitle } from '@/components/ui';
import { colors, spacing, typography } from '@/constants/theme';
import { useAuth } from '@/context/auth-context';
import { API_BASE_URL } from '@/lib/api';

export default function MoreScreen() {
  const { user, organizations, activeOrganization, selectOrganization, signOut } = useAuth();
  const [leaving, setLeaving] = useState(false);

  const logout = async () => { setLeaving(true); await signOut(); router.replace('/(auth)/sign-in'); };

  return (
    <Screen>
      <PageHeader eyebrow="Account and services" title={user?.name ?? 'Your account'} description={user?.email ?? user?.phone ?? ''} />
      <SectionTitle title="Farmer groups" />
      {organizations.length ? organizations.map((organization) => {
        const active = activeOrganization?.id === organization.id;
        return <Pressable accessibilityRole="button" accessibilityState={{ selected: active }} key={organization.id} onPress={() => selectOrganization(organization)}><Card style={active ? styles.activeCard : undefined}><View style={styles.row}><View style={[styles.orgMark, active && styles.orgMarkActive]} /><View style={styles.grow}><Text style={styles.itemTitle}>{organization.name}</Text><Text style={styles.itemMeta}>{organization.role?.replaceAll('_', ' ') ?? 'Member'}</Text></View>{active ? <Pill label="Active" tone="success" /> : null}</View></Card></Pressable>;
      }) : <Card><Text style={styles.body}>You are using AgriShield independently. You can still use every farmer service.</Text></Card>}
      <SectionTitle title="Services" />
      <Pressable accessibilityRole="button" onPress={() => router.push('/finance')}><Card><View style={styles.row}><View style={styles.serviceMark} /><View style={styles.grow}><Text style={styles.itemTitle}>Asset access</Text><Text style={styles.itemMeta}>Equipment, inputs and finance programmes</Text></View><Text style={styles.arrow}>›</Text></View></Card></Pressable>
      <SectionTitle title="App information" />
      <Card><Info label="Language" value={user?.locale?.toUpperCase() ?? 'EN'} /><Info label="API" value={API_BASE_URL.replace(/^https?:\/\//, '')} /><Info label="Security" value="Token stored in device keychain" /></Card>
      <Text style={styles.attribution}>N-ATLaS is an initiative of Nigeria&apos;s Federal Ministry of Communications, Innovation &amp; Digital Economy, powered by Awarri Technologies.</Text>
      <PrimaryButton label="Sign out" loading={leaving} onPress={logout} tone="secondary" />
    </Screen>
  );
}

function Info({ label, value }: { label: string; value: string }) { return <View style={styles.info}><Text style={styles.infoLabel}>{label}</Text><Text numberOfLines={1} style={styles.infoValue}>{value}</Text></View>; }
const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center', gap: spacing.md }, grow: { flex: 1, gap: 2 }, activeCard: { borderColor: colors.leaf, backgroundColor: '#FAFCFA' }, orgMark: { width: 12, height: 40, borderRadius: 8, backgroundColor: colors.line }, orgMarkActive: { backgroundColor: colors.leaf }, serviceMark: { width: 40, height: 40, borderRadius: 12, backgroundColor: colors.milletSoft, borderWidth: 6, borderColor: colors.millet },
  itemTitle: { fontFamily: typography.body, fontSize: 16, fontWeight: '700', color: colors.ink }, itemMeta: { fontFamily: typography.body, fontSize: 13, color: colors.muted, textTransform: 'capitalize' }, arrow: { fontFamily: typography.display, fontSize: 28, color: colors.leaf }, body: { fontFamily: typography.body, color: colors.muted, fontSize: 15 },
  info: { flexDirection: 'row', minHeight: 38, alignItems: 'center', borderBottomWidth: 1, borderBottomColor: colors.line, gap: spacing.md }, infoLabel: { width: 86, fontFamily: typography.body, fontWeight: '700', color: colors.ink, fontSize: 13 }, infoValue: { flex: 1, fontFamily: typography.data, color: colors.muted, fontSize: 11, textAlign: 'right' },
  attribution: { fontFamily: typography.body, color: colors.muted, fontSize: 11, lineHeight: 17, textAlign: 'center', paddingHorizontal: spacing.lg },
});
