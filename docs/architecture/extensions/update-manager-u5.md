# Extension Update Manager — U5

## Purpose

U5 adds a pre-flight update planner and hardens the existing extension package pipeline. It deliberately reuses `ExtensionInstaller`, `ExtensionPackageValidator`, `ExtensionUpgradeRunner`, and `ExtensionMigrationRunner`; it does not introduce a second installation mechanism.

## Update flow

1. Extract the candidate archive into an isolated staging directory.
2. Validate its manifest and archive entry paths.
3. Build a read-only update plan and validate the installed version, required extensions, minimum dependency versions, and declared conflicts.
4. Keep the current extension directory as a backup, then move the validated package into place.
5. Refresh Composer autoloading, validate the extension class, synchronise permissions, run extension migrations, and execute declared upgrade steps.
6. Delete the file backup only after the update pipeline succeeds.
7. On a failure, remove the candidate directory and restore the previous extension directory.

## Manifest additions

The following optional keys are supported in `extension.php`:

```php
'requires' => ['media-core'],
'require_versions' => ['media-core' => '2.0.0'],
'conflicts' => ['legacy-player'],
```

`requires` contains extension identifiers. `require_versions` defines minimum installed versions for those dependencies. `conflicts` lists extensions that must not be installed alongside the candidate.

## Safety boundaries

- Absolute paths, drive-prefixed paths, traversal segments, dot segments, null bytes, and Unix symbolic-link entries are rejected during archive validation.
- The archive is extracted only into a unique staging directory before it can replace live files.
- The existing extension directory is retained until migrations and upgrade steps finish.
- A failed update restores extension files. Database migrations and already committed upgrade steps are not guaranteed to be reversible; U5 does not claim a full database rollback.
- Dependencies are never installed, upgraded, or removed automatically. Major-version upgrades are not performed implicitly.
- The planner is read-only: it does not modify installed files or versions.

## Verification

Added unit coverage for successful planning, missing dependencies, minimum-version violations, declared conflicts, and non-increasing candidate versions. The package validator's existing Zip Slip tests remain applicable.

Run on the project's supported PHP environment:

```powershell
php artisan test --compact tests/Unit/Core/Extensions/Update tests/Unit/Core/Extensions/Installer/ExtensionPackageValidatorTest.php
php artisan test --compact tests/Feature/Core/Extensions/ExtensionInstallApiTest.php
```
