// Extension resource shapes as returned by the Extension Manager API
export interface ExtensionSummary {
  capabilities: string[]
  id: string
  name: string
  version: string
  dependencies: string[]
  enabled: boolean
}

export interface ExtensionDetail extends ExtensionSummary {
  path: string
  providers: string[]
}

export interface ExtensionConfigPayload {
  defaults: Record<string, unknown>
  values: Record<string, unknown>
}
