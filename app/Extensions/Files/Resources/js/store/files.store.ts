// Pinia store for the standalone Files extension: list, upload, delete, pagination.
import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import type { PaginationMeta } from '@shared/types/api'
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
      const result = await apiClient.getCollection<Omit<FileRecord, 'id'>>('/files', {
        'page[number]': page,
        'page[size]': perPage,
      })
      this.files = result.resources
      this.meta = result.meta ?? null
    },

    async uploadFile(file: globalThis.File): Promise<FileRecord> {
      const formData = new FormData()
      formData.append('file', file)

      const result = await apiClient.postFormResource<Omit<FileRecord, 'id'>>('/files', formData)
      if (!result) throw new Error('The file upload response did not include a resource.')
      return result
    },

    async deleteFile(fileId: string): Promise<void> {
      await apiClient.delete<void>(`/files/${fileId}`)
    },
  },
})
