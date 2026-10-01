// Pinia store for the CinemaMovie extension.
import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import type { CinemaMovieItem } from '../models/CinemaMovie'

interface CinemaMovieState {
  items: CinemaMovieItem[]
}

export const useCinemaMovieStore = defineStore('cinemaMovie', {
  state: (): CinemaMovieState => ({
    items: [],
  }),

  actions: {
    async fetchItems(): Promise<void> {
      const result = await apiClient.getCollection<object>('/cinema-movie')
      this.items = result.resources.map((item) => ({ ...item, type: 'cinema-movie-items' }))
    },
  },
})
