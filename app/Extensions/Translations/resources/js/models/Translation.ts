export interface TranslatableModule {
  id: string
  locales: string[]
}

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
