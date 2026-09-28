import { router } from 'expo-router';
import { useState } from 'react';
import { Image, KeyboardAvoidingView, Platform, StyleSheet, Text, View } from 'react-native';

import { Field, PrimaryButton, Screen, TextButton } from '@/components/ui';
import { colors, radii, spacing, typography } from '@/constants/theme';
import { useAuth } from '@/context/auth-context';
import { ApiError } from '@/lib/api';

export default function SignInScreen() {
  const { signIn } = useAuth();
  const [identifier, setIdentifier] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const submit = async () => {
    setLoading(true); setError('');
    try {
      await signIn(identifier, password);
      router.replace('/(tabs)');
    } catch (reason) {
      setError(reason instanceof ApiError ? reason.message : 'Check your connection and try again.');
    } finally { setLoading(false); }
  };

  return (
    <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={styles.flex}>
      <Screen>
        <View style={styles.brand}>
          <Image source={require('../../../assets/images/agrishield-icon.png')} style={styles.logo} />
          <View style={styles.brandCopy}><Text style={styles.wordmark}>AgriShield AI</Text><Text style={styles.tagline}>Clear guidance for every crop season.</Text></View>
        </View>
        <View style={styles.hero}>
          <Text style={styles.kicker}>FIELD COMPANION</Text>
          <Text accessibilityRole="header" style={styles.title}>Know what your farm needs next.</Text>
          <Text style={styles.description}>Farm records, crop checks, voice guidance and access to productive assets—in one simple place.</Text>
          <View style={styles.rows}><View style={styles.row} /><View style={[styles.row, styles.rowShort]} /><View style={[styles.row, styles.rowWarm]} /></View>
        </View>
        <View style={styles.form}>
          <Field autoComplete="tel" keyboardType="phone-pad" label="Phone number" onChangeText={setIdentifier} placeholder="+234 801 234 5678" textContentType="telephoneNumber" value={identifier} />
          <Field autoComplete="password" label="Password" onChangeText={setPassword} placeholder="Your password" secureTextEntry value={password} />
          {error ? <Text accessibilityRole="alert" style={styles.error}>{error}</Text> : null}
          <PrimaryButton disabled={!identifier || !password} label="Sign in" loading={loading} onPress={submit} />
          <View style={styles.join}><Text style={styles.joinText}>New to AgriShield?</Text><TextButton label="Create an account" onPress={() => router.push('/(auth)/register')} /></View>
        </View>
      </Screen>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: colors.canvas },
  brand: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, paddingTop: spacing.sm },
  logo: { width: 44, height: 44, borderRadius: 12 },
  brandCopy: { flex: 1 },
  wordmark: { fontFamily: typography.display, color: colors.forest, fontSize: 19, fontWeight: '700' },
  tagline: { fontFamily: typography.body, color: colors.muted, fontSize: 12 },
  hero: { marginTop: spacing.lg, backgroundColor: colors.forest, borderRadius: radii.lg, padding: spacing.lg, overflow: 'hidden', gap: spacing.sm },
  kicker: { fontFamily: typography.data, color: colors.millet, fontSize: 11, letterSpacing: 1.2, fontWeight: '700' },
  title: { fontFamily: typography.display, color: colors.paper, fontSize: 36, lineHeight: 41, fontWeight: '700' },
  description: { fontFamily: typography.body, color: colors.leafSoft, fontSize: 16, lineHeight: 23 },
  rows: { marginTop: spacing.md, gap: 6 },
  row: { height: 6, borderRadius: 8, width: '100%', backgroundColor: colors.leaf },
  rowShort: { width: '76%', backgroundColor: '#4B8D67' },
  rowWarm: { width: '44%', backgroundColor: colors.millet },
  form: { gap: spacing.md, paddingTop: spacing.md },
  error: { color: colors.danger, fontFamily: typography.body, fontSize: 14 },
  join: { flexDirection: 'row', alignItems: 'center', flexWrap: 'wrap', gap: spacing.sm, justifyContent: 'center' },
  joinText: { fontFamily: typography.body, color: colors.muted, fontSize: 15 },
});
