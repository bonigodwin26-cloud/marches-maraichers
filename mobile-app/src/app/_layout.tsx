import * as SplashScreen from 'expo-splash-screen';
import { StatusBar } from 'expo-status-bar';
import { useEffect } from 'react';

import AppTabs from '@/components/app-tabs';
import { SessionProvider, useSession } from '@/lib/session';

void SplashScreen.preventAutoHideAsync();

function AppNavigator() {
  const { ready } = useSession();

  useEffect(() => {
    if (ready) void SplashScreen.hideAsync();
  }, [ready]);

  if (!ready) return null;
  return <><StatusBar style="dark" /><AppTabs /></>;
}

export default function RootLayout() {
  return <SessionProvider><AppNavigator /></SessionProvider>;
}