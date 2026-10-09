// Pinia store for the Gallery extension: list, upload, delete, pagination
import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import type { PaginationMeta } from '@shared/types/api'
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
      const result = await apiClient.getCollection<Omit<Photo, 'id'>>('/photos', {
        'page[number]': page,
        'page[size]': perPage,
      })
      this.photos = result.resources
      this.meta = result.meta ?? null
    },

    async uploadPhoto(title: string, image: File): Promise<Photo> {
      const formData = new FormData()
      if (title) {
        formData.append('title', title)
      }
      formData.append('image', image)

      const result = await apiClient.postFormResource<Omit<Photo, 'id'>>('/photos/upload', formData)
      if (!result) throw new Error('The photo upload response did not include a resource.')
      return result
    },

    async deletePhoto(photoId: string): Promise<void> {
      await apiClient.delete<void>(`/photos/${photoId}`)
    },
  },
})
