// User resource shape as returned by the API
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
