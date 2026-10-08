import { expect, test } from '@playwright/test'

const email = process.env.E2E_USER_EMAIL ?? 'test@example.com'
const password = process.env.E2E_USER_PASSWORD ?? 'password'

test.describe('User Space engagement API', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/login', { waitUntil: 'domcontentloaded' })
        await page.evaluate(async () => fetch('/sanctum/csrf-cookie', { credentials: 'include' }))

        await page.locator('input[autocomplete="username"]').fill(email)
        await page.locator('input[autocomplete="current-password"]').fill(password)
        await page.getByRole('button', { name: 'Sign in' }).click()
    })

    test('can create, list and remove a favorite through the authenticated API', async ({ page }) => {
        const resourceId = `e2e-${Date.now()}`

        const create = await page.request.post('/api/v1/me/favorites', {
            data: {
                resource_type: 'e2e.test',
                resource_id: resourceId,
            },
        })
        expect(create.ok()).toBeTruthy()

        const list = await page.request.get('/api/v1/me/favorites?resource_type=e2e.test')
        expect(list.ok()).toBeTruthy()
        await expect.poll(async () => (await list.json()).data.some((item: { resource_id: string }) => item.resource_id === resourceId)).toBeTruthy()

        const remove = await page.request.delete(`/api/v1/me/favorites/e2e.test/${resourceId}`)
        expect(remove.ok()).toBeTruthy()
    })
})
