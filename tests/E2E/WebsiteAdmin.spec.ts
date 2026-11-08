import { expect, test } from '@playwright/test'
import { ADMIN_PERSONA } from './fixtures/personas'
import { LoginPage } from './poms/LoginPage.pom'
import { WebsiteAdminPage } from './poms/WebsiteAdminPage.pom'

test.describe('Website administration', () => {
  test.beforeEach(async ({ page }) => {
    const login = new LoginPage(page)
    await login.goto()
    expect(await login.login(ADMIN_PERSONA.email, ADMIN_PERSONA.password)).toBe(200)
    await expect(page).toHaveURL(/\/admin/)
  })

  test('opens the Website Pages screen from administration navigation', async ({ page }) => {
    const website = new WebsiteAdminPage(page)
    await website.openPagesFromNavigation()
    await expect(page).toHaveURL(/\/admin\/website\/pages$/)
    await expect(website.pagesHeading).toBeVisible()
    await expect(website.newPageButton).toBeVisible()
  })

  test('creates a website page from the admin UI', async ({ page }) => {
    const title = `Playwright About ${Date.now()}-${Math.random().toString(36).slice(2, 8)}`
    const website = new WebsiteAdminPage(page)
    await website.gotoPages()
    await website.createPage(title)

    await expect(website.createdPageTitle(title)).toBeVisible()
    await expect(website.createdPageSlug(title.toLowerCase().replaceAll(' ', '-'))).toBeVisible()
  })

  test('creates a website menu from the admin UI', async ({ page }) => {
    const uniqueSuffix = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`
    const name = `Playwright Main ${uniqueSuffix}`
    const code = `playwright-main-${uniqueSuffix}`
    const website = new WebsiteAdminPage(page)
    await website.gotoMenus()
    await website.createMenu(name, code, 'Home', '/')

    await expect(website.createdMenuName(name)).toBeVisible()
    await expect(website.createdMenuItem(name, 'Home')).toBeVisible()
  })
})
