import { router } from 'expo-router';
import { Image, StyleSheet, Text, View } from 'react-native';

import { PrimaryButton, Screen, TextButton } from '@/components/ui';
import { colors, radii, spacing, typography } from '@/constants/theme';

const BENEFITS = [
  { number: '01', title: 'Know your farm', body: 'Save your field and receive guidance for your location.' },
  { number: '02', title: 'Ask in your language', body: 'Record a question or take a crop photo when you need help.' },
  { number: '03', title: 'Grow with access', body: 'See verified equipment, input and finance opportunities.' },
];

export default function WelcomeScreen() {
  return (
    <Screen style={styles.screen}>
      <View style={styles.brandRow}>
        <Image accessibilityLabel="AgriShield AI shield and crop logo" source={require('../../../assets/images/agrishield-icon.png')} style={styles.logo} />
        <View><Text style={styles.wordmark}>AgriShield AI</Text><Text style={styles.brandLine}>Your farm. Your decisions.</Text></View>
      </View>

      <View style={styles.hero}>
        <Text style={styles.eyebrow}>BUILT FOR THE FIELD</Text>
        <Text accessibilityRole="header" style={styles.title}>Farm support that speaks your language.</Text>
        <Text style={styles.intro}>From your first farm record to crop checks and productive assets, AgriShield keeps the next action clear.</Text>
        <View style={styles.fieldLines}>
          <View style={styles.fieldLineLong} />
          <View style={styles.fieldLineMedium} />
          <View style={styles.fieldLineShort} />
        </View>
      </View>

      <View style={styles.benefits}>
        {BENEFITS.map((benefit) => (
          <View key={benefit.number} style={styles.benefit}>
            <Text style={styles.number}>{benefit.number}</Text>
            <View style={styles.benefitCopy}><Text style={styles.benefitTitle}>{benefit.title}</Text><Text style={styles.benefitBody}>{benefit.body}</Text></View>
          </View>
        ))}
      </View>

      <View style={styles.actions}>
        <PrimaryButton label="Create my farmer account" onPress={() => router.push('/(auth)/register')} />
        <View style={styles.signInRow}><Text style={styles.signInText}>Already have an account?</Text><TextButton label="Sign in" onPress={() => router.push('/(auth)/sign-in')} /></View>
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  screen: { paddingTop: spacing.sm },
  brandRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  logo: { width: 46, height: 46, borderRadius: 13 },
  wordmark: { fontFamily: typography.display, fontSize: 20, fontWeight: '700', color: colors.forest },
  brandLine: { fontFamily: typography.body, fontSize: 12, color: colors.muted },
  hero: { minHeight: 300, borderRadius: radii.lg, backgroundColor: colors.forest, padding: spacing.lg, gap: spacing.sm, overflow: 'hidden', justifyContent: 'center' },
  eyebrow: { fontFamily: typography.data, color: colors.millet, fontSize: 11, fontWeight: '700', letterSpacing: 1.2 },
  title: { fontFamily: typography.display, color: colors.paper, fontSize: 38, lineHeight: 43, fontWeight: '700', maxWidth: 440 },
  intro: { fontFamily: typography.body, color: colors.leafSoft, fontSize: 16, lineHeight: 23, maxWidth: 500 },
  fieldLines: { gap: 7, marginTop: spacing.md },
  fieldLineLong: { height: 6, width: '100%', borderRadius: 6, backgroundColor: colors.leaf },
  fieldLineMedium: { height: 6, width: '72%', borderRadius: 6, backgroundColor: '#4B8D67' },
  fieldLineShort: { height: 6, width: '38%', borderRadius: 6, backgroundColor: colors.millet },
  benefits: { gap: spacing.sm },
  benefit: { minHeight: 68, borderBottomWidth: 1, borderBottomColor: colors.line, paddingVertical: spacing.sm, flexDirection: 'row', gap: spacing.md, alignItems: 'flex-start' },
  number: { fontFamily: typography.data, fontSize: 11, fontWeight: '700', color: colors.leaf, paddingTop: 4 },
  benefitCopy: { flex: 1, gap: 2 },
  benefitTitle: { fontFamily: typography.body, fontSize: 16, fontWeight: '700', color: colors.ink },
  benefitBody: { fontFamily: typography.body, fontSize: 13, lineHeight: 19, color: colors.muted },
  actions: { gap: spacing.sm },
  signInRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: spacing.sm, flexWrap: 'wrap' },
  signInText: { fontFamily: typography.body, color: colors.muted, fontSize: 15 },
});
