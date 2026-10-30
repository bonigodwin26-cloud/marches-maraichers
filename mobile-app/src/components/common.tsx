import { SymbolView } from 'expo-symbols';
import React from 'react';
import { ActivityIndicator, Pressable, RefreshControl, ScrollView, StyleSheet, Text, TextInput, View, type TextInputProps } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { colors } from '@/constants/palette';

export type IconName = 'leaf' | 'home' | 'prices' | 'markets' | 'messages' | 'account' | 'search' | 'bell' | 'location' | 'calendar' | 'info' | 'arrow' | 'back' | 'phone' | 'check' | 'filter' | 'close' | 'refresh' | 'mail' | 'lock' | 'send' | 'clock';

const symbols = {
  leaf: { ios: 'leaf.fill', android: 'eco', web: 'eco' },
  home: { ios: 'house.fill', android: 'home', web: 'home' },
  prices: { ios: 'chart.bar.fill', android: 'bar_chart', web: 'bar_chart' },
  markets: { ios: 'storefront.fill', android: 'storefront', web: 'storefront' },
  messages: { ios: 'bubble.left.and.bubble.right.fill', android: 'chat_bubble', web: 'chat_bubble' },
  account: { ios: 'person.crop.circle.fill', android: 'account_circle', web: 'account_circle' },
  search: { ios: 'magnifyingglass', android: 'search', web: 'search' },
  bell: { ios: 'bell.fill', android: 'notifications', web: 'notifications' },
  location: { ios: 'mappin.and.ellipse', android: 'location_on', web: 'location_on' },
  calendar: { ios: 'calendar', android: 'calendar_month', web: 'calendar_month' },
  info: { ios: 'info.circle.fill', android: 'info', web: 'info' },
  arrow: { ios: 'arrow.right', android: 'arrow_forward', web: 'arrow_forward' },
  back: { ios: 'arrow.left', android: 'arrow_back', web: 'arrow_back' },
  phone: { ios: 'phone.fill', android: 'call', web: 'call' },
  check: { ios: 'checkmark.circle.fill', android: 'check_circle', web: 'check_circle' },
  filter: { ios: 'slider.horizontal.3', android: 'filter_list', web: 'filter_list' },
  close: { ios: 'xmark', android: 'close', web: 'close' },
  refresh: { ios: 'arrow.clockwise', android: 'refresh', web: 'refresh' },
  mail: { ios: 'envelope.fill', android: 'mail', web: 'mail' },
  lock: { ios: 'lock.fill', android: 'lock', web: 'lock' },
  send: { ios: 'paperplane.fill', android: 'send', web: 'send' },
  clock: { ios: 'clock.fill', android: 'schedule', web: 'schedule' },
} as const;

export function AppIcon({ name, size = 20, color = colors.forest }: { name: IconName; size?: number; color?: string }) {
  return <SymbolView name={symbols[name]} size={size} tintColor={color} />;
}

export function AppHeader({ subtitle = 'Bénin · Information de prix' }: { subtitle?: string }) {
  return (
    <View style={styles.header}>
      <View style={styles.brandMark}><AppIcon name="leaf" size={22} color={colors.lime} /></View>
      <View style={styles.brandCopy}>
        <Text style={styles.brandName}>Marchés maraîchers</Text>
        <Text style={styles.brandSubtitle}>{subtitle}</Text>
      </View>
      <View style={styles.locationBadge}><AppIcon name="location" size={14} color={colors.forest} /><Text style={styles.locationText}>Bénin</Text></View>
    </View>
  );
}

export function PageFrame({ children, refreshing, onRefresh }: React.PropsWithChildren<{ refreshing?: boolean; onRefresh?: () => void }>) {
  return (
    <SafeAreaView edges={['top']} style={styles.screen}>
      <ScrollView
        contentContainerStyle={styles.pageContent}
        keyboardShouldPersistTaps="handled"
        showsVerticalScrollIndicator={false}
        refreshControl={onRefresh ? <RefreshControl refreshing={!!refreshing} onRefresh={onRefresh} tintColor={colors.forest} /> : undefined}>
        {children}
      </ScrollView>
    </SafeAreaView>
  );
}

export function Eyebrow({ children }: React.PropsWithChildren) {
  return <Text style={styles.eyebrow}>{children}</Text>;
}

export function PageTitle({ title, subtitle }: { title: string; subtitle?: string }) {
  return (
    <View style={styles.titleBlock}>
      <Text style={styles.pageTitle}>{title}</Text>
      {subtitle ? <Text style={styles.pageSubtitle}>{subtitle}</Text> : null}
    </View>
  );
}

export function SectionTitle({ children, action, onAction }: React.PropsWithChildren<{ action?: string; onAction?: () => void }>) {
  return (
    <View style={styles.sectionTitleRow}>
      <Text style={styles.sectionTitle}>{children}</Text>
      {action && onAction ? <Pressable onPress={onAction} hitSlop={8}><Text style={styles.sectionAction}>{action}</Text></Pressable> : null}
    </View>
  );
}

export function PrimaryButton({ label, onPress, disabled, secondary = false }: { label: string; onPress: () => void; disabled?: boolean; secondary?: boolean }) {
  return (
    <Pressable accessibilityRole="button" disabled={disabled} onPress={onPress} style={({ pressed }) => [styles.button, secondary && styles.buttonSecondary, disabled && styles.buttonDisabled, pressed && styles.buttonPressed]}>
      <Text style={[styles.buttonText, secondary && styles.buttonSecondaryText]}>{label}</Text>
    </Pressable>
  );
}

export function Notice({ children, tone = 'info' }: React.PropsWithChildren<{ tone?: 'info' | 'warning' }>) {
  const warning = tone === 'warning';
  return (
    <View style={[styles.notice, warning && styles.noticeWarning]}>
      <AppIcon name="info" size={17} color={warning ? colors.amber : colors.forest} />
      <Text style={[styles.noticeText, warning && styles.noticeWarningText]}>{children}</Text>
    </View>
  );
}

export function Field({ label, style, multiline, ...props }: { label: string } & TextInputProps) {
  return (
    <View style={styles.fieldGroup}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <TextInput {...props} multiline={multiline} style={[styles.fieldInput, multiline && styles.fieldMultiline, style]} placeholderTextColor="#98A59C" />
    </View>
  );
}
export function LoadingBlock({ label = 'Chargement…' }: { label?: string }) {
  return <View style={styles.loading}><ActivityIndicator color={colors.forest} /><Text style={styles.loadingText}>{label}</Text></View>;
}

export function ErrorBlock({ message, onRetry }: { message: string; onRetry?: () => void }) {
  return (
    <View style={styles.errorBox}>
      <Text style={styles.errorText}>{message}</Text>
      {onRetry ? <Pressable onPress={onRetry} style={styles.retry}><AppIcon name="refresh" size={16} color={colors.forest} /><Text style={styles.retryText}>Réessayer</Text></Pressable> : null}
    </View>
  );
}

export function EmptyBlock({ title, text }: { title: string; text: string }) {
  return <View style={styles.emptyBox}><View style={styles.emptyIcon}><AppIcon name="leaf" size={22} color={colors.forest} /></View><Text style={styles.emptyTitle}>{title}</Text><Text style={styles.emptyText}>{text}</Text></View>;
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  pageContent: { paddingHorizontal: 16, paddingTop: 8, paddingBottom: 28, gap: 17 },
  header: { minHeight: 54, flexDirection: 'row', alignItems: 'center', gap: 10 },
  brandMark: { width: 42, height: 42, borderRadius: 15, backgroundColor: colors.forest, alignItems: 'center', justifyContent: 'center' },
  brandCopy: { flex: 1 },
  brandName: { color: colors.ink, fontWeight: '800', fontSize: 15 },
  brandSubtitle: { color: colors.muted, fontSize: 11, marginTop: 2 },
  locationBadge: { flexDirection: 'row', alignItems: 'center', gap: 4, borderWidth: 1, borderColor: colors.line, backgroundColor: colors.white, paddingHorizontal: 9, paddingVertical: 6, borderRadius: 18 },
  locationText: { color: colors.forest, fontSize: 10, fontWeight: '700' },
  eyebrow: { color: colors.leaf, fontSize: 10, fontWeight: '800', letterSpacing: 1.2, marginBottom: 8 },
  titleBlock: { gap: 6 },
  pageTitle: { color: colors.ink, fontSize: 30, fontWeight: '800', lineHeight: 35, letterSpacing: -0.7 },
  pageSubtitle: { color: colors.muted, fontSize: 14, lineHeight: 21 },
  sectionTitleRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 10, marginTop: 6 },
  sectionTitle: { color: colors.ink, fontSize: 18, fontWeight: '800' },
  sectionAction: { color: colors.forest, fontSize: 12, fontWeight: '800' },
  button: { minHeight: 48, borderRadius: 14, backgroundColor: colors.forest, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 17 },
  buttonSecondary: { backgroundColor: colors.pale, borderWidth: 1, borderColor: colors.line },
  buttonDisabled: { opacity: 0.55 },
  buttonPressed: { opacity: 0.82, transform: [{ scale: 0.99 }] },
  buttonText: { color: colors.white, fontSize: 14, fontWeight: '800' },
  buttonSecondaryText: { color: colors.forest },
  notice: { flexDirection: 'row', gap: 10, alignItems: 'flex-start', backgroundColor: colors.pale, borderWidth: 1, borderColor: '#DCEBD0', borderRadius: 13, padding: 12 },
  noticeWarning: { backgroundColor: colors.amberBg, borderColor: '#F0DAA1' },
  noticeText: { color: colors.ink, flex: 1, fontSize: 12, lineHeight: 18 },
  noticeWarningText: { color: '#785717' },
  fieldGroup: { gap: 6 },
  fieldLabel: { color: colors.ink, fontSize: 12, fontWeight: '800' },
  fieldInput: { minHeight: 46, borderWidth: 1, borderColor: colors.line, borderRadius: 12, backgroundColor: colors.white, paddingHorizontal: 13, paddingVertical: 11, color: colors.ink, fontSize: 14 },
  fieldMultiline: { minHeight: 90, textAlignVertical: 'top' },
  loading: { minHeight: 90, alignItems: 'center', justifyContent: 'center', gap: 8 },
  loadingText: { color: colors.muted, fontSize: 12 },
  errorBox: { backgroundColor: colors.white, borderRadius: 14, padding: 15, borderWidth: 1, borderColor: '#F0C9C2', gap: 10 },
  errorText: { color: colors.red, fontSize: 13, lineHeight: 19 },
  retry: { alignSelf: 'flex-start', flexDirection: 'row', alignItems: 'center', gap: 6, paddingVertical: 4 },
  retryText: { color: colors.forest, fontSize: 12, fontWeight: '800' },
  emptyBox: { alignItems: 'center', backgroundColor: colors.white, borderRadius: 16, borderWidth: 1, borderColor: colors.line, paddingHorizontal: 20, paddingVertical: 24, gap: 8 },
  emptyIcon: { width: 46, height: 46, borderRadius: 23, alignItems: 'center', justifyContent: 'center', backgroundColor: colors.pale },
  emptyTitle: { color: colors.ink, fontSize: 15, fontWeight: '800', textAlign: 'center' },
  emptyText: { color: colors.muted, fontSize: 13, textAlign: 'center', lineHeight: 20 },
});