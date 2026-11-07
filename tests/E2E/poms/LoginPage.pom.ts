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
    await this.email.fill(email)
    await this.password.fill(password)

    // Observe the real authentication request, not the preceding CSRF-cookie
    // response (which is normally 204 even when the credentials are invalid).
    // The application login store obtains the CSRF cookie itself.
    const loginResponse = this.page.waitForResponse(
      (response) => {
        const request = response.request()
        return new URL(response.url()).pathname.endsWith('/api/v1/auth/login')
          && request.method() === 'POST'
      },
      { timeout: 15_000 },
    )

    await this.submitButton.click()

    return (await loginResponse).status()
  }
}
