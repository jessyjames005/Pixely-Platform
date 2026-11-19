import type { Locator, Page } from '@playwright/test'
import { BasePage } from './BasePage.pom'

export class UserSpacePage extends BasePage {
  readonly mainHeading: Locator
  readonly favoritesHeading: Locator
  readonly historyHeading: Locator
  readonly profileLink: Locator
  readonly preferencesLink: Locator
  readonly favoritesLink: Locator
  readonly historyLink: Locator

  constructor(page: Page) {
    super(page)
    this.mainHeading = page.getByRole('heading', { name: 'My Space' })
    this.favoritesHeading = page.getByRole('heading', { name: 'Favorites' })
    this.historyHeading = page.getByRole('heading', { name: 'History' })
    this.profileLink = page.getByRole('link', { name: /Profile/i }).first()
    this.preferencesLink = page.getByRole('link', { name: /Preferences/i }).first()
    this.favoritesLink = page.getByRole('link', { name: 'Favorites', exact: true })
    this.historyLink = page.getByRole('link', { name: 'History', exact: true })
  }

  async goto(): Promise<void> {
    await this.open('/account')
  }

  async gotoFavorites(): Promise<void> {
    await this.open('/account/favorites')
  }

  async gotoHistory(): Promise<void> {
    await this.open('/account/history')
  }

  async openProfile(): Promise<void> {
    await this.profileLink.click()
  }

  async openPreferences(): Promise<void> {
    await this.preferencesLink.click()
  }

  async openHistory(): Promise<void> {
    await this.historyLink.click()
  }
}
