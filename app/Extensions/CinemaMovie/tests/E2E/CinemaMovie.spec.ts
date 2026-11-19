import { expect, test, type Page } from '@playwright/test'
import { ADMIN_PERSONA } from '../../../../../tests/E2E/fixtures/personas'
import { LoginPage } from '../../../../../tests/E2E/poms/LoginPage.pom'
import { CinemaMoviePage } from '../../../../../tests/E2E/poms/CinemaMoviePage.pom'

/** Authenticates the administrator and opens the extension's admin surface. */
async function openCinemaMovieAdministration(page: Page): Promise<CinemaMoviePage> {
  const login = new LoginPage(page)
  await login.goto()
  expect(await login.login(ADMIN_PERSONA.email, ADMIN_PERSONA.password)).toBe(200)
  await expect.poll(async () => (await page.context().cookies()).some((cookie) => cookie.name === 'XSRF-TOKEN')).toBe(true)
  await expect(page).toHaveURL(/\/admin/)

  const cinemaMovie = new CinemaMoviePage(page)
  await cinemaMovie.goto()
  await expect(cinemaMovie.pageHeading).toBeVisible()
  await expect(cinemaMovie.catalogueHeading).toBeVisible()
  await expect(cinemaMovie.genresHeading).toBeVisible()

  return cinemaMovie
}

test('logs in and opens the CinemaMovie administration screen', async ({ page }) => {
  await openCinemaMovieAdministration(page)
})

test('creates, edits and deletes a movie through the administration UI', async ({ page }) => {
  const cinemaMovie = await openCinemaMovieAdministration(page)
  // Each browser worker gets its own record so Chromium and Firefox can run concurrently.
  const uniqueId = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`
  const originalTitle = `Pixely E2E Original ${uniqueId}`
  const englishTitle = `Pixely E2E Movie ${uniqueId}`
  const frenchTitle = `Film Pixely E2E ${uniqueId}`
  const updatedTitle = `Pixely E2E Updated ${uniqueId}`

  await cinemaMovie.createMovie({ originalTitle, englishTitle, frenchTitle })
  const movieRow = cinemaMovie.movieRow(englishTitle)
  await expect(movieRow).toBeVisible()

  await cinemaMovie.editMovieTitle(englishTitle, updatedTitle)
  await expect(cinemaMovie.movieRow(updatedTitle)).toBeVisible()

  await cinemaMovie.deleteMovie(updatedTitle)
  await expect(cinemaMovie.movieRow(updatedTitle)).toHaveCount(0)
})

test('creates and deletes a bilingual genre through the administration UI', async ({ page }) => {
  const cinemaMovie = await openCinemaMovieAdministration(page)
  const uniqueId = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`
  const englishName = `Pixely E2E Genre ${uniqueId}`
  const frenchName = `Genre Pixely E2E ${uniqueId}`

  await cinemaMovie.createGenre(englishName, frenchName)
  await expect(cinemaMovie.genreRow(englishName)).toBeVisible()

  await cinemaMovie.deleteGenre(englishName)
  await expect(cinemaMovie.genreRow(englishName)).toHaveCount(0)
})
