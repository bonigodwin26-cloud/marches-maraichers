import { useFocusEffect, useRouter } from 'expo-router';
import * as SecureStore from 'expo-secure-store';
import { useCallback, useEffect, useState } from 'react';
import { Alert, Pressable, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';

import { AppHeader, ErrorBlock, Notice, PageFrame, PageTitle, PrimaryButton } from '@/components/common';
import { colors } from '@/constants/palette';
import { api, ApiError } from '@/lib/api';
import { useSession } from '@/lib/session';
import type { Market, PageResult, Product } from '@/lib/types';

const QUEUE_KEY = 'marches-observation-queue';
type Draft = { product_id: number; market_id: number; price_xof: number; available_quantity?: number; observed_at: string; quality?: string; notes?: string };

function isDraft(value: unknown): value is Draft {
  if (!value || typeof value !== 'object') return false;
  const draft = value as Partial<Draft>;
  return Number.isInteger(draft.product_id) && Number.isInteger(draft.market_id)
    && Number.isInteger(draft.price_xof) && typeof draft.observed_at === 'string';
}

async function readQueue(): Promise<Draft[]> {
  try {
    const raw = await SecureStore.getItemAsync(QUEUE_KEY);
    if (!raw) return [];
    const value: unknown = JSON.parse(raw);
    return Array.isArray(value) ? value.filter(isDraft) : [];
  } catch {
    return [];
  }
}

async function writeQueue(queue: Draft[]): Promise<void> {
  if (queue.length) await SecureStore.setItemAsync(QUEUE_KEY, JSON.stringify(queue));
  else await SecureStore.deleteItemAsync(QUEUE_KEY);
}

function isPermanent(error: unknown): boolean {
  return error instanceof ApiError && error.status >= 400 && error.status < 500 && error.status !== 429;
}

export default function CollectScreen() {
  const router = useRouter();
  const { token, pendingVerification } = useSession();
  const [products, setProducts] = useState<Product[]>([]);
  const [markets, setMarkets] = useState<Market[]>([]);
  const [productId, setProductId] = useState('');
  const [marketId, setMarketId] = useState('');
  const [price, setPrice] = useState('');
  const [quantity, setQuantity] = useState('');
  const [quality, setQuality] = useState('');
  const [notes, setNotes] = useState('');
  const [queued, setQueued] = useState(0);
  const [error, setError] = useState('');
  const [syncError, setSyncError] = useState('');

  useEffect(() => {
    if (!token) return;
    let active = true;
    // L'API plafonne per_page à 50.
    void Promise.all([
      api<PageResult<Product>>('/products?per_page=50').then((result) => { if (active) setProducts(result.data); }),
      api<PageResult<Market>>('/markets?per_page=50').then((result) => { if (active) setMarkets(result.data); }),
    ]).catch((cause) => {
      if (active) setError(cause instanceof ApiError ? cause.message : 'Les référentiels sont indisponibles.');
    });
    return () => { active = false; };
  }, [token]);

  const syncQueue = useCallback(async () => {
    if (!token) return;
    const queue = await readQueue();
    if (!queue.length) {
      setQueued(0);
      return;
    }

    const remaining: Draft[] = [];
    let sent = 0;
    for (const draft of queue) {
      try {
        await api('/observations', { method: 'POST', token, body: draft });
        sent++;
      } catch (cause) {
        if (isPermanent(cause)) {
          setSyncError('Une collecte en attente a été refusée par le serveur et n’a pas pu être synchronisée.');
          continue;
        }
        remaining.push(draft);
        setSyncError('Synchronisation interrompue : connexion indisponible. Réessayez plus tard.');
        break;
      }
    }
    await writeQueue(remaining);
    setQueued(remaining.length);
    if (sent && !remaining.length) setSyncError('');
  }, [token]);

  useFocusEffect(useCallback(() => { void syncQueue(); }, [syncQueue]));

  useEffect(() => {
    if (!token) return;
    void readQueue().then((queue) => setQueued(queue.length));
  }, [token]);

  const save = async () => {
    setError('');
    if (!token || pendingVerification) {
      setError('Connectez-vous avec un compte vérifié pour envoyer une collecte.');
      router.navigate('/account');
      return;
    }
    const draft: Draft = {
      product_id: Number(productId),
      market_id: Number(marketId),
      price_xof: Number(price),
      ...(quantity ? { available_quantity: Number(quantity) } : {}),
      // Léger décalage pour éviter un rejet lié à l'horloge de l'appareil.
      observed_at: new Date(Date.now() - 60_000).toISOString(),
      ...(quality ? { quality } : {}),
      ...(notes ? { notes } : {}),
    };
    if (!draft.product_id || !draft.market_id || !draft.price_xof || draft.price_xof <= 0) {
      setError('Choisissez un produit, un marché et saisissez un prix.');
      return;
    }
    try {
      await api('/observations', { method: 'POST', token, body: draft });
      Alert.alert('Collecte envoyée', 'Elle sera vérifiée par l’équipe avant publication.');
    } catch (cause) {
      if (isPermanent(cause)) {
        setError(cause instanceof ApiError ? cause.message : 'Cette collecte a été refusée. Vérifiez les informations saisies.');
        return;
      }
      const queue = await readQueue();
      await writeQueue([...queue, draft]);
      setQueued(queue.length + 1);
      setError('Hors connexion : la collecte a été enregistrée sur cet appareil et sera synchronisée plus tard.');
    }
  };

  if (!token || pendingVerification) {
    return (
      <PageFrame>
        <AppHeader />
        <PageTitle title="Collecte terrain" subtitle="Saisissez un relevé, même sans réseau. Il sera synchronisé dès que possible." />
        <Notice tone="warning">Connectez-vous avec un compte acheteur vérifié pour accéder à la collecte.</Notice>
        <PrimaryButton label="Ouvrir mon compte" onPress={() => router.navigate('/account')} />
      </PageFrame>
    );
  }

  return (
    <PageFrame>
      <AppHeader />
      <PageTitle title="Collecte terrain" subtitle="Saisissez un relevé, même sans réseau. Il sera synchronisé dès que possible." />
      <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
        {error ? <ErrorBlock message={error} /> : null}
        {syncError ? <ErrorBlock message={syncError} /> : null}

        <Text style={styles.label}>Produit</Text>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.optionRow}>
          {products.length ? products.map((product) => (
            <Pressable key={product.id} onPress={() => setProductId(String(product.id))} style={[styles.option, String(product.id) === productId && styles.optionActive]}>
              <Text style={[styles.optionText, String(product.id) === productId && styles.optionTextActive]}>{product.name}</Text>
              {product.unit ? <Text style={styles.optionHint}>{product.unit}</Text> : null}
            </Pressable>
          )) : <Text style={styles.loadingText}>Chargement des produits…</Text>}
        </ScrollView>

        <Text style={styles.label}>Marché</Text>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.optionRow}>
          {markets.length ? markets.map((market) => (
            <Pressable key={market.id} onPress={() => setMarketId(String(market.id))} style={[styles.option, String(market.id) === marketId && styles.optionActive]}>
              <Text style={[styles.optionText, String(market.id) === marketId && styles.optionTextActive]}>{market.name}</Text>
              {market.commune ? <Text style={styles.optionHint}>{market.commune}</Text> : null}
            </Pressable>
          )) : <Text style={styles.loadingText}>Chargement des marchés…</Text>}
        </ScrollView>

        <Text style={styles.label}>Prix (FCFA)</Text>
        <TextInput value={price} onChangeText={setPrice} keyboardType="number-pad" placeholder="Ex. 450" style={styles.input} />
        <Text style={styles.label}>Quantité disponible (facultatif)</Text>
        <TextInput value={quantity} onChangeText={setQuantity} keyboardType="decimal-pad" style={styles.input} />
        <Text style={styles.label}>Qualité</Text>
        <TextInput value={quality} onChangeText={setQuality} placeholder="Bonne, standard…" style={styles.input} />
        <Text style={styles.label}>Notes</Text>
        <TextInput value={notes} onChangeText={setNotes} multiline style={[styles.input, styles.notes]} />
        <PrimaryButton label="Enregistrer la collecte" onPress={() => void save()} />
        <View style={styles.queueRow}>
          <Text style={styles.queue}>{queued ? `${queued} collecte(s) en attente sur cet appareil.` : 'Aucune collecte en attente.'}</Text>
          {queued ? <PrimaryButton label="Synchroniser maintenant" onPress={() => void syncQueue()} secondary /> : null}
        </View>
      </ScrollView>
    </PageFrame>
  );
}

const styles = StyleSheet.create({
  content: { gap: 8, paddingBottom: 40 },
  label: { color: colors.ink, fontSize: 13, fontWeight: '700', marginTop: 8 },
  optionRow: { gap: 8, minHeight: 58, alignItems: 'center' },
  option: { paddingHorizontal: 13, paddingVertical: 10, borderRadius: 13, borderWidth: 1, borderColor: colors.line, backgroundColor: colors.white },
  optionActive: { borderColor: colors.forest, backgroundColor: colors.pale },
  optionText: { color: colors.ink, fontSize: 13, fontWeight: '700' },
  optionTextActive: { color: colors.forest },
  optionHint: { color: colors.muted, fontSize: 10, marginTop: 2 },
  loadingText: { color: colors.muted, fontSize: 12 },
  input: { backgroundColor: colors.white, borderColor: colors.line, borderRadius: 12, borderWidth: 1, color: colors.ink, minHeight: 46, paddingHorizontal: 14 },
  notes: { minHeight: 90, paddingTop: 12, textAlignVertical: 'top' },
  queueRow: { gap: 8, marginTop: 10 },
  queue: { color: colors.muted, fontSize: 13, textAlign: 'center' },
});
