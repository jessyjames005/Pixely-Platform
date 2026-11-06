import { expect, test } from '@playwright/test'
import { ADMIN_PERSONA } from './fixtures/personas'
import { LoginPage } from './poms/LoginPage.pom'
import { UserSpacePage } from './poms/UserSpacePage.pom'

test.describe('User Space security', () => {
    test('redirects guests away from protected engagement pages', async ({ page }) => {
        const userSpace = new UserSpacePage(page)
        await userSpace.gotoFavorites()
        await expect(page).toHaveURL(/\/login/)

        await userSpace.gotoHistory()
        await expect(page).toHaveURL(/\/login/)
    })

    test('keeps favorites and history inside the user surface', async ({ page }) => {
        const login = new LoginPage(page)
        await login.goto()
        await login.login(ADMIN_PERSONA.email, ADMIN_PERSONA.password)
        await expect(page).toHaveURL(/\/admin|\/account/)

        const userSpace = new UserSpacePage(page)
        await userSpace.gotoFavorites()
        await expect(page).toHaveURL(/\/account\/favorites/)
        await expect(userSpace.favoritesHeading).toBeVisible()

        await userSpace.gotoHistory()
        await expect(page).toHaveURL(/\/account\/history/)
        await expect(userSpace.historyHeading).toBeVisible()
    })
})
