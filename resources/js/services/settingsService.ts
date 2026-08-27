// API service for Core platform/user settings and locales
import { apiClient } from './apiClient'
import type { ApiCollectionResponse, ApiResponse } from '../types/api'

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

// Fetches the list of locales available across the platform
export function listLocales(): Promise<ApiCollectionResponse<Locale>> {
  return apiClient.get<ApiCollectionResponse<Locale>>('/locales')
}

// Fetches the current platform-wide settings
export function getPlatformSettings(): Promise<ApiResponse<PlatformSettings>> {
  return apiClient.get<ApiResponse<PlatformSettings>>('/settings/platform')
}

// Updates platform-wide settings (partial update, merged server-side)
export function updatePlatformSettings(
  payload: Partial<PlatformSettings>,
): Promise<ApiResponse<PlatformSettings>> {
  return apiClient.put<ApiResponse<PlatformSettings>>('/settings/platform', payload)
}

// Fetches the current user's own settings
export function getUserSettings(): Promise<ApiResponse<UserSettings>> {
  return apiClient.get<ApiResponse<UserSettings>>('/settings/user')
}

// Updates the current user's own settings (partial update, merged server-side)
export function updateUserSettings(
  payload: Partial<UserSettings>,
): Promise<ApiResponse<UserSettings>> {
  return apiClient.put<ApiResponse<UserSettings>>('/settings/user', payload)
}
