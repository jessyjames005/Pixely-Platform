// Pinia store for Core platform/user settings and locales
import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import { useAuthStore } from '@core/auth/store/auth.store'
import type { Locale, PlatformSettings, UserSettings } from '../models/Settings'

interface SettingsState {
  locales: Locale[]
  platformSettings: PlatformSettings | null
  userSettings: UserSettings | null
}

export const useSettingsStore = defineStore('settings', {
  state: (): SettingsState => ({
    locales: [],
    platformSettings: null,
    userSettings: null,
  }),

  actions: {
    async fetchLocales(): Promise<void> {
      const result = await apiClient.getCollection<Omit<Locale, 'id' | 'type'>>('/locales')
      this.locales = result.resources
    },

    async fetchPlatformSettings(): Promise<void> {
      this.platformSettings = await apiClient.getResource<PlatformSettings>('/platform-settings/current')
    },

    async updatePlatformSettings(payload: Partial<PlatformSettings>): Promise<void> {
      this.platformSettings = await apiClient.putResource(
        '/platform-settings/current',
        'platform-settings',
        payload,
        'current',
      )
    },

    async fetchUserSettings(): Promise<void> {
      const userId = useAuthStore().user?.id
      if (!userId) throw new Error('A signed-in user is required to load user settings.')
      this.userSettings = await apiClient.getResource<UserSettings>(`/user-settings/${userId}`)
    },

    async updateUserSettings(payload: Partial<UserSettings>): Promise<void> {
      const userId = useAuthStore().user?.id
      if (!userId) throw new Error('A signed-in user is required to update user settings.')
      this.userSettings = await apiClient.putResource(
        `/user-settings/${userId}`,
        'user-settings',
        payload,
        userId,
      )
    },
  },
})
