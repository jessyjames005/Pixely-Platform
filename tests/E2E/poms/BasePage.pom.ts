import type { Locator, Page } from '@playwright/test'

export abstract class BasePage {
  protected constructor(protected readonly page: Page) {}

  async open(path: string): Promise<void> {
    await this.page.goto(path)
  }

  get currentUrl(): string {
    return this.page.url()
  }

  protected byTestId(testId: string): Locator {
    return this.page.getByTestId(testId)
  }
}
