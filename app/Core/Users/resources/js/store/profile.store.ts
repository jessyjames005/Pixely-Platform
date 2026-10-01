// Pinia store for the current user's own self-service profile
// (name, bio, timezone, avatar) — distinct from users.store.ts,
// which is admin-only management of other users.
import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
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
      this.profile = await apiClient.getResource<Omit<Profile, 'id' | 'type'>>('/profile')
    },

    async updateProfile(payload: UpdateProfilePayload): Promise<void> {
      this.profile = await apiClient.putResource<Omit<Profile, 'id' | 'type'>>(
        '/profile',
        'users',
        payload,
        this.profile?.id,
      )
    },

    async uploadAvatar(file: File): Promise<void> {
      const formData = new FormData()
      formData.append('avatar', file)
      this.profile = await apiClient.postFormResource<Omit<Profile, 'id' | 'type'>>('/profile/avatar', formData)
    },
  },
})
