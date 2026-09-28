import * as Haptics from 'expo-haptics';
import { createContext, type PropsWithChildren, useContext, useEffect, useMemo, useState } from 'react';

import { ApiError, api } from '@/lib/api';
import { tokenStorage } from '@/lib/storage';
import type { AuthSession, Organization, User } from '@/types/api';

type AuthContextValue = {
  user: User | null;
  organizations: Organization[];
  activeOrganization: Organization | null;
  token: string | null;
  isReady: boolean;
  signIn: (identifier: string, password: string) => Promise<void>;
  register: (payload: Record<string, unknown>) => Promise<void>;
  signOut: () => Promise<void>;
  selectOrganization: (organization: Organization) => void;
  refreshProfile: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: PropsWithChildren) {
  const [user, setUser] = useState<User | null>(null);
  const [organizations, setOrganizations] = useState<Organization[]>([]);
  const [activeOrganization, setActiveOrganization] = useState<Organization | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [isReady, setIsReady] = useState(false);

  const applySession = async (session: AuthSession): Promise<void> => {
    api.setToken(session.token);
    await tokenStorage.set(session.token);
    setToken(session.token);
    setUser(session.user);
    setOrganizations(session.organizations);
    setActiveOrganization(session.organizations[0] ?? null);
  };

  useEffect(() => {
    void (async () => {
      const storedToken = await tokenStorage.get();
      if (!storedToken) {
        setIsReady(true);
        return;
      }

      api.setToken(storedToken);
      try {
        const profile = await api.me();
        const availableOrganizations = profile.organizations ?? [];
        setToken(storedToken);
        setUser(profile);
        setOrganizations(availableOrganizations);
        setActiveOrganization(availableOrganizations[0] ?? null);
      } catch (reason) {
        if (reason instanceof ApiError && reason.status === 401) {
          api.setToken(null);
          await tokenStorage.clear();
        } else {
          setToken(storedToken);
        }
      } finally {
        setIsReady(true);
      }
    })();
  }, []);

  const value = useMemo<AuthContextValue>(() => ({
    user,
    organizations,
    activeOrganization,
    token,
    isReady,
    signIn: async (identifier, password) => {
      const session = await api.login(identifier, password);
      await applySession(session);
      await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
    },
    register: async (payload) => {
      const session = await api.register(payload);
      await applySession(session);
      await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
    },
    signOut: async () => {
      try {
        await api.logout();
      } finally {
        api.setToken(null);
        await tokenStorage.clear();
        setToken(null);
        setUser(null);
        setOrganizations([]);
        setActiveOrganization(null);
      }
    },
    selectOrganization: setActiveOrganization,
    refreshProfile: async () => {
      const profile = await api.me();
      const availableOrganizations = profile.organizations ?? [];
      setUser(profile);
      setOrganizations(availableOrganizations);
      setActiveOrganization((current) => availableOrganizations.find((organization) => organization.id === current?.id) ?? availableOrganizations[0] ?? null);
    },
  }), [activeOrganization, isReady, organizations, token, user]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);
  if (!context) throw new Error('useAuth must be used within AuthProvider');
  return context;
}
