import { expect, test } from '@playwright/test'
import { AuthRecoveryPage } from './poms/AuthRecoveryPage.pom'

// Password recovery screens. These do not sign in, so they never touch the
// seeded admin's state and are safe to run in parallel with the other specs.
test.describe('Authentication recovery', () => {
  test('login screen offers "remember me" and a forgot-password link', async ({ page }) => {
    const auth = new AuthRecoveryPage(page)
    await auth.gotoLogin()

    await expect(auth.rememberMe).toBeVisible()
    await expect(auth.forgotPasswordLink).toBeVisible()
  })

  test('forgot-password shows the same confirmation for any address', async ({ page }) => {
    const auth = new AuthRecoveryPage(page)
    await auth.gotoLogin()
    await auth.openForgotPassword()
    await expect(page).toHaveURL(/\/forgot-password$/)

    await auth.requestReset('nobody@example.com')

    await expect(auth.resetLinkSent).toBeVisible()
  })

  test('reset-password rejects a link without a token', async ({ page }) => {
    const auth = new AuthRecoveryPage(page)
    await auth.gotoResetPassword()

    await expect(auth.invalidResetMessage).toBeVisible()
  })

  test('reset-password with a bogus token is refused by the API', async ({ page }) => {
    const auth = new AuthRecoveryPage(page)
    await auth.gotoResetPassword('bogus', 'nobody@example.com')

    await auth.submitNewPassword('a-brand-new-secret')

    await expect(auth.invalidResetMessage).toBeVisible()
  })
})
