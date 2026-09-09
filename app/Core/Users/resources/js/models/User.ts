// User resource shape as returned by the API
export interface User {
  id: number
  name: string
  email: string
  role: string | null
}

// Current user's own profile, self-service (distinct from admin User management)
export interface Profile {
  id: number
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
