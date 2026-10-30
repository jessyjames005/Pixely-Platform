# Pixely Update Manager — U4 patch

## Included

- Exact-host HTTPS allow-list policy per artifact provider.
- Streaming cURL download to a random private `.part` file, with TLS verification, timeout and byte limit; redirects are disabled rather than followed without validation.
- SHA-256 verification and mandatory detached Ed25519 signature verification against operator-pinned public keys.
- Laravel service provider bindings, configuration template, and unit tests.
- U4 security and trust-model documentation.

## Apply

Extract at the repository root and inspect the changes:

```powershell
Expand-Archive -Path .\Pixely-Update-Manager-U4.zip -DestinationPath . -Force
git status --short
git diff --check
```

Register `App\Core\Updates\Providers\UpdateArtifactServiceProvider::class` in `bootstrap/providers.php`, adjacent to the existing U3 `UpdateServiceProvider` entry. Merge this one-line registration into the current file; do not replace the entire provider list.

Review `config/update-manager.php`. Replace the reserved Pixely `.example` hostname with the real official artifact host and configure trusted Ed25519 public keys. Keep private signing keys outside the application repository. The defaults fail closed for Pixely-hosted downloads until configured.

Append the U4 checklist in `PATCH-ROADMAP-U4.md` to the existing `ROADMAP.md`; do not replace the roadmap.

## Verify

```powershell
docker compose exec app php artisan test --filter=ConfiguredArtifactHostPolicyTest
docker compose exec app php artisan test --filter=SodiumUpdateVerifierTest
docker compose exec app composer cs:check
git diff --check
```

The verifier test requires PHP Sodium. The downloader requires PHP cURL. These tests do not make network requests.

## Commit

```powershell
git add app/Core/Updates config/update-manager.php tests/Unit/Core/Updates docs/architecture/update-manager.md ROADMAP.md bootstrap/providers.php
git commit -m "feat(updates): add secure artifact download and verification"
git push
```

Before committing, preserve any existing local changes to `docs/architecture/update-manager.md`, `ROADMAP.md`, and `bootstrap/providers.php`.
