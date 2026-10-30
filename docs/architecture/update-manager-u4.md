# Pixely Update Manager — U4 artifact delivery and verification

## Responsibilities

U4 adds the secure artifact-delivery boundary. It downloads an artifact to a private staging directory, then verifies the exact downloaded bytes. It does not extract archives, execute extension code, install packages, run migrations, or change the installed extension state.

## Trust model

- The official Pixely Registry remains authoritative for release metadata and the signature/key identifier associated with a release.
- Pixely, GitHub, and GitLab identify artifact hosting only. The host allow-list is configured by the operator and matched against exact hostnames; subdomains are not implicitly trusted.
- HTTPS certificate verification is enabled. Redirects are not followed automatically, preventing an unvalidated redirect from escaping the allow-list. Registry release metadata must therefore provide a direct download URL whose hostname is allowed.
- SHA-256 must match the registry-provided digest.
- A detached Ed25519 signature is mandatory. Public keys are pinned in application configuration by key ID; a downloaded artifact cannot supply or replace its own trusted key.
- Missing keys, malformed signatures, checksum mismatches, unsupported signature verification, non-HTTPS URLs, and unapproved hosts fail closed.

## Configuration

`config/update-manager.php` defines exact `artifact_hosts`, base64-encoded Ed25519 `signing_public_keys`, a maximum artifact size, and a download timeout. Configure actual Pixely-owned hosts and trusted release public keys through deployment configuration before enabling downloads. Do not commit private signing keys. The example Pixely hostname is intentionally a reserved `.example` hostname and must be replaced with the real deployment host.

## Staging and archive safety

The downloader writes a randomly named `.part` file with restrictive permissions in a caller-provided staging directory. It streams bytes to disk and aborts when the configured byte limit is exceeded. U4 does not extract the archive, so archive path traversal (Zip Slip) cannot occur in this phase. Archive validation and safe extraction, if required, must be implemented before U5 installation and must reject absolute paths, `..` traversal, symlinks escaping the extraction root, and excessive expanded sizes.

## Important integration note

`ArtifactSourceInterface::download()` and `UpdateVerifierInterface::verify()` are intentionally separate steps. The caller must always verify the returned staged file before passing it to any future installation workflow. Do not treat a successful download as trusted.
