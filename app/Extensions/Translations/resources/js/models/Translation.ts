import type { JsonApiModel } from '@shared/types/api'

export interface TranslatableModule {
  locales: string[]
  id: string
  type: string
}

export type TranslationModule = JsonApiModel<{ locales: string[] }>
export type TranslationGroupResource = JsonApiModel<{ module: string; group: string; locale: string }>

export interface TranslationEntry {
  key: string
  reference: string | null
  target: string | null
  suspect: boolean
}

export interface TranslationGroup {
  module: string
  group: string
  locale: string
  reference: string
  entries: TranslationEntry[]
  completion: number
}
