// Pinia store for Core extension management: list, enable/disable,
// configuration, and install/update/uninstall (zip upload).
import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import { decodeJsonApiId, type JsonApiDocument, type JsonApiResource } from '@shared/types/api'
import type {
  ExtensionSummary,
  ExtensionDetail,
  ExtensionConfigAttributes,
  ExtensionConfigPayload,
  ExtensionInstallResult,
} from '../models/Extension'

interface ExtensionAttributes {
  name: string
  version: string
  dependencies: string[]
  enabled: boolean
}

export interface ExtensionSettingDefinition {
  type: string
  label?: string
  description?: string
  default?: unknown
  required?: boolean
  min?: number
  max?: number
  options?: Array<string | number | boolean>
}

interface ExtensionsState {
  extensions: ExtensionSummary[]
  configDefaults: Record<string, unknown> | null
  configValues: Record<string, unknown> | null
  navigation: NavItem[]
  configId: string | null
  settingsSchema: Record<string, ExtensionSettingDefinition> | null
}

export const useExtensionsStore = defineStore('extensions', {
  state: (): ExtensionsState => ({
    extensions: [],
    configDefaults: null,
    configValues: null,
    navigation: [],
    configId: null,
    settingsSchema: null,
  }),

  actions: {
    async fetchExtensions(): Promise<void> {
      const result = await apiClient.getCollection<ExtensionAttributes>('/extensions')
      this.extensions = result.resources
    },

    async fetchDetail(id: string): Promise<ExtensionDetail> {
      const result = await apiClient.getResource<ExtensionAttributes>(`/extensions/${id}`)
      if (!result) throw new Error('The extension detail response did not include a resource.')
      return result
    },

    async enable(id: string): Promise<void> {
      await apiClient.post<JsonApiDocument<JsonApiResource<ExtensionAttributes>>>(`/extensions/${id}/enable`)
    },

    async disable(id: string): Promise<void> {
      await apiClient.post<JsonApiDocument<JsonApiResource<ExtensionAttributes>>>(`/extensions/${id}/disable`)
    },

    async fetchSettingsSchema(id: string): Promise<void> {
      const result = await apiClient.get<{ data: { extension_id: string; schema: Record<string, ExtensionSettingDefinition> } }>(
        `/extensions/${id}/settings-schema`,
      )
      this.settingsSchema = result.data.schema ?? {}
    },

    async fetchConfig(id: string): Promise<void> {
      const result = await apiClient.getResource<ExtensionConfigAttributes>(`/extensions/${id}/config`)
      if (!result) throw new Error('The extension configuration response did not include a resource.')
      this.configId = result.id
      this.configDefaults = result.defaults
      this.configValues = result.values
    },

    async updateConfig(id: string, config: Record<string, unknown>): Promise<void> {
      if (!this.configId) await this.fetchConfig(id)
      const result = await apiClient.putResource<ExtensionConfigAttributes>(
        `/extensions/${id}/config`,
        'extension-configurations',
        { values: config },
        this.configId ?? undefined,
      )
      if (!result) throw new Error('The extension configuration response did not include a resource.')
      this.configId = result.id
      this.configDefaults = result.defaults
      this.configValues = result.values
    },

    async install(file: File): Promise<ExtensionInstallResult> {
      const formData = new FormData()
      formData.append('package', file)
      const result = await apiClient.postFormResource<Pick<ExtensionAttributes, 'name' | 'version'>>(
        '/extensions/install', formData,
      )
      if (!result) throw new Error('The extension install response did not include a resource.')
      return result
    },

    async update(id: string, file: File): Promise<ExtensionInstallResult> {
      const formData = new FormData()
      formData.append('package', file)
      const result = await apiClient.postFormResource<Pick<ExtensionAttributes, 'name' | 'version'>>(
        `/extensions/${id}/update`, formData,
      )
      if (!result) throw new Error('The extension update response did not include a resource.')
      return result
    },

    async uninstall(id: string): Promise<void> {
      const parts = decodeJsonApiId(id, 2)
      if (!parts || parts[0] !== 'extension' || !parts[1]) throw new Error('Invalid extension resource ID.')
      await apiClient.delete<void>(`/extensions/${parts[1]}`)
    },
  },
})
