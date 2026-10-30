import Constants from 'expo-constants';
import * as Linking from 'expo-linking';
import { AppState } from 'react-native';
import { useCallback, useEffect, useState } from 'react';

import { api, getApiBaseUrl } from '@/lib/api';

export type AppRelease = {
  version_code: number;
  version_name: string;
  release_notes: string;
  sha256: string;
  mandatory: boolean;
};

export type UpdateState = 'checking' | 'up_to_date' | 'available' | 'unavailable';

function getInstalledVersionCode(): number {
  const configuredCode = Constants.expoConfig?.android?.versionCode;
  const manifestCode = Constants.androidManifest?.versionCode;
  const code = Number(configuredCode ?? manifestCode ?? 1);

  return Number.isInteger(code) && code > 0 ? code : 1;
}

export function getInstalledVersionName(): string {
  return Constants.expoConfig?.version ?? '1.0.0';
}

export function useAppUpdate() {
  const [release, setRelease] = useState<AppRelease | null>(null);
  const [state, setState] = useState<UpdateState>('checking');
  const [downloadError, setDownloadError] = useState('');

  const checkForUpdates = useCallback(async () => {
    setState('checking');
    setDownloadError('');

    try {
      const latest = await api<AppRelease>('/app-update');
      const isValidRelease = Number.isInteger(latest.version_code)
        && latest.version_code > 0
        && typeof latest.version_name === 'string';

      if (!isValidRelease) {
        throw new Error('Invalid update manifest.');
      }

      if (latest.version_code > getInstalledVersionCode()) {
        setRelease(latest);
        setState('available');
      } else {
        setRelease(null);
        setState('up_to_date');
      }
    } catch {
      setRelease(null);
      setState('unavailable');
    }
  }, []);

  useEffect(() => {
    const initialCheck = setTimeout(() => void checkForUpdates(), 0);
    const subscription = AppState.addEventListener('change', (appState) => {
      if (appState === 'active') void checkForUpdates();
    });

    return () => {
      clearTimeout(initialCheck);
      subscription.remove();
    };
  }, [checkForUpdates]);

  const downloadUpdate = useCallback(async () => {
    setDownloadError('');

    try {
      await Linking.openURL(`${getApiBaseUrl()}/app-update/download`);
    } catch {
      setDownloadError('Le téléchargement ne s’est pas ouvert. Vérifiez que le serveur est accessible sur le même réseau Wi-Fi.');
    }
  }, []);

  return { release, state, downloadError, checkForUpdates, downloadUpdate };
}