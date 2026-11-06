import type { Locator, Page } from '@playwright/test'
import { BasePage } from './BasePage.pom'

export class AuthRecoveryPage extends BasePage {
  readonly rememberMe: Locator
  readonly forgotPasswordLink: Locator
  readonly resetEmail: Locator
  readonly sendResetLinkButton: Locator
  readonly resetLinkSent: Locator
  readonly newPassword: Locator
  readonly confirmPassword: Locator
  readonly resetPasswordButton: Locator
  readonly invalidResetMessage: Locator

  constructor(page: Page) {
    super(page)
    this.rememberMe = page.getByLabel('Remember me')
    this.forgotPasswordLink = page.getByRole('link', { name: 'Forgot your password?' })
    this.resetEmail = page.locator('input[type="email"]')
    this.sendResetLinkButton = page.getByRole('button', { name: 'Send reset link' })
    this.resetLinkSent = page.getByTestId('reset-link-sent')
    this.newPassword = page.getByLabel('New password', { exact: true })
    this.confirmPassword = page.getByLabel('Confirm new password')
    this.resetPasswordButton = page.getByRole('button', { name: 'Reset password' })
    this.invalidResetMessage = page.getByText('This password reset link is invalid or has expired.')
  }

  async gotoLogin(): Promise<void> {
    await this.open('/login')
  }

  async openForgotPassword(): Promise<void> {
    await this.forgotPasswordLink.click()
  }

  async requestReset(email: string): Promise<void> {
    await this.resetEmail.fill(email)
    await this.sendResetLinkButton.click()
  }

  async gotoResetPassword(token?: string, email?: string): Promise<void> {
    const query = token && email
      ? `?token=${encodeURIComponent(token)}&email=${encodeURIComponent(email)}`
      : ''
    await this.open(`/reset-password${query}`)
  }

  async submitNewPassword(password: string): Promise<void> {
    await this.newPassword.fill(password)
    await this.confirmPassword.fill(password)
    await this.resetPasswordButton.click()
  }
}
