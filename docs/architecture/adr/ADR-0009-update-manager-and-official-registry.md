# ADR-0009: Update Manager and Official Pixely Registry

- **Status:** Accepted
- **Date:** 2026-10-30
- **Decision owners:** Pixely Platform maintainers

## Context

Pixely needs one safe update workflow for platform dependencies and installable extensions, plugins and themes. Extension packages may be distributed through Pixely infrastructure or GitHub/GitLab release assets. Treating each hosting provider as an independent registry would fragment package identity, compatibility rules and trust policy.

Pixely already has an extension manifest, dependency resolver, installer, migration runner, upgrade runner and lifecycle manager. A new update system must compose these capabilities rather than replace them.

## Decision

1. Pixely will own one official registry as the authoritative catalogue for package identity, release metadata, compatibility, dependencies, conflicts, channels and artifact integrity metadata.
2. Artifact hosting is independent from registry authority. The official registry may reference Pixely-hosted, GitHub-hosted or GitLab-hosted release artifacts.
3. Core update responsibilities will be exposed through contracts under `App\Core\Updates`.
4. Downloaded artifacts must pass checksum and trusted publisher-signature verification before installation.
5. Extension updates must reuse the existing extension installer, versioning and migration lifecycle.
6. The initial default channel is `stable`; `beta` and `dev` require explicit opt-in.
7. Major upgrades, destructive migrations and downtime-sensitive operations require explicit confirmation.
8. The update manager will be delivered incrementally, beginning with U1 contracts and architecture. No registry server or updater execution is introduced in U1.

## Consequences

### Positive

- One canonical package identity and release policy.
- Hosting providers can change without changing registry semantics.
- GitHub/GitLab can be used for artifact delivery without trusting repository metadata as the publication authority.
- The update engine remains testable through interfaces and fake adapters.
- Existing extension lifecycle and migration behaviour remain the source of truth.

### Trade-offs

- Pixely must operate and secure an official registry service.
- Registry availability becomes important for update discovery (installed software continues to run if the registry is unavailable).
- Signature key management, rotation and revocation must be designed before remote installation is enabled.
- Git-hosted artifacts require strict host and redirect validation.

## Rejected alternatives

### Treat GitHub and GitLab as independent registries

Rejected because package identity, trust, compatibility and release metadata would become fragmented and provider-specific.

### Allow extensions to declare arbitrary update URLs in `extension.php`

Rejected because executable local manifests should not be trusted to nominate arbitrary remote code sources, and because it would bypass the official registry's publication policy.

### Replace the existing extension installer

Rejected because Pixely already has package validation, staging, versioning, migration and audit components that should be reused.

## Follow-up

- Define the official registry API and signed release-manifest format.
- Define signing-key rotation and revocation policy.
- Implement Composer/npm adapters and structured audit output.
- Implement GitHub/GitLab artifact source adapters with strict allow-lists.
- Design backup/recovery semantics for file updates and database migrations.
