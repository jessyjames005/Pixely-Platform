# Pixely Update Manager — U3 patch

## Scope

U3 adds a read-only installed-extension inventory and official-registry discovery service. It deliberately does not download, verify or install artifacts; those are U4 and U5 responsibilities.

## Existing extension lifecycle reused

- `ExtensionRepository::manifests()` discovers `app/Extensions/*/extension.php` using the existing dependency resolver.
- `ExtensionVersionRepository::find()` supplies the tracked installed version; when no version record exists, the manifest version is used for legacy installations.
- Local extension ids are mapped to official registry package ids using `pixely/<extension-id>`.
- The existing `ExtensionInstaller`, `ExtensionPackageValidator`, `ExtensionUpgradeRunner` and migration runner remain unchanged.

## Added files

- `InstalledExtensionInventoryInterface` and `InstalledExtensionInventory`
- `ExtensionPackageIdResolverInterface` and `PixelyExtensionPackageIdResolver`
- `InstalledExtension` and `ExtensionUpdateAvailability`
- `ExtensionUpdateDiscovery`
- `UpdateServiceProvider`
- Unit tests for package id mapping and discovery filtering
- Updated update-manager architecture
- `PATCH-ROADMAP-U3.md` with roadmap lines to append (preserves the existing roadmap)
- Registered `UpdateServiceProvider` in `bootstrap/providers.php`

## Registry boundary

`ExtensionUpdateDiscovery` depends only on the existing U1 `UpdateRegistryInterface`. No endpoint, response schema, authentication mechanism or HTTP client is invented in U3. The concrete official-registry adapter is intentionally deferred until the registry API contract is defined. GitHub and GitLab remain artifact hosts, never metadata authorities.

## Apply

Extract at repository root and review:

```powershell
Expand-Archive -Path .\Pixely-Update-Manager-U3.zip -DestinationPath . -Force
git status --short
git diff --check
```

The patch registers `UpdateServiceProvider` in `bootstrap/providers.php`, following the project's existing explicit provider list.

## Verify

```powershell
docker compose exec app php artisan test --filter=ExtensionUpdateDiscoveryTest
docker compose exec app php artisan test --filter=PixelyExtensionPackageIdResolverTest
docker compose exec app composer cs:check
```

U3 tests use fakes for the registry and do not require network access or credentials.
