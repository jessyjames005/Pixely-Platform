import type { Locator, Page } from '@playwright/test'
import { BasePage } from './BasePage.pom'

export class LoginPage extends BasePage {
  readonly email: Locator
  readonly password: Locator
  readonly rememberMe: Locator
  readonly submitButton: Locator
  readonly forgotPasswordLink: Locator
  readonly errorAlert: Locator

  constructor(page: Page) {
    super(page)
    this.email = page.getByTestId('login-email').locator('input')
    this.password = page.getByTestId('login-password').locator('input')
    this.rememberMe = page.getByLabel('Remember me')
    this.submitButton = page.getByTestId('login-submit')
    this.forgotPasswordLink = page.getByRole('link', { name: 'Forgot your password?' })
    this.errorAlert = page.getByTestId('login-error')
  }

  async goto(): Promise<void> {
    await this.open('/login')
  }

  async login(email: string, password: string): Promise<number> {
    const csrfResponse = this.page.waitForResponse('/sanctum/csrf-cookie')
    await this.page.evaluate(async () => fetch('/sanctum/csrf-cookie', { credentials: 'include' }))
    const response = await csrfResponse
    await this.email.fill(email)
    await this.password.fill(password)
    await this.submitButton.click()
    return response.status()
  }
}
