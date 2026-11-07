import type { Locator, Page } from '@playwright/test'
import { BasePage } from './BasePage.pom'

export class WebsiteAdminPage extends BasePage {
  readonly websiteNavigation: Locator
  readonly newPageButton: Locator
  readonly pageTitle: Locator
  readonly newMenuButton: Locator
  readonly menuName: Locator
  readonly menuCode: Locator
  readonly addMenuItemButton: Locator
  readonly menuItemTitle: Locator
  readonly menuItemTarget: Locator
  readonly saveButton: Locator
  readonly pagesHeading: Locator

  constructor(page: Page) {
    super(page)
    this.websiteNavigation = page.locator('.v-navigation-drawer').getByText('Website', { exact: true })
    this.newPageButton = page.getByRole('button', { name: 'New page' })
    this.pageTitle = page.getByLabel('Title')
    this.newMenuButton = page.getByRole('button', { name: 'New menu' })
    this.menuName = page.getByLabel('Name')
    this.menuCode = page.getByLabel('Code')
    this.addMenuItemButton = page.getByRole('button', { name: 'Add item' })
    this.menuItemTitle = page.getByLabel('Title').last()
    this.menuItemTarget = page.getByLabel('Target URL').last()
    this.saveButton = page.getByRole('button', { name: 'Save' })
    this.pagesHeading = page.getByRole('heading', { name: 'Website pages' })
  }

  async gotoPages(): Promise<void> {
    await this.open('/admin/website/pages')
  }

  async gotoMenus(): Promise<void> {
    await this.open('/admin/website/menus')
  }

  async openPagesFromNavigation(): Promise<void> {
    await this.websiteNavigation.click()
    await this.page.getByRole('link', { name: 'Pages' }).click()
  }

  async createPage(title: string): Promise<void> {
    await this.newPageButton.click()
    await this.pageTitle.waitFor({ state: 'visible' })
    await this.pageTitle.fill(title)

    // Wait for the write request so backend errors surface immediately instead
    // of turning into a long timeout on the subsequent list assertion.
    const saveResponse = this.page.waitForResponse((response) => {
      const request = response.request()
      return new URL(response.url()).pathname.endsWith('/api/v1/website/pages')
        && request.method() === 'POST'
    }, { timeout: 15_000 })

    await this.saveButton.click()
    const response = await saveResponse
    if (!response.ok()) {
      throw new Error(`Creating website page failed (${response.status()}): ${await response.text()}`)
    }
  }

  createdPageTitle(title: string): Locator {
    return this.page.getByText(title, { exact: true })
  }

  createdPageSlug(slug: string): Locator {
    return this.page.getByText(slug, { exact: true })
  }

  async createMenu(name: string, code: string, itemTitle: string, target: string): Promise<void> {
    await this.newMenuButton.click()
    await this.menuName.fill(name)
    await this.menuCode.fill(code)
    await this.addMenuItemButton.click()
    await this.menuItemTitle.fill(itemTitle)
    await this.menuItemTarget.fill(target)

    // Fail at the API boundary with its response body rather than waiting for
    // a menu card that can never appear after a rejected write.
    const saveResponse = this.page.waitForResponse((response) => {
      const request = response.request()
      return new URL(response.url()).pathname.endsWith('/api/v1/website/menus')
        && request.method() === 'POST'
    }, { timeout: 15_000 })

    await this.saveButton.click()
    const response = await saveResponse
    if (!response.ok()) {
      throw new Error(`Creating website menu failed (${response.status()}): ${await response.text()}`)
    }
  }

  createdMenuName(name: string): Locator {
    return this.page.getByText(name, { exact: true })
  }

  createdMenuItem(title: string): Locator {
    return this.page.getByText(title, { exact: true })
  }
}
