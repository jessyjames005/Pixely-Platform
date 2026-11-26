import { test, expect } from '@playwright/test'
import { MusicPublicPage } from './poms/MusicPublicPage.pom'

test.describe('Pixely Music public page', () => {
  test('loads the music public surface with ZeffyrMusic branding', async ({ page }) => {
    const music = new MusicPublicPage(page)
    await music.goto()
    await expect(music.brand).toBeVisible()
    await expect(page).toHaveTitle(/Pixely Platform/)
    // Accessible heading exists (visually hidden sr-only h1).
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible()
  })

  test('renders decade category cards', async ({ page }) => {
    const music = new MusicPublicPage(page)
    await music.goto()
    await expect(music.decadeCards).toHaveCount(4)
    await expect(music.decadeCards.first()).toBeVisible()
    await expect(music.decadeCards.nth(1)).toBeVisible()
    await expect(music.decadeCards.nth(2)).toBeVisible()
    await expect(music.decadeCards.nth(3)).toBeVisible()
  })

  test('renders Top Charts section with album cards', async ({ page }) => {
    const music = new MusicPublicPage(page)
    await music.goto()
    await expect(music.topChartsHeading).toBeVisible()
    await expect(music.topChartsGrid).toBeVisible()
  })

  test('renders bottom player bar', async ({ page }) => {
    const music = new MusicPublicPage(page)
    await music.goto()
    await expect(music.playerBar).toBeVisible()
  })

  test('renders sidebar now-playing section', async ({ page }) => {
    const music = new MusicPublicPage(page)
    await music.goto()
    await expect(music.nowPlayingSection).toBeVisible()
  })
})
