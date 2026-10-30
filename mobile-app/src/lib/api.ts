const API_BASE = (process.env.EXPO_PUBLIC_API_URL || 'http://localhost:8000/api/mobile/v1').replace(/\/+$/, '');

export class ApiError extends Error {
  status: number;
  code?: string;

  constructor(message: string, status: number, code?: string) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.code = code;
  }
}

interface ApiOptions {
  method?: 'GET' | 'POST' | 'PATCH';
  token?: string | null;
  body?: unknown;
}

export async function api<T>(path: string, options: ApiOptions = {}): Promise<T> {
  const headers: Record<string, string> = { Accept: 'application/json' };
  if (options.body !== undefined) headers['Content-Type'] = 'application/json';
  if (options.token) headers.Authorization = 'Bearer ' + options.token;

  let response: Response;
  try {
    response = await fetch(API_BASE + path, {
      method: options.method || 'GET',
      headers,
      body: options.body === undefined ? undefined : JSON.stringify(options.body),
    });
  } catch {
    throw new ApiError('Connexion impossible. Vérifiez l’adresse du serveur et votre réseau.', 0, 'network_error');
  }

  const payload = await response.json().catch(() => ({}));
  if (!response.ok) {
    const validationMessage = payload.errors ? Object.values(payload.errors).flat()[0] : null;
    throw new ApiError(
      String(validationMessage || payload.message || 'Une erreur est survenue. Réessayez.'),
      response.status,
      payload.code,
    );
  }

  return payload as T;
}

export function buildQuery(params: Record<string, string | number | null | undefined>): string {
  const query = Object.entries(params)
    .filter(([, value]) => value !== null && value !== undefined && value !== '')
    .map(([key, value]) => encodeURIComponent(key) + '=' + encodeURIComponent(String(value)))
    .join('&');
  return query ? '?' + query : '';
}

export function getApiBaseUrl(): string {
  return API_BASE;
}