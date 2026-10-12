import { expect, test } from '@playwright/test'

test.describe('Public website error handling', () => {
  test('renders the public 404 state for an unknown page', async ({ page }) => {
    await page.goto('/this-page-does-not-exist')

    // Unknown public pages get a real server-rendered 404 (resources/views/errors/404.blade.php).
    await expect(page.getByText('Page not found')).toBeVisible()
    await expect(page.getByText('The requested resource could not be found.')).toBeVisible()
  })
})
