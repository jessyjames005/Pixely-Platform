// A single available locale
export interface Locale {
  code: string
  label: string
}

// Platform-wide settings shape
export interface PlatformSettings {
  site_name: string
  locale: string
}

// Current user's own settings shape
export interface UserSettings {
  locale: string | null
}
