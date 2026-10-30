import { Tabs, TabList, TabSlot, TabTrigger } from 'expo-router/ui';
import { Pressable, StyleSheet, Text } from 'react-native';

import { colors } from '@/constants/palette';

const tabs = [
  ['index', '/', '⌂', 'Accueil'],
  ['prices', '/prices', '▥', 'Prix'],
  ['markets', '/markets', '⌖', 'Marchés'],
  ['requests', '/requests', '▤', 'Demandes'],
  ['account', '/account', '◉', 'Compte'],
] as const;

export default function AppTabs() {
  return (
    <Tabs>
      <TabSlot style={{ flex: 1 }} />
      <TabList style={styles.tabBar}>
        {tabs.map(([name, href, icon, label]) => (
          <TabTrigger key={name} name={name} href={href} asChild>
            <Pressable style={styles.tab}>
              <Text style={styles.tabIcon}>{icon}</Text>
              <Text style={styles.tabLabel}>{label}</Text>
            </Pressable>
          </TabTrigger>
        ))}
      </TabList>
    </Tabs>
  );
}

const styles = StyleSheet.create({
  tabBar: { position: 'absolute', bottom: 0, width: '100%', minHeight: 64, paddingHorizontal: 6, paddingTop: 7, paddingBottom: 8, flexDirection: 'row', justifyContent: 'space-around', backgroundColor: colors.white, borderTopWidth: 1, borderTopColor: colors.line },
  tab: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 3, minHeight: 45 },
  tabIcon: { color: colors.forest, fontSize: 18, lineHeight: 21 },
  tabLabel: { color: colors.muted, fontSize: 9, fontWeight: '700' },
});
