# Official Pixely Registry API Contract

## Status

- **Sprint:** U10
- **Step:** U10.4 — official Registry API contract
- **Status:** Proposed contract for client/server implementation
- **Scope:** API contract only. This step does not add HTTP routes, controllers, persistence, authentication or a Registry server.

## 1. Purpose and authority

The official Pixely Registry is the authoritative catalogue for package identity and release metadata. GitHub, GitLab and Pixely storage may host release artifacts, but they are not registries and do not define package identity or trust.

The API returns schema-v1 signed release manifests defined in `update-manager-u10.md`. A client MUST verify each manifest signature using its locally configured trust store before treating its metadata or artifact URI as trusted. HTTPS protects transport; it does not replace manifest-signature verification.

The API is read-only in this contract. Publishing, unpublishing, account management, key administration and moderation are outside U10.4.

## 2. Base URL and versioning

- Production base URL: `https://registry.pixely.example/api/v1`.
- All endpoints use HTTPS.
- API version is part of the path (`/v1`). A breaking contract change requires a new major API path.
- Requests and responses use `application/json; charset=utf-8`.
- The API MUST NOT redirect requests to a different origin.
- The production hostname is illustrative until the official Registry deployment is provisioned.

## 3. Package and release identity

A package is identified by the pair `(package.type, package.id)`. A release is identified by `(package.type, package.id, release.version)`. The package ID is an opaque canonical identifier; clients MUST NOT infer its structure or split it on `/`.

Package IDs are passed as query values rather than URL path segments. This avoids path ambiguity for identifiers such as `pixely/gallery`. Clients MUST percent-encode query parameter values according to RFC 3986.

## 4. Endpoints

### 4.1 List releases

`GET /releases`

Returns releases for one package, newest first, with optional channel and installed-version filters.

#### Query parameters

| Parameter | Type | Required | Rules |
|---|---|---:|---|
| `package_type` | string | Yes | `platform`, `extension`, `theme` or `plugin`. |
| `package_id` | string | Yes | Exact canonical package ID; non-empty. |
| `channel` | string | No | `stable` by default; `beta` or `dev` require explicit opt-in. |
| `after_version` | string | No | SemVer version; only strictly newer releases are returned. |
| `runtime[php]` | string | No | PHP runtime version used for compatibility filtering. |
| `runtime[pixely]` | string | No | Pixely runtime version used for compatibility filtering. |
| `runtime[laravel]` | string | No | Laravel runtime version when relevant. |
| `per_page` | integer | No | Default `20`; minimum `1`; maximum `100`. |
| `cursor` | string | No | Opaque cursor returned by the previous page. |

The server MAY use runtime parameters to reduce irrelevant results. The client MUST still evaluate the signed compatibility constraints locally. The server MUST NOT treat omitted runtime values as proof of compatibility.

The server MUST return only releases whose channel exactly matches the requested channel. If `after_version` is supplied, only strictly newer SemVer releases are eligible. Results MUST be ordered by SemVer descending; a package version is immutable.

#### Success response — `200 OK`

```json
{
  "data": [
    {
      "manifest": {
        "schema": "pixely.release-manifest",
        "schema_version": 1,
        "package": {},
        "release": {},
        "compatibility": {},
        "dependencies": {},
        "artifact": {},
        "signature": {}
      }
    }
  ],
  "meta": {
    "per_page": 20,
    "next_cursor": "opaque-cursor-or-null",
    "has_more": false
  }
}
```

The `manifest` member is the complete schema-v1 manifest, not a reduced or reconstructed representation. Empty result sets return `data: []`, `has_more: false` and `next_cursor: null`.

The example is illustrative; `{}` placeholders are not valid manifests. The actual manifest fields and signature rules are defined in `update-manager-u10.md`.

### 4.2 Resolve an exact release

`GET /releases/resolve`

Returns one exact immutable release. This endpoint maps directly to `UpdateRegistryInterface::findRelease()`.

#### Query parameters

| Parameter | Type | Required | Rules |
|---|---|---:|---|
| `package_type` | string | Yes | `platform`, `extension`, `theme` or `plugin`. |
| `package_id` | string | Yes | Exact canonical package ID. |
| `version` | string | Yes | Exact SemVer version. |

#### Success response — `200 OK`

```json
{
  "data": {
    "manifest": {
      "schema": "pixely.release-manifest",
      "schema_version": 1,
      "package": {},
      "release": {},
      "compatibility": {},
      "dependencies": {},
      "artifact": {},
      "signature": {}
    }
  }
}
```

The response MUST contain exactly the requested package type, package ID and version. The client MUST compare these values with its request after signature verification. A response for a different identity MUST be rejected.

## 5. Pagination

Cursor pagination is used instead of offset pagination so that new releases do not shift later pages during traversal.

- Cursors are opaque, URL-safe strings.
- Clients MUST NOT construct, decode, modify or persist cursors beyond the current traversal.
- A cursor is bound to the original filters; reusing it with different filters returns `400 invalid_cursor`.
- The server MUST provide a stable ordering by semantic version descending and a deterministic tie-breaker if future package rules permit equal precedence versions.
- The final page has `has_more: false` and `next_cursor: null`.
- Clients MUST stop when `has_more` is false; they MUST NOT infer completion from page length.

The server SHOULD cap cursor lifetime and SHOULD return a clear `invalid_cursor` error when a cursor expires.

## 6. Error envelope

Errors use the RFC 9457 Problem Details shape with `application/problem+json`.

```json
{
  "type": "https://registry.pixely.example/problems/invalid-query",
  "title": "Invalid query",
  "status": 400,
  "detail": "The requested channel is not supported.",
  "code": "invalid_channel",
  "request_id": "req_01J..."
}
```

`type`, `title` and `status` are required. `detail`, `code` and `request_id` are recommended. Clients MUST branch on the HTTP status and stable `code`, not on the human-readable `title` or `detail`.

### Status and error codes

| HTTP | Code | Meaning |
|---:|---|---|
| `400` | `invalid_query` | Missing, malformed or unsupported query parameter. |
| `400` | `invalid_cursor` | Cursor is malformed, expired or bound to other filters. |
| `404` | `package_not_found` | No package exists for the exact type and ID. |
| `404` | `release_not_found` | No exact release exists for the requested identity and version. |
| `406` | `unsupported_manifest_schema` | The Registry cannot provide a manifest schema supported by the request. |
| `429` | `rate_limited` | Request rate limit exceeded. |
| `500` | `internal_error` | Unexpected Registry failure. |
| `503` | `registry_unavailable` | Registry is temporarily unavailable. |

A missing package or release is a normal not-found result, not an empty successful response for the exact-release endpoint. The list endpoint returns `200` with an empty `data` array when the package exists but has no matching releases.

## 7. HTTP caching and immutability

Published release identity and signed manifest content are immutable. A correction requires publishing a new version; the Registry MUST NOT silently replace a manifest for an existing package version.

- Exact-release responses SHOULD include `ETag` and `Cache-Control: public, max-age=300`.
- List responses SHOULD include `Cache-Control: public, max-age=60`.
- Conditional requests using `If-None-Match` MAY return `304 Not Modified`.
- A `304` response has no body.
- Clients MUST NOT interpret a cached manifest as trusted until its signature has been verified in the current trust context.
- A manifest whose signing key is locally revoked MUST be rejected even if it was previously cached and verified.

## 8. Transport, security and privacy

- Registry endpoints are read-only and do not require user authentication in the initial public catalogue.
- The server MUST apply per-IP and per-client rate limits and return `429` with `Retry-After`.
- The server MUST impose request-target, query-length, response-size and page-size limits.
- The server MUST validate all query values and MUST NOT interpolate them into SQL or filesystem paths.
- The client MUST enforce HTTPS and a configured Registry base URL; it MUST NOT follow cross-origin redirects.
- The client MUST apply manifest verification, artifact host allow-list checks, artifact size limits, checksum verification and artifact-signature verification as separate mandatory controls.
- Registry availability failure MUST NOT prevent the installed platform or extensions from running.
- The Registry MUST NOT receive secrets, environment variables, local file paths or unnecessary user data. Runtime versions are the only client environment metadata in this contract.

## 9. Mapping to Pixely update contracts

| `UpdateRegistryInterface` operation | Registry request | Mapping |
|---|---|---|
| `findUpdates(UpdateQuery $query)` | `GET /releases` | Query package type/ID, channel, installed version as `after_version`, and supported runtime versions. Verify each manifest before mapping it to `UpdateCandidate`. |
| `findRelease(UpdateTargetType $type, string $packageId, string $version)` | `GET /releases/resolve` | Query exact package type, package ID and version. Verify the manifest before mapping it and reject any returned identity mismatch. |

U10.5 resolved the original package-type gap by adding `UpdateTargetType` as the first argument to `UpdateRegistryInterface::findRelease()`. The client MUST NOT infer package type from the package ID or select an arbitrary matching release.

The existing `UpdateCandidate` and `ArtifactReference` are created only after the manifest has passed structural validation and Ed25519 signature verification. `SodiumUpdateVerifier` remains responsible for detached signatures over downloaded artifact bytes.

## 10. Compatibility and forward evolution

- API v1 serves signed release-manifest schema version 1.
- The API MUST NOT rewrite, normalize or re-sign a publisher's manifest while serving it.
- New optional API envelope metadata may be added without changing the manifest schema.
- Breaking changes to endpoint semantics or pagination require `/api/v2`.
- A future manifest schema is negotiated explicitly and must not be silently downgraded.
- Publishing, key rotation/revocation, webhook delivery, search across all packages, categories, ratings and download analytics are outside this contract.

## 11. Acceptance criteria for U10.4

- [x] List and exact-release endpoints are defined.
- [x] Package type and package ID are unambiguous.
- [x] Stable, beta and dev channel behaviour is explicit.
- [x] Runtime filtering is advisory; local compatibility validation remains mandatory.
- [x] Cursor pagination and deterministic ordering are defined.
- [x] Success and RFC 9457 error envelopes are defined.
- [x] HTTP caching and immutable-release rules are defined.
- [x] The contract maps to `UpdateRegistryInterface`, including the existing package-type gap.
- [x] No handwritten OpenAPI YAML/JSON is added. Runtime API documentation will be generated from the PHP source using the project's established Scramble workflow when server endpoints are implemented.


## 12. U10.6 client conformance and verification

U10.6 closes the client-side contract verification loop. The Registry remains a proposed external service contract; this step does not implement its server.

The Laravel client conformance tests cover:

- Signed manifest verification before mapping to update DTOs, including tampering rejection.
- Exact package type and release identity on resolution.
- The documented `404` exact-release behaviour (`null`) and exception behaviour for other non-success HTTP statuses.
- Rejection of malformed success envelopes and invalid pagination metadata.
- Opaque cursor forwarding across pages and forwarding of supported runtime filters.
- Duplicate JSON object member rejection before decoding.

The client validates `meta.per_page` as an integer from 1 through 100, requires a non-empty cursor when `has_more` is true, and requires `next_cursor: null` on the final page. It independently filters package identity, channel and strictly newer versions, then sorts results by SemVer descending.

Conditional caching (`ETag`, `If-None-Match`, `304`) remains optional at the API contract level and is not implemented by this client. The client does not parse RFC 9457 error bodies; it raises `RegistryApiException` with the HTTP status in its message. This does not change the server's obligation to return the documented Problem Details envelope.

### U10.6 acceptance criteria

- [x] The API mapping reflects the typed `findRelease()` signature introduced in U10.5.
- [x] List and exact-release success envelopes have client conformance tests.
- [x] Exact-release not-found and non-success HTTP behaviour are tested.
- [x] Invalid response envelopes and pagination metadata are rejected.
- [x] Runtime filters and opaque cursor forwarding are tested.
- [x] The client enforces the documented final-page cursor rule and page-size bounds.
- [x] Optional caching and current error-body handling are explicitly documented.
- [x] No Registry server, persistence, publishing or artifact download/install flow is introduced.

## 13. U14.3 public package discovery extension

U14.3 extends the read-only v1 Registry contract with:

`GET /packages`

Optional query parameters:

| Parameter | Type | Rules |
|---|---|---|
| `q` | string | Case-insensitive partial match against package name or package ID; trimmed and limited to 100 characters. |
| `package_type` | string | `platform`, `extension`, `theme`, or `plugin`. |
| `per_page` | integer | Default `20`; minimum `1`; maximum `100`. |
| `cursor` | string | Opaque cursor bound to the normalized filters and page size. |

Only packages with at least one imported release are listed. Results are ordered
by stable package insertion ID ascending. Each item contains `type`,
`package_id`, `name`, and `latest_release` with `version`, `channel`, and
`published_at`. The latest stable release by SemVer is summarized when one
exists; otherwise the SemVer-highest release across channels is used.

The success response follows the same cursor envelope as the release-list
endpoint: `data` is an array and `meta` contains `per_page`, `next_cursor`, and
`has_more`. Empty results have `data: []`, `next_cursor: null`, and
`has_more: false`. Search and pagination errors use the existing RFC 9457
`invalid_query` and `invalid_cursor` codes. The endpoint is public and
read-only, shares Registry rate limits, and does not change the manifest
signature, trust, or compatibility requirements.
