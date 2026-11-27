import type { Locator, Page } from '@playwright/test'
import { BasePage } from './BasePage.pom'

export class MusicPublicPage extends BasePage {
  readonly brand: Locator
  readonly pageHeading: Locator
  readonly decadeCards: Locator
  readonly topChartsHeading: Locator
  readonly topChartsGrid: Locator
  readonly nowPlayingSection: Locator
  readonly playerBar: Locator
  readonly favoriteButtons: Locator

  constructor(page: Page) {
    super(page)
    this.brand = page.getByTestId('zm-brand')
    this.pageHeading = page.getByRole('heading', { level: 1 })
    this.decadeCards = page.locator('.decade-card')
    this.topChartsHeading = page.getByRole('heading', { name: 'Top Charts', exact: true })
    this.topChartsGrid = page.locator('.top-charts-grid .album-card')
    this.nowPlayingSection = page.getByTestId('now-playing-section')
    this.playerBar = page.getByTestId('player-bar')
    this.favoriteButtons = page.locator('.favorite-btn')
  }

  async goto(): Promise<void> {
    await this.open('/pixely-music')
  }
}