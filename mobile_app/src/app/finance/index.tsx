import { router, useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';

import { Card, EmptyState, ErrorState, LoadingState, PageHeader, Pill, PrimaryButton, Screen, SectionTitle, TextButton } from '@/components/ui';
import { colors, radii, spacing, typography } from '@/constants/theme';
import { useAuth } from '@/context/auth-context';
import { api } from '@/lib/api';
import type { FinanceApplication, FinanceProduct } from '@/types/api';

export default function FinanceScreen() {
  const { activeOrganization } = useAuth();
  const [products, setProducts] = useState<FinanceProduct[]>([]);
  const [applications, setApplications] = useState<FinanceApplication[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    if (!activeOrganization) { setLoading(false); return; }
    setLoading(true); setError('');
    try { const [p, a] = await Promise.all([api.financeProducts(activeOrganization.id), api.financeApplications(activeOrganization.id)]); setProducts(p); setApplications(a); }
    catch { setError('Asset access information could not be loaded.'); }
    finally { setLoading(false); }
  }, [activeOrganization]);
  useFocusEffect(useCallback(() => { void load(); }, [load]));

  return (
    <Screen>
      <TextButton label="Back" onPress={() => router.back()} />
      <PageHeader eyebrow="Productive crop assets" title="Asset access" description="Explore equipment options prepared for registered crop farms." />
      <View style={styles.notice}><Text style={styles.noticeTitle}>Important</Text><Text style={styles.noticeText}>These are proposed partner programmes. AgriShield does not approve or issue finance. Each bank independently decides eligibility, terms and disbursement.</Text></View>
      {loading ? <LoadingState /> : error ? <ErrorState message={error} retry={load} /> : !activeOrganization ? <EmptyState title="Choose an organisation" message="Select your organisation from More before opening Asset access." /> : (
        <>
          <SectionTitle title="Available equipment" />
          {products.map((product) => <Card key={product.id}><View style={styles.top}><Pill label="Proposed partner" tone="warning" /><Text style={styles.partner}>{product.partner.name}</Text></View><Text style={styles.title}>{product.name}</Text><Text style={styles.body}>{product.eligibility_summary ?? `${product.category.replaceAll('_', ' ')} for productive crop use.`}</Text><Text style={styles.range}>{formatCurrency(product.minimum_amount, product.currency)} – {formatCurrency(product.maximum_amount, product.currency)}</Text><PrimaryButton label="Review and apply" onPress={() => router.push({ pathname: '/finance/apply', params: { productId: product.id } })} tone="secondary" /></Card>)}
          <SectionTitle title="Your applications" />
          {applications.length ? applications.map((application) => <Card key={application.id}><View style={styles.top}><Pill label={application.status} tone={application.status === 'approved' || application.status === 'delivered' ? 'success' : application.status === 'declined' ? 'danger' : 'warning'} /><Text style={styles.partner}>{application.farm.name}</Text></View><Text style={styles.title}>{application.product.name}</Text><Text style={styles.body}>{application.purpose}</Text></Card>) : <EmptyState title="No applications yet" message="Choose equipment above when your farm is ready." />}
        </>
      )}
    </Screen>
  );
}

const formatCurrency = (value?: number | null, currency = 'NGN') => value == null ? 'Flexible' : new Intl.NumberFormat('en-NG', { style: 'currency', currency, maximumFractionDigits: 0 }).format(value);
const styles = StyleSheet.create({
  notice: { borderRadius: radii.md, backgroundColor: colors.milletSoft, padding: spacing.md, gap: spacing.xs }, noticeTitle: { fontFamily: typography.body, fontSize: 14, fontWeight: '800', color: colors.warning }, noticeText: { fontFamily: typography.body, fontSize: 13, lineHeight: 19, color: colors.warning },
  top: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.sm }, partner: { flex: 1, textAlign: 'right', fontFamily: typography.body, fontSize: 12, color: colors.muted }, title: { fontFamily: typography.display, fontSize: 21, fontWeight: '700', color: colors.ink }, body: { fontFamily: typography.body, fontSize: 14, lineHeight: 21, color: colors.muted }, range: { fontFamily: typography.data, fontSize: 13, fontWeight: '700', color: colors.forest },
});
