# Changelog

All notable changes to Pixely Platform are documented in this file.

## Unreleased

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

After deploying this change, run `php artisan migrate`, then `php artisan pixely:extensions:import-state`. The JSON file is retained as a migration source and is not deleted automatically. Back up the database and JSON file before deployment.
