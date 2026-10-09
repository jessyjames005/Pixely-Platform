// Authenticated user shape returned by the auth endpoints
export interface User {
  id: string
  name: string
  email: string
  permissions: string[]
  roles: string[]
  two_factor_enabled?: boolean
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
