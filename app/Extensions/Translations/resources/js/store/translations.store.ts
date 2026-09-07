// Pinia store for the Translations extension: browse and edit
// translation strings for Core and any translatable extension.
import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import type { ApiCollectionResponse, ApiResponse } from '@shared/types/api'
import type { TranslatableModule, TranslationGroup } from '../models/Translation'

interface TranslationsState {
  modules: TranslatableModule[]
  groups: string[]
  current: TranslationGroup | null
}

export const useTranslationsStore = defineStore('translations', {
  state: (): TranslationsState => ({
    modules: [],
    groups: [],
    current: null,
  }),

  actions: {
    async fetchModules(): Promise<void> {
      const result = await apiClient.get<ApiCollectionResponse<TranslatableModule>>('/translations/modules')
      this.modules = result.data
    },

    async fetchGroups(moduleId: string, locale: string): Promise<void> {
      const result = await apiClient.get<ApiCollectionResponse<string>>(`/translations/${moduleId}/groups`, { locale })
      this.groups = result.data
    },

    async fetchGroup(moduleId: string, group: string, locale: string, reference: string): Promise<void> {
      const result = await apiClient.get<ApiResponse<TranslationGroup>>(`/translations/${moduleId}/${group}`, {
        locale,
        reference,
      })
      this.current = result.data
    },

    async saveGroup(moduleId: string, group: string, locale: string, translations: Record<string, string | null>): Promise<void> {
      await apiClient.put<ApiResponse<{ saved: boolean }>>(`/translations/${moduleId}/${group}`, {
        locale,
        translations,
      })
    },
  },
})
