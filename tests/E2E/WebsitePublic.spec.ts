import { test, expect } from '@playwright/test'
import { PublicWebsitePage } from './poms/PublicWebsitePage.pom'

test.describe('Public website', () => {
  test('home route loads the public website surface', async ({ page }) => {
    const website = new PublicWebsitePage(page)
    await website.gotoHome()
    await expect(website.brand).toHaveText('Pixely')
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

    const website = new PublicWebsitePage(page)
    await website.gotoAbout()
    await expect(website.brand).toHaveText('Pixely')
    await expect(website.pageHeading).toHaveText('About Pixely')
    await expect(website.aboutLink).toBeVisible()
    await expect(page).toHaveTitle('About Pixely')
  })

  test('unknown public route displays the 404 state', async ({ page }) => {
    const website = new PublicWebsitePage(page)
    await website.gotoUnknownPage()
    await expect(website.notFoundMessage).toBeVisible()
  })
})


test('home, about and contact are reachable from public navigation', async ({ page }) => {
  const website = new PublicWebsitePage(page)
  await website.gotoHome()
  await expect(website.aboutLink).toBeVisible()
  await website.openAbout()
  await expect(page).toHaveURL(/\/about$/);
  await expect(website.pageHeading).toHaveText('About Pixely')

  await website.openContact()
  await expect(page).toHaveURL(/\/contact$/);
  await expect(website.pageHeading).toHaveText('Contact')

  await website.openHome()
  // The public navigation intentionally points to the explicit /home slug.
  await expect(page).toHaveURL(/\/home$/);
  await expect(website.pageHeading).toHaveText('Welcome to Pixely Platform')
})
