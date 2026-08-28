// API service for Core role and permission management
import { apiClient } from '@shared/services/apiClient'
import type { ApiCollectionResponse, ApiResponse } from '@shared/types/api'

export interface Permission {
  id: number
  name: string
}

export interface Role {
  id: number
  name: string
  permissions: Permission[]
}

export interface RolePayload {
  name?: string
  permissions?: string[]
}

export function listRoles(): Promise<ApiCollectionResponse<Role>> {
  return apiClient.get<ApiCollectionResponse<Role>>('/roles')
}

export function listPermissions(): Promise<ApiCollectionResponse<Permission>> {
  return apiClient.get<ApiCollectionResponse<Permission>>('/permissions')
}

export function createRole(payload: RolePayload): Promise<ApiResponse<Role>> {
  return apiClient.post<ApiResponse<Role>>('/roles', payload)
}

export function updateRole(roleId: number, payload: RolePayload): Promise<ApiResponse<Role>> {
  return apiClient.put<ApiResponse<Role>>(`/roles/${roleId}`, payload)
}

export function deleteRole(roleId: number): Promise<void> {
  return apiClient.delete<void>(`/roles/${roleId}`)
}

export function assignRole(userId: number, role: string): Promise<ApiResponse<unknown>> {
  return apiClient.post<ApiResponse<unknown>>('/roles/assign', { user_id: userId, role })
}
