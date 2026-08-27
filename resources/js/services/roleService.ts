// API service for Core role and permission management
import { apiClient } from './apiClient'
import type { ApiCollectionResponse, ApiResponse } from '../types/api'

// Shape of a Permission resource
export interface Permission {
  id: number
  name: string
}

// Shape of a Role resource, including its assigned permissions
export interface Role {
  id: number
  name: string
  permissions: Permission[]
}

// Payload accepted when creating or updating a role
export interface RolePayload {
  name?: string
  permissions?: string[]
}

// Fetches all roles with their permissions
export function listRoles(): Promise<ApiCollectionResponse<Role>> {
  return apiClient.get<ApiCollectionResponse<Role>>('/roles')
}

// Fetches all available permissions
export function listPermissions(): Promise<ApiCollectionResponse<Permission>> {
  return apiClient.get<ApiCollectionResponse<Permission>>('/permissions')
}

// Creates a new role
export function createRole(payload: RolePayload): Promise<ApiResponse<Role>> {
  return apiClient.post<ApiResponse<Role>>('/roles', payload)
}

// Updates an existing role's name and/or permissions
export function updateRole(roleId: number, payload: RolePayload): Promise<ApiResponse<Role>> {
  return apiClient.put<ApiResponse<Role>>(`/roles/${roleId}`, payload)
}

// Deletes a role
export function deleteRole(roleId: number): Promise<void> {
  return apiClient.delete<void>(`/roles/${roleId}`)
}

// Assigns a role to a user (replacing any previously assigned role)
export function assignRole(userId: number, role: string): Promise<ApiResponse<unknown>> {
  return apiClient.post<ApiResponse<unknown>>('/roles/assign', { user_id: userId, role })
}
