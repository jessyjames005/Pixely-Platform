# Extension Update Command (U6 / U13)

## Purpose

U6 introduces one Artisan entry point for discovering and applying extension updates. It orchestrates the existing package validator, update planner and installer instead of implementing a second installation pipeline.

U13 extends the command with dependency-aware batch planning and explicitly approved, dependency-first execution. See [U13.1](update-manager-u13.1.md), [U13.2](update-manager-u13.2.md), and [U13.3](update-manager-u13.3.md) for the lot details.

## Package source

The default package inbox is `storage/app/extension-updates`. Place one ZIP archive per extension in this directory. A package must contain a valid `extension.php` manifest at its root or inside one wrapping directory. The `pixely:update` apply command remains local-inbox-only; it does not fetch remote artifacts. Exact remote release downloads are handled separately by `pixely:extension:download`; they are verified and quarantined, but not installable.

## Usage

```bash
# Inspect every available update without changing live files
php artisan pixely:update --dry-run

# Interactively review and approve the dependency-first batch
php artisan pixely:update

# Explicitly approve in a non-interactive environment
php artisan pixely:update --yes --no-interaction

# Inspect or update one extension only
php artisan pixely:update --extension=gallery --dry-run
php artisan pixely:update --extension=gallery

# Use another local package directory
php artisan pixely:update --source=/path/to/extension-packages --dry-run
```

## Execution guarantees

1. ZIP entries are extracted to a disposable inspection directory and validated with `ExtensionPackageValidator`.
2. Installed versions, dependency minimums, conflicts and dependency cycles are checked by `ExtensionUpdatePlanner`.
3. `--dry-run` plans the complete selected batch, displays the deterministic dependency-first order, and never calls the installer.
4. Apply mode builds the same global plan and requires explicit approval before invoking the installer. Interactive confirmation defaults to no; non-interactive runs require `--yes`.
5. Real updates run sequentially through `ExtensionInstaller::update`, which repeats package validation and owns staging, backup, migrations, upgrade steps, audit logging and per-extension file restoration.
6. If an update fails, its transitive dependents are marked blocked while independent candidates continue. The command reports partial outcomes and exits unsuccessfully if any candidate failed or was blocked.
7. More than one candidate ZIP for the same extension is treated as an error to avoid ambiguous update selection.

## Scope and limitations

- No remote update catalogue, download client, signature trust store or automatic update scheduler is introduced.
- The batch is not one transaction. Earlier successful updates remain applied if a later candidate fails, and database migrations or extension upgrade steps are not generally reversible. Per-extension file restoration does not imply database rollback.
- The command does not uninstall extensions or remove dependencies.
- Dependency packages are not downloaded, installed, or removed automatically; all selected update ZIPs must already be in the local inbox.
- The update inbox should be writable only by trusted deployment or administrator processes.
