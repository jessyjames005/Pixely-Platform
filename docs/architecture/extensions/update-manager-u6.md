# Extension Update Command (U6)

## Purpose

U6 introduces one Artisan entry point for discovering and applying extension updates. It orchestrates the existing package validator, update planner and installer instead of implementing a second installation pipeline.

## Package source

The default package inbox is `storage/app/extension-updates`. Place one ZIP archive per extension in this directory. A package must contain a valid `extension.php` manifest at its root or inside one wrapping directory. The `pixely:update` apply command remains local-inbox-only; it does not fetch remote artifacts. Exact remote release downloads are handled separately by `pixely:extension:download`; they are verified and quarantined, but not installable.

## Usage

```bash
# Inspect every available update without changing live files
php artisan pixely:update --dry-run

# Apply every valid update
php artisan pixely:update

# Inspect or update one extension only
php artisan pixely:update --extension=gallery --dry-run
php artisan pixely:update --extension=gallery

# Use another local package directory
php artisan pixely:update --source=/path/to/extension-packages --dry-run
```

## Execution guarantees

1. ZIP entries are extracted to a disposable inspection directory and validated with `ExtensionPackageValidator`.
2. Installed versions, dependency minimums, conflicts and dependency cycles are checked by `ExtensionUpdatePlanner`.
3. `--dry-run` prints the plan and never calls the installer.
4. Real updates are delegated to `ExtensionInstaller::update`, which repeats package validation and owns staging, backup, migrations, upgrade steps, audit logging and file restoration.
5. A package failure is reported and does not prevent later packages from being inspected. The command exits unsuccessfully if at least one package failed.
6. More than one candidate ZIP for the same extension is treated as an error to avoid ambiguous update selection.

## Scope and limitations

- No remote update catalogue, download client, signature trust store or automatic update scheduler is introduced.
- Database migrations and extension upgrade steps are not generally reversible. File restoration does not imply database rollback.
- The command does not uninstall extensions or remove dependencies.
- The update inbox should be writable only by trusted deployment or administrator processes.
