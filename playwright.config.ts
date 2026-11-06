import { defineConfig, devices } from '@playwright/test'

export default defineConfig({
  timeout: 90_000,
  expect: { timeout: 30_000 },
  testDir: '.',
  testMatch: '**/tests/E2E/**/*.spec.ts',
  fullyParallel: true,
  forbidOnly: Boolean(process.env.CI),
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 2 : undefined,
  reporter: process.env.CI
    ? [
        ['github'],
        ['html', { outputFolder: '.playwright/report', open: 'never' }],
        ['junit', { outputFile: '.playwright/junit.xml' }],
      ]
    : 'list',
  outputDir: '.playwright/results',
  use: {
    baseURL: process.env.E2E_BASE_URL ?? process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8082',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    actionTimeout: 10_000,
    navigationTimeout: 20_000,
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },
  ],
})
