import { expect, test } from '@playwright/test'

// Password recovery screens. These do not sign in, so they never touch the
// seeded admin's state and are safe to run in parallel with the other specs.
test.describe('Authentication recovery', () => {
  test('login screen offers "remember me" and a forgot-password link', async ({ page }) => {
    await page.goto('/login', { waitUntil: 'domcontentloaded' })

    await expect(page.getByLabel('Remember me')).toBeVisible()
    await expect(page.getByRole('link', { name: 'Forgot your password?' })).toBeVisible()
  })

  test('forgot-password shows the same confirmation for any address', async ({ page }) => {
    await page.goto('/login', { waitUntil: 'domcontentloaded' })
    await page.getByRole('link', { name: 'Forgot your password?' }).click()
    await expect(page).toHaveURL(/\/forgot-password$/)

    await page.locator('input[type="email"]').fill('nobody@example.com')
    await page.getByRole('button', { name: 'Send reset link' }).click()

    await expect(page.getByTestId('reset-link-sent')).toBeVisible()
  })

  test('reset-password rejects a link without a token', async ({ page }) => {
    await page.goto('/reset-password', { waitUntil: 'domcontentloaded' })

    await expect(page.getByText('This password reset link is invalid or has expired.')).toBeVisible()
  })

  test('reset-password with a bogus token is refused by the API', async ({ page }) => {
    await page.goto('/reset-password?token=bogus&email=nobody%40example.com', { waitUntil: 'domcontentloaded' })

    await page.getByLabel('New password', { exact: true }).fill('a-brand-new-secret')
    await page.getByLabel('Confirm new password').fill('a-brand-new-secret')
    await page.getByRole('button', { name: 'Reset password' }).click()

    await expect(page.getByText('This password reset link is invalid or has expired.')).toBeVisible()
  })
})
