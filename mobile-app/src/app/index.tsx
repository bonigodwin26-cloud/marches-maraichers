import { useFocusEffect, useRouter } from 'expo-router';
import { ImageBackground, Pressable, StyleSheet, Text, View } from 'react-native';
import { useCallback, useState } from 'react';

import { AppHeader, AppIcon, EmptyBlock, ErrorBlock, Eyebrow, LoadingBlock, Notice, PageFrame, PageTitle, SectionTitle } from '@/components/common';
import { PriceCard } from '@/components/price-card';
import { AppUpdateCard } from '@/components/app-update-card';
import { useAppUpdate } from '@/lib/app-updates';
import { colors } from '@/constants/palette';
import { api, ApiError } from '@/lib/api';
import type { HomeData } from '@/lib/types';

export default function HomeScreen() {
  const router = useRouter();
  const [data, setData] = useState<HomeData | null>(null);
  const [busy, setBusy] = useState(true);
  const [error, setError] = useState('');
  const appUpdate = useAppUpdate();

  const load = useCallback(async () => {
    setBusy(true);
    setError('');
    try {
      const response = await api<{ data: HomeData }>('/home');
      setData(response.data);
    } catch (cause) {
      setError(cause instanceof ApiError ? cause.message : 'Impossible de charger les informations du marché.');
    } finally {
      setBusy(false);
    }
  }, []);

  useFocusEffect(useCallback(() => { void load(); }, [load]));

  return (
    <PageFrame refreshing={busy && !!data} onRefresh={load}>
      <AppHeader />
      <View>
        <Eyebrow>INFORMATION DE MARCHÉ · BÉNIN</Eyebrow>
        <PageTitle title="Les prix du jour, près de vous." subtitle="Des repères utiles pour comparer les marchés et mieux décider." />
      </View>
      <View style={styles.zonePill}><AppIcon name="location" size={15} color={colors.forest} /><Text style={styles.zoneText}>Zone pilote</Text></View>
      <ImageBackground source={require('@/assets/images/hero-market.jpg')} imageStyle={styles.heroImage} style={styles.hero}>
        <View style={styles.heroShade} />
        <View style={styles.heroCopy}>
          <Text style={styles.heroEyebrow}>REPÈRES COLLECTÉS</Text>
          <Text style={styles.heroTitle}>Comprendre le marché commence par un bon repère.</Text>
          <Text style={styles.heroDescription}>Consultez les observations validées par l’équipe de collecte.</Text>
          <Pressable onPress={() => router.navigate('/prices')} style={styles.heroLink}><Text style={styles.heroLinkText}>Voir les prix</Text><AppIcon name="arrow" size={16} color={colors.lime} /></Pressable>
        </View>
      </ImageBackground>
      {appUpdate.release ? <AppUpdateCard {...appUpdate} compact /> : null}
      {error ? <ErrorBlock message={error} onRetry={load} /> : null}
      {data?.demo_mode ? <Notice tone="warning">Mode démonstration : les valeurs signalées sont des exemples et ne remplacent pas les relevés du terrain.</Notice> : null}
      <View style={styles.statsRow}>
        <Stat value={data?.published_count} label="relevés publiés" icon="prices" />
        <Stat value={data?.market_count} label="marchés" icon="markets" />
        <Stat value={data?.product_count} label="produits suivis" icon="leaf" />
      </View>
      <SectionTitle action="Tout voir" onAction={() => router.navigate('/prices')}>Exemples de relevés</SectionTitle>
      {busy && !data ? <LoadingBlock label="Chargement des derniers relevés…" /> : null}
      {data && data.latest_prices.length > 0 ? data.latest_prices.map((item) => <PriceCard key={item.id} item={item} onPress={() => router.navigate('/prices')} />) : null}
      {data && data.latest_prices.length === 0 ? <EmptyBlock title="Les relevés arrivent bientôt" text="Les prix validés apparaîtront ici dès le début des collectes." /> : null}
      <Notice>Un prix observé donne un repère. Comparez toujours le produit, l’unité, le marché et la date.</Notice>
    </PageFrame>
  );
}

function Stat({ value, label, icon }: { value?: number; label: string; icon: 'prices' | 'markets' | 'leaf' }) {
  return (
    <View style={styles.statCard}>
      <View style={styles.statIcon}><AppIcon name={icon} size={17} color={colors.forest} /></View>
      <Text style={styles.statValue}>{value ?? '—'}</Text>
      <Text style={styles.statLabel}>{label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  zonePill: { alignSelf: 'flex-start', flexDirection: 'row', gap: 6, alignItems: 'center', paddingHorizontal: 11, paddingVertical: 7, borderRadius: 20, backgroundColor: colors.pale },
  zoneText: { color: colors.forest, fontWeight: '700', fontSize: 11 },
  hero: { minHeight: 210, justifyContent: 'flex-end', overflow: 'hidden', borderRadius: 20, backgroundColor: colors.forest },
  heroImage: { borderRadius: 20 },
  heroShade: { ...StyleSheet.absoluteFill, backgroundColor: 'rgba(2, 45, 32, 0.68)' },
  heroCopy: { padding: 18, gap: 8 },
  heroEyebrow: { color: colors.lime, fontSize: 9, fontWeight: '900', letterSpacing: 1 },
  heroTitle: { color: colors.white, fontWeight: '900', fontSize: 23, lineHeight: 27, maxWidth: 300 },
  heroDescription: { color: '#E8F3EC', fontSize: 12, lineHeight: 17, maxWidth: 300 },
  heroLink: { flexDirection: 'row', alignItems: 'center', gap: 7, marginTop: 3, alignSelf: 'flex-start' },
  heroLinkText: { color: colors.lime, fontSize: 12, fontWeight: '900' },
  statsRow: { flexDirection: 'row', gap: 8 },
  statCard: { flex: 1, minWidth: 0, padding: 11, borderRadius: 15, backgroundColor: colors.white, borderWidth: 1, borderColor: colors.line },
  statIcon: { width: 29, height: 29, alignItems: 'center', justifyContent: 'center', borderRadius: 15, backgroundColor: colors.pale, marginBottom: 8 },
  statValue: { color: colors.ink, fontSize: 19, fontWeight: '900' },
  statLabel: { color: colors.muted, fontSize: 9, lineHeight: 13, marginTop: 2 },
});