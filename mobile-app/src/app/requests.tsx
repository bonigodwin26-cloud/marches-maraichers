import { useFocusEffect, useRouter } from 'expo-router';
import { useCallback, useState } from 'react';
import { Alert, Linking, Pressable, StyleSheet, Text, View } from 'react-native';

import { AppHeader, AppIcon, EmptyBlock, ErrorBlock, LoadingBlock, Notice, PageFrame, PageTitle, PrimaryButton } from '@/components/common';
import { colors } from '@/constants/palette';
import { api, ApiError } from '@/lib/api';
import { useSession } from '@/lib/session';
import type { ContactRequest, PageResult } from '@/lib/types';

export default function RequestsScreen() {
  const router = useRouter();
  const { token, user } = useSession();
  const [items, setItems] = useState<ContactRequest[]>([]);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    if (!token) { setItems([]); return; }
    setBusy(true);
    setError('');
    try {
      const response = await api<PageResult<ContactRequest>>('/requests', { token });
      setItems(response.data);
    } catch (cause) {
      setError(cause instanceof ApiError ? cause.message : 'Vos demandes ne peuvent pas être chargées.');
    } finally {
      setBusy(false);
    }
  }, [token]);

  useFocusEffect(useCallback(() => { void load(); }, [load]));

  const declare = (item: ContactRequest, happened: boolean) => {
    Alert.alert('Confirmer la déclaration', happened ? 'La transaction a-t-elle bien eu lieu ?' : 'Confirmez-vous que la transaction n’a pas eu lieu ?', [
      { text: 'Annuler', style: 'cancel' },
      { text: 'Confirmer', onPress: () => { void api('/requests/' + item.id + '/transaction', { method: 'PATCH', token, body: { transaction_happened: happened } }).then(() => load()).catch((cause) => setError(cause instanceof ApiError ? cause.message : 'La déclaration n’a pas pu être enregistrée.')); } },
    ]);
  };

  return (
    <PageFrame refreshing={busy && items.length > 0} onRefresh={load}>
      <AppHeader />
      <View><Text style={styles.eyebrow}>SUIVI ACHETEUR · BÉNIN</Text><PageTitle title="Mes demandes" subtitle="Suivez les mises en relation avec les producteurs." /></View>
      {!user ? (
        <View style={styles.accessCard}><View style={styles.accessIcon}><AppIcon name="messages" size={23} color={colors.forest} /></View><Text style={styles.accessTitle}>Connectez-vous pour continuer</Text><Text style={styles.accessText}>Votre historique et les décisions de l’équipe sont accessibles depuis votre compte acheteur vérifié.</Text><PrimaryButton label="Ouvrir mon compte" onPress={() => router.navigate('/account')} /></View>
      ) : null}
      {user && error ? <ErrorBlock message={error} onRetry={load} /> : null}
      {user && busy && items.length === 0 ? <LoadingBlock label="Chargement de vos demandes…" /> : null}
      {user && !busy && !error && items.length === 0 ? <EmptyBlock title="Aucune demande pour le moment" text="Ouvrez un relevé de prix et demandez une mise en relation pour retrouver son suivi ici." /> : null}
      {user && items.map((item) => <RequestCard key={item.id} item={item} onDeclare={(happened) => declare(item, happened)} />)}
      {user ? <Notice tone="warning">Les coordonnées du producteur sont visibles uniquement après approbation de votre demande.</Notice> : null}
    </PageFrame>
  );
}

function RequestCard({ item, onDeclare }: { item: ContactRequest; onDeclare: (happened: boolean) => void }) {
  const approved = item.status === 'approved';
  const rejected = item.status === 'rejected';
  const statusLabel = approved ? 'Approuvée' : rejected ? 'Refusée' : 'En cours de vérification';

  return (
    <View style={styles.requestCard}>
      <View style={styles.requestHeader}><View style={styles.requestIcon}><AppIcon name="messages" size={19} color={colors.forest} /></View><View style={[styles.statusPill, approved && styles.statusApproved, rejected && styles.statusRejected]}><Text style={styles.statusText}>{statusLabel}</Text></View></View>
      <Text style={styles.requestProduct}>{item.product || 'Produit maraîcher'}</Text>
      <Text style={styles.requestMarket}>{item.market || 'Marché non précisé'}{item.requested_volume ? ' · ' + item.requested_volume : ''}</Text>
      {item.created_at ? <Text style={styles.requestDate}>Envoyée le {new Date(item.created_at).toLocaleDateString('fr-FR')}</Text> : null}
      {item.producer ? <View style={styles.producerBox}><Text style={styles.producerTitle}>Contact producteur</Text><Text style={styles.producerName}>{item.producer.name}</Text><Pressable onPress={() => void Linking.openURL('tel:' + item.producer?.phone)}><Text style={styles.producerPhone}>{item.producer.phone}</Text></Pressable></View> : null}
      {approved && !item.transaction_declared_at ? <View style={styles.declareBox}><Text style={styles.declareText}>Déclarez le résultat de cette mise en relation pour pouvoir envoyer une nouvelle demande.</Text><View style={styles.declareButtons}><Pressable onPress={() => onDeclare(true)} style={styles.declarePrimary}><Text style={styles.declarePrimaryText}>La transaction a eu lieu</Text></Pressable><Pressable onPress={() => onDeclare(false)} style={styles.declareSecondary}><Text style={styles.declareSecondaryText}>Non réalisée</Text></Pressable></View></View> : null}
      {item.transaction_declared_at ? <Text style={styles.transactionNote}>{item.transaction_happened ? 'Transaction déclarée réalisée.' : 'Transaction déclarée non réalisée.'}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  eyebrow: { color: colors.leaf, fontSize: 10, fontWeight: '900', letterSpacing: 1.1, marginBottom: 7 },
  accessCard: { alignItems: 'center', gap: 12, padding: 20, backgroundColor: colors.white, borderWidth: 1, borderColor: colors.line, borderRadius: 18 },
  accessIcon: { width: 52, height: 52, borderRadius: 26, alignItems: 'center', justifyContent: 'center', backgroundColor: colors.pale },
  accessTitle: { color: colors.ink, fontSize: 17, fontWeight: '900', textAlign: 'center' },
  accessText: { color: colors.muted, fontSize: 12, lineHeight: 19, textAlign: 'center' },
  requestCard: { gap: 8, padding: 15, backgroundColor: colors.white, borderWidth: 1, borderColor: colors.line, borderRadius: 17 },
  requestHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  requestIcon: { width: 40, height: 40, alignItems: 'center', justifyContent: 'center', borderRadius: 13, backgroundColor: colors.pale },
  statusPill: { borderRadius: 20, backgroundColor: colors.amberBg, paddingHorizontal: 10, paddingVertical: 6 },
  statusApproved: { backgroundColor: '#E8F5E7' },
  statusRejected: { backgroundColor: '#FCEBE8' },
  statusText: { color: colors.ink, fontSize: 10, fontWeight: '800' },
  requestProduct: { color: colors.ink, fontSize: 16, fontWeight: '900' },
  requestMarket: { color: colors.muted, fontSize: 12 },
  requestDate: { color: colors.muted, fontSize: 10 },
  producerBox: { backgroundColor: colors.pale, borderRadius: 13, padding: 12, gap: 4, marginTop: 4 },
  producerTitle: { color: colors.forest, fontSize: 10, fontWeight: '900' },
  producerName: { color: colors.ink, fontSize: 13, fontWeight: '800' },
  producerPhone: { color: colors.forest, fontSize: 13, fontWeight: '800', textDecorationLine: 'underline' },
  declareBox: { backgroundColor: colors.amberBg, borderRadius: 12, padding: 11, gap: 9, marginTop: 4 },
  declareText: { color: '#785717', fontSize: 11, lineHeight: 16 },
  declareButtons: { gap: 7 },
  declarePrimary: { minHeight: 39, alignItems: 'center', justifyContent: 'center', borderRadius: 11, backgroundColor: colors.forest, paddingHorizontal: 10 },
  declarePrimaryText: { color: colors.white, fontSize: 11, fontWeight: '800' },
  declareSecondary: { minHeight: 37, alignItems: 'center', justifyContent: 'center', borderRadius: 11, borderWidth: 1, borderColor: '#E2D4A7', backgroundColor: colors.white },
  declareSecondaryText: { color: colors.ink, fontSize: 11, fontWeight: '800' },
  transactionNote: { color: colors.forest, fontSize: 11, fontWeight: '700', marginTop: 4 },
});