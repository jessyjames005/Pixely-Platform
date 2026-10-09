// Maps the API's stable authentication error codes to translated messages.
// Unknown codes (including validation errors) fall back to the API's own text.
import type { ApiClientError } from '@shared/services/apiClient'
import { translate as t } from '@shared/plugins/i18n'

const MESSAGES: Record<string, { key: string; fallback: string }> = {
  INVALID_CREDENTIALS: { key: 'core.auth.msg.invalid_credentials', fallback: 'The provided credentials are incorrect.' },
  ACCOUNT_DISABLED: { key: 'core.auth.msg.account_disabled', fallback: 'This account has been disabled.' },
  INVALID_TWO_FACTOR_CODE: {
    key: 'core.auth.msg.invalid_two_factor_code',
    fallback: 'The authentication code is invalid or has already been used.',
  },
  TWO_FACTOR_SESSION_EXPIRED: {
    key: 'core.auth.msg.two_factor_session_expired',
    fallback: 'The sign-in session expired. Sign in again.',
  },
  TWO_FACTOR_ALREADY_ENABLED: {
    key: 'core.auth.msg.two_factor_already_enabled',
    fallback: 'Two-factor authentication is already enabled.',
  },
  INVALID_PASSWORD: { key: 'core.auth.msg.invalid_password', fallback: 'The provided password is incorrect.' },
  INVALID_RESET_TOKEN: {
    key: 'core.auth.msg.invalid_reset_token',
    fallback: 'This password reset link is invalid or has expired.',
  },
}

export function authErrorMessage(error: ApiClientError | null | undefined): string {
  if (!error) return ''

  if (error.code === 'TOO_MANY_ATTEMPTS' || error.code === 'TOO_MANY_REQUESTS') {
    const retryAfter = Number(error.errors[0]?.meta?.retry_after ?? 60)
    return t('core.auth.msg.too_many_attempts', 'Too many attempts. Try again in :seconds seconds.', {
      seconds: retryAfter,
    })
  }

  const entry = MESSAGES[error.code]
  return entry ? t(entry.key, entry.fallback) : error.message
}
