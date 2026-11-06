import { expect, test } from '@playwright/test'
import { ADMIN_PERSONA } from './fixtures/personas'
import { AdminDashboardPage } from './poms/AdminDashboardPage.pom'
import { LoginPage } from './poms/LoginPage.pom'

// Administration navigation: collapsible/rail sidebar, persisted state, and the
// grouped "Extensions" submenu. Runs in the CI Playwright job against a seeded
// admin persona, same credentials as CinemaMovie.spec.ts.
test.describe('Administration navigation', () => {
  test.beforeEach(async ({ page }) => {
    const login = new LoginPage(page)
    await login.goto()
    expect(await login.login(ADMIN_PERSONA.email, ADMIN_PERSONA.password)).toBe(204)
    await expect(page).toHaveURL(/\/admin/)
  })

  test('persists the collapsed/expanded rail state across clicks', async ({ page }) => {
    const dashboard = new AdminDashboardPage(page)
    // Default: expanded — the toggle offers to collapse.
    await expect(dashboard.collapseNavigationButton).toBeVisible()

    await dashboard.collapseNavigation()
    await expect(dashboard.expandNavigationButton).toBeVisible()
    await expect.poll(async () => page.evaluate(() => localStorage.getItem('pixely.nav.rail'))).toBe('true')

    await dashboard.expandNavigation()
    await expect(dashboard.collapseNavigationButton).toBeVisible()
    await expect.poll(async () => page.evaluate(() => localStorage.getItem('pixely.nav.rail'))).toBe('false')
  })

  test('extensions submenu reveals its children when opened', async ({ page }) => {
    const dashboard = new AdminDashboardPage(page)
    await expect(dashboard.extensionsMenu).toBeVisible()

    // The group's children are hidden until it is expanded.
    await expect(dashboard.manageExtensionsLink).toBeHidden()

    await dashboard.openExtensionsMenu()
    await expect(dashboard.manageExtensionsLink).toBeVisible()
    await expect(dashboard.manageExtensionsLink).toHaveAttribute('href', '/admin/extensions')
  })

  test('lists runtime extension entries as top-level items', async ({ page }) => {
    // Extension navigation comes from GET /api/v1/extensions/navigation (SDK v2)
    // and is appended after the core entries, not nested under "Extensions".
    const dashboard = new AdminDashboardPage(page)
    await expect(dashboard.galleryLink).toBeVisible()
    await expect(dashboard.galleryLink).toHaveAttribute('href', '/admin/gallery')
  })

  test('keyboard can reach and toggle the navigation control', async ({ page }) => {
    const dashboard = new AdminDashboardPage(page)
    await dashboard.collapseNavigationButton.focus()
    await expect(dashboard.collapseNavigationButton).toBeFocused()
    await dashboard.toggleNavigationWithKeyboard()
    await expect(dashboard.expandNavigationButton).toBeVisible()
    await expect.poll(async () => page.evaluate(() => localStorage.getItem('pixely.nav.rail'))).toBe('true')
  })
})
