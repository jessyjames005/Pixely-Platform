// Loads and caches the merged translation catalog (every module,
// every group) for the current locale. Backed by Core's public
// GET /locales/{locale} endpoint — works before login too, since the
// login screen itself needs translated text.
import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import type { ApiResponse } from '@shared/types/api'

// Deeply nested: { [module]: { [group]: { [key]: string } } }
type Catalog = Record<string, unknown>

interface I18nState {
  locale: string
  catalog: Catalog
  loaded: boolean
}

const DEFAULT_LOCALE = navigator.language?.toLowerCase().startsWith('fr') ? 'fr' : 'en'

export const useI18nStore = defineStore('i18n', {
  state: (): I18nState => ({
    locale: DEFAULT_LOCALE,
    catalog: {},
    loaded: false,
  }),

  actions: {
    async load(locale: string): Promise<void> {
      const result = await apiClient.get<ApiResponse<Catalog>>(`/locales/${locale}`)
      this.catalog = result.data
      this.locale = locale
      this.loaded = true
    },

    // No-op if already on that locale — avoids a redundant refetch
    // when e.g. the user's saved preference matches the browser guess.
    async setLocale(locale: string): Promise<void> {
      if (locale === this.locale && this.loaded) return
      await this.load(locale)
    },
  },
})
