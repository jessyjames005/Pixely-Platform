## [Unreleased]

### Added
- Complete TMDB v3 API route coverage across all 28 categories in `TmdbImportService` (account, auth, certifications, changes, collections, companies, configuration, credits, discover, find, genres, guest sessions, keywords, lists, movie lists, movies, networks, people lists, people, reviews, search, trending, TV series lists, TV series, seasons, episodes, episode groups, and watch providers).
- Public TMDB API proxy endpoints in `PublicMovieController` and registered them in CinemaMovie API routes for real data fetching.
- Website Engine S3 foundations: scheduled publication, render cache, locale support, sitemap/robots, image blocks, hierarchical menu items.

All notable changes to Pixely Platform are documented in this file.

## Unreleased

### Added — U18 Stabilisation and stable-release preparation (lots U18.1–U18.4)

- Verified repeatable clean install: environment (`php artisan about`), storage symlink, database-backed cache/queue/session, mail via Mailpit, and `php artisan migrate:status` showing all core and extension migrations ran.
- Verified extension permission synchronization (`pixely:sync-permissions`) across `files`, `cinema-movie`, `gallery`, `translations`, `tuleap`.
- Verified security and API surfaces: authentication, RBAC surface access control, strict JSON:API error envelopes, and OpenAPI specification generation/validation (`php artisan scramble:export`).
- Verified extension SDK v2: `make:extension` scaffolding, manifest/dependency validation, lifecycle enable/disable, migration runner, and extension state persistence across 182 backend/console unit+feature tests.
- Verified production frontend build (`npm run build`) and Storybook production build (`npm run build-storybook`); swagger UI documentation page accessible.

### Documentation and architecture

- Recorded the deployment boundary: one Pixely installation manages one independent website; multi-site / multi-tenant hosting is deferred (ADR-0010).
- Aligned the product vision, architecture overview, roadmap and generated GitHub wiki with Planning Global V4.0 (17 November 2026).
- Replaced the older active sprint sequence with the V4 execution plan: U17.4 close, U18 stabilisation, Website Engine, public extension contracts, Gallery/R1, R2, R3 and later extensions.

> Known U18 limitations not covered in this release: release documentation (install/update/rollback guide, troubleshooting, release notes, feature/limitation matrix), demonstrated backup/restore, frontend E2E/Playwright execution, and lint/static-analysis CI gates.

### Added — U17 Design System and Storybook foundation

- Added Storybook for the Vite/Vue 3 frontend with Vuetify 3 integration and a dedicated component documentation workflow.
- Added Pixely Design System tokens and shared UI/UX component wrappers for consistent platform-owned interfaces.
- Added Material-style authentication page components for login, email verification, password recovery/reset and two-step verification.
- Added the `PixelyCalendar` component and Storybook stories, including the CinemaMovie calendar/agenda use case.


## [U17] - 2026-11-19
### Design System, UI/UX Kit & Storybook Integration
- Initialized Storybook with Vite, Vue 3, and Vuetify 3 theme integration (U17.1).
- Implemented comprehensive Pixely Design System component library, tokens, and layout primitives (U17.2).
- Added interactive Storybook stories for core components and extension widgets like `CinemaCalendarAgenda` (U17.3).
- Performed production Storybook build verification, accessibility/state checks, and sprint close (U17.4).

## [U16]

- Added CinemaMovie as an SDK v2 reference extension with a bilingual movie and genre catalogue, authenticated JSON:API resources, permission-aware administration, and Files poster/backdrop relationships.
- Added isolated Playwright E2E infrastructure with Chromium and Firefox projects, reusable page objects, and seeded administrator credentials supplied only at runtime.
- Added browser journeys for CinemaMovie administration access and movie/genre create, edit, and delete workflows.
- Documented safe local E2E execution and CI diagnostics. The browser job remains non-blocking while the suite is stabilised.

### Added — U15 Extension SDK developer tools and documentation

- Hardened `php artisan make:extension` with extension-name validation, safe directory handling, SDK v2 test starters, and migration-runner guidance.
- Added standard PHPUnit discovery for extension-local backend tests and a reusable helper for JSON:API headers and users with declared extension permissions.
- Added extension SDK, development, frontend, Design System, testing, and Storybook guidance. Storybook itself remains uninstalled and unconfigured.

### Added — U14 Central Registry and extension catalogue

- Added operator-controlled import of locally stored, trusted schema-v1 release manifests with signature verification, idempotency, and immutable package/version records.
- Added public release listing and exact resolution, preserving the original signed manifest and providing SemVer filters, encrypted cursor pagination, RFC 9457 errors, caching headers, and per-IP/per-client rate limits.
- Added public package discovery with case-insensitive partial search by name or package ID, package-type filtering, cursor pagination, and stable/latest available release summaries.

### Added — U13 Dependency-aware extension update batches

- Added global validation and deterministic dependency-first planning for locally available extension update packages, including candidate-supplied minimum dependency versions.
- Added a dependency-ordered dry-run that reports the proposed execution order and planning blockers.
- Required explicit approval before batch updates; non-interactive runs require `--yes`.
- Added partial outcome reporting: failed updates block dependent extensions while independent updates continue.
- Documented that batches are sequential and do not provide a global transaction or guaranteed database rollback.

### Added — U12 Extension package installation and update flow

- Added an explicit Registry release download workflow that keeps packages in private quarantine until separately approved for installation.
- Added exact artifact-size, SHA-256, and Ed25519 verification, plus static package preflight that does not execute package PHP.
- Added an explicitly approved install/replace command that re-verifies releases and uses the existing migration and upgrade pipeline.
- Improved file rollback and cleanup error reporting while warning operators that database migrations and completed upgrade steps are not automatically reversed.
- Added a protected, paginated extension audit-history API and outcome records for trusted download, preflight, install, and update operations.

### Added — S5 Database-backed Extension Registry

- Added a database table and repository for persistent extension runtime state.
- Added `pixely:extensions:import-state` to import existing JSON state idempotently while preserving existing database values.
- Kept the JSON repository as a bootstrap fallback until the database migration has created the new table.

### Migration notes

For U14, run `php artisan migrate`, configure trusted public keys under `update-manager.signing_public_keys`, and import signed manifests with `php artisan pixely:registry:import <manifest-path>`. Back up the database and manifest source files before deployment.

For S5, run `php artisan migrate`, then `php artisan pixely:extensions:import-state`. The JSON file is retained as a migration source and is not deleted automatically. Back up the database and JSON file before deployment.
