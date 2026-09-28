import { router } from 'expo-router';
import { useState } from 'react';
import { KeyboardAvoidingView, Platform, StyleSheet, Text, View } from 'react-native';

import { Field, PageHeader, PrimaryButton, Screen, TextButton } from '@/components/ui';
import { colors, spacing, typography } from '@/constants/theme';
import { useAuth } from '@/context/auth-context';
import { ApiError } from '@/lib/api';

export default function RegisterScreen() {
  const { register } = useAuth();
  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [referral, setReferral] = useState('');
  const [password, setPassword] = useState('');
  const [confirmation, setConfirmation] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const submit = async () => {
    setLoading(true); setError('');
    try {
      await register({ name, phone, referral_code: referral || null, password, password_confirmation: confirmation, locale: 'en' });
      router.replace('/farms/new');
    } catch (reason) {
      if (reason instanceof ApiError) {
        const firstFieldError = Object.values(reason.errors)[0]?.[0];
        setError(firstFieldError ?? reason.message);
      } else setError('Check your connection and try again.');
    } finally { setLoading(false); }
  };

  return (
    <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={styles.flex}>
      <Screen>
        <TextButton label="Back" onPress={() => router.back()} />
        <PageHeader eyebrow="Create your farmer account" title="Start with your phone" description="Your phone number becomes your secure AgriShield sign-in." />
        <View style={styles.form}>
          <Field autoComplete="name" label="Full name" onChangeText={setName} value={name} />
          <Field autoComplete="tel" keyboardType="phone-pad" label="Phone number" onChangeText={setPhone} placeholder="+234 801 234 5678" textContentType="telephoneNumber" value={phone} />
          <Text style={styles.help}>Use +234 followed by your number without the first zero.</Text>
          <Field autoCapitalize="characters" label="Farmer group code (optional)" onChangeText={setReferral} value={referral} />
          <Field autoComplete="new-password" label="Password" onChangeText={setPassword} secureTextEntry value={password} />
          <Field autoComplete="new-password" label="Confirm password" onChangeText={setConfirmation} secureTextEntry value={confirmation} />
          {error ? <Text accessibilityRole="alert" style={styles.error}>{error}</Text> : null}
          <PrimaryButton disabled={!name || !phone || !password || password !== confirmation} label="Create account and add farm" loading={loading} onPress={submit} />
        </View>
      </Screen>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: colors.canvas },
  form: { gap: spacing.md },
  error: { color: colors.danger, fontFamily: typography.body, fontSize: 14 },
  help: { color: colors.muted, fontFamily: typography.body, fontSize: 13, lineHeight: 18, marginTop: -spacing.sm },
});
