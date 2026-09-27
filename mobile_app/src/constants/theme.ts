import { Platform } from 'react-native';

export const colors = {
  forest: '#173F2A',
  leaf: '#26724A',
  leafSoft: '#DDEBDF',
  millet: '#D9A441',
  milletSoft: '#F6EACD',
  clay: '#A94F36',
  sky: '#DCECF2',
  canvas: '#F4F7F2',
  paper: '#FFFFFF',
  ink: '#14231A',
  muted: '#627067',
  line: '#D9E0DA',
  danger: '#A53B31',
  warning: '#8B5D14',
  success: '#1F6B45',
  overlay: 'rgba(20, 35, 26, 0.56)',
} as const;

export const spacing = { xs: 4, sm: 8, md: 16, lg: 24, xl: 32, xxl: 48 } as const;
export const radii = { sm: 10, md: 16, lg: 24, pill: 999 } as const;

export const shadows = {
  card: Platform.select({
    ios: { shadowColor: colors.ink, shadowOpacity: 0.08, shadowRadius: 18, shadowOffset: { width: 0, height: 7 } },
    android: { elevation: 2 },
    default: {},
  }),
};

export const typography = {
  display: Platform.select({ ios: 'Georgia', android: 'serif', default: 'serif' }),
  body: Platform.select({ ios: 'Avenir Next', android: 'sans-serif', default: 'system-ui' }),
  data: Platform.select({ ios: 'Menlo', android: 'monospace', default: 'monospace' }),
};
