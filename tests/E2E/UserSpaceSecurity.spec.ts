import { expect, test } from '@playwright/test'

test.describe('User Space security', () => {
    test('redirects guests away from protected engagement pages', async ({ page }) => {
        await page.goto('/account/favorites')
        await expect(page).toHaveURL(/\/login/)

        await page.goto('/account/history')
        await expect(page).toHaveURL(/\/login/)
    })

    test('keeps favorites and history inside the user surface', async ({ page }) => {
        const email = process.env.E2E_USER_EMAIL ?? 'test@example.com'
        const password = process.env.E2E_USER_PASSWORD ?? 'password'

        await page.goto('/login', { waitUntil: 'domcontentloaded' })
        await page.evaluate(async () => fetch('/sanctum/csrf-cookie', { credentials: 'include' }))
        await page.locator('input[autocomplete="username"]').fill(email)
        await page.locator('input[autocomplete="current-password"]').fill(password)
        await page.getByRole('button', { name: 'Sign in' }).click()
        await expect(page).toHaveURL(/\/admin|\/account/)

        await page.goto('/account/favorites')
        await expect(page).toHaveURL(/\/account\/favorites/)
        await expect(page.getByRole('heading', { name: 'Favorites' })).toBeVisible()

        await page.goto('/account/history')
        await expect(page).toHaveURL(/\/account\/history/)
        await expect(page.getByRole('heading', { name: 'History' })).toBeVisible()
    })
})
