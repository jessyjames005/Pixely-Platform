# Update Manager U12.1–U12.7: Quarantined Extension Downloads and Installation

## Scope

U12.1 adds an explicit Artisan-only entry point to download one exact extension package release from the official Registry. U12.3 adds a non-executing static preflight after artifact authentication. U12.4 adds a separate explicit Artisan-only installation/replacement command. U12.5 uses the existing installer pipeline to run extension migrations and versioned upgrade steps. U12.6 improves filesystem rollback error reporting without attempting database rollback. U12.7 exposes a protected, read-only audit history and records outcomes for the U12 package flow. No automatic download/install path is added.

## Usage

```bash
php artisan pixely:extension:download pixely/gallery 1.2.0
php artisan pixely:extension:download pixely/gallery 1.2.0 --channel=beta
```

The command validates the package identifier and semantic version, resolves that exact extension release, checks the returned identity and requested channel, and checks runtime compatibility. The existing Registry, artifact-host policy and secure artifact source are used; any lookup, compatibility, host-policy or download failure fails the command.

## U12.1: Quarantine boundary

Downloads are staged under the private, non-public `storage/app/extension-release-quarantine` directory using restrictive directory/file permissions and a unique staged filename.

The download command never passes the quarantined artifact to `ExtensionInstaller`. Only the separate U12.4 command may pass it to the installer, after re-verification, static preflight, and explicit operator approval.

The existing `pixely:update` command remains limited to operator-supplied local inbox ZIPs; Registry release installation uses the separate U12.4 workflow.

## U12.2: Artifact verification

The authenticated release manifest's exact positive `artifact.size`, SHA-256, and detached Ed25519 signature/key id are carried into `ArtifactReference`. After download and secure-path checks, the command verifies byte size, checksum, and signature against locally trusted keys before reporting success. Missing or invalid size metadata, size/hash mismatches, malformed or absent signatures, and untrusted keys fail closed; failed downloads are cleaned from quarantine where possible.

A successful U12.2 download remained private and quarantined. U12.3 extends the download flow only with the static preflight described below; downloading still does not install or activate the extension.

### U12.3: Static package preflight

Only after exact size, SHA-256, and Ed25519 verification succeeds, the command extracts the ZIP into a private disposable inspection directory. ZIP traversal paths and symlinks are rejected. The `extension.php` manifest is parsed with a bounded literal grammar; it is never included, required, or evaluated. Calls, includes, variable expressions, arbitrary statements, and trailing executable statements are rejected. Literal arrays and scalar values, comments, `declare(strict_types=1)`, and qualified `::class` metadata are supported.

Preflight checks that the package id matches the Registry package's final segment, its manifest version matches the exact requested release, the minimum Pixely kernel version is supported, required extensions and minimum versions are installed, declared conflicts are absent, and the declared class has a matching static class declaration in package source. No extension class is loaded. On success, extracted inspection files are deleted and only the authenticated original ZIP remains in quarantine. On download/preflight failure, inspection data and the download quarantine directory are cleaned; cleanup failures are reported alongside the original error.

A successful U12.3 command reports the artifact as verified and preflight-passed, but does not install it. No activation, migration, or installer call occurs during download.

### U12.4: Explicit installation or replacement

Pass the exact path printed by the successful download command to the separate install command:

```bash
php artisan pixely:extension:install pixely/gallery 1.2.0 /absolute/path/from/download.zip
php artisan pixely:extension:install pixely/gallery 1.2.0 /absolute/path/from/download.zip --channel=beta
```

The install command accepts only a regular, non-symlink file exactly one directory below the private release quarantine. It does not accept arbitrary local ZIPs, download automatically, or derive an artifact path from package input. Before any prompt or installer call, it re-fetches the exact authenticated Registry release, checks its identity, channel and runtime compatibility, re-verifies exact size/SHA-256/Ed25519 authenticity, and repeats the non-executing static package preflight. If the extension is already installed, its canonical Registry package id must match and the existing update planner must accept the version progression; otherwise the command follows the install path. The artifact is retained in quarantine after installation for traceability.

By default, the command asks an interactive confirmation naming the extension and version and warning that publisher PHP may execute, files may be replaced, and migrations or upgrade steps can change the database. A declined prompt makes no installer call and leaves the artifact untouched. Automation must explicitly pass `--yes`; non-interactive use without `--yes` is rejected. The existing installer owns backup, replacement, autoloading, permission synchronization, migrations and upgrade steps. Its file rollback does not generally reverse database changes or migrations, so the administrator is responsible for a database backup before approval.

### U12.5: Migration and upgrade execution

The existing installer runs the extension migration runner after placing and validating the new extension files. Pending extension migrations are discovered from that extension's migration directory, checked against the minimum Pixely kernel version declared in its manifest, executed in migration order, and recorded in the extension migration repository. The installer then records the target version for a fresh installation or, for an update, applies declared upgrade steps strictly after the installed version and up to the target version.

Each declared upgrade step is applied in its own database transaction where supported. Its installed-version marker and audit entry are recorded after the step succeeds. If a later step fails, earlier successful steps remain applied and recorded; later steps do not run. The installer restores the previous extension files on failure, but migrations and completed upgrade steps are not generally reversible and are not covered by that file rollback. Back up the database before approving an installation or update.

U12.5 reuses `ExtensionInstaller`, `ExtensionMigrationRunner`, and `ExtensionUpgradeRunner`; it introduces no parallel migration mechanism and does not add automatic rollback of database changes.

### U12.6: File rollback and recovery reporting

The installer checks that directory backup, replacement, removal, restoration, and staging cleanup operations actually complete. If an install or update fails, it preserves the original failure as the primary reason and appends any file rollback errors with the failed action and path. If the previous extension cannot be restored, the error warns that the extension may be left in an inconsistent filesystem state and identifies the backup path when it remains available. The restored files trigger an autoloader refresh; a refresh failure is included in rollback details rather than discarded.

A failed fresh installation removes the candidate live directory when possible; a failed update restores the previous extension directory when possible. Successful updates continue to remove the backup after migration and upgrade work completes. No database migration or upgrade rollback is attempted. If a failure occurs after migration or upgrade work begins, the operator is warned that database changes may have partially applied, are not automatically rolled back, and require a database backup plus manual assessment before retry or restoration. This warning does not imply that all changes were applied.

### U12.7: Extension audit history

The append-only `extension_audit_logs` table and `ExtensionAuditLogger` remain the single audit store. The read-only collection is available at `GET /api/v1/extensions/audit-logs` and requires authentication plus `system.extensions.install`, because audit records can reveal operational details. It accepts `page` (default 1), `per_page` (default 15, maximum 100), `extension_id`, `action`, and `outcome` (`success` or `failure`) filters. Invalid values return the API's JSON:API validation error.

The response is a JSON:API collection: each `data` resource has type `extension-audit-logs`, its numeric `id`, and `attributes` for `user_id`, `extension_id`, `action`, `version`, `outcome`, `stage`, safe `details`, and `created_at`. Pagination is in `meta.pagination` (`current_page`, `last_page`, `per_page`, `total`) and page URLs are in `links` (`first`, `last`, `prev`, `next`). Historical free-form details such as package-provided descriptions are not returned; only recognized outcome and stage values are exposed.

The exact Registry download and install commands record successful and failed download/preflight stages when the Registry identity and version have been verified. The shared installer records successful and failed install/update outcomes once the package manifest identity and version are trustworthy. Records contain only outcome and a bounded stage name; raw package contents, file paths, and exception messages are not persisted as audit details. Audit persistence failures are reported but do not replace the operation's result or trigger a filesystem rollback.

### U12.1–U12.7 completion checklist

- [x] Preserve the signed manifest's exact byte size in the candidate artifact reference.
- [x] Verify exact size, SHA-256, and Ed25519 authenticity after download.
- [x] Clean staged data on verification failure; retain verified data only in quarantine.
- [x] Parse and validate the authenticated package without executing package PHP.
- [x] Remove extracted inspection files and retain only the verified ZIP after preflight.
- [x] Keep installation, activation and migrations out of the download command.
- [x] Add an explicit quarantine-only install/replacement command with re-verification and operator approval.
- [x] Preserve the authenticated artifact after installation and document database backup responsibility.
- [x] Run pending extension migrations and versioned upgrade steps through the existing installer pipeline.
- [x] Check file replacement, restoration, and cleanup outcomes; report rollback paths and database recovery caveats without automatic database rollback.
- [x] Expose the append-only audit history behind `system.extensions.install`, with validated filters and pagination.
- [x] Record trusted download, preflight, install, and update outcomes without persisting package contents, paths, or exception messages.

### Validation commands

```bash
docker exec pixely_app sh -lc 'cd /var/www && php artisan test --compact tests/Unit/Core/Extensions/Database/ExtensionMigrationRunnerTest.php tests/Feature/Core/Extensions/Versioning/ExtensionUpgradeRunnerTest.php tests/Feature/Extensions/Gallery/GalleryUpgradeStepsTest.php'
docker exec pixely_app sh -lc 'cd /var/www && php artisan test --compact tests/Unit/Core/Extensions/Installer/ExtensionInstallerFilesystemTest.php'
docker exec pixely_app sh -lc 'cd /var/www && php artisan test --compact tests/Unit/Core/Extensions/Installer/StaticExtensionManifestParserTest.php tests/Unit/Core/Extensions/Installer/ExtensionPackageValidatorTest.php tests/Unit/Core/Extensions/Update/ExtensionUpdatePlannerTest.php tests/Unit/Core/Updates/ArtifactReferenceTest.php tests/Unit/Core/Updates/HttpUpdateRegistryTest.php tests/Unit/Core/Updates/RuntimeCompatibilityCheckerTest.php tests/Unit/Core/Updates/SodiumUpdateVerifierTest.php tests/Feature/Console/DownloadExtensionReleaseCommandTest.php tests/Feature/Console/InstallExtensionReleaseCommandTest.php'
docker exec pixely_app sh -lc 'cd /var/www && composer phpstan'
docker exec pixely_app sh -lc 'cd /var/www && composer cs:check'
```
