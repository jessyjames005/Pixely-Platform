# Pixely Update Manager

## Purpose

The Update Manager is a Core capability that coordinates safe updates for the Pixely platform and its installable components. It provides one orchestration boundary for platform dependencies, extensions, plugins and themes while keeping package-manager and hosting details behind contracts.

The first implementation is intentionally incremental. U1 establishes the architecture and contracts; it does not yet contact a remote registry, download packages, execute package managers or install updates.

## Product decisions

- **Registry authority:** the official Pixely Registry is the only catalogue and release-metadata authority.
- **Artifact hosting:** release archives may be hosted by Pixely or by approved GitHub/GitLab release assets. A hosting provider is not a registry and cannot independently publish trusted release metadata.
- **Component coverage:** platform dependencies, extensions, plugins and themes.
- **Default channel:** `stable`. `beta` and `dev` are opt-in channels.
- **Safety default:** no major-version upgrades, destructive migrations or unsigned artifacts without explicit policy and user confirmation.

## Responsibilities

### Update Orchestrator

Coordinates discovery, compatibility checks, dependency planning, staging, verification, installation, health checks and reporting. It must not implement Composer, npm, registry HTTP or archive extraction itself.

### Dependency update adapters

Dedicated adapters will handle Composer and npm. They must preserve the relevant lockfiles, run in the correct Docker service, expose dry-run/check modes where supported and return structured results. The Update Manager must not directly edit lockfiles.

### Official Registry client

`UpdateRegistryInterface` reads package metadata and release manifests from the official Pixely Registry. Registry responses are untrusted input and must be schema-validated before becoming DTOs.

The registry owns package identity, release version, compatibility constraints, dependencies, conflicts, channel, release notes and artifact integrity metadata.

### Artifact sources

`ArtifactSourceInterface` retrieves an artifact from an approved host. Initial providers are Pixely-hosted storage, GitHub Releases and GitLab Releases. The source only transports bytes; it does not establish package identity or trust.

### Verification

`UpdateVerifierInterface` validates the SHA-256 checksum and publisher signature against trusted keys managed by Pixely. Checksum validation detects corruption; a trusted signature establishes authenticity. Both must pass before installation. A missing or invalid signature blocks installation by default.

### Installer

`UpdateInstallerInterface` accepts only a planned update and a verified staged artifact. Extension installation must reuse the existing `ExtensionInstaller`, `ExtensionPackageValidator`, `ExtensionUpgradeRunner` and extension migration services where appropriate. It must not duplicate extension lifecycle or migration logic.

## Contracts introduced in U1

| Contract / DTO | Responsibility |
|---|---|
| `UpdateRegistryInterface` | Query the official registry for updates and exact releases |
| `ArtifactSourceInterface` | Download an artifact from an approved hosting provider |
| `UpdateVerifierInterface` | Verify checksum and signature before installation |
| `UpdateInstallerInterface` | Install a verified update through the relevant subsystem |
| `UpdateQuery` | Describe installed component, version, channel and runtime context |
| `UpdateCandidate` | Immutable registry release metadata |
| `ArtifactReference` | Hosting location and integrity/signature metadata |
| `UpdatePlanItem` | One resolved operation in an update plan |
| `InstallationResult` | Structured result of an installation attempt |

The contracts are deliberately independent of HTTP clients, GitHub/GitLab SDKs, Composer, npm and database storage.

## Component identifiers and manifest compatibility

The current extension manifest remains the source of truth for installed extension identity and version (`id`, `version`, `minimum_kernel_version`, `requires`, class and surfaces). U1 does not replace `extension.php` or alter the current extension lifecycle.

A future manifest revision may add an optional registry namespace/source hint, but registry credentials, download URLs, signatures and release-channel policy must not be embedded in an installed extension's executable PHP manifest. Those values belong to trusted registry metadata and local platform configuration.

The registry package identifier should be namespaced (for example `pixely/gallery`) while the installed extension identifier remains the existing local ID (`gallery`). A mapping/normalization policy must be explicit and collision-checked before remote installation is implemented.

## Update lifecycle

```text
Installed component inventory
        |
        v
Official Registry discovery
        |
        v
Compatibility and dependency resolution
        |
        v
Update plan (display / dry-run)
        |
        v
Download to isolated staging directory
        |
        v
Checksum + trusted signature verification
        |
        v
Backup / maintenance policy
        |
        v
Install through existing subsystem
        |
        v
Migrations and health checks
        |
   +----+----+
   |         |
 success   failure
   |         |
 commit   rollback files / recovery workflow
   |         |
   +----+----+
        |
        v
Audit history and report
```

A database migration is not automatically reversible merely because files can be restored. Migrations must follow expand/contract practices; destructive data changes require a separately designed backup and recovery policy. The existing extension upgrade runner remains authoritative for extension version steps.

## Security boundaries

- Only the official Pixely Registry may author trusted release metadata.
- GitHub/GitLab are artifact hosts, not trust authorities.
- Enforce HTTPS and an allow-list of approved hosts; validate every redirect target.
- Reject path traversal, archive links escaping the staging directory, oversized archives and unexpected file types.
- Verify checksum and trusted signature before extracting or installing an artifact.
- Never execute scripts from a downloaded package during discovery or verification.
- Do not expose registry tokens in command output, logs or exception messages.
- Require explicit confirmation for major upgrades, downtime, destructive migrations and rollback operations.
- Keep a durable audit record of actor, package, versions, source, verification result and operation outcome.

## CLI direction

The planned single entry point is `php artisan pixely:update`. It will support `--check`, `--dry-run`, `--backend`, `--frontend`, `--extensions`, `--security`, `--extension=<id>` and `--channel=<stable|beta|dev>`. U1 does not register this command yet; the command belongs to a later vertical slice once orchestration services exist.

## Delivery phases

| Phase | Scope |
|---|---|
| U1 | Architecture decision, contracts, DTOs, enums and exception boundary |
| U2 | Composer/npm adapters, lockfile-safe execution and security audit reporting |
| U3 | Installed extension inventory and official-registry discovery |
| U4 | Pixely/GitHub/GitLab artifact sources and signature verification |
| U5 | Dependency planning, staging, installation, migration coordination and recovery |
| U6 | Unified Artisan CLI, history, reporting and integration tests |

## U1 non-goals

- No registry server or publisher portal.
- No remote HTTP calls or credentials.
- No GitHub/GitLab API integration.
- No Composer/npm update execution.
- No extension manifest format migration.
- No automatic database rollback.
- No admin UI.

## Testing strategy

U1 contracts are intentionally framework-independent. Tests for DTO invariants are added with their implementations. Subsequent phases must use fake registry/source/verifier/installer implementations to test orchestration without network access, then add integration tests for the real adapters and Docker execution.

## U2 — Composer and npm dependency adapters

U2 introduces process-backed adapters for Composer and npm behind `DependencyUpdaterInterface` and `ProcessRunnerInterface`.

- Composer checks direct dependencies with `composer outdated --direct --format=json` and updates within the constraints already declared in `composer.json` using `composer update --with-all-dependencies --no-interaction`.
- npm checks the installed project with `npm outdated --json`, updates within the semver ranges already declared in `package.json` using `npm update`, and applies non-forced security fixes with `npm audit fix`. Composer reports security advisories through `composer audit`; it deliberately refuses a misleading “security-only update” because Composer has no equivalent safe selective operation.
- Neither adapter uses a shell, `--force`, hand-edits lockfiles, or silently changes declared version ranges.
- Dry-run is passed to the underlying package manager. Audit results preserve non-zero exit codes because an audit finding is report data, not a process-launch failure.
- Package-manager execution is bounded by a one-hour process timeout. A later orchestration layer must capture the pre-update lockfiles and restore them if a package-manager operation fails.

### Docker execution boundary

The current Docker topology deliberately separates PHP/Composer (`app`) from Node/npm (`node`). The npm adapter must therefore execute inside the Node service (or another explicitly configured Node runtime); it must not assume that `npm` exists in the PHP container. The final single-command CLI in U6 will coordinate both service contexts through the supported host/Compose entry point. Do not install a second, drifting Node toolchain into the PHP application image merely to make the adapters share a process.

### U2 non-goals

- No unified Artisan command or cross-container dispatcher yet (U6).
- No automatic restoration of lockfiles yet; the orchestrator will own snapshots and recovery.
- No extension discovery, registry calls, artifact downloads or extension installation (U3–U5).
- No automatic major-version upgrades and no `npm audit fix --force`.


## U3 — Installed extension inventory and registry discovery

U3 adds a read-only inventory and discovery vertical slice on top of the existing extension subsystem. `InstalledExtensionInventory` delegates filesystem discovery and manifest parsing to the existing `ExtensionRepository::manifests()` and reads the authoritative installed version from `ExtensionVersionRepository`. For legacy extensions without a version row, it falls back to the manifest version. It does not instantiate extension classes or modify extension state.

Local extension identifiers remain unchanged (for example `gallery`). `PixelyExtensionPackageIdResolver` maps them to official registry identifiers (`pixely/gallery`). The mapping is isolated behind `ExtensionPackageIdResolverInterface` so a future verified namespace mapping can replace the default without changing discovery.

`ExtensionUpdateDiscovery` submits one `UpdateQuery` per installed extension to `UpdateRegistryInterface`. Registry results are treated as untrusted: only extension candidates with the exact expected package identifier and requested channel, and a strictly newer version, are eligible. The highest eligible version is returned. The service is read-only and does not download archives, verify signatures or install code.

No registry HTTP endpoint, response schema, authentication flow or API client is invented in U3. The official-registry transport is implemented only after its API contract is defined. GitHub and GitLab remain artifact hosts and cannot author release metadata.

### U3 non-goals

- No HTTP registry client or registry server implementation.
- No artifact downloads, archive extraction or signature verification (U4).
- No dependency graph planning, installation, migrations or rollback (U5).
- No CLI command or update UI (U6).
