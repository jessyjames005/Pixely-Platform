# Pixely Platform

> **Build once. Extend forever.**

Pixely Platform is a modular platform for creating, publishing and administering a complete website with **Laravel, PHP, Vue 3 and TypeScript**. A stable shared Core, the Website Engine and independently managed Extensions provide reusable capabilities without moving business-specific logic into the Core.

**Deployment model:** one Pixely installation operates one independent website. Multi-site / multi-tenant hosting inside one installation is deliberately deferred; multi-language content and the public, user, administration and API surfaces remain in scope.

The project is under active development. The roadmap is the source of truth for implementation status; planned capabilities should not be assumed to be production-ready.

## What Pixely is for

Pixely is intended to provide a reusable foundation for applications that need a combination of:

- A public website for pages, navigation, publication and SEO (one website per installation).
- A secure user area for authenticated users.
- An administration interface for users, roles, permissions, settings and extensions.
- A versioned API for the platform and its extensions.
- Independently developed extensions for domain-specific features.

## Architecture at a glance

### Core

The Core provides shared services and contracts, including:

- Authentication and user management.
- Roles, permissions and authorization.
- Platform settings and localization.
- Extension discovery, lifecycle, configuration and capabilities.
- Navigation and route registration across supported application surfaces.
- API infrastructure and common services.

### Extensions

Extensions add optional features without requiring business-specific code to be placed in the Core. An extension can declare its identity, version, dependencies, permissions, configuration and supported surfaces through the extension contracts and manifest.

Gallery is an example extension. The extensions available in a given checkout depend on the source code and their configuration; the roadmap describes their current implementation status.

### Application surfaces

Pixely is evolving as a multi-surface platform:

- **Public website** — public pages and navigation.
- **User space** — authenticated, user-facing features.
- **Administration** — platform and extension management.
- **API** — programmatic access to platform and extension capabilities.

Access to protected features is controlled through authentication, surface authorization and permissions.

## Integration with external software

Pixely is designed to support integrations with **external software and services** through dedicated extensions, adapters and APIs. An integration can exchange data with another system when the relevant connector, authentication, permissions and configuration have been implemented.

This is an architectural capability, not a promise that every external product is already integrated. Each integration must be assessed, implemented and tested independently. Credentials and other secrets should be stored using the application's secure configuration mechanisms, not committed to source control.

## Technology stack

| Area | Technologies |
| --- | --- |
| Backend | PHP 8.3+, Laravel |
| Frontend | Vue 3, TypeScript, Vue Router, Vuetify, Vite |
| API | JSON:API conventions and generated API documentation |
| Database and services | MySQL, Redis |
| Testing and quality | Pest / PHPUnit, Vitest, Playwright, PHPStan, PHPCS, ESLint, TypeScript checks |
| Development environment | Docker Compose, Composer, Node.js / npm |
| CI | GitHub Actions |

The exact supported versions are defined by `composer.json`, `package.json` and the lock files.

## Current architecture decision

The deployment boundary is an installation, not a tenant: do not introduce a generalised `site_id` / `tenant_id`, tenant-scoped data model, or Spatie Teams integration as part of the current roadmap. Revisit multi-site only after a confirmed business requirement and a dedicated architecture review. See [ADR-0010](docs/architecture/adr/ADR-0010-single-site-per-installation.md) and the [architecture overview](ARCHITECTURE.md).

## Getting started

### Requirements

- Docker Desktop with Docker Compose, or an equivalent Docker environment.
- Git.

The Docker setup provides the PHP application, web server, Node.js tooling, MySQL and Redis services.

### Start the containers

From the repository root, run:

```powershell
docker compose up -d --build
```

Install backend dependencies and initialise the Laravel application inside the PHP container:

```powershell
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

Install frontend dependencies and build the assets inside the Node container:

```powershell
docker compose exec node npm ci
docker compose exec node npm run build:front
```

Open the application at **http://localhost:8080**.

> **Environment note:** review `.env.example` and the Docker Compose configuration before using a non-local environment. Do not reuse development credentials or expose development-only tools in production. If your local environment already has a configured `.env` and application key, do not overwrite them unnecessarily.

## Useful development commands

Run backend tests:

```powershell
docker compose exec app php artisan test
```

Run backend static analysis and coding-standard checks:

```powershell
docker compose exec app composer phpstan
docker compose exec app composer cs:check
```

Run frontend tests, type checks, linting and a production build:

```powershell
docker compose exec node npm test
docker compose exec node npm run typecheck
docker compose exec node npm run lint
docker compose exec node npm run build:front
```

Run browser tests in the isolated E2E environment (Chromium and Firefox):

```powershell
Copy-Item .env.e2e.example .env.e2e
docker compose --env-file .env.e2e -p pixely-e2e -f docker-compose.e2e.yml up -d --build mysql-e2e redis-e2e app-e2e nginx-e2e
docker compose --env-file .env.e2e -p pixely-e2e -f docker-compose.e2e.yml exec app-e2e php artisan migrate:fresh --seed --force
docker compose --env-file .env.e2e -p pixely-e2e -f docker-compose.e2e.yml exec app-e2e php artisan db:seed --force
docker compose --env-file .env.e2e -p pixely-e2e -f docker-compose.e2e.yml exec app-e2e php artisan db:seed --class=E2eUserSeeder --force
docker compose --env-file .env.e2e -p pixely-e2e -f docker-compose.e2e.yml run --rm playwright
```

See the [E2E testing guide](docs/development/e2e-testing.md) before creating the local
environment. Its database and volume are separate from development data.

Use the project's CI configuration and `package.json` / `composer.json` as the definitive reference for available checks.

## Documentation

- [`VISION.md`](VISION.md) — product goals and principles.
- [`ROADMAP.md`](ROADMAP.md) — implementation status and planned work.
- [`CHANGELOG.md`](CHANGELOG.md) — notable changes.
- [`ARCHITECTURE.md`](ARCHITECTURE.md) — high-level architecture.
- [`docs/architecture/`](docs/architecture/) — architecture documentation and decisions, including the [Website Engine boundary](docs/architecture/website-engine.md) and [ADR-0010](docs/architecture/adr/ADR-0010-single-site-per-installation.md).
- [`docs/handbook/`](docs/handbook/) — practical guides.
- [`docs/api/`](docs/api/) — API documentation, generated specifications and the [OpenAPI generation rules](docs/api/openapi-generation.md).
- [`docs/development/`](docs/development/) — development conventions and project rules.

Significant architectural decisions should be documented in Architecture Decision Records (ADRs).

## Project status

Pixely Platform is actively evolving. Some foundations and extension capabilities are implemented, while other product features and integrations remain in progress or planned. Check [`ROADMAP.md`](ROADMAP.md) before relying on a capability or assuming it is complete.

## Contributing

1. Check the roadmap and relevant architecture documentation before starting work.
2. Keep extension-specific business logic out of the Core whenever an extension contract is appropriate.
3. Add or update tests and documentation with each change.
4. Run the relevant quality checks before opening a pull request.
5. Follow the repository's branch, commit and pull-request conventions.

## License

MIT — see [`LICENSE`](LICENSE) if present in this checkout.
