import { expect, test } from '@playwright/test'
import { ADMIN_PERSONA } from './fixtures/personas'
import { LoginPage } from './poms/LoginPage.pom'
import { UserSpacePage } from './poms/UserSpacePage.pom'

test.describe('User Space engagement', () => {
  test.beforeEach(async ({ page }) => {
    const login = new LoginPage(page)
    await login.goto()
    await login.login(ADMIN_PERSONA.email, ADMIN_PERSONA.password)
    await expect(page).toHaveURL(/\/admin|\/account/)
    await new UserSpacePage(page).gotoFavorites()
  })

  test('navigates between favorites and history', async ({ page }) => {
    const userSpace = new UserSpacePage(page)
    await expect(userSpace.favoritesHeading).toBeVisible()
    await userSpace.openHistory()
    await expect(page).toHaveURL(/\/account\/history/)
    await expect(userSpace.historyHeading).toBeVisible()
  })

  test('keeps user-space engagement behind authentication', async ({ browser }) => {
    const context = await browser.newContext()
    const guest = await context.newPage()
    await new UserSpacePage(guest).gotoFavorites()
    await expect(guest).toHaveURL(/\/login/)
    await context.close()
  })
})
