import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { AppHeader, AppIcon, ErrorBlock, Field, Notice, PageFrame, PageTitle, PrimaryButton } from '@/components/common';
import { colors } from '@/constants/palette';
import { ApiError } from '@/lib/api';
import { useSession } from '@/lib/session';
import { AppUpdateCard } from '@/components/app-update-card';
import { useAppUpdate } from '@/lib/app-updates';

export default function AccountScreen() {
  const { user, ready, signIn, signUp, signOut } = useSession();
  const [creating, setCreating] = useState(false);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmation, setConfirmation] = useState('');
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
        const message = await signUp(name.trim(), email.trim(), password, confirmation);
        setNotice(message);
        setCreating(false);
        setPassword('');
        setConfirmation('');
      } else {
        await signIn(email.trim(), password);
        setPassword('');
      }
    } catch (cause) {
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

  return (
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
      ) : ready ? (
        <View style={styles.formCard}>
          <View style={styles.formBadge}><AppIcon name="lock" size={17} color={colors.forest} /><Text style={styles.formBadgeText}>{creating ? 'CRÉER UN COMPTE ACHETEUR' : 'CONNEXION SÉCURISÉE'}</Text></View>
          {notice ? <Notice>{notice}</Notice> : null}
          {error ? <ErrorBlock message={error} /> : null}
          {creating ? <Field label="Nom complet" value={name} onChangeText={setName} placeholder="Votre nom" autoCapitalize="words" textContentType="name" /> : null}
          <Field label="Adresse e-mail" value={email} onChangeText={setEmail} placeholder="vous@exemple.com" keyboardType="email-address" autoCapitalize="none" autoCorrect={false} textContentType="emailAddress" />
          <Field label="Mot de passe" value={password} onChangeText={setPassword} placeholder={creating ? '8 caractères minimum' : 'Votre mot de passe'} secureTextEntry textContentType={creating ? 'newPassword' : 'password'} />
          {creating ? <Field label="Confirmer le mot de passe" value={confirmation} onChangeText={setConfirmation} placeholder="Répétez le mot de passe" secureTextEntry textContentType="newPassword" /> : null}
          <PrimaryButton label={busy ? 'Veuillez patienter…' : creating ? 'Créer mon compte' : 'Se connecter'} onPress={() => void submit()} disabled={busy || !email.trim() || !password || (creating && (!name.trim() || !confirmation))} />
          <Pressable onPress={() => { setCreating(!creating); setError(''); setNotice(''); }} style={styles.switchMode}>
            <Text style={styles.switchText}>{creating ? 'Déjà inscrit ? ' : 'Vous n’avez pas de compte ? '}<Text style={styles.switchLink}>{creating ? 'Se connecter' : 'Créer un compte'}</Text></Text>
          </Pressable>
          <Text style={styles.verificationNote}>Un compte acheteur doit confirmer son adresse e-mail avant de pouvoir envoyer une demande.</Text>
        </View>
      ) : null}
      {!ready ? <View style={styles.loadingCard}><Text style={styles.verificationNote}>Préparation de votre espace…</Text></View> : null}
      <View style={styles.privacyRow}><AppIcon name="check" size={18} color={colors.leaf} /><Text style={styles.privacyText}>Vos demandes sont vérifiées par l’équipe avant le partage des coordonnées des producteurs.</Text></View>
      <AppUpdateCard {...appUpdate} />
    </PageFrame>
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
  switchMode: { paddingVertical: 3, alignItems: 'center' },
  switchText: { color: colors.muted, fontSize: 12, textAlign: 'center' },
  switchLink: { color: colors.forest, fontWeight: '900' },
  verificationNote: { color: colors.muted, fontSize: 11, lineHeight: 17, textAlign: 'center' },
  loadingCard: { padding: 20, backgroundColor: colors.white, borderRadius: 16 },
  privacyRow: { flexDirection: 'row', alignItems: 'flex-start', gap: 9, paddingHorizontal: 2 },
  privacyText: { flex: 1, color: colors.muted, fontSize: 11, lineHeight: 17 },
});
