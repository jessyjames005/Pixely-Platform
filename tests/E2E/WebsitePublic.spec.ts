import { test, expect } from '@playwright/test'

test.describe('Public website', () => {
  test('home route loads the public website surface', async ({ page }) => {
    await page.goto('/')
    await expect(page.locator('.website-header')).toBeVisible()
    await expect(page.locator('.website-brand')).toHaveText('Pixely')
  })

  test('published page is rendered with public navigation', async ({ page }) => {
    await page.route('**/api/v1/website/navigation/main', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: [{ id: 'nav-about', title: 'About', type: 'page', href: '/about' }],
        }),
      })
    })

    await page.route('**/api/v1/website/public/pages/about', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            id: 'page-about',
            slug: 'about',
            title: 'About Pixely',
            status: 'published',
            template: 'default',
            seo: { title: 'About Pixely', description: 'About Pixely Platform' },
            blocks: [{ type: 'paragraph', text: 'Welcome to Pixely.' }],
          },
        }),
      })
    })

    await page.goto('/about')
    await expect(page.locator('.website-brand')).toHaveText('Pixely')
    await expect(page.locator('h1')).toHaveText('About Pixely')
    await expect(page.getByRole('link', { name: 'About' })).toBeVisible()
    await expect(page).toHaveTitle('About Pixely')
  })

  test('unknown public route displays the 404 state', async ({ page }) => {
    await page.goto('/this-page-does-not-exist')
    await expect(page.getByText('Page not found')).toBeVisible()
  })
})
