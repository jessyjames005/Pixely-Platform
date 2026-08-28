// API service for the Gallery extension
import { apiClient } from '@shared/services/apiClient'
import type { ApiCollectionResponse, ApiResponse } from '@shared/types/api'

export interface Photo {
  id: number
  title: string | null
  filename: string
}

export function listGalleryPhotos(page = 1, perPage = 20): Promise<ApiCollectionResponse<Photo>> {
  return apiClient.get<ApiCollectionResponse<Photo>>('/gallery', { page, per_page: perPage })
}

export function deleteGalleryPhoto(photoId: number): Promise<void> {
  return apiClient.delete<void>(`/gallery/${photoId}`)
}

export function uploadGalleryPhoto(title: string, image: File) {
  const formData = new FormData()
  if (title) {
    formData.append('title', title)
  }
  formData.append('image', image)

  return apiClient.post<ApiResponse<Photo>>('/gallery/upload', formData)
}
