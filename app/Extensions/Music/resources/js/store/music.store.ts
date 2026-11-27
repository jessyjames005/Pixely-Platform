// Pinia store for the Music extension.
import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import type { MusicItem } from '../models/Music'

interface MusicState {
  items: MusicItem[]
}

export const useMusicStore = defineStore('music', {
  state: (): MusicState => ({
    items: [],
  }),

  actions: {
    async fetchItems(): Promise<void> {
      const result = await apiClient.getCollection<MusicItem>('/music')
      this.items = result.resources
    },
  },
})
