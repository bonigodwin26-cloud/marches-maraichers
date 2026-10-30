import { Pressable, StyleSheet, Text, View } from 'react-native';

import { AppIcon, PrimaryButton } from '@/components/common';
import { colors } from '@/constants/palette';
import { getInstalledVersionName, type AppRelease, type UpdateState } from '@/lib/app-updates';

type AppUpdateCardProps = {
  release: AppRelease | null;
  state: UpdateState;
  downloadError: string;
  checkForUpdates: () => Promise<void>;
  downloadUpdate: () => Promise<void>;
  compact?: boolean;
};

export function AppUpdateCard({ release, state, downloadError, checkForUpdates, downloadUpdate, compact = false }: AppUpdateCardProps) {
  const versionName = getInstalledVersionName();

  if (compact && !release) return null;

  return (
    <View style={[styles.card, compact && styles.compactCard, release && styles.updateCard]}>
      <View style={styles.heading}>
        <View style={styles.icon}>
          <AppIcon name={release ? 'arrow' : 'refresh'} size={18} color={colors.forest} />
        </View>
        <View style={styles.headingCopy}>
          <Text style={styles.title}>{release ? `Version ${release.version_name} disponible` : 'Mise à jour de l’application'}</Text>
          <Text style={styles.subtitle}>
            {state === 'checking' ? 'Vérification en cours…' : state === 'up_to_date' ? `Version ${versionName} · Vous avez la dernière version.` : state === 'unavailable' ? 'Vérification impossible pour le moment.' : 'Une nouvelle version est prête.'}
          </Text>
        </View>
      </View>
      {release?.release_notes ? <Text style={styles.notes}>{release.release_notes}</Text> : null}
      {release ? (
        <>
          <PrimaryButton label="Télécharger la mise à jour" onPress={() => void downloadUpdate()} secondary />
          <Text style={styles.note}>Android vous demandera de confirmer l’installation après le téléchargement.</Text>
        </>
      ) : compact ? null : (
        <>
          {state === 'unavailable' ? <Text style={styles.note}>Vérifiez que votre téléphone est connecté au même Wi-Fi que l’ordinateur qui héberge l’application.</Text> : null}
          <Pressable accessibilityRole="button" disabled={state === 'checking'} onPress={() => void checkForUpdates()} style={({ pressed }) => [styles.checkButton, state === 'checking' && styles.disabled, pressed && styles.pressed]}>
            <Text style={styles.checkButtonText}>{state === 'checking' ? 'Vérification…' : 'Vérifier maintenant'}</Text>
          </Pressable>
        </>
      )}
      {downloadError ? <Text style={styles.error}>{downloadError}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  card: { gap: 12, padding: 15, borderRadius: 17, borderWidth: 1, borderColor: colors.line, backgroundColor: colors.white },
  compactCard: { borderColor: '#BBD7B7', backgroundColor: colors.pale },
  updateCard: { borderColor: colors.forest, backgroundColor: colors.pale },
  heading: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  icon: { width: 36, height: 36, alignItems: 'center', justifyContent: 'center', borderRadius: 12, backgroundColor: colors.white },
  headingCopy: { flex: 1, gap: 3 },
  title: { color: colors.ink, fontSize: 14, fontWeight: '900' },
  subtitle: { color: colors.muted, fontSize: 11, lineHeight: 16 },
  notes: { color: colors.ink, fontSize: 12, lineHeight: 18 },
  note: { color: colors.muted, fontSize: 10, lineHeight: 15, textAlign: 'center' },
  checkButton: { alignSelf: 'flex-start', paddingVertical: 3 },
  checkButtonText: { color: colors.forest, fontSize: 12, fontWeight: '900' },
  disabled: { opacity: 0.55 },
  pressed: { opacity: 0.75 },
  error: { color: colors.red, fontSize: 11, lineHeight: 16 },
});