import { useRouter } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';

import { AppHeader, AppIcon, ErrorBlock, Field, Notice, PageFrame, PageTitle, PrimaryButton } from '@/components/common';
import { colors } from '@/constants/palette';
import { ApiError } from '@/lib/api';
import { useSession } from '@/lib/session';

export default function VerifyCodeScreen() {
  const router = useRouter();
  const { pendingVerification, verifyCode, resendCode, cancelVerification } = useSession();
  const [code, setCode] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');

  if (!pendingVerification) {
    return (
      <PageFrame>
        <AppHeader />
        <Notice tone="warning">Aucune vérification en cours. Connectez-vous ou créez un compte pour recevoir un code.</Notice>
        <PrimaryButton label="Ouvrir mon compte" onPress={() => router.replace('/account')} />
      </PageFrame>
    );
  }

  const submit = async () => {
    setBusy(true);
    setError('');
    setNotice('');
    try {
      await verifyCode(code.trim());
      router.replace('/account');
    } catch (cause) {
      setError(cause instanceof ApiError ? cause.message : 'Le code n’a pas pu être vérifié. Réessayez.');
      setCode('');
    } finally {
      setBusy(false);
    }
  };

  const resend = async () => {
    setBusy(true);
    setError('');
    setNotice('');
    try {
      setNotice(await resendCode());
    } catch (cause) {
      setError(cause instanceof ApiError ? cause.message : 'Le renvoi a échoué. Réessayez dans un instant.');
    } finally {
      setBusy(false);
    }
  };

  const quit = async () => {
    await cancelVerification();
    router.replace('/account');
  };

  return (
    <PageFrame>
      <AppHeader />
      <View>
        <Text style={styles.eyebrow}>CONFIRMATION · BÉNIN</Text>
        <PageTitle title="Confirmez votre e-mail" subtitle={`Saisissez le code à six chiffres envoyé à ${pendingVerification.email}.`} />
      </View>
      <Notice>Le code est valable 15 minutes. Ne le partagez avec personne.</Notice>
      {notice ? <Notice>{notice}</Notice> : null}
      {error ? <ErrorBlock message={error} /> : null}
      <Field
        label="Code de vérification"
        value={code}
        onChangeText={(value) => setCode(value.replace(/\D/g, '').slice(0, 6))}
        placeholder="123456"
        keyboardType="number-pad"
        autoComplete="one-time-code"
        maxLength={6}
        editable={!busy}
        style={styles.codeInput}
      />
      <PrimaryButton label={busy ? 'Veuillez patienter…' : 'Confirmer mon adresse'} onPress={() => void submit()} disabled={busy || code.length !== 6} />
      <PrimaryButton label="Renvoyer le code" onPress={() => void resend()} disabled={busy} secondary />
      <PrimaryButton label="Revenir au compte" onPress={() => void quit()} disabled={busy} secondary />
      <View style={styles.helpRow}><AppIcon name="mail" size={17} color={colors.forest} /><Text style={styles.helpText}>Le message peut arriver dans vos courriers indésirables. Vérifiez également l’adresse saisie.</Text></View>
    </PageFrame>
  );
}

const styles = StyleSheet.create({
  eyebrow: { color: colors.leaf, fontSize: 10, fontWeight: '900', letterSpacing: 1.1, marginBottom: 7 },
  codeInput: { fontSize: 24, fontWeight: '900', letterSpacing: 8, textAlign: 'center' },
  helpRow: { flexDirection: 'row', alignItems: 'flex-start', gap: 9 },
  helpText: { flex: 1, color: colors.muted, fontSize: 11, lineHeight: 17 },
});
