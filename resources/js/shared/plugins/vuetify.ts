// Vuetify instance and Pixely Design System theme (light + dark).
// Vuetify's theme *is* the platform's design tokens layer — colours,
// spacing, and typography are defined here, not duplicated elsewhere.
import 'vuetify/styles'
import { createVuetify, type ThemeDefinition } from 'vuetify'

const pixelyLight: ThemeDefinition = {
  dark: false,
  colors: {
    background: '#f9fafb',
    surface: '#ffffff',
    primary: '#2563eb',
    secondary: '#e5e7eb',
    error: '#dc2626',
    info: '#0ea5e9',
    success: '#16a34a',
    warning: '#d97706',
  },
}

const pixelyDark: ThemeDefinition = {
  dark: true,
  colors: {
    background: '#0f172a',
    surface: '#1e293b',
    primary: '#3b82f6',
    secondary: '#334155',
    error: '#ef4444',
    info: '#38bdf8',
    success: '#22c55e',
    warning: '#f59e0b',
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
  },
})
