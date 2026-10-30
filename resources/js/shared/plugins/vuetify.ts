// Vuetify instance and Pixely Design System theme (light + dark).
// Vuetify's theme *is* the platform's design tokens layer — colours,
// spacing, and typography are defined here, not duplicated elsewhere.
import 'vuetify/styles'
import { createVuetify, type ThemeDefinition } from 'vuetify'

const pixelyLight: ThemeDefinition = {
  dark: false,
  colors: {
    background: '#f8fafc',
    surface: '#ffffff',
    'surface-variant': '#f1f5f9',
    'on-background': '#111827',
    'on-surface': '#111827',
    'on-surface-variant': '#475569',
    primary: '#2563eb',
    'on-primary': '#ffffff',
    secondary: '#e2e8f0',
    'on-secondary': '#1e293b',
    outline: '#cbd5e1',
    error: '#dc2626',
    info: '#0284c7',
    success: '#15803d',
    warning: '#b45309',
  },
}

const pixelyDark: ThemeDefinition = {
  dark: true,
  colors: {
    background: '#0b1120',
    surface: '#111c2e',
    'surface-variant': '#1e293b',
    'on-background': '#f8fafc',
    'on-surface': '#f8fafc',
    'on-surface-variant': '#cbd5e1',
    primary: '#60a5fa',
    'on-primary': '#0b1120',
    secondary: '#334155',
    'on-secondary': '#f8fafc',
    outline: '#475569',
    error: '#f87171',
    info: '#38bdf8',
    success: '#4ade80',
    warning: '#fbbf24',
  },
}

export const vuetify = createVuetify({
  theme: {
    defaultTheme: 'pixelyLight',
    themes: {
      pixelyLight,
      pixelyDark,
    },
  },
  defaults: {
    VBtn: { rounded: 'lg' },
    VCard: { rounded: 'lg', elevation: 1 },
    VAlert: { rounded: 'lg' },
    VTextField: { variant: 'outlined', color: 'primary' },
    VSelect: { variant: 'outlined', color: 'primary' },
    VTextarea: { variant: 'outlined', color: 'primary' },
  },
})
