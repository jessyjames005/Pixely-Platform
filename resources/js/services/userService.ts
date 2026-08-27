// API service for Core user management
import { apiClient } from './apiClient'
import type { ApiCollectionResponse, ApiResponse } from '../types/api'

// Shape of a User resource as returned by the API
export interface User {
  id: number
  name: string
  email: string
}

// Payload accepted when creating a user
export interface CreateUserPayload {
  name: string
  email: string
  password: string
}

// Payload accepted when updating a user (password optional)
export interface UpdateUserPayload {
  name?: string
  email?: string
  password?: string
}

// Fetches a paginated list of users
export function listUsers(page = 1, perPage = 20): Promise<ApiCollectionResponse<User>> {
  return apiClient.get<ApiCollectionResponse<User>>('/users', {
    page,
    per_page: perPage,
  })
}

// Creates a new user
export function createUser(payload: CreateUserPayload): Promise<ApiResponse<User>> {
  return apiClient.post<ApiResponse<User>>('/users', payload)
}

// Updates an existing user
export function updateUser(userId: number, payload: UpdateUserPayload): Promise<ApiResponse<User>> {
  return apiClient.put<ApiResponse<User>>(`/users/${userId}`, payload)
}

// Deletes a user
export function deleteUser(userId: number): Promise<void> {
  return apiClient.delete<void>(`/users/${userId}`)
}
