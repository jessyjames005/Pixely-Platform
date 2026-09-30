import { expect, test } from '@playwright/test'

test('opens the CinemaMovie administration screen', async ({ page }) => {
  await page.goto('/admin/cinema-movie')

  await expect(page.getByRole('heading', { name: 'CinemaMovie' })).toBeVisible()
})
