import { expect, test } from '@playwright/test'

test('logs in and opens the Music administration screen', async ({ page }) => {
  const email = process.env.E2E_USER_EMAIL ?? 'test@example.com'
  const password = process.env.E2E_USER_PASSWORD ?? 'password'

  await page.goto('/login')
  const csrfResponsePromise = page.waitForResponse('/sanctum/csrf-cookie')
  await page.evaluate(async () => fetch('/sanctum/csrf-cookie', { credentials: 'include' }))
  const csrfResponse = await csrfResponsePromise
  expect(csrfResponse.status()).toBe(204)
  await expect.poll(async () => (await page.context().cookies()).some((cookie) => cookie.name === 'XSRF-TOKEN')).toBe(true)
  await page.locator('input[autocomplete="username"]').fill(email)
  await page.locator('input[autocomplete="current-password"]').fill(password)
  await page.locator('form button[type="submit"]').click()

  await page.goto('/admin/music')

  await expect(page.getByRole('heading', { name: 'Music' })).toBeVisible()
})
