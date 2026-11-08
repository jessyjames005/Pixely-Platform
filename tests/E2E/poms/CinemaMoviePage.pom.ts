import type { Locator, Page } from '@playwright/test'
import { BasePage } from './BasePage.pom'

export class CinemaMoviePage extends BasePage {
  readonly pageHeading: Locator
  readonly catalogueHeading: Locator
  readonly genresHeading: Locator

  constructor(page: Page) {
    super(page)
    this.pageHeading = page.getByRole('heading', { name: /Cinema movies|Films/ })
    this.catalogueHeading = page.getByText(/Movie catalogue|Catalogue des films/, { exact: true })
    this.genresHeading = page.getByTestId('cinema-movie-genres-heading')
  }

  async goto(): Promise<void> {
    await this.open('/admin/cinema-movie')
  }
}
