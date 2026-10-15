# Changelog

All notable changes to Pixely Platform are documented in this file.

## Unreleased

### Added — S5 Database-backed Extension Registry

- Added a database table and repository for persistent extension runtime state.
- Added `pixely:extensions:import-state` to import existing JSON state idempotently while preserving existing database values.
- Kept the JSON repository as a bootstrap fallback until the database migration has created the new table.

### Migration notes

After deploying this change, run `php artisan migrate`, then `php artisan pixely:extensions:import-state`. The JSON file is retained as a migration source and is not deleted automatically. Back up the database and JSON file before deployment.
