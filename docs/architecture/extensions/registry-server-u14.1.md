# Registry server foundation — U14.1

U14.1 adds the Registry's local relational store and an operator-run importer
for already signed schema-v1 release manifests. It does not expose Registry
HTTP endpoints.

## Import a signed manifest

Configure trusted Ed25519 public keys in `config/update-manager.php` under
`signing_public_keys`, indexed by key ID and encoded as Base64. The import
command accepts a local file controlled by the operator:

```bash
docker exec pixely_app bash -c \
  'cd /var/www && php artisan pixely:registry:import storage/app/registry/releases/my-extension-1.2.0.json'
```

The file must contain one complete schema-v1 manifest with its signature
envelope. Import checks for duplicate JSON object keys, verifies the root
signature against the locally configured trusted keys, then validates the
manifest through the shared `ReleaseManifest` DTO. Invalid, unreadable, or
untrusted input fails the command; it is never skipped. The command only reads
local files and does not fetch remote content or execute imported files.

Re-importing the same signed release is a no-op. The command reports whether a
manifest was imported or was already present.

## Storage and immutability

- `registry_packages` stores one package per `(type, package_id)` across the
  supported platform, extension, theme, and plugin types.
- `registry_releases` stores release channel, publication time, the package
  name at publication, the exact original signed JSON bytes, and a SHA-256
  fingerprint. A unique `(registry_package_id, version)` constraint enforces
  one release per package version.
- The fingerprint combines the JCS-canonical unsigned manifest and its
  signature envelope, so whitespace and JSON object member ordering do not
  change release identity.
- If an imported package type, ID, and version already exist, an identical
  fingerprint is accepted without changing stored data. Any changed signed
  content or signature for that identity is rejected; neither the release nor
  package metadata is updated.
- The package's searchable display name follows the latest release by
  `published_at`, then SemVer precedence (and a lexical tie-break for
  SemVer-equivalent versions). Older releases cannot replace a newer release's
  name.
- Package and release writes are transactional. A rejected import leaves no
  partial package or release row.

The raw signed JSON is retained for later read-only Registry endpoints; the
importer does not reconstruct or normalize it.

## U14.1 scope

This lot provides database persistence and the local Artisan import command
only. There are no public or administrative HTTP routes, search/list APIs,
publishing or upload portal, account management, or moderation workflows.
