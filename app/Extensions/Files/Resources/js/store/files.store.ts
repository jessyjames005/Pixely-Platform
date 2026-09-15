// Pinia store for the standalone Files extension: list, upload, delete, pagination.
import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import type { ApiCollectionResponse, ApiResponse, PaginationMeta } from '@shared/types/api'
import type { FileRecord } from '../models/FileRecord'

interface FilesState {
  files: FileRecord[]
  meta: PaginationMeta | null
}

export const useFilesStore = defineStore('files', {
  state: (): FilesState => ({
    files: [],
    meta: null,
  }),

  actions: {
    async fetchFiles(page = 1, perPage = 20): Promise<void> {
      const result = await apiClient.get<ApiCollectionResponse<FileRecord>>('/files', {
        page,
        per_page: perPage,
      })
      this.files = result.data
      this.meta = result.meta
    },

    async uploadFile(file: globalThis.File): Promise<FileRecord> {
      const formData = new FormData()
      formData.append('file', file)

      const result = await apiClient.post<ApiResponse<FileRecord>>('/files', formData)
      return result.data
    },

    async deleteFile(fileId: number): Promise<void> {
      await apiClient.delete<void>(`/files/${fileId}`)
    },
  },
})
