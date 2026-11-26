import { expect, test } from '@playwright/test'
import { ADMIN_PERSONA, READER_PERSONA } from './fixtures/personas'
import { LoginPage } from './poms/LoginPage.pom'
import { AdminDashboardPage } from './poms/AdminDashboardPage.pom'

test.describe('Administration navigation rights', () => {
  test('admin user sees Music navigation link', async ({ page }) => {
    const login = new LoginPage(page)
    const dashboard = new AdminDashboardPage(page)

    await login.goto()
    await login.login(ADMIN_PERSONA.email, ADMIN_PERSONA.password)

    await expect(dashboard.musicLink).toBeVisible()
    await expect(dashboard.galleryLink).toBeVisible()
  })

  test('editor user does NOT see Music navigation link', async ({ page }) => {
    const login = new LoginPage(page)
    const dashboard = new AdminDashboardPage(page)

    await login.goto()
    await login.login(READER_PERSONA.email, READER_PERSONA.password)

    // Editor only has gallery.photos.view/manage permissions; music.tracks.view
    // is not granted, so the Music link should be hidden.
    await expect(dashboard.musicLink).not.toBeVisible()
    await expect(dashboard.galleryLink).toBeVisible()
  })
})