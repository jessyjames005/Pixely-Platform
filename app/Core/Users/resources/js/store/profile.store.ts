// Pinia store for the current user's own self-service profile
// (name, bio, timezone, avatar) — distinct from users.store.ts,
// which is admin-only management of other users.
import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import type { ApiResponse } from '@shared/types/api'
import type { Profile, UpdateProfilePayload } from '../models/User'

interface ProfileState {
  profile: Profile | null
}

export const useProfileStore = defineStore('profile', {
  state: (): ProfileState => ({
    profile: null,
  }),

  actions: {
    async fetchProfile(): Promise<void> {
      const result = await apiClient.get<ApiResponse<Profile>>('/profile')
      this.profile = result.data
    },

    async updateProfile(payload: UpdateProfilePayload): Promise<void> {
      const result = await apiClient.put<ApiResponse<Profile>>('/profile', payload)
      this.profile = result.data
    },

    async uploadAvatar(file: File): Promise<void> {
      const formData = new FormData()
      formData.append('avatar', file)
      const result = await apiClient.post<ApiResponse<Profile>>('/profile/avatar', formData)
      this.profile = result.data
    },
  },
})
