import { expect, test } from '@playwright/test'
import { ADMIN_PERSONA } from './fixtures/personas'
import { LoginPage } from './poms/LoginPage.pom'
import { UserSpacePage } from './poms/UserSpacePage.pom'

test.describe('User Space', () => {
  test('guest is redirected to login', async ({ page }) => {
    const userSpace = new UserSpacePage(page)
    await userSpace.goto()
    await expect(page).toHaveURL(/\/login/)
  })

  test('authenticated user can navigate between dashboard, profile and preferences', async ({ page }) => {
    const login = new LoginPage(page)
    await login.goto()
    await login.login(ADMIN_PERSONA.email, ADMIN_PERSONA.password)
    await expect(page).toHaveURL(/\/admin/)

    const userSpace = new UserSpacePage(page)
    await userSpace.goto()
    await expect(page).toHaveURL(/\/account$/)
    await expect(userSpace.mainHeading).toBeVisible()

    await userSpace.openProfile()
    await expect(page).toHaveURL(/\/account\/profile$/)

    await userSpace.openPreferences()
    await expect(page).toHaveURL(/\/account\/preferences$/)
  })
})
