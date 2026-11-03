# Update Manager U12.1: Quarantined Extension Release Downloads

## Scope

U12.1 adds an explicit Artisan-only entry point to download one exact extension package release from the official Registry. It does not install, activate, extract or inspect the downloaded archive. No UI, API endpoint or automatic download path is added.

## Usage

```bash
php artisan pixely:extension:download pixely/gallery 1.2.0
php artisan pixely:extension:download pixely/gallery 1.2.0 --channel=beta
```

The command validates the package identifier and semantic version, resolves that exact extension release, checks the returned identity and requested channel, and checks runtime compatibility. The existing Registry, artifact-host policy and secure artifact source are used; any lookup, compatibility, host-policy or download failure fails the command.

## Quarantine and verification boundary

Successful downloads are stored under the private, non-public `storage/app/extension-release-quarantine` directory using restrictive directory/file permissions and a unique staged filename. The command prints the absolute staged path and marks the artifact as pending U12.2 verification.

**A U12.1 artifact is quarantined and not installable.** U12.1 does not perform the manifest-declared exact-size check, SHA-256 verification or detached artifact-signature verification. The quarantined artifact is never passed to `ExtensionInstaller`; operators must not copy it into the local update inbox or otherwise attempt to install it. U12.2 will add the verification boundary before any installation workflow can consume a remote artifact.

The existing `pixely:update` command remains limited to operator-supplied local inbox ZIPs and continues to own the installation workflow.
