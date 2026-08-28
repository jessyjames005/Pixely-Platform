// Pinia store for Core user management: list, create, update, delete
import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import type { ApiCollectionResponse, ApiResponse, PaginationMeta } from '@shared/types/api'
import type { User, CreateUserPayload, UpdateUserPayload } from '../models/User'

interface UsersState {
  users: User[]
  meta: PaginationMeta | null
}

export const useUsersStore = defineStore('users', {
  state: (): UsersState => ({
    users: [],
    meta: null,
  }),

  actions: {
    async fetchUsers(page = 1, perPage = 20): Promise<void> {
      const result = await apiClient.get<ApiCollectionResponse<User>>('/users', {
        page,
        per_page: perPage,
      })
      this.users = result.data
      this.meta = result.meta
    },

    async createUser(payload: CreateUserPayload): Promise<User> {
      const result = await apiClient.post<ApiResponse<User>>('/users', payload)
      return result.data
    },

    async updateUser(userId: number, payload: UpdateUserPayload): Promise<User> {
      const result = await apiClient.put<ApiResponse<User>>(`/users/${userId}`, payload)
      return result.data
    },

    async deleteUser(userId: number): Promise<void> {
      await apiClient.delete<void>(`/users/${userId}`)
    },
  },
})
