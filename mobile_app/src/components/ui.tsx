import type { PropsWithChildren, ReactNode } from 'react';
import {
  ActivityIndicator,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  type TextInputProps,
  View,
  type ViewStyle,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { colors, radii, shadows, spacing, typography } from '@/constants/theme';

export function Screen({ children, scroll = true, style }: PropsWithChildren<{ scroll?: boolean; style?: ViewStyle }>) {
  const body = <View style={[styles.content, style]}>{children}</View>;
  return (
    <SafeAreaView edges={['top']} style={styles.safeArea}>
      {scroll ? <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">{body}</ScrollView> : body}
    </SafeAreaView>
  );
}

export function PageHeader({ eyebrow, title, description, action }: { eyebrow?: string; title: string; description?: string; action?: ReactNode }) {
  return (
    <View style={styles.header}>
      <View style={styles.headerText}>
        {eyebrow ? <Text style={styles.eyebrow}>{eyebrow}</Text> : null}
        <Text accessibilityRole="header" style={styles.title}>{title}</Text>
        {description ? <Text style={styles.description}>{description}</Text> : null}
      </View>
      {action}
    </View>
  );
}

export function SectionTitle({ title, action }: { title: string; action?: ReactNode }) {
  return <View style={styles.sectionTitle}><Text style={styles.sectionText}>{title}</Text>{action}</View>;
}

export function Card({ children, style }: PropsWithChildren<{ style?: ViewStyle }>) {
  return <View style={[styles.card, style]}>{children}</View>;
}

export function PrimaryButton({ label, onPress, loading = false, disabled = false, tone = 'primary' }: {
  label: string;
  onPress: () => void;
  loading?: boolean;
  disabled?: boolean;
  tone?: 'primary' | 'secondary' | 'danger';
}) {
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={label}
      disabled={disabled || loading}
      onPress={onPress}
      style={({ pressed }) => [styles.button, styles[`button_${tone}`], pressed && styles.pressed, (disabled || loading) && styles.disabled]}
    >
      {loading ? <ActivityIndicator color={tone === 'secondary' ? colors.forest : colors.paper} /> : <Text style={[styles.buttonText, tone === 'secondary' && styles.buttonTextSecondary]}>{label}</Text>}
    </Pressable>
  );
}

export function TextButton({ label, onPress }: { label: string; onPress: () => void }) {
  return <Pressable accessibilityRole="button" onPress={onPress} hitSlop={8} style={styles.textButton}><Text style={styles.textButtonLabel}>{label}</Text></Pressable>;
}

export function Field({ label, error, ...props }: TextInputProps & { label: string; error?: string }) {
  return (
    <View style={styles.field}>
      <Text style={styles.label}>{label}</Text>
      <TextInput
        accessibilityLabel={label}
        placeholderTextColor={colors.muted}
        style={[styles.input, props.multiline && styles.multiline, error && styles.inputError]}
        {...props}
      />
      {error ? <Text style={styles.errorText}>{error}</Text> : null}
    </View>
  );
}

export function Pill({ label, tone = 'neutral' }: { label: string; tone?: 'neutral' | 'success' | 'warning' | 'danger' }) {
  return <View style={[styles.pill, styles[`pill_${tone}`]]}><Text style={[styles.pillText, styles[`pillText_${tone}`]]}>{label.replaceAll('_', ' ')}</Text></View>;
}

export function EmptyState({ title, message, action }: { title: string; message: string; action?: ReactNode }) {
  return <Card style={styles.state}><View style={styles.stateMark} /><Text style={styles.stateTitle}>{title}</Text><Text style={styles.stateMessage}>{message}</Text>{action}</Card>;
}

export function ErrorState({ message, retry }: { message: string; retry?: () => void }) {
  return <Card style={styles.errorCard}><Text style={styles.errorTitle}>Couldn’t load this</Text><Text style={styles.stateMessage}>{message}</Text>{retry ? <TextButton label="Try again" onPress={retry} /> : null}</Card>;
}

export function LoadingState({ label = 'Loading your farm data…' }: { label?: string }) {
  return <View style={styles.loading}><ActivityIndicator color={colors.leaf} /><Text style={styles.stateMessage}>{label}</Text></View>;
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: colors.canvas },
  scroll: { flexGrow: 1 },
  content: { paddingHorizontal: spacing.md, paddingTop: spacing.md, paddingBottom: 120, gap: spacing.md, width: '100%', maxWidth: 760, alignSelf: 'center' },
  header: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md, marginBottom: spacing.sm },
  headerText: { flex: 1, gap: spacing.xs },
  eyebrow: { fontFamily: typography.data, fontSize: 12, fontWeight: '700', color: colors.leaf, letterSpacing: 1.1, textTransform: 'uppercase' },
  title: { fontFamily: typography.display, fontSize: 32, lineHeight: 38, color: colors.ink, fontWeight: '700' },
  description: { fontFamily: typography.body, fontSize: 16, lineHeight: 23, color: colors.muted },
  sectionTitle: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md, marginTop: spacing.sm },
  sectionText: { fontFamily: typography.body, fontSize: 19, lineHeight: 24, fontWeight: '700', color: colors.ink },
  card: { backgroundColor: colors.paper, borderRadius: radii.md, borderWidth: 1, borderColor: colors.line, padding: spacing.md, gap: spacing.sm, ...shadows.card },
  button: { minHeight: 52, borderRadius: radii.md, paddingHorizontal: spacing.lg, alignItems: 'center', justifyContent: 'center' },
  button_primary: { backgroundColor: colors.forest },
  button_secondary: { backgroundColor: colors.leafSoft, borderWidth: 1, borderColor: colors.leaf },
  button_danger: { backgroundColor: colors.danger },
  buttonText: { fontFamily: typography.body, fontSize: 16, fontWeight: '700', color: colors.paper },
  buttonTextSecondary: { color: colors.forest },
  pressed: { opacity: 0.82, transform: [{ scale: 0.99 }] },
  disabled: { opacity: 0.5 },
  textButton: { minHeight: 44, justifyContent: 'center', alignSelf: 'flex-start' },
  textButtonLabel: { fontFamily: typography.body, fontSize: 15, fontWeight: '700', color: colors.leaf, textDecorationLine: 'underline' },
  field: { gap: 6 },
  label: { fontFamily: typography.body, fontSize: 14, fontWeight: '700', color: colors.ink },
  input: { minHeight: 52, borderWidth: 1, borderColor: colors.line, borderRadius: radii.md, paddingHorizontal: 14, backgroundColor: colors.paper, color: colors.ink, fontFamily: typography.body, fontSize: 16 },
  multiline: { minHeight: 112, paddingTop: 14, textAlignVertical: 'top' },
  inputError: { borderColor: colors.danger },
  errorText: { fontFamily: typography.body, fontSize: 13, color: colors.danger },
  pill: { alignSelf: 'flex-start', borderRadius: radii.pill, paddingHorizontal: 10, paddingVertical: 5, backgroundColor: colors.canvas },
  pill_success: { backgroundColor: colors.leafSoft }, pill_warning: { backgroundColor: colors.milletSoft }, pill_danger: { backgroundColor: '#F4DDDA' }, pill_neutral: {},
  pillText: { fontFamily: typography.body, fontSize: 12, fontWeight: '700', color: colors.muted, textTransform: 'capitalize' },
  pillText_success: { color: colors.success }, pillText_warning: { color: colors.warning }, pillText_danger: { color: colors.danger }, pillText_neutral: {},
  state: { paddingVertical: spacing.xl, alignItems: 'center' },
  stateMark: { width: 44, height: 6, borderRadius: radii.pill, backgroundColor: colors.millet, marginBottom: spacing.sm },
  stateTitle: { fontFamily: typography.display, fontSize: 22, fontWeight: '700', color: colors.ink, textAlign: 'center' },
  stateMessage: { fontFamily: typography.body, fontSize: 15, lineHeight: 22, color: colors.muted, textAlign: 'center' },
  errorCard: { borderColor: '#E7C6C1' },
  errorTitle: { fontFamily: typography.body, fontSize: 17, fontWeight: '700', color: colors.danger },
  loading: { minHeight: 180, alignItems: 'center', justifyContent: 'center', gap: spacing.md },
});
