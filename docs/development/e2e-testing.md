# End-to-End Browser Testing

Pixely uses Playwright for full browser journeys across the Vue application,
Laravel routes and APIs, session authentication, permissions, and persistence.
The suite complements Laravel/Pest tests and Vitest.

## Suite and browsers

The Playwright configuration discovers specs under `tests/E2E` and extension-local
`app/Extensions/*/tests/E2E` directories. Every spec runs in both configured
projects: Chromium and Firefox. CI uploads HTML, JUnit, screenshots, videos, and
traces when available. Use `npm run test:e2e:chromium`,
`npm run test:e2e:firefox`, or `npm run test:e2e:report` to select a browser or
inspect the latest report.

Authenticated tests use the `admin@pixely.test` persona defined by the
`E2eUserSeeder`. Supply `E2E_PASSWORD` at runtime; the password is not stored in
source control. Tests creating persistent content must use unique values so the
two browser projects can run concurrently.

## Isolated local environment

The local E2E Compose project is separate from the development Compose project.
It uses database `pixely_e2e`, its own MySQL volume, Redis, PHP-FPM, Nginx, and a
Playwright image containing the matching Chromium and Firefox browsers. The
development Compose stack has no E2E runner attached to its database.

Create `.env.e2e` from the example, set distinct local-only database passwords
and `E2E_PASSWORD`, and fill in an `APP_KEY` generated for this environment.
This file is ignored by Git. After starting the services, a key can be generated
inside the E2E PHP container with `php artisan key:generate --show`; copy it into
`.env.e2e` and recreate `app-e2e` so the container receives it.

```powershell
Copy-Item .env.e2e.example .env.e2e
```

Start the isolated services and prepare only their database:

```powershell
docker compose --env-file .env.e2e -p pixely-e2e -f docker-compose.e2e.yml up -d --build mysql-e2e redis-e2e app-e2e nginx-e2e
docker compose --env-file .env.e2e -p pixely-e2e -f docker-compose.e2e.yml exec app-e2e composer install
docker compose --env-file .env.e2e -p pixely-e2e -f docker-compose.e2e.yml exec app-e2e php artisan migrate:fresh --seed --force
docker compose --env-file .env.e2e -p pixely-e2e -f docker-compose.e2e.yml exec app-e2e php artisan pixely:extension:migrate CinemaMovie
docker compose --env-file .env.e2e -p pixely-e2e -f docker-compose.e2e.yml exec app-e2e php artisan db:seed --class=E2eUserSeeder --force
```

`migrate:fresh` is destructive. Run it only against the isolated `pixely_e2e`
database shown in `.env.e2e`, never against development data.

Run all specs in both browsers:

```powershell
docker compose --env-file .env.e2e -p pixely-e2e -f docker-compose.e2e.yml run --rm playwright
```

Run only one browser by overriding the runner command:

```powershell
docker compose --env-file .env.e2e -p pixely-e2e -f docker-compose.e2e.yml run --rm playwright npm run test:e2e:chromium
docker compose --env-file .env.e2e -p pixely-e2e -f docker-compose.e2e.yml run --rm playwright npm run test:e2e:firefox
```

The default runner command installs Node dependencies, builds the frontend, and
runs Playwright. Open the HTML report with `npm run test:e2e:report` from the
Playwright container or copy `.playwright/report` to a local Node environment.

To stop the stack, run `docker compose --env-file .env.e2e -p pixely-e2e -f
docker-compose.e2e.yml down`. Add `-v` only when intentionally deleting the
isolated E2E database volume. Never run E2E migrations or seeders against the
development database.

## CI behavior

CI provisions an ephemeral MySQL service for E2E, seeds the admin persona with a
random job-only password, and runs all specs in Chromium and Firefox. The E2E job
is configured with `continue-on-error`, so its failure and artifacts remain
visible without invalidating the overall CI result during suite stabilization.
Playwright reports are uploaded even when tests fail.

E2E test data must not depend on ordering or be shared mutably across tests.
Prefer UI journeys for behavior under test; direct API requests may prepare data
when they do not bypass the UI behavior being verified.
