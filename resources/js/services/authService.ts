// API service for Core authentication (session-based via Sanctum SPA)
import { apiClient } from './apiClient'
import type { ApiResponse } from '../types/api'

// Authenticated user shape returned by the auth endpoints
export interface User {
  id: number
  name: string
  email: string
}

// Authenticates a user and starts a Sanctum session
export function login(email: string, password: string): Promise<ApiResponse<User>> {
  return apiClient.post<ApiResponse<User>>('/auth/login', { email, password })
}

// Ends the current session
export function logout(): Promise<void> {
  return apiClient.post<void>('/auth/logout')
}

// Returns the currently authenticated user, or rejects if not authenticated
export function me(): Promise<ApiResponse<User>> {
  return apiClient.get<ApiResponse<User>>('/auth/me')
}
