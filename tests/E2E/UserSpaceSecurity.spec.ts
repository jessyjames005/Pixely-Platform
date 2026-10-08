import { expect, test } from '@playwright/test'

test.describe('User Space security', () => {
    test('redirects guests away from protected engagement pages', async ({ page }) => {
        await page.goto('/account/favorites')
        await expect(page).toHaveURL(/\/login/)

        await page.goto('/account/history')
        await expect(page).toHaveURL(/\/login/)
    })

    test('keeps favorites and history inside the user surface', async ({ page }) => {
        const email = process.env.PIXELY_E2E_EMAIL ?? 'admin@example.com'
        const password = process.env.PIXELY_E2E_PASSWORD ?? 'password'

        await page.goto('/login')
        await page.getByLabel(/email/i).fill(email)
        await page.getByLabel(/password/i).fill(password)
        await page.getByRole('button', { name: /log in|login|connexion/i }).click()

        await page.goto('/account/favorites')
        await expect(page).toHaveURL(/\/account\/favorites/)
        await expect(page.getByRole('heading', { name: 'Favorites' })).toBeVisible()

        await page.goto('/account/history')
        await expect(page).toHaveURL(/\/account\/history/)
        await expect(page.getByRole('heading', { name: 'History' })).toBeVisible()
    })
})
