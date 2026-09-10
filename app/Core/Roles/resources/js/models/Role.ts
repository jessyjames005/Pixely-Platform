// Permission resource shape
export interface Permission {
  id: number
  name: string
  guard_name?: string
  is_core?: boolean
  created_at?: string
}

// Role resource shape, including its assigned permissions
export interface Role {
  id: number
  name: string
  permissions: Permission[]
  users_count?: number
  created_at?: string
}

// Payload accepted when creating or updating a role
export interface RolePayload {
  name?: string
  permissions?: string[]
}

// Payload for creating a permission
export interface PermissionPayload {
  name: string
  is_core?: boolean
}
