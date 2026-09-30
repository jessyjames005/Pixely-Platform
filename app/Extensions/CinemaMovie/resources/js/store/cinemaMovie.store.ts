// Pinia store for the CinemaMovie extension.
import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import type { ApiCollectionResponse } from '@shared/types/api'
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
      const result = await apiClient.get<ApiCollectionResponse<CinemaMovieItem>>('/cinema-movie')
      this.items = result.data
    },
  },
})
