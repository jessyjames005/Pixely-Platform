import { defineConfig, devices } from '@playwright/test'

export default defineConfig({
  timeout: 90_000,
  expect: { timeout: 30_000 },
  testDir: '.',
  testMatch: '**/tests/E2E/**/*.spec.ts',
  fullyParallel: true,
  forbidOnly: Boolean(process.env.CI),
  retries: process.env.CI ? 2 : 0,
  reporter: process.env.CI ? 'github' : 'list',
  use: {
    baseURL: process.env.E2E_BASE_URL ?? process.env.PLAYWRIGHT_BASE_URL ?? 'http://nginx',
    trace: 'retain-on-failure',
    ...devices['Desktop Chrome'],
  },
})
