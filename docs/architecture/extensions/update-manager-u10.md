# Update Manager U10.1: Signed Release Manifest Format

## Status

- **Sprint:** U10
- **Step:** U10.1 — release manifest format
- **Status:** Proposed contract for implementation
- **Scope:** Documentation only; no runtime behaviour or registry service is added in this step.

## Purpose

The official Pixely Registry is the authority for package identity and release metadata. Artifact hosting is separate: a release may point to a Pixely, GitHub or GitLab artifact, but the hosting provider is not the publication authority.

A release manifest is the signed, machine-readable description of one immutable package release. The manifest signature binds the package identity, version, compatibility and dependency metadata to the artifact checksum, download location and artifact-signature metadata. The archive also has its own detached signature over the exact archive bytes. These are two distinct signatures with different inputs and purposes.

## Format and encoding

- Format: UTF-8 JSON.
- Schema identifier: `pixely.release-manifest`.
- Initial schema version: `1`.
- Manifest version and package versions use Semantic Versioning 2.0.0.
- Object member order is not semantically significant in the transport JSON.
- The signed representation MUST be produced using JSON Canonicalization Scheme (JCS), RFC 8785, over the complete manifest object with the top-level `signature` member removed.
- The canonical representation is encoded as UTF-8 and signed as bytes. No newline or byte-order mark is appended.

Using a specified canonicalization algorithm prevents different JSON serializers from producing different signature inputs. Implementations MUST NOT invent their own key sorting or whitespace rules.

## Version 1 manifest

```json
{
  "schema": "pixely.release-manifest",
  "schema_version": 1,
  "package": {
    "type": "extension",
    "id": "pixely/gallery",
    "name": "Gallery"
  },
  "release": {
    "version": "1.2.0",
    "channel": "stable",
    "published_at": "2026-10-30T12:00:00Z",
    "release_notes": "https://registry.pixely.example/releases/pixely/gallery/1.2.0"
  },
  "compatibility": {
    "php": ">=8.3",
    "pixely": ">=1.0.0 <2.0.0"
  },
  "dependencies": {
    "requires": {
      "pixely/media": "^1.0"
    },
    "conflicts": {}
  },
  "artifact": {
    "provider": "github",
    "uri": "https://github.com/pixely/gallery/releases/download/1.2.0/gallery.zip",
    "filename": "gallery-1.2.0.zip",
    "size": 245760,
    "sha256": "0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef",
    "signature": {
      "algorithm": "Ed25519",
      "key_id": "pixely-artifact-2026-01",
      "value": "BASE64_ENCODED_ARTIFACT_SIGNATURE"
    }
  },
  "signature": {
    "algorithm": "Ed25519",
    "key_id": "pixely-manifest-2026-01",
    "value": "BASE64_ENCODED_MANIFEST_SIGNATURE"
  }
}
```

The example values are illustrative and MUST NOT be treated as a real published release or trusted signature.

## Field contract

### Root

| Field | Type | Requirement | Meaning |
|---|---|---|---|
| `schema` | string | Required | Exact value `pixely.release-manifest`. |
| `schema_version` | integer | Required | Exact value `1` for this document. |
| `package` | object | Required | Canonical package identity. |
| `release` | object | Required | Immutable release metadata. |
| `compatibility` | object | Required | Runtime compatibility constraints. |
| `dependencies` | object | Required | Required and conflicting package constraints. |
| `artifact` | object | Required | Artifact source and integrity metadata. |
| `signature` | object | Required | Detached Ed25519 signature envelope. |

Unknown fields MUST be rejected for schema version 1. A future schema version may define additional fields.

### Package

- `type`: one of `platform`, `extension`, `theme`, `plugin`, matching the existing `UpdateTargetType` enum.
- `id`: non-empty canonical package identifier. It MUST be stable across releases and MUST match the identifier used by the registry.
- `name`: non-empty display name.

### Release

- `version`: valid SemVer version. A release version is immutable once published.
- `channel`: one of `stable`, `beta`, `dev`, matching `UpdateChannel`.
- `published_at`: RFC 3339 timestamp in UTC (`Z`).
- `release_notes`: optional HTTPS URL to release notes; it is informational and is not an artifact source.

### Compatibility

- Keys are runtime identifiers such as `php` and `pixely`.
- Values are version constraints interpreted by the relevant runtime/package constraint implementation.
- At least the `pixely` constraint MUST be present for extension, theme and plugin releases. Platform releases MUST declare a `php` constraint.

### Dependencies

- `requires`: map of canonical package IDs to version constraints.
- `conflicts`: map of canonical package IDs to version constraints.
- Both maps are required; an empty object means no entries.
- Dependency resolution remains the responsibility of the existing update planner; the manifest only supplies signed metadata.

### Artifact

- `provider`: `pixely`, `github` or `gitlab`, matching `ArtifactProvider`.
- `uri`: absolute HTTPS URL. Redirects and final hosts MUST be checked against the configured artifact-host allow-list; a valid signature does not bypass host policy.
- `filename`: non-empty basename; path separators are forbidden.
- `size`: positive integer byte count, bounded by the configured maximum artifact size.
- `sha256`: exactly 64 lowercase hexadecimal characters, representing the SHA-256 digest of the exact downloaded archive bytes.
- `signature`: required detached Ed25519 signature envelope for the exact archive bytes.

The checksum is checked against the downloaded file before installation. The artifact URI, checksum and artifact-signature envelope are covered by the manifest signature.

#### Artifact signature

- `algorithm`: exact value `Ed25519` for schema version 1.
- `key_id`: non-empty identifier resolving to a trusted artifact-signing public key.
- `value`: strict Base64 encoding of a detached Ed25519 signature over the exact archive bytes, without transformation.
- The public key is selected from local trust configuration; a key supplied by the manifest MUST never be trusted.

### Manifest signature

- `algorithm`: exact value `Ed25519` for schema version 1.
- `key_id`: non-empty identifier resolving to a trusted manifest-signing public key.
- `value`: strict Base64 encoding of a detached Ed25519 signature.
- Signature input: RFC 8785 canonical UTF-8 JSON of the full manifest with only the root-level `signature` member removed. The nested `artifact.signature` object remains part of the signed input.
- Verification uses the trusted public key selected by `key_id`; a public key supplied by the manifest itself MUST never be trusted.

The manifest and artifact signatures MAY use the same key only when local trust policy explicitly permits that key for both purposes. Separate key identifiers and trust purposes are recommended.

## Validation and trust rules

A consumer MUST reject a manifest when any of the following applies:

1. The JSON is invalid, contains duplicate object member names, or is not valid UTF-8.
2. The schema identifier/version is unsupported, or a schema-v1 unknown field is present.
3. A required field is missing, has the wrong type, or violates the constraints above.
4. Package identity, release version, channel, provider or compatibility metadata is invalid.
5. The artifact URI is not HTTPS, its host is not allowed, or a redirect leaves the configured allow-list.
6. The artifact size exceeds the configured maximum, or the downloaded byte count does not match `artifact.size`.
7. The manifest-signing key ID is unknown, revoked or not trusted, the manifest signature encoding is malformed, or manifest Ed25519 verification fails.
8. The downloaded archive SHA-256 does not match `artifact.sha256`, or the artifact signature is malformed, untrusted or invalid.
9. The manifest package ID/version does not match the release requested from the registry.

The manifest signature MUST be verified before its metadata is trusted or its artifact URI is used. After download, the archive size and SHA-256 checksum MUST be checked and the artifact signature MUST be verified before the archive is passed to the installer. All checks are mandatory.

A valid manifest signature proves that the signed release metadata was approved by the holder of a trusted manifest-signing key. A valid artifact signature proves that the exact archive bytes were approved by the holder of a trusted artifact-signing key. Neither proves that the package is safe, compatible or non-malicious. Compatibility, dependency, host-policy and package validation checks remain mandatory.

## Relationship with existing Pixely contracts

| Manifest field | Existing Pixely representation |
|---|---|
| `package.type` | `UpdateTargetType` |
| `package.id`, `package.name` | `UpdateCandidate::packageId`, `UpdateCandidate::name` |
| `release.version`, `release.channel` | `UpdateCandidate::version`, `UpdateCandidate::channel` |
| `dependencies.requires`, `dependencies.conflicts` | `UpdateCandidate::$requires`, `UpdateCandidate::$conflicts` |
| `compatibility` | `UpdateCandidate::$runtimeConstraints` |
| `artifact.provider`, `artifact.uri`, `artifact.sha256` | `ArtifactReference::$provider`, `ArtifactReference::$uri`, `ArtifactReference::$sha256` |
| `artifact.signature.key_id`, `artifact.signature.value` | `ArtifactReference::$keyId`, `ArtifactReference::$signature`; algorithm is fixed to Ed25519 |
| Root `signature` | Verified by a dedicated release-manifest verifier before mapping the manifest to runtime DTOs; it is not `ArtifactReference::$signature`. |

The existing `SodiumUpdateVerifier` verifies the downloaded archive bytes against `ArtifactReference::$signature` and its trusted key. It does not verify a canonical JSON manifest. U10.3 therefore requires a distinct manifest-signature verification path using RFC 8785 canonicalization. The registry adapter may create an `UpdateCandidate` and `ArtifactReference` only after the manifest signature has been verified. `ArtifactSourceInterface` remains responsible for retrieving bytes from the artifact host.

## Explicitly deferred

This step does not define or implement:

- Registry HTTP endpoints or pagination (U10.4).
- Signing-key rotation, revocation distribution or emergency trust-store updates. These require a separate policy decision before remote installation is enabled.
- A registry server, publisher portal or publication workflow.
- Remote download or remote installation.
- A new installer or changes to extension lifecycle and migrations.

## Next U10 steps

- **U10.2:** map the manifest fields to typed PHP DTOs and validation rules.
- **U10.3:** implement RFC 8785 canonicalization and a dedicated manifest-signature verifier, with tests against known Ed25519 test vectors; retain `SodiumUpdateVerifier` for archive-byte signatures.
- **U10.4:** specify the official Registry API endpoints and response envelopes.
- **U10.5:** integrate the validated registry response with `UpdateRegistryInterface`.
- **U10.6:** document and test the complete contract.

## U10.2: Typed PHP DTOs and structural validation

U10.2 introduces immutable DTOs for the schema-v1 release manifest under `App\Core\Updates\DTO`:

- `ReleaseManifest` is the root object and rejects unsupported schema identifiers, schema versions and unknown root fields.
- `ReleaseManifestPackage` maps package identity to `UpdateTargetType`.
- `ReleaseManifestRelease` maps release metadata to `UpdateChannel` and `DateTimeImmutable`.
- `ReleaseManifestCompatibility` validates runtime constraint maps and requires `php` for platform releases or `pixely` for extension, theme and plugin releases.
- `ReleaseManifestDependencies` validates required and conflicting package maps.
- `ReleaseManifestArtifact` maps provider to `ArtifactProvider` and validates HTTPS URI, filename, positive size and lowercase SHA-256.
- `ReleaseManifestSignature` validates the Ed25519 algorithm, key identifier and canonical Base64 encoding of a 64-byte signature.
- `ReleaseManifestValue` contains shared structural validation helpers.

`ReleaseManifest::fromArray()` accepts decoded associative JSON data. The JSON decoding boundary remains responsible for rejecting invalid UTF-8 and duplicate object member names; this cannot be reliably detected after decoding into a PHP associative array.

These DTOs perform structural validation only. They do not verify trust, canonicalize JSON, fetch artifacts or map an unverified manifest to `UpdateCandidate`. Those responsibilities remain in U10.3 and U10.5 respectively. Version constraints are retained as strings here; semantic constraint evaluation remains with the existing update planner.

### U10.2 verification

```powershell
docker compose exec app php artisan test --compact tests/Unit/Core/Updates/ReleaseManifestTest.php
docker compose exec app php artisan test --compact tests/Unit/Core/Updates
```
