export interface Product {
  id: number;
  name: string;
  category?: string | null;
  unit?: string | null;
  published_observations_count?: number;
}

export interface Market {
  id: number;
  name: string;
  commune?: string | null;
  department?: string | null;
  published_observations_count?: number;
}

export interface PriceObservation {
  id: number;
  price_xof: number;
  unit_snapshot: string;
  quality?: string | null;
  observed_at?: string | null;
  product: Product | null;
  market: Market | null;
}

export interface PageResult<T> {
  data: T[];
  meta: { current_page: number; last_page: number; per_page: number; total: number };
}

export interface HomeData {
  published_count: number;
  market_count: number;
  product_count: number;
  demo_mode: boolean;
  latest_prices: PriceObservation[];
}

export interface ContactRequest {
  id: number;
  status: 'pending' | 'approved' | 'rejected' | string;
  created_at?: string | null;
  product?: string | null;
  market?: string | null;
  requested_volume?: string | null;
  producer?: { name: string; phone: string } | null;
  transaction_declared_at?: string | null;
  transaction_happened?: boolean | null;
}

export interface SessionUser {
  id: number;
  name: string;
  email: string;
}

export function formatPrice(value: number): string {
  return new Intl.NumberFormat('fr-FR').format(value) + ' F CFA';
}

export function formatDate(value?: string | null): string {
  if (!value) return 'Date non précisée';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }).format(date);
}