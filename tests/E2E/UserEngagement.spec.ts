import { expect, test, type Page } from '@playwright/test'
import { ADMIN_PERSONA } from './fixtures/personas'
import { LoginPage } from './poms/LoginPage.pom'

test.describe('User Space engagement API', () => {
    test.beforeEach(async ({ page }) => {
        const login = new LoginPage(page)
        await login.goto()
        await login.login(ADMIN_PERSONA.email, ADMIN_PERSONA.password)
        await expect(page).toHaveURL(/\/admin|\/account/)
    })

    // page.request shares the browser's cookies but is not the SPA client: Sanctum needs
    // the Referer to treat it as stateful, and the XSRF cookie echoed back as a header.
    async function statefulHeaders(page: Page): Promise<Record<string, string>> {
        const cookies = await page.context().cookies()
        const xsrf = decodeURIComponent(cookies.find((cookie) => cookie.name === 'XSRF-TOKEN')?.value ?? '')

        return {
            Accept: 'application/json',
            Referer: `${new URL(page.url()).origin}/`,
            'X-XSRF-TOKEN': xsrf,
        }
    }

    test('can create, list and remove a favorite through the authenticated API', async ({ page }) => {
        const resourceId = `e2e-${Date.now()}`

        const headers = await statefulHeaders(page)

        const create = await page.request.post('/api/v1/me/favorites', {
            headers,
            data: {
                resource_type: 'e2e.test',
                resource_id: resourceId,
            },
        })
        expect(create.ok()).toBeTruthy()

        const list = await page.request.get('/api/v1/me/favorites?resource_type=e2e.test', { headers })
        expect(list.ok()).toBeTruthy()
        await expect.poll(async () => (await list.json()).data.some((item: { resource_id: string }) => item.resource_id === resourceId)).toBeTruthy()

        const remove = await page.request.delete(`/api/v1/me/favorites/e2e.test/${resourceId}`, { headers })
        expect(remove.ok()).toBeTruthy()
    })
})
