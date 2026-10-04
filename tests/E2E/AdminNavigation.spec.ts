import { expect, test } from '@playwright/test'

const email = process.env.E2E_USER_EMAIL ?? 'test@example.com'
const password = process.env.E2E_USER_PASSWORD ?? 'password'

// Administration navigation: collapsible/rail sidebar, persisted state, and the
// grouped "Extensions" submenu. Runs in the CI Playwright job against a seeded
// admin (E2E_USER_EMAIL/E2E_USER_PASSWORD), same credentials as CinemaMovie.spec.ts.
test.describe('Administration navigation', () => {
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

  test('persists the collapsed/expanded rail state across clicks', async ({ page }) => {
    // Default: expanded — the toggle offers to collapse.
    const collapseToggle = page.getByRole('button', { name: 'Collapse navigation' })
    await expect(collapseToggle).toBeVisible()

    await collapseToggle.click()
    await expect(page.getByRole('button', { name: 'Expand navigation' })).toBeVisible()
    await expect.poll(async () => page.evaluate(() => localStorage.getItem('pixely.nav.rail'))).toBe('true')

    await page.getByRole('button', { name: 'Expand navigation' }).click()
    await expect(page.getByRole('button', { name: 'Collapse navigation' })).toBeVisible()
    await expect.poll(async () => page.evaluate(() => localStorage.getItem('pixely.nav.rail'))).toBe('false')
  })

  test('extensions submenu reveals its children when opened', async ({ page }) => {
    const drawer = page.locator('.v-navigation-drawer')
    const extensionsToggle = drawer.getByText('Extensions', { exact: true })
    await expect(extensionsToggle).toBeVisible()

    // Children are hidden until the group is expanded.
    await expect(drawer.getByText('Manage extensions', { exact: true })).toBeHidden()
    await expect(drawer.getByText('Gallery', { exact: true })).toBeHidden()

    await extensionsToggle.click()
    await expect(drawer.getByText('Manage extensions', { exact: true })).toBeVisible()
    await expect(drawer.getByText('Gallery', { exact: true })).toBeVisible()

    // Child links carry the expected href (independent of route resolution).
    const galleryLink = drawer.getByRole('link', { name: 'Gallery' })
    await expect(galleryLink).toHaveAttribute('href', '/admin/gallery')
    await expect(drawer.getByRole('link', { name: 'Manage extensions' })).toHaveAttribute('href', '/admin/extensions')
  })

  test('keyboard can reach and toggle the navigation control', async ({ page }) => {
    const toggle = page.getByRole('button', { name: 'Collapse navigation' })
    await toggle.focus()
    await expect(toggle).toBeFocused()
    await page.keyboard.press('Space')
    await expect(page.getByRole('button', { name: 'Expand navigation' })).toBeVisible()
    await expect.poll(async () => page.evaluate(() => localStorage.getItem('pixely.nav.rail'))).toBe('true')
  })
})
