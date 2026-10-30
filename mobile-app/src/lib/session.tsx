import * as SecureStore from 'expo-secure-store';
import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { Platform } from 'react-native';

import { api, ApiError } from './api';
import type { SessionUser } from './types';

const TOKEN_KEY = 'marches-mobile-token';
const USER_KEY = 'marches-mobile-user';

interface SessionContextValue {
  token: string | null;
  user: SessionUser | null;
  ready: boolean;
  signIn: (email: string, password: string) => Promise<void>;
  signUp: (name: string, email: string, password: string, passwordConfirmation: string) => Promise<string>;
  signOut: () => Promise<void>;
}

const SessionContext = createContext<SessionContextValue | null>(null);

async function readValue(key: string): Promise<string | null> {
  if (Platform.OS === 'web') return typeof localStorage === 'undefined' ? null : localStorage.getItem(key);
  return SecureStore.getItemAsync(key);
}

async function writeValue(key: string, value: string | null): Promise<void> {
  if (Platform.OS === 'web') {
    if (typeof localStorage === 'undefined') return;
    if (value === null) localStorage.removeItem(key);
    else localStorage.setItem(key, value);
    return;
  }

  if (value === null) await SecureStore.deleteItemAsync(key);
  else await SecureStore.setItemAsync(key, value);
}

export function SessionProvider({ children }: React.PropsWithChildren) {
  const [token, setToken] = useState<string | null>(null);
  const [user, setUser] = useState<SessionUser | null>(null);
  const [ready, setReady] = useState(false);

  useEffect(() => {
    let active = true;
    const restore = async () => {
      const [storedToken, storedUser] = await Promise.all([readValue(TOKEN_KEY), readValue(USER_KEY)]);
      if (!active) return;
      if (!storedToken) {
        setReady(true);
        return;
      }

      setToken(storedToken);
      if (storedUser) {
        try { setUser(JSON.parse(storedUser) as SessionUser); } catch { setUser(null); }
      }

      try {
        const response = await api<{ data: { user: SessionUser } }>('/me', { token: storedToken });
        if (!active) return;
        setUser(response.data.user);
        await writeValue(USER_KEY, JSON.stringify(response.data.user));
      } catch (error) {
        if (error instanceof ApiError && (error.status === 401 || error.status === 403)) {
          await Promise.all([writeValue(TOKEN_KEY, null), writeValue(USER_KEY, null)]);
          if (active) {
            setToken(null);
            setUser(null);
          }
        }
      } finally {
        if (active) setReady(true);
      }
    };
    void restore();
    return () => { active = false; };
  }, []);

  const signIn = useCallback(async (email: string, password: string) => {
    const response = await api<{ data: { token: string; user: SessionUser } }>('/login', {
      method: 'POST',
      body: { email, password },
    });
    await Promise.all([
      writeValue(TOKEN_KEY, response.data.token),
      writeValue(USER_KEY, JSON.stringify(response.data.user)),
    ]);
    setToken(response.data.token);
    setUser(response.data.user);
  }, []);

  const signUp = useCallback(async (name: string, email: string, password: string, passwordConfirmation: string) => {
    const response = await api<{ message: string }>('/register', {
      method: 'POST',
      body: { name, email, password, password_confirmation: passwordConfirmation },
    });
    return response.message;
  }, []);

  const signOut = useCallback(async () => {
    if (token) {
      try { await api('/logout', { method: 'POST', token }); } catch { /* Local sign-out still works offline. */ }
    }
    await Promise.all([writeValue(TOKEN_KEY, null), writeValue(USER_KEY, null)]);
    setToken(null);
    setUser(null);
  }, [token]);

  const value = useMemo(() => ({ token, user, ready, signIn, signUp, signOut }), [token, user, ready, signIn, signUp, signOut]);
  return <SessionContext.Provider value={value}>{children}</SessionContext.Provider>;
}

export function useSession(): SessionContextValue {
  const value = useContext(SessionContext);
  if (!value) throw new Error('useSession must be used inside SessionProvider');
  return value;
}