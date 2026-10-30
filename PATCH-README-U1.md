# Pixely Update Manager — U1 patch

Baseline inspected: `Pixely-Platform-docs(4).zip`, Git commit `25bc6e6` (`feature/sprint-s5-database-extension`).

## Included

- `app/Core/Updates/`: U1 contracts, immutable DTOs, enums and verification exception.
- `docs/architecture/update-manager.md`: architecture, lifecycle, security boundaries and delivery phases.
- `docs/architecture/adr/ADR-0009-update-manager-and-official-registry.md`: accepted registry/hosting decision.
- `tests/Unit/Core/Updates/`: DTO invariant tests.
- `ROADMAP.md`: U1 progress and follow-up phases.

## Important scope boundary

U1 defines the architecture and contracts only. It does not register a CLI command, contact a registry, download artifacts, run Composer/npm, install extensions or modify the existing extension manifest/lifecycle. Those behaviours are intentionally deferred to later phases.

## Apply

Extract this archive at the repository root, preserving its directory structure, then review:

```powershell
git status --short
git diff --check
git diff -- ROADMAP.md docs/architecture app/Core/Updates tests/Unit/Core/Updates
```

Run focused tests in Docker:

```powershell
docker compose exec app php artisan test --filter=Core\\Updates
```

Run coding standards for the added PHP files:

```powershell
docker compose exec app composer cs:check
```

Suggested commit:

```powershell
git add ROADMAP.md app/Core/Updates docs/architecture/update-manager.md docs/architecture/adr/ADR-0009-update-manager-and-official-registry.md tests/Unit/Core/Updates
git commit -m "feat(updates): define update manager foundation"
```

Do not start U2 until the patch is applied to the current working tree and the focused tests and coding standards have been checked.
