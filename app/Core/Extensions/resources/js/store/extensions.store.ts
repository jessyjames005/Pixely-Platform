// Pinia store for Core extension management: list, enable/disable,
// configuration, and install/update/uninstall (zip upload).
import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import type { ApiCollectionResponse, ApiResponse } from '@shared/types/api'
import type { ExtensionSummary, ExtensionDetail } from '../models/Extension'

interface ExtensionsState {
  extensions: ExtensionSummary[]
  config: Record<string, unknown> | null
}

export const useExtensionsStore = defineStore('extensions', {
  state: (): ExtensionsState => ({
    extensions: [],
    config: null,
  }),

  actions: {
    async fetchExtensions(): Promise<void> {
      const result = await apiClient.get<ApiCollectionResponse<ExtensionSummary>>('/extensions')
      this.extensions = result.data
    },

    async fetchDetail(id: string): Promise<ExtensionDetail> {
      const result = await apiClient.get<ApiResponse<ExtensionDetail>>(`/extensions/${id}`)
      return result.data
    },

    async enable(id: string): Promise<void> {
      await apiClient.post<ApiResponse<ExtensionSummary>>(`/extensions/${id}/enable`)
    },

    async disable(id: string): Promise<void> {
      await apiClient.post<ApiResponse<ExtensionSummary>>(`/extensions/${id}/disable`)
    },

    async fetchConfig(id: string): Promise<void> {
      const result = await apiClient.get<ApiResponse<Record<string, unknown>>>(`/extensions/${id}/config`)
      this.config = result.data
    },

    async updateConfig(id: string, config: Record<string, unknown>): Promise<void> {
      const result = await apiClient.put<ApiResponse<Record<string, unknown>>>(`/extensions/${id}/config`, config)
      this.config = result.data
    },

    async install(file: File): Promise<{ id: string; name: string; version: string }> {
      const formData = new FormData()
      formData.append('package', file)
      const result = await apiClient.post<ApiResponse<{ id: string; name: string; version: string }>>(
        '/extensions/install',
        formData,
      )
      return result.data
    },

    async update(id: string, file: File): Promise<{ id: string; name: string; version: string }> {
      const formData = new FormData()
      formData.append('package', file)
      const result = await apiClient.post<ApiResponse<{ id: string; name: string; version: string }>>(
        `/extensions/${id}/update`,
        formData,
      )
      return result.data
    },

    async uninstall(id: string): Promise<void> {
      await apiClient.delete<void>(`/extensions/${id}`)
    },
  },
})
