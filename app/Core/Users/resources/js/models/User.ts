// User resource shape as returned by the API
export interface User {
  id: string
  type: string
  name: string
  email: string
  role?: string | null
  roles?: string[]
  permissions?: string[]
}

// Current user's own profile, self-service (distinct from admin User management)
export interface Profile {
  id: string
  type: string
  name: string
  email: string
  bio: string | null
  timezone: string
  avatar_url: string | null
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

export interface UpdateProfilePayload {
  name?: string
  bio?: string | null
  timezone?: string
}
