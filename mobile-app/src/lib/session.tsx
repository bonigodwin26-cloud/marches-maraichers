import * as SecureStore from 'expo-secure-store';
import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { Platform } from 'react-native';

import { api, ApiError } from './api';
import type { SessionUser } from './types';

const TOKEN_KEY = 'marches-mobile-token';
const USER_KEY = 'marches-mobile-user';
const VERIFICATION_KEY = 'marches-mobile-verification';

export interface PendingVerification {
  token: string;
  email: string;
}

/** Levée après une inscription ou une connexion dont l'e-mail n'est pas encore confirmé. */
export class EmailVerificationRequiredError extends Error {
  email: string;

  constructor(email: string) {
    super('Confirmez votre adresse e-mail avec le code reçu.');
    this.name = 'EmailVerificationRequiredError';
    this.email = email;
  }
}

interface SessionContextValue {
  token: string | null;
  user: SessionUser | null;
  ready: boolean;
  pendingVerification: PendingVerification | null;
  signIn: (email: string, password: string) => Promise<void>;
  signUp: (name: string, email: string, password: string, passwordConfirmation: string, privacyAccepted: boolean) => Promise<string>;
  verifyCode: (code: string) => Promise<void>;
  resendCode: () => Promise<string>;
  cancelVerification: () => Promise<void>;
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

function parseVerification(raw: string | null): PendingVerification | null {
  if (!raw) return null;
  try {
    const value = JSON.parse(raw) as Partial<PendingVerification>;
    return typeof value.token === 'string' && typeof value.email === 'string'
      ? { token: value.token, email: value.email }
      : null;
  } catch {
    return null;
  }
}

export function SessionProvider({ children }: React.PropsWithChildren) {
  const [token, setToken] = useState<string | null>(null);
  const [user, setUser] = useState<SessionUser | null>(null);
  const [pendingVerification, setPendingVerification] = useState<PendingVerification | null>(null);
  const [ready, setReady] = useState(false);

  useEffect(() => {
    let active = true;
    const restore = async () => {
      const [storedToken, storedUser, storedVerification] = await Promise.all([
        readValue(TOKEN_KEY),
        readValue(USER_KEY),
        readValue(VERIFICATION_KEY),
      ]);
      if (!active) return;

      const pending = parseVerification(storedVerification);
      if (pending) {
        await Promise.all([writeValue(TOKEN_KEY, null), writeValue(USER_KEY, null)]);
        setPendingVerification(pending);
        setReady(true);
        return;
      }

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

  const startVerification = useCallback(async (verificationToken: string, email: string) => {
    const pending = { token: verificationToken, email };
    await Promise.all([
      writeValue(VERIFICATION_KEY, JSON.stringify(pending)),
      writeValue(TOKEN_KEY, null),
      writeValue(USER_KEY, null),
    ]);
    setPendingVerification(pending);
    setToken(null);
    setUser(null);
  }, []);

  const signIn = useCallback(async (email: string, password: string) => {
    try {
      const response = await api<{ data: { token: string; user: SessionUser } }>('/login', {
        method: 'POST',
        body: { email, password },
      });
      await Promise.all([
        writeValue(TOKEN_KEY, response.data.token),
        writeValue(USER_KEY, JSON.stringify(response.data.user)),
        writeValue(VERIFICATION_KEY, null),
      ]);
      setPendingVerification(null);
      setToken(response.data.token);
      setUser(response.data.user);
    } catch (error) {
      if (error instanceof ApiError && error.code === 'email_not_verified') {
        const data = error.data as { verification_token?: string; email?: string } | undefined;
        if (data?.verification_token) {
          const verifiedEmail = data.email || email;
          await startVerification(data.verification_token, verifiedEmail);
          throw new EmailVerificationRequiredError(verifiedEmail);
        }
      }
      throw error;
    }
  }, [startVerification]);

  const signUp = useCallback(async (name: string, email: string, password: string, passwordConfirmation: string, privacyAccepted: boolean) => {
    const response = await api<{ message: string; data?: { verification_token?: string; email?: string } }>('/register', {
      method: 'POST',
      body: { name, email, password, password_confirmation: passwordConfirmation, privacy_accepted: privacyAccepted },
    });
    const verificationToken = response.data?.verification_token;
    if (!verificationToken) {
      throw new ApiError('Le serveur n’a pas retourné de jeton de vérification. Réessayez.', 201, 'verification_token_missing');
    }
    await startVerification(verificationToken, response.data?.email || email);
    return response.message;
  }, [startVerification]);

  const verifyCode = useCallback(async (code: string) => {
    if (!pendingVerification) throw new ApiError('Aucune vérification en cours.', 401, 'verification_missing');
    const response = await api<{ data: { token: string; user: SessionUser } }>('/email/verify-code', {
      method: 'POST',
      token: pendingVerification.token,
      body: { code },
    });
    await Promise.all([
      writeValue(TOKEN_KEY, response.data.token),
      writeValue(USER_KEY, JSON.stringify(response.data.user)),
      writeValue(VERIFICATION_KEY, null),
    ]);
    setPendingVerification(null);
    setToken(response.data.token);
    setUser(response.data.user);
  }, [pendingVerification]);

  const resendCode = useCallback(async () => {
    if (!pendingVerification) throw new ApiError('Aucune vérification en cours.', 401, 'verification_missing');
    const response = await api<{ message: string }>('/email/resend-code', {
      method: 'POST',
      token: pendingVerification.token,
    });
    return response.message;
  }, [pendingVerification]);

  const cancelVerification = useCallback(async () => {
    await writeValue(VERIFICATION_KEY, null);
    setPendingVerification(null);
  }, []);

  const signOut = useCallback(async () => {
    if (token) {
      try { await api('/logout', { method: 'POST', token }); } catch { /* Local sign-out still works offline. */ }
    }
    await Promise.all([
      writeValue(TOKEN_KEY, null),
      writeValue(USER_KEY, null),
      writeValue(VERIFICATION_KEY, null),
    ]);
    setPendingVerification(null);
    setToken(null);
    setUser(null);
  }, [token]);

  const value = useMemo(() => ({
    token, user, ready, pendingVerification, signIn, signUp, verifyCode, resendCode, cancelVerification, signOut,
  }), [token, user, ready, pendingVerification, signIn, signUp, verifyCode, resendCode, cancelVerification, signOut]);

  return <SessionContext.Provider value={value}>{children}</SessionContext.Provider>;
}

export function useSession(): SessionContextValue {
  const value = useContext(SessionContext);
  if (!value) throw new Error('useSession must be used inside SessionProvider');
  return value;
}
