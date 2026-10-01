// Pinia store for the Translations extension: browse and edit
// translation strings for Core and any translatable extension.
import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import { decodeJsonApiId } from '@shared/types/api'
import type { TranslationGroupResource, TranslationGroup, TranslationModule, TranslationEntry } from '../models/Translation'

interface TranslationStringAttributes {
  key: string
  reference: string | null
  target: string | null
  suspect: boolean
}

interface TranslationsState {
  modules: TranslationModule[]
  groups: TranslationGroupResource[]
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
      const result = await apiClient.getCollection<{ locales: string[] }>('/translation-modules')
      this.modules = result.resources
    },

    async fetchGroups(moduleId: string, locale: string): Promise<void> {
      const moduleParts = decodeJsonApiId(moduleId, 2)
      if (!moduleParts || moduleParts[0] !== 'translation-modules' || !moduleParts[1]) {
        throw new Error('Invalid translation module resource ID.')
      }
      const result = await apiClient.getCollection<{ module: string; group: string; locale: string }>(
        '/translation-groups',
        { 'filter[module]': moduleParts[1], 'filter[locale]': locale },
      )
      this.groups = result.resources
    },

    async fetchGroup(groupId: string, reference: string): Promise<void> {
      const group = this.groups.find((item) => item.id === groupId)
      if (!group) throw new Error('The selected translation group is not available.')
      const result = await apiClient.getCollection<TranslationStringAttributes>('/translation-strings', {
        'filter[module]': group.module,
        'filter[group]': group.group,
        'filter[locale]': group.locale,
        'filter[reference]': reference,
      })
      const meta = result.meta ?? {}
      const entries: TranslationEntry[] = result.resources.map(({ key, reference: source, target, suspect }) => ({
        key,
        reference: source,
        target,
        suspect,
      }))
      this.current = {
        module: group.module,
        group: group.group,
        locale: group.locale,
        reference,
        entries,
        completion: Number(meta.completion ?? 0),
      }
    },

    async saveGroup(groupId: string, translations: Record<string, string | null>): Promise<void> {
      const group = this.groups.find((item) => item.id === groupId)
      if (!group) throw new Error('The selected translation group is not available.')
      await apiClient.patchResource(
        `/translation-groups/${group.id}`,
        'translation-groups',
        { locale: group.locale, translations },
        group.id,
      )
    },
  },
})
