// Shared, module-level authentication state for the administration SPA.
// Being module-level (not per-component), it acts as a lightweight
// singleton store shared across the whole app.
import { ref, readonly } from 'vue'
import { login as loginRequest, logout as logoutRequest, me as meRequest, type User } from '../services/authService'
import { fetchCsrfCookie } from '../services/apiClient'

const user = ref<User | null>(null)
const initialized = ref(false)

// Fetches the current authenticated user (if any). Called once by the
// router guard before the first navigation, and safe to call again.
async function checkAuth(): Promise<User | null> {
  try {
    const result = await meRequest()
    user.value = result.data
  } catch {
    user.value = null
  } finally {
    initialized.value = true
  }

  return user.value
}

// Logs a user in: fetches the CSRF cookie, then authenticates via Sanctum.
async function login(email: string, password: string): Promise<void> {
  await fetchCsrfCookie()
  const result = await loginRequest(email, password)
  user.value = result.data
}

// Logs the current user out and clears local state.
async function logout(): Promise<void> {
  await logoutRequest()
  user.value = null
}

export function useAuth() {
  return {
    user: readonly(user),
    initialized: readonly(initialized),
    checkAuth,
    login,
    logout,
  }
}
