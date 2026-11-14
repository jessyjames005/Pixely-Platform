const password = process.env.E2E_USER_PASSWORD ?? process.env.E2E_PASSWORD

if (!password) {
  throw new Error('E2E_USER_PASSWORD or E2E_PASSWORD must be set before running Playwright.')
}

export const ADMIN_PERSONA = {
  email: process.env.E2E_USER_EMAIL ?? 'admin@pixely.test',
  password,
} as const
