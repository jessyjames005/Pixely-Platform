// Pinia store for the signed-in user's own security settings:
// password change and two-factor authentication management.
import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import { useAuthStore } from './auth.store'

export interface TwoFactorStatus {
  enabled: boolean
  pendingConfirmation: boolean
  recoveryCodesRemaining: number
}

export interface TwoFactorEnrolment {
  secret: string
  otpauthUri: string
}

export interface ChangePasswordPayload {
  currentPassword: string
  password: string
  passwordConfirmation: string
}

interface MetaDocument<T> {
  meta: T
}

interface SecurityState {
  status: TwoFactorStatus | null
}

export const useSecurityStore = defineStore('security', {
  state: (): SecurityState => ({
    status: null,
  }),

  actions: {
    async fetchTwoFactorStatus(): Promise<TwoFactorStatus> {
      const document = await apiClient.get<
        MetaDocument<{ enabled: boolean; pending_confirmation: boolean; recovery_codes_remaining: number }>
      >('/auth/two-factor')

      const status: TwoFactorStatus = {
        enabled: document.meta.enabled,
        pendingConfirmation: document.meta.pending_confirmation,
        recoveryCodesRemaining: document.meta.recovery_codes_remaining,
      }
      this.status = status
      return status
    },

    // Starts enrolment. Two-factor stays inactive until confirmTwoFactor().
    async enableTwoFactor(password: string): Promise<TwoFactorEnrolment> {
      const document = await apiClient.post<MetaDocument<{ secret: string; otpauth_uri: string }>>(
        '/auth/two-factor',
        { password },
      )
      await this.fetchTwoFactorStatus()
      return { secret: document.meta.secret, otpauthUri: document.meta.otpauth_uri }
    },

    // Confirms enrolment; resolves with the recovery codes (shown only once).
    async confirmTwoFactor(code: string): Promise<string[]> {
      const document = await apiClient.post<MetaDocument<{ recovery_codes: string[] }>>(
        '/auth/two-factor/confirm',
        { code },
      )
      await this.fetchTwoFactorStatus()
      this.syncAuthenticatedUser(true)
      return document.meta.recovery_codes
    },

    async regenerateRecoveryCodes(password: string): Promise<string[]> {
      const document = await apiClient.post<MetaDocument<{ recovery_codes: string[] }>>(
        '/auth/two-factor/recovery-codes',
        { password },
      )
      await this.fetchTwoFactorStatus()
      return document.meta.recovery_codes
    },

    async disableTwoFactor(password: string): Promise<void> {
      await apiClient.delete<void>('/auth/two-factor', { password })
      await this.fetchTwoFactorStatus()
      this.syncAuthenticatedUser(false)
    },

    async changePassword(payload: ChangePasswordPayload): Promise<void> {
      await apiClient.put<void>('/auth/password', {
        current_password: payload.currentPassword,
        password: payload.password,
        password_confirmation: payload.passwordConfirmation,
      })
    },

    // Keeps the auth store's copy of the user in step with the server.
    syncAuthenticatedUser(twoFactorEnabled: boolean): void {
      const auth = useAuthStore()
      if (auth.user) auth.user = { ...auth.user, two_factor_enabled: twoFactorEnabled }
    },
  },
})
