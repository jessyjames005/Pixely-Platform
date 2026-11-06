import { expect, test } from '@playwright/test'
import { ADMIN_PERSONA } from '../../../../../tests/E2E/fixtures/personas'
import { LoginPage } from '../../../../../tests/E2E/poms/LoginPage.pom'
import { CinemaMoviePage } from '../../../../../tests/E2E/poms/CinemaMoviePage.pom'

test('logs in and opens the CinemaMovie administration screen', async ({ page }) => {
  const login = new LoginPage(page)
  await login.goto()
  expect(await login.login(ADMIN_PERSONA.email, ADMIN_PERSONA.password)).toBe(204)
  await expect.poll(async () => (await page.context().cookies()).some((cookie) => cookie.name === 'XSRF-TOKEN')).toBe(true)
  await expect(page).toHaveURL(/\/admin/)

  const cinemaMovie = new CinemaMoviePage(page)
  await cinemaMovie.goto()
  await expect(cinemaMovie.pageHeading).toBeVisible()
  await expect(cinemaMovie.catalogueHeading).toBeVisible()
})
