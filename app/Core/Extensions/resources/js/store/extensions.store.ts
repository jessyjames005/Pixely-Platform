// Pinia store for Core extension management: list, enable/disable,
// configuration, and install/update/uninstall (zip upload).
import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import type { ApiCollectionResponse, ApiResponse } from '@shared/types/api'
import type { ExtensionSummary, ExtensionDetail, ExtensionConfigPayload } from '../models/Extension'

interface ExtensionsState {
  extensions: ExtensionSummary[]
  configDefaults: Record<string, unknown> | null
  configValues: Record<string, unknown> | null
}

export const useExtensionsStore = defineStore('extensions', {
  state: (): ExtensionsState => ({
    extensions: [],
    configDefaults: null,
    configValues: null,
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
      const result = await apiClient.get<ApiResponse<ExtensionConfigPayload>>(`/extensions/${id}/config`)
      this.configDefaults = result.data.defaults
      this.configValues = result.data.values
    },

    async updateConfig(id: string, config: Record<string, unknown>): Promise<void> {
      const result = await apiClient.put<ApiResponse<ExtensionConfigPayload>>(`/extensions/${id}/config`, config)
      this.configDefaults = result.data.defaults
      this.configValues = result.data.values
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
