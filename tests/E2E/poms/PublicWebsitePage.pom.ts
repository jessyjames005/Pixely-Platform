import type { Locator, Page } from '@playwright/test'
import { BasePage } from './BasePage.pom'

export class PublicWebsitePage extends BasePage {
  readonly brand: Locator
  readonly pageHeading: Locator
  readonly aboutLink: Locator
  readonly contactLink: Locator
  readonly homeLink: Locator
  readonly notFoundMessage: Locator
  readonly notFoundDetail: Locator

  constructor(page: Page) {
    super(page)
    this.brand = page.locator('.website-brand')
    this.pageHeading = page.getByRole('heading', { level: 1 })
    this.aboutLink = page.getByRole('link', { name: 'About', exact: true })
    this.contactLink = page.getByRole('link', { name: 'Contact', exact: true })
    this.homeLink = page.getByRole('link', { name: 'Home', exact: true })
    this.notFoundMessage = page.getByText('Page not found')
    this.notFoundDetail = page.getByText('The requested resource could not be found.')
  }

  async gotoHome(): Promise<void> {
    await this.open('/')
  }

  async gotoAbout(): Promise<void> {
    await this.open('/about')
  }

  async gotoUnknownPage(): Promise<void> {
    await this.open('/this-page-does-not-exist')
  }

  async openAbout(): Promise<void> {
    await this.aboutLink.click()
  }

  async openContact(): Promise<void> {
    await this.contactLink.click()
  }

  async openHome(): Promise<void> {
    await this.homeLink.click()
  }
}
