/**
 * Pixely Design System — design tokens.
 *
 * Single source of truth for the visual language shared by the platform core
 * and extensions. Tokens are consumed by components, Vuetify themes and
 * Storybook stories so colors, spacing and typography stay consistent.
 */

export const colors = {
  primary: '#5B5BF5',
  primaryDark: '#3F3FD1',
  primaryLight: '#EDEDFE',
  secondary: '#1F2A44',
  success: '#4CAF50',
  warning: '#F2A93B',
  danger: '#EF5350',
  info: '#03A9F4',
  surface: '#FFFFFF',
  background: '#F5F6FA',
  border: '#E4E7EE',
  textPrimary: '#1F2A44',
  textSecondary: '#6B7280',
  textMuted: '#9CA3AF',
} as const

export const typography = {
  fontFamily: "'Inter', 'Roboto', 'Helvetica Neue', Arial, sans-serif",
  heading: {
    h1: { fontSize: '2rem', fontWeight: 700, lineHeight: 1.25 },
    h2: { fontSize: '1.5rem', fontWeight: 700, lineHeight: 1.3 },
    h3: { fontSize: '1.25rem', fontWeight: 600, lineHeight: 1.35 },
    h4: { fontSize: '1.063rem', fontWeight: 600, lineHeight: 1.4 },
  },
  body: {
    large: { fontSize: '1rem', fontWeight: 400, lineHeight: 1.6 },
    base: { fontSize: '0.875rem', fontWeight: 400, lineHeight: 1.5 },
    small: { fontSize: '0.8125rem', fontWeight: 400, lineHeight: 1.45 },
    caption: { fontSize: '0.75rem', fontWeight: 500, lineHeight: 1.4 },
  },
} as const

export const spacing = {
  xs: '4px',
  sm: '8px',
  md: '16px',
  lg: '24px',
  xl: '32px',
  xxl: '48px',
} as const

export const radius = {
  sm: '6px',
  md: '10px',
  lg: '16px',
  pill: '999px',
} as const

export const elevation = {
  flat: 'none',
  low: '0 1px 2px rgba(31, 42, 68, 0.06)',
  medium: '0 4px 16px rgba(31, 42, 68, 0.08)',
  high: '0 12px 32px rgba(31, 42, 68, 0.12)',
} as const

export type DesignTokens = {
  colors: typeof colors
  typography: typeof typography
  spacing: typeof spacing
  radius: typeof radius
  elevation: typeof elevation
}

export const designTokens: DesignTokens = {
  colors,
  typography,
  spacing,
  radius,
  elevation,
}
