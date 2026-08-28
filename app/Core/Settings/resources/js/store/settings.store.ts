// Pinia store for Core platform/user settings and locales
import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import type { ApiCollectionResponse, ApiResponse } from '@shared/types/api'
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
      const result = await apiClient.get<ApiCollectionResponse<Locale>>('/locales')
      this.locales = result.data
    },

    async fetchPlatformSettings(): Promise<void> {
      const result = await apiClient.get<ApiResponse<PlatformSettings>>('/settings/platform')
      this.platformSettings = result.data
    },

    async updatePlatformSettings(payload: Partial<PlatformSettings>): Promise<void> {
      const result = await apiClient.put<ApiResponse<PlatformSettings>>('/settings/platform', payload)
      this.platformSettings = result.data
    },

    async fetchUserSettings(): Promise<void> {
      const result = await apiClient.get<ApiResponse<UserSettings>>('/settings/user')
      this.userSettings = result.data
    },

    async updateUserSettings(payload: Partial<UserSettings>): Promise<void> {
      const result = await apiClient.put<ApiResponse<UserSettings>>('/settings/user', payload)
      this.userSettings = result.data
    },
  },
})
