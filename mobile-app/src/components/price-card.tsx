import React from 'react';
import { Image, ImageSourcePropType, Pressable, StyleSheet, Text, View } from 'react-native';

import { colors } from '@/constants/palette';
import { AppIcon } from '@/components/common';
import { formatDate, formatPrice, PriceObservation } from '@/lib/types';

const productImages: Record<string, ImageSourcePropType> = {
  tomate: require('@/assets/images/tomate.jpg'),
  piment: require('@/assets/images/piment.jpg'),
  oignon: require('@/assets/images/oignon.jpg'),
  gombo: require('@/assets/images/gombo.jpg'),
  aubergine: require('@/assets/images/aubergine-locale.jpg'),
  feuille: require('@/assets/images/legume-feuille.jpg'),
};

export function ProductImage({ name, size = 58 }: { name?: string | null; size?: number }) {
  const normalized = (name || '').toLocaleLowerCase('fr');
  const key = Object.keys(productImages).find((candidate) => normalized.includes(candidate));
  return (
    <View style={[styles.imageFrame, { width: size, height: size }]}>
      {key ? <Image source={productImages[key]} style={styles.image} /> : <AppIcon name="leaf" size={size * 0.4} color={colors.leaf} />}
    </View>
  );
}

export function PriceCard({ item, onPress }: { item: PriceObservation; onPress?: () => void }) {
  const content = (
    <View style={styles.card}>
      <ProductImage name={item.product?.name} />
      <View style={styles.details}>
        <Text style={styles.productName} numberOfLines={1}>{item.product?.name || 'Produit maraîcher'}</Text>
        <View style={styles.marketLine}><AppIcon name="location" size={13} color={colors.muted} /><Text style={styles.marketName} numberOfLines={1}>{item.market?.name || 'Marché'}</Text></View>
        <Text style={styles.date}>{formatDate(item.observed_at)}</Text>
      </View>
      <View style={styles.priceColumn}>
        <Text style={styles.price}>{formatPrice(item.price_xof)}</Text>
        <Text style={styles.unit}>par {item.unit_snapshot}</Text>
        {onPress ? <AppIcon name="arrow" size={15} color={colors.forest} /> : null}
      </View>
    </View>
  );

  if (!onPress) return content;
  return <Pressable accessibilityRole="button" onPress={onPress} style={({ pressed }) => pressed && styles.pressed}>{content}</Pressable>;
}

const styles = StyleSheet.create({
  card: { flexDirection: 'row', alignItems: 'center', gap: 11, minHeight: 88, padding: 11, backgroundColor: colors.white, borderWidth: 1, borderColor: colors.line, borderRadius: 16 },
  imageFrame: { borderRadius: 13, overflow: 'hidden', backgroundColor: colors.pale, alignItems: 'center', justifyContent: 'center' },
  image: { width: '100%', height: '100%' },
  details: { flex: 1, gap: 4, minWidth: 0 },
  productName: { color: colors.ink, fontWeight: '800', fontSize: 14 },
  marketLine: { flexDirection: 'row', alignItems: 'center', gap: 3 },
  marketName: { color: colors.muted, fontSize: 11, flexShrink: 1 },
  date: { color: colors.muted, fontSize: 10 },
  priceColumn: { alignItems: 'flex-end', gap: 3, maxWidth: 118 },
  price: { color: colors.forest, fontSize: 13, fontWeight: '900', textAlign: 'right' },
  unit: { color: colors.muted, fontSize: 10 },
  pressed: { opacity: 0.78 },
});