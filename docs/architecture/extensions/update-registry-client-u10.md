# U10.5 — Official Registry client integration

## Scope

U10.5 implements the client-side adapter for the read-only Registry API contract from U10.4. It does not implement the Registry server, publishing workflows, authentication or artifact downloads.

## Runtime components

- `HttpUpdateRegistry` implements `UpdateRegistryInterface` using Laravel's HTTP client.
- `JsonObjectKeyUniquenessGuard` rejects duplicate JSON object member names before PHP decoding. This matters because `json_decode()` otherwise silently keeps the last duplicate member.
- `SodiumReleaseManifestVerifier` verifies the root manifest signature against locally configured `update-manager.signing_public_keys`.
- `ReleaseManifestCanonicalizer` produces the signed canonical representation.
- `ReleaseManifest` validates the complete typed manifest after signature verification.
- Only then is the manifest mapped to `UpdateCandidate` and `ArtifactReference`.

The Registry adapter disables HTTP redirects, requires an HTTPS base URL, applies a timeout, checks the response envelope and bounds pagination traversal. A missing exact release (HTTP 404) maps to `null`; other non-success responses raise `RegistryApiException`.

## Configuration

Configure the official Registry origin and locally trusted public keys:

```dotenv
PIXELY_REGISTRY_URL=https://registry.example.com
PIXELY_REGISTRY_TIMEOUT=10
PIXELY_REGISTRY_PER_PAGE=20
```

`update-manager.signing_public_keys` is an operator-managed map of key IDs to canonical Base64 Ed25519 public keys. Never populate it from Registry responses. The default `registry.pixely.example` URL is a reserved placeholder and must be replaced for a real deployment.

The existing `signing_public_keys` trust map is also used by artifact verification. Registry signing keys and artifact signing keys should use distinct key IDs and be managed deliberately; a future key-purpose separation may be introduced without changing manifest format.

## Interface change

`UpdateRegistryInterface::findRelease()` now requires the package type:

```php
public function findRelease(
    UpdateTargetType $type,
    string $packageId,
    string $version,
): ?UpdateCandidate;
```

This is intentional. A release identity is `(type, package ID, version)`. The adapter never guesses a type from the package ID. Implementations and test doubles of the interface must adopt the new signature.

## Trust and validation sequence

1. Send an HTTPS request to the configured Registry origin. Cross-origin redirects are disabled.
2. Reject malformed JSON and duplicate object member names.
3. Read the root signature envelope and verify it with the local trust store.
4. Convert the authenticated JSON to the typed `ReleaseManifest` DTO; reject unsupported fields and invalid values.
5. Map authenticated metadata to `UpdateCandidate`.
6. Leave artifact host allow-list enforcement, downloaded-byte SHA-256 verification and detached artifact signature verification to the existing artifact download pipeline.

The adapter treats the Registry as an untrusted transport. A valid HTTPS response or a successful HTTP status does not establish release authenticity.

## Pagination and filtering

`findUpdates()` requests the exact package type, package ID, channel and installed version, and forwards supplied runtime versions. It follows opaque cursors, rejects repeated cursors, caps traversal at 1,000 pages, then independently filters exact package/type/channel matches and strictly newer versions. Results are sorted by SemVer descending.

Runtime compatibility constraints remain attached to `UpdateCandidate`; callers/planners must evaluate them locally. Server-side filtering is only an optimisation.

## Tests

`tests/Unit/Core/Updates/HttpUpdateRegistryTest.php` covers signed manifest mapping, tampering rejection, exact package-type resolution, duplicate JSON member rejection and cursor pagination. Run it together with the existing update unit tests and the full suite in the project's Docker environment.

## Acceptance checklist

- [x] HTTP adapter implements `UpdateRegistryInterface`.
- [x] Manifest signatures are verified against locally configured keys before DTO mapping.
- [x] Duplicate JSON object member names are rejected before decoding.
- [x] List and exact-release endpoints follow the U10.4 contract.
- [x] Package type is explicit in exact-release lookup.
- [x] Pagination loops and unexpected response envelopes are rejected.
- [x] No Registry server, persistence or publishing functionality is introduced.


## U10.6 — Contract conformance

The adapter validates `meta.per_page` as an integer between 1 and 100. A response with `has_more: true` must provide a non-empty opaque `next_cursor`; a final response with `has_more: false` must provide `next_cursor: null`. Repeated cursors and traversal beyond 1,000 pages are rejected.

The conformance tests also cover exact-release `404` as `null`, other non-success statuses as `RegistryApiException`, malformed envelopes, unexpected exact-release identity, supported runtime filters and cursor forwarding.

The API contract recommends ETag-based conditional caching but does not require it. U10.6 does not add conditional requests. The adapter currently reports non-success status through `RegistryApiException` and does not expose parsed RFC 9457 fields; the Registry server must nevertheless return the Problem Details response defined by the API contract.
