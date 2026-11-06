import type { Locator, Page } from '@playwright/test'
import { BasePage } from './BasePage.pom'

export class WebsiteAdminPage extends BasePage {
  readonly websiteNavigation: Locator
  readonly newPageButton: Locator
  readonly newPageHeading: Locator
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
    this.newPageHeading = page.getByRole('heading', { name: 'New page' })
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
    await this.pageTitle.fill(title)
    await this.saveButton.click()
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
    await this.saveButton.click()
  }

  createdMenuName(name: string): Locator {
    return this.page.getByText(name, { exact: true })
  }

  createdMenuItem(title: string): Locator {
    return this.page.getByText(title, { exact: true })
  }
}
