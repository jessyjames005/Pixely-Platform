// Pinia store for the Gallery extension: list, upload, delete, pagination
import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import type { ApiCollectionResponse, ApiResponse, PaginationMeta } from '@shared/types/api'
import type { Photo } from '../models/Photo'

interface GalleryState {
  photos: Photo[]
  meta: PaginationMeta | null
}

export const useGalleryStore = defineStore('gallery', {
  state: (): GalleryState => ({
    photos: [],
    meta: null,
  }),

  actions: {
    async fetchPhotos(page = 1, perPage = 20): Promise<void> {
      const result = await apiClient.get<ApiCollectionResponse<Photo>>('/gallery', {
        page,
        per_page: perPage,
      })
      this.photos = result.data
      this.meta = result.meta
    },

    async uploadPhoto(title: string, image: File): Promise<Photo> {
      const formData = new FormData()
      if (title) {
        formData.append('title', title)
      }
      formData.append('image', image)

      const result = await apiClient.post<ApiResponse<Photo>>('/gallery/upload', formData)
      return result.data
    },

    async deletePhoto(photoId: number): Promise<void> {
      await apiClient.delete<void>(`/gallery/${photoId}`)
    },
  },
})
