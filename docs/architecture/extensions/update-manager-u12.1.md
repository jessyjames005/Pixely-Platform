# Update Manager U12.1–U12.4: Quarantined Extension Downloads and Explicit Installation

## Scope

U12.1 adds an explicit Artisan-only entry point to download one exact extension package release from the official Registry. U12.3 adds a non-executing static preflight after artifact authentication. U12.4 adds a separate explicit Artisan-only installation/replacement command. No UI, API endpoint or automatic download/install path is added.

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

### U12.1–U12.4 completion checklist

- [x] Preserve the signed manifest's exact byte size in the candidate artifact reference.
- [x] Verify exact size, SHA-256, and Ed25519 authenticity after download.
- [x] Clean staged data on verification failure; retain verified data only in quarantine.
- [x] Parse and validate the authenticated package without executing package PHP.
- [x] Remove extracted inspection files and retain only the verified ZIP after preflight.
- [x] Keep installation, activation and migrations out of the download command.
- [x] Add an explicit quarantine-only install/replacement command with re-verification and operator approval.
- [x] Preserve the authenticated artifact after installation and document database backup responsibility.

### Validation commands

```bash
docker exec pixely_app sh -lc 'cd /var/www && php artisan test --compact tests/Unit/Core/Extensions/Installer/StaticExtensionManifestParserTest.php tests/Unit/Core/Extensions/Installer/ExtensionPackageValidatorTest.php tests/Unit/Core/Extensions/Update/ExtensionUpdatePlannerTest.php tests/Unit/Core/Updates/ArtifactReferenceTest.php tests/Unit/Core/Updates/HttpUpdateRegistryTest.php tests/Unit/Core/Updates/RuntimeCompatibilityCheckerTest.php tests/Unit/Core/Updates/SodiumUpdateVerifierTest.php tests/Feature/Console/DownloadExtensionReleaseCommandTest.php tests/Feature/Console/InstallExtensionReleaseCommandTest.php'
docker exec pixely_app sh -lc 'cd /var/www && composer phpstan'
docker exec pixely_app sh -lc 'cd /var/www && composer cs:check'
```
