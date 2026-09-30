import { expect, test } from '@playwright/test'

test('logs in and opens the CinemaMovie administration screen', async ({ page }) => {
  const email = process.env.E2E_USER_EMAIL ?? 'test@example.com'
  const password = process.env.E2E_USER_PASSWORD ?? 'password'

  await page.goto('/login', { waitUntil: 'domcontentloaded' })
  const csrfResponsePromise = page.waitForResponse('/sanctum/csrf-cookie')
  await page.evaluate(async () => fetch('/sanctum/csrf-cookie', { credentials: 'include' }))
  const csrfResponse = await csrfResponsePromise
  expect(csrfResponse.status()).toBe(204)
  await expect.poll(async () => (await page.context().cookies()).some((cookie) => cookie.name === 'XSRF-TOKEN')).toBe(true)
  await page.locator('input[autocomplete="username"]').fill(email)
  await page.locator('input[autocomplete="current-password"]').fill(password)
  await page.getByRole('button', { name: 'Sign in' }).click()
  await expect(page).toHaveURL(/\/admin/)

  await page.goto('/admin/cinema-movie', { waitUntil: 'domcontentloaded' })

  await expect(page.getByRole('heading', { name: 'CinemaMovie' })).toBeVisible()
})
