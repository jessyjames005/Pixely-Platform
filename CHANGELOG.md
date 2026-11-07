# Changelog

All notable changes to Pixely Platform are documented in this file.

## Unreleased

### Added — U16 Sample Cinema reference extension

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
