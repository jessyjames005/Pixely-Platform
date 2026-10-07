import { expect, test } from '@playwright/test'

const email = process.env.E2E_USER_EMAIL ?? 'test@example.com'
const password = process.env.E2E_USER_PASSWORD ?? 'password'

test.describe('Website administration', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login', { waitUntil: 'domcontentloaded' })
    const csrf = page.waitForResponse('/sanctum/csrf-cookie')
    await page.evaluate(async () => fetch('/sanctum/csrf-cookie', { credentials: 'include' }))
    expect((await csrf).status()).toBe(204)

    await page.locator('input[autocomplete="username"]').fill(email)
    await page.locator('input[autocomplete="current-password"]').fill(password)
    await page.getByRole('button', { name: 'Sign in' }).click()
    await expect(page).toHaveURL(/\/admin/)
  })

  test('opens the Website Pages screen from administration navigation', async ({ page }) => {
    await page.getByRole('link', { name: 'Pages' }).click()
    await expect(page).toHaveURL(/\/admin\/website\/pages$/)
    await expect(page.getByRole('heading', { name: 'Website pages' })).toBeVisible()
    await expect(page.getByRole('button', { name: 'New page' })).toBeVisible()
  })

  test('creates a website page from the admin UI', async ({ page }) => {
    await page.goto('/admin/website/pages')
    await page.getByRole('button', { name: 'New page' }).click()
    await expect(page.getByRole('heading', { name: 'New page' })).toBeVisible()

    await page.getByLabel('Title').fill('Playwright About')
    await page.getByRole('button', { name: 'Save' }).click()

    await expect(page.getByText('Playwright About')).toBeVisible()
    await expect(page.getByText('playwright-about')).toBeVisible()
  })

  test('creates a website menu from the admin UI', async ({ page }) => {
    await page.goto('/admin/website/menus')
    await page.getByRole('button', { name: 'New menu' }).click()
    await expect(page.getByRole('heading', { name: 'New menu' })).toBeVisible()

    await page.getByLabel('Name').fill('Playwright Main')
    await page.getByLabel('Code').fill('playwright-main')
    await page.getByRole('button', { name: 'Add item' }).click()
    await page.getByLabel('Title').last().fill('Home')
    await page.getByLabel('Target URL').last().fill('/')
    await page.getByRole('button', { name: 'Save' }).click()

    await expect(page.getByText('Playwright Main')).toBeVisible()
    await expect(page.getByText('Home')).toBeVisible()
  })
})
