# Update Manager U12.1–U12.3: Verified and Prepared Quarantined Extension Downloads

## Scope

U12.1 adds an explicit Artisan-only entry point to download one exact extension package release from the official Registry. U12.3 adds a non-executing static preflight after artifact authentication; neither stage installs or activates the extension. No UI, API endpoint or automatic download path is added.

## Usage

```bash
php artisan pixely:extension:download pixely/gallery 1.2.0
php artisan pixely:extension:download pixely/gallery 1.2.0 --channel=beta
```

The command validates the package identifier and semantic version, resolves that exact extension release, checks the returned identity and requested channel, and checks runtime compatibility. The existing Registry, artifact-host policy and secure artifact source are used; any lookup, compatibility, host-policy or download failure fails the command.

## U12.1: Quarantine boundary

Downloads are staged under the private, non-public `storage/app/extension-release-quarantine` directory using restrictive directory/file permissions and a unique staged filename.

The quarantined artifact is never passed to `ExtensionInstaller`.

The existing `pixely:update` command remains limited to operator-supplied local inbox ZIPs and continues to own the installation workflow.

## U12.2: Artifact verification

The authenticated release manifest's exact positive `artifact.size`, SHA-256, and detached Ed25519 signature/key id are carried into `ArtifactReference`. After download and secure-path checks, the command verifies byte size, checksum, and signature against locally trusted keys before reporting success. Missing or invalid size metadata, size/hash mismatches, malformed or absent signatures, and untrusted keys fail closed; failed downloads are cleaned from quarantine where possible.

A successful U12.2 download remained private and quarantined. U12.3 extends this flow only with the static preflight described below; the archive remains **not installable** and is never passed to an installer.

### U12.3: Static package preflight

Only after exact size, SHA-256, and Ed25519 verification succeeds, the command extracts the ZIP into a private disposable inspection directory. ZIP traversal paths and symlinks are rejected. The `extension.php` manifest is parsed with a bounded literal grammar; it is never included, required, or evaluated. Calls, includes, variable expressions, arbitrary statements, and trailing executable statements are rejected. Literal arrays and scalar values, comments, `declare(strict_types=1)`, and qualified `::class` metadata are supported.

Preflight checks that the package id matches the Registry package's final segment, its manifest version matches the exact requested release, the minimum Pixely kernel version is supported, required extensions and minimum versions are installed, declared conflicts are absent, and the declared class has a matching static class declaration in package source. No extension class is loaded. On success, extracted inspection files are deleted and only the authenticated original ZIP remains in quarantine. On failure, both inspection data and the download quarantine directory are cleaned; cleanup failures are reported alongside the original error.

A successful U12.3 command reports the artifact as verified and preflight-passed, but it is still **not installable**. No installation, activation, database migration, or installer call occurs. Explicit installation/replacement is a separate future workflow (U12.4).

### U12.1–U12.3 completion checklist

- [x] Preserve the signed manifest's exact byte size in the candidate artifact reference.
- [x] Verify exact size, SHA-256, and Ed25519 authenticity after download.
- [x] Clean staged data on verification failure; retain verified data only in quarantine.
- [x] Parse and validate the authenticated package without executing package PHP.
- [x] Remove extracted inspection files and retain only the verified ZIP after preflight.
- [x] Keep installation, activation, and migrations out of scope.

### Validation commands

```bash
docker exec pixely_app sh -lc 'cd /var/www && php artisan test --compact tests/Unit/Core/Extensions/Installer/StaticExtensionManifestParserTest.php tests/Unit/Core/Extensions/Installer/ExtensionPackageValidatorTest.php tests/Unit/Core/Extensions/Update/ExtensionUpdatePlannerTest.php tests/Unit/Core/Updates/ArtifactReferenceTest.php tests/Unit/Core/Updates/HttpUpdateRegistryTest.php tests/Unit/Core/Updates/RuntimeCompatibilityCheckerTest.php tests/Unit/Core/Updates/SodiumUpdateVerifierTest.php tests/Feature/Console/DownloadExtensionReleaseCommandTest.php'
docker exec pixely_app sh -lc 'cd /var/www && composer cs:check'
```
