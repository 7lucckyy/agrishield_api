import * as Haptics from 'expo-haptics';
import { router, useFocusEffect, useLocalSearchParams } from 'expo-router';
import { useCallback, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { Card, Field, LoadingState, PageHeader, Pill, PrimaryButton, Screen, TextButton } from '@/components/ui';
import { colors, radii, spacing, typography } from '@/constants/theme';
import { useAuth } from '@/context/auth-context';
import { ApiError, api } from '@/lib/api';
import type { Farm, FinanceProduct } from '@/types/api';

export default function FinanceApplyScreen() {
  const { productId } = useLocalSearchParams<{ productId: string }>();
  const { activeOrganization } = useAuth();
  const [products, setProducts] = useState<FinanceProduct[]>([]);
  const [farms, setFarms] = useState<Farm[]>([]);
  const [selectedFarm, setSelectedFarm] = useState<string>('');
  const [amount, setAmount] = useState('');
  const [quantity, setQuantity] = useState('1');
  const [purpose, setPurpose] = useState('');
  const [consent, setConsent] = useState(false);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [complete, setComplete] = useState(false);

  useFocusEffect(useCallback(() => {
    void (async () => {
      if (!activeOrganization) { setLoading(false); return; }
      try {
        const [productData, farmData] = await Promise.all([api.financeProducts(activeOrganization.id), api.farms({ 'filter[organization_id]': activeOrganization.id, per_page: 50 })]);
        setProducts(productData); setFarms(farmData); setSelectedFarm(farmData[0]?.id ?? '');
      } finally { setLoading(false); }
    })();
  }, [activeOrganization]));

  const product = products.find((item) => item.id === Number(productId));
  const submit = async () => {
    if (!activeOrganization || !product) return;
    setSubmitting(true); setError('');
    try {
      await api.createFinanceApplication(activeOrganization.id, { farm_id: selectedFarm, asset_finance_product_id: product.id, quantity: Number(quantity), requested_amount: Number(amount), purpose, consent: true });
      setComplete(true); await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
    } catch (reason) {
      if (reason instanceof ApiError) setError(Object.values(reason.errors)[0]?.[0] ?? reason.message); else setError('The application could not be saved.');
    } finally { setSubmitting(false); }
  };

  if (loading) return <Screen><LoadingState /></Screen>;
  if (complete) return <Screen><TextButton label="Close" onPress={() => router.back()} /><PageHeader eyebrow="Application recorded" title="Your organisation can track it now" description="The proposed finance partner still performs its own assessment and makes every financing decision." /><PrimaryButton label="Return to Asset access" onPress={() => router.back()} /></Screen>;

  return (
    <Screen>
      <TextButton label="Cancel" onPress={() => router.back()} />
      <PageHeader eyebrow={product?.partner.name ?? 'Asset access'} title={product?.name ?? 'Apply for equipment'} description="Complete the productive-use details. Submission does not guarantee approval." />
      {product ? <Card><View style={styles.productTop}><Pill label="Proposed partner" tone="warning" /><Text style={styles.structure}>{product.financing_structure}</Text></View><Text style={styles.body}>{product.eligibility_summary}</Text></Card> : null}
      <Text style={styles.label}>Farm</Text><View style={styles.options}>{farms.map((farm) => <Pressable accessibilityRole="button" accessibilityState={{ selected: selectedFarm === farm.id }} key={farm.id} onPress={() => setSelectedFarm(farm.id)} style={[styles.option, selectedFarm === farm.id && styles.optionActive]}><Text style={[styles.optionText, selectedFarm === farm.id && styles.optionTextActive]}>{farm.name}</Text></Pressable>)}</View>
      <View style={styles.row}><View style={styles.grow}><Field keyboardType="number-pad" label="Quantity" onChangeText={setQuantity} value={quantity} /></View><View style={styles.grow}><Field keyboardType="decimal-pad" label="Requested amount (NGN)" onChangeText={setAmount} placeholder="500000" value={amount} /></View></View>
      <Field label="How will this asset improve crop production?" multiline onChangeText={setPurpose} placeholder="Explain the crop, season and intended productive use…" value={purpose} />
      <Pressable accessibilityRole="checkbox" accessibilityState={{ checked: consent }} onPress={() => setConsent(!consent)} style={styles.consent}><View style={[styles.checkbox, consent && styles.checkboxChecked]}>{consent ? <View style={styles.check} /> : null}</View><Text style={styles.consentText}>I have the farmer’s permission to share this application and farm context with the named proposed finance partner for assessment.</Text></Pressable>
      {error ? <Text accessibilityRole="alert" style={styles.error}>{error}</Text> : null}
      <PrimaryButton disabled={!product || !selectedFarm || !amount || purpose.length < 20 || !consent} label="Record application" loading={submitting} onPress={submit} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  productTop: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.sm }, structure: { flex: 1, textAlign: 'right', fontFamily: typography.body, color: colors.muted, fontSize: 12 }, body: { fontFamily: typography.body, color: colors.muted, fontSize: 14, lineHeight: 21 },
  label: { fontFamily: typography.body, fontSize: 14, fontWeight: '700', color: colors.ink }, options: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }, option: { minHeight: 44, justifyContent: 'center', paddingHorizontal: 14, borderWidth: 1, borderColor: colors.line, borderRadius: radii.pill, backgroundColor: colors.paper }, optionActive: { backgroundColor: colors.forest, borderColor: colors.forest }, optionText: { fontFamily: typography.body, fontSize: 14, color: colors.ink, fontWeight: '600' }, optionTextActive: { color: colors.paper },
  row: { flexDirection: 'row', gap: spacing.sm }, grow: { flex: 1 }, consent: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md, backgroundColor: colors.milletSoft, borderRadius: radii.md, padding: spacing.md, minHeight: 72 }, checkbox: { width: 24, height: 24, borderRadius: 6, borderWidth: 2, borderColor: colors.warning, alignItems: 'center', justifyContent: 'center' }, checkboxChecked: { backgroundColor: colors.forest, borderColor: colors.forest }, check: { width: 10, height: 6, borderLeftWidth: 2, borderBottomWidth: 2, borderColor: colors.paper, transform: [{ rotate: '-45deg' }, { translateY: -1 }] }, consentText: { flex: 1, fontFamily: typography.body, fontSize: 13, lineHeight: 19, color: colors.warning }, error: { color: colors.danger, fontFamily: typography.body, fontSize: 14 },
});
