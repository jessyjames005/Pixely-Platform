import type { JsonApiModel } from '@shared/types/api'

// Permission resource shape
export type Permission = JsonApiModel<{
  name: string
  isCore?: boolean
}>

// Role resource shape, including its assigned permissions
export type Role = JsonApiModel<{
  name: string
}> & {
  permissions: Permission[]
}

export interface RoleUser {
  id: string
  name: string
  email: string
  role: string
}

// Payload accepted when creating or updating a role
export interface RolePayload {
  name?: string
  permissions?: string[]
}

// Payload for creating a permission
export interface PermissionPayload {
  name: string
  isCore?: boolean
}
