// API service for Core platform/user settings and locales
import { apiClient } from '@shared/services/apiClient'
import type { ApiCollectionResponse, ApiResponse } from '@shared/types/api'

export interface Locale {
  code: string
  label: string
}

export interface PlatformSettings {
  site_name: string
  locale: string
}

export interface UserSettings {
  locale: string | null
}

export function listLocales(): Promise<ApiCollectionResponse<Locale>> {
  return apiClient.get<ApiCollectionResponse<Locale>>('/locales')
}

export function getPlatformSettings(): Promise<ApiResponse<PlatformSettings>> {
  return apiClient.get<ApiResponse<PlatformSettings>>('/settings/platform')
}

export function updatePlatformSettings(payload: Partial<PlatformSettings>): Promise<ApiResponse<PlatformSettings>> {
  return apiClient.put<ApiResponse<PlatformSettings>>('/settings/platform', payload)
}

export function getUserSettings(): Promise<ApiResponse<UserSettings>> {
  return apiClient.get<ApiResponse<UserSettings>>('/settings/user')
}

export function updateUserSettings(payload: Partial<UserSettings>): Promise<ApiResponse<UserSettings>> {
  return apiClient.put<ApiResponse<UserSettings>>('/settings/user', payload)
}
