import { useRouter } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';

import { AppHeader, PageFrame, PageTitle, PrimaryButton } from '@/components/common';
import { colors } from '@/constants/palette';

export default function NotFoundScreen() {
  const router = useRouter();

  return (
    <PageFrame>
      <AppHeader />
      <View style={styles.codeBlock}>
        <Text style={styles.code}>404</Text>
        <Text style={styles.codeLabel}>PAGE INTROUVABLE</Text>
      </View>
      <PageTitle
        title="Cette page n’existe pas"
        subtitle="Le lien est peut-être ancien, ou la page a été déplacée. Reprenons depuis l’accueil."
      />
      <View style={styles.actions}>
        <PrimaryButton label="Retour à l’accueil" onPress={() => router.replace('/')} />
        <PrimaryButton label="Consulter les prix" onPress={() => router.replace('/prices')} secondary />
      </View>
    </PageFrame>
  );
}

const styles = StyleSheet.create({
  codeBlock: { alignItems: 'center', gap: 4, paddingVertical: 18 },
  code: { color: colors.forest, fontSize: 76, fontWeight: '900', letterSpacing: -4, lineHeight: 82 },
  codeLabel: { color: colors.leaf, fontSize: 11, fontWeight: '900', letterSpacing: 2 },
  actions: { gap: 10, marginTop: 8 },
});
