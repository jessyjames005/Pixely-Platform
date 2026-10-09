// Unit tests for the auth store's login flow: remember-me, the two-factor
// challenge branch, and the password recovery calls.
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { apiClient, fetchCsrfCookie } from '@shared/services/apiClient'
import { useAuthStore } from './auth.store'

vi.mock('@shared/services/apiClient', () => ({
  apiClient: {
    get: vi.fn(),
    getResource: vi.fn(),
    post: vi.fn(),
  },
  fetchCsrfCookie: vi.fn().mockResolvedValue(undefined),
}))

const userDocument = {
  data: {
    type: 'users',
    id: '1',
    attributes: {
      name: 'Jane',
      email: 'jane@example.com',
      permissions: [],
      roles: [],
      two_factor_enabled: false,
    },
  },
}

describe('useAuthStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.mocked(apiClient.post).mockReset()
    vi.mocked(fetchCsrfCookie).mockClear()
  })

  it('logs in and forwards the remember flag', async () => {
    vi.mocked(apiClient.post).mockResolvedValue(userDocument)
    const store = useAuthStore()

    const result = await store.login('jane@example.com', 'secret', true)

    expect(fetchCsrfCookie).toHaveBeenCalledOnce()
    expect(apiClient.post).toHaveBeenCalledWith('/auth/login', {
      email: 'jane@example.com',
      password: 'secret',
      remember: true,
    })
    expect(result).toEqual({ twoFactorRequired: false })
    expect(store.user?.email).toBe('jane@example.com')
  })

  it('defaults remember to false', async () => {
    vi.mocked(apiClient.post).mockResolvedValue(userDocument)

    await useAuthStore().login('jane@example.com', 'secret')

    expect(apiClient.post).toHaveBeenCalledWith('/auth/login', expect.objectContaining({ remember: false }))
  })

  it('does not set a user when the API asks for the second factor', async () => {
    vi.mocked(apiClient.post).mockResolvedValue({ meta: { two_factor_required: true } })
    const store = useAuthStore()

    const result = await store.login('jane@example.com', 'secret')

    expect(result).toEqual({ twoFactorRequired: true })
    expect(store.user).toBeNull()
  })

  it('completes the challenge with an authenticator code', async () => {
    vi.mocked(apiClient.post).mockResolvedValue(userDocument)
    const store = useAuthStore()

    await store.completeTwoFactorChallenge({ code: '123456' })

    expect(apiClient.post).toHaveBeenCalledWith('/auth/two-factor-challenge', { code: '123456' })
    expect(store.user?.email).toBe('jane@example.com')
  })

  it('completes the challenge with a recovery code', async () => {
    vi.mocked(apiClient.post).mockResolvedValue(userDocument)

    await useAuthStore().completeTwoFactorChallenge({ recoveryCode: 'abcde-fghij' })

    expect(apiClient.post).toHaveBeenCalledWith('/auth/two-factor-challenge', { recovery_code: 'abcde-fghij' })
  })

  it('requests a reset link', async () => {
    vi.mocked(apiClient.post).mockResolvedValue(undefined)

    await useAuthStore().forgotPassword('jane@example.com')

    expect(fetchCsrfCookie).toHaveBeenCalledOnce()
    expect(apiClient.post).toHaveBeenCalledWith('/auth/forgot-password', { email: 'jane@example.com' })
  })

  it('maps the reset payload to the API field names', async () => {
    vi.mocked(apiClient.post).mockResolvedValue(undefined)

    await useAuthStore().resetPassword({
      token: 'tok',
      email: 'jane@example.com',
      password: 'new-secret-1',
      passwordConfirmation: 'new-secret-1',
    })

    expect(apiClient.post).toHaveBeenCalledWith('/auth/reset-password', {
      token: 'tok',
      email: 'jane@example.com',
      password: 'new-secret-1',
      password_confirmation: 'new-secret-1',
    })
  })
})
