# Update Manager U12.1–U12.2: Verified Quarantined Extension Downloads

## Scope

U12.1 adds an explicit Artisan-only entry point to download one exact extension package release from the official Registry. It does not install, activate, extract or inspect the downloaded archive. No UI, API endpoint or automatic download path is added.

## Usage

```bash
php artisan pixely:extension:download pixely/gallery 1.2.0
php artisan pixely:extension:download pixely/gallery 1.2.0 --channel=beta
```

The command validates the package identifier and semantic version, resolves that exact extension release, checks the returned identity and requested channel, and checks runtime compatibility. The existing Registry, artifact-host policy and secure artifact source are used; any lookup, compatibility, host-policy or download failure fails the command.

## U12.1: Quarantine boundary

Downloads are staged under the private, non-public `storage/app/extension-release-quarantine` directory using restrictive directory/file permissions and a unique staged filename.

U12.1 downloads only; it does not install, activate, extract or inspect the archive. The quarantined artifact is never passed to `ExtensionInstaller`.

The existing `pixely:update` command remains limited to operator-supplied local inbox ZIPs and continues to own the installation workflow.

## U12.2: Artifact verification

The authenticated release manifest's exact positive `artifact.size`, SHA-256, and detached Ed25519 signature/key id are carried into `ArtifactReference`. After download and secure-path checks, the command verifies byte size, checksum, and signature against locally trusted keys before reporting success. Missing or invalid size metadata, size/hash mismatches, malformed or absent signatures, and untrusted keys fail closed; failed downloads are cleaned from quarantine where possible.

A successful U12.2 download remains private and quarantined. The command reports it as verified, but it is **not installable** and is never passed to an installer. Explicit administrator installation remains a separate future workflow (U12.3); no approval, preparation, or installation behavior is included here.

### U12.2 completion checklist

- [x] Preserve the signed manifest's exact byte size in the candidate artifact reference.
- [x] Verify exact size, SHA-256, and Ed25519 authenticity after download.
- [x] Clean staged data on verification failure; retain verified data only in quarantine.
- [x] Keep installation and activation out of scope.

### Validation commands

```bash
docker exec pixely_app sh -lc 'cd /var/www && php artisan test --compact tests/Unit/Core/Updates/ArtifactReferenceTest.php tests/Unit/Core/Updates/SodiumUpdateVerifierTest.php tests/Unit/Core/Updates/HttpUpdateRegistryTest.php tests/Feature/Console/DownloadExtensionReleaseCommandTest.php'
docker exec pixely_app sh -lc 'cd /var/www && composer cs:check'
```
