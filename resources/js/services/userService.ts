// API service for Core user management
import { apiClient } from '@shared/services/apiClient'
import type { ApiCollectionResponse, ApiResponse } from '@shared/types/api'

export interface User {
  id: number
  name: string
  email: string
}

export interface CreateUserPayload {
  name: string
  email: string
  password: string
}

export interface UpdateUserPayload {
  name?: string
  email?: string
  password?: string
}

export function listUsers(page = 1, perPage = 20): Promise<ApiCollectionResponse<User>> {
  return apiClient.get<ApiCollectionResponse<User>>('/users', { page, per_page: perPage })
}

export function createUser(payload: CreateUserPayload): Promise<ApiResponse<User>> {
  return apiClient.post<ApiResponse<User>>('/users', payload)
}

export function updateUser(userId: number, payload: UpdateUserPayload): Promise<ApiResponse<User>> {
  return apiClient.put<ApiResponse<User>>(`/users/${userId}`, payload)
}

export function deleteUser(userId: number): Promise<void> {
  return apiClient.delete<void>(`/users/${userId}`)
}
