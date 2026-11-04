# Registry server — U14.1, U14.2 and U14.3

U14.1 adds the Registry's local relational store and an operator-run importer
for already signed schema-v1 release manifests. U14.2 exposes a public,
read-only release catalogue over those imported manifests. U14.3 adds public
package discovery and search. Publishing and upload workflows remain out of
scope.

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

## Public release API — U14.2

The API is public (no authentication), read-only, and mounted under `/api/v1`.
It does not redirect to another origin. The endpoints implement the U10
release-list and exact-release contract:

### `GET /api/v1/releases`

Required query parameters are `package_type` (`platform`, `extension`, `theme`,
or `plugin`) and the exact non-empty `package_id`. `channel` defaults to
`stable` and accepts `stable`, `beta`, or `dev`. Optional `after_version` must
be valid SemVer and excludes that version and every older version. `per_page`
defaults to 20 and is limited to 1–100. `cursor` is opaque, encrypted and
authenticated with the application key, bound to those filters, and rejected
as `400 invalid_cursor` if tampered with or reused with different filters.

Results are ordered by SemVer descending, then deterministic version and
release-ID tie-breaks. The cursor captures a release-ID snapshot, so releases
imported after traversal begins do not shift subsequent pages. The success
body is `{data: [{manifest: <complete manifest>}, ...], meta: {per_page,
next_cursor, has_more}}`. The original `manifest_json` is embedded without
reconstructing, changing, or re-signing it. A package that exists but has no
matching releases returns an empty `data`, `has_more: false`, and
`next_cursor: null`; an unknown package returns `404 package_not_found`.

Optional `runtime[php]`, `runtime[pixely]`, and `runtime[laravel]` members must
be scalar SemVer versions. Unknown runtime members, arrays, and malformed
versions return `400 invalid_query`. These values are currently advisory only:
they are validated but do not filter releases.

### `GET /api/v1/releases/resolve`

Requires `package_type`, exact `package_id`, and exact valid SemVer `version`.
It returns `{data: {manifest: <complete manifest>}}`. Missing package and
missing release return separate `404 package_not_found` and
`404 release_not_found` problems.

Invalid query parameters use `400 invalid_query`; anticipated Registry errors
use RFC 9457 Problem Details with `Content-Type: application/problem+json`.
The endpoints enforce independent limits of 60 requests per minute per IP and
120 requests per minute per `X-Pixely-Client` label. A limit response uses
`429 rate_limited` Problem Details and includes `Retry-After`. Client labels
are untrusted, non-authenticating identifiers, limited to 128 ASCII
letters/digits or `.`, `_`, `:`, and `-`; absent or invalid labels fall back to
the requesting IP for the client limit. The per-IP limit remains an
independent backstop. Deployments must configure trusted proxies correctly so
the framework derives the real client IP safely.

Successful list responses advertise `Cache-Control: public, max-age=60`;
exact-release responses advertise `max-age=300` and an ETag. Reads do not
re-verify signatures: imported manifests have already been verified, and the
Registry serves their stored signed JSON as-is. Clients remain responsible for
local signature verification and compatibility checks.

Runtime compatibility values are validated but currently ignored; the U10
contract leaves server-side runtime filtering advisory. Conditional `304`
responses and cursor expiration are not implemented. Query strings are limited
to 4096 bytes, package IDs to 255 characters, and cursors to 2048 characters.
SemVer ordering is computed in memory over the matching releases for one
package; pagination response size is capped, but the per-package scan is not
currently bounded.

## Public package catalogue — U14.3

`GET /api/v1/packages` is public and read-only. It lists packages that have at
least one imported release. `q` optionally performs a case-insensitive partial
match against the package name or opaque package ID; `package_type` optionally
limits results to `platform`, `extension`, `theme`, or `plugin`. Search text is
trimmed, limited to 100 characters, and treats SQL wildcard characters
literally. `per_page` defaults to 20 and is limited to 1–100.

The response is `{data: [{type, package_id, name, latest_release:
{version, channel, published_at}}, ...], meta: {per_page, next_cursor,
has_more}}`. Each package's summary selects the SemVer-highest stable release;
if no stable release exists, it selects the SemVer-highest available release
across channels. The catalogue is ordered by package insertion ID ascending.
Opaque encrypted cursors bind the normalized query, package type and page
size, and capture package/release ID high-water marks so newly imported
packages and releases do not extend an in-progress traversal. Invalid or
filter-mismatched cursors return `400 invalid_cursor`; invalid search and
pagination parameters return `400 invalid_query`. Empty results use `data: []`
and null `next_cursor`.

Catalogue queries share the Registry's per-IP 60/minute and per-client
120/minute limits and return RFC 9457 Problem Details on throttling. Responses
advertise `Cache-Control: public, max-age=60`. This catalogue does not expose
release manifests, descriptions, categories, ratings, download statistics, or
publishing operations.

## U14.1 scope

U14.1 provided database persistence and the local Artisan import command.
Publishing, upload, account management, and moderation remain out of scope.
