import { expect, test } from '@playwright/test'

const email = process.env.PIXELY_E2E_EMAIL ?? 'admin@example.com'
const password = process.env.PIXELY_E2E_PASSWORD ?? 'password'

test.describe('User Space engagement', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login')
    await page.getByLabel(/email/i).fill(email)
    await page.getByLabel(/password/i).fill(password)
    await page.getByRole('button', { name: /log in|login|connexion/i }).click()
    await expect(page).toHaveURL(/\/admin|\/account/)
    await page.goto('/account/favorites')
  })

  test('navigates between favorites and history', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Favorites' })).toBeVisible()
    await page.getByRole('link', { name: 'History' }).click()
    await expect(page).toHaveURL(/\/account\/history/)
    await expect(page.getByRole('heading', { name: 'History' })).toBeVisible()
  })

  test('keeps user-space engagement behind authentication', async ({ browser }) => {
    const context = await browser.newContext()
    const guest = await context.newPage()
    await guest.goto('/account/favorites')
    await expect(guest).toHaveURL(/\/login/)
    await context.close()
  })
})
