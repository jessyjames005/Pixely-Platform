# Pixely Platform

> **Build once. Extend forever.**

Pixely Platform is an extensible web platform built with **Laravel, PHP, Vue 3 and TypeScript**. It provides a shared Core and an extension system so that features can be developed, enabled and maintained independently instead of being tightly coupled to the application.

The project is under active development. The roadmap is the source of truth for implementation status; planned capabilities should not be assumed to be production-ready.

## What Pixely is for

Pixely is intended to provide a reusable foundation for applications that need a combination of:

- A public website for pages and navigation.
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

Run end-to-end tests when the E2E profile and its environment are configured:

```powershell
docker compose --profile e2e run --rm e2e
```

Use the project's CI configuration and `package.json` / `composer.json` as the definitive reference for available checks.

## Documentation

- [`VISION.md`](VISION.md) — product goals and principles.
- [`ROADMAP.md`](ROADMAP.md) — implementation status and planned work.
- [`CHANGELOG.md`](CHANGELOG.md) — notable changes.
- [`ARCHITECTURE.md`](ARCHITECTURE.md) — high-level architecture.
- [`docs/architecture/`](docs/architecture/) — architecture documentation and decisions.
- [`docs/handbook/`](docs/handbook/) — practical guides.
- [`docs/api/`](docs/api/) — API documentation and generated specifications.
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
