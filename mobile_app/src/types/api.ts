export type Organization = { id: number; name: string; slug: string; role?: string; cluster_name?: string | null; status?: string };
export type User = { id: number; name: string; email?: string | null; phone?: string | null; locale: string; roles: string[]; organizations?: Organization[] };
export type AuthSession = { user: User; organizations: Organization[]; token: string; token_type: 'Bearer' };

export type CropCycle = {
  id: number;
  crop?: { id: number; name: string; slug?: string };
  status: string;
  planting_date?: string | null;
  expected_harvest_date?: string | null;
};

export type Farm = {
  id: string;
  name: string;
  locality?: string | null;
  state?: string | null;
  country?: string;
  status: string;
  provider_status?: string;
  area?: { hectares?: number | null; acres?: number | null };
  centroid?: { latitude?: number | null; longitude?: number | null };
  organization?: { id: number; name: string } | null;
  active_crop_cycle?: CropCycle | null;
  last_synced_at?: string | null;
};

export type Advisory = {
  id: number;
  type: string;
  severity?: string | null;
  title: string;
  summary: string;
  observed_at?: string | null;
  valid_until?: string | null;
  source?: string | null;
  acknowledgement?: { read_at?: string | null; acted_at?: string | null; feedback?: string | null };
};

export type WeatherDay = {
  forecast_date: string;
  temperature_min?: number | null;
  temperature_max?: number | null;
  rainfall?: number | null;
  rainfall_probability?: number | null;
  humidity?: number | null;
  condition_code?: string | null;
};

export type Diagnosis = {
  id: string;
  farm_id: string;
  status: string;
  note?: string | null;
  diagnosis?: string | null;
  recommendation?: string | null;
  confidence?: number | null;
  image?: { url: string; expires_at: string };
  created_at?: string;
};

export type VoiceRequest = {
  id: string;
  status: string;
  source_language: string;
  response_language: string;
  transcript?: string | null;
  translated_transcript?: string | null;
  guidance?: string | null;
  safety_note?: string | null;
  created_at?: string;
};

export type FinanceProduct = {
  id: number;
  name: string;
  category: string;
  financing_structure?: string | null;
  eligibility_summary?: string | null;
  minimum_amount?: number | null;
  maximum_amount?: number | null;
  minimum_deposit_percent?: number | null;
  maximum_tenor_months?: number | null;
  currency: string;
  partner: { id: number; name: string; type: string; relationship_status: string };
};

export type FinanceApplication = {
  id: string;
  status: string;
  requested_amount?: number | null;
  quantity: number;
  purpose: string;
  partner_reference?: string | null;
  product: FinanceProduct;
  farm: Pick<Farm, 'id' | 'name'>;
  created_at?: string;
};

export type Paginated<T> = { data: T[]; meta?: Record<string, unknown>; links?: Record<string, string | null> };
export type ApiEnvelope<T> = { data: T; meta?: Record<string, unknown> };
