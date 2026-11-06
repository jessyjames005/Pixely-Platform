import type { Locator, Page } from '@playwright/test'
import { BasePage } from './BasePage.pom'

export class CinemaMoviePage extends BasePage {
  readonly pageHeading: Locator
  readonly catalogueHeading: Locator

  constructor(page: Page) {
    super(page)
    this.pageHeading = page.getByRole('heading', { name: /Cinema movies|Films/ })
    this.catalogueHeading = page.getByText(/Movie catalogue|Catalogue des films/, { exact: true })
  }

  async goto(): Promise<void> {
    await this.open('/admin/cinema-movie')
  }
}
