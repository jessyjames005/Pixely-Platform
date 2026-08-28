// Permission resource shape
export interface Permission {
  id: number
  name: string
}

// Role resource shape, including its assigned permissions
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
