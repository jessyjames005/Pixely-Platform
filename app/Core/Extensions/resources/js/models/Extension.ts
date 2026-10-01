import type { JsonApiModel } from '@shared/types/api'

// Extension resource shapes as returned by the Extension Manager API
export type ExtensionSummary = JsonApiModel<{
  name: string
  version: string
  dependencies: string[]
  enabled: boolean
}>

export type ExtensionDetail = ExtensionSummary

export interface ExtensionConfigAttributes {
  defaults: Record<string, unknown>
  values: Record<string, unknown>
}

export type ExtensionConfigPayload = JsonApiModel<ExtensionConfigAttributes>

export type ExtensionInstallResult = JsonApiModel<Pick<ExtensionSummary, 'name' | 'version'>>
