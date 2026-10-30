import { useFocusEffect, useRouter } from 'expo-router';
import { useCallback, useState } from 'react';
import { Alert, KeyboardAvoidingView, Modal, Platform, Pressable, ScrollView, StyleSheet, Switch, Text, TextInput, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { AppHeader, AppIcon, EmptyBlock, ErrorBlock, Field, LoadingBlock, Notice, PageFrame, PageTitle, PrimaryButton, SectionTitle } from '@/components/common';
import { PriceCard } from '@/components/price-card';
import { colors } from '@/constants/palette';
import { api, ApiError, buildQuery } from '@/lib/api';
import { useSession } from '@/lib/session';
import type { Market, PageResult, PriceObservation, Product } from '@/lib/types';

export default function PricesScreen() {
  const [items, setItems] = useState<PriceObservation[]>([]);
  const [products, setProducts] = useState<Product[]>([]);
  const [markets, setMarkets] = useState<Market[]>([]);
  const [query, setQuery] = useState('');
  const [appliedQuery, setAppliedQuery] = useState('');
  const [productId, setProductId] = useState('');
  const [marketId, setMarketId] = useState('');
  const [openFilter, setOpenFilter] = useState<'product' | 'market' | null>(null);
  const [selected, setSelected] = useState<PriceObservation | null>(null);
  const [busy, setBusy] = useState(true);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    setBusy(true);
    setError('');
    try {
      const [priceResult, productResult, marketResult] = await Promise.all([
        api<PageResult<PriceObservation>>('/prices' + buildQuery({ q: appliedQuery, product: productId, market: marketId, per_page: 30 })),
        api<PageResult<Product>>('/products?per_page=50'),
        api<PageResult<Market>>('/markets?per_page=50'),
      ]);
      setItems(priceResult.data);
      setProducts(productResult.data);
      setMarkets(marketResult.data);
    } catch (cause) {
      setError(cause instanceof ApiError ? cause.message : 'Les relevés sont momentanément indisponibles.');
    } finally {
      setBusy(false);
    }
  }, [appliedQuery, productId, marketId]);

  useFocusEffect(useCallback(() => { void load(); }, [load]));

  const selectedProduct = products.find((product) => String(product.id) === productId);
  const selectedMarket = markets.find((market) => String(market.id) === marketId);
  const productOptions: FilterOption[] = [
    { value: '', label: 'Tous les produits', description: 'Afficher les prix de tous les produits' },
    ...products.map((product) => ({
      value: String(product.id),
      label: product.name,
      description: [product.category, product.unit].filter(Boolean).join(' · ') || undefined,
    })),
  ];
  const marketOptions: FilterOption[] = [
    { value: '', label: 'Tous les marchés', description: 'Afficher les prix de tous les marchés' },
    ...markets.map((market) => ({
      value: String(market.id),
      label: market.name,
      description: [market.commune, market.department].filter(Boolean).join(' · ') || undefined,
    })),
  ];

  return (
    <>
      <PageFrame refreshing={busy && items.length > 0} onRefresh={load}>
        <AppHeader />
        <View><Text style={styles.eyebrow}>SUIVI DES MARCHÉS · BÉNIN</Text><PageTitle title="Prix du jour" subtitle="Comparez les prix publiés par produit et par marché." /></View>
        <View style={styles.searchPanel}>
          <View style={styles.searchRow}><AppIcon name="search" size={18} color={colors.forest} /><TextInput value={query} onChangeText={setQuery} onSubmitEditing={() => setAppliedQuery(query.trim())} returnKeyType="search" placeholder="Produit ou marché…" placeholderTextColor="#8A978E" style={styles.searchInput} /></View>
          <Pressable onPress={() => setAppliedQuery(query.trim())} style={({ pressed }) => [styles.searchButton, pressed && styles.pressed]}><Text style={styles.searchButtonText}>Afficher les prix</Text></Pressable>
        </View>
        <View style={styles.filters}>
          <Text style={styles.filterLabel}>FILTRER PAR</Text>
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipRow}>
            <FilterChip label="Tous" active={!productId && !marketId} onPress={() => { setProductId(''); setMarketId(''); setQuery(''); setAppliedQuery(''); }} />
            <DropdownFilterButton label={selectedProduct?.name ?? 'Produit'} active={!!productId} onPress={() => setOpenFilter('product')} />
            <DropdownFilterButton label={selectedMarket?.name ?? 'Marché'} active={!!marketId} onPress={() => setOpenFilter('market')} />
          </ScrollView>
        </View>
        {error ? <ErrorBlock message={error} onRetry={load} /> : null}
        {busy && items.length === 0 ? <LoadingBlock label="Chargement des relevés…" /> : null}
        {!busy && !error && items.length === 0 ? <EmptyBlock title="Aucun relevé pour cette recherche" text="Les nouveaux prix publiés apparaîtront ici. Modifiez vos filtres ou revenez plus tard." /> : null}
        {items.length > 0 ? <><SectionTitle>{items.length} prix publiés</SectionTitle>{items.map((item) => <PriceCard key={item.id} item={item} onPress={() => setSelected(item)} />)}</> : null}
        <Notice>Les exemples du mode démonstration sont signalés. Un prix dépend du produit, de l’unité, du marché et de la date.</Notice>
      </PageFrame>
      <PriceDetails key={selected?.id ?? 'closed'} item={selected} onClose={() => setSelected(null)} />
      <FilterPicker visible={openFilter === 'product'} title="Choisir un produit" selectedValue={productId} options={productOptions} onSelect={(value) => { setProductId(value); setOpenFilter(null); }} onClose={() => setOpenFilter(null)} />
      <FilterPicker visible={openFilter === 'market'} title="Choisir un marché" selectedValue={marketId} options={marketOptions} onSelect={(value) => { setMarketId(value); setOpenFilter(null); }} onClose={() => setOpenFilter(null)} />
    </>
  );
}

function FilterChip({ label, active, onPress }: { label: string; active: boolean; onPress: () => void }) {
  return <Pressable onPress={onPress} style={[styles.chip, active && styles.chipActive]}><Text style={[styles.chipText, active && styles.chipTextActive]}>{label}</Text></Pressable>;
}

type FilterOption = { value: string; label: string; description?: string };

function DropdownFilterButton({ label, active, onPress }: { label: string; active: boolean; onPress: () => void }) {
  return (
    <Pressable accessibilityRole="button" accessibilityLabel={`Filtrer par ${label}`} onPress={onPress} style={({ pressed }) => [styles.dropdownFilter, active && styles.dropdownFilterActive, pressed && styles.pressed]}>
      <Text numberOfLines={1} style={[styles.dropdownFilterText, active && styles.dropdownFilterTextActive]}>{label}</Text>
      <Text style={[styles.dropdownCaret, active && styles.dropdownFilterTextActive]}>⌄</Text>
    </Pressable>
  );
}

function FilterPicker({ visible, title, selectedValue, options, onSelect, onClose }: { visible: boolean; title: string; selectedValue: string; options: FilterOption[]; onSelect: (value: string) => void; onClose: () => void }) {
  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onClose}>
      <View style={styles.filterOverlay}>
        <Pressable accessibilityRole="button" accessibilityLabel="Fermer la liste" onPress={onClose} style={StyleSheet.absoluteFill} />
        <View style={styles.filterMenu}>
          <View style={styles.filterMenuHeader}>
            <View style={styles.filterMenuHeading}>
              <Text style={styles.filterMenuTitle}>{title}</Text>
              <Text style={styles.filterMenuSubtitle}>{options.length - 1} choix disponibles</Text>
            </View>
            <Pressable accessibilityRole="button" accessibilityLabel="Fermer" onPress={onClose} style={styles.filterMenuClose}>
              <AppIcon name="close" size={18} color={colors.forest} />
            </Pressable>
          </View>
          <ScrollView showsVerticalScrollIndicator={false}>
            {options.map((option) => {
              const active = selectedValue === option.value;
              return (
                <Pressable key={option.value || 'all'} accessibilityRole="button" accessibilityState={{ selected: active }} onPress={() => onSelect(option.value)} style={({ pressed }) => [styles.filterOption, active && styles.filterOptionActive, pressed && styles.pressed]}>
                  <View style={styles.filterOptionCopy}>
                    <Text numberOfLines={1} style={[styles.filterOptionLabel, active && styles.filterOptionLabelActive]}>{option.label}</Text>
                    {option.description ? <Text numberOfLines={1} style={styles.filterOptionDescription}>{option.description}</Text> : null}
                  </View>
                  {active ? <AppIcon name="check" size={18} color={colors.forest} /> : null}
                </Pressable>
              );
            })}
          </ScrollView>
        </View>
      </View>
    </Modal>
  );
}

function PriceDetails({ item, onClose }: { item: PriceObservation | null; onClose: () => void }) {
  const { token, user } = useSession();
  const router = useRouter();
  const [phone, setPhone] = useState('');
  const [organization, setOrganization] = useState('');
  const [volume, setVolume] = useState('');
  const [message, setMessage] = useState('');
  const [consent, setConsent] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');

  const submit = async () => {
    if (!item || !token || !consent) return;
    setBusy(true);
    setError('');
    try {
      await api('/prices/' + item.id + '/contact', {
        method: 'POST',
        token,
        body: { phone, organization, requested_volume: volume, message, notification_consent: consent },
      });
      onClose();
      Alert.alert('Demande transmise', 'Votre demande a été envoyée à l’équipe pour validation.');
    } catch (cause) {
      setError(cause instanceof ApiError ? cause.message : 'Votre demande n’a pas pu être envoyée.');
    } finally {
      setBusy(false);
    }
  };

  return (
    <Modal visible={!!item} animationType="slide" presentationStyle="pageSheet" onRequestClose={onClose}>
      <SafeAreaView edges={['top']} style={styles.modalRoot}>
        <KeyboardAvoidingView style={styles.modalRoot} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
          <View style={styles.modalHeader}><Pressable onPress={onClose} style={styles.closeButton}><AppIcon name="close" size={20} color={colors.forest} /></Pressable><Text style={styles.modalTitle}>Détail du relevé</Text><View style={styles.closeSpacer} /></View>
          {item ? <ScrollView contentContainerStyle={styles.modalContent} keyboardShouldPersistTaps="handled">
            <View style={styles.detailCard}>
              <Text style={styles.eyebrow}>REPÈRE PUBLIÉ · BÉNIN</Text>
              <Text style={styles.detailProduct}>{item.product?.name || 'Produit maraîcher'}</Text>
              <Text style={styles.detailPrice}>{new Intl.NumberFormat('fr-FR').format(item.price_xof)} F CFA</Text>
              <Text style={styles.detailMeta}>par {item.unit_snapshot}</Text>
              <View style={styles.detailDivider} />
              <DetailRow icon="markets" label="Marché" value={item.market?.name || 'Non précisé'} />
              <DetailRow icon="location" label="Commune" value={item.market?.commune || item.market?.department || 'Non précisée'} />
              <DetailRow icon="calendar" label="Date du relevé" value={item.observed_at ? new Date(item.observed_at).toLocaleDateString('fr-FR') : 'Non précisée'} />
              {item.quality ? <DetailRow icon="check" label="Qualité" value={item.quality} /> : null}
            </View>
            <SectionTitle>Demander une mise en relation</SectionTitle>
            {!user ? <><Notice>Connectez-vous avec un compte acheteur vérifié pour envoyer une demande. Les coordonnées du producteur sont communiquées après validation.</Notice><PrimaryButton label="Se connecter" onPress={() => { onClose(); router.navigate('/account'); }} /></> : (
              <View style={styles.contactForm}>
                <Text style={styles.formIntro}>L’équipe vérifiera votre demande avant de transmettre les coordonnées du producteur.</Text>
                <Field label="Téléphone au format international" value={phone} onChangeText={setPhone} placeholder="+229 01 00 00 00 00" keyboardType="phone-pad" />
                <Field label="Organisation (facultatif)" value={organization} onChangeText={setOrganization} placeholder="Nom de votre structure" />
                <Field label="Volume souhaité (facultatif)" value={volume} onChangeText={setVolume} placeholder="Ex. 10 paniers" />
                <Field label="Message (facultatif)" value={message} onChangeText={setMessage} placeholder="Précisez votre besoin" multiline />
                <View style={styles.consentRow}><Switch value={consent} onValueChange={setConsent} trackColor={{ true: colors.leaf }} /><Text style={styles.consentText}>J’accepte de recevoir des notifications liées au traitement de cette demande.</Text></View>
                {error ? <Text style={styles.formError}>{error}</Text> : null}
                <PrimaryButton label={busy ? 'Envoi…' : 'Envoyer la demande'} onPress={submit} disabled={busy || !consent || !phone.trim()} />
              </View>
            )}
          </ScrollView> : null}
        </KeyboardAvoidingView>
      </SafeAreaView>
    </Modal>
  );
}

function DetailRow({ icon, label, value }: { icon: 'markets' | 'location' | 'calendar' | 'check'; label: string; value: string }) {
  return <View style={styles.detailRow}><AppIcon name={icon} size={16} color={colors.forest} /><View style={styles.detailRowText}><Text style={styles.detailLabel}>{label}</Text><Text style={styles.detailValue}>{value}</Text></View></View>;
}

const styles = StyleSheet.create({
  eyebrow: { color: colors.leaf, fontSize: 10, fontWeight: '900', letterSpacing: 1.1, marginBottom: 7 },
  searchPanel: { backgroundColor: colors.white, borderWidth: 1, borderColor: colors.line, borderRadius: 16, padding: 10, flexDirection: 'row', alignItems: 'center', gap: 8 },
  searchRow: { flex: 1, minWidth: 0, minHeight: 42, flexDirection: 'row', alignItems: 'center', gap: 8, paddingLeft: 5 },
  searchInput: { flex: 1, minWidth: 0, color: colors.ink, fontSize: 13, paddingVertical: 8 },
  searchButton: { minHeight: 42, paddingHorizontal: 13, borderRadius: 12, backgroundColor: colors.forest, alignItems: 'center', justifyContent: 'center' },
  searchButtonText: { color: colors.white, fontSize: 11, fontWeight: '800' },
  filters: { gap: 6 },
  dropdownFilter: { minHeight: 38, maxWidth: 170, flexDirection: 'row', alignItems: 'center', gap: 7, paddingHorizontal: 12, borderWidth: 1, borderColor: colors.line, borderRadius: 20, backgroundColor: colors.white },
  dropdownFilterActive: { backgroundColor: colors.pale, borderColor: colors.leaf },
  dropdownFilterText: { flexShrink: 1, color: colors.ink, fontSize: 11, fontWeight: '700' },
  dropdownFilterTextActive: { color: colors.forest, fontWeight: '900' },
  dropdownCaret: { color: colors.muted, fontSize: 14, fontWeight: '900', lineHeight: 17 },
  filterOverlay: { flex: 1, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 22, backgroundColor: 'rgba(5, 31, 22, 0.38)' },
  filterMenu: { width: '100%', maxWidth: 420, maxHeight: '78%', overflow: 'hidden', borderRadius: 20, backgroundColor: colors.white },
  filterMenuHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 12, paddingHorizontal: 17, paddingVertical: 14, borderBottomWidth: 1, borderBottomColor: colors.line },
  filterMenuHeading: { flex: 1, gap: 3 },
  filterMenuTitle: { color: colors.ink, fontSize: 16, fontWeight: '900' },
  filterMenuSubtitle: { color: colors.muted, fontSize: 11 },
  filterMenuClose: { width: 34, height: 34, alignItems: 'center', justifyContent: 'center', borderRadius: 17, backgroundColor: colors.pale },
  filterOption: { minHeight: 56, flexDirection: 'row', alignItems: 'center', gap: 10, paddingHorizontal: 17, paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: colors.line },
  filterOptionActive: { backgroundColor: colors.pale },
  filterOptionCopy: { flex: 1, gap: 3 },
  filterOptionLabel: { color: colors.ink, fontSize: 13, fontWeight: '700' },
  filterOptionLabelActive: { color: colors.forest, fontWeight: '900' },
  filterOptionDescription: { color: colors.muted, fontSize: 10 },
  filterLabel: { color: colors.muted, fontSize: 9, fontWeight: '900', letterSpacing: 1, marginBottom: 6 },
  chipRow: { gap: 6, paddingRight: 8 },
  chip: { borderWidth: 1, borderColor: colors.line, backgroundColor: colors.white, borderRadius: 20, paddingHorizontal: 11, paddingVertical: 8 },
  chipActive: { borderColor: colors.forest, backgroundColor: colors.forest },
  chipText: { color: colors.ink, fontSize: 11, fontWeight: '700' },
  chipTextActive: { color: colors.white },
  pressed: { opacity: 0.8 },
  modalRoot: { flex: 1, backgroundColor: colors.background },
  modalHeader: { minHeight: 52, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 16, borderBottomWidth: 1, borderBottomColor: colors.line, backgroundColor: colors.white },
  closeButton: { width: 36, height: 36, alignItems: 'center', justifyContent: 'center', borderRadius: 18, backgroundColor: colors.pale },
  modalTitle: { color: colors.ink, fontSize: 15, fontWeight: '900' },
  closeSpacer: { width: 36 },
  modalContent: { padding: 16, gap: 16, paddingBottom: 36 },
  detailCard: { backgroundColor: colors.white, borderRadius: 18, borderWidth: 1, borderColor: colors.line, padding: 18 },
  detailProduct: { color: colors.ink, fontSize: 26, fontWeight: '900' },
  detailPrice: { color: colors.forest, fontSize: 29, fontWeight: '900', marginTop: 10 },
  detailMeta: { color: colors.muted, fontSize: 12, marginTop: 2 },
  detailDivider: { height: 1, backgroundColor: colors.line, marginVertical: 15 },
  detailRow: { flexDirection: 'row', alignItems: 'center', gap: 10, paddingVertical: 9 },
  detailRowText: { flex: 1, gap: 2 },
  detailLabel: { color: colors.muted, fontSize: 10 },
  detailValue: { color: colors.ink, fontSize: 13, fontWeight: '700' },
  contactForm: { gap: 13, backgroundColor: colors.white, borderWidth: 1, borderColor: colors.line, borderRadius: 16, padding: 14 },
  formIntro: { color: colors.muted, fontSize: 12, lineHeight: 18 },
  consentRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  consentText: { flex: 1, color: colors.ink, fontSize: 11, lineHeight: 16 },
  formError: { color: colors.red, fontSize: 12, lineHeight: 17 },
});
