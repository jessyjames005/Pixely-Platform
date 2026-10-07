import { expect, test } from '@playwright/test'

test.describe('Public website error handling', () => {
  test('renders the public 404 state for an unknown page', async ({ page }) => {
    await page.goto('/this-page-does-not-exist')

    await expect(page.getByText('Page not found')).toBeVisible()
    await expect(page.getByText('The page you requested does not exist or is not published.')).toBeVisible()
  })
})
