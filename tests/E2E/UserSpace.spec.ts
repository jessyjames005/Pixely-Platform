import { expect, test } from '@playwright/test'

const email = process.env.E2E_USER_EMAIL ?? 'test@example.com'
const password = process.env.E2E_USER_PASSWORD ?? 'password'

test.describe('User Space', () => {
  test('guest is redirected to login', async ({ page }) => {
    await page.goto('/account')
    await expect(page).toHaveURL(/\/login/)
  })

  test('authenticated user can navigate between dashboard, profile and preferences', async ({ page }) => {
    await page.goto('/login', { waitUntil: 'domcontentloaded' })
    await page.evaluate(async () => fetch('/sanctum/csrf-cookie', { credentials: 'include' }))
    await page.locator('input[autocomplete="username"]').fill(email)
    await page.locator('input[autocomplete="current-password"]').fill(password)
    await page.getByRole('button', { name: 'Sign in' }).click()
    await expect(page).toHaveURL(/\/admin/)

    await page.goto('/account')
    await expect(page).toHaveURL(/\/account$/)
    await expect(page.getByText('My Space')).toBeVisible()

    await page.getByRole('link', { name: /Profile/i }).first().click()
    await expect(page).toHaveURL(/\/account\/profile$/)

    await page.getByRole('link', { name: /Preferences/i }).first().click()
    await expect(page).toHaveURL(/\/account\/preferences$/)
  })
})
