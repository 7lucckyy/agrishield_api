import { Redirect, type Href } from 'expo-router';

import { useAuth } from '@/context/auth-context';

export default function Index() {
  const { token } = useAuth();
  const destination = (token ? '/(tabs)' : '/(auth)/welcome') as Href;
  return <Redirect href={destination} />;
}
