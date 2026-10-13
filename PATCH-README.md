# Pixely Platform — S4 frontend dynamic route registry

## Included changes

- Adds a Core-owned, allowlisted frontend route registry using lazy-loaded extension route modules.
- Registers routes only when the current user's server-filtered extension navigation exposes the extension.
- Removes stale extension routes when the extension is disabled, no longer visible, or navigation refresh fails.
- Protects `/admin/*` paths while dynamic routes are being resolved and prevents unknown admin paths from falling through to public pages.
- Moves Gallery, Files, Translations, Tuleap, and Cinema Movie frontend route declarations into their owning extension directories.
- Fixes Gallery's navigation declaration to use `to` and `extensionId`, required by the frontend capability parser.
- Updates `make:extension` to generate `resources/js/routes.ts` and documents the new convention.

## Apply

Extract the ZIP at the root of the existing Pixely-Platform repository, preserving the included paths. Review the resulting diff before committing.

PowerShell alternative (run from the repository root):

```powershell
Expand-Archive -LiteralPath .\Pixely-Platform-S4-dynamic-route-registry.zip -DestinationPath . -Force
```

## Validation

Run in the project's Docker/dev environment:

```powershell
npm run test -- resources/js/router/extensionRouteRegistry.test.ts
npm run typecheck
npm run build:front
docker compose exec app php artisan test --filter=MakeExtensionTest
docker compose exec app php artisan test --filter=ExtensionSdkV2Test
```

This patching environment did not have usable Node dependencies (`vitest` was absent) and lacked PHP's DOM extension for Pest, so those suites could not be run here. PHP syntax checks and `git diff --check` passed.
