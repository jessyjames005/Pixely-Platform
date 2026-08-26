// API service for the Gallery extension
import { apiClient } from "./apiClient";
import type { ApiCollectionResponse, ApiResponse } from '../types/api'

// Shape of a Photo resource as returned by the Gallery API
export interface Photo {
  id: number;
  title: string | null;
  filename: string;
}

// Fetches a paginated list of gallery photos
export function listGalleryPhotos(
  page = 1,
  perPage = 20,
): Promise<ApiCollectionResponse<Photo>> {
  return apiClient.get<ApiCollectionResponse<Photo>>("/gallery", {
    page,
    per_page: perPage,
  });
}

// Deletes a gallery photo by id
export function deleteGalleryPhoto(photoId: number): Promise<void> {
  return apiClient.delete<void>(`/gallery/${photoId}`);
}

// Uploads a new gallery photo (multipart/form-data)
export function uploadGalleryPhoto(title: string, image: File) {
  const formData = new FormData();

  if (title) {
    formData.append("title", title);
  }
  formData.append("image", image);

  return apiClient.post<ApiResponse<Photo>>("/gallery/upload", formData);
}
