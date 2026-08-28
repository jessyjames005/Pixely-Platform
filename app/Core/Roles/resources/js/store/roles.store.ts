// Pinia store for Core role and permission management
import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import type { ApiCollectionResponse, ApiResponse } from '@shared/types/api'
import type { Role, Permission, RolePayload } from '../models/Role'

interface RolesState {
  roles: Role[]
  permissions: Permission[]
}

export const useRolesStore = defineStore('roles', {
  state: (): RolesState => ({
    roles: [],
    permissions: [],
  }),

  actions: {
    async fetchRoles(): Promise<void> {
      const result = await apiClient.get<ApiCollectionResponse<Role>>('/roles')
      this.roles = result.data
    },

    async fetchPermissions(): Promise<void> {
      const result = await apiClient.get<ApiCollectionResponse<Permission>>('/permissions')
      this.permissions = result.data
    },

    async createRole(payload: RolePayload): Promise<Role> {
      const result = await apiClient.post<ApiResponse<Role>>('/roles', payload)
      return result.data
    },

    async updateRole(roleId: number, payload: RolePayload): Promise<Role> {
      const result = await apiClient.put<ApiResponse<Role>>(`/roles/${roleId}`, payload)
      return result.data
    },

    async deleteRole(roleId: number): Promise<void> {
      await apiClient.delete<void>(`/roles/${roleId}`)
    },

    async assignRole(userId: number, role: string): Promise<void> {
      await apiClient.post<ApiResponse<unknown>>('/roles/assign', { user_id: userId, role })
    },
  },
})
