import { expect, test } from '@playwright/test'
import { PublicWebsitePage } from './poms/PublicWebsitePage.pom'

test.describe('Public website error handling', () => {
  test('renders the public 404 state for an unknown page', async ({ page }) => {
    const website = new PublicWebsitePage(page)
    await website.gotoUnknownPage()

    // Unknown public pages get a real server-rendered 404 (resources/views/errors/404.blade.php).
    await expect(website.notFoundMessage).toBeVisible()
    await expect(website.notFoundDetail).toBeVisible()
  })
})
