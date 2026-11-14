import { defineConfig, devices } from '@playwright/test'

export default defineConfig({
  // Keep failures actionable: a broken locator should fail quickly instead of
  // consuming 30 seconds for every retry. Individual slow workflows can opt
  // into a longer timeout at test level when there is a demonstrated need.
  timeout: 60_000,
  expect: { timeout: 8_000 },
  // Include core and extension-owned E2E suites (e.g. CinemaMovie).
  testDir: '.',
  testMatch: '**/tests/E2E/**/*.spec.ts',
  fullyParallel: true,
  forbidOnly: Boolean(process.env.CI),
  // One retry is enough to identify transient CI failures without multiplying
  // the runtime of a consistently broken test.
  retries: process.env.CI ? 1 : 0,
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
    actionTimeout: 8_000,
    navigationTimeout: 15_000,
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
