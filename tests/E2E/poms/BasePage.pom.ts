import type { Locator, Page } from '@playwright/test'

export abstract class BasePage {
  protected constructor(protected readonly page: Page) {}

  async open(path: string): Promise<void> {
    // DOM readiness is sufficient; Playwright locators wait for the actual UI.
    // Waiting for every asset/load event adds time without improving assertions.
    await this.page.goto(path, { waitUntil: 'domcontentloaded' })
  }

  get currentUrl(): string {
    return this.page.url()
  }

  protected byTestId(testId: string): Locator {
    return this.page.getByTestId(testId)
  }
}
