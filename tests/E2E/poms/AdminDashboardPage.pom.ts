import type { Locator, Page } from '@playwright/test'
import { BasePage } from './BasePage.pom'

export class AdminDashboardPage extends BasePage {
  readonly heading: Locator
  readonly navigation: Locator
  readonly collapseNavigationButton: Locator
  readonly expandNavigationButton: Locator
  readonly extensionsMenu: Locator
  readonly manageExtensionsLink: Locator
  readonly galleryLink: Locator

  constructor(page: Page) {
    super(page)
    this.heading = page.getByRole('heading', { name: 'Dashboard' })
    this.navigation = page.locator('.v-navigation-drawer')
    this.collapseNavigationButton = page.getByRole('button', { name: 'Collapse navigation' })
    this.expandNavigationButton = page.getByRole('button', { name: 'Expand navigation' })
    this.extensionsMenu = this.navigation.getByText('Extensions', { exact: true })
    this.manageExtensionsLink = this.navigation.getByRole('link', { name: 'Manage extensions' })
    this.galleryLink = this.navigation.getByRole('link', { name: 'Gallery' })
  }

  async goto(): Promise<void> {
    await this.open('/admin')
  }

  async collapseNavigation(): Promise<void> {
    await this.collapseNavigationButton.click()
  }

  async expandNavigation(): Promise<void> {
    await this.expandNavigationButton.click()
  }

  async toggleNavigationWithKeyboard(): Promise<void> {
    await this.collapseNavigationButton.focus()
    await this.page.keyboard.press('Space')
  }

  async openExtensionsMenu(): Promise<void> {
    await this.extensionsMenu.click()
  }
}
