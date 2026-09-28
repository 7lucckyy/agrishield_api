import Constants from 'expo-constants';
import { fetch as expoFetch } from 'expo/fetch';
import { File } from 'expo-file-system';

import type {
  Advisory,
  ApiEnvelope,
  AuthSession,
  Diagnosis,
  Farm,
  FinanceApplication,
  FinanceProduct,
  Organization,
  Paginated,
  User,
  VoiceRequest,
  WeatherDay,
} from '@/types/api';

const configuredUrl = process.env.EXPO_PUBLIC_API_URL ?? Constants.expoConfig?.extra?.apiUrl;
export const API_BASE_URL = String(configuredUrl ?? 'https://agrishield.ng/api/v1').replace(/\/$/, '');

export class ApiError extends Error {
  constructor(
    message: string,
    public readonly status: number,
    public readonly errors: Record<string, string[]> = {},
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

type Query = Record<string, string | number | boolean | null | undefined>;
type FilePart = { uri: string; name: string; type: string };

const queryString = (query?: Query): string => {
  if (!query) return '';
  const pairs = Object.entries(query)
    .filter(([, value]) => value !== undefined && value !== null && value !== '')
    .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`);
  return pairs.length ? `?${pairs.join('&')}` : '';
};

class AgriShieldApi {
  private token: string | null = null;

  setToken(token: string | null): void {
    this.token = token;
  }

  private async request<T>(path: string, init: RequestInit = {}): Promise<T> {
    const isMultipart = init.body instanceof FormData;
    const requestFetch = isMultipart ? expoFetch : fetch;
    const response = await requestFetch(`${API_BASE_URL}${path}`, {
      ...init,
      headers: {
        Accept: 'application/json',
        ...(isMultipart ? {} : { 'Content-Type': 'application/json' }),
        ...(this.token ? { Authorization: `Bearer ${this.token}` } : {}),
        ...init.headers,
      },
    });

    const body = await response.json().catch(() => ({}));
    if (!response.ok) {
      throw new ApiError(body.message ?? 'The request could not be completed.', response.status, body.errors ?? {});
    }

    return body as T;
  }

  private formData(fields: Record<string, string | number | null | undefined>, file?: { field: string; value: FilePart }): FormData {
    const data = new FormData();
    Object.entries(fields).forEach(([key, value]) => {
      if (value !== undefined && value !== null) data.append(key, String(value));
    });
    if (file) data.append(file.field, new File(file.value.uri), file.value.name);
    return data;
  }

  health = () => this.request<ApiEnvelope<Record<string, unknown>>>('/health');
  detailedHealth = () => this.request<ApiEnvelope<Record<string, unknown>>>('/health/detailed');

  async login(identifier: string, password: string): Promise<AuthSession> {
    const field = identifier.includes('@') ? 'email' : 'phone';
    const response = await this.request<ApiEnvelope<AuthSession>>('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ [field]: identifier.trim(), password, device_name: 'AgriShield mobile' }),
    });
    return response.data;
  }

  async register(payload: Record<string, unknown>): Promise<AuthSession> {
    const response = await this.request<ApiEnvelope<AuthSession>>('/auth/register', { method: 'POST', body: JSON.stringify(payload) });
    return response.data;
  }

  logout = () => this.request('/auth/logout', { method: 'POST' });
  revokeAllTokens = () => this.request('/auth/tokens/revoke-all', { method: 'POST' });
  forgotPassword = (identifier: Record<'email' | 'phone', string>) => this.request('/auth/password/forgot', { method: 'POST', body: JSON.stringify(identifier) });
  resetPassword = (payload: Record<string, string>) => this.request('/auth/password/reset', { method: 'POST', body: JSON.stringify(payload) });

  async me(): Promise<User> {
    return (await this.request<ApiEnvelope<User>>('/me')).data;
  }
  updateMe = (payload: Partial<User>) => this.request<ApiEnvelope<User>>('/me', { method: 'PATCH', body: JSON.stringify(payload) });
  updatePassword = (payload: Record<string, string>) => this.request('/me/password', { method: 'PATCH', body: JSON.stringify(payload) });

  async farms(query?: Query): Promise<Farm[]> {
    return (await this.request<Paginated<Farm>>(`/farms${queryString(query)}`)).data;
  }
  async farm(id: string): Promise<Farm> {
    return (await this.request<ApiEnvelope<Farm>>(`/farms/${id}`)).data;
  }
  createFarm = (payload: Record<string, unknown>) => this.request<ApiEnvelope<Farm>>('/farms', { method: 'POST', body: JSON.stringify(payload) });
  updateFarm = (id: string, payload: Record<string, unknown>) => this.request<ApiEnvelope<Farm>>(`/farms/${id}`, { method: 'PATCH', body: JSON.stringify(payload) });
  deleteFarm = (id: string) => this.request(`/farms/${id}`, { method: 'DELETE' });
  syncFarm = (id: string, payload: Record<string, unknown> = {}) => this.request(`/farms/${id}/sync`, { method: 'POST', body: JSON.stringify(payload) });
  syncRuns = (id: string, query?: Query) => this.request(`/farms/${id}/sync-runs${queryString(query)}`);

  async weather(id: string): Promise<WeatherDay[]> {
    return (await this.request<ApiEnvelope<{ days: WeatherDay[] }>>(`/farms/${id}/weather`)).data.days;
  }
  soilHealth = (id: string, query?: Query) => this.request(`/farms/${id}/soil-health${queryString(query)}`);
  satelliteObservations = (id: string, query?: Query) => this.request(`/farms/${id}/satellite-observations${queryString(query)}`);
  satelliteSummary = (id: string) => this.request(`/farms/${id}/satellite-observations/summary`);

  async advisories(id: string, query?: Query): Promise<Advisory[]> {
    return (await this.request<Paginated<Advisory>>(`/farms/${id}/advisories${queryString(query)}`)).data;
  }
  advisory = (farmId: string, advisoryId: number) => this.request<ApiEnvelope<Advisory>>(`/farms/${farmId}/advisories/${advisoryId}`);
  createAdvisory = (farmId: string, payload: Record<string, unknown>) => this.request(`/farms/${farmId}/advisories`, { method: 'POST', body: JSON.stringify(payload) });
  acknowledgeAdvisory = (farmId: string, advisoryId: number, payload: Record<string, unknown>) => this.request(`/farms/${farmId}/advisories/${advisoryId}/acknowledge`, { method: 'POST', body: JSON.stringify(payload) });

  async diagnoses(farmId: string, query?: Query): Promise<Diagnosis[]> {
    return (await this.request<Paginated<Diagnosis>>(`/farms/${farmId}/diagnosis-requests${queryString(query)}`)).data;
  }
  diagnosis = (farmId: string, id: string) => this.request<ApiEnvelope<Diagnosis>>(`/farms/${farmId}/diagnosis-requests/${id}`);
  updateDiagnosis = (farmId: string, id: string, payload: Record<string, unknown>) => this.request(`/farms/${farmId}/diagnosis-requests/${id}`, { method: 'PATCH', body: JSON.stringify(payload) });
  async submitDiagnosis(farmId: string, image: FilePart, note?: string): Promise<Diagnosis> {
    const body = this.formData({ note }, { field: 'image', value: image });
    return (await this.request<ApiEnvelope<Diagnosis>>(`/farms/${farmId}/diagnosis-requests`, {
      method: 'POST', body, headers: { 'Idempotency-Key': `${Date.now()}-${farmId}` },
    })).data;
  }

  cropCycles = (farmId: string, query?: Query) => this.request(`/farms/${farmId}/crop-cycles${queryString(query)}`);
  cropCycle = (farmId: string, id: number) => this.request(`/farms/${farmId}/crop-cycles/${id}`);
  createCropCycle = (farmId: string, payload: Record<string, unknown>) => this.request(`/farms/${farmId}/crop-cycles`, { method: 'POST', body: JSON.stringify(payload) });
  updateCropCycle = (farmId: string, id: number, payload: Record<string, unknown>) => this.request(`/farms/${farmId}/crop-cycles/${id}`, { method: 'PATCH', body: JSON.stringify(payload) });
  deleteCropCycle = (farmId: string, id: number) => this.request(`/farms/${farmId}/crop-cycles/${id}`, { method: 'DELETE' });

  crops = (query?: Query) => this.request(`/crops${queryString(query)}`);
  crop = (id: number) => this.request(`/crops/${id}`);
  createCrop = (payload: Record<string, unknown>) => this.request('/crops', { method: 'POST', body: JSON.stringify(payload) });
  updateCrop = (id: number, payload: Record<string, unknown>) => this.request(`/crops/${id}`, { method: 'PATCH', body: JSON.stringify(payload) });
  deactivateCrop = (id: number) => this.request(`/crops/${id}`, { method: 'DELETE' });

  async organizations(): Promise<Organization[]> {
    return (await this.request<Paginated<Organization>>('/organizations')).data;
  }
  organization = (id: number) => this.request<ApiEnvelope<Organization>>(`/organizations/${id}`);
  createOrganization = (payload: Record<string, unknown>) => this.request('/organizations', { method: 'POST', body: JSON.stringify(payload) });
  updateOrganization = (id: number, payload: Record<string, unknown>) => this.request(`/organizations/${id}`, { method: 'PATCH', body: JSON.stringify(payload) });
  organizationOverview = (id: number) => this.request(`/organizations/${id}/overview`);
  organizationFarms = (id: number, query?: Query) => this.request(`/organizations/${id}/farms${queryString(query)}`);
  organizationMembers = (id: number, query?: Query) => this.request(`/organizations/${id}/members${queryString(query)}`);
  updateMember = (organizationId: number, userId: number, payload: Record<string, unknown>) => this.request(`/organizations/${organizationId}/members/${userId}`, { method: 'PATCH', body: JSON.stringify(payload) });
  removeMember = (organizationId: number, userId: number) => this.request(`/organizations/${organizationId}/members/${userId}`, { method: 'DELETE' });
  rotateReferralCode = (id: number) => this.request(`/organizations/${id}/referral-code/rotate`, { method: 'POST' });

  async voiceRequests(): Promise<VoiceRequest[]> {
    return (await this.request<Paginated<VoiceRequest>>('/voice-assistance')).data;
  }
  voiceRequest = (id: string) => this.request<ApiEnvelope<VoiceRequest>>(`/voice-assistance/${id}`);
  async submitVoice(audio: FilePart, sourceLanguage: string, responseLanguage: string, farmId?: string): Promise<VoiceRequest> {
    const body = this.formData({ source_language: sourceLanguage, response_language: responseLanguage, farm_id: farmId }, { field: 'audio', value: audio });
    return (await this.request<ApiEnvelope<VoiceRequest>>('/voice-assistance', { method: 'POST', body })).data;
  }

  async financeProducts(organizationId: number): Promise<FinanceProduct[]> {
    return (await this.request<Paginated<FinanceProduct>>(`/organizations/${organizationId}/asset-finance/products`)).data;
  }
  async financeApplications(organizationId: number): Promise<FinanceApplication[]> {
    return (await this.request<Paginated<FinanceApplication>>(`/organizations/${organizationId}/asset-finance/applications`)).data;
  }
  financeApplication = (organizationId: number, id: string) => this.request<ApiEnvelope<FinanceApplication>>(`/organizations/${organizationId}/asset-finance/applications/${id}`);
  createFinanceApplication = (organizationId: number, payload: Record<string, unknown>) => this.request<ApiEnvelope<FinanceApplication>>(`/organizations/${organizationId}/asset-finance/applications`, { method: 'POST', body: JSON.stringify(payload) });
  updateFinanceApplication = (organizationId: number, id: string, payload: Record<string, unknown>) => this.request<ApiEnvelope<FinanceApplication>>(`/organizations/${organizationId}/asset-finance/applications/${id}`, { method: 'PATCH', body: JSON.stringify(payload) });

  integrations = () => this.request('/integrations');
  updateIntegration = (id: number, payload: Record<string, unknown>) => this.request(`/integrations/${id}`, { method: 'PATCH', body: JSON.stringify(payload) });
  testIntegration = (id: number) => this.request(`/integrations/${id}/test`, { method: 'POST' });
  resetIntegrationCircuit = (id: number) => this.request(`/integrations/${id}/circuit/reset`, { method: 'POST' });
}

export const api = new AgriShieldApi();
