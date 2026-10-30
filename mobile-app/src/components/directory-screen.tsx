import { useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import { Pressable, StyleSheet, Text, TextInput, View } from 'react-native';

import { AppHeader, AppIcon, EmptyBlock, ErrorBlock, Eyebrow, LoadingBlock, PageFrame, PageTitle } from '@/components/common';
import { ProductImage } from '@/components/price-card';
import { colors } from '@/constants/palette';
import { api, ApiError, buildQuery } from '@/lib/api';
import type { Market, PageResult, Product } from '@/lib/types';

type DirectoryKind = 'markets' | 'products';

export function DirectoryScreen({ kind }: { kind: DirectoryKind }) {
  const isMarket = kind === 'markets';
  const [items, setItems] = useState<(Market | Product)[]>([]);
  const [query, setQuery] = useState('');
  const [submittedQuery, setSubmittedQuery] = useState('');
  const [busy, setBusy] = useState(true);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    setBusy(true);
    setError('');
    try {
      const result = await api<PageResult<Market | Product>>('/' + kind + buildQuery({ q: submittedQuery, per_page: 50 }));
      setItems(result.data);
    } catch (cause) {
      setError(cause instanceof ApiError ? cause.message : 'Le catalogue est momentanément indisponible.');
    } finally {
      setBusy(false);
    }
  }, [kind, submittedQuery]);

  useFocusEffect(useCallback(() => { void load(); }, [load]));

  return (
    <PageFrame refreshing={busy && items.length > 0} onRefresh={load}>
      <AppHeader />
      <View>
        <Eyebrow>{isMarket ? 'ANCRAGE LOCAL · BÉNIN' : 'CULTURES SUIVIES · BÉNIN'}</Eyebrow>
        <PageTitle title={isMarket ? 'Les marchés' : 'Les produits'} subtitle={isMarket ? 'Retrouvez les marchés maraîchers référencés et les relevés publiés.' : 'Parcourez les produits suivis par les équipes de collecte.'} />
      </View>
      <View style={styles.searchPanel}>
        <View style={styles.searchInputRow}><AppIcon name="search" size={19} color={colors.forest} /><TextInput value={query} onChangeText={setQuery} onSubmitEditing={() => setSubmittedQuery(query.trim())} returnKeyType="search" placeholder={isMarket ? 'Rechercher un marché, une commune…' : 'Rechercher un produit…'} placeholderTextColor="#8A978E" style={styles.searchInput} /></View>
        <Pressable accessibilityRole="button" onPress={() => setSubmittedQuery(query.trim())} style={({ pressed }) => [styles.searchButton, pressed && styles.pressed]}><Text style={styles.searchButtonText}>Rechercher</Text></Pressable>
      </View>
      {error ? <ErrorBlock message={error} onRetry={load} /> : null}
      {busy && items.length === 0 ? <LoadingBlock label={isMarket ? 'Chargement des marchés…' : 'Chargement des produits…'} /> : null}
      {items.length > 0 ? (
        <View style={styles.results}>
          <View style={styles.resultHeading}><Text style={styles.resultTitle}>{isMarket ? 'Marchés référencés' : 'Produits maraîchers'}</Text><Text style={styles.resultCount}>{items.length} résultat{items.length > 1 ? 's' : ''}</Text></View>
          {items.map((item) => isMarket ? <MarketCard key={item.id} item={item as Market} /> : <ProductCard key={item.id} item={item as Product} />)}
        </View>
      ) : null}
      {!busy && !error && items.length === 0 ? <EmptyBlock title={isMarket ? 'Aucun marché trouvé' : 'Aucun produit trouvé'} text="Essayez un autre mot ou revenez consulter le catalogue plus tard." /> : null}
    </PageFrame>
  );
}

function MarketCard({ item }: { item: Market }) {
  return (
    <View style={styles.card}>
      <View style={styles.cardIcon}><AppIcon name="markets" size={22} color={colors.forest} /></View>
      <View style={styles.cardCopy}>
        <Text style={styles.cardTitle}>{item.name}</Text>
        <Text style={styles.cardSubtitle}>{[item.commune, item.department].filter(Boolean).join(' · ') || 'Localisation à préciser'}</Text>
        <Text style={styles.cardMeta}>{item.published_observations_count || 0} relevé(s) publié(s)</Text>
      </View>
    </View>
  );
}

function ProductCard({ item }: { item: Product }) {
  return (
    <View style={styles.card}>
      <ProductImage name={item.name} />
      <View style={styles.cardCopy}>
        <Text style={styles.cardTitle}>{item.name}</Text>
        <Text style={styles.cardSubtitle}>{item.category || 'Produit maraîcher'}</Text>
        <Text style={styles.cardMeta}>{item.published_observations_count || 0} relevé(s) · unité {item.unit || 'à préciser'}</Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  searchPanel: { backgroundColor: colors.white, borderWidth: 1, borderColor: colors.line, borderRadius: 17, padding: 11, gap: 9 },
  searchInputRow: { minHeight: 44, flexDirection: 'row', alignItems: 'center', gap: 10, paddingHorizontal: 10 },
  searchInput: { flex: 1, color: colors.ink, fontSize: 13, minWidth: 0 },
  searchButton: { minHeight: 43, alignItems: 'center', justifyContent: 'center', borderRadius: 12, backgroundColor: colors.forest },
  searchButtonText: { color: colors.white, fontSize: 13, fontWeight: '800' },
  results: { gap: 10 },
  resultHeading: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'baseline', gap: 8 },
  resultTitle: { color: colors.ink, fontSize: 18, fontWeight: '900' },
  resultCount: { color: colors.muted, fontSize: 11 },
  card: { flexDirection: 'row', alignItems: 'center', gap: 12, minHeight: 86, backgroundColor: colors.white, borderWidth: 1, borderColor: colors.line, borderRadius: 16, padding: 12 },
  cardIcon: { width: 54, height: 54, borderRadius: 16, backgroundColor: colors.pale, alignItems: 'center', justifyContent: 'center' },
  cardCopy: { flex: 1, gap: 4, minWidth: 0 },
  cardTitle: { color: colors.ink, fontSize: 15, fontWeight: '900' },
  cardSubtitle: { color: colors.muted, fontSize: 12 },
  cardMeta: { color: colors.forest, fontSize: 10, fontWeight: '700', marginTop: 2 },
  pressed: { opacity: 0.8 },
});
