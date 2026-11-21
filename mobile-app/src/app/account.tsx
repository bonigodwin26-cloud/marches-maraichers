import { useRouter } from 'expo-router';
import * as WebBrowser from 'expo-web-browser';
import { useState } from 'react';
import { Modal, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { AppHeader, AppIcon, ErrorBlock, Field, Notice, PageFrame, PageTitle, PrimaryButton } from '@/components/common';
import { colors } from '@/constants/palette';
import { ApiError } from '@/lib/api';
import { EmailVerificationRequiredError, useSession } from '@/lib/session';
import { AppUpdateCard } from '@/components/app-update-card';
import { useAppUpdate } from '@/lib/app-updates';
import { PRIVACY_SUMMARY, PRIVACY_URL, PRIVACY_VERSION } from '@/lib/privacy';

export default function AccountScreen() {
  const router = useRouter();
  const { user, ready, pendingVerification, signIn, signUp, cancelVerification, signOut } = useSession();
  const [creating, setCreating] = useState(false);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmation, setConfirmation] = useState('');
  const [privacyAccepted, setPrivacyAccepted] = useState(false);
  const [privacyOpen, setPrivacyOpen] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');
  const appUpdate = useAppUpdate();

  const submit = async () => {
    setBusy(true);
    setError('');
    setNotice('');
    try {
      if (creating) {
        if (!privacyAccepted) {
          setError('Acceptez la politique de confidentialité pour créer votre compte.');
          setPrivacyOpen(true);
          return;
        }
        const message = await signUp(name.trim(), email.trim(), password, confirmation, true);
        setNotice(message);
        setCreating(false);
        setPassword('');
        setConfirmation('');
        router.navigate('/verify-code');
      } else {
        await signIn(email.trim(), password);
        setPassword('');
      }
    } catch (cause) {
      if (cause instanceof EmailVerificationRequiredError) {
        setNotice(cause.message);
        router.navigate('/verify-code');
        return;
      }
      setError(cause instanceof ApiError ? cause.message : 'Une erreur est survenue. Réessayez.');
    } finally {
      setBusy(false);
    }
  };

  const logout = async () => {
    await signOut();
    setPassword('');
    setNotice('Vous êtes déconnecté.');
  };

  const switchMode = () => {
    const next = !creating;
    setCreating(next);
    setError('');
    setNotice('');
    if (next) {
      setPrivacyAccepted(false);
      setPrivacyOpen(true);
    }
  };

  return (
    <>
      <PageFrame>
        <AppHeader />
        <View><Text style={styles.eyebrow}>ESPACE ACHETEUR · BÉNIN</Text><PageTitle title={user ? 'Votre compte' : 'Bienvenue'} subtitle={user ? 'Retrouvez vos informations et vos demandes de mise en relation.' : 'Connectez-vous pour envoyer et suivre vos demandes.'} /></View>
        {ready && user ? (
          <View style={styles.profileCard}>
            <View style={styles.profileIcon}><AppIcon name="account" size={26} color={colors.forest} /></View>
            <Text style={styles.profileName}>{user.name}</Text>
            <Text style={styles.profileEmail}>{user.email}</Text>
            <Notice>Vos coordonnées personnelles restent protégées. Les producteurs ne les voient qu’après validation d’une demande.</Notice>
            <PrimaryButton label="Me déconnecter" onPress={() => void logout()} secondary />
          </View>
        ) : ready && pendingVerification ? (
          <View style={styles.formCard}>
            <View style={styles.formBadge}><AppIcon name="mail" size={17} color={colors.forest} /><Text style={styles.formBadgeText}>E-MAIL À CONFIRMER</Text></View>
            <Text style={styles.pendingText}>Un code à six chiffres a été envoyé à {pendingVerification.email}. Saisissez-le pour activer votre compte acheteur.</Text>
            {notice ? <Notice>{notice}</Notice> : null}
            <PrimaryButton label="Saisir le code de vérification" onPress={() => router.navigate('/verify-code')} />
            <PrimaryButton label="Annuler cette vérification" onPress={() => void cancelVerification()} secondary />
          </View>
        ) : ready ? (
          <View style={styles.formCard}>
            <View style={styles.formBadge}><AppIcon name="lock" size={17} color={colors.forest} /><Text style={styles.formBadgeText}>{creating ? 'CRÉER UN COMPTE ACHETEUR' : 'CONNEXION SÉCURISÉE'}</Text></View>
            {notice ? <Notice>{notice}</Notice> : null}
            {error ? <ErrorBlock message={error} /> : null}
            {creating ? <Field label="Nom complet" value={name} onChangeText={setName} placeholder="Votre nom" autoCapitalize="words" textContentType="name" /> : null}
            <Field label="Adresse e-mail" value={email} onChangeText={setEmail} placeholder="vous@exemple.com" keyboardType="email-address" autoCapitalize="none" autoCorrect={false} textContentType="emailAddress" />
            <Field label="Mot de passe" value={password} onChangeText={setPassword} placeholder={creating ? '8 caractères minimum' : 'Votre mot de passe'} secureTextEntry textContentType={creating ? 'newPassword' : 'password'} />
            {creating ? <Field label="Confirmer le mot de passe" value={confirmation} onChangeText={setConfirmation} placeholder="Répétez le mot de passe" secureTextEntry textContentType="newPassword" /> : null}
            {creating ? (
              <View style={styles.consentBlock}>
                <Pressable onPress={() => setPrivacyAccepted(!privacyAccepted)} style={styles.consentRow} accessibilityRole="checkbox" accessibilityState={{ checked: privacyAccepted }}>
                  <View style={[styles.checkBox, privacyAccepted && styles.checkBoxOn]}>
                    {privacyAccepted ? <AppIcon name="check" size={13} color={colors.white} /> : null}
                  </View>
                  <Text style={styles.consentText}>J’ai lu et j’accepte la politique de confidentialité (version {PRIVACY_VERSION}).</Text>
                </Pressable>
                <Pressable onPress={() => setPrivacyOpen(true)}><Text style={styles.consentLink}>Lire le résumé de la politique</Text></Pressable>
              </View>
            ) : null}
            <PrimaryButton label={busy ? 'Veuillez patienter…' : creating ? 'Créer mon compte' : 'Se connecter'} onPress={() => void submit()} disabled={busy || !email.trim() || !password || (creating && (!name.trim() || !confirmation || !privacyAccepted))} />
            <Pressable onPress={switchMode} style={styles.switchMode}>
              <Text style={styles.switchText}>{creating ? 'Déjà inscrit ? ' : 'Vous n’avez pas de compte ? '}<Text style={styles.switchLink}>{creating ? 'Se connecter' : 'Créer un compte'}</Text></Text>
            </Pressable>
            <Text style={styles.verificationNote}>Un compte acheteur doit confirmer son adresse e-mail avec le code reçu avant de pouvoir envoyer une demande.</Text>
          </View>
        ) : null}
        {!ready ? <View style={styles.loadingCard}><Text style={styles.verificationNote}>Préparation de votre espace…</Text></View> : null}
        <View style={styles.privacyRow}><AppIcon name="check" size={18} color={colors.leaf} /><Text style={styles.privacyText}>Vos demandes sont vérifiées par l’équipe avant le partage des coordonnées des producteurs.</Text></View>
        <AppUpdateCard {...appUpdate} />
      </PageFrame>

      <Modal visible={privacyOpen} transparent animationType="fade" onRequestClose={() => setPrivacyOpen(false)}>
        <View style={styles.modalBackdrop}>
          <Pressable style={StyleSheet.absoluteFill} onPress={() => setPrivacyOpen(false)} accessibilityLabel="Fermer la politique de confidentialité" />
          <View style={styles.modalCard}>
            <Text style={styles.modalKicker}>CONFIDENTIALITÉ · BÉNIN</Text>
            <Text style={styles.modalTitle}>Vos données, vos droits</Text>
            <ScrollView style={styles.modalScroll}>
              {PRIVACY_SUMMARY.map((line) => <Text key={line} style={styles.modalItem}>• {line}</Text>)}
            </ScrollView>
            {PRIVACY_URL ? <Pressable onPress={() => void WebBrowser.openBrowserAsync(PRIVACY_URL)} accessibilityRole="link"><Text style={styles.modalPolicyLink}>Lire la politique complète en ligne</Text></Pressable> : null}
            <Text style={styles.modalVersion}>Politique version {PRIVACY_VERSION}</Text>
            <PrimaryButton label="J’ai lu et j’accepte" onPress={() => { setPrivacyAccepted(true); setPrivacyOpen(false); }} />
            <PrimaryButton label="Pas maintenant" onPress={() => setPrivacyOpen(false)} secondary />
          </View>
        </View>
      </Modal>
    </>
  );
}

const styles = StyleSheet.create({
  eyebrow: { color: colors.leaf, fontSize: 10, fontWeight: '900', letterSpacing: 1.1, marginBottom: 7 },
  profileCard: { alignItems: 'center', gap: 12, padding: 18, backgroundColor: colors.white, borderWidth: 1, borderColor: colors.line, borderRadius: 18 },
  profileIcon: { width: 62, height: 62, alignItems: 'center', justifyContent: 'center', borderRadius: 31, backgroundColor: colors.pale },
  profileName: { color: colors.ink, fontSize: 20, fontWeight: '900' },
  profileEmail: { color: colors.muted, fontSize: 13, marginTop: -7 },
  formCard: { gap: 14, padding: 16, backgroundColor: colors.white, borderWidth: 1, borderColor: colors.line, borderRadius: 18 },
  formBadge: { alignSelf: 'flex-start', flexDirection: 'row', alignItems: 'center', gap: 7, paddingHorizontal: 10, paddingVertical: 7, borderRadius: 18, backgroundColor: colors.pale },
  formBadgeText: { color: colors.forest, fontSize: 9, fontWeight: '900', letterSpacing: 0.7 },
  pendingText: { color: colors.muted, fontSize: 13, lineHeight: 19 },
  consentBlock: { gap: 6, paddingTop: 2 },
  consentRow: { flexDirection: 'row', alignItems: 'flex-start', gap: 9 },
  checkBox: { width: 19, height: 19, marginTop: 1, borderRadius: 5, borderWidth: 1.5, borderColor: colors.line, alignItems: 'center', justifyContent: 'center', backgroundColor: colors.white },
  checkBoxOn: { backgroundColor: colors.forest, borderColor: colors.forest },
  consentText: { flex: 1, color: colors.ink, fontSize: 12, lineHeight: 18 },
  consentLink: { color: colors.forest, fontSize: 12, fontWeight: '800', textDecorationLine: 'underline', paddingLeft: 28 },
  switchMode: { paddingVertical: 3, alignItems: 'center' },
  switchText: { color: colors.muted, fontSize: 12, textAlign: 'center' },
  switchLink: { color: colors.forest, fontWeight: '900' },
  verificationNote: { color: colors.muted, fontSize: 11, lineHeight: 17, textAlign: 'center' },
  loadingCard: { padding: 20, backgroundColor: colors.white, borderRadius: 16 },
  privacyRow: { flexDirection: 'row', alignItems: 'flex-start', gap: 9, paddingHorizontal: 2 },
  privacyText: { flex: 1, color: colors.muted, fontSize: 11, lineHeight: 17 },
  modalBackdrop: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: 20, backgroundColor: 'rgba(3, 30, 22, 0.66)' },
  modalCard: { width: '100%', maxWidth: 440, maxHeight: '85%', gap: 12, padding: 22, borderRadius: 20, backgroundColor: colors.white },
  modalKicker: { color: colors.leaf, fontSize: 10, fontWeight: '900', letterSpacing: 1.2 },
  modalTitle: { color: colors.ink, fontSize: 22, fontWeight: '900' },
  modalScroll: { maxHeight: 260 },
  modalItem: { color: colors.muted, fontSize: 13, lineHeight: 19, marginBottom: 9 },
  modalPolicyLink: { color: colors.forest, fontSize: 12, fontWeight: '800', textDecorationLine: 'underline', textAlign: 'center' },
  modalVersion: { color: colors.muted, fontSize: 10, textAlign: 'center', marginBottom: 2 },
});
